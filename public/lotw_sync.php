<?php
// public/lotw_sync.php

// --- Debug Logging Setup ---
define('LOG_FILE', __DIR__ . '/lotw_debug.log');
// Clear previous log
if (file_exists(LOG_FILE)) { unlink(LOG_FILE); }
function log_debug($message) {
    file_put_contents(LOG_FILE, date('Y-m-d H:i:s') . ' - ' . $message . "\n", FILE_APPEND);
}

log_debug("Script start.");

// Increase execution time for potentially long sync process
set_time_limit(300);

require_once dirname(__DIR__) . '/config/config.php';
log_debug("Config loaded.");
require_once ROOT_PATH . '/src/utils.php';
log_debug("Utils loaded.");
require_once ROOT_PATH . '/src/db.php';
log_debug("DB loaded.");
require_once ROOT_PATH . '/src/adif.php';
log_debug("ADIF parser loaded.");


secure_session_start();
header('Content-Type: application/json');
log_debug("Session started and headers sent.");

// --- 0. Pre-flight Checks for Dependencies ---
if (!function_exists('curl_init')) {
    log_debug("FATAL: cURL function does not exist.");
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server configuration error: The cURL PHP extension is not enabled.']);
    exit();
}
if (!function_exists('openssl_encrypt')) {
    log_debug("FATAL: OpenSSL function does not exist.");
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server configuration error: The OpenSSL PHP extension is not enabled.']);
    exit();
}
log_debug("Pre-flight checks passed.");

// --- 1. Authentication & Setup ---
if (!isset($_SESSION['user_id'])) {
    log_debug("FATAL: No user ID in session.");
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit();
}

try {
    $pdo = get_db_connection();
    $user_id = $_SESSION['user_id'];
    log_debug("DB connection successful. User ID: $user_id");

    $stmt = $pdo->prepare('SELECT lotw_username, lotw_password FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    log_debug("Fetched user from DB.");

    if (!$user || empty($user['lotw_username']) || empty($user['lotw_password'])) {
        log_debug("FATAL: LoTW credentials not found in DB.");
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'LoTW credentials not configured. Please go to Settings.']);
        exit();
    }

    $lotw_user = decrypt($user['lotw_username']);
    $lotw_pass = decrypt($user['lotw_password']);
    log_debug("Credentials decrypted. Username: $lotw_user");

    // --- 2. Find Last Sync Date ---
    $stmt = $pdo->prepare("SELECT MAX(qso_date) as last_date FROM logs WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $last_sync = $stmt->fetchColumn();
    log_debug("Last sync date found: " . ($last_sync ?: 'None'));

    // --- 3. cURL Request ---
    $url = 'https://lotw.arrl.org/lotwuser/lotwreport.adi';
    $params = [
        'login' => $lotw_user, 'password' => $lotw_pass, 'qso_query' => '1',
    ];
    if ($last_sync) {
        $params['qso_startdate'] = $last_sync;
    }

    $query_string = http_build_query($params);
    log_debug("Initiating cURL request to: $url with query string.");

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url . '?' . $query_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'QSL-Card-Manager/1.0');
    $response_body = curl_exec($ch);

    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        curl_close($ch);
        log_debug("FATAL: cURL execution error: $error_msg");
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'cURL Error: ' . $error_msg]);
        exit();
    }

    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);
    log_debug("cURL request completed. HTTP Code: $http_code, Content-Type: $content_type");

    // --- 4. Process Response ---
    if ($http_code != 200 || strpos($content_type, 'text/html') !== false) {
        log_debug("FATAL: LoTW login failed or returned HTML error page.");
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'LoTW login failed. Please check your credentials in Settings.']);
        exit();
    }
    log_debug("Response seems to be valid ADIF data.");

    // --- 5. Database Operations ---
    $new_qso_count = 0;
    $adif_parser = new adif($response_body);
    $records = $adif_parser->parser();
    log_debug("ADIF data parsed. Found " . count($records) . " records.");
    
    if (!empty($records)) {
        $insert_stmt = $pdo->prepare('INSERT INTO logs (user_id, qso_date, time_on, `call`, band, freq, mode, rst_sent, rst_rcvd, qsl_sent, qsl_rcvd) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $check_stmt = $pdo->prepare('SELECT id FROM logs WHERE user_id = ? AND `call` = ? AND qso_date = ? AND time_on = ?');
        
        foreach ($records as $record) {
            if (empty($record['CALL']) || empty($record['QSO_DATE']) || empty($record['TIME_ON'])) continue;
            $qso_date = date('Y-m-d', strtotime($record['QSO_DATE']));
            $time_on = date('H:i:s', strtotime($record['TIME_ON']));
            $check_stmt->execute([$user_id, $record['CALL'], $qso_date, $time_on]);
            if ($check_stmt->fetch()) continue;
            $insert_stmt->execute([$user_id, $qso_date, $time_on, $record['CALL'] ?? null, $record['BAND'] ?? null, $record['FREQ'] ?? null, $record['MODE'] ?? null, $record['RST_SENT'] ?? null, $record['RST_RCVD'] ?? null, $record['QSL_SENT'] ?? 'N', $record['QSL_RCVD'] ?? 'N']);
            $new_qso_count++;
        }
        log_debug("Finished processing records. New QSOs: $new_qso_count");
    }

    // --- 6. Final Response ---
    log_debug("Script finished successfully.");
    echo json_encode(['success' => true, 'message' => "Sync complete. Added {$new_qso_count} new QSOs."]);

} catch (Exception $e) {
    log_debug("FATAL: An uncaught exception occurred: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An unexpected server error occurred: ' . $e->getMessage()]);
}

exit();
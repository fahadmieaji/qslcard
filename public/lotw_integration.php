<?php
// public/lotw_integration.php

require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_PATH . '/src/utils.php';
require_once ROOT_PATH . '/src/db.php';
require_once ROOT_PATH . '/src/adif.php';

secure_session_start();
require_login();

$pageTitle = 'Sync with LoTW';
$user_id = $_SESSION['user_id'];
$message = '';
$error_message = '';
$pdo = get_db_connection();

// Fetch user's existing LoTW username for the form
$stmt_get_user = $pdo->prepare("SELECT lotw_username FROM users WHERE id = ?");
$stmt_get_user->execute([$user_id]);
$lotw_username = decrypt($stmt_get_user->fetchColumn());


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lotw_user_post = $_POST['lotw_username'] ?? '';
    $lotw_pass_post = $_POST['lotw_password'] ?? '';
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : '1970-01-01';

    if (empty($lotw_user_post) || empty($lotw_pass_post)) {
        $error_message = "Please provide both your LoTW username and password.";
    } else {
        // 1. Save credentials to the users table
        $stmt_save_creds = $pdo->prepare("UPDATE users SET lotw_username = ?, lotw_password = ? WHERE id = ?");
        $stmt_save_creds->execute([encrypt($lotw_user_post), encrypt($lotw_pass_post), $user_id]);
        $lotw_username = $lotw_user_post;

        // 2. Fetch data from LoTW API
        $url = 'https://lotw.arrl.org/lotwuser/lotwreport.adi';
        $params = [
            'login' => $lotw_user_post, 'password' => $lotw_pass_post,
            'qso_query' => '1', 'qso_adif' => '1', 'qso_qsl' => 'yes',
            'qso_startdate' => $start_date,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code != 200 || strpos($response, '<!DOCTYPE HTML') !== false) {
            $error_message = "Failed to fetch data from LoTW. Please check your username and password.";
        } else {
            // 3. Parse and Store QSO Data
            $adif_parser = new adif($response);
            $records = $adif_parser->parser();
            $new_qso_count = 0;
            $updated_qso_count = 0;

            if (!empty($records)) {
                $sql_upsert = "
                    INSERT INTO logs (user_id, `call`, band, freq, mode, qso_date, time_on, qsl_rcvd, qsl_rdate, source) 
                    VALUES (:user_id, :call, :band, :freq, :mode, :qso_date, :time_on, :qsl_rcvd, :qsl_rdate, 'lotw')
                    ON DUPLICATE KEY UPDATE 
                        qsl_rcvd = VALUES(qsl_rcvd), 
                        qsl_rdate = VALUES(qsl_rdate),
                        source = 'lotw'
                ";
                $stmt_upsert = $pdo->prepare($sql_upsert);

                foreach ($records as $record) {
                    if (empty($record['CALL']) || empty($record['QSO_DATE']) || empty($record['TIME_ON'])) continue;
                    
                    $stmt_upsert->execute([
                        ':user_id' => $user_id,
                        ':call' => $record['CALL'],
                        ':band' => $record['BAND'] ?? null,
                        ':freq' => $record['FREQ'] ?? null,
                        ':mode' => $record['MODE'] ?? null,
                        ':qso_date' => date('Y-m-d', strtotime($record['QSO_DATE'])),
                        ':time_on' => date('H:i:s', strtotime($record['TIME_ON'])),
                        ':qsl_rcvd' => $record['QSL_RCVD'] ?? 'N',
                        ':qsl_rdate' => isset($record['QSL_RDATE']) ? date('Y-m-d', strtotime($record['QSL_RDATE'])) : null,
                    ]);

                    $rowCount = $stmt_upsert->rowCount();
                    if ($rowCount === 1) { $new_qso_count++; }
                    if ($rowCount === 2) { $updated_qso_count++; }
                }

                // 4. Update Sync Time
                $pdo->prepare("UPDATE users SET lotw_last_sync = NOW() WHERE id = ?")->execute([$user_id]);
                $message = "Sync complete! Added $new_qso_count new QSOs and updated $updated_qso_count existing QSOs with LoTW data.";
            } else {
                $message = "No new records found from LoTW for the selected date range.";
            }
        }
    }
}
include_once ROOT_PATH . '/templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h3 class="card-title">Sync with LoTW</h3>
                <?php if ($message) echo "<div class='alert alert-success'>$message</div>"; ?>
                <?php if ($error_message) echo "<div class='alert alert-danger'>$error_message</div>"; ?>

                <form action="lotw_integration.php" method="POST">
                    <div class="mb-3">
                        <label for="lotw_username" class="form-label">LoTW Username</label>
                        <input type="text" name="lotw_username" class="form-control" value="<?php echo htmlspecialchars($lotw_username); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="lotw_password" class="form-label">LoTW Password</label>
                        <input type="password" name="lotw_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="start_date" class="form-label">Sync QSOs From Date</label>
                        <input type="date" name="start_date" class="form-control" value="1970-01-01">
                    </div>
                    <button type="submit" class="btn btn-primary">Sync Now</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include_once ROOT_PATH . '/templates/footer.php'; ?>

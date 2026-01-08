<?php
// public/install.php

define('INSTALLING', true);

// Ensure config is loaded
require_once dirname(__DIR__) . '/config/config.php';

// If install.lock file exists, installation has already been completed.
if (file_exists(INSTALL_LOCK_FILE)) {
    header('Location: index.php');
    exit();
}

function send_json_response($status, $message, $progress, $next_step = '', $error = '', $data = []) {
    header('Content-Type: application/json');
    $response = [
        'status' => $status,
        'message' => $message,
        'progress' => $progress,
        'next_step' => $next_step,
        'error' => $error
    ];
    echo json_encode(array_merge($response, $data));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // For AJAX requests, we need the real functions
    require_once ROOT_PATH . '/src/utils.php';
    require_once ROOT_PATH . '/src/db.php';
    
    $action = $_POST['action'];

    // --- Get data from POST ---
    $db_host = $_POST['db_host'] ?? '';
    $db_port = $_POST['db_port'] ?? '3306';
    $db_name = $_POST['db_name'] ?? '';
    $db_user = $_POST['db_user'] ?? '';
    $db_pass = $_POST['db_pass'] ?? '';
    $admin_username = $_POST['admin_username'] ?? '';
    $admin_password = $_POST['admin_password'] ?? '';
    $admin_callsign = $_POST['admin_callsign'] ?? '';
    $root_url = $_POST['root_url'] ?? '';

    switch ($action) {
        case 'install':
            // Initial validation
            if (version_compare(PHP_VERSION, '8.0.0', '<') || !extension_loaded('pdo_mysql') || !extension_loaded('gd')) {
                send_json_response('error', 'System requirements not met.', 0, '', 'PHP version or extensions are not correct.');
            }
            if (empty($db_host) || empty($db_name) || empty($db_user) || empty($admin_username) || empty($admin_password) || empty($admin_callsign)) {
                send_json_response('error', 'All form fields are required.', 0, '', 'All form fields are required.');
            }
            send_json_response('success', 'Starting installation...', 10, 'update_config');
            break;

        case 'update_config':
            $config_path = ROOT_PATH . '/config/config.php';
            $config_content = file_get_contents($config_path);
            if ($config_content === false) {
                send_json_response('error', 'Could not read config file.', 10, '', 'File read error.');
            }

            $config_content = preg_replace("/define\('DB_HOST', '.*?'\);/", "define('DB_HOST', '$db_host');", $config_content);
            $config_content = preg_replace("/define\('DB_NAME', '.*?'\);/", "define('DB_NAME', '$db_name');", $config_content);
            $config_content = preg_replace("/define\('DB_USER', '.*?'\);/", "define('DB_USER', '$db_user');", $config_content);
            $config_content = preg_replace("/define\('DB_PASS', '.*?'\);/", "define('DB_PASS', '$db_pass');", $config_content);

            // Update ROOT_URL from user input
            $config_content = preg_replace("/define\('ROOT_URL', '.*?'\);/", "define('ROOT_URL', '$root_url');", $config_content);

            if (file_put_contents($config_path, $config_content) === false) {
                send_json_response('error', 'Could not write to config file.', 10, '', 'File write error. Check permissions.');
            }
            send_json_response('success', 'Configuration file updated.', 30, 'test_db');
            break;

        case 'test_db':
            try {
                $pdo = new PDO("mysql:host=$db_host;port=$db_port", $db_user, $db_pass);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                // Create database if it doesn't exist
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name`");
            } catch (PDOException $e) {
                send_json_response('error', 'Database connection failed.', 30, '', $e->getMessage());
            }
            send_json_response('success', 'Database connection successful.', 50, 'create_tables');
            break;

        case 'create_tables':
            try {
                $pdo = new PDO("mysql:host=$db_host;port=$db_port;dbname=$db_name", $db_user, $db_pass);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $sql = file_get_contents(ROOT_PATH . '/db_schema.sql');
                $pdo->exec($sql);
            } catch (PDOException $e) {
                send_json_response('error', 'Error creating database tables.', 50, '', $e->getMessage());
            }
            send_json_response('success', 'Database tables created.', 70, 'create_user');
            break;

        case 'create_user':
            try {
                $pdo = new PDO("mysql:host=$db_host;port=$db_port;dbname=$db_name", $db_user, $db_pass);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, callsign) VALUES (?, ?, ?)");
                $stmt->execute([$admin_username, $hashed_password, $admin_callsign]);

            } catch (PDOException $e) {
                send_json_response('error', 'Error creating admin user.', 70, '', $e->getMessage());
            }
            send_json_response('success', 'Admin user created.', 90, 'finish_install');
            break;

        case 'finish_install':
            file_put_contents(INSTALL_LOCK_FILE, 'installed');
            send_json_response('success', 'Installation finished!', 100, '', '', ['root_url' => $root_url]);
            break;

        default:
            send_json_response('error', 'Invalid action.', 0, '', 'Invalid action specified.');
            break;
    }
}

// ==================================================================
// HTML Part
// ==================================================================
$pageTitle = 'Installation';
include_once ROOT_PATH . '/templates/header.php';
?>

<style>
    .install-step { display: none; }
    .install-step.active { display: block; }
</style>

<div class="row justify-content-center h-100 d-flex align-items-center">
    <div class="col-md-8">
        <div class="card animated-fadeInUp">
            <div class="card-header bg-gradient-primary text-white">
                <h3><i class="bi bi-gear me-2"></i>Application Installation</h3>
            </div>
            <div class="card-body">

                <!-- Step 1: System Requirements -->
                <div id="step1" class="install-step active">
                    <h4>System Requirements</h4>
                    <ul class="list-group mb-4">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            PHP Version >= 8.0
                            <?php $php_ok = version_compare(PHP_VERSION, '8.0.0', '>='); ?>
                            <span class="badge bg-<?php echo $php_ok ? 'success' : 'danger'; ?>">
                                <i class="bi bi-<?php echo $php_ok ? 'check-circle' : 'x-circle'; ?>"></i> <?php echo PHP_VERSION; ?>
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            PDO MySQL Extension
                            <?php $pdo_ok = extension_loaded('pdo_mysql'); ?>
                            <span class="badge bg-<?php echo $pdo_ok ? 'success' : 'danger'; ?>">
                                <i class="bi bi-<?php echo $pdo_ok ? 'check-circle' : 'x-circle'; ?>"></i> <?php echo $pdo_ok ? 'Enabled' : 'Disabled'; ?>
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            GD Library
                            <?php $gd_ok = extension_loaded('gd'); ?>
                            <span class="badge bg-<?php echo $gd_ok ? 'success' : 'danger'; ?>">
                                <i class="bi bi-<?php echo $gd_ok ? 'check-circle' : 'x-circle'; ?>"></i> <?php echo $gd_ok ? 'Enabled' : 'Disabled'; ?>
                            </span>
                        </li>
                    </ul>
                    <button class="btn btn-primary next-step" <?php if (!$php_ok || !$pdo_ok || !$gd_ok) echo 'disabled'; ?>>Next</button>
                    <?php if (!$php_ok || !$pdo_ok || !$gd_ok): ?>
                        <p class="text-danger mt-2">Please fix the issues above to proceed.</p>
                    <?php endif; ?>
                </div>

                <!-- Step 2: Configuration -->
                <div id="step2" class="install-step">
                    <form id="install-form" method="POST">
                        <h4>Database Configuration</h4>
                        <div class="mb-3">
                            <label for="db_host" class="form-label">Database Host</label>
                            <input type="text" class="form-control" id="db_host" name="db_host" value="localhost" required>
                        </div>
                        <div class="mb-3">
                            <label for="db_port" class="form-label">Database Port</label>
                            <input type="text" class="form-control" id="db_port" name="db_port" value="3306" required>
                        </div>
                        <div class="mb-3">
                            <label for="db_name" class="form-label">Database Name</label>
                            <input type="text" class="form-control" id="db_name" name="db_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="db_user" class="form-label">Database User</label>
                            <input type="text" class="form-control" id="db_user" name="db_user" required>
                        </div>
                        <div class="mb-3">
                            <label for="db_pass" class="form-label">Database Password</label>
                            <input type="password" class="form-control" id="db_pass" name="db_pass">
                        </div>

                        <h4 class="mt-4">Application Configuration</h4>
                        <div class="mb-3">
                            <label for="root_url" class="form-label">Base URL</label>
                            <input type="text" class="form-control" id="root_url" name="root_url" value="<?php echo str_replace('/public/install.php', '', $_SERVER['PHP_SELF']); ?>" required>
                            <div class="form-text">This is the base path of your application. It is auto-detected, but you can change it if it's incorrect.</div>
                        </div>

                        <h4 class="mt-4">Admin User Configuration</h4>
                        <div class="mb-3">
                            <label for="admin_username" class="form-label">Admin Username</label>
                            <input type="text" class="form-control" id="admin_username" name="admin_username" required>
                        </div>
                        <div class="mb-3">
                            <label for="admin_password" class="form-label">Admin Password</label>
                            <input type="password" class="form-control" id="admin_password" name="admin_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="admin_callsign" class="form-label">Admin Callsign</label>
                            <input type="text" class="form-control" id="admin_callsign" name="admin_callsign" required>
                        </div>
                        <button type="button" class="btn btn-secondary prev-step">Previous</button>
                        <button type="submit" class="btn btn-primary">Install</button>
                    </form>
                </div>

                <!-- Step 3: Progress -->
                <div id="step3" class="install-step">
                    <h4>Installation Progress</h4>
                    <div class="progress mb-3">
                        <div id="progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                    </div>
                    <div id="log-output" class="alert alert-secondary" style="height: 200px; overflow-y: scroll; font-family: monospace;"></div>
                </div>

                <!-- Step 4: Congratulations -->
                <div id="step4" class="install-step">
                    <div class="alert alert-success text-center">
                        <h4><i class="bi bi-check-circle-fill"></i> Congratulations!</h4>
                        <p>The application has been installed successfully.</p>
                        <p>You will be redirected to the homepage in <span id="countdown">10</span> seconds.</p>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="js/install.js"></script>

<?php
include_once ROOT_PATH . '/templates/footer.php';
?>
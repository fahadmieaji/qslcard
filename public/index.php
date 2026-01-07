<?php
// public/index.php

// Check if application is installed
require_once dirname(__DIR__) . '/config/config.php'; // Need config for INSTALL_LOCK_FILE
if (!file_exists(INSTALL_LOCK_FILE)) {
    header('Location: install.php');
    exit();
}

require_once ROOT_PATH . '/src/utils.php';
require_once ROOT_PATH . '/src/db.php'; // Include the database connection

// Start session and check login status
secure_session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$pdo = get_db_connection();

// Fetch total number of QSOs stored in the database
try {
    $stmt_total_qso_public = $pdo->prepare('SELECT COUNT(*) FROM logs');
    $stmt_total_qso_public->execute();
    $total_qso_public = $stmt_total_qso_public->fetchColumn();
} catch (PDOException $e) {
    // If the table doesn't exist or there's an error, default to 0
    $total_qso_public = 0;
}


// For logged-out users, show a welcome page
$pageTitle = 'S21AF QSL Card Manager';
include_once ROOT_PATH . '/templates/header.php';
?>

<div class="p-3 mb-2 bg-gradient-primary text-white rounded-3 animated-fadeInUp">
    <div class="container-fluid py-5">
        <h1 class="display-5 fw-bold">📻📡 Welcome to QSL Card Manager 📡📻</h1>
        <p class="col-md-8 fs-4">Your one-stop solution for managing amateur radio logs and designing beautiful, custom QSL cards.</p>

        <p>Log your QSOs, upload ADIF files, and create stunning QSL cards with our easy-to-use-designer.</p>

        <p class="fs-5">Over <strong><?php echo number_format($total_qso_public); ?></strong> QSOs logged in this system!</p>

        <hr class="my-4">
        <a class="btn btn-primary btn-lg" href="login.php" role="button">Login</a>
    </div>
</div>

<div class="row align-items-md-stretch">
    <div class="col-md-6">
        <div class="h-100 p-5 text-white bg-dark rounded-3 animated-fadeInUp">
            <h2>📝 Log Management</h2>
            <p>Easily upload and manage your logs. Our system parses standard ADIF files and stores your contacts in a searchable database.</p>
        </div>
    </div>
    <div class="col-md-6">
        <div class="h-100 p-5 bg-light border rounded-3 animated-fadeInUp" style="animation-delay: 0.2s;">
            <h2>🎨 QSL Card Designer</h2>
            <p>Unleash your creativity. Upload a background, place your QSO data, and customize fonts and colors to create the perfect QSL card. Save multiple templates for different occasions.</p>
        </div>
    </div>
</div>



<div class="container mt-4 animated-fadeInUp" style="animation-delay: 0.4s;">
    <h2 class="mb-4">Public Callsign Search</h2>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form id="public-search-form">
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Enter Callsign (e.g., S21AF)" name="callsign" required>
                            <button class="btn btn-primary" type="submit">Search</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div id="search-results-container"></div>
</div>



<?php
include_once ROOT_PATH . '/templates/footer.php';
?>

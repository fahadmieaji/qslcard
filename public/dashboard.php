<?php
// public/dashboard.php

require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_PATH . '/src/utils.php';
require_once ROOT_PATH . '/src/db.php'; // Include the database connection

secure_session_start();
require_login(); // This ensures only logged-in users can access this page

$pdo = get_db_connection();
$user_id = $_SESSION['user_id'];

// --- Fetch Statistics ---

// Total QSOs
$stmt_total_qso = $pdo->prepare('SELECT COUNT(*) FROM logs WHERE user_id = ?');
$stmt_total_qso->execute([$user_id]);
$total_qso = $stmt_total_qso->fetchColumn();

// Unique Callsigns
$stmt_unique_callsigns = $pdo->prepare('SELECT COUNT(DISTINCT `call`) FROM logs WHERE user_id = ?');
$stmt_unique_callsigns->execute([$user_id]);
$unique_callsigns = $stmt_unique_callsigns->fetchColumn();

// QSOs by Band
$stmt_qso_by_band = $pdo->prepare('SELECT band, COUNT(*) as count FROM logs WHERE user_id = ? GROUP BY band ORDER BY count DESC LIMIT 5');
$stmt_qso_by_band->execute([$user_id]);
$qso_by_band = $stmt_qso_by_band->fetchAll();

// QSOs by Mode
$stmt_qso_by_mode = $pdo->prepare('SELECT mode, COUNT(*) as count FROM logs WHERE user_id = ? GROUP BY mode ORDER BY count DESC LIMIT 5');
$stmt_qso_by_mode->execute([$user_id]);
$qso_by_mode = $stmt_qso_by_mode->fetchAll();

// Top 10 Callsigns
$stmt_top_callsigns = $pdo->prepare('SELECT `call`, COUNT(*) as count FROM logs WHERE user_id = ? GROUP BY `call` ORDER BY count DESC LIMIT 10');
$stmt_top_callsigns->execute([$user_id]);
$top_callsigns = $stmt_top_callsigns->fetchAll();

// Recent QSOs
$stmt_recent_qso = $pdo->prepare('SELECT qso_date, time_on, `call`, band, mode FROM logs WHERE user_id = ? ORDER BY qso_date DESC, time_on DESC LIMIT 10');
$stmt_recent_qso->execute([$user_id]);
$recent_qso = $stmt_recent_qso->fetchAll();

// First QSO Date
$stmt_first_qso_date = $pdo->prepare('SELECT MIN(qso_date) FROM logs WHERE user_id = ?');
$stmt_first_qso_date->execute([$user_id]);
$first_qso_date = $stmt_first_qso_date->fetchColumn();

// Last QSO Date
$stmt_last_qso_date = $pdo->prepare('SELECT MAX(qso_date) FROM logs WHERE user_id = ?');
$stmt_last_qso_date->execute([$user_id]);
$last_qso_date = $stmt_last_qso_date->fetchColumn();

// Max QSOs in a day
$stmt_max_qso_day = $pdo->prepare('SELECT qso_date, COUNT(*) as qso_count FROM logs WHERE user_id = ? GROUP BY qso_date ORDER BY qso_count DESC LIMIT 1');
$stmt_max_qso_day->execute([$user_id]);
$max_qso_day = $stmt_max_qso_day->fetch(PDO::FETCH_ASSOC);

// Max QSOs in a month
// Assuming SQLite's STRFTIME for month grouping. Adjust for MySQL if needed (e.g., DATE_FORMAT(qso_date, \'%Y-%m\'))
$stmt_max_qso_month = $pdo->prepare('SELECT DATE_FORMAT(qso_date, \'%Y-%m\') as qso_month, COUNT(*) as qso_count FROM logs WHERE user_id = ? GROUP BY qso_month ORDER BY qso_count DESC LIMIT 1');
$stmt_max_qso_month->execute([$user_id]);
$max_qso_month = $stmt_max_qso_month->fetch(PDO::FETCH_ASSOC);


$pageTitle = 'Dashboard';
include_once ROOT_PATH . '/templates/header.php';

?>

<style>
    /* Custom styles for dashboard cards */
    .dashboard-card {
        border-radius: 0.75rem;
        transition: all 0.3s ease-in-out;
        transform: translateY(0);
        opacity: 1;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }
    .dashboard-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 1rem 2rem rgba(0, 0, 0, 0.25);
    }
    .dashboard-card.initial-hidden {
        opacity: 0;
        transform: translateY(20px);
    }
    .dashboard-card .card-body {
        padding: 1.5rem;
    }
    .dashboard-card .card-title {
        font-size: 2.5rem;
        font-weight: bold;
        margin-bottom: 0.5rem;
    }
    .dashboard-card .card-text {
        font-size: 0.9rem;
        color: rgba(255, 255, 255, 0.7);
    }
    .bg-gradient-primary {
        background: linear-gradient(45deg, #007bff, #0056b3);
    }
    .bg-gradient-success {
        background: linear-gradient(45deg, #28a745, #1e7e34);
    }
    .bg-gradient-info {
        background: linear-gradient(45deg, #17a2b8, #117a8b);
    }
    .bg-gradient-warning {
        background: linear-gradient(45deg, #ffc107, #d39e00);
    }
    .bg-gradient-danger {
        background: linear-gradient(45deg, #dc3545, #b02a37);
    }
    .stat-icon {
        font-size: 3rem;
        opacity: 0.3;
    }
</style>

<div class="container mt-4">
    <div class="py-5 px-4 mb-4 bg-white rounded-3 shadow-lg initial-hidden dashboard-card border-0">
        <div class="container-fluid">
            <h1 class="display-5 fw-bold mb-3">Welcome back, <span class="text-primary"><?php echo htmlspecialchars($_SESSION['username']); ?></span>!</h1>
            <p class="fs-5 lead">Your personalized QSL Card Manager dashboard, offering insights into your logging activity.</p>
            <hr class="my-4">
            <p class="col-md-8 fs-5">Dive into your logs, design custom QSL cards, and analyze your amateur radio journey.</p>
            <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                <a class="btn btn-primary btn-lg px-4 me-md-2" href="log_upload.php" role="button">Upload ADIF Log</a>
                <a class="btn btn-outline-secondary btn-lg px-4" href="logbook.php" role="button">View Logbook</a>
            </div>
        </div>
    </div>

    <h2 class="mt-5 mb-4 text-center text-secondary fw-light">Your Logging Overview</h2>
    <div class="row g-4 mb-4">
        <div class="col-md-6 col-lg-4">
            <div class="card text-white bg-gradient-primary dashboard-card initial-hidden" style="animation-delay: 0.1s;">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title display-5"><?php echo $total_qso; ?></h5>
                        <p class="card-text text-white-50">Total QSOs Logged</p>
                    </div>
                    <i class="bi bi-broadcast stat-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4">
            <div class="card text-white bg-gradient-success dashboard-card initial-hidden" style="animation-delay: 0.2s;">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title display-5"><?php echo $unique_callsigns; ?></h5>
                        <p class="card-text text-white-50">Unique Call Signs Worked</p>
                    </div>
                    <i class="bi bi-people-fill stat-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4">
            <div class="card text-white bg-gradient-warning dashboard-card initial-hidden" style="animation-delay: 0.3s;">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title display-6"><?php echo $first_qso_date ? htmlspecialchars($first_qso_date) : 'N/A'; ?></h5>
                        <p class="card-text text-white-50">First QSO</p>
                    </div>
                    <i class="bi bi-calendar-check stat-icon"></i>
                </div>
                    </div>
                </div>
            </div>
            
            <div class="row g-4 mb-4">
                <div class="col-md-6 col-lg-4">
                    <div class="card text-white bg-gradient-danger dashboard-card initial-hidden" style="animation-delay: 0.4s;">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title display-6"><?php echo $last_qso_date ? htmlspecialchars($last_qso_date) : 'N/A'; ?></h5>
                                <p class="card-text text-white-50">Last QSO</p>
                            </div>
                            <i class="bi bi-calendar-fill-x stat-icon"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="card text-white bg-gradient-info dashboard-card initial-hidden" style="animation-delay: 0.5s;">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title display-5">
                                    <?php echo !empty($max_qso_day['qso_count']) ? htmlspecialchars($max_qso_day['qso_count']) : '0'; ?>
                                    <small class="text-white-50 d-block d-sm-inline">
                                        (<?php echo !empty($max_qso_day['qso_date']) ? htmlspecialchars($max_qso_day['qso_date']) : 'N/A'; ?>)
                                    </small>
                                </h5>
                                <p class="card-text text-white-50">Max QSOs in a Day</p>
                            </div>
                            <i class="bi bi-sun stat-icon"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="card text-white bg-gradient-primary dashboard-card initial-hidden" style="animation-delay: 0.6s;">                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title display-5">
                            <?php echo !empty($max_qso_month['qso_count']) ? htmlspecialchars($max_qso_month['qso_count']) : '0'; ?>
                            <small class="text-white-50 d-block d-sm-inline">
                                (<?php echo !empty($max_qso_month['qso_month']) ? htmlspecialchars($max_qso_month['qso_month']) : 'N/A'; ?>)
                            </small>
                        </h5>
                        <p class="card-text text-white-50">Max QSOs in a Month</p>
                    </div>
                    <i class="bi bi-calendar-month stat-icon"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-4">
        <div class="col-lg-6">
            <div class="card dashboard-card initial-hidden" style="animation-delay: 0.7s;">
                <div class="card-header bg-primary text-white fs-5 fw-bold">Recent QSOs</div>
                <ul class="list-group list-group-flush">
                    <?php if (empty($recent_qso)): ?>
                        <li class="list-group-item text-muted">No recent QSOs to display.</li>
                    <?php else: ?>
                        <?php foreach ($recent_qso as $qso): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong class="text-primary"><?php echo htmlspecialchars($qso['call']); ?></strong>
                                    <small class="text-muted ms-2 d-block d-md-inline"><?php echo htmlspecialchars($qso['qso_date']); ?> <?php echo htmlspecialchars($qso['time_on']); ?></small>
                                </div>
                                <span class="badge bg-secondary rounded-pill px-3 py-2"><?php echo htmlspecialchars($qso['band']); ?> / <?php echo htmlspecialchars($qso['mode']); ?></span>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card dashboard-card initial-hidden" style="animation-delay: 0.8s;">
                <div class="card-header bg-success text-white fs-5 fw-bold">Top 10 Callsigns</div>
                <ul class="list-group list-group-flush">
                    <?php if (empty($top_callsigns)): ?>
                        <li class="list-group-item text-muted">No QSO data available to determine top callsigns.</li>
                    <?php else: ?>
                        <?php foreach ($top_callsigns as $callsign_data): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span class="text-success"><?php echo htmlspecialchars($callsign_data['call']); ?></span>
                                <span class="badge bg-info rounded-pill px-3 py-2"><?php echo $callsign_data['count']; ?></span>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animate cards on load
    const cards = document.querySelectorAll('.dashboard-card.initial-hidden');
    cards.forEach(function(card, index) {
        setTimeout(function() {
            card.classList.remove('initial-hidden');
        }, 100 + (index * 100)); // Staggered animation
    });
});
</script>
<?php include_once ROOT_PATH . '/templates/footer.php'; ?>
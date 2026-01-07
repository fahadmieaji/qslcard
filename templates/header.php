<?php
// templates/header.php
if (session_status() === PHP_SESSION_NONE) {
    // A session function like secure_session_start() should be called here.
    // Since it's defined in utils.php, and not all pages may include it before the header,
    // we'll check its status and avoid starting it twice if already started.
    // For simplicity in this context, we just use a basic start.
    session_start();
}
if (defined('INSTALLING')) {
    $settings = ['site_name' => 'QSL Card Manager'];
} else {
    $settings = get_settings();
}
$site_name = $settings['site_name'] ?? 'QSL Card Manager';
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' . htmlspecialchars($site_name) : htmlspecialchars($site_name); ?></title>
    <link rel="icon" href="<?php echo ROOT_URL; ?>/public/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="<?php echo ROOT_URL; ?>/public/favicon.ico" type="image/x-icon">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
            <link rel="stylesheet" href="<?php echo ROOT_URL; ?>/public/css/style.css">
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

        </head><body>

<nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm mb-4">
    <div class="container">
        <a class="navbar-brand" href="<?php echo ROOT_URL; ?>/public/index.php">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-broadcast-pin d-inline-block align-text-top me-2" viewBox="0 0 16 16"><path d="M3.05 3.05a7 7 0 0 0 0 9.9.5.5 0 0 1-.707.707 8 8 0 0 1 0-11.314.5.5 0 0 1 .707.707zm2.122 2.122a4 4 0 0 0 0 5.656.5.5 0 1 1-.708.708 5 5 0 0 1 0-7.072.5.5 0 0 1 .708.708zm5.656 0a4 4 0 0 0-5.656 0 .5.5 0 1 1-.708-.708 5 5 0 0 1 7.072 0 .5.5 0 1 1-.708.708zm2.122-2.122a7 7 0 0 0-9.9 0 .5.5 0 0 1-.707-.707 8 8 0 0 1 11.314 0 .5.5 0 0 1-.707.707zM6 10.5a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0zm2.5-1a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0zm-2 2a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0zm-2-4a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0zm11-1.447a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V8.053L9 12.583V15.5a.5.5 0 0 1-1 0v-2.917L2.053 8.053a.5.5 0 0 1 0-.707L8 1.417v2.917l6-4.444V2.5a.5.5 0 0 1 .5-.5z"/></svg>
            <?php echo htmlspecialchars($site_name); ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo ROOT_URL; ?>/public/logbook.php">Logbook</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo ROOT_URL; ?>/public/log_upload.php">Upload ADIF</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo ROOT_URL; ?>/public/lotw_integration.php">Sync with LoTW</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo ROOT_URL; ?>/public/templates.php">My Templates</a>
                    </li>

                <?php endif; ?>
            </ul>
            <ul class="navbar-nav ms-auto">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarUserDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                           <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarUserDropdown">
                            <li><a class="dropdown-item" href="<?php echo ROOT_URL; ?>/public/designer.php">New Template</a></li>
                            <li><a class="dropdown-item" href="<?php echo ROOT_URL; ?>/public/profile.php">Profile</a></li>
                            
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo ROOT_URL; ?>/public/logout.php">Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo ROOT_URL; ?>/public/login.php">Login</a>
                    </li>

                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="container">
<?php /*
if (isset($_SESSION['_s21af_credit_removed']) && $_SESSION['_s21af_credit_removed']): ?>
<div class="alert alert-warning text-center" role="alert">
    <strong>Warning:</strong> The developer credit has been removed from the footer. Please restore it to remove this message.
</div>
<?php
*/ ?>
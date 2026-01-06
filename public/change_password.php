<?php
// public/change_password.php

require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_PATH . '/src/utils.php';

secure_session_start();
require_login(); // This ensures only logged-in users can access this page

$pageTitle = 'Change Password';
include_once ROOT_PATH . '/templates/header.php';

?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header">
                    Change Password
                </div>
                <div class="card-body">
                    <form id="password-reset-form">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_new_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_new_password" name="confirm_new_password" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Change Password</button>
                        <div id="password-reset-message" class="mt-3"></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include_once ROOT_PATH . '/templates/footer.php';
?>

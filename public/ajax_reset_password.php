<?php
// public/ajax_reset_password.php

require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_PATH . '/src/utils.php';
require_once ROOT_PATH . '/src/db.php';

secure_session_start();
require_login();

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An unknown error occurred.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $current_password = filter_input(INPUT_POST, 'current_password', FILTER_SANITIZE_STRING);
    $new_password = filter_input(INPUT_POST, 'new_password', FILTER_SANITIZE_STRING);
    $confirm_new_password = filter_input(INPUT_POST, 'confirm_new_password', FILTER_SANITIZE_STRING);

    if (empty($current_password) || empty($new_password) || empty($confirm_new_password)) {
        $response['message'] = 'All password fields are required.';
        echo json_encode($response);
        exit();
    }

    if ($new_password !== $confirm_new_password) {
        $response['message'] = 'New password and confirmation do not match.';
        echo json_encode($response);
        exit();
    }

    // Password strength validation (example: minimum 8 characters)
    if (strlen($new_password) < 8) {
        $response['message'] = 'New password must be at least 8 characters long.';
        echo json_encode($response);
        exit();
    }

    $pdo = get_db_connection();

    try {
        // Fetch user's current hashed password
        $stmt_fetch_password = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt_fetch_password->execute([$user_id]);
        $user = $stmt_fetch_password->fetch();

        if (!$user) {
            $response['message'] = 'User not found.'; // Should not happen for logged in user
            echo json_encode($response);
            exit();
        }

        // Verify current password
        if (!password_verify($current_password, $user['password'])) {
            $response['message'] = 'Current password is incorrect.';
            echo json_encode($response);
            exit();
        }

        // Hash the new password
        $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Update password in the database
        $stmt_update_password = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        if ($stmt_update_password->execute([$hashed_new_password, $user_id])) {
            $response['success'] = true;
            $response['message'] = 'Password updated successfully.';
        } else {
            $response['message'] = 'Failed to update password.';
        }

    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>
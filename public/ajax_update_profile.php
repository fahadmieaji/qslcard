<?php
// public/ajax_update_profile.php

require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_PATH . '/src/utils.php';
require_once ROOT_PATH . '/src/db.php';

secure_session_start();
require_login();

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An unknown error occurred.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    
    $new_username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $new_email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $mobile = filter_input(INPUT_POST, 'mobile', FILTER_SANITIZE_STRING);
    $whatsapp = filter_input(INPUT_POST, 'whatsapp', FILTER_SANITIZE_STRING);
    $facebook = filter_input(INPUT_POST, 'facebook', FILTER_SANITIZE_STRING);
    $website = filter_input(INPUT_POST, 'website', FILTER_SANITIZE_URL);
    $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);
    $country = filter_input(INPUT_POST, 'country', FILTER_SANITIZE_STRING);
    $postal_address = filter_input(INPUT_POST, 'postal_address', FILTER_SANITIZE_STRING);
    $qsl_info = filter_input(INPUT_POST, 'qsl_info', FILTER_SANITIZE_STRING);
    $qsl_manager = filter_input(INPUT_POST, 'qsl_manager', FILTER_SANITIZE_STRING);
    $grid = filter_input(INPUT_POST, 'grid', FILTER_SANITIZE_STRING);

    if (empty($new_username)) {
        $response['message'] = 'Username cannot be empty.';
        echo json_encode($response);
        exit();
    }
    
    // Email is optional, but if provided, it must be valid.
    if (!empty($new_email) && !filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Invalid email format.';
        echo json_encode($response);
        exit();
    }

    $pdo = get_db_connection();

    try {
        // Fetch current user details to compare
        $stmt_current = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
        $stmt_current->execute([$user_id]);
        $current_user = $stmt_current->fetch();


        // Check for duplicate username if changed
        if ($new_username !== $current_user['username']) {
            $stmt_username_check = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt_username_check->execute([$new_username, $user_id]);
            if ($stmt_username_check->fetch()) {
                $response['message'] = 'This username is already taken.';
                echo json_encode($response);
                exit();
            }
        }

        // Check for duplicate email if changed and not empty
        if (!empty($new_email) && $new_email !== $current_user['email']) {
            $stmt_email_check = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt_email_check->execute([$new_email, $user_id]);
            if ($stmt_email_check->fetch()) {
                $response['message'] = 'This email is already registered.';
                echo json_encode($response);
                exit();
            }
        }

        // Update user profile
        $sql = "UPDATE users SET username = ?, email = ?, name = ?, mobile = ?, whatsapp = ?, facebook = ?, website = ?, address = ?, country = ?, postal_address = ?, qsl_info = ?, qsl_manager = ?, grid = ? WHERE id = ?";
        $params = [$new_username, $new_email, $name, $mobile, $whatsapp, $facebook, $website, $address, $country, $postal_address, $qsl_info, $qsl_manager, $grid, $user_id];
        
        $stmt_update = $pdo->prepare($sql);
        $execution_result = $stmt_update->execute($params);
        

        if ($execution_result) {
            // Update session username if it changed
            $_SESSION['username'] = $new_username;
            $response['success'] = true;
            $response['message'] = 'Profile updated successfully!';

        } else {
            $error_info = $stmt_update->errorInfo();
            $response['message'] = 'Failed to update profile. Error: ' . implode(" ", $error_info);
        }

    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>

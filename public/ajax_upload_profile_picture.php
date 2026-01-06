<?php
// public/ajax_upload_profile_picture.php

require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_PATH . '/src/utils.php';
require_once ROOT_PATH . '/src/db.php';

secure_session_start();
require_login();

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An unknown error occurred.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];

    if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
        $response['message'] = 'No file uploaded or an upload error occurred.';
        echo json_encode($response);
        exit();
    }

    $file = $_FILES['profile_picture'];
    $file_name = $file['name'];
    $file_tmp_name = $file['tmp_name'];
    $file_size = $file['size'];
    $file_type = $file['type'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    $max_file_size = 5 * 1024 * 1024; // 5MB

    if (!in_array($file_ext, $allowed_extensions)) {
        $response['message'] = 'Invalid file type. Only JPG, JPEG, PNG, GIF are allowed.';
        echo json_encode($response);
        exit();
    }

    if ($file_size > $max_file_size) {
        $response['message'] = 'File size exceeds 5MB limit.';
        echo json_encode($response);
        exit();
    }

    $upload_dir = 'uploads/profile_pictures/';
    // Ensure the directory exists (should have been created by previous step)
    if (!is_dir(ROOT_PATH . '/' . $upload_dir)) {
        mkdir(ROOT_PATH . '/' . $upload_dir, 0755, true);
    }

    $new_file_name = uniqid('profile_', true) . '.' . $file_ext;
    $file_destination = ROOT_PATH . '/' . $upload_dir . $new_file_name;
    $public_url = $upload_dir . $new_file_name;

    if (move_uploaded_file($file_tmp_name, $file_destination)) {
        $pdo = get_db_connection();
        
        try {
            // Fetch old profile picture URL to delete it
            $stmt_old_pic = $pdo->prepare("SELECT profile_picture_url FROM users WHERE id = ?");
            $stmt_old_pic->execute([$user_id]);
            $old_profile_picture_url = $stmt_old_pic->fetchColumn();

            // Update user's profile picture URL in the database
            $stmt_update = $pdo->prepare("UPDATE users SET profile_picture_url = ? WHERE id = ?");
            if ($stmt_update->execute([$public_url, $user_id])) {
                // Delete old profile picture file if it exists and is not the default placeholder
                if ($old_profile_picture_url && file_exists(ROOT_PATH . '/' . $old_profile_picture_url)) {
                    // Basic check to ensure we don't delete critical system files
                    if (strpos($old_profile_picture_url, 'uploads/profile_pictures/') === 0) {
                        unlink(ROOT_PATH . '/' . $old_profile_picture_url);
                    }
                }

                $response['success'] = true;
                $response['message'] = 'Profile picture uploaded successfully.';
                $response['file_url'] = ROOT_URL . '/' . $public_url;
            } else {
                // If DB update fails, delete the newly uploaded file
                unlink($file_destination);
                $response['message'] = 'Failed to update profile picture in database.';
            }
        } catch (PDOException $e) {
            unlink($file_destination); // Delete uploaded file on DB error
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'Failed to move uploaded file.';
    }

} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>
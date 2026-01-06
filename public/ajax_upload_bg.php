<?php
// public/ajax_upload_bg.php

require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_PATH . '/src/utils.php';

secure_session_start();
// We must have a logged-in user to do this
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit();
}

header('Content-Type: application/json');

if (isset($_FILES['background_image']) && $_FILES['background_image']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['background_image'];
    $file_name = $file['name'];
    $file_tmp_path = $file['tmp_name'];
    $file_size = $file['size'];
    $file_error = $file['error'];

    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

    if (in_array($file_ext, $allowed_ext)) {
        if ($file_error === 0) {
            if ($file_size < 5 * 1024 * 1024) { // 5MB limit
                $user_upload_dir = ROOT_PATH . '/public/uploads/user_' . $_SESSION['user_id'];
                if (!is_dir($user_upload_dir)) {
                    mkdir($user_upload_dir, 0755, true);
                }

                $new_file_name = uniqid('', true) . '.' . $file_ext;
                $destination = $user_upload_dir . '/' . $new_file_name;
                
                if (move_uploaded_file($file_tmp_path, $destination)) {
                    $file_url = ROOT_URL . '/public/uploads/user_' . $_SESSION['user_id'] . '/' . $new_file_name;
                    echo json_encode(['success' => true, 'url' => $file_url]);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file.']);
                }
            } else {
                http_response_code(413); // Payload Too Large
                echo json_encode(['success' => false, 'message' => 'File is too large. Maximum size is 5MB.']);
            }
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'An error occurred during upload.']);
        }
    } else {
        http_response_code(415); // Unsupported Media Type
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Allowed types: jpg, jpeg, png, gif.']);
    }
} else {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'No file uploaded or an upload error occurred.']);
}

exit();
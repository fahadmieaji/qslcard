<?php
// public/ajax_get_user_profile.php

require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_PATH . '/src/utils.php';
require_once ROOT_PATH . '/src/db.php';

header('Content-Type: application/json');

secure_session_start();
if (!isset($_SESSION['user_id'])) { // Changed from !is_logged_in() to direct session check
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$pdo = get_db_connection();

try {
    $stmt = $pdo->prepare(
        'SELECT callsign, email, mobile, whatsapp, facebook, website, address, country, postal_address, qsl_info, qsl_manager, grid, profile_picture_url
         FROM users
         WHERE id = ?'
    );
    $stmt->execute([$user_id]);
    $user_profile = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user_profile) {
        // Handle potential null values for profile_picture_url
        if (!empty($user_profile['profile_picture_url'])) {
            $user_profile['profile_picture_full_url'] = ROOT_URL . '/' . $user_profile['profile_picture_url'];
        } else {
            $user_profile['profile_picture_full_url'] = ''; // No default profile picture
        }

        echo json_encode(['success' => true, 'data' => $user_profile]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User profile not found.']);
    }
} catch (PDOException $e) {
    error_log("Database error fetching user profile: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
?>
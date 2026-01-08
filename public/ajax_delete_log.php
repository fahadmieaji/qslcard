<?php
// public/ajax_delete_log.php

require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_PATH . '/src/utils.php';
require_once ROOT_PATH . '/src/db.php';

secure_session_start();
require_login();

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An unknown error occurred.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $log_id = filter_input(INPUT_POST, 'log_id', FILTER_VALIDATE_INT);

    if ($log_id) {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare('DELETE FROM logs WHERE id = ? AND user_id = ?');
        $stmt->execute([$log_id, $_SESSION['user_id']]);

        if ($stmt->rowCount() > 0) {
            $response['success'] = true;
            $response['message'] = 'Log entry deleted successfully.';
        } else {
            $response['message'] = 'Could not delete log entry. It may not exist or you may not have permission.';
        }
    } else {
        $response['message'] = 'Invalid log ID.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>

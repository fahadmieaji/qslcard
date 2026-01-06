<?php
// public/ajax_get_templates.php

require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_PATH . '/src/utils.php';
require_once ROOT_PATH . '/src/db.php';

secure_session_start();
// Authentication check
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit();
}

header('Content-Type: application/json');

try {
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('SELECT id, template_name FROM qsl_templates WHERE user_id = ? ORDER BY template_name ASC');
    $stmt->execute([$_SESSION['user_id']]);
    $templates = $stmt->fetchAll();

    echo json_encode(['success' => true, 'templates' => $templates]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}

exit();

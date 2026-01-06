<?php
// public/ajax_public_search.php

require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_PATH . '/src/utils.php';
require_once ROOT_PATH . '/src/db.php';

header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => '',
    'results' => [],
    'has_public_template' => false,
];

$search_callsign = filter_input(INPUT_GET, 'callsign', FILTER_SANITIZE_STRING);

if (!empty($search_callsign)) {
    $pdo = get_db_connection();
    try {
        // Search for logs matching the callsign
        $stmt = $pdo->prepare("SELECT l.*, u.username FROM logs l JOIN users u ON l.user_id = u.id WHERE l.call = ? ORDER BY l.qso_date DESC, l.time_on DESC");
        $stmt->execute([$search_callsign]);
        $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($search_results)) {
            $response['message'] = "No QSO records found for callsign " . htmlspecialchars($search_callsign) . ".";
        } else {
            $response['success'] = true;
            $response['results'] = $search_results;

            // Check if the user has a public template
            $user_id = $search_results[0]['user_id'];
            $stmt = $pdo->prepare("SELECT id FROM qsl_templates WHERE user_id = ? AND is_public = 1");
            $stmt->execute([$user_id]);
            if ($stmt->fetch()) {
                $response['has_public_template'] = true;
            }
        }
    } catch (PDOException $e) {
        $response['message'] = "Database error: " . $e->getMessage();
    }
} else {
    $response['message'] = "Please enter a callsign to search.";
}

echo json_encode($response);

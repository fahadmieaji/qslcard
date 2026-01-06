<?php
// public/ajax_save_template.php

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $template_name = $_POST['template_name'] ?? '';
    $background_image_url = $_POST['background_image'] ?? '';
    $fields_json = $_POST['fields'] ?? '';

    // Validation
    if (empty($template_name)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Template Name is required.']);
        exit();
    }
    if (empty($background_image_url)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'A background image must be set.']);
        exit();
    }
    if (empty($fields_json)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'The template must contain at least one field.']);
        exit();
    }

    try {
        $pdo = get_db_connection();
        $template_id = $_POST['template_id'] ?? null;

        if (!empty($template_id)) {
            // Update existing template
            $stmt = $pdo->prepare(
                'UPDATE qsl_templates SET 
                    template_name = :template_name, 
                    background_image = :background_image, 
                    fields = :fields 
                 WHERE id = :id AND user_id = :user_id'
            );
            $stmt->execute([
                ':template_name' => $template_name,
                ':background_image' => $background_image_url,
                ':fields' => $fields_json,
                ':id' => $template_id,
                ':user_id' => $_SESSION['user_id']
            ]);
            $message = 'Template updated successfully!';
        } else {
            // Insert new template
            $stmt = $pdo->prepare(
                'INSERT INTO qsl_templates (user_id, template_name, background_image, fields) 
                 VALUES (:user_id, :template_name, :background_image, :fields)'
            );
            $stmt->execute([
                ':user_id' => $_SESSION['user_id'],
                ':template_name' => $template_name,
                ':background_image' => $background_image_url,
                ':fields' => $fields_json,
            ]);
            $message = 'Template saved successfully!';
        }

        echo json_encode(['success' => true, 'message' => $message]);

    } catch (PDOException $e) {
        http_response_code(500);
        // In production, you'd log this error instead of echoing it.
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

exit();
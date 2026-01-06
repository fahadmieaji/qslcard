<?php
// public/generate_public_qsl.php

require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_PATH . '/src/db.php';
require_once ROOT_PATH . '/src/utils.php'; // For session if needed later, and other utilities

// Public access, no login required.
// We expect log_ids to be passed, typically from public_search.php

$log_ids = [];
if (isset($_GET['log_id'])) {
    $log_ids = is_array($_GET['log_id']) ? $_GET['log_id'] : [$_GET['log_id']];
} elseif (isset($_POST['log_ids'])) { // For multiple selections via POST
    $log_ids = is_array($_POST['log_ids']) ? $_POST['log_ids'] : json_decode($_POST['log_ids'], true);
}

// Basic validation for log_ids
$log_ids = array_filter($log_ids, 'is_numeric');

if (empty($log_ids)) {
    die('No QSO IDs provided for QSL generation.');
}

$pdo = get_db_connection();

// --- Common Image Generation Function (adapted from generate.php) ---
function generateQSLImage($log_data, $template_data, $pdo_conn) {
    $font_path = ROOT_PATH . '/public/fonts/Roboto-Regular.ttf';
    if (!file_exists($font_path)) {
        // Fallback for missing font in image generation
        $img = imagecreatetruecolor(825, 525);
        $bg = imagecolorallocate($img, 255, 255, 255);
        $textColor = imagecolorallocate($img, 200, 0, 0);
        imagefill($img, 0, 0, $bg);
        imagestring($img, 5, 50, 250, 'ERROR: Font file not found!', $textColor);
        imagestring($img, 5, 50, 270, 'Please place Roboto-Regular.ttf in /public/fonts/', $textColor);
        ob_start();
        imagepng($img);
        imagedestroy($img);
        return ob_get_clean();
    }
    
    // Load background image and get its dimensions
    $bg_image_path = ROOT_PATH . str_replace(ROOT_URL, '', $template_data['background_image']);
    if (!file_exists($bg_image_path)) {
        // Fallback for missing background image
        $img = imagecreatetruecolor(825, 525);
        $bg = imagecolorallocate($img, 255, 255, 255);
        $textColor = imagecolorallocate($img, 200, 0, 0);
        imagefill($img, 0, 0, $bg);
        imagestring($img, 5, 50, 250, 'ERROR: Background image not found!', $textColor);
        imagestring($img, 5, 50, 270, 'Path: ' . $bg_image_path, $textColor);
        ob_start();
        imagepng($img);
        imagedestroy($img);
        return ob_get_clean();
    }

    list($width, $height, $image_type) = getimagesize($bg_image_path);
    if (!$width || !$height) {
        // Fallback for invalid image
        $img = imagecreatetruecolor(825, 525);
        $bg = imagecolorallocate($img, 255, 255, 255);
        $textColor = imagecolorallocate($img, 200, 0, 0);
        imagefill($img, 0, 0, $bg);
        imagestring($img, 5, 50, 250, 'ERROR: Could not get image dimensions.', $textColor);
        ob_start();
        imagepng($img);
        imagedestroy($img);
        return ob_get_clean();
    }

    $image = null;
    switch ($image_type) {
        case IMAGETYPE_JPEG: $image = imagecreatefromjpeg($bg_image_path); break;
        case IMAGETYPE_PNG: $image = imagecreatefrompng($bg_image_path); break;
        case IMAGETYPE_GIF: $image = imagecreatefromgif($bg_image_path); break;
        default:
            // Fallback for unsupported image type
            $img = imagecreatetruecolor(825, 525);
            $bg = imagecolorallocate($img, 255, 255, 255);
            $textColor = imagecolorallocate($img, 200, 0, 0);
            imagefill($img, 0, 0, $bg);
            imagestring($img, 5, 50, 250, 'ERROR: Unsupported image type.', $textColor);
            ob_start();
            imagepng($img);
            imagedestroy($img);
            return ob_get_clean();
    }
    if ($image === false) {
        // Fallback for image creation failure
        $img = imagecreatetruecolor(825, 525);
        $bg = imagecolorallocate($img, 255, 255, 255);
        $textColor = imagecolorallocate($img, 200, 0, 0);
        imagefill($img, 0, 0, $bg);
        imagestring($img, 5, 50, 250, 'ERROR: Failed to create image from file.', $textColor);
        ob_start();
        imagepng($img);
        imagedestroy($img);
        return ob_get_clean();
    }
    
    $fields = json_decode($template_data['fields'], true);

    foreach ($fields as $field) {
        $field_key = strtolower($field['qsoField']);
        $text = $log_data[$field_key] ?? $field['text'];
        
        $left = (float)($field['left'] ?? 0) * $width;
        $top = (float)($field['top'] ?? 0) * $height;
        $angle = (float)($field['angle'] ?? 0);
        $fontSizePx = (float)($field['fontSize'] ?? 0.05) * $height;

        list($r, $g, $b) = sscanf($field['fill'], "#%02x%02x%02x");
        $color = imagecolorallocate($image, $r, $g, $b);

        $font_size_pt = $fontSizePx * 0.75;
        
        $bbox = imagettfbbox($font_size_pt, -$angle, $font_path, $text);
        $y_offset = abs($bbox[7]);
        
        $final_y = $top + $y_offset;

        imagettftext($image, $font_size_pt, -$angle, $left, $final_y, $color, $font_path, $text);
    }
    
    ob_start();
    imagejpeg($image, null, 90);
    imagedestroy($image);
    return ob_get_clean();
}

$qsl_images_base64 = [];
$error_messages = [];

foreach ($log_ids as $log_id) {
    // Fetch log data for the current log_id
    $log_stmt = $pdo->prepare('SELECT * FROM logs WHERE id = ?');
    $log_stmt->execute([$log_id]);
    $log = $log_stmt->fetch(PDO::FETCH_ASSOC);

    if ($log) {
        // Fetch the public template for the user who owns the log
        $template_stmt = $pdo->prepare('SELECT * FROM qsl_templates WHERE user_id = ? AND is_public = 1');
        $template_stmt->execute([$log['user_id']]);
        $template = $template_stmt->fetch();

        if ($template) {
            $generated_image_blob = generateQSLImage($log, $template, $pdo);
            $qsl_images_base64[$log_id] = base64_encode($generated_image_blob);
        } else {
            $error_messages[] = "No public QSL template found for the owner of QSO ID " . htmlspecialchars($log_id) . ".";
        }
    } else {
        $error_messages[] = "QSO ID " . htmlspecialchars($log_id) . " not found.";
    }
}

// If only one image, output it directly. Otherwise, render an HTML page with images.
if (count($qsl_images_base64) === 1 && empty($error_messages)) {
    $image_data = base64_decode(reset($qsl_images_base64));
    if (isset($_GET['download']) && $_GET['download'] == 'true') {
        header('Content-Disposition: attachment; filename="QSL_' . key($qsl_images_base64) . '.jpg"');
    }
    header('Content-Type: image/jpeg');
    echo $image_data;
    exit();
} else {
    // Render an HTML page to display multiple images or errors
    $pageTitle = 'Generated QSL Cards';
    include_once ROOT_PATH . '/templates/header.php'; // Use header for consistent styling
    ?>
    <div class="container mt-4">
        <h1 class="mb-4">Generated QSL Cards</h1>

        <?php if (!empty($error_messages)): ?>
            <div class="alert alert-danger" role="alert">
                <h4 class="alert-heading">Errors during QSL Generation:</h4>
                <ul>
                    <?php foreach ($error_messages as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (empty($qsl_images_base64)): ?>
            <div class="alert alert-warning" role="alert">
                No QSL cards could be generated.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($qsl_images_base64 as $log_id => $base64_image): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header">
                                QSO ID: <?php echo htmlspecialchars($log_id); ?>
                            </div>
                            <div class="card-body text-center">
                                <img src="data:image/jpeg;base64,<?php echo $base64_image; ?>" class="img-fluid border mb-2" alt="QSL Card for QSO ID <?php echo htmlspecialchars($log_id); ?>">
                                <a href="data:image/jpeg;base64,<?php echo $base64_image; ?>" download="QSL_<?php echo htmlspecialchars($log_id); ?>.jpg" class="btn btn-sm btn-success">Download</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
    include_once ROOT_PATH . '/templates/footer.php'; // Use footer for consistent styling
}
?>
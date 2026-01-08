<?php
// public/generate.php

require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_PATH . '/src/utils.php';
require_once ROOT_PATH . '/src/db.php';

if (!extension_loaded('gd')) {
    die('The GD library is required for image generation but is not installed or enabled.');
}

secure_session_start();
require_login();

// --- Validation and Data Fetching ---
$log_id = $_GET['log_id'] ?? null;
$template_id = $_GET['template_id'] ?? null;
$output_type = $_GET['output'] ?? 'page'; // 'page' or 'image'

if (!$log_id || !$template_id) {
    die('Log ID and Template ID are required.');
}

$pdo = get_db_connection();
$user_id = $_SESSION['user_id'];

// Fetch log data, ensuring it belongs to the user
$log_stmt = $pdo->prepare('SELECT * FROM logs WHERE id = ? AND user_id = ?');
$log_stmt->execute([$log_id, $user_id]);
$log = $log_stmt->fetch();

// Fetch template data, ensuring it belongs to the user
$template_stmt = $pdo->prepare('SELECT * FROM qsl_templates WHERE id = ? AND user_id = ?');
$template_stmt->execute([$template_id, $user_id]);
$template = $template_stmt->fetch();

if (!$log || !$template) {
    die('Invalid log or template ID, or you do not have permission to access it.');
}

// --- Image Generation Logic ---
if ($output_type === 'image') {
    $font_path = ROOT_PATH . '/public/fonts/Roboto-Regular.ttf';
    if (!file_exists($font_path)) {
        header('Content-Type: image/png');
        $img = imagecreatetruecolor(825, 525);
        $bg = imagecolorallocate($img, 255, 255, 255);
        $textColor = imagecolorallocate($img, 200, 0, 0);
        imagefill($img, 0, 0, $bg);
        imagestring($img, 5, 50, 250, 'ERROR: Font file not found!', $textColor);
        imagestring($img, 5, 50, 270, 'Please place Roboto-Regular.ttf in /public/fonts/', $textColor);
        imagepng($img);
        imagedestroy($img);
        exit();
    }
    
    // Load background image and get its dimensions
    $bg_image_path = ROOT_PATH . str_replace(ROOT_URL, '', $template['background_image']);
    list($width, $height, $image_type) = getimagesize($bg_image_path);
    if (!$width || !$height) { die('Could not get image dimensions.'); }

    // Create image resource from file
    $image = null;
    switch ($image_type) {
        case IMAGETYPE_JPEG: $image = imagecreatefromjpeg($bg_image_path); break;
        case IMAGETYPE_PNG: $image = imagecreatefrompng($bg_image_path); break;
        case IMAGETYPE_GIF: $image = imagecreatefromgif($bg_image_path); break;
        default: die('Unsupported image type.');
    }
    if ($image === false) { die('Failed to create image from file.'); }
    
    // Process and draw each text field using relative coordinates
    $fields = json_decode($template['fields'], true);

    foreach ($fields as $field) {
        // Look up the log data using a case-insensitive key
        $field_key = strtolower($field['qsoField']);
        $text = $log[$field_key] ?? $field['text'];
        
        // --- Calculate absolute values from relative ---
        $left = (float)($field['left'] ?? 0) * $width;
        $top = (float)($field['top'] ?? 0) * $height;
        $angle = (float)($field['angle'] ?? 0);
        // Font size is relative to the image height
        $fontSizePx = (float)($field['fontSize'] ?? 0.05) * $height;

        // Convert hex color to RGB
        list($r, $g, $b) = sscanf($field['fill'], "#%02x%02x%02x");
        $color = imagecolorallocate($image, $r, $g, $b);

        $font_file = 'Roboto-Regular.ttf';
        if (!empty($field['fontWeight']) && $field['fontWeight'] === 'bold' && !empty($field['fontStyle']) && $field['fontStyle'] === 'italic') {
            $font_file = 'Roboto-BoldItalic.ttf';
        } elseif (!empty($field['fontWeight']) && $field['fontWeight'] === 'bold') {
            $font_file = 'Roboto-Bold.ttf';
        } elseif (!empty($field['fontStyle']) && $field['fontStyle'] === 'italic') {
            $font_file = 'Roboto-Italic.ttf';
        }
        $font_path = ROOT_PATH . '/public/fonts/' . $font_file;

        if (!file_exists($font_path)) {
            // Fallback to regular if the specific font is not found
            $font_path = ROOT_PATH . '/public/fonts/Roboto-Regular.ttf';
        }

        // Convert calculated pixel font size to GD points
        $font_size_pt = $fontSizePx * 0.75;
        
        // Get bounding box to calculate baseline offset
        $bbox = imagettfbbox($font_size_pt, -$angle, $font_path, $text);
        $y_offset = abs($bbox[7]);
        
        $final_y = $top + $y_offset;

        // Add the text to the image with the accurately calculated absolute position
        imagettftext($image, $font_size_pt, -$angle, $left, $final_y, $color, $font_path, $text);
    }
    
    // Output the final image
    header('Content-Type: image/jpeg');
    imagejpeg($image, null, 90);
    imagedestroy($image);
    exit();
}

// --- HTML Page View ---
$pageTitle = 'Generate QSL Card';
include_once ROOT_PATH . '/templates/header.php';
$image_url = "generate.php?log_id=$log_id&template_id=$template_id&output=image&t=" . time();
?>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">QSL Card Preview</h1>
        <div>
            <button class="btn btn-secondary print-qsl-btn" data-image-url="<?php echo $image_url; ?>">Print</button>
            <button class="btn btn-primary" disabled>Send as Email</button>
        </div>
    </div>
    
    <div class="card shadow-sm">
        <div class="card-body text-center">
            <p><strong>To:</strong> <?php echo htmlspecialchars($log['call']); ?></p>
            <p><strong>Date:</strong> <?php echo htmlspecialchars($log['qso_date']); ?> | <strong>Time:</strong> <?php echo htmlspecialchars($log['time_on']); ?> UTC</p>
            <hr>
            <img src="<?php echo $image_url; ?>" alt="Generated QSL Card" class="img-fluid border">
        </div>
    </div>
</div>
<?php include_once ROOT_PATH . '/templates/footer.php'; ?>
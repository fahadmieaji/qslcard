<?php
// public/designer.php

require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_PATH . '/src/utils.php';
require_once ROOT_PATH . '/src/db.php';

secure_session_start();
require_login();

$is_edit_mode = false;
$template_data_json = 'null';
$template_id = null;

if (isset($_GET['edit_id'])) {
    $is_edit_mode = true;
    $template_id = $_GET['edit_id'];
    $pdo = get_db_connection();

    $stmt = $pdo->prepare('SELECT * FROM qsl_templates WHERE id = ? AND user_id = ?');
    $stmt->execute([$template_id, $_SESSION['user_id']]);
    $template = $stmt->fetch();

    if ($template) {
        $template_data_json = json_encode($template);
    } else {
        // Template not found or doesn't belong to user, redirect or show error
        header('Location: templates.php');
        exit();
    }
}

$pageTitle = $is_edit_mode ? 'Edit QSL Card Template' : 'Create QSL Card Template';
include_once ROOT_PATH . '/templates/header.php';
?>

<script>
    // Pass PHP data to JavaScript
    const templateToLoad = <?php echo $template_data_json; ?>;
</script>
<script>
    const ROOT_URL = "<?php echo ROOT_URL; ?>";
</script>

<div class="container-fluid">
    <div class="row">
        <!-- Left Control Panel -->
        <div class="col-lg-3">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Template Controls</h5>
                </div>
                <div class="card-body">
                    <input type="hidden" id="template-id" value="<?php echo htmlspecialchars($template_id ?? ''); ?>">
                    <div class="mb-3">
                        <label for="template-name" class="form-label">Template Name</label>
                        <input type="text" id="template-name" class="form-control" placeholder="My Awesome Template">
                    </div>
                    <button id="save-template" class="btn btn-primary w-100">
                        <?php echo $is_edit_mode ? 'Update Template' : 'Save Template'; ?>
                    </button>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-header">
                    <h5 class="mb-0">Background</h5>
                </div>
                <div class="card-body">
                    <label for="background-upload" class="form-label">Upload Background Image</label>
                    <input type="file" id="background-upload" class="form-control" accept="image/*">
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-header">
                    <h5 class="mb-0">Add QSO Fields</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-secondary add-text" data-text="CALL">Add Callsign</button>
                        <button class="btn btn-secondary add-text" data-text="QSO_DATE">Add Date</button>
                        <button class="btn btn-secondary add-text" data-text="TIME_ON">Add Time</button>
                        <button class="btn btn-secondary add-text" data-text="BAND">Add Band</button>
                        <button class="btn btn-secondary add-text" data-text="FREQ">Add Freq</button>
                        <button class="btn btn-secondary add-text" data-text="MODE">Add Mode</button>
                        <button class="btn btn-secondary add-text" data-text="RST_SENT">Add RST (S)</button>
                        <button class="btn btn-secondary add-text" data-text="RST_RCVD">Add RST (R)</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Canvas Area and Edit Text Controls -->
        <div class="col-lg-6">
            <div class="card shadow-sm mb-3"> <!-- Added mb-3 here for spacing from controls below -->
                <div class="card-body">
                    <p>Upload a background image to begin. The canvas will resize to match your image dimensions.</p>
                    <div class="canvas-wrapper">
                        <canvas id="qsl-canvas"></canvas>
                    </div>
                </div>
            </div>

            <!-- Edit Text Controls (moved here) -->
            <div class="card shadow-sm mb-3" id="text-controls">
                <div class="card-header">
                    <h5 class="mb-0">Edit Text</h5>
                </div>
                                <div class="card-body">
                                    <div class="row g-2"> <!-- g-2 for gutter spacing -->
                                        <div class="col-md-4">
                                            <label for="font-size" class="form-label">Font Size</label>
                                            <input type="number" id="font-size" class="form-control form-control-sm" value="20" min="1">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="font-color" class="form-label">Color</label>
                                            <input type="color" id="font-color" class="form-control form-control-sm form-control-color" value="#000000">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Style</label>
                                            <div class="btn-group w-100">
                                                <button type="button" class="btn btn-outline-secondary btn-sm" id="font-bold"><b>B</b></button>
                                                <button type="button" class="btn btn-outline-secondary btn-sm" id="font-italic"><i>I</i></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
            </div>
        </div>

        <!-- Right Control Panel for User Fields -->
        <div class="col-lg-3">
            <div class="card shadow-sm mb-3">
                <div class="card-header">
                    <h5 class="mb-0">Add User Fields</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-secondary add-user-text" data-field="CALLSIGN">Add Callsign</button>
                        <button class="btn btn-secondary add-user-text" data-field="ADDRESS">Add Address</button>
                        <button class="btn btn-secondary add-user-text" data-field="GRID">Add Grid</button>
                        <button class="btn btn-secondary add-user-text" data-field="EMAIL">Add Email</button>
                        <button class="btn btn-secondary add-user-text" data-field="MOBILE">Add Mobile</button>
                        <button class="btn btn-secondary add-user-text" data-field="WHATSAPP">Add WhatsApp</button>
                        <button class="btn btn-secondary add-user-text" data-field="FACEBOOK">Add Facebook</button>
                        <button class="btn btn-secondary add-user-text" data-field="WEBSITE">Add Website</button>
                        <button class="btn btn-secondary add-user-text" data-field="COUNTRY">Add Country</button>
                        <button class="btn btn-secondary add-user-text" data-field="QSL_INFO">Add QSL Info</button>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include_once ROOT_PATH . '/templates/footer.php';
?>
<!-- Add the designer-specific JS file -->
<script src="<?php echo ROOT_URL; ?>/public/js/designer.js"></script>
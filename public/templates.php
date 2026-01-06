<?php
// public/templates.php

require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_PATH . '/src/utils.php';
require_once ROOT_PATH . '/src/db.php';

secure_session_start();
require_login();

$pageTitle = 'My QSL Templates';
$pdo = get_db_connection();

$success_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['set_public_template_id'])) {
        $public_template_id = $_POST['set_public_template_id'];
        $user_id = $_SESSION['user_id'];

        // First, set all templates for this user to not be public
        $stmt_reset = $pdo->prepare('UPDATE qsl_templates SET is_public = 0 WHERE user_id = ?');
        $stmt_reset->execute([$user_id]);

        // Then, set the selected template to be public
        $stmt_set = $pdo->prepare('UPDATE qsl_templates SET is_public = 1 WHERE id = ? AND user_id = ?');
        $stmt_set->execute([$public_template_id, $user_id]);
        $success_message = 'Public template setting saved successfully!';
    }

    if (isset($_POST['delete_template_id'])) {
        $template_id_to_delete = $_POST['delete_template_id'];
        // Ensure the template belongs to the user before deleting
        $stmt = $pdo->prepare('DELETE FROM qsl_templates WHERE id = ? AND user_id = ?');
        $stmt->execute([$template_id_to_delete, $_SESSION['user_id']]);
        $success_message = 'Template deleted successfully!';
    }
}

// Fetch all templates for the current user
$stmt = $pdo->prepare('SELECT id, template_name, background_image, is_public FROM qsl_templates WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$_SESSION['user_id']]);
$templates = $stmt->fetchAll();

include_once ROOT_PATH . '/templates/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">My QSL Templates</h1>
    <a href="designer.php" class="btn btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-plus-circle-fill me-1" viewBox="0 0 16 16">
          <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8.5 4.5a.5.5 0 0 0-1 0v3h-3a.5.5 0 0 0 0 1h3v3a.5.5 0 0 0 1 0v-3h3a.5.5 0 0 0 0-1h-3v-3z"/>
        </svg>
        Create New Template
    </a>
</div>

<?php if (!empty($success_message)): ?>
<div class="alert alert-success">
    <?php echo $success_message; ?>
</div>
<?php endif; ?>

<form action="templates.php" method="POST">
    <div class="row">
        <?php if (empty($templates)): ?>
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <p class="mb-0">You haven't created any templates yet.</p>
                    <a href="designer.php" class="alert-link">Create your first QSL card template!</a>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($templates as $template): ?>
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm h-100">
                        <img src="<?php echo htmlspecialchars($template['background_image']); ?>" class="card-img-top" alt="Template background" style="height: 150px; object-fit: cover;">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($template['template_name']); ?></h5>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="set_public_template_id" id="public_template_<?php echo $template['id']; ?>" value="<?php echo $template['id']; ?>" <?php echo $template['is_public'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="public_template_<?php echo $template['id']; ?>">
                                    Use as Public Template
                                </label>
                            </div>
                        </div>
                        <div class="card-footer text-end">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton-<?php echo $template['id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                    Actions
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton-<?php echo $template['id']; ?>">
                                    <li>
                                        <a class="dropdown-item" href="designer.php?edit_id=<?php echo $template['id']; ?>">Edit</a>
                                    </li>
                                    <li>
                                        <button type="submit" name="delete_template_id" value="<?php echo $template['id']; ?>" class="dropdown-item text-danger" onclick="return confirm('Are you sure you want to delete this template?');">Delete</button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php if (!empty($templates)): ?>
    <div class="row">
        <div class="col-12">
            <button type="submit" class="btn btn-primary">Save Public Template Setting</button>
        </div>
    </div>
    <?php endif; ?>
</form>

<?php include_once ROOT_PATH . '/templates/footer.php'; ?>
<?php
// public/profile.php

require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_PATH . '/src/utils.php';
require_once ROOT_PATH . '/src/db.php'; // Include the database connection

secure_session_start();
require_login(); // This ensures only logged-in users can access this page

$pdo = get_db_connection();
$user_id = $_SESSION['user_id'];

// Fetch current user details for profile update form
$stmt_user_details = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt_user_details->execute([$user_id]);
$user_details = $stmt_user_details->fetch(PDO::FETCH_ASSOC);

$current_username = htmlspecialchars($user_details['username']);
$current_email = htmlspecialchars($user_details['email']);
$current_profile_picture = htmlspecialchars($user_details['profile_picture_url'] ?? '');
$current_name = htmlspecialchars($user_details['name'] ?? '');
$current_mobile = htmlspecialchars($user_details['mobile'] ?? '');
$current_whatsapp = htmlspecialchars($user_details['whatsapp'] ?? '');
$current_facebook = htmlspecialchars($user_details['facebook'] ?? '');
$current_website = htmlspecialchars($user_details['website'] ?? '');
$current_address = htmlspecialchars($user_details['address'] ?? '');
$current_country = htmlspecialchars($user_details['country'] ?? '');
$current_postal_address = htmlspecialchars($user_details['postal_address'] ?? '');
$current_qsl_info = htmlspecialchars($user_details['qsl_info'] ?? '');
$current_qsl_manager = htmlspecialchars($user_details['qsl_manager'] ?? '');
$current_grid = htmlspecialchars($user_details['grid'] ?? '');

$pageTitle = 'Profile';
include_once ROOT_PATH . '/templates/header.php';

?>

<div class="container mt-4">
    <h2 class="mt-5 mb-3">Profile Settings</h2>
    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    Update Profile Information
                </div>
                <div class="card-body">
                    <form id="profile-update-form">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" value="<?php echo $current_username; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo $current_email; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo $current_name; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="mobile" class="form-label">Mobile</label>
                            <input type="text" class="form-control" id="mobile" name="mobile" value="<?php echo $current_mobile; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="whatsapp" class="form-label">Whatsapp</label>
                            <input type="text" class="form-control" id="whatsapp" name="whatsapp" value="<?php echo $current_whatsapp; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="facebook" class="form-label">Facebook</label>
                            <input type="text" class="form-control" id="facebook" name="facebook" value="<?php echo $current_facebook; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="website" class="form-label">Website</label>
                            <input type="text" class="form-control" id="website" name="website" value="<?php echo $current_website; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address"><?php echo $current_address; ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="country" class="form-label">Country</label>
                            <input type="text" class="form-control" id="country" name="country" value="<?php echo $current_country; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="postal_address" class="form-label">Postal Address</label>
                            <textarea class="form-control" id="postal_address" name="postal_address"><?php echo $current_postal_address; ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="qsl_info" class="form-label">QSL Info</label>
                            <select class="form-control" id="qsl_info" name="qsl_info">
                                <option value="Direct" <?php echo ($current_qsl_info == 'Direct') ? 'selected' : ''; ?>>Direct</option>
                                <option value="Manager" <?php echo ($current_qsl_info == 'Manager') ? 'selected' : ''; ?>>Manager</option>
                                <option value="Bureau" <?php echo ($current_qsl_info == 'Bureau') ? 'selected' : ''; ?>>Bureau</option>
                            </select>
                        </div>
                        <div class="mb-3" id="qsl-manager-field" style="<?php echo ($current_qsl_info == 'Manager') ? '' : 'display: none;'; ?>">
                            <label for="qsl_manager" class="form-label">QSL Manager</label>
                            <input type="text" class="form-control" id="qsl_manager" name="qsl_manager" value="<?php echo $current_qsl_manager; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="grid" class="form-label">Grid</label>
                            <input type="text" class="form-control" id="grid" name="grid" value="<?php echo $current_grid; ?>">
                        </div>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <div id="profile-update-message" class="mt-3"></div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    Profile Picture
                </div>
                <div class="card-body text-center">
                    <?php if ($current_profile_picture): ?>
                        <img id="profile-picture-preview" src="<?php echo ROOT_URL . '/' . $current_profile_picture; ?>" alt="Profile Picture" class="img-fluid rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                    <?php else: ?>
                        <img id="profile-picture-preview" src="https://via.placeholder.com/150?text=No+Image" alt="No Profile Picture" class="img-fluid rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                    <?php endif; ?>
                    <form id="profile-picture-form" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="profile_picture" class="form-label">Upload New Picture</label>
                            <input class="form-control" type="file" id="profile_picture" name="profile_picture" accept="image/*">
                        </div>
                        <button type="submit" class="btn btn-primary">Upload Picture</button>
                        <div id="profile-picture-message" class="mt-3"></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('qsl_info').addEventListener('change', function() {
    var qslManagerField = document.getElementById('qsl-manager-field');
    if (this.value === 'Manager') {
        qslManagerField.style.display = 'block';
    } else {
        qslManagerField.style.display = 'none';
    }
});
</script>

<?php
include_once ROOT_PATH . '/templates/footer.php';
?>

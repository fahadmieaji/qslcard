<?php
// public/view_profile.php

require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_PATH . '/src/utils.php';
require_once ROOT_PATH . '/src/db.php'; // Include the database connection

secure_session_start();

$user_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

if (!$user_id) {
    header('Location: public_search.php');
    exit();
}

$pdo = get_db_connection();

// Fetch user details
$stmt_user_details = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt_user_details->execute([$user_id]);
$user_details = $stmt_user_details->fetch(PDO::FETCH_ASSOC);

if (!$user_details) {
    // User not found
    header('Location: public_search.php');
    exit();
}

$username = htmlspecialchars($user_details['username']);
$profile_picture = htmlspecialchars($user_details['profile_picture_url'] ?? '');
$name = htmlspecialchars($user_details['name'] ?? '');
$mobile = htmlspecialchars($user_details['mobile'] ?? '');
$whatsapp = htmlspecialchars($user_details['whatsapp'] ?? '');
$facebook = htmlspecialchars($user_details['facebook'] ?? '');
$website = htmlspecialchars($user_details['website'] ?? '');
$address = htmlspecialchars($user_details['address'] ?? '');
$country = htmlspecialchars($user_details['country'] ?? '');
$postal_address = htmlspecialchars($user_details['postal_address'] ?? '');
$qsl_info = htmlspecialchars($user_details['qsl_info'] ?? '');
$qsl_manager = htmlspecialchars($user_details['qsl_manager'] ?? '');
$grid = htmlspecialchars($user_details['grid'] ?? '');

$pageTitle = 'Profile of ' . $username;
include_once ROOT_PATH . '/templates/header.php';

?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <?php if ($profile_picture): ?>
                        <img src="<?php echo ROOT_URL . '/' . $profile_picture; ?>" alt="Profile Picture" class="img-fluid rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                    <?php else: ?>
                        <img src="https://via.placeholder.com/150?text=No+Image" alt="No Profile Picture" class="img-fluid rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                    <?php endif; ?>
                    <h5 class="card-title"><?php echo $username; ?></h5>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Profile Information</h5>
                    <table class="table table-striped">
                        <tbody>
                            <tr>
                                <th scope="row">Name</th>
                                <td><?php echo $name; ?></td>
                            </tr>
                            <tr>
                                <th scope="row">Mobile</th>
                                <td><?php echo $mobile; ?></td>
                            </tr>
                            <tr>
                                <th scope="row">Whatsapp</th>
                                <td><?php echo $whatsapp; ?></td>
                            </tr>
                            <tr>
                                <th scope="row">Facebook</th>
                                <td><?php echo $facebook; ?></td>
                            </tr>
                            <tr>
                                <th scope="row">Website</th>
                                <td><?php echo $website; ?></td>
                            </tr>
                            <tr>
                                <th scope="row">Address</th>
                                <td><?php echo $address; ?></td>
                            </tr>
                            <tr>
                                <th scope="row">Country</th>
                                <td><?php echo $country; ?></td>
                            </tr>
                            <tr>
                                <th scope="row">Postal Address</th>
                                <td><?php echo $postal_address; ?></td>
                            </tr>
                            <tr>
                                <th scope="row">QSL Info</th>
                                <td><?php echo $qsl_info; ?></td>
                            </tr>
                            <?php if ($qsl_info == 'Manager'): ?>
                            <tr>
                                <th scope="row">QSL Manager</th>
                                <td><?php echo $qsl_manager; ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <th scope="row">Grid</th>
                                <td><?php echo $grid; ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include_once ROOT_PATH . '/templates/footer.php';
?>

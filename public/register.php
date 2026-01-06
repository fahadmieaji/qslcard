<?php
// public/register.php

require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_PATH . '/src/db.php';
require_once ROOT_PATH . '/src/utils.php'; // We will create this utility file next

$settings = get_settings();
$registration_enabled = $settings['registration_enabled'] ?? false;

if (!$registration_enabled) {
    header('Location: login.php?registration_disabled=1');
    exit();
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $callsign = $_POST['callsign'] ?? '';

    // Validation
    if (empty($username)) $errors[] = 'Username is required.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if (empty($password)) $errors[] = 'Password is required.';
    if ($password !== $password_confirm) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        try {
            $pdo = get_db_connection();
            // Check if username or email already exists
            $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $errors[] = 'Username or email already in use.';
            } else {
                // Hash password and insert user
                $stmt = $pdo->prepare('INSERT INTO users (username, email, password, callsign, is_email_verified) VALUES (?, ?, ?, ?, 1)'); // Set is_email_verified to true by default
                $stmt->execute([$username, $email, $hashed_password, $callsign]);
                


                $success = 'Registration successful! You can now log in.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Register';
include_once ROOT_PATH . '/templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3>Register</h3>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error): ?>
                            <p class="mb-0"><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <p class="mb-0"><?php echo htmlspecialchars($success); ?></p>
                    </div>
                <?php else: ?>
                    <form action="register.php" method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="password_confirm" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                        </div>
                        <div class="mb-3">
                            <label for="callsign" class="form-label">Callsign (Optional)</label>
                            <input type="text" class="form-control" id="callsign" name="callsign">
                        </div>
                        <button type="submit" class="btn btn-primary">Register</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
include_once ROOT_PATH . '/templates/footer.php';
?>

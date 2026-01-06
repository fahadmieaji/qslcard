<?php
// public/login.php

require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_PATH . '/src/db.php';
require_once ROOT_PATH . '/src/utils.php';

secure_session_start();

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$errors = [];
$success = '';


if (isset($_GET['registration_disabled'])) {
    $errors[] = 'Registration is currently disabled.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $errors[] = 'Username and password are required.';
    } else {
        try {
            $pdo = get_db_connection();
            $stmt = $pdo->prepare('SELECT id, username, password, callsign FROM users WHERE username = ?');
            $stmt->execute([$username]);
            $user = $stmt->fetch();

                            if ($user && password_verify($password, $user['password'])) {
                                // Password is correct, so start a new session
                                $_SESSION['user_id'] = $user['id'];
                                $_SESSION['username'] = $user['username'];
                                $_SESSION['callsign'] = $user['callsign'];
                                
                                // Login successful, redirect to dashboard
                                header('Location: dashboard.php');
                                exit();
                            } else {
                                $errors[] = 'Invalid username or password.';
                            }        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Login';
include_once ROOT_PATH . '/templates/header.php';
?>

<div class="row justify-content-center h-100 d-flex align-items-center">
    <div class="col-md-6">
        <div class="card animated-fadeInUp">
            <div class="card-header bg-gradient-primary text-white">
                <h3><i class="bi bi-person-circle me-2"></i>Login</h3>
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
                <?php endif; ?>

                <form action="login.php" method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Login</button>
                </form>
            </div>

        </div>
    </div>
</div>

<?php
include_once ROOT_PATH . '/templates/footer.php';
?>

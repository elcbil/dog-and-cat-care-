<?php
session_start();
require_once './configs/database.php';

$error_message = '';
$success_message = '';

if ($_POST) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $email = trim($_POST['email']);

    if (empty($username) || empty($password) || empty($email)) {
        $error_message = 'Please fill in all required fields.';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error_message = 'Password must be at least 6 characters long.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $database = new Database();
        $db = $database->getConnection();

        $query = "INSERT INTO users (username, password_hash, email) VALUES (:username, :password_hash, :email)";
        $stmt = $db->prepare($query);

        if ($stmt->execute([
            ':username' => $username,
            ':password_hash' => $password_hash,
            ':email' => $email
        ])) {
            $success_message = 'Account created successfully! You can now login.';
        } else {
            $error_message = 'Failed to create account.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Register</title>
    <link href="src/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="src/fontawesome/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="src/css/auth.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <i class="fas fa-user-plus fa-3x"></i>
                <h3>Register New User</h3>
                <p class="mb-0">Create a new account</p>
            </div>
            <div class="login-body">
                <?php if ($success_message): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" id="registerForm">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="username" name="username" required autocomplete="username">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-login w-100">
                        <i class="fas fa-user-plus me-2"></i>
                        Register
                    </button>
                </form>
            </div>
        </div>
    </div>
    <script src="src/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>

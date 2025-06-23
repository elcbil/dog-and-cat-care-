<?php
session_start();
require_once './configs/database.php';

// Check if user is already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit();
}

$error_message = '';

if ($_POST) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (!empty($username) && !empty($password)) {
        $user = authenticateAdmin($username, $password);
        
        if ($user) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $user->id;
            $_SESSION['admin_username'] = $user->username;
            header('Location: dashboard.php');
            exit();
        } else {
            $error_message = 'Invalid username or password.';
        }
    } else {
        $error_message = 'Please fill in all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Pet Shop</title>
    <link href="src/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="src/fontawesome/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="src/css/auth.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <i class="fas fa-paw fa-3x"></i>
                <h3>Pet Shop Admin</h3>
                <p class="mb-0">Sign in to your account</p>
            </div>
            <div class="login-body">
                <?php if ($error_message): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="loginForm">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="username" name="username" required value="admin" autocomplete="username">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required value="admin1234" autocomplete="current-password">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-login w-100">
                        <i class="fas fa-sign-in-alt me-2"></i>
                        Sign In
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="/src/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add loading state to button on form submit
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.querySelector('.btn-login');
            btn.classList.add('loading');
            btn.disabled = true;
        });

        // Prevent double submission
        let formSubmitted = false;
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            if (formSubmitted) {
                e.preventDefault();
                return false;
            }
            formSubmitted = true;
        });

        // Auto-focus first empty field
        window.addEventListener('load', function() {
            const username = document.getElementById('username');
            const password = document.getElementById('password');
            
            if (!username.value) {
                username.focus();
            } else if (!password.value) {
                password.focus();
            }
        });
    </script>
</body>
</html>
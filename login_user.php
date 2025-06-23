<?php
session_start();
require_once './configs/database.php';


$error_message = '';

if ($_POST) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        $user = authenticateUser($username, $password);

        if ($user) {
            $_SESSION['user_logged_in'] = true;
            $_SESSION['user_id'] = $user->id;
            $_SESSION['user_username'] = $user->username;
            header('Location: index.php');
            exit();
        } else {
            $error_message = 'Invalid username or password.';
        }
    } else {
        $error_message = 'Please fill in all fields.';
    }
}

function authenticateUser($username, $password) {
    $database = new Database();
    $db = $database->getConnection();

    $query = "SELECT * FROM users WHERE username = :username";
    $stmt = $db->prepare($query);
    $stmt->execute([':username' => $username]);

    $user = $stmt->fetch();

    if ($user && password_verify($password, $user->password_hash)) {
        return $user;
    }

    return false;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Login</title>
    <link href="src/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="src/fontawesome/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="src/css/auth.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <i class="fas fa-paw fa-3x"></i>
                <h3>User Login</h3>
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
                            <input type="text" class="form-control" id="username" name="username" required autocomplete="username">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password">
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
    <script src="src/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>

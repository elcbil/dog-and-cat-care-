<?php
session_start();
require_once './configs/database.php';

// Check if user is logged in as admin (optional - remove this if you want public registration)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Uncomment the lines below if you want to restrict registration to logged-in admins only
    // header('Location: login.php');
    // exit();
}

$success_message = '';
$error_message = '';

if ($_POST) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $email = trim($_POST['email']);
    
    // Validation
    if (empty($username) || empty($password) || empty($email)) {
        $error_message = 'Please fill in all required fields.';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error_message = 'Password must be at least 6 characters long.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } else {
        $result = registerAdmin($username, $password, $email);
        
        if ($result['success']) {
            $success_message = $result['message'];
        } else {
            $error_message = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Admin - Pet Shop</title>
    <link href="src/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="src/fontawesome/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="src/css/auth.css">
</head>
<body>
    <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']): ?>
    <a href="dashboard.php" class="back-link">
        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
    </a>
    <?php else: ?>
    <a href="login.php" class="back-link">
        <i class="fas fa-arrow-left me-2"></i>Back to Login
    </a>
    <?php endif; ?>

    <div class="login-card">
        <div class="login-header">
            <i class="fas fa-user-plus fa-3x mb-3"></i>
            <h3>Register New Admin</h3>
            <p class="mb-0">Create a new administrator account</p>
        </div>
        <div class="login-body">
            <?php if ($success_message): ?>
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                    <hr>
                    <div class="d-flex gap-2">
                        <a href="login.php" class="btn btn-success btn-sm">Go to Login</a>
                        <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']): ?>
                        <a href="dashboard.php" class="btn btn-outline-success btn-sm">Back to Dashboard</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!$success_message): ?>
            <form method="POST" id="registerForm">
                <div class="mb-3">
                    <label for="username" class="form-label">Username *</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                    </div>
                    <div class="form-text">Choose a unique username for the admin account.</div>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address *</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Password *</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" 
                               required minlength="6" onkeyup="checkPasswordStrength()">
                        <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('password')">
                            <i class="fas fa-eye" id="passwordToggleIcon"></i>
                        </button>
                    </div>
                    <div class="password-strength" id="passwordStrength"></div>
                    <div class="form-text">Password must be at least 6 characters long.</div>
                </div>
                
                <div class="mb-4">
                    <label for="confirm_password" class="form-label">Confirm Password *</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                               required minlength="6" onkeyup="checkPasswordMatch()">
                        <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('confirm_password')">
                            <i class="fas fa-eye" id="confirmPasswordToggleIcon"></i>
                        </button>
                    </div>
                    <div id="passwordMatchMessage" class="form-text"></div>
                </div>
                
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="agreeTerms" required>
                        <label class="form-check-label" for="agreeTerms">
                            I agree to the terms and conditions for admin access *
                        </label>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-register w-100" id="submitBtn">
                    <i class="fas fa-user-plus me-2"></i>
                    Create Admin Account
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <script src="/src/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById(fieldId + 'ToggleIcon');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthBar = document.getElementById('passwordStrength');
            
            // Reset classes
            strengthBar.className = 'password-strength';
            
            if (password.length === 0) {
                return;
            }
            
            let strength = 0;
            
            // Length check
            if (password.length >= 6) strength++;
            if (password.length >= 8) strength++;
            
            // Character variety checks
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            if (strength <= 2) {
                strengthBar.classList.add('strength-weak');
            } else if (strength <= 4) {
                strengthBar.classList.add('strength-medium');
            } else {
                strengthBar.classList.add('strength-strong');
            }
        }

        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const messageDiv = document.getElementById('passwordMatchMessage');
            
            if (confirmPassword.length === 0) {
                messageDiv.textContent = '';
                messageDiv.className = 'form-text';
                return;
            }
            
            if (password === confirmPassword) {
                messageDiv.textContent = '✓ Passwords match';
                messageDiv.className = 'form-text text-success';
            } else {
                messageDiv.textContent = '✗ Passwords do not match';
                messageDiv.className = 'form-text text-danger';
            }
        }

        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long!');
                return false;
            }
        });

        // Real-time username validation
        document.getElementById('username').addEventListener('input', function() {
            const username = this.value;
            const regex = /^[a-zA-Z0-9_]+$/;
            
            if (username && !regex.test(username)) {
                this.setCustomValidity('Username can only contain letters, numbers, and underscores');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
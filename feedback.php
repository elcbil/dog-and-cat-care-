<?php
session_start();
require_once './configs/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login_user.php');
    exit();
}

$user_username = isset($_SESSION['user_username']) ? $_SESSION['user_username'] : 'Guest';
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : null;

// Get database connection using the Database class
$database = new Database();
$db = $database->getConnection();

// Verify booking exists and belongs to user
$booking = null;
if ($booking_id && $user_id) {
    $stmt = $db->prepare("SELECT b.*, s.name as service_name FROM bookings b 
                          JOIN services s ON b.service_id = s.id 
                          WHERE b.id = ? AND b.user_id = ? AND b.status = 'completed'");
    $stmt->execute([$booking_id, $user_id]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        header('Location: history.php');
        exit();
    }
}

// Check if feedback already exists for this booking
$existing_feedback = null;
if ($booking_id) {
    $stmt = $db->prepare("SELECT * FROM testimonials WHERE booking_id = ?");
    $stmt->execute([$booking_id]);
    $existing_feedback = $stmt->fetch();
}

// Process form submission
if ($_POST && $booking) {
    $author = trim($_POST['author']);
    $comments = trim($_POST['comments']);
    $star = (int)$_POST['star'];
    $pet_type = trim($_POST['pet_type']);
    
    // Validation
    $errors = [];
    if (empty($author)) $errors[] = "Name is required";
    if (empty($comments)) $errors[] = "Comments are required";
    if ($star < 1 || $star > 5) $errors[] = "Please select a star rating";
    
    if (empty($errors)) {
        try {
            if ($existing_feedback) {
                // Update existing feedback
                $stmt = $db->prepare("UPDATE testimonials SET author = ?, comments = ?, star = ?, pet_type = ?, updated_at = NOW() WHERE booking_id = ?");
                $stmt->execute([$author, $comments, $star, $pet_type, $booking_id]);
            } else {
                // Insert new feedback
                $stmt = $db->prepare("INSERT INTO testimonials (author, comments, star, pet_type, booking_id, is_approved, created_at) VALUES (?, ?, ?, ?, ?, 0, NOW())");
                $stmt->execute([$author, $comments, $star, $pet_type, $booking_id]);
            }
            
            header('Location: history.php?feedback=success');
            exit();
        } catch (Exception $e) {
            $errors[] = "Error submitting feedback. Please try again.";
        }
    }
}

// Get site settings
$settings = getSiteSettings();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="src/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="src/css/style.css">
    <link rel="icon" type="image/x-icon" href="media/favicon.ico">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <title>Leave Feedback - <?= $settings['site_title'] ?? 'PetCare Pro' ?></title>
    <style>
        :root {
            --primary-color: #f59e0b;
            --primary-dark: #FFFAEC;
            --secondary-color: #f59e0b;
            --accent-color: #f59e0b;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --error-color: #ef4444;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --background-light: #f8fafc;
            --card-background: #ffffff;
            --border-color: #e5e7eb;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #FFFAEC;

            min-height: 100vh;
            color: var(--text-primary);
        }

        .feedback-container {
            padding: 140px 0 60px;
            min-height: 100vh;
            position: relative;
        }

        .feedback-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(196, 230, 186, 0.1) 0%, rgba(122, 193, 144, 0.1) 100%);

            backdrop-filter: blur(20px);
        }

        .container {
            position: relative;
            z-index: 1;
        }

        .feedback-card {
            background: var(--card-background);
            border-radius: 24px;
            box-shadow: var(--shadow-xl);
            padding: 40px;
            margin-bottom: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(20px);
            transition: all 0.3s ease;
        }

        .feedback-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .booking-summary {
            background: linear-gradient(135deg, var(--secondary-color), rgba(99, 102, 241, 0.05));
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 32px;
            border: 1px solid rgba(99, 102, 241, 0.1);
            position: relative;
            overflow: hidden;
        }

        .booking-summary::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
        }

        .booking-summary h5 {
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .booking-summary h5::before {
            content: '📅';
            font-size: 1.2em;
        }

        .star-rating {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
            align-items: center;
        }

        .star {
            font-size: 28px;
            color: #d1d5db;
            cursor: pointer;
            transition: all 0.2s ease;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .star:hover {
            transform: scale(1.1);
            color: var(--accent-color);
        }

        .star.active {
            color: var(--accent-color);
            text-shadow: 0 0 10px rgba(245, 158, 11, 0.5);
        }

        .back-btn {
            background: var(--card-background);
            border: 2px solid var(--primary-color);
            padding: 12px 24px;
            border-radius: 50px;
            color: var(--primary-color);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 32px;
            transition: all 0.3s ease;
            font-weight: 500;
            box-shadow: var(--shadow-sm);
        }

        .back-btn:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            text-decoration: none;
        }

        .submit-btn {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border: 1px solid black;
            padding: 16px 32px;
            border-radius: 50px;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-md);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 16px;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
        }

        .form-label {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .form-control {
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 12px 16px;
            transition: all 0.3s ease;
            background: var(--card-background);
            font-size: 16px;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
            outline: none;
            transform: translateY(-1px);
        }

        .already-submitted {
            background: linear-gradient(135deg, #dbeafe, #e0f2fe);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            position: relative;
            overflow: hidden;
        }

        .already-submitted::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #3b82f6, #06b6d4);
        }

        .already-submitted h6 {
            color: #1e40af;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .alert {
            border-radius: 12px;
            padding: 16px 20px;
            border: none;
            box-shadow: var(--shadow-sm);
        }

        .alert-danger {
            background: linear-gradient(135deg, #fef2f2, #fecaca);
            color: #991b1b;
            border-left: 4px solid var(--error-color);
        }

        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .bg-success {
            background: linear-gradient(135deg, var(--success-color), #059669) !important;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 12px;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .page-subtitle {
            color: var(--text-secondary);
            font-size: 1.1rem;
            margin-bottom: 32px;
        }

        /* Navbar Styles */
        .navbar {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: var(--shadow-sm);
        }

        .navbar-brand img {
            height: 40px;
            width: auto;
        }

        .nav-link {
            color: var(--text-primary) !important;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 8px 16px !important;
            border-radius: 8px;
        }

        .nav-link:hover {
            color: var(--primary-color) !important;
            background: rgba(99, 102, 241, 0.1);
        }


        /* Responsive Design */
        @media (max-width: 768px) {
            .feedback-card {
                padding: 24px;
                margin: 0 16px;
            }
            
            .page-title {
                font-size: 2rem;
            }
            
            .booking-summary {
                padding: 20px;
            }
            
            .star {
                font-size: 24px;
            }
        }

        /* Animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .feedback-card {
            animation: fadeInUp 0.6s ease-out;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php"><img src="media/icon.png" alt="" srcset=""></a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="fas fa-home me-1"></i>Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="history.php"><i class="fas fa-history me-1"></i>My Bookings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout_user.php"><i class="fas fa-sign-out-alt me-1"></i>Logout (<?= htmlspecialchars($user_username); ?>)</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="feedback-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <a href="history.php" class="back-btn">
                        <i class="fas fa-arrow-left"></i>
                        Back to Booking History
                    </a>
                    
                    <?php if (!$booking): ?>
                        <div class="alert alert-danger">
                            <h5><i class="fas fa-exclamation-triangle me-2"></i>Invalid Booking</h5>
                            <p>The booking you're trying to provide feedback for could not be found or is not eligible for feedback.</p>
                            <a href="history.php" class="btn btn-primary">Go Back</a>
                        </div>
                    <?php else: ?>
                        <div class="feedback-card">
                            <h2 class="page-title">
                                <?= $existing_feedback ? 'Update Your Feedback' : 'Share Your Experience' ?>
                            </h2>
                            <p class="page-subtitle">
                                <?= $existing_feedback ? 'Modify your previous feedback below' : 'Help us improve by sharing your thoughts about our service' ?>
                            </p>
                            
                            <!-- Booking Summary -->
                            <div class="booking-summary">
                                <h5>Service Details</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong><i class="fas fa-paw me-2"></i>Service:</strong> <?= htmlspecialchars($booking->service_name) ?></p>
                                        <p><strong><i class="fas fa-calendar me-2"></i>Date:</strong> <?= date('F j, Y', strtotime($booking->appointment_date)) ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong><i class="fas fa-clock me-2"></i>Time:</strong> <?= date('g:i A', strtotime($booking->appointment_time)) ?></p>
                                        <p><strong><i class="fas fa-check-circle me-2"></i>Status:</strong> <span class="badge bg-success">Completed</span></p>
                                    </div>
                                </div>
                            </div>

                            <?php if ($existing_feedback && !$_POST): ?>
                                <div class="already-submitted">
                                    <h6><i class="fas fa-edit me-2"></i>Previous Feedback Submitted</h6>
                                    <p class="mb-2">You can update your feedback using the form below.</p>
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        Last updated: <?= date('F j, Y g:i A', strtotime($existing_feedback->updated_at)) ?>
                                    </small>
                                </div>
                            <?php endif; ?>

                            <!-- Error Messages -->
                            <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger">
                                    <h6><i class="fas fa-exclamation-triangle me-2"></i>Please fix the following errors:</h6>
                                    <ul class="mb-0">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?= htmlspecialchars($error) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <!-- Feedback Form -->
                            <form method="POST">
                                <div class="mb-4">
                                    <label for="author" class="form-label">
                                        <i class="fas fa-user"></i>
                                        Your Name *
                                    </label>
                                    <input type="text" class="form-control" id="author" name="author" 
                                           value="<?= htmlspecialchars($existing_feedback ? $existing_feedback->author : ($user_username !== 'Guest' ? $user_username : '')) ?>" 
                                           required>
                                </div>

                                <div class="mb-4">
                                    <label for="pet_type" class="form-label">
                                        <i class="fas fa-paw"></i>
                                        Pet Type
                                    </label>
                                    <input type="text" class="form-control" id="pet_type" name="pet_type" 
                                           value="<?= htmlspecialchars($existing_feedback ? $existing_feedback->pet_type : '') ?>"
                                           placeholder="e.g., Dog, Cat, Bird, etc.">
                                </div>

                                <div class="mb-4">
                                    <label class="form-label">
                                        <i class="fas fa-star"></i>
                                        Rating *
                                    </label>
                                    <div class="star-rating" id="starRating">
                                        <span class="star" data-rating="1">★</span>
                                        <span class="star" data-rating="2">★</span>
                                        <span class="star" data-rating="3">★</span>
                                        <span class="star" data-rating="4">★</span>
                                        <span class="star" data-rating="5">★</span>
                                    </div>
                                    <input type="hidden" id="star" name="star" value="<?= $existing_feedback ? $existing_feedback->star : '' ?>" required>
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Click on stars to rate your experience
                                    </small>
                                </div>

                                <div class="mb-4">
                                    <label for="comments" class="form-label">
                                        <i class="fas fa-comment"></i>
                                        Your Feedback *
                                    </label>
                                    <textarea class="form-control" id="comments" name="comments" rows="5" 
                                              placeholder="Please share your experience with our service..." required><?= htmlspecialchars($existing_feedback ? $existing_feedback->comments : '') ?></textarea>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="submit-btn">
                                        <?= $existing_feedback ? '<i class="fas fa-sync-alt me-2"></i>Update Feedback' : '<i class="fas fa-paper-plane me-2"></i>Submit Feedback' ?>
                                    </button>
                                </div>
                            </form>

                            <div class="mt-4 text-center">
                                <small class="text-muted">
                                    <i class="fas fa-shield-alt me-1"></i>
                                    Your feedback will be reviewed before being published on our website
                                </small>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>


    <script src="src/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        // Star rating functionality
        document.addEventListener('DOMContentLoaded', function() {
            const stars = document.querySelectorAll('.star');
            const starInput = document.getElementById('star');
            const currentRating = starInput.value;

            // Set initial rating if exists
            if (currentRating) {
                updateStarDisplay(parseInt(currentRating));
            }

            stars.forEach(star => {
                star.addEventListener('click', function() {
                    const rating = parseInt(this.dataset.rating);
                    starInput.value = rating;
                    updateStarDisplay(rating);
                });

                star.addEventListener('mouseover', function() {
                    const rating = parseInt(this.dataset.rating);
                    updateStarDisplay(rating);
                });
            });

            document.getElementById('starRating').addEventListener('mouseleave', function() {
                const currentRating = parseInt(starInput.value) || 0;
                updateStarDisplay(currentRating);
            });

            function updateStarDisplay(rating) {
                stars.forEach((star, index) => {
                    if (index < rating) {
                        star.classList.add('active');
                    } else {
                        star.classList.remove('active');
                    }
                });
            }
        });

        // Form animations
        document.addEventListener('DOMContentLoaded', function() {
            const formControls = document.querySelectorAll('.form-control');
            
            formControls.forEach(control => {
                control.addEventListener('focus', function() {
                    this.style.transform = 'translateY(-1px)';
                });
                
                control.addEventListener('blur', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html>
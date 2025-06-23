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
    <title>Leave Feedback - <?= $settings['site_title'] ?? 'PetCare Pro' ?></title>
    <style>
        .feedback-container {
            padding: 120px 0 60px;
            min-height: 100vh;
            background: linear-gradient(135deg, #f4f1e1 0%, #c8e6c9 100%);
        }
        .feedback-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 20px;
        }
        .booking-summary {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .star-rating {
            display: flex;
            gap: 5px;
            margin-bottom: 15px;
        }
        .star {
            font-size: 24px;
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s;
        }
        .star:hover,
        .star.active {
            color: #ffc107;
        }
        .back-btn {
            background: linear-gradient(45deg, #f4f1e1 0%, #e0d4b2 100%);
            border: 1px solid black;
            padding: 12px 30px;
            border-radius: 25px;
            color: black;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 30px;
            transition: all 0.3s ease;
        }
        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(72, 182, 114, 0.4);
            text-decoration: none;
        }
        .submit-btn {
            background: linear-gradient(45deg, #28a745, #20c997);
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
            color: white;
        }
        .form-control:focus {
            border-color: #28a745;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        }
        .already-submitted {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
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
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="history.php">My Bookings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout_user.php">Logout (<?= htmlspecialchars($user_username); ?>)</a>
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
                        ← Back to Booking History
                    </a>
                    
                    <?php if (!$booking): ?>
                        <div class="alert alert-danger">
                            <h5>Invalid Booking</h5>
                            <p>The booking you're trying to provide feedback for could not be found or is not eligible for feedback.</p>
                            <a href="history.php" class="btn btn-primary">Go Back</a>
                        </div>
                    <?php else: ?>
                        <div class="feedback-card">
                            <h2 class="mb-4">
                                <?= $existing_feedback ? 'Update Your Feedback' : 'Leave Your Feedback' ?>
                            </h2>
                            
                            <!-- Booking Summary -->
                            <div class="booking-summary">
                                <h5 class="mb-3">Service Details</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Service:</strong> <?= htmlspecialchars($booking->service_name) ?></p>
                                        <p><strong>Date:</strong> <?= date('F j, Y', strtotime($booking->appointment_date)) ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Time:</strong> <?= date('g:i A', strtotime($booking->appointment_time)) ?></p>
                                        <p><strong>Status:</strong> <span class="badge bg-success">Completed</span></p>
                                    </div>
                                </div>
                            </div>

                            <?php if ($existing_feedback && !$_POST): ?>
                                <div class="already-submitted">
                                    <h6>📝 You have already submitted feedback for this service</h6>
                                    <p class="mb-2">You can update your feedback using the form below.</p>
                                    <small class="text-muted">
                                        Last updated: <?= date('F j, Y g:i A', strtotime($existing_feedback->updated_at)) ?>
                                    </small>
                                </div>
                            <?php endif; ?>

                            <!-- Error Messages -->
                            <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?= htmlspecialchars($error) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <!-- Feedback Form -->
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="author" class="form-label">Your Name *</label>
                                    <input type="text" class="form-control" id="author" name="author" 
                                           value="<?= htmlspecialchars($existing_feedback ? $existing_feedback->author : ($user_username !== 'Guest' ? $user_username : '')) ?>" 
                                           required>
                                </div>

                                <div class="mb-3">
                                    <label for="pet_type" class="form-label">Pet Type</label>
                                    <input type="text" class="form-control" id="pet_type" name="pet_type" 
                                           value="<?= htmlspecialchars($existing_feedback ? $existing_feedback->pet_type : '') ?>"
                                           placeholder="e.g., Dog, Cat, Bird, etc.">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Rating *</label>
                                    <div class="star-rating" id="starRating">
                                        <span class="star" data-rating="1">★</span>
                                        <span class="star" data-rating="2">★</span>
                                        <span class="star" data-rating="3">★</span>
                                        <span class="star" data-rating="4">★</span>
                                        <span class="star" data-rating="5">★</span>
                                    </div>
                                    <input type="hidden" id="star" name="star" value="<?= $existing_feedback ? $existing_feedback->star : '' ?>" required>
                                    <small class="text-muted">Click on stars to rate your experience</small>
                                </div>

                                <div class="mb-4">
                                    <label for="comments" class="form-label">Your Feedback *</label>
                                    <textarea class="form-control" id="comments" name="comments" rows="5" 
                                              placeholder="Please share your experience with our service..." required><?= htmlspecialchars($existing_feedback ? $existing_feedback->comments : '') ?></textarea>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="submit-btn">
                                        <?= $existing_feedback ? '🔄 Update Feedback' : '📝 Submit Feedback' ?>
                                    </button>
                                </div>
                            </form>

                            <div class="mt-3">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle"></i>
                                    Your feedback will be reviewed before being published on our website.
                                </small>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-bottom">
                    <p>&copy; 2025 <?= $settings['site_title'] ?? 'PetCare Pro' ?>. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

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
    </script>
</body>
</html>
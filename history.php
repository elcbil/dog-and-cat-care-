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

// Get user's bookings
$user_bookings = [];
if ($user_id) {
    $user_bookings = getUserBookingsByUserId($user_id);
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
    <title>Booking History - <?= $settings['site_title'] ?? 'PetCare Pro' ?></title>
    <style>
        .history-container {
            padding: 120px 0 60px;
            min-height: 100vh;
            background: linear-gradient(135deg, #f4f1e1 0%, #c8e6c9 100%);
        }
        .booking-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 20px;
            transition: transform 0.3s ease;
        }
        .booking-card:hover {
            transform: translateY(-5px);
        }
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .status-confirmed {
            background: #d1ecf1;
            color: #0c5460;
        }
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        .status-completed {
            background: #d4edda;
            color: #155724;
        }
        .booking-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        .booking-info h5 {
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .booking-info p {
            color: #6c757d;
            margin-bottom: 5px;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        .empty-state img {
            width: 200px;
            opacity: 0.5;
            margin-bottom: 20px;
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
        .feedback-btn {
            background: linear-gradient(45deg, #28a745, #20c997);
            border: none;
            padding: 8px 20px;
            border-radius: 20px;
            color: white;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            margin-top: 10px;
            transition: all 0.3s ease;
        }
        .feedback-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
            color: white;
            text-decoration: none;
        }
        .booking-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
        }
        .status-message {
            flex: 1;
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

    <div class="history-container">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <a href="index.php" class="back-btn">
                        ← Back to Home
                    </a>
                    <h2 class="mb-4">My Booking History</h2>
                    
                    <?php if (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <strong>🎉 Booking Successful!</strong> Your appointment has been successfully booked and is now pending confirmation.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_GET['feedback']) && $_GET['feedback'] === 'success'): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <strong>🎉 Thank You!</strong> Your feedback has been submitted successfully and is pending approval.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (empty($user_bookings)): ?>
                        <div class="empty-state">
                            <img src="media/hero-min.png" alt="No bookings" />
                            <h4>No Bookings Yet</h4>
                            <p class="text-muted">You haven't made any appointments yet. Book your first appointment now!</p>
                            <a href="index.php#contact" class="btn btn-primary btn-lg">Book Appointment</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($user_bookings as $booking): ?>
                            <div class="booking-card">
                                <div class="booking-details">
                                    <div class="booking-info">
                                        <h5><?= htmlspecialchars($booking->service_name) ?></h5>
                                        <p><strong>Date:</strong> <?= date('F j, Y', strtotime($booking->appointment_date)) ?></p>
                                        <p><strong>Time:</strong> <?= date('g:i A', strtotime($booking->appointment_time)) ?></p>
                                        <p><strong>Name:</strong> <?= htmlspecialchars($booking->first_name . ' ' . $booking->last_name) ?></p>
                                        <?php if ($booking->phone): ?>
                                            <p><strong>Phone:</strong> <?= htmlspecialchars($booking->phone) ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="booking-status">
                                        <span class="status-badge status-<?= $booking->status ?>">
                                            <?= ucfirst($booking->status) ?>
                                        </span>
                                        <p class="text-muted mt-2 mb-0">
                                            <small>Booked on <?= date('M j, Y', strtotime($booking->created_at)) ?></small>
                                        </p>
                                    </div>
                                </div>
                                
                                <?php if ($booking->message): ?>
                                    <div class="booking-message">
                                        <strong>Message:</strong>
                                        <p class="text-muted"><?= htmlspecialchars($booking->message) ?></p>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="booking-actions">
                                    <div class="status-message">
                                        <?php if ($booking->status === 'pending'): ?>
                                            <small class="text-info">⏳ Your appointment is being reviewed. We'll contact you soon!</small>
                                        <?php elseif ($booking->status === 'confirmed'): ?>
                                            <small class="text-success">✅ Your appointment is confirmed. See you soon!</small>
                                        <?php elseif ($booking->status === 'completed'): ?>
                                            <small class="text-success">🎉 Service completed. Thank you for choosing us!</small>
                                        <?php elseif ($booking->status === 'cancelled'): ?>
                                            <small class="text-danger">❌ This appointment was cancelled.</small>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($booking->status === 'completed'): ?>
                                        <div>
                                            <a href="feedback.php?booking_id=<?= $booking->id ?>" class="feedback-btn">
                                                💬 Feedback
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
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
</body>
</html>
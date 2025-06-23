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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <title>Booking History - <?= $settings['site_title'] ?? 'PetCare Pro' ?></title>
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

        .history-container {
            padding: 140px 0 60px;
            min-height: 100vh;
            position: relative;
        }

        .history-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.03) 0%, rgba(251, 191, 36, 0.05) 100%);
            backdrop-filter: blur(20px);
        }

        .container {
            position: relative;
            z-index: 1;
        }

        .page-header {
            margin-bottom: 40px;
        }

        .page-title {
            font-size: 2.75rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 12px;
            background: linear-gradient(135deg, var(--primary-color), #d97706);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-align: center;
        }

        .page-subtitle {
            color: var(--text-secondary);
            font-size: 1.2rem;
            text-align: center;
            margin-bottom: 32px;
        }

        .booking-card {
            background: var(--card-background);
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
            padding: 30px;
            margin-bottom: 24px;
            transition: all 0.3s ease;
            border: 1px solid rgba(245, 158, 11, 0.1);
            position: relative;
            overflow: hidden;
        }

        .booking-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), #d97706);
        }

        .booking-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-xl);
            border-color: rgba(245, 158, 11, 0.2);
        }

        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .booking-info h5 {
            color: var(--text-primary);
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .booking-info h5::before {
            content: '🐾';
            font-size: 1.2em;
        }

        .booking-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px;
            background: rgba(245, 158, 11, 0.05);
            border-radius: 12px;
            border: 1px solid rgba(245, 158, 11, 0.1);
        }

        .detail-item i {
            color: var(--primary-color);
            width: 20px;
            text-align: center;
        }

        .detail-item strong {
            color: var(--text-primary);
            margin-right: 4px;
        }

        .detail-item span {
            color: var(--text-secondary);
        }

        .status-badge {
            padding: 10px 20px;
            border-radius: 25px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: var(--shadow-sm);
        }

        .status-pending {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #92400e;
            border: 1px solid #fbbf24;
        }

        .status-confirmed {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            color: #1e40af;
            border: 1px solid #60a5fa;
        }

        .status-cancelled {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #dc2626;
            border: 1px solid #f87171;
        }

        .status-completed {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
            border: 1px solid #34d399;
        }

        .booking-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
            flex-wrap: wrap;
            gap: 12px;
        }

        .status-message {
            flex: 1;
            min-width: 200px;
        }

        .status-message small {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 500;
        }

        .back-btn {
            background: var(--card-background);
            border: 2px solid var(--primary-color);
            padding: 14px 28px;
            border-radius: 50px;
            color: var(--primary-color);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 32px;
            transition: all 0.3s ease;
            font-weight: 600;
            box-shadow: var(--shadow-sm);
        }

        .back-btn:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            text-decoration: none;
        }

        .feedback-btn {
            background: linear-gradient(135deg, var(--success-color), #059669);
            border: none;
            padding: 10px 20px;
            border-radius: 25px;
            color: white;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
        }

        .feedback-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            color: white;
            text-decoration: none;
            background: linear-gradient(135deg, #059669, var(--success-color));
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: var(--card-background);
            border-radius: 24px;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(245, 158, 11, 0.1);
        }

        .empty-state img {
            width: 200px;
            opacity: 0.6;
            margin-bottom: 24px;
            filter: sepia(20%) saturate(150%) hue-rotate(25deg);
        }

        .empty-state h4 {
            color: var(--text-primary);
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .empty-state p {
            color: var(--text-secondary);
            font-size: 1.1rem;
            margin-bottom: 32px;
        }

        .btn-book {
            background: linear-gradient(135deg, var(--primary-color), #d97706);
            border: none;
            padding: 16px 32px;
            border-radius: 50px;
            color: white;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-md);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-book:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            color: white;
            background: linear-gradient(135deg, #d97706, var(--primary-color));
        }

        .booking-message {
            background: rgba(245, 158, 11, 0.05);
            border-left: 4px solid var(--primary-color);
            border-radius: 8px;
            padding: 16px;
            margin: 16px 0;
        }

        .booking-message strong {
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 8px;
        }

        .booking-message p {
            color: var(--text-secondary);
            margin: 0;
            line-height: 1.5;
        }

        .alert {
            border-radius: 16px;
            padding: 18px 24px;
            border: none;
            box-shadow: var(--shadow-sm);
            margin-bottom: 24px;
        }

        .alert-success {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
            border-left: 4px solid var(--success-color);
        }

        /* Navbar Styles */
        .navbar {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(245, 158, 11, 0.1);
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
            background: rgba(245, 158, 11, 0.1);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .page-title {
                font-size: 2.25rem;
            }
            
            .booking-card {
                padding: 20px;
                margin: 0 8px 20px 8px;
            }
            
            .booking-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .booking-details {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            
            .booking-actions {
                flex-direction: column;
                align-items: stretch;
                gap: 16px;
            }
            
            .status-message {
                text-align: center;
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

        .booking-card {
            animation: fadeInUp 0.6s ease-out;
        }

        .booking-card:nth-child(even) {
            animation-delay: 0.1s;
        }

        .booking-card:nth-child(odd) {
            animation-delay: 0.2s;
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

    <div class="history-container">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <a href="index.php" class="back-btn">
                        <i class="fas fa-arrow-left"></i>
                        Back to Home
                    </a>
                    
                    <div class="page-header">
                        <h2 class="page-title">My Booking History</h2>
                        <p class="page-subtitle">Track your appointments and leave feedback</p>
                    </div>
                    
                    <?php if (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <strong><i class="fas fa-check-circle me-2"></i>Booking Successful!</strong> Your appointment has been successfully booked and is now pending confirmation.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_GET['feedback']) && $_GET['feedback'] === 'success'): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <strong><i class="fas fa-heart me-2"></i>Thank You!</strong> Your feedback has been submitted successfully and is pending approval.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (empty($user_bookings)): ?>
                        <div class="empty-state">
                            <img src="media/hero-min.png" alt="No bookings" />
                            <h4>No Bookings Yet</h4>
                            <p>You haven't made any appointments yet. Book your first appointment now and give your pet the care they deserve!</p>
                            <a href="index.php#contact" class="btn-book">
                                <i class="fas fa-calendar-plus"></i>
                                Book Your First Appointment
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($user_bookings as $booking): ?>
                            <div class="booking-card">
                                <div class="booking-header">
                                    <div class="booking-info">
                                        <h5><?= htmlspecialchars($booking->service_name) ?></h5>
                                        <p class="text-muted mb-0">
                                            <i class="fas fa-clock me-1"></i>
                                            Booked on <?= date('M j, Y', strtotime($booking->created_at)) ?>
                                        </p>
                                    </div>
                                    <div class="booking-status">
                                        <span class="status-badge status-<?= $booking->status ?>">
                                            <?php
                                            $statusIcons = [
                                                'pending' => '⏳',
                                                'confirmed' => '✅',
                                                'completed' => '🎉',
                                                'cancelled' => '❌'
                                            ];
                                            echo $statusIcons[$booking->status] ?? '';
                                            ?>
                                            <?= ucfirst($booking->status) ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="booking-details">
                                    <div class="detail-item">
                                        <i class="fas fa-calendar-alt"></i>
                                        <strong>Date:</strong>
                                        <span><?= date('F j, Y', strtotime($booking->appointment_date)) ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-clock"></i>
                                        <strong>Time:</strong>
                                        <span><?= date('g:i A', strtotime($booking->appointment_time)) ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-user"></i>
                                        <strong>Name:</strong>
                                        <span><?= htmlspecialchars($booking->first_name . ' ' . $booking->last_name) ?></span>
                                    </div>
                                    <?php if ($booking->phone): ?>
                                        <div class="detail-item">
                                            <i class="fas fa-phone"></i>
                                            <strong>Phone:</strong>
                                            <span><?= htmlspecialchars($booking->phone) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($booking->message): ?>
                                    <div class="booking-message">
                                        <strong>
                                            <i class="fas fa-comment-dots"></i>
                                            Additional Message:
                                        </strong>
                                        <p><?= htmlspecialchars($booking->message) ?></p>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="booking-actions">
                                    <div class="status-message">
                                        <?php if ($booking->status === 'pending'): ?>
                                            <small class="text-warning">
                                                <i class="fas fa-hourglass-half"></i>
                                                Your appointment is being reviewed. We'll contact you soon!
                                            </small>
                                        <?php elseif ($booking->status === 'confirmed'): ?>
                                            <small class="text-info">
                                                <i class="fas fa-check-double"></i>
                                                Your appointment is confirmed. See you soon!
                                            </small>
                                        <?php elseif ($booking->status === 'completed'): ?>
                                            <small class="text-success">
                                                <i class="fas fa-medal"></i>
                                                Service completed. Thank you for choosing us!
                                            </small>
                                        <?php elseif ($booking->status === 'cancelled'): ?>
                                            <small class="text-danger">
                                                <i class="fas fa-times-circle"></i>
                                                This appointment was cancelled.
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($booking->status === 'completed'): ?>
                                        <div>
                                            <a href="feedback.php?booking_id=<?= $booking->id ?>" class="feedback-btn">
                                                <i class="fas fa-star"></i>
                                                Leave Feedback
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

    <script src="src/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add smooth scroll behavior
        document.addEventListener('DOMContentLoaded', function() {
            // Animate booking cards on scroll
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);

            const bookingCards = document.querySelectorAll('.booking-card');
            bookingCards.forEach(card => {
                observer.observe(card);
            });

            // Auto-dismiss alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    if (alert.classList.contains('show')) {
                        alert.classList.remove('show');
                        setTimeout(() => alert.remove(), 300);
                    }
                }, 5000);
            });
        });
    </script>
</body>
</html>

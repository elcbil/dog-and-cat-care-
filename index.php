<?php
require_once './configs/database.php';

// Start session for CSRF protection
session_start();

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    // Jika belum login, arahkan ke halaman login
    header('Location: login_user.php');
    exit();
}

$user_username = isset($_SESSION['user_username']) ? $_SESSION['user_username'] : 'Guest';

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get dynamic content
$services = getServices();
$testimonials = getTestimonials();
$settings = getSiteSettings();

// Initialize messages
$success_message = '';
$error_message = '';
$validation_errors = [];

// Security and validation functions
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePhone($phone) {
    // Allow various phone formats
    return preg_match('/^[\+]?[0-9\s\-\(\)]+$/', $phone) && strlen(preg_replace('/[^0-9]/', '', $phone)) >= 8;
}

function validateDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date && $d >= new DateTime('today');
}

function validateTime($time) {
    return preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time);
}

function validateServiceId($service_id, $services) {
    foreach ($services as $service) {
        if ($service->id == $service_id) {
            return true;
        }
    }
    return false;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_appointment'])) {
    
    // CSRF Token validation
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $validation_errors[] = 'Security token mismatch. Please try again.';
    }
    
    // Validate and sanitize input data
    $firstName = isset($_POST['firstName']) ? sanitizeInput($_POST['firstName']) : '';
    $lastName = isset($_POST['lastName']) ? sanitizeInput($_POST['lastName']) : '';
    $email = isset($_POST['email']) ? sanitizeInput($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? sanitizeInput($_POST['phone']) : '';
    $service_id = isset($_POST['service']) ? (int)$_POST['service'] : 0;
    $appointment_date = isset($_POST['appointmentDate']) ? sanitizeInput($_POST['appointmentDate']) : '';
    $appointment_time = isset($_POST['appointmentTime']) ? sanitizeInput($_POST['appointmentTime']) : '';
    $message = isset($_POST['message']) ? sanitizeInput($_POST['message']) : '';
    
    // Validation checks
    if (empty($firstName) || strlen($firstName) < 2) {
        $validation_errors[] = 'First name must be at least 2 characters long.';
    }
    
    if (empty($lastName) || strlen($lastName) < 2) {
        $validation_errors[] = 'Last name must be at least 2 characters long.';
    }
    
    if (empty($email) || !validateEmail($email)) {
        $validation_errors[] = 'Please enter a valid email address.';
    }
    
    if (!empty($phone) && !validatePhone($phone)) {
        $validation_errors[] = 'Please enter a valid phone number.';
    }
    
    if ($service_id <= 0 || !validateServiceId($service_id, $services)) {
        $validation_errors[] = 'Please select a valid service.';
    }
    
    if (empty($appointment_date) || !validateDate($appointment_date)) {
        $validation_errors[] = 'Please select a valid future date for your appointment.';
    }
    
    if (empty($appointment_time) || !validateTime($appointment_time)) {
        $validation_errors[] = 'Please select a valid time for your appointment.';
    }
    
    // Check if appointment time is within business hours (8 AM - 6 PM)
    if (validateTime($appointment_time)) {
        $time_parts = explode(':', $appointment_time);
        $hour = (int)$time_parts[0];
        if ($hour < 8 || $hour >= 18) {
            $validation_errors[] = 'Appointments are only available between 8:00 AM and 6:00 PM.';
        }
    }
    
    // If no validation errors, proceed with database insertion
    if (empty($validation_errors)) {
    $booking_data = [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'phone' => $phone,
        'service_id' => $service_id,
        'appointment_date' => $appointment_date,
        'appointment_time' => $appointment_time,
        'message' => $message,
        'created_at' => date('Y-m-d H:i:s'),
        'status' => 'pending',
        'user_id' => $_SESSION['user_id'] ?? null // Tambahkan user_id
    ];
    
    try {
        if (createBooking($booking_data)) {
            // Generate new CSRF token after successful submission
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            
            // Log successful booking (optional)
            error_log("New appointment booked: {$email} for service ID {$service_id} on {$appointment_date} at {$appointment_time}");
            
            // Redirect to history page instead of staying on index
            header('Location: history.php?status=success');
            exit();
        } else {
            // Log database error
            error_log("Failed to create booking for: {$email}");
            header('Location: ' . $_SERVER['PHP_SELF'] . '?status=db_error');
            exit();
        }
    } catch (Exception $e) {
        // Log the actual error for debugging
        error_log("Booking creation error: " . $e->getMessage());
        header('Location: ' . $_SERVER['PHP_SELF'] . '?status=db_error');
        exit();
    }
}
}

// Handle status messages from redirect
if (isset($_GET['status'])) {
    switch ($_GET['status']) {
        case 'success':
            $success_message = 'Your appointment has been successfully booked! We will contact you soon to confirm.';
            break;
        case 'db_error':
            $error_message = 'Sorry, there was a database error. Please try again or contact us directly.';
            break;
        case 'validation_error':
            if (isset($_SESSION['validation_errors'])) {
                $validation_errors = $_SESSION['validation_errors'];
                unset($_SESSION['validation_errors']);
            }
            break;
    }
}

// Get preserved form data if exists
$form_data = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : [];
if (!empty($form_data)) {
    unset($_SESSION['form_data']);
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="src\bootstrap\css\bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="src\css\style.css">
      <link rel="icon" type="image/x-icon" href="media\favicon.ico">    
    <title><?= $settings['site_title'] ?? 'PetCare Pro' ?></title>
</head>
<body>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#home"><img src="media\icon.png" alt="" srcset=""></a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#home">Home</a>
                    </li>                    
                    <li class="nav-item">
                        <a class="nav-link" href="#services">Services</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#testimonials">Testimonials</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Reservation</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="history.php">History</a>
                    </li>
                    
                    <!-- Tampilkan nama user yang sedang login -->
                    <li class="nav-item">
                        <a class="nav-link" href="logout_user.php">Halo, <?= htmlspecialchars($user_username); ?>!</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main>
        
        <!-- Home Section -->
        <section id="home">
            <div class="container">
                <div class="row home-content align-items-center">
                    <div class="col-lg-6">
                        <div class="home-text">
                            <h1><?= $settings['site_tagline'] ?? 'Premium Pet Care Services You Can Trust' ?></h1>
                            <p>Book professional boarding, grooming, nutrition consultation, and photography services for your beloved companions. Our experienced team provides quality care that keeps your pets happy, healthy, and looking their absolute best.</p>
                            <div class="home-cta">
                                <a href="#contact" class="btn-home-primary">Book Now</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="home-illustration">
                            <img src="media\hero-min.png" alt="Pet Care Services" srcset="">
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- About Section -->
        <section id="about">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="section-header">
                            <h2>About Our Pet Care Services</h2>
                            <p>Dedicated to providing exceptional care for your beloved pets</p>
                        </div>
                    </div>
                </div>
                <div class="row align-items-center">
                    <div class="col-lg-6">
                        <div class="about-image">
                            <img src="media\about-min.png" alt="Professional pet care services" />
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="about-content">
                            <h2>Why Choose Our Pet Care?</h2>
                            <p>Our trained pets bring unconditional love, joy, and companionship to our lives every single day. They deserve premium nutrition, regular health check-ups, professional grooming, and specialized attention.</p>
                            <p>We provide comprehensive pet care services with experienced professionals who understand that every pet is unique and requires personalized attention to thrive.</p>
                            
                            <div class="about-stats">
                                <div class="stat-item">
                                    <span class="stat-number">500+</span>
                                    <span class="stat-label">Happy Pets</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number">5+</span>
                                    <span class="stat-label">Years Experience</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number">24/7</span>
                                    <span class="stat-label">Support</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Services Section -->
        <?php if($services): ?>
            <section id="services">
                <div class="container">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="section-header">
                                <h2>Our Services</h2>
                                <p>Professional pet care services tailored to your furry friends' needs</p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <?php foreach ($services as $service): ?>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="service-card">
                                    <div class="service-icon">
                                        <?= htmlspecialchars($service->icon) ?>
                                    </div>
                                    <h5><?= htmlspecialchars($service->name) ?></h5>
                                    <p><?= htmlspecialchars($service->description) ?></p>
                                    <div class="service-price">
                                        <strong>Rp<?= number_format($service->price, 0, ',', '.') ?></strong>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach ?>
                    </div>
                </div>
            </section>
        <?php endif ?>

        <!-- Testimonials Section -->
        <?php if ($testimonials): ?>
            <section id="testimonials">
                <div class="container">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="section-header">
                                <h2>What Our Clients Say</h2>
                                <p>Real experiences from happy pet owners</p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <?php foreach ($testimonials as $testimonial): ?>
                            <div class="col-lg-4 mb-4">
                                <div class="testimonial-card">
                                    <div class="testimonial-stars">
                                        <?php for ($i = 1; $i <= $testimonial->star; $i++): ?>
                                            ⭐
                                        <?php endfor; ?>
                                    </div>
                                    <p class="testimonial-text"><?= htmlspecialchars($testimonial->comments) ?></p>
                                    <div class="testimonial-author">
                                        <div class="testimonial-avatar">
                                            <?= strtoupper(substr($testimonial->author, 0, 1)) ?>
                                        </div>
                                        <div class="testimonial-info">
                                            <h6><?= htmlspecialchars($testimonial->author) ?></h6>
                                            <small><?= htmlspecialchars(ucwords($testimonial->pet_type)) ?> Owner</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif ?>

        <!-- Contact Section -->
        <section id="contact">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="section-header">
                            <h2>Schedule an Appointment</h2>
                            <p>Get in touch with us to schedule your appointment</p>
                        </div>
                    </div>
                </div>
                
                <?php if ($success_message): ?>
                    <div class="row justify-content-center mb-4">
                        <div class="col-lg-8">
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?= htmlspecialchars($success_message) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="row justify-content-center mb-4">
                        <div class="col-lg-8">
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?= htmlspecialchars($error_message) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($validation_errors)): ?>
                    <div class="row justify-content-center mb-4">
                        <div class="col-lg-8">
                            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                <strong>Please fix the following errors:</strong>
                                <ul class="mb-0 mt-2">
                                    <?php foreach ($validation_errors as $error): ?>
                                        <li><?= htmlspecialchars($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <form method="POST">
                            <!-- CSRF Protection -->
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="firstName" class="form-label">First Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="firstName" name="firstName" 
                                           value="<?= htmlspecialchars($form_data['firstName'] ?? '') ?>" 
                                           required maxlength="50" pattern="[A-Za-z\s]{2,}">
                                    <div class="invalid-feedback">Please enter a valid first name (minimum 2 characters).</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="lastName" class="form-label">Last Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="lastName" name="lastName" 
                                           value="<?= htmlspecialchars($form_data['lastName'] ?? '') ?>" 
                                           required maxlength="50" pattern="[A-Za-z\s]{2,}">
                                    <div class="invalid-feedback">Please enter a valid last name (minimum 2 characters).</div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?= htmlspecialchars($form_data['email'] ?? '') ?>" 
                                           required maxlength="100">
                                    <div class="invalid-feedback">Please enter a valid email address.</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Phone</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?= htmlspecialchars($form_data['phone'] ?? '') ?>" 
                                           maxlength="20" pattern="[\+]?[0-9\s\-\(\)]{8,}">
                                    <div class="invalid-feedback">Please enter a valid phone number.</div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="appointmentDate" class="form-label">Preferred Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="appointmentDate" name="appointmentDate" 
                                           value="<?= htmlspecialchars($form_data['appointmentDate'] ?? '') ?>" 
                                           required min="<?= date('Y-m-d') ?>">
                                    <div class="invalid-feedback">Please select a valid future date.</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="appointmentTime" class="form-label">Preferred Time <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" id="appointmentTime" name="appointmentTime" 
                                           value="<?= htmlspecialchars($form_data['appointmentTime'] ?? '') ?>" 
                                           required min="08:00" max="18:00">
                                    <div class="invalid-feedback">Please select a time between 8:00 AM and 6:00 PM.</div>
                                    <small class="form-text text-muted">Business hours: 8:00 AM - 6:00 PM</small>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="service" class="form-label">Service Needed <span class="text-danger">*</span></label>
                                <select class="form-control" id="service" name="service" required>
                                    <option value="">Select a service</option>
                                    <?php foreach ($services as $service): ?>
                                        <option value="<?= $service->id ?>" 
                                                <?= isset($form_data['service']) && $form_data['service'] == $service->id ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($service->name) ?> - Rp <?= number_format($service->price, 0, ',', '.') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Please select a service.</div>
                            </div>
                            <div class="mb-3">
                                <label for="message" class="form-label">Additional Message</label>
                                <textarea class="form-control" id="message" name="message" rows="4" 
                                          maxlength="500" placeholder="Tell us about your pet's special needs or any questions you have..."><?= htmlspecialchars($form_data['message'] ?? '') ?></textarea>
                                <small class="form-text text-muted">Maximum 500 characters</small>
                            </div>
                            <div class="text-center">
                                <button type="submit" name="book_appointment" class="btn btn-primary btn-lg">
                                    Schedule Appointment
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="row">
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="footer-brand">
                            <img src="media\icon.png" alt="" srcset="">
                            <h3><?= $settings['site_title'] ?? 'PetCare Pro' ?></h3>
                            <p>Your trusted partner in providing premium pet care services. We're dedicated to keeping your furry friends happy, healthy, and loved.</p>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-6 mb-4">
                        <div class="footer-section">
                            <h5>Services</h5>
                            <ul>
                                <?php foreach (array_slice($services, 0, 5) as $service): ?>
                                    <li><a href="#services"><?= htmlspecialchars($service->name) ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-6 mb-4">
                        <div class="footer-section">
                            <h5>Quick Links</h5>
                            <ul>
                                <li><a href="#home">Home</a></li>
                                <li><a href="#about">About Us</a></li>
                                <li><a href="#services">Services</a></li>
                                <li><a href="#testimonials">Reviews</a></li>
                                <li><a href="#contact">Contact</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="footer-section">
                            <h5>Contact Info</h5>
                            <ul>
                                <li>📍 <?= $settings['contact_address'] ?? '123 Pet Care Street, City, State 12345' ?></li>
                                <li>📞 <?= $settings['contact_phone'] ?? '(555) 123-4567' ?></li>
                                <li>📧 <?= $settings['contact_email'] ?? 'info@petcarepro.com' ?></li>
                                <li>🕒 <?= $settings['business_hours'] ?? 'Mon-Fri: 8AM-6PM, Sat-Sun: 9AM-4PM' ?></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 <?= $settings['site_title'] ?? null ?> . All rights reserved. | Privacy Policy | Terms of Service</p>
            </div>
        </div>
    </footer>

    <script src="src\bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
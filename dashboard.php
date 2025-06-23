<?php
session_start();
require_once './configs/database.php';

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Handle AJAX requests
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'update_booking_status':
            $result = updateBookingStatus($_POST['booking_id'], $_POST['status']);
            echo json_encode(['success' => $result]);
            exit();
            
        case 'approve_testimonial':
            $result = approveTestimonial($_POST['testimonial_id']);
            echo json_encode(['success' => $result]);
            exit();
            
        case 'delete_testimonial':
            $result = deleteTestimonial($_POST['testimonial_id']);
            echo json_encode(['success' => $result]);
            exit();
            
        case 'add_service':
            $result = addService($_POST);
            echo json_encode(['success' => $result]);
            exit();
            
        case 'update_service':
            $result = updateService($_POST['service_id'], $_POST);
            echo json_encode(['success' => $result]);
            exit();
            
        case 'delete_service':
            $result = deleteService($_POST['service_id']);
            echo json_encode(['success' => $result]);
            exit();
            
        case 'change_password':
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $admin_id = $_SESSION['admin_id'];
            
            $result = changeAdminPassword($admin_id, $current_password, $new_password);
            echo json_encode(['success' => $result['success'], 'message' => $result['message']]);
            exit();
    }
}

// Get data for dashboard
$services = getServices();
$bookings = getBookings();
$testimonials = getAllTestimonials();

// Calculate statistics
$total_bookings = count($bookings);
$pending_bookings = count(array_filter($bookings, function($b) { return $b->status === 'pending'; }));
$approved_testimonials = count(array_filter($testimonials, function($t) { return $t->is_approved == 1; }));
$pending_testimonials = count(array_filter($testimonials, function($t) { return $t->is_approved == 0; }));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Pet Shop</title>
    <link href="src/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="src/fontawsome/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="src/css/style.css">
</head>
<body class="dashboard">
    <!-- Sidebar -->
    <div class="sidebar-overlay" onclick="closeSidebar()"></div>
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h4 class="text-white mb-0">
                <i class="fas fa-paw me-2"></i>
                Pet Shop Admin
            </h4>
            <small class="text-white-50">Welcome, <?= $_SESSION['admin_username']; ?></small>
        </div>  
        <nav class="sidebar-nav">
            <button class="nav-link active w-100 text-start" onclick="showSection('dashboard')">
                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
            </button>
            <button class="nav-link w-100 text-start" onclick="showSection('services-dashboard')">
                <i class="fas fa-concierge-bell me-2"></i> Services
            </button>
            <button class="nav-link w-100 text-start" onclick="showSection('bookings')">
                <i class="fas fa-calendar-alt me-2"></i> Bookings
            </button>
            <button class="nav-link w-100 text-start" onclick="showSection('testimonials')">
                <i class="fas fa-comments me-2"></i> Testimonials
            </button>
            <button class="nav-link w-100 text-start" onclick="showSection('profile')">
                <i class="fas fa-user-cog me-2"></i> Profile Settings
            </button>
            <a href="logout.php" class="nav-link">
                <i class="fas fa-sign-out-alt me-2"></i> Logout
            </a>
        </nav>
    </div>
    <button class="mobile-menu-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Dashboard Section -->
        <div id="dashboard" class="section-content active">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Dashboard Overview</h2>
                <div class="text-muted">
                    <i class="fas fa-calendar me-1"></i>
                    <?= date('F j, Y'); ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-3 mb-4">
                    <div class="stats-card">
                        <div class="d-flex align-items-center">
                            <div class="stats-icon" style="background: linear-gradient(45deg, #28a745, #20c997);">
                                <i class="fas fa-concierge-bell"></i>
                            </div>
                            <div class="ms-3">
                                <h3 class="mb-0"><?= count($services); ?></h3>
                                <p class="text-muted mb-0">Total Services</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="stats-card">
                        <div class="d-flex align-items-center">
                            <div class="stats-icon" style="background: linear-gradient(45deg, #007bff, #6c5ce7);">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="ms-3">
                                <h3 class="mb-0"><?= $total_bookings; ?></h3>
                                <p class="text-muted mb-0">Total Bookings</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="stats-card">
                        <div class="d-flex align-items-center">
                            <div class="stats-icon" style="background: linear-gradient(45deg, #ffc107, #fd7e14);">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="ms-3">
                                <h3 class="mb-0"><?= $pending_bookings; ?></h3>
                                <p class="text-muted mb-0">Pending Bookings</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="stats-card">
                        <div class="d-flex align-items-center">
                            <div class="stats-icon" style="background: linear-gradient(45deg, #e74c3c, #fd79a8);">
                                <i class="fas fa-comments"></i>
                            </div>
                            <div class="ms-3">
                                <h3 class="mb-0"><?= count($testimonials); ?></h3>
                                <p class="text-muted mb-0">Total Reviews</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-container">
                <h5 class="mb-3">Recent Bookings</h5>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Customer</th>
                                <th>Service</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($bookings, 0, 5) as $i => $booking): ?>
                            <tr>
                                <td><?= htmlspecialchars($i+1); ?></td>
                                <td><?= htmlspecialchars($booking->first_name . ' ' . $booking->last_name); ?></td>
                                <td><?= htmlspecialchars($booking->service_name ?? 'N/A'); ?></td>
                                <td><?= date('M j, Y', strtotime($booking->appointment_date)); ?></td>
                                <td>
                                    <span class="badge bg-<?= $booking->status === 'confirmed' ? 'success' : ($booking->status === 'pending' ? 'warning' : 'danger'); ?>">
                                        <?= ucfirst($booking->status); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Services Section -->
        <div id="services-dashboard" class="section-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Services Management</h2>                
            </div>

            <div class="table-container">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th class="text-center">No</th>
                                <th class="text-center">Icon</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Price</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="servicesTable">
                            <?php foreach ($services as $i => $service): ?>
                            <tr data-id="<?= $service->id; ?>">
                                <td  class="text-center"><?= $i+1; ?></td>
                                <td  class="text-center"><?= htmlspecialchars($service->icon); ?></td>
                                <td><?= htmlspecialchars($service->name); ?></td>
                                <td><?= htmlspecialchars(substr($service->description, 0, 50)) . '...'; ?></td>
                                <td>$<?= number_format($service->price, 2); ?></td>
                                <td>
                                    <button class="btn btn-outline-danger btn-action" onclick="deleteService(<?= $service->id; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Bookings Section -->
        <div id="bookings" class="section-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Bookings Management</h2>
                <div class="btn-group">
                    <button class="btn btn-outline-secondary" onclick="filterBookings('all')">All</button>
                    <button class="btn btn-outline-warning" onclick="filterBookings('pending')">Pending</button>
                    <button class="btn btn-outline-success" onclick="filterBookings('confirmed')">Confirmed</button>
                    <button class="btn btn-outline-danger" onclick="filterBookings('cancelled')">Cancelled</button>
                </div>
            </div>

            <div class="table-container">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Customer</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Service</th>
                                <th>Date & Time</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="bookingsTable">
                            <?php foreach ($bookings as $i => $booking): ?>
                            <tr data-status="<?= $booking->status; ?>">
                                <td><?= htmlspecialchars($i+1); ?></td>
                                <td><?= htmlspecialchars($booking->first_name . ' ' . $booking->last_name); ?></td>
                                <td><?= htmlspecialchars($booking->email); ?></td>
                                <td><?= htmlspecialchars($booking->phone); ?></td>
                                <td><?= htmlspecialchars($booking->service_name ?? 'N/A'); ?></td>
                                <td>
                                    <?= date('M j, Y', strtotime($booking->appointment_date)); ?><br>
                                    <small class="text-muted"><?= date('g:i A', strtotime($booking->appointment_time)); ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $booking->status === 'confirmed' ? 'success' : ($booking->status === 'pending' ? 'warning' : 'danger'); ?>">
                                        <?= ucfirst($booking->status); ?>
                                    </span>
                                </td>
                                <td>
                                    <select class="form-select form-select-sm" onchange="updateBookingStatus(<?= $booking->id; ?>, this.value)">
                                        <option value="pending" <?= $booking->status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="confirmed" <?= $booking->status === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                        <option value="cancelled" <?= $booking->status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Testimonials Section -->
        <div id="testimonials" class="section-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Testimonials Management</h2>
                <div class="btn-group">
                    <button class="btn btn-outline-secondary" onclick="filterTestimonials('all')">All</button>
                    <button class="btn btn-outline-warning" onclick="filterTestimonials('pending')">Pending</button>
                    <button class="btn btn-outline-success" onclick="filterTestimonials('approved')">Approved</button>
                </div>
            </div>

            <div class="table-container">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Customer</th>
                                <th>Message</th>
                                <th>Rating</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="testimonialsTable">
                            <?php foreach ($testimonials as $i => $testimonial): ?>
                            <tr data-approved="<?= $testimonial->is_approved; ?>">
                                <td><?= htmlspecialchars($i+1); ?></td>
                                <td><?= htmlspecialchars($testimonial->author); ?></td>
                                <td><?= htmlspecialchars(substr($testimonial->comments, 0, 100)) . '...'; ?></td>
                                <td>
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?= $i <= $testimonial->star ? 'text-warning' : 'text-muted'; ?>"></i>
                                    <?php endfor; ?>
                                </td>
                                <td><?= date('M j, Y', strtotime($testimonial->created_at)); ?></td>
                                <td>
                                    <span class="badge bg-<?= $testimonial->is_approved ? 'success' : 'warning'; ?>">
                                        <?= $testimonial->is_approved ? 'Approved' : 'Pending'; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!$testimonial->is_approved): ?>
                                    <button class="btn btn-outline-success btn-action" onclick="approveTestimonial(<?= $testimonial->id; ?>)">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <?php endif; ?>
                                    <button class="btn btn-outline-danger btn-action" onclick="deleteTestimonial(<?= $testimonial->id; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Profile Settings Section -->
        <div id="profile" class="section-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Profile Settings</h2>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="table-container">
                        <h5 class="mb-3">Change Password</h5>
                        <form id="changePasswordForm">
                            <div class="mb-3">
                                <label for="currentPassword" class="form-label">Current Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="currentPassword" name="current_password" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="newPassword" class="form-label">New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-key"></i></span>
                                    <input type="password" class="form-control" id="newPassword" name="new_password" required minlength="6">
                                </div>
                                <div class="form-text">Password must be at least 6 characters long.</div>
                            </div>
                            <div class="mb-3">
                                <label for="confirmPassword" class="form-label">Confirm New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-key"></i></span>
                                    <input type="password" class="form-control" id="confirmPassword" required minlength="6">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Change Password
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="table-container">
                        <h5 class="mb-3">Account Information</h5>
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Username:</strong></td>
                                <td><?= htmlspecialchars($_SESSION['admin_username']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Account Type:</strong></td>
                                <td>Administrator</td>
                            </tr>
                            <tr>
                                <td><strong>Last Login:</strong></td>
                                <td><?= date('F j, Y g:i A'); ?></td>
                            </tr>
                        </table>
                        
                        <div class="mt-4">
                            <h6>Quick Actions</h6>
                            <a href="register.php" class="btn btn-outline-primary btn-sm me-2" target="_blank">
                                <i class="fas fa-user-plus me-1"></i>Register New Admin
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Service Modal -->
    <div class="modal fade" id="serviceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="serviceModalTitle">Add Service</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="serviceForm">
                        <input type="hidden" id="serviceId" name="service_id">
                        <div class="mb-3">
                            <label for="serviceName" class="form-label">Service Name</label>
                            <input type="text" class="form-control" id="serviceName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="serviceDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="serviceDescription" name="description" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="servicePrice" class="form-label">Price</label>
                            <input type="number" class="form-control" id="servicePrice" name="price" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label for="serviceIcon" class="form-label">Icon</label>
                            <input type="text" class="form-control" id="serviceIcon" name="icon" placeholder="fas fa-dog" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveService()">Save Service</button>
                </div>
            </div>
        </div>
    </div>

    <script src="/src/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        // Global variables
        let services = <?= json_encode($services); ?>;
        let currentServiceId = null;

        // Navigation functions
        function showSection(section) {
            // Hide all sections
            document.querySelectorAll('.section-content').forEach(el => {
                el.classList.remove('active');
            });
            
            // Remove active class from all nav links
            document.querySelectorAll('.nav-link').forEach(el => {
                el.classList.remove('active');
            });
            
            // Show selected section
            document.getElementById(section).classList.add('active');
            
            // Add active class to clicked nav link
            event.target.classList.add('active');
            
            // Store current section in localStorage
            localStorage.setItem('currentSection', section);
        }

        document.addEventListener('DOMContentLoaded', function() {
            const currentSection = localStorage.getItem('currentSection');
            if (currentSection && document.getElementById(currentSection)) {
                // Hide default active section
                document.querySelector('.section-content.active').classList.remove('active');
                document.querySelector('.nav-link.active').classList.remove('active');
                
                // Show stored section
                document.getElementById(currentSection).classList.add('active');
                document.querySelector(`[onclick="showSection('${currentSection}')"]`).classList.add('active');
            }
        });

        function showSectionWithHash(section) {
            // Hide all sections
            document.querySelectorAll('.section-content').forEach(el => {
                el.classList.remove('active');
            });
            
            // Remove active class from all nav links
            document.querySelectorAll('.nav-link').forEach(el => {
                el.classList.remove('active');
            });
            
            // Show selected section
            document.getElementById(section).classList.add('active');
            
            // Add active class to clicked nav link
            document.querySelector(`[onclick="showSectionWithHash('${section}')"]`).classList.add('active');
            
            // Update URL hash
            window.location.hash = section;
        }

        window.addEventListener('hashchange', function() {
            const section = window.location.hash.substring(1);
            if (section && document.getElementById(section)) {
                showSectionWithHash(section);
            }
        });

        // Initialize section from hash on page load
        window.addEventListener('load', function() {
            const section = window.location.hash.substring(1);
            if (section && document.getElementById(section)) {
                showSectionWithHash(section);
            }
        });

        // Service management functions
        function openServiceModal(id = null) {
            currentServiceId = id;
            const modal = document.getElementById('serviceModal');
            const title = document.getElementById('serviceModalTitle');
            const form = document.getElementById('serviceForm');
            
            if (id) {
                const service = services.find(s => s.id == id);
                title.textContent = 'Edit Service';
                document.getElementById('serviceId').value = id;
                document.getElementById('serviceName').value = service.name;
                document.getElementById('serviceDescription').value = service.description;
                document.getElementById('servicePrice').value = service.price;
                document.getElementById('serviceIcon').value = service.icon;
            } else {
                title.textContent = 'Add Service';
                form.reset();
                document.getElementById('serviceId').value = '';
            }
        }

        function saveService() {
            const form = document.getElementById('serviceForm');
            const formData = new FormData(form);
            
            const action = currentServiceId ? 'update_service' : 'add_service';
            formData.append('action', action);
            
            fetch('dashboard.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Instead of location.reload(), update the table dynamically
                    updateServicesTable();
                    // Close modal
                    bootstrap.Modal.getInstance(document.getElementById('serviceModal')).hide();
                    // Show success message
                    showAlert('Service saved successfully!', 'success');
                } else {
                    showAlert('Error saving service', 'danger');
                }
            });
        }

        function updateServicesTable() {
            fetch('dashboard.php?get_services=1')
            .then(response => response.json())
            .then(services => {
                const tbody = document.getElementById('servicesTable');
                tbody.innerHTML = '';
                
                services.forEach(service => {
                    const row = document.createElement('tr');
                    row.dataset.id = service.id;
                    row.innerHTML = `
                        <td><i class="${service.icon} fa-2x text-primary"></i></td>
                        <td>${service.name}</td>
                        <td>${service.description.substring(0, 50)}...</td>
                        <td>$${parseFloat(service.price).toFixed(2)}</td>
                        <td>
                            <button class="btn btn-outline-primary btn-action" onclick="editService(${service.id})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-outline-danger btn-action" onclick="deleteService(${service.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    `;
                    tbody.appendChild(row);
                });
                
                // Update global services array
                window.services = services;
            });
        }

        function editService(id) {
            openServiceModal(id);
            new bootstrap.Modal(document.getElementById('serviceModal')).show();
        }

        function deleteService(id) {
            if (confirm('Are you sure you want to delete this service?')) {
                const formData = new FormData();
                formData.append('action', 'delete_service');
                formData.append('service_id', id);
                
                fetch('dashboard.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the row instead of reloading
                        document.querySelector(`tr[data-id="${id}"]`).remove();
                        showAlert('Service deleted successfully!', 'success');
                    } else {
                        showAlert('Error deleting service', 'danger');
                    }
                });
            }
        }

        // Booking management functions
        function updateBookingStatus(bookingId, status) {
            const formData = new FormData();
            formData.append('action', 'update_booking_status');
            formData.append('booking_id', bookingId);
            formData.append('status', status);
            
            fetch('dashboard.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the badge in the row instead of reloading
                    const row = document.querySelector(`select[onchange*="${bookingId}"]`).closest('tr');
                    const badge = row.querySelector('.badge');
                    badge.className = `badge bg-${status === 'confirmed' ? 'success' : (status === 'pending' ? 'warning' : 'danger')}`;
                    badge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
                    row.dataset.status = status;
                    
                    showAlert('Booking status updated successfully!', 'success');
                } else {
                    showAlert('Error updating booking status', 'danger');
                }
            });
        }

        function filterBookings(status) {
            const rows = document.querySelectorAll('#bookingsTable tr');
            rows.forEach(row => {
                if (status === 'all' || row.dataset.status === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Testimonial management functions
        function approveTestimonial(id) {
            const formData = new FormData();
            formData.append('action', 'approve_testimonial');
            formData.append('testimonial_id', id);
            
            fetch('dashboard.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the row instead of reloading
                    const button = document.querySelector(`button[onclick*="approveTestimonial(${id})"]`);
                    const row = button.closest('tr');
                    const badge = row.querySelector('.badge');
                    badge.className = 'badge bg-success';
                    badge.textContent = 'Approved';
                    row.dataset.approved = '1';
                    button.remove(); // Remove approve button
                    
                    showAlert('Testimonial approved successfully!', 'success');
                } else {
                    showAlert('Error approving testimonial', 'danger');
                }
            });
        }

        function deleteTestimonial(id) {
            if (confirm('Are you sure you want to delete this testimonial?')) {
                const formData = new FormData();
                formData.append('action', 'delete_testimonial');
                formData.append('testimonial_id', id);
                
                fetch('dashboard.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the row instead of reloading
                        const button = document.querySelector(`button[onclick*="deleteTestimonial(${id})"]`);
                        button.closest('tr').remove();
                        
                        showAlert('Testimonial deleted successfully!', 'success');
                    } else {
                        showAlert('Error deleting testimonial', 'danger');
                    }
                });
            }
        }

        function filterTestimonials(status) {
            const rows = document.querySelectorAll('#testimonialsTable tr');
            rows.forEach(row => {
                const approved = row.dataset.approved;
                if (status === 'all' || 
                    (status === 'approved' && approved == '1') || 
                    (status === 'pending' && approved == '0')) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            // Insert at top of main content
            const mainContent = document.querySelector('.main-content');
            mainContent.insertBefore(alertDiv, mainContent.firstChild);
            
            // Auto dismiss after 3 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 3000);
        }

        // Profile management functions
        document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const currentPassword = document.getElementById('currentPassword').value;
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (newPassword !== confirmPassword) {
                showAlert('New passwords do not match!', 'danger');
                return;
            }
            
            if (newPassword.length < 6) {
                showAlert('Password must be at least 6 characters long!', 'danger');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'change_password');
            formData.append('current_password', currentPassword);
            formData.append('new_password', newPassword);
            
            fetch('dashboard.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Password changed successfully!', 'success');
                    document.getElementById('changePasswordForm').reset();
                } else {
                    showAlert(data.message || 'Error changing password', 'danger');
                }
            })
            .catch(error => {
                showAlert('Error changing password', 'danger');
                console.error(error);
            });
        });

        // Mobile sidebar toggle functions
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            
            if (sidebar.classList.contains('show')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        }

        function openSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            
            sidebar.classList.add('show');
            overlay.classList.add('show');
            document.body.style.overflow = 'hidden'; // Prevent background scrolling
        }

        function closeSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
            document.body.style.overflow = ''; // Restore scrolling
        }

        // Close sidebar when clicking on nav links (mobile)
        document.addEventListener('DOMContentLoaded', function() {
            const navLinks = document.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth <= 991.98) {
                        closeSidebar();
                    }
                });
            });
            
            // Close sidebar on window resize if desktop
            window.addEventListener('resize', function() {
                if (window.innerWidth > 991.98) {
                    closeSidebar();
                }
            });
        });
    </script>
</body>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form[method="POST"]');
    const submitButton = form.querySelector('button[type="submit"]');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent default form submission
        
        // Show loading state
        const originalButtonText = submitButton.innerHTML;
        submitButton.innerHTML = 'Scheduling...';
        submitButton.disabled = true;
        
        // Create FormData object
        const formData = new FormData(form);
        formData.append('book_appointment', '1');
        
        // Send AJAX request
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(html => {
            // Parse the response to extract success/error messages
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;
            
            // Look for alert messages in the response
            const successAlert = tempDiv.querySelector('.alert-success');
            const errorAlert = tempDiv.querySelector('.alert-danger');
            
            // Remove any existing alerts
            const existingAlerts = document.querySelectorAll('.alert');
            existingAlerts.forEach(alert => alert.remove());
            
            // Show the appropriate message
            if (successAlert) {
                showMessage(successAlert.textContent.trim(), 'success');
                form.reset(); // Clear the form on success
            } else if (errorAlert) {
                showMessage(errorAlert.textContent.trim(), 'error');
            } else {
                showMessage('An unexpected error occurred. Please try again.', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('Network error. Please check your connection and try again.', 'error');
        })
        .finally(() => {
            // Restore button state
            submitButton.innerHTML = originalButtonText;
            submitButton.disabled = false;
        });
    });
    
    function showMessage(message, type) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const alertHTML = `
            <div class="row justify-content-center mb-4">
                <div class="col-lg-8">
                    <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                </div>
            </div>
        `;
        
        // Insert the alert before the form
        const contactSection = document.getElementById('contact');
        const formContainer = contactSection.querySelector('.row.justify-content-center');
        formContainer.insertAdjacentHTML('beforebegin', alertHTML);
        
        // Scroll to the message
        const newAlert = contactSection.querySelector('.alert');
        newAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
        
        // Auto-hide success messages after 5 seconds
        if (type === 'success') {
            setTimeout(() => {
                if (newAlert && newAlert.parentNode) {
                    newAlert.parentNode.removeChild(newAlert.parentNode);
                }
            }, 5000);
        }
    }
});
</script>
</html>
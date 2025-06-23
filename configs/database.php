<?php
// Database configuration
class Database {
    private $host = 'localhost';
    private $db_name = 'petShops';
    private $username = 'root';
    private $password = '';
    private $conn;

    // Get database connection
    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        
        return $this->conn;
    }
}

// Helper functions
function getServices() {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM services ORDER BY id";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

function getTestimonials() {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM testimonials WHERE is_approved = 1 ORDER BY created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

function getAllTestimonials() {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM testimonials ORDER BY created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

function getBookings() {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT b.*, s.name as service_name 
              FROM bookings b 
              LEFT JOIN services s ON b.service_id = s.id 
              ORDER BY b.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

function getSiteSettings() {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT setting_key, setting_value FROM site_settings";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row->setting_key] = $row->setting_value;
    }
    
    return $settings;
}

function createBooking($data) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "INSERT INTO bookings (first_name, last_name, email, phone, service_id, appointment_date, appointment_time, message, user_id) 
              VALUES (:first_name, :last_name, :email, :phone, :service_id, :appointment_date, :appointment_time, :message, :user_id)";
    
    $stmt = $db->prepare($query);
    
    return $stmt->execute([
        ':first_name' => $data['first_name'],
        ':last_name' => $data['last_name'],
        ':email' => $data['email'],
        ':phone' => $data['phone'],
        ':service_id' => $data['service_id'],
        ':appointment_date' => $data['appointment_date'],
        ':appointment_time' => $data['appointment_time'],
        ':message' => $data['message'],
        ':user_id' => $data['user_id'] ?? null
    ]);
}

function updateBookingStatus($id, $status) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "UPDATE bookings SET status = :status WHERE id = :id";
    $stmt = $db->prepare($query);
    
    return $stmt->execute([
        ':status' => $status,
        ':id' => $id
    ]);
}

function getUserBookingsByUserId($user_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT b.*, s.name as service_name 
              FROM bookings b
              LEFT JOIN services s ON b.service_id = s.id
              WHERE b.user_id = :user_id
              ORDER BY b.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute([':user_id' => $user_id]);
    
    return $stmt->fetchAll();
}


function approveTestimonial($id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "UPDATE testimonials SET is_approved = 1 WHERE id = :id";
    $stmt = $db->prepare($query);
    
    return $stmt->execute([':id' => $id]);
}

function deleteTestimonial($id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "DELETE FROM testimonials WHERE id = :id";
    $stmt = $db->prepare($query);
    
    return $stmt->execute([':id' => $id]);
}

function addService($data) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "INSERT INTO services (name, description, price, icon) VALUES (:name, :description, :price, :icon)";
    $stmt = $db->prepare($query);
    
    return $stmt->execute([
        ':name' => $data['name'],
        ':description' => $data['description'],
        ':price' => $data['price'],
        ':icon' => $data['icon']
    ]);
}

function updateService($id, $data) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "UPDATE services SET name = :name, description = :description, price = :price, icon = :icon WHERE id = :id";
    $stmt = $db->prepare($query);
    
    return $stmt->execute([
        ':name' => $data['name'],
        ':description' => $data['description'],
        ':price' => $data['price'],
        ':icon' => $data['icon'],
        ':id' => $id
    ]);
}

function deleteService($id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "DELETE FROM services WHERE id = :id";
    $stmt = $db->prepare($query);
    
    return $stmt->execute([':id' => $id]);
}

function authenticateAdmin($username, $password) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM admin_users WHERE username = :username";
    $stmt = $db->prepare($query);
    $stmt->execute([':username' => $username]);
    
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user->password_hash)) {
        return $user;
    }
    
    return false;
}

function changeAdminPassword($admin_id, $current_password, $new_password) {
    $database = new Database();
    $db = $database->getConnection();
    
    // First, verify current password
    $query = "SELECT password_hash FROM admin_users WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute([':id' => $admin_id]);
    
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($current_password, $user->password_hash)) {
        return ['success' => false, 'message' => 'Current password is incorrect'];
    }
    
    // Update password
    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $update_query = "UPDATE admin_users SET password_hash = :password_hash WHERE id = :id";
    $update_stmt = $db->prepare($update_query);
    
    $result = $update_stmt->execute([
        ':password_hash' => $new_password_hash,
        ':id' => $admin_id
    ]);
    
    return ['success' => $result, 'message' => $result ? 'Password updated successfully' : 'Failed to update password'];
}

function registerAdmin($username, $password, $email) {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if username already exists
    $check_query = "SELECT id FROM admin_users WHERE username = :username";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute([':username' => $username]);
    
    if ($check_stmt->fetch()) {
        return ['success' => false, 'message' => 'Username already exists'];
    }
    
    // Check if email already exists
    $email_check_query = "SELECT id FROM admin_users WHERE email = :email";
    $email_check_stmt = $db->prepare($email_check_query);
    $email_check_stmt->execute([':email' => $email]);
    
    if ($email_check_stmt->fetch()) {
        return ['success' => false, 'message' => 'Email already exists'];
    }
    
    // Create new admin user
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $insert_query = "INSERT INTO admin_users (username, password_hash, email) VALUES (:username, :password_hash, :email)";
    $insert_stmt = $db->prepare($insert_query);
    
    $result = $insert_stmt->execute([
        ':username' => $username,
        ':password_hash' => $password_hash,
        ':email' => $email
    ]);
    
    return ['success' => $result, 'message' => $result ? 'Admin user created successfully' : 'Failed to create admin user'];
}
?>
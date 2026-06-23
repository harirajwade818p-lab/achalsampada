<?php
require_once __DIR__ . '/../config/database.php';

// Redirect function
function redirect($url) {
    header('Location: ' . BASE_URL . $url);
    exit();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Get current user data
function getCurrentUser() {
    global $conn;
    if (!isLoggedIn()) return null;
    
    $user_id = getCurrentUserId();
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

// Check if user is admin
function isAdmin() {
    $user = getCurrentUser();
    return $user && $user['role'] === 'admin';
}

// Check if user is seller
function isSeller() {
    $user = getCurrentUser();
    return $user && ($user['role'] === 'seller' || $user['role'] === 'agent' || $user['role'] === 'builder');
}

// Check if user is buyer
function isBuyer() {
    $user = getCurrentUser();
    return $user && $user['role'] === 'buyer';
}

// Check if user is agent
function isAgent() {
    $user = getCurrentUser();
    return $user && $user['role'] === 'agent';
}

// Check if user is builder
function isBuilder() {
    $user = getCurrentUser();
    return $user && $user['role'] === 'builder';
}

// Sanitize input
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Generate random token
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

// Generate slug
function generateSlug($string) {
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string)));
    return $slug;
}

// Format price
function formatPrice($price, $currency = 'INR') {
    return number_format($price, 2);
}

// Format date
function formatDate($date, $format = 'd M Y') {
    return date($format, strtotime($date));
}

// Time ago
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    if ($diff < 2592000) return floor($diff / 604800) . ' weeks ago';
    if ($diff < 31536000) return floor($diff / 2592000) . ' months ago';
    return floor($diff / 31536000) . ' years ago';
}

// Upload file
function uploadFile($file, $directory, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf']) {
    $targetDir = BASE_PATH . 'uploads/' . $directory . '/';
    
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $fileName = time() . '_' . basename($file['name']);
    $targetFilePath = $targetDir . $fileName;
    $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    if (!in_array($fileType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }
    
    if ($file['size'] > 5242880) { // 5MB
        return ['success' => false, 'message' => 'File size too large'];
    }
    
    if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
        return ['success' => true, 'fileName' => $fileName, 'filePath' => 'uploads/' . $directory . '/' . $fileName];
    }
    
    return ['success' => false, 'message' => 'File upload failed'];
}

// Delete file
function deleteFile($filePath) {
    $fullPath = BASE_PATH . $filePath;
    if (file_exists($fullPath)) {
        unlink($fullPath);
        return true;
    }
    return false;
}

// Send email
function sendEmail($to, $subject, $message, $headers = '') {
    $defaultHeaders = "From: " . SITE_EMAIL . "\r\n";
    $defaultHeaders .= "MIME-Version: 1.0\r\n";
    $defaultHeaders .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    $headers = $headers ? $headers : $defaultHeaders;
    
    return mail($to, $subject, $message, $headers);
}

// Generate OTP
function generateOTP() {
    return rand(100000, 999999);
}

// Verify OTP
function verifyOTP($userId, $otp) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND email_otp = ? AND otp_expiry > NOW()");
    $stmt->execute([$userId, $otp]);
    
    if ($stmt->fetch()) {
        $stmt = $conn->prepare("UPDATE users SET email_verified = 1, email_otp = NULL, otp_expiry = NULL WHERE id = ?");
        $stmt->execute([$userId]);
        return true;
    }
    
    return false;
}

// Log activity
function logActivity($userId, $action, $module, $description = null) {
    global $conn;
    
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, module, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $action, $module, $description, $ipAddress, $userAgent]);
}

// Get setting value
function getSetting($key) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch();
    
    return $result ? $result['setting_value'] : null;
}

// Set setting value
function setSetting($key, $value) {
    global $conn;
    
    $stmt = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->execute([$key, $value, $value]);
}

// Flash message
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = ['type' => $type, 'message' => $message];
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

// Pagination
function getPagination($total, $perPage, $currentPage) {
    $totalPages = ceil($total / $perPage);
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);
    
    return [
        'total' => $total,
        'perPage' => $perPage,
        'currentPage' => $currentPage,
        'totalPages' => $totalPages,
        'start' => $start,
        'end' => $end,
        'hasNext' => $currentPage < $totalPages,
        'hasPrev' => $currentPage > 1
    ];
}

function getSearchSortOptions() {
    return [
        'newest' => 'Newest First',
        'oldest' => 'Oldest First',
        'price_low' => 'Price: Low to High',
        'price_high' => 'Price: High to Low',
        'area_low' => 'Area: Low to High',
        'area_high' => 'Area: High to Low',
    ];
}

function buildSearchUrl(array $overrides = [], array $remove = []) {
    $query = array_merge($_GET, $overrides);

    foreach ($remove as $key) {
        unset($query[$key]);
    }

    return BASE_URL . 'search.php' . ($query ? '?' . http_build_query($query) : '');
}

// Check if property is in wishlist
function isInWishlist($propertyId) {
    global $conn;
    
    if (!isLoggedIn()) return false;
    
    $userId = getCurrentUserId();
    $stmt = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND property_id = ?");
    $stmt->execute([$userId, $propertyId]);
    
    return $stmt->fetch() ? true : false;
}

// Add to wishlist
function addToWishlist($propertyId) {
    global $conn;
    
    if (!isLoggedIn()) return false;
    
    $userId = getCurrentUserId();
    
    try {
        $stmt = $conn->prepare("INSERT INTO wishlist (user_id, property_id) VALUES (?, ?)");
        $stmt->execute([$userId, $propertyId]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// Remove from wishlist
function removeFromWishlist($propertyId) {
    global $conn;
    
    if (!isLoggedIn()) return false;
    
    $userId = getCurrentUserId();
    $stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND property_id = ?");
    $stmt->execute([$userId, $propertyId]);
    
    return $stmt->rowCount() > 0;
}

// Increment property view count
function incrementPropertyView($propertyId) {
    global $conn;
    
    $userId = isLoggedIn() ? getCurrentUserId() : null;
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    // Log view
    $stmt = $conn->prepare("INSERT INTO property_views (property_id, user_id, ip_address, user_agent) VALUES (?, ?, ?, ?)");
    $stmt->execute([$propertyId, $userId, $ipAddress, $userAgent]);
    
    // Increment view count
    $stmt = $conn->prepare("UPDATE properties SET view_count = view_count + 1 WHERE id = ?");
    $stmt->execute([$propertyId]);
    
    // Add to recently viewed if logged in
    if ($userId) {
        $stmt = $conn->prepare("INSERT INTO recently_viewed (user_id, property_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE viewed_at = CURRENT_TIMESTAMP");
        $stmt->execute([$userId, $propertyId]);
    }
}

// Calculate distance between two points (Haversine formula)
function calculateDistance($lat1, $lon1, $lat2, $lon2, $unit = 'km') {
    $earthRadius = $unit === 'km' ? 6371 : 3959;
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    $distance = $earthRadius * $c;
    
    return round($distance, 2);
}

// Get nearby properties
function getNearbyProperties($lat, $lon, $radius = 10, $limit = 10) {
    global $conn;
    
    $query = "SELECT *, 
              (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * 
              cos(radians(longitude) - radians(?)) + 
              sin(radians(?)) * sin(radians(latitude)))) AS distance
              FROM properties 
              WHERE latitude IS NOT NULL AND longitude IS NOT NULL
              HAVING distance < ?
              ORDER BY distance
              LIMIT ?";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$lat, $lon, $lat, $radius, $limit]);
    
    return $stmt->fetchAll();
}
?>

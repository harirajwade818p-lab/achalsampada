<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'property_marketplace');

// Base URL Configuration
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$path = dirname($_SERVER['PHP_SELF']);

define('BASE_URL', $protocol . $host . $path . '/');
define('BASE_PATH', __DIR__ . '/../');

// Site Configuration
define('SITE_NAME', 'Property Marketplace');
define('SITE_EMAIL', 'noreply@propertymarketplace.com');
define('ADMIN_EMAIL', 'admin@propertymarketplace.com');

// Create database connection
try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>

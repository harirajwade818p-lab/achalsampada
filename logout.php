<?php
require_once __DIR__ . '/config/database.php';
require_once BASE_PATH . 'includes/functions.php';
require_once BASE_PATH . 'includes/auth.php';

session_start();

logoutUser();

redirect('index.php');
?>

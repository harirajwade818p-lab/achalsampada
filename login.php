<?php
require_once __DIR__ . '/config/database.php';
require_once BASE_PATH . 'includes/functions.php';
require_once BASE_PATH . 'includes/auth.php';

session_start();

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter email and password';
    } else {
        $result = loginUser($email, $password);
        if ($result['success']) {
            if ($remember) {
                setcookie('user_email', $email, time() + (30 * 24 * 60 * 60), '/');
            }
            
            // Redirect based on role
            switch ($result['user']['role']) {
                case 'admin':
                    redirect('admin/dashboard.php');
                case 'agent':
                    redirect('agent/dashboard.php');
                case 'builder':
                    redirect('builder/dashboard.php');
                default:
                    redirect('dashboard.php');
            }
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
</head>
<body class="auth-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">
                <div class="auth-card">
                    <div class="text-center mb-4">
                        <h2>Welcome Back</h2>
                        <p class="text-muted">Login to your account</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" required value="<?php echo $_COOKIE['user_email'] ?? ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>

                        <div class="mb-3 d-flex justify-content-between">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label" for="remember">Remember me</label>
                            </div>
                            <a href="<?php echo BASE_URL; ?>forgot-password.php" class="text-decoration-none">Forgot Password?</a>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>

                    <div class="text-center mt-3">
                        <p>Don't have an account? <a href="<?php echo BASE_URL; ?>register.php">Register here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

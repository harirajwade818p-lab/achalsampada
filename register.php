<?php
require_once __DIR__ . '/config/database.php';
require_once BASE_PATH . 'includes/functions.php';
require_once BASE_PATH . 'includes/auth.php';

session_start();

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'email' => sanitize($_POST['email']),
        'password' => $_POST['password'],
        'first_name' => sanitize($_POST['first_name']),
        'last_name' => sanitize($_POST['last_name']),
        'phone' => sanitize($_POST['phone'] ?? ''),
        'role' => sanitize($_POST['role'] ?? 'buyer')
    ];
    
    // Validation
    if (empty($data['email']) || empty($data['password']) || empty($data['first_name']) || empty($data['last_name'])) {
        $error = 'Please fill all required fields';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } elseif (strlen($data['password']) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        $result = registerUser($data);
        if ($result['success']) {
            $success = $result['message'];
            $_SESSION['temp_user_id'] = $result['user_id'];
            redirect('verify-email.php');
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
    <title>Register - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
</head>
<body class="auth-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="auth-card">
                    <div class="text-center mb-4">
                        <h2>Create Account</h2>
                        <p class="text-muted">Join <?php echo SITE_NAME; ?> today</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">I want to</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="role" value="buyer" id="buyer" checked>
                                <label class="btn btn-outline-primary" for="buyer">Buy Property</label>
                                
                                <input type="radio" class="btn-check" name="role" value="seller" id="seller">
                                <label class="btn btn-outline-primary" for="seller">Sell Property</label>
                                
                                <input type="radio" class="btn-check" name="role" value="agent" id="agent">
                                <label class="btn btn-outline-primary" for="agent">Agent</label>
                                
                                <input type="radio" class="btn-check" name="role" value="builder" id="builder">
                                <label class="btn btn-outline-primary" for="builder">Builder</label>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name *</label>
                                <input type="text" name="first_name" class="form-control" required value="<?php echo $_POST['first_name'] ?? ''; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name *</label>
                                <input type="text" name="last_name" class="form-control" required value="<?php echo $_POST['last_name'] ?? ''; ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email Address *</label>
                            <input type="email" name="email" class="form-control" required value="<?php echo $_POST['email'] ?? ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" name="phone" class="form-control" value="<?php echo $_POST['phone'] ?? ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password *</label>
                            <input type="password" name="password" class="form-control" required minlength="6">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Confirm Password *</label>
                            <input type="password" name="confirm_password" class="form-control" required minlength="6">
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="terms" required>
                            <label class="form-check-label" for="terms">
                                I agree to the <a href="#">Terms & Conditions</a> and <a href="#">Privacy Policy</a>
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Register</button>
                    </form>

                    <div class="text-center mt-3">
                        <p>Already have an account? <a href="<?php echo BASE_URL; ?>login.php">Login here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.querySelector('input[name="password"]').value;
            const confirmPassword = document.querySelector('input[name="confirm_password"]').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match');
            }
        });
    </script>
</body>
</html>

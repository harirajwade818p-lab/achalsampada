<?php
require_once __DIR__ . '/config/database.php';
require_once BASE_PATH . 'includes/functions.php';
require_once BASE_PATH . 'includes/auth.php';

session_start();

$error = '';
$success = '';

if (!isset($_SESSION['temp_user_id'])) {
    redirect('login.php');
}

$userId = $_SESSION['temp_user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = sanitize($_POST['otp']);
    
    if (empty($otp)) {
        $error = 'Please enter OTP';
    } elseif (verifyOTP($userId, $otp)) {
        unset($_SESSION['temp_user_id']);
        $success = 'Email verified successfully';
        
        // Auto login
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['user_role'] = $user['role'];
        
        redirect('dashboard.php');
    } else {
        $error = 'Invalid or expired OTP';
    }
}

// Resend OTP
if (isset($_GET['resend'])) {
    $stmt = $conn->prepare("SELECT email, first_name FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if ($user) {
        $otp = generateOTP();
        $otpExpiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        
        $stmt = $conn->prepare("UPDATE users SET email_otp = ?, otp_expiry = ? WHERE id = ?");
        $stmt->execute([$otp, $otpExpiry, $userId]);
        
        $subject = 'Verify Your Email - ' . SITE_NAME;
        $message = "
            <html>
            <head>
                <title>Email Verification</title>
            </head>
            <body>
                <h2>Email Verification</h2>
                <p>Your OTP is: <strong>$otp</strong></p>
                <p>This OTP will expire in 15 minutes.</p>
            </body>
            </html>
        ";
        
        sendEmail($user['email'], $subject, $message);
        
        $success = 'OTP sent to your email';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - <?php echo SITE_NAME; ?></title>
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
                        <i class="fas fa-envelope fa-3x text-primary mb-3"></i>
                        <h2>Verify Your Email</h2>
                        <p class="text-muted">Enter the OTP sent to your email</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Enter OTP</label>
                            <input type="text" name="otp" class="form-control text-center" required maxlength="6" pattern="[0-9]{6}" placeholder="123456">
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Verify</button>
                    </form>

                    <div class="text-center mt-3">
                        <p>Didn't receive OTP? <a href="?resend=1" class="text-decoration-none">Resend OTP</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

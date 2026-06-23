<?php
require_once __DIR__ . '/config/database.php';
require_once BASE_PATH . 'includes/functions.php';
require_once BASE_PATH . 'includes/auth.php';

session_start();

if (!isLoggedIn()) {
    redirect('login.php');
}

$pageTitle = 'KYC Verification';
$user = getCurrentUser();

if ($user['kyc_verified'] === 'approved') {
    redirect('profile.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'aadhaar_number' => sanitize($_POST['aadhaar_number']),
        'pan_number' => sanitize($_POST['pan_number'])
    ];
    
    $files = [
        'aadhaar_document' => $_FILES['aadhaar_document'] ?? null,
        'pan_document' => $_FILES['pan_document'] ?? null
    ];
    
    if (empty($data['aadhaar_number']) && empty($data['pan_number'])) {
        $error = 'Please provide at least one document number';
    } else {
        $result = submitKYC($user['id'], $data, $files);
        if ($result['success']) {
            $success = $result['message'];
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
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
</head>
<body>
    <?php include BASE_PATH . 'includes/header.php'; ?>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">KYC Verification</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($user['kyc_verified'] === 'pending'): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-clock"></i> Your KYC documents are under review. We will notify you once verified.
                            </div>
                        <?php elseif ($user['kyc_verified'] === 'rejected'): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-times-circle"></i> Your KYC was rejected. Reason: <?php echo $user['kyc_rejection_reason'] ?: 'Not specified'; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Complete your KYC verification to unlock all features and build trust with buyers.
                            </div>
                        <?php endif; ?>

                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>

                        <?php if ($user['kyc_verified'] !== 'pending'): ?>
                            <form method="POST" enctype="multipart/form-data">
                                <div class="mb-4">
                                    <h6>Aadhaar Card</h6>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Aadhaar Number</label>
                                            <input type="text" name="aadhaar_number" class="form-control" pattern="[0-9]{12}" placeholder="12-digit Aadhaar number">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Upload Document</label>
                                            <input type="file" name="aadhaar_document" class="form-control" accept="image/*,.pdf">
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <h6>PAN Card</h6>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">PAN Number</label>
                                            <input type="text" name="pan_number" class="form-control" pattern="[A-Z]{5}[0-9]{4}[A-Z]{1}" placeholder="10-character PAN number">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Upload Document</label>
                                            <input type="file" name="pan_document" class="form-control" accept="image/*,.pdf">
                                        </div>
                                    </div>
                                </div>

                                <div class="alert alert-warning">
                                    <small>
                                        <i class="fas fa-exclamation-triangle"></i> 
                                        Please ensure that the documents are clear and readable. Accepted formats: JPG, PNG, PDF (Max 5MB)
                                    </small>
                                </div>

                                <button type="submit" class="btn btn-primary">Submit KYC Documents</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include BASE_PATH . 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
require_once __DIR__ . '/config/database.php';
require_once BASE_PATH . 'includes/functions.php';
require_once BASE_PATH . 'includes/auth.php';

session_start();

if (!isLoggedIn()) {
    redirect('login.php');
}

$pageTitle = 'My Profile';
$user = getCurrentUser();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $data = [
            'first_name' => sanitize($_POST['first_name']),
            'last_name' => sanitize($_POST['last_name']),
            'phone' => sanitize($_POST['phone']),
            'date_of_birth' => $_POST['date_of_birth'] ?: null,
            'gender' => $_POST['gender'] ?: null,
            'address' => sanitize($_POST['address']),
            'city' => sanitize($_POST['city']),
            'state' => sanitize($_POST['state']),
            'country' => sanitize($_POST['country']),
            'pincode' => sanitize($_POST['pincode'])
        ];
        
        $result = updateProfile($user['id'], $data);
        if ($result['success']) {
            $success = $result['message'];
            $user = getCurrentUser(); // Refresh user data
        } else {
            $error = $result['message'];
        }
    }
    
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
        $result = updateProfileImage($user['id'], $_FILES['profile_image']);
        if ($result['success']) {
            $success = 'Profile image updated successfully';
            $user = getCurrentUser(); // Refresh user data
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
        <h2 class="mb-4">My Profile</h2>

        <div class="row">
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <div class="position-relative d-inline-block">
                            <img src="<?php echo $user['profile_image'] ? BASE_URL . $user['profile_image'] : BASE_URL . 'assets/images/default-avatar.png'; ?>" 
                                 class="rounded-circle mb-3" width="150" height="150" alt="Profile" id="profileImagePreview">
                            <label class="btn btn-sm btn-primary position-absolute bottom-0 end-0" for="profileImageInput">
                                <i class="fas fa-camera"></i>
                            </label>
                            <input type="file" id="profileImageInput" class="d-none" accept="image/*" onchange="document.getElementById('profileForm').submit()">
                        </div>
                        <h4><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></h4>
                        <p class="text-muted"><?php echo ucfirst($user['role']); ?></p>
                        <p class="text-muted"><?php echo $user['email']; ?></p>
                        
                        <div class="mt-3">
                            <?php if (!$user['email_verified']): ?>
                                <span class="badge bg-warning">Email Not Verified</span>
                            <?php else: ?>
                                <span class="badge bg-success">Email Verified</span>
                            <?php endif; ?>
                            
                            <?php if ($user['kyc_verified'] === 'approved'): ?>
                                <span class="badge bg-success">KYC Verified</span>
                            <?php elseif ($user['kyc_verified'] === 'pending'): ?>
                                <span class="badge bg-warning">KYC Pending</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">KYC Not Submitted</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title mb-3">Account Info</h6>
                        <p><strong>Member Since:</strong> <?php echo formatDate($user['created_at']); ?></p>
                        <p><strong>Last Login:</strong> <?php echo $user['last_login'] ? formatDate($user['last_login'], 'd M Y H:i') : 'Never'; ?></p>
                        <p><strong>Account Status:</strong> <?php echo ucfirst($user['account_status']); ?></p>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Edit Profile</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>

                        <form method="POST" id="profileForm" enctype="multipart/form-data">
                            <input type="hidden" name="update_profile" value="1">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">First Name</label>
                                    <input type="text" name="first_name" class="form-control" required value="<?php echo $user['first_name']; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Last Name</label>
                                    <input type="text" name="last_name" class="form-control" required value="<?php echo $user['last_name']; ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" value="<?php echo $user['email']; ?>" disabled>
                                <small class="text-muted">Email cannot be changed</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="tel" name="phone" class="form-control" value="<?php echo $user['phone']; ?>">
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Date of Birth</label>
                                    <input type="date" name="date_of_birth" class="form-control" value="<?php echo $user['date_of_birth']; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Gender</label>
                                    <select name="gender" class="form-select">
                                        <option value="">Select</option>
                                        <option value="male" <?php echo $user['gender'] === 'male' ? 'selected' : ''; ?>>Male</option>
                                        <option value="female" <?php echo $user['gender'] === 'female' ? 'selected' : ''; ?>>Female</option>
                                        <option value="other" <?php echo $user['gender'] === 'other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" rows="2"><?php echo $user['address']; ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">City</label>
                                    <input type="text" name="city" class="form-control" value="<?php echo $user['city']; ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">State</label>
                                    <input type="text" name="state" class="form-control" value="<?php echo $user['state']; ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Pincode</label>
                                    <input type="text" name="pincode" class="form-control" value="<?php echo $user['pincode']; ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Country</label>
                                <input type="text" name="country" class="form-control" value="<?php echo $user['country']; ?>">
                            </div>

                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </form>
                    </div>
                </div>

                <!-- KYC Section -->
                <?php if ($user['kyc_verified'] !== 'approved'): ?>
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0">KYC Verification</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($user['kyc_verified'] === 'pending'): ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-clock"></i> Your KYC documents are under review.
                                </div>
                            <?php else: ?>
                                <p class="text-muted">Complete your KYC verification to unlock all features.</p>
                                <a href="<?php echo BASE_URL; ?>kyc.php" class="btn btn-primary">Submit KYC Documents</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Change Password -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Change Password</h5>
                    </div>
                    <div class="card-body">
                        <a href="<?php echo BASE_URL; ?>change-password.php" class="btn btn-outline-primary">Change Password</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include BASE_PATH . 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

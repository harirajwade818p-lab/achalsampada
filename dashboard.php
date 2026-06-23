<?php
require_once __DIR__ . '/config/database.php';
require_once BASE_PATH . 'includes/functions.php';

session_start();

if (!isLoggedIn()) {
    redirect('login.php');
}

$user = getCurrentUser();
$pageTitle = 'Dashboard';

// Get user statistics
$stats = [];

if (isSeller()) {
    // Get property stats
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM properties WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $stats['total_properties'] = $stmt->fetch()['total'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM properties WHERE user_id = ? AND approval_status = 'pending'");
    $stmt->execute([$user['id']]);
    $stats['pending_properties'] = $stmt->fetch()['total'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM properties WHERE user_id = ? AND approval_status = 'approved'");
    $stmt->execute([$user['id']]);
    $stats['approved_properties'] = $stmt->fetch()['total'];
    
    $stmt = $conn->prepare("SELECT SUM(view_count) as total FROM properties WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $stats['total_views'] = $stmt->fetch()['total'] ?? 0;
}

if (isBuyer()) {
    // Get wishlist count
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM wishlist WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $stats['wishlist_count'] = $stmt->fetch()['total'];
    
    // Get inquiry count
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM inquiries WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $stats['inquiry_count'] = $stmt->fetch()['total'];
}

// Get recent activities
$stmt = $conn->prepare("SELECT * FROM activity_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$user['id']]);
$recentActivities = $stmt->fetchAll();
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
        <div class="row">
            <div class="col-md-3">
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <img src="<?php echo $user['profile_image'] ? BASE_URL . $user['profile_image'] : BASE_URL . 'assets/images/default-avatar.png'; ?>" 
                             class="rounded-circle mb-3" width="100" height="100" alt="Profile">
                        <h5><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></h5>
                        <p class="text-muted"><?php echo ucfirst($user['role']); ?></p>
                        <p class="text-muted"><?php echo $user['email']; ?></p>
                        <?php if (!$user['email_verified']): ?>
                            <span class="badge bg-warning">Email Not Verified</span>
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
                
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title mb-3">Quick Links</h6>
                        <div class="list-group list-group-flush">
                            <a href="<?php echo BASE_URL; ?>profile.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-user me-2"></i> My Profile
                            </a>
                            <a href="<?php echo BASE_URL; ?>wishlist.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-heart me-2"></i> Wishlist
                            </a>
                            <?php if (isSeller()): ?>
                                <a href="<?php echo BASE_URL; ?>properties/my-properties.php" class="list-group-item list-group-item-action">
                                    <i class="fas fa-building me-2"></i> My Properties
                                </a>
                                <a href="<?php echo BASE_URL; ?>properties/add.php" class="list-group-item list-group-item-action">
                                    <i class="fas fa-plus me-2"></i> Add Property
                                </a>
                            <?php endif; ?>
                            <?php if (isAgent()): ?>
                                <a href="<?php echo BASE_URL; ?>agent/dashboard.php" class="list-group-item list-group-item-action">
                                    <i class="fas fa-chart-line me-2"></i> Agent Dashboard
                                </a>
                            <?php endif; ?>
                            <?php if (isBuilder()): ?>
                                <a href="<?php echo BASE_URL; ?>builder/dashboard.php" class="list-group-item list-group-item-action">
                                    <i class="fas fa-hard-hat me-2"></i> Builder Dashboard
                                </a>
                            <?php endif; ?>
                            <a href="<?php echo BASE_URL; ?>change-password.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-lock me-2"></i> Change Password
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-9">
                <h2 class="mb-4">Welcome, <?php echo $user['first_name']; ?>!</h2>
                
                <?php if (isSeller()): ?>
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5><?php echo $stats['total_properties']; ?></h5>
                                    <small>Total Properties</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-dark">
                                <div class="card-body">
                                    <h5><?php echo $stats['pending_properties']; ?></h5>
                                    <small>Pending Approval</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h5><?php echo $stats['approved_properties']; ?></h5>
                                    <small>Approved</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h5><?php echo $stats['total_views']; ?></h5>
                                    <small>Total Views</small>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (isBuyer()): ?>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5><?php echo $stats['wishlist_count']; ?></h5>
                                    <small>Saved Properties</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h5><?php echo $stats['inquiry_count']; ?></h5>
                                    <small>Inquiries Sent</small>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Activities</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentActivities)): ?>
                            <p class="text-muted">No recent activities</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Action</th>
                                            <th>Module</th>
                                            <th>Description</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentActivities as $activity): ?>
                                            <tr>
                                                <td><?php echo ucfirst($activity['action']); ?></td>
                                                <td><?php echo ucfirst($activity['module']); ?></td>
                                                <td><?php echo $activity['description'] ?? '-'; ?></td>
                                                <td><?php echo timeAgo($activity['created_at']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
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

<?php
require_once __DIR__ . '/../config/database.php';
require_once BASE_PATH . 'includes/functions.php';

session_start();

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

$pageTitle = 'Admin Dashboard';

// Get statistics
$stats = [];

// Total users
$stmt = $conn->query("SELECT COUNT(*) as total FROM users");
$stats['total_users'] = $stmt->fetch()['total'];

// Total properties
$stmt = $conn->query("SELECT COUNT(*) as total FROM properties");
$stats['total_properties'] = $stmt->fetch()['total'];

// Pending properties
$stmt = $conn->query("SELECT COUNT(*) as total FROM properties WHERE approval_status = 'pending'");
$stats['pending_properties'] = $stmt->fetch()['total'];

// Pending KYC
$stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE kyc_verified = 'pending'");
$stats['pending_kyc'] = $stmt->fetch()['total'];

// Total inquiries
$stmt = $conn->query("SELECT COUNT(*) as total FROM inquiries");
$stats['total_inquiries'] = $stmt->fetch()['total'];

// Pending inquiries
$stmt = $conn->query("SELECT COUNT(*) as total FROM inquiries WHERE status = 'pending'");
$stats['pending_inquiries'] = $stmt->fetch()['total'];

// Total reviews
$stmt = $conn->query("SELECT COUNT(*) as total FROM reviews");
$stats['total_reviews'] = $stmt->fetch()['total'];

// Pending reviews
$stmt = $conn->query("SELECT COUNT(*) as total FROM reviews WHERE status = 'pending'");
$stats['pending_reviews'] = $stmt->fetch()['total'];

// Get recent properties
$stmt = $conn->query("
    SELECT p.*, u.first_name, u.last_name,
           (SELECT image_path FROM property_images WHERE property_id = p.id AND is_cover = 1 LIMIT 1) as cover_image
    FROM properties p
    LEFT JOIN users u ON p.user_id = u.id
    ORDER BY p.created_at DESC
    LIMIT 5
");
$recentProperties = $stmt->fetchAll();

// Get recent users
$stmt = $conn->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
$recentUsers = $stmt->fetchAll();

// Get recent inquiries
$stmt = $conn->query("
    SELECT i.*, p.title as property_title, u.first_name, u.last_name
    FROM inquiries i
    LEFT JOIN properties p ON i.property_id = p.id
    LEFT JOIN users u ON i.user_id = u.id
    ORDER BY i.created_at DESC
    LIMIT 5
");
$recentInquiries = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/admin.css">
</head>
<body>
    <div class="d-flex">
        <?php include BASE_PATH . 'admin/includes/sidebar.php'; ?>

        <div class="flex-grow-1">
            <?php include BASE_PATH . 'admin/includes/header.php'; ?>

            <div class="container-fluid py-4">
                <h2 class="mb-4">Dashboard Overview</h2>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card bg-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white mb-0">Total Users</h6>
                                        <h3 class="text-white mb-0"><?php echo $stats['total_users']; ?></h3>
                                    </div>
                                    <i class="fas fa-users fa-2x text-white opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card bg-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white mb-0">Total Properties</h6>
                                        <h3 class="text-white mb-0"><?php echo $stats['total_properties']; ?></h3>
                                    </div>
                                    <i class="fas fa-building fa-2x text-white opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card bg-warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white mb-0">Pending Approvals</h6>
                                        <h3 class="text-white mb-0"><?php echo $stats['pending_properties']; ?></h3>
                                    </div>
                                    <i class="fas fa-clock fa-2x text-white opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card bg-info">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white mb-0">Total Inquiries</h6>
                                        <h3 class="text-white mb-0"><?php echo $stats['total_inquiries']; ?></h3>
                                    </div>
                                    <i class="fas fa-envelope fa-2x text-white opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Secondary Stats -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h6>Pending KYC</h6>
                                <h3><?php echo $stats['pending_kyc']; ?></h3>
                                <a href="<?php echo BASE_URL; ?>admin/users.php?kyc=pending" class="btn btn-sm btn-primary mt-2">View</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h6>Pending Inquiries</h6>
                                <h3><?php echo $stats['pending_inquiries']; ?></h3>
                                <a href="<?php echo BASE_URL; ?>admin/inquiries.php?status=pending" class="btn btn-sm btn-primary mt-2">View</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h6>Pending Reviews</h6>
                                <h3><?php echo $stats['pending_reviews']; ?></h3>
                                <a href="<?php echo BASE_URL; ?>admin/reviews.php?status=pending" class="btn btn-sm btn-primary mt-2">View</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h6>Total Reviews</h6>
                                <h3><?php echo $stats['total_reviews']; ?></h3>
                                <a href="<?php echo BASE_URL; ?>admin/reviews.php" class="btn btn-sm btn-primary mt-2">View</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Recent Properties -->
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Recent Properties</h5>
                                <a href="<?php echo BASE_URL; ?>admin/properties.php" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Property</th>
                                                <th>Owner</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentProperties as $property): ?>
                                                <tr>
                                                    <td>
                                                        <a href="<?php echo BASE_URL; ?>property/<?php echo $property['slug']; ?>" target="_blank">
                                                            <?php echo substr($property['title'], 0, 25); ?>...
                                                        </a>
                                                    </td>
                                                    <td><?php echo $property['first_name'] . ' ' . $property['last_name']; ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $property['approval_status'] === 'approved' ? 'success' : ($property['approval_status'] === 'rejected' ? 'danger' : 'warning'); ?>">
                                                            <?php echo ucfirst($property['approval_status']); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Users -->
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Recent Users</h5>
                                <a href="<?php echo BASE_URL; ?>admin/users.php" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>User</th>
                                                <th>Role</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentUsers as $user): ?>
                                                <tr>
                                                    <td>
                                                        <?php echo $user['first_name'] . ' ' . $user['last_name']; ?>
                                                        <br><small class="text-muted"><?php echo $user['email']; ?></small>
                                                    </td>
                                                    <td><?php echo ucfirst($user['role']); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $user['account_status'] === 'active' ? 'success' : 'danger'; ?>">
                                                            <?php echo ucfirst($user['account_status']); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Inquiries -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Recent Inquiries</h5>
                                <a href="<?php echo BASE_URL; ?>admin/inquiries.php" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Property</th>
                                                <th>User</th>
                                                <th>Email</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentInquiries as $inquiry): ?>
                                                <tr>
                                                    <td><?php echo substr($inquiry['property_title'] ?? 'N/A', 0, 30); ?>...</td>
                                                    <td><?php echo $inquiry['first_name'] . ' ' . $inquiry['last_name']; ?></td>
                                                    <td><?php echo $inquiry['email']; ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $inquiry['status'] === 'closed' ? 'success' : ($inquiry['status'] === 'pending' ? 'warning' : 'info'); ?>">
                                                            <?php echo ucfirst($inquiry['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo formatDate($inquiry['created_at']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

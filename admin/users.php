<?php
require_once __DIR__ . '/../config/database.php';
require_once BASE_PATH . 'includes/functions.php';

session_start();

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

$pageTitle = 'Manage Users';

// Get filters
$role = $_GET['role'] ?? '';
$status = $_GET['status'] ?? '';
$kyc = $_GET['kyc'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$where = ["1=1"];
$params = [];

if ($role) {
    $where[] = "role = ?";
    $params[] = $role;
}

if ($status) {
    $where[] = "account_status = ?";
    $params[] = $status;
}

if ($kyc) {
    $where[] = "kyc_verified = ?";
    $params[] = $kyc;
}

if ($search) {
    $where[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
}

$whereClause = implode(' AND ', $where);

// Get users
$stmt = $conn->prepare("SELECT * FROM users WHERE $whereClause ORDER BY created_at DESC");
$stmt->execute($params);
$users = $stmt->fetchAll();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $userId = $_POST['user_id'];
    
    if ($action === 'activate') {
        $stmt = $conn->prepare("UPDATE users SET account_status = 'active' WHERE id = ?");
        $stmt->execute([$userId]);
    } elseif ($action === 'suspend') {
        $stmt = $conn->prepare("UPDATE users SET account_status = 'suspended' WHERE id = ?");
        $stmt->execute([$userId]);
    } elseif ($action === 'delete') {
        $stmt = $conn->prepare("UPDATE users SET account_status = 'deleted' WHERE id = ?");
        $stmt->execute([$userId]);
    } elseif ($action === 'verify_kyc') {
        $stmt = $conn->prepare("UPDATE users SET kyc_verified = 'approved', verification_badge = 'verified_owner' WHERE id = ?");
        $stmt->execute([$userId]);
    } elseif ($action === 'reject_kyc') {
        $reason = sanitize($_POST['rejection_reason']);
        $stmt = $conn->prepare("UPDATE users SET kyc_verified = 'rejected', kyc_rejection_reason = ? WHERE id = ?");
        $stmt->execute([$reason, $userId]);
    }
    
    logActivity($_SESSION['user_id'], 'update', 'user', "User $action: $userId");
    redirect('admin/users.php?' . http_build_query($_GET));
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
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/admin.css">
</head>
<body>
    <div class="d-flex">
        <?php include BASE_PATH . 'admin/includes/sidebar.php'; ?>

        <div class="flex-grow-1">
            <?php include BASE_PATH . 'admin/includes/header.php'; ?>

            <div class="container-fluid py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Manage Users</h2>
                    <a href="<?php echo BASE_URL; ?>" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add User
                    </a>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-2">
                                <select name="role" class="form-select">
                                    <option value="">All Roles</option>
                                    <option value="buyer" <?php echo $role === 'buyer' ? 'selected' : ''; ?>>Buyer</option>
                                    <option value="seller" <?php echo $role === 'seller' ? 'selected' : ''; ?>>Seller</option>
                                    <option value="agent" <?php echo $role === 'agent' ? 'selected' : ''; ?>>Agent</option>
                                    <option value="builder" <?php echo $role === 'builder' ? 'selected' : ''; ?>>Builder</option>
                                    <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="status" class="form-select">
                                    <option value="">All Status</option>
                                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="suspended" <?php echo $status === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                    <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="kyc" class="form-select">
                                    <option value="">All KYC</option>
                                    <option value="pending" <?php echo $kyc === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="approved" <?php echo $kyc === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="rejected" <?php echo $kyc === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="search" class="form-control" placeholder="Search by name or email" value="<?php echo $search; ?>">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">Filter</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Role</th>
                                        <th>Account Status</th>
                                        <th>KYC Status</th>
                                        <th>Email Verified</th>
                                        <th>Joined</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?php echo $user['profile_image'] ? BASE_URL . $user['profile_image'] : BASE_URL . 'assets/images/default-avatar.png'; ?>" 
                                                         class="rounded-circle me-2" width="40" height="40" alt="">
                                                    <div>
                                                        <strong><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></strong>
                                                        <br><small class="text-muted"><?php echo $user['email']; ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo ucfirst($user['role']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $user['account_status'] === 'active' ? 'success' : 'danger'; ?>">
                                                    <?php echo ucfirst($user['account_status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $user['kyc_verified'] === 'approved' ? 'success' : ($user['kyc_verified'] === 'rejected' ? 'danger' : 'warning'); ?>">
                                                    <?php echo ucfirst($user['kyc_verified']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($user['email_verified']): ?>
                                                    <i class="fas fa-check-circle text-success"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-times-circle text-danger"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo formatDate($user['created_at']); ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                        Actions
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li>
                                                            <a class="dropdown-item" href="<?php echo BASE_URL; ?>admin/user-details.php?id=<?php echo $user['id']; ?>">
                                                                <i class="fas fa-eye me-2"></i> View Details
                                                            </a>
                                                        </li>
                                                        <?php if ($user['account_status'] === 'active'): ?>
                                                            <li>
                                                                <form method="POST" style="display: inline;">
                                                                    <input type="hidden" name="action" value="suspend">
                                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                    <button type="submit" class="dropdown-item">
                                                                        <i class="fas fa-ban me-2"></i> Suspend
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        <?php else: ?>
                                                            <li>
                                                                <form method="POST" style="display: inline;">
                                                                    <input type="hidden" name="action" value="activate">
                                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                    <button type="submit" class="dropdown-item">
                                                                        <i class="fas fa-check me-2"></i> Activate
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        <?php endif; ?>
                                                        <?php if ($user['kyc_verified'] === 'pending'): ?>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <form method="POST" style="display: inline;">
                                                                    <input type="hidden" name="action" value="verify_kyc">
                                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                    <button type="submit" class="dropdown-item">
                                                                        <i class="fas fa-check-circle me-2"></i> Approve KYC
                                                                    </button>
                                                                </form>
                                                            </li>
                                                            <li>
                                                                <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#rejectKYCModal<?php echo $user['id']; ?>">
                                                                    <i class="fas fa-times-circle me-2"></i> Reject KYC
                                                                </button>
                                                            </li>
                                                        <?php endif; ?>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure?');">
                                                                <input type="hidden" name="action" value="delete">
                                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                <button type="submit" class="dropdown-item text-danger">
                                                                    <i class="fas fa-trash me-2"></i> Delete
                                                                </button>
                                                            </form>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- Reject KYC Modal -->
                                        <div class="modal fade" id="rejectKYCModal<?php echo $user['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Reject KYC</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="POST">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="action" value="reject_kyc">
                                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                            <div class="mb-3">
                                                                <label class="form-label">Rejection Reason</label>
                                                                <textarea name="rejection_reason" class="form-control" required></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-danger">Reject</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

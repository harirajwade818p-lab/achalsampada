<?php
require_once __DIR__ . '/../config/database.php';
require_once BASE_PATH . 'includes/functions.php';

session_start();

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

$pageTitle = 'Manage Properties';

// Get filters
$status = $_GET['status'] ?? '';
$approval = $_GET['approval'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$where = ["1=1"];
$params = [];

if ($status) {
    $where[] = "property_status = ?";
    $params[] = $status;
}

if ($approval) {
    $where[] = "approval_status = ?";
    $params[] = $approval;
}

if ($search) {
    $where[] = "(title LIKE ? OR description LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm]);
}

$whereClause = implode(' AND ', $where);

// Get properties
$stmt = $conn->prepare("
    SELECT p.*, u.first_name, u.last_name, pc.name as category_name, pt.name as type_name,
           (SELECT image_path FROM property_images WHERE property_id = p.id AND is_cover = 1 LIMIT 1) as cover_image
    FROM properties p
    LEFT JOIN users u ON p.user_id = u.id
    LEFT JOIN property_categories pc ON p.category_id = pc.id
    LEFT JOIN property_types pt ON p.type_id = pt.id
    WHERE $whereClause
    ORDER BY p.created_at DESC
");
$stmt->execute($params);
$properties = $stmt->fetchAll();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $propertyId = $_POST['property_id'];
    
    if ($action === 'approve') {
        $stmt = $conn->prepare("UPDATE properties SET approval_status = 'approved' WHERE id = ?");
        $stmt->execute([$propertyId]);
    } elseif ($action === 'reject') {
        $reason = sanitize($_POST['rejection_reason']);
        $stmt = $conn->prepare("UPDATE properties SET approval_status = 'rejected', rejection_reason = ? WHERE id = ?");
        $stmt->execute([$reason, $propertyId]);
    } elseif ($action === 'feature') {
        $stmt = $conn->prepare("UPDATE properties SET is_featured = 1, featured_expiry = DATE_ADD(NOW(), INTERVAL 30 DAY) WHERE id = ?");
        $stmt->execute([$propertyId]);
    } elseif ($action === 'unfeature') {
        $stmt = $conn->prepare("UPDATE properties SET is_featured = 0 WHERE id = ?");
        $stmt->execute([$propertyId]);
    } elseif ($action === 'verify') {
        $stmt = $conn->prepare("UPDATE properties SET is_verified = 1, verification_badge = 'verified_property' WHERE id = ?");
        $stmt->execute([$propertyId]);
    } elseif ($action === 'delete') {
        // Delete images
        $stmt = $conn->prepare("SELECT image_path FROM property_images WHERE property_id = ?");
        $stmt->execute([$propertyId]);
        $images = $stmt->fetchAll();
        foreach ($images as $image) {
            deleteFile($image['image_path']);
        }
        
        $stmt = $conn->prepare("DELETE FROM properties WHERE id = ?");
        $stmt->execute([$propertyId]);
    }
    
    logActivity($_SESSION['user_id'], 'update', 'property', "Property $action: $propertyId");
    redirect('admin/properties.php?' . http_build_query($_GET));
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
                    <h2>Manage Properties</h2>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <select name="approval" class="form-select">
                                    <option value="">All Approval Status</option>
                                    <option value="pending" <?php echo $approval === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="approved" <?php echo $approval === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="rejected" <?php echo $approval === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="status" class="form-select">
                                    <option value="">All Property Status</option>
                                    <option value="available" <?php echo $status === 'available' ? 'selected' : ''; ?>>Available</option>
                                    <option value="sold" <?php echo $status === 'sold' ? 'selected' : ''; ?>>Sold</option>
                                    <option value="rented" <?php echo $status === 'rented' ? 'selected' : ''; ?>>Rented</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="search" class="form-control" placeholder="Search by title" value="<?php echo $search; ?>">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">Filter</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Properties Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Property</th>
                                        <th>Owner</th>
                                        <th>Type</th>
                                        <th>Price</th>
                                        <th>Approval</th>
                                        <th>Status</th>
                                        <th>Featured</th>
                                        <th>Verified</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($properties as $property): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?php echo $property['cover_image'] ? BASE_URL . $property['cover_image'] : BASE_URL . 'assets/images/no-image.jpg'; ?>" 
                                                         class="rounded me-2" width="60" height="45" style="object-fit: cover;" alt="">
                                                    <div>
                                                        <a href="<?php echo BASE_URL; ?>property/<?php echo $property['slug']; ?>" target="_blank" class="text-decoration-none fw-bold">
                                                            <?php echo substr($property['title'], 0, 25); ?>...
                                                        </a>
                                                        <br><small class="text-muted"><?php echo formatDate($property['created_at']); ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo $property['first_name'] . ' ' . $property['last_name']; ?></td>
                                            <td><?php echo $property['type_name']; ?></td>
                                            <td>₹<?php echo formatPrice($property['price']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $property['approval_status'] === 'approved' ? 'success' : ($property['approval_status'] === 'rejected' ? 'danger' : 'warning'); ?>">
                                                    <?php echo ucfirst($property['approval_status']); ?>
                                                </span>
                                                <?php if ($property['approval_status'] === 'rejected' && $property['rejection_reason']): ?>
                                                    <small class="d-block text-muted"><?php echo $property['rejection_reason']; ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $property['property_status'] === 'available' ? 'success' : 'secondary'; ?>">
                                                    <?php echo ucfirst($property['property_status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($property['is_featured']): ?>
                                                    <i class="fas fa-star text-warning"></i>
                                                <?php else: ?>
                                                    <i class="far fa-star text-muted"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($property['is_verified']): ?>
                                                    <i class="fas fa-check-circle text-success"></i>
                                                <?php else: ?>
                                                    <i class="far fa-circle text-muted"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                        Actions
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li>
                                                            <a class="dropdown-item" href="<?php echo BASE_URL; ?>property/<?php echo $property['slug']; ?>" target="_blank">
                                                                <i class="fas fa-eye me-2"></i> View Property
                                                            </a>
                                                        </li>
                                                        <?php if ($property['approval_status'] === 'pending'): ?>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <form method="POST" style="display: inline;">
                                                                    <input type="hidden" name="action" value="approve">
                                                                    <input type="hidden" name="property_id" value="<?php echo $property['id']; ?>">
                                                                    <button type="submit" class="dropdown-item">
                                                                        <i class="fas fa-check me-2"></i> Approve
                                                                    </button>
                                                                </form>
                                                            </li>
                                                            <li>
                                                                <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#rejectModal<?php echo $property['id']; ?>">
                                                                    <i class="fas fa-times me-2"></i> Reject
                                                                </button>
                                                            </li>
                                                        <?php endif; ?>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <?php if ($property['is_featured']): ?>
                                                            <li>
                                                                <form method="POST" style="display: inline;">
                                                                    <input type="hidden" name="action" value="unfeature">
                                                                    <input type="hidden" name="property_id" value="<?php echo $property['id']; ?>">
                                                                    <button type="submit" class="dropdown-item">
                                                                        <i class="fas fa-star me-2"></i> Remove Featured
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        <?php else: ?>
                                                            <li>
                                                                <form method="POST" style="display: inline;">
                                                                    <input type="hidden" name="action" value="feature">
                                                                    <input type="hidden" name="property_id" value="<?php echo $property['id']; ?>">
                                                                    <button type="submit" class="dropdown-item">
                                                                        <i class="far fa-star me-2"></i> Make Featured
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        <?php endif; ?>
                                                        <?php if (!$property['is_verified']): ?>
                                                            <li>
                                                                <form method="POST" style="display: inline;">
                                                                    <input type="hidden" name="action" value="verify">
                                                                    <input type="hidden" name="property_id" value="<?php echo $property['id']; ?>">
                                                                    <button type="submit" class="dropdown-item">
                                                                        <i class="fas fa-check-circle me-2"></i> Verify Property
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        <?php endif; ?>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this property?');">
                                                                <input type="hidden" name="action" value="delete">
                                                                <input type="hidden" name="property_id" value="<?php echo $property['id']; ?>">
                                                                <button type="submit" class="dropdown-item text-danger">
                                                                    <i class="fas fa-trash me-2"></i> Delete
                                                                </button>
                                                            </form>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- Reject Modal -->
                                        <div class="modal fade" id="rejectModal<?php echo $property['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Reject Property</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="POST">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="action" value="reject">
                                                            <input type="hidden" name="property_id" value="<?php echo $property['id']; ?>">
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

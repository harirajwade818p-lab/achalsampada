<?php
require_once __DIR__ . '/../config/database.php';
require_once BASE_PATH . 'includes/functions.php';

session_start();

if (!isLoggedIn() || !isSeller()) {
    redirect('login.php');
}

$pageTitle = 'My Properties';
$user = getCurrentUser();

// Get properties
$stmt = $conn->prepare("
    SELECT p.*, pc.name as category_name, pt.name as type_name,
           (SELECT image_path FROM property_images WHERE property_id = p.id AND is_cover = 1 LIMIT 1) as cover_image,
           (SELECT COUNT(*) FROM property_images WHERE property_id = p.id) as image_count
    FROM properties p
    LEFT JOIN property_categories pc ON p.category_id = pc.id
    LEFT JOIN property_types pt ON p.type_id = pt.id
    WHERE p.user_id = ?
    ORDER BY p.created_at DESC
");
$stmt->execute([$user['id']]);
$properties = $stmt->fetchAll();
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>My Properties</h2>
            <a href="<?php echo BASE_URL; ?>properties/add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Property
            </a>
        </div>

        <?php if (empty($properties)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> You haven't added any properties yet.
                <a href="<?php echo BASE_URL; ?>properties/add.php" class="btn btn-primary ms-3">Add Your First Property</a>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Property</th>
                                    <th>Type</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Approval</th>
                                    <th>Views</th>
                                    <th>Inquiries</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($properties as $property): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?php echo $property['cover_image'] ? BASE_URL . $property['cover_image'] : BASE_URL . 'assets/images/no-image.jpg'; ?>" 
                                                     class="rounded me-3" width="80" height="60" style="object-fit: cover;">
                                                <div>
                                                    <a href="<?php echo BASE_URL; ?>property/<?php echo $property['slug']; ?>" class="text-decoration-none fw-bold">
                                                        <?php echo substr($property['title'], 0, 30); ?>...
                                                    </a>
                                                    <small class="d-block text-muted"><?php echo formatDate($property['created_at']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo $property['type_name']; ?></td>
                                        <td>₹<?php echo formatPrice($property['price']); ?></td>
                                        <td>
                                            <?php
                                            $statusClass = match($property['property_status']) {
                                                'available' => 'success',
                                                'sold' => 'danger',
                                                'rented' => 'warning',
                                                'under_review' => 'info',
                                                default => 'secondary'
                                            };
                                            ?>
                                            <span class="badge bg-<?php echo $statusClass; ?>"><?php echo ucfirst($property['property_status']); ?></span>
                                        </td>
                                        <td>
                                            <?php
                                            $approvalClass = match($property['approval_status']) {
                                                'approved' => 'success',
                                                'rejected' => 'danger',
                                                'pending' => 'warning',
                                                default => 'secondary'
                                            };
                                            ?>
                                            <span class="badge bg-<?php echo $approvalClass; ?>"><?php echo ucfirst($property['approval_status']); ?></span>
                                            <?php if ($property['approval_status'] === 'rejected' && $property['rejection_reason']): ?>
                                                <small class="d-block text-muted"><?php echo $property['rejection_reason']; ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $property['view_count']; ?></td>
                                        <td><?php echo $property['inquiry_count']; ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="<?php echo BASE_URL; ?>properties/edit.php?id=<?php echo $property['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="<?php echo BASE_URL; ?>properties/images.php?id=<?php echo $property['id']; ?>" class="btn btn-sm btn-outline-info" title="Images">
                                                    <i class="fas fa-images"></i>
                                                </a>
                                                <button onclick="deleteProperty(<?php echo $property['id']; ?>)" class="btn btn-sm btn-outline-danger delete-btn" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include BASE_PATH . 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteProperty(id) {
            if (confirm('Are you sure you want to delete this property?')) {
                fetch(BASE_URL + 'api/delete-property.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'property_id=' + id
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                });
            }
        }
    </script>
</body>
</html>

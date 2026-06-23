<?php
require_once __DIR__ . '/config/database.php';
require_once BASE_PATH . 'includes/functions.php';

session_start();

if (!isLoggedIn()) {
    redirect('login.php');
}

$pageTitle = 'My Wishlist';
$user = getCurrentUser();

// Get wishlist properties
$stmt = $conn->prepare("
    SELECT p.*, w.created_at as added_date, pc.name as category_name, pt.name as type_name,
           (SELECT image_path FROM property_images WHERE property_id = p.id AND is_cover = 1 LIMIT 1) as cover_image
    FROM wishlist w
    JOIN properties p ON w.property_id = p.id
    LEFT JOIN property_categories pc ON p.category_id = pc.id
    LEFT JOIN property_types pt ON p.type_id = pt.id
    WHERE w.user_id = ? AND p.approval_status = 'approved'
    ORDER BY w.created_at DESC
");
$stmt->execute([$user['id']]);
$wishlist = $stmt->fetchAll();
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
        <h2 class="mb-4">My Wishlist</h2>

        <?php if (empty($wishlist)): ?>
            <div class="alert alert-info">
                <i class="fas fa-heart"></i> Your wishlist is empty.
                <a href="<?php echo BASE_URL; ?>search.php" class="btn btn-primary ms-3">Browse Properties</a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($wishlist as $property): ?>
                    <div class="col-md-4 mb-4">
                        <?php 
                            $property = $property;
                            $inWishlist = true;
                            include BASE_PATH . 'includes/property-card.php'; 
                        ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include BASE_PATH . 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
</body>
</html>

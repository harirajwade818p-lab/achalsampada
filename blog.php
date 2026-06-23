<?php
require_once __DIR__ . '/config/database.php';
require_once BASE_PATH . 'includes/functions.php';

$pageTitle = 'Blog';

// Get blogs
$stmt = $conn->prepare("
    SELECT b.*, u.first_name, u.last_name
    FROM blogs b
    LEFT JOIN users u ON b.user_id = u.id
    WHERE b.status = 'published'
    ORDER BY b.created_at DESC
");
$stmt->execute();
$blogs = $stmt->fetchAll();
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
        <h2 class="mb-4">Blog & News</h2>

        <?php if (empty($blogs)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No blog posts yet.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($blogs as $blog): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <?php if ($blog['featured_image']): ?>
                                <img src="<?php echo BASE_URL . $blog['featured_image']; ?>" class="card-img-top" alt="<?php echo $blog['title']; ?>" style="height: 200px; object-fit: cover;">
                            <?php endif; ?>
                            <div class="card-body">
                                <small class="text-muted">
                                    <i class="fas fa-calendar"></i> <?php echo formatDate($blog['created_at']); ?>
                                    <?php if ($blog['category']): ?>
                                        | <i class="fas fa-folder"></i> <?php echo $blog['category']; ?>
                                    <?php endif; ?>
                                </small>
                                <h5 class="card-title mt-2">
                                    <a href="#" class="text-decoration-none text-dark"><?php echo $blog['title']; ?></a>
                                </h5>
                                <p class="card-text text-truncate-2"><?php echo substr($blog['content'], 0, 150); ?>...</p>
                                <a href="#" class="btn btn-primary btn-sm">Read More</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include BASE_PATH . 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

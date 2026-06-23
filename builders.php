<?php
require_once __DIR__ . '/config/database.php';
require_once BASE_PATH . 'includes/functions.php';

$pageTitle = 'Builders';

// Get builders
$stmt = $conn->prepare("
    SELECT u.*, bp.*,
           (SELECT COUNT(*) FROM builder_projects WHERE builder_id = bp.id) as total_projects
    FROM users u
    JOIN builder_profiles bp ON u.id = bp.user_id
    WHERE u.role = 'builder' AND u.account_status = 'active'
    ORDER BY bp.rating DESC, u.created_at DESC
");
$stmt->execute();
$builders = $stmt->fetchAll();
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
        <h2 class="mb-4">Builders & Developers</h2>

        <?php if (empty($builders)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No builders registered yet.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($builders as $builder): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <img src="<?php echo $builder['company_logo'] ? BASE_URL . $builder['company_logo'] : BASE_URL . 'assets/images/default-avatar.png'; ?>" 
                                         class="rounded me-3" width="60" height="60" alt="<?php echo $builder['company_name']; ?>">
                                    <div>
                                        <h5 class="mb-0"><?php echo $builder['company_name']; ?></h5>
                                        <?php if ($builder['is_verified']): ?>
                                            <span class="badge bg-success"><i class="fas fa-check-circle"></i> Verified</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <p class="text-muted small">
                                    <?php if ($builder['established_year']): ?>
                                        <i class="fas fa-calendar"></i> Since <?php echo $builder['established_year']; ?><br>
                                    <?php endif; ?>
                                    <i class="fas fa-building"></i> <?php echo $builder['total_projects']; ?> Projects<br>
                                    <i class="fas fa-star"></i> <?php echo $builder['rating']; ?> Rating
                                </p>
                                <?php if ($builder['description']): ?>
                                    <p class="small text-truncate-2"><?php echo substr($builder['description'], 0, 100); ?>...</p>
                                <?php endif; ?>
                                <a href="mailto:<?php echo $builder['email']; ?>" class="btn btn-outline-primary btn-sm w-100">
                                    <i class="fas fa-envelope"></i> Contact Builder
                                </a>
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

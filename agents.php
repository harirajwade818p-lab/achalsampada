<?php
require_once __DIR__ . '/config/database.php';
require_once BASE_PATH . 'includes/functions.php';

$pageTitle = 'Agents';

// Get agents
$stmt = $conn->prepare("
    SELECT u.*, ap.*, 
           (SELECT COUNT(*) FROM properties WHERE user_id = u.id) as total_properties,
           (SELECT COUNT(*) FROM reviews WHERE agent_id = ap.id) as total_reviews
    FROM users u
    JOIN agent_profiles ap ON u.id = ap.user_id
    WHERE u.role = 'agent' AND u.account_status = 'active'
    ORDER BY ap.rating DESC, u.created_at DESC
");
$stmt->execute();
$agents = $stmt->fetchAll();
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
        <h2 class="mb-4">Property Agents</h2>

        <?php if (empty($agents)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No agents registered yet.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($agents as $agent): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <img src="<?php echo $agent['profile_image'] ? BASE_URL . $agent['profile_image'] : BASE_URL . 'assets/images/default-avatar.png'; ?>" 
                                     class="rounded-circle mb-3" width="100" height="100" alt="<?php echo $agent['first_name']; ?>">
                                <h5><?php echo $agent['first_name'] . ' ' . $agent['last_name']; ?></h5>
                                <?php if ($agent['agency_name']): ?>
                                    <p class="text-muted"><?php echo $agent['agency_name']; ?></p>
                                <?php endif; ?>
                                <div class="mb-3">
                                    <?php if ($agent['is_verified']): ?>
                                        <span class="badge bg-success"><i class="fas fa-check-circle"></i> Verified</span>
                                    <?php endif; ?>
                                    <?php if ($agent['rating'] > 0): ?>
                                        <span class="badge bg-warning text-dark">
                                            <i class="fas fa-star"></i> <?php echo $agent['rating']; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-muted">
                                    <i class="fas fa-building"></i> <?php echo $agent['total_properties']; ?> Properties<br>
                                    <i class="fas fa-star"></i> <?php echo $agent['total_reviews']; ?> Reviews
                                </p>
                                <?php if ($agent['experience_years']): ?>
                                    <small class="text-muted"><?php echo $agent['experience_years']; ?> years experience</small>
                                <?php endif; ?>
                                <div class="mt-3">
                                    <a href="mailto:<?php echo $agent['email']; ?>" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-envelope"></i> Contact
                                    </a>
                                </div>
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

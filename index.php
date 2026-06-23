<?php
require_once __DIR__ . '/config/database.php';
require_once BASE_PATH . 'includes/functions.php';

// Get featured properties
$stmt = $conn->prepare("
    SELECT p.*, u.first_name, u.last_name, 
           (SELECT image_path FROM property_images WHERE property_id = p.id AND is_cover = 1 LIMIT 1) as cover_image,
           (SELECT COUNT(*) FROM property_images WHERE property_id = p.id) as image_count
    FROM properties p
    LEFT JOIN users u ON p.user_id = u.id
    WHERE p.approval_status = 'approved' 
    AND p.property_status = 'available'
    AND (p.is_featured = 1 OR p.is_premium = 1)
    ORDER BY p.created_at DESC
    LIMIT 6
");
$stmt->execute();
$featuredProperties = $stmt->fetchAll();

// Get latest properties
$stmt = $conn->prepare("
    SELECT p.*, u.first_name, u.last_name, 
           (SELECT image_path FROM property_images WHERE property_id = p.id AND is_cover = 1 LIMIT 1) as cover_image,
           (SELECT COUNT(*) FROM property_images WHERE property_id = p.id) as image_count
    FROM properties p
    LEFT JOIN users u ON p.user_id = u.id
    WHERE p.approval_status = 'approved' 
    AND p.property_status = 'available'
    ORDER BY p.created_at DESC
    LIMIT 12
");
$stmt->execute();
$latestProperties = $stmt->fetchAll();

// Get property types
$stmt = $conn->prepare("SELECT * FROM property_types WHERE status = 'active' LIMIT 8");
$stmt->execute();
$propertyTypes = $stmt->fetchAll();

// Get cities
$stmt = $conn->prepare("SELECT DISTINCT c.name, s.name as state_name FROM cities c JOIN states s ON c.state_id = s.id WHERE c.status = 'active' LIMIT 10");
$stmt->execute();
$cities = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Find Your Dream Property</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
</head>
<body>
    <?php include BASE_PATH . 'includes/header.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="hero-content">
                        <h1>Find Your Dream Property</h1>
                        <p>Search from thousands of properties across India</p>
                        
                        <!-- Search Form -->
                        <form action="<?php echo BASE_URL; ?>search.php" method="GET" class="search-form">
                            <div class="row">
                                <div class="col-md-3">
                                    <select name="category" class="form-select">
                                        <option value="">Property Category</option>
                                        <option value="buy">Buy</option>
                                        <option value="rent">Rent</option>
                                        <option value="lease">Lease</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select name="type" class="form-select">
                                        <option value="">Property Type</option>
                                        <?php foreach ($propertyTypes as $type): ?>
                                            <option value="<?php echo $type['slug']; ?>"><?php echo $type['name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <input type="text" name="location" class="form-control" placeholder="Enter location">
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-search"></i> Search
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Property Types -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-4">Browse by Property Type</h2>
            <div class="row">
                <?php foreach ($propertyTypes as $type): ?>
                    <div class="col-md-3 col-sm-6 mb-4">
                        <a href="<?php echo BASE_URL; ?>search.php?type=<?php echo $type['slug']; ?>" class="property-type-card">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body text-center">
                                    <i class="fas <?php echo $type['icon']; ?> fa-3x mb-3 text-primary"></i>
                                    <h5 class="card-title"><?php echo $type['name']; ?></h5>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Featured Properties -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Featured Properties</h2>
                <a href="<?php echo BASE_URL; ?>search.php?featured=1" class="btn btn-outline-primary">View All</a>
            </div>
            <div class="row">
                <?php foreach ($featuredProperties as $property): ?>
                    <div class="col-md-4 mb-4">
                        <?php include BASE_PATH . 'includes/property-card.php'; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Latest Properties -->
    <section class="py-5">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Latest Properties</h2>
                <a href="<?php echo BASE_URL; ?>search.php" class="btn btn-outline-primary">View All</a>
            </div>
            <div class="row">
                <?php foreach ($latestProperties as $property): ?>
                    <div class="col-md-4 mb-4">
                        <?php include BASE_PATH . 'includes/property-card.php'; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Popular Cities -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-4">Popular Cities</h2>
            <div class="row">
                <?php foreach ($cities as $city): ?>
                    <div class="col-md-2 col-sm-4 mb-3">
                        <a href="<?php echo BASE_URL; ?>search.php?city=<?php echo urlencode($city['name']); ?>" class="city-card">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $city['name']; ?></h5>
                                    <small class="text-muted"><?php echo $city['state_name']; ?></small>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Why Choose Us -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-4">Why Choose Us</h2>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="feature-card text-center">
                        <i class="fas fa-home fa-3x mb-3 text-primary"></i>
                        <h4>Wide Range of Properties</h4>
                        <p>Thousands of properties to choose from</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="feature-card text-center">
                        <i class="fas fa-shield-alt fa-3x mb-3 text-primary"></i>
                        <h4>Verified Listings</h4>
                        <p>All properties are verified for authenticity</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="feature-card text-center">
                        <i class="fas fa-headset fa-3x mb-3 text-primary"></i>
                        <h4>24/7 Support</h4>
                        <p>Our team is always here to help you</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include BASE_PATH . 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
</body>
</html>

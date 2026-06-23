<?php
require_once __DIR__ . '/config/database.php';
require_once BASE_PATH . 'includes/functions.php';

session_start();

$slug = $_GET['slug'] ?? '';

if (!$slug) {
    redirect('index.php');
}

// Get property details
$stmt = $conn->prepare("
    SELECT p.*, u.first_name, u.last_name, u.phone, u.email as owner_email, u.profile_image as owner_image,
           pc.name as category_name, pt.name as type_name,
           s.name as state_name, c.name as city_name, a.name as area_name
    FROM properties p
    LEFT JOIN users u ON p.user_id = u.id
    LEFT JOIN property_categories pc ON p.category_id = pc.id
    LEFT JOIN property_types pt ON p.type_id = pt.id
    LEFT JOIN states s ON p.state_id = s.id
    LEFT JOIN cities c ON p.city_id = c.id
    LEFT JOIN areas a ON p.area_id = a.id
    WHERE p.slug = ? AND p.approval_status = 'approved'
");
$stmt->execute([$slug]);
$property = $stmt->fetch();

if (!$property) {
    redirect('index.php');
}

$pageTitle = $property['title'];

// Increment view count
incrementPropertyView($property['id']);

// Get property images
$stmt = $conn->prepare("SELECT * FROM property_images WHERE property_id = ? ORDER BY is_cover DESC, display_order ASC");
$stmt->execute([$property['id']]);
$images = $stmt->fetchAll();

// Get property videos
$stmt = $conn->prepare("SELECT * FROM property_videos WHERE property_id = ?");
$stmt->execute([$property['id']]);
$videos = $stmt->fetchAll();

// Get property documents
$stmt = $conn->prepare("SELECT * FROM property_documents WHERE property_id = ?");
$stmt->execute([$property['id']]);
$documents = $stmt->fetchAll();

// Get property amenities
$stmt = $conn->prepare("
    SELECT a.* FROM amenities a
    JOIN property_amenities pa ON a.id = pa.amenity_id
    WHERE pa.property_id = ?
");
$stmt->execute([$property['id']]);
$amenities = $stmt->fetchAll();

// Get floor plans
$stmt = $conn->prepare("SELECT * FROM property_floor_plans WHERE property_id = ?");
$stmt->execute([$property['id']]);
$floorPlans = $stmt->fetchAll();

// Get similar properties
$stmt = $conn->prepare("
    SELECT p.*, 
           (SELECT image_path FROM property_images WHERE property_id = p.id AND is_cover = 1 LIMIT 1) as cover_image
    FROM properties p
    WHERE p.type_id = ? AND p.city_id = ? AND p.id != ? AND p.approval_status = 'approved' AND p.property_status = 'available'
    ORDER BY RAND()
    LIMIT 4
");
$stmt->execute([$property['type_id'], $property['city_id'], $property['id']]);
$similarProperties = $stmt->fetchAll();

// Check if in wishlist
$inWishlist = isLoggedIn() ? isInWishlist($property['id']) : false;

// Handle inquiry submission
$inquirySuccess = '';
$inquiryError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_inquiry'])) {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
    
    $userId = getCurrentUserId();
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $message = sanitize($_POST['message']);
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO inquiries (property_id, user_id, owner_id, name, email, phone, message, inquiry_type, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'property', 'pending')
        ");
        $stmt->execute([$property['id'], $userId, $property['user_id'], $name, $email, $phone, $message]);
        
        // Update inquiry count
        $stmt = $conn->prepare("UPDATE properties SET inquiry_count = inquiry_count + 1 WHERE id = ?");
        $stmt->execute([$property['id']]);
        
        logActivity($userId, 'create', 'inquiry', 'Inquiry sent for property: ' . $property['title']);
        
        $inquirySuccess = 'Inquiry sent successfully! The owner will contact you soon.';
    } catch (PDOException $e) {
        $inquiryError = 'Error sending inquiry. Please try again.';
    }
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
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
</head>
<body>
    <?php include BASE_PATH . 'includes/header.php'; ?>

    <div class="container py-5">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>">Home</a></li>
                <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>search.php">Properties</a></li>
                <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>search.php?city=<?php echo urlencode($property['city_name']); ?>"><?php echo $property['city_name']; ?></a></li>
                <li class="breadcrumb-item active"><?php echo substr($property['title'], 0, 30); ?>...</li>
            </ol>
        </nav>

        <div class="row">
            <!-- Left Column - Property Details -->
            <div class="col-lg-8">
                <!-- Property Gallery -->
                <div class="card mb-4">
                    <div class="card-body">
                        <?php if (!empty($images)): ?>
                            <div class="row">
                                <div class="col-md-8">
                                    <img src="<?php echo BASE_URL . $images[0]['image_path']; ?>" 
                                         class="img-fluid rounded" id="mainImage" alt="<?php echo $property['title']; ?>">
                                </div>
                                <div class="col-md-4">
                                    <div class="row property-gallery">
                                        <?php foreach (array_slice($images, 0, 4) as $index => $image): ?>
                                            <div class="col-6 mb-2">
                                                <img src="<?php echo BASE_URL . $image['image_path']; ?>" 
                                                     class="img-fluid rounded <?php echo $index === 0 ? 'active' : ''; ?>" 
                                                     alt="Property Image">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <img src="<?php echo BASE_URL; ?>assets/images/no-image.jpg" class="img-fluid rounded" alt="No Image">
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Property Info -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h2><?php echo $property['title']; ?></h2>
                                <p class="text-muted mb-2">
                                    <i class="fas fa-map-marker-alt"></i> 
                                    <?php echo $property['street']; ?>, <?php echo $property['area_name']; ?>, 
                                    <?php echo $property['city_name']; ?>, <?php echo $property['state_name']; ?> - <?php echo $property['pincode']; ?>
                                </p>
                            </div>
                            <div class="text-end">
                                <button class="btn btn-outline-danger wishlist-btn" data-property-id="<?php echo $property['id']; ?>">
                                    <i class="fas fa-heart <?php echo $inWishlist ? 'text-danger' : ''; ?>"></i>
                                </button>
                                <button class="btn btn-outline-secondary ms-2" onclick="shareProperty()">
                                    <i class="fas fa-share-alt"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h3 class="text-primary">₹<?php echo formatPrice($property['price']); ?></h3>
                            <?php if ($property['price_per_sqft']): ?>
                                <small class="text-muted">₹<?php echo formatPrice($property['price_per_sqft']); ?> per sq.ft.</small>
                            <?php endif; ?>
                        </div>

                        <!-- Badges -->
                        <div class="mb-4">
                            <?php if ($property['is_featured']): ?>
                                <span class="badge bg-warning text-dark me-2">Featured</span>
                            <?php endif; ?>
                            <?php if ($property['is_premium']): ?>
                                <span class="badge bg-primary me-2">Premium</span>
                            <?php endif; ?>
                            <?php if ($property['is_verified']): ?>
                                <span class="badge bg-success me-2"><i class="fas fa-check-circle"></i> Verified</span>
                            <?php endif; ?>
                            <?php if ($property['rera_id']): ?>
                                <span class="badge bg-info me-2">RERA: <?php echo $property['rera_id']; ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Property Specifications -->
                        <div class="row mb-4">
                            <div class="col-md-3 col-6 mb-3">
                                <div class="p-3 bg-light rounded text-center">
                                    <i class="fas fa-bed fa-2x text-primary mb-2"></i>
                                    <h5><?php echo $property['bhk'] ?? 'N/A'; ?></h5>
                                    <small>BHK</small>
                                </div>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <div class="p-3 bg-light rounded text-center">
                                    <i class="fas fa-bath fa-2x text-primary mb-2"></i>
                                    <h5><?php echo $property['bathroom'] ?? 'N/A'; ?></h5>
                                    <small>Bathrooms</small>
                                </div>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <div class="p-3 bg-light rounded text-center">
                                    <i class="fas fa-ruler-combined fa-2x text-primary mb-2"></i>
                                    <h5><?php echo $property['area_size']; ?> <?php echo strtoupper($property['area_unit']); ?></h5>
                                    <small>Area</small>
                                </div>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <div class="p-3 bg-light rounded text-center">
                                    <i class="fas fa-building fa-2x text-primary mb-2"></i>
                                    <h5><?php echo $property['floor_number'] ?? 'G'; ?>/<?php echo $property['total_floors'] ?? 'G'; ?></h5>
                                    <small>Floor</small>
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="mb-4">
                            <h5>Description</h5>
                            <p><?php echo nl2br($property['description']); ?></p>
                        </div>

                        <!-- Amenities -->
                        <?php if (!empty($amenities)): ?>
                            <div class="mb-4">
                                <h5>Amenities</h5>
                                <div class="row">
                                    <?php foreach ($amenities as $amenity): ?>
                                        <div class="col-md-4 col-6 mb-2">
                                            <span class="badge bg-light text-dark">
                                                <i class="fas <?php echo $amenity['icon'] ?? 'fa-check'; ?> me-1"></i>
                                                <?php echo $amenity['name']; ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Additional Details -->
                        <div class="mb-4">
                            <h5>Additional Details</h5>
                            <table class="table">
                                <tr>
                                    <td><strong>Category:</strong></td>
                                    <td><?php echo $property['category_name']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Property Type:</strong></td>
                                    <td><?php echo $property['type_name']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Furnishing:</strong></td>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $property['furnishing'])); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Construction Status:</strong></td>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $property['construction_status'])); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Possession Date:</strong></td>
                                    <td><?php echo $property['possession_date'] ? formatDate($property['possession_date']) : 'N/A'; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Age of Property:</strong></td>
                                    <td><?php echo $property['age_of_property'] ? $property['age_of_property'] . ' years' : 'N/A'; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Facing:</strong></td>
                                    <td><?php echo $property['facing'] ? ucfirst($property['facing']) : 'N/A'; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Parking:</strong></td>
                                    <td><?php echo ucfirst($property['parking']); ?> <?php echo $property['parking_slots'] ? '(' . $property['parking_slots'] . ' slots)' : ''; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Posted On:</strong></td>
                                    <td><?php echo formatDate($property['created_at']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Views:</strong></td>
                                    <td><?php echo $property['view_count']; ?></td>
                                </tr>
                            </table>
                        </div>

                        <!-- Videos -->
                        <?php if (!empty($videos)): ?>
                            <div class="mb-4">
                                <h5>Property Videos</h5>
                                <?php foreach ($videos as $video): ?>
                                    <video controls class="w-100 mb-3" style="max-height: 400px;">
                                        <source src="<?php echo BASE_URL . $video['video_path']; ?>" type="video/mp4">
                                        Your browser does not support the video tag.
                                    </video>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Floor Plans -->
                        <?php if (!empty($floorPlans)): ?>
                            <div class="mb-4">
                                <h5>Floor Plans</h5>
                                <?php foreach ($floorPlans as $floor): ?>
                                    <div class="mb-3">
                                        <h6><?php echo $floor['floor_title']; ?></h6>
                                        <img src="<?php echo BASE_URL . $floor['image_path']; ?>" class="img-fluid rounded" alt="Floor Plan">
                                        <?php if ($floor['area_size']): ?>
                                            <small class="text-muted">Area: <?php echo $floor['area_size']; ?> sq.ft.</small>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Documents -->
                        <?php if (!empty($documents)): ?>
                            <div class="mb-4">
                                <h5>Property Documents</h5>
                                <div class="list-group">
                                    <?php foreach ($documents as $doc): ?>
                                        <a href="<?php echo BASE_URL . $doc['document_path']; ?>" class="list-group-item list-group-item-action" target="_blank">
                                            <i class="fas fa-file-pdf me-2"></i> <?php echo $doc['document_title'] ?: 'Document'; ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Similar Properties -->
                <?php if (!empty($similarProperties)): ?>
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Similar Properties</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php foreach ($similarProperties as $similar): ?>
                                    <div class="col-md-6 mb-3">
                                        <?php 
                                            $property = $similar;
                                            include BASE_PATH . 'includes/property-card.php'; 
                                        ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right Column - Contact & Owner -->
            <div class="col-lg-4">
                <!-- Contact Form -->
                <div class="card mb-4 sticky-top" style="top: 80px;">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Contact Owner</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($inquirySuccess): ?>
                            <div class="alert alert-success"><?php echo $inquirySuccess; ?></div>
                        <?php endif; ?>

                        <?php if ($inquiryError): ?>
                            <div class="alert alert-danger"><?php echo $inquiryError; ?></div>
                        <?php endif; ?>

                        <?php if (isLoggedIn()): ?>
                            <form method="POST">
                                <input type="hidden" name="submit_inquiry" value="1">
                                <div class="mb-3">
                                    <label class="form-label">Name</label>
                                    <input type="text" name="name" class="form-control" required value="<?php echo $_SESSION['user_name'] ?? ''; ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" required value="<?php echo $_SESSION['user_email'] ?? ''; ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Phone</label>
                                    <input type="tel" name="phone" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Message</label>
                                    <textarea name="message" class="form-control" rows="3" required>I'm interested in this property. Please contact me.</textarea>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Send Inquiry</button>
                            </form>
                        <?php else: ?>
                            <p class="text-center mb-3">Please login to contact the owner</p>
                            <a href="<?php echo BASE_URL; ?>login.php" class="btn btn-primary w-100">Login to Contact</a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Owner Info -->
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <img src="<?php echo $property['owner_image'] ? BASE_URL . $property['owner_image'] : BASE_URL . 'assets/images/default-avatar.png'; ?>" 
                             class="rounded-circle mb-3" width="80" height="80" alt="Owner">
                        <h5><?php echo $property['first_name'] . ' ' . $property['last_name']; ?></h5>
                        <p class="text-muted mb-3">Property Owner</p>
                        <?php if ($property['phone']): ?>
                            <a href="tel:<?php echo $property['phone']; ?>" class="btn btn-outline-success w-100 mb-2">
                                <i class="fas fa-phone me-2"></i> Call Owner
                            </a>
                        <?php endif; ?>
                        <a href="mailto:<?php echo $property['owner_email']; ?>" class="btn btn-outline-primary w-100">
                            <i class="fas fa-envelope me-2"></i> Email Owner
                        </a>
                    </div>
                </div>

                <!-- Quick Info -->
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">Quick Info</h6>
                        <ul class="list-unstyled">
                            <li class="mb-2"><i class="fas fa-calendar me-2 text-muted"></i> Posted: <?php echo timeAgo($property['created_at']); ?></li>
                            <li class="mb-2"><i class="fas fa-eye me-2 text-muted"></i> Views: <?php echo $property['view_count']; ?></li>
                            <li class="mb-2"><i class="fas fa-envelope me-2 text-muted"></i> Inquiries: <?php echo $property['inquiry_count']; ?></li>
                            <li><i class="fas fa-tag me-2 text-muted"></i> ID: #<?php echo $property['id']; ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include BASE_PATH . 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
    <script>
        function shareProperty() {
            const url = window.location.href;
            const title = '<?php echo $property['title']; ?>';
            
            if (navigator.share) {
                navigator.share({
                    title: title,
                    url: url
                });
            } else {
                copyToClipboard(url);
            }
        }
    </script>
</body>
</html>

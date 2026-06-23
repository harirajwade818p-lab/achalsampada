<?php
require_once __DIR__ . '/config/database.php';
require_once BASE_PATH . 'includes/functions.php';

$pageTitle = 'Search Properties';

// Get search parameters
$category = $_GET['category'] ?? '';
$type = $_GET['type'] ?? '';
$location = $_GET['location'] ?? '';
$city = $_GET['city'] ?? '';
$state = $_GET['state'] ?? '';
$minPrice = $_GET['min_price'] ?? 0;
$maxPrice = $_GET['max_price'] ?? 999999999;
$bhk = $_GET['bhk'] ?? '';
$bathroom = $_GET['bathroom'] ?? '';
$minArea = $_GET['min_area'] ?? 0;
$maxArea = $_GET['max_area'] ?? 999999;
$furnishing = $_GET['furnishing'] ?? '';
$constructionStatus = $_GET['construction_status'] ?? '';
$featured = isset($_GET['featured']) ? 1 : 0;
$premium = isset($_GET['premium']) ? 1 : 0;
$verified = isset($_GET['verified']) ? 1 : 0;
$allowedSorts = array_keys(getSearchSortOptions());
$sort = in_array($_GET['sort'] ?? '', $allowedSorts, true) ? $_GET['sort'] : '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Build query
$where = ["p.approval_status = 'approved'", "p.property_status = 'available'"];
$params = [];

if ($category) {
    $where[] = "pc.slug = ?";
    $params[] = $category;
}

if ($type) {
    // Support both old and new slug formats
    $where[] = "(pt.slug = ? OR pt.slug LIKE ?)";
    $params[] = $type;
    $params[] = "%$type%";
}

if ($location) {
    $where[] = "(p.street LIKE ? OR p.landmark LIKE ? OR a.name LIKE ? OR c.name LIKE ?)";
    $searchTerm = "%$location%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

if ($city) {
    $where[] = "c.name = ?";
    $params[] = $city;
}

if ($state) {
    $where[] = "s.name = ?";
    $params[] = $state;
}

if ($minPrice) {
    $where[] = "p.price >= ?";
    $params[] = $minPrice;
}

if ($maxPrice && $maxPrice < 999999999) {
    $where[] = "p.price <= ?";
    $params[] = $maxPrice;
}

if ($bhk) {
    $where[] = "p.bhk = ?";
    $params[] = $bhk;
}

if ($bathroom) {
    $where[] = "p.bathroom = ?";
    $params[] = $bathroom;
}

if ($minArea) {
    $where[] = "p.area_size >= ?";
    $params[] = $minArea;
}

if ($maxArea && $maxArea < 999999) {
    $where[] = "p.area_size <= ?";
    $params[] = $maxArea;
}

if ($furnishing) {
    $where[] = "p.furnishing = ?";
    $params[] = $furnishing;
}

if ($constructionStatus) {
    $where[] = "p.construction_status = ?";
    $params[] = $constructionStatus;
}

if ($featured) {
    $where[] = "p.is_featured = 1";
}

if ($premium) {
    $where[] = "p.is_premium = 1";
}

if ($verified) {
    $where[] = "p.is_verified = 1";
}

$whereClause = implode(' AND ', $where);

// Sort order
$orderBy = "p.is_featured DESC, p.is_premium DESC, p.created_at DESC";
switch ($sort) {
    case 'price_low':
        $orderBy = "p.price ASC";
        break;
    case 'price_high':
        $orderBy = "p.price DESC";
        break;
    case 'newest':
        $orderBy = "p.created_at DESC";
        break;
    case 'oldest':
        $orderBy = "p.created_at ASC";
        break;
    case 'area_low':
        $orderBy = "p.area_size ASC";
        break;
    case 'area_high':
        $orderBy = "p.area_size DESC";
        break;
}

// Get total count
$countQuery = "SELECT COUNT(*) as total FROM properties p 
               LEFT JOIN property_categories pc ON p.category_id = pc.id
               LEFT JOIN property_types pt ON p.type_id = pt.id
               LEFT JOIN areas a ON p.area_id = a.id
               LEFT JOIN cities c ON p.city_id = c.id
               LEFT JOIN states s ON p.state_id = s.id
               WHERE $whereClause";

$stmt = $conn->prepare($countQuery);
$stmt->execute($params);
$total = $stmt->fetch()['total'];

// Get properties
$query = "SELECT p.*, u.first_name, u.last_name, pc.name as category_name, pt.name as type_name,
          (SELECT image_path FROM property_images WHERE property_id = p.id AND is_cover = 1 LIMIT 1) as cover_image,
          (SELECT COUNT(*) FROM property_images WHERE property_id = p.id) as image_count
          FROM properties p
          LEFT JOIN users u ON p.user_id = u.id
          LEFT JOIN property_categories pc ON p.category_id = pc.id
          LEFT JOIN property_types pt ON p.type_id = pt.id
          LEFT JOIN areas a ON p.area_id = a.id
          LEFT JOIN cities c ON p.city_id = c.id
          LEFT JOIN states s ON p.state_id = s.id
          WHERE $whereClause
          ORDER BY $orderBy
          LIMIT $perPage OFFSET $offset";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$properties = $stmt->fetchAll();

// Get filters data
$categories = $conn->query("SELECT * FROM property_categories WHERE status = 'active'")->fetchAll();
$types = $conn->query("SELECT * FROM property_types WHERE status = 'active'")->fetchAll();
$cities = $conn->query("SELECT DISTINCT c.name FROM cities c JOIN states s ON c.state_id = s.id WHERE c.status = 'active' ORDER BY c.name")->fetchAll();
$states = $conn->query("SELECT * FROM states WHERE status = 'active' ORDER BY name")->fetchAll();

$pagination = getPagination($total, $perPage, $page);
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
        <div class="row">
            <!-- Filters Sidebar -->
            <div class="col-lg-3 mb-4">
                <div class="filter-sidebar">
                    <h5 class="mb-4">Filters</h5>
                    <form method="GET" action="<?php echo BASE_URL; ?>search.php">
                        <?php if ($sort): ?>
                            <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
                        <?php endif; ?>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-select">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['slug']; ?>" <?php echo $category === $cat['slug'] ? 'selected' : ''; ?>>
                                        <?php echo $cat['name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Property Type</label>
                            <select name="type" class="form-select">
                                <option value="">All Types</option>
                                <?php foreach ($types as $t): ?>
                                    <option value="<?php echo $t['slug']; ?>" <?php echo $type === $t['slug'] ? 'selected' : ''; ?>>
                                        <?php echo $t['name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" class="form-control" value="<?php echo $location; ?>" placeholder="Enter location">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">City</label>
                            <select name="city" class="form-select">
                                <option value="">All Cities</option>
                                <?php foreach ($cities as $c): ?>
                                    <option value="<?php echo $c['name']; ?>" <?php echo $city === $c['name'] ? 'selected' : ''; ?>>
                                        <?php echo $c['name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">State</label>
                            <select name="state" class="form-select">
                                <option value="">All States</option>
                                <?php foreach ($states as $s): ?>
                                    <option value="<?php echo $s['name']; ?>" <?php echo $state === $s['name'] ? 'selected' : ''; ?>>
                                        <?php echo $s['name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Min Price</label>
                                <input type="number" name="min_price" class="form-control" value="<?php echo $minPrice ? $minPrice : ''; ?>" placeholder="0">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Max Price</label>
                                <input type="number" name="max_price" class="form-control" value="<?php echo $maxPrice < 999999999 ? $maxPrice : ''; ?>" placeholder="Max">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">BHK</label>
                            <select name="bhk" class="form-select">
                                <option value="">Any</option>
                                <option value="1" <?php echo $bhk === '1' ? 'selected' : ''; ?>>1 BHK</option>
                                <option value="2" <?php echo $bhk === '2' ? 'selected' : ''; ?>>2 BHK</option>
                                <option value="3" <?php echo $bhk === '3' ? 'selected' : ''; ?>>3 BHK</option>
                                <option value="4" <?php echo $bhk === '4' ? 'selected' : ''; ?>>4 BHK</option>
                                <option value="5" <?php echo $bhk === '5' ? 'selected' : ''; ?>>5+ BHK</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Bathrooms</label>
                            <select name="bathroom" class="form-select">
                                <option value="">Any</option>
                                <option value="1" <?php echo $bathroom === '1' ? 'selected' : ''; ?>>1</option>
                                <option value="2" <?php echo $bathroom === '2' ? 'selected' : ''; ?>>2</option>
                                <option value="3" <?php echo $bathroom === '3' ? 'selected' : ''; ?>>3</option>
                                <option value="4" <?php echo $bathroom === '4' ? 'selected' : ''; ?>>4+</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Furnishing</label>
                            <select name="furnishing" class="form-select">
                                <option value="">Any</option>
                                <option value="unfurnished" <?php echo $furnishing === 'unfurnished' ? 'selected' : ''; ?>>Unfurnished</option>
                                <option value="semi_furnished" <?php echo $furnishing === 'semi_furnished' ? 'selected' : ''; ?>>Semi Furnished</option>
                                <option value="fully_furnished" <?php echo $furnishing === 'fully_furnished' ? 'selected' : ''; ?>>Fully Furnished</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Construction Status</label>
                            <select name="construction_status" class="form-select">
                                <option value="">Any</option>
                                <option value="ready_to_move" <?php echo $constructionStatus === 'ready_to_move' ? 'selected' : ''; ?>>Ready to Move</option>
                                <option value="under_construction" <?php echo $constructionStatus === 'under_construction' ? 'selected' : ''; ?>>Under Construction</option>
                                <option value="new_launch" <?php echo $constructionStatus === 'new_launch' ? 'selected' : ''; ?>>New Launch</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="featured" name="featured" <?php echo $featured ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="featured">Featured Only</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="premium" name="premium" <?php echo $premium ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="premium">Premium Only</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="verified" name="verified" <?php echo $verified ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="verified">Verified Only</label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                        <a href="<?php echo BASE_URL; ?>search.php" class="btn btn-outline-secondary w-100 mt-2">Clear Filters</a>
                    </form>
                </div>
            </div>

            <!-- Results -->
            <div class="col-lg-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5><?php echo $total; ?> Properties Found</h5>
                    <div class="d-flex align-items-center gap-2">
                        <label for="sortSelect" class="form-label mb-0 text-nowrap">Sort By</label>
                        <select class="form-select w-auto" id="sortSelect">
                            <option value="" <?php echo $sort === '' ? 'selected' : ''; ?>>Default (Featured First)</option>
                            <?php foreach (getSearchSortOptions() as $sortKey => $sortLabel): ?>
                                <option value="<?php echo $sortKey; ?>" <?php echo $sort === $sortKey ? 'selected' : ''; ?>>
                                    <?php echo $sortLabel; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <?php if (empty($properties)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No properties found matching your criteria.
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($properties as $property): ?>
                            <div class="col-md-4 mb-4">
                                <?php 
                                    $property = $property;
                                    include BASE_PATH . 'includes/property-card.php'; 
                                ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($pagination['totalPages'] > 1): ?>
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php echo !$pagination['hasPrev'] ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo buildSearchUrl(['page' => $page - 1]); ?>">Previous</a>
                                </li>
                                <?php for ($i = $pagination['start']; $i <= $pagination['end']; $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="<?php echo buildSearchUrl(['page' => $i]); ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo !$pagination['hasNext'] ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo buildSearchUrl(['page' => $page + 1]); ?>">Next</a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include BASE_PATH . 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
    <script>
        document.getElementById('sortSelect').addEventListener('change', function () {
            const url = new URL(window.location.href);

            if (this.value) {
                url.searchParams.set('sort', this.value);
            } else {
                url.searchParams.delete('sort');
            }

            url.searchParams.delete('page');
            window.location.href = url.toString();
        });
    </script>
</body>
</html>

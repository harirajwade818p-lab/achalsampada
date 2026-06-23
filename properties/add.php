<?php
require_once __DIR__ . '/../config/database.php';
require_once BASE_PATH . 'includes/functions.php';

session_start();

if (!isLoggedIn() || !isSeller()) {
    redirect('login.php');
}

$pageTitle = 'Add Property';
$user = getCurrentUser();

// Get form data
$categories = $conn->query("SELECT * FROM property_categories WHERE status = 'active'")->fetchAll();
$types = $conn->query("SELECT * FROM property_types WHERE status = 'active'")->fetchAll();
$states = $conn->query("SELECT * FROM states WHERE status = 'active' ORDER BY name")->fetchAll();
$amenities = $conn->query("SELECT * FROM amenities WHERE status = 'active' ORDER BY name")->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Generate slug
        $slug = generateSlug($_POST['title']);
        
        // Check if slug exists
        $stmt = $conn->prepare("SELECT id FROM properties WHERE slug = ?");
        $stmt->execute([$slug]);
        if ($stmt->fetch()) {
            $slug = $slug . '-' . time();
        }
        
        // Insert property
        $query = "INSERT INTO properties (
            user_id, category_id, type_id, title, slug, description, price, 
            area_size, area_unit, bhk, bathroom, balcony, floor_number, total_floors,
            furnishing, construction_status, possession_date, age_of_property, facing,
            parking, parking_slots, water_supply, power_backup, lift, security,
            gated_community, club_house, gym, pool, garden, play_area,
            street, area_id, city_id, state_id, pincode, landmark, rera_id,
            property_status, approval_status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'available', 'pending')";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([
            $user['id'],
            $_POST['category_id'],
            $_POST['type_id'],
            $_POST['title'],
            $slug,
            $_POST['description'],
            $_POST['price'],
            $_POST['area_size'],
            $_POST['area_unit'],
            $_POST['bhk'] ?: null,
            $_POST['bathroom'] ?: null,
            $_POST['balcony'] ?: null,
            $_POST['floor_number'] ?: null,
            $_POST['total_floors'] ?: null,
            $_POST['furnishing'],
            $_POST['construction_status'],
            $_POST['possession_date'] ?: null,
            $_POST['age_of_property'] ?: null,
            $_POST['facing'] ?: null,
            $_POST['parking'],
            $_POST['parking_slots'] ?: null,
            $_POST['water_supply'] ?: null,
            isset($_POST['power_backup']) ? 1 : 0,
            isset($_POST['lift']) ? 1 : 0,
            isset($_POST['security']) ? 1 : 0,
            isset($_POST['gated_community']) ? 1 : 0,
            isset($_POST['club_house']) ? 1 : 0,
            isset($_POST['gym']) ? 1 : 0,
            isset($_POST['pool']) ? 1 : 0,
            isset($_POST['garden']) ? 1 : 0,
            isset($_POST['play_area']) ? 1 : 0,
            $_POST['street'],
            $_POST['area_id'],
            $_POST['city_id'],
            $_POST['state_id'],
            $_POST['pincode'],
            $_POST['landmark'],
            $_POST['rera_id']
        ]);
        
        $propertyId = $conn->lastInsertId();
        
        // Upload images
        if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
            $images = reArrayFiles($_FILES['images']);
            foreach ($images as $index => $image) {
                if ($image['error'] === 0) {
                    $upload = uploadFile($image, 'property_images');
                    if ($upload['success']) {
                        $isCover = $index === 0 ? 1 : 0;
                        $stmt = $conn->prepare("INSERT INTO property_images (property_id, image_path, is_cover, display_order) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$propertyId, $upload['filePath'], $isCover, $index]);
                    }
                }
            }
        }
        
        // Upload video
        if (isset($_FILES['video']) && $_FILES['video']['error'] === 0) {
            $upload = uploadFile($_FILES['video'], 'property_videos', ['mp4', 'webm', 'mov']);
            if ($upload['success']) {
                $stmt = $conn->prepare("INSERT INTO property_videos (property_id, video_path, video_type) VALUES (?, ?, 'upload')");
                $stmt->execute([$propertyId, $upload['filePath']]);
            }
        }
        
        // Upload documents
        if (isset($_FILES['documents']) && !empty($_FILES['documents']['name'][0])) {
            $documents = reArrayFiles($_FILES['documents']);
            foreach ($documents as $document) {
                if ($document['error'] === 0) {
                    $upload = uploadFile($document, 'property_documents', ['pdf', 'jpg', 'jpeg', 'png']);
                    if ($upload['success']) {
                        $stmt = $conn->prepare("INSERT INTO property_documents (property_id, document_type, document_path) VALUES (?, 'general', ?)");
                        $stmt->execute([$propertyId, $upload['filePath']]);
                    }
                }
            }
        }
        
        // Add amenities
        if (isset($_POST['amenities']) && !empty($_POST['amenities'])) {
            foreach ($_POST['amenities'] as $amenityId) {
                $stmt = $conn->prepare("INSERT INTO property_amenities (property_id, amenity_id) VALUES (?, ?)");
                $stmt->execute([$propertyId, $amenityId]);
            }
        }
        
        logActivity($user['id'], 'create', 'property', 'Property added: ' . $_POST['title']);
        
        $success = 'Property added successfully! Pending approval.';
        
        // Redirect after 2 seconds
        echo "<script>setTimeout(function() { window.location.href = '" . BASE_URL . "properties/my-properties.php'; }, 2000);</script>";
        
    } catch (PDOException $e) {
        $error = 'Error adding property: ' . $e->getMessage();
    }
}

// Helper function to rearray files
function reArrayFiles(&$file_post) {
    $file_ary = array();
    $file_count = count($file_post['name']);
    $file_keys = array_keys($file_post);
    
    for ($i = 0; $i < $file_count; $i++) {
        foreach ($file_keys as $key) {
            $file_ary[$i][$key] = $file_post[$key][$i];
        }
    }
    
    return $file_ary;
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
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Add New Property</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data" id="propertyForm">
                            <!-- Basic Information -->
                            <h5 class="mb-3">Basic Information</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Property Title *</label>
                                    <input type="text" name="title" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Category *</label>
                                    <select name="category_id" class="form-select" required>
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Property Type *</label>
                                    <select name="type_id" class="form-select" required>
                                        <option value="">Select Type</option>
                                        <?php foreach ($types as $type): ?>
                                            <option value="<?php echo $type['id']; ?>"><?php echo $type['name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Price (₹) *</label>
                                    <input type="number" name="price" class="form-control" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Description *</label>
                                <textarea name="description" class="form-control" rows="5" required></textarea>
                            </div>

                            <!-- Property Details -->
                            <h5 class="mb-3 mt-4">Property Details</h5>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Area Size *</label>
                                    <input type="number" name="area_size" class="form-control" required step="0.01">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Area Unit *</label>
                                    <select name="area_unit" class="form-select" required>
                                        <option value="sqft">Sq. Ft.</option>
                                        <option value="sqyd">Sq. Yd.</option>
                                        <option value="sqm">Sq. M.</option>
                                        <option value="acre">Acre</option>
                                        <option value="ground">Ground</option>
                                        <option value="cents">Cents</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">BHK</label>
                                    <input type="number" name="bhk" class="form-control">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Bathrooms</label>
                                    <input type="number" name="bathroom" class="form-control">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Balconies</label>
                                    <input type="number" name="balcony" class="form-control">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Floor Number</label>
                                    <input type="number" name="floor_number" class="form-control">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Total Floors</label>
                                    <input type="number" name="total_floors" class="form-control">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Age of Property (Years)</label>
                                    <input type="number" name="age_of_property" class="form-control">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Furnishing</label>
                                    <select name="furnishing" class="form-select">
                                        <option value="unfurnished">Unfurnished</option>
                                        <option value="semi_furnished">Semi Furnished</option>
                                        <option value="fully_furnished">Fully Furnished</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Construction Status</label>
                                    <select name="construction_status" class="form-select">
                                        <option value="ready_to_move">Ready to Move</option>
                                        <option value="under_construction">Under Construction</option>
                                        <option value="new_launch">New Launch</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Possession Date</label>
                                    <input type="date" name="possession_date" class="form-control">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Facing</label>
                                    <select name="facing" class="form-select">
                                        <option value="">Select</option>
                                        <option value="north">North</option>
                                        <option value="south">South</option>
                                        <option value="east">East</option>
                                        <option value="west">West</option>
                                        <option value="north_east">North East</option>
                                        <option value="north_west">North West</option>
                                        <option value="south_east">South East</option>
                                        <option value="south_west">South West</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Amenities -->
                            <h5 class="mb-3 mt-4">Amenities</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Parking</label>
                                    <select name="parking" class="form-select">
                                        <option value="none">None</option>
                                        <option value="open">Open</option>
                                        <option value="covered">Covered</option>
                                        <option value="both">Both</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Parking Slots</label>
                                    <input type="number" name="parking_slots" class="form-control">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="power_backup" id="power_backup">
                                        <label class="form-check-label" for="power_backup">Power Backup</label>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="lift" id="lift">
                                        <label class="form-check-label" for="lift">Lift</label>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="security" id="security">
                                        <label class="form-check-label" for="security">Security</label>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="gated_community" id="gated_community">
                                        <label class="form-check-label" for="gated_community">Gated Community</label>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="club_house" id="club_house">
                                        <label class="form-check-label" for="club_house">Club House</label>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="gym" id="gym">
                                        <label class="form-check-label" for="gym">Gym</label>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="pool" id="pool">
                                        <label class="form-check-label" for="pool">Swimming Pool</label>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="garden" id="garden">
                                        <label class="form-check-label" for="garden">Garden</label>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="play_area" id="play_area">
                                        <label class="form-check-label" for="play_area">Play Area</label>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Additional Amenities</label>
                                <div class="row">
                                    <?php foreach ($amenities as $amenity): ?>
                                        <div class="col-md-4 mb-2">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" name="amenities[]" value="<?php echo $amenity['id']; ?>" id="amenity_<?php echo $amenity['id']; ?>">
                                                <label class="form-check-label" for="amenity_<?php echo $amenity['id']; ?>">
                                                    <?php echo $amenity['name']; ?>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Location -->
                            <h5 class="mb-3 mt-4">Location</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">State *</label>
                                    <select name="state_id" class="form-select" required id="stateSelect">
                                        <option value="">Select State</option>
                                        <?php foreach ($states as $state): ?>
                                            <option value="<?php echo $state['id']; ?>"><?php echo $state['name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">City *</label>
                                    <select name="city_id" class="form-select" required id="citySelect">
                                        <option value="">Select City</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Area *</label>
                                    <select name="area_id" class="form-select" required id="areaSelect">
                                        <option value="">Select Area</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Pincode</label>
                                    <input type="text" name="pincode" class="form-control">
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Street Address</label>
                                    <input type="text" name="street" class="form-control" required>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Landmark</label>
                                    <input type="text" name="landmark" class="form-control">
                                </div>
                            </div>

                            <!-- RERA -->
                            <h5 class="mb-3 mt-4">RERA Details</h5>
                            <div class="mb-3">
                                <label class="form-label">RERA ID</label>
                                <input type="text" name="rera_id" class="form-control">
                            </div>

                            <!-- Images -->
                            <h5 class="mb-3 mt-4">Property Images</h5>
                            <div class="mb-3">
                                <label class="form-label">Upload Images (Max 10)</label>
                                <input type="file" name="images[]" class="form-control" multiple accept="image/*" required>
                                <small class="text-muted">First image will be set as cover image</small>
                            </div>

                            <!-- Video -->
                            <h5 class="mb-3 mt-4">Property Video</h5>
                            <div class="mb-3">
                                <label class="form-label">Upload Video (Optional)</label>
                                <input type="file" name="video" class="form-control" accept="video/*">
                            </div>

                            <!-- Documents -->
                            <h5 class="mb-3 mt-4">Property Documents</h5>
                            <div class="mb-3">
                                <label class="form-label">Upload Documents (PDF, JPG, PNG)</label>
                                <input type="file" name="documents[]" class="form-control" multiple accept=".pdf,.jpg,.jpeg,.png">
                            </div>

                            <div class="text-end mt-4">
                                <a href="<?php echo BASE_URL; ?>properties/my-properties.php" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">Submit Property</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include BASE_PATH . 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Load cities on state change
        document.getElementById('stateSelect').addEventListener('change', function() {
            const stateId = this.value;
            fetch(BASE_URL + 'api/get-cities.php?state_id=' + stateId)
                .then(response => response.json())
                .then(data => {
                    const citySelect = document.getElementById('citySelect');
                    citySelect.innerHTML = '<option value="">Select City</option>';
                    data.forEach(city => {
                        citySelect.innerHTML += '<option value="' + city.id + '">' + city.name + '</option>';
                    });
                });
        });

        // Load areas on city change
        document.getElementById('citySelect').addEventListener('change', function() {
            const cityId = this.value;
            fetch(BASE_URL + 'api/get-areas.php?city_id=' + cityId)
                .then(response => response.json())
                .then(data => {
                    const areaSelect = document.getElementById('areaSelect');
                    areaSelect.innerHTML = '<option value="">Select Area</option>';
                    data.forEach(area => {
                        areaSelect.innerHTML += '<option value="' + area.id + '">' + area.name + '</option>';
                    });
                });
        });
    </script>
</body>
</html>

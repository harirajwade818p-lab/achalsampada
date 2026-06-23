<?php
$property = $property ?? null;
if (!$property) return;

$coverImage = $property['cover_image'] ?? BASE_URL . 'assets/images/no-image.jpg';
$price = formatPrice($property['price']);
$area = $property['area_size'] . ' ' . strtoupper($property['area_unit']);
$inWishlist = isInWishlist($property['id']);
?>
<div class="property-card">
    <div class="card h-100 border-0 shadow-sm">
        <div class="position-relative">
            <img src="<?php echo BASE_URL . $coverImage; ?>" class="card-img-top" alt="<?php echo $property['title']; ?>" style="height: 200px; object-fit: cover;">
            <div class="position-absolute top-0 start-0 m-2">
                <?php if ($property['is_featured']): ?>
                    <span class="badge bg-warning text-dark">Featured</span>
                <?php endif; ?>
                <?php if ($property['is_premium']): ?>
                    <span class="badge bg-primary">Premium</span>
                <?php endif; ?>
                <?php if ($property['is_verified']): ?>
                    <span class="badge bg-success"><i class="fas fa-check-circle"></i> Verified</span>
                <?php endif; ?>
            </div>
            <div class="position-absolute top-0 end-0 m-2">
                <button class="btn btn-sm btn-light wishlist-btn" data-property Id="<?php echo $property['id']; ?>">
                    <i class="fas fa-heart <?php echo $inWishlist ? 'text-danger' : ''; ?>"></i>
                </button>
            </div>
            <div class="position-absolute bottom-0 start-0 m-2">
                <span class="badge bg-secondary"><?php echo ucfirst($property['property_status']); ?></span>
            </div>
        </div>
        <div class="card-body">
            <h5 class="card-title">
                <a href="<?php echo BASE_URL; ?>property/<?php echo $property['slug']; ?>" class="text-decoration-none text-dark">
                    <?php echo substr($property['title'], 0, 50); ?>...
                </a>
            </h5>
            <p class="card-text text-muted mb-2">
                <i class="fas fa-map-marker-alt"></i> <?php echo $property['city']; ?>, <?php echo $property['state']; ?>
            </p>
            <div class="property-specs mb-3">
                <span class="badge bg-light text-dark"><i class="fas fa-bed"></i> <?php echo $property['bhk'] ?? 'N/A'; ?> BHK</span>
                <span class="badge bg-light text-dark"><i class="fas fa-bath"></i> <?php echo $property['bathroom'] ?? 'N/A'; ?></span>
                <span class="badge bg-light text-dark"><i class="fas fa-ruler-combined"></i> <?php echo $area; ?></span>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <span class="price">₹<?php echo $price; ?></span>
                <small class="text-muted"><?php echo timeAgo($property['created_at']); ?></small>
            </div>
        </div>
    </div>
</div>

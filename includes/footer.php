    <!-- Footer -->
    <footer class="footer bg-dark text-white mt-5">
        <div class="container py-5">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h4><?php echo SITE_NAME; ?></h4>
                    <p>Your trusted partner in finding the perfect property. We help you buy, sell, and rent properties across India.</p>
                    <div class="social-links">
                        <a href="#" class="text-white me-3"><i class="fab fa-facebook fa-lg"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-twitter fa-lg"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-instagram fa-lg"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-linkedin fa-lg"></i></a>
                    </div>
                </div>
                <div class="col-md-2 mb-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo BASE_URL; ?>" class="text-white text-decoration-none">Home</a></li>
                        <li><a href="<?php echo BASE_URL; ?>search.php" class="text-white text-decoration-none">Search</a></li>
                        <li><a href="<?php echo BASE_URL; ?>agents.php" class="text-white text-decoration-none">Agents</a></li>
                        <li><a href="<?php echo BASE_URL; ?>builders.php" class="text-white text-decoration-none">Builders</a></li>
                    </ul>
                </div>
                <div class="col-md-2 mb-4">
                    <h5>Property Types</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo BASE_URL; ?>search.php?type=flat-apartment" class="text-white text-decoration-none">Flats</a></li>
                        <li><a href="<?php echo BASE_URL; ?>search.php?type=house-villa" class="text-white text-decoration-none">Houses</a></li>
                        <li><a href="<?php echo BASE_URL; ?>search.php?type=plot-land" class="text-white text-decoration-none">Plots</a></li>
                        <li><a href="<?php echo BASE_URL; ?>search.php?type=commercial" class="text-white text-decoration-none">Commercial</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-4">
                    <h5>Contact Us</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-map-marker-alt me-2"></i> Mumbai, India</li>
                        <li><i class="fas fa-phone me-2"></i> +91 1234567890</li>
                        <li><i class="fas fa-envelope me-2"></i> info@propertymarketplace.com</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="bg-dark border-top border-secondary">
            <div class="container py-3">
                <div class="row">
                    <div class="col-md-6 text-center text-md-start">
                        <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
                    </div>
                    <div class="col-md-6 text-center text-md-end">
                        <a href="#" class="text-white text-decoration-none me-3">Privacy Policy</a>
                        <a href="#" class="text-white text-decoration-none">Terms & Conditions</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>

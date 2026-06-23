// Main JavaScript File

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Wishlist toggle
    document.querySelectorAll('.wishlist-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const propertyId = this.dataset.propertyId;
            const icon = this.querySelector('i');
            
            fetch(BASE_URL + 'api/toggle-wishlist.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'property_id=' + propertyId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    icon.classList.toggle('text-danger');
                    icon.classList.toggle('far');
                    icon.classList.toggle('fas');
                    showToast(data.message, 'success');
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred', 'error');
            });
        });
    });

    // Property gallery
    document.querySelectorAll('.property-gallery img').forEach(function(img) {
        img.addEventListener('click', function() {
            document.querySelectorAll('.property-gallery img').forEach(i => i.classList.remove('active'));
            this.classList.add('active');
            document.getElementById('mainImage').src = this.src;
        });
    });

    // Search form validation
    const searchForm = document.querySelector('.search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            const location = this.querySelector('input[name="location"]').value;
            if (!location) {
                e.preventDefault();
                showToast('Please enter a location', 'warning');
            }
        });
    }

    // Price range slider
    const minPrice = document.getElementById('minPrice');
    const maxPrice = document.getElementById('maxPrice');
    const priceRange = document.getElementById('priceRange');
    
    if (minPrice && maxPrice && priceRange) {
        minPrice.addEventListener('input', updatePriceRange);
        maxPrice.addEventListener('input', updatePriceRange);
    }

    function updatePriceRange() {
        const min = parseInt(minPrice.value) || 0;
        const max = parseInt(maxPrice.value) || 10000000;
        priceRange.textContent = '₹' + min.toLocaleString() + ' - ₹' + max.toLocaleString();
    }

    // Image preview
    const imageInput = document.querySelector('input[type="file"][accept*="image"]');
    if (imageInput) {
        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('imagePreview');
                    if (preview) {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Confirm delete
    document.querySelectorAll('.delete-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this item?')) {
                e.preventDefault();
            }
        });
    });

    // Auto-hide alerts
    setTimeout(function() {
        document.querySelectorAll('.alert').forEach(function(alert) {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.remove();
            }, 500);
        });
    }, 5000);
});

// Toast notification function
function showToast(message, type = 'info') {
    const toastContainer = document.querySelector('.toast-container') || createToastContainer();
    
    const toast = document.createElement('div');
    toast.className = 'toast align-items-center text-white bg-' + getToastColor(type);
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    
    const bsToast = new bootstrap.Toast(toast, {
        delay: 3000
    });
    
    bsToast.show();
    
    toast.addEventListener('hidden.bs.toast', function() {
        toast.remove();
    });
}

function createToastContainer() {
    const container = document.createElement('div');
    container.className = 'toast-container';
    document.body.appendChild(container);
    return container;
}

function getToastColor(type) {
    switch(type) {
        case 'success': return 'success';
        case 'error': return 'danger';
        case 'warning': return 'warning';
        default: return 'info';
    }
}

// Format currency
function formatCurrency(amount) {
    return '₹' + amount.toLocaleString('en-IN');
}

// Debounce function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Copy to clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        showToast('Copied to clipboard!', 'success');
    }, function(err) {
        showToast('Failed to copy', 'error');
    });
}

// Share property
function shareProperty(url, title) {
    if (navigator.share) {
        navigator.share({
            title: title,
            url: url
        }).catch(console.error);
    } else {
        copyToClipboard(url);
    }
}

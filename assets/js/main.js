/**
 * AgriSphere Main JavaScript
 */

// DOM Ready
document.addEventListener('DOMContentLoaded', function() {
    initMobileMenu();
    initModals();
    initAlerts();
    initQuantityControls();
    initFormValidation();
});

/**
 * Mobile Menu Toggle
 */
function initMobileMenu() {
    const menuToggle = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.sidebar');

    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
        });

        // Close sidebar when clicking outside
        document.addEventListener('click', function(e) {
            if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
    }
}

/**
 * Modal Functionality
 */
function initModals() {
    // Open modal
    document.querySelectorAll('[data-modal]').forEach(trigger => {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            const modalId = this.getAttribute('data-modal');
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        });
    });

    // Close modal
    document.querySelectorAll('.modal-close, .modal-overlay').forEach(closer => {
        closer.addEventListener('click', function(e) {
            if (e.target === this) {
                const modal = this.closest('.modal-overlay');
                if (modal) {
                    modal.classList.remove('active');
                    document.body.style.overflow = '';
                }
            }
        });
    });

    // Close on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.active').forEach(modal => {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            });
        }
    });
}

/**
 * Open Modal by ID
 */
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

/**
 * Close Modal by ID
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

/**
 * Auto-dismiss Alerts
 */
function initAlerts() {
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
}

/**
 * Quantity +/- Controls
 */
function initQuantityControls() {
    document.querySelectorAll('.quantity-control').forEach(control => {
        const minusBtn = control.querySelector('.qty-minus');
        const plusBtn = control.querySelector('.qty-plus');
        const input = control.querySelector('.qty-input');

        if (minusBtn && plusBtn && input) {
            minusBtn.addEventListener('click', function() {
                let value = parseInt(input.value) || 1;
                if (value > 1) {
                    input.value = value - 1;
                    input.dispatchEvent(new Event('change'));
                }
            });

            plusBtn.addEventListener('click', function() {
                let value = parseInt(input.value) || 1;
                let max = parseInt(input.getAttribute('max')) || 999;
                if (value < max) {
                    input.value = value + 1;
                    input.dispatchEvent(new Event('change'));
                }
            });
        }
    });
}

/**
 * Form Validation
 */
function initFormValidation() {
    document.querySelectorAll('form[data-validate]').forEach(form => {
        form.addEventListener('submit', function(e) {
            let isValid = true;

            // Check required fields
            form.querySelectorAll('[required]').forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    showFieldError(field, 'This field is required');
                } else {
                    clearFieldError(field);
                }
            });

            // Check email fields
            form.querySelectorAll('input[type="email"]').forEach(field => {
                if (field.value && !isValidEmail(field.value)) {
                    isValid = false;
                    showFieldError(field, 'Please enter a valid email');
                }
            });

            // Check password match
            const password = form.querySelector('input[name="password"]');
            const confirmPassword = form.querySelector('input[name="confirm_password"]');
            if (password && confirmPassword && password.value !== confirmPassword.value) {
                isValid = false;
                showFieldError(confirmPassword, 'Passwords do not match');
            }

            if (!isValid) {
                e.preventDefault();
            }
        });
    });
}

/**
 * Show Field Error
 */
function showFieldError(field, message) {
    clearFieldError(field);
    field.classList.add('error');
    const errorDiv = document.createElement('div');
    errorDiv.className = 'form-error';
    errorDiv.textContent = message;
    field.parentNode.appendChild(errorDiv);
}

/**
 * Clear Field Error
 */
function clearFieldError(field) {
    field.classList.remove('error');
    const existingError = field.parentNode.querySelector('.form-error');
    if (existingError) {
        existingError.remove();
    }
}

/**
 * Email Validation
 */
function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

/**
 * Format Currency
 */
function formatCurrency(amount) {
    return '৳' + parseFloat(amount).toFixed(2);
}

/**
 * Show Loading State
 */
function showLoading(element) {
    element.classList.add('loading');
    element.disabled = true;
    const originalText = element.innerHTML;
    element.setAttribute('data-original-text', originalText);
    element.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
}

/**
 * Hide Loading State
 */
function hideLoading(element) {
    element.classList.remove('loading');
    element.disabled = false;
    const originalText = element.getAttribute('data-original-text');
    if (originalText) {
        element.innerHTML = originalText;
    }
}

/**
 * AJAX Helper
 */
async function fetchData(url, options = {}) {
    try {
        const response = await fetch(url, {
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            ...options
        });

        if (!response.ok) {
            throw new Error('Network response was not ok');
        }

        return await response.json();
    } catch (error) {
        console.error('Fetch error:', error);
        throw error;
    }
}

/**
 * Show Toast Notification
 */
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type}`;
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        animation: slideIn 0.3s ease;
    `;
    toast.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
        ${message}
    `;

    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

/**
 * Debounce Function
 */
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

/**
 * Confirm Action
 */
function confirmAction(message) {
    return confirm(message);
}

/**
 * Add to Cart (AJAX)
 */
async function addToCart(productId, quantity = 1) {
    try {
        const response = await fetchData(BASE_URL + 'customer/cart_actions.php', {
            method: 'POST',
            body: JSON.stringify({
                action: 'add',
                product_id: productId,
                quantity: quantity
            })
        });

        if (response.success) {
            showToast('Product added to cart!', 'success');
            updateCartBadge(response.cartCount);
        } else {
            showToast(response.message || 'Failed to add to cart', 'error');
        }
    } catch (error) {
        showToast('An error occurred', 'error');
    }
}

/**
 * Update Cart Badge
 */
function updateCartBadge(count) {
    const badges = document.querySelectorAll('.sidebar-nav-badge');
    badges.forEach(badge => {
        if (badge.closest('a[href*="cart"]')) {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'inline-flex' : 'none';
        }
    });
}

/**
 * Get Government Price (AJAX)
 */
async function getGovernmentPrice(productName, categoryId) {
    try {
        const response = await fetchData(
            `${BASE_URL}api/get_govt_price.php?product=${encodeURIComponent(productName)}&category=${categoryId}`
        );
        return response;
    } catch (error) {
        console.error('Error fetching government price:', error);
        return null;
    }
}

// Add base URL for JS
const BASE_URL = document.querySelector('meta[name="base-url"]')?.content || '/Agrisphere/';

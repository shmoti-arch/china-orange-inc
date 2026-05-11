<?php
// Start output buffering to prevent header issues
ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>China Orange Inc - Premium Oranges from China</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center min-vh-100">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold text-white mb-4">
                        Premium Chinese Oranges
                        <span class="text-warning">Delivered Fresh</span>
                    </h1>
                    <p class="lead text-white-50 mb-4">
                        Experience the finest oranges from China's premium orchards. 
                        Sweet, juicy, and packed with vitamins - delivered straight to your door.
                    </p>
                    <div class="d-flex gap-3 mb-4">
                        <div class="feature-badge">
                            <i class="fas fa-truck text-warning"></i>
                            <span>Free Shipping</span>
                        </div>
                        <div class="feature-badge">
                            <i class="fas fa-leaf text-warning"></i>
                            <span>100% Organic</span>
                        </div>
                        <div class="feature-badge">
                            <i class="fas fa-medal text-warning"></i>
                            <span>Premium Quality</span>
                        </div>
                    </div>
                    <a href="#order-section" class="btn btn-warning btn-lg px-5 py-3">
                        <i class="fas fa-shopping-cart me-2"></i>Order Now
                    </a>
                </div>
                <div class="col-lg-6">
                    <div class="hero-image">
                        <img src="/assets/images/oranges-hero.jpg" alt="Fresh Chinese Oranges" class="img-fluid rounded-3 shadow-lg">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-12">
                    <h2 class="display-5 fw-bold mb-3">Why Choose Our Oranges?</h2>
                    <p class="lead text-muted">Premium quality oranges sourced directly from China's finest orchards</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card h-100">
                        <div class="feature-icon">
                            <i class="fas fa-seedling"></i>
                        </div>
                        <h4>100% Organic</h4>
                        <p>Grown without pesticides or harmful chemicals, ensuring pure and natural taste.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card h-100">
                        <div class="feature-icon">
                            <i class="fas fa-shipping-fast"></i>
                        </div>
                        <h4>Fast Delivery</h4>
                        <p>Fresh oranges delivered within 3-5 business days with temperature-controlled shipping.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card h-100">
                        <div class="feature-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <h4>Best Prices</h4>
                        <p>Direct from farm pricing at just $2.50/kg - unbeatable value for premium quality.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Order Section -->
    <section id="order-section" class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="order-card">
                        <div class="text-center mb-4">
                            <h2 class="display-6 fw-bold mb-3">Place Your Order</h2>
                            <p class="lead text-muted">Minimum order: 5kg | Price: $2.50 per kg</p>
                        </div>
                        
                        <form id="orderForm" class="needs-validation" novalidate>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="weight" class="form-label">Weight (kg) *</label>
                                    <input type="number" class="form-control form-control-lg" id="weight" min="5" step="0.5" required>
                                    <div class="invalid-feedback">Minimum order is 5kg</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="totalPrice" class="form-label">Total Price</label>
                                    <input type="text" class="form-control form-control-lg" id="totalPrice" readonly>
                                </div>
                                <div class="col-12">
                                    <label for="address" class="form-label">Shipping Address *</label>
                                    <textarea class="form-control" id="address" rows="3" required></textarea>
                                    <div class="invalid-feedback">Please provide a shipping address</div>
                                </div>
                                <div class="col-12">
                                    <label for="notes" class="form-label">Special Instructions (Optional)</label>
                                    <textarea class="form-control" id="notes" rows="2"></textarea>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-warning btn-lg w-100 py-3">
                                        <i class="fas fa-shopping-cart me-2"></i>Place Order - $<span id="orderTotal">0.00</span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/main.js"></script>
</body>
</html>

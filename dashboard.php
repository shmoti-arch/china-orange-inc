<?php
require_once 'config/session.php';
requireLogin();

$db = new Database();
$conn = $db->getConnection();

// Get user orders
$stmt = $conn->prepare("
    SELECT id, weight_kg, total_price, status, shipping_address, notes, created_at, updated_at 
    FROM orders 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get order statistics
$stmt = $conn->prepare("
    SELECT 
        status,
        COUNT(*) as count,
        COALESCE(SUM(total_price), 0) as total_spent
    FROM orders 
    WHERE user_id = ? 
    GROUP BY status
");
$stmt->execute([$_SESSION['user_id']]);
$stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

$orderStats = [];
$totalSpent = 0;
foreach ($stats as $stat) {
    $orderStats[$stat['status']] = $stat['count'];
    $totalSpent += $stat['total_spent'];
}

$balance = getUserBalance();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - China Orange Inc</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container py-4">
        <!-- Welcome Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-1">Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
                            <p class="text-muted mb-0">Manage your orange orders and track deliveries</p>
                        </div>
                        <div class="text-end">
                            <div class="stat-card d-inline-block">
                                <h3 class="user-balance">$<?php echo number_format($balance, 2); ?></h3>
                                <p>Account Balance</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="dashboard-card text-center">
                    <div class="feature-icon mx-auto mb-3" style="width: 60px; height: 60px;">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h4 id="count-total"><?php echo array_sum($orderStats); ?></h4>
                    <p class="text-muted mb-0">Total Orders</p>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="dashboard-card text-center">
                    <div class="feature-icon mx-auto mb-3" style="width: 60px; height: 60px;">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h4 id="count-pending"><?php echo $orderStats['pending'] ?? 0; ?></h4>
                    <p class="text-muted mb-0">Pending Orders</p>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="dashboard-card text-center">
                    <div class="feature-icon mx-auto mb-3" style="width: 60px; height: 60px;">
                        <i class="fas fa-truck"></i>
                    </div>
                    <h4 id="count-shipped"><?php echo $orderStats['shipped'] ?? 0; ?></h4>
                    <p class="text-muted mb-0">Shipped Orders</p>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="dashboard-card text-center">
                    <div class="feature-icon mx-auto mb-3" style="width: 60px; height: 60px;">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <h4>$<?php echo number_format($totalSpent, 2); ?></h4>
                    <p class="text-muted mb-0">Total Spent</p>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Orders Section -->
            <div class="col-lg-8 mb-4">
                <div class="dashboard-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="mb-0">Your Orders</h4>
                        <a href="/" class="btn btn-warning">
                            <i class="fas fa-plus me-2"></i>New Order
                        </a>
                    </div>

                    <?php if (empty($orders)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-shopping-cart text-muted" style="font-size: 4rem;"></i>
                        <h5 class="mt-3 text-muted">No orders yet</h5>
                        <p class="text-muted">Start by placing your first order for premium Chinese oranges!</p>
                        <a href="/" class="btn btn-warning">Place Your First Order</a>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Weight</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><strong>#<?php echo $order['id']; ?></strong></td>
                                    <td><?php echo $order['weight_kg']; ?> kg</td>
                                    <td>$<?php echo number_format($order['total_price'], 2); ?></td>
                                    <td>
                                        <span class="badge status-<?php echo $order['status']; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="viewOrder(<?php echo $order['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($order['status'] === 'pending'): ?>
                                        <button class="btn btn-sm btn-outline-danger" onclick="cancelOrder(<?php echo $order['id']; ?>)">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Chat Section -->
            <div class="col-lg-4 mb-4">
                <div class="dashboard-card">
                    <h4 class="mb-3">
                        <i class="fas fa-comments text-warning me-2"></i>Support Chat
                    </h4>
                    
                    <div id="chatMessages" class="chat-container mb-3"></div>
                    
                    <form id="chatForm">
                        <div class="input-group">
                            <input type="text" class="form-control" id="messageInput" placeholder="Type your message...">
                            <button class="btn btn-warning" type="submit">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div class="modal fade" id="orderModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Order Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="orderModalBody">
                    <!-- Order details will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/main.js"></script>
    <script>
        function viewOrder(orderId) {
            fetch(`/api/orders.php?id=${orderId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const order = data.order;
                    document.getElementById('orderModalBody').innerHTML = `
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Order Information</h6>
                                <p><strong>Order ID:</strong> #${order.id}</p>
                                <p><strong>Weight:</strong> ${order.weight_kg} kg</p>
                                <p><strong>Price per kg:</strong> $${parseFloat(order.price_per_kg).toFixed(2)}</p>
                                <p><strong>Total Price:</strong> $${parseFloat(order.total_price).toFixed(2)}</p>
                                <p><strong>Status:</strong> <span class="badge status-${order.status}">${order.status.charAt(0).toUpperCase() + order.status.slice(1)}</span></p>
                            </div>
                            <div class="col-md-6">
                                <h6>Shipping Details</h6>
                                <p><strong>Address:</strong><br>${order.shipping_address.replace(/\n/g, '<br>')}</p>
                                ${order.notes ? `<p><strong>Notes:</strong><br>${order.notes}</p>` : ''}
                                <p><strong>Order Date:</strong> ${formatDate(order.created_at)}</p>
                                ${order.updated_at !== order.created_at ? `<p><strong>Last Updated:</strong> ${formatDate(order.updated_at)}</p>` : ''}
                            </div>
                        </div>
                    `;
                    new bootstrap.Modal(document.getElementById('orderModal')).show();
                } else {
                    showAlert('Failed to load order details', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Failed to load order details', 'danger');
            });
        }

        function cancelOrder(orderId) {
            if (confirm('Are you sure you want to cancel this order?')) {
                fetch('/api/orders.php', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: orderId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('Order cancelled successfully', 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showAlert(data.message || 'Failed to cancel order', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('Failed to cancel order', 'danger');
                });
            }
        }
    </script>
</body>
</html>
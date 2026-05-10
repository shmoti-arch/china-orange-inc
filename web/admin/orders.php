<?php
require_once '../config/session.php';
requireAdmin();

$db = new Database();
$conn = $db->getConnection();

$message = '';
$messageType = '';

// Handle order status updates
if ($_POST && isset($_POST['update_status'])) {
    $orderId = $_POST['order_id'];
    $newStatus = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE orders SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    if ($stmt->execute([$newStatus, $orderId])) {
        $message = 'Order status updated successfully';
        $messageType = 'success';
    } else {
        $message = 'Failed to update order status';
        $messageType = 'danger';
    }
}

// Get all orders with user information
$stmt = $conn->prepare("
    SELECT o.id, o.weight_kg, o.price_per_kg, o.total_price, o.status, 
           o.shipping_address, o.notes, o.created_at, o.updated_at,
           u.username, u.email
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
");
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$viewOrder = null;
if (isset($_GET['view'])) {
    $viewOrderId = $_GET['view'];
    foreach ($orders as $order) {
        if ($order['id'] == $viewOrderId) {
            $viewOrder = $order;
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management - China Orange Inc</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Admin Sidebar -->
            <div class="col-md-3 col-lg-2 admin-sidebar">
                <nav class="nav flex-column">
                    <a class="nav-link" href="/admin/">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                    <a class="nav-link active" href="/admin/orders.php">
                        <i class="fas fa-shopping-cart me-2"></i>Orders
                    </a>
                    <a class="nav-link" href="/admin/users.php">
                        <i class="fas fa-users me-2"></i>Users
                    </a>
                    <a class="nav-link" href="/admin/chat.php">
                        <i class="fas fa-comments me-2"></i>Chat Support
                    </a>
                    <a class="nav-link" href="/admin/settings.php">
                        <i class="fas fa-cog me-2"></i>Settings
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 admin-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Order Management</h2>
                    <div class="btn-group">
                        <button class="btn btn-outline-primary" onclick="filterOrders('all')">All</button>
                        <button class="btn btn-outline-warning" onclick="filterOrders('pending')">Pending</button>
                        <button class="btn btn-outline-info" onclick="filterOrders('processing')">Processing</button>
                        <button class="btn btn-outline-primary" onclick="filterOrders('shipped')">Shipped</button>
                        <button class="btn btn-outline-success" onclick="filterOrders('delivered')">Delivered</button>
                    </div>
                </div>

                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <div class="dashboard-card">
                    <?php if (empty($orders)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-shopping-cart text-muted" style="font-size: 4rem;"></i>
                        <h5 class="mt-3 text-muted">No orders found</h5>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover" id="ordersTable">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Weight</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Order Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                <tr data-status="<?php echo $order['status']; ?>">
                                    <td><strong>#<?php echo $order['id']; ?></strong></td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($order['username']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($order['email']); ?></small>
                                        </div>
                                    </td>
                                    <td><?php echo $order['weight_kg']; ?> kg</td>
                                    <td>$<?php echo number_format($order['total_price'], 2); ?></td>
                                    <td>
                                        <span class="badge status-<?php echo $order['status']; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?>
                                        <?php if ($order['updated_at'] !== $order['created_at']): ?>
                                        <br><small class="text-muted">Updated: <?php echo date('M j, Y g:i A', strtotime($order['updated_at'])); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" onclick="viewOrderDetails(<?php echo $order['id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-success" onclick="showStatusModal(<?php echo $order['id']; ?>, '<?php echo $order['status']; ?>')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div class="modal fade" id="orderDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Order Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="orderDetailsBody">
                    <!-- Order details will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Order Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="order_id" id="statusOrderId">
                        <div class="mb-3">
                            <label for="status" class="form-label">New Status</label>
                            <select class="form-select" name="status" id="statusSelect" required>
                                <option value="pending">Pending</option>
                                <option value="processing">Processing</option>
                                <option value="shipped">Shipped</option>
                                <option value="delivered">Delivered</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_status" class="btn btn-warning">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/main.js"></script>
    <script>
        const orders = <?php echo json_encode($orders); ?>;

        function viewOrderDetails(orderId) {
            const order = orders.find(o => o.id == orderId);
            if (!order) return;

            document.getElementById('orderDetailsBody').innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Order Information</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Order ID:</strong></td><td>#${order.id}</td></tr>
                            <tr><td><strong>Weight:</strong></td><td>${order.weight_kg} kg</td></tr>
                            <tr><td><strong>Price per kg:</strong></td><td>$${parseFloat(order.price_per_kg).toFixed(2)}</td></tr>
                            <tr><td><strong>Total Price:</strong></td><td>$${parseFloat(order.total_price).toFixed(2)}</td></tr>
                            <tr><td><strong>Status:</strong></td><td><span class="badge status-${order.status}">${order.status.charAt(0).toUpperCase() + order.status.slice(1)}</span></td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Customer Information</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Name:</strong></td><td>${order.username}</td></tr>
                            <tr><td><strong>Email:</strong></td><td>${order.email}</td></tr>
                        </table>
                        <h6>Shipping Address</h6>
                        <p class="border p-2 rounded">${order.shipping_address.replace(/\n/g, '<br>')}</p>
                        ${order.notes ? `<h6>Notes</h6><p class="border p-2 rounded">${order.notes}</p>` : ''}
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <h6>Timeline</h6>
                        <p><strong>Order Date:</strong> ${formatDate(order.created_at)}</p>
                        ${order.updated_at !== order.created_at ? `<p><strong>Last Updated:</strong> ${formatDate(order.updated_at)}</p>` : ''}
                    </div>
                </div>
            `;
            
            new bootstrap.Modal(document.getElementById('orderDetailsModal')).show();
        }

        function showStatusModal(orderId, currentStatus) {
            document.getElementById('statusOrderId').value = orderId;
            document.getElementById('statusSelect').value = currentStatus;
            new bootstrap.Modal(document.getElementById('statusModal')).show();
        }

        function filterOrders(status) {
            const rows = document.querySelectorAll('#ordersTable tbody tr');
            rows.forEach(row => {
                if (status === 'all' || row.dataset.status === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Update active button
            document.querySelectorAll('.btn-group .btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
        }
    </script>
</body>
</html>

<?php
require_once '../config/session.php';
requireAdmin();

$db = new Database();
$conn = $db->getConnection();

// Get admin statistics
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_orders,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_orders,
        COUNT(CASE WHEN status = 'processing' THEN 1 END) as processing_orders,
        COUNT(CASE WHEN status = 'shipped' THEN 1 END) as shipped_orders,
        COUNT(CASE WHEN status = 'delivered' THEN 1 END) as delivered_orders,
        COALESCE(SUM(total_price), 0) as total_revenue
    FROM orders
");
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get total users
$stmt = $conn->prepare("SELECT COUNT(*) as total_users FROM users WHERE is_admin = FALSE");
$stmt->execute();
$userStats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get recent orders
$stmt = $conn->prepare("
    SELECT o.id, o.weight_kg, o.total_price, o.status, o.created_at,
           u.username, u.email
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
    LIMIT 10
");
$stmt->execute();
$recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php
// Start output buffering to prevent header issues
ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - China Orange Inc</title>
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
                    <a class="nav-link active" href="/admin/">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                    <a class="nav-link" href="/admin/orders.php">
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
                    <h2>Admin Dashboard</h2>
                    <div class="text-muted">
                        <i class="fas fa-calendar me-2"></i><?php echo date('F j, Y'); ?>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="stat-card">
                            <h3><?php echo $stats['total_orders']; ?></h3>
                            <p><i class="fas fa-shopping-cart me-2"></i>Total Orders</p>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stat-card">
                            <h3><?php echo $userStats['total_users']; ?></h3>
                            <p><i class="fas fa-users me-2"></i>Total Users</p>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stat-card">
                            <h3><?php echo $stats['pending_orders']; ?></h3>
                            <p><i class="fas fa-clock me-2"></i>Pending Orders</p>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stat-card">
                            <h3>$<?php echo number_format($stats['total_revenue'], 2); ?></h3>
                            <p><i class="fas fa-dollar-sign me-2"></i>Total Revenue</p>
                        </div>
                    </div>
                </div>

                <!-- Order Status Overview -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="dashboard-card">
                            <h4 class="mb-3">Order Status Overview</h4>
                            <div class="row text-center">
                                <div class="col-md-3 mb-3">
                                    <div class="p-3 border rounded">
                                        <h5 class="text-warning"><?php echo $stats['pending_orders']; ?></h5>
                                        <small class="text-muted">Pending</small>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="p-3 border rounded">
                                        <h5 class="text-info"><?php echo $stats['processing_orders']; ?></h5>
                                        <small class="text-muted">Processing</small>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="p-3 border rounded">
                                        <h5 class="text-primary"><?php echo $stats['shipped_orders']; ?></h5>
                                        <small class="text-muted">Shipped</small>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="p-3 border rounded">
                                        <h5 class="text-success"><?php echo $stats['delivered_orders']; ?></h5>
                                        <small class="text-muted">Delivered</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="dashboard-card">
                            <h4 class="mb-3">Quick Actions</h4>
                            <div class="d-grid gap-2">
                                <a href="/admin/orders.php" class="btn btn-warning">
                                    <i class="fas fa-shopping-cart me-2"></i>Manage Orders
                                </a>
                                <a href="/admin/users.php" class="btn btn-outline-primary">
                                    <i class="fas fa-users me-2"></i>View Users
                                </a>
                                <a href="/admin/chat.php" class="btn btn-outline-success">
                                    <i class="fas fa-comments me-2"></i>Support Chat
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="dashboard-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="mb-0">Recent Orders</h4>
                        <a href="/admin/orders.php" class="btn btn-outline-primary btn-sm">View All</a>
                    </div>
                    
                    <?php if (empty($recentOrders)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-shopping-cart text-muted" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-3">No orders yet</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Weight</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentOrders as $order): ?>
                                <tr>
                                    <td><strong>#<?php echo $order['id']; ?></strong></td>
                                    <td>
                                        <?php echo htmlspecialchars($order['username']); ?><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($order['email']); ?></small>
                                    </td>
                                    <td><?php echo $order['weight_kg']; ?> kg</td>
                                    <td>$<?php echo number_format($order['total_price'], 2); ?></td>
                                    <td>
                                        <span class="badge status-<?php echo $order['status']; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" onclick="viewOrder(<?php echo $order['id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-success" onclick="updateOrderStatus(<?php echo $order['id']; ?>)">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/main.js"></script>
    <script>
        function viewOrder(orderId) {
            window.location.href = `/admin/orders.php?view=${orderId}`;
        }

        function updateOrderStatus(orderId) {
            window.location.href = `/admin/orders.php?edit=${orderId}`;
        }
    </script>
</body>
</html>

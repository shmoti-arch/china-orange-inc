<?php
require_once '../config/session.php';
requireAdmin();

$db = new Database();
$conn = $db->getConnection();

$message = '';
$messageType = '';

// Handle user balance update
if ($_POST && isset($_POST['update_balance'])) {
    $userId = $_POST['user_id'];
    $newBalance = floatval($_POST['balance']);
    
    if ($newBalance >= 0) {
        if (updateUserBalance($userId, $newBalance)) {
            $message = 'User balance updated successfully';
            $messageType = 'success';
        } else {
            $message = 'Failed to update user balance';
            $messageType = 'danger';
        }
    } else {
        $message = 'Balance cannot be negative';
        $messageType = 'danger';
    }
}

// Get all users with their order statistics
$stmt = $conn->prepare("
    SELECT u.id, u.username, u.email, u.balance, u.is_admin, u.created_at,
           COUNT(o.id) as total_orders,
           COALESCE(SUM(o.total_price), 0) as total_spent,
           MAX(o.created_at) as last_order_date
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id
    WHERE u.is_admin = FALSE
    GROUP BY u.id, u.username, u.email, u.balance, u.is_admin, u.created_at
    ORDER BY u.created_at DESC
");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - China Orange Inc</title>
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
                    <a class="nav-link" href="/admin/orders.php">
                        <i class="fas fa-shopping-cart me-2"></i>Orders
                    </a>
                    <a class="nav-link active" href="/admin/users.php">
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
                    <h2>User Management</h2>
                    <div class="text-muted">
                        Total Users: <?php echo count($users); ?>
                    </div>
                </div>

                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <div class="dashboard-card">
                    <?php if (empty($users)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-users text-muted" style="font-size: 4rem;"></i>
                        <h5 class="mt-3 text-muted">No users found</h5>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Balance</th>
                                    <th>Orders</th>
                                    <th>Total Spent</th>
                                    <th>Last Order</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($user['username']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-success">$<?php echo number_format($user['balance'], 2); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary"><?php echo $user['total_orders']; ?></span>
                                    </td>
                                    <td>$<?php echo number_format($user['total_spent'], 2); ?></td>
                                    <td>
                                        <?php if ($user['last_order_date']): ?>
                                            <?php echo date('M j, Y', strtotime($user['last_order_date'])); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Never</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" onclick="viewUserDetails(<?php echo $user['id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-success" onclick="showBalanceModal(<?php echo $user['id']; ?>, <?php echo $user['balance']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                                <i class="fas fa-dollar-sign"></i>
                                            </button>
                                            <a href="/admin/chat.php?user=<?php echo $user['id']; ?>" class="btn btn-outline-warning">
                                                <i class="fas fa-comments"></i>
                                            </a>
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

    <!-- User Details Modal -->
    <div class="modal fade" id="userDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">User Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="userDetailsBody">
                    <!-- User details will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Balance Update Modal -->
    <div class="modal fade" id="balanceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update User Balance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="user_id" id="balanceUserId">
                        <div class="mb-3">
                            <label for="username" class="form-label">User</label>
                            <input type="text" class="form-control" id="balanceUsername" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="balance" class="form-label">New Balance</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" name="balance" id="balanceAmount" step="0.01" min="0" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_balance" class="btn btn-warning">Update Balance</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/main.js"></script>
    <script>
        const users = <?php echo json_encode($users); ?>;

        function viewUserDetails(userId) {
            const user = users.find(u => u.id == userId);
            if (!user) return;

            document.getElementById('userDetailsBody').innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>User Information</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Username:</strong></td><td>${user.username}</td></tr>
                            <tr><td><strong>Email:</strong></td><td>${user.email}</td></tr>
                            <tr><td><strong>Balance:</strong></td><td class="text-success fw-bold">$${parseFloat(user.balance).toFixed(2)}</td></tr>
                            <tr><td><strong>Joined:</strong></td><td>${formatDate(user.created_at)}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Order Statistics</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Total Orders:</strong></td><td>${user.total_orders}</td></tr>
                            <tr><td><strong>Total Spent:</strong></td><td>$${parseFloat(user.total_spent).toFixed(2)}</td></tr>
                            <tr><td><strong>Last Order:</strong></td><td>${user.last_order_date ? formatDate(user.last_order_date) : 'Never'}</td></tr>
                            <tr><td><strong>Avg Order Value:</strong></td><td>$${user.total_orders > 0 ? (parseFloat(user.total_spent) / parseInt(user.total_orders)).toFixed(2) : '0.00'}</td></tr>
                        </table>
                    </div>
                </div>
            `;
            
            new bootstrap.Modal(document.getElementById('userDetailsModal')).show();
        }

        function showBalanceModal(userId, currentBalance, username) {
            document.getElementById('balanceUserId').value = userId;
            document.getElementById('balanceUsername').value = username;
            document.getElementById('balanceAmount').value = parseFloat(currentBalance).toFixed(2);
            new bootstrap.Modal(document.getElementById('balanceModal')).show();
        }
    </script>
</body>
</html>

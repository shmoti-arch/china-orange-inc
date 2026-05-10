<?php
require_once '../config/session.php';
requireAdmin();

$db = new Database();
$conn = $db->getConnection();

$message = '';
$messageType = '';

// Handle admin reply
if ($_POST && isset($_POST['send_reply'])) {
    $userId = $_POST['user_id'];
    $replyMessage = trim($_POST['message']);
    
    if (!empty($replyMessage)) {
        $stmt = $conn->prepare("
            INSERT INTO chat_messages (user_id, admin_id, message, is_from_admin) 
            VALUES (?, ?, ?, TRUE)
        ");
        if ($stmt->execute([$userId, $_SESSION['user_id'], $replyMessage])) {
            $message = 'Reply sent successfully';
            $messageType = 'success';
        } else {
            $message = 'Failed to send reply';
            $messageType = 'danger';
        }
    }
}

// Get all users with chat messages
$stmt = $conn->prepare("
    SELECT DISTINCT u.id, u.username, u.email,
           (SELECT COUNT(*) FROM chat_messages cm WHERE cm.user_id = u.id) as message_count,
           (SELECT cm.created_at FROM chat_messages cm WHERE cm.user_id = u.id ORDER BY cm.created_at DESC LIMIT 1) as last_message_time
    FROM users u
    WHERE EXISTS (SELECT 1 FROM chat_messages cm WHERE cm.user_id = u.id)
    ORDER BY last_message_time DESC
");
$stmt->execute();
$chatUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$selectedUserId = $_GET['user'] ?? ($chatUsers[0]['id'] ?? null);
$chatMessages = [];

if ($selectedUserId) {
    $stmt = $conn->prepare("
        SELECT cm.message, cm.is_from_admin, cm.created_at,
               CASE WHEN cm.is_from_admin THEN au.username ELSE u.username END as sender_name
        FROM chat_messages cm
        JOIN users u ON cm.user_id = u.id
        LEFT JOIN users au ON cm.admin_id = au.id
        WHERE cm.user_id = ?
        ORDER BY cm.created_at ASC
    ");
    $stmt->execute([$selectedUserId]);
    $chatMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Support - China Orange Inc</title>
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
                    <a class="nav-link" href="/admin/users.php">
                        <i class="fas fa-users me-2"></i>Users
                    </a>
                    <a class="nav-link active" href="/admin/chat.php">
                        <i class="fas fa-comments me-2"></i>Chat Support
                    </a>
                    <a class="nav-link" href="/admin/settings.php">
                        <i class="fas fa-cog me-2"></i>Settings
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 admin-content">
                <h2 class="mb-4">Chat Support</h2>

                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <div class="row">
                    <!-- User List -->
                    <div class="col-md-4">
                        <div class="dashboard-card">
                            <h5 class="mb-3">Active Conversations</h5>
                            
                            <?php if (empty($chatUsers)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-comments text-muted" style="font-size: 3rem;"></i>
                                <p class="text-muted mt-3">No conversations yet</p>
                            </div>
                            <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($chatUsers as $user): ?>
                                <a href="?user=<?php echo $user['id']; ?>" 
                                   class="list-group-item list-group-item-action <?php echo $selectedUserId == $user['id'] ? 'active' : ''; ?>">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($user['username']); ?></h6>
                                        <small><?php echo $user['message_count']; ?> messages</small>
                                    </div>
                                    <p class="mb-1"><?php echo htmlspecialchars($user['email']); ?></p>
                                    <small>Last: <?php echo date('M j, g:i A', strtotime($user['last_message_time'])); ?></small>
                                </a>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Chat Area -->
                    <div class="col-md-8">
                        <div class="dashboard-card">
                            <?php if ($selectedUserId): ?>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">
                                    Chat with <?php echo htmlspecialchars($chatUsers[array_search($selectedUserId, array_column($chatUsers, 'id'))]['username']); ?>
                                </h5>
                                <button class="btn btn-sm btn-outline-primary" onclick="refreshChat()">
                                    <i class="fas fa-sync-alt"></i> Refresh
                                </button>
                            </div>

                            <!-- Chat Messages -->
                            <div class="chat-container mb-3" style="height: 400px;" id="chatMessages">
                                <?php foreach ($chatMessages as $msg): ?>
                                <div class="chat-message <?php echo $msg['is_from_admin'] ? 'admin' : 'user'; ?>">
                                    <div><?php echo htmlspecialchars($msg['message']); ?></div>
                                    <div class="chat-timestamp">
                                        <?php echo $msg['sender_name']; ?> - <?php echo date('M j, g:i A', strtotime($msg['created_at'])); ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Reply Form -->
                            <form method="POST" class="mt-3">
                                <input type="hidden" name="user_id" value="<?php echo $selectedUserId; ?>">
                                <div class="input-group">
                                    <textarea class="form-control" name="message" placeholder="Type your reply..." rows="2" required></textarea>
                                    <button class="btn btn-warning" type="submit" name="send_reply">
                                        <i class="fas fa-paper-plane"></i> Send
                                    </button>
                                </div>
                            </form>
                            <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-comments text-muted" style="font-size: 4rem;"></i>
                                <h5 class="mt-3 text-muted">Select a conversation</h5>
                                <p class="text-muted">Choose a user from the left to start chatting</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/main.js"></script>
    <script>
        function refreshChat() {
            location.reload();
        }

        // Auto-scroll chat to bottom
        const chatContainer = document.getElementById('chatMessages');
        if (chatContainer) {
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }

        // Auto-refresh every 30 seconds
        setInterval(refreshChat, 30000);
    </script>
</body>
</html>

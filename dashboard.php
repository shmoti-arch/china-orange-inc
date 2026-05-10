<?php
header('Content-Type: application/json');
require_once '../config/session.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$db = new Database();
$conn = $db->getConnection();

try {
    // Get current balance
    $balance = getUserBalance();
    
    // Get order counts by status
    $stmt = $conn->prepare("
        SELECT status, COUNT(*) as count
        FROM orders 
        WHERE user_id = ? 
        GROUP BY status
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $statusCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $orderCounts = [];
    foreach ($statusCounts as $count) {
        $orderCounts[$count['status']] = $count['count'];
    }
    
    echo json_encode([
        'success' => true,
        'balance' => $balance,
        'orderCounts' => $orderCounts
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
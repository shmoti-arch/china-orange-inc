<?php
header('Content-Type: application/json');
require_once '../config/session.php';

$db = new Database();
$conn = $db->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleGetOrder($conn);
            break;
        case 'POST':
            handleCreateOrder($conn);
            break;
        case 'DELETE':
            handleCancelOrder($conn);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

function handleGetOrder($conn) {
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        return;
    }

    $orderId = $_GET['id'] ?? null;
    if (!$orderId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Order ID required']);
        return;
    }

    $stmt = $conn->prepare("
        SELECT id, weight_kg, price_per_kg, total_price, status, shipping_address, notes, created_at, updated_at 
        FROM orders 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$orderId, $_SESSION['user_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        return;
    }

    echo json_encode(['success' => true, 'order' => $order]);
}

function handleCreateOrder($conn) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $weight = floatval($input['weight'] ?? 0);
    $address = trim($input['address'] ?? '');
    $notes = trim($input['notes'] ?? '');
    $pricePerKg = 2.50;
    $total = $weight * $pricePerKg;

    // Validation
    if ($weight < 5) {
        echo json_encode(['success' => false, 'message' => 'Minimum order is 5kg']);
        return;
    }

    if (empty($address)) {
        echo json_encode(['success' => false, 'message' => 'Shipping address is required']);
        return;
    }

    // Check if user is logged in
    if (!isLoggedIn()) {
        echo json_encode([
            'success' => true, 
            'requiresLogin' => true, 
            'message' => 'Please login to complete your order'
        ]);
        return;
    }

    // Check user balance
    $balance = getUserBalance();
    if ($balance < $total) {
        echo json_encode([
            'success' => false, 
            'message' => 'Insufficient balance. Your balance: $' . number_format($balance, 2) . ', Order total: $' . number_format($total, 2)
        ]);
        return;
    }

    // Create order
    $conn->beginTransaction();
    
    try {
        // Insert order
        $stmt = $conn->prepare("
            INSERT INTO orders (user_id, weight_kg, price_per_kg, total_price, shipping_address, notes) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$_SESSION['user_id'], $weight, $pricePerKg, $total, $address, $notes]);
        
        $orderId = $conn->lastInsertId();
        
        // Update user balance
        $newBalance = $balance - $total;
        updateUserBalance($_SESSION['user_id'], $newBalance);
        
        $conn->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Order placed successfully!',
            'orderId' => $orderId,
            'newBalance' => $newBalance
        ]);
        
    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }
}

function handleCancelOrder($conn) {
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $orderId = $input['id'] ?? null;

    if (!$orderId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Order ID required']);
        return;
    }

    // Get order details
    $stmt = $conn->prepare("
        SELECT id, user_id, total_price, status 
        FROM orders 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$orderId, $_SESSION['user_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        return;
    }

    if ($order['status'] !== 'pending') {
        echo json_encode(['success' => false, 'message' => 'Only pending orders can be cancelled']);
        return;
    }

    $conn->beginTransaction();
    
    try {
        // Update order status
        $stmt = $conn->prepare("UPDATE orders SET status = 'cancelled', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$orderId]);
        
        // Refund user balance
        $currentBalance = getUserBalance();
        $newBalance = $currentBalance + $order['total_price'];
        updateUserBalance($_SESSION['user_id'], $newBalance);
        
        $conn->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Order cancelled and refunded successfully',
            'refundAmount' => $order['total_price'],
            'newBalance' => $newBalance
        ]);
        
    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }
}
?>
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

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleGetMessages($conn);
            break;
        case 'POST':
            handleSendMessage($conn);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

function handleGetMessages($conn) {
    $stmt = $conn->prepare("
        SELECT cm.message, cm.is_from_admin, cm.created_at,
               CASE WHEN cm.is_from_admin THEN u.username ELSE NULL END as admin_name
        FROM chat_messages cm
        LEFT JOIN users u ON cm.admin_id = u.id
        WHERE cm.user_id = ?
        ORDER BY cm.created_at ASC
        LIMIT 50
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'messages' => $messages]);
}

function handleSendMessage($conn) {
    $input = json_decode(file_get_contents('php://input'), true);
    $message = trim($input['message'] ?? '');

    if (empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Message cannot be empty']);
        return;
    }

    if (strlen($message) > 1000) {
        echo json_encode(['success' => false, 'message' => 'Message too long (max 1000 characters)']);
        return;
    }

    $stmt = $conn->prepare("
        INSERT INTO chat_messages (user_id, message, is_from_admin) 
        VALUES (?, ?, FALSE)
    ");
    
    if ($stmt->execute([$_SESSION['user_id'], $message])) {
        echo json_encode(['success' => true, 'message' => 'Message sent successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send message']);
    }
}
?>
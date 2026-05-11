<?php
// Script to fix password hashes in the database
// Run this once to update existing users with correct password hashes

require_once 'web/config/database.php';

$db = new Database();
$conn = $db->getConnection();

try {
    // Update admin password (admin123)
    $adminHash = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE username = 'admin'");
    $stmt->execute([$adminHash]);
    echo "Admin password updated successfully\n";
    
    // Update test user password (user123)
    $userHash = password_hash('user123', PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE username = 'testuser'");
    $stmt->execute([$userHash]);
    echo "Test user password updated successfully\n";
    
    echo "Password fix completed!\n";
    echo "You can now login with:\n";
    echo "Admin: admin / admin123\n";
    echo "User: testuser / user123\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
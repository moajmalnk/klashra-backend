<?php
/**
 * Get Payment History for Admin
 */

header('Content-Type: application/json');
require_once 'db_connect.php';

try {
    // Join with booking tables to get more context if available
    // For simplicity, we'll fetch the main payment info and then correlate
    $sql = "SELECT p.* FROM payments p ORDER BY p.created_at DESC";
    $stmt = $pdo->query($sql);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $payments
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>

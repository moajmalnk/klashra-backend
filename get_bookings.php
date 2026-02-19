<?php
/**
 * Admin API to fetch all bookings
 */

require_once 'db_connect.php';

// In a real application, you should check for a valid session here
/*
session_start();
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized access"]);
    exit();
}
*/

try {
    $sql = "SELECT * FROM bookings ORDER BY created_at DESC";
    $stmt = $pdo->query($sql);
    $bookings = $stmt->fetchAll();

    echo json_encode([
        "status" => "success",
        "data" => $bookings
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>

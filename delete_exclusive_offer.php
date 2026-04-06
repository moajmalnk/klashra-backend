<?php
/**
 * API to delete an exclusive offer
 */

require_once 'db_connect.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$id) {
    echo json_encode(["status" => "error", "message" => "ID is required"]);
    exit();
}

try {
    $stmt = $pdo->prepare("DELETE FROM exclusive_offers WHERE id = :id");
    $stmt->execute(['id' => $id]);

    echo json_encode([
        "status" => "success",
        "message" => "Offer deleted successfully"
    ]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Server error: " . $e->getMessage()]);
}
?>

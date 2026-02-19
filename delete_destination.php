<?php
/**
 * API to delete a destination
 */

require_once 'db_connect.php';

$json_data = file_get_contents("php://input");
$data = json_decode($json_data, true);

if (!$data || empty($data['id'])) {
    echo json_encode(["status" => "error", "message" => "Destination ID is required"]);
    exit();
}

try {
    $id = (int)$data['id'];

    // Note: Foreign key constraints in the database will handle related packages (ON DELETE SET NULL or CASCADE)
    $sql = "DELETE FROM destinations WHERE id = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);

    echo json_encode([
        "status" => "success",
        "message" => "Destination deleted successfully"
    ]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
?>

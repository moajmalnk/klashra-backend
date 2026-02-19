<?php
/**
 * API to delete a visa
 */

require_once 'db_connect.php';

$json_data = file_get_contents("php://input");
$data = json_decode($json_data, true);

if (!$data || empty($data['id'])) {
    echo json_encode(["status" => "error", "message" => "Visa ID is required"]);
    exit();
}

try {
    $id = (int)$data['id'];

    $sql = "DELETE FROM visas WHERE id = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);

    echo json_encode([
        "status" => "success",
        "message" => "Visa deleted successfully"
    ]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
?>

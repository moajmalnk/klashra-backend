<?php
/**
 * Delete a payment record
 */

header('Content-Type: application/json');
require_once 'db_connect.php';

$json_data = file_get_contents("php://input");
$data = json_decode($json_data, true);

$id = $data['id'] ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Missing payment id"]);
    exit();
}

try {
    $stmt = $pdo->prepare("DELETE FROM payments WHERE id = :id");
    $stmt->execute([':id' => $id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(["status" => "success", "message" => "Payment record deleted successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Payment record not found"]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Server error: " . $e->getMessage()]);
}
?>

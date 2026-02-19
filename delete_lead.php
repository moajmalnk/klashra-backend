<?php
/**
 * API to delete a lead
 */

require_once 'db_connect.php';

$json_data = file_get_contents("php://input");
$data = json_decode($json_data, true);

if (!isset($data['id'])) {
    echo json_encode(["status" => "error", "message" => "ID is required"]);
    exit();
}

try {
    $stmt = $pdo->prepare("DELETE FROM leads WHERE id = :id");
    $stmt->execute([':id' => $data['id']]);

    echo json_encode(["status" => "success", "message" => "Lead deleted"]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>

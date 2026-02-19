<?php
/**
 * API to update lead status
 */

require_once 'db_connect.php';

$json_data = file_get_contents("php://input");
$data = json_decode($json_data, true);

if (!isset($data['id']) || !isset($data['status'])) {
    echo json_encode(["status" => "error", "message" => "ID and status are required"]);
    exit();
}

try {
    $stmt = $pdo->prepare("UPDATE leads SET status = :status WHERE id = :id");
    $stmt->execute([
        ':id' => $data['id'],
        ':status' => $data['status']
    ]);

    echo json_encode(["status" => "success", "message" => "Lead status updated"]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>

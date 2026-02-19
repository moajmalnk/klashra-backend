<?php
/**
 * Lightweight API to update status only (Holidays or Hotels)
 */

require_once 'db_connect.php';

$json_data = file_get_contents("php://input");
$data = json_decode($json_data, true);

if (!isset($data['id']) || !isset($data['status']) || !isset($data['type'])) {
    echo json_encode(["status" => "error", "message" => "Missing ID, Status, or Type"]);
    exit();
}

try {
    $id = (int)$data['id'];
    $status = $data['status'];
    $type = $data['type']; // 'holiday' or 'hotel'

    $table = ($type === 'holiday') ? 'holidays' : 'hotels';
    
    // Simple prepared statement to update status
    $sql = "UPDATE $table SET status = :status WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':status' => $status, ':id' => $id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(["status" => "success", "message" => "Status updated to $status"]);
    } else {
        echo json_encode(["status" => "error", "message" => "No changes made or record not found"]);
    }

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>

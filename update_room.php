<?php
/**
 * API to update an existing hotel room
 */

require_once 'db_connect.php';

$json_data = file_get_contents("php://input");
$data = json_decode($json_data, true);

if (!isset($data['id'])) {
    echo json_encode(["status" => "error", "message" => "ID is required"]);
    exit();
}

try {
    $sql = "UPDATE hotel_rooms SET 
            name = :name, 
            size = :size, 
            bed = :bed, 
            view = :view, 
            sleeps = :sleeps, 
            image = :image, 
            images = :images, 
            ratePlans = :ratePlans
            WHERE id = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id' => $data['id'],
        ':name' => $data['name'],
        ':size' => $data['size'] ?? '',
        ':bed' => $data['bed'] ?? '',
        ':view' => $data['view'] ?? '',
        ':sleeps' => $data['sleeps'] ?? '',
        ':image' => $data['image'] ?? null,
        ':images' => json_encode($data['images'] ?? []),
        ':ratePlans' => json_encode($data['ratePlans'] ?? [])
    ]);

    echo json_encode(["status" => "success", "message" => "Room updated successfully"]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>

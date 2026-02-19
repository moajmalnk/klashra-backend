<?php
/**
 * API to create a new hotel room
 */

require_once 'db_connect.php';

$json_data = file_get_contents("php://input");
$data = json_decode($json_data, true);

if (!isset($data['hotelId']) || !isset($data['name'])) {
    echo json_encode(["status" => "error", "message" => "Hotel ID and Name are required"]);
    exit();
}

try {
    $sql = "INSERT INTO hotel_rooms (hotelId, name, size, bed, view, sleeps, image, images, ratePlans) 
            VALUES (:hotelId, :name, :size, :bed, :view, :sleeps, :image, :images, :ratePlans)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':hotelId' => $data['hotelId'],
        ':name' => $data['name'],
        ':size' => $data['size'] ?? '',
        ':bed' => $data['bed'] ?? '',
        ':view' => $data['view'] ?? '',
        ':sleeps' => $data['sleeps'] ?? '',
        ':image' => $data['image'] ?? null,
        ':images' => json_encode($data['images'] ?? []),
        ':ratePlans' => json_encode($data['ratePlans'] ?? [])
    ]);

    echo json_encode([
        "status" => "success",
        "message" => "Room created successfully",
        "id" => $pdo->lastInsertId()
    ]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>

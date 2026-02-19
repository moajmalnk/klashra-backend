<?php
/**
 * Admin API to fetch rooms for a specific hotel or all rooms
 */

require_once 'db_connect.php';

$hotelId = isset($_GET['hotelId']) ? $_GET['hotelId'] : null;

try {
    if ($hotelId) {
        $stmt = $pdo->prepare("SELECT * FROM hotel_rooms WHERE hotelId = :hotelId ORDER BY id DESC");
        $stmt->execute([':hotelId' => $hotelId]);
    } else {
        $stmt = $pdo->query("SELECT * FROM hotel_rooms ORDER BY id DESC");
    }
    
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Decode JSON fields
    foreach ($rooms as &$room) {
        if (isset($room['images'])) {
            $room['images'] = json_decode($room['images'], true) ?: [];
        }
        if (isset($room['ratePlans'])) {
            $room['ratePlans'] = json_decode($room['ratePlans'], true) ?: [];
        }
    }

    echo json_encode([
        "status" => "success",
        "data" => $rooms
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>

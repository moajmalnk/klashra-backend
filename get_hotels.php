<?php
/**
 * Admin API to fetch all hotels
 */

require_once 'db_connect.php';

try {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
    
    if ($id) {
        $stmt = $pdo->prepare("SELECT h.*, (SELECT COUNT(*) FROM hotel_rooms r WHERE r.hotelId = h.id) as rooms FROM hotels h WHERE h.id = :id");
        $stmt->execute(['id' => $id]);
        $hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmt = $pdo->query("SELECT h.*, (SELECT COUNT(*) FROM hotel_rooms r WHERE r.hotelId = h.id) as rooms FROM hotels h ORDER BY h.id DESC");
        $hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Decode JSON fields
    foreach ($hotels as &$hotel) {
        if (isset($hotel['facilities'])) {
            $hotel['facilities'] = json_decode($hotel['facilities'], true) ?: [];
        }
        if (isset($hotel['specialLabels'])) {
            $hotel['specialLabels'] = json_decode($hotel['specialLabels'], true) ?: [];
        }
        if (isset($hotel['detailedFacilities'])) {
            $hotel['detailedFacilities'] = json_decode($hotel['detailedFacilities'], true) ?: [];
        }
        if (isset($hotel['images'])) {
            $hotel['images'] = json_decode($hotel['images'], true) ?: [];
        }
    }

    echo json_encode([
        "status" => "success",
        "data" => $hotels
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>

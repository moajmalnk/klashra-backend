<?php
/**
 * API to fetch filtered holidays based on destination or category
 */

require_once 'db_connect.php';

$destinationId = isset($_GET['destinationId']) ? (int)$_GET['destinationId'] : null;
$category = isset($_GET['category']) ? $_GET['category'] : null;

try {
    $sql = "SELECT * FROM holidays WHERE 1=1";
    $params = [];

    if ($destinationId) {
        $sql .= " AND destinationId = :destId";
        $params[':destId'] = $destinationId;
    }

    // Assuming a 'category' or 'type' column might exist or be part of description/title
    // For this example, we'll assume a column 'category'
    if ($category) {
        $sql .= " AND category = :cat";
        $params[':cat'] = $category;
    }

    $sql .= " ORDER BY id DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $holidays = $stmt->fetchAll();

    // Decode JSON fields
    foreach ($holidays as &$h) {
        $h['itinerary'] = json_decode($h['itinerary'], true);
        $h['galleryImages'] = json_decode($h['galleryImages'], true);
        $h['inclusionsExclusions'] = json_decode($h['inclusionsExclusions'], true);
        $h['pricingRules'] = json_decode($h['pricingRules'], true);
    }

    echo json_encode([
        "status" => "success",
        "data" => $holidays
    ]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>

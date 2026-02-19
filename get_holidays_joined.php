<?php
/**
 * API to fetch holidays with JOINed destination and country details
 */

require_once 'db_connect.php';

try {
    // JOIN holidays with destinations table
    $sql = "SELECT h.*, d.destination as destination_name, d.country as country_name 
            FROM holidays h
            LEFT JOIN destinations d ON h.destinationId = d.id
            ORDER BY h.id DESC";
            
    $stmt = $pdo->query($sql);
    $holidays = $stmt->fetchAll();

    // Decode JSON fields
    foreach ($holidays as &$h) {
        $json_fields = ['itinerary', 'galleryImages', 'inclusionsExclusions', 'pricingRules'];
        foreach ($json_fields as $field) {
            if (isset($h[$field])) {
                $decoded = json_decode($h[$field], true);
                $h[$field] = $decoded !== null ? $decoded : [];
            }
        }
    }

    echo json_encode([
        "status" => "success",
        "data" => $holidays
    ]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>

<?php
/**
 * API to fetch all holidays
 * Decodes JSON fields before returning
 */

require_once 'db_connect.php';

try {
    // Select all holidays
    $sql = "SELECT * FROM holidays ORDER BY id DESC";
    $stmt = $pdo->query($sql);
    $holidays = $stmt->fetchAll();

    // Decode JSON fields for each holiday
    foreach ($holidays as &$holiday) {
        $json_fields = ['itinerary', 'galleryImages', 'inclusionsExclusions', 'pricingRules'];
        
        foreach ($json_fields as $field) {
            if (isset($holiday[$field])) {
                $decoded = json_decode($holiday[$field], true);
                // Fallback to empty array if decode fails or is null
                $holiday[$field] = $decoded !== null ? $decoded : [];
            }
        }
    }

    echo json_encode([
        "status" => "success",
        "data" => $holidays
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>

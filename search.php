<?php
/**
 * Global Search API
 * Searches holidays and hotels by name/title using LIKE
 */

require_once 'db_connect.php';

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (empty($query)) {
    echo json_encode(["status" => "success", "data" => []]);
    exit();
}

try {
    $searchTerm = "%$query%";

    // 1. Search Holidays
    $h_sql = "SELECT id, title, 'holiday' as type, baseImageUrl, startingPriceAED 
              FROM holidays 
              WHERE title LIKE :q OR description LIKE :q";
    $h_stmt = $pdo->prepare($h_sql);
    $h_stmt->execute([':q' => $searchTerm]);
    $holidays = $h_stmt->fetchAll();

    // 2. Search Hotels (Targeting a generic 'hotels' table)
    $hotels = [];
    try {
        $ht_sql = "SELECT id, name as title, 'hotel' as type, baseImage as baseImageUrl 
                   FROM hotels 
                   WHERE name LIKE :q OR description LIKE :q";
        $ht_stmt = $pdo->prepare($ht_sql);
        $ht_stmt->execute([':q' => $searchTerm]);
        $hotels = $ht_stmt->fetchAll();
    } catch (PDOException $e) {
        // Table might not exist yet, ignore if so
    }

    $results = array_merge($holidays, $hotels);

    echo json_encode([
        "status" => "success",
        "query" => $query,
        "results" => $results
    ]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>

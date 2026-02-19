<?php
/**
 * API to fetch all destinations
 */

require_once 'db_connect.php';

try {
    $stmt = $pdo->query("SELECT * FROM destinations ORDER BY destination ASC");
    $destinations = $stmt->fetchAll();

    echo json_encode([
        "status" => "success",
        "data" => $destinations
    ]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>

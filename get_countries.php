<?php
/**
 * API to fetch all countries
 */

require_once 'db_connect.php';

try {
    $stmt = $pdo->query("SELECT * FROM countries ORDER BY name ASC");
    $countries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "data" => $countries
    ]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
?>

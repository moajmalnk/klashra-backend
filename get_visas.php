<?php
/**
 * API to fetch all visas
 */

require_once 'db_connect.php';

try {
    $stmt = $pdo->query("SELECT * FROM visas ORDER BY countryName ASC, visaType ASC");
    $visas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Decode JSON fields
    foreach ($visas as &$v) {
        if (isset($v['documentsRequired'])) {
            $decoded = json_decode($v['documentsRequired'], true);
            $v['documentsRequired'] = $decoded !== null ? $decoded : [];
        }
    }

    echo json_encode([
        "status" => "success",
        "data" => $visas
    ]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
?>

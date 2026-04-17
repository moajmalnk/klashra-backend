<?php
/**
 * Admin API to fetch single lead details by id
 */

require_once 'db_connect.php';
header('Content-Type: application/json');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Invalid lead id"
    ]);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT * FROM leads WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $lead = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$lead) {
        http_response_code(404);
        echo json_encode([
            "status" => "error",
            "message" => "Lead not found"
        ]);
        exit();
    }

    echo json_encode([
        "status" => "success",
        "data" => $lead
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>

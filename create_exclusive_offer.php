<?php
/**
 * API to create a new exclusive offer
 */

require_once 'db_connect.php';

$json_data = file_get_contents("php://input");
$data = json_decode($json_data, true);

if (!isset($data['title'])) {
    echo json_encode(["status" => "error", "message" => "Title is required"]);
    exit();
}

try {
    $sql = "INSERT INTO exclusive_offers (title, description, image, promoCode, category, status) 
            VALUES (:title, :description, :image, :promoCode, :category, :status)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':title' => $data['title'],
        ':description' => $data['description'] ?? '',
        ':image' => $data['image'] ?? '',
        ':promoCode' => $data['promoCode'] ?? '',
        ':category' => $data['category'] ?? 'hotel',
        ':status' => $data['status'] ?? 'Active'
    ]);

    echo json_encode([
        "status" => "success",
        "message" => "Offer created successfully",
        "id" => $pdo->lastInsertId()
    ]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Server error: " . $e->getMessage()]);
}
?>

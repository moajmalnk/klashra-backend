<?php
/**
 * API to update an existing exclusive offer
 */

require_once 'db_connect.php';

$json_data = file_get_contents("php://input");
$data = json_decode($json_data, true);

if (!isset($data['id']) || !isset($data['title'])) {
    echo json_encode(["status" => "error", "message" => "ID and Title are required"]);
    exit();
}

try {
    $sql = "UPDATE exclusive_offers SET 
            title = :title, 
            description = :description, 
            image = :image, 
            promoCode = :promoCode, 
            category = :category, 
            status = :status 
            WHERE id = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id' => (int)$data['id'],
        ':title' => $data['title'],
        ':description' => $data['description'] ?? '',
        ':image' => $data['image'] ?? '',
        ':promoCode' => $data['promoCode'] ?? '',
        ':category' => $data['category'] ?? 'hotel',
        ':status' => $data['status'] ?? 'Active'
    ]);

    echo json_encode([
        "status" => "success",
        "message" => "Offer updated successfully"
    ]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Server error: " . $e->getMessage()]);
}
?>

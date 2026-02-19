<?php
/**
 * API to save site settings
 */

require_once 'db_connect.php';

$json_data = file_get_contents("php://input");
$data = json_decode($json_data, true);

try {
    // We strictly use ID 1 for global settings
    $sql = "INSERT INTO settings (id, email, phone, address, facebook, instagram) 
            VALUES (1, :email, :phone, :address, :facebook, :instagram)
            ON DUPLICATE KEY UPDATE 
                email = VALUES(email),
                phone = VALUES(phone),
                address = VALUES(address),
                facebook = VALUES(facebook),
                instagram = VALUES(instagram)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':email' => $data['email'] ?? '',
        ':phone' => $data['phone'] ?? '',
        ':address' => $data['address'] ?? '',
        ':facebook' => $data['facebook'] ?? '',
        ':instagram' => $data['instagram'] ?? ''
    ]);

    echo json_encode(["status" => "success", "message" => "Settings saved successfully"]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>

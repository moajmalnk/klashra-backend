<?php
/**
 * API to get site settings
 */

require_once 'db_connect.php';

try {
    $sql = "SELECT * FROM settings WHERE id = 1 LIMIT 1";
    $stmt = $pdo->query($sql);
    $settings = $stmt->fetch();

    if ($settings) {
        echo json_encode(["status" => "success", "data" => $settings]);
    } else {
        // Return default empty structure if no settings exist yet
        echo json_encode([
            "status" => "success", 
            "data" => [
                "email" => "",
                "phone" => "",
                "address" => "",
                "facebook" => "",
                "instagram" => ""
            ]
        ]);
    }
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>

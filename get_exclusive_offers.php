<?php
/**
 * Admin API to fetch all exclusive offers
 * Includes table creation logic for first-run
 */

require_once 'db_connect.php';

try {
    // 1. Ensure table exists (First-run support)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `exclusive_offers` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `title` varchar(255) NOT NULL,
      `description` text DEFAULT NULL,
      `image` text DEFAULT NULL,
      `promoCode` varchar(50) DEFAULT NULL,
      `category` enum('hotel', 'holiday', 'visa') DEFAULT 'hotel',
      `status` enum('Active', 'Inactive') DEFAULT 'Active',
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
    
    if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM exclusive_offers WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $offers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmt = $pdo->query("SELECT * FROM exclusive_offers ORDER BY id DESC");
        $offers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode([
        "status" => "success",
        "data" => $offers
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>

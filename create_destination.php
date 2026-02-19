<?php
/**
 * API to create a new destination
 */

require_once 'db_connect.php';

$json_data = file_get_contents("php://input");
$data = json_decode($json_data, true);

if (!$data || empty($data['destination'])) {
    echo json_encode(["status" => "error", "message" => "Destination name is required"]);
    exit();
}

try {
    $destinationName = $data['destination'];
    $baseSlug = !empty($data['slug']) 
            ? $data['slug'] 
            : strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $destinationName)));

    // Ensure slug is unique
    $slug = $baseSlug;
    $count = 1;
    while (true) {
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM destinations WHERE slug = :slug");
        $checkStmt->execute([':slug' => $slug]);
        if ($checkStmt->fetchColumn() == 0) {
            break;
        }
        $slug = $baseSlug . '-' . $count;
        $count++;
    }

    $nights = (int)($data['nights'] ?? 0);
    $days = (int)($data['days'] ?? 0);
    $price = (float)($data['price'] ?? 0);
    $image = $data['image'] ?? '';

    $sql = "INSERT INTO destinations (destination, slug, nights, days, price, image) 
            VALUES (:destination, :slug, :nights, :days, :price, :image)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':destination' => $destinationName,
        ':slug' => $slug,
        ':nights' => $nights,
        ':days' => $days,
        ':price' => $price,
        ':image' => $image
    ]);

    echo json_encode([
        "status" => "success",
        "message" => "Destination created successfully",
        "id" => $pdo->lastInsertId()
    ]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Server error: " . $e->getMessage()]);
}
?>

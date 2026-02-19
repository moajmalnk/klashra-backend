<?php
/**
 * API to update an existing destination
 */

require_once 'db_connect.php';

$json_data = file_get_contents("php://input");
$data = json_decode($json_data, true);

if (!$data || empty($data['id'])) {
    echo json_encode(["status" => "error", "message" => "Destination ID is required"]);
    exit();
}

try {
    $id = (int)$data['id'];
    $destinationName = $data['destination'];
    
    $baseSlug = !empty($data['slug']) 
            ? $data['slug'] 
            : strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $destinationName)));

    // Ensure slug is unique, excluding current record
    $slug = $baseSlug;
    $count = 1;
    while (true) {
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM destinations WHERE slug = :slug AND id != :id");
        $checkStmt->execute([':slug' => $slug, ':id' => $id]);
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

    $sql = "UPDATE destinations SET 
                destination = :destination, 
                slug = :slug, 
                nights = :nights, 
                days = :days, 
                price = :price, 
                image = :image 
            WHERE id = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id' => $id,
        ':destination' => $destinationName,
        ':slug' => $slug,
        ':nights' => $nights,
        ':days' => $days,
        ':price' => $price,
        ':image' => $image
    ]);

    echo json_encode([
        "status" => "success",
        "message" => "Destination updated successfully"
    ]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Server error: " . $e->getMessage()]);
}
?>

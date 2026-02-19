<?php
/**
 * API to create a new hotel
 */

require_once 'db_connect.php';

$json_data = file_get_contents("php://input");
$data = json_decode($json_data, true);

if (!isset($data['name'])) {
    echo json_encode(["status" => "error", "message" => "Name is required"]);
    exit();
}

try {
    $hotelName = $data['name'];
    $baseSlug = !empty($data['slug']) 
            ? $data['slug'] 
            : strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $hotelName)));

    // Ensure slug is unique
    $slug = $baseSlug;
    $count = 1;
    while (true) {
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM hotels WHERE slug = :slug");
        $checkStmt->execute([':slug' => $slug]);
        if ($checkStmt->fetchColumn() == 0) {
            break;
        }
        $slug = $baseSlug . '-' . $count;
        $count++;
    }

    $sql = "INSERT INTO hotels (name, slug, location, rating, stars, score, reviews, rooms, price, status, mainImage, description, facilities, specialLabels, detailedFacilities, images) 
            VALUES (:name, :slug, :location, :rating, :stars, :score, :reviews, :rooms, :price, :status, :mainImage, :description, :facilities, :specialLabels, :detailedFacilities, :images)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':name' => $hotelName,
        ':slug' => $slug,
        ':location' => $data['location'] ?? '',
        ':rating' => $data['rating'] ?? '',
        ':stars' => $data['stars'] ?? 0,
        ':score' => $data['score'] ?? 0.0,
        ':reviews' => $data['reviews'] ?? 0,
        ':rooms' => $data['rooms'] ?? 0,
        ':price' => $data['price'] ?? 0.00,
        ':status' => $data['status'] ?? 'Active',
        ':mainImage' => $data['mainImage'] ?? ($data['image'] ?? null),
        ':description' => $data['description'] ?? '',
        ':facilities' => json_encode($data['facilities'] ?? []),
        ':specialLabels' => json_encode($data['specialLabels'] ?? []),
        ':detailedFacilities' => json_encode($data['detailedFacilities'] ?? []),
        ':images' => json_encode($data['images'] ?? [])
    ]);

    echo json_encode([
        "status" => "success",
        "message" => "Hotel created successfully",
        "id" => $pdo->lastInsertId()
    ]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Server error: " . $e->getMessage()]);
}
?>

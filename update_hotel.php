<?php
/**
 * API to update an existing hotel
 */

require_once 'db_connect.php';

$json_data = file_get_contents("php://input");
$data = json_decode($json_data, true);

if (!isset($data['id'])) {
    echo json_encode(["status" => "error", "message" => "ID is required"]);
    exit();
}

try {
    $id = (int)$data['id'];
    $hotelName = $data['name'];
    
    $baseSlug = !empty($data['slug']) 
            ? $data['slug'] 
            : strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $hotelName)));

    // Ensure slug is unique, excluding current record
    $slug = $baseSlug;
    $count = 1;
    while (true) {
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM hotels WHERE slug = :slug AND id != :id");
        $checkStmt->execute([':slug' => $slug, ':id' => $id]);
        if ($checkStmt->fetchColumn() == 0) {
            break;
        }
        $slug = $baseSlug . '-' . $count;
        $count++;
    }

    $sql = "UPDATE hotels SET 
            name = :name, 
            slug = :slug,
            location = :location, 
            rating = :rating, 
            stars = :stars, 
            score = :score, 
            reviews = :reviews, 
            rooms = :rooms, 
            price = :price, 
            status = :status, 
            mainImage = :mainImage, 
            description = :description, 
            facilities = :facilities, 
            specialLabels = :specialLabels, 
            detailedFacilities = :detailedFacilities, 
            images = :images
            WHERE id = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id' => $id,
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

    echo json_encode(["status" => "success", "message" => "Hotel updated successfully"]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Server error: " . $e->getMessage()]);
}
?>

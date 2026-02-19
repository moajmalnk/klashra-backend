<?php
/**
 * API to update an existing country
 */

require_once 'db_connect.php';

$json_data = file_get_contents("php://input");
$data = json_decode($json_data, true);

if (!$data || empty($data['id'])) {
    echo json_encode(["status" => "error", "message" => "Country ID is required"]);
    exit();
}

try {
    $id = (int)$data['id'];
    $name = $data['name'];
    $baseSlug = !empty($data['slug']) 
            ? $data['slug'] 
            : strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));

    // Ensure slug is unique, excluding current record
    $slug = $baseSlug;
    $count = 1;
    while (true) {
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM countries WHERE slug = :slug AND id != :id");
        $checkStmt->execute([':slug' => $slug, ':id' => $id]);
        if ($checkStmt->fetchColumn() == 0) {
            break;
        }
        $slug = $baseSlug . '-' . $count;
        $count++;
    }

    $region = $data['region'] ?? 'International';
    $image = $data['image'] ?? '';

    $sql = "UPDATE countries SET 
                name = :name, 
                slug = :slug, 
                region = :region, 
                image = :image 
            WHERE id = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id' => $id,
        ':name' => $name,
        ':slug' => $slug,
        ':region' => $region,
        ':image' => $image
    ]);

    echo json_encode([
        "status" => "success",
        "message" => "Country updated successfully"
    ]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Server error: " . $e->getMessage()]);
}
?>

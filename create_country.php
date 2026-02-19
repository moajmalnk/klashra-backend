<?php
/**
 * API to create a new country
 */

require_once 'db_connect.php';

$json_data = file_get_contents("php://input");
$data = json_decode($json_data, true);

if (!$data || empty($data['name'])) {
    echo json_encode(["status" => "error", "message" => "Country name is required"]);
    exit();
}

try {
    $name = $data['name'];
    $baseSlug = !empty($data['slug']) 
            ? $data['slug'] 
            : strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));

    // Ensure slug is unique
    $slug = $baseSlug;
    $count = 1;
    while (true) {
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM countries WHERE slug = :slug");
        $checkStmt->execute([':slug' => $slug]);
        if ($checkStmt->fetchColumn() == 0) {
            break;
        }
        $slug = $baseSlug . '-' . $count;
        $count++;
    }

    $region = $data['region'] ?? 'International';
    $image = $data['image'] ?? '';

    $sql = "INSERT INTO countries (name, slug, region, image) VALUES (:name, :slug, :region, :image)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':name' => $name,
        ':slug' => $slug,
        ':region' => $region,
        ':image' => $image
    ]);

    echo json_encode([
        "status" => "success",
        "message" => "Country created successfully",
        "id" => $pdo->lastInsertId()
    ]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Server error: " . $e->getMessage()]);
}
?>

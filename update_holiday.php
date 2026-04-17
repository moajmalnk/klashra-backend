<?php
/**
 * API to update an existing holiday
 */

require_once 'db_connect.php';

// Get raw POST data
$json_data = file_get_contents("php://input");
$data = json_decode($json_data, true);

if (!isset($data['id'])) {
    echo json_encode(["status" => "error", "message" => "Holiday ID is required for update"]);
    exit();
}

try {
    $id = (int)$data['id'];
    $title = $data['title'];
    $baseSlug = !empty($data['slug']) ? $data['slug'] : strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));

    // Ensure slug is unique, excluding the current record
    $slug = $baseSlug;
    $count = 1;
    while (true) {
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM holidays WHERE slug = :slug AND id != :id");
        $checkStmt->execute([':slug' => $slug, ':id' => $id]);
        if ($checkStmt->fetchColumn() == 0) {
            break;
        }
        $slug = $baseSlug . '-' . $count;
        $count++;
    }

    $sql = "UPDATE holidays SET 
                title = :title, 
                startingPriceAED = :startingPriceAED, 
                duration = :duration, 
                slug = :slug, 
                description = :description, 
                baseImageUrl = :baseImageUrl, 
                destinationId = :destinationId, 
                itinerary = :itinerary, 
                galleryImages = :galleryImages, 
                inclusionsExclusions = :inclusionsExclusions, 
                generalGuidelines = :generalGuidelines, 
                termsAndConditions = :termsAndConditions, 
                forceMajeure = :forceMajeure, 
                pricingRules = :pricingRules,
                brochure_pdf = :brochure_pdf
            WHERE id = :id";

    $stmt = $pdo->prepare($sql);

    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->bindValue(':title', $title);
    $stmt->bindValue(':startingPriceAED', (float)($data['startingPriceAED'] ?? 0));
    $stmt->bindValue(':duration', $data['duration'] ?? '');
    $stmt->bindValue(':slug', $slug);
    $stmt->bindValue(':description', $data['description'] ?? '');
    $stmt->bindValue(':baseImageUrl', $data['baseImageUrl'] ?? '');
    $stmt->bindValue(':destinationId', isset($data['destinationId']) ? (int)$data['destinationId'] : null, PDO::PARAM_INT);
    
    // JSON encoding
    $stmt->bindValue(':itinerary', json_encode($data['itinerary'] ?? []));
    $stmt->bindValue(':galleryImages', json_encode($data['galleryImages'] ?? []));
    $stmt->bindValue(':inclusionsExclusions', json_encode($data['inclusionsExclusions'] ?? []));
    $stmt->bindValue(':pricingRules', json_encode($data['pricingRules'] ?? []));
    
    $stmt->bindValue(':generalGuidelines', $data['generalGuidelines'] ?? '');
    $stmt->bindValue(':termsAndConditions', $data['termsAndConditions'] ?? '');
    $stmt->bindValue(':forceMajeure', $data['forceMajeure'] ?? '');
    $stmt->bindValue(':brochure_pdf', $data['brochure_pdf'] ?? '');

    if ($stmt->execute()) {
        echo json_encode([
            "status" => "success",
            "message" => "Holiday updated successfully"
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to update holiday"]);
    }

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Server error: " . $e->getMessage()]);
}
?>

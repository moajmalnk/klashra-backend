<?php
/**
 * API for creating a new holiday record
 * Receives POST request from React frontend
 */

require_once 'db_connect.php';

// Get the raw POST data (since it's JSON from fetch)
$json_data = file_get_contents("php://input");
$data = json_decode($json_data, true);

// Check if data exists
if (!$data) {
    echo json_encode([
        "status" => "error", 
        "message" => "No valid data provided. Ensure request body is JSON."
    ]);
    exit();
}

try {
    // Required fields check (minimal)
    if (empty($data['title'])) {
        echo json_encode(["status" => "error", "message" => "Title is required"]);
        exit();
    }

    // Prepare Slug: Use provided or generate from title
    $title = $data['title'];
    $baseSlug = !empty($data['slug']) 
            ? $data['slug'] 
            : strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));

    // Ensure slug is unique
    $slug = $baseSlug;
    $count = 1;
    while (true) {
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM holidays WHERE slug = :slug");
        $checkStmt->execute([':slug' => $slug]);
        if ($checkStmt->fetchColumn() == 0) {
            break;
        }
        $slug = $baseSlug . '-' . $count;
        $count++;
    }

    /**
     * SQL Prepared Statement
     * Maps frontend fields to DB columns
     */
    $sql = "INSERT INTO holidays (
                title, 
                startingPriceAED, 
                duration, 
                slug, 
                description, 
                baseImageUrl, 
                destinationId, 
                itinerary, 
                galleryImages, 
                inclusionsExclusions, 
                generalGuidelines, 
                termsAndConditions, 
                forceMajeure, 
                pricingRules
            ) VALUES (
                :title, 
                :startingPriceAED, 
                :duration, 
                :slug, 
                :description, 
                :baseImageUrl, 
                :destinationId, 
                :itinerary, 
                :galleryImages, 
                :inclusionsExclusions, 
                :generalGuidelines, 
                :termsAndConditions, 
                :forceMajeure, 
                :pricingRules
            )";

    $stmt = $pdo->prepare($sql);

    // Bind basic values
    $stmt->bindValue(':title', $title);
    $stmt->bindValue(':startingPriceAED', (float)($data['startingPriceAED'] ?? 0));
    $stmt->bindValue(':duration', $data['duration'] ?? '');
    $stmt->bindValue(':slug', $slug);
    $stmt->bindValue(':description', $data['description'] ?? '');
    $stmt->bindValue(':baseImageUrl', $data['baseImageUrl'] ?? '');
    $stmt->bindValue(':destinationId', isset($data['destinationId']) ? (int)$data['destinationId'] : null, PDO::PARAM_INT);
    
    // --- JSON ENCODING for arrays ---
    // SQL expects these to be strings (JSON format)
    $stmt->bindValue(':itinerary', json_encode($data['itinerary'] ?? []));
    $stmt->bindValue(':galleryImages', json_encode($data['galleryImages'] ?? []));
    $stmt->bindValue(':inclusionsExclusions', json_encode($data['inclusionsExclusions'] ?? []));
    $stmt->bindValue(':pricingRules', json_encode($data['pricingRules'] ?? []));
    
    // Other guide/legal text
    $stmt->bindValue(':generalGuidelines', $data['generalGuidelines'] ?? '');
    $stmt->bindValue(':termsAndConditions', $data['termsAndConditions'] ?? '');
    $stmt->bindValue(':forceMajeure', $data['forceMajeure'] ?? '');

    // Execute
    if ($stmt->execute()) {
        echo json_encode([
            "status" => "success", 
            "message" => "Holiday created successfully",
            "id" => $pdo->lastInsertId()
        ]);
    } else {
        echo json_encode([
            "status" => "error", 
            "message" => "Failed to save record to database"
        ]);
    }

} catch (PDOException $e) {
    // Return specific DB error
    echo json_encode([
        "status" => "error", 
        "message" => "Database error: " . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        "status" => "error", 
        "message" => "Server error: " . $e->getMessage()
    ]);
}
?>

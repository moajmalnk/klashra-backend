<?php
/**
 * API to create a new visa
 */

require_once 'db_connect.php';

$json_data = file_get_contents("php://input");
$data = json_decode($json_data, true);

if (!$data || empty($data['countryName']) || empty($data['visaType'])) {
    echo json_encode(["status" => "error", "message" => "Country Name and Visa Type are required"]);
    exit();
}

try {
    $countryId = isset($data['countryId']) ? (int)$data['countryId'] : null;
    $countryName = $data['countryName'];
    $visaType = $data['visaType'];
    
    $baseSlug = !empty($data['slug']) 
            ? $data['slug'] 
            : strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $countryName . '-' . $visaType)));

    // Ensure slug is unique
    $slug = $baseSlug;
    $count = 1;
    while (true) {
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM visas WHERE slug = :slug");
        $checkStmt->execute([':slug' => $slug]);
        if ($checkStmt->fetchColumn() == 0) {
            break;
        }
        $slug = $baseSlug . '-' . $count;
        $count++;
    }

    $processingTime = $data['processingTime'] ?? '';
    $stayPeriod = $data['stayPeriod'] ?? '';
    $validity = $data['validity'] ?? '';
    $entryType = $data['entryType'] ?? 'Single';
    $description = $data['description'] ?? '';
    $image = $data['image'] ?? '';
    $status = $data['status'] ?? 'Active';
    $documentsRequired = json_encode($data['documentsRequired'] ?? []);

    $sql = "INSERT INTO visas (
                countryId, countryName, visaType, processingTime, stayPeriod, 
                validity, entryType, description, image, status, slug, documentsRequired
            ) VALUES (
                :countryId, :countryName, :visaType, :processingTime, :stayPeriod, 
                :validity, :entryType, :description, :image, :status, :slug, :documentsRequired
            )";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':countryId' => $countryId,
        ':countryName' => $countryName,
        ':visaType' => $visaType,
        ':processingTime' => $processingTime,
        ':stayPeriod' => $stayPeriod,
        ':validity' => $validity,
        ':entryType' => $entryType,
        ':description' => $description,
        ':image' => $image,
        ':status' => $status,
        ':slug' => $slug,
        ':documentsRequired' => $documentsRequired
    ]);

    echo json_encode([
        "status" => "success",
        "message" => "Visa created successfully",
        "id" => $pdo->lastInsertId()
    ]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Server error: " . $e->getMessage()]);
}
?>

<?php
/**
 * API to update an existing visa
 */

require_once 'db_connect.php';

$json_data = file_get_contents("php://input");
$data = json_decode($json_data, true);

if (!$data || empty($data['id'])) {
    echo json_encode(["status" => "error", "message" => "Visa ID is required"]);
    exit();
}

try {
    $id = (int)$data['id'];
    $countryName = $data['countryName'];
    $visaType = $data['visaType'];
    
    $baseSlug = !empty($data['slug']) 
            ? $data['slug'] 
            : strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $countryName . '-' . $visaType)));

    // Ensure slug is unique, excluding current record
    $slug = $baseSlug;
    $count = 1;
    while (true) {
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM visas WHERE slug = :slug AND id != :id");
        $checkStmt->execute([':slug' => $slug, ':id' => $id]);
        if ($checkStmt->fetchColumn() == 0) {
            break;
        }
        $slug = $baseSlug . '-' . $count;
        $count++;
    }

    $countryId = isset($data['countryId']) ? (int)$data['countryId'] : null;
    $processingTime = $data['processingTime'] ?? '';
    $stayPeriod = $data['stayPeriod'] ?? '';
    $validity = $data['validity'] ?? '';
    $entryType = $data['entryType'] ?? 'Single';
    $description = $data['description'] ?? '';
    $image = $data['image'] ?? '';
    $status = $data['status'] ?? 'Active';
    $documentsRequired = json_encode($data['documentsRequired'] ?? []);

    $sql = "UPDATE visas SET 
                countryId = :countryId, 
                countryName = :countryName, 
                visaType = :visaType, 
                processingTime = :processingTime, 
                stayPeriod = :stayPeriod, 
                validity = :validity, 
                entryType = :entryType, 
                description = :description, 
                image = :image, 
                status = :status, 
                slug = :slug, 
                documentsRequired = :documentsRequired 
            WHERE id = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id' => $id,
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
        "message" => "Visa updated successfully"
    ]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Server error: " . $e->getMessage()]);
}
?>

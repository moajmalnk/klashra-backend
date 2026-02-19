<?php
/**
 * API to delete a holiday by ID
 */

require_once 'db_connect.php';

// Get the raw POST data
$json_data = file_get_contents("php://input");
$data = json_decode($json_data, true);

// Check if id is provided
if (!isset($data['id'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Holiday ID is required for deletion"
    ]);
    exit();
}

try {
    $id = (int)$data['id'];
    
    // Use prepared statement for security
    $sql = "DELETE FROM holidays WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                "status" => "success",
                "message" => "Holiday deleted successfully"
            ]);
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Holiday not found or already deleted"
            ]);
        }
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Failed to delete holiday"
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>

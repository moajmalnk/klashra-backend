<?php
/**
 * Public API to submit a general enquiry/lead
 */

require_once 'db_connect.php';

// Get the raw POST data
$json_data = file_get_contents("php://input");
$data = json_decode($json_data, true);

// Basic validation
if (empty($data['firstName']) || empty($data['email'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Name and Email are required"
    ]);
    exit();
}

try {
    $sql = "INSERT INTO leads (
                firstName, 
                lastName, 
                email, 
                phone, 
                category, 
                query, 
                status
            ) VALUES (
                :firstName, 
                :lastName, 
                :email, 
                :phone, 
                :category, 
                :query, 
                'New'
            )";

    $stmt = $pdo->prepare($sql);
    
    $stmt->bindValue(':firstName', $data['firstName']);
    $stmt->bindValue(':lastName', $data['lastName'] ?? '');
    $stmt->bindValue(':email', $data['email']);
    $stmt->bindValue(':phone', $data['phone'] ?? '');
    $stmt->bindValue(':category', $data['category'] ?? 'General');
    $stmt->bindValue(':query', $data['query'] ?? '');

    if ($stmt->execute()) {
        echo json_encode([
            "status" => "success",
            "message" => "Your enquiry has been submitted. Our team will contact you soon."
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Failed to submit enquiry"
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>

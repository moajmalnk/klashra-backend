<?php
/**
 * Public API to submit a booking inquiry
 */

require_once 'db_connect.php';

// Get the raw POST data
$json_data = file_get_contents("php://input");
$data = json_decode($json_data, true);

// Basic validation
if (empty($data['name']) || empty($data['email'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Name and Email are required"
    ]);
    exit();
}

try {
    $sql = "INSERT INTO bookings (
                name, 
                email, 
                phone, 
                service_type, 
                message, 
                status,
                created_at
            ) VALUES (
                :name, 
                :email, 
                :phone, 
                :service_type, 
                :message, 
                'pending',
                NOW()
            )";

    $stmt = $pdo->prepare($sql);
    
    $stmt->bindValue(':name', $data['name']);
    $stmt->bindValue(':email', $data['email']);
    $stmt->bindValue(':phone', $data['phone'] ?? '');
    $stmt->bindValue(':service_type', $data['service_type'] ?? 'general');
    $stmt->bindValue(':message', $data['message'] ?? '');

    if ($stmt->execute()) {
        echo json_encode([
            "status" => "success",
            "message" => "Your inquiry has been submitted successfully. Our team will contact you soon."
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Failed to submit booking"
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>

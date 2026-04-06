<?php
/**
 * Public API to submit a visa booking inquiry
 */

require_once 'db_connect.php';

// Get the raw POST data
$json_data = file_get_contents("php://input");
$data = json_decode($json_data, true);

// Basic validation
if (empty($data['applicantName']) || empty($data['mobile']) || empty($data['visaType'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Name, Mobile, and Visa Type are required"
    ]);
    exit();
}

try {
    $sql = "INSERT INTO visa_bookings (
                applicantName, 
                email, 
                mobile, 
                passportNumber, 
                visaType, 
                destinationCountry, 
                submissionDate,
                status
            ) VALUES (
                :applicantName, 
                :email, 
                :mobile, 
                :passportNumber, 
                :visaType, 
                :destinationCountry, 
                CURDATE(),
                'Processing'
            )";

    $stmt = $pdo->prepare($sql);
    
    $stmt->bindValue(':applicantName', $data['applicantName']);
    $stmt->bindValue(':email', $data['email'] ?? '');
    $stmt->bindValue(':mobile', $data['mobile']);
    $stmt->bindValue(':passportNumber', $data['passportNumber'] ?? '');
    $stmt->bindValue(':visaType', $data['visaType']);
    $stmt->bindValue(':destinationCountry', $data['destinationCountry'] ?? '');

    if ($stmt->execute()) {
        echo json_encode([
            "status" => "success",
            "message" => "Your visa inquiry has been submitted successfully. Our team will contact you soon."
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Failed to submit visa enquiry"
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>

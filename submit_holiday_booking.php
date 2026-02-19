<?php
require_once 'db_connect.php';


$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["status" => "error", "message" => "Invalid JSON"]);
    exit;
}

// Basic Validation
if (empty($data['customerDetails']['name']) || empty($data['customerDetails']['email'])) {
    echo json_encode(["status" => "error", "message" => "Missing customer details"]);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO holiday_bookings (
        packageId, 
        packageName,
        customerName, 
        email, 
        phone, 
        departureDate, 
        travelers, 
        totalAmount, 
        status, 
        travelerDetails, 
        created_at
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?, NOW()
    )");
    
    $travelerDetails = json_encode($data['travelersDetails'] ?? []);
    
    $result = $stmt->execute([
        $data['packageId'] ?? 0,
        $data['packageName'] ?? 'N/A',
        $data['customerDetails']['name'],
        $data['customerDetails']['email'],
        $data['customerDetails']['phone'],
        $data['departureDate'],
        $data['travelers'],
        $data['totalAmount'],
        $travelerDetails
    ]);

    if ($result) {
        $bookingId = $pdo->lastInsertId();
        echo json_encode(["status" => "success", "message" => "Holiday package booked successfully", "bookingId" => $bookingId]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to create booking record"]);
    }

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
?>

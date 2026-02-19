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
    // Generate a Booking ID if not provided
    $bookingId = $data['bookingId'] ?? 'HB-' . strtoupper(substr(uniqid(), -6));
    
    // Map frontend 'hotelReference' to 'hotelId'
    $hotelId = $data['hotelReference'] ?? 0;
    
    $stmt = $pdo->prepare("INSERT INTO hotel_bookings (
        bookingId, 
        customerName, 
        email, 
        phone, 
        hotelId, 
        roomType, 
        checkIn, 
        checkOut, 
        totalAmount, 
        status, 
        created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())");
    
    $result = $stmt->execute([
        $bookingId,
        $data['customerDetails']['name'],
        $data['customerDetails']['email'],
        $data['customerDetails']['phone'],
        $hotelId,
        $data['roomType'],
        $data['checkIn'],
        $data['checkOut'],
        $data['totalAmount']
    ]);

    if ($result) {
        echo json_encode(["status" => "success", "message" => "Hotel booking confirmed", "bookingId" => $bookingId]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to create booking record"]);
    }

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
?>

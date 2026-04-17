<?php
require_once 'db_connect.php';
session_start();


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
    // Generate a unique sequential Booking ID (HB-XXX format)
    $stmt = $pdo->query("SELECT MAX(CAST(SUBSTRING(bookingId, 4) AS UNSIGNED)) as max_num FROM hotel_bookings WHERE bookingId LIKE 'HB-%'");
    $row = $stmt->fetch();
    $nextNum = ($row['max_num'] ?? 0) + 1;
    $bookingId = 'HB-' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
    
    // Map frontend 'hotelReference' to 'hotelId'
    $hotelId = $data['hotelReference'] ?? 0;
    
    $sessionUserId = $_SESSION['user_id'] ?? null;
    $email = trim($data['customerDetails']['email'] ?? '');
    $resolvedUserId = $sessionUserId;

    if (!$resolvedUserId && $email !== '') {
        $userStmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $userStmt->execute([':email' => $email]);
        $resolvedUserId = $userStmt->fetchColumn() ?: null;
    }

    $stmt = $pdo->prepare("INSERT INTO hotel_bookings (
        bookingId, 
        customerName, 
        email, 
        phone, 
        user_id,
        hotelId, 
        roomType, 
        checkIn, 
        checkOut, 
        totalAmount, 
        status, 
        created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())");
    
    $result = $stmt->execute([
        $bookingId,
        $data['customerDetails']['name'],
        $email,
        $data['customerDetails']['phone'],
        $resolvedUserId,
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

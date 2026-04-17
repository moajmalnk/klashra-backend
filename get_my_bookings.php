<?php
/**
 * Fetch all bookings for a specific user
 */

session_start();
header('Content-Type: application/json');
require_once 'db_connect.php';

$user_id = $_SESSION['user_id'] ?? null;
$user_email = $_SESSION['user_email'] ?? null;

if (!$user_id && !$user_email) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit();
}

try {
    // 1. Fetch Hotel Bookings
    $hotelStmt = $pdo->prepare("SELECT hb.*, h.name as hotelName, h.mainImage 
                                FROM hotel_bookings hb 
                                LEFT JOIN hotels h ON hb.hotelId = h.id 
                                WHERE hb.user_id = :uid 
                                   OR (:uemail IS NOT NULL AND :uemail != '' AND hb.email = :uemail)
                                ORDER BY hb.created_at DESC");
    $hotelStmt->execute([':uid' => $user_id, ':uemail' => $user_email]);
    $hotels = $hotelStmt->fetchAll();

    // 2. Fetch Holiday Bookings
    // Note: holiday_bookings schema has travelers and totalAmount, etc.
    $holidayStmt = $pdo->prepare("SELECT * FROM holiday_bookings 
                                  WHERE user_id = :uid
                                     OR (:uemail IS NOT NULL AND :uemail != '' AND email = :uemail)
                                  ORDER BY id DESC");
    $holidayStmt->execute([':uid' => $user_id, ':uemail' => $user_email]);
    $holidays = $holidayStmt->fetchAll();

    // 3. Fetch Visa Bookings
    $visaStmt = $pdo->prepare("SELECT * FROM visa_bookings 
                               WHERE user_id = :uid
                                  OR (:uemail IS NOT NULL AND :uemail != '' AND email = :uemail)
                               ORDER BY id DESC");
    $visaStmt->execute([':uid' => $user_id, ':uemail' => $user_email]);
    $visas = $visaStmt->fetchAll();

    echo json_encode([
        "status" => "success",
        "data" => [
            "hotels" => $hotels,
            "holidays" => $holidays,
            "visas" => $visas
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Server error: " . $e->getMessage()]);
}
?>

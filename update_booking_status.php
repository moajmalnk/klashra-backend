<?php
/**
 * API to update booking status for Hotels, Holidays or Visas
 */

require_once 'db_connect.php';

$json_data = file_get_contents("php://input");
$data = json_decode($json_data, true);

if (!isset($data['id']) || !isset($data['status']) || !isset($data['type'])) {
    echo json_encode(["status" => "error", "message" => "Missing ID, Status, or Type"]);
    exit();
}

try {
    $id = $data['id'];
    $status = $data['status'];
    $type = $data['type']; // 'hotel', 'holiday', or 'visa'

    switch ($type) {
        case 'hotel':
            $sql = "UPDATE hotel_bookings SET status = :status WHERE bookingId = :id";
            break;
        case 'holiday':
            $sql = "UPDATE holiday_bookings SET status = :status WHERE id = :id";
            break;
        case 'visa':
            $sql = "UPDATE visa_bookings SET status = :status WHERE id = :id";
            break;
        default:
            echo json_encode(["status" => "error", "message" => "Invalid booking type"]);
            exit();
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':status' => $status, ':id' => $id]);

    echo json_encode(["status" => "success", "message" => "Booking status updated to $status"]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>

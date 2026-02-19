<?php
/**
 * API to delete a booking
 */

require_once 'db_connect.php';

$json_data = file_get_contents("php://input");
$data = json_decode($json_data, true);

if (!isset($data['id']) || !isset($data['type'])) {
    echo json_encode(["status" => "error", "message" => "Missing ID or Type"]);
    exit();
}

try {
    $id = $data['id'];
    $type = $data['type']; // 'hotel', 'holiday', or 'visa'

    switch ($type) {
        case 'hotel':
            $sql = "DELETE FROM hotel_bookings WHERE bookingId = :id";
            break;
        case 'holiday':
            $sql = "DELETE FROM holiday_bookings WHERE id = :id";
            break;
        case 'visa':
            $sql = "DELETE FROM visa_bookings WHERE id = :id";
            break;
        default:
            echo json_encode(["status" => "error", "message" => "Invalid booking type"]);
            exit();
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);

    echo json_encode(["status" => "success", "message" => "Booking deleted successfully"]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>

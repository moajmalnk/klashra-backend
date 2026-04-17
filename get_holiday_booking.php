<?php
/**
 * API to fetch a single holiday booking by ID with joined holiday details
 */

require_once 'db_connect.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

try {
    if ($id) {
        // Fetch specific booking
        $stmt = $pdo->prepare("
            SELECT 
                hb.*, 
                h.title as holiday_title, 
                h.duration as holiday_duration, 
                h.baseImageUrl as holiday_image,
                h.pricingRules as holiday_pricing_rules
            FROM holiday_bookings hb
            LEFT JOIN holidays h ON hb.packageId = h.id
            WHERE hb.id = ?
        ");
        $stmt->execute([$id]);
    } else {
        // Fetch latest pending booking as fallback
        $stmt = $pdo->prepare("
            SELECT 
                hb.*, 
                h.title as holiday_title, 
                h.duration as holiday_duration, 
                h.baseImageUrl as holiday_image,
                h.pricingRules as holiday_pricing_rules
            FROM holiday_bookings hb
            LEFT JOIN holidays h ON hb.packageId = h.id
            WHERE hb.status = 'Pending'
            ORDER BY hb.id DESC
            LIMIT 1
        ");
        $stmt->execute();
    }
    
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        echo json_encode(["status" => "error", "message" => "Booking not found"]);
        exit;
    }

    // Decode JSON fields
    if (isset($booking['travelerDetails'])) {
        $decoded = json_decode($booking['travelerDetails'], true);
        $booking['travelerDetails'] = $decoded !== null ? $decoded : [];
    }
    
    if (isset($booking['holiday_pricing_rules'])) {
        $decoded = json_decode($booking['holiday_pricing_rules'], true);
        $booking['holiday_pricing_rules'] = $decoded !== null ? $decoded : [];
    }

    echo json_encode([
        "status" => "success",
        "data" => $booking
    ]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
?>

<?php
/**
 * API to get quick statistics for the Admin Dashboard
 */

require_once 'db_connect.php';

try {
    // 1. Total Holidays Count
    $holiday_stmt = $pdo->query("SELECT COUNT(*) as total FROM holidays");
    $total_holidays = $holiday_stmt->fetch()['total'];

    // 2. Total Bookings Count
    $booking_stmt = $pdo->query("SELECT COUNT(*) as total FROM bookings");
    $total_bookings = $booking_stmt->fetch()['total'];

    // 3. Pending Inquiries Count
    $pending_stmt = $pdo->query("SELECT COUNT(*) as total FROM bookings WHERE status = 'pending'");
    $pending_inquiries = $pending_stmt->fetch()['total'];

    echo json_encode([
        "status" => "success",
        "data" => [
            "totalHolidays" => (int)$total_holidays,
            "totalBookings" => (int)$total_bookings,
            "pendingInquiries" => (int)$pending_inquiries
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>

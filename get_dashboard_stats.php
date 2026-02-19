<?php
/**
 * API to get quick statistics for the Admin Dashboard
 */

require_once 'db_connect.php';

try {
    // 1. Total Holidays Count
    $holiday_stmt = $pdo->query("SELECT COUNT(*) as total FROM holidays");
    $total_holidays = $holiday_stmt->fetch()['total'];

    // 2. Total Bookings Count (Sum of all 3 types)
    $h_stmt = $pdo->query("SELECT COUNT(*) as total FROM hotel_bookings");
    $v_stmt = $pdo->query("SELECT COUNT(*) as total FROM visa_bookings");
    $p_stmt = $pdo->query("SELECT COUNT(*) as total FROM holiday_bookings");
    
    $total_bookings = $h_stmt->fetch()['total'] + $v_stmt->fetch()['total'] + $p_stmt->fetch()['total'];

    // 3. Pending Inquiries Count (Leads with status 'New')
    $pending_stmt = $pdo->query("SELECT COUNT(*) as total FROM leads WHERE status = 'New'");
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

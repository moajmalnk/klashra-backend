<?php
/**
 * API to get recent activity for the Admin Dashboard
 */

require_once 'db_connect.php';

try {
    $activities = [];

    // 1. Recent Hotel Bookings
    $hotel_stmt = $pdo->query("SELECT id, customerName, 'Hotel Booking' as type, created_at FROM hotel_bookings ORDER BY created_at DESC LIMIT 3");
    while ($row = $hotel_stmt->fetch()) {
        $activities[] = [
            "id" => "hb_" . $row['id'],
            "title" => "New Hotel Booking: " . $row['customerName'],
            "type" => "booking",
            "time" => $row['created_at']
        ];
    }

    // 2. Recent holiday Bookings
    $holiday_stmt = $pdo->query("SELECT id, customerName, 'Holiday Booking' as type, created_at FROM holiday_bookings ORDER BY created_at DESC LIMIT 3");
    while ($row = $holiday_stmt->fetch()) {
        $activities[] = [
            "id" => "pb_" . $row['id'],
            "title" => "New Holiday Booking: " . $row['customerName'],
            "type" => "package",
            "time" => $row['created_at']
        ];
    }

    // 3. Recent Leads
    $lead_stmt = $pdo->query("SELECT id, name, created_at FROM leads ORDER BY created_at DESC LIMIT 3");
    while ($row = $lead_stmt->fetch()) {
        $activities[] = [
            "id" => "ld_" . $row['id'],
            "title" => "New Inquiry from " . $row['name'],
            "type" => "inquiry",
            "time" => $row['created_at']
        ];
    }

    // Sort all by time DESC
    usort($activities, function($a, $b) {
        return strtotime($b['time']) - strtotime($a['time']);
    });

    // Take top 5
    $activities = array_slice($activities, 0, 5);

    // Format time for frontend (e.g. "2 hours ago") - will do simple formatting here or in JS
    // For now just return the raw ISO string

    echo json_encode([
        "status" => "success",
        "data" => $activities
    ]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>

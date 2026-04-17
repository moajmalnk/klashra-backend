<?php
/**
 * Admin API to fetch all users with payment status
 */

require_once 'db_connect.php';

try {
    // Fetch users and calculate total paid amount from all booking types
    // We join payments with bookings that are linked to the user
    $sql = "SELECT u.id, u.name, u.email, u.phone, u.created_at,
            (
                SELECT COALESCE(SUM(p.amount), 0) 
                FROM payments p
                WHERE (p.booking_type = 'hotel' AND p.booking_id IN (SELECT id FROM hotel_bookings WHERE user_id = u.id))
                   OR (p.booking_type = 'holiday' AND p.booking_id IN (SELECT id FROM holiday_bookings WHERE user_id = u.id))
                   OR (p.booking_type = 'visa' AND p.booking_id IN (SELECT id FROM visa_bookings WHERE user_id = u.id))
            ) as total_paid
            FROM users u 
            ORDER BY u.created_at DESC";
            
    $stmt = $pdo->query($sql);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "data" => $users
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>

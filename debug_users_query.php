<?php
require_once 'db_connect.php';
header('Content-Type: application/json');

try {
    $results = [];
    
    // Check users
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $results['users_count'] = $stmt->fetch()['count'];
    
    // Check payments
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM payments");
    $results['payments_count'] = $stmt->fetch()['count'];
    
    // Check columns
    foreach (['hotel_bookings', 'holiday_bookings', 'visa_bookings'] as $table) {
        $stmt = $pdo->query("SHOW COLUMNS FROM $table LIKE 'user_id'");
        $results[$table . '_user_id_exists'] = !!$stmt->fetch();
    }
    
    // Test the specific query
    $sql = "SELECT u.id, u.name, u.email, u.phone, u.created_at,
            (
                SELECT COALESCE(SUM(p.amount), 0) 
                FROM payments p
                WHERE (p.booking_type = 'hotel' AND p.booking_id IN (SELECT id FROM hotel_bookings WHERE user_id = u.id))
                   OR (p.booking_type = 'holiday' AND p.booking_id IN (SELECT id FROM holiday_bookings WHERE user_id = u.id))
                   OR (p.booking_type = 'visa' AND p.booking_id IN (SELECT id FROM visa_bookings WHERE user_id = u.id))
            ) as total_paid
            FROM users u";
    $stmt = $pdo->query($sql);
    $results['query_test'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($results);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>

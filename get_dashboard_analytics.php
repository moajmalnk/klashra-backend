<?php
/**
 * Dashboard analytics for admin panel.
 */

require_once 'db_connect.php';
header('Content-Type: application/json');

try {
    $months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
    $year = date('Y');

    // Build month buckets for current year
    $trends = [];
    foreach ($months as $idx => $name) {
        $monthNo = $idx + 1;
        $key = sprintf('%04d-%02d', (int)$year, $monthNo);
        $trends[$key] = [
            "name" => $name,
            "enquiries" => 0,
            "bookings" => 0
        ];
    }

    // Count leads (enquiries) by month
    $leadStmt = $pdo->prepare("
        SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS cnt
        FROM leads
        WHERE YEAR(created_at) = :year
        GROUP BY ym
    ");
    $leadStmt->execute([':year' => $year]);
    foreach ($leadStmt->fetchAll() as $row) {
        $ym = $row['ym'] ?? '';
        if (isset($trends[$ym])) {
            $trends[$ym]['enquiries'] = (int)$row['cnt'];
        }
    }

    // Count bookings by month from all booking tables
    $bookingSql = "
        SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS cnt
        FROM (
            SELECT created_at FROM hotel_bookings
            UNION ALL
            SELECT created_at FROM holiday_bookings
            UNION ALL
            SELECT created_at FROM visa_bookings
        ) b
        WHERE YEAR(created_at) = :year
        GROUP BY ym
    ";
    $bookingStmt = $pdo->prepare($bookingSql);
    $bookingStmt->execute([':year' => $year]);
    foreach ($bookingStmt->fetchAll() as $row) {
        $ym = $row['ym'] ?? '';
        if (isset($trends[$ym])) {
            $trends[$ym]['bookings'] = (int)$row['cnt'];
        }
    }

    // Conversion rate = bookings / enquiries (safe)
    $totalEnquiries = 0;
    $totalBookings = 0;
    foreach ($trends as $bucket) {
        $totalEnquiries += (int)$bucket['enquiries'];
        $totalBookings += (int)$bucket['bookings'];
    }
    $conversionRate = $totalEnquiries > 0
        ? round(($totalBookings / $totalEnquiries) * 100, 1)
        : 0;

    echo json_encode([
        "status" => "success",
        "data" => [
            "trends" => array_values($trends),
            "conversionRate" => $conversionRate
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Failed to fetch analytics"
    ]);
}
?>

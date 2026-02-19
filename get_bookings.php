<?php
/**
 * Admin API to fetch all bookings categorized by type
 */

require_once 'db_connect.php';

try {
    // Fetch Hotel Bookings with Hotel names
    $stmt = $pdo->query("
        SELECT hb.*, h.name as hotelName 
        FROM hotel_bookings hb
        LEFT JOIN hotels h ON hb.hotelId = h.id
        ORDER BY hb.created_at DESC
    ");
    $hotelBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Format Hotel Bookings
    $formattedHotels = [];
    foreach ($hotelBookings as $hb) {
        $formattedHotels[] = [
            "id" => $hb['id'],
            "bookingId" => $hb['bookingId'],
            "customerDetails" => [
                "name" => $hb['customerName'],
                "email" => $hb['email'],
                "phone" => $hb['phone']
            ],
            "hotelReference" => $hb['hotelId'],
            "hotelName" => $hb['hotelName'],
            "roomType" => $hb['roomType'],
            "checkIn" => $hb['checkIn'],
            "checkOut" => $hb['checkOut'],
            "totalAmount" => (float)$hb['totalAmount'],
            "status" => $hb['status'],
            "created_at" => $hb['created_at']
        ];
    }

    // Fetch Holiday Bookings with Destination name
    $stmt = $pdo->query("
        SELECT hb.*, d.destination as destinationName
        FROM holiday_bookings hb
        LEFT JOIN holidays h ON hb.packageId = h.id
        LEFT JOIN destinations d ON h.destinationId = d.id
        ORDER BY hb.id DESC
    ");
    $holidayBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Decode JSON fields for holiday bookings
    foreach ($holidayBookings as &$hb) {
        if (isset($hb['travelerDetails'])) {
            $decoded = json_decode($hb['travelerDetails'], true);
            $hb['travelerDetails'] = $decoded !== null ? $decoded : [];
        }
    }
    // Format Holiday Bookings
    $formattedHolidays = [];
    foreach ($holidayBookings as $hb) {
        $formattedHolidays[] = [
            "id" => $hb['id'],
            "packageId" => $hb['packageId'],
            "packageName" => $hb['packageName'],
            "customerDetails" => [
                "name" => $hb['customerName'],
                "email" => $hb['email'],
                "phone" => $hb['phone']
            ],
            "departureDate" => $hb['departureDate'],
            "travelers" => (int)$hb['travelers'],
            "totalAmount" => (float)$hb['totalAmount'],
            "status" => $hb['status'],
            "travelerDetails" => $hb['travelerDetails'],
            "destinationName" => $hb['destinationName'] ?? "N/A"
        ];
    }

    // Fetch Visa Bookings
    $stmt = $pdo->query("SELECT * FROM visa_bookings ORDER BY submissionDate DESC");
    $visaBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "data" => [
            "hotels" => $formattedHotels,
            "holidays" => $formattedHolidays,
            "visas" => $visaBookings
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>

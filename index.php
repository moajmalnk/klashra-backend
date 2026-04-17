<?php
/**
 * Klashar API - Central Router & Security Gateway
 * Handling all deployment-ready routes and security headers.
 */

// 1. Security Headers (Best Practices)
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");

// Only enforce HTTPS/HSTS if not on localhost to prevent development CORS issues
if ($_SERVER['HTTP_HOST'] !== 'localhost' && $_SERVER['HTTP_HOST'] !== '127.0.0.1') {
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
}
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; object-src 'none';");

// 2. Handle CORS (Inherited from db_connect, but centralized here for better control)
require_once 'db_connect.php'; 

// 3. Routing Logic
$request_uri = $_SERVER['REQUEST_URI'] ?? '/';
$route = parse_url($request_uri, PHP_URL_PATH) ?? '/';
$route = trim($route, '/');

// Support requests that come through a proxy prefix (e.g. /api/user_auth.php)
if (strpos($route, 'api/') === 0) {
    $route = substr($route, 4);
}

// If an actual PHP file is requested, serve it directly.
// This prevents router 404s for endpoints like user_auth.php.
if ($route !== '' && substr($route, -4) === '.php') {
    $directFile = __DIR__ . '/' . $route;
    if (file_exists($directFile)) {
        require_once $directFile;
        exit();
    }
}

// Route Mapping
$routes = [
    // Holiday Management
    'holidays'           => 'get_holidays_joined.php',
    'holidays/create'    => 'create_holiday.php',
    'holidays/update'    => 'update_holiday.php',
    'holidays/delete'    => 'delete_holiday.php',
    'holidays/status'    => 'update_status.php',
    'holidays/filter'    => 'filter_holidays.php',
    
    // Admin & Auth
    'login'              => 'login.php',
    'user_auth'          => 'user_auth.php',
    'my-bookings'        => 'get_my_bookings.php',
    'stats'              => 'get_dashboard_stats.php',
    'settings'           => 'get_settings.php',
    'settings/save'      => 'save_settings.php',
    
    // Bookings & Public
    'bookings'           => 'get_bookings.php',
    'bookings/submit'    => 'submit_booking.php',
    'search'             => 'search.php',
    'upload'             => 'upload_image.php',
    'destinations'       => 'get_destinations.php',
    'destinations/create'=> 'create_destination.php',
    'destinations/update'=> 'update_destination.php',
    'destinations/delete'=> 'delete_destination.php',
    'countries'          => 'get_countries.php',
    'countries/create'   => 'create_country.php',
    'countries/update'   => 'update_country.php',
    'countries/delete'   => 'delete_country.php',
    'visas/all'          => 'get_visas.php',
    'visas/create'       => 'create_visa.php',
    'visas/update'       => 'update_visa.php',
    'visas/delete'       => 'delete_visa.php',
    'bookings'           => 'get_bookings.php',
    'bookings/update-status'=> 'update_booking_status.php',
    'bookings/delete'    => 'delete_booking.php',
    'hotels'             => 'get_hotels.php',
    'hotels/create'      => 'create_hotel.php',
    'hotels/update'      => 'update_hotel.php',
    'hotels/delete'      => 'delete_hotel.php',
    'rooms'              => 'get_rooms.php',
    'rooms/create'       => 'create_room.php',
    'rooms/update'       => 'update_room.php',
    'rooms/delete'       => 'delete_room.php',
    'leads'              => 'get_leads.php',
    'leads/update-status'=> 'update_lead_status.php',
    'leads/delete'       => 'delete_lead.php',

    // Payments (Stripe)
    // Keep backward-compatible path used in Stripe dashboard / older clients.
    'payment/hook'       => 'stripe_webhook.php',
    'payments/hook'      => 'stripe_webhook.php',
    'stripe_webhook'     => 'stripe_webhook.php',
];

// Check if the route exists
if (array_key_exists($route, $routes)) {
    require_once $routes[$route];
} else {
    // Route not found
    http_response_code(404);
    echo json_encode([
        "status" => "error",
        "message" => "Endpoint not found: $route"
    ]);
}
?>

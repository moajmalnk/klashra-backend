<?php
/**
 * Klashar API - Central Router & Security Gateway
 * Handling all deployment-ready routes and security headers.
 */

// 1. Security Headers (Best Practices)
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
header("Content-Security-Policy: default-src 'self'; script-src 'self'; object-src 'none';");

// 2. Handle CORS (Inherited from db_connect, but centralized here for better control)
require_once 'db_connect.php'; 

// 3. Routing Logic
$request_uri = $_SERVER['REQUEST_URI'];
$base_path = '/klashra/klashra-backend/'; // Adjust based on your server folder
$route = str_replace($base_path, '', $request_uri);
$route = explode('?', $route)[0]; // Remove query strings

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
    'stats'              => 'get_dashboard_stats.php',
    'settings'           => 'get_settings.php',
    'settings/save'      => 'save_settings.php',
    
    // Bookings & Public
    'bookings'           => 'get_bookings.php',
    'bookings/submit'    => 'submit_booking.php',
    'search'             => 'search.php',
    'upload'             => 'upload_image.php'
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

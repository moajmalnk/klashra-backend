<?php
/**
 * Database Connection using PDO
 * Includes CORS headers for React frontend integration
 */

$host = "localhost";
$db_name = "klashar_db"; // As specified in requirements
$username = "root";      // XAMPP Default
$password = "";          // XAMPP Default

try {
    // Standard PDO connection string
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password);
    
    // Set error mode to exception to catch errors in try-catch blocks
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // --- CORS Headers ---
    // Specifically allow the React frontend port 8080 as requested in vite.config.ts
    // In production, this should be your actual domain.
    $allowed_origins = ["http://localhost:8080", "http://localhost:5173", "http://localhost:3000"];
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    
    if (in_array($origin, $allowed_origins)) {
        header("Access-Control-Allow-Origin: $origin");
    } else {
        header("Access-Control-Allow-Origin: http://localhost:8080"); // Fallback
    }

    header("Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE, PUT");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    header("Content-Type: application/json; charset=UTF-8");

    // Handle Preflight OPTIONS request (sent by browser before POST)
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        http_response_code(200);
        exit();
    }
} catch(PDOException $e) {
    // Return connection error as JSON
    header("Content-Type: application/json");
    echo json_encode([
        "status" => "error", 
        "message" => "Connection failed: " . $e->getMessage()
    ]);
    exit();
}
?>

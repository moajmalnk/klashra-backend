<?php
/**
 * Database Connection using PDO
 * Includes CORS headers for React frontend integration
 */

$host = "localhost";
$db_name = "klashar_db"; // As specified in requirements
$username = "root";      // XAMPP Default
$password = "";          // XAMPP Default

// --- CORS Headers (MUST be before any output) ---
$allowed_origins = [
    "http://localhost:8080", 
    "http://localhost:8081", 
    "http://localhost:8082", 
    "http://localhost:5173", 
    "http://localhost:3000"
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    // For local development with multiple ports, we can use * or the first allowed origin
    header("Access-Control-Allow-Origin: " . ($allowed_origins[0] ?? '*')); 
}

header("Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE, PUT");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Origin, Accept");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");

// Handle Preflight OPTIONS request (sent by browser before POST)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Standard PDO connection string
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password);
    
    // Set error mode to exception to catch errors in try-catch blocks
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    // Return connection error as JSON
    http_response_code(500);
    echo json_encode([
        "status" => "error", 
        "message" => "Database Connection failed: " . $e->getMessage()
    ]);
    exit();
}
?>

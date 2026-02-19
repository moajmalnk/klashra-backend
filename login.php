<?php
/**
 * Admin Login API
 * Verifies credentials using password_verify
 */

require_once 'db_connect.php';

// Get the raw POST data
$json_data = file_get_contents("php://input");
$data = json_decode($json_data, true);

if (!isset($data['username']) || !isset($data['password'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Username and password are required"
    ]);
    exit();
}

try {
    $username = $data['username'];
    $password = $data['password'];

    // Fetch admin by username
    $sql = "SELECT id, username, password FROM admins WHERE username = :username LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        // Successful login
        // In a real app, you would start a session or generate a JWT here
        echo json_encode([
            "status" => "success",
            "message" => "Login successful",
            "user" => [
                "id" => $admin['id'],
                "username" => $admin['username'],
                "role" => "admin"
            ]
        ]);
    } else {
        // Invalid credentials
        echo json_encode([
            "status" => "error",
            "message" => "Invalid username or password"
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>

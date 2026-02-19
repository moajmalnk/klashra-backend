<?php
/**
 * API for handling file uploads
 * Saves to 'uploads/' folder and returns the path
 */

require_once 'db_connect.php';

// Define the upload directory
$upload_dir = 'uploads/';

// Create directory if it doesn't exist
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Check if file was uploaded
if (!isset($_FILES['image'])) {
    echo json_encode([
        "status" => "error",
        "message" => "No image file uploaded"
    ]);
    exit();
}

$file = $_FILES['image'];
$file_name = $file['name'];
$file_tmp = $file['tmp_name'];
$file_error = $file['error'];

// Check for PHP upload errors
if ($file_error !== UPLOAD_ERR_OK) {
    echo json_encode([
        "status" => "error",
        "message" => "Upload error code: " . $file_error
    ]);
    exit();
}

// Security: Generate a unique filename to prevent overwrites and basic sanitization
$file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
$allowed_extensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

if (!in_array($file_ext, $allowed_extensions)) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid file extension. Allowed: " . implode(', ', $allowed_extensions)
    ]);
    exit();
}

$new_file_name = uniqid('img_', true) . '.' . $file_ext;
$destination = $upload_dir . $new_file_name;

if (move_uploaded_file($file_tmp, $destination)) {
    // Return the full relative path for the frontend to use
    // Assuming the backend is at some URL, this path is relative to it
    echo json_encode([
        "status" => "success",
        "message" => "File uploaded successfully",
        "file_path" => $destination,
        "full_url" => "http://" . $_SERVER['HTTP_HOST'] . "/klashra/klashra-backend/" . $destination
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to move uploaded file"
    ]);
}
?>

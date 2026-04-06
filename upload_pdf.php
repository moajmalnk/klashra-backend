<?php
/**
 * API for handling PDF brochure uploads
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
// The frontend uses 'pdf' as the key in FormData
if (!isset($_FILES['pdf'])) {
    echo json_encode([
        "status" => "error",
        "message" => "No PDF file uploaded. Key 'pdf' not found."
    ]);
    exit();
}

$file = $_FILES['pdf'];
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

// Security: Generate a unique filename to prevent overwrites
$file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
$allowed_extensions = ['pdf'];

if (!in_array($file_ext, $allowed_extensions)) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid file extension. Only PDF allowed."
    ]);
    exit();
}

// Generate a clean filename
$clean_name = preg_replace("/[^a-zA-Z0-0.]/", "_", pathinfo($file_name, PATHINFO_FILENAME));
$new_file_name = 'brochure_' . $clean_name . '_' . uniqid() . '.' . $file_ext;
$destination = $upload_dir . $new_file_name;

if (move_uploaded_file($file_tmp, $destination)) {
    echo json_encode([
        "status" => "success",
        "message" => "PDF uploaded successfully",
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

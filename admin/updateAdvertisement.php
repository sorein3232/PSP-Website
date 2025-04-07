<?php
session_start();
include 'database.php'; // Ensure this file contains your database connection

// Function to validate image file with improved error handling
function isValidImageFile($file) {
    // Allowed image file extensions
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
    
    // Allowed MIME types
    $allowedMimeTypes = [
        'image/jpeg',
        'image/png', 
        'image/gif', 
        'image/webp', 
        'image/bmp'
    ];

    // Maximum file size (5MB)
    $maxFileSize = 5 * 1024 * 1024; // 5 MB in bytes

    // Check if file was uploaded
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['upload_error'] = "No file uploaded or file upload failed.";
        return false;
    }

    // Check file size
    if ($file['size'] > $maxFileSize) {
        $_SESSION['upload_error'] = "File is too large. Maximum file size is 5MB.";
        return false;
    }

    // Get file extension
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Get file MIME type
    $fileMimeType = mime_content_type($file['tmp_name']);

    // Check file extension
    if (!in_array($fileExtension, $allowedExtensions)) {
        $_SESSION['upload_error'] = "Invalid file type. Allowed extensions are: " . implode(', ', $allowedExtensions);
        return false;
    }

    // Check MIME type
    if (!in_array($fileMimeType, $allowedMimeTypes)) {
        $_SESSION['upload_error'] = "Detected file type is not allowed. Only image files are permitted.";
        return false;
    }

    // Additional check for image validity
    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        $_SESSION['upload_error'] = "Invalid image file. The file could not be processed as an image.";
        return false;
    }

    return true;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate input
    if (!isset($_POST['id']) || !isset($_POST['title']) || !isset($_POST['description'])) {
        $_SESSION['error'] = "Missing required parameters.";
        header("Location: advertisement.php");
        exit();
    }

    $id = intval($_POST['id']);
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $old_image = $_POST['old_image'] ?? '';

    // Validate input fields
    if (empty($title) || empty($description)) {
        $_SESSION['error'] = "Title and description cannot be empty.";
        header("Location: advertisement.php");
        exit();
    }

    // Get current timestamp from user's device
    $updated_at = date('Y-m-d H:i:s');

    // Handle image upload
    $image = $old_image; // Default to old image
    if (!empty($_FILES['file_upload']['name'])) {
        // Validate image file
        if (isValidImageFile($_FILES['file_upload'])) {
            $target_dir = "uploads/";
            
            // Ensure upload directory exists
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            // Generate a unique filename to prevent overwriting
            $filename = uniqid() . '_' . basename($_FILES["file_upload"]["name"]);
            $image = $target_dir . $filename;
            
            // Move uploaded file
            if (!move_uploaded_file($_FILES["file_upload"]["tmp_name"], $image)) {
                // Handle upload error
                $_SESSION['error'] = "File upload failed.";
                header("Location: advertisement.php");
                exit();
            }

            // Delete old image if it exists and is different
            if (!empty($old_image) && $old_image != $image && file_exists($old_image)) {
                unlink($old_image);
            }
        } else {
            // Invalid file type (error is already set in isValidImageFile)
            header("Location: advertisement.php");
            exit();
        }
    }

    // Prepare and execute update statement
    try {
        $stmt = $conn->prepare("UPDATE advertisements SET title = ?, image = ?, description = ?, created_at = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $title, $image, $description, $updated_at, $id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Advertisement updated successfully.";
        } else {
            $_SESSION['error'] = "Failed to update advertisement. " . $stmt->error;
        }
        
        $stmt->close();
    } catch (Exception $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }

    header("Location: advertisement.php");
    exit();
} else {
    // If accessed directly without POST method
    $_SESSION['error'] = "Invalid access method.";
    header("Location: advertisement.php");
    exit();
}
?>
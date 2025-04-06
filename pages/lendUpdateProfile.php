<?php
header('Content-Type: application/json');
session_start();

// Database connection
$conn = mysqli_connect('localhost', 'root', 'figureitout', 'LMSDB');
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Get the POST data
$data = json_decode(file_get_contents('php://input'), true);

// Validate JSON data
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit();
}

// Basic validation
if (!isset($_SESSION['user_id']) || empty($data['lender_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if (empty($data['name']) || empty($data['email']) || empty($data['phone'])) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit();
}

// Sanitize inputs
$lender_id = (int)$data['lender_id'];
$name = mysqli_real_escape_string($conn, $data['name']);
$email = mysqli_real_escape_string($conn, $data['email']);
$phone = mysqli_real_escape_string($conn, $data['phone']);

// Start transaction
mysqli_begin_transaction($conn);

try {
    // 1. Update lenders table
    $query = "UPDATE lenders SET 
              name = '$name',
              email = '$email',
              phone = '$phone'
              WHERE lender_id = $lender_id";
    
    if (!mysqli_query($conn, $query)) {
        throw new Exception('Failed to update lender details');
    }

    // 2. Update users table
    $query = "UPDATE users SET 
              user_name = '$name',
              email = '$email',
              phone = '$phone'
              WHERE user_id = " . (int)$_SESSION['user_id'];
    
    if (!mysqli_query($conn, $query)) {
        throw new Exception('Failed to update user account');
    }

    // Commit transaction
    mysqli_commit($conn);

    // Update session data
    $_SESSION['user_name'] = $name;
    $_SESSION['profile_message'] = 'Profile updated successfully';
    $_SESSION['profile_message_type'] = 'success';
    
    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

mysqli_close($conn);
?>
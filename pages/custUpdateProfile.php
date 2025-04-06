<?php
header('Content-Type: application/json');
session_start();

// Database connection
$conn = mysqli_connect('localhost', 'root', 'figureitout', 'LMSDB');
if (!$conn) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed'
    ]);
    exit();
}

// Get the POST data
$data = json_decode(file_get_contents('php://input'), true);

// Basic validation
if (!isset($_SESSION['user_id']) || !isset($data['customer_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit();
}

// Initialize success flag
$success = true;
$message = '';

// Update customers table
$customerUpdates = [];
foreach ($data as $field => $value) {
    if (in_array($field, ['name', 'email', 'phone', 'address', 'bank_account'])) {
        $customerUpdates[] = "$field = '" . mysqli_real_escape_string($conn, $value) . "'";
    }
}

if (!empty($customerUpdates)) {
    $query = "UPDATE customers SET " . implode(', ', $customerUpdates) . 
            " WHERE customer_id = " . (int)$data['customer_id'];
    if (!mysqli_query($conn, $query)) {
        $success = false;
        $message = 'Failed to update customer details';
    }
}

// Update users table
$userUpdates = [];
if (isset($data['name'])) {
    $userUpdates[] = "user_name = '" . mysqli_real_escape_string($conn, $data['name']) . "'";
}
if (isset($data['email'])) {
    $userUpdates[] = "email = '" . mysqli_real_escape_string($conn, $data['email']) . "'";
}
if (isset($data['phone'])) {
    $userUpdates[] = "phone = '" . mysqli_real_escape_string($conn, $data['phone']) . "'";
}

if (!empty($userUpdates)) {
    $query = "UPDATE users SET " . implode(', ', $userUpdates) . 
            " WHERE user_id = " . (int)$_SESSION['user_id'];
    if (!mysqli_query($conn, $query)) {
        $success = false;
        $message = 'Failed to update user account';
    }
}

// Update session data
if (isset($data['name'])) {
    $_SESSION['user_name'] = $data['name'];
}

// Set response
if ($success) {
    $_SESSION['profile_message'] = 'Profile updated successfully';
    $_SESSION['profile_message_type'] = 'success';
    echo json_encode([
        'success' => true,
        'newName' => $data['name'] ?? null
    ]);
} else {
    $_SESSION['profile_message'] = $message ?: 'Profile update failed';
    $_SESSION['profile_message_type'] = 'error';
    echo json_encode([
        'success' => false,
        'message' => $message ?: 'Profile update failed'
    ]);
}

mysqli_close($conn);
?>
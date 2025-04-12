<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Database connection
$conn = mysqli_connect('localhost', 'root', 'figureitout', 'LMSDB');
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Basic validation
if (!isset($_SESSION['user_id']) || empty($_POST['customer_id'])) {
    $_SESSION['profile_message'] = "Unauthorized access";
    $_SESSION['profile_message_type'] = "error";
    header("Location: customerDashboard.php#profile");
    exit;
}

// Initialize success flag
$success = true;
$message = '';

// Track changed fields for logging
$changedFields = [];

// Update customers table
$customerUpdates = [];
foreach ($_POST as $field => $value) {
    if (in_array($field, ['name', 'email', 'phone', 'address', 'bank_account'])) {
        $escapedValue = $conn->real_escape_string($value);
        $customerUpdates[] = "$field = '$escapedValue'";
        $changedFields[$field] = $escapedValue;
    }
}

if (!empty($customerUpdates)) {
    $query = "UPDATE customers SET " . implode(', ', $customerUpdates) . 
            " WHERE customer_id = " . (int)$_POST['customer_id'];
    if (!$conn->query($query)) {
        $success = false;
        $message = 'Failed to update customer details';
    }
}

// Update users table
$userUpdates = [];
if (isset($_POST['name'])) {
    $userUpdates[] = "user_name = '" . $conn->real_escape_string($_POST['name']) . "'";
}
if (isset($_POST['email'])) {
    $userUpdates[] = "email = '" . $conn->real_escape_string($_POST['email']) . "'";
}
if (isset($_POST['phone'])) {
    $userUpdates[] = "phone = '" . $conn->real_escape_string($_POST['phone']) . "'";
}

if (!empty($userUpdates)) {
    $query = "UPDATE users SET " . implode(', ', $userUpdates) . 
            " WHERE user_id = " . (int)$_SESSION['user_id'];
    if (!$conn->query($query)) {
        $success = false;
        $message = 'Failed to update user account';
    }
}

// Update session data
if (isset($_POST['name'])) {
    $_SESSION['user_name'] = $_POST['name'];
}

// Log profile update activity if successful
if ($success && !empty($changedFields)) {
    $activityDetails = [];
    foreach ($changedFields as $field => $value) {
        $activityDetails[] = "$field: $value";
    }
    $activity = "Updated profile";
    $conn->query(
        "INSERT INTO activity (user_id, activity, activity_time, activity_type)
        VALUES ({$_SESSION['user_id']}, '$activity', NOW(), 'profile update')"
    );
}

// Set response
if ($success) {
    $_SESSION['profile_message'] = 'Profile updated successfully';
    $_SESSION['profile_message_type'] = 'success';
} else {
    $_SESSION['profile_message'] = $message ?: 'Profile update failed';
    $_SESSION['profile_message_type'] = 'error';
}

header("Location: customerDashboard.php#profile");
exit;
?>
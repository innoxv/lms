<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Database config file
include '../phpconfig/config.php';

// Basic validation
if (!isset($_SESSION['user_id']) || empty($_POST['lender_id'])) {
    $_SESSION['profile_message'] = "Unauthorized access";
    $_SESSION['profile_message_type'] = "error";
    header("Location: lenderDashboard.php#profile");
    exit;
}

// Initialize success flag
$success = true;
$message = '';

// Track changed fields for logging
$changedFields = [];

// Update lenders table
$lenderUpdates = [];
foreach ($_POST as $field => $value) {
    if (in_array($field, ['name', 'email', 'phone', 'address'])) {
        $escapedValue = $myconn->real_escape_string($value);
        $lenderUpdates[] = "$field = '$escapedValue'";
        $changedFields[$field] = $escapedValue;
    }
}

if (!empty($lenderUpdates)) {
    $query = "UPDATE lenders SET " . implode(', ', $lenderUpdates) . 
            " WHERE lender_id = " . (int)$_POST['lender_id'];
    if (!$myconn->query($query)) {
        $success = false;
        $message = 'Failed to update lender details';
    }
}

// Update users table
$userUpdates = [];
if (isset($_POST['name'])) {
    $userUpdates[] = "user_name = '" . $myconn->real_escape_string($_POST['name']) . "'";
}
if (isset($_POST['email'])) {
    $userUpdates[] = "email = '" . $myconn->real_escape_string($_POST['email']) . "'";
}
if (isset($_POST['phone'])) {
    $userUpdates[] = "phone = '" . $myconn->real_escape_string($_POST['phone']) . "'";
}

if (!empty($userUpdates)) {
    $query = "UPDATE users SET " . implode(', ', $userUpdates) . 
            " WHERE user_id = " . (int)$_SESSION['user_id'];
    if (!$myconn->query($query)) {
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
    $activity = "Updated profile" ;
    $myconn->query(
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

header("Location: lenderDashboard.php#profile");
exit;
?>
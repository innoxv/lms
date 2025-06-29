<?php
// Initiates or resumes a session to manage user state
session_start(); // Starts a new session or resumes an existing one

// Includes the database configuration file to establish the $myconn connection
include '../phpconfig/config.php'; // Imports database connection settings from config.php

// Performs basic validation to ensure user is logged in and lender_id is provided
if (!isset($_SESSION['user_id']) || empty($_POST['lender_id'])) { // isset() checks if user_id is set; empty() checks if lender_id is provided
    $_SESSION['profile_message'] = "Unauthorized access"; // Sets an error message in the session
    $_SESSION['profile_message_type'] = "error"; // Sets the message type to error
    header("Location: lenderDashboard.php#profile"); // Redirects to the profile section of lenderDashboard.php
    exit; // Terminates script execution after redirection
}

// Initializes a success flag to track update status
$success = true; // Sets initial value to true, assuming success
$message = ''; // Initializes an empty string for error messages

// Initializes an array to track changed fields for activity logging
$changedFields = []; // Empty array to store fields that were modified

// Builds updates for the lenders table based on submitted form data
$lenderUpdates = []; // Initializes an empty array for lenders table updates
foreach ($_POST as $field => $value) { // Iterates through each POST field
    if (in_array($field, ['name', 'email', 'phone', 'address'])) { // in_array() checks if the field is allowed for update
        $escapedValue = $myconn->real_escape_string($value); // Escapes special characters in the value for SQL safety
        $lenderUpdates[] = "$field = '$escapedValue'"; // Adds the update clause to the array
        $changedFields[$field] = $escapedValue; // Tracks the changed field and its value for logging
    }
}

// Executes the update query for the lenders table if there are updates
if (!empty($lenderUpdates)) { // Checks if there are any lender updates
    $query = "UPDATE lenders SET " . implode(', ', $lenderUpdates) . 
            " WHERE lender_id = " . (int)$_POST['lender_id']; // Builds SQL query to update lenders table
    if (!$myconn->query($query)) { // Executes query and checks if it failed
        $success = false; // Sets success flag to false on failure
        $message = 'Failed to update lender details'; // Sets error message
    }
}

// Builds updates for the users table based on submitted form data
$userUpdates = []; // Initializes an empty array for users table updates
if (isset($_POST['name'])) { // Checks if name field was submitted
    $userUpdates[] = "user_name = '" . $myconn->real_escape_string($_POST['name']) . "'"; // Adds user_name update clause
}
if (isset($_POST['email'])) { // Checks if email field was submitted
    $userUpdates[] = "email = '" . $myconn->real_escape_string($_POST['email']) . "'"; // Adds email update clause
}
if (isset($_POST['phone'])) { // Checks if phone field was submitted
    $userUpdates[] = "phone = '" . $myconn->real_escape_string($_POST['phone']) . "'"; // Adds phone update clause
}

// Executes the update query for the users table if there are updates
if (!empty($userUpdates)) { // Checks if there are any user updates
    $query = "UPDATE users SET " . implode(', ', $userUpdates) . 
            " WHERE user_id = " . (int)$_SESSION['user_id']; // Builds SQL query to update users table
    if (!$myconn->query($query)) { // Executes query and checks if it failed
        $success = false; // Sets success flag to false on failure
        $message = 'Failed to update user account'; // Sets error message
    }
}

// Updates session data with the new user name if provided
if (isset($_POST['name'])) { // Checks if name field was submitted
    $_SESSION['user_name'] = $_POST['name']; // Updates the session with the new user name
}

// Logs profile update activity if the update was successful and fields were changed
if ($success && !empty($changedFields)) { // Checks if update was successful and fields were modified
    $activityDetails = []; // Initializes an array to store activity details
    foreach ($changedFields as $field => $value) { // Iterates through changed fields
        $activityDetails[] = "$field: $value"; // Formats each changed field for logging
    }
    $activity = "Updated profile"; // Defines the activity description
    $myconn->query(
        "INSERT INTO activity (user_id, activity, activity_time, activity_type)
        VALUES ({$_SESSION['user_id']}, '$activity', NOW(), 'profile update')"
    ); // Executes SQL query to log user_id, activity description, current timestamp, and 'profile update' type
}

// Sets the response message based on the success flag
if ($success) { // Checks if the update was successful
    $_SESSION['profile_message'] = 'Profile updated successfully'; // Sets success message in session
    $_SESSION['profile_message_type'] = 'success'; // Sets the message type to success
} else {
    $_SESSION['profile_message'] = $message ?: 'Profile update failed'; // Sets error message or default if none set
    $_SESSION['profile_message_type'] = 'error'; // Sets the message type to error
}

// Redirects to the profile section of the lender dashboard
header("Location: lenderDashboard.php#profile"); // Sends HTTP header to redirect
exit; // Terminates script execution after redirection
?>
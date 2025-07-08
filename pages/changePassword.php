<?php
// Starts or resumes the session for user authentication and messaging
session_start(); // Initiates or resumes a session to manage user state and messages

// Includes the database configuration file for $myconn
include '../phpconfig/config.php'; // Imports database connection settings from config.php

// Checks if the user is logged in
if (!isset($_SESSION['user_id'])) { // Checks if user_id is set in the session to verify authentication
    $_SESSION['profile_message'] = "Unauthorized access"; // Sets error message for unauthorized access
    $_SESSION['profile_message_type'] = "error"; // Sets message type to error
    header("Location: login.php"); // Redirects to login page for unauthenticated users
    exit(); // Terminates script execution after redirection
}

// Gets the user ID from the session
$user_id = (int)$_SESSION['user_id']; // Converts and stores user_id from session as an integer for security

// Retrieves the user's role from the session or database
$role = $_SESSION['role'] ?? ''; // Retrieves role from session, defaults to empty if not set
if (empty($role)) { // Checks if role is not set in session
    $query = "SELECT role FROM users WHERE user_id = ?"; // Prepares query to fetch user role
    $stmt = $myconn->prepare($query); // Prepares the statement to prevent SQL injection
    $stmt->bind_param("i", $user_id); // Binds the user_id as an integer
    $stmt->execute(); // Executes the query
    $result = $stmt->get_result(); // Gets the result set
    if ($result && $result->num_rows > 0) { // Checks if query succeeded and user exists
        $row = $result->fetch_assoc(); // Fetches the role from the result set
        $role = $row['role']; // Stores the user role
        $_SESSION['role'] = $role; // Stores role in session for future use
    } else {
        $_SESSION['profile_message'] = "User role not found."; // Sets error message for missing role
        $_SESSION['profile_message_type'] = "error"; // Sets message type to error
        header("Location: login.php"); // Redirects to login page if role cannot be determined
        exit(); // Terminates script execution after redirection
    }
    $stmt->close(); // Closes the statement
}

// Determines the redirect URL based on the user's role
$redirect_url = ''; // Initializes redirect URL
switch (strtolower($role)) { // Converts role to lowercase for case-insensitive comparison
    case 'customer':
        $redirect_url = 'customerDashboard.php#profile'; // Redirects customers to customer dashboard
        break;
    case 'lender':
        $redirect_url = 'lenderDashboard.php#profile'; // Redirects lenders to lender dashboard
        break;
    case 'admin':
        $redirect_url = 'adminDashboard.php#profile'; // Redirects admins to admin dashboard
        break;
    default:
        $redirect_url = 'login.php'; // Redirects to login page for unknown roles
        $_SESSION['profile_message'] = "Invalid user role."; // Sets error message for unknown role
        $_SESSION['profile_message_type'] = "error"; // Sets message type to error
        break;
}

// Retrieves and sanitizes form inputs
$old_password = $_POST['old_password'] ?? ''; // Retrieves old_password from POST, defaults to empty if not set
$new_password = $_POST['new_password'] ?? ''; // Retrieves new_password from POST, defaults to empty if not set
$confirm_password = $_POST['confirm_password'] ?? ''; // Retrieves confirm_password from POST, defaults to empty if not set

// Validates that all fields are filled
if (!$old_password || !$new_password || !$confirm_password) { // Checks if any field is empty
    $_SESSION['profile_message'] = "All fields are required."; // Sets error message for missing fields
    $_SESSION['profile_message_type'] = "error"; // Sets message type to error
    header("Location: $redirect_url"); // Redirects to the role-specific dashboard
    exit(); // Terminates script execution after redirection
}

// Checks if the new passwords match
if ($new_password !== $confirm_password) { // Verifies that new_password matches confirm_password
    $_SESSION['profile_message'] = "New passwords do not match."; // Sets error message for mismatched passwords
    $_SESSION['profile_message_type'] = "error"; // Sets message type to error
    header("Location: $redirect_url"); // Redirects to the role-specific dashboard
    exit(); // Terminates script execution after redirection
}

// Fetches the current password hash from the database using a prepared statement
$query = "SELECT password FROM users WHERE user_id = ?"; // Prepares query to fetch current password hash
$stmt = $myconn->prepare($query); // Prepares the statement to prevent SQL injection
$stmt->bind_param("i", $user_id); // Binds the user_id as an integer
$stmt->execute(); // Executes the query
$result = $stmt->get_result(); // Gets the result set
if (!$result || $result->num_rows === 0) { // Checks if query failed or no user found
    $_SESSION['profile_message'] = "User not found."; // Sets error message for invalid user
    $_SESSION['profile_message_type'] = "error"; // Sets message type to error
    header("Location: $redirect_url"); // Redirects to the role-specific dashboard
    exit(); // Terminates script execution after redirection
}
$row = $result->fetch_assoc(); // Fetches the password hash from the result set
$current_hash = $row['password']; // Stores the current password hash
$stmt->close(); // Closes the statement

// Verifies the old password
if (!password_verify($old_password, $current_hash)) { // Verifies old_password against the stored hash
    $_SESSION['profile_message'] = "Old password is incorrect."; // Sets error message for incorrect old password
    $_SESSION['profile_message_type'] = "error"; // Sets message type to error
    header("Location: $redirect_url"); // Redirects to the role-specific dashboard
    exit(); // Terminates script execution after redirection
}

// Hashes the new password securely
$new_hash = password_hash($new_password, PASSWORD_DEFAULT); // Generates a secure hash for the new password

// Updates the password in the database using a prepared statement
$stmt = $myconn->prepare("UPDATE users SET password = ? WHERE user_id = ?"); // Prepares update query
$stmt->bind_param("si", $new_hash, $user_id); // Binds the new_hash (string) and user_id (integer)
$update = $stmt->execute(); // Executes the prepared statement
$stmt->close(); // Closes the statement

// Sets a success or error message based on the update result
if ($update) { // Checks if the update was successful
    $_SESSION['profile_message'] = "Password changed successfully."; // Sets success message
    $_SESSION['profile_message_type'] = "success"; // Sets message type to success
    // Logs password update activity if the update was successful
    $myconn->query(
        "INSERT INTO activity (user_id, activity, activity_time, activity_type)
        VALUES ($user_id, 'Updated password', NOW(), 'password update')"
    ); // Executes SQL query to log user_id, activity description, current timestamp, and 'password update' type
} else { // Handles update failure
    $_SESSION['profile_message'] = "Failed to update password."; // Sets error message
    $_SESSION['profile_message_type'] = "error"; // Sets message type to error
}

// Redirects back to the role-specific profile section with a message
header("Location: $redirect_url"); // Redirects to the role-specific dashboard with the message
exit(); // Terminates script execution after redirection
?>
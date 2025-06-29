<?php
// Initiates or resumes a session to manage user state
session_start(); // Starts a new session or resumes an existing one

// Validates that the user is logged in
if (!isset($_SESSION['user_id'])) { // isset() checks if user_id is set in the session
    header("Location: signin.html"); // Redirects to the sign-in page
    exit(); // Terminates script execution after redirection
}

// Includes the database configuration file to establish the $myconn connection
include '../phpconfig/config.php'; // Imports database connection settings from config.php

// Retrieves POST parameters
$userId = $_POST['user_id'] ?? 0; // Gets user_id, defaults to 0
$action = $_POST['action'] ?? ''; // Gets action, defaults to empty string
$newStatus = $_POST['new_status'] ?? ''; // Gets new_status, defaults to empty string
$restrictionType = $_POST['restriction_type'] ?? ''; // Gets restriction_type, defaults to empty string
$role = $_POST['role'] ?? ''; // Gets role, defaults to empty string

// Validates required parameters
if (empty($userId) || empty($action)) { // Checks if user_id or action is missing
    $_SESSION['admin_message'] = "Invalid request"; // Sets error message
    header("Location: adminDashboard.php?role=$roleFilter#viewUsers"); // Redirects to viewUsers section
    exit(); // Terminates script execution after redirection
}

// Checks if user exists and retrieves role
$checkUser = mysqli_query($myconn, "SELECT role, user_name, email FROM users WHERE user_id = '$userId'"); // Queries users table
if (mysqli_num_rows($checkUser) === 0) { // Checks if user was found
    $_SESSION['admin_message'] = "User not found"; // Sets error message
    header("Location: adminDashboard.php?role=$roleFilter#viewUsers"); // Redirects to viewUsers section
    exit(); // Terminates script execution after redirection
}

$userData = mysqli_fetch_assoc($checkUser); // Fetches user data
$role = $userData['role']; // Stores user role
$userName = $userData['user_name']; // Stores user name
$userEmail = $userData['email']; // Stores user email

// Determines the appropriate table and ID field based on role
if ($role === 'Customer') { // Checks if user is a customer
    $table = 'customers'; // Sets table to customers
    $idField = 'customer_id'; // Sets ID field to customer_id
} elseif ($role === 'Lender') { // Checks if user is a lender
    $table = 'lenders'; // Sets table to lenders
    $idField = 'lender_id'; // Sets ID field to lender_id
} else {
    // Prevents modification of admin or other roles
    $_SESSION['admin_message'] = "Cannot modify admin permissions"; // Sets error message
    header("Location: adminDashboard.php?role=$roleFilter#viewUsers"); // Redirects to viewUsers section
    exit(); // Terminates script execution after redirection
}

// Gets user's ID and status in their role table
$roleIdQuery = mysqli_query($myconn, "SELECT $idField, status FROM $table WHERE user_id = '$userId'"); // Queries role-specific table
if (mysqli_num_rows($roleIdQuery) === 0) { // Checks if role record was found
    $_SESSION['admin_message'] = "User role record not found"; // Sets error message
    header("Location: adminDashboard.php?role=$roleFilter#viewUsers"); // Redirects to viewUsers section
    exit(); // Terminates script execution after redirection
}

$roleData = mysqli_fetch_assoc($roleIdQuery); // Fetches role data
$currentStatus = $roleData['status']; // Stores current status
$roleId = $roleData[$idField]; // Stores role-specific ID

// Handles user actions (toggle status or restriction)
switch ($action) { // switch() selects code based on action
    case 'toggle_status':
        // Sets status and activity details
        if ($newStatus === 'inactive') { // Checks if setting to inactive
            $statusToSet = 'inactive'; // Sets status to inactive
            $actionVerb = 'Blocked'; // Sets action description
            $activityType = 'user block'; // Sets activity type
        } elseif ($newStatus === 'active') { // Checks if setting to active
            $statusToSet = 'active'; // Sets status to active
            $actionVerb = 'Unblocked'; // Sets action description
            $activityType = 'user unblock'; // Sets activity type
        } else {
            $_SESSION['admin_message'] = "Invalid status"; // Sets error message
            header("Location: adminDashboard.php?role=$roleFilter#viewUsers"); // Redirects to viewUsers section
            exit(); // Terminates script execution after redirection
        }

        // Updates users table
        $query = "UPDATE users SET status = ? WHERE user_id = ?"; // Query to update users table
        $stmt = mysqli_prepare($myconn, $query); // Prepares the query
        mysqli_stmt_bind_param($stmt, "si", $statusToSet, $userId); // Binds status and user_id
        mysqli_stmt_execute($stmt); // Executes the query

        // Updates role-specific table
        $query = "UPDATE $table SET status = ? WHERE $idField = ?"; // Query to update role table
        $stmt = mysqli_prepare($myconn, $query); // Prepares the query
        mysqli_stmt_bind_param($stmt, "si", $statusToSet, $roleId); // Binds status and role ID
        mysqli_stmt_execute($stmt); // Executes the query

        // Logs the status change activity
        $activity = "$actionVerb user $userEmail"; // Creates activity description
        mysqli_query($myconn,
            "INSERT INTO activity (user_id, activity, activity_time, activity_type)
            VALUES ({$_SESSION['user_id']}, '$activity', NOW(), '$activityType')"
        ); // Logs activity

        $_SESSION['admin_message'] = ($statusToSet === 'active') ? "User is Unblocked" : "User is Blocked"; // Sets success message
        break;

    case 'toggle_restriction':
        // Handles restrictions for customers
        if ($role === 'Customer' && $restrictionType === 'apply_loan') { // Checks if restricting customer loan applications
            $newStatus = ($currentStatus === 'restricted_apply') ? 'active' : 'restricted_apply'; // Toggles status
            $query = "UPDATE customers SET status = ? WHERE customer_id = ?"; // Query to update customers table
            $stmt = mysqli_prepare($myconn, $query); // Prepares the query
            mysqli_stmt_bind_param($stmt, "si", $newStatus, $roleId); // Binds status and customer_id
            mysqli_stmt_execute($stmt); // Executes the query
            
            // Logs the restriction activity
            $actionText = ($newStatus === 'active') ? 'Unrestricted loan application for' : 'Restricted loan application for'; // Sets action text
            $activity = "$actionText user $userEmail"; // Creates activity description
            mysqli_query($myconn,
                "INSERT INTO activity (user_id, activity, activity_time, activity_type)
                VALUES ({$_SESSION['user_id']}, '$activity', NOW(), 'user restriction')"
            ); // Logs activity
            
            $_SESSION['admin_message'] = ($newStatus === 'active') ? 'User can now apply for loans' : 'User restricted from applying for loans'; // Sets success message
        } 
        // Handles restrictions for lenders
        elseif ($role === 'Lender' && $restrictionType === 'create_loan') { // Checks if restricting lender loan creation
            $newStatus = ($currentStatus === 'restricted_create') ? 'active' : 'restricted_create'; // Toggles status
            $query = "UPDATE lenders SET status = ? WHERE lender_id = ?"; // Query to update lenders table
            $stmt = mysqli_prepare($myconn, $query); // Prepares the query
            mysqli_stmt_bind_param($stmt, "si", $newStatus, $roleId); // Binds status and lender_id
            mysqli_stmt_execute($stmt); // Executes the query
            
            // Logs the restriction activity
            $actionText = ($newStatus === 'active') ? 'Unrestricted loan creation to' : 'Restricted loan creation to'; // Sets action text
            $activity = "$actionText user $userEmail"; // Creates activity description
            mysqli_query($myconn,
                "INSERT INTO activity (user_id, activity, activity_time, activity_type)
                VALUES ({$_SESSION['user_id']}, '$activity', NOW(), 'user restriction')"
            ); // Logs activity
            
            $_SESSION['admin_message'] = ($newStatus === 'active') ? 'User can now create loans' : 'User restricted from creating loans'; // Sets success message
        } 
        else {
            $_SESSION['admin_message'] = 'Invalid restriction type for this role'; // Sets error message
        }
        break;

    default:
        $_SESSION['admin_message'] = 'Invalid action'; // Sets error message for invalid action
}

// Closes the database connection
mysqli_close($myconn); // Terminates the database connection
header("Location: adminDashboard.php?role=$roleFilter#viewUsers"); // Redirects to viewUsers section
exit(); // Terminates script execution after redirection
?>
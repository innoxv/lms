<?php
// Initiates or resumes a session to manage user state
session_start(); // Starts a new session or resumes an existing one

// Includes the database configuration file to establish the $myconn connection
include '../phpconfig/config.php'; // Imports database connection settings from config.php

// Validates user session to ensure the user is logged in
if (!isset($_SESSION['user_id'])) { // isset() checks if user_id is set in the session
    $_SESSION['loan_message'] = "You must be logged in to perform this action"; // Sets an error message in the session
    $_SESSION['message_type'] = "error"; // Sets the message type to error
    header("Location: signin.html"); // Redirects to the sign-in page
    exit; // Terminates script execution after redirection
}

// Validates the loan ID from the POST request
if (!isset($_POST['loan_id']) || !is_numeric($_POST['loan_id'])) { // Checks if loan_id is set and numeric
    $_SESSION['loan_message'] = "Invalid loan ID"; // Sets an error message for invalid or missing loan ID
    $_SESSION['message_type'] = "error"; // Sets the message type to error
    header("Location: customerDashboard.php#loanHistory"); // Redirects to the loanHistory section of customerDashboard.php
    exit; // Terminates script execution after redirection
}

// Sanitizes input data to ensure correct types
$loan_id = intval($_POST['loan_id']); // Converts loan_id to integer
$user_id = intval($_SESSION['user_id']); // Converts user_id from session to integer

// Verifies that the loan belongs to the user and is eligible for deletion
$loan_check = $myconn->query(
    "SELECT loans.status, loans.amount, loans.duration 
    FROM loans
    JOIN customers ON loans.customer_id = customers.customer_id
    WHERE loans.loan_id = $loan_id
    AND customers.user_id = $user_id
    LIMIT 1"
); // Executes SQL query to fetch loan details, ensuring the loan belongs to the user, limited to 1 row

// Checks if the loan exists and the user has permission to delete it
if (!$loan_check || $loan_check->num_rows === 0) { // Checks if query failed or no rows were returned
    $_SESSION['loan_message'] = "Loan not found or you don't have permission to delete it"; // Sets error message for invalid loan or permissions
    $_SESSION['message_type'] = "error"; // Sets the message type to error
    header("Location: customerDashboard.php#loanHistory"); // Redirects to the loanHistory section
    exit; // Terminates script execution after redirection
}

// Fetches loan data for further processing
$loan = $loan_check->fetch_assoc(); // mysqli_fetch_assoc() fetches the loan data as an associative array
$status = strtolower($loan['status']); // Converts the loan status to lowercase for consistency

// Deletes the loan from the database
$delete_query = "DELETE FROM loans WHERE loan_id = $loan_id"; // SQL query to delete the loan by loan_id
if ($myconn->query($delete_query)) { // Executes the deletion query and checks if successful
    // Logs the deletion activity
    $activity = "Deleted loan application, Loan ID $loan_id "; // Creates activity description with loan ID
    $myconn->query(
        "INSERT INTO activity (user_id, activity, activity_time, activity_type)
        VALUES ($user_id, '$activity', NOW(), 'application deletion')"
    ); // Executes SQL query to log user_id, activity description, current timestamp, and 'application deletion' type
    
    $_SESSION['loan_message'] = "Loan application deleted successfully"; // Sets success message in session
    $_SESSION['message_type'] = "success"; // Sets the message type to success
} else {
    $_SESSION['loan_message'] = "Database error: " . $myconn->error; // Sets error message with database error
    $_SESSION['message_type'] = "error"; // Sets the message type to error
}

// Redirects to the loanHistory section of the customer dashboard
header("Location: customerDashboard.php#loanHistory"); // Sends HTTP header to redirect
exit; // Terminates script execution after redirection
?>
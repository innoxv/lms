<?php
// Initiates or resumes a session to manage user state
session_start(); // Starts a new session or resumes an existing one

// Includes the database configuration file to establish the $myconn connection
include '../phpconfig/config.php'; // Imports database connection settings from config.php

// Validates that the user is logged in
if (!isset($_SESSION['user_id'])) { // isset() checks if user_id is set in the session
    header("Location: signin.html"); // Redirects to the sign-in page
    exit(); // Terminates script execution after redirection
}

// Handles loan rejection via POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['loan_id'])) { // Checks for POST request and loan_id
    $loanId = (int)$_POST['loan_id']; // Converts loan_id to integer
    $lenderId = (int)$_SESSION['lender_id']; // Converts lender_id from session to integer
    
    // Builds query to reject the loan
    $query = "UPDATE loans SET status = 'rejected' 
              WHERE loan_id = $loanId AND lender_id = $lenderId AND status = 'pending'"; // Updates loan status to rejected
    
    // Executes the query and handles results
    if (mysqli_query($myconn, $query)) { // Executes the query
        if (mysqli_affected_rows($myconn) > 0) { // Checks if any rows were updated
            // Logs loan rejection activity
            $activity = "Rejected loan application #$loanId"; // Creates activity description
            $logSql = "INSERT INTO activity (user_id, activity, activity_time, activity_type)
                      VALUES ('{$_SESSION['user_id']}', '$activity', NOW(), 'loan rejection')"; // Query to log activity
            mysqli_query($myconn, $logSql); // Executes activity log query
            
            $_SESSION['loan_message'] = "Loan $loanId has been rejected!"; // Sets success message
            $_SESSION['message_type'] = 'success'; // Sets message type to success
        } else {
            $_SESSION['loan_message'] = "Loan $loanId has already been disbursed!"; // Sets warning message
            $_SESSION['message_type'] = 'warning'; // Sets message type to warning
        }
    } else {
        $_SESSION['loan_message'] = "Error: " . mysqli_error($myconn); // Sets error message with database error
        $_SESSION['message_type'] = 'error'; // Sets message type to error
    }
}

// Redirects to the loanRequests section of the lender dashboard
header("Location: lenderDashboard.php#loanRequests"); // Sends HTTP header to redirect
exit(); // Terminates script execution after redirection
?>
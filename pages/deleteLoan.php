<?php
// Initiates or resumes a session to manage user state
session_start(); // Starts a new session or resumes an existing one

// Validates user session to ensure the user is logged in and has a lender ID
if (!isset($_SESSION['user_id']) || !isset($_SESSION['lender_id'])) { // isset() checks if user_id and lender_id are set in the session
    $_SESSION['loan_message'] = "Unauthorized access"; // Sets an error message in the session
    header("Location: lenderDashboard.php"); // Redirects to the lender dashboard
    exit(); // Terminates script execution after redirection
}

// Sanitizes the user ID from the session
$user_id = intval($_SESSION['user_id']); // Converts user_id from session to integer

// Includes the database configuration file to establish the $myconn connection
include '../phpconfig/config.php'; // Imports database connection settings from config.php

// Retrieves the offer ID from the POST request
$offer_id = $_POST['offer_id']; // Gets offer_id from POST data

// Validates the offer ID
if (!$offer_id) { // Checks if offer_id is empty or not set
    $_SESSION['loan_message'] = "No loan offer specified"; // Sets an error message for missing offer ID
    header("Location: lenderDashboard.php"); // Redirects to the lender dashboard
    exit(); // Terminates script execution after redirection
}

// Verifies that the loan offer belongs to the current lender
$verifyQuery = "SELECT offer_id, loan_type, interest_rate, max_amount, max_duration 
               FROM loan_offers 
               WHERE offer_id = $offer_id AND lender_id = {$_SESSION['lender_id']}"; // SQL query to check if offer_id matches lender_id
$verifyResult = mysqli_query($myconn, $verifyQuery); // mysqli_query() executes the query on the database connection

// Checks if the loan offer exists and belongs to the lender
if (mysqli_num_rows($verifyResult) === 0) { // mysqli_num_rows() returns the number of rows in the result set
    $_SESSION['loan_message'] = "Loan offer not found or unauthorized"; // Sets error message for invalid or unauthorized offer
    header("Location: lenderDashboard.php"); // Redirects to the lender dashboard
    exit(); // Terminates script execution after redirection
}

// Fetches the loan offer details
$offer = mysqli_fetch_assoc($verifyResult); // mysqli_fetch_assoc() fetches a result row as an associative array

// Checks if there are active or pending loans associated with this offer
$loansCheck = "SELECT COUNT(*) as loan_count FROM loans 
              WHERE offer_id = $offer_id 
              AND status IN ('pending', 'disbursed', 'disbursed', 'active')"; // SQL query to count loans with specific statuses
$loansResult = mysqli_query($myconn, $loansCheck); // Executes the query
$loansData = mysqli_fetch_assoc($loansResult); // Fetches the result as an associative array

// Prevents deletion if there are active or pending loans
if ($loansData['loan_count'] > 0) { // Checks if loan count is greater than 0
    $_SESSION['loan_message'] = "Cannot delete - there are active loans for this offer"; // Sets error message for active loans
    header("Location: lenderDashboard.php#createLoan"); // Redirects to the createLoan section of lenderDashboard.php
    exit(); // Terminates script execution after redirection
}

// Deletes the loan offer from the database
$deleteQuery = "DELETE FROM loan_offers WHERE offer_id = $offer_id"; // SQL query to delete the loan offer by offer_id
if (mysqli_query($myconn, $deleteQuery)) { // Executes the deletion query and checks if successful
    // Logs the deletion activity
    $activity = "Deleted loan offer, offer ID $offer_id"; // Creates activity description with offer ID
    $myconn->query(
        "INSERT INTO activity (user_id, activity, activity_time, activity_type)
        VALUES ({$_SESSION['user_id']}, '$activity', NOW(), 'loan offer deletion')"
    ); // Executes SQL query to log user_id, activity description, current timestamp, and 'loan offer deletion' type
    
    // Updates the average interest rate in the lenders table
    $updateLender = "UPDATE lenders l
                    SET average_interest_rate = (
                        SELECT COALESCE(AVG(interest_rate), 0) 
                        FROM loan_offers 
                        WHERE lender_id = {$_SESSION['lender_id']}
                    )
                    WHERE l.lender_id = {$_SESSION['lender_id']}"; // SQL query to update average interest rate
    if (!mysqli_query($myconn, $updateLender)) { // Executes the update query and checks for failure
        error_log("Failed to update average interest rate: " . mysqli_error($myconn)); // Logs error to server log using error_log()
    }
    
    $_SESSION['loan_message'] = "Loan offer deleted successfully"; // Sets success message in session
} else {
    $_SESSION['loan_message'] = "Error deleting loan: " . mysqli_error($myconn); // Sets error message with database error
}

// Redirects to the createLoan section of the lender dashboard
header("Location: lenderDashboard.php#createLoan"); // Sends HTTP header to redirect
exit(); // Terminates script execution after redirection
?>
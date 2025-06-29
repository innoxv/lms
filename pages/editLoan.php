<?php
// Initiates or resumes a session to manage user state
session_start(); // Starts a new session or resumes an existing one

// Validates that the user is logged in as a lender
if (!isset($_SESSION['lender_id'])) { // isset() checks if lender_id is set in the session
    $_SESSION['loan_message'] = "You must be logged in"; // Sets an error message in the session
    header("Location: lenderDashboard.php"); // Redirects to the lender dashboard
    exit(); // Terminates script execution after redirection
}

// Sanitizes the user ID from the session
$user_id = intval($_SESSION['user_id']); // Converts user_id from session to integer

// Includes the database configuration file to establish the $myconn connection
include '../phpconfig/config.php'; // Imports database connection settings from config.php

// Retrieves and validates the offer ID from the POST request
$offer_id = intval($_POST['offer_id'] ?? 0); // Uses null coalescing operator to default to 0 if not set; converts to integer
$lender_id = intval($_SESSION['lender_id']); // Converts lender_id from session to integer

// Verifies that the loan offer exists and belongs to the lender
$check_query = "SELECT offer_id, loan_type FROM loan_offers 
                WHERE offer_id = $offer_id 
                AND lender_id = $lender_id 
                LIMIT 1"; // SQL query to check if offer_id matches lender_id, limited to 1 row
$check_result = mysqli_query($myconn, $check_query); // mysqli_query() executes the query on the database connection

// Checks if the loan offer exists and the lender has permission
if (!$check_result || mysqli_num_rows($check_result) === 0) { // Checks if query failed or no rows were returned
    $_SESSION['loan_message'] = "Loan offer not found or you don't have permission"; // Sets error message for invalid or unauthorized offer
    header("Location: lenderDashboard.php#createLoan"); // Redirects to the createLoan section of lenderDashboard.php
    exit(); // Terminates script execution after redirection
}

// Fetches the loan offer details
$offer = mysqli_fetch_assoc($check_result); // mysqli_fetch_assoc() fetches a result row as an associative array
$changes = []; // Initializes an empty array to track changes for logging

// Prepares the update by collecting valid fields from the POST request
$updates = []; // Initializes an empty array for SQL update clauses
if (!empty($_POST['interest_rate'])) { // Checks if interest_rate field is not empty
    $rate = floatval($_POST['interest_rate']); // Converts interest_rate to float
    $updates[] = "interest_rate = $rate"; // Adds interest_rate update clause
    $changes[] = "interest rate to $rate%"; // Tracks change for logging
}
if (!empty($_POST['max_amount'])) { // Checks if max_amount field is not empty
    $amount = floatval($_POST['max_amount']); // Converts max_amount to float
    $updates[] = "max_amount = $amount"; // Adds max_amount update clause
    $changes[] = "max amount to $$amount"; // Tracks change for logging
}
if (!empty($_POST['max_duration'])) { // Checks if max_duration field is not empty
    $duration = intval($_POST['max_duration']); // Converts max_duration to integer
    $updates[] = "max_duration = $duration"; // Adds max_duration update clause
    $changes[] = "duration to $duration months"; // Tracks change for logging
}

// Executes the update query if there are changes to apply
if (!empty($updates)) { // Checks if there are any updates to perform
    $update_query = "UPDATE loan_offers SET " . implode(", ", $updates) . 
                   " WHERE offer_id = $offer_id"; // Builds SQL query to update loan_offers table
    if (mysqli_query($myconn, $update_query)) { // Executes the update query and checks if successful
        // Logs the edit activity if changes were made
        if (!empty($changes)) { // Checks if there are changes to log
            $activity = "Edited loan offer, offer ID $offer_id"; // Creates activity description with offer ID
            $myconn->query(
                "INSERT INTO activity (user_id, activity, activity_time, activity_type)
                VALUES ($user_id, '$activity', NOW(), 'loan offer edit')"
            ); // Executes SQL query to log user_id, activity description, current timestamp, and 'loan offer edit' type
        }
        
        // Updates the average interest rate in the lenders table if interest_rate was modified
        if (in_array("interest_rate = $rate", $updates)) { // Checks if interest_rate was updated
            $updateLender = "UPDATE lenders 
                            SET average_interest_rate = (
                                SELECT AVG(interest_rate) 
                                FROM loan_offers 
                                WHERE lender_id = $lender_id
                            )
                            WHERE lender_id = $lender_id"; // SQL query to update average interest rate
            if (!mysqli_query($myconn, $updateLender)) { // Executes the update query and checks for failure
                error_log("Failed to update average interest rate: " . mysqli_error($myconn)); // Logs error to server log using error_log()
            }
        }
        
        $_SESSION['loan_message'] = "Loan updated successfully"; // Sets success message in session
    } else {
        $_SESSION['loan_message'] = "Error updating: " . mysqli_error($myconn); // Sets error message with database error
    }
} else {
    $_SESSION['loan_message'] = "No changes were made"; // Sets message if no updates were provided
}

// Closes the database connection
mysqli_close($myconn); // mysqli_close() terminates the database connection

// Redirects to the createLoan section of the lender dashboard
header("Location: lenderDashboard.php#createLoan"); // Sends HTTP header to redirect
exit(); // Terminates script execution after redirection
?>
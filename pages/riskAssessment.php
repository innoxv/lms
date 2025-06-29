<?php
// Initiates or resumes a session to manage user state
if (session_status() === PHP_SESSION_NONE) { // session_status() checks if a session is active; PHP_SESSION_NONE indicates no active session
    session_start(); // Starts a new session or resumes an existing one
}

// Validates that the user is logged in
if (!isset($_SESSION['user_id'])) { // isset() checks if user_id is set in the session
    $_SESSION['admin_message'] = "Please log in to access risk assessment."; // Sets error message in session
    $_SESSION['admin_message_type'] = 'error'; // Sets message type to error
    header("Location: signin.html"); // Redirects to the sign-in page
    exit(); // Terminates script execution after redirection
}

// Retrieves the user ID from the session
$userId = $_SESSION['user_id']; // Stores user_id from session

// Includes the database configuration file to establish the $myconn connection
include '../phpconfig/config.php'; // Imports database connection settings from config.php

// Defines a function to fetch all submitted loans with customer names
function fetchAllLoans($conn) { // Takes database connection as parameter
    // Builds query to fetch submitted loans
    $query = "SELECT loans.loan_id, loans.customer_id, loans.amount, loans.duration, 
                     loans.collateral_value, loans.collateral_description, loans.collateral_image, 
                     loans.status, loans.application_date, loan_offers.loan_type, customers.name AS customer_name
              FROM loans
              JOIN loan_offers ON loans.offer_id = loan_offers.offer_id
              JOIN customers ON loans.customer_id = customers.customer_id
              WHERE loans.status = 'submitted'
              ORDER BY loans.loan_id DESC"; // Query joins tables and filters for submitted loans
    
    $stmt = $conn->prepare($query); // Prepares the query
    if ($stmt && $stmt->execute()) { // Checks if preparation and execution are successful
        $result = $stmt->get_result(); // Gets the result set
        $loans = []; // Initializes array to store loans
        while ($row = $result->fetch_assoc()) { // Fetches each row as an associative array
            $loans[] = $row; // Adds loan data to array
        }
        $stmt->close(); // Closes the statement
        return $loans; // Returns the fetched loans
    }
    return []; // Returns empty array on failure
}

// Fetches loans initially and stores in session
$_SESSION['pending_loans'] = fetchAllLoans($myconn); // Calls fetchAllLoans() and stores result in session

// Handles approve or reject form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') { // Checks for POST request
    $loanId = intval($_POST['loan_id']); // Converts loan_id to integer
    $officerId = $userId; // Uses user_id as officer ID

    // Verifies that the loan exists and is in submitted status
    $verifyQuery = "SELECT loans.status 
                    FROM loans 
                    WHERE loans.loan_id = ? AND loans.status = 'submitted'"; // Query to check loan status
    $stmt = $myconn->prepare($verifyQuery); // Prepares the query
    $stmt->bind_param("i", $loanId); // Binds loan_id as integer
    
    if ($stmt->execute()) { // Executes the query
        $result = $stmt->get_result(); // Gets the result set
        if ($result->num_rows === 0) { // Checks if loan is invalid or not submitted
            $_SESSION['admin_message'] = "Invalid loan selected or loan is not submitted."; // Sets error message
            $_SESSION['admin_message_type'] = 'error'; // Sets message type to error
            header("Location: adminDashboard.php#loanApplicationReview"); // Redirects to loanApplicationReview section
            exit(); // Terminates script execution after redirection
        }
        $stmt->close(); // Closes the statement

        // Handles approve or reject action
        if (isset($_POST['approve'])) { // Checks if approve action is requested
            $newStatus = 'pending'; // Sets new status to pending
            $activityType = "loan approval"; // Sets activity type
            $activity = "Approved loan ID $loanId."; // Creates activity description
            $_SESSION['admin_message'] = "Loan approved successfully."; // Sets success message
            $_SESSION['admin_message_type'] = 'success'; // Sets message type to success
        } elseif (isset($_POST['reject'])) { // Checks if reject action is requested
            $newStatus = 'rejected'; // Sets new status to rejected
            $activityType = "loan rejection"; // Sets activity type
            $activity = "Rejected loan ID $loanId."; // Creates activity description
            $_SESSION['admin_message'] = "Loan rejected successfully."; // Sets success message
            $_SESSION['admin_message_type'] = 'success'; // Sets message type to success
        } else {
            $_SESSION['admin_message'] = "Invalid action."; // Sets error message for invalid action
            $_SESSION['admin_message_type'] = 'error'; // Sets message type to error
            header("Location: adminDashboard.php#loanApplicationReview"); // Redirects to loanApplicationReview section
            exit(); // Terminates script execution after redirection
        }

        // Updates loan status
        $updateStmt = $myconn->prepare("UPDATE loans SET status = ? WHERE loan_id = ?"); // Prepares update query
        $updateStmt->bind_param("si", $newStatus, $loanId); // Binds new status and loan_id
        $updateStmt->execute(); // Executes the query
        $updateStmt->close(); // Closes the statement

        // Logs the activity
        $activityStmt = $myconn->prepare("INSERT INTO activity (user_id, activity, activity_type, activity_time) 
                                        VALUES (?, ?, ?, NOW())"); // Prepares activity log query
        $activityStmt->bind_param("iss", $officerId, $activity, $activityType); // Binds officer_id, activity, and type
        $activityStmt->execute(); // Executes the query
        $activityStmt->close(); // Closes the statement

        // Refreshes pending loans
        $_SESSION['pending_loans'] = fetchAllLoans($myconn); // Refreshes loan data in session
    } else {
        $_SESSION['admin_message'] = "Database error during verification."; // Sets error message
        $_SESSION['admin_message_type'] = 'error'; // Sets message type to error
    }
    
    header("Location: adminDashboard.php#riskAssessment"); // Redirects to riskAssessment section
    exit(); // Terminates script execution after redirection
}
?>
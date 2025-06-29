<?php
// Initiates or resumes a session to manage user state
session_start(); // Starts a new session or resumes an existing one

// Includes the database configuration file to establish the $myconn connection
include '../phpconfig/config.php'; // Imports database connection settings from config.php

// Validates that the user is logged in and has a lender ID
if (!isset($_SESSION['user_id']) || !isset($_SESSION['lender_id'])) { // isset() checks if user_id and lender_id are set in the session
    $_SESSION['loan_message'] = "Please log in to disburse loans."; // Sets an error message in the session
    $_SESSION['message_type'] = 'error'; // Sets the message type to error
    header("Location: signin.html"); // Redirects to the sign-in page
    exit(); // Terminates script execution after redirection
}

// Validates that the request is a POST request and includes a loan ID
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['loan_id'])) { // Checks if REQUEST_METHOD is not POST or loan_id is not set
    $_SESSION['loan_message'] = "Invalid request."; // Sets an error message for invalid request
    $_SESSION['message_type'] = 'error'; // Sets the message type to error
    header("Location: lenderDashboard.php#loanRequests"); // Redirects to the loanRequests section of lenderDashboard.php
    exit(); // Terminates script execution after redirection
}

// Sanitizes input data to ensure correct types
$loanId = (int)$_POST['loan_id']; // Converts loan_id to integer
$lenderId = (int)$_SESSION['lender_id']; // Converts lender_id from session to integer
$userId = (int)$_SESSION['user_id']; // Converts user_id from session to integer

// Begins a database transaction to ensure atomicity
$myconn->begin_transaction(); // begin_transaction() starts a transaction for consistent database operations

// Verifies that the loan exists, belongs to the lender, and is in pending status
$verifyQuery = "SELECT customer_id, amount, interest_rate, duration 
                FROM loans 
                WHERE loan_id = ? AND lender_id = ? AND status = 'pending'"; // SQL query with placeholders for prepared statement
$stmt = $myconn->prepare($verifyQuery); // prepare() creates a prepared statement for secure execution
if (!$stmt) { // Checks if statement preparation failed
    $myconn->rollback(); // rollback() cancels the transaction
    $_SESSION['loan_message'] = "Query preparation failed: " . $myconn->error; // Sets error message with database error
    $_SESSION['message_type'] = 'error'; // Sets the message type to error
    error_log("Verify query preparation failed for Loan ID $loanId: " . $myconn->error); // Logs error to server log using error_log()
    $myconn->close(); // Closes the database connection
    header("Location: lenderDashboard.php#loanRequests"); // Redirects to the loanRequests section
    exit(); // Terminates script execution after redirection
}

// Binds parameters to the verification query
$stmt->bind_param("ii", $loanId, $lenderId); // bind_param() binds loanId and lenderId as integers
$stmt->execute(); // Executes the prepared statement
$result = $stmt->get_result(); // Gets the result set

// Checks if the loan exists and is eligible for approval
if ($result->num_rows === 0) { // num_rows returns the number of rows in the result set
    $myconn->rollback(); // Cancels the transaction
    $_SESSION['loan_message'] = "Loan not found or not eligible for approval."; // Sets error message for invalid or ineligible loan
    $_SESSION['message_type'] = 'error'; // Sets the message type to error
    $myconn->close(); // Closes the database connection
    header("Location: lenderDashboard.php#loanRequests"); // Redirects to the loanRequests section
    exit(); // Terminates script execution after redirection
}

// Fetches loan data for further processing
$loanData = $result->fetch_assoc(); // fetch_assoc() fetches the result row as an associative array
$customerId = $loanData['customer_id']; // Stores the customer ID
$principal = floatval($loanData['amount']); // Converts amount to float
$interestRate = floatval($loanData['interest_rate']) / 100; // Converts interest rate percentage to decimal
$durationMonths = floatval($loanData['duration']); // Converts duration to float (months)
$durationYears = $durationMonths / 12; // Converts duration from months to years

// Validates loan data to ensure positive values
if ($principal <= 0 || $durationMonths <= 0) { // Checks if principal or duration is non-positive
    $myconn->rollback(); // Cancels the transaction
    $_SESSION['loan_message'] = "Invalid loan: Amount and duration must be positive."; // Sets error message for invalid data
    $_SESSION['message_type'] = 'error'; // Sets the message type to error
    error_log("Invalid loan data for Loan ID $loanId: principal=$principal, duration=$durationMonths"); // Logs error to server log
    $myconn->close(); // Closes the database connection
    header("Location: lenderDashboard.php#loanRequests"); // Redirects to the loanRequests section
    exit(); // Terminates script execution after redirection
}

// Calculates total amount due using simple interest
$totalAmountDue = $principal; // Initializes total amount due as principal
if ($interestRate >= 0) { // Checks if interest rate is non-negative
    $totalAmountDue = $principal + ($principal * $interestRate * $durationYears); // Calculates total with simple interest
} else {
    error_log("Invalid interest rate for Loan ID $loanId: interest_rate=$interestRate"); // Logs warning for invalid interest rate
}

// Updates loan status to 'disbursed'
$updateQuery = "UPDATE loans SET status = 'disbursed' 
                WHERE loan_id = ? AND lender_id = ? AND status = 'pending'"; // SQL query to update loan status
$stmt = $myconn->prepare($updateQuery); // Prepares the update query
if (!$stmt) { // Checks if statement preparation failed
    $myconn->rollback(); // Cancels the transaction
    $_SESSION['loan_message'] = "Update query preparation failed: " . $myconn->error; // Sets error message with database error
    $_SESSION['message_type'] = 'error'; // Sets the message type to error
    error_log("Update query preparation failed for Loan ID $loanId: " . $myconn->error); // Logs error to server log
    $myconn->close(); // Closes the database connection
    header("Location: lenderDashboard.php#loanRequests"); // Redirects to the loanRequests section
    exit(); // Terminates script execution after redirection
}

// Binds parameters to the update query
$stmt->bind_param("ii", $loanId, $lenderId); // Binds loanId and lenderId as integers
if (!$stmt->execute()) { // Executes the statement and checks for failure
    $myconn->rollback(); // Cancels the transaction
    $_SESSION['loan_message'] = "Failed to update loan status: " . $myconn->error; // Sets error message with database error
    $_SESSION['message_type'] = 'error'; // Sets the message type to error
    error_log("Failed to update loan status for Loan ID $loanId: " . $myconn->error); // Logs error to server log
    $myconn->close(); // Closes the database connection
    header("Location: lenderDashboard.php#loanRequests"); // Redirects to the loanRequests section
    exit(); // Terminates script execution after redirection
}

// Inserts an initial payment record into the payments table
$paymentQuery = "INSERT INTO payments (
    loan_id, customer_id, lender_id, amount,
    payment_method, payment_type, remaining_balance
) VALUES (?, ?, ?, ?, ?, ?, ?)"; // SQL query to insert payment record
$stmt = $myconn->prepare($paymentQuery); // Prepares the payment query
if (!$stmt) { // Checks if statement preparation failed
    $myconn->rollback(); // Cancels the transaction
    $_SESSION['loan_message'] = "Payment query preparation failed: " . $myconn->error; // Sets error message with database error
    $_SESSION['message_type'] = 'error'; // Sets the message type to error
    error_log("Payment query preparation failed for Loan ID $loanId: " . $myconn->error); // Logs error to server log
    $myconn->close(); // Closes the database connection
    header("Location: lenderDashboard.php#loanRequests"); // Redirects to the loanRequests section
    exit(); // Terminates script execution after redirection
}

// Defines initial payment data
$initialAmount = 0.00; // Sets initial payment amount to 0 (no payment made yet)
$paymentMethod = 'none'; // Placeholder for payment method
$paymentType = 'unpaid'; // Sets payment type to unpaid
$remainingBalance = $totalAmountDue; // Sets remaining balance to total amount due

// Binds parameters to the payment query
$stmt->bind_param(
    "iiiddsd",
    $loanId,
    $customerId,
    $lenderId,
    $initialAmount,
    $paymentMethod,
    $paymentType,
    $remainingBalance
); // Binds parameters with types: integer (i), double (d), string (s)

// Executes the payment query and checks for failure
if (!$stmt->execute()) { // Checks if the statement execution failed
    $myconn->rollback(); // Cancels the transaction
    $_SESSION['loan_message'] = "Failed to initialize payment record: " . $myconn->error; // Sets error message with database error
    $_SESSION['message_type'] = 'error'; // Sets the message type to error
    error_log("Failed to initialize payment record for Loan ID $loanId: " . $myconn->error); // Logs error to server log
    $myconn->close(); // Closes the database connection
    header("Location: lenderDashboard.php#loanRequests"); // Redirects to the loanRequests section
    exit(); // Terminates script execution after redirection
}

// Logs the loan disbursal activity
$activity = "Disbursed loan ID $loanId"; // Creates activity description with loan ID
$logQuery = "INSERT INTO activity (user_id, activity, activity_time, activity_type)
             VALUES (?, ?, NOW(), 'loan disbursal')"; // SQL query to log activity
$stmt = $myconn->prepare($logQuery); // Prepares the activity log query
if (!$stmt) { // Checks if statement preparation failed
    $myconn->rollback(); // Cancels the transaction
    $_SESSION['loan_message'] = "Activity log query preparation failed: " . $myconn->error; // Sets error message with database error
    $_SESSION['message_type'] = 'error'; // Sets the message type to error
    error_log("Activity log query preparation failed for Loan ID $loanId: " . $myconn->error); // Logs error to server log
    $myconn->close(); // Closes the database connection
    header("Location: lenderDashboard.php#loanRequests"); // Redirects to the loanRequests section
    exit(); // Terminates script execution after redirection
}

// Binds parameters to the activity log query
$stmt->bind_param("is", $userId, $activity); // Binds userId as integer and activity as string
if (!$stmt->execute()) { // Checks if the statement execution failed
    $myconn->rollback(); // Cancels the transaction
    $_SESSION['loan_message'] = "Failed to log activity: " . $myconn->error; // Sets error message with database error
    $_SESSION['message_type'] = 'error'; // Sets the message type to error
    error_log("Failed to log activity for Loan ID $loanId: " . $myconn->error); // Logs error to server log
    $myconn->close(); // Closes the database connection
    header("Location: lenderDashboard.php#loanRequests"); // Redirects to the loanRequests section
    exit(); // Terminates script execution after redirection
}

// Commits the transaction to save all changes
$myconn->commit(); // commit() finalizes the transaction

// Sets success message
$_SESSION['loan_message'] = "Loan ID $loanId has been disbursed!"; // Sets success message with loan ID
$_SESSION['message_type'] = 'success'; // Sets the message type to success

// Closes the database connection
$myconn->close(); // Closes the database connection

// Redirects to the loanRequests section of the lender dashboard
header("Location: lenderDashboard.php#loanRequests"); // Sends HTTP header to redirect
exit(); // Terminates script execution after redirection
?>
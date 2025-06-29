<?php
// Initiates or resumes a session to manage user state
session_start(); // Starts a new session or resumes an existing one

// Validates that the user is logged in
if (!isset($_SESSION['user_id'])) { // isset() checks if user_id is set in the session
    $_SESSION['payment_message'] = "Please log in to access payment tracking."; // Sets error message in session
    $_SESSION['payment_message_type'] = 'error'; // Sets message type to error
    header("Location: signin.html"); // Redirects to the sign-in page
    exit(); // Terminates script execution after redirection
}

// Retrieves user and customer IDs from session
$userId = $_SESSION['user_id']; // Stores user_id from session
$customerId = $_SESSION['customer_id'] ?? null; // Gets customer_id, defaults to null if not set

// Checks if customer profile exists
if (!$customerId) { // Checks if customer_id is null
    $_SESSION['payment_message'] = "Customer profile not found. Please log in again."; // Sets error message in session
    $_SESSION['payment_message_type'] = 'error'; // Sets message type to error
    header("Location: customerDashboard.php#paymentTracking"); // Redirects to paymentTracking section
    exit(); // Terminates script execution after redirection
}

// Includes the database configuration file to establish the $myconn connection
include '../phpconfig/config.php'; // Imports database connection settings from config.php

// Includes the fetchActiveLoans.php file to use the fetchActiveLoans function
require_once 'fetchActiveLoans.php'; // Imports function to fetch active loans

// Updates overdue loans to set isDue flag
$overdueQuery = "
    UPDATE loans
    SET isDue = 1
    WHERE customer_id = ?
    AND status = 'disbursed'
    AND due_date IS NOT NULL
    AND due_date < CURDATE()
    AND EXISTS (
        SELECT 1 FROM payments
        WHERE payments.loan_id = loans.loan_id
        AND payments.remaining_balance > 0
    )"; // Query to mark loans as overdue based on due date and remaining balance
$stmt = $myconn->prepare($overdueQuery); // Prepares the query
$stmt->bind_param("i", $customerId); // Binds customer_id as integer
$stmt->execute(); // Executes the query to update overdue loans

// Handles filters from GET request
$filters = [
    'payment_status' => $_GET['payment_status'] ?? '', // Gets payment_status filter, defaults to empty
    'loan_type' => $_GET['loan_type'] ?? '', // Gets loan_type filter, defaults to empty
    'amount_range' => $_GET['amount_range'] ?? '', // Gets amount_range filter, defaults to empty
    'date_range' => $_GET['date_range'] ?? '', // Gets date_range filter, defaults to empty
    'due_status' => $_GET['due_status'] ?? '' // Gets due_status filter, defaults to empty
];

// Resets filters if requested
if (isset($_GET['reset']) && $_GET['reset'] === 'true') { // Checks if reset flag is set to true
    unset($_SESSION['active_loans']); // Removes active_loans from session
    unset($_SESSION['payment_filters']); // Removes payment_filters from session
    $filters = [
        'payment_status' => '', // Resets payment_status filter
        'loan_type' => '', // Resets loan_type filter
        'amount_range' => '', // Resets amount_range filter
        'date_range' => '', // Resets date_range filter
        'due_status' => '' // Resets due_status filter
    ];
}

// Fetches active loans and stores them in session
$_SESSION['active_loans'] = fetchActiveLoans($myconn, $customerId, $filters); // Calls fetchActiveLoans() with filters
$_SESSION['payment_filters'] = $filters; // Stores applied filters in session

// Handles payment submission via POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_submit'])) { // Checks for POST request and payment_submit
    $loanId = intval($_POST['loan_id']); // Converts loan_id to integer
    $amount = floatval($_POST['amount']); // Converts payment amount to float
    $paymentMethod = $myconn->real_escape_string($_POST['payment_method']); // Escapes payment method for SQL safety

    // Verifies that the loan exists and belongs to the customer
    $verifyQuery = "SELECT 
        loans.customer_id, 
        loans.lender_id,
        loans.amount, 
        loans.interest_rate, 
        loans.duration,
        loans.installments,
        loans.due_date,
        loans.application_date
    FROM loans 
    WHERE loans.loan_id = ? AND loans.status = 'disbursed'"; // Query to verify loan details
    $stmt = $myconn->prepare($verifyQuery); // Prepares the query
    $stmt->bind_param("i", $loanId); // Binds loan_id as integer
    $stmt->execute(); // Executes the query
    $result = $stmt->get_result(); // Gets the result set

    // Checks if loan is valid and belongs to the customer
    if ($result->num_rows === 0 || $result->fetch_assoc()['customer_id'] != $customerId) { // Verifies loan and ownership
        $_SESSION['payment_message'] = "Invalid or undisbursed loan selected for payment"; // Sets error message
        $_SESSION['payment_message_type'] = 'error'; // Sets message type to error
    } else {
        $result->data_seek(0); // Resets result pointer to start
        $loanDetails = $result->fetch_assoc(); // Fetches loan details

        // Extracts loan details for calculations
        $lenderId = $loanDetails['lender_id']; // Stores lender_id
        $principal = $loanDetails['amount']; // Stores loan principal
        $interestRate = $loanDetails['interest_rate'] / 100; // Converts interest rate to decimal
        $durationYears = $loanDetails['duration'] / 12; // Converts duration to years
        $expectedInstallment = $loanDetails['installments']; // Stores expected installment amount
        $currentDueDate = $loanDetails['due_date']; // Stores current due date
        $applicationDate = $loanDetails['application_date']; // Stores application date

        // Calculates total amount due with simple interest
        $totalAmountDue = $principal + ($principal * $interestRate * $durationYears); // Computes total with interest

        // Fetches sum of previous payments
        $paymentSumQuery = "SELECT COALESCE(SUM(amount), 0) AS total_paid 
                           FROM payments 
                           WHERE loan_id = ?"; // Query to sum payments for the loan
        $stmt = $myconn->prepare($paymentSumQuery); // Prepares the query
        $stmt->bind_param("i", $loanId); // Binds loan_id as integer
        $stmt->execute(); // Executes the query
        $totalPaid = $stmt->get_result()->fetch_assoc()['total_paid']; // Gets total paid amount

        // Calculates current remaining balance
        $currentRemainingBalance = $totalAmountDue - $totalPaid; // Computes remaining balance

        // Validates payment amount
        if ($amount <= 0 || $amount > $currentRemainingBalance + 0.01) { // Checks if amount is valid
            $_SESSION['payment_message'] = "Invalid payment amount. Must be greater than 0 and not exceed remaining balance."; // Sets error message
            $_SESSION['payment_message_type'] = 'error'; // Sets message type to error
        } else {
            // Calculates new remaining balance
            $newRemainingBalance = $currentRemainingBalance - $amount; // Subtracts payment amount

            // Determines payment type
            $paymentType = ($newRemainingBalance <= 0) ? 'full' : 'partial'; // Sets full or partial based on balance

            // Fetches total amount paid for the current installment period
            $lastPaymentQuery = "SELECT COALESCE(SUM(amount), 0) AS installment_paid 
                               FROM payments 
                               WHERE loan_id = ? 
                               AND payment_date <= NOW()
                               AND installment_balance IS NOT NULL
                               ORDER BY payment_date DESC 
                               LIMIT 1"; // Query to get latest installment payment
            $stmt = $myconn->prepare($lastPaymentQuery); // Prepares the query
            $stmt->bind_param("i", $loanId); // Binds loan_id as integer
            $stmt->execute(); // Executes the query
            $lastInstallmentPaid = $stmt->get_result()->fetch_assoc()['installment_paid']; // Gets last installment paid
            $totalInstallmentPaid = $lastInstallmentPaid + $amount; // Adds new payment to total

            // Calculates installment balance
            $installmentBalance = ($totalInstallmentPaid >= $expectedInstallment) ? NULL : max(0, $expectedInstallment - $totalInstallmentPaid); // Computes new installment balance

            // Inserts new payment record
            $insertQuery = "INSERT INTO payments (
                loan_id, customer_id, lender_id, amount, 
                payment_method, payment_type, remaining_balance, installment_balance
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"; // Query to insert payment
            $stmt = $myconn->prepare($insertQuery); // Prepares the query
            $stmt->bind_param(
                "iiidssdd",
                $loanId,
                $customerId,
                $lenderId,
                $amount,
                $paymentMethod,
                $paymentType,
                $newRemainingBalance,
                $installmentBalance
            ); // Binds parameters with appropriate types

            // Executes payment insertion and handles success or failure
            if ($stmt->execute()) { // Checks if payment insertion was successful
                // Updates due_date and isDue if installment is fully paid
                if ($totalInstallmentPaid >= $expectedInstallment) { // Checks if installment is paid
                    $appDay = date('d', strtotime($applicationDate)); // Gets day of application
                    $appMonth = date('m', strtotime($currentDueDate)); // Gets current due month
                    $appYear = date('Y', strtotime($currentDueDate)); // Gets current due year
                    $nextMonth = date('Y-m-d', strtotime("+1 month", strtotime("$appYear-$appMonth-$appDay"))); // Calculates next due date
                    $updateLoanQuery = "UPDATE loans 
                                        SET due_date = ?, isDue = 0
                                        WHERE loan_id = ? AND status = 'disbursed'"; // Query to update loan
                    $stmt = $myconn->prepare($updateLoanQuery); // Prepares the query
                    $stmt->bind_param("si", $nextMonth, $loanId); // Binds next due date and loan_id
                    $stmt->execute(); // Executes the query
                } else {
                    // Checks if due date is reached for partial payment
                    $isDue = (strtotime($currentDueDate) <= time()) ? 1 : 0; // Sets isDue based on current time
                    $updateLoanQuery = "UPDATE loans 
                                        SET isDue = ?
                                        WHERE loan_id = ? AND status = 'disbursed'"; // Query to update isDue
                    $stmt = $myconn->prepare($updateLoanQuery); // Prepares the query
                    $stmt->bind_param("ii", $isDue, $loanId); // Binds isDue and loan_id
                    $stmt->execute(); // Executes the query
                }

                // Sets success message
                $_SESSION['payment_message'] = "Payment of KES " . number_format($amount, 2) . " processed successfully!"; // Formats success message
                $_SESSION['payment_message_type'] = 'success'; // Sets message type to success

                // Logs payment activity
                $activity = "Processed payment of KES $amount for loan ID $loanId"; // Creates activity description
                $activityQuery = "INSERT INTO activity (user_id, activity, activity_time, activity_type) 
                                 VALUES (?, ?, NOW(), 'payment')"; // Query to log activity
                $stmt = $myconn->prepare($activityQuery); // Prepares the query
                $stmt->bind_param("is", $userId, $activity); // Binds user_id and activity
                $stmt->execute(); // Executes the query

                // Refreshes active loans
                $_SESSION['active_loans'] = fetchActiveLoans($myconn, $customerId, $filters); // Refreshes loan data
            } else {
                error_log("Payment insert error: " . $stmt->error); // Logs error to server log
                $_SESSION['payment_message'] = "Error processing payment: " . $stmt->error; // Sets error message
                $_SESSION['payment_message_type'] = 'error'; // Sets message type to error
            }
        }
    }
}

// Closes the database connection
mysqli_close($myconn); // Terminates the database connection
header("Location: customerDashboard.php#paymentTracking"); // Redirects to paymentTracking section
exit(); // Terminates script execution after redirection
?>
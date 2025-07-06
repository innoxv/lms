<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
    $amount = round(floatval($_POST['amount']), 2); // Converts payment amount to float and rounds to 2 decimal places
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
        loans.application_date,
        loans.status
    FROM loans 
    WHERE loans.loan_id = ? AND (loans.status = 'disbursed' OR loans.status = 'active')";
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

        // Calculates total amount due with simple interest and rounds to 2 decimal places
        $totalAmountDue = round($principal + ($principal * $interestRate * $durationYears), 2); // Computes total with interest

        // Fetches sum of previous payments and rounds to 2 decimal places
        $paymentSumQuery = "SELECT COALESCE(SUM(amount), 0) AS total_paid 
                           FROM payments 
                           WHERE loan_id = ?";
        $stmt = $myconn->prepare($paymentSumQuery); // Prepares the query
        $stmt->bind_param("i", $loanId); // Binds loan_id as integer
        $stmt->execute(); // Executes the query
        $totalPaid = round($stmt->get_result()->fetch_assoc()['total_paid'], 2); // Gets and rounds total paid amount

        // Calculates current remaining balance with rounding
        $currentRemainingBalance = round($totalAmountDue - $totalPaid, 2); // Computes remaining balance

        // Validates payment amount with floating-point tolerance
        if ($amount <= 0) {
            $_SESSION['payment_message'] = "Payment amount must be greater than 0."; // Sets error message
            $_SESSION['payment_message_type'] = 'error'; // Sets message type to error
        } elseif (abs($amount - $currentRemainingBalance) < 0.02) { // Checks if amount is within 0.02 tolerance
            $amount = $currentRemainingBalance; // Auto-adjusts to exact remaining balance
            $newRemainingBalance = 0.00; // Sets balance to zero
            $paymentType = 'full'; // Marks as full payment
        } elseif ($amount > $currentRemainingBalance) { // Checks if amount exceeds balance
            $_SESSION['payment_message'] = "Payment amount cannot exceed remaining balance of KES " . number_format($currentRemainingBalance, 2); // Sets error message
            $_SESSION['payment_message_type'] = 'error'; // Sets message type to error
        } else {
            // Calculates new remaining balance with rounding
            $newRemainingBalance = round($currentRemainingBalance - $amount, 2); // Subtracts payment amount
            $paymentType = ($newRemainingBalance <= 0.009) ? 'full' : 'partial'; // Sets full or partial based on balance
        }

        // Only proceed if no validation errors
        if (!isset($_SESSION['payment_message_type']) || $_SESSION['payment_message_type'] !== 'error') {
            // Gets the current installment period's payment total and remaining balance
            $installmentQuery = "SELECT 
                COALESCE(SUM(amount), 0) AS current_installment_paid,
                COALESCE(MIN(installment_balance), ?) AS current_installment_balance
                FROM payments 
                WHERE loan_id = ? 
                AND payment_date BETWEEN 
                    (SELECT due_date FROM loans WHERE loan_id = ?) 
                    AND NOW()"; // Tracks payments since last due date
            
            $stmt = $myconn->prepare($installmentQuery);
            $stmt->bind_param("dii", $expectedInstallment, $loanId, $loanId); // Binds expected installment as default
            $stmt->execute();
            $installmentData = $stmt->get_result()->fetch_assoc();
            
            $currentInstallmentPaid = round($installmentData['current_installment_paid'], 2);
            $currentInstallmentBalance = round($installmentData['current_installment_balance'], 2);
            
            // Calculate new totals including current payment
            $totalInstallmentPaid = round($currentInstallmentPaid + $amount, 2);
            $newInstallmentBalance = round($currentInstallmentBalance - $amount, 2);
            
            // Determine if we're completing an installment period
            $isInstallmentComplete = ($totalInstallmentPaid >= $expectedInstallment);
            $isLoanComplete = ($newRemainingBalance <= 0.009);
            
            // Calculate carryover amount if payment exceeds current installment
            $carryoverAmount = 0;
            if ($isInstallmentComplete && !$isLoanComplete) {
                $carryoverAmount = round($totalInstallmentPaid - $expectedInstallment, 2);
            }
            
            // Determine the installment balance for this payment (original logic)
            if ($isLoanComplete) {
                $installmentBalance = NULL; // No balance when loan is fully paid
            } elseif ($isInstallmentComplete) {
                // If completing installment, balance shows carryover to next period
                $installmentBalance = round($expectedInstallment - $carryoverAmount, 2);
                
                // Ensure installment balance doesn't exceed remaining loan balance
                if ($newRemainingBalance < $installmentBalance) {
                    $installmentBalance = $newRemainingBalance;
                }
            } else {
                // Normal partial payment - check if remaining balance is less than calculated installment balance
                $installmentBalance = max(0, $newInstallmentBalance);
                
                // Ensure installment balance doesn't exceed remaining loan balance
                if ($newRemainingBalance < $installmentBalance) {
                    $installmentBalance = $newRemainingBalance;
                }
            }

            // Modified validation: Allow payment up to remaining balance if it exceeds installment balance
            if ($amount > $currentInstallmentBalance && $currentInstallmentBalance > 0 && $amount > $currentRemainingBalance) {
                $_SESSION['payment_message'] = "Payment amount cannot exceed remaining balance of KES " . number_format($currentRemainingBalance, 2);
                $_SESSION['payment_message_type'] = 'error';
                header("Location: customerDashboard.php#paymentTracking");
                exit();
            }

            // Inserts new payment record
            $insertQuery = "INSERT INTO payments (
                loan_id, customer_id, lender_id, amount, 
                payment_method, payment_type, remaining_balance, installment_balance
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $myconn->prepare($insertQuery);
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
            );

            if ($stmt->execute()) {
                // Handle due date and installment tracking updates
                if ($isLoanComplete) {
                    // Full loan payoff
                    $updateLoanQuery = "UPDATE loans 
                                      SET due_date = NULL, isDue = 0
                                      WHERE loan_id = ?";
                    $stmt = $myconn->prepare($updateLoanQuery);
                    $stmt->bind_param("i", $loanId);
                    $stmt->execute();
                } elseif ($isInstallmentComplete) {
                    // Installment completed - move to next period
                    $appDay = date('d', strtotime($applicationDate));
                    $appMonth = date('m', strtotime($currentDueDate));
                    $appYear = date('Y', strtotime($currentDueDate));
                    $nextMonth = date('Y-m-d', strtotime("+1 month", strtotime("$appYear-$appMonth-$appDay")));
                    
                    $updateLoanQuery = "UPDATE loans 
                                      SET due_date = ?, isDue = ?
                                      WHERE loan_id = ?";
                    $stmt = $myconn->prepare($updateLoanQuery);
                    $isDue = ($carryoverAmount > 0) ? 0 : 1; // Only mark as due if no carryover
                    $stmt->bind_param("sii", $nextMonth, $isDue, $loanId);
                    $stmt->execute();
                    
                    // If there's a carryover, create a new payment record for the next period
                    if ($carryoverAmount > 0) {
                        $carryoverQuery = "INSERT INTO payments (
                            loan_id, customer_id, lender_id, amount, 
                            payment_method, payment_type, remaining_balance, installment_balance
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                        $stmt = $myconn->prepare($carryoverQuery);
                        $stmt->bind_param(
                            "iiidssdd",
                            $loanId,
                            $customerId,
                            $lenderId,
                            $carryoverAmount,
                            $paymentMethod,
                            $paymentType,
                            $newRemainingBalance,
                            $expectedInstallment
                        );
                        $stmt->execute();
                    }
                } else {
                    // Partial payment handling
                    $isDue = (strtotime($currentDueDate) <= time()) ? 1 : 0;
                    $updateLoanQuery = "UPDATE loans 
                                      SET isDue = ?
                                      WHERE loan_id = ?";
                    $stmt = $myconn->prepare($updateLoanQuery);
                    $stmt->bind_param("ii", $isDue, $loanId);
                    $stmt->execute();
                }

                // Sets success message with formatted amount
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
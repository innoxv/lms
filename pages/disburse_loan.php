<?php
session_start();

// Database config file
include '../phpconfig/config.php';

// Check if lender is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['lender_id'])) {
    $_SESSION['loan_message'] = "Please log in to disburse loans.";
    $_SESSION['message_type'] = 'error';
    header("Location: signin.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['loan_id'])) {
    $_SESSION['loan_message'] = "Invalid request.";
    $_SESSION['message_type'] = 'error';
    header("Location: lenderDashboard.php#loanRequests");
    exit();
}

$loanId = (int)$_POST['loan_id'];
$lenderId = (int)$_SESSION['lender_id'];
$userId = (int)$_SESSION['user_id'];

// Begin transaction for atomicity
$myconn->begin_transaction();

// Verify loan exists and belongs to lender
$verifyQuery = "SELECT customer_id, amount, interest_rate, duration 
                FROM loans 
                WHERE loan_id = ? AND lender_id = ? AND status = 'pending'";
$stmt = $myconn->prepare($verifyQuery);
if (!$stmt) {
    $myconn->rollback();
    $_SESSION['loan_message'] = "Query preparation failed: " . $myconn->error;
    $_SESSION['message_type'] = 'error';
    error_log("Verify query preparation failed for Loan ID $loanId: " . $myconn->error);
    $myconn->close();
    header("Location: lenderDashboard.php#loanRequests");
    exit();
}

$stmt->bind_param("ii", $loanId, $lenderId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $myconn->rollback();
    $_SESSION['loan_message'] = "Loan not found or not eligible for approval.";
    $_SESSION['message_type'] = 'error';
    $myconn->close();
    header("Location: lenderDashboard.php#loanRequests");
    exit();
}

$loanData = $result->fetch_assoc();
$customerId = $loanData['customer_id'];
$principal = floatval($loanData['amount']);
$interestRate = floatval($loanData['interest_rate']) / 100; // Convert percentage to decimal
$durationMonths = floatval($loanData['duration']); // Duration in months
$durationYears = $durationMonths / 12; // Convert months to years

// Validate loan data
if ($principal <= 0 || $durationMonths <= 0) {
    $myconn->rollback();
    $_SESSION['loan_message'] = "Invalid loan: Amount and duration must be positive.";
    $_SESSION['message_type'] = 'error';
    error_log("Invalid loan data for Loan ID $loanId: principal=$principal, duration=$durationMonths");
    $myconn->close();
    header("Location: lenderDashboard.php#loanRequests");
    exit();
}

// Calculate total amount due with simple interest
$totalAmountDue = $principal;
if ($interestRate >= 0) {
    $totalAmountDue = $principal + ($principal * $interestRate * $durationYears);
} else {
    error_log("Invalid interest rate for Loan ID $loanId: interest_rate=$interestRate");
}

// Update loan status to disbursed
$updateQuery = "UPDATE loans SET status = 'disbursed' 
                WHERE loan_id = ? AND lender_id = ? AND status = 'pending'";
$stmt = $myconn->prepare($updateQuery);
if (!$stmt) {
    $myconn->rollback();
    $_SESSION['loan_message'] = "Update query preparation failed: " . $myconn->error;
    $_SESSION['message_type'] = 'error';
    error_log("Update query preparation failed for Loan ID $loanId: " . $myconn->error);
    $myconn->close();
    header("Location: lenderDashboard.php#loanRequests");
    exit();
}

$stmt->bind_param("ii", $loanId, $lenderId);
if (!$stmt->execute()) {
    $myconn->rollback();
    $_SESSION['loan_message'] = "Failed to update loan status: " . $myconn->error;
    $_SESSION['message_type'] = 'error';
    error_log("Failed to update loan status for Loan ID $loanId: " . $myconn->error);
    $myconn->close();
    header("Location: lenderDashboard.php#loanRequests");
    exit();
}

// Insert initial payment record into payments table
$paymentQuery = "INSERT INTO payments (
    loan_id, customer_id, lender_id, amount,
    payment_method, payment_type, remaining_balance
) VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt = $myconn->prepare($paymentQuery);
if (!$stmt) {
    $myconn->rollback();
    $_SESSION['loan_message'] = "Payment query preparation failed: " . $myconn->error;
    $_SESSION['message_type'] = 'error';
    error_log("Payment query preparation failed for Loan ID $loanId: " . $myconn->error);
    $myconn->close();
    header("Location: lenderDashboard.php#loanRequests");
    exit();
}

$initialAmount = 0.00; // No payment made yet
$paymentMethod = 'none'; // Placeholder
$paymentType = 'unpaid';
$remainingBalance = $totalAmountDue;

$stmt->bind_param(
    "iiiddsd",
    $loanId,
    $customerId,
    $lenderId,
    $initialAmount,
    $paymentMethod,
    $paymentType,
    $remainingBalance
);

if (!$stmt->execute()) {
    $myconn->rollback();
    $_SESSION['loan_message'] = "Failed to initialize payment record: " . $myconn->error;
    $_SESSION['message_type'] = 'error';
    error_log("Failed to initialize payment record for Loan ID $loanId: " . $myconn->error);
    $myconn->close();
    header("Location: lenderDashboard.php#loanRequests");
    exit();
}

// Log loan approval activity
$activity = "Disbursed loan application #$loanId";
$logQuery = "INSERT INTO activity (user_id, activity, activity_time, activity_type)
             VALUES (?, ?, NOW(), 'loan approval')";
$stmt = $myconn->prepare($logQuery);
if (!$stmt) {
    $myconn->rollback();
    $_SESSION['loan_message'] = "Activity log query preparation failed: " . $myconn->error;
    $_SESSION['message_type'] = 'error';
    error_log("Activity log query preparation failed for Loan ID $loanId: " . $myconn->error);
    $myconn->close();
    header("Location: lenderDashboard.php#loanRequests");
    exit();
}

$stmt->bind_param("is", $userId, $activity);
if (!$stmt->execute()) {
    $myconn->rollback();
    $_SESSION['loan_message'] = "Failed to log activity: " . $myconn->error;
    $_SESSION['message_type'] = 'error';
    error_log("Failed to log activity for Loan ID $loanId: " . $myconn->error);
    $myconn->close();
    header("Location: lenderDashboard.php#loanRequests");
    exit();
}

// Commit transaction
$myconn->commit();

$_SESSION['loan_message'] = "Loan $loanId has been disbursed!";
$_SESSION['message_type'] = 'success';

$myconn->close();
header("Location: lenderDashboard.php#loanRequests");
exit();
?>
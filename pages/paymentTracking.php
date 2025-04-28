<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
session_start();

if (!isset($_SESSION['user_id'])) {
    $_SESSION['payment_message'] = "Please log in to access payment tracking.";
    $_SESSION['payment_message_type'] = 'error';
    header("Location: signin.html");
    exit();
}

$userId = $_SESSION['user_id'];
$customerId = $_SESSION['customer_id'] ?? null;

if (!$customerId) {
    $_SESSION['payment_message'] = "Customer profile not found. Please log in again.";
    $_SESSION['payment_message_type'] = 'error';
    header("Location: customerDashboard.php#paymentTracking");
    exit();
}

$conn = mysqli_connect('localhost', 'root', 'figureitout', 'LMSDB');
if (!$conn) {
    $_SESSION['payment_message'] = "Connection failed: " . mysqli_connect_error();
    $_SESSION['payment_message_type'] = 'error';
    header("Location: customerDashboard.php#paymentTracking");
    exit();
}

require_once 'fetchActiveLoans.php';

$filters = [
    'payment_status' => $_GET['payment_status'] ?? '',
    'loan_type' => $_GET['loan_type'] ?? '',
    'amount_range' => $_GET['amount_range'] ?? '',
    'date_range' => $_GET['date_range'] ?? ''
];

if (isset($_GET['reset']) && $_GET['reset'] === 'true') {
    unset($_SESSION['active_loans']);
    unset($_SESSION['payment_filters']);
    $filters = [
        'payment_status' => '',
        'loan_type' => '',
        'amount_range' => '',
        'date_range' => ''
    ];
}

$_SESSION['active_loans'] = fetchActiveLoans($conn, $customerId, $filters);
$_SESSION['payment_filters'] = $filters;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_submit'])) {
    $loanId = intval($_POST['loan_id']);
    $amount = floatval($_POST['amount']);
    $paymentMethod = $conn->real_escape_string($_POST['payment_method']);
    $submittedRemainingBalance = floatval($_POST['remaining_balance']);

    $verifyQuery = "SELECT 
        loans.customer_id, 
        loans.amount, 
        loans.interest_rate, 
        loans.duration, 
        payments.payment_id, 
        COALESCE(payments.amount, 0) AS amount_paid, 
        payments.remaining_balance 
    FROM loans 
    LEFT JOIN payments ON loans.loan_id = payments.loan_id 
    WHERE loans.loan_id = ?";
    $stmt = $conn->prepare($verifyQuery);
    $stmt->bind_param("i", $loanId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0 || $result->fetch_assoc()['customer_id'] != $customerId) {
        $_SESSION['payment_message'] = "Invalid loan selected for payment";
        $_SESSION['payment_message_type'] = 'error';
    } else {
        $result->data_seek(0);
        $loanDetails = $result->fetch_assoc();
        $paymentId = $loanDetails['payment_id'];

        $principal = $loanDetails['amount'];
        $interestRate = $loanDetails['interest_rate'] / 100;
        $durationYears = $loanDetails['duration'] / 12;
        $totalAmountDue = $principal + ($principal * $interestRate * $durationYears);

        $currentAmountPaid = $loanDetails['amount_paid'];
        $currentRemainingBalance = $loanDetails['remaining_balance'] ?? $totalAmountDue;

        if ($amount <= 0 || $amount > $currentRemainingBalance) {
            $_SESSION['payment_message'] = "Invalid payment amount. Must be greater than 0 and not exceed remaining balance.";
            $_SESSION['payment_message_type'] = 'error';
        } else {
            $newAmountPaid = $currentAmountPaid + $amount;
            $newRemainingBalance = $currentRemainingBalance - $amount;
            $paymentType = ($newRemainingBalance <= 0) ? 'full' : 'partial';

            if ($paymentId) {
                $updateQuery = "UPDATE payments SET 
                    amount = ?, 
                    payment_method = ?, 
                    payment_type = ?, 
                    remaining_balance = ?
                WHERE payment_id = ?";
                $stmt = $conn->prepare($updateQuery);
                $stmt->bind_param(
                    "dssdi",
                    $newAmountPaid,
                    $paymentMethod,
                    $paymentType,
                    $newRemainingBalance,
                    $paymentId
                );
            } else {
                $insertQuery = "INSERT INTO payments (
                    loan_id, customer_id, lender_id, amount, 
                    payment_method, payment_type, remaining_balance
                ) VALUES (?, ?, ?, ?, ?, ?, ?)";
                
                $lenderQuery = "SELECT lender_id FROM loans WHERE loan_id = ?";
                $stmt = $conn->prepare($lenderQuery);
                $stmt->bind_param("i", $loanId);
                $stmt->execute();
                $lenderId = $stmt->get_result()->fetch_row()[0];

                $stmt = $conn->prepare($insertQuery);
                $stmt->bind_param(
                    "iiidssd",
                    $loanId,
                    $customerId,
                    $lenderId,
                    $amount,
                    $paymentMethod,
                    $paymentType,
                    $newRemainingBalance
                );
            }

            if ($stmt->execute()) {
                $_SESSION['payment_message'] = "Payment of KES " . number_format($amount, 2) . " processed successfully!";
                $_SESSION['payment_message_type'] = 'success';
                
                // Activity Logging
                $activity = "Processed payment of $amount for loan ID $loanId";
                $activityQuery = "INSERT INTO activity (user_id, activity, activity_time, activity_type) VALUES (?, ?, NOW(), 'payment')";
                $stmt = $conn->prepare($activityQuery);
                $stmt->bind_param("is", $userId, $activity);
                $stmt->execute();

                $_SESSION['active_loans'] = fetchActiveLoans($conn, $customerId, $filters);
            } else {
                $_SESSION['payment_message'] = "Error processing payment: " . $conn->error;
                $_SESSION['payment_message_type'] = 'error';
            }
        }
    }
}

mysqli_close($conn);
header("Location: customerDashboard.php#paymentTracking");
exit();
?>
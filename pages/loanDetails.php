<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Create database connection
$mysqli = new mysqli('localhost', 'root', 'figureitout', 'LMSDB');
// Session handling
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Authentication check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['customer_id'])) {
    $_SESSION['loan_message'] = "Please login to view loan details";
    header("Location: /lms/pages/signin.html");
    exit;
}

// Validate loan ID
if (!isset($_GET['loan_id']) || !is_numeric($_GET['loan_id'])) {
    $_SESSION['loan_message'] = "Invalid loan ID";
    header("Location: /lms/pages/customerDashboard.php#loanHistory");
    exit;
}

$loan_id = (int)$_GET['loan_id'];
$customer_id = (int)$_SESSION['customer_id'];

// Fetch loan details without table aliases
$query = "SELECT 
            loans.loan_id,
            loans.offer_id,
            loans.lender_id,
            loans.customer_id,
            loans.amount,
            loans.interest_rate,
            loans.duration,
            loans.installments,
            loans.collateral_value,
            loans.collateral_description,
            loans.status,
            loans.created_at,
            loan_offers.loan_type,
            lenders.name AS lender_name
          FROM loans
          JOIN loan_offers ON loans.offer_id = loan_offers.offer_id
          JOIN lenders ON loans.lender_id = lenders.lender_id
          WHERE loans.loan_id = ? AND loans.customer_id = ?";

$stmt = $mysqli->prepare($query);
if (!$stmt) {
    $_SESSION['loan_message'] = "Database error";
    header("Location: /lms/pages/customerDashboard.php#loanHistory");
    exit;
}

$stmt->bind_param("ii", $loan_id, $customer_id);

if (!$stmt->execute()) {
    $_SESSION['loan_message'] = "Error fetching loan details";
    header("Location: /lms/pages/customerDashboard.php#loanHistory");
    exit;
}

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['loan_message'] = "Loan not found or access denied";
    header("Location: /lms/pages/customerDashboard.php#loanHistory");
    exit;
}

$loan = $result->fetch_assoc();

// Format display values
$loan_details = [
    'loan_id' => $loan['loan_id'],
    'loan_type' => htmlspecialchars($loan['loan_type']),
    'lender_name' => htmlspecialchars($loan['lender_name']),
    'amount' => number_format($loan['amount']),
    'interest_rate' => $loan['interest_rate'],
    'duration' => $loan['duration'],
    'installments' => number_format($loan['installments'], 2),
    'collateral_value' => number_format($loan['collateral_value']),
    'collateral_description' => htmlspecialchars($loan['collateral_description']),
    'status' => $loan['status'],
    'created_date' => date('j M Y', strtotime($loan['created_at']))
];

// Store in session for display
$_SESSION['loan_details'] = $loan_details;

// Redirect back
header("Location: /lms/pages/customerDashboard.php#loanHistory");
exit;
?>
<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['lender_id'])) {
    die("Lender ID not set in session.");
}

$lender_id = $_SESSION['lender_id'];

// Database config file
include '../phpconfig/config.php';

// Get filter parameters
$activeStatusFilter = $_GET['active_status'] ?? '';
$activeLoanTypeFilter = $_GET['active_loan_type'] ?? '';
$activeDateRange = $_GET['active_date_range'] ?? '';
$activeAmountRange = $_GET['active_amount_range'] ?? '';
$activeDurationRange = $_GET['active_duration_range'] ?? '';
$activeCollateralRange = $_GET['active_collateral_range'] ?? '';

// Active loans query
$activeLoansQuery = "
    SELECT 
        loans.loan_id,
        loans.amount,
        loans.interest_rate,
        loans.duration,
        loans.collateral_value,
        loans.collateral_description,
        loans.status,
        loans.created_at,
        customers.name,
        loan_offers.loan_type,
        latest_payment.remaining_balance
    FROM loans
    JOIN loan_offers ON loans.offer_id = loan_offers.offer_id
    JOIN customers ON loans.customer_id = customers.customer_id
    JOIN (
        SELECT loan_id, remaining_balance, payment_type
        FROM payments
        WHERE (loan_id, payment_date) IN (
            SELECT loan_id, MAX(payment_date)
            FROM payments
            GROUP BY loan_id
        )
    ) latest_payment ON loans.loan_id = latest_payment.loan_id
    WHERE loans.lender_id = '$lender_id'
    AND loans.status = 'approved'
    AND latest_payment.remaining_balance > 0
    AND latest_payment.payment_type != 'full'";

// Apply filters
// Status filter
if (!empty($activeStatusFilter) && in_array($activeStatusFilter, ['approved'])) {
    $activeLoansQuery .= " AND loans.status = '$activeStatusFilter'";
}

// Loan type filter
if (!empty($activeLoanTypeFilter)) {
    $activeLoansQuery .= " AND loan_offers.loan_type = '$activeLoanTypeFilter'";
}

// Date range filter
if (!empty($activeDateRange)) {
    switch ($activeDateRange) {
        case 'today':
            $activeLoansQuery .= " AND DATE(loans.created_at) = CURDATE()";
            break;
        case 'week':
            $activeLoansQuery .= " AND YEARWEEK(loans.created_at, 1) = YEARWEEK(CURDATE(), 1)";
            break;
        case 'month':
            $activeLoansQuery .= " AND MONTH(loans.created_at) = MONTH(CURDATE()) AND YEAR(loans.created_at) = YEAR(CURDATE())";
            break;
        case 'year':
            $activeLoansQuery .= " AND YEAR(loans.created_at) = YEAR(CURDATE())";
            break;
    }
}

// Amount range filter
if (!empty($activeAmountRange)) {
    list($minAmount, $maxAmount) = explode('-', str_replace('+', '-', $activeAmountRange));
    $activeLoansQuery .= " AND loans.amount >= $minAmount";
    if (is_numeric($maxAmount)) {
        $activeLoansQuery .= " AND loans.amount <= $maxAmount";
    }
}

// Duration filter
if (!empty($activeDurationRange)) {
    list($minDuration, $maxDuration) = explode('-', str_replace('+', '-', $activeDurationRange));
    $activeLoansQuery .= " AND loans.duration >= $minDuration";
    if (is_numeric($maxDuration)) {
        $activeLoansQuery .= " AND loans.duration <= $maxDuration";
    }
}

// Collateral filter
if (!empty($activeCollateralRange)) {
    list($minCollateral, $maxCollateral) = explode('-', str_replace('+', '-', $activeCollateralRange));
    $activeLoansQuery .= " AND loans.collateral_value >= $minCollateral";
    if (is_numeric($maxCollateral)) {
        $activeLoansQuery .= " AND loans.collateral_value <= $maxCollateral";
    }
}

$activeLoansQuery .= " ORDER BY loans.created_at DESC";

// Execute query
$activeLoansResult = mysqli_query($myconn, $activeLoansQuery);
if (!$activeLoansResult) {
    die("Active loans query failed: " . mysqli_error($myconn));
}
$activeLoanData = mysqli_fetch_all($activeLoansResult, MYSQLI_ASSOC);

// Define all loan types
$allLoanTypes = [
    "Personal Loan", "Business Loan", "Mortgage Loan", 
    "MicroFinance Loan", "Student Loan", "Construction Loan",
    "Green Loan", "Medical Loan", "Startup Loan", "Agricultural Loan"
];

// Return data
return [
    'activeLoanData' => $activeLoanData,
    'filters' => [
        'status' => $activeStatusFilter,
        'loan_type' => $activeLoanTypeFilter,
        'date_range' => $activeDateRange,
        'amount_range' => $activeAmountRange,
        'duration_range' => $activeDurationRange,
        'collateral_range' => $activeCollateralRange
    ],
    'allLoanTypes' => $allLoanTypes
];

// mysqli_close($myconn);
?>
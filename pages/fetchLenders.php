<?php
// Start the session
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    $_SESSION['loan_message'] = "Unauthorized access";
    $_SESSION['message_type'] = "error";
    header("Location: /lms/pages/signin.html");
    exit;
}

// Database connection
$mysqli = new mysqli('localhost', 'root', 'figureitout', 'LMSDB');
if ($mysqli->connect_error) {
    $_SESSION['loan_message'] = "Database connection failed";
    $_SESSION['message_type'] = "error";
    header("Location: /lms/pages/customerDashboard.php#applyLoan");
    exit;
}

// Handle reset request
if (isset($_GET['reset_filters']) && $_GET['reset_filters'] === 'true') {
    unset($_SESSION['filtered_lenders']);
    unset($_SESSION['current_filters']);
    unset($_SESSION['filters_applied']);
    header("Location: customerDashboard.php#applyLoan");
    exit;
}

// Initialize filters
$filters = [
    'loan_types' => [],
    'interest_ranges' => []
];

// Process loan type filter
if (isset($_GET['loan_type']) && is_array($_GET['loan_type'])) {
    $filters['loan_types'] = array_map(function($type) use ($mysqli) {
        return $mysqli->real_escape_string($type);
    }, $_GET['loan_type']);
}

// Process amount filters 
$amountConditions = [];
if (isset($_GET['min_amount']) && is_numeric($_GET['min_amount'])) {
    $filters['min_amount'] = max(0, (int)$_GET['min_amount']);
    $amountConditions[] = "loan_offers.max_amount >= ?";
}

if (isset($_GET['max_amount']) && is_numeric($_GET['max_amount'])) {
    $filters['max_amount'] = (int)$_GET['max_amount'];
    $amountConditions[] = "loan_offers.max_amount <= ?";
}

// Process interest rate filter
if (isset($_GET['interest_range']) && is_array($_GET['interest_range'])) {
    $filters['interest_ranges'] = array_filter($_GET['interest_range'], function($range) {
        return in_array($range, ['0-5', '5-10', '10+']);
    });
}

// Build base query
$query = "SELECT 
            loan_offers.*, 
            lenders.name AS lender_name
          FROM loan_offers
          JOIN lenders ON loan_offers.lender_id = lenders.lender_id";

// Add WHERE conditions only if we have any filters
$whereConditions = [];
$params = [];
$paramTypes = '';

// Add amount conditions if they exist
if (!empty($amountConditions)) {
    $whereConditions = array_merge($whereConditions, $amountConditions);
}

// Add loan type condition if specified
if (!empty($filters['loan_types'])) {
    $placeholders = implode(',', array_fill(0, count($filters['loan_types']), '?'));
    $whereConditions[] = "loan_offers.loan_type IN ($placeholders)";
}

// Add interest rate conditions if specified
if (!empty($filters['interest_ranges'])) {
    $conditions = [];
    foreach ($filters['interest_ranges'] as $range) {
        switch ($range) {
            case '0-5': 
                $conditions[] = "loan_offers.interest_rate BETWEEN 0 AND 5"; 
                break;
            case '5-10': 
                $conditions[] = "loan_offers.interest_rate BETWEEN 5 AND 10"; 
                break;
            case '10+': 
                $conditions[] = "loan_offers.interest_rate > 10"; 
                break;
        }
    }
    $whereConditions[] = "(" . implode(' OR ', $conditions) . ")";
}

// Combine all conditions
if (!empty($whereConditions)) {
    $query .= " WHERE " . implode(' AND ', $whereConditions);
}

// Add sorting
$query .= " ORDER BY loan_offers.offer_id DESC";

// Prepare statement
$stmt = $mysqli->prepare($query);
if (!$stmt) {
    $_SESSION['loan_message'] = "Failed to prepare query: " . $mysqli->error;
    $_SESSION['message_type'] = "error";
    header("Location: customerDashboard.php#applyLoan");
    exit();
}

// Bind parameters
$params = [];
$paramTypes = '';

// Add amount parameters if they exist
if (isset($filters['min_amount'])) {
    $params[] = $filters['min_amount'];
    $paramTypes .= 'i';
}

if (isset($filters['max_amount'])) {
    $params[] = $filters['max_amount'];
    $paramTypes .= 'i';
}

// Add loan types to parameters if they exist
if (!empty($filters['loan_types'])) {
    $paramTypes .= str_repeat('s', count($filters['loan_types']));
    $params = array_merge($params, $filters['loan_types']);
}

// Bind parameters if they exist
if (!empty($params)) {
    $stmt->bind_param($paramTypes, ...$params);
}

// Execute query
if (!$stmt->execute()) {
    $_SESSION['loan_message'] = "Failed to execute query: " . $stmt->error;
    $_SESSION['message_type'] = "error";
    header("Location: customerDashboard.php#applyLoan");
    exit();
}

// Get results
$result = $stmt->get_result();
$lenders = $result->fetch_all(MYSQLI_ASSOC);

// Clear previous filtered lenders
unset($_SESSION['filtered_lenders']);

if (!empty($lenders)) {
    $_SESSION['filtered_lenders'] = array_map(function($lender) {
        return [
            'offer_id' => (int)$lender['offer_id'],
            'lender_id' => (int)$lender['lender_id'],
            'name' => htmlspecialchars($lender['lender_name']),
            'type' => htmlspecialchars($lender['loan_type']),
            'rate' => $lender['interest_rate'],
            'duration' => (int)$lender['max_duration'],
            'amount' => (int)$lender['max_amount']
        ];
    }, $lenders);
}

// Store filter state only if filters were actually applied
if (!empty($_GET['loan_type']) || isset($_GET['min_amount']) || 
    isset($_GET['max_amount']) || !empty($_GET['interest_range'])) {
    $_SESSION['current_filters'] = $filters;
    $_SESSION['filters_applied'] = true;
}

$stmt->close();
$mysqli->close();

// Redirect back to loan application page
header("Location: customerDashboard.php#applyLoan");
exit();
?>
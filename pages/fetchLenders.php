<?php
// Start the session
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Don't start a new session if one already exists
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    // Store error message and redirect
    $_SESSION['loan_message'] = "Unauthorized access";
    $_SESSION['message_type'] = "error";
    header("Location: /lms/pages/signin.html");
    exit;
}

// Database connection
$mysqli = new mysqli('localhost', 'root', 'figureitout', 'LMSDB');
if ($mysqli->connect_error) {
    // Store error in session and redirect
    $_SESSION['loan_message'] = "Database connection failed";
    $_SESSION['message_type'] = "error";
    header("Location: /lms/pages/customerDashboard.php#applyLoan");
    exit;
}

// Initialize filters with default values
$filters = [
    'min_amount' => 0,
    'max_amount' => PHP_INT_MAX,
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
if (isset($_GET['min_amount']) && is_numeric($_GET['min_amount'])) {
    $filters['min_amount'] = max(0, (int)$_GET['min_amount']);
}

if (isset($_GET['max_amount']) && is_numeric($_GET['max_amount'])) {
    $filters['max_amount'] = max($filters['min_amount'], (int)$_GET['max_amount']);
}

// Process interest rate filter
if (isset($_GET['interest_range']) && is_array($_GET['interest_range'])) {
    $filters['interest_ranges'] = array_filter($_GET['interest_range'], function($range) {
        return in_array($range, ['0-5', '5-10', '10+']);
    });
}

// Build base query
$query = "SELECT 
            loan_products.*, 
            lenders.name AS lender_name
          FROM loan_products
          JOIN lenders ON loan_products.lender_id = lenders.lender_id
          WHERE loan_products.max_amount BETWEEN ? AND ?";

// Add loan type condition if specified
if (!empty($filters['loan_types'])) {
    $placeholders = implode(',', array_fill(0, count($filters['loan_types']), '?'));
    $query .= " AND loan_products.loan_type IN ($placeholders)";
}

// Add interest rate conditions if specified
if (!empty($filters['interest_ranges'])) {
    $conditions = [];
    foreach ($filters['interest_ranges'] as $range) {
        switch ($range) {
            case '0-5': 
                $conditions[] = "loan_products.interest_rate BETWEEN 0 AND 5"; 
                break;
            case '5-10': 
                $conditions[] = "loan_products.interest_rate BETWEEN 5 AND 10"; 
                break;
            case '10+': 
                $conditions[] = "loan_products.interest_rate > 10"; 
                break;
        }
    }
    $query .= " AND (" . implode(' OR ', $conditions) . ")";
}

// Add sorting
$query .= " ORDER BY loan_products.product_id DESC";

// Prepare statement
$stmt = $mysqli->prepare($query);
if (!$stmt) {
    $_SESSION['loan_message'] = "Failed to prepare query: " . $mysqli->error;
    $_SESSION['message_type'] = "error";
    header("Location: customerDashboard.php#applyLoan");
    exit();
}

// Bind parameters
$paramTypes = 'ii'; // min_amount and max_amount are integers
$params = [$filters['min_amount'], $filters['max_amount']];

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

// After executing the query:
if (empty($lenders)) {
    $_SESSION['loan_message'] = "No loans match your filter criteria";
    $_SESSION['message_type'] = "error";
    $_SESSION['filtered_lenders'] = []; // Explicit empty array
    $_SESSION['filters_applied'] = true; // Track that filters were applied
} else {
    $_SESSION['filtered_lenders'] = array_map(function($lender) {
        // Your existing mapping code
    }, $lenders);
    $_SESSION['filters_applied'] = true; // Track that filters were applied
}

// Clear any previous filtered lenders
unset($_SESSION['filtered_lenders']);

if (empty($lenders)) {
    // No lenders matched the filter criteria
    $_SESSION['loan_message'] = "No loans available matching your filter criteria";
    $_SESSION['message_type'] = "info";
} else {
    // Store lenders in session to display on page reload
    $_SESSION['filtered_lenders'] = array_map(function($lender) {
        return [
            'product_id' => (int)$lender['product_id'],
            'lender_id' => (int)$lender['lender_id'],
            'name' => htmlspecialchars($lender['lender_name']),
            'type' => htmlspecialchars($lender['loan_type']),
            'rate' => (float)$lender['interest_rate'],
            'duration' => (int)$lender['max_duration'],
            'amount' => (int)$lender['max_amount']
        ];
    }, $lenders);
}

// Store the filter criteria to maintain filter state
$_SESSION['current_filters'] = [
    'min_amount' => $filters['min_amount'],
    'max_amount' => $filters['max_amount'],
    'loan_types' => $filters['loan_types'],
    'interest_ranges' => $filters['interest_ranges']
];

$stmt->close();
$mysqli->close();

// Redirect back to loan application page
header("Location: customerDashboard.php#applyLoan");
exit();
?>
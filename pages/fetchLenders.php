<?php
session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'error' => 'Unauthorized']));
}

// Database connection
$mysqli = new mysqli('localhost', 'root', 'figureitout', 'LMSDB');
if ($mysqli->connect_error) {
    die(json_encode(['success' => false, 'error' => 'Database connection failed']));
}

// Get and validate filters
$filters = [
    'min_amount' => 0,
    'max_amount' => PHP_INT_MAX,
    'interest_ranges' => []
];

// Get loan types from database if they exist in request
$loanTypesCondition = "";
if (isset($_GET['loan_type']) && is_array($_GET['loan_type'])) {
    // Escape and prepare loan types for SQL
    $loanTypes = array_map(function($type) use ($mysqli) {
        return "'" . $mysqli->real_escape_string($type) . "'";
    }, $_GET['loan_type']);
    
    $loanTypesCondition = " AND loan_products.loan_type IN (" . implode(',', $loanTypes) . ")";
}

if (isset($_GET['min_amount'])) {
    $filters['min_amount'] = max(0, (int)$_GET['min_amount']);
}

if (isset($_GET['max_amount'])) {
    $filters['max_amount'] = max($filters['min_amount'], (int)$_GET['max_amount']);
}

if (isset($_GET['interest_range']) && is_array($_GET['interest_range'])) {
    $filters['interest_ranges'] = array_filter($_GET['interest_range'], function($range) {
        return in_array($range, ['0-5', '5-10', '10+']);
    });
}

// Build query
$query = "SELECT 
            loan_products.*, 
            lenders.name AS lender_name
          FROM loan_products
          JOIN lenders ON loan_products.lender_id = lenders.lender_id
          WHERE loan_products.max_amount BETWEEN ? AND ?
          $loanTypesCondition";

// Interest rate filter
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

$query .= " ORDER BY loan_products.product_id DESC";

// Prepare and execute query
$stmt = $mysqli->prepare($query);
if (!$stmt) {
    die(json_encode(['success' => false, 'error' => 'Query preparation failed: ' . $mysqli->error]));
}

// Bind parameters (only amount range as loan types are in the query directly)
$stmt->bind_param('ii', $filters['min_amount'], $filters['max_amount']);

if (!$stmt->execute()) {
    die(json_encode(['success' => false, 'error' => 'Query execution failed: ' . $stmt->error]));
}

$result = $stmt->get_result();
$lenders = $result->fetch_all(MYSQLI_ASSOC);

// Return response - UPDATED VERSION
echo json_encode([
    'success' => true,
    'data' => array_map(function($lender) {
        return [
            'id' => (int)$lender['product_id'],  // This is crucial for product_id
            'product_id' => (int)$lender['product_id'], // Added for clarity
            'lender_id' => (int)$lender['lender_id'],
            'name' => htmlspecialchars($lender['lender_name']),
            'type' => htmlspecialchars($lender['loan_type']),
            'rate' => (float)$lender['interest_rate'],
            'duration' => (int)$lender['max_duration'],
            'amount' => (int)$lender['max_amount']
        ];
    }, $lenders)
]);
$stmt->close();
$mysqli->close();
?>
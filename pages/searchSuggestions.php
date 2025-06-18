<?php
// Start the session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit;
}

// Database config file
include '../phpconfig/config.php';

if ($myconn->connect_error) {
    header('HTTP/1.1 500 Internal Server Error');
    exit;
}

$query = isset($_GET['query']) ? trim($_GET['query']) : '';
$results = ['loan_types' => [], 'lenders' => []];

if (strlen($query) >= 2) {
    // List of available loan types
    $loan_types = [
        'Personal Loan',
        'Business Loan',
        'Mortgage Loan',
        'MicroFinance Loan',
        'Student Loan',
        'Construction Loan',
        'Green Loan',
        'Medical Loan',
        'Startup Loan',
        'Agricultural Loan'
    ];

    // Filter loan types based on query
    foreach ($loan_types as $type) {
        if (stripos($type, $query) !== false) {
            $results['loan_types'][] = $type;
        }
    }

    // Fetch lenders from database
    $sql = "SELECT DISTINCT name FROM lenders WHERE name LIKE ?";
    $stmt = $myconn->prepare($sql);
    if ($stmt) {
        $search_term = "%" . $myconn->real_escape_string($query) . "%";
        $stmt->bind_param('s', $search_term);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $results['lenders'][] = $row['name'];
        }
        $stmt->close();
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($results);
$myconn->close();
?>
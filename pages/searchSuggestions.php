<?php
// Start the session to access user session data
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration file to establish connection
include '../phpconfig/config.php';

// Get the search query from GET parameters, trim whitespace, default to empty string
$query = isset($_GET['query']) ? trim($_GET['query']) : '';

// Initialize results array to store loan types and lenders
$results = ['loan_types' => [], 'lenders' => []];

// Process query only if it’s at least 1 character long
if (strlen($query) >= 1) {
    // Define static list of available loan types
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

    // Filter loan types that match the query (case-insensitive)
    foreach ($loan_types as $type) {
        if (stripos($type, $query) !== false) {
            $results['loan_types'][] = $type; // Add matching loan type to results
        }
    }

    // Prepared SQL query to fetch lender names matching the query
    $sql = "SELECT DISTINCT name FROM lenders WHERE name LIKE ?";
    $stmt = $myconn->prepare($sql); // Prepared statement to prevent SQL injection
    if ($stmt) {
        // Create search term with wildcards for partial matching
        $search_term = "%" . $myconn->real_escape_string($query) . "%";
        $stmt->bind_param('s', $search_term); // Bind search term as string
        $stmt->execute(); // Execute the query
        $result = $stmt->get_result(); // Get query results
        // Fetch each matching lender name and add to results
        while ($row = $result->fetch_assoc()) {
            $results['lenders'][] = $row['name'];
        }
        $stmt->close(); // Close the statement 
    }
}

// Set response header to indicate JSON output
header('Content-Type: application/json');
// Encode results array as JSON and output
echo json_encode($results);
// Close database connection to free resources
$myconn->close();
?>
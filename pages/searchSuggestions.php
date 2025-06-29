<?php
// Initiates or resumes a session to manage user state
if (session_status() === PHP_SESSION_NONE) { // session_status() checks if a session is active; PHP_SESSION_NONE indicates no active session
    session_start(); // Starts a new session or resumes an existing one
}

// Includes the database configuration file to establish the $myconn connection
include '../phpconfig/config.php'; // Imports database connection settings from config.php

// Retrieves and sanitizes the search query from GET parameters
$query = isset($_GET['query']) ? trim($_GET['query']) : ''; // Gets query, trims whitespace, defaults to empty string

// Initializes results array to store lenders and loan types
$results = ['lenders' => [], 'loan_types' => []]; // Empty array for search results

// Processes query if it is at least 1 character long
if (strlen($query) >= 1) { // Checks if query length is sufficient
    // Prepares SQL query to fetch lender names matching the query
    $sql = "SELECT DISTINCT name FROM lenders WHERE name LIKE ?"; // Query to find matching lender names
    $stmt = $myconn->prepare($sql); // Prepares the query to prevent SQL injection
    if ($stmt) { // Checks if statement preparation was successful
        // Creates search term with wildcards for partial matching
        $search_term = "%" . $myconn->real_escape_string($query) . "%"; // Escapes query and adds wildcards
        $stmt->bind_param('s', $search_term); // Binds search term as string
        $stmt->execute(); // Executes the query
        $result = $stmt->get_result(); // Gets the result set
        // Fetches each matching lender name
        while ($row = $result->fetch_assoc()) { // Fetches rows as associative array
            $results['lenders'][] = $row['name']; // Adds lender name to results
        }
        $stmt->close(); // Closes the statement
    }

    // Defines static list of available loan types
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
    ]; // Static array of loan types

    // Filters loan types that match the query
    foreach ($loan_types as $type) { // Iterates through loan types
        if (stripos($type, $query) !== false) { // stripos() checks for case-insensitive match
            $results['loan_types'][] = $type; // Adds matching loan type to results
        }
    }
}

// Sets response header to indicate JSON output
header('Content-Type: application/json'); // Specifies JSON content type
// Encodes results array as JSON and outputs it
echo json_encode($results); // Outputs JSON-encoded results
// Closes the database connection
$myconn->close(); // Terminates the database connection
?>
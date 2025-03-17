<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['loan_message'] = "You must be logged in to create a loan.";
    header("Location: lenderDashboard.php#createLoan");
    exit();
}

// Database connection
$myconn = mysqli_connect('localhost', 'root', 'figureitout', 'LMSDB');

// Check connection
if (!$myconn) {
    $_SESSION['loan_message'] = "Connection failed: " . mysqli_connect_error();
    header("Location: lenderDashboard.php#createLoan");
    exit();
}

// Retrieve user_id from the session
$user_id = $_SESSION['user_id'];

// Fetch lender_id from the lenders table
$lenderQuery = "SELECT lender_id FROM lenders WHERE user_id = '$user_id'";
$lenderResult = mysqli_query($myconn, $lenderQuery);

if (mysqli_num_rows($lenderResult) === 0) {
    $_SESSION['loan_message'] = "You are not registered as a lender.";
    header("Location: lenderDashboard.php#createLoan");
    exit();
}

$lender = mysqli_fetch_assoc($lenderResult);
$lender_id = $lender['lender_id'];

// Get form data
$loan_type = $_POST['type'];
$interest_rate = floatval($_POST['interestRate']);
$max_duration = intval($_POST['maxDuration']);


// Insert into loans table
$sql = "INSERT INTO loans (lender_id, loan_type, interest_rate, max_duration, customer_id, amount, duration, installments, collateral_description, collateral_value) 
        VALUES ('$lender_id', '$loan_type', '$interest_rate', '$max_duration', NULL, 0, 0, 0, 'null', 0)";

if (mysqli_query($myconn, $sql)) {
    $_SESSION['loan_message'] = "Loan created successfully!";
} else {
    $_SESSION['loan_message'] = "Error: " . mysqli_error($myconn);
}

// Close the database connection
mysqli_close($myconn);

// Redirect back to the lender dashboard
header("Location: lenderDashboard.php#createLoan");
exit();
?>
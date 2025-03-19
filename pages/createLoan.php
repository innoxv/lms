<?php
// Start the session
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
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

// Check if the loan type already exists
$checkQuery = "SELECT loan_id FROM loans WHERE loan_type = '$loan_type'";
$checkResult = mysqli_query($myconn, $checkQuery);

if (mysqli_num_rows($checkResult) > 0) {
    $_SESSION['loan_message'] = "$loan_type already exists.";
    header("Location: lenderDashboard.php#createLoan");
    exit();
}

// Insert the loan type into the loans table
$sql = "INSERT INTO loans (lender_id, loan_type, interest_rate, max_duration, available) 
        VALUES ('$lender_id', '$loan_type', '$interest_rate', '$max_duration', TRUE)";

if (mysqli_query($myconn, $sql)) {
    // Generate 4 extra slots for the new loan type
    for ($i = 0; $i < 4; $i++) {
        $slotQuery = "INSERT INTO loans (lender_id, loan_type, interest_rate, max_duration, available) 
                      VALUES ('$lender_id', '$loan_type', '$interest_rate', '$max_duration', TRUE)";
        mysqli_query($myconn, $slotQuery);
    }

    $_SESSION['loan_message'] = "$loan_type created successfully with 5 slots!";
} else {
    $_SESSION['loan_message'] = "Error: " . mysqli_error($myconn);
}

// Close the database connection
mysqli_close($myconn);

// Redirect back to the lender dashboard
header("Location: lenderDashboard.php#createLoan");
exit();
?>
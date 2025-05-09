<?php
session_start();
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

if (!isset($_SESSION['user_id'])) {
    $_SESSION['loan_message'] = "You must be logged in to create a loan offer.";
    header("Location: lenderDashboard.php#createLoan");
    exit();
}

// Database config file
include '../phpconfig/config.php';

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

// Get form data with validation
$loan_type = mysqli_real_escape_string($myconn, $_POST['type']);
$interest_rate = floatval($_POST['interestRate']);
$max_amount = floatval($_POST['maxAmount']);
$max_duration = intval($_POST['maxDuration']);

// Check if the loan type already exists
$checkQuery = "SELECT offer_id FROM loan_offers 
              WHERE loan_type = '$loan_type' AND lender_id = '$lender_id'";
$checkResult = mysqli_query($myconn, $checkQuery);

if (mysqli_num_rows($checkResult) > 0) {
    $_SESSION['loan_message'] = "$loan_type already exists in your loan offers!";
    header("Location: lenderDashboard.php#createLoan");
    exit();
}

// Insert into loan_offers table
$sql = "INSERT INTO loan_offers 
        (lender_id, loan_type, interest_rate, max_amount, max_duration)
        VALUES 
        ('$lender_id', '$loan_type', '$interest_rate', '$max_amount', '$max_duration')";

if (mysqli_query($myconn, $sql)) {
    // Log loan offer creation activity
    $activity = "Created loan offer: $loan_type";
    $logSql = "INSERT INTO activity (user_id, activity, activity_time, activity_type)
              VALUES ('$user_id', '$activity', NOW(), 'loan offer creation')";
    mysqli_query($myconn, $logSql);
    
    // Calculate new average interest rate
    $avgQuery = "SELECT AVG(interest_rate) AS new_avg FROM loan_offers WHERE lender_id = '$lender_id'";
    $avgResult = mysqli_query($myconn, $avgQuery);
    $avgData = mysqli_fetch_assoc($avgResult);
    $newAverage = $avgData['new_avg'];

    // Update lenders table
    $updateLender = "UPDATE lenders SET average_interest_rate = '$newAverage' WHERE lender_id = '$lender_id'";
    mysqli_query($myconn, $updateLender);

    $_SESSION['loan_message'] = "$loan_type created successfully!";
} else {
    $_SESSION['loan_message'] = "Error creating loan offer: " . mysqli_error($myconn);
}

mysqli_close($myconn);
header("Location: lenderDashboard.php#createLoan");
exit();
?>
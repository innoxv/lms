<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    $_SESSION['loan_message'] = "You must be logged in to add a slot.";
    header("Location: lenderDashboard.php");
    exit();
}

$myconn = mysqli_connect('localhost', 'root', 'figureitout', 'LMSDB');

if (!$myconn) {
    $_SESSION['loan_message'] = "Connection failed: " . mysqli_connect_error();
    header("Location: lenderDashboard.php");
    exit();
}

// Ensure lender_id is set in the session
if (!isset($_SESSION['lender_id'])) {
    $_SESSION['loan_message'] = "Lender ID not found. Please log in again.";
    header("Location: lenderDashboard.php");
    exit();
}

$loan_type = $_POST['loan_type']; // Get the loan type from the form
$lender_id = $_SESSION['lender_id']; // Get the lender ID from the session

// Fetch the most recent loan of the same type to get the interest rate and max duration
$fetchQuery = "SELECT interest_rate, max_duration 
               FROM loans 
               WHERE loan_type = '$loan_type' AND lender_id = '$lender_id' 
               ORDER BY loan_id DESC 
               LIMIT 1";
$fetchResult = mysqli_query($myconn, $fetchQuery);

if (mysqli_num_rows($fetchResult) > 0) {
    $loanData = mysqli_fetch_assoc($fetchResult);
    $interest_rate = $loanData['interest_rate'];
    $max_duration = $loanData['max_duration'];
} else {
    // Default values if no previous loan of this type exists
    $interest_rate = 5.0; // Default interest rate
    $max_duration = 12;   // Default max duration in months
}

// Insert a new slot for the loan type
$insertQuery = "INSERT INTO loans (lender_id, loan_type, interest_rate, max_duration, customer_id) 
                VALUES ('$lender_id', '$loan_type', '$interest_rate', '$max_duration', NULL)";

if (mysqli_query($myconn, $insertQuery)) {
    $_SESSION['loan_message'] = "New slot added for $loan_type!";
} else {
    $_SESSION['loan_message'] = "Error adding slot: " . mysqli_error($myconn);
}

mysqli_close($myconn);
header("Location: lenderDashboard.php#createLoan");
exit();
?>
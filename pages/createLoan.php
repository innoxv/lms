<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    $_SESSION['loan_message'] = "You must be logged in to create a loan product.";
    header("Location: lenderDashboard.php#createLoan");
    exit();
}

$myconn = mysqli_connect('localhost', 'root', 'figureitout', 'LMSDB');

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

// Get form data with validation
$loan_type = mysqli_real_escape_string($myconn, $_POST['type']);
$interest_rate = floatval($_POST['interestRate']);
$max_amount = floatval($_POST['maxAmount']);
$max_duration = intval($_POST['maxDuration']);


// Check if the loan type already exists in loan_products for this lender
$checkQuery = "SELECT product_id FROM loan_products 
              WHERE loan_type = '$loan_type' AND lender_id = '$lender_id'";
$checkResult = mysqli_query($myconn, $checkQuery);

if (mysqli_num_rows($checkResult) > 0) {
    $_SESSION['loan_message'] = "$loan_type already exists in your loan products!";
    header("Location: lenderDashboard.php#createLoan");
    exit();
}

// Insert into loan_products table
$sql = "INSERT INTO loan_products 
        (lender_id, loan_type, interest_rate, max_amount, max_duration)
        VALUES 
        ('$lender_id', '$loan_type', '$interest_rate', '$max_amount', '$max_duration')";

if (mysqli_query($myconn, $sql)) {
    // Calculate new average interest rate
    $avgQuery = "SELECT AVG(interest_rate) AS new_avg 
                FROM loan_products 
                WHERE lender_id = '$lender_id'";
    $avgResult = mysqli_query($myconn, $avgQuery);
    $avgData = mysqli_fetch_assoc($avgResult);
    $newAverage = $avgData['new_avg'];

    // Update lenders table
    $updateLender = "UPDATE lenders 
                    SET average_interest_rate = '$newAverage' 
                    WHERE lender_id = '$lender_id'";
    mysqli_query($myconn, $updateLender);

    $_SESSION['loan_message'] = "$loan_type created successfully!";
} else {
    $_SESSION['loan_message'] = "Error creating loan product: " . mysqli_error($myconn);
}

mysqli_close($myconn);
header("Location: lenderDashboard.php#createLoan");
exit();
?>
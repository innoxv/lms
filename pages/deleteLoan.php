<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['lender_id'])) {
    $_SESSION['loan_message'] = "Unauthorized access";
    header("Location: lenderDashboard.php");
    exit();
}

$myconn = mysqli_connect('localhost', 'root', 'figureitout', 'LMSDB');

// Get product ID to delete
$product_id = $_POST['product_id'];

if (!$product_id) {
    $_SESSION['loan_message'] = "No loan product specified";
    header("Location: lenderDashboard.php");
    exit();
}

// Verify the product belongs to this lender
$verifyQuery = "SELECT product_id FROM loan_products 
               WHERE product_id = $product_id AND lender_id = {$_SESSION['lender_id']}";
$verifyResult = mysqli_query($myconn, $verifyQuery);

if (mysqli_num_rows($verifyResult) === 0) {
    $_SESSION['loan_message'] = "Loan product not found or unauthorized";
    header("Location: lenderDashboard.php");
    exit();
}

// Check if there are active loans for this product
$loansCheck = "SELECT COUNT(*) as loan_count FROM loans 
              WHERE product_id = $product_id 
              AND status IN ('pending', 'approved', 'disbursed', 'active')";
$loansResult = mysqli_query($myconn, $loansCheck);
$loansData = mysqli_fetch_assoc($loansResult);

if ($loansData['loan_count'] > 0) {
    $_SESSION['loan_message'] = "Cannot delete - there are active loans for this product";
    header("Location: lenderDashboard.php");
    exit();
}

// Delete the loan product
$deleteQuery = "DELETE FROM loan_products WHERE product_id = $product_id";


if (mysqli_query($myconn, $deleteQuery)) {
    // Update average interest rate in lenders table
    $updateLender = "UPDATE lenders l
                    SET average_interest_rate = (
                        SELECT COALESCE(AVG(interest_rate), 0) 
                        FROM loan_products 
                        WHERE lender_id = {$_SESSION['lender_id']}
                    )
                    WHERE l.lender_id = {$_SESSION['lender_id']}";
    
    if (!mysqli_query($myconn, $updateLender)) {
        error_log("Failed to update average interest rate: " . mysqli_error($myconn));
    }
    
    $_SESSION['loan_message'] = "Loan product deleted successfully";
} else {
    $_SESSION['loan_message'] = "Error deleting loan: " . mysqli_error($myconn);
}
mysqli_close($myconn);
header("Location: lenderDashboard.php#createLoan");
exit();
?>
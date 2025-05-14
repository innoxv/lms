<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id']) || !isset($_SESSION['lender_id'])) {
    $_SESSION['loan_message'] = "Unauthorized access";
    header("Location: lenderDashboard.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);

// Database config file
include '../phpconfig/config.php';

// Get offer ID to delete
$offer_id = $_POST['offer_id'];

if (!$offer_id) {
    $_SESSION['loan_message'] = "No loan offer specified";
    header("Location: lenderDashboard.php");
    exit();
}

// Verify the offer belongs to this lender and get details
$verifyQuery = "SELECT offer_id, loan_type, interest_rate, max_amount, max_duration 
               FROM loan_offers 
               WHERE offer_id = $offer_id AND lender_id = {$_SESSION['lender_id']}";
$verifyResult = mysqli_query($myconn, $verifyQuery);

if (mysqli_num_rows($verifyResult) === 0) {
    $_SESSION['loan_message'] = "Loan offer not found or unauthorized";
    header("Location: lenderDashboard.php");
    exit();
}

$offer = mysqli_fetch_assoc($verifyResult);

// Check if there are active loans for this offer
$loansCheck = "SELECT COUNT(*) as loan_count FROM loans 
              WHERE offer_id = $offer_id 
              AND status IN ('pending', 'disbursed', 'disbursed', 'active')";
$loansResult = mysqli_query($myconn, $loansCheck);
$loansData = mysqli_fetch_assoc($loansResult);

if ($loansData['loan_count'] > 0) {
    $_SESSION['loan_message'] = "Cannot delete - there are active loans for this offer";
    header("Location: lenderDashboard.php#createLoan");
    exit();
}

// Delete the loan offer
$deleteQuery = "DELETE FROM loan_offers WHERE offer_id = $offer_id";

if (mysqli_query($myconn, $deleteQuery)) {
    // Log the deletion activity
    $activity = "Deleted loan offer, offer ID $offer_id";
    $myconn->query(
        "INSERT INTO activity (user_id, activity, activity_time, activity_type)
        VALUES ({$_SESSION['user_id']}, '$activity', NOW(), 'loan offer deletion')"
    );
    
    // Update average interest rate in lenders table
    $updateLender = "UPDATE lenders l
                    SET average_interest_rate = (
                        SELECT COALESCE(AVG(interest_rate), 0) 
                        FROM loan_offers 
                        WHERE lender_id = {$_SESSION['lender_id']}
                    )
                    WHERE l.lender_id = {$_SESSION['lender_id']}";
    
    if (!mysqli_query($myconn, $updateLender)) {
        error_log("Failed to update average interest rate: " . mysqli_error($myconn));
    }
    
    $_SESSION['loan_message'] = "Loan offer deleted successfully";
} else {
    $_SESSION['loan_message'] = "Error deleting loan: " . mysqli_error($myconn);
}
mysqli_close($myconn);
header("Location: lenderDashboard.php#createLoan");
exit();
?>
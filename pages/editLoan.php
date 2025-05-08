<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
//Check if user is logged in as lender
if (!isset($_SESSION['lender_id'])) {
    $_SESSION['loan_message'] = "You must be logged in";
    header("Location: lenderDashboard.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);


// Database config file
include '../phpconfig/config.php';

//Get and validate offer ID
$offer_id = intval($_POST['offer_id'] ?? 0);
$lender_id = intval($_SESSION['lender_id']);

//Verify the offer exists and belongs to this lender
$check_query = "SELECT offer_id, loan_type FROM loan_offers 
                WHERE offer_id = $offer_id 
                AND lender_id = $lender_id 
                LIMIT 1";
$check_result = mysqli_query($myconn, $check_query);

if (!$check_result || mysqli_num_rows($check_result) === 0) {
    $_SESSION['loan_message'] = "Loan offer not found or you don't have permission";
    header("Location: lenderDashboard.php#createLoan");
    exit();
}

$offer = mysqli_fetch_assoc($check_result);
$changes = [];

//Prepare the update
$updates = [];
if (!empty($_POST['interest_rate'])) {
    $rate = floatval($_POST['interest_rate']);
    $updates[] = "interest_rate = $rate";
    $changes[] = "interest rate to $rate%";
}
if (!empty($_POST['max_amount'])) {
    $amount = floatval($_POST['max_amount']);
    $updates[] = "max_amount = $amount";
    $changes[] = "max amount to $$amount";
}
if (!empty($_POST['max_duration'])) {
    $duration = intval($_POST['max_duration']);
    $updates[] = "max_duration = $duration";
    $changes[] = "duration to $duration months";
}

// Execute update if there are changes
if (!empty($updates)) {
    $update_query = "UPDATE loan_offers SET " . implode(", ", $updates) . 
                   " WHERE offer_id = $offer_id";
    
    if (mysqli_query($myconn, $update_query)) {
        // Log the edit activity
        if (!empty($changes)) {
            $activity = "Edited loan offer, offer ID $offer_id";
            $myconn->query(
                "INSERT INTO activity (user_id, activity, activity_time, activity_type)
                VALUES ($user_id, '$activity', NOW(), 'loan offer edit')"
            );
        }
        
        // Only update lenders table if interest rate was modified
        if (in_array("interest_rate = $rate", $updates)) {
            $updateLender = "UPDATE lenders 
                            SET average_interest_rate = (
                                SELECT AVG(interest_rate) 
                                FROM loan_offers 
                                WHERE lender_id = $lender_id
                            )
                            WHERE lender_id = $lender_id";
            
            if (!mysqli_query($myconn, $updateLender)) {
                error_log("Failed to update average interest rate: " . mysqli_error($myconn));
            }
        }
        
        $_SESSION['loan_message'] = "Loan updated successfully";
    } else {
        $_SESSION['loan_message'] = "Error updating: " . mysqli_error($myconn);
    }
} else {
    $_SESSION['loan_message'] = "No changes were made";
}

mysqli_close($myconn);
header("Location: lenderDashboard.php#createLoan");
exit();
?>
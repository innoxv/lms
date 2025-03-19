<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    $_SESSION['loan_message'] = "You must be logged in to delete a loan type.";
    header("Location: lenderDashboard.php");
    exit();
}

$myconn = mysqli_connect('localhost', 'root', 'figureitout', 'LMSDB');

if (!$myconn) {
    $_SESSION['loan_message'] = "Connection failed: " . mysqli_connect_error();
    header("Location: lenderDashboard.php");
    exit();
}

$loan_type = $_POST['loan_type']; // Get the loan type from the form
$lender_id = $_SESSION['lender_id']; // Get the lender ID from the session

// Check if any slots are assigned to customers
$checkQuery = "SELECT COUNT(*) AS assigned_slots 
               FROM loans 
               WHERE loan_type = '$loan_type' AND lender_id = '$lender_id' AND customer_id IS NOT NULL";
$checkResult = mysqli_query($myconn, $checkQuery);
$assignedSlots = mysqli_fetch_assoc($checkResult)['assigned_slots'];

if ($assignedSlots > 0) {
    $_SESSION['loan_message'] = "Cannot delete '$loan_type' because some slots are assigned to customers.";
} else {
    // Delete all slots for the loan type and lender
    $deleteQuery = "DELETE FROM loans WHERE loan_type = '$loan_type' AND lender_id = '$lender_id'";
    if (mysqli_query($myconn, $deleteQuery)) {
        $_SESSION['loan_message'] = "$loan_type deleted successfully!";
    } else {
        $_SESSION['loan_message'] = "Error deleting loan type: " . mysqli_error($myconn);
    }
}

mysqli_close($myconn);
header("Location: lenderDashboard.php#createLoan");
exit();
?>
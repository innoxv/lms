<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    $_SESSION['loan_message'] = "You must be logged in to delete a slot.";
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

// Delete one empty slot (customer_id IS NULL) for the loan type and lender
$deleteQuery = "DELETE FROM loans 
                WHERE loan_type = '$loan_type' AND lender_id = '$lender_id' AND customer_id IS NULL 
                LIMIT 1"; // Delete only one slot

if (mysqli_query($myconn, $deleteQuery)) {
    $_SESSION['loan_message'] = "Slot deleted for $loan_type!";
} else {
    $_SESSION['loan_message'] = "Error deleting slot: " . mysqli_error($myconn);
}

mysqli_close($myconn);
header("Location: lenderDashboard.php#createLoan");
exit();
?>
<?php
session_start();

$myconn = mysqli_connect('localhost', 'root', 'figureitout', 'LMSDB');
if (!$myconn) {
    die("Connection failed: " . mysqli_connect_error());
}

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['loan_id'])) {
    $loanId = (int)$_POST['loan_id'];
    $lenderId = (int)$_SESSION['lender_id'];
    
    $query = "UPDATE loans SET status = 'approved' 
              WHERE loan_id = $loanId AND lender_id = $lenderId AND status = 'pending'";
    
    if (mysqli_query($myconn, $query)) {
        $_SESSION['loan_message'] = "Loan $loanId has been approved!";
    } else {
        $_SESSION['loan_message'] = "Error: " . mysqli_error($myconn);
    }
}

header("Location: lenderDashboard.php#loanRequests");
exit();
?>
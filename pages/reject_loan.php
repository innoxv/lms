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
    
    $query = "UPDATE loans SET status = 'rejected' 
              WHERE loan_id = $loanId AND lender_id = $lenderId AND status = 'pending'";
    
    if (mysqli_query($myconn, $query)) {
        if (mysqli_affected_rows($myconn) > 0) {
            // Log loan rejection activity
            $activity = "Rejected loan application #$loanId";
            $logSql = "INSERT INTO activity (user_id, activity, activity_time, activity_type)
                      VALUES ('{$_SESSION['user_id']}', '$activity', NOW(), 'loan rejection')";
            mysqli_query($myconn, $logSql);
            
            $_SESSION['loan_message'] = "Loan $loanId has been rejected!";
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['loan_message'] = "Loan $loanId has already been approved!";
            $_SESSION['message_type'] = 'warning';
        }
    } else {
        $_SESSION['loan_message'] = "Error: " . mysqli_error($myconn);
        $_SESSION['message_type'] = 'error';
    }
}

header("Location: lenderDashboard.php#loanRequests");
exit();
?>
<?php
session_start();
// Database connection
$conn = mysqli_connect('localhost', 'root', 'figureitout', 'LMSDB');
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Validate user session
if (!isset($_SESSION['user_id'])) {
    $_SESSION['loan_message'] = "You must be logged in to perform this action";
    $_SESSION['message_type'] = "error";
    header("Location: signin.html");
    exit;
}

// Validate loan ID
if (!isset($_POST['loan_id']) || !is_numeric($_POST['loan_id'])) {
    $_SESSION['loan_message'] = "Invalid loan ID";
    $_SESSION['message_type'] = "error";
    header("Location: customerDashboard.php#loanHistory");
    exit;
}

$loan_id = intval($_POST['loan_id']);
$user_id = intval($_SESSION['user_id']);

// Verify the loan belongs to the user and is deletable
$loan_check = $conn->query(
    "SELECT loans.status, loans.amount, loans.duration 
    FROM loans
    JOIN customers ON loans.customer_id = customers.customer_id
    WHERE loans.loan_id = $loan_id
    AND customers.user_id = $user_id
    LIMIT 1"
);

if (!$loan_check || $loan_check->num_rows === 0) {
    $_SESSION['loan_message'] = "Loan not found or you don't have permission to delete it";
    $_SESSION['message_type'] = "error";
    header("Location: customerDashboard.php#loanHistory");
    exit;
}

$loan = $loan_check->fetch_assoc();
$status = strtolower($loan['status']);

// Delete the loan
$delete_query = "DELETE FROM loans WHERE loan_id = $loan_id";
if ($conn->query($delete_query)) {
    // Log the deletion activity
    $activity = "Deleted loan application, Loan ID $loan_id ";
    $conn->query(
        "INSERT INTO activity (user_id, activity, activity_time, activity_type)
        VALUES ($user_id, '$activity', NOW(), 'application deletion')"
    );
    
    $_SESSION['loan_message'] = "Loan application deleted successfully";
    $_SESSION['message_type'] = "success";
} else {
    $_SESSION['loan_message'] = "Database error: " . $conn->error;
    $_SESSION['message_type'] = "error";
}

header("Location: customerDashboard.php#loanHistory");
exit;
?>
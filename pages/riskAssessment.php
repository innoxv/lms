<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['admin_message'] = "Please log in to access risk assessment.";
    $_SESSION['admin_message_type'] = 'error';
    header("Location: signin.html");
    exit();
}

$userId = $_SESSION['user_id'];

// Database config file
include '../phpconfig/config.php';

// Function to fetch all submitted loans with customer name
function fetchAllLoans($conn) {
    $query = "SELECT loans.loan_id, loans.customer_id, loans.amount, loans.duration, 
                     loans.collateral_value, loans.collateral_description, loans.collateral_image, 
                     loans.status, loans.application_date, loan_offers.loan_type, customers.name AS customer_name
              FROM loans
              JOIN loan_offers ON loans.offer_id = loan_offers.offer_id
              JOIN customers ON loans.customer_id = customers.customer_id
              WHERE loans.status = 'submitted'
              ORDER BY loans.loan_id DESC";
    
    $stmt = $conn->prepare($query);
    if ($stmt && $stmt->execute()) {
        $result = $stmt->get_result();
        $loans = [];
        while ($row = $result->fetch_assoc()) {
            $loans[] = $row;
        }
        $stmt->close();
        return $loans;
    }
    return [];
}

// Fetch loans initially and store in session
$_SESSION['pending_loans'] = fetchAllLoans($myconn);

// Handle approve/reject form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loanId = intval($_POST['loan_id']);
    $officerId = $userId;

    // Verify loan exists and is submitted
    $verifyQuery = "SELECT loans.status 
                    FROM loans 
                    WHERE loans.loan_id = ? AND loans.status = 'submitted'";
    $stmt = $myconn->prepare($verifyQuery);
    $stmt->bind_param("i", $loanId);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            $_SESSION['admin_message'] = "Invalid loan selected or loan is not submitted.";
            $_SESSION['admin_message_type'] = 'error';
            header("Location: adminDashboard.php#loanApplicationReview");
            exit();
        }
        $stmt->close();

        // Handle Approve or Reject
        if (isset($_POST['approve'])) {
            $newStatus = 'pending';
            $activityType = "loan approval";
            $activity = "Approved loan ID $loanId.";
            $_SESSION['admin_message'] = "Loan approved successfully.";
            $_SESSION['admin_message_type'] = 'success';
        } elseif (isset($_POST['reject'])) {
            $newStatus = 'rejected';
            $activityType = "loan rejection";
            $activity = "Rejected loan ID $loanId.";
            $_SESSION['admin_message'] = "Loan rejected successfully.";
            $_SESSION['admin_message_type'] = 'success';
        } else {
            $_SESSION['admin_message'] = "Invalid action.";
            $_SESSION['admin_message_type'] = 'error';
            header("Location: adminDashboard.php#loanApplicationReview");
            exit();
        }

        // Update loan status
        $updateStmt = $myconn->prepare("UPDATE loans SET status = ? WHERE loan_id = ?");
        $updateStmt->bind_param("si", $newStatus, $loanId);
        $updateStmt->execute();
        $updateStmt->close();

        // Log activity
        $activityStmt = $myconn->prepare("INSERT INTO activity (user_id, activity, activity_type, activity_time) 
                                        VALUES (?, ?, ?, NOW())");
        $activityStmt->bind_param("iss", $officerId, $activity, $activityType);
        $activityStmt->execute();
        $activityStmt->close();

        // Refresh pending loans
        $_SESSION['pending_loans'] = fetchAllLoans($myconn);
    } else {
        $_SESSION['admin_message'] = "Database error during verification.";
        $_SESSION['admin_message_type'] = 'error';
    }
    
    header("Location: adminDashboard.php#riskAssessment");
    exit();
}
?>
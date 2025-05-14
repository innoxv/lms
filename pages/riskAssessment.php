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

// Function to fetch all loans with optional risk level filter
function fetchAllLoans($conn, $filters) {
    $query = "SELECT loans.loan_id, loans.customer_id, loans.amount, loans.duration, 
                     loans.collateral_value, loans.collateral_description, loans.status, 
                     loans.risk_level 
              FROM loans";
    
    $params = [];
    if (!empty($filters['risk_level']) && in_array($filters['risk_level'], ['high', 'low', 'medium', 'unverified'])) {
        $query .= " WHERE loans.risk_level = ?";
        $params[] = $filters['risk_level'];
    }
    
    $query .= " ORDER BY loans.loan_id DESC";
    
    $stmt = $conn->prepare($query);
    if ($stmt && !empty($params)) {
        $stmt->bind_param("s", $params[0]);
    }
    
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

// Function to calculate risk level
function calculateRiskLevel($loanAmount, $collateralValue) {
    if ($loanAmount <= 0) {
        return 'unverified';
    }

    $percentageDifference = (($collateralValue - $loanAmount) / $loanAmount) * 100;

    if ($percentageDifference >= 50) {
        return 'low';
    } elseif ($percentageDifference >= 0) {
        return 'medium';
    } else {
        return 'high';
    }
}

// Handle evaluation form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['evaluate'])) {
    $loanId = intval($_POST['loan_id']);
    $officerId = $userId;

    // Verify loan exists
    $verifyQuery = "SELECT loans.amount, loans.collateral_value, loans.duration 
                    FROM loans 
                    WHERE loans.loan_id = ?";
    $stmt = $myconn->prepare($verifyQuery);
    $stmt->bind_param("i", $loanId);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            $_SESSION['admin_message'] = "Invalid loan selected for evaluation.";
            $_SESSION['admin_message_type'] = 'error';
            header("Location: adminDashboard.php#riskAssessment");
            exit();
        }

        $loan = $result->fetch_assoc();
        $stmt->close();

        // Calculate and update risk level
        $riskLevel = calculateRiskLevel($loan['amount'], $loan['collateral_value']);
        
        $updateStmt = $myconn->prepare("UPDATE loans SET risk_level = ? WHERE loan_id = ?");
        $updateStmt->bind_param("si", $riskLevel, $loanId);
        $updateStmt->execute();
        $updateStmt->close();

        // Log activity
        $activityStmt = $myconn->prepare("INSERT INTO activity (user_id, activity, activity_type, activity_time) 
                                        VALUES (?, ?, ?, NOW())");
        $activity = "Evaluated loan ID $loanId with risk level $riskLevel.";
        $activityType = "loan evaluation";
        $activityStmt->bind_param("iss", $officerId, $activity, $activityType);
        $activityStmt->execute();
        $activityStmt->close();

        $_SESSION['admin_message'] = "Loan evaluated and risk level set to $riskLevel.";
        $_SESSION['admin_message_type'] = 'success';
        $_SESSION['pending_loans'] = fetchAllLoans($myconn, $_SESSION['risk_filters'] ?? ['risk_level' => '']);
    }
    
    header("Location: adminDashboard.php#riskAssessment");
    exit();
}
<?php
session_start();
// Database connection
$conn = mysqli_connect('localhost', 'root', 'figureitout', 'LMSDB');
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Basic checks
if (!isset($_SESSION['user_id'])) {
    $_SESSION['loan_message'] = "Please login";
    $_SESSION['message_type'] = "error";
    header("Location: signin.html");
    exit;
}

$userId = $_SESSION['user_id'];
$loanId = $_GET['loan_id'] ?? null;
$statusFilter = $_GET['status'] ?? '';

if ($loanId) {
    // Single loan details
    $stmt = $conn->prepare("SELECT loans.*, loan_products.loan_type, lenders.name AS lender_name 
                         FROM loans 
                         JOIN loan_products ON loans.product_id = loan_products.product_id
                         JOIN lenders ON loans.lender_id = lenders.lender_id
                         WHERE loans.loan_id = ? AND loans.customer_id IN 
                         (SELECT customer_id FROM customers WHERE user_id = ?)");
    $stmt->bind_param("ii", $loanId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['loan_details'] = $result->fetch_assoc();
    } else {
        $_SESSION['loan_message'] = "Loan not found";
        $_SESSION['message_type'] = "error";
    }
    
    header("Location: customerDashboard.php#loanHistory");
    exit;
}

// All loans with optional status filter
$query = "SELECT loans.loan_id, loan_products.loan_type, lenders.name AS lender_name,
          loans.amount, loans.interest_rate, loans.status, loans.created_at
          FROM loans
          JOIN loan_products ON loans.product_id = loan_products.product_id
          JOIN lenders ON loans.lender_id = lenders.lender_id
          JOIN customers ON loans.customer_id = customers.customer_id
          WHERE customers.user_id = ?";

// Add status filter if specified and valid
$validStatuses = ['approved', 'pending', 'rejected'];
if (!empty($statusFilter) && in_array($statusFilter, $validStatuses)) {
    $query .= " AND loans.status = ?";
}

$query .= " ORDER BY loans.created_at DESC";

$stmt = $conn->prepare($query);
if (!empty($statusFilter)) {
    $stmt->bind_param("is", $userId, $statusFilter);
} else {
    $stmt->bind_param("i", $userId);
}

$stmt->execute();
$loans = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$_SESSION['loan_history'] = $loans;

if (empty($loans)) {
    $message = 'No loan history found';
    if (!empty($statusFilter)) {
        $message = "No $statusFilter loans found";
    }
    $_SESSION['loan_message'] = $message;
    $_SESSION['message_type'] = "info";
}

header("Location: customerDashboard.php#loanHistory");
exit;
?>
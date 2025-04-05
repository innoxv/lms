<?php
session_start();
header('Content-Type: application/json');

// Basic checks
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login']);
    exit;
}

$myconn = mysqli_connect('localhost', 'root', 'figureitout', 'LMSDB');
if (!$myconn) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

$userId = $_SESSION['user_id'];
$loanId = $_GET['loan_id'] ?? null;
$statusFilter = $_GET['status'] ?? ''; // Get status filter from URL

if ($loanId) {
    // Single loan details
    $stmt = $myconn->prepare("SELECT loans.*, loan_products.loan_type, lenders.name AS lender_name 
                             FROM loans 
                             JOIN loan_products ON loans.product_id = loan_products.product_id
                             JOIN lenders ON loans.lender_id = lenders.lender_id
                             WHERE loans.loan_id = ? AND loans.customer_id IN 
                             (SELECT customer_id FROM customers WHERE user_id = ?)");
    $stmt->bind_param("ii", $loanId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo $result->num_rows > 0 
        ? json_encode(['success' => true, 'loan' => $result->fetch_assoc()]) 
        : json_encode(['success' => false, 'message' => 'Loan not found']);
} else {
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
    
    $stmt = $myconn->prepare($query);
    if (!empty($statusFilter)) {
        $stmt->bind_param("is", $userId, $statusFilter);
    } else {
        $stmt->bind_param("i", $userId);
    }
    
    $stmt->execute();
    $loans = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $message = 'No loan history found';
    if (!empty($statusFilter)) {
        $message .= " with status '$statusFilter'";
    }
    
    echo json_encode([
        'success' => true,
        'loans' => $loans,
        'message' => empty($loans) ? $message : ''
    ]);
}
?>
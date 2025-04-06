<?php
session_start();
header('Content-Type: application/json');

// Database connection
$conn = mysqli_connect('localhost', 'root', 'figureitout', 'LMSDB');
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

try {
    // Validate user session
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("You must be logged in to perform this action");
    }

    // Validate loan ID
    if (!isset($_POST['loan_id']) || !is_numeric($_POST['loan_id'])) {
        throw new Exception("Invalid loan ID");
    }
    $loan_id = intval($_POST['loan_id']);
    $user_id = intval($_SESSION['user_id']);

    // Verify the loan belongs to the user and is deletable
    $loan_check = mysqli_query($conn,
        "SELECT loans.status 
        FROM loans
        JOIN customers ON loans.customer_id = customers.customer_id
        WHERE loans.loan_id = $loan_id
        AND customers.user_id = $user_id
        LIMIT 1");
    
    if (mysqli_num_rows($loan_check) === 0) {
        throw new Exception("Loan not found or you don't have permission to delete it");
    }

    $loan = mysqli_fetch_assoc($loan_check);
    $status = strtolower($loan['status']);
    


    // Delete the loan
    $delete_query = "DELETE FROM loans WHERE loan_id = $loan_id";
    if (!mysqli_query($conn, $delete_query)) {
        throw new Exception("Database error: " . mysqli_error($conn));
    }

    echo json_encode([
        'success' => true,
        'message' => 'Loan application deleted successfully'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    mysqli_close($conn);
}
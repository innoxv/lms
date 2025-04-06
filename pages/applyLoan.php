<?php
session_start();

// Set header for JSON response
header('Content-Type: application/json');

// Database connection
$conn = mysqli_connect('localhost', 'root', 'figureitout', 'LMSDB');
if (!$conn) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed'
    ]);
    exit();
}

try {
    // Validate user session
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("You must be logged in to apply for a loan");
    }

    // Required fields validation
    $required = [
        'product_id', 'lender_id', 'interest_rate',
        'amount', 'duration', 'collateral_value', 
        'collateral_description'
    ];
    
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Sanitize inputs
    $product_id = intval($_POST['product_id']);
    $lender_id = intval($_POST['lender_id']);
    $user_id = intval($_SESSION['user_id']);
    $amount = floatval($_POST['amount']);
    $interest_rate = floatval($_POST['interest_rate']);
    $duration = intval($_POST['duration']);
    $collateral_value = floatval($_POST['collateral_value']);
    $collateral_description = mysqli_real_escape_string($conn, $_POST['collateral_description']);
    $installments = isset($_POST['installments']) ? floatval($_POST['installments']) : 0;

    // Verify customer exists
    $customer_result = mysqli_query($conn, 
        "SELECT customer_id FROM customers WHERE user_id = $user_id LIMIT 1");
    
    if (!$customer_result || mysqli_num_rows($customer_result) === 0) {
        throw new Exception("Customer account not found");
    }
    $customer_id = mysqli_fetch_assoc($customer_result)['customer_id'];

    // Verify loan product belongs to lender
    $product_check = mysqli_query($conn,
        "SELECT 1 FROM loan_products 
        WHERE product_id = $product_id 
        AND lender_id = $lender_id 
        LIMIT 1");
    
    if (!$product_check || mysqli_num_rows($product_check) === 0) {
        throw new Exception("Invalid loan product for selected lender");
    }

    // Check for existing active loan of same type
    $existing_loan_check = mysqli_query($conn,
        "SELECT loans.status, loan_products.loan_type 
        FROM loans
        JOIN loan_products ON loans.product_id = loan_products.product_id
        WHERE loans.customer_id = $customer_id
        AND loans.product_id = $product_id
        AND loans.status != 'Paid'
        LIMIT 1");
    
    if (mysqli_num_rows($existing_loan_check) > 0) {
        $existing_loan = mysqli_fetch_assoc($existing_loan_check);
        throw new Exception("Loan is active, pay first to reapply.");
    }

    // Insert loan application
    $insert_query = "INSERT INTO loans (
        product_id, customer_id, lender_id,
        amount, interest_rate, duration,
        installments, collateral_description, collateral_value,
        status, created_at
    ) VALUES (
        $product_id, $customer_id, $lender_id,
        $amount, $interest_rate, $duration,
        $installments, '$collateral_description', $collateral_value,
        'pending', NOW()
    )";

    if (!mysqli_query($conn, $insert_query)) {
        throw new Exception("Database error: " . mysqli_error($conn));
    }

    // On success - return JSON response only
    echo json_encode([
        'success' => true,
        'message' => 'Application submitted successfully',
        'redirect' => 'customerDashboard.php#applyLoan'
    ]);
    exit();

} catch (Exception $e) {
    // Return error as JSON
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit();
} finally {
    if (isset($conn)) {
        mysqli_close($conn);
    }
}
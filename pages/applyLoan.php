<?php
session_start();
// Database config file
include '../phpconfig/config.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['loan_message'] = "Invalid request method";
    $_SESSION['message_type'] = "error";
    header("Location: customerDashboard.php#applyLoan");
    exit;
}

// Validate required fields
$required = [
    'offer_id', 'lender_id', 'interest_rate',
    'amount', 'duration', 'collateral_value', 
    'collateral_description', 'collateral_image'
];

$missing = [];
foreach ($required as $field) {
    if (empty($_POST[$field]) && ($field !== 'collateral_image' || empty($_FILES['collateral_image']['name']))) {
        $missing[] = $field;
    }
}

if (!empty($missing)) {
    $_SESSION['loan_message'] = "Missing required fields: " . implode(', ', $missing);
    $_SESSION['message_type'] = "error";
    header("Location: customerDashboard.php#applyLoan");
    exit;
}

// Sanitize inputs
$offer_id = intval($_POST['offer_id']);
$lender_id = intval($_POST['lender_id']);
$user_id = intval($_SESSION['user_id']);
$amount = floatval($_POST['amount']);
$interest_rate = floatval($_POST['interest_rate']);
$duration = intval($_POST['duration']);
$collateral_value = floatval($_POST['collateral_value']);
$collateral_description = $myconn->real_escape_string($_POST['collateral_description']);
$installments = isset($_POST['installments']) ? floatval($_POST['installments']) : 0;

// Handle image upload
$collateral_image = '';
if (!empty($_FILES['collateral_image']['name'])) {
    $target_dir = "../uploads/"; // Absolute path to uploads directory
    $target_file = $target_dir . basename($_FILES['collateral_image']['name']);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

    // Check if file is an image
    $check = getimagesize($_FILES['collateral_image']['tmp_name']);
    if ($check === false) {
        $_SESSION['loan_message'] = "File is not an image";
        $_SESSION['message_type'] = "error";
        header("Location: customerDashboard.php#applyLoan");
        exit;
    }

    // Check file size (limit to 2MB)
    if ($_FILES['collateral_image']['size'] > 2000000) {
        $_SESSION['loan_message'] = "File size too large (max 2MB)";
        $_SESSION['message_type'] = "error";
        header("Location: customerDashboard.php#applyLoan");
        exit;
    }

    // Allow only specific file types
    if (!in_array($imageFileType, $allowed_types)) {
        $_SESSION['loan_message'] = "Only JPG, JPEG, PNG, and GIF files are allowed";
        $_SESSION['message_type'] = "error";
        header("Location: customerDashboard.php#applyLoan");
        exit;
    }

    // Move file to server
    if (move_uploaded_file($_FILES['collateral_image']['tmp_name'], $target_file)) {
        $collateral_image = $target_file;
    } else {
        $_SESSION['loan_message'] = "Error uploading file: " . $_FILES['collateral_image']['error'];
        $_SESSION['message_type'] = "error";
        header("Location: customerDashboard.php#applyLoan");
        exit;
    }
} else {
    $_SESSION['loan_message'] = "Collateral image is required";
    $_SESSION['message_type'] = "error";
    header("Location: customerDashboard.php#applyLoan");
    exit;
}

// Verify customer exists
$customer_result = $myconn->query(
    "SELECT customer_id FROM customers WHERE user_id = $user_id LIMIT 1"
);

if (!$customer_result || $customer_result->num_rows === 0) {
    $_SESSION['loan_message'] = "Customer account not found";
    $_SESSION['message_type'] = "error";
    header("Location: customerDashboard.php#applyLoan");
    exit;
}

$customer_id = $customer_result->fetch_assoc()['customer_id'];

// Verify loan offer belongs to lender
$offer_check = $myconn->query(
    "SELECT 1 FROM loan_offers 
    WHERE offer_id = $offer_id 
    AND lender_id = $lender_id 
    LIMIT 1"
);

if (!$offer_check || $offer_check->num_rows === 0) {
    $_SESSION['loan_message'] = "Invalid loan offer for selected lender";
    $_SESSION['message_type'] = "error";
    header("Location: customerDashboard.php#applyLoan");
    exit;
}

// Check for existing active loan of same type
$existing_loan_check = $myconn->query(
    "SELECT status, loan_type 
    FROM loans
    JOIN loan_offers ON loans.offer_id = loan_offers.offer_id
    WHERE loans.customer_id = $customer_id
    AND loans.offer_id = $offer_id
    AND EXISTS (
        SELECT 1
        FROM payments
        WHERE payments.loan_id = loans.loan_id
        AND payments.customer_id = $customer_id
        AND payments.payment_date = (
            SELECT MAX(payment_date)
            FROM payments
            WHERE payments.loan_id = loans.loan_id
            AND payments.customer_id = $customer_id
        )
        AND payments.payment_type != 'full'
    )
    LIMIT 1"
);

if ($existing_loan_check && $existing_loan_check->num_rows > 0) {
    $existing_loan = $existing_loan_check->fetch_assoc();
    $_SESSION['loan_message'] = "Loan is already active, pay first to reapply.";
    $_SESSION['message_type'] = "error";
    header("Location: customerDashboard.php#applyLoan");
    exit;
}

// Check for unpaid loans (more than 2)
$unpaid_loans_query = "
    SELECT COUNT(DISTINCT loan_id) as unpaid_count
    FROM loans
    WHERE customer_id = $customer_id
    AND EXISTS (
        SELECT 1
        FROM payments
        WHERE payments.loan_id = loans.loan_id
        AND payments.customer_id = $customer_id
        AND payments.payment_date = (
            SELECT MAX(payment_date)
            FROM payments
            WHERE payments.loan_id = loans.loan_id
            AND payments.customer_id = $customer_id
        )
        AND payments.payment_type != 'full'
    )
";

$unpaid_loans_result = $myconn->query($unpaid_loans_query);

if (!$unpaid_loans_result) {
    $_SESSION['loan_message'] = "Error checking unpaid loans: " . $myconn->error;
    $_SESSION['message_type'] = "error";
    header("Location: customerDashboard.php#applyLoan");
    exit;
}

$unpaid_count = $unpaid_loans_result->fetch_assoc()['unpaid_count'];

if ($unpaid_count > 2) {
    $_SESSION['loan_message'] = "You have more than 2 unpaid loans. Settle them to apply.";
    $_SESSION['message_type'] = "error";
    header("Location: customerDashboard.php#applyLoan");
    exit;
}

// Insert loan application
$insert_query = "INSERT INTO loans (
    offer_id, customer_id, lender_id,
    amount, interest_rate, duration,
    installments, collateral_description, collateral_value, collateral_image,
    status, created_at
) VALUES (
    $offer_id, $customer_id, $lender_id,
    $amount, $interest_rate, $duration,
    $installments, '$collateral_description', $collateral_value, '$collateral_image',
    'submitted', NOW()  -- this is to ensure the admin approves the loan for the new status to be pending
)";

if ($myconn->query($insert_query)) {
    // ACTIVITY LOGGING 
    $loan_id = $myconn->insert_id;
    $activity_description = "Applied for loan, Loan ID $loan_id";
    $myconn->query(
        "INSERT INTO activity (user_id, activity, activity_time, activity_type)
        VALUES ($user_id, '$activity_description', NOW(), 'loan application')"
    );
    
    $_SESSION['loan_message'] = "Application submitted successfully";
    $_SESSION['message_type'] = "success";
} else {
    $_SESSION['loan_message'] = "Database error: " . $myconn->error;
    $_SESSION['message_type'] = "error";
}

header("Location: customerDashboard.php#applyLoan");
exit;
?>
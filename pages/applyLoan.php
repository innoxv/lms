<?php
session_start();
// Database connection
$conn = mysqli_connect('localhost', 'root', 'figureitout', 'LMSDB');
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

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
    'collateral_description'
];

$missing = [];
foreach ($required as $field) {
    if (empty($_POST[$field])) {
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
$collateral_description = $conn->real_escape_string($_POST['collateral_description']);
$installments = isset($_POST['installments']) ? floatval($_POST['installments']) : 0;

// Verify customer exists
$customer_result = $conn->query(
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
$offer_check = $conn->query(
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
$existing_loan_check = $conn->query(
    "SELECT loans.status, loan_offers.loan_type 
    FROM loans
    JOIN loan_offers ON loans.offer_id = loan_offers.offer_id
    WHERE loans.customer_id = $customer_id
    AND loans.offer_id = $offer_id
    AND loans.status != 'Paid'
    LIMIT 1"
);

if ($existing_loan_check && $existing_loan_check->num_rows > 0) {
    $existing_loan = $existing_loan_check->fetch_assoc();
    $_SESSION['loan_message'] = "Loan is already active, pay first to reapply.";
    $_SESSION['message_type'] = "error";
    header("Location: customerDashboard.php#applyLoan");
    exit;
}

// Insert loan application
$insert_query = "INSERT INTO loans (
    offer_id, customer_id, lender_id,
    amount, interest_rate, duration,
    installments, collateral_description, collateral_value,
    status, created_at
) VALUES (
    $offer_id, $customer_id, $lender_id,
    $amount, $interest_rate, $duration,
    $installments, '$collateral_description', $collateral_value,
    'pending', NOW()
)";

if ($conn->query($insert_query)) {
    // ACTIVITY LOGGING 
    $loan_id = $conn->insert_id;
    $activity_description = "Applied for loan, Loan ID $loan_id";
    $conn->query(
        "INSERT INTO activity (user_id, activity, activity_time, activity_type)
        VALUES ($user_id, '$activity_description', NOW(), 'loan application')"
    );
    
    $_SESSION['loan_message'] = "Application submitted successfully";
    $_SESSION['message_type'] = "success";
} else {
    $_SESSION['loan_message'] = "Database error: " . $conn->error;
    $_SESSION['message_type'] = "error";
}

header("Location: customerDashboard.php#applyLoan");
exit;
?>
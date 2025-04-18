<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
// Start the session
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Access Restrictions from Admin Functionality
require_once 'check_access.php';

// Database connection
$myconn = mysqli_connect('localhost', 'root', 'figureitout', 'LMSDB');
if (!$myconn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.html");
    exit();
}

$userId = $_SESSION['user_id'];

// USER DATA FETCHING

// Fetch user basic info
$userQuery = "SELECT user_name FROM users WHERE user_id = ?";
$stmt = $myconn->prepare($userQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$userResult = $stmt->get_result();
$user = $userResult->fetch_assoc() ?? ['user_name' => "Guest"];
$_SESSION['user_name'] = $user['user_name'];

// Fetch customer profile
$customerQuery = "SELECT customer_id, name, email, phone, address, bank_account, 
                 DATE_FORMAT(dob, '%Y-%m-%d') as dob, 
                 DATE_FORMAT(registration_date, '%Y-%m-%d') as registration_date, 
                 national_id 
                 FROM customers WHERE user_id = ?";
$stmt = $myconn->prepare($customerQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$customerResult = $stmt->get_result();
$customerProfile = $customerResult->fetch_assoc();

if (!$customerProfile) {
    $_SESSION['loan_message'] = "You are not registered as a customer.";
    header("Location: customerDashboard.php");
    exit();
}

$_SESSION['customer_id'] = $customerProfile['customer_id'];
$customer_id = $_SESSION['customer_id'];

// LOAN DATA FETCHING

// Status filter
$statusFilter = isset($_GET['status']) && in_array($_GET['status'], ['approved', 'pending', 'rejected']) 
    ? $_GET['status'] 
    : '';

// Fetch loan history
// Base query
$loansQuery = "SELECT 
    loans.loan_id,
    loan_products.loan_type,
    loans.amount,
    loans.interest_rate,
    loans.status AS loan_status,  
    lenders.name AS lender_name,
    DATE_FORMAT(loans.created_at, '%Y-%m-%d') as created_at
FROM loans
JOIN loan_products ON loans.product_id = loan_products.product_id
JOIN lenders ON loans.lender_id = lenders.lender_id
WHERE loans.customer_id = ?";

// Add filters dynamically
$params = [$customer_id];
$types = "i"; // Start with customer_id as integer

// Status filter
if ($statusFilter) {
    $loansQuery .= " AND loans.status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

// Loan type filter
if (isset($_GET['loan_type']) && $_GET['loan_type']) {
    $loansQuery .= " AND loan_products.loan_type = ?";
    $params[] = $_GET['loan_type'];
    $types .= "s";
}

// Date range filter
if (isset($_GET['date_range']) && $_GET['date_range']) {
    switch ($_GET['date_range']) {
        case 'today':
            $loansQuery .= " AND DATE(loans.created_at) = CURDATE()";
            break;
        case 'week':
            $loansQuery .= " AND YEARWEEK(loans.created_at, 1) = YEARWEEK(CURDATE(), 1)";
            break;
        case 'month':
            $loansQuery .= " AND MONTH(loans.created_at) = MONTH(CURDATE()) AND YEAR(loans.created_at) = YEAR(CURDATE())";
            break;
        case 'year':
            $loansQuery .= " AND YEAR(loans.created_at) = YEAR(CURDATE())";
            break;
    }
}

// Amount range filter
if (isset($_GET['amount_range']) && $_GET['amount_range']) {
    list($minAmount, $maxAmount) = explode('-', str_replace('+', '-', $_GET['amount_range']));
    $loansQuery .= " AND loans.amount >= ?";
    $params[] = $minAmount;
    $types .= "d";
    
    if (is_numeric($maxAmount)) {
        $loansQuery .= " AND loans.amount <= ?";
        $params[] = $maxAmount;
        $types .= "d";
    }
}

// Interest rate filter
if (isset($_GET['interest_rate']) && $_GET['interest_rate']) {
    list($minRate, $maxRate) = explode('-', str_replace('+', '-', $_GET['interest_rate']));
    $loansQuery .= " AND loans.interest_rate >= ?";
    $params[] = $minRate;
    $types .= "d";
    
    if (is_numeric($maxRate)) {
        $loansQuery .= " AND loans.interest_rate <= ?";
        $params[] = $maxRate;
        $types .= "d";
    }
}

// Add sorting
$loansQuery .= " ORDER BY loans.created_at DESC";

// Prepare and execute
$stmt = $myconn->prepare($loansQuery);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$loans = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Loan Details Handling 
if (isset($_GET['loan_id'])) {
    $loanId = $_GET['loan_id'];
    
    // First verify the loan belongs to the current customer
    $verifyQuery = "SELECT customer_id FROM loans WHERE loan_id = ?";
    $stmt = $myconn->prepare($verifyQuery);
    $stmt->bind_param("i", $loanId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['loan_message'] = "Loan not found";
        header("Location: customerDashboard.php#loanHistory");
        exit();
    }
    
    $loanData = $result->fetch_assoc();
    if ($loanData['customer_id'] != $customer_id) {
        $_SESSION['loan_message'] = "You don't have permission to view this loan";
        header("Location: customerDashboard.php#loanHistory");
        exit();
    }

    // Now fetch the full details with joins
    $loanDetailsQuery = "SELECT 
        loans.*,
        loan_products.loan_type,
        lenders.name AS lender_name,
        DATE_FORMAT(loans.created_at, '%Y-%m-%d') as created_date
    FROM loans
    JOIN loan_products ON loans.product_id = loan_products.product_id
    JOIN lenders ON loans.lender_id = lenders.lender_id
    WHERE loans.loan_id = ?";
    
    $stmt = $myconn->prepare($loanDetailsQuery);
    $stmt->bind_param("i", $loanId);
    $stmt->execute();
    $loanDetails = $stmt->get_result()->fetch_assoc();
    
    if ($loanDetails) {
        $_SESSION['loan_details'] = $loanDetails;
        header("Location: customerDashboard.php#loanHistory");
        exit();
    } else {
        $_SESSION['loan_message'] = "Failed to load loan details";
        header("Location: customerDashboard.php#loanHistory");
        exit();
    }
}

// METRICS AND CHARTS DATA

// Loan metrics
$metricsQuery = "SELECT 
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_loans,
    SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END) as total_borrowed,
    SUM(CASE WHEN status IN ('approved', 'disbursed', 'active') THEN amount ELSE 0 END) as outstanding_balance,
    MIN(CASE WHEN status = 'approved' THEN DATE_ADD(created_at, INTERVAL 1 MONTH) ELSE NULL END) as next_payment_date
FROM loans
WHERE customer_id = ?";

$stmt = $myconn->prepare($metricsQuery);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$metrics = $stmt->get_result()->fetch_assoc();

// Format metrics
$approvedLoans = $metrics['approved_loans'] ?? 0;
$totalBorrowed = $metrics['total_borrowed'] ?? 0;
$outstandingBalance = $metrics['outstanding_balance'] ?? 0;
$nextPaymentDate = $metrics['next_payment_date'] 
    ? date('j M', strtotime($metrics['next_payment_date'])) 
    : 'N/A';

// Loan types data for chart
$allLoanTypes = [
    "Personal Loan", "Business Loan", "Mortgage Loan", 
    "MicroFinance Loan", "Student Loan", "Construction Loan",
    "Green Loan", "Medical Loan", "Startup Loan", "Agricultural Loan"
];

$loanTypesQuery = "SELECT 
    loan_products.loan_type, 
    COUNT(*) as loan_count
FROM loans 
JOIN loan_products ON loans.product_id = loan_products.product_id
WHERE loans.customer_id = ?
AND loans.status IN ('approved', 'disbursed', 'active')
GROUP BY loan_products.loan_type";

$stmt = $myconn->prepare($loanTypesQuery);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$loanTypesResult = $stmt->get_result();

$loanCounts = array_fill_keys($allLoanTypes, 0);
while ($row = $loanTypesResult->fetch_assoc()) {
    if (array_key_exists($row['loan_type'], $loanCounts)) {
        $loanCounts[$row['loan_type']] = (int)$row['loan_count'];
    }
}

// Pie chart data
$statusQuery = "SELECT status, COUNT(*) as count 
                FROM loans 
                WHERE customer_id = ? 
                GROUP BY status";
$stmt = $myconn->prepare($statusQuery);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$statusResult = $stmt->get_result();

$statusData = [];
$totalLoans = 0;
while ($row = $statusResult->fetch_assoc()) {
    $statusData[$row['status']] = (int)$row['count'];
    $totalLoans += (int)$row['count'];
}

// Calculate percentages for pie chart
$pieData = [
    'pending' => isset($statusData['pending']) ? ($statusData['pending'] / $totalLoans * 100) : 0,
    'approved' => isset($statusData['approved']) ? ($statusData['approved'] / $totalLoans * 100) : 0,
    'rejected' => isset($statusData['rejected']) ? ($statusData['rejected'] / $totalLoans * 100) : 0
];

// MESSAGES HANDLING
// Clear any existing messages if they were shown
if (isset($_SESSION['loan_application_message_shown'])) {
    unset($_SESSION['loan_message']);
    unset($_SESSION['loan_application_message_shown']);
}

if (isset($_SESSION['loan_details_message_shown'])) {
    unset($_SESSION['loan_details_message']);
    unset($_SESSION['loan_details_message_shown']);
}

if (isset($_SESSION['profile_message_shown'])) {
    unset($_SESSION['profile_message']);
    unset($_SESSION['profile_message_type']);
    unset($_SESSION['profile_message_shown']);
}

// Close connection
// mysqli_close($myconn);
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <main>
        <div class="header">
            <div class="header2">
                <div class="logo">LMS</div>
            </div>
            <div class="header3">
                <ul>
                    <li><a href="logoutbtn.php" id="logout"class="no-col">Log Out</a></li>
                </ul>
            </div>
        </div>
        <div class="customer-content">
            <div class="nav">
                <ul class="nav-split">
                    <div class="top">
                        <li><a href="#dashboard" id="dashboardLink">Dashboard</a></li>
                        <li>
                            <a href="#applyLoan" id="applyLoanLink"
                            class="<?php echo ($status === 'restricted_apply') ? 'disabled-link' : '' ?>">
                            Apply for Loan
                            </a>
                        </li>
                        <li><a href="#loanHistory" id="loanHistoryLink">Loan History</a></li>
                        <li class="disabled-link"><a href="" id="">Payment Tracking</a></li> <!-- this is still in production -->
                        <li class="disabled-link"><a href="#notifications">Notifications</a></li>  <!-- this is still in production -->
                        <li><a href="#profile">Profile</a></li>
                    </div>
                    <div class="bottom">
                        <li><a href="#feedback">Feedback</a></li>
                        <li><a href="#contactSupport">Contact Support</a></li>
                    </div>
                </ul>
            </div>

            <!-- Dynamic display enabled by CSS -->
            <div class="display">
                <!-- Apply for Loan -->
                <div id="applyLoan" class="margin">
                    <div>
                        <h1>Apply for Loan</h1>
                        <p>Find a suitable Lender and fill out the form to apply for a new loan.</p>
                    </div>
                    <div class="loan-right">
                        <div class="loan-filter">
                            <p style="color: whitesmoke; font-weight: 900; line-height: 1;">Filters</p>
                            <form method="GET" action="fetchLenders.php">
                                <div>
                                    <ul>
                                    <li>
                                        <p>Loan Type</p>
                                        <span>
                                        <input type="checkbox" name="loan_type[]" value="Personal Loan" id="personal">
                                        <label for="personal">Personal</label>
                                        </span>
                                        <span>
                                        <input type="checkbox" name="loan_type[]" value="Business Loan" id="business">
                                        <label for="business">Business</label>
                                        </span>
                                        <span>
                                        <input type="checkbox" name="loan_type[]" value="Mortgage Loan" id="mortgage">
                                        <label for="mortgage">Mortgage</label>
                                        </span>
                                        <span>
                                        <input type="checkbox" name="loan_type[]" value="MicroFinance Loan" id="microfinance">
                                        <label for="microfinance">MicroFinance</label>
                                        </span>
                                        <span>
                                        <input type="checkbox" name="loan_type[]" value="Student Loan" id="student">
                                        <label for="student">Student</label>
                                        </span>
                                        <span>
                                        <input type="checkbox" name="loan_type[]" value="Construction Loan" id="construction">
                                        <label for="construction">Construction</label>
                                        </span>
                                        <span>
                                        <input type="checkbox" name="loan_type[]" value="Green Loan" id="green">
                                        <label for="green">Green</label>
                                        </span>
                                        <span>
                                        <input type="checkbox" name="loan_type[]" value="Medical Loan" id="medical">
                                        <label for="medical">Medical</label>
                                        </span>
                                        <span>
                                        <input type="checkbox" name="loan_type[]" value="Startup Loan" id="startup">
                                        <label for="startup">Startup</label>
                                        </span>
                                        <span>
                                        <input type="checkbox" name="loan_type[]" value="Agricultural Loan" id="agricultural">
                                        <label for="agricultural">Agricultural</label>
                                        </span>
                                    </li>
                                    <li>
                                        <p>Amount Range (sh)</p>
                                        <span class="range">
                                        <div>
                                            <input type="text" name="min_amount" placeholder="500" min="500">
                                            <span>-</span>
                                            <input type="text" name="max_amount" placeholder="100000" min="500" >
                                        </div>
                                        <div>
                                            <div class="quick-amounts">
                                            <button class="one" type="button" data-min="1000" data-max="5000">1k-5k</button>
                                            <button class="two" type="button" data-min="5000" data-max="20000">5k-20k</button>
                                            <button class="three" type="button" data-min="20000" data-max="100000">20k-100k</button>
                                            </div>
                                        </div>
                                        </span>
                                    </li>
                                    <li>
                                        <p>Interest Rates</p>
                                        <span>
                                        <input type="radio" name="interest_range[]" value="0-5" id="0-5">
                                        <label for="0-5">0 - 5%</label>
                                        </span>
                                        <span>
                                        <input type="radio" name="interest_range[]" value="5-10" id="5-10">
                                        <label for="5-10">5 - 10%</label>
                                        </span>
                                        <span>
                                        <input type="radio" name="interest_range[]" value="10+" id="10+">
                                        <label for="10+">10% +</label>
                                        </span>
                                    </li>
                                    <li>
                                        <div class="subres">
                                        <button class="sub" type="submit">Apply Filters</button>
                                        <button class="res" type="reset">Reset</button>
                                        </div>
                                        
                                    </li>
                                    </ul>
                                </div>
                                </form>
                        </div>
                        
                        <!-- Loan Lenders display and filter functionality -->
                        <div class="loan-lenders" id="lendersContainer">
                        <?php
                            if (isset($_SESSION['filters_applied']) && $_SESSION['filters_applied']): 
                                // Filtered view
                                if (!empty($_SESSION['filtered_lenders'])): ?>
                                    <?php foreach ($_SESSION['filtered_lenders'] as $lender): ?>
                                        <div class="lender">
                                            <span><?= $lender['name'] ?></span>
                                            <span><?= $lender['type'] ?></span>
                                            <span>Rate: <?= $lender['rate'] ?>%</span>
                                            <span>Max Amt: <?= number_format($lender['amount']) ?></span>
                                            <span>Max Dur: <?= $lender['duration'] ?> months</span>
                                            <button class="applynow" 
                                                data-product="<?= $lender['product_id'] ?>"
                                                data-lender="<?= $lender['lender_id'] ?>"
                                                data-rate="<?= $lender['rate'] ?>"
                                                data-name="<?= $lender['name'] ?>"
                                                data-type="<?= $lender['type'] ?>"
                                                data-maxamount="<?= $lender['amount'] ?>"
                                                data-maxduration="<?= $lender['duration'] ?>">
                                                Apply Now
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="no-results">
                                        No lenders match your current filters.
                                    </div>
                                <?php endif; ?>
                            <?php else: 

                            // Default view - show all lenders
                            $query = "SELECT loan_products.*, lenders.name AS lender_name
                                    FROM loan_products
                                    JOIN lenders ON loan_products.lender_id = lenders.lender_id
                                    ORDER BY loan_products.product_id DESC";
                            $result = $myconn->query($query);
            
                            if ($result && $result->num_rows > 0): ?>
                                <?php while ($lender = $result->fetch_assoc()): ?>
                                    <div class="lender">
                                        <span><?= htmlspecialchars($lender['lender_name']) ?></span>
                                        <span><?= htmlspecialchars($lender['loan_type']) ?></span>
                                        <span>Rate: <?= $lender['interest_rate'] ?>%</span>
                                        <span>Max Amt: <?= number_format($lender['max_amount']) ?></span>
                                        <span>Max Dur: <?= $lender['max_duration'] ?> months</span>
                                        <button class="applynow" 
                                            data-product="<?= $lender['product_id'] ?>"
                                            data-lender="<?= $lender['lender_id'] ?>"
                                            data-rate="<?= $lender['interest_rate'] ?>"
                                            data-name="<?= htmlspecialchars($lender['lender_name']) ?>"
                                            data-type="<?= htmlspecialchars($lender['loan_type']) ?>"
                                            data-maxamount="<?= $lender['max_amount'] ?>"
                                            data-maxduration="<?= $lender['max_duration'] ?>">
                                            Apply Now
                                        </button>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="error">No lenders currently available in the system</div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                        <!-- Loan Application Popup -->
                        <div class="popup-overlay2" id="loanPopup" style="display: none;">
                            <div class="popup-content">
                                <h2>Loan Application</h2>
                        
                        <?php
                            // Display messages
                            if (isset($_SESSION['loan_message'])): ?>
                                <div class="alert <?= $_SESSION['message_type'] ?? 'info' ?>">
                                    <?= htmlspecialchars($_SESSION['loan_message']) ?>
                                </div>
                                <?php 
                                unset($_SESSION['loan_message']);
                                unset($_SESSION['message_type']);
                            endif; ?>
                        
                                
                            <!-- Loan Application Form -->
                            <form id="loanApplicationForm" action="applyLoan.php" method="post">
                                <!-- Hidden fields for submission -->
                                <div class="form-group">
                                        <input type="hidden" id="productId" name="product_id">
                                        <input type="hidden" id="lenderId" name="lender_id">
                                        <input type="hidden" id="interestRate" name="interest_rate">
                                </div>
                                 

                                <!-- Visible lender information -->
                                <div class="form-group2">
                                    <label>Lender:</label>
                                    <div id="displayLenderName" class="display-info"></div>
                                </div>
                                
                                <div class="form-group2">
                                    <label>Loan Type:</label>
                                    <div id="displayType" class="display-info"></div>
                                </div>
                                <div class="form-group2">
                                    <label>Interest Rate:</label>
                                    <div id="displayInterestRate" class="display-info"></div>
                                </div>
                                
                                <div class="form-group2">
                                    <label>Maximum Amount:</label>
                                    <div id="displayMaxAmount" class="display-info"></div>
                                </div>
                                
                                <div class="form-group2">
                                    <label>Maximum Duration:</label>
                                    <div id="displayMaxDuration" class="display-info"></div>
                                </div>
                                
                                <!-- User input fields -->
                                <div class="form-group">
                                    <label for="amountNeeded">Amount Needed (KES):*</label>
                                    <input type="text" id="amountNeeded" name="amount" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="duration">Duration (months):*</label>
                                    <input type="text" id="duration" name="duration" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="installments">Monthly Installment (KES):</label>
                                    <input style="border-bottom: none;" type="text" id="installments" name="installments" placeholder="auto-calculated" readonly>
                                </div>
                                
                                <div class="form-group">
                                    <label for="collateralValue">Collateral Value (KES):*</label>
                                    <input type="text" id="collateralValue" name="collateral_value" required >
                                </div>
                                
                                <div class="form-group">
                                    <label for="collateralDesc">Collateral Description:*</label>
                                    <textarea id="collateralDesc" name="collateral_description" placeholder="enter a short description" required></textarea>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="button" class="cancel-btn" id="cancelBtn">Cancel</button>
                                    <button type="submit" class="submit-btn">Submit Application</button>
                                </div>
                            </form>
                            </div>
                        </div>   
                    </div>   
                </div>

                
                <!-- Loan History -->
                <div id="loanHistory" class="margin">
                    <h1>Loan History</h1>
                    <p>View your loan history</p>
                   <!-- Loan Status Filter -->
                   <div class="loan-filter-container">
    <form method="get" action="#loanHistory">
        <div class="filter-row">
            <div class="filter-group">
                <label for="status">Status:</label>
                <select name="status" id="status" onchange="this.form.submit()">
                    <option value="">All Loans</option>
                    <option value="pending" <?= ($statusFilter === 'pending') ? 'selected' : '' ?>>Pending</option>
                    <option value="approved" <?= ($statusFilter === 'approved') ? 'selected' : '' ?>>Approved</option>
                    <option value="rejected" <?= ($statusFilter === 'rejected') ? 'selected' : '' ?>>Rejected</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="loan_type">Type:</label>
                <select name="loan_type" id="loan_type" onchange="this.form.submit()">
                    <option value="">All Types</option>
                    <?php foreach ($allLoanTypes as $type): ?>
                        <option value="<?= $type ?>" <?= (isset($_GET['loan_type']) && $_GET['loan_type'] === $type) ? 'selected' : '' ?>>
                            <?= $type ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>


            <div class="filter-group">
                <label for="date_range">Date Range:</label>
                <select name="date_range" id="date_range" onchange="this.form.submit()">
                    <option value="">All Time</option>
                    <option value="today" <?= (isset($_GET['date_range']) && $_GET['date_range'] === 'today') ? 'selected' : '' ?>>Today</option>
                    <option value="week" <?= (isset($_GET['date_range']) && $_GET['date_range'] === 'week') ? 'selected' : '' ?>>This Week</option>
                    <option value="month" <?= (isset($_GET['date_range']) && $_GET['date_range'] === 'month') ? 'selected' : '' ?>>This Month</option>
                    <option value="year" <?= (isset($_GET['date_range']) && $_GET['date_range'] === 'year') ? 'selected' : '' ?>>This Year</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="amount_range">Amount Range:</label>
                <select name="amount_range" id="amount_range" onchange="this.form.submit()">
                    <option value="">Any Amount</option>
                    <option value="0-5000" <?= (isset($_GET['amount_range']) && $_GET['amount_range'] === '0-5000') ? 'selected' : '' ?>>0 - 5,000</option>
                    <option value="5000-20000" <?= (isset($_GET['amount_range']) && $_GET['amount_range'] === '5000-20000') ? 'selected' : '' ?>>5,000 - 20,000</option>
                    <option value="20000-50000" <?= (isset($_GET['amount_range']) && $_GET['amount_range'] === '20000-50000') ? 'selected' : '' ?>>20,000 - 50,000</option>
                    <option value="50000-100000" <?= (isset($_GET['amount_range']) && $_GET['amount_range'] === '50000-100000') ? 'selected' : '' ?>>50,000 - 100,000</option>
                    <option value="100000+" <?= (isset($_GET['amount_range']) && $_GET['amount_range'] === '100000+') ? 'selected' : '' ?>>100,000+</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="interest_rate">Interest Rate:</label>
                <select name="interest_rate" id="interest_rate" onchange="this.form.submit()">
                    <option value="">Any</option>
                    <option value="0-5" <?= (isset($_GET['interest_rate']) && $_GET['interest_rate'] === '0-5') ? 'selected' : '' ?>>0-5%</option>
                    <option value="5-10" <?= (isset($_GET['interest_rate']) && $_GET['interest_rate'] === '5-10') ? 'selected' : '' ?>>5-10%</option>
                    <option value="10+" <?= (isset($_GET['interest_rate']) && $_GET['interest_rate'] === '10+') ? 'selected' : '' ?>>10%+</option>
                </select>
            </div>
            
            <div class="filter-actions">
                <a href="customerDashboard.php#loanHistory"><button type="button" class="reset">Reset</button></a>
            </div>

        </div>
        
    </form>
</div>
                    <!-- Loan History -->
                    <div class="loanhistory" id="loanHistoryContainer">
                        <?php if (empty($loans)): ?>
                            <div class="no-loans">No loan history found</div>
                        <?php else: ?>
                            <table class="simple-loan-table">
                                <thead>
                                    <tr>
                                        <th>Loan ID</th>
                                        <th>Type</th>
                                        <th>Lender</th>
                                        <th>Amount (KES)</th>
                                        <th>Interest</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($loans as $loan): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($loan['loan_id']) ?></td>
                                            <td><?= htmlspecialchars($loan['loan_type']) ?></td>
                                            <td><?= htmlspecialchars($loan['lender_name']) ?></td>
                                            <td><?= number_format($loan['amount']) ?></td>
                                            <td><?= htmlspecialchars($loan['interest_rate']) ?>%</td>
                                            <td>
                                                <span class="loan-status <?= strtolower($loan['loan_status']) ?>">
                                                    <?= htmlspecialchars($loan['loan_status']) ?>
                                                </span>
                                            </td>
                                            <td><?= date('j M Y', strtotime($loan['created_at'])) ?></td>
                                            <td>
                                                <button class="view-btn" onclick="showLoanDetails(<?= $loan['loan_id'] ?>)">
                                                    View
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Loan Details Popup -->
                    <div id="loanDetailsPopup" class="popup-overlay3" style="display: <?= isset($_SESSION['loan_details']) ? 'flex' : 'none' ?>;">
                        <div class="popup-content3">
                            <h2>Loan Details for ID <span id="popupLoanId"><?= $_SESSION['loan_details']['loan_id'] ?? '' ?></span></h2>
                            <button id="closePopupBtn" class="close-btn">&times;</button>
                    
                            <?php if (isset($_SESSION['loan_details_message'])): ?>
                                <div class="alert <?= $_SESSION['loan_details_message_type'] ?? 'info' ?>">
                                    <?= htmlspecialchars($_SESSION['loan_details_message']) ?>
                                </div>
                                <?php $_SESSION['loan_details_message_shown'] = true; ?>
                                <script>
                                    setTimeout(() => {
                                        document.querySelector('#loanDetailsPopup .alert').style.display = 'none';
                                    }, 2000);
                                </script>
                            <?php endif; ?>
                            
                            <div id="loanDetailsContent" class="popup-body">
                                <?php if (isset($_SESSION['loan_details'])): ?>
                                    <div class="loan-details-grid">
                                        <div class="detail-row">
                                            <span class="detail-label">Loan Type:</span>
                                            <span class="detail-value"><?= $_SESSION['loan_details']['loan_type'] ?></span>
                                        </div>
                                        <div class="detail-row">
                                            <span class="detail-label">Lender:</span>
                                            <span class="detail-value"><?= $_SESSION['loan_details']['lender_name'] ?></span>
                                        </div>
                                        <div class="detail-row">
                                            <span class="detail-label">Amount:</span>
                                            <span class="detail-value">KES <?= $_SESSION['loan_details']['amount'] ?></span>
                                        </div>
                                        <div class="detail-row">
                                            <span class="detail-label">Interest Rate:</span>
                                            <span class="detail-value"><?= $_SESSION['loan_details']['interest_rate'] ?>%</span>
                                        </div>
                                        <div class="detail-row">
                                            <span class="detail-label">Duration:</span>
                                            <span class="detail-value"><?= $_SESSION['loan_details']['duration'] ?> months</span>
                                        </div>
                                        <div class="detail-row">
                                            <span class="detail-label">Monthly Installment:</span>
                                            <span class="detail-value">KES <?= $_SESSION['loan_details']['installments'] ?></span>
                                        </div>
                                        <div class="detail-row">
                                            <span class="detail-label">Collateral Value:</span>
                                            <span class="detail-value">KES <?= $_SESSION['loan_details']['collateral_value'] ?></span>
                                        </div>
                                        <div class="detail-row">
                                            <span class="detail-label">Collateral Description:</span>
                                            <span class="detail-value"><?= $_SESSION['loan_details']['collateral_description'] ?></span>
                                        </div>
                                        <div class="detail-row">
                                            <span class="detail-label">Status:</span>
                                            <span class="detail-value loan-status <?= strtolower($_SESSION['loan_details']['status']) ?>">
                                                <?= $_SESSION['loan_details']['status'] ?>
                                            </span>
                                        </div>
                                        <div class="detail-row">
                                            <span class="detail-label">Application Date:</span>
                                            <span class="detail-value"><?= $_SESSION['loan_details']['created_at'] ?></span>
                                        </div>
                                        
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div id="loanActionButtons" class="popup-actions">
                                <?php if (isset($_SESSION['loan_details']) && in_array(strtolower($_SESSION['loan_details']['status']), ['pending', 'rejected'])): ?>
                                    <form action="deleteApplication.php" method="post" class="delete-form">
                                        <input type="hidden" name="loan_id" value="<?= $_SESSION['loan_details']['loan_id'] ?>">
                                        <button type="submit" class="delete-btn" onclick="return confirm('Are you sure you want to delete this application?')">
                                            Delete Application
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                            </div>
                        </div>
                    </div>
                    
                    <?php 
                    // Clear the loan details from session after display
                    if (isset($_SESSION['loan_details'])) {
                        unset($_SESSION['loan_details']);
                    }
                    ?>
                </div>

                <!-- Notifications -->
                <div id="notifications" class="margin">
                    <h1>Notifications</h1>
                    <p>View your alerts and reminders.</p>
                </div>

                <!-- Profile -->
                <div id="profile" class="margin">
                    <h1>Profile</h1>
                    <p>View and update your personal information.</p>
                    
                    <div class="profile-container">
                        <div class="profile-details">
                        <h2>Personal Information</h2>
                            <div class="profile-row">
                                <span class="profile-label">Full Name:</span>
                                <span class="profile-value"><?php echo htmlspecialchars($customerProfile['name']); ?></span>
                            </div>
                            <div class="profile-row">
                                <span class="profile-label">Member Since:</span>
                                <span class="profile-value"><?php echo date('j M Y', strtotime($customerProfile['registration_date'])); ?></span>
                            </div>
                            <div class="profile-row">
                                <span class="profile-label">Email:</span>
                                <span class="profile-value"><?php echo htmlspecialchars($customerProfile['email']); ?></span>
                            </div>
                            <div class="profile-row">
                                <span class="profile-label">Phone:</span>
                                <span class="profile-value"><?php echo htmlspecialchars($customerProfile['phone']); ?></span>
                            </div>
                            <div class="profile-row">
                                <span class="profile-label">Date of Birth:</span>
                                <span class="profile-value"><?php echo date('j M Y', strtotime($customerProfile['dob'])); ?></span>
                            </div>
                            <div class="profile-row">
                                <span class="profile-label">National ID:</span>
                                <span class="profile-value"><?php echo htmlspecialchars($customerProfile['national_id']); ?></span>
                            </div>
                            <div class="profile-row">
                                <span class="profile-label">Address:</span>
                                <span class="profile-value"><?php echo htmlspecialchars($customerProfile['address']); ?></span>
                            </div>
                            <div class="profile-row">
                                <span class="profile-label">Bank Account:</span>
                                <span class="profile-value"><?php echo htmlspecialchars($customerProfile['bank_account']); ?></span>
                            </div>
                            
                            <button id="editProfileBtn" class="edit-btn">Edit Profile</button>
                
                        </div>
                        <div class="additional-settings">
                            <h2>Additional Settings</h2>
                            <p class="change">Change Password</p>
                            <p class="delete">Delete Account</p>
                        </div>
                
                    </div>
                    
                </div>
                
                <!-- Profile Edit Overlay -->
                <div class="popup-overlay3" id="profileOverlay">
                    
                    <div class="popup-content3">
                    <!-- Message container -->
                    <div id="profileMessage" class="message-container">
                        <?php if (isset($_SESSION['profile_message'])): ?>
                            <div class="alert <?= $_SESSION['profile_message_type'] ?? 'info' ?>">
                                <?= htmlspecialchars($_SESSION['profile_message']) ?>
                            </div>
                            <?php 
                                unset($_SESSION['profile_message']);
                                unset($_SESSION['profile_message_type']);
                            ?>
                        <?php endif; ?>
                    </div>

                        <h2>Edit Profile</h2>
                        <form id="profileEditForm" action="custUpdateProfile.php" method="post">
                            <input type="hidden" name="customer_id" value="<?php echo $customer_id; ?>">
                            
                            <div class="form-group">
                                <label for="editName">Full Name</label>
                                <input type="text" id="editName" name="name" value="<?php echo htmlspecialchars($customerProfile['name']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="editEmail">Email</label>
                                <input type="email" id="editEmail" name="email" value="<?php echo htmlspecialchars($customerProfile['email']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="editPhone">Phone</label>
                                <input type="tel" id="editPhone" name="phone" value="<?php echo htmlspecialchars($customerProfile['phone']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="editAddress">Address</label>
                                <input id="editAddress" name="address" value="<?php echo htmlspecialchars($customerProfile['address']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="editBankAccount">Bank Account</label>
                                <input type="text" id="editBankAccount" name="bank_account" value="<?php echo htmlspecialchars($customerProfile['bank_account']); ?>">
                            </div>
                            
                            <div class="form-actions">
                                <button type="button" id="cancelEditBtn" class="cancel-btn">Cancel</button>
                                <button type="submit" class="save-btn">Save Changes</button>
                            </div>
                        </form>
                    </div>
                    
                </div>
                
                <!-- Feedback -->
                <div id="feedback" class="margin">
                    <h1>Feedback</h1>
                    <p>Share your feedback with us.</p>
                </div>

                <!-- Contact Support -->
                <div id="contactSupport" class="margin">
                    <h1>Contact Support</h1>
                    <p>Reach out to our support team for assistance.</p>
                </div>

                <!-- Dashboard -->
                <div id="dashboard" >
                    <div class="dash-header">
                        <div>
                            <h1>Customer's Dashboard</h1>
                            <p>Overview of your loans and financial status.</p>
                        </div>
                        <div class="greeting">
                            <p>
                                <code>
                                <!-- Greeting based on time -->
                                <?php
                                    // Set the timezone to Nairobi, Kenya
                                    date_default_timezone_set('Africa/Nairobi');
                                    // 24-hour format
                                    $currentTime = date("H");
                                    $message = "";
                                    if ($currentTime < 12) {
                                        $message = "good morning,";
                                    } elseif ($currentTime < 18) {
                                        $message = "good afternoon,";
                                    } else {
                                        $message = "good evening,";
                                    }
                                    echo "<span>$message</span>";
                                    ?>
                                <!-- Display the user's name -->
                                <span class="span"><?php echo $_SESSION['user_name']; ?>!</span>
                                </code>
                            </p>
                        </div>
                    </div>
                    <div class="metrics">
                    <div>
                        <p>Active Loans</p>
                        <div class="metric-value-container">
                        <span class="span-2"><?php echo $approvedLoans; ?></span>
                        </div>
                    </div>
                    <div>
                        <p>Total Amounts Borrowed</p>
                        <div class="metric-value-container">
                        <span class="span-2"><?php echo number_format($totalBorrowed); ?></span>
                        </div>
                    </div>
                    <div>
                        <p>Outstanding Balance</p>
                        <div class="metric-value-container">
                        <span class="span-2"><?php echo number_format($outstandingBalance); ?></span>
                        </div>
                    </div>
                    <div>
                        <p>Next Payment Date</p>
                        <div class="metric-value-container">
                        <span class="span-2"><?php echo $nextPaymentDate; ?></span>
                            
                        </div>
                    </div>
                </div>

                <div class="visuals">
                    <div>
                        <p>Number of Active Loans per Loan Type</p>
                        <canvas id="barChart" width="800" height="300"></canvas>
                    </div>
                    <div>
                        <p>Loan Status</p>
                        <canvas id="pieChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
        

        <!-- Copyright -->
        <div class="copyright">
            <p><?php
                $currentYear = date("Y");
                echo "&copy; $currentYear";
                ?>
                <a href="mailto:innocentmukabwa@gmail.com">dev</a>
            </p>
        </div>


                
</main>



<script>

// Initializations

document.addEventListener('DOMContentLoaded', function() {
    // Initialize metrics font size adjustment
    adjustMetricsFontSize();
    
    // Initialize charts
    initializeBarChart();
    initializePieChart();
    
    // Set up event listeners
    setupEventListeners();
    
    // Handle any existing messages
    handleMessages();
    
    // Initialize popup functionality
    initPopups();
    
    // Loan Application Messages Handling -shows message before pop up disappears
    const popup = document.getElementById('loanPopup');
    const alert = popup?.querySelector('.alert');

    if (popup && alert && alert.textContent.trim() !== '') {
        popup.style.display = 'flex';
        document.body.classList.add('popup-open');

        // Fade out after 3 seconds
        setTimeout(() => {
            popup.style.opacity = '0';

            setTimeout(() => {
                popup.style.display = 'none';
                popup.style.opacity = '';
                document.body.classList.remove('popup-open');
            }, 500);
        }, 3000);
    }

    // Profile Messages
    const profileOverlay = document.getElementById('profileOverlay');
    const profileAlert = profileOverlay?.querySelector('.alert');
    
    if (profileOverlay && profileAlert && profileAlert.textContent.trim() !== '') {
        profileOverlay.style.display = 'flex';
        document.body.classList.add('popup-open');
    
        // Fade out alert after 3 seconds
        setTimeout(() => {
            profileAlert.style.opacity = '0';
    
            setTimeout(() => {
                profileAlert.style.display = 'none';
                profileAlert.style.opacity = '';
                profileOverlay.style.display = 'none';
                document.body.classList.remove('popup-open');
            }, 500);
        }, 3000);
    }

});
    


// POPUP MANAGEMENT

function initPopups() {
    // Close buttons
    document.querySelectorAll('.popup-close, .cancel-btn').forEach(btn => {
        btn.addEventListener('click', closeAllPopups);
    });

    // Close when clicking outside content
    document.querySelectorAll('.popup-overlay').forEach(popup => {
        popup.addEventListener('click', function(e) {
            if (e.target === this) closeAllPopups();
        });
    });


    // Apply Now buttons
    document.querySelectorAll('.applynow').forEach(btn => {
        btn.addEventListener('click', function() {
            // Get lender data from button attributes
            document.getElementById('productId').value = this.dataset.product;
            document.getElementById('lenderId').value = this.dataset.lender;
            document.getElementById('interestRate').value = this.dataset.rate;
            
            // Update display fields
            document.getElementById('displayLenderName').textContent = this.dataset.name;
            document.getElementById('displayType').textContent = this.dataset.type;
            document.getElementById('displayInterestRate').textContent = this.dataset.rate + '%';
            document.getElementById('displayMaxAmount').textContent = numberWithCommas(this.dataset.maxamount);
            document.getElementById('displayMaxDuration').textContent = this.dataset.maxduration + ' months';
            
            // Show popup
            document.getElementById('loanPopup').style.display = 'flex';
            document.body.classList.add('popup-open');
        });
    });

    // View buttons
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const loanId = this.closest('tr').getAttribute('data-loan-id') || 
                          this.getAttribute('data-loan-id') ||
                          this.getAttribute('onclick').match(/showLoanDetails\((\d+)\)/)[1];
            
            // Redirect to same page with loan_id parameter
            window.location.href = 'customerDashboard.php?loan_id=' + loanId + '#loanHistory';  // theres a bug here, It refreshes the filtered
        });
    });

    // Profile edit button
    document.getElementById('editProfileBtn')?.addEventListener('click', function() {
        document.getElementById('profileOverlay').style.display = 'flex';
        document.body.classList.add('popup-open');
    });

    // PHP-triggered popups
    <?php if (isset($_SESSION['loan_message'])): ?>
        document.getElementById('loanPopup').style.display = 'flex';
        document.body.classList.add('popup-open');
        <?php unset($_SESSION['loan_message']); ?>
    <?php endif; ?>
}

function closeAllPopups() {
    document.querySelectorAll('.popup-overlay').forEach(popup => {
        popup.style.display = 'none';
    });
    document.body.classList.remove('popup-open');
}

function numberWithCommas(x) {
    return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

function confirmDelete() {
    return confirm('Are you sure you want to delete this application?');
}

// METRICS AND CHART FUNCTIONS

function adjustMetricsFontSize() {
    const metricValues = document.querySelectorAll('.metrics .span-2');
    
    function adjustSizes() {
        metricValues.forEach(span => {
            span.style.fontSize = '';
            const container = span.closest('.metrics > div');
            const containerWidth = container.offsetWidth;
            const textWidth = span.scrollWidth;
            
            if (textWidth > containerWidth - 10) {
                const scaleRatio = (containerWidth - 10) / textWidth;
                const newSize = Math.max(2, 4 * scaleRatio);
                span.style.fontSize = `${newSize}em`;
            } else {
                span.style.fontSize = '4em';
            }
        });
    }
    
    adjustSizes();
    window.addEventListener('resize', adjustSizes);
}

function initializeBarChart() {
    const loanCounts = <?= json_encode($loanCounts) ?>;
    const loanTypes = Object.keys(loanCounts);
    const counts = Object.values(loanCounts);
    
    const barCanvas = document.getElementById('barChart');
    const barCtx = barCanvas.getContext('2d');
    
    // Clear any previous chart
    barCtx.clearRect(0, 0, barCanvas.width, barCanvas.height);
    
    // Chart dimensions
    const barWidth = 30;
    const barSpacing = 20;
    const startX = 50;
    const startY = barCanvas.height - 80;
    const axisPadding = 5;
    
    // Calculate Y-axis max
    const maxCount = Math.max(5, ...counts);
    const yAxisMax = Math.ceil(maxCount / 5) * 5;
    
    // Draw bars
    counts.forEach((value, index) => {
        const x = startX + (barWidth + barSpacing) * index;
        const barHeight = (value / yAxisMax) * (startY - 20);
        const y = startY - barHeight;
        
        barCtx.fillStyle = '#74C0FC';
        barCtx.fillRect(x, y, barWidth, barHeight);
    });
    
    // X-axis labels
    barCtx.fillStyle = 'white';
    barCtx.font = '16px Trebuchet MS';
    loanTypes.forEach((type, index) => {
        const label = type.substring(0, 2).toUpperCase();
        const x = startX + (barWidth + barSpacing) * index + barWidth / 5;
        barCtx.fillText(label, x, startY + 20);
    });
    
    // Y-axis and grid
    barCtx.strokeStyle = 'white';
    barCtx.beginPath();
    barCtx.moveTo(startX - axisPadding, startY);
    barCtx.lineTo(startX - axisPadding, 20);
    barCtx.stroke();
    
    // Y-axis labels
    barCtx.fillStyle = 'whitesmoke';
    barCtx.textAlign = 'right';
    barCtx.font = '16px Trebuchet MS';
    
    for (let i = 0; i <= yAxisMax; i += (yAxisMax > 10 ? 2 : 1)) {
        const y = startY - (i / yAxisMax) * (startY - 20);
        barCtx.fillText(i, startX - axisPadding - 5, y + 5);
        
        // Grid lines
        barCtx.strokeStyle = 'rgba(255, 255, 255, 0.2)';
        barCtx.beginPath();
        barCtx.moveTo(startX - axisPadding, y);
        barCtx.lineTo(barCanvas.width - 250, y);
        barCtx.stroke();
    }
    
    // Legend
    const legendX = barCanvas.width - 250;
    const legendY = 40;
    const legendSpacing = 20;
    
    barCtx.font = '16px Trebuchet MS';
    barCtx.textAlign = 'left';
    loanTypes.forEach((type, index) => {
        const label = type.substring(0, 2).toUpperCase();
        barCtx.fillStyle = 'lightgray';
        barCtx.fillText(`${label}: ${type}`, legendX + 20, legendY + index * legendSpacing + 12);
    });
}

function initializePieChart() {
    const pieData = <?= json_encode($pieData) ?>;
    const pieCanvas = document.getElementById('pieChart');
    const pieCtx = pieCanvas.getContext('2d');
    
    const labels = ['Pending', 'Approved', 'Rejected'];
    const values = [
        pieData.pending,
        pieData.approved,
        pieData.rejected
    ];
    
    const statusColors = {
        'Pending': '#ddd',
        'Approved': 'teal',
        'Rejected': 'tomato'
    };

    const total = values.reduce((sum, value) => sum + value, 0);
    let startAngle = 0;
    const centerX = pieCanvas.width / 4;
    const centerY = pieCanvas.height / 2;
    const radius = Math.min(pieCanvas.width / 3, pieCanvas.height / 2) - 10;

    values.forEach((value, index) => {
        if (value > 0) {
            const sliceAngle = (2 * Math.PI * value) / total;
            pieCtx.beginPath();
            pieCtx.moveTo(centerX, centerY);
            pieCtx.arc(centerX, centerY, radius, startAngle, startAngle + sliceAngle);
            pieCtx.closePath();
            pieCtx.fillStyle = statusColors[labels[index]];
            pieCtx.fill();
            startAngle += sliceAngle;
        }
    });

    // Add a legend
    pieCtx.font = '16px Trebuchet MS';
    let legendY = 20;
    const legendX = centerX + radius + 20;
    const legendSpacing = 20;

    values.forEach((value, index) => {
        if (value > 0) {
            pieCtx.fillStyle = statusColors[labels[index]];
            pieCtx.fillRect(legendX, legendY, 15, 15);
            pieCtx.fillStyle = 'whitesmoke';
            pieCtx.fillText(`${labels[index]}: ${value.toFixed(1)}%`, legendX + 20, legendY + 12);
            legendY += legendSpacing;
        }
    });
}

// LOAN APPLICATION FUNCTIONS

function setupEventListeners() {
    // Navigation links
    document.getElementById('applyLoanLink')?.addEventListener('click', function(e) {
        e.preventDefault();
        window.location.hash = '#applyLoan';
    });
    
    document.getElementById('loanHistoryLink')?.addEventListener('click', function(e) {
        e.preventDefault();
        window.location.hash = '#loanHistory';
    });

    // Loan filter form
    document.querySelector('.sub')?.addEventListener('click', function(e) {
        e.preventDefault();
        document.querySelector('.loan-filter form').submit();
    });
    
    document.querySelector('.res')?.addEventListener('click', function(e) {
        e.preventDefault();
        document.querySelector('.loan-filter form').reset();
        document.querySelector('.loan-filter form').submit();
    });

    // Quick amount buttons
    document.querySelectorAll('.quick-amounts button').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelector('[name="min_amount"]').value = this.dataset.min;
            document.querySelector('[name="max_amount"]').value = this.dataset.max;
            document.querySelector('.loan-filter form').submit();
        });
    });
    
    // Loan application form
    document.getElementById('amountNeeded')?.addEventListener('input', calculateInstallments);
    document.getElementById('duration')?.addEventListener('input', calculateInstallments);
    
    // Popup controls
    document.getElementById('cancelBtn')?.addEventListener('click', function() {
        document.getElementById('loanPopup').style.display = 'none';
        document.body.classList.remove('popup-open');
    });
    
    document.getElementById('closePopupBtn')?.addEventListener('click', function() {
        document.getElementById('loanDetailsPopup').style.display = 'none';
        document.body.classList.remove('popup-open');
    });
    
    // Profile edit controls
    document.getElementById('editProfileBtn')?.addEventListener('click', function() {
        document.getElementById('profileOverlay').style.display = 'flex';
        document.body.classList.add('popup-open');
    });
    
    document.getElementById('cancelEditBtn')?.addEventListener('click', function() {
        document.getElementById('profileOverlay').style.display = 'none';
        document.body.classList.remove('popup-open');
    });
}

function calculateInstallments() {
    const amount = parseFloat(document.getElementById('amountNeeded').value) || 0;
    const duration = parseInt(document.getElementById('duration').value) || 1;
    const rate = parseFloat(document.getElementById('interestRate').value) || 0;
    
    if (amount > 0 && duration > 0 && rate > 0) {
        const monthlyRate = rate / 100 / 12;
        const numerator = amount * monthlyRate * Math.pow(1 + monthlyRate, duration);
        const denominator = Math.pow(1 + monthlyRate, duration) - 1;
        const monthlyInstallment = numerator / denominator;
        
        document.getElementById('installments').value = numberWithCommas(monthlyInstallment.toFixed(2));
    } else {
        document.getElementById('installments').value = '';
    }
}

// MESSAGE HANDLING

function handleMessages() {
    // Auto-hide alert messages after 3 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 500);
        }, 3000);
    });
}
</script>

     

</body>
</html>
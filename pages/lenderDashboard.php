<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

session_start();


// Access Restrictions from Admin Functionality
require_once 'check_access.php';


if (!isset($_SESSION['user_id'])) {
    header("Location: signin.html");
    exit();
}

// Database config file
include '../phpconfig/config.php';

$userId = $_SESSION['user_id'];

// Fetch user data
$query = "SELECT user_name FROM users WHERE user_id = '$userId'";
$result = mysqli_query($myconn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $user = mysqli_fetch_assoc($result);
    $_SESSION['user_name'] = $user['user_name'];
} else {
    $_SESSION['user_name'] = "Guest";
}

// Fetch lender_id
$lenderQuery = "SELECT lender_id FROM lenders WHERE user_id = '$userId'";
$lenderResult = mysqli_query($myconn, $lenderQuery);

if (mysqli_num_rows($lenderResult) > 0) {
    $lender = mysqli_fetch_assoc($lenderResult);
    $_SESSION['lender_id'] = $lender['lender_id'];
} else {
    $_SESSION['loan_message'] = "You are not registered as a lender.";
    header("Location: lenderDashboard.php");
    exit();
}

// Include paymentReview.php 
require_once 'paymentReview.php';

// Include activeLoans.php
$activeLoansData = require_once 'activeLoans.php';
$activeLoanData = $activeLoansData['activeLoanData'];
$activeFilters = $activeLoansData['filters'];
$allLoanTypes = $activeLoansData['allLoanTypes'];

// Define all loan types
$allLoanTypes = [
    "Personal Loan", "Business Loan", "Mortgage Loan", 
    "MicroFinance Loan", "Student Loan", "Construction Loan",
    "Green Loan", "Medical Loan", "Startup Loan", "Agricultural Loan"
];

// Get loan offers count
$totalOffersQuery = "SELECT COUNT(*) FROM loan_offers WHERE lender_id = '$lender_id'";
$totalOffersResult = mysqli_query($myconn, $totalOffersQuery);
$totalOffers = (int)mysqli_fetch_row($totalOffersResult)[0];

// Get average interest rate
$avgInterestQuery = "SELECT AVG(interest_rate) FROM loan_offers WHERE lender_id = '$lender_id'";
$avgInterestResult = mysqli_query($myconn, $avgInterestQuery);
$avgInterestRate = number_format((float)mysqli_fetch_row($avgInterestResult)[0], 2);

// Get total loan amount owed
// subquery checks last payment state to determine state
$owedQuery = "
    SELECT COALESCE(SUM(latest_payment.remaining_balance), 0)
    FROM loans
    JOIN (
        SELECT loan_id, remaining_balance
        FROM payments
        WHERE (loan_id, payment_date) IN (
            SELECT loan_id, MAX(payment_date)
            FROM payments
            GROUP BY loan_id
        )
    ) latest_payment ON loans.loan_id = latest_payment.loan_id
    WHERE loans.lender_id = ?
    AND loans.status = 'disbursed'";

$stmt = $myconn->prepare($owedQuery);
$stmt->bind_param("i", $lender_id);
$stmt->execute();
$owedResult = $stmt->get_result();
$owedData = $owedResult->fetch_row();
$owedCapacity = $owedData[0] ? number_format((float)$owedData[0], 0) : '0';

// Get total disbursed loans count
$disbursedLoansQuery = "SELECT COUNT(*) FROM loans WHERE lender_id = '$lender_id' AND status = 'disbursed'";
$disbursedLoansResult = mysqli_query($myconn, $disbursedLoansQuery);
$disbursedLoans = (int)mysqli_fetch_row($disbursedLoansResult)[0];

// Get active loans count (disbursed loans with remaining balance)
// subquery checks last payment state to determine state

$activeLoansQuery = "
    SELECT COUNT(DISTINCT loans.loan_id)
    FROM loans
    JOIN (
        SELECT loan_id, remaining_balance
        FROM payments
        WHERE (loan_id, payment_date) IN (
            SELECT loan_id, MAX(payment_date)
            FROM payments
            GROUP BY loan_id
        )
    ) latest_payment ON loans.loan_id = latest_payment.loan_id
    WHERE loans.lender_id = ?
    AND loans.status = 'disbursed'
    AND latest_payment.remaining_balance > 0";

$stmt = $myconn->prepare($activeLoansQuery);
$stmt->bind_param("i", $lender_id);
$stmt->execute();
$activeLoansResult = $stmt->get_result();
$activeLoans = (int)$activeLoansResult->fetch_row()[0] ?? 0;

// Get total amount disbursed
$disbursedAmountQuery = "SELECT SUM(amount) FROM loans WHERE lender_id = '$lender_id' AND status IN ('disbursed')";
$disbursedAmountResult = mysqli_query($myconn, $disbursedAmountQuery);
$disbursedAmountData = mysqli_fetch_row($disbursedAmountResult);
$totalDisbursedAmount = $disbursedAmountData[0] ? number_format((float)$disbursedAmountData[0]) : 0;

// Get loan offers with their disbursed loans count
$loanOffersQuery = "SELECT 
                      loan_offers.offer_id,
                      loan_offers.loan_type,
                      loan_offers.interest_rate,
                      loan_offers.max_amount,
                      loan_offers.max_duration,
                      COUNT(loans.loan_id) as disbursed_count
                    FROM loan_offers
                    LEFT JOIN loans ON loan_offers.offer_id = loans.offer_id
                      AND loans.lender_id = '$lender_id'
                      AND loans.status = 'disbursed'
                    WHERE loan_offers.lender_id = '$lender_id'
                    GROUP BY loan_offers.offer_id, loan_offers.loan_type, loan_offers.interest_rate, 
                             loan_offers.max_amount, loan_offers.max_duration";

$loanOffersResult = mysqli_query($myconn, $loanOffersQuery);

// Initialize loan counts
$loanCounts = array_fill_keys($allLoanTypes, 0);
$offersData = [];

if ($loanOffersResult) {
    while ($row = mysqli_fetch_assoc($loanOffersResult)) {
        $loanType = $row['loan_type'];
        $loanCounts[$loanType] = (int)$row['disbursed_count'];
        
        $offersData[] = [
            'offer_id' => $row['offer_id'],
            'loan_type' => $loanType,
            'interest_rate' => $row['interest_rate'],
            'max_amount' => $row['max_amount'],
            'max_duration' => $row['max_duration']
        ];
    }
    // Sort the $offersData array by offer_id in descending order using funtion usort
    usort($offersData, function($a, $b) {
        return $b['offer_id'] - $a['offer_id'];
    });
}

// Get loan status distribution
$statusQuery = "SELECT status, COUNT(*) as count FROM loans WHERE lender_id = '$lender_id' GROUP BY status";
$statusResult = mysqli_query($myconn, $statusQuery);
$statusData = mysqli_fetch_all($statusResult, MYSQLI_ASSOC);



// Get filter parameters from URL (add near top with other initializations)
$statusFilter = $_GET['status'] ?? '';
$loanTypeFilter = $_GET['loan_type'] ?? '';

// loan requests query to include both filters
$loanRequestsQuery = "SELECT 
    loans.loan_id,
    loans.amount,
    loans.interest_rate,
    loans.duration,
    loans.collateral_value,
    loans.collateral_description,
    loans.risk_level,
    loans.status,
    loans.created_at,
    customers.name,
    loan_offers.loan_type
FROM loans
JOIN loan_offers ON loans.offer_id = loan_offers.offer_id
JOIN customers ON loans.customer_id = customers.customer_id
WHERE loans.lender_id = '$lender_id'";

// Status filter
if (!empty($statusFilter) && in_array($statusFilter, ['pending', 'disbursed', 'rejected'])) {
    $loanRequestsQuery .= " AND loans.status = '$statusFilter'";
}

// Loan type filter
if (!empty($loanTypeFilter)) {
    $loanRequestsQuery .= " AND loan_offers.loan_type = '$loanTypeFilter'";
}

// Date range filter
if (isset($_GET['date_range']) && $_GET['date_range']) {
    switch ($_GET['date_range']) {
        case 'today':
            $loanRequestsQuery .= " AND DATE(loans.created_at) = CURDATE()";
            break;
        case 'week':
            $loanRequestsQuery .= " AND YEARWEEK(loans.created_at, 1) = YEARWEEK(CURDATE(), 1)";
            break;
        case 'month':
            $loanRequestsQuery .= " AND MONTH(loans.created_at) = MONTH(CURDATE()) AND YEAR(loans.created_at) = YEAR(CURDATE())";
            break;
        case 'year':
            $loanRequestsQuery .= " AND YEAR(loans.created_at) = YEAR(CURDATE())";
            break;
    }
}

// Amount range filter
if (isset($_GET['amount_range']) && $_GET['amount_range']) {
    list($minAmount, $maxAmount) = explode('-', str_replace('+', '-', $_GET['amount_range']));
    $loanRequestsQuery .= " AND loans.amount >= $minAmount";
    if (is_numeric($maxAmount)) {
        $loanRequestsQuery .= " AND loans.amount <= $maxAmount";
    }
}

// Duration filter
if (isset($_GET['duration_range']) && $_GET['duration_range']) {
    list($minDuration, $maxDuration) = explode('-', str_replace('+', '-', $_GET['duration_range']));
    $loanRequestsQuery .= " AND loans.duration >= $minDuration";
    if (is_numeric($maxDuration)) {
        $loanRequestsQuery .= " AND loans.duration <= $maxDuration";
    }
}

// Collateral filter
if (isset($_GET['collateral_range']) && $_GET['collateral_range']) {
    list($minCollateral, $maxCollateral) = explode('-', str_replace('+', '-', $_GET['collateral_range']));
    $loanRequestsQuery .= " AND loans.collateral_value >= $minCollateral";
    if (is_numeric($maxCollateral)) {
        $loanRequestsQuery .= " AND loans.collateral_value <= $maxCollateral";
    }
}
 $loanRequestsQuery .= " ORDER BY loans.created_at DESC";


// Execute the query
$loanRequestsResult = mysqli_query($myconn, $loanRequestsQuery);
if (!$loanRequestsResult) {
    die("Query failed: " . mysqli_error($myconn));
}
$loanRequests = mysqli_fetch_all($loanRequestsResult, MYSQLI_ASSOC);


// Pie Chart
// Get loan status distribution for the current lender
$statusQuery = "SELECT status, COUNT(*) as count 
                FROM loans 
                WHERE lender_id = '$lender_id' 
                GROUP BY status";
$statusResult = mysqli_query($myconn, $statusQuery);
$statusData = [];
$totalLoans = 0;

while ($row = mysqli_fetch_assoc($statusResult)) {
    $statusData[$row['status']] = (int)$row['count'];
    $totalLoans += (int)$row['count'];
}

// Calculate percentages for each status
$pieData = [
    'pending' => isset($statusData['pending']) ? ($statusData['pending'] / $totalLoans * 100) : 0,
    'disbursed' => isset($statusData['disbursed']) ? ($statusData['disbursed'] / $totalLoans * 100) : 0,
    'rejected' => isset($statusData['rejected']) ? ($statusData['rejected'] / $totalLoans * 100) : 0
];

// Fetch lender profile data
$lenderProfileQuery = "SELECT * FROM lenders WHERE lender_id = '$lender_id'";
$lenderProfileResult = mysqli_query($myconn, $lenderProfileQuery);
$lenderProfile = mysqli_fetch_assoc($lenderProfileResult);



// Check for messages
if (isset($_SESSION['loan_message'])) {
    $loan_message = $_SESSION['loan_message'];
    unset($_SESSION['loan_message']);
} else {
    $loan_message = null;
}

mysqli_close($myconn);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lender's Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <main>
        <div class="header">
            <div class="header2">
                <div class="logo">LMS</div>
            </div>
            <div class="header4">
                <div>
                    <?php if ($loan_message): ?>
                        <div id="loan-message" class="loan-message">
                            <?php echo htmlspecialchars($loan_message); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div>
                <ul>
                    <li><a href="logoutbtn.php" class="no-col">Log Out</a></li>
                </ul>
                </div>
                
            </div>
            
        </div>
        <div class="customer-content">
            <div class="nav">
                <ul class="nav-split">
                    <div class="top">
                        <li><a href="#dashboard">Dashboard</a></li>
                        <li>
                            <a href="#createLoan" 
                            class="<?php echo ($status === 'restricted_create') ? 'disabled-link' : '' ?>">
                            Create Loan Offers
                            </a>
                        </li>
                        <li><a href="#loanRequests">Loan Requests</a></li>
                        <li><a href="#activeLoans">Active Loans</a></li>  
                        <li><a href="#paymentReview">Payment Tracking</a></li>  
                        <li><a href="#profile">Profile</a></li>
                    </div>
                    <div class="bottom">
                        <li><a href="#feedback">Feedback</a></li>
                        <li><a href="#contactSupport">Help</a></li>
                                <!-- Copyright -->
                                <div class="copyright">
                                    <p><?php
                                        $currentYear = date("Y");
                                        echo "&copy; $currentYear";
                                        ?>
                                        <a href="mailto:innocentmukabwa@gmail.com">dev</a>
                                    </p>
                                </div>
                    </div>
                </ul>
            </div>
            <div class="display">
                <!-- Create Loan Offer -->
                <div id="createLoan" class="margin">
                    <div class="loan-split">
                        <div class="loan-split-left">
                            <div>
                                <h1>Create a Loan Offer</h1>
                                <p>Fill out the form to create a new loan offer.</p>
                            </div>         
                            <div>
                                <form action="createLoan.php" method="post" onsubmit="return validateFormLoans()">
                                <div id="error" style="color: tomato; font-weight:700"></div>
                                <table>
                                        <tr>
                                            
                                        <td><label>Loan Type</label></td>
                                        <td>
                                            <select name="type" id="type" class="select">
                                                <option value="--select option--" selected>--select option--</option>
                                                <option value="Personal Loan">Personal Loan</option>
                                                <option value="Business Loan">Business Loan</option>
                                                <option value="Mortgage Loan">Mortgage Loan</option>
                                                <option value="MicroFinance Loan">MicroFinance Loan</option>
                                                <option value="Student Loan">Student Loan</option>
                                                <option value="Construction Loan">Construction Loan</option>
                                                <option value="Green Loan">Green Loan</option>
                                                <option value="Medical Loan">Medical Loan</option>
                                                <option value="Startup Loan">Startup Loan</option>
                                                <option value="Agricultural Loan">Agricultural Loan</option>
                                            </select>
                                        </td>
                                        </tr>
                                        <tr>
                                            <td><label for="interestRate">Interest Rate</label></td>
                                            <td><input type="text" id="interestRate" name="interestRate"></td>
                                        </tr>
                                        <tr>
                                            <td><label for="maxAmount">Maximum Amount <br>(in shillings)</label></td>
                                            <td><input type="text" id="maxAmount" name="maxAmount"></td>
                                        </tr>
                                        <tr>
                                            <td><label for="maxDuration">Maximum Duration <br>(in months)</label></td>
                                            <td><input type="text" id="maxDuration" name="maxDuration"></td>
                                        </tr>
                                        <tr class="submit-action">
                                            <td><button type="submit" name="submit">SUBMIT</button></td>
                                        </tr>
                                    </table>
                                </form>
                            </div>
                        </div>
                        <div class="loan-slot">
                            <h3>Loan Offers Information</h3>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Loan Type</th>
                                        <th class="mid">Interest Rate</th>
                                        <th class="mid">Max Amount</th>
                                        <th class="mid">Max Duration</th>
                                        <th class="mid">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($offersData as $offer): ?>
                                    <tr>        
                                        <td><?php echo htmlspecialchars($offer['loan_type']); ?></td>
                                        <td class="mid"><?php echo htmlspecialchars($offer['interest_rate']); ?>%</td>
                                        <td class="mid"><?php echo number_format(htmlspecialchars($offer['max_amount'])); ?></td>
                                        <td class="mid"><?php echo htmlspecialchars($offer['max_duration']); ?> months</td>
                                        <td class="action-buttons">
                                            <!-- Edit Button-->
                                            <button class="act edit-btn" 
                                                    data-offer-id="<?= $offer['offer_id'] ?>" 
                                                    data-loan-type="<?= htmlspecialchars($offer['loan_type']) ?>" 
                                                    data-interest-rate="<?= $offer['interest_rate'] ?>" 
                                                    data-max-amount="<?= $offer['max_amount'] ?>" 
                                                    data-max-duration="<?= $offer['max_duration'] ?>">
                                                Edit
                                            </button>
                                            
                                            <!-- Delete Form-->
                                            <form action="deleteLoan.php" method="post" class="del-form">
                                                <input type="hidden" name="offer_id" value="<?= $offer['offer_id'] ?>">
                                                <button type="submit" class="del-btn">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>   
                </div>

                <!-- Edit Loan Functionality -->
                <div class="popup-overlay" id="popupOverlay"></div>

                <div class="edit-popup" id="editPopup">
                    <h3>Edit Loan offer</h3>
                    <form class="edit-form" id="editForm" method="post" action="editLoan.php">
                        <input type="hidden" name="offer_id" id="editOfferId">
                        
                        <div class="ltype">
                           <div><label for="editLoanType">Loan Type:</label></div> 
                            <div><span id="editLoanType"></span></div>
                        </div>
                        
                        <div>
                            <label for="editInterestRate">Interest Rate (%):</label>
                            <input type="text" step="0.01" name="interest_rate" id="editInterestRate">
                        </div>
                        
                        <div>
                            <label for="editMaxAmount">Max Amount (shillings):</label>
                            <input type="text" name="max_amount" id="editMaxAmount">
                        </div>
                        
                        <div>
                            <label for="editMaxDuration">Max Duration (months):</label>
                            <input type="text" name="max_duration" id="editMaxDuration">
                        </div>
                        
                        <div class="edit-act">
                            <button type="button" class="del" onclick="hideEditPopup()">Cancel</button>
                            <button type="submit" class="act">Save Changes</button>
                        </div>
                    </form>
                </div>

                <!-- Loan Request -->
                <div id="loanRequests" class="margin">
                    <h1>Loan Requests</h1>
                    <p>Loan applications from customers for your loan offers.</p>
                    <div class="loan-filter-container">
                        <form method="get" action="#loanRequests">
                            <div class="filter-row">
                                <div class="filter-group">
                                    <label for="status">Status:</label>
                                    <select name="status" id="status"  onchange="this.form.submit()">  <!-- this submits the form on select -->
                                        <option value="">All Statuses</option>
                                        <option value="pending" <?= ($statusFilter === 'pending') ? 'selected' : '' ?>>Pending</option>
                                        <option value="disbursed" <?= ($statusFilter === 'disbursed') ? 'selected' : '' ?>>Disbursed</option>
                                        <option value="rejected" <?= ($statusFilter === 'rejected') ? 'selected' : '' ?>>Rejected</option>
                                    </select>
                                </div>
                                
                                <div class="filter-group">
                                    <label for="loan_type">Loan Type:</label>
                                    <select name="loan_type" id="loan_type"  onchange="this.form.submit()">  <!-- this submits the form on select -->
                                        <option value="">All Types</option>
                                        <option value="Personal Loan" <?= ($loanTypeFilter === 'Personal Loan') ? 'selected' : '' ?>>Personal</option>
                                        <option value="Business Loan" <?= ($loanTypeFilter === 'Business Loan') ? 'selected' : '' ?>>Business</option>
                                        <option value="Mortgage Loan" <?= ($loanTypeFilter === 'Mortgage Loan') ? 'selected' : '' ?>>Mortgage</option>
                                        <option value="MicroFinance Loan" <?= ($loanTypeFilter === 'MicroFinance Loan') ? 'selected' : '' ?>>MicroFinance</option>
                                        <option value="Student Loan" <?= ($loanTypeFilter === 'Student Loan') ? 'selected' : '' ?>>Student</option>
                                        <option value="Construction Loan" <?= ($loanTypeFilter === 'Construction Loan') ? 'selected' : '' ?>>Construction</option>
                                        <option value="Green Loan" <?= ($loanTypeFilter === 'Green Loan') ? 'selected' : '' ?>>Green</option>
                                        <option value="Medical Loan" <?= ($loanTypeFilter === 'Medical Loan') ? 'selected' : '' ?>>Medical</option>
                                        <option value="Startup Loan" <?= ($loanTypeFilter === 'Startup Loan') ? 'selected' : '' ?>>Startup</option>
                                        <option value="Agricultural Loan" <?= ($loanTypeFilter === 'Agricultural Loan') ? 'selected' : '' ?>>Agricultural</option>
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
                                    <label for="duration_range">Duration:</label>
                                    <select name="duration_range" id="duration_range" onchange="this.form.submit()">
                                        <option value="">Any Duration</option>
                                        <option value="0-6" <?= (isset($_GET['duration_range']) && $_GET['duration_range'] === '0-6') ? 'selected' : '' ?>>0-6 months</option>
                                        <option value="6-12" <?= (isset($_GET['duration_range']) && $_GET['duration_range'] === '6-12') ? 'selected' : '' ?>>6-12 months</option>
                                        <option value="12-24" <?= (isset($_GET['duration_range']) && $_GET['duration_range'] === '12-24') ? 'selected' : '' ?>>12-24 months</option>
                                        <option value="24+" <?= (isset($_GET['duration_range']) && $_GET['duration_range'] === '24+') ? 'selected' : '' ?>>24+ months</option>
                                    </select>
                                </div>

                                <div class="filter-group">
                                    <label for="collateral_range">Collateral Range:</label>
                                    <select name="collateral_range" id="collateral_range" onchange="this.form.submit()">
                                        <option value="">Any Collateral</option>
                                        <option value="0-5000" <?= (isset($_GET['collateral_range']) && $_GET['collateral_range'] === '0-5000') ? 'selected' : '' ?>>0 - 5,000</option>
                                        <option value="5000-20000" <?= (isset($_GET['collateral_range']) && $_GET['collateral_range'] === '5000-20000') ? 'selected' : '' ?>>5,000 - 20,000</option>
                                        <option value="20000-50000" <?= (isset($_GET['collateral_range']) && $_GET['collateral_range'] === '20000-50000') ? 'selected' : '' ?>>20,000 - 50,000</option>
                                        <option value="50000+" <?= (isset($_GET['collateral_range']) && $_GET['collateral_range'] === '50000+') ? 'selected' : '' ?>>50,000+</option>
                                    </select>
                                </div>


                                <div class="filter-actions">
                                    <!-- <button type="submit" class="apply-btn">Apply Filters</button>  FALL BACK 16 4 1851HRS-->
                                    <a href="lenderDashboard.php#loanRequests"><button type="button" class="reset-btn">Reset</button></a>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="loan-requests-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Loan ID</th>
                                    <th>Customer</th>
                                    <th>Loan Type</th>
                                    <th>Amount</th>
                                    <th>Duration</th>
                                    <th>Collateral Value</th>
                                    <th>Risk Level</th>
                                    <th>Status</th>
                                    <th >Application Date</th>
                                    <th style="text-align: center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($loanRequests)): ?>
                                    <?php foreach ($loanRequests as $request): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($request['loan_id']); ?></td>
                                        <td><?php echo htmlspecialchars($request['name']); ?></td>
                                        <td><?php echo htmlspecialchars($request['loan_type']); ?></td>
                                        <td><?php echo number_format($request['amount'], 2); ?></td>
                                        <td style="text-align: center"><?php echo htmlspecialchars($request['duration']); ?></td>
                                        <td style="text-align: center"><?php echo htmlspecialchars($request['collateral_value']); ?></td>
                                        <td>
                                            <span class="risk-badge risk-<?php echo strtolower(htmlspecialchars($request['risk_level'])); ?>">
                                                <?php echo htmlspecialchars($request['risk_level']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower(htmlspecialchars($request['status'])); ?>">
                                                <?php echo htmlspecialchars($request['status']); ?>
                                            </span>
                                        </td>
                                        <td style="text-align: center"><?php echo date('j M Y', strtotime($request['created_at'])); ?></td>
                                        <td class="action-buttons">
                                             <!-- View Details Button -->
                                              <!-- View Button -->
                                            <button class="btn-view" 
                                                    data-loan-id="<?= htmlspecialchars($request['loan_id']) ?>"
                                                    data-customer="<?= htmlspecialchars($request['name']) ?>"
                                                    data-loan-type="<?= htmlspecialchars($request['loan_type']) ?>"
                                                    data-amount="<?= htmlspecialchars($request['amount']) ?>"
                                                    data-interest-rate="<?= htmlspecialchars($request['interest_rate']) ?>"
                                                    data-duration="<?= htmlspecialchars($request['duration']) ?>"
                                                    data-collateral-value="<?= htmlspecialchars($request['collateral_value']) ?>"
                                                    data-collateral-desc="<?= htmlspecialchars($request['collateral_description']) ?>"
                                                    data-status="<?= htmlspecialchars($request['status']) ?>"
                                                    data-created-at="<?= htmlspecialchars($request['created_at']) ?>">
                                                View
                                            </button>

                                            <form action="disburse_loan.php" method="post" class="inline-form">
                                                <input type="hidden" name="loan_id" value="<?php echo $request['loan_id']; ?>">
                                                <button type="submit" class="btn-disburse <?php echo $request['status'] !== 'pending' ? 'disabled' : ''; ?>" 
                                                    <?php echo $request['status'] !== 'pending' ? 'disabled' : ''; ?>>
                                                    Disburse
                                                </button>
                                            </form>
                                            <form action="reject_loan.php" method="post" class="inline-form">
                                                <input type="hidden" name="loan_id" value="<?php echo $request['loan_id']; ?>">
                                                <button type="submit" class="btn-reject <?php echo $request['status'] === 'rejected' ? 'disabled' : ''; ?>" 
                                                    <?php echo $request['status'] !== 'pending' ? 'disabled' : ''; ?>>
                                                    Reject
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td style="color: tomato; font-size: 1.2em;" colspan="9" class="no-data">
                                            No loan requests found for your offers
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>

                    </div>
                    <!-- View Loan Details Popup -->
                <div class="popup-overlay3" id="viewLoanOverlay"></div>
                <div class="view-popup" id="viewLoanPopup">
                    <h2>Loan Application Details</h2>
                    <div class="view-form">
                        <div class="detail-row">
                            <div class="detail-label">Loan ID:</div>
                            <div class="detail-value" id="viewLoanId"></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Customer:</div>
                            <div class="detail-value" id="viewCustomer"></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Loan Type:</div>
                            <div class="detail-value" id="viewLoanType"></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Amount:</div>
                            <div class="detail-value" id="viewAmount"></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Interest Rate:</div>
                            <div class="detail-value" id="viewInterestRate"></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Duration:</div>
                            <div class="detail-value" id="viewDuration"></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Collateral Value:</div>
                            <div class="detail-value" id="viewCollateralValue"></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Collateral Description:</div>
                            <div class="detail-value" id="viewCollateralDesc"></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Status:</div>
                            <div class="detail-value" id="viewStatus"></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Application Date:</div>
                            <div class="detail-value" id="viewCreatedAt"></div>
                        </div>
                        <div class="view-actions">
                            <button type="button" class="close-btn" onclick="hideViewLoanPopup()">&times;</button>
                        </div>
                    </div>
                </div>
                </div>
                
                <!-- Active Loans -->
                <div id="activeLoans" class="margin">
                    <h1>Active Loans</h1>
                    <p>View loans with outstanding balances.</p>
                    <div class="loan-filter-container">
                        <form method="get" action="lenderDashboard.php#activeLoans">
                            <div class="filter-row">
                                <div class="filter-group">
                                    <label for="active_loan_type">Loan Type:</label>
                                    <select name="active_loan_type" id="active_loan_type" onchange="this.form.submit()">
                                        <option value="">All Types</option>
                                        <?php foreach ($allLoanTypes as $type): ?>
                                            <option value="<?php echo $type; ?>" <?= ($activeFilters['loan_type'] === $type) ? 'selected' : '' ?>><?php echo $type; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="filter-group">
                                    <label for="active_date_range">Date Range:</label>
                                    <select name="active_date_range" id="active_date_range" onchange="this.form.submit()">
                                        <option value="">All Time</option>
                                        <option value="today" <?= ($activeFilters['date_range'] === 'today') ? 'selected' : '' ?>>Today</option>
                                        <option value="week" <?= ($activeFilters['date_range'] === 'week') ? 'selected' : '' ?>>This Week</option>
                                        <option value="month" <?= ($activeFilters['date_range'] === 'month') ? 'selected' : '' ?>>This Month</option>
                                        <option value="year" <?= ($activeFilters['date_range'] === 'year') ? 'selected' : '' ?>>This Year</option>
                                    </select>
                                </div>
                                <div class="filter-group">
                                    <label for="active_amount_range">Amount Range:</label>
                                    <select name="active_amount_range" id="active_amount_range" onchange="this.form.submit()">
                                        <option value="">Any Amount</option>
                                        <option value="0-5000" <?= ($activeFilters['amount_range'] === '0-5000') ? 'selected' : '' ?>>0 - 5,000</option>
                                        <option value="5000-20000" <?= ($activeFilters['amount_range'] === '5000-20000') ? 'selected' : '' ?>>5,000 - 20,000</option>
                                        <option value="20000-50000" <?= ($activeFilters['amount_range'] === '20000-50000') ? 'selected' : '' ?>>20,000 - 50,000</option>
                                        <option value="50000-100000" <?= ($activeFilters['amount_range'] === '50000-100000') ? 'selected' : '' ?>>50,000 - 100,000</option>
                                        <option value="100000+" <?= ($activeFilters['amount_range'] === '100000+') ? 'selected' : '' ?>>100,000+</option>
                                    </select>
                                </div>
                                <div class="filter-group">
                                    <label for="active_duration_range">Duration:</label>
                                    <select name="active_duration_range" id="active_duration_range" onchange="this.form.submit()">
                                        <option value="">Any Duration</option>
                                        <option value="0-6" <?= ($activeFilters['duration_range'] === '0-6') ? 'selected' : '' ?>>0-6 months</option>
                                        <option value="6-12" <?= ($activeFilters['duration_range'] === '6-12') ? 'selected' : '' ?>>6-12 months</option>
                                        <option value="12-24" <?= ($activeFilters['duration_range'] === '12-24') ? 'selected' : '' ?>>12-24 months</option>
                                        <option value="24+" <?= ($activeFilters['duration_range'] === '24+') ? 'selected' : '' ?>>24+ months</option>
                                    </select>
                                </div>
                                <div class="filter-group">
                                    <label for="active_collateral_range">Collateral Range:</label>
                                    <select name="active_collateral_range" id="active_collateral_range" onchange="this.form.submit()">
                                        <option value="">Any Collateral</option>
                                        <option value="0-5000" <?= ($activeFilters['collateral_range'] === '0-5000') ? 'selected' : '' ?>>0 - 5,000</option>
                                        <option value="5000-20000" <?= ($activeFilters['collateral_range'] === '5000-20000') ? 'selected' : '' ?>>5,000 - 20,000</option>
                                        <option value="20000-50000" <?= ($activeFilters['collateral_range'] === '20000-50000') ? 'selected' : '' ?>>20,000 - 50,000</option>
                                        <option value="50000+" <?= ($activeFilters['collateral_range'] === '50000+') ? 'selected' : '' ?>>50,000+</option>
                                    </select>
                                </div>
                                <div class="filter-actions">
                                    <a href="lenderDashboard.php#activeLoans"><button type="button" class="reset-btn">Reset</button></a>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="loan-requests-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Loan ID</th>
                                    <th>Customer</th>
                                    <th>Loan Type</th>
                                    <th>Amount</th>
                                    <th>Rate</th>
                                    <th>Duration</th>
                                    <th>Collateral Value</th>
                                    <th>Balance</th>
                                    <th>Application Date</th>
                                    <th style="text-align: right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($activeLoanData)): ?>
                                    <?php foreach ($activeLoanData as $loan): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($loan['loan_id']); ?></td>
                                            <td><?php echo htmlspecialchars($loan['name']); ?></td>
                                            <td><?php echo htmlspecialchars($loan['loan_type']); ?></td>
                                            <td><?php echo number_format($loan['amount'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($loan['interest_rate']); ?>%</td>
                                            <td><?php echo htmlspecialchars($loan['duration']); ?></td>
                                            <td><?php echo htmlspecialchars($loan['collateral_value']); ?></td>
                                            <td><?php echo number_format($loan['remaining_balance'], 2); ?></td>
                                            <td><?php echo date('j M Y', strtotime($loan['created_at'])); ?></td>
                                            <td class="action-buttons">
                                                <button class="btn-view-active" 
                                                        data-loan-id="<?= htmlspecialchars($loan['loan_id']) ?>"
                                                        data-customer="<?= htmlspecialchars($loan['name']) ?>"
                                                        data-loan-type="<?= htmlspecialchars($loan['loan_type']) ?>"
                                                        data-amount="<?= htmlspecialchars($loan['amount']) ?>"
                                                        data-interest-rate="<?= htmlspecialchars($loan['interest_rate']) ?>"
                                                        data-duration="<?= htmlspecialchars($loan['duration']) ?>"
                                                        data-collateral-value="<?= htmlspecialchars($loan['collateral_value']) ?>"
                                                        data-collateral-desc="<?= htmlspecialchars($loan['collateral_description']) ?>"
                                                        data-remaining-balance="<?= htmlspecialchars($loan['remaining_balance']) ?>"
                                                        data-created-at="<?= htmlspecialchars($loan['created_at']) ?>">
                                                    View
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td style="color: tomato; font-size: 1.2em;" colspan="10" class="no-data">
                                            No active loans found
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                
                    <!-- View Active Loan Details Popup -->
                    <div class="popup-overlay3" id="viewActiveLoanOverlay"></div>
                    <div class="view-popup" id="viewActiveLoanPopup">
                        <h2>Active Loan Details</h2>
                        <div class="view-form">
                            <div class="detail-row">
                                <div class="detail-label">Loan ID:</div>
                                <div class="detail-value" id="viewActiveLoanId"></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Customer:</div>
                                <div class="detail-value" id="viewActiveCustomer"></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Loan Type:</div>
                                <div class="detail-value" id="viewActiveLoanType"></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Amount:</div>
                                <div class="detail-value" id="viewActiveAmount"></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Interest Rate:</div>
                                <div class="detail-value" id="viewActiveInterestRate"></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Duration:</div>
                                <div class="detail-value" id="viewActiveDuration"></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Collateral Value:</div>
                                <div class="detail-value" id="viewActiveCollateralValue"></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Collateral Description:</div>
                                <div class="detail-value" id="viewActiveCollateralDesc"></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Remaining Balance:</div>
                                <div class="detail-value" id="viewActiveRemainingBalance"></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Application Date:</div>
                                <div class="detail-value" id="viewActiveCreatedAt"></div>
                            </div>
                            <div class="view-actions">
                                <button type="button" class="close-btn" onclick="hideViewActiveLoanPopup()">×</button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- payment Review -->
                
                <div id="paymentReview" class="margin">
                    <h1>Payment Tracking</h1>
                    <p>View and filter payment records for your loans.</p>
                
                    <?php if (isset($_SESSION['loan_message'])): ?>
                        <div class="loan-message"><?php echo htmlspecialchars($_SESSION['loan_message']); ?></div>
                        <?php unset($_SESSION['loan_message']); ?>
                    <?php endif; ?>
                
                    <div class="loan-filter-container">
                        <form method="get" action="lenderDashboard.php#paymentReview">
                            <div class="filter-row">
                
                
                                <div class="filter-group">
                                    <label for="payment_method">Payment Method:</label>
                                    <select name="payment_method" id="payment_method" onchange="this.form.submit()">
                                        <option value="">All Methods</option>
                                        <?php foreach ($paymentMethods as $method): ?>
                                            <option value="<?php echo htmlspecialchars($method); ?>" 
                                                    <?php echo ($paymentMethodFilter === $method) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($method); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                
                                <div class="filter-group">
                                    <label for="date_range">Date Range:</label>
                                    <select name="date_range" id="date_range" onchange="this.form.submit()">
                                        <option value="">All Time</option>
                                        <option value="today" <?php echo (isset($_GET['date_range']) && $_GET['date_range'] === 'today') ? 'selected' : ''; ?>>Today</option>
                                        <option value="week" <?php echo (isset($_GET['date_range']) && $_GET['date_range'] === 'week') ? 'selected' : ''; ?>>This Week</option>
                                        <option value="month" <?php echo (isset($_GET['date_range']) && $_GET['date_range'] === 'month') ? 'selected' : ''; ?>>This Month</option>
                                        <option value="year" <?php echo (isset($_GET['date_range']) && $_GET['date_range'] === 'year') ? 'selected' : ''; ?>>This Year</option>
                                    </select>
                                </div>
                
                                <div class="filter-group">
                                    <label for="amount_range">Amount Range:</label>
                                    <select name="amount_range" id="amount_range" onchange="this.form.submit()">
                                        <option value="">Any Amount</option>
                                        <option value="0-5000" <?php echo (isset($_GET['amount_range']) && $_GET['amount_range'] === '0-5000') ? 'selected' : ''; ?>>0 - 5,000</option>
                                        <option value="5000-20000" <?php echo (isset($_GET['amount_range']) && $_GET['amount_range'] === '5000-20000') ? 'selected' : ''; ?>>5,000 - 20,000</option>
                                        <option value="20000-50000" <?php echo (isset($_GET['amount_range']) && $_GET['amount_range'] === '20000-50000') ? 'selected' : ''; ?>>20,000 - 50,000</option>
                                        <option value="50000-100000" <?php echo (isset($_GET['amount_range']) && $_GET['amount_range'] === '50000-100000') ? 'selected' : ''; ?>>50,000 - 100,000</option>
                                        <option value="100000+" <?php echo (isset($_GET['amount_range']) && $_GET['amount_range'] === '100000+') ? 'selected' : ''; ?>>100,000+</option>
                                    </select>
                                </div>
                
                                <div class="filter-actions">
                                    <a href="lenderDashboard.php#paymentReview"><button type="button" class="reset-btn">Reset</button></a>
                                </div>
                            </div>
                        </form>
                    </div>
                
                    <div class="loan-requests-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Payment ID</th>
                                    <th>Loan ID</th>
                                    <th>Customer</th>
                                    <th>Loan Type</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Type</th>
                                    <th>Date</th>
                                    <th style="text-align: right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($payments)): ?>
                                    <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($payment['payment_id']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['loan_id']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['customer_name']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['loan_type']); ?></td>
                                        <td><?php echo number_format($payment['amount'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['payment_type']); ?></td>
                                        <td><?php echo date('j M Y', strtotime($payment['payment_date'])); ?></td>
                                        <td class="action-buttons">
                                            <button class="view" 
                                                    data-payment-id="<?php echo htmlspecialchars($payment['payment_id']); ?>"
                                                    data-loan-id="<?php echo htmlspecialchars($payment['loan_id']); ?>"
                                                    data-customer="<?php echo htmlspecialchars($payment['customer_name']); ?>"
                                                    data-loan-type="<?php echo htmlspecialchars($payment['loan_type']); ?>"
                                                    data-amount="<?php echo htmlspecialchars($payment['amount']); ?>"
                                                    data-method="<?php echo htmlspecialchars($payment['payment_method']); ?>"
                                                    data-type="<?php echo htmlspecialchars($payment['payment_type']); ?>"
                                                    data-date="<?php echo htmlspecialchars($payment['payment_date']); ?>">
                                                View
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td style="color: tomato; font-size: 1.2em;" colspan="10" class="no-data">
                                            No payment records found
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                
                    <!-- View Payment Details Popup -->
                    <div class="popup-overlay3" id="viewPaymentOverlay"></div>
                    <div class="view-popup" id="viewPaymentPopup">
                        <h2>Payment Details</h2>
                        <div class="view-form">
                            <div class="detail-row">
                                <div class="detail-label">Payment ID:</div>
                                <div class="detail-value" id="viewPaymentId"></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Loan ID:</div>
                                <div class="detail-value" id="viewPaymentLoanId"></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Customer:</div>
                                <div class="detail-value" id="viewPaymentCustomer"></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Loan Type:</div>
                                <div class="detail-value" id="viewPaymentLoanType"></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Amount:</div>
                                <div class="detail-value" id="viewPaymentAmount"></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Payment Method:</div>
                                <div class="detail-value" id="viewPaymentMethod"></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Payment Type:</div>
                                <div class="detail-value" id="viewPaymentType"></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Payment Date:</div>
                                <div class="detail-value" id="viewPaymentDate"></div>
                            </div>
                            <div class="view-actions">
                                <button type="button" class="close-btn" onclick="hideViewPaymentPopup()">×</button>
                            </div>
                        </div>
                    </div>
                </div>

                
                <!-- Notifications -->
                <!-- <div id="notifications" class="margin">
                    <h1>Notifications</h1>
                    <p>View your alerts and reminders.</p>
                </div> -->
                
                <!-- Profile -->
                <div id="profile" class="margin">
                    <h1>Profile</h1>
                    <p>View and update your personal information.</p>
                    
                    
                    <div class="profile-container">
                        <div class="profile-details">
                            <h2>Personal Information</h2>
                            <div class="profile-row">
                                <span class="profile-label">Full Name:</span>
                                <span class="profile-value"><?php echo htmlspecialchars($lenderProfile['name']); ?></span>
                            </div>
                            <div class="profile-row">
                                <span class="profile-label">Member Since:</span>
                                <span class="profile-value"><?php echo date('j M Y', strtotime($lenderProfile['registration_date'])); ?></span>
                            </div>
                            <div class="profile-row">
                                <span class="profile-label">Email:</span>
                                <span class="profile-value"><?php echo htmlspecialchars($lenderProfile['email']); ?></span>
                            </div>
                            <div class="profile-row">
                                <span class="profile-label">Phone:</span>
                                <span class="profile-value"><?php echo htmlspecialchars($lenderProfile['phone']); ?></span>
                            </div>
                            <div class="profile-row">
                                <span class="profile-label">Address:</span>
                                <span class="profile-value"><?php echo htmlspecialchars($lenderProfile['address']); ?></span>
                            </div>
                            
                            <button id="editProfileBtn" >Edit Profile</button>
                            
                        </div>
                        <div class="additional-settings">
                                <h2>Additional Settings</h2>
                                <p class="change">Change Password</p>
                                <p class="delete">Delete Account</p>
                            </div>
                    </div>
                </div>
                

                <!-- Profile Edit Overlay -->
                <div class="popup-overlay3" id="profileOverlay"></div>
                <div class="popup-content3" id="profilePopup">
                    <!-- Message container -->
                    <div id="profileMessage" class="message-container">
                        <?php if (isset($_SESSION['profile_message'])): ?>
                            <div class="alert <?= $_SESSION['profile_message_type'] ?? 'info' ?>">
                                <?= htmlspecialchars($_SESSION['profile_message']) ?>
                            </div>
                            <?php 
                                // Clear the message after displaying
                                unset($_SESSION['profile_message']);
                                unset($_SESSION['profile_message_type']);
                            ?>
                        <?php endif; ?>
                    </div>
                    <h2>Edit Profile</h2>
                    <form id="profileEditForm" action="lendUpdateProfile.php" method="post">
                        <input type="hidden" name="lender_id" value="<?php echo $lender_id; ?>">
                        
                        <div class="form-group">
                            <label for="editName">Full Name</label>
                            <input type="text" id="editName" name="name" value="<?php echo htmlspecialchars($lenderProfile['name']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="editEmail">Email</label>
                            <input type="email" id="editEmail" name="email" value="<?php echo htmlspecialchars($lenderProfile['email']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="editPhone">Phone</label>
                            <input type="tel" id="editPhone" name="phone" value="<?php echo htmlspecialchars($lenderProfile['phone']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="editPhone">Address</label>
                            <input type="text" id="editAddress" name="address" value="<?php echo htmlspecialchars($lenderProfile['address']); ?>">
                        </div>
                        
                
                        
                        <div class="form-actions">
                            <button type="button" id="cancelEditBtn" class="cancel-btn">Cancel</button>
                            <button type="submit" class="save-btn">Save Changes</button>
                        </div>
                    </form>
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
                <div id="dashboard" class="margin">
                    <div class="dash-header">
                        <div>
                            <h1>Lender's Dashboard</h1>
                            <p>Overview of your loans and financial status.</p>
                        </div>
                        <div class="greeting">
                            <p>
                                <code>
                                <!-- Greeting based on time -->
                                <?php
                                    // Set the timezone to local Nairobi, Kenya
                                    date_default_timezone_set('Africa/Nairobi');
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
                            <p>Loan Types Offered</p>
                            <div class="metric-value-container">
                                <span class="span-2"><?php echo $totalOffers; ?></span>
                            </div>
                        </div>
                        <div>
                            <p>Active Loans</p>
                            <div class="metric-value-container">
                            <span class="span-2"><?php echo $activeLoans; ?></span>
                            </div>
                        </div>
                        <div>
                            <p>Disbursed Loans</p>
                            <div class="metric-value-container">
                            <span class="span-2"><?php echo $disbursedLoans; ?></span>
                            </div>
                        </div>
                        <div>
                            <p>Amount Disbursed</p>
                            <div class="metric-value-container">
                                <span class="span-2"><?php echo $totalDisbursedAmount; ?></span>
                            </div>
                        </div>
                        <div>
                            <p>Total Amount Owed</p>
                            <div class="metric-value-container">
                                <span class="span-2"><?php echo $owedCapacity; ?></span>
                            </div>
                        </div>
                        <div>
                            <p>Avg. Interest Rate</p>
                            <div class="metric-value-container">
                                <div class="span-2">
                                    <span class="avg"><?php echo $avgInterestRate; ?></span>
                                    <span class="percentage">%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="visuals">
                        <div>
                        <p>Number of Disbursed Loans per Loan Type</p>
                        <canvas id="barChart" width="800" height="300"></canvas>
                        
                        </div>
                        <div>
                            <p>Loan Status </p>
                            <canvas id="pieChart" width="400" height="200"></canvas>
                             
                            
                        </div>
                    

 
                    </div>
                </div>
            </div>
        </div>


                <!-- PHP page reloads -->
                <iframe name="hiddenFrame" style="display:none;"></iframe>
    </main>
    <script src="../js/validinput.js"></script>

<script>
    // ACTIVE NAV LINK
function updateActiveNavLink() {
    const navLinks = document.querySelectorAll('.nav ul li a');
    const currentHash = window.location.hash || '#dashboard'; // Default to #dashboard if no hash

    // Remove .active class from all links
    navLinks.forEach(link => link.classList.remove('active'));

    // Find the link that matches the current hash and add .active class
    const activeLink = document.querySelector(`.nav ul li a[href="${currentHash}"]`);
    if (activeLink) {
        activeLink.classList.add('active');
    } else {
        // Default to Dashboard if no matching link
        document.querySelector('.nav ul li a[href="#dashboard"]').classList.add('active');
    }
}

// Initialize active link on page load and handle hash changes
document.addEventListener('DOMContentLoaded', function() {
    updateActiveNavLink();

    // Update active link when hash changes
    window.addEventListener('hashchange', updateActiveNavLink);

    // Update active link when navigation links are clicked
    document.querySelectorAll('.nav ul li a').forEach(link => {
        link.addEventListener('click', function() {
            // Remove .active from all links
            document.querySelectorAll('.nav ul li a').forEach(l => l.classList.remove('active'));
            // Add .active to the clicked link
            this.classList.add('active');
        });
    });
});
</script>

    <!-- Loan Requests Filter -->
    <script>
        // Update the iframe reload handler to preserve both filters
window.onload = function() {
    const hiddenFrame = document.getElementsByName('hiddenFrame')[0];
    hiddenFrame.onload = function() {
        // Preserve both filter states when refreshing
        const statusFilter = document.getElementById('status').value;
        const loanTypeFilter = document.getElementById('loan_type').value;
        let url = window.location.href.split('#')[0] + '#loanRequests';
        
        if (statusFilter || loanTypeFilter) {
            url += '?';
            if (statusFilter) url += `status=${encodeURIComponent(statusFilter)}`;
            if (statusFilter && loanTypeFilter) url += '&';
            if (loanTypeFilter) url += `loan_type=${encodeURIComponent(loanTypeFilter)}`;
        }
        
        // Refresh just the loan requests section
        fetch(url)
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newContent = doc.querySelector('#loanRequests').innerHTML;
                document.querySelector('#loanRequests').innerHTML = newContent;
            });
    };
};

// visual feedback when filtering
document.querySelector('#loanRequests form').addEventListener('submit', function(e) {
    const loanRequestsTable = document.querySelector('.loan-requests-table');
    loanRequestsTable.style.opacity = '.2';
    loanRequestsTable.style.transition = 'opacity .3s ease';
    
    setTimeout(() => {
        loanRequestsTable.style.opacity = '1';
    }, 500);
});
    </script>
    <script>
        // Function to hide the loan message after 2 seconds
        function hideLoanMessage() {
            const loanMessage = document.getElementById('loan-message');
            if (loanMessage) {
                setTimeout(() => {
                    loanMessage.style.opacity = '0'; // Fade out the message
                    setTimeout(() => {
                        loanMessage.style.display = 'none'; // Hide the message after fading out
                    }, 700); // Wait for the transition to complete
                }, 2000); // 2000 milliseconds = 2seconds
            }
        }

        // Call the function when the page loads
        window.onload = hideLoanMessage;
    </script>

    <!-- Container metrics overflow handling  -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const metricValues = document.querySelectorAll('.metrics .span-2');
            
            function adjustSizes() {
                metricValues.forEach(span => {
                    // Reset to default size for measurement
                    span.style.fontSize = '';
                    
                    const container = span.closest('.metrics > div');
                    const containerWidth = container.offsetWidth;
                    const textWidth = span.scrollWidth;
                    
                    // Only scale if text overflows (with 5px buffer)
                    if (textWidth > containerWidth - 10) {
                        const scaleRatio = (containerWidth - 10) / textWidth;
                        const newSize = Math.max(2, 4 * scaleRatio); // Never below 2em
                        span.style.fontSize = `${newSize}em`;
                    } else {
                        span.style.fontSize = '4em'; // Reset to original if fits
                    }
                });
            }
        
            // Run on load and resize
            adjustSizes();
            window.addEventListener('resize', adjustSizes);
        });
        </script>




<!-- // Edit Loan Functionality -->
    <script>

        

    // Store original values
    let originalValues = {};

    // Show popup with offer data
    function showEditPopup(offerId, loanType, interestRate, maxAmount, maxDuration) {
        // Store original values
        originalValues = {
            interest_rate: interestRate,
            max_amount: maxAmount,
            max_duration: maxDuration
        };

        // Set form values
        document.getElementById('editOfferId').value = offerId;
        document.getElementById('editLoanType').textContent = loanType; // Display only
        document.getElementById('editInterestRate').value = interestRate;
        document.getElementById('editMaxAmount').value = maxAmount;
        document.getElementById('editMaxDuration').value = maxDuration;
        
        // Show popup
        document.getElementById('popupOverlay').style.display = 'block';
        document.getElementById('editPopup').style.display = 'block';
    }
    
    // Hide popup
    function hideEditPopup() {
        document.getElementById('popupOverlay').style.display = 'none';
        document.getElementById('editPopup').style.display = 'none';
    }
    
    // Handle form submission
    function handleFormSubmit(e) {
        e.preventDefault();
        
        // Create hidden form
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'editLoan.php';
        
        // Add offer ID
        const offerIdInput = document.createElement('input');
        offerIdInput.type = 'hidden';
        offerIdInput.name = 'offer_id';
        offerIdInput.value = document.getElementById('editOfferId').value;
        form.appendChild(offerIdInput);
        
        // Only add changed fields
        const currentInterest = document.getElementById('editInterestRate').value;
        if (currentInterest !== originalValues.interest_rate) {
            const interestInput = document.createElement('input');
            interestInput.type = 'hidden';
            interestInput.name = 'interest_rate';
            interestInput.value = currentInterest;
            form.appendChild(interestInput);
        }
        
        const currentAmount = document.getElementById('editMaxAmount').value;
        if (currentAmount !== originalValues.max_amount) {
            const amountInput = document.createElement('input');
            amountInput.type = 'hidden';
            amountInput.name = 'max_amount';
            amountInput.value = currentAmount;
            form.appendChild(amountInput);
        }
        
        const currentDuration = document.getElementById('editMaxDuration').value;
        if (currentDuration !== originalValues.max_duration) {
            const durationInput = document.createElement('input');
            durationInput.type = 'hidden';
            durationInput.name = 'max_duration';
            durationInput.value = currentDuration;
            form.appendChild(durationInput);
        }
        
        // Submit form
        document.body.appendChild(form);
        form.submit();
    }
    
    // Initialize edit buttons and form
    document.addEventListener('DOMContentLoaded', function() {
    // Attach to only edit buttons (not all .act buttons)
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault(); // Prevent any default behavior
            showEditPopup(
                this.dataset.offerId,
                this.dataset.loanType,
                this.dataset.interestRate,
                this.dataset.maxAmount,
                this.dataset.maxDuration
            );
        });
    });

        // Close when clicking overlay
        document.getElementById('popupOverlay').addEventListener('click', hideEditPopup);
        
        // Attach form submission handler
        document.getElementById('editForm').addEventListener('submit', handleFormSubmit);
    });


</script>

<script>
// Show View Loan popup
function showViewLoanPopup(loanId, customer, loanType, amount, interestRate, duration, 
                         collateralValue, collateralDesc, status, createdAt) {
    // Set values in the popup
    document.getElementById('viewLoanId').textContent = loanId;
    document.getElementById('viewCustomer').textContent = customer;
    document.getElementById('viewLoanType').textContent = loanType;
    document.getElementById('viewAmount').textContent = parseFloat(amount).toLocaleString(undefined, {minimumFractionDigits: 2});
    document.getElementById('viewInterestRate').textContent = interestRate + '%';
    document.getElementById('viewDuration').textContent = duration + ' months';
    document.getElementById('viewCollateralValue').textContent = collateralValue;
    document.getElementById('viewCollateralDesc').textContent = collateralDesc;
    
    // Format status
    const statusElement = document.getElementById('viewStatus');
    statusElement.innerHTML = '';
    const statusBadge = document.createElement('span');
    statusBadge.className = `status-badge status-${status.toLowerCase()}`;
    statusBadge.textContent = status;
    statusElement.appendChild(statusBadge);
    
    // Format date
    const date = new Date(createdAt);
    document.getElementById('viewCreatedAt').textContent = date.toLocaleDateString('en-US', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
    
    // Show popup
    document.getElementById('viewLoanOverlay').style.display = 'block';
    document.getElementById('viewLoanPopup').style.display = 'block';
}

// Hide View Loan popup
function hideViewLoanPopup() {
    document.getElementById('viewLoanOverlay').style.display = 'none';
    document.getElementById('viewLoanPopup').style.display = 'none';
}

// Initialize view buttons
document.addEventListener('DOMContentLoaded', function() {
    // Attach click handlers to all view buttons
    document.querySelectorAll('.btn-view').forEach(btn => {
        btn.addEventListener('click', function() {
            showViewLoanPopup(
                this.dataset.loanId,
                this.dataset.customer,
                this.dataset.loanType,
                this.dataset.amount,
                this.dataset.interestRate,
                this.dataset.duration,
                this.dataset.collateralValue,
                this.dataset.collateralDesc,
                this.dataset.status,
                this.dataset.createdAt
            );
        });
    });

    // Close when clicking overlay
    document.getElementById('viewLoanOverlay').addEventListener('click', hideViewLoanPopup);
});
</script>

<!-- Active Loans -->
<script>
    // View Active Loan Popup
    function showViewActiveLoanPopup(loanId, customer, loanType, amount, interestRate, duration, 
                                   collateralValue, collateralDesc, remainingBalance, createdAt) {
        document.getElementById('viewActiveLoanId').textContent = loanId;
        document.getElementById('viewActiveCustomer').textContent = customer;
        document.getElementById('viewActiveLoanType').textContent = loanType;
        document.getElementById('viewActiveAmount').textContent = parseFloat(amount).toLocaleString(undefined, {minimumFractionDigits: 2});
        document.getElementById('viewActiveInterestRate').textContent = interestRate + '%';
        document.getElementById('viewActiveDuration').textContent = duration + ' months';
        document.getElementById('viewActiveCollateralValue').textContent = parseFloat(collateralValue).toLocaleString(undefined, {minimumFractionDigits: 2});
        document.getElementById('viewActiveCollateralDesc').textContent = collateralDesc;
        document.getElementById('viewActiveRemainingBalance').textContent = parseFloat(remainingBalance).toLocaleString(undefined, {minimumFractionDigits: 2});
        const date = new Date(createdAt);
        document.getElementById('viewActiveCreatedAt').textContent = date.toLocaleDateString('en-US', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
        document.getElementById('viewActiveLoanOverlay').style.display = 'block';
        document.getElementById('viewActiveLoanPopup').style.display = 'block';
    }

    function hideViewActiveLoanPopup() {
        document.getElementById('viewActiveLoanOverlay').style.display = 'none';
        document.getElementById('viewActiveLoanPopup').style.display = 'none';
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.btn-view-active').forEach(btn => {
            btn.addEventListener('click', function() {
                showViewActiveLoanPopup(
                    this.dataset.loanId,
                    this.dataset.customer,
                    this.dataset.loanType,
                    this.dataset.amount,
                    this.dataset.interestRate,
                    this.dataset.duration,
                    this.dataset.collateralValue,
                    this.dataset.collateralDesc,
                    this.dataset.remainingBalance,
                    this.dataset.createdAt
                );
            });
        });
        document.getElementById('viewActiveLoanOverlay').addEventListener('click', hideViewActiveLoanPopup);
    });
</script>

<script>
// Show View Payment popup

function showViewPaymentPopup(paymentId, loanId, customer, loanType, amount, method, type, balance, date) {
    document.getElementById('viewPaymentId').textContent = paymentId;
    document.getElementById('viewPaymentLoanId').textContent = loanId;
    document.getElementById('viewPaymentCustomer').textContent = customer;
    document.getElementById('viewPaymentLoanType').textContent = loanType;
    document.getElementById('viewPaymentAmount').textContent = parseFloat(amount).toLocaleString(undefined, {minimumFractionDigits: 2});
    document.getElementById('viewPaymentMethod').textContent = method.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
    document.getElementById('viewPaymentType').textContent = type;
    
    const paymentDate = new Date(date);
    document.getElementById('viewPaymentDate').textContent = paymentDate.toLocaleDateString('en-US', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
    
    document.getElementById('viewPaymentOverlay').style.display = 'block';
    document.getElementById('viewPaymentPopup').style.display = 'block';
}

// Hide View Payment popup
function hideViewPaymentPopup() {
    document.getElementById('viewPaymentOverlay').style.display = 'none';
    document.getElementById('viewPaymentPopup').style.display = 'none';
}

// Initialize view buttons for payments
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('#paymentReview .view').forEach(btn => {
        btn.addEventListener('click', function() {
            showViewPaymentPopup(
                this.dataset.paymentId,
                this.dataset.loanId,
                this.dataset.customer,
                this.dataset.loanType,
                this.dataset.amount,
                this.dataset.method,
                this.dataset.type,
                this.dataset.balance,
                this.dataset.date
            );
        });
    });

    document.getElementById('viewPaymentOverlay').addEventListener('click', hideViewPaymentPopup);

});
</script>

    <!-- barchart -->
     

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const loanCounts = <?php echo json_encode($loanCounts); ?>;
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
        
        // Calculate Y-axis max (minimum of 5 for visibility)
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
        
        // X-axis labels (abbreviated)
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
    });
</script>


    <!-- pie chart -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const pieCanvas = document.getElementById('pieChart');
        const pieCtx = pieCanvas.getContext('2d');
        
        // Get the actual data from PHP
        const pieData = {
            labels: ['Pending', 'Disbursed', 'Rejected'
            ],
            values: [
                <?php echo round($pieData['pending'], 2); ?>,
                <?php echo round($pieData['disbursed'], 2); ?>,
                <?php echo round($pieData['rejected'], 2); ?>,
                
            ]
        };

        // Define colors for each status
        const statusColors = {
            'Pending': '#ddd',  
            'Disbursed': 'teal',
            'Rejected': 'tomato', 
        };

        // Extract labels and values from pieData
        const labels = pieData.labels;
        const values = pieData.values;

        // Calculate the total for percentage calculations
        const total = values.reduce((sum, value) => sum + value, 0);

        // Draw the pie chart
        let startAngle = 0;
        const centerX = pieCanvas.width / 4;
        const centerY = pieCanvas.height / 2;
        const radius = Math.min(pieCanvas.width / 3, pieCanvas.height / 2) - 10;

        values.forEach((value, index) => {
            if (value > 0) {  // Only draw slices for non-zero values
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
            if (value > 0) {  // Only show legend items for non-zero values
                pieCtx.fillStyle = statusColors[labels[index]];
                pieCtx.fillRect(legendX, legendY, 15, 15);
                pieCtx.fillStyle = 'whitesmoke';
                pieCtx.fillText(`${labels[index]}: ${value.toFixed(1)}%`, legendX + 20, legendY + 12);
                legendY += legendSpacing;
            }
        });

       
    });
</script>

<script>
// Profile Edit Popup Functionality
document.addEventListener('DOMContentLoaded', function() {
    // Show profile edit popup
    document.getElementById('editProfileBtn').addEventListener('click', function() {
        document.getElementById('profileOverlay').style.display = 'block';
        document.getElementById('profilePopup').style.display = 'block';
    });
    
    // Hide profile edit popup
    function hideProfilePopup() {
        document.getElementById('profileOverlay').style.display = 'none';
        document.getElementById('profilePopup').style.display = 'none';
    }
    
    // Close when clicking overlay or cancel button
    document.getElementById('profileOverlay').addEventListener('click', hideProfilePopup);
    document.getElementById('cancelEditBtn').addEventListener('click', hideProfilePopup);
    
    // Prevent form from closing when clicking inside popup
    document.getElementById('profilePopup').addEventListener('click', function(e) {
        e.stopPropagation();
    });
    
    // Handle form submission
    document.getElementById('profileEditForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Perform basic validation
        const name = document.getElementById('editName').value.trim();
        const email = document.getElementById('editEmail').value.trim();
        const phone = document.getElementById('editPhone').value.trim();
        
        if (!name || !email || !phone) {
            alert('Please fill in all required fields');
            return;
        }
        
        // Submit the form
        this.submit();
    });
    
    // Auto-hide success/error message after 3 seconds
    const profileMessage = document.getElementById('profileMessage');
    if (profileMessage && profileMessage.textContent.trim() !== '') {
        setTimeout(() => {
            profileMessage.style.opacity = '0';
            setTimeout(() => {
                profileMessage.style.display = 'none';
            }, 500);
        }, 3000);
    }
});
</script>
</body>
</html>
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

$myconn = mysqli_connect('localhost', 'root', 'figureitout', 'LMSDB');
if (!$myconn) {
    die("Connection failed: " . mysqli_connect_error());
}

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

$lender_id = $_SESSION['lender_id'];


// Define all loan types
$allLoanTypes = [
    "Personal Loan", "Business Loan", "Mortgage Loan", 
    "MicroFinance Loan", "Student Loan", "Construction Loan",
    "Green Loan", "Medical Loan", "Startup Loan", "Agricultural Loan"
];

// Get loan products count
$totalProductsQuery = "SELECT COUNT(*) FROM loan_products WHERE lender_id = '$lender_id'";
$totalProductsResult = mysqli_query($myconn, $totalProductsQuery);
$totalProducts = (int)mysqli_fetch_row($totalProductsResult)[0];

// Get average interest rate
$avgInterestQuery = "SELECT AVG(interest_rate) FROM loan_products WHERE lender_id = '$lender_id'";
$avgInterestResult = mysqli_query($myconn, $avgInterestQuery);
$avgInterestRate = number_format((float)mysqli_fetch_row($avgInterestResult)[0], 2);

// Get total loan capacity
$capacityQuery = "SELECT SUM(max_amount) FROM loan_products WHERE lender_id = '$lender_id'";
$capacityResult = mysqli_query($myconn, $capacityQuery);
$capacityData = mysqli_fetch_row($capacityResult);
$totalCapacity = $capacityData[0] ? number_format((float)$capacityData[0]) : 0;

// Get total APPROVED loans count
$approvedLoansQuery = "SELECT COUNT(*) FROM loans WHERE lender_id = '$lender_id' AND status = 'approved'";
$approvedLoansResult = mysqli_query($myconn, $approvedLoansQuery);
$approvedLoans = (int)mysqli_fetch_row($approvedLoansResult)[0];

// Get total amount disbursed
$disbursedAmountQuery = "SELECT SUM(amount) FROM loans WHERE lender_id = '$lender_id' AND status IN ('approved')";
$disbursedAmountResult = mysqli_query($myconn, $disbursedAmountQuery);
$disbursedAmountData = mysqli_fetch_row($disbursedAmountResult);
$totalDisbursedAmount = $disbursedAmountData[0] ? number_format((float)$disbursedAmountData[0]) : 0;

// Get loan products with their approved loans count
$loanProductsQuery = "SELECT 
                      loan_products.product_id,
                      loan_products.loan_type,
                      loan_products.interest_rate,
                      loan_products.max_amount,
                      loan_products.max_duration,
                      COUNT(loans.loan_id) as approved_count
                    FROM loan_products
                    LEFT JOIN loans ON loan_products.product_id = loans.product_id
                      AND loans.lender_id = '$lender_id'
                      AND loans.status = 'approved'
                    WHERE loan_products.lender_id = '$lender_id'
                    GROUP BY loan_products.product_id, loan_products.loan_type, loan_products.interest_rate, 
                             loan_products.max_amount, loan_products.max_duration";

$loanProductsResult = mysqli_query($myconn, $loanProductsQuery);

// Initialize loan counts
$loanCounts = array_fill_keys($allLoanTypes, 0);
$productsData = [];

if ($loanProductsResult) {
    while ($row = mysqli_fetch_assoc($loanProductsResult)) {
        $loanType = $row['loan_type'];
        $loanCounts[$loanType] = (int)$row['approved_count'];
        
        $productsData[] = [
            'product_id' => $row['product_id'],
            'loan_type' => $loanType,
            'interest_rate' => $row['interest_rate'],
            'max_amount' => $row['max_amount'],
            'max_duration' => $row['max_duration']
        ];
    }
    // Sort the $productsData array by product_id in descending order using funtion usort
    usort($productsData, function($a, $b) {
        return $b['product_id'] - $a['product_id'];
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
    loans.status,
    loans.created_at,
    customers.name,
    loan_products.loan_type
FROM loans
JOIN loan_products ON loans.product_id = loan_products.product_id
JOIN customers ON loans.customer_id = customers.customer_id
WHERE loans.lender_id = '$lender_id'";

// Add status filter if specified
if (!empty($statusFilter) && in_array($statusFilter, ['pending', 'approved', 'rejected'])) {
    $loanRequestsQuery .= " AND loans.status = '$statusFilter'";
}

// loan type filter if specified
if (!empty($loanTypeFilter)) {
    $loanRequestsQuery .= " AND loan_products.loan_type = '$loanTypeFilter'";
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
    'approved' => isset($statusData['approved']) ? ($statusData['approved'] / $totalLoans * 100) : 0,
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
                        <li><a href="#notifications">Notifications</a></li>
                        <li><a href="#profile">Profile</a></li>
                    </div>
                    <div class="bottom">
                        <li><a href="#feedback">Feedback</a></li>
                        <li><a href="#contactSupport">Contact Support</a></li>
                    </div>
                </ul>
            </div>
            <div class="display">
                <!-- Apply for Loan -->
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
                            <h3>Loan Products Information</h3>
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
                                <?php foreach ($productsData as $product): ?>
                                    <tr>        
                                        <td><?php echo htmlspecialchars($product['loan_type']); ?></td>
                                        <td class="mid"><?php echo htmlspecialchars($product['interest_rate']); ?>%</td>
                                        <td class="mid"><?php echo number_format(htmlspecialchars($product['max_amount'])); ?></td>
                                        <td class="mid"><?php echo htmlspecialchars($product['max_duration']); ?> months</td>
                                        <td class="action-buttons">
                                            <!-- Edit Button-->
                                            <button class="act edit-btn" 
                                                    data-product-id="<?= $product['product_id'] ?>" 
                                                    data-loan-type="<?= htmlspecialchars($product['loan_type']) ?>" 
                                                    data-interest-rate="<?= $product['interest_rate'] ?>" 
                                                    data-max-amount="<?= $product['max_amount'] ?>" 
                                                    data-max-duration="<?= $product['max_duration'] ?>">
                                                Edit
                                            </button>
                                            
                                            <!-- Delete Form-->
                                            <form action="deleteLoan.php" method="post" class="del-form">
                                                <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
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
                    <h3>Edit Loan Product</h3>
                    <form class="edit-form" id="editForm" method="post" action="editLoan.php">
                        <input type="hidden" name="product_id" id="editProductId">
                        
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


                <div id="loanRequests" class="margin">
                    <h1>Loan Requests</h1>
                    <p>Loan applications from customers for your loan products.</p>
                    <div class="loan-filter-container">
                        <form method="get" action="#loanRequests">
                            <div class="filter-row">
                                <div class="filter-group">
                                    <label for="status">Status:</label>
                                    <select name="status" id="status">
                                        <option value="">All Statuses</option>
                                        <option value="pending" <?= ($statusFilter === 'pending') ? 'selected' : '' ?>>Pending</option>
                                        <option value="approved" <?= ($statusFilter === 'approved') ? 'selected' : '' ?>>Approved</option>
                                        <option value="rejected" <?= ($statusFilter === 'rejected') ? 'selected' : '' ?>>Rejected</option>
                                    </select>
                                </div>
                                
                                <div class="filter-group">
                                    <label for="loan_type">Loan Type:</label>
                                    <select name="loan_type" id="loan_type">
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
                                
                                <div class="filter-actions">
                                    <button type="submit" class="apply-btn">Apply Filters</button>
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
                                    <th> Rate</th>
                                    <th>Duration</th>
                                    <th>Collateral Value</th>
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
                                        <td><?php echo htmlspecialchars($request['interest_rate']); ?>%</td>
                                        <td style="text-align: center"><?php echo htmlspecialchars($request['duration']); ?></td>
                                        <td style="text-align: center"><?php echo htmlspecialchars($request['collateral_value']); ?></td>
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

                                            <form action="approve_loan.php" method="post" class="inline-form">
                                                <input type="hidden" name="loan_id" value="<?php echo $request['loan_id']; ?>">
                                                <button type="submit" class="btn-approve <?php echo $request['status'] !== 'pending' ? 'disabled' : ''; ?>" 
                                                    <?php echo $request['status'] !== 'pending' ? 'disabled' : ''; ?>>
                                                    Approve
                                                </button>
                                            </form>
                                            <form action="reject_loan.php" method="post" class="inline-form">
                                                <input type="hidden" name="loan_id" value="<?php echo $request['loan_id']; ?>">
                                                <button type="submit" class="btn-reject <?php echo $request['status'] === 'rejected' ? 'disabled' : ''; ?>" 
                                                    <?php echo $request['status'] === 'rejected' ? 'disabled' : ''; ?>>
                                                    Reject
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td style="color: tomato; font-size: 1.2em;" colspan="9" class="no-data">
                                            No loan requests found for your products
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
                <div id="dashboard" >
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
                            <p>Types of Loans Offered</p>
                            <div class="metric-value-container">
                                <span class="span-2"><?php echo $totalProducts; ?></span>
                            </div>
                        </div>
                        <div>
                            <p>Total Loan Capacity</p>
                            <div class="metric-value-container">
                                <span class="span-2"><?php echo $totalCapacity; ?></span>
                            </div>
                        </div>
                        <div>
                            <p>Active Loans</p>
                            <div class="metric-value-container">
                            <span class="span-2"><?php echo $approvedLoans; ?></span>
                            </div>
                        </div>
                        <div>
                            <p>Amount Disbursed</p>
                            <div class="metric-value-container">
                                <span class="span-2"><?php echo $totalDisbursedAmount; ?></span>
                            </div>
                        </div>
                        <div>
                            <p>Average Interest Rate</p>
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
                        <p>Number of Active Loans per Loan Type</p>
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


                <!-- Copyright -->
                <div class="copyright">
                    <p><?php
                        $currentYear = date("Y");
                        echo "&copy; $currentYear";
                        ?>
                        <a href="mailto:innocentmukabwa@gmail.com">dev</a>
                    </p>
                </div>


                <!-- PHP page reloads -->
                <iframe name="hiddenFrame" style="display:none;"></iframe>
    </main>
    <script src="../js/validinput.js"></script>

    <!-- Page Reloads with JS PHP -->
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

    // Show popup with product data
    function showEditPopup(productId, loanType, interestRate, maxAmount, maxDuration) {
        // Store original values
        originalValues = {
            interest_rate: interestRate,
            max_amount: maxAmount,
            max_duration: maxDuration
        };

        // Set form values
        document.getElementById('editProductId').value = productId;
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
        
        // Add product ID
        const productIdInput = document.createElement('input');
        productIdInput.type = 'hidden';
        productIdInput.name = 'product_id';
        productIdInput.value = document.getElementById('editProductId').value;
        form.appendChild(productIdInput);
        
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
                this.dataset.productId,
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
        year: 'numeric'
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
            labels: ['Pending', 'Approved', 'Rejected'
            ],
            values: [
                <?php echo round($pieData['pending'], 2); ?>,
                <?php echo round($pieData['approved'], 2); ?>,
                <?php echo round($pieData['rejected'], 2); ?>,
                
            ]
        };

        // Define colors for each status
        const statusColors = {
            'Pending': '#ddd',  
            'Approved': 'teal',
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
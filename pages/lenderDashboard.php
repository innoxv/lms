<?php
require_once 'lenderDashboardData.php'; // has the dashboard data
?>
<!DOCTYPE html> <!-- Declares the document type and version of HTML (HTML5) -->
<html lang="en"> <!-- Root element of the HTML document; 'lang="en"' specifies the language as English -->
<head>
    <meta charset="UTF-8"> <!-- Sets the character encoding for the document to UTF-8, supporting most characters from all languages -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- 
        The viewport meta tag controls the layout on mobile browsers.
        width=device-width: sets the width of the page to follow the screen-width of the device.
        initial-scale=1.0: sets the initial zoom level when the page is first loaded by the browser.
    -->
    <title>Lender's Dashboard</title> <!-- Sets the title of the page, shown in the browser tab -->
    <link id="themeStylesheet" rel="stylesheet" href="../css/style.css">
    <!-- 
        The link tag links an external CSS stylesheet to the HTML document.
        rel="stylesheet": specifies the relationship as a stylesheet.
        href="../css/style.css": path to the CSS file that styles the page.
    -->
</head>
<body>
    <main>
        <div class="header">
            <div class="header2">
                <div class="logo">loanSqr</div>
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
                    <li><button id="toggleTheme" style="margin-right:1em;cursor:pointer;">Light/Dark Mode</button></li>

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
                            Loan Offers
                            </a>
                        </li>
                        <li>
                        <a href="#loanRequests">Loan Requests
                            <?php if ($loanRequestsCount > 0): // Only show badge if count > 0 ?>
                            <span class="badge"><?php echo $loanRequestsCount;?></span>
                            <?php endif; ?>
                        </a>
                        </li>
                        <li><a href="#activeLoans">Active Loans
                            <?php if ($activeLoans > 0): ?>
                                <span class="badge"><?php echo $activeLoans; ?></span>
                            <?php endif; ?>
                        </a></li>  
                        <li><a href="#paymentReview">Payment Tracking</a></li>  
                        <li><a href="#profile">Profile</a></li>
                    </div>
                    <div class="bottom">
                        <!-- <li><a href="#feedback">Feedback</a></li> -->
                        <li><a href="#contactSupport">Help</a></li>
                                <!-- Copyright -->
                                <div class="copyright">
                                    <div><?php
                                        $currentYear = date("Y");
                                        echo "&copy; $currentYear";
                                        ?>
                                    </div>
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
                                <form id="loanOfferForm" action="createLoan.php" method="post" onsubmit="return validateFormLoans()">
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
                                            <td><label for="maxAmount">Maximum Amount</label></td>
                                            <td><input type="text" id="maxAmount" name="maxAmount"></td>
                                        </tr>
                                        <tr>
                                            <td><label for="maxDuration">Maximum Duration <br>(in months)</label></td>
                                            <td><input type="text" id="maxDuration" name="maxDuration"></td>
                                        </tr>
                                        <tr class="submit-action">
                                            <td></td>
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
                                    <!-- <th>Risk Level</th> -->
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
                                            <span class="status-badge status-<?php echo strtolower(htmlspecialchars($request['status'])); ?>">
                                                <?php echo htmlspecialchars($request['status']); ?>
                                            </span>
                                        </td>
                                        <td style="text-align: center"><?php echo date('j M Y', strtotime($request['application_date'])); ?></td>
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
                                                    data-created-at="<?= htmlspecialchars($request['application_date']) ?>">
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
                                        <td style="color: tomato; font-size: 1.5em;" colspan="9" class="no-data">
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
                            <div class="detail-label">Application <br> Date:</div>
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
                                    <label for="active_due_status">Due Status:</label>
                                    <select name="active_due_status" id="due_status" onchange="this.form.submit()">
                                        <option value="">All</option>
                                        <option value="due" <?php echo (isset($activeFilters['due_status']) && $activeFilters['due_status'] === 'due') ? 'selected' : ''; ?>>Due</option>
                                        <option value="not_due" <?php echo (isset($activeFilters['due_status']) && $activeFilters['due_status'] === 'not_due') ? 'selected' : ''; ?>>Not Due</option>
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
                                    <th>Due Date</th>
                                    <th>Is Due</th>

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
                                            <td><?php echo date('j M Y', strtotime($loan['application_date'])); ?></td>
                                            <td><?php echo date('j M Y', strtotime($loan['due_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($loan['isDue']) == 1 ? 'Yes' : 'No'; ?></td>

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
                                                        data-created-at="<?= htmlspecialchars($loan['application_date']) ?>">
                                                    View
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td style="color: tomato; font-size: 1.5em;" colspan="10" class="no-data">
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
                                <div class="detail-label">Application <br> Date:</div>
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
                
                    <div class="transaction-history-container">
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
                
                    <div class="active-loans-table">
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
                                        <td style="color: tomato; font-size: 1.5em;" colspan="10" class="no-data">
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
                    
                    <!-- Messages -->
                    <?php if (isset($_SESSION['profile_message'])): ?>
                        <div id="profileMessage" class="alert <?= $_SESSION['profile_message_type'] ?? 'info' ?>">
                            <?= htmlspecialchars($_SESSION['profile_message']) ?>
                        </div>
                        <?php 
                        unset($_SESSION['profile_message']);
                        unset($_SESSION['profile_message_type']);
                        ?>
                    <?php endif; ?>

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

                    </div>
                    <h2>Edit Profile</h2>
                    <form id="profileEditForm" action="lendUpdateProfile.php" method="post" onsubmit="return validateLenderProfileForm()">
                        <input type="hidden" name="lender_id" value="<?php echo $lender_id; ?>">
                        
                        <!-- Error Message -->
                        <div id="lender_error" style="color: tomato;font-weight:700"></div>

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
                <!-- Change Password Popup Overlay -->
                <div class="popup-overlay3" id="changePasswordOverlay" style="display: none;">
                    <div class="popup-content3">
                        <h2>Change Password</h2>
                        <form id="changePasswordForm" action="changePassword.php" method="post" onsubmit="return validateChangePasswordForm()" style="text-align: left;">
                            <!-- Error Message -->
                            <div id="password_error" style="color: tomato;font-weight:700"></div>

                            <div class="form-group">
                                <label for="oldPassword">Old Password</label>
                                <input type="password" id="oldPassword" name="old_password" style="text-align: left;">
                            </div>
                            <div class="form-group">
                                <label for="newPassword">New Password</label>
                                <input type="password" id="newPassword" name="new_password" style="text-align: left;">
                            </div>
                            <div class="form-group">
                                <label for="confirmPassword">Confirm Password</label>
                                <input type="password" id="confirmPassword" name="confirm_password" style="text-align: left;">
                            </div>
                            <div class="form-actions">
                                <button type="button" id="cancelChangePassBtn" class="cancel-btn">Cancel</button>
                                <button type="submit" class="save-btn">Change Password</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Contact Support -->
                <div id="contactSupport" class="margin">
                    <div class="dash-header">
                        <div>
                        <h1>Contact Support</h1>
                        </div>
                    </div>
                    <div class="contact-container" id="contactContainer">
                        <div class="contact-info">
                            <h2>Get in Touch</h2>
                            <p>Have questions or need assistance? Our team is here to help.</p>
                            
                            <div class="contact-details">
                                <p><img src="../icons/mail.svg" alt="" srcset=""> <a href="mailto:innocentmukabwa@gmail.com">support@loansqr.com</a></p>
                                <p><img src="../icons/phone.svg" alt="" srcset=""> <a href="tel:+254705036698">+254 705 036 698</a></p>
                                <p><img src="../icons/map2.svg" alt="" srcset=""> <a href="https://www.google.com/maps/search/?api=1&query=125+Ongata+Rongai,+Kajiado,+Kenya" target="_blank" rel="noopener">125 Ongata Rongai, Kajiado, Kenya</a></p>
                            </div>
                            
                            <div class="business-hours">
                                <h3>Business Hours</h3>
                                <p>Monday - Friday: 8:00 AM - 5:00 PM</p>
                                <p>Saturday: 9:00 AM - 1:00 PM</p>
                                <p>Sunday: Closed</p>
                            </div>
                        </div>
                        <div class="social-media">
                            <h2>Social Media Platforms</h2>
                            <div class="social-media-links">
                                <a href="https://www.instagram.com/innoxv/" ><img src="../icons/instagram.svg" alt="">Instagram</a>
                                <a href="https://github.com/innoxv/" ><img src="../icons/github.svg" alt="">Github</a>
                                <a href="https://wa.me/254705036698?text=hi, (wink)" ><img src="../icons/whatsapp.svg" alt="">Whatsapp</a>
                                <a href="" ><img src="../icons/x.svg" alt="">X</a>
                                <a href="" ><img src="../icons/linkedin.svg" alt="">LinkedIn</a>
                            </div>
                        </div>
                    </div>
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
                    <div class="data"> 
                        <div class="metrics">
                            <div>
                                <p>Loan Offers</p>
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
                                <p>Amount Owed</p>
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
                            <canvas id="barChart" width="650" height="300"></canvas>
                            
                            </div>
                            <div>
                                <p>Loan Status </p>
                                <canvas id="pieChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                        </div> 
                </div>
            </div>
        </div>


        <!-- PHP page reloads -->
        <iframe name="hiddenFrame" style="display:none;"></iframe>
    </main>

<!-- input validation javascript -->
<script src="../js/validinput.js"></script>

<!-- lender dashboard javascript -->
<script src="../js/lenderDashboard.js"></script>

<!-- CHART FUNCTIONS -->
<script>
// BAR CHART SECTION
// Initializes and renders a bar chart to display loan counts by loan type when the page loads
document.addEventListener('DOMContentLoaded', function() {
    // Parses JSON data (injected from PHP) into a JavaScript object containing loan counts
    const loanCounts = <?php echo json_encode($loanCounts); ?>;
    // Extracts loan type names as an array from the keys of the loanCounts object
    const loanTypes = Object.keys(loanCounts);
    // Extracts loan count values as an array from the values of the loanCounts object
    const counts = Object.values(loanCounts);
    
    // Gets the canvas element with ID 'barChart' for rendering the chart
    const barCanvas = document.getElementById('barChart');
    // Gets the 2D rendering context of the canvas for drawing
    const barCtx = barCanvas.getContext('2d');
    
    // Clears the entire canvas to remove any previous chart content
    barCtx.clearRect(0, 0, barCanvas.width, barCanvas.height);
    
    // Defines constants for chart dimensions and layout
    const barWidth = 20; // Sets the width of each bar to 20 pixels
    const barSpacing = 20; // Sets the spacing between bars to 20 pixels
    const startX = 30; // Sets the x-coordinate where the first bar starts
    const startY = barCanvas.height - 50; // Sets the y-coordinate of the chart base (50px from bottom)
    const axisPadding = 10; // Sets padding for the y-axis (space for labels)
    
    // Calculates the maximum value for the y-axis, ensuring a minimum of 5 for visibility
    const maxCount = Math.max(5, ...counts);
    // Rounds up the maximum count to the nearest multiple of 5 for y-axis scaling
    const yAxisMax = Math.ceil(maxCount / 5) * 5;
    
    // Draws bars for each loan type
    counts.forEach((value, index) => {
        // Calculates the x-coordinate for the current bar based on its index
        const x = startX + (barWidth + barSpacing) * index;
        // Calculates the height of the bar proportional to the y-axis maximum
        const barHeight = (value / yAxisMax) * (startY - 20);
        // Calculates the y-coordinate for the top of the bar
        const y = startY - barHeight;
        
        // Sets the fill color for the bars to a light blue shade (#74C0FC)
        barCtx.fillStyle = '#74C0FC';
        // Draws a filled rectangle (bar) at the calculated position and size
        barCtx.fillRect(x, y, barWidth, barHeight);
    });
    
    // Draws x-axis labels for loan types (abbreviated)
    barCtx.fillStyle = 'white'; // Sets the text color to white
    barCtx.font = '16px Trebuchet MS'; // Sets the font for labels
    loanTypes.forEach((type, index) => {
        // Creates a short label by taking the first two characters of the loan type and capitalizing them
        const label = type.substring(0, 2).toUpperCase();
        // Calculates the x-coordinate for the label, slightly offset from the bar’s left edge
        const x = startX + (barWidth + barSpacing) * index + barWidth / 5;
        // Draws the label below the bar (20px below the chart base)
        barCtx.fillText(label, x, startY + 20);
    });
    
    // Draws the y-axis line
    barCtx.strokeStyle = 'white'; // Sets the line color to white
    barCtx.beginPath(); // Starts a new drawing path
    barCtx.moveTo(startX - axisPadding, startY); // Moves to the bottom of the y-axis
    barCtx.lineTo(startX - axisPadding, 20); // Draws a line to the top of the y-axis
    barCtx.stroke(); // Renders the y-axis line
    
    // Draws y-axis labels and grid lines
    barCtx.fillStyle = 'whitesmoke'; // Sets the text color to a light gray
    barCtx.textAlign = 'right'; // Aligns text to the right for y-axis labels
    barCtx.font = '16px Trebuchet MS'; // Sets the font for y-axis labels
    
    // Loops through y-axis values, stepping by 2 if max is >10, otherwise by 1
    for (let i = 0; i <= yAxisMax; i += (yAxisMax > 10 ? 2 : 1)) {
        // Calculates the y-coordinate for the current label based on the y-axis scale
        const y = startY - (i / yAxisMax) * (startY - 20);
        // Draws the label to the left of the y-axis, slightly offset vertically
        barCtx.fillText(i, startX - axisPadding - 5, y + 5);
        
        // Draws horizontal grid lines
        barCtx.strokeStyle = 'rgba(255, 255, 255, 0.2)'; // Sets a semi-transparent white for grid lines
        barCtx.beginPath(); // Starts a new drawing path
        barCtx.moveTo(startX - axisPadding, y); // Moves to the start of the grid line
        barCtx.lineTo(barCanvas.width - 240, y); // Draws the grid line across the chart
        barCtx.stroke(); // Renders the grid line
    }
    
    // Draws the legend
    const legendX = barCanvas.width - 240; // Sets the x-coordinate for the legend (right side)
    const legendY = 60; // Sets the starting y-coordinate for the legend
    const legendSpacing = 20; // Sets the vertical spacing between legend items
    
    barCtx.font = '16px Trebuchet MS'; // Sets the font for legend text
    barCtx.textAlign = 'left'; // Aligns legend text to the left
    loanTypes.forEach((type, index) => {
        // Creates a short label for the legend by capitalizing the first two characters
        const label = type.substring(0, 2).toUpperCase();
        barCtx.fillStyle = 'lightgray'; // Sets the text color to light gray
        // Draws the legend text, combining the short label and full loan type
        barCtx.fillText(`${label}: ${type}`, legendX + 20, legendY + index * legendSpacing + 12);
    });
});

// PIE CHART SECTION
// Initializes and renders a pie chart to display loan status distribution when the page loads
document.addEventListener('DOMContentLoaded', function() {
    // Gets the canvas element with ID 'pieChart' for rendering the chart
    const pieCanvas = document.getElementById('pieChart');
    // Gets the 2D rendering context of the canvas for drawing
    const pieCtx = pieCanvas.getContext('2d');

    // Clears the canvas to ensure a fresh drawing surface
    pieCtx.clearRect(0, 0, pieCanvas.width, pieCanvas.height);

    // Defines the pie chart data, injected from PHP as a JSON object
    const pieData = <?= json_encode($pieData) ?>;

    // Defines the labels for loan statuses
    const labels = ['Pending', 'Disbursed', 'Rejected'];
    // Extracts values for each status from the pieData object
    const values = [
        pieData.pending || 0, // Percentage of pending loans, default to 0 if undefined
        pieData.disbursed || 0, // Percentage of disbursed loans, default to 0 if undefined
        pieData.rejected || 0 // Percentage of rejected loans, default to 0 if undefined
    ];

    // Defines colors for each loan status
    const statusColors = {
        'Pending': '#ddd', // Light gray for pending loans
        'Disbursed': 'teal', // Teal for disbursed loans
        'Rejected': 'tomato' // Red-orange for rejected loans
    };

    // Calculates the total sum of all values for proportion calculations
    const total = values.reduce((sum, value) => sum + value, 0);

    // Defines chart configuration for the donut chart
    const centerX = pieCanvas.width / 4; // Sets the x-coordinate of the chart’s center
    const centerY = pieCanvas.height / 2; // Sets the y-coordinate of the chart’s center
    const radius = Math.min(pieCanvas.width / 3, pieCanvas.height / 2) - 10; // Calculates the radius to fit within the canvas
    const lineWidth = 20; // Sets the thickness of the donut rings
    const animationDuration = 1000; // Sets the animation duration in milliseconds
    const startTime = performance.now(); // Records the start time for animation timing

    // Initializes an array to store the current angle for each segment during animation
    const currentAngles = new Array(labels.length).fill(0);
    // Calculates the final angle for each segment based on its proportion of the total
    const finalAngles = values.map(value => (value > 0 ? (2 * Math.PI * value) / total : 0));

    // Defines the animation function to progressively draw the donut chart
    function animate(currentTime) {
        // Clears the canvas on each frame to redraw with updated angles
        pieCtx.clearRect(0, 0, pieCanvas.width, pieCanvas.height);

        // Calculates the animation progress (0 to 1) based on elapsed time
        // Progress is the completion fraction of the animation, ranging from 0 (start) to 1 (end)
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / animationDuration, 1); // Caps progress at 1

        // Draws donut chart slices with animated angles
        let startAngle = 0; // Initializes the starting angle for the current frame
        values.forEach((value, index) => {
            // Only draws a slice if the value is greater than 0
            if (value > 0) {
                // Calculates the current angle for the segment based on animation progress
                currentAngles[index] = progress * finalAngles[index];
                pieCtx.beginPath(); // Starts a new drawing path
                // Draws an arc from the start angle to the current animated angle
                pieCtx.arc(centerX, centerY, radius, startAngle, startAngle + currentAngles[index]);
                pieCtx.lineWidth = lineWidth; // Sets the thickness of the ring
                pieCtx.strokeStyle = statusColors[labels[index]]; // Sets the color for the slice
                pieCtx.stroke(); // Draws the arc as a hollow ring
                // Updates the start angle for the next slice
                startAngle += currentAngles[index];
            }
        });

        // Continues the animation if progress is less than 1
        if (progress < 1) {
            requestAnimationFrame(animate); // Schedules the next animation frame
        } else {
            // Draws the legend for the donut chart after animation completes
            pieCtx.font = '16px Trebuchet MS'; // Sets the font for legend text
            let legendY = 20; // Sets the starting y-coordinate for the legend
            const legendX = centerX + radius + 20; // Sets the x-coordinate for the legend (right of chart)
            const legendSpacing = 20; // Sets the vertical spacing between legend items

            values.forEach((value, index) => {
                // Only includes legend entries for non-zero values
                if (value > 0) {
                    // Draws a colored square for the legend item
                    pieCtx.fillStyle = statusColors[labels[index]];
                    pieCtx.fillRect(legendX, legendY, 15, 15);
                    // Sets the text color to a light gray for readability
                    pieCtx.fillStyle = 'whitesmoke';
                    // Draws the legend text, showing the loan status and percentage (already in pieData)
                    pieCtx.fillText(`${labels[index]}: ${value.toFixed(1)}%`, legendX + 20, legendY + 12);
                    // Moves the y-coordinate down for the next legend item
                    legendY += legendSpacing;
                }
            });
        }
    }

    // Checks if there is valid data to animate
    if (total > 0) {
        // Starts the animation if there are non-zero values
        requestAnimationFrame(animate);
    } else {
        // Draws a static donut chart if no data is available
        let startAngle = 0; // Initializes the starting angle for the first slice
        values.forEach((value, index) => {
            // Only draws a slice if the value is greater than 0
            if (value > 0) {
                // Calculates the angle for the current slice based on its proportion of the total
                const sliceAngle = (2 * Math.PI * value) / total;
                pieCtx.beginPath(); // Starts a new drawing path
                // Draws an arc from the start angle to the end angle with the specified radius
                pieCtx.arc(centerX, centerY, radius, startAngle, startAngle + sliceAngle);
                pieCtx.lineWidth = lineWidth; // Sets the thickness of the ring
                pieCtx.strokeStyle = statusColors[labels[index]]; // Sets the color for the slice
                pieCtx.stroke(); // Draws the arc as a hollow ring
                // Updates the start angle for the next slice
                startAngle += sliceAngle;
            }
        });

        // Draws a filled circle in the center to maintain the donut’s hollow effect
        pieCtx.beginPath();
        pieCtx.arc(centerX, centerY, radius - lineWidth, 0, 2 * Math.PI);
        pieCtx.fillStyle = '#1a2526'; // Matches the canvas background color (adjust as needed)
        pieCtx.fill();

        // Draws the legend for the static donut chart
        pieCtx.font = '16px Trebuchet MS'; // Sets the font for legend text
        let legendY = 20; // Sets the starting y-coordinate for the legend
        const legendX = centerX + radius + 20; // Sets the x-coordinate for the legend (right of chart)
        const legendSpacing = 20; // Sets the vertical spacing between legend items

        values.forEach((value, index) => {
            // Only includes legend entries for non-zero values
            if (value > 0) {
                // Draws a colored square for the legend item
                pieCtx.fillStyle = statusColors[labels[index]];
                pieCtx.fillRect(legendX, legendY, 15, 15);
                // Sets the text color to a light gray for readability
                pieCtx.fillStyle = 'whitesmoke';
                // Draws the legend text, showing the loan status and percentage (already in pieData)
                pieCtx.fillText(`${labels[index]}: ${value.toFixed(1)}%`, legendX + 20, legendY + 12);
                // Moves the y-coordinate down for the next legend item
                legendY += legendSpacing;
            }
        });
    }
});
</script>
<script>
const themeStylesheet = document.getElementById('themeStylesheet');
const toggleBtn = document.getElementById('toggleTheme');

// Set theme on load based on localStorage
if (localStorage.getItem('theme') === 'light') {
    themeStylesheet.href = '../css/style-light.css';
} else {
    themeStylesheet.href = '../css/style.css';
}

// Toggle theme on button click
if (toggleBtn) {
    toggleBtn.addEventListener('click', function() {
        if (themeStylesheet.getAttribute('href').includes('style.css') && !themeStylesheet.getAttribute('href').includes('style-light.css')) {
            themeStylesheet.href = '../css/style-light.css';
            localStorage.setItem('theme', 'light');
        } else {
            themeStylesheet.href = '../css/style.css';
            localStorage.setItem('theme', 'dark');
        }
    });
}
</script>
</body>
</html>
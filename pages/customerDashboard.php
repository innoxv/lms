<?php
// require_once is a PHP statement that includes and evaluates the specified file during script execution.
// It ensures that the file is included only once, even if called multiple times in the same script.
require_once 'customerDashboardData.php'; // has the dashboard data
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
    <title>Customer's Dashboard</title> <!-- Sets the title of the page, shown in the browser tab -->
    <link rel="stylesheet" href="../css/style.css">
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
                <div class="logo">LMS</div>
            </div>
            <div class="header3">
                <ul>
                    <li><a href="logoutbtn.php" id="logout" class="no-col">Log Out</a></li>
                </ul>
            </div>
        </div>
        <div class="customer-content">
            <div class="nav">
                <ul class="nav-split">
                    <div class="top">
                        <li><a href="#dashboard" id="dashboardLink">Dashboard</a></li>
                        <li><a href="#applyLoan" id="applyLoanLink" class="<?php echo ($status === 'restricted_apply') ? 'disabled-link' : ''; ?>">Apply Loan</a></li>
                        <li><a href="#loanHistory" id="loanHistoryLink">Loan History</a></li>
                        <li><a href="#paymentTracking" id="">Payment Tracking</a></li> 
                        <li><a href="#transactionHistory" id="">Transaction History</a></li> 
                        <!-- <li class="disabled-link"><a href="#notifications">Notifications</a></li> -->
                        <li><a href="#profile">Profile</a></li>
                    </div>
                    <div class="bottom">
                        <!-- <li><a href="#feedback">Feedback</a></li> -->
                        <li><a href="#contactSupport">Help</a></li>
                        <div class="copyright">
                            <p> <?php echo "&copy; $currentYear"; ?></p>
                        </div>
                    </div>
                </ul>
            </div>

            <div class="display">
                <!-- Apply for Loan -->
                <div id="applyLoan" class="margin">
                <div class="dash-header">
                        <div>
                        <h1>Apply for Loan</h1>
                        <div>
                        <p> Find a suitable Lender and fill out the form to apply for a new loan.   </p>
                        </div>
                        </div>
                        <div class="greeting">
                            <p>
                                <code>
                                    <span><?php echo $message; ?></span>
                                    <span class="span"><?php echo $_SESSION['user_name']; ?>!</span>
                                </code>
                            </p>
                            <!-- Search Functionality -->
                            <!-- last JS script and searchSuggestions.php-->
                            <div class="search-container">
                                <div style="display:flex; gap:1px;">
                                    <input type="text" id="lenderSearch" placeholder="🔍Search lenders or loan types..." autocomplete="off" value="<?= htmlspecialchars($_SESSION['search_query'] ?? '') ?>">
                                    <button type="button" class="res x" style="outline:1px solid tomato;"><a href="fetchLenders.php?reset_filters=true">X</a></button>
                                </div>          
                                <div id="suggestions" class="suggestions"></div>
                                
                            </div>
                        </div>
                    </div>
                    <!-- Filters -->
                    <div class="loan-right">
                        <div class="loan-filter">
                            <p style="color: whitesmoke; font-weight: 900; line-height: 1;">Filters</p>
                            <form method="GET" action="fetchLenders.php" id="loanFilterForm">
                                <div>
                                    <ul>
                                        <li>
                                            <p>Loan Type</p>
                                            <?php
                                            $current_filters = $_SESSION['current_filters'] ?? [];
                                            $selected_loan_types = $current_filters['loan_types'] ?? (isset($_GET['loan_type']) ? (is_array($_GET['loan_type']) ? $_GET['loan_type'] : [$_GET['loan_type']]) : []);
                                            ?>
                                            <span>
                                                <input type="checkbox" name="loan_type[]" value="Personal Loan" id="personal" <?= in_array('Personal Loan', $selected_loan_types) ? 'checked' : '' ?>>
                                                <label for="personal">Personal</label>
                                            </span>
                                            <span>
                                                <input type="checkbox" name="loan_type[]" value="Business Loan" id="business" <?= in_array('Business Loan', $selected_loan_types) ? 'checked' : '' ?>>
                                                <label for="business">Business</label>
                                            </span>
                                            <span>
                                                <input type="checkbox" name="loan_type[]" value="Mortgage Loan" id="mortgage" <?= in_array('Mortgage Loan', $selected_loan_types) ? 'checked' : '' ?>>
                                                <label for="mortgage">Mortgage</label>
                                            </span>
                                            <span>
                                                <input type="checkbox" name="loan_type[]" value="MicroFinance Loan" id="microfinance" <?= in_array('MicroFinance Loan', $selected_loan_types) ? 'checked' : '' ?>>
                                                <label for="microfinance">MicroFinance</label>
                                            </span>
                                            <span>
                                                <input type="checkbox" name="loan_type[]" value="Student Loan" id="student" <?= in_array('Student Loan', $selected_loan_types) ? 'checked' : '' ?>>
                                                <label for="student">Student</label>
                                            </span>
                                            <span>
                                                <input type="checkbox" name="loan_type[]" value="Construction Loan" id="construction" <?= in_array('Construction Loan', $selected_loan_types) ? 'checked' : '' ?>>
                                                <label for="construction">Construction</label>
                                            </span>
                                            <span>
                                                <input type="checkbox" name="loan_type[]" value="Green Loan" id="green" <?= in_array('Green Loan', $selected_loan_types) ? 'checked' : '' ?>>
                                                <label for="green">Green</label>
                                            </span>
                                            <span>
                                                <input type="checkbox" name="loan_type[]" value="Medical Loan" id="medical" <?= in_array('Medical Loan', $selected_loan_types) ? 'checked' : '' ?>>
                                                <label for="medical">Medical</label>
                                            </span>
                                            <span>
                                                <input type="checkbox" name="loan_type[]" value="Startup Loan" id="startup" <?= in_array('Startup Loan', $selected_loan_types) ? 'checked' : '' ?>>
                                                <label for="startup">Startup</label>
                                            </span>
                                            <span>
                                                <input type="checkbox" name="loan_type[]" value="Agricultural Loan" id="agricultural" <?= in_array('Agricultural Loan', $selected_loan_types) ? 'checked' : '' ?>>
                                                <label for="agricultural">Agricultural</label>
                                            </span>
                                        </li>
                                        <li>
                                            <p>Amount Range</p>
                                            <span class="range">
                                                <div class="input">
                                                    <input type="text" name="min_amount" placeholder="500" min="500" value="<?= htmlspecialchars($current_filters['min_amount'] ?? ($_GET['min_amount'] ?? '')) ?>">
                                                    <span>-</span>
                                                    <input type="text" name="max_amount" placeholder="100000" min="500" value="<?= htmlspecialchars($current_filters['max_amount'] ?? ($_GET['max_amount'] ?? '')) ?>">
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
                                            <?php
                                            $selected_interest = $current_filters['interest_ranges'][0] ?? ($_GET['interest_range'] ?? '');
                                            ?>
                                            <span>
                                                <input type="radio" name="interest_range[]" value="0-5" id="0-5" <?= $selected_interest === '0-5' ? 'checked' : '' ?>>
                                                <label for="0-5">0 - 5%</label>
                                            </span>
                                            <span>
                                                <input type="radio" name="interest_range[]" value="5-10" id="5-10" <?= $selected_interest === '5-10' ? 'checked' : '' ?>>
                                                <label for="5-10">5 - 10%</label>
                                            </span>
                                            <span>
                                                <input type="radio" name="interest_range[]" value="10+" id="10+" <?= $selected_interest === '10+' ? 'checked' : '' ?>>
                                                <label for="10+">10% +</label>
                                            </span>
                                        </li>
                                        <li>
                                            <div class="subres">
                                                <button class="sub" type="submit">Apply Filters</button>
                                                <button type="button" class="res"><a href="fetchLenders.php?reset_filters=true">Reset</a></button>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </form>
                        </div>
                        <!-- Lenders Display functionality -->
                        <div class="loan-lenders" id="lendersContainer">
                            <?php if (isset($_SESSION['filters_applied']) && $_SESSION['filters_applied']): ?>
                                <?php if (!empty($_SESSION['filtered_lenders'])): ?>
                                    <?php foreach ($_SESSION['filtered_lenders'] as $lender): 
                                        $nameParts = explode(' ', $lender['name']);
                                        $initials = '';
                                        foreach ($nameParts as $part) {
                                            if (!empty($part)) {
                                                $initials .= strtoupper($part[0]);
                                            }
                                        }
                                    ?>
                                        <div class="lender">
                                            <span>
                                                <div class="lender-icon"><?= $initials ?></div> 
                                                <?= $lender['name'] ?>
                                            </span>
                                            <span><?= $lender['type'] ?></span>
                                            <span>Rate: <?= $lender['rate'] ?>%</span>
                                            <span>Max Amt: <?= number_format($lender['amount']) ?></span>
                                            <span>Max Dur: <?= $lender['duration'] ?> months</span>
                                            <button class="applynow" 
                                                data-offer="<?= $lender['offer_id'] ?>"
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
                                    <div class="no-results">No lenders match your current filters.</div>
                                <?php endif; ?>
                            <?php else: 
                                $query = "SELECT loan_offers.offer_id, loan_offers.lender_id, loan_offers.loan_type, loan_offers.interest_rate, loan_offers.max_amount, loan_offers.max_duration, lenders.name AS lender_name
                                        FROM loan_offers
                                        JOIN lenders ON loan_offers.lender_id = lenders.lender_id
                                        ORDER BY loan_offers.offer_id DESC";
                                $result = $myconn->query($query);
                                if ($result && $result->num_rows > 0): ?>
                                    <?php while ($lender = $result->fetch_assoc()): 
                                        $nameParts = explode(' ', $lender['lender_name']);
                                        $initials = '';
                                        foreach ($nameParts as $part) {
                                            if (!empty($part)) {
                                                $initials .= strtoupper($part[0]);
                                            }
                                        }
                                    ?>
                                        <div class="lender">
                                            <span>
                                                <div class="lender-icon"><?= $initials ?></div>    
                                                <?= htmlspecialchars($lender['lender_name']) ?>
                                            </span>
                                            <span><?= htmlspecialchars($lender['loan_type']) ?></span>
                                            <span>Rate: <?= $lender['interest_rate'] ?>%</span>
                                            <span>Max Amt: <?= number_format($lender['max_amount']) ?></span>
                                            <span>Max Dur: <?= $lender['max_duration'] ?> months</span>
                                            <button class="applynow" 
                                                data-offer="<?= $lender['offer_id'] ?>"
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

                        <!-- Loan Application Form -->
                        <div class="popup-overlay2" id="loanPopup" style="display: none;">
                            <div class="popup-content">
                                <h2>Loan Application</h2>
                                <?php if (isset($_SESSION['loan_message'])): ?>
                                    <div class="alert <?= $_SESSION['message_type'] ?? 'info' ?>">
                                        <?= htmlspecialchars($_SESSION['loan_message']) ?>
                                    </div>
                                    <?php 
                                    unset($_SESSION['loan_message']);
                                    unset($_SESSION['message_type']);
                                    ?>
                                <?php endif; ?>
                                <!-- enctype="multipart/form-data" ensures the form data, including files, is encoded properly for submission. -->
                                <form id="loanApplicationForm" action="applyLoan.php" method="post" enctype="multipart/form-data" onsubmit="return validateLoanApplicationForm()">
                                    <div class="form-group">
                                        <input type="hidden" id="offerId" name="offer_id">
                                        <input type="hidden" id="lenderId" name="lender_id">
                                        <input type="hidden" id="interestRate" name="interest_rate">
                                    </div>
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
                                    <!-- Error Message -->
                                    <div id="error" style="color: tomato;font-weight:700"></div>

                                    <div class="form-group">
                                        <label for="amountNeeded">Amount Needed:*</label>
                                        <input type="text" id="amountNeeded" name="amount">
                                    </div>
                                    <div class="form-group">
                                        <label for="duration">Duration (months):*</label>
                                        <input type="text" id="duration" name="duration">
                                    </div>
                                    <div class="form-group">
                                        <label for="installments">Monthly Installment:</label>
                                        <input style="border-bottom: none;" type="text" id="installments" name="installments" placeholder="auto-calculated" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label for="collateralValue">Collateral Value:*</label>
                                        <input type="text" id="collateralValue" name="collateral_value">
                                    </div>
                                    <div class="form-group">
                                        <label for="collateralDesc">Collateral Description:*</label>
                                        <textarea id="collateralDesc" name="collateral_description" placeholder="enter a short description" ></textarea>
                                    </div>
                                    <div id="collateralImageGroup">
                                        <div><label for="collateralImage">Collateral Image:*</label></div>
                                        <div><input style="border-bottom: none;"  type="file" id="collateralImage" name="collateral_image" accept="image/*"></div>
                                    </div>
                                    <div class="form-actions">
                                        <button type="reset" class="cancel-btn" id="cancelBtn">Cancel</button>
                                        <button type="submit" class="submit-btn">Submit Application</button>
                                    </div>
                                </form>
                            </div>
                        </div>  
                    </div>   
                </div>


                <!-- Loan History -->
                <div id="loanHistory" class="margin">
                    <div class="dash-header">
                        <div>
                        <h1>Loan History</h1>
                        <p>View your loan history.</p>
                        </div>
                    </div>
                    <div class="loan-filter-container">
                        <form method="get" action="#loanHistory">
                            <div class="filter-row">
                                <div class="filter-group">
                                    <label for="status">Status:</label>
                                    <select name="status" id="status" onchange="this.form.submit()">
                                        <option value="">All Loans</option>
                                        <option value="pending" <?= ($statusFilter === 'pending') ? 'selected' : '' ?>>Pending</option>
                                        <option value="disbursed" <?= ($statusFilter === 'disbursed') ? 'selected' : '' ?>>Disbursed</option>
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
                                        <th>Amount</th>
                                        <th>Interest</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                    <?php foreach ($loans as $loan): ?>
                        <tr>
                            <td><?= htmlspecialchars($loan['loan_id'] ?? '') ?></td>
                            <td><?= htmlspecialchars($loan['loan_type'] ?? '') ?></td>
                            <td><?= htmlspecialchars($loan['lender_name'] ?? '') ?></td>
                            <td><?= number_format($loan['amount'] ?? 0) ?></td>
                            <td><?= htmlspecialchars($loan['interest_rate'] ?? '') ?>%</td>
                            <td>
                                <span class="loan-status <?= strtolower($loan['loan_status'] ?? '') ?>">
                                    <?= htmlspecialchars($loan['loan_status'] ?? '') ?>
                                </span>
                            </td>
                            <td><?= date('j M Y', strtotime($loan['application_date'] ?? 'now')) ?></td>
                            <td>
                                <button class="view-btn" 
                                        data-loan-id="<?= htmlspecialchars($loan['loan_id'] ?? '') ?>"
                                        data-loan-type="<?= htmlspecialchars($loan['loan_type'] ?? '') ?>"
                                        data-lender-name="<?= htmlspecialchars($loan['lender_name'] ?? '') ?>"
                                        data-amount="<?= htmlspecialchars($loan['amount'] ?? '0') ?>"
                                        data-interest-rate="<?= htmlspecialchars($loan['interest_rate'] ?? '0') ?>"
                                        data-duration="<?= htmlspecialchars($loan['duration'] ?? '0') ?>"
                                        data-installments="<?= htmlspecialchars($loan['installments'] ?? '0') ?>"
                                        data-collateral-value="<?= htmlspecialchars($loan['collateral_value'] ?? '0') ?>"
                                        data-collateral-description="<?= htmlspecialchars($loan['collateral_description'] ?? 'Not specified') ?>"
                                        data-status="<?= htmlspecialchars($loan['loan_status'] ?? '') ?>"
                                        data-created-at="<?= htmlspecialchars($loan['application_date'] ?? '') ?>">
                                    View
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                    <div id="loanDetailsPopup" class="popup-overlay3" style="display: none;">
                        <div class="popup-content3">
                            <h2>Loan Details for ID <span id="viewLoanId"></span></h2>
                            <button id="closeLoanPopupBtn" class="close-btn">×</button>
                            <div id="loanDetailsContent" class="popup-body">
                                <div class="loan-details-grid">
                                    <div class="detail-row">
                                        <span class="detail-label">Loan Type:</span>
                                        <span class="detail-value" id="viewLoanType"></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Lender:</span>
                                        <span class="detail-value" id="viewLenderName"></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Amount:</span>
                                        <span class="detail-value" id="viewAmount"></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Interest Rate:</span>
                                        <span class="detail-value" id="viewInterestRate"></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Duration:</span>
                                        <span class="detail-value" id="viewDuration"></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Monthly Installment:</span>
                                        <span class="detail-value" id="viewInstallments"></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Collateral Value:</span>
                                        <span class="detail-value" id="viewCollateralValue"></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Collateral Description:</span>
                                        <span class="detail-value" id="viewCollateralDescription"></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Status:</span>
                                        <span class="detail-value" id="viewStatus"></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Application <br> Date:</span>
                                        <span class="detail-value" id="viewCreatedAt"></span>
                                    </div>
                                </div>
                            </div>
                            <div id="loanActionButtons" class="popup-actions">
                                <!-- Delete button will be added dynamically based on status (ref Javascript) -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Tracking -->
                <div id="paymentTracking" class="margin">
                    <div class="dash-header">
                        <div>
                        <h1>Payment Tracking</h1>
                        <p>View and manage your active loan payments.</p>
                        </div>
                    </div>
                    <!-- Messages -->
                    <?php if (isset($_SESSION['payment_message'])): ?>
                        <div class="alert <?= $_SESSION['payment_message_type'] ?? 'info' ?>">
                            <?= htmlspecialchars($_SESSION['payment_message']) ?>
                        </div>
                        <?php 
                        unset($_SESSION['payment_message']);
                        unset($_SESSION['payment_message_type']);
                        ?>
                    <?php endif; ?>

                    <div class="payment-tracking-container">
                        <form method="get" action="paymentTracking.php">
                            <div class="filter-row">
                                <div class="filter-group">
                                    <label for="payment_status">Payment Status:</label>
                                    <select name="payment_status" id="payment_status" onchange="this.form.submit()">
                                        <option value="">All</option>
                                        <option value="unpaid" <?= ($filters['payment_status'] === 'unpaid') ? 'selected' : '' ?>>Unpaid</option>
                                        <option value="partially_paid" <?= ($filters['payment_status'] === 'partially_paid') ? 'selected' : '' ?>>Partially Paid</option>
                                        <option value="fully_paid" <?= ($filters['payment_status'] === 'fully_paid') ? 'selected' : '' ?>>Fully Paid</option>
                                    </select>
                                </div>
                                <div class="filter-group">
                                    <label for="due_status">Due Status:</label>
                                    <select name="due_status" id="due_status" onchange="this.form.submit()">
                                        <option value="">All</option>
                                        <option value="due" <?= ($filters['due_status'] === 'due') ? 'selected' : '' ?>>Due</option>
                                        <option value="not_due" <?= ($filters['due_status'] === 'not_due') ? 'selected' : '' ?>>Not Due</option>
                                    </select>
                                </div>
                                <div class="filter-group">
                                    <label for="loan_type">Loan Type:</label>
                                    <select name="loan_type" id="loan_type" onchange="this.form.submit()"> 
                                        <option value="">All Types</option>
                                        <?php foreach ($allLoanTypes as $type): ?>
                                            <option value="<?= htmlspecialchars($type) ?>" <?= ($filters['loan_type'] === $type) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($type) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="filter-group">
                                    <label for="amount_range">Loan Amount:</label>
                                    <select name="amount_range" id="amount_range" onchange="this.form.submit()">
                                        <option value="">Any Amount</option>
                                        <option value="0-5000" <?= ($filters['amount_range'] === '0-5000') ? 'selected' : '' ?>>0 - 5,000</option>
                                        <option value="5000-20000" <?= ($filters['amount_range'] === '5000-20000') ? 'selected' : '' ?>>5,000 - 20,000</option>
                                        <option value="20000-50000" <?= ($filters['amount_range'] === '20000-50000') ? 'selected' : '' ?>>20,000 - 50,000</option>
                                        <option value="50000-100000" <?= ($filters['amount_range'] === '50000-100000') ? 'selected' : '' ?>>50,000 - 100,000</option>
                                        <option value="100000+" <?= ($filters['amount_range'] === '100000+') ? 'selected' : '' ?>>100,000+</option>
                                    </select>
                                </div>
                                <div class="filter-group">
                                    <label for="date_range">Application Date:</label>
                                    <select name="date_range" id="date_range" onchange="this.form.submit()">
                                        <option value="">All Time</option>
                                        <option value="today" <?= ($filters['date_range'] === 'today') ? 'selected' : '' ?>>Today</option>
                                        <option value="week" <?= ($filters['date_range'] === 'week') ? 'selected' : '' ?>>This Week</option>
                                        <option value="month" <?= ($filters['date_range'] === 'month') ? 'selected' : '' ?>>This Month</option>
                                        <option value="year" <?= ($filters['date_range'] === 'year') ? 'selected' : '' ?>>This Year</option>
                                    </select>
                                </div>

                                <div class="filter-actions">
                                    <a href="paymentTracking.php?reset=true"><button type="button" class="reset-btn">Reset</button></a>
                                </div>
                            </div>
                        </form>
                        <div class="active-loans-table">
                            <?php if (empty($activeLoans)): ?>
                                <div class="no-lenders">No active loans found</div>
                            <?php else: ?>
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Loan ID</th>
                                            <th>Type</th>
                                            <th>Lender</th>
                                            <th>Amount Due</th>
                                            <th>Installments</th>
                                            <th>Due Date</th>
                                            <th>Is Due</th>
                                            <th>Paid</th>
                                            <th>Balance</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($activeLoans as $loan): ?>
                                            <?php if (!isset($loan['amount']) || !isset($loan['total_amount_due']) || !isset($loan['interest_rate'])) {
                                                error_log("Skipping Loan ID {$loan['loan_id']}: Missing required fields");
                                                continue;
                                            } ?>
                                            <tr>
                                                <td><?= htmlspecialchars($loan['loan_id']) ?></td>
                                                <td><?= htmlspecialchars($loan['loan_type']) ?></td>
                                                <td><?= htmlspecialchars($loan['lender_name']) ?></td>

                                                <td><?= number_format($loan['total_amount_due']) ?></td>
                                                <td><?= number_format($loan['installments']) ?></td>
                                                
                                                <td><?= $loan['due_date'] ? date('j M Y', strtotime($loan['due_date'])) : 'N/A' ?></td>
                                                <td><?= $loan['isDue'] ? 'Yes' : 'No' ?></td>


                                                <td><?= number_format($loan['amount_paid'] ?? 0) ?></td>
                                                <td><?= number_format($loan['remaining_balance'] ?? 0) ?></td>
                                                <td>
                                                    <span class="payment-status <?= htmlspecialchars($loan['payment_status'] ?? 'unpaid') ?>">
                                                        <?= ucfirst(str_replace('_', ' ', htmlspecialchars($loan['payment_status'] ?? 'unpaid'))) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="pay-btn" 
                                                        data-loan-id="<?= $loan['loan_id'] ?>"
                                                        data-loan-amount="<?= $loan['amount'] ?>"
                                                        data-amount-due="<?= $loan['total_amount_due'] ?>"
                                                        data-amount-paid="<?= $loan['amount_paid'] ?? 0 ?>"
                                                        data-remaining-balance="<?= $loan['remaining_balance'] ?? 0 ?>"
                                                        data-installments="<?= $loan['installments'] ?? 0 ?>"
                                                        data-installment-balance="<?= $loan['installment_balance'] ?? 0 ?>"
                                                        
                                                        onclick="showPaymentPopup(this)"
                                                        <?= ($loan['payment_status'] === 'fully_paid') ? 'disabled' : '' ?>> <!-- disables button if status is fully paid -->
                                                        Pay
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Payment Popup -->
                <div class="popup-overlay" id="paymentPopup" style="display: none;">
                    <div class="popup-content3">
                        <h2>Make Payment</h2>
                        <button class="close-btn" onclick="closePaymentPopup()">×</button>
                        <form id="paymentForm" method="post" action="paymentTracking.php#paymentTracking" onsubmit="return validatePaymentForm()">
                            <input type="hidden" name="loan_id" id="payment_loan_id">
                            <input type="hidden" name="remaining_balance" id="payment_remaining_balance">
                            <div class="form-group">
                                <label for="payment_loan_amount">Loan Amount:</label>
                                <input style="border: none;" type="text" id="payment_loan_amount" readonly>
                            </div>
                            <div class="form-group">
                                <label for="payment_amount_due">Due Amount:</label>
                                <input style="border: none;" type="text" id="payment_amount_due" readonly>
                            </div>
                            <div class="form-group">
                                <label for="payment_amount_paid">Paid Amount:</label>
                                <input style="border: none;" type="text" id="payment_amount_paid" readonly>
                            </div>
                            <div class="form-group">
                                <label for="payment_balance">Balance:</label>
                                <input style="border: none;" type="text" id="payment_balance" readonly>
                            </div>
                            <div class="form-group">
                                <label for="payment_installments">Monthly Installments:</label>
                                <input style="border: none;" type="text" id="payment_installments" readonly>
                            </div>
                            <div class="form-group">
                                <label for="payment_installment_balance">Installment Balance:</label>
                                <input style="border: none;" type="text" id="payment_installment_balance" readonly>
                            </div>
                            <!-- Error Message -->
                            <div id="payment_error" style="color: tomato;font-weight:700"></div>

                            <div class="form-group">
                                <label for="payment_amount">Payment Amount:*</label>
                                <input type="text" id="payment_amount" name="amount" min="1" >
                            </div>
                            <div class="form-group">
                                <label for="payment_method">Payment Method:*</label>
                                <select class="select" id="payment_method" name="payment_method" >
                                    <option value="">Select Method</option>
                                    <option value="mpesa">M-Pesa</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="credit_card">Credit Card</option>
                                    <option value="debit_card">Debit Card</option>
                                </select>
                            </div>
                            <div class="form-actions">
                                <button type="button" class="cancel-btn" onclick="closePaymentPopup()">Cancel</button>
                                <button type="submit" name="payment_submit" class="submit-btn">Process Payment</button>
                            </div>
                        </form>
                    </div>
                </div>



                <!-- Transaction History -->
                <div id="transactionHistory" class="margin">
                    <div class="dash-header">
                        <div>
                        <h1>Transaction History</h1>
                        <p>View all your payment transactions.</p>
                        </div>
                    </div>

                    <div class="transaction-history-container">
                        <?php if (isset($_SESSION['trans_error_message'])): ?>
                            <div class="alert error"><?= htmlspecialchars($_SESSION['trans_error_message']) ?></div>
                            <?php unset($_SESSION['trans_error_message']); ?>
                        <?php endif; ?>
                        <form method="get" action="customerDashboard.php#transactionHistory">
                            <div class="filter-row">
                                <div class="filter-group">
                                    <label for="trans_payment_type">Payment Type:</label>
                                    <select name="payment_type" id="trans_payment_type" onchange="this.form.submit()">
                                        <option value="">All</option>
                                        <option value="partial" <?= isset($_GET['payment_type']) && $_GET['payment_type'] === 'partial' ? 'selected' : '' ?>>Partial</option>
                                        <option value="full" <?= isset($_GET['payment_type']) && $_GET['payment_type'] === 'full' ? 'selected' : '' ?>>Full</option>
                                    </select>
                                </div>
                                <div class="filter-group">
                                    <label for="trans_payment_method">Payment Method:</label>
                                    <select name="payment_method" id="trans_payment_method" onchange="this.form.submit()">
                                        <option value="">All Methods</option>
                                        <option value="mpesa" <?= isset($_GET['payment_method']) && $_GET['payment_method'] === 'mpesa' ? 'selected' : '' ?>>M-Pesa</option>
                                        <option value="bank_transfer" <?= isset($_GET['payment_method']) && $_GET['payment_method'] === 'bank_transfer' ? 'selected' : '' ?>>Bank Transfer</option>
                                        <option value="credit_card" <?= isset($_GET['payment_method']) && $_GET['payment_method'] === 'credit_card' ? 'selected' : '' ?>>Credit Card</option>
                                        <option value="debit_card" <?= isset($_GET['payment_method']) && $_GET['payment_method'] === 'debit_card' ? 'selected' : '' ?>>Debit Card</option>
                                    </select>
                                </div>
                                <div class="filter-group">
                                    <label for="trans_amount_range">Amount Range:</label>
                                    <select name="amount_range" id="trans_amount_range" onchange="this.form.submit()">
                                        <option value="">Any Amount</option>
                                        <option value="0-5000" <?= isset($_GET['amount_range']) && $_GET['amount_range'] === '0-5000' ? 'selected' : '' ?>>0 - 5,000</option>
                                        <option value="5000-20000" <?= isset($_GET['amount_range']) && $_GET['amount_range'] === '5000-20000' ? 'selected' : '' ?>>5,000 - 20,000</option>
                                        <option value="20000-50000" <?= isset($_GET['amount_range']) && $_GET['amount_range'] === '20000-50000' ? 'selected' : '' ?>>20,000 - 50,000</option>
                                        <option value="50000-100000" <?= isset($_GET['amount_range']) && $_GET['amount_range'] === '50000-100000' ? 'selected' : '' ?>>50,000 - 100,000</option>
                                        <option value="100000+" <?= isset($_GET['amount_range']) && $_GET['amount_range'] === '100000+' ? 'selected' : '' ?>>100,000+</option>
                                    </select>
                                </div>
                                <div class="filter-group">
                                    <label for="trans_date_range">Payment Date:</label>
                                    <select name="date_range" id="trans_date_range" onchange="this.form.submit()">
                                        <option value="">All Time</option>
                                        <option value="today" <?= isset($_GET['date_range']) && $_GET['date_range'] === 'today' ? 'selected' : '' ?>>Today</option>
                                        <option value="week" <?= isset($_GET['date_range']) && $_GET['date_range'] === 'week' ? 'selected' : '' ?>>This Week</option>
                                        <option value="month" <?= isset($_GET['date_range']) && $_GET['date_range'] === 'month' ? 'selected' : '' ?>>This Month</option>
                                        <option value="year" <?= isset($_GET['date_range']) && $_GET['date_range'] === 'year' ? 'selected' : '' ?>>This Year</option>
                                    </select>
                                </div>
                                <div class="filter-actions">
                                    <a href="paymentHistory.php?reset=true"><button type="button" class="reset-btn">Reset</button></a>
                                </div>
                            </div>
                        </form>
                        <div class="active-loans-table">
                            <?php
                            if (!isset($_SESSION['payment_history'])) {
                                $_SESSION['trans_error_message'] = "Error loading transactions.";
                                echo '<div class="error">Error loading transactions.</div>';
                            } elseif (empty($_SESSION['payment_history'])) {
                                echo '<div class="no-lenders">No transactions found</div>';
                            } else {
                                error_log("customerDashboard.php: Rendering " . count($_SESSION['payment_history']) . " transaction payments");
                            ?>
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Payment ID</th>
                                            <th>Loan ID</th>
                                            <th>Amount</th>
                                            <th>Method</th>
                                            <th>Type</th>
                                            <th>Date</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($_SESSION['payment_history'] as $trans_payment): ?>
                                            <?php
                                            $trans_payment_id = $trans_payment['payment_id'] ?? 'N/A';
                                            $trans_loan_id = $trans_payment['loan_id'] ?? 'N/A';
                                            $trans_amount = $trans_payment['amount'] ?? 0;
                                            $trans_lender_name = $trans_payment['lender_name'] ?? 'Unknown';
                                            $trans_payment_method = $trans_payment['payment_method'] ?? 'unknown';
                                            $trans_payment_type = $trans_payment['payment_type'] ?? 'unknown';
                                            $trans_payment_date = $trans_payment['payment_date'] ?? date('Y-m-d H:i:s');
                                            ?>
                                            <tr>
                                                <td><?= htmlspecialchars($trans_payment_id) ?></td>
                                                <td><?= htmlspecialchars($trans_loan_id) ?></td>
                                                <td><?= number_format($trans_amount, 2) ?></td>
                                                <td><?= htmlspecialchars(ucwords(str_replace('_', ' ', $trans_payment_method))) ?></td>
                                                <td>
                                                    <span class="payment-status <?= htmlspecialchars(strtolower($trans_payment_type)) ?>">
                                                        <?= htmlspecialchars(ucfirst($trans_payment_type)) ?>
                                                    </span>
                                                </td>
                                                <td><?= date('j M Y', strtotime($trans_payment_date)) ?></td>
                                                <td>
                                                    <button class="trans-btn-view" 
                                                            data-trans-payment-id="<?= htmlspecialchars($trans_payment_id) ?>"
                                                            data-trans-loan-id="<?= htmlspecialchars($trans_loan_id) ?>"
                                                            data-trans-lender-name="<?= htmlspecialchars($trans_lender_name) ?>"
                                                            data-trans-amount="<?= htmlspecialchars($trans_amount) ?>"
                                                            data-trans-payment-method="<?= htmlspecialchars($trans_payment_method) ?>"
                                                            data-trans-payment-type="<?= htmlspecialchars($trans_payment_type) ?>"
                                                            data-trans-payment-date="<?= htmlspecialchars($trans_payment_date) ?>">
                                                        View
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="popup-overlay3 trans-payment-details-popup" id="transPaymentDetailsOverlay" style="display: none;">
                        <div class="view-popup" id="transPaymentDetailsPopup" style="display: none;">
                            <h2>Payment Details</h2>
                            <button class="close-btn" id="transClosePaymentDetailsPopupBtn">×</button>
                            <div class="payment-details-grid">
                                <div class="detail-row">
                                    <span class="detail-label">Payment ID:</span>
                                    <span class="detail-value" id="transViewPaymentId"></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Loan ID:</span>
                                    <span class="detail-value" id="transViewLoanId"></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Lender:</span>
                                    <span class="detail-value" id="transViewLenderName"></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Amount:</span>
                                    <span class="detail-value" id="transViewAmount"></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Payment Method:</span>
                                    <span class="detail-value" id="transViewPaymentMethod"></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Payment Type:</span>
                                    <span class="detail-value" id="transViewPaymentType"></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Payment Date:</span>
                                    <span class="detail-value" id="transViewPaymentDate"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Profile -->
                <div id="profile" class="margin">
                    <div class="dash-header">
                        <div>
                        <h1>Profile</h1>
                        <p>View and update your personal information.</p>
                        </div>
                    </div>

                    <div class="profile-container">
                        <div class="profile-details">
                            <h2>Personal Information</h2>
                            <div class="profile-row">
                                <span class="profile-label">Full Name:</span>
                                <span class="profile-value"><?php echo htmlspecialchars($customerProfile['name'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="profile-row">
                                <span class="profile-label">Member Since:</span>
                                <span class="profile-value"><?php echo $customerProfile ? date('j M Y', strtotime($customerProfile['registration_date'])) : 'N/A'; ?></span>
                            </div>
                            <div class="profile-row">
                                <span class="profile-label">Email:</span>
                                <span class="profile-value"><?php echo htmlspecialchars($customerProfile['email'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="profile-row">
                                <span class="profile-label">Phone:</span>
                                <span class="profile-value"><?php echo htmlspecialchars($customerProfile['phone'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="profile-row">
                                <span class="profile-label">Date of Birth:</span>
                                <span class="profile-value"><?php echo $customerProfile ? date('j M Y', strtotime($customerProfile['dob'])) : 'N/A'; ?></span>
                            </div>
                            <div class="profile-row">
                                <span class="profile-label">National ID:</span>
                                <span class="profile-value"><?php echo htmlspecialchars($customerProfile['national_id'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="profile-row">
                                <span class="profile-label">Address:</span>
                                <span class="profile-value"><?php echo htmlspecialchars($customerProfile['address'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="profile-row">
                                <span class="profile-label">Bank Account:</span>
                                <span class="profile-value"><?php echo htmlspecialchars($customerProfile['bank_account'] ?? 'N/A'); ?></span>
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
                <!-- <div id="feedback" class="margin">
                    <h1>Feedback</h1>
                    <p>Share your feedback with us.</p>
                </div> -->

                <!-- Contact Support -->
                <div id="contactSupport" class="margin">
                    <div class="dash-header">
                        <div>
                        <h1>Contact Support</h1>
                        <p>Reach out to our support team for assistance.</p>
                        </div>
                        <div class="greeting">
                            <p>
                                <code>
                                    <span><?php echo $message; ?></span>
                                    <span class="span"><?php echo $_SESSION['user_name']; ?>!</span>
                                </code>
                            </p>
                        </div>
                    </div>

                </div>

                <!-- Dashboard -->
                <div id="dashboard" class="margin">
                    <div class="dash-header">
                        <div>
                            <h1>Customer's Dashboard</h1>
                            <p>Overview of your loans and financial status.</p>
                        </div>
                        <div class="greeting">
                            <p>
                                <code>
                                    <span><?php echo $message; ?></span>
                                    <span class="span"><?php echo $_SESSION['user_name']; ?>!</span>
                                </code>
                            </p>
                        </div>
                    </div>
                    <div class="data">
                        <div class="metrics">
                            <div>
                                <p>Active Loans</p>
                                <div class="metric-value-container">
                                    <span class="span-2"><?php echo $activeLoansCount; ?></span>
                                </div>
                            </div>
                            <div>
                                <p>Disbursed Loans</p>
                                <div class="metric-value-container">
                                    <span class="span-2"><?php echo $totalDisbursedLoans; ?></span>
                                </div>
                            </div>
                            <div>
                                <p>Amount Borrowed</p>
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
                                <p>Number of Disbursed Loans per Loan Type</p>
                                <canvas id="barChart" width="650" height="300"></canvas>
                            </div>
                            <div>
                                <p>Loan Status</p>
                                <canvas id="pieChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

<!--input validation javascript-->
<script src="../js/validinput.js"></script>

<!-- customer dasboard javascript -->
<script src="../js/customerDashboard.js"></script>

<!-- CHART FUNCTIONS -->
<script>
// BAR CHART SECTION
// Initializes and renders a bar chart to display loan counts by loan type
function initializeBarChart() {
    // Parses JSON data (injected from PHP) into a JavaScript object containing loan counts
    const loanCounts = <?= json_encode($loanCounts) ?>;
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
    
    // Calculates the maximum value for the y-axis, ensuring a minimum of 5
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
    
    // Draws x-axis labels for loan types
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
}

// PIE CHART SECTION
// Initializes and renders a pie chart to display loan status distribution
function initializePieChart() {
    // Parses JSON data (injected from PHP) into a JavaScript object containing pie chart data
    const pieData = <?= json_encode($pieData) ?>;
    // Gets the canvas element with ID 'pieChart' for rendering the chart
    const pieCanvas = document.getElementById('pieChart');
    // Gets the 2D rendering context of the canvas for drawing
    const pieCtx = pieCanvas.getContext('2d');
    
    // Defines the labels for the pie chart segments
    const labels = ['Pending', 'Disbursed', 'Rejected'];
    // Extracts values for each status from the pieData object
    const values = [
        pieData.pending, // Number of pending loans
        pieData.disbursed, // Number of disbursed loans
        pieData.rejected // Number of rejected loans
    ];
    
    // Defines colors for each status segment
    const statusColors = {
        'Pending': '#ddd', // Light gray for pending loans
        'Disbursed': 'teal', // Teal for disbursed loans
        'Rejected': 'tomato' // Red-orange for rejected loans
    };

    // Calculates the total sum of all values for percentage calculations
    const total = values.reduce((sum, value) => sum + value, 0);
    // Initializes the starting angle for the first pie slice
    let startAngle = 0;
    // Sets the x-coordinate of the pie chart’s center
    const centerX = pieCanvas.width / 4;
    // Sets the y-coordinate of the pie chart’s center
    const centerY = pieCanvas.height / 2;
    // Calculates the radius of the pie chart, ensuring it fits within the canvas
    const radius = Math.min(pieCanvas.width / 3, pieCanvas.height / 2) - 10;

    // Draws pie chart slices for each status
    values.forEach((value, index) => {
        // Only draws a slice if the value is greater than 0
        if (value > 0) {
            // Calculates the angle for the current slice based on its proportion of the total
            const sliceAngle = (2 * Math.PI * value) / total;
            pieCtx.beginPath(); // Starts a new drawing path
            pieCtx.moveTo(centerX, centerY); // Moves to the center of the pie chart
            // Draws an arc from the start angle to the end angle with the specified radius
            pieCtx.arc(centerX, centerY, radius, startAngle, startAngle + sliceAngle);
            pieCtx.closePath(); // Closes the path to form a slice
            // Sets the fill color for the slice based on the status
            pieCtx.fillStyle = statusColors[labels[index]];
            pieCtx.fill(); // Fills the slice with the specified color
            // Updates the start angle for the next slice
            startAngle += sliceAngle;
        }
    });

    // Draws the legend for the pie chart
    pieCtx.font = '16px Trebuchet MS'; // Sets the font for legend text
    let legendY = 20; // Sets the starting y-coordinate for the legend
    const legendX = centerX + radius + 20; // Sets the x-coordinate for the legend (right of pie)
    const legendSpacing = 20; // Sets the vertical spacing between legend items

    values.forEach((value, index) => {
        // Only includes legend entries for non-zero values
        if (value > 0) {
            // Draws a colored square for the legend item
            pieCtx.fillStyle = statusColors[labels[index]];
            pieCtx.fillRect(legendX, legendY, 15, 15);
            // Sets the text color to a light gray for readability
            pieCtx.fillStyle = 'whitesmoke';
            // Draws the legend text, showing the status label and percentage
            pieCtx.fillText(`${labels[index]}: ${value.toFixed(1)}%`, legendX + 20, legendY + 12);
            // Moves the y-coordinate down for the next legend item
            legendY += legendSpacing;
        }
    });
}
</script>
</body>
</html>
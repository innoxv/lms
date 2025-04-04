<?php
// Start the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to the login page if the user is not logged in
    header("Location: signin.html");
    exit();
}

// Database connection
$myconn = mysqli_connect('localhost', 'root', 'figureitout', 'LMSDB');

// Check connection
if (!$myconn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Fetch user data from the database
$userId = $_SESSION['user_id'];
$query = "SELECT user_name FROM users WHERE user_id = '$userId'";
$result = mysqli_query($myconn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $user = mysqli_fetch_assoc($result);
    $_SESSION['user_name'] = $user['user_name']; // Update the session with the latest data
} else {
    // Handle error if user data is not found
    $_SESSION['user_name'] = "Guest";
}

// Loan History

$userId = $_SESSION['user_id'];
$loansQuery = "SELECT 
    loans.loan_id,
    loan_products.loan_type,
    loans.amount,
    loans.interest_rate,
    loans.status,
    loans.created_at
FROM loans
JOIN loan_products ON loans.product_id = loan_products.product_id
JOIN customers ON loans.customer_id = customers.customer_id
WHERE customers.user_id = ?
ORDER BY loans.created_at DESC";

$stmt = $myconn->prepare($loansQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$loans = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);


// Close the database connection
mysqli_close($myconn);
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
                        <li><a href="#dashboard">Dashboard</a></li>
                        <li><a href="#applyLoan" id="applyLoanLink">Apply for Loan</a></li>
                        <li><a href="#loanHistory" id="loanHistoryLink">Loan History</a></li>
                        <li><a href="#financialSummary">Financial Summary</a></li>
                        <li><a href="#notifications">Notifications</a></li>
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
                                            <input type="number" name="min_amount" placeholder="500" min="500" >
                                            <span>-</span>
                                            <input type="number" name="max_amount" placeholder="100000" min="500" >
                                        </div>
                                        <div>
                                            <div class="quick-amounts">
                                            <button type="button" data-min="1000" data-max="5000">1k-5k</button>
                                            <button type="button" data-min="5000" data-max="20000">5k-20k</button>
                                            <button type="button" data-min="20000" data-max="100000">20k-100k</button>
                                            </div>
                                        </div>
                                        </span>
                                    </li>
                                    <li>
                                        <p>Interest Rates</p>
                                        <span>
                                        <input type="checkbox" name="interest_range[]" value="0-5" id="0-5">
                                        <label for="0-5">0 - 5%</label>
                                        </span>
                                        <span>
                                        <input type="checkbox" name="interest_range[]" value="5-10" id="5-10">
                                        <label for="5-10">5 - 10%</label>
                                        </span>
                                        <span>
                                        <input type="checkbox" name="interest_range[]" value="10+" id="10+">
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
                            <!-- Content will be loaded dynamically - Javascript and Css--> 
                                <div class="loading"></div>
                                <div class="error"></div>
                        </div>

                        <!-- Loan Application Popup -->
                        <div class="popup-overlay2" id="loanPopup">
                            <div class="popup-content">
                                <h2>Loan Application</h2>
                            
                                <!-- Message Reporting -->
                                <div id="loanMessage" class="message-container">
                                    <?php if (isset($_SESSION['loan_message'])): ?>
                                        <div class="alert <?= $_SESSION['message_type'] ?? 'info' ?>">
                                            <?= htmlspecialchars($_SESSION['loan_message']) ?>
                                        </div>
                                        <?php 
                                            // Immediately unset after displaying
                                            unset($_SESSION['loan_message']);
                                            unset($_SESSION['message_type']);
                                        ?>
                                    <?php endif; ?>
                                </div>
                                
                            <!-- Loan Application Form -->
                            <form id="loanApplicationForm">
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
                                    <input type="number" id="amountNeeded" name="amount" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="duration">Duration (months):*</label>
                                    <input type="number" id="duration" name="duration" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="installments">Monthly Installment (KES):</label>
                                    <input type="text" id="installments" name="installments" placeholder="auto-calculated" readonly>
                                </div>
                                
                                <div class="form-group">
                                    <label for="collateralValue">Collateral Value (KES):*</label>
                                    <input type="number" id="collateralValue" name="collateral_value" required >
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
                    <div class="loanhistory" id="loanHistoryContainer">
                        <!-- Content will be loaded dynamically -->
                        <div class="loading">loading...</div>
                    </div>
                </div>

                <!-- Loan Details Popup (hidden by default) -->
                <div id="loanDetailsPopup" class="popup-overlay3" style="display: none;">
                <div class="popup-content3">
                    <h2>Loan Details for ID <span id="popupLoanId"></span></h2>
                    <button id="closePopupBtn" class="close-btn">&times;</button>
                    <div id="loanDetailsContent" class="popup-body"></div>
                </div>
                </div>
                
        
                <!-- Financial Summary -->
                <div id="financialSummary" class="margin">
                    <h1>Financial Summary</h1>
                    <p>Charts and graphs summarizing your financial data.</p>
                </div>

                <!-- Notifications -->
                <div id="notifications" class="margin">
                    <h1>Notifications</h1>
                    <p>View your alerts and reminders.</p>
                </div>

                <!-- Profile -->
                <div id="profile" class="margin">
                    <h1>Profile</h1>
                    <p>Update your personal information and settings.</p>
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
                            <span class="span-2">0</span>
                        </div>
                        <div>
                            <p>Loan Amounts</p>
                            <span class="span-2">0</span>
                        </div>
                        <div>
                            <p>Interest Rates</p>
                            <span class="span-2">0</span>
                        </div>
                        <div>
                            <p>Outstanding Balance</p>
                            <span class="span-2">0</span>
                        </div>
                    </div>
                    <div class="visuals">
                        <div>
                        <canvas id="barChart" width="400" height="200"></canvas>
                        <p>dummy bar graph</p>
                        </div>
                        <div>
                             <canvas id="pieChart" width="400" height="200"></canvas>
                             <p>dummy pie chart</p>
                            
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

<!-- Filter, Lenders Display and Application Popup Logic -->
<script>
// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    loadLenders();
    

    // Reloads lenders page when Apply For Loan is clicked
    document.getElementById('applyLoanLink').addEventListener('click', function(e) {
        e.preventDefault(); // Prevent default anchor behavior
        loadLenders(); // Reload lenders
        window.location.hash = '#applyLoan'; // Update URL hash
    });
    //Reloads loan history when Loan History is clicked
    document.getElementById('loanHistoryLink').addEventListener('click', function(e) {
    e.preventDefault(); // Prevent default anchor behavior
    loadLoanHistory(); // This should call loadLoanHistory, not showLoanDetails
    window.location.hash = '#loanHistory'; // Update URL hash
});


    // Filter form submission
    document.querySelector('.sub').addEventListener('click', function(e) {
        e.preventDefault();
        loadLenders();
    });
    
    // Reset button
    document.querySelector('.res').addEventListener('click', function(e) {
        e.preventDefault();
        document.querySelector('.loan-filter form').reset();
        loadLenders();
    });
    
    // Quick amount buttons
    document.querySelectorAll('.quick-amounts button').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelector('[name="min_amount"]').value = this.dataset.min;
            document.querySelector('[name="max_amount"]').value = this.dataset.max;
            loadLenders();
        });
    });
    
    // Calculate installments when amount or duration changes
    document.getElementById('amountNeeded').addEventListener('input', calculateInstallments);
    document.getElementById('duration').addEventListener('input', calculateInstallments);
    
    // Form submission handler
    document.getElementById('loanApplicationForm').addEventListener('submit', handleLoanSubmission);
    
    // Cancel button
    document.getElementById('cancelBtn').addEventListener('click', function() {
        document.getElementById('loanPopup').style.display = 'none';
    });
    
    // Handle any existing messages on page load
    handleLoanMessage();
});

let loadingStartTime; // Keep track of when loading started

// Load lenders based on filters
function loadLenders() {
    const container = document.getElementById('lendersContainer');
    container.innerHTML = '<div class="loading">loading ...</div>';
    loadingStartTime = Date.now(); // Record start time
    
    const formData = new FormData(document.querySelector('.loan-filter form'));
    const params = new URLSearchParams();
    
    formData.getAll('loan_type[]').forEach(type => params.append('loan_type[]', type));
    formData.getAll('interest_range[]').forEach(range => params.append('interest_range[]', range));
    if (formData.get('min_amount')) params.append('min_amount', formData.get('min_amount'));
    if (formData.get('max_amount')) params.append('max_amount', formData.get('max_amount'));

    fetch(`fetchLenders.php?${params.toString()}`)
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(result => {
            const elapsed = Date.now() - loadingStartTime;
            const remainingDelay = Math.max(0, 1000 - elapsed); // Maintain minimum 1s loading time
            
            setTimeout(() => {
                if (!result.success) throw new Error(result.error || 'Unknown error');
                if (!Array.isArray(result.data)) throw new Error('Invalid data format');
                renderLenders(result.data);
            }, remainingDelay);
        })
        .catch(error => {
            console.error('Error:', error);
            container.innerHTML = `<div class="error">${error.message}</div>`;
        });
}

// Render lenders in the container
function renderLenders(lenders) {
    const container = document.getElementById('lendersContainer');
    
    if (!lenders || !Array.isArray(lenders)) {
        container.innerHTML = '<div class="error">No lenders data available</div>';
        return;
    }
    
    if (lenders.length === 0) {
        container.innerHTML = '<div class="no-lenders">No results matching your filters</div>';
        return;
    }
    
    container.innerHTML = lenders.map(lender => `
        <div class="lender">
            <div class="lender-info">
                <span>${lender.name}</span>
                <span>${lender.type}</span>
                <span>Rate: ${lender.rate}%</span>
                <span>MD: ${lender.duration} months</span>
                <span>MA: ${lender.amount.toLocaleString()} KES</span>
                <a href="alert.html">More Info</a>
            </div>
            <button class="applynow" 
                    data-product-id="${lender.id}"
                    data-lender-id="${lender.lender_id}"
                    data-name="${lender.name}"
                    data-rate="${lender.rate}"
                    data-duration="${lender.duration}"
                    data-amount="${lender.amount}">
                Apply Now
            </button>
        </div>
    `).join('');
    
    // Add click handlers to all Apply Now buttons
    document.querySelectorAll('.applynow').forEach(btn => {
        btn.addEventListener('click', function() {
            showLoanPopup(this);
        });
    });
}

// Show the loan application popup with visible lender info
function showLoanPopup(button) {
    // Set hidden form fields
    document.getElementById('productId').value = button.dataset.productId;
    document.getElementById('lenderId').value = button.dataset.lenderId;
    document.getElementById('interestRate').value = button.dataset.rate;
    
    // Set VISIBLE lender information
    document.getElementById('displayLenderName').textContent = button.dataset.name;
    document.getElementById('displayInterestRate').textContent = button.dataset.rate + '%';
    document.getElementById('displayMaxAmount').textContent = 'KES ' + parseFloat(button.dataset.amount).toLocaleString();
    document.getElementById('displayMaxDuration').textContent = button.dataset.duration + ' months';
    
    // Set max values for validation
    document.getElementById('amountNeeded').max = button.dataset.amount;
    document.getElementById('duration').max = button.dataset.duration;
    
    // Reset form fields
    document.getElementById('amountNeeded').value = '';
    document.getElementById('duration').value = '';
    document.getElementById('installments').value = '';
    document.getElementById('collateralValue').value = '';
    document.getElementById('collateralDesc').value = '';
    
    // Clear any existing messages
    document.getElementById('loanMessage').innerHTML = '';
    
    // Show popup
    document.getElementById('loanPopup').style.display = 'flex';
}

// Calculate monthly installments
function calculateInstallments() {
    const amount = parseFloat(document.getElementById('amountNeeded').value) || 0;
    const duration = parseInt(document.getElementById('duration').value) || 1;
    const rate = parseFloat(document.getElementById('interestRate').value) || 0;
    
    if (amount > 0 && duration > 0 && rate > 0) {
        const monthlyRate = rate / 100 / 12;
        const numerator = amount * monthlyRate * Math.pow(1 + monthlyRate, duration);
        const denominator = Math.pow(1 + monthlyRate, duration) - 1;
        const monthlyInstallment = numerator / denominator;
        
        document.getElementById('installments').value = monthlyInstallment.toFixed(2);
    } else {
        document.getElementById('installments').value = '';
    }
}

// Handle loan application form submission
async function handleLoanSubmission(e) {
    e.preventDefault();
    
    const form = e.target;
    const submitBtn = form.querySelector('.submit-btn');
    const messageDiv = document.getElementById('loanMessage');
    
    submitBtn.disabled = true;
    submitBtn.textContent = 'Processing...';
    messageDiv.innerHTML = ''; // Clear previous messages

    try {
        const formData = new FormData(form);
        
        // Client-side validation
        const required = ['product_id', 'lender_id', 'amount', 'duration', 
                         'collateral_value', 'collateral_description'];
        const missing = [];
        
        required.forEach(field => {
            if (!formData.get(field)) missing.push(field);
        });

        if (missing.length > 0) {
            throw new Error(`Please fill in all required fields: ${missing.join(', ')}`);
        }

        const response = await fetch('applyLoan.php', {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json'
            }
        });

        const result = await response.json();
        
        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Application failed');
        }
        
        // Show success message
        messageDiv.innerHTML = `
            <div class="alert success">${result.message}</div>
        `;
        
        // Hide the popup after a delay
        setTimeout(() => {
            document.getElementById('loanPopup').style.display = 'none';
            window.location.href = result.redirect || 'customerDashboard.php#applyLoan';
        }, 2000);
        
    } catch (error) {
        messageDiv.innerHTML = `
            <div class="alert error">${error.message}</div>
        `;
        console.error('Error:', error);
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Submit Application';
        // Start the message fade-out timer
        handleLoanMessage();
    }
}

// Handle loan message display and fading
function handleLoanMessage() {
    const loanMessage = document.getElementById('loanMessage');
    if (loanMessage) {
        const alert = loanMessage.querySelector('.alert');
        if (alert) {
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => {
                    alert.remove();
                }, 700);
            }, 2000); // Message will fade out after 2 seconds
        }
    }
}
// Load and render loan history with enhanced error handling
function loadLoanHistory() {
    const container = document.getElementById('loanHistoryContainer');
    container.innerHTML = '<div class="loading">loading history...</div>';
    const loadingStart = Date.now();

    fetch('loanHistory.php')
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            // Ensure loading displays for at least 1 seconds
            const elapsed = Date.now() - loadingStart;
            const remainingDelay = Math.max(0, 1000 - elapsed);

            setTimeout(() => {
                if (!data.success) {
                    throw new Error(data.message || 'Failed to load loan history');
                }
                
                if (!data.loans || data.loans.length === 0) {
                    container.innerHTML = '<div class="no-loans">No loan history found</div>';
                    return;
                }
                
                renderLoanHistory(data.loans);
            }, remainingDelay);
        })
        .catch(error => {
            container.innerHTML = `
                <div class="error">
                    <p>Failed to load loan history</p>
                    ${error.message ? `<p class="error-detail">${error.message}</p>` : ''}
                </div>
            `;
        });
}

// Render loan table (unchanged)
function renderLoanHistory(loans) {
    const container = document.getElementById('loanHistoryContainer');
    
    container.innerHTML = `
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
                ${loans.map(loan => `
                <tr>
                    <td>${loan.loan_id}</td>
                    <td>${loan.loan_type}</td> 
                    <td>${loan.lender_name}</td>
                    <td>${loan.amount.toLocaleString()}</td> 
                    <td>${loan.interest_rate}%</td>
                    <td><span class="loan-status ${loan.status.toLowerCase()}">${loan.status}</span></td>
                    <td>${new Date(loan.created_at).toLocaleDateString()}</td>
                    <td><button class="view-btn" onclick="showLoanDetails(${loan.loan_id})">View</button></td>
                </tr>
                `).join('')}
            </tbody>
        </table>
    `;
}

// Show loan details in popup with error handling
function showLoanDetails(loanId) {
    const popup = document.getElementById('loanDetailsPopup');
    const content = document.getElementById('loanDetailsContent');
    
    // Show loading state
    popup.style.display = 'flex';
    
    fetch(`loanHistory.php?loan_id=${loanId}`)
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.message || 'Loan not found');
            }
            
            document.getElementById('popupLoanId').textContent = `${data.loan.loan_id}`;
            content.innerHTML = `
                <p><strong>Type:</strong> ${data.loan.loan_type}</p>
                <p><strong>Lender:</strong> ${data.loan.lender_name || 'N/A'}</p>
                <p><strong>Amount:</strong> KES ${data.loan.amount.toLocaleString()}</p>
                <p><strong>Interest:</strong> ${data.loan.interest_rate}%</p>
                <p><strong>Status:</strong> <span class="loan-status ${data.loan.status.toLowerCase()}">
                    ${data.loan.status}
                </span></p>
                <p><strong>Date:</strong> ${new Date(data.loan.created_at).toLocaleDateString()}</p>
            `;
        })
        .catch(error => {
            content.innerHTML = `
                <div class="error">
                    <p>Failed to load loan details</p>
                    ${error.message ? `<p class="error-detail">${error.message}</p>` : ''}
                </div>
            `;
        });
}

// Close popup 
function closePopup() {
    document.getElementById('loanDetailsPopup').style.display = 'none';
}

// Event listeners 
document.getElementById('closePopupBtn').addEventListener('click', closePopup);
document.getElementById('loanDetailsPopup').addEventListener('click', (e) => {
    if (e.target === document.getElementById('loanDetailsPopup')) {
        closePopup();
    }
});

// Initialize on page load 
document.addEventListener('DOMContentLoaded', () => {
    if (window.location.hash === '#loanHistory') {
        loadLoanHistory();
    }
});
// Attach to form
document.getElementById('loanApplicationForm')?.addEventListener('submit', handleLoanSubmission);
</script>

</body>
</html>
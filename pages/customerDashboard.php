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

// Fetch user data from the database (if needed)
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
                        <li><a href="#applyLoan">Apply for Loan</a></li>
                        <li><a href="#loanHistory">Loan History</a></li>
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
                                        <button class="sub" type="submit">Apply Filters</button>
                                        <button class="res" type="reset">Reset</button>
                                    </li>
                                    </ul>
                                </div>
                                </form>
                        </div>
                        <!-- Loan Lenders display and filter functionality -->
                        <div class="loan-lenders" id="lendersContainer">
                            <!-- Content will be loaded dynamically -->
                                <div class="loading"></div>
                                <div class="error"></div>
                        </div>

                        <!-- Loan Application Popup -->
                    <div class="popup-overlay2" id="loanPopup">
                        <div class="popup-content">
                            <h2>Loan Application</h2>
                            <form id="loanApplicationForm">
                                <div class="form-group">
                                    <label for="lenderName">Lender:</label>
                                    <input type="text" id="lenderName" readonly>
                                </div>
                                
                                <div class="form-group">
                                    <label for="interestRate">Interest Rate (%):</label>
                                    <input type="text" id="interestRate" readonly>
                                </div>
                                
                                <div class="form-group">
                                    <label for="maxDuration">Maximum Duration (months):</label>
                                    <input type="text" id="maxDuration" readonly>
                                </div>
                                
                                <div class="form-group">
                                    <label for="maxAmount">Maximum Amount (KES):</label>
                                    <input type="text" id="maxAmount" readonly>
                                </div>
                                
                                <div class="form-group">
                                    <label for="amountNeeded">Amount Needed (KES):*</label>
                                    <input type="number" id="amountNeeded" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="duration">Duration (months):*</label>
                                    <input type="number" id="duration" required >
                                </div>
                                
                                <div class="form-group">
                                    <label for="installments">Monthly Installment (KES):</label>
                                    <input type="text" id="installments" readonly>
                                </div>
                                
                                <div class="form-group">
                                    <label for="collateralValue">Collateral Value (KES):*</label>
                                    <input type="number" id="collateralValue" required min="1">
                                </div>
                                
                                <div class="form-group">
                                    <label for="collateralDesc">Collateral Description:*</label>
                                    <textarea id="collateralDesc" required  style="padding: 1.5em 3.5em; margin-right: .5em;"></textarea>
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
                    <p>View your past loans and their status.</p>
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
    let loadingStartTime;

function loadLenders() {
    const container = document.getElementById('lendersContainer');
    container.innerHTML = '<div class="loading">loading ...</div>';
    loadingStartTime = Date.now();
    
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
            const remainingDelay = Math.max(0, 1000 - elapsed); //1000ms
            
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
                    data-id="${lender.id}"
                    data-name="${lender.name}"
                    data-rate="${lender.rate}"
                    data-duration="${lender.duration}"
                    data-amount="${lender.amount}">
                Apply Now
            </button>
        </div>
    `).join('');
}

// Show loan popup with lender data
function showLoanPopup(button) {
    const lenderData = {
        name: button.dataset.name,
        rate: button.dataset.rate,
        maxDuration: button.dataset.duration,
        maxAmount: button.dataset.amount
    };
    
    document.getElementById('lenderName').value = lenderData.name;
    document.getElementById('interestRate').value = lenderData.rate;
    document.getElementById('maxDuration').value = lenderData.maxDuration;
    document.getElementById('maxAmount').value = parseFloat(lenderData.maxAmount).toLocaleString();
    
    // Reset form fields
    document.getElementById('amountNeeded').value = '';
    document.getElementById('duration').value = '';
    document.getElementById('installments').value = '';
    document.getElementById('collateralDesc').value = '';
    document.getElementById('collateralValue').value = '';
    
    // Set max values
    document.getElementById('amountNeeded').max = lenderData.maxAmount;
    document.getElementById('duration').max = lenderData.maxDuration;
    
    // Show popup
    document.getElementById('loanPopup').style.display = 'flex';
}

// Calculate monthly installments
function calculateInstallments() {
    const amount = parseFloat(document.getElementById('amountNeeded').value) || 0;
    const duration = parseInt(document.getElementById('duration').value) || 1;
    const interestRate = parseFloat(document.getElementById('interestRate').value) || 0;
    
    if (amount > 0 && duration > 0) {
        const monthlyRate = interestRate / 100 / 12;
        const numerator = amount * monthlyRate * Math.pow(1 + monthlyRate, duration);
        const denominator = Math.pow(1 + monthlyRate, duration) - 1;
        const monthlyInstallment = numerator / denominator;
        
        document.getElementById('installments').value = monthlyInstallment.toFixed(2);
    } else {
        document.getElementById('installments').value = '';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    loadLenders();
    
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
    
    // Apply Now button click
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('applynow')) {
            showLoanPopup(e.target);
        }
    });
    
    // Cancel button
    document.getElementById('cancelBtn').addEventListener('click', function() {
        document.getElementById('loanPopup').style.display = 'none';
    });
    
    // Calculate installments
    document.getElementById('amountNeeded').addEventListener('input', calculateInstallments);
    document.getElementById('duration').addEventListener('input', calculateInstallments);
    
    // Form submission
    document.getElementById('loanApplicationForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            lenderName: document.getElementById('lenderName').value,
            interestRate: document.getElementById('interestRate').value,
            amountNeeded: document.getElementById('amountNeeded').value,
            duration: document.getElementById('duration').value,
            monthlyInstallment: document.getElementById('installments').value,
            collateralDesc: document.getElementById('collateralDesc').value,
            collateralValue: document.getElementById('collateralValue').value
        };
        
        // Here you would send the data to your server
        console.log('Loan application submitted:', formData);
        alert('Loan application submitted successfully!');
        document.getElementById('loanPopup').style.display = 'none';
    });
});
</script>

</body>
</html>
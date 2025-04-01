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
    <title>Administrator Dashboard</title>
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
                    <li><a href="logoutbtn.php" class="no-col">Log Out</a></li>
                </ul>
            </div>
        </div>
        <div class="customer-content">
            <div class="nav">
                <ul class="nav-split">
                    <div class="top">
                        <li><a href="#dashboard">Dashboard</a></li>
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
                

                <!-- Loan History -->
                <div id="loanHistory" class="margin">
                    <h1>Loan History</h1>
                    <p>View past loans and their status.</p>
                </div>

                <!-- Financial Summary -->
                <div id="financialSummary" class="margin">
                    <h1>Financial Summary</h1>
                    <p>Charts and graphs summarizing financial data.</p>
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
                            <h1>Administrator's Dashboard</h1>
                            <p>Overview the systems performance.</p>
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
    <script>
        // Function to handle the active state of sections
function setActiveSection() {
    // Get all the sections
    const sections = document.querySelectorAll('.display > div');
    // Get the current target (the section being displayed)
    const currentTarget = window.location.hash;

    // Remove the active class from all sections
    sections.forEach(section => {
        section.classList.remove('active-section');
    });

    // Add the active class to the current target section
    if (currentTarget) {
        const activeSection = document.querySelector(currentTarget);
        if (activeSection) {
            activeSection.classList.add('active-section');
        }
    } else {
        // If no target, make the dashboard active
        const dashboard = document.getElementById('dashboard');
        if (dashboard) {
            dashboard.classList.add('active-section');
        }
    }
}

// Call the function when the page loads
window.addEventListener('load', setActiveSection);

// Call the function when the hash changes (e.g., when a link is clicked)
window.addEventListener('hashchange', setActiveSection);
    </script>

</body>
</html>
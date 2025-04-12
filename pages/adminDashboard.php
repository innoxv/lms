<?php
// Start the session
session_start();

// error_reporting(E_ALL);
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);



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

// Fetch current user data from the database
$userId = $_SESSION['user_id'];
$query = "SELECT user_name FROM users WHERE user_id = '$userId'";
$result = mysqli_query($myconn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $user = mysqli_fetch_assoc($result);
    $_SESSION['user_name'] = $user['user_name'];
} else {
    $_SESSION['user_name'] = "Guest";
}

// Count active users 
$activeUsersQuery = "SELECT COUNT(*) as active_users FROM users where status='active'";
$activeUsersResult = mysqli_query($myconn, $activeUsersQuery);
$activeUsersCount = 0;
if ($activeUsersResult && mysqli_num_rows($activeUsersResult) > 0) {
    $countData = mysqli_fetch_assoc($activeUsersResult);
    $activeUsersCount = $countData['active_users'];
}
// Count blocked users 
$blockedUsersQuery = "SELECT COUNT(*) as blocked_users FROM users WHERE status='inactive'";
$blockedUsersResult = mysqli_query($myconn, $blockedUsersQuery);
$blockedUsersCount = 0;
if ($blockedUsersResult && mysqli_num_rows($blockedUsersResult) > 0) {
    $countData = mysqli_fetch_assoc($blockedUsersResult);
    $blockedUsersCount = $countData['blocked_users'];
}
// Count total customers
$customersQuery = "SELECT COUNT(*) as total_customers FROM customers";
$customersResult = mysqli_query($myconn, $customersQuery);
$totalCustomers = 0;
if ($customersResult && mysqli_num_rows($customersResult) > 0) {
    $countData = mysqli_fetch_assoc($customersResult);
    $totalCustomers = $countData['total_customers'];
}
// Count total lenders
$lendersQuery = "SELECT COUNT(*) as total_lenders FROM lenders";
$lendersResult = mysqli_query($myconn, $lendersQuery);
$totalLenders = 0;
if ($lendersResult && mysqli_num_rows($lendersResult) > 0) {
    $countData = mysqli_fetch_assoc($lendersResult);
    $totalLenders = $countData['total_lenders'];
}


// Fetch all users from the database for the View Users section
$roleFilter = isset($_GET['role']) ? $_GET['role'] : '';

$usersQuery = "SELECT 
               users.user_id, 
               users.user_name, 
               users.phone, 
               users.role,
               CASE 
                   WHEN users.role = 'Customer' THEN customers.status
                   WHEN users.role = 'Lender' THEN lenders.status
                   ELSE 'active'
               END as status
               FROM users
               LEFT JOIN customers ON users.user_id = customers.user_id AND users.role = 'Customer'
               LEFT JOIN lenders ON users.user_id = lenders.user_id AND users.role = 'Lender'
               ORDER BY users.user_id DESC";
// Add role filter if specified
if (!empty($roleFilter) && in_array($roleFilter, ['Admin', 'Lender', 'Customer'])) {
    $usersQuery = "SELECT 
                   users.user_id, 
                   users.user_name, 
                   users.phone, 
                   users.role,
                   CASE 
                       WHEN users.role = 'Customer' THEN customers.status
                       WHEN users.role = 'Lender' THEN lenders.status
                       ELSE 'active'
                   END as status
                   FROM users
                   LEFT JOIN customers ON users.user_id = customers.user_id AND users.role = 'Customer'
                   LEFT JOIN lenders ON users.user_id = lenders.user_id AND users.role = 'Lender'
                   WHERE users.role = '$roleFilter'
                   ORDER BY users.user_id DESC";
}


$usersResult = mysqli_query($myconn, $usersQuery);


// Initialize users array
$users = [];
if ($usersResult && mysqli_num_rows($usersResult) > 0) {
    while ($row = mysqli_fetch_assoc($usersResult)) {
        $users[] = $row;
    }
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

            <div class="header4">
                <div>
            <!-- reporting -->
            <?php if (isset($_SESSION['admin_message'])): ?>
                <div class="loan-message" id="admin-message">  <!-- loan message class is for styling    -->
                    <?php echo htmlspecialchars($_SESSION['admin_message']); ?>
                    <?php unset($_SESSION['admin_message']); ?>
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
                        <li><a href="#viewUsers">View Users</a></li>
                        <li><a href="#addUsers">Add New User</a></li>
                        <li><a href="#activityLogs">Activity Logs</a></li>
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
                

                <!-- View Users -->
            <div id="viewUsers" class="margin">
                <h1>View and manage Users</h1>
                <p>View all the users and perform necessary activities</p>
                
                <!-- Role Filter Form -->
                <div class="user-filter">
                    <form method="get" action="#viewUsers">
                        <label for="role">Filter by Role:</label>
                        <select name="role" id="role">
                            <option value="">All Users</option>
                            <option value="Admin" <?php echo (isset($_GET['role']) && $_GET['role'] === 'Admin') ? 'selected' : ''; ?>>Admin</option>
                            <option value="Lender" <?php echo (isset($_GET['role']) && $_GET['role'] === 'Lender') ? 'selected' : ''; ?>>Lender</option>
                            <option value="Customer" <?php echo (isset($_GET['role']) && $_GET['role'] === 'Customer') ? 'selected' : ''; ?>>Customer</option>
                        </select>
                        <button type="submit">Apply Filter</button>
                    </form>
                </div>
                

                <?php if (!empty($users)): ?>
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>User ID</th>
                                <th>Username</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Restrictions</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($user['user_id']) ?></td>
                                        <td><?= htmlspecialchars($user['user_name']) ?></td>
                                        <td><?= htmlspecialchars($user['phone']) ?></td>
                                        <td><?= htmlspecialchars($user['role']) ?></td>
                                        <td class="status-<?= 
                                            strpos($user['status'] ?? 'active', 'restricted') !== false ? 'restricted' : 
                                            ($user['status'] === 'inactive' ? 'inactive' : 'active') 
                                        ?>">
                                            <?= ($user['status'] ?? 'active') ?>
                                        </td>
                                        <td>
                                            <?php
                                                $restrictions = [];
                                                if ($user['role'] === 'Customer' && ($user['status'] ?? '') === 'restricted_apply') {
                                                    $restrictions[] = "Cannot apply for loans";
                                                }
                                                if ($user['role'] === 'Lender' && ($user['status'] ?? '') === 'restricted_create') {
                                                    $restrictions[] = "Cannot create loan offers";
                                                }
                                                echo $restrictions ? implode(', ', $restrictions) : 'None';
                                            ?>
                                        </td>

                                        <td class="action-buttons">
    
                                            <!-- Restriction Form -->
                                            <?php
                                                $restrict_btn_visible = true;
                                                if (($user['status'] ?? 'active') === 'inactive' && ($user['status'] ?? '') !== 'restricted_apply' && ($user['status'] ?? '') !== 'restricted_create') {
                                                    $restrict_btn_visible = false;
                                                }
                                            ?>
                                            <?php if ($user['role'] === 'Customer'): ?>
                                                <form method="post" action="user_actions.php">
                                                    <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                                    <input type="hidden" name="action" value="toggle_restriction">
                                                    <input type="hidden" name="restriction_type" value="apply_loan">
                                                    <button type="submit" class="<?= 
                                                        ($user['status'] ?? '') === 'restricted_apply' ? 'unrestrict-btn' : 'restrict-btn' 
                                                    ?>" <?= $restrict_btn_visible ? '' : 'style="display: none;"' ?>>
                                                        <?= ($user['status'] ?? '') === 'restricted_apply' ? 'Permit' : 'Forbid' ?>
                                                    </button>
                                                </form>
                                            <?php elseif ($user['role'] === 'Lender'): ?>
                                                <form method="post" action="user_actions.php">
                                                    <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                                    <input type="hidden" name="action" value="toggle_restriction">
                                                    <input type="hidden" name="restriction_type" value="create_loan">
                                                    <button type="submit" class="<?= 
                                                        ($user['status'] ?? '') === 'restricted_create' ? 'unrestrict-btn' : 'restrict-btn' 
                                                    ?>" <?= $restrict_btn_visible ? '' : 'style="display: none;"' ?>>
                                                        <?= ($user['status'] ?? '') === 'restricted_create' ? 'Permit' : 'Forbid' ?>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        
                                            <!-- Status Form -->
                                            <form method="post" action="user_actions.php">
                                                <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="role" value="<?= $user['role'] ?>">
                                                
                                                <?php if (($user['status'] ?? 'active') === 'active' || 
                                                        strpos($user['status'] ?? '', 'restricted') !== false): ?>
                                                    <button type="submit" name="new_status" value="inactive" class="block-btn">Block</button>
                                                <?php else: ?>
                                                    <button type="submit" name="new_status" value="active" class="unblock-btn">Unblock</button>
                                                <?php endif; ?>
                                            </form>
                                        </td>
                    
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                    </table>
                <?php else: ?>
                        <p>No users found<?= !empty($roleFilter) ? " with role '$roleFilter'" : '' ?>.</p>
                <?php endif; ?>
            </div>



                <!-- Add Users -->
                 <div id="addUsers" class="margin">
                    <h1>Add Users</h1>
                    <p>Add a new user to the system.</p>
    
                    <form action="users.php" id="signupForm" method="post" onsubmit="return validateFormUsers()">
                <table>
                    <!-- Error tag for displaying validation errors -->
                    <div id="error" style="color: tomato; font-weight:700;"></div>
                    <tr>
                        <td><label>Register as?</label></td>
                        <td>
                            <select name="role" id="user-role" class="select">
                                <option value="--select option--" selected>--select option--</option>
                                <option value="Admin">Admin</option>
                                <option value="Customer">Customer</option>
                                <option value="Lender">Lender</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td><label for="firstName">First Name</label></td>
                        <td><input type="text" id="firstName" name="firstName"></td>
                        <td><label for="secondName">Second Name</label></td>
                        <td><input type="text" id="secondName" name="secondName"></td>
                    </tr>
                    <tr>
                        <td><label for="email">Email</label></td>
                        <td><input type="text" id="email" name="email"></td>
                        <td><label for="phone">Phone</label></td>
                        <td><input type="text" id="phone" name="phone"></td>
                    </tr>
                    <tr>
                        <td><label for="address">Address</label></td>
                        <td><input type="text" id="address" name="address"></td>
                        <!-- Fields only relevant to customers are in class hidden -->
                        <td id="customerFields" class="hidden"><label for="dob">Date of Birth</label></td>
                        <td id="customerFields" class="hidden"><input type="text" id="dob" name="dob"></td>
                    </tr>
                    <tr id="customerFields" class="hidden">
                        <td><label for="nationalId">National ID</label></td>
                        <td><input type="text" id="nationalId" name="nationalId"></td>
                        <td><label for="accountNumber">Account No.</label></td>
                        <td><input type="text" id="accountNumber" name="accountNumber"></td>
                    </tr>
                    <tr>
                        <td><label for="password">Password</label></td>
                        <td><input type="password" id="password" name="password"></td>
                    </tr>
                    <tr>
                        <td><label for="confPassword">Confirm <br> Password</label></td>
                        <td><input type="password" id="confPassword" name="confPassword"></td>
                    </tr>
                    <tr class="submit-action">
                        <td><button type="submit" name="submit">REGISTER</button></td>
                    </tr>
                </table>
            </form>
                 </div>
                    
                <!-- Activity Logs -->
                <div id="activityLogs" class="margin">
                    <h1>Activity Logs</h1>
                    <p>View user activity logs.</p>
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
                            <p>Active Users</p>
                            <span class="span-2"><?php echo $activeUsersCount; ?></span>
                        </div>
                        <div>
                            <p>Blocked Users</p>
                            <span class="span-2"><?php echo $blockedUsersCount; ?></span>
                        </div>
                        <div>
                            <p>Total Registered Borrowers</p>
                            <span class="span-2"><?php echo $totalCustomers; ?></span>
                        </div>
                        <div>
                            <p>Total Registered Lenders</p>
                            <span class="span-2"><?php echo $totalLenders; ?></span>
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

                <!-- PHP page reloads -->
                <iframe name="hiddenFrame" style="display:none; border: none; outline:none;"></iframe>

                 
    </main>

    <!-- External JavaScript for validation -->
    <script src="../js/validinput.js"></script>
    
    <script>
    
        // Show/hide fields based on role selection
        const roleDropdown = document.getElementById('user-role');
        const customerFields = document.querySelectorAll('#customerFields');
    
        roleDropdown.addEventListener('change', function () {
            if (roleDropdown.value === 'Customer') {
                customerFields.forEach(field => field.classList.remove('hidden'));
            } else {
                customerFields.forEach(field => field.classList.add('hidden'));
            }
        });
    
        // Initialize visibility on page load
        window.onload = function () {
            if (roleDropdown.value === '--select option--') {
                customerFields.forEach(field => field.classList.remove('hidden'));
            }
        };
    </script>

<script>
        // Function to hide the admin message after 2 seconds
        function hideAdminMessage() {
            const adminMessage = document.getElementById('admin-message');
            if (adminMessage) {
                setTimeout(() => {
                    adminMessage.style.opacity = '0'; // Fade out the message
                    setTimeout(() => {
                        adminMessage.style.display = 'none'; // Hide the message after fading out
                    }, 700); // Wait for the transition to complete
                }, 2000); // 2000 milliseconds = 2seconds
            }
        }

        // Call the function when the page loads
        window.onload = hideAdminMessage;
    </script>

    
</body>
</html>
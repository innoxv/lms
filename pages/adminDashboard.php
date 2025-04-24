<?php
// Start the session
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);



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

// Count total users 
$totalUsersQuery = "SELECT COUNT(*) as total_users FROM users";
$totalUsersResult = mysqli_query($myconn, $totalUsersQuery);
$totalUsersCount = 0;
if ($totalUsersResult && mysqli_num_rows($totalUsersResult) > 0) {
    $countData = mysqli_fetch_assoc($totalUsersResult);
    $totalUsersCount = $countData['total_users'];
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

// Count total admins
$adminsQuery = "SELECT COUNT(*) as total_admins FROM users WHERE role='Admin'";
$adminsResult = mysqli_query($myconn, $adminsQuery);
$totalAdmins = 0;
if ($adminsResult && mysqli_num_rows($adminsResult) > 0) {
    $countData = mysqli_fetch_assoc($adminsResult);
    $totalAdmins = $countData['total_admins'];
}


// Fetch all users from the database for the View Users section
$roleFilter = isset($_GET['role']) ? $_GET['role'] : '';    // role
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';  // status

// Base query
$usersQuery = "SELECT 
               users.user_id, 
               users.user_name, 
               users.email, 
               users.phone, 
               users.role,
               CASE 
                   WHEN users.role = 'Customer' THEN customers.status
                   WHEN users.role = 'Lender' THEN lenders.status
                   ELSE 'active'
               END as status
               FROM users
               LEFT JOIN customers ON users.user_id = customers.user_id AND users.role = 'Customer'
               LEFT JOIN lenders ON users.user_id = lenders.user_id AND users.role = 'Lender'";

// Build WHERE conditions
$whereConditions = [];

// Role filter
if (!empty($roleFilter) && in_array($roleFilter, ['Admin', 'Lender', 'Customer'])) {
    $whereConditions[] = "users.role = '$roleFilter'";
}

// Status filter
if (!empty($statusFilter)) {
    if ($statusFilter === 'active') {
        $whereConditions[] = "(customers.status = 'active' OR lenders.status = 'active' OR users.role = 'Admin')";
    } 
    elseif ($statusFilter === 'restricted') {
        $whereConditions[] = "(customers.status LIKE '%restricted%' OR lenders.status LIKE '%restricted%')";
    } 
    elseif ($statusFilter === 'blocked') {
        $whereConditions[] = "(customers.status = 'inactive' OR lenders.status = 'inactive')";
    }
}

// Combine WHERE conditions
if (!empty($whereConditions)) {
    $usersQuery .= " WHERE " . implode(' AND ', $whereConditions);
}

// Add sorting in descending order based on the user_id
$usersQuery .= " ORDER BY users.user_id DESC";

$usersResult = mysqli_query($myconn, $usersQuery);


// Initialize users array
$users = [];
if ($usersResult && mysqli_num_rows($usersResult) > 0) {
    while ($row = mysqli_fetch_assoc($usersResult)) {
        $users[] = $row;
    }
}


// Fetch activity logs 
// Get activity type filter
$activityFilter = isset($_GET['activity_type']) ? $_GET['activity_type'] : '';

// Activity log query
$activityQuery = "SELECT 
    activity.log_id, 
    users.user_name, 
    users.email,
    activity.activity, 
    activity.activity_time, 
    activity.activity_type
FROM activity
JOIN users ON activity.user_id = users.user_id";

// Add activity type filter if specified
if (!empty($activityFilter)) {
    $activityQuery .= " WHERE activity.activity_type = '$activityFilter'";
}

$activityQuery .= " ORDER BY activity.activity_time DESC";

$activityResult = mysqli_query($myconn, $activityQuery);

// Initialize activity logs array
$activityLogs = [];
if ($activityResult && mysqli_num_rows($activityResult) > 0) {
    while ($row = mysqli_fetch_assoc($activityResult)) {
        $activityLogs[] = $row;
    }
}


// Recent Activity Logs - for the Dashboard

$recentActivityQuery = "SELECT 
    activity.log_id, 
    users.email,
    activity.activity_time, 
    activity.activity_type
FROM activity
JOIN users ON activity.user_id = users.user_id
ORDER BY activity.activity_time DESC
LIMIT 10"; // Limit to 10 most recent logs

$recentActivityResult = mysqli_query($myconn, $recentActivityQuery);

// Initialize activity logs array
$recentActivityLogs = [];
if ($recentActivityResult && mysqli_num_rows($recentActivityResult) > 0) {
    while ($row = mysqli_fetch_assoc($recentActivityResult)) {
        $recentActivityLogs[] = $row;
    }
}


// Pie chart data
$roleQuery = "SELECT role, COUNT(*) as count FROM users GROUP BY role";
$roleResult = mysqli_query($myconn, $roleQuery);

$roleData = [];
$totalUsers = 0;
while ($row = mysqli_fetch_assoc($roleResult)) {
    $roleData[$row['role']] = (int)$row['count'];
    $totalUsers += (int)$row['count'];
}

// Calculate percentages for pie chart
$pieData = [
    'Admin' => isset($roleData['Admin']) ? ($roleData['Admin'] / $totalUsers * 100) : 0,
    'Customer' => isset($roleData['Customer']) ? ($roleData['Customer'] / $totalUsers * 100) : 0,
    'Lender' => isset($roleData['Lender']) ? ($roleData['Lender'] / $totalUsers * 100) : 0
];



// Fetch admin profile data
$adminProfileQuery = "SELECT * FROM users WHERE user_id = '$userId'";
$adminProfileResult = mysqli_query($myconn, $adminProfileQuery);
$adminProfile = mysqli_fetch_assoc($adminProfileResult);

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
                        <li class="disabled-link"><a href="#notifications">Notifications</a></li>  <!-- this is still in production -->
                        <li><a href="#profile">Profile</a></li>
                    </div>
                    <div class="bottom">
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

            <!-- Dynamic display enabled by CSS -->
            <div class="display">
                

                <!-- View Users -->
            <div id="viewUsers" class="margin">
                <h1>View and manage Users</h1>
                <p>View all the users and perform necessary activities</p>
                
                <!-- Role Filter Form -->
                <div class="user-filter">
                    <form method="get" action="#viewUsers">
                    <div class="filter-row">

                        <div class="filter-group">
                        <label for="role">Role:</label>
                        <select name="role" id="role" onchange="this.form.submit()">  <!-- this submits the form on select -->
                            <option value="">All Roles</option>
                            <option value="Admin" <?= $roleFilter==='Admin'?'selected':'' ?>>Admin</option>
                            <option value="Lender" <?= $roleFilter==='Lender'?'selected':'' ?>>Lender</option>
                            <option value="Customer" <?= $roleFilter==='Customer'?'selected':'' ?>>Customer</option>
                        </select>
                        </div>
                        <div class="filter-group">
                        <label for="status">Status:</label>
                        <select name="status" id="status2" onchange="this.form.submit()">  <!-- this submits the form on select -->
                            <option value="">All Statuses</option>
                            <option value="active" <?= $statusFilter==='active'?'selected':'' ?>>Active</option>
                            <option value="restricted" <?= $statusFilter==='restricted'?'selected':'' ?>>Restricted</option>
                            <option value="blocked" <?= $statusFilter==='blocked'?'selected':'' ?>>Blocked</option>
                        </select>
                        </div>
                        <a href="adminDashboard.php#viewUsers"><button type="button" class="reset">Reset</button></a>
                    </div>
                    </form>
                </div>
                

                <?php if (!empty($users)): ?>
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>User ID</th>
                                <th>Username</th>
                                <th>Email</th>
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
                                        <td><?= htmlspecialchars($user['email']) ?></td>
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
                        <p style="color: tomato; font-size: 1.2em;">No users found<?= !empty($roleFilter) ? " with role $roleFilter" : '' ?>.</p>
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
                                <td id="customerFields" class="hidden"><input type="text" placeholder="dd-mm-yyyy" id="dob" name="dob"></td>
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
                    
                    <!-- Activity Type Filter -->
                    <div class="activity-filter">
                        <form method="get" action="#activityLogs">
                            <label for="activity_type">Filter:</label>
                            <select name="activity_type" id="activity_type" onchange="this.form.submit()">
                                <option value="">All Activities</option>
                                
                                <optgroup label="Authentication">
                                    <option value="login" <?= $activityFilter==='login'?'selected':'' ?>>Log In</option>
                                    <option value="logout" <?= $activityFilter==='logout'?'selected':'' ?>>Log Out</option>
                                    <option value="failed login" <?= $activityFilter==='failed login'?'selected':'' ?>>Failed Log In</option>
                                </optgroup>
                                
                                <optgroup label="Loan Activities">
                                    <option value="loan application" <?= $activityFilter==='loan application'?'selected':'' ?>>Loan Application</option>
                                    <option value="application deletion" <?= $activityFilter==='application deletion'?'selected':'' ?>>Application Deletion</option>
                                    <option value="loan approval" <?= $activityFilter==='loan approval'?'selected':'' ?>>Loan Approval</option>
                                    <option value="loan rejection" <?= $activityFilter==='loan rejection'?'selected':'' ?>>Loan Rejection</option>
                                    <option value="loan offer creation" <?= $activityFilter==='loan offer creation'?'selected':'' ?>>Loan Offer Creation</option>
                                    <option value="loan offer edit" <?= $activityFilter==='loan offer edit'?'selected':'' ?>>Loan Offer Edit</option>
                                    <option value="loan offer deletion" <?= $activityFilter==='loan offer deletion'?'selected':'' ?>>Loan Offer Deletion</option>

                                </optgroup>
                                
                                <optgroup label="User Management">
                                    <option value="account registration" <?= $activityFilter==='account registration'?'selected':'' ?>>Account Registration</option>
                                    <option value="user registration" <?= $activityFilter==='user registration'?'selected':'' ?>>User Registration</option>
                                    <option value="profile update" <?= $activityFilter==='profile update'?'selected':'' ?>>Profile Update</option>
                                    <option value="user restriction" <?= $activityFilter==='user restriction'?'selected':'' ?>>User Restriction</option>
                                    <option value="user block" <?= $activityFilter==='user block'?'selected':'' ?>>User Block</option>
                                    <option value="user unblock" <?= $activityFilter==='user unblock'?'selected':'' ?>>User Unblock</option>
                                </optgroup>
                            </select>
                            <a href="adminDashboard.php#activityLogs"><button type="button" class="reset">Reset</button></a>
                        </form>
                    </div>
    
                    <!-- Activity Logs Display -->
                    <?php if (!empty($activityLogs)): ?>
                        <div class="activity-logs-container">
                            <table class="activity-table">
                                <thead>
                                    <tr>
                                        <th>Log ID</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Activity</th>
                                        <th>Type</th>
                                        <th>Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($activityLogs as $log): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($log['log_id']) ?></td>
                                            <td><?= htmlspecialchars($log['user_name']) ?></td>
                                            <td><?= htmlspecialchars($log['email']) ?></td>
                                            <td><?= htmlspecialchars($log['activity']) ?></td>
                                            <td class="log-type-<?= strtolower($log['activity_type']) ?>">
                                                <?= htmlspecialchars($log['activity_type']) ?>
                                            </td>
                                            <td><?= date('M j, Y G:i ', strtotime($log['activity_time'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No activity logs found.</p>
                    <?php endif; ?>
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
                                <span class="profile-value"><?php echo htmlspecialchars($adminProfile['user_name']); ?></span>
                            </div>
                            <div class="profile-row">
                                <span class="profile-label">Email:</span>
                                <span class="profile-value"><?php echo htmlspecialchars($adminProfile['email']); ?></span>
                            </div>
                            <div class="profile-row">
                                <span class="profile-label">Phone:</span>
                                <span class="profile-value"><?php echo htmlspecialchars($adminProfile['phone']); ?></span>
                            </div>
                            
                            <button id="editProfileBtn" style="cursor:not-allowed;">Edit Profile</button>
                            
                        </div>
                        <div class="additional-settings">
                                <h2>Additional Settings</h2>
                                <p class="change">Change Password</p>
                                <p class="delete">Delete Account</p>
                            </div>
                    </div>
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
                    <div class="metrics" style="height:unset;">
                    <div>
                            <p>Total Users</p>
                            <span class="span-2"><?php echo $totalUsersCount; ?></span>
                        </div>
                        <div>
                            <p>Active Users</p>
                            <span class="span-2"><?php echo $activeUsersCount; ?></span>
                        </div>
                        <div>
                            <p>Blocked Users</p>
                            <span class="span-2"><?php echo $blockedUsersCount; ?></span>
                        </div>
                        <div>
                            <p>Total Borrowers</p>
                            <span class="span-2"><?php echo $totalCustomers; ?></span>
                        </div>
                        <div>
                            <p>Total Lenders</p>
                            <span class="span-2"><?php echo $totalLenders; ?></span>
                        </div>
                        <div>
                            <p>Total Admins</p>
                            <span class="span-2"><?php echo $totalAdmins; ?></span>
                        </div>
                    </div>
                    <div class="admin-visuals">
                        <div class="visuals-left">
                            <p><span>Recent Activities</span><span><a href="#activityLogs"><button>View All &#9660</button></a></span></p>
                            <div class="recent-activities">
                                <?php if (!empty($recentActivityLogs)): ?>
                                <div class="activity-logs-container">
                                    <table class="recent-activity-table">
                                        <thead>
                                            <tr>
                                                <th>Log ID</th>
                                                <th>Email</th>
                                                <th>Type</th>
                                                <th style="text-align:center;">Time</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentActivityLogs as $log): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($log['log_id']) ?></td>
                                                    <td><?= htmlspecialchars($log['email']) ?></td>
                                                    <td class="log-type-<?= strtolower($log['activity_type']) ?>">
                                                        <?= htmlspecialchars($log['activity_type']) ?>
                                                    </td>
                                                    <td style="text-align:right;"><?= date('M j, Y G:i ', strtotime($log['activity_time'])) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                    <p>No activity logs found.</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div>
                        <div class="visuals-right">
                        <p>User Distribution</p>
                        
                        <canvas id="pieChart" width="400" height="200"></canvas>
                        </div>
                        </div>
                    

 
                    </div>
                </div>
            </div>
        </div>



                 
    </main>

    <!-- External JavaScript for validation -->
    <script src="../js/validinput.js"></script>
   
<!-- Pie Chart for user Distribution -->
<script>
    function initializePieChart() {
    const pieData = <?= json_encode($pieData) ?>;
    const pieCanvas = document.getElementById('pieChart');
    const pieCtx = pieCanvas.getContext('2d');
    
    // Corrected labels to match your data
    const labels = ['Admin', 'Lender', 'Customer'];
    const values = [
        pieData.Admin,
        pieData.Lender,
        pieData.Customer
    ];
    
    // Assign colors to the correct labels
    const statusColors = {
        'Admin': 'tomato',    
        'Lender': 'teal',  
        'Customer': '#ddd' 
    };

    const total = values.reduce((sum, value) => sum + value, 0);
    let startAngle = 0;
    const centerX = pieCanvas.width / 4;
    const centerY = pieCanvas.height / 2;
    const radius = Math.min(pieCanvas.width / 3, pieCanvas.height / 2) - 10;

    // Draw pie slices
    labels.forEach((label, index) => {
        const value = values[index];
        if (value > 0) {
            const sliceAngle = (2 * Math.PI * value) / total;
            pieCtx.beginPath();
            pieCtx.moveTo(centerX, centerY);
            pieCtx.arc(centerX, centerY, radius, startAngle, startAngle + sliceAngle);
            pieCtx.closePath();
            pieCtx.fillStyle = statusColors[label];
            pieCtx.fill();
            startAngle += sliceAngle;
        }
    });

    // Add legend
    pieCtx.font = '16px Trebuchet MS';
    let legendY = 20;
    const legendX = centerX + radius + 20;
    const legendSpacing = 20;

    labels.forEach((label, index) => {
        const value = values[index];
        if (value > 0) {
            pieCtx.fillStyle = statusColors[label];
            pieCtx.fillRect(legendX, legendY, 15, 15);
            pieCtx.fillStyle = 'whitesmoke';
            pieCtx.fillText(`${label}: ${value.toFixed(1)}%`, legendX + 20, legendY + 12);
            legendY += legendSpacing;
        }
    });
}

// Make sure to call this when the page loads
document.addEventListener('DOMContentLoaded', initializePieChart);
</script>

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
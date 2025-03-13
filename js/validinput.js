function validateForm() {
    // Get the selected role
    var role = document.getElementById("role").value;

    // Validate Role
    if (role === "--select option--") {
        alert("You must select a valid role (Customer or Lender).");
        return false;
    }

    // Validate First Name
    var firstName = document.getElementById("firstName").value;
    if (firstName === "") {
        alert("Please enter your First Name.");
        document.getElementById("firstName").focus();
        return false;
    }

    // Validate Second Name
    var secondName = document.getElementById("secondName").value;
    if (secondName === "") {
        alert("Please enter your Second Name.");
        document.getElementById("secondName").focus();
        return false;
    }

    // Validate Email
    var email = document.getElementById("email").value;
    if (email === "" || !email.includes("@") || !email.includes(".")) {
        alert("Please enter a valid Email address.");
        document.getElementById("email").focus();
        return false;
    }

    // Validate Phone
    var phone = document.getElementById("phone").value.trim();
    if (phone === "" || phone.length !== 10 || isNaN(phone) || !phone.startsWith("0")) {
        alert("Phone number must be exactly 10 digits and start with 0.");
        document.getElementById("phone").focus();
        return false;
    }

    // Validate Address (Common for both roles)
    var address = document.getElementById("address").value;
    if (address === "") {
        alert("Please enter your Address.");
        document.getElementById("address").focus();
        return false;
    }

    // Validate Password
    const password = document.getElementById("password").value.trim();
    const confirmPassword = document.getElementById("confPassword").value.trim();

    if (password === "") {
        alert("Password cannot be empty.");
        return false;
    }
    if (password.length < 8) {
        alert("Password must be at least 8 characters.");
        return false;
    }
    if (password !== confirmPassword) {
        alert("Passwords do not match.");
        return false;
    }

    // Role-specific validation to enable validation with hidden fields 
    //(Only for Customer)
    if (role === "Customer") {
        // Validate Date of Birth
        var date = document.getElementById("dob").value;
        if (date.indexOf("-") === -1) {
            alert("Date must be entered and of the format (dd-mm-yyyy).");
            document.getElementById("dob").focus();
            return false;
        }

        var comps = date.split("-");
        if (comps.length !== 3 || comps[0].length !== 2 || comps[1].length !== 2 || comps[2].length !== 4) {
            alert("Date must be of the format (dd-mm-yyyy).");
            document.getElementById("dob").focus();
            return false;
        }

        var day = parseInt(comps[0], 10);
        var month = parseInt(comps[1], 10);
        var year = parseInt(comps[2], 10);

        if (isNaN(day) || isNaN(month) || isNaN(year)) {
            alert("Date components must be integers.");
            document.getElementById("dob").focus();
            return false;
        }

        if (month < 1 || month > 12) {
            alert("Month must be between 1 and 12.");
            document.getElementById("dob").focus();
            return false;
        }

        if (day < 1 || day > 31) {
            alert("Day must be between 1 and 31.");
            document.getElementById("dob").focus();
            return false;
        }

        if (year < 1900 || year > new Date().getFullYear()) {
            alert("Year must be a valid year (1900 or later).");
            document.getElementById("dob").focus();
            return false;
        }

        var today = new Date();
        var givenDate = new Date(year, month - 1, day);
        if (givenDate > today) {
            alert("Date of Birth cannot be greater than today.");
            document.getElementById("dob").focus();
            return false;
        }

        // Validate National ID
        var nationalId = document.getElementById("nationalId").value;
        if (nationalId === "" || isNaN(nationalId)) {
            alert("Please enter a valid National ID.");
            document.getElementById("nationalId").focus();
            return false;
        }

        // Validate Account Number
        var accountNumber = document.getElementById("accountNumber").value;
        if (accountNumber === "" || isNaN(accountNumber)) {
            alert("Please enter a valid Account Number.");
            document.getElementById("accountNumber").focus();
            return false;
        }
    }
    return true;
}

// 2. Log In Validation
function validateForm2() {
    var email = document.getElementById("email").value;
    if (email === "" || !email.includes("@") || !email.includes(".")) {
        alert("Please enter a valid Email address.");
        document.getElementById("email").focus();
        return false;
    }
    return true;
}

// 3. Activity Table Validation
function validateFormActivity() {
    // Validate User ID
    var userId = document.getElementById("userId").value;
    if (userId === "" || isNaN(userId)) {
        alert("Please enter a valid user ID.");
        document.getElementById("userId").focus();
        return false;
    }

    // Validate Activity
    var activity = document.getElementById("activity").value;
    if (activity === "") {
        alert("Please enter the Activity.");
        document.getElementById("activity").focus();
        return false;
    }

    // Validate Activity Date & Time
    var activityDateTime = document.getElementById("activityDateTime").value;
    var dateTimeParts = activityDateTime.split(" ");
    if (dateTimeParts.length !== 2) {
        alert("Activity Date & Time must be in the format dd-mm-yyyy HH:MM.");
        document.getElementById("activityDateTime").focus();
        return false;
    }

    var dateParts = dateTimeParts[0].split("-");
    var timeParts = dateTimeParts[1].split(":");

    if (dateParts.length !== 3 || timeParts.length !== 2) {
        alert("Activity Date & Time must be in the format dd-mm-yyyy HH:MM.");
        document.getElementById("activityDateTime").focus();
        return false;
    }

    var day = parseInt(dateParts[0], 10);
    var month = parseInt(dateParts[1], 10);
    var year = parseInt(dateParts[2], 10);
    var hour = parseInt(timeParts[0], 10);
    var minute = parseInt(timeParts[1], 10);

    if (isNaN(day) || isNaN(month) || isNaN(year) || isNaN(hour) || isNaN(minute)) {
        alert("Date and time components must be integers.");
        document.getElementById("activityDateTime").focus();
        return false;
    }

    if (month < 1 || month > 12) {
        alert("Month must be between 1 and 12.");
        document.getElementById("activityDateTime").focus();
        return false;
    }

    if (day < 1 || day > 31) {
        alert("Day must be between 1 and 31.");
        document.getElementById("activityDateTime").focus();
        return false;
    }

    if (hour < 0 || hour > 23 || minute < 0 || minute > 59) {
        alert("Time must be in a valid format (HH:MM).");
        document.getElementById("activityDateTime").focus();
        return false;
    }

    // Validate Activity Type
    var activityType = document.getElementById("activityType").value;
    if (activityType === "") {
        alert("Please enter the Activity Type.");
        document.getElementById("activityType").focus();
        return false;
    }

    return true;
}

// 4. Customer Table Validation
function validateFormCustomer() {
    // Validate First Name
    var firstName = document.getElementById("firstName").value;
    if (firstName === "") {
        alert("Please enter First Name.");
        document.getElementById("firstName").focus();
        return false;
    }

    // Validate Second Name
    var secondName = document.getElementById("secondName").value;
    if (secondName === "") {
        alert("Please enter Second Name.");
        document.getElementById("secondName").focus();
        return false;
    }

    // Validate Email
    var email = document.getElementById("email").value;
    if (email === "" || !email.includes("@") || !email.includes(".")) {
        alert("Please enter a valid Email address.");
        document.getElementById("email").focus();
        return false;
    }

    // Validate Phone
    var phone = document.getElementById("phone").value.trim();
    if (phone === "" || phone.length !== 10 || isNaN(phone) || !phone.startsWith("0")) {
        alert("Phone number must be exactly 10 digits and start with 0.");
        document.getElementById("phone").focus();
        return false;
    }

    // Validate Address
    var address = document.getElementById("address").value;
    if (address === "") {
        alert("Please enter Address.");
        document.getElementById("address").focus();
        return false;
    }

    // Validate Date of Birth
    var date = document.getElementById("dob").value;
    if (date.indexOf("-") === -1) {
        alert("Date must be entered and of the format (dd-mm-yyyy).");
        document.getElementById("dob").focus();
        return false;
    }

    var comps = date.split("-");
    if (comps.length !== 3 || comps[0].length !== 2 || comps[1].length !== 2 || comps[2].length !== 4) {
        alert("Date must be of the format (dd-mm-yyyy).");
        document.getElementById("dob").focus();
        return false;
    }

    var day = parseInt(comps[0], 10);
    var month = parseInt(comps[1], 10);
    var year = parseInt(comps[2], 10);

    if (isNaN(day) || isNaN(month) || isNaN(year)) {
        alert("Date components must be integers.");
        document.getElementById("dob").focus();
        return false;
    }

    if (month < 1 || month > 12) {
        alert("Month must be between 1 and 12.");
        document.getElementById("dob").focus();
        return false;
    }

    if (day < 1 || day > 31) {
        alert("Day must be between 1 and 31.");
        document.getElementById("dob").focus();
        return false;
    }

    if (year < 1900 || year > new Date().getFullYear()) {
        alert("Year must be a valid year (1900 or later).");
        document.getElementById("dob").focus();
        return false;
    }

    var today = new Date();
    var givenDate = new Date(year, month - 1, day);
    if (givenDate > today) {
        alert("Date of Birth cannot be greater than today.");
        document.getElementById("dob").focus();
        return false;
    }

    // Validate National ID
    var nationalId = document.getElementById("nationalId").value;
    if (nationalId === "" || isNaN(nationalId)) {
        alert("Please enter a valid National ID.");
        document.getElementById("nationalId").focus();
        return false;
    }

    // Validate Account Number
    var accountNumber = document.getElementById("accountNumber").value;
    if (accountNumber === "" || isNaN(accountNumber)) {
        alert("Please enter a valid Account Number.");
        document.getElementById("accountNumber").focus();
        return false;
    }

    // Validate Password
    const password = document.getElementById("password").value.trim();
    const confirmPassword = document.getElementById("confPassword").value.trim();

    if (password === "") {
        alert("Password cannot be empty.");
        return false;
    }
    if (password.length < 8) {
        alert("Password must be at least 8 characters.");
        return false;
    }
    if (password !== confirmPassword) {
        alert("Passwords do not match.");
        return false;
    }

    alert("Customer Registered Successfully!");
    return true;
}

// 5. Lender Table Validation
function validateFormLenders() {
    // Validate Lender Name
    var lenderName = document.getElementById("lenderName").value;
    if (lenderName === "") {
        alert("Please enter Lender's Name.");
        document.getElementById("lenderName").focus();
        return false;
    }

    // Validate Email
    var email = document.getElementById("email").value;
    if (email === "" || !email.includes("@") || !email.includes(".")) {
        alert("Please enter a valid Email address.");
        document.getElementById("email").focus();
        return false;
    }

    // Validate Phone
    var phone = document.getElementById("phone").value.trim();
    if (phone === "" || phone.length !== 10 || isNaN(phone) || !phone.startsWith("0")) {
        alert("Phone number must be exactly 10 digits and start with 0.");
        document.getElementById("phone").focus();
        return false;
    }

    // Validate Address
    var address = document.getElementById("address").value;
    if (address === "") {
        alert("Please enter Address.");
        document.getElementById("address").focus();
        return false;
    }

    // Validate Total Loans
    var totalLoans = document.getElementById("totalLoans").value;
    if (totalLoans === "" || isNaN(totalLoans)) {
        alert("Please enter a valid number for Total Loans.");
        document.getElementById("totalLoans").focus();
        return false;
    }

    // Validate Interest Rate
    var interestRate = document.getElementById("interestRate").value.trim();
    if (interestRate === "" || isNaN(interestRate)) {
        alert("Please enter a valid Interest Rate.");
        document.getElementById("interestRate").focus();
        return false;
    }

    var interestRateValue = parseFloat(interestRate);
    if (interestRateValue < 0.01 || interestRateValue > 100) {
        alert("Interest rate must be between 0.01 and 100.");
        document.getElementById("interestRate").focus();
        return false;
    }

    // Validate Password
    const password = document.getElementById("password").value.trim();
    const confirmPassword = document.getElementById("confPassword").value.trim();

    if (password === "") {
        alert("Password cannot be empty.");
        return false;
    }
    if (password.length < 8) {
        alert("Password must be at least 8 characters.");
        return false;
    }
    if (password !== confirmPassword) {
        alert("Passwords do not match.");
        return false;
    }

    alert("Lender Registered Successfully!");
    return true;
}

// 6. Loans Table Validation
function validateFormLoans() {
    // Validate Lender ID
    var lenderId = document.getElementById("lenderId").value.trim();
    if (lenderId === "" || isNaN(lenderId)) {
        alert("Lender ID must be a valid number.");
        document.getElementById("lenderId").focus();
        return false;
    }

    // Validate Customer ID
    var customerId = document.getElementById("customerId").value.trim();
    if (customerId === "" || isNaN(customerId)) {
        alert("Customer ID must be a valid number.");
        document.getElementById("customerId").focus();
        return false;
    }

    // Validate Amount
    var amount = document.getElementById("amount").value.trim();
    if (amount === "" || isNaN(amount) || parseFloat(amount) <= 0) {
        alert("Amount must be a positive number.");
        document.getElementById("amount").focus();
        return false;
    }

    // Validate Interest Rate
    var interestRate = document.getElementById("interestRate").value.trim();
    if (interestRate === "" || isNaN(interestRate) || parseFloat(interestRate) <= 0 || parseFloat(interestRate) > 100) {
        alert("Interest rate must be between 0.01 and 100.");
        document.getElementById("interestRate").focus();
        return false;
    }

    // Validate Duration
    var duration = document.getElementById("duration").value.trim();
    if (duration === "" || isNaN(duration) || parseInt(duration, 10) <= 0) {
        alert("Duration must be a positive number.");
        document.getElementById("duration").focus();
        return false;
    }

    // Validate Installments
    var installments = document.getElementById("installments").value.trim();
    if (installments === "" || isNaN(installments) || parseInt(installments, 10) <= 0) {
        alert("Installments must be a positive number.");
        document.getElementById("installments").focus();
        return false;
    }

    // Validate Collateral Description
    var collateralDescription = document.getElementById("collateralDescription").value.trim();
    if (collateralDescription === "") {
        alert("Collateral description cannot be empty.");
        document.getElementById("collateralDescription").focus();
        return false;
    }

    // Validate Collateral Value
    var collateralValue = document.getElementById("collateralValue").value.trim();
    if (collateralValue === "" || isNaN(collateralValue) || parseFloat(collateralValue) <= 0) {
        alert("Collateral value must be a positive number.");
        document.getElementById("collateralValue").focus();
        return false;
    }

    alert("Loan Record Inserted Successfully!");
    return true;
}

// 7. Users Table Validation
function validateFormUsers() {
    // Validate Role
    var role = document.getElementById("role").value;
    if (role === "--select option--") {
        alert("You must select a valid role (Customer, Lender, or Admin).");
        return false;
    }

    // Validate First Name
    var firstName = document.getElementById("firstName").value;
    if (firstName === "") {
        alert("Please enter First Name.");
        document.getElementById("firstName").focus();
        return false;
    }

    // Validate Second Name
    var secondName = document.getElementById("secondName").value;
    if (secondName === "") {
        alert("Please enter Second Name.");
        document.getElementById("secondName").focus();
        return false;
    }

    // Validate Email
    var email = document.getElementById("email").value;
    if (email === "" || !email.includes("@") || !email.includes(".")) {
        alert("Please enter a valid Email address.");
        document.getElementById("email").focus();
        return false;
    }

    // Validate Phone
    var phone = document.getElementById("phone").value.trim();
    if (phone === "" || phone.length !== 10 || isNaN(phone) || !phone.startsWith("0")) {
        alert("Phone number must be exactly 10 digits and start with 0.");
        document.getElementById("phone").focus();
        return false;
    }

    // Validate Password
    const password = document.getElementById("password").value.trim();
    const confirmPassword = document.getElementById("confPassword").value.trim();

    if (password === "") {
        alert("Password cannot be empty.");
        return false;
    }
    if (password.length < 8) {
        alert("Password must be at least 8 characters.");
        return false;
    }
    if (password !== confirmPassword) {
        alert("Passwords do not match.");
        return false;
    }
    return true;
}
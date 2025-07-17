// ACTIVE NAV LINK SECTION
// Manages the visual indication of the active navigation link based on the current URL hash
function updateActiveNavLink() {
    // Selects all anchor tags (<a>) inside list items within the navigation menu (.nav ul li a)
    const navLinks = document.querySelectorAll('.nav ul li a');
    // Gets the current URL hash - no default to prevent dashboard flash
    const currentHash = window.location.hash;

    // Loops through all navigation links to remove the 'active' CSS class, resetting their appearance
    navLinks.forEach(link => link.classList.remove('active'));

    // Finds the navigation link whose href attribute matches the current hash
    const activeLink = document.querySelector(`.nav ul li a[href="${currentHash}"]`);
    // Checks if a matching link was found
    if (activeLink) {
        // Adds the 'active' CSS class to the matching link to highlight it visually
        activeLink.classList.add('active');
    } else if (!currentHash) {
        // Only defaults to dashboard if no hash exists (initial load)
        document.querySelector('.nav ul li a[href="#dashboard"]').classList.add('active');
    }
}

// Sets up navigation link behavior when the page loads or the URL hash changes
document.addEventListener('DOMContentLoaded', function() {
    // Ensures hash exists immediately to prevent dashboard flash
    if (!window.location.hash) {
        // Sets dashboard hash without adding to browser history
        history.replaceState(null, null, '#dashboard');
    }

    // Calls updateActiveNavLink to set the initial active link when the page loads
    updateActiveNavLink();

    // Adds an event listener to detect changes in the URL hash (when the user navigates)
    window.addEventListener('hashchange', updateActiveNavLink);

    // Uses event delegation for more efficient click handling
    document.querySelector('.nav').addEventListener('click', function(e) {
        // Checks if clicked element is a navigation link
        const navLink = e.target.closest('a');
        if (navLink && navLink.getAttribute('href').startsWith('#')) {
            // Removes the 'active' class from all navigation links
            document.querySelectorAll('.nav ul li a').forEach(l => l.classList.remove('active'));
            // Adds the 'active' class to the clicked link
            navLink.classList.add('active');
        }
    });
});

// ADD USER SECTION
// Manages the visibility of customer-specific fields based on the selected user role
// Gets the dropdown element for selecting the user role
const roleDropdown = document.getElementById('user-role');
// Selects all elements with ID 'customerFields' (fields specific to the Customer role)
const customerFields = document.querySelectorAll('#customerFields');

// Adds a change event listener to the role dropdown
roleDropdown.addEventListener('change', function() {
    // Checks if the selected value in the dropdown is 'Customer'
    if (roleDropdown.value === 'Customer') {
        // Loops through all customer-specific fields
        customerFields.forEach(field => {
            // Removes the 'hidden' CSS class to show the field
            field.classList.remove('hidden');
        });
    } else {
        // If the selected role is not 'Customer', loops through all customer-specific fields
        customerFields.forEach(field => {
            // Adds the 'hidden' CSS class to hide the field
            field.classList.add('hidden');
        });
    }
});

// Initializes the visibility of customer fields when the page loads
window.onload = function() {
    // Checks if the role dropdown’s value is the default placeholder '--select option--'
    if (roleDropdown.value === '--select option--') {
        // Loops through all customer-specific fields
        customerFields.forEach(field => {
            // Removes the 'hidden' CSS class to show the fields by default
            field.classList.remove('hidden');
        });
    }
};

// MESSAGE HANDLING
// Manages the display and automatic hiding of admin notification messages
function hideAdminMessage() {
    // Gets the element with ID 'admin-message' that contains the notification
    const adminMessage = document.getElementById('admin-message');
    // Checks if the admin message element exists
    if (adminMessage) {
        // Starts a timer to fade out the message after 2 seconds
        setTimeout(() => {
            // Sets the message’s opacity to 0 for a fade-out effect
            adminMessage.style.opacity = '0';
            // Starts a timer to hide the message after the fade-out transition completes
            setTimeout(() => {
                // Sets the message’s display style to 'none' to hide it
                adminMessage.style.display = 'none';
            }, 700); // Matches the CSS transition duration (700ms)
        }, 2000); // Displays the message for 2000 milliseconds (2 seconds)
    }
}

// Calls hideAdminMessage when the page loads
window.onload = hideAdminMessage;

// POPUP HANDLING
// Displays a popup with detailed information about a loan
function openLoanPopup(loan) {
    // Sets the text content of the element with ID 'popup-loan-id' to the loan’s ID
    document.getElementById('popup-loan-id').textContent = loan.loan_id;
    // Sets the text content of the element with ID 'popup-customer-id' to the customer’s ID
    document.getElementById('popup-customer-id').textContent = loan.customer_id;
    // Sets the text content of the element with ID 'popup-customer-name' to the customer’s name
    document.getElementById('popup-customer-name').textContent = loan.customer_name;
    // Formats the loan amount to two decimal places and sets it
    document.getElementById('popup-amount').textContent = parseFloat(loan.amount).toFixed(2);
    // Sets the text content of the element with ID 'popup-duration' to the loan duration
    document.getElementById('popup-duration').textContent = loan.duration;
    // Formats the collateral value to two decimal places and sets it
    document.getElementById('popup-collateral-value').textContent = parseFloat(loan.collateral_value).toFixed(2);
    // Sets the text content of the element with ID 'popup-collateral-desc' to the collateral description
    document.getElementById('popup-collateral-desc').textContent = loan.collateral_description;
    // Sets the src attribute of the element with ID 'popup-collateral-image' to the collateral image URL, or empty string if none
    document.getElementById('popup-collateral-image').src = loan.collateral_image || '';
    // Sets the value of the hidden input with ID 'popup-loan-id-input' to the loan ID (for approval form)
    document.getElementById('popup-loan-id-input').value = loan.loan_id;
    // Sets the value of the hidden input with ID 'popup-loan-id-input-reject' to the loan ID (for rejection form)
    document.getElementById('popup-loan-id-input-reject').value = loan.loan_id;
    // Shows the loan popup by setting its display style to 'block'
    document.getElementById('loanPopup').style.display = 'block';
}

// Hides the loan popup
function closeLoanPopup() {
    // Sets the loan popup’s display style to 'none' to hide it
    document.getElementById('loanPopup').style.display = 'none';
}

// Adds a click event listener to the window to close the popup when clicking outside
window.onclick = function(event) {
    // Gets the loan popup element
    const popup = document.getElementById('loanPopup');
    // Checks if the clicked element is the popup itself (the overlay)
    if (event.target == popup) {
        // Hides the popup by setting its display style to 'none'
        popup.style.display = 'none';
    }
}

// PROFILE EDIT SECTION
// Manages the profile editing popup functionality
document.addEventListener('DOMContentLoaded', function() {
    // Adds a click event listener to the edit profile button
    document.getElementById('editProfileBtn').addEventListener('click', function() {
        // Shows the profile edit popup overlay
        document.getElementById('profileOverlay').style.display = 'block';
        // Shows the profile edit popup content
        document.getElementById('profilePopup').style.display = 'block';
    });
    
    // Defines a function to hide the profile edit popup
    function hideProfilePopup() {
        // Hides the profile edit popup overlay
        document.getElementById('profileOverlay').style.display = 'none';
        // Hides the profile edit popup content
        document.getElementById('profilePopup').style.display = 'none';
    }
    
    // Adds a click event listener to the profile overlay to close the popup
    document.getElementById('profileOverlay').addEventListener('click', hideProfilePopup);
    // Adds a click event listener to the cancel button to close the popup
    document.getElementById('cancelEditBtn').addEventListener('click', hideProfilePopup);
    
    // Adds a click event listener to the profile popup to prevent closing when clicking inside
    document.getElementById('profilePopup').addEventListener('click', function(e) {
        // Stops the click event from propagating to the overlay
        e.stopPropagation();
    });
    
 
    // Gets the profile message element
    const profileMessage = document.getElementById('profileMessage');
    // Checks if the profile message exists and has content
    if (profileMessage && profileMessage.textContent.trim() !== '') {
        // Starts a timer to fade out the message after 3 seconds
        setTimeout(() => {
            // Sets the message opacity to 0 for a fade-out effect
            profileMessage.style.opacity = '0';
            // Hides the message after the fade-out transition
            setTimeout(() => {
                profileMessage.style.display = 'none';
            }, 500); // Matches the CSS transition duration
        }, 3000); // Displays for 3 seconds
    }
});

// CHANGE PASSWORD POPUP SECTION
// Initializes event listeners for the change password popup functionality when the DOM is fully loaded
document.addEventListener('DOMContentLoaded', function () {
    // Retrieves the Change Password button element by its class
    const changePassBtn = document.querySelector('.change');
    // Retrieves the Change Password overlay element by its ID
    const changePassOverlay = document.getElementById('changePasswordOverlay');
    // Retrieves the Cancel button within the change password popup by its ID
    const cancelChangePassBtn = document.getElementById('cancelChangePassBtn');

    // Checks if both the Change Password button and overlay exist before adding event listeners
    if (changePassBtn && changePassOverlay) {
        // Adds a click event listener to the Change Password button to show the popup
        changePassBtn.addEventListener('click', function () {
            // Displays the change password overlay as a flex container
            changePassOverlay.style.display = 'flex';
            // Adds a class to the body to disable scrolling while the popup is open
            document.body.classList.add('popup-open');
        });
    }

    // Checks if both the Cancel button and change password overlay exist before adding event listeners
    if (cancelChangePassBtn && changePassOverlay) {
        // Adds a click event listener to the Cancel button to hide the popup and clear the form
        cancelChangePassBtn.addEventListener('click', function () {
            // Hides the change password overlay
            changePassOverlay.style.display = 'none';
            // Removes the popup-open class from the body to restore scrolling
            document.body.classList.remove('popup-open');
            // Retrieves the change password form element by its ID
            const passwordForm = document.getElementById('changePasswordForm');
            // Checks if the password form exists before resetting it
            if (passwordForm) {
                // Resets all form fields to their initial values
                passwordForm.reset();
                // Retrieves the error message field by its ID
                const errorField = document.getElementById('password_error');
                // Checks if the error field exists before clearing it
                if (errorField) errorField.innerText = ''; // Clears any existing error message
            }
        });
    }

    // Gets the profile message element
    const profileMessage = document.getElementById('profileMessage');
    // Checks if the profile message exists and has content
    if (profileMessage && profileMessage.textContent.trim() !== '') {
        // Starts a timer to fade out the message after 3 seconds
        setTimeout(() => {
            // Sets the message opacity to 0 for a fade-out effect
            profileMessage.style.opacity = '0';
            // Hides the message after the fade-out transition
            setTimeout(() => {
                profileMessage.style.display = 'none';
            }, 500); // Matches the CSS transition duration
        }, 3000); // Displays for 3 seconds
    }
});
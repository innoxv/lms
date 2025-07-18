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

// METRICS FONT SIZE ADJUSTMENT
// Adjusts the font size of metric values to prevent text overflow in containers
function adjustMetricsFontSize() {
    // Selects all metric value elements with class 'span-2' inside .metrics
    const metricValues = document.querySelectorAll('.metrics .span-2');
    
    // Defines a function to adjust font sizes based on container width
    function adjustSizes() {
        // Loops through each metric value element
        metricValues.forEach(span => {
            // Get the metric-value-container (the actual container we need to fit within)
            const container = span.closest('.metric-value-container');
            if (!container) return;
            
            // Reset font size to default for accurate measurement
            span.style.fontSize = '4em';
            
            // Force a reflow to ensure the reset takes effect
            container.offsetHeight;
            
            // Get container dimensions (accounting for padding)
            const containerWidth = container.clientWidth;
            const containerHeight = container.clientHeight;
            
            // Get text dimensions
            const textWidth = span.scrollWidth;
            const textHeight = span.scrollHeight;
            
            // Calculate scale ratios for both width and height
            const widthRatio = containerWidth / textWidth;
            const heightRatio = containerHeight / textHeight;
            
            // Use the smaller ratio to ensure content fits in both dimensions
            const scaleRatio = Math.min(widthRatio, heightRatio);
            
            // Only scale down if content is too large (scaleRatio < 1)
            if (scaleRatio < 1) {
                // Calculate new font size (start from 4em and scale down)
                const newSize = Math.max(1, 4 * scaleRatio * 0.9); // 0.9 for safety margin
                span.style.fontSize = `${newSize}em`;
            }
            // If scaleRatio >= 1, keep the default 4em size
        });
    }
    
    // Debounce function to prevent excessive calls during resize
    let resizeTimeout;
    function debouncedAdjustSizes() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(adjustSizes, 100);
    }
    
    // Run on page load with a small delay to ensure layout is complete
    setTimeout(adjustSizes, 100);
    
    // Add resize event listener with debouncing
    window.addEventListener('resize', debouncedAdjustSizes);
}

// LOAN OFFERS SECTION
// Manages the editing of loan offers through a popup form
// Declares an object to store the original values of loan offer fields for comparison
let originalValues = {};

// Displays a popup form pre-filled with loan offer data for editing
function showEditPopup(offerId, loanType, interestRate, maxAmount, maxDuration) {
    // Stores the original values of the loan offer fields to track changes
    originalValues = {
        interest_rate: interestRate, // Stores the original interest rate
        max_amount: maxAmount, // Stores the original maximum loan amount
        max_duration: maxDuration // Stores the original maximum loan duration
    };

    // Sets the value of the hidden input field with ID 'editOfferId' to the offerId parameter
    document.getElementById('editOfferId').value = offerId;
    // Sets the text content of the element with ID 'editLoanType' to display the loan type (non-editable)
    document.getElementById('editLoanType').textContent = loanType;
    // Sets the value of the input field with ID 'editInterestRate' to the interest rate
    document.getElementById('editInterestRate').value = interestRate;
    // Sets the value of the input field with ID 'editMaxAmount' to the maximum loan amount
    document.getElementById('editMaxAmount').value = maxAmount;
    // Sets the value of the input field with ID 'editMaxDuration' to the maximum loan duration
    document.getElementById('editMaxDuration').value = maxDuration;
    
    // Makes the popup overlay visible by setting its display style to 'block'
    document.getElementById('popupOverlay').style.display = 'block';
    // Makes the edit popup form visible by setting its display style to 'block'
    document.getElementById('editPopup').style.display = 'block';
}

// Hides the edit loan offer popup
function hideEditPopup() {
    // Hides the popup overlay by setting its display style to 'none'
    document.getElementById('popupOverlay').style.display = 'none';
    // Hides the edit popup form by setting its display style to 'none'
    document.getElementById('editPopup').style.display = 'none';
}

// Handles the submission of the edit loan offer form
function handleFormSubmit(e) {
    // Prevents the default form submission behavior to handle it programmatically
    e.preventDefault();
    
    // Creates a new form element dynamically to submit data to the server
    const form = document.createElement('form');
    // Sets the form's method to POST for sending data securely
    form.method = 'POST';
    // Sets the form's action to the PHP script that processes the edit
    form.action = 'editLoan.php';
    
    // Creates a hidden input field for the offer ID
    const offerIdInput = document.createElement('input');
    // Sets the input type to hidden so it’s not visible to the user
    offerIdInput.type = 'hidden';
    // Sets the input name to 'offer_id' for server-side processing
    offerIdInput.name = 'offer_id';
    // Sets the input value to the current value of the 'editOfferId' field
    offerIdInput.value = document.getElementById('editOfferId').value;
    // Appends the offer ID input to the form
    form.appendChild(offerIdInput);
    
    // Gets the current interest rate value from the input field
    const currentInterest = document.getElementById('editInterestRate').value;
    // Checks if the interest rate has changed from its original value
    if (currentInterest !== originalValues.interest_rate) {
        // Creates a hidden input for the interest rate
        const interestInput = document.createElement('input');
        // Sets the input type to hidden
        interestInput.type = 'hidden';
        // Sets the input name to 'interest_rate' for server-side processing
        interestInput.name = 'interest_rate';
        // Sets the input value to the current interest rate
        interestInput.value = currentInterest;
        // Appends the interest rate input to the form
        form.appendChild(interestInput);
    }
    
    // Gets the current maximum amount value from the input field
    const currentAmount = document.getElementById('editMaxAmount').value;
    // Checks if the maximum amount has changed from its original value
    if (currentAmount !== originalValues.max_amount) {
        // Creates a hidden input for the maximum amount
        const amountInput = document.createElement('input');
        // Sets the input type to hidden
        amountInput.type = 'hidden';
        // Sets the input name to 'max_amount' for server-side processing
        amountInput.name = 'max_amount';
        // Sets the input value to the current maximum amount
        amountInput.value = currentAmount;
        // Appends the maximum amount input to the form
        form.appendChild(amountInput);
    }
    
    // Gets the current maximum duration value from the input field
    const currentDuration = document.getElementById('editMaxDuration').value;
    // Checks if the maximum duration has changed from its original value
    if (currentDuration !== originalValues.max_duration) {
        // Creates a hidden input for the maximum duration
        const durationInput = document.createElement('input');
        // Sets the input type to hidden
        durationInput.type = 'hidden';
        // Sets the input name to 'max_duration' for server-side processing
        durationInput.name = 'max_duration';
        // Sets the input value to the current maximum duration
        durationInput.value = currentDuration;
        // Appends the maximum duration input to the form
        form.appendChild(durationInput); // Adds the duration input to the form for submission
    }
    
    // Appends the dynamically created form to the document body
    document.body.appendChild(form);
    // Submits the form to the server for processing
    form.submit();
}

// Initializes event listeners for edit buttons and the form
document.addEventListener('DOMContentLoaded', function() {
    // Selects all elements with class 'edit-btn' (edit buttons for loan offers)
    document.querySelectorAll('.edit-btn').forEach(btn => {
        // Adds a click event listener to each edit button
        btn.addEventListener('click', function(e) {
            // Prevents the default behavior (navigating to a link)
            e.preventDefault();
            // Calls showEditPopup with data attributes from the clicked button
            showEditPopup(
                this.dataset.offerId, // Accesses the 'data-offer-id' attribute of the button
                this.dataset.loanType, // Accesses the 'data-loan-type' attribute
                this.dataset.interestRate, // Accesses the 'data-interest-rate' attribute
                this.dataset.maxAmount, // Accesses the 'data-max-amount' attribute
                this.dataset.maxDuration // Accesses the 'data-max-duration' attribute
            );
            //  this.dataset.offerId retrieves the value of the 'data-offer-id' attribute from the button element, which stores the unique ID of the loan offer
        });
    });

    // Adds a click event listener to the popup overlay to close the popup
    document.getElementById('popupOverlay').addEventListener('click', hideEditPopup);
    
    // Adds a submit event listener to the edit form to handle submission
    document.getElementById('editForm').addEventListener('submit', handleFormSubmit);
});

// LOAN REQUESTS SECTION
// Manages filtering of loan requests and updates the iframe content
window.onload = function() {
    // Gets the iframe element named 'hiddenFrame' used for form submissions
    const hiddenFrame = document.getElementsByName('hiddenFrame')[0];
    // Adds an onload event listener to the iframe
    hiddenFrame.onload = function() {
        // Gets the current status filter value from the status dropdown
        const statusFilter = document.getElementById('status').value;
        // Gets the current loan type filter value from the loan type dropdown
        const loanTypeFilter = document.getElementById('loan_type').value;
        // Constructs the base URL without the hash
        let url = window.location.href.split('#')[0] + '#loanRequests';
        
        // Builds query string if filters are applied
        if (statusFilter || loanTypeFilter) {
            // Starts the query string
            url += '?';
            // Adds status filter to the URL if present
            if (statusFilter) url += `status=${encodeURIComponent(statusFilter)}`;
            // Adds an ampersand if both filters are present
            if (statusFilter && loanTypeFilter) url += '&';
            // Adds loan type filter to the URL if present
            if (loanTypeFilter) url += `loan_type=${encodeURIComponent(loanTypeFilter)}`;
        }
        
        // Fetches the updated loan requests content from the server
        fetch(url)
            // Converts the response to text (HTML)
            .then(response => response.text())
            // Processes the HTML response
            .then(html => {
                // Creates a DOM parser to parse the HTML string
                const parser = new DOMParser();
                // Parses the HTML into a DOM document
                const doc = parser.parseFromString(html, 'text/html');
                // Gets the inner HTML of the loanRequests section from the parsed document
                const newContent = doc.querySelector('#loanRequests').innerHTML;
                // Updates the loanRequests section with the new content
                document.querySelector('#loanRequests').innerHTML = newContent;
            });
    };
};

// Provides visual feedback during filter application
document.querySelector('#loanRequests form').addEventListener('submit', function(e) {
    // Selects the loan requests table element
    const loanRequestsTable = document.querySelector('.loan-requests-table');
    // Sets the table opacity to 0.2 for a fade effect during filtering
    loanRequestsTable.style.opacity = '.2';
    // Applies a CSS transition for smooth opacity change
    loanRequestsTable.style.transition = 'opacity .3s ease';
    
    // Restores full opacity after 500ms to indicate filtering is complete
    setTimeout(() => {
        loanRequestsTable.style.opacity = '1';
    }, 500);
});

// Hides the loan message after displaying it for 2 seconds
function hideLoanMessage() {
    // Gets the loan message element
    const loanMessage = document.getElementById('loan-message');
    // Checks if the loan message element exists
    if (loanMessage) {
        // Starts a timer to fade out the message after 2 seconds
        setTimeout(() => {
            // Sets the message opacity to 0 for a fade-out effect
            loanMessage.style.opacity = '0';
            // Hides the message after the fade-out transition completes
            setTimeout(() => {
                loanMessage.style.display = 'none';
            }, 700); // Matches the CSS transition duration
        }, 2000); // Displays for 2 seconds
    }
}

// Calls hideLoanMessage when the page loads
window.onload = hideLoanMessage;


// VIEW LOAN POPUP SECTION
// Displays a popup with detailed information about a loan request
function showViewLoanPopup(loanId, customer, loanType, amount, interestRate, duration, 
                         collateralValue, collateralDesc, status, createdAt) {
    // Sets the text content of the element with ID 'viewLoanId' to the loan ID
    document.getElementById('viewLoanId').textContent = loanId;
    // Sets the text content of the element with ID 'viewCustomer' to the customer name
    document.getElementById('viewCustomer').textContent = customer;
    // Sets the text content of the element with ID 'viewLoanType' to the loan type
    document.getElementById('viewLoanType').textContent = loanType;
    // Formats the amount as a number with two decimal places and sets it
    document.getElementById('viewAmount').textContent = parseFloat(amount).toLocaleString(undefined, {minimumFractionDigits: 2});
    // Appends a percentage sign to the interest rate and sets it
    document.getElementById('viewInterestRate').textContent = interestRate + '%';
    // Appends ' months' to the duration and sets it
    document.getElementById('viewDuration').textContent = duration + ' months';
    // Sets the text content of the element with ID 'viewCollateralValue'
    document.getElementById('viewCollateralValue').textContent = collateralValue;
    // Sets the text content of the element with ID 'viewCollateralDesc'
    document.getElementById('viewCollateralDesc').textContent = collateralDesc;
    
    // Gets the status element to display the loan status
    const statusElement = document.getElementById('viewStatus');
    // Clears any existing content in the status element
    statusElement.innerHTML = '';
    // Creates a new span element for the status badge
    const statusBadge = document.createElement('span');
    // Sets the class of the badge based on the status (status-pending)
    statusBadge.className = `status-badge status-${status.toLowerCase()}`;
    // Sets the text content of the badge to the status
    statusBadge.textContent = status;
    // Appends the status badge to the status element
    statusElement.appendChild(statusBadge);
    
    // Creates a Date object from the createdAt timestamp
    const date = new Date(createdAt);
    // Formats the date and sets it in the createdAt element
    document.getElementById('viewCreatedAt').textContent = date.toLocaleDateString('en-US', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
    
    // Shows the view loan popup overlay
    document.getElementById('viewLoanOverlay').style.display = 'block';
    // Shows the view loan popup content
    document.getElementById('viewLoanPopup').style.display = 'block';
}

// Hides the view loan popup
function hideViewLoanPopup() {
    // Hides the view loan popup overlay
    document.getElementById('viewLoanOverlay').style.display = 'none';
    // Hides the view loan popup content
    document.getElementById('viewLoanPopup').style.display = 'none';
}

// Initializes view buttons for loan requests
document.addEventListener('DOMContentLoaded', function() {
    // Selects all elements with class 'btn-view' (view buttons for loans)
    document.querySelectorAll('.btn-view').forEach(btn => {
        // Adds a click event listener to each view button
        btn.addEventListener('click', function() {
            // Calls showViewLoanPopup with data attributes from the button
            showViewLoanPopup(
                this.dataset.loanId, // Accesses the 'data-loan-id' attribute
                this.dataset.customer, // Accesses the 'data-customer' attribute
                this.dataset.loanType, // Accesses the 'data-loan-type' attribute
                this.dataset.amount, // Accesses the 'data-amount' attribute
                this.dataset.interestRate, // Accesses the 'data-interest-rate' attribute
                this.dataset.duration, // Accesses the 'data-duration' attribute
                this.dataset.collateralValue, // Accesses the 'data-collateral-value' attribute
                this.dataset.collateralDesc, // Accesses the 'data-collateral-desc' attribute
                this.dataset.status, // Accesses the 'data-status' attribute
                this.dataset.createdAt // Accesses the 'data-created-at' attribute
            );
            // this.dataset.loanId retrieves the value of the 'data-loan-id' attribute from the button element, which stores the unique ID of the loan
        });
    });

    // Adds a click event listener to the view loan overlay to close the popup
    document.getElementById('viewLoanOverlay').addEventListener('click', hideViewLoanPopup);
});

// ACTIVE LOANS SECTION
// Displays a popup with detailed information about an active loan
function showViewActiveLoanPopup(loanId, customer, loanType, amount, interestRate, duration, 
                               collateralValue, collateralDesc, remainingBalance, createdAt) {
    // Sets the text content of the element with ID 'viewActiveLoanId' to the loan ID
    document.getElementById('viewActiveLoanId').textContent = loanId;
    // Sets the text content of the element with ID 'viewActiveCustomer' to the customer name
    document.getElementById('viewActiveCustomer').textContent = customer;
    // Sets the text content of the element with ID 'viewActiveLoanType' to the loan type
    document.getElementById('viewActiveLoanType').textContent = loanType;
    // Formats the amount as a number with two decimal places and sets it
    document.getElementById('viewActiveAmount').textContent = parseFloat(amount).toLocaleString(undefined, {minimumFractionDigits: 2});
    // Appends a percentage sign to the interest rate and sets it
    document.getElementById('viewActiveInterestRate').textContent = interestRate + '%';
    // Appends ' months' to the duration and sets it
    document.getElementById('viewActiveDuration').textContent = duration + ' months';
    // Formats the collateral value as a number with two decimal places and sets it
    document.getElementById('viewActiveCollateralValue').textContent = parseFloat(collateralValue).toLocaleString(undefined, {minimumFractionDigits: 2});
    // Sets the text content of the element with ID 'viewActiveCollateralDesc'
    document.getElementById('viewActiveCollateralDesc').textContent = collateralDesc;
    // Formats the remaining balance as a number with two decimal places and sets it
    document.getElementById('viewActiveRemainingBalance').textContent = parseFloat(remainingBalance).toLocaleString(undefined, {minimumFractionDigits: 2});
    // Creates a Date object from the createdAt timestamp
    const date = new Date(createdAt);
    // Formats the date and sets it in the createdAt element
    document.getElementById('viewActiveCreatedAt').textContent = date.toLocaleDateString('en-US', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
    // Shows the active loan popup overlay
    document.getElementById('viewActiveLoanOverlay').style.display = 'block';
    // Shows the active loan popup content
    document.getElementById('viewActiveLoanPopup').style.display = 'block';
}

// Hides the active loan popup
function hideViewActiveLoanPopup() {
    // Hides the active loan popup overlay
    document.getElementById('viewActiveLoanOverlay').style.display = 'none';
    // Hides the active loan popup content
    document.getElementById('viewActiveLoanPopup').style.display = 'none';
}

// Initializes view buttons for active loans
document.addEventListener('DOMContentLoaded', function() {
    // Selects all elements with class 'btn-view-active' (view buttons for active loans)
    document.querySelectorAll('.btn-view-active').forEach(btn => {
        // Adds a click event listener to each view button
        btn.addEventListener('click', function() {
            // Calls showViewActiveLoanPopup with data attributes from the button
            showViewActiveLoanPopup(
                this.dataset.loanId, // Accesses the 'data-loan-id' attribute
                this.dataset.customer, // Accesses the 'data-customer' attribute
                this.dataset.loanType, // Accesses the 'data-loan-type' attribute
                this.dataset.amount, // Accesses the 'data-amount' attribute
                this.dataset.interestRate, // Accesses the 'data-interest-rate' attribute
                this.dataset.duration, // Accesses the 'data-duration' attribute
                this.dataset.collateralValue, // Accesses the 'data-collateral-value' attribute
                this.dataset.collateralDesc, // Accesses the 'data-collateral-desc' attribute
                this.dataset.remainingBalance, // Accesses the 'data-remaining-balance' attribute
                this.dataset.createdAt // Accesses the 'data-created-at' attribute
            );
        });
    });
    // Adds a click event listener to the active loan overlay to close the popup
    document.getElementById('viewActiveLoanOverlay').addEventListener('click', hideViewActiveLoanPopup);
});

// PAYMENT REVIEW SECTION
// Displays a popup with detailed information about a payment
function showViewPaymentPopup(paymentId, loanId, customer, loanType, amount, method, type, balance, date) {
    // Sets the text content of the element with ID 'viewPaymentId' to the payment ID
    document.getElementById('viewPaymentId').textContent = paymentId;
    // Sets the text content of the element with ID 'viewPaymentLoanId' to the loan ID
    document.getElementById('viewPaymentLoanId').textContent = loanId;
    // Sets the text content of the element with ID 'viewPaymentCustomer' to the customer name
    document.getElementById('viewPaymentCustomer').textContent = customer;
    // Sets the text content of the element with ID 'viewPaymentLoanType' to the loan type
    document.getElementById('viewPaymentLoanType').textContent = loanType;
    // Formats the amount as a number with two decimal places and sets it
    document.getElementById('viewPaymentAmount').textContent = parseFloat(amount).toLocaleString(undefined, {minimumFractionDigits: 2});
    // Formats the payment method ('credit_card' to 'Credit Card') and sets it
    document.getElementById('viewPaymentMethod').textContent = method.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
    // Sets the text content of the element with ID 'viewPaymentType' to the payment type
    document.getElementById('viewPaymentType').textContent = type;
    
    // Creates a Date object from the payment date
    const paymentDate = new Date(date);
    // Formats the date and sets it in the payment date element
    document.getElementById('viewPaymentDate').textContent = paymentDate.toLocaleDateString('en-US', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
    
    // Shows the payment popup overlay
    document.getElementById('viewPaymentOverlay').style.display = 'block';
    // Shows the payment popup content
    document.getElementById('viewPaymentPopup').style.display = 'block';
}

// Hides the payment review popup
function hideViewPaymentPopup() {
    // Hides the payment popup overlay
    document.getElementById('viewPaymentOverlay').style.display = 'none';
    // Hides the payment popup content
    document.getElementById('viewPaymentPopup').style.display = 'none';
}

// Initializes view buttons for payment reviews
document.addEventListener('DOMContentLoaded', function() {
    // Selects all elements with class 'view' inside the paymentReview section
    document.querySelectorAll('#paymentReview .view').forEach(btn => {
        // Adds a click event listener to each view button
        btn.addEventListener('click', function() {
            // Calls showViewPaymentPopup with data attributes from the button
            showViewPaymentPopup(
                this.dataset.paymentId, // Accesses the 'data-payment-id' attribute
                this.dataset.loanId, // Accesses the 'data-loan-id' attribute
                this.dataset.customer, // Accesses the 'data-customer' attribute
                this.dataset.loanType, // Accesses the 'data-loan-type' attribute
                this.dataset.amount, // Accesses the 'data-amount' attribute
                this.dataset.method, // Accesses the 'data-method' attribute
                this.dataset.type, // Accesses the 'data-type' attribute
                this.dataset.balance, // Accesses the 'data-balance' attribute
                this.dataset.date // Accesses the 'data-date' attribute
            );
        });
    });

    // Adds a click event listener to the payment overlay to close the popup
    document.getElementById('viewPaymentOverlay').addEventListener('click', hideViewPaymentPopup);
});

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
});



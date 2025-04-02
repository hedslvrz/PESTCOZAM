document.addEventListener('DOMContentLoaded', function() {
    checkSession();

    // Add logout functionality
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            logout();
        });
    }
});

function checkSession() {
    fetch('../PHP CODES/check_session.php')
        .then(response => response.json())
        .then(data => {
            updateNavigation(data.loggedIn, data.profilePic);
        })
        .catch(error => console.error('Error:', error));
}

function updateNavigation(isLoggedIn, profilePic) {
    const authButtons = document.querySelector('.auth-buttons');
    const userProfile = document.querySelector('.user-profile');
    
    if (isLoggedIn) {
        if (authButtons) authButtons.classList.add('hidden');
        if (userProfile) {
            userProfile.classList.remove('hidden');
            const profileImage = userProfile.querySelector('.profile-pic');
            if (profileImage) {
                profileImage.src = profilePic || '../Pictures/default-profile.png';
            }
        }
    } else {
        if (authButtons) authButtons.classList.remove('hidden');
        if (userProfile) userProfile.classList.add('hidden');
    }
}

function logout() {
    fetch('../PHP CODES/logout.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = 'Home_page.php';
            }
        })
        .catch(error => console.error('Error:', error));
}

/**
 * Session Handler for appointment flow
 * Provides utility functions to manage appointment flow state
 */

class AppointmentSessionHandler {
    /**
     * Store current appointment step in session storage
     * @param {string} step - The current step in the appointment process
     */
    static saveCurrentStep(step) {
        sessionStorage.setItem('appointmentStep', step);
    }
    
    /**
     * Get the current appointment step
     * @returns {string|null} The current step or null if not set
     */
    static getCurrentStep() {
        return sessionStorage.getItem('appointmentStep');
    }
    
    /**
     * Store appointment data for recovery in case of page refresh
     * @param {Object} data - The data to store
     */
    static saveAppointmentData(data) {
        sessionStorage.setItem('appointmentData', JSON.stringify(data));
    }
    
    /**
     * Get stored appointment data
     * @returns {Object|null} The appointment data or null if not set
     */
    static getAppointmentData() {
        const data = sessionStorage.getItem('appointmentData');
        return data ? JSON.parse(data) : null;
    }
    
    /**
     * Clear all appointment session data
     */
    static clearAppointmentData() {
        sessionStorage.removeItem('appointmentStep');
        sessionStorage.removeItem('appointmentData');
    }
}

// Initialize event listener for page unload to warn about losing progress
document.addEventListener('DOMContentLoaded', function() {
    // Check if we're in an appointment page
    if (window.location.href.includes('Appointment-')) {
        window.addEventListener('beforeunload', function(e) {
            // Don't show warning if we're on the final confirmation page
            if (!window.location.href.includes('Appointment-successful.php')) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    }
});

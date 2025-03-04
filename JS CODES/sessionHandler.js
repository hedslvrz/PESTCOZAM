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
                window.location.href = 'Home_page.html';
            }
        })
        .catch(error => console.error('Error:', error));
}

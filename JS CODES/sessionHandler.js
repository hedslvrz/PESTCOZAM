document.addEventListener('DOMContentLoaded', function() {
    checkLoginStatus();

    // Add logout handler
    document.getElementById('logoutBtn').addEventListener('click', function(e) {
        e.preventDefault();
        logout();
    });
});

function checkLoginStatus() {
    fetch('../PHP CODES/check_session.php')
        .then(response => response.json())
        .then(data => {
            const authButtons = document.querySelector('.auth-buttons');
            const userProfile = document.querySelector('.user-profile');

            if (data.loggedIn) {
                // User is logged in
                authButtons.classList.add('hidden');
                userProfile.classList.remove('hidden');
                
                // Update profile picture if available
                if (data.profilePic) {
                    document.querySelector('.profile-pic').src = data.profilePic;
                }
            } else {
                // User is not logged in
                authButtons.classList.remove('hidden');
                userProfile.classList.add('hidden');
            }
        })
        .catch(error => console.error('Error:', error));
}

function logout() {
    fetch('../PHP CODES/logout.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert('Logout failed. Please try again.');
            }
        })
        .catch(error => console.error('Error:', error));
}

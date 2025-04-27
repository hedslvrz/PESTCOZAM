document.addEventListener("DOMContentLoaded", function() {
    const form = document.getElementById('resetPasswordForm');
    
    form.addEventListener('submit', function(event) {
        event.preventDefault();
        
        const password = document.getElementById('password').value.trim();
        const confirmPassword = document.getElementById('confirm_password').value.trim();
        const token = document.getElementById('token').value.trim();
        
        // Validate password
        if (password === '') {
            showModal('Error', 'Please enter a new password.');
            return;
        }
        
        if (password.length < 8) {
            showModal('Error', 'Password must be at least 8 characters.');
            return;
        }
        
        if (confirmPassword === '') {
            showModal('Error', 'Please confirm your password.');
            return;
        }
        
        if (password !== confirmPassword) {
            showModal('Error', 'Passwords do not match.');
            return;
        }
        
        // Send the password reset request
        resetPassword(password, token);
    });
    
    // Modal functions
    function showModal(title, message) {
        document.getElementById('modalTitle').textContent = title;
        document.getElementById('modalMessage').textContent = message;
        document.getElementById('customModal').style.display = 'block';
    }
    
    // Close modal when X is clicked
    const closeBtn = document.querySelector('.close');
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            document.getElementById('customModal').style.display = 'none';
        });
    }
    
    // Close modal when OK is clicked
    const modalBtn = document.getElementById('modalBtn');
    if (modalBtn) {
        modalBtn.addEventListener('click', function() {
            document.getElementById('customModal').style.display = 'none';
            
            // If the password was reset successfully, redirect to login
            if (document.getElementById('modalTitle').textContent === 'Success') {
                window.location.href = '../HTML CODES/Login.php';
            }
        });
    }
    
    function resetPassword(password, token) {
        // Show loading state
        const submitBtn = document.querySelector('.login-btn');
        submitBtn.textContent = 'Saving...';
        submitBtn.disabled = true;
        
        // Use FormData for more reliable handling
        const formData = new FormData();
        formData.append('password', password);
        formData.append('token', token);
        
        fetch('../PHP CODES/reset_password.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Server error: ' + response.status);
            }
            
            // Parse response as JSON
            return response.json();
        })
        .then(data => {
            // Reset button state
            submitBtn.textContent = 'Save Password';
            submitBtn.disabled = false;
            
            if (data.success) {
                showModal('Success', 'Your password has been reset successfully. You can now log in with your new password.');
            } else {
                showModal('Error', data.message || 'An error occurred. Please try again.');
            }
        })
        .catch(error => {
            // Reset button state
            submitBtn.textContent = 'Save Password';
            submitBtn.disabled = false;
            
            console.error('Error:', error);
            showModal('Error', 'An error occurred. Please try again later.');
        });
    }
});

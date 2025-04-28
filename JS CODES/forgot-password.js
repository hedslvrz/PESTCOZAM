document.addEventListener("DOMContentLoaded", function() {
    const form = document.getElementById('forgotPasswordForm');
    
    if (form) {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            
            const email = document.getElementById('email').value.trim();
            
            // Basic validation
            if (email === '') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Empty Field',
                    text: 'Please enter your email address.',
                    confirmButtonColor: '#144578',
                    confirmButtonText: 'OK'
                });
                return;
            }
            
            // Simple email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Email',
                    text: 'Please enter a valid email address.',
                    confirmButtonColor: '#144578',
                    confirmButtonText: 'OK'
                });
                return;
            }
            
            // Show loading state
            const submitBtn = document.querySelector('.login-btn');
            submitBtn.textContent = 'Sending...';
            submitBtn.disabled = true;
            
            // Create form data
            const formData = new FormData();
            formData.append('email', email);
            
            // Send request using XMLHttpRequest instead of fetch
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '../PHP CODES/forgot_password.php', true);
            
            xhr.onload = function() {
                submitBtn.textContent = 'Reset Password';
                submitBtn.disabled = false;
                
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Reset Email Sent',
                                text: 'If your email exists in our system, you will receive a password reset link shortly.',
                                confirmButtonColor: '#144578',
                                timer: 3000,
                                timerProgressBar: true,
                                willClose: () => {
                                    document.getElementById('email').value = '';
                                    window.location.href = '../HTML CODES/Login.php';
                                }
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message || 'An error occurred. Please try again.',
                                confirmButtonColor: '#144578'
                            });
                        }
                    } catch (e) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Processing Error',
                            text: 'There was a problem processing the response. Please try again.',
                            confirmButtonColor: '#144578'
                        });
                        console.error('Response parse error:', xhr.responseText);
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Connection Error',
                        text: 'There was a problem connecting to the server. Please try again later.',
                        confirmButtonColor: '#144578'
                    });
                }
            };
            
            xhr.onerror = function() {
                submitBtn.textContent = 'Reset Password';
                submitBtn.disabled = false;
                Swal.fire({
                    icon: 'error',
                    title: 'Connection Error',
                    text: 'There was a problem connecting to the server. Please try again later.',
                    confirmButtonColor: '#144578'
                });
            };
            
            xhr.send(formData);
        });
    }
});

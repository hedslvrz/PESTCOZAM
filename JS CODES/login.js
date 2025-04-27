document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("loginUser");
    form.addEventListener("submit", function (event) {
        validateForm(event); // Call validateForm and pass the event object
    });
});

function validateForm(event) {
    let email = document.getElementById('email').value.trim();
    let password = document.getElementById('password').value.trim();

    // Email validation
    if (email === "") {
        showModal("Error", "Email is required.");
        event.preventDefault();
        return false;
    }
    
    // Validate email format
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        showModal("Error", "Please enter a valid email address.");
        event.preventDefault();
        return false;
    }
    
    // Password validation
    if (password === "") {
        showModal("Error", "Password is required.");
        event.preventDefault();
        return false;
    }
    
    // Check for minimum password length
    if (password.length < 6) {
        showModal("Error", "Password must be at least 6 characters.");
        event.preventDefault();
        return false;
    }

    return loginUser(event); // Pass the event object
}

function showModal(title, message) {
    const modal = document.getElementById('customModal');
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('modalMessage').textContent = message;
    modal.style.display = "block";
}

function loginUser(event) {
    event.preventDefault(); // Prevent form submission

    // Use FormData instead of JSON for more reliable form submission
    const formData = new FormData();
    formData.append('email', document.getElementById('email').value.trim());
    formData.append('password', document.getElementById('password').value.trim());

    fetch("../PHP CODES/login_api.php", {
        method: "POST",
        headers: { 
            "X-Requested-With": "XMLHttpRequest" // Helps identify AJAX requests
        },
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        
        // Handle both text and JSON responses
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        } else {
            // For non-JSON responses, try to parse or return formatted error
            return response.text().then(text => {
                try {
                    // Try to parse as JSON anyway
                    return JSON.parse(text);
                } catch (e) {
                    console.error("Server response was not JSON:", text);
                    // Create a standardized error response
                    return { 
                        success: false, 
                        message: "Server returned invalid response. Please try again." 
                    };
                }
            });
        }
    })
    .then(data => {
        if (data.success) {
            if (data.role === "admin") {
                customModal.showSuccess("Login Successful!", () => {
                    window.location.href = "../HTML CODES/dashboard-admin.php";
                });
            } else if (data.role === "technician") {
                customModal.showSuccess("Login Successful!", () => {
                    window.location.href = "../HTML CODES/dashboard-pct.php";
                });
            } else if (data.role === "supervisor") {
                customModal.showSuccess("Login Successful!", () => {
                    window.location.href = "../HTML CODES/dashboard-aos.php";
                });
            } else {
                customModal.showSuccess("Login Successful!", () => {
                    window.location.href = "../Index.php";
                });
            }
        } else {
            customModal.showError(data.message || "Invalid login credentials");
        }
    })
    .catch(error => {
        console.error("Login Error:", error);
        customModal.showError("An error occurred during login. Please try again.");
    });
}
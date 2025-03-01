document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("registerForm");

    form.addEventListener("submit", function (event) {
        event.preventDefault();
        registerUser();
    });
});

async function registerUser() {
    // ✅ Validate input before submission
    if (!validateForm()) return;

    let formData = {
        firstname: document.getElementById('firstname').value.trim(),
        lastname: document.getElementById('lastname').value.trim(),
        email: document.getElementById('email').value.trim(),
        mobile_number: document.getElementById('mobile_number').value.trim(),
        password: document.getElementById('password').value.trim(),
        confirm_password: document.getElementById('confirm_password').value.trim()
    };

    try {
        let response = await fetch("../PHP CODES/register_api.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(formData)
        });

        let result = await response.json();
        
        if (result.success) {
            alert("Signup successful!");
            window.location.href = "../HTML CODES/appointment-service.php"; // Redirect after signup
        } else {
            alert(result.message);
        }
    } catch (error) {
        alert("An error occurred during registration. Please try again.");
        console.error("Error:", error);
    }
}

function validateForm() {
    let password = document.getElementById('password').value.trim();
    let confirmPassword = document.getElementById('confirm_password').value.trim();

    if (password !== confirmPassword) {
        alert("Passwords do not match.");
        return false;
    }
    return true;
}

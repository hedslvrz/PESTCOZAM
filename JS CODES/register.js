document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("registerForm");

    form.addEventListener("submit", function (event) {
        event.preventDefault();
        registerUser(event);
    });
});

async function registerUser(event) {
    if (!validateForm()) {
        return;
    }
    let form = document.forms["registerForm"];
    let userData = {
        firstname: form["firstname"].value.trim(),
        lastname: form["lastname"].value.trim(),
        email: form["email"].value.trim(),
        mobile_number: form["mobile_number"].value.trim(),
        password: form["password"].value.trim(),
        confirm_password: form["confirm_password"].value.trim()
    };

    try {
        let response = await fetch("../PHP CODES/register_api.php", {
            method: "POST",
            headers: { "Content-type": "application/json" },
            body: JSON.stringify(userData)
        });

        if (!response.ok) {
            throw new Error("Network response was not ok.");
        }

        let result = await response.json();
        if (result.success) {
            customModal.showSuccess(result.message, () => {
                window.location.href = "../HTML CODES/Login.php";
            });
        } else {
            customModal.showError(result.message);
        }
    } catch (error) {
        customModal.showError("An error occurred during registration. Please try again.");
        console.error("Error:", error);
    }
}

function validateForm() {
    var firstName = document.getElementById('firstname').value.trim();
    var lastName = document.getElementById('lastname').value.trim();
    var mobile_number = document.getElementById('mobile_number').value.trim();
    var email = document.getElementById('email').value.trim();
    var password = document.getElementById('password').value.trim();
    var confirm_password = document.getElementById('confirm_password').value.trim();

    if (firstName === "") {
        showModal("Error", "First Name is required.");
        return false;
    }
    if (lastName === "") {
        showModal("Error", "Last Name is required.");
        return false;
    }
    if (mobile_number === "") {
        customModal.showError("Contact number is required.");
        return false;
    }
    if (email === "") {
        customModal.showError("Email is required.");
        return false;
    }
    if (password === "") {
        customModal.showError("Password is required.");
        return false;
    }
    if (confirm_password === "") {
        customModal.showError("Confirm Password is required.");
        return false;
    }

    return validatePassword();
}

function validatePassword() {
    var password = document.getElementById('password').value;
    var confirm_password = document.getElementById('confirm_password').value;
    var minNumberofChars = 8;
    var maxNumberofChars = 16;
    var regularExpression = /^(?=.*\d)(?=.*[a-zA-Z])[a-zA-Z0-9]{8,16}$/;

    if (password.length < minNumberofChars || password.length > maxNumberofChars) {
        alert("Password must be between 8 to 16 characters.");
        return false;
    }
    if (!regularExpression.test(password)) {
        alert("Password must contain at least one number and one letter.");
        return false;
    }
    if (password !== confirm_password) {
        alert("Passwords do not match.");
        return false;
    }
    return true;
}

function showModal(title, message) {
    const modal = document.getElementById('customModal');
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('modalMessage').textContent = message;
    modal.style.display = "block";
}
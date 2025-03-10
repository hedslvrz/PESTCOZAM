document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("loginUser");
    form.addEventListener("submit", function (event) {
        validateForm(event); // Call validateForm and pass the event object
    });
});

function validateForm(event) {
    let email = document.getElementById('email').value.trim();
    let password = document.getElementById('password').value.trim();

    if (email === "") {
        showModal("Error", "Email is required.");
        return false;
    }
    if (password === "") {
        showModal("Error", "Password is required.");
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

    let formData = {
        email: document.getElementById('email').value.trim(),
        password: document.getElementById('password').value.trim()
    };

    fetch("../PHP CODES/login_api.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.role === "admin") {
                customModal.showSuccess("Login Successful!", () => {
                    window.location.href = "../HTML CODES/dashboard-admin.php";
                });
            } else if (data.role === "technician") {
                window.location.href = "../HTML CODES/dashboard-pct.php";
            } else if (data.role === "supervisor") {
                window.location.href = "../HTML CODES/dashboard-aos.php";
            } else {
                customModal.showSuccess("Login Successful!", () => {
                    window.location.href = "../HTML CODES/Home_page.php";
                });
            }
        } else {
            customModal.showError(data.message);
        }
    })
    .catch(error => {
        customModal.showError("An error occurred. Please try again.");
        console.error("Error:", error);
    });
}
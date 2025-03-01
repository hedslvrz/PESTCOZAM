document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("loginUser");
    form.addEventListener("submit", function (event) {
        validateForm(event);
    });
});

function validateForm(event) {
    let email = document.getElementById('email').value.trim();
    let password = document.getElementById('password').value.trim();

    if (email === "" || password === "") {
        alert("Email and password are required.");
        return false;
    }

    return loginUser(event);
}

function loginUser(event) {
    event.preventDefault();

    let formData = {
        email: document.getElementById('email').value.trim(),
        password: document.getElementById('password').value.trim()
    };

    fetch("login_api.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert("Welcome, " + data.firstname + "!");

            let redirectPage = sessionStorage.getItem("redirectTo") || "profile.php"; // Default to profile if no redirect
            sessionStorage.removeItem("redirectTo"); // Clear after use
            window.location.href = redirectPage;
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error("Error:", error);
        alert("An error occurred. Please try again.");
    });
}

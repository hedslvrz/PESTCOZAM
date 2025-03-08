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
        alert("Email is required.");
        return false;
    }
    if (password === "") {
        alert("Password is required.");
        return false;
    }

    return loginUser(event); // Pass the event object
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
                alert("Login Successful!")
                window.location.href = "../HTML CODES/dashboard.html";
            } else if (data.role === "employee"){
                window.location.href = "../HTML CODES/dashboard.html"
            } else {
                alert("Login Successful!")
                window.location.href = "../HTML CODES/Home_page.php";
            }
        } else {
            alert(data.message);
            
        }
    })
    .catch(error => {
        console.error("Error:", error);
        alert("An error occurred. Please try again.");
    });
}
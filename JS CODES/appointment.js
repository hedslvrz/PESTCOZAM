document.getElementById('bookAppointment').addEventListener('click', function(event) {
    // Prevent default form submission if this button is inside a form
    if (this.form) {
        event.preventDefault();
    }
    
    let isForSelf = document.getElementById('is_for_self').checked ? 1 : 0; // Checkbox for self-appointment

    let formData = {
        user_id: document.getElementById('user_id').value,
        service_id: document.getElementById('service_id').value,
        region: document.getElementById('region').value,
        province: document.getElementById('province').value,
        city: document.getElementById('city').value,
        barangay: document.getElementById('barangay').value,
        street_address: document.getElementById('street_address').value,
        appointment_date: document.getElementById('appointment_date').value,
        appointment_time: document.getElementById('appointment_time').value,
        is_for_self: isForSelf
    };

    // If not booking for self, include additional fields
    if (!isForSelf) {
        formData.firstname = document.getElementById('first_name').value;
        formData.lastname = document.getElementById('last_name').value;
        formData.email = document.getElementById('email').value;
        formData.mobile_number = document.getElementById('mobile_number').value;
    }

    // First clear any existing appointment session
    fetch('PHP CODES/clear_appointment_session.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(sessionData => {
        // Only proceed with appointment creation if session was cleared
        if (sessionData.success) {
            // Create new appointment
            return fetch('PHP CODES/appointment_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });
        } else {
            throw new Error('Failed to clear session');
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: data.message,
                confirmButtonColor: '#144578',
                timer: 2000,
                timerProgressBar: true,
                showConfirmButton: false,
                didClose: () => {
                    window.location.href = "appointment-success.php";
                }
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message,
                confirmButtonColor: '#144578',
                confirmButtonText: 'OK'
            });
        }
    })
    .catch(error => console.error('Error:', error));
});

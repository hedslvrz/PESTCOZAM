document.getElementById('bookAppointment').addEventListener('click', function() {
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
        formData.first_name = document.getElementById('first_name').value;
        formData.last_name = document.getElementById('last_name').value;
        formData.email = document.getElementById('email').value;
        formData.mobile_number = document.getElementById('mobile_number').value;
    }

    fetch('Insert-Appointment-Api.php', {  
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
        if (data.success) {
            window.location.href = "appointment-success.php";
        }
    })
    .catch(error => console.error('Error:', error));
});

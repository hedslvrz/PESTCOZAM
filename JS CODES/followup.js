/**
 * JavaScript functionality for the follow-up scheduling form
 */

// Debug function to log values to console
function debugLog(message, value) {
    console.log(`[FOLLOWUP DEBUG] ${message}:`, value);
}

// Loads customer details when an appointment is selected in the follow-up form
function loadCustomerDetails(appointmentId) {
    if (!appointmentId) {
        // Clear form fields if no appointment is selected
        document.getElementById('service-type').value = '';
        document.getElementById('customer-location').value = '';
        // For multi-select, clear all selections
        const techSelect = document.getElementById('technician-select');
        if (techSelect) {
            for (let opt of techSelect.options) {
                opt.selected = false;
            }
        }
        return;
    }
    
    console.log('Loading details for appointment ID:', appointmentId);
    
    // Get the selected option element
    const selectedOption = document.querySelector(`#customer-select option[value="${appointmentId}"]`);
    if (!selectedOption) {
        console.error('Selected option not found for appointment ID:', appointmentId);
        return;
    }
    
    // Extract data from data attributes
    const serviceId = selectedOption.getAttribute('data-service');
    const location = selectedOption.getAttribute('data-location');
    const allTechnicians = selectedOption.getAttribute('data-all-technicians');
    const allTechnicianNames = selectedOption.getAttribute('data-all-technician-names');
    
    console.log('Appointment details found:', { serviceId, location, allTechnicians });
    
    // Set service type and customer location if available
    if (serviceId) document.getElementById('service-type').value = serviceId;
    if (location) document.getElementById('customer-location').value = location;
    
    const technicianSelect = document.getElementById('technician-select');
    if (technicianSelect) {
        // Clear previous selections
        for (let opt of technicianSelect.options) {
            opt.selected = false;
        }
        
        let technicianIds = [];
        let technicianNamesArr = [];
        
        if (allTechnicians && allTechnicians.trim() !== "" && allTechnicians.toLowerCase() !== "null") {
            technicianIds = allTechnicians.split(',').map(item => item.trim());
            if (allTechnicianNames && allTechnicianNames.trim() !== "") {
                technicianNamesArr = allTechnicianNames.split(',').map(item => item.trim());
            }
        }
        // Fallback to single technician if no multi data exist
        if (technicianIds.length === 0) {
            const singleTech = selectedOption.getAttribute('data-technician');
            if (singleTech && singleTech.trim() !== '') {
                technicianIds.push(singleTech.trim());
                technicianNamesArr.push(selectedOption.getAttribute('data-technician-name') || ('Technician ' + singleTech));
            }
        }
        
        // Remove duplicate IDs while preserving order and names
        let uniqueIds = [];
        let uniqueNames = [];
        technicianIds.forEach((id, index) => {
            if (uniqueIds.indexOf(id) === -1) {
                uniqueIds.push(id);
                uniqueNames.push(technicianNamesArr[index] || ('Technician ' + id));
            }
        });
        
        // Add missing technician options and mark selected for each unique id
        uniqueIds.forEach((id, idx) => {
            let optionExists = Array.from(technicianSelect.options).some(opt => opt.value === id);
            if (!optionExists && id) {
                const newOption = document.createElement('option');
                newOption.value = id;
                newOption.textContent = uniqueNames[idx];
                technicianSelect.appendChild(newOption);
            }
            // Mark option selected
            for (let opt of technicianSelect.options) {
                if (opt.value === id) {
                    opt.selected = true;
                }
            }
        });
        console.log("Setting technicians:", uniqueIds.join(', '));
    }
}

// Prevent duplicate submissions
let isSubmitting = false;

// Schedules a follow-up appointment
function scheduleFollowUp() {
    if (isSubmitting) return;
    isSubmitting = true;
    
    const customerSelect = document.getElementById('customer-select');
    const appointmentId = customerSelect.value;
    const serviceId = document.getElementById('service-type').value;
    const techSelect = document.getElementById('technician-select');
    
    // Get all selected technicians (multi-select)
    let selectedTechnicians = [];
    for (let i = 0; i < techSelect.options.length; i++) {
        if (techSelect.options[i].selected) {
            selectedTechnicians.push(techSelect.options[i].value);
        }
    }
    
    const technicianIds = selectedTechnicians.join(',');
    const followupDate = document.getElementById('followup-date').value;
    const followupTime = document.getElementById('followup-time').value;
    
    // Debug log all input values
    console.log("Form Data:", { appointmentId, serviceId, technicianIds, followupDate, followupTime });
    
    if (!appointmentId) {
        showModal("Validation Error", "No appointment selected. Please choose a customer from the list.");
        isSubmitting = false;
        return;
    }
    if (!serviceId) {
        showModal("Validation Error", "Service type is not selected.");
        isSubmitting = false;
        return;
    }
    if (!technicianIds) {
        showModal("Validation Error", "No technician selected.");
        isSubmitting = false;
        return;
    }
    if (!followupDate) {
        showModal("Validation Error", "Please select a follow-up date.");
        isSubmitting = false;
        return;
    }
    if (!followupTime) {
        showModal("Validation Error", "Please select a follow-up time.");
        isSubmitting = false;
        return;
    }
    
    console.log("Scheduling follow-up with data:", { appointmentId, serviceId, technicianIds, followupDate, followupTime });
    
    const formData = new FormData();
    formData.append('appointment_id', appointmentId);
    formData.append('service_id', serviceId);
    formData.append('technician_id', technicianIds);
    formData.append('followup_date', followupDate);
    formData.append('followup_time', followupTime);
    
    const scheduleBtn = document.getElementById('schedule-followup-btn');
    scheduleBtn.disabled = true;
    scheduleBtn.innerHTML = '<i class="bx bx-loader-circle bx-spin"></i> Processing...';
    
    fetch('schedule_followup.php', { method: 'POST', body: formData })
    .then(response => {
        // Log the raw response for debugging
        response.clone().text().then(text => {
            console.log("Raw server response:", text);
        });
        return response.json();
    })
    .then(data => {
        scheduleBtn.disabled = false;
        scheduleBtn.innerHTML = '<i class="bx bx-calendar-check"></i> Schedule Follow-up';
        isSubmitting = false;
        if (data.success) {
            showModal('Success', `Follow-up appointment #${data.appointment_id} scheduled successfully!`);
            // Clear form fields
            document.getElementById('customer-select').value = '';
            document.getElementById('service-type').value = '';
            document.getElementById('customer-location').value = '';
            for (let opt of techSelect.options) { opt.selected = false; }
            document.getElementById('followup-date').value = '';
            document.getElementById('followup-time').value = '';
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showModal('Error', data.message || 'Failed to schedule follow-up.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        scheduleBtn.disabled = false;
        scheduleBtn.innerHTML = '<i class="bx bx-calendar-check"></i> Schedule Follow-up';
        isSubmitting = false;
        showModal('Error', 'An unexpected error occurred. Please try again.');
    });
}

// Shows a modal with a message
function showModal(title, message) {
    const modal = document.getElementById('customModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalMessage = document.getElementById('modalMessage');
    
    if (!modal || !modalTitle || !modalMessage) {
        alert(`${title}: ${message}`);
        return;
    }
    
    modalTitle.textContent = title;
    modalMessage.textContent = message;
    modal.style.display = 'block';
    
    const modalOkBtn = document.getElementById('modalOkBtn');
    if (modalOkBtn) {
        modalOkBtn.onclick = function() { modal.style.display = 'none'; };
    }
    
    const closeBtn = modal.querySelector('.close');
    if (closeBtn) { closeBtn.onclick = function() { modal.style.display = 'none'; }; }
    
    window.onclick = function(event) { if (event.target === modal) { modal.style.display = 'none'; } };
}

// Single DOMContentLoaded event listener attaching necessary event handlers
document.addEventListener('DOMContentLoaded', function() {
    const customerSelect = document.getElementById('customer-select');
    if (customerSelect) {
        customerSelect.addEventListener('change', function() {
            loadCustomerDetails(this.value);
        });
    }
    
    const scheduleBtn = document.getElementById('schedule-followup-btn');
    if (scheduleBtn) {
        scheduleBtn.addEventListener('click', scheduleFollowUp);
    }
    
    debugLog("Follow-up script initialized", "Ready");
});

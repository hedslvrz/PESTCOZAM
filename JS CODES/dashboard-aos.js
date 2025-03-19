// Sidebar toggle
const menuBar = document.querySelector('#main-navbar .bx-menu');
const sidebar = document.getElementById('sidebar');

menuBar.addEventListener('click', function () {
    sidebar.classList.toggle('hide');
});

// Section display
function showSection(sectionId) {
    // Hide all sections
    document.querySelectorAll('.section').forEach(section => {
        section.classList.remove('active');
    });
    
    // Show selected section
    document.getElementById(sectionId).classList.add('active');
    
    // Update active menu item
    document.querySelectorAll('.side-menu li').forEach(item => {
        item.classList.remove('active');
    });
    
    // Find and activate the menu item
    const menuItem = document.querySelector(`a[href="#${sectionId}"]`).parentElement;
    menuItem.classList.add('active');
}

// Remove the duplicate DOMContentLoaded event listeners
// and replace with the new one
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('assignTechModal');
    const form = document.getElementById('assignTechForm');
    const closeBtn = modal.querySelector('.close');
    const cancelBtn = modal.querySelector('.btn-cancel');

    // Close modal function
    function closeModal() {
        modal.style.display = 'none';
        form.reset();
    }

    // Close modal event listeners
    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);
    window.onclick = function(event) {
        if (event.target === modal) {
            closeModal();
        }
    };

    // Handle form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            appointment_id: document.getElementById('appointmentId').value,
            technician_id: document.getElementById('technicianId').value
        };

        console.log('Sending data:', formData); // Debug log

        if (!formData.technician_id) {
            alert('Please select a technician');
            return;
        }

        fetch('../PHP CODES/assign_technician.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        })
        .then(response => {
            console.log('Response status:', response.status); // Debug log
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Failed to parse JSON:', text);
                    throw new Error('Invalid JSON response from server');
                }
            });
        })
        .then(data => {
            console.log('Parsed response:', data); // Debug log
            if (data.success) {
                alert('Technician assigned successfully!');
                closeModal();
                location.reload();
            } else {
                throw new Error(data.message || 'Failed to assign technician');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error assigning technician: ' + error.message);
        });
    });

    // Filter functionality
    const filterButtons = document.querySelectorAll('.filter-btn');
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            const filter = this.getAttribute('data-filter');
            
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach(row => {
                if (filter === 'all' || row.getAttribute('data-status') === filter) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });

    // Logout confirmation
    const logoutLink = document.querySelector('a[href*="logout"]');
    if (logoutLink) {
        logoutLink.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = this.href;
            }
        });
    }
});

// Add this for handling inline technician assignment
document.addEventListener('DOMContentLoaded', function() {
    // Get all inline assignment forms
    const assignForms = document.querySelectorAll('.inline-assign-form');
    
    assignForms.forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const appointmentId = this.dataset.appointmentId;
            const technicianId = this.querySelector('.tech-select').value;
            
            if (!technicianId) {
                customModal.showWarning('Please select a technician');
                return;
            }
            
            try {
                const response = await fetch('../PHP CODES/assign_technician.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        appointment_id: appointmentId,
                        technician_id: technicianId
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    customModal.showSuccess('Technician assigned successfully!', () => {
                        window.location.reload();
                    });
                } else {
                    customModal.showError(result.message || 'Failed to assign technician');
                }
            } catch (error) {
                console.error('Error:', error);
                customModal.showError('An error occurred while assigning the technician');
            }
        });
    });
});

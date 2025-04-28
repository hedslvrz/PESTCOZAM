const allSideMenu = document.querySelectorAll('#sidebar .side-menu.top li a');

allSideMenu.forEach(item=> {
    const li = item.parentElement;

    item.addEventListener('click', function(){
        allSideMenu.forEach(i=> {
            i.parentElement.classList.remove('active');
        })
        li.classList.add('active');
    })
});


// TOGGLE SIDEBAR //
const allMenus = document.querySelectorAll('.bx-menu');
const sidebar = document.getElementById('sidebar');

allMenus.forEach(menu => {
    menu.addEventListener('click', function() {
        sidebar.classList.toggle('hide');
    });
});
// TOGGLE SIDEBAR //


if(window.innerWidth <768){
    sidebar.classList.add('hide');
} else if(window.innerWidth < 576){
    
}

document.addEventListener('DOMContentLoaded', function() {
    // Search functionality with null checks
    const searchButton = document.querySelector('#content nav form .form-input button');
    const searchForm = document.querySelector('#content nav form');
    
    if (searchButton && searchForm) {
        const searchButtonIcon = searchButton.querySelector('.bx');
        
        searchButton.addEventListener('click', function(e) {
            if (window.innerWidth < 576) {
                e.preventDefault();
                searchForm.classList.toggle('show');
                if (searchForm.classList.contains('show') && searchButtonIcon) {
                    searchButtonIcon.classList.replace('bx-search', 'bx-x');
                }
            }
        });
    }

    // Initialize technician assignment forms
    const assignForms = document.querySelectorAll('.inline-assign-form');
    
    if (assignForms.length > 0) {
        assignForms.forEach(form => {
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const appointmentId = this.dataset.appointmentId;
                const techSelect = this.querySelector('.tech-select');
                
                if (!techSelect) {
                    console.error('Technician select element not found');
                    return;
                }
                
                const technicianId = techSelect.value;
                
                if (!technicianId) {
                    alert('Please select a technician');
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
                    
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    
                    const result = await response.json();
                    console.log('Server response:', result);
                    
                    if (result.success) {
                        // Update the UI with assigned technician
                        const row = form.closest('tr');
                        const techCell = row.querySelector('.tech-info');
                        const statusCell = row.querySelector('.status');
                        
                        if (techCell) {
                            techCell.innerHTML = `
                                <i class='bx bx-user-check'></i>
                                <span>${result.technician.firstname} ${result.technician.lastname}</span>
                            `;
                        }
                        
                        if (statusCell) {
                            statusCell.textContent = 'Confirmed';
                            statusCell.className = 'status confirmed';
                        }
                        
                        alert('Technician assigned successfully!');
                    } else {
                        alert(result.message || 'Failed to assign technician');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('An error occurred while processing your request');
                }
            });
        });
    }

    // Filter functionality
    const filterButtons = document.querySelectorAll('.filter-btn');
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            const filter = this.getAttribute('data-filter');
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach(row => {
                if (filter === 'all' || row.querySelector('.status').textContent.toLowerCase() === filter) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });

    // Initialize report cards
    initializeReportCards();

    // Add modal close handlers
    const closeButtons = document.querySelectorAll('.close-modal');
    closeButtons.forEach(button => {
        button.addEventListener('click', closeReportModal);
    });

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('reportModal');
        if (event.target === modal) {
            closeReportModal();
        }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeReportModal();
        }
    });

    const timeSlotForm = document.getElementById('time-slot-form');

    if (timeSlotForm) {
        timeSlotForm.addEventListener('submit', function (e) {
            const appointmentId = document.getElementById('appointment-id').value.trim();
            const newDate = document.getElementById('new-date').value;
            const newTime = document.getElementById('new-time').value;

            if (!appointmentId || !newDate || !newTime) {
                e.preventDefault();
                alert('Please fill out all fields before submitting.');
            }
        });
    }

    // Initialize calendar for time slot management
    initTimeSlotCalendar();

    // Initialize profile editor functionality
    initProfileEditor();
});

// Technician Assignment Functions
function openAssignModal(appointmentId) {
    const modal = document.getElementById('assignTechModal');
    document.getElementById('appointmentId').value = appointmentId;
    modal.style.display = 'block';
}

function closeAssignModal() {
    const modal = document.getElementById('assignTechModal');
    modal.style.display = 'none';
}

// Handle technician assignment form submission
document.getElementById('assignTechForm').addEventListener('submit', handleAssignSubmit);

function handleAssignSubmit(e) {
    e.preventDefault();
    
    const appointmentId = document.getElementById('appointmentId').value;
    const technicianId = document.getElementById('technicianId').value;

    if (!technicianId) {
        alert('Please select a technician');
        return;
    }

    const formData = {
        appointment_id: appointmentId,
        technician_id: technicianId
    };

    fetch('../PHP CODES/assign_technician.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Technician assigned successfully!');
            closeAssignModal();
            location.reload(); // Refresh to show updated data
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while assigning technician');
    });
}

// Handle technician assignment
document.addEventListener('DOMContentLoaded', function() {
    const assignForms = document.querySelectorAll('.inline-assign-form');
    
    assignForms.forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const appointmentId = this.dataset.appointmentId;
            const techSelect = this.querySelector('.tech-select');
            const technicianId = techSelect.value;
            const technicianName = techSelect.options[techSelect.selectedIndex].text;
            
            if (!technicianId) {
                alert('Please select a technician');
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
                    alert(`Technician successfully ${technicianId ? 'updated' : 'assigned'}!`);
                    window.location.reload();
                } else {
                    alert(result.message || 'Failed to assign technician');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while processing your request');
            }
        });
    });
});

// Add filtering functionality
document.querySelectorAll('.filter-btn').forEach(button => {
    button.addEventListener('click', function() {
        // Remove active class from all buttons
        document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
        // Add active class to clicked button
        this.classList.add('active');
        
        // Filter table rows based on status
        const filter = this.dataset.filter;
        const rows = document.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const status = row.querySelector('.status').textContent.toLowerCase();
            if (filter === 'all' || status === filter) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
});

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('assignTechModal');
    if (event.target === modal) {
        closeAssignModal();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Initialize form handler if it exists
    const assignForm = document.getElementById('assignTechForm');
    if (assignForm) {
        assignForm.addEventListener('submit', handleAssignSubmit);
    }
    
    // Filter functionality
    const filterButtons = document.querySelectorAll('.filter-btn');
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            const filter = this.getAttribute('data-filter');
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach(row => {
                if (filter === 'all' || row.querySelector('.status').textContent.toLowerCase() === filter) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });
});

// Improved section navigation with proper hiding
function showSection(sectionId) {
    // Hide all sections
    document.querySelectorAll('.section').forEach(section => {
        section.classList.remove('active');
        section.style.display = 'none'; // Ensure all sections are hidden
        section.style.pointerEvents = 'none'; // Disable interaction with hidden sections
        section.style.opacity = '0'; // Make hidden sections invisible
        section.style.zIndex = '-1'; // Push hidden sections behind the active section
    });

    // Hide all modals when changing sections
    hideAllModals();

    // Show the selected section
    const targetSection = document.getElementById(sectionId);
    if (targetSection) {
        targetSection.style.display = 'block'; // Display the selected section
        targetSection.style.pointerEvents = 'auto'; // Enable interaction with the active section
        targetSection.style.opacity = '1'; // Make the active section visible
        targetSection.style.zIndex = '10'; // Bring the active section to the front
        targetSection.classList.add('active'); // Mark it as active

        // Update the active menu item in the sidebar
        document.querySelectorAll('#sidebar .side-menu.top li').forEach(item => {
            item.classList.remove('active');
        });
        const menuItem = document.querySelector(`#sidebar .side-menu.top li a[href="#${sectionId}"]`).parentElement;
        if (menuItem) {
            menuItem.classList.add('active');
        }

        // Scroll back to top when changing sections
        window.scrollTo(0, 0);
    }
}

// Ensure the dashboard section is shown by default on page load
document.addEventListener('DOMContentLoaded', function() {
    showSection('content'); // Default section to show
});

// Updated Report Modal Functions
function openReportModal(reportId) {
    console.log(`Opening report modal for ID: ${reportId}`);
    const modal = document.getElementById('reportModal');

    if (!modal) {
        console.error('Report modal element not found.');
        return;
    }

    // Find the report data from the global reportsData array
    const reportData = window.reportsData.find(report => report.report_id == reportId);

    if (!reportData) {
        console.error(`Report data not found for ID: ${reportId}`);
        return;
    }

    // Populate the modal fields
    document.getElementById('reportIdField').value = reportId;
    document.getElementById('reportIdDisplay').value = `#${reportId}`;
    document.getElementById('reportDateField').value = reportData.date_of_treatment || 'N/A';
    document.getElementById('techNameField').value = reportData.tech_name || 'N/A';
    document.getElementById('clientNameField').value = reportData.account_name || 'N/A';
    document.getElementById('locationField').value = reportData.location || 'N/A';
    document.getElementById('contactNoField').value = reportData.contact_no || 'N/A';
    document.getElementById('treatmentTypeField').value = reportData.treatment_type || 'N/A';
    document.getElementById('treatmentMethodField').value = reportData.treatment_method || 'N/A';
    document.getElementById('timeInField').value = reportData.time_in || 'N/A';
    document.getElementById('timeOutField').value = reportData.time_out || 'N/A';
    document.getElementById('pestCountField').value = reportData.pest_count || 'N/A';
    document.getElementById('deviceInstallationField').value = reportData.device_installation || 'N/A';
    document.getElementById('chemicalsField').value = reportData.consumed_chemicals || 'N/A';
    document.getElementById('frequencyField').value = reportData.frequency_of_visits || 'N/A';

    // Handle photos
    const photosContainer = document.getElementById('photosContainer');
    if (!photosContainer) {
        console.error('Photos container element not found.');
        return;
    }
    photosContainer.innerHTML = ''; // Clear existing photos
    if (reportData.photos) {
        const photos = JSON.parse(reportData.photos); // Assuming photos are stored as JSON
        photos.forEach(photo => {
            const img = document.createElement('img');
            img.src = `../uploads/${photo}`;
            img.alt = 'Service Photo';
            img.className = 'report-photo';
            photosContainer.appendChild(img);
        });
    } else {
        photosContainer.innerHTML = '<p>No photos available</p>';
    }

    // Show the modal
    modal.style.display = 'flex';
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeReportModal() {
    const modal = document.getElementById('reportModal');
    if (modal) {
        modal.style.display = 'none';
        modal.classList.remove('show');
        document.body.style.overflow = '';
        console.log('Report modal closed.');
    }
}

function initializeReportCards() {
    console.log('Initializing report cards...');
    
    // First, ensure the report modal is hidden
    const reportModal = document.getElementById('reportModal');
    if (reportModal) {
        reportModal.style.display = 'none';
        reportModal.classList.remove('show');
    }
    
    const reportCards = document.querySelectorAll('.report-card');

    if (reportCards.length > 0) {
        console.log(`Found ${reportCards.length} report cards.`);
        reportCards.forEach(card => {
            // Remove any existing click listeners to avoid duplicates
            const newCard = card.cloneNode(true);
            card.parentNode.replaceChild(newCard, card);

            newCard.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const reportId = this.getAttribute('data-report-id');
                console.log('Report card clicked, ID:', reportId);
                openReportModal(reportId);
            });
        });
    } else {
        console.log('No report cards found.');
    }
}

// Add a dedicated function to ensure modals are properly hidden
function hideAllModals() {
    const modals = document.querySelectorAll('.modal, #reportModal, .report-modal-content');
    modals.forEach(modal => {
        if (modal) {
            modal.style.display = 'none';
            modal.classList.remove('show');
        }
    });
    document.body.style.overflow = '';
}

// Ensure showSection also hides modals when changing sections
function showSection(sectionId) {
    // Hide all sections
    document.querySelectorAll('.section').forEach(section => {
        section.classList.remove('active');
        section.style.display = 'none'; // Ensure all sections are hidden
        section.style.pointerEvents = 'none'; // Disable interaction with hidden sections
        section.style.opacity = '0'; // Make hidden sections invisible
        section.style.zIndex = '-1'; // Push hidden sections behind the active section
    });

    // Hide all modals when changing sections
    hideAllModals();

    // Show the selected section
    const targetSection = document.getElementById(sectionId);
    if (targetSection) {
        targetSection.style.display = 'block'; // Display the selected section
        targetSection.style.pointerEvents = 'auto'; // Enable interaction with the active section
        targetSection.style.opacity = '1'; // Make the active section visible
        targetSection.style.zIndex = '10'; // Bring the active section to the front
        targetSection.classList.add('active'); // Mark it as active

        // Update the active menu item in the sidebar
        document.querySelectorAll('#sidebar .side-menu.top li').forEach(item => {
            item.classList.remove('active');
        });
        const menuItem = document.querySelector(`#sidebar .side-menu.top li a[href="#${sectionId}"]`).parentElement;
        if (menuItem) {
            menuItem.classList.add('active');
        }

        // Scroll back to top when changing sections
        window.scrollTo(0, 0);
    }
}

// Function to update report status (add this if it doesn't exist)
function updateReportStatus(status) {
    const reportId = document.getElementById('reportIdField').value;
    if (!reportId) {
        alert('Report ID not found');
        return;
    }
    
    fetch('../PHP CODES/update_report_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            report_id: reportId,
            status: status
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`Report ${status === 'approved' ? 'approved' : 'rejected'} successfully!`);
            // Refresh the page to show updated status
            location.reload();
        } else {
            alert('Error updating report status: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the report status.');
    });
}

// Add function to download report PDF if it doesn't exist
function downloadReportPDF() {
    const reportId = document.getElementById('reportIdField').value;
    const reportForm = document.getElementById('reportForm');
    
    // Hide buttons during PDF generation
    const actionButtons = document.querySelector('.form-actions');
    actionButtons.style.display = 'none';
    
    html2canvas(reportForm).then(canvas => {
        const imgData = canvas.toDataURL('image/png');
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF('p', 'mm', 'a4');
        const imgProps = pdf.getImageProperties(imgData);
        const pdfWidth = pdf.internal.pageSize.getWidth();
        const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;
        
        pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
        pdf.save(`Service_Report_${reportId}.pdf`);
        
        // Show buttons again after PDF generation
        actionButtons.style.display = 'flex';
    });
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('reportModal');
    if (event.target === modal) {
        closeReportModal();
    }
}

function showSection(sectionId) {
    // Hide all sections
    document.querySelectorAll('.section').forEach(section => {
        section.style.display = 'none';
        section.classList.remove('active');
    });

    // Show the selected section
    const targetSection = document.getElementById(sectionId);
    if (targetSection) {
        targetSection.style.display = 'block';
        targetSection.classList.add('active');

        // Update the active menu item in the sidebar
        document.querySelectorAll('#sidebar .side-menu.top li').forEach(item => {
            item.classList.remove('active');
        });
        const menuItem = document.querySelector(`#sidebar .side-menu.top li a[href="#${sectionId}"]`).parentElement;
        if (menuItem) {
            menuItem.classList.add('active');
        }

        // Initialize report cards if we're showing the reports section
        if (sectionId === 'reports') {
            initializeReportCards();
        }
    }
}

// Add click event listeners to all sidebar menu items
document.addEventListener('DOMContentLoaded', function() {
    const sideMenuItems = document.querySelectorAll('#sidebar .side-menu.top li a');
    sideMenuItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const sectionId = this.getAttribute('href').substring(1); // Remove the # from href
            showSection(sectionId);
        });
    });

    // Show dashboard by default
    showSection('content');
});

// Calendar rendering functionality
function renderCalendar() {
    const month = parseInt(monthSelect.value);
    const year = parseInt(yearSelect.value);
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const daysInMonth = lastDay.getDate();
    const today = new Date();
    
    // Clear previous calendar days
    const calendarGrid = document.querySelector('.calendar-grid');
    const dayNames = document.querySelectorAll('.day-name');
    calendarGrid.innerHTML = '';
    
    // Re-add day names
    dayNames.forEach(dayName => {
        calendarGrid.appendChild(dayName.cloneNode(true));
    });
    
    // Add empty cells for days before the first day of the month
    for (let i = 0; i < firstDay.getDay(); i++) {
        const emptyDay = document.createElement('div');
        emptyDay.className = 'day';
        calendarGrid.appendChild(emptyDay);
    }
    
    // Add days of the month
    for (let day = 1; day <= daysInMonth; day++) {
        const dayElement = document.createElement('div');
        dayElement.className = 'day';
        dayElement.textContent = day;
        
        // Check if this is today's date
        if (today.getDate() === day && 
            today.getMonth() === month && 
            today.getFullYear() === year) {
            dayElement.classList.add('today');
        }
        
        dayElement.addEventListener('click', function() {
            document.querySelectorAll('.day').forEach(d => {
                d.classList.remove('selected');
            });
            this.classList.add('selected');
            updateTimeSlots(year, month + 1, day);
        });
        
        calendarGrid.appendChild(dayElement);
    }
}

function updateTimeSlots(year, month, day) {
    const selectedDate = `${year}-${month.toString().padStart(2, '0')}-${day.toString().padStart(2, '0')}`;
    document.querySelectorAll('.time-slot').forEach(slot => {
        // Update time slot availability based on selected date
        // Add your logic here
    });
}

// Service Management Functions
function editService(serviceId) {
    window.location.href = `edit-service.php?id=${serviceId}`;
}

function confirmDeleteService(serviceId, serviceName) {
    if (confirm(`Are you sure you want to delete "${serviceName}"? This action cannot be undone.`)) {
        fetch('../PHP CODES/delete-service.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ service_id: serviceId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Service deleted successfully!');
                location.reload(); // Refresh the page to update the service list
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the service');
        });
    }
}

function openServiceDeleteModal(serviceId, serviceName) {
    document.getElementById('serviceIdToDelete').value = serviceId;
    document.getElementById('serviceToDelete').textContent = serviceName;
    document.getElementById('deleteServiceModal').style.display = 'flex';
}

function confirmDeleteService() {
    document.getElementById('deleteServiceForm').submit();
}

// Incident Report Modal Functions
function showIncidentModal(appointmentId) {
    document.getElementById('incident_appointment_id').value = appointmentId;
    document.getElementById('incidentModal').classList.add('show');
}

function closeIncidentModal() {
    document.getElementById('incidentModal').classList.remove('show');
    document.getElementById('incidentForm').reset();
}

// Add event listener for incident form submission
document.getElementById('incidentForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    try {
        const response = await fetch(this.action, {
            method: 'POST',
            body: new FormData(this)
        });
        
        const result = await response.json();
        if (result.success) {
            alert('Incident report submitted successfully');
            closeIncidentModal();
        } else {
            alert('Error submitting report: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred while submitting the report');
    }
});

// Calendar and Time Slot Management
function initTimeSlotCalendar() {
    const monthSelect = document.getElementById('monthSelect');
    const yearSelect = document.getElementById('yearSelect');
    const calendarDays = document.getElementById('calendar-days');
    const selectedDatesInput = document.getElementById('selectedDatesInput');
    const selectedDatesList = document.getElementById('selectedDatesList');
    
    // Set up year select options
    const currentYear = new Date().getFullYear();
    for (let year = currentYear; year <= currentYear + 2; year++) {
        const option = document.createElement('option');
        option.value = year;
        option.textContent = year;
        yearSelect.appendChild(option);
    }
    
    // Set current month and year as default
    const currentDate = new Date();
    monthSelect.value = currentDate.getMonth();
    yearSelect.value = currentDate.getFullYear();
    
    // Store selected dates
    let selectedDates = [];
    // Track the last selected date to clear its time slot inputs when a new date is selected
    let lastSelectedDate = null;
    
    // Render calendar
    function renderCalendar() {
        const month = parseInt(monthSelect.value);
        const year = parseInt(yearSelect.value);
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const daysInMonth = lastDay.getDate();
        const today = new Date();
        
        // Clear previous calendar days
        calendarDays.innerHTML = '';
        
        // Add empty cells for days before the first day of the month
        for (let i = 0; i < firstDay.getDay(); i++) {
            const emptyDay = document.createElement('div');
            emptyDay.className = 'day';
            calendarDays.appendChild(emptyDay);
        }
        
        // Add days of the month
        for (let day = 1; day <= daysInMonth; day++) {
            const dayElement = document.createElement('div');
            dayElement.className = 'day';
            dayElement.textContent = day;
            
            // Create date string and store it as a data attribute
            const dateString = `${year}-${(month + 1).toString().padStart(2, '0')}-${day.toString().padStart(2, '0')}`;
            dayElement.setAttribute('data-date', dateString);
            
            // Check if this is today's date
            if (today.getDate() === day && 
                today.getMonth() === month && 
                today.getFullYear() === year) {
                dayElement.classList.add('today');
            }
            
            // Check if this date is selected
            if (selectedDates.includes(dateString)) {
                dayElement.classList.add('selected');
            }
            
            // Make past dates unselectable
            const dayDate = new Date(year, month, day);
            if (dayDate < new Date(new Date().setHours(0,0,0,0))) {
                dayElement.classList.add('past');
                dayElement.style.opacity = '0.5';
                dayElement.style.cursor = 'not-allowed';
            } else {
                // Add click event for selectable days
                dayElement.addEventListener('click', function() {
                    toggleDateSelection(dateString, dayElement);
                });
            }
            
            calendarDays.appendChild(dayElement);
        }
    }
    
    // Toggle date selection
    function toggleDateSelection(dateString, dayElement) {
        const index = selectedDates.indexOf(dateString);
        
        if (index === -1) {
            // Before adding new date, reset the time slot values to default
            if (selectedDates.length > 0) {
                resetTimeSlotValues();
            }
            
            // Add date if not already selected
            selectedDates.push(dateString);
            dayElement.classList.add('selected');
            
            // Remove any other selected dates - we're implementing single date selection
            const prevSelectedDays = document.querySelectorAll('.day.selected');
            prevSelectedDays.forEach(day => {
                if (day !== dayElement) {
                    day.classList.remove('selected');
                    const dateVal = day.getAttribute('data-date');
                    if (dateVal && dateVal !== dateString) {
                        const idx = selectedDates.indexOf(dateVal);
                        if (idx !== -1) {
                            selectedDates.splice(idx, 1);
                        }
                    }
                }
            });
            
            // Set lastSelectedDate and fetch time slots for the selected date
            lastSelectedDate = dateString;
            fetchTimeSlots(dateString);
        } else {
            // Remove date if already selected
            selectedDates.splice(index, 1);
            dayElement.classList.remove('selected');
            resetTimeSlotValues();
            lastSelectedDate = null;
        }
        
        // Update hidden input and display selected dates
        selectedDatesInput.value = JSON.stringify(selectedDates);
        updateSelectedDatesDisplay();
    }
    
    // Reset time slot values to default
    function resetTimeSlotValues() {
        document.querySelectorAll('.time-slots input[type="number"]').forEach(input => {
            input.value = 3; // Reset to default value
        });
    }
    
    // Update the display of selected dates
    function updateSelectedDatesDisplay() {
        selectedDatesList.innerHTML = '';
        
        if (selectedDates.length === 0) {
            const emptyMessage = document.createElement('div');
            emptyMessage.className = 'no-dates';
            emptyMessage.textContent = 'No dates selected';
            selectedDatesList.appendChild(emptyMessage);
            return;
        }
        
        // Sort dates before displaying
        selectedDates.sort();
        
        selectedDates.forEach(dateString => {
            const date = new Date(dateString);
            const formattedDate = date.toLocaleDateString('en-US', { 
                weekday: 'short', 
                month: 'short', 
                day: 'numeric' 
            });
            
            const dateTag = document.createElement('div');
            dateTag.className = 'date-tag';
            
            const dateText = document.createElement('span');
            dateText.textContent = formattedDate;
            
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'remove-date';
            removeBtn.innerHTML = '<i class="bx bx-x"></i>';
            removeBtn.addEventListener('click', function() {
                removeDateSelection(dateString);
            });
            
            dateTag.appendChild(dateText);
            dateTag.appendChild(removeBtn);
            selectedDatesList.appendChild(dateTag);
        });
    }
    
    // Remove date selection
    function removeDateSelection(dateString) {
        const index = selectedDates.indexOf(dateString);
        if (index !== -1) {
            selectedDates.splice(index, 1);
            selectedDatesInput.value = JSON.stringify(selectedDates);
            
            // Reset time slot values if the removed date was the last selected date
            if (dateString === lastSelectedDate) {
                resetTimeSlotValues();
                lastSelectedDate = null;
            }
            
            // Re-render calendar to update UI
            renderCalendar();
            updateSelectedDatesDisplay();
        }
    }
    
    // Fetch time slots for a selected date
    function fetchTimeSlots(date) {
        // Show loading indicator
        const timeSlotContainers = document.querySelectorAll('.time-slot');
        timeSlotContainers.forEach(container => {
            const loadingIndicator = document.createElement('div');
            loadingIndicator.className = 'loading-indicator';
            loadingIndicator.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Loading...';
            container.appendChild(loadingIndicator);
        });
        
        fetch(`../PHP CODES/fetch_timeslot.php?date=${date}`)
            .then(response => response.json())
            .then(data => {
                // Remove loading indicators
                document.querySelectorAll('.loading-indicator').forEach(indicator => {
                    indicator.remove();
                });
                
                if (data.error) {
                    console.error('Error fetching time slots:', data.error);
                    return;
                }
                
                // Reset to default values first
                resetTimeSlotValues();
                
                // Update the slot limits with fetched data
                if (data.slots && data.slots.length > 0) {
                    data.slots.forEach(slot => {
                        const inputElement = document.querySelector(`input[name="time_slots[${slot.slot_name}]"]`);
                        if (inputElement) {
                            inputElement.value = slot.slot_limit;
                        }
                    });
                }
            })
            .catch(error => {
                // Remove loading indicators on error
                document.querySelectorAll('.loading-indicator').forEach(indicator => {
                    indicator.remove();
                });
                console.error('Error fetching time slots:', error);
            });
    }
    
    // Event listeners for month and year changes
    monthSelect.addEventListener('change', renderCalendar);
    yearSelect.addEventListener('change', renderCalendar);
    
    // Initial render
    renderCalendar();
    updateSelectedDatesDisplay();
}

// Initialize time slot calendar when page loads
document.addEventListener('DOMContentLoaded', function() {
    initTimeSlotCalendar();
});

// PROFILE SECTION FUNCTIONS - Added from dashboard-aos.js
function openProfileModal() {
    const modal = document.getElementById('profileModal');
    if (!modal) return;
    modal.style.display = 'flex';
    // Force reflow then add show class for transition
    void modal.offsetWidth;
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeProfileModal() {
    const modal = document.getElementById('profileModal');
    if (!modal) return;
    modal.classList.remove('show');
    setTimeout(() => {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }, 300);
}

function updateUserProfile(form) {
    const submitBtn = form.querySelector('[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Saving...';
    submitBtn.disabled = true;

    const formData = new FormData(form);

    fetch('../PHP CODES/update_admin_profile.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(text => {
        console.log('Raw server response:', text);
        let data = null;
        try {
            // Try to parse as JSON only if it looks like JSON
            if (text.trim().startsWith('{') || text.trim().startsWith('[')) {
                data = JSON.parse(text);
            } else if (text.toLowerCase().includes('success')) {
                data = { success: true, message: 'Profile updated successfully' };
            } else {
                throw new Error('Response is not valid JSON');
            }
        } catch (e) {
            console.error('JSON parse error:', e, 'Response text:', text);
            alert('Error updating profile. Invalid response from server.');
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
            return;
        }
        if (data && data.success) {
            alert('Profile updated successfully!');
            updateProfileDisplay(formData);
            closeProfileModal();
            history.replaceState(null, null, location.pathname);
        } else {
            alert('Error: ' + (data && data.message ? data.message : 'Update failed'));
        }
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    })
    .catch(error => {
        console.error('Error updating profile:', error);
        alert('An error occurred while updating profile: ' + error.message);
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
}

function updateProfileDisplay(formData) {
    const firstname = formData.get('firstname');
    const lastname = formData.get('lastname');
    const email = formData.get('email');
    
    // Update Profile Card (using .profile-info)
    const profileName = document.querySelector('.profile-info h3');
    const profileEmail = document.querySelector('.profile-info p');
    if (profileName) profileName.textContent = `${firstname} ${lastname}`;
    if (profileEmail) profileEmail.textContent = email;
    
    // Update details in personal information section
    const fnameDetail = document.querySelector('[data-field="firstname"]');
    const middlenameDetail = document.querySelector('[data-field="middlename"]');
    const lnameDetail = document.querySelector('[data-field="lastname"]');
    const emailDetail = document.querySelector('[data-field="email"]');
    const phoneDetail = document.querySelector('[data-field="mobile_number"]');
    if (fnameDetail) fnameDetail.textContent = firstname;
    if (middlenameDetail) middlenameDetail.textContent = formData.get('middlename') || 'Not set';
    if (lnameDetail) lnameDetail.textContent = lastname;
    if (emailDetail) emailDetail.textContent = email;
    if (phoneDetail) phoneDetail.textContent = formData.get('mobile_number');
}

// Initialize profile editor functionality
function initProfileEditor() {
    const editProfileBtn = document.getElementById('openProfileModalBtn');
    const profileModal = document.getElementById('profileModal');
    const closeBtn = profileModal ? profileModal.querySelector('.close') : null;
    const cancelBtn = document.getElementById('closeProfileModalBtn');
    const profileForm = document.getElementById('editProfileForm');
    
    if (editProfileBtn && profileModal && profileForm) {
        editProfileBtn.addEventListener('click', function() {
            openProfileModal();
        });
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                closeProfileModal();
            });
        }
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function() {
                closeProfileModal();
            });
        }
        window.addEventListener('click', function(event) {
            if (event.target === profileModal) {
                closeProfileModal();
            }
            });
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && profileModal.classList.contains('show')) {
                closeProfileModal();
            }
        });
        profileForm.addEventListener('submit', function(e) {
            e.preventDefault();
            updateUserProfile(this);
        });
    }
}

// Navigation functionality
const sidebarToggle = document.querySelector('#main-navbar .bx.bx-menu');
const allSideDivider = document.querySelectorAll('#sidebar .divider');

function handleSidebar() {
    if (sidebar && sidebarToggle) {
        sidebarToggle.addEventListener('click', function () {
            sidebar.classList.toggle('hide');
        });
    }
}

// Section navigation
function showSection(sectionId) {
    const sections = document.querySelectorAll('.section');
    sections.forEach(section => {
        section.classList.remove('active');
    });
    
    const sectionToShow = document.getElementById(sectionId);
    if (sectionToShow) {
        sectionToShow.classList.add('active');
    }
    
    // Update the active item in the sidebar
    const sideMenuItems = document.querySelectorAll('#sidebar .side-menu li');
    sideMenuItems.forEach(item => {
        item.classList.remove('active');
    });
    
    // Find and activate the menu item that triggered this
    const activeItem = document.querySelector(`#sidebar .side-menu li a[href="#${sectionId}"]`);
    if (activeItem) {
        activeItem.parentElement.classList.add('active');
    }
    
    // If sidebar is in mobile view, hide it after selection
    if (window.innerWidth <= 768) {
        sidebar.classList.add('hide');
    }
}

// Calendar functionality for time slot management
function initializeCalendar() {
    const monthSelect = document.getElementById('monthSelect');
    const yearSelect = document.getElementById('yearSelect');
    const calendarDays = document.getElementById('calendar-days');
    
    if (!monthSelect || !yearSelect || !calendarDays) return;
    
    // Populate year dropdown
    const currentYear = new Date().getFullYear();
    for (let year = currentYear; year <= currentYear + 5; year++) {
        const option = document.createElement('option');
        option.value = year;
        option.textContent = year;
        yearSelect.appendChild(option);
    }
    
    // Set current month and year
    const currentDate = new Date();
    monthSelect.value = currentDate.getMonth();
    yearSelect.value = currentDate.getFullYear();
    
    // Initialize selected dates array
    let selectedDates = [];
    
    // Generate calendar
    function generateCalendar() {
        const month = parseInt(monthSelect.value);
        const year = parseInt(yearSelect.value);
        
        // Clear previous calendar
        while (calendarDays.firstChild) {
            calendarDays.removeChild(calendarDays.firstChild);
        }
        
        // Get first day of month and number of days
        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        
        // Add empty cells for days before the first day of month
        for (let i = 0; i < firstDay; i++) {
            const emptyDay = document.createElement('div');
            emptyDay.className = 'day';
            calendarDays.appendChild(emptyDay);
        }
        
        // Add days of the month
        const today = new Date();
        const currentMonth = today.getMonth();
        const currentYear = today.getFullYear();
        const currentDay = today.getDate();
        
        for (let day = 1; day <= daysInMonth; day++) {
            const dayElement = document.createElement('div');
            dayElement.className = 'day';
            dayElement.textContent = day;
            
            // Check if day is today
            if (day === currentDay && month === currentMonth && year === currentYear) {
                dayElement.classList.add('today');
            }
            
            // Check if day is in past
            const dayDate = new Date(year, month, day);
            if (dayDate < new Date(new Date().setHours(0,0,0,0))) {
                dayElement.classList.add('past');
            } else {
                // Only allow selection of future dates
                dayElement.addEventListener('click', function() {
                    if (dayElement.classList.contains('past')) return;
                    
                    const dateString = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                    
                    // Toggle selection
                    if (dayElement.classList.contains('selected')) {
                        dayElement.classList.remove('selected');
                        selectedDates = selectedDates.filter(date => date !== dateString);
                    } else {
                        dayElement.classList.add('selected');
                        selectedDates.push(dateString);
                    }
                    
                    // Update hidden input and display
                    updateSelectedDatesDisplay();
                });
            }
            
            // Check if day is already selected
            const dateString = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            if (selectedDates.includes(dateString)) {
                dayElement.classList.add('selected');
            }
            
            calendarDays.appendChild(dayElement);
        }
    }
    
    // Update display of selected dates
    function updateSelectedDatesDisplay() {
        const selectedDatesList = document.getElementById('selectedDatesList');
        const selectedDatesInput = document.getElementById('selectedDatesInput');
        
        if (!selectedDatesList || !selectedDatesInput) return;
        
        // Clear previous content
        selectedDatesList.innerHTML = '';
        
        // Update hidden input
        selectedDatesInput.value = JSON.stringify(selectedDates);
        
        if (selectedDates.length === 0) {
            selectedDatesList.innerHTML = '<div class="no-dates">No dates selected</div>';
            return;
        }
        
        // Sort dates
        selectedDates.sort();
        
        // Add date tags
        selectedDates.forEach(dateString => {
            const date = new Date(dateString);
            const formattedDate = date.toLocaleDateString('en-US', { 
                weekday: 'short', 
                month: 'short', 
                day: 'numeric', 
                year: 'numeric' 
            });
            
            const dateTag = document.createElement('div');
            dateTag.className = 'date-tag';
            dateTag.innerHTML = `
                <span>${formattedDate}</span>
                <button type="button" class="remove-date" data-date="${dateString}">
                    <i class='bx bx-x'></i>
                </button>
            `;
            
            selectedDatesList.appendChild(dateTag);
        });
        
        // Add event listeners to remove buttons
        document.querySelectorAll('.remove-date').forEach(button => {
            button.addEventListener('click', function() {
                const dateToRemove = this.getAttribute('data-date');
                selectedDates = selectedDates.filter(date => date !== dateToRemove);
                updateSelectedDatesDisplay();
                
                // Also update the calendar view
                generateCalendar();
            });
        });
    }
    
    // Calendar navigation
    monthSelect.addEventListener('change', generateCalendar);
    yearSelect.addEventListener('change', generateCalendar);
    
    // Initialize calendar
    generateCalendar();
    updateSelectedDatesDisplay();
}

// Initialize all functions on DOM content loaded
document.addEventListener('DOMContentLoaded', function() {
    handleSidebar();
    
    // Initialize active section based on URL hash if present
    const hash = window.location.hash.substring(1);
    if (hash) {
        showSection(hash);
    }
    
    // Initialize calendar if the time slot management section exists
    initializeCalendar();
});

// Function to open the report modal
function openReportModal(reportId) {
    console.log(`Opening report modal for ID: ${reportId}`);
    const modal = document.getElementById('reportModal');

    if (!modal) {
        console.error('Report modal element not found.');
        return;
    }

    // Find the report data from the global reportsData array
    const reportData = window.reportsData.find(report => report.report_id == reportId);

    if (!reportData) {
        console.error(`Report data not found for ID: ${reportId}`);
        return;
    }

    // Populate the modal fields
    document.getElementById('reportIdField').value = reportId;
    document.getElementById('reportIdDisplay').value = `#${reportId}`;
    document.getElementById('reportDateField').value = reportData.date_of_treatment || 'N/A';
    document.getElementById('techNameField').value = reportData.tech_name || 'N/A';
    document.getElementById('clientNameField').value = reportData.account_name || 'N/A';
    document.getElementById('locationField').value = reportData.location || 'N/A';
    document.getElementById('contactNoField').value = reportData.contact_no || 'N/A';
    document.getElementById('treatmentTypeField').value = reportData.treatment_type || 'N/A';
    document.getElementById('treatmentMethodField').value = reportData.treatment_method || 'N/A';
    document.getElementById('timeInField').value = reportData.time_in || 'N/A';
    document.getElementById('timeOutField').value = reportData.time_out || 'N/A';
    document.getElementById('pestCountField').value = reportData.pest_count || 'N/A';
    document.getElementById('deviceInstallationField').value = reportData.device_installation || 'N/A';
    document.getElementById('chemicalsField').value = reportData.consumed_chemicals || 'N/A';
    document.getElementById('frequencyField').value = reportData.frequency_of_visits || 'N/A';

    // Handle photos
    const photosContainer = document.getElementById('photosContainer');
    if (!photosContainer) {
        console.error('Photos container element not found.');
        return;
    }
    photosContainer.innerHTML = ''; // Clear existing photos
    if (reportData.photos) {
        const photos = JSON.parse(reportData.photos); // Assuming photos are stored as JSON
        photos.forEach(photo => {
            const img = document.createElement('img');
            img.src = `../uploads/${photo}`;
            img.alt = 'Service Photo';
            img.className = 'report-photo';
            photosContainer.appendChild(img);
        });
    } else {
        photosContainer.innerHTML = '<p>No photos available</p>';
    }

    // Show the modal
    modal.style.display = 'flex';
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';

    // Debugging: Confirm modal visibility
    const computedStyle = window.getComputedStyle(modal);
    console.log('Modal display style:', computedStyle.display);
    console.log('Modal visibility:', computedStyle.visibility);

    if (computedStyle.display !== 'flex') {
        console.error('Modal is not visible. Check CSS or JavaScript logic.');
    } else {
        console.log('Modal is now visible.');
    }
}

// Function to close the report modal
function closeReportModal() {
    const modal = document.getElementById('reportModal');
    if (modal) {
        modal.style.display = 'none';
        modal.classList.remove('show');
        document.body.style.overflow = '';
        console.log('Report modal closed.');
    }
}

// Helper function to format dates
function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
}

// Function to update report status
function updateReportStatus(status) {
    const reportId = document.getElementById('reportIdField').value;
    if (!reportId) {
        alert('Report ID not found');
        return;
    }
    
    // Send AJAX request to update status
    fetch('../PHP CODES/update_report_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `report_id=${reportId}&status=${status}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`Report ${status} successfully`);
            closeReportModal();
            // Reload page to reflect changes
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Failed to update report status'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the report status');
    });
}

// Function to download report as PDF
function downloadReportPDF() {
    alert('PDF download functionality will be implemented soon');
    // This would be implemented with jsPDF or similar library
}

// Make sure these functions are globally available
window.openReportModal = openReportModal;
window.closeReportModal = closeReportModal;
window.updateReportStatus = updateReportStatus;
window.downloadReportPDF = downloadReportPDF;

// Function to load and display review data
function loadReviewData(appointmentId) {
    console.log("Loading review data for appointment ID:", appointmentId);
    
    // Get the modal element
    const modal = document.getElementById('reviewModal');
    if (!modal) {
        console.error('Review modal not found in the DOM');
        return;
    }
    
    // Show the modal with loading state
    modal.style.display = 'flex';
    setTimeout(() => {
        modal.classList.add('show');
    }, 10);
    
    // Get all the elements we need to update
    const reviewText = document.getElementById('review-text');
    const serviceFeedback = document.getElementById('service-feedback');
    const reportedIssues = document.getElementById('reported-issues');
    const overallRatingValue = document.getElementById('overall-rating-value');
    const serviceRatingValue = document.getElementById('service-rating-value');
    const technicianRatingValue = document.getElementById('technician-rating-value');
    const overallStars = document.getElementById('overall-stars');
    const serviceStars = document.getElementById('service-stars');
    const technicianStars = document.getElementById('technician-stars');
    const reviewDate = document.getElementById('review-date');
    const issuesContainer = document.getElementById('issues-container');
    
    // Set loading states
    if (reviewText) reviewText.textContent = 'Loading...';
    if (serviceFeedback) serviceFeedback.textContent = 'Loading...';
    if (reportedIssues) reportedIssues.textContent = 'Loading...';
    if (overallStars) overallStars.innerHTML = '';
    if (serviceStars) serviceStars.innerHTML = '';
    if (technicianStars) technicianStars.innerHTML = '';
    
    // Fetch the review data
    fetch(`../PHP CODES/get_review.php?appointment_id=${appointmentId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log("Review data received:", data);
            
            if (data.success && data.review) {
                const review = data.review;
                
                // Update overall rating
                if (overallRatingValue) overallRatingValue.textContent = review.rating || 'N/A';
                if (overallStars) overallStars.innerHTML = generateStars(review.rating);
                
                // Update service rating
                if (serviceRatingValue) serviceRatingValue.textContent = review.service_rating || 'N/A';
                if (serviceStars) serviceStars.innerHTML = generateStars(review.service_rating);
                
                // Update technician rating
                if (technicianRatingValue) technicianRatingValue.textContent = review.technician_rating || 'N/A';
                if (technicianStars) technicianStars.innerHTML = generateStars(review.technician_rating);
                
                // Update review text
                if (reviewText) reviewText.textContent = review.review_text || 'No review provided';
                
                // Update service feedback
                if (serviceFeedback) serviceFeedback.textContent = review.service_feedback || 'No feedback provided';
                
                // Update reported issues
                if (issuesContainer) {
                    if (review.reported_issues && review.reported_issues.trim() !== '') {
                        if (reportedIssues) reportedIssues.textContent = review.reported_issues;
                        issuesContainer.style.display = 'block';
                    } else {
                        issuesContainer.style.display = 'none';
                    }
                }
                
                // Update review date
                if (reviewDate) reviewDate.textContent = review.formatted_date || 'N/A';
                
            } else {
                // No review found or error occurred
                console.log("No review found or error occurred");
                
                if (overallRatingValue) overallRatingValue.textContent = 'N/A';
                if (serviceRatingValue) serviceRatingValue.textContent = 'N/A';
                if (technicianRatingValue) technicianRatingValue.textContent = 'N/A';
                
                if (overallStars) overallStars.innerHTML = '';
                if (serviceStars) serviceStars.innerHTML = '';
                if (technicianStars) technicianStars.innerHTML = '';
                
                if (reviewText) reviewText.textContent = 'No review has been submitted for this appointment.';
                if (serviceFeedback) serviceFeedback.textContent = 'No feedback provided.';
                
                if (issuesContainer) issuesContainer.style.display = 'none';
                if (reviewDate) reviewDate.textContent = 'N/A';
            }
        })
        .catch(error => {
            console.error('Error fetching review data:', error);
            
            // Handle error state
            if (reviewText) reviewText.textContent = 'Error loading review data. Please try again.';
            if (serviceFeedback) serviceFeedback.textContent = 'Error loading data.';
            if (reportedIssues) reportedIssues.textContent = 'Error loading data.';
            
            if (overallRatingValue) overallRatingValue.textContent = 'Error';
            if (serviceRatingValue) serviceRatingValue.textContent = 'Error';
            if (technicianRatingValue) technicianRatingValue.textContent = 'Error';
        });
}

// Helper function to generate star icons based on rating
function generateStars(rating) {
    if (!rating || isNaN(rating)) {
        // Return empty stars if no rating or invalid rating
        let emptyStars = '';
        for (let i = 0; i < 5; i++) {
            emptyStars += '<i class="bx bxs-star"></i>';
        }
        return emptyStars;
    }
    
    // Parse rating as float and limit to 0-5 range
    rating = Math.min(Math.max(parseFloat(rating), 0), 5);
    
    // Calculate full and empty stars
    const fullStars = Math.floor(rating);
    const emptyStars = 5 - fullStars;
    
    // Build the HTML for stars
    let starsHTML = '';
    
    // Add filled stars
    for (let i = 0; i < fullStars; i++) {
        starsHTML += '<i class="bx bxs-star filled"></i>';
    }
    
    // Add empty stars
    for (let i = 0; i < emptyStars; i++) {
        starsHTML += '<i class="bx bxs-star"></i>';
    }
    
    return starsHTML;
}

// Function to close review modal
function closeReviewModal() {
    const modal = document.getElementById('reviewModal');
    if (modal) {
        modal.classList.remove('show');
        setTimeout(() => {
            modal.style.display = 'none';
        }, 300);
    }
}

// Make functions globally available
window.loadReviewData = loadReviewData;
window.closeReviewModal = closeReviewModal;
window.generateStars = generateStars;





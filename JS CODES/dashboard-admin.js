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

    // Enhanced logout confirmation
    const logoutLink = document.querySelector('a[href*="logout"]'); // Ensure the correct logout link is selected
    const logoutModal = document.getElementById('logoutModal');
    const confirmLogout = document.getElementById('confirmLogout');
    const cancelLogout = document.getElementById('cancelLogout');

    if (logoutLink) {
        logoutLink.addEventListener('click', function (e) {
            e.preventDefault(); // Prevent default logout behavior
            logoutModal.style.display = 'block';
        });

        confirmLogout.addEventListener('click', function () {
            window.location.href = logoutLink.href; // Redirect to the logout link
        });

        cancelLogout.addEventListener('click', function () {
            logoutModal.style.display = 'none'; // Hide the modal
        });

        // Close modal when clicking outside the modal content
        window.addEventListener('click', function (e) {
            if (e.target === logoutModal) {
                logoutModal.style.display = 'none';
            }
        });
    }

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

    // Enhanced logout confirmation
    const logoutLink = document.querySelector('a[href*="logout"]'); // Ensure the correct logout link is selected
    const logoutModal = document.getElementById('logoutModal');
    const confirmLogout = document.getElementById('confirmLogout');
    const cancelLogout = document.getElementById('cancelLogout');

    if (logoutLink) {
        logoutLink.addEventListener('click', function (e) {
            e.preventDefault(); // Prevent default logout behavior
            logoutModal.style.display = 'block';
        });

        confirmLogout.addEventListener('click', function () {
            window.location.href = logoutLink.href; // Redirect to the logout link
        });

        cancelLogout.addEventListener('click', function () {
            logoutModal.style.display = 'none'; // Hide the modal
        });

        // Close modal when clicking outside the modal content
        window.addEventListener('click', function (e) {
            if (e.target === logoutModal) {
                logoutModal.style.display = 'none';
            }
        });
    }
});

// Logout Modal Functionality
document.addEventListener('DOMContentLoaded', function() {
    const logoutLink = document.querySelector('.logout');
    const logoutModal = document.getElementById('logoutModal');
    const confirmLogout = document.getElementById('confirmLogout');
    const cancelLogout = document.getElementById('cancelLogout');

    // Show modal when logout link is clicked
    if (logoutLink) {
        logoutLink.addEventListener('click', function(e) {
            e.preventDefault();
            logoutModal.style.display = 'block';
        });
    }

    // Handle confirm logout
    if (confirmLogout) {
        confirmLogout.addEventListener('click', function() {
            window.location.href = 'Login.php';
        });
    }

    // Handle cancel logout
    if (cancelLogout) {
        cancelLogout.addEventListener('click', function() {
            logoutModal.style.display = 'none';
        });
    }

    // Close modal when clicking outside
    window.addEventListener('click', function(e) {
        if (e.target === logoutModal) {
            logoutModal.style.display = 'none';
        }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && logoutModal.style.display === 'block') {
            logoutModal.style.display = 'none';
        }
    });
});

// Updated Report Modal Functions
function openReportModal(reportId) {
    const modal = document.getElementById('reportModal');
    if (!modal) {
        console.error('Modal element not found');
        return;
    }
    modal.classList.add('show'); // Add show class instead of display:block
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeReportModal() {
    const modal = document.getElementById('reportModal');
    if (!modal) return;
    modal.classList.remove('show');
    modal.style.display = 'none';
    document.body.style.overflow = '';
}

function initializeReportCards() {
    const reportCards = document.querySelectorAll('.report-card');
    reportCards.forEach(card => {
        card.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const reportId = this.dataset.reportId;
            openReportModal(reportId);
        });
    });
}

function approveReport() {
    // Add approve logic here
    alert('Report approved successfully!');
    closeReportModal();
}

function rejectReport() {
    // Add reject logic here
    const reason = prompt('Please enter the reason for rejection:');
    if (reason) {
        alert('Report rejected. Reason: ' + reason);
        closeReportModal();
    }
}

function printReport() {
    window.print();
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





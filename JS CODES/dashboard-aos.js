// Sidebar toggle
const menuBar = document.querySelector('#main-navbar .bx-menu');
const sidebar = document.getElementById('sidebar');

menuBar.addEventListener('click', function () {
    sidebar.classList.toggle('hide');
});

// Section display - enhanced with debugging
function showSection(sectionId) {
    console.log(`Attempting to show section: ${sectionId}`);
    
    // Validate section exists
    const targetSection = document.getElementById(sectionId);
    if (!targetSection) {
        console.error(`Section with ID "${sectionId}" not found!`);
        return;
    }
    
    // Hide all sections
    const allSections = document.querySelectorAll('.section');
    console.log(`Found ${allSections.length} sections to hide`);
    
    allSections.forEach(section => {
        section.classList.remove('active');
        console.log(`Removed active class from: ${section.id}`);
    });
    
    // Show selected section
    targetSection.classList.add('active');
    console.log(`Added active class to: ${sectionId}`);
    
    // Update active menu item
    const allMenuItems = document.querySelectorAll('.side-menu li');
    console.log(`Found ${allMenuItems.length} menu items to update`);
    
    allMenuItems.forEach(item => {
        item.classList.remove('active');
    });
    
    // Find and activate the menu item - more robust selection
    const menuLinks = document.querySelectorAll('.side-menu a');
    console.log(`Found ${menuLinks.length} menu links to check`);
    
    let menuItemFound = false;
    menuLinks.forEach(link => {
        const href = link.getAttribute('href');
        console.log(`Checking menu link with href: ${href}`);
        
        if (href === `#${sectionId}` || href.endsWith(`#${sectionId}`)) {
            const menuItem = link.closest('li');
            if (menuItem) {
                menuItem.classList.add('active');
                console.log(`Set active menu item for: ${sectionId}`);
                menuItemFound = true;
            }
        }
    });
    
    if (!menuItemFound) {
        console.warn(`No menu item found for section: ${sectionId}`);
    }
}

// NEW GLOBAL FUNCTIONS FOR PROFILE MODAL

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
    
    fetch('update_profile.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Profile updated successfully!');
            updateProfileDisplay(formData);
            closeProfileModal();
            // Clear POST data by replacing state if needed
            history.replaceState(null, null, location.pathname);
        } else {
            alert('Error: ' + (data.message || 'Update failed'));
        }
    })
    .catch(error => {
        console.error('Error updating profile:', error);
        alert('An error occurred while updating profile');
    })
    .finally(() => {
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

// NEW: Initialize profile editor functionality
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
        }}
    );
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

// Remove the duplicate DOMContentLoaded event listeners
// and replace with the new one
document.addEventListener('DOMContentLoaded', function() {
    // Check if there's a stored active section (from job-details page)
    const activeSection = localStorage.getItem('activeSection');
    if (activeSection) {
        showSection(activeSection);
        localStorage.removeItem('activeSection'); // Clear stored section
    }

    // Fixed direct logout handler - simple and reliable
    document.querySelector('.logout').addEventListener('click', function(e) {
        // Don't prevent default - let the natural link behavior work
        // This matches how dashboard-admin.php handles logout
        
        // If you want a confirmation, use this simple approach:
        if (!confirm("Are you sure you want to logout?")) {
            e.preventDefault(); // Only prevent if user cancels
        }
        // If confirmed, the link will work naturally
    });

    // Add modal close handlers
    const closeButtons = document.querySelectorAll('.close-modal');
    closeButtons.forEach(button => {
        button.addEventListener('click', closeReportModal);
    });

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('reportModal');
        if (event.target === modal) {
            closeReportModal();
        }
    };

    // Initialize report cards
    initializeReportCards();

    // Close modal with Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeReportModal();
        }
    });

    // Initialize Work Orders Functionality
    initWorkOrdersFilters();

    // Initialize time slot selection for schedule follow-up
    if (document.querySelector('.time-option')) {
        initTimeSlotSelection();
    }

    // Event listener for time option selection
    const timeOptions = document.querySelectorAll('.time-option');
    timeOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Remove selected class from all options
            timeOptions.forEach(opt => opt.classList.remove('selected'));
            // Add selected class to clicked option
            this.classList.add('selected');
        });
    });

    // Initialize follow-ups table functionality
    initFollowupsTable();

    // Initialize profile editor functionality
    initProfileEditor();
});

// Report Modal Functions
function openReportModal(reportId) {
    const modal = document.getElementById('reportModal');
    if (!modal) {
        console.error('Modal element not found');
        return;
    }
    modal.classList.add('show');
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';

    // You can add logic here to fetch and populate report data based on reportId
}

function closeReportModal() {
    const modal = document.getElementById('reportModal');
    if (!modal) return;
    modal.classList.remove('show');
    modal.style.display = 'none';
    document.body.style.overflow = '';
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

// Work Orders Filtering and Search Functionality
function initWorkOrdersFilters() {
    // Get references to elements
    const searchInput = document.getElementById('searchAppointments');
    const filterButtons = document.querySelectorAll('.filter-buttons .filter-btn');
    const dateFilter = document.getElementById('filterDate');
    const tableRows = document.querySelectorAll('.work-orders-table tbody tr');
    
    if (!searchInput || !dateFilter || tableRows.length === 0) {
        // Elements don't exist, probably on a different page
        return;
    }
    
    // Set default date to today
    const today = new Date().toISOString().split('T')[0];
    dateFilter.value = today;
    
    // Function to filter table rows by search term
    function filterBySearchTerm(term) {
        term = term.toLowerCase().trim();
        
        tableRows.forEach(row => {
            if (row.classList.contains('no-records')) return;
            
            const text = row.textContent.toLowerCase();
            if (text.includes(term)) {
                row.dataset.searchMatch = "true";
            } else {
                row.dataset.searchMatch = "false";
            }
            
            checkRowVisibility(row);
        });
        
        checkNoResults();
    }
    
    // Function to filter table rows by status
    function filterByStatus(status) {
        tableRows.forEach(row => {
            if (row.classList.contains('no-records')) return;
            
            if (status === 'all' || row.getAttribute('data-status') === status) {
                row.dataset.statusMatch = "true";
            } else {
                row.dataset.statusMatch = "false";
            }
            
            checkRowVisibility(row);
        });
        
        checkNoResults();
    }
    
    // Function to filter table rows by date
    function filterByDate(date) {
        if (!date) {
            // If no date is selected, show all rows
            tableRows.forEach(row => {
                if (row.classList.contains('no-records')) return;
                row.dataset.dateMatch = "true";
                checkRowVisibility(row);
            });
            return;
        }
        
        tableRows.forEach(row => {
            if (row.classList.contains('no-records')) return;
            
            const rowDate = row.getAttribute('data-date');
            if (rowDate === date) {
                row.dataset.dateMatch = "true";
            } else {
                row.dataset.dateMatch = "false";
            }
            
            checkRowVisibility(row);
        });
        
        checkNoResults();
    }
    
    // Function to check if a row should be visible based on all filters
    function checkRowVisibility(row) {
        if (row.classList.contains('no-records')) return;
        
        const searchMatch = row.dataset.searchMatch !== "false";
        const statusMatch = row.dataset.statusMatch !== "false";
        const dateMatch = row.dataset.dateMatch !== "false";
        
        if (searchMatch && statusMatch && dateMatch) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    }
    
    // Function to check if there are no visible results and show a message
    function checkNoResults() {
        let hasVisibleRows = false;
        
        tableRows.forEach(row => {
            if (!row.classList.contains('no-records') && row.style.display !== 'none') {
                hasVisibleRows = true;
            }
        });
        
        // Get or create the "no results" row
        let noResultsRow = document.querySelector('.work-orders-table tbody tr.no-results');
        
        if (!hasVisibleRows) {
            if (!noResultsRow) {
                noResultsRow = document.createElement('tr');
                noResultsRow.className = 'no-results';
                noResultsRow.innerHTML = '<td colspan="7" class="no-records">No matching records found</td>';
                document.querySelector('.work-orders-table tbody').appendChild(noResultsRow);
            }
            noResultsRow.style.display = '';
        } else if (noResultsRow) {
            noResultsRow.style.display = 'none';
        }
    }
    
    // Apply all filters together
    function applyAllFilters() {
        const searchTerm = searchInput.value;
        const activeFilterBtn = document.querySelector('.filter-btn.active');
        const status = activeFilterBtn ? activeFilterBtn.getAttribute('data-filter') : 'all';
        const date = dateFilter.value;
        
        // Initialize datasets for all rows
        tableRows.forEach(row => {
            if (row.classList.contains('no-records')) return;
            row.dataset.searchMatch = "true";
            row.dataset.statusMatch = "true";
            row.dataset.dateMatch = "true";
        });
        
        if (searchTerm) filterBySearchTerm(searchTerm);
        filterByStatus(status);
        if (date) filterByDate(date);
    }
    
    // Event listeners
    searchInput.addEventListener('input', function() {
        applyAllFilters();
    });
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons
            filterButtons.forEach(btn => btn.classList.remove('active'));
            
            // Add active class to clicked button
            this.classList.add('active');
            
            // Apply all filters
            applyAllFilters();
        });
    });
    
    dateFilter.addEventListener('change', function() {
        applyAllFilters();
    });
    
    // Initialize table with all rows visible
    tableRows.forEach(row => {
        if (!row.classList.contains('no-records')) {
            row.dataset.searchMatch = "true";
            row.dataset.statusMatch = "true";
            row.dataset.dateMatch = "true";
            row.style.display = '';
        }
    });

    // Store active section when clicking on view details
    document.querySelectorAll('.view-btn').forEach(button => {
        button.addEventListener('click', function() {
            localStorage.setItem('activeSection', 'work-orders');
        });
    });
}

// Feedback Modal Functions
function showFeedbackModal(appointmentId) {
    const modal = document.getElementById('feedbackModal');
    if (!modal) {
        console.error('Feedback modal element not found');
        return;
    }
    
    // Set the appointment ID in the form
    const idInput = document.getElementById('feedback_appointment_id');
    if (idInput) {
        idInput.value = appointmentId;
    }
    
    // Show the modal
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeFeedbackModal() {
    const modal = document.getElementById('feedbackModal');
    if (!modal) return;
    
    modal.classList.remove('show');
    document.body.style.overflow = '';
    
    // Reset the form
    const form = document.getElementById('feedbackForm');
    if (form) form.reset();
}

// Schedule Follow-up Functions
function generateVisitDates() {
    const customerSelect = document.getElementById('customer-select');
    const planType = document.getElementById('plan-type');
    const frequency = document.getElementById('frequency-select');
    const startDate = document.getElementById('start-date');
    const contractDuration = document.getElementById('contract-duration');
    
    // Validate required fields
    if (!customerSelect.value || !planType.value || !frequency.value || !startDate.value || !contractDuration.value) {
        showAlert('Please fill in all required fields before generating a schedule.');
        return;
    }
    
    // Clear existing schedule
    const scheduleContainer = document.getElementById('visit-schedule');
    scheduleContainer.innerHTML = '';
    
    // Parse input values
    const start = new Date(startDate.value);
    const duration = parseInt(contractDuration.value);
    const freq = parseInt(frequency.value);
    
    // Calculate end date (months from start)
    const endDate = new Date(start);
    endDate.setMonth(endDate.getMonth() + duration);
    
    // Generate dates based on plan type and frequency
    const dates = [];
    let currentDate = new Date(start);
    let visitCount = 1;
    
    while (currentDate < endDate) {
        // Add current date to the schedule
        addVisitToSchedule(scheduleContainer, new Date(currentDate), visitCount);
        visitCount++;
        
        // Increment date based on plan type and frequency
        if (planType.value === 'weekly') {
            // Add 7/freq days (e.g., for freq=2, add 3.5 days)
            currentDate.setDate(currentDate.getDate() + Math.ceil(7/freq));
        } else if (planType.value === 'monthly') {
            if (freq >= 4) {
                // Weekly visits
                currentDate.setDate(currentDate.getDate() + 7);
            } else {
                // Calculate days per month based on frequency
                const daysInMonth = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0).getDate();
                const increment = Math.floor(daysInMonth / freq);
                currentDate.setDate(currentDate.getDate() + increment);
            }
        } else if (planType.value === 'quarterly') {
            const monthsPerVisit = 3 / freq;
            currentDate.setMonth(currentDate.getMonth() + monthsPerVisit);
        } else if (planType.value === 'yearly') {
            const monthsPerVisit = 12 / freq;
            currentDate.setMonth(currentDate.getMonth() + monthsPerVisit);
        }
    }
    
    // Show success message
    showAlert(`Successfully generated ${visitCount-1} follow-up visits for the selected plan.`);
}

// Helper function to add a visit date to the schedule display
function addVisitToSchedule(container, date, visitNumber) {
    const timeOption = document.querySelector('.time-option.selected');
    const customTimeStart = document.getElementById('custom-time-start');
    const customTimeEnd = document.getElementById('custom-time-end');
    
    let timeDisplay = '8:00 AM - 10:00 AM'; // Default time
    
    if (timeOption) {
        timeDisplay = timeOption.getAttribute('data-time');
    } else if (customTimeStart.value && customTimeEnd.value) {
        // Format custom time
        const start = formatTimeDisplay(customTimeStart.value);
        const end = formatTimeDisplay(customTimeEnd.value);
        timeDisplay = `${start} - ${end}`;
    }
    
    // Format date
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    const formattedDate = date.toLocaleDateString('en-US', options);
    
    // Create visit item
    const visitItem = document.createElement('div');
    visitItem.classList.add('visit-date-item');
    visitItem.innerHTML = `
        <div class="visit-info">
            <span class="date">${formattedDate}</span>
            <span class="time">${timeDisplay}</span>
        </div>
        <span class="visit-number">Visit #${visitNumber}</span>
    `;
    
    container.appendChild(visitItem);
}

// Helper function to format time display (from 24h to 12h format)
function formatTimeDisplay(time24h) {
    const [hours, minutes] = time24h.split(':');
    const hour = parseInt(hours);
    const suffix = hour >= 12 ? 'PM' : 'AM';
    const hour12 = hour === 0 ? 12 : hour > 12 ? hour - 12 : hour;
    return `${hour12}:${minutes} ${suffix}`;
}

// Helper function to show alerts
function showAlert(message) {
    // Use existing modal if available
    if (typeof showModal === 'function') {
        showModal('Schedule Generator', message);
    } else {
        alert(message);
    }
}

// Function to clear the schedule
function clearSchedule() {
    document.getElementById('visit-schedule').innerHTML = '';
    document.getElementById('customer-select').value = '';
    document.getElementById('plan-type').value = '';
    document.getElementById('frequency-select').innerHTML = '<option value="">Select Frequency</option>';
    document.getElementById('start-date').value = '';
    document.getElementById('contract-duration').value = '3';
    
    // Clear time selection
    const selectedTimeOption = document.querySelector('.time-option.selected');
    if (selectedTimeOption) {
        selectedTimeOption.classList.remove('selected');
    }
    document.getElementById('custom-time-start').value = '';
    document.getElementById('custom-time-end').value = '';
    
    showAlert('Schedule cleared successfully.');
}

// Function to save/submit the generated schedule to the database
function saveSchedule() {
    const scheduleItems = document.querySelectorAll('.visit-date-item');
    
    if (scheduleItems.length === 0) {
        showAlert('Please generate a schedule before submitting.');
        return;
    }
    
    const customerSelect = document.getElementById('customer-select');
    if (!customerSelect.value) {
        showAlert('Please select a customer before submitting the schedule.');
        return;
    }
    
    // In a real implementation, this would gather all schedule data
    // and send it to the server using AJAX/fetch
    
    // For demonstration purposes:
    showAlert('Schedule submitted successfully! The follow-up appointments have been created and the customer will be notified.');
    
    // Optional: Clear the form after successful submission
    // clearSchedule();
}

// Function to clear the schedule
function clearSchedule() {
    document.getElementById('visit-schedule').innerHTML = '';
    document.getElementById('customer-select').value = '';
    document.getElementById('plan-type').value = '';
    document.getElementById('frequency-select').innerHTML = '<option value="">Select Frequency</option>';
    document.getElementById('start-date').value = '';
    document.getElementById('contract-duration').value = '3';
    
    // Clear time selection
    const selectedTimeOption = document.querySelector('.time-option.selected');
    if (selectedTimeOption) {
        selectedTimeOption.classList.remove('selected');
    }
    document.getElementById('custom-time-start').value = '';
    document.getElementById('custom-time-end').value = '';
    
    showAlert('Schedule cleared successfully.');
}

// Function to update frequency options based on plan type
function updateFrequencyOptions() {
    const planType = document.getElementById('plan-type').value;
    const frequencySelect = document.getElementById('frequency-select');
    
    // Clear existing options
    frequencySelect.innerHTML = '<option value="">Select Frequency</option>';
    
    // Add appropriate options based on plan type
    if (planType === 'weekly') {
        addOption(frequencySelect, '1', '1 visit per week');
        addOption(frequencySelect, '2', '2 visits per week');
        addOption(frequencySelect, '3', '3 visits per week');
    } else if (planType === 'monthly') {
        addOption(frequencySelect, '1', '1 visit per month');
        addOption(frequencySelect, '2', '2 visits per month');
        addOption(frequencySelect, '4', 'Weekly (4 visits per month)');
    } else if (planType === 'quarterly') {
        addOption(frequencySelect, '1', '1 visit per quarter');
        addOption(frequencySelect, '2', '2 visits per quarter');
        addOption(frequencySelect, '3', '3 visits per quarter (monthly)');
    } else if (planType === 'yearly') {
        addOption(frequencySelect, '1', '1 visit per year');
        addOption(frequencySelect, '2', '2 visits per year (semi-annual)');
        addOption(frequencySelect, '4', '4 visits per year (quarterly)');
        addOption(frequencySelect, '12', '12 visits per year (monthly)');
    }
}

// Helper function to add options to select element
function addOption(selectElement, value, text) {
    const option = document.createElement('option');
    option.value = value;
    option.textContent = text;
    selectElement.appendChild(option);
}

// Initialize time slot selection
function initTimeSlotSelection() {
    const timeOptions = document.querySelectorAll('.time-option');
    const customTimeStart = document.getElementById('custom-time-start');
    const customTimeEnd = document.getElementById('custom-time-end');
    
    // Handle predefined time slot selection
    timeOptions.forEach(option => {
        option.addEventListener('click', () => {
            timeOptions.forEach(btn => btn.classList.remove('selected'));
            option.classList.add('selected');
            
            // Clear custom time inputs
            customTimeStart.value = '';
            customTimeEnd.value = '';
        });
    });

    // Handle custom time input
    function handleCustomTimeInput() {
        const start = customTimeStart.value;
        const end = customTimeEnd.value;
        
        if (start && end) {
            // Validate time range (7 AM - 5 PM)
            const startHour = parseInt(start.split(':')[0]);
            const endHour = parseInt(end.split(':')[0]);
            
            if (startHour < 7 || endHour > 17 || startHour >= endHour) {
                alert('Please select a valid time between 7:00 AM and 5:00 PM');
                return;
            }

            // Clear predefined selections
            timeOptions.forEach(btn => btn.classList.remove('selected'));
        }
    }

    customTimeStart.addEventListener('input', handleCustomTimeInput);
    customTimeEnd.addEventListener('input', handleCustomTimeInput);
}

// Function to load customer details when a customer is selected - simplified
function loadCustomerDetails(appointmentId) {
    if (!appointmentId) {
        // Clear fields if no appointment is selected
        document.getElementById('service-type').value = '';
        document.getElementById('customer-location').value = '';
        document.getElementById('technician-select').value = '';
        return;
    }
    
    console.log("Loading details for appointment ID:", appointmentId);
    
    // Get data from option attributes
    const selectedOption = document.querySelector(`#customer-select option[value="${appointmentId}"]`);
    if (selectedOption) {
        const serviceId = selectedOption.getAttribute('data-service');
        const locationText = selectedOption.getAttribute('data-location');
        const technicianId = selectedOption.getAttribute('data-technician');
        
        console.log("Option data:", {
            serviceId,
            locationText,
            technicianId
        });
        
        // Set the service type if available
        if (serviceId) {
            document.getElementById('service-type').value = serviceId;
            console.log("Set service type:", serviceId);
        }
        
        // Set the customer location if available
        if (locationText) {
            document.getElementById('customer-location').value = locationText;
            console.log("Set location:", locationText);
        }
        
        // Set the technician if available
        if (technicianId && technicianId !== "null" && technicianId !== "undefined") {
            const techSelect = document.getElementById('technician-select');
            techSelect.value = technicianId;
            console.log("Set technician:", technicianId);
            
            // Log whether the value was actually set
            if (techSelect.value === technicianId) {
                console.log("Successfully set technician select value");
            } else {
                console.warn("Failed to set technician. Available options:", Array.from(techSelect.options).map(o => o.value));
            }
        } else {
            console.warn("No technician ID available in the data attributes");
        }
    }
}

// Function to load customer details for follow-up scheduling
function loadCustomerDetails(appointmentId) {
    if (!appointmentId) return;
    
    // Get the selected option
    const selectedOption = document.querySelector(`#customer-select option[value="${appointmentId}"]`);
    
    if (selectedOption) {
        // Set service dropdown
        const serviceId = selectedOption.getAttribute('data-service');
        if (serviceId) {
            document.getElementById('service-type').value = serviceId;
        }
        
        // Set location
        const location = selectedOption.getAttribute('data-location');
        if (location) {
            document.getElementById('customer-location').value = location;
        }
        
        // Get technician information - try all technicians first, then fall back to main technician
        let technicianId = null;
        const allTechnicians = selectedOption.getAttribute('data-all-technicians');
        
        if (allTechnicians && allTechnicians.length > 0) {
            // Use the first technician from the list if available
            technicianId = allTechnicians.split(',')[0];
        } else {
            // Fall back to the main technician
            technicianId = selectedOption.getAttribute('data-technician');
        }
        
        if (technicianId) {
            document.getElementById('technician-select').value = technicianId;
        }
        
        // For debugging
        console.log("Appointment ID:", appointmentId);
        console.log("Service ID:", serviceId);
        console.log("Location:", location);
        console.log("All Technicians:", allTechnicians);
        console.log("Selected Technician ID:", technicianId);
    }
}

// Function to schedule a follow-up appointment
function scheduleFollowUp() {
    // Get form values
    const appointmentId = document.getElementById('customer-select').value;
    const serviceId = document.getElementById('service-type').value;
    const technicianId = document.getElementById('technician-select').value;
    const followupDate = document.getElementById('followup-date').value;
    const followupTime = document.getElementById('followup-time').value;
    const notes = document.getElementById('followup-notes').value;
    
    // Validate required fields
    if (!appointmentId || !serviceId || !technicianId || !followupDate || !followupTime) {
        alert('Please fill all required fields');
        return;
    }
    
    // Create form data
    const formData = new FormData();
    formData.append('appointment_id', appointmentId);
    formData.append('service_id', serviceId);
    formData.append('technician_id', technicianId);
    formData.append('followup_date', followupDate);
    formData.append('followup_time', followupTime);
    formData.append('notes', notes);
    
    // Send AJAX request
    fetch('schedule_followup.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            alert('Follow-up scheduled successfully!');
            
            // Reload the page or just the follow-ups table
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            // Show error message
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while scheduling the follow-up');
    });
}

// Function to schedule a follow-up
function scheduleFollowUp() {
    const customerId = document.getElementById('customer-select').value;
    const serviceId = document.getElementById('service-type').value;
    const technicianId = document.getElementById('technician-select').value;
    const followupDate = document.getElementById('followup-date').value;
    const followupTime = document.getElementById('followup-time').value;
    const notes = document.getElementById('followup-notes').value;
    
    // Validate form
    if (!customerId || !serviceId || !technicianId || !followupDate || !followupTime) {
        showNotification('Please fill in all required fields', 'error');
        return;
    }
    
    // Prepare data for submission
    const formData = new FormData();
    formData.append('appointment_id', customerId);
    formData.append('service_id', serviceId);
    formData.append('technician_id', technicianId);
    formData.append('followup_date', followupDate);
    formData.append('followup_time', followupTime);
    formData.append('notes', notes);
    
    // Submit the form
    fetch('schedule_followup.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Follow-up scheduled successfully!', 'success');
            // Refresh the followups list
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showNotification(data.message || 'Error scheduling follow-up', 'error');
        }
    })
    .catch(error => {
        console.error('Error scheduling follow-up:', error);
        showNotification('An error occurred while scheduling the follow-up', 'error');
    });
}

// Utility function to show notifications
function showNotification(message, type = 'info') {
    // Check if customModal exists
    const modal = document.getElementById('customModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalMessage = document.getElementById('modalMessage');
    const modalOkBtn = document.getElementById('modalOkBtn');
    
    if (modal && modalTitle && modalMessage) {
        // Set modal content based on notification type
        modalTitle.textContent = type.charAt(0).toUpperCase() + type.slice(1);
        modalMessage.textContent = message;
        
        // Apply styling based on notification type
        const modalContent = document.querySelector('.modal-content');
        modalContent.className = 'modal-content'; // Reset classes
        modalContent.classList.add(`modal-${type}`);
        
        // Show the modal
        modal.style.display = 'block';
        
        // Set up close button
        modalOkBtn.onclick = function() {
            modal.style.display = 'none';
        };
        
        // Close when clicking X
        document.querySelector('.close').onclick = function() {
            modal.style.display = 'none';
        };
    } else {
        // Fallback to alert if modal elements don't exist
        alert(message);
    }
}

// Function to initialize followups table functionality
function initFollowupsTable() {
    const searchInput = document.getElementById('followup-search');
    const filterButtons = document.querySelectorAll('.followups-controls .filter-btn');
    const followupRows = document.querySelectorAll('.followup-row');
    const followupsList = document.getElementById('followups-list');
    
    if (!searchInput || !followupsList) return; // Exit if elements don't exist
    
    // Variables for pagination
    const rowsPerPage = 10;
    let currentPage = 1;
    let filteredRows = [...followupRows]; // Start with all rows
    
    // Function to filter rows by search term
    function filterBySearchTerm(term) {
        term = term.toLowerCase().trim();
        
        filteredRows = [...followupRows].filter(row => {
            if (!term) return true; // Show all rows if search is empty
            return row.textContent.toLowerCase().includes(term);
        });
        
        // Apply current filter
        const activeFilter = document.querySelector('.followups-controls .filter-btn.active');
        if (activeFilter) {
            const filterValue = activeFilter.getAttribute('data-filter');
            if (filterValue !== 'all') {
                filteredRows = filteredRows.filter(row => {
                    return filterValue === 'all' || row.getAttribute('data-period') === filterValue;
                });
            }
        }
        
        updateTableDisplay();
    }
    
    // Function to filter rows by period
    function filterByPeriod(period) {
        // Start with rows that match the search term
        const searchTerm = searchInput.value.toLowerCase().trim();
        filteredRows = [...followupRows].filter(row => {
            if (!searchTerm) return true;
            return row.textContent.toLowerCase().includes(searchTerm);
        });
        
        // Then filter by period
        if (period !== 'all') {
            const today = new Date();
            
            // Calculate date ranges for different periods
            const weekStart = new Date(today);
            weekStart.setDate(today.getDate() - today.getDay() + (today.getDay() === 0 ? -6 : 1)); // Start of current week (Monday)
            
            const weekEnd = new Date(weekStart);
            weekEnd.setDate(weekStart.getDate() + 6); // End of current week (Sunday)
            
            const nextWeekStart = new Date(weekStart);
            nextWeekStart.setDate(weekStart.getDate() + 7); // Start of next week
            
            const nextWeekEnd = new Date(nextWeekStart);
            nextWeekEnd.setDate(nextWeekStart.getDate() + 6); // End of next week
            
            // Calculate next month range
            const nextMonthStart = new Date(today.getFullYear(), today.getMonth() + 1, 1); // First day of next month
            const nextMonthEnd = new Date(today.getFullYear(), today.getMonth() + 2, 0); // Last day of next month
            
            // Filter based on the period
            filteredRows = filteredRows.filter(row => {
                const dateStr = row.getAttribute('data-date');
                if (!dateStr) return false;
                
                const rowDate = new Date(dateStr);
                
                switch (period) {
                    case 'thisweek':
                        return rowDate >= weekStart && rowDate <= weekEnd;
                    case 'nextweek':
                        return rowDate >= nextWeekStart && rowDate <= nextWeekEnd;
                    case 'nextmonth':
                        return rowDate >= nextMonthStart && rowDate <= nextMonthEnd;
                    default:
                        return true;
                }
            });
        }
        
        updateTableDisplay();
    }

    // Add profile editing functionality
    function initProfileEditor() {
        console.log('Initializing profile editor');
        const editProfileBtn = document.querySelector('.profile-edit-btn');
        const profileModal = document.getElementById('profileModal');
        const closeBtn = profileModal ? profileModal.querySelector('.close') : null;
        const profileForm = document.getElementById('editProfileForm');
        
        if (!editProfileBtn || !profileModal || !profileForm) {
            console.error('Profile editor initialization failed. Missing elements:', {
                editBtn: !editProfileBtn,
                modal: !profileModal,
                form: !profileForm
            });
            return; // Required elements not found, exit function
        }

        console.log('Profile editor elements found, setting up event listeners');

        // Open profile edit modal when edit button is clicked
        editProfileBtn.addEventListener('click', function() {
            console.log('Edit button clicked, opening modal');
            openProfileModal();
        });

        // Close modal when clicking the X button
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                console.log('Close button clicked');
                closeProfileModal();
            });
        }

        // Close modal when clicking outside the modal content
        window.addEventListener('click', function(event) {
            if (event.target === profileModal) {
                console.log('Clicked outside modal');
                closeProfileModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && profileModal.style.display === 'block') {
                console.log('Escape key pressed');
                closeProfileModal();
            }
        });

        // Handle form submission
        profileForm.addEventListener('submit', function(e) {
            e.preventDefault();
            console.log('Form submitted');
            updateUserProfile(this);
        });
    }

    // Function to open profile edit modal
    function openProfileModal() {
        const modal = document.getElementById('profileModal');
        if (!modal) return;
        
        console.log('Opening profile modal');
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden'; // Prevent scrolling behind modal
    }

    // Function to close profile edit modal
    function closeProfileModal() {
        const modal = document.getElementById('profileModal');
        if (!modal) return;
        
        console.log('Closing profile modal');
        modal.style.display = 'none';
        document.body.style.overflow = ''; // Re-enable scrolling
    }

    // Function to update user profile via AJAX
    function updateUserProfile(form) {
        // Show loading state
        const submitBtn = form.querySelector('[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Saving...';
        submitBtn.disabled = true;
        
        const formData = new FormData(form);
        
        fetch('update_profile.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                showNotification('Profile updated successfully!', 'success');
                
                // Update displayed profile information without refreshing
                updateProfileDisplay(formData);
                
                // Close the modal
                closeProfileModal();
            } else {
                // Show error message
                showNotification(data.message || 'Error updating profile', 'error');
            }
        })
        .catch(error => {
            console.error('Error updating profile:', error);
            showNotification('An error occurred. Please try again.', 'error');
        })
        .finally(() => {
            // Reset button state
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        });
    }

    // Function to update profile display without page refresh
    function updateProfileDisplay(formData) {
        // Update user name in profile card
        const firstname = formData.get('firstname');
        const lastname = formData.get('lastname');
        const email = formData.get('email');
        
        // Update profile card
        const profileName = document.querySelector('.user-info h3');
        const profileEmail = document.querySelector('.user-info p');
        
        if (profileName) {
            profileName.textContent = `${firstname} ${lastname}`;
        }
        
        if (profileEmail) {
            profileEmail.textContent = email;
        }
        
        // Update details in personal details section
        const firstNameDetail = document.querySelector('.details-grid [data-field="firstname"]');
        const lastNameDetail = document.querySelector('.details-grid [data-field="lastname"]');
        const emailDetail = document.querySelector('.details-grid [data-field="email"]');
        const phoneDetail = document.querySelector('.details-grid [data-field="mobile_number"]');
        
        if (firstNameDetail) firstNameDetail.textContent = firstname;
        if (lastNameDetail) lastNameDetail.textContent = lastname;
        if (emailDetail) emailDetail.textContent = email;
        if (phoneDetail) phoneDetail.textContent = formData.get('mobile_number');
    }
    
    // Function to update the display of table rows
    function updateTableDisplay() {
        // Reset page to 1 when filters change
        currentPage = 1;
        
        // Hide all rows first
        followupRows.forEach(row => {
            row.classList.add('hidden');
        });
        
        // Remove existing "no results" row if it exists
        const existingNoResults = followupsList.querySelector('.no-results');
        if (existingNoResults) {
            existingNoResults.remove();
        }
        
        // Check if there are any results
        if (filteredRows.length === 0) {
            // Create and insert "no results" row
            const noResultsRow = document.createElement('tr');
            noResultsRow.className = 'no-results visible';
            noResultsRow.innerHTML = '<td colspan="6">No matching follow-ups found</td>';
            followupsList.appendChild(noResultsRow);
        } else {
            // Show rows for current page
            const startIndex = (currentPage - 1) * rowsPerPage;
            const endIndex = Math.min(startIndex + rowsPerPage, filteredRows.length);
            
            for (let i = startIndex; i < endIndex; i++) {
                filteredRows[i].classList.remove('hidden');
            }
            
            // Update pagination
            updatePagination();
        }
    }
    
    // Function to create and update pagination controls
    function updatePagination() {
        const paginationContainer = document.getElementById('followups-pagination');
        if (!paginationContainer) return;
        
        // Clear existing pagination
        paginationContainer.innerHTML = '';
        
        // Calculate total pages
        const totalPages = Math.ceil(filteredRows.length / rowsPerPage);
        if (totalPages <= 1) return; // Don't show pagination if only one page
        
        // Create previous button
        const prevBtn = document.createElement('button');
        prevBtn.innerHTML = '<i class="bx bx-chevron-left"></i>';
        prevBtn.disabled = currentPage === 1;
        prevBtn.addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                updateTableDisplay();
            }
        });
        paginationContainer.appendChild(prevBtn);
        
        // Create page buttons
        let startPage = Math.max(1, currentPage - 2);
        let endPage = Math.min(totalPages, startPage + 4);
        
        // Adjust start page if we're showing less than 5 pages
        if (endPage - startPage < 4) {
            startPage = Math.max(1, endPage - 4);
        }
        
        // Show page buttons
        for (let i = startPage; i <= endPage; i++) {
            const pageBtn = document.createElement('button');
            pageBtn.textContent = i;
            pageBtn.classList.toggle('active', i === currentPage);
            pageBtn.addEventListener('click', () => {
                currentPage = i;
                updateTableDisplay();
            });
            paginationContainer.appendChild(pageBtn);
        }
        
        // Create next button
        const nextBtn = document.createElement('button');
        nextBtn.innerHTML = '<i class="bx bx-chevron-right"></i>';
        nextBtn.disabled = currentPage === totalPages;
        nextBtn.addEventListener('click', () => {
            if (currentPage < totalPages) {
                currentPage++;
                updateTableDisplay();
            }
        });
        paginationContainer.appendChild(nextBtn);
        
        // Add pagination info
        const paginationInfo = document.createElement('div');
        paginationInfo.className = 'pagination-info';
        paginationInfo.textContent = `Showing ${Math.min(filteredRows.length, (currentPage - 1) * rowsPerPage + 1)}-${Math.min(filteredRows.length, currentPage * rowsPerPage)} of ${filteredRows.length} follow-ups`;
        paginationContainer.appendChild(paginationInfo);
    }
    
    // Add event listeners
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            filterBySearchTerm(this.value);
        });
    }
    
    if (filterButtons.length > 0) {
        filterButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all buttons
                filterButtons.forEach(btn => btn.classList.remove('active'));
                
                // Add active class to clicked button
                this.classList.add('active');
                
                // Filter by the selected period
                const filterValue = this.getAttribute('data-filter');
                filterByPeriod(filterValue);
            });
        });
    }
    
    // Initialize table display
    updateTableDisplay();
}

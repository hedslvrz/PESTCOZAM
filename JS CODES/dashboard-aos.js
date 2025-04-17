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

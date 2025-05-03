// Sidebar toggle
const menuBar = document.querySelector('#main-navbar .bx-menu');
const sidebar = document.getElementById('sidebar');

menuBar.addEventListener('click', function () {
    sidebar.classList.toggle('hide');
});

// Section navigation
function showSection(sectionId) {
    // Hide all sections
    document.querySelectorAll('.section').forEach(section => {
        section.classList.remove('active');
    });
    
    // Show selected section
    const targetSection = document.getElementById(sectionId);
    if (targetSection) {
        targetSection.classList.add('active');
    }
    
    // Update active state in menu
    document.querySelectorAll('.side-menu li').forEach(item => {
        item.classList.remove('active');
    });
    
    const activeLink = document.querySelector(`a[href="#${sectionId}"]`);
    if (activeLink) {
        activeLink.parentElement.classList.add('active');
    }
}

// Function to handle menu item activation and section display
function activateMenuItem(clickedLink, sectionId) {
    // Remove active class from all menu items
    document.querySelectorAll('.side-menu li').forEach(item => {
        item.classList.remove('active');
    });
    
    // Add active class to clicked menu item's parent li
    clickedLink.parentElement.classList.add('active');
    
    // Hide all sections
    document.querySelectorAll('.section').forEach(section => {
        section.classList.remove('active');
    });
    
    // Show selected section
    document.getElementById(sectionId).classList.add('active');
}

// Set initial active state when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Get the hash from URL or default to work-orders
    const currentSection = window.location.hash.slice(1) || 'work-orders';
    const defaultLink = document.querySelector(`a[href="#${currentSection}"]`);
    
    if (defaultLink) {
        activateMenuItem(defaultLink, currentSection);
    }
});

// Enhanced Calendar and Scheduling Functionality
function initSchedulingCalendar() {
    const calendar = document.getElementById('calendarDays');
    const selectedDatesContainer = document.getElementById('selectedDates');
    const selectedDatesInput = document.getElementById('selectedDatesInput');
    const selectedTimesInput = document.getElementById('selectedTimesInput');
    let selectedDates = new Set();
    let selectedTimes = {};

    function updateSubscriptionInfo(clientId) {
        // This would typically fetch data from the server
        const subscriptions = {
            '1': { plan: 'Weekly', frequency: '1 visit per week', remaining: 48 },
            '2': { plan: 'Monthly', frequency: '2 visits per month', remaining: 22 },
            '3': { plan: 'Yearly', frequency: '1 visit per month', remaining: 9 }
        };

        const sub = subscriptions[clientId] || { plan: '-', frequency: '-', remaining: '-' };
        document.getElementById('planType').textContent = sub.plan;
        document.getElementById('frequency').textContent = sub.frequency;
        document.getElementById('visitsRemaining').textContent = sub.remaining;
    }

    function renderSelectedDates() {
        selectedDatesContainer.innerHTML = '';
        selectedDates.forEach(date => {
            const tag = document.createElement('div');
            tag.className = 'selected-date-tag';
            const formattedDate = new Date(date).toLocaleDateString('en-US', {
                weekday: 'short',
                month: 'short',
                day: 'numeric'
            });
            const time = selectedTimes[date] || '';
            tag.innerHTML = `
                ${formattedDate} ${time}
                <i class='bx bx-x remove-date' data-date="${date}"></i>
            `;
            selectedDatesContainer.appendChild(tag);
        });

        // Update hidden inputs
        selectedDatesInput.value = JSON.stringify(Array.from(selectedDates));
        selectedTimesInput.value = JSON.stringify(selectedTimes);
    }

    function handleDateSelection(date, element) {
        const dateString = date.toISOString().split('T')[0];
        
        if (selectedDates.has(dateString)) {
            selectedDates.delete(dateString);
            delete selectedTimes[dateString];
            element.classList.remove('selected');
        } else {
            // Check subscription limits
            const clientSelect = document.getElementById('client');
            const clientId = clientSelect.value;
            if (!clientId) {
                alert('Please select a client first');
                return;
            }

            const limits = {
                '1': { max: 1, period: 'week' },
                '2': { max: 2, period: 'month' },
                '3': { max: 1, period: 'month' }
            };

            const limit = limits[clientId];
            if (checkSubscriptionLimit(dateString, limit)) {
                selectedDates.add(dateString);
                element.classList.add('selected');
            } else {
                alert(`Maximum ${limit.max} visits per ${limit.period} allowed`);
                return;
            }
        }
        
        renderSelectedDates();
    }

    function checkSubscriptionLimit(dateString, limit) {
        // Implementation of subscription limit checking
        // This would need to check against existing appointments
        return true; // Placeholder
    }

    // Initialize client selection handler
    const clientSelect = document.getElementById('client');
    if (clientSelect) {
        clientSelect.addEventListener('change', (e) => {
            updateSubscriptionInfo(e.target.value);
            selectedDates.clear();
            selectedTimes = {};
            renderCalendar(new Date());
            renderSelectedDates();
        });
    }

    // Initialize time slot handlers
    document.querySelectorAll('.time-slot').forEach(slot => {
        slot.addEventListener('click', () => {
            const time = slot.dataset.time;
            const selectedDate = Array.from(selectedDates).pop();
            
            if (selectedDate) {
                selectedTimes[selectedDate] = time;
                renderSelectedDates();
                
                // Update UI
                document.querySelectorAll('.time-slot').forEach(s => {
                    s.classList.remove('selected');
                });
                slot.classList.add('selected');
            } else {
                alert('Please select a date first');
            }
        });
    });

    // Event delegation for remove buttons
    selectedDatesContainer.addEventListener('click', (e) => {
        if (e.target.classList.contains('remove-date')) {
            const date = e.target.dataset.date;
            selectedDates.delete(date);
            delete selectedTimes[date];
            renderSelectedDates();
            renderCalendar(new Date(date));
        }
    });
}

// Initialize scheduling calendar when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('calendarDays')) {
        initSchedulingCalendar();
    }
});

// Calendar functionality with dropdowns and custom time
function handleTimeSlotSelection() {
    document.querySelectorAll('.select-time').forEach(button => {
        button.addEventListener('click', (e) => {
            const timeSlot = e.target;
            const selectedDate = document.getElementById('appointment-date').value;
            
            if (!selectedDate) {
                alert('Please select a date first');
                return;
            }

            // Remove selection from all buttons
            document.querySelectorAll('.select-time').forEach(btn => {
                btn.classList.remove('selected');
            });

            // Add selection to clicked button
            timeSlot.classList.add('selected');
            
            // Update selected times
            selectedTimes[selectedDate] = timeSlot.dataset.time;
            renderSelectedDates();
        });
    });
}

function initCalendar() {
    const dateInput = document.getElementById('appointment-date');
    const today = new Date();
    
    // Set today as default date
    dateInput.valueAsDate = today;
    
    // Set min date to today
    dateInput.min = today.toISOString().split('T')[0];
    
    // Handle date selection
    dateInput.addEventListener('change', (e) => {
        const selectedDate = new Date(e.target.value);
        handleDateSelection(selectedDate);
    });

    // Initialize time slot handlers
    handleTimeSlotSelection();
}

// Initialize calendar when DOM is loaded
document.addEventListener('DOMContentLoaded', initCalendar);

// Visit schedule generation
function generateVisitDates() {
    const planType = document.getElementById('plan-type').value;
    const startDate = new Date(document.getElementById('start-date').value);
    const duration = parseInt(document.getElementById('plan-duration').value);
    
    if (!planType || !startDate || isNaN(duration)) {
        alert('Please fill in all schedule settings');
        return;
    }
    
    // Calculate visits based on plan type
    const visitFrequency = {
        'weekly': 7,
        'monthly': 30,
        'yearly': 90 // Every 3 months
    };

    const daysInterval = visitFrequency[planType];
    const visits = [];
    let currentDate = new Date(startDate);
    let visitCount = 1;

    // Generate visit dates
    while (visits.length < Math.floor((duration * 30) / daysInterval)) {
        visits.push({
            date: new Date(currentDate),
            number: visitCount++
        });
        currentDate.setDate(currentDate.getDate() + daysInterval);
    }

    // Get selected time slot
    const selectedTimeOption = document.querySelector('.time-option.selected');
    const customTimeStart = document.getElementById('custom-time-start');
    const customTimeEnd = document.getElementById('custom-time-end');
    
    let timeSlot = '';
    if (selectedTimeOption) {
        timeSlot = selectedTimeOption.dataset.time;
    } else if (customTimeStart.value && customTimeEnd.value) {
        timeSlot = `${formatTime(customTimeStart.value)} - ${formatTime(customTimeEnd.value)}`;
    }
    
    if (!timeSlot) {
        alert('Please select a time slot or enter custom time');
        return;
    }
    
    // Include time slot in generated schedule
    visits.forEach(visit => {
        visit.time = timeSlot;
    });
    
    renderVisitSchedule(visits);
}

function clearSchedule() {
    if (confirm('Are you sure you want to clear the schedule?')) {
        document.getElementById('visit-schedule').innerHTML = '';
        // Reset other form elements as needed
    }
}

// Time slot handling functions
function initTimeSlotSelection() {
    const timeOptions = document.querySelectorAll('.time-option');
    const customTimeStart = document.getElementById('custom-time-start');
    const customTimeEnd = document.getElementById('custom-time-end');
    let selectedTimeSlot = '';

    // Handle predefined time slot selection
    timeOptions.forEach(option => {
        option.addEventListener('click', () => {
            timeOptions.forEach(btn => btn.classList.remove('selected'));
            option.classList.add('selected');
            selectedTimeSlot = option.dataset.time;
            
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

            // Convert to 12-hour format
            selectedTimeSlot = `${formatTime(start)} - ${formatTime(end)}`;
            
            // Clear predefined selections
            timeOptions.forEach(btn => btn.classList.remove('selected'));
            
            // Update the schedule
            updateScheduleWithTime(selectedTimeSlot);
        }
    }

    customTimeStart.addEventListener('input', handleCustomTimeInput);
    customTimeEnd.addEventListener('input', handleCustomTimeInput);
}

function updateScheduleWithTime(timeSlot) {
    const scheduleContainer = document.getElementById('visit-schedule');
    if (!scheduleContainer) return;
    
    const visitItems = scheduleContainer.getElementsByClassName('visit-date-item');
    Array.from(visitItems).forEach(item => {
        const timeSpan = item.querySelector('.time');
        if (timeSpan) {
            timeSpan.textContent = timeSlot;
        }
    });
}

function formatTime(time24h) {
    return new Date('2000-01-01T' + time24h)
        .toLocaleTimeString('en-US', { 
            hour: 'numeric', 
            minute: '2-digit', 
            hour12: true 
        });
}

// Enhanced function to load customer details when a customer is selected
function loadCustomerDetails(appointmentId) {
    if (!appointmentId) return;
    
    console.log('Appointment ID:', appointmentId);
    
    const selectedOption = document.querySelector(`#customer-select option[value="${appointmentId}"]`);
    if (!selectedOption) {
        console.error('Selected option not found');
        return;
    }
    
    // Get data from data attributes
    const serviceId = selectedOption.getAttribute('data-service');
    const location = selectedOption.getAttribute('data-location');
    const allTechnicians = selectedOption.getAttribute('data-all-technicians');
    const allTechnicianNames = selectedOption.getAttribute('data-all-technician-names');
    
    console.log('Service ID:', serviceId);
    console.log('Location:', location);
    console.log('All Technicians:', allTechnicians);
    console.log('All Technician Names:', allTechnicianNames);
    
    // Update the form fields
    if (serviceId) {
        document.getElementById('service-type').value = serviceId;
    }
    
    if (location) {
        document.getElementById('customer-location').value = location;
    }
    
    // Handle technician selection
    const technicianSelect = document.getElementById('technician-select');
    if (!technicianSelect) {
        console.error('Technician select element not found');
        return;
    }
    
    // Log current options for debugging
    console.log('Available technician options:');
    Array.from(technicianSelect.options).forEach(opt => {
        console.log(`- Option ${opt.value}: ${opt.text}`);
    });
    
    // First, clear all selections
    for (let i = 0; i < technicianSelect.options.length; i++) {
        technicianSelect.options[i].selected = false;
    }
    
    // Ensure the select is visible
    technicianSelect.style.display = 'block';
    technicianSelect.style.visibility = 'visible';
    
    // If we have technician data from all-technicians attribute
    if (allTechnicians && allTechnicians.trim() !== '' && allTechnicians !== 'null') {
        const techIds = allTechnicians.split(',').map(id => id.trim());
        console.log('Setting technicians:', techIds);
        
        // Create a set of available option values for faster lookup
        const availableValues = new Set();
        Array.from(technicianSelect.options).forEach(opt => {
            availableValues.add(opt.value);
        });
        
        // Log the available values
        console.log('Available technician values:', Array.from(availableValues));
        
        // Loop through each technician ID and select it if available
        techIds.forEach(techId => {
            if (availableValues.has(techId)) {
                console.log(`Selecting technician ID ${techId}`);
                // Find and select the option
                for (let i = 0; i < technicianSelect.options.length; i++) {
                    if (technicianSelect.options[i].value === techId) {
                        technicianSelect.options[i].selected = true;
                        break;
                    }
                }
            } else {
                console.warn(`Technician with ID ${techId} not found in available options`);
                
                // Alternative approach - try adding the option if it doesn't exist
                if (allTechnicianNames) {
                    const techNamesList = allTechnicianNames.split(',').map(name => name.trim());
                    const techNames = techNamesList[techIds.indexOf(techId)];
                    
                    if (techNames) {
                        console.log(`Adding missing technician: ${techId} - ${techNames}`);
                        const newOption = new Option(techNames, techId, false, true);
                        technicianSelect.add(newOption);
                    }
                }
            }
        });
    } else {
        // Fallback to single technician if no multiple technicians found
        const technicianId = selectedOption.getAttribute('data-technician');
        if (technicianId && technicianId !== 'null') {
            console.log('Selected Technician ID:', technicianId);
            
            // Check if this technician exists in the options
            let found = false;
            for (let i = 0; i < technicianSelect.options.length; i++) {
                if (technicianSelect.options[i].value === technicianId) {
                    technicianSelect.options[i].selected = true;
                    found = true;
                    break;
                }
            }
            
            if (!found) {
                console.warn(`Technician with ID ${technicianId} not found in options - will try to add it`);
                const techName = selectedOption.getAttribute('data-technician-name');
                if (techName) {
                    const newOption = new Option(techName, technicianId, false, true);
                    technicianSelect.add(newOption);
                }
            }
        }
    }
}

// Add event listener for technician select changes - modified to remove display updating
document.addEventListener('DOMContentLoaded', function() {
    const techSelect = document.getElementById('technician-select');
    if (techSelect) {
        // Initialize the select with a proper height
        techSelect.style.height = 'auto';
        techSelect.style.minHeight = '150px';
        
        // Make sure the select element is visible
        setTimeout(() => {
            techSelect.style.display = 'block';
            techSelect.style.visibility = 'visible';
            techSelect.style.opacity = '1';
        }, 100);
    }
});

// Function to schedule a follow-up with improved technician handling
function scheduleFollowUp() {
    const customerId = document.getElementById('customer-select').value;
    const serviceId = document.getElementById('service-type').value;
    const technicianSelect = document.getElementById('technician-select');
    const followupDate = document.getElementById('followup-date').value;
    const followupTime = document.getElementById('followup-time').value;
    
    // Get all selected technician IDs as a comma-separated string
    const selectedTechnicianIds = Array.from(technicianSelect.selectedOptions)
        .map(option => option.value)
        .join(',');
    
    // Enhanced validation
    if (!customerId) {
        alert('Please select a customer first');
        return;
    }
    
    if (!serviceId) {
        alert('Please select a service type');
        return;
    }
    
    if (!selectedTechnicianIds) {
        alert('Please assign at least one technician');
        return;
    }
    
    if (!followupDate) {
        alert('Please select a follow-up date');
        return;
    }
    
    if (!followupTime) {
        alert('Please select a follow-up time');
        return;
    }
    
    // Prepare data for submission
    const formData = new FormData();
    formData.append('appointment_id', customerId);
    formData.append('service_id', serviceId);
    formData.append('technician_id', selectedTechnicianIds); // Send as comma-separated string
    formData.append('followup_date', followupDate);
    formData.append('followup_time', followupTime);
    
    // Log the data being sent
    console.log('Scheduling follow-up with data:', {
        appointment_id: customerId,
        service_id: serviceId,
        technician_id: selectedTechnicianIds,
        followup_date: followupDate,
        followup_time: followupTime
    });
    
    // Show loading indication
    const scheduleBtn = document.getElementById('schedule-followup-btn');
    const originalText = scheduleBtn.innerHTML;
    scheduleBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Scheduling...';
    scheduleBtn.disabled = true;
    
    // Submit the form
    fetch('../HTML CODES/schedule_followup-pct.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('Response from server:', data);
        if (data.success) {
            alert('Follow-up scheduled successfully!');
            // Refresh the page after a short delay
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            alert(data.message || 'Error scheduling follow-up');
            // Reset button state
            scheduleBtn.innerHTML = originalText;
            scheduleBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error scheduling follow-up:', error);
        alert('An error occurred while scheduling the follow-up');
        // Reset button state
        scheduleBtn.innerHTML = originalText;
        scheduleBtn.disabled = false;
    });
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

// Add event listeners for the followup section when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize time slot selection for schedule follow-up
    if (document.querySelector('.time-option')) {
        initTimeSlotSelection();
    }

    // Initialize follow-ups table functionality
    initFollowupsTable();
    
    // Attach event handler to schedule button
    const scheduleBtn = document.getElementById('schedule-followup-btn');
    if (scheduleBtn) {
        scheduleBtn.addEventListener('click', scheduleFollowUp);
    }

    // Initialize customer select change event if it exists on this page
    const customerSelect = document.getElementById('customer-select');
    if (customerSelect) {
        customerSelect.addEventListener('change', function() {
            loadCustomerDetails(this.value);
        });
        
        // If a value is already selected on page load, trigger the change event
        if (customerSelect.value) {
            loadCustomerDetails(customerSelect.value);
        }
    }
    
    // Debug technician select
    const techSelect = document.getElementById('technician-select');
    if (techSelect) {
        console.log('DOM loaded - Technician select found with', techSelect.options.length, 'options');
        // Log each option
        Array.from(techSelect.options).forEach((opt, index) => {
            console.log(`Tech option ${index}: value=${opt.value}, text=${opt.textContent}`);
        });
    } else {
        console.warn('DOM loaded - Technician select element not found!');
    }
});

let originalTechnicianOptions = '';

document.addEventListener('DOMContentLoaded', function() {
    const techSelect = document.getElementById('technician-select');
    if (techSelect) {
        originalTechnicianOptions = techSelect.innerHTML;
    }
});

// Add a function to initialize select elements when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize technician select if it exists
    const techSelect = document.getElementById('technician-select');
    if (techSelect) {
        console.log('Initializing technician select with', techSelect.options.length, 'options');
        
        // Initially, make sure the select is visible
        techSelect.style.display = 'block';
        techSelect.style.visibility = 'visible';
        
        // Ensure the options are styled properly
        Array.from(techSelect.options).forEach(opt => {
            opt.style.padding = '8px';
            opt.style.margin = '4px 0';
        });
        
        // Store original HTML for reference
        originalTechnicianOptions = techSelect.innerHTML;
    }
    
    // Check if customer is already selected
    const customerSelect = document.getElementById('customer-select');
    if (customerSelect && customerSelect.value) {
        console.log('Customer already selected, loading details for:', customerSelect.value);
        loadCustomerDetails(customerSelect.value);
    }
});

// Add a debug function that can be called from the browser console
function debugTechnicianSelect() {
    const techSelect = document.getElementById('technician-select');
    if (!techSelect) {
        console.error('Technician select not found');
        return;
    }
    
    console.log('Technician select element:', techSelect);
    console.log('Display style:', techSelect.style.display);
    console.log('Visibility style:', techSelect.style.visibility);
    console.log('Total options:', techSelect.options.length);
    console.log('Selected options:', techSelect.selectedOptions.length);
    console.log('Selected indices:', Array.from(techSelect).map((opt, idx) => opt.selected ? idx : null).filter(i => i !== null));
    console.log('CSS computed style:', window.getComputedStyle(techSelect));
    
    // Log parent container styles
    const parent = techSelect.parentElement;
    if (parent) {
        console.log('Parent element:', parent);
        console.log('Parent display style:', window.getComputedStyle(parent).display);
        console.log('Parent visibility style:', window.getComputedStyle(parent).visibility);
    }
}

// Function to handle view job details
function viewJobDetails(assignmentId) {
    if (assignmentId) {
        window.location.href = `view_job_details-pct.php?id=${assignmentId}`;
    }
}

// Function to handle viewing submitted reports
function viewSubmittedReports() {
    window.location.href = 'view_submitted_reports-pct.php';
}

// Function to handle assignment search
function initAssignmentSearch() {
    const searchInput = document.getElementById('assignment-search');
    if (!searchInput) return;
    
    const assignmentRows = document.querySelectorAll('tr[data-status]');
    const noResultsRow = document.createElement('tr');
    noResultsRow.className = 'no-results';
    noResultsRow.innerHTML = '<td colspan="7" style="text-align:center; padding: 20px;">No matching assignments found</td>';
    noResultsRow.style.display = 'none';
    
    if (assignmentRows.length > 0) {
        assignmentRows[0].parentNode.appendChild(noResultsRow);
    }
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        let visibleCount = 0;
        
        // Get currently active filter
        const activeFilter = document.querySelector('.filter-btn.active');
        const filterValue = activeFilter ? activeFilter.getAttribute('data-filter') : 'all';
        
        // First apply the status filter, then apply the search filter
        assignmentRows.forEach(row => {
            // Reset the row visibility based on the active filter
            if (filterValue === 'all' || row.getAttribute('data-status') === filterValue) {
                row.classList.remove('hidden-by-filter');
            } else {
                row.classList.add('hidden-by-filter');
            }
            
            // Now apply search filter on top of that
            const rowText = row.textContent.toLowerCase();
            if (searchTerm === '' || rowText.includes(searchTerm)) {
                row.classList.remove('hidden-by-search');
                if (!row.classList.contains('hidden-by-filter')) {
                    visibleCount++;
                }
            } else {
                row.classList.add('hidden-by-search');
            }
        });
        
        // Show/hide the "no results" message
        if (visibleCount === 0) {
            noResultsRow.style.display = 'table-row';
        } else {
            noResultsRow.style.display = 'none';
        }
    });
}

// Updated filter button handling to work with search
document.addEventListener('DOMContentLoaded', function() {
    const filterButtons = document.querySelectorAll('.filter-btn');
    const assignmentRows = document.querySelectorAll('tr[data-status]');
    const searchInput = document.getElementById('assignment-search');
    
    // Initialize assignment search
    initAssignmentSearch();
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons
            filterButtons.forEach(btn => btn.classList.remove('active'));
            
            // Add active class to clicked button
            this.classList.add('active');
            
            const filterValue = this.getAttribute('data-filter');
            const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
            let visibleCount = 0;
            
            // Apply both the filter and search term
            assignmentRows.forEach(row => {
                const rowText = row.textContent.toLowerCase();
                const matchesSearch = searchTerm === '' || rowText.includes(searchTerm);
                
                if ((filterValue === 'all' || row.getAttribute('data-status') === filterValue) && matchesSearch) {
                    row.classList.remove('hidden-by-filter');
                    row.classList.remove('hidden-by-search');
                    row.style.display = '';
                    visibleCount++;
                } else {
                    if (filterValue !== 'all' && row.getAttribute('data-status') !== filterValue) {
                        row.classList.add('hidden-by-filter');
                    } else {
                        row.classList.remove('hidden-by-filter');
                    }
                    
                    if (!matchesSearch) {
                        row.classList.add('hidden-by-search');
                    } else {
                        row.classList.remove('hidden-by-search');
                    }
                    
                    row.style.display = 'none';
                }
            });
            
            // Show/hide no results message
            const noResultsRow = document.querySelector('tr.no-results');
            if (noResultsRow) {
                if (visibleCount === 0) {
                    noResultsRow.style.display = 'table-row';
                } else {
                    noResultsRow.style.display = 'none';
                }
            }
        });
    });
});

// Profile Modal Functionality
document.addEventListener('DOMContentLoaded', function() {
    // Get modal and buttons
    const profileModal = document.getElementById('profileModal');
    const openProfileModalBtn = document.getElementById('openProfileModalBtn');
    const closeProfileModalBtn = document.getElementById('closeProfileModalBtn');
    const closeBtn = document.querySelector('#profileModal .close');
    const editProfileForm = document.getElementById('editProfileForm');
    
    // Function to open modal
    function openProfileModal() {
        if (profileModal) {
            profileModal.style.display = 'flex';
            setTimeout(() => {
                profileModal.classList.add('show');
            }, 10);
        }
    }
    
    // Function to close modal
    function closeProfileModal() {
        if (profileModal) {
            profileModal.classList.remove('show');
            setTimeout(() => {
                profileModal.style.display = 'none';
            }, 300); // Match transition duration
        }
    }
    
    // Event listeners
    if (openProfileModalBtn) {
        openProfileModalBtn.addEventListener('click', openProfileModal);
    }
    
    if (closeProfileModalBtn) {
        closeProfileModalBtn.addEventListener('click', closeProfileModal);
    }
    
    if (closeBtn) {
        closeBtn.addEventListener('click', closeProfileModal);
    }
    
    // Close the modal when clicking outside the content
    window.addEventListener('click', function(event) {
        if (event.target === profileModal) {
            closeProfileModal();
        }
    });
    
    // Handle profile form submission
    if (editProfileForm) {
        editProfileForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Here you would typically send form data with AJAX
            // For now, let's mock a successful update
            const formData = new FormData(editProfileForm);
            
            // Simulate AJAX request (you'd replace this with an actual fetch call)
            setTimeout(() => {
                // Update user info in the profile view
                for (const [name, value] of formData.entries()) {
                    const displayElement = document.querySelector(`.info-content span[data-field="${name}"]`);
                    if (displayElement) {
                        displayElement.textContent = value;
                    }
                }
                
                // Close the modal
                closeProfileModal();
                
                // Success alert removed as requested
            }, 500);
        });
    }
});

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

// Logout confirmation
document.addEventListener('DOMContentLoaded', function() {
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

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initTimeSlotSelection();
});

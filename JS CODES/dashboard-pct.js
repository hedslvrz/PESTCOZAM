// Sidebar toggle
const menuBar = document.querySelector('#main-navbar .bx-menu');
const sidebar = document.getElementById('sidebar');

menuBar.addEventListener('click', function () {
    sidebar.classList.toggle('hide');
});

// Section navigation
function showSection(sectionId) {
    document.querySelectorAll('.section').forEach(section => {
        section.classList.remove('active');
    });
    document.getElementById(sectionId).classList.add('active');
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

// Calendar functionality
document.addEventListener('DOMContentLoaded', function() {
    const calendar = document.querySelector('.calendar-grid');
    const monthDisplay = document.getElementById('currentMonth');
    let currentDate = new Date();

    function renderCalendar(date) {
        const firstDay = new Date(date.getFullYear(), date.getMonth(), 1);
        const lastDay = new Date(date.getFullYear(), date.getMonth() + 1, 0);
        const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 
                          'July', 'August', 'September', 'October', 'November', 'December'];

        monthDisplay.textContent = `${monthNames[date.getMonth()]} ${date.getFullYear()}`;
        calendar.innerHTML = '';

        // Add day headers
        ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].forEach(day => {
            const dayHeader = document.createElement('div');
            dayHeader.className = 'calendar-header-day';
            dayHeader.textContent = day;
            calendar.appendChild(dayHeader);
        });

        // Add blank days from previous month
        for (let i = 0; i < firstDay.getDay(); i++) {
            calendar.appendChild(document.createElement('div'));
        }

        // Add days of current month
        for (let day = 1; day <= lastDay.getDate(); day++) {
            const dayElement = document.createElement('div');
            dayElement.className = 'calendar-day';
            dayElement.textContent = day;

            // Add special classes for today and days with appointments
            if (day === currentDate.getDate() && 
                date.getMonth() === currentDate.getMonth() && 
                date.getFullYear() === currentDate.getFullYear()) {
                dayElement.classList.add('today');
            }

            // Example: Add has-appointment class to some random days
            if (Math.random() > 0.7) {
                dayElement.classList.add('has-appointment');
            }

            calendar.appendChild(dayElement);
        }
    }

    // Initialize calendar
    renderCalendar(currentDate);

    // Previous month button
    document.getElementById('prevMonth').addEventListener('click', () => {
        currentDate = new Date(currentDate.getFullYear(), currentDate.getMonth() - 1);
        renderCalendar(currentDate);
    });

    // Next month button
    document.getElementById('nextMonth').addEventListener('click', () => {
        currentDate = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1);
        renderCalendar(currentDate);
    });
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

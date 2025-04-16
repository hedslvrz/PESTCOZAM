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

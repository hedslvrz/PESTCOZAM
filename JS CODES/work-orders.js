/**
 * Work Orders Management JavaScript
 * This script handles search, filtering, and date filtering for the job orders table
 */

document.addEventListener('DOMContentLoaded', function() {
    // Set a flag to indicate this script is handling the work orders
    window.workOrdersInitialized = true;
    
    // Get references to elements
    const searchInput = document.getElementById('searchAppointments');
    const filterButtons = document.querySelectorAll('.filter-buttons .filter-btn');
    const dateFilter = document.getElementById('filterDate');
    const tableRows = document.querySelectorAll('.work-orders-table tbody tr');
    
    // If elements don't exist, exit early
    if (!searchInput || !dateFilter || tableRows.length === 0) {
        console.log("Required elements not found, exiting early");
        return;
    }
    
    // Set default date to today - but don't apply it as a filter immediately
    const today = new Date().toISOString().split('T')[0];
    dateFilter.value = today;
    let isDateFilterActive = false; // Flag to track if date filter is being used
    
    // Function to filter table rows by search term
    function filterBySearchTerm(term) {
        term = term.toLowerCase().trim();
        
        console.log("Searching for:", term);
        console.log("Number of rows to search:", tableRows.length);
        
        tableRows.forEach(row => {
            if (row.classList.contains('no-records')) return;
            
            const text = row.textContent.toLowerCase();
            console.log("Row text:", text.substring(0, 50) + "...");
            console.log("Contains search term:", text.includes(term));
            
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
        console.log("Filtering by status:", status);
        
        tableRows.forEach(row => {
            if (row.classList.contains('no-records')) return;
            
            const rowStatus = row.getAttribute('data-status');
            console.log("Row status:", rowStatus);
            
            if (status === 'all' || rowStatus === status) {
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
        // If date filter is not active or no date is provided, show all rows
        if (!isDateFilterActive || !date) {
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
        const hasVisibleRows = Array.from(tableRows).some(row => 
            !row.classList.contains('no-records') &&
            !row.classList.contains('no-results') &&
            row.style.display !== 'none'
        );
        
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
    
    // Enhanced function to apply all filters
    function applyAllFilters() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        const activeFilterBtn = document.querySelector('.filter-btn.active');
        const status = activeFilterBtn ? activeFilterBtn.getAttribute('data-filter') : 'all';
        const date = isDateFilterActive ? dateFilter.value : '';
        
        console.log("Applying all filters:");
        console.log("- Search term:", searchTerm);
        console.log("- Status:", status);
        console.log("- Date:", isDateFilterActive ? date : "Not active");
        
        // Reset all rows first
        tableRows.forEach(row => {
            if (row.classList.contains('no-records') || row.classList.contains('no-results')) return;
            
            const rowStatus = row.getAttribute('data-status') || '';
            const rowDate = row.getAttribute('data-date') || '';
            const rowText = row.textContent.toLowerCase();
            
            console.log('Checking row:', { 
                text: rowText.substring(0, 30) + '...', 
                status: rowStatus,
                date: rowDate 
            });
            
            // Check search match
            const searchMatch = !searchTerm || rowText.includes(searchTerm);
            row.dataset.searchMatch = searchMatch ? "true" : "false";
            
            // Check status match
            const statusMatch = status === 'all' || rowStatus === status;
            row.dataset.statusMatch = statusMatch ? "true" : "false";
            
            // Check date match
            const dateMatch = !isDateFilterActive || !date || rowDate === date;
            row.dataset.dateMatch = dateMatch ? "true" : "false";
            
            // Update visibility
            if (searchMatch && statusMatch && dateMatch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
        
        // Show "no results" message if needed
        checkNoResults();
        
        // Log filter results
        const visibleCount = Array.from(tableRows).filter(row => 
            !row.classList.contains('no-records') && 
            !row.classList.contains('no-results') && 
            row.style.display !== 'none'
        ).length;
        
        console.log(`Filter results: ${visibleCount} visible rows out of ${tableRows.length} total`);
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
        // Set the date filter to active when user changes it
        isDateFilterActive = true;
        // Add visual indicator that date filter is active
        this.classList.add('active-filter');
        applyAllFilters();
    });
    
    // Add a reset button for the date filter
    const dateFilterContainer = dateFilter.parentElement;
    if (dateFilterContainer) {
        const resetBtn = document.createElement('button');
        resetBtn.type = 'button';
        resetBtn.className = 'reset-date-filter';
        resetBtn.innerHTML = '<i class="bx bx-x"></i>';
        resetBtn.title = 'Clear date filter';
        resetBtn.style.marginLeft = '5px';
        resetBtn.style.cursor = 'pointer';
        resetBtn.style.display = 'none';
        
        resetBtn.addEventListener('click', function() {
            isDateFilterActive = false;
            dateFilter.classList.remove('active-filter');
            dateFilter.value = today; // Reset to today's date
            this.style.display = 'none';
            applyAllFilters();
        });
        
        dateFilterContainer.appendChild(resetBtn);
        
        // Show reset button when date filter becomes active
        dateFilter.addEventListener('change', function() {
            resetBtn.style.display = 'inline-block';
        });
    }
    
    // Properly initialize table with all rows visible and apply initial filtering
    tableRows.forEach(row => {
        if (!row.classList.contains('no-records') && !row.classList.contains('no-results')) {
            // Set initial dataset attributes
            row.dataset.searchMatch = "true";
            row.dataset.statusMatch = "true";
            row.dataset.dateMatch = "true";
        }
    });
    
    // Apply initial filtering (only apply status filter initially, not date filter)
    applyAllFilters();
    
    // Log initialization complete
    console.log("Work orders filtering initialized successfully");
});

/**
 * Work Orders Management JavaScript
 * This script handles search, filtering, and date filtering for the job orders table
 */

document.addEventListener('DOMContentLoaded', function() {
    // Get references to elements
    const searchInput = document.getElementById('searchAppointments');
    const filterButtons = document.querySelectorAll('.filter-buttons .filter-btn');
    const dateFilter = document.getElementById('filterDate');
    const tableRows = document.querySelectorAll('.work-orders-table tbody tr');
    
    // If elements don't exist, exit early
    if (!searchInput || !dateFilter || tableRows.length === 0) {
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
});

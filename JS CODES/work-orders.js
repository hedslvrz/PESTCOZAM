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
    
    // Set default date to today
    const today = new Date().toISOString().split('T')[0];
    dateFilter.value = today;
    
    // Initial state - show all records first instead of filtering immediately
    // Comment out initial date filtering to show all records first
    // filterByDate(today);
    
    // Function to filter table rows by search term
    function filterBySearchTerm(term) {
        term = term.toLowerCase().trim();
        
        tableRows.forEach(row => {
            if (!row.classList.contains('no-records')) {
                const text = row.textContent.toLowerCase();
                if (term === '' || text.includes(term)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        });
        
        checkNoResults();
    }
    
    // Function to filter table rows by status
    function filterByStatus(status) {
        tableRows.forEach(row => {
            if (!row.classList.contains('no-records')) {
                if (status === 'all') {
                    row.style.display = '';
                } else {
                    const statusCell = row.querySelector('.status');
                    if (statusCell) {
                        // Make case-insensitive comparison and handle partial matches
                        const statusText = statusCell.textContent.toLowerCase().trim();
                        // Check if status text contains the filter term rather than exact match
                        if (statusText.includes(status)) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    } else {
                        row.style.display = 'none';
                    }
                }
            }
        });
        
        checkNoResults();
    }
    
    // Function to filter table rows by date
    function filterByDate(date) {
        if (!date) {
            // If no date is selected, show all rows
            tableRows.forEach(row => {
                if (!row.classList.contains('no-records')) {
                    row.style.display = '';
                }
            });
            return;
        }
        
        // Convert input date to a Date object for comparison
        const filterDate = new Date(date);
        const filterYear = filterDate.getFullYear();
        const filterMonth = filterDate.getMonth();
        const filterDay = filterDate.getDate();
        
        tableRows.forEach(row => {
            if (!row.classList.contains('no-records')) {
                const dateElement = row.querySelector('.schedule-info .date');
                if (dateElement) {
                    try {
                        // Parse the date displayed in the row (format: "May 1, 2023")
                        const displayedDate = dateElement.textContent.trim();
                        const rowDate = new Date(displayedDate);
                        
                        // Make sure rowDate is valid before comparing
                        if (!isNaN(rowDate.getTime())) {
                            // Compare year, month, and day for equality instead of string comparison
                            const rowYear = rowDate.getFullYear();
                            const rowMonth = rowDate.getMonth();
                            const rowDay = rowDate.getDate();
                            
                            if (rowYear === filterYear && rowMonth === filterMonth && rowDay === filterDay) {
                                row.style.display = '';
                            } else {
                                row.style.display = 'none';
                            }
                        } else {
                            console.warn(`Could not parse date: ${displayedDate}`);
                            row.style.display = ''; // Keep visible if cannot parse
                        }
                    } catch (error) {
                        console.error('Error parsing date:', error);
                        row.style.display = ''; // Keep visible if error
                    }
                } else {
                    row.style.display = ''; // If no date element found, keep visible
                }
            }
        });
        
        checkNoResults();
    }
    
    // Function to check if there are no visible results and show a message
    function checkNoResults() {
        let visibleRows = 0;
        tableRows.forEach(row => {
            if (row.style.display !== 'none' && !row.classList.contains('no-records')) {
                visibleRows++;
            }
        });
        
        const tbody = document.querySelector('.work-orders-table tbody');
        
        // Remove existing no-results row if it exists
        const existingNoResults = tbody.querySelector('.no-results');
        if (existingNoResults) {
            tbody.removeChild(existingNoResults);
        }
        
        // Add no-results message if needed
        if (visibleRows === 0) {
            const noResultsRow = document.createElement('tr');
            noResultsRow.className = 'no-results';
            noResultsRow.innerHTML = '<td colspan="7" class="no-records">No matching work orders found</td>';
            tbody.appendChild(noResultsRow);
        }
    }
    
    // Apply all filters together
    function applyAllFilters() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        const activeFilterButton = document.querySelector('.filter-buttons .filter-btn.active');
        const status = activeFilterButton ? activeFilterButton.getAttribute('data-filter') : 'all';
        const date = dateFilter.value;
        
        // Reset display
        tableRows.forEach(row => {
            if (!row.classList.contains('no-records')) {
                row.style.display = '';
            }
        });
        
        // Apply search filter
        if (searchTerm !== '') {
            tableRows.forEach(row => {
                if (!row.classList.contains('no-records') && row.style.display !== 'none') {
                    const text = row.textContent.toLowerCase();
                    if (!text.includes(searchTerm)) {
                        row.style.display = 'none';
                    }
                }
            });
        }
        
        // Apply status filter
        if (status !== 'all') {
            tableRows.forEach(row => {
                if (!row.classList.contains('no-records') && row.style.display !== 'none') {
                    const statusCell = row.querySelector('.status');
                    if (statusCell) {
                        const statusText = statusCell.textContent.toLowerCase().trim();
                        if (!statusText.includes(status)) {
                            row.style.display = 'none';
                        }
                    } else {
                        row.style.display = 'none';
                    }
                }
            });
        }
        
        // Apply date filter
        if (date) {
            // Convert input date to a Date object for comparison
            const filterDate = new Date(date);
            const filterYear = filterDate.getFullYear();
            const filterMonth = filterDate.getMonth();
            const filterDay = filterDate.getDate();
            
            tableRows.forEach(row => {
                if (!row.classList.contains('no-records') && row.style.display !== 'none') {
                    const dateElement = row.querySelector('.schedule-info .date');
                    if (dateElement) {
                        try {
                            const displayedDate = dateElement.textContent.trim();
                            const rowDate = new Date(displayedDate);
                            
                            if (!isNaN(rowDate.getTime())) {
                                // Compare year, month, and day for equality
                                const rowYear = rowDate.getFullYear();
                                const rowMonth = rowDate.getMonth();
                                const rowDay = rowDate.getDate();
                                
                                if (!(rowYear === filterYear && rowMonth === filterMonth && rowDay === filterDay)) {
                                    row.style.display = 'none';
                                }
                            }
                        } catch (error) {
                            console.error('Error parsing date:', error);
                        }
                    }
                }
            });
        }
        
        checkNoResults();
    }
    
    // Add console logging to help debug
    console.log(`Total work order rows: ${tableRows.length}`);
    console.log(`Work orders with 'no-records' class: ${document.querySelectorAll('.work-orders-table tbody tr.no-records').length}`);
    
    // Show all records when the page loads
    console.log('Showing all work order records by default');
    tableRows.forEach(row => {
        if (!row.classList.contains('no-records')) {
            row.style.display = '';
        }
    });
    
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
            
            applyAllFilters();
        });
    });
    
    dateFilter.addEventListener('change', function() {
        applyAllFilters();
    });
});

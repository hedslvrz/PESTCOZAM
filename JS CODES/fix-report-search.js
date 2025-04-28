/**
 * Report search and filter functionality
 * This script improves the search and filter capabilities for technician reports
 */

document.addEventListener('DOMContentLoaded', function() {
    // Wait for the page to fully load
    setTimeout(() => {
        // Make sure all report cards have proper data attributes
        initializeReportCardAttributes();
        
        // Set up search and filter event listeners
        setupReportFilters();
    }, 100);
});

/**
 * Ensure all report cards have the data attributes needed for filtering
 */
function initializeReportCardAttributes() {
    const reportCards = document.querySelectorAll('.report-card');
    console.log(`Initializing attributes for ${reportCards.length} report cards`);
    
    reportCards.forEach(card => {
        // Get data from text content if attributes aren't set
        const techName = card.querySelector('.technician-info h3');
        const locationEl = card.querySelector('.report-preview p:nth-child(1)');
        const clientEl = card.querySelector('.report-preview p:nth-child(2)');
        const serviceEl = card.querySelector('.report-preview p:nth-child(3)');
        const statusEl = card.querySelector('.report-status');
        
        // Set attributes from content
        if (techName && !card.getAttribute('data-tech-name')) {
            card.setAttribute('data-tech-name', techName.textContent.trim());
        }
        
        if (locationEl && !card.getAttribute('data-location')) {
            // Extract location text properly
            let locationText = locationEl.textContent.trim();
            locationText = locationText.replace(/^\s*\u{1F4CD}\s*/u, '').trim();
            card.setAttribute('data-location', locationText);
        }
        
        if (clientEl && !card.getAttribute('data-account')) {
            // Extract client name
            let clientText = clientEl.textContent.trim();
            clientText = clientText.replace(/^Client:\s*/i, '').trim();
            card.setAttribute('data-account', clientText);
        }
        
        if (serviceEl && !card.getAttribute('data-treatment')) {
            // Extract service name
            let serviceText = serviceEl.textContent.trim();
            serviceText = serviceText.replace(/^Service:\s*/i, '').trim();
            card.setAttribute('data-treatment', serviceText);
        }
        
        if (statusEl && !card.getAttribute('data-status')) {
            let statusText = statusEl.textContent.trim().toLowerCase();
            // Normalize status text
            if (statusText.includes('pending')) statusText = 'pending';
            if (statusText.includes('approved')) statusText = 'approved';
            if (statusText.includes('rejected')) statusText = 'rejected';
            card.setAttribute('data-status', statusText);
        }
    });
}

/**
 * Set up the report filter controls with proper event listeners
 */
function setupReportFilters() {
    const searchInput = document.getElementById('reportSearchInput');
    const statusFilter = document.getElementById('statusFilter');
    const dateFilter = document.getElementById('dateFilter');
    const resetFilters = document.getElementById('resetFilters');
    
    if (searchInput) {
        // Replace existing event handlers
        const newInput = searchInput.cloneNode(true);
        searchInput.parentNode.replaceChild(newInput, searchInput);
        
        newInput.addEventListener('input', function() {
            filterReportCards();
        });
    }
    
    if (statusFilter) {
        // Replace existing event handlers
        const newSelect = statusFilter.cloneNode(true);
        statusFilter.parentNode.replaceChild(newSelect, statusFilter);
        
        newSelect.addEventListener('change', function() {
            filterReportCards();
        });
    }
    
    if (dateFilter) {
        // Replace existing event handlers
        const newDate = dateFilter.cloneNode(true);
        dateFilter.parentNode.replaceChild(newDate, dateFilter);
        
        newDate.addEventListener('change', function() {
            filterReportCards();
        });
    }
    
    if (resetFilters) {
        // Replace existing event handlers
        const newReset = resetFilters.cloneNode(true);
        resetFilters.parentNode.replaceChild(newReset, resetFilters);
        
        newReset.addEventListener('click', function() {
            if (document.getElementById('reportSearchInput')) 
                document.getElementById('reportSearchInput').value = '';
            if (document.getElementById('statusFilter')) 
                document.getElementById('statusFilter').value = '';
            if (document.getElementById('dateFilter')) 
                document.getElementById('dateFilter').value = '';
            filterReportCards();
        });
    }
}

/**
 * The main function to filter report cards based on search, status, and date
 */
function filterReportCards() {
    const searchInput = document.getElementById('reportSearchInput');
    const statusFilter = document.getElementById('statusFilter');
    const dateFilter = document.getElementById('dateFilter');
    
    if (!searchInput && !statusFilter && !dateFilter) {
        console.warn('Filter elements not found on the page');
        return;
    }
    
    const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
    const statusTerm = statusFilter ? statusFilter.value.toLowerCase() : '';
    const dateTerm = dateFilter ? dateFilter.value : '';
    
    // Log current filter values
    console.log(`Filtering reports with: search="${searchTerm}", status="${statusTerm}", date="${dateTerm}"`);
    
    const reportCards = document.querySelectorAll('.report-card');
    let visibleCount = 0;
    
    reportCards.forEach(card => {
        // Clear previous highlights
        card.querySelectorAll('.search-highlight').forEach(el => {
            const parent = el.parentNode;
            parent.replaceChild(document.createTextNode(el.textContent), el);
            parent.normalize();
        });
        
        // Get card data
        const techName = (card.getAttribute('data-tech-name') || '').toLowerCase();
        const location = (card.getAttribute('data-location') || '').toLowerCase();
        const account = (card.getAttribute('data-account') || '').toLowerCase();
        const treatment = (card.getAttribute('data-treatment') || '').toLowerCase();
        const status = (card.getAttribute('data-status') || '').toLowerCase();
        const date = card.getAttribute('data-date') || '';
        
        // Check search match
        const matchesSearch = !searchTerm || 
            techName.includes(searchTerm) || 
            location.includes(searchTerm) || 
            account.includes(searchTerm) || 
            treatment.includes(searchTerm);
            
        // Check status match
        const matchesStatus = !statusTerm || status === statusTerm;
        
        // Check date match
        const matchesDate = !dateTerm || date === dateTerm;
        
        // Show/hide based on all criteria
        const isVisible = matchesSearch && matchesStatus && matchesDate;
        card.style.display = isVisible ? 'block' : 'none';
        
        // Add highlights for visible cards
        if (isVisible && searchTerm) {
            // Highlight in technician name
            const techNameEl = card.querySelector('.technician-info h3');
            if (techNameEl) highlightElementText(techNameEl, searchTerm);
            
            // Highlight in location
            const locationEl = card.querySelector('.report-preview p:nth-child(1)');
            if (locationEl) highlightElementText(locationEl, searchTerm);
            
            // Highlight in client name
            const clientEl = card.querySelector('.report-preview p:nth-child(2)');
            if (clientEl) highlightElementText(clientEl, searchTerm);
            
            // Highlight in service type
            const serviceEl = card.querySelector('.report-preview p:nth-child(3)');
            if (serviceEl) highlightElementText(serviceEl, searchTerm);
        }
        
        if (isVisible) visibleCount++;
    });
    
    // Show "no results" message if needed
    updateNoResultsMessage(visibleCount);
    
    console.log(`Filter complete: ${visibleCount} visible cards`);
}

/**
 * Helper function to highlight search terms in element text
 */
function highlightElementText(element, searchTerm) {
    if (!element || !searchTerm) return;
    
    // Get the HTML content
    let html = element.innerHTML;
    
    // Preserve any icons at the beginning
    const iconMatch = html.match(/^(<i[^>]*><\/i>)/);
    const prefixMatch = html.match(/^([^:]*:\s*)/);
    
    let prefix = '';
    let iconHtml = '';
    
    if (iconMatch) {
        iconHtml = iconMatch[1];
        html = html.substring(iconMatch[0].length);
    }
    
    if (prefixMatch) {
        prefix = prefixMatch[1];
        html = html.substring(prefixMatch[0].length);
    }
    
    // Escape regex special characters
    const escapedSearchTerm = searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    
    // Create regex that preserves case
    const regex = new RegExp('(' + escapedSearchTerm + ')', 'gi');
    
    // Replace only the text part, preserving the icon and prefix
    const highlightedText = html.replace(regex, '<span class="search-highlight">$1</span>');
    
    // Reassemble the HTML
    element.innerHTML = iconHtml + prefix + highlightedText;
}

/**
 * Update the "no results" message based on visible report count
 */
function updateNoResultsMessage(visibleCount) {
    const reportsGrid = document.querySelector('.reports-grid');
    if (!reportsGrid) return;
    
    let noReportsMsg = reportsGrid.querySelector('.no-reports');
    
    if (visibleCount === 0) {
        if (!noReportsMsg) {
            // Create a "no results" message
            noReportsMsg = document.createElement('div');
            noReportsMsg.className = 'no-reports';
            noReportsMsg.innerHTML = `
                <i class='bx bx-search-alt'></i>
                <p>No matching reports found</p>
                <span>Try adjusting your search or filter criteria</span>
            `;
            reportsGrid.appendChild(noReportsMsg);
        } else {
            // Update existing message
            noReportsMsg.style.display = 'block';
            noReportsMsg.innerHTML = `
                <i class='bx bx-search-alt'></i>
                <p>No matching reports found</p>
                <span>Try adjusting your search or filter criteria</span>
            `;
        }
    } else if (noReportsMsg) {
        noReportsMsg.style.display = 'none';
    }
}

// Add these functions to the global scope for use throughout the application
window.filterReportsCorrectly = filterReportCards;
window.initializeReportCardAttributes = initializeReportCardAttributes;
window.setupReportFilters = setupReportFilters;

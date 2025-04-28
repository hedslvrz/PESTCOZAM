/**
 * Technician report display fix
 * This script fixes display issues with technician report cards in the admin dashboard
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('Technician report fix loaded');
    
    // Run immediately and also after a short delay to catch dynamic content
    fixTechnicianReportCards();
    setTimeout(fixTechnicianReportCards, 300);
});

/**
 * Fix all technician report cards by ensuring proper display of client info and service details
 */
function fixTechnicianReportCards() {
    // Get all report cards
    const reportCards = document.querySelectorAll('#reports .report-card');
    console.log(`Found ${reportCards.length} technician report cards to fix`);
    
    if (reportCards.length === 0) return;
    
    reportCards.forEach(card => {
        try {
            fixSingleReportCard(card);
        } catch (err) {
            console.error('Error fixing report card:', err);
        }
    });
}

/**
 * Fix a single report card
 */
function fixSingleReportCard(card) {
    // Get the report preview section
    const reportPreview = card.querySelector('.report-preview');
    if (!reportPreview) return;
    
    // Extract all data from card - try data attributes first, then fallback to DOM content
    const location = getCardData(card, 'location', '.report-preview p:nth-child(1)');
    const client = getCardData(card, 'account', '.report-preview p:nth-child(2)', 'Client: ');
    const service = getCardData(card, 'treatment', '.report-preview p:nth-child(3)', 'Service: ');
    
    console.log('Card data:', { location, client, service });
    
    // Clear existing content to prevent duplication
    reportPreview.innerHTML = '';
    
    // Create all paragraphs fresh to ensure consistency
    createParagraph(reportPreview, 'bx-map', location);
    createParagraph(reportPreview, 'bx-user', 'Client: ' + client);
    createParagraph(reportPreview, 'bx-spray-can', 'Service: ' + service);
}

/**
 * Get data from card, trying data attributes first then DOM content
 */
function getCardData(card, dataAttribute, selector, prefixToRemove = '') {
    // First try data attribute
    let value = card.getAttribute('data-' + dataAttribute);
    
    // If not available, try getting from DOM
    if (!value || value === 'undefined' || value === 'null') {
        const element = card.querySelector(selector);
        if (element) {
            // Remove any icons before getting text
            const clone = element.cloneNode(true);
            const icons = clone.querySelectorAll('i');
            icons.forEach(icon => icon.remove());
            
            // Get text and remove prefix if needed
            value = clone.textContent.trim();
            if (prefixToRemove && value.startsWith(prefixToRemove)) {
                value = value.substring(prefixToRemove.length).trim();
            }
        }
    }
    
    // Fallback if still nothing
    if (!value || value === 'undefined' || value === 'null') {
        if (dataAttribute === 'account') return 'Unknown Client';
        if (dataAttribute === 'treatment') return 'Unknown Service';
        if (dataAttribute === 'location') return 'Unknown Location';
        return '';
    }
    
    return value;
}

/**
 * Create a new paragraph with icon and text
 */
function createParagraph(container, iconClass, text) {
    const paragraph = document.createElement('p');
    
    // Create and add icon
    const icon = document.createElement('i');
    icon.className = 'bx ' + iconClass;
    paragraph.appendChild(icon);
    
    // Add text
    const textNode = document.createTextNode(' ' + text);
    paragraph.appendChild(textNode);
    
    // Apply styling directly to ensure consistency
    paragraph.style.display = 'flex';
    paragraph.style.alignItems = 'center';
    paragraph.style.margin = '8px 0';
    paragraph.style.fontSize = '14px';
    paragraph.style.lineHeight = '1.4';
    
    icon.style.marginRight = '8px';
    icon.style.fontSize = '16px';
    icon.style.color = '#144578';
    icon.style.minWidth = '20px';
    icon.style.flexShrink = '0';
    
    container.appendChild(paragraph);
}

// Make functions available globally
window.fixTechnicianReportCards = fixTechnicianReportCards;
window.fixSingleReportCard = fixSingleReportCard;

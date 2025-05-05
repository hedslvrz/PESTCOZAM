// Sidebar toggle
const menuBar = document.querySelector('#main-navbar .bx-menu');
const sidebar = document.getElementById('sidebar');

menuBar.addEventListener('click', function () {
    sidebar.classList.toggle('hide');
});

// Section display - enhanced with debugging
function showSection(sectionId) {
    console.log(`Attempting to show section: ${sectionId}`);
    
    // Validate section exists
    const targetSection = document.getElementById(sectionId);
    if (!targetSection) {
        console.error(`Section with ID "${sectionId}" not found!`);
        return;
    }
    
    // Hide all sections
    const allSections = document.querySelectorAll('.section');
    console.log(`Found ${allSections.length} sections to hide`);
    
    allSections.forEach(section => {
        section.classList.remove('active');
        console.log(`Removed active class from: ${section.id}`);
    });
    
    // Show selected section
    targetSection.classList.add('active');
    console.log(`Added active class to: ${sectionId}`);
    
    // Update active menu item
    const allMenuItems = document.querySelectorAll('.side-menu li');
    console.log(`Found ${allMenuItems.length} menu items to update`);
    
    allMenuItems.forEach(item => {
        item.classList.remove('active');
    });
    
    // Find and activate the menu item - more robust selection
    const menuLinks = document.querySelectorAll('.side-menu a');
    console.log(`Found ${menuLinks.length} menu links to check`);
    
    let menuItemFound = false;
    menuLinks.forEach(link => {
        const href = link.getAttribute('href');
        console.log(`Checking menu link with href: ${href}`);
        
        if (href === `#${sectionId}` || href.endsWith(`#${sectionId}`)) {
            const menuItem = link.closest('li');
            if (menuItem) {
                menuItem.classList.add('active');
                console.log(`Set active menu item for: ${sectionId}`);
                menuItemFound = true;
            }
        }
    });
    
    if (!menuItemFound) {
        console.warn(`No menu item found for section: ${sectionId}`);
    }
}

// NEW GLOBAL FUNCTIONS FOR PROFILE MODAL

function openProfileModal() {
    const modal = document.getElementById('profileModal');
    if (!modal) return;
    modal.style.display = 'flex';
    // Force reflow then add show class for transition
    void modal.offsetWidth;
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeProfileModal() {
    const modal = document.getElementById('profileModal');
    if (!modal) return;
    
    // Reset any save button that might be in "Saving..." state
    const submitBtn = modal.querySelector('[type="submit"]');
    if (submitBtn && submitBtn.disabled) {
        submitBtn.textContent = 'Save Changes';
        submitBtn.disabled = false;
    }
    
    modal.classList.remove('show');
    setTimeout(() => {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }, 300);
}

// Updated function to make profile updates work
function updateUserProfile(form) {
    const submitBtn = form.querySelector('[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Saving...';
    submitBtn.disabled = true;
    
    const formData = new FormData(form);
    
    fetch('../PHP CODES/update_profile.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Create a custom modal or alert to show success
            showModal('Success', 'Profile updated successfully!');
            updateProfileDisplay(formData);
            
            // Ensure button is reset before closing modal
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
            
            closeProfileModal();
            // Clear POST data by replacing state if needed
            history.replaceState(null, null, location.pathname);
        } else {
            showModal('Error', data.message || 'Update failed');
            // Reset button state on error
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error updating profile:', error);
        showModal('Error', 'An error occurred while updating profile');
        // Reset button state on error
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
}

// Helper function to display modal messages
function showModal(title, message) {
    const modal = document.getElementById('customModal');
    if (!modal) return;
    
    const modalTitle = document.getElementById('modalTitle');
    const modalMessage = document.getElementById('modalMessage');
    const modalOkBtn = document.getElementById('modalOkBtn');
    
    if (modalTitle) modalTitle.textContent = title;
    if (modalMessage) modalMessage.textContent = message;
    
    modal.classList.add('show');
    modal.style.display = 'flex';
    
    // Handle OK button click
    if (modalOkBtn) {
        modalOkBtn.onclick = function() {
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }
    }
    
    // Close when clicking outside
    window.onclick = function(event) {
        if (event.target === modal) {
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }
    }
}

function updateProfileDisplay(formData) {
    const firstname = formData.get('firstname');
    const lastname = formData.get('lastname');
    const email = formData.get('email');
    
    // Update Profile Card (using .profile-info)
    const profileName = document.querySelector('.profile-info h3');
    const profileEmail = document.querySelector('.profile-info p');
    if (profileName) profileName.textContent = `${firstname} ${lastname}`;
    if (profileEmail) profileEmail.textContent = email;
    
    // Update details in personal information section
    const fnameDetail = document.querySelector('[data-field="firstname"]');
    const middlenameDetail = document.querySelector('[data-field="middlename"]');
    const lnameDetail = document.querySelector('[data-field="lastname"]');
    const emailDetail = document.querySelector('[data-field="email"]');
    const phoneDetail = document.querySelector('[data-field="mobile_number"]');
    if (fnameDetail) fnameDetail.textContent = firstname;
    if (middlenameDetail) middlenameDetail.textContent = formData.get('middlename') || 'Not set';
    if (lnameDetail) lnameDetail.textContent = lastname;
    if (emailDetail) emailDetail.textContent = email;
    if (phoneDetail) phoneDetail.textContent = formData.get('mobile_number');
}

// NEW: Initialize profile editor functionality
function initProfileEditor() {
    const editProfileBtn = document.getElementById('openProfileModalBtn');
    const profileModal = document.getElementById('profileModal');
    const closeBtn = profileModal ? profileModal.querySelector('.close') : null;
    const cancelBtn = document.getElementById('closeProfileModalBtn');
    const profileForm = document.getElementById('editProfileForm');
    
    if (editProfileBtn && profileModal && profileForm) {
        editProfileBtn.addEventListener('click', function() {
            openProfileModal();
        });
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                closeProfileModal();
            });
        }
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function() {
                closeProfileModal();
            });
        }
        window.addEventListener('click', function(event) {
            if (event.target === profileModal) {
                closeProfileModal();
        }}
    );
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && profileModal.classList.contains('show')) {
                closeProfileModal();
            }
        });
        profileForm.addEventListener('submit', function(e) {
            e.preventDefault();
            updateUserProfile(this);
        });
    }
}

// Add a function to initialize the review modal
function initReviewModal() {
    console.log('Initializing review modal');
    
    // Attach click handlers to review buttons
    const reviewButtons = document.querySelectorAll('.review-btn');
    reviewButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const appointmentId = this.getAttribute('href').split('=')[1];
            console.log('Review button clicked for appointment ID:', appointmentId);
            loadReviewData(appointmentId);
        });
    });
    
    // Close modal when clicking outside
    const reviewModal = document.getElementById('reviewModal');
    if (reviewModal) {
        window.addEventListener('click', function(event) {
            if (event.target === reviewModal) {
                closeReviewModal();
            }
        });
        
        // Close with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && reviewModal.classList.contains('show')) {
                closeReviewModal();
            }
        });
    } else {
        console.error('Review modal element not found in the DOM');
    }
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

    // Initialize follow-ups table functionality
    initFollowupsTable();

    // Initialize profile editor functionality
    initProfileEditor();

    // Initialize the review modal
    initReviewModal();
});

// Updated Report Modal Functions
function openReportModal(reportId) {
    console.log('openReportModal called with ID:', reportId);
    
    // Find the report data
    if (typeof window.reportsData !== 'undefined') {
        const report = window.reportsData.find(r => parseInt(r.report_id) === parseInt(reportId));
        if (!report) {
            console.error('Report not found for ID:', reportId);
            alert('Error: Report data not found');
            return;
        }
        
        console.log('Found report data:', report);
        
        try {
            // Populate the modal fields
            document.getElementById('reportIdField').value = report.report_id || '';
            document.getElementById('reportIdDisplay').value = report.report_id ? `REP-${report.report_id.toString().padStart(4, '0')}` : 'N/A';
            
            if (report.date_of_treatment) {
                document.getElementById('reportDateField').value = new Date(report.date_of_treatment).toLocaleDateString('en-US', {
                    year: 'numeric', month: 'long', day: 'numeric'
                });
            } else {
                document.getElementById('reportDateField').value = 'N/A';
            }
            
            document.getElementById('techNameField').value = report.tech_name || 'N/A';
            document.getElementById('clientNameField').value = report.account_name || 'N/A';
            document.getElementById('contactNoField').value = report.contact_no || 'N/A';
            document.getElementById('locationField').value = report.location || 'N/A';
            document.getElementById('treatmentTypeField').value = report.treatment_type || 'N/A';
            document.getElementById('treatmentMethodField').value = report.treatment_method || 'N/A';
            document.getElementById('timeInField').value = report.time_in || 'N/A';
            document.getElementById('timeOutField').value = report.time_out || 'N/A';
            document.getElementById('pestCountField').value = report.pest_count || 'N/A';
            document.getElementById('deviceInstallationField').value = report.device_installation || 'N/A';
            document.getElementById('chemicalsField').value = report.consumed_chemicals || 'N/A';
            
            // Handle approval buttons based on status
            const approveBtn = document.getElementById('approveBtn');
            const rejectBtn = document.getElementById('rejectBtn');
            
            if (approveBtn && rejectBtn) {
                // Reset any disabled state and event listeners
                approveBtn.disabled = false;
                rejectBtn.disabled = false;
                approveBtn.classList.remove('disabled');
                rejectBtn.classList.remove('disabled');
                
                // Remove existing event listeners by cloning
                const newApproveBtn = approveBtn.cloneNode(true);
                const newRejectBtn = rejectBtn.cloneNode(true);
                approveBtn.parentNode.replaceChild(newApproveBtn, approveBtn);
                rejectBtn.parentNode.replaceChild(newRejectBtn, rejectBtn);
                
                // Add new event listeners
                newApproveBtn.addEventListener('click', function() {
                    updateReportStatus('approved');
                });
                
                newRejectBtn.addEventListener('click', function() {
                    updateReportStatus('rejected');
                });
                
                // Update disabled state based on current status
                if (report.status.toLowerCase() === 'approved') {
                    newApproveBtn.disabled = true;
                    newApproveBtn.classList.add('disabled');
                } else if (report.status.toLowerCase() === 'rejected') {
                    newRejectBtn.disabled = true;
                    newRejectBtn.classList.add('disabled');
                }
            }
            
            // Handle photos
            const imageGallery = document.getElementById('imageGallery');
            const photosSection = document.getElementById('photosSection');
            
            if (imageGallery) {
                imageGallery.innerHTML = ''; // Clear previous images
                
                if (photosSection && report.photos && report.photos !== null && report.photos !== '') {
                    photosSection.style.display = 'block';
                    
                    try {
                        let photos = report.photos;
                        console.log('Processing photos data:', photos, 'Type:', typeof photos);
                        
                        if (typeof photos === 'string') {
                            try {
                                photos = JSON.parse(photos);
                                console.log('Parsed JSON photos:', photos);
                            } catch (e) {
                                console.warn('Error parsing photos JSON, treating as single photo:', e);
                                photos = [photos]; // Treat as single photo
                            }
                        }
                        
                        if (Array.isArray(photos) && photos.length > 0) {
                            console.log('Processing photo array of length:', photos.length);
                            photos.forEach((photo, index) => {
                                createPhotoElement(photo, index, imageGallery);
                            });
                        } else if (photos && typeof photos === 'string') {
                            console.log('Processing single photo string:', photos);
                            createPhotoElement(photos, 0, imageGallery);
                        } else {
                            console.warn('No usable photos found');
                            if (photosSection) photosSection.style.display = 'none';
                        }
                    } catch (e) {
                        console.error('Error processing photos:', e);
                        if (photosSection) photosSection.style.display = 'none';
                    }
                } else {
                    console.log('No photos available for report');
                    if (photosSection) photosSection.style.display = 'none';
                }
            }
            
            // Show the modal
            const modal = document.getElementById('reportModal');
            modal.classList.add('show');
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        } catch (err) {
            console.error('Error populating modal fields:', err);
        }
    } else {
        console.error('reportsData is not defined. Modal cannot be populated.');
    }
}

// Helper function for creating photo elements
function createPhotoElement(photo, index, container) {
    console.log(`Creating photo element for: ${photo} (index: ${index})`);
    const imgContainer = document.createElement('div');
    imgContainer.className = 'image-item';
    
    const img = document.createElement('img');
    img.src = `../uploads/${photo}`;
    img.alt = `Treatment Photo ${index + 1}`;
    
    // Add error handling for images
    img.onerror = function() {
        console.warn(`Image not found: ${img.src}`);
        this.src = '../Pictures/image-placeholder.jpg';
        this.alt = 'Image not found';
    };
    
    const span = document.createElement('span');
    span.textContent = `Photo ${index + 1}`;
    
    imgContainer.appendChild(img);
    imgContainer.appendChild(span);
    container.appendChild(imgContainer);
}

// Function to update report status
function updateReportStatus(status) {
    const reportId = document.getElementById('reportIdField').value;
    if (!reportId) {
        alert('Report ID not found');
        return;
    }
    
    // Confirm before proceeding
    if (!confirm(`Are you sure you want to ${status} this report?`)) {
        return;
    }
    
    fetch('../PHP CODES/update_report_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            report_id: reportId,
            status: status,
            role: 'supervisor' // Add role identifier
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`Report ${status === 'approved' ? 'approved' : 'rejected'} successfully!`);
            // Refresh the page to show updated status
            location.reload();
        } else {
            alert('Error updating report status: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the report status.');
    });
}

// Function to download report PDF
function downloadReportPDF() {
    const reportId = document.getElementById('reportIdField').value;
    const reportForm = document.getElementById('reportForm');
    
    if (!reportForm) {
        alert('Report form not found');
        return;
    }
    
    // Hide buttons during PDF generation
    const actionButtons = document.querySelector('.form-actions');
    if (actionButtons) actionButtons.style.display = 'none';
    
    // Check if html2canvas and jsPDF are available
    if (typeof html2canvas === 'undefined' || typeof window.jspdf === 'undefined') {
        alert('PDF generation libraries not loaded. Unable to create PDF.');
        if (actionButtons) actionButtons.style.display = 'flex';
        return;
    }
    
    html2canvas(reportForm).then(canvas => {
        const imgData = canvas.toDataURL('image/png');
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF('p', 'mm', 'a4');
        const imgProps = pdf.getImageProperties(imgData);
        const pdfWidth = pdf.internal.pageSize.getWidth();
        const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;
        
        pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
        pdf.save(`Service_Report_${reportId}.pdf`);
        
        // Show buttons again after PDF generation
        if (actionButtons) actionButtons.style.display = 'flex';
    });
}

// Updated initializeReportCards function with proper event handling
function initializeReportCards() {
    console.log('Initializing report cards');
    const reportCards = document.querySelectorAll('.report-card');
    
    if (reportCards.length > 0) {
        console.log(`Found ${reportCards.length} report cards`);
        reportCards.forEach(card => {
            // Remove any existing click listeners to avoid duplicates
            const newCard = card.cloneNode(true);
            card.parentNode.replaceChild(newCard, card);
            
            newCard.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const reportId = this.getAttribute('data-report-id');
                console.log('Report card clicked, ID:', reportId);
                openReportModal(reportId);
            });
        });
    } else {
        console.log('No report cards found');
    }
}

function closeReportModal() {
    const modal = document.getElementById('reportModal');
    if (!modal) return;
    modal.classList.remove('show');
    modal.style.display = 'none';
    document.body.style.overflow = '';
}

// Initialize follow-ups table functionality
function initFollowupsTable() {
    console.log('Initializing followups table');
    
    // Get the filter buttons and followup rows
    const filterButtons = document.querySelectorAll('.followups-controls .filter-btn');
    const followupRows = document.querySelectorAll('#followups-list tr.followup-row');
    const searchInput = document.getElementById('followup-search');
    
    // Skip if elements don't exist on the current page
    if (!filterButtons.length || !followupRows.length) {
        console.log('Follow-ups table elements not found on this page');
        return;
    }
    
    console.log(`Found ${filterButtons.length} filter buttons and ${followupRows.length} follow-up rows`);
    
    // Add click event listeners to filter buttons
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons
            filterButtons.forEach(btn => btn.classList.remove('active'));
            
            // Add active class to the clicked button
            this.classList.add('active');
            
            // Get the filter value
            const filterValue = this.getAttribute('data-filter');
            console.log(`Filter button clicked: ${filterValue}`);
            
            // Apply the filter
            filterFollowups(filterValue, searchInput ? searchInput.value : '');
        });
    });
    
    // Add search functionality
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            // Get the current active filter
            const activeFilter = document.querySelector('.followups-controls .filter-btn.active');
            const filterValue = activeFilter ? activeFilter.getAttribute('data-filter') : 'all';
            
            // Apply the filter with search
            filterFollowups(filterValue, this.value);
        });
    }
    
    // Initialize pagination if needed
    initPagination(followupRows);
}

// Function to filter followups based on period and search term
function filterFollowups(period, searchTerm = '') {
    console.log(`Filtering followups: period=${period}, search=${searchTerm}`);
    
    const followupRows = document.querySelectorAll('#followups-list tr.followup-row');
    let visibleCount = 0;
    
    // Get date boundaries for filtering
    const today = new Date();
    const thisWeekStart = new Date(today);
    thisWeekStart.setDate(today.getDate() - today.getDay()); // First day of this week (Sunday)
    
    const nextWeekStart = new Date(thisWeekStart);
    nextWeekStart.setDate(thisWeekStart.getDate() + 7);
    
    const nextWeekEnd = new Date(nextWeekStart);
    nextWeekEnd.setDate(nextWeekStart.getDate() + 6);
    
    const nextMonthStart = new Date(today.getFullYear(), today.getMonth() + 1, 1);
    const nextMonthEnd = new Date(today.getFullYear(), today.getMonth() + 2, 0);
    
    console.log(`Date ranges:
        This Week: ${thisWeekStart.toDateString()} - ${new Date(thisWeekStart).setDate(thisWeekStart.getDate() + 6)}
        Next Week: ${nextWeekStart.toDateString()} - ${nextWeekEnd.toDateString()}
        Next Month: ${nextMonthStart.toDateString()} - ${nextMonthEnd.toDateString()}`);
    
    followupRows.forEach(row => {
        let showRow = true;
        
        // Apply period filter
        if (period !== 'all') {
            const dateStr = row.getAttribute('data-date');
            if (!dateStr) {
                showRow = false;
            } else {
                const rowDate = new Date(dateStr);
                
                switch(period) {
                    case 'thisweek':
                        // Check if date is within this week
                        showRow = rowDate >= thisWeekStart && rowDate <= new Date(thisWeekStart).setDate(thisWeekStart.getDate() + 6);
                        break;
                    case 'nextweek':
                        // Check if date is within next week
                        showRow = rowDate >= nextWeekStart && rowDate <= nextWeekEnd;
                        break;
                    case 'nextmonth':
                        // Check if date is within next month
                        showRow = rowDate >= nextMonthStart && rowDate <= nextMonthEnd;
                        break;
                }
            }
        }
        
        // Apply search filter if there's a search term
        if (showRow && searchTerm) {
            const rowText = row.textContent.toLowerCase();
            showRow = rowText.includes(searchTerm.toLowerCase());
        }
        
        // Show or hide the row
        if (showRow) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    // Handle no results message
    const noResultsRow = document.querySelector('#followups-list tr.no-results');
    if (noResultsRow) {
        if (visibleCount === 0) {
            noResultsRow.style.display = 'table-row';
        } else {
            noResultsRow.style.display = 'none';
        }
    } else if (visibleCount === 0) {
        // Create a no results row if it doesn't exist
        const tbody = document.querySelector('#followups-list');
        if (tbody) {
            const newRow = document.createElement('tr');
            newRow.className = 'no-results';
            newRow.innerHTML = '<td colspan="6">No follow-ups match your criteria</td>';
            tbody.appendChild(newRow);
        }
    }
    
    console.log(`Filtering complete. ${visibleCount} rows visible.`);
    
    // Update pagination if it exists
    updatePagination();
}

// Initialize pagination for follow-ups table
function initPagination(rows, rowsPerPage = 10) {
    // Implementation for pagination if needed
    console.log('Pagination initialized');
}

// Update pagination after filtering
function updatePagination() {
    // Implementation to update pagination after filtering
    console.log('Pagination updated');
}

// Function to load customer details for follow-up scheduling
function loadCustomerDetails(appointmentId) {
    if (!appointmentId) return;
    
    console.log('Loading customer details for appointment ID:', appointmentId);
    
    const option = document.querySelector(`#customer-select option[value="${appointmentId}"]`);
    if (!option) {
        console.error('Selected option not found');
        return;
    }
    
    // Get data from the option attributes
    const serviceId = option.getAttribute('data-service');
    const location = option.getAttribute('data-location');
    const technicianId = option.getAttribute('data-technician');
    const technicianName = option.getAttribute('data-technician-name');
    const allTechnicianIds = option.getAttribute('data-all-technicians');
    
    console.log('Retrieved data:', { serviceId, location, technicianId, technicianName, allTechnicianIds });
    
    // Set the service type
    const serviceSelect = document.getElementById('service-type');
    if (serviceSelect && serviceId) {
        serviceSelect.value = serviceId;
    }
    
    // Set the customer location
    const locationInput = document.getElementById('customer-location');
    if (locationInput && location) {
        locationInput.value = location;
    }
    
    // Set the technician(s)
    const techSelect = document.getElementById('technician-select');
    if (techSelect && allTechnicianIds) {
        // Clear previous selections
        Array.from(techSelect.options).forEach(opt => {
            opt.selected = false;
        });
        
        // Select all assigned technicians
        const techIds = allTechnicianIds.split(',');
        techIds.forEach(id => {
            const option = techSelect.querySelector(`option[value="${id}"]`);
            if (option) {
                option.selected = true;
            }
        });
    } else if (techSelect && technicianId) {
        // Fall back to single technician if all technicians not available
        const option = techSelect.querySelector(`option[value="${technicianId}"]`);
        if (option) {
            option.selected = true;
        }
    }
}

// Initialize work orders filters
function initWorkOrdersFilters() {
    const searchInput = document.getElementById('searchAppointments');
    const filterButtons = document.querySelectorAll('#work-orders .filter-btn');
    const dateFilterInput = document.getElementById('filterDate');
    const appointments = document.querySelectorAll('#work-orders tbody tr:not(.no-records)');
    
    if (!appointments.length) {
        console.log('No appointment rows found');
        return;
    }
    
    // Add search functionality
    if (searchInput) {
        searchInput.addEventListener('input', filterWorkOrders);
    }
    
    // Add status filter functionality
    if (filterButtons.length) {
        filterButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all buttons
                filterButtons.forEach(btn => btn.classList.remove('active'));
                // Add active class to clicked button
                this.classList.add('active');
                // Apply filters
                filterWorkOrders();
            });
        });
    }
    
    // Add date filter functionality
    if (dateFilterInput) {
        dateFilterInput.addEventListener('change', function() {
            // Add visual indicator if date filter is active
            if (this.value) {
                this.classList.add('active-filter');
            } else {
                this.classList.remove('active-filter');
            }
            filterWorkOrders();
        });
    }
    
    // Function to filter work orders
    function filterWorkOrders() {
        const searchValue = searchInput ? searchInput.value.toLowerCase() : '';
        const activeFilter = document.querySelector('#work-orders .filter-btn.active');
        const statusFilter = activeFilter ? activeFilter.getAttribute('data-filter') : 'all';
        const dateFilter = dateFilterInput ? dateFilterInput.value : '';
        
        console.log(`Filtering work orders: search=${searchValue}, status=${statusFilter}, date=${dateFilter}`);
        
        let visibleCount = 0;
        
        appointments.forEach(row => {
            let visible = true;
            
            // Apply status filter
            if (statusFilter !== 'all') {
                const rowStatus = row.getAttribute('data-status');
                if (rowStatus !== statusFilter) {
                    visible = false;
                }
            }
            
            // Apply date filter
            if (visible && dateFilter) {
                const rowDate = row.getAttribute('data-date');
                if (rowDate !== dateFilter) {
                    visible = false;
                }
            }
            
            // Apply search filter
            if (visible && searchValue) {
                const rowText = row.textContent.toLowerCase();
                if (!rowText.includes(searchValue)) {
                    visible = false;
                }
            }
            
            // Show or hide row
            row.style.display = visible ? '' : 'none';
            
            if (visible) visibleCount++;
        });
        
        // Show no results message if needed
        const noResultsRow = document.querySelector('#work-orders tbody tr.no-results');
        if (noResultsRow) {
            noResultsRow.style.display = visibleCount === 0 ? '' : 'none';
        } else if (visibleCount === 0) {
            // Create a no results row if it doesn't exist
            const tbody = document.querySelector('#work-orders tbody');
            if (tbody) {
                const newRow = document.createElement('tr');
                newRow.className = 'no-results';
                newRow.innerHTML = '<td colspan="7">No appointments match your criteria</td>';
                tbody.appendChild(newRow);
            }
        }
    }
}

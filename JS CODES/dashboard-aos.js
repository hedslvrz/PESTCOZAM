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
    modal.classList.remove('show');
    setTimeout(() => {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }, 300);
}

function updateUserProfile(form) {
    const submitBtn = form.querySelector('[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Saving...';
    submitBtn.disabled = true;
    
    const formData = new FormData(form);
    
    fetch('update_profile.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Profile updated successfully!');
            updateProfileDisplay(formData);
            closeProfileModal();
            // Clear POST data by replacing state if needed
            history.replaceState(null, null, location.pathname);
        } else {
            alert('Error: ' + (data.message || 'Update failed'));
        }
    })
    .catch(error => {
        console.error('Error updating profile:', error);
        alert('An error occurred while updating profile');
    })
    .finally(() => {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
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
            document.getElementById('frequencyField').value = report.frequency_of_visits || 'N/A';
            
            // Handle approval buttons based on status
            const approveBtn = document.getElementById('approveBtn');
            const rejectBtn = document.getElementById('rejectBtn');
            
            if (approveBtn && rejectBtn) {
                if (report.status === 'approved') {
                    approveBtn.disabled = true;
                    approveBtn.classList.add('disabled');
                    rejectBtn.disabled = false;
                    rejectBtn.classList.remove('disabled');
                } else if (report.status === 'rejected') {
                    approveBtn.disabled = false;
                    approveBtn.classList.remove('disabled');
                    rejectBtn.disabled = true;
                    rejectBtn.classList.add('disabled');
                } else {
                    approveBtn.disabled = false;
                    approveBtn.classList.remove('disabled');
                    rejectBtn.disabled = false;
                    rejectBtn.classList.remove('disabled');
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
    
    fetch('../PHP CODES/update_report_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            report_id: reportId,
            status: status
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`Report ${status === 'approved' ? 'approved' : 'rejected'} successfully!`);
            // Refresh the page to show updated status
            location.reload();
        } else {
            alert('Error updating report status: ' + data.message);
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

// Replace the existing initializeReportCards function with this enhanced version
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

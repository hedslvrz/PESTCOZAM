const allSideMenu = document.querySelectorAll('#sidebar .side-menu.top li a');

allSideMenu.forEach(item=> {
    const li = item.parentElement;

    item.addEventListener('click', function(){
        allSideMenu.forEach(i=> {
            i.parentElement.classList.remove('active');
        })
        li.classList.add('active');
    })
});


// TOGGLE SIDEBAR //
const allMenus = document.querySelectorAll('.bx-menu');
const sidebar = document.getElementById('sidebar');

allMenus.forEach(menu => {
    menu.addEventListener('click', function() {
        sidebar.classList.toggle('hide');
    });
});
// TOGGLE SIDEBAR //


if(window.innerWidth <768){
    sidebar.classList.add('hide');
} else if(window.innerWidth < 576){
    
}

document.addEventListener('DOMContentLoaded', function() {
    // Search functionality with null checks
    const searchButton = document.querySelector('#content nav form .form-input button');
    const searchForm = document.querySelector('#content nav form');
    
    if (searchButton && searchForm) {
        const searchButtonIcon = searchButton.querySelector('.bx');
        
        searchButton.addEventListener('click', function(e) {
            if (window.innerWidth < 576) {
                e.preventDefault();
                searchForm.classList.toggle('show');
                if (searchForm.classList.contains('show') && searchButtonIcon) {
                    searchButtonIcon.classList.replace('bx-search', 'bx-x');
                }
            }
        });
    }

    // Initialize technician assignment forms
    const assignForms = document.querySelectorAll('.inline-assign-form');
    
    if (assignForms.length > 0) {
        assignForms.forEach(form => {
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const appointmentId = this.dataset.appointmentId;
                const techSelect = this.querySelector('.tech-select');
                
                if (!techSelect) {
                    console.error('Technician select element not found');
                    return;
                }
                
                const technicianId = techSelect.value;
                
                if (!technicianId) {
                    alert('Please select a technician');
                    return;
                }
                
                try {
                    const response = await fetch('../PHP CODES/assign_technician.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            appointment_id: appointmentId,
                            technician_id: technicianId
                        })
                    });
                    
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    
                    const result = await response.json();
                    console.log('Server response:', result);
                    
                    if (result.success) {
                        // Update the UI with assigned technician
                        const row = form.closest('tr');
                        const techCell = row.querySelector('.tech-info');
                        const statusCell = row.querySelector('.status');
                        
                        if (techCell) {
                            techCell.innerHTML = `
                                <i class='bx bx-user-check'></i>
                                <span>${result.technician.firstname} ${result.technician.lastname}</span>
                            `;
                        }
                        
                        if (statusCell) {
                            statusCell.textContent = 'Confirmed';
                            statusCell.className = 'status confirmed';
                        }
                        
                        alert('Technician assigned successfully!');
                    } else {
                        alert(result.message || 'Failed to assign technician');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('An error occurred while processing your request');
                }
            });
        });
    }

    // Auto refresh work orders table every 30 seconds
    if (document.querySelector('.work-orders-table')) {
        setInterval(function() {
            if (document.getElementById('work-orders').classList.contains('active')) {
                location.reload();
            }
        }, 30000); // 30 seconds
    }
    
    // Filter functionality
    const filterButtons = document.querySelectorAll('.filter-btn');
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            const filter = this.getAttribute('data-filter');
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach(row => {
                if (filter === 'all' || row.querySelector('.status').textContent.toLowerCase() === filter) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });

    // Enhanced logout confirmation
    const logoutLink = document.querySelector('a[href*="logout"]'); // Ensure the correct logout link is selected
    const logoutModal = document.getElementById('logoutModal');
    const confirmLogout = document.getElementById('confirmLogout');
    const cancelLogout = document.getElementById('cancelLogout');

    if (logoutLink) {
        logoutLink.addEventListener('click', function (e) {
            e.preventDefault(); // Prevent default logout behavior
            logoutModal.style.display = 'block';
        });

        confirmLogout.addEventListener('click', function () {
            window.location.href = logoutLink.href; // Redirect to the logout link
        });

        cancelLogout.addEventListener('click', function () {
            logoutModal.style.display = 'none'; // Hide the modal
        });

        // Close modal when clicking outside the modal content
        window.addEventListener('click', function (e) {
            if (e.target === logoutModal) {
                logoutModal.style.display = 'none';
            }
        });
    }

    // Initialize report cards
    initializeReportCards();

    // Add modal close handlers
    const closeButtons = document.querySelectorAll('.close-modal');
    closeButtons.forEach(button => {
        button.addEventListener('click', closeReportModal);
    });

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('reportModal');
        if (event.target === modal) {
            closeReportModal();
        }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeReportModal();
        }
    });
});

// Technician Assignment Functions
function openAssignModal(appointmentId) {
    const modal = document.getElementById('assignTechModal');
    document.getElementById('appointmentId').value = appointmentId;
    modal.style.display = 'block';
}

function closeAssignModal() {
    const modal = document.getElementById('assignTechModal');
    modal.style.display = 'none';
}

// Handle technician assignment form submission
document.getElementById('assignTechForm').addEventListener('submit', handleAssignSubmit);

function handleAssignSubmit(e) {
    e.preventDefault();
    
    const appointmentId = document.getElementById('appointmentId').value;
    const technicianId = document.getElementById('technicianId').value;

    if (!technicianId) {
        alert('Please select a technician');
        return;
    }

    const formData = {
        appointment_id: appointmentId,
        technician_id: technicianId
    };

    fetch('../PHP CODES/assign_technician.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Technician assigned successfully!');
            closeAssignModal();
            location.reload(); // Refresh to show updated data
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while assigning technician');
    });
}

// Handle technician assignment
document.addEventListener('DOMContentLoaded', function() {
    const assignForms = document.querySelectorAll('.inline-assign-form');
    
    assignForms.forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const appointmentId = this.dataset.appointmentId;
            const techSelect = this.querySelector('.tech-select');
            const technicianId = techSelect.value;
            const technicianName = techSelect.options[techSelect.selectedIndex].text;
            
            if (!technicianId) {
                alert('Please select a technician');
                return;
            }
            
            try {
                const response = await fetch('../PHP CODES/assign_technician.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        appointment_id: appointmentId,
                        technician_id: technicianId
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert(`Technician successfully ${technicianId ? 'updated' : 'assigned'}!`);
                    window.location.reload();
                } else {
                    alert(result.message || 'Failed to assign technician');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while processing your request');
            }
        });
    });
});

// Add filtering functionality
document.querySelectorAll('.filter-btn').forEach(button => {
    button.addEventListener('click', function() {
        // Remove active class from all buttons
        document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
        // Add active class to clicked button
        this.classList.add('active');
        
        // Filter table rows based on status
        const filter = this.dataset.filter;
        const rows = document.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const status = row.querySelector('.status').textContent.toLowerCase();
            if (filter === 'all' || status === filter) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
});

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('assignTechModal');
    if (event.target === modal) {
        closeAssignModal();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Initialize form handler if it exists
    const assignForm = document.getElementById('assignTechForm');
    if (assignForm) {
        assignForm.addEventListener('submit', handleAssignSubmit);
    }

    // Auto refresh work orders table every 30 seconds
    if (document.querySelector('.work-orders-table')) {
        setInterval(function() {
            if (document.getElementById('work-orders').classList.contains('active')) {
                location.reload();
            }
        }, 30000); // 30 seconds
    }
    
    // Filter functionality
    const filterButtons = document.querySelectorAll('.filter-btn');
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            const filter = this.getAttribute('data-filter');
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach(row => {
                if (filter === 'all' || row.querySelector('.status').textContent.toLowerCase() === filter) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });

    // Enhanced logout confirmation
    const logoutLink = document.querySelector('a[href*="logout"]'); // Ensure the correct logout link is selected
    const logoutModal = document.getElementById('logoutModal');
    const confirmLogout = document.getElementById('confirmLogout');
    const cancelLogout = document.getElementById('cancelLogout');

    if (logoutLink) {
        logoutLink.addEventListener('click', function (e) {
            e.preventDefault(); // Prevent default logout behavior
            logoutModal.style.display = 'block';
        });

        confirmLogout.addEventListener('click', function () {
            window.location.href = logoutLink.href; // Redirect to the logout link
        });

        cancelLogout.addEventListener('click', function () {
            logoutModal.style.display = 'none'; // Hide the modal
        });

        // Close modal when clicking outside the modal content
        window.addEventListener('click', function (e) {
            if (e.target === logoutModal) {
                logoutModal.style.display = 'none';
            }
        });
    }
});

// Logout Modal Functionality
document.addEventListener('DOMContentLoaded', function() {
    const logoutLink = document.querySelector('.logout');
    const logoutModal = document.getElementById('logoutModal');
    const confirmLogout = document.getElementById('confirmLogout');
    const cancelLogout = document.getElementById('cancelLogout');

    // Show modal when logout link is clicked
    if (logoutLink) {
        logoutLink.addEventListener('click', function(e) {
            e.preventDefault();
            logoutModal.style.display = 'block';
        });
    }

    // Handle confirm logout
    if (confirmLogout) {
        confirmLogout.addEventListener('click', function() {
            window.location.href = 'Login.php';
        });
    }

    // Handle cancel logout
    if (cancelLogout) {
        cancelLogout.addEventListener('click', function() {
            logoutModal.style.display = 'none';
        });
    }

    // Close modal when clicking outside
    window.addEventListener('click', function(e) {
        if (e.target === logoutModal) {
            logoutModal.style.display = 'none';
        }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && logoutModal.style.display === 'block') {
            logoutModal.style.display = 'none';
        }
    });
});

// Updated Report Modal Functions
function openReportModal(reportId) {
    const modal = document.getElementById('reportModal');
    if (!modal) {
        console.error('Modal element not found');
        return;
    }
    modal.classList.add('show'); // Add show class instead of display:block
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeReportModal() {
    const modal = document.getElementById('reportModal');
    if (!modal) return;
    modal.classList.remove('show');
    modal.style.display = 'none';
    document.body.style.overflow = '';
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

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('reportModal');
    if (event.target === modal) {
        closeReportModal();
    }
}

function showSection(sectionId) {
    // Hide all sections
    document.querySelectorAll('.section').forEach(section => {
        section.style.display = 'none';
        section.classList.remove('active');
    });

    // Show the selected section
    const targetSection = document.getElementById(sectionId);
    if (targetSection) {
        targetSection.style.display = 'block';
        targetSection.classList.add('active');

        // Update the active menu item in the sidebar
        document.querySelectorAll('#sidebar .side-menu.top li').forEach(item => {
            item.classList.remove('active');
        });
        const menuItem = document.querySelector(`#sidebar .side-menu.top li a[href="#${sectionId}"]`).parentElement;
        if (menuItem) {
            menuItem.classList.add('active');
        }
    }
}

// Add click event listeners to all sidebar menu items
document.addEventListener('DOMContentLoaded', function() {
    const sideMenuItems = document.querySelectorAll('#sidebar .side-menu.top li a');
    sideMenuItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const sectionId = this.getAttribute('href').substring(1); // Remove the # from href
            showSection(sectionId);
        });
    });

    // Show dashboard by default
    showSection('content');
});





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

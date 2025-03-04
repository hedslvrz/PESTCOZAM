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

const searchButton = document.querySelector('#content nav form .form-input button');
const searchButtonIcon = document.querySelector('#content nav form .form-input button .bx');
const searchForm = document.querySelector('#content nav form');

    searchButton.addEventListener('click', function(e){
        if(window.innerWidth < 576){
        e.preventDefault();
        searchForm.classList.toggle('show');
        if(searchForm.classList.contains('show')){
            searchButtonIcon.classList.replace('bx-search', 'bx-x');
        }
    }
})

function showSection(sectionId) {
    // Hide all sections first
    document.querySelectorAll('.section').forEach(section => {
        section.style.display = 'none';
        section.classList.remove('active');
    });
    
    // Show the selected section
    if (sectionId === 'dashboard') {
        document.getElementById('content').style.display = 'block';
        document.getElementById('content').classList.add('active');
    } else {
        const selectedSection = document.getElementById(sectionId);
        if (selectedSection) {
            selectedSection.style.display = 'block';
            selectedSection.classList.add('active');
        }
    }
    
    // Update active state in sidebar
    document.querySelectorAll('.side-menu li').forEach(item => {
        item.classList.remove('active');
    });
    
    // Find and activate the clicked sidebar item
    const sidebarItem = document.querySelector(`a[href="#${sectionId}"]`);
    if (sidebarItem) {
        sidebarItem.parentElement.classList.add('active');
    }
}





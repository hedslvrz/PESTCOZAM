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

const menuBar = document.querySelector('#content nav .bx.bx-menu');
const sideBar = document.getElementById('sidebar');

menuBar.addEventListener('click', function(){
    sideBar.classList.toggle('hide');
})
// TOGGLE SIDEBAR //


if(window.innerWidth <768){
    sideBar.classList.add('hide');
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






// note: "button" is in parameters because of toggleSubMenu("this"), as seen in the achievements page.
const toggleButton = document.getElementById('toggle-btn')
const sidebar = document.getElementById('sidebar')

function toggleSidebar(){
sidebar.classList.toggle('close')
toggleButton.classList.toggle('rotate')

Array.from(sidebar.getElementsByClassName('show')).forEach(ul => 
    {
        ul.classList.remove('show')
        ul.previousElementSibling.classList.remove('rotate')
    })
}

function toggleSubMenu(button){
    button.nextElementSibling.classList.toggle('show')
    button.classlist.toggle('rotate')

    if(sidebar.classList.contains('close')){
    sidebar.classList.toggle('close')
    toggleButton.classlist.toggle('rotate')

    }
}
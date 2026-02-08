
// note: "button" is in parameters because of toggleSubMenu("this"), as seen in the achievements page.
function toggleSubMenu(button){
    button.nextElementSibling.classList.toggle('show')
    button.classlist.toggle('rotate')
}
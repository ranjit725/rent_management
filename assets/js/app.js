function toggleSidebar() {
    document.getElementById("sidebar").classList.toggle("active");
    document.getElementById("overlay").classList.toggle("active");
}

function toggleSubmenu(element) {
    element.parentElement.classList.toggle("active");
}


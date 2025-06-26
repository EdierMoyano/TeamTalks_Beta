const sidebar = document.querySelector(".sidebar");
const sidebarToggler = document.querySelector(".sidebar-toggler");



// Colapsar sidebar y cerrar submenús
sidebarToggler.addEventListener("click", () => {
  sidebar.classList.toggle("collapsed");
  document.body.classList.toggle("sidebar-collapsed", sidebar.classList.contains("collapsed"));
});

// Desplegar submenús al hacer clic en el toggle
document.querySelectorAll('.submenu-toggle').forEach(toggle => {
  toggle.addEventListener('click', function(e) {
    e.preventDefault();
    const parent = this.closest('.has-submenu');
    parent.classList.toggle('open');
  });
});


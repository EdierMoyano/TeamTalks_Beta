const sidebar = document.querySelector(".sidebar");
const sidebarToggler = document.querySelector(".sidebar-toggler");



// Colapsar sidebar y cerrar submenús
sidebarToggler.addEventListener("click", () => {
  sidebar.classList.toggle("collapsed");
  document.body.classList.toggle("sidebar-collapsed", sidebar.classList.contains("collapsed"));

  // Cerrar todos los submenús si se colapsa el sidebar
  if (sidebar.classList.contains("collapsed")) {
    document.querySelectorAll(".has-submenu").forEach(item => item.classList.remove("open"));
  }
});


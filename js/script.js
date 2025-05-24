const sidebar = document.querySelector(".sidebar");
const sidebarToggler = document.querySelector(".sidebar-toggler");

// Toggle submenús
document.querySelectorAll(".submenu-toggle").forEach(toggle => {
  toggle.addEventListener("click", (e) => {
    e.preventDefault();

    const parent = toggle.closest(".has-submenu");

    // Cerrar otros submenús
    document.querySelectorAll(".has-submenu").forEach(item => {
      if (item !== parent) item.classList.remove("open");
    });

    // Alternar el submenú actual
    parent.classList.toggle("open");
  });
});

// Colapsar sidebar y cerrar submenús
sidebarToggler.addEventListener("click", () => {
  sidebar.classList.toggle("collapsed");
  document.body.classList.toggle("sidebar-collapsed", sidebar.classList.contains("collapsed"));

  // Cerrar todos los submenús si se colapsa el sidebar
  if (sidebar.classList.contains("collapsed")) {
    document.querySelectorAll(".has-submenu").forEach(item => item.classList.remove("open"));
  }
});
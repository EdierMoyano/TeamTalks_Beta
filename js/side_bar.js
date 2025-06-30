// Variables globales
let sidebarCollapsed = localStorage.getItem("sidebarCollapsed") === "true"

// Inicializar sidebar al cargar la página
document.addEventListener("DOMContentLoaded", () => {
  const sidebar = document.getElementById("sidebar")
  const body = document.body

  // Verificar si es móvil
  if (window.innerWidth <= 768) {
    // En móvil siempre colapsado
    sidebar.classList.add("collapsed")
    body.classList.add("sidebar-collapsed")
    sidebarCollapsed = true
  } else {
    // En desktop aplicar estado guardado
    if (sidebarCollapsed) {
      sidebar.classList.add("collapsed")
      body.classList.add("sidebar-collapsed")
    } else {
      sidebar.classList.remove("collapsed")
      body.classList.remove("sidebar-collapsed")
    }
  }

  // Aplicar ícono inicial de la flecha
  updateToggleIcon()

  // Marcar enlace activo
  markActiveLink()

  // Configurar responsive
  handleResponsive()

  // Ajustar contenido principal
  adjustMainContent()
})

// Toggle del sidebar (solo funciona en desktop)
function toggleSidebar() {
  // No funcionar en móvil
  if (window.innerWidth <= 768) {
    return
  }

  const sidebar = document.getElementById("sidebar")
  const body = document.body

  // Toggle de la clase collapsed
  sidebar.classList.toggle("collapsed")
  body.classList.toggle("sidebar-collapsed")
  sidebarCollapsed = sidebar.classList.contains("collapsed")

  // Guardar estado en localStorage
  localStorage.setItem("sidebarCollapsed", sidebarCollapsed)

  // Cerrar submenús si se colapsa
  if (sidebarCollapsed) {
    closeAllSubmenus()
  }

  // Actualizar la flecha
  updateToggleIcon()

  // Ajustar contenido principal
  adjustMainContent()
}

// Función para actualizar la dirección de la flecha cambiando las clases
function updateToggleIcon() {
  const sidebar = document.getElementById("sidebar")
  const toggleIcon = sidebar.querySelector(".toggle-icon")

  if (toggleIcon) {
    if (sidebar.classList.contains("collapsed")) {
      // Sidebar cerrado: flecha apunta DERECHA (→)
      toggleIcon.className = "bi bi-chevron-right toggle-icon"
    } else {
      // Sidebar abierto: flecha apunta IZQUIERDA (←)
      toggleIcon.className = "bi bi-chevron-left toggle-icon"
    }
  }
}

// Toggle de submenús
function toggleSubmenu(element) {
  const sidebar = document.getElementById("sidebar")

  // No abrir submenús si está colapsado o en móvil
  if (sidebar.classList.contains("collapsed") || window.innerWidth <= 768) {
    return
  }

  const parentItem = element.closest(".nav-item")

  // Cerrar otros submenús
  const allSubmenus = document.querySelectorAll(".has-submenu")
  allSubmenus.forEach((item) => {
    if (item !== parentItem) {
      item.classList.remove("open")
    }
  })

  // Toggle del submenú actual
  parentItem.classList.toggle("open")

  // Prevenir navegación
  event.preventDefault()
  return false
}

// Cerrar todos los submenús
function closeAllSubmenus() {
  const allSubmenus = document.querySelectorAll(".has-submenu")
  allSubmenus.forEach((item) => {
    item.classList.remove("open")
  })
}

// Marcar enlace activo
function markActiveLink() {
  const currentPath = window.location.pathname
  const navLinks = document.querySelectorAll(".nav-link")

  navLinks.forEach((link) => {
    const href = link.getAttribute("href")
    if (href && currentPath.includes(href)) {
      link.classList.add("active")
    }
  })
}

// Manejo responsive
function handleResponsive() {
  window.addEventListener("resize", () => {
    const sidebar = document.getElementById("sidebar")
    const body = document.body

    if (window.innerWidth <= 768) {
      // Móvil: siempre colapsado
      sidebar.classList.add("collapsed")
      body.classList.add("sidebar-collapsed")
      closeAllSubmenus()
    } else {
      // Desktop: restaurar estado guardado
      if (sidebarCollapsed) {
        sidebar.classList.add("collapsed")
        body.classList.add("sidebar-collapsed")
      } else {
        sidebar.classList.remove("collapsed")
        body.classList.remove("sidebar-collapsed")
      }
    }

    // Actualizar flecha después del resize
    updateToggleIcon()

    // Ajustar contenido principal
    adjustMainContent()
  })
}

// Función para ajustar el contenido principal
function adjustMainContent() {
  const sidebar = document.getElementById("sidebar")
  const mainContent = document.querySelector(".main-content")
  const body = document.body

  if (mainContent) {
    if (window.innerWidth <= 768) {
      // En móvil siempre usar el ancho colapsado
      mainContent.style.marginLeft = "var(--sidebar-collapsed-width)"
    } else {
      // En desktop usar el estado actual
      if (sidebar.classList.contains("collapsed")) {
        mainContent.style.marginLeft = "var(--sidebar-collapsed-width)"
      } else {
        mainContent.style.marginLeft = "var(--sidebar-width)"
      }
    }
  }
}

// Ejecutar ajuste al cargar y redimensionar
document.addEventListener("DOMContentLoaded", adjustMainContent)
window.addEventListener("resize", adjustMainContent)

// Animación de entrada para los elementos del menú
function animateMenuItems() {
  const navItems = document.querySelectorAll(".nav-item")
  navItems.forEach((item, index) => {
    // Saltar el primer elemento que es el botón
    if (index > 0) {
      item.style.animationDelay = `${(index - 1) * 0.1}s`
    }
  })
}

// Ejecutar animación al cargar
document.addEventListener("DOMContentLoaded", animateMenuItems)

// Función para detectar dispositivo móvil
function isMobile() {
  return window.innerWidth <= 768
}

// Prevenir comportamientos no deseados en móvil
document.addEventListener("DOMContentLoaded", () => {
  if (isMobile()) {
    // Remover eventos de hover en móvil para mejor rendimiento
    const navLinks = document.querySelectorAll(".nav-link")
    navLinks.forEach((link) => {
      link.addEventListener("touchstart", () => {
        // Activar tooltip en touch
      })
    })
  }
})

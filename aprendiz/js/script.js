document.addEventListener("DOMContentLoaded", () => {
  // Pestañas principales (Tablón, Trabajo, Personas)
  const tabButtons = document.querySelectorAll(".tab-button")
  const tabContents = document.querySelectorAll(".tab-content")

  tabButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const targetTab = this.getAttribute("data-tab")

      // Remover clase active de todos los botones y contenidos
      tabButtons.forEach((btn) => {
        btn.classList.remove("active")
      })
      tabContents.forEach((content) => {
        content.classList.remove("active")
      })

      // Agregar clase active al botón clickeado y su contenido correspondiente
      this.classList.add("active")
      document.getElementById(targetTab).classList.add("active")
    })
  })

  // Pestañas de personas (Instructores/Estudiantes)
  const personasTabButtons = document.querySelectorAll(".personas-tab-button")
  const personasTabContents = document.querySelectorAll(".personas-tab-content")

  personasTabButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const targetTab = this.getAttribute("data-personas-tab")

      // Remover clase active de todos los botones y contenidos de personas
      personasTabButtons.forEach((btn) => {
        btn.classList.remove("active")
      })
      personasTabContents.forEach((content) => {
        content.classList.remove("active")
      })

      // Agregar clase active al botón clickeado y su contenido correspondiente
      this.classList.add("active")
      document.getElementById(targetTab).classList.add("active")
    })
  })

  // Funcionalidad para anuncios
  const btnNuevoAnuncio = document.getElementById("btnNuevoAnuncio")
  const formularioAnuncio = document.getElementById("formularioAnuncio")
  const btnCancelarAnuncio = document.getElementById("btnCancelarAnuncio")
  const formNuevoAnuncio = document.getElementById("formNuevoAnuncio")

  if (btnNuevoAnuncio) {
    btnNuevoAnuncio.addEventListener("click", function () {
      formularioAnuncio.style.display = "block"
      this.style.display = "none"
    })
  }

  if (btnCancelarAnuncio) {
    btnCancelarAnuncio.addEventListener("click", () => {
      formularioAnuncio.style.display = "none"
      btnNuevoAnuncio.style.display = "inline-flex"
      formNuevoAnuncio.reset()
    })
  }

  if (formNuevoAnuncio) {
    formNuevoAnuncio.addEventListener("submit", function (e) {
      e.preventDefault()

      const formData = new FormData(this)
      const submitBtn = this.querySelector('button[type="submit"]')
      const originalText = submitBtn.textContent

      // Deshabilitar botón durante el envío
      submitBtn.disabled = true
      submitBtn.textContent = "Publicando..."

      fetch("crear_anuncio.php", {
        method: "POST",
        body: formData,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            mostrarNotificacion("Anuncio publicado exitosamente", "success")
            // Recargar la página para mostrar el nuevo anuncio
            setTimeout(() => {
              window.location.reload()
            }, 1000)
          } else {
            mostrarNotificacion(data.message || "Error al publicar el anuncio", "error")
          }
        })
        .catch((error) => {
          console.error("Error:", error)
          mostrarNotificacion("Error de conexión", "error")
        })
        .finally(() => {
          // Rehabilitar botón
          submitBtn.disabled = false
          submitBtn.textContent = originalText
        })
    })
  }

  // Efectos de hover para las tarjetas
  const cards = document.querySelectorAll(".card")
  cards.forEach((card) => {
    card.addEventListener("mouseenter", function () {
      this.style.transform = "translateY(-4px)"
    })

    card.addEventListener("mouseleave", function () {
      this.style.transform = "translateY(0)"
    })
  })

  // Funcionalidad adicional: Click en tareas para expandir detalles
  const taskItems = document.querySelectorAll(".task-item")
  taskItems.forEach((item) => {
    item.addEventListener("click", function () {
      const taskTitle = this.querySelector(".task-title").textContent
      console.log("Actividad seleccionada:", taskTitle)

      // Efecto visual al hacer click
      this.style.backgroundColor = "#e0e7ff"
      setTimeout(() => {
        item.style.backgroundColor = ""
      }, 300)
    })
  })

  // Funcionalidad para personas
  const personItems = document.querySelectorAll(".person-item")
  personItems.forEach((item) => {
    item.addEventListener("click", function () {
      const personName = this.querySelector(".person-name").textContent
      const personEmail = this.querySelector(".person-email").textContent
      console.log("Persona seleccionada:", personName, "-", personEmail)

      // Efecto visual
      this.style.backgroundColor = "#f0f9ff"
      setTimeout(() => {
        item.style.backgroundColor = ""
      }, 500)
    })
  })

  // Animación de entrada para los elementos
  const observerOptions = {
    threshold: 0.1,
    rootMargin: "0px 0px -50px 0px",
  }

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.style.opacity = "1"
        entry.target.style.transform = "translateY(0)"
      }
    })
  }, observerOptions)

  // Aplicar animación a las tarjetas
  cards.forEach((card) => {
    card.style.opacity = "0"
    card.style.transform = "translateY(20px)"
    card.style.transition = "opacity 0.6s ease, transform 0.6s ease"
    observer.observe(card)
  })

  // Función para actualizar datos en tiempo real (opcional)
  function actualizarDatos() {
    // Aquí puedes agregar código para actualizar los datos via AJAX
    console.log("Actualizando datos...")
  }

  // Actualizar datos cada 5 minutos (opcional)
  setInterval(actualizarDatos, 300000)
})

// Función para mostrar notificaciones
function mostrarNotificacion(mensaje, tipo = "info") {
  const notificacion = document.createElement("div")
  notificacion.className = "notificacion " + tipo
  notificacion.textContent = mensaje

  // Colores según el tipo
  let backgroundColor = "#4f46e5" // info
  if (tipo === "success") backgroundColor = "#10b981"
  if (tipo === "error") backgroundColor = "#ef4444"

  notificacion.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${backgroundColor};
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 1000;
        transform: translateX(100%);
        transition: transform 0.3s ease;
    `

  document.body.appendChild(notificacion)

  setTimeout(() => {
    notificacion.style.transform = "translateX(0)"
  }, 100)

  setTimeout(() => {
    notificacion.style.transform = "translateX(100%)"
    setTimeout(() => {
      document.body.removeChild(notificacion)
    }, 300)
  }, 3000)
}

// Manejador de materias para instructores - VERSIÓN CORREGIDA
class MateriasHandler {
  constructor() {
    this.currentData = null
    this.searchTimeout = null
    this.init()
  }

  init() {
    // Usar delegación de eventos para elementos dinámicos
    document.addEventListener("click", this.handleClick.bind(this))
    document.addEventListener("input", this.handleInput.bind(this))
    document.addEventListener("submit", this.handleSubmit.bind(this))
    document.addEventListener("change", this.handleChange.bind(this))
  }

  handleClick(e) {
    // Manejar paginación
    if (e.target.closest(".page-link[data-pagina]")) {
      e.preventDefault()
      const pagina = e.target.closest(".page-link").dataset.pagina
      this.cambiarPagina(Number.parseInt(pagina))
    }

    // Manejar limpiar búsqueda
    if (e.target.closest("#limpiarBusqueda, #limpiarBusquedaVacio")) {
      e.preventDefault()
      this.limpiarBusqueda()
    }

    // Manejar clic en tarjetas de materia (para seleccionar radio)
    if (e.target.closest(".materia-card")) {
      const card = e.target.closest(".materia-card")
      const radio = card.querySelector(".materia-radio")
      if (radio && !radio.checked) {
        radio.checked = true
        this.actualizarTarjetas(radio)
      }
    }
  }

  handleInput(e) {
    // Manejar búsqueda en tiempo real
    if (e.target.id === "buscarMaterias") {
      clearTimeout(this.searchTimeout)
      this.searchTimeout = setTimeout(() => {
        this.buscarMaterias(e.target.value)
      }, 500)
    }
  }

  handleSubmit(e) {
    // Manejar envío del formulario
    if (e.target.id === "formAsignarMaterias") {
      e.preventDefault()
      this.guardarMaterias(e.target)
    }
  }

  handleChange(e) {
    // Manejar cambios en radio buttons
    if (e.target.classList.contains("materia-radio")) {
      this.actualizarTarjetas(e.target)
    }
  }

  actualizarTarjetas(radioSelected) {
    // Primero, quitar selección de todas las tarjetas
    document.querySelectorAll(".materia-card").forEach((card) => {
      card.classList.remove("border-primary", "bg-light")
      card.classList.add("border-secondary")
    })

    // Luego, marcar solo la tarjeta seleccionada
    if (radioSelected && radioSelected.checked) {
      const card = radioSelected.closest(".materia-card")
      if (card) {
        card.classList.remove("border-secondary")
        card.classList.add("border-primary", "bg-light")
      }
    }
  }

  obtenerDatosActuales() {
    const dataElement = document.getElementById("materiasData")
    if (dataElement) {
      try {
        this.currentData = JSON.parse(dataElement.textContent)
      } catch (e) {
        console.error("Error parsing materias data:", e)
        this.currentData = null
      }
    }
    return this.currentData
  }

  async cambiarPagina(nuevaPagina) {
    const data = this.obtenerDatosActuales()
    if (!data) return

    const busqueda = document.getElementById("buscarMaterias")?.value || ""
    await this.recargarMaterias(data.instructorId, nuevaPagina, busqueda)
  }

  async limpiarBusqueda() {
    const data = this.obtenerDatosActuales()
    if (!data) return

    const searchInput = document.getElementById("buscarMaterias")
    if (searchInput) {
      searchInput.value = ""
    }
    await this.recargarMaterias(data.instructorId, 1, "")
  }

  async buscarMaterias(busqueda) {
    const data = this.obtenerDatosActuales()
    if (!data) return

    await this.recargarMaterias(data.instructorId, 1, busqueda)
  }

  async recargarMaterias(instructorId, pagina = 1, busqueda = "") {
    try {
      const url = `get_materias.php?id_instructor=${encodeURIComponent(instructorId)}&pagina=${pagina}&busqueda=${encodeURIComponent(busqueda)}`

      const response = await fetch(url)

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      const data = await response.json()

      if (data.success) {
        const container = document.getElementById("asignarMateriasContent")
        if (container) {
          container.innerHTML = data.html
        }
      } else {
        console.error("Error al recargar materias:", data.message)
        this.mostrarError("Error al recargar materias: " + data.message)
      }
    } catch (error) {
      console.error("Error:", error)
      this.mostrarError("Error de conexión al recargar materias")
    }
  }

  async guardarMaterias(form) {
    const formData = new FormData(form)
    const submitBtn = form.querySelector("#btnGuardarMaterias")

    if (!submitBtn) {
      console.error("Botón de guardar no encontrado")
      return
    }

    const originalText = submitBtn.innerHTML

    // Debug: Verificar datos del formulario
    console.log("Datos del formulario:")
    for (const [key, value] of formData.entries()) {
      console.log(key, value)
    }

    // Verificar que hay una materia seleccionada
    const materiaSeleccionada = formData.get("materia_seleccionada")
    console.log("Materia seleccionada:", materiaSeleccionada)

    if (!materiaSeleccionada) {
      this.mostrarError("Por favor selecciona una materia")
      return
    }

    // Mostrar loading
    submitBtn.disabled = true
    submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Guardando...'

    try {
      const response = await fetch("save_materias_instructor.php", {
        method: "POST",
        body: formData,
      })

      console.log("Response status:", response.status)

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      const responseText = await response.text()
      console.log("Response text:", responseText)

      let data
      try {
        data = JSON.parse(responseText)
      } catch (parseError) {
        console.error("Error parsing JSON:", parseError)
        throw new Error("Respuesta del servidor no es JSON válido: " + responseText)
      }

      console.log("Response data:", data)

      if (data.success) {
        // Mostrar éxito temporalmente
        submitBtn.innerHTML = '<i class="bi bi-check-circle"></i> ¡Guardado!'
        submitBtn.classList.remove("btn-primary")
        submitBtn.classList.add("btn-success")

        // Mostrar mensaje de éxito
        this.mostrarExito(`Materia asignada correctamente: ${data.materia_asignada || "Materia seleccionada"}`)

        setTimeout(() => {
          const modal = window.bootstrap.Modal.getInstance(document.getElementById("asignarMateriasModal"))
          if (modal) {
            modal.hide()
          }
          // Recargar la página para actualizar las estadísticas
          window.location.reload()
        }, 1500)
      } else {
        throw new Error(data.message || "Error desconocido")
      }
    } catch (error) {
      console.error("Error completo:", error)
      this.mostrarError("Error: " + error.message)

      // Restaurar botón
      submitBtn.disabled = false
      submitBtn.innerHTML = originalText
      submitBtn.classList.remove("btn-success")
      submitBtn.classList.add("btn-primary")
    }
  }

  mostrarError(mensaje) {
    // Crear toast o alert para mostrar error
    if (window.Swal) {
      window.Swal.fire({
        icon: "error",
        title: "Error",
        text: mensaje,
      })
    } else {
      alert(mensaje)
    }
  }

  mostrarExito(mensaje) {
    // Crear toast o alert para mostrar éxito
    if (window.Swal) {
      window.Swal.fire({
        icon: "success",
        title: "Éxito",
        text: mensaje,
        timer: 2000,
      })
    } else {
      alert(mensaje)
    }
  }
}

// Inicializar el manejador cuando el DOM esté listo
document.addEventListener("DOMContentLoaded", () => {
  window.materiasHandler = new MateriasHandler()
})

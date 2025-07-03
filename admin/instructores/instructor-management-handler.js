// Manejador de gestión de instructores - VERSIÓN CORREGIDA
class InstructorManagementHandler {
  constructor() {
    this.currentData = null
    this.searchTimeout = null
    this.init()
  }

  init() {
    // Usar delegación de eventos para elementos dinámicos
    document.addEventListener("click", this.handleClick.bind(this))
    document.addEventListener("submit", this.handleSubmit.bind(this))
    document.addEventListener("change", this.handleChange ? this.handleChange.bind(this) : () => {})
  }

  handleClick(e) {
    // Manejar clic en botones de cambiar estado
    if (e.target.closest(".cambiar-estado-instructor")) {
      e.preventDefault()
      const button = e.target.closest(".cambiar-estado-instructor")
      this.mostrarModalCambiarEstado(button)
    }

    // Manejar clic en botones de gestionar fichas
    if (e.target.closest(".gestionar-fichas")) {
      e.preventDefault()
      const button = e.target.closest(".gestionar-fichas")
      this.cargarGestionFichas(button)
    }

    // Manejar transferir ficha
    if (e.target.closest(".transferir-ficha")) {
      e.preventDefault()
      const button = e.target.closest(".transferir-ficha")
      this.mostrarModalTransferirFicha(button)
    }

    // Manejar dejar de administrar
    if (e.target.closest(".dejar-administrar")) {
      e.preventDefault()
      const button = e.target.closest(".dejar-administrar")
      this.mostrarModalDejarAdministrar(button)
    }
  }

  handleSubmit(e) {
    // Manejar envío del formulario de cambiar estado
    if (e.target.id === "formCambiarEstado") {
      e.preventDefault()
      this.procesarCambioEstado(e.target)
    }

    // Manejar envío del formulario de transferir ficha
    if (e.target.id === "formTransferirFicha") {
      e.preventDefault()
      this.procesarTransferirFicha(e.target)
    }

    // Manejar envío del formulario de dejar administrar
    if (e.target.id === "formDejarAdministrar") {
      e.preventDefault()
      this.procesarDejarAdministrar(e.target)
    }
  }

  mostrarModalCambiarEstado(button) {
    const idInstructor = button.getAttribute("data-id")
    const nombre = button.getAttribute("data-nombre")
    const estadoActual = button.getAttribute("data-estado")

    document.getElementById("estado_id_instructor").value = idInstructor
    document.getElementById("estado_instructor_nombre").textContent = nombre
    document.getElementById("nuevo_estado").value = estadoActual == 1 ? "2" : "1"

    const modal = window.bootstrap.Modal(document.getElementById("cambiarEstadoModal"))
    modal.show()
  }

  async cargarGestionFichas(button) {
    const idInstructor = button.getAttribute("data-id")
    const nombre = button.getAttribute("data-nombre")

    try {
      const response = await fetch(`get_fichas_instructor.php?id_instructor=${idInstructor}`)
      const data = await response.json()

      if (data.success) {
        document.getElementById("gestionarFichasContent").innerHTML = data.html
        document.getElementById("gestionarFichasModalLabel").textContent = `Gestionar Fichas - ${nombre}`

        const modal = window.bootstrap.Modal(document.getElementById("gestionarFichasModal"))
        modal.show()
      } else {
        this.mostrarError(data.message)
      }
    } catch (error) {
      console.error("Error:", error)
      this.mostrarError("Error al cargar gestión de fichas")
    }
  }

  async procesarCambioEstado(form) {
    const formData = new FormData(form)
    const submitBtn = form.querySelector('button[type="submit"]')
    const originalText = submitBtn.innerHTML

    submitBtn.disabled = true
    submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Procesando...'

    try {
      const response = await fetch("cambiar_estado_instructor.php", {
        method: "POST",
        body: formData,
      })

      const data = await response.json()

      if (data.success) {
        this.mostrarExito(data.message)
        setTimeout(() => {
          location.reload()
        }, 1500)
      } else {
        throw new Error(data.message)
      }
    } catch (error) {
      console.error("Error:", error)
      this.mostrarError("Error: " + error.message)

      submitBtn.disabled = false
      submitBtn.innerHTML = originalText
    }
  }

  async procesarTransferirFicha(form) {
    const formData = new FormData(form)
    formData.append("accion", "transferir")

    try {
      const response = await fetch("gestionar_fichas_instructor.php", {
        method: "POST",
        body: formData,
      })

      const data = await response.json()

      if (data.success) {
        this.mostrarExito(data.message)
        setTimeout(() => {
          location.reload()
        }, 1500)
      } else {
        this.mostrarError(data.message)
      }
    } catch (error) {
      this.mostrarError("Error de conexión")
    }
  }

  async procesarDejarAdministrar(form) {
    const formData = new FormData(form)
    formData.append("accion", "dejar_administrar")

    try {
      const response = await fetch("gestionar_fichas_instructor.php", {
        method: "POST",
        body: formData,
      })

      const data = await response.json()

      if (data.success) {
        this.mostrarExito(data.message)
        setTimeout(() => {
          location.reload()
        }, 1500)
      } else {
        this.mostrarError(data.message)
      }
    } catch (error) {
      this.mostrarError("Error de conexión")
    }
  }

  mostrarError(mensaje) {
    console.error("Mostrando error:", mensaje)
    if (window.Swal) {
      window.Swal.fire({
        icon: "error",
        title: "Error",
        text: mensaje,
      })
    } else {
      alert("Error: " + mensaje)
    }
  }

  mostrarExito(mensaje) {
    if (window.Swal) {
      window.Swal.fire({
        icon: "success",
        title: "Éxito",
        text: mensaje,
        timer: 2000,
      })
    } else {
      alert("Éxito: " + mensaje)
    }
  }
}

// Inicializar el manejador cuando el DOM esté listo
document.addEventListener("DOMContentLoaded", () => {
  window.instructorManagementHandler = new InstructorManagementHandler()
  console.log("InstructorManagementHandler inicializado correctamente")
})

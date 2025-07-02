// Manejador de gestión de instructores - VERSIÓN ACTUALIZADA Y CORREGIDA
class InstructorManagementHandler {
  constructor() {
    this.currentData = null
    this.searchTimeout = null
    this.init()
  }

  init() {
    // Usar delegación de eventos para elementos dinámicos
    document.addEventListener("click", this.handleClick.bind(this))
    // Línea eliminada: document.addEventListener("input", this.handleInput.bind(this))
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

    // Manejar clic en botones de generar reportes
    if (e.target.closest(".generar-reportes")) {
      e.preventDefault()
      const button = e.target.closest(".generar-reportes")
      if (button) this.cargarOpcionesReporte(button)
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

const modal = new bootstrap.Modal(document.getElementById("cambiarEstadoModal"));    modal.show()
  }

  async cargarOpcionesReporte(button) {
    const idInstructor = button.getAttribute("data-id")
    console.log("ID de instructor a reportar:", idInstructor)

    try {
      const response = await fetch(`generar_reporte_instructor.php?id_instructor=${idInstructor}`)
      const data = await response.json()

      if (data.success) {
        document.getElementById("reportesModalContent").innerHTML = data.html

        const modal = new bootstrap.Modal(document.getElementById("reportesModal"))
        modal.show()
      } else {
        this.mostrarError(data.message)
      }
    } catch (error) {
      console.error("Error:", error)
      this.mostrarError("Error al cargar opciones de reporte")
    }
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

        const modal = new bootstrap.Modal(document.getElementById("cambiarEstadoModal"))
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

// Función global para generar reportes (llamada desde el modal)
async function generarReporte(tipo, idInstructor, idFicha = null) {
  try {
    console.log("=== GENERANDO REPORTE ===")
    console.log("Tipo:", tipo)
    console.log("ID Instructor:", idInstructor)
    console.log("ID Ficha:", idFicha)

    // Validar parámetros
    if (!tipo || !idInstructor) {
      throw new Error("Parámetros faltantes: tipo o idInstructor")
    }

    // Construir URL con encoding adecuado
    let url = `procesar_reporte_instructor.php?id_instructor=${encodeURIComponent(idInstructor)}&tipo_reporte=${encodeURIComponent(tipo)}`
    if (idFicha) {
      url += `&id_ficha=${encodeURIComponent(idFicha)}`
    }

    console.log("URL construida:", url)

    // Mostrar loading
    if (window.Swal) {
      window.Swal.fire({
        title: "Generando reporte...",
        text: "Por favor espera",
        allowOutsideClick: false,
        didOpen: () => {
          window.Swal.showLoading()
        },
      })
    }

    // Método 1: Intentar con window.open
    console.log("Intentando abrir ventana...")
    const ventana = window.open(url, "_blank")

    if (ventana) {
      console.log("Ventana abierta exitosamente")
      setTimeout(() => {
        if (window.Swal) {
          window.Swal.fire("Éxito", "Reporte generado correctamente", "success")
        }
      }, 1000)
    } else {
      console.log("Ventana bloqueada, intentando método alternativo...")

      // Método 2: Crear enlace temporal
      const link = document.createElement("a")
      link.href = url
      link.target = "_blank"
      link.download = `reporte_${tipo}_${idInstructor}.xls`
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)

      setTimeout(() => {
        if (window.Swal) {
          window.Swal.fire("Éxito", "Descarga iniciada", "success")
        }
      }, 500)
    }
  } catch (error) {
    console.error("Error al generar reporte:", error)
    if (window.Swal) {
      window.Swal.fire("Error", "No se pudo generar el reporte: " + error.message, "error")
    }
  }
}

function generarReporteFichaSeleccionada(idInstructor) {
  const selectFicha = document.getElementById("selectFichaIndividual")

  if (!selectFicha) {
    console.error("No se encontró el select de fichas")
    if (window.Swal) {
      window.Swal.fire("Error", "No se encontró el selector de fichas", "error")
    }
    return
  }

  const idFicha = selectFicha.value

  console.log("Ficha seleccionada:", idFicha)

  if (!idFicha) {
    if (window.Swal) {
      window.Swal.fire("Atención", "Por favor selecciona una ficha", "warning")
    }
    return
  }

  generarReporte("ficha_individual", idInstructor, idFicha)
}

// Inicializar el manejador cuando el DOM esté listo
document.addEventListener("DOMContentLoaded", () => {
  window.instructorManagementHandler = new InstructorManagementHandler()
  console.log("InstructorManagementHandler inicializado")
})

// Manejador de reportes para fichas
class FichasReportesHandler {
  constructor() {
    this.currentFichaId = null
    this.currentPrograma = ""
    this.init()
  }

  init() {
    // Usar delegación de eventos para elementos dinámicos
    document.addEventListener("click", this.handleClick.bind(this))
  }

  handleClick(e) {
    // Manejar clic en botón de reportes de ficha
    if (e.target.closest(".btn-reportes-ficha")) {
      e.preventDefault()
      const button = e.target.closest(".btn-reportes-ficha")
      this.mostrarModalReportes(button)
    }
  }

  mostrarModalReportes(button) {
    this.currentFichaId = button.getAttribute("data-ficha")
    this.currentPrograma = button.getAttribute("data-programa")

    // Actualizar modal
    document.getElementById("fichaNumeroReporte").textContent = this.currentFichaId
    document.getElementById("programaReporte").textContent = this.currentPrograma

    // Mostrar modal
    const modal = new window.bootstrap.Modal(document.getElementById("reportesFichaModal"))
    modal.show()
  }

  async generarReporte(tipoReporte) {
    if (!this.currentFichaId) {
      this.mostrarError("No se ha seleccionado una ficha")
      return
    }

    try {
      // Mostrar loading
      this.mostrarCargando("Generando reporte...")

      // Crear formulario para descarga
      const form = document.createElement("form")
      form.method = "POST"
      form.action = "generar_reporte_ficha.php"
      form.target = "_blank"
      form.style.display = "none"

      const inputFicha = document.createElement("input")
      inputFicha.type = "hidden"
      inputFicha.name = "id_ficha"
      inputFicha.value = this.currentFichaId
      form.appendChild(inputFicha)

      const inputTipo = document.createElement("input")
      inputTipo.type = "hidden"
      inputTipo.name = "tipo_reporte"
      inputTipo.value = tipoReporte
      form.appendChild(inputTipo)

      document.body.appendChild(form)
      form.submit()
      document.body.removeChild(form)

      // Mostrar éxito
      this.mostrarExito("Reporte generado exitosamente")

      // Cerrar modal
      setTimeout(() => {
        const modal = window.bootstrap.Modal.getInstance(document.getElementById("reportesFichaModal"))
        if (modal) {
          modal.hide()
        }
      }, 1500)
    } catch (error) {
      console.error("Error:", error)
      this.mostrarError("Error al generar el reporte")
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
      alert("Error: " + mensaje)
    }
  }

  mostrarExito(mensaje) {
    if (window.Swal) {
      window.Swal.fire({
        icon: "success",
        title: "Éxito",
        text: mensaje,
        timer: 3000,
        showConfirmButton: false,
      })
    } else {
      alert("Éxito: " + mensaje)
    }
  }

  mostrarCargando(mensaje) {
    if (window.Swal) {
      window.Swal.fire({
        title: "Generando...",
        text: mensaje,
        allowOutsideClick: false,
        showConfirmButton: false,
        willOpen: () => {
          window.Swal.showLoading()
        },
      })
    }
  }
}

// Función global para generar reportes (llamada desde los botones del modal)
function generarReporteFicha(tipoReporte) {
  if (window.fichasReportesHandler) {
    window.fichasReportesHandler.generarReporte(tipoReporte)
  }
}

// Inicializar el manejador cuando el DOM esté listo
document.addEventListener("DOMContentLoaded", () => {
  window.fichasReportesHandler = new FichasReportesHandler()
  console.log("FichasReportesHandler inicializado correctamente")
})

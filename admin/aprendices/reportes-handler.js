// Manejador de reportes para aprendices
document.addEventListener("DOMContentLoaded", () => {
  // Función para mostrar modal de reportes
  window.mostrarModalReportes = () => {
    const modalEl = document.getElementById("reportesModal")
    if (modalEl) {
      const bootstrap = window.bootstrap // Declare the bootstrap variable
      const modal = new bootstrap.Modal(modalEl)
      modal.show()
    } else {
      console.error("Modal de reportes no encontrado")
    }
  }

  // Función para generar reportes
  window.generarReporte = (tipo) => {
    const Swal = window.Swal // Declare the Swal variable
    if (tipo === "general") {
      // Mostrar notificación de descarga
      Swal.fire({
        title: "Generando Reporte General",
        text: "El reporte se está generando y descargará automáticamente...",
        icon: "info",
        showConfirmButton: false,
        timer: 2000,
        timerProgressBar: true,
      })

      // Crear formulario temporal para descarga
      const form = document.createElement("form")
      form.method = "POST"
      form.action = "generar_reporte_excel.php"
      form.style.display = "none"

      const tipoInput = document.createElement("input")
      tipoInput.type = "hidden"
      tipoInput.name = "tipo"
      tipoInput.value = "general"
      form.appendChild(tipoInput)

      document.body.appendChild(form)
      form.submit()
      document.body.removeChild(form)
    } else if (tipo === "ficha") {
      const fichaSeleccionada = document.getElementById("ficha_reporte").value
      if (!fichaSeleccionada) {
        Swal.fire({
          icon: "warning",
          title: "Atención",
          text: "Por favor selecciona una ficha para generar el reporte.",
        })
        return
      }

      // Mostrar notificación de descarga
      Swal.fire({
        title: "Generando Reporte por Ficha",
        text: "El reporte se está generando y descargará automáticamente...",
        icon: "info",
        showConfirmButton: false,
        timer: 2000,
        timerProgressBar: true,
      })

      // Crear formulario temporal para descarga
      const form = document.createElement("form")
      form.method = "POST"
      form.action = "generar_reporte_excel.php"
      form.style.display = "none"

      const tipoInput = document.createElement("input")
      tipoInput.type = "hidden"
      tipoInput.name = "tipo"
      tipoInput.value = "ficha"
      form.appendChild(tipoInput)

      const fichaInput = document.createElement("input")
      fichaInput.type = "hidden"
      fichaInput.name = "ficha"
      fichaInput.value = fichaSeleccionada
      form.appendChild(fichaInput)

      document.body.appendChild(form)
      form.submit()
      document.body.removeChild(form)
    }
  }

  // Función para generar reporte individual
  window.generarReporteIndividual = (idAprendiz, nombreAprendiz) => {
    const Swal = window.Swal // Declare the Swal variable
    Swal.fire({
      title: "Generar Reporte Individual",
      html: `¿Deseas generar el reporte de <strong>${nombreAprendiz}</strong>?`,
      icon: "question",
      showCancelButton: true,
      confirmButtonColor: "#28a745",
      cancelButtonColor: "#6c757d",
      confirmButtonText: '<i class="bi bi-download"></i> Descargar Excel',
      cancelButtonText: "Cancelar",
    }).then((result) => {
      if (result.isConfirmed) {
        // Mostrar notificación de descarga
        Swal.fire({
          title: "Generando Reporte",
          text: "El reporte se está generando y descargará automáticamente...",
          icon: "info",
          showConfirmButton: false,
          timer: 2000,
          timerProgressBar: true,
        })

        // Crear formulario temporal para descarga
        const form = document.createElement("form")
        form.method = "POST"
        form.action = "generar_reporte_individual.php"
        form.style.display = "none"

        const idInput = document.createElement("input")
        idInput.type = "hidden"
        idInput.name = "id_aprendiz"
        idInput.value = idAprendiz
        form.appendChild(idInput)

        document.body.appendChild(form)
        form.submit()
        document.body.removeChild(form)
      }
    })
  }

  // Event listener para botones de reporte individual
  document.addEventListener("click", (event) => {
    if (event.target.closest(".generar-reporte-individual")) {
      const button = event.target.closest(".generar-reporte-individual")
      const idAprendiz = button.getAttribute("data-aprendiz")
      const nombreAprendiz = button.getAttribute("data-nombre")
      window.generarReporteIndividual(idAprendiz, nombreAprendiz) // Use window prefix
    }
  })
})

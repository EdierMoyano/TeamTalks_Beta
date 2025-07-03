// Manejador de reportes para instructores - VERSIÓN FINAL SIN GUARDAR ARCHIVOS
class ReportesHandler {
  constructor() {
    this.currentInstructorId = null
    this.currentInstructorName = ""
    this.init()
  }

  init() {
    // Usar delegación de eventos para elementos dinámicos
    document.addEventListener("click", this.handleClick.bind(this))
    document.addEventListener("submit", this.handleSubmit.bind(this))
    document.addEventListener("change", this.handleChange.bind(this))
  }

  handleClick(e) {
    // Manejar clic en botón de reportes
    if (e.target.closest(".btn-reportes")) {
      e.preventDefault()
      const button = e.target.closest(".btn-reportes")
      this.mostrarModalReportes(button)
    }

    // Manejar clic en opciones de reporte
    if (e.target.closest(".opcion-reporte")) {
      e.preventDefault()
      const button = e.target.closest(".opcion-reporte")
      this.seleccionarTipoReporte(button)
    }
  }

  handleSubmit(e) {
    // Manejar envío del formulario de reportes
    if (e.target.id === "formGenerarReporte") {
      e.preventDefault()
      this.generarReporte(e.target)
    }
  }

  handleChange(e) {
    // Manejar cambio en select de fichas
    if (e.target.id === "selectFichaReporte") {
      this.validarFormularioReporte()
    }

    // Manejar cambio en select de trimestres
    if (e.target.id === "selectTrimestreReporte") {
      this.validarFormularioReporte()
    }
  }

  mostrarModalReportes(button) {
    this.currentInstructorId = button.getAttribute("data-instructor")
    this.currentInstructorName = button.getAttribute("data-nombre")

    // Actualizar título del modal
    document.getElementById("reportesModalLabel").textContent = `Generar Reportes - ${this.currentInstructorName}`
    document.getElementById("nombreInstructorReporte").textContent = this.currentInstructorName
    document.getElementById("instructorIdReporte").value = this.currentInstructorId

    // Resetear formulario
    this.resetearFormulario()

    // Mostrar modal
    const modal = new window.bootstrap.Modal(document.getElementById("reportesModal"))
    modal.show()
  }

  seleccionarTipoReporte(button) {
    const tipoReporte = button.getAttribute("data-tipo")

    // Remover selección anterior
    document.querySelectorAll(".opcion-reporte").forEach((btn) => {
      btn.classList.remove("active", "btn-primary")
      btn.classList.add("btn-outline-primary")
    })

    // Marcar como seleccionado
    button.classList.remove("btn-outline-primary")
    button.classList.add("active", "btn-primary")

    // Actualizar campo oculto
    document.getElementById("tipoReporte").value = tipoReporte

    // Manejar las secciones según el tipo de reporte
    const seccionFichas = document.getElementById("seccionFichas")
    const seccionTrimestre = document.getElementById("seccionTrimestre")
    const selectFicha = document.getElementById("selectFichaReporte")
    const selectTrimestre = document.getElementById("selectTrimestreReporte")

    // Ocultar todas las secciones primero
    seccionFichas.style.display = "none"
    seccionTrimestre.style.display = "none"
    selectFicha.removeAttribute("required")
    selectTrimestre.removeAttribute("required")
    selectFicha.value = ""
    selectTrimestre.value = ""

    // Mostrar sección según el tipo
    if (tipoReporte === "ficha_individual") {
      seccionFichas.style.display = "block"
      selectFicha.setAttribute("required", "required")
      this.cargarFichasInstructor()
    } else if (tipoReporte === "horarios_trimestre") {
      seccionTrimestre.style.display = "block"
      selectTrimestre.setAttribute("required", "required")
      this.cargarTrimestresInstructor()
    }

    // Validar formulario
    this.validarFormularioReporte()
  }

  async cargarFichasInstructor() {
    try {
      const response = await fetch(
        `generar_reportes/get_fichas_instructor_reportes.php?id_instructor=${this.currentInstructorId}`,
      )
      const data = await response.json()

      const select = document.getElementById("selectFichaReporte")
      select.innerHTML = '<option value="">Seleccionar ficha...</option>'

      if (data.success && data.fichas.length > 0) {
        data.fichas.forEach((ficha) => {
          const option = document.createElement("option")
          option.value = ficha.id_ficha
          option.textContent = `${ficha.id_ficha} - ${ficha.programa} (${ficha.total_aprendices} aprendices)`
          select.appendChild(option)
        })
      } else {
        const option = document.createElement("option")
        option.value = ""
        option.textContent = "No hay fichas asignadas"
        option.disabled = true
        select.appendChild(option)
      }
    } catch (error) {
      console.error("Error al cargar fichas:", error)
      this.mostrarError("Error al cargar las fichas del instructor")
    }
  }

  async cargarTrimestresInstructor() {
    try {
      const response = await fetch(
        `generar_reportes/get_trimestres_instructor.php?id_instructor=${this.currentInstructorId}`,
      )
      const data = await response.json()

      const select = document.getElementById("selectTrimestreReporte")
      select.innerHTML = '<option value="">Seleccionar trimestre...</option>'

      if (data.success && data.trimestres.length > 0) {
        data.trimestres.forEach((trimestre) => {
          const option = document.createElement("option")
          option.value = trimestre.id_trimestre
          option.textContent = `${trimestre.trimestre} (${trimestre.total_horarios} horarios - ${Math.round(trimestre.total_horas_semanales)} horas)`
          select.appendChild(option)
        })
      } else {
        const option = document.createElement("option")
        option.value = ""
        option.textContent = "No hay trimestres con horarios"
        option.disabled = true
        select.appendChild(option)
      }
    } catch (error) {
      console.error("Error al cargar trimestres:", error)
      this.mostrarError("Error al cargar los trimestres del instructor")
    }
  }

  validarFormularioReporte() {
    const tipoReporte = document.getElementById("tipoReporte").value
    const btnGenerar = document.getElementById("btnGenerarReporte")

    if (!tipoReporte) {
      btnGenerar.disabled = true
      return
    }

    // Validar según el tipo de reporte
    if (tipoReporte === "ficha_individual") {
      const fichaSeleccionada = document.getElementById("selectFichaReporte").value
      btnGenerar.disabled = !fichaSeleccionada
    } else if (tipoReporte === "horarios_trimestre") {
      const trimestreSeleccionado = document.getElementById("selectTrimestreReporte").value
      btnGenerar.disabled = !trimestreSeleccionado
    } else {
      // Para otros tipos de reporte, habilitar directamente
      btnGenerar.disabled = false
    }
  }

  async generarReporte(form) {
    const formData = new FormData(form)
    const tipoReporte = formData.get("tipo_reporte")

    // Asegurar que el ID del instructor se incluya siempre
    formData.set("id_instructor", this.currentInstructorId)

    // Limpiar campos no necesarios según el tipo de reporte
    if (tipoReporte !== "ficha_individual") {
      formData.delete("id_ficha")
    }
    if (tipoReporte !== "horarios_trimestre") {
      formData.delete("id_trimestre")
    }

    const btnGenerar = document.getElementById("btnGenerarReporte")
    const originalText = btnGenerar.innerHTML

    // Validaciones
    if (!tipoReporte) {
      this.mostrarError("Por favor selecciona un tipo de reporte")
      return
    }

    if (!this.currentInstructorId) {
      this.mostrarError("Error: No se ha seleccionado un instructor")
      return
    }

    if (tipoReporte === "ficha_individual" && !formData.get("id_ficha")) {
      this.mostrarError("Por favor selecciona una ficha")
      return
    }

    if (tipoReporte === "horarios_trimestre" && !formData.get("id_trimestre")) {
      this.mostrarError("Por favor selecciona un trimestre")
      return
    }

    // Mostrar loading
    btnGenerar.disabled = true
    btnGenerar.innerHTML = '<i class="bi bi-hourglass-split"></i> Generando reporte...'

    try {
      // Determinar el archivo PHP según el tipo de reporte
      let endpoint = ""
      switch (tipoReporte) {
        case "general_completo":
          endpoint = "generar_reportes/reporte_general_completo.php"
          break
        case "datos_personales":
          endpoint = "generar_reportes/reporte_datos_personales.php"
          break
        case "fichas_asignadas":
          endpoint = "generar_reportes/reporte_fichas_asignadas.php"
          break
        case "ficha_individual":
          endpoint = "generar_reportes/reporte_ficha_individual.php"
          break
        case "horarios_trimestre":
          endpoint = "generar_reportes/reporte_horarios_trimestre.php"
          break
        default:
          throw new Error("Tipo de reporte no válido")
      }

      // Crear un formulario temporal para enviar la descarga
      const tempForm = document.createElement("form")
      tempForm.method = "POST"
      tempForm.action = endpoint
      tempForm.target = "_blank"
      tempForm.style.display = "none"

      // Agregar todos los campos del FormData al formulario temporal
      for (const [key, value] of formData.entries()) {
        const input = document.createElement("input")
        input.type = "hidden"
        input.name = key
        input.value = value
        tempForm.appendChild(input)
      }

      // Agregar al DOM, enviar y remover
      document.body.appendChild(tempForm)
      tempForm.submit()
      document.body.removeChild(tempForm)

      // Mostrar éxito
      btnGenerar.innerHTML = '<i class="bi bi-check-circle"></i> ¡Reporte generado!'
      btnGenerar.classList.remove("btn-primary")
      btnGenerar.classList.add("btn-success")

      // Mostrar notificación de éxito
      window.Swal.fire({
        icon: "success",
        title: "¡Reporte generado exitosamente!",
        text: "El archivo se está descargando automáticamente",
        timer: 3000,
        showConfirmButton: false,
      })

      // Cerrar modal después de un momento
      setTimeout(() => {
        const modal = window.bootstrap.Modal.getInstance(document.getElementById("reportesModal"))
        if (modal) {
          modal.hide()
        }
      }, 2000)
    } catch (error) {
      console.error("Error:", error)
      this.mostrarError("Error al generar el reporte: " + error.message)
    } finally {
      // Restaurar botón después de un tiempo
      setTimeout(() => {
        btnGenerar.disabled = false
        btnGenerar.innerHTML = originalText
        btnGenerar.classList.remove("btn-success")
        btnGenerar.classList.add("btn-primary")
      }, 3000)
    }
  }

  resetearFormulario() {
    // Limpiar selecciones
    document.querySelectorAll(".opcion-reporte").forEach((btn) => {
      btn.classList.remove("active", "btn-primary")
      btn.classList.add("btn-outline-primary")
    })

    // Resetear campos
    document.getElementById("tipoReporte").value = ""

    const selectFicha = document.getElementById("selectFichaReporte")
    selectFicha.innerHTML = '<option value="">Seleccionar ficha...</option>'
    selectFicha.removeAttribute("required")

    const selectTrimestre = document.getElementById("selectTrimestreReporte")
    selectTrimestre.innerHTML = '<option value="">Seleccionar trimestre...</option>'
    selectTrimestre.removeAttribute("required")

    document.getElementById("seccionFichas").style.display = "none"
    document.getElementById("seccionTrimestre").style.display = "none"
    document.getElementById("btnGenerarReporte").disabled = true

    // Restaurar botón
    const btnGenerar = document.getElementById("btnGenerarReporte")
    btnGenerar.innerHTML = '<i class="bi bi-file-earmark-excel"></i> Generar Reporte'
    btnGenerar.classList.remove("btn-success")
    btnGenerar.classList.add("btn-primary")
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
      })
    } else {
      alert("Éxito: " + mensaje)
    }
  }
}

// Inicializar el manejador cuando el DOM esté listo
document.addEventListener("DOMContentLoaded", () => {
  window.reportesHandler = new ReportesHandler()
  console.log("ReportesHandler inicializado correctamente")
})

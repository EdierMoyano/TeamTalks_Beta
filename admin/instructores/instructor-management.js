// JavaScript mejorado para la gestión de instructores

class InstructorManager {
  constructor() {
    this.instructoresData = []
    this.paginaActual = 1
    this.instructoresPorPagina = 6
    this.searchTimeout = null

    this.init()
  }

  init() {
    document.addEventListener("DOMContentLoaded", () => {
      this.cargarInstructoresData()
      this.setupEventListeners()
      this.initializeTooltips()
      this.setupModalCleanup()
    })
  }

  setupEventListeners() {
    // Event listeners para filtros
    const buscarInput = document.getElementById("buscarInstructor")
    const filtroRol = document.getElementById("filtroRol")

    if (buscarInput) {
      buscarInput.addEventListener("input", () => this.filtrarInstructores())
    }

    if (filtroRol) {
      filtroRol.addEventListener("change", () => this.filtrarInstructores())
    }

    // Event listeners para botones de sección
    document.addEventListener("click", (event) => {
      if (event.target.closest(".ver-detalles")) {
        const button = event.target.closest(".ver-detalles")
        const idInstructor = button.getAttribute("data-instructor")
        this.cargarDetallesInstructor(idInstructor)
      }

      if (event.target.closest(".asignar-materias")) {
        const button = event.target.closest(".asignar-materias")
        const idInstructor = button.getAttribute("data-instructor")
        const nombre = button.getAttribute("data-nombre")
        this.cargarFormularioMaterias(idInstructor, nombre)
      }

      if (event.target.closest(".editar-instructor")) {
        const button = event.target.closest(".editar-instructor")
        this.mostrarModalEditar(button)
      }
    })
  }

  setupModalCleanup() {
    // Limpiar backdrop huérfano cuando se cierran los modales
    document.querySelectorAll(".modal").forEach((modalEl) => {
      modalEl.addEventListener("hidden.bs.modal", () => {
        setTimeout(() => {
          document.querySelectorAll(".modal-backdrop").forEach((el) => el.remove())
          document.body.classList.remove("modal-open")
          document.body.style = ""
        }, 350)
      })
    })
  }

  initializeTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    tooltipTriggerList.map((tooltipTriggerEl) => new bootstrap.Tooltip(tooltipTriggerEl))
  }

  // Mostrar sección específica
  mostrarSeccion(seccion) {
    document.querySelectorAll(".seccion-instructores").forEach((el) => {
      el.style.display = "none"
    })

    document.querySelectorAll("#btnTodosInstructores, #btnSinMaterias").forEach((btn) => {
      btn.classList.remove("active", "btn-primary")
      btn.classList.add("btn-outline-primary")
    })

    if (seccion === "todos") {
      document.getElementById("seccion-todos").style.display = "block"
      const btn = document.getElementById("btnTodosInstructores")
      btn.classList.add("active", "btn-primary")
      btn.classList.remove("btn-outline-primary")
    } else if (seccion === "sin-materias") {
      document.getElementById("seccion-sin-materias").style.display = "block"
      const btn = document.getElementById("btnSinMaterias")
      btn.classList.add("active", "btn-primary")
      btn.classList.remove("btn-outline-primary")
      this.cargarInstructoresSinMaterias()
    }
  }

  // Cargar instructores sin materias
  async cargarInstructoresSinMaterias() {
    try {
      const response = await fetch("get_instructores_sin_materias.php")

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      const data = await response.json()

      const container = document.getElementById("instructoresSinMateriasContainer")
      if (data.success) {
        container.innerHTML = data.html
      } else {
        container.innerHTML = '<div class="alert alert-info">No hay instructores sin materias asignadas</div>'
      }
    } catch (error) {
      console.error("Error:", error)
      document.getElementById("instructoresSinMateriasContainer").innerHTML =
        '<div class="alert alert-danger">Error al cargar instructores sin materias</div>'
    }
  }

  // Cargar datos de instructores para filtrado
  cargarInstructoresData() {
    const instructorCards = document.querySelectorAll("#instructoresContainer .col-md-6")
    this.instructoresData = Array.from(instructorCards).map((card) => {
      const nombre = card.querySelector(".card-header h6").textContent.trim()
      const documento = card.querySelector(".card-text").textContent.match(/Documento:\s*(\d+)/)?.[1] || ""
      const correo = card.querySelector(".card-text").textContent.match(/Correo:\s*([^\n]+)/)?.[1] || ""
      const rol = card.querySelector(".badge").textContent.trim()
      return {
        element: card,
        nombre: nombre.toLowerCase(),
        documento: documento,
        correo: correo.toLowerCase(),
        rol: rol === "Normal" ? "3" : "5",
        visible: true,
      }
    })
  }

  // Filtrar instructores
  filtrarInstructores() {
    const busqueda = document.getElementById("buscarInstructor").value.toLowerCase()
    const filtroRol = document.getElementById("filtroRol").value

    this.instructoresData.forEach((instructor) => {
      const coincideBusqueda =
        !busqueda ||
        instructor.nombre.includes(busqueda) ||
        instructor.documento.includes(busqueda) ||
        instructor.correo.includes(busqueda)
      const coincideRol = !filtroRol || instructor.rol === filtroRol

      instructor.visible = coincideBusqueda && coincideRol
      instructor.element.style.display = instructor.visible ? "block" : "none"
    })

    this.paginaActual = 1
    this.actualizarPaginacion()
    this.mostrarPagina(this.paginaActual)
  }

  // Limpiar filtros
  limpiarFiltros() {
    document.getElementById("buscarInstructor").value = ""
    document.getElementById("filtroRol").value = ""
    this.filtrarInstructores()
  }

  // Mostrar página específica
  mostrarPagina(pagina) {
    const instructoresVisibles = this.instructoresData.filter((instructor) => instructor.visible)
    const totalPaginas = Math.ceil(instructoresVisibles.length / this.instructoresPorPagina)

    if (pagina < 1) pagina = 1
    if (pagina > totalPaginas) pagina = totalPaginas

    // Ocultar todos los instructores
    this.instructoresData.forEach((instructor) => {
      instructor.element.style.display = "none"
    })

    // Mostrar instructores de la página actual
    const inicio = (pagina - 1) * this.instructoresPorPagina
    const fin = inicio + this.instructoresPorPagina
    for (let i = inicio; i < fin && i < instructoresVisibles.length; i++) {
      instructoresVisibles[i].element.style.display = "block"
    }

    this.paginaActual = pagina
    this.actualizarPaginacion()
  }

  // Actualizar paginación
  actualizarPaginacion() {
    const instructoresVisibles = this.instructoresData.filter((instructor) => instructor.visible)
    const totalPaginas = Math.ceil(instructoresVisibles.length / this.instructoresPorPagina)

    let paginacion = document.querySelector(".pagination")
    if (!paginacion) {
      const nav = document.createElement("nav")
      nav.setAttribute("aria-label", "Paginación de instructores")
      nav.innerHTML = '<ul class="pagination justify-content-center"></ul>'
      document.querySelector("#instructoresContainer").parentNode.appendChild(nav)
      paginacion = nav.querySelector(".pagination")
    }

    paginacion.innerHTML = ""

    if (totalPaginas <= 1) return

    // Botón anterior
    if (this.paginaActual > 1) {
      paginacion.innerHTML += `
                <li class="page-item">
                    <button class="page-link" onclick="instructorManager.mostrarPagina(${this.paginaActual - 1})">
                        <i class="bi bi-chevron-left"></i> Anterior
                    </button>
                </li>
            `
    }

    // Números de página
    const inicioPag = Math.max(1, this.paginaActual - 2)
    const finPag = Math.min(totalPaginas, inicioPag + 4)

    for (let i = inicioPag; i <= finPag; i++) {
      paginacion.innerHTML += `
                <li class="page-item ${this.paginaActual === i ? "active" : ""}">
                    <button class="page-link" onclick="instructorManager.mostrarPagina(${i})">${i}</button>
                </li>
            `
    }

    // Botón siguiente
    if (this.paginaActual < totalPaginas) {
      paginacion.innerHTML += `
                <li class="page-item">
                    <button class="page-link" onclick="instructorManager.mostrarPagina(${this.paginaActual + 1})">
                        Siguiente <i class="bi bi-chevron-right"></i>
                    </button>
                </li>
            `
    }
  }

  // Cargar detalles del instructor
  async cargarDetallesInstructor(idInstructor, paginaFichas = 1) {
    try {
      const response = await fetch(
        `get_instructor_details.php?id_instructor=${encodeURIComponent(idInstructor)}&pagina_fichas=${paginaFichas}`,
      )

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      const data = await response.json()

      if (data.success) {
        document.getElementById("detallesInstructorContent").innerHTML = data.html
        const modalEl = document.getElementById("detallesInstructorModal")
        const modal = new bootstrap.Modal(modalEl)
        modal.show()
      } else {
        alert("Error al cargar los detalles del instructor: " + data.message)
      }
    } catch (error) {
      console.error("Error:", error)
      alert("Error al cargar los detalles del instructor")
    }
  }

  // Cargar formulario de materias
  async cargarFormularioMaterias(idInstructor, nombre) {
    try {
      const response = await fetch(`get_materias.php?id_instructor=${encodeURIComponent(idInstructor)}`)

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      const data = await response.json()

      if (data.success) {
        document.getElementById("asignarMateriasContent").innerHTML = data.html
        document.getElementById("asignarMateriasModalLabel").textContent = `Asignar Materias - ${nombre}`

        const modalEl = document.getElementById("asignarMateriasModal")
        const modal = new bootstrap.Modal(modalEl)
        modal.show()
      } else {
        alert("Error al cargar el formulario de materias: " + (data.message || "Error desconocido"))
      }
    } catch (error) {
      console.error("Error:", error)
      alert("Error al cargar el formulario de materias")
    }
  }

  // Mostrar modal de edición
  mostrarModalEditar(button) {
    const idInstructor = button.getAttribute("data-id")
    const nombre = button.getAttribute("data-nombre")
    const correo = button.getAttribute("data-correo")
    const telefono = button.getAttribute("data-telefono")

    document.getElementById("edit_id_instructor").value = idInstructor
    document.getElementById("edit_instructor_nombre").textContent = nombre
    document.getElementById("edit_correo").value = correo
    document.getElementById("edit_telefono").value = telefono || ""

    const modalEl = document.getElementById("editarInstructorModal")
    const modal = new bootstrap.Modal(modalEl)
    modal.show()
  }

  // Cambiar página de fichas en detalles
  cambiarPaginaFichas(idInstructor, pagina) {
    this.cargarDetallesInstructor(idInstructor, pagina)
  }
}

// Crear instancia global
const instructorManager = new InstructorManager()

// Funciones globales para compatibilidad
function mostrarSeccion(seccion) {
  instructorManager.mostrarSeccion(seccion)
}

function limpiarFiltros() {
  instructorManager.limpiarFiltros()
}

function cambiarPaginaFichas(idInstructor, pagina) {
  instructorManager.cambiarPaginaFichas(idInstructor, pagina)
}

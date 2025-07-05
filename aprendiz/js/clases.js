document.addEventListener("DOMContentLoaded", () => {
  const ITEMS_POR_PAGINA = 3
  let clasesTotales = []
  let clasesFiltradas = []
  let paginaActual = 1
  let trimestreActual = null

  const contenedor = document.getElementById("contenedor-clases")
  const paginacion = document.createElement("div")
  paginacion.className = "d-flex justify-content-center mt-4"
  contenedor.parentNode.appendChild(paginacion)

  const formBusqueda = document.querySelector("form[role='search']")
  const inputBusqueda = document.getElementById("input-busqueda")

  // Crear indicador de trimestre actual
  function crearIndicadorTrimestre() {
    if (trimestreActual) {
      const indicador = document.createElement("div")
      indicador.className = "alert alert-info mb-3"
      indicador.innerHTML = `
        <i class="fas fa-calendar-alt me-2"></i>
        <strong>Trimestre Actual:</strong> ${trimestreActual.trimestre} Trimestre
        <small class="ms-2 text-muted">(${new Date().toLocaleDateString()})</small>
      `
      contenedor.parentNode.insertBefore(indicador, contenedor)
    }
  }

  // Crear leyenda de estados
  function crearLeyendaEstados() {
    const leyenda = document.createElement("div")
    leyenda.className = "card mb-3"
    leyenda.innerHTML = `
      <div class="card-body py-2">
        <div class="row text-center">
          <div class="col-md-4">
            <span class="badge badge-actual me-1">●</span>
            <small>Trimestre Actual</small>
          </div>
          <div class="col-md-4">
            <span class="badge badge-recuperacion me-1">●</span>
            <small>Pendiente de Recuperación</small>
          </div>
          <div class="col-md-4">
            <span class="badge bg-danger me-1">●</span>
            <small>Reprobada</small>
          </div>
        </div>
      </div>
    `
    contenedor.parentNode.insertBefore(leyenda, contenedor)
  }

  function obtenerClaseEstilo(materia) {
    const baseClass = "card card-clase h-100 shadow-sm"

    switch (materia.estado_visualizacion) {
      case "actual":
        return `${baseClass} border-primary`
      case "sin_nota":
        return `${baseClass} border-warning`
      case "reprobada":
        return `${baseClass} border-danger`
      default:
        return baseClass
    }
  }

  function obtenerBadgeEstado(materia) {
    switch (materia.estado_visualizacion) {
      case "actual":
        return '<span class="badge badge-actual mb-2">Trimestre Actual</span>'
      case "sin_nota":
        return '<span class="badge badge-recuperacion mb-2">Pendiente de Recuperación</span>'
      case "reprobada":
        return `<span class="badge bg-danger mb-2">Reprobada (Nota: ${materia.nota_definitiva?.toFixed(1) || "N/A"})</span>`
      default:
        return ""
    }
  }

  function obtenerMensajeEstado(materia) {
    switch (materia.estado_visualizacion) {
      case "sin_nota":
        return '<p class="card-text text-recuperacion"><small><i class="fas fa-exclamation-triangle me-1"></i>Debe completar actividades pendientes</small></p>'
      case "reprobada":
        return '<p class="card-text text-danger"><small><i class="fas fa-redo me-1"></i>Requiere recuperación para aprobar</small></p>'
      default:
        return ""
    }
  }

  function renderizarPagina(pagina) {
    contenedor.innerHTML = ""

    const inicio = (pagina - 1) * ITEMS_POR_PAGINA
    const fin = inicio + ITEMS_POR_PAGINA
    const clasesPagina = clasesFiltradas.slice(inicio, fin)

    if (clasesPagina.length === 0) {
      contenedor.innerHTML = `
        <div class="col-12">
          <div class="alert alert-info text-center">
            <i class="fas fa-info-circle me-2"></i>
            No se encontraron materias que cumplan con los criterios de visualización.
          </div>
        </div>
      `
      paginacion.innerHTML = ""
      return
    }

    clasesPagina.forEach((materia) => {
      const col = document.createElement("div")
      col.className = "col-md-4 mb-4"

      const card = document.createElement("div")
      card.className = obtenerClaseEstilo(materia)

      card.innerHTML = `
        <img src="${materia.imagen}" class="card-img-top" alt="Imagen de ${materia.nombre_clase}">
        <div class="card-body">
          ${obtenerBadgeEstado(materia)}
          <h5 class="card-title">${materia.nombre_clase}</h5>
          <p class="card-text"><strong>Instructor:</strong> ${materia.nombre_profesor}</p>
          <p class="card-text"><strong>Ficha:</strong> ${materia.numero_fichas}</p>
          <p class="card-text"><strong>Trimestre:</strong> ${materia.nombre_trimestre}</p>
          ${obtenerMensajeEstado(materia)}
        </div>
        <div class="card-footer bg-transparent border-top-0">
          <a href="../../aprendiz/clase/index.php?id_clase=${materia.id_clase}" 
             class="btn ${materia.es_reprobada ? "btn-recuperar" : "btn-blue-dark"} w-100">
            ${materia.es_reprobada ? "Recuperar Materia" : "Ingresar a Clase"}
          </a>
        </div>
      `

      col.appendChild(card)
      contenedor.appendChild(col)
    })

    renderizarControles(clasesFiltradas.length)
  }

  function renderizarControles(totalItems) {
    const totalPaginas = Math.ceil(totalItems / ITEMS_POR_PAGINA)
    paginacion.innerHTML = ""

    if (totalPaginas <= 1) return

    for (let i = 1; i <= totalPaginas; i++) {
      const btn = document.createElement("button")
      btn.textContent = i
      btn.className = `btn mx-1 ${i === paginaActual ? "btn-blue-dark" : "btn-outline-secondary"}`
      btn.addEventListener("click", () => {
        paginaActual = i
        renderizarPagina(paginaActual)
      })
      paginacion.appendChild(btn)
    }
  }

  function filtrarClases() {
    const texto = inputBusqueda.value.trim().toLowerCase()

    if (texto === "") {
      clasesFiltradas = clasesTotales.slice()
    } else {
      clasesFiltradas = clasesTotales.filter(
        (materia) =>
          materia.nombre_clase.toLowerCase().includes(texto) ||
          materia.nombre_profesor.toLowerCase().includes(texto) ||
          materia.numero_fichas.toString().toLowerCase().includes(texto) ||
          materia.nombre_trimestre.toLowerCase().includes(texto),
      )
    }

    paginaActual = 1
    renderizarPagina(paginaActual)
  }

  // Cargar datos desde la API
  fetch("../api/clases.php")
    .then((res) => res.json())
    .then((data) => {
      if (data.error) {
        contenedor.innerHTML = `
          <div class="col-12">
            <div class="alert alert-danger">
              <i class="fas fa-exclamation-triangle me-2"></i>
              Error: ${data.error}
            </div>
          </div>
        `
        return
      }

      clasesTotales = data.materias || []
      clasesFiltradas = clasesTotales.slice()
      trimestreActual = data.trimestre_actual

      // Crear elementos informativos
      crearIndicadorTrimestre()
      crearLeyendaEstados()

      if (clasesTotales.length === 0) {
        contenedor.innerHTML = `
          <div class="col-12">
            <div class="alert alert-warning text-center">
              <i class="fas fa-graduation-cap me-2"></i>
              No tienes materias asignadas para el trimestre actual o pendientes de recuperación.
            </div>
          </div>
        `
        return
      }

      renderizarPagina(paginaActual)

      // Mostrar estadísticas
      const stats = {
        actual: clasesTotales.filter((m) => m.estado_visualizacion === "actual").length,
        sin_nota: clasesTotales.filter((m) => m.estado_visualizacion === "sin_nota").length,
        reprobada: clasesTotales.filter((m) => m.estado_visualizacion === "reprobada").length,
      }

      console.log("Estadísticas de materias:", stats)
    })
    .catch((err) => {
      console.error("Error al cargar materias:", err)
      contenedor.innerHTML = `
        <div class="col-12">
          <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Error al cargar las materias. Por favor, intenta nuevamente.
          </div>
        </div>
      `
    })

  // Event listeners
  formBusqueda.addEventListener("submit", (e) => {
    e.preventDefault()
    filtrarClases()
  })

  inputBusqueda.addEventListener("input", filtrarClases)
})

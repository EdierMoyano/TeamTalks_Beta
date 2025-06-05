// Variables globales para paginación
let materiasCurrentPage = 1
let aprendicesCurrentPage = 1
let horariosCurrentPage = 1
const itemsPerPage = 6

// Función para mostrar página de materias
function mostrarPaginaMaterias(page) {
  const rows = document.querySelectorAll(".materia-row")
  const totalPages = Math.ceil(rows.length / itemsPerPage)

  if (page < 1) page = 1
  if (page > totalPages) page = totalPages

  const start = (page - 1) * itemsPerPage
  const end = start + itemsPerPage

  rows.forEach((row, index) => {
    if (index >= start && index < end) {
      row.style.display = ""
    } else {
      row.style.display = "none"
    }
  })

  // Actualizar info
  const info = document.getElementById("materias-info")
  if (info) {
    const showing = Math.min(end, rows.length)
    info.textContent = `Mostrando ${start + 1}-${showing} de ${rows.length} registros`
  }

  // Actualizar botones
  actualizarBotonesPaginacion("materias", page, totalPages)
  materiasCurrentPage = page
}

// Función para mostrar página de aprendices
function mostrarPaginaAprendices(page) {
  const rows = document.querySelectorAll(".aprendiz-row")
  const totalPages = Math.ceil(rows.length / itemsPerPage)

  if (page < 1) page = 1
  if (page > totalPages) page = totalPages

  const start = (page - 1) * itemsPerPage
  const end = start + itemsPerPage

  rows.forEach((row, index) => {
    if (index >= start && index < end) {
      row.style.display = ""
    } else {
      row.style.display = "none"
    }
  })

  // Actualizar info
  const info = document.getElementById("aprendices-info")
  if (info) {
    const showing = Math.min(end, rows.length)
    info.textContent = `Mostrando ${start + 1}-${showing} de ${rows.length} registros`
  }

  // Actualizar botones
  actualizarBotonesPaginacion("aprendices", page, totalPages)
  aprendicesCurrentPage = page
}

// Función para mostrar página de horarios
function mostrarPaginaHorarios(page) {
  const rows = document.querySelectorAll(".horario-row")
  const totalPages = Math.ceil(rows.length / itemsPerPage)

  if (page < 1) page = 1
  if (page > totalPages) page = totalPages

  const start = (page - 1) * itemsPerPage
  const end = start + itemsPerPage

  rows.forEach((row, index) => {
    if (index >= start && index < end) {
      row.style.display = ""
    } else {
      row.style.display = "none"
    }
  })

  // Actualizar info
  const info = document.getElementById("horarios-info")
  if (info) {
    const showing = Math.min(end, rows.length)
    info.textContent = `Mostrando ${start + 1}-${showing} de ${rows.length} registros`
  }

  // Actualizar botones
  actualizarBotonesPaginacion("horarios", page, totalPages)
  horariosCurrentPage = page
}

// Función para actualizar botones de paginación
function actualizarBotonesPaginacion(tipo, currentPage, totalPages) {
  const pagination = document.getElementById(tipo + "-pagination")
  if (!pagination) return

  // Limpiar paginación existente
  pagination.innerHTML = ""

  // Botón anterior
  const prevItem = document.createElement("li")
  prevItem.className = `page-item ${currentPage === 1 ? "disabled" : ""}`
  prevItem.innerHTML = `<button class="page-link" onclick="cambiarPagina${tipo.charAt(0).toUpperCase() + tipo.slice(1)}(${currentPage - 1})">Anterior</button>`
  pagination.appendChild(prevItem)

  // Números de página
  for (let i = 1; i <= totalPages; i++) {
    if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
      const pageItem = document.createElement("li")
      pageItem.className = `page-item ${i === currentPage ? "active" : ""}`
      pageItem.innerHTML = `<button class="page-link" onclick="cambiarPagina${tipo.charAt(0).toUpperCase() + tipo.slice(1)}(${i})">${i}</button>`
      pagination.appendChild(pageItem)
    } else if (i === currentPage - 2 || i === currentPage + 2) {
      const dotsItem = document.createElement("li")
      dotsItem.className = "page-item disabled"
      dotsItem.innerHTML = '<span class="page-link">...</span>'
      pagination.appendChild(dotsItem)
    }
  }

  // Botón siguiente
  const nextItem = document.createElement("li")
  nextItem.className = `page-item ${currentPage === totalPages ? "disabled" : ""}`
  nextItem.innerHTML = `<button class="page-link" onclick="cambiarPagina${tipo.charAt(0).toUpperCase() + tipo.slice(1)}(${currentPage + 1})">Siguiente</button>`
  pagination.appendChild(nextItem)
}

// Funciones para cambiar página (llamadas desde los botones)
function cambiarPaginaMaterias(page) {
  mostrarPaginaMaterias(page)
}

function cambiarPaginaAprendices(page) {
  mostrarPaginaAprendices(page)
}

function cambiarPaginaHorarios(page) {
  mostrarPaginaHorarios(page)
}

// Función para inicializar paginación cuando se carga el modal
function inicializarPaginacionModal() {
  // Resetear páginas actuales
  materiasCurrentPage = 1
  aprendicesCurrentPage = 1
  horariosCurrentPage = 1

  // Inicializar cada tabla si tiene más de 6 elementos
  const materiasRows = document.querySelectorAll(".materia-row")
  if (materiasRows.length > 6) {
    mostrarPaginaMaterias(1)
  }

  const aprendicesRows = document.querySelectorAll(".aprendiz-row")
  if (aprendicesRows.length > 6) {
    mostrarPaginaAprendices(1)
  }

  const horariosRows = document.querySelectorAll(".horario-row")
  if (horariosRows.length > 6) {
    mostrarPaginaHorarios(1)
  }
}

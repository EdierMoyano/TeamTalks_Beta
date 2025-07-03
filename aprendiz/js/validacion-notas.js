/**
 * Sistema de validación visual para notas de actividades
 */
document.addEventListener("DOMContentLoaded", () => {
  console.log("🎨 Sistema de validación visual de notas iniciado")

  // Aplicar validación visual a todas las actividades completadas
  aplicarValidacionVisualNotas()

  // Observar cambios dinámicos en el DOM
  observarCambiosActividades()
})

/**
 * Aplica la validación visual basada en las notas
 */
function aplicarValidacionVisualNotas() {
  const actividadesEntregadas = document.querySelectorAll(".actividad-entregada")

  actividadesEntregadas.forEach((actividad) => {
    const notaElement = actividad.querySelector(".nota-valor")

    if (notaElement) {
      // Extraer la nota del texto
      const notaTexto = notaElement.textContent || notaElement.innerText
      const notaMatch = notaTexto.match(/(\d+\.?\d*)/)

      if (notaMatch) {
        const nota = Number.parseFloat(notaMatch[1])
        const claseNota = determinarClaseNota(nota)

        // Remover clases anteriores
        actividad.classList.remove("nota-roja", "nota-amarilla", "nota-verde", "sin-nota")

        // Aplicar nueva clase
        actividad.classList.add(claseNota)

        // Agregar indicador visual si no existe
        agregarIndicadorVisual(notaElement, nota)

        console.log(`✅ Nota ${nota} aplicada con clase: ${claseNota}`)
      }
    } else {
      // Sin nota - aplicar clase por defecto
      actividad.classList.remove("nota-roja", "nota-amarilla", "nota-verde")
      actividad.classList.add("sin-nota")
    }
  })
}

/**
 * Determina la clase CSS según la nota
 * @param {number} nota - La nota numérica
 * @returns {string} - La clase CSS correspondiente
 */
function determinarClaseNota(nota) {
  if (nota >= 1.0 && nota <= 2.9) {
    return "nota-roja"
  } else if (nota >= 3.0 && nota <= 3.9) {
    return "nota-amarilla"
  } else if (nota >= 4.0 && nota <= 5.0) {
    return "nota-verde"
  }
  return "sin-nota"
}

/**
 * Agrega un indicador visual de color junto a la nota
 * @param {Element} notaElement - El elemento que contiene la nota
 * @param {number} nota - La nota numérica
 */
function agregarIndicadorVisual(notaElement, nota) {
  // Verificar si ya existe un indicador
  if (notaElement.querySelector(".nota-indicador")) {
    return
  }

  const indicador = document.createElement("span")
  indicador.className = "nota-indicador"

  if (nota >= 1.0 && nota <= 2.9) {
    indicador.classList.add("roja")
  } else if (nota >= 3.0 && nota <= 3.9) {
    indicador.classList.add("amarilla")
  } else if (nota >= 4.0 && nota <= 5.0) {
    indicador.classList.add("verde")
  } else {
    indicador.classList.add("sin-nota")
  }

  // Insertar el indicador al inicio del elemento de nota
  notaElement.insertBefore(indicador, notaElement.firstChild)
}

/**
 * Observa cambios dinámicos en las actividades para aplicar validación
 */
function observarCambiosActividades() {
  const observer = new MutationObserver((mutations) => {
    let shouldReapply = false

    mutations.forEach((mutation) => {
      if (mutation.type === "childList") {
        mutation.addedNodes.forEach((node) => {
          if (
            node.nodeType === 1 &&
            (node.classList.contains("actividad-entregada") || node.querySelector(".actividad-entregada"))
          ) {
            shouldReapply = true
          }
        })
      }
    })

    if (shouldReapply) {
      setTimeout(aplicarValidacionVisualNotas, 100)
    }
  })

  // Observar cambios en el contenedor principal
  const contenedor = document.querySelector(".tab-content") || document.body
  observer.observe(contenedor, {
    childList: true,
    subtree: true,
  })
}

/**
 * Función auxiliar para debugging - mostrar estadísticas de notas
 */
function mostrarEstadisticasNotas() {
  const actividades = document.querySelectorAll(".actividad-entregada")
  const estadisticas = {
    total: actividades.length,
    rojas: document.querySelectorAll(".actividad-entregada.nota-roja").length,
    amarillas: document.querySelectorAll(".actividad-entregada.nota-amarilla").length,
    verdes: document.querySelectorAll(".actividad-entregada.nota-verde").length,
    sinNota: document.querySelectorAll(".actividad-entregada.sin-nota").length,
  }

  console.log("📊 Estadísticas de validación visual:", estadisticas)
  return estadisticas
}

// Exponer función para debugging
window.mostrarEstadisticasNotas = mostrarEstadisticasNotas

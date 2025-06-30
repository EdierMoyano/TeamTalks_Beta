// JavaScript COMPLETAMENTE CORREGIDO para el manejo de múltiples archivos

document.addEventListener("DOMContentLoaded", () => {
  const fileInput = document.getElementById("archivos")
  const previewContainer = document.getElementById("preview-archivos")
  const submitBtn = document.getElementById("btn-entregar")
  const cancelBtn = document.getElementById("btn-cancelar")
  const fileUploadArea = document.getElementById("file-upload-area")

  let archivosSeleccionados = []
  const maxArchivos = 3

  console.log("Sistema de archivos múltiples simple iniciado")

  if (fileInput) {
    fileInput.addEventListener("change", function (e) {
      const files = Array.from(e.target.files)
      console.log("Archivos seleccionados:", files.length)

      if (files.length === 0) {
        archivosSeleccionados = []
        mostrarPreview()
        return
      }

      if (files.length > maxArchivos) {
        alert(`Solo se permiten máximo ${maxArchivos} archivos. Has seleccionado ${files.length}.`)
        this.value = ""
        archivosSeleccionados = []
        mostrarPreview()
        return
      }

      // Validar cada archivo
      let todosValidos = true
      for (const file of files) {
        if (file.size > 10 * 1024 * 1024) {
          alert(`El archivo "${file.name}" excede el tamaño máximo de 10MB`)
          todosValidos = false
          break
        }

        const extension = file.name.split(".").pop().toLowerCase()
        const extensionesPermitidas = ["pdf", "doc", "docx", "txt", "jpg", "jpeg", "png", "gif", "zip", "rar"]

        if (!extensionesPermitidas.includes(extension)) {
          alert(`El archivo "${file.name}" tiene una extensión no permitida`)
          todosValidos = false
          break
        }
      }

      if (!todosValidos) {
        this.value = ""
        archivosSeleccionados = []
        mostrarPreview()
        return
      }

      archivosSeleccionados = files
      console.log("Archivos válidos guardados:", archivosSeleccionados.length)
      mostrarPreview()
    })
  }

  function mostrarPreview() {
    if (!previewContainer) return

    previewContainer.innerHTML = ""

    if (archivosSeleccionados.length === 0) {
      if (submitBtn) submitBtn.style.display = "none"
      return
    }

    archivosSeleccionados.forEach((file, index) => {
      const fileDiv = document.createElement("div")
      fileDiv.className =
        "file-preview-item d-flex justify-content-between align-items-center p-3 mb-2 border rounded bg-light"

      const extension = file.name.split(".").pop().toLowerCase()
      let iconClass = "fas fa-file text-secondary"
      let iconColor = "#6c757d"

      if (["jpg", "jpeg", "png", "gif"].includes(extension)) {
        iconClass = "fas fa-file-image"
        iconColor = "#28a745"
      } else if (["pdf"].includes(extension)) {
        iconClass = "fas fa-file-pdf"
        iconColor = "#dc3545"
      } else if (["doc", "docx"].includes(extension)) {
        iconClass = "fas fa-file-word"
        iconColor = "#0066cc"
      } else if (["zip", "rar"].includes(extension)) {
        iconClass = "fas fa-file-archive"
        iconColor = "#ffc107"
      }

      fileDiv.innerHTML = `
        <div class="d-flex align-items-center flex-grow-1">
          <i class="${iconClass}" style="font-size: 2rem; color: ${iconColor}; margin-right: 15px;"></i>
          <div>
            <div class="fw-bold text-dark mb-1">${file.name}</div>
            <small class="text-muted">${formatFileSize(file.size)}</small>
          </div>
        </div>
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="eliminarArchivo(${index})" title="Eliminar archivo">
          <i class="fas fa-times"></i>
        </button>
      `

      previewContainer.appendChild(fileDiv)
    })

    if (submitBtn) {
      submitBtn.style.display = "inline-block"
      submitBtn.innerHTML = `<i class="fas fa-paper-plane"></i> Entregar Actividad (${archivosSeleccionados.length} archivo${archivosSeleccionados.length > 1 ? "s" : ""})`
    }
  }

  window.eliminarArchivo = (index) => {
    console.log(`Eliminando archivo en índice ${index}`)

    if (index >= 0 && index < archivosSeleccionados.length) {
      archivosSeleccionados.splice(index, 1)

      // Actualizar el input file
      const dt = new DataTransfer()
      archivosSeleccionados.forEach((file) => dt.items.add(file))
      fileInput.files = dt.files

      mostrarPreview()
    }
  }

  // Manejar envío
  if (submitBtn) {
    submitBtn.addEventListener("click", (e) => {
      e.preventDefault()

      if (archivosSeleccionados.length === 0) {
        alert("Debes seleccionar al menos un archivo")
        return
      }

      console.log("Iniciando entrega con", archivosSeleccionados.length, "archivos")
      entregarActividad()
    })
  }

  // Manejar cancelación
  if (cancelBtn) {
    cancelBtn.addEventListener("click", (e) => {
      e.preventDefault()
      if (confirm("¿Estás seguro de que quieres cancelar la entrega?")) {
        cancelarEntrega()
      }
    })
  }

  function entregarActividad() {
    const formData = new FormData()

    const idActividad =
      document.querySelector('input[name="id_actividad"]')?.value ||
      document.querySelector("[data-id-actividad]")?.getAttribute("data-id-actividad")
    const idUsuario =
      document.querySelector('input[name="id_usuario"]')?.value ||
      document.querySelector("[data-id-usuario]")?.getAttribute("data-id-usuario")
    const contenido = document.querySelector('textarea[name="contenido"]')?.value || ""

    if (!idActividad || !idUsuario) {
      alert("Error: No se pudieron obtener los datos de la actividad")
      return
    }

    formData.append("id_actividad", idActividad)
    formData.append("id_usuario", idUsuario)
    formData.append("contenido", contenido)

    // Agregar cada archivo
    archivosSeleccionados.forEach((file, index) => {
      console.log(`📎 Agregando archivo ${index + 1}: ${file.name} (${formatFileSize(file.size)})`)
      formData.append("archivos[]", file)
    })

    // Mostrar loading
    submitBtn.disabled = true
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Entregando...'

    fetch("entregar_actividad.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        console.log("Respuesta del servidor:", data)
        if (data.success) {
          alert(`Actividad entregada exitosamente!\nArchivos subidos: ${archivosSeleccionados.length}`)
          location.reload()
        } else {
          alert("Error: " + data.message)
        }
      })
      .catch((error) => {
        console.error("Error:", error)
        alert("Error al entregar la actividad")
      })
      .finally(() => {
        submitBtn.disabled = false
        submitBtn.innerHTML = `<i class="fas fa-paper-plane"></i> Entregar Actividad (${archivosSeleccionados.length} archivo${archivosSeleccionados.length > 1 ? "s" : ""})`
      })
  }

  function cancelarEntrega() {
    const idActividad =
      document.querySelector('input[name="id_actividad"]')?.value ||
      document.querySelector("[data-id-actividad]")?.getAttribute("data-id-actividad") ||
      new URLSearchParams(window.location.search).get("id")

    const idUsuario =
      document.querySelector('input[name="id_usuario"]')?.value ||
      document.querySelector("[data-id-usuario]")?.getAttribute("data-id-usuario")

    if (!idActividad || !idUsuario) {
      alert("Error: No se pudieron obtener los datos de la actividad")
      return
    }

    const formData = new FormData()
    formData.append("id_actividad", idActividad)
    formData.append("id_usuario", idUsuario)

    cancelBtn.disabled = true
    cancelBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cancelando...'

    fetch("cancelar_entrega.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          alert("Entrega cancelada exitosamente")
          location.reload()
        } else {
          alert("Error: " + data.message)
        }
      })
      .catch((error) => {
        console.error("Error:", error)
        alert("Error al cancelar la entrega")
      })
      .finally(() => {
        cancelBtn.disabled = false
        cancelBtn.innerHTML = '<i class="fas fa-times"></i> Cancelar entrega'
      })
  }

  function formatFileSize(bytes) {
    if (bytes === 0) return "0 Bytes"
    const k = 1024
    const sizes = ["Bytes", "KB", "MB", "GB"]
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    return Number.parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i]
  }

  window.volverAClase = () => {
    const idClase = document.querySelector("[data-id-clase]")?.getAttribute("data-id-clase")
    if (idClase) {
      window.location.href = `index.php?id_clase=${idClase}`
    } else {
      window.location.href = "../index.php"
    }
  }
})

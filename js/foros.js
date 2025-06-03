document.addEventListener('DOMContentLoaded', () => {
  const contenedor = document.getElementById('contenedor-foros');

  fetch('api/foros_cla.php')
    .then(response => response.json())
    .then(data => {
      if (data.error) {
        contenedor.innerHTML = `<p class="text-danger text-center">${data.error}</p>`;
        return;
      }

      data.forEach((foro, iF) => {
        const foroDiv = document.createElement('div');
        foroDiv.className = 'card card-clase mb-4 shadow';

        foroDiv.innerHTML = `
          <img src="${foro.imagen}" class="card-img-top" alt="Imagen clase">
          <div class="card-body">
            <h5 class="card-title">Clase: ${foro.nombre_clase}</h5>
            <p><strong>Profesor:</strong> ${foro.nombre_profesor}</p>
            <p><strong>Ficha:</strong> ${foro.numero_fichas}</p>
            <p><strong>Fecha del foro:</strong> ${foro.fecha_foro}</p>
            <hr>
        `;

        foro.temas.forEach((tema, iT) => {
          const collapseId = `respuestas_${iF}_${iT}`;
          let temaHTML = `
            <div class="bg-light p-3 rounded mb-3">
              <h6 class="text-dark mb-1"><i class="bi bi-chat-left-text"></i> ${tema.titulo}</h6>
              <p>${tema.descripcion}</p>
              <p class="text-muted"><small>Creado por <strong>${tema.creador}</strong> el ${tema.fecha_creacion}</small></p>
          `;

          if (tema.respuestas.length > 0) {
            temaHTML += `
              <button class="btn btn-sm btn-outline-dark mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#${collapseId}">
                Ver respuestas (${tema.respuestas.length})
              </button>
              <div class="collapse" id="${collapseId}">
                <div class="ps-3 border-start border-2 border-primary mt-2">
            `;

            tema.respuestas.forEach(resp => {
              temaHTML += `
                <div class="mb-2">
                  <p class="mb-0">${resp.descripcion}</p>
                  <p class="text-muted mb-0"><small>Respuesta de <strong>${resp.respondido_por}</strong> el ${resp.fecha_respuesta}</small></p>
                </div>
              `;
            });

            temaHTML += `</div></div>`;
          } else {
            temaHTML += `<p class="text-secondary"><em>No hay respuestas aún.</em></p>`;
          }

          // Formulario para responder
          temaHTML += `
  <form onsubmit="enviarRespuesta(event, ${tema.id_tema_foro})" class="mt-2">
    <div class="mb-2">
      <textarea required name="respuesta" class="form-control" placeholder="Escribe tu respuesta..." onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();this.form.requestSubmit();}"></textarea>
    </div>
    <button type="submit" class="btn btn-dark">Responder</button>
  </form>
</div>`;

          foroDiv.innerHTML += temaHTML;
        });

        foroDiv.innerHTML += `</div>`;
        contenedor.appendChild(foroDiv);
      });
    })
    .catch(error => {
      console.error('Error al obtener los foros:', error);
      contenedor.innerHTML = `<p class="text-danger text-center">Ocurrió un error al cargar los foros.</p>`;
    });
});

// Función para enviar la respuesta
function enviarRespuesta(e, idTema) {
  e.preventDefault();
  const form = e.target;
  const respuesta = form.respuesta.value;

  fetch('api/guardar_respuesta.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      id_tema_foro: idTema,
      descripcion: respuesta,
      id_user: window.ID_USER // <-- aquí usas el id del usuario logueado
    })
  })
    .then(res => res.json())
    .then(result => {
      if (result.success) {
        alert('Respuesta guardada correctamente');
        location.reload();
      } else {
        alert('Error: ' + result.error);
      }
    });
}
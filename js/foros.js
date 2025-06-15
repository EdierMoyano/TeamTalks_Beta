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
        const col = document.createElement('div');
        col.className = 'col-12 col-md-8 offset-md-2 mb-4';

        const card = document.createElement('div');
        card.className = 'card p-3 foro-card shadow-sm';

        card.innerHTML = `
          <div class="foro-header d-flex justify-content-between align-items-center" role="button" tabindex="0" aria-expanded="false" style="cursor:pointer; user-select:none;">
            <div>
              <h5 class="mb-1" style="color:#0E4A86;">${foro.nombre_clase}</h5>
              <small><strong>Instructor:</strong> ${foro.nombre_profesor} | <strong>Ficha:</strong> ${foro.numero_fichas} | <strong>Fecha foro:</strong> ${foro.fecha_foro}</small>
            </div>
            <div>
              <svg class="flecha" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M6 12l4-4-4-4v8z"/>
              </svg>
            </div>
          </div>
          <div class="contenido-desplegable mt-3" style="display:none; height:0;">
            <button class="btn btn-blue-dark">Ingresar al foro</button>
          </div>
        `;

        const header = card.querySelector('.foro-header');
        const flecha = card.querySelector('.flecha');
        const contenido = card.querySelector('.contenido-desplegable');
        const btnIngresar = contenido.querySelector('button');

        header.addEventListener('click', () => {
          const expanded = header.getAttribute('aria-expanded') === 'true';
          header.setAttribute('aria-expanded', !expanded);

          if (!expanded) {
            // Abrir con animación suave
            contenido.style.display = 'block'; 
            contenido.style.height = '0px'; // reset antes de animar
            const height = contenido.scrollHeight + 'px';
            setTimeout(() => {
              contenido.style.height = height;
            }, 10);

            contenido.addEventListener('transitionend', function handler() {
              contenido.style.height = 'auto';
              contenido.removeEventListener('transitionend', handler);
            });
          } else {
            // Cerrar con animación suave
            const height = contenido.scrollHeight;
            contenido.style.height = height + 'px'; 
            setTimeout(() => {
              contenido.style.height = '0px';
            }, 10);

            contenido.addEventListener('transitionend', function handler() {
              contenido.style.display = 'none';
              contenido.removeEventListener('transitionend', handler);
            });
          }

          flecha.classList.toggle('rotated', !expanded);
        });

        header.addEventListener('keydown', e => {
          if(e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            header.click();
          }
        });

        btnIngresar.addEventListener('click', () => {
          window.location.href = `respuesta_foro.php?id=${foro.id_foro}`;
        });

        col.appendChild(card);
        contenedor.appendChild(col);
      });
    })
    .catch(err => {
      console.error('Error al obtener los foros:', err);
      contenedor.innerHTML = `<p class="text-danger text-center">Ocurrió un error al cargar los foros.</p>`;
    });
});

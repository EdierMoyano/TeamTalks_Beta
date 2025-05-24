document.addEventListener("DOMContentLoaded", function () {
  const ITEMS_POR_PAGINA = 6;
  let clasesTotales = [];
  let clasesFiltradas = [];
  let paginaActual = 1;

  const contenedor = document.getElementById("contenedor-clases");
  const paginacion = document.createElement("div");
  paginacion.className = "d-flex justify-content-center mt-4";
  contenedor.parentNode.appendChild(paginacion);

  const formBusqueda = document.querySelector("form[role='search']");
  const inputBusqueda = formBusqueda.querySelector("input[type='search']");

  function renderizarPagina(pagina) {
    contenedor.innerHTML = "";

    const inicio = (pagina - 1) * ITEMS_POR_PAGINA;
    const fin = inicio + ITEMS_POR_PAGINA;
    const clasesPagina = clasesFiltradas.slice(inicio, fin);

    if (clasesPagina.length === 0) {
      contenedor.innerHTML = "<p>No se encontraron clases.</p>";
      paginacion.innerHTML = "";
      return;
    }

    clasesPagina.forEach(clase => {
      const col = document.createElement("div");
      col.className = "col-md-4 mb-4";

      const card = document.createElement("div");
      card.className = "card card-clase h-100 shadow-sm";

      card.innerHTML = `
        <img src="${clase.imagen}" class="card-img-top" alt="Imagen de ${clase.nombre_clase}">
        <div class="card-body">
          <h5 class="card-title">${clase.nombre_clase}</h5>
          <p class="card-text"><strong>Profesor:</strong> ${clase.nombre_profesor}</p>
          <p class="card-text"><strong>Número de Ficha:</strong> ${clase.numero_fichas}</p>
        </div>
        <div class="card-footer bg-transparent border-top-0">
          <a href="#" class="btn btn-blue-dark w-100">Ingresar a Clase</a>
        </div>
      `;

      col.appendChild(card);
      contenedor.appendChild(col);
    });

    renderizarControles(clasesFiltradas.length);
  }

  function renderizarControles(totalItems) {
    const totalPaginas = Math.ceil(totalItems / ITEMS_POR_PAGINA);
    paginacion.innerHTML = "";

    for (let i = 1; i <= totalPaginas; i++) {
      const btn = document.createElement("button");
      btn.textContent = i;
      btn.className = `btn mx-1 ${i === paginaActual ? 'btn-blue-dark' : 'btn-outline-secondary'}`;
      btn.addEventListener("click", () => {
        paginaActual = i;
        renderizarPagina(paginaActual);
      });
      paginacion.appendChild(btn);
    }
  }

  function filtrarClases() {
    const texto = inputBusqueda.value.trim().toLowerCase();

    if (texto === "") {
      clasesFiltradas = clasesTotales.slice();
    } else {
      clasesFiltradas = clasesTotales.filter(clase =>
        clase.nombre_clase.toLowerCase().includes(texto) ||
        clase.nombre_profesor.toLowerCase().includes(texto)
      );
    }

    paginaActual = 1;
    renderizarPagina(paginaActual);
  }

  fetch("api/clases.php")
    .then(res => res.json())
    .then(clases => {
      clasesTotales = clases;
      clasesFiltradas = clasesTotales.slice();
      if (clasesTotales.length === 0) {
        contenedor.innerHTML = "<p>No hay clases disponibles.</p>";
        return;
      }
      renderizarPagina(paginaActual);
    })
    .catch(err => {
      console.error("Error al cargar clases:", err);
      contenedor.innerHTML = "<p>Error al cargar las clases.</p>";
    });

  formBusqueda.addEventListener("submit", e => {
    e.preventDefault();
    filtrarClases();
  });

  inputBusqueda.addEventListener("input", filtrarClases);
});

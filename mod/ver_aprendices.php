<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/conexion/init.php';
include 'session.php';
if ($_SESSION['rol'] !== 3 && $_SESSION['rol'] !== 5) {
    header('Location:' . BASE_URL . '/includes/exit.php?motivo=acceso-denegado');
    exit;
}

$id_instructor = $_SESSION['documento'];
$rol = $_SESSION['rol'] ?? '';

$redirecciones = [
  3 => '/instructor/index.php',
  5 => '/transversal/index.php'
];

$destino = BASE_URL . ($redirecciones[$rol] ?? '/index.php');
$id_ficha = isset($_GET['id_ficha']) ? (int)$_GET['id_ficha'] : 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 6;
$offset = ($page - 1) * $limit;

// Obtener total de aprendices
$total_stmt = $conex->prepare("SELECT COUNT(*) FROM user_ficha WHERE id_ficha = :id_ficha");
$total_stmt->execute(['id_ficha' => $id_ficha]);
$total_aprendices = $total_stmt->fetchColumn();
$total_pages = ceil($total_aprendices / $limit);

// Obtener aprendices de la página actual
$sql = "
    SELECT 
        u.id,
        u.nombres,
        u.apellidos,
        u.correo,
        u.telefono
    FROM user_ficha uf
    JOIN usuarios u ON uf.id_user = u.id
    WHERE uf.id_ficha = :id_ficha
    LIMIT $limit OFFSET $offset
";

$stmt = $conex->prepare($sql);
$stmt->execute(['id_ficha' => $id_ficha]);
$aprendices = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Ficha <?= htmlspecialchars($id_ficha) ?></title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/styles/style_side.css" />
  <link rel="stylesheet" href="<?= BASE_URL ?>/styles/header.css">
  <link rel="icon" href="<?= BASE_URL ?>/assets/img/icon2.png" />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" />

  <style>
    :root {
      --sidebar-width: 280px;
      --sidebar-collapsed-width: 70px;
      --header-height: 80px;
      --primary-color: #0E4A86;
      --primary-hover: #0a3d6b;
      --background-color: #f8f9fa;
      --border-color: #dee2e6;
      --text-muted: #6c757d;
    }

    /* SCROLLBAR */
    ::-webkit-scrollbar {
      width: 8px;
    }

    ::-webkit-scrollbar-track {
      background: var(--background-color);
    }

    ::-webkit-scrollbar-thumb {
      background: var(--border-color);
      border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb:hover {
      background: var(--text-muted);
    }

    /* MAIN CONTENT */
    .main-content {
      margin-left: var(--sidebar-width);
      margin-top: -50px;
      transition: margin-left 0.5s cubic-bezier(0.4, 0, 0.2, 1);
      min-height: calc(100vh - var(--header-height));
      padding: 2rem;
    }

    /* Cuando el sidebar está colapsado */
    .sidebar.collapsed~.main-content,
    body.sidebar-collapsed .main-content {
      margin-left: var(--sidebar-collapsed-width);
    }

    /* BARRA DE NAVEGACIÓN RESPONSIVE */
    .search-navbar {
      border-radius: 12px;
      padding: 1rem;
      margin-bottom: 2rem;
      position: relative;
    }

    .search-form {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      justify-content: center;
      flex-wrap: wrap;
    }

    .search-input {
      width: 800px;
      max-width: 100%;
      border: 2px solid var(--border-color);
      border-radius: 8px;
      padding: 0.75rem 1rem;
      font-size: 1rem;
      transition: all 0.3s ease;
    }

    .search-input:focus {
      border-color: var(--primary-color);
      box-shadow: 0 0 0 0.2rem rgba(14, 74, 134, 0.25);
      outline: none;
    }

    /* BOTONES RESPONSIVE */
    .but {
      background-color: var(--primary-color);
      border-color: var(--primary-color);
      color: white;
      cursor: pointer;
      transition: all 0.3s ease;
      min-width: 50px;
      padding: 0.75rem 1rem;
    }

    .but:hover {
      background-color: var(--primary-hover);
      border-color: var(--primary-hover);
      color: white;
      transform: translateY(-1px);
    }

    /* TÍTULO RESPONSIVE */
    .page-title {
      font-size: 2rem;
      font-weight: 600;
      color: var(--primary-color);
      margin-bottom: 2rem;
      text-align: center;
    }

    /* GRID DE APRENDICES - 3 COLUMNAS DE 2 FILAS */
    .aprendices-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      grid-template-rows: repeat(2, 1fr);
      gap: 1.5rem;
      max-width: 1200px;
      margin: 0 auto;
      min-height: 400px;
    }

    /* TARJETAS DE APRENDICES RESPONSIVE */
    .ficha-aprendiz-card {
      border-radius: 1rem;
      background-color: #f9f9f9;
      transition: all 0.3s ease;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      border: 1px solid var(--border-color);
      height: 100%;
      min-height: 180px;
    }

    .ficha-aprendiz-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 10px 25px rgba(14, 74, 134, 0.15);
      border-color: var(--primary-color);
    }

    .ficha-aprendiz-card .card-title {
      font-weight: 600;
      font-size: 1.1rem;
      color: var(--primary-color);
      margin-bottom: 0.75rem;
      line-height: 1.3;
    }

    .ficha-aprendiz-card .card-text {
      font-size: 0.9rem;
      color: var(--text-muted);
      margin-bottom: 0.5rem;
      line-height: 1.4;
    }

    .ficha-aprendiz-card .card-body {
      padding: 1.25rem;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      height: 100%;
    }

    .btn-detalles {
      transition: all 0.3s ease;
      font-weight: 500;
      border-color: var(--primary-color);
      background-color: var(--primary-color);
      color: white;
      width: 100%;
      padding: 0.75rem;
      margin-top: auto;
    }

    .btn-detalles:hover {
      color: var(--primary-color);
      background-color: white;
      border-color: var(--primary-color);
      transform: translateY(-1px);
    }

    /* PAGINACIÓN RESPONSIVE */
    .pagination {
      justify-content: center;
      flex-wrap: wrap;
      gap: 0.25rem;
    }

    .pagination .page-link {
      background-color: white;
      color: var(--primary-color);
      border-color: var(--primary-color);
      margin: 0;
      transition: all 0.3s ease;
      min-width: 40px;
      text-align: center;
    }

    .pagination .page-link:hover {
      background-color: var(--primary-color);
      color: white;
      transform: translateY(-1px);
    }

    .pagination .active .page-link {
      background-color: var(--primary-color);
      color: white;
      border-color: var(--primary-color);
    }

    /* MODAL RESPONSIVE */
    .modal-content {
      border: none;
      border-radius: 12px;
      box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.175);
    }

    .modal-header {
      background-color: var(--primary-color);
      border-radius: 12px 12px 0 0;
      padding: 1.5rem;
    }

    .modal-body {
      padding: 2rem;
    }

    /* LOADING STATE */
    .loading-container {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 3rem 1rem;
      background: white;
      border-radius: 12px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      grid-column: 1 / -1;
      min-height: 200px;
    }

    .spinner-border {
      color: var(--primary-color);
    }

    /* ESTADO VACÍO */
    .empty-state {
      grid-column: 1 / -1;
      text-align: center;
      padding: 3rem 1rem;
      color: var(--text-muted);
    }

    /* RESPONSIVE BREAKPOINTS */

    /* Tablets (768px - 991px) */
    @media (max-width: 991px) {
      .main-content {
        margin-left: var(--sidebar-collapsed-width);
        padding: 1.5rem;
      }
      

      .search-navbar {
        padding: 0.75rem;
        margin-bottom: 1.5rem;
      }

      .search-input {
        width: 100%;
        max-width: 500px;
      }

      .page-title {
        font-size: 1.75rem;
      }

      /* 2 columnas en tablets */
      .aprendices-grid {
        grid-template-columns: repeat(2, 1fr);
        grid-template-rows: repeat(3, 1fr);
        gap: 1.25rem;
        min-height: 500px;
      }

      .ficha-aprendiz-card .card-title {
        font-size: 1rem;
      }

      .ficha-aprendiz-card .card-body {
        padding: 1rem;
      }
    }

    /* Mobile Large (576px - 767px) */
    @media (max-width: 767px) {
      .main-content {
        margin-left: 0 !important;
        padding: 1rem;
      }

      .search-navbar {
        padding: 0.75rem;
        margin-bottom: 1.5rem;
      }

      .search-form {
        flex-direction: column;
        align-items: stretch;
        gap: 0.75rem;
      }

      .search-buttons {
        display: flex;
        gap: 0.5rem;
        order: 1;
      }

      .search-input {
        width: 100%;
        max-width: none;
        order: 2;
      }

      .but {
        flex: 1;
        padding: 0.75rem;
        display: none;
      }

      .page-title {
        font-size: 1.5rem;
        margin-bottom: 1.5rem;
      }

      /* 2 columnas en móviles grandes */
      .aprendices-grid {
        grid-template-columns: repeat(2, 1fr);
        grid-template-rows: repeat(3, 1fr);
        gap: 1rem;
        min-height: 450px;
      }

      .ficha-aprendiz-card {
        min-height: 140px;
      }

      .ficha-aprendiz-card .card-body {
        padding: 0.875rem;
      }

      .ficha-aprendiz-card .card-title {
        font-size: 0.95rem;
        margin-bottom: 0.5rem;
      }

      .ficha-aprendiz-card .card-text {
        font-size: 0.8rem;
        margin-bottom: 0.4rem;
      }

      .btn-detalles {
        padding: 0.6rem;
        font-size: 0.9rem;
      }

      /* Paginación en móvil */
      .pagination .page-link {
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
        min-width: 36px;
      }

      /* Modal en móvil */
      .modal-dialog {
        margin: 0.5rem;
        max-width: calc(100% - 1rem);
      }

      .modal-body {
        padding: 1rem;
      }

      .modal-header {
        padding: 1rem;
      }
    }

    /* Mobile Medium (480px - 575px) */
    @media (max-width: 575px) {
      .main-content {
        padding: 0.75rem;
      }

      .search-navbar {
        padding: 0.5rem;
        margin-bottom: 1rem;
      }

      .search-form {
        gap: 0.5rem;
      }

      .but {
        padding: 0.6rem 0.8rem;
        font-size: 0.9rem;
        display: none;

      }

      .page-title {
        font-size: 1.25rem;
        margin-bottom: 1rem;
      }

      /* 1 columna en móviles medianos */
      .aprendices-grid {
        grid-template-columns: 1fr;
        grid-template-rows: repeat(6, 1fr);
        gap: 0.75rem;
        min-height: auto;
      }

      .ficha-aprendiz-card {
        min-height: 120px;
      }

      .ficha-aprendiz-card .card-body {
        padding: 0.75rem;
      }

      .ficha-aprendiz-card .card-title {
        font-size: 0.9rem;
        margin-bottom: 0.4rem;
      }

      .ficha-aprendiz-card .card-text {
        font-size: 0.75rem;
        margin-bottom: 0.3rem;
      }

      .btn-detalles {
        padding: 0.5rem;
        font-size: 0.85rem;
      }

      .pagination .page-link {
        padding: 0.4rem 0.6rem;
        font-size: 0.8rem;
        min-width: 32px;
      }
    }

    /* Mobile Small (320px - 479px) */
    @media (max-width: 479px) {
      .main-content {
        padding: 0.5rem;
      }

      .search-navbar {
        padding: 0.5rem;
        border-radius: 8px;
      }

      .search-input {
        padding: 0.5rem 0.75rem;
        font-size: 0.9rem;
      }

      .but {
        padding: 0.5rem;
        font-size: 0.85rem;
        display: none;

      }

      .page-title {
        font-size: 1.1rem;
        margin-bottom: 0.75rem;
      }

      .aprendices-grid {
        gap: 0.5rem;
      }

      .ficha-aprendiz-card {
        border-radius: 8px;
        min-height: 110px;
      }

      .ficha-aprendiz-card .card-body {
        padding: 0.5rem;
      }

      .ficha-aprendiz-card .card-title {
        font-size: 0.85rem;
        margin-bottom: 0.3rem;
      }

      .ficha-aprendiz-card .card-text {
        font-size: 0.7rem;
        margin-bottom: 0.25rem;
      }

      .btn-detalles {
        padding: 0.4rem;
        font-size: 0.8rem;
      }

      .pagination .page-link {
        padding: 0.3rem 0.5rem;
        font-size: 0.75rem;
        min-width: 28px;
      }

      .modal-dialog {
        margin: 0.25rem;
        max-width: calc(100% - 0.5rem);
      }

      .modal-body {
        padding: 0.75rem;
      }

      .modal-header {
        padding: 0.75rem;
      }
    }

    /* Landscape orientation en móviles */
    @media (max-height: 500px) and (orientation: landscape) {
      .main-content {
        margin-top: 60px;
        padding: 1rem;
      }

      .search-navbar {
        margin-bottom: 1rem;
      }

      .page-title {
        font-size: 1.25rem;
        margin-bottom: 1rem;
      }

      .aprendices-grid {
        min-height: auto;
      }
    }

    /* Extra responsive para pantallas muy pequeñas */
    @media (max-width: 320px) {
      .main-content {
        padding: 0.25rem;
      }

      .search-navbar {
        padding: 0.25rem;
      }

      .search-input {
        padding: 0.4rem;
        font-size: 0.85rem;
      }

      .page-title {
        font-size: 1rem;
      }

      .ficha-aprendiz-card .card-body {
        padding: 0.4rem;
      }

      .btn-detalles {
        padding: 0.3rem;
        font-size: 0.75rem;
      }

      .pagination .page-link {
        padding: 0.25rem 0.4rem;
        font-size: 0.7rem;
        min-width: 24px;
      }
    }

    /* Estados de carga mejorados */
    @media (max-width: 767px) {
      .loading-container {
        min-height: 150px;
        padding: 2rem 1rem;
      }
    }

    /* Mejoras de accesibilidad */
    @media (prefers-reduced-motion: reduce) {
      * {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
      }
    }

    /* Optimizaciones para touch */
    @media (pointer: coarse) {
      .but {
        min-height: 44px;
        min-width: 44px;
      }

      .btn-detalles {
        min-height: 44px;
      }

      .pagination .page-link {
        min-height: 44px;
        min-width: 44px;
      }
    }

    
  </style>
</head>

<body style="padding-top:180px;" class="sidebar-collapsed">
  <?php include 'design/header.php'; ?>
  <?php include 'design/sidebar.php'; ?>

  <div class="main-content">
    <nav class="search-navbar">
      <div class="search-form">
        <div class="search-buttons">
          <a href="<?= $destino ?>" class="but btn">
            <i class="bi bi-arrow-90deg-left"></i>
          </a>
          <button class="but btn" type="button" title="Buscar">
            <i class="bi bi-search"></i>
          </button>
        </div>
        <input id="buscarficha" class="search-input form-control" type="search" placeholder="Buscar número de documento" aria-label="Search" />
      </div>
    </nav>

    <h2 class="page-title">Ficha <?= htmlspecialchars($id_ficha) ?></h2>

    <div class="container-fluid">
      <div class="aprendices-grid" id="contenedor-aprendices">
        <!-- Aquí se cargan los aprendices vía AJAX -->
        <div class="loading-container">
          <div class="spinner-border" role="status">
            <span class="visually-hidden">Cargando...</span>
          </div>
          <p class="mt-2">Cargando aprendices...</p>
        </div>
      </div>

      <div class="d-flex justify-content-center mt-4">
        <nav>
          <ul class="pagination" id="paginacion-aprendices">
          </ul>
        </nav>
      </div>
    </div>
  </div>

  <!-- Modal para detalles del aprendiz -->
  <div class="modal fade" id="modalAprendiz" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title text-white">
            <i class="bi bi-person-circle me-2"></i>Detalles del Aprendiz
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body" id="modalContenido">
          <!-- Aquí se cargará la tabla desde detalles_aprendiz.php -->
          <div class="loading-container">
            <div class="spinner-border" role="status">
              <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2">Cargando detalles...</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const input = document.getElementById('buscarficha');
      const container = document.getElementById('contenedor-aprendices');
      const paginacion = document.getElementById('paginacion-aprendices');
      const idFicha = <?= (int)$id_ficha ?>;

      function cargarAprendices(query = '', page = 1) {
        // Estado de carga
        container.innerHTML = `
          <div class="loading-container">
            <div class="spinner-border" role="status">
              <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2 mb-0">Buscando aprendices...</p>
          </div>
        `;

        const formData = new FormData();
        formData.append('id_ficha', idFicha);
        formData.append('query', query);
        formData.append('page', page);

        fetch('buscar_aprendices.php', {
            method: 'POST',
            body: formData
          })
          .then(res => {
            if (!res.ok) {
              throw new Error('Error en la respuesta del servidor');
            }
            return res.json();
          })
          .then(data => {
            container.innerHTML = data.tarjetas;
            paginacion.innerHTML = data.paginacion;

            // Reasignar eventos de los botones
            document.querySelectorAll('.page-link').forEach(link => {
              link.addEventListener('click', e => {
                e.preventDefault();
                const nuevaPagina = e.target.dataset.page;
                cargarAprendices(input.value.trim(), nuevaPagina);
              });
            });
          })
          .catch(error => {
            console.error('Error:', error);
            container.innerHTML = `
              <div class="loading-container">
                <i class="bi bi-exclamation-triangle" style="font-size: 3rem; color: var(--text-muted);"></i>
                <h3 style="color: var(--text-muted);">Error al cargar</h3>
                <p style="color: var(--text-muted);">No se pudieron cargar los aprendices. Intenta nuevamente.</p>
              </div>
            `;
          });
      }

      // Debounce para optimizar búsquedas
      let searchTimeout;
      input.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
          cargarAprendices(input.value.trim(), 1);
        }, 300);
      });

      // Carga inicial
      cargarAprendices();
    });

    // Manejar clics en botones de detalles
    document.addEventListener('click', function(e) {
      if (e.target.classList.contains('btn-detalles')) {
        const id = e.target.getAttribute('data-id');

        document.getElementById('modalContenido').innerHTML = `
          <div class="loading-container">
            <div class="spinner-border" role="status">
              <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2 mb-0">Cargando detalles...</p>
          </div>
        `;

        const modal = new bootstrap.Modal(document.getElementById('modalAprendiz'));
        modal.show();

        const formData = new FormData();
        formData.append('id', id);

        fetch('detalles_aprendiz.php', {
            method: 'POST',
            body: formData
          })
          .then(response => {
            if (!response.ok) {
              throw new Error('Error en la respuesta del servidor');
            }
            return response.text();
          })
          .then(html => {
            document.getElementById('modalContenido').innerHTML = html;
          })
          .catch(error => {
            console.error('Error:', error);
            document.getElementById('modalContenido').innerHTML = `
              <div class="loading-container">
                <i class="bi bi-exclamation-triangle" style="font-size: 3rem; color: var(--text-muted);"></i>
                <h3 style="color: var(--text-muted);">Error al cargar</h3>
                <p style="color: var(--text-muted);">No se pudieron cargar los detalles del aprendiz.</p>
              </div>
            `;
          });
      }
    });

    // Manejar cambios de orientación
    window.addEventListener('orientationchange', () => {
      setTimeout(() => {
        window.scrollTo(0, 0);
      }, 100);
    });
  </script>
</body>

</html>
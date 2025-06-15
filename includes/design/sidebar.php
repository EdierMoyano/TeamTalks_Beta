<?php
$esLocal = strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['DOCUMENT_ROOT'], 'htdocs') !== false;

// Ruta dinámica hacia init.php
$rutaInit = $esLocal
    ? $_SERVER['DOCUMENT_ROOT'] . '/teamtalks/conexion/init.php'
    : $_SERVER['DOCUMENT_ROOT'] . '/conexion/init.php';

require_once $rutaInit;


?>

<link rel="stylesheet" href="<?= BASE_URL ?>/styles/style_side.css">

<aside class="sidebar collapsed" style="height: 620px;">
  <!-- Sidebar header -->
  <header class="sidebar-header">
    <a href="#" class="header-logo">
      <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="CodingNepal">
    </a>
    <button class="toggler sidebar-toggler">
      <span class="material-symbols-rounded">chevron_left</span>
    </button>
    <button class="toggler menu-toggler">
      <span class="material-symbols-rounded">menu</span>
    </button>
  </header>

  <nav class="sidebar-nav">
    <!-- Primary top nav -->
    <ul class="nav-list primary-nav">


      <!-- Solo para Instructor (rol 3) -->
      <?php if ($_SESSION['rol'] == 3): ?>
        <li class="nav-item">
          <a href="<?= BASE_URL ?>/instructor/index.php" class="nav-link">
            <i class="side bi bi-people-fill"></i>
            <span class="nav-label">Fichas</span>
          </a>
        </li>


        <li class="nav-item">
          <a href="<?= BASE_URL ?>/instructor/actividades.php" class="nav-link">
            <i class="side bi bi-backpack-fill"></i>
            <span class="nav-label">Actividades</span>
          </a>
        </li>
        <li class="nav-item">
          <a href="#" class="nav-link">
            <i class="side bi bi-pencil-square"></i>
            <span class="nav-label">Foros</span>
          </a>
        </li>

      <?php endif; ?>

      <!-- Solo para Aprendiz (rol 4) -->
      <?php if ($_SESSION['rol'] == 4): ?>
        <li class="nav-item"><a href="<?= BASE_URL ?>/aprendiz/index.php" class="nav-link"><i class="bi bi-book" style="position: relative; top: 0px; left: 0px; color: black;"></i><span class="nav-label">Formaciones</span></a></li>
        <li class="nav-item has-submenu">
          <a href="#" class="nav-link submenu-toggle">
            <i class="bi bi-mortarboard" style="position: relative; top: 0px; left: 0px; color: black;"></i>
            <span class="nav-label">Clases Inscritas</span>
            <i class="bi bi-chevron-down submenu-arrow" style="position: relative; top: 0px; left: 0px; color: black;"></i>
          </a>
          <ul class="submenu">
            <li><a href="#" class="nav-link small"><i class="bi bi-journal-bookmark-fill" style="position: relative; top: 0px; left: 0px; color: black;"></i>Actividades</a></li>
            <li><a href="#" class="nav-link small"><i class="bi bi-card-checklist" style="position: relative; top: 0px; left: 0px; color: black;"></i>Calificaciones</a></li>
          </ul>
        </li>

        <li class="nav-item"><a href="<?= BASE_URL ?>/aprendiz/foros.php" class="nav-link"><i class="side bi bi-pencil-square"></i><span class="nav-label">Foros</span></a></li>
      <?php endif; ?>

      <!-- Solo para Transversal (rol 5) -->
      <?php if ($_SESSION['rol'] == 5): ?>
        <li class="nav-item">
          <a href="<?= BASE_URL ?>/transversal/index.php" class="nav-link">
            <i class="side bi bi-people-fill"></i>
            <span class="nav-label">Fichas</span>
          </a>
        </li>


        <li class="nav-item">
          <a href="<?= BASE_URL ?>/transversal/actividades.php" class="nav-link">
            <i class="side bi bi-backpack-fill"></i>
            <span class="nav-label">Actividades</span>
          </a>
        </li>
        <li class="nav-item">
          <a href="#" class="nav-link">
            <i class="side bi bi-pencil-square"></i>
            <span class="nav-label">Foros</span>
          </a>
        </li>

      <?php endif; ?>



    </ul>
  </nav>
</aside>

<script src="<?= BASE_URL ?>/js/side_bar.js"></script>
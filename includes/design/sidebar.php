<?php
// Definir si está en entorno local
$esLocal = in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']);

require_once $_SERVER['DOCUMENT_ROOT'] . '/conexion/init.php';

// Ruta dinámica hacia init.php (aunque en este caso es la misma)
$rutaInit = $esLocal
    ? $_SERVER['DOCUMENT_ROOT'] . '/conexion/init.php'
    : $_SERVER['DOCUMENT_ROOT'] . '/conexion/init.php';

require_once $rutaInit;
?>


<link rel="stylesheet" href="<?= BASE_URL ?>/styles/style_side.css">

<aside class="sidebar collapsed">
  <!-- Sidebar header -->
  <header class="sidebar-header">
    <a href="#" class="header-logo">
      <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="CodingNepal">
    </a>
    <button class="toggler sidebar-toggler">
      <span class="material-symbols-rounded">chevron_left</span>
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
        <li class="nav-item">
          <a href="<?= BASE_URL ?>/aprendiz/tarjeta_formacion/index.php" class="nav-link">
            <i class="side bi bi-backpack-fill"></i>
            <span class="nav-label">Formaciones</span>
          </a>
        </li>
        <li class="nav-item has-submenu">
          <a href="#" class="nav-link submenu-toggle">
            <i class="side bi bi-people-fill"></i>
            <span class="nav-label">Clases Inscritas</span>
            <i class="bi bi-chevron-down submenu-arrow"></i>
          </a>
          <ul class="submenu">
            <li><a href="<?= BASE_URL ?>/aprendiz/" class="nav-link small"><i class="side bi bi-briefcase-fill"></i>Actividades</a></li>
            <li><a href="<?= BASE_URL ?>/aprendiz/" class="nav-link small"><i class="side bi bi-arrow-left-right"></i>Transversales</a></li>
          </ul>
        </li>

        <li class="nav-item">
          <a href="<?= BASE_URL ?>/aprendiz/foros/foros.php" class="nav-link">
            <i class="side bi bi-pencil-square"></i>
            <span class="nav-label">Foros</span>
          </a>
        </li>

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
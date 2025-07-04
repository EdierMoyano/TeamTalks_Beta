<?php
if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['DOCUMENT_ROOT'], 'htdocs') !== false) {
  require_once $_SERVER['DOCUMENT_ROOT'] . '/teamtalks/conexion/init.php';
} else {
  require_once $_SERVER['DOCUMENT_ROOT'] . '/conexion/init.php';
}
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/styles/style_side.css">

<aside class="sidebar collapsed" id="sidebar">
  <nav class="sidebar-nav">
    <ul class="nav-list primary-nav">
      <button class="toggler sidebar-toggler" onclick="toggleSidebar()" style="height: 50px;">
        <span class="material-symbols-rounded">
          <i class="bi bi-chevron-right toggle-icon"></i>
        </span>
      </button>

      <!-- Solo para Instructor (rol 3) -->
      <?php if ($_SESSION['rol'] == 3): ?>
        <li class="nav-item">
          <a href="<?= BASE_URL ?>/instructor/index.php" class="sidebar-nav-link" data-tooltip="Fichas">
            <i class="side bi bi-people-fill nav-icon"></i>
            <span class="nav-label">Fichas</span>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?= BASE_URL ?>/instructor/horario.php" class="sidebar-nav-link" data-tooltip="Horarios">
            <i class="side bi bi-calendar2-range-fill nav-icon"></i>
            <span class="nav-label">Horarios</span>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?= BASE_URL ?>/instructor/actividades.php" class="sidebar-nav-link" data-tooltip="Actividades">
            <i class="side bi bi-backpack-fill nav-icon"></i>
            <span class="nav-label">Actividades</span>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?= BASE_URL ?>/instructor/foros.php" class="sidebar-nav-link" data-tooltip="Foros">
            <i class="side bi bi-pencil-square nav-icon"></i>
            <span class="nav-label">Foros</span>
          </a>
        </li>
      <?php endif; ?>

      <!-- Solo para Aprendiz (rol 4) -->
      <?php if ($_SESSION['rol'] == 4): ?>
        <li class="nav-item">
          <a href="<?= BASE_URL ?>/aprendiz/tarjeta_formacion/index.php" class="sidebar-nav-link" data-tooltip="Formaciones">
            <i class="side bi bi-backpack-fill nav-icon"></i>
            <span class="nav-label">Formaciones</span>
          </a>
        </li>

        <li class="nav-item">
          <a href="<?= BASE_URL ?>/aprendiz/foros/foros.php" class="sidebar-nav-link" data-tooltip="Foros">
            <i class="side bi bi-pencil-square nav-icon"></i>
            <span class="nav-label">Foros</span>
          </a>
        </li>
      <?php endif; ?>

      <!-- Solo para Transversal (rol 5) -->
      <?php if ($_SESSION['rol'] == 5): ?>
        <li class="nav-item">
          <a href="<?= BASE_URL ?>/transversal/index.php" class="sidebar-nav-link" data-tooltip="Fichas">
            <i class="side bi bi-people-fill nav-icon"></i>
            <span class="nav-label">Fichas</span>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?= BASE_URL ?>/transversal/horario.php" class="sidebar-nav-link" data-tooltip="Horarios">
            <i class="side bi bi-calendar2-range-fill nav-icon"></i>
            <span class="nav-label">Horarios</span>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?= BASE_URL ?>/transversal/actividades.php" class="sidebar-nav-link" data-tooltip="Actividades">
            <i class="side bi bi-backpack-fill nav-icon"></i>
            <span class="nav-label">Actividades</span>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?= BASE_URL ?>/transversal/foros.php" class="sidebar-nav-link" data-tooltip="Foros">
            <i class="side bi bi-pencil-square nav-icon"></i>
            <span class="nav-label">Foros</span>
          </a>
        </li>
      <?php endif; ?>
    </ul>
  </nav>
</aside>

<script src="<?= BASE_URL ?>/js/side_bar.js"></script>
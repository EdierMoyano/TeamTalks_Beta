<aside class="sidebar collapsed" style="height: 620px;">
  <!-- Sidebar header -->
  <header class="sidebar-header">
    <a href="#" class="header-logo">
      <img src="../assets/img/logo.png" alt="CodingNepal">
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
        <li class="nav-item has-submenu">
          <a href="#" class="nav-link submenu-toggle">
            <i class="side bi bi-people-fill"></i>
            <span class="nav-label">Fichas</span>
            <i class="bi bi-chevron-down submenu-arrow" style="position: relative; top: 0px; left: 0px; color: black;"></i>
          </a>
          <ul class="submenu">
            <li><a href="index.php" class="nav-link small">Gerente</a></li>
            <li><a href="transversales.php" class="nav-link small">Transversales</a></li>
          </ul>
        </li>


        <li class="nav-item">
          <a href="#" class="nav-link">
            <i class="side bi bi-backpack-fill"></i>
            <span class="nav-label">Actividades</span>
          </a>
        </li>
        <li class="nav-item">
          <a href="#" class="nav-link">
            <i class="side bi bi-mortarboard-fill"></i>
            <span class="nav-label">Aprendices</span>
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
        <li class="nav-item has-submenu">
          <a href="#" class="nav-link submenu-toggle">
            <i class="side bi bi-people-fill"></i>
            <span class="nav-label">Clases Inscritas</span>
            <i class="bi bi-chevron-down submenu-arrow" style="position: relative; top: 0px; left: 0px; color: black;"></i>
          </a>
          <ul class="submenu">
            <li><a href="index.php" class="nav-link small">Actividades</a></li>
            <li><a href="transversales.php" class="nav-link small">Calificaciones</a></li>
          </ul>
        </li>

      <?php endif; ?>

      

    </ul>
  </nav>
</aside>

<script src="../js/script.js"></script>
  <link rel="stylesheet" href="../styles/style_side_cla.css">

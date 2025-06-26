<script>
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

      // Modificación: Usar id_clase como parámetro en la URL
      card.innerHTML = `
        <img src="${clase.imagen}" class="card-img-top" alt="Imagen de ${clase.nombre_clase}">
        <div class="card-body">
          <h5 class="card-title">${clase.nombre_clase}</h5>
          <p class="card-text"><strong>Instructor:</strong> ${clase.nombre_profesor}</p>
          <p class="card-text"><strong>Ficha:</strong> ${clase.numero_fichas}</p>
        </div>
        <div class="card-footer bg-transparent border-top-0">
          <a href="../aprendiz/clase/index.php?id_clase=${clase.id_clase}" class="btn btn-blue-dark w-100">Ingresar a Clase</a>
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
</script>
<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['documento'])) {
    die("Usuario no autenticado.");
}

$documento_usuario = $_SESSION['documento'];

// Obtener el ID real del usuario a partir del documento
$stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['documento']]);
$usuario = $stmt->fetch();

if (!$usuario) {
    die("Error: No se encontró el usuario en la base de datos.");
}

$id_usuario_actual = $usuario['id'];



// Obtener el ID de la clase desde la URL
$id_clase = isset($_GET['id_clase']) ? intval($_GET['id_clase']) : null;

if (!$id_clase) {
    die("Error: No se ha especificado una clase. Por favor, seleccione una clase desde la página de 'Mis Clases'.");
}

// Verificar que la materia_ficha existe y obtener sus datos
$stmt = $pdo->prepare("
    SELECT mf.id_materia_ficha, mf.id_materia, mf.id_ficha, m.materia, f.id_ficha
    FROM materia_ficha mf
    JOIN materias m ON mf.id_materia = m.id_materia
    JOIN fichas f ON mf.id_ficha = f.id_ficha
    WHERE mf.id_materia_ficha = ?
");
$stmt->execute([$id_clase]);
$materiaFichaData = $stmt->fetch();

if (!$materiaFichaData) {
    die("Error: La clase especificada no existe.");
}

// Verificar que el usuario tiene acceso a esta materia_ficha
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count
    FROM user_ficha uf
    WHERE uf.id_user = ? AND uf.id_ficha = ?
");
$stmt->execute([$id_usuario_actual, $materiaFichaData['id_ficha']]);
$result = $stmt->fetch();


if (!isset($result['count']) || intval($result['count']) === 0) {
    die("Error: No tienes acceso a esta clase.");
}



// Asignar los valores necesarios para el resto del código
$id_materia_seleccionada = $materiaFichaData['id_materia'];
$id_ficha_actual = $materiaFichaData['id_ficha'];
$idMateriaFicha = $id_clase;

// Obtener datos de la ficha
$stmt = $pdo->prepare("
    SELECT f.*, fo.nombre as nombre_formacion, j.jornada, a.ambiente
    FROM fichas f
    LEFT JOIN formacion fo ON f.id_formacion = fo.id_formacion
    LEFT JOIN jornada j ON f.id_jornada = j.id_jornada
    LEFT JOIN ambientes a ON f.id_ambiente = a.id_ambiente
    WHERE f.id_ficha = ? AND f.id_estado = 1
");
$stmt->execute([$id_ficha_actual]);
$ficha = $stmt->fetch();

if (!$ficha) {
    die("Error: No se pudo obtener información de la ficha.");
}

// Obtener información de la materia
$stmt = $pdo->prepare("
    SELECT m.materia, m.id_materia, mf.id_materia_ficha
    FROM materia_ficha mf
    JOIN materias m ON mf.id_materia = m.id_materia
    WHERE mf.id_materia_ficha = ?
");
$stmt->execute([$id_clase]);
$materiaActual = $stmt->fetch();

if (!$materiaActual) {
    die("Error: No se pudo obtener información de la materia.");
}

// Obtener actividades de la materia
$stmt = $pdo->prepare("
    SELECT a.*, m.materia, u.nombres, u.apellidos,
           au.id_actividad_user as entrega_id,
           CASE 
               WHEN au.id_actividad_user IS NOT NULL THEN 'entregada'
               WHEN a.fecha_entrega < NOW() THEN 'vencida'
               ELSE 'pendiente'
           END as estado_entrega
    FROM actividades a
    JOIN materia_ficha mf ON a.id_materia_ficha = mf.id_materia_ficha
    JOIN materias m ON mf.id_materia = m.id_materia
    JOIN usuarios u ON mf.id_instructor = u.id
    LEFT JOIN actividades_user au ON a.id_actividad = au.id_actividad AND au.id_user = ?
    WHERE a.id_materia_ficha = ?
    ORDER BY a.fecha_entrega ASC
");
$stmt->execute([$id_usuario_actual, $idMateriaFicha]);
$todasActividades = $stmt->fetchAll();

// Obtener actividades completadas
$stmt = $pdo->prepare("
    SELECT a.*, m.materia, u.nombres, u.apellidos, au.fecha_entrega as fecha_entregada, au.nota
    FROM actividades_user au
    JOIN actividades a ON au.id_actividad = a.id_actividad
    JOIN materia_ficha mf ON a.id_materia_ficha = mf.id_materia_ficha
    JOIN materias m ON mf.id_materia = m.id_materia
    JOIN usuarios u ON mf.id_instructor = u.id
    WHERE au.id_user = ? AND a.id_materia_ficha = ?
    ORDER BY au.fecha_entrega DESC
");
$stmt->execute([$id_usuario_actual, $idMateriaFicha]);
$actividadesCompletadas = $stmt->fetchAll();

// Obtener temas recientes del foro
$stmt = $pdo->prepare("
    SELECT tf.*, u.nombres, u.apellidos, f.fecha_foro
    FROM temas_foro tf
    JOIN foros f ON tf.id_foro = f.id_foro
    JOIN materia_ficha mf ON f.id_materia_ficha = mf.id_materia_ficha
    JOIN usuarios u ON tf.id_user = u.id
    WHERE mf.id_materia_ficha = ?
    ORDER BY tf.fecha_creacion DESC
    LIMIT 5
");
$stmt->execute([$idMateriaFicha]);
$temasRecientes = $stmt->fetchAll();

// Obtener anuncios recientes
$stmt = $pdo->prepare("
    SELECT ai.id_anuncio, ai.titulo, ai.contenido as descripcion, ai.fecha_creacion, 
           u.nombres, u.apellidos
    FROM anuncios_instructor ai
    JOIN materia_ficha mf ON ai.id_materia_ficha = mf.id_materia_ficha
    JOIN usuarios u ON mf.id_instructor = u.id
    WHERE ai.id_materia_ficha = ? AND ai.id_estado = 1
    ORDER BY ai.fecha_creacion DESC
    LIMIT 5
");
$stmt->execute([$idMateriaFicha]);
$anuncios = $stmt->fetchAll();

// Obtener instructores de la materia
$stmt = $pdo->prepare("
    SELECT u.id, u.nombres, u.apellidos
    FROM materia_ficha mf
    JOIN usuarios u ON mf.id_instructor = u.id
    WHERE mf.id_materia_ficha = ?
");
$stmt->execute([$idMateriaFicha]);
$instructores = $stmt->fetchAll();

// Verificar si el usuario actual es instructor de esta materia_ficha
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count
    FROM materia_ficha mf
    WHERE mf.id_instructor = ? AND mf.id_materia_ficha = ?
");
$stmt->execute([$id_usuario_actual, $idMateriaFicha]);
$result = $stmt->fetch();
$esInstructor = $result['count'] > 0;

// Separar actividades por estado
$actividadesPendientes = array_filter($todasActividades, function ($actividad) {
    return $actividad['estado_entrega'] === 'pendiente';
});

$actividadesVencidas = array_filter($todasActividades, function ($actividad) {
    return $actividad['estado_entrega'] === 'vencida';
});

$actividadesEntregadas = array_filter($todasActividades, function ($actividad) {
    return $actividad['estado_entrega'] === 'entregada';
});

// Para el tablón, solo mostrar próximas entregas (pendientes)
$proximasEntregas = array_filter($todasActividades, function ($actividad) {
    return $actividad['estado_entrega'] === 'pendiente' && strtotime($actividad['fecha_entrega']) >= time();
});

// Función para formatear fechas
function formatearFecha($fecha)
{
    $timestamp = strtotime($fecha);
    return date('d/m/Y H:i', $timestamp);
}

// Función para obtener iniciales
function obtenerIniciales($nombre_completo)
{
    $palabras = explode(' ', $nombre_completo);
    $iniciales = '';
    foreach ($palabras as $palabra) {
        if (!empty($palabra)) {
            $iniciales .= strtoupper(substr($palabra, 0, 1));
        }
    }
    return $iniciales;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Clases - <?php echo htmlspecialchars($materiaActual['materia']); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap y fuentes -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
        integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0">

    <link rel="stylesheet" href="../css/styles.css">

    <style>
        body.sidebar-collapsed .main-content {
            margin-left: 140px;
        }

        .main-content {
            padding: 20px;
        }

        .clase-info-card {
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .card {
            margin-bottom: 20px;
        }

        .tab-button,
        .personas-tab-button {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        .task-list,
        .announcement-list,
        .people-list {
            padding-left: 0 !important;
        }

        .avatar {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            min-width: 50px;
        }

        .task-item {
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .task-item:hover {
            background-color: #f8f9fa;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .task-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .task-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px 0;
            cursor: pointer;
            transition: background 0.2s;
        }

        .task-item:hover {
            background-color: #f9f9f9;
            border-radius: 8px;
        }

        .task-icon {
            flex-shrink: 0;
            width: 38px;
            height: 38px;
            background: #f1f1f1;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 6px;
        }

        .task-icon i {
            color: #111;
            font-size: 1.5rem;
            transform: translate(30px, -25px);
        }

        .task-info {
            flex: 1;
        }

        .task-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .task-title {
            margin: 0;
            font-weight: bold;
        }

        .task-meta {
            margin: 4px 0;
            color: #555;
        }

        .task-description {
            background: #f8f9fa;
            padding: 8px 12px;
            border-radius: 6px;
            margin: 0;
            font-size: 0.95rem;
            color: #333;
        }

        .status-badge.pendiente {
            background-color: #ffeb3b;
            color: #333;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .task-item.vencida {
            border-left: 4px solid #dc3545;
            background-color: #fff5f5;
            padding-left: 16px;
        }

        .task-item.vencida:hover {
            background-color: #ffe6e6;
        }

        .task-icon.status-vencida {
            background-color: #dc3545 !important;
        }

        .task-icon.status-vencida i {
            color: white !important;
            transform: none !important;
        }

        .status-badge.vencida {
            background-color: #dc3545;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .alerta-vencidas {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
            position: relative;
        }

        .alerta-vencidas .icono-alerta {
            font-size: 1.5rem;
            animation: pulse 2s infinite;
        }

        .alerta-vencidas .btn-close-alert {
            position: absolute;
            top: 10px;
            right: 15px;
            background: none;
            border: none;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 5px;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s;
        }

        .alerta-vencidas .btn-close-alert:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .alerta-vencidas.hidden {
            display: none;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.1);
            }

            100% {
                transform: scale(1);
            }
        }

        .status-badge.completed {
            background-color: #28a745;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
    </style>
</head>

<body class="sidebar-collapsed">

    <!-- Header -->
    <?php include '../../includes/design/header.php'; ?>

    <!-- Sidebar -->
    <?php include '../../includes/design/sidebar.php'; ?>

    <!-- Contenido principal -->
    <main class="main-content">
        <div class="container-fluid">
            <div class="row" id="contenedor-clases">
                <!-- Aquí irán las tarjetas -->
                <div class="col-12">
                    <div class="clase-info-card card mb-4 p-3 shadow-sm bg-white">
                        <h2 class="mb-1"><?php echo htmlspecialchars($materiaActual['materia']); ?></h2>
                        <p class="text-muted mb-0">
                            Ficha: <?php echo htmlspecialchars($ficha['id_ficha']); ?> •
                            Instructor: <?php echo !empty($instructores) ? htmlspecialchars($instructores[0]['nombres'] . ' ' . $instructores[0]['apellidos']) : 'No asignado'; ?>
                        </p>
                    </div>

                    <!-- Alerta de tareas vencidas -->
                    <?php if (count($actividadesVencidas) > 0): ?>
                        <div class="alerta-vencidas" id="alertaVencidas">
                            <i class="bi bi-exclamation-triangle-fill icono-alerta"></i>
                            <div>
                                <strong>¡Atención!</strong> Tienes <?php echo count($actividadesVencidas); ?>
                                actividad<?php echo count($actividadesVencidas) > 1 ? 'es' : ''; ?> vencida<?php echo count($actividadesVencidas) > 1 ? 's' : ''; ?>
                                sin entregar.
                            </div>
                            <button class="btn-close-alert" onclick="cerrarAlertaVencidas()" title="Cerrar alerta por 2 días">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                    <?php endif; ?>

                    <!-- Navegación por pestañas -->
                    <div class="tabs-container">
                        <div class="tabs">
                            <button class="tab-button active" data-tab="tablon">
                                <span class="tab-icon"></span>
                                Tablón
                            </button>
                            <button class="tab-button" data-tab="trabajo">
                                <span class="tab-icon"></span>
                                Trabajo en clase
                                <?php if (count($actividadesVencidas) > 0): ?>
                                    <span class="badge bg-danger ms-2"><?php echo count($actividadesVencidas); ?></span>
                                <?php endif; ?>
                            </button>
                            <button class="tab-button" data-tab="personas">
                                <span class="tab-icon"></span>
                                Foro
                            </button>
                        </div>
                    </div>

                    <!-- Contenido de las pestañas -->
                    <div class="tab-content active" id="tablon">
                        <div class="grid">
                            <!-- Próximas entregas -->
                            <div class="card">
                                <div class="card-header">
                                    <h3><span class="icon"><i class="bi bi-alarm-fill"></i></span> Próximas entregas</h3>
                                </div>
                                <div class="card-content">
                                    <?php if (count($proximasEntregas) > 0): ?>
                                        <ul class="task-list">
                                            <?php foreach ($proximasEntregas as $actividad): ?>
                                                <li class="task-item" onclick="window.location.href='detalle_actividad.php?id=<?php echo $actividad['id_actividad']; ?>'">
                                                    <div class="task-icon status-pendiente">
                                                        <i class="bi bi-clock-history"></i>
                                                    </div>
                                                    <div class="task-info">
                                                        <div class="task-header">
                                                            <p class="task-title"><?php echo htmlspecialchars($actividad['titulo']); ?></p>
                                                            <span class="status-badge pendiente">Pendiente</span>
                                                        </div>
                                                        <p class="task-meta">
                                                            <strong><?php echo htmlspecialchars($actividad['materia']); ?></strong> •
                                                            <?php echo htmlspecialchars($actividad['nombres'] . ' ' . $actividad['apellidos']); ?> •
                                                            Entrega: <?php echo formatearFecha($actividad['fecha_entrega']); ?>
                                                        </p>
                                                        <?php if (!empty($actividad['descripcion'])): ?>
                                                            <p class="task-description"><?php echo htmlspecialchars($actividad['descripcion']); ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <div class="empty-state">
                                            <span class="empty-icon"><i class="bi bi-journals"></i></span>
                                            <p>No hay entregas próximas</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Temas de foro recientes -->
                            <div class="card">
                                <div class="card-header">
                                    <h3>
                                        <span class="icon"><i class="bi bi-chat-fill"></i></span>
                                        Temas recientes en el foro
                                        <?php if (count($temasRecientes) > 0): ?>
                                            <span class="badge"><?php echo count($temasRecientes); ?> tema<?php echo count($temasRecientes) > 1 ? 's' : ''; ?></span>
                                        <?php endif; ?>
                                    </h3>
                                </div>
                                <div class="card-content">
                                    <?php if (count($temasRecientes) > 0): ?>
                                        <ul class="task-list">
                                            <?php foreach ($temasRecientes as $tema): ?>
                                                <li class="task-item" onclick="window.location.href='detalle_tema.php?id=<?php echo $tema['id_tema_foro']; ?>'">
                                                    <div class="task-icon"><i class="bi bi-chat-dots"></i></div>
                                                    <div class="task-info">
                                                        <p class="task-title"><?php echo htmlspecialchars($tema['titulo']); ?></p>
                                                        <p class="task-meta">
                                                            <strong><?php echo htmlspecialchars($tema['nombres'] . ' ' . $tema['apellidos']); ?></strong> •
                                                            <?php echo formatearFecha($tema['fecha_creacion']); ?>
                                                        </p>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <div class="empty-state">
                                            <span class="empty-icon"><i class="bi bi-chat"></i></span>
                                            <p>No hay temas recientes</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Anuncios recientes -->
                        <div class="card full-width">
                            <div class="card-header">
                                <h3><span class="icon"><i class="bi bi-megaphone-fill"></i></span> Anuncios</h3>
                            </div>
                            <div class="card-content">
                                <!-- Lista de anuncios (visible para todos) -->
                                <div id="listaAnuncios">
                                    <?php if (count($anuncios) > 0): ?>
                                        <ul class="announcement-list">
                                            <?php foreach ($anuncios as $anuncio): ?>
                                                <li class="announcement-item">
                                                    <div class="announcement-header">
                                                        <div class="avatar instructor-avatar">
                                                            <?php echo obtenerIniciales($anuncio['nombres'] . ' ' . $anuncio['apellidos']); ?>
                                                        </div>
                                                        <div class="announcement-meta">
                                                            <p class="instructor-name"><?php echo htmlspecialchars($anuncio['nombres'] . ' ' . $anuncio['apellidos']); ?></p>
                                                            <p class="announcement-date"><?php echo formatearFecha($anuncio['fecha_creacion']); ?></p>
                                                        </div>
                                                    </div>
                                                    <div class="announcement-content">
                                                        <p class="announcement-title"><?php echo htmlspecialchars($anuncio['titulo']); ?></p>
                                                        <?php if ($anuncio['descripcion']): ?>
                                                            <p class="announcement-text"><?php echo htmlspecialchars($anuncio['descripcion']); ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <div class="empty-state">
                                            <span class="empty-icon"><i class="bi bi-megaphone"></i></span>
                                            <p>No hay anuncios recientes</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-content" id="trabajo">
                        <div class="card full-width">
                            <div class="card-header">
                                <h3><span class="icon"><i class="bi bi-clipboard2-minus-fill"></i></span> Trabajo en clase</h3>
                            </div>
                            <div class="card-content">
                                <!-- Tareas incompletas (vencidas) -->
                                <div class="work-section">
                                    <h4>
                                        <i class="bi bi-exclamation-triangle-fill"></i>
                                        Actividades incompletas
                                    </h4>
                                    <?php if (count($actividadesVencidas) > 0): ?>
                                        <ul class="task-list">
                                            <?php foreach ($actividadesVencidas as $actividad): ?>
                                                <li class="task-item vencida" onclick="window.location.href='detalle_actividad.php?id=<?php echo $actividad['id_actividad']; ?>'">
                                                    <div class="task-icon status-vencida">
                                                        <i class="bi bi-exclamation-triangle-fill"></i>
                                                    </div>
                                                    <div class="task-info">
                                                        <div class="task-header">
                                                            <p class="task-title"><?php echo htmlspecialchars($actividad['titulo']); ?></p>
                                                            <div class="task-actions">
                                                                <span class="status-badge vencida">Vencida</span>
                                                            </div>
                                                        </div>
                                                        <p class="task-meta">
                                                            <strong><?php echo htmlspecialchars($actividad['materia']); ?></strong> •
                                                            <?php echo htmlspecialchars($actividad['nombres'] . ' ' . $actividad['apellidos']); ?> •
                                                            <span style="color: #dc3545; font-weight: bold;">
                                                                Venció: <?php echo formatearFecha($actividad['fecha_entrega']); ?>
                                                            </span>
                                                        </p>
                                                        <?php if (isset($actividad['descripcion']) && $actividad['descripcion']): ?>
                                                            <p class="task-description"><?php echo htmlspecialchars($actividad['descripcion']); ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <div class="empty-state">
                                            <span class="empty-icon"><i class="bi bi-exclamation-triangle"></i></span>
                                            <p>No hay actividades incompletas</p>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="work-section">
                                    <h4>
                                        <i class="bi bi-clock-fill"></i>
                                        Actividades pendientes
                                    </h4>
                                    <?php if (count($actividadesPendientes) > 0): ?>
                                        <ul class="task-list">
                                            <?php foreach ($actividadesPendientes as $actividad): ?>
                                                <li class="task-item" onclick="window.location.href='detalle_actividad.php?id=<?php echo $actividad['id_actividad']; ?>'">
                                                    <div class="task-icon status-pendiente" style="display: flex; align-items: center; justify-content: center; width: 38px; height: 38px; background: #f1f1f1; border-radius: 50%; margin-right: 14px;">
                                                        <i class="bi bi-clock-history" style="color: #111; font-size: 1.5rem;"></i>
                                                    </div>
                                                    <div class="task-info">
                                                        <div class="task-header" style="display: flex; align-items: center; justify-content: space-between;">
                                                            <p class="task-title" style="margin-bottom: 0;"><?php echo htmlspecialchars($actividad['titulo']); ?></p>
                                                            <div class="task-actions">
                                                                <span class="status-badge pendiente">Pendiente</span>
                                                            </div>
                                                        </div>
                                                        <p class="task-meta">
                                                            <strong><?php echo htmlspecialchars($actividad['materia']); ?></strong> •
                                                            <?php echo htmlspecialchars($actividad['nombres'] . ' ' . $actividad['apellidos']); ?> •
                                                            Entrega: <?php echo formatearFecha($actividad['fecha_entrega']); ?>
                                                        </p>
                                                        <?php if (isset($actividad['descripcion']) && $actividad['descripcion']): ?>
                                                            <p class="task-description"><?php echo htmlspecialchars($actividad['descripcion']); ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <div class="empty-state">
                                            <span class="empty-icon"><i class="bi bi-clock-history"></i></span>
                                            <p>No hay actividades pendientes</p>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="work-section">
                                    <h4>
                                        <i class="bi bi-book-fill"></i>
                                        Actividades completadas
                                    </h4>
                                    <?php if (count($actividadesCompletadas) > 0): ?>
                                        <ul class="task-list">
                                            <?php foreach ($actividadesCompletadas as $actividad): ?>
                                                <li class="task-item" onclick="window.location.href='detalle_actividad.php?id=<?php echo $actividad['id_actividad']; ?>'">
                                                    <div class="task-icon status-completed"><i class="bi bi-patch-check"></i></div>
                                                    <div class="task-info">
                                                        <div class="task-header">
                                                            <p class="task-title"><?php echo htmlspecialchars($actividad['titulo']); ?></p>
                                                            <span class="status-badge completed">Completada</span>
                                                        </div>
                                                        <p class="task-meta">
                                                            <strong><?php echo htmlspecialchars($actividad['materia']); ?></strong> •
                                                            <?php echo htmlspecialchars($actividad['nombres'] . ' ' . $actividad['apellidos']); ?> •
                                                            Entregado: <?php echo formatearFecha($actividad['fecha_entregada']); ?>
                                                            <?php if ($actividad['nota']): ?>
                                                                • Nota: <?php echo htmlspecialchars($actividad['nota']); ?>
                                                            <?php endif; ?>
                                                        </p>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <div class="empty-state">
                                            <span class="empty-icon"><i class="bi bi-journals"></i></span>
                                            <p>No hay actividades completadas</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-content" id="personas">
                        <div class="card full-width">
                            <div class="card-header">
                                <h3><span class="icon"><i class="bi bi-chat-fill"></i></span> Foros de discusión</h3>
                            </div>
                            <div class="card-content">
                                <div class="mb-4">
                                    <p>Participa en los foros de discusión para resolver dudas y compartir conocimientos con tus compañeros e instructores.</p>
                                </div>

                                <!-- Temas recientes -->
                                <h4 class="mb-3">Temas recientes</h4>
                                <?php if (count($temasRecientes) > 0): ?>
                                    <ul class="task-list">
                                        <?php foreach ($temasRecientes as $tema): ?>
                                            <li class="task-item" onclick="window.location.href='detalle_tema.php?id=<?php echo $tema['id_tema_foro']; ?>'">
                                                <div class="task-icon"><i class="bi bi-chat-dots"></i></div>
                                                <div class="task-info">
                                                    <p class="task-title"><?php echo htmlspecialchars($tema['titulo']); ?></p>
                                                    <p class="task-meta">
                                                        <strong><?php echo htmlspecialchars($tema['nombres'] . ' ' . $tema['apellidos']); ?></strong> •
                                                        <?php echo formatearFecha($tema['fecha_creacion']); ?>
                                                    </p>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <span class="empty-icon"><i class="bi bi-chat"></i></span>
                                        <p>No hay temas recientes</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="../js/script.js"></script>

    <script>
        // Función para manejar la alerta de actividades vencidas
        function cerrarAlertaVencidas() {
            const alerta = document.getElementById('alertaVencidas');
            if (alerta) {
                // Ocultar la alerta con animación
                alerta.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                alerta.style.opacity = '0';
                alerta.style.transform = 'translateY(-10px)';

                setTimeout(() => {
                    alerta.classList.add('hidden');
                }, 300);

                // Guardar en localStorage que la alerta fue cerrada
                const fechaCierre = new Date().getTime();
                const claveAlerta = 'alertaVencidas_' + <?php echo $id_usuario_actual; ?> + '_' + <?php echo $idMateriaFicha; ?>;
                localStorage.setItem(claveAlerta, fechaCierre.toString());
            }
        }

        // Verificar si la alerta debe mostrarse al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            const alerta = document.getElementById('alertaVencidas');
            if (alerta) {
                const claveAlerta = 'alertaVencidas_' + <?php echo $id_usuario_actual; ?> + '_' + <?php echo $idMateriaFicha; ?>;
                const fechaCierre = localStorage.getItem(claveAlerta);

                if (fechaCierre) {
                    const fechaCierreMs = parseInt(fechaCierre);
                    const fechaActual = new Date().getTime();
                    const dosDiasEnMs = 2 * 24 * 60 * 60 * 1000; // 2 días en milisegundos

                    // Si han pasado menos de 2 días desde que se cerró, mantener oculta
                    if (fechaActual - fechaCierreMs < dosDiasEnMs) {
                        alerta.classList.add('hidden');
                    } else {
                        // Si han pasado más de 2 días, eliminar el registro y mostrar la alerta
                        localStorage.removeItem(claveAlerta);
                    }
                }
            }
        });
    </script>
</body>

</html>

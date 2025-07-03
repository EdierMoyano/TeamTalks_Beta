<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/conexion/init.php';
include 'session.php';

$id_instructor = (int)$_SESSION['documento'];
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 6;
$offset = ($page - 1) * $limit;

// Get total stats
$stats_sql = "
    SELECT 
        COUNT(DISTINCT mf.id_materia_ficha) as total_materias,
        COUNT(DISTINCT mf.id_ficha) as total_fichas
    FROM materia_ficha mf
    WHERE mf.id_instructor = :id
";
$stats_stmt = $conex->prepare($stats_sql);
$stats_stmt->execute(['id' => $id_instructor]);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// 🔽 NUEVO BLOQUE: Contar total de aprendices únicos asignados a fichas del instructor
$aprendices_sql = "
    SELECT COUNT(DISTINCT uf.id_user) AS total_aprendices
    FROM materia_ficha mf
    JOIN fichas f ON mf.id_ficha = f.id_ficha
    JOIN user_ficha uf ON f.id_ficha = uf.id_ficha
    WHERE mf.id_instructor = :id
";
$aprendices_stmt = $conex->prepare($aprendices_sql);
$aprendices_stmt->execute(['id' => $id_instructor]);
$total_aprendices = $aprendices_stmt->fetchColumn();

if ($q === '') {
  // Mostrar fichas paginadas normalmente
  $sql = "
        SELECT 
        mat.materia AS nombre_materia,
        mf.id_ficha AS ficha_materia,
        fo.nombre AS nombre_formacion
        FROM materia_ficha mf
        JOIN materias mat ON mf.id_materia = mat.id_materia
        JOIN fichas f ON mf.id_ficha = f.id_ficha
        JOIN formacion fo ON f.id_formacion = fo.id_formacion
        WHERE mf.id_instructor = :id
        ORDER BY mf.id_materia_ficha ASC
        LIMIT $limit OFFSET $offset
    ";
  $stmt = $conex->prepare($sql);
  $stmt->execute(['id' => $id_instructor]);
  $fichas = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Contar total para paginación
  $total = $conex->prepare("SELECT COUNT(*) FROM materia_ficha WHERE id_instructor = :id");
  $total->execute(['id' => $id_instructor]);
  $total_pages = ceil($total->fetchColumn() / $limit);
  $total_fichas_query = $total->fetchColumn();
} else {
  // Búsqueda por nombre de materia, nombre de formación o número de ficha (sin paginación)
  $sql = "
        SELECT 
        mat.materia AS nombre_materia,
        mf.id_ficha AS ficha_materia,
        fo.nombre AS nombre_formacion
        FROM materia_ficha mf
        JOIN materias mat ON mf.id_materia = mat.id_materia
        JOIN fichas f ON mf.id_ficha = f.id_ficha
        JOIN formacion fo ON f.id_formacion = fo.id_formacion
        WHERE mf.id_instructor = :id 
        AND (CAST(mf.id_ficha AS CHAR) LIKE :q1 
             OR mat.materia LIKE :q2 
             OR fo.nombre LIKE :q3)
        ORDER BY mf.id_materia_ficha ASC
    ";
  $stmt = $conex->prepare($sql);
  $stmt->execute([
    'id' => $id_instructor,
    'q1' => "%$q%",
    'q2' => "%$q%",
    'q3' => "%$q%"
  ]);
  $fichas = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $total_pages = 0; // No hay paginación en búsqueda
  $total_fichas_query = count($fichas);
}

// Generate HTML
$html = '';
if (count($fichas) > 0) {
  foreach ($fichas as $ficha) {
    $html .= '
        <div class="ficha-card">
            <div class="ficha-header">
                <div class="ficha-icon">
                    <i class="bi bi-book"></i>
                </div>
                <div class="ficha-info">
                    <h3 class="ficha-number">' . htmlspecialchars($ficha['nombre_materia']) . '</h3>
                    <p class="ficha-type">Materia Transversal • Ficha ' . htmlspecialchars($ficha['ficha_materia']) . '</p>
                </div>
            </div>
            
            <div class="ficha-content">
                <div class="ficha-formation">
                    <div class="formation-label">Programa de Formación</div>
                    <p class="formation-name">' . htmlspecialchars($ficha['nombre_formacion']) . '</p>
                </div>
            </div>
            
            <div class="ficha-actions">
                <button class="btn-modern btn-outline-modern btn-detalles fichas" data-id="' . $ficha['ficha_materia'] . '">
                    <i class="bi bi-info-circle"></i>
                    Detalles
                </button>
                <a href="../mod/ver_aprendices.php?id_ficha=' . $ficha['ficha_materia'] . '" class="btn-modern btn-primary-modern">
                    <i class="bi bi-people"></i>
                    Aprendices
                </a>
            </div>
        </div>';
  }

  // Add pagination if needed
  if ($q === '' && $total_pages > 1) {
    $html .= '<div class="pagination-container">
            <div class="pagination-modern">';

    for ($i = 1; $i <= $total_pages; $i++) {
      $activeClass = ($i == $page) ? 'active' : '';
      $html .= '<a class="page-btn ' . $activeClass . '" href="#" data-page="' . $i . '">' . $i . '</a>';
    }

    $html .= '</div></div>';
  }
} else {
  $html = '
    <div class="empty-state">
        <i class="bi bi-folder-x empty-icon"></i>
        <h3 class="empty-title">No se encontraron materias</h3>
        <p class="empty-description">' .
    ($q ? 'No hay materias que coincidan con "' . htmlspecialchars($q) . '"' : 'No tienes materias transversales asignadas') .
    '</p>
    </div>';
}

// Return JSON response
echo json_encode([
  'html' => $html,
  'count_text' => '',
  'total_materias' => $stats['total_materias'],
  'total_fichas' => $stats['total_fichas'],
  'total_aprendices' => $total_aprendices // 👈 NUEVO DATO DEVUELTO
]);

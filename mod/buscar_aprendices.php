<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/conexion/init.php';

if ($_SESSION['rol'] !== 3 && $_SESSION['rol'] !== 5) {
  header('Location:' . BASE_URL . '/includes/exit.php?motivo=acceso-denegado');
  exit;
}

$id_ficha = $_POST['id_ficha'] ?? 0;
$query = $_POST['query'] ?? '';
$page = $_POST['page'] ?? 1;
$limit = 6;
$offset = ($page - 1) * $limit;

$params = ['id_ficha' => $id_ficha];
$condicion = "WHERE uf.id_ficha = :id_ficha";

// Add search by document
if (!empty($query)) {
  $condicion .= " AND u.id LIKE :query";
  $params['query'] = "%$query%";
}

// Total matches for pagination
$total_sql = "
    SELECT COUNT(*) 
    FROM user_ficha uf 
    JOIN usuarios u ON uf.id_user = u.id 
    $condicion
";
$total_stmt = $conex->prepare($total_sql);
$total_stmt->execute($params);
$total_aprendices = $total_stmt->fetchColumn();
$total_pages = ceil($total_aprendices / $limit);

// Main query with pagination
$sql = "
    SELECT u.id, u.nombres, u.apellidos, u.correo, u.telefono, u.avatar
    FROM user_ficha uf 
    JOIN usuarios u ON uf.id_user = u.id 
    $condicion 
    LIMIT $limit OFFSET $offset
";
$stmt = $conex->prepare($sql);
$stmt->execute($params);
$aprendices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Generate cards HTML
$tarjetasHTML = '';

if (count($aprendices) === 0) {
  $tarjetasHTML = '
    <div class="empty-state">
        <i class="bi bi-person-x empty-icon"></i>
        <h3 class="empty-title">No se encontraron aprendices</h3>
        <p class="empty-description">Intenta con un número de documento diferente o verifica los filtros aplicados.</p>
    </div>';
} else {
  foreach ($aprendices as $aprendiz) {
    $tarjetasHTML .= '
        <div class="student-card">
            <div class="student-header">
              <div class="student-avatar">
                  <img src="' . BASE_URL . '/' . (empty($aprendiz['avatar']) ? 'uploads/avatar/user.webp' : htmlspecialchars($aprendiz['avatar'])) . '" alt="Avatar del aprendiz" class="rounded-circle" width="60" height="60">
              </div>                
                <div class="student-info">
                    <h3 class="student-name">' . htmlspecialchars($aprendiz['nombres']) . ' ' . htmlspecialchars($aprendiz['apellidos']) . '</h3>
                    <p class="student-id">ID: ' . htmlspecialchars($aprendiz['id']) . '</p>
                </div>
            </div>
            <div class="student-details">
                <div class="detail-item">
                    <i class="bi bi-envelope-fill detail-icon"></i>
                    <span class="detail-text">' . htmlspecialchars($aprendiz['correo']) . '</span>
                </div>
                <div class="detail-item">
                    <i class="bi bi-telephone-fill detail-icon"></i>
                    <span class="detail-text">' . htmlspecialchars($aprendiz['telefono']) . '</span>
                </div>
            </div>
            <button class="view-details-btn" data-id="' . $aprendiz['id'] . '">
                <i class="bi bi-eye"></i>
                Ver detalles completos
            </button>
        </div>';
  }
}

// Generate pagination HTML
$paginacionHTML = '';

if ($total_pages > 1) {
  // Previous button
  if ($page > 1) {
    $paginacionHTML .= '<a class="page-btn" href="#" data-page="' . ($page - 1) . '" title="Página anterior">
            <i class="bi bi-chevron-left"></i>
        </a>';
  } else {
    $paginacionHTML .= '<span class="page-btn" style="opacity: 0.5; cursor: not-allowed;" title="Primera página">
            <i class="bi bi-chevron-left"></i>
        </span>';
  }

  // Page numbers
  $start = max(1, $page - 2);
  $end = min($total_pages, $page + 2);

  if ($start > 1) {
    $paginacionHTML .= '<a class="page-btn" href="#" data-page="1">1</a>';
    if ($start > 2) {
      $paginacionHTML .= '<span class="page-btn" style="cursor: default;">...</span>';
    }
  }

  for ($i = $start; $i <= $end; $i++) {
    $activeClass = ($i == $page) ? 'active' : '';
    $paginacionHTML .= '<a class="page-btn ' . $activeClass . '" href="#" data-page="' . $i . '">' . $i . '</a>';
  }

  if ($end < $total_pages) {
    if ($end < $total_pages - 1) {
      $paginacionHTML .= '<span class="page-btn" style="cursor: default;">...</span>';
    }
    $paginacionHTML .= '<a class="page-btn" href="#" data-page="' . $total_pages . '">' . $total_pages . '</a>';
  }

  // Next button
  if ($page < $total_pages) {
    $paginacionHTML .= '<a class="page-btn" href="#" data-page="' . ($page + 1) . '" title="Página siguiente">
            <i class="bi bi-chevron-right"></i>
        </a>';
  } else {
    $paginacionHTML .= '<span class="page-btn" style="opacity: 0.5; cursor: not-allowed;" title="Última página">
            <i class="bi bi-chevron-right"></i>
        </span>';
  }
}

// Generate total text
$totalText = '';
if ($total_aprendices > 0) {
  $showing_start = ($page - 1) * $limit + 1;
  $showing_end = min($page * $limit, $total_aprendices);
  $totalText = "Mostrando $showing_start-$showing_end de $total_aprendices aprendices";
  if (!empty($query)) {
    $totalText .= " para \"" . htmlspecialchars($query) . "\"";
  }
} else {
  $totalText = "No se encontraron aprendices";
  if (!empty($query)) {
    $totalText .= " para \"" . htmlspecialchars($query) . "\"";
  }
}

// Return as JSON
echo json_encode([
  'tarjetas' => $tarjetasHTML,
  'paginacion' => $paginacionHTML,
  'total_text' => $totalText
]);

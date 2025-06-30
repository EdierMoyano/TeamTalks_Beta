<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/teamtalks/conexion/init.php';

$id_ficha = $_POST['id_ficha'] ?? 0;
$query = $_POST['query'] ?? '';
$page = $_POST['page'] ?? 1;
$limit = 6;
$offset = ($page - 1) * $limit;

$params = ['id_ficha' => $id_ficha];
$condicion = "WHERE uf.id_ficha = :id_ficha";

// Agrega búsqueda por documento
if (!empty($query)) {
  $condicion .= " AND u.id LIKE :query";
  $params['query'] = "%$query%";
}

// Total de coincidencias para paginación
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

// Consulta principal con paginación
$sql = "
    SELECT u.id, u.nombres, u.apellidos, u.correo, u.telefono 
    FROM user_ficha uf 
    JOIN usuarios u ON uf.id_user = u.id 
    $condicion 
    LIMIT $limit OFFSET $offset
";

$stmt = $conex->prepare($sql);
$stmt->execute($params);
$aprendices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Generar HTML de tarjetas
$tarjetasHTML = '';

if (count($aprendices) === 0) {
  $tarjetasHTML = '<div class="empty-state">
    <i class="bi bi-person-x" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
    <h3 style="color: var(--text-muted);">No se encontraron aprendices</h3>
    <p style="color: var(--text-muted);">Intenta con un número de documento diferente.</p>
  </div>';
} else {
  foreach ($aprendices as $aprendiz) {
    $tarjetasHTML .= '
    <div class="card shadow-sm border-0 ficha-aprendiz-card">
      <div class="card-body">
        <div>
          <h5 class="card-title">
            <i class="bi bi-person-circle me-2"></i>' . htmlspecialchars($aprendiz['nombres']) . ' ' . htmlspecialchars($aprendiz['apellidos']) . '
          </h5>
          <p class="card-text">
            <i class="bi bi-person-badge-fill me-1"></i><strong>ID:</strong> ' . htmlspecialchars($aprendiz['id']) . '<br>
            <i class="bi bi-envelope-fill me-1"></i><strong>Correo:</strong> ' . htmlspecialchars($aprendiz['correo']) . '<br>
            <i class="bi bi-telephone-fill me-1"></i><strong>Teléfono:</strong> ' . htmlspecialchars($aprendiz['telefono']) . '
          </p>
        </div>
        <button class="btn btn-detalles" data-id="' . $aprendiz['id'] . '">
          <i class="bi bi-eye-fill me-1"></i> Ver detalles
        </button>
      </div>
    </div>';
  }
}

// Generar HTML de paginación
$paginacionHTML = '';
if ($total_pages > 1) {
  for ($i = 1; $i <= $total_pages; $i++) {
    $activeClass = ($i == $page) ? 'active' : '';
    $paginacionHTML .= '
      <li class="page-item ' . $activeClass . '">
        <a class="page-link" href="#" data-page="' . $i . '">' . $i . '</a>
      </li>';
  }
}

// Devolver como JSON
echo json_encode([
  'tarjetas' => $tarjetasHTML,
  'paginacion' => $paginacionHTML
]);
?>

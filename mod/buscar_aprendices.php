<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/conexion/init.php';


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
  $tarjetasHTML = '<div class="alert alert-warning text-center">No se encontraron aprendices.</div>';
} else {
  foreach ($aprendices as $aprendiz) {
    $tarjetasHTML .= '
    <div class="col-md-6 col-lg-4 d-flex">
      <div class="card shadow-sm border-0 ficha-aprendiz-card w-100">
        <div class="card-body d-flex flex-column justify-content-between">
          <div>
            <h5 class="card-title mb-2" style="color: #0E4A86;">
              <i class="bi bi-person-circle me-2"></i>' . htmlspecialchars($aprendiz['nombres']) . ' ' . htmlspecialchars($aprendiz['apellidos']) . '
            </h5>
            <p class="card-text text-muted small">
              <i class="bi bi-person-badge-fill me-1"></i><strong>ID:</strong> ' . htmlspecialchars($aprendiz['id']) . '<br>
              <i class="bi bi-envelope-fill me-1"></i><strong>Correo:</strong> ' . htmlspecialchars($aprendiz['correo']) . '<br>
              <i class="bi bi-telephone-fill me-1"></i><strong>Teléfono:</strong> ' . htmlspecialchars($aprendiz['telefono']) . '
            </p>
          </div>
          <button class="btn btn-detalles  mt-3 " data-id="' . $aprendiz['id'] . '">
            <i class="bi bi-eye-fill me-1"></i> Ver detalles
          </button>
        </div>
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

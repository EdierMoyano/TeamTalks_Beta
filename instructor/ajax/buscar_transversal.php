<?php
require_once('../../conexion/conexion.php');
include '../../includes/session.php';

$conexion = new database();
$conex = $conexion->connect();

$id_instructor = (int)$_SESSION['documento'];
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 6;
$offset = ($page - 1) * $limit;

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

} else {
    // Búsqueda sin paginación
    $sql = "
        SELECT 
        mat.materia AS nombre_materia,
        mf.id_ficha AS ficha_materia,
        fo.nombre AS nombre_formacion
        FROM materia_ficha mf
        JOIN materias mat ON mf.id_materia = mat.id_materia
        JOIN fichas f ON mf.id_ficha = f.id_ficha
        JOIN formacion fo ON f.id_formacion = fo.id_formacion
        WHERE mf.id_instructor = :id AND mf.id_ficha LIKE :q
        ORDER BY mf.id_materia_ficha ASC
    ";
    $stmt = $conex->prepare($sql);
    $stmt->execute([
        'id' => $id_instructor,
        'q' => "%$q%"
    ]);
    
    $fichas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_pages = 0; // No hay paginación en búsqueda
}

// Generar HTML
if (count($fichas) > 0) {
    foreach ($fichas as $ficha) {
        echo '
        <div class="col-md-4">
          <div class="card shadow bg-light h-100" style="width: 320px;">
            <div class="card-body">
              <h5 class="card-title"> ' . htmlspecialchars($ficha['nombre_materia']) . '</h5>
              <p class="card-text">
                <strong>Ficha:</strong> ' . htmlspecialchars($ficha['ficha_materia']) . '<br />
                <strong>Formación:</strong> '. htmlspecialchars($ficha['nombre_formacion']) . '
              </p>
            </div>
          </div>
        </div>';
    }

    // Solo mostrar paginación si no estás buscando
    if ($q === '' && $total_pages > 1) {
        echo '<div class="d-flex justify-content-center mt-4"><nav><ul class="pagination">';
        for ($i = 1; $i <= $total_pages; $i++) {
            echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '">
                    <a class="page-link" href="#" onclick="buscarFicha(' . $i . ')">' . $i . '</a>
                  </li>';
        }
        echo '</ul></nav></div>';
    }
} else {
    echo '<div class="col-12 text-center"><p>No se encontraron coincidencias.</p></div>';
}
?>

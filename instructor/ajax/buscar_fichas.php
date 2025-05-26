<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/teamtalks/conexion/init.php';
include 'session.php';



$id_instructor = (int)$_SESSION['documento'];
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 6;
$offset = ($page - 1) * $limit;

if ($q === '') {
    // Mostrar fichas paginadas normalmente
    $sql = "
        SELECT f.id_ficha, fo.nombre AS nombre_formacion
        FROM fichas f
        JOIN formacion fo ON f.id_formacion = fo.id_formacion
        WHERE f.id_instructor = :id
        ORDER BY f.id_ficha ASC
        LIMIT $limit OFFSET $offset
    ";
    $stmt = $conex->prepare($sql);
    $stmt->execute(['id' => $id_instructor]);
    
    $fichas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Contar total para paginación
    $total = $conex->prepare("SELECT COUNT(*) FROM fichas WHERE id_instructor = :id");
    $total->execute(['id' => $id_instructor]);
    $total_pages = ceil($total->fetchColumn() / $limit);

} else {
    // Búsqueda sin paginación
    $sql = "
        SELECT f.id_ficha, fo.nombre AS nombre_formacion
        FROM fichas f
        JOIN formacion fo ON f.id_formacion = fo.id_formacion
        WHERE f.id_instructor = :id AND f.id_ficha LIKE :q
        ORDER BY f.id_ficha ASC
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
          <div class="card shadow bg-light" style="width: 320px; height: 165px">
            <div class="card-body">
              <h5 class="card-title">Ficha: ' . htmlspecialchars($ficha['id_ficha']) . '</h5>
              <p class="card-text">
                <strong>Formación:</strong> ' . htmlspecialchars($ficha['nombre_formacion']) . '<br />
                <div class="d-flex justify-content-around">

                <button class="fichas btn btn-detalles" data-id="' . $ficha['id_ficha'] . '">Detalles</button>


                    <a href="mod/ver_aprendices.php?id_ficha=' . $ficha['id_ficha'] . '"><button class="fichas btn btn-detalles">Aprendices</button></a>


                </div>
              </p>
            </div>
          </div>
        </div>';
    }

    // Solo mostrar paginación si no se está buscando
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

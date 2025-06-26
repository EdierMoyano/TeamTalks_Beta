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

?>

<?php if (count($fichas) > 0): ?>
  <div class="row g-4">
    <?php foreach ($fichas as $ficha): ?>
      <div class="col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm ficha-card h-100" style="transition: transform 0.2s ease-in-out;">
          <div class="card-body">
            <h5 class="card-title mb-2" style="color: #0E4A86;">
              <i class="bi bi-journal-code me-1" ></i><?= htmlspecialchars($ficha['nombre_materia']) ?>
            </h5>
            <p class="card-text text-muted">
              <strong>Ficha:</strong><?= htmlspecialchars($ficha['ficha_materia']) ?>
            </p>
            <p class="card-text text-muted">
              <strong>Formacion:</strong><?= htmlspecialchars($ficha['nombre_formacion']) ?>
            </p>
            <div class="d-flex justify-content-between mt-4">
              <button class="btn btn-detalles btn-outline-primary w-100 me-2 fichas" data-id="<?= $ficha['ficha_materia'] ?>">
                <i class="bi bi-info-circle"></i> Detalles
              </button>
              <a href="../mod/ver_aprendices.php?id_ficha=<?= $ficha['ficha_materia'] ?>" class="btn w-100" style="background-color: #0E4A86; color: white">
                <i class="bi bi-people"></i> Aprendices
              </a>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if ($q === '' && $total_pages > 1): ?>
    <div class="d-flex justify-content-center mt-4">
      <nav>
        <ul class="pagination">
          <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <li class="page-item <?= ($i == $page ? 'active' : '') ?>">
              <a class="page-link" href="#" onclick="buscarFicha(<?= $i ?>)"><?= $i ?></a>
            </li>
          <?php endfor; ?>
        </ul>
      </nav>
    </div>
  <?php endif; ?>

<?php else: ?>
  <div class="col-12 text-center">
    <p class="text-muted">No se encontraron coincidencias.</p>
  </div>
<?php endif; ?>
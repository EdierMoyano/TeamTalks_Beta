<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/teamtalks/conexion/init.php';
include 'session.php';

$id_instructor = (int)$_SESSION['documento'];

$sql = "
    SELECT DISTINCT f.id_ficha, fo.nombre AS nombre_formacion
    FROM materia_ficha mf
    JOIN fichas f ON mf.id_ficha = f.id_ficha
    JOIN formacion fo ON f.id_formacion = fo.id_formacion
    WHERE mf.id_instructor = :id
    ORDER BY f.id_ficha ASC
";

$stmt = $conex->prepare($sql);
$stmt->execute(['id' => $id_instructor]);
$fichas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php if (count($fichas) > 0): ?>
  <div class="row g-3">
    <?php foreach ($fichas as $f): ?>
      <div class="col-12">
        <div
          class="card shadow-sm ficha-item"
          data-id="<?= $f['id_ficha'] ?>"
          style="cursor: pointer; border-left: 5px solid #0E4A86; transition: transform 0.2s ease;"
          onmouseover="this.style.transform = 'scale(1.02)'; this.style.boxShadow = '0 8px 20px rgba(74,144,226,0.3)';"
          onmouseout="this.style.transform = 'scale(1)'; this.style.boxShadow = '0 1px 6px rgba(0,0,0,0.1)';">
          <div class="card-body">
            <h5 class="card-title mb-2" style="color: #0E4A86;">Formación técnica</h5>
            <p class="card-text mb-1"><strong>Ficha:</strong> <?= htmlspecialchars($f['id_ficha']) ?></p>
            <p class="card-text text-muted"><small>Formación: <?= htmlspecialchars($f['nombre_formacion']) ?></small></p>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php else: ?>
  <div class="text-center text-muted py-4">No tienes fichas asignadas.</div>
<?php endif; ?>
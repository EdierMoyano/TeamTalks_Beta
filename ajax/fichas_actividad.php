<?php
$esLocal = strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['DOCUMENT_ROOT'], 'htdocs') !== false;

// Ruta dinámica hacia init.php
$rutaInit = $esLocal
    ? $_SERVER['DOCUMENT_ROOT'] . '/teamtalks/conexion/init.php'
    : $_SERVER['DOCUMENT_ROOT'] . '/conexion/init.php';

require_once $rutaInit;include 'session.php';

$id_instructor = (int)$_SESSION['documento'];

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
";

$stmt = $conex->prepare($sql);
$stmt->execute(['id' => $id_instructor]);
$fichas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php if(count($fichas) > 0): ?>
  <div class="row g-3">
    <?php foreach($fichas as $f): ?>
      <div class="col-12">
        <div 
          class="card shadow-sm ficha-item" 
          data-id="<?= $f['ficha_materia'] ?>" 
          style="cursor: pointer; border-left: 5px solid #0E4A86; transition: transform 0.2s ease;"
          onmouseover="this.style.transform = 'scale(1.02)'; this.style.boxShadow = '0 8px 20px rgba(74,144,226,0.3)';"
          onmouseout="this.style.transform = 'scale(1)'; this.style.boxShadow = '0 1px 6px rgba(0,0,0,0.1)';"
        >
          <div class="card-body">
            <h5 class="card-title mb-2" style="color: #0E4A86;"><?= htmlspecialchars($f['nombre_materia']) ?></h5>
            <p class="card-text mb-1"><strong>Ficha:</strong> <?= htmlspecialchars($f['ficha_materia']) ?></p>
            <p class="card-text text-muted"><small>Formación: <?= htmlspecialchars($f['nombre_formacion']) ?></small></p>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php else: ?>
  <div class="text-center text-muted py-4">No tienes fichas asignadas.</div>
<?php endif; ?>




<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/teamtalks/conexion/init.php';

$id = $_POST['id'];


$sql = "
SELECT 
    u.id, u.nombres, u.apellidos, u.correo, u.telefono,
    f.id_ficha AS ficha, fo.nombre AS formacion,
    tf.tipo_formacion AS tipo_formacion, tipof.tipo_ficha AS tipo_ficha
FROM usuarios u
JOIN user_ficha uf ON uf.id_user = u.id
JOIN fichas f ON f.id_ficha = uf.id_ficha
JOIN formacion fo ON fo.id_formacion = f.id_formacion
JOIN tipo_formacion tf ON tf.id_tipo_formacion = fo.id_tipo_formacion
JOIN tipo_ficha tipof ON tipof.id_tipo_ficha = f.id_tipo_ficha
WHERE u.id = :id
";

$stmt = $conex->prepare($sql);
$stmt->execute(['id' => $id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if ($data): ?>
    <div class="card shadow-sm border-0">
        <div class="card-header text-white d-flex justify-content-between align-items-center" style="background-color: #0E4A86">
            <h5 class="mb-0">📋 Detalles del Aprendiz</h5>
            <span class="badge bg-light text-primary">ID: <?= htmlspecialchars($data['id']) ?></span>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <h6 class="text-muted mb-1">Nombre Completo</h6>
                <p class="fw-semibold fs-5"><?= htmlspecialchars($data['nombres'] . ' ' . $data['apellidos']) ?></p>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <h6 class="text-muted mb-1">Correo Electrónico</h6>
                    <p class="mb-0"><?= htmlspecialchars($data['correo']) ?></p>
                </div>
                <div class="col-md-6 mb-3">
                    <h6 class="text-muted mb-1">Teléfono</h6>
                    <p class="mb-0"><?= htmlspecialchars($data['telefono']) ?></p>
                </div>
            </div>

            <hr>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <h6 class="text-muted mb-1">Ficha</h6>
                    <span class="badge bg-success fs-6"><?= htmlspecialchars($data['ficha']) ?></span>
                </div>
                <div class="col-md-6 mb-3">
                    <h6 class="text-muted mb-1">Tipo de Ficha</h6>
                    <span class="badge bg-info text-dark fs-6"><?= htmlspecialchars($data['tipo_ficha']) ?></span>
                </div>
            </div>

            <div class="mb-3">
                <h6 class="text-muted mb-1">Formación</h6>
                <p class="mb-0 fw-medium"><?= htmlspecialchars($data['formacion']) ?></p>
            </div>

            <div class="mb-3">
                <h6 class="text-muted mb-1">Tipo de Formación</h6>
                <span class="badge bg-secondary"><?= htmlspecialchars($data['tipo_formacion']) ?></span>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-warning">No se encontró información del aprendiz.</div>
<?php endif; ?>
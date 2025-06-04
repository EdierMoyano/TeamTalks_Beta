<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/teamtalks/conexion/init.php';
include 'session.php';

// Obtener el ID de la ficha desde la URL, asegurándose de que sea un entero
$id_ficha = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Consulta SQL para obtener todos los detalles de la ficha
$sql = "
    SELECT 
        f.id_ficha, 
        fo.nombre AS nombre_formacion, 
        f.id_ambiente,
        u.nombres AS nom_instru, 
        u.apellidos AS ape_instru, 
        j.jornada, 
        tf.tipo_ficha,
        tfo.tipo_formacion,
        f.id_trimestre
    FROM fichas f
    JOIN formacion fo ON f.id_formacion = fo.id_formacion
    JOIN tipo_formacion tfo ON fo.id_tipo_formacion = tfo.id_tipo_formacion
    JOIN usuarios u ON f.id_instructor = u.id
    JOIN jornada j ON f.id_jornada = j.id_jornada
    JOIN tipo_ficha tf ON f.id_tipo_ficha = tf.id_tipo_ficha
    WHERE f.id_ficha = :id
";

$stmt = $conex->prepare($sql);
$stmt->execute(['id' => $id_ficha]);
$ficha = $stmt->fetch(PDO::FETCH_ASSOC);


// Si se encuentra la ficha, mostrar los datos en una tabla para el modal
if ($ficha): ?>
    <div class="card shadow-sm border-0">
        <div class="card-header text-white d-flex justify-content-between align-items-center" style="background-color: #0E4A86;">
            <h5 class="mb-0">📘 Detalles de la Ficha</h5>
            <span class="badge bg-light" style="color: #0E4A86">Ficha #<?= htmlspecialchars($ficha['id_ficha']) ?></span>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <h6 class="text-muted">Nombre del Programa</h6>
                <p class="fs-5 fw-semibold"><?= htmlspecialchars($ficha['nombre_formacion']) ?></p>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <h6 class="text-muted mb-1">Ambiente</h6>
                    <span class="badge bg-primary"><?= htmlspecialchars($ficha['id_ambiente']) ?></span>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted mb-1">Trimestre</h6>
                    <span class="badge bg-secondary"><?= htmlspecialchars($ficha['id_trimestre']) ?></span>
                </div>
            </div>

            <hr>

            <div class="row g-3">
                <div class="col-md-6">
                    <h6 class="text-muted mb-1">Instructor Gerente</h6>
                    <p class="mb-0"><?= htmlspecialchars($ficha['nom_instru'] . ' ' . $ficha['ape_instru']) ?></p>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted mb-1">Jornada</h6>
                    <span class="badge bg-primary"><?= htmlspecialchars($ficha['jornada']) ?></span>
                </div>
            </div>

            <div class="row g-3 mt-3">
                <div class="col-md-6">
                    <h6 class="text-muted mb-1">Tipo de Ficha</h6>
                    <span class="badge bg-secondary"><?= htmlspecialchars($ficha['tipo_ficha']) ?></span>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted mb-1">Tipo de Formación</h6>
                    <span class="badge" style="background-color: #0E4A86;"><?= htmlspecialchars($ficha['tipo_formacion']) ?></span>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-warning">No se encontraron detalles de la ficha.</div>
<?php endif; ?>

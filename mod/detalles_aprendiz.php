<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/conexion/init.php';


$id = $_POST['id'];


$sql = "
SELECT 
    u.id, u.nombres, u.apellidos, u.correo, u.telefono, u.avatar,
    f.id_ficha AS ficha, fo.nombre AS formacion,
    tf.tipo_formacion AS tipo_formacion, tipof.tipo_ficha AS tipo_ficha, e.estado
FROM usuarios u
JOIN user_ficha uf ON uf.id_user = u.id
JOIN fichas f ON f.id_ficha = uf.id_ficha
JOIN formacion fo ON fo.id_formacion = f.id_formacion
JOIN tipo_formacion tf ON tf.id_tipo_formacion = fo.id_tipo_formacion
JOIN tipo_ficha tipof ON tipof.id_tipo_ficha = f.id_tipo_ficha
JOIN estado e ON e.id_estado = uf.id_estado
WHERE u.id = :id
";

$stmt = $conex->prepare($sql);
$stmt->execute(['id' => $id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if ($data): ?>
    <div class="card shadow-sm border-0">
        <div class="card-header text-white d-flex justify-content-between align-items-center" style="background-color: #0E4A86">
            <h5 class="mb-0">📋 Detalles del Aprendiz</h5>
            <span class="badge bg-light" style="color: #0E4A86;">ID: <?= htmlspecialchars($data['id']) ?></span>
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
                    <img src="<?= BASE_URL ?>/<?= empty($data['avatar']) ? 'uploads/avatar/user.webp' : htmlspecialchars($user['avatar']) ?>" alt="Avatar" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid #ffffff; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); position: absolute; top: 100px; right: 100px">

                </div>

                <div class="col-md-6 mb-3">
                </div>
            </div>

            <hr>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <h6 class="text-muted mb-1">Ficha</h6>
                    <span class="badge bg-primary fs-6"><?= htmlspecialchars($data['ficha']) ?></span>
                </div>
                <div class="col-md-6 mb-3">
                    <h6 class="text-muted mb-1">Modalidad</h6>
                    <span class="badge bg-secondary fs-6"><?= htmlspecialchars($data['tipo_ficha']) ?></span>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="mb-3">
                        <h6 class="text-muted mb-1">Formación</h6>
                        <p class="mb-0 fw-medium"><?= htmlspecialchars($data['formacion']) ?></p>
                    </div>

                    <div class="mb-3">
                        <h6 class="text-muted mb-1">Tipo de Formación</h6>
                        <span class="badge fs-6" style="background-color: #0E4A86;"><?= htmlspecialchars($data['tipo_formacion']) ?></span>
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <div class="mb-3">
                        <h6 class="text-muted mb-1">Estado</h6>
                        <span class="badge bg-primary fs-6"><?= htmlspecialchars($data['estado']) ?></span>
                    </div>

                    <div class="mb-3">
                        <h6 class="text-muted mb-1">Entregas del aprendiz</h6>
                        <a href="actividades_aprendiz.php?id=<?= $data['id'] ?>"><span class="fs-6" style="color: #0E4A86;">Ver aquí las actividades del aprendiz <i class="bi bi-box-arrow-up-right"></i></span></a>
                    </div>

                </div>



            </div>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-warning">No se encontró información del aprendiz.</div>
<?php endif; ?>
<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/conexion/init.php';

if ($_SESSION['rol'] !== 3 && $_SESSION['rol'] !== 5) {
    header('Location:' . BASE_URL . '/includes/exit.php?motivo=acceso-denegado');
    exit;
}

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

<div class="detail-card">
    <div class="detail-header">
        <div class="header-content">
            <h2 class="student-title">
                <i class="bi bi-person-circle"></i>
                Información del Aprendiz
            </h2>
            <span class="student-badge">ID: <?= htmlspecialchars($data['id']) ?></span>
        </div>
    </div>
    
    <div class="detail-body">
        <div class="student-main-info">
            <img 
                class="student-avatar-large" 
                src="<?= BASE_URL ?>/<?= empty($data['avatar']) ? 'uploads/avatar/user.webp' : htmlspecialchars($data['avatar']) ?>" 
                alt="Avatar de <?= htmlspecialchars($data['nombres']) ?>"
            >
            <h3 class="student-full-name">
                <?= htmlspecialchars($data['nombres'] . ' ' . $data['apellidos']) ?>
            </h3>
        </div>

        <div class="info-grid">
            <!-- Contact Information -->
            <div class="info-section">
                <h4 class="section-title">
                    <i class="bi bi-person-lines-fill section-icon"></i>
                    Información de Contacto
                </h4>
                <div class="info-item">
                    <div class="info-label">Correo Electrónico</div>
                    <div class="info-value"><?= htmlspecialchars($data['correo']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Teléfono</div>
                    <div class="info-value"><?= htmlspecialchars($data['telefono']) ?></div>
                </div>
            </div>

            <!-- Academic Information -->
            <div class="info-section">
                <h4 class="section-title">
                    <i class="bi bi-mortarboard-fill section-icon"></i>
                    Información Académica
                </h4>
                <div class="info-item">
                    <div class="info-label">Ficha</div>
                    <div class="info-value">
                        <span class="status-badge"><?= htmlspecialchars($data['ficha']) ?></span>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Modalidad</div>
                    <div class="info-value"><?= htmlspecialchars($data['tipo_ficha']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Estado</div>
                    <div class="info-value">
                        <span class="status-badge"><?= htmlspecialchars($data['estado']) ?></span>
                    </div>
                </div>
            </div>

            <!-- Training Information -->
            <div class="info-section">
                <h4 class="section-title">
                    <i class="bi bi-book-fill section-icon"></i>
                    Información de Formación
                </h4>
                <div class="info-item">
                    <div class="info-label">Programa de Formación</div>
                    <div class="info-value"><?= htmlspecialchars($data['formacion']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Tipo de Formación</div>
                    <div class="info-value">
                        <span class="status-badge"><?= htmlspecialchars($data['tipo_formacion']) ?></span>
                    </div>
                </div>
            </div>

            <!-- Activities -->
            <div class="info-section">
                <h4 class="section-title">
                    <i class="bi bi-clipboard-check-fill section-icon"></i>
                    Actividades y Entregas
                </h4>
                <div class="info-item">
                    <div class="info-label">Gestión de Actividades</div>
                    <div class="info-value">
                        <a href="actividades_aprendiz.php?id=<?= $data['id'] ?>" class="activities-link">
                            <i class="bi bi-box-arrow-up-right"></i>
                            Ver todas las actividades
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
    <div class="empty-state">
        <i class="bi bi-exclamation-triangle empty-icon"></i>
        <h3 class="empty-title">Información no encontrada</h3>
        <p class="empty-description">No se encontró información del aprendiz solicitado.</p>
    </div>
<?php endif; ?>
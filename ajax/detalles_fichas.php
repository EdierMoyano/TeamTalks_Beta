<?php
if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['DOCUMENT_ROOT'], 'htdocs') !== false) {
  require_once $_SERVER['DOCUMENT_ROOT'] . '/teamtalks/conexion/init.php';
} else {
  require_once $_SERVER['DOCUMENT_ROOT'] . '/conexion/init.php';
}include 'session.php';

// Get ficha ID from URL, ensuring it's an integer
$id_ficha = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// SQL query to get all ficha details
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
        f.id_trimestre,
        COUNT(uf.id_user) as total_aprendices
    FROM fichas f
    JOIN formacion fo ON f.id_formacion = fo.id_formacion
    JOIN tipo_formacion tfo ON fo.id_tipo_formacion = tfo.id_tipo_formacion
    JOIN usuarios u ON f.id_instructor = u.id
    JOIN jornada j ON f.id_jornada = j.id_jornada
    JOIN tipo_ficha tf ON f.id_tipo_ficha = tf.id_tipo_ficha
    LEFT JOIN user_ficha uf ON f.id_ficha = uf.id_ficha
    WHERE f.id_ficha = :id
    GROUP BY f.id_ficha
";

$stmt = $conex->prepare($sql);
$stmt->execute(['id' => $id_ficha]);
$ficha = $stmt->fetch(PDO::FETCH_ASSOC);

// If ficha is found, show data in a modern card design
if ($ficha): ?>

<div class="detail-card-modern">
    <div class="detail-header-modern">
        <div class="header-content-modern">
            <h2 class="ficha-title-modern">
                <i class="bi bi-journal-bookmark"></i>
                Información de la Ficha
            </h2>
            <span class="ficha-badge-modern">Ficha #<?= htmlspecialchars($ficha['id_ficha']) ?></span>
        </div>
    </div>
    
    <div class="detail-body-modern">
        <div class="program-info-modern">
            <h3 class="program-name-modern">
                <?= htmlspecialchars($ficha['nombre_formacion']) ?>
            </h3>
            <p class="program-type-modern">
                <?= htmlspecialchars($ficha['tipo_formacion']) ?> • <?= htmlspecialchars($ficha['tipo_ficha']) ?>
            </p>
        </div>

        <div class="info-grid-modern">
            <!-- Academic Information -->
            <div class="info-section-modern">
                <h4 class="section-title-modern">
                    <i class="bi bi-mortarboard-fill section-icon-modern"></i>
                    Información Académica
                </h4>
                <div class="info-item-modern">
                    <div class="info-label-modern">Ambiente</div>
                    <div class="info-value-modern">
                        <span class="status-badge-modern"><?= htmlspecialchars($ficha['id_ambiente']) ?></span>
                    </div>
                </div>
                <div class="info-item-modern">
                    <div class="info-label-modern">Trimestre Actual</div>
                    <div class="info-value-modern">
                        <span class="status-badge-modern secondary-badge-modern"><?= htmlspecialchars($ficha['id_trimestre']) ?></span>
                    </div>
                </div>
                <div class="info-item-modern">
                    <div class="info-label-modern">Jornada</div>
                    <div class="info-value-modern">
                        <span class="status-badge-modern"><?= htmlspecialchars($ficha['jornada']) ?></span>
                    </div>
                </div>
            </div>

            <!-- Instructor Information -->
            <div class="info-section-modern">
                <h4 class="section-title-modern">
                    <i class="bi bi-person-badge-fill section-icon-modern"></i>
                    Instructor Responsable
                </h4>
                <div class="info-item-modern">
                    <div class="info-label-modern">Nombre Completo</div>
                    <div class="info-value-modern"><?= htmlspecialchars($ficha['nom_instru'] . ' ' . $ficha['ape_instru']) ?></div>
                </div>
                <div class="info-item-modern">
                    <div class="info-label-modern">Rol</div>
                    <div class="info-value-modern">
                        <span class="status-badge-modern highlight-badge-modern">Instructor Gerente</span>
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <div class="stats-section-modern">
                <h4 class="stats-title-modern">
                    <i class="bi bi-people-fill section-icon-modern"></i>
                    Aprendices Matriculados
                </h4>
                <span class="stats-number-modern"><?= htmlspecialchars($ficha['total_aprendices']) ?></span>
                <span class="stats-label-modern">Total de Aprendices</span>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
    <div class="empty-state">
        <i class="bi bi-exclamation-triangle empty-icon"></i>
        <h3 class="empty-title">Información no encontrada</h3>
        <p class="empty-description">No se encontraron detalles de la ficha solicitada.</p>
    </div>
<?php endif; ?>
<?php
$esLocal = strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['DOCUMENT_ROOT'], 'htdocs') !== false;

// Ruta dinámica hacia init.php
$rutaInit = $esLocal
    ? $_SERVER['DOCUMENT_ROOT'] . '/teamtalks/conexion/init.php'
    : $_SERVER['DOCUMENT_ROOT'] . '/conexion/init.php';

require_once $rutaInit;
$id_actividad = $_GET['id_actividad'];
$id_user = $_GET['id_user'];

$stmt = $conex->prepare(
    "SELECT a.titulo, au.contenido, au.archivo1, au.archivo2, au.archivo3, au.fecha_entrega, au.nota,
            au.id_user, au.comentario_inst, au.id_estado_actividad,
            u.nombres, u.apellidos, e.estado AS estado
     FROM actividades_user au
     INNER JOIN actividades a ON a.id_actividad = au.id_actividad
     INNER JOIN usuarios u ON u.id = au.id_user
     INNER JOIN estado e ON e.id_estado = au.id_estado_actividad
     WHERE au.id_actividad = ? AND au.id_user = ?"
);
$stmt->execute([$id_actividad, $id_user]);
$data = $stmt->fetch();

if ($data):
?>

    <style>
        .card-minimal {
            border: 1px solid #dee2e6;
            background-color: #fff;
            border-radius: 0.5rem;
            padding: 0.5rem 0.75rem;
            font-family: "Segoe UI", sans-serif;
            font-size: 0.82rem;
            max-width: 620px;
        }

        .section-title {
            font-weight: 600;
            color: #333;
            font-size: 0.82rem;
            margin-bottom: 0.25rem;
        }

        .section-content {
            background-color: #f8f9fa;
            border-radius: 0.5rem;
            padding: 0.5rem;
            color: #444;
            white-space: pre-wrap;
            font-size: 0.82rem;
            max-height: 160px;
            overflow-y: auto;
            text-align: left;
        }

        .nota {
            background-color: #0E4A86;
            color: #fff;
            border: none;
            padding: 0.4rem 1rem;
            border-radius: 999px;
            font-size: 0.82rem;
        }

        .nota:hover {
            background-color: #0b3a6b;
        }

        .form-control {
            font-size: 0.82rem;
            padding: 0.35rem 0.6rem;
        }

        .form-control:focus {
            border-color: #0E4A86;
            box-shadow: 0 0 0 0.1rem rgba(14, 74, 134, 0.25);
        }

        .btn-outline-link {
            font-size: 0.75rem;
            padding: 0.3rem 0.6rem;
            border-radius: 999px;
            border: 1px solid #0E4A86;
            color: #0E4A86;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            transition: all 0.3s ease;
            max-width: 280px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .btn-outline-link:hover {
            background-color: #0E4A86;
            color: white;
        }

        small.text-muted {
            font-size: 0.75rem;
        }

        h4,
        h5 {
            margin-bottom: 0.3rem !important;
            font-size: 0.98rem;
            color: #0E4A86;
        }

        .archivo-nombre {
            max-width: 180px;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
            display: inline-block;
            vertical-align: middle;
        }

        .mb-3,
        .mb-2 {
            margin-bottom: 0.5rem !important;
        }

        #comentario {
            max-height: 80px;
            resize: vertical;
        }

        #respuesta-nota {
            font-size: 0.78rem;
        }
    </style>


    <div class="card-minimal shadow-sm">
        <!-- Cabecera -->
        <div class="mb-3">
            <h4><i class="bi bi-person-circle me-2"></i><?= htmlspecialchars($data['nombres'] . ' ' . $data['apellidos']) ?></h4>
            <h5><i class="bi bi-journal-text me-2"></i><?= htmlspecialchars($data['titulo']) ?></h5>
            <small class="text-muted me-3">
                <i class="bi bi-clock me-1"></i>Entrega: <?= htmlspecialchars($data['fecha_entrega']) ?>
            </small>
            <small class="text-muted">
                <i class="bi bi-info-circle me-1"></i>Estado: <strong><?= htmlspecialchars($data['estado']) ?></strong>
            </small>
        </div>

        <!-- Contenido -->
        <div class="mb-2">
            <div class="section-title"><i class="bi bi-pencil-square me-2 text-secondary"></i>Contenido</div>
            <div class="section-content">
                <?= $data['contenido'] ? nl2br(htmlspecialchars($data['contenido'])) : '<span class="text-muted">Sin contenido entregado</span>' ?>
            </div>
        </div>

        <?php
        $archivos = [];
        foreach (['archivo1', 'archivo2', 'archivo3'] as $archivoKey) {
            if (!empty($data[$archivoKey])) {
                $archivos[] = $data[$archivoKey];
            }
        }
        ?>

        <?php if (!empty($archivos)): ?>
            <div class="mb-2">
                <div class="section-title">
                    <i class="bi bi-paperclip me-2 text-secondary"></i>Archivos Adjuntos
                </div>
                <div class="d-flex flex-wrap gap-3">
                    <?php foreach ($archivos as $archivo): ?>
                        <a href="../uploads/<?= $archivo ?>" target="_blank" class="btn-outline-link flex-fill text-truncate" style="flex: 1 1 calc(33.333% - 0.5rem); min-width: 150px;">
                            <i class="bi bi-file-earmark-arrow-down"></i>
                            <span class="archivo-nombre"><?= htmlspecialchars($archivo) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>



        <!-- Nota y Comentario -->
        <form id="form-nota">
            <div class="mb-2">
                <label for="nota" class="form-label section-title">
                    <i class="bi bi-clipboard-check me-2 text-secondary"></i>Nota (0,0 - 5,0)
                </label>
                <input
                    type="number"
                    name="nota"
                    id="nota"
                    class="form-control rounded-3"
                    step="0.1"
                    min="0"
                    max="5"
                    placeholder="Ej: 4,5"
                    value="<?= htmlspecialchars($data['nota']) ?>"
                    required>
            </div>

            <div class="mb-3">
                <label for="comentario" class="form-label section-title">
                    <i class="bi bi-chat-left-dots me-2 text-secondary"></i>Comentario privado
                </label>
                <textarea
                    name="comentario_inst"
                    id="comentario"
                    class="form-control rounded-3"
                    rows="2"
                    placeholder="Comentario para el aprendiz"
                    style="max-height: 100px;"><?= htmlspecialchars($data['comentario_inst']) ?></textarea>
            </div>

            <div id="respuesta-nota" class="text-start mb-2 text-success small"></div>

            <div class="text-end">
                <button type="submit" class="nota btn">
                    <i class="bi bi-check2-circle me-1"></i>Guardar Nota
                </button>
            </div>
        </form>
    </div>

<?php else: ?>
    <div class="alert alert-warning text-center">
        <i class="bi bi-exclamation-circle-fill"></i> No se encontró la entrega del aprendiz.
    </div>
<?php endif; ?>


<script>
    document.getElementById('formCalificarActividad').addEventListener('submit', function(e) {
        e.preventDefault();

        const nota = parseFloat(document.getElementById('nota').value);
        const comentario = document.getElementById('comentario_inst').value;
        const idActividadUser = this.querySelector('input[name="id_actividad_user"]').value;

        if (isNaN(nota)) {
            alert('Por favor ingresa una nota válida.');
            return;
        }

        const formData = new FormData();
        formData.append('nota', nota);
        formData.append('comentario_inst', comentario);
        formData.append('id_actividad_user', idActividadUser);

        fetch('../ajax/guardar_calificacion.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const mensajeDiv = document.getElementById('mensajeCalificacion');
                if (data.success) {
                    mensajeDiv.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
                    document.getElementById('nota').disabled = true;
                    document.getElementById('comentario_inst').disabled = true;
                } else {
                    mensajeDiv.innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
                }
            })
            .catch(() => {
                document.getElementById('mensajeCalificacion').innerHTML = '<div class="alert alert-danger">Error al calificar.</div>';
            });
    });
</script>
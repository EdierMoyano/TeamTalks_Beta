<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/conexion/init.php';
include 'session.php';


$id_actividad = $_GET['id_actividad'];
$id_user = $_GET['id_user'];

$stmt = $conex->prepare(
    "SELECT a.titulo, au.contenido, au.archivo1, au.archivo2, au.archivo3, au.fecha_entrega, au.nota,
            au.id_user, au.comentario_inst, au.id_estado_actividad, au.id_actividad_user,
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
            font-size: 1rem;
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
            font-size: 0.90rem;
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
            font-size: 0.8rem;
        }

        .nota:hover {
            background-color: #0b3a6b;
        }

        .form-control {
            font-size: 0.90rem;
            padding: 0.35rem 0.6rem;
        }

        .form-control:focus {
            border-color: #0E4A86;
            box-shadow: 0 0 0 0.1rem rgba(14, 74, 134, 0.25);
        }

        .btn-outline-link {
            font-size: 0.85rem;
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
            font-size: 0.87rem;
        }

        .aprendiz {
            margin-bottom: 0.3rem !important;
            font-size: 1rem;
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

        .form-range {
            width: 70%;
            height: 0.6rem;
            padding: 0;
            background-color: transparent;
            appearance: none;
        }

        .form-range:focus {
            outline: none;
        }

        /* Barra de fondo */
        .form-range::-webkit-slider-runnable-track {
            height: 6px;
            background: #dee2e6;
            border-radius: 3px;
        }

        .form-range::-moz-range-track {
            height: 6px;
            background: #dee2e6;
            border-radius: 3px;
        }

        /* Thumb */
        .form-range::-webkit-slider-thumb {
            appearance: none;
            height: 16px;
            width: 16px;
            border-radius: 50%;
            background: #0E4A86;
            cursor: pointer;
            margin-top: -5px;
            transition: background 0.3s;
        }

        .form-range::-moz-range-thumb {
            height: 16px;
            width: 16px;
            border-radius: 50%;
            background: #0E4A86;
            border: none;
            cursor: pointer;
        }

        .form-range::-webkit-slider-thumb:hover,
        .form-range::-moz-range-thumb:hover {
            background: #0b3a6b;
        }

        #nota-valor {
            font-weight: bold;
            color: #0E4A86;
        }
    </style>


    <div class="card-minimal shadow-sm">
        <!-- Cabecera -->
        <div class="mb-3">
            <h3 class="aprendiz"><i class="bi bi-person-circle me-2"></i><?= htmlspecialchars($data['nombres'] . ' ' . $data['apellidos']) ?></h3>
            <h4 class="aprendiz"><i class="bi bi-journal-text me-2"></i><?= htmlspecialchars($data['titulo']) ?></h4>
            <small class="text-muted me-3">
                <i class="bi bi-clock me-1"></i>Entrega: <?= htmlspecialchars($data['fecha_entrega']) ?>
            </small>
            <small class="text-muted">
                <i class="bi bi-info-circle me-1"></i>Estado: <strong><?= htmlspecialchars($data['estado']) ?></strong>
            </small>
        </div>

        <!-- Contenido -->
        <div class="mb-4">
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
            <input type="hidden" name="id_actividad_user" value="<?= $data['id_actividad_user'] ?>">

            <div class="mb-2">
                <label for="nota" class="form-label section-title">
                    <i class="bi bi-clipboard-check me-2 text-secondary"></i>
                    Nota: <span id="nota-valor"><?= htmlspecialchars($data['nota'] ?? 3.0) ?></span>
                </label><br>
                <input
                    type="range"
                    class="form-range"
                    min="0"
                    max="5"
                    step="0.1"
                    id="nota"
                    name="nota"
                    value="<?= htmlspecialchars($data['nota'] ?? 3.0) ?>"
                    oninput="document.getElementById('nota-valor').innerText = this.value" />
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
                <button type="submit" class="nota btn" id="btn-guardar-nota">
                    <i class="bi bi-check2-circle me-1"></i>Calificar
                </button>
            </div>
            <div id="mensajeCalificacion" class="mt-2"></div>
        </form>
    </div>

<?php else: ?>
    <div class="alert alert-warning text-center">
        <i class="bi bi-exclamation-circle-fill"></i> No se encontró la entrega del aprendiz.
    </div>
<?php endif; ?>
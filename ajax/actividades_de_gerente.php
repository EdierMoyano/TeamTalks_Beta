<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/conexion/init.php';
include 'session.php';

$id_ficha = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$id_instructor = (int) $_SESSION['documento'];

if ($id_ficha === 0 || $id_instructor === 0) {
  echo "<div class='text-danger'>Acceso no autorizado.</div>";
  exit;
}

// Verificar que la ficha pertenezca al instructor
$checkSql = "
  SELECT 1 
  FROM materia_ficha 
  WHERE id_ficha = :id_ficha AND id_instructor = :id_instructor
  LIMIT 1
";
$checkStmt = $conex->prepare($checkSql);
$checkStmt->execute(['id_ficha' => $id_ficha, 'id_instructor' => $id_instructor]);

if (!$checkStmt->fetch()) {
  echo "<div class='text-danger'>No tienes permiso para ver esta ficha.</div>";
  exit;
}

// Consultar materias relacionadas a la ficha e instructor para los selects
$matSql = "SELECT mf.id_materia_ficha, m.materia
           FROM materia_ficha mf
           JOIN materias m ON mf.id_materia = m.id_materia
           WHERE mf.id_ficha = :id_ficha
           AND mf.id_instructor = :id_instructor";

$matStmt = $conex->prepare($matSql);
$matStmt->execute(['id_ficha' => $id_ficha, 'id_instructor' => $id_instructor]);
$materias = $matStmt->fetchAll(PDO::FETCH_ASSOC);


// Consultar actividades
$sql = "
  SELECT 
    a.id_actividad,
    a.titulo,
    a.descripcion,
    a.fecha_entrega,
    a.archivo1,
    a.archivo2,
    a.archivo3,
    mf.id_materia_ficha,
    m.materia AS materia
  FROM actividades a
  INNER JOIN materia_ficha mf ON a.id_materia_ficha = mf.id_materia_ficha
  INNER JOIN materias m ON mf.id_materia = m.id_materia
  WHERE mf.id_ficha = :id_ficha
    AND mf.id_instructor = :id_instructor
  ORDER BY a.fecha_entrega ASC
";

$stmt = $conex->prepare($sql);
$stmt->execute([
  'id_ficha' => $id_ficha,
  'id_instructor' => $id_instructor
]);
$actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
  .upload,
  .archivos,
  .select,
  .cancelar {
    color: #0E4A86;
    background-color: white;
    border-color: #0E4A86;
  }

  .cancelar:hover {
    color: #0E4A86;
    background-color: rgb(222, 222, 222);
    border-color: #0E4A86;
  }

  .upload:hover,
  .archivos:hover,
  .select:hover,
  .accion:hover {
    color: white;
    background-color: #0E4A86;
  }

  .accion {
    color: #0E4A86;
    font-size: 20px;
    background-color: white;
    border-color: #0E4A86;
    border-radius: 20%;

  }

  .input-group .form-control:hover {
    background-color: #f0f0f0;
  }

  .check:checked {
    color: white;
    background-color: #0E4A86;
  }

  .check:focus {
    box-shadow: 0 0 0 0.25rem rgba(14, 74, 134, 0.25);
  }

  .actividad,
  .crear {
    color: white;
    background-color: #0E4A86;
  }

  .actividad:hover,
  .crear:hover {
    color: white;
    background-color: rgb(11, 48, 86);
  }

  .img {
    max-width: 300px;
  }
</style>


<div class="d-flex justify-content-between align-items-center mb-4">
  <h5 class="mb-0">Actividades de la Ficha <?= htmlspecialchars($id_ficha) ?></h5>
  <button class="actividad btn btn" data-bs-toggle="modal" data-bs-target="#crearActividadModal">
    <i class="bi bi-plus-lg"></i> Crear Actividad
  </button>
</div>

<?php if (count($actividades) > 0): ?>
  <div class="list-group">
    <?php foreach ($actividades as $index => $act): ?>
      <div
        class="list-group-item list-group-item-action mb-3 shadow-sm rounded-3 p-4 d-flex justify-content-between align-items-center"
        style="cursor: pointer;"
        data-bs-toggle="modal"
        data-bs-target="#actividadModal<?= $index ?>">
        <div>
          <h5 class="mb-1" style="color: #0E4A86;">
            <i class="bi bi-journal-text me-2"></i><?= htmlspecialchars($act['titulo']) ?>
          </h5>
          <h6 class="text-muted small mb-0"><?= htmlspecialchars($act['materia']) ?></h6>
          <h6 class="text-muted small mb-o">Fecha de entrega: <?= htmlspecialchars($act['fecha_entrega']) ?></h6>
        </div>
        <div class="ms-3 d-flex gap-2">

          <button type="button"
            class="accion btn"
            title="Ver entregas"
            onclick="event.stopPropagation(); window.location.href='../mod/ver_entregas.php?id_actividad=<?= $act['id_actividad'] ?>';">
            <i class="bi bi-eye"></i>
          </button>


          <button type="button"
            class="accion btn"
            title="Editar actividad"
            data-bs-toggle="modal"
            data-bs-target="#editarActividadModal<?= $act['id_actividad'] ?>">
            <i class="bi bi-pencil"></i>
          </button>


        </div>
      </div>

      <!-- Modal para editar actividad -->
      <div class="modal fade" id="editarActividadModal<?= $act['id_actividad'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
          <form method="POST" action="../mod/editar_actividad.php" enctype="multipart/form-data" class="modal-content rounded-5 shadow-sm border-0 p-4">
            <div class="modal-header border-0 pb-0">
              <h5 class="modal-title fw-bold">✏️ Editar Actividad</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body pt-2">
              <input type="hidden" name="id_actividad" value="<?= $act['id_actividad'] ?>">

              <div class="mb-4">
                <label class="form-label text-secondary fw-semibold">Título</label>
                <input type="text" name="titulo_mostrar" value="<?= htmlspecialchars($act['titulo']) ?>" class="form-control border-0 border-bottom border-2 rounded-0 px-0 py-2" readonly>
                <input type="hidden" name="titulo" value="<?= htmlspecialchars($act['titulo']) ?>">
              </div>

              <div class="mb-4">
                <label class="form-label text-secondary fw-semibold">Descripción</label>
                <textarea name="descripcion" rows="4" class="form-control border-0 border-bottom border-2 rounded-0 px-0 py-2" required><?= htmlspecialchars($act['descripcion']) ?></textarea>
              </div>

              <div class="row g-4 mb-4">
                <div class="col-md-6">
                  <label for="fecha_entrega" class="form-label text-secondary fw-semibold">Fecha de Entrega</label>
                  <?php
                  $hoy = date('Y-m-d');
                  ?>
                  <input
                    type="date"
                    value="<?= $act['fecha_entrega'] ?>"
                    name="fecha_entrega"
                    id="fecha_entrega"
                    class="form-control border-0 border-bottom border-2 rounded-0 px-0 py-2"
                    required
                    min="<?= $hoy ?>">

                </div>
                <div class="col-md-6">
                  <label class="form-label text-secondary fw-semibold">Materia</label>
                  <select name="id_materia_ficha" class="form-select border-0 border-bottom border-2 rounded-0 px-0 py-2" required>
                    <?php foreach ($materias as $mat): ?>
                      <option value="<?= $mat['id_materia_ficha'] ?>" <?= $mat['id_materia_ficha'] == $act['id_materia_ficha'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($mat['materia']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <!-- Mostrar archivos actuales si existen -->
              <div class="mb-4">
                <label class="form-label text-secondary fw-semibold">Archivos actuales y nuevos</label>
                <div class="row g-3">
                  <?php for ($i = 1; $i <= 3; $i++): ?>
                    <div class="col-md-4">
                      <div class="border rounded-3 p-2 h-100 d-flex flex-column justify-content-between">
                        <?php if (!empty($act["archivo$i"])): ?>
                          <div class="mb-2">
                            <a download="<?= htmlspecialchars($act["archivo$i"]) ?>" href="../uploads/<?= htmlspecialchars($act["archivo$i"]) ?>" target="_blank"
                              class="d-block text-decoration-none text-dark text-truncate"
                              style="max-width: 100%;" title="<?= htmlspecialchars($act["archivo$i"]) ?>">
                              📄 <?= htmlspecialchars($act["archivo$i"]) ?>
                            </a>
                            <div class="form-check mt-2">
                              <input type="checkbox" name="eliminar_archivo<?= $i ?>" id="eliminar_archivo<?= $i ?>_<?= $act['id_actividad'] ?>"
                                class="check form-check-input" value="1">
                              <label for="eliminar_archivo<?= $i ?>_<?= $act['id_actividad'] ?>" class="form-check-label small">Eliminar</label>
                            </div>
                          </div>
                        <?php else: ?>
                          <div class="text-muted small mb-2">Sin archivo actual</div>
                        <?php endif; ?>

                        <label for="archivo<?= $i ?>_<?= $act['id_actividad'] ?>" class="select custom-file-label btn btn-sm rounded-pill w-100 text-truncate text-start px-3 py-2">
                          Seleccionar archivo
                        </label>
                        <input type="file" name="archivo<?= $i ?>" id="archivo<?= $i ?>_<?= $act['id_actividad'] ?>" class="d-none"
                          accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.mp4,.mov,.avi,.txt">
                      </div>
                    </div>
                  <?php endfor; ?>
                </div>
              </div>
            </div>

            <div class="modal-footer justify-content-end border-0 pt-0">
              <button type="button" class="cancelar btn btn rounded-pill px-4 me-3" data-bs-dismiss="modal">Cancelar</button>
              <button type="submit" class="crear btn btn rounded-pill px-5 shadow-sm">Guardar Cambios</button>
            </div>
          </form>
        </div>
      </div>


      <!-- Modal Detalles de Actividad -->
      <div class="modal fade" id="actividadModal<?= $index ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
          <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
            <div class="modal-header text-white border-0 py-3 px-4" style="background-color: #0E4A86;">
              <div>
                <h5 class="modal-title fw-semibold mb-0">
                  <i class="bi bi-journal-text me-2"></i><?= htmlspecialchars($act['titulo']) ?>
                </h5>
                <small class="opacity-75"><?= htmlspecialchars($act['materia']) ?></small>
              </div>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body px-4 py-4">
              <div class="mb-4">
                <label class="fw-semibold text-secondary small text-uppercase mb-1">Descripción</label>
                <div class="bg-light rounded-3 p-3 border" style="max-height: 300px; overflow-y: auto;">
                  <?= nl2br(htmlspecialchars($act['descripcion'])) ?>
                </div>
              </div>
              <div class="mb-3 d-flex align-items-center">
                <i class="bi bi-calendar-event me-2 fs-5" style="color: #0E4A86"></i>
                <div>
                  <div class="fw-semibold text-secondary small text-uppercase">Fecha de Entrega</div>
                  <div class="fw-bold"><?= htmlspecialchars($act['fecha_entrega']) ?></div>
                </div>
              </div>
              <div class="mt-4">
                <label class="fw-semibold text-secondary small text-uppercase mb-2">Archivos</label><br>
                <?php
                $archivos = [];
                for ($i = 1; $i <= 3; $i++) {
                  $archivo = $act["archivo$i"] ?? null;

                  if (!empty($archivo)) {
                    // Extraer solo el nombre original (después del primer guion bajo)
                    $partes = explode('_', $archivo, 2);
                    $nombreVisible = $partes[1] ?? $archivo;

                    $archivos[] = '<a href="../uploads/' . htmlspecialchars($archivo) . '" target="_blank" class="archivos btn btn-sm btn rounded-pill me-2 mb-2">
                         <i class="bi bi-file-earmark-arrow-down me-1"></i>' . htmlspecialchars($nombreVisible) . '
                       </a>';
                  }
                }

                if (!empty($archivos)) {
                  echo implode('', $archivos);
                } else {
                  echo '<span class="text-muted fst-italic">No se adjuntaron archivos.</span>';
                }
                ?>
              </div>

            </div>
            <div class="modal-footer border-0 px-4 py-3">
              <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cerrar</button>
            </div>
          </div>
        </div>
      </div>
      <!-- Fin Modal Detalles -->
    <?php endforeach; ?>
  </div>
<?php else: ?>
  <div class="text-center text-muted">Esta ficha no tiene actividades asignadas.
    <img src="../assets/img/n-result.webp" alt="" class="img-fluid img">
  </div>
<?php endif; ?>


<!-- Modal Crear Actividad - Rediseño Minimalista -->
<div class="modal fade" id="crearActividadModal" tabindex="-1" aria-labelledby="crearActividadLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <form id="formCrearActividad" method="POST" action="../mod/crear_actividad.php" enctype="multipart/form-data" class="modal-content rounded-5 shadow-sm border-0 p-4">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold" id="crearActividadLabel">➕ Crear Nueva Actividad</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body pt-2">

        <input type="hidden" name="id_ficha" value="<?= htmlspecialchars($id_ficha) ?>">

        <div class="mb-4">
          <label for="titulo" class="form-label text-secondary fw-semibold">Título</label>
          <input type="text" name="titulo" id="titulo" class="form-control border-0 border-bottom border-2 rounded-0 px-0 py-2" placeholder="Nombre de la actividad" required autofocus>
        </div>

        <div class="mb-4">
          <label for="descripcion" class="form-label text-secondary fw-semibold">Descripción</label>
          <textarea name="descripcion" id="descripcion" class="form-control border-0 border-bottom border-2 rounded-0 px-0 py-2" rows="4" style="max-height: 200px;" placeholder="Descripción detallada" required></textarea>
        </div>

        <div class="row g-4 mb-4">
          <div class="col-md-6">
            <label for="fecha_entrega" class="form-label text-secondary fw-semibold">Fecha de Entrega</label>
            <?php
            $hoy = date('Y-m-d');
            ?>
            <input
              type="date"
              name="fecha_entrega"
              id="fecha_entrega"
              class="form-control border-0 border-bottom border-2 rounded-0 px-0 py-2"
              required
              min="<?= $hoy ?>">

          </div>
          <div class="col-md-6">
            <label for="id_materia_ficha" class="form-label text-secondary fw-semibold">Materia</label>
            <select name="id_materia_ficha" id="id_materia_ficha" class="form-select border-0 border-bottom border-2 rounded-0 px-0 py-2" required>
              <?php
              $matSql = "SELECT mf.id_materia_ficha, m.materia
                         FROM materia_ficha mf
                         JOIN materias m ON mf.id_materia = m.id_materia
                         WHERE mf.id_ficha = :id_ficha
                         AND mf.id_instructor = :id_instructor";
              $matStmt = $conex->prepare($matSql);
              $matStmt->execute(['id_ficha' => $id_ficha, 'id_instructor' => $id_instructor]);
              $materias = $matStmt->fetchAll(PDO::FETCH_ASSOC);
              foreach ($materias as $mat) {
                echo '<option value="' . htmlspecialchars($mat['id_materia_ficha']) . '">' . htmlspecialchars($mat['materia']) . '</option>';
              }
              ?>
            </select>
          </div>
        </div>

        <div class="mb-4">
          <label class="form-label text-secondary fw-semibold">Archivos (opcional). 20MB C/U</label>
          <div class="row g-3">

            <!-- Archivo 1 -->
            <div class="col-md-4">
              <label for="archivo1"
                class="form-control form-control-sm border-0 border-bottom border-2 rounded-0 px-0 py-2 text-muted small d-flex align-items-center"
                style="cursor: pointer; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                <i class="bi bi-paperclip me-1 flex-shrink-0"></i>
                <span id="archivo1Label" class="text-truncate w-100 d-inline-block">Sin archivo</span>
              </label>
              <input type="file" name="archivo1" id="archivo1" class="d-none"
                accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.mp4,.mov,.avi,.txt"
                onchange="document.getElementById('archivo1Label').textContent = this.files[0]?.name || 'Archivo 1';">
            </div>

            <!-- Archivo 2 -->
            <div class="col-md-4">
              <label for="archivo2"
                class="form-control form-control-sm border-0 border-bottom border-2 rounded-0 px-0 py-2 text-muted small d-flex align-items-center"
                style="cursor: pointer; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                <i class="bi bi-paperclip me-1 flex-shrink-0"></i>
                <span id="archivo2Label" class="text-truncate w-100 d-inline-block">Sin archivo</span>
              </label>
              <input type="file" name="archivo2" id="archivo2" class="d-none"
                accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.mp4,.mov,.avi,.txt"
                onchange="document.getElementById('archivo2Label').textContent = this.files[0]?.name || 'Archivo 2';">
            </div>

            <!-- Archivo 3 -->
            <div class="col-md-4">
              <label for="archivo3"
                class="form-control form-control-sm border-0 border-bottom border-2 rounded-0 px-0 py-2 text-muted small d-flex align-items-center"
                style="cursor: pointer; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                <i class="bi bi-paperclip me-1 flex-shrink-0"></i>
                <span id="archivo3Label" class="text-truncate w-100 d-inline-block">Sin archivo</span>
              </label>
              <input type="file" name="archivo3" id="archivo3" class="d-none"
                accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.mp4,.mov,.avi,.txt"
                onchange="document.getElementById('archivo3Label').textContent = this.files[0]?.name || 'Archivo 3';">
            </div>

          </div>
        </div>




      </div>
      <div class="modal-footer justify-content-end border-0 pt-0">
        <button type="button" class="cancelar btn btn rounded-pill px-4 me-3" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="crear btn btn rounded-pill px-5 shadow-sm">Crear</button>
      </div>
    </form>
  </div>
</div>






<script>
  document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-editar').forEach(function(button) {
      button.addEventListener('click', function() {
        const id = this.getAttribute('data-id');
        const titulo = this.getAttribute('data-titulo');
        const descripcion = (this.getAttribute('data-descripcion'));
        const fecha = this.getAttribute('data-fecha');
        const materia = this.getAttribute('data-materia');
        const archivo = this.getAttribute('data-archivo');

        document.getElementById('edit_id_actividad').value = id;
        document.getElementById('edit_titulo').value = titulo;
        document.getElementById('edit_descripcion').value = descripcion;
        document.getElementById('edit_fecha_entrega').value = fecha;
        document.getElementById('edit_id_materia_ficha').value = materia;

        const archivoContainer = document.getElementById('archivoActualContainer');
        const archivoLink = document.getElementById('archivoActualLink');

        if (archivo && archivo.trim() !== '') {
          archivoLink.href = '../uploads/' + encodeURIComponent(archivo);
          archivoLink.textContent = '📄 ' + archivo;
          archivoContainer.style.display = 'block';
        } else {
          archivoContainer.style.display = 'none';
        }
      });
    });
  });
</script>


<script>
  document.getElementById('formCrearActividad').addEventListener('submit', function(e) {
    const maxSize = 20 * 1024 * 1024; // 20MB
    const archivos = [
      document.getElementById('archivo1'),
      document.getElementById('archivo2'),
      document.getElementById('archivo3')
    ];

    for (let i = 0; i < archivos.length; i++) {
      const archivo = archivos[i].files[0];
      if (archivo && archivo.size > maxSize) {
        alert(`El archivo ${i + 1} supera el límite de 20MB. Por favor, selecciona un archivo más pequeño.`);
        archivos[i].value = ''; // Limpia el input
        document.getElementById(`archivo${i + 1}Label`).textContent = 'Sin archivo';
        e.preventDefault(); // Cancela el envío
        return;
      }
    }
  });
</script>




<script>
  document.addEventListener('DOMContentLoaded', function() {
    const fechaInput = document.getElementById('fecha_entrega');
    if (fechaInput) {
      const today = new Date().toISOString().split('T')[0];
      fechaInput.min = today;
    }
  });
</script>
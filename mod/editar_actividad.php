<?php
if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['DOCUMENT_ROOT'], 'htdocs') !== false) {
  require_once $_SERVER['DOCUMENT_ROOT'] . '/teamtalks/conexion/init.php';
} else {
  require_once $_SERVER['DOCUMENT_ROOT'] . '/conexion/init.php';
}
include 'session.php';

if ($_SESSION['rol'] !== 3 && $_SESSION['rol'] !== 5) {
    header('Location:' . BASE_URL . '/includes/exit.php?motivo=acceso-denegado');
    exit;
}

$id_actividad = (int) ($_POST['id_actividad'] ?? 0);
$titulo = $_POST['titulo'] ?? '';
$descripcion = $_POST['descripcion'] ?? '';
$fecha_entrega = $_POST['fecha_entrega'] ?? '';
$id_materia_ficha = (int) ($_POST['id_materia_ficha'] ?? 0);

// Obtener archivos actuales
$sql = "SELECT archivo1, archivo2, archivo3 FROM actividades WHERE id_actividad = :id";
$stmt = $conex->prepare($sql);
$stmt->execute(['id' => $id_actividad]);
$actividad = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$actividad) {
    die("Actividad no encontrada.");
}

// Procesar cada archivo
$archivos_finales = [];

for ($i = 1; $i <= 3; $i++) {
    $key = "archivo$i";
    $archivo_actual = $actividad[$key] ?? null;
    $nuevo_archivo = null;

    $checkbox = isset($_POST["eliminar_archivo$i"]) && $_POST["eliminar_archivo$i"] == '1';

    // Subida de archivo nuevo
    if (isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES[$key]['tmp_name'];
        $nombreOriginal = basename($_FILES[$key]['name']);
        $ext = pathinfo($nombreOriginal, PATHINFO_EXTENSION);
        $nuevoNombre = uniqid("act_{$i}_") . "." . $ext;
        $destino = "../uploads/$nuevoNombre";

        if (move_uploaded_file($tmpName, $destino)) {
            // Eliminar archivo anterior si existe
            if ($archivo_actual && file_exists($_SERVER['DOCUMENT_ROOT'] . "/uploads/$archivo_actual")) {
                unlink("../uploads/$archivo_actual");
            }
            $nuevo_archivo = $nuevoNombre;
        }
    }

    // Eliminar si se marcó checkbox y no se subió nuevo
    if ($checkbox && !$nuevo_archivo) {
        if ($archivo_actual && file_exists($_SERVER['DOCUMENT_ROOT'] . "/uploads/$archivo_actual")) {
            unlink("../uploads/$archivo_actual");
        }
        $archivo_actual = null;
    }

    // Guardar archivo nuevo o existente si no fue eliminado
    $archivos_finales[$key] = $nuevo_archivo ?: ($checkbox ? null : $archivo_actual);
}

// Actualizar en base de datos
$updateSql = "UPDATE actividades SET 
    descripcion = :descripcion,
    fecha_entrega = :fecha_entrega,
    id_materia_ficha = :id_materia_ficha,
    archivo1 = :archivo1,
    archivo2 = :archivo2,
    archivo3 = :archivo3
    WHERE id_actividad = :id";

$updateStmt = $conex->prepare($updateSql);
$updateStmt->execute([
    'descripcion' => $descripcion,
    'fecha_entrega' => $fecha_entrega,
    'id_materia_ficha' => $id_materia_ficha,
    'archivo1' => $archivos_finales['archivo1'],
    'archivo2' => $archivos_finales['archivo2'],
    'archivo3' => $archivos_finales['archivo3'],
    'id' => $id_actividad
]);

$_SESSION['actividad_actualizada'] = $titulo;
header('Location: ../instructor/actividades.php?id=' . $_POST['id_ficha']);
exit;

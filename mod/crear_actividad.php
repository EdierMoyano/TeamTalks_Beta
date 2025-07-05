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

$rol = $_SESSION['rol'] ?? '';
$subcarpeta = $rol === 5 ? 'transversal' : 'instructor';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_ficha = $_POST['id_ficha'] ?? 0;
    $titulo = $_POST['titulo'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $fecha_entrega = $_POST['fecha_entrega'] ?? '';
    $id_materia_ficha = $_POST['id_materia_ficha'] ?? 0;

    $archivosPermitidos = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'gif', 'mp4', 'avi', 'mov', 'wmv', 'mkv', 'txt'];
    $nombresFinales = [null, null, null];
    $uploads_dir = '../uploads/';

    if (!is_dir($uploads_dir)) {
        mkdir($uploads_dir, 0755, true);
    }

    for ($i = 1; $i <= 3; $i++) {
        $archivo = $_FILES['archivo' . $i] ?? null;

        if ($archivo && $archivo['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
            $size = $archivo['size'];

            if (!in_array($ext, $archivosPermitidos)) {
                $_SESSION['error_actividad'] = "El archivo {$i} tiene una extensión no permitida.";
                header("Location: ../$subcarpeta/actividades.php?id=" . (int)$id_ficha);
                exit;
            }

            if ($size > 20 * 1024 * 1024) {
                $_SESSION['error_actividad'] = "El archivo {$i} excede el límite de 20MB.";
                header("Location: ../$subcarpeta/actividades.php?id=" . (int)$id_ficha);
                exit;
            }

            $nombreFinal = uniqid("DOC{$i}_") . '_' . basename($archivo['name']);
            $destino = $uploads_dir . $nombreFinal;

            if (move_uploaded_file($archivo['tmp_name'], $destino)) {
                $nombresFinales[$i - 1] = $nombreFinal;
            } else {
                $_SESSION['error_actividad'] = "Error al subir el archivo {$i}.";
                header("Location: ../$subcarpeta/actividades.php?id=" . (int)$id_ficha);
                exit;
            }
        }
    }

    // Insertar actividad
    $sql = "INSERT INTO actividades (titulo, descripcion, fecha_entrega, archivo1, archivo2, archivo3, id_materia_ficha)
            VALUES (:titulo, :descripcion, :fecha_entrega, :archivo1, :archivo2, :archivo3, :id_materia_ficha)";
    $stmt = $conex->prepare($sql);
    $stmt->bindValue(':titulo', $titulo);
    $stmt->bindValue(':descripcion', $descripcion);
    $stmt->bindValue(':fecha_entrega', $fecha_entrega);
    $stmt->bindValue(':archivo1', $nombresFinales[0]);
    $stmt->bindValue(':archivo2', $nombresFinales[1]);
    $stmt->bindValue(':archivo3', $nombresFinales[2]);
    $stmt->bindValue(':id_materia_ficha', $id_materia_ficha);

    if ($stmt->execute()) {
        $id_actividad = $conex->lastInsertId();

        // Obtener nombre de la materia e instructor antes de usarlo
        $sqlMateria = "
            SELECT m.nombre_materia, u.nombres, u.apellidos
            FROM materia_ficha mf
            JOIN materias m ON m.id_materia = mf.id_materia
            JOIN usuarios u ON u.id = mf.id_instructor
            WHERE mf.id_materia_ficha = :id_materia_ficha
            LIMIT 1
        ";
        $stmtMateria = $conex->prepare($sqlMateria);
        $stmtMateria->execute(['id_materia_ficha' => $id_materia_ficha]);
        $rowMateria = $stmtMateria->fetch(PDO::FETCH_ASSOC);

        $nombreMateria = $rowMateria['nombre_materia'] ?? 'materia';
        $nombreInstructor = trim($rowMateria['nombres'] . ' ' . $rowMateria['apellidos']);

        // Obtener aprendices asociados
        $sqlAprendices = "
            SELECT u.id AS id_user
            FROM user_ficha uf
            JOIN usuarios u ON u.id = uf.id_user
            WHERE uf.id_ficha = :id_ficha
            AND u.id_rol = 4
        ";
        $stmtAprendices = $conex->prepare($sqlAprendices);
        $stmtAprendices->execute(['id_ficha' => $id_ficha]);

        $aprendices = $stmtAprendices->fetchAll(PDO::FETCH_ASSOC);

        // Insertar registros en actividades_user
        $sqlInsertActividadUser = "
            INSERT INTO actividades_user 
            (id_actividad, id_estado_actividad, contenido, archivo1, archivo2, archivo3, fecha_entrega, id_user, nota, comentario_inst)
            VALUES (:id_actividad, 9, NULL, NULL, NULL, NULL, NULL, :id_user, NULL, NULL)
        ";
        $stmtInsertAU = $conex->prepare($sqlInsertActividadUser);

        foreach ($aprendices as $aprendiz) {
            $stmtInsertAU->execute([
                'id_actividad' => $id_actividad,
                'id_user' => $aprendiz['id_user'],
            ]);
        }

        // Insertar notificaciones
        $sqlNotificacion = "
            INSERT INTO notificaciones 
            (id_usuario, tipo, mensaje, url_destino, leido, fecha, id_emisor, id_respuesta_foro)
            VALUES (:id_usuario, 'actividad', :mensaje, :url_destino, 0, NOW(), :id_emisor, NULL)
        ";
        $stmtNotif = $conex->prepare($sqlNotificacion);

        $mensajeNotif = "El instructor $nombreInstructor ha publicado la actividad \"$titulo\" en la materia $nombreMateria.";
        $urlDestino = BASE_URL . "/aprendiz/clase/detalle_actividad.php?id=" . $id_actividad;
        $idEmisor = $_SESSION['documento'];

        foreach ($aprendices as $aprendiz) {
            // Verificar si ya existe una notificación para este aprendiz y esta actividad
            $stmtCheck = $conex->prepare("
                SELECT COUNT(*) FROM notificaciones
                WHERE id_usuario = :id_usuario
                AND url_destino = :url_destino
                AND tipo = 'actividad'
            ");
            $stmtCheck->execute([
                'id_usuario' => $aprendiz['id_user'],
                'url_destino' => $urlDestino
            ]);

            if ((int)$stmtCheck->fetchColumn() === 0) {
                $stmtNotif->execute([
                    'id_usuario'   => $aprendiz['id_user'],
                    'mensaje'      => $mensajeNotif,
                    'url_destino'  => $urlDestino,
                    'id_emisor'    => $idEmisor
                ]);
            }
        }

        $_SESSION['actividad_creada'] = $titulo;
        header("Location: ../$subcarpeta/actividades.php?id=" . (int)$id_ficha);
        exit;
    } else {
        $_SESSION['error_actividad'] = "Ocurrió un error al guardar la actividad.";
        header("Location: ../$subcarpeta/actividades.php?id=" . (int)$id_ficha);
        exit;
    }
}

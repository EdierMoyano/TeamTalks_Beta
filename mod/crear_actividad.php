<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/conexion/init.php';
include 'session.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_ficha = $_POST['id_ficha'] ?? 0;
    $titulo = $_POST['titulo'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $fecha_entrega = $_POST['fecha_entrega'] ?? '';
    $id_materia_ficha = $_POST['id_materia_ficha'] ?? 0;

    $archivosPermitidos = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'gif', 'mp4', 'avi', 'mov', 'wmv', 'mkv'];
    $totalSize = 0;
    $nombresFinales = [null, null, null]; // archivo1, archivo2, archivo3

    $uploads_dir = $_SERVER['DOCUMENT_ROOT'] . '/teamtalks/uploads/';
    if (!is_dir($uploads_dir)) {
        mkdir($uploads_dir, 0755, true);
    }

    for ($i = 1; $i <= 3; $i++) {
        $archivo = $_FILES['archivo' . $i] ?? null;

        if ($archivo && $archivo['error'] === UPLOAD_ERR_OK) {
            $totalSize += $archivo['size'];

            // Validar extensión
            $ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $archivosPermitidos)) {
                echo "El archivo {$i} tiene una extensión no permitida.";
                exit;
            }

            // Guardar con nombre único
            $nombreFinal = uniqid("archivo{$i}_") . '_' . basename($archivo['name']);
            $destino = $uploads_dir . $nombreFinal;

            if (move_uploaded_file($archivo['tmp_name'], $destino)) {
                $nombresFinales[$i - 1] = $nombreFinal;
            } else {
                echo "Error al subir el archivo {$i}.";
                exit;
            }
        }
    }

    if ($totalSize > 50 * 1024 * 1024) {
        echo "La suma total de los archivos no puede superar los 50 MB.";
        exit;
    }

    // Insertar en la base de datos
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

        // Obtener todos los aprendices de la ficha relacionada
        $sqlAprendices = "
        SELECT u.id AS id_user
        FROM materia_ficha mf
        JOIN fichas f ON f.id_ficha = mf.id_ficha
        JOIN user_ficha uf ON uf.id_ficha = f.id_ficha
        JOIN usuarios u ON u.id = uf.id_user
        WHERE mf.id_materia_ficha = :id_materia_ficha
        AND u.id_rol = 4
    ";
        $stmtAprendices = $conex->prepare($sqlAprendices);
        $stmtAprendices->execute(['id_materia_ficha' => $id_materia_ficha]);
        $aprendices = $stmtAprendices->fetchAll(PDO::FETCH_ASSOC);

        // Insertar en actividades_user para cada aprendiz
        $sqlInsertActividadUser = "
        INSERT INTO actividades_user (id_actividad, id_estado_actividad, contenido, archivo, fecha_entrega, id_user, nota)
        VALUES (:id_actividad, 9, NULL, NULL, NULL, :id_user, NULL)
    ";
        $stmtInsertAU = $conex->prepare($sqlInsertActividadUser);

        foreach ($aprendices as $aprendiz) {
            $stmtInsertAU->execute([
                'id_actividad' => $id_actividad,
                'id_user' => $aprendiz['id_user'],
            ]);
        }
        $_SESSION['actividad_creada'] = $titulo;
        header("Location: /teamtalks/instructor/actividades.php?id=" . (int)$id_ficha);
        exit;
    }
}

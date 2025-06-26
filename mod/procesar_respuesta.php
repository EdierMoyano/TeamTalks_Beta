<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/teamtalks/conexion/init.php';
include 'session.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_tema_foro = (int) $_POST['id_tema_foro'];
    $descripcion = trim($_POST['descripcion']);
    $id_user = $_SESSION['documento'];
    $id_respuesta_padre = !empty($_POST['id_respuesta_padre']) ? (int) $_POST['id_respuesta_padre'] : null;

    // Validaciones
    if (empty($descripcion)) {
        $_SESSION['error'] = 'La descripción no puede estar vacía.';
        header("Location: ver_respuestas.php?id_tema=$id_tema_foro");
        exit;
    }

    if (strlen($descripcion) > 1000) {
        $_SESSION['error'] = 'La respuesta no puede exceder 1000 caracteres.';
        header("Location: ver_respuestas.php?id_tema=$id_tema_foro");
        exit;
    }

    try {
        // Verificar que el tema existe
        $stmt = $conex->prepare("SELECT id_tema_foro FROM temas_foro WHERE id_tema_foro = ?");
        $stmt->execute([$id_tema_foro]);
        if (!$stmt->fetch()) {
            throw new Exception('El tema no existe.');
        }

        // Si es una respuesta a otro comentario, verificar que existe
        if ($id_respuesta_padre) {
            $stmt = $conex->prepare("SELECT id_respuesta_foro FROM respuesta_foro WHERE id_respuesta_foro = ? AND id_tema_foro = ?");
            $stmt->execute([$id_respuesta_padre, $id_tema_foro]);
            if (!$stmt->fetch()) {
                throw new Exception('El comentario al que intentas responder no existe.');
            }
        }

        // Insertar la respuesta
        $stmt = $conex->prepare("
    INSERT INTO respuesta_foro (id_tema_foro, id_user, descripcion, fecha_respuesta, id_respuesta_padre) 
    VALUES (?, ?, ?, NOW(), ?)
");
        $stmt->execute([$id_tema_foro, $id_user, $descripcion, $id_respuesta_padre]);

        $id_respuesta_insertada = $conex->lastInsertId();

        // Notificar al autor del comentario original (respuesta padre)
        if ($id_respuesta_padre) {
            $stmt = $conex->prepare("SELECT id_user FROM respuesta_foro WHERE id_respuesta_foro = ?");
            $stmt->execute([$id_respuesta_padre]);
            $autor_padre = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($autor_padre && $autor_padre['id_user'] == $id_user) {
                $mensaje = 'Han respondido a tu comentario en el foro.';
                $url = BASE_URL . "/mod/ver_respuestas.php?id_tema=$id_tema_foro";

                $stmt = $conex->prepare("
            INSERT INTO notificaciones (id_usuario, tipo, mensaje, url_destino, leido, fecha, id_emisor, id_respuesta_foro)
            VALUES (?, 'respuesta_comentario', ?, ?, 0, NOW(), ?, ?)
        ");
                $stmt->execute([
                    $autor_padre['id_user'],
                    $mensaje,
                    $url,
                    $id_user,
                    $id_respuesta_insertada
                ]);
            }
        }

        $_SESSION['success'] = $id_respuesta_padre ? 'Respuesta publicada correctamente.' : 'Comentario publicado correctamente.';
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error al publicar: ' . $e->getMessage();
    }

    header("Location: ver_respuestas.php?id_tema=$id_tema_foro");
    exit;
}

// Si no es POST, redirigir
header("Location: index.php");
exit;

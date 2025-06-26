<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/teamtalks/conexion/init.php';
include 'session.php';

// Validar que la solicitud sea POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos del formulario
    $id_foro = isset($_POST['id_foro']) ? (int)$_POST['id_foro'] : 0;
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $id_user = $_SESSION['documento'];

    // Validaciones básicas
    if ($id_foro <= 0 || empty($titulo) || empty($descripcion)) {
        $_SESSION['error_tema_foro'] = 'Todos los campos son obligatorios.';
        header("Location: ver_temas.php?id_foro=$id_foro");
        exit;
    }

    try {
        // Insertar tema
        $stmt = $conex->prepare("
            INSERT INTO temas_foro (id_foro, id_user, titulo, descripcion, fecha_creacion)
            VALUES (:id_foro, :id_user, :titulo, :descripcion, NOW())
        ");

        $stmt->execute([
            'id_foro' => $id_foro,
            'id_user' => $id_user,
            'titulo' => $titulo,
            'descripcion' => $descripcion
        ]);

        $_SESSION['tema_creado'] = 'Tema creado correctamente.';
        header("Location: temas_foro.php?id_foro=$id_foro");
        exit;

    } catch (PDOException $e) {
        // Puedes guardar el error en log si lo deseas
        $_SESSION['error_tema_foro'] = 'Error al crear el tema.';
        header("Location: temas_foro.php?id_foro=$id_foro");
        exit;
    }

} else {
    header("Location: ../instructor/foros.php");
    exit;
}

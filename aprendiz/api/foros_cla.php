<?php
header('Content-Type: application/json');

/*
 * API: foros_cla.php
 * Descripción:
 * Obtiene la información de los foros de clase, incluyendo los temas y respuestas asociadas.
 * Devuelve una estructura jerárquica: Foro → Temas → Respuestas.
 * 
 * Tablas involucradas:
 * - foros: Información general del foro.
 * - materia_ficha: Relaciona materias, instructores y fichas.
 * - materias: Nombres de las materias.
 * - usuarios: Datos de instructores, creadores de temas y aprendices que responden.
 * - fichas: Números de ficha.
 * - temas_foro: Temas creados dentro de un foro.
 * - respuesta_foro: Respuestas a los temas del foro.
 */

$host = 'localhost';
$db = 'teamtalks';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    // Conexión a la base de datos
    $pdo = new PDO($dsn, $user, $pass, $options);

    // Consulta SQL para obtener foros, temas y respuestas relacionados a clases
    $sql = "SELECT 
                f.id_foro,
                f.fecha_foro,
                mf.id_materia_ficha AS id_clase,
                m.materia AS nombre_clase,
                CONCAT(TRIM(u.nombres), ' ', TRIM(u.apellidos)) AS nombre_profesor,
                fichas.id_ficha AS numero_fichas,
                
                tf.id_tema_foro,
                tf.titulo AS titulo_tema,
                tf.descripcion AS descripcion_tema,
                tf.fecha_creacion AS fecha_tema,
                CONCAT(TRIM(ut.nombres), ' ', TRIM(ut.apellidos)) AS creador_tema,
                
                rf.id_respuesta_foro,
                rf.descripcion AS respuesta,
                rf.fecha_respuesta,
                CONCAT(TRIM(ur.nombres), ' ', TRIM(ur.apellidos)) AS aprendiz_respondio
                
            FROM foros f
            JOIN materia_ficha mf ON f.id_materia_ficha = mf.id_materia_ficha
            JOIN materias m ON mf.id_materia = m.id_materia
            JOIN usuarios u ON mf.id_instructor = u.id
            JOIN fichas ON mf.id_ficha = fichas.id_ficha

            LEFT JOIN temas_foro tf ON tf.id_foro = f.id_foro
            LEFT JOIN usuarios ut ON tf.id_user = ut.id

            LEFT JOIN respuesta_foro rf ON rf.id_tema_foro = tf.id_tema_foro
            LEFT JOIN usuarios ur ON rf.id_user = ur.id

            ORDER BY f.id_foro, tf.id_tema_foro, rf.id_respuesta_foro";

    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll();

    $foros = [];

    // Procesamiento de los resultados para estructurarlos jerárquicamente
    foreach ($rows as $row) {
        $foro_id = $row['id_foro'];
        $tema_id = $row['id_tema_foro'];

        // Si el foro aún no está en el array, se agrega
        if (!isset($foros[$foro_id])) {
            $foros[$foro_id] = [
                'id_foro' => $foro_id,
                'fecha_foro' => $row['fecha_foro'],
                'id_clase' => $row['id_clase'],
                'nombre_clase' => $row['nombre_clase'],
                'nombre_profesor' => $row['nombre_profesor'],
                'numero_fichas' => $row['numero_fichas'],
                // Imagen simulada para la clase
                'imagen' => "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSLlPxXwu6GBz2YNT0kRZhPElAeyZArGF2evQ&s",
                'temas' => []
            ];
        }

        // Si el tema aún no está en el foro, se agrega
        if ($tema_id && !isset($foros[$foro_id]['temas'][$tema_id])) {
            $foros[$foro_id]['temas'][$tema_id] = [
                'id_tema_foro' => $tema_id,
                'titulo' => $row['titulo_tema'],
                'descripcion' => $row['descripcion_tema'],
                'fecha_creacion' => $row['fecha_tema'],
                'creador' => $row['creador_tema'],
                'respuestas' => []
            ];
        }

        // Si hay respuesta, se agrega al tema correspondiente
        if (!empty($row['id_respuesta_foro']) && $tema_id) {
            $foros[$foro_id]['temas'][$tema_id]['respuestas'][] = [
                'id_respuesta_foro' => $row['id_respuesta_foro'],
                'descripcion' => $row['respuesta'],
                'fecha_respuesta' => $row['fecha_respuesta'],
                'respondido_por' => $row['aprendiz_respondio'] ?? 'Anónimo'
            ];
        }
    }

    // Reindexar los arrays para que sean numéricos y más fáciles de manejar en el frontend
    $foros = array_values(array_map(function ($foro) {
        $foro['temas'] = array_values($foro['temas']);
        return $foro;
    }, $foros));

    // Devolver la respuesta en formato JSON
    echo json_encode($foros, JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    // Manejo de errores de conexión o consulta
    echo json_encode(['error' => 'Error de conexión o consulta: ' . $e->getMessage()]);
}

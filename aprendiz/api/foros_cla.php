<?php
header('Content-Type: application/json');

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
    $pdo = new PDO($dsn, $user, $pass, $options);

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

            ORDER BY f.id_foro, tf.id_tema_foro, rf.id_respuesta_foro
    ";

    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll();

    $foros = [];

    foreach ($rows as $row) {
        $foro_id = $row['id_foro'];
        $tema_id = $row['id_tema_foro'];

        if (!isset($foros[$foro_id])) {
            $foros[$foro_id] = [
                'id_foro' => $foro_id,
                'fecha_foro' => $row['fecha_foro'],
                'id_clase' => $row['id_clase'],
                'nombre_clase' => $row['nombre_clase'],
                'nombre_profesor' => $row['nombre_profesor'],
                'numero_fichas' => $row['numero_fichas'],
                'imagen' => "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSLlPxXwu6GBz2YNT0kRZhPElAeyZArGF2evQ&s" . urlencode($row['nombre_clase']),
                'temas' => []
            ];
        }

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

        if (!empty($row['id_respuesta_foro'])) {
            $foros[$foro_id]['temas'][$tema_id]['respuestas'][] = [
                'id_respuesta_foro' => $row['id_respuesta_foro'],
                'descripcion' => $row['respuesta'],
                'fecha_respuesta' => $row['fecha_respuesta'],
                'respondido_por' => $row['aprendiz_respondido']
            ];
        }
    }

    // Reindexar arrays para convertir los objetos en arreglos válidos JSON
    $foros = array_values(array_map(function ($foro) {
        $foro['temas'] = array_values($foro['temas']);
        return $foro;
    }, $foros));

    echo json_encode($foros, JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Error de conexión o consulta: ' . $e->getMessage()]);
}

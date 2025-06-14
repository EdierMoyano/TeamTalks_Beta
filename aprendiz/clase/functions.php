<?php
require_once 'config.php';

function obtenerFichaActivaDeUsuario($id_usuario)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT f.*
        FROM user_ficha uf
        INNER JOIN fichas f ON f.id_ficha = uf.id_ficha
        WHERE uf.id_user = ? AND uf.id_estado = 1 AND f.id_estado = 1
        LIMIT 1
    ");
    $stmt->execute([$id_usuario]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}


// Obtener información de una ficha específica
function obtenerFicha($id_ficha)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT f.*, fo.nombre as nombre_formacion, j.jornada, a.ambiente
        FROM fichas f
        LEFT JOIN formacion fo ON f.id_formacion = fo.id_formacion
        LEFT JOIN jornada j ON f.id_jornada = j.id_jornada
        LEFT JOIN ambientes a ON f.id_ambiente = a.id_ambiente
        WHERE f.id_ficha = ? AND f.id_estado = 1
    ");
    $stmt->execute([$id_ficha]);
    return $stmt->fetch();
}

function obtenerDetalleActividad($id_actividad)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT a.*, m.materia, 
               u.nombres as instructor_nombres, u.apellidos as instructor_apellidos,
               mf.id_ficha, mf.id_materia_ficha
        FROM actividades a
        JOIN materia_ficha mf ON a.id_materia_ficha = mf.id_materia_ficha
        JOIN materias m ON mf.id_materia = m.id_materia
        JOIN usuarios u ON mf.id_instructor = u.id
        WHERE a.id_actividad = ?
    ");
    $stmt->execute([$id_actividad]);
    return $stmt->fetch();
}

// Obtener actividades próximas de una ficha
function obtenerActividadesProximas($id_ficha)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT a.*, m.materia, u.nombres, u.apellidos
        FROM actividades a
        JOIN materia_ficha mf ON a.id_materia_ficha = mf.id_materia_ficha
        JOIN materias m ON mf.id_materia = m.id_materia
        JOIN usuarios u ON mf.id_instructor = u.id
        WHERE mf.id_ficha = ? 
        AND a.fecha_entrega >= CURDATE()
        ORDER BY a.fecha_entrega ASC
        LIMIT 10
    ");
    $stmt->execute([$id_ficha]);
    return $stmt->fetchAll();
}

// Obtener actividades completadas de una ficha
function obtenerActividadesCompletadas($id_ficha, $id_usuario)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT a.*, m.materia, u.nombres, u.apellidos, au.fecha_entrega as fecha_entregada, au.nota
        FROM actividades a
        JOIN materia_ficha mf ON a.id_materia_ficha = mf.id_materia_ficha
        JOIN materias m ON mf.id_materia = m.id_materia
        JOIN usuarios u ON mf.id_instructor = u.id
        JOIN actividades_user au ON a.id_actividad = au.id_actividad
        WHERE mf.id_ficha = ? 
        AND au.id_user = ?
        AND au.id_estado_actividad = 1
        ORDER BY au.fecha_entrega DESC
        LIMIT 10
    ");
    $stmt->execute([$id_ficha, $id_usuario]);
    return $stmt->fetchAll();
}

// Obtener temas de foro recientes
function obtenerTemasForoRecientes($id_ficha)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT tf.*, u.nombres, u.apellidos, f.fecha_foro
        FROM temas_foro tf
        JOIN foros f ON tf.id_foro = f.id_foro
        JOIN materia_ficha mf ON f.id_materia_ficha = mf.id_materia_ficha
        JOIN usuarios u ON tf.id_user = u.id
        WHERE mf.id_ficha = ?
        ORDER BY tf.fecha_creacion DESC
        LIMIT 5
    ");
    $stmt->execute([$id_ficha]);
    return $stmt->fetchAll();
}

// Modificar la función obtenerInstructoresFicha para filtrar por materia específica
function obtenerInstructoresFicha($id_ficha, $id_materia = null)
{
    global $pdo;

    if ($id_materia) {
        // Obtener solo el instructor de la materia específica
        $stmt = $pdo->prepare("
            SELECT DISTINCT u.id, u.nombres, u.apellidos, u.correo, m.materia
            FROM usuarios u
            JOIN materia_ficha mf ON u.id = mf.id_instructor
            JOIN materias m ON mf.id_materia = m.id_materia
            WHERE mf.id_ficha = ? AND mf.id_materia = ? AND u.id_estado = 1
            ORDER BY u.nombres
        ");
        $stmt->execute([$id_ficha, $id_materia]);
    } else {
        // Obtener todos los instructores de la ficha (comportamiento original)
        $stmt = $pdo->prepare("
            SELECT DISTINCT u.id, u.nombres, u.apellidos, u.correo
            FROM usuarios u
            JOIN materia_ficha mf ON u.id = mf.id_instructor
            WHERE mf.id_ficha = ? AND u.id_estado = 1
            ORDER BY u.nombres
        ");
        $stmt->execute([$id_ficha]);
    }

    return $stmt->fetchAll();
}

// Obtener estudiantes de una ficha
function obtenerEstudiantesFicha($id_ficha)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT u.id, u.nombres, u.apellidos, u.correo
        FROM usuarios u
        JOIN user_ficha uf ON u.id = uf.id_user
        WHERE uf.id_ficha = ? AND u.id_estado = 1 AND uf.id_estado = 1
        ORDER BY u.nombres
    ");
    $stmt->execute([$id_ficha]);
    return $stmt->fetchAll();
}

// Obtener materias de una ficha
function obtenerMateriasFicha($id_ficha)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT m.materia, u.nombres, u.apellidos, t.trimestre
        FROM materia_ficha mf
        JOIN materias m ON mf.id_materia = m.id_materia
        JOIN usuarios u ON mf.id_instructor = u.id
        LEFT JOIN trimestre t ON mf.id_trimestre = t.id_trimestre
        WHERE mf.id_ficha = ?
        ORDER BY m.materia
    ");
    $stmt->execute([$id_ficha]);
    return $stmt->fetchAll();
}

// Modificar la función obtenerMateriaPrincipal para devolver también el id_materia_ficha
function obtenerMateriaPrincipal($id_ficha)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT m.materia, m.id_materia, mf.id_materia_ficha
        FROM materia_ficha mf
        JOIN materias m ON mf.id_materia = m.id_materia
        WHERE mf.id_ficha = ?
        ORDER BY mf.id_materia_ficha ASC
        LIMIT 1
    ");
    $stmt->execute([$id_ficha]);
    return $stmt->fetch();
}

// Modificar la función obtenerAnunciosRecientes para usar id_materia_ficha
function obtenerAnunciosRecientes($id_materia_ficha = null)
{
    global $pdo;

    if ($id_materia_ficha) {
        $stmt = $pdo->prepare("
            SELECT ai.id_anuncio, ai.titulo, ai.contenido as descripcion, ai.fecha_creacion, 
                   u.nombres, u.apellidos
            FROM anuncios_instructor ai
            JOIN materia_ficha mf ON ai.id_materia_ficha = mf.id_materia_ficha
            JOIN usuarios u ON mf.id_instructor = u.id
            WHERE ai.id_materia_ficha = ? AND ai.id_estado = 1
            ORDER BY ai.fecha_creacion DESC
            LIMIT 5
        ");
        $stmt->execute([$id_materia_ficha]);
    } else {
        $stmt = $pdo->prepare("
            SELECT ai.id_anuncio, ai.titulo, ai.contenido as descripcion, ai.fecha_creacion, 
                   u.nombres, u.apellidos
            FROM anuncios_instructor ai
            JOIN materia_ficha mf ON ai.id_materia_ficha = mf.id_materia_ficha
            JOIN usuarios u ON mf.id_instructor = u.id
            WHERE ai.id_estado = 1
            ORDER BY ai.fecha_creacion DESC
            LIMIT 5
        ");
        $stmt->execute();
    }

    return $stmt->fetchAll();
}

// Modificar la función para crear anuncios usando id_materia_ficha
function crearAnuncio($id_materia_ficha, $titulo, $contenido)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO anuncios_instructor (id_materia_ficha, titulo, contenido)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$id_materia_ficha, $titulo, $contenido]);
        return ['success' => true, 'message' => 'Anuncio creado exitosamente'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Error al crear el anuncio: ' . $e->getMessage()];
    }
}

// Modificar la función para verificar si un usuario es instructor usando id_materia_ficha
function esInstructorMateriaFicha($id_usuario, $id_materia_ficha)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM materia_ficha mf
        WHERE mf.id_instructor = ? AND mf.id_materia_ficha = ?
    ");
    $stmt->execute([$id_usuario, $id_materia_ficha]);
    $result = $stmt->fetch();

    return $result['count'] > 0;
}

// Mantener la función original para compatibilidad
function esInstructorMateria($id_usuario, $id_ficha, $id_materia)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM materia_ficha mf
        WHERE mf.id_instructor = ? AND mf.id_ficha = ? AND mf.id_materia = ?
    ");
    $stmt->execute([$id_usuario, $id_ficha, $id_materia]);
    $result = $stmt->fetch();

    return $result['count'] > 0;
}

// Obtener fichas de un usuario
function obtenerFichasUsuario($id_usuario)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT f.id_ficha, fo.nombre as nombre_formacion, j.jornada
        FROM fichas f
        JOIN user_ficha uf ON f.id_ficha = uf.id_ficha
        JOIN formacion fo ON f.id_formacion = fo.id_formacion
        JOIN jornada j ON f.id_jornada = j.id_jornada
        WHERE uf.id_user = ? AND f.id_estado = 1 AND uf.id_estado = 1
        ORDER BY fo.nombre
    ");
    $stmt->execute([$id_usuario]);
    return $stmt->fetchAll();
}

// Verificar si ya existe una entrega para una actividad y usuario
function verificarEntregaExistente($id_actividad, $id_usuario)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM actividades_user 
        WHERE id_actividad = ? AND id_user = ?
    ");
    $stmt->execute([$id_actividad, $id_usuario]);
    $result = $stmt->fetch();

    return $result['count'] > 0;
}

// Guardar entrega de actividad (solo usando actividades_user)
function guardarEntregaActividad($id_actividad, $id_usuario, $contenido, $archivos)
{
    global $pdo;

    try {
        // Verificar si la actividad está vencida
        if (actividadEstaVencida($id_actividad)) {
            return ['success' => false, 'message' => 'No se puede entregar esta actividad porque ya está vencida.'];
        }

        // Verificar si ya existe una entrega
        if (verificarEntregaExistente($id_actividad, $id_usuario)) {
            return ['success' => false, 'message' => 'Ya has entregado esta actividad anteriormente.'];
        }

        // Crear información de archivos como JSON para el campo archivo
        $archivos_json = !empty($archivos) ? json_encode($archivos) : null;

        // Insertar la entrega en actividades_user usando 'contenido' y 'archivo'
        $stmt = $pdo->prepare("
            INSERT INTO actividades_user (id_actividad, id_user, contenido, archivo, fecha_entrega, id_estado_actividad)
            VALUES (?, ?, ?, ?, NOW(), 1)
        ");
        $stmt->execute([$id_actividad, $id_usuario, $contenido, $archivos_json]);

        return ['success' => true, 'message' => 'Entrega guardada exitosamente'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Error al guardar la entrega: ' . $e->getMessage()];
    }
}

// Verificar si una actividad está vencida
function actividadEstaVencida($id_actividad)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT fecha_entrega 
        FROM actividades 
        WHERE id_actividad = ?
    ");
    $stmt->execute([$id_actividad]);
    $actividad = $stmt->fetch();

    if (!$actividad) {
        return true; // Si no existe la actividad, considerarla vencida
    }

    return strtotime($actividad['fecha_entrega']) < time();
}

// Obtener entregas de un usuario para una actividad específica
function obtenerEntregaUsuario($id_actividad, $id_usuario)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT * FROM actividades_user
        WHERE id_actividad = ? AND id_user = ?
    ");
    $stmt->execute([$id_actividad, $id_usuario]);
    return $stmt->fetch();
}

// Modificar la función para incluir todas las actividades (pendientes, vencidas y entregadas)
function obtenerTodasActividadesConEstado($id_ficha, $id_usuario)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT a.*, m.materia, u.nombres, u.apellidos,
               au.id_actividad_user as entrega_id,
               CASE 
                   WHEN au.id_actividad_user IS NOT NULL THEN 'entregada'
                   WHEN a.fecha_entrega < NOW() THEN 'vencida'
                   ELSE 'pendiente'
               END as estado_entrega
        FROM actividades a
        JOIN materia_ficha mf ON a.id_materia_ficha = mf.id_materia_ficha
        JOIN materias m ON mf.id_materia = m.id_materia
        JOIN usuarios u ON mf.id_instructor = u.id
        LEFT JOIN actividades_user au ON a.id_actividad = au.id_actividad AND au.id_user = ?
        WHERE mf.id_ficha = ?
        ORDER BY a.fecha_entrega ASC
    ");
    $stmt->execute([$id_usuario, $id_ficha]);
    return $stmt->fetchAll();
}

// Mantener la función original para compatibilidad
function obtenerActividadesProximasConEstado($id_ficha, $id_usuario)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT a.*, m.materia, u.nombres, u.apellidos,
               au.id_actividad_user as entrega_id,
               CASE 
                   WHEN au.id_actividad_user IS NOT NULL THEN 'entregada'
                   WHEN a.fecha_entrega < NOW() THEN 'vencida'
                   ELSE 'pendiente'
               END as estado_entrega
        FROM actividades a
        JOIN materia_ficha mf ON a.id_materia_ficha = mf.id_materia_ficha
        JOIN materias m ON mf.id_materia = m.id_materia
        JOIN usuarios u ON mf.id_instructor = u.id
        LEFT JOIN actividades_user au ON a.id_actividad = au.id_actividad AND au.id_user = ?
        WHERE mf.id_ficha = ? 
        AND a.fecha_entrega >= CURDATE()
        ORDER BY a.fecha_entrega ASC
        LIMIT 10
    ");
    $stmt->execute([$id_usuario, $id_ficha]);
    return $stmt->fetchAll();
}




// Existing functions from your original file...
// (Keep all your existing functions and add these new ones)

// Obtener todos los foros de una ficha
function obtenerForosFicha($id_ficha)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT f.*, mf.id_materia_ficha, m.materia, u.nombres, u.apellidos,
               (SELECT COUNT(*) FROM temas_foro WHERE id_foro = f.id_foro) as cantidad_temas
        FROM foros f
        JOIN materia_ficha mf ON f.id_materia_ficha = mf.id_materia_ficha
        JOIN materias m ON mf.id_materia = m.id_materia
        JOIN usuarios u ON mf.id_instructor = u.id
        WHERE mf.id_ficha = ?
        ORDER BY f.fecha_foro DESC
    ");
    $stmt->execute([$id_ficha]);
    return $stmt->fetchAll();
}

// Obtener detalle de un foro específico
function obtenerForoDetalle($id_foro)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT f.*, mf.id_materia_ficha, mf.id_ficha, m.materia, u.nombres, u.apellidos
        FROM foros f
        JOIN materia_ficha mf ON f.id_materia_ficha = mf.id_materia_ficha
        JOIN materias m ON mf.id_materia = m.id_materia
        JOIN usuarios u ON mf.id_instructor = u.id
        WHERE f.id_foro = ?
    ");
    $stmt->execute([$id_foro]);
    return $stmt->fetch();
}

// Obtener todos los temas de un foro específico
function obtenerTemasForo($id_foro)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT tf.*, u.nombres, u.apellidos, 
               (SELECT COUNT(*) FROM respuesta_foro WHERE id_tema_foro = tf.id_tema_foro) as cantidad_respuestas
        FROM temas_foro tf
        JOIN usuarios u ON tf.id_user = u.id
        WHERE tf.id_foro = ?
        ORDER BY tf.fecha_creacion DESC
    ");
    $stmt->execute([$id_foro]);
    return $stmt->fetchAll();
}

// Obtener detalle de un tema específico
function obtenerDetalleTema($id_tema)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT tf.*, u.nombres, u.apellidos, f.id_materia_ficha, mf.id_ficha, m.materia
        FROM temas_foro tf
        JOIN usuarios u ON tf.id_user = u.id
        JOIN foros f ON tf.id_foro = f.id_foro
        JOIN materia_ficha mf ON f.id_materia_ficha = mf.id_materia_ficha
        JOIN materias m ON mf.id_materia = m.id_materia
        WHERE tf.id_tema_foro = ?
    ");
    $stmt->execute([$id_tema]);
    return $stmt->fetch();
}

// Obtener todas las respuestas de un tema
function obtenerRespuestasTema($id_tema)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT rf.*, u.nombres, u.apellidos
        FROM respuesta_foro rf
        JOIN usuarios u ON rf.id_user = u.id
        WHERE rf.id_tema_foro = ?
        ORDER BY rf.fecha_respuesta ASC
    ");
    $stmt->execute([$id_tema]);
    return $stmt->fetchAll();
}

// Crear un nuevo tema en el foro
function crearTemaForo($id_foro, $titulo, $descripcion, $id_usuario)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO temas_foro (id_foro, titulo, descripcion, fecha_creacion, id_user)
            VALUES (?, ?, ?, NOW(), ?)
        ");
        $stmt->execute([$id_foro, $titulo, $descripcion, $id_usuario]);
        return ['success' => true, 'id_tema' => $pdo->lastInsertId()];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Error al crear el tema: ' . $e->getMessage()];
    }
}

// Crear una nueva respuesta en un tema
function crearRespuestaForo($id_tema, $descripcion, $id_usuario)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO respuesta_foro (id_tema_foro, descripcion, fecha_respuesta, id_user)
            VALUES (?, ?, NOW(), ?)
        ");
        $stmt->execute([$id_tema, $descripcion, $id_usuario]);
        return ['success' => true, 'id_respuesta' => $pdo->lastInsertId()];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Error al crear la respuesta: ' . $e->getMessage()];
    }
}

// Verificar si un usuario puede participar en un foro (está en la ficha)
function puedeParticiparForo($id_usuario, $id_materia_ficha)
{
    global $pdo;

    // Verificar si es instructor de la materia
    $esInstructor = esInstructorMateriaFicha($id_usuario, $id_materia_ficha);
    if ($esInstructor) {
        return true;
    }

    // Verificar si es estudiante de la ficha
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM user_ficha uf
        JOIN materia_ficha mf ON uf.id_ficha = mf.id_ficha
        WHERE uf.id_user = ? AND mf.id_materia_ficha = ? AND uf.id_estado = 1
    ");
    $stmt->execute([$id_usuario, $id_materia_ficha]);
    $result = $stmt->fetch();

    return $result['count'] > 0;
}





if (!function_exists('formatearFecha')) {
    function formatearFecha($fecha) {
        if (!$fecha) return 'Sin fecha';

        $fechaObj = new DateTime($fecha);
        $ahora = new DateTime();
        $diferencia = $ahora->diff($fechaObj);

        if ($diferencia->days == 0) {
            return 'Hoy';
        } elseif ($diferencia->days == 1) {
            return $diferencia->invert ? 'Ayer' : 'Mañana';
        } else {
            return $diferencia->invert ?
                'Hace ' . $diferencia->days . ' días' :
                'En ' . $diferencia->days . ' días';
        }
    }
}

if (!function_exists('obtenerIniciales')) {
    function obtenerIniciales($nombre) {
        $palabras = explode(' ', $nombre);
        $iniciales = '';
        foreach ($palabras as $palabra) {
            if (!empty($palabra)) {
                $iniciales .= strtoupper(substr($palabra, 0, 1));
            }
        }
        return substr($iniciales, 0, 2);
    }
}

function eliminarEntregaActividad($id_actividad, $id_usuario) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("DELETE FROM actividades_user WHERE id_actividad = :id_actividad AND id_user = :id_usuario");
        $stmt->execute([
            ':id_actividad' => $id_actividad,
            ':id_usuario' => $id_usuario
        ]);

        return ['success' => true];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Error al eliminar la entrega: ' . $e->getMessage()
        ];
    }
}

?>


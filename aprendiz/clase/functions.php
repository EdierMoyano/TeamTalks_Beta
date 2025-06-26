<?php
require_once 'config.php';

// Función para obtener datos de sesión del usuario
function obtenerDatosSesion()
{
    if (!isset($_SESSION['documento'])) {
        return null;
    }

    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT u.id, u.nombres, u.apellidos, uf.id_ficha, mf.id_materia_ficha, mf.id_materia
        FROM usuarios u
        JOIN user_ficha uf ON u.id = uf.id_user
        JOIN materia_ficha mf ON uf.id_ficha = mf.id_ficha
        WHERE u.id = ? AND uf.id_estado = 1
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['documento']]);
    return $stmt->fetch();
}

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

// Obtener información de la materia principal de una ficha
function obtenerMateriaPrincipal($id_ficha)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT m.materia, mf.id_materia_ficha, mf.id_materia
        FROM materia_ficha mf
        JOIN materias m ON mf.id_materia = m.id_materia
        WHERE mf.id_ficha = ?
        ORDER BY mf.id_materia_ficha ASC
        LIMIT 1
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
               mf.id_ficha, mf.id_materia_ficha,
               a.archivo1 as archivo_instructor_1,
               a.archivo2 as archivo_instructor_2, 
               a.archivo3 as archivo_instructor_3
        FROM actividades a
        JOIN materia_ficha mf ON a.id_materia_ficha = mf.id_materia_ficha
        JOIN materias m ON mf.id_materia = m.id_materia
        JOIN usuarios u ON mf.id_instructor = u.id
        WHERE a.id_actividad = ?
    ");
    $stmt->execute([$id_actividad]);
    return $stmt->fetch();
}

// Función CORREGIDA para obtener todas las actividades con estados
function obtenerTodasActividadesConEstado($id_materia_ficha, $id_usuario)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT a.*, m.materia, u.nombres, u.apellidos,
               au.id_actividad_user as entrega_id,
               au.nota, au.comentario_inst, au.fecha_entrega as fecha_entregada,
               e.estado as estado_nombre,
               au.id_estado_actividad,
               NOW() as fecha_actual
        FROM actividades a
        JOIN materia_ficha mf ON a.id_materia_ficha = mf.id_materia_ficha
        JOIN materias m ON mf.id_materia = m.id_materia
        JOIN usuarios u ON mf.id_instructor = u.id
        LEFT JOIN actividades_user au ON a.id_actividad = au.id_actividad AND au.id_user = ?
        LEFT JOIN estado e ON au.id_estado_actividad = e.id_estado
        WHERE a.id_materia_ficha = ?
        ORDER BY a.fecha_entrega ASC
    ");
    $stmt->execute([$id_usuario, $id_materia_ficha]);
    $actividades = $stmt->fetchAll();
    
    // Procesar cada actividad para determinar su estado correctamente
    foreach ($actividades as &$actividad) {
        // Crear objeto DateTime para la fecha de entrega
        $fechaEntrega = new DateTime($actividad['fecha_entrega']);
        
        // Si la fecha de entrega no tiene hora específica (es 00:00:00), 
        // ajustarla al final del día (23:59:59)
        if ($fechaEntrega->format('H:i:s') === '00:00:00') {
            $fechaEntrega->setTime(23, 59, 59);
        }
        
        $fechaActual = new DateTime();
        
        // Determinar si está vencida comparando fechas
        $estaVencida = $fechaEntrega < $fechaActual;
        
        // Determinar el estado basado en la lógica correcta
        if ($actividad['id_estado_actividad'] == 8) {
            $estado = 'entregada';
        } elseif (is_null($actividad['id_estado_actividad']) && $estaVencida) {
            $estado = 'vencida';
        } elseif (is_null($actividad['id_estado_actividad']) && !$estaVencida) {
            $estado = 'pendiente';
        } else {
            $estado = 'pendiente'; // Estado por defecto
        }
        
        $actividad['estado_entrega'] = $estado;
        
        // Debug: agregar información adicional
        $actividad['debug_info'] = [
            'tiene_entrega' => !is_null($actividad['entrega_id']),
            'estado_actividad' => $actividad['id_estado_actividad'],
            'fecha_entrega_original' => $actividad['fecha_entrega'],
            'fecha_actual_bd' => $actividad['fecha_actual'],
            'fecha_entrega_ajustada' => $fechaEntrega->format('Y-m-d H:i:s'),
            'fecha_actual_obj' => $fechaActual->format('Y-m-d H:i:s'),
            'esta_vencida' => $estaVencida,
            'estado_calculado' => $estado,
            'comparacion' => $fechaEntrega->format('Y-m-d H:i:s') . ' vs ' . $fechaActual->format('Y-m-d H:i:s'),
            'hora_original' => DateTime::createFromFormat('Y-m-d H:i:s', $actividad['fecha_entrega'])->format('H:i:s')
        ];
    }
    
    return $actividades;
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

// Función corregida para guardar entregas con manejo correcto de archivos
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
        
        if (count($archivos) > 3) {
            return ['success' => false, 'message' => 'Solo se permiten hasta 3 archivos por entrega.'];
        }

        // Extraer hasta tres archivos del array $archivos
        $archivo1 = isset($archivos[0]) ? $archivos[0] : null;
        $archivo2 = isset($archivos[1]) ? $archivos[1] : null;
        $archivo3 = isset($archivos[2]) ? $archivos[2] : null;

        // Insertar la entrega en actividades_user con estado "Entregado" (id_estado = 8)
        $stmt = $pdo->prepare("
            INSERT INTO actividades_user (
                id_actividad, id_user, contenido, archivo1, archivo2, archivo3, 
                fecha_entrega, id_estado_actividad
            ) VALUES (?, ?, ?, ?, ?, ?, NOW(), 8)
        ");

        $stmt->execute([
            $id_actividad,
            $id_usuario,
            $contenido,
            $archivo1,
            $archivo2,
            $archivo3
        ]);

        return ['success' => true, 'message' => 'Entrega guardada exitosamente'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Error al guardar la entrega: ' . $e->getMessage()];
    }
}

// Verificar si una actividad está vencida - FUNCIÓN CORREGIDA
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

    // Crear objeto DateTime para la fecha de entrega
    $fechaEntrega = new DateTime($actividad['fecha_entrega']);
    
    // Si la fecha de entrega no tiene hora específica (es 00:00:00), 
    // ajustarla al final del día (23:59:59)
    if ($fechaEntrega->format('H:i:s') === '00:00:00') {
        $fechaEntrega->setTime(23, 59, 59);
    }
    
    $fechaActual = new DateTime();
    
    return $fechaEntrega < $fechaActual;
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

function eliminarEntregaActividad($id_actividad, $id_usuario) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("DELETE FROM actividades_user WHERE id_actividad = ? AND id_user = ?");
        $stmt->execute([$id_actividad, $id_usuario]);

        return ['success' => true];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Error al eliminar la entrega: ' . $e->getMessage()
        ];
    }
}

// Obtener temas de foro recientes usando datos de sesión
function obtenerTemasForoRecientes($id_materia_ficha)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT tf.*, u.nombres, u.apellidos, f.fecha_foro
        FROM temas_foro tf
        JOIN foros f ON tf.id_foro = f.id_foro
        JOIN materia_ficha mf ON f.id_materia_ficha = mf.id_materia_ficha
        JOIN usuarios u ON tf.id_user = u.id
        WHERE mf.id_materia_ficha = ?
        ORDER BY tf.fecha_creacion DESC
        LIMIT 5
    ");
    $stmt->execute([$id_materia_ficha]);
    return $stmt->fetchAll();
}

// Obtener instructores de la materia
function obtenerInstructoresFicha($id_ficha, $id_materia = null)
{
    global $pdo;

    if ($id_materia) {
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

// Obtener anuncios recientes
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

// Verificar si un usuario es instructor de una materia_ficha
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

// Funciones para foros usando datos de sesión
function obtenerForosSesion()
{
    $datosSesion = obtenerDatosSesion();
    if (!$datosSesion) {
        return [];
    }

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
    $stmt->execute([$datosSesion['id_ficha']]);
    return $stmt->fetchAll();
}

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

function obtenerDetalleTema($id_tema)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT tf.*, u.nombres, u.apellidos, f.id_foro, f.id_materia_ficha, mf.id_ficha, m.materia
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

function crearTemaForoSesion($id_foro, $titulo, $descripcion)
{
    $datosSesion = obtenerDatosSesion();
    if (!$datosSesion) {
        return ['success' => false, 'message' => 'Usuario no autenticado'];
    }

    global $pdo;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO temas_foro (id_foro, titulo, descripcion, fecha_creacion, id_user)
            VALUES (?, ?, ?, NOW(), ?)
        ");
        $stmt->execute([$id_foro, $titulo, $descripcion, $datosSesion['id']]);
        return ['success' => true, 'id_tema' => $pdo->lastInsertId()];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Error al crear el tema: ' . $e->getMessage()];
    }
}

function crearRespuestaForoSesion($id_tema, $descripcion)
{
    $datosSesion = obtenerDatosSesion();
    if (!$datosSesion) {
        return ['success' => false, 'message' => 'Usuario no autenticado'];
    }

    global $pdo;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO respuesta_foro (id_tema_foro, descripcion, fecha_respuesta, id_user)
            VALUES (?, ?, NOW(), ?)
        ");
        $stmt->execute([$id_tema, $descripcion, $datosSesion['id']]);
        return ['success' => true, 'id_respuesta' => $pdo->lastInsertId()];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Error al crear la respuesta: ' . $e->getMessage()];
    }
}

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

// Funciones de utilidad
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

// Función para crear respuesta a un comentario específico
function crearRespuestaAComentarioSesion($id_tema, $descripcion, $id_respuesta_padre = null)
{
    $datosSesion = obtenerDatosSesion();
    if (!$datosSesion) {
        return ['success' => false, 'message' => 'Usuario no autenticado'];
    }

    global $pdo;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO respuesta_foro (id_tema_foro, descripcion, fecha_respuesta, id_user, id_respuesta_padre)
            VALUES (?, ?, NOW(), ?, ?)
        ");
        $stmt->execute([$id_tema, $descripcion, $datosSesion['id'], $id_respuesta_padre]);
        return ['success' => true, 'id_respuesta' => $pdo->lastInsertId()];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Error al crear la respuesta: ' . $e->getMessage()];
    }
}

// Función para obtener respuestas con jerarquía
function obtenerRespuestasConJerarquia($id_tema)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT rf.*, u.nombres, u.apellidos,
               (SELECT COUNT(*) FROM respuesta_foro WHERE id_respuesta_padre = rf.id_respuesta_foro) as tiene_respuestas
        FROM respuesta_foro rf
        JOIN usuarios u ON rf.id_user = u.id
        WHERE rf.id_tema_foro = ?
        ORDER BY rf.id_respuesta_padre ASC, rf.fecha_respuesta ASC
    ");
    $stmt->execute([$id_tema]);
    $todasRespuestas = $stmt->fetchAll();
    
    // Organizar respuestas en jerarquía
    $respuestasPrincipales = [];
    $respuestasHijas = [];
    
    foreach ($todasRespuestas as $respuesta) {
        if (is_null($respuesta['id_respuesta_padre'])) {
            $respuestasPrincipales[] = $respuesta;
        } else {
            if (!isset($respuestasHijas[$respuesta['id_respuesta_padre']])) {
                $respuestasHijas[$respuesta['id_respuesta_padre']] = [];
            }
            $respuestasHijas[$respuesta['id_respuesta_padre']][] = $respuesta;
        }
    }
    
    return ['principales' => $respuestasPrincipales, 'hijas' => $respuestasHijas];
}
?>

<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['documento'])) {
    echo json_encode(['error' => 'Aprendiz no autenticado.']);
    exit;
}

$idAprendiz = $_SESSION['documento'];

// === DETECCIÓN DE ENTORNO (Local o Producción) ===
$isLocal = (
    strpos($_SERVER['HTTP_HOST'], 'localhost') !== false ||
    strpos($_SERVER['DOCUMENT_ROOT'], 'htdocs') !== false
);

if ($isLocal) {
    // Local - XAMPP
    $host = 'localhost';
    $db   = 'u148394603_teamtalks';
    $user = 'root';
    $pass = '';
} else {
    // Producción - Hostinger
    $host = 'localhost';
    $db   = 'u148394603_teamtalks';
    $user = 'u148394603_teamtalks';
    $pass = 'TeamTalks2901879'; 
}

$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // Función para determinar el trimestre actual
    function obtenerTrimestreActual($pdo)
    {
        $mesActual = date('n'); // Mes actual (1-12)

        $sql = "SELECT id_trimestre, trimestre 
                FROM trimestre 
                WHERE :mes BETWEEN mes_inicio AND mes_fin 
                LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['mes' => $mesActual]);

        return $stmt->fetch();
    }

    // Función para obtener nota definitiva de una materia
    function obtenerNotaDefinitiva($pdo, $idAprendiz, $idMateriaFicha)
    {
        $sql = "SELECT AVG(au.nota) as nota_promedio, COUNT(au.nota) as total_notas
                FROM actividades a
                JOIN actividades_user au ON a.id_actividad = au.id_actividad
                WHERE a.id_materia_ficha = :idMateriaFicha 
                AND au.id_user = :idAprendiz 
                AND au.nota IS NOT NULL";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'idMateriaFicha' => $idMateriaFicha,
            'idAprendiz' => $idAprendiz
        ]);

        $resultado = $stmt->fetch();

        if ($resultado['total_notas'] > 0) {
            return $resultado['nota_promedio'];
        }

        return null;
    }

    // Obtener trimestre actual
    $trimestreActual = obtenerTrimestreActual($pdo);

    if (!$trimestreActual) {
        echo json_encode(['error' => 'No se pudo determinar el trimestre actual.']);
        exit;
    }

    // Consulta principal
    $sql = "SELECT 
                mf.id_materia_ficha AS id_clase,
                m.materia AS nombre_clase,
                CONCAT(TRIM(u.nombres), ' ', TRIM(u.apellidos)) AS nombre_profesor,
                f.id_ficha AS numero_fichas,
                mf.id_trimestre,
                t.trimestre AS nombre_trimestre,
                mf.id_estado as estado_materia
            FROM user_ficha uf
            JOIN fichas f ON uf.id_ficha = f.id_ficha
            JOIN materia_ficha mf ON mf.id_ficha = f.id_ficha
            JOIN materias m ON mf.id_materia = m.id_materia
            JOIN usuarios u ON mf.id_instructor = u.id
            JOIN trimestre t ON mf.id_trimestre = t.id_trimestre
            WHERE uf.id_user = :idAprendiz";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['idAprendiz' => $idAprendiz]);
    $todasLasMaterias = $stmt->fetchAll();

    $materiasVisibles = [];

    foreach ($todasLasMaterias as $materia) {
        $notaDefinitiva = obtenerNotaDefinitiva($pdo, $idAprendiz, $materia['id_clase']);
        $esTrimestreActual = $materia['id_trimestre'] == $trimestreActual['id_trimestre'];
        $esTrimestreAnterior = $materia['id_trimestre'] < $trimestreActual['id_trimestre'];

        $mostrarMateria = false;
        $esReprobada = false;
        $estadoVisualizacion = 'normal';

        if ($esTrimestreActual) {
            $mostrarMateria = true;
            $estadoVisualizacion = 'actual';
        } elseif ($esTrimestreAnterior) {
            if ($notaDefinitiva === null) {
                $mostrarMateria = true;
                $esReprobada = true;
                $estadoVisualizacion = 'sin_nota';
            } elseif ($notaDefinitiva < 3.0) {
                $mostrarMateria = true;
                $esReprobada = true;
                $estadoVisualizacion = 'reprobada';
            }
        }

        if ($mostrarMateria) {
            $materia['es_reprobada'] = $esReprobada;
            $materia['estado_visualizacion'] = $estadoVisualizacion;
            $materia['nota_definitiva'] = $notaDefinitiva;
            $materia['es_trimestre_actual'] = $esTrimestreActual;
            $materia['imagen'] = "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSLlPxXwu6GBz2YNT0kRZhPElAeyZArGF2evQ&s";

            $materiasVisibles[] = $materia;
        }
    }

    echo json_encode([
        'materias' => $materiasVisibles,
        'trimestre_actual' => $trimestreActual,
        'total_materias' => count($materiasVisibles),
        'fecha_consulta' => date('Y-m-d H:i:s')
    ]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
}

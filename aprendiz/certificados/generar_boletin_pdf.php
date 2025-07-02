<?php
session_start();
require_once '../clase/config.php';
require_once '../clase/functions.php';

if (!isset($_SESSION['documento'])) {
    header('Location: ../../login/login.php');
    exit;
}

$id_usuario = $_SESSION['documento'];
$id_trimestre = $_GET['trimestre'] ?? null;

if (!$id_trimestre) {
    die("Error: Trimestre no especificado");
}

// Obtener datos del usuario
$stmt = $pdo->prepare("
    SELECT u.*, uf.id_ficha, f.*, fo.nombre as nombre_formacion
    FROM usuarios u
    JOIN user_ficha uf ON u.id = uf.id_user
    JOIN fichas f ON uf.id_ficha = f.id_ficha
    JOIN formacion fo ON f.id_formacion = fo.id_formacion
    WHERE u.id = ? AND uf.id_estado = 1
    LIMIT 1
");
$stmt->execute([$id_usuario]);
$datosUsuario = $stmt->fetch();

if (!$datosUsuario) {
    die("Error: No se encontraron datos del usuario");
}

// Obtener información del trimestre
$stmt_trimestre = $pdo->prepare("SELECT trimestre FROM trimestre WHERE id_trimestre = ?");
$stmt_trimestre->execute([$id_trimestre]);
$trimestre_info = $stmt_trimestre->fetch();

if (!$trimestre_info) {
    die("Error: Trimestre no encontrado");
}

// Obtener materias con promedios del trimestre específico
$stmt = $pdo->prepare("
    SELECT 
        m.materia as nombre,
        AVG(au.nota) as promedio_final,
        COUNT(au.nota) as total_actividades
    FROM materia_ficha mf
    JOIN materias m ON mf.id_materia = m.id_materia
    LEFT JOIN actividades a ON mf.id_materia_ficha = a.id_materia_ficha
    LEFT JOIN actividades_user au ON a.id_actividad = au.id_actividad 
        AND au.id_user = ? 
        AND au.nota IS NOT NULL 
        AND au.nota > 0
        AND au.id_estado_actividad = 8
    WHERE mf.id_ficha = ? AND mf.id_trimestre = ?
    GROUP BY m.id_materia, m.materia
    HAVING COUNT(a.id_actividad) > 0
    ORDER BY m.materia
");

$stmt->execute([$id_usuario, $datosUsuario['id_ficha'], $id_trimestre]);
$materias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Función para convertir fecha a español
function fechaEspanol($fecha) {
    $meses = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
        5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
        9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'
    ];
    
    $dia = date('d', strtotime($fecha));
    $mes = $meses[date('n', strtotime($fecha))];
    $año = date('Y', strtotime($fecha));
    
    return "$dia de $mes de $año";
}

// Configurar headers para HTML (no PDF)
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boletín de Calificaciones</title>
    <style>
        @page {
            size: A4;
            margin: 0;
        }
        
        @media print {
            body { 
                margin: 0; 
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .no-print { display: none !important; }
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: white;
            width: 210mm;
            height: 297mm;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .boletin {
            width: 190mm;
            height: 270mm;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
        }
        
        .header {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 3px solid #0E4A86;
            padding-bottom: 15px;
        }
        
        .logo-text {
            font-size: 32px;
            font-weight: bold;
            color: #0E4A86;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .titulo {
            font-size: 24px;
            font-weight: bold;
            color: #0E4A86;
            margin-bottom: 5px;
        }
        
        .subtitulo {
            font-size: 14px;
            color: #666;
        }
        
        .info-estudiante {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .info-label {
            font-weight: bold;
            color: #333;
        }
        
        .info-value {
            color: #666;
        }
        
        .materias-section {
            flex: 1;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: bold;
            color: #0E4A86;
            margin-bottom: 15px;
            border-bottom: 2px solid #0E4A86;
            padding-bottom: 8px;
        }
        
        .materia-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            margin-bottom: 8px;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 4px solid #0E4A86;
        }
        
        .materia-nombre {
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        
        .materia-promedio {
            font-weight: bold;
            padding: 6px 12px;
            border-radius: 15px;
            color: white;
            font-size: 12px;
        }
        
        .promedio-alto {
            background-color: #28a745;
        }
        
        .promedio-medio {
            background-color: #ffc107;
            color: #333;
        }
        
        .promedio-bajo {
            background-color: #dc3545;
        }
        
        .promedio-sin {
            background-color: #6c757d;
        }
        
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #dee2e6;
            padding-top: 15px;
        }
        
        .fecha-generacion {
            text-align: right;
            font-size: 12px;
            color: #666;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="boletin">
        <div class="header">
            <div class="logo-text">TeamTalks</div>
            <div class="titulo">Boletín de Calificaciones</div>
            <div class="subtitulo">Plataforma de Educación Virtual</div>
        </div>
        
        <div class="info-estudiante">
            <div class="info-row">
                <span class="info-label">Estudiante:</span>
                <span class="info-value"><?= htmlspecialchars($datosUsuario['nombres'] . ' ' . $datosUsuario['apellidos']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Documento:</span>
                <span class="info-value"><?= htmlspecialchars($datosUsuario['id']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Programa:</span>
                <span class="info-value"><?= htmlspecialchars($datosUsuario['nombre_formacion']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Ficha:</span>
                <span class="info-value"><?= htmlspecialchars($datosUsuario['id_ficha']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Trimestre:</span>
                <span class="info-value"><?= htmlspecialchars($trimestre_info['trimestre']) ?> Trimestre</span>
            </div>
        </div>
        
        <div class="materias-section">
            <div class="section-title">Calificaciones por Materia - <?= htmlspecialchars($trimestre_info['trimestre']) ?> Trimestre</div>
            
            <?php if (!empty($materias)): ?>
                <?php foreach ($materias as $materia): ?>
                    <?php 
                    $promedio = $materia['promedio_final'] ? round($materia['promedio_final'], 2) : null;
                    $clasePromedio = 'promedio-sin';
                    
                    if ($promedio !== null) {
                        if ($promedio >= 4.0) {
                            $clasePromedio = 'promedio-alto';
                        } elseif ($promedio >= 3.0) {
                            $clasePromedio = 'promedio-medio';
                        } else {
                            $clasePromedio = 'promedio-bajo';
                        }
                    }
                    ?>
                    <div class="materia-item">
                        <span class="materia-nombre"><?= htmlspecialchars($materia['nombre']) ?></span>
                        <span class="materia-promedio <?= $clasePromedio ?>">
                            <?= $promedio !== null ? $promedio : 'Sin calificar' ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; color: #666; font-style: italic;">No hay materias calificadas en este trimestre</p>
            <?php endif; ?>
        </div>
        
        <div class="fecha-generacion">
            Generado el: <?= fechaEspanol(date('Y-m-d')) ?>
        </div>
        
        <div class="footer">
            <p>Este documento es una constancia académica oficial generada por el sistema TeamTalks</p>
            <p>Para verificar la autenticidad de este documento, contacte con la institución</p>
        </div>
    </div>
    
    <script>
        // Auto-imprimir y cerrar
        window.onload = function() {
            // Cambiar el título del documento
            document.title = 'Boletin_<?= $datosUsuario["nombres"] ?>_<?= $datosUsuario["apellidos"] ?>_<?= $trimestre_info["trimestre"] ?>_Trimestre';
            
            // Imprimir automáticamente
            setTimeout(function() {
                window.print();
            }, 500);
            
            // Cerrar ventana después de imprimir (opcional)
            window.addEventListener('afterprint', function() {
                setTimeout(function() {
                    window.close();
                }, 1000);
            });
        };
    </script>
</body>
</html>

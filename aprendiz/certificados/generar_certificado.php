<?php
session_start();
require_once '../clase/config.php';
require_once '../clase/functions.php';

if (!isset($_SESSION['documento'])) {
    header('Location: ../../login/login.php');
    exit;
}

$tipo = $_GET['tipo'] ?? 'general';
$id_usuario = $_SESSION['documento'];

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
    <title>Certificado de Estudio</title>
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
            font-family: 'Georgia', serif;
            margin: 0;
            padding: 0;
            background: white;
            width: 210mm;
            height: 297mm;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .certificado {
            width: 190mm;
            height: 270mm;
            background: white;
            padding: 40px;
            border: 8px solid #0E4A86;
            border-radius: 20px;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background-image: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgdmlld0JveD0iMCAwIDIwMCAyMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxwYXRoIGQ9Ik0xMDAgMTBMMTgwIDUwVjE1MEwxMDAgMTkwTDIwIDE1MFY1MEwxMDAgMTBaIiBzdHJva2U9IiMwRTRBODYiIHN0cm9rZS13aWR0aD0iMiIgZmlsbD0ibm9uZSIgb3BhY2l0eT0iMC4wNSIvPgo8Y2lyY2xlIGN4PSIxMDAiIGN5PSIxMDAiIHI9IjQwIiBzdHJva2U9IiMwRTRBODYiIHN0cm9rZS13aWR0aD0iMiIgZmlsbD0ibm9uZSIgb3BhY2l0eT0iMC4wNSIvPgo8L3N2Zz4K');
            background-repeat: no-repeat;
            background-position: center;
            background-size: 300px 300px;
        }
        
        .certificado::before {
            content: "";
            position: absolute;
            top: 15px;
            left: 15px;
            right: 15px;
            bottom: 15px;
            border: 2px solid #0E4A86;
            border-radius: 12px;
            pointer-events: none;
        }
        
        .header {
            text-align: center;
            position: relative;
            z-index: 1;
        }
        
        .logo-text {
            font-size: 42px;
            font-weight: bold;
            color: #0E4A86;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 3px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }
        
        .titulo {
            font-size: 32px;
            font-weight: bold;
            color: #0E4A86;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .subtitulo {
            font-size: 16px;
            color: #64748b;
            margin-bottom: 30px;
        }
        
        .contenido {
            text-align: center;
            line-height: 1.6;
            font-size: 15px;
            color: #374151;
            position: relative;
            z-index: 1;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .nombre-estudiante {
            font-size: 28px;
            font-weight: bold;
            color: #0E4A86;
            margin: 25px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-decoration: underline;
            text-decoration-color: #0E4A86;
        }
        
        .programa {
            font-size: 22px;
            font-weight: 600;
            color: #1e293b;
            margin: 20px 0;
            font-style: italic;
        }
        
        .ficha {
            font-size: 16px;
            color: #64748b;
            margin: 15px 0;
        }
        
        .footer {
            position: relative;
            z-index: 1;
        }
        
        .fecha {
            text-align: right;
            font-size: 14px;
            color: #64748b;
            margin-bottom: 40px;
        }
        
        .firma {
            display: flex;
            justify-content: space-between;
            align-items: end;
        }
        
        .firma-item {
            text-align: center;
            width: 180px;
        }
        
        .firma-linea {
            border-top: 2px solid #0E4A86;
            margin-bottom: 8px;
        }
        
        .firma-texto {
            font-size: 14px;
            color: #374151;
        }
    </style>
</head>
<body>
    <div class="certificado">
        <div class="header">
            <div class="logo-text">TeamTalks</div>
            <div class="titulo">Certificado de Estudio</div>
            <div class="subtitulo">Plataforma de Educación Virtual</div>
        </div>
        
        <div class="contenido">
            <p>Por medio del presente documento se certifica que:</p>
            
            <div class="nombre-estudiante">
                <?= htmlspecialchars($datosUsuario['nombres'] . ' ' . $datosUsuario['apellidos']) ?>
            </div>
            
            <p>Identificado(a) con documento número <strong><?= htmlspecialchars($datosUsuario['id']) ?></strong></p>
            
            <p>Ha cursado satisfactoriamente el programa de formación:</p>
            
            <div class="programa">
                <?= htmlspecialchars($datosUsuario['nombre_formacion']) ?>
            </div>
            
            <div class="ficha">
                Ficha de Formación: <strong><?= htmlspecialchars($datosUsuario['id_ficha']) ?></strong>
            </div>
            
            <p>Cumpliendo con todos los requisitos académicos establecidos por la institución.</p>
        </div>
        
        <div class="footer">
            <div class="fecha">
                Expedido el: <?= fechaEspanol(date('Y-m-d')) ?>
            </div>
            
            <div class="firma">
                <div class="firma-item">
                    <div class="firma-linea"></div>
                    <div class="firma-texto">Coordinador Académico</div>
                </div>
                <div class="firma-item">
                    <div class="firma-linea"></div>
                    <div class="firma-texto">Director de Programa</div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-imprimir y cerrar
        window.onload = function() {
            // Cambiar el título del documento
            document.title = 'Certificado_<?= $datosUsuario["nombres"] ?>_<?= $datosUsuario["apellidos"] ?>';
            
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

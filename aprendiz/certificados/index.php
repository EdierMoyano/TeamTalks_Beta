<?php
session_start();
require_once '../clase/config.php';
require_once '../clase/functions.php';

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['documento'])) {
    header('Location: ../../login/login.php');
    exit;
}

// Verificar que sea un aprendiz
if ($_SESSION['rol'] != 4) {
    header('Location: ../../index.php');
    exit;
}

// Obtener datos de sesión del usuario
$datosSesion = obtenerDatosSesion();
if (!$datosSesion) {
    die("Error: No se pudieron obtener los datos del usuario.");
}

$id_usuario_actual = $datosSesion['id'];
$id_ficha_actual = $datosSesion['id_ficha'];

// Obtener datos de la ficha
$ficha = obtenerFicha($id_ficha_actual);
if (!$ficha) {
    die("Error: No se pudo obtener información de la ficha.");
}

// Obtener trimestre actual
$mes_actual = date('n');
$stmt_trimestre = $pdo->prepare("
    SELECT id_trimestre, trimestre 
    FROM trimestre 
    WHERE ? BETWEEN mes_inicio AND mes_fin
    LIMIT 1
");
$stmt_trimestre->execute([$mes_actual]);
$trimestre_actual = $stmt_trimestre->fetch();

// Fallback si no se encuentra
if (!$trimestre_actual) {
    $trimestre_actual = ['id_trimestre' => 3, 'trimestre' => 'Tercer'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Certificados y Boletines - TeamTalks</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap y fuentes -->
    <link rel="stylesheet" href="../../styles/header.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        /* Estilos existentes más nuevos estilos */
        body.sidebar-collapsed .main-content {
            margin-left: 100px;
        }
        
        .main-content {
            padding: 20px;
        }
        
        .certificados-header {
            background: linear-gradient(135deg, #0E4A86 0%, #1a5490 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
        }
        
        .certificados-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .certificado-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border: 1px solid #eee;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .certificado-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .certificado-icon {
            width: 60px;
            height: 60px;
            background-color: #0E4A86;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 20px;
        }
        
        .certificado-card h3 {
            color: #0E4A86;
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .certificado-card p {
            color: #666;
            margin-bottom: 20px;
        }
        
        .certificado-content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .certificado-info {
            flex: 1;
            margin-bottom: 20px;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #555;
            font-size: 0.9rem;
            margin-bottom: 8px;
        }
        
        .info-item i {
            color: #0E4A86;
            width: 16px;
        }
        
        .promedio-display {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .promedio-valor {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .promedio-valor.alto {
            color: #28a745;
        }
        
        .promedio-valor.medio {
            color: #ffc107;
        }
        
        .promedio-valor.bajo {
            color: #dc3545;
        }
        
        .promedio-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .btn-certificado {
            background-color: #0E4A86;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            width: 100%;
            justify-content: center;
            margin-top: auto;
        }
        
        .btn-certificado:hover {
            background-color: #08325a;
            color: white;
            text-decoration: none;
        }
        
        .btn-certificado:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
        }
        
        .breadcrumb-custom {
            background: none;
            padding: 0;
            margin-bottom: 20px;
        }
        
        .breadcrumb-custom .breadcrumb-item a {
            color: #0E4A86;
            text-decoration: none;
        }
        
        .breadcrumb-custom .breadcrumb-item a:hover {
            text-decoration: underline;
        }
        
        /* Modal styles */
        .modal-header {
            background-color: #0E4A86;
            color: white;
        }
        
        .modal-title {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-close {
            filter: invert(1);
        }
        
        .materia-item {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .materia-nombre {
            font-weight: 500;
            color: #333;
        }
        
        .materia-promedio {
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        
        .materia-promedio.alto {
            background-color: #d4edda;
            color: #155724;
        }
        
        .materia-promedio.medio {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .materia-promedio.bajo {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .trimestre-badge {
            background-color: #0E4A86;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            margin-bottom: 15px;
            display: inline-block;
        }
        
        .trimestre-selector {
            margin-bottom: 20px;
        }
        
        .form-select {
            border: 2px solid #0E4A86;
            border-radius: 8px;
        }
        
        .form-select:focus {
            border-color: #0E4A86;
            box-shadow: 0 0 0 0.2rem rgba(14, 74, 134, 0.25);
        }
        
        .alert-warning {
            background-color: #fff3cd;
            border-color: #ffeaa7;
            color: #856404;
        }
        
        .alert-info {
            background-color: #d1ecf1;
            border-color: #bee5eb;
            color: #0c5460;
        }
        
        .debug-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            font-family: monospace;
            font-size: 0.9rem;
        }
        
        /* Alerta para trimestre actual */
        .trimestre-actual-warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }
    </style>
</head>

<body class="sidebar-collapsed">
    <!-- Header -->
    <?php include '../../includes/design/header.php'; ?>
    <!-- Sidebar -->
    <?php include '../../includes/design/sidebar.php'; ?>

    <!-- Contenido principal -->
    <main class="main-content">
        <div class="container-fluid">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-custom">
                    <li class="breadcrumb-item">
                        <a href="../tarjeta_formacion/index.php">
                            <i class="fas fa-home"></i> Inicio
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        Certificados y Boletines
                    </li>
                </ol>
            </nav>

            <!-- Debug Info (temporal) -->
            <div class="debug-info" id="debugInfo" style="display: none;">
                <h6>Debug Information:</h6>
                <div id="debugContent"></div>
                <button class="btn btn-sm btn-secondary mt-2" onclick="document.getElementById('debugInfo').style.display='none'">Ocultar Debug</button>
            </div>

            <!-- Encabezado -->
            <div class="certificados-header">
                <h1 class="h2 mb-3">
                    <i class="fas fa-certificate"></i>
                    Certificados y Boletines
                </h1>
                <p class="mb-0">Consulta y descarga tus certificados de estudio y boletines de calificaciones</p>
                <?php if ($trimestre_actual): ?>
                    <div class="trimestre-badge mt-3">
                        <i class="fas fa-calendar-alt"></i> <?= htmlspecialchars($trimestre_actual['trimestre']) ?> Trimestre (Actual)
                    </div>
                <?php endif; ?>
                
                <!-- Botón de debug temporal -->
                <button class="btn btn-outline-light btn-sm mt-2" onclick="mostrarDebugInfo()">
                    <i class="fas fa-bug"></i> Mostrar Debug Info
                </button>
            </div>

            <!-- Grid de certificados -->
            <div class="certificados-grid">
                <!-- Certificado General -->
                <div class="certificado-card">
                    <div class="certificado-content">
                        <div class="certificado-icon">
                            <i class="fas fa-award"></i>
                        </div>
                        <h3>Certificado de Estudio</h3>
                        <p>Certificado general de tu programa de formación</p>
                        <div class="certificado-info">
                            <div class="info-item">
                                <i class="fas fa-graduation-cap"></i>
                                <span><?php echo htmlspecialchars($ficha['nombre_formacion'] ?? 'Programa de Formación'); ?></span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-calendar"></i>
                                <span>Ficha: <?php echo htmlspecialchars($ficha['id_ficha']); ?></span>
                            </div>
                        </div>
                        <button class="btn-certificado" onclick="descargarCertificado('general')">
                            <i class="fas fa-download"></i>
                            Descargar Certificado
                        </button>
                    </div>
                </div>

                <!-- Boletín General -->
                <div class="certificado-card">
                    <div class="certificado-content">
                        <div class="certificado-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3>Boletín General</h3>
                        <p>Boletín de calificaciones por trimestre</p>
                        
                        <!-- Alerta sobre trimestre actual -->
                        <div class="trimestre-actual-warning">
                            <i class="fas fa-info-circle"></i>
                            <strong>Nota:</strong> Solo puedes consultar boletines de trimestres completados. 
                            El trimestre actual (<?= htmlspecialchars($trimestre_actual['trimestre']) ?>) no está disponible.
                        </div>
                        
                        <!-- Selector de trimestre -->
                        <div class="trimestre-selector">
                            <label for="trimestreBoletinSelect" class="form-label">Seleccionar Trimestre:</label>
                            <select class="form-select" id="trimestreBoletinSelect">
                                <option value="">Cargando trimestres...</option>
                            </select>
                        </div>
                        
                        <div class="promedio-display" id="promedioGeneral" style="display: none;">
                            <div class="promedio-valor">-</div>
                            <div class="promedio-label">Promedio General</div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button class="btn-certificado" onclick="verBoletinGeneral()" id="btnVerBoletin" disabled>
                                <i class="fas fa-eye"></i>
                                Ver Boletín Completo
                            </button>
                            <button class="btn-certificado" onclick="descargarBoletin()" id="btnDescargarBoletin" disabled>
                                <i class="fas fa-download"></i>
                                Descargar PDF
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Materias Individuales -->
                <div class="certificado-card">
                    <div class="certificado-content">
                        <div class="certificado-icon">
                            <i class="fas fa-book"></i>
                        </div>
                        <h3>Calificaciones por Materia</h3>
                        <p>Consulta el detalle de calificaciones por materia</p>
                        
                        <!-- Alerta sobre trimestre actual -->
                        <div class="trimestre-actual-warning">
                            <i class="fas fa-info-circle"></i>
                            <strong>Nota:</strong> Solo puedes consultar calificaciones de trimestres completados.
                        </div>
                        
                        <!-- Selector de trimestre -->
                        <div class="trimestre-selector">
                            <label for="trimestreMateriaSelect" class="form-label">Seleccionar Trimestre:</label>
                            <select class="form-select" id="trimestreMateriaSelect">
                                <option value="">Cargando trimestres...</option>
                            </select>
                        </div>
                        
                        <div class="certificado-info">
                            <div class="materias-preview" id="materiasPreview">
                                <div class="info-item">
                                    <i class="fas fa-info-circle"></i>
                                    <span>Selecciona un trimestre</span>
                                </div>
                            </div>
                        </div>
                        <button class="btn-certificado" onclick="verDetalleMateria()" id="btnVerMaterias" disabled>
                            <i class="fas fa-list"></i>
                            Ver Todas las Materias
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal para Boletín General -->
    <div class="modal fade" id="modalBoletinGeneral" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-chart-line"></i>
                        Boletín General de Calificaciones
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="contenidoBoletinGeneral">
                    <!-- Se cargará dinámicamente -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Detalle de Materias -->
    <div class="modal fade" id="modalDetalleMaterias" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-book"></i>
                        Calificaciones por Materia
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="contenidoDetalleMaterias">
                    <!-- Se cargará dinámicamente -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/script.js"></script>
    <script>
        const idUsuario = <?php echo $id_usuario_actual; ?>;
        const idFicha = <?php echo $id_ficha_actual; ?>;
        const trimestreActual = <?php echo $trimestre_actual['id_trimestre']; ?>;
        let trimestresDisponibles = [];
        let trimestreSeleccionadoBoletin = null;
        let trimestreSeleccionadoMateria = null;

        // Cargar trimestres al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            cargarTrimestresDisponibles();
        });

        function mostrarDebugInfo() {
            fetch('debug_trimestres.php', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                const debugDiv = document.getElementById('debugInfo');
                const debugContent = document.getElementById('debugContent');
                
                if (data.success) {
                    debugContent.innerHTML = `
                        <strong>Usuario ID:</strong> ${data.debug_info.id_usuario}<br>
                        <strong>Ficha ID:</strong> ${data.debug_info.id_ficha}<br>
                        <strong>Mes Actual:</strong> ${data.debug_info.mes_actual}<br>
                        <strong>Trimestre Actual:</strong> ${trimestreActual}<br><br>
                        
                        <strong>Todos los trimestres en BD:</strong><br>
                        ${data.debug_info.todos_trimestres_bd.map(t => 
                            `- ${t.trimestre} (ID: ${t.id_trimestre}, Meses: ${t.mes_inicio}-${t.mes_fin})`
                        ).join('<br>')}<br><br>
                        
                        <strong>Materias por trimestre:</strong><br>
                        ${data.debug_info.materias_por_trimestre.map(m => 
                            `- ${m.trimestre}: ${m.total_materias} materias`
                        ).join('<br>')}<br><br>
                        
                        <strong>Actividades por trimestre:</strong><br>
                        ${data.debug_info.actividades_por_trimestre.map(a => 
                            `- ${a.trimestre}: ${a.total_actividades} actividades, ${a.actividades_calificadas} calificadas`
                        ).join('<br>')}
                    `;
                } else {
                    debugContent.innerHTML = `Error: ${data.message}`;
                }
                
                debugDiv.style.display = 'block';
            })
            .catch(error => {
                console.error('Error en debug:', error);
            });
        }

        function cargarTrimestresDisponibles() {
            fetch('obtener_trimestres_cursados.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id_usuario=${idUsuario}&id_ficha=${idFicha}`
            })
            .then(response => response.json())
            .then(data => {
                console.log('Respuesta de trimestres:', data); // Debug
                
                if (data.success) {
                    trimestresDisponibles = data.trimestres;
                    llenarSelectoresTrimestre();
                    
                    // Mostrar debug info en consola
                    if (data.debug) {
                        console.log('Debug info:', data.debug);
                    }
                } else {
                    console.error('Error al cargar trimestres:', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        function llenarSelectoresTrimestre() {
            const selectBoletin = document.getElementById('trimestreBoletinSelect');
            const selectMateria = document.getElementById('trimestreMateriaSelect');
            
            console.log('Llenando selectores con:', trimestresDisponibles); // Debug
            
            // Limpiar opciones
            selectBoletin.innerHTML = '<option value="">Seleccionar trimestre...</option>';
            selectMateria.innerHTML = '<option value="">Seleccionar trimestre...</option>';
            
            if (trimestresDisponibles.length === 0) {
                selectBoletin.innerHTML = '<option value="">No hay trimestres completados disponibles</option>';
                selectMateria.innerHTML = '<option value="">No hay trimestres completados disponibles</option>';
                return;
            }
            
            trimestresDisponibles.forEach(trimestre => {
                const optionBoletin = document.createElement('option');
                optionBoletin.value = trimestre.id_trimestre;
                optionBoletin.textContent = trimestre.nombre;
                selectBoletin.appendChild(optionBoletin);
                
                const optionMateria = document.createElement('option');
                optionMateria.value = trimestre.id_trimestre;
                optionMateria.textContent = trimestre.nombre;
                selectMateria.appendChild(optionMateria);
            });
            
            // Event listeners
            selectBoletin.addEventListener('change', function() {
                trimestreSeleccionadoBoletin = this.value;
                if (this.value) {
                    cargarPromedioTrimestre(this.value);
                    const trimestre = trimestresDisponibles.find(t => t.id_trimestre == this.value);
                    document.getElementById('btnVerBoletin').disabled = false;
                    document.getElementById('btnDescargarBoletin').disabled = !trimestre.puede_descargar;
                } else {
                    document.getElementById('promedioGeneral').style.display = 'none';
                    document.getElementById('btnVerBoletin').disabled = true;
                    document.getElementById('btnDescargarBoletin').disabled = true;
                }
            });
            
            selectMateria.addEventListener('change', function() {
                trimestreSeleccionadoMateria = this.value;
                if (this.value) {
                    cargarPreviewMateriasTrimestre(this.value);
                    document.getElementById('btnVerMaterias').disabled = false;
                } else {
                    document.getElementById('materiasPreview').innerHTML = '<div class="info-item"><i class="fas fa-info-circle"></i><span>Selecciona un trimestre</span></div>';
                    document.getElementById('btnVerMaterias').disabled = true;
                }
            });
        }

        function cargarPromedioTrimestre(idTrimestre) {
            fetch('obtener_boletin_por_trimestre.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id_usuario=${idUsuario}&id_ficha=${idFicha}&id_trimestre=${idTrimestre}`
            })
            .then(response => response.json())
            .then(data => {
                console.log('Respuesta del boletín:', data); // Debug
                
                const promedioElement = document.getElementById('promedioGeneral');
                if (data.success && data.materias.length > 0) {
                    // Calcular promedio general
                    let sumaPromedios = 0;
                    let materiasConNota = 0;
                    
                    data.materias.forEach(materia => {
                        if (materia.promedio_final) {
                            sumaPromedios += parseFloat(materia.promedio_final);
                            materiasConNota++;
                        }
                    });
                    
                    if (materiasConNota > 0) {
                        const promedio = sumaPromedios / materiasConNota;
                        let claseColor = 'bajo';
                        if (promedio >= 4.0) claseColor = 'alto';
                        else if (promedio >= 3.0) claseColor = 'medio';

                        promedioElement.innerHTML = `
                            <div class="promedio-valor ${claseColor}">${promedio.toFixed(2)}</div>
                            <div class="promedio-label">${data.trimestre_nombre}</div>
                        `;
                        promedioElement.style.display = 'block';
                    } else {
                        promedioElement.innerHTML = `
                            <div class="promedio-valor">N/A</div>
                            <div class="promedio-label">Sin calificaciones</div>
                        `;
                        promedioElement.style.display = 'block';
                    }
                } else {
                    promedioElement.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        function cargarPreviewMateriasTrimestre(idTrimestre) {
            fetch('obtener_materias_por_trimestre.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id_usuario=${idUsuario}&id_ficha=${idFicha}&id_trimestre=${idTrimestre}`
            })
            .then(response => response.json())
            .then(data => {
                const previewElement = document.getElementById('materiasPreview');
                if (data.success && data.materias.length > 0) {
                    const preview = data.materias.slice(0, 3).map(materia => {
                        return `
                            <div class="info-item">
                                <i class="fas fa-book"></i>
                                <span>${materia.nombre}: ${materia.promedio_final ? materia.promedio_final.toFixed(2) : 'N/A'}</span>
                            </div>
                        `;
                    }).join('');
                    
                    previewElement.innerHTML = preview;
                } else {
                    previewElement.innerHTML = '<div class="info-item"><i class="fas fa-info-circle"></i><span>No hay materias calificadas</span></div>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        function descargarCertificado(tipo) {
            const btn = event.target;
            const originalText = btn.innerHTML;

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando...';

            // Abrir en nueva ventana para descarga automática
            window.open(`generar_certificado.php?tipo=${tipo}`, '_blank');
            
            setTimeout(() => {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }, 2000);
        }

        function verBoletinGeneral() {
            if (!trimestreSeleccionadoBoletin) {
                alert('Por favor selecciona un trimestre');
                return;
            }

            const modal = new bootstrap.Modal(document.getElementById('modalBoletinGeneral'));
            const contenido = document.getElementById('contenidoBoletinGeneral');

            contenido.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Cargando boletín...</div>';
            modal.show();

            fetch('obtener_boletin_por_trimestre.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id_usuario=${idUsuario}&id_ficha=${idFicha}&id_trimestre=${trimestreSeleccionadoBoletin}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    contenido.innerHTML = generarHTMLBoletinGeneral(data);
                } else {
                    contenido.innerHTML = `<div class="alert alert-danger">Error: ${data.message}</div>`;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                contenido.innerHTML = '<div class="alert alert-danger">Error al cargar el boletín</div>';
            });
        }

        function verDetalleMateria() {
            if (!trimestreSeleccionadoMateria) {
                alert('Por favor selecciona un trimestre');
                return;
            }

            const modal = new bootstrap.Modal(document.getElementById('modalDetalleMaterias'));
            const contenido = document.getElementById('contenidoDetalleMaterias');

            contenido.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Cargando materias...</div>';
            modal.show();

            fetch('obtener_materias_por_trimestre.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id_usuario=${idUsuario}&id_ficha=${idFicha}&id_trimestre=${trimestreSeleccionadoMateria}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    contenido.innerHTML = generarHTMLMaterias(data.materias, data.trimestre_nombre);
                } else {
                    contenido.innerHTML = `<div class="alert alert-danger">Error: ${data.message}</div>`;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                contenido.innerHTML = '<div class="alert alert-danger">Error al cargar las materias</div>';
            });
        }

        function generarHTMLBoletinGeneral(data) {
            const trimestre = trimestresDisponibles.find(t => t.id_trimestre == trimestreSeleccionadoBoletin);
            let alertaDescarga = '';
            
            if (!trimestre.puede_descargar) {
                if (!trimestre.completado) {
                    alertaDescarga = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Este trimestre aún está en curso. No se puede descargar el boletín hasta que termine.</div>';
                } else if (!trimestre.todas_calificadas) {
                    alertaDescarga = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> No todas las materias están calificadas. Complete todas las calificaciones para descargar el boletín.</div>';
                }
            }
            
            return `
                ${alertaDescarga}
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="text-center p-3 bg-light rounded">
                            <div class="h3 mb-1">${data.total_materias}</div>
                            <small class="text-muted">Materias Cursadas</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center p-3 bg-light rounded">
                            <div class="h3 mb-1">${data.materias_con_calificacion}</div>
                            <small class="text-muted">Materias Calificadas</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center p-3 bg-light rounded">
                            <div class="h3 mb-1">${data.total_actividades}</div>
                            <small class="text-muted">Actividades Completadas</small>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Mostrando calificaciones del <strong>${data.trimestre_nombre}</strong>
                </div>
                
                <h5 class="mb-3">Promedio Final por Materia</h5>
                <div class="row">
                    ${data.materias.map(materia => {
                        let claseMateria = 'bajo';
                        if (materia.promedio_final >= 4.0) claseMateria = 'alto';
                        else if (materia.promedio_final >= 3.0) claseMateria = 'medio';
                        
                        return `
                            <div class="col-md-6 mb-3">
                                <div class="materia-item">
                                    <span class="materia-nombre">${materia.nombre}</span>
                                    <span class="materia-promedio ${claseMateria}">
                                        ${materia.promedio_final ? materia.promedio_final.toFixed(2) : 'Sin calificar'}
                                    </span>
                                </div>
                            </div>
                        `;
                    }).join('')}
                </div>
            `;
        }

        function generarHTMLMaterias(materias, trimestre) {
            return `
                <div class="alert alert-info mb-4">
                    <i class="fas fa-info-circle"></i> Mostrando calificaciones del <strong>${trimestre}</strong>
                </div>
                <div class="row">
                    ${materias.map(materia => {
                        let claseMateria = 'bajo';
                        if (materia.promedio_final >= 4.0) claseMateria = 'alto';
                        else if (materia.promedio_final >= 3.0) claseMateria = 'medio';
                        
                        return `
                            <div class="col-12 mb-4">
                                <div class="card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">${materia.nombre}</h6>
                                        <span class="materia-promedio ${claseMateria}">
                                            Promedio Final: ${materia.promedio_final ? materia.promedio_final.toFixed(2) : 'Sin calificar'}
                                        </span>
                                    </div>
                                    <div class="card-body">
                                        <div class="text-center p-3">
                                            <div class="h4 mb-2 text-${claseMateria === 'alto' ? 'success' : claseMateria === 'medio' ? 'warning' : 'danger'}">
                                                ${materia.promedio_final ? materia.promedio_final.toFixed(2) : 'N/A'}
                                            </div>
                                            <p class="text-muted mb-0">
                                                Basado en ${materia.total_actividades_calificadas} actividades calificadas
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    }).join('')}
                </div>
            `;
        }

        function descargarBoletin() {
            if (!trimestreSeleccionadoBoletin) {
                alert('Por favor selecciona un trimestre');
                return;
            }
            
            const trimestre = trimestresDisponibles.find(t => t.id_trimestre == trimestreSeleccionadoBoletin);
            if (!trimestre.puede_descargar) {
                if (!trimestre.completado) {
                    alert('No se puede descargar el boletín de un trimestre que aún está en curso.');
                } else if (!trimestre.todas_calificadas) {
                    alert('No se puede descargar el boletín hasta que todas las materias estén calificadas.');
                }
                return;
            }

            const btn = event.target;
            const originalText = btn.innerHTML;

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando PDF...';

            // Abrir en nueva ventana para descarga automática
            window.open(`generar_boletin_pdf.php?trimestre=${trimestreSeleccionadoBoletin}`, '_blank');
            
            setTimeout(() => {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }, 2000);
        }
    </script>
</body>
</html>

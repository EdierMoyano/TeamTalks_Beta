<?php
session_start();

// Verificar si el usuario está logueado y es super admin
if (!isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1) {
    header('Location: index.php');
    exit;
}

// Incluir archivo de conexión
require_once '../conexion/conexion.php';

// Incluir la librería de códigos de barras (asegúrate de que el path sea correcto)
require_once '../vendor/autoload.php';
use Picqer\Barcode\BarcodeGeneratorPNG;

// Inicializar variables
$mensaje = '';
$tipo_mensaje = '';
$id = '';
$nombres = '';
$apellidos = '';
$correo = '';
$nit = isset($_GET['empresa']) ? (int)$_GET['empresa'] : '';
$rol = '';

// Función para generar y guardar imagen de código de barras basado en el documento de identidad
function generarGuardarCodigoBarras($documento) {
    // Crear directorio si no existe
    $directorio = '../barcode/';
    if (!file_exists($directorio)) {
        mkdir($directorio, 0755, true);
    }
    
    // Crear generador de código de barras
    $generator = new BarcodeGeneratorPNG();
    
    // Generar código de barras en formato PNG
    $barcode = $generator->getBarcode($documento, $generator::TYPE_CODE_128, 2, 50);
    
    // Nombre del archivo (usando el documento como nombre)
    $nombreArchivo =  $documento . '.png';
    $rutaCompleta = $directorio . $nombreArchivo;
    
    // Guardar la imagen en el servidor
    file_put_contents($rutaCompleta, $barcode);
    
    // Devolver la ruta relativa para guardar en la base de datos
    return 'barcode/' . $nombreArchivo;
}

// Obtener empresas y roles
try {
    $db = new Database();
    $conn = $db->connect();
    
    // Obtener empresas
    $stmt = $conn->query("SELECT nit, empresa FROM empresa ORDER BY empresa");
    $empresa = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener roles
    $stmt = $conn->query("SELECT id_rol,rol FROM roles ORDER BY id_rol");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $mensaje = "Error al obtener datos: " . $e->getMessage();
    $tipo_mensaje = "danger";
}

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = trim($_POST['id']);
    $nombres = trim($_POST['nombres']);
    $apellidos = trim($_POST['apellidos']);
    $correo = trim($_POST['correo']);
    $contraseña = trim($_POST['contraseña']);
    $confirmar_contraseña = trim($_POST['confirmar_contraseña']);
    $nit = !empty($_POST['nit']) ? (int)$_POST['nit'] : null;
    $rol = (int)$_POST['rol'];
    
    // Validaciones
    $errores = [];
    
    if (empty($id)) {
        $errores[] = "El documento de identidad es obligatorio.";
    }
    
    if (empty($nombres)) {
        $errores[] = "Los nombres son obligatorios.";
    }
    
    if (empty($apellidos)) {
        $errores[] = "Los apellidos son obligatorios.";
    }
    
    if (empty($correo)) {
        $errores[] = "El correo es obligatorio.";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El formato del correo no es válido.";
    }
    
    if (empty($contraseña)) {
        $errores[] = "La contraseña es obligatoria.";
    } elseif (strlen($contraseña) < 6) {
        $errores[] = "La contraseña debe tener al menos 6 caracteres.";
    }
    
    if ($contraseña !== $confirmar_contraseña) {
        $errores[] = "Las contraseñas no coinciden.";
    }
    
    if (empty($rol)) {
        $errores[] = "El rol es obligatorio.";
    }
    
    // Verificar si el usuario ya existe
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            $errores[] = "Ya existe un usuario con ese documento de identidad.";
        }
        
        $stmt = $conn->prepare("SELECT COUNT(*) FROM usuarios WHERE correo = ?");
        $stmt->execute([$correo]);
        if ($stmt->fetchColumn() > 0) {
            $errores[] = "Ya existe un usuario con ese correo.";
        }
    } catch (PDOException $e) {
        $errores[] = "Error al verificar usuario: " . $e->getMessage();
    }
    
    if (empty($errores)) {
        try {
            $ruta_codigo_barras = generarGuardarCodigoBarras($id);
            
            $contraseña_hash = password_hash($contraseña, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("INSERT INTO usuarios (id, nombres, apellidos,  correo, contraseña, nit, id_rol ) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$id, $nombres, $apellidos, $correo, $contraseña_hash, $nit, $id_rol]);
            
            $mensaje = "Usuario creado correctamente.";
            $tipo_mensaje = "success";
            
            header("Location: usuarios_ver.php?id=$id&mensaje=creado");
            exit;
        } catch (PDOException $e) {
            $mensaje = "Error al crear el usuario: " . $e->getMessage();
            $tipo_mensaje = "danger";
        }
    } else {
        $mensaje = implode("<br>", $errores);
        $tipo_mensaje = "danger";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Usuario - Super Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .sidebar {
            min-height: calc(100vh - 56px);
        }
        .barcode-preview {
            margin-top: 10px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #f8f9fa;
            text-align: center;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-danger">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-shield-lock"></i> Panel Super Admin
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="empresas.php">Empresas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="licencias.php">Licencias</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="usuarios.php">Usuarios</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo $_SESSION['nombres']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php">Cerrar Sesión</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="empresas.php">
                                <i class="bi bi-building"></i> Empresas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="licencias.php">
                                <i class="bi bi-key"></i> Licencias
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="usuarios.php">
                                <i class="bi bi-people"></i> Usuarios
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="tipos_licencia.php">
                                <i class="bi bi-tags"></i> Tipos de Licencia
                            </a>
                        </li>
                        
                    </ul>
                </div>
            </div>
            
            <!-- Contenido principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Crear Nuevo Usuario</h1>
                </div>
                
                <?php if (!empty($mensaje)): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                    <?php echo $mensaje; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-person-plus-fill me-1"></i> Formulario de Registro
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="id" class="form-label">Documento de Identidad *</label>
                                    <input type="text" class="form-control" id="id" name="id" value="<?php echo htmlspecialchars($id); ?>" required onchange="mostrarVistaPrevia()">
                                    <div class="form-text">Ingrese el número de documento sin puntos ni guiones.</div>
                                    
                                    <!-- Vista previa del código de barras -->
                                    <div id="barcodePreview" class="barcode-preview d-none">
                                        <p class="mb-1">Vista previa del código de barras:</p>
                                        <div id="barcodeImage"></div>
                                        <small class="text-muted">Esta imagen se guardará en la carpeta 'barcode' al registrar el usuario.</small>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="Rol" class="form-label">Rol</label>
                                    <select class="form-select" id="rol" name="rol" required>
                                        <option value="">Seleccione un rol</option>
                                        <?php foreach ($roles as $r): ?>
                                        <option value="<?php echo $r['rol']; ?>" <?php echo ($rol == $r['rol']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($r['rol']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="nombres" class="form-label">Nombres *</label>
                                    <input type="text" class="form-control" id="nombres" name="nombres" value="<?php echo htmlspecialchars($nombres); ?>" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="apellidos" class="form-label">Apellidos *</label>
                                    <input type="text" class="form-control" id="apellidos" name="apellidos" value="<?php echo htmlspecialchars($apellidos); ?>" required>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="correo" class="form-label">Correo *</label>
                                    <input type="correo" class="form-control" id="correo" name="correo" value="<?php echo htmlspecialchars($correo); ?>" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="nit" class="form-label">Empresa</label>
                                    <select class="form-select" id="nit" name="nit">
                                        <option value="">Sin empresa</option>
                                        <?php foreach ($empresa as $empresa): ?>
                                        <option value="<?php echo $empresa['nit']; ?>" <?php echo ($nit == $empresa['nit']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($empresa['empresa']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="contraseña" class="form-label">Contraseña *</label>
                                    <input type="password" class="form-control" id="contraseña" name="contraseña" required>
                                    <div class="form-text">Mínimo 6 caracteres.</div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="confirmar_contraseña" class="form-label">Confirmar Contraseña *</label>
                                    <input type="password" class="form-control" id="confirmar_contraseña" name="confirmar_contraseña" required>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="usuarios.php" class="btn btn-secondary me-md-2">Cancelar</a>
                                <button type="submit" class="btn btn-primary">Guardar Usuario</button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <script>
        function mostrarVistaPrevia() {
            const documento = document.getElementById('id').value;
            if (documento) {
                // Mostrar el contenedor de vista previa
                const previewDiv = document.getElementById('barcodePreview');
                previewDiv.classList.remove('d-none');
                
                // Crear un elemento SVG para el código de barras
                const barcodeContainer = document.getElementById('barcodeImage');
                barcodeContainer.innerHTML = '<svg id="barcode"></svg>';
                
                // Generar el código de barras usando JsBarcode
                JsBarcode("#barcode", documento, {
                    format: "CODE128",
                    lineColor: "#000",
                    width: 2,
                    height: 50,
                    displayValue: true
                });
            }
        }
        
        // Ejecutar al cargar la página si ya hay un valor
        document.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById('id').value) {
                mostrarVistaPrevia();
            }
        });
    </script>
</body>
</html>
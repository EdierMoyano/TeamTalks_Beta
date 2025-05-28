<?php
session_start();

// Redirigir si ya está logueado como super admin
if (isset($_SESSION['id_rol']) && $_SESSION['id_rol'] == 1) {
    header('Location: dashboard.php');
    exit;
}

// Incluir archivo de conexión
require_once '../conexion/conexion.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $correo = trim($_POST['correo']);
    $password = trim($_POST['password']);
    
    if (empty($correo) || empty($password)) {
        $error = 'Por favor, complete todos los campos.';
    } else {
        try {
            // Crear instancia de la base de datos
            $db = new Database();
            $conn = $db->connect();
            
            // Consulta CORREGIDA - Usando parámetro nombrado correctamente
            $query = "SELECT u.id, u.nombres, u.apellidos, u.correo, u.contraseña, r.id_rol, r.rol
                      FROM usuarios u
                      JOIN roles r ON u.id_rol = r.id_rol
                      WHERE u.correo = :correo AND r.id_rol = 1";

            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':correo', $correo, PDO::PARAM_STR);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Verificar contraseña
                if (password_verify($password, $user['contraseña'])) {
                    // Iniciar sesión
                    $_SESSION['id'] = $user['id'];
                    $_SESSION['nombres'] = $user['nombres'];
                    $_SESSION['apellidos'] = $user['apellidos'];
                    $_SESSION['nombre_completo'] = $user['nombres'] . ' ' . $user['apellidos'];
                    $_SESSION['correo'] = $user['correo'];
                    $_SESSION['id_rol'] = $user['id_rol'];
                    $_SESSION['rol'] = $user['rol'];
                    
                    // Redirigir al dashboard
                    header('Location: dashboard.php');
                    exit;
                } else {
                    $error = 'Contraseña incorrecta.';
                }
            } else {
                $error = 'Usuario no encontrado o no tiene permisos de Super Administrador.';
            }
        } catch (PDOException $e) {
            $error = 'Error en el sistema. Por favor, intente más tarde.';
            // Para desarrollo puedes mostrar el error real:
            // $error = 'Error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Super Administrador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .login-container {
            margin-top: 5rem;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-light">
    <div class="container login-container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card">
                    <div class="card-header bg-danger text-white text-center">
                        <h3>Super Administrador</h3>
                    </div>
                    <div class="card-body p-4">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        
                        <form method="post" action="">
                            <div class="mb-3">
                                <label for="correo" class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-control" id="correo" name="correo" required autofocus>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-danger btn-lg">Iniciar Sesión</button>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer text-center">
                        <a href="../index.php" class="text-decoration-none">Volver al inicio</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
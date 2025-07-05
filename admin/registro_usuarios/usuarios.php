<?php
    session_start();


    if ($_SESSION['rol'] !== 2) {
        header('Location: ../../includes/exit.php?');
        exit;
    }

    require_once '../../conexion/conexion.php';

    // Verificar si el usuario está autenticado
    if (!isset($_SESSION['documento'])) {
        header('Location: ../login/login.php');
        exit;
    }

    // Inicializar mensaje de alerta
    $alertMessage = '';
    $alertType = '';
    $modalMessage = '';
    $modalType = '';

    // Crear instancia de la conexión
    $db = new Database();
    $conexion = $db->connect();

    // Obtener NIT de la empresa del usuario logueado
    $nitEmpresa = 0;
    try {
        $stmt = $conexion->prepare("SELECT nit FROM usuarios WHERE id = :id");
        $stmt->bindParam(':id', $_SESSION['documento'], PDO::PARAM_INT);
        $stmt->execute();
        $usuarioActual = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($usuarioActual) {
            $nitEmpresa = $usuarioActual['nit'];
        }
    } catch (PDOException $e) {
        $alertMessage = "Error al obtener información del usuario: " . $e->getMessage();
        $alertType = "danger";
    }

    // Procesar formulario de registro manual
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'manual') {
        $id = $_POST['id'];
        $nombres = $_POST['nombres'];
        $apellidos = $_POST['apellidos'];
        $correo = $_POST['correo'];
        $password = $_POST['password'];
        $telefono = $_POST['telefono'];
        $id_rol = $_POST['id_rol'];
        $id_tipo = $_POST['id_tipo'];
        $id_estado = 1; // Activo por defecto
        $id_ficha = !empty($_POST['id_ficha']) ? $_POST['id_ficha'] : null;
        $id_materia = !empty($_POST['id_materia']) ? $_POST['id_materia'] : null;
        $fecha_registro = date('Y-m-d');

        // Validar que el rol sea Instructor, Aprendiz o Inst_Transversal
        $rolesPermitidos = ['3', '4', '5'];
        if (!in_array($id_rol, $rolesPermitidos)) {
            $alertMessage = "Solo se permite registrar usuarios con rol Instructor, Aprendiz o Instructor Transversal";
            $alertType = "danger";
        } else {
            // Validar datos básicos
            if (empty($id) || empty($nombres) || empty($correo) || empty($password)) {
                $alertMessage = "Los campos ID, Nombres, Correo y Contraseña son obligatorios";
                $alertType = "danger";
            } else {
                // Validaciones específicas por rol
                $requiereFicha = ($id_rol == 4); // Solo aprendices requieren ficha
                $requiereMateria = ($id_rol == 5); // Instructor transversal requiere materia
                $esInstructorNormal = ($id_rol == 3); // Instructor normal
                
                if ($requiereFicha && empty($id_ficha)) {
                    $alertMessage = "Para aprendices, la ficha es obligatoria";
                    $alertType = "danger";
                } elseif ($requiereMateria && empty($id_materia)) {
                    $alertMessage = "Para instructores transversales, la materia es obligatoria";
                    $alertType = "danger";
                } else {
                    // Obtener ID de la materia "Formacion Tecnica" para instructor normal
                    $id_materia_formacion_tecnica = null;
                    if ($esInstructorNormal) {
                        $stmt = $conexion->prepare("SELECT id_materia FROM materias WHERE materia = 'Formacion Tecnica'");
                        $stmt->execute();
                        $materia_ft = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($materia_ft) {
                            $id_materia_formacion_tecnica = $materia_ft['id_materia'];
                        }
                    }
                    
                    try {
                        // Verificar si la ficha existe y está activa (solo para aprendices)
                        if ($requiereFicha && !empty($id_ficha)) {
                            $stmt = $conexion->prepare("
                                SELECT f.id_ficha, f.id_estado as ficha_estado, fo.id_estado as formacion_estado 
                                FROM fichas f
                                JOIN formacion fo ON f.id_formacion = fo.id_formacion
                                WHERE f.id_ficha = :id_ficha
                            ");
                            $stmt->bindParam(':id_ficha', $id_ficha, PDO::PARAM_INT);
                            $stmt->execute();
                            $ficha = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            if (!$ficha) {
                                $alertMessage = "La ficha especificada no existe";
                                $alertType = "danger";
                            } elseif ($ficha['ficha_estado'] != 1) {
                                $alertMessage = "La ficha especificada está inactiva";
                                $alertType = "danger";
                            } elseif ($ficha['formacion_estado'] != 1) {
                                $alertMessage = "La formación asociada a esta ficha está inactiva";
                                $alertType = "danger";
                            }
                        }
                        
                        // Verificar si la materia existe (para instructor transversal)
                        if ($requiereMateria && !empty($id_materia)) {
                            $stmt = $conexion->prepare("SELECT id_materia FROM materias WHERE id_materia = :id_materia");
                            $stmt->bindParam(':id_materia', $id_materia, PDO::PARAM_INT);
                            $stmt->execute();
                            $materia = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            if (!$materia) {
                                $alertMessage = "La materia especificada no existe";
                                $alertType = "danger";
                            }
                        }
                        
                        // Si todo está bien, proceder con el registro o actualización
                        if ($alertType != "danger") {
                            // Hash de la contraseña para seguridad
                            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                            
                            // Verificar si el usuario ya existe
                            $stmt = $conexion->prepare("SELECT id FROM usuarios WHERE id = :id");
                            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                            $stmt->execute();
                            $usuarioExistente = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            // Iniciar transacción
                            $conexion->beginTransaction();
                            
                            if ($usuarioExistente) {
                                // Actualizar usuario existente
                                $stmt = $conexion->prepare("
                                    UPDATE usuarios SET 
                                    nombres = :nombres, 
                                    apellidos = :apellidos, 
                                    correo = :correo, 
                                    contraseña = :password, 
                                    telefono = :telefono, 
                                    id_rol = :id_rol, 
                                    id_tipo = :id_tipo, 
                                    id_estado = :id_estado, 
                                    fecha_registro = :fecha_registro, 
                                    nit = :nit 
                                    WHERE id = :id
                                ");
                                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                                $stmt->bindParam(':nombres', $nombres, PDO::PARAM_STR);
                                $stmt->bindParam(':apellidos', $apellidos, PDO::PARAM_STR);
                                $stmt->bindParam(':correo', $correo, PDO::PARAM_STR);
                                $stmt->bindParam(':password', $passwordHash, PDO::PARAM_STR);
                                $stmt->bindParam(':telefono', $telefono, PDO::PARAM_STR);
                                $stmt->bindParam(':id_rol', $id_rol, PDO::PARAM_INT);
                                $stmt->bindParam(':id_tipo', $id_tipo, PDO::PARAM_INT);
                                $stmt->bindParam(':id_estado', $id_estado, PDO::PARAM_INT);
                                $stmt->bindParam(':fecha_registro', $fecha_registro, PDO::PARAM_STR);
                                $stmt->bindParam(':nit', $nitEmpresa, PDO::PARAM_INT);
                                
                                if ($stmt->execute()) {
                                    $alertMessage = "Usuario actualizado correctamente";
                                    $alertType = "success";
                                    
                                    // Si es aprendiz y tiene ficha, actualizar o crear relación en user_ficha
                                    if ($requiereFicha && !empty($id_ficha)) {
                                        // Verificar si ya existe una relación
                                        $stmt = $conexion->prepare("
                                            SELECT id_user_ficha FROM user_ficha 
                                            WHERE id_user = :id_user AND id_ficha = :id_ficha
                                        ");
                                        $stmt->bindParam(':id_user', $id, PDO::PARAM_INT);
                                        $stmt->bindParam(':id_ficha', $id_ficha, PDO::PARAM_INT);
                                        $stmt->execute();
                                        $relacionExistente = $stmt->fetch(PDO::FETCH_ASSOC);
                                        
                                        if ($relacionExistente) {
                                            // Actualizar relación existente
                                            $stmt = $conexion->prepare("
                                                UPDATE user_ficha SET 
                                                id_estado = 1, 
                                                fecha_asig = CURRENT_DATE 
                                                WHERE id_user = :id_user AND id_ficha = :id_ficha
                                            ");
                                            $stmt->bindParam(':id_user', $id, PDO::PARAM_INT);
                                            $stmt->bindParam(':id_ficha', $id_ficha, PDO::PARAM_INT);
                                            $stmt->execute();
                                        } else {
                                            // Crear nueva relación
                                            $stmt = $conexion->prepare("
                                                INSERT INTO user_ficha (id_user, id_ficha, fecha_asig, id_estado) 
                                                VALUES (:id_user, :id_ficha, CURRENT_DATE, 1)
                                            ");
                                            $stmt->bindParam(':id_user', $id, PDO::PARAM_INT);
                                            $stmt->bindParam(':id_ficha', $id_ficha, PDO::PARAM_INT);
                                            $stmt->execute();
                                        }
                                    }
                                    
                                    // Si es instructor normal, asignar automáticamente Formacion Tecnica
                                    if ($esInstructorNormal && !empty($id_materia_formacion_tecnica)) {
                                        // Verificar si ya existe una relación
                                        $stmt = $conexion->prepare("
                                            SELECT id_detalles_instructor FROM materia_instructor 
                                            WHERE id_instructor = :id_instructor AND id_materia = :id_materia
                                        ");
                                        $stmt->bindParam(':id_instructor', $id, PDO::PARAM_INT);
                                        $stmt->bindParam(':id_materia', $id_materia_formacion_tecnica, PDO::PARAM_INT);
                                        $stmt->execute();
                                        $relacionExistente = $stmt->fetch(PDO::FETCH_ASSOC);
                                        
                                        if (!$relacionExistente) {
                                            // Crear nueva relación
                                            $stmt = $conexion->prepare("
                                                INSERT INTO materia_instructor (id_instructor, id_materia) 
                                                VALUES (:id_instructor, :id_materia)
                                            ");
                                            $stmt->bindParam(':id_instructor', $id, PDO::PARAM_INT);
                                            $stmt->bindParam(':id_materia', $id_materia_formacion_tecnica, PDO::PARAM_INT);
                                            $stmt->execute();
                                        }
                                    }
                                    
                                    // Si es instructor transversal y tiene materia, actualizar o crear relación
                                    if ($requiereMateria && !empty($id_materia)) {
                                        // Verificar si ya existe una relación
                                        $stmt = $conexion->prepare("
                                            SELECT id_detalles_instructor FROM materia_instructor 
                                            WHERE id_instructor = :id_instructor AND id_materia = :id_materia
                                        ");
                                        $stmt->bindParam(':id_instructor', $id, PDO::PARAM_INT);
                                        $stmt->bindParam(':id_materia', $id_materia, PDO::PARAM_INT);
                                        $stmt->execute();
                                        $relacionExistente = $stmt->fetch(PDO::FETCH_ASSOC);
                                        
                                        if (!$relacionExistente) {
                                            // Crear nueva relación
                                            $stmt = $conexion->prepare("
                                                INSERT INTO materia_instructor (id_instructor, id_materia) 
                                                VALUES (:id_instructor, :id_materia)
                                            ");
                                            $stmt->bindParam(':id_instructor', $id, PDO::PARAM_INT);
                                            $stmt->bindParam(':id_materia', $id_materia, PDO::PARAM_INT);
                                            $stmt->execute();
                                        }
                                    }
                                } else {
                                    $alertMessage = "Error al actualizar el usuario";
                                    $alertType = "danger";
                                }
                            } else {
                                // Crear nuevo usuario
                                $stmt = $conexion->prepare("
                                    INSERT INTO usuarios (
                                        id, nombres, apellidos, correo, contraseña, telefono, 
                                        id_rol, id_tipo, id_estado, fecha_registro, nit
                                    ) VALUES (
                                        :id, :nombres, :apellidos, :correo, :password, :telefono, 
                                        :id_rol, :id_tipo, :id_estado, :fecha_registro, :nit
                                    )
                                ");
                                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                                $stmt->bindParam(':nombres', $nombres, PDO::PARAM_STR);
                                $stmt->bindParam(':apellidos', $apellidos, PDO::PARAM_STR);
                                $stmt->bindParam(':correo', $correo, PDO::PARAM_STR);
                                $stmt->bindParam(':password', $passwordHash, PDO::PARAM_STR);
                                $stmt->bindParam(':telefono', $telefono, PDO::PARAM_STR);
                                $stmt->bindParam(':id_rol', $id_rol, PDO::PARAM_INT);
                                $stmt->bindParam(':id_tipo', $id_tipo, PDO::PARAM_INT);
                                $stmt->bindParam(':id_estado', $id_estado, PDO::PARAM_INT);
                                $stmt->bindParam(':fecha_registro', $fecha_registro, PDO::PARAM_STR);
                                $stmt->bindParam(':nit', $nitEmpresa, PDO::PARAM_INT);
                                
                                if ($stmt->execute()) {
                                    $alertMessage = "Usuario registrado correctamente";
                                    $alertType = "success";
                                    
                                    // Si es aprendiz y tiene ficha, crear relación en user_ficha
                                    if ($requiereFicha && !empty($id_ficha)) {
                                        $stmt = $conexion->prepare("
                                            INSERT INTO user_ficha (id_user, id_ficha, fecha_asig, id_estado) 
                                            VALUES (:id_user, :id_ficha, CURRENT_DATE, 1)
                                        ");
                                        $stmt->bindParam(':id_user', $id, PDO::PARAM_INT);
                                        $stmt->bindParam(':id_ficha', $id_ficha, PDO::PARAM_INT);
                                        $stmt->execute();
                                    }
                                    
                                    // Si es instructor normal, asignar automáticamente Formacion Tecnica
                                    if ($esInstructorNormal && !empty($id_materia_formacion_tecnica)) {
                                        $stmt = $conexion->prepare("
                                            INSERT INTO materia_instructor (id_instructor, id_materia) 
                                            VALUES (:id_instructor, :id_materia)
                                        ");
                                        $stmt->bindParam(':id_instructor', $id, PDO::PARAM_INT);
                                        $stmt->bindParam(':id_materia', $id_materia_formacion_tecnica, PDO::PARAM_INT);
                                        $stmt->execute();
                                    }
                                    
                                    // Si es instructor transversal y tiene materia, crear relación
                                    if ($requiereMateria && !empty($id_materia)) {
                                        $stmt = $conexion->prepare("
                                            INSERT INTO materia_instructor (id_instructor, id_materia) 
                                            VALUES (:id_instructor, :id_materia)
                                        ");
                                        $stmt->bindParam(':id_instructor', $id, PDO::PARAM_INT);
                                        $stmt->bindParam(':id_materia', $id_materia, PDO::PARAM_INT);
                                        $stmt->execute();
                                    }
                                } else {
                                    $alertMessage = "Error al registrar el usuario";
                                    $alertType = "danger";
                                }
                            }
                            
                            // Confirmar transacción
                            $conexion->commit();
                        }
                    } catch (PDOException $e) {
                        // Rollback en caso de error
                        $conexion->rollBack();
                        $alertMessage = "Error: " . $e->getMessage();
                        $alertType = "danger";
                    }
                }
            }
        }
    }

    // Procesar Registro de usuarios por CSV
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'masivo') {
        if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] == 0) {
            $nombreArchivo = $_FILES['excel_file']['tmp_name'];
            
            // Validar extensión
            $fileExtension = pathinfo($_FILES['excel_file']['name'], PATHINFO_EXTENSION);
            if ($fileExtension != 'csv') {
                $alertMessage = "Solo se permiten archivos CSV";
                $alertType = "danger";
            } else {
                $resultados = [
                    'exitosos' => 0,
                    'errores' => 0,
                    'errores_ficha' => 0,
                    'errores_formacion' => 0,
                    'errores_tipo_documento' => 0,
                    'errores_materia' => 0,
                    'actualizados' => 0,
                    'sin_materia' => 0
                ];
                
                $erroresDetalle = [];
                
                // Obtener tipos de documento válidos
                $tiposDocumentoValidos = [];
                try {
                    $stmt = $conexion->query("SELECT id_tipo FROM tipo_documento");
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $tiposDocumentoValidos[] = $row['id_tipo'];
                    }
                } catch (PDOException $e) {
                    $alertMessage = "Error al obtener tipos de documento: " . $e->getMessage();
                    $alertType = "danger";
                    $erroresDetalle[] = "Error al obtener tipos de documento: " . $e->getMessage();
                }
                
                // Obtener ID de la materia "Formacion Tecnica"
                $idFormacionTecnica = null;
                try {
                    $stmt = $conexion->prepare("SELECT id_materia FROM materias WHERE materia = 'Formacion Tecnica'");
                    $stmt->execute();
                    $materia = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($materia) {
                        $idFormacionTecnica = $materia['id_materia'];
                    }
                } catch (PDOException $e) {
                    $erroresDetalle[] = "Error al obtener materia Formacion Tecnica: " . $e->getMessage();
                }
                
                // Leer archivo CSV
                if (($handle = fopen($nombreArchivo, "r")) !== FALSE) {
                    $esPrimera = true;

                    // Iniciar transacción
                    $conexion->beginTransaction();
                    try {
                        while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
                            if ($esPrimera) { 
                                $esPrimera = false; 
                                continue;
                            }

                            // Verificar si hay suficientes columnas (ahora puede incluir materia)
                            if (count($data) < 10) {
                                $erroresDetalle[] = "Formato incorrecto en línea: " . implode(';', $data);
                                $resultados['errores']++;
                                continue;
                            }
                            
                            // Extraer datos
                            $id = trim($data[0]);
                            $nombres = trim($data[1]);
                            $correo = trim($data[2]);
                            $password = trim($data[3]);
                            $avatar = trim($data[4]);
                            $telefono = trim($data[5]);
                            $id_rol = trim($data[6]);
                            $id_estado = 1;
                            $id_tipo = trim($data[8]);
                            $id_ficha = !empty(trim($data[9])) ? trim($data[9]) : null;
                            $materia_nombre = isset($data[10]) ? trim($data[10]) : null; // Nueva columna para materia
                            $fecha_registro = date('Y-m-d');
                            
                            // Validar que el rol sea Instructor, Aprendiz o Inst_Transversal
                            $rolesPermitidos = ['3', '4', '5'];
                            if (!in_array($id_rol, $rolesPermitidos)) {
                                $erroresDetalle[] = "Solo se permite Instructor, Aprendiz o Instructor Transversal para el usuario: " . $id . " - " . $nombres;
                                $resultados['errores']++;
                                continue;
                            }

                            // Validar datos básicos
                            if (empty($id) || empty($nombres) || empty($correo) || empty($password)) {
                                $erroresDetalle[] = "Faltan datos obligatorios en: " . $id . " - " . $nombres;
                                $resultados['errores']++;
                                continue;
                            }

                            // Validar que el tipo de documento exista
                            if (!in_array((int)$id_tipo, array_map('intval', $tiposDocumentoValidos))) {
                                $erroresDetalle[] = "El tipo de documento " . $id_tipo . " no existe para usuario: " . $id . " - " . $nombres;
                                $resultados['errores_tipo_documento']++;
                                continue;
                            }   

                            // Hash de la contraseña para seguridad
                            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                            
                            // Validaciones específicas por rol
                            $requiereFicha = ($id_rol == 4); // Aprendiz
                            $esInstructor = ($id_rol == 3); // Instructor normal
                            $esInstTransversal = ($id_rol == 5); // Instructor transversal
                            
                            // Validar ficha para aprendices
                            if ($requiereFicha && !empty($id_ficha)) {
                                $stmt = $conexion->prepare("
                                    SELECT f.id_ficha, f.id_estado as ficha_estado, fo.id_estado as formacion_estado 
                                    FROM fichas f
                                    JOIN formacion fo ON f.id_formacion = fo.id_formacion
                                    WHERE f.id_ficha = :id_ficha
                                ");
                                $stmt->bindParam(':id_ficha', $id_ficha, PDO::PARAM_INT);
                                $stmt->execute();
                                $ficha = $stmt->fetch(PDO::FETCH_ASSOC);
                                
                                if (!$ficha) {
                                    $erroresDetalle[] = "La ficha " . $id_ficha . " no existe para usuario: " . $id . " - " . $nombres;
                                    $resultados['errores_ficha']++;
                                    continue;
                                } elseif ($ficha['ficha_estado'] != 1) {
                                    $erroresDetalle[] = "La ficha " . $id_ficha . " está inactiva para usuario: " . $id . " - " . $nombres;
                                    $resultados['errores_ficha']++;
                                    continue;
                                } elseif ($ficha['formacion_estado'] != 1) {
                                    $erroresDetalle[] = "La formación de la ficha " . $id_ficha . " está inactiva para usuario: " . $id . " - " . $nombres;
                                    $resultados['errores_formacion']++;
                                    continue;
                                }
                            }
                            
                            // Determinar materia a asignar
                            $id_materia_asignar = null;
                            $registrar_sin_materia = false;
                            
                            if ($esInstructor) {
                                // Instructor normal: asignar automáticamente Formacion Tecnica
                                $id_materia_asignar = $idFormacionTecnica;
                            } elseif ($esInstTransversal) {
                                // Instructor transversal: validar materia del CSV
                                if (!empty($materia_nombre)) {
                                    $stmt = $conexion->prepare("SELECT id_materia FROM materias WHERE materia = :materia_nombre");
                                    $stmt->bindParam(':materia_nombre', $materia_nombre, PDO::PARAM_STR);
                                    $stmt->execute();
                                    $materia = $stmt->fetch(PDO::FETCH_ASSOC);
                                    
                                    if ($materia) {
                                        $id_materia_asignar = $materia['id_materia'];
                                    } else {
                                        // Materia no encontrada, registrar sin materia
                                        $registrar_sin_materia = true;
                                        $erroresDetalle[] = "Materia '" . $materia_nombre . "' no encontrada para instructor transversal: " . $id . " - " . $nombres . ". Se registrará sin materia.";
                                        $resultados['sin_materia']++;
                                    }
                                } else {
                                    // No se proporcionó materia, registrar sin materia
                                    $registrar_sin_materia = true;
                                    $erroresDetalle[] = "No se proporcionó materia para instructor transversal: " . $id . " - " . $nombres . ". Se registrará sin materia.";
                                    $resultados['sin_materia']++;
                                }
                            }
                            
                            // Verificar si el usuario ya existe
                            $stmt = $conexion->prepare("SELECT id FROM usuarios WHERE id = :id");
                            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                            $stmt->execute();
                            $usuarioExistente = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            if ($usuarioExistente) {
                                // Actualizar usuario existente
                                $stmt = $conexion->prepare("
                                    UPDATE usuarios SET 
                                    nombres = :nombres, 
                                    correo = :correo, 
                                    contraseña = :password, 
                                    avatar = :avatar, 
                                    telefono = :telefono, 
                                    id_rol = :id_rol, 
                                    id_tipo = :id_tipo, 
                                    id_estado = :id_estado, 
                                    fecha_registro = :fecha_registro, 
                                    nit = :nit 
                                    WHERE id = :id
                                ");
                                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                                $stmt->bindParam(':nombres', $nombres, PDO::PARAM_STR);
                                $stmt->bindParam(':correo', $correo, PDO::PARAM_STR);
                                $stmt->bindParam(':password', $passwordHash, PDO::PARAM_STR);
                                $stmt->bindParam(':avatar', $avatar, PDO::PARAM_STR);
                                $stmt->bindParam(':telefono', $telefono, PDO::PARAM_STR);
                                $stmt->bindParam(':id_rol', $id_rol, PDO::PARAM_INT);
                                $stmt->bindParam(':id_tipo', $id_tipo, PDO::PARAM_INT);
                                $stmt->bindParam(':id_estado', $id_estado, PDO::PARAM_INT);
                                $stmt->bindParam(':fecha_registro', $fecha_registro, PDO::PARAM_STR);
                                $stmt->bindParam(':nit', $nitEmpresa, PDO::PARAM_INT);
                                
                                if ($stmt->execute()) {
                                    $resultados['actualizados']++;
                                    
                                    // Manejar relaciones según el rol
                                    if ($requiereFicha && !empty($id_ficha)) {
                                        // Gestionar relación user_ficha para aprendices
                                        $stmt = $conexion->prepare("
                                            SELECT id_user_ficha FROM user_ficha 
                                            WHERE id_user = :id_user AND id_ficha = :id_ficha
                                        ");
                                        $stmt->bindParam(':id_user', $id, PDO::PARAM_INT);
                                        $stmt->bindParam(':id_ficha', $id_ficha, PDO::PARAM_INT);
                                        $stmt->execute();
                                        $relacionExistente = $stmt->fetch(PDO::FETCH_ASSOC);
                                        
                                        if ($relacionExistente) {
                                            $stmt = $conexion->prepare("
                                                UPDATE user_ficha SET 
                                                id_estado = 1, 
                                                fecha_asig = CURRENT_DATE 
                                                WHERE id_user = :id_user AND id_ficha = :id_ficha
                                            ");
                                            $stmt->bindParam(':id_user', $id, PDO::PARAM_INT);
                                            $stmt->bindParam(':id_ficha', $id_ficha, PDO::PARAM_INT);
                                            $stmt->execute();
                                        } else {
                                            $stmt = $conexion->prepare("
                                                INSERT INTO user_ficha (id_user, id_ficha, fecha_asig, id_estado) 
                                                VALUES (:id_user, :id_ficha, CURRENT_DATE, 1)
                                            ");
                                            $stmt->bindParam(':id_user', $id, PDO::PARAM_INT);
                                            $stmt->bindParam(':id_ficha', $id_ficha, PDO::PARAM_INT);
                                            $stmt->execute();
                                        }
                                    }
                                    
                                    // Gestionar relación materia_instructor para instructores
                                    if (($esInstructor || $esInstTransversal) && !empty($id_materia_asignar)) {
                                        $stmt = $conexion->prepare("
                                            SELECT id_detalles_instructor FROM materia_instructor 
                                            WHERE id_instructor = :id_instructor AND id_materia = :id_materia
                                        ");
                                        $stmt->bindParam(':id_instructor', $id, PDO::PARAM_INT);
                                        $stmt->bindParam(':id_materia', $id_materia_asignar, PDO::PARAM_INT);
                                        $stmt->execute();
                                        $relacionExistente = $stmt->fetch(PDO::FETCH_ASSOC);
                                        
                                        if (!$relacionExistente) {
                                            $stmt = $conexion->prepare("
                                                INSERT INTO materia_instructor (id_instructor, id_materia) 
                                                VALUES (:id_instructor, :id_materia)
                                            ");
                                            $stmt->bindParam(':id_instructor', $id, PDO::PARAM_INT);
                                            $stmt->bindParam(':id_materia', $id_materia_asignar, PDO::PARAM_INT);
                                            $stmt->execute();
                                        }
                                    }
                                } else {
                                    $erroresDetalle[] = "Error al actualizar usuario: " . $id . " - " . $nombres;
                                    $resultados['errores']++;
                                }
                            } else {
                                // Crear nuevo usuario
                                $stmt = $conexion->prepare("
                                    INSERT INTO usuarios (
                                        id, nombres, correo, contraseña, avatar, telefono, 
                                        id_rol, id_tipo, id_estado, fecha_registro, nit
                                    ) VALUES (
                                        :id, :nombres, :correo, :password, :avatar, :telefono, 
                                        :id_rol, :id_tipo, :id_estado, :fecha_registro, :nit
                                    )
                                ");
                                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                                $stmt->bindParam(':nombres', $nombres, PDO::PARAM_STR);
                                $stmt->bindParam(':correo', $correo, PDO::PARAM_STR);
                                $stmt->bindParam(':password', $passwordHash, PDO::PARAM_STR);
                                $stmt->bindParam(':avatar', $avatar, PDO::PARAM_STR);
                                $stmt->bindParam(':telefono', $telefono, PDO::PARAM_STR);
                                $stmt->bindParam(':id_rol', $id_rol, PDO::PARAM_INT);
                                $stmt->bindParam(':id_tipo', $id_tipo, PDO::PARAM_INT);
                                $stmt->bindParam(':id_estado', $id_estado, PDO::PARAM_INT);
                                $stmt->bindParam(':fecha_registro', $fecha_registro, PDO::PARAM_STR);
                                $stmt->bindParam(':nit', $nitEmpresa, PDO::PARAM_INT);
                                
                                if ($stmt->execute()) {
                                    $resultados['exitosos']++;
                                    
                                    // Manejar relaciones según el rol
                                    if ($requiereFicha && !empty($id_ficha)) {
                                        $stmt = $conexion->prepare("
                                            INSERT INTO user_ficha (id_user, id_ficha, fecha_asig, id_estado) 
                                            VALUES (:id_user, :id_ficha, CURRENT_DATE, 1)
                                        ");
                                        $stmt->bindParam(':id_user', $id, PDO::PARAM_INT);
                                        $stmt->bindParam(':id_ficha', $id_ficha, PDO::PARAM_INT);
                                        $stmt->execute();
                                    }
                                    
                                    // Gestionar relación materia_instructor para instructores
                                    if (($esInstructor || $esInstTransversal) && !empty($id_materia_asignar)) {
                                        $stmt = $conexion->prepare("
                                            INSERT INTO materia_instructor (id_instructor, id_materia) 
                                            VALUES (:id_instructor, :id_materia)
                                        ");
                                        $stmt->bindParam(':id_instructor', $id, PDO::PARAM_INT);
                                        $stmt->bindParam(':id_materia', $id_materia_asignar, PDO::PARAM_INT);
                                        $stmt->execute();
                                    }
                                } else {
                                    $erroresDetalle[] = "Error al crear usuario: " . $id . " - " . $nombres;
                                    $resultados['errores']++;
                                }
                            }
                        }
                        
                        // Commit de la transacción
                        $conexion->commit();
                        
                        // Preparar mensaje de resultado
                        $mensajeResultado = "Procesamiento completado: <br>";
                        $mensajeResultado .= "- Usuarios nuevos: " . $resultados['exitosos'] . "<br>";
                        $mensajeResultado .= "- Usuarios actualizados: " . $resultados['actualizados'] . "<br>";
                        $mensajeResultado .= "- Instructores transversales sin materia: " . $resultados['sin_materia'] . "<br>";
                        $mensajeResultado .= "- Errores formación inactiva: " . $resultados['errores_formacion'] . "<br>";
                        $mensajeResultado .= "- Errores ficha no existente/inactiva: " . $resultados['errores_ficha'] . "<br>";
                        $mensajeResultado .= "- Errores tipo documento inválido: " . $resultados['errores_tipo_documento'] . "<br>";
                        $mensajeResultado .= "- Otros errores: " . $resultados['errores'] . "<br>";
                        
                        if (count($erroresDetalle) > 0) {
                            $mensajeResultado .= "<hr><strong>Detalle de errores:</strong><br>";
                            $mensajeResultado .= implode("<br>", array_slice($erroresDetalle, 0, 10));
                            if (count($erroresDetalle) > 10) {
                                $mensajeResultado .= "<br>... y " . (count($erroresDetalle) - 10) . " errores más.";
                            }
                        }
                        
                        $modalMessage = $mensajeResultado;
                        $modalType = ($resultados['exitosos'] > 0 || $resultados['actualizados'] > 0) ? "success" : "warning";
                        
                    } catch (PDOException $e) {
                        // Rollback en caso de error
                        $conexion->rollBack();
                        $alertMessage = "Error en la transacción: " . $e->getMessage();
                        $alertType = "danger";
                    }
                    
                    fclose($handle);
                } else {
                    $alertMessage = "No se pudo abrir el archivo";
                    $alertType = "danger";
                }
            }
        } else {
            $alertMessage = "No se seleccionó ningún archivo o hubo un error al subirlo";
            $alertType = "danger";
        }
    }

    // Obtener todos los tipos de documento
    $tiposDocumento = [];
    try {
        $stmt = $conexion->query("SELECT * FROM tipo_documento ORDER BY tipo_doc");
        $tiposDocumento = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $alertMessage = "Error al cargar tipos de documento: " . $e->getMessage();
        $alertType = "danger";
    }

    // Obtener roles permitidos (Instructor, Aprendiz, Instructor Transversal)
    $roles = [];
    try {
        $stmt = $conexion->query("SELECT * FROM roles WHERE rol IN ('Instructor', 'Aprendiz', 'Instructor Transversal') ORDER BY rol");
        $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $alertMessage = "Error al cargar roles: " . $e->getMessage();
        $alertType = "danger";
    }

    // Obtener todas las fichas activas
    $fichas = [];
    try {
        $stmt = $conexion->query("
            SELECT f.id_ficha, f.id_formacion, fo.nombre as formacion_nombre 
            FROM fichas f
            JOIN formacion fo ON f.id_formacion = fo.id_formacion
            WHERE f.id_estado = 1
            ORDER BY f.id_ficha
        ");
        $fichas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $alertMessage = "Error al cargar fichas: " . $e->getMessage();
        $alertType = "danger";
    }

    // Obtener materias (excluyendo Formacion Tecnica para instructores transversales)
    $materias = [];
    try {
        $stmt = $conexion->query("
            SELECT * FROM materias 
            WHERE materia != 'Formacion Tecnica' 
            ORDER BY materia
        ");
        $materias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $alertMessage = "Error al cargar materias: " . $e->getMessage();
        $alertType = "danger";
    }

    // Parámetros de paginación
    $usuariosPorPagina = 5;
    $paginaActual = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    if ($paginaActual < 1) $paginaActual = 1;

    // Contar total de usuarios (para paginación)
    try {
        $stmt = $conexion->query("
            SELECT COUNT(DISTINCT u.id) as total
            FROM usuarios u
            LEFT JOIN roles r ON u.id_rol = r.id_rol
        ");
        $totalUsuarios = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    } catch (PDOException $e) {
        $totalUsuarios = 0;
    }

    // Calcular total de páginas
    $totalPaginas = ceil($totalUsuarios / $usuariosPorPagina);
    if ($paginaActual > $totalPaginas) $paginaActual = $totalPaginas;

    // Calcular offset
    $offset = ($paginaActual - 1) * $usuariosPorPagina;

    // Obtener lista de usuarios con paginación
    $usuarios = [];
    try {
        $stmt = $conexion->query("
            SELECT u.id, u.nombres, u.apellidos, u.correo, u.telefono, u.fecha_registro,
                r.rol, td.tipo_doc, e.estado, 
                GROUP_CONCAT(DISTINCT f.id_ficha) as fichas,
                GROUP_CONCAT(DISTINCT m.materia) as materias
            FROM usuarios u
            LEFT JOIN roles r ON u.id_rol = r.id_rol
            LEFT JOIN tipo_documento td ON u.id_tipo = td.id_tipo
            LEFT JOIN estado e ON u.id_estado = e.id_estado
            LEFT JOIN user_ficha uf ON u.id = uf.id_user
            LEFT JOIN fichas f ON uf.id_ficha = f.id_ficha
            LEFT JOIN materia_instructor mi ON u.id = mi.id_instructor
            LEFT JOIN materias m ON mi.id_materia = m.id_materia
            GROUP BY u.id
            ORDER BY u.fecha_registro DESC, u.id DESC
        ");
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $alertMessage = "Error al cargar usuarios: " . $e->getMessage();
        $alertType = "danger";
    }

    ?>

    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Gestión de Usuarios</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
        <!-- Select2 CSS -->
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <link rel="stylesheet" href="../styles/sidebard.css">
        <link rel="stylesheet" href="../styles/main.css">
    </head>
    <body>
        <div class="wrapper">
            <?php include '../includes/sidebard.php'; ?>
            <div class="main-content">
                <div class="container mt-4">
                    <!-- Tarjeta para Registro de usuarios -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">Registro De usuarios</h4>
                            <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#userModal">
                                <i class="bi bi-person-plus"></i> Nuevo Usuario
                            </button>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($alertMessage)): ?>
                                <div class="alert alert-<?php echo $alertType; ?> alert-dismissible fade show" role="alert">
                                    <?php echo $alertMessage; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                            <form action="" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="masivo">
                                <div class="row align-items-end">
                                    <div class="col-md-7 mb-3">
                                        <label for="excel_file" class="form-label">Archivo CSV con usuarios:</label>
                                        <input type="file" class="form-control" id="excel_file" name="excel_file" accept=".csv" required>
                                        <div class="form-text">
                                            Formato: Id_user;Nombres;Correo;Contrasena;Avatar;Telefono;Id_rol;Id_estado;Id_docu;ficha;materia<br>
                                            Ejemplo: 65904850;Pedro Gómez;pedrogomez@mail.com;clave123;;3001234567;5;1;1;;Inglés<br>
                                            <strong>Nota:</strong> Para instructores transversales (rol 5), incluir nombre de materia en la última columna
                                        </div>
                                    </div>
                                    <div class="col-md-5 mb-3">
                                        <div class="d-grid gap-2">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-cloud-upload"></i> Cargar Usuarios
                                            </button>
                                            <a href="plantillas/plantilla_usuarios.csv" download class="btn btn-outline-secondary">
                                                <i class="bi bi-download"></i> Descargar Plantilla
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <!-- Tarjeta para listar usuarios -->
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h4 class="mb-0">Lista de Usuarios</h4>
                        </div>
                        <div class="table-responsive">
                            <div class="d-flex justify-content-between align-items-center mt-3 mb-4 px-3">
                                <div>
                                    <label for="filasPorPagina" class="form-label me-2">Mostrar:</label>
                                    <select id="filasPorPagina" class="form-select form-select-sm d-inline-block w-auto" onchange="cambiarFilasPorPagina(this.value)">
                                        <option value="5" selected>5</option>
                                        <option value="10">10</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                    </select>
                                    <span class="ms-2 text-muted">registros por página</span>
                                </div>
                                <input 
                                    type="text" 
                                    id="busquedaUsuario" 
                                    class="form-control" 
                                    style="max-width: 350px;" 
                                    placeholder="Buscar usuario (nombre, correo...)" 
                                    oninput="filtrarUsuario()"
                                >
                            </div>
                            <table class="table table-hover" id="tablaUsuarios">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Correo</th>
                                        <th>Teléfono</th>
                                        <th>Tipo</th>
                                        <th>Rol</th>
                                        <th>Estado</th>
                                        <th>Fichas</th>
                                        <th>Materias</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($usuarios)): ?>
                                        <tr>
                                            <td colspan="9" class="text-center">No hay usuarios registrados</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($usuarios as $usuario): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($usuario['id']); ?></td>
                                                <td><?php echo htmlspecialchars($usuario['nombres'] . ' ' . $usuario['apellidos']); ?></td>
                                                <td><?php echo htmlspecialchars($usuario['correo']); ?></td>
                                                <td><?php echo htmlspecialchars($usuario['telefono']); ?></td>
                                                <td><?php echo htmlspecialchars($usuario['tipo_doc']); ?></td>
                                                <td><?php echo htmlspecialchars($usuario['rol']); ?></td>
                                                <td>
                                                    <span class="badge <?php echo ($usuario['estado'] == 'Activo') ? 'bg-success' : 'bg-danger'; ?>">
                                                        <?php echo htmlspecialchars($usuario['estado']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php
                                                    if (!empty($usuario['fichas'])) {
                                                        $fichasIds = explode(',', $usuario['fichas']);
                                                        foreach ($fichasIds as $fichaId) {
                                                            echo '<span class="badge bg-info me-1">' . htmlspecialchars($fichaId) . '</span>';
                                                        }
                                                    } else {
                                                        echo '<span class="badge bg-secondary">Sin ficha</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    if (!empty($usuario['materias'])) {
                                                        $materiasNombres = explode(',', $usuario['materias']);
                                                        foreach ($materiasNombres as $materiaNombre) {
                                                            echo '<span class="badge bg-warning me-1">' . htmlspecialchars($materiaNombre) . '</span>';
                                                        }
                                                    } else {
                                                        echo '<span class="badge bg-secondary">Sin materia</span>';
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            <nav>
                                <ul class="pagination justify-content-center" id="paginacionUsuarios"></ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal para registro manual -->
        <div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="userModalLabel">Nuevo Usuario</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="" method="POST" id="userForm">
                        <div class="modal-body">
                            <input type="hidden" name="action" value="manual">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="id" class="form-label">Número de Identificación *</label>
                                    <input type="text" class="form-control" id="id" name="id" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="id_tipo" class="form-label">Tipo de Documento *</label>
                                    <select class="form-select" id="id_tipo" name="id_tipo" required>
                                        <option value="">Seleccione un tipo</option>
                                        <?php foreach ($tiposDocumento as $tipo): ?>
                                            <option value="<?php echo $tipo['id_tipo']; ?>">
                                                <?php echo htmlspecialchars($tipo['tipo_doc']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nombres" class="form-label">Nombres *</label>
                                    <input type="text" class="form-control" id="nombres" name="nombres" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="apellidos" class="form-label">Apellidos</label>
                                    <input type="text" class="form-control" id="apellidos" name="apellidos">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="correo" class="form-label">Correo Electrónico *</label>
                                    <input type="email" class="form-control" id="correo" name="correo" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="telefono" class="form-label">Teléfono</label>
                                    <input type="text" class="form-control" id="telefono" name="telefono">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Contraseña *</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <div id="passwordHelp" class="form-text text-danger"></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="id_rol" class="form-label">Rol *</label>
                                    <select class="form-select" id="id_rol" name="id_rol" required>
                                        <option value="">Seleccione un rol</option>
                                        <?php foreach ($roles as $rol): ?>
                                            <option value="<?php echo $rol['id_rol']; ?>">
                                                <?php echo htmlspecialchars($rol['rol']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3 ficha-container" style="display: none;">
                                <label for="id_ficha" class="form-label">Ficha (Solo para Aprendices)</label>
                                <select class="form-select" id="id_ficha" name="id_ficha" style="width:100%;">
                                    <option value="">Seleccione una ficha</option>
                                    <?php foreach ($fichas as $ficha): ?>
                                        <option value="<?php echo $ficha['id_ficha']; ?>">
                                            <?php echo htmlspecialchars($ficha['id_ficha'] . ' - ' . $ficha['formacion_nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">La ficha solo es obligatoria para usuarios con rol de Aprendiz</div>
                            </div>
                            <div class="mb-3 materia-container" style="display: none;">
                                <label for="id_materia" class="form-label">Materia (Para Instructores Transversales) *</label>
                                <select class="form-select" id="id_materia" name="id_materia" style="width:100%;">
                                    <option value="">Seleccione una materia</option>
                                    <?php foreach ($materias as $materia): ?>
                                        <option value="<?php echo $materia['id_materia']; ?>">
                                            <?php echo htmlspecialchars($materia['materia']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">La materia es obligatoria para instructores transversales (no incluye Formación Técnica)</div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- Modal para mostrar resultados de Registro de usuarios -->
        <div class="modal fade" id="resultadosModal" tabindex="-1" aria-labelledby="resultadosModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header" id="resultadosModalHeader">
                        <h5 class="modal-title" id="resultadosModalLabel">Resultados de Carga</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="resultadosModalBody">
                        <!-- Aquí se insertarán los resultados -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- SCRIPTS -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <!-- Select2 -->
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script src="../js/sidebard.js"></script>
        <!-- SELECT2 para campos de ficha y materia -->
        <script>
        $(document).ready(function() {
            $('#id_ficha').select2({
                dropdownParent: $('#userModal'),
                dropdownAutoWidth: true,
                width: '100%',
                placeholder: "Seleccione una ficha",
                allowClear: true,
                dropdownPosition: 'below'
            });
            
            $('#id_materia').select2({
                dropdownParent: $('#userModal'),
                dropdownAutoWidth: true,
                width: '100%',
                placeholder: "Seleccione una materia",
                allowClear: true,
                dropdownPosition: 'below'
            });
            
            // Resetea los select2 cada vez que abras el modal
            $('#userModal').on('shown.bs.modal', function () {
                $('#id_ficha').val('').trigger('change');
                $('#id_materia').val('').trigger('change');
            });
        });
        </script>
        <!-- PAGINACIÓN Y FILTRO JS MEJORADO -->
        <script>
        let filasPorPaginaUsuarios = 5;
        let paginaActualUsuarios = 1;

        function obtenerFilasUsuariosFiltradas() {
            let filas = Array.from(document.querySelectorAll("#tablaUsuarios tbody tr"));
            let filtro = document.getElementById("busquedaUsuario").value.trim().toLowerCase();
            if (filtro === "") return filas;
            return filas.filter(fila => {
                let texto = fila.innerText.toLowerCase();
                return texto.includes(filtro);
            });
        }

        function mostrarPaginaUsuarios(pagina) {
            let filas = obtenerFilasUsuariosFiltradas();
            let totalPaginas = Math.ceil(filas.length / filasPorPaginaUsuarios);
            
            if (pagina < 1) pagina = 1;
            if (pagina > totalPaginas) pagina = totalPaginas;
            
            // Ocultar todas las filas
            document.querySelectorAll("#tablaUsuarios tbody tr").forEach(fila => fila.style.display = "none");
            
            // Mostrar filas de la página actual
            let inicio = (pagina - 1) * filasPorPaginaUsuarios;
            let fin = inicio + filasPorPaginaUsuarios;
            for (let i = inicio; i < fin && i < filas.length; i++) {
                filas[i].style.display = "";
            }
            
            // Generar paginación inteligente
            generarPaginacionInteligente(pagina, totalPaginas);
            paginaActualUsuarios = pagina;
        }

        function generarPaginacionInteligente(paginaActual, totalPaginas) {
            let paginacion = document.getElementById("paginacionUsuarios");
            paginacion.innerHTML = "";
            
            if (totalPaginas <= 1) return;
            
            const maxPaginasVisibles = 5; // Máximo número de páginas a mostrar
            let paginaInicio, paginaFin;
            
            // Calcular rango de páginas a mostrar
            if (totalPaginas <= maxPaginasVisibles) {
                paginaInicio = 1;
                paginaFin = totalPaginas;
            } else {
                // Centrar la página actual en el rango visible
                let mitad = Math.floor(maxPaginasVisibles / 2);
                paginaInicio = Math.max(1, paginaActual - mitad);
                paginaFin = Math.min(totalPaginas, paginaInicio + maxPaginasVisibles - 1);
                
                // Ajustar si estamos cerca del final
                if (paginaFin - paginaInicio < maxPaginasVisibles - 1) {
                    paginaInicio = Math.max(1, paginaFin - maxPaginasVisibles + 1);
                }
            }
            
            // Botón "Primera" (solo si no estamos en la primera página)
            if (paginaActual > 1) {
                paginacion.innerHTML += `
                    <li class="page-item">
                        <button class="page-link" onclick="cambiarPaginaUsuarios(1)" title="Primera página">
                            <i class="bi bi-chevron-double-left"></i>
                        </button>
                    </li>`;
            }
            
            // Botón "Anterior"
            paginacion.innerHTML += `
                <li class="page-item ${paginaActual === 1 ? 'disabled' : ''}">
                    <button class="page-link" onclick="cambiarPaginaUsuarios(${paginaActual - 1})" title="Página anterior">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                </li>`;
            
            // Mostrar "..." si hay páginas antes del rango visible
            if (paginaInicio > 1) {
                paginacion.innerHTML += `
                    <li class="page-item disabled">
                        <span class="page-link">...</span>
                    </li>`;
            }
            
            // Números de página
            for (let i = paginaInicio; i <= paginaFin; i++) {
                paginacion.innerHTML += `
                    <li class="page-item ${paginaActual === i ? 'active' : ''}">
                        <button class="page-link" onclick="cambiarPaginaUsuarios(${i})">${i}</button>
                    </li>`;
            }
            
            // Mostrar "..." si hay páginas después del rango visible
            if (paginaFin < totalPaginas) {
                paginacion.innerHTML += `
                    <li class="page-item disabled">
                        <span class="page-link">...</span>
                    </li>`;
            }
            
            // Botón "Siguiente"
            paginacion.innerHTML += `
                <li class="page-item ${paginaActual === totalPaginas ? 'disabled' : ''}">
                    <button class="page-link" onclick="cambiarPaginaUsuarios(${paginaActual + 1})" title="Página siguiente">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </li>`;
            
            // Botón "Última" (solo si no estamos en la última página)
            if (paginaActual < totalPaginas) {
                paginacion.innerHTML += `
                    <li class="page-item">
                        <button class="page-link" onclick="cambiarPaginaUsuarios(${totalPaginas})" title="Última página">
                            <i class="bi bi-chevron-double-right"></i>
                        </button>
                    </li>`;
            }
            
            // Mostrar información de página actual
            mostrarInfoPaginacion(paginaActual, totalPaginas, obtenerFilasUsuariosFiltradas().length);
        }

        function mostrarInfoPaginacion(paginaActual, totalPaginas, totalRegistros) {
            // Crear o actualizar el elemento de información si no existe
            let infoPaginacion = document.getElementById("infoPaginacionUsuarios");
            if (!infoPaginacion) {
                infoPaginacion = document.createElement("div");
                infoPaginacion.id = "infoPaginacionUsuarios";
                infoPaginacion.className = "text-center mt-2 text-muted small";
                document.getElementById("paginacionUsuarios").parentNode.appendChild(infoPaginacion);
            }
            
            let registroInicio = ((paginaActual - 1) * filasPorPaginaUsuarios) + 1;
            let registroFin = Math.min(paginaActual * filasPorPaginaUsuarios, totalRegistros);
            
            infoPaginacion.innerHTML = `
                Mostrando ${registroInicio} a ${registroFin} de ${totalRegistros} registros 
                (Página ${paginaActual} de ${totalPaginas})
            `;
        }

        function cambiarPaginaUsuarios(nuevaPagina) {
            mostrarPaginaUsuarios(nuevaPagina);
        }

        function filtrarUsuario() {
            paginaActualUsuarios = 1;
            mostrarPaginaUsuarios(paginaActualUsuarios);
        }

        // Función para cambiar el número de filas por página
        function cambiarFilasPorPagina(nuevasFilas) {
            filasPorPaginaUsuarios = parseInt(nuevasFilas);
            paginaActualUsuarios = 1;
            mostrarPaginaUsuarios(paginaActualUsuarios);
        }

        document.addEventListener("DOMContentLoaded", function() {
            mostrarPaginaUsuarios(paginaActualUsuarios);
        });
        </script>
        <!-- RESTO DE SCRIPTS (modales, tooltips, AJAX para editar, etc.) -->
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (!empty($modalMessage)): ?>
                const resultadosModal = new bootstrap.Modal(document.getElementById('resultadosModal'));
                const resultadosModalHeader = document.getElementById('resultadosModalHeader');
                const resultadosModalBody = document.getElementById('resultadosModalBody');
                resultadosModalHeader.className = 'modal-header <?php echo ($modalType == "success") ? "bg-success" : "bg-warning"; ?> text-white';
                resultadosModalBody.innerHTML = `<?php echo $modalMessage; ?>`;
                resultadosModal.show();
            <?php endif; ?>
            
            // Inicializar tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Mostrar/ocultar campos según el rol seleccionado
            document.getElementById('id_rol').addEventListener('change', function() {
                const fichaContainer = document.querySelector('.ficha-container');
                const fichaSelect = document.getElementById('id_ficha');
                const materiaContainer = document.querySelector('.materia-container');
                const materiaSelect = document.getElementById('id_materia');
                
                // Ocultar todos los contenedores primero
                fichaContainer.style.display = 'none';
                materiaContainer.style.display = 'none';
                fichaSelect.removeAttribute('required');
                materiaSelect.removeAttribute('required');
                
                if (this.value == '4') {
                    // Aprendiz: mostrar ficha
                    fichaContainer.style.display = 'block';
                    fichaSelect.setAttribute('required', 'required');
                } else if (this.value == '5') {
                    // Instructor Transversal: mostrar materia
                    materiaContainer.style.display = 'block';
                    materiaSelect.setAttribute('required', 'required');
                }
                // Para instructor normal (rol 3) no se muestra nada adicional
            });
            
            // Cargar datos para edición al hacer click en botón editar
            const editButtons = document.querySelectorAll('.edit-user');
            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const userId = this.getAttribute('data-id');
                    fetch('ajax/get_user.php?id=' + userId)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const user = data.user;
                                document.getElementById('id').value = user.id;
                                document.getElementById('id').readOnly = true;
                                document.getElementById('nombres').value = user.nombres;
                                document.getElementById('apellidos').value = user.apellidos;
                                document.getElementById('correo').value = user.correo;
                                document.getElementById('telefono').value = user.telefono;
                                document.getElementById('password').value = '';
                                document.getElementById('password').placeholder = 'Dejar en blanco para mantener actual';
                                document.getElementById('password').required = false;
                                document.getElementById('id_tipo').value = user.id_tipo;
                                document.getElementById('id_rol').value = user.id_rol;
                                
                                const fichaContainer = document.querySelector('.ficha-container');
                                const fichaSelect = document.getElementById('id_ficha');
                                const materiaContainer = document.querySelector('.materia-container');
                                const materiaSelect = document.getElementById('id_materia');
                                
                                // Ocultar todos los contenedores
                                fichaContainer.style.display = 'none';
                                materiaContainer.style.display = 'none';
                                fichaSelect.removeAttribute('required');
                                materiaSelect.removeAttribute('required');
                                
                                if (user.id_rol == '4') {
                                    // Aprendiz
                                    fichaContainer.style.display = 'block';
                                    if (user.id_ficha) {
                                        fichaSelect.value = user.id_ficha;
                                        $('#id_ficha').trigger('change');
                                    }
                                } else if (user.id_rol == '5') {
                                    // Instructor Transversal
                                    materiaContainer.style.display = 'block';
                                    materiaSelect.setAttribute('required', 'required');
                                    if (user.id_materia) {
                                        materiaSelect.value = user.id_materia;
                                        $('#id_materia').trigger('change');
                                    }
                                }
                                
                                document.getElementById('userModalLabel').textContent = 'Editar Usuario';
                                const userModal = new bootstrap.Modal(document.getElementById('userModal'));
                                userModal.show();
                            } else {
                                alert('Error al cargar datos del usuario: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Error al obtener datos del usuario');
                        });
                });
            });
            
            // Resetear formulario cuando se cierra el modal
            document.getElementById('userModal').addEventListener('hidden.bs.modal', function() {
                document.getElementById('userForm').reset();
                document.getElementById('id').readOnly = false;
                document.getElementById('password').required = true;
                document.getElementById('password').placeholder = '';
                document.getElementById('userModalLabel').textContent = 'Nuevo Usuario';
                
                // Ocultar contenedores adicionales
                document.querySelector('.ficha-container').style.display = 'none';
                document.querySelector('.materia-container').style.display = 'none';
                
                // Limpiar select2
                $('#id_ficha').val('').trigger('change');
                $('#id_materia').val('').trigger('change');
            });
        });
        
        </script>
        <script>
$(document).ready(function() {
    // Validación en tiempo real de contraseña
    $('#password').on('input', function() {
        const password = $(this).val();
        const regex = /^(?=.*[A-Za-z])(?=.*\d)(?=.*[!@#$%^&*()_\-+=\[\]{};':"\\|,.<>\/?]).{6,20}$/;
        if (password.length === 0) {
            $('#passwordHelp').text('');
        } else if (!regex.test(password)) {
            $('#passwordHelp').text('La contraseña debe tener entre 6 y 20 caracteres, al menos 1 letra, 1 número y 1 caracter especial.');
        } else {
            $('#passwordHelp').text('');
        }
    });

    // Validación en tiempo real de documento
    $('#id').on('input', function() {
        const doc = $(this).val();
        if (doc.length === 0) {
            $(this).removeClass('is-invalid');
            $('#docHelp').text('');
        } else if (doc.length < 8 || doc.length > 10) {
            $(this).addClass('is-invalid');
            $('#docHelp').text('El documento debe tener entre 8 y 10 caracteres.');
        } else {
            $(this).removeClass('is-invalid');
            $('#docHelp').text('');
        }
    });

    
    // Validación final al enviar
    $('#userForm').on('submit', function(e) {
        const password = $('#password').val();
        const regexPass = /^(?=.*[A-Za-z])(?=.*\d)(?=.*[!@#$%^&*()_\-+=\[\]{};':"\\|,.<>\/?]).{6,20}$/;
        const doc = $('#id').val();

        let error = false;

        if (password && !regexPass.test(password)) {
            $('#passwordHelp').text('La contraseña debe tener entre 6 y 20 caracteres, al menos 1 letra, 1 número y 1 caracter especial.');
            $('#password').focus();
            error = true;
        } else {
            $('#passwordHelp').text('');
        }

        if (doc.length < 8 || doc.length > 10) {
            $('#id').addClass('is-invalid');
            $('#docHelp').text('El documento debe tener entre 8 y 10 caracteres.');
            $('#id').focus();
            error = true;
        } else {
            $('#id').removeClass('is-invalid');
            $('#docHelp').text('');
        }

        if (error) {
            e.preventDefault();
            return false;
        }
    });
});
        </script>

    </body>
    </html>
    
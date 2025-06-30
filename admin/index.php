<?php
session_start();


if ($_SESSION['rol'] !== 2) {
    header('Location: includes/exit.php?motivo=acceso-denegado');
    exit;
}

?>




<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administrativo - Dashboard</title>
    <!-- Bootstrap y Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="icon" href="../assets/img/icon2.png">
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="styles/sidebard.css">
</head>

<body>
    <div class="wrapper">
        <!-- Incluir el sidebar -->
        <?php include 'includes/sidebard.php'; ?>
        <!-- Contenido principal -->
        <main class="main-content">
            <header class="content-header">
                <h1>Dashboard</h1>
            </header>
            <div class="content">
                <!-- Contenido del dashboard -->
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Total Usuarios</h5>
                                <p class="card-text display-4">120</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Fichas Activas</h5>
                                <p class="card-text display-4">15</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Instructores</h5>
                                <p class="card-text display-4">25</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                Actividad Reciente
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item">Usuario nuevo registrado</li>
                                    <li class="list-group-item">Ficha actualizada</li>
                                    <li class="list-group-item">Horario modificado</li>
                                    <li class="list-group-item">Nueva clase creada</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                Próximas Clases
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item">Programación - 8:00 AM</li>
                                    <li class="list-group-item">Diseño Web - 10:00 AM</li>
                                    <li class="list-group-item">Base de Datos - 1:00 PM</li>
                                    <li class="list-group-item">Redes - 3:00 PM</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/sidebard.js"></script>
    
    
</body>

</html>
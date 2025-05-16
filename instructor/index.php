<?php

require_once('../conexion/conexion.php');
include '../includes/session.php';
$conexion = new database();
$conex = $conexion->connect();


$clases = [
    ['nombre' => 'Matemáticas Avanzadas', 'descripcion' => 'Álgebra, cálculo y más.'],
    ['nombre' => 'Programación Web', 'descripcion' => 'HTML, CSS, JavaScript y PHP.'],
    ['nombre' => 'Historia Moderna', 'descripcion' => 'Revoluciones, guerras mundiales, etc.'],
    ['nombre' => 'Matemáticas Avanzadas', 'descripcion' => 'Álgebra, cálculo y más.'],
    ['nombre' => 'Programación Web', 'descripcion' => 'HTML, CSS, JavaScript y PHP.'],
    ['nombre' => 'Historia Moderna', 'descripcion' => 'Revoluciones, guerras mundiales, etc.'],
    ['nombre' => 'Matemáticas Avanzadas', 'descripcion' => 'Álgebra, cálculo y más.'],
    ['nombre' => 'Programación Web', 'descripcion' => 'HTML, CSS, JavaScript y PHP.'],
    ['nombre' => 'Historia Moderna', 'descripcion' => 'Revoluciones, guerras mundiales, etc.'],
    ['nombre' => 'Matemáticas Avanzadas', 'descripcion' => 'Álgebra, cálculo y más.'],
    ['nombre' => 'Programación Web', 'descripcion' => 'HTML, CSS, JavaScript y PHP.'],
    ['nombre' => 'Historia Moderna', 'descripcion' => 'Revoluciones, guerras mundiales, etc.'],
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teamtalks</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="../assets/img/icon2.png">
    <link rel="stylesheet" href="../styles/style_side.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>

        .main-content {
            margin-left: 250px;
            transition: margin-left 0.4s ease;
        }

        body.sidebar-collapsed .main-content {
            margin-left: 100px; /* ajusta según el ancho del sidebar colapsado */
        }
    </style>

</head>
<body style="padding-top:120px;">
    <?php
    include '../includes/design/header.php'; 
    ?>
    
    <?php
    include '../includes/design/sidebar.php';
    ?><br>

<div class="main-content">
        <div class="container">
            <h2 class="mb-4 text-center">Tus Clases</h2>
            <div class="row justify-content-center">
                <?php foreach ($clases as $clase): ?>
                    <div class="col-md-4 mb-4 d-flex">
                        <div class="card shadow w-100">
                            <div class="card-body">
                                <h5 class="card-title"><?= $clase['nombre'] ?></h5>
                                <p class="card-text"><?= $clase['descripcion'] ?></p>
                                <a href="#" class="btn btn-primary">Ver más</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    

</body>
</html>
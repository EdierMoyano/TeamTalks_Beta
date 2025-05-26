<?php
// api/foros.php
header('Content-Type: application/json');

// Datos simulados para pruebas
$foros = [
    [
        "id_foro" => 1,
        "nombre_foro" => "Foro de Matemáticas",
        "descripcion" => "Discusión sobre temas de matemáticas avanzadas.",
        "profesor" => "Dr. Juan Pérez",
        "imagen" => "",
    ],
    [
        "id_foro" => 2,
        "nombre_foro" => "Foro de Física",
        "descripcion" => "Debate sobre conceptos de física moderna.",
        "profesor" => "Dra. Ana Gómez",
        "imagen" => "",
    ],
    [
        "id_foro" => 3,
        "nombre_foro" => "Foro de Programación",
        "descripcion" => "Intercambio de ideas sobre programación web.",
        "profesor" => "Ing. Carlos López",
        "imagen" => "",
    ],
    [
        "id_foro" => 4,
        "nombre_foro" => "Foro de Química",
        "descripcion" => "Discusión sobre química orgánica y sus aplicaciones.",
        "profesor" => "Dra. María Torres",
        "imagen" => "",
    ],
    [
        "id_foro" => 5,
        "nombre_foro" => "Foro de Historia",
        "descripcion" => "Análisis de eventos históricos importantes.",
        "profesor" => "Lic. Pedro Martínez",
        "imagen" => "",
    ],
    [
        "id_foro" => 6,
        "nombre_foro" => "Foro de Literatura",
        "descripcion" => "Debate sobre obras literarias clásicas y contemporáneas.",
        "profesor" => "Prof. Laura Fernández",
        "imagen" => "",
    ],
];

echo json_encode($foros);

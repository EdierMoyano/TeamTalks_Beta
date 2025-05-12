<?php
// api/clases.php
header('Content-Type: application/json');

// Datos simulados para pruebas
$clases = [
    [
        "id_clase" => 101,
        "nombre_clase" => "Matemáticas Avanzadas",
        "nombre_profesor" => "Dr. Juan Pérez",
        "numero_fichas" => 2901879,
        "imagen" => "https://colegiopadregarralda.edu.es/wp-content/uploads/2020/04/matematicas-600x600.jpg.webp",
    ],
    [
        "id_clase" => 102,
        "nombre_clase" => "Física Moderna",
        "nombre_profesor" => "Dra. Ana Gómez",
        "numero_fichas" => 2901879,
        "imagen" => "https://ai-previews.123rf.com/ai-txt2img/600nwm/001940df-fdbc-4d95-a536-1a0ccc57488d.jpg",
    ],
    [
        "id_clase" => 103,
        "nombre_clase" => "Programación Web",
        "nombre_profesor" => "Ing. Carlos López",
        "numero_fichas" => 2901879,
        "imagen" => "https://miseo.es/wp-content/uploads/2020/11/sip.png",
    ],
    [
        "id_clase" => 104,
        "nombre_clase" => "Química Orgánica",
        "nombre_profesor" => "Dra. María Torres",
        "numero_fichas" => 2901879,
        "imagen" => "https://img.freepik.com/vector-gratis/fondo-quimica-flat_23-2148162430.jpg?semt=ais_hybrid&w=740",
    ],
    [
        "id_clase" => 105,
        "nombre_clase" => "Historia del Arte",
        "nombre_profesor" => "Lic. Laura Martínez",
        "numero_fichas" => 2901879,
        "imagen" => "https://i.pinimg.com/736x/7a/f4/ca/7af4ca33608ee3244d82671e8b2138f4.jpg",
    ],
    [
        "id_clase" => 106,
        "nombre_clase" => "Biología Celular",
        "nombre_profesor" => "Dr. Andrés Ruiz",
        "numero_fichas" => 2901879,
        "imagen" => "https://cdn0.ecologiaverde.com/es/posts/0/9/6/biologia_molecular_que_es_y_su_importancia_3690_600_square.jpg",
    ],
    [
        "id_clase" => 107,
        "nombre_clase" => "Economía Internacional",
        "nombre_profesor" => "Lic. Patricia Fernández",
        "numero_fichas" => 2901879,
    ],
    [
        "id_clase" => 108,
        "nombre_clase" => "Literatura Comparada",
        "nombre_profesor" => "Prof. Javier Ramírez",
        "numero_fichas" => 2901879,
    ],
    [
        "id_clase" => 109,
        "nombre_clase" => "Ética Profesional",
        "nombre_profesor" => "Dra. Sofía Castro",
        "numero_fichas" => 2901879,
    ],
];

echo json_encode($clases);

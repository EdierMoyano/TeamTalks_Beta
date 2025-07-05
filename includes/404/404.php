<?php
if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['DOCUMENT_ROOT'], 'htdocs') !== false) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/teamtalks/conexion/init.php';
} else {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/conexion/init.php';
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Página 404</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" href="../../assets/img/icon2.png" />
  <!-- Bootstrap -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/css/bootstrap.min.css">


  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css?family=Arvo" rel="stylesheet">

  <!-- Estilos personalizados -->
  <link rel="stylesheet" href="style.css">
</head>

<body>

  <section class="page_404">
    <div class="container text-center">
      <div class="four_zero_four_bg"></div>
      <div class="content_box_404">
        <h3>¡Parece que estás perdido!</h3>
        <p>La página que buscas no está disponible</p>
        <a href="<?= BASE_URL ?>/index.php" class="btn btn-primary link_404">Ir al inicio</a>
      </div>
    </div>
  </section>

</body>

</html>
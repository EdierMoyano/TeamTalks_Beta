<?php
session_start();
<<<<<<< HEAD
unset($_SESSION['tipo']);
unset($_SESSION['documento']);
unset($_SESSION['estado']);
unset($_SESSION['rol']);
=======
unset($_SESSION['empresa']);
unset($_SESSION['documento']);
unset($_SESSION['estado']);
unset($_SESSION['rol']);
unset($_SESSION['nombre']);
>>>>>>> 346b133f6a8dc17d05d4315ef4562bf1dc391b62
session_destroy();
session_write_close();

echo '<script>window.location = "../index.php"</script>';

?>
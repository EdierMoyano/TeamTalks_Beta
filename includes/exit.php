<?php
session_start();
unset($_SESSION['empresa']);
unset($_SESSION['documento']);
unset($_SESSION['estado']);
unset($_SESSION['rol']);
unset($_SESSION['nombre']);
session_destroy();
session_write_close();

echo '<script>window.location = "../index.php"</script>';

?>
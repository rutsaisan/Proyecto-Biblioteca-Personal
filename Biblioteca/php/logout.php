<?php
session_start();
session_destroy(); // Borra la sesión
header("Location: ../index.php"); // Vuelve al inicio
exit;
?>
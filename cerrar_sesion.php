<?php
session_start(); // Iniciar la sesión
session_destroy(); // Destruir todas las variables de sesión
header("Location: index.html"); // Redirigir a la página de login
exit();
?>
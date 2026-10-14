<?php
session_start();
// Eliminamos todas las variables de la sesión
$_SESSION = array();

// Eliminamos la sesión de forma física
session_destroy(); 

// Ahora redirigimos al usuario a la página de login
header('Location: ../');
exit;
?>

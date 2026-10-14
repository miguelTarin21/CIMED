<?php
$servername = "localhost";
$username = "root"; 
$password = "George170205"; 
$dbname = "simulador_medico"; 

// Crea la conexión
$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8mb4");

// Verifica la conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
?>
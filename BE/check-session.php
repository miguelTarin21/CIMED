<?php
session_start();

$response = ['active' => false];

// Verificamos si hay una sesión activa
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    $response['active'] = true;
}

header('Content-Type: application/json');
echo json_encode($response);
?>
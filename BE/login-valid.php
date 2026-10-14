<?php

session_start();

include 'connection.php';

$response = ['success' => false]; // Respuesta inicial

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $correo = $_POST['correo'];
    $contraseña = $_POST['contraseña'];

    // Verificamos si los campos están vacíos
    if (empty($correo) || empty($contraseña)) {
        $response['message'] = 'Por favor, asegúrese de rellenar todos los campos.';
        echo json_encode($response);
        exit; // Salimos aquí para no ejecutar el resto del código
    } else {
        // Si el correo está hasheado en la base de datos, hasheamos el correo ingresado
        $hashedCorreo = hash('sha256', $correo);

        $stmt = $conn->prepare("SELECT contraseña, superadmin FROM usuarios WHERE correo = ?");
        $stmt->bind_param("s", $hashedCorreo);  // Comparamos el correo hasheado
        $stmt->execute();
        $stmt->store_result();

        // Verificamos si el correo existe
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($hashedPassword, $superadmin);
            $stmt->fetch();
            // Verificamos la contraseña
            if (password_verify($contraseña, $hashedPassword)) {
                $_SESSION['loggedin'] = true;
                $_SESSION['correo'] = $correo;
                $_SESSION['superadmin'] = $superadmin; 
                $response['success'] = true;
            }
             else {
                $response['message'] = 'Error al iniciar sesión, credenciales incorrectas.';
            }
        } else {
            $response['message'] = 'Error al iniciar sesión, credenciales incorrectas.';
        }
    }

    // Cerramos el statement si fue inicializado
    if ($stmt) {
        $stmt->close();
    }
}

// Devolvemos la respuesta en formato JSON
header('Content-Type: application/json');
echo json_encode($response);

$conn->close();
?>

<?php

// Importar PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

// Configuración para permitir la respuesta en JSON
header('Content-Type: application/json');

// Conectar a la base de datos médica
include 'connection.php';

// Verificar si se recibió el correo electrónico desde el formulario
if (isset($_POST['correo']) && filter_var($_POST['correo'], FILTER_VALIDATE_EMAIL)) {
    $correo = $_POST['correo'];

    // Hasheamos el correo ingresado para compararlo con el correo hasheado en la base de datos
    $hashedCorreo = hash('sha256', $correo);

    // Consultar la base de datos para obtener la contraseña y verificar el correo
    $stmt = $conn->prepare("SELECT correo, contraseña FROM usuarios WHERE correo = ?");
    $stmt->bind_param("s", $hashedCorreo);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        // Generar un token único para la recuperación de contraseña
        $resetToken = bin2hex(random_bytes(32)); // Genera un token aleatorio de 64 caracteres

        // Guardar el token en la base de datos
        $stmt = $conn->prepare("UPDATE usuarios SET reset_token = ? WHERE correo = ?");
        $stmt->bind_param("ss", $resetToken, $hashedCorreo);
        $stmt->execute();

        // Crear una instancia de PHPMailer
        $mail = new PHPMailer(true);

        try {
            // Configuración del servidor SMTP de Gmail
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'simuladormedicocimed@gmail.com';        // Correo de Gmail que se utilizará como soporte
            $mail->Password = 'hxsg drej dbrq wfrt'; // App Password específicamente de PHPMailer
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Configuración del correo
            $mail->setFrom('tu_correo@gmail.com', 'Soporte');
            $mail->addAddress($correo);                      // Correo del usuario
            $mail->Subject = 'Recuperacion de cuenta';

            // URL para restablecer la contraseña (agrega el token a la URL)
            $resetLink = "https://cimahub-fcitec.tij.uabc.mx/CIMED/BE/password-reset.php?token=$resetToken"; // Este se tiene que cambiar al link de la página ya hosteada
            $mail->CharSet = 'UTF-8';
            $mail->isHTML(true);
            $mail->Body = "<html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background-color: #4A89DC; color: white; padding: 10px; text-align: center; }
                        .content { padding: 20px; background-color: #f9f9f9; }
                        .button { display: inline-block; padding: 10px 20px; background-color: #4A89DC; color: white; 
                                text-decoration: none; border-radius: 5px; margin-top: 15px; }
                        .footer { font-size: 12px; color: #666; margin-top: 20px; text-align: center; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h2>Restablecimiento de Contraseña</h2>
                        </div>
                        <div class='content'>
                            <p>Hola,</p>
                            <p>Para restablecer tu contraseña, haz clic en el siguiente botón:</p>
                            <p style='text-align: center;'><a href='$resetLink' class='button'>Restablecer Contraseña</a></p>
                            <p>Si no solicitaste este cambio, por favor ignora este correo.</p>
                        </div>
                        <div class='footer'>
                            <p>Este es un correo automático, por favor no responda a este mensaje.</p>
                        </div>
                    </div>
                </body>
            </html>
            ";

            // Enviar el correo
            $mail->send();

            echo json_encode(['success' => true, 'message' => 'Correo enviado con éxito']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => "Error al enviar el correo: {$mail->ErrorInfo}"]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Correo no registrado']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Correo no proporcionado o no válido']);
}

// Cerrar la conexión
$stmt->close();
$conn->close();
?>

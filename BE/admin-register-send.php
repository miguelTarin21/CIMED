<?php

// Importar PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

// Configuración para permitir la respuesta en JSON
header('Content-Type: application/json');

// Conectar a la base de datos
include 'connection.php';

session_start();

if (!isset($_SESSION['correo'])) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado. Sesión no iniciada.']);
    exit;
}

// Verificar si el usuario es superadmin
$correoSesion = $_SESSION['correo'];
$hashedCorreoSesion = hash('sha256', $correoSesion);

$stmt = $conn->prepare("SELECT superadmin FROM usuarios WHERE correo = ?");
$stmt->bind_param("s", $hashedCorreoSesion);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Usuario no encontrado.']);
    exit;
}

$row = $result->fetch_assoc();
if ($row['superadmin'] != 1) {
    echo json_encode(['success' => false, 'message' => 'No tiene permisos para realizar esta acción.']);
    exit;
}


// Verificar si se recibió el correo electrónico desde el formulario
if (isset($_POST['correo']) && filter_var($_POST['correo'], FILTER_VALIDATE_EMAIL)) {
    $correo = $_POST['correo'];
    
    // Verificar si el correo ya está registrado
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE correo = ?");
    $hashedCorreo = hash('sha256', $correo); // Hash el correo para buscar en la BD
    $stmt->bind_param("s", $hashedCorreo);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Este correo ya está registrado como administrador']);
        $stmt->close();
        $conn->close();
        exit;
    }
    $stmt->close();
    
    // Crear una instancia de PHPMailer
    $mail = new PHPMailer(true);
    
    try {
        // Configuración del servidor SMTP de Gmail
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'simuladormedicocimed@gmail.com'; // Tu correo
        $mail->Password = 'hxsg drej dbrq wfrt'; // Tu contraseña de aplicación
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Configuración del correo
        $mail->setFrom('simuladormedicocimed@gmail.com', 'Sistema Administrador'); // Usar el mismo correo como remitente
        $mail->addAddress($correo);
        $mail->Subject = 'Invitación para registro de administrador';
        
        // URL para el registro
        $registerLink = "https://cimahub-fcitec.tij.uabc.mx/CIMED/BE/register.php"; //cambiar url para pruebas locales
        
        // Configurar codificación de caracteres
        $mail->CharSet = 'UTF-8';
        
        // Contenido del correo en HTML
        $mail->isHTML(true);
        $mail->Body = "
            <html>
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
                        <h2>Invitación de Registro de Administrador CIMED</h2>
                    </div>
                    <div class='content'>
                        <p>Hola,</p>
                        <p>Has sido invitado para registrarte como administrador en nuestro sistema CIMED.</p>
                        <p>Para completar tu registro, haz clic en el siguiente botón:</p>
                        <p style='text-align: center;'><a href='$registerLink' class='button'>Completar Registro</a></p>
                        <p>Si no solicitaste esta invitación, puedes ignorar este correo.</p>
                    </div>
                    <div class='footer'>
                        <p>Este es un correo automático, por favor no responda a este mensaje.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
        
        // Versión de texto plano para clientes de correo que no soportan HTML
        $mail->AltBody = "Hola,\n\nHas sido invitado para registrarte como administrador en nuestro sistema.\n\n".
                        "Para completar tu registro, haz clic en el siguiente enlace:\n$registerLink\n\n".
                        "Si no solicitaste esta invitación, puedes ignorar este correo.";
        
        // Enviar el correo
        $mail->send();
        
        echo json_encode(['success' => true, 'message' => 'Invitación enviada con éxito']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => "Error al enviar el correo: {$mail->ErrorInfo}"]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Correo no proporcionado o no válido']);
}

// Cerrar la conexión
$conn->close();
?>
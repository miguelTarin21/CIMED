<?php
session_start();  // Iniciar sesión para trabajar con variables de sesión
include 'connection.php';  // Conexión a la base de datos

$message = '';

// Verificar si se recibió el token en la URL
if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Buscar al usuario con ese token
    $stmt = $conn->prepare("SELECT id, correo FROM usuarios WHERE reset_token = ?");
    $stmt->bind_param("s", $token);  // "s" significa que el parámetro es una cadena (string)
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        echo "Token inválido o expirado.";
        exit;
    }
} else {
    echo "No se proporcionó un token.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Obtener la nueva contraseña desde el formulario
    $newPassword = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    // Validar que las contraseñas coincidan
    if ($newPassword !== $confirmPassword) {
        echo "Las contraseñas no coinciden.";
        exit;
    }

    // Hashear la nueva contraseña
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    // Actualizar la contraseña en la base de datos y eliminar el token
    $stmt = $conn->prepare("UPDATE usuarios SET contraseña = ?, reset_token = NULL WHERE id = ?");
    $stmt->bind_param("si", $hashedPassword, $user['id']);
    if ($stmt->execute()) {
        $message = "Contraseña actualizada exitosamente. Ya puedes cerrar esta pestaña";
    } else {
        $message = "Error al actualizar la contraseña.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Contraseña - CIMED</title>
    <link rel="icon" type="image/png" href="images/CimarronMedicoIndexIcon.png">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet" />
    <style>
        html,
        body {
            background: linear-gradient(100deg, #eaecf7 0%, #dce0f3 100%);
            font-family: 'Poppins', sans-serif;
            height: 100%;
            margin: 0;
            display: flex;
            flex-direction: column;
        }
        
        .brand-name {
            font-size: 24px;
            font-weight: bold;
            color: #2f50ff;
            display: inline-block;
        }
        .brand-ci,
        .brand-med {
            margin: 0;
            padding: 0;
            line-height: 1;
        }
        .brand-ci {
            color: #00723e;
        }
        .brand-med {
            color: #2f50ff;
        }
        
        .navbar-nav .nav-link {
            color: #000000;
            font-weight: 500;
            transition: font-weight 0.3s ease;
        }
        .navbar-nav .nav-link:hover {
            color: #333;
            font-weight: bold;
        }
        
        .container {
            width: 100%;
            max-width: 600px;
            padding: 0 15px;
        }
        
        main {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .register-container {
            max-width: 90%;
            margin: 20px auto;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 20px;
            position: relative;
            overflow: hidden;
            height: 100%;
        }
        
        .register-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #2f50ff 0%, #42a7ff 100%);
        }
        
        .register-title {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            text-align: center;
            margin-bottom: 15px;
        }

        .register-logo {
            text-align: center;
            margin-bottom: 10px;
        }

        .register-logo img {
            height: 60px;
        }
        
        .form-control {
            border-radius: 8px;
            padding: 8px 12px;
            border: 1px solid #ddd;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #2f50ff;
            box-shadow: 0 0 0 0.2rem rgba(47, 80, 255, 0.25);
        }
        
        .btn-submit {
            background: linear-gradient(90deg, #2f50ff 0%, #42a7ff 100%);
            border-radius: 50px;
            color: white;
            padding: 8px 20px;
            font-weight: bold;
            transition: background 0.3s ease;
            width: 100%;
            margin-top: 15px;
            border: none;
        }
        
        .btn-submit:hover {
            background: linear-gradient(90deg, #42a7ff 0%, #2f50ff 100%);
        }
        
        .input-with-icon {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            right: 12px;
            cursor: pointer;
            color: #6c757d;
        }
        
        .form-floating > .form-control {
            padding-top: 1.4rem;
            padding-bottom: 0.4rem;
            height: calc(2.5rem + 2px);
        }
        
        .form-floating > label {
            padding: 0.75rem 0.75rem;
        }
        
        .footer {
            width: 100%;
            background-color: #f8f9fa;
            text-align: center;
            padding: 5px 0;
            margin-top: auto;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <span class="brand-name"><span class="brand-ci">CI</span><span class="brand-med">MED</span></span>
            </a>
        </div>
    </nav>

    <main>
        <div class="container">
            <div class="register-container">
                <div class="register-logo">
                <img src="../images/CimarronMedicoIndexIcon.png" alt="CIMED Logo">
            </div>
                <h2 class="register-title">Cambiar Contraseña</h2>
                <!-- Formulario de cambio de contraseña -->
                <form method="POST">
                    <div class="form-floating input-with-icon mb-3">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Nueva Contraseña" required>
                        <label for="password">Nueva Contraseña</label>
                    </div>
                    <div class="form-floating input-with-icon mb-3">
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirmar Contraseña" required>
                        <label for="confirm_password">Confirmar Contraseña</label>
                    </div>
                    <button class="btn btn-submit" type="submit">
                        <i class="bi bi-key me-1"></i> Cambiar Contraseña
                    </button>
                </form>
                <!-- Mensaje de respuesta -->
                <?php if ($message): ?>
                    <div class="mt-3 text-center text-success">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer class="footer py-2 bg-light">
        <div class="container text-center">
            <span class="text-muted small">Cuarta Generación de Ingeniería en Software © 2024</span>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>



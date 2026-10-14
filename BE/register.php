<?php
// Procesamiento del formulario - Colocado al inicio del archivo
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    include 'connection.php';

    $email = $_POST['correo'];
    $contraseña = $_POST['contraseña'];
    $clave = $_POST['clave'];

    // Validaciones del servidor
    $errors = [];
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['correo'] = "Por favor ingrese un correo electrónico válido";
    }
    
    if (strlen($contraseña) < 5) {
        $errors['contraseña'] = "La contraseña debe tener al menos 5 caracteres";
    }
    
    if (strlen($clave) < 5) {
        $errors['clave'] = "La clave debe tener al menos 5 caracteres";
    } elseif (!ctype_alnum($clave)) {
        $errors['clave'] = "La clave no debe contener caracteres especiales";
    }
    
    // Variable para almacenar los mensajes (se usará más adelante en el HTML)
    $errorScript = "";
    $successMessage = "";
    
    if (!empty($errors)) {
        $errorScript = "<script>
                document.addEventListener('DOMContentLoaded', function() {";
        foreach ($errors as $field => $message) {
            $errorScript .= "document.getElementById('{$field}').classList.add('input-error');";
            $errorScript .= "document.getElementById('{$field}-feedback').textContent = '{$message}';";
            $errorScript .= "document.getElementById('{$field}-feedback').classList.add('show');";
        }
        $errorScript .= "});
            </script>";
    } else {
        $hashedEmail = hash('sha256', $email);
        $hashedPassword = password_hash($contraseña, PASSWORD_DEFAULT);
        $hashedKey = hash('sha256', $clave);
        $resetToken = '';

        // Verificar si el correo ya existe
        $checkStmt = $conn->prepare("SELECT id FROM usuarios WHERE correo = ?");
        $checkStmt->bind_param("s", $hashedEmail);
        $checkStmt->execute();
        $result = $checkStmt->get_result();

        if ($result->num_rows > 0) {
            $errorScript = "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        document.getElementById('correo').classList.add('input-error');
                        document.getElementById('correo-feedback').innerHTML = '<i class=\"bi bi-exclamation-circle feedback-icon\"></i> Este correo electrónico ya está registrado';
                        document.getElementById('correo-feedback').classList.add('show');
                    });
                </script>";
        } else {
            // Verificar si la clave ya existe en la base de datos
            $checkKeyStmt = $conn->prepare("SELECT id FROM usuarios WHERE clave_asignada = ?");
            $checkKeyStmt->bind_param("s", $hashedKey);
            $checkKeyStmt->execute();
            $keyResult = $checkKeyStmt->get_result();

            if ($keyResult->num_rows > 0) {
                $errorScript = "<script>
                        document.addEventListener('DOMContentLoaded', function() {
                            document.getElementById('clave').classList.add('input-error');
                            document.getElementById('clave-feedback').innerHTML = '<i class=\"bi bi-exclamation-circle feedback-icon\"></i> Esta clave ya está en uso. Por favor ingrese otra';
                            document.getElementById('clave-feedback').classList.add('show');
                        });
                    </script>";
            } else {
                // Registrar el nuevo usuario
                $stmt = $conn->prepare("INSERT INTO usuarios (correo, contraseña, reset_token, clave_asignada) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $hashedEmail, $hashedPassword, $resetToken, $hashedKey);

                if ($stmt->execute()) {
                    // Iniciar sesión para el nuevo usuario
                    session_start();
                    $_SESSION['loggedin'] = true;
                    $_SESSION['correo'] = $email; // Guardar el correo sin hashear en la sesión
                    
                    $successMessage = "<div id='temp-message' class='response-message success-message'><i class='bi bi-check-circle'></i> ¡Registro exitoso! Redireccionando...</div>
                    <script>
                        setTimeout(function() {
                            window.location.href = '../admin-panel.php';
                        }, 2000);
                    </script>";
                } else {
                    $successMessage = "<div id='temp-message' class='response-message error-message'><i class='bi bi-exclamation-triangle'></i> Error al registrar el usuario</div>";
                }
                $stmt->close();
            }
            $checkKeyStmt->close();
        }
        $checkStmt->close();
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CIMED - Registro de Administrador</title>
    <link rel="icon" type="image/png" href="../images/CimarronMedicoIndexIcon.png">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet" />
    <link href="../css/register.css" rel="stylesheet"/>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="register.php">
                <span class="brand-name">
                    <span class="brand-ci">CI</span><span class="brand-med">MED</span>
                </span>
            </a>
        </div>
    </nav>

    <main>
    <div class="container">
        <div class="register-container">
            <div class="register-logo">
                <img src="../images/CimarronMedicoIndexIcon.png" alt="CIMED Logo">
            </div>
            <h2 class="register-title">Formulario de Registro</h2>

            <!-- Formulario -->
            <form action="register.php" method="POST" autocomplete="off">
                <!-- Campo de correo -->
                <div class="mb-3">
                    <div class="form-floating">
                        <input type="email" class="form-control" id="correo" name="correo" placeholder="correo@ejemplo.com" required>
                        <label for="correo"><i class="bi bi-envelope me-1"></i>Correo electrónico</label>
                    </div>
                    <div id="correo-feedback" class="validation-feedback">
                        <i class="bi bi-exclamation-circle feedback-icon"></i>
                        Por favor ingrese un correo válido
                    </div>
                </div>

                <!-- Campo de contraseña -->
                <div class="mb-3">
                    <div class="form-floating input-with-icon">
                        <input type="password" class="form-control" id="contraseña" name="contraseña" placeholder="Contraseña" required>
                        <label for="contraseña"><i class="bi bi-lock me-1"></i>Contraseña</label>
                    </div>
                    <div id="contraseña-feedback" class="validation-feedback">
                        <i class="bi bi-exclamation-circle feedback-icon"></i>
                        La contraseña debe tener al menos 5 caracteres
                    </div>
                </div>

                <!-- Campo de clave -->
                <div class="mb-3">
                    <div class="form-floating input-with-icon">
                        <input type="password" class="form-control" id="clave" name="clave" placeholder="Clave de registro" required>
                        <label for="clave"><i class="bi bi-key me-1"></i>Clave de Casos</label>
                    </div>
                    <div id="clave-feedback" class="validation-feedback">
                        <i class="bi bi-exclamation-circle feedback-icon"></i>
                        La clave debe tener al menos 5 caracteres y no contener símbolos
                    </div>
                </div>
                <div class="clave-info">
                    <i class="bi bi-info-circle"></i> La clave ingresada será la que se relacionará con sus casos creados y la forma en que personas podrán resolverlos.
                </div>
                
                <button class="btn btn-submit" type="submit">
                    <i class="bi bi-person-plus me-1"></i> Completar Registro
                </button>
            </form>

            <?php 
            // Mostrar mensajes de éxito o error si existen
            if (isset($errorScript)) echo $errorScript;
            if (isset($successMessage)) echo $successMessage;
            ?>
        </div>
    </div>
    </main>

    <footer class="footer py-2 bg-light">
        <div class="container text-center">
            <span class="text-muted small">Cuarta Generación de Ingeniería en Software © 2024</span>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/register.js"></script>
</body>
</html>
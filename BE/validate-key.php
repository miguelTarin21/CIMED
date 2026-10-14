<?php
session_start(); // Iniciamos sesión antes de cualquier salida

header('Content-Type: application/json');
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

include("connection.php");

$response = ['success' => false, 'message' => 'Credenciales incorrectas'];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido', 405);
    }

    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Formato de datos inválido', 400);
    }

    if (!isset($data['key']) || !is_string($data['key'])) {
        throw new Exception('Datos incompletos', 400);
    }

    $key = trim($data['key']);
    if (empty($key) || strlen($key) > 50) {
        throw new Exception('Longitud de código inválida', 400);
    }

    $hashedKey = hash('sha256', $key);

    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE clave_asignada = ? LIMIT 1");
    if ($stmt === false) {
        throw new Exception('Error en el sistema', 500);
    }

    $stmt->bind_param("s", $hashedKey);
    if (!$stmt->execute()) {
        throw new Exception('Error en la verificación', 500);
    }

    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($userId);
        $stmt->fetch();

        // Guardamos el ID en la sesión
        $_SESSION['userId'] = $userId;

        $response = [
            'success' => true,
            'message' => 'Validación exitosa'
        ];
    }

} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    $response['message'] = $e->getMessage();
    error_log("Error en validate-key: " . $e->getMessage());

} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
    
    header("X-Content-Type-Options: nosniff");
    echo json_encode($response);
}
?>

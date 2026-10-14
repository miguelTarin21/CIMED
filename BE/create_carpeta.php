<?php
include 'connection.php';
session_start();

header('Content-Type: application/json');

if (empty($_SESSION['loggedin'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$user = $_SESSION['correo'];
$hashedCorreo = hash('sha256', $user);

// Obtener ID del usuario
$stmt = $conn->prepare("SELECT id FROM usuarios WHERE correo = ?");
$stmt->bind_param("s", $hashedCorreo);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
    exit;
}
$row = $result->fetch_assoc();
$userId = $row['id'];
$stmt->close();

// Decodificar datos
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['nombre']) || trim($data['nombre']) === '') {
    echo json_encode(['success' => false, 'message' => 'El nombre de la carpeta es requerido']);
    exit;
}

$nombre = trim($data['nombre']);
$descripcion = isset($data['descripcion']) ? trim($data['descripcion']) : '';

// Insertar carpeta
$stmt = $conn->prepare("INSERT INTO carpetas (nombre, descripcion, id_usuario_creador) VALUES (?, ?, ?)");
$stmt->bind_param("ssi", $nombre, $descripcion, $userId);

if ($stmt->execute()) {
    $idCarpeta = $stmt->insert_id;
    echo json_encode([
        'success'  => true,
        'message'  => 'Carpeta creada correctamente',
        'idCarpeta' => $idCarpeta,
        'nombre'   => htmlspecialchars($nombre)
    ]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}

$stmt->close();
$conn->close();
?>

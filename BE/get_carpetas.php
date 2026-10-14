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

// Obtener el ID del usuario
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

// Obtener carpetas del usuario
$stmt = $conn->prepare("SELECT id, nombre, descripcion, DATE_FORMAT(fecha_creacion, '%d/%m/%Y') as fecha_creacion FROM carpetas WHERE id_usuario_creador = ? ORDER BY nombre ASC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$carpetas = [];
while ($row = $result->fetch_assoc()) {
    $carpetas[] = [
        'id'           => $row['id'],
        'nombre'       => htmlspecialchars($row['nombre']),
        'descripcion'  => htmlspecialchars($row['descripcion'] ?? ''),
        'fecha'        => $row['fecha_creacion']
    ];
}
$stmt->close();
$conn->close();

echo json_encode(['success' => true, 'carpetas' => $carpetas]);
?>

<?php
// Elimina un video por su ID (y opcionalmente el archivo físico)
include 'connection.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id']) || !filter_var($data['id'], FILTER_VALIDATE_INT)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID de video inválido o no proporcionado']);
    exit;
}

$id = intval($data['id']);

// Obtener la ruta del archivo antes de eliminar
$stmt = $conn->prepare("SELECT ruta FROM videos WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->bind_result($ruta);
$stmt->fetch();
$stmt->close();

// Eliminar de la base de datos
$stmt = $conn->prepare("DELETE FROM videos WHERE id = ?");
$stmt->bind_param('i', $id);

if ($stmt->execute()) {
    // Intentar eliminar el archivo físico si existe
    if ($ruta) {
        // Normalizar ruta relativa desde raíz del proyecto
        $rutaFisica = __DIR__ . '/../' . ltrim(str_replace('./', '', $ruta), '/');
        if (file_exists($rutaFisica)) {
            unlink($rutaFisica);
        }
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Video eliminado correctamente']);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error al eliminar el video: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>

<?php
// Obtiene todos los videos de un caso
include 'connection.php';

if (!isset($_GET['id_caso']) || !filter_var($_GET['id_caso'], FILTER_VALIDATE_INT)) {
    echo json_encode(['error' => 'ID de caso inválido o no proporcionado']);
    exit;
}

$idCaso = intval($_GET['id_caso']);

$sql  = "SELECT id, nombre, ruta FROM videos WHERE id_caso = ? ORDER BY id ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $idCaso);
$stmt->execute();
$result = $stmt->get_result();

$videos = [];
while ($row = $result->fetch_assoc()) {
    $videos[] = [
        'id'     => $row['id'],
        'nombre' => htmlspecialchars($row['nombre']),
        'ruta'   => $row['ruta'],
    ];
}

$stmt->close();
$conn->close();

header('Content-Type: application/json');
echo json_encode($videos);
?>

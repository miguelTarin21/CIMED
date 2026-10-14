<?php
include 'connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $visibilidad = intval($_POST['visibilidad']);

    // Cambiamos la visibilidad al valor opuesto
    $newVisibilidad = $visibilidad === 1 ? 0 : 1;

    // Actualizamos la visibilidad en la base de datos
    $sql = "UPDATE casos SET visibilidad = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $newVisibilidad, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'newVisibilidad' => $newVisibilidad]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar la visibilidad']);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>
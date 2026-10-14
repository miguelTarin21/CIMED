<?php
include 'connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);

    // Consulta SQL para eliminar el caso clínico
    $sql = "DELETE FROM casos WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar el caso clínico de la DB']);
    }
    $stmt->close();
    $conn->close();

    // Eliminar el directorio del caso clínico
    $dirToDel = "../uploads/caso_$id";

    if (!eliminarCarpetaRecursiva($dirToDel)) {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar el directorio del caso clínico']);
    }

    // Responder con éxito
    echo json_encode(['success' => true]);

} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}

// Función para eliminar una carpeta y su contenido de forma recursiva
function eliminarCarpetaRecursiva($carpeta) {
    if (!is_dir($carpeta)) {
        return true;
    }

    $archivos = array_diff(scandir($carpeta), array('.', '..'));

    foreach ($archivos as $archivo) {
        $rutaArchivo = $carpeta . DIRECTORY_SEPARATOR . $archivo;
        if (is_dir($rutaArchivo)) {
            eliminarCarpetaRecursiva($rutaArchivo); // Llamada recursiva para subcarpetas
        } else {
            unlink($rutaArchivo); // Elimina el archivo
        }
    }

    return rmdir($carpeta); // Finalmente elimina la carpeta vacía
}
?>
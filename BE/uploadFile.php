<?php
// Conexion a la base de datos
include 'connection.php';

// Verifica si el archivo ha sido enviado y si los IDs están presentes
if (isset($_FILES['archivo']) && isset($_POST['id_caso']) && isset($_POST['nombre_estudio'])) {
    $idCaso = intval($_POST['id_caso']); // Sanitiza input
    $nombreEstudio = $_POST['nombre_estudio'];

    if ($_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
        $nombreArchivo = basename($_FILES['archivo']['name']); // Asegúrate de que solo sea el nombre
        $rutaTemporal = $_FILES['archivo']['tmp_name'];

        // Crear carpeta si no existe
        $rutaBase = '../uploads/';
        $rutaCaso = $rutaBase . 'caso_' . $idCaso;
        if (!file_exists($rutaBase)) {
            mkdir($rutaBase, 0777, true);
        }
        if (!file_exists($rutaCaso)) {
            mkdir($rutaCaso, 0777, true);
        }
        
        $rutaDestino = $rutaCaso . '/' . $nombreArchivo;
        
        // Verificar si el archivo ya existe
        $contador = 1;
        while (file_exists($rutaDestino)) {
            $nombreArchivo = $contador . '_' . $nombreArchivo;
            $rutaDestino = $rutaCaso . '/' . $nombreArchivo;
            $contador++;
        }

        // Mover archivo
        if (move_uploaded_file($rutaTemporal, $rutaDestino)) {

            // Guardar en la base de datos
            $rutaDestino = substr($rutaDestino, 1); // Elimina el primer caracter de la ruta
            $sql = "INSERT INTO archivos (nombre, ruta, id_caso) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssi", $nombreEstudio, $rutaDestino, $idCaso);

            if ($stmt->execute()) {
                echo json_encode(["success" => true, "message" => "Archivo $nombreArchivo subido y guardado con éxito."]);
            } else {
                echo json_encode(["success" => false, "message" => "Error al guardar en la base de datos: " . $conn->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(["success" => false, "message" => "Error al mover el archivo $nombreArchivo."]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Error con el archivo: " . $_FILES['archivo']['error']]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Faltan datos o archivo."]);
}

// Cerrar conexión
$conn->close();
?>

<?php
// Sube un video y lo guarda en la tabla `videos` (soporta múltiples por caso)
include 'connection.php';

if (isset($_FILES['archivo']) && isset($_POST['id_caso'])) {
    $idCaso      = intval($_POST['id_caso']);
    $nombreVideo = isset($_POST['nombre_video']) && trim($_POST['nombre_video']) !== ''
                    ? trim($_POST['nombre_video'])
                    : basename($_FILES['archivo']['name']);

    if ($_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
        $nombreArchivo = basename($_FILES['archivo']['name']);
        $rutaTemporal  = $_FILES['archivo']['tmp_name'];

        // Crear carpeta si no existe
        $rutaBase = '../uploads/videos/';
        if (!file_exists($rutaBase)) {
            mkdir($rutaBase, 0777, true);
        }

        // Nombre único para el archivo en disco
        $nombreFisico = time() . '_' . preg_replace("/[^a-zA-Z0-9.-]/", "_", $nombreArchivo);
        $rutaDestino  = $rutaBase . $nombreFisico;

        if (move_uploaded_file($rutaTemporal, $rutaDestino)) {
            // Guardar en la tabla videos (ruta relativa desde la raíz web)
            $rutaBD = './uploads/videos/' . $nombreFisico;

            $sql  = "INSERT INTO videos (nombre, ruta, id_caso) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssi", $nombreVideo, $rutaBD, $idCaso);

            if ($stmt->execute()) {
                $insertedId = $stmt->insert_id;
                echo json_encode([
                    "success" => true,
                    "message" => "Video subido y guardado con éxito.",
                    "id"      => $insertedId,
                    "nombre"  => $nombreVideo,
                    "ruta"    => $rutaBD
                ]);
            } else {
                echo json_encode([
                    "success" => false,
                    "message" => "Error al guardar en la base de datos: " . $conn->error
                ]);
            }
            $stmt->close();
        } else {
            echo json_encode(["success" => false, "message" => "Error al mover el video $nombreArchivo."]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Error con el archivo de video: " . $_FILES['archivo']['error']]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Faltan datos o archivo de video."]);
}

$conn->close();
?>

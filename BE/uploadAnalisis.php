<?php
// Conexión a la base de datos
include 'connection.php';
// Recibe y decodifica los datos JSON
$data = json_decode(file_get_contents("php://input"), true);

// Comprobar si los datos existen
if (isset($data['nombreEstudio'], $data['descripcionEstudio'], $data['tipoEstudio'])) {
    $nombreEstudio = $data['nombreEstudio'];
    $descripcionEstudio = $data['descripcionEstudio'];
    $tipoEstudio = $data['tipoEstudio'];

    // Inserción en la tabla estudios
    $sql = "INSERT INTO estudios (nombre, descripcion, tipo_estudio) VALUES (?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("sss", $nombreEstudio, $descripcionEstudio, $tipoEstudio);

        if ($stmt->execute()) {
            // Respuesta exitosa
            echo json_encode(["success" => true, "message" => "Estudio insertado correctamente en la tabla estudios"]);
        } else {
            // Error en la ejecución
            echo json_encode(["success" => false, "message" => "Error al insertar el estudio en la tabla estudios"]);
        }

        $stmt->close();
    } else {
        echo json_encode(["success" => false, "message" => "Error en la preparación de la consulta"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Datos incompletos"]);
}

$conn->close();
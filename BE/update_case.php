<?php
// Configuración de la conexión a la base de datos
include 'connection.php';

// Decodificar datos JSON recibidos
$data = json_decode(file_get_contents("php://input"), true);

// Verificar que el JSON fue recibido correctamente y contiene los datos necesarios
if (isset($data['caso'], $data['paciente'], $data['preguntas'], $data['rutas'])) {

    // Variables del caso
    $casoId = $data['caso']['casoId'];
    $casoInfo = $data['caso'];
    $pacienteInfo = $data['paciente'];

    // Primero borramos las preguntas y opciones asociadas al caso (solo ocupamos eliminar las preguntas, ya que las opciones se eliminan en cascada)
    $stmt = $conn->prepare("DELETE FROM preguntas WHERE id_caso = ?");
    $stmt->bind_param("i", $casoId);
    $stmt->execute();
    $stmt->close();

    // Actualizar la info del caso
    $stmt = $conn->prepare("UPDATE casos SET nombre = ?, clave = ?, descripcion = ?, motivo_consulta = ?, visibilidad = ?, fecha_actualizacion = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->bind_param("sssssi", 
        $casoInfo['nombre'],
        $casoInfo['clave'],
        $casoInfo['descripcion'],
        $casoInfo['motivoConsulta'],
        $casoInfo['visibilidad'],
        $casoId
    );

    if (!$stmt->execute()) {
        echo json_encode(["error" => "Failed to update case info: " . $stmt->error]);
        exit;
    }
    $stmt->close();

    // Actualizar la info del paciente
    $stmt = $conn->prepare("UPDATE pacientes SET nombre = ?, sexo = ?, edad = ?, peso = ?, altura = ?, enfermedades = ? WHERE id_caso = ?");
    $stmt->bind_param("ssssssi",
        $pacienteInfo['nombre'],
        $pacienteInfo['sexo'],
        $pacienteInfo['edad'],
        $pacienteInfo['peso'],
        $pacienteInfo['altura'],
        $pacienteInfo['enfermedades'],
        $casoId
    );

    if (!$stmt->execute()) {
        echo json_encode(["error" => "Failed to update patient profile: " . $stmt->error]);
        exit;
    }
    $stmt->close();

    // Obtener el id_paciente basado en el id_caso
    $sqlCaso = "SELECT id FROM pacientes WHERE id_caso = ?";
    $stmtCaso = $conn->prepare($sqlCaso);
    $stmtCaso->bind_param('i', $casoId);
    $stmtCaso->execute();
    $stmtCaso->bind_result($id_paciente); 
    $stmtCaso->fetch();
    $stmtCaso->close();

    // Actualizar los signos vitales del paciente
    $ritmo = isset($pacienteInfo['ritmo']) ? $pacienteInfo['ritmo'] : 'sinusal';
    $stmt = $conn->prepare("UPDATE signos_pacientes SET FR = ?, FC = ?, TA = ?, Temperatura = ?, SAT = ?, ritmo = ? WHERE id_paciente = ?");
    $stmt->bind_param("iisdisi", 
        $pacienteInfo['frecuenciaRespiratoria'], 
        $pacienteInfo['frecuenciaCardiaca'], 
        $pacienteInfo['tensionArterial'], 
        $pacienteInfo['temperatura'], 
        $pacienteInfo['saturacion'], 
        $ritmo,
        $id_paciente
    );

    if (!$stmt->execute()) {
        echo json_encode(["error" => "Failed to update patient signs: " . $stmt->error]);
        exit;
    }
    $stmt->close();

    // Insertar preguntas y sus opciones
    foreach ($data['preguntas'] as $pregunta) {
        $preguntaTexto = $pregunta['texto_pregunta'];
        $feedback = $pregunta['feedback'];

        // Insertar pregunta
        $stmt = $conn->prepare("INSERT INTO preguntas (texto_pregunta, feedback, id_caso) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $preguntaTexto, $feedback, $casoId);
        
        if ($stmt->execute()) {
            $preguntaId = $stmt->insert_id;

            // Insertar opciones
            foreach ($pregunta['incisos'] as $inciso) {
                $stmtInciso = $conn->prepare("INSERT INTO incisos (id_pregunta, texto, correcto) VALUES (?, ?, ?)");
                $stmtInciso->bind_param("isi", $preguntaId, $inciso['texto'], $inciso['correcto']);
                $stmtInciso->execute();
                $stmtInciso->close();
            }
        } else {
            echo json_encode(["error" => "Failed to insert question: " . $stmt->error]);
            exit;
        }
        $stmt->close();
    }

    // Borrar todo los archivos de la DB, menos los que se van a subir
    $stmt = $conn->prepare("SELECT id, nombre, ruta FROM archivos WHERE id_caso = ?");
    $stmt->bind_param("i", $casoId);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    // Prepara la declaración DELETE una sola vez
    $deleteStmt = $conn->prepare("DELETE FROM archivos WHERE id = ?");

    while ($row = $result->fetch_assoc()) {
        if (!in_array($row['ruta'], $data['rutas'])) {
            $deleteStmt->bind_param("i", $row['id']);
            $deleteStmt->execute();
        }
    }

    $deleteStmt->close();

    // Mensaje de éxito
    echo json_encode(["message" => "Case updated successfully"]);
} else {
    echo json_encode(["error" => "No case data received"]);
}

// Cerrar conexión
$conn->close();
?>

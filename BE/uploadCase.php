<?php
include 'connection.php';

session_start();

// Verificamos si hay una sesión activa del usuario
if (empty($_SESSION['loggedin'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Obtenemos el usuario de la sesión activa
$user = $_SESSION['correo'];
$hashedCorreo = hash('sha256', $user);

// Consulta SQL para obtener el ID del usuario
$stmt = $conn->prepare("SELECT id FROM usuarios WHERE correo = ?");
$stmt->bind_param("s", $hashedCorreo);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $userId = $row['id'];
    $stmt->close();
}

// Decodificamos datos JSON recibidos
$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['nombreCaso'], $data['descripcion'], $data['motivoConsulta'])) {
    $nombreCaso     = $data['nombreCaso'];
    $claveCaso      = '';
    $descripcion    = $data['descripcion'];
    $motivoConsulta = $data['motivoConsulta'];
    $idCarpeta      = isset($data['idCarpeta']) && $data['idCarpeta'] !== '' 
                        ? intval($data['idCarpeta']) 
                        : null;

    // Se separa el query según si hay carpeta o no (para que NULL no se convierta en 0)
    if ($idCarpeta !== null) {
        $stmt = $conn->prepare("
            INSERT INTO casos 
                (nombre, clave, descripcion, motivo_consulta, id_usuario_creador, visibilidad, id_carpeta, fecha_actualizacion)
            VALUES (?, ?, ?, ?, ?, 1, ?, CURRENT_TIMESTAMP)
        ");
        $stmt->bind_param("ssssii", $nombreCaso, $claveCaso, $descripcion, $motivoConsulta, $userId, $idCarpeta);
    } else {
        $stmt = $conn->prepare("
            INSERT INTO casos 
                (nombre, clave, descripcion, motivo_consulta, id_usuario_creador, visibilidad, fecha_actualizacion)
            VALUES (?, ?, ?, ?, ?, 1, CURRENT_TIMESTAMP)
        ");
        $stmt->bind_param("ssssi", $nombreCaso, $claveCaso, $descripcion, $motivoConsulta, $userId);
    }

    if ($stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode([
            "success" => true,
            "message" => "Caso insertado correctamente",
            "idCaso"  => $stmt->insert_id
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            "success" => false,
            "error"   => "Error al insertar el caso: " . $stmt->error
        ]);
    }
    $stmt->close();
    exit();
}

// Insertamos perfil del paciente
if (isset($data['perfilPaciente'])) {
    $perfilPaciente = $data['perfilPaciente'];
    $idCaso = $perfilPaciente['idCaso']; // Obtenemos idCaso
    $response = ["success" => false, "message" => "Error desconocido"]; // Inicializamos respuesta

    // Primero, insertar los signos vitales
    if (
        isset($perfilPaciente['temperatura']) &&
        isset($perfilPaciente['tensionArterial']) &&
        isset($perfilPaciente['frecuenciaCardiaca']) &&
        isset($perfilPaciente['frecuenciaRespiratoria']) &&
        isset($perfilPaciente['saturacionOxigeno'])
    ) {
        // Obtener los datos del perfil del paciente
        $temperatura = floatval($perfilPaciente['temperatura']);  // Asegúrate de que sea un float
        $ta = $perfilPaciente['tensionArterial'];         // Convertir a entero
        $fc = intval($perfilPaciente['frecuenciaCardiaca']);      // Convertir a entero
        $fr = intval($perfilPaciente['frecuenciaRespiratoria']);  // Convertir a entero
        $sat = intval($perfilPaciente['saturacionOxigeno']);      // Convertir a entero
        
        // Comenzar transacción para asegurar la integridad de los datos
        $conn->begin_transaction();
        try {
            // Insertar paciente primero para obtener el ID
            $stmtPaciente = $conn->prepare("INSERT INTO pacientes (nombre, sexo, edad, peso, altura, enfermedades, id_caso) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmtPaciente->bind_param("ssssssi", 
                $perfilPaciente['nombrePaciente'], 
                $perfilPaciente['sexo'], 
                $perfilPaciente['edad'], 
                $perfilPaciente['peso'], 
                $perfilPaciente['altura'], 
                $perfilPaciente['enfermedades'], 
                $idCaso
            );
            
            if (!$stmtPaciente->execute()) {
                throw new Exception("Error al insertar paciente: " . $stmtPaciente->error);
            }
            
            $idPaciente = $stmtPaciente->insert_id;
            $stmtPaciente->close();
            
            // Ahora, inserta los signos vitales
            $ritmo = isset($perfilPaciente['ritmo']) ? $perfilPaciente['ritmo'] : 'sinusal';
            $stmtSignos = $conn->prepare("INSERT INTO signos_pacientes (FR, FC, TA, Temperatura, SAT, ritmo, id_paciente) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmtSignos->bind_param("iisdisi", $fr, $fc, $ta, $temperatura, $sat, $ritmo, $idPaciente);
            
            // Ejecutar la inserción
            if (!$stmtSignos->execute()) {
                throw new Exception("Error al insertar signos vitales: " . $stmtSignos->error);
            }
            
            $idSignos = $stmtSignos->insert_id;
            $stmtSignos->close();
            
            // Actualizar el paciente con el ID de signos vitales
            $stmtUpdate = $conn->prepare("UPDATE pacientes SET id_signos_vitales = ? WHERE id = ?");
            $stmtUpdate->bind_param("ii", $idSignos, $idPaciente);
            
            if (!$stmtUpdate->execute()) {
                throw new Exception("Error al actualizar paciente con signos vitales: " . $stmtUpdate->error);
            }
            
            $stmtUpdate->close();
            
            // Confirmar la transacción
            $conn->commit();
            
            $response = [
                "success" => true,
                "message" => "Paciente y signos vitales guardados correctamente",
                "idPaciente" => $idPaciente,
                "idSignos" => $idSignos
            ];
            
        } catch (Exception $e) {
            // Revertir la transacción en caso de error
            $conn->rollback();
            $response = [
                "success" => false,
                "error" => $e->getMessage()
            ];
        }
    } else {
        // Si faltan datos de signos vitales, solo insertar el paciente
        $stmtPaciente = $conn->prepare("INSERT INTO pacientes (nombre, sexo, edad, peso, altura, enfermedades, id_caso) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmtPaciente->bind_param("ssssssi", 
            $perfilPaciente['nombrePaciente'], 
            $perfilPaciente['sexo'], 
            $perfilPaciente['edad'], 
            $perfilPaciente['peso'], 
            $perfilPaciente['altura'], 
            $perfilPaciente['enfermedades'], 
            $idCaso
        );
        
        if ($stmtPaciente->execute()) {
            $idPaciente = $stmtPaciente->insert_id;
            $response = [
                "success" => true,
                "message" => "Paciente insertado, datos de signos vitales faltantes",
                "idPaciente" => $idPaciente
            ];
        } else {
            $response = [
                "success" => false,
                "error" => "Error al insertar paciente: " . $stmtPaciente->error
            ];
        }
        $stmtPaciente->close();
    }
    
    // Enviar respuesta
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Insertamos preguntas y sus opciones
if (isset($data['preguntas'])) {
    $response = ["success" => true, "message" => "Preguntas y opciones insertadas correctamente"];
    
    foreach ($data['preguntas'] as $pregunta) {
        $preguntaTexto = $pregunta['preguntaTexto'];
        $feedback = $pregunta['feedback'];
        $idCaso = $pregunta['idCaso'];

        $stmt = $conn->prepare("INSERT INTO preguntas (texto_pregunta, feedback, id_caso) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $preguntaTexto, $feedback, $idCaso);

        if ($stmt->execute()) {
            $idPregunta = $stmt->insert_id;

            // Insertamos opciones (incisos) para cada pregunta
            foreach ($pregunta['incisos'] as $inciso) {
                $texto = $inciso['texto'];
                $correcto = $inciso['correcto'] ? 1 : 0; // Asegurar que sea 1 o 0

                $stmtInciso = $conn->prepare("INSERT INTO incisos (id_pregunta, texto, correcto) VALUES (?, ?, ?)");
                $stmtInciso->bind_param("isi", $idPregunta, $texto, $correcto);
                $stmtInciso->execute();
                $stmtInciso->close();
            }
        } else {
            $response = [
                "success" => false, 
                "error" => "Error al insertar pregunta: " . $stmt->error
            ];
            break;
        }
        $stmt->close();
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Cerrar conexión
$conn->close();
?>

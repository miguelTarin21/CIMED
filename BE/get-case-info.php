<?php
// Conexión a la base de datos
include 'connection.php';

// Validar que el ID sea válido
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    echo json_encode(['success' => false, 'message' => 'ID inválido o no proporcionado.']);
    exit;
}

$id = intval($_GET['id']);
$response = [];

// Consultar la tabla de "casos"
$sqlCaso = "SELECT nombre, visibilidad, clave, descripcion, motivo_consulta,
            DATE_FORMAT(fecha_actualizacion, '%d/%m/%Y') as fecha_actualizacion
            FROM casos WHERE id = ?";
$stmtCaso = $conn->prepare($sqlCaso);
if (!$stmtCaso) {
    echo json_encode(['success' => false, 'message' => 'Error al preparar la consulta para casos.']);
    exit;
}
$stmtCaso->bind_param('i', $id);
$stmtCaso->execute();
$stmtCaso->bind_result($nombre, $visibilidad, $clave, $descripcion, $motivoConsulta, $fechaActualizacion);
if (!$stmtCaso->fetch()) {
    echo json_encode(['success' => false, 'message' => 'El caso no fue encontrado.']);
    exit;
}
$stmtCaso->close();

$response['caso'] = [
    'nombre'             => htmlspecialchars($nombre),
    'visibilidad'        => $visibilidad,
    'clave'              => htmlspecialchars($nombre),
    'descripcion'        => htmlspecialchars($descripcion),
    'motivoConsulta'     => htmlspecialchars($motivoConsulta),
    'fecha_actualizacion'=> $fechaActualizacion
];

// Consultar los videos del caso
$sqlVideos = "SELECT id, nombre, ruta FROM videos WHERE id_caso = ? ORDER BY id ASC";
$stmtVideos = $conn->prepare($sqlVideos);
$stmtVideos->bind_param('i', $id);
$stmtVideos->execute();
$resultVideos = $stmtVideos->get_result();
$videosArr = [];
while ($vrow = $resultVideos->fetch_assoc()) {
    $videosArr[] = [
        'id'     => $vrow['id'],
        'nombre' => htmlspecialchars($vrow['nombre']),
        'ruta'   => $vrow['ruta'],
    ];
}
$stmtVideos->close();
$response['videos'] = $videosArr;

// Consultar la tabla de "pacientes"
$sqlPaciente = "SELECT nombre, sexo, edad, peso, altura, enfermedades FROM pacientes WHERE id_caso = ?";
$stmtPaciente = $conn->prepare($sqlPaciente);
$stmtPaciente->bind_param('i', $id);
$stmtPaciente->execute();
$stmtPaciente->bind_result($nombre, $sexo, $edad, $peso, $altura, $enfermedades);
$stmtPaciente->fetch();
$stmtPaciente->close();

$response['paciente'] = [
    'nombre' => htmlspecialchars($nombre),
    'sexo' => $sexo,
    'edad' => $edad,
    'peso' => $peso,
    'altura' => $altura,
    'enfermedades' => htmlspecialchars($enfermedades),
];

// Obtener el id_paciente basado en el id_caso
$sqlCaso = "SELECT id FROM pacientes WHERE id_caso = ?";
$stmtCaso = $conn->prepare($sqlCaso);
$stmtCaso->bind_param('i', $id); // Asumiendo que $id_caso es el ID del caso
$stmtCaso->execute();
$stmtCaso->bind_result($id_paciente); 
$stmtCaso->fetch();
$stmtCaso->close();

// Consultar la tabla de "signos_pacientes" usando el id_paciente obtenido
$sqlPacienteSignos = "SELECT FR, FC, TA, Temperatura, SAT, ritmo FROM signos_pacientes WHERE id_paciente = ?";
$stmtPacienteSignos = $conn->prepare($sqlPacienteSignos);
$stmtPacienteSignos->bind_param('i', $id_paciente);
$stmtPacienteSignos->execute();
$stmtPacienteSignos->bind_result($FR, $FC, $TA, $Temperatura, $SAT, $ritmo);
$stmtPacienteSignos->fetch();
$stmtPacienteSignos->close();

// Almacenar los resultados en un arreglo de respuesta
$response['signos_paciente'] = [
    'FR' => $FR,
    'FC' => $FC,
    'TA' => $TA,
    'Temperatura' => $Temperatura,
    'SAT' => $SAT,
    'ritmo' => $ritmo
];

// Consultar la tabla de "preguntas" e "incisos"
$sqlPreguntas = "
SELECT
    p.id AS pregunta_id,
    p.texto_pregunta,
    p.feedback,
    i.id AS inciso_id,
    i.texto AS inciso_texto,
    i.correcto
FROM preguntas p
LEFT JOIN incisos i ON p.id = i.id_pregunta
WHERE p.id_caso = ?
ORDER BY p.id ASC, i.id ASC;
";

$stmtPreguntas = $conn->prepare($sqlPreguntas);
$stmtPreguntas->bind_param('i', $id);
$stmtPreguntas->execute();
$result = $stmtPreguntas->get_result();

$preguntas = [];
while ($row = $result->fetch_assoc()) {
    $preguntaId = $row['pregunta_id'];

    if (!isset($preguntas[$preguntaId])) {
        $preguntas[$preguntaId] = [
            'id' => $preguntaId,
            'texto_pregunta' => htmlspecialchars($row['texto_pregunta']),
            'feedback' => htmlspecialchars($row['feedback']),
            'incisos' => []
        ];
    }

    if (!is_null($row['inciso_id'])) {
        $preguntas[$preguntaId]['incisos'][] = [
            'id' => $row['inciso_id'],
            'texto' => htmlspecialchars($row['inciso_texto']),
            'correcto' => $row['correcto']
        ];
    }
}
$stmtPreguntas->close();
$response['preguntas'] = array_values($preguntas);

// Consultar la tabla de "archivos"
$sqlArchivos = "
SELECT
    archivos.id AS archivo_id,
    archivos.nombre AS archivo_nombre,
    archivos.ruta AS archivo_ruta
FROM archivos
WHERE archivos.id_caso = ?
ORDER BY archivos.nombre ASC;
";

$stmtArchivos = $conn->prepare($sqlArchivos);
$stmtArchivos->bind_param('i', $id);
$stmtArchivos->execute();
$resultArchivos = $stmtArchivos->get_result();

$archivos = [];
while ($row = $resultArchivos->fetch_assoc()) {
    $archivos[] = [
        'id' => $row['archivo_id'],
        'nombre' => htmlspecialchars($row['archivo_nombre']),
        'ruta' => $row['archivo_ruta'],
    ];
}
$stmtArchivos->close();
$response['archivos'] = $archivos;

// Enviar datos como JSON sin `print_r`
$response['success'] = true;
echo json_encode($response);

// Cerrar la conexión
$conn->close();
?>

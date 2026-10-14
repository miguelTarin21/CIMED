<?php
include 'connection.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['casoId'])) {
    echo json_encode(['success' => false, 'message' => 'ID de caso requerido']);
    exit;
}

$casoId   = intval($data['casoId']);
$fr       = isset($data['FR'])          ? intval($data['FR'])          : null;
$fc       = isset($data['FC'])          ? intval($data['FC'])          : null;
$ta       = isset($data['TA'])          ? $data['TA']                  : null;
$temp     = isset($data['Temperatura']) ? floatval($data['Temperatura']) : null;
$sat      = isset($data['SAT'])         ? intval($data['SAT'])         : null;
$ritmo    = isset($data['ritmo'])       ? $data['ritmo']               : 'sinusal';

// Obtener el id de signos_pacientes del caso
$stmt = $conn->prepare(
    "SELECT sp.id FROM signos_pacientes sp
     JOIN pacientes p ON sp.id_paciente = p.id
     WHERE p.id_caso = ? LIMIT 1"
);
$stmt->bind_param('i', $casoId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'No se encontraron signos vitales para este caso']);
    exit;
}

$signosId = $row['id'];

// Actualizar los signos vitales
$stmt = $conn->prepare(
    "UPDATE signos_pacientes SET FR = ?, FC = ?, TA = ?, Temperatura = ?, SAT = ?, ritmo = ? WHERE id = ?"
);
$stmt->bind_param('iisdisi', $fr, $fc, $ta, $temp, $sat, $ritmo, $signosId);

if ($stmt->execute()) {
    // Actualizar la fecha del caso
    $stmtFecha = $conn->prepare("UPDATE casos SET fecha_actualizacion = CURRENT_TIMESTAMP WHERE id = ?");
    $stmtFecha->bind_param('i', $casoId);
    $stmtFecha->execute();
    $stmtFecha->close();

    echo json_encode(['success' => true, 'message' => 'Signos vitales actualizados']);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}

$stmt->close();
$conn->close();
?>

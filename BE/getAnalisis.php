<?php 
// Conexión a la base de datos
include 'connection.php';

// Consultar estudios y clasificarlos
$sql = "SELECT id, nombre, tipo_estudio FROM estudios";
$result = $conn->query($sql);

$laboratorios = [];
$gabinete = [];

// Clasificar resultados en dos arrays
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        if ($row['tipo_estudio'] === 'Laboratorios') {
            $laboratorios[] = [
                'id' => $row['id'],
                'nombre' => $row['nombre']
            ];
        } elseif ($row['tipo_estudio'] === 'Gabinete') {
            $gabinete[] = [
                'id' => $row['id'],
                'nombre' => $row['nombre']
            ];
        }
    }
}

// Enviar datos como JSON sin `print_r`
echo json_encode([
    "laboratorios" => $laboratorios,
    "gabinete" => $gabinete
]);

$conn->close();
?>

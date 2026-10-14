<?php
// Conexion a la base de datos
include 'connection.php';

if (isset($_GET['id_caso'])) {
    $idCaso = intval($_GET['id_caso']);

    $sql = "SELECT archivos.id, archivos.nombre, archivos.ruta
            FROM archivos
            WHERE archivos.id_caso = ?
            ORDER BY archivos.id DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idCaso);
    $stmt->execute();

    $result = $stmt->get_result();
    $files = [];
    while ($row = $result->fetch_assoc()) {
        $files[] = [ // Cambiar a [] en lugar de usar claves
            'id' => $row['id'],       // Puedes incluir 'id' si lo necesitas
            'nombre' => $row['nombre'],
            'ruta' => $row['ruta']
        ];
    }
    echo json_encode($files); // Se devuelve un array
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['error' => 'Faltan parámetros']);
}
?>

<?php
include 'connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $hashedCorreo = hash('sha256', $_POST['id']);
    $currentKey = $_POST['currentKey'];
    $newKey = $_POST['newKey'];  

    // Consulta para obtener la clave actual del usuario
    $sql = "SELECT contraseña, clave_asignada FROM usuarios WHERE correo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $hashedCorreo);
    $stmt->execute();
    $stmt->bind_result($storedPasswordHash, $storedKeyHash);
    $stmt->fetch();
    $stmt->close();

    // Verificamos si la clave actual es correcta
    if (!password_verify($currentKey, $storedPasswordHash)) {
        echo json_encode(['success' => false, 'message' => 'La contraseña actual es incorrecta']);
        $conn->close();
        exit;
    }

    // Verificamos si la nueva clave es igual a la actual
    if ($currentKey == $newKey) {
        echo json_encode(['success' => false, 'message' => 'La clave no puede ser igual a la contraseña actual']);
        $conn->close();
        exit;
    }

    // Hasheamos la nueva clave para comparación
    $hashedNewKey = hash('sha256', $newKey);

    // Verificamos si la nueva clave ya existe en la base de datos
    $checkSql = "SELECT COUNT(*) FROM usuarios WHERE clave_asignada = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param('s', $hashedNewKey);
    $checkStmt->execute();
    $checkStmt->bind_result($keyExists);
    $checkStmt->fetch();
    $checkStmt->close();

    if ($keyExists > 0) {
        echo json_encode(['success' => false, 'message' => 'Esta clave ya está en uso. Por favor, elija otra']);
        $conn->close();
        exit;
    }

    // Actualizar la clave en la base de datos
    $updateSql = "UPDATE usuarios SET clave_asignada = ? WHERE correo = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param('ss', $hashedNewKey, $hashedCorreo);

    if ($updateStmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Clave actualizada con éxito']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar la clave']);
    }

    $updateStmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>
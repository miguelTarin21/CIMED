<?php
include 'connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hashedCorreo = hash('sha256', $_POST['id']);
    $currentPassword = $_POST['currentPassword'];
    $newPassword = $_POST['newPassword'];

    // Consulta para obtener la contraseña actual del usuario
    $sql = "SELECT contraseña FROM usuarios WHERE correo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $hashedCorreo);
    $stmt->execute();
    $stmt->bind_result($storedPasswordHash);
    $stmt->fetch();
    $stmt->close();

    // Verificar si la contraseña actual es correcta
    if (!password_verify($currentPassword, $storedPasswordHash)) {
        echo json_encode(['success' => false, 'message' => 'La contraseña actual es incorrecta']);
        $conn->close();
        exit;
    }

    // Hashear la nueva contraseña
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    // Actualizar la contraseña en la base de datos
    $updateSql = "UPDATE usuarios SET contraseña = ? WHERE correo = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param('ss', $hashedPassword, $hashedCorreo);

    if ($updateStmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar la contraseña']);
    }

    $updateStmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>
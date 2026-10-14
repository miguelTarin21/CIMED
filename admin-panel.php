<?php
include './BE/connection.php';
session_start();

// Verificamos si hay una sesión activa del usuario
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    // Si no hay una sesión activa, redirigimos inmediatamente a la página de login
    header('Location: ./');
    exit;
}

// Obtenemos el usuario de la sesión activa
$user = $_SESSION['correo'];
$hashedCorreo = hash('sha256', $user);

// Consulta SQL para obtener el ID del usuario
$stmt = $conn->prepare("SELECT id FROM usuarios WHERE correo = ?");
$stmt->bind_param("s", $hashedCorreo);  // Comparamos el correo hasheado
$stmt->execute();
$result = $stmt->get_result();

// Variable para almacenar los casos
$casesResult = null;

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $userId = $row['id'];
    
    // Cerramos la primera consulta antes de hacer la segunda
    $stmt->close();
    
    // Consulta SQL para obtener los casos clínicos del usuario con su carpeta
    $sql = "
        SELECT c.id, c.nombre, c.descripcion, c.visibilidad, c.id_carpeta,
               cp.nombre AS nombre_carpeta,
               DATE_FORMAT(c.fecha_actualizacion, '%d/%m/%Y') AS fecha_actualizacion
        FROM casos c
        LEFT JOIN carpetas cp ON c.id_carpeta = cp.id
        WHERE c.id_usuario_creador = ?
        ORDER BY cp.nombre ASC, c.nombre ASC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $casesResult = $stmt->get_result();

    // Agrupar casos por carpeta
    $carpetaMap   = [];  // id_carpeta => ['nombre' => ..., 'casos' => [...]]
    $sinCarpeta   = [];  // casos sin carpeta asignada

    if ($casesResult && $casesResult->num_rows > 0) {
        while ($row = $casesResult->fetch_assoc()) {
            $casoDato = [
                'id'                  => $row['id'],
                'nombre'              => $row['nombre'],
                'descripcion'         => $row['descripcion'],
                'fecha_actualizacion' => $row['fecha_actualizacion'],
                'visibilidad'         => $row['visibilidad']
            ];

            if ($row['id_carpeta']) {
                $cid = $row['id_carpeta'];
                if (!isset($carpetaMap[$cid])) {
                    $carpetaMap[$cid] = [
                        'nombre' => $row['nombre_carpeta'],
                        'casos'  => []
                    ];
                }
                $carpetaMap[$cid]['casos'][] = $casoDato;
            } else {
                $sinCarpeta[] = $casoDato;
            }
        }
    }
}

// Verifica si el usuario está logeado
if (isset($_SESSION['correo'])) {
    $userCorreo = $_SESSION['correo'];
    $hashedCorreo = hash('sha256', $userCorreo); // Hashea el correo igual que arriba
    
    
    // Consulta para obtener el valor de superadmin
    $stmt = $conn->prepare("SELECT superadmin FROM usuarios WHERE correo = ?");
    $stmt->bind_param("s", $hashedCorreo); // Usa el correo hasheado
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Verifica si la consulta retorna algún usuario
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        // Asigna el valor de superadmin
        $isSuperAdmin = $user['superadmin'] == 1;
    } else {
        $isSuperAdmin = false;  // Si no hay resultado, no es superadmin
    }
    
   
    
} else {
    $isSuperAdmin = false;
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CIMED - Administrador</title>
    <link rel="icon" type="image/png" href="images/CimarronMedicoIndexIcon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/admin-panel-style.css" />
    <link rel="stylesheet" href="css/index-style.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Sliders de signos vitales */
        .vitals-sliders-section {
            background: #f8f9ff;
            border-radius: 12px;
            padding: 18px 20px;
            border: 1px solid #e2e8ff;
            margin-bottom: 16px;
        }
        .vital-slider-row { padding: 4px 0; }
        .vital-range {
            width: 100%;
            accent-color: #2f50ff;
            cursor: pointer;
        }
        .vital-badge {
            background: #2f50ff;
            color: white;
            border-radius: 20px;
            padding: 2px 12px;
            font-size: 0.88rem;
            font-weight: 600;
            min-width: 90px;
            text-align: center;
            transition: background 0.3s;
        }
        .vital-badge.warning  { background: #fd7e14; }
        .vital-badge.critical { background: #dc3545; }
        .vital-badge.normal   { background: #198754; }
        #realtime-status.saving  { color: #fd7e14; }
        #realtime-status.saved   { color: #198754; }
        #realtime-status.error   { color: #dc3545; }

        /* Estilos para las carpetas */
        .folder-section { margin-bottom: 2rem; }
        .folder-header {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            background: linear-gradient(90deg,#e8f4ef,#dce8ff);
            border-radius: 10px;
            padding: 12px 18px;
            margin-bottom: 12px;
            border: 1px solid #c5d7ef;
            user-select: none;
            transition: background 0.2s;
        }
        .folder-header:hover { background: linear-gradient(90deg,#d4edd8,#ccdcf7); }
        .folder-header i.folder-icon { font-size: 1.4rem; color: #2f50ff; }
        .folder-header .folder-name { font-weight: 600; font-size: 1.1rem; color: #222; flex:1; }
        .folder-header .folder-count { font-size: 0.85rem; color: #666; }
        .folder-header .chevron { font-size: 1rem; color: #555; transition: transform 0.3s; }
        .folder-header.collapsed .chevron { transform: rotate(-90deg); }
        .folder-cases { padding-left: 10px; }
        .section-sin-carpeta .folder-header { background: linear-gradient(90deg,#f4f4f4,#eaeaea); border-color:#ddd; }
        .section-sin-carpeta .folder-header i.folder-icon { color:#aaa; }
    </style>

</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="./">
                <span class="brand-name">
                    <span class="brand-ci">CI</span><span class="brand-med">MED</span>
                </span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-center" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="admin-panel" style="margin-left: 130px;">Panel Administrador</a>
                    </li>
                </ul>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalNuevaCarpeta" style="background: linear-gradient(90deg,#00723e,#1a8f5a); border-radius: 50px; font-weight: 600; border: none; padding: 8px 22px;">
                    <i class="bi bi-folder-plus me-1"></i> Nueva carpeta
                </button>
                <a href="./BE/logout.php" class="btn btn-primary btn-exit d-flex align-items-center">
                    <i class="bi bi-box-arrow-left me-2"></i> Salir
                </a>
            </div>
        </div>
    </nav>

    <!-- Main -->
    <main>
        <div class="container my-4">

            <!-- Botones del admin -->
            <div class="d-flex justify-content-end mb-3">
                <?php if ($isSuperAdmin): ?>
                    <button class="btn btn-secondary person-add-btn me-2" data-id="<?php echo $_SESSION['correo']; ?>" title= "Añadir Usuario">
                        <i class="bi bi-person-add"></i>
                    </button>
                <?php endif; ?>
                <button class="btn btn-secondary key-btn me-2" data-id="<?php echo $_SESSION['correo']; ?>" title="Gestionar Claves">
                    <i class="bi bi-key"></i>        
                </button>
                <button class="btn btn-secondary gear-btn me-2" data-id="<?php echo $_SESSION['correo']; ?>" title="Configuración">
                    <i class="bi bi-gear"></i>
                </button>
                <a href="case_form.html" id="caseFormLink">
                    <button class="btn btn-primary" id="btnAgregarCaso">Agregar caso</button>
                </a>
            </div>

            <!-- Lista de casos clínicos agrupados -->
            <div class="case-list-grouped">
                <?php
                $hayAlgo = !empty($carpetaMap) || !empty($sinCarpeta);

                if (!$hayAlgo):
                ?>
                    <div class="text-center py-5">
                        <i class="bi bi-folder2-open" style="font-size:3rem;color:#aaa;"></i>
                        <p class="mt-3 text-muted">No hay casos clínicos disponibles.</p>
                    </div>
                <?php else: ?>

                    <!-- Carpetas con casos -->
                    <?php foreach ($carpetaMap as $cid => $carpeta): ?>
                    <div class="folder-section">
                        <div class="folder-header" onclick="toggleFolder('folder-<?= $cid ?>', this)">
                            <i class="bi bi-folder-fill folder-icon"></i>
                            <span class="folder-name"><?= htmlspecialchars($carpeta['nombre']) ?></span>
                            <span class="folder-count"><?= count($carpeta['casos']) ?> caso(s)</span>
                            <i class="bi bi-chevron-down chevron"></i>
                        </div>
                        <div class="folder-cases" id="folder-<?= $cid ?>">
                            <?php foreach ($carpeta['casos'] as $row): ?>
                                <div class="card case-card mb-3">
                                    <div class="card-body position-relative">
                                        
                                        <div class="position-absolute top-0 end-0 p-2" style="font-size: 0.8rem; color: #6c757d;">
                                            Ultima Actualización: &nbsp<i class="bi bi-calendar-check me-1"></i><?= $row["fecha_actualizacion"] ?>
                                        </div>
                                        
                                        <h5 class="card-title" style="font-size: 1.5rem; font-weight: 700; color: #343a40; padding-right: 80px;"><?= htmlspecialchars($row["nombre"]) ?></h5>
                                        <p class="card-text"><?= htmlspecialchars($row["descripcion"]) ?></p>
                                        <div class="d-flex justify-content-end">
                                            <button class="btn btn-warning edit-case-btn me-2" data-id="<?= $row["id"] ?>" title="Editar"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-danger delete-case-btn me-2" data-id="<?= $row["id"] ?>" title="Borrar"><i class="bi bi-trash"></i></button>
                                            <?php if($row["visibilidad"] == 0): ?>
                                                <button class="btn btn-secondary toggle-visibility" data-id="<?= $row["id"] ?>" data-visibilidad="<?= $row["visibilidad"] ?>" title="Visibilidad"><i class="bi bi-eye-slash"></i></button>
                                            <?php else: ?>
                                                <button class="btn btn-secondary toggle-visibility" data-id="<?= $row["id"] ?>" data-visibilidad="<?= $row["visibilidad"] ?>" title="Visibilidad"><i class="bi bi-eye-fill"></i></button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <!-- Casos sin carpeta -->
                    <?php if (!empty($sinCarpeta)): ?>
                    <div class="folder-section section-sin-carpeta">
                        <div class="folder-header" onclick="toggleFolder('folder-sin-carpeta', this)">
                            <i class="bi bi-folder2 folder-icon"></i>
                            <span class="folder-name">Sin carpeta</span>
                            <span class="folder-count"><?= count($sinCarpeta) ?> caso(s)</span>
                            <i class="bi bi-chevron-down chevron"></i>
                        </div>
                        <div class="folder-cases" id="folder-sin-carpeta">
                            <?php foreach ($sinCarpeta as $row): ?>
                                <div class="card case-card mb-3">
                                    <div class="card-body position-relative">
                                        
                                        <div class="position-absolute top-0 end-0 p-2" style="font-size: 0.8rem; color: #6c757d;">
                                            Ultima Actualización: &nbsp<i class="bi bi-calendar-check me-1"></i><?= $row["fecha_actualizacion"] ?>
                                        </div>
                                        
                                        <h5 class="card-title" style="font-size: 1.5rem; font-weight: 700; color: #343a40; padding-right: 80px;"><?= htmlspecialchars($row["nombre"]) ?></h5>
                                        <p class="card-text"><?= htmlspecialchars($row["descripcion"]) ?></p>
                                        <div class="d-flex justify-content-end">
                                            <button class="btn btn-warning edit-case-btn me-2" data-id="<?= $row["id"] ?>" title="Editar"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-danger delete-case-btn me-2" data-id="<?= $row["id"] ?>" title="Borrar"><i class="bi bi-trash"></i></button>
                                            <?php if($row["visibilidad"] == 0): ?>
                                                <button class="btn btn-secondary toggle-visibility" data-id="<?= $row["id"] ?>" data-visibilidad="<?= $row["visibilidad"] ?>" title="Visibilidad"><i class="bi bi-eye-slash"></i></button>
                                            <?php else: ?>
                                                <button class="btn btn-secondary toggle-visibility" data-id="<?= $row["id"] ?>" data-visibilidad="<?= $row["visibilidad"] ?>" title="Visibilidad"><i class="bi bi-eye-fill"></i></button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                <?php endif; 

                // Cerrar la sentencia y la conexión si aún están abiertas
                if (isset($stmt) && $stmt) {
                    $stmt->close();
                }
                $conn->close();
                ?>

                </div>
            </div>
        </div>

    </main>

    <!-- Modal de Confirmación de Eliminación -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirmar eliminación</h5>
                    <!-- <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> -->
                </div>
                <div class="modal-body">
                    ¿Estás seguro de que deseas eliminar este caso clínico?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Eliminar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para registrar un nuevo administrador -->
    <div class="modal fade" id="registerAdminModal" tabindex="-1" aria-labelledby="registerAdminModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="registerAdminModalLabel">Registrar nuevo administrador</h5>
                    <!-- <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> -->
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="adminEmail" class="form-label">Correo electrónico</label>
                        <input type="email" class="form-control" id="adminEmail" name="adminEmail" required>
                    </div>
                    <div id="emailFeedback" class="invalid-feedback d-none">
                        Por favor ingrese un correo electrónico válido.
                    </div>
                    <div class="alert alert-info mt-3">
                        Se enviará un enlace de registro al correo proporcionado.
                    </div>
                    <div class="d-flex justify-content-center mb-1">
                        <button type="button" class="btn btn-primary" id="sendInvitationBtn">Enviar invitación</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para actualizar la clave del usuario -->
    <div class="modal fade" id="updateKeyModal" tabindex="-1" aria-labelledby="updateKeyModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content" id="modalContentUpdateKey">
                <div class="modal-header">
                    <h5 class="modal-title" id="updatePasswordModalLabel">Cambiar clave </h5>
                    <!-- <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> -->
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="currentPassword" class="form-label">Contraseña actual</label>
                        <input type="password" class="form-control" id="currentKey" name="currentPassword" required>
                    </div>
                    <div class="mb-3">
                        <label for="newKey" class="form-label">Nueva clave</label>
                        <input type="password" class="form-control" id="newKey" name="newKey" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirmKey" class="form-label">Confirmar clave</label>
                        <input type="password" class="form-control" id="confirmKey" name="confirmKey" required>
                    </div>
                    <div  class="d-flex justify-content-center mb-1">
                    <button type="button" class="btn btn-primary" id="UpdateKeyBtn">Actualizar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para actualizar la contraseña del usuario -->
    <div class="modal fade" id="updatePasswordModal" tabindex="-1" aria-labelledby="updatePasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updatePasswordModalLabel">Actualizar contraseña</h5>
                    <!-- <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> -->
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="currentPassword" class="form-label">Contraseña actual</label>
                        <input type="password" class="form-control" id="currentPassword" name="currentPassword" required>
                    </div>
                    <div class="mb-3">
                        <label for="newPassword" class="form-label">Nueva contraseña</label>
                        <input type="password" class="form-control" id="newPassword" name="newPassword" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirmPassword" class="form-label">Confirmar contraseña</label>
                        <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
                    </div>
                    <div  class="d-flex justify-content-center mb-1">
                    <button type="button" class="btn btn-primary" id="UpdatePasswordBtn">Actualizar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para actualizar la información de un caso -->
    <div class="modal fade" id="updateCaseModal" tabindex="-1" aria-labelledby="updateCaseModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateCaseModalLabel">Actualizar caso clínico</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Pestañas -->
                    <ul class="nav nav-tabs" id="caseTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="patient-tab" data-bs-toggle="tab" data-bs-target="#patient" type="button" role="tab" aria-controls="patient" aria-selected="false">Paciente</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button" role="tab" aria-controls="info" aria-selected="true">Info del Caso</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="studies-tab" data-bs-toggle="tab" data-bs-target="#studies" type="button" role="tab" aria-controls="studies" aria-selected="false">Estudios</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="questionnaire-tab" data-bs-toggle="tab" data-bs-target="#questionnaire" type="button" role="tab" aria-controls="questionnaire" aria-selected="false">Cuestionario</button>
                        </li>
                    </ul>
                    <!-- Contenido de las pestañas -->
                    <div class="tab-content mt-3" id="caseTabsContent">

                        
                    <!-- Paciente -->
                    <div class="tab-pane fade show active" id="patient" role="tabpanel" aria-labelledby="patient-tab">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="patientName" class="form-label">Nombre del paciente</label>
                                <input type="text" class="form-control" id="patientName" name="patientName" required>
                            </div>
                            <div class="col-md-6">
                                <label for="patientGender" class="form-label">Género</label>
                                <select class="form-select" id="patientGender" name="patientGender" required>
                                    <option value="" disabled selected hidden></option>
                                    <option value="Hombre">Hombre</option>
                                    <option value="Mujer">Mujer</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="patientAge" class="form-label">Edad del paciente</label>
                                <input type="number" class="form-control" id="patientAge" name="patientAge" required>
                            </div>
                            <div class="col-md-6">
                                <label for="patientWeight" class="form-label">Peso del paciente (kg)</label>
                                <input type="number" step="0.1" class="form-control" id="patientWeight" name="patientWeight" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="patientHeight" class="form-label">Altura del paciente (cm)</label>
                                <input type="number" step="0.01" class="form-control" id="patientHeight" name="patientHeight" required>
                            </div>
                        </div>

                        <!-- ── Signos vitales con sliders ── -->
                        <div class="vitals-sliders-section">
                            <h6 class="fw-bold mb-3" style="color:#2f50ff;border-bottom:2px solid #dce0f3;padding-bottom:6px;">
                                <i class="bi bi-activity me-1"></i> Signos Vitales (actualizan en tiempo real)
                            </h6>

                            <!-- FR -->
                            <div class="vital-slider-row mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label mb-0 fw-semibold">
                                        <i class="bi bi-lungs me-1" style="color:#17a2b8"></i>
                                        Frecuencia Respiratoria
                                    </label>
                                    <span class="vital-badge" id="fr-badge">
                                        <span id="fr-display">--</span> rpm
                                    </span>
                                </div>
                                <input type="range" class="form-range vital-range" id="patientFr"
                                       min="0" max="60" step="1" value="16">
                                <div class="d-flex justify-content-between text-muted" style="font-size:0.75rem;">
                                    <span>0</span><span style="color:#198754">Normal: 12–20</span><span>60</span>
                                </div>
                            </div>

                            <!-- FC -->
                            <div class="vital-slider-row mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label mb-0 fw-semibold">
                                        <i class="bi bi-heart-pulse me-1" style="color:#dc3545"></i>
                                        Frecuencia Cardíaca
                                    </label>
                                    <span class="vital-badge" id="fc-badge">
                                        <span id="fc-display">--</span> lpm
                                    </span>
                                </div>
                                <input type="range" class="form-range vital-range" id="patientFc"
                                       min="0" max="250" step="1" value="75">
                                <div class="d-flex justify-content-between text-muted" style="font-size:0.75rem;">
                                    <span>0</span><span style="color:#198754">Normal: 60–100</span><span>250</span>
                                </div>
                            </div>

                            <!-- TA (dos sliders: sistólica y diastólica) -->
                            <div class="vital-slider-row mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label mb-0 fw-semibold">
                                        <i class="bi bi-droplet-half me-1" style="color:#6f42c1"></i>
                                        Tensión Arterial
                                    </label>
                                    <span class="vital-badge" id="ta-badge">
                                        <span id="ta-sis-display">--</span>/<span id="ta-dia-display">--</span> mmHg
                                    </span>
                                </div>
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <small class="text-muted" style="min-width:70px;">Sistólica</small>
                                    <input type="range" class="form-range vital-range" id="patientTaSis"
                                           min="60" max="220" step="1" value="120">
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <small class="text-muted" style="min-width:70px;">Diastólica</small>
                                    <input type="range" class="form-range vital-range" id="patientTaDia"
                                           min="40" max="140" step="1" value="80">
                                </div>
                                <!-- Campo oculto para la TA combinada -->
                                <input type="hidden" id="patientTa" value="120/80">
                                <div class="d-flex justify-content-between text-muted mt-1" style="font-size:0.75rem;">
                                    <span></span><span style="color:#198754">Normal: &lt;120/80</span><span></span>
                                </div>
                            </div>

                            <!-- Temperatura -->
                            <div class="vital-slider-row mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label mb-0 fw-semibold">
                                        <i class="bi bi-thermometer-half me-1" style="color:#fd7e14"></i>
                                        Temperatura
                                    </label>
                                    <span class="vital-badge" id="temp-badge">
                                        <span id="temp-display">--</span> °C
                                    </span>
                                </div>
                                <input type="range" class="form-range vital-range" id="patientTemp"
                                       min="34" max="43" step="0.1" value="37.0">
                                <div class="d-flex justify-content-between text-muted" style="font-size:0.75rem;">
                                    <span>34°C</span><span style="color:#198754">Normal: 36.1–37.2</span><span>43°C</span>
                                </div>
                            </div>

                            <!-- SAT -->
                            <div class="vital-slider-row mb-2">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label mb-0 fw-semibold">
                                        <i class="bi bi-moisture me-1" style="color:#20c997"></i>
                                        Saturación de Oxígeno
                                    </label>
                                    <span class="vital-badge" id="sat-badge">
                                        <span id="sat-display">--</span> %
                                    </span>
                                </div>
                                <input type="range" class="form-range vital-range" id="patientSat"
                                       min="0" max="100" step="1" value="98">
                                <div class="d-flex justify-content-between text-muted" style="font-size:0.75rem;">
                                    <span>0%</span><span style="color:#198754">Normal: 95–100%</span><span>100%</span>
                                </div>
                            </div>

                            <!-- Indicador de estado en tiempo real -->
                            <div id="realtime-status" class="mt-2" style="font-size:0.82rem;color:#888;text-align:right;">
                                <i class="bi bi-circle-fill me-1" style="color:#ccc;font-size:0.6rem;"></i>
                                Mueve un slider para guardar en tiempo real
                            </div>
                        </div>

                        <!-- ── Ritmos cardíacos ── -->
                        <div class="mt-3 p-3" style="background:#f0f4ff;border-radius:10px;border:1px solid #dce0f3;">
                            <h6 class="fw-bold mb-2" style="color:#2f50ff;font-size:.85rem;">
                                <i class="bi bi-activity me-1"></i> Ritmos Cardíacos
                            </h6>
                            <div class="d-flex flex-wrap gap-2" id="rhythm-btn-group">
                                <button type="button" class="btn-rhythm-admin" id="adm-rhythm-sinusal"
                                    onclick="applyRhythmAdmin('sinusal')"
                                    style="background:#e8f5e9;color:#198754;border:1px solid #198754;border-radius:6px;padding:5px 12px;font-size:.78rem;font-weight:600;cursor:pointer;">
                                    ♡ Ritmo Sinusal
                                </button>
                                <button type="button" class="btn-rhythm-admin" id="adm-rhythm-taquicardia"
                                    onclick="applyRhythmAdmin('taquicardia')"
                                    style="background:#fff8e1;color:#fd7e14;border:1px solid #fd7e14;border-radius:6px;padding:5px 12px;font-size:.78rem;font-weight:600;cursor:pointer;">
                                    ⚡ Taquicardia
                                </button>
                                <button type="button" class="btn-rhythm-admin" id="adm-rhythm-asistole"
                                    onclick="applyRhythmAdmin('asistole')"
                                    style="background:#fdecea;color:#dc3545;border:1px solid #dc3545;border-radius:6px;padding:5px 12px;font-size:.78rem;font-weight:600;cursor:pointer;">
                                    ✕ Asistole
                                </button>
                                <button type="button" class="btn-rhythm-admin" id="adm-rhythm-fv"
                                    onclick="applyRhythmAdmin('fv')"
                                    style="background:#fdecea;color:#dc3545;border:1px solid #dc3545;border-radius:6px;padding:5px 12px;font-size:.78rem;font-weight:600;cursor:pointer;">
                                    〜 Fibrilación Ventricular
                                </button>
                                <button type="button" class="btn-rhythm-admin" id="adm-rhythm-st_depresion"
                                    onclick="applyRhythmAdmin('st_depresion')"
                                    style="background:#fff3e0;color:#e65100;border:1px solid #e65100;border-radius:6px;padding:5px 12px;font-size:.78rem;font-weight:600;cursor:pointer;">
                                    ↓ ST Depresión
                                </button>
                                <button type="button" class="btn-rhythm-admin" id="adm-rhythm-st_elevacion"
                                    onclick="applyRhythmAdmin('st_elevacion')"
                                    style="background:#fdecea;color:#c62828;border:1px solid #c62828;border-radius:6px;padding:5px 12px;font-size:.78rem;font-weight:600;cursor:pointer;">
                                    ↑ ST Elevación
                                </button>
                            </div>
                            <div id="rhythm-feedback" style="font-size:.75rem;color:#888;margin-top:6px;"></div>
                        </div>

                        <div class="mb-3">
                            <label for="patientEnfermedades" class="form-label">Antecedentes de importancia del paciente</label>
                            <textarea class="form-control" id="patientEnfermedades" name="patientEnfermedades" rows="3" required></textarea>
                        </div>
                    </div>


                        <!-- Info del Caso -->
                        <div class="tab-pane fade" id="info" role="tabpanel" aria-labelledby="info-tab">
                            <div class="mb-3">
                                <label for="caseName" class="form-label">Nombre del caso</label>
                                <input type="text" class="form-control" id="caseName" name="caseName" required>
                            </div>
                            <div class="mb-3">
                                <label for="caseMotivo" class="form-label">Motivo de consulta</label>
                                <!-- <textarea class="form-control" id="caseMotivo" name="caseMotivo" rows="3" required></textarea> -->
                                <input type="text" class="form-control" id="caseMotivo" name="caseMotivo" required>
                            </div>
                            <div class="mb-3">
                                <label for="caseDescription" class="form-label">Descripción del caso</label>
                                <textarea class="form-control" id="caseDescription" name="caseDescription" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="caseVisibility" class="form-label">Visibilidad del caso</label>
                                <select class="form-select" id="caseVisibility" name="caseVisibility" required>
                                    <option value="1">Visible</option>
                                    <option value="0">Oculto</option>
                                </select>
                            </div>
                            <!-- Videos del caso (múltiples) -->
                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-camera-video me-1" style="color:#2f50ff;"></i>
                                    Videos del caso <span class="text-muted fw-normal">(opcional)</span>
                                </label>
                                <!-- Lista de videos existentes (cargada dinámicamente) -->
                                <div id="videos-existentes-container" class="mb-2"></div>
                                <!-- Nuevos videos a agregar -->
                                <div id="videos-nuevos-container"></div>
                                <button type="button" id="btn-agregar-video-edit" class="btn btn-sm mt-1"
                                    style="background:linear-gradient(90deg,#2f50ff,#4a6aff);color:white;border:none;border-radius:8px;padding:7px 16px;font-size:0.85rem;font-weight:600;">
                                    <i class="bi bi-plus-circle me-1"></i> Agregar video
                                </button>
                            </div>
                        </div>

                        <!-- Estudios -->
                        <div class="tab-pane fade" id="studies" role="tabpanel" aria-labelledby="studies-tab">
                            <div id="container-estudios">
                                <h5 class="fw-bold">Estudios Complementarios</h5>
                                <p>Agregue, modifique o elimine estudios según lo requiera.</p>

                                <div id="studies-container" class="studies-container">
                                    <!-- Aquí se añadirán dinámicamente los campos de carga de estudios -->
                                    <div class="study-item">
                                        <div class="input-group">
                                            <input type="text" id="nombre-estudio" required>
                                            <label for="nombre-estudio" class="form-label">Nombre del estudio</label>
                                            <input type="file" class="form-control" accept=".pdf, .jpeg, .jpg">
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-center">
                                    <button type="button" id="add-study" class="btn btn-add-study btn-success">Agregar estudio</button>
                                </div>
                            </div>
                        </div>

                        <!-- Cuestionario -->
                        <div class="tab-pane fade" id="questionnaire" role="tabpanel" aria-labelledby="questionnaire-tab">
                            <div id="preguntas-container" class="mt-3">
                                <!-- Aquí se añadirán dinámicamente las preguntas -->
                            </div>
                            <div class="d-flex justify-content-between mt-4">
                                <button type="button" class="btn btn-danger" id="remove-question">
                                    <i class="fa-solid fa-trash"></i> Eliminar última pregunta
                                </button>
                                <button type="button" class="btn btn-success" id="add-question">
                                    <i class="fa-solid fa-plus"></i> Agregar nueva pregunta
                                </button>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="modal-footer">
                    <div id="container-mensaje-alerta" class="d-flex align-items-center justify-content-center w-100">
                        <!-- Aqui va el mensaje -->
                    </div>
                    <button type="button" class="btn btn-secondary" id="closeUpdateBtn" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" id="updateCaseBtn">Actualizar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nueva Carpeta -->
    <div class="modal fade" id="modalNuevaCarpeta" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-folder-plus me-2"></i>Nueva carpeta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nombre de la carpeta</label>
                        <input type="text" class="form-control" id="inputNombreCarpeta"
                               placeholder="Ej: Cardiovasculares, Respiratorios…" maxlength="100">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Descripción <span class="text-muted">(opcional)</span></label>
                        <textarea class="form-control" id="inputDescCarpeta" rows="2"
                                  placeholder="Descripción breve de la categoría…" maxlength="500"></textarea>
                    </div>
                    <div id="carpetaFeedback"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="btnCrearCarpeta">
                        <i class="bi bi-folder-check me-1"></i> Crear carpeta
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer mt-auto py-3 bg-light">
        <div class="container text-center">
            <span class="text-muted">Cuarta Generación de Ingeniería en Software © 2024</span>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/toggle-visibility.js?v=<?= time() ?>"></script>
    <script src="js/delete-case.js?v=<?= time() ?>"></script>
    <script src="js/password-update.js?v=<?= time() ?>"></script>
    <script src="js/key-update.js?v=<?= time() ?>"></script>
    <script src="js/update-case.js?v=<?= time() ?>"></script>
    <script src="js/admin-register.js?v=<?= time() ?>"></script>

    <script>
        // Expandir / contraer carpetas
        function toggleFolder(id, header) {
            const el = document.getElementById(id);
            const isHidden = el.style.display === 'none';
            el.style.display = isHidden ? '' : 'none';
            header.classList.toggle('collapsed', !isHidden);
        }

        // Crear nueva carpeta
        const btnCrearCarpeta = document.getElementById('btnCrearCarpeta');
        if (btnCrearCarpeta) {
            btnCrearCarpeta.addEventListener('click', async function () {
                const nombre = document.getElementById('inputNombreCarpeta').value.trim();
                const descripcion = document.getElementById('inputDescCarpeta').value.trim();
                const feedback = document.getElementById('carpetaFeedback');

                if (!nombre) {
                    feedback.innerHTML = '<div class="alert alert-warning py-2">El nombre de la carpeta es requerido.</div>';
                    return;
                }

                this.disabled = true;
                feedback.innerHTML = '<div class="alert alert-info py-2">Creando carpeta…</div>';

                try {
                    const res = await fetch('./BE/create_carpeta.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ nombre, descripcion })
                    });
                    const data = await res.json();

                    if (data.success) {
                        feedback.innerHTML = '<div class="alert alert-success py-2">¡Carpeta creada exitosamente!</div>';
                        setTimeout(() => location.reload(), 1200);
                    } else {
                        feedback.innerHTML = `<div class="alert alert-danger py-2">${data.message || data.error}</div>`;
                        this.disabled = false;
                    }
                } catch (err) {
                    feedback.innerHTML = '<div class="alert alert-danger py-2">Error de red. Intenta de nuevo.</div>';
                    this.disabled = false;
                }
            });
        }
    </script>

    <script>
    // ── Presets de ritmo ──────────────────────────────────────
    const RHYTHM_PRESETS_ADMIN = {
        sinusal:      { FC: 72,  FR: 16,  TA_sis: 120, TA_dia: 80,  label: 'Ritmo Sinusal',          color: '#198754' },
        taquicardia:  { FC: 150, FR: 20,  TA_sis: 130, TA_dia: 85,  label: 'Taquicardia',            color: '#fd7e14' },
        asistole:     { FC: 0,   FR: 0,   TA_sis: 60,  TA_dia: 40,  label: 'Asistole',               color: '#dc3545' },
        fv:           { FC: 0,   FR: 4,   TA_sis: 60,  TA_dia: 40,  label: 'Fibrilación Ventricular', color: '#dc3545' },
        st_depresion: { FC: 72,  FR: 18,  TA_sis: 140, TA_dia: 90,  label: 'ST Depresión',           color: '#e65100' },
        st_elevacion: { FC: 90,  FR: 22,  TA_sis: 160, TA_dia: 100, label: 'ST Elevación',           color: '#c62828' },
    };

    function applyRhythmAdmin(rhythm) {
        window.currentRhythm = rhythm;
        const preset = RHYTHM_PRESETS_ADMIN[rhythm];
        if (!preset) return;

        // Aplicar valores a los sliders
        const setSlider = (id, val) => {
            const el = document.getElementById(id);
            if (el) { el.value = val; el.dispatchEvent(new Event('input')); }
        };

        setSlider('patientFc',    preset.FC);
        setSlider('patientFr',    preset.FR);
        setSlider('patientTaSis', preset.TA_sis);
        setSlider('patientTaDia', preset.TA_dia);

        // Resaltar botón activo
        document.querySelectorAll('.btn-rhythm-admin').forEach(b => {
            b.style.opacity    = '0.45';
            b.style.fontWeight = '500';
            b.style.transform  = 'scale(1)';
        });
        const active = document.getElementById('adm-rhythm-' + rhythm);
        if (active) {
            active.style.opacity    = '1';
            active.style.fontWeight = '700';
            active.style.transform  = 'scale(1.05)';
            active.style.boxShadow  = `0 0 8px ${preset.color}66`;
        }

        // Feedback
        const fb = document.getElementById('rhythm-feedback');
        if (fb) {
            fb.innerHTML = `<i class="bi bi-check-circle-fill me-1" style="color:${preset.color}"></i>
                            Ritmo aplicado: <strong style="color:${preset.color}">${preset.label}</strong>
                            — los sliders se han actualizado y se guardarán al mover cualquier slider.`;
        }
    }
    </script>

</body>

</html>
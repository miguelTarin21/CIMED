<?php
session_start();

if (!isset($_SESSION['userId'])) {
    header("Location: index.html");
    exit;
}

include './BE/connection.php';

$userId = $_SESSION['userId'];

// Obtener casos con carpeta (LEFT JOIN para incluir los sin carpeta)
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
$result = $stmt->get_result();

// Agrupar casos por carpeta
$carpetaMap   = [];  // id_carpeta => ['nombre' => ..., 'casos' => [...]]
$sinCarpeta   = [];  // casos sin carpeta asignada

while ($row = $result->fetch_assoc()) {
    if ($row['visibilidad'] != 1) continue;

    $casoDato = [
        'id'          => $row['id'],
        'nombre'      => $row['nombre'],
        'descripcion' => $row['descripcion'],
        'fecha'       => $row['fecha_actualizacion'],
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

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CIMED - Casos</title>
    <link rel="icon" type="image/png" href="images/CimarronMedicoIndexIcon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/iniciar-simulador-style.css" />
    <style>
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
        .btn-nueva-carpeta {
            background: linear-gradient(90deg,#00723e,#1a8f5a);
            border-radius: 50px;
            color: white;
            padding: 8px 22px;
            font-weight: 600;
            border: none;
            transition: background 0.3s;
        }
        .btn-nueva-carpeta:hover { background: linear-gradient(90deg,#005c31,#00723e); color: white; }
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
                        <a class="nav-link" href="./" style="margin-left: 120px;">Inicio</a>
                    </li>
                </ul>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="./" class="btn btn-primary btn-exit d-flex align-items-center">
                    <i class="bi bi-box-arrow-left me-2"></i> Regresar
                </a>
            </div>
        </div>
    </nav>

    <!-- Main -->
    <main>
        <div class="container my-4">

            <?php
            $hayAlgo = !empty($carpetaMap) || !empty($sinCarpeta);

            if (!$hayAlgo):
            ?>
                <div class="text-center py-5">
                    <i class="bi bi-folder2-open" style="font-size:3rem;color:#aaa;"></i>
                    <p class="mt-3 text-muted">No hay casos clínicos para iniciar la simulación.</p>
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
                    <div class="folder-cases row" id="folder-<?= $cid ?>">
                        <?php foreach ($carpeta['casos'] as $caso): ?>
                            <div class="col-md-4">
                                <div class="card case-card mb-3">
                                    <div class="card-body d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="card-title"><?= htmlspecialchars($caso['nombre']) ?></h5>
                                        </div>
                                        <a href="#" class="btn-play" data-bs-toggle="modal"
                                           data-bs-target="#caseModal<?= $caso['id'] ?>" style="margin-left:10px;">
                                            <i class="bi bi-play-circle-fill"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <!-- Modal del caso -->
                            <div class="modal fade" id="caseModal<?= $caso['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-md">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Detalles del Caso Clínico</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <h2 class="text-success">Nombre del Caso: <?= htmlspecialchars($caso['nombre']) ?></h2>
                                            <br>
                                            <p><?= htmlspecialchars($caso['descripcion']) ?></p>
                                            <div class="text-center mt-4">
                                                <button class="btn btn-success btn-play">
                                                    <a href="simulacion.php?id=<?= $caso['id'] ?>" class="text-white text-decoration-none">
                                                        <i class="bi bi-play-circle-fill me-2"></i> Iniciar Simulación
                                                    </a>
                                                </button>
                                            </div>
                                        </div>
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
                    <div class="folder-cases row" id="folder-sin-carpeta">
                        <?php foreach ($sinCarpeta as $caso): ?>
                            <div class="col-md-4">
                                <div class="card case-card mb-3">
                                    <div class="card-body d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="card-title"><?= htmlspecialchars($caso['nombre']) ?></h5>
                                        </div>
                                        <a href="#" class="btn-play" data-bs-toggle="modal"
                                           data-bs-target="#caseModal<?= $caso['id'] ?>" style="margin-left:10px;">
                                            <i class="bi bi-play-circle-fill"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <!-- Modal del caso -->
                            <div class="modal fade" id="caseModal<?= $caso['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-md">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Detalles del Caso Clínico</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <h2 class="text-success">Nombre del Caso: <?= htmlspecialchars($caso['nombre']) ?></h2>
                                            <br>
                                            <p><?= htmlspecialchars($caso['descripcion']) ?></p>
                                            <div class="text-center mt-4">
                                                <button class="btn btn-success btn-play">
                                                    <a href="simulacion.php?id=<?= $caso['id'] ?>" class="text-white text-decoration-none">
                                                        <i class="bi bi-play-circle-fill me-2"></i> Iniciar Simulación
                                                    </a>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    </main>


    <!-- Footer -->
    <footer class="footer mt-auto py-3 bg-light">
        <div class="container text-center">
            <span class="text-muted">Cuarta Generación de Ingeniería en Software © 2024</span>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Expandir / contraer carpetas
        function toggleFolder(id, header) {
            const el = document.getElementById(id);
            const isHidden = el.style.display === 'none';
            el.style.display = isHidden ? '' : 'none';
            header.classList.toggle('collapsed', !isHidden);
        }

    </script>
</body>

</html>

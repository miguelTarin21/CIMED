<?php
$caseId = isset($_GET['id']) ? intval($_GET['id']) : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CIMED - Simulación</title>
    <link rel="icon" type="image/png" href="images/CimarronMedicoIndexIcon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/simulacion-styles.css" />
</head>

<script>
    let caseId = <?= $caseId ?>;
</script>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="./">
                <span class="brand-name">
                    <span class="brand-ci">CI</span><span class="brand-med">MED</span>
                </span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-center" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="iniciar-simulacion.php" style="margin-left:120px;">Casos</a>
                    </li>
                </ul>
            </div>
            <a href="iniciar-simulacion.php" class="btn btn-primary btn-exit d-flex align-items-center">
                <i class="bi bi-box-arrow-left me-2"></i> Regresar
            </a>
        </div>
    </nav>

    <!-- ═══════════════════ TAB BAR ═══════════════════ -->
    <div class="sim-tab-bar">
        <button class="sim-tab active" data-tab="caso">
            <i class="bi bi-file-medical"></i>
            <span>Caso</span>
        </button>
        <button class="sim-tab" data-tab="estudios">
            <i class="bi bi-folder2-open"></i>
            <span>Estudios</span>
        </button>
        <button class="sim-tab" data-tab="videos">
            <i class="bi bi-play-circle"></i>
            <span>Maniobras</span>
        </button>
        <button class="sim-tab" data-tab="electro">
            <i class="bi bi-activity"></i>
            <span>Electro</span>
        </button>
        <button class="sim-tab" data-tab="quiz">
            <i class="bi bi-patch-question"></i>
            <span>Quiz</span>
        </button>
    </div>

    <main class="sim-main">

        <!-- ══════════════ PESTAÑA 1: CASO ══════════════ -->
        <div class="sim-panel active" id="panel-caso">
            <div class="sim-card">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <img src="images/clave (1).png" alt="clave" style="width:28px;height:auto;">
                        <h2 class="sim-card-title mb-0">
                            Nombre del Caso: <span id="nombre-caso" class="text-success"></span>
                        </h2>
                    </div>
                    <div id="fecha-actualizacion-container" class="sim-fecha-badge" style="display:none;">
                        <i class="bi bi-calendar-check me-1"></i>
                        Última actualización: <span id="fecha-actualizacion"></span>
                    </div>
                </div>

                <p class="sim-descripcion" id="descripcion-caso"></p>

                <!-- Datos del paciente -->
                <div class="sim-section-header">
                    <img src="images/infpaciente-removebg-preview.png" alt="paciente" style="width:26px;height:auto;">
                    <h4 class="text-primary mb-0">Datos del Paciente</h4>
                </div>
                <div class="row mt-2">
                    <div class="col-md-6">
                        <ul class="sim-list">
                            <li><strong>Nombre:</strong> <span id="nombre-paciente"></span></li>
                            <li><strong>Sexo:</strong> <span id="sexo-paciente"></span></li>
                            <li><strong>Motivo de consulta:</strong>
                                <div id="motivo-consulta" class="mt-1"></div>
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul class="sim-list">
                            <li><strong>Edad:</strong> <span id="edad-paciente"></span> años</li>
                            <li><strong>Peso:</strong> <span id="peso-paciente"></span> kg</li>
                            <li><strong>Altura:</strong> <span id="altura-paciente"></span> cm</li>
                        </ul>
                    </div>
                    <div class="col-12 mt-2">
                        <ul class="sim-list">
                            <li><strong>Antecedentes de importancia:</strong>
                                <div id="enfermedades-paciente" class="mt-1"></div>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Signos vitales (resumen estático) -->
                <div class="sim-section-header mt-3">
                    <img src="images/signos-paciente.png" alt="signos" style="width:26px;height:auto;">
                    <h4 class="text-primary mb-0">Signos del Paciente</h4>
                </div>
                <div class="row mt-2">
                    <div class="col-md-6">
                        <ul class="sim-list">
                            <li><strong>FR:</strong> <span id="fr-paciente"></span> rpm</li>
                            <li><strong>FC:</strong> <span id="fc-paciente"></span> lpm</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul class="sim-list">
                            <li><strong>TA:</strong> <span id="ta-paciente"></span> mmHg</li>
                            <li><strong>Temp:</strong> <span id="temp-paciente"></span> °C</li>
                            <li><strong>SAT:</strong> <span id="sat-paciente"></span> %</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══════════════ PESTAÑA 2: ESTUDIOS ══════════════ -->
        <div class="sim-panel" id="panel-estudios">
            <div class="sim-card">
                <h4 class="sim-panel-title">
                    <i class="bi bi-folder2-open text-primary me-2"></i> Estudios Disponibles
                </h4>
                <p class="text-muted" style="font-size:.88rem;">Selecciona un estudio para visualizarlo.</p>
                <div class="row justify-content-start" id="estudios-container">
                    <!-- Se llena desde JS -->
                </div>
                <div id="detalleEstudio" class="mt-4" style="display:none;"></div>
            </div>
        </div>

        <!-- ══════════════ PESTAÑA 3: VIDEOS ══════════════ -->
        <div class="sim-panel" id="panel-videos">
            <div class="sim-card sim-card-dark">
                <h4 class="sim-panel-title text-white">
                    <i class="bi bi-play-circle-fill text-danger me-2"></i> Maniobras de Propedéutica
                </h4>
                <div id="videos-list" class="mt-3">
                    <p class="text-muted">No hay videos disponibles para este caso.</p>
                </div>
            </div>
        </div>

        <!-- ══════════════ PESTAÑA 4: ELECTRO ══════════════ -->
        <div class="sim-panel" id="panel-electro">
            <div id="seccionECG">
                <div class="monitor-pro">
                    <!-- Barra de alertas -->
                    <div class="mon-alert" id="monitor-alert" style="display:none"></div>

                    <!-- Cuerpo: canales + vitals -->
                    <div class="mon-body">
                        <!-- Columna izquierda: canales de onda -->
                        <div class="mon-channels">
                            <div class="mon-ch">
                                <div class="mon-ch-info">
                                    <span class="mon-ch-name" style="color:#00ff9f">I</span>
                                    <span class="mon-ch-sub" style="color:#00ff9f">ECG</span>
                                </div>
                                <canvas id="canvas-ecg" class="mon-canvas" height="88"></canvas>
                            </div>
                            <div class="mon-ch">
                                <div class="mon-ch-info">
                                    <span class="mon-ch-name" style="color:#4db8ff">SpO₂</span>
                                    <span class="mon-ch-sub" style="color:#4db8ff">PLETH</span>
                                </div>
                                <canvas id="canvas-spo2" class="mon-canvas" height="88"></canvas>
                            </div>
                            <div class="mon-ch">
                                <div class="mon-ch-info">
                                    <span class="mon-ch-name" style="color:#ff6060">IBP</span>
                                    <span class="mon-ch-sub" style="color:#ff6060">ART</span>
                                </div>
                                <canvas id="canvas-bp" class="mon-canvas" height="88"></canvas>
                            </div>
                            <div class="mon-ch">
                                <div class="mon-ch-info">
                                    <span class="mon-ch-name" style="color:#00e5cc">EEG</span>
                                    <span class="mon-ch-sub" style="color:#00e5cc">BIS</span>
                                </div>
                                <canvas id="canvas-eeg" class="mon-canvas" height="88"></canvas>
                            </div>
                            <div class="mon-ch mon-ch-last">
                                <div class="mon-ch-info">
                                    <span class="mon-ch-name" style="color:#ffee00">CO₂</span>
                                    <span class="mon-ch-sub" style="color:#ffee00">CAPNO</span>
                                </div>
                                <canvas id="canvas-co2" class="mon-canvas" height="88"></canvas>
                            </div>
                        </div>

                        <!-- Columna derecha: valores numéricos -->
                        <div class="mon-vitals">
                            <div class="mv-block" id="card-fc">
                                <div class="mv-lbl" style="color:#00ff9f">HR</div>
                                <div class="mv-val" id="vi-fc" style="color:#00ff9f">--</div>
                                <div class="mv-unit" style="color:#00ff9f">bpm</div>
                            </div>
                            <div class="mv-block" id="card-sat">
                                <div class="mv-lbl" style="color:#4db8ff">SpO2</div>
                                <div class="mv-val" id="vi-sat" style="color:#4db8ff">--</div>
                                <div class="mv-unit" style="color:#4db8ff">%</div>
                            </div>
                            <div class="mv-block" id="card-ta">
                                <div class="mv-lbl" style="color:#ff6060">IBP</div>
                                <div class="mv-val mv-val-sm" id="vi-ta" style="color:#ff6060">--/--</div>
                                <div class="mv-unit" style="color:#ff6060">mmHg</div>
                            </div>
                            <div class="mv-block" id="card-temp">
                                <div class="mv-lbl" style="color:#00e5cc">BIS</div>
                                <div class="mv-val" id="vi-bis" style="color:#00e5cc">--</div>
                                <div class="mv-unit" style="color:#00e5cc">&nbsp;</div>
                                <div style="display:none" id="vi-temp"></div>
                            </div>
                            <div class="mv-block mv-block-co2" id="card-fr">
                                <div class="mv-lbl" style="color:#ffee00">CO₂</div>
                                <div class="mv-val mv-val-lg" id="vi-etco2" style="color:#ffee00">--</div>
                                <div class="mv-unit" style="color:#ffee00">
                                    RR&nbsp;<span id="vi-fr">--</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Barra de estado -->
                    <div class="mon-statusbar">
                        <span class="ecg-led" id="ecg-led"></span>
                        <span style="font-family:monospace;color:#00ff9f;font-size:.78rem;letter-spacing:2px">
                            CIMED MONITOR
                        </span>
                        <span class="ecg-bpm-display" id="ecg-bpm-display" style="margin-left:14px">-- BPM</span>
                        <span class="ecg-status-text" id="ecg-status-text" style="margin-left:10px">Cargando…</span>
                        <span class="ms-auto" style="font-size:.7rem;color:#444" id="ecg-last-update"></span>
                    </div>
                </div>
            </div>
            <!-- Elementos ocultos para compatibilidad con JS legacy -->
            <canvas id="ecgCanvas" style="display:none"></canvas>
            <div id="vitals-indicators" style="display:none"></div>
        </div>

        <!-- ══════════════ PESTAÑA 5: QUIZ ══════════════ -->
        <div class="sim-panel" id="panel-quiz">
            <div id="seccionBienvenida" class="sim-card text-center">
                <div class="sim-quiz-start-icon">🩺</div>
                <h3 class="fw-bold mt-3">¿Listo para empezar?</h3>
                <p class="text-muted">Responde las preguntas del caso clínico.</p>
                <button class="btn sim-btn-start mt-2" onclick="iniciarPreguntas()">
                    <i class="bi bi-play-fill me-1"></i> Empezar quiz
                </button>
            </div>

            <div id="seccionPrincipal" class="sim-card" style="display:none;">
                <div id="preguntaContainer">
                    <h3 id="preguntaTexto" class="sim-pregunta-texto"></h3>
                    <div id="opcionesContainer" class="mt-3 mb-2"></div>
                    <div id="explicacionIncorrecta" class="text-dangerss mt-2"></div>
                    <button class="btn sim-btn-next mt-3" onclick="cambiarPregunta()">
                        Siguiente <i class="bi bi-arrow-right ms-1"></i>
                    </button>
                </div>
            </div>
        </div>

    </main>

    <!-- Modal PDF/Imagen -->
    <div class="modal fade" id="archivoModal" tabindex="-1" aria-labelledby="archivoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="archivoModalLabel">Vista del Archivo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <iframe id="archivoIframe" src="" frameborder="0" style="width:100%;height:80vh;"></iframe>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container text-center">
            <span class="text-muted">Cuarta Generación de Ingeniería en Software © 2024</span>
        </div>
    </footer>

    <script src="js/simulacion.js?v=<?= time() ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
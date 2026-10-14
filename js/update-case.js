// ===== Helpers para badges de signos vitales =====
function updateVitalBadge(badgeId, value, type) {
    const badge = document.getElementById(badgeId);
    if (!badge) return;
    badge.classList.remove('normal','warning','critical');
    let cls = 'normal';
    if (type === 'fr') {
        if (value < 8 || value > 30) cls = 'critical';
        else if (value < 12 || value > 20) cls = 'warning';
    } else if (type === 'fc') {
        if (value < 40 || value > 150) cls = 'critical';
        else if (value < 60 || value > 100) cls = 'warning';
    } else if (type === 'temp') {
        if (value < 35 || value > 39) cls = 'critical';
        else if (value < 36.1 || value > 37.2) cls = 'warning';
    } else if (type === 'sat') {
        if (value < 90) cls = 'critical';
        else if (value < 95) cls = 'warning';
    } else if (type === 'ta') {
        // value = sistólica
        if (value >= 180) cls = 'critical';
        else if (value >= 130) cls = 'warning';
    }
    badge.classList.add(cls);
}

function initSliderListeners() {
    const frSlider   = document.getElementById('patientFr');
    const fcSlider   = document.getElementById('patientFc');
    const taSisSlider = document.getElementById('patientTaSis');
    const taDiaSlider = document.getElementById('patientTaDia');
    const tempSlider = document.getElementById('patientTemp');
    const satSlider  = document.getElementById('patientSat');

    function onSliderChange() {
        // Actualizar displays
        const fr   = parseInt(frSlider.value);
        const fc   = parseInt(fcSlider.value);
        const taSis = parseInt(taSisSlider.value);
        const taDia = parseInt(taDiaSlider.value);
        const temp = parseFloat(tempSlider.value);
        const sat  = parseInt(satSlider.value);

        document.getElementById('fr-display').textContent   = fr;
        document.getElementById('fc-display').textContent   = fc;
        document.getElementById('ta-sis-display').textContent = taSis;
        document.getElementById('ta-dia-display').textContent = taDia;
        document.getElementById('temp-display').textContent  = temp.toFixed(1);
        document.getElementById('sat-display').textContent   = sat;

        // Actualizar campo oculto de TA
        document.getElementById('patientTa').value = `${taSis}/${taDia}`;

        // Colores badge
        updateVitalBadge('fr-badge',   fr,   'fr');
        updateVitalBadge('fc-badge',   fc,   'fc');
        updateVitalBadge('ta-badge',   taSis,'ta');
        updateVitalBadge('temp-badge', temp, 'temp');
        updateVitalBadge('sat-badge',  sat,  'sat');

        // Guardar en tiempo real si hay un caso cargado
        if (window._caseIdToEdit) {
            const ritmo = window.currentRhythm || 'sinusal';
            saveVitalSigns(window._caseIdToEdit, fr, fc, `${taSis}/${taDia}`, temp, sat, ritmo);
        }
    }

    [frSlider, fcSlider, taSisSlider, taDiaSlider, tempSlider, satSlider].forEach(slider => {
        if (slider) slider.addEventListener('input', onSliderChange);
    });
}

let saveTimeout = null;
function saveVitalSigns(casoId, fr, fc, ta, temp, sat, ritmo) {
    const status = document.getElementById('realtime-status');
    clearTimeout(saveTimeout);
    saveTimeout = setTimeout(async () => {
        if (status) {
            status.className = 'mt-2 saving';
            status.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i> Guardando signos vitales…';
        }
        try {
            const res = await fetch('./BE/update_signos_vitales.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ casoId, FR: fr, FC: fc, TA: ta, Temperatura: temp, SAT: sat, ritmo: ritmo })
            });
            const data = await res.json();
            if (status) {
                if (data.success) {
                    status.className = 'mt-2 saved';
                    status.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Guardado en tiempo real';
                } else {
                    status.className = 'mt-2 error';
                    status.innerHTML = '<i class="bi bi-x-circle me-1"></i> Error al guardar';
                }
            }
        } catch (e) {
            if (status) {
                status.className = 'mt-2 error';
                status.innerHTML = '<i class="bi bi-x-circle me-1"></i> Error de red';
            }
        }
    }, 300); // debounce 300ms
}

document.addEventListener('DOMContentLoaded', function () {
    // Variables globales
    let caseIdToEdit = null;
    let questionCount = 0;
    const preguntasContainer = document.getElementById('preguntas-container');
    const addQuestionBtn = document.getElementById('add-question');
    const removeQuestionBtn = document.getElementById('remove-question');
    const updateCaseBtn = document.getElementById('updateCaseBtn');

    // Inicializar sliders
    initSliderListeners();
    
    updateCaseBtn.addEventListener('click', function () {
        if (validateInfo()) {
            updateCase();
        }
    });

    // Eventos para agregar estudios
    const addStudyBtn = document.getElementById('add-study');
    addStudyBtn.addEventListener('click', function () {
        addFileInput();
    });

    // Evento: agregar nuevo video en el modal de edición
    document.getElementById('btn-agregar-video-edit') &&
        document.getElementById('btn-agregar-video-edit').addEventListener('click', addVideoNuevoEdit);

    // Abrir el modal al hacer clic en el botón de editar
    document.querySelectorAll('.edit-case-btn').forEach(button => {
        button.addEventListener('click', function () {
            caseIdToEdit = this.getAttribute('data-id');
            window._caseIdToEdit = caseIdToEdit; // para sliders en tiempo real
            const editModal = new bootstrap.Modal(document.getElementById('updateCaseModal'));
            loadCaseData(caseIdToEdit);
            editModal.show();
        });
    });

    // Función para cargar los datos del caso en el formulario
    function loadCaseData(caseId) {
        fetch(`./BE/get-case-info.php?id=${caseId}`)
            .then(response => response.json())
            .then(data => {
                         
                // Precargar información del paciente
                document.getElementById('patientName').value = data.paciente.nombre;
                document.getElementById('patientGender').value = data.paciente.sexo;
                document.getElementById('patientAge').value = data.paciente.edad;
                document.getElementById('patientWeight').value = data.paciente.peso;
                document.getElementById('patientHeight').value = data.paciente.altura;
                document.getElementById('patientEnfermedades').value = data.paciente.enfermedades;

                // Rellenar sliders de signos vitales
                const fr   = data.signos_paciente.FR   || 16;
                const fc   = data.signos_paciente.FC   || 75;
                const ta   = data.signos_paciente.TA   || '120/80';
                const temp = data.signos_paciente.Temperatura || 37.0;
                const sat  = data.signos_paciente.SAT  || 98;

                // Parse TA "sistólica/diastólica"
                const taParts = String(ta).split('/');
                const taSis   = parseInt(taParts[0]) || 120;
                const taDia   = parseInt(taParts[1]) || 80;

                document.getElementById('patientFr').value    = fr;
                document.getElementById('patientFc').value    = fc;
                document.getElementById('patientTaSis').value = taSis;
                document.getElementById('patientTaDia').value = taDia;
                document.getElementById('patientTa').value    = ta;
                document.getElementById('patientTemp').value  = temp;
                document.getElementById('patientSat').value   = sat;

                // Actualizar displays de valor
                document.getElementById('fr-display').textContent    = fr;
                document.getElementById('fc-display').textContent    = fc;
                document.getElementById('ta-sis-display').textContent = taSis;
                document.getElementById('ta-dia-display').textContent = taDia;
                document.getElementById('temp-display').textContent  = parseFloat(temp).toFixed(1);
                document.getElementById('sat-display').textContent   = sat;

                // Colores badge
                updateVitalBadge('fr-badge',   fr,    'fr');
                updateVitalBadge('fc-badge',   fc,    'fc');
                updateVitalBadge('ta-badge',   taSis, 'ta');
                updateVitalBadge('temp-badge', temp,  'temp');
                updateVitalBadge('sat-badge',  sat,   'sat');
           
                // Precargar ritmo cardíaco activo
                const ritmo = data.signos_paciente.ritmo || 'sinusal';
                window.currentRhythm = ritmo;
                document.querySelectorAll('.btn-rhythm-admin').forEach(b => {
                    b.style.opacity    = '0.45';
                    b.style.fontWeight = '500';
                    b.style.transform  = 'scale(1)';
                    b.style.boxShadow  = 'none';
                });
                const activeBtn = document.getElementById('adm-rhythm-' + ritmo);
                if (activeBtn) {
                    activeBtn.style.opacity    = '1';
                    activeBtn.style.fontWeight = '700';
                    activeBtn.style.transform  = 'scale(1.05)';
                    if (typeof RHYTHM_PRESETS_ADMIN !== 'undefined' && RHYTHM_PRESETS_ADMIN[ritmo]) {
                        activeBtn.style.boxShadow  = `0 0 8px ${RHYTHM_PRESETS_ADMIN[ritmo].color}66`;
                    }
                }
                
                // Precargar información del caso
                document.getElementById('caseName').value = data.caso.nombre;
                document.getElementById('caseMotivo').value = data.caso.motivoConsulta;
                document.getElementById('caseDescription').value = data.caso.descripcion;
                document.getElementById('caseVisibility').value = data.caso.visibilidad;

                // Precargar videos (se cargan via loadVideos)
                loadVideos();

                // Precargar preguntas e incisos
                const preguntasContainer = document.getElementById('preguntas-container');
                preguntasContainer.innerHTML = ''; // Limpiar preguntas previas
                questionCount = 0; // Reiniciar contador de preguntas

                data.preguntas.forEach((pregunta, index) => {
                    addQuestion(); // Agrega una nueva pregunta al formulario

                    // Precargar texto de la pregunta y feedback
                    document.getElementById(`pregunta-${questionCount}`).value = pregunta.texto_pregunta;
                    document.getElementById(`feedback-${questionCount}`).value = pregunta.feedback;

                    // Precargar incisos
                    pregunta.incisos.forEach((inciso, i) => {
                        // Asegurarse de que haya suficientes opciones
                        if (i >= 2) addOption(questionCount); // Agregar opciones adicionales si es necesario

                        // Precargar texto del inciso
                        const optionInputs = document.querySelectorAll(`#option-list-${questionCount} input[type="text"]`);
                        optionInputs[i].value = inciso.texto;

                        // Marcar la opción correcta
                        const radioInputs = document.querySelectorAll(`#option-list-${questionCount} input[type="radio"]`);
                        if (inciso.correcto === 1) {
                            radioInputs[i].checked = true; // Marcar como correcta
                        }
                    });
                });

                // Precargar estudios
                loadStudies();

            })
            .catch(error => console.error('Error cargando datos del caso:', error));
    }
    
    // ** Función para cargar los videos del caso **
    function loadVideos() {
        const container = document.getElementById('videos-existentes-container');
        const nuevosContainer = document.getElementById('videos-nuevos-container');
        if (container)  container.innerHTML = '';
        if (nuevosContainer) nuevosContainer.innerHTML = '';

        fetch(`./BE/get_videos.php?id_caso=${caseIdToEdit}`)
            .then(r => r.json())
            .then(videos => {
                if (!Array.isArray(videos)) return;
                if (videos.length === 0) {
                    container.innerHTML = '<p class="text-muted" style="font-size:0.83rem;">No hay videos aún.</p>';
                    return;
                }
                videos.forEach(video => {
                    addVideoExistente(video.id, video.nombre, video.ruta);
                });
            })
            .catch(e => console.error('Error cargando videos:', e));
    }

    // Agrega un video existente a la lista (con nombre editable y botón eliminar)
    function addVideoExistente(videoId, nombre, ruta) {
        const container = document.getElementById('videos-existentes-container');
        const div = document.createElement('div');
        div.className = 'video-existente-item mb-2';
        div.style.cssText = 'background:#f0f4ff;border:1px solid #dce0f3;border-radius:10px;padding:10px 14px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;';
        div.innerHTML = `
            <i class="bi bi-camera-video-fill" style="color:#2f50ff;font-size:1.1rem;"></i>
            <input type="text" class="form-control form-control-sm video-nombre-edit" value="${nombre}"
                style="flex:1;min-width:120px;border-radius:7px;"
                data-video-id="${videoId}">
            <a href="${ruta}" target="_blank" class="btn btn-sm btn-outline-primary" title="Ver video">
                <i class="bi bi-play-circle"></i> Ver
            </a>
            <button type="button" class="btn btn-sm btn-danger btn-eliminar-video" data-video-id="${videoId}" title="Eliminar">
                <i class="bi bi-trash"></i>
            </button>
        `;
        // Botón eliminar
        div.querySelector('.btn-eliminar-video').addEventListener('click', async function () {
            const id = this.getAttribute('data-video-id');
            if (!confirm('¿Eliminar este video?')) return;
            try {
                const res = await fetch('./BE/deleteVideo.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: parseInt(id) })
                });
                const data = await res.json();
                if (data.success) {
                    div.remove();
                } else {
                    alert('Error al eliminar: ' + (data.message || 'desconocido'));
                }
            } catch(e) { alert('Error de red al eliminar el video.'); }
        });
        container.appendChild(div);
    }

    // Agrega un nuevo campo para subir un video al editar
    function addVideoNuevoEdit() {
        const container = document.getElementById('videos-nuevos-container');
        const div = document.createElement('div');
        div.className = 'video-nuevo-item mb-2';
        div.style.cssText = 'background:#f8f9ff;border:1px solid #dce0f3;border-radius:10px;padding:10px 14px;';
        div.innerHTML = `
            <div class="mb-1">
                <input type="text" class="form-control form-control-sm video-nombre-nuevo" placeholder="Nombre del video (ej: Clase 1)" style="border-radius:7px;">
            </div>
            <div style="display:flex;gap:8px;align-items:center;">
                <input type="file" class="form-control form-control-sm video-archivo-nuevo" accept="video/*" style="flex:1;">
                <button type="button" class="btn btn-sm btn-danger btn-quitar-video-nuevo" title="Cancelar">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        `;
        div.querySelector('.btn-quitar-video-nuevo').addEventListener('click', () => div.remove());
        container.appendChild(div);
    }

    function loadStudies() {
        const container = document.getElementById('studies-container');
        container.innerHTML = ''; // Limpiar el contenedor

        // Realiza una consulta para obtener los archivos asociados
        fetch(`./BE/get_files.php?id_caso=${caseIdToEdit}`)
            .then(response => response.json())
            .then(files => {
                if (files.error) {
                    console.error('Error en la respuesta del servidor:', files.error);
                    return;
                }    

                // Añade los estudios al formulario
                files.forEach(file => {
                    addFileInput(file.nombre, file.ruta);
                });
        })
        .catch(error => console.error('Error obteniendo archivos:', error));

    }

    // ** Función para agregar y eliminar estudios en el formulario **

    // Añadir un nuevo campo de entrada para un estudio, similar a addStudyItem
    function addFileInput(text = '', ruta = '') {
        const container = document.getElementById('studies-container');
        const studyDiv = document.createElement('div');
        studyDiv.className = 'study-item mb-3';

        // Añadir nombre del estudio, campo de archivo y botones para ver el archivo actual y eliminar el estudio
        studyDiv.innerHTML = `
            <div class="input-group mb-2">
                <input type="text" class="form-control" value="${text}" id="nombre-estudio" placeholder="Nombre del estudio" required ${ruta ? 'readonly' : ''}>
            </div>
            <div class="input-group">
                <input type="file" class="form-control me-3" id="${ruta}" accept=".pdf, .jpeg, .jpg">
                ${ruta ? `<button class="btn btn-success me-2">
                    <i class="bi bi-download"></i> Ver actual
                </button>` : ''}
                <button class="btn btn-danger">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        `;

        // Configura el botón de descarga si existe una ruta
        if (ruta) {
            const downloadButton = studyDiv.querySelector('.btn-success');
            downloadButton.addEventListener('click', () => {
                window.open(ruta, '_blank'); // Abre el archivo en una nueva pestaña
            });
        }

        // Configura el botón de eliminar
        const deleteButton = studyDiv.querySelector('.btn-danger');
        deleteButton.addEventListener('click', () => {
            removeFileInput(studyDiv);
        });

        container.appendChild(studyDiv);
    }

    // Remover un campo de entrada para un estudio
    function removeFileInput(studyDiv) {
        const container = document.getElementById('studies-container');
        if (container.children.length > 1) {
            container.removeChild(studyDiv);
        } else {
            alert('Debe haber al menos un estudio.');
        }
    }

    // ** Funcion para actualizar el caso en la base de datos **

    function validateInfo() {
        // Función para validar cada campo
        function validateField(field, type = 'text') {
            const value = field.value.trim(); // Eliminar espacios en blanco
            field.value = value; // Actualizar el valor limpio en el campo

            if (type === 'text' && value === '') {
                return false; // Campo de texto vacío
            }

            if (type === 'number') {
                const numValue = parseFloat(value);
                if (isNaN(numValue) || numValue < 0) {
                    return false; // Valor no numérico o negativo
                }
            }

            return true; // Campo válido
        }

        // Mostrar mensajes de error
        function showError(field, message) {
            field.classList.add('is-invalid');
            const errorDiv = document.createElement('div');
            errorDiv.className = 'invalid-feedback';
            errorDiv.textContent = message;
            if (!field.nextElementSibling) {
                field.parentNode.appendChild(errorDiv);
            }
        }

        // Limpiar errores previos
        function clearError(field) {
            field.classList.remove('is-invalid');
            if (field.nextElementSibling) {
                field.nextElementSibling.remove();
            }
        }

        // Validar cada campo
        let isValid = true;

        // Info del caso
        const caseName = document.getElementById('caseName');
        const caseMotivo = document.getElementById('caseMotivo');
        const caseDescription = document.getElementById('caseDescription');
        const caseVisibility = document.getElementById('caseVisibility');

        [caseName, caseDescription, caseMotivo, caseVisibility].forEach((field) => {
            clearError(field);
            if (!validateField(field)) {
                isValid = false;
                showError(field, 'Este campo no puede estar vacío.');
            }
        });

        if(!isValid) {
            alert('Por favor, complete todos los campos correctamente en "Info del Caso".');
            return isValid;
        }

        // Paciente
        const patientName = document.getElementById('patientName');
        const patientGender = document.getElementById('patientGender');
        const patientAge = document.getElementById('patientAge');
        const patientWeight = document.getElementById('patientWeight');
        const patientHeight = document.getElementById('patientHeight');
        const patientEnfermedades = document.getElementById('patientEnfermedades');

        // Signos vitales
        const patientFr = document.getElementById('patientFr');
        const patientFc = document.getElementById('patientFc');
        const patientTa = document.getElementById('patientTa');
        const patientTemp = document.getElementById('patientTemp');
        const patientSat = document.getElementById('patientSat');

        let isValid_input = true;

        // Validar campos de texto
        [
            patientName,
            patientGender,
            patientEnfermedades,
            patientTa // TA es varchar
        ].forEach((field) => {
            clearError(field);
            if (!validateField(field)) {
                isValid_input = false;
                showError(field, 'Este campo no puede estar vacío.');
            }
        });

        // Validar campos numéricos
        [
            patientAge,
            patientWeight,
            patientHeight,
            patientFr,
            patientFc,
            patientTemp,
            patientSat
        ].forEach((field) => {
            clearError(field);
            if (!validateField(field, 'number')) {
                isValid_input = false;
                showError(field, 'Ingrese un número válido y positivo.');
            }
        });

        if (!isValid_input) {
            alert('Por favor, complete todos los campos correctamente en "Paciente".');
            return isValid_input;
        }

        // Validar que haya al menos un archivo cargado
        const estudiosContenedor = document.querySelectorAll('#studies-container .study-item');
    
        if (estudiosContenedor.length === 0) {
            isValid = false;
            alert('Debe agregar al menos un estudio.');
            return isValid;
        }
    
        estudiosContenedor.forEach(estudio => {
            const nombreEstudio = estudio.querySelector('input[type="text"]');
            const fileInput = estudio.querySelector('input[type="file"]');
            const downloadButton = estudio.querySelector('.btn-success');
    
            // Validar que el nombre del estudio no esté vacío o solo tenga espacios
            if (!nombreEstudio.value.trim()) {
                isValid = false;
                nombreEstudio.classList.add('error'); // Añade una clase de error para estilos
                nombreEstudio.style.border = '2px solid red'; // Pone el borde en rojo
            } else {
                nombreEstudio.classList.remove('error'); // Remueve la clase de error si es válido
                nombreEstudio.style.border = ''; // Remueve el borde rojo si es válido
            }
    
            // Validar que se haya cargado un archivo o exista un archivo actual
            if (fileInput.files.length === 0 && !downloadButton) {
                isValid = false;
                fileInput.classList.add('error'); // Añade una clase de error para estilos
                fileInput.style.border = '2px solid red'; // Pone el borde en rojo
            } else {
                fileInput.classList.remove('error'); // Remueve la clase de error si es válido
                fileInput.style.border = ''; // Remueve el borde rojo si es válido
            }
        });        

        if(!isValid) {
            alert('Por favor, complete todos los campos correctamente en "Estudios".');
            return isValid;
        }

        // Validar preguntas e incisos
        const questions = document.querySelectorAll('.pregunta-container');
        questions.forEach((question) => {
            const questionId = question.getAttribute('data-question-id');
            const questionInput = document.getElementById(`pregunta-${questionId}`);
            const feedbackInput = document.getElementById(`feedback-${questionId}`);
            const optionsList = document.getElementById(`option-list-${questionId}`);
            const options = optionsList.querySelectorAll('li');
            const radios = optionsList.querySelectorAll('input[type="radio"]');

            // Validar texto de la pregunta
            clearError(questionInput);
            if (questionInput.value.trim() === '') {
                isValid = false;
                showError(questionInput, 'La pregunta no puede estar vacía.');
            }

            // Validar feedback
            clearError(feedbackInput);
            if (feedbackInput.value.trim() === '') {
                isValid = false;
                showError(feedbackInput, 'El feedback no puede estar vacío.');
            }

            // Validar texto de cada opción
            options.forEach((option) => {
                const optionInput = option.querySelector('input[type="text"]');
                clearError(optionInput);
                if (optionInput.value.trim() === '') {
                    isValid = false;
                    optionInput.classList.add('is-invalid');
                }
            });

            // Validar que haya una sola respuesta correcta
            const selectedRadios = Array.from(radios).filter(radio => radio.checked);
            if (selectedRadios.length !== 1) {
                isValid = false;
                alert(`La pregunta ${questionId} debe tener exactamente un inciso marcado como respuesta correcta.`);
            }
        });

        if(!isValid) {
            alert('Por favor, complete todas las preguntas e incisos correctamente.');
            return isValid;
        }

        return isValid;

        // Si todas las validaciones pasan
        if (isValid) {
            console.log('Formulario válido. Procesando...');
            // Aquí puedes enviar los datos o continuar con el flujo deseado
        } else {
            console.log('Errores en el formulario. Revise los campos.');
        }

    }

    // ** Función para actualizar el caso en la base de datos **

    async function updateCase() {
        // Info del Caso
        const casename = document.getElementById('caseName').value;
        const casedescription = document.getElementById('caseDescription').value;
        const casemotivo = document.getElementById('caseMotivo').value;
        const casevisibility = document.getElementById('caseVisibility').value;

        // Paciente
        const patientname = document.getElementById('patientName').value;
        const patientgender = document.getElementById('patientGender').value;
        const patientage = document.getElementById('patientAge').value;
        const patientweight = document.getElementById('patientWeight').value;
        const patientheight = document.getElementById('patientHeight').value;
        const patientenfermedades = document.getElementById('patientEnfermedades').value;

        // Preguntas e incisos
        const questions = document.querySelectorAll('.pregunta-container');
        const preguntas = [];

        questions.forEach((question) => {
            const questionId = question.getAttribute('data-question-id');
            const questionInput = document.getElementById(`pregunta-${questionId}`);
            const feedbackInput = document.getElementById(`feedback-${questionId}`);
            const optionsList = document.getElementById(`option-list-${questionId}`);
            const options = optionsList.querySelectorAll('li');

            const incisos = [];
            options.forEach((option, index) => {
                const optionInput = option.querySelector('input[type="text"]');
                const radioInput = option.querySelector('input[type="radio"]');
                const correct = radioInput.checked ? 1 : 0;

                incisos.push({
                    texto: optionInput.value,
                    correcto: correct
                });
            });

            preguntas.push({
                texto_pregunta: questionInput.value,
                feedback: feedbackInput.value,
                incisos: incisos
            });
        });
        
        const rutasArchivos = Array.from(document.querySelectorAll("#studies-container .study-item")).map(studyItem => {
            const archivoInput = studyItem.querySelector("input[type='file']");
            const archivoActual = studyItem.querySelector(".btn-success");
            let archivoRuta = "";
    
            if (archivoInput.files.length > 0) {
                // Si hay un archivo cargado, obtenemos la ruta del archivo cargado
                archivoRuta = archivoInput.files[0].name; // Solo obtiene el nombre del archivo, se puede adaptar según se necesite
            } else if (archivoInput.getAttribute('id')) {
                // Si existe una ruta en el atributo id del input de archivo, la usamos como la ruta actual
                archivoRuta = archivoInput.getAttribute('id');
            } else if (archivoActual) {
                // Si no se ha cargado un archivo nuevo pero existe un archivo actual, obtenemos su ruta relativa
                const fullHref = archivoActual.getAttribute('href');
                if (fullHref) {
                    const url = new URL(fullHref, window.location.origin);
                    archivoRuta = url.pathname.startsWith('/') ? '.' + url.pathname : './' + url.pathname;
                }
            }
    
            return archivoRuta;
        }).filter(ruta => ruta !== ""); // Filtramos para eliminar las rutas vacías, ya que solo queremos rutas existentes

        // Crear objeto con los datos del caso
        const caseData = {
            caso: {
                casoId: caseIdToEdit,
                nombre: casename,
                descripcion: casedescription,
                motivoConsulta: casemotivo,
                visibilidad: casevisibility
            },
            paciente: {
                nombre: patientname,
                sexo: patientgender,
                edad: patientage,
                peso: patientweight,
                altura: patientheight,
                enfermedades: patientenfermedades,
                frecuenciaRespiratoria: document.getElementById('patientFr').value,
                frecuenciaCardiaca: document.getElementById('patientFc').value,
                tensionArterial: document.getElementById('patientTa').value,
                temperatura: document.getElementById('patientTemp').value,
                saturacion: document.getElementById('patientSat').value,
                ritmo: window.currentRhythm || 'sinusal'
            },
            preguntas: preguntas,
            rutas: rutasArchivos
        };

        // Re-armar TA desde sliders
        const taSis = document.getElementById('patientTaSis') ? document.getElementById('patientTaSis').value : '';
        const taDia = document.getElementById('patientTaDia') ? document.getElementById('patientTaDia').value : '';
        if (taSis && taDia) {
            caseData.paciente.tensionArterial = `${taSis}/${taDia}`;
        }

        console.log('Datos del caso:', caseData);

        // Enviar la informacion a la base de datos (sin los archivos)
        try {
            const response = await fetch(`./BE/update_case.php`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify(caseData)
            });
            const casoUpdateResponse = await response.json();
            console.log("Update case:", casoUpdateResponse);
        } catch (error) {
            console.error("Error updating case:", error);
        }

        // ── Calcular archivos a subir ──
        let archivosRequeridos = 0;
        const videosNuevos = Array.from(document.querySelectorAll('#videos-nuevos-container .video-nuevo-item'));
        videosNuevos.forEach(item => {
            if (item.querySelector('.video-archivo-nuevo').files.length > 0) archivosRequeridos++;
        });

        const estudiosSeleccionados = Array.from(document.querySelectorAll("#studies-container .study-item")).map((studyItem, index) => {
            const nombreEstudio = studyItem.querySelector("#nombre-estudio").value;
            const archivoInput = studyItem.querySelector("input[type='file']");
            if (archivoInput && archivoInput.files.length > 0) {
                archivosRequeridos++;
            }
            return {
                id: index,
                nombre: nombreEstudio,
                archivo: archivoInput
            };
        });

        if (archivosRequeridos === 0) {
            window.location.href = "./admin-panel.php";
            return;
        }

        let archivosSubidos = 0;

        function checkAllUploadsDone() {
            if (archivosSubidos >= archivosRequeridos) {
                window.location.href = "./admin-panel.php";
            }
        }

        estudiosSeleccionados.forEach(async estudio => {
            if (estudio.archivo && estudio.archivo.files.length > 0) {
                await uploadFile(caseIdToEdit, estudio.archivo.files[0], estudio.nombre);
            }
        });

        // Subir videos nuevos con nombre editable
        videosNuevos.forEach(async item => {
            const archivoVideo = item.querySelector('.video-archivo-nuevo');
            const nombreVideo  = item.querySelector('.video-nombre-nuevo').value.trim();
            if (archivoVideo.files.length > 0) {
                await uploadVideoFile(caseIdToEdit, archivoVideo.files[0], nombreVideo || archivoVideo.files[0].name);
            }
        });

        // Subir un estudio (PDF/imagen)
        async function uploadFile(idCaso, archivo, nombreEstudio) {
            const containerMensaje = document.getElementById("container-mensaje-alerta");
            const btnUpdate      = document.getElementById('updateCaseBtn');
            const btnCloseUpdate = document.getElementById('closeUpdateBtn');
            const formData = new FormData();
            formData.append('archivo', archivo);
            formData.append('id_caso', idCaso);
            formData.append('nombre_estudio', nombreEstudio);

            // Deshabilitar el botón de actualizar mientras se sube el archivo
            btnUpdate.disabled = true;
            btnCloseUpdate.disabled = true;
            containerMensaje.innerHTML = `
                <div class="alert alert-warning d-flex justify-content-center align-items-center text-center" role="alert" style="height: 100%;">
                    <i class="fa-solid fa-spinner-third fa-spin"></i> Espere un momento, subiendo archivo(s)...
                </div>
            `;

            const xhr = new XMLHttpRequest();
            xhr.open('POST', './BE/uploadFile.php', true);
            xhr.upload.onprogress = e => { if (e.lengthComputable) console.log(`Progreso: ${((e.loaded/e.total)*100).toFixed(2)}%`); };
            xhr.onload = function () {
                try {
                    const resp = JSON.parse(xhr.responseText);
                    if (!resp.success) alert(`Error: ${resp.message || resp.error}`);
                    archivosSubidos++; checkAllUploadsDone();
                } catch(e) { archivosSubidos++; checkAllUploadsDone(); }
            };
            xhr.onerror = () => { archivosSubidos++; checkAllUploadsDone(); };
            xhr.send(formData);
        }

        // Subir un video con nombre editable
        async function uploadVideoFile(idCaso, archivo, nombreVideo) {
            const containerMensaje = document.getElementById("container-mensaje-alerta");
            const btnUpdate      = document.getElementById('updateCaseBtn');
            const btnCloseUpdate = document.getElementById('closeUpdateBtn');
            const formData = new FormData();
            formData.append('archivo', archivo);
            formData.append('id_caso', idCaso);
            formData.append('nombre_video', nombreVideo);

            btnUpdate.disabled = true;
            btnCloseUpdate.disabled = true;
            containerMensaje.innerHTML = `
                <div class="alert alert-warning d-flex justify-content-center align-items-center text-center" role="alert" style="height: 100%;">
                    <i class="fa-solid fa-spinner-third fa-spin"></i> Espere un momento, subiendo video(s)...
                </div>
            `;
            const xhr = new XMLHttpRequest();
            xhr.open('POST', './BE/uploadVideo.php', true);
            xhr.upload.onprogress = e => { if (e.lengthComputable) console.log(`Video: ${((e.loaded/e.total)*100).toFixed(2)}%`); };
            xhr.onload = function () {
                try {
                    const resp = JSON.parse(xhr.responseText);
                    if (!resp.success) alert(`Error al subir video: ${resp.message || resp.error}`);
                    archivosSubidos++; checkAllUploadsDone();
                } catch(e) { archivosSubidos++; checkAllUploadsDone(); }
            };
            xhr.onerror = () => { archivosSubidos++; checkAllUploadsDone(); };
            xhr.send(formData);
        }

    }

    // ** Funcionalidades para editar las preguntas e incisos del caso **

    function addQuestion() {
        questionCount++;
        const questionDiv = document.createElement('div');
        questionDiv.classList.add('pregunta-container');
        questionDiv.setAttribute('data-question-id', questionCount); // Identificador único por pregunta
        questionDiv.innerHTML = `
            <p class="p-pregunta">Pregunta ${questionCount}</p>
            <div class="input-group mb-3">
                <input type="text" class="form-control" placeholder="Escribe la pregunta" id="pregunta-${questionCount}">
            </div>
            <div id="options-${questionCount}" class="mb-3">
                <p class="p-incisos">Incisos</p>
                <p class="text-muted">Presiona ◉ para escoger el inciso correcto</p>
                <ul class="option-list inciso-container" id="option-list-${questionCount}">
                    <!-- Opciones generadas dinámicamente -->
                </ul>
                <div class="btn-box">
                    <button type="button" class="btn btn-warning btn-sm remove-option" data-question-id="${questionCount}">
                        <i class="fa-solid fa-minus"></i> Eliminar inciso
                    </button>
                    <button type="button" class="btn btn-primary btn-sm add-option" data-question-id="${questionCount}">
                        <i class="fa-solid fa-plus"></i> Agregar inciso
                    </button>
                </div>
            </div>
            <p class="p-feedback">Feedback</p>
            <div class="mt-3">
                <textarea class="form-control" placeholder="Escribe el feedback" id="feedback-${questionCount}" rows="3"></textarea>
            </div>
        `;
        preguntasContainer.appendChild(questionDiv);

        // Inicializa con al menos 2 opciones
        addOption(questionCount);
        addOption(questionCount);

        // Vincular eventos con los botones dinámicos usando el atributo data-question-id
        const addOptionBtn = questionDiv.querySelector('.add-option');
        const removeOptionBtn = questionDiv.querySelector('.remove-option');

        addOptionBtn.addEventListener('click', function () {
            const questionId = this.getAttribute('data-question-id'); // Obtener ID único de la pregunta
            addOption(questionId);
        });

        removeOptionBtn.addEventListener('click', function () {
            const questionId = this.getAttribute('data-question-id'); // Obtener ID único de la pregunta
            removeOption(questionId);
        });
    }

    function addOption(questionId) {
        const optionList = document.getElementById(`option-list-${questionId}`);
        if (optionList.children.length < 4) {
            const optionItem = document.createElement('li');
            optionItem.classList.add('option-item');
            optionItem.innerHTML = `
                <input type="radio" name="correct-answer-${questionId}" class="form-check-input me-2">
                <input type="text" class="form-control" placeholder="Escribe el inciso">
            `;
            optionList.appendChild(optionItem);
        } else {
            alert('Solo puedes agregar hasta 4 incisos.');
        }
    }

    function removeOption(questionId) {
        const optionList = document.getElementById(`option-list-${questionId}`);
        if (optionList.children.length > 2) {
            optionList.removeChild(optionList.lastElementChild);
        } else {
            alert('Debe haber al menos 2 incisos por pregunta.');
        }
    }

    function removeQuestion() {
        if (preguntasContainer.children.length > 1) {
            preguntasContainer.removeChild(preguntasContainer.lastElementChild);
            questionCount--;
        } else {
            alert('Debe haber al menos una pregunta.');
        }
    }

    // Eventos de botones
    addQuestionBtn.addEventListener('click', addQuestion);
    removeQuestionBtn.addEventListener('click', removeQuestion);

});

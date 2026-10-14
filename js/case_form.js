// Variables generales
var container = document.getElementById("container-form");
var progress = document.getElementById("progress");
const selectElements = document.querySelectorAll('.custom-select');
const placeholders = document.querySelectorAll('.select-placeholder');

// Función para validar el formulario
function validateForm(form) {
    let valid = true;
    const inputs = form.querySelectorAll('input:not(.search-box)[required], textarea[required], select[required]'); // Ignora la búsqueda

    // Validar inputs
    inputs.forEach(input => {
        if (!input.value.trim()) {
            valid = false;
            input.classList.add('error'); // Agrega clase de error
        } else {
            input.classList.remove('error'); // Quita clase de error si está lleno
        }

        // Evento que remueve el error en tiempo real cuando el usuario escribe
        input.addEventListener('input', () => {
            if (input.value.trim()) {
                input.classList.remove('error');
            }
        });
    });

    // Validar selección
    const selectedItemsContainers = form.querySelectorAll('.selected-items');
    selectedItemsContainers.forEach(container => {
        if (container.children.length === 0) {
            valid = false;
            container.classList.add('error');
        } else {
            container.classList.remove('error');
        }
    });

    return valid;
}

// Iterar sobre todos los select y aplicar los eventos a cada uno
selectElements.forEach((select, index) => {
    const placeholder = placeholders[index];

    // Detectar el foco en el select
    select.addEventListener('focus', function () {
        placeholder.classList.add('active-placeholder');
    });

    // Si el select pierde el foco y no tiene una opción seleccionada, restaurar el placeholder
    select.addEventListener('blur', function () {
        if (!select.value) {
            placeholder.classList.remove('active-placeholder');
        }
    });
});

// Ajustar altura del contenedor al cambiar de formulario
function ajustarAltura() {
    const activeForm = document.querySelector('form.active');
    const container = document.querySelector('.container');
    container.style.height = (activeForm.scrollHeight +
        `.container{
            padding-bottom: 0px;
        }
    `) + 'px';
}

// Agregar preguntas
function agregarPreguntas() {
    let questionCount = 0;
    const preguntasContainer = document.getElementById('preguntas-container');
    const addQuestionBtn = document.getElementById('add-question');
    const removeQuestionBtn = document.getElementById('remove-question');

    function addQuestion() {
        questionCount++;
        const questionDiv = document.createElement('div');
        questionDiv.classList.add('pregunta-container');
        questionDiv.setAttribute('data-question-id', questionCount);
        questionDiv.innerHTML = `
            <p class="p-pregunta">Pregunta ${questionCount}</p>
            <div class="input-group">
                <input type="text" required placeholder=" " id="pregunta-${questionCount}">
                <label for="pregunta-${questionCount}" class="form-label">Escribe la pregunta</label>
            </div>
            <div id="options-${questionCount}">
                <p class="p-incisos">Incisos</p>
                <p class="p-secundario">Presiona ◉ para escoger el inciso correcto</p>
                <ul class="option-list" id="option-list-${questionCount}"></ul>
                <div class="btn-box">
                    <button type="button" class="btn-remove remove-option" data-question="${questionCount}">
                        <i class="fa-solid fa-minus"></i>
                    </button>
                    <button type="button" class="btn-add add-option" data-question="${questionCount}">
                        <i class="fa-solid fa-plus"></i>
                    </button>
                </div>
            </div>
            <div class="textarea-group">
                <textarea id="feedback-${questionCount}" placeholder=" " required></textarea>
                <label for="feedback-${questionCount}" class="text-label">Feedback</label>
            </div>
        `;
        preguntasContainer.appendChild(questionDiv);

        const questionId = questionCount;
        addOption(questionId); // Inicializa con al menos 2 opciones
        addOption(questionId);

        questionDiv.querySelector('.add-option').addEventListener('click', function () {
            addOption(questionId);
        });

        questionDiv.querySelector('.remove-option').addEventListener('click', function () {
            removeOption(questionId);
        });
    }

    function removeQuestion() {
        if (preguntasContainer.children.length > 1) {
            preguntasContainer.removeChild(preguntasContainer.lastElementChild);
            questionCount--;
        } else {
            alert('Debe haber al menos una pregunta.');
        }
    }

    function addOption(questionId) {
        const optionList = document.getElementById(`option-list-${questionId}`);
        if (optionList.children.length < 4) {
            const optionItem = document.createElement('li');
            optionItem.classList.add('option-item');
            optionItem.innerHTML = `
                <input class="radio-correct" type="radio" name="correct-answer-${questionId}" required>
                <input type="text" placeholder="Escribe el inciso" required>
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

    // Eventos
    addQuestionBtn.addEventListener('click', addQuestion);
    removeQuestionBtn.addEventListener('click', removeQuestion);

    // Función que inicializa al cargar la página
    window.onload = function () {
        addQuestion(); // Crea una pregunta al cargar la página
    };
};

function validateAllQuestions() {
    const questions = document.querySelectorAll('.pregunta-container');
    let valid = true;

    questions.forEach(question => {
        const radioButtons = question.querySelectorAll('.radio-correct');
        let isOptionSelected = false;

        // Comprobar si al menos un radio está seleccionado
        radioButtons.forEach(radio => {
            if (radio.checked) {
                isOptionSelected = true;
            }
        });

        // Si no hay ningún radio seleccionado, no es válido
        if (!isOptionSelected) {
            valid = false;
            question.querySelector('.p-pregunta').style.color = 'red';
        } else {
            question.querySelector('.p-pregunta').style.color = '';
        }
    });

    if (!valid) {
        alert('Por favor, selecciona una respuesta correcta para cada pregunta.');
    }
    return valid;
}

function addFileInput() {
    const container = document.getElementById('studies-container');
    const studyDiv = document.createElement('div');
    studyDiv.className = 'study-item';
    // Añadir nombre del estudio y campo de archivo
    studyDiv.innerHTML = `
            <div class="input-group">
                <input type="text" id="nombre-estudio" required>
                <label for="nombre-estudio" class="form-label">Nombre del estudio</label>
                <input type="file" class="form-control" accept=".pdf, .jpeg, .jpg">
            </div>
        `;
    container.appendChild(studyDiv);
}

function removeFileInput() {
    const container = document.getElementById('studies-container');
    if (container.children.length > 1) {
        container.removeChild(container.lastElementChild);
    } else {
        alert('Debe haber al menos un estudio.');
    }
}

// ===== VIDEOS: agregar / quitar =====
function addVideoInput() {
    const container = document.getElementById('videos-container');
    const videoDiv = document.createElement('div');
    videoDiv.className = 'video-item';
    videoDiv.style.cssText = 'background:#f8f9ff;border:1px solid #dce0f3;border-radius:10px;padding:12px 14px;margin-bottom:10px;';
    videoDiv.innerHTML = `
        <div class="input-group" style="margin-bottom:8px;">
            <input type="text" placeholder="Nombre del video (ej: Clase 1, Referencia)" class="form-control video-nombre" style="border-radius:8px;">
        </div>
        <div style="display:flex;gap:8px;align-items:center;">
            <input type="file" class="form-control video-archivo" accept="video/*" style="flex:1;padding:8px;">
            <button type="button" class="btn-remove-video"
                style="background:#dc3545;color:white;border:none;border-radius:8px;padding:8px 12px;cursor:pointer;white-space:nowrap;">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    `;
    videoDiv.querySelector('.btn-remove-video').addEventListener('click', () => {
        container.removeChild(videoDiv);
    });
    container.appendChild(videoDiv);
}

// ===== CARPETAS: cargar y crear =====
async function cargarCarpetas() {
    try {
        const res = await fetch('./BE/get_carpetas.php');
        const data = await res.json();
        if (data.success && data.carpetas.length > 0) {
            const select = document.getElementById('carpeta-caso');
            data.carpetas.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.id;
                opt.textContent = c.nombre;
                select.appendChild(opt);
            });
        }
    } catch (e) {
        console.warn('No se pudieron cargar las carpetas:', e);
    }
}

document.addEventListener("DOMContentLoaded", () => {

    // Cargar carpetas al abrir el form
    cargarCarpetas();

    // Toggle nueva carpeta inline
    const btnNuevaCarpeta = document.getElementById('btn-nueva-carpeta-form');
    const inlineDiv       = document.getElementById('nueva-carpeta-inline');
    const btnCancelar     = document.getElementById('btn-cancelar-carpeta');
    const btnConfirmar    = document.getElementById('btn-confirmar-carpeta');

    if (btnNuevaCarpeta) {
        btnNuevaCarpeta.addEventListener('click', () => {
            inlineDiv.style.display = inlineDiv.style.display === 'none' ? 'block' : 'none';
        });
    }
    if (btnCancelar) {
        btnCancelar.addEventListener('click', () => {
            inlineDiv.style.display = 'none';
            document.getElementById('nueva-carpeta-nombre').value = '';
            document.getElementById('carpeta-form-feedback').textContent = '';
        });
    }
    if (btnConfirmar) {
        btnConfirmar.addEventListener('click', async () => {
            const nombre = document.getElementById('nueva-carpeta-nombre').value.trim();
            const feedback = document.getElementById('carpeta-form-feedback');
            if (!nombre) {
                feedback.textContent = 'El nombre de la carpeta es requerido.';
                feedback.style.color = '#c0392b';
                return;
            }
            btnConfirmar.disabled = true;
            feedback.textContent = 'Creando carpeta…';
            feedback.style.color = '#555';
            try {
                const res = await fetch('./BE/create_carpeta.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ nombre })
                });
                const data = await res.json();
                if (data.success) {
                    // Agregar al select y seleccionarla
                    const select = document.getElementById('carpeta-caso');
                    const opt = document.createElement('option');
                    opt.value = data.idCarpeta;
                    opt.textContent = data.nombre;
                    select.appendChild(opt);
                    select.value = data.idCarpeta;
                    // Cerrar el panel
                    inlineDiv.style.display = 'none';
                    document.getElementById('nueva-carpeta-nombre').value = '';
                    feedback.textContent = '';
                } else {
                    feedback.textContent = data.message || data.error || 'Error al crear carpeta.';
                    feedback.style.color = '#c0392b';
                }
            } catch (e) {
                feedback.textContent = 'Error de red. Intenta de nuevo.';
                feedback.style.color = '#c0392b';
            }
            btnConfirmar.disabled = false;
        });
    }

    // Constantes para los formularios
    const form1 = document.getElementById("form1");
    const form2 = document.getElementById("form2");
    const form3 = document.getElementById("form3");
    const form4 = document.getElementById("form4");
    const form5 = document.getElementById("form5");

    // Constantes para los botones de siguiente
    const next1 = document.getElementById("next1");
    const next2 = document.getElementById("next2");
    const next3 = document.getElementById("next3");
    const next4 = document.getElementById("next4");
    const next5 = document.getElementById("next5");

    // Constantes para los botones de regreso
    const back1 = document.getElementById("back1");
    const back2 = document.getElementById("back2");
    const back3 = document.getElementById("back3");
    const back4 = document.getElementById("back4");

    // Limpiar el contenedor de estudios
    const container = document.getElementById('studies-container');
    container.innerHTML = '';

    // Eventos para los botones de agregar y quitar estudios
    const addStudyBtn = document.getElementById('add-study');
    const removeStudyBtn = document.getElementById('remove-study');
    addStudyBtn.addEventListener('click', addFileInput);
    removeStudyBtn.addEventListener('click', removeFileInput);

    // Agregar videos
    const addVideoBtn = document.getElementById('add-video');
    if (addVideoBtn) addVideoBtn.addEventListener('click', addVideoInput);

    agregarPreguntas(); // Agregar preguntas al cargar la página
    addFileInput(); // Agregar un campo de archivo al cargar la página

    // Mostrar el primer formulario y ajustar la altura
    form1.classList.add('active');
    ajustarAltura();

    // Función para mostrar el formulario actual y ocultar los demás
    function showForm(currentForm, nextForm, progressWidth) {
        currentForm.classList.remove('active');
        nextForm.classList.add('active');
        progress.style.width = progressWidth;
        ajustarAltura();
    }

    //*********************************** */
    // ES PARA PRUEBA, BORRAR
    // showForm(form1, form3, "144px");
    // addFileInput();
    //*********************************** */

    // Eventos para los botones de siguiente y regreso

    next1.addEventListener("click", function () {
        if (validateForm(form1)) {
            showForm(form1, form2, "144px");
            progress.style.width = "144px";
        }
    });

    back1.addEventListener("click", function () {
        showForm(form2, form1, "0px");
        progress.style.width = "0px";
    });

    next2.addEventListener("click", function () {
        if (validateForm(form2)) {
            showForm(form2, form3, "288px");
            progress.style.width = "288px";
        }
    });

    back2.addEventListener("click", function () {
        showForm(form3, form2, "144px");
        progress.style.width = "144px";
    });

    next3.addEventListener("click", function (event) {
        event.preventDefault();
        if (validateForm(form3)) {
            showForm(form3, form4, "432px");
            progress.style.width = "432px";
        }
    });

    back3.addEventListener("click", function () {
        showForm(form4, form3, "288px");
        progress.style.width = "288px";
    });

    next4.addEventListener("click", function (event) {
        event.preventDefault(); // Previene la recarga de la página
        if (validateForm(form4) && validateAllQuestions()) {
            showForm(form4, form5, "720px");
            progress.style.width = "720px";
        }
    });

    back4.addEventListener("click", function () {
        showForm(form5, form4, "432px");
        progress.style.width = "432px";
    });

    next5.addEventListener("click", function () {
        collectData();  // Llamar a la función collectData
    });

    // Función para recopilar todos los datos y mostrar en consola
    async function collectData() {
        // Formulario 1: Descripción del caso
        const nombreCaso = document.getElementById("nombre-caso").value;
        const motivoConsulta = document.getElementById("motivo-consulta").value;
        const descripcion = document.getElementById("descripcion-caso").value;

        // Carpeta seleccionada (puede estar vacío o "0")
        let idCarpeta = document.getElementById('carpeta-caso') ? document.getElementById('carpeta-caso').value : '';
        if (idCarpeta === '0') {
            idCarpeta = '';
        }

        // Send case description and get case ID
        let caseId;
        try {
            const caseResponse = await fetch("./BE/uploadCase.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    nombreCaso,
                    descripcion,
                    motivoConsulta,
                    idCarpeta
                }),
            });
            const caseData = await caseResponse.json();
            if (!caseData.success) {
                alert(caseData.message || "Error al crear el caso.");
                if (caseData.message === 'No autorizado') window.location.href = './index.html';
                return;
            }
            caseId = caseData.idCaso;  // Assuming PHP returns idCaso as key
            console.log("Case ID:", caseId);
        } catch (error) {
            console.error("Error sending case description:", error);
            alert("Error de red o de servidor al guardar el caso.");
            return;
        }

        if (!caseId) {
            console.error("Case ID not returned from the server");
            alert("Error: El servidor no regresó un ID para el caso.");
            return;
        }

        // Formulario 2: Perfil del paciente
        const perfilPaciente = {
            nombrePaciente: document.getElementById("nombre-paciente").value,
            sexo: document.getElementById("sexo-paciente").value,
            edad: document.getElementById("edad-paciente").value,
            peso: document.getElementById("peso-paciente").value,
            altura: document.getElementById("altura-paciente").value,
            frecuenciaRespiratoria: document.getElementById("fr-paciente").value,
            frecuenciaCardiaca: document.getElementById("fc-paciente").value,
            tensionArterial: document.getElementById("ta-paciente").value,
            temperatura: document.getElementById("temp-paciente").value,
            saturacionOxigeno: document.getElementById("sat-paciente").value,
            enfermedades: document.getElementById("enfermedades-paciente").value,
            idCaso: caseId // Attach case ID
        };

        console.log("Perfil paciente:", perfilPaciente);
        
        const perfilResponse = await fetch("./BE/uploadCase.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({ perfilPaciente }),
        });
        
        const textResponse = await perfilResponse.text(); // Obtener la respuesta como texto
        console.log("Respuesta del servidor:", textResponse); // Ver qué devuelve el servidor
        
        try {
            const perfilData = JSON.parse(textResponse); // Intentar parsear manualmente
            if (!perfilData.success) {
                alert(perfilData.message || "Error al guardar el perfil del paciente.");
                return;
            }
            console.log("Perfil paciente:", perfilData);
        } catch (error) {
            console.error("Error parsing JSON:", error);
        }        

        // Formulario 3: Preguntas y respuestas
        const preguntasContainer = document.getElementById('preguntas-container');
        const preguntas = Array.from(preguntasContainer.children).map((preguntaElement) => {
            const preguntaTexto = preguntaElement.querySelector("input[type='text']").value;
            const feedback = preguntaElement.querySelector("textarea").value;

            const incisos = Array.from(preguntaElement.querySelectorAll(".option-item")).map((inciso) => ({
                texto: inciso.querySelector("input[type='text']").value,
                correcto: inciso.querySelector("input[type='radio']").checked
            }));

            return { preguntaTexto, feedback, incisos, idCaso: caseId };  // Include idCaso
        });

        // Send questions with options
        try {
            const preguntasResponse = await fetch("./BE/uploadCase.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({ preguntas }),
            });
            const preguntasData = await preguntasResponse.json();
            if (!preguntasData.success) {
                alert(preguntasData.message || "Error al guardar preguntas.");
                return;
            }
            console.log("Preguntas y respuestas:", preguntasData);
        } catch (error) {
            console.error("Error sending questions and answers:", error);
            alert("Error de red o de servidor al guardar preguntas.");
            return;
        }

        // Formulario 4: Estudios seleccionados
        const estudiosSeleccionados = Array.from(document.querySelectorAll("#studies-container .study-item")).map((studyItem, index) => {
            const nombreEstudio = studyItem.querySelector("#nombre-estudio").value;
            const archivoInput = studyItem.querySelector("input[type='file']");
            return { id: index, nombre: nombreEstudio, archivo: archivoInput };
        });

        // Videos del caso
        const videosContainer = document.getElementById('videos-container');
        const videosItems = videosContainer ? Array.from(videosContainer.querySelectorAll('.video-item')) : [];

        // Contar archivos a subir
        let archivosRequeridos = 0;
        estudiosSeleccionados.forEach(e => { if (e.archivo.files.length > 0) archivosRequeridos++; });
        videosItems.forEach(v => { if (v.querySelector('.video-archivo').files.length > 0) archivosRequeridos++; });

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

        // Subir estudios
        estudiosSeleccionados.forEach(async estudio => {
            if (estudio.archivo.files.length > 0) {
                await uploadFile(caseId, estudio.archivo.files[0], estudio.nombre);
            }
        });

        // Subir videos nuevos con nombre editable
        videosItems.forEach(async videoItem => {
            const archivoVideo = videoItem.querySelector('.video-archivo');
            const nombreVideo  = videoItem.querySelector('.video-nombre').value.trim();
            if (archivoVideo.files.length > 0) {
                await uploadVideoFile(caseId, archivoVideo.files[0], nombreVideo || archivoVideo.files[0].name);
            }
        });

        // Subir un estudio (PDF/imagen)
        async function uploadFile(idCaso, archivo, nombreEstudio) {
            const containerMensaje = document.getElementById("container-mensaje-alerta");
            const formData = new FormData();
            formData.append('archivo', archivo);
            formData.append('id_caso', idCaso);
            formData.append('nombre_estudio', nombreEstudio);

            back4.disabled = true;
            next5.disabled = true;
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
                    archivosSubidos++; checkAllUploadsDone();
                    if (!resp.success) console.error(resp.message || resp.error);
                } catch(e) { archivosSubidos++; checkAllUploadsDone(); }
            };
            xhr.onerror = () => { archivosSubidos++; checkAllUploadsDone(); };
            xhr.send(formData);
        }

        // Subir un video con nombre editable
        async function uploadVideoFile(idCaso, archivo, nombreVideo) {
            const containerMensaje = document.getElementById("container-mensaje-alerta");
            const formData = new FormData();
            formData.append('archivo', archivo);
            formData.append('id_caso', idCaso);
            formData.append('nombre_video', nombreVideo);

            back4.disabled = true;
            next5.disabled = true;
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
                    archivosSubidos++; checkAllUploadsDone();
                    if (!resp.success) console.error(resp.message || resp.error);
                } catch(e) { archivosSubidos++; checkAllUploadsDone(); }
            };
            xhr.onerror = () => { archivosSubidos++; checkAllUploadsDone(); };
            xhr.send(formData);
        }

    }

});

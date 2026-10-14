var form1 = document.getElementById("form1");
var form2 = document.getElementById("form2");
var form3 = document.getElementById("form3");

var next1 = document.getElementById("next1");
var next2 = document.getElementById("next2");
var back1 = document.getElementById("back1");
var back2 = document.getElementById("back2");

var container = document.getElementById("container-form");

var progress = document.getElementById("progress");

const selectElements = document.querySelectorAll('.custom-select');
const placeholders = document.querySelectorAll('.select-placeholder');

function showForm(currentForm, nextForm, progressWidth) {
    currentForm.classList.remove('active');
    nextForm.classList.add('active');
    progress.style.width = progressWidth;
    ajustarAltura(); // Ajustamos la altura del contenedor
}

next1.onclick = function() {
    if (validateForm(form1)) {
        showForm(form1, form2, "360px");
        progress.style.width = "360px";
    }
}

back1.onclick = function() {
    showForm(form2, form1, "0px");
    progress.style.width = "0px";
}

next2.onclick = function() {
    console.log("El botón 'Siguiente' del formulario 2 fue presionado");

    if (validateForm(form2) && validateRadioSelection(form2)) {
        // Ejecutar collectData() antes de avanzar
        collectData();

        // Mostrar el tercer formulario
        showForm(form2, form3, "720px");
        progress.style.width = "720px";
    }
}


back2.onclick = function() {
    showForm(form3, form2, "360px");
    progress.style.width = "360px";
}

function validateForm(form) {
    let valid = true;
    const inputs = form.querySelectorAll('input, textarea, select');

    inputs.forEach(input => {
        if (!input.value) {
            valid = false;
            input.classList.add('error'); // Agrega clase de error
        } else {
            input.classList.remove('error'); // Quita clase de error si está lleno
        }

        input.addEventListener('input', () => {
            if (input.value.trim()) {
                input.classList.remove('error');
            }
        });
    });

    return valid;
}

// CSS para resaltar los errores
const style = document.createElement('style');
style.innerHTML = `
    .error {
        border: 2px solid red;
    }
`;
document.head.appendChild(style);

function validateRadioSelection(form) {
    const radios = form.querySelectorAll('input[type="radio"]');
    let radioSelected = false;

    radios.forEach(radio => {
        if (radio.checked) {
            radioSelected = true;
        }
    });

    if (!radioSelected) {
        alert('Por favor, selecciona al menos una categoria antes de continuar.');
    }

    return radioSelected;
}

// Iterar sobre todos los select y aplicar los eventos a cada uno
selectElements.forEach((select, index) => {
    const placeholder = placeholders[index];

    // Detectar el foco en el select
    select.addEventListener('focus', function() {
        placeholder.classList.add('active-placeholder');
    });

    // Si el select pierde el foco y no tiene una opción seleccionada, restaurar el placeholder
    select.addEventListener('blur', function() {
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

// Al cargar la página, mostrar el primer formulario y ajustar altura
window.onload = function() {
    form1.classList.add('active');
    ajustarAltura();
}

function toggleDistractor(element) {
    element.classList.toggle('active');
    const parentCheckbox = element.previousElementSibling;
    parentCheckbox.checked = !parentCheckbox.checked;
}

function collectData() {
    const nombreEstudio = document.getElementById("nombre-Estudio").value;
    const descripcionEstudio = document.getElementById("descrip-Estudio").value;
    
    const tipoEstudioRadios = document.getElementsByName("tipoEstudio");
    let tipoEstudioSeleccionado = null;

    for (let i = 0; i < tipoEstudioRadios.length; i++) {
        if (tipoEstudioRadios[i].checked) {
            tipoEstudioSeleccionado = tipoEstudioRadios[i].value;
            break;
        }
    }

    const data = {
        nombreEstudio: nombreEstudio,
        descripcionEstudio: descripcionEstudio,
        tipoEstudio: tipoEstudioSeleccionado
    };

    fetch("./BE/uploadAnalisis.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        console.log("Datos insertados correctamente:", data);
    })
    .catch(error => {
        console.error("Error:", error);
    });
}
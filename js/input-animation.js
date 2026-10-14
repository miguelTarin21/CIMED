document.addEventListener('DOMContentLoaded', function () {
    // Seleccionamos el botón de iniciar simulación
    const btnSimulacion = document.querySelector('.btn-simulacion');

    // Estado para controlar si el input está visible
    let inputActive = false;

    // Guardamos el HTML original del botón para restaurarlo después
    const originalButtonHTML = btnSimulacion ? btnSimulacion.innerHTML : '';
    const originalButtonClasses = btnSimulacion ? btnSimulacion.className : '';
    const originalWidth = btnSimulacion ? btnSimulacion.offsetWidth : 0;

    // Verificamos si el botón existe
    if (btnSimulacion) {
        // Agregamos evento al botón
        btnSimulacion.addEventListener('click', function (e) {
            e.preventDefault();
            if (!inputActive) {
                transformToInput();
            }
        });


    }

    // Función para transformar el botón en input
    function transformToInput() {
        // Cambiar clase y estilo manteniendo el ancho original inicialmente
        btnSimulacion.className = 'input-container';
        btnSimulacion.style.width = (originalWidth + 5) + 'px';

        // Cambiamos el contenido al input y botón de enviar
        // Creamos un contenedor para el input que permita mostrar/ocultar nativamente
        btnSimulacion.innerHTML = `
            <div class="password-field-wrapper">
                <input type="password" class="form-control access-code" id="accessCode" placeholder="Ingrese clave de acceso" autocomplete="current-password">
                <button class="btn-toggle-password" id="togglePassword">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
            <button class="btn btn-continue" id="submitCode">
                <i class="bi bi-check-circle-fill"></i>
            </button>
            <div class="invalid-feedback" id="codeError">Clave no válida</div>
        `;


        // Animamos la transición
        btnSimulacion.style.transition = 'width 0.3s ease';

        // Expandimos después de un breve retraso
        setTimeout(() => {
            btnSimulacion.style.width = '300px';
        }, 10);

        // Enfocamos el input automáticamente después de un pequeño retraso
        setTimeout(() => {
            const accessCodeInput = document.getElementById('accessCode');
            accessCodeInput.focus();
        }, 300);

        // Agregamos evento al botón de enviar
        document.getElementById('submitCode').addEventListener('click', verifyCode);

        // Agregamos evento para verificar al presionar Enter
        document.getElementById('accessCode').addEventListener('keypress', function (event) {
            if (event.key === 'Enter') {
                verifyCode();
            }
        });

        document.getElementById('togglePassword').addEventListener('click', function () {
            const accessCodeInput = document.getElementById('accessCode');
            if (accessCodeInput.type === 'password') {
                accessCodeInput.type = 'text';
                this.innerHTML = '<i class="bi bi-eye-slash"></i>';
            } else {
                accessCodeInput.type = 'password';
                this.innerHTML = '<i class="bi bi-eye"></i>';
            }
        });


        // Actualizamos estado
        inputActive = true;
    }

    // Función para transformar el input de vuelta a botón
    function transformToButton() {
        // Si hay un error activo, ocultarlo primero
        const errorElement = document.getElementById('codeError');
        if (errorElement && errorElement.style.display === 'block') {
            errorElement.style.display = 'none';
        }

        // Animamos de vuelta al ancho original antes de cambiar el contenido
        btnSimulacion.style.transition = 'width 0.3s ease';
        btnSimulacion.style.width = (originalWidth + 5) + 'px';

        // Esperamos a que termine la animación para cambiar el contenido
        setTimeout(() => {
            btnSimulacion.className = originalButtonClasses;
            btnSimulacion.innerHTML = originalButtonHTML;
            btnSimulacion.style.transition = '';
            inputActive = false;
        }, 300);
    }

    function verifyCode() {
        const codeInput = document.getElementById('accessCode');
        const errorElement = document.getElementById('codeError');
        const accessCode = codeInput.value.trim();

        if (!accessCode) {
            showError(errorElement, codeInput);
            return;
        }

        const submitButton = document.getElementById('submitCode');
        const originalButtonContent = submitButton.innerHTML;
        submitButton.innerHTML = '<i class="bi bi-hourglass-split"></i>';
        submitButton.disabled = true;

        fetch('./BE/validate-key.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ key: accessCode })
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la conexión');
                }
                return response.json();
            })
            .then(data => {
                submitButton.innerHTML = originalButtonContent;
                submitButton.disabled = false;

                if (data.success) {
                    window.location.href = 'iniciar-simulacion.php';
                } else {
                    errorElement.textContent = data.message || 'Clave no válida';
                    showError(errorElement, codeInput);
                    // Si la clave es incorrecta o está vacía, cerramos el input
                    if (!accessCode.trim() || !data.success) {
                        transformToButton();
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                submitButton.innerHTML = originalButtonContent;
                submitButton.disabled = false;

                errorElement.textContent = 'Error de conexión. Intente nuevamente.';
                showError(errorElement, codeInput);
            });
    }


    // Función para mostrar el error con animación
    function showError(errorElement, inputElement) {
        errorElement.style.display = 'none'; // Oculta el mensaje de error
        inputElement.classList.add('error-placeholder');
        inputElement.placeholder = '⚠ Clave no valida'; // Cambia el texto del placeholder

        // Espera 2 segundos y luego restaura el placeholder original
        setTimeout(() => {
            inputElement.placeholder = 'Ingrese clave de acceso'; // Texto original
            inputElement.classList.remove('error-placeholder'); // Elimina la clase de error
        }, 2000); // 2000 ms = 2 segundos

        // Animamos el contenedor
        btnSimulacion.style.animation = 'shake 0.5s';
        setTimeout(() => {
            btnSimulacion.style.animation = '';
        }, 500);
    }
});

// Añadimos estilos necesarios
const style = document.createElement('style');
style.textContent = `
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
    20%, 40%, 60%, 80% { transform: translateX(5px); }
}

.input-container {
    display: flex;
    position: relative;
    margin-left: 65px;
    border-radius: 50px;
    overflow: hidden;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    background: white;
    border: 2px solid #489c5b;
}

.password-field-wrapper {
    flex-grow: 1;
    position: relative;
    /* Eliminamos restricciones que podrían ocultar el botón nativo */
}

.access-code {
    width: 100%;
    height: 100%;
    border: none !important;
    border-radius: 50px 0 0 50px !important;
    padding: 10px 40px 10px 15px; /* Añadimos padding derecho para el botón personalizado */
    font-family: 'Poppins', sans-serif;
    outline: none !important;
    box-shadow: none !important;
    /* Estilos para ocultar el botón nativo de mostrar/ocultar contraseña */
    -webkit-textfield-decoration-container: none !important;
    -webkit-appearance: none !important;
}

/* Ocultar el botón nativo en diferentes navegadores */
.access-code::-ms-reveal,
.access-code::-ms-clear {
    display: none !important;
    width: 0 !important;
    height: 0 !important;
}

.access-code::-webkit-credentials-auto-fill-button,
.access-code::-webkit-strong-password-auto-fill-button {
    display: none !important;
}

/* Para navegadores basados en Chromium */
.access-code[type="password"]::-webkit-textfield-decoration-container {
    display: none !important;
}

.access-code:focus {
    box-shadow: none !important;
    outline: none !important;
    border-bottom: none !important;
}

.access-code.is-invalid {
    border-right: none;
    background-position: right calc(0.375em + 0.1875rem) center;
}

.btn-continue {
    background: linear-gradient(90deg, #489c5b 0%, #57b578 100%);
    border: none;
    border-radius: 0 50px 50px 0;
    color: white;
    padding: 0 15px;
    min-width: 46px;
    transition: background 0.3s ease;
    z-index: 1; /* Aseguramos que esté por encima del input */
}

.btn-continue:hover {
    background: linear-gradient(90deg, #57b578 0%, #489c5b 100%);
}

.btn-continue:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.btn-toggle-password {
    background: transparent;
    border: none;
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: #489c5b;
    font-size: 18px;
    z-index: 10; /* Aseguramos que esté por encima del input */
    pointer-events: auto !important;
}

.btn-toggle-password:focus {
    outline: none;
}

.btn-toggle-password:hover {
    color: #57b578;
}

.error-placeholder::placeholder {
    color: #dc3545 !important; /* Rojo de error */
}
`;
document.head.appendChild(style);
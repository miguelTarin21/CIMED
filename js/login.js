// Función para agregar el modal de login al body del documento
function addLoginModal() {
    const modalHTML = `
    <!-- Modal del login -->
    <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <!-- Encabezado del modal -->
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title" id="loginModalLabel">Iniciar sesión</h5>
                </div>

                <!-- Cuerpo del modal -->
                <div class="modal-body">
                    <div id="login-form-section">
                        <form id="loginForm" method="POST">
                            <!-- Campo de usuario -->
                            <div class="form-floating mt-3 mb-5">
                                <input type="email" class="form-control" id="floatingInput" name="correo" placeholder="name@example.com">
                                <label for="floatingInput">Correo Electrónico</label>
                            </div>
                            <!-- Campo de contraseña -->
                            <div class="form-floating mb-4">
                                <input type="password" class="form-control" id="floatingPassword" name="contraseña" placeholder="Password">
                                <label for="floatingPassword">Contraseña</label>
                            </div>
                            <!-- Enlace para recuperar contraseña -->
                            <div class="text-center mt-4 mb-3">
                                <a href="#" id="forgot-password-link" class="text-decoration-none" style="font-size: 0.875rem; color: #007bff;">¿Olvidaste tu contraseña?</a>
                            </div>
                            <!-- Botón de acceso -->
                            <div class="d-flex justify-content-center mb-3">
                                <button class="w-30 btn btn-lg btn-primary btn-access" type="submit">Acceder</button>
                            </div>
                            <!-- Mensaje de error -->
                            <div id="error-message" class="text-danger text-center mt-2" style="color: red;"></div>
                        </form>
                    </div>
                </div>

                <!-- Sección de Recuperación de Contraseña (Oculta inicialmente) -->
                <div id="forgot-password-section" style="display: none;">
                    <div class="px-3">
                        <h6 class="text-center mb-5">Introduce tu correo electrónico para reestablecer tu contraseña</h6>
                        <form id="forgotPasswordForm" method="POST">
                            <div class="form-floating mb-3">
                                <input type="email" class="form-control" id="emailInput" name="correo" placeholder="name@example.com">
                                <label for="emailInput">Correo Electrónico</label>
                            </div>
                            <div class="d-flex justify-content-between mt-1 mb-5">
                                <button id="back-to-login" class="btn btn-sm btn-primary btn-back-login" type="button">
                                    Regresar
                                </button>
                                <button id="btn-recovery-password" class="btn btn-sm btn-primary btn-access" type="submit">
                                    Enviar
                                </button>
                            </div>
                            <!-- Mensaje de éxito/error para restablecimiento -->
                            <div id="reset-message" class="text-center mt-2 mb-3"></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>`;

    document.body.insertAdjacentHTML('beforeend', modalHTML);
}

// Función para mostrar el formulario de recuperación y ocultar el de login
function showForgotPasswordForm() {
    document.getElementById('login-form-section').style.display = 'none';
    document.getElementById('forgot-password-section').style.display = 'block';
}

// Función para mostrar el formulario del login
function showLoginForm() {
    document.getElementById('login-form-section').style.display = 'block';
    document.getElementById('forgot-password-section').style.display = 'none';
}

// Función para limpiar el contenido del modal
function clearModal() {
    // Limpiar el formulario de login
    document.getElementById('loginForm').reset();
    document.getElementById('error-message').textContent = '';
    // Limpiar el formulario de olvidé mi contraseña
    document.getElementById('forgotPasswordForm').reset();
    document.getElementById('reset-message').textContent = '';
    // Asegurarse de que solo el formulario de login esté visible al abrir
    document.getElementById('loginForm').style.display = 'block';
    document.getElementById('forgot-password-section').style.display = 'none';
}

// Función para verificar si hay una sesión activa
function verifySession() {
    fetch('./BE/check-session.php')
        .then(response => response.json())
        .then(data => {
            if (data.active) {
                // Si la sesión está activa, redirige al panel de administración
                window.location.href = 'admin-panel.php';
            } else {
                // Si no hay sesión activa, muestra el modal de inicio de sesión
                const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
                loginModal.show();
                showLoginForm();
            }
        })
        .catch(error => console.error('Error:', error));
}

// Llamar a la función para agregar el modal al cargar la página
window.addEventListener('DOMContentLoaded', (event) => {
    addLoginModal();

    // Asigna el evento de clic al botón "Administrador"
    document.querySelector('.btn-admin').addEventListener('click', function (event) {
        event.preventDefault(); // Evita el comportamiento predeterminado
        verifySession(); // Llama a la función de verificación de sesión
    });

    document.getElementById('back-to-login').addEventListener('click', function (event) {
        event.preventDefault();  // Prevenir el comportamiento de "submit"
        clearModal();  // Limpiar los formularios
        showLoginForm();  // Mostrar el formulario de login
    });


    // Manejador del evento para el formulario de login
    document.getElementById('loginForm').addEventListener('submit', function (event) {
        event.preventDefault();
        const formData = new FormData(this);

        fetch('./BE/login-valid.php', {
            method: 'POST',
            body: formData,
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'admin-panel.php';
                } else {
                    document.getElementById('error-message').textContent = data.message;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('error-message').textContent = 'Ocurrió un error. Por favor, intente nuevamente.';
            });
    });

    // Manejador de evento para el link "¿Olvidaste tu contraseña?"
    document.getElementById('forgot-password-link').addEventListener('click', function (event) {
        event.preventDefault();
        showForgotPasswordForm();
    });

    // Evento para resetear al inicio al cerrar el modal
    const loginModal = document.getElementById('loginModal');
    loginModal.addEventListener('hidden.bs.modal', function () {
        clearModal();
        showLoginForm();
    });

    // Manejador de evento para el formulario de recuperación de contraseña
    document.getElementById('forgotPasswordForm').addEventListener('submit', function (event) {
        event.preventDefault();  // Evita el envío del formulario

        const emailField = document.getElementById('emailInput');
        const btnRecovery = document.getElementById('btn-recovery-password');
        const resetMessage = document.getElementById('reset-message');

        if (!document.getElementById('forgot-password-section').style.display === 'block') {
            return;  // No hacer nada si no está visible
        }

        // Validar si el correo es válido
        if (!emailField.checkValidity()) {
            alert('Por favor, ingrese un correo electrónico válido.');
            return;  // Detiene la ejecución si el correo no es válido
        }

        const formData = new FormData(this);  // Recoge los datos del formulario

        // Deshabilitar el botón y mostrar mensaje de espera
        btnRecovery.disabled = true;
        resetMessage.style.color = 'black';
        resetMessage.textContent = 'Espere un momento...';

        // Realiza la solicitud fetch
        fetch('./BE/password-recovery.php', {
            method: 'POST',
            body: formData,  // Envia los datos como un FormData
        })
            .then(response => {
                // Asegurarse de que la respuesta sea un JSON válido
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor, intenta más tarde.');
                }
                return response.json();
            })
            .then(data => {
                // Verificar si la solicitud fue exitosa
                if (data.success) {
                    resetMessage.textContent = 'Se ha enviado un correo con un enlace de recuperación.';
                    resetMessage.style.color = 'green';
                } else {
                    resetMessage.textContent = data.message;
                    resetMessage.style.color = 'red';
                }
            })
            .catch(error => {
                resetMessage.textContent = 'Ingrese un correo electrónico válido.';
                resetMessage.style.color = 'red';
                console.error('Error:', error);
            })
            .finally(() => {
                btnRecovery.disabled = false; // Habilita el botón
                emailField.value = '';  // Limpia el campo de correo
            });
    });

    // Limpiar el modal al cerrarse
    document.getElementById('loginModal').addEventListener('hidden.bs.modal', clearModal);
});
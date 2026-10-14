document.addEventListener('DOMContentLoaded', function() {
    let adminId = null;

    // Abrir modal al hacer clic en el botón de añadir usuario
    document.querySelectorAll('.person-add-btn').forEach(button => {
        button.addEventListener('click', function() {
            adminId = this.getAttribute('data-id');
            const registerModal = new bootstrap.Modal(document.getElementById('registerAdminModal'));
            registerModal.show();
        });
    });

    // Referencia al botón de enviar invitación
    const sendInvitationBtn = document.getElementById('sendInvitationBtn');

    if (sendInvitationBtn) {
        sendInvitationBtn.addEventListener('click', function() {
            const adminEmail = document.getElementById('adminEmail').value.trim();
            const emailFeedback = document.getElementById('emailFeedback');

            // Validar el correo electrónico
            if (!isValidEmail(adminEmail)) {
                showValidationError(emailFeedback, 'Por favor ingrese un correo electrónico válido.');
                return;
            }

            // Si es válido, ocultar mensaje de error
            emailFeedback.classList.add('d-none');

            // Configurar estado de carga
            setButtonLoadingState(sendInvitationBtn, true, 'Enviando...');

            // Enviar solicitud AJAX
            fetch('BE/admin-register-send.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'correo=' + encodeURIComponent(adminEmail)
            })
            .then(response => response.json())
            .then(data => {
                setButtonLoadingState(sendInvitationBtn, false, 'Enviar invitación');
                
                if (data.success) {
                    handleSuccessResponse(adminEmail);
                } else {
                    showAlertAndCloseModal('danger', data.message || 'Error al enviar la invitación.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                setButtonLoadingState(sendInvitationBtn, false, 'Enviar invitación');
                showAlertAndCloseModal('danger', 'Error de conexión. Inténtelo nuevamente.');
            });
        });
    }

    // Función para validar email
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    // Función para mostrar errores de validación
    function showValidationError(feedbackElement, message) {
        feedbackElement.classList.remove('d-none');
        feedbackElement.textContent = message;
    }

    // Función para manejar estado del botón
    function setButtonLoadingState(button, isLoading, text) {
        button.disabled = isLoading;
        button.innerHTML = isLoading 
            ? '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' + text
            : text;
    }

    // Función para manejar respuesta exitosa
    function handleSuccessResponse(email) {
        showAlert('success', 'Invitación enviada correctamente. El administrador recibirá un correo con instrucciones.');
        const modal = bootstrap.Modal.getInstance(document.getElementById('registerAdminModal'));
        if (modal) modal.hide();
        document.getElementById('adminEmail').value = '';
    }

    // Función para mostrar alertas y cerrar modal (para errores)
    function showAlertAndCloseModal(type, message) {
        const modal = bootstrap.Modal.getInstance(document.getElementById('registerAdminModal'));
        if (modal) modal.hide();
        showAlert(type, message);
    }

    // Función para mostrar alertas
    function showAlert(type, message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.role = 'alert';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        const container = document.querySelector('.container') || document.body;
        container.insertBefore(alertDiv, container.firstChild);

        setTimeout(() => {
            alertDiv.classList.remove('show');
            setTimeout(() => alertDiv.remove(), 150);
        }, 5000);
    }
});
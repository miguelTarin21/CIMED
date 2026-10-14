document.addEventListener('DOMContentLoaded', function () {
    let adminId = null;

    // Abrir el modal al hacer clic en el botón del engranaje
    document.querySelectorAll('.gear-btn').forEach(button => {
        button.addEventListener('click', function () {
            adminId = this.getAttribute('data-id');
            const gearModal = new bootstrap.Modal(document.getElementById('updatePasswordModal'));
            gearModal.show();
        });
    });

    // Cambiar la contraseña
    document.getElementById('UpdatePasswordBtn').addEventListener('click', function () {
        const currentPassword = document.getElementById('currentPassword').value;
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;

        // Verificar que no haya campos vacíos
        if (!currentPassword || !newPassword || !confirmPassword) {
            showAlertAndCloseModal('danger', 'Por favor, llene todos los campos');
            return;
        }

        // Verificar que la nueva contraseña y la confirmación coinciden
        if (newPassword !== confirmPassword) {
            showAlertAndCloseModal('danger', 'La nueva contraseña y la confirmación no coinciden');
            return;
        }

        // Mostrar estado de carga
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando...';

        // Realizar la solicitud para cambiar la contraseña
        fetch('./BE/update_password.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${adminId}&currentPassword=${encodeURIComponent(currentPassword)}&newPassword=${encodeURIComponent(newPassword)}`
        })
        .then(response => response.json())
        .then(data => {
            // Restaurar botón
            btn.disabled = false;
            btn.innerHTML = 'Actualizar';
            
            if (data.success) {
                // Cerrar el modal
                const gearModal = bootstrap.Modal.getInstance(document.getElementById('updatePasswordModal'));
                gearModal.hide();
                showAlert('success', 'Contraseña actualizada con éxito');
                
                // Limpiar campos
                document.getElementById('currentPassword').value = '';
                document.getElementById('newPassword').value = '';
                document.getElementById('confirmPassword').value = '';
            } else {
                showAlertAndCloseModal('danger', data.message || 'Error al cambiar la contraseña');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            btn.disabled = false;
            btn.innerHTML = 'Actualizar';
            showAlertAndCloseModal('danger', 'Error en la solicitud AJAX');
        });
    });

    // Función para mostrar alertas y cerrar el modal (solo para errores)
    function showAlertAndCloseModal(type, message) {
        const gearModal = bootstrap.Modal.getInstance(document.getElementById('updatePasswordModal'));
        if (gearModal) {
            gearModal.hide();
        }
        showAlert(type, message);
    }

    // Función para mostrar alertas en la interfaz
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
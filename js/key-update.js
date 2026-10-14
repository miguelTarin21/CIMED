document.addEventListener('DOMContentLoaded', function () {
    let adminId = null;

    // Abrir modal al hacer clic en el botón de clave
    document.querySelectorAll('.key-btn').forEach(button => {
        button.addEventListener('click', function () {
            adminId = this.getAttribute('data-id');
            const keyModal = new bootstrap.Modal(document.getElementById('updateKeyModal'));
            keyModal.show();
        });
    });

    // Evento para cambiar la clave
    document.getElementById('UpdateKeyBtn').addEventListener('click', function () {
        const currentKey = document.getElementById('currentKey').value;
        const newKey = document.getElementById('newKey').value;
        const confirmKey = document.getElementById('confirmKey').value;
        
        // Mostrar estado de carga
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando...';

        // Verificar campos vacíos
        if (!currentKey || !newKey || !confirmKey) {
            resetButtonAndCloseModal(btn, 'Por favor, llene todos los campos');
            return;
        }

        // Verificar que la nueva clave no sea igual a la contraseña
        if (newKey === currentKey) {
            resetButtonAndCloseModal(btn, 'La nueva clave no puede ser igual a la contraseña');
            return;
        }

        // Verificar longitud mínima de clave
        if (newKey.length < 5) {
            resetButtonAndCloseModal(btn, 'La clave debe tener al menos 5 caracteres');
            return;
        }

        // Verificar coincidencia de claves
        if (newKey !== confirmKey) {
            resetButtonAndCloseModal(btn, 'La nueva clave y la confirmación no coinciden');
            return;
        }

        // Realizar solicitud para cambiar la clave
        fetch('./BE/update_key.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${adminId}&currentKey=${encodeURIComponent(currentKey)}&newKey=${encodeURIComponent(newKey)}`
        })
        .then(response => response.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = 'Actualizar';
            
            if (data.success) {
                const keyModal = bootstrap.Modal.getInstance(document.getElementById('updateKeyModal'));
                keyModal.hide();
                showAlert('success', 'Clave actualizada con éxito');
                
                // Limpiar campos
                document.getElementById('currentKey').value = '';
                document.getElementById('newKey').value = '';
                document.getElementById('confirmKey').value = '';
            } else {
                resetButtonAndCloseModal(btn, data.message || 'Error al cambiar la clave');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            resetButtonAndCloseModal(btn, 'Error en la solicitud AJAX');
        });
    });

    // Función para resetear botón y cerrar modal con alerta de error
    function resetButtonAndCloseModal(btn, message) {
        btn.disabled = false;
        btn.innerHTML = 'Actualizar';
        const keyModal = bootstrap.Modal.getInstance(document.getElementById('updateKeyModal'));
        if (keyModal) {
            keyModal.hide();
        }
        showAlert('danger', message);
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
document.addEventListener('DOMContentLoaded', function () {
    let caseIdToDelete = null;

    // Abrir el modal al hacer clic en el botón de eliminar
    document.querySelectorAll('.delete-case-btn').forEach(button => {
        button.addEventListener('click', function () {
            caseIdToDelete = this.getAttribute('data-id');
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        });
    });

    // Confirmar la eliminación
    document.getElementById('confirmDeleteBtn').addEventListener('click', function () {
        if (caseIdToDelete) {
            fetch('./BE/delete_case.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${caseIdToDelete}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Eliminar el elemento del DOM
                        document.querySelector(`.delete-case-btn[data-id="${caseIdToDelete}"]`).closest('.case-card').remove();
                        // Cerrar el modal
                        const deleteModal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
                        deleteModal.hide();
                        // Recargar la página
                        location.reload();
                    } else {
                        console.error(data);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error en la solicitud AJAX');
                });
        }
    });
});
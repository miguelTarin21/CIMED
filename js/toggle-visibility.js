document.addEventListener("DOMContentLoaded", function () {
    const visibilityButtons = document.querySelectorAll(".toggle-visibility");

    visibilityButtons.forEach((button) => {
        button.addEventListener("click", function () {
            const caseId = this.getAttribute("data-id");
            const currentVisibilidad = parseInt(
                this.getAttribute("data-visibilidad")
            );

            // Realizar la solicitud AJAX
            fetch("./BE/update_visibility.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                body: `id=${caseId}&visibilidad=${currentVisibilidad}`,
            })
                .then((response) => response.text()) // Cambiar a .text() para inspeccionar
                .then((data) => {
                    try {
                        const jsonData = JSON.parse(data);
                        if (jsonData.success) {
                            // Actualizar el icono y el atributo data-visibilidad
                            button.setAttribute("data-visibilidad", jsonData.newVisibilidad);
                            const icon = button.querySelector("i");
                            if (jsonData.newVisibilidad === 0) {
                                icon.classList.remove("bi-eye-fill");
                                icon.classList.add("bi-eye-slash");
                            } else {
                                icon.classList.remove("bi-eye-slash");
                                icon.classList.add("bi-eye-fill");
                            }
                        } else {
                            alert("Error al actualizar la visibilidad");
                        }
                    } catch (e) {
                        console.error("Respuesta no válida del servidor:", data);
                        alert("Error: respuesta no válida del servidor");
                    }
                })
                .catch((error) => {
                    console.error("Error:", error);
                    alert("Error en la solicitud AJAX");
                });
        });
    });
});

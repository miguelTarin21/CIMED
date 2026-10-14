document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const correoInput = document.getElementById('correo');
    const contraseñaInput = document.getElementById('contraseña');
    const claveInput = document.getElementById('clave');
    
    // Referencias a los mensajes de retroalimentación
    const correoFeedback = document.getElementById('correo-feedback');
    const contraseñaFeedback = document.getElementById('contraseña-feedback');
    const claveFeedback = document.getElementById('clave-feedback');
    
    // Función para validar correo
    function validarCorreo(correo) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo);
    }
    
    // Función para validar clave
    function validarClave(clave) {
        return /^[a-zA-Z0-9]+$/.test(clave);
    }
    
    // Función para mostrar un mensaje de validación
    function mostrarValidacion(input, feedbackElement, mensaje, esValido) {
        if (esValido) {
            input.classList.remove('input-error');
            feedbackElement.classList.remove('show');
        } else {
            input.classList.add('input-error');
            feedbackElement.innerHTML = `<i class="bi bi-exclamation-circle feedback-icon"></i> ${mensaje}`;
            feedbackElement.classList.add('show');
        }
    }
    
    // Validación en tiempo real para correo
    correoInput.addEventListener('input', function() {
        if (this.value.length > 0) {
            const esValido = validarCorreo(this.value);
            mostrarValidacion(
                this, 
                correoFeedback, 
                'Por favor ingrese un correo válido', 
                esValido
            );
        } else {
            this.classList.remove('input-error');
            correoFeedback.classList.remove('show');
        }
    });
    
    // Validación en tiempo real para contraseña
    contraseñaInput.addEventListener('input', function() {
        if (this.value.length > 0) {
            const esValido = this.value.length >= 5;
            mostrarValidacion(
                this, 
                contraseñaFeedback, 
                'La contraseña debe tener al menos 5 caracteres', 
                esValido
            );
        } else {
            this.classList.remove('input-error');
            contraseñaFeedback.classList.remove('show');
        }
    });
    
    // Validación en tiempo real para clave
    claveInput.addEventListener('input', function() {
        if (this.value.length > 0) {
            let mensaje = '';
            let esValido = true;
            
            if (this.value.length < 5) {
                mensaje = 'La clave debe tener al menos 5 caracteres';
                esValido = false;
            } else if (!validarClave(this.value)) {
                mensaje = 'La clave no debe contener caracteres especiales';
                esValido = false;
            }
            
            mostrarValidacion(this, claveFeedback, mensaje, esValido);
            // Validar que no sea igual a la contraseña
            if (contraseñaInput.value && this.value === contraseñaInput.value) {
                mostrarValidacion(
                    this,
                    claveFeedback,
                    'La clave y la contraseña no pueden ser iguales',
                    false
                );
            }
        } else {
            this.classList.remove('input-error');
            claveFeedback.classList.remove('show');
        }
        
        validarNoIguales();
    });
    
    // Validación al enviar
    form.addEventListener('submit', function(e) {
        let isValid = true;
        
        // Resetear todos los errores
        document.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));
        document.querySelectorAll('.validation-feedback').forEach(el => el.classList.remove('show'));
        
        // Validar correo
        if (!validarCorreo(correoInput.value)) {
            mostrarValidacion(
                correoInput, 
                correoFeedback, 
                'Por favor ingrese un correo válido', 
                false
            );
            isValid = false;
        }
        
        // Validar contraseña
        if (contraseñaInput.value.length < 5) {
            mostrarValidacion(
                contraseñaInput, 
                contraseñaFeedback, 
                'La contraseña debe tener al menos 5 caracteres', 
                false
            );
            isValid = false;
        }
        
        // Validar clave
        if (claveInput.value.length < 5) {
            mostrarValidacion(
                claveInput, 
                claveFeedback, 
                'La clave debe tener al menos 5 caracteres', 
                false
            );
            isValid = false;
        } else if (!validarClave(claveInput.value)) {
            mostrarValidacion(
                claveInput, 
                claveFeedback, 
                'La clave no debe contener caracteres especiales', 
                false
            );
            isValid = false;
        }
    });
    
    // Función para mostrar mensaje de error general
    function mostrarMensajeError(mensaje) {
        let errorDiv = document.getElementById('form-error-message');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.id = 'form-error-message';
            errorDiv.className = 'response-message error-message';
            document.querySelector('.register-title').insertAdjacentElement('afterend', errorDiv);
        }
        errorDiv.innerHTML = `<i class="bi bi-exclamation-triangle"></i> ${mensaje}`;
        
        // Eliminar el mensaje después de un tiempo
        setTimeout(() => {
            errorDiv.classList.add('fade-out');
            setTimeout(() => {
                errorDiv.remove();
            }, 500);
        }, 3000);
    }
});
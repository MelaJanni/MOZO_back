<script>
document.addEventListener('DOMContentLoaded', function() {
    // Agregar indicadores de loading para campos live
    const liveFields = document.querySelectorAll('[wire\\:model\\.live], [x-data*="live"]');

    liveFields.forEach(field => {
        field.addEventListener('input', function() {
            // Agregar clase de loading
            this.closest('.fi-fo-field-wrp')?.classList.add('fi-field-saving');

            // Remover clase después de un tiempo
            setTimeout(() => {
                this.closest('.fi-fo-field-wrp')?.classList.remove('fi-field-saving');
            }, 2000);
        });

        field.addEventListener('change', function() {
            // Para selects y toggles
            this.closest('.fi-fo-field-wrp')?.classList.add('fi-field-saving');

            setTimeout(() => {
                this.closest('.fi-fo-field-wrp')?.classList.remove('fi-field-saving');
            }, 2000);
        });
    });

    // Mejorar botones de acción
    const actionButtons = document.querySelectorAll('[data-action], .fi-btn');

    actionButtons.forEach(button => {
        button.addEventListener('click', function() {
            // No aplicar a botones de cancelar o cerrar
            if (this.textContent.includes('Cancelar') || this.textContent.includes('Cerrar')) {
                return;
            }

            // Agregar spinner al botón
            const originalText = this.innerHTML;
            const spinner = '<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';

            this.innerHTML = spinner + 'Procesando...';
            this.disabled = true;

            // Restaurar después de un tiempo
            setTimeout(() => {
                this.innerHTML = originalText;
                this.disabled = false;
            }, 3000);
        });
    });
});

// Estilo CSS adicional para la experiencia de loading
const style = document.createElement('style');
style.textContent = `
    .fi-field-saving {
        position: relative;
    }

    .fi-field-saving::before {
        content: '';
        position: absolute;
        top: -2px;
        left: -2px;
        right: -2px;
        bottom: -2px;
        border: 2px solid #3b82f6;
        border-radius: 6px;
        animation: fi-pulse-border 1.5s ease-in-out infinite;
        z-index: 1;
        pointer-events: none;
    }

    @keyframes fi-pulse-border {
        0%, 100% {
            border-color: #3b82f6;
            opacity: 0.6;
        }
        50% {
            border-color: #1d4ed8;
            opacity: 1;
        }
    }

    .fi-btn:disabled {
        opacity: 0.7;
        cursor: not-allowed;
    }

    /* Mejorar las notificaciones */
    .fi-no-content {
        z-index: 9999;
    }

    /* Estilo para campos que están guardando */
    .fi-field-saving input,
    .fi-field-saving select,
    .fi-field-saving textarea {
        background-color: rgba(59, 130, 246, 0.05);
        border-color: #3b82f6;
    }
`;
document.head.appendChild(style);
</script>
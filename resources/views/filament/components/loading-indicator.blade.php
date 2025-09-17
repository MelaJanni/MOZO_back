<div class="fi-loading-indicator">
    <style>
        .fi-loading-indicator {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: #6b7280;
        }

        .fi-spinner {
            width: 1rem;
            height: 1rem;
            border: 2px solid #e5e7eb;
            border-top: 2px solid #3b82f6;
            border-radius: 50%;
            animation: fi-spin 1s linear infinite;
        }

        @keyframes fi-spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .fi-loading-text {
            margin-left: 0.25rem;
        }

        /* Estado de carga para campos */
        .fi-field-loading {
            opacity: 0.7;
            pointer-events: none;
            position: relative;
        }

        .fi-field-loading::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            z-index: 10;
        }

        /* Pulso para indicar que algo está pasando */
        .fi-field-saving {
            animation: fi-pulse 1.5s ease-in-out infinite;
        }

        @keyframes fi-pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
    </style>

    <div class="fi-spinner"></div>
    <span class="fi-loading-text">{{ $message ?? 'Cargando...' }}</span>
</div>
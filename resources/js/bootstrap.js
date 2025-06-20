/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
    wsHost: import.meta.env.VITE_PUSHER_HOST ?? window.location.hostname,
    wsPort: import.meta.env.VITE_PUSHER_PORT ?? 6001,
    wssPort: import.meta.env.VITE_PUSHER_PORT ?? 6001,
    forceTLS: false,
    enabledTransports: ['ws', 'wss'],
});

// ---------------------------------------------------------------------
// SUSCRIPCIÓN A NOTIFICACIONES DEL USUARIO AUTENTICADO
// ---------------------------------------------------------------------

// Requiere exponer el ID del usuario en un meta-tag:
// <meta name="user-id" content="{{ auth()->id() }}">

document.addEventListener('DOMContentLoaded', () => {
    const meta = document.querySelector('meta[name="user-id"]');
    if (!meta) return;

    const userId = meta.content;

    window.Echo.private(`App.Models.User.${userId}`)
        .notification((notification) => {
            // Emitir evento global para que los componentes lo escuchen
            window.dispatchEvent(new CustomEvent('notification-received', {
                detail: notification,
            }));
        });
});

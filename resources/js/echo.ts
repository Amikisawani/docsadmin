import Echo from 'laravel-echo';
import { useAuthStore } from './stores/auth';

/**
 * Initialisation de Laravel Echo pour les notifications temps réel.
 *
 * Le broadcast driver est « reverb » ou « pusher ». Si aucune clé
 * applicative n'est configurée (ex. BROADCAST_CONNECTION=log en dev),
 * on renvoie `null` et l'application retombe sur le polling classique.
 */
let echoInstance: Echo | null = null;

export function getEchoToken(): string | null {
    const authStore = useAuthStore();
    return authStore.token || localStorage.getItem('token');
}

export function initEcho(): Echo | null {
    if (echoInstance) return echoInstance;

    const key = import.meta.env.VITE_REVERB_APP_KEY || import.meta.env.VITE_PUSHER_APP_KEY;
    if (!key) {
        // Aucun serveur de diffusion configuré : on désactive le temps réel.
        return null;
    }

    const host = import.meta.env.VITE_REVERB_HOST || import.meta.env.VITE_PUSHER_HOST || window.location.hostname;
    const port = import.meta.env.VITE_REVERB_PORT || import.meta.env.VITE_PUSHER_PORT || 8080;
    const scheme = import.meta.env.VITE_REVERB_SCHEME || import.meta.env.VITE_PUSHER_SCHEME || 'http';

    echoInstance = new Echo({
        broadcaster: import.meta.env.VITE_BROADCAST_DRIVER === 'pusher' ? 'pusher' : 'reverb',
        key,
        wsHost: host,
        wsPort: port,
        wssPort: port,
        forceTLS: scheme === 'https',
        enabledTransports: ['ws', 'wss'],
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                Authorization: `Bearer ${getEchoToken()}`,
                Accept: 'application/json',
            },
        },
    });

    return echoInstance;
}

export function getEcho(): Echo | null {
    return echoInstance;
}

export function destroyEcho(): void {
    if (echoInstance) {
        echoInstance.disconnect();
        echoInstance = null;
    }
}

export default initEcho;

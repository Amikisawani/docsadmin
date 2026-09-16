import axios from 'axios';

const apiClient = axios.create({
    baseURL: '/api/v1',
    headers: {
        'Accept': 'application/json',
    },
});

let redirectingToLogin = false;

function isPublicAuthRequest(url?: string): boolean {
    const path = String(url ?? '');
    return /\/auth\/(login|forgot-password|reset-password|director-pending-count)(?:\?|$)/.test(path);
}

apiClient.interceptors.request.use((config) => {
    const token = localStorage.getItem('token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    // Quand on envoie un FormData (upload fichier), on laisse axios/navigateur
    // poser le Content-Type multipart avec le boundary adéquat.
    // Sinon on force le JSON pour les appels API classiques.
    if (config.data instanceof FormData) {
        if (typeof config.headers?.set === 'function') {
            config.headers.set('Content-Type', null);
        }
        delete config.headers['Content-Type'];
    } else {
        config.headers['Content-Type'] = 'application/json';
    }
    return config;
});

apiClient.interceptors.response.use(
    (response) => response,
    (error) => {
        const status = error.response?.status;
        const requestUrl = error.config?.url as string | undefined;
        const onLoginPage = typeof window !== 'undefined' && window.location.pathname === '/login';

        // Un 401 sur la page de login (ex. compteur directeur sans token) ne
        // doit pas relancer un rechargement complet : sinon la page d'accueil
        // s'actualise en boucle.
        if (status === 401 && !isPublicAuthRequest(requestUrl) && !onLoginPage && !redirectingToLogin) {
            redirectingToLogin = true;
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            window.location.replace('/login');
        }
        return Promise.reject(error);
    }
);

export default apiClient;


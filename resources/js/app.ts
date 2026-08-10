import { createApp } from 'vue';
import { createPinia } from 'pinia';
import router from './router';
import App from './App.vue';
import { useAuthStore } from './stores/auth';
import { useNotificationStore } from './stores/notification';
import { useDirectorInboxStore } from './stores/directorInbox';
import { initEcho, getEcho, destroyEcho } from './echo';

const app = createApp(App);

app.use(createPinia());
app.use(router);

// Rustine pour les types Vite (import.meta.env)
interface ImportMetaEnv {
    VITE_REVERB_APP_KEY?: string;
    VITE_REVERB_HOST?: string;
    VITE_REVERB_PORT?: string;
    VITE_REVERB_SCHEME?: string;
    VITE_PUSHER_APP_KEY?: string;
    VITE_PUSHER_HOST?: string;
    VITE_PUSHER_PORT?: string;
    VITE_PUSHER_SCHEME?: string;
    VITE_BROADCAST_DRIVER?: string;
}

// Connexion temps réel après authentification
router.beforeEach((to, _from, next) => {
    const authStore = useAuthStore();
    const notifStore = useNotificationStore();

    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        destroyEcho();
        return next('/login');
    }

    if (to.meta.requiresAuth && authStore.isAuthenticated) {
        const echo = getEcho() || initEcho();
        if (echo) {
            notifStore.startRealtime(echo);
            if (authStore.hasRole('directeur_cabinet')) {
                const inboxStore = useDirectorInboxStore();
                inboxStore.bindRealtime(echo);
            }
        } else {
            notifStore.startPolling();
        }
        notifStore.fetchUnreadCount();
    }

    next();
});

app.mount('#app');

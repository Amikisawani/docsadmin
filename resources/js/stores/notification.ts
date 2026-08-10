import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import apiClient from '../utils/axios';
import type { AppNotification } from '../types';
import type Echo from 'laravel-echo';
import { useAuthStore } from './auth';

export const useNotificationStore = defineStore('notification', () => {
    const notifications = ref<AppNotification[]>([]);
    const unreadCount = ref(0);
    const loading = ref(false);
    let pollingInterval: ReturnType<typeof setInterval> | null = null;
    let realtimeBound = false;

    const unreadNotifications = computed(() =>
        notifications.value.filter(n => !n.is_read)
    );

    async function fetchNotifications() {
        loading.value = true;
        try {
            const { data } = await apiClient.get('/notifications', {
                params: { per_page: 50 },
            });
            notifications.value = data.data.data || [];
            unreadCount.value = notifications.value.filter(n => !n.is_read).length;
        } catch (e) {
            console.error(e);
        } finally {
            loading.value = false;
        }
    }

    async function fetchUnreadCount() {
        try {
            const { data } = await apiClient.get('/notifications/unread-count');
            unreadCount.value = data.data.unread_count || 0;
        } catch (e) {
            console.error(e);
        }
    }

    async function markAsRead(id: string) {
        try {
            await apiClient.post(`/notifications/${id}/read`);
            const notif = notifications.value.find(n => n.id === id);
            if (notif) {
                notif.is_read = true;
                notif.read_at = new Date().toISOString();
                unreadCount.value = Math.max(0, unreadCount.value - 1);
            }
        } catch (e) {
            console.error(e);
        }
    }

    async function markAllAsRead() {
        try {
            await apiClient.post('/notifications/mark-all-read');
            notifications.value.forEach(n => {
                n.is_read = true;
                n.read_at = new Date().toISOString();
            });
            unreadCount.value = 0;
        } catch (e) {
            console.error(e);
        }
    }

    /**
     * Branche les événements temps réel (WebSocket) sur Echo.
     * Écoute les événements émis par le backend :
     *   - document.submitted  (envoyé à la signature)
     *   - document.signed     (signé)
     *   - document.rejected   (rejeté)
     *   - document.recalled   (rappelé)
     */
    function startRealtime(echo: Echo) {
        const authStore = useAuthStore();
        const userId = authStore.user?.id;
        if (!userId || realtimeBound) return;

        const channel = echo.private(`App.Models.User.${userId}`);

        channel
            .listen('.document.submitted', (e: any) => {
                fetchNotifications();
                fetchUnreadCount();
            })
            .listen('.document.signed', (e: any) => {
                fetchNotifications();
                fetchUnreadCount();
            })
            .listen('.document.rejected', (e: any) => {
                fetchNotifications();
                fetchUnreadCount();
            })
            .listen('.document.recalled', (e: any) => {
                fetchNotifications();
                fetchUnreadCount();
            });

        realtimeBound = true;
    }

    function startPolling() {
        if (pollingInterval) return;
        pollingInterval = setInterval(() => {
            fetchUnreadCount();
        }, 30000); // Toutes les 30 secondes
    }

    function stopPolling() {
        if (pollingInterval) {
            clearInterval(pollingInterval);
            pollingInterval = null;
        }
    }

    return {
        notifications,
        unreadCount,
        unreadNotifications,
        loading,
        fetchNotifications,
        fetchUnreadCount,
        markAsRead,
        markAllAsRead,
        startRealtime,
        startPolling,
        stopPolling,
    };
});

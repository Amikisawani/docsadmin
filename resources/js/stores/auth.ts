import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import apiClient from '../utils/axios';
import type { User, LoginCredentials, LoginResponse } from '../types';

export const useAuthStore = defineStore('auth', () => {
    const user = ref<User | null>(JSON.parse(localStorage.getItem('user') || 'null'));
    const token = ref<string | null>(localStorage.getItem('token'));
    const loading = ref(false);

    const isAuthenticated = computed(() => !!token.value);
    const isAdmin = computed(() => user.value?.roles?.some(r => r.name === 'admin') ?? false);
    const userPermissions = computed(() => user.value?.permissions ?? []);

    function hasPermission(permission: string): boolean {
        return isAdmin.value || userPermissions.value.includes(permission);
    }

    function hasRole(role: string): boolean {
        return user.value?.roles?.some(r => r.name === role) ?? false;
    }

    function hasAnyRole(roles: string[]): boolean {
        if (hasRole('admin')) return true;
        return roles.some(role => hasRole(role));
    }

// Mémorise le dernier rôle connecté (pour le panneau d'urgence de la page de connexion).
    const lastRole = ref<string | null>(localStorage.getItem('last_role'));

    function rememberLastRole(roles: { name: string }[] | undefined): void {
        if (!roles || roles.length === 0) {
            localStorage.setItem('last_role', 'none');
            lastRole.value = 'none';
            return;
        }
        const role = roles[0].name;
        localStorage.setItem('last_role', role);
        lastRole.value = role;
    }

    async function login(credentials: LoginCredentials): Promise<void> {
        loading.value = true;
        try {
            const { data } = await apiClient.post<{ data: LoginResponse }>('/auth/login', credentials);
            const { user: userData, token: authToken } = data.data;

            user.value = userData;
            token.value = authToken;

            localStorage.setItem('token', authToken);
            localStorage.setItem('user', JSON.stringify(userData));
            rememberLastRole(userData?.roles);
        } finally {
            loading.value = false;
        }
    }

    async function logout(): Promise<void> {
        try {
            await apiClient.post('/auth/logout');
        } catch {
            // Ignore errors on logout
        } finally {
            user.value = null;
            token.value = null;
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            // On garde last_role mémorisé : il sert à la page de connexion.
        }
    }

    async function fetchUser(): Promise<void> {
        try {
            const { data } = await apiClient.get<{ data: User }>('/auth/me');
            user.value = data.data;
            localStorage.setItem('user', JSON.stringify(data.data));
        } catch {
            await logout();
        }
    }

return {
        user,
        token,
        loading,
        isAuthenticated,
        isAdmin,
        userPermissions,
        lastRole,
        hasPermission,
        hasRole,
        hasAnyRole,
        login,
        logout,
        fetchUser,
    };
});

import { defineStore } from 'pinia';
import { ref } from 'vue';
import apiClient from '../utils/axios';
import type { Document, MailMergeBatch } from '../types';
import type Echo from 'laravel-echo';
import { useAuthStore } from './auth';

export const useDirectorInboxStore = defineStore('directorInbox', () => {
    const documents = ref<Document[]>([]);
    const campaigns = ref<MailMergeBatch[]>([]);
    const stats = ref<any>({});
    const loading = ref(false);
    const error = ref('');
    const recentRejected = ref<Document[]>([]);
    const history = ref<any[]>([]);
    const historyCounts = ref<any>({});
    let realtimeBound = false;

    async function fetchInbox(params: Record<string, any> = {}) {
        loading.value = true;
        error.value = '';
        try {
const { data } = await apiClient.get('/director/inbox', { params });
            documents.value = data.data?.data || [];
            campaigns.value = data.campaigns || data.data?.campaigns || [];
        } catch (e: any) {
            error.value = e?.response?.data?.message || 'Erreur de chargement de la boîte de réception';
        } finally {
            loading.value = false;
        }
    }

    async function fetchStats() {
        try {
            const { data } = await apiClient.get('/director/stats');
            stats.value = data.data || {};
        } catch (e) {
            console.error(e);
        }
    }

    async function fetchRecentRejected() {
        try {
            const { data } = await apiClient.get('/director/recent-rejected');
            recentRejected.value = data.data || [];
        } catch (e) {
            console.error(e);
        }
    }

    async function fetchHistory(params: Record<string, any> = {}) {
        try {
            const { data } = await apiClient.get('/director/history', { params });
            history.value = data.data || [];
            historyCounts.value = data.counts || {};
        } catch (e) {
            console.error(e);
        }
    }

    async function signDocument(documentId: string, signatureId: string, position?: { x: number; y: number }) {
        const { data } = await apiClient.post(`/documents/${documentId}/sign`, {
            signature_id: signatureId,
            position,
        });
        await fetchInbox();
        await fetchStats();
        return data;
    }

    async function rejectDocument(documentId: string, rejection_reason: string) {
        const { data } = await apiClient.post(`/documents/${documentId}/reject`, {
            rejection_reason,
        });
        await fetchInbox();
        await fetchStats();
        return data;
    }

    async function signCampaign(batchId: string, signatureId: string, position?: { x: number; y: number }) {
        const { data } = await apiClient.post(`/mail-merge/${batchId}/sign-campaign`, {
            signature_id: signatureId,
            position,
        });
        await fetchInbox();
        await fetchStats();
        return data;
    }

    async function sendCampaignToSignature(batchId: string) {
        const { data } = await apiClient.post(`/mail-merge/${batchId}/sign`);
        await fetchInbox();
        await fetchStats();
        return data;
    }

    function bindRealtime(echo: Echo) {
        if (realtimeBound) return;
        const authStore = useAuthStore();
        const userId = authStore.user?.id;
        if (!userId) return;

        const channel = echo.private(`App.Models.User.${userId}`);

        channel
            .listen('.document.submitted', () => {
                fetchInbox();
                fetchStats();
            })
            .listen('.document.signed', () => {
                fetchInbox();
                fetchStats();
            })
            .listen('.document.rejected', () => {
                fetchInbox();
                fetchStats();
            })
            .listen('.document.recalled', () => {
                fetchInbox();
                fetchStats();
            });

        realtimeBound = true;
    }

    return {
        documents,
        campaigns,
        stats,
        loading,
        error,
        recentRejected,
        history,
        historyCounts,
        fetchInbox,
        fetchStats,
        fetchRecentRejected,
        fetchHistory,
        signDocument,
        rejectDocument,
        signCampaign,
        sendCampaignToSignature,
        bindRealtime,
    };
});


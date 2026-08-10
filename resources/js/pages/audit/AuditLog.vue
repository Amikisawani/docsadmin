<template>
  <div class="page-container">
    <div class="page-header">
      <div>
        <h1 class="page-title">Journal d'audit</h1>
        <p class="page-subtitle">Traçabilité des actions effectuées dans DocsAdmin</p>
      </div>
    </div>

    <!-- Cartes statistiques (horizontales) -->
    <div class="stats-grid mb-6">
      <div class="stat-card">
        <div class="stat-icon stat-icon-blue">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div class="stat-content">
          <p class="stat-label">Activités enregistrées</p>
          <p class="stat-value">{{ stats.total_logs }}</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon stat-icon-green">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
        </div>
        <div class="stat-content">
          <p class="stat-label">Aujourd'hui</p>
          <p class="stat-value text-primary">{{ stats.logs_today }}</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon stat-icon-purple">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/></svg>
        </div>
        <div class="stat-content">
          <p class="stat-label">Cette semaine</p>
          <p class="stat-value text-green-600">{{ stats.logs_this_week }}</p>
        </div>
      </div>
    </div>

    <div class="filter-card">
      <div class="filter-grid">
        <input v-model.trim="filters.search" class="form-input" placeholder="Rechercher une action..." @keyup.enter="applyFilters" />
        <select v-model="filters.event" class="form-select">
          <option value="">Toutes les actions</option>
          <option v-for="event in events" :key="event" :value="event">{{ event }}</option>
        </select>
        <input v-model="filters.date_from" type="date" class="form-input" aria-label="Date debut" />
        <input v-model="filters.date_to" type="date" class="form-input" aria-label="Date fin" />
        <button class="btn-filter" @click="applyFilters">Filtrer</button>
        <button class="btn-secondary" @click="resetFilters">Réinitialiser</button>
      </div>
    </div>
    <div v-if="errorMessage" class="alert-error mb-4">{{ errorMessage }}</div>

    <div class="table-card">
      <div class="table-wrapper">
        <table class="data-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Action</th>
              <th>Utilisateur</th>
              <th>Élément concerné</th>
              <th>Détails</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="isLoading">
              <td colspan="5" class="loading-state">Chargement du journal...</td>
            </tr>
            <tr v-for="log in logs" :key="log.id">
              <td class="text-muted">{{ formatDate(log.created_at) }}</td>
              <td><span class="badge badge-blue">{{ log.description }}</span></td>
              <td>{{ log.causer?.name || 'Système' }}</td>
              <td class="text-muted">{{ subjectLabel(log) }}</td>
              <td class="max-w-xs truncate text-muted" :title="detailsLabel(log)">{{ detailsLabel(log) }}</td>
            </tr>
            <tr v-if="!isLoading && !logs.length">
              <td colspan="5" class="empty-state">Aucune activité ne correspond aux critères sélectionnés.</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="pagination.last_page > 1" class="pagination">
        <p class="text-muted">{{ pagination.from }}–{{ pagination.to }} sur {{ pagination.total }}</p>
        <div class="pagination-btns">
          <button class="btn-page" :disabled="pagination.current_page <= 1" @click="changePage(pagination.current_page - 1)">Précédent</button>
          <button class="btn-page" :disabled="pagination.current_page >= pagination.last_page" @click="changePage(pagination.current_page + 1)">Suivant</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue';
import apiClient from '../../utils/axios';
import type { AuditLog, PaginatedResponse } from '../../types';

interface AuditStats { total_logs: number; logs_today: number; logs_this_week: number; }

const logs = ref<AuditLog[]>([]);
const events = ref<string[]>([]);
const isLoading = ref(false);
const errorMessage = ref('');
const stats = ref<AuditStats>({ total_logs: 0, logs_today: 0, logs_this_week: 0 });
const filters = reactive({ search: '', event: '', date_from: '', date_to: '' });
const pagination = reactive({ current_page: 1, last_page: 1, total: 0, from: 0, to: 0 });

function formatDate(date: string): string {
  return new Date(date).toLocaleString('fr-FR', { dateStyle: 'medium', timeStyle: 'short' });
}
function subjectLabel(log: AuditLog): string {
  return log.subject_type ? `${log.subject_type.split('\\').pop() || 'Élément'}${log.subject_id ? ' - ' + log.subject_id : ''}` : '—';
}
function detailsLabel(log: AuditLog): string {
  if (!log.properties) return '—';
  return typeof log.properties === 'string' ? log.properties : JSON.stringify(log.properties);
}
async function loadLogs() {
  isLoading.value = true; errorMessage.value = '';
  try {
    const params: Record<string, string | number> = { page: pagination.current_page, per_page: 50 };
    for (const [key, value] of Object.entries(filters)) if (value) params[key] = value;
    const { data } = await apiClient.get('/audit', { params });
    const response: PaginatedResponse<AuditLog> = data.data;
    logs.value = response.data || [];
    Object.assign(pagination, {
      current_page: response.current_page, last_page: response.last_page,
      total: response.total, from: response.from || 0, to: response.to || 0,
    });
  } catch (error) {
    errorMessage.value = 'Impossible de charger le journal d\'audit.';
    console.error('Erreur chargement audit', error);
  } finally { isLoading.value = false; }
}
async function loadMetadata() {
  try {
    const [eventsResponse, statsResponse] = await Promise.all([
      apiClient.get('/audit/events'), apiClient.get('/audit/stats'),
    ]);
    events.value = eventsResponse.data.data || [];
    stats.value = statsResponse.data.data || stats.value;
  } catch (error) { console.error('Erreur chargement metadonnees audit', error); }
}
function applyFilters() { pagination.current_page = 1; loadLogs(); }
function resetFilters() { Object.assign(filters, { search: '', event: '', date_from: '', date_to: '' }); applyFilters(); }
function changePage(page: number) { pagination.current_page = page; loadLogs(); }
onMounted(async () => { await Promise.all([loadMetadata(), loadLogs()]); });
</script>

<style scoped>
/* --- Cartes statistiques (disposition horizontale) --- */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}
.stat-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 1.15rem 1.25rem;
  box-shadow: 0 1px 3px rgba(15,23,42,0.04), 0 4px 16px rgba(15,23,42,0.03);
  transition: all 0.2s ease;
  min-width: 0;
}
.stat-card:hover {
  box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
  transform: translateY(-2px);
  border-color: #cbd5e1;
}

/* --- Icônes améliorées (dégradés + ombres) --- */
.stat-icon {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  flex-shrink: 0;
  box-shadow: 0 6px 14px rgba(0,0,0,0.18);
}
.stat-icon svg {
  width: 24px;
  height: 24px;
}
.stat-icon-blue {
  background: linear-gradient(135deg, #60a5fa 0%, #2563eb 55%, #1d4ed8 100%);
}
.stat-icon-green {
  background: linear-gradient(135deg, #4ade80 0%, #22c55e 55%, #15803d 100%);
}
.stat-icon-purple {
  background: linear-gradient(135deg, #c4b5fd 0%, #8b5cf6 55%, #6d28d9 100%);
}

.stat-content {
  flex: 1;
  min-width: 0;
}
.stat-label {
  font-size: 0.76rem;
  font-weight: 600;
  color: #64748b;
  margin: 0 0 0.15rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.stat-value {
  font-size: 1.7rem;
  font-weight: 800;
  color: #0f172a;
  margin: 0;
  line-height: 1.15;
}
.text-primary { color: #2563eb; }

/* --- Message d'erreur --- */
.alert-error {
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #b91c1c;
  padding: 0.75rem 1rem;
  border-radius: 10px;
  font-size: 0.85rem;
  margin-bottom: 1rem;
}
</style>


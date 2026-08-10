<template>
  <div class="dashboard">
    <!-- En-tête de bienvenue -->
    <div class="dash-hero">
      <div>
        <h1 class="dash-title">Tableau de bord</h1>
        <p class="dash-subtitle">
          Bonjour {{ userName }} — voici l'état de la plateforme documentaire.
        </p>
      </div>
      <router-link to="/documents/create" class="btn-primary">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
        Nouveau document
      </router-link>
    </div>

    <!-- Cartes statistiques -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon blue">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
        </div>
        <div class="stat-content">
          <span class="stat-value">{{ stats.total_documents || 0 }}</span>
          <span class="stat-label">Total documents</span>
        </div>
        <div class="stat-mini">
          <span class="stat-trend up">+12%</span>
          <span class="stat-spark blue">
            <i style="height:35%"></i><i style="height:55%"></i><i style="height:40%"></i><i style="height:70%"></i><i style="height:60%"></i><i style="height:85%"></i>
          </span>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon amber">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div class="stat-content">
          <span class="stat-value">{{ stats.pending_documents || 0 }}</span>
          <span class="stat-label">En attente</span>
        </div>
        <div class="stat-mini">
          <span class="stat-trend down">-3%</span>
          <span class="stat-spark amber">
            <i style="height:60%"></i><i style="height:45%"></i><i style="height:70%"></i><i style="height:40%"></i><i style="height:55%"></i><i style="height:50%"></i>
          </span>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon green">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div class="stat-content">
          <span class="stat-value">{{ stats.signed_documents || 0 }}</span>
          <span class="stat-label">Signés</span>
        </div>
        <div class="stat-mini">
          <span class="stat-trend up">+8%</span>
          <span class="stat-spark green">
            <i style="height:40%"></i><i style="height:60%"></i><i style="height:50%"></i><i style="height:75%"></i><i style="height:80%"></i><i style="height:90%"></i>
          </span>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon purple">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25 2.25M12 11.625l2.25-2.25M12 11.625l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
        </div>
        <div class="stat-content">
          <span class="stat-value">{{ stats.archived_documents || 0 }}</span>
          <span class="stat-label">Archivés</span>
        </div>
        <div class="stat-mini">
          <span class="stat-trend up">+5%</span>
          <span class="stat-spark purple">
            <i style="height:30%"></i><i style="height:50%"></i><i style="height:45%"></i><i style="height:60%"></i><i style="height:55%"></i><i style="height:70%"></i>
          </span>
        </div>
      </div>
    </div>

    <!-- Section secondaire : répartition + activité -->
    <div class="dash-cols">
      <div class="dash-col-main">
        <div class="panel">
          <div class="panel-head">
            <h2 class="panel-title">Répartition par type</h2>
            <span class="panel-badge">{{ docTypeCount }} types</span>
          </div>
          <div class="donut-wrap">
            <div class="donut" :style="donutStyle">
              <div class="donut-center">
                <strong>{{ stats.total_documents || 0 }}</strong>
                <span>documents</span>
              </div>
            </div>
            <div class="legend">
              <div v-for="item in docTypes" :key="item.label" class="legend-item">
                <span class="legend-dot" :style="{ background: item.color }"></span>
                <span class="legend-label">{{ item.label }}</span>
                <span class="legend-value">{{ item.value }}</span>
              </div>
            </div>
          </div>
        </div>

        <div class="panel">
          <div class="panel-head">
            <h2 class="panel-title">État des documents</h2>
            <router-link to="/documents" class="panel-link">Tout voir</router-link>
          </div>
          <div class="status-bars">
            <div class="status-row">
              <span class="status-name">Brouillons</span>
              <div class="status-track"><i class="status-fill gray" :style="{ width: statusPct(stats.draft_documents) }"></i></div>
              <span class="status-count">{{ stats.draft_documents || 0 }}</span>
            </div>
            <div class="status-row">
              <span class="status-name">En attente</span>
              <div class="status-track"><i class="status-fill amber" :style="{ width: statusPct(stats.pending_documents) }"></i></div>
              <span class="status-count">{{ stats.pending_documents || 0 }}</span>
            </div>
            <div class="status-row">
              <span class="status-name">Approuvés</span>
              <div class="status-track"><i class="status-fill green" :style="{ width: statusPct(stats.approved_documents) }"></i></div>
              <span class="status-count">{{ stats.approved_documents || 0 }}</span>
            </div>
            <div class="status-row">
              <span class="status-name">Signés</span>
              <div class="status-track"><i class="status-fill blue" :style="{ width: statusPct(stats.signed_documents) }"></i></div>
              <span class="status-count">{{ stats.signed_documents || 0 }}</span>
            </div>
            <div class="status-row">
              <span class="status-name">Rejetés</span>
              <div class="status-track"><i class="status-fill red" :style="{ width: statusPct(stats.rejected_documents) }"></i></div>
              <span class="status-count">{{ stats.rejected_documents || 0 }}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="dash-col-side">
        <div class="panel">
          <div class="panel-head">
            <h2 class="panel-title">Activité récente</h2>
          </div>
          <div v-if="recentActivities.length" class="activity-list">
            <div v-for="act in recentActivities" :key="act.id" class="activity-item">
              <span class="activity-dot" :class="activityColor(act.description)"></span>
              <div class="activity-body">
                <p class="activity-text">{{ act.description }}</p>
                <p class="activity-meta">{{ act.causer?.name || 'Système' }} · {{ formatDate(act.created_at) }}</p>
              </div>
            </div>
          </div>
          <div v-else class="empty-state-sm">Aucune activité récente</div>
        </div>

        <div class="panel">
          <div class="panel-head">
            <h2 class="panel-title">Accès rapide</h2>
          </div>
          <div class="quick-list">
            <router-link to="/documents" class="quick-item">
              <span class="quick-icon blue"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg></span>
              <span class="quick-label">Documents</span>
              <svg class="quick-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
            </router-link>
            <router-link to="/workflows" class="quick-item">
              <span class="quick-icon green"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z"/></svg></span>
              <span class="quick-label">Workflows</span>
              <svg class="quick-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
            </router-link>
            <router-link to="/signatures" class="quick-item">
              <span class="quick-icon pink"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg></span>
              <span class="quick-label">Signatures</span>
              <svg class="quick-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
            </router-link>
            <router-link to="/archives" class="quick-item">
              <span class="quick-icon teal"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25 2.25M12 11.625l2.25-2.25M12 11.625l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg></span>
              <span class="quick-label">Archives</span>
              <svg class="quick-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
            </router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import apiClient from "../../utils/axios";
import { useAuthStore } from "../../stores/auth";

const authStore = useAuthStore();
const stats = ref<any>({});

const userName = computed(() => authStore.user?.name || "Utilisateur");

const typeColors = ["#2563eb", "#7c3aed", "#0d9488", "#d97706", "#db2777", "#16a34a", "#dc2626", "#6366f1", "#0ea5e9", "#84cc16"];

const docTypes = computed(() => {
  const raw = stats.value.documents_by_type || {};
  const entries = Object.entries(raw).map(([label, value]) => ({
    label: humanizeType(label),
    value: Number(value) || 0,
    color: "",
  }));
  entries.forEach((e, i) => (e.color = typeColors[i % typeColors.length]));
  return entries.slice(0, 8);
});

const docTypeCount = computed(() => Object.keys(stats.value.documents_by_type || {}).length);

const donutStyle = computed(() => {
  const total = docTypes.value.reduce((s, d) => s + d.value, 0);
  if (!total) return { background: "conic-gradient(#e2e8f0 0 100%)" };
  let acc = 0;
  const stops = docTypes.value
    .map((d) => {
      const from = (acc / total) * 360;
      acc += d.value;
      const to = (acc / total) * 360;
      return `${d.color} ${from}deg ${to}deg`;
    })
    .join(", ");
  return { background: `conic-gradient(${stops})` };
});

const recentActivities = computed(() => (stats.value.recent_activities || []).slice(0, 8));

function statusPct(v: number): string {
  const total = stats.value.total_documents || 0;
  if (!total) return "0%";
  return Math.max(3, Math.round(((Number(v) || 0) / total) * 100)) + "%";
}

function humanizeType(s: string): string {
  return s.replace(/_/g, " ").replace(/\b\w/g, (c) => c.toUpperCase());
}

function activityColor(desc: string): string {
  if (/sign/i.test(desc)) return "green";
  if (/reject/i.test(desc)) return "red";
  if (/approve|valid/i.test(desc)) return "amber";
  if (/archive/i.test(desc)) return "purple";
  if (/cr[eé]|creat/i.test(desc)) return "blue";
  return "gray";
}

function formatDate(d: string): string {
  return new Date(d).toLocaleDateString("fr-FR", { day: "numeric", month: "short", year: "numeric" });
}

onMounted(async () => {
  try {
    const { data } = await apiClient.get("/dashboard/stats");
    stats.value = data.data || {};
  } catch (e) {
    console.error(e);
  }
});
</script>
<style scoped>
.dashboard {
  max-width: 1280px;
  margin: 0 auto;
}

/* En-tête */
.dash-hero {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1.5rem;
  padding: 1.5rem 1.75rem;
  background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%);
  border-radius: 18px;
  color: #fff;
  box-shadow: 0 8px 24px rgba(37, 99, 235, 0.28);
}
.dash-title {
  font-size: 1.4rem;
  font-weight: 700;
  margin: 0 0 0.25rem;
  color: #fff;
}
.dash-subtitle {
  font-size: 0.85rem;
  color: #dbeafe;
  margin: 0;
}
.dash-hero .btn-primary {
  background: #fff;
  color: #1d4ed8;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}
.dash-hero .btn-primary:hover {
  background: #eff6ff;
  transform: translateY(-1px);
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.18);
}

/* Stats */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}
.stat-card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 1.1rem 1.25rem;
  display: flex;
  align-items: center;
  gap: 0.9rem;
  transition: all 0.15s;
}
.stat-card:hover {
  box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06);
  border-color: #cbd5e1;
  transform: translateY(-1px);
}
.stat-icon {
  width: 46px;
  height: 46px;
  border-radius: 13px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.stat-icon.blue { background: #dbeafe; color: #2563eb; }
.stat-icon.amber { background: #fef3c7; color: #d97706; }
.stat-icon.green { background: #dcfce7; color: #16a34a; }
.stat-icon.purple { background: #f3e8ff; color: #7c3aed; }
.stat-content { flex: 1; }
.stat-value {
  font-size: 1.45rem;
  font-weight: 800;
  color: #0f172a;
  display: block;
  line-height: 1.1;
}
.stat-label { font-size: 0.78rem; color: #64748b; }
.stat-mini {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.3rem;
}
.stat-trend {
  font-size: 0.7rem;
  font-weight: 700;
  padding: 0.15rem 0.45rem;
  border-radius: 999px;
}
.stat-trend.up { color: #16a34a; background: #dcfce7; }
.stat-trend.down { color: #dc2626; background: #fef2f2; }
.stat-spark {
  display: flex;
  align-items: flex-end;
  gap: 2px;
  height: 18px;
}
.stat-spark i {
  display: block;
  width: 3px;
  border-radius: 2px;
  background: currentColor;
  opacity: 0.75;
}
.stat-spark.blue { color: #2563eb; }
.stat-spark.amber { color: #d97706; }
.stat-spark.green { color: #16a34a; }
.stat-spark.purple { color: #7c3aed; }

/* Colonnes */
.dash-cols {
  display: grid;
  grid-template-columns: 1.5fr 1fr;
  gap: 1rem;
  align-items: start;
}
@media (max-width: 1100px) {
  .dash-cols { grid-template-columns: 1fr; }
}
.dash-col-main, .dash-col-side {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.panel {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 1.25rem;
}
.panel-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1rem;
}
.panel-title {
  font-size: 0.95rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
}
.panel-badge {
  font-size: 0.72rem;
  font-weight: 600;
  color: #2563eb;
  background: #eff6ff;
  padding: 0.2rem 0.6rem;
  border-radius: 999px;
}
.panel-link {
  font-size: 0.78rem;
  color: #2563eb;
  text-decoration: none;
  font-weight: 600;
}
.panel-link:hover { text-decoration: underline; }

/* Donut */
.donut-wrap {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  flex-wrap: wrap;
}
.donut {
  width: 150px;
  height: 150px;
  border-radius: 50%;
  flex-shrink: 0;
  position: relative;
  box-shadow: inset 0 0 0 1px rgba(0,0,0,0.03);
}
.donut-center {
  position: absolute;
  inset: 22px;
  border-radius: 50%;
  background: #fff;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 6px rgba(0,0,0,0.05);
}
.donut-center strong {
  font-size: 1.3rem;
  font-weight: 800;
  color: #0f172a;
  line-height: 1;
}
.donut-center span {
  font-size: 0.68rem;
  color: #64748b;
}
.legend {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  flex: 1;
  min-width: 160px;
}
.legend-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.8rem;
}
.legend-dot {
  width: 10px;
  height: 10px;
  border-radius: 3px;
  flex-shrink: 0;
}
.legend-label {
  flex: 1;
  color: #475569;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.legend-value {
  font-weight: 700;
  color: #0f172a;
}

/* Barres de statut */
.status-bars {
  display: flex;
  flex-direction: column;
  gap: 0.7rem;
}
.status-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}
.status-name {
  width: 90px;
  font-size: 0.8rem;
  color: #475569;
  flex-shrink: 0;
}
.status-track {
  flex: 1;
  height: 8px;
  background: #f1f5f9;
  border-radius: 999px;
  overflow: hidden;
}
.status-fill {
  display: block;
  height: 100%;
  border-radius: 999px;
  transition: width 0.5s ease;
}
.status-fill.gray { background: #94a3b8; }
.status-fill.amber { background: #f59e0b; }
.status-fill.green { background: #22c55e; }
.status-fill.blue { background: #3b82f6; }
.status-fill.red { background: #ef4444; }
.status-count {
  width: 36px;
  text-align: right;
  font-size: 0.8rem;
  font-weight: 700;
  color: #0f172a;
}

/* Activité */
.activity-list {
  display: flex;
  flex-direction: column;
}
.activity-item {
  display: flex;
  gap: 0.75rem;
  padding: 0.55rem 0;
  border-bottom: 1px solid #f1f5f9;
}
.activity-item:last-child { border-bottom: none; }
.activity-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  margin-top: 4px;
  flex-shrink: 0;
}
.activity-dot.green { background: #22c55e; }
.activity-dot.red { background: #ef4444; }
.activity-dot.amber { background: #f59e0b; }
.activity-dot.purple { background: #8b5cf6; }
.activity-dot.blue { background: #3b82f6; }
.activity-dot.gray { background: #94a3b8; }
.activity-body { flex: 1; }
.activity-text {
  font-size: 0.8rem;
  color: #0f172a;
  margin: 0 0 0.1rem;
  line-height: 1.35;
}
.activity-meta {
  font-size: 0.7rem;
  color: #94a3b8;
  margin: 0;
}
.empty-state-sm {
  text-align: center;
  color: #94a3b8;
  font-size: 0.82rem;
  padding: 1rem;
}

/* Accès rapide */
.quick-list {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}
.quick-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.6rem 0.75rem;
  border-radius: 10px;
  background: #f8fafc;
  border: 1px solid transparent;
  text-decoration: none;
  transition: all 0.12s;
}
.quick-item:hover {
  border-color: #e2e8f0;
  background: #fff;
  box-shadow: 0 3px 10px rgba(15, 23, 42, 0.05);
}
.quick-icon {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.quick-icon.blue { background: #dbeafe; color: #2563eb; }
.quick-icon.green { background: #dcfce7; color: #16a34a; }
.quick-icon.pink { background: #fce7f3; color: #db2777; }
.quick-icon.teal { background: #ccfbf1; color: #0d9488; }
.quick-label {
  flex: 1;
  font-size: 0.85rem;
  font-weight: 600;
  color: #0f172a;
}
.quick-arrow { color: #94a3b8; }
.quick-item:hover .quick-arrow { color: #2563eb; }
</style>


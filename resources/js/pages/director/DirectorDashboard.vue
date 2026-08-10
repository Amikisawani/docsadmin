<template>
  <div class="director-dashboard">
    <!-- En-tête -->
    <div class="dir-hero">
      <div>
        <h1 class="dir-title">Validation des documents</h1>
        <p class="dir-subtitle">
          Bonjour {{ userName }} — voici les documents qui attendent votre décision.
        </p>
      </div>
<div class="dir-hero-actions">
        <button class="dir-refresh" @click="refresh" :disabled="loading" title="Rafraîchir">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
        </button>
        <span v-if="stats.pending > 0" class="dir-pending-pill">
          {{ stats.pending }} en attente
        </span>
      </div>
    </div>

    <!-- Onglets -->
    <div class="dir-tabs">
      <button class="dir-tab" :class="{ active: activeTab === 'inbox' }" @click="activeTab = 'inbox'">
        Boîte de validation
      </button>
      <button class="dir-tab" :class="{ active: activeTab === 'history' }" @click="activeTab = 'history'; loadHistory()">
        Historique
      </button>
    </div>

<!-- Contenu : Boîte de validation -->
    <div v-if="activeTab === 'inbox'">

    <!-- Cartes de synthèse -->
    <div class="dir-stats">
      <div class="dir-stat">
        <div class="dir-stat-icon blue">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div class="dir-stat-body">
          <span class="dir-stat-value">{{ stats.pending || 0 }}</span>
          <span class="dir-stat-label">À signer</span>
        </div>
      </div>
      <div class="dir-stat">
        <div class="dir-stat-icon red">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
        </div>
        <div class="dir-stat-body">
          <span class="dir-stat-value">{{ stats.urgent || 0 }}</span>
          <span class="dir-stat-label">Urgents</span>
        </div>
      </div>
      <div class="dir-stat">
        <div class="dir-stat-icon green">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8.25v-1.5m0 1.5c-1.11 0-2.025-.364-2.555-.75M12 8.25c1.11 0 2.025-.364 2.555-.75M12 8.25v1.5m3.75-3a3 3 0 11-7.5 0 3 3 0 017.5 0zM12 8.25c1.11 0 2.025.364 2.555.75M12 8.25v-1.5m0 1.5v1.5"/></svg>
        </div>
        <div class="dir-stat-body">
          <span class="dir-stat-value">{{ stats.new_today || 0 }}</span>
          <span class="dir-stat-label">Nouveaux aujourd'hui</span>
        </div>
      </div>
      <div class="dir-stat">
        <div class="dir-stat-icon amber">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div class="dir-stat-body">
          <span class="dir-stat-value">{{ stats.signed || 0 }}</span>
          <span class="dir-stat-label">Signés</span>
        </div>
      </div>
    </div>

    <!-- Documents à signer -->
    <div class="dir-panel">
      <div class="dir-panel-head">
        <h2 class="dir-panel-title">Documents à signer</h2>
        <div class="dir-panel-filters">
          <select v-model="filterPriority" @change="applyFilters" class="dir-select">
            <option value="">Toutes priorités</option>
            <option value="urgente">Urgente</option>
            <option value="haute">Haute</option>
            <option value="normale">Normale</option>
          </select>
        </div>
      </div>

      <div v-if="loading" class="dir-loading">Chargement des documents...</div>
      <div v-else-if="error" class="dir-error">{{ error }}</div>
      <div v-else-if="documents.length === 0" class="dir-empty">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="color:#94a3b8;margin-bottom:0.5rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z"/></svg>
        <p>Aucun document en attente de signature pour le moment.</p>
      </div>

      <div v-else class="dir-table">
        <div class="dir-table-head">
          <span>Réf.</span>
          <span>Expéditeur</span>
          <span>Service</span>
          <span>Objet</span>
          <span>Priorité</span>
          <span>Date</span>
          <span>Actions</span>
        </div>
        <div v-for="doc in documents" :key="doc.id" class="dir-table-row" :class="{ urgent: doc.priority === 'urgente' }">
          <span class="dir-cell ref">{{ doc.document_number }}</span>
          <span class="dir-cell">{{ doc.author?.name }}</span>
          <span class="dir-cell">{{ doc.department?.name || '—' }}</span>
          <span class="dir-cell subject">{{ doc.subject }}</span>
          <span class="dir-cell">
            <span class="dir-priority" :class="priorityClass(doc.priority)">{{ priorityLabel(doc.priority) }}</span>
          </span>
          <span class="dir-cell">{{ formatDate(doc.submitted_for_signature_at) }}</span>
          <span class="dir-cell actions">
            <router-link :to="`/director/documents/${doc.id}`" class="dir-btn dir-btn-view">Examiner</router-link>
          </span>
        </div>
      </div>
    </div>

    <!-- Campagnes de publipostage à signer -->
    <div class="dir-panel">
      <div class="dir-panel-head">
        <h2 class="dir-panel-title">Campagnes de publipostage</h2>
        <span v-if="campaigns.length" class="dir-panel-count">{{ campaigns.length }} campagne(s)</span>
      </div>

      <div v-if="campaigns.length === 0" class="dir-empty">
        <p>Aucune campagne de publipostage en attente de signature.</p>
      </div>

      <div v-else class="dir-table">
        <div class="dir-table-head">
          <span>Titre</span>
          <span>Créateur</span>
          <span>Destinataires</span>
          <span>Statut</span>
          <span>Envoyé le</span>
          <span>Actions</span>
        </div>
        <div v-for="campaign in campaigns" :key="campaign.id" class="dir-table-row">
          <span class="dir-cell ref">{{ campaign.title || 'Sans titre' }}</span>
          <span class="dir-cell">{{ campaign.creator?.name }}</span>
          <span class="dir-cell">{{ campaign.total_recipients || 0 }} document(s)</span>
          <span class="dir-cell">
            <span class="dir-priority" :class="campaignStatusClass(campaign.status)">{{ campaignStatusLabel(campaign.status) }}</span>
          </span>
          <span class="dir-cell">{{ formatDate(campaign.submitted_for_signature_at) }}</span>
          <span class="dir-cell actions">
            <router-link :to="`/director/campaigns/${campaign.id}`" class="dir-btn dir-btn-view">Examiner</router-link>
          </span>
        </div>
      </div>
    </div>

    <!-- Documents rejetés récemment -->
    <div class="dir-panel" v-if="recentRejected.length > 0">
      <div class="dir-panel-head">
        <h2 class="dir-panel-title">Rejetés récemment</h2>
      </div>
      <div class="dir-table">
        <div class="dir-table-head">
          <span>Réf.</span>
          <span>Expéditeur</span>
          <span>Objet</span>
          <span>Motif</span>
          <span>Date</span>
        </div>
<div v-for="doc in recentRejected" :key="doc.id" class="dir-table-row">
          <span class="dir-cell ref">{{ doc.document_number }}</span>
          <span class="dir-cell">{{ doc.author?.name }}</span>
          <span class="dir-cell subject">{{ doc.subject }}</span>
          <span class="dir-cell">{{ doc.rejection_reason || '—' }}</span>
          <span class="dir-cell">{{ formatDate(doc.updated_at) }}</span>
        </div>
      </div>
    </div>
    </div><!-- /inbox -->

    <!-- Onglet Historique -->
    <div v-else class="dir-history">
      <div class="dir-panel">
        <div class="dir-panel-head">
          <h2 class="dir-panel-title">Historique de l'activité</h2>
          <div class="dir-panel-filters">
            <select v-model="historyFilter" @change="loadHistory" class="dir-select">
              <option value="">Toutes les actions</option>
              <option value="signed">Documents signés</option>
              <option value="rejected">Rejetés</option>
              <option value="pending">En attente</option>
            </select>
          </div>
        </div>

        <div v-if="historyLoading" class="dir-loading">Chargement de l'historique...</div>
        <div v-else-if="history.length === 0" class="dir-empty">
          <p>Aucune activité enregistrée pour le moment.</p>
        </div>

        <div v-else class="dir-timeline">
          <div v-for="(item, idx) in history" :key="idx" class="dir-tl-item">
            <div class="dir-tl-icon" :class="historyIconClass(item.action)">
              <svg v-if="item.action === 'signed'" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              <svg v-else-if="item.action === 'pending'" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
            </div>
            <div class="dir-tl-body">
              <div class="dir-tl-row">
                <span class="dir-tl-label">{{ item.label }}</span>
                <span class="dir-tl-type" :class="historyTypeClass(item.type)">{{ item.type === 'campaign' ? 'Publipostage' : 'Document' }}</span>
              </div>
              <p class="dir-tl-title">{{ item.title }}</p>
              <p class="dir-tl-meta">
                <span v-if="item.reference">{{ item.reference }} · </span>
                <span v-if="item.author">Par {{ item.author }} · </span>
                <span>{{ formatDateTime(item.date) }}</span>
              </p>
              <p v-if="item.reason" class="dir-tl-reason">Motif : {{ item.reason }}</p>
              <router-link v-if="item.route" :to="item.route" class="dir-btn dir-btn-view">Voir</router-link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from "vue";
import { useDirectorInboxStore } from "../../stores/directorInbox";
import { useAuthStore } from "../../stores/auth";
import { useNotificationStore } from "../../stores/notification";

const inbox = useDirectorInboxStore();
const authStore = useAuthStore();
const notifStore = useNotificationStore();

const filterPriority = ref("");
let pollingTimer: ReturnType<typeof setInterval> | null = null;

const activeTab = ref<"inbox" | "history">("inbox");
const historyFilter = ref("");
const historyLoading = ref(false);

const userName = computed(() => authStore.user?.name || "Directeur de Cabinet");
const documents = computed(() => inbox.documents);
const campaigns = computed(() => inbox.campaigns);
const stats = computed(() => inbox.stats);
const recentRejected = computed(() => inbox.recentRejected);
const loading = computed(() => inbox.loading);
const error = computed(() => inbox.error);
const history = computed(() => inbox.history);

async function refresh() {
  await inbox.fetchInbox({ priority: filterPriority.value || undefined });
  await inbox.fetchStats();
  await inbox.fetchRecentRejected();
}

async function loadHistory() {
  historyLoading.value = true;
  try {
    await inbox.fetchHistory({ action: historyFilter.value || undefined });
  } finally {
    historyLoading.value = false;
  }
}

function historyIconClass(action: string): string {
  return action === "signed" ? "green" : action === "pending" ? "amber" : "red";
}
function historyTypeClass(type: string): string {
  return type === "campaign" ? "campaign" : "";
}
function formatDateTime(d?: string): string {
  if (!d) return "—";
  return new Date(d).toLocaleDateString("fr-FR", { day: "numeric", month: "short", year: "numeric", hour: "2-digit", minute: "2-digit" });
}

function applyFilters() {
  refresh();
}

function priorityLabel(p?: string): string {
  return ({ normale: "Normale", haute: "Haute", urgente: "Urgente" } as Record<string, string>)[p || "normale"] || "Normale";
}
function priorityClass(p?: string): string {
  return p === "urgente" ? "urgent" : p === "haute" ? "high" : "normal";
}
function campaignStatusLabel(s?: string): string {
  return ({ pending_signature: "En attente de signature", signed: "Signée", rejected: "Rejetée", completed: "Terminée" } as Record<string, string>)[s || ""] || s || "—";
}
function campaignStatusClass(s?: string): string {
  return ({ pending_signature: "urgent", signed: "normal", rejected: "urgent", completed: "normal" } as Record<string, string>)[s || ""] || "normal";
}
function formatDate(d?: string): string {
  if (!d) return "—";
  return new Date(d).toLocaleDateString("fr-FR", { day: "numeric", month: "short", year: "numeric" });
}

onMounted(async () => {
  await refresh();
  notifStore.fetchNotifications();
  notifStore.startPolling();
});

onUnmounted(() => {
  if (pollingTimer) clearInterval(pollingTimer);
});
</script>

<style scoped>
.director-dashboard { max-width: 1280px; margin: 0 auto; font-family: system-ui, sans-serif; }

.dir-hero {
  display: flex; align-items: center; justify-content: space-between; gap: 1rem;
  margin-bottom: 1.5rem; padding: 1.25rem 1.5rem;
  background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 60%, #1d4ed8 100%);
  border-radius: 18px; color: #fff; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.25);
}
.dir-title { font-size: 1.4rem; font-weight: 700; margin: 0 0 0.25rem; color: #fff; }
.dir-subtitle { font-size: 0.85rem; color: #dbeafe; margin: 0; }
.dir-hero-actions { display: flex; align-items: center; gap: 0.75rem; }
.dir-pending-pill {
  background: #fff; color: #1d4ed8; font-size: 0.8rem; font-weight: 700;
  padding: 0.4rem 0.9rem; border-radius: 999px;
}
.dir-refresh {
  width: 36px; height: 36px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.2);
  background: rgba(255,255,255,0.1); color: #fff; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
}
.dir-refresh:hover { background: rgba(255,255,255,0.2); }
.dir-refresh:disabled { opacity: 0.5; cursor: not-allowed; }

/* Onglets */
.dir-tabs {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1.25rem;
  border-bottom: 1px solid #e2e8f0;
}
.dir-tab {
  padding: 0.6rem 1rem;
  font-size: 0.85rem;
  font-weight: 600;
  color: #64748b;
  background: none;
  border: none;
  border-bottom: 2px solid transparent;
  cursor: pointer;
  margin-bottom: -1px;
}
.dir-tab.active {
  color: #1d4ed8;
  border-bottom-color: #1d4ed8;
}
.dir-tab:hover { color: #0f172a; }

/* Historique */
.dir-timeline { display: flex; flex-direction: column; }
.dir-tl-item {
  display: flex;
  gap: 0.9rem;
  padding: 0.9rem 0;
  border-bottom: 1px solid #f1f5f9;
}
.dir-tl-item:last-child { border-bottom: none; }
.dir-tl-icon {
  width: 34px;
  height: 34px;
  flex-shrink: 0;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
}
.dir-tl-icon.green { background: #16a34a; }
.dir-tl-icon.amber { background: #d97706; }
.dir-tl-icon.red { background: #dc2626; }
.dir-tl-body { flex: 1; min-width: 0; }
.dir-tl-row { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
.dir-tl-label { font-size: 0.85rem; font-weight: 700; color: #0f172a; }
.dir-tl-type {
  font-size: 0.68rem;
  font-weight: 700;
  padding: 0.15rem 0.5rem;
  border-radius: 999px;
  background: #eff6ff;
  color: #1d4ed8;
}
.dir-tl-type.campaign { background: #fef3c7; color: #b45309; }
.dir-tl-title { font-size: 0.85rem; color: #334155; margin: 0.3rem 0 0.15rem; }
.dir-tl-meta { font-size: 0.74rem; color: #94a3b8; margin: 0; }
.dir-tl-reason {
  font-size: 0.76rem;
  color: #b91c1c;
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 8px;
  padding: 0.35rem 0.6rem;
  margin-top: 0.4rem;
}

.dir-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
.dir-stat { display: flex; align-items: center; gap: 0.8rem; background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 1rem 1.1rem; }
.dir-stat-icon { width: 42px; height: 42px; border-radius: 11px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.dir-stat-icon.blue { background: #dbeafe; color: #2563eb; }
.dir-stat-icon.red { background: #fee2e2; color: #dc2626; }
.dir-stat-icon.green { background: #dcfce7; color: #16a34a; }
.dir-stat-icon.amber { background: #fef3c7; color: #d97706; }
.dir-stat-body { display: flex; flex-direction: column; }
.dir-stat-value { font-size: 1.4rem; font-weight: 800; color: #0f172a; line-height: 1.1; }
.dir-stat-label { font-size: 0.75rem; color: #64748b; }

.dir-panel { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 1.25rem; }
.dir-panel-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.75rem; }
.dir-panel-title { font-size: 1rem; font-weight: 700; color: #0f172a; margin: 0; }
.dir-panel-filters { display: flex; align-items: center; gap: 0.6rem; }
.dir-select { padding: 0.45rem 0.7rem; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.8rem; background: #fff; color: #0f172a; }
.dir-panel-count { font-size: 0.75rem; font-weight: 600; color: #64748b; background: #f1f5f9; padding: 0.25rem 0.6rem; border-radius: 999px; }
.dir-panel + .dir-panel { margin-top: 1rem; }

.dir-loading, .dir-empty, .dir-error { padding: 2.5rem; text-align: center; color: #64748b; font-size: 0.9rem; }
.dir-empty { display: flex; flex-direction: column; align-items: center; }
.dir-error { color: #b91c1c; }

.dir-table { display: flex; flex-direction: column; }
.dir-table-head, .dir-table-row { display: grid; grid-template-columns: 1.2fr 1fr 1fr 2fr 0.8fr 1fr 0.8fr; gap: 0.75rem; align-items: center; padding: 0.6rem 0.75rem; }
.dir-table-head { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #94a3b8; border-bottom: 1px solid #e2e8f0; }
.dir-table-row { border-bottom: 1px solid #f1f5f9; font-size: 0.82rem; color: #334155; transition: background 0.12s; }
.dir-table-row:hover { background: #f8fafc; }
.dir-table-row.urgent { background: #fef2f2; }
.dir-table-row.urgent:hover { background: #fef2f2; }
.dir-cell { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.dir-cell.ref { font-weight: 600; color: #0f172a; }
.dir-cell.subject { font-weight: 500; color: #0f172a; }
.dir-priority { font-size: 0.7rem; font-weight: 700; padding: 0.2rem 0.55rem; border-radius: 999px; }
.dir-priority.normal { background: #f1f5f9; color: #475569; }
.dir-priority.high { background: #fef3c7; color: #b45309; }
.dir-priority.urgent { background: #fee2e2; color: #b91c1c; }
.dir-btn { font-size: 0.75rem; font-weight: 600; padding: 0.35rem 0.7rem; border-radius: 7px; text-decoration: none; }
.dir-btn-view { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
.dir-btn-view:hover { background: #dbeafe; }

@media (max-width: 900px) {
  .dir-table-head { display: none; }
  .dir-table-row { grid-template-columns: 1fr 1fr; grid-auto-rows: auto; gap: 0.4rem; padding: 0.9rem; }
  .dir-cell.subject { grid-column: 1 / -1; }
}
</style>

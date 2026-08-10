<template>
  <div v-if="document" class="page-container">

    <div class="page-header">
      <div>
        <h1 class="page-title">{{ document.subject }}</h1>
        <p class="page-subtitle">{{ document.document_number }}</p>
      </div>

<div class="flex gap-2 items-center flex-wrap">
        <router-link
          v-if="canEdit"
          :to="`/documents/${document.id}/edit`"
          class="btn-secondary"
        >
          Modifier
        </router-link>

        <!-- Auteur : envoyer à la signature (brouillon / rejeté) -->
        <button
          v-if="canSubmitForSignature"
          class="btn-primary"
          @click="openSubmitModal"
        >
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/></svg>
          Envoyer à la signature
        </button>

        <!-- Auteur : rappeler une demande de signature (en attente, non signé) -->
        <button
          v-if="canRecall"
          class="btn-secondary"
          :disabled="recalling"
          @click="recallSignature"
        >
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/></svg>
          {{ recalling ? 'Envoi...' : 'Rappeler la signature' }}
        </button>

        <!-- Directeur de Cabinet : signer -->
        <button
          v-if="canSign"
          class="btn-primary"
          :class="{ 'btn-success': document.status === 'signed' }"
          @click="openSignatureTool"
        >
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
          {{ document.status === 'signed' ? 'Déjà signé' : 'Signer' }}
        </button>
      </div>
    </div>

    <!-- Bandeau priorité / délai -->
    <div v-if="document.priority || document.deadline" class="meta-banner">
      <span v-if="document.priority" class="priority-badge" :class="'prio-' + document.priority">
        Priorité {{ priorityLabel(document.priority) }}
      </span>
      <span v-if="document.deadline" class="deadline-badge">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        Délai : {{ formatDate(document.deadline) }}
      </span>
    </div>

    <!-- Bandeau motif de rejet -->
    <div v-if="document.status === 'rejected' && document.rejection_reason" class="rejection-banner">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
      <span><strong>Document rejeté.</strong> Motif : {{ document.rejection_reason }}</span>
    </div>

    <!-- Modal envoyer à la signature -->
    <div v-if="showSubmitModal" class="modal-overlay" @click.self="showSubmitModal = false">
      <div class="modal-content">
        <h3 class="card-title mb-1">Envoyer à la signature</h3>
        <p class="sigtool-sub">Définissez la priorité et le délai avant envoi au Directeur de Cabinet.</p>
        <div class="form-group">
          <label>Priorité</label>
          <select v-model="submitForm.priority" class="form-select">
            <option value="normale">Normale</option>
            <option value="haute">Haute</option>
            <option value="urgente">Urgente</option>
          </select>
        </div>
        <div class="form-group">
          <label>Délai (optionnel)</label>
          <input v-model="submitForm.deadline" type="date" class="form-input" />
        </div>
        <div v-if="submitError" class="form-error">{{ submitError }}</div>
        <div class="form-actions">
          <button class="btn-primary" :disabled="submitting" @click="submitForSignature">
            {{ submitting ? 'Envoi...' : 'Confirmer l\'envoi' }}
          </button>
          <button class="btn-secondary" @click="showSubmitModal = false">Annuler</button>
        </div>
      </div>
    </div>

    <!-- Bandeau publipostage -->
    <div v-if="isMailMerge" class="mm-banner">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
      <span>Document issu du <strong>publipostage</strong> — la signature sera appliquée sur toutes les pages.</span>
    </div>

    <div class="detail-grid">

            <!-- Partie principale -->
      <div class="detail-main">

        <!-- G7-4 PDF Preview / Content -->
        <div v-if="document.signed_pdf_path || document.source_file_path" class="info-card">
          <h3 class="card-title">Aperçu du document</h3>
          <iframe 
            :src="'/storage/' + (document.signed_pdf_path || document.source_file_path)" 
            class="doc-preview-iframe"
          ></iframe>
        </div>

        <div v-if="!showSignedCompactView" class="info-card">
          <h3 class="card-title">Informations</h3>

          <div class="info-grid">

            <div>
              <span class="info-label">Type :</span>
              <span class="info-value">{{ document.document_type }}</span>
            </div>

            <div>
              <span class="info-label">Statut :</span>
              <span class="badge" :class="statusBadge(document.status)">
                {{ statusLabel(document.status) }}
              </span>
            </div>

            <div>
              <span class="info-label">Version :</span>
              <span class="info-value">{{ document.version }}</span>
            </div>

            <div>
              <span class="info-label">Confidentialité :</span>
              <span class="info-value">{{ document.confidentiality }}</span>
            </div>

            <div>
              <span class="info-label">Date :</span>
              <span class="info-value">
                {{ formatDate(document.document_date) }}
              </span>
            </div>

            <div>
              <span class="info-label">Auteur :</span>
              <span class="info-value">
                {{ document.author?.name }}
              </span>
            </div>

          </div>
        </div>

        <div v-if="!showSignedCompactView && document.content" class="info-card">
          <h3 class="card-title">Contenu</h3>

          <p class="text-sm whitespace-pre-wrap">
            {{ document.content }}
          </p>
        </div>

        <div v-if="!showSignedCompactView" class="info-card">
          <h3 class="card-title">Historique</h3>

          <div class="timeline">

            <div
              v-for="h in document.histories"
              :key="h.id"
              class="timeline-item"
            >
              <div class="timeline-dot"></div>

              <div>
                <p class="text-sm">{{ historyLabel(h.action) }}</p>

                <p class="text-xs text-muted">
                  {{ h.user?.name }} · {{ formatDate(h.created_at) }}
                </p>
              </div>

            </div>

          </div>
        </div>

      </div>

      <!-- Barre latérale -->
            <div class="detail-side">

        <!-- G7-4 Workflow detail card -->
        <div v-if="document.currentWorkflowInstance" class="wf-detail-card">
          <h3 class="wf-detail-title">Suivi du Workflow</h3>
          
          <div class="wf-progress-mini mb-4">
            <div class="wf-progress-bar-mini">
              <div class="wf-progress-fill-mini" :style="{ width: workflowProgressPercent + '%' }"></div>
            </div>
            <span class="wf-progress-text">{{ workflowProgressPercent }}%</span>
          </div>

          <div class="wf-step-list">
            <div 
              v-for="step in document.currentWorkflowInstance.approvals" 
              :key="step.id"
              class="wf-step-item"
              :class="step.status"
            >
              <div class="flex flex-col flex-1">
                <div class="flex items-center gap-2">
                  <span class="wf-step-name">{{ step.step_name }}</span>
                  <span class="wf-step-role">{{ getRoleLabel(step.approver?.roles?.[0]?.name) }}</span>
                </div>
                <span class="wf-approver-name">
                  Par : <strong>{{ step.approver?.name || 'Inconnu' }}</strong>
                </span>
              </div>
              <div class="wf-step-status">
                <span class="badge" :class="statusBadge(step.status)">
                  {{ statusLabel(step.status) }}
                </span>
              </div>
            </div>
          </div>

          <div v-if="canRemind" class="mt-4">
            <button @click="sendReminder" class="btn-secondary w-full" :disabled="reminding">
              {{ reminding ? 'Envoi...' : 'Rappeler l\'approbateur actuel' }}
            </button>
          </div>
        </div>

        <div v-if="!showSignedCompactView" class="info-card">
          <h3 class="card-title">Pièces jointes</h3>

          <div
            v-if="document.attachments?.length"
            class="space-y-2"
          >

            <div
              v-for="att in document.attachments"
              :key="att.id"
              class="attachment-item"
            >
              {{ att.original_name }}
            </div>

          </div>

          <p
            v-else
            class="text-muted text-sm"
          >
            Aucune pièce jointe
          </p>

        </div>

        <div class="info-card">
          <h3 class="card-title">Signatures</h3>

          <div
            v-if="document.signatures?.length"
            class="space-y-2"
          >

            <div
              v-for="sig in document.signatures"
              :key="sig.id"
              class="signature-item"
            >
              <p>Signé par {{ sig.signer?.name }}</p>
              <p class="text-xs text-muted">
                {{ formatDate(sig.signed_at) }}
              </p>
              <p v-if="sig.position" class="text-xs text-muted">
                Position : ({{ Math.round(sig.position?.x || 0) }}%, {{ Math.round(sig.position?.y || 0) }}%)
              </p>
            </div>

          </div>

          <p
            v-else
            class="text-muted text-sm"
          >
            Non signé
          </p>

          <!-- PDF final signé (Phase D) -->
          <a
            v-if="document.signed_pdf_path"
            :href="'/storage/' + document.signed_pdf_path"
            target="_blank"
            class="btn-primary mt-2"
            style="display:inline-flex; align-items:center; gap:0.4rem;"
          >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
            PDF signé
          </a>

        </div>

      </div>

    </div>

    <!-- Outil de signature animé -->
    <SignatureTool
      v-if="showSignatureTool"
      :document="document"
      @close="showSignatureTool = false"
      @signed="onSigned"
    />

  </div>

<div v-else-if="loadError" class="page-container">
    <div class="error-card">
      <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
      <h3>Document introuvable</h3>
      <p>{{ loadError }}</p>
      <button class="btn-primary" @click="router.push('/documents')">Retour à la liste</button>
    </div>
  </div>

  <div v-else class="loading-state">
    Chargement...
  </div>

</template>
<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import apiClient from "../../utils/axios";
import type { Document } from "../../types";
import SignatureTool from "../../components/SignatureTool.vue";
import { useAuthStore } from "../../stores/auth";

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const document = ref<Document | null>(null);
const loadError = ref("");
const showSignatureTool = ref(false);
const reminding = ref(false);

// --- État pour l'envoi à la signature ---
const showSubmitModal = ref(false);
const submitting = ref(false);
const submitError = ref("");
const recalling = ref(false);
const submitForm = ref<{ priority: string; deadline: string | null }>({
  priority: "normale",
  deadline: null,
});

const workflowProgressPercent = computed(() => {
  if (!document.value?.currentWorkflowInstance?.approvals) return 0;
  const approvals = document.value.currentWorkflowInstance.approvals;
  const approved = approvals.filter(a => a.status === 'approved').length;
  return Math.round((approved / approvals.length) * 100);
});

const isAuthor = computed(() => document.value?.author_id === authStore.user?.id);
const isDirector = computed(() => authStore.hasRole('directeur_cabinet'));

// L'auteur (ou admin) peut modifier
const canEdit = computed(() => {
  if (!document.value) return false;
  if (document.value.status === 'signed' || document.value.status === 'archived') return false;
  return isAuthor.value || authStore.hasAnyRole(['admin']);
});

// L'auteur peut envoyer un brouillon ou un document rejeté à la signature
const canSubmitForSignature = computed(() => {
  if (!document.value) return false;
  if (!isAuthor.value && !authStore.hasAnyRole(['admin'])) return false;
  return ['draft', 'rejected'].includes(document.value.status);
});

// L'auteur peut rappeler une demande de signature si le document est en attente et non signé
const canRecall = computed(() => {
  if (!document.value) return false;
  if (!isAuthor.value && !authStore.hasAnyRole(['admin'])) return false;
  return document.value.status === 'pending' && !document.value.signed_pdf_path;
});

const canRemind = computed(() => {
  const instance = document.value?.currentWorkflowInstance;
  if (!instance || instance.status !== 'in_progress') return false;
  
  // Seul l'auteur ou un admin peut rappeler
  const isDocAuthor = document.value?.author_id === authStore.user?.id;
  const isAdmin = authStore.hasAnyRole(['admin']);
  
  return isDocAuthor || isAdmin;
});

// Seul le Directeur de Cabinet peut signer
const canSign = computed(() => {
  if (!document.value) return false;
  if (document.value.status === 'signed' || document.value.status === 'archived') return false;
  return isDirector.value;
});

const isMailMerge = computed(() => {
  return document.value?.flow_type === 'mail_merge' || Boolean(document.value?.is_mail_merge);
});

const showSignedCompactView = computed(() => {
  return Boolean(isMailMerge.value && document.value?.status === 'signed');
});

function openSignatureTool() {
  if (document.value?.status === 'signed') return;
  showSignatureTool.value = true;
}

function openSubmitModal() {
  submitError.value = "";
  submitForm.value = { priority: document.value?.priority || "normale", deadline: document.value?.deadline || null };
  showSubmitModal.value = true;
}

async function submitForSignature() {
  submitError.value = "";
  submitting.value = true;
  try {
    await apiClient.post(`/documents/${document.value?.id}/submit-for-signature`, {
      priority: submitForm.value.priority,
      deadline: submitForm.value.deadline,
    });
    showSubmitModal.value = false;
    await loadDocument();
  } catch (e: any) {
    submitError.value = e.response?.data?.message || "Erreur lors de l'envoi à la signature";
  } finally {
    submitting.value = false;
  }
}

async function recallSignature() {
  recalling.value = true;
  try {
    await apiClient.post(`/documents/${document.value?.id}/recall-signature`);
    alert("Demande de signature rappelée.");
    await loadDocument();
  } catch (e: any) {
    alert(e.response?.data?.message || "Erreur lors du rappel");
  } finally {
    recalling.value = false;
  }
}

function priorityLabel(p: string): string {
  return ({ normale: "Normale", haute: "Haute", urgente: "Urgente" } as Record<string, string>)[p] || p;
}

async function onSigned() {
  showSignatureTool.value = false;
  await loadDocument();
}

async function sendReminder() {
  const instance = document.value?.currentWorkflowInstance;
  if (!instance?.approvals) return;

  const pending = instance.approvals.find(a => a.status === 'pending');
  if (!pending) return;

  reminding.value = true;
  try {
    await apiClient.post(`/workflows/approvals/${pending.id}/remind`);
    alert("Rappel envoyé !");
  } catch (e: any) {
    alert(e.response?.data?.message || "Erreur lors du rappel");
  } finally {
    reminding.value = false;
  }
}

function getRoleLabel(role?: string) {
  if (!role) return 'Approbauteur';
  const roles: Record<string, string> = {
    'admin': 'Administrateur',
    'secretaire_general': 'Secrétaire Général',
    'directeur': 'Directeur',
    'chef_division': 'Chef de Division',
    'chef_bureau': 'Chef de Bureau',
    'agent': 'Agent'
  };
  return roles[role] || role;
}

function statusLabel(s: string): string {
  return ({ draft: "Brouillon", pending: "En attente", approved: "Approuvé", signed: "Signé", rejected: "Rejeté", archived: "Archivé" } as Record<string, string>)[s] || s;
}
function statusBadge(s: string): string {
  return ({ draft: "badge-gray", pending: "badge-amber", approved: "badge-green", signed: "badge-blue", rejected: "badge-red", archived: "badge-purple" } as Record<string, string>)[s] || "badge-gray";
}
function historyLabel(a: string): string {
  return ({ signed: "Document signé", archived: "Document archivé", workflow_approved: "Étape validée", workflow_rejected: "Étape rejetée", updated: "Document mis à jour", created: "Document créé" } as Record<string, string>)[a] || a;
}
function formatDate(d: string): string {
  return new Date(d).toLocaleDateString("fr-FR", { day: "numeric", month: "short", year: "numeric", hour: "2-digit", minute: "2-digit" });
}

async function loadDocument() {
  loadError.value = "";
  try {
    const { data } = await apiClient.get(`/documents/${route.params.id}`);
    document.value = data.data;
  } catch (e: any) {
    console.error(e);
    loadError.value =
      (e.response?.data?.message as string) ||
      "Ce document n'existe pas ou a été supprimé.";
  }
}

onMounted(loadDocument);
</script>

<style scoped>
.btn-success {
  background: #16a34a;
}
.btn-success:hover {
  background: #15803d;
}
.mm-banner {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.7rem 1rem;
  background: #fef3c7;
  border: 1px solid #fde68a;
  border-radius: 12px;
  color: #92400e;
  font-size: 0.85rem;
  margin-bottom: 1.25rem;
}
.mm-banner svg {
  flex-shrink: 0;
  color: #f59e0b;
}
.error-card {
  max-width: 460px;
  margin: 3rem auto;
  padding: 2rem;
  text-align: center;
  background: #fff;
  border: 1px solid #fecaca;
  border-radius: 16px;
  color: #b91c1c;
  box-shadow: 0 1px 3px rgba(15,23,42,0.04), 0 4px 16px rgba(15,23,42,0.03);
}
.error-card svg {
  color: #dc2626;
  margin-bottom: 0.5rem;
}
.error-card h3 {
  margin: 0 0 0.5rem;
  font-size: 1.1rem;
  color: #0f172a;
}
.error-card p {
  margin: 0 0 1.25rem;
  font-size: 0.85rem;
  color: #64748b;
}

/* Bandeaux priorité / délai / rejet */
.meta-banner {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
  margin-bottom: 1.25rem;
}
.priority-badge {
  display: inline-flex;
  align-items: center;
  font-size: 0.78rem;
  font-weight: 700;
  padding: 0.3rem 0.7rem;
  border-radius: 999px;
}
.priority-badge.prio-normale { color: #166534; background: #dcfce7; }
.priority-badge.prio-haute { color: #92400e; background: #fef3c7; }
.priority-badge.prio-urgente { color: #b91c1c; background: #fee2e2; }
.deadline-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.78rem;
  font-weight: 600;
  color: #475569;
  background: #f1f5f9;
  padding: 0.3rem 0.7rem;
  border-radius: 999px;
}
.rejection-banner {
  display: flex;
  align-items: flex-start;
  gap: 0.6rem;
  padding: 0.75rem 1rem;
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 12px;
  color: #b91c1c;
  font-size: 0.85rem;
  margin-bottom: 1.25rem;
}
.rejection-banner svg { flex-shrink: 0; margin-top: 1px; }

/* Modal générique */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 200;
  padding: 1rem;
}
.modal-content {
  background: #fff;
  border-radius: 16px;
  padding: 1.5rem;
  width: 100%;
  max-width: 440px;
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2);
}
.form-group { margin-bottom: 1rem; }
.form-group label {
  display: block;
  font-size: 0.8rem;
  font-weight: 600;
  color: #334155;
  margin-bottom: 0.3rem;
}
.form-select, .form-input {
  width: 100%;
  padding: 0.6rem 0.75rem;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  font-size: 0.875rem;
  background: #f8fafc;
  color: #0f172a;
  box-sizing: border-box;
}
.form-error {
  margin-bottom: 1rem;
  padding: 0.6rem 0.9rem;
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 10px;
  font-size: 0.8rem;
  color: #b91c1c;
}
.form-actions {
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
  margin-top: 1rem;
}
</style>


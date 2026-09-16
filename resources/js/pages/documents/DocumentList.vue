<template>
  <div class="page-container">
    <div class="page-header">
      <div>
        <h1 class="page-title">Documents</h1>
        <p class="page-subtitle">Gerer les documents administratifs</p>
      </div>
      <router-link to="/documents/create" class="btn-primary">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
        Nouveau document
      </router-link>
    </div>
    <div class="filter-card">
      <div class="filter-grid">
        <input v-model="filters.search" placeholder="Rechercher..." class="form-input" />
        <select v-model="filters.type" class="form-select">
          <option value="">Tous les types</option>
          <option v-for="(label, key) in documentTypes" :key="key" :value="key">{{ label }}</option>
        </select>
        <select v-model="filters.flow_type" class="form-select">
          <option value="">Tous les flux</option>
          <option value="unique">Document unique</option>
          <option value="mail_merge">Publipostage</option>
        </select>
        <select v-model="filters.status" class="form-select">
          <option value="">Tous les statuts</option>
          <option value="draft">Brouillon</option>
          <option value="pending">En attente</option>
          <option value="approved">Approuve</option>
          <option value="signed">Signe</option>
          <option value="rejected">Rejete</option>
          <option value="archived">Archive</option>
        </select>
        <button @click="loadDocuments" class="btn-filter">Filtrer</button>
      </div>
    </div>
    <div class="table-card">
      <div v-if="workflowSummary" class="wf-summary-bar">
        <span class="wf-summary-item">
          <span class="wf-summary-label">Avancement workflow :</span>
          <span class="wf-summary-value approved">{{ workflowSummary.approved }} étape(s) validée(s)</span>
          <span class="wf-summary-value pending">{{ workflowSummary.pending }} en attente</span>
          <span class="wf-summary-value rejected">{{ workflowSummary.rejected }} rejetée(s)</span>
        </span>
        <span class="wf-summary-item">
          <span class="wf-summary-label">Documents avec workflow :</span>
          <span class="wf-summary-value">{{ workflowSummary.withWorkflow }} / {{ documents.length }}</span>
        </span>
      </div>
      <div class="table-wrapper">
        <table class="data-table">
          <thead>
                        <tr>
              <th>N Document</th>
              <th>Objet</th>
              <th>Type</th>
              <th>Flux</th>
              <th>Statut</th>
              <th>Étape actuelle</th>
              <th>Suivi workflow</th>
              <th>Auteur</th>
              <th>Date</th>
              <th class="text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="doc in documents" :key="doc.id">
              <td class="font-mono">{{ doc.document_number }}</td>
              <td class="max-w-xs truncate">{{ doc.subject }}</td>
              <td>{{ getTypeLabel(doc.document_type) }}</td>
              <td><span class="badge" :class="flowBadge(doc.flow_type)">{{ flowLabel(doc.flow_type) }}</span></td>
                            <td><span class="badge" :class="statusBadge(doc.status)">{{ statusLabel(doc.status) }}</span></td>
              <td>
                <span v-if="doc.currentWorkflowInstance" class="badge badge-blue">
                  Étape {{ getCurrentStep(doc) }} / {{ doc.currentWorkflowInstance.workflow?.steps?.length || 0 }}
                </span>
                <span v-else class="text-muted">-</span>
              </td>
              <td>
                <WorkflowProgressCell
                  :doc="doc"
                  :current-user-id="currentUserId"
                  :current-user-role="currentUserRole"
                  @remind="sendReminder"
                />
              </td>
              <td>{{ doc.author?.name || "-" }}</td>
              <td class="text-muted">{{ formatDate(doc.document_date) }}</td>
<td class="text-right">
                <div class="action-btns">
                  <router-link :to="`/documents/${doc.id}`" class="action-btn" title="Voir le document">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                  </router-link>
                  <!-- Auteur : envoyer à la signature (brouillon / rejeté) -->
                  <button
                    v-if="canSubmit(doc)"
                    class="action-btn action-submit"
                    title="Envoyer à la signature"
                    @click="submitForSignature(doc)"
                  >
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/></svg>
                  </button>
                  <!-- Auteur : rappeler une demande de signature (en attente, non signé) -->
                  <button
                    v-if="canRecall(doc)"
                    class="action-btn action-recall"
                    title="Rappeler la demande de signature"
                    @click="recallSignature(doc)"
                  >
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/></svg>
                  </button>
                  <button
                    v-if="canDelete(doc)"
                    class="action-btn action-btn-danger"
                    title="Supprimer"
                    @click="deleteDocument(doc)"
                  >
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!documents.length">
              <td :colspan="10" class="empty-state">Aucun document trouve</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="pagination.last_page > 1" class="pagination">
        <p class="text-muted">Page {{ pagination.current_page }} sur {{ pagination.last_page }}</p>
        <div class="pagination-btns">
          <button :disabled="pagination.current_page <= 1" @click="changePage(pagination.current_page - 1)" class="btn-page">Precedent</button>
          <button :disabled="pagination.current_page >= pagination.last_page" @click="changePage(pagination.current_page + 1)" class="btn-page">Suivant</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from "vue";
import apiClient from "../../utils/axios";
import { useAuthStore } from "../../stores/auth";
import type { Document } from "../../types";
import WorkflowProgressCell from "../../components/WorkflowProgressCell.vue";

const authStore = useAuthStore();
const currentUserId = computed(() => authStore.user?.id || "");
const currentUserRole = computed(() => {
  const roles = authStore.user?.roles || [];
  return roles.length > 0 ? roles[0].name : "";
});

const documents = ref<Document[]>([]);
const pagination = reactive({ current_page: 1, last_page: 1, total: 0 });
const filters = reactive({ search: "", type: "", flow_type: "", status: "" });

const workflowSummary = computed(() => {
  let approved = 0, pending = 0, rejected = 0, withWorkflow = 0;
  documents.value.forEach(doc => {
    const instance = doc.currentWorkflowInstance;
    if (instance) {
      withWorkflow++;
      if (instance.approvals) {
        instance.approvals.forEach(a => {
          if (a.status === 'approved') approved++;
          else if (a.status === 'pending') pending++;
          else if (a.status === 'rejected') rejected++;
        });
      }
    }
  });
  return { approved, pending, rejected, withWorkflow };
});

const documentTypes: Record<string,string> = {
  courrier_entrant: "Courrier entrant", courrier_sortant: "Courrier sortant", note: "Note",
  notification: "Notification", decision: "Decision", arrete: "Arrete", decret: "Decret",
  circulaire: "Circulaire", proces_verbal: "Procès-verbal", rapport: "Rapport",
  contrat: "Contrat", convention: "Convention", demande: "Demande", conge: "Congé",
  mission: "Mission", facture: "Facture", autre: "Autre"
};

function getTypeLabel(t: string): string { return documentTypes[t] || t; }
function flowLabel(f: string): string { return ({ unique: "Document unique", mail_merge: "Publipostage" } as Record<string,string>)[f] || f; }
function flowBadge(f: string): string { return ({ unique: "badge-blue", mail_merge: "badge-purple" } as Record<string,string>)[f] || "badge-gray"; }
function statusLabel(s: string): string { return ({ draft: "Brouillon", pending: "En attente", approved: "Approuve", signed: "Signe", rejected: "Rejete", archived: "Archive" } as Record<string,string>)[s] || s; }
function statusBadge(s: string): string { return ({ draft: "badge-gray", pending: "badge-amber", approved: "badge-green", signed: "badge-blue", rejected: "badge-red", archived: "badge-purple" } as Record<string,string>)[s] || "badge-gray"; }

function getCurrentStep(doc: Document): number {
  const instance = doc.currentWorkflowInstance;
  if (!instance?.approvals) return 0;
  
  const pending = instance.approvals.find(a => a.status === 'pending');
  if (pending) {
    return instance.approvals.findIndex(a => a.id === pending.id) + 1;
  }
  
  const rejected = instance.approvals.find(a => a.status === 'rejected');
  if (rejected) {
    return instance.approvals.findIndex(a => a.id === rejected.id) + 1;
  }
  
  return instance.approvals.length;
}
function formatDate(d: string): string { return new Date(d).toLocaleDateString("fr-FR"); }
function changePage(p: number) { pagination.current_page = p; loadDocuments(); }

async function loadDocuments() {
  try {
    const params: any = { page: pagination.current_page, per_page: 15 };
    if (filters.search) params.search = filters.search;
    if (filters.type) params.type = filters.type;
    if (filters.flow_type) params.flow_type = filters.flow_type;
    if (filters.status) params.status = filters.status;
    const { data } = await apiClient.get("/documents", { params });
    documents.value = data.data.data || [];
    pagination.current_page = data.data.current_page;
    pagination.last_page = data.data.last_page;
    pagination.total = data.data.total;
  } catch (e) { console.error(e); }
}

async function sendReminder(doc: Document) {
  const instance = doc.currentWorkflowInstance;
  if (!instance?.approvals) return;

  const pending = instance.approvals.find(a => a.status === 'pending');
  if (!pending) return;

  try {
    await apiClient.post(`/workflows/approvals/${pending.id}/remind`);
    alert("Rappel envoyé à " + (pending.approver?.name || "l'approbateur"));
  } catch (e: any) {
    alert(e.response?.data?.message || "Erreur lors de l'envoi du rappel");
  }
}

// ─────────────────────────────────────────────────────────────
// Actions contextualisées par rôle (Version Présidence)
// L'auteur du document peut l'envoyer à la signature (brouillon /
// rejeté) ou rappeler une demande en attente (non encore signée).
// ─────────────────────────────────────────────────────────────

/** L'auteur de ce document peut-il l'envoyer à la signature ? */
function canSubmit(doc: Document): boolean {
  const isAuthor = doc.author_id === authStore.user?.id;
  if (!isAuthor) return false;
  // Brouillon ou rejeté → envoyable
  return doc.status === 'draft' || doc.status === 'rejected';
}

/** L'auteur de ce document peut-il rappeler la demande de signature ? */
function canRecall(doc: Document): boolean {
  const isAuthor = doc.author_id === authStore.user?.id;
  if (!isAuthor) return false;
  // En attente de signature, non encore signé → rappelable
  return doc.status === 'pending' && !doc.signed_pdf_path;
}

/** Envoie le document à la signature du Directeur de Cabinet. */
async function submitForSignature(doc: Document) {
  if (!confirm(`Envoyer « ${doc.subject} » à la signature du Directeur de Cabinet ?`)) return;
  try {
    await apiClient.post(`/documents/${doc.id}/submit-for-signature`);
    alert("Document envoyé à la signature.");
    await loadDocuments();
  } catch (e: any) {
    alert(e.response?.data?.message || "Erreur lors de l'envoi à la signature.");
  }
}

/** Rappelle une demande de signature en attente (document non signé). */
async function recallSignature(doc: Document) {
  if (!confirm(`Rappeler la demande de signature de « ${doc.subject} » ?`)) return;
  try {
    await apiClient.post(`/documents/${doc.id}/recall-signature`);
    alert("Demande de signature rappelée.");
    await loadDocuments();
  } catch (e: any) {
    alert(e.response?.data?.message || "Erreur lors du rappel de la demande.");
  }
}

/** L'auteur ou un admin peut-il supprimer ce document ? */
function canDelete(doc: Document): boolean {
  return doc.author_id === authStore.user?.id || authStore.isAdmin;
}

/** Suppression logique du document (il disparaît de la liste). */
async function deleteDocument(doc: Document) {
  if (!confirm(`Supprimer « ${doc.subject} » ? Cette action le retire de la liste.`)) return;
  try {
    await apiClient.delete(`/documents/${doc.id}`);
    await loadDocuments();
  } catch (e: any) {
    alert(e.response?.data?.message || "Erreur lors de la suppression.");
  }
}

onMounted(loadDocuments);
</script>

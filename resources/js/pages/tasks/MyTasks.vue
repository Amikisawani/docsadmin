<template>
  <div class="page-container">
    <div class="page-header">
      <div>
        <h1 class="page-title">Mes tâches</h1>
        <p class="page-subtitle">Documents et campagnes vous attendant</p>
      </div>
    </div>

    <div v-if="loading" class="loading-state">
      Chargement...
    </div>

    <div v-else class="tasks-grid">
      <!-- Approvals en attente -->
      <div class="task-section">
        <h2 class="task-section-title">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          Approvals de workflow ({{ pendingApprovals.length }})
        </h2>

        <div v-if="!pendingApprovals.length" class="task-empty">
          Aucune approbation en attente.
        </div>

        <div v-else class="task-list">
          <div v-for="approval in pendingApprovals" :key="approval.id" class="task-card task-card-warning">
            <div class="task-card-header">
              <span class="badge badge-amber">{{ approval.step_name }}</span>
              <span class="task-date text-muted">{{ formatDate(approval.created_at) }}</span>
            </div>
            <div class="task-card-body">
              <p class="task-title">
                {{ approval.workflowInstance?.document?.subject || "Document" }}
              </p>
              <p class="task-meta">
                N° {{ approval.workflowInstance?.document?.document_number || "-" }}
                · Workflow : {{ approval.workflowInstance?.workflow?.name || "-" }}
              </p>
            </div>
<div class="task-card-actions">
              <router-link
                :to="documentLink(approval)"
                class="btn-primary btn-sm"
              >
                Voir le document
              </router-link>
              <button
                v-if="canRemind(approval)"
                class="btn-secondary btn-sm"
                @click="remind(approval)"
              >
                Rappeler
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Documents à signer -->
      <div class="task-section">
        <h2 class="task-section-title">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
          </svg>
          Documents à signer ({{ signableDocuments.length }})
        </h2>

        <div v-if="!signableDocuments.length" class="task-empty">
          Aucun document à signer pour le moment.
        </div>

        <div v-else class="task-list">
          <div v-for="doc in signableDocuments" :key="doc.id" class="task-card task-card-info">
            <div class="task-card-header">
              <span class="badge badge-blue">{{ statusLabel(doc.status) }}</span>
              <span class="task-date text-muted">{{ formatDate(doc.document_date) }}</span>
            </div>
            <div class="task-card-body">
              <p class="task-title">{{ doc.subject }}</p>
              <p class="task-meta">
                N° {{ doc.document_number }} · {{ getTypeLabel(doc.document_type) }}
              </p>
            </div>
            <div class="task-card-actions">
              <router-link :to="`/documents/${doc.id}`" class="btn-primary btn-sm">
                Signer
              </router-link>
            </div>
          </div>
        </div>
      </div>

      <!-- Campagnes de publipostage à valider -->
      <div class="task-section">
        <h2 class="task-section-title">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
          </svg>
          Campagnes à valider ({{ pendingBatches.length }})
        </h2>

        <div v-if="!pendingBatches.length" class="task-empty">
          Aucune campagne en attente de validation.
        </div>

        <div v-else class="task-list">
          <div v-for="batch in pendingBatches" :key="batch.id" class="task-card task-card-purple">
            <div class="task-card-header">
              <span class="badge badge-purple">{{ batch.status }}</span>
              <span class="task-date text-muted">{{ formatDate(batch.created_at) }}</span>
            </div>
            <div class="task-card-body">
              <p class="task-title">{{ batch.title || "Campagne de publipostage" }}</p>
              <p class="task-meta">
                Source : {{ batch.document?.subject || "Document" }}
                · {{ batch.total_recipients }} destinataire(s)
              </p>
            </div>
            <div class="task-card-actions">
              <router-link :to="`/mail-merge`" class="btn-primary btn-sm">
                Voir la campagne
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import apiClient from "../../utils/axios";
import { useAuthStore } from "../../stores/auth";
import type { Document, MailMergeBatch, WorkflowApproval } from "../../types";

const authStore = useAuthStore();
const loading = ref(true);
const pendingApprovals = ref<WorkflowApproval[]>([]);
const signableDocuments = ref<Document[]>([]);
const pendingBatches = ref<MailMergeBatch[]>([]);

const documentTypes: Record<string, string> = {
  courrier_entrant: "Courrier entrant", courrier_sortant: "Courrier sortant", note: "Note",
  notification: "Notification", decision: "Decision", arrete: "Arrete", decret: "Decret",
  circulaire: "Circulaire", proces_verbal: "Procès-verbal", rapport: "Rapport",
  contrat: "Contrat", convention: "Convention", demande: "Demande", conge: "Congé",
  mission: "Mission", facture: "Facture", autre: "Autre"
};

function getTypeLabel(t: string): string { return documentTypes[t] || t; }
function statusLabel(s: string): string {
  return ({ draft: "Brouillon", pending: "En attente", approved: "Approuvé", signed: "Signé", rejected: "Rejeté", archived: "Archivé" } as Record<string, string>)[s] || s;
}
function formatDate(d: string): string {
  return new Date(d).toLocaleDateString("fr-FR", { day: "numeric", month: "short", year: "numeric" });
}

function canRemind(approval: WorkflowApproval): boolean {
  // L'auteur du document peut rappeler, ou un admin
  const doc = approval.workflowInstance?.document;
  if (!doc) return false;
  const isAuthor = doc.author_id === authStore.user?.id;
  const isAdmin = authStore.hasRole("admin");
  return isAuthor || isAdmin;
}

function documentLink(approval: WorkflowApproval): string {
  const doc = approval.workflowInstance?.document;
  // L'ID est exposé soit via la relation chargée, soit via la colonne de l'instance
  const id =
    doc?.id ||
    (approval.workflowInstance as any)?.document_id ||
    (approval as any)?.document_id ||
    "";
  return id ? `/documents/${id}` : "/";
}

async function remind(approval: WorkflowApproval) {
  try {
    await apiClient.post(`/workflows/approvals/${approval.id}/remind`);
    alert("Rappel envoyé.");
  } catch (e: any) {
    alert(e.response?.data?.message || "Erreur lors de l'envoi du rappel");
  }
}

async function loadTasks() {
  loading.value = true;
  try {
    // 1. Approvals en attente pour l'utilisateur courant
    const { data: apprData } = await apiClient.get("/workflows/pending-approvals", {
      params: { per_page: 50 },
    });
    pendingApprovals.value = apprData.data.data || [];

    // 2. Documents à signer (statut pending ou approved, et l'utilisateur peut signer)
    const { data: docData } = await apiClient.get("/documents", {
      params: { status: "pending", per_page: 50 },
    });
    signableDocuments.value = (docData.data.data || []).filter((d: Document) =>
      d.status === "pending" || d.status === "approved"
    );

    // 3. Campagnes de publipostage en attente (awaiting_workflow ou processing)
    const { data: mmData } = await apiClient.get("/mail-merge", {
      params: { per_page: 50 },
    });
    pendingBatches.value = (mmData.data.data || []).filter((b: MailMergeBatch) =>
      b.status === "awaiting_workflow" || b.status === "processing"
    );
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
}

onMounted(loadTasks);
</script>

<style scoped>
.tasks-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: 1.5rem;
}
.task-section {
  background: #fff;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  padding: 1.25rem;
}
.task-section-title {
  font-size: 0.9rem;
  font-weight: 600;
  color: #0f172a;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid #e2e8f0;
}
.task-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}
.task-card {
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 0.75rem;
  transition: all 0.12s;
}
.task-card:hover {
  border-color: #cbd5e1;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}
.task-card-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
}
.task-card-body {
  margin-bottom: 0.5rem;
}
.task-title {
  font-size: 0.85rem;
  font-weight: 600;
  color: #0f172a;
  margin: 0;
}
.task-meta {
  font-size: 0.75rem;
  color: #64748b;
  margin: 0;
}
.task-date {
  font-size: 0.72rem;
  margin-left: auto;
}
.task-card-actions {
  display: flex;
  gap: 0.5rem;
}
.btn-sm {
  font-size: 0.75rem;
  padding: 0.3rem 0.75rem;
}
.task-empty {
  font-size: 0.85rem;
  color: #94a3b8;
  text-align: center;
  padding: 1.5rem;
}
.task-card-warning {
  border-left: 3px solid #f59e0b;
}
.task-card-info {
  border-left: 3px solid #3b82f6;
}
.task-card-purple {
  border-left: 3px solid #8b5cf6;
}
</style>

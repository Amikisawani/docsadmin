// ============================================================
// MailMergeList.vue
//
// Publipostage (mail merge) :
//  - Source : document finalisé de type publipostage (workflow requis)
//  - Liste des agents : upload .xls/.xlsx/.txt/.csv avec aperçu automatique
//  - Variables globales pré-remplies automatiquement (modifiables)
//  - Lancement de la génération (1 document par destinataire)
//  - Téléchargement du ZIP groupé
// ============================================================
<template>
  <div class="page-container">
    <div class="page-header">
      <div>
        <h1 class="page-title">Publipostage</h1>
        <p class="page-subtitle">Générer des documents personnalisés pour des centaines de destinataires</p>
      </div>
      <button class="btn-primary" @click="openForm">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
        Nouvelle campagne
      </button>
    </div>

    <!-- Liste des campagnes -->
          <div v-if="workflowSummary" class="wf-summary-bar">
        <span class="wf-summary-item">
          <span class="wf-summary-label">Avancement global :</span>
          <span class="wf-summary-value approved">{{ workflowSummary.approved }} étape(s) validée(s)</span>
          <span class="wf-summary-value pending">{{ workflowSummary.pending }} en attente</span>
          <span class="wf-summary-value rejected">{{ workflowSummary.rejected }} rejetée(s)</span>
        </span>
        <span class="wf-summary-item">
          <span class="wf-summary-label">Campagnes actives :</span>
          <span class="wf-summary-value">{{ batches.length }}</span>
        </span>
      </div>
      <div class="table-wrapper">
        <table class="data-table">
          <thead>
            <tr>
              <th>Titre</th>
              <th>Source</th>
              <th>Destinataires</th>
              <th>Workflow</th>
              <th>Suivi workflow</th>
              <th>Fichier</th>
              <th>Statut</th>
              <th>Créé le</th>
              <th class="text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="b in batches" :key="b.id">
              <td>
                <div class="cell-stack">
                  <span class="font-medium">{{ b.title || "-" }}</span>
                  <span class="text-xs text-muted">Campagne {{ statusLabel(b.status).toLowerCase() }}</span>
                </div>
              </td>
              <td>
                <div class="cell-stack">
                  <span class="badge badge-purple">Source</span>
                  <span class="text-xs text-muted">{{ sourceLabel(b) }}</span>
                </div>
              </td>
              <td>
                <div class="cell-stack">
                  <span class="font-medium">{{ b.generated_count }} / {{ b.total_recipients }}</span>
                  <span class="text-xs text-muted">{{ recipientPreview(b) }}</span>
                </div>
              </td>
              <td>
                <div class="cell-stack">
                  <span class="badge badge-blue">Workflow</span>
                  <span class="text-xs text-muted">{{ workflowLabel(b) }}</span>
                </div>
              </td>
              <td>
                <BatchWorkflowProgressCell
                  :batch="b"
                  :current-user-id="currentUserId"
                  :current-user-role="currentUserRole"
                  @remind="remindBatch"
                />
              </td>
              <td>
                <span v-if="b.signed_zip_path" class="badge badge-green">ZIP signé</span>
                <span v-else-if="b.zip_path" class="badge badge-blue">ZIP</span>
                <span v-else class="text-muted text-xs">Aucun</span>
              </td>
              <td>
                <div class="cell-stack">
                  <span class="badge" :class="statusBadge(b.status)">{{ statusLabel(b.status) }}</span>
                  <span v-if="errorPreview(b)" class="text-xs text-red-500">{{ errorPreview(b) }}</span>
                </div>
              </td>
              <td>{{ formatDate(b.created_at) }}</td>
              <td class="text-right">
                <div class="action-btns">
                  <button @click="viewDetails(b)" class="action-btn" title="Voir les détails">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                  </button>
                  <a v-if="b.signed_zip_path" class="action-btn" :href="'/storage/' + b.signed_zip_path" download title="Télécharger le ZIP signé">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                  </a>
                  <a v-else-if="b.zip_path" class="action-btn" :href="'/storage/' + b.zip_path" download title="Télécharger le ZIP">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                  </a>
<button v-if="(['completed', 'partial', 'awaiting_workflow'].includes(b.status) && b.generated_count > 0 && !b.signed_at)" @click="sendToSignature(b)" class="action-btn action-btn-sign" title="Envoyer à la signature">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                  </button>
                  <button v-if="['pending_signature', 'rejected'].includes(b.status)" @click="recallSignature(b)" class="action-btn" title="Rappeler de la signature">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0118 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6.375c.621 0 1.125.504 1.125 1.125v9.375m-8.25-3l1.5 1.5 3-3.75"/></svg>
                  </button>
                  <button @click="deleteBatch(b)" class="action-btn action-btn-danger" title="Supprimer">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                  </button>
                </div>
              </td>
            </tr>
          <tr v-if="!batches.length && !loading">
            <td colspan="9" class="empty-state">Aucune campagne de publipostage</td>
          </tr>
          <tr v-if="loading">
            <td colspan="9" class="loading-state">Chargement...</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal nouvelle campagne -->
    <div v-if="showForm" class="modal-overlay" @click.self="closeForm">
      <div class="modal-content modal-lg">
        <h3 class="card-title mb-4">Nouvelle campagne de publipostage</h3>

        <form @submit.prevent="runCampaign">
          <div class="form-group">
            <label>Titre de la campagne</label>
            <input v-model="form.title" class="form-input" placeholder="ex: Arrêté de mutation du 15 août 2026" />
          </div>

          <!-- Source du document -->
          <div class="form-group">
            <label>Document source *</label>
            <select v-model="form.document_id" class="form-select">
              <option value="" disabled>Sélectionnez un document de publipostage</option>
              <option v-for="d in documents" :key="d.id" :value="d.id">
                {{ d.subject }} ({{ d.document_number }} — {{ d.status }})
              </option>
            </select>
            <p v-if="!documents.length" class="form-hint text-red-500">Aucun document publipostage disponible pour le moment.</p>
            <p v-else class="form-hint">Les documents source de type publipostage sont visibles dès le brouillon, puis lorsqu’ils sont approuvés ou signés.</p>
          </div>

<div class="form-group">
            <label>Workflow de validation <span class="text-muted">(optionnel)</span></label>
            <select v-model="form.workflow_id" class="form-select">
              <option value="">Aucun workflow (génération immédiate)</option>
              <option v-for="wf in workflows" :key="wf.id" :value="wf.id">
                {{ wf.name }}<template v-if="wf.document_type"> ({{ wf.document_type }})</template>
              </option>
            </select>
            <p class="form-hint">Optionnel : sans workflow, les documents sont générés immédiatement puis envoyés à la signature.</p>
          </div>

          <div class="form-group">
            <label>Format de sortie</label>
            <select v-model="form.format" class="form-select">
              <option value="pdf">PDF</option>
              <option value="docx">Word (DOCX)</option>
              <option value="txt">Texte</option>
            </select>
          </div>

          <!-- Liste des agents -->
          <div class="form-group">
            <label>Liste des agents (XLS / XLSX / TXT / CSV)</label>
            <label class="file-dropzone" :class="{ 'has-file': form.recipients_file }">
              <input type="file" accept=".xls,.xlsx,.txt,.csv,.tsv" @change="onRecipientsFileChange" />
              <div class="dz-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25"/></svg>
              </div>
              <p v-if="!form.recipients_file" class="dz-title">Cliquez pour choisir la liste des agents</p>
              <p v-else class="dz-title">{{ form.recipients_file.name }}</p>
              <p class="dz-sub">En-têtes mappés automatiquement : nom, postnom, prenom, matricule, grade, fonction...</p>
            </label>
          </div>

          <!-- Aperçu du fichier agents -->
          <div v-if="preview" class="preview-card">
            <p class="preview-title">Aperçu du fichier — {{ preview.count }} agent(s)</p>
            <div class="table-responsive">
              <table class="data-table">
                <thead>
                  <tr>
                    <th v-for="h in preview.headers" :key="h">{{ h }}</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(row, i) in preview.rows" :key="i">
                    <td v-for="h in preview.headers" :key="h">{{ row[h] || "-" }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Variables globales -->
          <div class="form-group">
            <label>Variables globales (pré-remplies automatiquement, modifiables)</label>
            <div class="vars-grid">
              <div v-for="(value, key) in form.default_variables" :key="key" class="var-field">
                <span class="var-key">{{ key }}</span>
                <input v-model="form.default_variables[key]" class="form-input" :placeholder="'{{' + key + '}}'" />
              </div>
            </div>
            <p class="form-hint">
              Variables disponibles : <span class="font-mono">{{ availableVars }}</span>
            </p>
          </div>

          <div v-if="error" class="form-error">{{ error }}</div>

          <div class="form-actions">
              <button type="submit" class="btn-primary" :disabled="saving">
                {{ saving ? "Génération en cours..." : "Générer les documents" }}
              </button>
              <button type="button" class="btn-secondary" @click="closeForm">Annuler</button>
            </div>
          </form>
      </div>
    </div>

    <!-- Modal Détails de la campagne (G7-3) -->
    <div v-if="selectedBatch" class="modal-overlay" @click.self="selectedBatch = null">
      <div class="modal-content modal-lg">
          <div class="flex justify-between items-center mb-4">
            <h3 class="card-title m-0">Détails de la campagne : {{ selectedBatch.title }}</h3>
            <button @click="selectedBatch = null" class="link-action">&times; Fermer</button>
          </div>

          <div class="info-grid mb-4">
            <div>
              <span class="info-label">Statut :</span>
              <span class="badge" :class="statusBadge(selectedBatch.status)">{{ statusLabel(selectedBatch.status) }}</span>
            </div>
            <div>
              <span class="info-label">Progression :</span>
              <span class="info-value">{{ selectedBatch.generated_count }} / {{ selectedBatch.total_recipients }} documents générés</span>
            </div>
          </div>

          <div class="table-wrapper" style="max-height: 300px;">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Destinataire</th>
                  <th>Statut</th>
                  <th class="text-right">Action</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="r in selectedBatch.recipients" :key="r.name">
                  <td>{{ r.destinataire || r.name }}</td>
                  <td>
                    <span class="badge" :class="r.output_path ? 'badge-green' : 'badge-gray'">
                      {{ r.output_path ? 'Généré' : 'En attente' }}
                    </span>
                  </td>
                  <td class="text-right">
                    <button v-if="r.output_path" @click="previewDoc(r.output_path)" class="link-action">Aperçu</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-if="previewUrl" class="mt-4">
            <h4 class="text-sm font-bold mb-2">Aperçu du document</h4>
            <iframe :src="previewUrl" class="doc-preview-iframe"></iframe>
          </div>

          <div class="form-actions">
            <button @click="selectedBatch = null" class="btn-secondary">Fermer</button>
          </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import apiClient from "../../utils/axios";
import { useAuthStore } from "../../stores/auth";
import BatchWorkflowProgressCell from "../../components/BatchWorkflowProgressCell.vue";

interface Workflow { id: string; name: string; document_type: string | null; is_active: boolean; }
interface EligibleDocument { id: string; subject: string; document_number: string; reference: string | null; status: string; flow_type?: 'unique' | 'mail_merge'; }
interface Batch {
  id: string;
  title: string | null;
  document?: { subject: string } | null;
  workflow?: { name: string } | null;
  currentWorkflowInstance?: {
    id: string;
    current_step: string;
    status: string;
    workflow?: { name: string; steps?: Array<{ name: string; role: string; order: number }> };
    approvals?: Array<{
      id: string;
      status: string;
      step_name: string;
      approver?: { id: string; name: string; email: string } | null;
    }>;
  };
  created_by: string;
  format: string;
  total_recipients: number;
  generated_count: number;
  failed_count: number;
  status: string;
  signed_at?: string | null;
  signed_zip_path?: string | null;
  zip_path: string | null;
  recipients?: Array<{ name: string; destinataire?: string | null; output_path?: string | null }>;
  created_at: string;
}
interface PreviewData {
  headers: string[];
  rows: Record<string, string>[];
  count: number;
}

const batches = ref<Batch[]>([]);
const documents = ref<EligibleDocument[]>([]);
const workflows = ref<Workflow[]>([]);
const preview = ref<PreviewData | null>(null);
const loading = ref(true);
const saving = ref(false);
const error = ref("");
const showForm = ref(false);
const selectedBatch = ref<Batch | null>(null);
const previewUrl = ref<string | null>(null);
const availableVars = "nom, postnom, prenom, nom_complet, civilite, matricule, grade, fonction, service, direction, administration, ministere, adresse_administration, ville, email, telephone, annee, date_notification, date_arrete, numero_arrete, reference, nom_signataire, fonction_signataire";

const authStore = useAuthStore();
const currentUserId = computed(() => authStore.user?.id || "");
const currentUserRole = computed(() => {
  const roles = authStore.user?.roles || [];
  return roles.length > 0 ? roles[0].name : "";
});

const workflowSummary = computed(() => {
  let approved = 0, pending = 0, rejected = 0;
  batches.value.forEach(b => {
    const instance = b.currentWorkflowInstance;
    if (instance?.approvals) {
      instance.approvals.forEach(a => {
        if (a.status === 'approved') approved++;
        else if (a.status === 'pending') pending++;
        else if (a.status === 'rejected') rejected++;
      });
    }
  });
  return { approved, pending, rejected };
});

const form = ref({
  title: "",
  document_id: "",
  workflow_id: "",
  recipients_file: null as File | null,
  format: "pdf",
  default_variables: {} as Record<string, string>,
});

function statusLabel(s: string): string {
  return ({ processing: "En cours", completed: "Terminé", partial: "Partiel", failed: "Échec", awaiting_workflow: "En attente de workflow", pending_signature: "En attente de signature", signed: "Signé", rejected: "Rejeté" } as Record<string, string>)[s] || s;
}
function statusBadge(s: string): string {
  return ({ processing: "badge-amber", completed: "badge-green", partial: "badge-blue", failed: "badge-red", awaiting_workflow: "badge-amber", pending_signature: "badge-amber", signed: "badge-green", rejected: "badge-red" } as Record<string, string>)[s] || "badge-gray";
}
function formatDate(d: string): string {
  return new Date(d).toLocaleDateString("fr-FR", { day: "numeric", month: "short", year: "numeric", hour: "2-digit", minute: "2-digit" });
}
function sourceLabel(b: Batch): string {
  return b.document?.subject || "Document";
}
function workflowLabel(b: Batch): string {
  return b.workflow?.name || "—";
}
function recipientPreview(b: Batch): string {
  const items = (b.recipients || [])
    .map((r) => r.destinataire || r.name)
    .filter(Boolean);

  if (!items.length) return "Aucun destinataire";

  const preview = items.slice(0, 2);
  const remaining = items.length - preview.length;
  const suffix = remaining > 0 ? ` +${remaining} de plus` : "";

  return `${preview.join(", ")}${suffix}`;
}

function errorPreview(b: Batch): string {
  if (!b.errors) return "";
  if (Array.isArray(b.errors)) return String(b.errors[0] || "");
  if (typeof b.errors === "string") return b.errors;
  if (typeof b.errors === "object") return String((b.errors as { message?: string }).message || "");
  return "";
}

function openForm() {
  error.value = "";
  preview.value = null;
  const now = new Date();
  form.value = {
    title: "",
    document_id: "",
    workflow_id: "",
    recipients_file: null,
    format: "pdf",
    default_variables: {
      annee: String(now.getFullYear()),
      date_notification: now.toLocaleDateString("fr-FR"),
      date_arrete: now.toLocaleDateString("fr-FR"),
      numero_arrete: "",
      reference: "",
      nom_signataire: "",
      fonction_signataire: "",
    },
  };
  showForm.value = true;
  loadDocuments();
}

function closeForm() {
  showForm.value = false;
  preview.value = null;
}

function viewDetails(b: Batch) {
  selectedBatch.value = b;
  previewUrl.value = null;
}

function previewDoc(path: string) {
  previewUrl.value = '/storage/' + path;
}

async function onRecipientsFileChange(e: Event) {
  const target = e.target as HTMLInputElement;
  if (!target.files?.length) return;
  form.value.recipients_file = target.files[0];

  // Aperçu automatique
  const fd = new FormData();
  fd.append("recipients_file", form.value.recipients_file);
  try {
    const { data } = await apiClient.post("/mail-merge/preview", fd);
    preview.value = data.data;
    // Pré-remplir les variables globales depuis les en-têtes détectés
    for (const h of data.data.headers) {
      if (!(h in form.value.default_variables)) {
        form.value.default_variables[h] = "";
      }
    }
  } catch (err: any) {
        error.value = err.response?.data?.message || "Impossible de lire le fichier";
    preview.value = null;
  }
}

async function loadBatches() {
  loading.value = true;
  try {
    const { data } = await apiClient.get("/mail-merge");
    batches.value = data.data.data || [];
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
}

async function loadWorkflows() {
  try {
    const { data } = await apiClient.get("/workflows");
    workflows.value = (data.data?.data || data.data || []).filter((w: Workflow) => w.is_active !== false);
  } catch (e) {
    console.error(e);
  }
}

async function loadDocuments() {
  try {
    const { data } = await apiClient.get("/mail-merge/documents");
    documents.value = (data.data || []).filter((doc: EligibleDocument) => doc.flow_type === 'mail_merge' || doc.flow_type === undefined);
  } catch (e) {
    console.error(e);
  }
}

async function runCampaign() {
  error.value = "";

if (!form.value.document_id) {
    error.value = "Veuillez sélectionner un document de publipostage.";
    return;
  }
  if (!form.value.recipients_file) {
    error.value = "Veuillez choisir la liste des agents (XLS/XLSX/TXT/CSV).";
    return;
  }

  saving.value = true;
  try {
    const fd = new FormData();
    fd.append("title", form.value.title || "Publipostage");
    fd.append("format", form.value.format);

    fd.append("document_id", form.value.document_id);
    if (form.value.workflow_id) {
      fd.append("workflow_id", form.value.workflow_id);
    }

    if (form.value.recipients_file) fd.append("recipients_file", form.value.recipients_file);

    // Variables globales (filtrer les vides)
    const cleanVars = Object.fromEntries(
      Object.entries(form.value.default_variables).filter(([, v]) => v !== "" && v != null)
    );
    if (Object.keys(cleanVars).length) {
      fd.append("default_variables", JSON.stringify(cleanVars));
    }

    const { data } = await apiClient.post("/mail-merge", fd);
    closeForm();
    await loadBatches();
    return data;
  } catch (e: any) {
    const payload = e.response?.data;
    const generated = Number(payload?.data?.generated_count || 0);
    if (generated > 0) {
      closeForm();
      await loadBatches();
      return;
    }
    const details = Array.isArray(payload?.errors)
      ? payload.errors.filter((item: unknown) => typeof item === "string" && item.trim() !== "").join(" ")
      : "";
    error.value = [payload?.message, details].filter(Boolean).join(" — ") || "Erreur lors du publipostage";
  } finally {
    saving.value = false;
  }
}

async function deleteBatch(b: Batch) {
  if (!confirm("Supprimer cette campagne et ses fichiers générés ?")) return;
  try {
    await apiClient.delete("/mail-merge/" + b.id);
    await loadBatches();
  } catch (e: any) {
    alert(e.response?.data?.message || "Erreur lors de la suppression");
  }
}

async function remindBatch(batch: Batch) {
  const instance = batch.currentWorkflowInstance;
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

async function sendToSignature(batch: Batch) {
  if (!confirm("Envoyer cette campagne à la signature ? Cette action est irréversible.")) return;
  
  try {
    await apiClient.post(`/mail-merge/${batch.id}/sign`);
    alert("Campagne envoyée à la signature avec succès !");
    await loadBatches();
  } catch (e: any) {
    alert(e.response?.data?.message || "Erreur lors de l'envoi à la signature");
  }
}

async function recallSignature(batch: Batch) {
  if (!confirm("Rappeler cette campagne de la signature ? Elle sera de nouveau disponible pour modification.")) return;

  try {
    await apiClient.post(`/mail-merge/${batch.id}/recall-signature`);
    alert("Campagne rappelée de la signature.");
    await loadBatches();
  } catch (e: any) {
    alert(e.response?.data?.message || "Erreur lors du rappel de la signature");
  }
}

onMounted(() => {
  loadBatches();
  loadDocuments();
  loadWorkflows();
});
</script>

<style scoped>
.source-tabs {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}
.source-tab {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.45rem 0.85rem;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #f8fafc;
  color: #475569;
  font-size: 0.82rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.12s;
}
.source-tab:hover {
  border-color: #2563eb;
  color: #2563eb;
}
.source-tab.active {
  background: #2563eb;
  border-color: #2563eb;
  color: #fff;
}
.file-dropzone {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.3rem;
  padding: 1.25rem;
  border: 2px dashed #cbd5e1;
  border-radius: 12px;
  background: #f8fafc;
  cursor: pointer;
  transition: all 0.15s;
  text-align: center;
}
.file-dropzone:hover {
  border-color: #2563eb;
  background: #eff6ff;
}
.file-dropzone.has-file {
  border-color: #16a34a;
  background: #f0fdf4;
}
.file-dropzone input {
  display: none;
}
.dz-icon {
  color: #2563eb;
}
.dz-title {
  font-size: 0.85rem;
  font-weight: 600;
  color: #334155;
  margin: 0;
}
.dz-sub {
  font-size: 0.72rem;
  color: #94a3b8;
  margin: 0;
}
.preview-card {
  margin: 0.5rem 0 1rem;
  padding: 0.85rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
}
.preview-title {
  font-size: 0.8rem;
  font-weight: 600;
  color: #334155;
  margin: 0 0 0.5rem;
}
.table-responsive {
  overflow-x: auto;
}
.vars-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 0.6rem;
}
.var-field {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.var-key {
  font-size: 0.72rem;
  font-weight: 600;
  color: #64748b;
  white-space: nowrap;
  font-family: ui-monospace, monospace;
}
.var-field .form-input {
  flex: 1;
  min-width: 0;
}
</style>


<template>
  <div class="page-container">
    <div class="page-header">
      <div>
        <h1 class="page-title">Workflows</h1>
        <p class="page-subtitle">Circuits de validation des documents</p>
      </div>
      <button class="btn-primary" @click="openForm(null)">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
        Nouveau workflow
      </button>
    </div>

<div class="table-card">
      <div class="table-wrapper">
        <table class="data-table">
          <thead>
            <tr>
              <th>Nom</th>
              <th>Type document</th>
              <th>Etapes</th>
              <th>Statut</th>
              <th class="text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="wf in workflows" :key="wf.id">
              <td class="font-medium">{{ wf.name }}</td>
              <td>{{ documentTypeLabel(wf.document_type) }}</td>
              <td>
                <span class="badge badge-blue">{{ wf.steps?.length || 0 }} etape(s)</span>
              </td>
              <td>
                <span class="badge" :class="wf.is_active ? 'badge-green' : 'badge-gray'">{{ wf.is_active ? "Actif" : "Inactif" }}</span>
              </td>
              <td class="text-right">
                <div class="action-btns">
                  <button @click="openForm(wf)" class="action-btn" title="Modifier le workflow">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125"/></svg>
                  </button>
                  <button @click="deleteWorkflow(wf)" class="action-btn action-btn-danger" title="Supprimer le workflow">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!workflows.length">
              <td colspan="5" class="empty-state">Aucun workflow</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ==================== MODAL FORMULAIRE ==================== -->
    <div v-if="showForm" class="modal-overlay" @click.self="closeForm">
      <div class="modal-content modal-lg">
        <h3 class="card-title mb-4">{{ editId ? "Modifier le workflow" : "Nouveau workflow" }}</h3>

        <!-- Informations générales -->
        <div class="form-group">
          <label>Nom du circuit *</label>
          <input v-model="form.name" required class="form-input" placeholder="ex: Circuit validation courrier sortant" />
          <p class="form-hint">Le nom permet d'identifier le circuit dans la liste des workflows.</p>
        </div>

        <div class="form-grid">
          <div class="form-group">
            <label>Type de document concerné *</label>
            <select v-model="form.document_type" required class="form-select">
              <option value="" disabled>Selectionner un type...</option>
              <option v-for="(label, key) in documentTypes" :key="key" :value="key">{{ label }}</option>
            </select>
            <p class="form-hint">Ce workflow ne sera proposé que pour les documents de ce type.</p>
          </div>
          <div class="form-group">
            <label>Statut</label>
            <select v-model="form.is_active" class="form-select">
              <option :value="true">Actif</option>
              <option :value="false">Inactif</option>
            </select>
            <p class="form-hint">Un workflow inactif ne peut plus être démarré.</p>
          </div>
        </div>

                <div class="form-group">
          <label>Description</label>
          <textarea v-model="form.description" rows="2" class="form-textarea" placeholder="A quoi sert ce circuit ? (optionnel)"></textarea>
        </div>

        <div class="form-group">
          <label>Étape finale de signature</label>
          <p class="form-hint">
            Après validation de toutes les étapes, le document sera prêt pour la signature officielle. Cette étape ne fait pas partie du workflow de validation.
          </p>
        </div>

        <!-- Constructeur d'étapes -->
        <div class="form-group">
          <label>Etapes de validation *</label>
          <p class="form-hint mb-4">
            Chaque étape représente un niveau de validation. Le document circule dans l'ordre (1, 2, 3...). A chaque étape, l'utilisateur ayant le rôle sélectionné doit approuver (ou rejeter) le document avant qu'il ne passe à l'étape suivante.
          </p>

          <div v-if="!form.steps.length" class="wf-empty-steps">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <p>Aucune étape pour le moment. Ajoutez votre première étape de validation ci-dessous.</p>
          </div>

          <div v-for="(step, index) in form.steps" :key="index" class="wf-step">
            <div class="wf-step-number">{{ index + 1 }}</div>
            <div class="wf-step-body">
              <div class="form-grid wf-step-fields">
                    <div class="form-group">
                      <label>Nom de l'étape</label>
                      <input v-model="step.name" class="form-input" placeholder="ex: Validation chef" />
                    </div>
                    <div class="form-group">
                      <label>Rôle de validation</label>
                      <select v-model="step.role" class="form-select" @change="loadUsersForRole(step)">
                        <option value="" disabled>Choisir un rôle...</option>
                        <option v-for="(label, key) in roles" :key="key" :value="key">{{ label }}</option>
                      </select>
                    </div>
                    <div class="form-group">
                      <label>Utilisateur spécifique (optionnel)</label>
                      <select v-model="step.user_id" class="form-select" :disabled="!step.role">
                        <option value="">Tous les utilisateurs avec ce rôle</option>
                        <option v-for="user in step.users" :key="user.id" :value="user.id">{{ user.name }} ({{ user.email }})</option>
                      </select>
                      <p class="form-hint">Si sélectionné, seul cet utilisateur recevra la notification.</p>
                    </div>
                  </div>
            </div>
            <div class="wf-step-actions">
              <button type="button" class="wf-step-btn" title="Monter" :disabled="index === 0" @click="moveStep(index, -1)">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5"/></svg>
              </button>
              <button type="button" class="wf-step-btn" title="Descendre" :disabled="index === form.steps.length - 1" @click="moveStep(index, 1)">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
              </button>
              <button type="button" class="wf-step-btn wf-step-remove" title="Supprimer l'étape" @click="removeStep(index)">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
              </button>
            </div>
            <div v-if="index < form.steps.length - 1" class="wf-step-arrow">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5L12 21m0 0l-7.5-7.5M12 21V3"/></svg>
            </div>
          </div>

          <button type="button" class="btn-add-step" @click="addStep">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Ajouter une étape
          </button>
        </div>

        <!-- Aperçu du circuit -->
        <div v-if="form.steps.length" class="wf-preview">
          <p class="wf-preview-title">Aperçu du circuit</p>
                    <div class="wf-preview-flow">
            <template v-for="(step, index) in form.steps" :key="index">
              <span class="wf-preview-chip" :class="previewClass(step)">
                <span class="wf-preview-step">{{ index + 1 }}</span>
                {{ step.name || "Etape " + (index + 1) }}
                <span v-if="step.role" class="wf-preview-role">{{ roleLabel(step.role) }}</span>
                <span v-if="step.user_id" class="wf-preview-role">→ {{ getUserName(step) }}</span>
              </span>
              <svg v-if="index < form.steps.length - 1" class="wf-preview-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
            </template>
          </div>
          <p class="form-hint">
            Quand un document de type <strong>{{ documentTypeLabel(form.document_type) || "..." }}</strong> sera lancé dans ce workflow, il suivra ce circuit. Le statut du document passe automatiquement de <em>draft</em> à <em>pending</em>, puis <em>approved</em> (si toutes les étapes validées) ou <em>rejected</em> (si une étape rejette).
          </p>
        </div>

        <div v-if="error" class="form-error">{{ error }}</div>

        <div class="form-actions">
          <button type="submit" :disabled="saving" class="btn-primary" @click="saveWorkflow">
            {{ saving ? "Enregistrement..." : "Enregistrer le workflow" }}
          </button>
          <button type="button" class="btn-secondary" @click="closeForm">Annuler</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import apiClient from "../../utils/axios";

interface WorkflowStep {
  name: string;
  role: string;
  user_id?: string;
  users?: Array<{ id: string; name: string; email: string }>;
  order: number;
}
interface Workflow {
  id: string;
  name: string;
  description: string;
  document_type: string;
  steps: WorkflowStep[];
  is_active: boolean;
}

const workflows = ref<Workflow[]>([]);
const showForm = ref(false);
const editId = ref<string | null>(null);
const saving = ref(false);
const error = ref("");

const roles: Record<string, string> = {
  agent: "Agent",
  chef_service: "Chef de service",
  directeur: "Directeur",
  secretaire: "Secretaire",
  admin: "Administrateur",
  auditeur: "Auditeur",
  archiviste: "Archiviste",
};

const documentTypes: Record<string, string> = {
  courrier_entrant: "Courrier entrant",
  courrier_sortant: "Courrier sortant",
  note: "Note",
  notification: "Notification",
  decision: "Decision",
  arrete: "Arrete",
  decret: "Decret",
  circulaire: "Circulaire",
  proces_verbal: "Proces-verbal",
  rapport: "Rapport",
  contrat: "Contrat",
  convention: "Convention",
  demande: "Demande",
  conge: "Conge",
  mission: "Mission",
  facture: "Facture",
  autre: "Autre",
};

const form = ref({
  name: "",
  description: "",
  document_type: "",
  is_active: true,
  steps: [] as WorkflowStep[],
});

function roleLabel(key: string): string {
  return roles[key] || key;
}

function documentTypeLabel(key: string): string {
  return documentTypes[key] || key || "-";
}

function previewClass(step: WorkflowStep): string {
  return step.role ? "wf-preview-chip-valid" : "wf-preview-chip-warn";
}

async function loadUsersForRole(step: WorkflowStep) {
  if (!step.role) return;
  try {
    const { data } = await apiClient.get(`/users/by-role/${step.role}`);
    step.users = data.data || [];
  } catch (e) {
    console.error(e);
  }
}

function getUserName(step: WorkflowStep): string {
  const user = step.users?.find(u => u.id === step.user_id);
  return user ? user.name : "Utilisateur inconnu";
}

function addStep() {
  form.value.steps.push({ name: "", role: "", order: form.value.steps.length + 1 });
  reorderSteps();
}

function removeStep(index: number) {
  form.value.steps.splice(index, 1);
  reorderSteps();
}

function moveStep(index: number, dir: number) {
  const target = index + dir;
  if (target < 0 || target >= form.value.steps.length) return;
  const [item] = form.value.steps.splice(index, 1);
  form.value.steps.splice(target, 0, item);
  reorderSteps();
}

function reorderSteps() {
  form.value.steps.forEach((s, i) => (s.order = i + 1));
}

function openForm(w: Workflow | null) {
  editId.value = w?.id || null;
  error.value = "";
  form.value = {
    name: w?.name || "",
    description: w?.description || "",
    document_type: w?.document_type || "",
    is_active: w?.is_active ?? true,
    steps: (w?.steps || []).map((s, i) => ({ name: s.name || "", role: s.role || "", user_id: s.user_id || undefined, users: [], order: i + 1 })),
  };
  showForm.value = true;
}

function closeForm() {
  showForm.value = false;
  editId.value = null;
  error.value = "";
  form.value = { name: "", description: "", document_type: "", is_active: true, steps: [] };
}

async function loadWorkflows() {
  try {
    const { data } = await apiClient.get("/workflows");
    workflows.value = data.data.data || [];
  } catch (e) {
    console.error(e);
  }
}

async function deleteWorkflow(w: Workflow) {
  if (!confirm("Supprimer le workflow ?")) return;
  try {
    await apiClient.delete("/workflows/" + w.id);
    await loadWorkflows();
  } catch (e: any) {
    alert(e.response?.data?.message || "Erreur lors de la suppression");
  }
}

async function saveWorkflow() {
  error.value = "";

  // Validation
  if (!form.value.name.trim()) {
    error.value = "Le nom du workflow est requis.";
    return;
  }
  if (!form.value.document_type) {
    error.value = "Selectionnez un type de document.";
    return;
  }
  if (!form.value.steps.length) {
    error.value = "Ajoutez au moins une etape de validation.";
    return;
  }
  for (const step of form.value.steps) {
    if (!step.name.trim()) {
      error.value = "Chaque etape doit avoir un nom.";
      return;
    }
    if (!step.role) {
      error.value = "Chaque etape doit avoir un role de validation.";
      return;
    }
  }

  saving.value = true;
  try {
    const payload = {
      name: form.value.name,
      description: form.value.description,
      document_type: form.value.document_type,
      is_active: form.value.is_active,
      steps: form.value.steps.map((s) => ({ name: s.name.trim(), role: s.role, user_id: s.user_id, order: s.order })),
    };
    if (editId.value) {
      await apiClient.put("/workflows/" + editId.value, payload);
    } else {
      await apiClient.post("/workflows", payload);
    }
    closeForm();
    await loadWorkflows();
  } catch (e: any) {
    const msg = e.response?.data?.message;
    error.value = typeof msg === "string" ? msg : "Erreur lors de l'enregistrement";
  } finally {
    saving.value = false;
  }
}

onMounted(loadWorkflows);
</script>


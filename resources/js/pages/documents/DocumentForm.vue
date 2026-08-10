<template>
  <div class="page-container">
    <div class="page-header">
      <div>
        <h1 class="page-title">{{ isEdit ? "Modifier le document" : "Nouveau document" }}</h1>
        <p class="page-subtitle">{{ isEdit ? "Modifier les informations du document" : "Créer un nouveau document administratif" }}</p>
      </div>
    </div>
    <div class="form-card">
      <form @submit.prevent="saveDocument">
        <div class="form-grid">
          <div class="form-group">
            <label>Type de flux *</label>
            <select v-model="form.flow_type" class="form-select" @change="onFlowTypeChange">
              <option value="unique">Document unique</option>
              <option value="mail_merge">Publipostage</option>
            </select>
          </div>
          <div class="form-group">
            <label>Type de document *</label>
            <select v-model="form.document_type" required class="form-select" @change="onTypeChange">
              <option value="">Sélectionner...</option>
              <option v-for="(label, key) in documentTypes" :key="key" :value="key">{{ label }}</option>
            </select>
          </div>
        </div>

        <div class="form-grid">
          <div class="form-group">
            <label>Référence</label>
            <input v-model="form.reference" class="form-input" />
          </div>
          <div class="form-group">
            <label>Flux</label>
<div class="form-hint">
              {{ form.flow_type === 'mail_merge' ? 'Le document est destiné au publipostage et ne nécessite pas de workflow à la création.' : 'Le document est créé en brouillon. Après création, vous pourrez l\'envoyer directement à la signature du Directeur de Cabinet.' }}
            </div>
          </div>
        </div>

        <div class="form-group">
          <label>Objet *</label>
          <input v-model="form.subject" required class="form-input" />
        </div>

        <div class="form-grid">
          <div class="form-group">
            <label>Confidentialité</label>
            <select v-model="form.confidentiality" class="form-select">
              <option value="public">Public</option>
              <option value="interne">Interne</option>
              <option value="confidentiel">Confidentiel</option>
              <option value="secret">Secret</option>
            </select>
          </div>
          <div class="form-group">
            <label>Date du document</label>
            <input v-model="form.document_date" type="date" class="form-input" />
          </div>
        </div>

<!-- Version Présidence : le workflow n'est plus nécessaire à la création.
             Le document est créé en brouillon puis envoyé à la signature du
             Directeur de Cabinet. Un workflow éventuel reste géré via le publipostage. -->
        <div class="form-group">
          <label>Workflow de validation (optionnel)</label>
          <div class="form-hint">
            Le document est créé en brouillon. Après création, vous pourrez l'envoyer
            directement à la signature du Directeur de Cabinet.
          </div>
          <select v-model="form.workflow_id" class="form-select">
            <option value="">-- Aucun workflow --</option>
            <option v-for="wf in workflows" :key="wf.id" :value="wf.id">
              {{ wf.name }}<template v-if="wf.document_type"> ({{ typeLabel(wf.document_type) }})</template>
            </option>
          </select>
          <div class="mt-1">
            <router-link to="/workflows" class="link-action">+ Gérer les workflows</router-link>
          </div>
        </div>

        <!-- Fichier Word source (Phase C) -->
        <div class="form-group">
          <label>Fichier source (Word / PDF)</label>
          <div class="form-hint">Le document final sera généré à partir de ce fichier (DOCX recommandé).</div>
          <label class="file-dropzone" :class="{ 'has-file': form.source_file, 'dragover': dragOver }" @dragover.prevent="dragOver = true" @dragleave.prevent="dragOver = false" @drop.prevent="onDrop">
            <input type="file" accept=".doc,.docx,.odt,.pdf,.txt" @change="onFileChange" />
            <div class="dz-icon">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
            </div>
            <p v-if="!form.source_file" class="dz-title">Choisir un document…</p>
            <p v-else class="dz-title">{{ form.source_file.name }}</p>
            <p class="dz-sub">DOCX, PDF, ODT, TXT — glissez-déposez ou cliquez</p>
          </label>
        </div>

        <div class="form-group">
          <label>Contenu</label>
          <textarea v-model="form.content" rows="8" class="form-textarea"></textarea>
        </div>

        <!-- Aide variables publipostage -->
        <div v-if="form.flow_type === 'mail_merge'" class="form-group mm-vars-help">
          <label>Variables de publipostage</label>
          <p class="form-hint">
            Insérez des variables dans le contenu ou le fichier source avec la syntaxe
            <code v-pre>{{nom_variable}}</code>. Elles seront remplacées automatiquement
            pour chaque destinataire lors de la génération.
          </p>
          <div class="mm-vars-grid">
            <span v-for="v in mailMergeVariables" :key="v.key" class="mm-var-chip" :title="v.desc">
              <code v-pre>{{</code>{{ v.key }}<code v-pre>}}</code>
            </span>
          </div>
          <p class="form-hint mt-1">
            Exemple : « Je soussigné(e) <code v-pre>{{nom_complet}}</code>, matricule <code v-pre>{{matricule}}</code>,
            grade <code v-pre>{{grade}}</code>… »
          </p>
        </div>

        <div v-if="error" class="form-error">{{ error }}</div>

        <div class="form-actions">
          <button type="submit" :disabled="loading" class="btn-primary">{{ loading ? "Enregistrement..." : (isEdit ? "Mettre à jour" : "Créer le document") }}</button>
          <router-link to="/documents" class="btn-secondary">Annuler</router-link>
        </div>
      </form>
    </div>
  </div>
</template>
<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import { useRouter, useRoute } from "vue-router";
import apiClient from "../../utils/axios";

const router = useRouter();
const route = useRoute();
const isEdit = computed(() => !!route.params.id);
const loading = ref(false);
const error = ref("");
const dragOver = ref(false);

const form = ref({
  flow_type: "unique" as "unique" | "mail_merge",
  document_type: "",
  reference: "",
  subject: "",
  confidentiality: "interne",
  document_date: new Date().toISOString().split("T")[0],
  content: "",
  workflow_id: "",
  source_file: null as File | null,
});

const documentTypes: Record<string, string> = {
  courrier_entrant: "Courrier entrant", courrier_sortant: "Courrier sortant", note: "Note",
  notification: "Notification", decision: "Décision", arrete: "Arrêté", decret: "Décret",
  circulaire: "Circulaire", proces_verbal: "Procès-verbal", rapport: "Rapport", contrat: "Contrat",
  convention: "Convention", demande: "Demande", conge: "Congé", mission: "Mission",
  facture: "Facture", autre: "Autre",
};

interface Workflow {
  id: string;
  name: string;
  description: string | null;
  document_type: string;
  steps: any[];
  is_active: boolean;
}

const workflows = ref<Workflow[]>([]);

const mailMergeVariables = [
  { key: 'nom', desc: 'Nom de famille' },
  { key: 'postnom', desc: 'Postnom' },
  { key: 'prenom', desc: 'Prénom' },
  { key: 'nom_complet', desc: 'Nom complet' },
  { key: 'civilite', desc: 'Civilité (M./Mme)' },
  { key: 'matricule', desc: 'Matricule administratif' },
  { key: 'grade', desc: 'Grade' },
  { key: 'fonction', desc: 'Fonction' },
  { key: 'service', desc: 'Service' },
  { key: 'direction', desc: 'Direction' },
  { key: 'administration', desc: 'Administration' },
  { key: 'ministere', desc: 'Ministère' },
  { key: 'ville', desc: 'Ville' },
  { key: 'email', desc: 'Adresse e-mail' },
  { key: 'telephone', desc: 'Téléphone' },
  { key: 'annee', desc: 'Année en cours' },
  { key: 'date_notification', desc: 'Date de notification' },
  { key: 'date_arrete', desc: 'Date de l\'arrêté' },
  { key: 'numero_arrete', desc: 'Numéro de l\'arrêté' },
  { key: 'reference', desc: 'Référence du document' },
  { key: 'nom_signataire', desc: 'Nom du signataire' },
  { key: 'fonction_signataire', desc: 'Fonction du signataire' },
];

const workflowFiltered = ref(false);
const filteredWorkflows = computed(() => {
  if (!form.value.document_type) return workflows.value;
  return workflows.value.filter(w => !w.document_type || w.document_type === form.value.document_type);
});

function typeLabel(type: string): string {
  return documentTypes[type] || type;
}

function onFlowTypeChange() {
  if (form.value.flow_type === 'mail_merge') {
    form.value.workflow_id = "";
  }
}

function onTypeChange() {
  workflowFiltered.value = true;
  // Réinitialiser le workflow sélectionné s'il n'est plus compatible
  if (!filteredWorkflows.value.some(w => w.id === form.value.workflow_id)) {
    form.value.workflow_id = "";
  }
}

function onFileChange(e: Event) {
  const target = e.target as HTMLInputElement;
  if (target.files?.length) form.value.source_file = target.files[0];
}

function onDrop(e: DragEvent) {
  dragOver.value = false;
  const file = e.dataTransfer?.files?.[0];
  if (file) form.value.source_file = file;
}

async function loadWorkflows() {
  try {
    const { data } = await apiClient.get("/workflows");
    workflows.value = (data.data?.data || data.data || []).filter((w: Workflow) => w.is_active !== false);
  } catch (e) {
    console.error("Erreur chargement workflows", e);
  }
}

async function saveDocument() {
  error.value = "";
  loading.value = true;
  try {
    const payload = new FormData();
    payload.append("flow_type", form.value.flow_type);
    payload.append("document_type", form.value.document_type);
    payload.append("subject", form.value.subject);
    payload.append("confidentiality", form.value.confidentiality);
    payload.append("document_date", form.value.document_date);
    if (form.value.flow_type === 'unique' && form.value.workflow_id) {
      payload.append("workflow_id", form.value.workflow_id);
    }
    if (form.value.reference) payload.append("reference", form.value.reference);
    if (form.value.content) payload.append("content", form.value.content);
    if (form.value.source_file) payload.append("source_file", form.value.source_file);

    if (isEdit.value) {
      await apiClient.post(`/documents/${route.params.id}`, payload, { headers: { "Content-Type": "multipart/form-data" } });
    } else {
      await apiClient.post("/documents", payload, { headers: { "Content-Type": "multipart/form-data" } });
    }
    router.push("/documents");
  } catch (e: any) {
    error.value = e.response?.data?.message || "Erreur lors de l'enregistrement";
  } finally {
    loading.value = false;
  }
}

onMounted(async () => {
  await loadWorkflows();
  if (isEdit.value) {
    try {
      const { data } = await apiClient.get(`/documents/${route.params.id}`);
      const doc = data.data;
      form.value = {
        flow_type: doc.flow_type || 'unique',
        document_type: doc.document_type,
        reference: doc.reference || "",
        subject: doc.subject,
        confidentiality: doc.confidentiality,
        document_date: doc.document_date,
        content: doc.content || "",
        workflow_id: doc.workflow_id || "",
        source_file: null,
      };
      workflowFiltered.value = true;
    } catch {
      router.push("/documents");
    }
  }
});
</script>

<style scoped>
/* ============================================================
   Formulaire document — styles scoped
   ============================================================ */
.form-card {
  max-width: 860px;
  margin: 0 auto;
}

.form-card form {
  display: flex;
  flex-direction: column;
}

/* Champs avec icône / libellé bien alignés */
.form-group {
  margin-bottom: 1.1rem;
}

.form-group label {
  font-weight: 600;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

@media (max-width: 640px) {
  .form-grid {
    grid-template-columns: 1fr;
  }
}

/* Fichier : zone upload (complète la classe globale .file-dropzone) */
.file-dropzone {
  cursor: pointer;
  padding: 1.75rem 1.5rem;
}
.file-dropzone input[type="file"] {
  display: none;
}
.file-dropzone.dragover {
  border-color: #2563eb;
  background: #eff6ff;
}

/* Pied de formulaire : boutons bien présentés */
.form-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding-top: 1.25rem;
  border-top: 1px solid #f1f5f9;
  margin-top: 0.5rem;
}

.form-actions .btn-primary,
.form-actions .btn-secondary {
  min-width: 150px;
  justify-content: center;
}

/* Message d'erreur sous un champ (ex: workflow vide) */
.field-error {
  color: #dc2626;
  font-size: 0.78rem;
  margin-top: 0.35rem;
}

/* Liste des workflows compatibles */
.wf-badge {
  display: inline-block;
  padding: 0.15rem 0.5rem;
  font-size: 0.7rem;
  font-weight: 600;
  border-radius: 999px;
  background: #dbeafe;
  color: #2563eb;
  margin-left: 0.35rem;
}

.mm-vars-help {
  padding: 1rem;
  background: #f0f9ff;
  border: 1px solid #bae6fd;
  border-radius: 12px;
}
.mm-vars-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
  margin-top: 0.5rem;
}
.mm-var-chip {
  display: inline-flex;
  align-items: center;
  font-size: 0.72rem;
  font-family: ui-monospace, monospace;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  padding: 0.2rem 0.45rem;
  color: #334155;
  cursor: default;
}
.mm-var-chip code {
  font-family: inherit;
  color: #64748b;
}
.mt-1 { margin-top: 0.5rem; }
</style>


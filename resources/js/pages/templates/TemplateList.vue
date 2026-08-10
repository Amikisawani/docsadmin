<template>
  <div class="page-container">
    <div class="page-header">
      <div>
        <h1 class="page-title">Modeles de documents</h1>
        <p class="page-subtitle">Gerer les modeles de documents Word, PDF et textes</p>
      </div>
      <button class="btn-primary" @click="openForm(null)">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
        Nouveau modele
      </button>
    </div>

<div class="table-card">
      <div class="table-wrapper">
        <table class="data-table">
          <thead>
            <tr>
              <th>Nom</th>
              <th>Type</th>
              <th>Categorie</th>
              <th>Source</th>
              <th>Statut</th>
              <th class="text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="tpl in templates" :key="tpl.id">
              <td class="font-medium">{{ tpl.name }}</td>
              <td>
                <span class="badge" :class="badgeClass(tpl.type)">{{ typeLabel(tpl.type) }}</span>
              </td>
              <td>{{ tpl.category || "-" }}</td>
              <td>
                <span class="badge badge-gray">{{ tpl.content ? "Contenu saisi" : "Fichier importe" }}</span>
              </td>
              <td>
                <span class="badge" :class="tpl.is_active ? 'badge-green' : 'badge-gray'">{{ tpl.is_active ? "Actif" : "Inactif" }}</span>
              </td>
              <td class="text-right">
                <div class="action-btns">
                  <button @click="openForm(tpl)" class="action-btn" title="Modifier le modèle">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125"/></svg>
                  </button>
                  <button @click="deleteTemplate(tpl)" class="action-btn action-btn-danger" title="Supprimer le modèle">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!templates.length && !loading">
              <td colspan="6" class="empty-state">Aucun modele</td>
            </tr>
            <tr v-if="loading">
              <td colspan="6" class="loading-state">Chargement...</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="showForm" class="modal-overlay" @click.self="closeForm">
      <div class="modal-content modal-lg">
        <h3 class="card-title mb-4">{{ editId ? "Modifier le modele" : "Nouveau modele" }}</h3>

        <div class="mode-toggle mb-4">
          <button
            type="button"
            class="mode-btn"
            :class="{ active: mode === 'content' }"
            @click="mode = 'content'"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125"/></svg>
            Saisir le contenu
          </button>
          <button
            type="button"
            class="mode-btn"
            :class="{ active: mode === 'file' }"
            @click="mode = 'file'"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
            Importer un fichier (DOCX / PDF)
          </button>
        </div>

        <form @submit.prevent="saveTemplate">
          <div class="form-group">
            <label>Nom *</label>
            <input v-model="form.name" required class="form-input" />
          </div>

          <div class="form-grid">
            <div class="form-group">
              <label>Type</label>
              <select v-model="form.type" class="form-select">
                <option value="word">Word (DOCX)</option>
                <option value="pdf">PDF</option>
                <option value="text">Texte simple</option>
              </select>
            </div>
            <div class="form-group">
              <label>Categorie</label>
              <input v-model="form.category" class="form-input" placeholder="ex: courrier, arrete, decision..." />
            </div>
          </div>

          <div class="form-group">
            <label>Description</label>
            <textarea v-model="form.description" rows="2" class="form-textarea"></textarea>
          </div>

          <template v-if="mode === 'content'">
            <div class="form-group">
              <label>Contenu *</label>
              <textarea
                v-model="form.content"
                rows="10"
                required
                class="form-textarea font-mono"
                :placeholder="contentPlaceholder"
              ></textarea>
              <p class="form-hint">
                Utilisez les variables disponibles : {{ availableVars }}
              </p>
            </div>
          </template>

          <template v-else>
            <div class="form-group">
              <label>Fichier modele * (DOCX ou PDF)</label>

              <div
                class="file-dropzone"
                :class="{ 'has-file': !!selectedFile, 'dragover': dragging }"
                @click="openFilePicker"
                @dragover.prevent="dragging = true"
                @dragleave="dragging = false"
                @drop.prevent="onDropFile"
              >
                <div class="dz-icon">
                  <svg v-if="!selectedFile" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                  <svg v-else width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>

                <template v-if="!selectedFile">
                  <p class="dz-title">Cliquez ou glissez-deposez votre fichier</p>
                  <p class="dz-sub">DOCX, PDF, TXT ou MD — taille max 2 Mo</p>
                </template>
                <template v-else>
                  <div class="file-chip">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                    <span class="fc-name">{{ selectedFile.name }}</span>
                    <span class="fc-size">{{ formatFileSize(selectedFile.size) }}</span>
                    <button type="button" class="fc-clear" title="Retirer le fichier" @click.stop="clearFile">&times;</button>
                  </div>
                  <p class="dz-sub">Cliquez pour changer de fichier</p>
                </template>

                <input ref="fileInput" type="file" accept=".docx,.pdf,.txt,.md" @change="onFileSelected" />
              </div>

              <p v-if="editId && !selectedFile" class="form-hint">
                Laissez vide pour conserver le fichier actuel.
              </p>
            </div>
          </template>

          <div v-if="error" class="form-error">{{ error }}</div>

          <div class="form-actions">
            <button type="submit" :disabled="saving" class="btn-primary">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
              {{ saving ? "Enregistrement..." : "Enregistrer" }}
            </button>
            <button type="button" class="btn-secondary" @click="closeForm">Annuler</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import apiClient from "../../utils/axios";

interface Template {
  id: string;
  name: string;
  type: string;
  category: string;
  description: string;
  content: string;
  file_path: string;
  is_active: boolean;
}

const templates = ref<Template[]>([]);
const loading = ref(true);
const showForm = ref(false);
const editId = ref<string | null>(null);
const mode = ref<"content" | "file">("content");
const selectedFile = ref<File | null>(null);
const saving = ref(false);
const error = ref("");
const dragging = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);

const contentPlaceholder = "Contenu du modele avec variables : {{nom}} {{prenom}} {{fonction}} {{date}} {{objet}}...";
const availableVars = "{{nom}}, {{prenom}}, {{fonction}}, {{direction}}, {{date}}, {{numero}}, {{objet}}, {{reference}}, {{signataire}}...";

const form = ref({
  name: "",
  type: "word",
  category: "",
  description: "",
  content: "",
  is_active: true,
});

function typeLabel(t: string): string {
  return t === "word" ? "Word" : t === "pdf" ? "PDF" : "Texte";
}

function badgeClass(t: string): string {
  return t === "pdf" ? "badge-red" : t === "word" ? "badge-blue" : "badge-gray";
}

function formatFileSize(bytes: number): string {
  if (bytes < 1024) return bytes + " o";
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + " Ko";
  return (bytes / (1024 * 1024)).toFixed(2) + " Mo";
}

function openFilePicker() {
  fileInput.value?.click();
}

function clearFile() {
  selectedFile.value = null;
  if (fileInput.value) fileInput.value.value = "";
}

function onDropFile(event: DragEvent) {
  dragging.value = false;
  const file = event.dataTransfer?.files?.[0];
  if (file) setFile(file);
}

function setFile(file: File) {
  const ext = file.name.split(".").pop()?.toLowerCase() || "";
  const allowed = ["docx", "doc", "pdf", "txt", "md"];
  if (!allowed.includes(ext)) {
    error.value = "Format de fichier non supporte. Utilisez DOCX, PDF, TXT ou MD.";
    return;
  }
  if (file.size > 2 * 1024 * 1024) {
    error.value = "Le fichier depasse 2 Mo.";
    return;
  }
  error.value = "";
  selectedFile.value = file;
  if (ext === "pdf") form.value.type = "pdf";
  else if (ext === "docx" || ext === "doc") form.value.type = "word";
  else form.value.type = "text";
}

function onFileSelected(event: Event) {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0];
  if (file) setFile(file);
}

async function loadTemplates() {
  loading.value = true;
  try {
    const { data } = await apiClient.get("/templates");
    templates.value = data.data.data || [];
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
}

function openForm(t: Template | null) {
  editId.value = t?.id || null;
  mode.value = t?.content ? "content" : "file";
  selectedFile.value = null;
  error.value = "";
  dragging.value = false;
  form.value = {
    name: t?.name || "",
    type: t?.type || "word",
    category: t?.category || "",
    description: t?.description || "",
    content: t?.content || "",
    is_active: t?.is_active ?? true,
  };
  showForm.value = true;
}

function closeForm() {
  showForm.value = false;
  editId.value = null;
  selectedFile.value = null;
  error.value = "";
  dragging.value = false;
  if (fileInput.value) fileInput.value.value = "";
}

async function deleteTemplate(t: Template) {
  if (!confirm("Supprimer le modele ?")) return;
  try {
    await apiClient.delete("/templates/" + t.id);
    await loadTemplates();
  } catch (e: any) {
    alert(e.response?.data?.message || "Erreur lors de la suppression");
  }
}

async function saveTemplate() {
  error.value = "";
  saving.value = true;
  try {
    if (mode.value === "file" && !selectedFile.value && !editId.value) {
      error.value = "Veuillez choisir un fichier DOCX ou PDF.";
      return;
    }

    const payload = new FormData();
    payload.append("name", form.value.name);
    payload.append("type", form.value.type);
    payload.append("category", form.value.category);
    payload.append("description", form.value.description);

    if (mode.value === "content") {
      payload.append("content", form.value.content);
    } else if (selectedFile.value) {
      payload.append("file", selectedFile.value);
    }

    if (editId.value) {
      payload.append("_method", "PUT");
      await apiClient.post("/templates/" + editId.value, payload);
    } else {
      await apiClient.post("/templates", payload);
    }

    closeForm();
    await loadTemplates();
  } catch (e: any) {
    const msg = e.response?.data?.message;
    if (msg) {
      error.value = typeof msg === "string" ? msg : "Erreur de validation";
    } else {
      error.value = "Erreur lors de l'enregistrement";
    }
  } finally {
    saving.value = false;
  }
}

onMounted(loadTemplates);
</script>


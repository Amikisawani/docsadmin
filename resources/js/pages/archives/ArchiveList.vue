<template>
  <div class="page-container">
    <div class="page-header">
      <div>
        <h1 class="page-title">Archives</h1>
        <p class="page-subtitle">Consultez les dossiers archives et leurs echeances de conservation</p>
      </div>
      <div class="header-actions">
        <button class="btn-primary" @click="openArchiveDialog">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25 2.25M12 11.625l2.25-2.25M12 11.625l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
          Archiver un document
        </button>
        <button class="btn-secondary" @click="openBoxDialog">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
          Nouvelle boite
        </button>
      </div>
    </div>
    <div class="filter-card">
      <div class="filter-grid">
        <input v-model.trim="filters.search" class="form-input" placeholder="Reference ou document..." @keyup.enter="applyFilters" />
        <select v-model="filters.status" class="form-select">
          <option value="">Tous les statuts</option>
          <option value="active">Active</option>
          <option value="transferred">Transferee</option>
          <option value="destroyed">Detruite</option>
        </select>
        <select v-model="filters.box_id" class="form-select">
          <option value="">Toutes les boites</option>
          <option v-for="box in boxes" :key="box.id" :value="box.id">{{ box.code }} — {{ box.name }}</option>
        </select>
        <button class="btn-filter" @click="applyFilters">Filtrer</button>
      </div>
    </div>
    <p v-if="errorMessage" class="form-error">{{ errorMessage }}</p>
    <div class="table-card">
      <div class="table-wrapper">
        <table class="data-table">
          <thead>
            <tr>
              <th>Reference</th>
              <th>Document</th>
              <th>Boite</th>
              <th>Archive le</th>
              <th>Conservation</th>
              <th>Statut</th>
              <th class="text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="isLoading"><td colspan="7" class="loading-state">Chargement des archives...</td></tr>
            <tr v-for="archive in archives" :key="archive.id">
              <td class="font-mono font-medium">{{ archive.reference }}</td>
              <td>
                <router-link v-if="archive.document" :to="`/documents/${archive.document.id}`" class="link-action">{{ archive.document.document_number }}</router-link>
                <p class="max-w-xs truncate text-muted text-xs">{{ archive.document?.subject || "Document indisponible" }}</p>
              </td>
              <td>
                <span v-if="archive.archiveBox">
                  {{ archive.archiveBox.code }}
                  <span class="block text-xs text-muted">{{ archive.archiveBox.name }}</span>
                </span>
                <span v-else class="text-muted">Non classee</span>
              </td>
              <td class="text-muted">{{ formatDate(archive.archived_at) }}</td>
              <td><span class="badge" :class="conservationClass(archive)">{{ conservationLabel(archive) }}</span></td>
              <td><span class="badge" :class="statusBadge(archive.status)">{{ statusLabel(archive.status) }}</span></td>
              <td class="text-right">
                <button class="link-action" :disabled="restoringId === archive.id" @click="restoreArchive(archive)">{{ restoringId === archive.id ? "Restauration..." : "Desarchiver" }}</button>
              </td>
            </tr>
            <tr v-if="!isLoading && !archives.length"><td colspan="7" class="empty-state">Aucune archive ne correspond aux criteres selectionnes.</td></tr>
          </tbody>
        </table>
      </div>
      <div v-if="pagination.last_page > 1" class="pagination">
        <p class="text-muted">{{ pagination.from }}–{{ pagination.to }} sur {{ pagination.total }}</p>
        <div class="pagination-btns">
          <button class="btn-page" :disabled="pagination.current_page <= 1" @click="changePage(pagination.current_page - 1)">Precedent</button>
          <button class="btn-page" :disabled="pagination.current_page >= pagination.last_page" @click="changePage(pagination.current_page + 1)">Suivant</button>
        </div>
      </div>
    </div>

    <!-- Modal : Archiver un document -->
    <div v-if="isArchiveDialogOpen" class="modal-overlay" @click.self="closeArchiveDialog">
      <div class="modal-content modal-lg">
        <h3 class="card-title mb-4">Archiver un document</h3>
        <form @submit.prevent="submitArchive">
          <div class="form-group">
            <label>Document *</label>
            <select v-model="archiveForm.document_id" required class="form-select">
              <option value="" disabled>Selectionnez un document a archiver...</option>
              <option v-for="doc in eligibleDocs" :key="doc.id" :value="doc.id">
                {{ doc.document_number }} — {{ doc.subject }}
              </option>
            </select>
            <p v-if="!eligibleDocs.length" class="form-hint text-red-500">Aucun document eligible n'est disponible pour l'archivage.</p>
          </div>
          <div class="form-grid">
            <div class="form-group">
              <label>Boite d'archives (optionnel)</label>
              <select v-model="archiveForm.archive_box_id" class="form-select">
                <option value="">Aucune boite</option>
                <option v-for="box in boxes" :key="box.id" :value="box.id">{{ box.code }} — {{ box.name }}</option>
              </select>
            </div>
            <div class="form-group">
              <label>Duree de conservation (jours)</label>
              <input v-model.number="archiveForm.conservation_until_days" type="number" min="0" class="form-input" placeholder="ex: 3650" />
            </div>
          </div>
          <div class="form-group">
            <label>Notes</label>
            <textarea v-model.trim="archiveForm.notes" rows="2" class="form-textarea" style="min-height:70px" placeholder="Notes optionnelles sur cet archivage"></textarea>
          </div>
          <p v-if="archiveError" class="form-error">{{ archiveError }}</p>
          <div class="form-actions">
            <button type="submit" class="btn-primary" :disabled="isSavingArchive">{{ isSavingArchive ? "Archivage..." : "Archiver" }}</button>
            <button type="button" class="btn-secondary" @click="closeArchiveDialog">Annuler</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal : Nouvelle boite -->
    <div v-if="isBoxDialogOpen" class="modal-overlay" @click.self="closeBoxDialog">
      <div class="modal-content">
        <h3 class="card-title mb-4">Nouvelle boite d archives</h3>
        <form @submit.prevent="createBox">
          <div class="form-group"><label>Nom *</label><input v-model.trim="boxForm.name" required maxlength="255" class="form-input" placeholder="Ex. Boite des decisions 2026" /></div>
          <div class="form-grid">
            <div class="form-group"><label>Categorie</label><input v-model.trim="boxForm.category" maxlength="255" class="form-input" placeholder="Decisions" /></div>
            <div class="form-group"><label>Capacite</label><input v-model.number="boxForm.capacity" type="number" min="1" class="form-input" placeholder="100" /></div>
          </div>
          <div class="form-group"><label>Emplacement</label><input v-model.trim="boxForm.location" maxlength="255" class="form-input" placeholder="Salle A · Etagere 3" /></div>
          <div class="form-group"><label>Description</label><textarea v-model.trim="boxForm.description" rows="3" class="form-textarea" style="min-height:80px"></textarea></div>
          <p v-if="boxError" class="form-error">{{ boxError }}</p>
          <div class="form-actions">
            <button type="submit" class="btn-primary" :disabled="isSavingBox">{{ isSavingBox ? "Creation..." : "Creer la boite" }}</button>
            <button type="button" class="btn-secondary" @click="closeBoxDialog">Annuler</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
<script setup lang="ts">
import { computed, onMounted, reactive, ref } from "vue";
import apiClient from "../../utils/axios";
import type { Archive, ArchiveBox, Document, PaginatedResponse } from "../../types";

const archives = ref<Archive[]>([]);
const boxes = ref<ArchiveBox[]>([]);
const eligibleDocs = ref<Document[]>([]);
const isLoading = ref(false);
const errorMessage = ref("");
const restoringId = ref<string | null>(null);

const isArchiveDialogOpen = ref(false);
const isSavingArchive = ref(false);
const archiveError = ref("");
const isBoxDialogOpen = ref(false);
const isSavingBox = ref(false);
const boxError = ref("");

const pagination = reactive({ current_page: 1, last_page: 1, total: 0, from: 0, to: 0 });
const filters = reactive({ search: "", status: "", box_id: "", expiring: false });

const archiveForm = reactive({ document_id: "", archive_box_id: "", conservation_until_days: null as number | null, notes: "" });
const boxForm = reactive({ name: "", category: "", location: "", capacity: null as number | null, description: "" });

const expiringCount = computed(() => archives.value.filter(isExpiringSoon).length);
const permanentCount = computed(() => archives.value.filter((a: Archive) => a.conservation_duration === "permanent").length);

function formatDate(d: string | null): string { return d ? new Date(d).toLocaleDateString("fr-FR", { day: "2-digit", month: "short", year: "numeric" }) : "—"; }
function isExpiringSoon(a: Archive): boolean { return !!a.conservation_until && new Date(a.conservation_until) <= new Date(Date.now() + 30 * 24 * 60 * 60 * 1000); }
function conservationLabel(a: Archive): string { if (a.conservation_duration === "permanent") return "Permanente"; return a.conservation_until ? `Jusqu au ${formatDate(a.conservation_until)}` : a.conservation_duration || "Non definie"; }
function conservationClass(a: Archive): string { return isExpiringSoon(a) ? "badge-amber" : a.conservation_duration === "permanent" ? "badge-blue" : "badge-gray"; }
function statusLabel(s: string): string { return ({ active: "Active", transferred: "Transferee", destroyed: "Detruite" } as Record<string,string>)[s] || s; }
function statusBadge(s: string): string { return ({ active: "badge-green", transferred: "badge-purple", destroyed: "badge-gray" } as Record<string,string>)[s] || "badge-gray"; }

async function loadBoxes() {
  const { data } = await apiClient.get("/archive-boxes", { params: { per_page: 100, status: "active" } });
  boxes.value = data.data?.data || [];
}

async function loadEligibleDocs() {
  try {
    const { data } = await apiClient.get("/archives/eligible-documents", { params: { per_page: 100 } });
    eligibleDocs.value = data.data?.data || [];
  } catch (e) {
    console.error(e);
  }
}

async function loadArchives() {
  isLoading.value = true; errorMessage.value = "";
  try {
    const params: Record<string,string|number|boolean> = { page: pagination.current_page, per_page: 15 };
    if (filters.search) params.search = filters.search;
    if (filters.status) params.status = filters.status;
    if (filters.box_id) params.box_id = filters.box_id;
    const { data } = await apiClient.get("/archives", { params });
    const r: PaginatedResponse<Archive> = data.data;
    archives.value = r.data || [];
    Object.assign(pagination, { current_page: r.current_page, last_page: r.last_page, total: r.total, from: r.from || 0, to: r.to || 0 });
  } catch (e) {
    errorMessage.value = "Impossible de charger les archives.";
    console.error(e);
  } finally { isLoading.value = false; }
}

function applyFilters() { pagination.current_page = 1; loadArchives(); }
function changePage(p: number) { pagination.current_page = p; loadArchives(); }

async function restoreArchive(a: Archive) {
  if (!confirm("Desarchiver ?")) return;
  restoringId.value = a.id;
  try {
    await apiClient.delete("/archives/" + a.id);
    await loadArchives();
    await loadEligibleDocs();
  } catch (e) {
    console.error(e);
  } finally { restoringId.value = null; }
}

// --- Modal Archiver ---
function openArchiveDialog() {
  archiveForm.document_id = "";
  archiveForm.archive_box_id = "";
  archiveForm.conservation_until_days = null;
  archiveForm.notes = "";
  archiveError.value = "";
  loadEligibleDocs();
  isArchiveDialogOpen.value = true;
}
function closeArchiveDialog() { if (!isSavingArchive.value) isArchiveDialogOpen.value = false; }

async function submitArchive() {
  isSavingArchive.value = true; archiveError.value = "";
  try {
    if (!archiveForm.document_id) {
      archiveError.value = "Veuillez selectionner un document.";
      return;
    }
    const payload: Record<string, unknown> = { document_id: archiveForm.document_id };
    if (archiveForm.archive_box_id) payload.archive_box_id = archiveForm.archive_box_id;
    if (archiveForm.conservation_until_days && archiveForm.conservation_until_days > 0) payload.conservation_until_days = archiveForm.conservation_until_days;
    if (archiveForm.notes) payload.notes = archiveForm.notes;

    await apiClient.post(`/documents/${archiveForm.document_id}/archive`, payload);
    isArchiveDialogOpen.value = false;
    await loadArchives();
    await loadEligibleDocs();
  } catch (e: any) {
    archiveError.value = e.response?.data?.message || "Erreur lors de l'archivage du document.";
  } finally { isSavingArchive.value = false; }
}

// --- Modal Boite ---
function openBoxDialog() {
  boxForm.name = ""; boxForm.category = ""; boxForm.location = ""; boxForm.capacity = null; boxForm.description = "";
  boxError.value = "";
  isBoxDialogOpen.value = true;
}
function closeBoxDialog() { if (!isSavingBox.value) isBoxDialogOpen.value = false; }

async function createBox() {
  isSavingBox.value = true; boxError.value = "";
  try {
    const payload = Object.fromEntries(Object.entries(boxForm).filter(([,v]) => v !== "" && v !== null));
    await apiClient.post("/archive-boxes", payload);
    await loadBoxes();
    isBoxDialogOpen.value = false;
  } catch (e: any) {
    boxError.value = e.response?.data?.message || "Erreur";
  } finally { isSavingBox.value = false; }
}

onMounted(async () => {
  await Promise.all([loadBoxes(), loadArchives(), loadEligibleDocs()]);
});
</script>

<style scoped>
.header-actions {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  flex-wrap: wrap;
}
.block { display: block; }
</style>

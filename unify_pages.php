<?php
/**
 * Unifie toutes les pages Vue avec le design professionnel du dashboard.
 * Inspire de DocuWare, Alfresco, M-Files.
 */

$base = __DIR__ . '/resources/js/pages';

// ========== 1. DocumentList - Uniformise ==========
file_put_contents("$base/documents/DocumentList.vue", '<template>
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
    <div class="table-card">
      <table class="data-table">
        <thead>
          <tr>
            <th>N Document</th>
            <th>Objet</th>
            <th>Type</th>
            <th>Statut</th>
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
            <td><span class="badge" :class="statusBadge(doc.status)">{{ statusLabel(doc.status) }}</span></td>
            <td>{{ doc.author?.name || "-" }}</td>
            <td class="text-muted">{{ formatDate(doc.document_date) }}</td>
            <td class="text-right"><router-link :to="`/documents/${doc.id}`" class="link-action">Voir</router-link></td>
          </tr>
          <tr v-if="!documents.length">
            <td colspan="7" class="empty-state">Aucun document trouve</td>
          </tr>
        </tbody>
      </table>
      <div v-if="pagination.last_page > 1" class="pagination">
        <p class="text-muted">Page {{ pagination.current_page }} sur {{ pagination.last_page }}</p>
        <div class="pagination-btns">
          <button :disabled="pagination.current_page <= 1" @click="changePage(pagination.current_page - 1)" class="btn-page">Precedent</button>
          <button :disabled="pagination.current_page >= pagination.last_page" @click="changePage(pagination.current_page + 1)" class="btn-page">Suivant</button>
        </div>
    </div>
</template>
<script setup lang="ts">
import { ref, reactive, onMounted } from "vue";
import apiClient from "../../utils/axios";
import type { Document } from "../../types";
const documents = ref<Document[]>([]);
const pagination = reactive({ current_page: 1, last_page: 1, total: 0 });
const filters = reactive({ search: "", type: "", status: "" });
const documentTypes: Record<string,string> = { courrier_entrant: "Courrier entrant", courrier_sortant: "Courrier sortant", note: "Note", notification: "Notification", decision: "Decision", arrete: "Arrete", rapport: "Rapport", contrat: "Contrat", convention: "Convention", demande: "Demande", mission: "Mission", facture: "Facture", autre: "Autre" };
function getTypeLabel(t: string): string { return documentTypes[t] || t; }
function statusLabel(s: string): string { return ({ draft: "Brouillon", pending: "En attente", approved: "Approuve", signed: "Signe", rejected: "Rejete", archived: "Archive" } as Record<string,string>)[s] || s; }
function statusBadge(s: string): string { return ({ draft: "badge-gray", pending: "badge-amber", approved: "badge-green", signed: "badge-blue", rejected: "badge-red", archived: "badge-purple" } as Record<string,string>)[s] || "badge-gray"; }
function formatDate(d: string): string { return new Date(d).toLocaleDateString("fr-FR"); }
function changePage(p: number) { pagination.current_page = p; loadDocuments(); }
async function loadDocuments() {
  try {
    const params: any = { page: pagination.current_page, per_page: 15 };
    if (filters.search) params.search = filters.search;
    if (filters.type) params.type = filters.type;
    if (filters.status) params.status = filters.status;
    const { data } = await apiClient.get("/documents", { params });
    documents.value = data.data.data || [];
    pagination.current_page = data.data.current_page;
    pagination.last_page = data.data.last_page;
    pagination.total = data.data.total;
  } catch (e) { console.error(e); }
}
onMounted(loadDocuments);
</script>');

echo "1/6 DocumentList OK\n";

// ========== 2. DocumentForm - Uniformise ==========
file_put_contents("$base/documents/DocumentForm.vue", '<template>
  <div class="page-container">
    <div class="page-header">
      <div>
        <h1 class="page-title">{{ isEdit ? "Modifier le document" : "Nouveau document" }}</h1>
        <p class="page-subtitle">{{ isEdit ? "Modifier les informations du document" : "Creer un nouveau document administratif" }}</p>
      </div>
    <div class="form-card">
      <form @submit.prevent="saveDocument">
        <div class="form-grid">
          <div class="form-group">
            <label>Type de document *</label>
            <select v-model="form.document_type" required class="form-select">
              <option value="">Selectionner...</option>
              <option v-for="(label, key) in documentTypes" :key="key" :value="key">{{ label }}</option>
            </select>
          </div>
          <div class="form-group">
            <label>Reference</label>
            <input v-model="form.reference" class="form-input" />
          </div>
        <div class="form-group">
          <label>Objet *</label>
          <input v-model="form.subject" required class="form-input" />
        </div>
        <div class="form-grid">
          <div class="form-group">
            <label>Confidentialite</label>
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
        <div class="form-group">
          <label>Contenu</label>
          <textarea v-model="form.content" rows="8" class="form-textarea"></textarea>
        </div>
        <div v-if="error" class="form-error">{{ error }}</div>
        <div class="form-actions">
          <button type="submit" :disabled="loading" class="btn-primary">{{ loading ? "Enregistrement..." : (isEdit ? "Mettre a jour" : "Creer le document") }}</button>
          <router-link to="/documents" class="btn-secondary">Annuler</router-link>
        </div>
      </form>
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
const form = ref({ document_type: "", reference: "", subject: "", confidentiality: "interne", document_date: new Date().toISOString().split("T")[0], content: "" });
const documentTypes: Record<string,string> = { courrier_entrant: "Courrier entrant", courrier_sortant: "Courrier sortant", note: "Note", notification: "Notification", decision: "Decision", arrete: "Arrete", decret: "Decret", circulaire: "Circulaire", proces_verbal: "Proces-verbal", rapport: "Rapport", contrat: "Contrat", convention: "Convention", demande: "Demande", conge: "Conge", mission: "Mission", facture: "Facture", autre: "Autre" };
async function saveDocument() {
  error.value = ""; loading.value = true;
  try {
    if (isEdit.value) await apiClient.put(`/documents/${route.params.id}`, form.value);
    else await apiClient.post("/documents", form.value);
    router.push("/documents");
  } catch (e: any) { error.value = e.response?.data?.message || "Erreur lors de l enregistrement"; }
  finally { loading.value = false; }
}
onMounted(async () => {
  if (isEdit.value) {
    try {
      const { data } = await apiClient.get(`/documents/${route.params.id}`);
      const doc = data.data;
      form.value = { document_type: doc.document_type, reference: doc.reference || "", subject: doc.subject, confidentiality: doc.confidentiality, document_date: doc.document_date, content: doc.content || "" };
    } catch { router.push("/documents"); }
  }
});
</script>');

echo "2/6 DocumentForm OK\n";

// ========== 3. DocumentShow - Uniformise ==========
file_put_contents("$base/documents/DocumentShow.vue", '<template>
  <div v-if="document" class="page-container">
    <div class="page-header">
      <div>
        <h1 class="page-title">{{ document.subject }}</h1>
        <p class="page-subtitle">{{ document.document_number }}</p>
      </div>
      <div class="flex gap-2">
        <router-link :to="`/documents/${document.id}/edit`" class="btn-secondary">Modifier</router-link>
      </div>
    <div class="detail-grid">
      <div class="detail-main">
        <div class="info-card">
          <h3 class="card-title">Informations</h3>
          <div class="info-grid">
            <div><span class="info-label">Type :</span><span class="info-value">{{ document.document_type }}</span></div>
            <div><span class="info-label">Statut :</span><span class="badge" :class="statusBadge(document.status)">{{ statusLabel(document.status) }}</span></div>
            <div><span class="info-label">Version :</span><span class="info-value">{{ document.version }}</span></div>
            <div><span class="info-label">Confidentialite :</span><span class="info-value">{{ document.confidentiality }}</span></div>
            <div><span class="info-label">Date :</span><span class="info-value">{{ formatDate(document.document_date) }}</span></div>
            <div><span class="info-label">Auteur :</span><span class="info-value">{{ document.author?.name }}</span></div>
        </div>
        <div v-if="document.content" class="info-card">
          <h3 class="card-title">Contenu</h3>
          <p class="text-sm whitespace-pre-wrap">{{ document.content }}</p>
        </div>
        <div class="info-card">
          <h3 class="card-title">Historique</h3>
          <div class="timeline">
            <div v-for="h in document.histories" :key="h.id" class="timeline-item">
              <div class="timeline-dot"></div>
              <div><p class="text-sm">{{ h.action }}</p><p class="text-xs text-muted">{{ h.user?.name }} · {{ formatDate(h.created_at) }}</p></div>
          </div>
      </div>
      <div class="detail-side">
        <div class="info-card">
          <h3 class="card-title">Pieces jointes</h3>
          <div v-if="document.attachments?.length" class="space-y-2">
            <div v-for="att in document.attachments" :key="att.id" class="attachment-item">{{ att.original_name }}</div>
          <p v-else class="text-muted text-sm">Aucune piece jointe</p>
        </div>
        <div class="info-card">
          <h3 class="card-title">Signatures</h3>
          <div v-if="document.signatures?.length" class="space-y-2">
            <div v-for="sig in document.signatures" :key="sig.id" class="signature-item">
              <p>Signe par {{ sig.signer?.name }}</p>
              <p class="text-xs text-muted">{{ formatDate(sig.signed_at) }}</p>
            </div>
          <p v-else class="text-muted text-sm">Non signe</p>
        </div>
    </div>
  <div v-else class="loading-state">Chargement...</div>
</template>
<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useRoute } from "vue-router";
import apiClient from "../../utils/axios";
import type { Document } from "../../types";
const route = useRoute();
const document = ref<Document | null>(null);
function statusLabel(s: string): string { return ({ draft:"Brouillon", pending:"En attente", approved:"Approuve", signed:"Signe", rejected:"Rejete", archived:"Archive" } as Record<string,string>)[s] || s; }
function statusBadge(s: string): string { return ({ draft:"badge-gray", pending:"badge-amber", approved:"badge-green", signed:"badge-blue", rejected:"badge-red", archived:"badge-purple" } as Record<string,string>)[s] || "badge-gray"; }
function formatDate(d: string): string { return new Date(d).toLocaleDateString("fr-FR", { day:"numeric", month:"short", year:"numeric", hour:"2-digit", minute:"2-digit" }); }
onMounted(async () => {
  try { const { data } = await apiClient.get(`/documents/${route.params.id}`); document.value = data.data; }
  catch (e) { console.error(e); }
});
</script>');

echo "3/6 DocumentShow OK\n";

// ========== 4. UserList - Full CRUD ==========
file_put_contents("$base/users/UserList.vue", '<template>
  <div class="page-container">
    <div class="page-header">
      <div>
        <h1 class="page-title">Utilisateurs</h1>
        <p class="page-subtitle">Gerer les comptes utilisateurs de la plateforme</p>
      </div>
      <router-link to="/users/create" class="btn-primary">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
        Nouvel utilisateur
      </router-link>
    </div>
    <div class="table-card">
      <table class="data-table">
        <thead>
          <tr>
            <th>Nom</th>
            <th>Email</th>
            <th>Fonction</th>
            <th>Service</th>
            <th>Statut</th>
            <th class="text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="user in users" :key="user.id">
            <td class="font-medium">{{ user.name }}</td>
            <td class="text-muted">{{ user.email }}</td>
            <td>{{ user.fonction || "-" }}</td>
            <td>{{ user.service || "-" }}</td>
            <td><span class="badge" :class="user.is_active ? \'badge-green\' : \'badge-gray\'">{{ user.is_active ? "Actif" : "Inactif" }}</span></td>
            <td class="text-right">
              <router-link :to="`/users/${user.id}/edit`" class="link-action">Modifier</router-link>
            </td>
          </tr>
          <tr v-if="!users.length">
            <td colspan="6" class="empty-state">Aucun utilisateur trouve</td>
          </tr>
        </tbody>
      </table>
    </div>
</template>
<script setup lang="ts">
import { ref, onMounted } from "vue";
import apiClient from "../../utils/axios";
interface User { id: string; name: string; email: string; fonction: string; service: string; is_active: boolean; }
const users = ref<User[]>([]);
onMounted(async () => {
  try { const { data } = await apiClient.get("/users"); users.value = data.data.data || []; }
  catch (e) { console.error(e); }
});
</script>');

echo "4/6 UserList OK\n";

// ========== 5. UserForm - Full ==========
file_put_contents("$base/users/UserForm.vue", '<template>
  <div class="page-container">
    <div class="page-header">
      <div>
        <h1 class="page-title">{{ isEdit ? "Modifier l utilisateur" : "Nouvel utilisateur" }}</h1>
        <p class="page-subtitle">{{ isEdit ? "Modifier les informations de l utilisateur" : "Creer un nouveau compte utilisateur" }}</p>
      </div>
    <div class="form-card">
      <form @submit.prevent="saveUser">
        <div class="form-grid">
          <div class="form-group">
            <label>Nom complet *</label>
            <input v-model="form.name" required class="form-input" />
          </div>
          <div class="form-group">
            <label>Email *</label>
            <input v-model="form.email" type="email" required class="form-input" />
          </div>
        <div class="form-grid">
          <div class="form-group">
            <label>Mot de passe {{ isEdit ? "(laisser vide pour conserver)" : "*" }}</label>
            <input v-model="form.password" type="password" :required="!isEdit" class="form-input" />
          </div>
          <div class="form-group">
            <label>Fonction</label>
            <input v-model="form.fonction" class="form-input" />
          </div>
        <div class="form-group">
          <label>Service</label>
          <input v-model="form.service" class="form-input" />
        </div>
        <div v-if="error" class="form-error">{{ error }}</div>
        <div class="form-actions">
          <button type="submit" :disabled="loading" class="btn-primary">{{ loading ? "Enregistrement..." : (isEdit ? "Mettre a jour" : "Creer l utilisateur") }}</button>
          <router-link to="/users" class="btn-secondary">Annuler</router-link>
        </div>
      </form>
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
const form = ref({ name: "", email: "", password: "", fonction: "", service: "" });
async function saveUser() {
  error.value = ""; loading.value = true;
  try {
    const payload = { ...form.value };
    if (isEdit.value && !payload.password) delete payload.password;
    if (isEdit.value) await apiClient.put(`/users/${route.params.id}`, payload);
    else await apiClient.post("/users", payload);
    router.push("/users");
  } catch (e: any) { error.value = e.response?.data?.message || "Erreur"; }
  finally { loading.value = false; }
}
onMounted(async () => {
  if (isEdit.value) {
    try {
      const { data } = await apiClient.get(`/users/${route.params.id}`);
      const u = data.data;
      form.value = { name: u.name, email: u.email, password: "", fonction: u.fonction || "", service: u.service || "" };
    } catch { router.push("/users"); }
  }
});
</script>');

echo "5/6 UserForm OK\n";

// ========== 6. DepartmentTree - Full ==========
file_put_contents("$base/departments/DepartmentTree.vue", '<template>
  <div class="page-container">
    <div class="page-header">
      <div>
        <h1 class="page-title">Organigramme</h1>
        <p class="page-subtitle">Structure hierarchique de l organisation</p>
      </div>
      <button @click="showForm = true; editId = null" class="btn-primary">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
        Nouveau departement
      </button>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="lg:col-span-2">
        <div class="table-card">
          <div v-if="loading" class="loading-state">Chargement...</div>
          <div v-else-if="departments.length === 0" class="empty-state">Aucun departement</div>
          <div v-else class="tree-view">
            <div v-for="dept in rootDepartments" :key="dept.id" class="tree-node">
              <div class="tree-node-header" @click="toggleExpand(dept.id)">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21"/></svg>
                <span class="font-medium">{{ dept.name }}</span>
                <span class="text-muted text-xs ml-2">{{ dept.code }}</span>
                <span class="ml-auto flex gap-2">
                  <button @click.stop="editDepartment(dept)" class="link-action">Modifier</button>
                  <button @click.stop="deleteDepartment(dept)" class="link-action text-red-500">Supprimer</button>
                </span>
              </div>
              <div v-if="expanded[dept.id]" class="tree-children">
                <div v-for="child in getChildren(dept.id)" :key="child.id" class="tree-node">
                  <div class="tree-node-header pl-8">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614fM16.5 9.75V3.75m0 0h3.75m-3.75 0H9.75m0 0H5.25m0 0V3.75"/></svg>
                    <span>{{ child.name }}</span>
                    <span class="text-muted text-xs ml-2">{{ child.code }}</span>
                    <span class="ml-auto flex gap-2">
                      <button @click.stop="editDepartment(child)" class="link-action">Modifier</button>
                      <button @click.stop="deleteDepartment(child)" class="link-action text-red-500">Supprimer</button>
                    </span>
                  </div>
              </div>
          </div>
      </div>
      <div>
        <div v-if="showForm" class="form-card">
          <h3 class="card-title mb-4">{{ editId ? "Modifier" : "Nouveau departement" }}</h3>
          <form @submit.prevent="saveDepartment">
            <div class="form-group">
              <label>Nom *</label>
              <input v-model="deptForm.name" required class="form-input" />
            </div>
            <div class="form-group">
              <label>Code</label>
              <input v-model="deptForm.code" class="form-input" />
            </div>
            <div class="form-group">
              <label>Parent</label>
              <select v-model="deptForm.parent_id" class="form-select">
                <option :value="null">Aucun (racine)</option>
                <option v-for="d in departments" :key="d.id" :value="d.id" :disabled="d.id === editId">{{ d.name }}</option>
              </select>
            </div>
            <div class="form-actions">
              <button type="submit" class="btn-primary">Enregistrer</button>
              <button type="button" @click="showForm = false" class="btn-secondary">Annuler</button>
            </div>
          </form>
        </div>
    </div>
</template>
<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import apiClient from "../../utils/axios";
interface Department { id: string; name: string; code: string; parent_id: string | null; children: Department[]; }
const departments = ref<Department[]>([]);
const loading = ref(true);
const showForm = ref(false);
const editId = ref<string | null>(null);
const expanded = ref<Record<string,boolean>>({});
const deptForm = ref({ name: "", code: "", parent_id: null as string | null });
const rootDepartments = computed(() => departments.value.filter(d => !d.parent_id));
function getChildren(parentId: string): Department[] { return departments.value.filter(d => d.parent_id === parentId); }
function toggleExpand(id: string) { expanded.value[id] = !expanded.value[id]; }
function editDepartment(d: Department) { editId.value = d.id; deptForm.value = { name: d.name, code: d.code || "", parent_id: d.parent_id }; showForm.value = true; }
async function deleteDepartment(d: Department) { if (!confirm("Supprimer " + d.name + " ?")) return; await apiClient.delete("/departments/" + d.id); await loadDepartments(); }
async function saveDepartment() {
  try {
    if (editId.value) await apiClient.put("/departments/" + editId.value, deptForm.value);
    else await apiClient.post("/departments", deptForm.value);
    showForm.value = false; editId.value = null; deptForm.value = { name: "", code: "", parent_id: null };
    await loadDepartments();
  } catch (e: any) { alert(e.response?.data?.message || "Erreur"); }
}
async function loadDepartments() {
  loading.value = true;
  try { const { data } = await apiClient.get("/departments"); departments.value = data.data.data || []; }
  catch (e) { console.error(e); }
  finally { loading.value = false; }
}
onMounted(loadDepartments);
</script>');

echo "6/6 DepartmentTree OK\n";

// ========== Remaining pages ==========
file_put_contents("$base/templates/TemplateList.vue", '<template>
  <div class="page-container">
    <div class="page-header">
      <div>
        <h1 class="page-title">Modeles de documents</h1>
        <p class="page-subtitle">Gerer les modeles de documents Word et PDF</p>
      </div>
      <button class="btn-primary" @click="showForm = true; editId = null">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
        Nouveau modele
      </button>
    </div>
    <div class="table-card">
      <table class="data-table">
        <thead><tr><th>Nom</th><th>Type</th><th>Document type</th><th>Statut</th><th class="text-right">Actions</th></tr></thead>
        <tbody>
          <tr v-for="tpl in templates" :key="tpl.id">
            <td class="font-medium">{{ tpl.name }}</td>
            <td>{{ tpl.type }}</td>
            <td>{{ tpl.document_type || "-" }}</td>
            <td><span class="badge" :class="tpl.is_active ? \'badge-green\' : \'badge-gray\'">{{ tpl.is_active ? "Actif" : "Inactif" }}</span></td>
            <td class="text-right">
              <button @click="editTemplate(tpl)" class="link-action mr-2">Modifier</button>
              <button @click="deleteTemplate(tpl)" class="link-action text-red-500">Supprimer</button>
            </td>
          </tr>
          <tr v-if="!templates.length"><td colspan="5" class="empty-state">Aucun modele</td></tr>
        </tbody>
      </table>
    </div>
    <div v-if="showForm" class="modal-overlay" @click.self="showForm = false">
      <div class="modal-content">
        <h3 class="card-title mb-4">{{ editId ? "Modifier" : "Nouveau modele" }}</h3>
        <form @submit.prevent="saveTemplate">
          <div class="form-group"><label>Nom *</label><input v-model="form.name" required class="form-input" /></div>
          <div class="form-grid">
            <div class="form-group"><label>Type</label><select v-model="form.type" class="form-select"><option value="word">Word</option><option value="pdf">PDF</option></select></div>
            <div class="form-group"><label>Document type</label><input v-model="form.document_type" class="form-input" /></div>
          <div class="form-group"><label>Contenu *</label><textarea v-model="form.content" rows="6" required class="form-textarea font-mono" placeholder="Contenu du modele avec variables {{nom}} {{prenom}}..."></textarea></div>
          <div class="form-actions">
            <button type="submit" class="btn-primary">Enregistrer</button>
            <button type="button" @click="showForm = false" class="btn-secondary">Annuler</button>
          </div>
        </form>
      </div>
  </div>
</template>
<script setup lang="ts">
import { ref, onMounted } from "vue";
import apiClient from "../../utils/axios";
interface Template { id: string; name: string; type: string; document_type: string; content: string; is_active: boolean; }
const templates = ref<Template[]>([]);
const showForm = ref(false);
const editId = ref<string | null>(null);
const form = ref({ name: "", type: "word", document_type: "", content: "" });
async function loadTemplates() { try { const { data } = await apiClient.get("/templates"); templates.value = data.data.data || []; } catch (e) { console.error(e); } }
function editTemplate(t: Template) { editId.value = t.id; form.value = { name: t.name, type: t.type, document_type: t.document_type || "", content: t.content || "" }; showForm.value = true; }
async function deleteTemplate(t: Template) { if (!confirm("Supprimer le modele ?")) return; await apiClient.delete("/templates/" + t.id); await loadTemplates(); }
async function saveTemplate() {
  try {
    if (editId.value) await apiClient.put("/templates/" + editId.value, form.value);
    else await apiClient.post("/templates", form.value);
    showForm.value = false; editId.value = null; form.value = { name: "", type: "word", document_type: "", content: "" };
    await loadTemplates();
  } catch (e: any) { alert(e.response?.data?.message || "Erreur"); }
}
onMounted(loadTemplates);
</script>');

echo "7/10 TemplateList OK\n";

file_put_contents("$base/workflows/WorkflowList.vue", '<template>
  <div class="page-container">
    <div class="page-header">
      <div>
        <h1 class="page-title">Workflows</h1>
        <p class="page-subtitle">Circuits de validation des documents</p>
      </div>
      <button class="btn-primary" @click="showForm = true; editId = null">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
        Nouveau workflow
      </button>
    </div>
    <div class="table-card">
      <table class="data-table">
        <thead><tr><th>Nom</th><th>Type document</th><th>Etapes</th><th>Statut</th><th class="text-right">Actions</th></tr></thead>
        <tbody>
          <tr v-for="wf in workflows" :key="wf.id">
            <td class="font-medium">{{ wf.name }}</td>
            <td>{{ wf.document_type || "-" }}</td>
            <td>{{ wf.steps?.length || 0 }} etape(s)</td>
            <td><span class="badge" :class="wf.is_active ? \'badge-green\' : \'badge-gray\'">{{ wf.is_active ? "Actif" : "Inactif" }}</span></td>
            <td class="text-right">
              <button @click="editWorkflow(wf)" class="link-action mr-2">Modifier</button>
              <button @click="deleteWorkflow(wf)" class="link-action text-red-500">Supprimer</button>
            </td>
          </tr>
          <tr v-if="!workflows.length"><td colspan="5" class="empty-state">Aucun workflow</td></tr>
        </tbody>
      </table>
    </div>
    <div v-if="showForm" class="modal-overlay" @click.self="showForm = false">
      <div class="modal-content">
        <h3 class="card-title mb-4">{{ editId ? "Modifier" : "Nouveau workflow" }}</h3>
        <form @submit.prevent="saveWorkflow">
          <div class="form-group"><label>Nom *</label><input v-model="form.name" required class="form-input" /></div>
          <div class="form-group"><label>Type document</label><input v-model="form.document_type" class="form-input" /></div>
          <div class="form-group"><label>Etapes (une par ligne : nom,role)</label><textarea v-model="stepsText" rows="4" class="form-textarea font-mono" placeholder="Validation chef,admin&#10;Approbation dir,director&#10;Signature,admin"></textarea></div>
          <div class="form-actions">
            <button type="submit" class="btn-primary">Enregistrer</button>
            <button type="button" @click="showForm = false" class="btn-secondary">Annuler</button>
          </div>
        </form>
      </div>
  </div>
</template>
<script setup lang="ts">
import { ref, onMounted } from "vue";
import apiClient from "../../utils/axios";
interface Workflow { id: string; name: string; document_type: string; steps: any[]; is_active: boolean; }
const workflows = ref<Workflow[]>([]);
const showForm = ref(false);
const editId = ref<string | null>(null);
const form = ref({ name: "", document_type: "", steps: [] as any[] });
const stepsText = ref("");
async function loadWorkflows() { try { const { data } = await apiClient.get("/workflows"); workflows.value = data.data.data || []; } catch (e) { console.error(e); } }
function editWorkflow(w: Workflow) { editId.value = w.id; form.value = { name: w.name, document_type: w.document_type || "", steps: w.steps || [] }; stepsText.value = (w.steps || []).map((s: any) => s.name + "," + (s.role || "")).join("\n"); showForm.value = true; }
async function deleteWorkflow(w: Workflow) { if (!confirm("Supprimer le workflow ?")) return; await apiClient.delete("/workflows/" + w.id); await loadWorkflows(); }
async function saveWorkflow() {
  try {
    form.value.steps = stepsText.value.split("\n").filter(Boolean).map((line, i) => {
      const parts = line.split(",");
      return { name: parts[0].trim(), role: (parts[1] || "").trim(), order: i + 1 };
    });
    if (editId.value) await apiClient.put("/workflows/" + editId.value, form.value);
    else await apiClient.post("/workflows", form.value);
    showForm.value = false; editId.value = null; form.value = { name: "", document_type: "", steps: [] }; stepsText.value = "";
    await loadWorkflows();
  } catch (e: any) { alert(e.response?.data?.message || "Erreur"); }
}
onMounted(loadWorkflows);
</script>');

echo "8/10 WorkflowList OK\n";

file_put_contents("$base/signatures/SignatureList.vue", '<template>
  <div class="page-container">

    <div class="page-header">
      <div>
        <h1 class="page-title">Signatures</h1>
        <p class="page-subtitle">Gerer vos signatures electroniques</p>
      </div>

      <button class="btn-primary" @click="openForm">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        Nouvelle signature
      </button>
    </div>


    <div class="table-card">

      <table class="data-table">
        <thead>
          <tr>
            <th>Label</th>
            <th>Type</th>
            <th>Defaut</th>
            <th>Statut</th>
            <th class="text-right">Actions</th>
          </tr>
        </thead>

        <tbody>

          <tr v-for="sig in signatures" :key="sig.id">

            <td class="font-medium">
              {{ sig.label || "Signature" }}
            </td>

            <td>
              <span class="badge" :class="typeBadge(sig.type)">
                {{ sig.type }}
              </span>
            </td>

            <td>
              {{ sig.is_default ? "Oui" : "-" }}
            </td>

            <td>
              <span 
                class="badge" 
                :class="sig.is_active ? \'badge-green\' : \'badge-gray\'">
                {{ sig.is_active ? "Actif" : "Inactif" }}
              </span>
            </td>

            <td class="text-right">
              <button 
                @click="deleteSignature(sig)" 
                class="link-action text-red-500">
                Supprimer
              </button>
            </td>

          </tr>


          <tr v-if="!signatures.length">
            <td colspan="5" class="empty-state">
              Aucune signature
            </td>
          </tr>


        </tbody>
      </table>

    </div>


    <!-- MODAL FORMULAIRE -->

    <div 
      v-if="showForm" 
      class="modal-overlay" 
      @click.self="closeForm">

      <div class="modal-content">

        <h3 class="card-title mb-4">
          Nouvelle signature
        </h3>


        <form @submit.prevent="saveSignature">


          <div class="form-group">
            <label>Libelle *</label>

            <input 
              v-model="form.label"
              required
              class="form-input"
              placeholder="Signature direction" />
          </div>



          <div class="form-group">

            <label>Type</label>

            <select 
              v-model="form.type"
              class="form-select">

              <option value="text">
                Texte
              </option>

              <option value="image">
                Image
              </option>

            </select>

          </div>



          <div 
            v-if="form.type === \'text\'" 
            class="form-group">

            <label>
              Signature texte
            </label>

            <textarea
              v-model="form.content"
              rows="4"
              class="form-textarea"
              placeholder="Nom, fonction...">
            </textarea>

          </div>
                    <div 
            v-if="form.type === \'image\'" 
            class="form-group">

            <label>
              URL image
            </label>

            <input 
              v-model="form.content"
              class="form-input"
              placeholder="/storage/signatures/signature.png" />

          </div>



          <div class="form-group">

            <label>

              <input 
                type="checkbox"
                v-model="form.is_default" />

              Signature par defaut

            </label>

          </div>



          <div class="form-actions">

            <button 
              type="submit"
              class="btn-primary">

              Enregistrer

            </button>


            <button 
              type="button"
              @click="closeForm"
              class="btn-secondary">

              Annuler

            </button>

          </div>


        </form>


      </div>

    </div>


  </div>

</template>


<script setup lang="ts">

import { ref, onMounted } from "vue";
import apiClient from "../../utils/axios";


interface Signature {

  id: string;
  label: string;
  type: string;
  content: string;
  is_default: boolean;
  is_active: boolean;

}



const signatures = ref<Signature[]>([]);

const showForm = ref(false);

const editId = ref<string | null>(null);



const form = ref({

  label: "",
  type: "text",
  content: "",
  is_default: false

});



function openForm(){

  showForm.value = true;
  editId.value = null;

}



function closeForm(){

  showForm.value = false;
  editId.value = null;

  form.value = {

    label: "",
    type: "text",
    content: "",
    is_default: false

  };

}



function typeBadge(type:string):string{

  return ({

    text: "badge-blue",
    image: "badge-purple"

  } as Record<string,string>)[type] || "badge-gray";

}



async function loadSignatures(){

  try{

    const { data } = await apiClient.get("/signatures");

    signatures.value = data.data.data || [];

  }
  catch(e){

    console.error(e);

  }

}



async function deleteSignature(sig:Signature){

  if(!confirm("Supprimer cette signature ?")) return;


  try{

    await apiClient.delete("/signatures/" + sig.id);

    await loadSignatures();

  }
  catch(e){

    console.error(e);

  }

}



async function saveSignature(){

  try{


    if(editId.value){

      await apiClient.put(
        "/signatures/" + editId.value,
        form.value
      );

    }
    else{

      await apiClient.post(
        "/signatures",
        form.value
      );

    }



    closeForm();

    await loadSignatures();


  }
  catch(e:any){

    alert(
      e.response?.data?.message || "Erreur"
    );

  }

}



onMounted(loadSignatures);


</script>');
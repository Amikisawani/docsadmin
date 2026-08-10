<template>
  <div class="page-container">
    <div class="page-header">
      <div>
        <h1 class="page-title">Organigramme</h1>
        <p class="page-subtitle">Structure hierarchique de l organisation</p>
      </div>
      <button class="btn-primary" @click="openForm(null)">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
        Nouveau departement
      </button>
    </div>
    <div class="table-card">
      <div v-if="loading" class="loading-state">Chargement...</div>
      <div v-else-if="!departments.length" class="empty-state">Aucun departement</div>
      <div v-else>
        <div v-for="dept in rootDepartments" :key="dept.id" class="tree-node">
          <div class="tree-node-header" @click="toggleExpand(dept.id)">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21"/></svg>
            <span class="font-medium">{{ dept.name }}</span>
            <span class="text-muted text-xs ml-2">{{ dept.code }}</span>
            <span class="ml-auto flex gap-2">
              <button @click.stop="openForm(dept)" class="link-action">Modifier</button>
              <button @click.stop="deleteDepartment(dept)" class="link-action text-red-500">Supprimer</button>
            </span>
          </div>
          <div v-if="expanded[dept.id]" class="tree-children">
            <div v-for="child in getChildren(dept.id)" :key="child.id" class="tree-node">
              <div class="tree-node-header pl-8">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614fM16.5 9.75V3.75m0 0h3.75m-3.75 0H9.75m0 0H5.25m0 0V3.75"/></svg>
                <span>{{ child.name }}</span>
                <span class="text-muted text-xs ml-2">{{ child.code }}</span>
                <span class="ml-auto flex gap-2">
                  <button @click.stop="openForm(child)" class="link-action">Modifier</button>
                  <button @click.stop="deleteDepartment(child)" class="link-action text-red-500">Supprimer</button>
                </span>
              </div>
          </div>
      </div>
      </div>
    </div>
    <div v-if="showForm" class="modal-overlay" @click.self="showForm = false">
      <div class="modal-content">
        <h3 class="card-title mb-4">{{ editId ? "Modifier" : "Nouveau departement" }}</h3>
        <form @submit.prevent="saveDepartment">
          <div class="form-group"><label>Nom *</label><input v-model="deptForm.name" required class="form-input" /></div>
          <div class="form-group"><label>Code</label><input v-model="deptForm.code" class="form-input" /></div>
          <div class="form-group"><label>Parent</label>
            <select v-model="deptForm.parent_id" class="form-select">
              <option :value="null">Aucun (racine)</option>
              <option v-for="d in departments" :key="d.id" :value="d.id" :disabled="d.id === editId">{{ d.name }}</option>
            </select>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn-primary">Enregistrer</button>
            <button type="button" class="btn-secondary" @click="showForm = false">Annuler</button>
          </div>
        </form>
      </div>
  </div>
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
function openForm(d: Department | null) {
  editId.value = d ? d.id : null;
  deptForm.value = { name: d?.name || "", code: d?.code || "", parent_id: d?.parent_id || null };
  showForm.value = true;
}
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
</script>

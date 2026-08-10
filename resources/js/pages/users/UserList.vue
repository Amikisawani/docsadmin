<template>
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
    <div class="filter-card">
      <div class="filter-grid">
        <input v-model="filters.search" placeholder="Rechercher un utilisateur..." class="form-input" @keyup.enter="loadUsers" />
        <select v-model="filters.role" class="form-select">
          <option value="">Tous les roles</option>
          <option value="admin">Administrateur</option>
          <option value="secretaire">Secretaire</option>
          <option value="directeur">Directeur</option>
          <option value="chef">Chef de service</option>
          <option value="agent">Agent</option>
          <option value="auditeur">Auditeur</option>
          <option value="archiviste">Archiviste</option>
        </select>
        <select v-model="filters.status" class="form-select">
          <option value="">Tous les statuts</option>
          <option value="active">Actif</option>
          <option value="inactive">Inactif</option>
        </select>
        <button class="btn-filter" @click="loadUsers">Filtrer</button>
      </div>
    </div>
    <div class="table-card">
      <div class="table-wrapper">
        <table class="data-table">
          <thead>
            <tr>
              <th>Nom</th>
              <th>Email</th>
              <th>Fonction</th>
              <th>Service</th>
              <th>Roles</th>
              <th>Statut</th>
              <th class="text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in users" :key="user.id">
              <td class="font-medium">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary text-xs font-bold">{{ user.name?.charAt(0).toUpperCase() }}</div>
                  {{ user.name }}
                </div>
              </td>
              <td class="text-muted">{{ user.email }}</td>
              <td>{{ user.fonction || "-" }}</td>
              <td>{{ user.service || "-" }}</td>
              <td>
                <span v-for="role in (user.roles || [])" :key="role" class="badge badge-blue mr-1">{{ role.name || role }}</span>
                <span v-if="!user.roles?.length" class="text-muted text-xs">-</span>
              </td>
              <td><span class="badge" :class="user.is_active ? 'badge-green' : 'badge-gray'">{{ user.is_active ? "Actif" : "Inactif" }}</span></td>
              <td class="text-right">
                <div class="action-btns">
                  <router-link :to="`/users/${user.id}/edit`" class="action-btn" title="Modifier l'utilisateur">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125"/></svg>
                  </router-link>
                </div>
              </td>
            </tr>
            <tr v-if="!users.length && !loading">
              <td colspan="7" class="empty-state">Aucun utilisateur trouve</td>
            </tr>
            <tr v-if="loading">
              <td colspan="7" class="loading-state">Chargement...</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="pagination.last_page > 1" class="pagination">
        <p class="text-muted">Page {{ pagination.current_page }} sur {{ pagination.last_page }}</p>
        <div class="pagination-btns">
          <button class="btn-page" :disabled="pagination.current_page <= 1" @click="changePage(pagination.current_page - 1)">Precedent</button>
          <button class="btn-page" :disabled="pagination.current_page >= pagination.last_page" @click="changePage(pagination.current_page + 1)">Suivant</button>
        </div>
    </div>
    </div>
    </div>
</template>
<script setup lang="ts">
import { ref, reactive, onMounted } from "vue";
import apiClient from "../../utils/axios";
interface User { id: string; name: string; email: string; fonction: string; service: string; is_active: boolean; roles: { name: string }[]; }
const users = ref<User[]>([]);
const loading = ref(false);
const pagination = reactive({ current_page: 1, last_page: 1, total: 0 });
const filters = reactive({ search: "", role: "", status: "" });
function changePage(p: number) { pagination.current_page = p; loadUsers(); }
async function loadUsers() {
  loading.value = true;
  try {
    const params: any = { page: pagination.current_page, per_page: 15 };
    if (filters.search) params.search = filters.search;
    if (filters.role) params.role = filters.role;
    const { data } = await apiClient.get("/users", { params });
    users.value = data.data.data || [];
    pagination.current_page = data.data.current_page;
    pagination.last_page = data.data.last_page;
    pagination.total = data.data.total;
  } catch (e) { console.error(e); }
  finally { loading.value = false; }
}
onMounted(loadUsers);
</script>

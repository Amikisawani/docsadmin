<template>
  <div class="page-container">
    <div class="page-header">
      <div>
        <h1 class="page-title">{{ isEdit ? "Modifier l utilisateur" : "Nouvel utilisateur" }}</h1>
        <p class="page-subtitle">{{ isEdit ? "Modifier les informations de l utilisateur" : "Creer un nouveau compte utilisateur" }}</p>
      </div>
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
        </div>
        <div class="form-grid">
          <div class="form-group">
            <label>Service</label>
            <input v-model="form.service" class="form-input" />
          </div>
          <div class="form-group">
            <label>Roles</label>
            <select v-model="form.role" class="form-select">
              <option value="">Selectionner un role</option>
              <option value="admin">Administrateur</option>
              <option value="secretaire">Secretaire</option>
              <option value="directeur">Directeur</option>
              <option value="chef">Chef de service</option>
              <option value="agent">Agent</option>
              <option value="auditeur">Auditeur</option>
              <option value="archiviste">Archiviste</option>
            </select>
          </div>
        </div>
        <div v-if="error" class="form-error">{{ error }}</div>
        <div class="form-actions">
          <button type="submit" :disabled="loading" class="btn-primary">{{ loading ? "Enregistrement..." : (isEdit ? "Mettre a jour" : "Creer l utilisateur") }}</button>
          <router-link to="/users" class="btn-secondary">Annuler</router-link>
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
const form = ref({ name: "", email: "", password: "", fonction: "", service: "", role: "" });
async function saveUser() {
  error.value = ""; loading.value = true;
  try {
    const payload: any = { name: form.value.name, email: form.value.email, fonction: form.value.fonction, service: form.value.service };
    if (form.value.password) payload.password = form.value.password;
    if (form.value.role) payload.role = form.value.role;
    if (isEdit.value) await apiClient.put("/users/" + route.params.id, payload);
    else await apiClient.post("/users", payload);
    router.push("/users");
  } catch (e: any) { error.value = e.response?.data?.message || "Erreur lors de l enregistrement"; }
  finally { loading.value = false; }
}
onMounted(async () => {
  if (isEdit.value) {
    try {
      const { data } = await apiClient.get("/users/" + route.params.id);
      const u = data.data;
      form.value = { name: u.name, email: u.email, password: "", fonction: u.fonction || "", service: u.service || "", role: u.roles?.[0]?.name || "" };
    } catch { router.push("/users"); }
  }
});
</script>

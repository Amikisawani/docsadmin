<?php
// LoginPage.vue
file_put_contents('c:/Users/Dell/Documents/gestion_doc/resources/js/pages/auth/LoginPage.vue', '<template>
  <div class="min-h-screen flex">
    <div class="w-1/2 bg-gradient-to-br from-slate-900 via-blue-800 to-blue-600 p-12 flex items-center justify-center">
      <div class="text-center max-w-md">
        <h1 class="text-4xl font-bold text-white mb-2">AdminFlow</h1>
        <p class="text-blue-200 mb-8">Plateforme de gestion electronique des documents</p>
      </div>
    <div class="w-1/2 bg-slate-50 p-8 flex items-center justify-center">
      <div class="w-full max-w-sm">
        <h2 class="text-2xl font-bold mb-1">Connexion</h2>
        <p class="text-sm text-slate-500 mb-6">Accedez a votre espace de travail</p>
        <form @submit.prevent="handleLogin">
          <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Email</label>
            <input v-model="email" type="email" required class="w-full p-2.5 border rounded-lg text-sm">
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Mot de passe</label>
            <input v-model="password" type="password" required class="w-full p-2.5 border rounded-lg text-sm">
          </div>
          <div v-if="error" class="mb-4 p-3 bg-red-50 text-red-700 rounded-lg text-sm border border-red-200">{{ error }}</div>
          <button type="submit" class="w-full p-2.5 bg-blue-700 text-white rounded-lg text-sm font-medium">Se connecter</button>
        </form>
      </div>
  </div>
</template>
<script setup lang="ts">
import { ref } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "../../stores/auth";
const router = useRouter();
const authStore = useAuthStore();
const email = ref("");
const password = ref("");
const loading = ref(false);
const error = ref("");
async function handleLogin() {
  error.value = "";
  loading.value = true;
  try {
    await authStore.login({ email: email.value, password: password.value });
    router.push("/");
  } catch (ex: any) {
    error.value = ex?.response?.data?.message || "Erreur de connexion";
  } finally {
    loading.value = false;
  }
}
</script>');

// AdminLayout.vue
file_put_contents('c:/Users/Dell/Documents/gestion_doc/resources/js/layouts/AdminLayout.vue', '<template>
  <div class="flex" style="min-height: 100vh;">
    <aside style="width:260px; background:#0f172a; position:fixed; left:0; top:0; z-index:50; display:flex; flex-direction:column; min-height:100vh;">
      <div style="padding:1.25rem 1rem; border-bottom:1px solid rgba(255,255,255,0.08);">
        <div style="font-size:1.1rem; font-weight:700; color:#fff;">AdminFlow</div>
        <div style="font-size:0.65rem; color:#94a3b8;">Gestion documentaire</div>
      <nav style="flex:1; padding:0.75rem 0; overflow-y:auto;">
        <router-link to="/" class="nav-item" style="display:block; padding:0.5rem 1rem; color:#94a3b8; text-decoration:none; font-size:0.85rem;">Tableau de bord</router-link>
        <div style="padding:0.75rem 1rem 0.25rem; font-size:0.65rem; color:#64748b; text-transform:uppercase;">Gestion</div>
        <router-link to="/documents" class="nav-item" style="display:block; padding:0.5rem 1rem; color:#94a3b8; text-decoration:none; font-size:0.85rem;">Documents</router-link>
        <router-link to="/templates" class="nav-item" style="display:block; padding:0.5rem 1rem; color:#94a3b8; text-decoration:none; font-size:0.85rem;">Modeles</router-link>
        <router-link to="/workflows" class="nav-item" style="display:block; padding:0.5rem 1rem; color:#94a3b8; text-decoration:none; font-size:0.85rem;">Workflows</router-link>
        <router-link to="/signatures" class="nav-item" style="display:block; padding:0.5rem 1rem; color:#94a3b8; text-decoration:none; font-size:0.85rem;">Signatures</router-link>
        <router-link to="/archives" class="nav-item" style="display:block; padding:0.5rem 1rem; color:#94a3b8; text-decoration:none; font-size:0.85rem;">Archives</router-link>
        <div style="padding:0.75rem 1rem 0.25rem; font-size:0.65rem; color:#64748b; text-transform:uppercase;">Organisation</div>
        <router-link to="/users" class="nav-item" style="display:block; padding:0.5rem 1rem; color:#94a3b8; text-decoration:none; font-size:0.85rem;">Utilisateurs</router-link>
        <router-link to="/departments" class="nav-item" style="display:block; padding:0.5rem 1rem; color:#94a3b8; text-decoration:none; font-size:0.85rem;">Organigramme</router-link>
        <div style="padding:0.75rem 1rem 0.25rem; font-size:0.65rem; color:#64748b; text-transform:uppercase;">Suivi</div>
        <router-link to="/audit" class="nav-item" style="display:block; padding:0.5rem 1rem; color:#94a3b8; text-decoration:none; font-size:0.85rem;">Audit</router-link>
      </nav>
      <div style="padding:0.75rem; border-top:1px solid rgba(255,255,255,0.08);">
        <div @click="handleLogout" style="padding:0.5rem 1rem; color:#94a3b8; font-size:0.85rem; cursor:pointer;">Deconnexion</div>
    </aside>
    <div style="margin-left:260px; width:100%;">
      <header style="height:60px; background:#fff; border-bottom:1px solid #e2e8f0; display:flex; align-items:center; justify-content:space-between; padding:0 1.5rem; position:sticky; top:0; z-index:40;">
        <h1 style="font-size:1.1rem; font-weight:600; color:#0f172a;">{{ pageTitle }}</h1>
        <div style="display:flex; align-items:center; gap:0.5rem;">
          <span style="font-size:0.85rem; color:#475569;">{{ userName }}</span>
        </div>
      </header>
      <main style="padding:1.5rem;">
        <router-view></router-view>
      </main>
    </div>
</template>
<script setup lang="ts">
import { computed } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useAuthStore } from "../stores/auth";
const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const pageTitle = computed(() => (route.meta?.title as string) || "AdminFlow");
const userName = computed(() => authStore.user?.name || "Utilisateur");
function handleLogout() { authStore.logout(); router.push("/login"); }
</script>
<style scoped>
.nav-item:hover { background:#1e293b; color:#e2e8f0; }
.nav-item.router-link-exact-active { background:#1e40af; color:#fff; }
</style>');

// DashboardPage.vue
file_put_contents('c:/Users/Dell/Documents/gestion_doc/resources/js/pages/dashboard/DashboardPage.vue', '<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold">Tableau de bord</h1>
        <p class="text-sm text-slate-500 mt-1">Vue d\'ensemble de la plateforme documentaire</p>
      </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
      <div class="bg-white rounded-xl border p-5">
        <div class="text-2xl font-bold">{{ stats.total_documents || 0 }}</div>
        <div class="text-sm text-slate-400 mt-1">Documents</div>
      <div class="bg-white rounded-xl border p-5">
        <div class="text-2xl font-bold">{{ stats.pending_documents || 0 }}</div>
        <div class="text-sm text-slate-400 mt-1">En attente</div>
      <div class="bg-white rounded-xl border p-5">
        <div class="text-2xl font-bold">{{ stats.signed_documents || 0 }}</div>
        <div class="text-sm text-slate-400 mt-1">Signes</div>
      <div class="bg-white rounded-xl border p-5">
        <div class="text-2xl font-bold">{{ stats.archived_documents || 0 }}</div>
        <div class="text-sm text-slate-400 mt-1">Archives</div>
    </div>
    <h2 class="text-lg font-semibold mb-4">Acces rapide</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <router-link to="/documents" class="bg-white rounded-xl border p-5 block no-underline">
        <h3 class="font-semibold">Documents</h3>
        <p class="text-sm text-slate-500 mt-1">Gerer les documents administratifs</p>
      </router-link>
      <router-link to="/workflows" class="bg-white rounded-xl border p-5 block no-underline">
        <h3 class="font-semibold">Workflows</h3>
        <p class="text-sm text-slate-500 mt-1">Circuit de validation</p>
      </router-link>
      <router-link to="/users" class="bg-white rounded-xl border p-5 block no-underline">
        <h3 class="font-semibold">Utilisateurs</h3>
        <p class="text-sm text-slate-500 mt-1">Gerer les utilisateurs</p>
      </router-link>
      <router-link to="/templates" class="bg-white rounded-xl border p-5 block no-underline">
        <h3 class="font-semibold">Modeles</h3>
        <p class="text-sm text-slate-500 mt-1">Modeles de documents</p>
      </router-link>
      <router-link to="/signatures" class="bg-white rounded-xl border p-5 block no-underline">
        <h3 class="font-semibold">Signatures</h3>
        <p class="text-sm text-slate-500 mt-1">Signatures electroniques</p>
      </router-link>
      <router-link to="/archives" class="bg-white rounded-xl border p-5 block no-underline">
        <h3 class="font-semibold">Archives</h3>
        <p class="text-sm text-slate-500 mt-1">Documents archives</p>
      </router-link>
    </div>
</template>
<script setup lang="ts">
import { ref, onMounted } from "vue";
import apiClient from "../../utils/axios";
const stats = ref<any>({});
onMounted(async () => {
  try {
    const { data } = await apiClient.get("/dashboard/stats");
    stats.value = data.data || {};
  } catch (e) { console.error(e); }
});
</script>');

echo "All 3 Vue files created successfully!\n";

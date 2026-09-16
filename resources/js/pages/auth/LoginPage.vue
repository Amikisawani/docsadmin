<template>
  <div style="min-height:100vh;display:flex;background:#f1f5f9;font-family:system-ui,sans-serif;">
    
    <div style="flex:1;display:none;position:relative;overflow:hidden;align-items:center;justify-content:center;padding:2rem;" class="brand-panel">
      
      <div style="position:absolute;inset:0;background:linear-gradient(135deg,#0b1d3a,#1e3a5f,#1e40af);">
        <div style="position:absolute;top:-40%;right:-20%;width:700px;height:700px;border-radius:50%;background:radial-gradient(circle,rgba(59,130,246,0.12),transparent);"></div>
        <div style="position:absolute;bottom:-30%;left:-15%;width:500px;height:500px;border-radius:50%;background:radial-gradient(circle,rgba(99,102,241,0.08),transparent);"></div>
      </div>

      <div style="position:relative;z-index:10;text-align:center;">
        
        <div style="width:72px;height:72px;margin:0 auto 1.25rem;background:linear-gradient(135deg,#3b82f6,#6366f1);border-radius:18px;display:flex;align-items:center;justify-content:center;box-shadow:0 20px 60px rgba(59,130,246,0.3);color:#fff;">
          <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
          </svg>
        </div>

        <h1 style="font-size:2.2rem;font-weight:800;color:#fff;letter-spacing:-0.02em;margin:0 0 0.5rem;">
          DocsAdmin
        </h1>

        <p style="font-size:0.95rem;color:rgba(255,255,255,0.65);max-width:320px;margin:0 auto 2.5rem;line-height:1.6;">
          Plateforme intelligente de gestion électronique des documents
        </p>

        <div style="display:inline-flex;gap:1.5rem;background:rgba(255,255,255,0.06);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,0.08);border-radius:16px;padding:1rem 1.5rem;">
          
          <div style="text-align:center;">
            <div style="font-size:1.25rem;font-weight:700;color:#60a5fa;">10K+</div>
            <div style="font-size:0.65rem;color:rgba(255,255,255,0.45);text-transform:uppercase;letter-spacing:0.05em;">
              Documents
            </div>
          </div>

          <div style="width:1px;background:rgba(255,255,255,0.1);"></div>

          <div style="text-align:center;">
            <div style="font-size:1.25rem;font-weight:700;color:#a78bfa;">500+</div>
            <div style="font-size:0.65rem;color:rgba(255,255,255,0.45);text-transform:uppercase;letter-spacing:0.05em;">
              Utilisateurs
            </div>
          </div>

          <div style="width:1px;background:rgba(255,255,255,0.1);"></div>

          <div style="text-align:center;">
            <div style="font-size:1.25rem;font-weight:700;color:#34d399;">99%</div>
            <div style="font-size:0.65rem;color:rgba(255,255,255,0.45);text-transform:uppercase;letter-spacing:0.05em;">
              Disponibilité
            </div>
          </div>

        </div>

      </div>

    </div>
    <div style="flex:1;display:flex;align-items:center;justify-content:center;padding:2rem;">
      <div style="width:100%;max-width:400px;">
        <div style="text-align:center;margin-bottom:1.5rem;" class="mobile-logo">
          <div style="width:48px;height:48px;margin:0 auto 0.5rem;background:linear-gradient(135deg,#3b82f6,#6366f1);border-radius:12px;display:flex;align-items:center;justify-content:center;color:#fff;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
            </svg>
          </div>
          <h1 style="font-size:1.3rem;font-weight:700;color:#0f172a;margin:0;">AdminFlow</h1>
        </div>
        <!-- Panneau urgent : affiché uniquement si le dernier utilisateur était le Directeur de Cabinet -->
        <div
          v-if="directorPanelVisible"
          style="margin-bottom:1rem;padding:0.9rem 1rem;background:linear-gradient(135deg,#eff6ff,#dbeafe);border:1px solid #bfdbfe;border-radius:14px;display:flex;align-items:center;gap:0.75rem;"
        >
          <div style="width:38px;height:38px;flex-shrink:0;background:#2563eb;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
          </div>
          <div>
            <div style="font-size:0.85rem;font-weight:700;color:#1e3a8a;margin:0 0 0.1rem;">
              Directeur de Cabinet
            </div>
            <div style="font-size:0.8rem;color:#1d4ed8;margin:0;">
              <template v-if="directorNewToday > 0">
                <strong>{{ directorNewToday }}</strong> nouveau{{ directorNewToday > 1 ? 'x' : '' }} document{{ directorNewToday > 1 ? 's' : '' }} nécessitent votre validation.
              </template>
              <template v-else-if="directorPendingCount > 0">
                Vous avez <strong>{{ directorPendingCount }}</strong> document{{ directorPendingCount > 1 ? 's' : '' }} en attente de signature.
              </template>
              <template v-else>
                Aucun document en attente de validation.
              </template>
            </div>
          </div>
        </div>

        <div style="background:#fff;border-radius:20px;padding:2rem;box-shadow:0 1px 3px rgba(0,0,0,0.04);border:1px solid #e2e8f0;">
          <div style="margin-bottom:1.5rem;">
            <h2 style="font-size:1.35rem;font-weight:700;color:#0f172a;margin:0 0 0.25rem;">Connexion</h2>
            <p style="font-size:0.85rem;color:#64748b;margin:0;">Accedez a votre espace documentaire</p>
          </div>
          <form @submit.prevent="handleLogin">
            <div style="margin-bottom:1rem;">
              <label style="display:block;font-size:0.8rem;font-weight:600;color:#1e293b;margin-bottom:0.375rem;">Adresse email</label>
              <div style="position:relative;">
                <svg style="position:absolute;left:12px;top:50%;transform:translateY(-50%);width:18px;height:18px;color:#94a3b8;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                <input v-model="email" type="email" required placeholder="admin@adminflow.ci" style="width:100%;padding:0.7rem 0.75rem 0.7rem 2.5rem;border:1px solid #cbd5e1;border-radius:10px;font-size:0.875rem;outline:none;background:#f8fafc;color:#0f172a;box-sizing:border-box;transition:all 0.15s;" />
              </div>
            </div>
            <div style="margin-bottom:1rem;">
              <label style="display:block;font-size:0.8rem;font-weight:600;color:#1e293b;margin-bottom:0.375rem;">Mot de passe</label>
              <div style="position:relative;">
                <svg style="position:absolute;left:12px;top:50%;transform:translateY(-50%);width:18px;height:18px;color:#94a3b8;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                <input v-model="password" type="password" required placeholder="********" style="width:100%;padding:0.7rem 0.75rem 0.7rem 2.5rem;border:1px solid #cbd5e1;border-radius:10px;font-size:0.875rem;outline:none;background:#f8fafc;color:#0f172a;box-sizing:border-box;transition:all 0.15s;" />
              </div>
            </div>
            <div style="text-align:right;margin-bottom:1rem;">
              <button type="button" style="font-size:0.8rem;color:#3b82f6;background:none;border:none;cursor:pointer;font-weight:500;padding:0;">Mot de passe oublie ?</button>
            </div>
            <div v-if="error" style="margin-bottom:1rem;padding:0.75rem 1rem;background:#fef2f2;border:1px solid #fecaca;border-radius:10px;font-size:0.8rem;color:#b91c1c;display:flex;align-items:center;gap:0.5rem;">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
              <span>{{ error }}</span>
            </div>
            <button type="submit" :disabled="loading" style="width:100%;padding:0.7rem;background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;border:none;border-radius:10px;font-size:0.9rem;font-weight:600;cursor:pointer;transition:all 0.15s;font-family:inherit;">{{ loading ? "Connexion..." : "Se connecter" }}</button>
          </form>
        </div>
    </div>
    </div>
  </div>
</template>
<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "../../stores/auth";
import apiClient from "../../utils/axios";

const router = useRouter();
const authStore = useAuthStore();
const email = ref("");
const password = ref("");
const loading = ref(false);
const error = ref("");

// Panneau « urgences » du Directeur de Cabinet (affiché uniquement si le
// dernier utilisateur déconnecté était le Directeur de Cabinet).
const directorPendingCount = ref(0);
const directorNewToday = ref(0);
const directorPanelVisible = computed(
  () => authStore.lastRole === 'directeur_cabinet'
);

async function loadDirectorPendingCount() {
  try {
    const { data } = await apiClient.get('/auth/director-pending-count');
    directorPendingCount.value = data?.data?.pending_count ?? 0;
    directorNewToday.value = data?.data?.new_today_count ?? 0;
  } catch (e) {
    directorPendingCount.value = 0;
    directorNewToday.value = 0;
  }
}

onMounted(() => {
  // L'endpoint est protégé par Sanctum : sans token, l'appel 401 relançait
  // un rechargement de /login. On n'interroge que si une session API existe.
  if (directorPanelVisible.value && localStorage.getItem('token')) {
    loadDirectorPendingCount();
  }
});

async function handleLogin() {
  error.value = "";
  loading.value = true;
try {
    await authStore.login({ email: email.value, password: password.value });
    // Le Directeur de Cabinet est redirigé vers sa boîte de validation.
    if (authStore.hasRole('directeur_cabinet')) {
      router.push("/director");
    } else {
      router.push("/");
    }
  } catch (ex: any) {
    error.value = ex?.response?.data?.message || "Erreur de connexion";
  } finally {
    loading.value = false;
  }
}
</script>
<style scoped>
@media (min-width: 1024px) {
  .brand-panel { display: flex !important; }
  .mobile-logo { display: none; }
}
</style>

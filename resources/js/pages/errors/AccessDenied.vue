<template>
  <div class="access-denied-page">
    <div class="ad-card">
      <div class="ad-icon">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
        </svg>
      </div>
      <h1 class="ad-title">Action reservée</h1>
      <p class="ad-desc">
        La signature de documents est reservée aux rôles suivants :
      </p>
      <div class="ad-roles-list">
        <span v-for="role in requiredRoles" :key="role" class="ad-role-badge">{{ roleLabel(role) }}</span>
      </div>
      <p class="ad-desc">
        Vous etes connecté en tant que <strong>{{ currentRole }}</strong>. Veuillez vous connecter avec un compte disposant d'un rôle autorisé.
      </p>
      <div class="ad-actions">
        <router-link to="/login" class="btn-primary">Se connecter</router-link>
        <router-link to="/" class="btn-secondary">Retour à l'accueil</router-link>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useAuthStore } from "../../stores/auth";

const authStore = useAuthStore();

const requiredRoles = ['admin', 'secretaire_general', 'directeur', 'chef_division', 'chef_bureau'];

const currentRole = computed(() => {
  const roles = authStore.user?.roles?.map(r => r.name) || [];
  return roles.length > 0 ? roles.map(r => roleLabel(r)).join(", ") : "Aucun rôle";
});

function roleLabel(role: string): string {
  const labels: Record<string, string> = {
    admin: "Administrateur",
    secretaire_general: "Secrétaire Général",
    secretaire_general_adjoint: "Secrétaire Général Adjoint",
    directeur: "Directeur",
    directeur_chef_service: "Directeur Chef de Service",
    chef_division: "Chef de Division",
    chef_bureau: "Chef de Bureau",
    agent_administration: "Agent d'Administration",
    huissier: "Huissier",
  };
  return labels[role] || role;
}
</script>

<style scoped>
.access-denied-page {
  min-height: 70vh;
  display: flex;
  align-items: center;
  justify-content: center;
}
.ad-card {
  max-width: 480px;
  width: 100%;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 2.5rem 2rem;
  text-align: center;
  box-shadow: 0 4px 24px rgba(0,0,0,0.06);
}
.ad-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: #fef2f2;
  color: #dc2626;
  margin-bottom: 1rem;
}
.ad-title {
  font-size: 1.35rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 0.5rem;
}
.ad-desc {
  font-size: 0.88rem;
  color: #64748b;
  margin: 0.5rem 0;
  line-height: 1.5;
}
.ad-roles-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
  justify-content: center;
  margin: 0.75rem 0;
}
.ad-role-badge {
  display: inline-block;
  padding: 0.25rem 0.6rem;
  font-size: 0.75rem;
  font-weight: 600;
  background: #dbeafe;
  color: #1e40af;
  border-radius: 6px;
}
.ad-actions {
  display: flex;
  gap: 0.75rem;
  justify-content: center;
  margin-top: 1.5rem;
}
</style>

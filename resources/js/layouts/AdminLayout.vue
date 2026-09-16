<template>
  <div class="app-layout">
        <aside class="sidebar" :class="{ 'sidebar-collapsed': isCollapsed }">
<div class="sidebar-header" @click="isCollapsed && toggleSidebar()">
<div class="sidebar-logo">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
        </div>
        <div class="sidebar-brand" v-if="!isCollapsed">
          <span class="sidebar-title">DocsAdmin</span>
          <span class="sidebar-subtitle">GED Administrative</span>
        </div>
        <button class="sidebar-toggle" @click.stop="toggleSidebar" :title="isCollapsed ? 'Développer' : 'Réduire'">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
        </button>
      </div>
<nav class="sidebar-nav">
        <!-- ==== ESPACE DIRECTEUR DE CABINET (interface épurée) ==== -->
        <template v-if="isDirector">
          <div class="nav-section-label">Validation</div>
          <router-link to="/director" class="nav-item" exact-active-class="nav-active" data-tooltip="Documents à signer">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 012.012 1.244l.256.512a2.25 2.25 0 002.013 1.244h3.218a2.25 2.25 0 002.013-1.244l.256-.512a2.25 2.25 0 012.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 00-2.15-1.588H6.911a2.25 2.25 0 00-2.15 1.588L2.35 13.177a2.25 2.25 0 00-.1.661z"/></svg>
            <span>Documents à signer</span>
          </router-link>
          <div class="nav-section-label">Gestion</div>
          <router-link to="/signatures" class="nav-item" active-class="nav-active" data-tooltip="Signatures">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
            <span>Signatures</span>
          </router-link>
          <router-link to="/archives" class="nav-item" active-class="nav-active" data-tooltip="Archives">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5a1.125 1.125 0 00-1.125-1.125H3.375a1.125 1.125 0 00-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
            <span>Archives</span>
          </router-link>
          <router-link to="/tasks" class="nav-item" active-class="nav-active" data-tooltip="Mes tâches">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/></svg>
            <span>Mes tâches</span>
          </router-link>
        </template>

        <!-- ==== MENU STANDARD (utilisateurs) ==== -->
        <template v-if="!isDirector">
          <div class="nav-section-label">Navigation</div>
          <router-link to="/" class="nav-item" exact-active-class="nav-active" data-tooltip="Tableau de bord">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/></svg>
            <span>Tableau de bord</span>
          </router-link>
          <div class="nav-section-label">Gestion</div>
          <router-link to="/documents" class="nav-item" active-class="nav-active" data-tooltip="Documents">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
            <span>Documents</span>
          </router-link>
          <router-link to="/mail-merge" class="nav-item" active-class="nav-active" data-tooltip="Publipostage">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
            <span>Publipostage</span>
          </router-link>

          <!-- Modules réservés aux rôles hiérarchiques (approbations workflow) -->
          <template v-if="isHierarchical && !isAdmin">
            <router-link to="/tasks" class="nav-item" active-class="nav-active" data-tooltip="Mes tâches">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/></svg>
              <span>Mes tâches</span>
            </router-link>
            <router-link to="/workflows" class="nav-item" active-class="nav-active" data-tooltip="Workflows">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
              <span>Workflows</span>
            </router-link>
          </template>

          <!-- Modules administratifs réservés à l'admin et aux rôles hiérarchiques -->
          <template v-if="isAdmin || isHierarchical">
            <div class="nav-section-label">Administration</div>
            <router-link v-if="isAdmin" to="/users" class="nav-item" active-class="nav-active" data-tooltip="Utilisateurs">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
              <span>Utilisateurs</span>
            </router-link>
            <router-link v-if="isAdmin" to="/departments" class="nav-item" active-class="nav-active" data-tooltip="Organigramme">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z"/></svg>
              <span>Organigramme</span>
            </router-link>
            <router-link v-if="isAdmin || isAuditor" to="/audit" class="nav-item" active-class="nav-active" data-tooltip="Audit">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              <span>Audit</span>
            </router-link>
          </template>

          <!-- Admin : accès complet (signatures, workflows, tâches, archives) -->
          <template v-if="isAdmin">
            <div class="nav-section-label">Administration avancée</div>
            <router-link to="/signatures" class="nav-item" active-class="nav-active" data-tooltip="Signatures">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
              <span>Signatures</span>
            </router-link>
            <router-link to="/workflows" class="nav-item" active-class="nav-active" data-tooltip="Workflows">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
              <span>Workflows</span>
            </router-link>
            <router-link to="/tasks" class="nav-item" active-class="nav-active" data-tooltip="Mes tâches">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/></svg>
              <span>Mes tâches</span>
            </router-link>
            <router-link to="/archives" class="nav-item" active-class="nav-active" data-tooltip="Archives">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5a1.125 1.125 0 00-1.125-1.125H3.375a1.125 1.125 0 00-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
              <span>Archives</span>
            </router-link>
          </template>
        </template>
      </nav>
      <div class="sidebar-footer">
        <button @click="handleLogout" class="logout-btn" data-tooltip="Deconnexion">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/></svg>
          <span>Deconnexion</span>
        </button>
      </div>
    </aside>
    <div class="main-area" :class="{ 'main-area-expanded': isCollapsed }">
      <header class="topbar">
        <h1 class="topbar-title">{{ pageTitle }}</h1>
        <div class="topbar-actions">
          <div class="notif-dropdown" ref="notifDropdown">
            <button class="notif-btn" title="Notifications" @click="toggleNotif">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/></svg>
              <span v-if="notifStore.unreadCount > 0" class="notif-badge">{{ notifStore.unreadCount }}</span>
            </button>
            <div v-if="notifOpen" class="notif-menu">
              <div class="notif-menu-header">
                <span class="notif-menu-title">Notifications</span>
                <button v-if="notifStore.unreadCount > 0" class="notif-mark-all" @click="notifStore.markAllAsRead">Tout lire</button>
              </div>
              <div class="notif-list">
                <div v-if="!notifStore.notifications.length" class="notif-empty">
                  Aucune notification
                </div>
                <div
                  v-for="n in notifStore.notifications"
                  :key="n.id"
                  class="notif-item"
                  :class="{ unread: !n.is_read }"
                  @click="notifStore.markAsRead(n.id)"
                >
                  <div class="notif-item-title">{{ n.title }}</div>
                  <div class="notif-item-body">{{ n.body }}</div>
                  <div class="notif-item-time">{{ formatDate(n.created_at) }}</div>
                </div>
              </div>
              <div class="notif-menu-footer">
                <router-link to="/tasks" class="notif-view-all" @click="notifOpen = false">Voir toutes les tâches</router-link>
              </div>
            </div>
          </div>
          <div class="user-profile">
            <div class="user-avatar">{{ userInitials }}</div>
            <span class="user-name">{{ userName }}</span>
          </div>
        </div>
      </header>
      <main class="main-content">
        <router-view />
      </main>
    </div>
  </div>
</template>
<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useAuthStore } from "../stores/auth";
import { useNotificationStore } from "../stores/notification";

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const notifStore = useNotificationStore();

const notifOpen = ref(false);
const notifDropdown = ref<HTMLElement | null>(null);
const isCollapsed = ref(false);

const pageTitle = computed(() => (route.meta?.title as string) || "AdminFlow");
const userName = computed(() => authStore.user?.name || "Utilisateur");

// ---- Rôles (navigation dynamique) ----
const isDirector = computed(() => authStore.hasRole('directeur_cabinet'));
const isAdmin = computed(() => authStore.hasRole('admin'));
// Utilisateurs standards : ni admin, ni directeur de cabinet.
const isStandard = computed(() => !isDirector.value && !isAdmin.value);
const isHierarchical = computed(() =>
  authStore.hasAnyRole(['secretaire_general', 'secretaire_general_adjoint', 'directeur', 'directeur_chef_service', 'chef_division', 'chef_bureau'])
);
const isArchivist = computed(() => authStore.hasRole('archiviste'));
const isAuditor = computed(() => authStore.hasRole('auditeur'));
const userInitials = computed(() => {
  const name = authStore.user?.name || "U";
  const parts = name.split(" ");
  if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase();
  return name.substring(0, 2).toUpperCase();
});

function handleLogout() { authStore.logout(); router.push("/login"); }
function toggleNotif() { notifOpen.value = !notifOpen.value; }
function toggleSidebar() { isCollapsed.value = !isCollapsed.value; }
function formatDate(d: string): string {
  return new Date(d).toLocaleDateString("fr-FR", { day: "numeric", month: "short", year: "numeric" });
}

// Fermer le dropdown en cliquant à l'extérieur
function handleOutsideClick(e: MouseEvent) {
  if (notifDropdown.value && !notifDropdown.value.contains(e.target as Node)) {
    notifOpen.value = false;
  }
}

onMounted(() => {
  document.addEventListener("click", handleOutsideClick);
  // Initialiser le chargement et le polling des notifications
  notifStore.fetchNotifications();
  notifStore.fetchUnreadCount();
  notifStore.startPolling();
});

onUnmounted(() => {
  document.removeEventListener("click", handleOutsideClick);
  notifStore.stopPolling();
});
</script>
<style scoped>
.app-layout { display: flex; min-height: 100vh; font-family: system-ui, sans-serif; }
.sidebar { width: 260px; background: #0f172a; position: fixed; left: 0; top: 0; bottom: 0; z-index: 50; display: flex; flex-direction: column; transition: all 0.2s ease; }
.sidebar.sidebar-collapsed { width: 70px; }
.sidebar-header { position: relative; display: flex; align-items: center; gap: 0.75rem; padding: 1.25rem 1rem; border-bottom: 1px solid rgba(255,255,255,0.06); justify-content: space-between; overflow: visible; }
.sidebar.sidebar-collapsed .sidebar-header { justify-content: center; padding: 1.25rem 0.5rem; }

/* Logo + toggle : le toggle se place à la place du logo en fondu quand la nav est réduite */
.sidebar-logo {
  width: 36px;
  height: 36px;
  background: linear-gradient(135deg, #3b82f6, #6366f1);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  flex-shrink: 0;
  transition: opacity 0.25s ease, transform 0.25s ease;
}
.sidebar-brand { display: flex; flex-direction: column; }
.sidebar-title { font-size: 1rem; font-weight: 700; color: #fff; }
.sidebar-subtitle { font-size: 0.65rem; color: #64748b; }
.sidebar-toggle {
  width: 30px;
  height: 30px;
  border-radius: 8px;
  border: none;
  background: rgba(255,255,255,0.06);
  color: #94a3b8;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
  flex-shrink: 0;
  margin-left: auto;
}
.sidebar-toggle:hover { background: rgba(255,255,255,0.14); color: #fff; }

/* Mode réduit : le logo s'efface et le toggle apparaît en fondu à sa place */
.sidebar.sidebar-collapsed .sidebar-logo {
  opacity: 0;
  transform: scale(0.8);
  pointer-events: none;
}
.sidebar.sidebar-collapsed .sidebar-toggle {
  position: absolute;
  left: 50%;
  top: 50%;
  transform: translate(-50%, -50%);
  width: 30px;
  height: 30px;
  border-radius: 10px;
  background: #2563eb;
  color: #fff;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.4);
  margin-left: 0;
  z-index: 5;
  opacity: 1;
  transition: opacity 0.25s ease, background 0.15s;
}
.sidebar.sidebar-collapsed .sidebar-toggle:hover { background: #1d4ed8; }
.sidebar.sidebar-collapsed .sidebar-toggle svg { width: 16px; height: 16px; }

.sidebar-nav { flex: 1; overflow-y: auto; overflow-x: hidden; padding: 0.75rem 0; }
.nav-section-label { font-size: 0.65rem; font-weight: 600; text-transform: uppercase; color: #475569; padding: 0.9rem 1rem 0.3rem; letter-spacing: 0.05em; white-space: nowrap; }
.sidebar.sidebar-collapsed .nav-section-label { padding: 0.75rem 0 0.3rem; text-align: center; font-size: 0.55rem; }
.nav-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.55rem 1rem; margin: 0.125rem 0.5rem; border-radius: 8px; color: #94a3b8; font-size: 0.85rem; font-weight: 500; text-decoration: none; transition: all 0.12s; white-space: nowrap; }
.nav-item:hover { background: #1e293b; color: #e2e8f0; }
.nav-active { background: linear-gradient(90deg, #1e40af, #2563eb) !important; color: #fff !important; box-shadow: 0 2px 8px rgba(37,99,235,0.35); }
.sidebar.sidebar-collapsed .nav-item { justify-content: center; padding: 0.6rem 0.5rem; position: relative; }
.sidebar.sidebar-collapsed .nav-item span { display: none; }

/* Infobulles sur les liens quand la nav est réduite (icônes seules) */
.sidebar.sidebar-collapsed .nav-item::after,
.sidebar.sidebar-collapsed .logout-btn::after {
  content: attr(data-tooltip);
  position: absolute;
  left: 100%;
  top: 50%;
  transform: translateY(-50%) translateX(-6px);
  margin-left: 12px;
  background: #0f172a;
  color: #e2e8f0;
  padding: 0.4rem 0.7rem;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 500;
  white-space: nowrap;
  z-index: 60;
  box-shadow: 0 4px 12px rgba(0,0,0,0.35);
  border: 1px solid rgba(255,255,255,0.08);
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.18s ease, transform 0.18s ease;
  transition-delay: 0s;
}
.sidebar.sidebar-collapsed .nav-item:hover::after,
.sidebar.sidebar-collapsed .logout-btn:hover::after {
  opacity: 1;
  transform: translateY(-50%) translateX(0);
  transition-delay: 0.25s;
}
.sidebar.sidebar-collapsed .logout-btn { position: relative; }

.sidebar-footer { padding: 0.75rem; border-top: 1px solid rgba(255,255,255,0.06); }
.logout-btn { display: flex; align-items: center; gap: 0.75rem; padding: 0.55rem 0.75rem; width: 100%; border-radius: 8px; color: #94a3b8; font-size: 0.85rem; font-weight: 500; background: none; border: none; cursor: pointer; transition: all 0.12s; white-space: nowrap; }
.logout-btn:hover { background: #1e293b; color: #fca5a5; }
.sidebar.sidebar-collapsed .logout-btn { justify-content: center; padding: 0.55rem 0.5rem; }
.sidebar.sidebar-collapsed .logout-btn span { display: none; }
.main-area { margin-left: 260px; width: 100%; min-height: 100vh; background: #f1f5f9; transition: all 0.2s ease; display: flex; flex-direction: column; min-width: 0; max-width: 100%; overflow-x: hidden; }
.main-area.main-area-expanded { margin-left: 70px; }
.topbar { height: 60px; background: rgba(255,255,255,0.92); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; padding: 0 1.5rem; position: sticky; top: 0; z-index: 40; }
.topbar-title { font-size: 1.1rem; font-weight: 700; color: #0f172a; margin: 0; }
.topbar-actions { display: flex; align-items: center; gap: 0.75rem; }
.notif-btn { width: 38px; height: 38px; border-radius: 10px; border: 1px solid #e2e8f0; background: #fff; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #64748b; transition: all 0.12s; position: relative; }
.notif-btn:hover { background: #f1f5f9; color: #0f172a; border-color: #cbd5e1; }
.notif-badge { position: absolute; top: 4px; right: 4px; background: #ef4444; color: #fff; font-size: 0.6rem; font-weight: 700; border-radius: 50%; width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; }
.user-profile { display: flex; align-items: center; gap: 0.5rem; padding: 0.25rem 0.75rem 0.25rem 0.25rem; border-radius: 10px; border: 1px solid #e2e8f0; cursor: pointer; background: #fff; }
.user-profile:hover { background: #f8fafc; border-color: #cbd5e1; }
.user-avatar { width: 30px; height: 30px; border-radius: 8px; background: linear-gradient(135deg, #3b82f6, #8b5cf6); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 0.65rem; font-weight: 700; }
.user-name { font-size: 0.85rem; font-weight: 600; color: #0f172a; }
.main-content { padding: 1.75rem; flex: 1; min-width: 0; max-width: 100%; overflow-x: auto; }

@media (max-width: 768px) {
  .main-content { padding: 1rem; }
  .user-name { display: none; }
  .sidebar { width: 260px !important; transform: translateX(-100%); }
  .sidebar.sidebar-collapsed { transform: translateX(0); width: 260px !important; }
  .sidebar.sidebar-collapsed .nav-item { justify-content: flex-start; padding: 0.55rem 1rem; }
  .sidebar.sidebar-collapsed .nav-item span { display: inline; }
  .sidebar.sidebar-collapsed .nav-section-label { text-align: left; padding: 0.9rem 1rem 0.3rem; font-size: 0.65rem; }
  .sidebar.sidebar-collapsed .logout-btn { justify-content: flex-start; padding: 0.55rem 0.75rem; }
  .sidebar.sidebar-collapsed .logout-btn span { display: inline; }
  .sidebar.sidebar-collapsed .nav-item::after,
  .sidebar.sidebar-collapsed .logout-btn::after { display: none; }
  .sidebar.sidebar-collapsed .sidebar-toggle { position: static; transform: none; margin-left: auto; }
  .sidebar.sidebar-collapsed .sidebar-logo { opacity: 1; transform: none; pointer-events: auto; }
  .main-area, .main-area.main-area-expanded { margin-left: 0; }
  .sidebar-toggle { margin-left: auto; }
}
.notif-dropdown { position: relative; }
.notif-menu { position: absolute; top: 44px; right: 0; width: 320px; background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); z-index: 100; max-height: 400px; display: flex; flex-direction: column; }
.notif-menu-header { display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 1rem; border-bottom: 1px solid #e2e8f0; }
.notif-menu-title { font-size: 0.85rem; font-weight: 600; color: #0f172a; }
.notif-mark-all { font-size: 0.72rem; color: #3b82f6; background: none; border: none; cursor: pointer; padding: 0.2rem 0.5rem; border-radius: 4px; }
.notif-mark-all:hover { background: #eff6ff; }
.notif-list { flex: 1; overflow-y: auto; max-height: 300px; }
.notif-item { padding: 0.6rem 1rem; border-bottom: 1px solid #f1f5f9; cursor: pointer; transition: background 0.12s; }
.notif-item:hover { background: #f8fafc; }
.notif-item.unread { background: #eff6ff; border-left: 3px solid #3b82f6; }
.notif-item-title { font-size: 0.8rem; font-weight: 600; color: #0f172a; margin-bottom: 0.15rem; }
.notif-item-body { font-size: 0.72rem; color: #64748b; margin-bottom: 0.15rem; }
.notif-item-time { font-size: 0.65rem; color: #94a3b8; }
.notif-empty { padding: 1rem; text-align: center; color: #94a3b8; font-size: 0.8rem; }
.notif-menu-footer { padding: 0.5rem 1rem; border-top: 1px solid #e2e8f0; }
.notif-view-all { display: block; text-align: center; font-size: 0.78rem; color: #3b82f6; text-decoration: none; padding: 0.3rem 0; }
.notif-view-all:hover { background: #f8fafc; }
</style>

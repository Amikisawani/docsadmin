<?php
// Write complete Vue files without truncation risk

$files = [];

// ====== LOGIN PAGE ======
$files['resources/js/pages/auth/LoginPage.vue'] = <<<'VUE'
<template>
  <div class="login-page">
    <div class="login-container">
      <div class="login-branding">
        <div class="brand-bg"></div>
        <div class="brand-content">
          <div class="brand-logo">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
            </svg>
          </div>
          <h1 class="brand-title">AdminFlow</h1>
          <p class="brand-subtitle">Plateforme intelligente de gestion<br/>electronique des documents</p>
          <div class="brand-stats">
            <div class="stat-item">
              <span class="stat-value">10K+</span>
              <span class="stat-label">Documents</span>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
              <span class="stat-value">500+</span>
              <span class="stat-label">Utilisateurs</span>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
              <span class="stat-value">99.9%</span>
              <span class="stat-label">Disponibilite</span>
            </div>
        </div>
      <div class="login-form-section">
        <div class="login-form-wrapper">
          <div class="form-header">
            <div class="mobile-logo">
              <div class="mobile-logo-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                </svg>
              </div>
              <span class="mobile-logo-text">AdminFlow</span>
            </div>
            <h2 class="form-title">Connexion</h2>
            <p class="form-subtitle">Accedez a votre espace documentaire</p>
          </div>
          <form @submit.prevent="handleLogin" class="login-form">
            <div class="input-group">
              <label>Adresse email</label>
              <div class="input-wrapper">
                <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                </svg>
                <input v-model="email" type="email" required placeholder="admin@adminflow.ci" />
              </div>
            <div class="input-group">
              <label>Mot de passe</label>
              <div class="input-wrapper">
                <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                </svg>
                <input v-model="password" type="password" required placeholder="********" />
              </div>
            <div v-if="error" class="error-message">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
              </svg>
              <span>{{ error }}</span>
            </div>
            <button type="submit" :disabled="loading" class="submit-btn">
              <span v-if="!loading">Se connecter</span>
              <span v-else>Connexion en cours...</span>
            </button>
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
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  background: #f0f4f8;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
}

.login-container {
  display: flex;
  width: 100%;
  max-width: 1000px;
  min-height: 600px;
  background: #fff;
  border-radius: 24px;
  box-shadow: 0 25px 50px -12px rgba(0,0,0,0.15);
  overflow: hidden;
}

.login-branding {
  flex: 1;
  position: relative;
  display: none;
  background: linear-gradient(135deg, #0b1d3a, #1e3a5f, #1e40af);
  padding: 3rem;
  align-items: center;
  justify-content: center;
}

.brand-bg {
  position: absolute;
  inset: 0;
  overflow: hidden;
}

.brand-bg::before {
  content: '';
  position: absolute;
  top: -50%;
  right: -30%;
  width: 800px;
  height: 800px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(59,130,246,0.15), transparent 70%);
}

.brand-bg::after {
  content: '';
  position: absolute;
  bottom: -30%;
  left: -20%;
  width: 600px;
  height: 600px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(99,102,241,0.1), transparent 70%);
}

.brand-content {
  position: relative;
  z-index: 1;
  text-align: center;
}

.brand-logo {
  width: 72px;
  height: 72px;
  margin: 0 auto 1.5rem;
  background: linear-gradient(135deg, #3b82f6, #6366f1);
  border-radius: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  box-shadow: 0 20px 60px rgba(59,130,246,0.3);
}

.brand-title {
  font-size: 2.2rem;
  font-weight: 800;
  color: #fff;
  letter-spacing: -0.02em;
  margin-bottom: 0.5rem;
}

.brand-subtitle {
  font-size: 1rem;
  color: rgba(255,255,255,0.7);
  line-height: 1.6;
  margin-bottom: 2rem;
}

.brand-stats {
  display: inline-flex;
  gap: 1.5rem;
  align-items: center;
  background: rgba(255,255,255,0.08);

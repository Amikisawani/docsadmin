<?php
$path = 'c:/Users/Dell/Documents/gestion_doc/resources/js/pages/auth/LoginPage.vue';
$content = '<template>
<div class="flex min-h-screen">
  <div class="w-1-2 bg-slate-900 p-12">
    <h1 class="text-4xl font-bold text-white">AdminFlow</h1>
    <p class="text-blue-200">Plateforme de gestion electronique des documents</p>
  </div>
  <div class="w-1-2 bg-slate-50 p-8">
    <h2 class="text-2xl font-bold mb-1">Connexion</h2>
    <p class="text-sm text-slate-500 mb-6">Accedez a votre espace de travail</p>
    <form submit="handleLogin">
      <div class="mb-4">
        <label class="block text-sm font-medium mb-1">Email</label>
        <input type="email" required class="w-full p-2 border rounded text-sm">
      </div>
      <div class="mb-4">
        <label class="block text-sm font-medium mb-1">Mot de passe</label>
        <input type="password" required class="w-full p-2 border rounded text-sm">
      </div>
      <button type="submit" class="w-full p-2 bg-blue-700 text-white rounded text-sm font-medium">Se connecter</button>
    </form>
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
const error = ref("");
async function handleLogin() {
  try {
    await authStore.login({ email: email.value, password: password.value });
    router.push("/");
  } catch (ex) {
    error.value = "Erreur de connexion";
  }
}
</script>';
file_put_contents($path, $content);
echo "Written " . strlen($content) . " bytes to $path\n";
echo "First bytes hex: " . bin2hex(substr($content, 0, 15)) . "\n";

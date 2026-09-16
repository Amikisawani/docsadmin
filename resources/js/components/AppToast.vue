<template>
  <Teleport to="body">
    <Transition name="app-toast">
      <div
        v-if="toast"
        class="app-toast"
        :class="'is-' + toast.type"
        role="alert"
      >
        <div class="app-toast-icon" aria-hidden="true">
          <svg v-if="toast.type === 'success'" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <svg v-else width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
        </div>
        <div class="app-toast-copy">
          <p class="app-toast-title">{{ toast.title }}</p>
          <p class="app-toast-message">{{ toast.message }}</p>
        </div>
        <button type="button" class="app-toast-close" aria-label="Fermer" @click="dismiss">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { onUnmounted, watch } from "vue";

export type ToastType = "success" | "error" | "warning";

export interface AppToastPayload {
  type: ToastType;
  title: string;
  message: string;
}

const toast = defineModel<AppToastPayload | null>({ default: null });

let hideTimer: ReturnType<typeof setTimeout> | null = null;

function dismiss() {
  toast.value = null;
}

watch(toast, (value) => {
  if (hideTimer) {
    clearTimeout(hideTimer);
    hideTimer = null;
  }
  if (value?.type === "success") {
    hideTimer = setTimeout(() => {
      toast.value = null;
    }, 4200);
  }
});

onUnmounted(() => {
  if (hideTimer) clearTimeout(hideTimer);
});
</script>

<style scoped>
.app-toast {
  position: fixed;
  right: 1.25rem;
  bottom: 1.25rem;
  z-index: 80;
  width: min(420px, calc(100vw - 2rem));
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 0.95rem 1rem;
  border-radius: 16px;
  background: #0f172a;
  color: #fff;
  box-shadow: 0 18px 40px rgba(15, 23, 42, 0.28);
  border: 1px solid rgba(255, 255, 255, 0.08);
}
.app-toast.is-success {
  background: linear-gradient(180deg, #0f172a 0%, #052e16 180%);
  border-color: rgba(34, 197, 94, 0.35);
}
.app-toast.is-error,
.app-toast.is-warning {
  background: linear-gradient(180deg, #0f172a 0%, #3f0d12 180%);
  border-color: rgba(248, 113, 113, 0.4);
}
.app-toast-icon {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.is-success .app-toast-icon { background: rgba(34, 197, 94, 0.18); color: #86efac; }
.is-error .app-toast-icon,
.is-warning .app-toast-icon { background: rgba(248, 113, 113, 0.18); color: #fca5a5; }
.app-toast-copy { flex: 1; min-width: 0; }
.app-toast-title {
  margin: 0 0 0.15rem;
  font-size: 0.88rem;
  font-weight: 700;
  color: #fff;
}
.app-toast-message {
  margin: 0;
  font-size: 0.8rem;
  line-height: 1.45;
  color: #cbd5e1;
}
.app-toast-close {
  width: 28px;
  height: 28px;
  border: none;
  background: rgba(255, 255, 255, 0.06);
  color: #94a3b8;
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.app-toast-close:hover { background: rgba(255, 255, 255, 0.12); color: #fff; }

.app-toast-enter-active,
.app-toast-leave-active {
  transition: opacity 0.22s ease, transform 0.22s ease;
}
.app-toast-enter-from,
.app-toast-leave-to {
  opacity: 0;
  transform: translateY(10px);
}
</style>

<template>
  <div class="wf-progress-cell">
    <div v-if="!doc.currentWorkflowInstance" class="wf-empty">
      <span class="text-muted text-xs">—</span>
    </div>

    <div v-else class="wf-progress">
      <div class="wf-info">
        <span class="wf-step text-xs font-medium">
          {{ currentStepLabel }}
        </span>
        <span v-if="approvedNames.length" class="wf-approved text-xs">
          <span class="wf-check">✔</span> {{ approvedNames }}
        </span>
        <span v-if="pendingApprover" class="wf-approver text-xs">
          <span class="wf-clock">⏳</span> En attente : {{ pendingApprover.name }}
        </span>
        <span v-else-if="isCompleted" class="wf-completed text-xs">
          ✓ Workflow terminé
        </span>
      </div>

      <button
        v-if="canRemind"
        class="wf-remind-btn"
        title="Rappeler l'approbateur"
        @click="$emit('remind', doc)"
      >
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0A3 3 0 1115.714 21H8.286a3 3 0 110-6h7.428z" />
        </svg>
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { Document } from "../types";

interface Props {
  doc: Document;
  currentUserRole: string;
  currentUserId: string;
}

const props = defineProps<Props>();

const instance = computed(() => props.doc.currentWorkflowInstance);
const approvals = computed(() => instance.value?.approvals || []);

const totalSteps = computed(() => {
  const steps = instance.value?.workflow?.steps;
  return steps ? steps.length : approvals.value.length;
});

const completedSteps = computed(() =>
  approvals.value.filter((a) => a.status === "approved").length
);

const progressPercent = computed(() => {
  if (totalSteps.value === 0) return 0;
  return Math.round((completedSteps.value / totalSteps.value) * 100);
});

const pendingApprovals = computed(() =>
  approvals.value.filter((a) => a.status === "pending")
);

const pendingApprover = computed(() => {
  const pending = pendingApprovals.value[0];
  return pending?.approver;
});

const approvedNames = computed(() => {
  return approvals.value
    .filter((a) => a.status === "approved" && a.approver?.name)
    .map((a) => a.approver!.name)
    .join(", ");
});

const isCompleted = computed(() => instance.value?.status === "completed");

const currentStepLabel = computed(() => {
  if (!instance.value) return "";
  if (isCompleted.value) return "Terminé";
  const step = instance.value.current_step;
  const pending = pendingApprovals.value[0];
  if (pending) {
    return `${step} (${pendingApprover.value?.name || "en attente"})`;
  }
  return step;
});

const canRemind = computed(() => {
  // Le bouton de rappel est visible si :
  // - il y a une approbation en attente
  // - l'utilisateur courant est l'auteur du document ou admin
  if (pendingApprovals.value.length === 0) return false;
  const isAuthor = props.doc.author_id === props.currentUserId;
  const isAdmin = props.currentUserRole === "admin";
  return isAuthor || isAdmin;
});
</script>

<style scoped>
.wf-progress-cell {
  min-width: 260px;
}
.wf-approved {
  color: #16a34a;
  line-height: 1.3;
}
.wf-check {
  color: #16a34a;
}
.wf-clock {
  color: #f59e0b;
}
.wf-completed {
  color: #16a34a;
  font-weight: 600;
}
.wf-progress {
  display: flex;
  align-items: center;
  gap: 0.4rem;
}
.wf-progress-bar {
  width: 50px;
  height: 6px;
  background: #e2e8f0;
  border-radius: 3px;
  overflow: hidden;
  flex-shrink: 0;
}
.wf-progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #3b82f6, #10b981);
  border-radius: 3px;
  transition: width 0.3s ease;
}
.wf-info {
  display: flex;
  flex-direction: column;
  flex: 1;
}
.wf-step {
  line-height: 1.2;
}
.wf-approver {
  line-height: 1.2;
}
.wf-remind-btn {
  width: 24px;
  height: 24px;
  border-radius: 4px;
  border: 1px solid #e2e8f0;
  background: #fff;
  color: #64748b;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.12s;
  flex-shrink: 0;
}
.wf-remind-btn:hover {
  background: #eff6ff;
  color: #2563eb;
  border-color: #2563eb;
}
.wf-empty {
  padding: 2px 0;
}
</style>

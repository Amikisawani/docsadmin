// ============================================================
// SignatureTool.vue
//
// Modal de signature professionnelle pour le Directeur de Cabinet :
//  - Aperçu réel du document (PDF ou contenu)
//  - Deux sources de signature : existante ou dessinée
//  - Placement par clic sur le document
//  - Confirmation et signature sécurisée
// ============================================================
<template>
  <div class="modal-overlay sigtool" @click.self="close">
    <div class="modal-content sigtool-modal">
      <div class="sigtool-header">
        <div>
          <h3 class="card-title mb-1">Signer le document</h3>
          <p class="sigtool-sub">
            Visualisez le document, choisissez ou dessinez votre signature, puis confirmez.
          </p>
        </div>
        <button class="sigtool-close" @click="close" title="Fermer">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>

      <div v-if="loading" class="sigtool-no-sig">
        <p>Chargement...</p>
      </div>

      <div v-else-if="roleDenied" class="sigtool-no-sig">
        <p>Rôle non autorisé à signer.</p>
      </div>

      <template v-else>
        <!-- Bandeau publipostage -->
        <div v-if="isMailMerge" class="sigtool-mailmerge">
          <div class="sigtool-mailmerge-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
          </div>
          <div>
            <p class="sigtool-mm-title">Publipostage</p>
            <p class="sigtool-mm-sub">La signature sera appliquée sur toutes les pages du document.</p>
          </div>
        </div>

        <!-- Onglets source de signature -->
        <div class="sigtool-tabs">
          <button
            class="sigtool-tab"
            :class="{ active: sourceMode === 'existing' }"
            @click="sourceMode = 'existing'"
          >
            Signature existante
          </button>
          <button
            class="sigtool-tab"
            :class="{ active: sourceMode === 'draw' }"
            @click="sourceMode = 'draw'"
          >
            Dessiner
          </button>
        </div>

        <!-- Sélection signature existante -->
        <div v-if="sourceMode === 'existing'" class="sigtool-section">
          <label class="sigtool-label">Choisir une signature</label>
          <select v-model="selectedSignatureId" class="form-select sigtool-select">
            <option v-for="s in signatures" :key="s.id" :value="s.id">
              {{ s.label || ("Signature " + (s.type || "")) }}
            </option>
          </select>
          <div v-if="signatures.length === 0" class="sigtool-empty-select">
            Aucune signature enregistrée.
            <router-link to="/signatures" class="sigtool-link">Créez-en une</router-link>
          </div>
        </div>

        <!-- Dessin de signature -->
        <div v-if="sourceMode === 'draw'" class="sigtool-section">
          <label class="sigtool-label">Dessinez votre signature</label>
          <SignaturePad v-model="drawnSignature" :stroke-color="drawColor" />
          <div class="sigtool-draw-tools">
            <input type="color" v-model="drawColor" class="sigtool-color" />
            <button type="button" class="sigtool-btn-outline" @click="drawnSignature = ''">Effacer</button>
          </div>
        </div>

<!-- Taille de la signature -->
        <div class="sigtool-size-row">
          <label class="sigtool-label">Taille de la signature</label>
          <input
            type="range"
            min="40"
            max="160"
            v-model.number="sigScale"
            class="sigtool-range"
          />
          <span class="sigtool-size-value">{{ sigScale }}%</span>
        </div>

        <!-- Aperçu du document -->
        <div class="sigtool-preview-section">
          <label class="sigtool-label">Aperçu du document</label>
          <div class="sigtool-document-wrap" ref="paper" @click="onPaperClick">
            <iframe
              v-if="documentPreviewUrl"
              :src="documentPreviewUrl"
              class="sigtool-document-iframe"
            ></iframe>
            <div v-else class="sigtool-document-fallback">
              <p class="sigtool-doc-fallback-title">{{ document.subject }}</p>
              <p class="sigtool-doc-fallback-ref">N° {{ document.document_number }}</p>
              <div v-if="document.content" class="sigtool-doc-fallback-content">{{ document.content }}</div>
            </div>

            <!-- Signature placée -->
            <div
              v-if="hasSignature && placement"
              class="sigtool-placed-sig"
              :style="placedStyle"
              @mousedown.stop="startDrag"
              @touchstart.stop.prevent="startDrag"
            >
              <img v-if="previewSignatureUrl" :src="previewSignatureUrl" class="sigtool-placed-img" draggable="false" />
              <div v-else class="sigtool-placed-text">{{ drawnSignature ? 'Signature' : '' }}</div>
              <button class="sigtool-placed-remove" @mousedown.stop @click.stop="removePlacement" title="Retirer">
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
              </button>
            </div>

            <!-- Invitation à cliquer -->
            <div v-if="!placement" class="sigtool-click-hint">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
              <span>Cliquez sur le document pour poser votre signature</span>
            </div>
          </div>
        </div>

        <!-- Confirmation emplacement -->
        <transition name="sigtool-pop">
          <div v-if="placement" class="sigtool-confirm">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 100-18 9 9 0 000 18z" /></svg>
            <span>Emplacement confirmé : ({{ Math.round(placement.x || 0) }}%, {{ Math.round(placement.y || 0) }}%) — cliquer sur « Signer »</span>
          </div>
        </transition>

        <div v-if="error" class="form-error">{{ error }}</div>

        <div class="form-actions sigtool-actions">
          <button
            class="btn-primary"
            :disabled="!canSubmit || signing"
            @click="sign"
          >
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
            {{ signing ? "Signature en cours..." : "Signer le document" }}
          </button>
          <button type="button" class="btn-secondary" @click="close">Annuler</button>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from "vue";
import apiClient from "../utils/axios";
import type { Signature, Document } from "../types";
import SignaturePad from "./SignaturePad.vue";
import { useAuthStore } from "../stores/auth";

const props = withDefaults(defineProps<{
  document: Document;
}>(), {});

const emit = defineEmits<{
  (e: "signed", result: any): void;
  (e: "close"): void;
}>();

const authStore = useAuthStore();
const loading = ref(true);
const signatures = ref<Signature[]>([]);
const selectedSignatureId = ref("");
const sourceMode = ref<"existing" | "draw">("existing");
const drawnSignature = ref("");
const drawColor = ref("#0f172a");
const placement = ref<{ x: number; y: number } | null>(null);
const signing = ref(false);
const error = ref("");
const paper = ref<HTMLElement | null>(null);
const sigScale = ref(100); // Taille relative de la signature (%)

const roleDenied = computed(
  () => !authStore.hasAnyRole(["directeur_cabinet", "admin"])
);

const documentSignable = computed(
  () => props.document.status === "pending"
);

const isMailMerge = computed(
  () => props.document?.flow_type === "mail_merge"
    || Boolean((props.document as any)?.is_mail_merge)
    || props.document?.reference?.toLowerCase().includes("mail")
    || props.document?.reference?.toLowerCase().includes("publipostage")
);

const hasSignature = computed(() => {
  if (sourceMode.value === "draw") return Boolean(drawnSignature.value);
  return Boolean(selectedSignatureId.value && selectedSignature.value);
});

const selectedSignature = computed(() =>
  signatures.value.find((s) => s.id === selectedSignatureId.value) || null
);

const previewSignatureUrl = computed(() => {
  if (sourceMode.value === "draw") return drawnSignature.value;
  const s = selectedSignature.value;
  if (!s) return null;
  return s.image_path ? "/storage/" + s.image_path : null;
});

const placedStyle = computed(() => {
  if (!placement.value) return {};
  return {
    left: placement.value.x + "%",
    top: placement.value.y + "%",
    transform: "translate(-50%, -50%) scale(" + (sigScale.value / 100) + ")",
    transformOrigin: "center",
  };
});

const canSubmit = computed(() => {
  if (!documentSignable.value) return false;
  if (!hasSignature.value) return false;
  if (!placement.value) return false;
  return true;
});

const documentPreviewUrl = computed(() => {
  const pdf = props.document.signed_pdf_path || props.document.source_file_path;
  if (pdf && pdf.toLowerCase().endsWith(".pdf")) {
    return "/storage/" + pdf;
  }
  return null;
});

function onPaperClick(e: MouseEvent) {
  if (!paper.value) return;
  const rect = paper.value.getBoundingClientRect();
  const x = ((e.clientX - rect.left) / rect.width) * 100;
  const y = ((e.clientY - rect.top) / rect.height) * 100;
  placement.value = { x: clamp(x, 4, 96), y: clamp(y, 4, 96) };
  error.value = "";
}

let dragging = false;
let dragStart = { x: 0, y: 0 };
let dragPlacement = { x: 0, y: 0 };

function startDrag(e: MouseEvent | TouchEvent) {
  if (!placement.value || !paper.value) return;
  e.preventDefault();
  dragging = true;
  const pos = getEventPos(e);
  dragStart = pos;
  dragPlacement = { ...placement.value };

  const onMove = (ev: MouseEvent | TouchEvent) => {
    if (!dragging || !paper.value) return;
    const p = getEventPos(ev);
    const dx = ((p.x - dragStart.x) / paper.value.getBoundingClientRect().width) * 100;
    const dy = ((p.y - dragStart.y) / paper.value.getBoundingClientRect().height) * 100;
    placement.value = {
      x: clamp(dragPlacement.x + dx, 4, 96),
      y: clamp(dragPlacement.y + dy, 4, 96),
    };
  };

  const onUp = () => {
    dragging = false;
    document.removeEventListener("mousemove", onMove);
    document.removeEventListener("mouseup", onUp);
    document.removeEventListener("touchmove", onMove);
    document.removeEventListener("touchend", onUp);
  };

  document.addEventListener("mousemove", onMove);
  document.addEventListener("mouseup", onUp);
  document.addEventListener("touchmove", onMove, { passive: false });
  document.addEventListener("touchend", onUp);
}

function getEventPos(e: MouseEvent | TouchEvent): { x: number; y: number } {
  if ("touches" in e) {
    return { x: e.touches[0].clientX, y: e.touches[0].clientY };
  }
  return { x: e.clientX, y: e.clientY };
}

function removePlacement() {
  placement.value = null;
}

function clamp(v: number, min: number, max: number) {
  return Math.min(max, Math.max(min, v));
}

async function sign() {
  if (!canSubmit.value || signing.value) return;
  signing.value = true;
  error.value = "";
  try {
    let signatureId = selectedSignatureId.value;
    let position = placement.value;

    // Si mode dessiné, créer d'abord la signature puis l'utiliser
    if (sourceMode.value === "draw" && drawnSignature.value) {
      const drawn = await saveDrawnSignature(drawnSignature.value);
      signatureId = drawn.id;
    }

    const res = await apiClient.post(`/documents/${props.document.id}/sign`, {
      signature_id: signatureId,
      position,
      is_mail_merge: isMailMerge.value,
      pages: isMailMerge.value ? 1 : null,
    });
    emit("signed", res.data);
    emit("close");
  } catch (e: any) {
    error.value = e.response?.data?.message || "Erreur lors de la signature";
  } finally {
    signing.value = false;
  }
}

async function saveDrawnSignature(dataUrl: string): Promise<Signature> {
  const blob = await (await fetch(dataUrl)).blob();
  const form = new FormData();
  form.append("type", "graphical");
  form.append("label", "Signature dessinée");
  form.append("image", blob, "signature.png");
  const { data } = await apiClient.post("/signatures", form, {
    headers: { "Content-Type": "multipart/form-data" },
  });
  signatures.value.push(data.data);
  selectedSignatureId.value = data.data.id;
  sourceMode.value = "existing";
  return data.data;
}

function close() {
  emit("close");
}

function onKeydown(e: KeyboardEvent) {
  if (e.key === "Escape") {
    close();
  } else if (e.key === "Enter" && canSubmit.value && !signing.value) {
    e.preventDefault();
    sign();
  }
}

onMounted(async () => {
  window.addEventListener("keydown", onKeydown);
  try {
    const { data } = await apiClient.get("/signatures");
    signatures.value = (data.data.data || []).filter((s: any) => s.is_active !== false);
const def = signatures.value.find((s) => s.is_default);
    selectedSignatureId.value = (def || signatures.value[0])?.id || "";
    // Pré-placer la signature à un emplacement par défaut pour que le bouton
    // « Signer » soit immédiatement actif (le Directeur peut ensuite le déplacer).
    if (!placement.value && !roleDenied.value && documentSignable.value) {
      placement.value = { x: 50, y: 72 };
    }
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
});

onUnmounted(() => {
  window.removeEventListener("keydown", onKeydown);
});

watch(
  () => sourceMode.value,
  () => {
    error.value = "";
  }
);
</script>

<style scoped>
.sigtool-modal {
  max-width: 980px;
  max-height: calc(100vh - 2rem);
  display: flex;
  flex-direction: column;
}
.sigtool-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1rem;
}
.sigtool-sub {
  font-size: 0.82rem;
  color: #64748b;
  margin: 0;
}
.sigtool-close {
  background: none;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 6px;
}
.sigtool-close:hover {
  background: #f1f5f9;
  color: #0f172a;
}
.sigtool-no-sig {
  padding: 2rem;
  text-align: center;
  color: #64748b;
}

/* Bandeau publipostage */
.sigtool-mailmerge {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  background: #fef3c7;
  border: 1px solid #fde68a;
  border-radius: 12px;
  margin-bottom: 1rem;
}
.sigtool-mailmerge-icon {
  width: 38px;
  height: 38px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
  background: #f59e0b;
  color: #fff;
}
.sigtool-mm-title {
  font-size: 0.88rem;
  font-weight: 700;
  color: #92400e;
  margin: 0;
}
.sigtool-mm-sub {
  font-size: 0.78rem;
  color: #b45309;
  margin: 0;
}

/* Onglets */
.sigtool-tabs {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1rem;
  border-bottom: 1px solid #e2e8f0;
}
.sigtool-tab {
  padding: 0.5rem 0.9rem;
  font-size: 0.82rem;
  font-weight: 600;
  color: #64748b;
  background: none;
  border: none;
  border-bottom: 2px solid transparent;
  cursor: pointer;
  margin-bottom: -1px;
}
.sigtool-tab.active {
  color: #1d4ed8;
  border-bottom-color: #1d4ed8;
}

/* Section */
.sigtool-section {
  margin-bottom: 1rem;
}
.sigtool-label {
  display: block;
  font-size: 0.78rem;
  font-weight: 600;
  color: #334155;
  margin-bottom: 0.3rem;
}
.sigtool-select {
  width: 100%;
  max-width: 360px;
}
.sigtool-empty-select {
  font-size: 0.82rem;
  color: #64748b;
  margin-top: 0.4rem;
}
.sigtool-link {
  color: #2563eb;
  text-decoration: none;
  font-weight: 600;
}
.sigtool-link:hover {
  text-decoration: underline;
}

/* Dessin */
.sigtool-draw-tools {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-top: 0.6rem;
}
.sigtool-color {
  width: 32px;
  height: 32px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 2px;
  cursor: pointer;
}
.sigtool-btn-outline {
  font-size: 0.78rem;
  font-weight: 600;
  color: #dc2626;
  background: none;
  border: 1px solid #fecaca;
  border-radius: 8px;
  padding: 0.35rem 0.7rem;
  cursor: pointer;
}
.sigtool-btn-outline:hover {
  background: #fef2f2;
}

/* Taille signature */
.sigtool-size-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1rem;
}
.sigtool-size-row .sigtool-label {
  margin: 0;
  min-width: 130px;
}
.sigtool-range {
  flex: 1;
  accent-color: #2563eb;
}
.sigtool-size-value {
  font-size: 0.8rem;
  font-weight: 700;
  color: #1d4ed8;
  min-width: 40px;
  text-align: right;
}

/* Aperçu document */
.sigtool-preview-section {
  margin-bottom: 1rem;
}
.sigtool-document-wrap {
  position: relative;
  background: #f1f5f9;
  border-radius: 10px;
  overflow: hidden;
  border: 1px solid #e2e8f0;
  min-height: 420px;
  max-height: 56vh;
  cursor: crosshair;
}
.sigtool-document-iframe {
  width: 100%;
  height: 56vh;
  border: none;
  background: #fff;
  display: block;
}
.sigtool-document-fallback {
  padding: 2rem;
  background: #fff;
}
.sigtool-doc-fallback-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 0.25rem;
}
.sigtool-doc-fallback-ref {
  font-size: 0.82rem;
  color: #64748b;
  margin: 0 0 1rem;
}
.sigtool-doc-fallback-content {
  font-size: 0.9rem;
  color: #334155;
  line-height: 1.6;
  white-space: pre-wrap;
}

/* Signature placée */
.sigtool-placed-sig {
  position: absolute;
  transform: translate(-50%, -50%);
  z-index: 30;
  user-select: none;
  -webkit-user-select: none;
}
.sigtool-placed-img {
  display: block;
  height: 64px;
  width: auto;
  max-width: 220px;
}
.sigtool-placed-text {
  font-size: 1.1rem;
  font-style: italic;
  color: #0f172a;
  white-space: nowrap;
}
.sigtool-placed-remove {
  position: absolute;
  top: -8px;
  right: -8px;
  width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: #dc2626;
  color: #fff;
  border: none;
  cursor: pointer;
  box-shadow: 0 1px 4px rgba(0,0,0,0.2);
  opacity: 0;
  transition: opacity 0.15s;
}
.sigtool-placed-sig:hover .sigtool-placed-remove {
  opacity: 1;
}

/* Invitation */
.sigtool-click-hint {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  flex-direction: column;
  color: #94a3b8;
  font-size: 0.85rem;
  background: rgba(255,255,255,0.55);
  pointer-events: none;
  text-align: center;
  padding: 1rem;
}

/* Confirmation */
.sigtool-confirm {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-top: 0.75rem;
  padding: 0.6rem 0.9rem;
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 10px;
  color: #15803d;
  font-size: 0.82rem;
  font-weight: 500;
}
.sigtool-confirm svg {
  flex-shrink: 0;
}
.sigtool-actions {
  border-top: none;
  padding-top: 1rem;
}

/* Transitions */
.sigtool-pop-enter-active,
.sigtool-pop-leave-active {
  transition: all 0.25s ease;
}
.sigtool-pop-enter-from,
.sigtool-pop-leave-to {
  opacity: 0;
  transform: translateY(6px);
}
</style>

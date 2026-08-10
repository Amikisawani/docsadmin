// ============================================================
// CameraSignatureCapture.vue
//
// Scanner de signature manuscrite via la webcam :
//  - L'utilisateur présente sa signature (écrite sur une feuille
//    blanche) devant la webcam.
//  - Un cadre de recadrage guide le cadrage.
//  - À la capture, l'image est recadrée, la zone blanche/fond
//    devient transparente (seule l'encre est conservée), et le
//    résultat est fourni en PNG transparent (dataURL + Blob).
// ============================================================
<template>
  <div class="cam-sig">
    <div v-if="!hasCamera && !error" class="cam-sig-empty">
      <p>Vérification des caméras...</p>
    </div>

    <div v-if="error" class="form-error">{{ error }}</div>

    <template v-else-if="streamReady && !captured">
      <div class="cam-sig-viewport">
        <video
          ref="videoEl"
          class="cam-sig-video"
          autoplay
          playsinline
          muted
        ></video>

        <!-- Cadre de recadrage -->
        <div class="cam-sig-guide">
          <div class="cam-sig-guide-corner tl"></div>
          <div class="cam-sig-guide-corner tr"></div>
          <div class="cam-sig-guide-corner bl"></div>
          <div class="cam-sig-guide-corner br"></div>
          <p class="cam-sig-guide-text">Placez votre signature dans le cadre</p>
        </div>
      </div>

      <div class="cam-sig-actions">
        <button type="button" class="btn-primary" @click="capture">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z"/></svg>
          Capturer
        </button>
        <button type="button" class="btn-secondary" @click="stopCamera">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
          Annuler
        </button>
      </div>
    </template>

    <!-- Résultat après capture : recadrage + fond transparent -->
    <template v-else-if="captured">
      <div class="cam-sig-preview">
        <div class="cam-sig-preview-paper">
          <img :src="resultDataUrl" alt="Signature scannée" />
        </div>
        <p class="cam-sig-preview-hint">Fond rendu transparent — seule l'encre est conservée.</p>
      </div>

      <div class="cam-sig-actions">
        <button type="button" class="btn-primary" @click="confirm">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          Utiliser cette signature
        </button>
        <button type="button" class="btn-secondary" @click="recapture">Reprendre</button>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from "vue";

const emit = defineEmits<{
  (e: "captured", data: { dataUrl: string; blob: Blob }): void;
}>();

const videoEl = ref<HTMLVideoElement | null>(null);
const hasCamera = ref(false);
const streamReady = ref(false);
const captured = ref(false);
const error = ref("");
const resultDataUrl = ref("");

let stream: MediaStream | null = null;

/** Demande l'accès à la webcam (écran principal). */
async function startCamera() {
  error.value = "";
  try {
    if (!navigator.mediaDevices?.getUserMedia) {
      error.value = "Votre navigateur ne supporte pas l'accès à la caméra.";
      return;
    }
    stream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: "environment", width: { ideal: 1280 }, height: { ideal: 720 } },
      audio: false,
    });
    hasCamera.value = true;
    streamReady.value = true;
    await new Promise<void>((resolve) => {
      const v = videoEl.value;
      if (!v) return resolve();
      v.onloadedmetadata = () => resolve();
      // Fallback si onloadedmetadata ne se déclenche pas
      setTimeout(() => resolve(), 600);
    });
    if (videoEl.value) {
      videoEl.value.srcObject = stream;
      await videoEl.value.play().catch(() => {});
    }
  } catch (e: any) {
    console.error(e);
    error.value = e?.name === "NotAllowedError"
      ? "Accès à la caméra refusé. Autorisez la caméra dans votre navigateur puis réessayez."
      : e?.name === "NotFoundError"
        ? "Aucune caméra détectée sur cet appareil."
        : "Impossible d'accéder à la caméra : " + (e?.message || "erreur inconnue");
  }
}

/** Stoppe la webcam et réinitialise. */
function stopCamera() {
  if (stream) {
    stream.getTracks().forEach((t) => t.stop());
    stream = null;
  }
  streamReady.value = false;
  hasCamera.value = false;
  captured.value = false;
  resultDataUrl.value = "";
}

/** Capture une image, recadre et met le fond en transparence. */
function capture() {
  const v = videoEl.value;
  if (!v || !v.videoWidth) {
    error.value = "Aucune image disponible de la caméra.";
    return;
  }

  const videoW = v.videoWidth;
  const videoH = v.videoHeight;

  // Le guide occupe ~78% de la largeur et ~55% de la hauteur (centré)
  const cropW = Math.round(videoW * 0.78);
  const cropH = Math.round(videoH * 0.55);
  const cropX = Math.round((videoW - cropW) / 2);
  const cropY = Math.round((videoH - cropH) / 2);

  // Canvas intermédiaire : image brute recadrée
  const raw = document.createElement("canvas");
  raw.width = cropW;
  raw.height = cropH;
  const rawCtx = raw.getContext("2d", { willReadFrequently: true });
  if (!rawCtx) return;
  rawCtx.drawImage(v, cropX, cropY, cropW, cropH, 0, 0, cropW, cropH);

  // Rendre le fond blanc/clair transparent (seuil de luminance)
  const out = document.createElement("canvas");
  out.width = cropW;
  out.height = cropH;
  const outCtx = out.getContext("2d");
  if (!outCtx) return;

  const imgData = rawCtx.getImageData(0, 0, cropW, cropH);
  const px = imgData.data;

  // 1er passage : déterminer la luminance médiane pour un seuil adaptatif
  let sum = 0;
  let count = 0;
  for (let i = 0; i < px.length; i += 4) {
    const lum = 0.299 * px[i] + 0.587 * px[i + 1] + 0.114 * px[i + 2];
    sum += lum;
    count++;
  }
  const avg = count ? sum / count : 255;

  // Seuil : tout pixel plus clair que (moyenne - marge) devient transparent.
  // Si la moyenne est très élevée (fond très clair), on garde un seuil raisonnable.
  const threshold = Math.min(230, Math.max(150, avg + 25));

  for (let i = 0; i < px.length; i += 4) {
    const lum = 0.299 * px[i] + 0.587 * px[i + 1] + 0.114 * px[i + 2];
    if (lum > threshold) {
      px[i + 3] = 0; // alpha = 0 → transparent
    } else {
      // Renforcer le contraste de l'encre
      const darker = Math.max(0, lum - 30);
      px[i] = darker;
      px[i + 1] = darker;
      px[i + 2] = darker;
      px[i + 3] = 255;
    }
  }

  outCtx.putImageData(imgData, 0, 0);

  resultDataUrl.value = out.toDataURL("image/png");
  captured.value = true;
}

/** Confirme la capture et transmet au parent. */
function confirm() {
  if (!resultDataUrl.value) return;
  const blob = dataUrlToBlob(resultDataUrl.value);
  if (blob) {
    emit("captured", { dataUrl: resultDataUrl.value, blob });
  }
}

/** Reprendre une photo. */
function recapture() {
  captured.value = false;
  resultDataUrl.value = "";
}

function dataUrlToBlob(dataUrl: string): Blob | null {
  const parts = dataUrl.split(",");
  if (parts.length < 2) return null;
  const mime = parts[0].match(/:(.*?);/)?.[1] || "image/png";
  const b64 = atob(parts[1]);
  const bytes = new Uint8Array(b64.length);
  for (let i = 0; i < b64.length; i++) bytes[i] = b64.charCodeAt(i);
  return new Blob([bytes], { type: mime });
}

onMounted(startCamera);
onBeforeUnmount(() => {
  if (stream) {
    stream.getTracks().forEach((t) => t.stop());
    stream = null;
  }
});

defineExpose({ stopCamera });
</script>

<style scoped>
.cam-sig {
  width: 100%;
}
.cam-sig-empty {
  padding: 1.5rem;
  text-align: center;
  color: #64748b;
  font-size: 0.85rem;
}
.cam-sig-viewport {
  position: relative;
  width: 100%;
  border-radius: 12px;
  overflow: hidden;
  background: #0f172a;
  aspect-ratio: 4 / 3;
}
.cam-sig-video {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
.cam-sig-guide {
  position: absolute;
  left: 11%;
  right: 11%;
  top: 22.5%;
  bottom: 22.5%;
  border: 2px dashed rgba(255, 255, 255, 0.75);
  border-radius: 8px;
  pointer-events: none;
}
.cam-sig-guide-corner {
  position: absolute;
  width: 18px;
  height: 18px;
  border: 3px solid #22c55e;
}
.cam-sig-guide-corner.tl { top: -3px; left: -3px; border-right: none; border-bottom: none; border-radius: 6px 0 0 0; }
.cam-sig-guide-corner.tr { top: -3px; right: -3px; border-left: none; border-bottom: none; border-radius: 0 6px 0 0; }
.cam-sig-guide-corner.bl { bottom: -3px; left: -3px; border-right: none; border-top: none; border-radius: 0 0 0 6px; }
.cam-sig-guide-corner.br { bottom: -3px; right: -3px; border-left: none; border-top: none; border-radius: 0 0 6px 0; }
.cam-sig-guide-text {
  position: absolute;
  bottom: -34px;
  left: 50%;
  transform: translateX(-50%);
  white-space: nowrap;
  color: rgba(255,255,255,0.92);
  background: rgba(15,23,42,0.6);
  padding: 0.25rem 0.7rem;
  border-radius: 6px;
  font-size: 0.75rem;
}
.cam-sig-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-top: 1rem;
}
.cam-sig-preview {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  padding: 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: linear-gradient(45deg, #f8fafc 25%, transparent 25%), linear-gradient(-45deg, #f8fafc 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #f8fafc 75%), linear-gradient(-45deg, transparent 75%, #f8fafc 75%);
  background-size: 16px 16px;
  background-position: 0 0, 0 8px, 8px -8px, -8px 0;
}
.cam-sig-preview-paper {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  min-height: 140px;
  background: #fff;
  border-radius: 8px;
}
.cam-sig-preview-paper img {
  max-height: 130px;
  max-width: 80%;
}
.cam-sig-preview-hint {
  font-size: 0.78rem;
  color: #64748b;
  margin: 0;
}
</style>

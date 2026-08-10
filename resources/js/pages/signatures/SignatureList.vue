<template>
  <div class="page-container">
    <div class="page-header">
      <div>
        <h1 class="page-title">Signatures</h1>
        <p class="page-subtitle">Gérer vos signatures électroniques</p>
      </div>
      <button class="btn-primary" @click="openForm()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
        Nouvelle signature
      </button>
    </div>

    <!-- Prévisualisation de l'animation -->
    <div v-if="signatures.length" class="sig-preview-row">
      <div class="sig-preview-card">
        <p class="sig-preview-title">Aperçu animation signature</p>
        <p class="sig-preview-sub">C'est l'animation qui sera jouée quand vous signerez un document.</p>
        <div class="sig-preview-paper" ref="previewPaper">
          <img v-if="previewSigUrl" :src="previewSigUrl" alt="Aperçu" class="sig-preview-img" />
          <p v-else class="sig-preview-empty">Aucune signature sélectionnée</p>
        </div>
        <button class="btn-filter mt-2" :disabled="!previewSigUrl" @click="replayPreview">▶ Rejouer l'animation</button>
      </div>
    </div>

<div class="table-card">
      <div class="table-wrapper">
        <table class="data-table">
          <thead><tr><th>Label</th><th>Type</th><th>Défaut</th><th>Statut</th><th class="text-right">Actions</th></tr></thead>
          <tbody>
            <tr v-for="sig in signatures" :key="sig.id" :class="{ 'sig-row-active': sig.id === previewSigId }">
              <td class="font-medium">
                <span class="sig-thumb" @click="selectPreview(sig)" :title="'Prévisualiser : ' + (sig.label || 'Signature')">
                  <img v-if="sig.image_path" :src="'/storage/' + sig.image_path" alt="sig" />
                  <svg v-else width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                </span>
                {{ sig.label || "Signature" }}
              </td>
              <td><span class="badge" :class="sig.type === 'graphical' ? 'badge-blue' : sig.type === 'digital' ? 'badge-purple' : 'badge-amber'">{{ sig.type }}</span></td>
              <td>{{ sig.is_default ? "Oui" : "-" }}</td>
              <td><span class="badge" :class="sig.is_active ? 'badge-green' : 'badge-gray'">{{ sig.is_active ? "Actif" : "Inactif" }}</span></td>
              <td class="text-right">
                <div class="action-btns">
                  <button @click="selectPreview(sig)" class="action-btn" title="Aperçu de la signature">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                  </button>
                  <button @click="deleteSignature(sig)" class="action-btn action-btn-danger" title="Supprimer la signature">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!signatures.length"><td colspan="5" class="empty-state">Aucune signature</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal création signature -->
    <div v-if="showForm" class="modal-overlay" @click.self="showForm = false">
      <div class="modal-content">
        <h3 class="card-title mb-4">Nouvelle signature</h3>
        <form @submit.prevent="createSignature">
          <div class="form-group">
            <label>Type *</label>
            <select v-model="form.type" required class="form-select">
              <option value="graphical">Graphique (dessin / PNG)</option>
              <option value="digital">Numérique</option>
              <option value="certificate">Certificat</option>
            </select>
          </div>

<!-- Mode graphique : dessin, upload ou scan webcam -->
          <div v-if="form.type === 'graphical'" class="form-group">
            <div class="mode-toggle mode-toggle-3 mb-4">
              <button type="button" class="mode-btn" :class="{ active: graphicMode === 'draw' }" @click="graphicMode = 'draw'">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                Dessiner
              </button>
              <button type="button" class="mode-btn" :class="{ active: graphicMode === 'upload' }" @click="graphicMode = 'upload'">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                Importer
              </button>
              <button type="button" class="mode-btn" :class="{ active: graphicMode === 'scan' }" @click="graphicMode = 'scan'">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z"/></svg>
                Scanner
              </button>
            </div>

            <template v-if="graphicMode === 'draw'">
              <SignaturePad ref="signaturePad" @change="onPadChange" />
              <p class="form-hint">Dessinez avec la souris ou le doigt. La signature sera enregistrée en PNG transparent.</p>
            </template>

            <template v-else-if="graphicMode === 'upload'">
              <label class="file-dropzone" :class="{ 'has-file': form.image }">
                <input type="file" accept="image/png" @change="onFileChange" />
                <div class="dz-icon">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                </div>
                <p v-if="!form.image" class="dz-title">Cliquez pour choisir un PNG</p>
                <p v-else class="dz-title">{{ form.image.name }}</p>
                <p class="dz-sub">Signature scannée ou exportée en PNG transparent</p>
              </label>
            </template>

            <template v-else>
              <CameraSignatureCapture @captured="onCameraCaptured" />
              <p class="form-hint">Signez sur une feuille blanche, placez-la devant la caméra et capturez. Le fond sera automatiquement rendu transparent.</p>
            </template>
          </div>

          <div class="form-group"><label>Label</label><input v-model="form.label" class="form-input" placeholder="ex: Signature officielle" /></div>

          <div v-if="form.type === 'certificate'" class="form-group"><label>Données du certificat</label><textarea v-model="form.certificate_data" rows="3" class="form-textarea"></textarea></div>

          <div v-if="error" class="form-error">{{ error }}</div>

          <div class="form-actions">
            <button type="submit" class="btn-primary" :disabled="saving">{{ saving ? "Création..." : "Créer" }}</button>
            <button type="button" class="btn-secondary" @click="showForm = false">Annuler</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import apiClient from "../../utils/axios";
import SignaturePad from "../../components/SignaturePad.vue";
import CameraSignatureCapture from "../../components/CameraSignatureCapture.vue";

interface Signature {
  id: string;
  label: string;
  type: string;
  is_default: boolean;
  is_active: boolean;
  image_path: string | null;
  user_id: string;
}

const signatures = ref<Signature[]>([]);
const showForm = ref(false);
const saving = ref(false);
const error = ref("");
const graphicMode = ref<"draw" | "upload" | "scan">("draw");
const signaturePad = ref<InstanceType<typeof SignaturePad> | null>(null);
const drawnDataUrl = ref<string | null>(null);
const camDataUrl = ref<string | null>(null);

const previewSigId = ref("");
const previewPaper = ref<HTMLElement | null>(null);
const previewImgEl = ref<HTMLImageElement | null>(null);

const previewSig = computed(() => signatures.value.find(s => s.id === previewSigId.value) || null);
const previewSigUrl = computed(() => (previewSig.value?.image_path ? "/storage/" + previewSig.value.image_path : null));

const form = ref({ type: "graphical", label: "", image: null as File | null, certificate_data: "" });

async function loadSignatures() {
  try {
    const { data } = await apiClient.get("/signatures");
    signatures.value = data.data.data || [];
    if (signatures.value.length && !previewSigId.value) {
      const def = signatures.value.find(s => s.is_default) || signatures.value[0];
      previewSigId.value = def.id;
    }
  } catch (e) { console.error(e); }
}

function openForm() {
  form.value = { type: "graphical", label: "", image: null, certificate_data: "" };
  graphicMode.value = "draw";
  drawnDataUrl.value = null;
  camDataUrl.value = null;
  error.value = "";
  showForm.value = true;
}

function onPadChange(dataUrl: string | null) {
  drawnDataUrl.value = dataUrl;
}

function onFileChange(e: Event) {
  const target = e.target as HTMLInputElement;
  if (target.files?.length) form.value.image = target.files[0];
}

function onCameraCaptured(payload: { dataUrl: string; blob: Blob }) {
  // Convertir le Blob PNG en File pour l'upload standardisé
  const file = new File([payload.blob], "signature-scanned.png", { type: "image/png" });
  form.value.image = file;
  camDataUrl.value = payload.dataUrl;
  error.value = "";
}

async function deleteSignature(sig: Signature) {
  if (!confirm("Supprimer la signature ?")) return;
  await apiClient.delete("/signatures/" + sig.id);
  if (previewSigId.value === sig.id) previewSigId.value = "";
  await loadSignatures();
}

async function createSignature() {
  saving.value = true;
  error.value = "";

  if (form.value.type === "graphical" && graphicMode.value === "draw" && !drawnDataUrl.value) {
    error.value = "Dessinez votre signature avant de l'enregistrer.";
    saving.value = false;
    return;
  }

  try {
    const payload = new FormData();
    payload.append("type", form.value.type);
    if (form.value.label) payload.append("label", form.value.label);

    if (form.value.type === "graphical") {
      if (graphicMode.value === "draw" && drawnDataUrl.value) {
        // Convertir le dataURL PNG en Blob/File
        const blob = dataUrlToBlob(drawnDataUrl.value);
        if (blob) {
          const file = new File([blob], "signature.png", { type: "image/png" });
          payload.append("image", file);
        }
      } else if (form.value.image) {
        payload.append("image", form.value.image);
      }
    }

    if (form.value.certificate_data) payload.append("certificate_data", form.value.certificate_data);

    await apiClient.post("/signatures", payload, { headers: { "Content-Type": "multipart/form-data" } });
    showForm.value = false;
    await loadSignatures();
  } catch (e: any) {
    error.value = e.response?.data?.message || "Erreur lors de l'enregistrement";
  } finally {
    saving.value = false;
  }
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

function selectPreview(sig: Signature) {
  previewSigId.value = sig.id;
  setTimeout(() => replayPreview(), 30);
}

function replayPreview() {
  if (!previewPaper.value || !previewSigUrl.value) return;
  const paper = previewPaper.value;
  paper.classList.remove("sig-preview-anim");
  void paper.offsetWidth;
  paper.classList.add("sig-preview-anim");
}

onMounted(loadSignatures);
</script>

<style scoped>
.sig-preview-row {
  margin-bottom: 1rem;
}
.sig-preview-card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 1rem 1.25rem;
}
.sig-preview-title {
  font-size: 0.9rem;
  font-weight: 600;
  color: #0f172a;
  margin: 0 0 0.15rem;
}
.sig-preview-sub {
  font-size: 0.78rem;
  color: #64748b;
  margin: 0 0 0.75rem;
}
.sig-preview-paper {
  position: relative;
  width: 100%;
  height: 90px;
  background: linear-gradient(135deg, #f8fafc 25%, transparent 25%), linear-gradient(225deg, #f8fafc 25%, transparent 25%);
  background-size: 20px 20px;
  background-position: 0 0, 10px 10px;
  border: 1px dashed #cbd5e1;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}
.sig-preview-img {
  max-height: 60px;
  max-width: 60%;
  opacity: 0;
}
.sig-preview-paper.sig-preview-anim .sig-preview-img {
  animation: sig-preview-reveal 1.8s ease forwards;
}
.sig-preview-empty {
  color: #94a3b8;
  font-size: 0.8rem;
}
.sig-thumb {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  margin-right: 0.5rem;
  vertical-align: middle;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #f8fafc;
  cursor: pointer;
  transition: all 0.12s;
  overflow: hidden;
}
.sig-thumb:hover {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
}
.sig-thumb img {
  max-width: 100%;
  max-height: 100%;
}
.sig-thumb svg {
  color: #94a3b8;
}
.sig-row-active td {
  background: #eff6ff;
}
.mt-2 {
  margin-top: 0.5rem;
}

@keyframes sig-preview-reveal {
  0% { opacity: 0; transform: scale(0.7) rotate(-8deg); }
  40% { opacity: 0.9; transform: scale(1.08) rotate(2deg); }
  60% { opacity: 1; transform: scale(0.97) rotate(0deg); }
  100% { opacity: 1; transform: scale(1) rotate(0deg); }
}
</style>


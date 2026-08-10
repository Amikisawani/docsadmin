<template>
  <!-- Mode campagne : signature d'une campagne de publipostage -->
  <div v-if="isCampaign && campaign" class="dir-review">
    <div class="dir-review-header">
      <div>
        <button class="dir-back" @click="router.push('/director')">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
          Retour à la boîte
        </button>
        <h1 class="dir-review-title">{{ campaign.title || 'Campagne de publipostage' }}</h1>
        <p class="dir-review-ref">Source : {{ campaign.document?.subject || '—' }}</p>
      </div>
      <div class="dir-review-actions">
        <span class="dir-status-badge pending">{{ campaign.status_label || campaign.status }}</span>
      </div>
    </div>

    <div class="dir-review-grid">
      <div class="dir-review-main">
        <div class="dir-card">
          <h3 class="dir-card-title">Destinataires ({{ campaign.recipients?.length || campaign.total_recipients || 0 }})</h3>
          <div class="table-wrapper">
            <table class="data-table">
<thead>
                <tr>
                  <th>Destinataire</th>
                  <th>Statut</th>
                  <th class="text-right">Action</th>
                </tr>
              </thead>
              <tbody>
<tr v-for="r in campaign.recipients || []" :key="r.name || r.id">
                  <td>{{ r.destinataire || r.name }}</td>
                  <td>
                    <span class="badge" :class="r.output_path ? 'badge-green' : 'badge-gray'">
                      {{ r.status && r.status !== 'pending' ? r.status : (r.output_path ? 'Généré' : 'En attente') }}
                    </span>
                  </td>
                  <td class="text-right">
                    <button v-if="r.output_path" @click="campaignPreviewUrl = '/storage/' + r.output_path" class="link-action">Aperçu</button>
                  </td>
                </tr>
<tr v-if="!campaign.recipients?.length">
                  <td colspan="3" class="empty-state">Aucun destinataire chargé.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

<div v-if="campaign.zip_path" class="dir-card">
          <h3 class="dir-card-title">Fichier généré</h3>
          <a class="dir-btn dir-btn-secondary" :href="'/storage/' + campaign.zip_path" download>
            Télécharger le ZIP{{ campaign.signed_at ? ' signé' : '' }}
          </a>
        </div>

<div v-if="campaignPreviewUrl" class="dir-card">
          <h3 class="dir-card-title">Aperçu du document</h3>
          <div class="dir-preview-toolbar">
            <a :href="campaignPreviewUrl" download class="dir-btn dir-btn-secondary">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
              Télécharger
            </a>
          </div>
          <div class="dir-preview-wrap">
            <iframe v-if="campaignPreviewUrl.toLowerCase().endsWith('.pdf')" :src="campaignPreviewUrl" class="dir-preview-iframe"></iframe>
            <div v-else class="dir-preview-fallback">
              <p class="dir-preview-fallback-empty">
                Ce document est un fichier Word. Cliquez sur « Télécharger » pour l'examiner.
              </p>
            </div>
          </div>
        </div>
      </div>

      <div class="dir-review-side">
        <div class="dir-card">
          <h3 class="dir-card-title">Informations</h3>
          <dl class="dir-info-list">
            <div class="dir-info-row"><dt>Créateur</dt><dd>{{ campaign.creator?.name || '—' }}</dd></div>
            <div class="dir-info-row"><dt>Format</dt><dd>{{ campaign.format || 'pdf' }}</dd></div>
            <div class="dir-info-row"><dt>Destinataires</dt><dd>{{ campaign.total_recipients || 0 }}</dd></div>
            <div class="dir-info-row"><dt>Générés</dt><dd>{{ campaign.generated_count || 0 }}</dd></div>
            <div class="dir-info-row"><dt>Envoyé le</dt><dd>{{ formatDate(campaign.submitted_for_signature_at) }}</dd></div>
          </dl>
        </div>

        <div class="dir-card dir-signed-note" v-if="campaign.status === 'signed'">
          ✅ Cette campagne a déjà été signée.
        </div>

        <div class="dir-card dir-decision" v-else>
          <h3 class="dir-card-title">Signature de la campagne</h3>
          <div class="dir-decision-actions">
            <label class="dir-reject-label">Votre signature</label>
            <select v-model="selectedSignatureId" class="dir-textarea">
              <option value="" disabled>Sélectionnez une signature</option>
              <option v-for="s in signatures" :key="s.id" :value="s.id">
                {{ s.label || ('Signature ' + (s.type || '')) }}
              </option>
            </select>
            <button class="dir-btn dir-btn-primary" :disabled="signing || !selectedSignatureId" @click="signCampaign">
              {{ signing ? 'Signature en cours...' : 'Signer la campagne' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Mode document : examen d'un document -->
  <div v-else-if="document" class="dir-review">
    <div class="dir-review-header">
      <div>
        <button class="dir-back" @click="router.push('/director')">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
          Retour à la boîte
        </button>
        <h1 class="dir-review-title">{{ document.subject }}</h1>
        <p class="dir-review-ref">N° {{ document.document_number }} · {{ document.document_type }}</p>
      </div>
      <div class="dir-review-actions">
        <span class="dir-status-badge" :class="statusClass(document.status)">{{ statusLabel(document.status) }}</span>
      </div>
    </div>

    <div class="dir-review-grid">
      <div class="dir-review-main">
<div class="dir-card">
          <h3 class="dir-card-title">
            Aperçu du document
            <span v-if="document.signed_pdf_path" class="dir-preview-toggle">
              <button :class="{ active: previewTab === 'before' }" @click="previewTab = 'before'">Avant</button>
              <button :class="{ active: previewTab === 'after' }" @click="previewTab = 'after'">Après</button>
            </span>
          </h3>
<div class="dir-preview-toolbar" v-if="document.source_file_path">
            <a :href="'/storage/' + document.source_file_path" download class="dir-btn dir-btn-secondary">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
              Télécharger le fichier source
            </a>
          </div>
          <div class="dir-preview-wrap">
            <iframe
              v-if="isPdfPreview"
              :src="previewFileUrl ?? undefined"
              class="dir-preview-iframe"
            ></iframe>
            <div v-else class="dir-preview-fallback">
              <p class="dir-preview-fallback-title">{{ document.subject }}</p>
              <div v-if="document.content" class="dir-preview-fallback-content">{{ document.content }}</div>
              <div v-else-if="document.source_file_path" class="dir-preview-fallback-content">
                <p class="dir-preview-fallback-empty">
                  Ce document est un fichier Word. Cliquez sur « Télécharger le fichier source » pour l'examiner.
                </p>
              </div>
              <p v-else class="dir-preview-fallback-empty">Aucun aperçu disponible.</p>
            </div>
          </div>
        </div>

        <div v-if="document.content" class="dir-card">
          <h3 class="dir-card-title">Contenu</h3>
          <p class="dir-content">{{ document.content }}</p>
        </div>

        <div class="dir-card">
          <h3 class="dir-card-title">Historique</h3>
          <div class="dir-timeline">
            <div v-for="h in document.histories" :key="h.id" class="dir-timeline-item">
              <div class="dir-timeline-dot"></div>
              <div>
                <p class="dir-timeline-text">{{ historyLabel(h.action) }}</p>
                <p class="dir-timeline-meta">{{ h.user?.name || 'Système' }} · {{ formatDateTime(h.created_at) }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="dir-review-side">
        <div class="dir-card">
          <h3 class="dir-card-title">Informations</h3>
          <dl class="dir-info-list">
            <div class="dir-info-row"><dt>Expéditeur</dt><dd>{{ document.author?.name }}</dd></div>
            <div class="dir-info-row"><dt>Service</dt><dd>{{ document.department?.name || '—' }}</dd></div>
            <div class="dir-info-row"><dt>Type</dt><dd>{{ document.document_type }}</dd></div>
            <div class="dir-info-row"><dt>Priorité</dt><dd><span class="dir-priority" :class="priorityClass(document.priority)">{{ priorityLabel(document.priority) }}</span></dd></div>
            <div class="dir-info-row"><dt>Confidentialité</dt><dd>{{ document.confidentiality }}</dd></div>
            <div class="dir-info-row"><dt>Date</dt><dd>{{ formatDate(document.document_date) }}</dd></div>
            <div v-if="document.deadline" class="dir-info-row"><dt>Délai</dt><dd class="dir-deadline">{{ formatDate(document.deadline) }}</dd></div>
          </dl>
        </div>

        <div class="dir-card dir-decision">
          <h3 class="dir-card-title">Décision</h3>

          <div v-if="rejecting" class="dir-reject-form">
            <label class="dir-reject-label">Motif du rejet (obligatoire)</label>
            <textarea v-model="rejectReason" class="dir-textarea" rows="4" placeholder="Précisez le motif du rejet..."></textarea>
            <div class="dir-reject-actions">
              <button class="dir-btn dir-btn-danger" :disabled="!rejectReason.trim() || rejectingSubmitting" @click="confirmReject">
                {{ rejectingSubmitting ? 'Rejet...' : 'Confirmer le rejet' }}
              </button>
              <button class="dir-btn dir-btn-secondary" @click="rejecting = false; rejectReason = ''">Annuler</button>
            </div>
          </div>

          <div v-else class="dir-decision-actions">
            <button
              v-if="document.status !== 'signed'"
              class="dir-btn dir-btn-primary"
              :disabled="signing"
              @click="openSignTool"
            >
              {{ signing ? 'Signature...' : 'Signer le document' }}
            </button>
            <button
              v-if="document.status !== 'signed'"
              class="dir-btn dir-btn-danger-outline"
              @click="rejecting = true"
            >
              Rejeter
            </button>
            <a
              v-if="document.signed_pdf_path"
              :href="'/storage/' + document.signed_pdf_path"
              target="_blank"
              class="dir-btn dir-btn-secondary"
            >
              Télécharger le PDF signé
            </a>
          </div>
        </div>
      </div>
    </div>

    <SignatureTool
      v-if="showSignatureTool"
      :document="document"
      @close="showSignatureTool = false"
      @signed="onSigned"
    />
  </div>

  <div v-else-if="loadError" class="dir-error-page">
    <h3>Document introuvable</h3>
    <p>{{ loadError }}</p>
    <button class="dir-btn dir-btn-primary" @click="router.push('/director')">Retour à la boîte</button>
  </div>

  <div v-else class="dir-loading-page">Chargement...</div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import apiClient from "../../utils/axios";
import type { Document, MailMergeBatch, Signature } from "../../types";
import SignatureTool from "../../components/SignatureTool.vue";

const route = useRoute();
const router = useRouter();
const document = ref<Document | null>(null);
const campaign = ref<MailMergeBatch | null>(null);
const loadError = ref("");
const showSignatureTool = ref(false);
const signing = ref(false);
const rejecting = ref(false);
const rejectReason = ref("");
const rejectingSubmitting = ref(false);
const signatures = ref<Signature[]>([]);
const selectedSignatureId = ref("");
const campaignPreviewUrl = ref<string | null>(null);
const previewTab = ref<"before" | "after">("before");

const isPdfPreview = computed(() => {
  if (!previewFileUrl.value) return false;
  return previewFileUrl.value.toLowerCase().endsWith(".pdf");
});

const previewFileUrl = computed(() => {
  if (!document.value) return null;
  if (previewTab.value === "after" && document.value.signed_pdf_path) {
    return "/storage/" + document.value.signed_pdf_path;
  }
  // N'affiche dans l'iframe que les fichiers PDF (les fichiers Word ne sont
  // pas nativement rendus par les navigateurs et déclenchent un téléchargement).
  if (document.value.source_file_path && document.value.source_file_path.toLowerCase().endsWith(".pdf")) {
    return "/storage/" + document.value.source_file_path;
  }
  return null;
});

const isCampaign = computed(() => route.name === "DirectorCampaignReview");

async function loadCurrent() {
  loadError.value = "";
  try {
if (isCampaign.value) {
      const { data } = await apiClient.get(`/mail-merge/${route.params.id}`);
      campaign.value = data.data;
      await loadSignatures();
      // Pré-afficher le premier document généré (aperçu avant signature).
      // Seuls les PDF s'affichent dans l'iframe ; les fichiers Word (docx/doc/txt)
      // sont proposés au téléchargement pour éviter le téléchargement automatique.
      const firstRecipient = (campaign.value?.recipients || []).find((r: any) => r.output_path);
      const firstOutput = firstRecipient?.output_path;
      if (firstOutput) {
        campaignPreviewUrl.value = '/storage/' + firstOutput;
      } else if (campaign.value?.zip_path) {
        campaignPreviewUrl.value = '/storage/' + campaign.value.zip_path;
      } else {
        campaignPreviewUrl.value = null;
      }
    } else {
      const { data } = await apiClient.get(`/documents/${route.params.id}`);
      document.value = data.data;
    }
  } catch (e: any) {
    loadError.value = e?.response?.data?.message || "Impossible de charger cet élément.";
  }
}

async function loadSignatures() {
  try {
    const { data } = await apiClient.get("/signatures");
    const list = (data.data.data || data.data || []).filter((s: any) => s.is_active !== false);
    signatures.value = list;
    selectedSignatureId.value = (list.find((s: any) => s.is_default) || list[0])?.id || "";
  } catch (e) {
    console.error("Erreur de chargement des signatures", e);
  }
}

async function signCampaign() {
  if (!selectedSignatureId.value) {
    alert("Veuillez choisir une signature.");
    return;
  }
  signing.value = true;
  try {
    await apiClient.post(`/mail-merge/${campaign.value?.id}/sign-campaign`, {
      signature_id: selectedSignatureId.value,
      position: { x: 50, y: 50 },
    });
    alert("Campagne signée avec succès !");
    await loadCurrent();
  } catch (e: any) {
    alert(e?.response?.data?.message || "Erreur lors de la signature de la campagne");
  } finally {
    signing.value = false;
  }
}

function openSignTool() {
  if (document.value?.status === 'signed') return;
  showSignatureTool.value = true;
}

async function onSigned() {
  showSignatureTool.value = false;
  await loadCurrent();
}

async function confirmReject() {
  if (!rejectReason.value.trim()) return;
  rejectingSubmitting.value = true;
  try {
    await apiClient.post(`/documents/${document.value?.id}/reject`, {
      rejection_reason: rejectReason.value.trim(),
    });
    rejecting.value = false;
    rejectReason.value = "";
    await loadCurrent();
  } catch (e: any) {
    alert(e?.response?.data?.message || "Erreur lors du rejet");
  } finally {
    rejectingSubmitting.value = false;
  }
}

function statusLabel(s: string): string {
  return ({ draft: "Brouillon", pending: "En attente", signed: "Signé", rejected: "Rejeté", approved: "Approuvé", archived: "Archivé" } as Record<string, string>)[s] || s;
}
function statusClass(s: string): string {
  return ({ pending: "pending", signed: "signed", rejected: "rejected", draft: "draft" } as Record<string, string>)[s] || "draft";
}
function historyLabel(a: string): string {
  return ({
    signed: "Document signé",
    submitted_for_signature: "Envoyé à la signature",
    recalled: "Rappel de signature",
    workflow_rejected: "Rejeté",
    updated: "Document mis à jour",
    created: "Document créé",
  } as Record<string, string>)[a] || a;
}
function priorityLabel(p?: string): string {
  return ({ normale: "Normale", haute: "Haute", urgente: "Urgente" } as Record<string, string>)[p || "normale"] || "Normale";
}
function priorityClass(p?: string): string {
  return p === "urgente" ? "urgent" : p === "haute" ? "high" : "normal";
}
function formatDate(d?: string | null): string {
  if (!d) return "—";
  return new Date(d).toLocaleDateString("fr-FR", { day: "numeric", month: "short", year: "numeric" });
}
function formatDateTime(d?: string | null): string {
  if (!d) return "—";
  return new Date(d).toLocaleDateString("fr-FR", { day: "numeric", month: "short", year: "numeric", hour: "2-digit", minute: "2-digit" });
}

onMounted(loadCurrent);
</script>

<style scoped>
.link-action {
  background: none;
  border: none;
  color: #2563eb;
  font-size: 0.82rem;
  font-weight: 600;
  cursor: pointer;
  padding: 0;
}
.link-action:hover {
  text-decoration: underline;
}
.dir-review { max-width: 1280px; margin: 0 auto; font-family: system-ui, sans-serif; }
.dir-review-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; margin-bottom: 1.25rem; }
.dir-back { display: inline-flex; align-items: center; gap: 0.4rem; background: none; border: none; color: #1d4ed8; font-size: 0.82rem; font-weight: 600; cursor: pointer; padding: 0.3rem 0; margin-bottom: 0.5rem; }
.dir-back:hover { text-decoration: underline; }
.dir-review-title { font-size: 1.35rem; font-weight: 700; color: #0f172a; margin: 0; }
.dir-review-ref { font-size: 0.82rem; color: #64748b; margin: 0.2rem 0 0; }
.dir-status-badge { font-size: 0.75rem; font-weight: 700; padding: 0.35rem 0.8rem; border-radius: 999px; }
.dir-status-badge.pending { background: #fef3c7; color: #b45309; }
.dir-status-badge.signed { background: #dcfce7; color: #15803d; }
.dir-status-badge.rejected { background: #fee2e2; color: #b91c1c; }
.dir-status-badge.draft { background: #f1f5f9; color: #475569; }

.dir-review-grid { display: grid; grid-template-columns: 1.6fr 1fr; gap: 1rem; align-items: start; }
@media (max-width: 1000px) { .dir-review-grid { grid-template-columns: 1fr; } }
.dir-review-main { display: flex; flex-direction: column; gap: 1rem; }
.dir-review-side { display: flex; flex-direction: column; gap: 1rem; }
.dir-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 1.25rem; }
.dir-card-title { font-size: 0.95rem; font-weight: 700; color: #0f172a; margin: 0 0 0.9rem; }

.dir-preview-toolbar {
  display: flex;
  justify-content: flex-end;
  margin-bottom: 0.6rem;
}
.dir-preview-toggle {
  display: inline-flex;
  gap: 0.25rem;
  margin-left: 0.6rem;
  border: 1px solid #e2e8f0;
  border-radius: 7px;
  overflow: hidden;
  vertical-align: middle;
}
.dir-preview-toggle button {
  border: none;
  background: #f8fafc;
  color: #64748b;
  font-size: 0.72rem;
  font-weight: 700;
  padding: 0.25rem 0.6rem;
  cursor: pointer;
}
.dir-preview-toggle button.active {
  background: #2563eb;
  color: #fff;
}
.dir-preview-wrap {
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  overflow: hidden;
  background: #fff;
  min-height: 420px;
}
.dir-preview-iframe {
  width: 100%;
  height: 520px;
  border: none;
  display: block;
}
.dir-preview-fallback {
  padding: 2rem;
}
.dir-preview-fallback-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 0.25rem;
}
.dir-preview-fallback-content {
  font-size: 0.9rem;
  color: #334155;
  line-height: 1.6;
  white-space: pre-wrap;
  margin-top: 1rem;
}
.dir-preview-fallback-empty {
  color: #94a3b8;
  font-size: 0.9rem;
}

.dir-content { font-size: 0.9rem; color: #334155; line-height: 1.6; white-space: pre-wrap; margin: 0; }

.dir-timeline { display: flex; flex-direction: column; }
.dir-timeline-item { display: flex; gap: 0.7rem; padding: 0.45rem 0; border-bottom: 1px solid #f1f5f9; }
.dir-timeline-item:last-child { border-bottom: none; }
.dir-timeline-dot { width: 10px; height: 10px; border-radius: 50%; background: #3b82f6; margin-top: 5px; flex-shrink: 0; }
.dir-timeline-text { font-size: 0.83rem; color: #0f172a; margin: 0; }
.dir-timeline-meta { font-size: 0.72rem; color: #94a3b8; margin: 0.1rem 0 0; }

.dir-info-list { margin: 0; }
.dir-info-row { display: flex; justify-content: space-between; gap: 0.5rem; padding: 0.45rem 0; border-bottom: 1px solid #f1f5f9; font-size: 0.82rem; }
.dir-info-row:last-child { border-bottom: none; }
.dir-info-row dt { color: #64748b; font-weight: 500; }
.dir-info-row dd { color: #0f172a; font-weight: 600; text-align: right; margin: 0; }
.dir-deadline { color: #b45309 !important; }

.dir-priority { font-size: 0.7rem; font-weight: 700; padding: 0.2rem 0.55rem; border-radius: 999px; }
.dir-priority.normal { background: #f1f5f9; color: #475569; }
.dir-priority.high { background: #fef3c7; color: #b45309; }
.dir-priority.urgent { background: #fee2e2; color: #b91c1c; }

.dir-decision-actions { display: flex; flex-direction: column; gap: 0.6rem; }
.dir-signed-note { font-size: 0.9rem; font-weight: 600; color: #15803d; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 9px; padding: 0.7rem 0.9rem; }
.dir-btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem; font-size: 0.82rem; font-weight: 700; padding: 0.6rem 0.9rem; border-radius: 9px; border: none; cursor: pointer; text-decoration: none; transition: all 0.12s; }
.dir-btn-primary { background: linear-gradient(135deg, #2563eb, #1d4ed8); color: #fff; }
.dir-btn-primary:hover { background: linear-gradient(135deg, #1d4ed8, #1e40af); }
.dir-btn-danger { background: #dc2626; color: #fff; }
.dir-btn-danger:hover { background: #b91c1c; }
.dir-btn-danger-outline { background: #fff; color: #dc2626; border: 1px solid #fecaca; }
.dir-btn-danger-outline:hover { background: #fef2f2; }
.dir-btn-secondary { background: #f1f5f9; color: #334155; border: 1px solid #e2e8f0; }
.dir-btn-secondary:hover { background: #e2e8f0; }
.dir-btn:disabled { opacity: 0.5; cursor: not-allowed; }

.dir-reject-label { display: block; font-size: 0.8rem; font-weight: 600; color: #334155; margin-bottom: 0.4rem; }
.dir-textarea { width: 100%; padding: 0.6rem 0.75rem; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.85rem; font-family: inherit; resize: vertical; box-sizing: border-box; }
.dir-textarea:focus { outline: none; border-color: #2563eb; }
.dir-reject-actions { display: flex; gap: 0.5rem; margin-top: 0.6rem; }

.dir-loading-page, .dir-error-page { padding: 3rem; text-align: center; color: #64748b; }
.dir-error-page h3 { color: #0f172a; margin: 0 0 0.5rem; }

/* Campagne */
.badge { display: inline-flex; align-items: center; font-size: 0.72rem; font-weight: 700; padding: 0.2rem 0.55rem; border-radius: 999px; }
.badge-green { background: #dcfce7; color: #15803d; }
.badge-gray { background: #f1f5f9; color: #475569; }
.data-table { width: 100%; border-collapse: collapse; font-size: 0.82rem; }
.data-table th { text-align: left; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; color: #94a3b8; padding: 0.5rem; border-bottom: 1px solid #e2e8f0; }
.data-table td { padding: 0.5rem; border-bottom: 1px solid #f1f5f9; color: #334155; }
.empty-state { text-align: center; color: #94a3b8; padding: 1rem; }
.table-wrapper { overflow-x: auto; }
</style>

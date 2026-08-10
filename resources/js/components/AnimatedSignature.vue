// ============================================================
// AnimatedSignature.vue
//
// Affiche une image de signature (PNG) sur un bout de document
// ("paper"), avec une animation "stamp" :
//   runAnimation() -> le stamp descend, dessine un halo, puis la
//   signature apparaît en fondu avec un trait de stylo animé.
//
// Le composant est utilisé par un outil de placement (drag) :
//   - props.placement { x, y } en % de la zone de document
//   - draggable -> l'utilisateur peut déplacer la signature
// ============================================================
<template>
  <div
    class="anim-sig"
    :class="{ 'draggable': draggable, 'is-placed': isPlaced, 'is-animating': isAnimating }"
    :style="style"
    @mousedown.stop="onDragStart"
    @touchstart.stop.prevent="onDragStart"
  >
    <!-- Halo / cachet animé -->
    <div v-show="isAnimating" class="anim-sig-halo"></div>

    <!-- Signature -->
    <img v-if="signatureUrl" :src="signatureUrl" alt="Signature" class="anim-sig-img" draggable="false" />
    <div v-else class="anim-sig-placeholder-text">Signature</div>

    <!-- Petit label sous la signature -->
    <div v-if="showLabel" class="anim-sig-label">{{ label }}</div>

    <!-- Bouton de suppression (quand placé) -->
    <button v-if="draggable && isPlaced" class="anim-sig-remove" @mousedown.stop @click.stop="$emit('remove')" title="Retirer la signature">
      <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from "vue";

const props = withDefaults(defineProps<{
  signatureUrl?: string | null;
  placement?: { x: number; y: number } | null;
  label?: string;
  showLabel?: boolean;
  draggable?: boolean;
  /** Taille relative de la signature (0.05..0.35) */
  scale?: number;
  /** Durée de l'animation d'apparition (ms) */
  animationDuration?: number;
}>(), {
  signatureUrl: null,
  placement: null,
  label: "",
  showLabel: true,
  draggable: false,
  scale: 0.18,
  animationDuration: 2000,
});

const emit = defineEmits<{
  (e: "update:placement", placement: { x: number; y: number } | null): void;
  (e: "placed"): void;
  (e: "remove"): void;
}>();

const el = ref<HTMLElement | null>(null);
const isAnimating = ref(false);
const isPlaced = ref(false);
const local = ref(props.placement ? { ...props.placement } : null);

watch(
  () => props.placement,
  (v) => {
    if (v) local.value = { ...v };
  }
);

// Les coordonnées sont exprimées en % du document (0 à 100)
const style = computed(() => {
  const x = local.value?.x ?? 50;
  const y = local.value?.y ?? 80;
  return {
    left: x + "%",
    top: y + "%",
    width: props.scale * 100 + "%",
    "--anim-duration": props.animationDuration + "ms",
  };
});

function onDragStart(e: MouseEvent | TouchEvent) {
  if (!props.draggable) return;
  if (e.target instanceof SVGElement) return;
  e.preventDefault();

  const moveHandler = (ev: MouseEvent | TouchEvent) => {
    const paper = el.value?.parentElement;
    if (!paper) return;
    const rect = paper.getBoundingClientRect();
    const pt = "clientX" in ev ? { x: ev.clientX, y: ev.clientY } : { x: ev.touches[0].clientX, y: ev.touches[0].clientY };
    const x = ((pt.x - rect.left) / rect.width) * 100;
    const y = ((pt.y - rect.top) / rect.height) * 100;
    local.value = { x: clamp(x, 2, 98), y: clamp(y, 2, 98) };
  };
  const upHandler = () => {
    document.removeEventListener("mousemove", moveHandler);
    document.removeEventListener("mouseup", upHandler);
    document.removeEventListener("touchmove", moveHandler);
    document.removeEventListener("touchend", upHandler);
    if (local.value) emit("update:placement", { ...local.value });
  };
  document.addEventListener("mousemove", moveHandler);
  document.addEventListener("mouseup", upHandler);
  document.addEventListener("touchmove", moveHandler, { passive: true });
  document.addEventListener("touchend", upHandler);
}

function clamp(v: number, min: number, max: number) {
  return Math.min(max, Math.max(min, v));
}

function runAnimation() {
  isAnimating.value = true;
  emit("placed");
  setTimeout(() => {
    isAnimating.value = false;
    isPlaced.value = true;
  }, props.animationDuration);
}

defineExpose({ runAnimation });
</script>

<style scoped>
.anim-sig {
  position: absolute;
  transform: translate(-50%, -60%);
  z-index: 30;
  user-select: none;
  -webkit-user-select: none;
  cursor: default;
}
.anim-sig.draggable {
  cursor: grab;
}
.anim-sig.draggable:active {
  cursor: grabbing;
}
.anim-sig-img {
  display: block;
  width: 100%;
  height: auto;
  opacity: 0;
}
.anim-sig.is-animating .anim-sig-img {
  animation: sig-reveal var(--anim-duration) ease forwards;
}
.anim-sig.is-placed .anim-sig-img {
  opacity: 1;
}

/* Halo / cachet qui se déplie */
.anim-sig-halo {
  position: absolute;
  top: 50%;
  left: 50%;
  width: 260%;
  aspect-ratio: 1;
  transform: translate(-50%, -55%) scale(0);
  border: 2px solid rgba(37, 99, 235, 0.5);
  border-radius: 50%;
  opacity: 0;
  animation: halo-pulse var(--anim-duration) ease forwards;
  pointer-events: none;
}

/* Trait de stylo qui trace autour de la signature */
.anim-sig.is-animating::before {
  content: "";
  position: absolute;
  inset: -6% -14%;
  border: 2px solid rgba(37, 99, 235, 0.75);
  border-radius: 6px;
  transform: scaleX(0);
  transform-origin: left center;
  animation: pen-stroke calc(var(--anim-duration) * 0.7) ease forwards;
  pointer-events: none;
}

.anim-sig-label {
  text-align: center;
  font-size: 0.62rem;
  font-weight: 600;
  color: #2563eb;
  margin-top: 4px;
  text-shadow: 0 0 4px #fff;
}

.anim-sig-placeholder-text {
  font-size: 1.2rem;
  font-style: italic;
  color: #2563eb;
  white-space: nowrap;
}

.anim-sig-remove {
  position: absolute;
  top: -10px;
  right: -10px;
  width: 22px;
  height: 22px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: #dc2626;
  color: #fff;
  border: none;
  cursor: pointer;
  box-shadow: 0 2px 6px rgba(0,0,0,0.2);
  opacity: 0;
  transition: opacity 0.15s;
}
.anim-sig.draggable:hover .anim-sig-remove {
  opacity: 1;
}

@keyframes sig-reveal {
  0% { opacity: 0; transform: scale(0.75) rotate(-8deg); }
  40% { opacity: 0.9; transform: scale(1.06) rotate(2deg); }
  60% { opacity: 1; transform: scale(0.97) rotate(0deg); }
  100% { opacity: 1; transform: scale(1) rotate(0deg); }
}

@keyframes halo-pulse {
  0% { transform: translate(-50%, -55%) scale(0); opacity: 0; }
  25% { opacity: 0.7; }
  100% { transform: translate(-50%, -55%) scale(1); opacity: 0; }
}

@keyframes pen-stroke {
  0% { transform: scaleX(0); opacity: 0.6; }
  100% { transform: scaleX(1); opacity: 0; }
}
</style>


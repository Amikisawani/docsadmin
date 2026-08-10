<template>
  <div class="sig-pad-wrap">
    <canvas
      ref="canvas"
      class="sig-pad-canvas"
      @mousedown="startDraw"
      @touchstart="startDraw"
      @mousemove="draw"
      @touchmove="draw"
      @mouseup="endDraw"
      @touchend="endDraw"
      @mouseleave="endDraw"
    ></canvas>
    <div class="sig-pad-actions">
      <button type="button" class="sig-pad-btn" @click="clear">Effacer</button>
      <span class="sig-pad-hint">Dessinez votre signature ci-dessus</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch } from "vue";

const props = withDefaults(defineProps<{
  modelValue?: string | null;
  width?: number;
  height?: number;
  strokeColor?: string;
  strokeWidth?: number;
}>(), {
  modelValue: null,
  width: 400,
  height: 160,
  strokeColor: "#0f172a",
  strokeWidth: 2.5,
});

const emit = defineEmits<{
  (e: "update:modelValue", value: string): void;
  (e: "change", value: string): void;
}>();

const canvas = ref<HTMLCanvasElement | null>(null);
let ctx: CanvasRenderingContext2D | null = null;
let drawing = false;
let lastX = 0;
let lastY = 0;

function initCanvas() {
  if (!canvas.value) return;
  const dpr = window.devicePixelRatio || 1;
  canvas.value.width = props.width * dpr;
  canvas.value.height = props.height * dpr;
  canvas.value.style.width = props.width + "px";
  canvas.value.style.height = props.height + "px";
  ctx = canvas.value.getContext("2d");
  if (!ctx) return;
  ctx.scale(dpr, dpr);
  ctx.lineCap = "round";
  ctx.lineJoin = "round";
  ctx.strokeStyle = props.strokeColor;
  ctx.lineWidth = props.strokeWidth;
}

function getPos(e: MouseEvent | TouchEvent): { x: number; y: number } {
  if (!canvas.value) return { x: 0, y: 0 };
  const rect = canvas.value.getBoundingClientRect();
  const clientX = "touches" in e ? e.touches[0].clientX : e.clientX;
  const clientY = "touches" in e ? e.touches[0].clientY : e.clientY;
  return {
    x: clientX - rect.left,
    y: clientY - rect.top,
  };
}

function startDraw(e: MouseEvent | TouchEvent) {
  e.preventDefault();
  drawing = true;
  const pos = getPos(e);
  lastX = pos.x;
  lastY = pos.y;
}

function draw(e: MouseEvent | TouchEvent) {
  if (!drawing || !ctx) return;
  e.preventDefault();
  const pos = getPos(e);
  ctx.beginPath();
  ctx.moveTo(lastX, lastY);
  ctx.lineTo(pos.x, pos.y);
  ctx.stroke();
  lastX = pos.x;
  lastY = pos.y;
}

function endDraw() {
  if (drawing) {
    drawing = false;
    emitChange();
  }
}

function clear() {
  if (!canvas.value || !ctx) return;
  ctx.clearRect(0, 0, props.width, props.height);
  emit("update:modelValue", "");
  emit("change", "");
}

function emitChange() {
  if (!canvas.value) return;
  const dataUrl = canvas.value.toDataURL("image/png");
  emit("update:modelValue", dataUrl);
  emit("change", dataUrl);
}

watch(() => props.modelValue, (val) => {
  const context = ctx;
  if (!val || !canvas.value || !context) return;
  const img = new Image();
  img.onload = () => {
    context.clearRect(0, 0, props.width, props.height);
    context.drawImage(img, 0, 0, props.width, props.height);
  };
  img.src = val;
});

onMounted(() => {
  initCanvas();
});

onUnmounted(() => {
  drawing = false;
});

defineExpose({ clear, getDataUrl: () => canvas.value?.toDataURL("image/png") ?? "" });
</script>

<style scoped>
.sig-pad-wrap {
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: #fff;
  overflow: hidden;
}
.sig-pad-canvas {
  display: block;
  width: 100%;
  max-width: 100%;
  height: auto;
  cursor: crosshair;
  touch-action: none;
  background: #fff;
}
.sig-pad-actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.6rem 0.9rem;
  background: #f8fafc;
  border-top: 1px solid #e2e8f0;
}
.sig-pad-btn {
  font-size: 0.78rem;
  font-weight: 600;
  color: #dc2626;
  background: none;
  border: none;
  cursor: pointer;
  padding: 0.25rem 0.5rem;
  border-radius: 6px;
}
.sig-pad-btn:hover {
  background: #fef2f2;
}
.sig-pad-hint {
  font-size: 0.72rem;
  color: #94a3b8;
}
</style>

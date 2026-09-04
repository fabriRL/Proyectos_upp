<script setup>
import { useToast } from '@/composables/useToast.js'
const { toastState, cerrarToast } = useToast()

const estilos = {
  info:    { border: 'rgba(77,179,240,0.35)',  bg: 'rgba(77,179,240,0.10)',  color: '#55b8ef', icon: 'ti-info-circle' },
  success: { border: 'rgba(0,201,167,0.35)',   bg: 'rgba(0,201,167,0.10)',   color: '#00c9a7', icon: 'ti-circle-check' },
  warning: { border: 'rgba(251,191,36,0.35)',  bg: 'rgba(251,191,36,0.10)',  color: '#fbbf24', icon: 'ti-alert-triangle' },
  error:   { border: 'rgba(248,113,113,0.35)', bg: 'rgba(248,113,113,0.10)', color: '#f87171', icon: 'ti-alert-circle' },
}
</script>

<template>
  <Teleport to="body">
    <Transition name="toast-fade">
      <div v-if="toastState.visible" class="toast-wrap">
        <div
          class="toast-card"
          :style="{ borderColor: estilos[toastState.type].border }"
        >
          <div class="toast-icon" :style="{ background: estilos[toastState.type].bg, color: estilos[toastState.type].color }">
            <i :class="`ti ${estilos[toastState.type].icon}`"></i>
          </div>
          <span class="toast-msg">{{ toastState.message }}</span>
          <button class="toast-close" @click="cerrarToast">
            <i class="ti ti-x"></i>
          </button>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.toast-wrap {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 999999;
}
.toast-card {
  display: flex;
  align-items: center;
  gap: 10px;
  max-width: 380px;
  padding: 12px 14px;
  border: 1px solid;
  border-radius: 10px;
  background: linear-gradient(145deg, #0d2233, #0b1c2b);
  box-shadow: 0 15px 40px rgba(0, 0, 0, .4);
  color: #d4e4f0;
  font-size: .78rem;
}
.toast-icon {
  width: 28px;
  height: 28px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 7px;
  font-size: 15px;
}
.toast-msg { flex: 1; line-height: 1.4; }
.toast-close {
  background: none;
  border: none;
  color: #7893a7;
  cursor: pointer;
  font-size: 14px;
  flex-shrink: 0;
}
.toast-close:hover { color: #e0edf5; }

.toast-fade-enter-active, .toast-fade-leave-active { transition: all .25s ease; }
.toast-fade-enter-from, .toast-fade-leave-to { opacity: 0; transform: translateY(-10px); }
</style>
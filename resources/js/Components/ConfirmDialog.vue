<script setup>
import { useConfirm } from '@/composables/useConfirm.js'
const { confirmState, aceptar, cancelar } = useConfirm()

const variantes = {
  danger:  { color: '#f87171', bg: 'rgba(248,113,113,0.1)', border: 'rgba(248,113,113,0.3)', icon: 'ti-trash',          btnBg: '#f87171', btnText: '#2b0e0e' },
  warning: { color: '#fbbf24', bg: 'rgba(251,191,36,0.1)',  border: 'rgba(251,191,36,0.3)',  icon: 'ti-alert-triangle', btnBg: '#fbbf24', btnText: '#2b2005' },
  info:    { color: '#55b8ef', bg: 'rgba(77,179,240,0.1)',  border: 'rgba(77,179,240,0.3)',  icon: 'ti-info-circle',    btnBg: '#55b8ef', btnText: '#05202b' },
}
</script>

<template>
  <Teleport to="body">
    <Transition name="confirm-fade">
      <div v-if="confirmState.visible" class="confirm-overlay" @click.self="cancelar">
        <div class="confirm-box">
          <div
            class="confirm-icon"
            :style="{
              background: variantes[confirmState.variant].bg,
              color: variantes[confirmState.variant].color,
              borderColor: variantes[confirmState.variant].border,
            }"
          >
            <i :class="`ti ${variantes[confirmState.variant].icon}`"></i>
          </div>
          <h3 class="confirm-title">{{ confirmState.title }}</h3>
          <p class="confirm-message">{{ confirmState.message }}</p>
          <div class="confirm-actions">
            <button class="confirm-btn-cancel" @click="cancelar">{{ confirmState.cancelText }}</button>
            <button
              class="confirm-btn-accept"
              :style="{ background: variantes[confirmState.variant].btnBg, color: variantes[confirmState.variant].btnText }"
              @click="aceptar"
            >
              {{ confirmState.confirmText }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.confirm-overlay {
  position: fixed;
  inset: 0;
  z-index: 9999999;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 20px;
  background: rgba(1, 8, 14, .78);
  backdrop-filter: blur(5px);
}
.confirm-box {
  width: min(380px, 100%);
  border: 1px solid #29485e;
  border-radius: 13px;
  background: linear-gradient(145deg, #0d2233, #0b1c2b);
  box-shadow: 0 25px 90px rgba(0, 0, 0, .58);
  padding: 24px;
  text-align: center;
}
.confirm-icon {
  width: 48px;
  height: 48px;
  margin: 0 auto 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid;
  border-radius: 12px;
  font-size: 22px;
}
.confirm-title {
  margin: 0 0 8px;
  color: #dceaf2;
  font-size: 1rem;
  font-weight: 700;
}
.confirm-message {
  margin: 0 0 20px;
  color: #8ea9bf;
  font-size: .78rem;
  line-height: 1.5;
}
.confirm-actions {
  display: flex;
  gap: 9px;
}
.confirm-btn-cancel,
.confirm-btn-accept {
  flex: 1;
  padding: 10px 14px;
  border-radius: 8px;
  font-size: .78rem;
  font-weight: 700;
  cursor: pointer;
  transition: .15s;
}
.confirm-btn-cancel {
  border: 1px solid #31516a;
  background: transparent;
  color: #91aabd;
}
.confirm-btn-cancel:hover {
  background: rgba(255, 255, 255, .04);
  border-color: #45657b;
}
.confirm-btn-accept {
  border: none;
}
.confirm-btn-accept:hover {
  filter: brightness(1.08);
}

.confirm-fade-enter-active, .confirm-fade-leave-active { transition: opacity .2s ease; }
.confirm-fade-enter-from, .confirm-fade-leave-to { opacity: 0; }
</style>
<script setup>
import { repConfig, secciones } from '@/data/proyecto.js'
import { ref } from 'vue'
const seleccionadas = ref(new Set(secciones))
const toggle = (s) => seleccionadas.value.has(s) ? seleccionadas.value.delete(s) : seleccionadas.value.add(s)
</script>

<template>
  <div class="p-5 max-w-lg">
    <!-- Configuración -->
    <div class="rounded-xl overflow-hidden mb-4" style="background-color:#0d1f30; border:1px solid #1e3a52;">
      <div class="px-5 py-3" style="border-bottom:1px solid #19354d;">
        <div class="section-title" style="margin-bottom:0;">Configuración del reporte</div>
      </div>
      <div class="px-5 py-4 flex flex-col gap-3">
        <div v-for="[l, v] in repConfig" :key="l">
          <div class="field-label">{{ l }}</div>
          <input class="w-full rounded-lg px-3 py-2 text-sm mt-1"
                 :value="v"
                 style="background-color:#122130; border:1px solid #1e3a52; color:#d0dde8; outline:none;"
                 @focus="$event.target.style.borderColor='#00c9a7'"
                 @blur="$event.target.style.borderColor='#1e3a52'">
        </div>
      </div>
    </div>

    <!-- Secciones -->
    <div class="rounded-xl overflow-hidden mb-4" style="background-color:#0d1f30; border:1px solid #1e3a52;">
      <div class="flex justify-between items-center px-5 py-3" style="border-bottom:1px solid #19354d;">
        <div class="section-title" style="margin-bottom:0;">Secciones a incluir</div>
        <span class="text-xs" style="color:#8ea9bf;">{{ seleccionadas.size }} / {{ secciones.length }}</span>
      </div>
      <div class="px-5 py-4 grid grid-cols-2 gap-2">
        <label v-for="s in secciones" :key="s" class="flex items-center gap-2 cursor-pointer">
          <input type="checkbox"
                 class="checkbox checkbox-xs"
                 :checked="seleccionadas.has(s)"
                 @change="toggle(s)"
                 style="border-color:#1e3a52;">
          <span class="text-xs" style="color:#c8dae7;">{{ s }}</span>
        </label>
      </div>
    </div>

    <!-- Botones -->
    <div class="flex gap-3">
      <button class="flex-1 flex items-center justify-center gap-2 rounded-lg py-2.5 text-sm font-semibold transition-all"
              style="background-color:#122130; border:1px solid #1e3a52; color:#8ea9bf;"
              @mouseenter="$event.currentTarget.style.borderColor='#00c9a7'; $event.currentTarget.style.color='#00c9a7'"
              @mouseleave="$event.currentTarget.style.borderColor='#1e3a52'; $event.currentTarget.style.color='#8ea9bf'">
        <i class="ti ti-eye" aria-hidden="true"></i> Vista previa
      </button>
      <button class="btn btn-primary flex-1">
        <i class="ti ti-download" aria-hidden="true"></i> Generar reporte V5
      </button>
    </div>
  </div>
</template>

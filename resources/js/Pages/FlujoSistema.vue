<script setup>
import { phases } from '@/data/proyecto.js'
</script>

<template>
  <div class="p-5 max-w-2xl">
    <p class="mb-5 text-xs leading-relaxed" style="color:#8ea9bf;">
      Ciclo completo desde el registro del proyecto hasta el cierre administrativo.
      Las fases 03, 04 y 05 se retroalimentan continuamente durante la ejecución.
    </p>

    <div class="flex flex-col gap-0">
      <div v-for="(ph, idx) in phases" :key="idx" class="flex gap-3">
        <!-- Número + conector -->
        <div class="flex flex-col items-center w-8 shrink-0">
          <div class="w-7 h-7 rounded-full flex items-center justify-center text-[11px] font-medium"
               style="background-color:#122130; border:1px solid #1e3a52; color:#8ea9bf;">
            {{ ph.num }}
          </div>
          <div v-if="idx < phases.length - 1"
               class="w-px flex-1 min-h-3 mt-1"
               style="background-color:#19354d;" />
        </div>

        <!-- Card de fase -->
        <div class="flex-1 pb-3">
          <div class="rounded-xl p-4" style="background-color:#0d1f30; border:1px solid #1e3a52;">
            <div class="flex items-center gap-2 mb-3">
              <i :class="'ti text-sm ' + ph.icon" style="color:#8ea9bf;" aria-hidden="true" />
              <span class="font-semibold text-sm" style="color:#d0dde8;">{{ ph.name }}</span>
              <span v-if="ph.loop"
                    class="ml-auto text-[10px] px-2 py-0.5 rounded-full font-semibold"
                    style="background:rgba(245,158,11,0.15); color:#f59e0b; border:1px solid rgba(245,158,11,0.3);">
                continuo
              </span>
            </div>
            <div class="flex flex-wrap gap-1.5 items-center">
              <template v-for="(st, i) in ph.steps" :key="i">
                <i v-if="i > 0" class="ti ti-arrow-right text-[10px]" style="color:#1e3a52;" aria-hidden="true" />
                <span class="rounded-lg px-2 py-0.5 text-[11px]"
                      style="border:1px solid #1e3a52; background-color:#122130; color:#c8dae7;">
                  {{ st }}
                </span>
              </template>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Nota de retroalimentación -->
    <div class="rounded-xl p-3 text-xs mt-1"
         style="background:rgba(245,158,11,0.08); border:1px solid rgba(245,158,11,0.25); color:#f59e0b;">
      <i class="ti ti-info-circle text-sm mr-1.5" style="vertical-align:-2px;" aria-hidden="true" />
      Un problema detectado puede generar una modificación contractual que impacta el cronograma y las planillas.
    </div>
  </div>
</template>

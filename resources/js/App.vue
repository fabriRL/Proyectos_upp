<script setup>
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import AppSidebar from '@/Components/AppSidebar.vue'
import AppTopbar from '@/Components/AppTopbar.vue'
import ToastNotification from '@/Components/ToastNotification.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'

const route = useRoute()
const collapsed = ref(false)
const showLayout = computed(() => !route.meta.public)
</script>

<template>
  <div v-if="showLayout" class="flex" style="min-height:100vh; background-color:#060f18;">
    <AppSidebar :collapsed="collapsed" @toggle="collapsed = !collapsed" />
    <div class="flex flex-col flex-1" style="min-width:0;">
      <AppTopbar @toggle-menu="collapsed = !collapsed" />
      <main class="flex-1" style="overflow-x:hidden;">
        <router-view />
      </main>
    </div>
  </div>
  <router-view v-else />

  <ToastNotification />
  <ConfirmDialog />
</template>
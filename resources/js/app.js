import '../css/app.css'
import { createApp } from 'vue'
import './lib/axios' // MArca de la libreria axios ojo
import './utils/inputNumericoEstricto'
import App from './App.vue'
import router from './router'

createApp(App).use(router).mount('#app')

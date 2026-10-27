import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import './style.css'
import { useAdminStore } from './store/admin'

// Manejador global para actualización de recursos al desplegar nuevas versiones
window.addEventListener('vite:preloadError', (event) => {
  console.warn('Nueva versión de StreamePro detectada. Actualizando recursos...', event)
  window.location.reload()
})

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)

// Inicializar sincronización en la nube (Firebase Firestore)
try {
  const adminStore = useAdminStore()
  adminStore.initCloudSync()
} catch (e) {
  console.warn('[Sync] Inicialización en segundo plano:', e)
}

// Limpieza de Service Workers obsoletos en entorno de desarrollo
if (import.meta.env.DEV && 'serviceWorker' in navigator) {
  navigator.serviceWorker.getRegistrations().then(registrations => {
    for (const r of registrations) {
      r.unregister()
    }
  })
}

// Modo producción listo

app.mount('#app')

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

// Control de Service Workers y actualización garantizada en producción
if ('serviceWorker' in navigator) {
  if (import.meta.env.DEV) {
    navigator.serviceWorker.getRegistrations().then(registrations => {
      for (const r of registrations) {
        r.unregister()
      }
    })
  } else {
    // En producción: forzar chequeo de actualización inmediata
    navigator.serviceWorker.getRegistrations().then(registrations => {
      for (const r of registrations) {
        r.update().catch(() => {})
      }
    })
    
    // Si un nuevo Service Worker toma el control, recargar para servir la última versión
    let hasReloaded = false
    navigator.serviceWorker.addEventListener('controllerchange', () => {
      if (!hasReloaded) {
        hasReloaded = true
        console.log('[StreamePro] Nueva versión activada. Actualizando vista...')
        window.location.reload()
      }
    })
  }
}

// Modo producción listo

app.mount('#app')

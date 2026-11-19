import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import './style.css'
import { useAdminStore } from './store/admin'

// Manejador seguro para actualización de recursos al desplegar nuevas versiones (protección contra bucles de recarga)
window.addEventListener('vite:preloadError', (event) => {
  event?.preventDefault?.()
  console.warn('[StreamePro] Fallo al precargar recurso dinámico. Verificando versión...', event)
  const lastReload = Number(sessionStorage.getItem('streamepro_last_chunk_reload') || 0)
  const now = Date.now()
  if (now - lastReload > 20000) {
    sessionStorage.setItem('streamepro_last_chunk_reload', String(now))
    if ('caches' in window) {
      caches.keys().then(names => Promise.all(names.map(n => caches.delete(n)))).finally(() => {
        window.location.reload()
      })
    } else {
      window.location.reload()
    }
  } else {
    console.error('[StreamePro] Se detuvo el bucle de recarga continua. La sesión continúa con los recursos en memoria.')
  }
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

// Control de Service Workers en desarrollo y producción
if ('serviceWorker' in navigator) {
  if (import.meta.env.DEV) {
    navigator.serviceWorker.getRegistrations().then(registrations => {
      for (const r of registrations) {
        r.unregister()
      }
    })
  } else {
    // Chequear actualizaciones en segundo plano sin interrumpir la sesión activa
    navigator.serviceWorker.getRegistrations().then(registrations => {
      for (const r of registrations) {
        r.update().catch(() => {})
      }
    })
  }
}

// Modo producción listo

app.mount('#app')

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import './style.css'

// Manejador global para actualización de recursos al desplegar nuevas versiones
window.addEventListener('vite:preloadError', (event) => {
  console.warn('Nueva versión de StreamePro detectada. Actualizando recursos...', event)
  window.location.reload()
})

const app = createApp(App)

app.use(createPinia())
app.use(router)

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

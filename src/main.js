import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import './style.css'

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

// Medidas de seguridad y protección en producción
if (!import.meta.env.DEV) {
  document.addEventListener('contextmenu', event => event.preventDefault())
  document.addEventListener('keydown', (e) => {
    if (e.key === 'F12' || (e.ctrlKey && e.shiftKey && e.key === 'I')) {
      e.preventDefault()
    }
  })
}

app.mount('#app')

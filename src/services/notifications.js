// Servicio de Notificaciones Web & Push para StreamPro PWA
const NOTIF_PERMISSION_KEY = 'streampro_notif_enabled'

export function isNotificationSupported() {
  return typeof window !== 'undefined' && 'Notification' in window
}

export function getNotificationPermission() {
  if (!isNotificationSupported()) return 'unsupported'
  return Notification.permission // 'default' | 'granted' | 'denied'
}

export function isNotificationEnabled() {
  if (!isNotificationSupported()) return false
  return Notification.permission === 'granted'
}

export async function requestNotificationPermission() {
  if (!isNotificationSupported()) {
    return { success: false, error: 'Tu navegador no soporta notificaciones del sistema' }
  }

  try {
    const permission = await Notification.requestPermission()
    if (permission === 'granted') {
      localStorage.setItem(NOTIF_PERMISSION_KEY, 'true')
      
      // Notificación de bienvenida al activar
      sendLocalNotification('⚡ StreamPro Notificaciones Activadas', {
        body: 'Te avisaremos cuando tus pedidos estén listos, tus cuentas se renueven o haya novedades oficiales.',
        icon: '/pwa-192x192.png',
        badge: '/favicon.svg',
        tag: 'streampro-welcome'
      })
      return { success: true }
    } else {
      return { success: false, error: 'Permiso de notificaciones denegado en el navegador' }
    }
  } catch (err) {
    console.error('[Notification] Error pidiendo permisos:', err)
    return { success: false, error: err.message }
  }
}

export function sendLocalNotification(title, options = {}) {
  if (!isNotificationEnabled()) return false

  const defaultOptions = {
    icon: '/pwa-192x192.png',
    badge: '/favicon.svg',
    vibrate: [200, 100, 200],
    ...options
  }

  try {
    if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
      navigator.serviceWorker.ready.then(reg => {
        reg.showNotification(title, defaultOptions)
      }).catch(() => {
        new Notification(title, defaultOptions)
      })
    } else {
      new Notification(title, defaultOptions)
    }
    return true
  } catch (e) {
    console.warn('[Notification] Falló el envío de notificación:', e)
    return false
  }
}

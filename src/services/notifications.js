// Servicio de Notificaciones Web, Sonoras & Push para StreamePro PWA
const NOTIF_PERMISSION_KEY = 'streampro_notif_enabled'
const IN_APP_NOTIF_KEY = 'streampro_in_app_notif_enabled'

// Reproductor de Chime de Audio con Web Audio API (100% nativo, sin dependencias ni mp3 externos)
export function playNotificationSound() {
  if (typeof window === 'undefined') return
  try {
    const AudioCtx = window.AudioContext || window.webkitAudioContext
    if (!AudioCtx) return
    const ctx = new AudioCtx()

    // Manejo de estado suspendido por políticas de interacción del navegador
    if (ctx.state === 'suspended') {
      ctx.resume().catch(() => {})
    }

    const now = ctx.currentTime

    // Acorde armónico ascendente moderno (C5 523.25Hz -> E5 659.25Hz -> G5 783.99Hz)
    const playTone = (freq, start, duration, gainLevel = 0.22) => {
      const osc = ctx.createOscillator()
      const gain = ctx.createGain()
      osc.type = 'sine'
      osc.frequency.setValueAtTime(freq, start)
      gain.gain.setValueAtTime(gainLevel, start)
      gain.gain.exponentialRampToValueAtTime(0.0001, start + duration)
      osc.connect(gain)
      gain.connect(ctx.destination)
      osc.start(start)
      osc.stop(start + duration)
    }

    playTone(523.25, now, 0.16)
    playTone(659.25, now + 0.12, 0.22)
    playTone(783.99, now + 0.24, 0.38, 0.26)
  } catch (e) {
    // Silencioso en caso de bloqueo estricto del navegador
  }
}

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

export function isInAppNotificationEnabled() {
  if (typeof window === 'undefined') return false
  return localStorage.getItem(IN_APP_NOTIF_KEY) === 'true'
}

export function enableInAppNotification() {
  if (typeof window === 'undefined') return false
  localStorage.setItem(IN_APP_NOTIF_KEY, 'true')
  playNotificationSound()
  return true
}

export function disableInAppNotification() {
  if (typeof window === 'undefined') return false
  localStorage.removeItem(IN_APP_NOTIF_KEY)
  return true
}

export function isAnyNotificationActive() {
  return isNotificationEnabled() || isInAppNotificationEnabled()
}

export async function requestNotificationPermission() {
  if (!isNotificationSupported()) {
    // Si el navegador no soporta Notification, habilitar modo web interno
    enableInAppNotification()
    return { 
      success: true, 
      inAppOnly: true,
      message: 'Notificaciones activadas en modo web interno' 
    }
  }

  // Si ya estaba previamente denegado en el navegador, no volver a llamar a requestPermission directamente
  // porque el navegador lo rechaza en 0ms sin mostrar popup
  if (Notification.permission === 'denied') {
    return { 
      success: false, 
      permission: 'denied',
      error: 'Permiso de notificaciones bloqueado en el navegador. Puedes desbloquearlo en ajustes del sitio o activar las alertas internas en la web.' 
    }
  }

  try {
    const permission = await Notification.requestPermission()
    if (permission === 'granted') {
      localStorage.setItem(NOTIF_PERMISSION_KEY, 'true')
      enableInAppNotification()
      
      // Notificación de bienvenida al activar
      sendLocalNotification('⚡ StreamePro Notificaciones Activadas', {
        body: 'Te avisaremos cuando tus pedidos estén listos, tus cuentas se renueven o haya novedades oficiales.',
        icon: '/pwa-192x192.png',
        badge: '/favicon.svg',
        tag: 'streampro-welcome'
      })
      return { success: true }
    } else {
      return { 
        success: false, 
        permission: permission,
        error: 'Permiso de notificaciones denegado en el navegador' 
      }
    }
  } catch (err) {
    console.error('[Notification] Error pidiendo permisos:', err)
    return { success: false, error: err.message }
  }
}

export function sendLocalNotification(title, options = {}) {
  // 1. Siempre reproducir el sonido si alguna notificación está activa
  if (isAnyNotificationActive()) {
    playNotificationSound()
  }

  // 2. Si no tiene notificaciones del sistema habilitadas, retornar
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
    console.warn('[Notification] Falló el envío de notificación nativa:', e)
    return false
  }
}

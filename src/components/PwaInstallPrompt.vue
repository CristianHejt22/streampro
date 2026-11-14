<script setup>
import { ref, onMounted, computed } from 'vue'
import { 
  isNotificationSupported, 
  getNotificationPermission, 
  requestNotificationPermission, 
  sendLocalNotification,
  isInAppNotificationEnabled,
  enableInAppNotification,
  playNotificationSound,
  isAnyNotificationActive
} from '../services/notifications'
import { useToastStore } from '../store/toast'

const toast = useToastStore()

const deferredPrompt = ref(null)
const isInstalled = ref(false)
const isIos = ref(false)
const showIosModal = ref(false)
const showUnblockModal = ref(false)
const isDismissed = ref(false)
const isCollapsed = ref(false)
const notifPermission = ref(getNotificationPermission())
const inAppNotifActive = ref(isInAppNotificationEnabled())

// Verificar si ya está instalado como PWA o ejecutándose como app independiente
const checkStandalone = () => {
  if (typeof window === 'undefined') return false
  const isStandaloneMatch = window.matchMedia('(display-mode: standalone)').matches
  const isIosStandalone = window.navigator.standalone === true
  return isStandaloneMatch || isIosStandalone
}

// Detectar dispositivo iOS (iPhone, iPad, iPod)
const checkIsIos = () => {
  if (typeof window === 'undefined') return false
  const ua = window.navigator.userAgent.toLowerCase()
  return /iphone|ipad|ipod/.test(ua)
}

onMounted(() => {
  isInstalled.value = checkStandalone()
  isIos.value = checkIsIos()
  notifPermission.value = getNotificationPermission()
  inAppNotifActive.value = isInAppNotificationEnabled()

  // Comprobar si el usuario minimizó el banner previamente en esta sesión
  const savedDismiss = sessionStorage.getItem('streampro_pwa_dismissed')
  if (savedDismiss === 'true') {
    isCollapsed.value = true
  }

  // Escuchar evento de instalación nativo (Android Chrome, Windows Edge, Mac Chrome)
  window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault()
    deferredPrompt.value = e
    isInstalled.value = false
  })

  // Escuchar cuando el usuario instala la app exitosamente
  window.addEventListener('appinstalled', () => {
    isInstalled.value = true
    deferredPrompt.value = null
    toast.add('¡StreamePro instalado con éxito en tu dispositivo!', 'success')
    sendLocalNotification('⚡ ¡StreamePro Instalado!', {
      body: 'Ya tienes acceso rápido a tu catálogo de streaming y tecnología en tu pantalla de inicio.',
      icon: '/pwa-192x192.png'
    })
  })
})

const canInstall = computed(() => {
  if (isInstalled.value) return false
  return Boolean(deferredPrompt.value) || isIos.value
})

const handleInstallClick = async () => {
  if (deferredPrompt.value) {
    try {
      deferredPrompt.value.prompt()
      const choice = await deferredPrompt.value.userChoice
      if (choice.outcome === 'accepted') {
        toast.add('Instalando StreamePro...', 'info')
      }
      deferredPrompt.value = null
    } catch (err) {
      console.warn('[PWA] Error instalando:', err)
    }
  } else if (isIos.value) {
    showIosModal.value = true
  } else {
    toast.add('Puedes agregar esta página a tu pantalla de inicio desde el menú de tu navegador (⋮)', 'info')
  }
}

const handleNotificationToggle = async () => {
  // Si el navegador ya tiene el permiso bloqueado ('denied'), abrir modal de desbloqueo con solución inmediata
  if (notifPermission.value === 'denied') {
    showUnblockModal.value = true
    return
  }

  const res = await requestNotificationPermission()
  notifPermission.value = getNotificationPermission()
  inAppNotifActive.value = isInAppNotificationEnabled()

  if (res.success) {
    toast.add('🔔 ¡Notificaciones activadas correctamente!', 'success')
    playNotificationSound()
  } else if (res.permission === 'denied' || notifPermission.value === 'denied') {
    // Si el usuario denegó o estaba bloqueado, mostrar modal explicativo con activación en web
    showUnblockModal.value = true
  } else {
    toast.add(res.error || 'No se pudieron activar las notificaciones', 'error')
  }
}

const activateInAppOnly = () => {
  enableInAppNotification()
  inAppNotifActive.value = true
  showUnblockModal.value = false
  toast.add('🔔 ¡Alertas en la web activadas! Recibirás avisos sonoros y en pantalla cuando tu pedido cambie.', 'success')
}

const testNotificationChime = () => {
  playNotificationSound()
  toast.add('🎵 Sonido de alerta StreamePro reproducido.', 'info')
}

const dismissBanner = () => {
  isCollapsed.value = true
  sessionStorage.setItem('streampro_pwa_dismissed', 'true')
}
</script>

<template>
  <div>
    <!-- BANNER FLOTANTE DE INSTALACIÓN PWA Y NOTIFICACIONES -->
    <div 
      v-if="!isInstalled && !isCollapsed" 
      class="fixed bottom-4 left-4 right-4 sm:left-auto sm:right-6 sm:w-96 z-40 animate-in slide-in-from-bottom-4 duration-300"
    >
      <div class="bg-gradient-to-b from-slate-900 via-slate-900/95 to-slate-950 border border-amber-500/40 rounded-2xl p-4 shadow-2xl shadow-black/80 backdrop-blur-xl relative space-y-3">
        <!-- Botón minimizar / cerrar -->
        <button 
          @click="dismissBanner" 
          class="absolute top-2.5 right-2.5 text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 text-xs transition-colors cursor-pointer"
          title="Minimizar"
        >
          ✕
        </button>

        <div class="flex items-start gap-3 pr-6">
          <div class="w-11 h-11 rounded-xl bg-gradient-to-tr from-amber-500 to-orange-500 flex items-center justify-center font-black text-slate-950 shadow-lg text-lg shrink-0">
            ⚡
          </div>
          <div>
            <div class="flex items-center gap-1.5">
              <h4 class="font-black text-white text-xs sm:text-sm">Instalar StreamePro</h4>
              <span class="text-[9px] font-mono font-bold bg-amber-400/20 text-amber-300 border border-amber-400/30 px-1 rounded">App Móvil</span>
            </div>
            <p class="text-[11px] text-slate-300 leading-snug mt-0.5">
              Accede a tus cuentas y catálogo sin descargas pesadas directo en tu inicio.
            </p>
          </div>
        </div>

        <!-- Botones de Acción -->
        <div class="flex flex-col gap-2 pt-1">
          <div class="flex gap-2">
            <button 
              @click="handleInstallClick" 
              class="flex-1 bg-gradient-to-r from-amber-400 via-orange-400 to-amber-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 font-black py-2.5 px-3 rounded-xl shadow-lg shadow-amber-500/20 text-xs flex items-center justify-center gap-1.5 transition-all cursor-pointer"
            >
              <span>📲</span>
              <span>Instalar en Dispositivo</span>
            </button>
            <button 
              @click="dismissBanner" 
              class="bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold px-3 py-2.5 rounded-xl transition-colors cursor-pointer"
            >
              Luego
            </button>
          </div>

          <!-- Botón de Notificaciones Push & Alertas -->
          <div class="border-t border-slate-800/80 pt-2 flex items-center justify-between text-[11px]">
            <span class="text-slate-400 flex items-center gap-1">
              <span>🔔</span>
              <span>Notificaciones de Pedidos:</span>
            </span>
            
            <!-- Estado 1: Permiso concedido por el navegador -->
            <div v-if="notifPermission === 'granted'" class="flex items-center gap-1.5">
              <span class="text-emerald-400 font-bold flex items-center gap-1 font-mono">
                <span>✓</span> Activas
              </span>
              <button 
                type="button" 
                @click="testNotificationChime" 
                class="text-[10px] text-slate-400 hover:text-white bg-slate-800 hover:bg-slate-700 px-1.5 py-0.5 rounded border border-slate-700 cursor-pointer"
                title="Probar sonido"
              >
                🎵 Probar
              </button>
            </div>

            <!-- Estado 2: Modo Web Interno activo (navegador bloqueado pero cliente avisado) -->
            <div v-else-if="inAppNotifActive" class="flex items-center gap-1.5">
              <span class="text-amber-300 font-bold flex items-center gap-1 font-mono">
                <span>🔔</span> En Web
              </span>
              <button 
                type="button" 
                @click="showUnblockModal = true" 
                class="text-[10px] text-sky-400 hover:text-sky-300 underline cursor-pointer"
                title="Ajustes de notificación"
              >
                Ajustes
              </button>
            </div>

            <!-- Estado 3: Permiso Bloqueado en el navegador ('denied') -->
            <button 
              v-else-if="notifPermission === 'denied'" 
              @click="showUnblockModal = true"
              class="text-amber-400 hover:text-amber-300 font-bold bg-amber-400/10 hover:bg-amber-400/20 border border-amber-400/30 px-2 py-0.5 rounded-lg transition-colors cursor-pointer flex items-center gap-1"
              title="Permiso bloqueado en el navegador - Clic para desbloquear"
            >
              <span>🔓</span>
              <span>Desbloquear</span>
            </button>

            <!-- Estado 4: Permiso por defecto (aún no consultado) -->
            <button 
              v-else 
              @click="handleNotificationToggle"
              class="text-amber-400 hover:text-amber-300 font-bold bg-amber-400/10 hover:bg-amber-400/20 border border-amber-400/30 px-2.5 py-0.5 rounded-lg transition-colors cursor-pointer flex items-center gap-1"
            >
              <span>🔔</span>
              <span>Activar</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- BOTÓN MINIMIZADO FLOTANTE (Si fue cerrado o para acceso rápido) -->
    <div 
      v-else-if="!isInstalled" 
      class="fixed bottom-4 right-4 z-40 animate-fade-in"
    >
      <button 
        @click="isCollapsed = false" 
        class="bg-slate-900/90 hover:bg-slate-800 border border-amber-500/30 text-amber-400 hover:text-amber-300 text-xs font-bold px-3.5 py-2 rounded-full shadow-xl shadow-black/60 backdrop-blur-md flex items-center gap-2 transition-all cursor-pointer group"
      >
        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
        <span class="group-hover:inline">📲 Instalar App</span>
      </button>
    </div>

    <!-- MODAL DE DESBLOQUEO / ACTIVACIÓN DE NOTIFICACIONES -->
    <div v-if="showUnblockModal" class="fixed inset-0 z-[90] flex items-center justify-center p-3 sm:p-5 pt-16 sm:pt-6 pb-6 overflow-y-auto overscroll-contain bg-black/85 backdrop-blur-md animate-fade-in">
      <div class="bg-slate-900 border border-amber-500/30 w-full max-w-md rounded-3xl p-6 shadow-2xl relative space-y-5">
        <button 
          @click="showUnblockModal = false" 
          type="button" 
          class="absolute top-4 right-4 w-9 h-9 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white flex items-center justify-center text-sm font-bold cursor-pointer"
        >
          ✕
        </button>

        <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-2xl bg-amber-500/20 text-amber-400 border border-amber-500/30 flex items-center justify-center text-2xl font-black shrink-0">
            🔔
          </div>
          <div>
            <h3 class="text-lg font-black text-white">Notificaciones de Pedidos</h3>
            <p class="text-xs text-slate-300">Recibe avisos inmediatos cuando tu pedido esté listo o aprobado.</p>
          </div>
        </div>

        <!-- Opción 1 Directa: Alertas Web con Sonido (100% Funcional sin tocar el navegador) -->
        <div class="bg-gradient-to-br from-amber-500/10 via-slate-950 to-slate-950 p-4 rounded-2xl border border-amber-500/30 space-y-2.5">
          <div class="flex items-center justify-between">
            <span class="text-xs font-black text-amber-300 uppercase tracking-wider flex items-center gap-1.5">
              <span>⚡</span> Activación Rápida
            </span>
            <span class="text-[10px] bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 px-2 py-0.5 rounded font-mono font-bold">
              100% Garantizado
            </span>
          </div>
          <p class="text-xs text-slate-300 leading-relaxed">
            Activa las alertas sonoras y avisos flotantes en pantalla dentro de StreamePro, sin necesidad de cambiar permisos en el navegador.
          </p>
          <div class="flex gap-2 pt-1">
            <button 
              type="button" 
              @click="activateInAppOnly" 
              class="flex-1 bg-gradient-to-r from-amber-400 to-orange-400 hover:from-amber-300 hover:to-orange-300 text-slate-950 font-black py-2.5 px-3 rounded-xl text-xs transition shadow-md cursor-pointer flex items-center justify-center gap-1.5"
            >
              <span>🔔</span>
              <span>Activar Alertas con Sonido</span>
            </button>
            <button 
              type="button" 
              @click="testNotificationChime" 
              class="bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold px-3 py-2.5 rounded-xl text-xs transition cursor-pointer"
              title="Probar sonido"
            >
              🎵 Probar
            </button>
          </div>
        </div>

        <!-- Opción 2: Cómo desbloquear en el Navegador si desea Notificaciones Nativas del Sistema -->
        <div class="space-y-2.5 text-xs">
          <div class="font-bold text-slate-300 flex items-center gap-1.5">
            <span>🔒</span>
            <span>Para recibir notificaciones del sistema con pantalla apagada:</span>
          </div>

          <div class="bg-slate-950 p-3.5 rounded-2xl border border-slate-800 space-y-2.5 text-slate-300">
            <div class="flex items-start gap-2.5">
              <span class="w-5 h-5 rounded-md bg-slate-800 border border-slate-700 flex items-center justify-center font-bold text-amber-400 shrink-0 text-[11px]">1</span>
              <p>Toca el ícono del <strong>candado o ajustes 🔒</strong> al lado de <em>streamepro.me</em> en la barra de direcciones.</p>
            </div>
            <div class="flex items-start gap-2.5">
              <span class="w-5 h-5 rounded-md bg-slate-800 border border-slate-700 flex items-center justify-center font-bold text-amber-400 shrink-0 text-[11px]">2</span>
              <p>Entra en <strong>Permisos</strong> o <strong>Configuración del sitio</strong>.</p>
            </div>
            <div class="flex items-start gap-2.5">
              <span class="w-5 h-5 rounded-md bg-slate-800 border border-slate-700 flex items-center justify-center font-bold text-amber-400 shrink-0 text-[11px]">3</span>
              <p>Busca <strong>Notificaciones</strong> y cámbialo a <strong class="text-emerald-400">Permitir</strong>.</p>
            </div>
            <div class="flex items-start gap-2.5">
              <span class="w-5 h-5 rounded-md bg-slate-800 border border-slate-700 flex items-center justify-center font-bold text-amber-400 shrink-0 text-[11px]">4</span>
              <p>Recarga la página para aplicar los cambios del sistema.</p>
            </div>
          </div>
        </div>

        <button 
          @click="showUnblockModal = false" 
          class="w-full bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white font-bold py-2.5 rounded-xl text-xs transition cursor-pointer"
        >
          Cerrar
        </button>
      </div>
    </div>

    <!-- MODAL DE GUÍA PARA DISPOSITIVOS iOS (SAFARI EN IPHONE / IPAD) -->
    <div v-if="showIosModal" class="fixed inset-0 z-[80] flex items-center justify-center p-3 sm:p-5 pt-16 sm:pt-6 pb-6 overflow-y-auto overscroll-contain bg-black/85 backdrop-blur-md animate-fade-in">
      <div class="bg-slate-900 border border-slate-700 w-full max-w-sm rounded-3xl p-6 shadow-2xl relative space-y-5 text-center">
        <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-amber-500 to-orange-500 flex items-center justify-center font-black text-slate-950 text-2xl mx-auto shadow-lg">
          ⚡
        </div>

        <div>
          <h3 class="text-lg font-black text-white">Instalar en tu iPhone / iPad</h3>
          <p class="text-xs text-slate-400 mt-1">Sigue estos 2 simples pasos en Safari:</p>
        </div>

        <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 text-left space-y-3 text-xs text-slate-300">
          <div class="flex items-start gap-3">
            <span class="w-6 h-6 rounded-lg bg-slate-800 border border-slate-700 flex items-center justify-center font-bold text-amber-400 shrink-0">1</span>
            <p>Toca el botón <strong>Compartir</strong> <span class="bg-slate-800 px-1.5 py-0.5 rounded text-white font-mono">⎋</span> en la barra inferior de Safari.</p>
          </div>
          <div class="flex items-start gap-3">
            <span class="w-6 h-6 rounded-lg bg-slate-800 border border-slate-700 flex items-center justify-center font-bold text-amber-400 shrink-0">2</span>
            <p>Baja y selecciona la opción <strong>"Agregar a pantalla de inicio"</strong> <span class="bg-slate-800 px-1.5 py-0.5 rounded text-white font-mono">➕</span>.</p>
          </div>
        </div>

        <button 
          @click="showIosModal = false" 
          class="w-full bg-slate-800 hover:bg-slate-700 text-white font-bold py-2.5 rounded-xl text-xs transition-colors cursor-pointer"
        >
          ¡Entendido!
        </button>
      </div>
    </div>
  </div>
</template>

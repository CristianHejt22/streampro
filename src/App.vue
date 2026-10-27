<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { RouterLink, RouterView, useRoute } from 'vue-router'
import { useToastStore } from './store/toast'
import { useResellerStore, useAdminStore } from './store/admin'
import PwaInstallPrompt from './components/PwaInstallPrompt.vue'
import UserProfileModal from './components/UserProfileModal.vue'

const route = useRoute()
const toastStore = useToastStore()
const resellerStore = useResellerStore()
const adminStore = useAdminStore()

const showProfileModal = ref(false)
const showNavDropdown = ref(false)
const savedUserName = ref('')

const openSupportWhatsApp = () => {
  const targetPhone = adminStore.providerBankInfo?.whatsapp || adminStore.adminAuth?.whatsapp || '+54 9 11 0000-0000'
  const cleanPhone = targetPhone.replace(/[^0-9]/g, '')
  const msg = '¡Hola StreamePro! 👋 Necesito información o soporte sobre las suscripciones y cuentas premium.'
  window.open(`https://wa.me/${cleanPhone}?text=${encodeURIComponent(msg)}`, '_blank')
}

const copyStoreUrl = () => {
  if (navigator?.clipboard?.writeText) {
    navigator.clipboard.writeText('https://streamepro.me').then(() => {
      toastStore.add('¡Enlace oficial https://streamepro.me copiado con éxito!', 'success')
    }).catch(() => {
      toastStore.add('Enlace oficial: https://streamepro.me', 'info')
    })
  }
}

watch(() => route.fullPath, () => {
  showNavDropdown.value = false
})

const checkSavedUser = () => {
  try {
    const raw = localStorage.getItem('streampro_user_profile')
    if (raw) {
      const parsed = JSON.parse(raw)
      savedUserName.value = parsed.name ? parsed.name.split(' ')[0] : ''
    } else {
      savedUserName.value = ''
    }
  } catch (e) {
    savedUserName.value = ''
  }
}

onMounted(() => {
  checkSavedUser()
})

const handleProfileSaved = (prof) => {
  savedUserName.value = prof.name ? prof.name.split(' ')[0] : ''
}

const isClientArea = computed(() => route.name === 'home' || route.name === 'portal' || route.name === 'recruitment' || ['/', '/portal', '/cliente', '/clientes', '/boveda', '/portal-cliente', '/p', '/revendedores', '/unete', '/distribuidores', '/quiero-ser-revendedor', '/trabaja-con-nosotros'].includes(route.path))
const isResellerArea = computed(() => route.name === 'crm' || ['/crm', '/cm', '/reseller', '/revendedor', '/panel', '/c'].includes(route.path))
const isAdminArea = computed(() => route.name === 'myprove' || ['/myprove', '/myprov', '/proveedor', '/admin-proveedor', '/prove', '/admin'].includes(route.path))
</script>

<template>
  <div class="min-h-screen bg-[#020617] text-white flex flex-col font-sans selection:bg-brand-accent selection:text-white relative overflow-hidden">
    <!-- Ambient Background Gradients -->
    <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] rounded-full bg-brand-accent/20 blur-[120px] pointer-events-none"></div>
    <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] rounded-full bg-blue-600/10 blur-[120px] pointer-events-none"></div>

    <header class="bg-[#030712]/85 backdrop-blur-xl border-b border-white/[0.06] sticky top-0 z-50 transition-all">
      <div class="container mx-auto px-3 sm:px-4 py-2.5 sm:py-3 flex items-center justify-between gap-2 sm:gap-4">
        <!-- Logo & Enterprise Brand -->
        <RouterLink to="/" class="flex items-center gap-2.5 sm:gap-3 group shrink-0">
          <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-gradient-to-br from-sky-400 via-blue-600 to-indigo-600 flex items-center justify-center shadow-[0_0_20px_rgba(56,189,248,0.4)] border border-white/20 group-hover:scale-105 transition-transform shrink-0">
            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-white drop-shadow" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
          </div>
          <div>
            <div class="flex items-center gap-1.5 sm:gap-2">
              <h1 class="text-base sm:text-xl font-black text-transparent bg-clip-text bg-gradient-to-r from-sky-400 via-blue-300 to-indigo-300 tracking-tight leading-tight">STREAMEPRO</h1>
              <span v-if="isClientArea" class="text-[9px] sm:text-[10px] font-mono font-black uppercase tracking-widest bg-sky-500/10 text-sky-400 border border-sky-500/20 px-1.5 py-0.2 rounded">Oficial</span>
              <span v-else-if="isResellerArea" class="text-[9px] sm:text-[10px] font-mono font-black uppercase tracking-widest bg-blue-500/20 text-blue-300 border border-blue-500/30 px-1.5 py-0.2 rounded">Distribuidores</span>
              <span v-else-if="isAdminArea" class="text-[9px] sm:text-[10px] font-mono font-black uppercase tracking-widest bg-amber-500/20 text-amber-300 border border-amber-500/30 px-1.5 py-0.2 rounded">Mayorista</span>
            </div>
            <p class="hidden md:block text-[10px] text-slate-500 font-medium tracking-wide">
              {{ isClientArea ? 'Tienda Oficial de Cuentas Premium' : isResellerArea ? 'Panel Exclusivo de Distribuidores' : 'Centro de Control & Inventario Proveedor' }}
            </p>
          </div>
        </RouterLink>

        <!-- System Status & Separated Navigation -->
        <div class="flex items-center gap-2 sm:gap-3">
          <div class="hidden lg:flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-semibold">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            <span>Garantía Oficial • Entrega: 10 min a 1 hora</span>
          </div>

          <!-- NAVEGACIÓN PARA CLIENTES: Todas las funciones activas como antes + Menú desplegable mejorado -->
          <nav v-if="isClientArea" class="flex flex-wrap items-center gap-1 sm:gap-1.5 text-xs font-bold bg-slate-900/80 p-1 sm:p-1.5 rounded-2xl border border-white/[0.08] shadow-inner">
            <RouterLink 
              to="/" 
              class="px-2.5 sm:px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-white/[0.05] transition-all flex items-center gap-1.5" 
              active-class="!bg-sky-500/15 !text-sky-300 !border !border-sky-500/30 shadow-sm"
              title="Tienda Oficial"
            >
              <span class="text-sm">🛒</span>
              <span class="hidden sm:inline">Tienda</span>
            </RouterLink>
            
            <RouterLink 
              to="/p" 
              class="px-2.5 sm:px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-white/[0.05] transition-all flex items-center gap-1.5" 
              active-class="!bg-amber-500/15 !text-amber-300 !border !border-amber-500/30 shadow-sm"
              title="Catálogo de Productos Físicos tua.ar"
            >
              <span>📦</span>
              <span class="hidden sm:inline">Catálogo Físico</span>
            </RouterLink>

            <RouterLink 
              to="/portal" 
              class="px-2.5 sm:px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-white/[0.05] transition-all flex items-center gap-1.5" 
              active-class="!bg-sky-500/15 !text-sky-300 !border !border-sky-500/30 shadow-sm"
              title="Mi Bóveda de Clientes"
            >
              <span>🔐</span>
              <span class="hidden sm:inline">Mi Bóveda</span>
            </RouterLink>

            <RouterLink 
              to="/revendedores" 
              class="px-2.5 sm:px-3 py-2 rounded-xl text-amber-300 hover:text-white bg-amber-500/10 hover:bg-amber-500/20 border border-amber-500/20 transition-all flex items-center gap-1.5 font-bold" 
              active-class="!bg-amber-500/20 !text-amber-200 !border-amber-500/40 shadow-sm"
              title="Ser Revendedor Oficial"
            >
              <span>🚀</span>
              <span class="hidden md:inline">Ser Revendedor</span>
            </RouterLink>

            <!-- Acceso Rápido / Registro de Usuario -->
            <button 
              @click="showProfileModal = true" 
              class="px-2.5 sm:px-3 py-2 rounded-xl text-sky-300 hover:text-white bg-sky-500/10 hover:bg-sky-500/20 border border-sky-500/30 transition-all flex items-center gap-1.5 font-bold shadow-sm cursor-pointer"
              title="Mi Perfil, compras y registro"
            >
              <span>👤</span>
              <span class="hidden sm:inline">{{ savedUserName ? savedUserName : 'Mi Cuenta' }}</span>
            </button>

            <!-- BOTÓN DEL MENÚ DESPLEGABLE MEJORADO -->
            <button 
              @click="showNavDropdown = !showNavDropdown" 
              class="px-2.5 sm:px-3.5 py-2 rounded-xl bg-gradient-to-r from-sky-500/20 via-blue-500/20 to-indigo-500/20 hover:from-sky-500/30 hover:to-indigo-500/30 text-sky-300 hover:text-white border border-sky-500/40 font-black transition-all flex items-center gap-1.5 shadow-sm active:scale-95 cursor-pointer"
              :class="{ '!bg-sky-500 !text-slate-950 !border-white shadow-[0_0_20px_rgba(56,189,248,0.5)]': showNavDropdown }"
              title="Abrir menú desplegable con todas las opciones"
            >
              <span class="text-sm font-bold">{{ showNavDropdown ? '✕' : '☰' }}</span>
              <span class="hidden sm:inline">Menú</span>
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
            </button>
          </nav>

          <!-- NAVEGACIÓN CUANDO SE ENCUENTRA EN EL PANEL DE REVENDEDOR -->
          <div v-else-if="isResellerArea" class="flex items-center gap-2">
            <div v-if="resellerStore.isAuthenticated" class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-800 text-xs">
              <span class="text-slate-400">Distribuidor:</span>
              <strong class="text-amber-400 font-mono">[{{ resellerStore.activeResellerId }}]</strong>
              <span class="text-emerald-400 font-mono font-bold">${{ resellerStore.wallet }} ARS</span>
            </div>

            <button 
              @click="showProfileModal = true" 
              class="px-3 py-2 rounded-xl text-sky-300 hover:text-white bg-slate-900 hover:bg-slate-800 border border-slate-800 text-xs font-bold transition-all flex items-center gap-1.5"
            >
              <span>👤</span>
              <span class="hidden sm:inline">Mi Perfil</span>
            </button>

            <RouterLink 
              to="/" 
              class="text-xs font-bold px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-white border border-slate-800 transition-all flex items-center gap-1.5"
            >
              <span>← Volver a Tienda</span>
            </RouterLink>
          </div>

          <!-- NAVEGACIÓN CUANDO SE ENCUENTRA EN EL PANEL DE ADMIN -->
          <div v-else-if="isAdminArea" class="flex items-center gap-2">
            <span class="hidden sm:inline-block text-xs font-mono font-bold text-amber-300 bg-amber-500/15 border border-amber-500/30 px-3 py-1.5 rounded-xl">
              Panel Mayorista Proveedor
            </span>
            <RouterLink 
              to="/" 
              class="text-xs font-bold px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-white border border-slate-800 transition-all flex items-center gap-1.5"
            >
              <span>← Volver a Tienda</span>
            </RouterLink>
          </div>
        </div>
      </div>

      <!-- ======================================================== -->
      <!-- MENÚ DESPLEGABLE PRO (COMPLETO, ATRACTIVO Y ORDENADO)   -->
      <!-- ======================================================== -->
      <Teleport to="body">
        <div 
          v-if="showNavDropdown" 
          class="fixed inset-0 z-[9999] bg-black/75 backdrop-blur-md flex justify-end p-2.5 sm:p-5 animate-in fade-in duration-200"
          @click="showNavDropdown = false"
        >
        <div 
          @click.stop 
          class="w-full max-w-sm sm:max-w-md bg-[#030712]/98 border border-sky-500/40 rounded-3xl p-4 sm:p-6 shadow-[0_25px_60px_rgba(0,0,0,0.95),0_0_40px_rgba(56,189,248,0.25)] flex flex-col justify-between overflow-y-auto max-h-[92dvh] space-y-4 text-xs relative animate-in slide-in-from-top-4 duration-200 custom-scrollbar"
        >
          <!-- Encabezado del Menú Desplegable -->
          <div class="flex items-center justify-between border-b border-white/[0.08] pb-3">
            <div class="flex items-center gap-2.5 sm:gap-3">
              <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-2xl bg-gradient-to-br from-sky-400 via-blue-600 to-indigo-600 flex items-center justify-center text-white text-base sm:text-lg shadow-[0_0_20px_rgba(56,189,248,0.4)] border border-white/20">
                ⚡
              </div>
              <div>
                <div class="flex items-center gap-1.5 sm:gap-2">
                  <h3 class="font-black text-white text-sm sm:text-base tracking-tight leading-none">STREAMEPRO</h3>
                  <span class="text-[9px] font-mono font-bold bg-sky-500/20 text-sky-400 px-1.5 py-0.2 rounded border border-sky-500/30">
                    OFICIAL
                  </span>
                </div>
                <p class="text-[10px] sm:text-[11px] text-slate-400 font-medium">Menú Principal & Servicios para Clientes</p>
              </div>
            </div>
            <button 
              @click="showNavDropdown = false" 
              class="w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white flex items-center justify-center font-bold text-sm transition-colors cursor-pointer"
              title="Cerrar Menú"
            >
              ✕
            </button>
          </div>

          <!-- Saludo Personalizado & Estado del Sistema -->
          <div class="bg-gradient-to-r from-slate-900/90 to-[#082f49]/40 border border-sky-500/30 rounded-2xl p-3 flex items-center justify-between shadow-inner">
            <div class="flex items-center gap-2.5">
              <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse shrink-0"></span>
              <div>
                <span class="text-[11px] text-white font-bold block">
                  {{ savedUserName ? `¡Hola, ${savedUserName}!` : 'Bienvenido a la Tienda Oficial' }}
                </span>
                <span class="text-[10px] text-emerald-400 font-medium">🟢 Entrega: 10 min a 1 hora • Stock 100%</span>
              </div>
            </div>
            <span class="text-[10px] font-mono bg-emerald-500/10 text-emerald-300 border border-emerald-500/20 px-2 py-0.5 rounded-full font-bold shrink-0">
              Garantizado
            </span>
          </div>

          <!-- SECCIÓN 1: 🛍️ CATÁLOGOS & SUSCRIPCIONES -->
          <div class="space-y-2">
            <div class="flex items-center justify-between px-1">
              <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                <span>🛍️</span> Catálogos & Suscripciones
              </span>
              <span class="text-[9px] font-mono text-sky-400">Entrega Inmediata</span>
            </div>

            <div class="grid grid-cols-1 gap-2">
              <RouterLink 
                to="/?sec=digital" 
                @click="showNavDropdown = false"
                class="flex items-center justify-between p-3 rounded-2xl bg-slate-900/70 hover:bg-slate-800/90 border border-white/[0.06] hover:border-sky-500/40 transition-all group shadow-sm"
              >
                <div class="flex items-center gap-3">
                  <span class="w-9 h-9 rounded-xl bg-sky-500/15 border border-sky-500/30 text-sky-400 flex items-center justify-center text-base shrink-0 group-hover:scale-105 transition-transform">
                    🎬
                  </span>
                  <div>
                    <span class="font-bold text-white text-xs block group-hover:text-sky-300">Suscripciones Digitales</span>
                    <span class="text-[10px] text-slate-400">Netflix, Max, Disney+, Spotify, IPTV, Canva Pro</span>
                  </div>
                </div>
                <span class="text-slate-500 group-hover:text-sky-400 font-bold text-sm">→</span>
              </RouterLink>

              <RouterLink 
                to="/?sec=packages" 
                @click="showNavDropdown = false"
                class="flex items-center justify-between p-3 rounded-2xl bg-gradient-to-r from-purple-950/40 via-pink-950/30 to-slate-900/70 hover:bg-slate-800/90 border border-pink-500/30 hover:border-pink-500/60 transition-all group shadow-sm"
              >
                <div class="flex items-center gap-3">
                  <span class="w-9 h-9 rounded-xl bg-pink-500/20 border border-pink-500/40 text-pink-300 flex items-center justify-center text-base shrink-0 group-hover:scale-105 transition-transform">
                    🎁
                  </span>
                  <div>
                    <div class="flex items-center gap-2">
                      <span class="font-bold text-white text-xs group-hover:text-pink-300">Combos & Paquetes VIP</span>
                      <span class="text-[8px] bg-pink-500/20 text-pink-300 px-1.5 py-0.2 rounded font-black border border-pink-500/30 uppercase">
                        Hasta 40% OFF
                      </span>
                    </div>
                    <span class="text-[10px] text-slate-400">Plataformas combinadas con ahorro máximo</span>
                  </div>
                </div>
                <span class="text-pink-400 font-bold text-sm">→</span>
              </RouterLink>

              <RouterLink 
                to="/p" 
                @click="showNavDropdown = false"
                class="flex items-center justify-between p-3 rounded-2xl bg-slate-900/70 hover:bg-slate-800/90 border border-amber-500/25 hover:border-amber-500/50 transition-all group shadow-sm"
              >
                <div class="flex items-center gap-3">
                  <span class="w-9 h-9 rounded-xl bg-amber-500/15 border border-amber-500/30 text-amber-300 flex items-center justify-center text-base shrink-0 group-hover:scale-105 transition-transform">
                    📦
                  </span>
                  <div>
                    <div class="flex items-center gap-2">
                      <span class="font-bold text-white text-xs group-hover:text-amber-300">Catálogo de Productos Físicos</span>
                      <span class="text-[8px] bg-amber-500/20 text-amber-300 px-1.5 py-0.2 rounded font-black border border-amber-500/30 uppercase">
                        tua.ar
                      </span>
                    </div>
                    <span class="text-[10px] text-slate-400">Tecnología, accesorios, bazar y envíos a todo el país</span>
                  </div>
                </div>
                <span class="text-amber-400 font-bold text-sm">→</span>
              </RouterLink>
            </div>
          </div>

          <!-- SECCIÓN 2: 🔐 TU CUENTA & ACCESOS -->
          <div class="space-y-2">
            <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 px-1 block flex items-center gap-1.5">
              <span>🔐</span> Tu Cuenta & Accesos Privados
            </span>

            <div class="grid grid-cols-1 gap-2">
              <RouterLink 
                to="/portal" 
                @click="showNavDropdown = false"
                class="flex items-center justify-between p-3 rounded-2xl bg-slate-900/70 hover:bg-slate-800/90 border border-white/[0.06] hover:border-sky-500/40 transition-all group shadow-sm"
              >
                <div class="flex items-center gap-3">
                  <span class="w-9 h-9 rounded-xl bg-sky-500/15 border border-sky-500/30 text-sky-400 flex items-center justify-center text-base shrink-0">
                    🔐
                  </span>
                  <div>
                    <span class="font-bold text-white text-xs block group-hover:text-sky-300">Mi Bóveda de Clientes</span>
                    <span class="text-[10px] text-slate-400">Consulta tus perfiles y PINs con tu WhatsApp o CBU</span>
                  </div>
                </div>
                <span class="text-slate-500 group-hover:text-sky-400 font-bold text-sm">→</span>
              </RouterLink>

              <button 
                @click="showProfileModal = true; showNavDropdown = false"
                class="w-full flex items-center justify-between p-3 rounded-2xl bg-slate-900/70 hover:bg-slate-800/90 border border-white/[0.06] hover:border-sky-500/40 transition-all group text-left shadow-sm cursor-pointer"
              >
                <div class="flex items-center gap-3">
                  <span class="w-9 h-9 rounded-xl bg-blue-500/15 border border-blue-500/30 text-blue-400 flex items-center justify-center text-base shrink-0">
                    👤
                  </span>
                  <div>
                    <span class="font-bold text-white text-xs block group-hover:text-blue-300">
                      {{ savedUserName ? `Perfil de ${savedUserName}` : 'Mi Perfil & Comprobantes' }}
                    </span>
                    <span class="text-[10px] text-slate-400">Datos guardados para compras inmediatas y comprobantes</span>
                  </div>
                </div>
                <span class="text-slate-500 group-hover:text-blue-400 font-bold text-sm">→</span>
              </button>
            </div>
          </div>

          <!-- SECCIÓN 3: 💼 DISTRIBUIDORES & NEGOCIO MAYORISTA (RECUPERADA) -->
          <div class="space-y-2">
            <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 px-1 block flex items-center gap-1.5">
              <span>💼</span> Distribuidores & Negocio Mayorista
            </span>

            <div class="grid grid-cols-1 gap-2">
              <RouterLink 
                to="/revendedores" 
                @click="showNavDropdown = false"
                class="flex items-center justify-between p-3 rounded-2xl bg-gradient-to-r from-amber-500/15 via-orange-500/10 to-slate-900 border border-amber-500/35 hover:border-amber-400 transition-all group shadow-sm"
              >
                <div class="flex items-center gap-3">
                  <span class="w-9 h-9 rounded-xl bg-amber-500/20 border border-amber-500/40 text-amber-300 flex items-center justify-center text-base shrink-0 group-hover:scale-105 transition-transform">
                    🚀
                  </span>
                  <div>
                    <div class="flex items-center gap-2">
                      <span class="font-black text-amber-300 text-xs group-hover:text-white">Ser Revendedor Autorizado</span>
                      <span class="text-[8px] bg-amber-400 text-slate-950 px-1.5 py-0.2 rounded font-black uppercase">
                        Comisiones
                      </span>
                    </div>
                    <span class="text-[10px] text-slate-300">Gana hasta 50% revendiendo streaming con tu propia tienda</span>
                  </div>
                </div>
                <span class="text-amber-400 font-bold text-sm">→</span>
              </RouterLink>

              <RouterLink 
                to="/crm" 
                @click="showNavDropdown = false"
                class="flex items-center justify-between p-2.5 rounded-2xl bg-slate-900/60 hover:bg-slate-800/80 border border-white/[0.06] hover:border-slate-700 transition-all group text-left"
              >
                <div class="flex items-center gap-3">
                  <span class="w-8 h-8 rounded-xl bg-slate-800 border border-slate-700 text-slate-300 flex items-center justify-center text-sm shrink-0">
                    🔑
                  </span>
                  <div>
                    <span class="font-bold text-slate-300 text-xs block group-hover:text-white">Acceso a Panel de Distribuidores</span>
                    <span class="text-[10px] text-slate-500">Ingreso exclusivo para mayoristas registrados (RES-01...)</span>
                  </div>
                </div>
                <span class="text-slate-500 group-hover:text-white text-sm">→</span>
              </RouterLink>
            </div>
          </div>

          <!-- SECCIÓN 4: 🛡️ GARANTÍA & COMPROMISO OFICIAL -->
          <div class="bg-gradient-to-br from-slate-900/90 to-blue-950/30 border border-white/[0.08] rounded-2xl p-3.5 space-y-2">
            <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
              <span>🛡️</span> Garantía StreamePro 100%
            </span>
            <div class="grid grid-cols-2 gap-2 text-[10px] text-slate-300">
              <div class="flex items-center gap-1.5 bg-slate-950/60 p-2 rounded-xl border border-white/[0.04]">
                <span class="text-emerald-400 font-bold">✓</span>
                <span>PIN Privado Propio</span>
              </div>
              <div class="flex items-center gap-1.5 bg-slate-950/60 p-2 rounded-xl border border-white/[0.04]">
                <span class="text-emerald-400 font-bold">✓</span>
                <span>Reposición Rápida</span>
              </div>
              <div class="flex items-center gap-1.5 bg-slate-950/60 p-2 rounded-xl border border-white/[0.04]">
                <span class="text-emerald-400 font-bold">✓</span>
                <span>Entrega: 10m - 1h</span>
              </div>
              <div class="flex items-center gap-1.5 bg-slate-950/60 p-2 rounded-xl border border-white/[0.04]">
                <span class="text-emerald-400 font-bold">✓</span>
                <span>Soporte Oficial 24/7</span>
              </div>
            </div>
          </div>

          <!-- SECCIÓN 5: 💬 SOPORTE OFICIAL & ACCIONES RÁPIDAS -->
          <div class="space-y-2.5 pt-2 border-t border-white/[0.08]">
            <button 
              @click="openSupportWhatsApp(); showNavDropdown = false"
              class="w-full bg-gradient-to-r from-emerald-500 via-emerald-400 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-slate-950 font-black py-3 px-4 rounded-2xl transition-all flex items-center justify-center gap-2 shadow-[0_0_25px_rgba(16,185,129,0.3)] active:scale-95 cursor-pointer text-xs sm:text-sm"
            >
              <span class="text-lg">💬</span>
              <span>Soporte Oficial por WhatsApp 24/7</span>
            </button>

            <div class="flex gap-2">
              <button 
                @click="copyStoreUrl"
                class="flex-1 bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-white border border-slate-800 hover:border-slate-700 py-2.5 rounded-xl text-[11px] font-bold transition-all flex items-center justify-center gap-1.5 cursor-pointer shadow-sm"
                title="Copiar enlace oficial de la tienda"
              >
                <span>🔗</span>
                <span>Copiar Enlace Tienda</span>
              </button>
              
              <button 
                @click="showNavDropdown = false" 
                class="px-4 bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 py-2.5 rounded-xl text-[11px] font-bold transition-all cursor-pointer"
              >
                Cerrar Menú
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </header>

    <main class="flex-grow container mx-auto px-4 py-8 relative z-10 max-w-7xl">
      <RouterView />
    </main>

    <footer class="border-t border-white/[0.08] mt-auto relative z-10 bg-[#020617]/95 backdrop-blur-xl">
      <!-- Grid Principal del Footer -->
      <div class="container mx-auto px-4 py-12">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-10">
          
          <!-- Columna 1: Marca & Confianza -->
          <div class="space-y-4">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-sky-400 via-blue-600 to-indigo-600 flex items-center justify-center shadow-[0_0_15px_rgba(56,189,248,0.4)] border border-white/20">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
              </div>
              <span class="text-lg font-black tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-sky-400 via-blue-200 to-indigo-200">
                STREAMEPRO
              </span>
            </div>
            
            <p class="text-xs text-slate-400 leading-relaxed">
              Infraestructura mayorista y plataforma líder en gestión de suscripciones de streaming oficiales con entrega automatizada, PIN privado y garantía completa de reposición.
            </p>

            <div class="flex flex-wrap gap-2 pt-1">
              <span class="text-[10px] font-mono font-bold bg-slate-900 text-slate-300 border border-slate-800 px-2 py-1 rounded-lg flex items-center gap-1">
                🔒 SSL 256-Bit
              </span>
              <span class="text-[10px] font-mono font-bold bg-amber-500/10 text-amber-300 border border-amber-500/20 px-2 py-1 rounded-lg flex items-center gap-1">
                ⏱️ Entrega: 10 min a 1 hora
              </span>
              <span class="text-[10px] font-mono font-bold bg-sky-500/10 text-sky-400 border border-sky-500/20 px-2 py-1 rounded-lg flex items-center gap-1">
                🛡️ Garantía 100%
              </span>
            </div>
          </div>

          <!-- Columna 2: Clientes -->
          <div class="space-y-3 text-xs">
            <h4 class="text-xs font-black uppercase tracking-wider text-slate-200 border-b border-slate-800 pb-2">
              Área de Clientes
            </h4>
            <ul class="space-y-2 text-slate-400">
              <li>
                <RouterLink to="/" class="hover:text-brand-accent transition-colors flex items-center gap-1.5">
                  <span>🍿</span>
                  <span>Catálogo de Suscripciones</span>
                </RouterLink>
              </li>
              <li>
                <RouterLink to="/p" class="hover:text-amber-400 transition-colors flex items-center gap-1.5">
                  <span>📦</span>
                  <span>Catálogo Físico (tua.ar)</span>
                </RouterLink>
              </li>
              <li>
                <RouterLink to="/portal" class="hover:text-brand-accent transition-colors flex items-center gap-1.5">
                  <span>🔐</span>
                  <span>Bóveda de Accesos & Perfiles</span>
                </RouterLink>
              </li>
              <li>
                <button @click="showProfileModal = true" class="hover:text-sky-300 transition-colors flex items-center gap-1.5 text-left">
                  <span>👤</span>
                  <span>Mi Cuenta & Registro</span>
                </button>
              </li>
              <li>
                <span class="text-slate-500 flex items-center gap-1.5">
                  <span>💳</span>
                  <span>Transferencia Bancaria & CBU</span>
                </span>
              </li>
            </ul>
          </div>

          <!-- Columna 3: Seguridad & Beneficios para Clientes -->
          <div class="space-y-3 text-xs">
            <h4 class="text-xs font-black uppercase tracking-wider text-slate-200 border-b border-slate-800 pb-2">
              Seguridad & Beneficios
            </h4>
            <ul class="space-y-2 text-slate-400">
              <li class="flex items-center gap-1.5">
                <span>🔒</span>
                <span>PIN Privado en Cada Pantalla</span>
              </li>
              <li class="flex items-center gap-1.5">
                <span>🛡️</span>
                <span>Garantía de Reposición Oficial</span>
              </li>
              <li class="flex items-center gap-1.5">
                <span>⏱️</span>
                <span>Entrega: 10 min a 1 hora (con comprobante)</span>
              </li>
              <li class="flex items-center gap-1.5">
                <span>🔄</span>
                <span>Renovaciones sin Pérdida de Perfil</span>
              </li>
            </ul>
          </div>

          <!-- Columna 4: Estado Operativo & SLA -->
          <div class="space-y-3 text-xs">
            <h4 class="text-xs font-black uppercase tracking-wider text-slate-200 border-b border-slate-800 pb-2">
              Disponibilidad & Soporte
            </h4>
            <div class="p-3 bg-slate-900/80 rounded-xl border border-slate-800 space-y-2">
              <div class="flex items-center justify-between">
                <span class="text-slate-400 text-[11px]">Estado de Servidores</span>
                <span class="flex items-center gap-1 text-[11px] font-bold text-emerald-400">
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                  99.9% Online
                </span>
              </div>
              <div class="flex items-center justify-between border-t border-slate-800 pt-1.5">
                <span class="text-slate-400 text-[11px]">Soporte Técnico</span>
                <span class="text-slate-200 font-mono text-[11px]">24/7 Monitoreado</span>
              </div>
            </div>
            <p class="text-[11px] text-slate-500">
              La entrega de las cuentas puede demorar de 10 minutos a 1 hora. Carga tu comprobante para agilizar la entrega.
            </p>
          </div>

        </div>

        <!-- Barra Inferior de Copyright & Separación Estricta de Accesos -->
        <div class="pt-6 border-t border-slate-800/80 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500">
          <div class="flex flex-wrap items-center gap-2 text-center sm:text-left">
            <p class="text-[11px] text-slate-400 font-medium">
              &copy; 2026 StreamePro Cloud Enterprise. Todos los derechos reservados.
            </p>
            <span class="hidden sm:inline text-slate-700">•</span>
            <span class="text-[11px] text-slate-400 font-mono">
              Dirección Ejecutiva & CEO: <strong class="text-slate-300">StreamePro Cloud Enterprise</strong>
            </span>
            <span class="hidden sm:inline text-slate-700">•</span>
            <a 
              href="https://spro.li" 
              target="_blank" 
              class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-sky-500/10 hover:bg-sky-500/20 text-sky-400 font-mono text-[10px] font-bold border border-sky-500/30 transition-all shadow-sm"
              title="Creador & Derechos: https://spro.li"
            >
              <span>⚖️</span>
              <span>Creador & Derechos: https://spro.li</span>
            </a>
          </div>

          <!-- SI ESTÁ EN EL PANEL DE REVENDEDORES -->
          <div v-if="isResellerArea" class="flex items-center gap-2 text-[11px] text-slate-400 font-mono">
            <span class="w-2 h-2 rounded-full bg-blue-400"></span>
            <span>Entorno Privado de Distribuidor</span>
          </div>

          <!-- SI ESTÁ EN EL PANEL DE PROVEEDOR (/myprove) -->
          <div v-else-if="isAdminArea" class="flex items-center gap-2 text-[11px] text-amber-400 font-mono">
            <span class="w-2 h-2 rounded-full bg-amber-400"></span>
            <span>Centro Mayorista Proveedor Activo</span>
          </div>

          <!-- EN VISTA GENERAL DE CLIENTES -->
          <div v-else class="flex items-center gap-3 text-xs text-slate-500 font-mono text-[11px]">
            <span class="flex items-center gap-1 text-slate-400">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
              Cuentas 100% Oficiales
            </span>
            <span class="text-slate-700">•</span>
            <span>Servicio Protegido SSL</span>
          </div>
        </div>
      </div>
    </footer>

    <!-- Modal de Mi Cuenta & Registro Rápido -->
    <UserProfileModal v-model="showProfileModal" @profileSaved="handleProfileSaved" />

    <!-- Banner & Acceso PWA / Notificaciones Push Multi-dispositivo -->
    <PwaInstallPrompt />

    <!-- Toasts Container -->
    <div class="fixed bottom-6 right-6 z-[100] flex flex-col gap-3 pointer-events-none">
      <TransitionGroup name="toast">
        <div v-for="toast in toastStore.toasts" :key="toast.id" 
             class="pointer-events-auto flex items-center gap-3 px-4 py-3 rounded-lg shadow-2xl shadow-black/50 border min-w-[250px] transform transition-all duration-300"
             :class="toast.type === 'success' ? 'bg-emerald-950/80 border-emerald-500/50 text-emerald-100' : 
                     toast.type === 'error' ? 'bg-red-950/80 border-red-500/50 text-red-100' : 
                     'bg-slate-800/80 border-slate-600 text-white'">
          
          <svg v-if="toast.type === 'success'" class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
          <svg v-if="toast.type === 'error'" class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
          
          <p class="text-sm font-medium">{{ toast.message }}</p>
        </div>
      </TransitionGroup>
    </div>
  </div>
</template>

<style>
/* Toast Animations */
.toast-enter-active,
.toast-leave-active {
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}
.toast-enter-from {
  opacity: 0;
  transform: translateX(50px) scale(0.9);
}
.toast-leave-to {
  opacity: 0;
  transform: translateY(20px) scale(0.9);
}
</style>

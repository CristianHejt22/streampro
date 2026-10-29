<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router'
import { useToastStore } from './store/toast'
import { useAdminStore, useResellerStore } from './store/admin'
import { useCartStore } from './store/cart'
import PwaInstallPrompt from './components/PwaInstallPrompt.vue'
import UserProfileModal from './components/UserProfileModal.vue'

const route = useRoute()
const router = useRouter()
const toastStore = useToastStore()
const adminStore = useAdminStore()
const resellerStore = useResellerStore()
const cartStore = useCartStore()

const showProfileModal = ref(false)
const savedUserName = ref('')

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
  adminStore.ensurePhysicalProductsLoaded()
})

const handleProfileSaved = (prof) => {
  savedUserName.value = prof.name ? prof.name.split(' ')[0] : ''
}

const isClientArea = computed(() => {
  const p = route.path.toLowerCase()
  return [
    '/', '/suscripciones', '/streaming', '/digital',
    '/combos', '/paquetes', '/ofertas',
    '/tienda', '/catalogo', '/productos', '/p',
    '/portal', '/cliente', '/clientes', '/boveda', '/portal-cliente',
    '/revendedores', '/unete', '/distribuidores', '/quiero-ser-revendedor', '/trabaja-con-nosotros'
  ].some(allowed => p === allowed || p.startsWith(allowed + '/'))
})
const isResellerArea = computed(() => route.name === 'crm' || ['/crm', '/cm', '/reseller', '/revendedor', '/panel', '/c'].includes(route.path))
const isAdminArea = computed(() => route.name === 'myprove' || ['/myprove', '/myprov', '/proveedor', '/admin-proveedor', '/prove', '/admin'].includes(route.path))

const publicPhysicalCount = computed(() => {
  return (adminStore.physicalProducts || []).filter(p => !p.adminOnly && p.categoryId !== 'paginas').length
})

const activePackagesCount = computed(() => {
  return (adminStore.packages || []).filter(p => p.active).length
})

const handleOpenCart = () => {
  cartStore.openDrawer()
}
</script>

<template>
  <div class="min-h-screen bg-[#020617] text-white flex flex-col font-sans selection:bg-brand-accent selection:text-white relative overflow-hidden">
    <!-- Ambient Background Gradients -->
    <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] rounded-full bg-brand-accent/20 blur-[120px] pointer-events-none"></div>
    <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] rounded-full bg-blue-600/10 blur-[120px] pointer-events-none"></div>

    <header class="bg-[#030712]/90 backdrop-blur-xl border-b border-white/[0.08] sticky top-0 z-50 transition-all shadow-lg shadow-black/20">
      <div class="container mx-auto px-3 sm:px-4 py-2.5 sm:py-3">
        <!-- Fila Principal: Logo + Status + Acciones / Navegación -->
        <div class="flex items-center justify-between gap-3">
          <!-- 1. Logo & Identidad de Marca -->
          <RouterLink to="/" class="flex items-center gap-2.5 sm:gap-3 group shrink-0">
            <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-gradient-to-br from-sky-400 via-blue-600 to-indigo-600 flex items-center justify-center shadow-[0_0_20px_rgba(56,189,248,0.4)] border border-white/20 group-hover:scale-105 transition-transform">
              <svg class="w-4 h-4 sm:w-5 sm:h-5 text-white drop-shadow" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div>
              <div class="flex items-center gap-1.5 sm:gap-2">
                <h1 class="text-lg sm:text-xl font-black text-transparent bg-clip-text bg-gradient-to-r from-sky-400 via-blue-300 to-indigo-300 tracking-tight">STREAMEPRO</h1>
                <span v-if="isClientArea" class="text-[9px] sm:text-[10px] font-mono font-black uppercase tracking-wider bg-sky-500/10 text-sky-400 border border-sky-500/20 px-1.5 py-0.2 rounded">Oficial</span>
                <span v-else-if="isResellerArea" class="text-[9px] sm:text-[10px] font-mono font-black uppercase tracking-wider bg-blue-500/20 text-blue-300 border border-blue-500/30 px-1.5 py-0.2 rounded">Distribuidores</span>
                <span v-else-if="isAdminArea" class="text-[9px] sm:text-[10px] font-mono font-black uppercase tracking-wider bg-amber-500/20 text-amber-300 border border-amber-500/30 px-1.5 py-0.2 rounded">Mayorista Central</span>
              </div>
              <p class="hidden sm:block text-[10px] text-slate-500 font-medium tracking-wide">
                {{ isClientArea ? 'Tienda Oficial de Cuentas Premium & Tecnología' : isResellerArea ? 'Panel Exclusivo de Distribuidores' : 'Centro de Control & Inventario Proveedor' }}
              </p>
            </div>
          </RouterLink>

          <!-- 2. Badge de Garantía (Visible en Pantallas Grandes) -->
          <div class="hidden xl:flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-semibold">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            <span>Garantía Oficial • Entrega: 10 min a 1 hora</span>
          </div>

          <!-- 3. Navegación Desktop para Clientes (lg / xl) -->
          <nav v-if="isClientArea" class="hidden lg:flex items-center gap-1 text-xs font-bold bg-slate-900/70 p-1.5 rounded-2xl border border-white/[0.08] shadow-inner shrink-0">
            <RouterLink 
              to="/" 
              class="px-3 py-2 rounded-xl transition-all flex items-center gap-1.5 whitespace-nowrap cursor-pointer"
              :class="$route.path === '/' ? 'bg-gradient-to-r from-sky-400 via-indigo-500 to-amber-400 text-slate-950 font-black shadow-sm' : 'text-slate-300 hover:text-white hover:bg-white/[0.05]'"
            >
              <span>🌟</span>
              <span>Inicio / Ofertas</span>
            </RouterLink>
            
            <RouterLink 
              to="/suscripciones" 
              class="px-3 py-2 rounded-xl transition-all flex items-center gap-1.5 whitespace-nowrap cursor-pointer"
              :class="['/suscripciones', '/streaming', '/digital'].includes($route.path) ? 'bg-sky-500/20 text-sky-300 border border-sky-500/40 shadow-sm font-black' : 'text-slate-300 hover:text-white hover:bg-white/[0.05]'"
            >
              <span>🎬</span>
              <span>Suscripciones</span>
            </RouterLink>

            <RouterLink 
              to="/combos" 
              class="px-3 py-2 rounded-xl transition-all flex items-center gap-1.5 whitespace-nowrap cursor-pointer"
              :class="['/combos', '/paquetes', '/ofertas'].includes($route.path) ? 'bg-purple-500/20 text-pink-300 border border-purple-500/40 shadow-sm font-black' : 'text-slate-300 hover:text-white hover:bg-white/[0.05]'"
            >
              <span>🎁</span>
              <span>Combos</span>
              <span v-if="activePackagesCount > 0" class="bg-pink-400/20 text-pink-300 text-[10px] px-1.5 py-0.2 rounded-full font-mono font-bold border border-pink-400/30">
                {{ activePackagesCount }}
              </span>
            </RouterLink>
            
            <RouterLink 
              to="/tienda" 
              class="px-3 py-2 rounded-xl transition-all flex items-center gap-1.5 whitespace-nowrap cursor-pointer"
              :class="['/tienda', '/catalogo', '/productos', '/p'].includes($route.path) ? 'bg-amber-500/20 text-amber-300 border border-amber-500/40 shadow-sm font-black' : 'text-slate-300 hover:text-white hover:bg-white/[0.05]'"
            >
              <span>📦</span>
              <span>Tienda Física</span>
              <span class="bg-amber-400/20 text-amber-300 text-[10px] px-1.5 py-0.2 rounded-full font-mono font-bold border border-amber-400/30">
                {{ publicPhysicalCount }}
              </span>
            </RouterLink>

            <RouterLink 
              to="/portal" 
              class="px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-white/[0.05] transition-all flex items-center gap-1.5 whitespace-nowrap" 
              active-class="!bg-sky-500/15 !text-sky-300 !border !border-sky-500/30 shadow-sm"
            >
              <span>🔐</span>
              <span>Mi Bóveda</span>
            </RouterLink>

            <RouterLink 
              to="/revendedores" 
              class="px-3 py-2 rounded-xl text-amber-300 hover:text-white bg-amber-500/10 hover:bg-amber-500/20 border border-amber-500/20 transition-all flex items-center gap-1.5 font-bold whitespace-nowrap" 
              active-class="!bg-amber-500/20 !text-amber-200 !border-amber-500/40 shadow-sm"
            >
              <span>🚀</span>
              <span>Revendedores</span>
            </RouterLink>
          </nav>

          <!-- 4. Acciones Principales (Mi Cuenta + Carrito) -->
          <div v-if="isClientArea" class="flex items-center gap-2 shrink-0">
            <!-- Acceso Mi Cuenta / Perfil -->
            <button 
              @click="showProfileModal = true" 
              class="px-2.5 sm:px-3 py-2 rounded-xl text-sky-300 hover:text-white bg-sky-500/10 hover:bg-sky-500/20 border border-sky-500/30 transition-all flex items-center gap-1.5 font-bold text-xs shadow-sm cursor-pointer"
              title="Mi Perfil, compras y registro"
            >
              <span class="text-sm">👤</span>
              <span class="max-w-[70px] sm:max-w-[100px] truncate">{{ savedUserName ? savedUserName : 'Mi Cuenta' }}</span>
            </button>

            <!-- Botón Ver Carrito Pro con Contador Dinámico -->
            <button 
              @click="handleOpenCart" 
              class="relative bg-gradient-to-r from-sky-400 via-blue-500 to-indigo-600 hover:from-sky-300 hover:to-indigo-500 text-slate-950 font-black px-3 sm:px-4 py-2 rounded-xl transition-all shadow-[0_0_15px_rgba(56,189,248,0.25)] hover:shadow-[0_0_20px_rgba(56,189,248,0.4)] flex items-center gap-1.5 text-xs cursor-pointer active:scale-95"
              title="Abrir Carrito de Compras"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
              <span class="hidden sm:inline">Carrito</span>
              <span v-if="cartStore.totalItems > 0" class="bg-amber-400 text-slate-950 text-[10px] font-black w-5 h-5 rounded-full flex items-center justify-center font-mono animate-pulse shadow-sm">
                {{ cartStore.totalItems }}
              </span>
            </button>
          </div>

          <!-- Reseller Navigation -->
          <div v-else-if="isResellerArea" class="flex items-center gap-2">
            <div v-if="resellerStore.isAuthenticated" class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-800 text-xs">
              <span class="text-slate-400">Distribuidor:</span>
              <strong class="text-amber-400 font-mono">[{{ resellerStore.activeResellerId }}]</strong>
              <span class="text-emerald-400 font-mono font-bold">${{ resellerStore.wallet }} ARS</span>
            </div>

            <button 
              @click="showProfileModal = true" 
              class="px-3 py-2 rounded-xl text-sky-300 hover:text-white bg-slate-900 hover:bg-slate-800 border border-slate-800 text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer"
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

          <!-- Admin Navigation -->
          <div v-else-if="isAdminArea" class="flex flex-wrap items-center gap-2">
            <span class="hidden sm:inline-block text-xs font-mono font-bold text-amber-300 bg-amber-500/15 border border-amber-500/30 px-3 py-1.5 rounded-xl">
              Panel Mayorista Proveedor
            </span>
            <RouterLink 
              to="/" 
              class="text-xs font-bold px-3 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-sky-300 hover:text-white border border-sky-500/30 transition-all flex items-center gap-1.5"
              title="Ver Tienda Pública completa de suscripciones y catálogo"
            >
              <span>🎬 Tienda Pública</span>
            </RouterLink>
            <RouterLink 
              to="/p" 
              class="text-xs font-bold px-3 py-2 rounded-xl bg-amber-500/15 hover:bg-amber-500/25 text-amber-300 hover:text-white border border-amber-500/30 transition-all flex items-center gap-1.5"
              title="Ver Catálogo Físico"
            >
              <span>📦 Catálogo Físico ({{ publicPhysicalCount }})</span>
            </RouterLink>
          </div>
        </div>

        <!-- Tier 2 para Móviles y Tablets (< lg): Barra de Pestañas con Desplazamiento Horizontal Suave -->
        <div v-if="isClientArea" class="lg:hidden mt-2 pt-2 border-t border-white/[0.06] flex items-center gap-1.5 overflow-x-auto no-scrollbar text-xs font-bold py-1">
          <RouterLink 
            to="/" 
            class="px-3 py-1.5 rounded-xl transition-all flex items-center gap-1.5 cursor-pointer shrink-0 whitespace-nowrap"
            :class="$route.path === '/' ? 'bg-gradient-to-r from-sky-400 via-indigo-500 to-amber-400 text-slate-950 font-black shadow-sm' : 'bg-slate-900/80 text-slate-300 border border-white/[0.06] hover:bg-slate-800'"
          >
            <span>🌟</span>
            <span>Inicio / Ofertas</span>
          </RouterLink>
          
          <RouterLink 
            to="/suscripciones" 
            class="px-3 py-1.5 rounded-xl transition-all flex items-center gap-1.5 cursor-pointer shrink-0 whitespace-nowrap"
            :class="['/suscripciones', '/streaming', '/digital'].includes($route.path) ? 'bg-sky-500/20 text-sky-300 border border-sky-500/40 font-black shadow-sm' : 'bg-slate-900/80 text-slate-300 border border-white/[0.06] hover:bg-slate-800'"
          >
            <span>🎬</span>
            <span>Suscripciones</span>
          </RouterLink>

          <RouterLink 
            to="/combos" 
            class="px-3 py-1.5 rounded-xl transition-all flex items-center gap-1.5 cursor-pointer shrink-0 whitespace-nowrap"
            :class="['/combos', '/paquetes', '/ofertas'].includes($route.path) ? 'bg-purple-500/20 text-pink-300 border border-purple-500/40 font-black shadow-sm' : 'bg-slate-900/80 text-slate-300 border border-white/[0.06] hover:bg-slate-800'"
          >
            <span>🎁</span>
            <span>Combos</span>
            <span v-if="activePackagesCount > 0" class="bg-pink-400/20 text-pink-300 text-[10px] px-1.5 py-0.2 rounded-full font-mono font-bold border border-pink-400/30">
              {{ activePackagesCount }}
            </span>
          </RouterLink>
          
          <RouterLink 
            to="/tienda" 
            class="px-3 py-1.5 rounded-xl transition-all flex items-center gap-1.5 cursor-pointer shrink-0 whitespace-nowrap"
            :class="['/tienda', '/catalogo', '/productos', '/p'].includes($route.path) ? 'bg-amber-500/20 text-amber-300 border border-amber-500/40 font-black shadow-sm' : 'bg-slate-900/80 text-slate-300 border border-white/[0.06] hover:bg-slate-800'"
          >
            <span>📦</span>
            <span>Físicos</span>
            <span class="bg-amber-400/20 text-amber-300 text-[10px] px-1.5 py-0.2 rounded-full font-mono font-bold border border-amber-400/30">
              {{ publicPhysicalCount }}
            </span>
          </RouterLink>

          <RouterLink 
            to="/portal" 
            class="px-3 py-1.5 rounded-xl bg-slate-900/80 text-slate-300 border border-white/[0.06] hover:bg-slate-800 transition-all flex items-center gap-1.5 shrink-0 whitespace-nowrap" 
            active-class="!bg-sky-500/15 !text-sky-300 !border-sky-500/40 font-bold"
          >
            <span>🔐</span>
            <span>Mi Bóveda</span>
          </RouterLink>

          <RouterLink 
            to="/revendedores" 
            class="px-3 py-1.5 rounded-xl bg-amber-500/10 text-amber-300 border border-amber-500/20 hover:bg-amber-500/20 transition-all flex items-center gap-1.5 shrink-0 whitespace-nowrap font-bold" 
            active-class="!bg-amber-500/25 !text-amber-200 !border-amber-500/40"
          >
            <span>🚀</span>
            <span>Revendedores</span>
          </RouterLink>
        </div>
      </div>
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
                  <span>Catálogo Físico Oficial</span>
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
/* Scrollbar invisible para pill-bar en móviles */
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

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

<script setup>
import { ref, computed, onMounted, onUnmounted, defineAsyncComponent, watch } from 'vue'
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router'
import { useToastStore } from './store/toast'
import { useAdminStore, useResellerStore } from './store/admin'
import { useCartStore } from './store/cart'
import { getRememberedSession } from './services/biometrics'
import PwaInstallPrompt from './components/PwaInstallPrompt.vue'

const UserProfileModal = defineAsyncComponent(() => import('./components/UserProfileModal.vue'))
const CustomerToolsHubModal = defineAsyncComponent(() => import('./components/CustomerToolsHubModal.vue'))

const route = useRoute()
const router = useRouter()
const toastStore = useToastStore()
const adminStore = useAdminStore()
const resellerStore = useResellerStore()
const cartStore = useCartStore()

const showProfileModal = ref(false)
const savedUserName = ref('')
const showCustomerToolsModal = ref(false)
const customerToolsModalTab = ref('calculator')

const openCustomerTools = (tab = 'calculator') => {
  customerToolsModalTab.value = tab
  showCustomerToolsModal.value = true
}

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

watch(() => adminStore.seoConfig, (cfg) => {
  if (cfg && typeof document !== 'undefined') {
    if (cfg.siteTitle && !route.meta?.title) {
      document.title = cfg.siteTitle
    }
    if (cfg.metaDescription) {
      const metaDesc = document.querySelector('meta[name="description"]')
      if (metaDesc) metaDesc.setAttribute('content', cfg.metaDescription)
    }
    if (cfg.keywords) {
      const metaKey = document.querySelector('meta[name="keywords"]')
      if (metaKey) metaKey.setAttribute('content', cfg.keywords)
    }
  }
}, { immediate: true, deep: true })

const showScrollTop = ref(false)
const handleScroll = () => {
  if (typeof window !== 'undefined') {
    showScrollTop.value = window.scrollY > 350
  }
}
const scrollToTop = () => {
  if (typeof window !== 'undefined') {
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

onMounted(() => {
  checkSavedUser()
  adminStore.ensurePhysicalProductsLoaded()

  // Sincronización en la nube con Firebase Firestore para todos los clientes en tiempo real
  adminStore.initStoreSettingsSync().catch(() => {})

  // Monitor de estado de conectividad en tiempo real y scroll
  if (typeof window !== 'undefined') {
    window.addEventListener('online', () => {
      toastStore.add('🟢 Conexión a internet restablecida.', 'success')
    })
    window.addEventListener('offline', () => {
      toastStore.add('⚠️ Modo sin conexión: Navegando en catálogo offline con respaldo local.', 'warning')
    })
    window.addEventListener('scroll', handleScroll, { passive: true })
  }

  // Sincronización en tiempo real protegida y con debounce entre pestañas
  if (typeof window !== 'undefined') {
    let syncDebounceTimer = null
    const handleSync = (key) => {
      if (syncDebounceTimer) clearTimeout(syncDebounceTimer)
      syncDebounceTimer = setTimeout(() => {
        adminStore.reloadKeyFromStorage(key)
      }, 60)
    }

    if ('BroadcastChannel' in window) {
      const bc = new BroadcastChannel('streamepro_catalog_sync')
      bc.onmessage = (event) => {
        if (event.data && event.data.type === 'DB_CHANGED') {
          handleSync(event.data.key)
        }
      }
    } else {
      window.addEventListener('storage', (e) => {
        if (!e.key) return
        const cleanKey = e.key.replace('streampro_prod_v2_', '')
        handleSync(cleanKey)
      })
    }
  }
})

onUnmounted(() => {
  if (typeof window !== 'undefined') {
    window.removeEventListener('scroll', handleScroll)
  }
})

const handleProfileSaved = (prof) => {
  savedUserName.value = prof.name ? prof.name.split(' ')[0] : ''
}

const isRecruitmentArea = computed(() => {
  const p = route.path.toLowerCase()
  return [
    '/revendedores', '/unete', '/distribuidores', '/quiero-ser-revendedor', '/trabaja-con-nosotros'
  ].some(allowed => p === allowed || p.startsWith(allowed + '/'))
})

const isClientArea = computed(() => {
  if (isRecruitmentArea.value) return false
  if (isResellerArea.value || isAdminArea.value) return false
  return true
})
const isResellerArea = computed(() => route.name === 'crm' || ['/crm', '/cm', '/reseller', '/revendedor', '/panel', '/c'].includes(route.path))
const isAdminArea = computed(() => route.name === 'myprove' || ['/myprove', '/myprov', '/proveedor', '/admin-proveedor', '/prove', '/admin'].includes(route.path))

const supportWhatsAppUrl = computed(() => {
  const phone = adminStore.providerBankInfo?.whatsapp || adminStore.adminAuth?.whatsapp || '5492915658321'
  let cleanPhone = String(phone).replace(/[^0-9]/g, '')
  if (cleanPhone.startsWith('0')) cleanPhone = cleanPhone.substring(1)
  if (cleanPhone.length === 10) cleanPhone = `549${cleanPhone}`
  else if (cleanPhone.length === 11 && cleanPhone.startsWith('15')) cleanPhone = `54911${cleanPhone.substring(2)}`
  else if (cleanPhone.length === 12 && cleanPhone.startsWith('54') && !cleanPhone.startsWith('549')) cleanPhone = `549${cleanPhone.substring(2)}`
  return `https://wa.me/${cleanPhone}?text=${encodeURIComponent('¡Hola! Me comunico desde StreamePro para consultar por productos y soporte.')}`
})

const isAdminUser = computed(() => {
  try {
    const saved = getRememberedSession('admin')
    if (saved && (saved.username || saved.id === 'admin')) return true
    if (route.query.preview === 'admin') return true
  } catch (e) {}
  return false
})

const currentResellerShop = computed(() => {
  const slug = route.params.shopSlug || (typeof localStorage !== 'undefined' ? localStorage.getItem('streamepro_shop_slug') : '')
  if (slug) {
    return adminStore.resolveResellerBySlug(slug)
  }
  const refId = typeof localStorage !== 'undefined' ? localStorage.getItem('streamepro_referral') : ''
  if (refId && refId !== 'DIRECT') {
    return adminStore.resellers?.find(r => r.id === refId) || null
  }
  return null
})

const resellerShopConfig = computed(() => {
  return currentResellerShop.value?.shopConfig || null
})

const homeLink = computed(() => {
  if (route.params.shopSlug) {
    return `/${route.params.shopSlug}`
  }
  return '/'
})

const getAppSectionLink = (sec) => {
  if (route.params.shopSlug) {
    return `/${route.params.shopSlug}/${sec}`
  }
  return `/${sec}`
}

const isStreamingNavVisible = computed(() => {
  if (resellerShopConfig.value) {
    return resellerShopConfig.value.showStreaming !== false
  }
  return true
})

const isCombosNavVisible = computed(() => {
  if (resellerShopConfig.value) {
    return resellerShopConfig.value.showCombos !== false
  }
  return true
})

const isGamingNavVisible = computed(() => {
  if (resellerShopConfig.value) {
    return resellerShopConfig.value.showGaming !== false
  }
  return true
})

const isPhysicalNavVisible = computed(() => {
  const base = Boolean(adminStore.isStoreVisible && adminStore.isPhysicalStoreVisible)
  if (!base) return false
  if (resellerShopConfig.value) {
    return Boolean(resellerShopConfig.value.showPhysical)
  }
  return true
})

const publicPhysicalCount = computed(() => {
  if (!isPhysicalNavVisible.value) return 0
  return (adminStore.physicalProducts || []).filter(p => !p.adminOnly && p.categoryId !== 'paginas').length
})

const activePackagesCount = computed(() => {
  return (adminStore.packages || []).filter(p => p.active).length
})

const activeGiveawaysCount = computed(() => {
  return (adminStore.activeGiveaways || []).length
})

const isTabActive = (tab) => {
  const p = route.path
  const sec = route.params.section
  if (tab === 'home') return p === '/' || (route.params.shopSlug && !sec)
  if (tab === 'streaming') return ['/suscripciones', '/streaming', '/digital'].includes(p) || sec === 'suscripciones'
  if (tab === 'combos') return ['/combos', '/paquetes', '/ofertas'].includes(p) || sec === 'combos'
  if (tab === 'juegos') return ['/juegos', '/gaming', '/videojuegos', '/ps5', '/ps4', '/ps3', '/playstation'].some(x => p === x || p.startsWith(x + '/')) || sec === 'juegos'
  if (tab === 'sorteos') return ['/sorteos', '/sorteo', '/descuentos', '/embajadores'].includes(p) || sec === 'sorteos'
  return false
}

const handleOpenCart = () => {
  cartStore.openDrawer()
}
</script>

<template>
  <div class="min-h-screen bg-[#020617] text-white flex flex-col font-sans selection:bg-brand-accent selection:text-white relative overflow-hidden">
    <!-- Ambient Background Gradients -->
    <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] rounded-full bg-brand-accent/20 blur-[120px] pointer-events-none"></div>
    <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] rounded-full bg-blue-600/10 blur-[120px] pointer-events-none"></div>

    <header v-if="!isRecruitmentArea" class="bg-[#030712]/90 backdrop-blur-xl border-b border-white/[0.08] sticky top-0 z-30 transition-all shadow-lg shadow-black/20">
      <div class="container mx-auto px-3 sm:px-4 py-2.5 sm:py-3">
        <!-- Fila Principal: Logo + Status + Acciones / Navegación -->
        <div class="flex items-center justify-between gap-3">
          <!-- 1. Logo & Identidad de Marca -->
          <RouterLink :to="homeLink" class="flex items-center gap-2.5 sm:gap-3 group shrink-0">
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
          <div class="hidden 2xl:flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-semibold shrink-0">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            <span>Garantía Oficial • Entrega 10-60 min</span>
          </div>

          <!-- 3. Navegación Desktop para Clientes (lg / xl) -->
          <nav v-if="isClientArea" class="hidden lg:flex items-center gap-1 text-xs font-bold bg-slate-900/60 backdrop-blur-xl p-1 rounded-2xl border border-white/[0.08] shadow-inner shrink-0">
            <RouterLink 
              :to="homeLink" 
              class="px-3 py-1.5 rounded-xl transition-all flex items-center gap-1.5 whitespace-nowrap cursor-pointer"
              :class="($route.path === '/' || ($route.params.shopSlug && !$route.params.section)) ? 'bg-sky-500/20 text-sky-300 border border-sky-500/40 font-black shadow-sm' : 'text-slate-300 hover:text-white hover:bg-white/[0.05]'"
            >
              <span>🌟</span>
              <span>Inicio</span>
            </RouterLink>
            
            <RouterLink 
              v-if="isStreamingNavVisible"
              :to="getAppSectionLink('suscripciones')" 
              class="px-3 py-1.5 rounded-xl transition-all flex items-center gap-1.5 whitespace-nowrap cursor-pointer"
              :class="(['/suscripciones', '/streaming', '/digital'].includes($route.path) || $route.params.section === 'suscripciones') ? 'bg-sky-500/20 text-sky-300 border border-sky-500/40 font-black shadow-sm' : 'text-slate-300 hover:text-white hover:bg-white/[0.05]'"
            >
              <span>🎬</span>
              <span>Suscripciones</span>
            </RouterLink>

            <RouterLink 
              v-if="isCombosNavVisible"
              :to="getAppSectionLink('combos')" 
              class="px-3 py-1.5 rounded-xl transition-all flex items-center gap-1.5 whitespace-nowrap cursor-pointer"
              :class="(['/combos', '/paquetes', '/ofertas'].includes($route.path) || $route.params.section === 'combos') ? 'bg-purple-500/20 text-pink-300 border border-purple-500/40 font-black shadow-sm' : 'text-slate-300 hover:text-white hover:bg-white/[0.05]'"
            >
              <span>🎁</span>
              <span>Combos</span>
              <span v-if="activePackagesCount > 0" class="bg-pink-400/20 text-pink-300 text-[10px] px-1.5 py-0.2 rounded-full font-mono font-bold border border-pink-400/30">
                {{ activePackagesCount }}
              </span>
            </RouterLink>

            <RouterLink 
              v-if="isGamingNavVisible"
              :to="getAppSectionLink('juegos')" 
              class="px-3 py-1.5 rounded-xl transition-all flex items-center gap-1.5 whitespace-nowrap cursor-pointer"
              :class="(['/juegos', '/gaming', '/videojuegos', '/ps5', '/ps4', '/ps3', '/playstation'].some(p => $route.path === p || $route.path.startsWith(p + '/')) || $route.params.section === 'juegos') ? 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/40 font-black shadow-sm' : 'text-slate-300 hover:text-white hover:bg-white/[0.05]'"
            >
              <span>🎮</span>
              <span>Juegos</span>
              <span class="bg-indigo-400/20 text-indigo-300 text-[10px] px-1.5 py-0.2 rounded-full font-mono font-bold border border-indigo-400/30">
                +15K
              </span>
            </RouterLink>
            
            <RouterLink 
              v-if="isPhysicalNavVisible"
              :to="getAppSectionLink('tienda')" 
              class="px-3 py-1.5 rounded-xl transition-all flex items-center gap-1.5 whitespace-nowrap cursor-pointer"
              :class="(['/tienda', '/catalogo', '/productos', '/p'].includes($route.path) || $route.params.section === 'tienda') ? 'bg-amber-500/20 text-amber-300 border border-amber-500/40 font-black shadow-sm' : 'text-slate-300 hover:text-white hover:bg-white/[0.05]'"
            >
              <span>📦</span>
              <span>Físicos</span>
              <span class="bg-amber-400/20 text-amber-300 text-[10px] px-1.5 py-0.2 rounded-full font-mono font-bold border border-amber-400/30">
                {{ publicPhysicalCount }}
              </span>
            </RouterLink>

            <RouterLink 
              :to="getAppSectionLink('sorteos')" 
              class="px-3 py-1.5 rounded-xl transition-all flex items-center gap-1.5 whitespace-nowrap cursor-pointer"
              :class="(['/sorteos', '/sorteo', '/descuentos', '/embajadores'].includes($route.path) || $route.params.section === 'sorteos') ? 'bg-amber-500/20 text-amber-300 border border-amber-500/40 font-black shadow-sm' : 'text-slate-300 hover:text-white hover:bg-white/[0.05]'"
            >
              <span>🎉</span>
              <span>Sorteos</span>
              <span v-if="activeGiveawaysCount > 0" class="bg-amber-400/20 text-amber-300 text-[10px] px-1.5 py-0.2 rounded-full font-mono font-bold border border-amber-400/30">
                {{ activeGiveawaysCount }}
              </span>
            </RouterLink>

            <!-- Botón Acceso Rápido a Centro de Herramientas & Calculadora -->
            <button 
              type="button"
              @click="openCustomerTools('calculator')" 
              class="px-3 py-1.5 rounded-xl text-sky-300 hover:text-white bg-sky-500/10 hover:bg-sky-500/20 border border-sky-500/30 transition-all flex items-center gap-1.5 font-bold whitespace-nowrap cursor-pointer shadow-sm group" 
              title="Calculadora de Ahorro, Rastreo de Órdenes y Herramientas"
            >
              <span class="group-hover:rotate-12 transition-transform">🔧</span>
              <span>Herramientas</span>
              <span class="bg-emerald-400 text-slate-950 text-[9px] font-black px-1.5 py-0.2 rounded-full font-mono">
                AHORRO
              </span>
            </button>

            <RouterLink 
              to="/portal" 
              class="px-3 py-1.5 rounded-xl text-slate-300 hover:text-white hover:bg-white/[0.05] transition-all flex items-center gap-1.5 whitespace-nowrap" 
              active-class="!bg-sky-500/15 !text-sky-300 !border !border-sky-500/30 font-bold shadow-sm"
            >
              <span>🔐</span>
              <span>Mi Bóveda</span>
            </RouterLink>

            <RouterLink 
              to="/revendedores" 
              class="px-3 py-1.5 rounded-xl text-amber-300 hover:text-white bg-amber-500/10 hover:bg-amber-500/20 border border-amber-500/20 transition-all flex items-center gap-1.5 font-bold whitespace-nowrap" 
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
              <span class="font-black text-xs">Carrito</span>
              <span v-if="cartStore.totalItems > 0" class="bg-amber-400 text-slate-950 text-[10px] font-black min-w-5 h-5 px-1 rounded-full flex items-center justify-center font-mono animate-pulse shadow-sm">
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
            :to="homeLink" 
            class="px-3 py-1.5 rounded-xl transition-all flex items-center gap-1.5 cursor-pointer shrink-0 whitespace-nowrap"
            :class="($route.path === '/' || ($route.params.shopSlug && !$route.params.section)) ? 'bg-sky-500/20 text-sky-300 border border-sky-500/40 font-black shadow-sm' : 'bg-slate-900/70 text-slate-300 border border-white/[0.06] hover:bg-slate-800'"
          >
            <span>🌟</span>
            <span>Inicio</span>
          </RouterLink>
          
          <RouterLink 
            v-if="isStreamingNavVisible"
            :to="getAppSectionLink('suscripciones')" 
            class="px-3 py-1.5 rounded-xl transition-all flex items-center gap-1.5 cursor-pointer shrink-0 whitespace-nowrap"
            :class="(['/suscripciones', '/streaming', '/digital'].includes($route.path) || $route.params.section === 'suscripciones') ? 'bg-sky-500/20 text-sky-300 border border-sky-500/40 font-black shadow-sm' : 'bg-slate-900/70 text-slate-300 border border-white/[0.06] hover:bg-slate-800'"
          >
            <span>🎬</span>
            <span>Suscripciones</span>
          </RouterLink>

          <RouterLink 
            v-if="isCombosNavVisible"
            :to="getAppSectionLink('combos')" 
            class="px-3 py-1.5 rounded-xl transition-all flex items-center gap-1.5 cursor-pointer shrink-0 whitespace-nowrap"
            :class="(['/combos', '/paquetes', '/ofertas'].includes($route.path) || $route.params.section === 'combos') ? 'bg-purple-500/20 text-pink-300 border border-purple-500/40 font-black shadow-sm' : 'bg-slate-900/70 text-slate-300 border border-white/[0.06] hover:bg-slate-800'"
          >
            <span>🎁</span>
            <span>Combos</span>
            <span v-if="activePackagesCount > 0" class="bg-pink-400/20 text-pink-300 text-[10px] px-1.5 py-0.2 rounded-full font-mono font-bold border border-pink-400/30">
              {{ activePackagesCount }}
            </span>
          </RouterLink>

          <RouterLink 
            v-if="isGamingNavVisible"
            :to="getAppSectionLink('juegos')" 
            class="px-3 py-1.5 rounded-xl transition-all flex items-center gap-1.5 cursor-pointer shrink-0 whitespace-nowrap"
            :class="(['/juegos', '/gaming', '/videojuegos', '/ps5', '/ps4', '/ps3', '/playstation'].some(p => $route.path === p || $route.path.startsWith(p + '/')) || $route.params.section === 'juegos') ? 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/40 font-black shadow-sm' : 'bg-slate-900/70 text-slate-300 border border-white/[0.06] hover:bg-slate-800'"
          >
            <span>🎮</span>
            <span>Juegos</span>
            <span class="bg-indigo-400/20 text-indigo-300 text-[10px] px-1.5 py-0.2 rounded-full font-mono font-bold border border-indigo-400/30">
              +15K
            </span>
          </RouterLink>
          
          <RouterLink 
            v-if="isPhysicalNavVisible"
            :to="getAppSectionLink('tienda')" 
            class="px-3 py-1.5 rounded-xl transition-all flex items-center gap-1.5 cursor-pointer shrink-0 whitespace-nowrap"
            :class="(['/tienda', '/catalogo', '/productos', '/p'].includes($route.path) || $route.params.section === 'tienda') ? 'bg-amber-500/20 text-amber-300 border border-amber-500/40 font-black shadow-sm' : 'bg-slate-900/70 text-slate-300 border border-white/[0.06] hover:bg-slate-800'"
          >
            <span>📦</span>
            <span>Físicos</span>
            <span class="bg-amber-400/20 text-amber-300 text-[10px] px-1.5 py-0.2 rounded-full font-mono font-bold border border-amber-400/30">
              {{ publicPhysicalCount }}
            </span>
          </RouterLink>

          <RouterLink 
            :to="getAppSectionLink('sorteos')" 
            class="px-3 py-1.5 rounded-xl transition-all flex items-center gap-1.5 cursor-pointer shrink-0 whitespace-nowrap"
            :class="(['/sorteos', '/sorteo', '/descuentos', '/embajadores'].includes($route.path) || $route.params.section === 'sorteos') ? 'bg-amber-500/20 text-amber-300 border border-amber-500/40 font-black shadow-sm' : 'bg-slate-900/70 text-slate-300 border border-white/[0.06] hover:bg-slate-800'"
          >
            <span>🎉</span>
            <span>Sorteos</span>
            <span v-if="activeGiveawaysCount > 0" class="bg-amber-400/20 text-amber-300 text-[10px] px-1.5 py-0.2 rounded-full font-mono font-bold border border-amber-400/30">
              {{ activeGiveawaysCount }}
            </span>
          </RouterLink>

          <!-- Acceso Rápido Móvil a Herramientas -->
          <button 
            type="button"
            @click="openCustomerTools('calculator')" 
            class="px-3 py-1.5 rounded-xl transition-all flex items-center gap-1.5 cursor-pointer shrink-0 whitespace-nowrap bg-sky-500/15 text-sky-300 border border-sky-500/30 hover:bg-sky-500/25 font-bold shadow-sm"
          >
            <span>🔧</span>
            <span>Herramientas</span>
            <span class="bg-emerald-400 text-slate-950 text-[9px] font-black px-1.5 py-0.2 rounded-full font-mono">
              PRO
            </span>
          </button>
        </div>
      </div>
    </header>

    <main :class="['flex-grow relative', isRecruitmentArea ? 'w-full p-0' : 'container mx-auto px-3 sm:px-4 py-3 sm:py-8 pb-24 sm:pb-8 max-w-7xl']">
      <RouterView />
    </main>

    <footer v-if="!isRecruitmentArea" class="border-t border-white/[0.08] mt-auto relative z-20 bg-[#020617]/95 backdrop-blur-xl">
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
            <span class="text-slate-700">•</span>
            <RouterLink to="/myprove" class="text-slate-500 hover:text-amber-400 transition-colors flex items-center gap-1 cursor-pointer" title="Acceso Panel Proveedor">
              <span>🔒</span>
              <span>Acceso Proveedor</span>
            </RouterLink>
          </div>
        </div>
      </div>
    </footer>

    <!-- Modal de Mi Cuenta & Registro Rápido -->
    <UserProfileModal v-model="showProfileModal" @profileSaved="handleProfileSaved" />

    <!-- Modal Centro de Herramientas & Calculadora StreamePro -->
    <CustomerToolsHubModal v-model="showCustomerToolsModal" :initial-tab="customerToolsModalTab" />

    <!-- Banner & Acceso PWA / Notificaciones Push Multi-dispositivo -->
    <PwaInstallPrompt />

    <!-- Botón Flotante Inteligente de Soporte WhatsApp Oficial -->
    <a 
      v-if="!isAdminArea"
      :href="supportWhatsAppUrl"
      target="_blank"
      rel="noopener noreferrer"
      class="fixed bottom-20 left-3 sm:bottom-6 sm:left-6 z-30 group flex items-center gap-2.5 px-3 py-2 sm:px-3.5 sm:py-2.5 rounded-full bg-gradient-to-r from-emerald-500 via-emerald-600 to-green-600 hover:from-emerald-400 hover:to-green-500 text-white font-bold text-xs shadow-[0_4px_20px_rgba(16,185,129,0.4)] hover:shadow-[0_4px_25px_rgba(16,185,129,0.6)] transition-all transform hover:scale-105 active:scale-95 border border-emerald-400/40 cursor-pointer"
      title="Soporte Oficial por WhatsApp"
    >
      <span class="relative flex h-3 w-3">
        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
        <span class="relative inline-flex rounded-full h-3 w-3 bg-white"></span>
      </span>
      <svg class="w-4 h-4 fill-current shrink-0" viewBox="0 0 24 24">
        <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
      </svg>
      <span class="hidden sm:inline tracking-wide">WhatsApp</span>
    </a>

    <!-- Botón Flotante Scroll-to-Top -->
    <Transition name="fade">
      <button 
        v-if="showScrollTop"
        type="button"
        @click="scrollToTop"
        class="fixed bottom-32 right-3 sm:bottom-6 sm:right-6 z-30 p-2.5 sm:p-3 rounded-full bg-slate-900/90 hover:bg-slate-800 text-sky-400 hover:text-white border border-sky-500/40 shadow-xl shadow-black/50 backdrop-blur-md transition-all transform hover:scale-110 active:scale-95 cursor-pointer"
        title="Volver al inicio"
        aria-label="Volver al inicio"
      >
        <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
        </svg>
      </button>
    </Transition>

    <!-- Dock de Navegación Inferior Móvil Premium (Estilo App Nativa con Safe-Area y SVGs) -->
    <nav 
      v-if="isClientArea"
      class="sm:hidden fixed bottom-0 inset-x-0 z-50 bg-[#060913]/92 backdrop-blur-2xl border-t border-white/[0.12] px-1.5 pt-1.5 pb-[max(0.6rem,env(safe-area-inset-bottom))] flex items-center justify-around gap-0.5 shadow-[0_-12px_35px_rgba(0,0,0,0.85)]"
    >
      <!-- Resplandor ambiental superior -->
      <div class="absolute -top-[1px] inset-x-0 h-[1px] bg-gradient-to-r from-transparent via-sky-400/50 via-indigo-500/50 to-transparent pointer-events-none"></div>

      <!-- Tab: Inicio -->
      <RouterLink 
        :to="homeLink"
        class="flex-1 flex flex-col items-center justify-center gap-1 py-1 px-1 rounded-2xl transition-all duration-200 relative cursor-pointer touch-manipulation select-none active:scale-90"
        :class="isTabActive('home') ? 'text-sky-300 font-black bg-white/[0.08] shadow-inner' : 'text-slate-400 hover:text-slate-200'"
      >
        <span v-if="isTabActive('home')" class="absolute -top-1.5 w-6 h-0.5 rounded-full bg-sky-400 shadow-[0_0_8px_rgba(56,189,248,0.9)]"></span>
        <svg class="w-5 h-5 transition-transform duration-200" :class="isTabActive('home') ? 'scale-110 drop-shadow-[0_2px_8px_rgba(56,189,248,0.5)]' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
        </svg>
        <span class="text-[9px] leading-tight font-black tracking-tight">Inicio</span>
      </RouterLink>

      <!-- Tab: Streaming -->
      <RouterLink 
        v-if="isStreamingNavVisible"
        :to="getAppSectionLink('suscripciones')"
        class="flex-1 flex flex-col items-center justify-center gap-1 py-1 px-1 rounded-2xl transition-all duration-200 relative cursor-pointer touch-manipulation select-none active:scale-90"
        :class="isTabActive('streaming') ? 'text-cyan-300 font-black bg-white/[0.08] shadow-inner' : 'text-slate-400 hover:text-slate-200'"
      >
        <span v-if="isTabActive('streaming')" class="absolute -top-1.5 w-6 h-0.5 rounded-full bg-cyan-400 shadow-[0_0_8px_rgba(34,211,238,0.9)]"></span>
        <svg class="w-5 h-5 transition-transform duration-200" :class="isTabActive('streaming') ? 'scale-110 drop-shadow-[0_2px_8px_rgba(34,211,238,0.5)]' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
        </svg>
        <span class="text-[9px] leading-tight font-black tracking-tight">Streaming</span>
      </RouterLink>

      <!-- Tab: Combos -->
      <RouterLink 
        v-if="isCombosNavVisible"
        :to="getAppSectionLink('combos')"
        class="flex-1 flex flex-col items-center justify-center gap-1 py-1 px-1 rounded-2xl transition-all duration-200 relative cursor-pointer touch-manipulation select-none active:scale-90"
        :class="isTabActive('combos') ? 'text-pink-300 font-black bg-white/[0.08] shadow-inner' : 'text-slate-400 hover:text-slate-200'"
      >
        <span v-if="isTabActive('combos')" class="absolute -top-1.5 w-6 h-0.5 rounded-full bg-pink-400 shadow-[0_0_8px_rgba(244,114,182,0.9)]"></span>
        <div class="relative">
          <svg class="w-5 h-5 transition-transform duration-200" :class="isTabActive('combos') ? 'scale-110 drop-shadow-[0_2px_8px_rgba(244,114,182,0.5)]' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 01-2-2V8a2 2 0 012-2h14a2 2 0 012 2v2a2 2 0 01-2 2m-14 0v7a2 2 0 002 2h10a2 2 0 002-2v-7"/>
          </svg>
          <span v-if="activePackagesCount > 0" class="absolute -top-1 -right-1 w-2 h-2 rounded-full bg-pink-500 ring-2 ring-slate-950 animate-pulse"></span>
        </div>
        <span class="text-[9px] leading-tight font-black tracking-tight">Combos</span>
      </RouterLink>

      <!-- Tab: Juegos -->
      <RouterLink 
        v-if="isGamingNavVisible"
        :to="getAppSectionLink('juegos')"
        class="flex-1 flex flex-col items-center justify-center gap-1 py-1 px-1 rounded-2xl transition-all duration-200 relative cursor-pointer touch-manipulation select-none active:scale-90"
        :class="isTabActive('juegos') ? 'text-indigo-300 font-black bg-white/[0.08] shadow-inner' : 'text-slate-400 hover:text-slate-200'"
      >
        <span v-if="isTabActive('juegos')" class="absolute -top-1.5 w-6 h-0.5 rounded-full bg-indigo-400 shadow-[0_0_8px_rgba(129,140,248,0.9)]"></span>
        <svg class="w-5 h-5 transition-transform duration-200" :class="isTabActive('juegos') ? 'scale-110 drop-shadow-[0_2px_8px_rgba(129,140,248,0.5)]' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M6 12h4m-2-2v4m7-2h.01m2.99 0h.01M9 19l2.5-2h3L17 19a3 3 0 003-3V9a4 4 0 00-4-4H8a4 4 0 00-4 4v7a3 3 0 003 3z"/>
        </svg>
        <span class="text-[9px] leading-tight font-black tracking-tight">Juegos</span>
      </RouterLink>

      <!-- Tab: Sorteos -->
      <RouterLink 
        :to="getAppSectionLink('sorteos')"
        class="flex-1 flex flex-col items-center justify-center gap-1 py-1 px-1 rounded-2xl transition-all duration-200 relative cursor-pointer touch-manipulation select-none active:scale-90"
        :class="isTabActive('sorteos') ? 'text-amber-300 font-black bg-white/[0.08] shadow-inner' : 'text-slate-400 hover:text-slate-200'"
      >
        <span v-if="isTabActive('sorteos')" class="absolute -top-1.5 w-6 h-0.5 rounded-full bg-amber-400 shadow-[0_0_8px_rgba(251,191,36,0.9)]"></span>
        <div class="relative">
          <svg class="w-5 h-5 transition-transform duration-200" :class="isTabActive('sorteos') ? 'scale-110 drop-shadow-[0_2px_8px_rgba(251,191,36,0.5)]' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 15v5m-4 0h8m-9-9V6a2 2 0 012-2h6a2 2 0 012 2v5a5 5 0 01-10 0zm-3-3a2 2 0 002 2h1V6H4a2 2 0 00-2 2zm18 0a2 2 0 01-2 2h-1V6h1a2 2 0 012 2z"/>
          </svg>
          <span v-if="activeGiveawaysCount > 0" class="absolute -top-1 -right-1 w-2 h-2 rounded-full bg-amber-400 ring-2 ring-slate-950"></span>
        </div>
        <span class="text-[9px] leading-tight font-black tracking-tight">Sorteos</span>
      </RouterLink>

      <!-- Botón: Carrito Heroico en Dock -->
      <button 
        type="button"
        @click="handleOpenCart"
        class="flex-1 flex flex-col items-center justify-center gap-1 py-1 px-1 rounded-2xl transition-all duration-200 relative cursor-pointer touch-manipulation select-none active:scale-90"
        :class="cartStore.totalItems > 0 ? 'text-amber-300 font-black bg-gradient-to-b from-amber-500/25 to-orange-500/15 border border-amber-400/50 shadow-[0_0_15px_rgba(245,158,11,0.3)]' : 'text-slate-400 hover:text-slate-200'"
      >
        <span v-if="cartStore.totalItems > 0" class="absolute -top-1.5 w-6 h-0.5 rounded-full bg-amber-400 shadow-[0_0_8px_rgba(251,191,36,0.9)]"></span>
        <div class="relative">
          <svg class="w-5 h-5 transition-transform duration-200" :class="cartStore.totalItems > 0 ? 'scale-110 text-amber-300' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
          </svg>
          <span 
            v-if="cartStore.totalItems > 0" 
            class="absolute -top-1.5 -right-2 bg-gradient-to-r from-amber-400 to-orange-500 text-slate-950 text-[9px] font-black w-4 h-4 rounded-full flex items-center justify-center font-mono shadow-md animate-bounce ring-1 ring-slate-950"
          >
            {{ cartStore.totalItems }}
          </span>
        </div>
        <span class="text-[9px] leading-tight font-black tracking-tight">
          {{ cartStore.totalItems > 0 ? `$${Math.round(cartStore.total / 1000)}k` : 'Carrito' }}
        </span>
      </button>
    </nav>

    <!-- Toasts Container (Ajustado para flotar limpiamente sobre el dock en móviles) -->
    <div class="fixed bottom-20 sm:bottom-6 right-3 sm:right-6 left-3 sm:left-auto z-[100] flex flex-col gap-2.5 pointer-events-none">
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

    <!-- BOTÓN FLOTANTE PERMANENTE: CARRITO VISIBLE (Solo Desktop para no tapar contenido en móviles) -->
    <div 
      v-if="isClientArea" 
      class="hidden sm:flex fixed sm:bottom-6 sm:right-6 z-40 items-center gap-2 pointer-events-auto"
    >
      <button 
        type="button"
        @click="handleOpenCart"
        class="group relative bg-gradient-to-r from-amber-400 via-orange-500 to-red-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 font-black px-3.5 py-2.5 sm:px-5 sm:py-3 rounded-2xl shadow-[0_8px_25px_rgba(245,158,11,0.5)] hover:shadow-[0_10px_30px_rgba(245,158,11,0.7)] flex items-center gap-2.5 transition-all transform hover:scale-105 active:scale-95 cursor-pointer border border-amber-200/50"
        title="Abrir Carrito de Compras StreamePro"
      >
        <div class="relative flex items-center justify-center">
          <svg class="w-5 h-5 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
          </svg>
          <span 
            v-if="cartStore.totalItems > 0" 
            class="absolute -top-2.5 -right-2.5 bg-slate-950 text-amber-300 text-[10px] font-black w-5 h-5 rounded-full flex items-center justify-center font-mono border-2 border-amber-400 shadow-md animate-bounce"
          >
            {{ cartStore.totalItems }}
          </span>
        </div>
        <div class="flex flex-col items-start leading-none text-left">
          <div class="flex items-center gap-1">
            <span class="text-xs uppercase tracking-wider font-black">CARRITO</span>
            <span v-if="cartStore.totalItems > 0" class="text-[10px] font-mono font-black bg-slate-950 text-emerald-400 px-1 rounded">
              {{ cartStore.totalItems }}
            </span>
          </div>
          <span class="text-[10px] font-mono font-bold text-slate-950 mt-0.5">
            {{ cartStore.totalItems > 0 ? '$' + Math.round(cartStore.total).toLocaleString('es-AR') + ' ARS' : 'Ver Pedido' }}
          </span>
        </div>
      </button>
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

/* Fade Animations */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease, transform 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(10px) scale(0.9);
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

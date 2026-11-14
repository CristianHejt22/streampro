<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAdminStore } from '../store/admin'
import { useToastStore } from '../store/toast'
import { useCartStore } from '../store/cart'
import { sanitizePhone } from '../utils/marketing'

const route = useRoute()
const router = useRouter()
const adminStore = useAdminStore()
const toast = useToastStore()
const cartStore = useCartStore()

const slug = computed(() => {
  return String(route.params.shopSlug || route.query.shop || '').trim().toLowerCase()
})

const activeSection = ref('all')
const searchQuery = ref('')
const selectedProduct = ref(null)
const selectedPlanKey = ref(null)
const showPlanModal = ref(false)
const showCartDrawer = ref(false)
const showPaymentInfo = ref(false)
const gamingLicenseMode = ref({}) // gameId -> 'primary' | 'secondary'

// Datos del cliente para finalizar pedido
const customerName = ref('')
const customerPhone = ref('')
const customerEmail = ref('')
const activationEmail = ref('')

// Buscar distribuidor por slug
const reseller = computed(() => {
  if (!slug.value) return null
  return adminStore.resolveResellerBySlug(slug.value)
})

const shopConfig = computed(() => {
  return reseller.value?.shopConfig || {
    enabled: true,
    slug: slug.value,
    storeName: reseller.value?.storeName || reseller.value?.name || 'Tienda Oficial',
    tagline: 'Videojuegos Digitales, Tecnología & Streaming Garantizado',
    whatsapp: reseller.value?.contactNumber || '5492915659321',
    bannerText: '¡Activación rápida en 10-60 minutos con garantía oficial!',
    showStreaming: true,
    showCombos: true,
    showGaming: true,
    showPhysical: false,
    enabledStreamingPlatforms: ['Netflix', 'Max', 'Disney+', 'Spotify', 'IPTV'],
    enabledGamingPlatforms: ['PS5', 'PS4']
  }
})

const contactPhone = computed(() => {
  const p = shopConfig.value.whatsapp || reseller.value?.contactNumber || '5492915659321'
  return sanitizePhone(p).replace(/\D/g, '')
})

onMounted(async () => {
  // Asegurar que los datos de revendedores y catálogo estén sincronizados
  if (!adminStore.resellers || adminStore.resellers.length === 0) {
    try {
      await adminStore.fetchResellersFromFirebase?.()
    } catch (e) {}
  }
  if (!adminStore.gamingCatalogLoaded) {
    adminStore.loadFullGamingCatalog?.().catch(() => {})
  }

  // Recordar este referido en la sesión para compras futuras
  if (reseller.value) {
    try {
      localStorage.setItem('streamepro_referral', reseller.value.id)
      localStorage.setItem('streamepro_shop_slug', slug.value)
    } catch (e) {}
  }
})

// ========================================================
// CATÁLOGO FILTRADO SEGÚN PREFERENCIAS DEL DISTRIBUIDOR
// ========================================================

// 1. Streaming
const filteredStreaming = computed(() => {
  if (shopConfig.value.showStreaming === false) return []
  const enabledPlats = shopConfig.value.enabledStreamingPlatforms || ['Netflix', 'Max', 'Disney+', 'Spotify', 'IPTV']
  const allProds = adminStore.streamingProducts || []
  
  return allProds.filter(prod => {
    const matchesPlatform = enabledPlats.length === 0 || enabledPlats.includes(prod.platform)
    const matchesQuery = !searchQuery.value || prod.name.toLowerCase().includes(searchQuery.value.toLowerCase()) || prod.platform.toLowerCase().includes(searchQuery.value.toLowerCase())
    return matchesPlatform && matchesQuery
  })
})

// 2. Combos
const filteredCombos = computed(() => {
  if (shopConfig.value.showCombos === false) return []
  const allCombos = adminStore.packages.filter(p => p.active)
  if (!searchQuery.value) return allCombos
  return allCombos.filter(c => c.title.toLowerCase().includes(searchQuery.value.toLowerCase()))
})

// 3. Videojuegos Digitales
const filteredGames = computed(() => {
  if (shopConfig.value.showGaming === false) return []
  const enabledPlatforms = shopConfig.value.enabledGamingPlatforms || ['PS5', 'PS4']
  const games = adminStore.processedGamingProducts || []
  
  return games.filter(g => {
    const platMatch = enabledPlatforms.some(p => (g.platform || '').toUpperCase().includes(p.toUpperCase()))
    const queryMatch = !searchQuery.value || g.title.toLowerCase().includes(searchQuery.value.toLowerCase())
    return platMatch && queryMatch
  }).slice(0, 48) // Primeros 48 juegos optimizados para velocidad
})

// 4. Catálogo Físico (si el distribuidor lo activó)
const filteredPhysical = computed(() => {
  if (shopConfig.value.showPhysical !== true) return []
  const prods = adminStore.physicalProducts || []
  if (!searchQuery.value) return prods.slice(0, 24)
  return prods.filter(p => p.name.toLowerCase().includes(searchQuery.value.toLowerCase())).slice(0, 24)
})

// Helpers de Streaming
const getStreamingPlans = (prod) => {
  return adminStore.pricingCatalog?.[prod?.platform]?.plans || prod?.plans || {}
}

const getMinPrice = (prod) => {
  const plans = Object.values(getStreamingPlans(prod))
  const prices = plans.map(p => Number(p?.clientPrice || 0)).filter(n => n > 0)
  return prices.length > 0 ? Math.min(...prices) : 0
}

const openPlanSelector = (prod) => {
  selectedProduct.value = prod
  const plans = getStreamingPlans(prod)
  const keys = Object.keys(plans)
  selectedPlanKey.value = keys[0] || null
  showPlanModal.value = true
}

// Helpers de Gaming
const getGameLicenseMode = (gameId) => gamingLicenseMode.value[gameId] || 'secondary'
const setGameLicenseMode = (gameId, mode) => { gamingLicenseMode.value[gameId] = mode }

const getGamePrice = (game) => {
  const mode = getGameLicenseMode(game.id)
  return mode === 'primary' ? (game.effectivePrimaryPrice || 0) : (game.effectiveSecondaryPrice || game.effectivePrimaryPrice || 0)
}

// ========================================================
// ACCIONES DE COMPRA Y WHATSAPP DIRECTO CON EL DISTRIBUIDOR
// ========================================================

const getDistributorWhatsAppUrl = (msg = '') => {
  const encoded = encodeURIComponent(msg)
  return `https://wa.me/${contactPhone.value}?text=${encoded}`
}

const buyStreamingWhatsApp = (prod, planKey = null) => {
  const plans = getStreamingPlans(prod)
  const pKey = planKey || selectedPlanKey.value || Object.keys(plans)[0]
  const plan = plans[pKey] || {}
  const storeTitle = shopConfig.value.storeName || 'tu tienda'
  const text = `Hola! Te contacto desde tu tienda *${storeTitle}* en StreamePro.\n\n` +
    `Quiero contratar: *${prod.name}*\n` +
    `• Modalidad: *${plan.label || 'Plan 30 Días'}*\n` +
    `• Precio: *$${Number(plan.clientPrice || 0).toLocaleString()} ARS*\n` +
    `• Garantía: SLA 30 Días Oficiales\n\n` +
    `¿Tienen disponibilidad para activar de inmediato?`
  window.open(getDistributorWhatsAppUrl(text), '_blank')
}

const buyComboWhatsApp = (combo) => {
  const storeTitle = shopConfig.value.storeName || 'tu tienda'
  const text = `Hola! Te contacto desde tu tienda *${storeTitle}* en StreamePro.\n\n` +
    `Quiero pedir el *${combo.title}*:\n` +
    `• Precio promocional: *$${Number(combo.promoPrice || 0).toLocaleString()} ARS*\n` +
    `• Incluye: ${combo.services.map(s => s.platform).join(', ')}\n\n` +
    `¿Me confirmas disponibilidad para transferir?`
  window.open(getDistributorWhatsAppUrl(text), '_blank')
}

const buyGamingWhatsApp = (game) => {
  const mode = getGameLicenseMode(game.id)
  const price = getGamePrice(game)
  const storeTitle = shopConfig.value.storeName || 'tu tienda'
  const modeLabel = mode === 'primary' ? 'Cuenta Primaria (Juegas con tu usuario)' : 'Cuenta Secundaria (Juegas desde la cuenta)'
  const text = `Hola! Te contacto desde tu tienda *${storeTitle}* en StreamePro.\n\n` +
    `Quiero comprar el juego: *${game.title}* (${game.platform})\n` +
    `• Modalidad: *${modeLabel}*\n` +
    `• Precio oficial: *$${Number(price).toLocaleString()} ARS*\n\n` +
    `¿Me confirmas si tienen entrega inmediata?`
  window.open(getDistributorWhatsAppUrl(text), '_blank')
}

// Agregar al Carrito Local
const addStreamingToCart = (prod) => {
  const plans = getStreamingPlans(prod)
  const plan = plans[selectedPlanKey.value] || Object.values(plans)[0]
  cartStore.addItem({
    id: `${prod.id}_${selectedPlanKey.value}_${Date.now()}`,
    productId: prod.id,
    name: `${prod.name} (${plan.label})`,
    platform: prod.platform,
    price: Number(plan.clientPrice || 0),
    type: 'streaming',
    planKey: selectedPlanKey.value,
    planLabel: plan.label,
    resellerId: reseller.value?.id || 'DIRECT',
    image: prod.image || ''
  })
  toast.add(`✓ ${prod.name} añadido a tu pedido`, 'success')
  showPlanModal.value = false
}

const addGameToCart = (game) => {
  const mode = getGameLicenseMode(game.id)
  const price = getGamePrice(game)
  cartStore.addItem({
    id: `${game.id}_${mode}_${Date.now()}`,
    productId: game.id,
    name: `${game.title} (${game.platform} - ${mode === 'primary' ? 'Primaria' : 'Secundaria'})`,
    platform: game.platform,
    price: Number(price),
    type: 'gaming',
    licenseType: mode,
    resellerId: reseller.value?.id || 'DIRECT',
    image: game.image || ''
  })
  toast.add(`✓ ${game.title} añadido al pedido`, 'success')
}

// Finalizar pedido del carrito enviando a WhatsApp del distribuidor
const submitCartOrder = () => {
  if (cartStore.items.length === 0) {
    toast.add('Tu carrito está vacío', 'info')
    return
  }
  const storeTitle = shopConfig.value.storeName || 'tu tienda'
  let text = `Hola! Quiero confirmar este pedido en tu tienda *${storeTitle}*:\n\n`
  if (customerName.value) text += `👤 *Cliente:* ${customerName.value}\n`
  if (customerPhone.value) text += `📞 *Teléfono:* ${customerPhone.value}\n\n`
  
  text += `🛒 *DETALLE DEL PEDIDO:*\n`
  cartStore.items.forEach((item, idx) => {
    text += `${idx + 1}. ${item.name} — $${Number(item.price).toLocaleString()} ARS\n`
  })
  text += `\n💰 *TOTAL A PAGAR:* $${cartStore.total.toLocaleString()} ARS\n`
  text += `\nPor favor pásame los datos de CBU/Alias para realizar la transferencia bancaria. ¡Muchas gracias!`

  // Registrar orden en la tienda
  try {
    adminStore.createOrder({
      customerName: customerName.value || 'Cliente Web Tienda',
      customerPhone: customerPhone.value || '',
      resellerId: reseller.value?.id || 'DIRECT',
      resellerName: reseller.value?.name || storeTitle,
      total: cartStore.total,
      items: [...cartStore.items],
      status: 'Waiting Provider'
    })
  } catch (e) {}

  window.open(getDistributorWhatsAppUrl(text), '_blank')
  cartStore.clearCart()
  showCartDrawer.value = false
  toast.add('¡Pedido enviado al distribuidor por WhatsApp!', 'success')
}

const shareShopLink = () => {
  const url = `https://streamepro.me/${slug.value}`
  if (navigator.clipboard) {
    navigator.clipboard.writeText(url)
    toast.add(`✓ Enlace copiado: ${url}`, 'success')
  }
}
</script>

<template>
  <div class="min-h-screen bg-[#020617] text-white flex flex-col font-sans selection:bg-amber-400 selection:text-slate-950">
    
    <!-- ESTADO 1: TIENDA NO ENCONTRADA -->
    <div v-if="!reseller" class="flex-1 flex items-center justify-center p-6 text-center">
      <div class="max-w-md bg-slate-900 border border-slate-800 rounded-3xl p-8 shadow-2xl space-y-4">
        <div class="w-16 h-16 rounded-2xl bg-slate-800 border border-slate-700 flex items-center justify-center text-3xl mx-auto">
          🏪
        </div>
        <h2 class="text-xl font-black text-white">Tienda no encontrada</h2>
        <p class="text-xs text-slate-400 leading-relaxed">
          No pudimos encontrar una tienda activa con el enlace <strong class="text-amber-400 font-mono">streamepro.me/{{ slug }}</strong>. Puede que el enlace esté mal escrito o haya sido modificado.
        </p>
        <RouterLink 
          to="/" 
          class="inline-block bg-gradient-to-r from-sky-400 to-emerald-400 text-slate-950 font-black px-6 py-3 rounded-xl text-xs shadow-md transition active:scale-95"
        >
          Ir a la Portada Oficial StreamePro →
        </RouterLink>
      </div>
    </div>

    <!-- ESTADO 2: TIENDA ACTIVA DEL DISTRIBUIDOR -->
    <div v-else class="flex-1 flex flex-col">
      
      <!-- HEADER PRINCIPAL DE LA TIENDA DEL DISTRIBUIDOR -->
      <header class="sticky top-0 z-40 bg-slate-950/85 backdrop-blur-xl border-b border-white/[0.08] shadow-lg">
        <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between gap-3">
          
          <!-- Logo & Nombre de la Tienda -->
          <div class="flex items-center gap-3 min-w-0">
            <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-amber-500 to-orange-500 flex items-center justify-center font-black text-slate-950 text-lg shadow-md shrink-0">
              🏪
            </div>
            <div class="min-w-0">
              <div class="flex items-center gap-2">
                <h1 class="font-black text-white text-base sm:text-lg leading-tight truncate">
                  {{ shopConfig.storeName }}
                </h1>
                <span class="hidden sm:inline-flex items-center gap-1 bg-emerald-500/15 text-emerald-300 text-[10px] font-mono font-bold px-2 py-0.5 rounded-full border border-emerald-500/30 shrink-0">
                  <span>✓</span> Oficial
                </span>
              </div>
              <p class="text-[11px] text-slate-400 truncate">
                Distribuidor Autorizado ({{ reseller.id }}) • Garantía Oficial StreamePro
              </p>
            </div>
          </div>

          <!-- Acciones de Cabecera -->
          <div class="flex items-center gap-2 shrink-0">
            <button 
              type="button" 
              @click="shareShopLink"
              class="hidden sm:flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-white border border-slate-700 text-xs font-bold transition cursor-pointer"
              title="Copiar link corto de la tienda"
            >
              <span>🔗</span>
              <span class="font-mono text-[11px]">/{{ slug }}</span>
            </button>

            <a 
              :href="getDistributorWhatsAppUrl('Hola! Te contacto desde tu tienda oficial en StreamePro.')" 
              target="_blank" 
              rel="noopener noreferrer"
              class="bg-emerald-600 hover:bg-emerald-500 text-white font-black px-3.5 py-2 rounded-xl text-xs flex items-center gap-1.5 shadow-md transition active:scale-95 cursor-pointer"
            >
              <span>💬</span>
              <span class="hidden sm:inline">WhatsApp</span>
            </a>

            <button 
              type="button" 
              @click="showCartDrawer = true" 
              class="relative bg-slate-900 hover:bg-slate-800 text-amber-400 border border-amber-400/30 p-2 sm:px-3 sm:py-2 rounded-xl text-xs font-bold transition cursor-pointer flex items-center gap-1.5"
            >
              <span>🛒</span>
              <span class="hidden sm:inline">Pedido</span>
              <span v-if="cartStore.items.length > 0" class="w-5 h-5 rounded-full bg-amber-400 text-slate-950 font-black text-[10px] flex items-center justify-center font-mono">
                {{ cartStore.items.length }}
              </span>
            </button>
          </div>
        </div>
      </header>

      <!-- HERO BANNER DEL DISTRIBUIDOR -->
      <section class="relative bg-gradient-to-b from-slate-900 via-slate-950 to-[#020617] border-b border-white/[0.06] py-8 sm:py-12 px-4 overflow-hidden">
        <div class="absolute -top-24 -right-24 w-80 h-80 rounded-full blur-3xl opacity-20 pointer-events-none bg-amber-500"></div>
        <div class="absolute -bottom-24 -left-24 w-80 h-80 rounded-full blur-3xl opacity-15 pointer-events-none bg-sky-500"></div>

        <div class="max-w-4xl mx-auto text-center relative z-10 space-y-3.5">
          <div class="inline-flex items-center gap-2 bg-amber-500/10 border border-amber-500/25 text-amber-300 px-3.5 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider shadow-sm">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            <span>Atención Directa por {{ shopConfig.storeName }}</span>
          </div>

          <h2 class="text-2xl sm:text-4xl font-black text-white tracking-tight leading-tight">
            {{ shopConfig.tagline || 'Cuentas Oficiales, Videojuegos & Tecnología' }}
          </h2>

          <p class="text-xs sm:text-sm text-slate-300 max-w-2xl mx-auto leading-relaxed">
            {{ shopConfig.bannerText || 'Selecciona los productos que deseas adquirir. Recibe activación inmediata de 10 a 60 minutos con reposición garantizada 100% y atención personalizada.' }}
          </p>

          <!-- Badges de Garantía -->
          <div class="flex flex-wrap items-center justify-center gap-2 pt-2 text-xs font-semibold text-slate-300">
            <div class="bg-slate-900/80 px-3 py-1.5 rounded-xl border border-white/10 flex items-center gap-1.5">
              <span>⚡</span> Entrega Rápida 10-60m
            </div>
            <div class="bg-slate-900/80 px-3 py-1.5 rounded-xl border border-white/10 flex items-center gap-1.5">
              <span>🛡️</span> Garantía SLA 30 Días
            </div>
            <div class="bg-slate-900/80 px-3 py-1.5 rounded-xl border border-white/10 flex items-center gap-1.5">
              <span>🔒</span> Cuentas con PIN Privado
            </div>
          </div>
        </div>
      </section>

      <!-- FILTROS & BUSCADOR -->
      <section class="max-w-6xl mx-auto px-4 pt-6 pb-2 w-full space-y-4">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
          
          <!-- Chips de Secciones Disponibles -->
          <div class="flex items-center gap-1.5 p-1 bg-slate-900/80 rounded-2xl border border-white/[0.08] overflow-x-auto no-scrollbar max-w-full">
            <button 
              type="button" 
              @click="activeSection = 'all'" 
              :class="[
                'px-3.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer shrink-0',
                activeSection === 'all' ? 'bg-amber-400 text-slate-950 font-black shadow' : 'text-slate-400 hover:text-white'
              ]"
            >
              🌟 Todo
            </button>
            <button 
              v-if="shopConfig.showStreaming !== false" 
              type="button" 
              @click="activeSection = 'streaming'" 
              :class="[
                'px-3.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer shrink-0',
                activeSection === 'streaming' ? 'bg-sky-500 text-slate-950 font-black shadow' : 'text-slate-400 hover:text-white'
              ]"
            >
              🎬 Streaming ({{ filteredStreaming.length }})
            </button>
            <button 
              v-if="shopConfig.showCombos !== false && filteredCombos.length > 0" 
              type="button" 
              @click="activeSection = 'combos'" 
              :class="[
                'px-3.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer shrink-0',
                activeSection === 'combos' ? 'bg-pink-500 text-white font-black shadow' : 'text-slate-400 hover:text-white'
              ]"
            >
              🎁 Combos ({{ filteredCombos.length }})
            </button>
            <button 
              v-if="shopConfig.showGaming !== false && filteredGames.length > 0" 
              type="button" 
              @click="activeSection = 'gaming'" 
              :class="[
                'px-3.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer shrink-0',
                activeSection === 'gaming' ? 'bg-indigo-500 text-white font-black shadow' : 'text-slate-400 hover:text-white'
              ]"
            >
              🎮 Juegos PS5/PS4 ({{ filteredGames.length }})
            </button>
            <button 
              v-if="shopConfig.showPhysical === true && filteredPhysical.length > 0" 
              type="button" 
              @click="activeSection = 'physical'" 
              :class="[
                'px-3.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer shrink-0',
                activeSection === 'physical' ? 'bg-amber-500 text-slate-950 font-black shadow' : 'text-slate-400 hover:text-white'
              ]"
            >
              📦 Físicos ({{ filteredPhysical.length }})
            </button>
          </div>

          <!-- Buscador Rápido -->
          <div class="w-full sm:w-64 relative">
            <input 
              v-model="searchQuery" 
              type="text" 
              placeholder="Buscar en la tienda..." 
              class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3.5 py-2 pl-9 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-amber-400 transition"
            >
            <span class="absolute left-3 top-2.5 text-xs text-slate-500">🔍</span>
            <button 
              v-if="searchQuery" 
              @click="searchQuery = ''" 
              class="absolute right-2.5 top-2 text-xs text-slate-400 hover:text-white"
            >
              ✕
            </button>
          </div>
        </div>
      </section>

      <!-- CONTENIDO DEL CATÁLOGO DE LA TIENDA -->
      <main class="max-w-6xl mx-auto px-4 py-6 w-full space-y-10 flex-1">
        
        <!-- SECCIÓN 1: STREAMING -->
        <div v-if="(activeSection === 'all' || activeSection === 'streaming') && filteredStreaming.length > 0" class="space-y-4">
          <div class="flex items-center justify-between border-b border-white/[0.08] pb-2">
            <h3 class="text-base sm:text-lg font-black text-white flex items-center gap-2">
              <span>🎬</span>
              <span>Suscripciones Digitales & Pantallas 4K</span>
            </h3>
            <span class="text-xs font-mono text-slate-400">{{ filteredStreaming.length }} opciones</span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            <div 
              v-for="prod in filteredStreaming" 
              :key="prod.id"
              class="bg-slate-900/80 border border-white/[0.08] hover:border-sky-500/40 rounded-3xl p-5 flex flex-col justify-between transition-all duration-300 group shadow-lg"
            >
              <div class="space-y-3">
                <div class="flex items-start justify-between gap-2.5">
                  <div class="flex items-center gap-3">
                    <div 
                      class="w-11 h-11 rounded-2xl flex items-center justify-center bg-slate-950 border border-white/10 shadow-inner shrink-0"
                      v-html="adminStore.streamingBrandMeta[prod.platform]?.iconSvg || '🎬'"
                    ></div>
                    <div>
                      <span class="text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full border bg-sky-500/10 text-sky-300 border-sky-500/25 inline-block mb-0.5">
                        {{ prod.platform }}
                      </span>
                      <h4 class="text-base font-black text-white group-hover:text-sky-300 transition-colors leading-tight">
                        {{ prod.name }}
                      </h4>
                    </div>
                  </div>
                  <span class="text-[9px] font-bold px-2 py-0.5 rounded-full border font-mono bg-emerald-500/15 text-emerald-400 border-emerald-500/30 shrink-0">
                    🟢 10-60m
                  </span>
                </div>

                <div class="space-y-1 text-xs text-slate-300 py-1">
                  <div class="flex items-center gap-1.5"><span class="text-emerald-400 font-bold">✓</span><span>Calidad Ultra HD 4K</span></div>
                  <div class="flex items-center gap-1.5"><span class="text-emerald-400 font-bold">✓</span><span>PIN Privado Exclusivo</span></div>
                  <div class="flex items-center gap-1.5"><span class="text-emerald-400 font-bold">✓</span><span>SLA 30 Días Garantía</span></div>
                </div>

                <div class="bg-slate-950/90 rounded-2xl p-3 border border-slate-800 flex items-center justify-between">
                  <div>
                    <span class="text-[9px] uppercase font-bold text-slate-400 block">Desde:</span>
                    <span class="text-xl font-black font-mono text-emerald-400">${{ getMinPrice(prod).toLocaleString() }} <span class="text-[10px] text-slate-400 font-bold">ARS</span></span>
                  </div>
                  <span class="text-[10px] font-bold text-sky-300 bg-sky-500/10 px-2.5 py-1 rounded-xl border border-sky-500/20 font-mono">
                    {{ Object.keys(getStreamingPlans(prod)).length }} Modalidades
                  </span>
                </div>
              </div>

              <div class="flex gap-2 pt-3 mt-3 border-t border-white/[0.06]">
                <button 
                  type="button" 
                  @click="buyStreamingWhatsApp(prod)" 
                  class="flex-1 bg-emerald-600 hover:bg-emerald-500 text-white font-black py-2.5 px-3 rounded-xl text-xs flex items-center justify-center gap-1.5 transition active:scale-95 cursor-pointer shadow-md"
                >
                  <span>💬</span>
                  <span>Pedir por WhatsApp</span>
                </button>
                <button 
                  type="button" 
                  @click="openPlanSelector(prod)" 
                  class="bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold px-3 py-2.5 rounded-xl text-xs transition cursor-pointer"
                  title="Ver todas las modalidades"
                >
                  Ver Planes
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- SECCIÓN 2: SUPER COMBOS -->
        <div v-if="(activeSection === 'all' || activeSection === 'combos') && filteredCombos.length > 0" class="space-y-4">
          <div class="flex items-center justify-between border-b border-white/[0.08] pb-2">
            <h3 class="text-base sm:text-lg font-black text-white flex items-center gap-2">
              <span>🎁</span>
              <span>Super Combos Promocionales</span>
            </h3>
            <span class="text-xs font-mono text-pink-400">{{ filteredCombos.length }} ofertas</span>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div 
              v-for="combo in filteredCombos" 
              :key="combo.id"
              class="bg-slate-900/80 border border-white/[0.08] hover:border-pink-500/40 rounded-3xl p-5 flex flex-col justify-between transition-all group shadow-lg"
            >
              <div class="space-y-3">
                <div class="flex justify-between items-start gap-2">
                  <span class="text-[9px] font-black uppercase tracking-wider bg-gradient-to-r from-pink-500 to-rose-500 text-white px-2.5 py-0.5 rounded-full">
                    {{ combo.badge || '🔥 MÁXIMO AHORRO' }}
                  </span>
                  <span class="text-[10px] font-mono text-slate-400 bg-slate-950 px-2 py-0.5 rounded border border-slate-800">
                    {{ combo.services.length }} Plataformas
                  </span>
                </div>

                <h4 class="text-base font-black text-white group-hover:text-pink-300 transition-colors leading-tight">
                  {{ combo.title }}
                </h4>

                <div class="flex flex-wrap gap-1.5 py-1">
                  <span v-for="(s, sIdx) in combo.services" :key="sIdx" class="bg-slate-950 px-2.5 py-1 rounded-xl text-xs font-bold text-white border border-pink-500/20">
                    {{ s.platform }}
                  </span>
                </div>

                <div class="bg-gradient-to-r from-slate-950 to-pink-950/20 p-3 rounded-2xl border border-pink-500/20 flex items-center justify-between">
                  <div>
                    <span v-if="combo.originalPrice > combo.promoPrice" class="text-[10px] text-slate-400 line-through block">
                      ${{ combo.originalPrice.toLocaleString() }} ARS
                    </span>
                    <span class="text-xl font-black font-mono text-pink-400">
                      ${{ combo.promoPrice.toLocaleString() }} <span class="text-[10px] text-slate-400">ARS</span>
                    </span>
                  </div>
                  <div v-if="combo.originalPrice > combo.promoPrice" class="bg-emerald-500/15 border border-emerald-500/30 text-emerald-300 text-[10px] font-black px-2 py-0.5 rounded-lg">
                    Ahorro ${{ (combo.originalPrice - combo.promoPrice).toLocaleString() }}
                  </div>
                </div>
              </div>

              <div class="pt-3 mt-3 border-t border-white/[0.06]">
                <button 
                  type="button" 
                  @click="buyComboWhatsApp(combo)" 
                  class="w-full bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-400 hover:to-pink-400 text-white font-black py-2.5 px-3 rounded-xl text-xs flex items-center justify-center gap-1.5 transition active:scale-95 cursor-pointer shadow-md"
                >
                  <span>💬</span>
                  <span>Pedir Combo por WhatsApp</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- SECCIÓN 3: VIDEOJUEGOS DIGITALES PS5 / PS4 -->
        <div v-if="(activeSection === 'all' || activeSection === 'gaming') && filteredGames.length > 0" class="space-y-4">
          <div class="flex items-center justify-between border-b border-white/[0.08] pb-2">
            <h3 class="text-base sm:text-lg font-black text-white flex items-center gap-2">
              <span>🎮</span>
              <span>Videojuegos Digitales PlayStation (PS5 & PS4)</span>
            </h3>
            <span class="text-xs font-mono text-indigo-400">{{ filteredGames.length }} juegos</span>
          </div>

          <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3.5 sm:gap-5">
            <div 
              v-for="game in filteredGames" 
              :key="game.id" 
              class="bg-slate-900/80 border border-white/[0.08] rounded-2xl overflow-hidden hover:border-indigo-500/40 transition-all flex flex-col justify-between group shadow-md"
            >
              <div>
                <div class="relative h-40 sm:h-48 bg-slate-950 overflow-hidden flex items-center justify-center">
                  <img :src="game.image" :alt="game.title" loading="lazy" class="w-full h-full object-contain p-2 group-hover:scale-105 transition-transform duration-300">
                  <span class="absolute top-2 left-2 bg-indigo-600 text-white text-[9px] font-black px-2 py-0.5 rounded-full font-mono shadow">
                    {{ game.platform }}
                  </span>
                </div>

                <div class="p-3 space-y-2">
                  <h4 class="font-black text-white text-xs sm:text-sm line-clamp-1 group-hover:text-indigo-300 transition" :title="game.title">
                    {{ game.title }}
                  </h4>

                  <!-- Selector Primaria vs Secundaria -->
                  <div class="grid grid-cols-2 gap-1 p-0.5 bg-slate-950 rounded-lg border border-white/[0.06] text-[10px] font-bold">
                    <button 
                      type="button" 
                      @click="setGameLicenseMode(game.id, 'primary')" 
                      :class="[
                        'py-1 rounded text-center transition cursor-pointer',
                        getGameLicenseMode(game.id) === 'primary' ? 'bg-indigo-600 text-white font-black' : 'text-slate-400 hover:text-white'
                      ]"
                    >
                      Primaria
                    </button>
                    <button 
                      type="button" 
                      @click="setGameLicenseMode(game.id, 'secondary')" 
                      :class="[
                        'py-1 rounded text-center transition cursor-pointer',
                        getGameLicenseMode(game.id) === 'secondary' ? 'bg-indigo-600 text-white font-black' : 'text-slate-400 hover:text-white'
                      ]"
                    >
                      Secundaria
                    </button>
                  </div>

                  <div class="flex items-baseline justify-between pt-1">
                    <span class="text-[10px] text-slate-400">Precio:</span>
                    <strong class="text-sm sm:text-base font-black text-indigo-300 font-mono">
                      ${{ Number(getGamePrice(game)).toLocaleString() }} <span class="text-[10px] font-normal text-slate-400">ARS</span>
                    </strong>
                  </div>
                </div>
              </div>

              <div class="p-3 pt-0 flex gap-1.5">
                <button 
                  type="button" 
                  @click="buyGamingWhatsApp(game)" 
                  class="flex-1 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-black py-2 px-2 rounded-xl text-[11px] flex items-center justify-center gap-1 transition active:scale-95 cursor-pointer shadow"
                >
                  <span>⚡ Comprar</span>
                </button>
                <button 
                  type="button" 
                  @click="addGameToCart(game)" 
                  class="bg-indigo-600/30 hover:bg-indigo-600 text-indigo-200 hover:text-white font-bold p-2 rounded-xl text-xs transition cursor-pointer"
                  title="Añadir al carrito"
                >
                  🛒
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- SECCIÓN 4: CATÁLOGO FÍSICO (Si está habilitado) -->
        <div v-if="(activeSection === 'all' || activeSection === 'physical') && filteredPhysical.length > 0" class="space-y-4">
          <div class="flex items-center justify-between border-b border-white/[0.08] pb-2">
            <h3 class="text-base sm:text-lg font-black text-white flex items-center gap-2">
              <span>📦</span>
              <span>Tecnología, Electrónica & Bazar</span>
            </h3>
            <span class="text-xs font-mono text-amber-400">{{ filteredPhysical.length }} productos</span>
          </div>

          <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3.5 sm:gap-5">
            <div 
              v-for="item in filteredPhysical" 
              :key="item.id" 
              class="bg-slate-900/80 border border-white/[0.08] rounded-2xl overflow-hidden hover:border-amber-400/40 transition-all flex flex-col justify-between group shadow-md"
            >
              <div>
                <div class="h-36 sm:h-44 bg-slate-950 p-2 flex items-center justify-center">
                  <img :src="item.image" :alt="item.name" loading="lazy" class="max-h-full max-w-full object-contain group-hover:scale-105 transition-transform">
                </div>
                <div class="p-3 space-y-1">
                  <h4 class="font-bold text-white text-xs sm:text-sm line-clamp-2" :title="item.name">
                    {{ item.name }}
                  </h4>
                  <div class="text-sm sm:text-base font-black text-amber-400 font-mono">
                    {{ item.price }}
                  </div>
                </div>
              </div>

              <div class="p-3 pt-0">
                <a 
                  :href="getDistributorWhatsAppUrl(`Hola! Quiero consultar por ${item.name} (${item.price}) en tu tienda StreamePro.`)"
                  target="_blank" 
                  rel="noopener noreferrer"
                  class="w-full bg-amber-400 hover:bg-amber-300 text-slate-950 font-black py-2 px-2 rounded-xl text-xs flex items-center justify-center gap-1 transition shadow cursor-pointer"
                >
                  <span>💬</span>
                  <span>Pedir por WhatsApp</span>
                </a>
              </div>
            </div>
          </div>
        </div>
      </main>

      <!-- MODAL SELECTOR DE PLANES (STREAMING) -->
      <div v-if="showPlanModal && selectedProduct" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-5 bg-black/85 backdrop-blur-md animate-fade-in">
        <div class="bg-slate-900 border border-slate-700 w-full max-w-lg rounded-3xl p-6 shadow-2xl relative space-y-4 max-h-[90vh] overflow-y-auto">
          <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-2xl bg-slate-950 border border-white/10 flex items-center justify-center font-bold text-xl">
                🎬
              </div>
              <div>
                <h3 class="text-base font-black text-white">{{ selectedProduct.name }}</h3>
                <p class="text-xs text-slate-400">Selecciona la modalidad para contratar:</p>
              </div>
            </div>
            <button @click="showPlanModal = false" class="w-8 h-8 rounded-xl bg-slate-800 text-slate-300 hover:text-white flex items-center justify-center font-bold text-sm cursor-pointer">✕</button>
          </div>

          <div class="space-y-2">
            <div 
              v-for="(plan, pKey) in getStreamingPlans(selectedProduct)" 
              :key="pKey"
              @click="selectedPlanKey = pKey"
              :class="[
                'p-3.5 rounded-2xl border-2 transition cursor-pointer flex items-center justify-between',
                selectedPlanKey === pKey ? 'bg-sky-500/15 border-sky-400 text-white' : 'bg-slate-950/80 border-slate-800 text-slate-300 hover:border-slate-700'
              ]"
            >
              <div>
                <div class="font-black text-sm text-white">{{ plan.label }}</div>
                <div class="text-[11px] text-slate-400 mt-0.5">✓ 30 Días de Cobertura con PIN Privado</div>
              </div>
              <div class="text-right">
                <div class="font-black text-base text-emerald-400 font-mono">${{ Number(plan.clientPrice || 0).toLocaleString() }} ARS</div>
                <div class="text-[10px] text-slate-400 font-bold uppercase">Garantía SLA</div>
              </div>
            </div>
          </div>

          <div class="flex gap-2 pt-2">
            <button 
              type="button" 
              @click="buyStreamingWhatsApp(selectedProduct, selectedPlanKey)" 
              class="flex-1 bg-emerald-600 hover:bg-emerald-500 text-white font-black py-3 px-3 rounded-xl text-xs flex items-center justify-center gap-1.5 transition active:scale-95 cursor-pointer shadow-md"
            >
              <span>💬</span>
              <span>Pedir por WhatsApp</span>
            </button>
            <button 
              type="button" 
              @click="addStreamingToCart(selectedProduct)" 
              class="flex-1 bg-gradient-to-r from-sky-400 to-emerald-400 text-slate-950 font-black py-3 px-3 rounded-xl text-xs flex items-center justify-center gap-1.5 transition active:scale-95 cursor-pointer shadow-md"
            >
              <span>+ Añadir al Carrito</span>
            </button>
          </div>
        </div>
      </div>

      <!-- MODAL / DRAWER DEL CARRITO -->
      <div v-if="showCartDrawer" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-5 bg-black/85 backdrop-blur-md animate-fade-in">
        <div class="bg-slate-900 border border-slate-700 w-full max-w-lg rounded-3xl p-6 shadow-2xl relative space-y-4 max-h-[90vh] overflow-y-auto">
          <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <div class="flex items-center gap-2">
              <span class="text-xl">🛒</span>
              <h3 class="text-base font-black text-white">Mi Pedido en {{ shopConfig.storeName }}</h3>
            </div>
            <button @click="showCartDrawer = false" class="w-8 h-8 rounded-xl bg-slate-800 text-slate-300 hover:text-white flex items-center justify-center font-bold text-sm cursor-pointer">✕</button>
          </div>

          <div v-if="cartStore.items.length === 0" class="text-center py-8 text-slate-400 text-xs">
            Tu carrito está vacío. Agrega productos para realizar tu pedido.
          </div>

          <div v-else class="space-y-3">
            <div class="space-y-2 max-h-56 overflow-y-auto pr-1">
              <div v-for="(item, iIdx) in cartStore.items" :key="item.id" class="p-3 bg-slate-950 rounded-xl border border-slate-800 flex items-center justify-between text-xs">
                <div>
                  <div class="font-bold text-white">{{ item.name }}</div>
                  <div class="text-[10px] text-slate-400">${{ Number(item.price).toLocaleString() }} ARS</div>
                </div>
                <button @click="cartStore.removeItem(item.id)" class="text-rose-400 hover:text-rose-300 font-bold px-2 py-1 cursor-pointer">✕</button>
              </div>
            </div>

            <div class="p-3 bg-slate-950 rounded-xl border border-slate-800 flex items-center justify-between">
              <span class="text-xs font-bold text-slate-400">Total a Pagar:</span>
              <span class="text-xl font-black font-mono text-emerald-400">${{ cartStore.total.toLocaleString() }} ARS</span>
            </div>

            <!-- Datos del Cliente -->
            <div class="space-y-2 pt-2 border-t border-slate-800 text-xs">
              <div class="font-bold text-slate-300">Tus Datos para la Entrega:</div>
              <input v-model="customerName" type="text" placeholder="Tu Nombre y Apellido" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-xs text-white focus:outline-none focus:border-amber-400">
              <input v-model="customerPhone" type="tel" placeholder="Tu Número de WhatsApp (+54 9 ...)" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-xs text-white focus:outline-none focus:border-amber-400">
            </div>

            <!-- Datos Bancarios del Distribuidor -->
            <div v-if="reseller.cbuAlias" class="bg-amber-500/10 border border-amber-500/20 p-3 rounded-xl text-xs space-y-1">
              <div class="font-bold text-amber-300">💳 Transferencia Directa a este Distribuidor:</div>
              <div class="text-slate-300"><strong>Titular:</strong> {{ reseller.holderName || reseller.name }}</div>
              <div class="text-slate-300"><strong>Alias / CBU:</strong> <span class="font-mono text-amber-300 font-bold select-all">{{ reseller.cbuAlias }}</span></div>
              <div v-if="reseller.bankName" class="text-slate-400 text-[11px]">{{ reseller.bankName }}</div>
            </div>

            <button 
              type="button" 
              @click="submitCartOrder" 
              class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-black py-3 px-4 rounded-xl text-xs flex items-center justify-center gap-2 transition active:scale-95 cursor-pointer shadow-md"
            >
              <span>📲</span>
              <span>Enviar Pedido por WhatsApp a {{ shopConfig.storeName }}</span>
            </button>
          </div>
        </div>
      </div>

      <!-- FOOTER DE LA TIENDA -->
      <footer class="bg-slate-950 border-t border-white/[0.06] py-8 px-4 text-center text-xs text-slate-500 space-y-2 mt-auto">
        <div class="flex items-center justify-center gap-2 text-slate-400 font-bold">
          <span>🏪 {{ shopConfig.storeName }}</span>
          <span>•</span>
          <span>Distribuidor Autorizado ({{ reseller.id }})</span>
        </div>
        <p class="max-w-md mx-auto text-[11px]">
          Plataforma oficial protegida por la infraestructura y garantía de servicio StreamePro Cloud Enterprise.
        </p>
        <div class="pt-2">
          <RouterLink to="/" class="text-sky-400 hover:text-sky-300 underline text-[11px]">
            Explorar Tienda Central StreamePro →
          </RouterLink>
        </div>
      </footer>
    </div>
  </div>
</template>

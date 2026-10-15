<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useCartStore } from '../store/cart'
import { useAdminStore, PLATFORM_PLANS } from '../store/admin'
import { useToastStore } from '../store/toast'
import FlyerGeneratorModal from '../components/FlyerGeneratorModal.vue'

const route = useRoute()
const cartStore = useCartStore()
const adminStore = useAdminStore()
const toast = useToastStore()

const showFlyerModal = ref(false)
const flyerInitialProduct = ref(null)

const openFlyerForPhysical = (prod) => {
  flyerInitialProduct.value = prod
  showFlyerModal.value = true
}

const products = ref([
  { 
    id: 1, 
    name: 'Netflix Premium Ultra HD', 
    category: 'Streaming', 
    platform: 'Netflix',
    description: 'Calidad 4K HDR con audio espacial y descargas offline.',
    plans: PLATFORM_PLANS.Netflix.plans
  },
  { 
    id: 2, 
    name: 'Spotify Familiar Individual', 
    category: 'Musica', 
    platform: 'Spotify',
    description: 'Música ilimitada sin anuncios y reproducción offline.',
    plans: PLATFORM_PLANS.Spotify.plans
  },
  { 
    id: 3, 
    name: 'Max Platino Estándar', 
    category: 'Streaming', 
    platform: 'Max',
    description: 'Series de HBO, películas de Warner y transmisiones en vivo.',
    plans: PLATFORM_PLANS.Max.plans
  },
  { 
    id: 4, 
    name: 'Disney+ / Star+ Combo', 
    category: 'Streaming', 
    platform: 'Disney+',
    description: 'Todo el contenido de Disney, Pixar, Marvel y deportes ESPN.',
    plans: PLATFORM_PLANS['Disney+'].plans
  },
  { 
    id: 5, 
    name: 'IPTV MagisTV Pro 1 Mes', 
    category: 'IPTV', 
    platform: 'IPTV',
    description: '+1200 canales en vivo, FHD, 24/7 y eventos deportivos.',
    plans: PLATFORM_PLANS.IPTV.plans
  },
])

// Modal de Configuración
const selectedProduct = ref(null)
const selectedPlanKey = ref('1_perfil')
const customProfiles = ref([])
const showConfigModal = ref(false)
const pinPreference = ref('same')
const isProfilesExpanded = ref(true)

// Sección Activa en Tienda: 'digital' (Streaming & Música) vs 'physical' (Productos Físicos del Catálogo)
const storeSection = ref('digital') // 'digital' | 'physical'

// Modal de Detalle de Producto Físico
const selectedPhysicalProduct = ref(null)
const showPhysicalModal = ref(false)
const physicalQuantity = ref(1)

// Modal del Carrito previo al envío
const showCartDrawer = ref(false)

// Modal y Formulario Checkout
const showCheckoutModal = ref(false)
const clientForm = ref({
  clientNumber: `CLI-${Math.floor(1000 + Math.random() * 9000)}`,
  fullName: '',
  whatsapp: '',
  shippingAddress: '',
  selectedResellerId: 'RES-01',
  paymentMethod: 'Transferencia Bancaria / CBU',
  transferVoucher: '',
  isSharedUrl: false
})

// Datos del Revendedor Seleccionado para confirmación de pago
const currentReseller = computed(() => {
  return adminStore.resellers.find(r => r.id === clientForm.value.selectedResellerId) || adminStore.resellers[0]
})

// Nombre comercial limpio de la tienda para los clientes
const resellerStoreName = computed(() => {
  const r = currentReseller.value
  if (!r) return 'TechStream Digital'
  if (r.storeName) return r.storeName
  return r.name.replace(/\s*\(Distribuidor\s*#?\d+\)/gi, '').replace(/^\[.*?\]\s*/, '').trim()
})

// Detección de Parámetros de URL: Afiliado (?ref=RES-01) y Producto compartido (?prod=962)
onMounted(() => {
  if (route.query.ref) {
    const matchedRes = adminStore.resellers.find(r => r.id === route.query.ref)
    if (matchedRes) {
      clientForm.value.selectedResellerId = matchedRes.id
      toast.add(`Atendido por distribuidor oficial: ${matchedRes.name}`, 'info')
    }
  }

  // Si viene con enlace de producto compartido (ej. ?prod=962 o ?p=962) o sección física (?sec=physical)
  if (route.query.sec === 'physical') {
    storeSection.value = 'physical'
  } else if (route.query.sec === 'packages' || route.query.pkg) {
    storeSection.value = 'packages'
    if (route.query.pkg) {
      clientForm.value.isSharedUrl = true
      toast.add('¡Mostrando paquete promocional seleccionado!', 'info')
    }
  }

  const sharedProdId = route.query.prod || route.query.p
  if (sharedProdId) {
    clientForm.value.isSharedUrl = true
    storeSection.value = 'physical'
    const foundProd = adminStore.physicalProducts.find(p => String(p.id) === String(sharedProdId))
    if (foundProd) {
      openPhysicalModal(foundProd)
      toast.add(`¡Bienvenido! Mostrando: ${foundProd.name.slice(0, 32)}...`, 'info')
    }
  }
})

const openConfig = (product) => {
  selectedProduct.value = product
  const firstKey = Object.keys(product.plans)[0]
  selectedPlanKey.value = firstKey
  pinPreference.value = 'same'
  updateProfilesList(product.plans[firstKey])
  isProfilesExpanded.value = true
  showConfigModal.value = true
}

const onPlanChange = () => {
  const planData = selectedProduct.value.plans[selectedPlanKey.value]
  updateProfilesList(planData)
}

const updateProfilesList = (planData) => {
  const units = planData.units
  const type = planData.type
  const platform = selectedProduct.value.platform.toUpperCase()

  customProfiles.value = Array.from({ length: units }, (_, idx) => ({
    name: type === 'perfil' 
      ? `PERFIL ${idx + 1} - ${platform}` 
      : `DISPOSITIVO ${idx + 1} - ${platform}`
  }))
}

const confirmAddToCart = () => {
  const planData = selectedProduct.value.plans[selectedPlanKey.value]
  
  cartStore.addItem(
    selectedProduct.value,
    selectedPlanKey.value,
    planData,
    customProfiles.value.map(p => ({ name: p.name.trim() || 'PERFIL CLIENTE' }))
  )

  const lastItem = cartStore.items[cartStore.items.length - 1]
  if (lastItem) {
    lastItem.pinPreference = pinPreference.value
  }

  toast.add(`${selectedProduct.value.name} (${planData.label}) añadido al carrito`, 'success')
  showConfigModal.value = false
}

// GESTIÓN DE PRODUCTOS FÍSICOS
const openPhysicalModal = (product) => {
  selectedPhysicalProduct.value = product
  physicalQuantity.value = 1
  showPhysicalModal.value = true
}

const addPhysicalToCart = (product, qty = 1) => {
  cartStore.addPhysicalItem(product, qty)
  toast.add(`"${product.name.slice(0, 30)}..." añadido al carrito`, 'success')
  showPhysicalModal.value = false
}

const copyShareProductUrl = (product) => {
  const resId = clientForm.value.selectedResellerId || 'RES-01'
  const origin = window.location.origin
  const shareUrl = `${origin}/?ref=${resId}&prod=${product.id}`
  navigator.clipboard.writeText(shareUrl).then(() => {
    toast.add('¡Enlace copiado! Gracias por ayudar compartiendo.', 'success')
  })
}

const shareProductWhatsApp = (product) => {
  const resId = clientForm.value.selectedResellerId || 'RES-01'
  const origin = window.location.origin
  const shareUrl = `${origin}/?ref=${resId}&prod=${product.id}`
  const msg = `🔥 *MIRA ESTE PRODUCTO EN STREAMPRO*\n📦 *${product.name}*\n💰 Precio: *${product.price}*\n\n👉 Compra directa aquí:\n${shareUrl}`
  window.open(`https://wa.me/?text=${encodeURIComponent(msg)}`, '_blank')
}

// Búsqueda y Filtros
const searchQuery = ref('')
const selectedCategory = ref('All')
const physicalCategoryFilter = ref('All')

const filteredProducts = computed(() => {
  return products.value.filter(p => {
    const matchCat = selectedCategory.value === 'All' || p.category === selectedCategory.value
    const matchSearch = p.name.toLowerCase().includes(searchQuery.value.toLowerCase())
    return matchCat && matchSearch
  })
})

const filteredPhysicalProducts = computed(() => {
  return adminStore.physicalProducts.filter(p => {
    const matchesCat = physicalCategoryFilter.value === 'All' || 
      (p.categoryIds && p.categoryIds.includes(physicalCategoryFilter.value))
    const q = searchQuery.value.toLowerCase().trim()
    const matchesQuery = !q || p.name.toLowerCase().includes(q) || (p.description && p.description.toLowerCase().includes(q))
    return matchesCat && matchesQuery
  })
})

const filteredPackages = computed(() => {
  return adminStore.packages.filter(p => {
    if (!p.active) return false
    const q = searchQuery.value.toLowerCase().trim()
    if (!q) return true
    const matchTitle = p.title.toLowerCase().includes(q)
    const matchDesc = p.description.toLowerCase().includes(q)
    const matchServices = p.services.some(s => s.platform.toLowerCase().includes(q))
    return matchTitle || matchDesc || matchServices
  })
})

const addPackageToCart = (pkg) => {
  cartStore.addPackageItem(pkg)
  toast.add(`¡Combo "${pkg.title}" agregado al pedido!`, 'success')
  showCartDrawer.value = true
}

const sharePackageLink = (pkg) => {
  const origin = typeof window !== 'undefined' && window.location?.origin ? window.location.origin : 'https://streampro.digital'
  const link = `${origin}/?ref=${clientForm.value.selectedResellerId}&pkg=${pkg.id}`
  navigator.clipboard.writeText(link).then(() => {
    toast.add(`¡Enlace copiado! Comparte este combo con tus contactos`, 'success')
  }).catch(() => {
    toast.add('Error al copiar enlace', 'error')
  })
}

const openCheckoutForm = () => {
  if (cartStore.items.length === 0) {
    toast.add('El carrito está vacío', 'error')
    return
  }
  showCartDrawer.value = false
  showCheckoutModal.value = true
}

const submitOrder = () => {
  if (!clientForm.value.fullName.trim() || !clientForm.value.whatsapp.trim()) {
    toast.add('Por favor completa Nombre y WhatsApp para el envío', 'error')
    return
  }

  const digitalItems = cartStore.items.filter(i => i.itemType !== 'physical')
  const physicalItems = cartStore.items.filter(i => i.itemType === 'physical')

  // Registrar órdenes digitales de streaming
  digitalItems.forEach(item => {
    adminStore.orders.push({
      id: `ORD-${Date.now().toString().slice(-4)}`,
      customerNumber: clientForm.value.clientNumber,
      customerName: clientForm.value.fullName,
      customerPhone: clientForm.value.whatsapp,
      platform: item.platform,
      devices: item.units,
      pinPreference: item.pinPreference || 'same',
      planKey: item.planKey,
      planLabel: item.planLabel,
      providerCost: item.providerCost,
      resellerProfit: item.resellerProfit,
      clientPrice: item.clientPrice,
      resellerId: clientForm.value.selectedResellerId,
      resellerName: currentReseller.value.name,
      voucher: clientForm.value.transferVoucher || 'Sin comprobante adjunto',
      paymentMethod: clientForm.value.paymentMethod,
      profileSelections: (item.profiles || []).map(p => ({
        name: p.name
      })),
      status: 'Waiting Provider',
      createdAt: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    })
  })

  // Registrar orden de productos físicos con comisión (1% desde tienda, 3% por link compartido)
  if (physicalItems.length > 0) {
    const source = clientForm.value.isSharedUrl ? 'shared_url' : 'store'
    adminStore.createPhysicalOrder({
      customerNumber: clientForm.value.clientNumber,
      customerName: clientForm.value.fullName,
      customerPhone: clientForm.value.whatsapp,
      shippingAddress: clientForm.value.shippingAddress || 'A coordinar por WhatsApp',
      resellerId: clientForm.value.selectedResellerId,
      source,
      items: physicalItems,
      paymentMethod: clientForm.value.paymentMethod
    })
  }

  cartStore.clearCart()
  showCheckoutModal.value = false
  const prevNum = clientForm.value.clientNumber
  clientForm.value = {
    clientNumber: `CLI-${Math.floor(1000 + Math.random() * 9000)}`,
    fullName: '',
    whatsapp: '',
    shippingAddress: '',
    selectedResellerId: 'RES-01',
    paymentMethod: 'Transferencia Bancaria / CBU',
    transferVoucher: '',
    isSharedUrl: false
  }

  if (physicalItems.length > 0 && digitalItems.length === 0) {
    toast.add(`¡Pedido físico registrado como PENDIENTE (#${prevNum})! No transfieras dinero: un vendedor oficial se comunicará contigo por WhatsApp para confirmar stock y coordinar el pago.`, 'success')
  } else if (physicalItems.length > 0 && digitalItems.length > 0) {
    toast.add(`¡Orden enviada (#${prevNum})! Suscripción digital en proceso. Para los productos físicos, un vendedor oficial te contactará por WhatsApp antes de transferir.`, 'success')
  } else {
    toast.add(`¡Orden enviada con éxito! Tu número de cliente asignado es ${prevNum}.`, 'success')
  }
}
</script>

<template>
  <div class="space-y-8">
    <!-- Hero Banner Promocional Enterprise -->
    <div class="glass-panel rounded-3xl p-8 sm:p-10 relative overflow-hidden border border-white/[0.08] shadow-2xl">
      <div class="absolute right-[-10%] top-[-20%] w-96 h-96 bg-gradient-to-br from-sky-500/20 via-blue-600/10 to-transparent rounded-full blur-3xl pointer-events-none"></div>
      <div class="max-w-3xl relative z-10">
        <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-sky-500/10 border border-sky-500/25 text-sky-400 text-xs font-bold uppercase tracking-wider mb-4 shadow-sm">
          <span class="w-2 h-2 rounded-full bg-sky-400 animate-pulse"></span>
          Entrega Automatizada & Stock 100% Garantizado
        </div>
        <h2 class="text-3xl sm:text-5xl font-black mb-3 text-white tracking-tight leading-tight">
          Streaming Premium al Mejor Costo.
        </h2>
        <p class="text-slate-300 text-sm sm:text-base leading-relaxed max-w-2xl font-normal">
          Acceso oficial por pantalla individual o cuentas multi-dispositivo.
          Personaliza los nombres de tus perfiles y selecciona tus preferencias de PIN con seguridad blindada.
        </p>

        <!-- Trust Metrics -->
        <div class="mt-6 flex flex-wrap items-center gap-3 text-xs font-semibold">
          <div class="flex items-center gap-2 bg-slate-900/80 px-3.5 py-2 rounded-xl border border-white/[0.08]">
            <span class="text-emerald-400 text-sm">✓</span>
            <span class="text-slate-200">Activación Inmediata</span>
          </div>
          <div class="flex items-center gap-2 bg-slate-900/80 px-3.5 py-2 rounded-xl border border-white/[0.08]">
            <span class="text-sky-400 text-sm">🔒</span>
            <span class="text-slate-200">Garantía SLA 30 Días</span>
          </div>
          <div class="flex items-center gap-2 bg-slate-900/80 px-3.5 py-2 rounded-xl border border-white/[0.08]">
            <span class="text-amber-400 text-sm">🛡️</span>
            <span class="text-slate-200">Protección Anti-Caídas</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Selector Principal de Sección: Streaming Digital vs Paquetes vs Productos Físicos -->
    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-white/[0.08] pb-4">
      <div class="flex flex-wrap p-1.5 bg-slate-900/80 backdrop-blur-xl rounded-2xl border border-white/[0.08] shadow-lg gap-1">
        <button 
          @click="storeSection = 'digital'"
          :class="[
            'px-5 py-3 rounded-xl font-black text-xs sm:text-sm transition-all flex items-center gap-2',
            storeSection === 'digital' 
              ? 'bg-gradient-to-r from-sky-400 to-blue-500 text-slate-950 shadow-lg shadow-sky-500/20' 
              : 'text-slate-400 hover:text-white hover:bg-white/[0.04]'
          ]"
        >
          <span>🎬</span>
          <span>Suscripciones Digitales</span>
        </button>

        <button 
          @click="storeSection = 'packages'"
          :class="[
            'px-5 py-3 rounded-xl font-black text-xs sm:text-sm transition-all flex items-center gap-2',
            storeSection === 'packages' 
              ? 'bg-gradient-to-r from-purple-500 via-pink-500 to-rose-500 text-white shadow-lg shadow-purple-500/20' 
              : 'text-slate-400 hover:text-white hover:bg-white/[0.04]'
          ]"
        >
          <span>🎁</span>
          <span>Paquetes & Combos en Oferta</span>
          <span class="bg-slate-950/80 text-pink-300 text-[10px] px-2 py-0.5 rounded-full font-mono border border-pink-400/30">
            {{ adminStore.packages.filter(p => p.active).length }}
          </span>
        </button>

        <button 
          @click="storeSection = 'physical'"
          :class="[
            'px-5 py-3 rounded-xl font-black text-xs sm:text-sm transition-all flex items-center gap-2',
            storeSection === 'physical' 
              ? 'bg-gradient-to-r from-amber-400 to-orange-500 text-slate-950 shadow-lg shadow-amber-500/20' 
              : 'text-slate-400 hover:text-white hover:bg-white/[0.04]'
          ]"
        >
          <span>📦</span>
          <span>Catálogo Físico</span>
          <span class="bg-slate-950/80 text-amber-300 text-[10px] px-2 py-0.5 rounded-full font-mono border border-amber-400/30">
            {{ adminStore.physicalProducts.length }}
          </span>
        </button>
      </div>

      <!-- Banner de Afiliación / Reseller Asignado (Sin porcentajes privados para el cliente) -->
      <div class="flex items-center gap-2 bg-slate-900/90 px-4 py-2.5 rounded-2xl border border-white/[0.08] text-xs">
        <span class="text-slate-400">Distribuidor Asignado:</span>
        <strong class="text-white font-bold bg-slate-800 px-2.5 py-0.5 rounded">{{ resellerStoreName }}</strong>
        <span v-if="clientForm.isSharedUrl" class="bg-emerald-500/20 text-emerald-300 font-bold px-2 py-0.5 rounded text-[11px] border border-emerald-500/30">
          🤝 Recomendación Compartida
        </span>
        <span v-else class="bg-sky-500/15 text-sky-300 font-bold px-2.5 py-0.5 rounded text-[11px] border border-sky-500/30 flex items-center gap-1 shadow-sm">
          🛍️ Tienda Oficial (VERIFICADO)
        </span>
      </div>
    </div>

    <!-- SECCIÓN 1: SUSCRIPCIONES DIGITALES -->
    <div v-if="storeSection === 'digital'" class="space-y-6">
      <!-- Barra de Búsqueda y Botón de Carrito -->
      <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
        <div class="flex gap-4 p-2 bg-slate-900/70 backdrop-blur-xl border border-white/[0.08] rounded-2xl flex-1 w-full shadow-lg">
          <div class="relative flex-1">
            <svg class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            <input v-model="searchQuery" type="text" placeholder="Buscar por Netflix, Max, Spotify, Disney, IPTV..." class="w-full bg-transparent border-none p-3 pl-10 text-white focus:outline-none focus:ring-0 text-sm placeholder:text-slate-500">
          </div>
          <div class="w-px bg-white/10 my-2"></div>
          <select v-model="selectedCategory" class="bg-transparent border-none p-3 text-white focus:outline-none cursor-pointer text-sm font-semibold pr-4">
            <option value="All" class="bg-slate-900">Todas las categorías</option>
            <option value="Streaming" class="bg-slate-900">Series & Películas</option>
            <option value="Musica" class="bg-slate-900">Música & Audio</option>
            <option value="IPTV" class="bg-slate-900">Televisión IPTV</option>
          </select>
        </div>

        <!-- Botón Ver Carrito Pro -->
        <button 
          @click="showCartDrawer = true" 
          class="relative bg-gradient-to-r from-sky-400 via-blue-500 to-indigo-600 hover:from-sky-300 hover:to-indigo-500 text-slate-950 font-black px-7 py-3.5 rounded-2xl transition-all shadow-[0_0_20px_rgba(56,189,248,0.3)] hover:shadow-[0_0_25px_rgba(56,189,248,0.5)] flex items-center gap-3 w-full sm:w-auto justify-center transform active:scale-95"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
          <span>Mi Carrito</span>
          <span v-if="cartStore.items.length > 0" class="bg-slate-950 text-white text-xs px-2.5 py-0.5 rounded-full font-bold shadow-inner border border-white/20">
            {{ cartStore.items.length }}
          </span>
        </button>
      </div>

      <!-- Banner Promocional Combos & Ofertas Especiales -->
      <div 
        @click="storeSection = 'packages'" 
        class="cursor-pointer bg-gradient-to-r from-purple-950/60 via-pink-950/40 to-slate-900 p-4 rounded-2xl border border-pink-500/30 hover:border-pink-500/60 transition-all shadow-xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 group"
      >
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-pink-500/20 text-pink-300 flex items-center justify-center text-xl shrink-0 group-hover:scale-110 transition-transform">
            🎁
          </div>
          <div>
            <div class="flex items-center gap-2">
              <span class="text-xs font-black uppercase tracking-wider bg-pink-500/20 text-pink-300 px-2 py-0.5 rounded border border-pink-500/30">
                ¡Super Ahorro!
              </span>
              <span class="text-xs text-slate-300 font-semibold">¿Quieres combinar plataformas en oferta?</span>
            </div>
            <p class="text-xs text-slate-400 mt-0.5">Descubre nuestros combos de streaming con hasta 35% de descuento directo en un solo pago.</p>
          </div>
        </div>
        <div class="flex items-center gap-2 text-pink-300 font-black text-xs group-hover:translate-x-1 transition-transform shrink-0">
          <span>Ver Paquetes en Oferta ({{ adminStore.packages.filter(p => p.active).length }})</span>
          <span>→</span>
        </div>
      </div>

      <!-- Catálogo de Suscripciones -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="product in filteredProducts" :key="product.id" class="glass-panel glass-panel-hover rounded-3xl p-6 border border-white/[0.08] flex flex-col justify-between relative overflow-hidden group">
          <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-sky-400 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
          <div>
            <div class="flex justify-between items-start mb-3">
              <span class="text-[10px] font-black text-sky-300 tracking-widest uppercase bg-sky-500/10 px-2.5 py-1 rounded-lg border border-sky-500/20">{{ product.category }}</span>
              <span class="text-xs text-slate-400 font-medium">Hasta 4 dispositivos</span>
            </div>
            <h3 class="text-xl font-bold text-white mb-2">{{ product.name }}</h3>
            <p class="text-xs text-slate-400 mb-4 line-clamp-2 leading-relaxed">{{ product.description }}</p>
            
            <div class="space-y-1.5 bg-slate-950/70 p-3 rounded-xl border border-slate-800 mb-4 text-xs">
              <div class="flex justify-between items-center text-slate-300">
                <span>1 Perfil Individual:</span>
                <strong class="text-white">${{ product.plans['1_perfil']?.clientPrice }} ARS</strong>
              </div>
              <div v-if="product.plans['2_dispositivos']" class="flex justify-between items-center text-slate-300">
                <span>2 Dispositivos:</span>
                <strong class="text-emerald-400">${{ product.plans['2_dispositivos']?.clientPrice }} ARS</strong>
              </div>
              <div v-if="product.plans['4_dispositivos']" class="flex justify-between items-center text-slate-300">
                <span>4 Dispositivos (Full):</span>
                <strong class="text-amber-300">${{ product.plans['4_dispositivos']?.clientPrice }} ARS</strong>
              </div>
            </div>
          </div>

          <button @click="openConfig(product)" class="w-full bg-slate-800 text-brand-accent hover:bg-brand-accent hover:text-slate-950 font-black py-3 px-4 rounded-xl transition-all duration-300 flex items-center justify-center gap-2 group-hover:bg-brand-accent group-hover:text-slate-950 shadow-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            Elegir Plan & Nombres
          </button>
        </div>
      </div>
    </div>

    <!-- SECCIÓN 2: PAQUETES & COMBOS EN OFERTA -->
    <div v-else-if="storeSection === 'packages'" class="space-y-6">
      <!-- Barra de Filtros y Ver Carrito para Combos -->
      <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
        <div class="flex gap-4 p-2 bg-slate-900/70 backdrop-blur-xl border border-white/[0.08] rounded-2xl flex-1 w-full shadow-lg">
          <div class="relative flex-1">
            <svg class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            <input v-model="searchQuery" type="text" placeholder="Buscar combos (Netflix + Spotify, Trío VIP, MagisTV, Disney)..." class="w-full bg-transparent border-none p-3 pl-10 text-white focus:outline-none focus:ring-0 text-sm placeholder:text-slate-500">
          </div>
        </div>

        <button 
          @click="showCartDrawer = true" 
          class="relative bg-gradient-to-r from-purple-500 via-pink-500 to-rose-500 hover:from-purple-400 hover:to-rose-400 text-white font-black px-7 py-3.5 rounded-2xl transition-all shadow-[0_0_20px_rgba(236,72,153,0.3)] flex items-center gap-3 w-full sm:w-auto justify-center transform active:scale-95 shrink-0"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
          <span>Ver Mi Pedido</span>
          <span v-if="cartStore.items.length > 0" class="bg-slate-950 text-white text-xs px-2.5 py-0.5 rounded-full font-bold shadow-inner border border-white/20">
            {{ cartStore.items.length }}
          </span>
        </button>
      </div>

      <!-- Banner de Garantía y Beneficios de los Combos -->
      <div class="bg-gradient-to-r from-purple-950/50 via-pink-950/40 to-slate-900 p-6 rounded-3xl border border-pink-500/30 shadow-2xl flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="flex items-center gap-3.5">
          <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-purple-500 to-pink-500 text-white flex items-center justify-center text-2xl shrink-0 shadow-lg shadow-pink-500/20">
            🎁
          </div>
          <div>
            <div class="flex items-center gap-2">
              <span class="text-xs font-black uppercase tracking-wider bg-pink-500/20 text-pink-300 px-2 py-0.5 rounded border border-pink-500/30">
                Ahorro Inteligente
              </span>
              <span class="text-xs text-slate-300 font-mono">Entrega y PINs Inmediatos</span>
            </div>
            <h3 class="text-xl font-black text-white mt-1">Paquetes Combinados & Ofertas Exclusivas</h3>
            <p class="text-xs text-slate-400">Contrata múltiples servicios en un solo pago con tarifas promocionales y soporte integral continuo.</p>
          </div>
        </div>
      </div>

      <!-- Grid de Paquetes -->
      <div v-if="filteredPackages.length === 0" class="text-center py-16 bg-slate-900/40 rounded-3xl border border-slate-800 text-slate-400">
        <span class="text-3xl block mb-2">🔍</span>
        <p class="text-sm font-bold text-white">No se encontraron combos para tu búsqueda.</p>
        <p class="text-xs text-slate-500 mt-1">Prueba borrando los filtros o explorando las suscripciones individuales.</p>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div 
          v-for="pkg in filteredPackages" 
          :key="pkg.id"
          class="glass-panel rounded-3xl p-6 border border-white/[0.08] hover:border-pink-500/40 transition-all flex flex-col justify-between relative group hover:shadow-2xl hover:shadow-pink-500/10"
        >
          <div class="space-y-4">
            <!-- Header de Tarjeta -->
            <div class="flex justify-between items-start gap-2">
              <span class="text-[10px] font-black uppercase tracking-wider bg-gradient-to-r from-pink-500 to-rose-500 text-white px-3 py-1 rounded-full shadow-sm">
                {{ pkg.badge || '🔥 OFERTA' }}
              </span>
              <span class="text-[11px] font-mono text-slate-400 bg-slate-900 px-2 py-0.5 rounded border border-slate-800">
                {{ pkg.services.length }} Plataformas
              </span>
            </div>

            <!-- Título y Descripción -->
            <div>
              <h3 class="text-lg font-black text-white group-hover:text-pink-300 transition-colors leading-tight">
                {{ pkg.title }}
              </h3>
              <p class="text-xs text-slate-400 mt-1.5 leading-relaxed line-clamp-2">
                {{ pkg.description }}
              </p>
            </div>

            <!-- Servicios Incluidos -->
            <div class="bg-slate-950/80 p-3.5 rounded-2xl border border-slate-800/80 space-y-2">
              <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Servicios Incluidos en el Combo:</span>
              <div class="space-y-1.5">
                <div 
                  v-for="(srv, sIdx) in pkg.services" 
                  :key="sIdx"
                  class="flex items-center justify-between text-xs bg-slate-900/60 p-2 rounded-xl border border-slate-800/60"
                >
                  <div class="flex items-center gap-2">
                    <span class="text-base">
                      {{ srv.platform === 'Netflix' ? '🍿' : srv.platform === 'Spotify' ? '🎧' : srv.platform === 'Disney+' ? '✨' : srv.platform === 'IPTV' ? '📺' : '🎬' }}
                    </span>
                    <strong class="text-white">{{ srv.platform }}</strong>
                    <span class="text-[11px] text-slate-400">({{ srv.planLabel }})</span>
                  </div>
                  <span class="text-[10px] text-slate-400 font-mono">1 mes</span>
                </div>
              </div>
            </div>

            <!-- Precio y Descuento -->
            <div class="bg-gradient-to-r from-slate-950 to-pink-950/20 p-4 rounded-2xl border border-pink-500/20 flex justify-between items-center">
              <div>
                <span class="text-[11px] text-slate-400 line-through block">Antes: ${{ pkg.originalPrice.toLocaleString() }} ARS</span>
                <span class="text-2xl font-black text-transparent bg-clip-text bg-gradient-to-r from-pink-400 via-rose-300 to-amber-300 font-mono">
                  ${{ pkg.promoPrice.toLocaleString() }} <span class="text-xs font-normal text-slate-400">ARS</span>
                </span>
              </div>
              <div v-if="pkg.originalPrice > pkg.promoPrice" class="bg-emerald-500/15 border border-emerald-500/30 text-emerald-400 text-xs font-bold px-2.5 py-1 rounded-xl text-center">
                <span>Ahorras</span>
                <div class="font-black text-sm">${{ (pkg.originalPrice - pkg.promoPrice).toLocaleString() }}</div>
              </div>
            </div>
          </div>

          <!-- Botones de Acción -->
          <div class="space-y-2 pt-4">
            <button 
              @click="addPackageToCart(pkg)" 
              class="w-full bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-400 hover:to-pink-400 text-white font-black py-3 px-4 rounded-xl transition-all shadow-lg shadow-pink-500/20 flex items-center justify-center gap-2"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
              <span>+ Agregar Combo al Pedido</span>
            </button>

            <button 
              @click="sharePackageLink(pkg)"
              class="w-full bg-slate-900/80 hover:bg-slate-800 text-slate-300 hover:text-white text-xs font-bold py-2.5 px-3 rounded-xl transition-all border border-slate-800 flex items-center justify-center gap-1.5"
            >
              <span>🤝</span>
              <span>AYUDAR COMPARTIENDO</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- SECCIÓN 3: CATÁLOGO DE PRODUCTOS FÍSICOS (C:\Users\yo\catalogo) -->
    <div v-else class="space-y-6">
      <!-- Filtros del Catálogo Físico -->
      <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
        <div class="flex gap-4 p-2 bg-slate-900/70 backdrop-blur-xl border border-white/[0.08] rounded-2xl flex-1 w-full shadow-lg">
          <div class="relative flex-1">
            <svg class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            <input v-model="searchQuery" type="text" placeholder="Buscar productos físicos (herramientas, hogar, electrónica, bazar)..." class="w-full bg-transparent border-none p-3 pl-10 text-white focus:outline-none focus:ring-0 text-sm placeholder:text-slate-500">
          </div>
          <div class="w-px bg-white/10 my-2"></div>
          <select v-model="physicalCategoryFilter" class="bg-transparent border-none p-3 text-white focus:outline-none cursor-pointer text-sm font-semibold pr-4">
            <option value="All" class="bg-slate-900">Todas las categorías ({{ adminStore.physicalProducts.length }})</option>
            <option v-for="cat in adminStore.physicalCategories" :key="cat.id" :value="cat.id" class="bg-slate-900">
              {{ cat.name }} ({{ cat.cantidad }})
            </option>
          </select>
        </div>

        <button 
          @click="showCartDrawer = true" 
          class="relative bg-gradient-to-r from-amber-400 via-orange-500 to-red-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 font-black px-7 py-3.5 rounded-2xl transition-all shadow-[0_0_20px_rgba(251,191,36,0.3)] flex items-center gap-3 w-full sm:w-auto justify-center"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
          <span>Mi Pedido</span>
          <span v-if="cartStore.items.length > 0" class="bg-slate-950 text-white text-xs px-2.5 py-0.5 rounded-full font-bold border border-white/20">
            {{ cartStore.items.length }}
          </span>
        </button>
      </div>

      <!-- Banner Oficial para Clientes con Imagen Solicitada -->
      <div class="w-full rounded-2xl overflow-hidden border border-amber-500/30 shadow-2xl relative bg-slate-950 group">
        <img 
          src="https://tua.ar/images/banner22.webp" 
          alt="Catálogo Oficial Productos Físicos StreamPro" 
          class="w-full h-auto max-h-64 sm:max-h-72 object-cover object-center group-hover:scale-[1.01] transition-transform duration-500"
          loading="eager"
        >
      </div>

      <!-- Banner Oficial de Fidelidad para Clientes -->
      <div class="bg-gradient-to-r from-amber-950/40 via-orange-950/30 to-slate-900 p-5 rounded-2xl border border-amber-500/30 shadow-xl flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="flex items-center gap-3.5">
          <div class="w-12 h-12 rounded-2xl bg-amber-500/20 text-amber-400 flex items-center justify-center text-2xl shrink-0 border border-amber-500/30 shadow-inner">
            📦
          </div>
          <div>
            <h3 class="text-base sm:text-lg font-black tracking-wider text-transparent bg-clip-text bg-gradient-to-r from-amber-300 via-orange-300 to-yellow-200 uppercase">
              ⭐ EL PRODUCTO QUE NECESITAS ESTA AQUI.
            </h3>
            <p class="text-xs text-slate-300 mt-0.5">
              Catálogo oficial con asesoría directa personalizada. No transfieras previo: confirmamos stock y envío contigo por WhatsApp.
            </p>
          </div>
        </div>
        <div class="text-xs font-mono text-amber-300 bg-slate-950/80 px-3.5 py-2 rounded-xl border border-amber-500/30 shrink-0 flex items-center gap-2">
          <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
          <span>Asesoría Directa Oficial</span>
        </div>
      </div>

      <!-- Grid de Productos Físicos -->
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
        <div 
          v-for="item in filteredPhysicalProducts" 
          :key="item.id" 
          class="bg-slate-900/80 rounded-2xl border border-white/[0.08] overflow-hidden flex flex-col justify-between hover:border-amber-400/40 hover:-translate-y-1 transition-all duration-300 shadow-lg group relative"
        >
          <!-- Envío Gratis Badge si corresponde -->
          <div v-if="item.priceNumeric >= 40000" class="absolute top-3 left-3 bg-emerald-500 text-slate-950 text-[10px] font-black px-2 py-0.5 rounded-full shadow-md z-10">
            ENVÍO GRATIS
          </div>

          <!-- Imagen del Producto -->
          <div class="w-full h-48 bg-white flex items-center justify-center p-3 overflow-hidden cursor-pointer relative" @click="openPhysicalModal(item)">
            <img 
              :src="item.image" 
              :alt="item.name" 
              class="max-h-full max-w-full object-contain group-hover:scale-105 transition-transform duration-300"
              loading="lazy"
            >
          </div>

          <!-- Información y Precios -->
          <div class="p-4 flex flex-col flex-1 justify-between space-y-3">
            <div>
              <h4 class="font-bold text-white text-sm line-clamp-2 leading-snug cursor-pointer hover:text-amber-300 transition-colors" @click="openPhysicalModal(item)">
                {{ item.name }}
              </h4>
              <p class="text-[11px] text-slate-400 line-clamp-2 mt-1 leading-relaxed">
                {{ item.description || 'Producto físico oficial con garantía.' }}
              </p>
            </div>

            <div class="pt-2 border-t border-slate-800">
              <div class="flex justify-between items-baseline mb-3">
                <span class="text-xs text-slate-400">Precio Oficial:</span>
                <span class="text-lg font-black text-amber-400">{{ item.price }}</span>
              </div>

              <!-- Botones de Acción: Ver Más, Agregar y Compartir Link -->
              <div class="space-y-2">
                <div class="flex gap-2">
                  <button 
                    @click="openPhysicalModal(item)" 
                    class="flex-1 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold py-2 rounded-xl transition-colors border border-slate-700 flex items-center justify-center gap-1"
                  >
                    <span>Ver Ficha</span>
                  </button>
                  <button 
                    @click="addPhysicalToCart(item, 1)" 
                    class="flex-1 bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-black py-2 rounded-xl transition-all shadow flex items-center justify-center gap-1"
                  >
                    <span>+ Carrito</span>
                  </button>
                </div>

                <!-- Botón AYUDAR COMPARTIENDO -->
                <div class="flex gap-1.5 pt-1">
                  <button 
                    @click="copyShareProductUrl(item)" 
                    class="flex-1 bg-amber-500/10 hover:bg-amber-500/20 text-[11px] font-black py-1.5 rounded-lg border border-amber-500/30 text-amber-300 hover:text-amber-200 transition-colors flex items-center justify-center gap-1.5"
                    title="Copiar enlace para ayudar compartiendo"
                  >
                    <span>🤝 AYUDAR COMPARTIENDO</span>
                  </button>
                  <button 
                    @click="shareProductWhatsApp(item)" 
                    class="bg-emerald-600/20 hover:bg-emerald-600 text-emerald-400 hover:text-white text-[11px] font-bold px-2 py-1.5 rounded-lg border border-emerald-500/30 transition-colors"
                    title="Compartir por WhatsApp"
                  >
                    💬
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL DE CONFIGURACIÓN -->
    <div v-if="showConfigModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm animate-in fade-in duration-200">
      <div class="bg-slate-900 border border-slate-700 w-full max-w-lg rounded-2xl p-6 shadow-2xl relative space-y-5">
        <div class="flex justify-between items-start border-b border-slate-800 pb-3">
          <div>
            <h3 class="text-xl font-black text-white">Configurar: {{ selectedProduct.name }}</h3>
            <p class="text-xs text-slate-400 mt-0.5">Elige la modalidad que deseas y personaliza los nombres sin complicaciones.</p>
          </div>
          <button @click="showConfigModal = false" class="text-slate-400 hover:text-white p-1">✕</button>
        </div>

        <!-- Selector Desplegable de Modalidades -->
        <div>
          <label class="block text-xs font-bold uppercase text-slate-400 tracking-wider mb-2">Selecciona tu Modalidad</label>
          <div class="relative">
            <select 
              v-model="selectedPlanKey" 
              @change="onPlanChange"
              class="w-full bg-slate-950 border-2 border-slate-700 focus:border-brand-accent rounded-xl p-3.5 text-white font-bold text-sm outline-none appearance-none cursor-pointer pr-10"
            >
              <option v-for="(plan, pKey) in selectedProduct.plans" :key="pKey" :value="pKey">
                {{ plan.label }} — ${{ plan.clientPrice }} ARS
              </option>
            </select>
            <svg class="w-5 h-5 absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
          </div>
        </div>

        <!-- Preferencia de PIN -->
        <div v-if="customProfiles.length > 1" class="bg-slate-950 p-3.5 rounded-xl border border-slate-800 space-y-2">
          <label class="block text-xs font-bold uppercase text-slate-300 tracking-wider">
            ¿Cómo prefieres los PINs de tus pantallas?
          </label>
          <div class="grid grid-cols-2 gap-2 text-xs">
            <button 
              type="button" 
              @click="pinPreference = 'same'"
              :class="[
                'p-2.5 rounded-lg border font-semibold text-center transition-all',
                pinPreference === 'same' 
                  ? 'bg-brand-accent/20 border-brand-accent text-brand-accent font-bold' 
                  : 'bg-slate-900 border-slate-800 text-slate-400 hover:text-white'
              ]"
            >
              🔒 Mismo PIN para ambos
            </button>
            <button 
              type="button" 
              @click="pinPreference = 'different'"
              :class="[
                'p-2.5 rounded-lg border font-semibold text-center transition-all',
                pinPreference === 'different' 
                  ? 'bg-emerald-500/20 border-emerald-500 text-emerald-400 font-bold' 
                  : 'bg-slate-900 border-slate-800 text-slate-400 hover:text-white'
              ]"
            >
              🔑 PINs distintos
            </button>
          </div>
        </div>

        <!-- Nombres de Perfiles -->
        <div class="border border-slate-800 rounded-xl overflow-hidden bg-slate-950/60">
          <button 
            type="button" 
            @click="isProfilesExpanded = !isProfilesExpanded"
            class="w-full p-3.5 flex justify-between items-center text-left hover:bg-slate-800/40 transition-colors"
          >
            <div class="flex items-center gap-2">
              <span class="text-brand-accent font-bold text-xs uppercase tracking-wider">
                Personalizar Nombres ({{ customProfiles.length }} {{ customProfiles.length === 1 ? 'perfil' : 'dispositivos' }})
              </span>
              <span class="text-[10px] text-slate-500 font-medium">(Opcional)</span>
            </div>
            <svg 
              :class="['w-4 h-4 text-slate-400 transition-transform duration-200', isProfilesExpanded ? 'rotate-180' : '']" 
              fill="none" 
              stroke="currentColor" 
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
          </button>

          <div v-show="isProfilesExpanded" class="p-3.5 pt-0 space-y-2 max-h-[220px] overflow-y-auto custom-scrollbar border-t border-slate-800/60 mt-1">
            <p class="text-[11px] text-slate-400 mb-2 italic">
              Escribe el nombre deseado para cada acceso. El PIN te llegará con las credenciales finales:
            </p>
            <div v-for="(prof, idx) in customProfiles" :key="idx" class="flex gap-2 items-center bg-slate-900/90 p-2 rounded-lg border border-slate-800">
              <span class="text-xs font-bold text-slate-500 w-6 text-center">#{{ idx + 1 }}</span>
              <input 
                v-model="prof.name" 
                type="text" 
                :placeholder="`Nombre ${idx + 1}`" 
                class="w-full bg-slate-950 border border-slate-800 rounded-md px-3 py-1.5 text-xs text-white font-mono focus:border-brand-accent outline-none"
              >
            </div>
          </div>
        </div>

        <div class="flex justify-between items-center bg-slate-950 p-4 rounded-xl border border-slate-800">
          <div>
            <p class="text-xs text-slate-400 font-medium">Total a pagar:</p>
            <p class="text-2xl font-black text-brand-accent">
              ${{ selectedProduct.plans[selectedPlanKey]?.clientPrice }} <span class="text-xs text-slate-400 font-normal">ARS</span>
            </p>
          </div>
          <button @click="confirmAddToCart" class="bg-brand-accent text-slate-950 font-black px-6 py-3 rounded-xl hover:bg-sky-400 transition-all shadow-md text-sm">
            Añadir a la Orden
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL CARRITO PREVIO AL ENVÍO -->
    <div v-if="showCartDrawer" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm animate-in fade-in duration-200">
      <div class="bg-slate-900 border border-slate-700 w-full max-w-2xl rounded-2xl p-6 shadow-2xl relative space-y-6">
        <div class="flex justify-between items-center border-b border-slate-800 pb-4">
          <div class="flex items-center gap-2">
            <svg class="w-6 h-6 text-brand-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
            <h3 class="text-xl font-black text-white">Carrito de Compras</h3>
          </div>
          <button @click="showCartDrawer = false" class="text-slate-400 hover:text-white p-1">✕</button>
        </div>

        <div v-if="cartStore.items.length === 0" class="text-center py-12 text-slate-500">
          <p class="text-sm">Tu carrito no tiene ítems añadidos aún.</p>
        </div>

        <div v-else class="space-y-4">
          <div class="space-y-3 max-h-[280px] overflow-y-auto pr-2 custom-scrollbar">
            <div v-for="(item, index) in cartStore.items" :key="index" class="bg-slate-950 p-4 rounded-xl border border-slate-800 flex justify-between items-start gap-4">
              <div class="space-y-1 flex-1">
                <div class="flex items-center gap-2">
                  <img v-if="item.image" :src="item.image" :alt="item.name" class="w-8 h-8 object-contain bg-white rounded p-0.5 shrink-0">
                  <h4 class="font-bold text-white text-sm line-clamp-1">{{ item.name }}</h4>
                  <span :class="['text-[10px] font-black uppercase px-2 py-0.5 rounded', item.itemType === 'physical' ? 'bg-amber-400/20 text-amber-300' : 'bg-brand-accent/20 text-brand-accent']">
                    {{ item.planLabel }}
                  </span>
                  <span v-if="item.units > 1 && item.itemType !== 'physical'" class="text-[10px] bg-slate-800 text-amber-300 font-bold px-2 py-0.5 rounded">
                    {{ item.pinPreference === 'same' ? 'Mismo PIN' : 'PINs Distintos' }}
                  </span>
                </div>
                
                <div v-if="item.profiles && item.profiles.length > 0" class="flex flex-wrap gap-1.5 text-xs pt-1">
                  <span v-for="(p, pIdx) in item.profiles" :key="pIdx" class="bg-slate-900 px-2 py-0.5 rounded text-slate-300 font-mono text-[11px] border border-slate-800">
                    👤 {{ p.name }}
                  </span>
                </div>

                <div class="text-xs text-slate-400 pt-1">
                  Precio unitario: <strong class="text-white">${{ Number(item.clientPrice).toLocaleString() }} ARS</strong>
                </div>
              </div>

              <div class="flex flex-col items-end gap-2">
                <div class="flex items-center gap-2 bg-slate-900 border border-slate-800 rounded-lg p-1">
                  <button @click="cartStore.updateItemQuantity(index, -1)" class="w-6 h-6 flex items-center justify-center text-slate-400 hover:text-white font-bold bg-slate-800 rounded">-</button>
                  <span class="text-xs font-bold px-1 text-white">{{ item.quantity }}</span>
                  <button @click="cartStore.updateItemQuantity(index, 1)" class="w-6 h-6 flex items-center justify-center text-slate-400 hover:text-white font-bold bg-slate-800 rounded">+</button>
                </div>
                <button @click="cartStore.removeItem(index)" class="text-xs text-red-400 hover:underline">Eliminar</button>
              </div>
            </div>
          </div>

          <div class="bg-slate-950 p-4 rounded-xl space-y-2 text-sm border border-slate-800">
            <div class="flex justify-between text-slate-400">
              <span>Subtotal:</span>
              <span class="text-white font-semibold">${{ cartStore.subtotal }} ARS</span>
            </div>
            <div v-if="cartStore.discountPercentage > 0" class="flex justify-between text-emerald-400 font-bold bg-emerald-400/10 p-2 rounded-lg">
              <span>Descuento por Volumen ({{ cartStore.discountPercentage }}%):</span>
              <span>- ${{ (cartStore.subtotal * (cartStore.discountPercentage / 100)).toFixed(2) }} ARS</span>
            </div>
            <div class="flex justify-between items-center text-lg font-black border-t border-slate-800 pt-2 text-white">
              <span>Total Final:</span>
              <span class="text-brand-accent text-2xl">${{ cartStore.total.toFixed(2) }} ARS</span>
            </div>
          </div>

          <div class="flex gap-3">
            <button @click="showCartDrawer = false" class="flex-1 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold py-3 rounded-xl transition-all text-sm">
              Seguir Comprando
            </button>
            <button @click="openCheckoutForm" class="flex-1 bg-brand-accent hover:bg-sky-400 text-slate-950 font-black py-3 rounded-xl transition-all shadow-lg text-sm flex items-center justify-center gap-2">
              <span>Continuar al Pago</span>
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL CHECKOUT: CON DATOS COMPLETOS DEL REVENDEDOR PARA CONFIRMAR PAGOS -->
    <div v-if="showCheckoutModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm animate-in fade-in duration-200">
      <div class="bg-slate-900 border border-slate-700 w-full max-w-lg rounded-2xl p-6 shadow-2xl relative space-y-5">
        <div class="flex justify-between items-start border-b border-slate-800 pb-3">
          <div>
            <div class="flex items-center gap-2">
              <h3 class="text-xl font-black text-white">Finalizar Pedido</h3>
              <span class="text-xs bg-slate-800 text-brand-accent font-mono font-bold px-2 py-0.5 rounded border border-slate-700">
                {{ clientForm.clientNumber }}
              </span>
            </div>
            <p class="text-xs text-slate-400 mt-0.5">Identificador de cliente generado para vincular tu perfil y servicio.</p>
          </div>
          <button @click="showCheckoutModal = false" class="text-slate-400 hover:text-white p-1">✕</button>
        </div>

        <form @submit.prevent="submitOrder" class="space-y-4 text-xs">
          <!-- Datos Personales del Cliente -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold uppercase text-slate-400 mb-1">Nombre Completo</label>
              <input v-model="clientForm.fullName" type="text" placeholder="Ej: Carlos Lopez" class="w-full bg-slate-950 border border-slate-800 focus:border-brand-accent rounded-xl p-3 text-white outline-none font-medium" required>
            </div>
            <div>
              <label class="block font-bold uppercase text-slate-400 mb-1">WhatsApp de Contacto</label>
              <input v-model="clientForm.whatsapp" type="tel" placeholder="+54 9 11 1234-5678" class="w-full bg-slate-950 border border-slate-800 focus:border-brand-accent rounded-xl p-3 text-white outline-none font-medium" required>
            </div>
          </div>

          <!-- Canal Oficial de Despacho Verificado (Sin datos técnicos internos de revendedores) -->
          <div class="flex items-center justify-between p-3.5 bg-slate-950 rounded-xl border border-slate-800">
            <div>
              <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Canal Oficial Asignado</span>
              <span class="text-xs font-bold text-white">{{ resellerStoreName }}</span>
            </div>
            <span class="text-[10px] font-mono font-bold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 px-2 py-0.5 rounded flex items-center gap-1">
              <span>✓</span> Canal Oficial
            </span>
          </div>

          <!-- AVISO PRODUCTOS FÍSICOS: PASOS A SEGUIR PARA EL CLIENTE -->
          <div v-if="cartStore.hasPhysicalItems" class="bg-gradient-to-br from-amber-950/60 via-slate-950 to-orange-950/40 p-4 rounded-xl border border-amber-500/40 space-y-2.5 shadow-lg">
            <div class="flex items-center gap-2 text-amber-300 font-black">
              <span class="text-xl">⚠️</span>
              <span class="text-xs uppercase tracking-wider">NO REALICES NINGUNA TRANSFERENCIA AHORA</span>
            </div>
            <p class="text-slate-200 text-xs leading-relaxed">
              Tu pedido quedará guardado en estado <strong class="text-amber-300 font-mono">PENDIENTE</strong>.
            </p>
            <div class="text-[11px] text-slate-300 space-y-2 bg-slate-900/80 p-3 rounded-lg border border-slate-800">
              <div class="font-bold text-amber-400 uppercase tracking-wider text-[10px]">Pasos a seguir:</div>
              <div class="flex items-start gap-2">
                <span class="text-amber-400 font-bold">1.</span>
                <span><strong>Te contactamos por WhatsApp:</strong> Un asesor se comunicará contigo para confirmar el stock de tu producto.</span>
              </div>
              <div class="flex items-start gap-2">
                <span class="text-amber-400 font-bold">2.</span>
                <span><strong>Coordinamos el envío:</strong> Acordaremos la dirección de entrega y el costo exacto hasta tu domicilio.</span>
              </div>
              <div class="flex items-start gap-2">
                <span class="text-amber-400 font-bold">3.</span>
                <span><strong>Pago seguro:</strong> Te brindaremos los datos oficiales para pagar únicamente cuando todo esté confirmado.</span>
              </div>
            </div>
          </div>

          <!-- TARJETA CON DATOS OFICIALES PARA TRANSFERENCIA (SOLO SI TIENE SERVICIOS DIGITALES) -->
          <div v-if="cartStore.hasDigitalItems" class="bg-gradient-to-br from-slate-950 to-indigo-950/40 p-4 rounded-xl border border-brand-accent/30 space-y-2">
            <div class="flex justify-between items-center border-b border-slate-800/80 pb-2">
              <span class="text-[10px] font-black uppercase tracking-wider text-brand-accent">Datos Oficiales para Transferencia (Suscripciones Digitales)</span>
              <span class="text-[11px] font-mono text-emerald-400">Verificado</span>
            </div>
            <div class="space-y-1 text-slate-300 font-mono text-xs">
              <p>Titular: <strong class="text-white">{{ currentReseller.holderName }}</strong></p>
              <p>Entidad: <span class="text-slate-300">{{ currentReseller.bankName }}</span></p>
              <p>CUIT/CUIL: <span class="text-slate-300">{{ currentReseller.cuit }}</span></p>
              <div class="bg-slate-900 p-2.5 rounded-lg border border-slate-800 flex justify-between items-center mt-1.5">
                <span class="text-amber-300 font-bold">Alias/CBU: {{ currentReseller.cbuAlias }}</span>
                <span class="text-[11px] text-slate-300 font-sans">WhatsApp: {{ currentReseller.contactNumber }}</span>
              </div>
            </div>
          </div>

          <!-- Dirección de Envío para Productos Físicos -->
          <div v-if="cartStore.hasPhysicalItems">
            <label class="block font-bold uppercase text-amber-400 mb-1">Dirección de Entrega / Envío (Productos Físicos)</label>
            <input 
              v-model="clientForm.shippingAddress" 
              type="text" 
              placeholder="Calle, Número, Piso/Depto, Localidad, Código Postal" 
              class="w-full bg-slate-950 border border-slate-800 focus:border-amber-400 rounded-xl p-3 text-white outline-none font-medium"
              required
            >
          </div>

          <!-- Comprobante de Pago (Solo para Items Digitales) -->
          <div v-if="cartStore.hasDigitalItems">
            <label class="block font-bold uppercase text-slate-400 mb-1">Número de Comprobante / Transacción (Opcional)</label>
            <input v-model="clientForm.transferVoucher" type="text" placeholder="Ej: OP-9843217" class="w-full bg-slate-950 border border-slate-800 focus:border-brand-accent rounded-xl p-3 text-white outline-none font-mono">
          </div>

          <div class="bg-slate-950 p-3 rounded-xl border border-slate-800 flex justify-between items-center text-sm">
            <span class="text-slate-400 font-semibold">
              {{ cartStore.hasPhysicalItems && !cartStore.hasDigitalItems ? 'Total Estimado del Pedido:' : 'Total a transferir:' }}
            </span>
            <span :class="['font-black text-lg', cartStore.hasPhysicalItems && !cartStore.hasDigitalItems ? 'text-amber-400' : 'text-brand-accent']">
              ${{ cartStore.total.toFixed(2) }} ARS
            </span>
          </div>

          <div class="flex gap-3 pt-2">
            <button type="button" @click="showCheckoutModal = false; showCartDrawer = true" class="w-1/3 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold py-3 rounded-xl">
              Volver
            </button>
            <button 
              type="submit" 
              :class="[
                'w-2/3 font-black py-3 rounded-xl transition-all shadow-lg text-sm',
                cartStore.hasPhysicalItems && !cartStore.hasDigitalItems 
                  ? 'bg-amber-400 hover:bg-amber-300 text-slate-950 shadow-amber-400/20' 
                  : 'bg-brand-accent text-slate-950 hover:bg-sky-400 shadow-brand-accent/20'
              ]"
            >
              {{ cartStore.hasPhysicalItems && !cartStore.hasDigitalItems ? 'Confirmar Pedido Físico (Estado Pendiente)' : 'Confirmar y Finalizar Pedido' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL DE DETALLE DE PRODUCTO FÍSICO -->
    <div v-if="showPhysicalModal && selectedPhysicalProduct" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm animate-in fade-in duration-200">
      <div class="bg-slate-900 border border-slate-700 w-full max-w-xl rounded-3xl p-6 shadow-2xl relative space-y-5 max-h-[90vh] overflow-y-auto custom-scrollbar">
        <div class="flex justify-between items-start border-b border-slate-800 pb-3">
          <div class="flex items-center gap-2">
            <span class="bg-amber-400/20 text-amber-300 text-[10px] font-black uppercase px-2 py-0.5 rounded border border-amber-400/30">
              Catálogo Físico Oficial
            </span>
            <span class="text-slate-400 text-xs font-mono">ID: #{{ selectedPhysicalProduct.id }}</span>
          </div>
          <button @click="showPhysicalModal = false" class="text-slate-400 hover:text-white p-1">✕</button>
        </div>

        <div class="flex flex-col sm:flex-row gap-6 items-center">
          <div class="w-full sm:w-48 h-48 bg-white rounded-2xl p-3 flex items-center justify-center shrink-0 shadow-inner">
            <img :src="selectedPhysicalProduct.image" :alt="selectedPhysicalProduct.name" class="max-h-full max-w-full object-contain">
          </div>
          <div class="space-y-2 flex-1">
            <h3 class="text-lg font-black text-white leading-snug">{{ selectedPhysicalProduct.name }}</h3>
            <div class="text-2xl font-black text-amber-400">{{ selectedPhysicalProduct.price }}</div>
            <div v-if="selectedPhysicalProduct.priceNumeric >= 40000" class="inline-block bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 text-xs font-bold px-2.5 py-0.5 rounded-full">
              ✓ Califica para Envío Gratis
            </div>
          </div>
        </div>

        <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 space-y-2 text-xs">
          <h4 class="font-bold text-slate-300 uppercase tracking-wider text-[11px]">Descripción y Especificaciones:</h4>
          <p class="text-slate-300 whitespace-pre-line leading-relaxed">{{ selectedPhysicalProduct.description || 'Sin descripción adicional.' }}</p>
        </div>

        <!-- Opción de Compartir Producto: AYUDAR COMPARTIENDO -->
        <div class="bg-gradient-to-r from-amber-950/40 via-slate-950 to-slate-950 p-4 rounded-2xl border border-amber-500/30 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs shadow-md">
          <div class="space-y-0.5">
            <div class="font-black text-amber-300 flex items-center gap-1.5">
              <span>🤝</span>
              <span>AYUDAR COMPARTIENDO</span>
            </div>
            <div class="text-[11px] text-slate-300">
              El producto que necesitas está aquí. Comparte el enlace directo con quien quieras.
            </div>
          </div>
          <div class="flex flex-wrap gap-2 shrink-0 w-full sm:w-auto">
            <button @click="copyShareProductUrl(selectedPhysicalProduct)" class="flex-1 sm:flex-none bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 font-black px-4 py-2 rounded-xl border border-amber-500/40 flex items-center justify-center gap-1.5 transition-all text-xs">
              <span>🤝 Ayudar Compartiendo</span>
            </button>
            <button @click="shareProductWhatsApp(selectedPhysicalProduct)" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold px-3 py-2 rounded-xl flex items-center justify-center gap-1 transition-all text-xs">
              <span>WhatsApp</span>
            </button>
            <button @click="openFlyerForPhysical(selectedPhysicalProduct)" class="bg-gradient-to-r from-amber-500/20 to-orange-500/20 hover:from-amber-500/30 text-amber-300 font-black px-3.5 py-2 rounded-xl border border-amber-500/40 flex items-center justify-center gap-1.5 transition-all text-xs" title="Crear Flyer publicitario para WhatsApp e Instagram de este producto">
              <span>🎨 Flyer</span>
            </button>
          </div>
        </div>

        <div class="flex items-center gap-3 pt-2">
          <div class="flex items-center border border-slate-800 rounded-xl bg-slate-950 p-1">
            <button @click="physicalQuantity = Math.max(1, physicalQuantity - 1)" class="w-8 h-8 rounded-lg bg-slate-800 text-white font-bold hover:bg-slate-700">-</button>
            <span class="px-3 text-sm font-bold text-white">{{ physicalQuantity }}</span>
            <button @click="physicalQuantity++" class="w-8 h-8 rounded-lg bg-slate-800 text-white font-bold hover:bg-slate-700">+</button>
          </div>
          <button @click="addPhysicalToCart(selectedPhysicalProduct, physicalQuantity)" class="flex-1 bg-amber-500 hover:bg-amber-400 text-slate-950 font-black py-3 rounded-xl transition-all shadow-lg text-sm flex items-center justify-center gap-2">
            <span>Agregar al Pedido ({{ physicalQuantity }} un.)</span>
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL GENERADOR DE FLYERS -->
    <FlyerGeneratorModal 
      :show="showFlyerModal" 
      :initial-product="flyerInitialProduct"
      :default-store-name="resellerStoreName"
      :default-phone="currentReseller?.contactNumber || adminStore.providerBankInfo.whatsapp"
      @close="showFlyerModal = false" 
    />
  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #334155;
  border-radius: 4px;
}
</style>

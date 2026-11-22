<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAdminStore } from '../store/admin'
import { useCartStore } from '../store/cart'
import { useToastStore } from '../store/toast'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  initialTab: {
    type: String,
    default: 'calculator' // 'calculator' | 'tracker' | 'gamer' | 'warranty' | 'quote'
  }
})

const emit = defineEmits(['update:modelValue', 'openCheckout'])

const router = useRouter()
const adminStore = useAdminStore()
const cartStore = useCartStore()
const toast = useToastStore()

const activeTab = ref(props.initialTab || 'calculator')

watch(() => props.initialTab, (newTab) => {
  if (newTab) activeTab.value = newTab
})

watch(() => props.modelValue, (isOpen) => {
  if (typeof document !== 'undefined') {
    document.body.style.overflow = isOpen ? 'hidden' : ''
  }
})

const closeModal = () => {
  emit('update:modelValue', false)
}

// ==========================================
// 1. HERRAMIENTA: CALCULADORA DE AHORRO
// ==========================================
const availableServices = ref([
  {
    id: 'netflix',
    name: 'Netflix 4K Ultra HD',
    platform: 'Netflix',
    category: 'streaming',
    icon: '🎬',
    officialPrice: 24800,
    storePrice: 8400,
    selected: true,
    tag: '4 Pantallas UHD'
  },
  {
    id: 'disney',
    name: 'Disney+ Premium (con ESPN & Star)',
    platform: 'Disney+',
    category: 'streaming',
    icon: '🍿',
    officialPrice: 21500,
    storePrice: 7200,
    selected: true,
    tag: 'Deportes en Vivo'
  },
  {
    id: 'max',
    name: 'Max Platino 4K (HBO Max)',
    platform: 'Max',
    category: 'streaming',
    icon: '⚡',
    officialPrice: 19200,
    storePrice: 5900,
    selected: true,
    tag: 'Warner & HBO'
  },
  {
    id: 'spotify',
    name: 'Spotify Premium Individual',
    platform: 'Spotify',
    category: 'streaming',
    icon: '🎵',
    officialPrice: 11800,
    storePrice: 4500,
    selected: false,
    tag: 'Sin Anuncios'
  },
  {
    id: 'youtube',
    name: 'YouTube Premium + Music',
    platform: 'YouTube',
    category: 'streaming',
    icon: '▶️',
    officialPrice: 13500,
    storePrice: 5200,
    selected: false,
    tag: 'Fondo & Sin Ads'
  },
  {
    id: 'prime',
    name: 'Amazon Prime Video Oficial',
    platform: 'Prime Video',
    category: 'streaming',
    icon: '📺',
    officialPrice: 9900,
    storePrice: 3900,
    selected: false,
    tag: 'Series Exclusivas'
  },
  {
    id: 'gaming_ps5',
    name: 'Juego PS5 Digital (Cuenta Primaria)',
    platform: 'PlayStation',
    category: 'gaming',
    icon: '🎮',
    officialPrice: 118000,
    storePrice: 54000,
    selected: false,
    tag: 'PS Store Oficial c/ Impuestos'
  },
  {
    id: 'gaming_ps4',
    name: 'Juego PS4 Digital (Cuenta Primaria)',
    platform: 'PlayStation',
    category: 'gaming',
    icon: '🕹️',
    officialPrice: 82000,
    storePrice: 38000,
    selected: false,
    tag: 'PS Store Oficial c/ Impuestos'
  }
])

const toggleService = (srv) => {
  srv.selected = !srv.selected
}

const selectAllServices = (val) => {
  availableServices.value.forEach(s => s.selected = val)
}

const totalOfficialMonthly = computed(() => {
  return availableServices.value
    .filter(s => s.selected)
    .reduce((acc, s) => acc + s.officialPrice, 0)
})

const totalStoreMonthly = computed(() => {
  return availableServices.value
    .filter(s => s.selected)
    .reduce((acc, s) => acc + s.storePrice, 0)
})

const monthlySavings = computed(() => {
  return Math.max(0, totalOfficialMonthly.value - totalStoreMonthly.value)
})

const annualSavings = computed(() => {
  return monthlySavings.value * 12
})

const savingsPercentage = computed(() => {
  if (totalOfficialMonthly.value === 0) return 0
  return Math.round((monthlySavings.value / totalOfficialMonthly.value) * 100)
})

const addCalculatedComboToCart = () => {
  const selected = availableServices.value.filter(s => s.selected)
  if (selected.length === 0) {
    toast.add('Selecciona al menos un servicio para armar tu combo', 'warning')
    return
  }

  let addedCount = 0
  selected.forEach(s => {
    // Buscar si existe producto digital en adminStore
    const prod = adminStore.products.find(p => 
      p.platform.toLowerCase().includes(s.platform.toLowerCase()) || 
      p.name.toLowerCase().includes(s.platform.toLowerCase())
    )
    if (prod) {
      cartStore.addItem({
        id: prod.id,
        name: prod.name,
        price: prod.clientPrice || s.storePrice,
        type: 'digital',
        platform: prod.platform,
        profileCount: 1
      })
      addedCount++
    }
  })

  toast.add(`🛒 Se agregaron ${addedCount || selected.length} servicios al carrito con descuento`, 'success')
  closeModal()
  cartStore.openDrawer()
}

const shareCalculatedBudgetWhatsApp = () => {
  const selected = availableServices.value.filter(s => s.selected)
  if (selected.length === 0) {
    toast.add('Selecciona al menos un servicio para compartir', 'warning')
    return
  }

  const lines = selected.map(s => `  • ${s.icon} *${s.name}*: $${s.storePrice.toLocaleString('es-AR')} ARS (Oficial c/ imp: ~$${s.officialPrice.toLocaleString('es-AR')})`).join('\n')

  const text = `📊 *PRESUPUESTO INTELIGENTE STREAMEPRO*\n` +
    `⚡ *Servicios seleccionados:*\n${lines}\n\n` +
    `💳 *Costo oficial mensual (c/ 60% recargo bancario):* $${totalOfficialMonthly.value.toLocaleString('es-AR')} ARS\n` +
    `🔥 *Costo en StreamePro Oficial:* $${totalStoreMonthly.value.toLocaleString('es-AR')} ARS\n` +
    `🎉 *¡Ahorro Mensual Real:* $${monthlySavings.value.toLocaleString('es-AR')} ARS (-${savingsPercentage.value}%)\n` +
    `🚀 *Ahorro Anual Proyectado:* $${annualSavings.value.toLocaleString('es-AR')} ARS/año\n\n` +
    `🛡️ Cuentas oficiales, PIN privado y garantía 100% permanente.\n` +
    `👉 *Ver y activar aquí:* https://streamepro.me/`

  const waUrl = `https://wa.me/?text=${encodeURIComponent(text)}`
  window.open(waUrl, '_blank')
}

// ==========================================
// 2. HERRAMIENTA: RASTREADOR DE PEDIDOS & PIN
// ==========================================
const searchOrderQuery = ref('')
const orderSearchResult = ref(null)
const searchPerformed = ref(false)
const showPinCode = ref(false)

const trackOrder = () => {
  const q = searchOrderQuery.value.trim().toLowerCase()
  if (!q) {
    toast.add('Ingresa tu número de pedido (ej: SP-1024) o tu teléfono de WhatsApp', 'warning')
    return
  }

  searchPerformed.value = true
  showPinCode.value = false

  // Buscar en store de pedidos digitales
  const cleanQ = q.replace('#', '').replace(/\s+/g, '')
  const foundDigital = (adminStore.orders || []).find(o => {
    const idMatch = String(o.id || '').toLowerCase().includes(cleanQ)
    const phoneMatch = String(o.clientPhone || '').replace(/\D/g, '').includes(cleanQ)
    return idMatch || phoneMatch
  })

  // Buscar en store de pedidos físicos
  const foundPhysical = !foundDigital ? (adminStore.physicalOrders || []).find(o => {
    const idMatch = String(o.id || '').toLowerCase().includes(cleanQ)
    const phoneMatch = String(o.clientPhone || '').replace(/\D/g, '').includes(cleanQ)
    return idMatch || phoneMatch
  }) : null

  if (foundDigital) {
    orderSearchResult.value = {
      type: 'digital',
      id: foundDigital.id,
      title: `${foundDigital.platform || 'Suscripción Digital'} - Perfil Oficial`,
      status: foundDigital.status || 'En Proceso',
      date: foundDigital.createdAt ? new Date(foundDigital.createdAt).toLocaleDateString('es-AR') : 'Hoy',
      pin: foundDigital.pin || 'Generado en Entrega',
      email: foundDigital.email || 'Entregado por WhatsApp',
      profileName: foundDigital.profileName || 'Perfil Personal Asignado',
      clientPhone: foundDigital.clientPhone,
      amount: foundDigital.clientPrice || 0
    }
  } else if (foundPhysical) {
    orderSearchResult.value = {
      type: 'physical',
      id: foundPhysical.id,
      title: foundPhysical.productName || 'Producto Físico StreamePro',
      status: foundPhysical.status || 'En Preparación',
      date: foundPhysical.createdAt ? new Date(foundPhysical.createdAt).toLocaleDateString('es-AR') : 'Hoy',
      shippingAddress: foundPhysical.shippingAddress || 'Dirección registrada',
      clientPhone: foundPhysical.clientPhone,
      amount: foundPhysical.total || foundPhysical.clientPrice || 0
    }
  } else {
    // Si no está en memoria local inmediata, ofrecer consulta en vivo vía WhatsApp con operadores
    orderSearchResult.value = {
      notFound: true,
      query: searchOrderQuery.value.trim()
    }
  }
}

const getStatusBadge = (status) => {
  const s = String(status || '').toLowerCase()
  if (s.includes('dispatch') || s.includes('entreg') || s.includes('listo') || s.includes('aprob') || s.includes('confirm')) {
    return { text: '🟢 Entregado / Activo', class: 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30' }
  }
  if (s.includes('wait') || s.includes('pend') || s.includes('proc') || s.includes('cola')) {
    return { text: '🟡 En Cola de Activación (10-60m)', class: 'bg-amber-500/20 text-amber-300 border-amber-500/30' }
  }
  return { text: '🔵 En Verificación', class: 'bg-sky-500/20 text-sky-300 border-sky-500/30' }
}

const consultWhatsAppOrder = (orderId) => {
  const text = `Hola StreamePro, quiero consultar el estado de activación de mi pedido *#${orderId || searchOrderQuery.value}*. ¿Me podrían confirmar por favor?`
  window.open(`https://wa.me/5491136000000?text=${encodeURIComponent(text)}`, '_blank')
}

// ==========================================
// 3. HERRAMIENTA: ASISTENTE GAMER
// ==========================================
const gamerConsole = ref('PS5')
const gamerUserPref = ref('personal') // 'personal' | 'any'
const gamerConnection = ref('always') // 'always' | 'offline'

const gamerRecommendation = computed(() => {
  if (gamerUserPref.value === 'personal' || gamerConnection.value === 'offline') {
    return {
      type: 'Primaria',
      badge: '👑 RECOMENDADA: CUENTA PRIMARIA',
      badgeClass: 'bg-amber-500/20 text-amber-300 border-amber-500/40',
      summary: 'Experiencia original 100% idéntica a comprar el juego en tu propia PlayStation Store.',
      benefits: [
        'Juegas desde tu usuario personal de siempre',
        'Acumulas y sincronizas trofeos en tu PSN ID',
        'Juegas online con tus amigos en multijugador',
        'Funciona con o sin conexión a internet'
      ],
      idealFor: 'Jugadores que quieren conservar sus partidas en su cuenta principal y ganar trofeos.',
      saving: 'Ahorras entre un 40% y 55% sobre el precio de PS Store con impuestos.'
    }
  } else {
    return {
      type: 'Secundaria',
      badge: '⚡ RECOMENDADA: CUENTA SECUNDARIA',
      badgeClass: 'bg-indigo-500/20 text-indigo-300 border-indigo-500/40',
      summary: 'La opción con el precio más accesible y económico de todo el mercado gamer.',
      benefits: [
        'Juegas desde el perfil exclusivo que te entregamos',
        'Juego 100% completo, sin recortes de contenido',
        'Misma calidad gráfica 4K / 60FPS según consola',
        'Requiere conexión a internet activa para jugar'
      ],
      idealFor: 'Gamers que quieren pasar la historia o campaña gastando la menor cantidad de dinero.',
      saving: 'Ahorras hasta un 70% sobre el precio oficial de PlayStation Store.'
    }
  }
})

const goToGamesCatalog = (licenseType) => {
  closeModal()
  router.push('/juegos')
}

// ==========================================
// 4. HERRAMIENTA: VALIDADOR DE GARANTÍA
// ==========================================
const guaranteePillars = [
  {
    icon: '🛡️',
    title: 'Garantía SLA 30 Días de Reposición',
    desc: 'Si experimentas cualquier interrupción o inconveniente con tu perfil, nuestro soporte técnico te brinda reposición inmediata sin costos adicionales.'
  },
  {
    icon: '🔒',
    title: 'PIN Personal & Privado',
    desc: 'Nunca compartes pantalla ni clave de perfil. Cada usuario dispone de su propio espacio seguro, garantizando que tu contenido se mantenga donde lo dejaste.'
  },
  {
    icon: '⚡',
    title: 'Activación en 10m a 60m',
    desc: 'Operadores reales de turno atienden las activaciones de lunes a domingo para que puedas disfrutar de tu entretenimiento en minutos.'
  },
  {
    icon: '🇦🇷',
    title: 'Pesos Argentinos Sin Impuestos Extra',
    desc: 'Sin recargos sorpresa del 60% de tarjetas ni percepciones bancarias en dólares. El precio publicado es el precio final que pagas.'
  }
]

const copyText = (txt, msg) => {
  if (!txt) return
  navigator.clipboard.writeText(txt).then(() => {
    toast.add(msg || 'Copiado al portapapeles', 'success')
  })
}
</script>

<template>
  <Transition name="fade">
    <div 
      v-if="modelValue" 
      class="fixed inset-0 z-[70] flex items-center justify-center p-2 sm:p-4 bg-slate-950/85 backdrop-blur-xl overflow-y-auto"
      @click.self="closeModal"
    >
      <div 
        class="relative w-full max-w-4xl my-auto rounded-2xl sm:rounded-3xl bg-slate-900/95 border border-white/10 shadow-[0_0_50px_rgba(56,189,248,0.15)] overflow-hidden flex flex-col max-h-[94vh] sm:max-h-[92vh]"
      >
        <!-- Ambient Top Neon Glow -->
        <div class="absolute top-0 inset-x-0 h-1.5 bg-gradient-to-r from-sky-400 via-indigo-500 to-amber-400"></div>
        <div class="absolute -top-24 -right-24 w-60 h-60 bg-sky-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -left-24 w-60 h-60 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <!-- HEADER DEL MODAL -->
        <div class="p-3.5 sm:p-6 border-b border-white/[0.08] flex items-center justify-between shrink-0 bg-slate-900/60 backdrop-blur-md">
          <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
            <div class="w-9 h-9 sm:w-11 sm:h-11 rounded-xl sm:rounded-2xl bg-gradient-to-br from-sky-500/20 via-indigo-500/20 to-purple-500/20 border border-sky-400/30 flex items-center justify-center text-lg sm:text-2xl shadow-inner shrink-0">
              🔧
            </div>
            <div class="min-w-0">
              <div class="flex items-center gap-1.5 sm:gap-2">
                <h3 class="text-sm sm:text-2xl font-black text-white tracking-tight truncate">Centro de Herramientas</h3>
                <span class="text-[9px] sm:text-[10px] font-black uppercase tracking-wider px-1.5 sm:px-2 py-0.5 rounded-full bg-sky-500/20 text-sky-300 border border-sky-500/30 font-mono shrink-0">
                  PRO SUITE
                </span>
              </div>
              <p class="text-[11px] sm:text-xs text-slate-300 mt-0.5 line-clamp-1 sm:line-clamp-none">Calculadora inteligente, rastreo de órdenes, licencias gamer y garantía SLA</p>
            </div>
          </div>

          <button 
            @click="closeModal" 
            class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-slate-800/80 hover:bg-slate-700 text-slate-400 hover:text-white transition-all flex items-center justify-center border border-white/10 cursor-pointer shadow-sm shrink-0 ml-2 touch-manipulation"
            aria-label="Cerrar modal"
          >
            ✕
          </button>
        </div>

        <!-- TABS DE NAVEGACIÓN DE HERRAMIENTAS -->
        <div class="px-3 sm:px-6 pt-2.5 pb-2 border-b border-white/[0.06] bg-slate-950/40 shrink-0 flex items-center gap-1.5 overflow-x-auto no-scrollbar touch-pan-x">
          <button 
            type="button"
            @click="activeTab = 'calculator'"
            :class="[
              'px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-xl text-[11px] sm:text-xs font-bold transition-all flex items-center gap-1.5 shrink-0 cursor-pointer border select-none touch-manipulation',
              activeTab === 'calculator'
                ? 'bg-gradient-to-r from-sky-500/25 to-blue-600/25 text-sky-300 border-sky-400/50 shadow-md font-black'
                : 'text-slate-400 hover:text-slate-200 border-transparent hover:bg-white/[0.04]'
            ]"
          >
            <span>🧮</span>
            <span>Calculadora</span>
            <span class="text-[8px] sm:text-[9px] px-1.5 py-0.2 rounded-full bg-emerald-500/20 text-emerald-300 font-mono font-black border border-emerald-500/30">
              -68%
            </span>
          </button>

          <button 
            type="button"
            @click="activeTab = 'tracker'"
            :class="[
              'px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-xl text-[11px] sm:text-xs font-bold transition-all flex items-center gap-1.5 shrink-0 cursor-pointer border select-none touch-manipulation',
              activeTab === 'tracker'
                ? 'bg-gradient-to-r from-emerald-500/25 to-teal-600/25 text-emerald-300 border-emerald-400/50 shadow-md font-black'
                : 'text-slate-400 hover:text-slate-200 border-transparent hover:bg-white/[0.04]'
            ]"
          >
            <span>🔍</span>
            <span>Rastrear Pedido</span>
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
          </button>

          <button 
            type="button"
            @click="activeTab = 'gamer'"
            :class="[
              'px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-xl text-[11px] sm:text-xs font-bold transition-all flex items-center gap-1.5 shrink-0 cursor-pointer border select-none touch-manipulation',
              activeTab === 'gamer'
                ? 'bg-gradient-to-r from-indigo-500/25 to-purple-600/25 text-indigo-300 border-indigo-400/50 shadow-md font-black'
                : 'text-slate-400 hover:text-slate-200 border-transparent hover:bg-white/[0.04]'
            ]"
          >
            <span>🎮</span>
            <span>Asistente Gamer</span>
            <span class="text-[8px] sm:text-[9px] px-1.5 py-0.2 rounded-full bg-indigo-500/20 text-indigo-300 font-mono font-bold border border-indigo-500/30">
              PS5/PS4
            </span>
          </button>

          <button 
            type="button"
            @click="activeTab = 'warranty'"
            :class="[
              'px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-xl text-[11px] sm:text-xs font-bold transition-all flex items-center gap-1.5 shrink-0 cursor-pointer border select-none touch-manipulation',
              activeTab === 'warranty'
                ? 'bg-gradient-to-r from-amber-500/25 to-orange-600/25 text-amber-300 border-amber-400/50 shadow-md font-black'
                : 'text-slate-400 hover:text-slate-200 border-transparent hover:bg-white/[0.04]'
            ]"
          >
            <span>🛡️</span>
            <span>Garantía & SLA</span>
          </button>
        </div>

        <!-- CUERPO PRINCIPAL DEL MODAL (SCROLLABLE) -->
        <div class="p-3.5 sm:p-6 overflow-y-auto space-y-4 sm:space-y-6 overscroll-contain">

          <!-- ============================================== -->
          <!-- TAB 1: CALCULADORA DE AHORRO INTELIGENTE -->
          <!-- ============================================== -->
          <div v-if="activeTab === 'calculator'" class="space-y-4 sm:space-y-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2.5 sm:gap-3 bg-sky-950/30 border border-sky-500/25 rounded-xl sm:rounded-2xl p-3 sm:p-4">
              <div>
                <h4 class="text-xs sm:text-base font-black text-white flex items-center gap-2">
                  <span>💡</span> ¿Cuánto gastas hoy vs en StreamePro?
                </h4>
                <p class="text-[11px] sm:text-xs text-slate-300 mt-0.5">
                  En Argentina, las tarjetas bancarias suman un <strong>60% de impuestos</strong>. En StreamePro abonas en pesos finales sin recargos.
                </p>
              </div>
              <div class="grid grid-cols-2 gap-2 w-full sm:w-auto shrink-0">
                <button 
                  type="button"
                  @click="selectAllServices(true)" 
                  class="px-2.5 py-1.5 rounded-lg bg-slate-800 text-sky-300 text-xs font-bold hover:bg-slate-700 transition cursor-pointer text-center touch-manipulation"
                >
                  Marcar Todos
                </button>
                <button 
                  type="button"
                  @click="selectAllServices(false)" 
                  class="px-2.5 py-1.5 rounded-lg bg-slate-800 text-slate-400 text-xs font-bold hover:bg-slate-700 transition cursor-pointer text-center touch-manipulation"
                >
                  Limpiar
                </button>
              </div>
            </div>

            <!-- Grilla interactiva de selección de plataformas -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-3">
              <div 
                v-for="srv in availableServices"
                :key="srv.id"
                @click="toggleService(srv)"
                :class="[
                  'p-2.5 sm:p-3.5 rounded-xl sm:rounded-2xl border transition-all cursor-pointer flex items-center justify-between gap-2.5 group relative overflow-hidden touch-manipulation',
                  srv.selected 
                    ? 'bg-slate-800/90 border-sky-400/50 shadow-[0_0_20px_rgba(56,189,248,0.15)] ring-1 ring-sky-400/30' 
                    : 'bg-slate-900/50 border-white/[0.06] hover:border-white/20 opacity-75 hover:opacity-100'
                ]"
              >
                <div class="flex items-center gap-2.5 min-w-0">
                  <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-slate-950/80 border border-white/10 flex items-center justify-center text-lg sm:text-xl shrink-0 group-hover:scale-105 transition-transform">
                    {{ srv.icon }}
                  </div>
                  <div class="min-w-0">
                    <div class="flex items-center gap-1.5">
                      <span class="text-xs font-black text-white group-hover:text-sky-300 transition-colors truncate">{{ srv.name }}</span>
                    </div>
                    <span class="text-[10px] text-slate-400 font-mono block truncate">{{ srv.tag }}</span>
                    <div class="flex items-center gap-2 mt-0.5 text-[10px] sm:text-[11px] flex-wrap">
                      <span class="text-slate-400 line-through font-mono">Oficial: ${{ srv.officialPrice.toLocaleString('es-AR') }}</span>
                      <span class="text-emerald-400 font-bold font-mono">StreamePro: ${{ srv.storePrice.toLocaleString('es-AR') }}</span>
                    </div>
                  </div>
                </div>

                <!-- Custom Checkbox Indicator -->
                <div 
                  :class="[
                    'w-5 h-5 sm:w-6 sm:h-6 rounded-lg flex items-center justify-center shrink-0 border transition-all font-bold text-xs ml-1',
                    srv.selected 
                      ? 'bg-sky-400 text-slate-950 border-sky-300 shadow-sm' 
                      : 'border-slate-700 bg-slate-950/60 text-transparent'
                  ]"
                >
                  ✓
                </div>
              </div>
            </div>

            <!-- DASHBOARD DE AHORRO CALCULADO (Adaptado: 2 columnas superiores + ahorro full width en móvil) -->
            <div class="rounded-2xl sm:rounded-3xl bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 border border-emerald-500/30 p-3.5 sm:p-5 shadow-2xl relative overflow-hidden">
              <div class="absolute -right-10 -bottom-10 w-44 h-44 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

              <div class="grid grid-cols-2 md:grid-cols-3 gap-2.5 sm:gap-4 text-left items-center">
                <!-- Tarjeta 1: Precio Oficial Banco -->
                <div class="p-2.5 sm:p-3.5 rounded-xl sm:rounded-2xl bg-slate-900/80 border border-white/[0.06]">
                  <span class="text-[9px] sm:text-[10px] font-bold uppercase tracking-wider text-slate-400 line-clamp-1">Oficial (+60% imp.)</span>
                  <div class="text-base sm:text-2xl font-black text-red-400 font-mono mt-0.5 sm:mt-1">
                    ${{ totalOfficialMonthly.toLocaleString('es-AR') }} <span class="text-[10px] sm:text-xs font-normal text-slate-400">/mes</span>
                  </div>
                  <span class="text-[9px] sm:text-[10px] text-slate-500 block mt-0.5 line-clamp-1">Con recargos bancarios</span>
                </div>

                <!-- Tarjeta 2: Precio StreamePro -->
                <div class="p-2.5 sm:p-3.5 rounded-xl sm:rounded-2xl bg-sky-950/40 border border-sky-500/30">
                  <span class="text-[9px] sm:text-[10px] font-bold uppercase tracking-wider text-sky-300 line-clamp-1">Precio StreamePro</span>
                  <div class="text-base sm:text-2xl font-black text-sky-400 font-mono mt-0.5 sm:mt-1">
                    ${{ totalStoreMonthly.toLocaleString('es-AR') }} <span class="text-[10px] sm:text-xs font-normal text-sky-300">/mes</span>
                  </div>
                  <span class="text-[9px] sm:text-[10px] text-emerald-400 font-semibold block mt-0.5 line-clamp-1">0% impuestos extra</span>
                </div>

                <!-- Tarjeta 3: Tu Ahorro Real (2 cols en móvil, 1 col en desktop) -->
                <div class="col-span-2 md:col-span-1 p-2.5 sm:p-3.5 rounded-xl sm:rounded-2xl bg-gradient-to-br from-emerald-950/60 to-emerald-900/30 border border-emerald-400/50 shadow-lg">
                  <div class="flex items-center justify-between">
                    <span class="text-[10px] font-black uppercase tracking-wider text-emerald-300">🎉 Ahorro Mensual</span>
                    <span class="text-xs font-black px-2 py-0.5 rounded-full bg-emerald-400 text-slate-950 font-mono animate-pulse">
                      -{{ savingsPercentage }}%
                    </span>
                  </div>
                  <div class="text-xl sm:text-3xl font-black text-emerald-300 font-mono mt-0.5 sm:mt-1">
                    ${{ monthlySavings.toLocaleString('es-AR') }}
                  </div>
                  <div class="text-[10px] sm:text-[11px] font-bold text-amber-300 mt-0.5 sm:mt-1">
                    🚀 ¡Ahorras ${{ annualSavings.toLocaleString('es-AR') }} al año!
                  </div>
                </div>
              </div>

              <!-- BOTONES DE ACCIÓN RÁPIDA -->
              <div class="mt-4 sm:mt-5 pt-3 sm:pt-4 border-t border-white/[0.08] flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 sm:gap-3">
                <div class="text-[11px] sm:text-xs text-slate-300 flex items-center gap-1.5">
                  <span class="text-emerald-400">✓</span> Activación garantizada en 10m-60m con PIN propio
                </div>

                <div class="grid grid-cols-2 gap-2 w-full sm:w-auto">
                  <button 
                    type="button"
                    @click="shareCalculatedBudgetWhatsApp"
                    class="px-3 sm:px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-sky-300 border border-sky-500/30 font-bold text-[11px] sm:text-xs transition-all flex items-center justify-center gap-1.5 cursor-pointer shadow-sm active:scale-95 touch-manipulation"
                  >
                    <span>💬</span>
                    <span class="truncate">WhatsApp</span>
                  </button>

                  <button 
                    type="button"
                    @click="addCalculatedComboToCart"
                    class="px-3 sm:px-5 py-2.5 rounded-xl bg-gradient-to-r from-sky-400 to-blue-500 hover:from-sky-300 hover:to-blue-400 text-slate-950 font-black text-[11px] sm:text-xs transition-all flex items-center justify-center gap-1.5 cursor-pointer shadow-lg shadow-sky-500/20 active:scale-95 touch-manipulation"
                  >
                    <span>🛒</span>
                    <span class="truncate">Armar en Carrito</span>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- ============================================== -->
          <!-- TAB 2: RASTREADOR DE PEDIDOS & PIN -->
          <!-- ============================================== -->
          <div v-if="activeTab === 'tracker'" class="space-y-4 sm:space-y-6">
            <div class="bg-gradient-to-r from-slate-900 to-emerald-950/30 border border-emerald-500/25 rounded-xl sm:rounded-2xl p-3.5 sm:p-5">
              <h4 class="text-sm sm:text-lg font-black text-white flex items-center gap-2">
                <span>🔍</span> Consulta el Estado de tu Entrega & Activación
              </h4>
              <p class="text-[11px] sm:text-xs text-slate-300 mt-1 max-w-xl">
                Ingresa el número de pedido (ej: <code>#1024</code>) o tu número de WhatsApp para ver el estado en tiempo real.
              </p>

              <!-- Search Bar -->
              <div class="mt-3.5 sm:mt-4 flex flex-col sm:flex-row items-stretch gap-2">
                <div class="relative flex-1">
                  <input 
                    v-model="searchOrderQuery"
                    type="text" 
                    placeholder="Ej: SP-1024 o tu WhatsApp..."
                    class="w-full bg-slate-950/80 border border-white/10 rounded-xl px-3.5 py-2.5 text-white text-xs sm:text-sm focus:outline-none focus:border-emerald-400 transition"
                    @keyup.enter="trackOrder"
                  />
                  <button 
                    v-if="searchOrderQuery" 
                    @click="searchOrderQuery = ''; searchPerformed = false; orderSearchResult = null"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white text-xs cursor-pointer p-1"
                  >
                    ✕
                  </button>
                </div>

                <button 
                  type="button"
                  @click="trackOrder"
                  class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-slate-950 font-black text-xs sm:text-sm transition-all flex items-center justify-center gap-2 shadow-lg shadow-emerald-500/20 cursor-pointer active:scale-95 shrink-0 touch-manipulation"
                >
                  <span>🔍</span>
                  <span>Rastrear Pedido</span>
                </button>
              </div>
            </div>

            <!-- RESULTADO DE LA BÚSQUEDA -->
            <div v-if="orderSearchResult && !orderSearchResult.notFound" class="rounded-2xl sm:rounded-3xl bg-slate-900/90 border border-emerald-500/40 p-3.5 sm:p-5 space-y-3 sm:space-y-4 shadow-xl">
              <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 pb-3 border-b border-white/[0.08]">
                <div class="flex items-center gap-2.5">
                  <span class="text-2xl">📦</span>
                  <div>
                    <h5 class="text-xs sm:text-sm font-black text-white">{{ orderSearchResult.title }}</h5>
                    <span class="text-[10px] sm:text-[11px] font-mono text-slate-400">Orden ID: #{{ orderSearchResult.id }} · Fecha: {{ orderSearchResult.date }}</span>
                  </div>
                </div>

                <span class="text-[11px] sm:text-xs font-bold px-3 py-1 rounded-full border" :class="getStatusBadge(orderSearchResult.status).class">
                  {{ getStatusBadge(orderSearchResult.status).text }}
                </span>
              </div>

              <!-- Detalle de Credenciales / Entrega -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3 pt-1">
                <div class="p-3 rounded-xl sm:rounded-2xl bg-slate-950/70 border border-white/[0.06] space-y-1">
                  <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Perfil Asignado</span>
                  <div class="text-xs font-bold text-sky-300 font-mono">{{ orderSearchResult.profileName }}</div>
                  <span class="text-[10px] text-slate-500">Exclusivo para tu uso personal</span>
                </div>

                <div class="p-3 rounded-xl sm:rounded-2xl bg-slate-950/70 border border-white/[0.06] space-y-1">
                  <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">PIN de Seguridad</span>
                  <div class="flex items-center gap-2">
                    <span class="text-sm font-black text-amber-300 font-mono">
                      {{ showPinCode ? orderSearchResult.pin : '••••' }}
                    </span>
                    <button 
                      type="button" 
                      @click="showPinCode = !showPinCode" 
                      class="text-[10px] font-bold px-2 py-0.5 rounded bg-slate-800 text-slate-300 hover:text-white transition cursor-pointer touch-manipulation"
                    >
                      {{ showPinCode ? 'Ocultar' : 'Ver PIN' }}
                    </button>
                    <button 
                      v-if="showPinCode"
                      type="button" 
                      @click="copyText(orderSearchResult.pin, 'PIN copiado')" 
                      class="text-[10px] font-bold px-2 py-0.5 rounded bg-sky-500/20 text-sky-300 hover:bg-sky-500/30 transition cursor-pointer touch-manipulation"
                    >
                      Copiar
                    </button>
                  </div>
                  <span class="text-[10px] text-slate-500">Mantén tu PIN resguardado</span>
                </div>
              </div>

              <div class="pt-2 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 sm:gap-3">
                <span class="text-[10px] sm:text-[11px] text-slate-400 flex items-center gap-1.5">
                  <span class="text-emerald-400">🛡️</span> Garantía 100% activa durante los 30 días de servicio
                </span>

                <button 
                  type="button" 
                  @click="consultWhatsAppOrder(orderSearchResult.id)" 
                  class="w-full sm:w-auto px-4 py-2 rounded-xl bg-emerald-500/15 hover:bg-emerald-500/25 border border-emerald-500/30 text-emerald-300 font-bold text-xs flex items-center justify-center gap-1.5 transition cursor-pointer touch-manipulation"
                >
                  <span>💬</span>
                  <span>Contactar Operador de Turno</span>
                </button>
              </div>
            </div>

            <!-- RESULTADO: NO ENCONTRADO LOCALMENTE / CONSULTA EN VIVO -->
            <div v-else-if="orderSearchResult && orderSearchResult.notFound" class="rounded-2xl sm:rounded-3xl bg-slate-900/60 border border-white/10 p-4 sm:p-5 text-center space-y-2.5 sm:space-y-3">
              <span class="text-3xl">📡</span>
              <h5 class="text-xs sm:text-sm font-bold text-white">¿No encuentras tu orden en este navegador?</h5>
              <p class="text-[11px] sm:text-xs text-slate-300 max-w-md mx-auto">
                Si realizaste la compra recientemente desde otro dispositivo o directamente por WhatsApp, nuestro equipo de soporte puede verificarla de inmediato con tu comprobante.
              </p>
              <div class="pt-1 sm:pt-2">
                <button 
                  type="button"
                  @click="consultWhatsAppOrder(searchOrderQuery)"
                  class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-400 hover:to-green-500 text-slate-950 font-black text-xs transition-all inline-flex items-center justify-center gap-2 shadow-lg cursor-pointer touch-manipulation active:scale-95"
                >
                  <span>💬</span>
                  <span>Consultar Estado por WhatsApp</span>
                </button>
              </div>
            </div>

            <!-- METRICAS DE CONFIANZA DEL SISTEMA DE ENTREGAS -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-3 pt-1 sm:pt-2">
              <div class="p-2.5 sm:p-3 rounded-xl sm:rounded-2xl bg-slate-900/60 border border-white/[0.06] text-center">
                <span class="text-base sm:text-lg">⚡</span>
                <div class="text-[11px] sm:text-xs font-black text-white mt-0.5">10 a 60 min</div>
                <span class="text-[9px] sm:text-[10px] text-slate-400">Tiempo Entrega</span>
              </div>
              <div class="p-2.5 sm:p-3 rounded-xl sm:rounded-2xl bg-slate-900/60 border border-white/[0.06] text-center">
                <span class="text-base sm:text-lg">👨‍💻</span>
                <div class="text-[11px] sm:text-xs font-black text-emerald-300 mt-0.5">Activos</div>
                <span class="text-[9px] sm:text-[10px] text-slate-400">Operadores Turno</span>
              </div>
              <div class="p-2.5 sm:p-3 rounded-xl sm:rounded-2xl bg-slate-900/60 border border-white/[0.06] text-center">
                <span class="text-base sm:text-lg">🛡️</span>
                <div class="text-[11px] sm:text-xs font-black text-amber-300 mt-0.5">SLA 30 Días</div>
                <span class="text-[9px] sm:text-[10px] text-slate-400">Garantía Escrita</span>
              </div>
              <div class="p-2.5 sm:p-3 rounded-xl sm:rounded-2xl bg-slate-900/60 border border-white/[0.06] text-center">
                <span class="text-base sm:text-lg">🔒</span>
                <div class="text-[11px] sm:text-xs font-black text-sky-300 mt-0.5">100% Legal</div>
                <span class="text-[9px] sm:text-[10px] text-slate-400">PIN y Perfil Propio</span>
              </div>
            </div>
          </div>

          <!-- ============================================== -->
          <!-- TAB 3: ASISTENTE GAMER DE LICENCIAS -->
          <!-- ============================================== -->
          <div v-if="activeTab === 'gamer'" class="space-y-4 sm:space-y-6">
            <div class="bg-gradient-to-r from-indigo-950/40 via-slate-900 to-purple-950/40 border border-indigo-500/30 rounded-xl sm:rounded-2xl p-3.5 sm:p-5">
              <h4 class="text-sm sm:text-lg font-black text-white flex items-center gap-2">
                <span>🎮</span> Asistente Interactivo de Licencias Gamer (PS5 / PS4)
              </h4>
              <p class="text-[11px] sm:text-xs text-slate-300 mt-1">
                Responde 3 preguntas rápidas para saber si te conviene adquirir una <strong>Cuenta Primaria</strong> o una <strong>Cuenta Secundaria</strong>.
              </p>
            </div>

            <!-- Wizard Questions -->
            <div class="space-y-3 sm:space-y-4">
              <!-- Pregunta 1: Consola -->
              <div class="p-3 sm:p-4 rounded-xl sm:rounded-2xl bg-slate-900/80 border border-white/[0.06] space-y-2">
                <span class="text-xs font-black text-slate-200">1. ¿En qué consola vas a jugar?</span>
                <div class="grid grid-cols-3 gap-1.5 sm:gap-2">
                  <button 
                    type="button" 
                    @click="gamerConsole = 'PS5'"
                    :class="[
                      'py-2 px-1 rounded-xl text-[11px] sm:text-xs font-bold border transition cursor-pointer text-center truncate touch-manipulation',
                      gamerConsole === 'PS5' ? 'bg-sky-500/20 text-sky-300 border-sky-400 font-black' : 'bg-slate-950/60 text-slate-400 border-white/[0.06]'
                    ]"
                  >
                    ⚡ PS5
                  </button>
                  <button 
                    type="button" 
                    @click="gamerConsole = 'PS4'"
                    :class="[
                      'py-2 px-1 rounded-xl text-[11px] sm:text-xs font-bold border transition cursor-pointer text-center truncate touch-manipulation',
                      gamerConsole === 'PS4' ? 'bg-indigo-500/20 text-indigo-300 border-indigo-400 font-black' : 'bg-slate-950/60 text-slate-400 border-white/[0.06]'
                    ]"
                  >
                    🎮 PS4
                  </button>
                  <button 
                    type="button" 
                    @click="gamerConsole = 'BOTH'"
                    :class="[
                      'py-2 px-1 rounded-xl text-[11px] sm:text-xs font-bold border transition cursor-pointer text-center truncate touch-manipulation',
                      gamerConsole === 'BOTH' ? 'bg-purple-500/20 text-purple-300 border-purple-400 font-black' : 'bg-slate-950/60 text-slate-400 border-white/[0.06]'
                    ]"
                  >
                    🔄 Ambas
                  </button>
                </div>
              </div>

              <!-- Pregunta 2: Usuario y Trofeos -->
              <div class="p-3 sm:p-4 rounded-xl sm:rounded-2xl bg-slate-900/80 border border-white/[0.06] space-y-2">
                <span class="text-xs font-black text-slate-200">2. ¿Querés jugar desde tu usuario de siempre y ganar trofeos?</span>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                  <button 
                    type="button" 
                    @click="gamerUserPref = 'personal'"
                    :class="[
                      'p-2.5 sm:p-3 rounded-xl text-left border transition cursor-pointer flex items-center justify-between touch-manipulation',
                      gamerUserPref === 'personal' ? 'bg-amber-500/20 text-amber-300 border-amber-400/50' : 'bg-slate-950/60 text-slate-400 border-white/[0.06]'
                    ]"
                  >
                    <div>
                      <div class="text-xs font-bold text-white">⭐ Sí, en mi usuario personal</div>
                      <div class="text-[10px] text-slate-400 mt-0.5">Quiero mis trofeos, amigos y progreso en mi ID</div>
                    </div>
                    <span v-if="gamerUserPref === 'personal'" class="text-amber-400 text-sm font-bold shrink-0 ml-2">✓</span>
                  </button>

                  <button 
                    type="button" 
                    @click="gamerUserPref = 'any'"
                    :class="[
                      'p-2.5 sm:p-3 rounded-xl text-left border transition cursor-pointer flex items-center justify-between touch-manipulation',
                      gamerUserPref === 'any' ? 'bg-indigo-500/20 text-indigo-300 border-indigo-400/50' : 'bg-slate-950/60 text-slate-400 border-white/[0.06]'
                    ]"
                  >
                    <div>
                      <div class="text-xs font-bold text-white">⚡ Busco el menor precio</div>
                      <div class="text-[10px] text-slate-400 mt-0.5">Puedo jugar en el perfil asignado para ahorrar</div>
                    </div>
                    <span v-if="gamerUserPref === 'any'" class="text-indigo-400 text-sm font-bold shrink-0 ml-2">✓</span>
                  </button>
                </div>
              </div>

              <!-- Pregunta 3: Conexión a Internet -->
              <div class="p-3 sm:p-4 rounded-xl sm:rounded-2xl bg-slate-900/80 border border-white/[0.06] space-y-2">
                <span class="text-xs font-black text-slate-200">3. ¿Tienes conexión continua a internet?</span>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                  <button 
                    type="button" 
                    @click="gamerConnection = 'always'"
                    :class="[
                      'py-2 px-3 rounded-xl text-xs font-bold border transition cursor-pointer text-left touch-manipulation',
                      gamerConnection === 'always' ? 'bg-sky-500/20 text-sky-300 border-sky-400' : 'bg-slate-950/60 text-slate-400 border-white/[0.06]'
                    ]"
                  >
                    🌐 Sí, siempre tengo Wi-Fi / cable
                  </button>

                  <button 
                    type="button" 
                    @click="gamerConnection = 'offline'"
                    :class="[
                      'py-2 px-3 rounded-xl text-xs font-bold border transition cursor-pointer text-left touch-manipulation',
                      gamerConnection === 'offline' ? 'bg-sky-500/20 text-sky-300 border-sky-400' : 'bg-slate-950/60 text-slate-400 border-white/[0.06]'
                    ]"
                  >
                    ✈️ A veces juego sin internet (Offline)
                  </button>
                </div>
              </div>
            </div>

            <!-- RESULTADO DEL DIAGNÓSTICO -->
            <div class="rounded-2xl sm:rounded-3xl bg-slate-950/90 border border-indigo-500/40 p-3.5 sm:p-5 space-y-3 sm:space-y-4 shadow-2xl relative overflow-hidden">
              <div class="flex items-center justify-between">
                <span class="text-xs font-black uppercase tracking-wider px-2.5 py-0.5 rounded-full border" :class="gamerRecommendation.badgeClass">
                  {{ gamerRecommendation.badge }}
                </span>
                <span class="text-xs font-mono font-bold text-slate-400">{{ gamerConsole }}</span>
              </div>

              <div>
                <h5 class="text-sm sm:text-base font-black text-white">{{ gamerRecommendation.summary }}</h5>
                <p class="text-[11px] sm:text-xs text-slate-300 mt-1">{{ gamerRecommendation.idealFor }}</p>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1">
                <div 
                  v-for="(ben, idx) in gamerRecommendation.benefits" 
                  :key="idx" 
                  class="flex items-center gap-2 text-[11px] sm:text-xs text-slate-200 bg-slate-900/60 p-2 sm:p-2.5 rounded-xl border border-white/[0.04]"
                >
                  <span class="text-emerald-400 font-bold shrink-0">✓</span>
                  <span>{{ ben }}</span>
                </div>
              </div>

              <div class="pt-2 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 border-t border-white/[0.08]">
                <div class="text-xs font-bold text-amber-300">
                  💰 {{ gamerRecommendation.saving }}
                </div>

                <div class="flex items-center gap-2 w-full sm:w-auto">
                  <button 
                    type="button" 
                    @click="goToGamesCatalog(gamerRecommendation.type)"
                    class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-400 hover:to-purple-500 text-white font-black text-xs transition shadow-lg shadow-indigo-500/20 cursor-pointer active:scale-95 touch-manipulation text-center"
                  >
                    🎮 Ver Catálogo de Videojuegos →
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- ============================================== -->
          <!-- TAB 4: VALIDADOR DE GARANTÍA & SLA -->
          <!-- ============================================== -->
          <div v-if="activeTab === 'warranty'" class="space-y-4 sm:space-y-6">
            <div class="bg-gradient-to-r from-amber-950/40 via-slate-900 to-orange-950/40 border border-amber-500/30 rounded-xl sm:rounded-2xl p-3.5 sm:p-5">
              <h4 class="text-sm sm:text-lg font-black text-white flex items-center gap-2">
                <span>🛡️</span> Pilares de Garantía Oficial StreamePro
              </h4>
              <p class="text-[11px] sm:text-xs text-slate-300 mt-1">
                Comercializamos únicamente perfiles originales, legales y con respaldo post-venta garantizado por escrito.
              </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3.5">
              <div 
                v-for="(p, idx) in guaranteePillars"
                :key="idx"
                class="p-3 sm:p-4 rounded-xl sm:rounded-2xl bg-slate-900/80 border border-white/[0.06] space-y-1.5 sm:space-y-2 hover:border-amber-400/30 transition-all"
              >
                <div class="flex items-center gap-2.5">
                  <span class="text-xl sm:text-2xl">{{ p.icon }}</span>
                  <h5 class="text-xs sm:text-sm font-black text-white">{{ p.title }}</h5>
                </div>
                <p class="text-[11px] sm:text-xs text-slate-300 leading-relaxed">{{ p.desc }}</p>
              </div>
            </div>

            <!-- Banner de Contacto Garantía Directa -->
            <div class="rounded-xl sm:rounded-2xl bg-slate-950/80 border border-white/10 p-3.5 sm:p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
              <div>
                <div class="text-xs font-bold text-white flex items-center gap-1.5">
                  <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                  Canal de Garantía & Soporte Técnico 24/7
                </div>
                <p class="text-[10px] sm:text-[11px] text-slate-400 mt-0.5">
                  Si surge cualquier duda con tu suscripción o juego, nuestro equipo responde con máxima prioridad.
                </p>
              </div>

              <a 
                href="https://wa.me/5491136000000?text=Hola%20StreamePro,%20tengo%20una%20consulta%20sobre%20mi%20garant%C3%ADa"
                target="_blank"
                rel="noopener noreferrer"
                class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-emerald-500/20 hover:bg-emerald-500/30 border border-emerald-500/40 text-emerald-300 font-black text-xs flex items-center justify-center gap-2 transition cursor-pointer shrink-0 touch-manipulation"
              >
                <span>💬</span>
                <span>Hablar con Soporte</span>
              </a>
            </div>
          </div>

        </div>

        <!-- FOOTER DEL MODAL -->
        <div class="p-3 sm:p-4 border-t border-white/[0.08] bg-slate-950/80 flex items-center justify-between text-xs text-slate-400 shrink-0">
          <div class="flex items-center gap-1.5 sm:gap-2">
            <span class="text-emerald-400">✓</span>
            <span class="font-mono text-[10px] sm:text-[11px]">StreamePro Enterprise v2.4</span>
          </div>

          <button 
            type="button"
            @click="closeModal"
            class="px-3.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white font-bold text-xs transition cursor-pointer active:scale-95 touch-manipulation"
          >
            Cerrar
          </button>
        </div>

      </div>
    </div>
  </Transition>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.25s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>

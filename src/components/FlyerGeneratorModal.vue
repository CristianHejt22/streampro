<script setup>
import { ref, computed, watch } from 'vue'
import html2canvas from 'html2canvas'
import { useAdminStore } from '../store/admin'
import { useToastStore } from '../store/toast'

const props = defineProps({
  show: { type: Boolean, default: false },
  defaultStoreName: { type: String, default: 'StreamPro Oficial' },
  defaultPhone: { type: String, default: '+54 9 11 0000-0000' }
})

const emit = defineEmits(['close'])

const adminStore = useAdminStore()
const toast = useToastStore()

// Tipo de producto a promocionar: 'streaming' | 'physical'
const flyerProductType = ref('streaming')

// Streaming selection
const selectedPlatform = ref('Netflix')
const selectedPlanKey = ref('1_perfil')

// Physical product selection
const selectedPhysicalId = ref('')
const physicalSearch = ref('')

// Personalización del flyer
const storeName = ref(props.defaultStoreName)
const contactPhone = ref(props.defaultPhone)
const headlineBadge = ref('🔥 ¡OFERTA EXCLUSIVA!')
const customPrice = ref(null)
const customTitle = ref('')
const customSubtitle = ref('')
const flyerTheme = ref('cinema') // 'cinema' (dark red/amber), 'cyber' (purple/cyan), 'emerald' (green/gold), 'blue' (navy/sky)
const isExporting = ref(false)
const flyerPreviewRef = ref(null)

// Badges sugeridos
const suggestedBadges = [
  '🔥 ¡OFERTA EXCLUSIVA!',
  '⚡ ENTREGA INMEDIATA',
  '🍿 CINE EN CASA',
  '🚀 100% GARANTIZADO',
  '💎 CALIDAD ULTRA HD 4K',
  '📦 ENVÍO A TODO EL PAÍS'
]

// Bullet points destacados
const bullet1 = ref('Calidad Ultra HD 4K Sin Caídas')
const bullet2 = ref('Perfil Privado con PIN de Seguridad')
const bullet3 = ref('Soporte & Garantía Directa 24/7')

watch(() => props.defaultStoreName, (val) => {
  if (val) storeName.value = val
}, { immediate: true })

watch(() => props.defaultPhone, (val) => {
  if (val) contactPhone.value = val
}, { immediate: true })

// Plataforma actual y sus planes
const currentPlatformData = computed(() => {
  return adminStore.pricingCatalog[selectedPlatform.value] || { name: selectedPlatform.value, plans: {} }
})

const currentPlanData = computed(() => {
  return currentPlatformData.value.plans?.[selectedPlanKey.value] || { label: '1 Perfil', clientPrice: 5000 }
})

// Producto físico seleccionado
const filteredPhysicalProducts = computed(() => {
  if (!physicalSearch.value.trim()) return adminStore.physicalProducts.slice(0, 15)
  const q = physicalSearch.value.toLowerCase()
  return adminStore.physicalProducts.filter(p => p.name.toLowerCase().includes(q)).slice(0, 15)
})

const selectedPhysicalProduct = computed(() => {
  if (!selectedPhysicalId.value) return adminStore.physicalProducts[0] || null
  return adminStore.physicalProducts.find(p => String(p.id) === String(selectedPhysicalId.value)) || adminStore.physicalProducts[0]
})

// Inicializar selección de plan al cambiar plataforma
watch(selectedPlatform, (newPlat) => {
  const plans = adminStore.pricingCatalog[newPlat]?.plans || {}
  const keys = Object.keys(plans)
  if (keys.length > 0) {
    selectedPlanKey.value = keys[0]
  }
  // Personalizar bullets por defecto según plataforma
  if (newPlat === 'Spotify') {
    bullet1.value = 'Música Sin Anuncios en Alta Fidelidad'
    bullet2.value = 'Descarga tus canciones sin conexión'
    bullet3.value = 'Compatible con Celular, PC y TV'
  } else if (newPlat === 'IPTV') {
    bullet1.value = '+1.500 Canales en Vivo Fútbol Libre'
    bullet2.value = 'Series, Películas y Estrenos 2026'
    bullet3.value = 'Compatible con Smart TV, TV Box y Celular'
  } else {
    bullet1.value = 'Calidad Ultra HD 4K Sin Caídas'
    bullet2.value = 'Perfil Privado con PIN de Seguridad'
    bullet3.value = 'Soporte & Garantía Directa 24/7'
  }
})

// Precio final a mostrar
const displayPrice = computed(() => {
  if (customPrice.value !== null && customPrice.value !== '') {
    return Number(customPrice.value).toLocaleString()
  }
  if (flyerProductType.value === 'streaming') {
    return (currentPlanData.value.clientPrice || 0).toLocaleString()
  } else {
    return (selectedPhysicalProduct.value?.priceNumeric || 0).toLocaleString()
  }
})

// Título principal a mostrar
const displayTitle = computed(() => {
  if (customTitle.value.trim()) return customTitle.value
  if (flyerProductType.value === 'streaming') {
    return currentPlatformData.value.name || selectedPlatform.value
  } else {
    return selectedPhysicalProduct.value?.name || 'Producto Tecnológico'
  }
})

// Subtítulo
const displaySubtitle = computed(() => {
  if (customSubtitle.value.trim()) return customSubtitle.value
  if (flyerProductType.value === 'streaming') {
    return currentPlanData.value.label || 'Suscripción Oficial'
  } else {
    return 'Tecnología & Accesorios de Alta Calidad'
  }
})

// Imagen de fondo o icono
const platformLogos = {
  Netflix: '🍿 Netflix',
  'Disney+': '✨ Disney+',
  Max: '🎬 Max Platino',
  IPTV: '📺 IPTV MagisTV',
  Spotify: '🎵 Spotify Premium',
  'Prime Video': '📦 Prime Video'
}

// Descargar imagen con html2canvas
const downloadFlyerImage = async () => {
  if (!flyerPreviewRef.value) return
  isExporting.value = true

  try {
    const canvas = await html2canvas(flyerPreviewRef.value, {
      scale: 2, // Alta definición para Stories
      useCORS: true,
      allowTaint: true,
      backgroundColor: null
    })

    const dataUrl = canvas.toDataURL('image/png')
    const link = document.createElement('a')
    const safeName = displayTitle.value.toLowerCase().replace(/[^a-z0-9]/g, '_')
    link.download = `flyer_${safeName}_${Date.now()}.png`
    link.href = dataUrl
    link.click()

    toast.add('¡Flyer descargado en alta calidad! Listo para tu Estado de WhatsApp o Instagram.', 'success')
  } catch (err) {
    console.error('Error generando flyer:', err)
    toast.add('Error al generar la imagen. Inténtalo de nuevo.', 'error')
  } finally {
    isExporting.value = false
  }
}

// Copiar texto para publicar en redes
const copySocialCopy = () => {
  const text = 
`🔥 *${headlineBadge.value}* 🔥\n\n` +
`🎬 *${displayTitle.value}*\n` +
`📌 *Plan:* ${displaySubtitle.value}\n` +
`💰 *PRECIO EXCLUSIVO:* $${displayPrice.value} ARS\n\n` +
`✅ ${bullet1.value}\n` +
`✅ ${bullet2.value}\n` +
`✅ ${bullet3.value}\n\n` +
`⚡ *¡Entrega inmediata! Cupos limitados.*\n` +
`📲 *Pide el tuyo por WhatsApp:* ${contactPhone.value}\n` +
`🏪 *${storeName.value}*`

  navigator.clipboard.writeText(text).then(() => {
    toast.add('¡Texto de publicación copiado! Pégalo en tu Estado o Historia.', 'success')
  })
}

// Abrir WhatsApp con el copy
const shareDirectWhatsApp = () => {
  const cleanPhone = contactPhone.value.replace(/[^0-9]/g, '')
  const text = 
`¡Hola! Quiero aprovechar la oferta de *${displayTitle.value}* (${displaySubtitle.value}) por $${displayPrice.value} ARS. ¿Tienen disponible para entrega inmediata?`
  window.open(`https://wa.me/${cleanPhone}?text=${encodeURIComponent(text)}`, '_blank')
}
</script>

<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 bg-black/85 backdrop-blur-md overflow-y-auto animate-in fade-in duration-200">
    <div class="bg-slate-900 border border-amber-500/30 w-full max-w-5xl rounded-3xl shadow-2xl overflow-hidden my-auto flex flex-col max-h-[94vh]">
      <!-- Encabezado Modal -->
      <div class="flex justify-between items-center px-6 py-4 border-b border-slate-800 bg-slate-950">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-amber-500 to-orange-500 flex items-center justify-center text-slate-950 font-black text-lg shadow">
            🎨
          </div>
          <div>
            <h3 class="text-lg font-black text-white flex items-center gap-2">
              Generador de Flyers & Banners de Oferta
              <span class="text-[10px] font-mono font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30 px-2 py-0.5 rounded">
                ESTADOS & STORIES (9:16)
              </span>
            </h3>
            <p class="text-xs text-slate-400">Crea imágenes publicitarias profesionales para WhatsApp e Instagram en 1 segundo y multiplica tus ventas.</p>
          </div>
        </div>
        <button @click="emit('close')" class="text-slate-400 hover:text-white p-2 rounded-xl hover:bg-slate-800 transition-colors">
          ✕
        </button>
      </div>

      <!-- Cuerpo: Grid con Controles a la Izquierda y Preview a la Derecha -->
      <div class="p-6 grid grid-cols-1 lg:grid-cols-12 gap-6 overflow-y-auto flex-1">
        
        <!-- COLUMNA IZQUIERDA: CONTROLES DE PERSONALIZACIÓN -->
        <div class="lg:col-span-7 space-y-4 text-xs">
          
          <!-- Selector de Tipo de Producto -->
          <div class="flex gap-2 p-1 bg-slate-950 rounded-xl border border-slate-800">
            <button 
              @click="flyerProductType = 'streaming'"
              :class="['flex-1 py-2 rounded-lg font-black transition-all flex items-center justify-center gap-1.5', flyerProductType === 'streaming' ? 'bg-amber-400 text-slate-950 shadow' : 'text-slate-400 hover:text-white']"
            >
              <span>🍿</span>
              <span>Streaming & Cuentas</span>
            </button>
            <button 
              @click="flyerProductType = 'physical'"
              :class="['flex-1 py-2 rounded-lg font-black transition-all flex items-center justify-center gap-1.5', flyerProductType === 'physical' ? 'bg-amber-400 text-slate-950 shadow' : 'text-slate-400 hover:text-white']"
            >
              <span>📦</span>
              <span>Tecnología & Físicos</span>
            </button>
          </div>

          <!-- Si es Streaming -->
          <div v-if="flyerProductType === 'streaming'" class="grid grid-cols-1 sm:grid-cols-2 gap-3 bg-[#020617] p-3.5 rounded-2xl border border-slate-800">
            <div>
              <label class="block font-bold uppercase text-slate-400 mb-1">Plataforma</label>
              <select v-model="selectedPlatform" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-white font-bold outline-none focus:border-amber-400">
                <option v-for="(plat, key) in adminStore.pricingCatalog" :key="key" :value="key">
                  {{ plat.name || key }}
                </option>
              </select>
            </div>
            <div>
              <label class="block font-bold uppercase text-slate-400 mb-1">Plan / Pantallas</label>
              <select v-model="selectedPlanKey" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-white font-bold outline-none focus:border-amber-400">
                <option v-for="(pl, pKey) in currentPlatformData.plans" :key="pKey" :value="pKey">
                  {{ pl.label }} (${{ pl.clientPrice?.toLocaleString() }})
                </option>
              </select>
            </div>
          </div>

          <!-- Si es Físico -->
          <div v-else class="space-y-2 bg-[#020617] p-3.5 rounded-2xl border border-slate-800">
            <label class="block font-bold uppercase text-slate-400 mb-1">Buscar y Seleccionar Producto</label>
            <input 
              v-model="physicalSearch" 
              type="text" 
              placeholder="Buscar por nombre (ej: cortadora, smartwatch, trípode)..."
              class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2 text-white outline-none focus:border-amber-400 mb-2"
            >
            <select v-model="selectedPhysicalId" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-white font-bold outline-none focus:border-amber-400">
              <option v-for="prod in filteredPhysicalProducts" :key="prod.id" :value="prod.id">
                {{ prod.name }} ({{ prod.price }})
              </option>
            </select>
          </div>

          <!-- Personalización de Textos -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block font-bold uppercase text-slate-400 mb-1">Badge Superior de Impacto</label>
              <input v-model="headlineBadge" list="badgesList" type="text" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2 text-amber-300 font-bold outline-none">
              <datalist id="badgesList">
                <option v-for="b in suggestedBadges" :key="b" :value="b" />
              </datalist>
            </div>
            <div>
              <label class="block font-bold uppercase text-slate-400 mb-1">Precio a Mostrar (ARS)</label>
              <input v-model="customPrice" type="number" :placeholder="`Por defecto: $${displayPrice}`" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2 text-emerald-400 font-mono font-bold outline-none">
            </div>
          </div>

          <!-- Datos de la Tienda y Contacto -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block font-bold uppercase text-slate-400 mb-1">Nombre de tu Tienda</label>
              <input v-model="storeName" type="text" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2 text-white outline-none">
            </div>
            <div>
              <label class="block font-bold uppercase text-slate-400 mb-1">WhatsApp de Pedidos</label>
              <input v-model="contactPhone" type="text" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2 text-white outline-none font-mono">
            </div>
          </div>

          <!-- Puntos Clave / Beneficios -->
          <div class="space-y-1.5 bg-[#020617] p-3 rounded-2xl border border-slate-800">
            <span class="block font-bold uppercase text-slate-400 text-[11px] mb-1">Puntos Clave / Beneficios de la Oferta</span>
            <input v-model="bullet1" type="text" class="w-full bg-slate-950 border border-slate-800 rounded-lg p-1.5 text-slate-200 outline-none text-xs">
            <input v-model="bullet2" type="text" class="w-full bg-slate-950 border border-slate-800 rounded-lg p-1.5 text-slate-200 outline-none text-xs">
            <input v-model="bullet3" type="text" class="w-full bg-slate-950 border border-slate-800 rounded-lg p-1.5 text-slate-200 outline-none text-xs">
          </div>

          <!-- Selector de Estilo / Tema Visual -->
          <div>
            <label class="block font-bold uppercase text-slate-400 mb-1.5">Estilo Visual</label>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
              <button 
                @click="flyerTheme = 'cinema'"
                :class="['p-2 rounded-xl border text-center font-bold text-[11px] transition-all', flyerTheme === 'cinema' ? 'bg-red-950/40 border-red-500 text-red-300' : 'bg-slate-950 border-slate-800 text-slate-400']"
              >
                🎬 Cinema Dark
              </button>
              <button 
                @click="flyerTheme = 'cyber'"
                :class="['p-2 rounded-xl border text-center font-bold text-[11px] transition-all', flyerTheme === 'cyber' ? 'bg-purple-950/40 border-purple-500 text-purple-300' : 'bg-slate-950 border-slate-800 text-slate-400']"
              >
                ⚡ Cyber Neon
              </button>
              <button 
                @click="flyerTheme = 'emerald'"
                :class="['p-2 rounded-xl border text-center font-bold text-[11px] transition-all', flyerTheme === 'emerald' ? 'bg-emerald-950/40 border-emerald-500 text-emerald-300' : 'bg-slate-950 border-slate-800 text-slate-400']"
              >
                💎 Emerald Deal
              </button>
              <button 
                @click="flyerTheme = 'blue'"
                :class="['p-2 rounded-xl border text-center font-bold text-[11px] transition-all', flyerTheme === 'blue' ? 'bg-sky-950/40 border-sky-500 text-sky-300' : 'bg-slate-950 border-slate-800 text-slate-400']"
              >
                ✨ Deep Blue
              </button>
            </div>
          </div>

          <!-- Botones de Acción -->
          <div class="flex flex-col sm:flex-row gap-2.5 pt-2">
            <button 
              @click="downloadFlyerImage" 
              :disabled="isExporting"
              class="flex-1 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-400 hover:to-orange-400 text-slate-950 font-black py-3 rounded-xl shadow-lg transition-all flex items-center justify-center gap-2 text-xs cursor-pointer disabled:opacity-50"
            >
              <span>{{ isExporting ? '⏳ Generando Imagen...' : '⬇️ Descargar Flyer (PNG 9:16)' }}</span>
            </button>
            <button 
              @click="copySocialCopy" 
              class="bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold px-4 py-3 rounded-xl border border-slate-700 transition-all flex items-center justify-center gap-1.5"
              title="Copiar texto listo para pegar en la descripción de la historia o estado"
            >
              <span>📋 Copiar Texto</span>
            </button>
            <button 
              @click="shareDirectWhatsApp" 
              class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold px-4 py-3 rounded-xl shadow transition-all flex items-center justify-center gap-1.5"
              title="Probar enlace directo de pedido en WhatsApp"
            >
              <span>💬 Test WhatsApp</span>
            </button>
          </div>
        </div>

        <!-- COLUMNA DERECHA: VISTA PREVIA DEL FLYER (FORMATO STORIES 9:16) -->
        <div class="lg:col-span-5 flex flex-col items-center justify-center bg-slate-950/80 p-4 rounded-2xl border border-slate-800">
          <span class="text-[11px] font-mono text-slate-400 mb-2 font-bold uppercase tracking-wider">
            Vista Previa de Estado / Story
          </span>

          <!-- CONTENEDOR FLYER CAPTURABLE (Ratio 9:16) -->
          <div 
            ref="flyerPreviewRef" 
            :class="[
              'w-full max-w-[320px] aspect-[9/16] rounded-3xl p-5 flex flex-col justify-between relative overflow-hidden shadow-2xl border transition-all text-white select-none',
              flyerTheme === 'cinema' ? 'bg-gradient-to-b from-black via-red-950/40 to-slate-950 border-red-500/30' :
              flyerTheme === 'cyber' ? 'bg-gradient-to-b from-slate-950 via-purple-950/50 to-cyan-950/30 border-purple-500/40' :
              flyerTheme === 'emerald' ? 'bg-gradient-to-b from-slate-950 via-emerald-950/40 to-amber-950/30 border-emerald-500/40' :
              'bg-gradient-to-b from-slate-950 via-sky-950/50 to-blue-950/40 border-sky-500/40'
            ]"
          >
            <!-- Efectos de Luces y Gradientes de fondo -->
            <div class="absolute -top-12 -right-12 w-40 h-40 bg-amber-500/15 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-12 -left-12 w-40 h-40 bg-purple-500/15 rounded-full blur-3xl pointer-events-none"></div>

            <!-- PARTE SUPERIOR: Marca de la Tienda & Badge -->
            <div class="relative z-10 text-center space-y-2">
              <div class="flex items-center justify-between border-b border-white/10 pb-2">
                <span class="text-[11px] font-mono font-black uppercase tracking-widest text-amber-300">
                  {{ storeName }}
                </span>
                <span class="text-[9px] font-bold bg-white/10 px-2 py-0.5 rounded-full border border-white/15 text-slate-300">
                  OFICIAL
                </span>
              </div>

              <!-- Badge Dinámico -->
              <div class="inline-block bg-gradient-to-r from-amber-500 to-orange-500 text-slate-950 text-[11px] font-black px-3.5 py-1 rounded-full shadow-lg uppercase tracking-wider">
                {{ headlineBadge }}
              </div>
            </div>

            <!-- PARTE CENTRAL: Imagen / Icono y Titular -->
            <div class="relative z-10 text-center space-y-3 my-auto">
              <!-- Icono / Imagen del Producto -->
              <div class="w-24 h-24 mx-auto rounded-2xl bg-white/5 border border-white/15 flex items-center justify-center p-2 backdrop-blur-md shadow-inner">
                <img 
                  v-if="flyerProductType === 'physical' && selectedPhysicalProduct?.image" 
                  :src="selectedPhysicalProduct.image" 
                  :alt="displayTitle"
                  class="w-full h-full object-contain rounded-xl"
                  crossorigin="anonymous"
                >
                <div v-else class="text-4xl">
                  {{ selectedPlatform === 'Netflix' ? '🍿' : selectedPlatform === 'Disney+' ? '✨' : selectedPlatform === 'Max' ? '🎬' : selectedPlatform === 'IPTV' ? '📺' : selectedPlatform === 'Spotify' ? '🎵' : '⚡' }}
                </div>
              </div>

              <div>
                <h2 class="text-2xl font-black tracking-tight leading-tight drop-shadow-md text-white">
                  {{ displayTitle }}
                </h2>
                <p class="text-xs font-semibold text-amber-300/90 font-mono mt-0.5">
                  {{ displaySubtitle }}
                </p>
              </div>

              <!-- Precio Gigante -->
              <div class="py-2.5 px-4 rounded-2xl bg-black/40 border border-white/10 backdrop-blur-md inline-block shadow-xl">
                <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider block">SOLO POR ESTE MES</span>
                <div class="flex items-baseline justify-center gap-1">
                  <span class="text-sm font-black text-amber-400">$</span>
                  <span class="text-3xl font-black tracking-tight text-white">{{ displayPrice }}</span>
                  <span class="text-xs font-bold text-slate-400 font-mono">ARS</span>
                </div>
              </div>

              <!-- Bullets / Beneficios -->
              <div class="space-y-1 text-left text-[11px] px-2 font-medium text-slate-200">
                <div class="flex items-center gap-1.5">
                  <span class="text-emerald-400 font-bold">✓</span>
                  <span class="truncate">{{ bullet1 }}</span>
                </div>
                <div class="flex items-center gap-1.5">
                  <span class="text-emerald-400 font-bold">✓</span>
                  <span class="truncate">{{ bullet2 }}</span>
                </div>
                <div class="flex items-center gap-1.5">
                  <span class="text-emerald-400 font-bold">✓</span>
                  <span class="truncate">{{ bullet3 }}</span>
                </div>
              </div>
            </div>

            <!-- PARTE INFERIOR: Llamada a la Acción de WhatsApp -->
            <div class="relative z-10 text-center space-y-1.5 pt-2 border-t border-white/10">
              <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">
                ⚡ ¡Pide el tuyo ahora antes de que se agote!
              </span>
              <div class="bg-emerald-500 text-slate-950 font-black py-2 px-3 rounded-xl flex items-center justify-center gap-2 shadow-lg text-xs">
                <span>💬 WhatsApp:</span>
                <span class="font-mono text-sm tracking-tight">{{ contactPhone }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

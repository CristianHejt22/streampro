<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import html2canvas from 'html2canvas'
import { useAdminStore } from '../store/admin'
import { useToastStore } from '../store/toast'

const props = defineProps({
  show: { type: Boolean, default: false },
  defaultStoreName: { type: String, default: 'StreamePro Oficial' },
  initialProduct: { type: Object, default: null },
  resellerRef: { type: String, default: 'DIRECT' },
  websiteDomain: { type: String, default: 'streamepro.me' }
})

const emit = defineEmits(['close'])

const adminStore = useAdminStore()
const toast = useToastStore()

// Modo de flyer: 'individual' | 'combo' | 'physical'
const flyerProductType = ref('individual')

// 1. Servicio Individual
const selectedPlatform = ref('Netflix')
const selectedPlanKey = ref('1_perfil')

// 2. Promoción Combo / Paquete
const selectedPackageId = ref('PKG-101')

// 3. Catálogo Físico
const selectedPhysicalId = ref('')
const physicalSearch = ref('')
const selectedPhysicalCat = ref('All')

// Personalización del flyer (Solo dominio principal)
const storeName = ref(props.defaultStoreName)
const websiteUrl = ref('streamepro.me')
const headlineBadge = ref('🔥 25% OFF - RECOMENDADO')
const customPrice = ref(null)
const customOriginalPrice = ref(null)
const customTitle = ref('')
const customSubtitle = ref('')
const flyerTheme = ref('cinema') // 'cinema' | 'cyber' | 'emerald' | 'blue'
const flyerFormat = ref('story') // 'story' (9:16) | 'feed' (4:5) | 'square' (1:1) | 'banner' (16:9)

const flyerFormatLabels = {
  story: '9:16 Historias / Estados',
  feed: '4:5 Feed Instagram',
  square: '1:1 Post Cuadrado',
  banner: '16:9 Banner Horizontal'
}

const isExporting = ref(false)
const flyerPreviewRef = ref(null)

// Badges sugeridos
const suggestedBadges = [
  '🔥 25% OFF - RECOMENDADO',
  '🎁 COMBO RECOMENDADO',
  '🔥 ¡OFERTA EXCLUSIVA!',
  '⏱️ ENTREGA 10 MIN A 1 HORA',
  '🍿 CINE EN CASA ULTRA HD',
  '🚀 100% GARANTIZADO',
  '💎 CALIDAD ULTRA HD 4K',
  '📦 ENVÍO A TODO EL PAÍS'
]

// Bullet points destacados
const bullet1 = ref('Calidad Ultra HD 4K Sin Caídas')
const bullet2 = ref('Perfil Privado con PIN de Seguridad')
const bullet3 = ref('Soporte & Garantía Directa 24/7')

// Iconos de plataforma
const getPlatformIcon = (platform) => {
  const p = (platform || '').toLowerCase()
  if (p.includes('netflix')) return '🍿'
  if (p.includes('disney')) return '✨'
  if (p.includes('max') || p.includes('hbo')) return '🎬'
  if (p.includes('spotify') || p.includes('music')) return '🎵'
  if (p.includes('iptv') || p.includes('magis') || p.includes('tele')) return '📺'
  if (p.includes('prime') || p.includes('amazon')) return '📦'
  if (p.includes('paramount')) return '⛰️'
  if (p.includes('crunchy')) return '🍥'
  if (p.includes('apple')) return '🍏'
  if (p.includes('youtube')) return '▶️'
  return '⚡'
}

const currentPlatformIcon = computed(() => getPlatformIcon(selectedPlatform.value))

// Solo dominio principal (limpio, sin https:// ni rutas)
const displayDomain = computed(() => {
  let val = (websiteUrl.value || props.websiteDomain || 'streamepro.me').trim()
  val = val.replace(/^https?:\/\//i, '')
  val = val.split('/')[0].split('?')[0].split('#')[0]
  return val || 'streamepro.me'
})

// Watchers de props iniciales
watch(() => props.defaultStoreName, (val) => {
  if (val) storeName.value = val
}, { immediate: true })

watch(() => props.websiteDomain, (val) => {
  if (val) {
    websiteUrl.value = val.replace(/^https?:\/\//i, '').split('/')[0]
  }
}, { immediate: true })

// Helper para convertir imagen a URL con CORS usando proxy seguro wsrv.nl
const getCORSImageUrl = (rawUrl) => {
  if (!rawUrl) return null
  let fullUrl = rawUrl
  if (!fullUrl.startsWith('http')) {
    fullUrl = `https://tua.ar/${rawUrl}`
  }
  return `https://wsrv.nl/?url=${encodeURIComponent(fullUrl)}&w=800&output=jpg`
}

// Plataforma actual y sus planes (Servicio Individual)
const currentPlatformData = computed(() => {
  return adminStore.pricingCatalog[selectedPlatform.value] || { name: selectedPlatform.value, plans: {} }
})

const currentPlanData = computed(() => {
  return currentPlatformData.value.plans?.[selectedPlanKey.value] || { label: '1 Perfil', clientPrice: 5000 }
})

// Combo / Paquete actual
const availablePackages = computed(() => adminStore.packages || [])

const selectedPackage = computed(() => {
  const list = availablePackages.value
  if (!list.length) return null
  return list.find(p => p.id === selectedPackageId.value) || list[0]
})

// Filtrado de productos físicos
const filteredPhysicalProducts = computed(() => {
  return adminStore.physicalProducts.filter(p => {
    const matchesCat = selectedPhysicalCat.value === 'All' || (p.categoryIds && p.categoryIds.includes(selectedPhysicalCat.value))
    const q = physicalSearch.value.toLowerCase().trim()
    const matchesQ = !q || p.name.toLowerCase().includes(q) || String(p.id).includes(q)
    return matchesCat && matchesQ
  })
})

const selectedPhysicalProduct = computed(() => {
  const list = adminStore.physicalProducts || []
  if (list.length === 0) return null
  if (!selectedPhysicalId.value) return list[0]
  return list.find(p => String(p.id) === String(selectedPhysicalId.value)) || list[0]
})

// Imagen con CORS para físicos
const productImageCORS = computed(() => {
  if (flyerProductType.value === 'physical' && selectedPhysicalProduct.value?.image) {
    return getCORSImageUrl(selectedPhysicalProduct.value.image)
  }
  return null
})

// Sincronizar selección cuando cambie el combo
watch(selectedPackageId, (newId) => {
  if (flyerProductType.value === 'combo') {
    const pkg = availablePackages.value.find(p => p.id === newId)
    if (pkg) {
      customTitle.value = pkg.title
      customSubtitle.value = pkg.description || 'Promoción Especial de Combos Streaming'
      headlineBadge.value = pkg.badge || '🔥 25% OFF - RECOMENDADO'
      customPrice.value = pkg.promoPrice || null
      customOriginalPrice.value = pkg.originalPrice || null
      
      if (pkg.services && pkg.services.length >= 2) {
        bullet1.value = `✓ ${pkg.services[0].platform} (${pkg.services[0].planLabel})`
        bullet2.value = `✓ ${pkg.services[1].platform} (${pkg.services[1].planLabel})`
        bullet3.value = pkg.services[2] 
          ? `✓ ${pkg.services[2].platform} (${pkg.services[2].planLabel})`
          : 'Garantía Total 24/7 & Entrega Inmediata'
      }
    }
  }
})

// Sincronizar selección cuando cambie la plataforma individual
watch(selectedPlatform, (newPlat) => {
  if (flyerProductType.value === 'individual') {
    const plans = adminStore.pricingCatalog[newPlat]?.plans || {}
    const keys = Object.keys(plans)
    if (keys.length > 0) {
      selectedPlanKey.value = keys[0]
    }
    headlineBadge.value = '🔥 ¡OFERTA EXCLUSIVA!'
    customTitle.value = ''
    customSubtitle.value = ''
    customPrice.value = null
    customOriginalPrice.value = null

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
  }
})

// Sincronizar selección cuando cambie el producto físico
watch(selectedPhysicalId, (newId) => {
  if (flyerProductType.value === 'physical') {
    const prod = adminStore.physicalProducts.find(p => String(p.id) === String(newId))
    if (prod) {
      customTitle.value = prod.name || ''
      customSubtitle.value = 'Tecnología & Accesorios de Alta Calidad'
      headlineBadge.value = '📦 ENVÍO A TODO EL PAÍS'
      customPrice.value = prod.priceNumeric || null
      customOriginalPrice.value = null
      bullet1.value = 'Excelente Calidad y Diseño Garantizado'
      bullet2.value = 'Envío Rápido y Seguro a Todo el País'
      bullet3.value = 'Compra 100% Segura con Asesoría Oficial'
    }
  }
})

// Alternar entre los 3 modos de flyer
watch(flyerProductType, (newType) => {
  if (newType === 'combo') {
    const pkg = selectedPackage.value
    if (pkg) {
      customTitle.value = pkg.title
      customSubtitle.value = pkg.description || 'Promoción Especial de Combos Streaming'
      headlineBadge.value = pkg.badge || '🔥 25% OFF - RECOMENDADO'
      customPrice.value = pkg.promoPrice || null
      customOriginalPrice.value = pkg.originalPrice || null
      if (pkg.services && pkg.services.length >= 2) {
        bullet1.value = `✓ ${pkg.services[0].platform} (${pkg.services[0].planLabel})`
        bullet2.value = `✓ ${pkg.services[1].platform} (${pkg.services[1].planLabel})`
        bullet3.value = pkg.services[2] 
          ? `✓ ${pkg.services[2].platform} (${pkg.services[2].planLabel})`
          : 'Garantía Total 24/7 & Entrega Inmediata'
      }
    }
  } else if (newType === 'physical') {
    if (!selectedPhysicalId.value && adminStore.physicalProducts.length > 0) {
      selectedPhysicalId.value = String(adminStore.physicalProducts[0].id)
    }
    const prod = selectedPhysicalProduct.value
    if (prod) {
      customTitle.value = prod.name || ''
      customSubtitle.value = 'Tecnología & Accesorios de Alta Calidad'
      headlineBadge.value = '📦 ENVÍO A TODO EL PAÍS'
      customPrice.value = prod.priceNumeric || null
      customOriginalPrice.value = null
      bullet1.value = 'Excelente Calidad y Diseño Garantizado'
      bullet2.value = 'Envío Rápido y Seguro a Todo el País'
      bullet3.value = 'Compra 100% Segura con Asesoría Oficial'
    }
  } else {
    // Individual
    headlineBadge.value = '🔥 ¡OFERTA EXCLUSIVA!'
    customTitle.value = ''
    customSubtitle.value = ''
    customPrice.value = null
    customOriginalPrice.value = null
    bullet1.value = 'Calidad Ultra HD 4K Sin Caídas'
    bullet2.value = 'Perfil Privado con PIN de Seguridad'
    bullet3.value = 'Soporte & Garantía Directa 24/7'
  }
})

// Manejar producto inicial pasado por prop
watch(() => props.initialProduct, (prod) => {
  if (!prod) return
  if (prod.itemType === 'package' || prod.services || prod.promoPrice) {
    flyerProductType.value = 'combo'
    if (prod.id) selectedPackageId.value = prod.id
    customTitle.value = prod.title || ''
    customSubtitle.value = prod.description || ''
    headlineBadge.value = prod.badge || '🔥 25% OFF - RECOMENDADO'
    customPrice.value = prod.promoPrice || null
    customOriginalPrice.value = prod.originalPrice || null
  } else if (prod.itemType === 'physical' || prod.platform === 'Producto Físico' || prod.categoryIds || prod.price?.includes('$')) {
    flyerProductType.value = 'physical'
    selectedPhysicalId.value = String(prod.id)
    customTitle.value = prod.name || ''
    customSubtitle.value = 'Tecnología & Accesorios de Alta Calidad'
    customPrice.value = prod.priceNumeric || (parseInt(prod.price?.replace(/[$. ]/g, '').replace(',', '') || '0') / 100 || null)
    customOriginalPrice.value = null
    headlineBadge.value = '📦 ENVÍO A TODO EL PAÍS'
  } else if (prod.platform) {
    flyerProductType.value = 'individual'
    selectedPlatform.value = prod.platform
    selectedPlanKey.value = prod.planKey || '1_perfil'
    customTitle.value = ''
    customSubtitle.value = ''
    customPrice.value = null
    customOriginalPrice.value = null
  }
}, { immediate: true })

// Precios computados
const displayPriceNumeric = computed(() => {
  if (customPrice.value !== null && customPrice.value !== '') {
    return Number(customPrice.value)
  }
  if (flyerProductType.value === 'combo') {
    return selectedPackage.value?.promoPrice || 15900
  }
  if (flyerProductType.value === 'individual') {
    return currentPlanData.value.clientPrice || 5000
  }
  return selectedPhysicalProduct.value?.priceNumeric || 0
})

const displayPrice = computed(() => displayPriceNumeric.value.toLocaleString())

const displayOriginalPrice = computed(() => {
  if (customOriginalPrice.value !== null && customOriginalPrice.value !== '') {
    return Number(customOriginalPrice.value)
  }
  if (flyerProductType.value === 'combo') {
    return selectedPackage.value?.originalPrice || null
  }
  return null
})

// Título principal
const displayTitle = computed(() => {
  if (customTitle.value?.trim()) return customTitle.value.trim()
  if (flyerProductType.value === 'combo') {
    return selectedPackage.value?.title || 'Combo Especial Streaming'
  }
  if (flyerProductType.value === 'individual') {
    return currentPlatformData.value?.name || selectedPlatform.value || 'Streaming'
  }
  return selectedPhysicalProduct.value?.name || 'Producto Tecnológico'
})

// Subtítulo
const displaySubtitle = computed(() => {
  if (customSubtitle.value?.trim()) return customSubtitle.value.trim()
  if (flyerProductType.value === 'combo') {
    return selectedPackage.value?.description || 'Las mejores plataformas combinadas con descuento especial'
  }
  if (flyerProductType.value === 'individual') {
    return currentPlanData.value?.label || 'Suscripción Oficial'
  }
  return 'Tecnología & Accesorios de Alta Calidad'
})

// URL de referencia oficial (usando solo el dominio principal)
const fullProductReferralUrl = computed(() => {
  const domain = displayDomain.value || 'streamepro.me'
  const base = `https://${domain}`
  const refCode = props.resellerRef && props.resellerRef !== 'DIRECT' ? props.resellerRef : null

  if (flyerProductType.value === 'combo' && selectedPackage.value?.id) {
    const params = new URLSearchParams()
    params.set('sec', 'packages')
    params.set('pkg', selectedPackage.value.id)
    if (refCode) params.set('ref', refCode)
    return `${base}/?${params.toString()}`
  }

  if (flyerProductType.value === 'physical' && selectedPhysicalProduct.value?.id) {
    const params = new URLSearchParams()
    params.set('prod', String(selectedPhysicalProduct.value.id))
    if (refCode) params.set('ref', refCode)
    return `${base}/?${params.toString()}`
  }

  const params = new URLSearchParams()
  if (selectedPlatform.value) params.set('plat', selectedPlatform.value)
  if (refCode) params.set('ref', refCode)
  const qs = params.toString()
  return qs ? `${base}/?${qs}` : `${base}/`
})

const copyProductReferralUrl = () => {
  if (navigator?.clipboard?.writeText) {
    navigator.clipboard.writeText(fullProductReferralUrl.value).then(() => {
      toast.add('¡Enlace de referencia con tu comisión copiado!', 'success')
    }).catch(() => {
      toast.add('Error al copiar el enlace', 'error')
    })
  } else {
    toast.add('Portapapeles no disponible en este dispositivo', 'error')
  }
}

const previewProductReferralUrl = () => {
  if (fullProductReferralUrl.value) {
    window.open(fullProductReferralUrl.value, '_blank')
  }
}

// ==========================================
// DESCARGA DE FLYER IDÉNTICA A LA PREVISUALIZACIÓN (HTML2CANVAS)
// ==========================================
const downloadFlyerImage = async () => {
  if (!flyerPreviewRef.value) return
  isExporting.value = true
  toast.add(`Generando flyer en formato ${flyerFormatLabels[flyerFormat.value]} con diseño idéntico...`, 'info')

  try {
    const previewEl = flyerPreviewRef.value

    // Esperar renderizado limpio
    await nextTick()

    // Factor de escala retina 3x para ultra alta definición (1080p / 2K)
    const exportScale = 3

    const canvas = await html2canvas(previewEl, {
      scale: exportScale,
      useCORS: true,
      allowTaint: true,
      backgroundColor: null,
      logging: false,
      imageTimeout: 12000
    })

    canvas.toBlob((blob) => {
      if (!blob) {
        toast.add('Error al generar la imagen del flyer', 'error')
        return
      }
      const url = URL.createObjectURL(blob)
      const link = document.createElement('a')
      const safeName = displayTitle.value.toLowerCase().replace(/[^a-z0-9]/g, '_').slice(0, 24) || 'flyer'
      link.download = `flyer_${safeName}_${flyerFormat.value}.png`
      link.href = url
      link.click()
      URL.revokeObjectURL(url)
      toast.add(`¡Flyer descargado con éxito en formato ${flyerFormatLabels[flyerFormat.value]}!`, 'success')
    }, 'image/png')
  } catch (err) {
    console.error('Error generando flyer con html2canvas:', err)
    toast.add('Ocurrió un error al generar el flyer. Inténtalo de nuevo.', 'error')
  } finally {
    isExporting.value = false
  }
}

// Copiar texto para redes
const copySocialCopy = () => {
  let text = `🔥 *${headlineBadge.value}* 🔥\n\n` +
    `🎁 *${displayTitle.value}*\n` +
    `📌 *Detalle:* ${displaySubtitle.value}\n`

  if (displayOriginalPrice.value && Number(displayOriginalPrice.value) > Number(displayPriceNumeric.value)) {
    text += `❌ *Precio Normal:* ~$${Number(displayOriginalPrice.value).toLocaleString()} ARS~\n`
    text += `💰 *PRECIO PROMO:* *$${displayPrice.value} ARS* (Ahorras $${(Number(displayOriginalPrice.value) - Number(displayPriceNumeric.value)).toLocaleString()})\n\n`
  } else {
    text += `💰 *PRECIO EXCLUSIVO:* *$${displayPrice.value} ARS*\n\n`
  }

  text += `✅ ${bullet1.value}\n`
  text += `✅ ${bullet2.value}\n`
  text += `✅ ${bullet3.value}\n\n`
  text += `⚡ *¡Haz tu pedido 100% online con garantía oficial!*\n`
  text += `🌐 *Compra directa aquí:* ${fullProductReferralUrl.value}\n`
  text += `🏪 *${storeName.value}*`

  if (navigator?.clipboard?.writeText) {
    navigator.clipboard.writeText(text).then(() => {
      toast.add('¡Texto con tu enlace de referencia copiado! Pégalo en tu Estado o Historia.', 'success')
    })
  }
}

// Compartir a WhatsApp
const shareToWhatsAppStatus = () => {
  let text = `🔥 *${headlineBadge.value}* 🔥\n\n` +
    `🎁 *${displayTitle.value}*\n`

  if (displayOriginalPrice.value && Number(displayOriginalPrice.value) > Number(displayPriceNumeric.value)) {
    text += `💰 *PROMO:* *$${displayPrice.value} ARS* ~(Antes $${Number(displayOriginalPrice.value).toLocaleString()})~\n\n`
  } else {
    text += `💰 *PRECIO:* *$${displayPrice.value} ARS*\n\n`
  }

  text += `✅ ${bullet1.value}\n`
  text += `✅ ${bullet2.value}\n`
  text += `✅ ${bullet3.value}\n\n`
  text += `🌐 *Pide aquí:* ${fullProductReferralUrl.value}`

  const waUrl = `https://api.whatsapp.com/send?text=${encodeURIComponent(text)}`
  window.open(waUrl, '_blank')
}
</script>

<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-black/85 backdrop-blur-md animate-in fade-in duration-200">
    <div class="bg-slate-900 border border-slate-700/80 w-full max-w-5xl max-h-[92vh] rounded-3xl shadow-2xl flex flex-col overflow-hidden text-xs sm:text-sm">
      
      <!-- HEADER DEL MODAL -->
      <div class="px-5 py-3.5 border-b border-slate-800 flex justify-between items-center bg-slate-950/70 shrink-0">
        <div class="flex items-center gap-2.5">
          <span class="w-8 h-8 rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center text-slate-950 text-base shadow-md">
            🎨
          </span>
          <div>
            <h3 class="font-black text-white text-base sm:text-lg flex items-center gap-2">
              <span>Generador de Flyers & Banners</span>
              <span class="text-[10px] font-mono text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded-full border border-amber-500/20 font-bold hidden sm:inline-block">
                HD Reticular 3X
              </span>
            </h3>
            <p class="text-[11px] text-slate-400">
              Crea contenido promocional en formatos adaptables para WhatsApp, Instagram, Facebook y Web.
            </p>
          </div>
        </div>
        <button @click="emit('close')" class="w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center transition-colors text-base font-bold">
          ✕
        </button>
      </div>

      <!-- CONTENIDO PRINCIPAL: 2 COLUMNAS (CONFIGURACIÓN + PREVISUALIZACIÓN) -->
      <div class="p-4 sm:p-5 overflow-y-auto grid grid-cols-1 lg:grid-cols-12 gap-5 flex-1 custom-scrollbar">
        
        <!-- COLUMNA IZQUIERDA: CONTROLES DE GENERACIÓN (7 cols) -->
        <div class="lg:col-span-7 space-y-4">
          
          <!-- SELECTOR DE TIPO DE FLYER: 3 MODOS OFICIALES -->
          <div class="space-y-1.5">
            <label class="block font-bold uppercase text-slate-400 text-[11px]">
              🎯 Modo de Flyer a Promocionar
            </label>
            <div class="grid grid-cols-3 gap-1.5 p-1 bg-slate-950 rounded-2xl border border-slate-800">
              <button 
                type="button"
                @click="flyerProductType = 'individual'"
                :class="['py-2 px-2 rounded-xl font-black text-xs transition-all flex flex-col items-center justify-center gap-1 text-center', flyerProductType === 'individual' ? 'bg-amber-400 text-slate-950 shadow-md font-black' : 'text-slate-400 hover:text-white hover:bg-slate-900']"
              >
                <span class="text-base">🎬</span>
                <span class="leading-tight">Servicio Individual</span>
              </button>

              <button 
                type="button"
                @click="flyerProductType = 'combo'"
                :class="['py-2 px-2 rounded-xl font-black text-xs transition-all flex flex-col items-center justify-center gap-1 text-center relative overflow-hidden', flyerProductType === 'combo' ? 'bg-gradient-to-r from-pink-500 to-rose-500 text-white shadow-md font-black' : 'text-slate-400 hover:text-white hover:bg-slate-900']"
              >
                <span class="text-base">🎁</span>
                <span class="leading-tight">Combo / Paquete</span>
                <span class="text-[8px] bg-amber-400 text-slate-950 font-black px-1.5 py-0.2 rounded-full uppercase tracking-tighter">
                  Recomendado
                </span>
              </button>

              <button 
                type="button"
                @click="flyerProductType = 'physical'"
                :class="['py-2 px-2 rounded-xl font-black text-xs transition-all flex flex-col items-center justify-center gap-1 text-center', flyerProductType === 'physical' ? 'bg-sky-500 text-slate-950 shadow-md font-black' : 'text-slate-400 hover:text-white hover:bg-slate-900']"
              >
                <span class="text-base">📦</span>
                <span class="leading-tight">Productos Físicos</span>
              </button>
            </div>
          </div>

          <!-- SELECTOR DE FORMATO / TAMAÑOS ADAPTABLES -->
          <div class="space-y-1.5 bg-[#020617] p-3 rounded-2xl border border-slate-800">
            <div class="flex justify-between items-center">
              <label class="block font-bold uppercase text-slate-400 text-[11px]">
                📐 Formato / Tamaño Adaptable
              </label>
              <span class="text-[10px] font-mono text-amber-400 font-bold">
                {{ flyerFormat === 'story' ? '1080 × 1920 (9:16)' : flyerFormat === 'feed' ? '1080 × 1350 (4:5)' : flyerFormat === 'square' ? '1080 × 1080 (1:1)' : '1920 × 1080 (16:9)' }}
              </span>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
              <button 
                type="button"
                @click="flyerFormat = 'story'"
                :class="['p-2 rounded-xl border text-center font-bold text-xs transition-all flex flex-col items-center gap-0.5', flyerFormat === 'story' ? 'bg-amber-500/20 border-amber-500 text-amber-300 shadow' : 'bg-slate-950 border-slate-800 text-slate-400 hover:text-white']"
              >
                <span class="text-base">📱</span>
                <span class="font-black text-[11px]">9:16 Story / Estado</span>
                <span class="text-[9px] text-slate-500 font-mono">WhatsApp & Reels</span>
              </button>

              <button 
                type="button"
                @click="flyerFormat = 'feed'"
                :class="['p-2 rounded-xl border text-center font-bold text-xs transition-all flex flex-col items-center gap-0.5', flyerFormat === 'feed' ? 'bg-amber-500/20 border-amber-500 text-amber-300 shadow' : 'bg-slate-950 border-slate-800 text-slate-400 hover:text-white']"
              >
                <span class="text-base">📸</span>
                <span class="font-black text-[11px]">4:5 Feed Instagram</span>
                <span class="text-[9px] text-slate-500 font-mono">Retrato Vertical</span>
              </button>

              <button 
                type="button"
                @click="flyerFormat = 'square'"
                :class="['p-2 rounded-xl border text-center font-bold text-xs transition-all flex flex-col items-center gap-0.5', flyerFormat === 'square' ? 'bg-amber-500/20 border-amber-500 text-amber-300 shadow' : 'bg-slate-950 border-slate-800 text-slate-400 hover:text-white']"
              >
                <span class="text-base">⏹️</span>
                <span class="font-black text-[11px]">1:1 Post Cuadrado</span>
                <span class="text-[9px] text-slate-500 font-mono">Feed & Catálogo</span>
              </button>

              <button 
                type="button"
                @click="flyerFormat = 'banner'"
                :class="['p-2 rounded-xl border text-center font-bold text-xs transition-all flex flex-col items-center gap-0.5', flyerFormat === 'banner' ? 'bg-amber-500/20 border-amber-500 text-amber-300 shadow' : 'bg-slate-950 border-slate-800 text-slate-400 hover:text-white']"
              >
                <span class="text-base">🖥️</span>
                <span class="font-black text-[11px]">16:9 Banner Web</span>
                <span class="text-[9px] text-slate-500 font-mono">Horizontal Cover</span>
              </button>
            </div>
          </div>

          <!-- MODO 1: SERVICIO INDIVIDUAL -->
          <div v-if="flyerProductType === 'individual'" class="grid grid-cols-1 sm:grid-cols-2 gap-3 bg-[#020617] p-3.5 rounded-2xl border border-slate-800">
            <div>
              <label class="block font-bold uppercase text-slate-400 mb-1">🎬 Plataforma de Streaming</label>
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

          <!-- MODO 2: PROMOCIÓN DE COMBO / PAQUETE (RECOMENDADO) -->
          <div v-else-if="flyerProductType === 'combo'" class="space-y-3 bg-[#020617] p-3.5 rounded-2xl border border-pink-500/30">
            <div class="flex items-center justify-between">
              <label class="block font-black uppercase text-pink-300 text-xs flex items-center gap-1.5">
                <span>🎁</span> Seleccionar Combo Oficial ({{ availablePackages.length }})
              </label>
              <span class="text-[10px] font-mono text-amber-400 font-bold bg-amber-500/10 px-2 py-0.5 rounded border border-amber-500/20">
                Ahorro Especial
              </span>
            </div>
            <select v-model="selectedPackageId" class="w-full bg-slate-900 border border-pink-500/40 rounded-xl p-2.5 text-white font-bold outline-none focus:border-pink-400">
              <option v-for="pkg in availablePackages" :key="pkg.id" :value="pkg.id">
                {{ pkg.title }} — Promo ${{ pkg.promoPrice?.toLocaleString() }} (Antes ${{ pkg.originalPrice?.toLocaleString() }})
              </option>
            </select>

            <!-- Resumen de servicios incluidos en el combo -->
            <div v-if="selectedPackage" class="bg-slate-950/90 p-2.5 rounded-xl border border-slate-800 space-y-1.5">
              <span class="text-[10px] font-bold uppercase text-slate-400 block">Servicios incluidos en este combo:</span>
              <div class="flex flex-wrap gap-2">
                <span 
                  v-for="(srv, idx) in selectedPackage.services" 
                  :key="idx"
                  class="bg-slate-900 px-2.5 py-1 rounded-lg border border-slate-700 text-white font-bold text-[11px] flex items-center gap-1.5"
                >
                  <span>{{ getPlatformIcon(srv.platform) }}</span>
                  <span>{{ srv.platform }}</span>
                  <span class="text-[10px] text-pink-300 font-normal">({{ srv.planLabel }})</span>
                </span>
              </div>
            </div>
          </div>

          <!-- MODO 3: PRODUCTOS FÍSICOS -->
          <div v-else class="space-y-3 bg-[#020617] p-3.5 rounded-2xl border border-slate-800">
            <div class="flex flex-col sm:flex-row gap-2">
              <div class="flex-1">
                <label class="block font-bold uppercase text-slate-400 mb-1">Buscar Producto Físico</label>
                <input 
                  v-model="physicalSearch" 
                  type="text" 
                  placeholder="Buscar por nombre (ej: cortadora, smartwatch, trípode)..."
                  class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2 text-white outline-none focus:border-amber-400"
                >
              </div>
              <div class="sm:w-44">
                <label class="block font-bold uppercase text-slate-400 mb-1">Categoría</label>
                <select v-model="selectedPhysicalCat" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2 text-white outline-none focus:border-amber-400 font-bold">
                  <option value="All">Todas ({{ adminStore.physicalProducts.length }})</option>
                  <option v-for="cat in adminStore.physicalCategories" :key="cat.id" :value="cat.id">
                    {{ cat.name }}
                  </option>
                </select>
              </div>
            </div>

            <div>
              <div class="flex justify-between items-center mb-1">
                <label class="block font-bold uppercase text-slate-400">Seleccionar Producto ({{ filteredPhysicalProducts.length }})</label>
                <span class="text-[10px] font-mono text-slate-500">ID: #{{ selectedPhysicalProduct?.id }}</span>
              </div>
              <select v-model="selectedPhysicalId" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-white font-bold outline-none focus:border-amber-400">
                <option v-for="prod in filteredPhysicalProducts" :key="prod.id" :value="prod.id">
                  {{ prod.name }} — {{ prod.price }}
                </option>
              </select>
            </div>

            <!-- Preview Imagen Físico -->
            <div v-if="selectedPhysicalProduct" class="flex items-center gap-3 bg-slate-950 p-2 rounded-xl border border-slate-800">
              <div class="w-12 h-12 bg-white rounded-lg p-1 shrink-0 flex items-center justify-center border border-slate-700">
                <img :src="selectedPhysicalProduct.image" :alt="selectedPhysicalProduct.name" class="max-h-full max-w-full object-contain">
              </div>
              <div class="flex-1 min-w-0">
                <h5 class="text-xs font-bold text-white truncate">{{ selectedPhysicalProduct.name }}</h5>
                <span class="text-emerald-400 font-black text-xs font-mono">{{ selectedPhysicalProduct.price }}</span>
              </div>
            </div>
          </div>

          <!-- PERSONALIZACIÓN DE TÍTULO Y SUBTÍTULO -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 bg-[#020617] p-3 rounded-2xl border border-slate-800">
            <div>
              <div class="flex justify-between items-center mb-1">
                <label class="block font-bold uppercase text-slate-400 text-[11px]">Título en Flyer</label>
                <button v-if="customTitle" type="button" @click="customTitle = ''" class="text-[10px] text-amber-400 hover:underline">
                  Restablecer
                </button>
              </div>
              <input 
                v-model="customTitle" 
                type="text" 
                :placeholder="displayTitle" 
                class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2 text-white font-bold outline-none focus:border-amber-400"
              >
            </div>
            <div>
              <div class="flex justify-between items-center mb-1">
                <label class="block font-bold uppercase text-slate-400 text-[11px]">Subtítulo en Flyer</label>
                <button v-if="customSubtitle" type="button" @click="customSubtitle = ''" class="text-[10px] text-amber-400 hover:underline">
                  Restablecer
                </button>
              </div>
              <input 
                v-model="customSubtitle" 
                type="text" 
                :placeholder="displaySubtitle" 
                class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2 text-amber-300 font-semibold outline-none focus:border-amber-400"
              >
            </div>
          </div>

          <!-- BADGE SUPERIOR Y PRECIOS -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
              <label class="block font-bold uppercase text-slate-400 mb-1">Badge de Impacto</label>
              <input v-model="headlineBadge" list="badgesList" type="text" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2 text-amber-300 font-bold outline-none">
              <datalist id="badgesList">
                <option v-for="b in suggestedBadges" :key="b" :value="b" />
              </datalist>
            </div>

            <div>
              <label class="block font-bold uppercase text-slate-400 mb-1">
                Precio Promo (ARS)
              </label>
              <input 
                v-model="customPrice" 
                type="number" 
                :placeholder="`$${displayPrice}`" 
                class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2 text-emerald-400 font-mono font-bold outline-none"
              >
            </div>

            <div>
              <label class="block font-bold uppercase text-slate-400 mb-1">
                Precio Normal (Tachado)
              </label>
              <input 
                v-model="customOriginalPrice" 
                type="number" 
                :placeholder="displayOriginalPrice ? `$${displayOriginalPrice.toLocaleString()}` : 'Opcional (Tachado)'" 
                class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2 text-slate-300 font-mono outline-none"
              >
            </div>
          </div>

          <!-- PÁGINA WEB / DOMINIO EN EL FLYER: SOLO DOMINIO PRINCIPAL -->
          <div class="bg-[#020617] p-3 rounded-2xl border border-sky-500/30 space-y-2">
            <div class="flex items-center justify-between">
              <label class="block font-bold uppercase text-sky-400 text-[11px] flex items-center gap-1.5">
                <span>🌐</span> Página Web / Dominio en el Flyer
              </label>
              <span class="text-[10px] font-mono text-emerald-400 font-bold bg-emerald-500/10 px-2 py-0.5 rounded border border-emerald-500/20">
                Solo Dominio Principal
              </span>
            </div>

            <!-- Opciones Rápidas de Dominio Principal Oficial -->
            <div class="grid grid-cols-2 gap-2">
              <button 
                type="button" 
                @click="websiteUrl = 'streamepro.me'" 
                :class="['p-2.5 rounded-xl border text-left font-bold transition-all flex items-center gap-2.5 cursor-pointer', displayDomain === 'streamepro.me' ? 'bg-sky-500 text-slate-950 border-sky-400 shadow-md' : 'bg-slate-950 text-slate-300 border-slate-800 hover:border-sky-500/50']"
              >
                <span class="text-base">🌐</span>
                <div class="min-w-0">
                  <div class="font-mono font-black text-xs truncate">streamepro.me</div>
                  <div class="text-[9px] opacity-85">Dominio Principal Oficial</div>
                </div>
              </button>

              <button 
                type="button" 
                @click="websiteUrl = 'spro.li'" 
                :class="['p-2.5 rounded-xl border text-left font-bold transition-all flex items-center gap-2.5 cursor-pointer', displayDomain === 'spro.li' ? 'bg-sky-500 text-slate-950 border-sky-400 shadow-md' : 'bg-slate-950 text-slate-300 border-slate-800 hover:border-sky-500/50']"
              >
                <span class="text-base">⭐</span>
                <div class="min-w-0">
                  <div class="font-mono font-black text-xs truncate">spro.li</div>
                  <div class="text-[9px] opacity-85">Dominio Corto Oficial</div>
                </div>
              </button>
            </div>

            <!-- Entrada de solo dominio limpio -->
            <div class="flex items-center gap-2 pt-1">
              <span class="text-slate-400 text-[11px] font-mono">Dominio:</span>
              <input 
                v-model="websiteUrl" 
                type="text" 
                class="flex-1 bg-slate-900 border border-slate-800 rounded-xl px-3 py-1.5 text-sky-400 outline-none font-mono font-bold text-xs"
                placeholder="streamepro.me"
              >
            </div>
          </div>

          <!-- ENLACE DE REFERENCIA DIRECTO -->
          <div class="bg-slate-950/90 p-3 rounded-2xl border border-slate-800 space-y-2">
            <div class="flex items-center justify-between text-xs">
              <span class="font-bold text-slate-300 flex items-center gap-1.5">
                <span>🔗</span> Enlace Directo para Redes Sociales:
              </span>
              <span class="text-[10px] font-mono text-amber-400 bg-slate-900 px-2 py-0.5 rounded border border-slate-800">
                Ref: {{ resellerRef || 'DIRECT' }}
              </span>
            </div>
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
              <input 
                :value="fullProductReferralUrl" 
                readonly 
                class="flex-1 bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-sky-300 font-mono outline-none select-all"
              >
              <div class="flex gap-1.5">
                <button 
                  @click="copyProductReferralUrl" 
                  class="flex-1 sm:flex-none bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs px-3.5 py-2 rounded-xl transition flex items-center justify-center gap-1 shadow"
                  title="Copiar URL"
                >
                  <span>📋 Copiar</span>
                </button>
                <button 
                  @click="previewProductReferralUrl" 
                  class="flex-1 sm:flex-none bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs px-3.5 py-2 rounded-xl transition flex items-center justify-center gap-1 shadow"
                  title="Abrir tienda en nueva pestaña"
                >
                  <span>👁️ Ver ↗</span>
                </button>
              </div>
            </div>
          </div>

          <!-- PUNTOS CLAVE / BENEFICIOS -->
          <div class="space-y-1.5 bg-[#020617] p-3 rounded-2xl border border-slate-800">
            <span class="block font-bold uppercase text-slate-400 text-[11px] mb-1">
              Beneficios Clave en el Flyer (3 Puntos)
            </span>
            <input v-model="bullet1" type="text" class="w-full bg-slate-950 border border-slate-800 rounded-lg p-1.5 text-slate-200 outline-none text-xs">
            <input v-model="bullet2" type="text" class="w-full bg-slate-950 border border-slate-800 rounded-lg p-1.5 text-slate-200 outline-none text-xs">
            <input v-model="bullet3" type="text" class="w-full bg-slate-950 border border-slate-800 rounded-lg p-1.5 text-slate-200 outline-none text-xs">
          </div>

          <!-- ESTILO VISUAL / TEMA -->
          <div>
            <label class="block font-bold uppercase text-slate-400 mb-1.5">Estilo / Tema Visual</label>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
              <button 
                @click="flyerTheme = 'cinema'"
                :class="['p-2 rounded-xl border text-center font-bold text-[11px] transition-all', flyerTheme === 'cinema' ? 'bg-red-950/50 border-red-500 text-red-300 shadow' : 'bg-slate-950 border-slate-800 text-slate-400']"
              >
                🎬 Cinema Dark
              </button>
              <button 
                @click="flyerTheme = 'cyber'"
                :class="['p-2 rounded-xl border text-center font-bold text-[11px] transition-all', flyerTheme === 'cyber' ? 'bg-purple-950/50 border-purple-500 text-purple-300 shadow' : 'bg-slate-950 border-slate-800 text-slate-400']"
              >
                ⚡ Cyber Neon
              </button>
              <button 
                @click="flyerTheme = 'emerald'"
                :class="['p-2 rounded-xl border text-center font-bold text-[11px] transition-all', flyerTheme === 'emerald' ? 'bg-emerald-950/50 border-emerald-500 text-emerald-300 shadow' : 'bg-slate-950 border-slate-800 text-slate-400']"
              >
                💎 Emerald Deal
              </button>
              <button 
                @click="flyerTheme = 'blue'"
                :class="['p-2 rounded-xl border text-center font-bold text-[11px] transition-all', flyerTheme === 'blue' ? 'bg-sky-950/50 border-sky-500 text-sky-300 shadow' : 'bg-slate-950 border-slate-800 text-slate-400']"
              >
                ✨ Deep Blue
              </button>
            </div>
          </div>

          <!-- BOTONES DE ACCIÓN -->
          <div class="flex flex-col sm:flex-row gap-2.5 pt-2">
            <button 
              @click="downloadFlyerImage" 
              :disabled="isExporting"
              class="flex-1 bg-gradient-to-r from-amber-500 via-orange-500 to-amber-500 hover:from-amber-400 hover:to-orange-400 text-slate-950 font-black py-3 px-4 rounded-xl shadow-lg transition-all flex items-center justify-center gap-2 text-xs cursor-pointer disabled:opacity-50"
            >
              <span>{{ isExporting ? '⏳ Exportando Imagen HD...' : `⬇️ Descargar Flyer HD (${flyerFormatLabels[flyerFormat]})` }}</span>
            </button>
            <button 
              @click="copySocialCopy" 
              class="bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold px-4 py-3 rounded-xl border border-slate-700 transition-all flex items-center justify-center gap-1.5"
              title="Copiar texto con enlace listo para pegar en la historia o post"
            >
              <span>📋 Copiar Texto</span>
            </button>
            <button 
              @click="shareToWhatsAppStatus" 
              class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold px-3.5 py-3 rounded-xl shadow transition-all flex items-center justify-center text-sm"
              title="Compartir a WhatsApp"
            >
              <span>📲</span>
            </button>
          </div>
        </div>

        <!-- COLUMNA DERECHA: VISTA PREVIA EXACTA DEL FLYER (5 cols) -->
        <div class="lg:col-span-5 flex flex-col items-center justify-center bg-slate-950/90 p-4 rounded-3xl border border-slate-800">
          <div class="flex items-center justify-between w-full mb-3">
            <span class="text-[11px] font-mono text-slate-400 font-bold uppercase tracking-wider">
              Vista Previa en Vivo
            </span>
            <span class="text-[10px] font-mono text-amber-400 bg-amber-500/10 px-2.5 py-0.5 rounded-full border border-amber-500/20 font-bold">
              {{ flyerFormatLabels[flyerFormat] }}
            </span>
          </div>

          <!-- CONTENEDOR DEL FLYER (ESTRUCTURA EXACTA CAPTURABLE POR HTML2CANVAS) -->
          <div 
            ref="flyerPreviewRef" 
            :class="[
              'w-full rounded-3xl p-4 sm:p-5 flex flex-col justify-between relative overflow-hidden shadow-2xl border transition-all text-white select-none',
              flyerFormat === 'story' ? 'max-w-[310px] aspect-[9/16]' :
              flyerFormat === 'feed' ? 'max-w-[320px] aspect-[4/5]' :
              flyerFormat === 'square' ? 'max-w-[320px] aspect-square' :
              'max-w-[460px] aspect-[16/9]',
              flyerTheme === 'cinema' ? 'bg-gradient-to-b from-black via-red-950/70 to-slate-950 border-red-500/40' :
              flyerTheme === 'cyber' ? 'bg-gradient-to-b from-slate-950 via-purple-950/80 to-slate-950 border-purple-500/40' :
              flyerTheme === 'emerald' ? 'bg-gradient-to-b from-slate-950 via-emerald-950/70 to-slate-950 border-emerald-500/40' :
              'bg-gradient-to-b from-slate-950 via-sky-950/80 to-slate-950 border-sky-500/40'
            ]"
          >
            <!-- Efectos de Luces y Gradientes ambientales -->
            <div class="absolute -top-12 -right-12 w-44 h-44 bg-amber-500/15 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-12 -left-12 w-44 h-44 bg-purple-500/15 rounded-full blur-3xl pointer-events-none"></div>

            <!-- PARTE SUPERIOR: MARCA DE LA TIENDA & BADGE -->
            <div class="relative z-10 text-center space-y-1.5 shrink-0">
              <div class="flex items-center justify-between border-b border-white/10 pb-1.5">
                <span class="text-[11px] font-mono font-black uppercase tracking-widest text-amber-300 truncate max-w-[200px]">
                  {{ storeName }}
                </span>
                <span class="text-[9px] font-bold bg-white/10 px-2 py-0.5 rounded-full border border-white/15 text-slate-300">
                  OFICIAL
                </span>
              </div>

              <!-- Badge Dinámico (oculto en banner para balancear espacio horizontal) -->
              <div v-if="flyerFormat !== 'banner'" class="inline-block bg-gradient-to-r from-amber-500 via-orange-500 to-amber-500 text-slate-950 text-[10px] font-black px-3.5 py-0.5 rounded-full shadow-lg uppercase tracking-wider">
                {{ headlineBadge }}
              </div>
            </div>

            <!-- ============================================== -->
            <!-- CASO 1: FORMATO BANNER HORIZONTAL (16:9)       -->
            <!-- ============================================== -->
            <div v-if="flyerFormat === 'banner'" class="relative z-10 flex items-center gap-3.5 my-auto">
              <!-- Columna izquierda: Hero visual -->
              <div class="w-28 h-28 shrink-0 flex items-center justify-center">
                <!-- Modo Combo: Cluster de plataformas -->
                <div v-if="flyerProductType === 'combo'" class="grid grid-cols-2 gap-1.5 p-1 bg-slate-900/90 rounded-2xl border border-pink-500/40 shadow-xl">
                  <div 
                    v-for="(srv, idx) in (selectedPackage?.services?.slice(0, 4) || [])" 
                    :key="idx"
                    class="w-10 h-10 rounded-xl bg-slate-950 flex flex-col items-center justify-center p-0.5 border border-white/10"
                  >
                    <span class="text-base">{{ getPlatformIcon(srv.platform) }}</span>
                    <span class="text-[7px] text-white font-bold truncate max-w-full">{{ srv.platform }}</span>
                  </div>
                </div>

                <!-- Modo Individual: Icono de Plataforma -->
                <div v-else-if="flyerProductType === 'individual'" class="w-24 h-24 rounded-2xl bg-slate-900/90 border border-white/20 flex flex-col items-center justify-center shadow-xl p-2">
                  <span class="text-4xl">{{ currentPlatformIcon }}</span>
                  <span class="text-[9px] font-black text-amber-300 mt-1 uppercase">{{ selectedPlatform }}</span>
                </div>

                <!-- Modo Físico: Imagen con CORS -->
                <div v-else class="w-24 h-24 rounded-2xl bg-white p-2 flex items-center justify-center shadow-xl border border-white/40 overflow-hidden">
                  <img 
                    :src="productImageCORS || selectedPhysicalProduct?.image" 
                    :alt="displayTitle" 
                    crossorigin="anonymous"
                    class="max-h-full max-w-full object-contain filter drop-shadow-sm"
                  >
                </div>
              </div>

              <!-- Columna derecha: Detalles y Precio -->
              <div class="flex-1 min-w-0 space-y-1">
                <div class="inline-block bg-gradient-to-r from-amber-500 to-orange-500 text-slate-950 text-[9px] font-black px-2 py-0.5 rounded-full uppercase tracking-wider">
                  {{ headlineBadge }}
                </div>
                <h2 class="text-xs font-black tracking-tight leading-tight text-white line-clamp-1">
                  {{ displayTitle }}
                </h2>
                <div class="text-[9px] font-semibold text-amber-300 font-mono truncate">
                  {{ displaySubtitle }}
                </div>

                <!-- Precios con Tachado si corresponde -->
                <div class="flex items-baseline gap-2">
                  <span v-if="displayOriginalPrice" class="text-[10px] text-slate-400 line-through">
                    ${{ Number(displayOriginalPrice).toLocaleString() }}
                  </span>
                  <div class="flex items-baseline gap-0.5">
                    <span class="text-amber-400 font-bold text-xs">$</span>
                    <span class="font-black text-base text-white font-mono">{{ displayPrice }}</span>
                    <span class="text-[8px] font-bold text-slate-400 font-mono">ARS</span>
                  </div>
                </div>

                <div class="text-[9px] text-slate-300 truncate">
                  <span class="text-emerald-400 font-bold">✓</span> {{ bullet1 }}
                </div>
              </div>
            </div>

            <!-- ============================================== -->
            <!-- CASO 2: FORMATOS VERTICALES (9:16, 4:5, 1:1)   -->
            <!-- ============================================== -->
            <div v-else class="relative z-10 text-center space-y-2 my-auto">
              
              <!-- HERO VISUAL SEGÚN EL MODO -->
              <!-- Modo Combo: Tarjetas interactivas de plataformas del paquete -->
              <div v-if="flyerProductType === 'combo'" class="mx-auto flex items-center justify-center gap-1.5 py-1">
                <div 
                  v-for="(srv, idx) in (selectedPackage?.services?.slice(0, 3) || [])" 
                  :key="idx"
                  :class="[
                    'rounded-2xl bg-slate-900/90 border border-pink-500/40 flex flex-col items-center justify-center shadow-lg p-1 transition-all',
                    flyerFormat === 'story' ? 'w-16 h-16 sm:w-18 sm:h-18' :
                    flyerFormat === 'feed' ? 'w-14 h-14' : 'w-12 h-12'
                  ]"
                >
                  <span :class="flyerFormat === 'square' ? 'text-lg' : 'text-2xl'">{{ getPlatformIcon(srv.platform) }}</span>
                  <span class="text-[8px] font-black text-white truncate max-w-full mt-0.5">{{ srv.platform }}</span>
                  <span class="text-[7px] text-pink-300 truncate font-mono">{{ srv.planLabel }}</span>
                </div>
              </div>

              <!-- Modo Individual: Icono gigante de streaming -->
              <div 
                v-else-if="flyerProductType === 'individual'" 
                :class="[
                  'mx-auto rounded-3xl bg-slate-900/90 border border-white/20 flex flex-col items-center justify-center shadow-2xl p-2 transition-all',
                  flyerFormat === 'story' ? 'w-24 h-24 sm:w-28 sm:h-28' :
                  flyerFormat === 'feed' ? 'w-20 h-20' : 'w-16 h-16'
                ]"
              >
                <span :class="flyerFormat === 'story' ? 'text-4xl sm:text-5xl' : flyerFormat === 'feed' ? 'text-3xl' : 'text-2xl'">
                  {{ currentPlatformIcon }}
                </span>
                <span :class="['font-black text-amber-300 uppercase tracking-wider', flyerFormat === 'square' ? 'text-[8px]' : 'text-[10px] mt-1']">
                  {{ selectedPlatform }}
                </span>
              </div>

              <!-- Modo Físico: Foto del producto sobre fondo blanco -->
              <div 
                v-else
                :class="[
                  'mx-auto rounded-2xl bg-white p-2 flex items-center justify-center shadow-xl border border-white/40 overflow-hidden',
                  flyerFormat === 'story' ? 'w-28 h-28 sm:w-32 sm:h-32' :
                  flyerFormat === 'feed' ? 'w-24 h-24' : 'w-20 h-20'
                ]"
              >
                <img 
                  :src="productImageCORS || selectedPhysicalProduct?.image" 
                  :alt="displayTitle"
                  crossorigin="anonymous"
                  class="max-h-full max-w-full object-contain filter drop-shadow-sm"
                >
              </div>

              <!-- TÍTULO Y SUBTÍTULO -->
              <div>
                <h2 :class="['font-black tracking-tight leading-tight drop-shadow text-white line-clamp-2', flyerFormat === 'square' ? 'text-xs' : 'text-sm sm:text-base']">
                  {{ displayTitle }}
                </h2>
                <p class="text-[10px] font-semibold text-amber-300/90 font-mono mt-0.5 truncate">
                  {{ displaySubtitle }}
                </p>
              </div>

              <!-- TARJETA DE PRECIO CON TACHADO (ESPECIAL PARA COMBOS Y OFERTAS) -->
              <div class="py-1 px-3.5 rounded-2xl bg-black/60 border border-white/10 inline-block shadow-xl">
                <!-- Precio original tachado si existe -->
                <div v-if="displayOriginalPrice" class="flex items-center justify-center gap-1.5 mb-0.5">
                  <span class="text-[10px] text-slate-400 line-through">
                    Antes: ${{ Number(displayOriginalPrice).toLocaleString() }}
                  </span>
                  <span v-if="Number(displayOriginalPrice) > Number(displayPriceNumeric)" class="text-[8px] bg-emerald-500/20 text-emerald-300 font-black px-1 rounded">
                    -{{ Math.round((1 - Number(displayPriceNumeric) / Number(displayOriginalPrice)) * 100) }}%
                  </span>
                </div>
                <div v-else class="text-[8px] uppercase font-bold text-slate-400 tracking-wider">
                  PRECIO EXCLUSIVO
                </div>

                <!-- Precio final -->
                <div class="flex items-baseline justify-center gap-1">
                  <span class="text-xs font-black text-amber-400">$</span>
                  <span :class="['font-black tracking-tight text-white font-mono', flyerFormat === 'square' ? 'text-lg' : 'text-xl sm:text-2xl']">
                    {{ displayPrice }}
                  </span>
                  <span class="text-[9px] font-bold text-slate-400 font-mono">ARS</span>
                </div>
              </div>

              <!-- PUNTOS CLAVE / BENEFICIOS -->
              <div :class="['space-y-0.5 text-left px-2 font-medium text-slate-200', flyerFormat === 'square' ? 'text-[9px]' : 'text-[10px]']">
                <div class="flex items-center gap-1.5 truncate">
                  <span class="text-emerald-400 font-bold">✓</span>
                  <span class="truncate">{{ bullet1 }}</span>
                </div>
                <div class="flex items-center gap-1.5 truncate">
                  <span class="text-emerald-400 font-bold">✓</span>
                  <span class="truncate">{{ bullet2 }}</span>
                </div>
                <div v-if="flyerFormat !== 'square'" class="flex items-center gap-1.5 truncate">
                  <span class="text-emerald-400 font-bold">✓</span>
                  <span class="truncate">{{ bullet3 }}</span>
                </div>
              </div>
            </div>

            <!-- PARTE INFERIOR: LLAMADA A LA ACCIÓN CON SOLO EL DOMINIO PRINCIPAL -->
            <div class="relative z-10 text-center space-y-1 pt-1.5 border-t border-white/10 shrink-0">
              <span class="text-[8px] text-slate-400 font-bold uppercase tracking-wider block">
                ⚡ ¡Haz tu pedido online con garantía oficial!
              </span>
              <div class="bg-gradient-to-r from-sky-500 to-cyan-400 text-slate-950 font-black py-1.5 px-3.5 rounded-xl flex items-center justify-center gap-1.5 shadow-md">
                <span class="text-xs">🌐</span>
                <span class="font-mono text-xs font-black tracking-wide">{{ displayDomain }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 5px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: rgba(15, 23, 42, 0.6);
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(148, 163, 184, 0.3);
  border-radius: 9999px;
}
</style>

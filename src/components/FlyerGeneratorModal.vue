<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import { toPng } from 'html-to-image'
import { useAdminStore } from '../store/admin'
import { useToastStore } from '../store/toast'

const props = defineProps({
  show: { type: Boolean, default: false },
  defaultStoreName: { type: String, default: 'StreamePro Oficial' },
  initialProduct: { type: Object, default: null },
  resellerRef: { type: String, default: 'DIRECT' },
  websiteDomain: { type: String, default: 'streamepro.me' },
  clientOnlyPhysical: { type: Boolean, default: false }
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
const flyerTheme = ref('cyber') // 'cinema' | 'cyber' | 'emerald' | 'blue'
const flyerFormat = ref('story') // 'story' (9:16) | 'feed' (4:5) | 'square' (1:1) | 'banner' (16:9)

const flyerFormatLabels = {
  story: '9:16 Historias / Estados',
  feed: '4:5 Feed Instagram',
  square: '1:1 Post Cuadrado',
  banner: '16:9 Banner Horizontal'
}

// Configuración adaptativa de Estilos / Temas con el mismo diseño oficial y efectos específicos
const currentTheme = computed(() => {
  switch (flyerTheme.value) {
    case 'cinema':
      return {
        id: 'cinema',
        name: 'Cinema Dark',
        filter: 'hue-rotate(170deg) saturate(1.25) contrast(1.05)',
        accent: '#ef4444',
        accentLight: '#f87171',
        glow: 'rgba(239, 68, 68, 0.45)',
        border: 'rgba(239, 68, 68, 0.65)',
        badgeGrad: ['#dc2626', '#b91c1c'],
        cardBg: 'rgba(28, 6, 14, 0.95)',
        cardBorder: 'rgba(239, 68, 68, 0.55)',
        textAccent: '#f87171',
        pedGlow: 'rgba(239, 68, 68, 0.22)',
        bulletColor: '#ef4444',
        cssBorder: 'border-red-500/50',
        cssText: 'text-red-400',
        cssTagBorder: 'border-red-500/40 bg-red-950/80 text-red-200',
        cssBadge: 'bg-gradient-to-r from-red-600 via-rose-600 to-red-600 text-white shadow-lg shadow-red-950/60',
        cssPriceBox: 'bg-slate-950/90 border border-red-500/40 shadow-xl shadow-red-950/50',
        cssCheck: 'bg-red-500 text-slate-950',
        cssCheckText: 'text-red-400',
        cssContainerBorder: 'border-red-500/50 shadow-red-950/70',
        cssCurrency: 'text-red-400'
      }
    case 'emerald':
      return {
        id: 'emerald',
        name: 'Emerald Deal',
        filter: 'hue-rotate(-50deg) saturate(1.2) contrast(1.05)',
        accent: '#10b981',
        accentLight: '#34d399',
        glow: 'rgba(16, 185, 129, 0.45)',
        border: 'rgba(16, 185, 129, 0.65)',
        badgeGrad: ['#059669', '#047857'],
        cardBg: 'rgba(6, 26, 18, 0.95)',
        cardBorder: 'rgba(16, 185, 129, 0.55)',
        textAccent: '#34d399',
        pedGlow: 'rgba(16, 185, 129, 0.22)',
        bulletColor: '#10b981',
        cssBorder: 'border-emerald-500/50',
        cssText: 'text-emerald-400',
        cssTagBorder: 'border-emerald-500/40 bg-emerald-950/80 text-emerald-200',
        cssBadge: 'bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-600 text-white shadow-lg shadow-emerald-950/60',
        cssPriceBox: 'bg-slate-950/90 border border-emerald-500/40 shadow-xl shadow-emerald-950/50',
        cssCheck: 'bg-emerald-400 text-slate-950',
        cssCheckText: 'text-emerald-400',
        cssContainerBorder: 'border-emerald-500/50 shadow-emerald-950/70',
        cssCurrency: 'text-emerald-400'
      }
    case 'blue':
      return {
        id: 'blue',
        name: 'Deep Blue',
        filter: 'hue-rotate(30deg) saturate(1.3) contrast(1.05)',
        accent: '#3b82f6',
        accentLight: '#60a5fa',
        glow: 'rgba(59, 130, 246, 0.45)',
        border: 'rgba(59, 130, 246, 0.65)',
        badgeGrad: ['#1d4ed8', '#1e40af'],
        cardBg: 'rgba(8, 18, 44, 0.95)',
        cardBorder: 'rgba(59, 130, 246, 0.55)',
        textAccent: '#60a5fa',
        pedGlow: 'rgba(59, 130, 246, 0.22)',
        bulletColor: '#3b82f6',
        cssBorder: 'border-blue-500/50',
        cssText: 'text-blue-400',
        cssTagBorder: 'border-blue-500/40 bg-blue-950/80 text-blue-200',
        cssBadge: 'bg-gradient-to-r from-blue-600 via-indigo-600 to-blue-600 text-white shadow-lg shadow-blue-950/60',
        cssPriceBox: 'bg-slate-950/90 border border-blue-500/40 shadow-xl shadow-blue-950/50',
        cssCheck: 'bg-blue-400 text-slate-950',
        cssCheckText: 'text-blue-400',
        cssContainerBorder: 'border-blue-500/50 shadow-blue-950/70',
        cssCurrency: 'text-blue-400'
      }
    case 'cyber':
    default:
      return {
        id: 'cyber',
        name: 'Cyber Pro',
        filter: 'none',
        accent: '#00f0ff',
        accentLight: '#38bdf8',
        glow: 'rgba(0, 240, 255, 0.4)',
        border: 'rgba(56, 189, 248, 0.65)',
        badgeGrad: ['#f59e0b', '#ea580c'],
        cardBg: 'rgba(15, 23, 42, 0.95)',
        cardBorder: 'rgba(56, 189, 248, 0.55)',
        textAccent: '#38bdf8',
        pedGlow: 'rgba(0, 240, 255, 0.18)',
        bulletColor: '#00f0ff',
        cssBorder: 'border-cyan-400/50',
        cssText: 'text-cyan-300',
        cssTagBorder: 'border-cyan-500/40 bg-cyan-950/80 text-cyan-200',
        cssBadge: 'bg-gradient-to-r from-amber-500 via-orange-500 to-amber-500 text-slate-950 shadow-lg shadow-cyan-950/40',
        cssPriceBox: 'bg-slate-950/90 border border-cyan-400/40 shadow-xl shadow-cyan-950/40',
        cssCheck: 'bg-cyan-400 text-slate-950',
        cssCheckText: 'text-cyan-400',
        cssContainerBorder: 'border-cyan-400/50 shadow-cyan-950/60',
        cssCurrency: 'text-amber-400'
      }
  }
})

const isExporting = ref(false)
const flyerPreviewRef = ref(null)

// Precarga de las 4 plantillas Cyber Oficiales de Canva
if (typeof window !== 'undefined') {
  ['story', 'feed', 'square', 'banner'].forEach((fmt) => {
    const ext = fmt === 'story' ? 'png' : 'jpg'
    const img = new Image()
    img.src = `/flyer-template-cyber-${fmt}.${ext}`
  })
}

// Helper para obtener la URL de la plantilla Cyber según formato
// Helper de estilo de marca para plataformas de streaming en modo Cyber
const getPlatformCyberStyle = (platform) => {
  const p = (platform || '').toLowerCase()
  if (p.includes('netflix')) {
    return { color: '#e50914', glow: 'rgba(229, 9, 20, 0.45)', label: 'NETFLIX', sub: 'ORIGINAL & SERIES 4K', symbol: '🍿' }
  }
  if (p.includes('disney')) {
    return { color: '#38bdf8', glow: 'rgba(14, 165, 233, 0.5)', label: 'DISNEY+', sub: 'DISNEY · PIXAR · MARVEL', symbol: '✨' }
  }
  if (p.includes('max') || p.includes('hbo')) {
    return { color: '#818cf8', glow: 'rgba(99, 102, 241, 0.45)', label: 'HBO MAX', sub: 'WARNER BROS & DC', symbol: '🎬' }
  }
  if (p.includes('spotify') || p.includes('music')) {
    return { color: '#22c55e', glow: 'rgba(34, 197, 94, 0.45)', label: 'SPOTIFY PREMIUM', sub: 'MÚSICA EN ALTA FIDELIDAD', symbol: '🎵' }
  }
  if (p.includes('prime') || p.includes('amazon')) {
    return { color: '#00a8e1', glow: 'rgba(0, 168, 225, 0.45)', label: 'PRIME VIDEO', sub: 'SERIES & AMAZON ORIGINALS', symbol: '📦' }
  }
  if (p.includes('youtube')) {
    return { color: '#ef4444', glow: 'rgba(239, 68, 68, 0.45)', label: 'YOUTUBE PREMIUM', sub: 'SIN ANUNCIOS + MUSIC', symbol: '▶️' }
  }
  if (p.includes('iptv') || p.includes('magis') || p.includes('tele')) {
    return { color: '#06b6d4', glow: 'rgba(6, 182, 212, 0.45)', label: 'IPTV PRO', sub: '+1.500 CANALES EN VIVO', symbol: '📺' }
  }
  if (p.includes('paramount')) {
    return { color: '#38bdf8', glow: 'rgba(56, 189, 248, 0.45)', label: 'PARAMOUNT+', sub: 'PELÍCULAS & DEPORTES', symbol: '⛰️' }
  }
  if (p.includes('crunchy')) {
    return { color: '#f97316', glow: 'rgba(249, 115, 22, 0.45)', label: 'CRUNCHYROLL', sub: 'ANIME EN SIMULCAST', symbol: '🍥' }
  }
  return { color: '#38bdf8', glow: 'rgba(56, 189, 248, 0.45)', label: (platform || 'STREAMING').toUpperCase(), sub: 'SUSCRIPCIÓN OFICIAL', symbol: '⚡' }
}

const getCyberTemplateUrl = (format) => {
  if (format === 'story') return '/flyer-template-cyber-story.png'
  if (format === 'feed') return '/flyer-template-cyber-feed.jpg'
  if (format === 'square') return '/flyer-template-cyber-square.jpg'
  if (format === 'banner') return '/flyer-template-cyber-banner.jpg'
  return '/flyer-template-cyber-story.png'
}

// Badges sugeridos reactivos definidos junto al cálculo dinámico

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
  if (val.includes('spro.li') || !val) {
    return 'streamepro.me'
  }
  return val
})

// Watchers de props iniciales
watch(() => props.defaultStoreName, (val) => {
  if (val) storeName.value = val
}, { immediate: true })

watch(() => props.clientOnlyPhysical, (val) => {
  if (val) {
    flyerProductType.value = 'physical'
  }
}, { immediate: true })

watch(() => props.websiteDomain, (val) => {
  if (val && !val.includes('spro.li')) {
    websiteUrl.value = val.replace(/^https?:\/\//i, '').split('/')[0]
  } else {
    websiteUrl.value = 'streamepro.me'
  }
}, { immediate: true })

// Helper para convertir imagen a URL con CORS usando proxy seguro wsrv.nl
const getCORSImageUrl = (rawUrl) => {
  if (!rawUrl) return null
  let fullUrl = rawUrl
  if (!fullUrl.startsWith('http')) {
    fullUrl = fullUrl.startsWith('/') ? `${typeof window !== 'undefined' ? window.location.origin : 'https://streamepro.me'}${fullUrl}` : `https://www.revende.com.ar/${rawUrl}`
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
      headlineBadge.value = '🔥 ¡OFERTA EXCLUSIVA!'
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
      headlineBadge.value = '🔥 ¡OFERTA EXCLUSIVA!'
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

// Cálculo dinámico del porcentaje de descuento basado en precios ajustados
const calculatedDiscountPct = computed(() => {
  const promo = Number(displayPriceNumeric.value)
  const regular = Number(displayOriginalPrice.value)
  if (regular > 0 && promo > 0 && regular > promo) {
    const pct = Math.round(((regular - promo) / regular) * 100)
    return (pct > 0 && pct < 100) ? pct : null
  }
  return null
})

// Badges sugeridos reactivos al porcentaje calculado
const suggestedBadges = computed(() => {
  const pct = calculatedDiscountPct.value
  const list = []
  if (pct) {
    list.push(`🔥 ${pct}% OFF - RECOMENDADO`)
    list.push(`⚡ ${pct}% DE DESCUENTO`)
    list.push(`💥 MEGA PROMO ${pct}% OFF`)
    list.push(`🔥 AHORRA ${pct}% HOY`)
  } else {
    list.push('🔥 25% OFF - RECOMENDADO')
    list.push('🔥 ¡OFERTA EXCLUSIVA!')
  }
  list.push(
    '🎁 COMBO RECOMENDADO',
    '⏱️ ENTREGA 10 MIN A 1 HORA',
    '🍿 CINE EN CASA ULTRA HD',
    '🚀 100% GARANTIZADO',
    '💎 CALIDAD ULTRA HD 4K',
    '📦 ENVÍO A TODO EL PAÍS'
  )
  return list
})

// Función para actualizar el texto del badge reconociendo el porcentaje
const updateHeadlineBadgeWithDiscount = (newPct) => {
  if (!newPct) return
  if (!headlineBadge.value || !headlineBadge.value.trim()) {
    headlineBadge.value = `🔥 ${newPct}% OFF - RECOMENDADO`
    return
  }

  // Si ya tiene un patrón de porcentaje (ej: 25% OFF, 25%, 25% DE DESCUENTO)
  if (/(\d+)%\s*off/i.test(headlineBadge.value)) {
    headlineBadge.value = headlineBadge.value.replace(/(\d+)%\s*off/gi, `${newPct}% OFF`)
  } else if (/(\d+)%\s*de descuento/i.test(headlineBadge.value)) {
    headlineBadge.value = headlineBadge.value.replace(/(\d+)%\s*de descuento/gi, `${newPct}% DE DESCUENTO`)
  } else if (/(\d+)%/i.test(headlineBadge.value)) {
    headlineBadge.value = headlineBadge.value.replace(/(\d+)%/gi, `${newPct}%`)
  } else if (
    headlineBadge.value === '🔥 ¡OFERTA EXCLUSIVA!' || 
    headlineBadge.value === '📦 ENVÍO A TODO EL PAÍS' || 
    headlineBadge.value === '🎁 COMBO RECOMENDADO' ||
    headlineBadge.value.includes('RECOMENDADO')
  ) {
    headlineBadge.value = `🔥 ${newPct}% OFF - RECOMENDADO`
  }
}

// Watcher que sincroniza automáticamente el Badge de Impacto cuando cambian los precios (computados o manuales)
watch([displayPriceNumeric, displayOriginalPrice], () => {
  if (calculatedDiscountPct.value) {
    updateHeadlineBadgeWithDiscount(calculatedDiscountPct.value)
  }
}, { immediate: true })

const applyCalculatedBadge = (customFormat = 'recomendado') => {
  if (!calculatedDiscountPct.value) return
  if (customFormat === 'descuento') {
    headlineBadge.value = `⚡ ${calculatedDiscountPct.value}% DE DESCUENTO`
  } else if (customFormat === 'promo') {
    headlineBadge.value = `💥 MEGA PROMO ${calculatedDiscountPct.value}% OFF`
  } else {
    headlineBadge.value = `🔥 ${calculatedDiscountPct.value}% OFF - RECOMENDADO`
  }
}

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

// ========================================================
// MOTOR CANVAS 2D DE RESPALDO (100% LIBRE DE ERRORES OKLCH/DOM)
// ========================================================
const loadFlyerImageElement = (rawUrl) => {
  return new Promise((resolve) => {
    if (!rawUrl) return resolve(null)
    const img = new Image()
    img.crossOrigin = 'anonymous'
    let timer = setTimeout(() => resolve(null), 3500)
    img.onload = () => {
      clearTimeout(timer)
      resolve(img)
    }
    img.onerror = () => {
      clearTimeout(timer)
      resolve(null)
    }
    img.src = rawUrl
  })
}

function renderCanvasFlyerBlob() {
  return new Promise(async (resolve, reject) => {
    try {
      const format = flyerFormat.value
      let width = 1080
      let height = 1920

      if (format === 'feed') {
        width = 1080
        height = 1350
      } else if (format === 'square') {
        width = 1080
        height = 1080
      } else if (format === 'banner') {
        width = 1920
        height = 1080
      }

      const canvas = document.createElement('canvas')
      canvas.width = width
      canvas.height = height
      const ctx = canvas.getContext('2d')

      function roundRect(ctx, x, y, w, h, radius, fill, stroke, strokeColor = '#fff', lineWidth = 1) {
        ctx.beginPath()
        ctx.moveTo(x + radius, y)
        ctx.lineTo(x + w - radius, y)
        ctx.quadraticCurveTo(x + w, y, x + w, y + radius)
        ctx.lineTo(x + w, y + h - radius)
        ctx.quadraticCurveTo(x + w, y + h, x + w - radius, y + h)
        ctx.lineTo(x + radius, y + h)
        ctx.quadraticCurveTo(x, y + h, x, y + h - radius)
        ctx.lineTo(x, y + radius)
        ctx.quadraticCurveTo(x, y, x + radius, y)
        ctx.closePath()
        if (fill) ctx.fill()
        if (stroke) {
          ctx.lineWidth = lineWidth
          ctx.strokeStyle = strokeColor
          ctx.stroke()
        }
      }

      function wrapText(text, maxChars) {
        if (!text) return []
        const words = text.split(' ')
        const lines = []
        let cur = words[0] || ''
        for (let i = 1; i < words.length; i++) {
          if ((cur + ' ' + words[i]).length > maxChars) {
            lines.push(cur)
            cur = words[i]
          } else {
            cur += ' ' + words[i]
          }
        }
        lines.push(cur)
        return lines
      }

      const domainText = displayDomain.value || 'streamepro.me'

      // ========================================================
      // HELPERS DEL SISTEMA DE DISEÑO CYBER OFICIAL ELEVADO
      // ========================================================
      function drawCyberBackground(ctx, w, h, th) {
        const bgGrad = ctx.createLinearGradient(0, 0, 0, h)
        bgGrad.addColorStop(0, '#020617')
        bgGrad.addColorStop(0.5, (th && th.cardBg) ? th.cardBg : '#082f49')
        bgGrad.addColorStop(1, '#020617')
        ctx.fillStyle = bgGrad
        ctx.fillRect(0, 0, w, h)
      }

      async function drawVisorHeroContent(ctx, visorX, visorY, visorW, visorH) {
        const centerX = visorX + visorW / 2
        const centerY = visorY + visorH / 2
        const th = currentTheme.value

        ctx.save()

        // Resplandor holográfico sutil de fondo
        const pedGlow = ctx.createRadialGradient(centerX, centerY, 20, centerX, centerY, visorW * 0.5)
        pedGlow.addColorStop(0, th.pedGlow)
        pedGlow.addColorStop(1, 'transparent')
        ctx.fillStyle = pedGlow
        ctx.fillRect(visorX, visorY, visorW, visorH)

        if (flyerProductType.value === 'physical') {
          // Encajar la imagen del producto perfectamente en el rectángulo
          const imgEl = await loadFlyerImageElement(productImageCORS.value)

          // Marco interno exacto del rectángulo
          const pad = (visorW < 450) ? 14 : 8
          const innerX = visorX + pad
          const innerY = visorY + pad
          const innerW = visorW - pad * 2
          const innerH = visorH - pad * 2
          const cornerR = Math.min(26, innerW * 0.065)

          ctx.save()
          roundRect(ctx, innerX, innerY, innerW, innerH, cornerR, false, false)
          ctx.clip()

          // Fondo blanco inmaculado de vitrina
          ctx.fillStyle = '#ffffff'
          ctx.fillRect(innerX, innerY, innerW, innerH)

          if (imgEl) {
            // La imagen aprovecha el rectángulo al máximo (object-fit: contain)
            const imgPad = 14
            const maxW = innerW - imgPad * 2
            const maxH = innerH - imgPad * 2
            const imgAspect = (imgEl.width || 1) / (imgEl.height || 1)
            let dw = maxW
            let dh = maxH
            if (dw / dh > imgAspect) {
              dw = dh * imgAspect
            } else {
              dh = dw / imgAspect
            }
            const dx = innerX + (innerW - dw) / 2
            const dy = innerY + (innerH - dh) / 2
            ctx.drawImage(imgEl, dx, dy, dw, dh)
          } else {
            const iconSize = Math.min(130, innerH * 0.38)
            ctx.font = `${iconSize}px sans-serif`
            ctx.textAlign = 'center'
            ctx.fillText('📦', centerX, centerY + iconSize * 0.25)
          }

          ctx.restore()

          // Borde con resplandor neón alrededor del marco interno (sin obstruir la imagen)
          ctx.save()
          ctx.shadowColor = th.glow
          ctx.shadowBlur = 10
          roundRect(ctx, innerX, innerY, innerW, innerH, cornerR, false, true, th.border, 1.5)
          ctx.restore()
        } else if (flyerProductType.value === 'combo') {
          // Combos streaming: Tarjetas translúcidas futuristas encajadas en el rectángulo
          const services = (selectedPackage.value?.services || []).slice(0, 3)
          if (services.length > 0) {
            const pad = (visorW < 450) ? 8 : 10
            const innerX = visorX + pad
            const innerY = visorY + pad
            const innerW = visorW - pad * 2
            const innerH = visorH - pad * 2
            const gap = (visorW < 450) ? 8 : 12
            const cardW = (innerW - (services.length - 1) * gap) / services.length
            const cardH = innerH

            services.forEach((srv, idx) => {
              const cx = innerX + idx * (cardW + gap)
              const pStyle = getPlatformCyberStyle(srv.platform)

              ctx.shadowColor = th.glow
              ctx.shadowBlur = 16
              ctx.fillStyle = th.cardBg
              roundRect(ctx, cx, innerY, cardW, cardH, 20, true, true, pStyle.color, 2)
              ctx.shadowBlur = 0

              const emojiSize = Math.min(cardH * 0.28, 64)
              ctx.font = `${emojiSize}px sans-serif`
              ctx.textAlign = 'center'
              ctx.fillText(getPlatformIcon(srv.platform), cx + cardW / 2, innerY + cardH * 0.35)

              ctx.font = `bold ${Math.min(20, Math.max(12, cardW * 0.12))}px sans-serif`
              ctx.fillStyle = '#ffffff'
              ctx.fillText(srv.platform, cx + cardW / 2, innerY + cardH * 0.55)

              ctx.font = `bold ${Math.min(15, Math.max(10, cardW * 0.09))}px monospace`
              ctx.fillStyle = th.textAccent
              ctx.fillText(srv.planLabel || '1 Perfil', cx + cardW / 2, innerY + cardH * 0.72)

              const badgeW = Math.min(cardW - 16, 92)
              ctx.fillStyle = 'rgba(16, 185, 129, 0.25)'
              roundRect(ctx, cx + cardW / 2 - badgeW / 2, innerY + cardH * 0.82, badgeW, 24, 8, true, true, '#10b981', 1)
              ctx.fillStyle = '#34d399'
              ctx.font = 'bold 12px sans-serif'
              ctx.fillText('✓ INCLUIDO', cx + cardW / 2, innerY + cardH * 0.82 + 16)
            })
          } else {
            ctx.font = '90px sans-serif'
            ctx.textAlign = 'center'
            ctx.fillText('🍿 🎬 ✨', centerX, centerY + 20)
          }
        } else {
          // Streaming Individual: Emblema Holográfico de Marca perfectamente proporcionado
          const platStyle = getPlatformCyberStyle(selectedPlatform.value)

          const shieldSize = Math.min(visorH * 0.38, 120)
          const shieldY = centerY - visorH * 0.12

          ctx.shadowColor = th.glow
          ctx.shadowBlur = 28
          ctx.fillStyle = th.cardBg
          roundRect(ctx, centerX - shieldSize / 2, shieldY - shieldSize / 2, shieldSize, shieldSize, 28, true, true, platStyle.color, 2.5)
          ctx.shadowBlur = 0

          ctx.font = `${shieldSize * 0.55}px sans-serif`
          ctx.textAlign = 'center'
          ctx.fillText(platStyle.symbol, centerX, shieldY + shieldSize * 0.2)

          const titleSize = Math.min(48, Math.max(24, visorH * 0.16))
          ctx.font = `900 ${titleSize}px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif`
          ctx.fillStyle = '#ffffff'
          ctx.shadowColor = th.glow
          ctx.shadowBlur = 14
          ctx.fillText(platStyle.label, centerX, centerY + visorH * 0.2)
          ctx.shadowBlur = 0

          const subtagText = (currentPlanData.value.label || '1 PERFIL PRIVADO').toUpperCase() + ' · 4K ULTRA HD'
          const tagFontSize = Math.min(18, Math.max(12, visorH * 0.075))
          ctx.font = `bold ${tagFontSize}px monospace`
          const tagW = ctx.measureText(subtagText).width + 36
          const tagH = tagFontSize + 16
          ctx.fillStyle = 'rgba(10, 20, 45, 0.85)'
          roundRect(ctx, centerX - tagW / 2, centerY + visorH * 0.28, tagW, tagH, tagH / 2, true, true, th.border, 1.5)
          ctx.fillStyle = '#a5f3fc'
          ctx.fillText(subtagText, centerX, centerY + visorH * 0.28 + tagH * 0.68)
        }

        ctx.restore()
      }

      function drawCyberBadge(ctx, centerX, y, h, maxW = 860, isSmall = false) {
        ctx.save()
        const th = currentTheme.value
        const badgeText = headlineBadge.value.toUpperCase()
        ctx.font = `900 ${isSmall ? 18 : 24}px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif`
        const bW = Math.min(ctx.measureText(badgeText).width + (isSmall ? 36 : 52), maxW)
        const bX = centerX - bW / 2

        ctx.shadowColor = th.glow
        ctx.shadowBlur = 18

        const bGrad = ctx.createLinearGradient(bX, y, bX + bW, y)
        bGrad.addColorStop(0, th.badgeGrad[0])
        bGrad.addColorStop(1, th.badgeGrad[1])
        ctx.fillStyle = bGrad
        roundRect(ctx, bX, y, bW, h, h / 2, true, false)

        ctx.shadowBlur = 0
        ctx.textAlign = 'center'
        ctx.fillStyle = th.id === 'cyber' ? '#020617' : '#ffffff'
        ctx.fillText(badgeText, centerX, y + h / 2 + (isSmall ? 6 : 8))
        ctx.restore()
      }

      function drawCyberPriceCard(ctx, x, y, w, h, isSquare = false) {
        ctx.save()
        const th = currentTheme.value
        ctx.shadowColor = th.glow
        ctx.shadowBlur = 18

        const cGrad = ctx.createLinearGradient(x, y, x, y + h)
        cGrad.addColorStop(0, th.cardBg)
        cGrad.addColorStop(1, '#020617')
        ctx.fillStyle = cGrad
        roundRect(ctx, x, y, w, h, isSquare ? 18 : 24, true, true, th.cardBorder, 2)
        ctx.shadowBlur = 0

        const centerX = x + w / 2
        const hasOrig = displayOriginalPrice.value && Number(displayOriginalPrice.value) > Number(displayPriceNumeric.value)
        const guaranteeText = flyerProductType.value === 'physical'
          ? '🚚 Envío a Todo el País · Garantía Oficial 100%'
          : '⚡ Entrega Inmediata · Garantía Oficial 100%'

        if (hasOrig) {
          ctx.font = `bold ${isSquare ? 15 : 21}px sans-serif`
          ctx.fillStyle = '#94a3b8'
          const origText = `Antes: ${Number(displayOriginalPrice.value).toLocaleString()} ARS`
          const origW = ctx.measureText(origText).width
          const discountShift = calculatedDiscountPct.value ? (isSquare ? 40 : 52) : 0
          const textX = centerX - discountShift
          ctx.textAlign = 'center'
          ctx.fillText(origText, textX, y + (isSquare ? 24 : 36))

          ctx.strokeStyle = '#ef4444'
          ctx.lineWidth = 2.5
          ctx.beginPath()
          ctx.moveTo(textX - origW / 2, y + (isSquare ? 19 : 29))
          ctx.lineTo(textX + origW / 2, y + (isSquare ? 19 : 29))
          ctx.stroke()

          if (calculatedDiscountPct.value) {
            const badgeX = textX + origW / 2 + 10
            const badgeW = isSquare ? 68 : 90
            const badgeH = isSquare ? 22 : 30
            ctx.fillStyle = 'rgba(16, 185, 129, 0.25)'
            roundRect(ctx, badgeX, y + (isSquare ? 10 : 16), badgeW, badgeH, 8, true, true, '#10b981', 1.5)
            ctx.fillStyle = '#34d399'
            ctx.font = `900 ${isSquare ? 12 : 16}px monospace`
            ctx.textAlign = 'center'
            ctx.fillText(`-${calculatedDiscountPct.value}%`, badgeX + badgeW / 2, y + (isSquare ? 26 : 37))
          }

          // Precio Principal
          ctx.textAlign = 'center'
          ctx.font = `900 ${isSquare ? 46 : 74}px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif`
          ctx.fillStyle = '#ffffff'
          ctx.shadowColor = th.glow
          ctx.shadowBlur = 16
          ctx.fillText(`${displayPrice.value} ARS`, centerX, y + (isSquare ? 72 : 110))
          ctx.shadowBlur = 0

          ctx.font = `bold ${isSquare ? 12 : 17}px sans-serif`
          ctx.fillStyle = th.textAccent
          ctx.fillText(guaranteeText, centerX, y + (isSquare ? 96 : 148))
        } else {
          ctx.font = `bold ${isSquare ? 15 : 21}px sans-serif`
          ctx.fillStyle = '#94a3b8'
          ctx.textAlign = 'center'
          ctx.fillText('PRECIO EXCLUSIVO', centerX, y + (isSquare ? 24 : 36))

          ctx.font = `900 ${isSquare ? 48 : 76}px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif`
          ctx.fillStyle = '#ffffff'
          ctx.shadowColor = th.glow
          ctx.shadowBlur = 16
          ctx.fillText(`${displayPrice.value} ARS`, centerX, y + (isSquare ? 72 : 112))
          ctx.shadowBlur = 0

          ctx.font = `bold ${isSquare ? 12 : 17}px sans-serif`
          ctx.fillStyle = th.textAccent
          ctx.fillText(guaranteeText, centerX, y + (isSquare ? 96 : 148))
        }
        ctx.restore()
      }

      function drawCyberBullets(ctx, startX, startY, lineSpacing, maxBullets = 3, isSmall = false) {
        ctx.save()
        const th = currentTheme.value
        const bullets = [bullet1.value, bullet2.value, bullet3.value].slice(0, maxBullets)
        ctx.textAlign = 'left'
        bullets.forEach((b, idx) => {
          const by = startY + idx * lineSpacing
          const radius = isSmall ? 9 : 13

          ctx.shadowColor = th.glow
          ctx.shadowBlur = 12
          ctx.fillStyle = th.bulletColor
          ctx.beginPath()
          ctx.arc(startX, by - (isSmall ? 4 : 6), radius, 0, Math.PI * 2)
          ctx.fill()
          ctx.shadowBlur = 0

          ctx.fillStyle = (th.id === 'cinema') ? '#ffffff' : '#020617'
          ctx.font = `bold ${isSmall ? 13 : 17}px sans-serif`
          ctx.textAlign = 'center'
          ctx.fillText('✓', startX, by - (isSmall ? 0 : 1))
          ctx.textAlign = 'left'

          ctx.font = `600 ${isSmall ? 19 : 26}px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif`
          ctx.fillStyle = '#f8fafc'
          ctx.shadowColor = 'rgba(0, 0, 0, 0.7)'
          ctx.shadowBlur = 6
          const maxLen = isSmall ? 36 : 44
          ctx.fillText(b.length > maxLen ? b.slice(0, maxLen - 2) + '...' : b, startX + radius + 14, by)
          ctx.shadowBlur = 0
        })
        ctx.restore()
      }

      // ========================================================
      // 1. MOTOR DE DISEÑO OFICIAL UNIFICADO CON CANVA ENGINE PRO
      //    (CYBER PRO, CINEMA DARK, EMERALD DEAL, DEEP BLUE)
      // ========================================================
      const tplUrl = getCyberTemplateUrl(format)
      const cyberImg = await loadFlyerImageElement(tplUrl)
      const th = currentTheme.value

      if (cyberImg) {
        ctx.save()
        if (th.filter && th.filter !== 'none') {
          ctx.filter = th.filter
        }
        ctx.drawImage(cyberImg, 0, 0, width, height)
        ctx.restore()
      } else {
        drawCyberBackground(ctx, width, height, th)
      }

        if (format === 'banner') {
          // ================= BANNER HORIZONTAL (1920 x 1080) =================
          // Visor izquierdo: X=150, Y=260, W=520, H=560
          await drawVisorHeroContent(ctx, 150, 260, 520, 560)

          const rightX = 740
          const rightW = 1040

          drawCyberBadge(ctx, rightX + rightW / 2, 140, 50, rightW, false)

          ctx.textAlign = 'left'
          ctx.font = '900 46px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
          ctx.fillStyle = '#ffffff'
          ctx.shadowColor = 'rgba(0, 0, 0, 0.8)'
          ctx.shadowBlur = 10
          const titleLines = wrapText(displayTitle.value, 32).slice(0, 2)
          titleLines.forEach((tl, i) => ctx.fillText(tl, rightX, 230 + i * 50))
          ctx.shadowBlur = 0
          const subY = 230 + titleLines.length * 50 + 6

          ctx.font = 'bold 23px sans-serif'
          ctx.fillStyle = th.textAccent
          ctx.fillText(displaySubtitle.value.length > 56 ? displaySubtitle.value.slice(0, 54) + '...' : displaySubtitle.value, rightX, subY)

          const pCardY = subY + 20
          drawCyberPriceCard(ctx, rightX, pCardY, rightW, 180, false)

          drawCyberBullets(ctx, rightX + 20, pCardY + 180 + 45, 68, 3, false)

        } else if (format === 'story') {
          // ================= STORY (1080 x 1920, 9:16) =================
          // Visor superior exacto: X=198, Y=261, W=684, H=544
          await drawVisorHeroContent(ctx, 198, 261, 684, 544)

          // Contenido estrictamente dentro del cuadrante (Y=830..1750)
          drawCyberBadge(ctx, width / 2, 840, 56, 860, false)

          ctx.textAlign = 'center'
          ctx.font = '900 46px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
          ctx.fillStyle = '#ffffff'
          ctx.shadowColor = 'rgba(0, 0, 0, 0.8)'
          ctx.shadowBlur = 12
          const titleLines = wrapText(displayTitle.value, 28).slice(0, 2)
          titleLines.forEach((tl, i) => ctx.fillText(tl, width / 2, 940 + i * 50))
          ctx.shadowBlur = 0
          const subY = 940 + titleLines.length * 50 + 8

          ctx.font = 'bold 24px sans-serif'
          ctx.fillStyle = th.textAccent
          ctx.fillText(displaySubtitle.value.length > 48 ? displaySubtitle.value.slice(0, 46) + '...' : displaySubtitle.value, width / 2, subY)

          const pCardY = subY + 28
          drawCyberPriceCard(ctx, 110, pCardY, 860, 195, false)

          drawCyberBullets(ctx, 145, pCardY + 195 + 45, 68, 3, false)

        } else if (format === 'feed') {
          // ================= FEED PORTRAIT (1080 x 1350, 4:5) =================
          // Visor superior exacto: X=218, Y=194, W=645, H=385
          await drawVisorHeroContent(ctx, 218, 194, 645, 385)

          // Contenido estrictamente dentro del cuadrante (Y=600..1220)
          drawCyberBadge(ctx, width / 2, 600, 46, 840, false)

          ctx.textAlign = 'center'
          ctx.font = '900 38px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
          ctx.fillStyle = '#ffffff'
          ctx.shadowColor = 'rgba(0, 0, 0, 0.8)'
          ctx.shadowBlur = 10
          const titleLines = wrapText(displayTitle.value, 30).slice(0, 2)
          titleLines.forEach((tl, i) => ctx.fillText(tl, width / 2, 675 + i * 42))
          ctx.shadowBlur = 0
          const subY = 675 + titleLines.length * 42 + 6

          ctx.font = 'bold 21px sans-serif'
          ctx.fillStyle = th.textAccent
          ctx.fillText(displaySubtitle.value.length > 48 ? displaySubtitle.value.slice(0, 46) + '...' : displaySubtitle.value, width / 2, subY)

          const pCardY = subY + 20
          drawCyberPriceCard(ctx, 120, pCardY, 840, 170, false)

          drawCyberBullets(ctx, 145, pCardY + 170 + 40, 58, 3, false)

        } else {
          // ================= SQUARE (1080 x 1080, 1:1) =================
          // Visor superior exacto: X=353, Y=148, W=374, H=376
          await drawVisorHeroContent(ctx, 353, 148, 374, 376)

          // Contenido estrictamente dentro del cuadrante (Y=545..940)
          drawCyberBadge(ctx, width / 2, 545, 42, 800, false)

          ctx.textAlign = 'center'
          ctx.font = '900 32px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
          ctx.fillStyle = '#ffffff'
          ctx.shadowColor = 'rgba(0, 0, 0, 0.8)'
          ctx.shadowBlur = 10
          const titleLines = wrapText(displayTitle.value, 32).slice(0, 2)
          titleLines.forEach((tl, i) => ctx.fillText(tl, width / 2, 622 + i * 36))
          ctx.shadowBlur = 0
          const subY = 622 + titleLines.length * 36 + 4

          ctx.font = 'bold 18px sans-serif'
          ctx.fillStyle = th.textAccent
          ctx.fillText(displaySubtitle.value.length > 48 ? displaySubtitle.value.slice(0, 46) + '...' : displaySubtitle.value, width / 2, subY)

          const pCardY = subY + 16
          drawCyberPriceCard(ctx, 130, pCardY, 820, 130, true)

          const bulletsY = pCardY + 130 + 20
          const numBullets = titleLines.length > 1 ? 2 : 3
          drawCyberBullets(ctx, 150, bulletsY, 34, numBullets, true)
        }

        canvas.toBlob((blob) => {
          if (!blob) {
            reject(new Error('Fallo al exportar blob de canvas'))
            return
          }
          resolve(blob)
        }, 'image/png')
      } catch (e) {
        reject(e)
      }
    })
  }

const triggerFileDownload = (fileUrl, filename) => {
  const link = document.createElement('a')
  link.download = filename
  link.href = fileUrl
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

// Exportador seguro con timeout para html-to-image
const exportWithHtmlToImage = (node) => {
  return new Promise(async (resolve, reject) => {
    const timer = setTimeout(() => {
      reject(new Error('Timeout de renderizado html-to-image'))
    }, 6000)

    try {
      const dataUrl = await toPng(node, {
        pixelRatio: 3,
        cacheBust: true,
        backgroundColor: null
      })
      clearTimeout(timer)
      resolve(dataUrl)
    } catch (err) {
      clearTimeout(timer)
      reject(err)
    }
  })
}

// ==========================================
// DESCARGA DE FLYER INFALIBLE CON DOBLE MOTOR
// Prioriza Canvas 2D Studio (HD Nativo 1080p sin recortes)
// ==========================================
const downloadFlyerImage = async () => {
  if (!flyerPreviewRef.value) return
  isExporting.value = true
  toast.add(`Generando flyer en formato ${flyerFormatLabels[flyerFormat.value]}...`, 'info')

  const safeName = displayTitle.value.toLowerCase().replace(/[^a-z0-9]/g, '_').slice(0, 24) || 'flyer'
  const filename = `flyer_${safeName}_${flyerFormat.value}.png`

  try {
    // 1. Motor principal de ultra alta fidelidad: Canvas 2D Nativo a 1080p sin recortes
    let exported = false
    try {
      const blob = await renderCanvasFlyerBlob()
      if (blob) {
        const url = URL.createObjectURL(blob)
        triggerFileDownload(url, filename)
        URL.revokeObjectURL(url)
        toast.add(`¡Flyer descargado con éxito en formato ${flyerFormatLabels[flyerFormat.value]}!`, 'success')
        exported = true
      }
    } catch (canvasErr) {
      console.warn('Fallo renderizado Canvas 2D, probando motor DOM html-to-image:', canvasErr)
    }

    if (exported) return

    // 2. Motor de respaldo DOM (html-to-image)
    const previewEl = flyerPreviewRef.value
    await nextTick()
    const dataUrl = await exportWithHtmlToImage(previewEl)
    if (dataUrl) {
      triggerFileDownload(dataUrl, filename)
      toast.add(`¡Flyer descargado con éxito en formato ${flyerFormatLabels[flyerFormat.value]}!`, 'success')
      return
    }

    throw new Error('No se pudo procesar la imagen')
  } catch (err) {
    console.error('Error generando flyer:', err)
    toast.add('Ocurrió un error al descargar el flyer. Por favor, reintenta.', 'error')
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
          <!-- SELECTOR DE TIPO DE FLYER (Solo para revendedores/admins, simplificado para clientes en productos físicos) -->
          <div v-if="!clientOnlyPhysical" class="space-y-1.5">
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

          <!-- ENCABEZADO SIMPLIFICADO PARA CLIENTES (SOLO PRODUCTOS FÍSICOS) -->
          <div v-else class="flex items-center justify-between p-3 bg-[#020617] rounded-2xl border border-amber-500/30">
            <div class="flex items-center gap-2.5">
              <span class="w-8 h-8 rounded-xl bg-amber-500/20 text-amber-300 flex items-center justify-center text-lg border border-amber-500/30">
                📦
              </span>
              <div>
                <span class="font-black text-amber-300 text-xs block">Flyer Oficial de Producto Físico</span>
                <span class="text-[10px] text-slate-400">Personaliza y descarga el flyer oficial para compartir en tus historias o estados</span>
              </div>
            </div>
            <span class="text-[9px] bg-amber-400 text-slate-950 font-black px-2.5 py-0.5 rounded-full uppercase tracking-wider">
              Catálogo Oficial
            </span>
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
              <div class="flex items-center justify-between mb-1">
                <label class="block font-bold uppercase text-slate-400 text-xs">Badge de Impacto</label>
                <span 
                  v-if="calculatedDiscountPct" 
                  class="text-[10px] font-mono font-bold text-amber-300 bg-amber-500/20 px-1.5 py-0.5 rounded border border-amber-500/30 flex items-center gap-1 shadow-sm"
                  title="Porcentaje calculado a partir de precio promo y normal"
                >
                  <span>🔥</span> -{{ calculatedDiscountPct }}% OFF
                </span>
              </div>
              <input 
                v-model="headlineBadge" 
                list="badgesList" 
                type="text" 
                class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2 text-amber-300 font-bold outline-none focus:border-amber-400 text-sm"
                placeholder="🔥 25% OFF - RECOMENDADO"
              >
              <datalist id="badgesList">
                <option v-for="b in suggestedBadges" :key="b" :value="b" />
              </datalist>

              <!-- Atajos rápidos para el Badge de Impacto -->
              <div v-if="calculatedDiscountPct" class="mt-1.5 flex flex-wrap gap-1">
                <button 
                  type="button"
                  @click="applyCalculatedBadge('recomendado')"
                  class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 border border-amber-500/30 transition-colors"
                >
                  🔥 {{ calculatedDiscountPct }}% OFF
                </button>
                <button 
                  type="button"
                  @click="applyCalculatedBadge('descuento')"
                  class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 transition-colors"
                >
                  ⚡ {{ calculatedDiscountPct }}% DESC
                </button>
              </div>
            </div>

            <div>
              <div class="flex items-center justify-between mb-1">
                <label class="block font-bold uppercase text-slate-400 text-xs">
                  Precio Promo (ARS)
                </label>
                <span v-if="calculatedDiscountPct" class="text-[10px] font-mono font-bold text-emerald-400 bg-emerald-500/10 px-1.5 py-0.5 rounded border border-emerald-500/20">
                  -{{ calculatedDiscountPct }}% OFF
                </span>
              </div>
              <input 
                v-model="customPrice" 
                type="number" 
                :placeholder="'$' + displayPrice" 
                class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2 text-emerald-400 font-mono font-bold outline-none focus:border-emerald-400 text-sm"
              >
            </div>

            <div>
              <div class="flex items-center justify-between mb-1">
                <label class="block font-bold uppercase text-slate-400 text-xs">
                  Precio Normal (Tachado)
                </label>
                <span v-if="displayOriginalPrice && displayPriceNumeric && Number(displayOriginalPrice) > Number(displayPriceNumeric)" class="text-[10px] font-mono text-slate-400 truncate max-w-[110px]" :title="'Ahorro: $' + (Number(displayOriginalPrice) - Number(displayPriceNumeric)).toLocaleString()">
                  Ahorro: ${{ (Number(displayOriginalPrice) - Number(displayPriceNumeric)).toLocaleString() }}
                </span>
              </div>
              <input 
                v-model="customOriginalPrice" 
                type="number" 
                :placeholder="displayOriginalPrice ? '$' + Number(displayOriginalPrice).toLocaleString() : 'Opcional (Tachado)'" 
                class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2 text-slate-300 font-mono outline-none focus:border-slate-600 text-sm"
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

            <!-- Dominio Principal Oficial Único para Flyers -->
            <div class="bg-slate-950 p-3 rounded-xl border border-slate-800 flex items-center justify-between">
              <div class="flex items-center gap-2.5">
                <span class="text-base">🌐</span>
                <div class="min-w-0">
                  <div class="font-mono font-black text-xs text-white">streamepro.me</div>
                  <div class="text-[9px] text-slate-400">Dominio Principal Oficial en el Flyer</div>
                </div>
              </div>
              <span class="text-[10px] font-mono font-bold text-emerald-400 bg-emerald-500/10 px-2.5 py-1 rounded-full border border-emerald-500/20">
                ✓ Dominio Oficial
              </span>
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
                @click="flyerTheme = 'cyber'"
                :class="['p-2 rounded-xl border text-center font-bold text-[11px] transition-all', flyerTheme === 'cyber' ? 'bg-cyan-950/70 border-cyan-400 text-cyan-300 shadow-lg shadow-cyan-950/50' : 'bg-slate-950 border-slate-800 text-slate-400 hover:text-white']"
              >
                🤖 Cyber Oficial
              </button>
              <button 
                @click="flyerTheme = 'cinema'"
                :class="['p-2 rounded-xl border text-center font-bold text-[11px] transition-all', flyerTheme === 'cinema' ? 'bg-red-950/50 border-red-500 text-red-300 shadow' : 'bg-slate-950 border-slate-800 text-slate-400 hover:text-white']"
              >
                🎬 Cinema Dark
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

          <!-- CONTENEDOR DEL FLYER (ESTRUCTURA EXACTA CAPTURABLE SIN RECORTES) -->
          <div 
            ref="flyerPreviewRef" 
            :class="[
              'w-full rounded-3xl flex flex-col justify-between relative overflow-hidden shadow-2xl border transition-all text-white select-none p-0',
              flyerFormat === 'story' ? 'max-w-[320px] aspect-[9/16]' :
              flyerFormat === 'feed' ? 'max-w-[330px] aspect-[4/5]' :
              flyerFormat === 'square' ? 'max-w-[340px] aspect-square' :
              'max-w-[480px] aspect-[16/9]',
              currentTheme.cssContainerBorder
            ]"
          >
            <!-- Capa de Plantilla de Fondo con Filtro Temático (Cinema, Emerald, Blue, Cyber) -->
            <div 
              class="absolute inset-0 pointer-events-none transition-all duration-300 z-0"
              :style="{
                backgroundImage: `url(${getCyberTemplateUrl(flyerFormat)})`,
                backgroundSize: '100% 100%',
                backgroundRepeat: 'no-repeat',
                backgroundPosition: 'center',
                filter: currentTheme.filter
              }"
            />

            <!-- Capa de Contenido Elevada Oficial (Sin Filtro, Máxima Fidelidad) -->
            <div class="relative w-full h-full text-white pointer-events-none select-none z-10">
              
              <!-- 1.1 STORY (9:16) -->
              <div v-if="flyerFormat === 'story'" class="relative w-full h-full">
                <!-- Visor Window -->
                <div class="absolute top-[13.6%] left-[18.3%] w-[63.3%] h-[28.3%] flex items-center justify-center p-1.5 z-10">
                  <div v-if="flyerProductType === 'physical'" :class="['w-full h-full bg-white rounded-2xl flex items-center justify-center p-2.5 shadow-lg border overflow-hidden relative', currentTheme.cssBorder]">
                    <img :src="productImageCORS || selectedPhysicalProduct?.image" :alt="displayTitle" class="w-full h-full object-contain filter drop-shadow-md">
                  </div>
                  <div v-else-if="flyerProductType === 'combo'" class="w-full h-full flex items-center justify-center gap-1.5 p-0.5">
                    <div v-for="(srv, idx) in (selectedPackage?.services?.slice(0, 3) || [])" :key="idx" :class="['flex-1 h-full rounded-xl bg-slate-950/90 border flex flex-col items-center justify-center p-1 shadow-md', currentTheme.cssBorder]">
                      <span class="text-xl sm:text-2xl">{{ getPlatformIcon(srv.platform) }}</span>
                      <span class="text-[7px] font-black text-white truncate max-w-full mt-0.5">{{ srv.platform }}</span>
                      <span :class="['text-[6px] truncate font-mono', currentTheme.cssText]">{{ srv.planLabel }}</span>
                      <span class="text-[5px] bg-emerald-500/20 text-emerald-300 font-bold px-1 rounded mt-0.5">✓ INCLUIDO</span>
                    </div>
                  </div>
                  <div v-else class="flex flex-col items-center justify-center text-center">
                    <div :class="['w-12 h-12 rounded-2xl bg-slate-950/90 border flex items-center justify-center shadow-lg', currentTheme.cssBorder]">
                      <span class="text-2xl drop-shadow">{{ currentPlatformIcon }}</span>
                    </div>
                    <span class="font-black text-white text-xs sm:text-sm uppercase tracking-wider mt-1 drop-shadow">{{ selectedPlatform }}</span>
                    <span :class="['text-[7px] font-mono font-bold border px-2 py-0.5 rounded-full mt-1', currentTheme.cssTagBorder]">{{ currentPlanData?.label || '1 PERFIL' }} · 4K ULTRA HD</span>
                  </div>
                </div>

                <!-- Contenido Inferior (Dentro del cuadrante) -->
                <div class="absolute top-[43.5%] bottom-[8%] left-[9%] right-[9%] flex flex-col justify-between items-center text-center z-10 py-1">
                  <div :class="['font-black px-3 py-0.5 rounded-full shadow-lg text-[9px] uppercase tracking-wider', currentTheme.cssBadge]">
                    {{ headlineBadge }}
                  </div>
                  <div class="space-y-0.5 w-full">
                    <h2 class="font-black text-xs sm:text-sm tracking-tight leading-tight line-clamp-2 text-white drop-shadow">{{ displayTitle }}</h2>
                    <p :class="['font-bold text-[9px] font-mono truncate', currentTheme.cssText]">{{ displaySubtitle }}</p>
                  </div>
                  <div :class="['w-full rounded-2xl py-1.5 px-3', currentTheme.cssPriceBox]">
                    <div v-if="displayOriginalPrice" class="flex items-center justify-center gap-1 mb-0.5">
                      <span class="text-slate-400 line-through text-[9px]">Antes: $${Number(displayOriginalPrice).toLocaleString()}</span>
                      <span v-if="calculatedDiscountPct" class="bg-emerald-500/20 text-emerald-300 font-black text-[8px] px-1 rounded">-${calculatedDiscountPct}%</span>
                    </div>
                    <div v-else class="text-[8px] uppercase font-bold text-slate-400 tracking-wider">PRECIO EXCLUSIVO</div>
                    <div class="flex items-baseline justify-center gap-0.5">
                      <span :class="['text-xs font-black', currentTheme.cssCurrency]">$</span>
                      <span class="font-black tracking-tight text-white font-mono text-xl sm:text-2xl">{{ displayPrice }}</span>
                      <span class="text-[8px] font-bold text-slate-400 font-mono">ARS</span>
                    </div>
                    <div :class="['text-[7px] font-bold truncate', currentTheme.cssText]">
                      {{ flyerProductType === 'physical' ? '🚚 Envío a Todo el País · Garantía Oficial 100%' : '⚡ Entrega Inmediata · Garantía Oficial 100%' }}
                    </div>
                  </div>
                  <div class="w-full text-left space-y-1 text-[8px] sm:text-[9px] font-semibold text-slate-200 px-1">
                    <div class="flex items-center gap-1.5 truncate"><span :class="['w-3.5 h-3.5 rounded-full flex items-center justify-center text-[8px] font-bold shrink-0', currentTheme.cssCheck]">✓</span><span class="truncate">{{ bullet1 }}</span></div>
                    <div class="flex items-center gap-1.5 truncate"><span :class="['w-3.5 h-3.5 rounded-full flex items-center justify-center text-[8px] font-bold shrink-0', currentTheme.cssCheck]">✓</span><span class="truncate">{{ bullet2 }}</span></div>
                    <div class="flex items-center gap-1.5 truncate"><span :class="['w-3.5 h-3.5 rounded-full flex items-center justify-center text-[8px] font-bold shrink-0', currentTheme.cssCheck]">✓</span><span class="truncate">{{ bullet3 }}</span></div>
                  </div>
                </div>
              </div>

              <!-- 1.2 FEED (4:5) -->
              <div v-else-if="flyerFormat === 'feed'" class="relative w-full h-full">
                <!-- Visor Window -->
                <div class="absolute top-[14.4%] left-[20.2%] w-[59.7%] h-[28.5%] flex items-center justify-center p-1.5 z-10">
                  <div v-if="flyerProductType === 'physical'" :class="['w-full h-full bg-white rounded-xl flex items-center justify-center p-2 shadow-lg border overflow-hidden relative', currentTheme.cssBorder]">
                    <img :src="productImageCORS || selectedPhysicalProduct?.image" :alt="displayTitle" class="w-full h-full object-contain filter drop-shadow-md">
                  </div>
                  <div v-else-if="flyerProductType === 'combo'" class="w-full h-full flex items-center justify-center gap-1 p-0.5">
                    <div v-for="(srv, idx) in (selectedPackage?.services?.slice(0, 3) || [])" :key="idx" :class="['flex-1 h-full rounded-lg bg-slate-950/90 border flex flex-col items-center justify-center p-0.5', currentTheme.cssBorder]">
                      <span class="text-base">{{ getPlatformIcon(srv.platform) }}</span>
                      <span class="text-[6px] font-black text-white truncate max-w-full">{{ srv.platform }}</span>
                      <span :class="['text-[5px] truncate font-mono', currentTheme.cssText]">{{ srv.planLabel }}</span>
                    </div>
                  </div>
                  <div v-else class="flex flex-col items-center justify-center text-center">
                    <div :class="['w-10 h-10 rounded-xl bg-slate-950/90 border flex items-center justify-center shadow', currentTheme.cssBorder]">
                      <span class="text-xl drop-shadow">{{ currentPlatformIcon }}</span>
                    </div>
                    <span class="font-black text-white text-xs uppercase tracking-wider mt-0.5">{{ selectedPlatform }}</span>
                    <span :class="['text-[6px] font-mono mt-0.5', currentTheme.cssText]">4K ULTRA HD · {{ currentPlanData?.label || '1 PERFIL' }}</span>
                  </div>
                </div>

                <!-- Contenido Inferior (Dentro del cuadrante) -->
                <div class="absolute top-[44.5%] bottom-[8.5%] left-[10%] right-[10%] flex flex-col justify-between items-center text-center z-10 py-1">
                  <div :class="['font-black px-2.5 py-0.5 rounded-full shadow text-[8px] uppercase tracking-wider', currentTheme.cssBadge]">
                    {{ headlineBadge }}
                  </div>
                  <div class="space-y-0.5 w-full">
                    <h2 class="font-black text-xs tracking-tight leading-tight line-clamp-2 text-white">{{ displayTitle }}</h2>
                    <p :class="['font-bold text-[8px] font-mono truncate', currentTheme.cssText]">{{ displaySubtitle }}</p>
                  </div>
                  <div :class="['w-full rounded-xl py-1 px-2.5 shadow', currentTheme.cssPriceBox]">
                    <div v-if="displayOriginalPrice" class="flex items-center justify-center gap-1 mb-0.5">
                      <span class="text-slate-400 line-through text-[8px]">Antes: $${Number(displayOriginalPrice).toLocaleString()}</span>
                      <span v-if="calculatedDiscountPct" class="bg-emerald-500/20 text-emerald-300 font-black text-[7px] px-1 rounded">-${calculatedDiscountPct}%</span>
                    </div>
                    <div v-else class="text-[7px] uppercase font-bold text-slate-400">PRECIO EXCLUSIVO</div>
                    <div class="flex items-baseline justify-center gap-0.5">
                      <span :class="['text-[10px] font-black', currentTheme.cssCurrency]">$</span>
                      <span class="font-black tracking-tight text-white font-mono text-base sm:text-lg">{{ displayPrice }}</span>
                      <span class="text-[7px] font-bold text-slate-400 font-mono">ARS</span>
                    </div>
                    <div :class="['text-[6px] font-bold truncate', currentTheme.cssText]">
                      {{ flyerProductType === 'physical' ? '🚚 Envío a Todo el País · Garantía Oficial 100%' : '⚡ Entrega Inmediata · Garantía Oficial 100%' }}
                    </div>
                  </div>
                  <div class="w-full text-left space-y-0.5 text-[8px] font-medium text-slate-200 px-1">
                    <div class="flex items-center gap-1 truncate"><span :class="['font-bold', currentTheme.cssCheckText]">✓</span><span class="truncate">{{ bullet1 }}</span></div>
                    <div class="flex items-center gap-1 truncate"><span :class="['font-bold', currentTheme.cssCheckText]">✓</span><span class="truncate">{{ bullet2 }}</span></div>
                    <div class="flex items-center gap-1 truncate"><span :class="['font-bold', currentTheme.cssCheckText]">✓</span><span class="truncate">{{ bullet3 }}</span></div>
                  </div>
                </div>
              </div>

              <!-- 1.3 SQUARE (1:1) -->
              <div v-else-if="flyerFormat === 'square'" class="relative w-full h-full">
                <!-- Visor Window -->
                <div class="absolute top-[13.7%] left-[32.7%] w-[34.6%] h-[34.8%] flex items-center justify-center p-1 z-10">
                  <div v-if="flyerProductType === 'physical'" :class="['w-full h-full bg-white rounded-xl flex items-center justify-center p-2 shadow-lg border overflow-hidden relative', currentTheme.cssBorder]">
                    <img :src="productImageCORS || selectedPhysicalProduct?.image" :alt="displayTitle" class="w-full h-full object-contain filter drop-shadow-md">
                  </div>
                  <div v-else-if="flyerProductType === 'combo'" class="w-full h-full flex items-center justify-center gap-1 p-0.5">
                    <div v-for="(srv, idx) in (selectedPackage?.services?.slice(0, 3) || [])" :key="idx" :class="['flex-1 h-full rounded-lg bg-slate-950/90 border flex flex-col items-center justify-center p-0.5', currentTheme.cssBorder]">
                      <span class="text-sm">{{ getPlatformIcon(srv.platform) }}</span>
                      <span class="text-[6px] font-black text-white truncate max-w-full">{{ srv.platform }}</span>
                    </div>
                  </div>
                  <div v-else class="flex flex-col items-center justify-center text-center">
                    <div :class="['w-10 h-10 rounded-xl bg-slate-950/90 border flex items-center justify-center shadow', currentTheme.cssBorder]">
                      <span class="text-xl drop-shadow">{{ currentPlatformIcon }}</span>
                    </div>
                    <span class="font-black text-white text-[10px] uppercase tracking-wider mt-0.5">{{ selectedPlatform }}</span>
                    <span :class="['text-[6px] font-mono mt-0.5', currentTheme.cssText]">4K ULTRA HD</span>
                  </div>
                </div>

                <!-- Contenido Inferior (Dentro del cuadrante Y=545..950) -->
                <div class="absolute top-[51.0%] bottom-[9.5%] left-[10%] right-[10%] flex flex-col justify-between items-center text-center z-10 py-1">
                  <div :class="['font-black px-2.5 py-0.5 rounded-full shadow text-[8px] uppercase tracking-wider', currentTheme.cssBadge]">
                    {{ headlineBadge }}
                  </div>
                  <div class="space-y-0.5 w-full">
                    <h2 class="font-black text-xs tracking-tight leading-tight line-clamp-2 text-white">{{ displayTitle }}</h2>
                    <p :class="['font-bold text-[8px] font-mono truncate', currentTheme.cssText]">{{ displaySubtitle }}</p>
                  </div>
                  <div :class="['w-full rounded-xl py-1 px-2.5 shadow', currentTheme.cssPriceBox]">
                    <div v-if="displayOriginalPrice" class="flex items-center justify-center gap-1 mb-0.5">
                      <span class="text-slate-400 line-through text-[7px]">Antes: $${Number(displayOriginalPrice).toLocaleString()}</span>
                      <span v-if="calculatedDiscountPct" class="bg-emerald-500/20 text-emerald-300 font-black text-[6px] px-0.5 rounded">-${calculatedDiscountPct}%</span>
                    </div>
                    <div v-else class="text-[6px] uppercase font-bold text-slate-400">PRECIO EXCLUSIVO</div>
                    <div class="flex items-baseline justify-center gap-0.5">
                      <span :class="['text-[9px] font-black', currentTheme.cssCurrency]">$</span>
                      <span class="font-black tracking-tight text-white font-mono text-sm sm:text-base">{{ displayPrice }}</span>
                      <span class="text-[6px] font-bold text-slate-400 font-mono">ARS</span>
                    </div>
                    <div :class="['text-[6px] font-bold truncate', currentTheme.cssText]">
                      {{ flyerProductType === 'physical' ? '🚚 Envío a Todo el País · Garantía Oficial 100%' : '⚡ Entrega Inmediata · Garantía Oficial 100%' }}
                    </div>
                  </div>
                  <div class="w-full text-left space-y-0.5 text-[7.5px] font-medium text-slate-200 px-1">
                    <div class="flex items-center gap-1 truncate"><span :class="['font-bold', currentTheme.cssCheckText]">✓</span><span class="truncate">{{ bullet1 }}</span></div>
                    <div class="flex items-center gap-1 truncate"><span :class="['font-bold', currentTheme.cssCheckText]">✓</span><span class="truncate">{{ bullet2 }}</span></div>
                    <div class="flex items-center gap-1 truncate"><span :class="['font-bold', currentTheme.cssCheckText]">✓</span><span class="truncate">{{ bullet3 }}</span></div>
                  </div>
                </div>
              </div>

              <!-- 1.4 BANNER (16:9) -->
              <div v-else class="relative w-full h-full">
                <!-- Visor Window -->
                <div class="absolute top-[24.1%] left-[7.8%] w-[27.1%] h-[51.9%] flex items-center justify-center p-1.5 z-10">
                  <div v-if="flyerProductType === 'physical'" :class="['w-full h-full bg-white rounded-2xl flex items-center justify-center p-2.5 shadow-lg border overflow-hidden relative', currentTheme.cssBorder]">
                    <img :src="productImageCORS || selectedPhysicalProduct?.image" :alt="displayTitle" class="w-full h-full object-contain filter drop-shadow-md">
                  </div>
                  <div v-else-if="flyerProductType === 'combo'" class="grid grid-cols-2 gap-1 w-full h-full p-0.5">
                    <div v-for="(srv, idx) in (selectedPackage?.services?.slice(0, 4) || [])" :key="idx" :class="['rounded bg-slate-950/90 border flex flex-col items-center justify-center p-0.5', currentTheme.cssBorder]">
                      <span class="text-sm">{{ getPlatformIcon(srv.platform) }}</span>
                      <span class="text-[6px] text-white font-bold truncate max-w-full">{{ srv.platform }}</span>
                    </div>
                  </div>
                  <div v-else class="flex flex-col items-center justify-center text-center">
                    <div :class="['w-12 h-12 rounded-2xl bg-slate-950/90 border flex items-center justify-center shadow', currentTheme.cssBorder]">
                      <span class="text-2xl drop-shadow">{{ currentPlatformIcon }}</span>
                    </div>
                    <span class="font-black text-white text-[10px] uppercase mt-1">{{ selectedPlatform }}</span>
                    <span :class="['text-[6px] font-mono mt-0.5', currentTheme.cssText]">4K ULTRA HD</span>
                  </div>
                </div>

                <!-- Contenido Derecha (Dentro del cuadrante) -->
                <div class="absolute top-[13%] bottom-[9%] left-[38.5%] right-[6%] flex flex-col justify-between z-10 py-1 text-left">
                  <div :class="['inline-block text-[7px] font-black px-2 py-0.5 rounded-full uppercase self-start', currentTheme.cssBadge]">
                    {{ headlineBadge }}
                  </div>
                  <div class="space-y-0.2">
                    <h2 class="text-xs font-black text-white line-clamp-1 leading-tight">{{ displayTitle }}</h2>
                    <div :class="['text-[7px] font-bold font-mono truncate', currentTheme.cssText]">{{ displaySubtitle }}</div>
                  </div>
                  <div :class="['w-full rounded-xl py-1 px-3', currentTheme.cssPriceBox]">
                    <div class="flex items-baseline gap-1.5">
                      <span v-if="displayOriginalPrice" class="text-[8px] text-slate-400 line-through">$${Number(displayOriginalPrice).toLocaleString()}</span>
                      <span v-if="calculatedDiscountPct" class="text-[6px] bg-emerald-500/20 text-emerald-300 font-black px-1 rounded">-${calculatedDiscountPct}%</span>
                      <div class="flex items-baseline gap-0.5">
                        <span :class="['font-bold text-[9px]', currentTheme.cssCurrency]">$</span>
                        <span class="font-black text-xs text-white font-mono">{{ displayPrice }}</span>
                        <span class="text-[6px] font-bold text-slate-400 font-mono">ARS</span>
                      </div>
                    </div>
                    <div :class="['text-[6px] font-bold truncate', currentTheme.cssText]">
                      {{ flyerProductType === 'physical' ? '🚚 Envío a Todo el País · Garantía Oficial 100%' : '⚡ Entrega Inmediata · Garantía Oficial 100%' }}
                    </div>
                  </div>
                  <div class="space-y-0.5 text-[8px] text-slate-200">
                    <div class="truncate"><span :class="['font-bold', currentTheme.cssCheckText]">✓</span> {{ bullet1 }}</div>
                    <div class="truncate"><span :class="['font-bold', currentTheme.cssCheckText]">✓</span> {{ bullet2 }}</div>
                    <div class="truncate"><span :class="['font-bold', currentTheme.cssCheckText]">✓</span> {{ bullet3 }}</div>
                  </div>
                </div>
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

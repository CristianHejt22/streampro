<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import { toPng } from 'html-to-image'
import { generateAttractiveShareCopy } from '../utils/marketing'
import { useAdminStore } from '../store/admin'
import { useToastStore } from '../store/toast'

const props = defineProps({
  show: { type: Boolean, default: false },
  defaultStoreName: { type: String, default: 'StreamePro Oficial' },
  initialProduct: { type: Object, default: null },
  resellerRef: { type: String, default: 'DIRECT' },
  websiteDomain: { type: String, default: 'streamepro.me' },
  clientOnlyPhysical: { type: Boolean, default: false },
  defaultPhone: { type: String, default: '' }
})

const emit = defineEmits(['close', 'openReels'])

const adminStore = useAdminStore()
const toast = useToastStore()

// Modo móvil: 'preview' (vista previa y descargas) | 'editor' (herramientas)
const mobileTab = ref('preview')

// Modo de flyer: 'individual' | 'combo' | 'physical'
const flyerProductType = ref('individual')

// Disponibilidad de flyers de productos físicos (condicionado a que la tienda física esté activa)
const isPhysicalFlyerAvailable = computed(() => {
  return Boolean(
    adminStore.isStoreVisible && 
    adminStore.isPhysicalStoreVisible && 
    adminStore.physicalProducts && 
    adminStore.physicalProducts.length > 0
  )
})

// 1. Servicio Individual
const selectedPlatform = ref('Netflix')
const selectedPlanKey = ref('1_perfil')

// 2. Promoción Combo / Paquete
const selectedPackageId = ref('PKG-101')

// 3. Catálogo Físico
const selectedPhysicalId = ref('')
const physicalSearch = ref('')
const selectedPhysicalCat = ref('All')

// Decodificador seguro de entidades HTML para textos de flyer y canvas
const decodeHtmlEntities = (str) => {
  if (!str || typeof str !== 'string') return ''
  return str
    .replace(/&#039;|&apos;/g, "'")
    .replace(/&quot;/g, '"')
    .replace(/&amp;/g, '&')
    .replace(/&lt;/g, '<')
    .replace(/&gt;/g, '>')
    .replace(/&#8230;|&hellip;/g, '...')
    .replace(/&#8217;|&#8216;/g, "'")
    .replace(/&#8220;|&#8221;/g, '"')
    .replace(/&ntilde;/gi, 'ñ')
    .replace(/&Ntilde;/g, 'Ñ')
    .replace(/&aacute;/gi, 'á')
    .replace(/&eacute;/gi, 'é')
    .replace(/&iacute;/gi, 'í')
    .replace(/&oacute;/gi, 'ó')
    .replace(/&uacute;/gi, 'ú')
    .replace(/&#(\d+);/g, (match, dec) => String.fromCharCode(dec))
}

// 4. Videojuegos Digitales Multiplataforma (Catálogo Completo + Etiquetas Inteligentes + Ajuste Manual)
const selectedGamingId = ref('GAME-001')
const selectedGamingLicense = ref('primary') // 'primary' | 'secondary'
const selectedGamingPlatform = ref('PS5')
const gamingSearch = ref('')
const selectedGamingCat = ref('All')

const GAMING_LICENSE_BADGES_MAP = {
  ps4_primaria: { key: 'ps4_primaria', label: 'PS4 PRIMARIA', imgUrl: '/flyers/badge-ps4-primaria.png' },
  ps4_secundaria: { key: 'ps4_secundaria', label: 'PS4 SECUNDARIA', imgUrl: '/flyers/badge-ps4-secundaria.png' },
  ps5_primaria: { key: 'ps5_primaria', label: 'PS5 PRIMARIA', imgUrl: '/flyers/badge-ps5-primaria.png' },
  ps5_secundaria: { key: 'ps5_secundaria', label: 'PS5 SECUNDARIA', imgUrl: '/flyers/badge-ps5-secundaria.png' },
  ps3_digital: { key: 'ps3_digital', label: 'PS3 DIGITAL', imgUrl: '/flyers/badge-ps3-digital.png' }
}
const GAMING_LICENSE_BADGES_LIST = Object.values(GAMING_LICENSE_BADGES_MAP)
const gamingBadgeOverride = ref('auto') // 'auto' | key | 'none'
const gamingBadgePosition = ref('main_top_left') // 'main_top_left' | 'frame_top' | 'frame_bottom' | 'pill_top'

// Herramientas de ajuste manual de imagen en recuadro
const imageZoom = ref(1.0)
const imageOffsetX = ref(0)
const imageOffsetY = ref(0)
const customUploadImageUrl = ref('')
const customCoverInputRef = ref(null)

// Teléfono e identidad de Revendedor Oficial por Flyer
const selectedFlyerResellerId = ref(props.resellerRef || 'DIRECT')
const customContactPhone = ref(props.defaultPhone || '')

const resolveResellerRecord = (refId) => {
  if (!refId || refId === 'DIRECT') return null
  return (adminStore.resellers || []).find(r => r.id === refId || r.username === refId) || null
}

const effectiveResellerRef = computed(() => {
  if (props.resellerRef && props.resellerRef !== 'DIRECT') return props.resellerRef
  return selectedFlyerResellerId.value || 'DIRECT'
})

const effectiveResellerRecord = computed(() => resolveResellerRecord(effectiveResellerRef.value))

const effectiveStoreLabel = computed(() => {
  if (effectiveResellerRecord.value) {
    return effectiveResellerRecord.value.storeName || effectiveResellerRecord.value.name || storeName.value
  }
  return storeName.value || props.defaultStoreName || 'StreamePro Oficial'
})

const effectiveContactPhone = computed(() => {
  const edited = String(customContactPhone.value || '').trim()
  if (edited) return edited
  const rec = effectiveResellerRecord.value
  if (rec) return rec.contactNumber || rec.whatsapp || ''
  return props.defaultPhone || adminStore.providerBankInfo?.whatsapp || ''
})

watch(() => [props.show, props.resellerRef, props.defaultPhone], () => {
  if (props.resellerRef && props.resellerRef !== 'DIRECT') {
    selectedFlyerResellerId.value = props.resellerRef
  }
  const rec = resolveResellerRecord(effectiveResellerRef.value)
  customContactPhone.value = props.defaultPhone || rec?.contactNumber || rec?.whatsapp || adminStore.providerBankInfo?.whatsapp || ''
}, { immediate: true })

watch(selectedFlyerResellerId, (newId) => {
  if (!props.resellerRef || props.resellerRef === 'DIRECT') {
    const rec = resolveResellerRecord(newId)
    if (rec) {
      customContactPhone.value = rec.contactNumber || rec.whatsapp || adminStore.providerBankInfo?.whatsapp || ''
    } else {
      customContactPhone.value = props.defaultPhone || adminStore.providerBankInfo?.whatsapp || ''
    }
  }
})

// Consulta o Solicitud Manual de Juego al Administrador desde el Flyer Studio
const showManualInquiryBox = ref(false)
const manualInquiryTitle = ref('')
const manualInquiryPlatform = ref('PS5')
const manualInquiryLicense = ref('Primaria')
const manualInquiryName = ref('')
const manualInquiryPhone = ref('')
const manualInquiryNotes = ref('')

const submitManualGameInquiry = () => {
  const title = manualInquiryTitle.value.trim() || selectedGamingProduct.value?.title || ''
  if (!title && !manualInquiryNotes.value.trim()) {
    toast.add('Escribe el título del juego o tu consulta para el Administrador.', 'warning')
    return
  }
  const isReseller = effectiveResellerRef.value !== 'DIRECT'
  const senderName = manualInquiryName.value.trim() || effectiveStoreLabel.value || 'Cliente Web'
  const senderPhone = manualInquiryPhone.value.trim() || effectiveContactPhone.value || ''

  const res = adminStore.createSupportTicket({
    type: 'game_request',
    sourceRole: isReseller ? 'reseller' : 'client',
    category: 'gaming',
    clientNumber: effectiveResellerRef.value,
    clientName: senderName,
    phone: senderPhone,
    resellerId: effectiveResellerRef.value,
    resellerName: isReseller ? effectiveStoreLabel.value : '',
    requestedGame: title,
    requestedPlatform: manualInquiryPlatform.value,
    requestedLicense: manualInquiryLicense.value,
    subject: `Pedido / Consulta Manual: ${title} (${manualInquiryPlatform.value})`,
    message: manualInquiryNotes.value.trim() || `Solicitud de cotización o carga manual para ${title} (${manualInquiryPlatform.value} - ${manualInquiryLicense.value}).`
  })
  if (res.success) {
    toast.add(`📩 Solicitud #${res.ticket.id} enviada al Administrador. Te responderemos a la brevedad.`, 'success')
    manualInquiryTitle.value = ''
    manualInquiryNotes.value = ''
    showManualInquiryBox.value = false
  }
}

const GAMING_LAYOUTS = {
  story: { frame: { left: 18.6, top: 15.1, width: 62.8, height: 26.9, radius: 46 } },
  feed: { frame: { left: 21.8, top: 15.1, width: 56.4, height: 34.2, radius: 44 } },
  square: { frame: { left: 21.2, top: 16.0, width: 57.6, height: 36.8, radius: 44 } },
  banner: { frame: { left: 7.4, top: 19.3, width: 49.4, height: 45.7, radius: 42 } }
}

const gamingProductsList = computed(() => adminStore.processedGamingProducts || [])

const availableGamePlatforms = computed(() => {
  const g = selectedGamingProduct.value
  if (!g) return ['PS5', 'PS4']
  if (g.platform === 'PS4 / PS5') return ['PS5', 'PS4']
  if (g.platform.includes('Switch')) return ['Nintendo Switch']
  if (g.platform.includes('Xbox')) return ['Xbox Series X|S', 'Xbox One']
  if (g.platform.includes('PC') || g.platform.includes('Steam')) return ['PC (Steam)']
  return [g.platform || 'PS5']
})

const filteredGamingProducts = computed(() => {
  return gamingProductsList.value.filter(g => {
    let matchesCat = false
    if (selectedGamingCat.value === 'All' || selectedGamingCat.value === 'Todas' || !selectedGamingCat.value) {
      matchesCat = true
    } else if (selectedGamingCat.value === 'PS5') {
      matchesCat = Boolean(g.platform && g.platform.includes('PS5'))
    } else if (selectedGamingCat.value === 'PS4') {
      matchesCat = Boolean(g.platform && g.platform.includes('PS4'))
    } else if (selectedGamingCat.value === 'PS3') {
      matchesCat = Boolean(g.platform && g.platform.includes('PS3'))
    } else if (selectedGamingCat.value === 'Xbox') {
      matchesCat = Boolean(g.platform && g.platform.includes('Xbox'))
    } else if (selectedGamingCat.value === 'Nintendo Switch') {
      matchesCat = Boolean(g.platform && g.platform.includes('Switch'))
    } else if (selectedGamingCat.value === 'PC') {
      matchesCat = Boolean(g.platform && (g.platform.includes('PC') || g.platform.includes('Steam')))
    } else if (selectedGamingCat.value === 'Lanzamientos') {
      matchesCat = Boolean(g.badge && (g.badge.includes('LANZAMIENTO') || g.badge.includes('2026') || g.badge.includes('2025') || g.badge.includes('2024') || g.badge.includes('TOP') || g.badge.includes('MÁS VENDIDO') || g.badge.includes('FENÓMENO') || g.badge.includes('JUEGO DEL AÑO')))
    } else {
      matchesCat = g.category === selectedGamingCat.value
    }
    const q = gamingSearch.value.toLowerCase().trim()
    const matchesQ = !q || g.title.toLowerCase().includes(q) || (g.platform && g.platform.toLowerCase().includes(q)) || (g.category && g.category.toLowerCase().includes(q))
    return matchesCat && matchesQ
  })
})

const selectedGamingProduct = computed(() => {
  const list = gamingProductsList.value
  if (!list.length) return null
  if (!selectedGamingId.value) return list[0]
  return list.find(g => g.id === selectedGamingId.value) || list[0]
})

const displayedGamingSelectOptions = computed(() => {
  const slice = filteredGamingProducts.value.slice(0, 120)
  const curr = selectedGamingProduct.value
  if (curr && !slice.some(g => g.id === curr.id)) {
    return [curr, ...slice]
  }
  return slice
})

const selectedGameSupportsSecondary = computed(() => {
  const plat = String(selectedGamingPlatform.value || selectedGamingProduct.value?.platform || '').toLowerCase()
  return !plat.includes('ps3') && !plat.includes('pc') && !plat.includes('steam') && !plat.includes('xbox') && !plat.includes('switch') && !plat.includes('nintendo')
})

const detectedGamingBadgeInfo = computed(() => {
  const plat = String(selectedGamingPlatform.value || selectedGamingProduct.value?.platform || '').toUpperCase()
  const titleUpper = String(selectedGamingProduct.value?.title || '').toUpperCase()
  const isSec = selectedGamingLicense.value === 'secondary'

  if (plat.includes('XBOX')) return { key: 'xbox_digital', label: '💚 XBOX DIGITAL', imgUrl: null }
  if (plat.includes('SWITCH') || plat.includes('NINTENDO')) return { key: 'switch_digital', label: '❤️ NINTENDO SWITCH', imgUrl: null }
  if (plat.includes('PC') || plat.includes('STEAM')) return { key: 'pc_steam', label: '🖥️ PC STEAM', imgUrl: null }
  if ((plat.includes('PS3') && !plat.includes('PS4') && !plat.includes('PS5')) || (/\bPS3\b/.test(titleUpper) && !/\bPS[45]\b/.test(titleUpper))) {
    return GAMING_LICENSE_BADGES_MAP.ps3_digital
  }
  if (plat.includes('PS4') && !plat.includes('PS5')) {
    return isSec ? GAMING_LICENSE_BADGES_MAP.ps4_secundaria : GAMING_LICENSE_BADGES_MAP.ps4_primaria
  }
  return isSec ? GAMING_LICENSE_BADGES_MAP.ps5_secundaria : GAMING_LICENSE_BADGES_MAP.ps5_primaria
})

const activeGamingBadgeInfo = computed(() => {
  if (gamingBadgeOverride.value === 'none') return null
  if (gamingBadgeOverride.value !== 'auto' && GAMING_LICENSE_BADGES_MAP[gamingBadgeOverride.value]) {
    return GAMING_LICENSE_BADGES_MAP[gamingBadgeOverride.value]
  }
  return detectedGamingBadgeInfo.value
})

const coverPreviewTransformStyle = computed(() => ({
  transform: `translate(${imageOffsetX.value}%, ${imageOffsetY.value}%) scale(${imageZoom.value})`
}))

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
const flyerImageFit = ref('contain') // 'contain' (centrada completa con fondo ambiental) | 'cover' (rellenar marco)

const currentGamingLayout = computed(() => GAMING_LAYOUTS[flyerFormat.value] || GAMING_LAYOUTS.story)

const getBadgePercentBox = (fmt) => {
  const pos = gamingBadgePosition.value
  if (fmt === 'banner') {
    if (pos === 'main_top_left') return { left: 5.4, top: 11.4, width: 24.5 }
    if (pos === 'frame_top') return { left: 6.4, top: 17.2, width: 28.0 }
    if (pos === 'pill_top') return { left: 57.0, top: 67.6, width: 32.0 }
    return { left: 16.5, top: 63.0, width: 32.5 }
  }
  if (fmt === 'feed') {
    if (pos === 'main_top_left') return { left: 7.2, top: 10.2, width: 41.0 }
    if (pos === 'frame_top') return { left: 20.5, top: 14.0, width: 43.0 }
    if (pos === 'pill_top') return { left: 22.0, top: 62.6, width: 56.0 }
    return { left: 22.0, top: 46.8, width: 56.0 }
  }
  if (fmt === 'square') {
    if (pos === 'main_top_left') return { left: 7.2, top: 10.0, width: 39.5 }
    if (pos === 'frame_top') return { left: 20.0, top: 14.8, width: 43.0 }
    if (pos === 'pill_top') return { left: 22.0, top: 69.2, width: 56.0 }
    return { left: 22.0, top: 50.2, width: 56.0 }
  }
  // story
  if (pos === 'main_top_left') return { left: 7.2, top: 9.4, width: 46.0 }
  if (pos === 'frame_top') return { left: 17.5, top: 13.8, width: 46.0 }
  if (pos === 'pill_top') return { left: 21.0, top: 51.0, width: 58.0 }
  return { left: 21.0, top: 39.8, width: 58.0 }
}

const gamingDomBadgeStyle = computed(() => {
  const b = getBadgePercentBox(flyerFormat.value)
  return {
    left: b.left + '%',
    top: b.top + '%',
    width: b.width + '%'
  }
})

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

// Precarga de las 4 plantillas Cyber + las 4 plantillas Gaming Oficiales + 5 Etiquetas de Licencia
if (typeof window !== 'undefined') {
  ['story', 'feed', 'square', 'banner'].forEach((fmt) => {
    const ext = fmt === 'story' ? 'png' : 'jpg'
    const imgCyber = new Image()
    imgCyber.src = `/flyer-template-cyber-${fmt}.${ext}`
    const imgGaming = new Image()
    imgGaming.src = `/flyers/gaming-template-${fmt}.jpg`
  })
  ;[
    '/flyers/badge-ps4-primaria.png',
    '/flyers/badge-ps4-secundaria.png',
    '/flyers/badge-ps5-primaria.png',
    '/flyers/badge-ps5-secundaria.png',
    '/flyers/badge-ps3-digital.png'
  ].forEach((bSrc) => {
    const bImg = new Image()
    bImg.src = bSrc
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
  if (flyerProductType.value === 'gaming') {
    if (format === 'feed') return '/flyers/gaming-template-feed.jpg'
    if (format === 'square') return '/flyers/gaming-template-square.jpg'
    if (format === 'banner') return '/flyers/gaming-template-banner.jpg'
    return '/flyers/gaming-template-story.jpg'
  }
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
  if (val && isPhysicalFlyerAvailable.value) {
    flyerProductType.value = 'physical'
  } else if (!isPhysicalFlyerAvailable.value && flyerProductType.value === 'physical') {
    flyerProductType.value = 'individual'
  }
}, { immediate: true })

// Si la tienda física se desactiva en tiempo real y el flyer estaba en modo físico, volver a individual
watch(isPhysicalFlyerAvailable, (available) => {
  if (!available && flyerProductType.value === 'physical') {
    flyerProductType.value = 'individual'
  }
})

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

// Filtrado de productos físicos (solo si la tienda física y sus flyers están activos)
const filteredPhysicalProducts = computed(() => {
  if (!isPhysicalFlyerAvailable.value) return []
  return adminStore.physicalProducts.filter(p => {
    const matchesCat = selectedPhysicalCat.value === 'All' || (p.categoryIds && p.categoryIds.includes(selectedPhysicalCat.value))
    const q = physicalSearch.value.toLowerCase().trim()
    const matchesQ = !q || p.name.toLowerCase().includes(q) || String(p.id).includes(q)
    return matchesCat && matchesQ
  })
})

const selectedPhysicalProduct = computed(() => {
  if (!isPhysicalFlyerAvailable.value) return null
  const list = adminStore.physicalProducts || []
  if (list.length === 0) return null
  if (!selectedPhysicalId.value) return list[0]
  return list.find(p => String(p.id) === String(selectedPhysicalId.value)) || list[0]
})

// Imagen con CORS y soporte para portada personalizada manual
const productImageCORS = computed(() => {
  if (customUploadImageUrl.value) {
    return customUploadImageUrl.value
  }
  if (flyerProductType.value === 'physical' && selectedPhysicalProduct.value?.image) {
    return getCORSImageUrl(selectedPhysicalProduct.value.image)
  }
  if (flyerProductType.value === 'gaming' && selectedGamingProduct.value?.image) {
    return getCORSImageUrl(selectedGamingProduct.value.image)
  }
  return null
})

const effectiveCoverImage = computed(() => {
  if (customUploadImageUrl.value) return customUploadImageUrl.value
  return productImageCORS.value || (flyerProductType.value === 'gaming' ? selectedGamingProduct.value?.image : selectedPhysicalProduct.value?.image) || ''
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
          : 'Garantía Total 24/7 & Entrega 60/90min'
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

const resetImageAdjustments = () => {
  imageZoom.value = 1
  imageOffsetX.value = 0
  imageOffsetY.value = 0
}

const triggerCustomCoverUpload = () => {
  customCoverInputRef.value?.click()
}

const handleCustomCoverUpload = (event) => {
  const file = event.target?.files?.[0]
  if (!file) return
  const reader = new FileReader()
  reader.onload = (e) => {
    if (e.target?.result) {
      customUploadImageUrl.value = String(e.target.result)
      resetImageAdjustments()
      toast.add('🖼️ Portada personalizada cargada en el recuadro', 'success')
    }
  }
  reader.readAsDataURL(file)
  if (event.target) event.target.value = ''
}

let dragState = null
const startCoverDrag = (event) => {
  const box = event.currentTarget?.getBoundingClientRect()
  if (!box) return
  event.currentTarget?.setPointerCapture?.(event.pointerId)
  dragState = {
    startX: event.clientX,
    startY: event.clientY,
    initOffX: imageOffsetX.value,
    initOffY: imageOffsetY.value,
    w: Math.max(1, box.width),
    h: Math.max(1, box.height)
  }
}

const onCoverDrag = (event) => {
  if (!dragState) return
  const dxPct = ((event.clientX - dragState.startX) / dragState.w) * 100
  const dyPct = ((event.clientY - dragState.startY) / dragState.h) * 100
  imageOffsetX.value = Math.max(-60, Math.min(60, Math.round(dragState.initOffX + dxPct)))
  imageOffsetY.value = Math.max(-60, Math.min(60, Math.round(dragState.initOffY + dyPct)))
}

const endCoverDrag = () => {
  dragState = null
}

// Sincronización oficial para videojuegos multiplataforma
const syncGamingFlyer = () => {
  if (flyerProductType.value !== 'gaming') return
  const g = selectedGamingProduct.value
  if (!g) return

  if (g.platform === 'PS5') {
    selectedGamingPlatform.value = 'PS5'
  } else if (g.platform === 'PS4') {
    selectedGamingPlatform.value = 'PS4'
  } else if (g.platform === 'PS3') {
    selectedGamingPlatform.value = 'PS3'
  } else if (g.platform.includes('Switch')) {
    selectedGamingPlatform.value = 'Nintendo Switch'
  } else if (g.platform.includes('Xbox')) {
    selectedGamingPlatform.value = g.platform.includes('One') ? 'Xbox One' : 'Xbox Series X|S'
  } else if (g.platform.includes('PC') || g.platform.includes('Steam')) {
    selectedGamingPlatform.value = 'PC (Steam)'
  } else if (!availableGamePlatforms.value.includes(selectedGamingPlatform.value)) {
    selectedGamingPlatform.value = availableGamePlatforms.value[0] || 'PS5'
  }

  if (!selectedGameSupportsSecondary.value && selectedGamingLicense.value === 'secondary') {
    selectedGamingLicense.value = 'primary'
  }

  const isPrim = selectedGamingLicense.value === 'primary'
  customTitle.value = decodeHtmlEntities(g.title)

  let storeNameLabel = 'PlayStation Store'
  const plat = selectedGamingPlatform.value
  if (plat.includes('Switch')) storeNameLabel = 'Nintendo eShop'
  else if (plat.includes('Xbox')) storeNameLabel = 'Xbox Store'
  else if (plat.includes('PC') || plat.includes('Steam')) storeNameLabel = 'Steam'

  if (plat.includes('PC') || plat.includes('Steam')) {
    customSubtitle.value = `🔑 Clave Digital Oficial · ${storeNameLabel}`
    headlineBadge.value = '🔥 ENTREGA 60/90 MIN & GARANTÍA OFICIAL'
    bullet1.value = 'Código original para canjear en tu cuenta de Steam'
  } else if (plat.includes('Switch')) {
    customSubtitle.value = `⭐️ Cuenta Primaria · ${plat} Oficial`
    headlineBadge.value = '🔥 JUEGA CON TU USUARIO PERSONAL'
    bullet1.value = 'Juega con tu Perfil Personal en Nintendo Switch'
  } else if (plat.includes('Xbox')) {
    customSubtitle.value = `⭐️ Licencia Digital · ${plat} Oficial`
    headlineBadge.value = '🔥 JUEGA CON TU PERFIL PERSONAL'
    bullet1.value = 'Juega con tu Gamertag Personal (Logros en tu cuenta)'
  } else if (plat.includes('PS3')) {
    customSubtitle.value = '🕹️ Licencia Digital Oficial · PS3'
    headlineBadge.value = '🔥 JUEGA EN TU CONSOLA PLAYSTATION 3'
    bullet1.value = 'Instalación completa y directa en tu consola PS3'
  } else {
    customSubtitle.value = `${isPrim ? '⭐️ Cuenta Primaria' : '⚡️ Cuenta Secundaria'} · ${plat} Digital Oficial`
    headlineBadge.value = isPrim ? '🔥 JUEGA CON TU USUARIO PERSONAL' : '⚡️ ACCESO ECONÓMICO 100% GARANTIZADO'
    bullet1.value = isPrim 
      ? 'Juega con tu Perfil Personal (Trofeos en tu cuenta)' 
      : 'Juega desde el usuario asignado 100% oficial'
  }

  const resolvedPrice = isPrim
    ? (g.effectivePrimaryPrice || 0)
    : (g.effectiveSecondaryPrice || g.effectivePrimaryPrice || 0)
  customPrice.value = resolvedPrice
  customOriginalPrice.value = Math.round(resolvedPrice * 1.35)
  bullet2.value = `Descarga directa desde ${storeNameLabel}`
  bullet3.value = 'Garantía oficial y entrega en 60 a 90 min 24/7'
}

watch(selectedGamingId, () => {
  customUploadImageUrl.value = ''
  resetImageAdjustments()
})

watch([selectedGamingId, selectedGamingLicense, selectedGamingPlatform], () => {
  if (flyerProductType.value === 'gaming') {
    syncGamingFlyer()
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
          : 'Garantía Total 24/7 & Entrega 60/90min'
      }
    }
  } else if (newType === 'physical') {
    if (!isPhysicalFlyerAvailable.value) {
      flyerProductType.value = 'individual'
      toast.add('La tienda física está desactivada. Cambiado a catálogo digital.', 'warning')
      return
    }
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
  } else if (newType === 'gaming') {
    flyerImageFit.value = 'contain'
    if (!selectedGamingId.value && gamingProductsList.value.length > 0) {
      selectedGamingId.value = gamingProductsList.value[0].id
    }
    syncGamingFlyer()
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
  if (prod.itemType === 'gaming' || prod.isGaming || prod.effectivePrimaryPrice) {
    flyerProductType.value = 'gaming'
    flyerImageFit.value = 'contain'
    if (prod.id || prod.gameId) selectedGamingId.value = prod.id || prod.gameId
    if (prod.licenseType) selectedGamingLicense.value = prod.licenseType
    if (prod.platform) {
      if (prod.platform.includes('PS3')) selectedGamingPlatform.value = 'PS3'
      else if (prod.platform.includes('PS4')) selectedGamingPlatform.value = 'PS4'
      else if (prod.platform.includes('PS5')) selectedGamingPlatform.value = 'PS5'
      else if (prod.platform.includes('Switch')) selectedGamingPlatform.value = 'Nintendo Switch'
      else if (prod.platform.includes('Xbox')) selectedGamingPlatform.value = prod.platform.includes('One') ? 'Xbox One' : 'Xbox Series X|S'
      else if (prod.platform.includes('PC') || prod.platform.includes('Steam')) selectedGamingPlatform.value = 'PC (Steam)'
    }
    syncGamingFlyer()
  } else if (prod.itemType === 'package' || prod.services || prod.promoPrice) {
    flyerProductType.value = 'combo'
    if (prod.id) selectedPackageId.value = prod.id
    customTitle.value = prod.title || ''
    customSubtitle.value = prod.description || ''
    headlineBadge.value = prod.badge || '🔥 25% OFF - RECOMENDADO'
    customPrice.value = prod.promoPrice || null
    customOriginalPrice.value = prod.originalPrice || null
  } else if (prod.itemType === 'physical' || prod.platform === 'Producto Físico' || prod.categoryIds || prod.price?.includes('$')) {
    if (isPhysicalFlyerAvailable.value) {
      flyerProductType.value = 'physical'
      selectedPhysicalId.value = String(prod.id)
      customTitle.value = prod.name || ''
      customSubtitle.value = 'Tecnología & Accesorios de Alta Calidad'
      customPrice.value = prod.priceNumeric || (parseInt(prod.price?.replace(/[$. ]/g, '').replace(',', '') || '0') / 100 || null)
      customOriginalPrice.value = null
      headlineBadge.value = '📦 ENVÍO A TODO EL PAÍS'
    } else {
      flyerProductType.value = 'individual'
      toast.add('La tienda física está desactivada. Se muestra el catálogo digital.', 'warning')
    }
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
  if (flyerProductType.value === 'gaming') {
    const g = selectedGamingProduct.value
    if (!g) return 25000
    return selectedGamingLicense.value === 'primary' ? g.effectivePrimaryPrice : g.effectiveSecondaryPrice
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
  if (customTitle.value?.trim()) return decodeHtmlEntities(customTitle.value.trim())
  if (flyerProductType.value === 'gaming') {
    return decodeHtmlEntities(selectedGamingProduct.value?.title || 'Videojuego Digital Oficial')
  }
  if (flyerProductType.value === 'combo') {
    return decodeHtmlEntities(selectedPackage.value?.title || 'Combo Especial Streaming')
  }
  if (flyerProductType.value === 'individual') {
    return decodeHtmlEntities(currentPlatformData.value?.name || selectedPlatform.value || 'Streaming')
  }
  return decodeHtmlEntities(selectedPhysicalProduct.value?.name || 'Producto Tecnológico')
})

// Subtítulo
const displaySubtitle = computed(() => {
  if (customSubtitle.value?.trim()) return decodeHtmlEntities(customSubtitle.value.trim())
  if (flyerProductType.value === 'gaming') {
    const isPrim = selectedGamingLicense.value === 'primary'
    const plat = selectedGamingPlatform.value
    if (plat.includes('PC') || plat.includes('Steam')) {
      return '🔑 Clave Digital Oficial · Steam'
    }
    return `${isPrim ? '⭐️ Cuenta Primaria' : '⚡️ Cuenta Secundaria'} · ${plat} Digital Oficial`
  }
  if (flyerProductType.value === 'combo') {
    return decodeHtmlEntities(selectedPackage.value?.description || 'Las mejores plataformas combinadas con descuento especial')
  }
  if (flyerProductType.value === 'individual') {
    return decodeHtmlEntities(currentPlanData.value?.label || 'Suscripción Oficial')
  }
  return 'Tecnología & Accesorios de Alta Calidad'
})

const gamingFlyerGuaranteeText = computed(() => {
  const plat = String(selectedGamingPlatform.value || '')
  if (plat.includes('PC') || plat.includes('Steam')) return '🔑 Clave Oficial Steam · Entrega 60/90min · Garantía 100%'
  if (plat.includes('Switch')) return '🎮 Licencia Oficial Nintendo · Entrega 60/90min · Garantía 100%'
  if (plat.includes('Xbox')) return '🎮 Licencia Oficial Xbox · Entrega 60/90min · Garantía 100%'
  return '🎮 Licencia Oficial PlayStation · Entrega 60/90min · Garantía 100%'
})

// URL de referencia / enlace directo oficial con OpenGraph 1200x630
const fullProductReferralUrl = computed(() => {
  const domain = displayDomain.value || 'streamepro.me'
  const base = `https://${domain}`
  const refCode = effectiveResellerRef.value && effectiveResellerRef.value !== 'DIRECT' ? effectiveResellerRef.value : null

  if (flyerProductType.value === 'gaming' && selectedGamingProduct.value?.id) {
    const gamePath = `${base}/juego/${encodeURIComponent(selectedGamingProduct.value.id)}${refCode ? '/' + encodeURIComponent(refCode) : ''}`
    const params = new URLSearchParams()
    if (selectedGamingLicense.value) params.set('lic', selectedGamingLicense.value)
    if (selectedGamingPlatform.value) params.set('plat', selectedGamingPlatform.value)
    const qs = params.toString()
    return qs ? `${gamePath}?${qs}` : gamePath
  }

  if (flyerProductType.value === 'combo' && selectedPackage.value?.id) {
    const params = new URLSearchParams()
    params.set('sec', 'packages')
    params.set('pkg', selectedPackage.value.id)
    if (refCode) params.set('ref', refCode)
    return `${base}/?${params.toString()}`
  }

  if (flyerProductType.value === 'physical' && selectedPhysicalProduct.value?.id) {
    const prodId = encodeURIComponent(String(selectedPhysicalProduct.value.id))
    return refCode ? `${base}/producto/${prodId}/${encodeURIComponent(refCode)}` : `${base}/producto/${prodId}`
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
      const isReseller = effectiveResellerRef.value && effectiveResellerRef.value !== 'DIRECT'
      toast.add(isReseller ? '¡Enlace de referencia con tu comisión copiado!' : '¡Enlace oficial del producto copiado!', 'success')
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

      function drawCoverWithManualAdjust(ctx, imgEl, innerX, innerY, innerW, innerH, cornerR, isGamer) {
        ctx.save()
        roundRect(ctx, innerX, innerY, innerW, innerH, cornerR, false, false)
        ctx.clip()

        ctx.fillStyle = isGamer ? '#060a14' : '#0f172a'
        ctx.fillRect(innerX, innerY, innerW, innerH)

        if (imgEl) {
          const imgAspect = (imgEl.width || 1) / (imgEl.height || 1)
          const visorAspect = innerW / innerH

          // 1. Fondo ambiental luminoso difuminado con saturación limpia
          const bgScale = Math.max(innerW / imgEl.width, innerH / imgEl.height) * 1.22
          const bgW = imgEl.width * bgScale
          const bgH = imgEl.height * bgScale
          ctx.save()
          ctx.filter = 'blur(20px) brightness(0.48) saturate(1.25)'
          ctx.drawImage(imgEl, innerX + (innerW - bgW) / 2, innerY + (innerH - bgH) / 2, bgW, bgH)
          ctx.restore()

          // Sombra sutil superior/inferior para fundir con el marco metálico
          const vigGrad = ctx.createLinearGradient(innerX, innerY, innerX, innerY + innerH)
          vigGrad.addColorStop(0, 'rgba(0, 0, 0, 0.25)')
          vigGrad.addColorStop(0.5, 'rgba(0, 0, 0, 0)')
          vigGrad.addColorStop(1, 'rgba(0, 0, 0, 0.38)')
          ctx.fillStyle = vigGrad
          ctx.fillRect(innerX, innerY, innerW, innerH)

          // 2. Tamaño base según modo ('contain' centrado completo o 'cover' rellenar)
          const pad = isGamer ? Math.max(18, Math.round(innerH * 0.05)) : 16
          let baseW, baseH
          if (flyerImageFit.value === 'contain') {
            if (visorAspect > imgAspect) {
              baseH = innerH - pad * 2
              baseW = baseH * imgAspect
            } else {
              baseW = innerW - pad * 2
              baseH = baseW / imgAspect
            }
          } else {
            if (visorAspect > imgAspect) {
              baseW = innerW
              baseH = innerW / imgAspect
            } else {
              baseH = innerH
              baseW = innerH * imgAspect
            }
          }

          // 3. Transformación manual: translate(offsetX%, offsetY%) + scale(imageZoom) equivalente a CSS
          const scale = Math.max(0.5, Number(imageZoom.value) || 1)
          const dw = baseW * scale
          const dh = baseH * scale
          const panX = ((Number(imageOffsetX.value) || 0) / 100) * innerW
          const panY = ((Number(imageOffsetY.value) || 0) / 100) * innerH
          const dx = innerX + (innerW - dw) / 2 + panX
          const dy = innerY + (innerH - dh) / 2 + panY

          ctx.save()
          if (flyerImageFit.value === 'contain') {
            ctx.shadowColor = 'rgba(0, 0, 0, 0.85)'
            ctx.shadowBlur = 26
            ctx.shadowOffsetY = 8
          }
          ctx.drawImage(imgEl, dx, dy, dw, dh)
          ctx.restore()
        } else {
          const iconSize = Math.min(130, innerH * 0.38)
          ctx.font = `${iconSize}px sans-serif`
          ctx.textAlign = 'center'
          ctx.fillText(isGamer ? '🎮' : '📦', innerX + innerW / 2, innerY + innerH / 2 + iconSize * 0.25)
        }
        ctx.restore()
      }

      async function drawVisorHeroContent(ctx, visorX, visorY, visorW, visorH) {
        const centerX = visorX + visorW / 2
        const centerY = visorY + visorH / 2
        const th = currentTheme.value

        ctx.save()
        const pedGlow = ctx.createRadialGradient(centerX, centerY, 20, centerX, centerY, visorW * 0.5)
        pedGlow.addColorStop(0, th.pedGlow)
        pedGlow.addColorStop(1, 'transparent')
        ctx.fillStyle = pedGlow
        ctx.fillRect(visorX, visorY, visorW, visorH)

        if (flyerProductType.value === 'physical') {
          const imgEl = await loadFlyerImageElement(effectiveCoverImage.value)
          const pad = (visorW < 450) ? 6 : 4
          const innerX = visorX + pad
          const innerY = visorY + pad
          const innerW = visorW - pad * 2
          const innerH = visorH - pad * 2
          const cornerR = Math.min(28, innerW * 0.065)

          drawCoverWithManualAdjust(ctx, imgEl, innerX, innerY, innerW, innerH, cornerR, false)

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
        const badgeText = decodeHtmlEntities(headlineBadge.value.toUpperCase())
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
          : flyerProductType.value === 'gaming'
          ? gamingFlyerGuaranteeText.value
          : '⚡ Entrega 60/90min · Garantía Oficial 100%'

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
        const bullets = [bullet1.value, bullet2.value, bullet3.value].slice(0, maxBullets).map(b => decodeHtmlEntities(b))
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

      async function drawGamingCanvasLayout() {
        const th = currentTheme.value
        const layout = currentGamingLayout.value
        const phoneStr = effectiveContactPhone.value

        // 1. Dibujar portada en el marco metálico exacto con ajuste manual centrado
        const fRect = layout.frame
        const fx = Math.round((fRect.left / 100) * width)
        const fy = Math.round((fRect.top / 100) * height)
        const fw = Math.round((fRect.width / 100) * width)
        const fh = Math.round((fRect.height / 100) * height)
        const coverEl = await loadFlyerImageElement(effectiveCoverImage.value)
        drawCoverWithManualAdjust(ctx, coverEl, fx, fy, fw, fh, fRect.radius || 24, true)

        // 2. Etiqueta Oficial de Tipo de Licencia (Superior Izquierda desde Recuadro Principal por defecto)
        if (activeGamingBadgeInfo.value && gamingBadgeOverride.value !== 'none') {
          const bSpec = getBadgePercentBox(format)
          const bw = Math.round((bSpec.width / 100) * width)
          const bx = Math.round((bSpec.left / 100) * width)
          const by = Math.round((bSpec.top / 100) * height)

          if (activeGamingBadgeInfo.value.imgUrl) {
            const badgeEl = await loadFlyerImageElement(activeGamingBadgeInfo.value.imgUrl)
            if (badgeEl) {
              const bh = Math.round(bw * (badgeEl.height / badgeEl.width))
              ctx.save()
              ctx.shadowColor = 'rgba(0, 220, 255, 0.7)'
              ctx.shadowBlur = 18
              ctx.drawImage(badgeEl, bx, by, bw, bh)
              ctx.restore()
            }
          } else {
            const bh = Math.round(bw * 0.16)
            ctx.save()
            const g = ctx.createLinearGradient(bx, by, bx + bw, by)
            g.addColorStop(0, '#0f172a')
            g.addColorStop(0.5, '#0284c7')
            g.addColorStop(1, '#1e3a8a')
            ctx.fillStyle = g
            roundRect(ctx, bx, by, bw, bh, bh / 2, true, false)
            ctx.fillStyle = '#ffffff'
            ctx.font = `900 ${Math.round(bh * 0.46)}px sans-serif`
            ctx.textAlign = 'center'
            ctx.fillText(activeGamingBadgeInfo.value.label, bx + bw / 2, by + bh * 0.67)
            ctx.restore()
          }
        }

        const hasOrig = displayOriginalPrice.value && Number(displayOriginalPrice.value) > Number(displayPriceNumeric.value)
        const guaranteeTxt = gamingFlyerGuaranteeText.value

        const drawOpenFeatheredScrim = (x, y, w, h, horizontal = false) => {
          ctx.save()
          const grad = horizontal
            ? ctx.createLinearGradient(x, y, x + w, y)
            : ctx.createLinearGradient(x, y, x, y + h)
          if (horizontal) {
            grad.addColorStop(0, 'rgba(2, 6, 23, 0.90)')
            grad.addColorStop(0.75, 'rgba(2, 6, 23, 0.72)')
            grad.addColorStop(1, 'rgba(2, 6, 23, 0)')
          } else {
            grad.addColorStop(0, 'rgba(2, 6, 23, 0)')
            grad.addColorStop(0.35, 'rgba(2, 6, 23, 0.86)')
            grad.addColorStop(0.65, 'rgba(2, 6, 23, 0.86)')
            grad.addColorStop(1, 'rgba(2, 6, 23, 0)')
          }
          ctx.fillStyle = grad
          ctx.fillRect(x, y, w, h)
          ctx.restore()
        }

        if (format === 'story') {
          // Etiqueta Impacto (46.6%)
          drawCyberBadge(ctx, width / 2, Math.round(height * 0.466), 44, 780, false)

          // Título + Subtítulo dentro del Recuadro Oscuro Oficial (PILL: 54.4%..63.4%)
          const pTop = Math.round(height * 0.546)
          const pH = Math.round(height * 0.088)
          ctx.save()
          ctx.textAlign = 'center'
          const titleLines = wrapText(displayTitle.value, 28).slice(0, 2)
          ctx.font = `900 ${titleLines.length > 1 ? 34 : 42}px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif`
          ctx.fillStyle = '#ffffff'
          titleLines.forEach((tl, i) => {
            const ty = pTop + (titleLines.length > 1 ? 52 + i * 42 : pH * 0.48)
            ctx.fillText(tl, width / 2, ty)
          })
          ctx.font = 'bold 21px monospace'
          ctx.fillStyle = th.textAccent
          const subStr = displaySubtitle.value.length > 46 ? displaySubtitle.value.slice(0, 44) + '...' : displaySubtitle.value
          ctx.fillText(subStr, width / 2, pTop + pH - 22)
          ctx.restore()

          // Zona Inferior Limpia y Esculpida (sin recuadro anexado con bordes)
          ctx.save()
          const haloGrad = ctx.createRadialGradient(width / 2, height * 0.715, 20, width / 2, height * 0.715, 340)
          haloGrad.addColorStop(0, 'rgba(0, 220, 255, 0.14)')
          haloGrad.addColorStop(1, 'rgba(0, 220, 255, 0)')
          ctx.fillStyle = haloGrad
          ctx.fillRect(100, height * 0.64, width - 200, 260)

          ctx.textAlign = 'center'
          if (hasOrig) {
            ctx.font = 'bold 21px sans-serif'
            ctx.fillStyle = '#94a3b8'
            const origStr = `Antes: $${Number(displayOriginalPrice.value).toLocaleString()}`
            const discStr = calculatedDiscountPct.value ? `  (-${calculatedDiscountPct.value}% OFF)` : ''
            ctx.fillText(origStr + discStr, width / 2, Math.round(height * 0.674))
          } else {
            ctx.font = 'bold 19px sans-serif'
            ctx.fillStyle = '#94a3b8'
            ctx.fillText('PRECIO EXCLUSIVO', width / 2, Math.round(height * 0.674))
          }

          ctx.font = '900 76px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
          ctx.fillStyle = '#ffffff'
          ctx.shadowColor = th.glow
          ctx.shadowBlur = 20
          ctx.fillText(`$${displayPrice.value} ARS`, width / 2, Math.round(height * 0.720))
          ctx.shadowBlur = 0

          ctx.font = 'bold 18px sans-serif'
          ctx.fillStyle = th.textAccent
          ctx.fillText(guaranteeTxt, width / 2, Math.round(height * 0.746))

          // Línea divisoria sutil de degradado
          const divGrad = ctx.createLinearGradient(220, 0, width - 220, 0)
          divGrad.addColorStop(0, 'rgba(56, 189, 248, 0)')
          divGrad.addColorStop(0.5, 'rgba(56, 189, 248, 0.5)')
          divGrad.addColorStop(1, 'rgba(56, 189, 248, 0)')
          ctx.strokeStyle = divGrad
          ctx.lineWidth = 1.5
          ctx.beginPath()
          ctx.moveTo(220, Math.round(height * 0.768))
          ctx.lineTo(width - 220, Math.round(height * 0.768))
          ctx.stroke()
          ctx.restore()

          drawCyberBullets(ctx, 130, Math.round(height * 0.798), 54, 3, false)

          if (phoneStr) {
            ctx.save()
            ctx.fillStyle = '#a5f3fc'
            ctx.font = 'bold 23px monospace'
            ctx.textAlign = 'center'
            ctx.shadowColor = 'rgba(0, 0, 0, 0.85)'
            ctx.shadowBlur = 8
            ctx.fillText(`📲 Pedidos WhatsApp: ${phoneStr}`, width / 2, Math.round(height * 0.892))
            ctx.restore()
          }
        } else if (format === 'feed') {
          // Zona abierta 50.8%..65.0% con degradado suave sin caja ni bordes
          const zTop = Math.round(height * 0.508)
          const zH = Math.round(height * 0.142)
          drawOpenFeatheredScrim(Math.round(width * 0.05), zTop, Math.round(width * 0.90), zH, false)
          drawCyberBadge(ctx, width / 2, zTop + 14, 32, 700, true)

          ctx.save()
          ctx.textAlign = 'center'
          ctx.shadowColor = 'rgba(0, 0, 0, 0.95)'
          ctx.shadowBlur = 10
          ctx.font = '900 36px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
          ctx.fillStyle = '#ffffff'
          const tClip = displayTitle.value.length > 34 ? displayTitle.value.slice(0, 32) + '...' : displayTitle.value
          ctx.fillText(tClip, width / 2, zTop + 98)
          ctx.font = 'bold 20px monospace'
          ctx.fillStyle = th.textAccent
          const subClip = displaySubtitle.value.length > 50 ? displaySubtitle.value.slice(0, 48) + '...' : displaySubtitle.value
          ctx.fillText(subClip, width / 2, zTop + 136)
          ctx.restore()

          // Precio DENTRO del Recuadro Oscuro Oficial (65.6%..76.4%)
          const pTop = Math.round(height * 0.658)
          ctx.save()
          ctx.textAlign = 'center'
          if (hasOrig) {
            ctx.font = 'bold 20px sans-serif'
            ctx.fillStyle = '#94a3b8'
            ctx.fillText(`Antes: $${Number(displayOriginalPrice.value).toLocaleString()}${calculatedDiscountPct.value ? ` (-${calculatedDiscountPct.value}% OFF)` : ''}`, width / 2, pTop + 32)
          }
          ctx.font = '900 58px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
          ctx.fillStyle = '#ffffff'
          ctx.shadowColor = th.glow
          ctx.shadowBlur = 16
          ctx.fillText(`$${displayPrice.value} ARS`, width / 2, pTop + 90)
          ctx.shadowBlur = 0
          ctx.font = 'bold 17px sans-serif'
          ctx.fillStyle = th.textAccent
          ctx.fillText(guaranteeTxt, width / 2, pTop + 122)
          ctx.restore()

          // Beneficios + Contacto limpios centrados debajo del pill (77.4%..89.2%)
          ctx.save()
          ctx.textAlign = 'center'
          ctx.font = '600 20px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
          ctx.fillStyle = '#e2e8f0'
          ctx.fillText(`✓ ${bullet1.value.slice(0, 42)}   •   ✓ ${bullet2.value.slice(0, 34)}`, width / 2, Math.round(height * 0.812))
          if (phoneStr) {
            ctx.fillStyle = '#a5f3fc'
            ctx.font = 'bold 22px monospace'
            ctx.fillText(`📲 Pedidos WhatsApp: ${phoneStr}`, width / 2, Math.round(height * 0.865))
          }
          ctx.restore()
        } else if (format === 'square') {
          // Zona abierta 54.2%..71.6% con degradado suave sin caja ni bordes
          const zTop = Math.round(height * 0.542)
          const zH = Math.round(height * 0.174)
          drawOpenFeatheredScrim(Math.round(width * 0.05), zTop, Math.round(width * 0.90), zH, false)
          drawCyberBadge(ctx, width / 2, zTop + 14, 32, 700, true)

          ctx.save()
          ctx.textAlign = 'center'
          ctx.shadowColor = 'rgba(0, 0, 0, 0.95)'
          ctx.shadowBlur = 10
          const sqLines = wrapText(displayTitle.value, 32).slice(0, 2)
          ctx.font = `900 ${sqLines.length > 1 ? 32 : 36}px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif`
          ctx.fillStyle = '#ffffff'
          sqLines.forEach((tl, i) => {
            const ty = zTop + (sqLines.length > 1 ? 82 + i * 36 : 94)
            ctx.fillText(tl, width / 2, ty)
          })
          ctx.font = 'bold 19px monospace'
          ctx.fillStyle = th.textAccent
          const subClip = displaySubtitle.value.length > 50 ? displaySubtitle.value.slice(0, 48) + '...' : displaySubtitle.value
          ctx.fillText(subClip, width / 2, zTop + (sqLines.length > 1 ? 152 : 136))
          ctx.restore()

          // Precio DENTRO del Recuadro Oscuro Oficial (72.2%..83.7%)
          const pTop = Math.round(height * 0.724)
          ctx.save()
          ctx.textAlign = 'center'
          if (hasOrig) {
            ctx.font = 'bold 18px sans-serif'
            ctx.fillStyle = '#94a3b8'
            ctx.fillText(`Antes: $${Number(displayOriginalPrice.value).toLocaleString()}${calculatedDiscountPct.value ? ` (-${calculatedDiscountPct.value}% OFF)` : ''}`, width / 2, pTop + 26)
          }
          ctx.font = '900 50px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
          ctx.fillStyle = '#ffffff'
          ctx.shadowColor = th.glow
          ctx.shadowBlur = 16
          ctx.fillText(`$${displayPrice.value} ARS`, width / 2, pTop + 78)
          ctx.shadowBlur = 0
          ctx.font = 'bold 16px sans-serif'
          ctx.fillStyle = th.textAccent
          ctx.fillText(guaranteeTxt, width / 2, pTop + 108)
          ctx.restore()

          // Franja inferior limpia (84.8%..88.8%)
          ctx.save()
          ctx.font = '600 18px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
          ctx.fillStyle = '#e2e8f0'
          ctx.textAlign = 'left'
          ctx.fillText(`✓ ${bullet1.value.slice(0, 42)}`, 108, Math.round(height * 0.873))
          if (phoneStr) {
            ctx.textAlign = 'right'
            ctx.fillStyle = '#a5f3fc'
            ctx.font = 'bold 18px monospace'
            ctx.fillText(`📲 ${phoneStr}`, width - 108, Math.round(height * 0.873))
          }
          ctx.restore()
        } else {
          // BANNER 16:9 GAMER (1920 x 1080) — Texto integrado abajo izq. sin caja ni bordes
          const cardX = Math.round(width * 0.04)
          const cardY = Math.round(height * 0.675)
          const cardW = Math.round(width * 0.475)
          const cardH = Math.round(height * 0.225)
          drawOpenFeatheredScrim(cardX, cardY, cardW, cardH, true)

          const textLeft = Math.round(width * 0.072)
          ctx.save()
          const bGrad = ctx.createLinearGradient(textLeft, cardY + 18, textLeft + 380, cardY + 18)
          bGrad.addColorStop(0, th.badgeGrad[0])
          bGrad.addColorStop(1, th.badgeGrad[1])
          const badgeTxt = decodeHtmlEntities(headlineBadge.value.toUpperCase())
          ctx.font = '900 17px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
          const bW = Math.min(ctx.measureText(badgeTxt).width + 36, Math.round(width * 0.40))
          ctx.fillStyle = bGrad
          roundRect(ctx, textLeft, cardY + 16, bW, 32, 16, true, false)
          ctx.textAlign = 'left'
          ctx.fillStyle = th.id === 'cyber' ? '#020617' : '#ffffff'
          ctx.fillText(badgeTxt.slice(0, 44), textLeft + 18, cardY + 38)

          ctx.shadowColor = 'rgba(0, 0, 0, 0.95)'
          ctx.shadowBlur = 10
          ctx.font = '900 35px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
          ctx.fillStyle = '#ffffff'
          const tClip = displayTitle.value.length > 34 ? displayTitle.value.slice(0, 32) + '...' : displayTitle.value
          ctx.fillText(tClip, textLeft, cardY + 94)

          ctx.font = 'bold 21px monospace'
          ctx.fillStyle = th.textAccent
          const sClip = displaySubtitle.value.length > 48 ? displaySubtitle.value.slice(0, 46) + '...' : displaySubtitle.value
          ctx.fillText(sClip, textLeft, cardY + 132)

          ctx.font = '600 19px sans-serif'
          ctx.fillStyle = '#e2e8f0'
          ctx.fillText(`✓ ${bullet1.value.slice(0, 52)}`, textLeft, cardY + 168)
          ctx.restore()

          // Precio DENTRO del Recuadro Oscuro Derecho Oficial (51.6%..94.5%, 72.3%..84.2%)
          const rCenterX = Math.round(width * 0.731)
          const rTop = Math.round(height * 0.725)
          ctx.save()
          ctx.textAlign = 'center'
          if (hasOrig) {
            ctx.font = 'bold 20px sans-serif'
            ctx.fillStyle = '#94a3b8'
            ctx.fillText(`Antes: $${Number(displayOriginalPrice.value).toLocaleString()}${calculatedDiscountPct.value ? `  |  -${calculatedDiscountPct.value}% OFF` : ''}`, rCenterX, rTop + 30)
          }
          ctx.font = '900 54px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
          ctx.fillStyle = '#ffffff'
          ctx.shadowColor = th.glow
          ctx.shadowBlur = 16
          ctx.fillText(`$${displayPrice.value} ARS`, rCenterX, rTop + 82)
          ctx.shadowBlur = 0
          ctx.font = 'bold 17px sans-serif'
          ctx.fillStyle = th.textAccent
          ctx.fillText(guaranteeTxt, rCenterX, rTop + 114)

          if (phoneStr) {
            ctx.fillStyle = '#a5f3fc'
            ctx.font = 'bold 21px monospace'
            ctx.fillText(`📲 WhatsApp Pedidos: ${phoneStr}`, rCenterX, Math.round(height * 0.876))
          }
          ctx.restore()
        }
      }

      // ========================================================
      // 1. MOTOR DE DISEÑO OFICIAL UNIFICADO (GAMING + STREAMING/FÍSICO)
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

      if (flyerProductType.value === 'gaming') {
        await drawGamingCanvasLayout()
      } else if (format === 'banner') {
        // ================= BANNER HORIZONTAL (1920 x 1080) =================
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
        await drawVisorHeroContent(ctx, 198, 261, 684, 544)

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
        await drawVisorHeroContent(ctx, 218, 194, 645, 385)

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
        await drawVisorHeroContent(ctx, 353, 148, 374, 376)

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

      // Banda inferior para modos Streaming / Combo / Físico (las plantillas Gaming ya tienen su propia barra footer)
      if (flyerProductType.value !== 'gaming' && (effectiveContactPhone.value || displayDomain.value)) {
        const footerText = [
          effectiveStoreLabel.value || 'StreamePro Oficial',
          effectiveContactPhone.value ? `WhatsApp: ${effectiveContactPhone.value}` : '',
          displayDomain.value || 'streamepro.me'
        ].filter(Boolean).join('  •  ')
        const barW = Math.min(width - 120, Math.max(460, footerText.length * 14 + 60))
        const barX = format === 'banner' ? 740 : (width - barW) / 2
        const barY = height - (format === 'story' ? 88 : 66)
        ctx.fillStyle = 'rgba(2, 6, 23, 0.85)'
        roundRect(ctx, barX, barY, barW, 44, 22, true, true, th.cardBorder, 1.5)
        ctx.fillStyle = '#e2e8f0'
        ctx.font = 'bold 20px sans-serif'
        ctx.textAlign = 'center'
        ctx.fillText(footerText, barX + barW / 2, barY + 29)
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
  link.rel = 'noopener'
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  if (fileUrl.startsWith('blob:')) {
    setTimeout(() => {
      try { URL.revokeObjectURL(fileUrl) } catch (_) {}
    }, 60000)
  }
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
// PASE DIRECTO A ESTUDIO DE REELS / TIKTOK 9:16 (ANIMADO A 30 FPS)
// ==========================================
function getReelsPayload() {
  const isGamer = flyerProductType.value === 'gaming'
  const selGame = selectedGamingProduct.value
  return {
    itemType: isGamer ? 'gaming' : (flyerProductType.value === 'physical' ? 'physical' : (flyerProductType.value === 'combo' ? 'combo' : 'streaming')),
    id: isGamer ? (selGame?.id || '') : '',
    gameId: isGamer ? (selGame?.id || '') : '',
    title: displayTitle.value,
    subtitle: displaySubtitle.value,
    price: Number(displayPriceNumeric.value || 0),
    originalPrice: Number(displayOriginalPrice.value || 0),
    effectivePrimaryPrice: isGamer ? (selGame?.effectivePrimaryPrice || selGame?.primaryPrice || displayPriceNumeric.value) : undefined,
    effectiveSecondaryPrice: isGamer ? (selGame?.effectiveSecondaryPrice || selGame?.secondaryPrice || undefined) : undefined,
    badge: headlineBadge.value,
    license: selectedGamingLicense.value,
    platform: selectedGamingPlatform.value || selectedPlatform.value,
    image: effectiveCoverImage.value,
    phone: effectiveContactPhone.value,
    bullet1: bullet1.value,
    bullet2: bullet2.value,
    bullet3: bullet3.value,
    theme: flyerTheme.value,
    guaranteeText: isGamer ? gamingFlyerGuaranteeText.value : '🛡️ Garantía Oficial 30 Días',
    zoom: imageZoom.value,
    panX: imageOffsetX.value,
    panY: imageOffsetY.value
  }
}

function handleOpenReels() {
  const payload = getReelsPayload()
  emit('openReels', payload)
}

// ==========================================
// DESCARGA DE FLYER INFALIBLE CON DOBLE MOTOR
// Prioriza Canvas 2D Studio (HD Nativo 1080p sin recortes)
// ==========================================
const downloadFlyerImage = async () => {
  if (flyerProductType.value === 'physical' && !isPhysicalFlyerAvailable.value) {
    toast.add('La tienda física está desactivada. No se pueden generar flyers para productos físicos.', 'warning')
    return
  }
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

// Texto comercial atractivo autogenerado con emojis, garantía y enlace oficial
const attractiveFlyerCopy = computed(() => {
  const isGamer = flyerProductType.value === 'gaming'
  const selGame = selectedGamingProduct.value
  return generateAttractiveShareCopy({
    type: isGamer ? 'gaming' : (flyerProductType.value === 'physical' ? 'physical' : (flyerProductType.value === 'combo' ? 'combo' : 'streaming')),
    title: displayTitle.value,
    subtitle: displaySubtitle.value,
    price: Number(displayPriceNumeric.value || 0),
    originalPrice: Number(displayOriginalPrice.value || 0),
    platform: isGamer ? (selGame?.platform || selectedGamingPlatform.value) : (selectedPlatform.value || ''),
    license: isGamer ? selectedGamingLicense.value : '',
    storeName: effectiveStoreLabel.value,
    phone: effectiveContactPhone.value,
    url: fullProductReferralUrl.value
  })
})

const copyAttractiveFlyerCopy = async () => {
  try {
    await navigator.clipboard.writeText(attractiveFlyerCopy.value)
    toast.add('📋 ¡Texto promocional atractivo copiado para WhatsApp o Redes!', 'success')
  } catch {
    toast.add(attractiveFlyerCopy.value, 'info')
  }
}

const shareFlyerWithText = async () => {
  if (flyerProductType.value === 'physical' && !isPhysicalFlyerAvailable.value) {
    toast.add('La tienda física está desactivada.', 'warning')
    return
  }
  const shareText = attractiveFlyerCopy.value
  try {
    isExporting.value = true
    let blob = null
    try {
      blob = await renderCanvasFlyerBlob()
    } catch (_) {}

    if (blob && typeof navigator !== 'undefined' && navigator.share && navigator.canShare) {
      const safeName = displayTitle.value.toLowerCase().replace(/[^a-z0-9]/g, '_').slice(0, 24) || 'flyer'
      const file = new File([blob], `flyer_${safeName}.png`, { type: 'image/png' })
      if (navigator.canShare({ files: [file] })) {
        await navigator.share({
          title: displayTitle.value,
          text: shareText,
          files: [file]
        })
        toast.add('✅ ¡Flyer y texto compartidos con éxito!', 'success')
        return
      }
    }
    // Fallback WhatsApp directo con texto atractivo
    window.open(`https://api.whatsapp.com/send?text=${encodeURIComponent(shareText)}`, '_blank')
    toast.add('📲 WhatsApp abierto con el texto promocional listo', 'success')
  } catch (err) {
    if (err?.name !== 'AbortError') {
      window.open(`https://api.whatsapp.com/send?text=${encodeURIComponent(shareText)}`, '_blank')
    }
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
  text += `🏪 *${effectiveStoreLabel.value}*`
  if (effectiveContactPhone.value) {
    text += `\n📲 *WhatsApp:* ${effectiveContactPhone.value}`
  }

  if (navigator?.clipboard?.writeText) {
    navigator.clipboard.writeText(text).then(() => {
      const isReseller = effectiveResellerRef.value !== 'DIRECT'
      toast.add(isReseller ? '¡Texto con tu enlace y WhatsApp de revendedor copiado!' : '¡Texto promocional con enlace directo copiado! Pégalo en WhatsApp o Redes.', 'success')
    })
  }
}

// Compartir a WhatsApp
const shareToWhatsAppStatus = () => {
  if (flyerProductType.value === 'physical' && !isPhysicalFlyerAvailable.value) {
    toast.add('La tienda física está desactivada. No se puede compartir la oferta física.', 'warning')
    return
  }
  let text = `🔥 *${headlineBadge.value}* 🔥\n\n` +
    `🎁 *${displayTitle.value}*\n` +
    `📌 ${displaySubtitle.value}\n`

  if (displayOriginalPrice.value && Number(displayOriginalPrice.value) > Number(displayPriceNumeric.value)) {
    text += `💰 *PROMO:* *$${displayPrice.value} ARS* ~(Antes $${Number(displayOriginalPrice.value).toLocaleString()})~\n\n`
  } else {
    text += `💰 *PRECIO:* *$${displayPrice.value} ARS*\n\n`
  }

  text += `✅ ${bullet1.value}\n`
  text += `✅ ${bullet2.value}\n`
  text += `✅ ${bullet3.value}\n\n`
  text += `🌐 *Pide aquí:* ${fullProductReferralUrl.value}`
  if (effectiveContactPhone.value) {
    text += `\n📲 *WhatsApp:* ${effectiveContactPhone.value}`
  }

  const waUrl = `https://api.whatsapp.com/send?text=${encodeURIComponent(text)}`
  window.open(waUrl, '_blank')
}
</script>

<template>
  <div v-if="show" class="fixed inset-0 z-[80] flex items-center justify-center p-3 sm:p-5 pt-16 sm:pt-6 pb-6 overflow-y-auto overscroll-contain bg-black/85 backdrop-blur-md animate-in fade-in duration-200">
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
        <button @click="emit('close')" type="button" class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-slate-800/90 hover:bg-slate-700 active:scale-95 text-slate-300 hover:text-white border border-slate-700/80 shadow-md flex items-center justify-center text-sm sm:text-base font-bold transition-all shrink-0 cursor-pointer ml-auto" title="Cerrar modal">✕</button>
      </div>

      <!-- CONTENIDO PRINCIPAL: 2 COLUMNAS (CONFIGURACIÓN + PREVISUALIZACIÓN) -->
      <div class="p-4 sm:p-5 overflow-y-auto grid grid-cols-1 lg:grid-cols-12 gap-5 flex-1 custom-scrollbar">
        
        <!-- SELECTOR DE VISTA MÓVIL STICKY (< lg) -->
        <div class="lg:hidden col-span-1 flex items-center p-1 bg-slate-950 rounded-2xl border border-slate-800 sticky top-0 z-30 shadow-md">
          <button
            type="button"
            @click="mobileTab = 'preview'"
            :class="['flex-1 py-2 px-3 rounded-xl font-black text-xs transition flex items-center justify-center gap-1.5 cursor-pointer', mobileTab === 'preview' ? 'bg-amber-400 text-slate-950 shadow' : 'text-slate-400 hover:text-white']"
          >
            <span>👁️</span>
            <span>Ver Flyer / Previa</span>
          </button>
          <button
            type="button"
            @click="mobileTab = 'editor'"
            :class="['flex-1 py-2 px-3 rounded-xl font-black text-xs transition flex items-center justify-center gap-1.5 cursor-pointer', mobileTab === 'editor' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-white']"
          >
            <span>🛠️</span>
            <span>Personalizar Opciones</span>
          </button>
        </div>

        <!-- COLUMNA IZQUIERDA: CONTROLES DE GENERACIÓN (7 cols) -->
        <div :class="['lg:col-span-7 space-y-4', mobileTab === 'editor' ? 'block' : 'hidden lg:block']">
          
          <!-- SELECTOR DE TIPO DE FLYER: MODOS OFICIALES -->
          <div v-if="!clientOnlyPhysical || !isPhysicalFlyerAvailable" class="space-y-1.5">
            <label class="block font-bold uppercase text-slate-400 text-[11px]">
              🎯 Modo de Flyer a Promocionar
            </label>
            <div :class="['grid gap-1.5 p-1 bg-slate-950 rounded-2xl border border-slate-800', isPhysicalFlyerAvailable ? 'grid-cols-2 sm:grid-cols-4' : 'grid-cols-3']">
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
                @click="flyerProductType = 'gaming'"
                :class="['py-2 px-2 rounded-xl font-black text-xs transition-all flex flex-col items-center justify-center gap-1 text-center', flyerProductType === 'gaming' ? 'bg-indigo-600 text-white shadow-md font-black' : 'text-slate-400 hover:text-white hover:bg-slate-900']"
              >
                <span class="text-base">🎮</span>
                <span class="leading-tight">Videojuegos Digitales</span>
              </button>

              <button 
                v-if="isPhysicalFlyerAvailable"
                type="button"
                @click="flyerProductType = 'physical'"
                :class="['py-2 px-2 rounded-xl font-black text-xs transition-all flex flex-col items-center justify-center gap-1 text-center', flyerProductType === 'physical' ? 'bg-sky-500 text-slate-950 shadow-md font-black' : 'text-slate-400 hover:text-white hover:bg-slate-900']"
              >
                <span class="text-base">📦</span>
                <span class="leading-tight">Productos Físicos</span>
              </button>
            </div>
          </div>

          <!-- ENCABEZADO SIMPLIFICADO PARA CLIENTES (SOLO PRODUCTOS FÍSICOS CUANDO ESTÁN ACTIVOS) -->
          <div v-else-if="clientOnlyPhysical && isPhysicalFlyerAvailable" class="flex items-center justify-between p-3 bg-[#020617] rounded-2xl border border-amber-500/30">
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

            <!-- SUITE UNIFICADA 9:16 (STORY / REEL 30 FPS) -->
            <div v-if="flyerFormat === 'story'" class="mt-2.5 p-3 bg-gradient-to-r from-purple-950/70 via-slate-950 to-indigo-950/70 rounded-2xl border border-purple-500/40 flex flex-col sm:flex-row items-center justify-between gap-2.5">
              <div class="flex items-center gap-2.5 min-w-0">
                <span class="w-8 h-8 rounded-xl bg-purple-600/30 border border-purple-400/40 flex items-center justify-center text-base shrink-0">
                  🎬
                </span>
                <div class="min-w-0">
                  <div class="font-black text-xs text-purple-200">Suite 9:16 Unificada (Story &amp; Reel)</div>
                  <div class="text-[10px] text-slate-400 truncate">Elige flyer estático para estados o video animado 30 FPS</div>
                </div>
              </div>
              <button
                type="button"
                @click="handleOpenReels"
                class="w-full sm:w-auto px-3.5 py-2 rounded-xl bg-gradient-to-r from-pink-500 via-purple-600 to-indigo-600 hover:from-pink-400 hover:to-indigo-500 text-white font-black text-xs shadow-lg flex items-center justify-center gap-1.5 transition active:scale-95 shrink-0 cursor-pointer"
                title="Abrir estudio de animación 30 FPS para este flyer"
              >
                <span>✨ Animar como Reel 9:16</span>
                <span class="text-[9px] bg-white/20 px-1.5 py-0.5 rounded-full font-bold">30 FPS</span>
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

          <!-- MODO 4: VIDEOJUEGOS DIGITALES -->
          <div v-else-if="flyerProductType === 'gaming'" class="space-y-3 bg-[#020617] p-3.5 rounded-2xl border border-indigo-500/30">
            <div class="flex items-center justify-between">
              <label class="block font-black uppercase text-indigo-300 text-xs flex items-center gap-1.5">
                <span>🎮</span> Catálogo de Videojuegos Multiplataforma ({{ gamingProductsList.length }})
              </label>
              <span class="text-[10px] font-mono text-indigo-400 font-bold bg-indigo-500/10 px-2 py-0.5 rounded border border-indigo-500/20">
                StreamePro Oficial
              </span>
            </div>

            <!-- Buscador y filtro de categoría -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
              <div>
                <input 
                  v-model="gamingSearch" 
                  type="text" 
                  placeholder="Buscar juego (FC 25, GTA, Spider-Man)..."
                  class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2 text-white outline-none focus:border-indigo-400 text-xs"
                >
              </div>
              <div>
                <select v-model="selectedGamingCat" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2 text-white outline-none focus:border-indigo-400 font-bold text-xs">
                  <option value="All">Todas las Categorías ({{ gamingProductsList.length }})</option>
                  <option v-for="cat in adminStore.gamingCategories" :key="cat" :value="cat">
                    {{ cat }}
                  </option>
                </select>
              </div>
            </div>

            <!-- Selector de Juego -->
            <div>
              <div class="flex justify-between items-center mb-1">
                <label class="block font-bold uppercase text-slate-400 text-[11px]">Seleccionar Videojuego</label>
                <span class="text-[10px] font-mono text-indigo-300">{{ filteredGamingProducts.length.toLocaleString() }} coincidencias</span>
              </div>
              <select v-model="selectedGamingId" class="w-full bg-slate-900 border border-indigo-500/40 rounded-xl p-2.5 text-white font-bold outline-none focus:border-indigo-400 text-xs">
                <option v-for="g in displayedGamingSelectOptions" :key="g.id" :value="g.id">
                  {{ g.title }} ({{ g.platform }}) — Pri: ${{ g.effectivePrimaryPrice?.toLocaleString() }}{{ g.effectiveSecondaryPrice ? ' | Sec: $' + g.effectiveSecondaryPrice.toLocaleString() : '' }}
                </option>
              </select>
            </div>

            <!-- Selector de Licencia (Primaria / Secundaria) & Plataforma -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
              <div>
                <label class="block font-bold uppercase text-slate-400 mb-1 text-[11px]">Tipo de Licencia</label>
                <div class="grid grid-cols-2 gap-1.5 p-1 bg-slate-950 rounded-xl border border-slate-800">
                  <button 
                    type="button" 
                    @click="selectedGamingLicense = 'primary'" 
                    :class="['py-1.5 px-2 rounded-lg font-bold text-xs transition text-center cursor-pointer active:scale-95', selectedGamingLicense === 'primary' ? 'bg-indigo-600 text-white font-black shadow' : 'text-slate-400 hover:text-white']"
                  >
                    ⭐️ Primaria
                  </button>
                  <button 
                    v-if="selectedGameSupportsSecondary"
                    type="button" 
                    @click="selectedGamingLicense = 'secondary'" 
                    :class="['py-1.5 px-2 rounded-lg font-bold text-xs transition text-center cursor-pointer active:scale-95', selectedGamingLicense === 'secondary' ? 'bg-indigo-600 text-white font-black shadow' : 'text-slate-400 hover:text-white']"
                  >
                    ⚡️ Secundaria
                  </button>
                </div>
              </div>

              <div>
                <label class="block font-bold uppercase text-slate-400 mb-1 text-[11px]">Consola / Plataforma</label>
                <div class="flex flex-wrap gap-1.5 p-1 bg-slate-950 rounded-xl border border-slate-800">
                  <button 
                    v-for="plat in availableGamePlatforms"
                    :key="plat"
                    type="button" 
                    @click="selectedGamingPlatform = plat" 
                    :class="['flex-1 py-1.5 px-2 rounded-lg font-bold text-xs transition text-center whitespace-nowrap cursor-pointer active:scale-95', selectedGamingPlatform === plat ? 'bg-indigo-600 text-white font-black shadow' : 'text-slate-400 hover:text-white']"
                  >
                    {{ plat }}
                  </button>
                </div>
              </div>
            </div>

            <!-- ETIQUETA OFICIAL INTELIGENTE (PS4/PS5 PRIMARIA/SECUNDARIA, PS3 DIGITAL) -->
            <div class="space-y-2 bg-slate-950/90 p-3 rounded-2xl border border-cyan-500/30">
              <div class="flex flex-wrap items-center justify-between gap-2">
                <span class="text-[11px] font-black text-cyan-300 flex items-center gap-1.5">
                  <span>🏷️</span> Etiqueta Oficial de Licencia
                </span>
                <span v-if="detectedGamingBadgeInfo" class="text-[10px] font-mono font-bold text-emerald-300 bg-emerald-500/15 px-2 py-0.5 rounded-full border border-emerald-500/30">
                  Auto: {{ detectedGamingBadgeInfo.label }}
                </span>
              </div>

              <div class="grid grid-cols-2 sm:grid-cols-3 gap-1.5">
                <button
                  type="button"
                  @click="gamingBadgeOverride = 'auto'"
                  :class="['py-1.5 px-2 rounded-lg font-bold text-[10px] transition cursor-pointer border', gamingBadgeOverride === 'auto' ? 'bg-cyan-600/30 border-cyan-400 text-white' : 'bg-slate-900 border-slate-800 text-slate-400 hover:text-white']"
                >
                  🤖 Auto Inteligente
                </button>
                <button
                  v-for="b in GAMING_LICENSE_BADGES_LIST"
                  :key="b.key"
                  type="button"
                  @click="gamingBadgeOverride = b.key"
                  :class="['py-1.5 px-2 rounded-lg font-bold text-[10px] transition cursor-pointer border truncate', gamingBadgeOverride === b.key ? 'bg-indigo-600 border-indigo-400 text-white font-black' : 'bg-slate-900 border-slate-800 text-slate-300 hover:text-white']"
                >
                  {{ b.label }}
                </button>
                <button
                  type="button"
                  @click="gamingBadgeOverride = 'none'"
                  :class="['py-1.5 px-2 rounded-lg font-bold text-[10px] transition cursor-pointer border', gamingBadgeOverride === 'none' ? 'bg-rose-600/30 border-rose-400 text-rose-200' : 'bg-slate-900 border-slate-800 text-slate-500 hover:text-white']"
                >
                  🚫 Ocultar
                </button>
              </div>

              <div class="flex flex-wrap items-center justify-between gap-2 pt-1 border-t border-slate-800/80">
                <span class="text-[10px] text-slate-400 font-bold uppercase">Posición estratégica:</span>
                <div class="flex flex-wrap gap-1">
                  <button
                    type="button"
                    @click="gamingBadgePosition = 'main_top_left'"
                    :class="['px-2 py-1 rounded-lg text-[10px] font-bold cursor-pointer', gamingBadgePosition === 'main_top_left' ? 'bg-cyan-500 text-slate-950 font-black' : 'bg-slate-900 text-slate-400 hover:text-white']"
                  >
                    ↖️ Sup. Izq. Marco
                  </button>
                  <button
                    type="button"
                    @click="gamingBadgePosition = 'frame_top'"
                    :class="['px-2 py-1 rounded-lg text-[10px] font-bold cursor-pointer', gamingBadgePosition === 'frame_top' ? 'bg-cyan-500 text-slate-950 font-black' : 'bg-slate-900 text-slate-400 hover:text-white']"
                  >
                    ↖️ Esq. Portada
                  </button>
                  <button
                    type="button"
                    @click="gamingBadgePosition = 'frame_bottom'"
                    :class="['px-2 py-1 rounded-lg text-[10px] font-bold cursor-pointer', gamingBadgePosition === 'frame_bottom' ? 'bg-cyan-500 text-slate-950 font-black' : 'bg-slate-900 text-slate-400 hover:text-white']"
                  >
                    ⬇️ Borde Portada
                  </button>
                  <button
                    type="button"
                    @click="gamingBadgePosition = 'pill_top'"
                    :class="['px-2 py-1 rounded-lg text-[10px] font-bold cursor-pointer', gamingBadgePosition === 'pill_top' ? 'bg-cyan-500 text-slate-950 font-black' : 'bg-slate-900 text-slate-400 hover:text-white']"
                  >
                    📌 Sobre Recuadro
                  </button>
                </div>
              </div>
            </div>

            <!-- Preview Card del Juego Seleccionado -->
            <div v-if="selectedGamingProduct" class="flex items-center gap-3 bg-slate-950 p-2.5 rounded-xl border border-indigo-500/20">
              <img :src="effectiveCoverImage || selectedGamingProduct.image" :alt="selectedGamingProduct.title" class="w-12 h-14 object-cover rounded-lg border border-slate-700 shrink-0">
              <div class="flex-1 min-w-0">
                <h5 class="text-xs font-bold text-white truncate">{{ selectedGamingProduct.title }}</h5>
                <div class="flex items-center gap-2 mt-0.5">
                  <span class="text-indigo-400 font-mono font-bold text-xs">
                    {{ selectedGamingLicense === 'primary' ? '⭐️ Primaria' : '⚡️ Secundaria' }}: ${{ (selectedGamingLicense === 'primary' ? selectedGamingProduct.effectivePrimaryPrice : selectedGamingProduct.effectiveSecondaryPrice)?.toLocaleString() }} ARS
                  </span>
                  <span class="text-[10px] text-slate-400">({{ selectedGamingPlatform }})</span>
                </div>
              </div>
            </div>

            <!-- HERRAMIENTAS DE AJUSTE MANUAL DE IMAGEN EN RECUADRO -->
            <div class="space-y-2.5 bg-slate-950/90 p-3 rounded-2xl border border-indigo-500/30">
              <div class="flex flex-wrap items-center justify-between gap-2 text-[11px] font-bold">
                <span class="text-indigo-300 flex items-center gap-1.5">
                  <span>🖼️</span> Ajuste Manual de Imagen en Recuadro
                </span>
                <div class="flex items-center gap-1.5">
                  <button
                    type="button"
                    @click="triggerCustomCoverUpload"
                    class="px-2 py-1 rounded-lg bg-sky-600/20 hover:bg-sky-600 text-sky-300 hover:text-white border border-sky-500/30 text-[10px] font-bold cursor-pointer"
                    title="Subir imagen o banner propio desde tu equipo"
                  >
                    📁 Imagen Propia
                  </button>
                  <button
                    v-if="customUploadImageUrl"
                    type="button"
                    @click="customUploadImageUrl = ''"
                    class="px-2 py-1 rounded-lg bg-slate-800 text-amber-300 text-[10px] font-bold cursor-pointer"
                  >
                    ↺ Original
                  </button>
                  <button
                    type="button"
                    @click="resetImageAdjustments"
                    class="px-2 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-[10px] font-bold cursor-pointer"
                    title="Centrar y restaurar zoom al 100%"
                  >
                    ⌖ Centrar
                  </button>
                </div>
                <input ref="customCoverInputRef" type="file" accept="image/*" class="hidden" @change="handleCustomCoverUpload">
              </div>

              <div class="grid grid-cols-2 gap-1.5">
                <button 
                  type="button" 
                  @click="flyerImageFit = 'contain'" 
                  :class="['py-1.5 px-2 rounded-lg font-bold text-xs transition text-center flex items-center justify-center gap-1 cursor-pointer', flyerImageFit === 'contain' ? 'bg-indigo-600 text-white font-black shadow' : 'bg-slate-900 border border-slate-800 text-slate-400 hover:text-white']"
                  title="Portada centrada al 100% sin recortar sobre fondo ambiental"
                >
                  <span>📐</span> Centrada Completa
                </button>
                <button 
                  type="button" 
                  @click="flyerImageFit = 'cover'" 
                  :class="['py-1.5 px-2 rounded-lg font-bold text-xs transition text-center flex items-center justify-center gap-1 cursor-pointer', flyerImageFit === 'cover' ? 'bg-indigo-600 text-white font-black shadow' : 'bg-slate-900 border border-slate-800 text-slate-400 hover:text-white']"
                  title="La imagen rellena todo el marco de portada"
                >
                  <span>🎯</span> Rellenar Marco
                </button>
              </div>

              <!-- Controles de Zoom y Posición X / Y (también puedes arrastrar la vista previa) -->
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 pt-1 text-[10px]">
                <div class="bg-slate-900/70 p-2 rounded-xl border border-slate-800">
                  <div class="flex justify-between font-bold text-slate-300 mb-1">
                    <span>🔍 Zoom</span>
                    <span class="font-mono text-cyan-300">{{ Math.round(imageZoom * 100) }}%</span>
                  </div>
                  <input v-model.number="imageZoom" type="range" min="0.6" max="2.4" step="0.05" class="w-full accent-cyan-400 cursor-pointer">
                </div>
                <div class="bg-slate-900/70 p-2 rounded-xl border border-slate-800">
                  <div class="flex justify-between font-bold text-slate-300 mb-1">
                    <span>↔️ Posición X</span>
                    <span class="font-mono text-cyan-300">{{ imageOffsetX > 0 ? '+' + Math.round(imageOffsetX) : Math.round(imageOffsetX) }}%</span>
                  </div>
                  <input v-model.number="imageOffsetX" type="range" min="-60" max="60" step="1" class="w-full accent-cyan-400 cursor-pointer">
                </div>
                <div class="bg-slate-900/70 p-2 rounded-xl border border-slate-800">
                  <div class="flex justify-between font-bold text-slate-300 mb-1">
                    <span>↕️ Posición Y</span>
                    <span class="font-mono text-cyan-300">{{ imageOffsetY > 0 ? '+' + Math.round(imageOffsetY) : Math.round(imageOffsetY) }}%</span>
                  </div>
                  <input v-model.number="imageOffsetY" type="range" min="-60" max="60" step="1" class="w-full accent-cyan-400 cursor-pointer">
                </div>
              </div>
              <p class="text-[10px] text-slate-400">
                💡 Tip: También puedes hacer click/tocar directamente sobre la portada en la vista previa y <strong class="text-cyan-300">arrastrarla</strong> para encuadrarla al milímetro.
              </p>
            </div>
          </div>

          <!-- MODO 3: PRODUCTOS FÍSICOS (CUANDO LA TIENDA FÍSICA ESTÁ ACTIVA) -->
          <div v-else-if="flyerProductType === 'physical' && isPhysicalFlyerAvailable" class="space-y-3 bg-[#020617] p-3.5 rounded-2xl border border-slate-800">
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

            <!-- Modo de Enfoque y Relleno de Imagen Físico -->
            <div class="space-y-1.5 bg-slate-950/80 p-2.5 rounded-xl border border-amber-500/30">
              <div class="flex items-center justify-between text-[11px] font-bold">
                <span class="text-amber-300 flex items-center gap-1.5">
                  <span>🖼️</span> Enfoque y Relleno de Imagen
                </span>
                <span class="text-[9px] text-emerald-400 font-mono font-bold">100% Sin Espacios Vacíos</span>
              </div>
              <div class="grid grid-cols-2 gap-1.5">
                <button 
                  type="button" 
                  @click="flyerImageFit = 'cover'" 
                  :class="['py-1.5 px-2 rounded-lg font-bold text-xs transition text-center flex items-center justify-center gap-1 cursor-pointer', flyerImageFit === 'cover' ? 'bg-amber-600 text-white font-black shadow' : 'bg-slate-900 border border-slate-800 text-slate-400 hover:text-white']"
                  title="La imagen rellena completamente el visor enfocada en el centro"
                >
                  <span>🎯</span> Enfocado (Rellenar)
                </button>
                <button 
                  type="button" 
                  @click="flyerImageFit = 'contain'" 
                  :class="['py-1.5 px-2 rounded-lg font-bold text-xs transition text-center flex items-center justify-center gap-1 cursor-pointer', flyerImageFit === 'contain' ? 'bg-amber-600 text-white font-black shadow' : 'bg-slate-900 border border-slate-800 text-slate-400 hover:text-white']"
                  title="La imagen se muestra completa sobre un fondo ambiental que cubre todo el espacio"
                >
                  <span>📐</span> Completo + Fondo
                </button>
              </div>
            </div>
          </div>

          <!-- AVISO CUANDO LA TIENDA FÍSICA ESTÁ DESACTIVADA -->
          <div v-else-if="flyerProductType === 'physical' && !isPhysicalFlyerAvailable" class="p-5 bg-amber-950/40 border border-amber-500/30 rounded-2xl text-center space-y-3">
            <span class="text-3xl block">📦⚠️</span>
            <h4 class="text-amber-300 font-black text-sm">Catálogo y Flyers Físicos Desactivados</h4>
            <p class="text-slate-300 text-xs max-w-md mx-auto">
              La tienda de productos físicos se encuentra desactivada actualmente por el administrador. Los flyers para estos artículos están en pausa.
            </p>
            <button 
              type="button" 
              @click="flyerProductType = 'individual'"
              class="px-4 py-2 bg-amber-400 hover:bg-amber-300 text-slate-950 font-black rounded-xl text-xs transition cursor-pointer"
            >
              🎬 Cambiar a Servicio Individual
            </button>
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

          <!-- ENLACE DE REFERENCIA Y TELÉFONO DEL REVENDEDOR OFICIAL -->
          <div class="bg-slate-950/90 p-3 rounded-2xl border border-slate-800 space-y-2.5">
            <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
              <span class="font-bold text-slate-300 flex items-center gap-1.5">
                <span>🔗</span> Enlace & Contacto del Revendedor Oficial:
              </span>
              <span class="text-[10px] font-mono text-amber-400 bg-slate-900 px-2 py-0.5 rounded border border-slate-800">
                Ref: {{ effectiveResellerRef }}
              </span>
            </div>

            <!-- Selector de Revendedor (visible en modo Admin/Directo para armar flyer + link de cualquier revendedor oficial) -->
            <div v-if="!props.resellerRef || props.resellerRef === 'DIRECT'" class="grid grid-cols-1 sm:grid-cols-2 gap-2">
              <div>
                <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Revendedor Oficial Asignado</label>
                <select
                  v-model="selectedFlyerResellerId"
                  class="w-full bg-slate-900 border border-slate-700 rounded-xl px-2.5 py-2 text-xs text-white font-bold outline-none focus:border-cyan-400"
                >
                  <option value="DIRECT">🏪 Tienda Oficial Directa (Sin ref)</option>
                  <option v-for="res in (adminStore.resellers || [])" :key="res.id" :value="res.id">
                    👤 {{ res.storeName || res.name }} ({{ res.id }})
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">📲 Teléfono WhatsApp en Flyer</label>
                <input
                  v-model="customContactPhone"
                  type="text"
                  placeholder="Ej: +54 9 11 1234-5678"
                  class="w-full bg-slate-900 border border-slate-700 rounded-xl px-2.5 py-2 text-xs text-cyan-200 font-mono font-bold outline-none focus:border-cyan-400"
                >
              </div>
            </div>

            <!-- Teléfono Editable Directo (para Revendedor logueado o Cliente referido) -->
            <div v-else>
              <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">📲 WhatsApp visible en Flyer (tu número de pedidos)</label>
              <input
                v-model="customContactPhone"
                type="text"
                placeholder="Ej: +54 9 11 1234-5678"
                class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-cyan-200 font-mono font-bold outline-none focus:border-cyan-400"
              >
            </div>

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
              <input 
                :value="fullProductReferralUrl" 
                readonly 
                class="flex-1 bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-sky-300 font-mono outline-none select-all"
              >
              <div class="flex gap-1.5">
                <button 
                  type="button"
                  @click="copyProductReferralUrl" 
                  class="flex-1 sm:flex-none bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs px-3.5 py-2 rounded-xl transition flex items-center justify-center gap-1 shadow cursor-pointer"
                  title="Copiar URL"
                >
                  <span>📋 Copiar</span>
                </button>
                <button 
                  type="button"
                  @click="previewProductReferralUrl" 
                  class="flex-1 sm:flex-none bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs px-3.5 py-2 rounded-xl transition flex items-center justify-center gap-1 shadow cursor-pointer"
                  title="Abrir tienda en nueva pestaña"
                >
                  <span>👁️ Ver ↗</span>
                </button>
              </div>
            </div>
          </div>

          <!-- CONSULTA O SOLICITUD DE JUEGO MANUAL AL ADMINISTRADOR -->
          <div v-if="flyerProductType === 'gaming'" class="bg-slate-950/90 p-3 rounded-2xl border border-amber-500/30 space-y-2">
            <div class="flex items-center justify-between gap-2">
              <div class="flex items-center gap-2 min-w-0">
                <span class="text-base">📩</span>
                <div class="min-w-0">
                  <h5 class="text-xs font-black text-amber-300 truncate">¿No encuentras el juego o necesitas pedido manual?</h5>
                  <p class="text-[10px] text-slate-400 truncate">Envía una consulta directa al Administrador y recíbe respuesta en tu panel o WhatsApp.</p>
                </div>
              </div>
              <button
                type="button"
                @click="showManualInquiryBox = !showManualInquiryBox"
                class="px-2.5 py-1.5 rounded-xl bg-amber-500/15 hover:bg-amber-500/30 text-amber-300 border border-amber-500/30 text-[10px] font-black cursor-pointer shrink-0 transition"
              >
                {{ showManualInquiryBox ? '▲ Ocultar' : '📝 Consultar / Pedir' }}
              </button>
            </div>

            <div v-if="showManualInquiryBox" class="pt-2 border-t border-slate-800 space-y-2">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <input
                  v-model="manualInquiryTitle"
                  type="text"
                  placeholder="Título del juego buscado o pedido manual..."
                  class="w-full bg-slate-900 border border-slate-700 rounded-xl px-2.5 py-2 text-xs text-white outline-none focus:border-amber-400"
                >
                <div class="grid grid-cols-2 gap-1.5">
                  <select v-model="manualInquiryPlatform" class="bg-slate-900 border border-slate-700 rounded-xl px-2 py-2 text-xs text-white font-bold outline-none">
                    <option value="PS5">PS5</option>
                    <option value="PS4">PS4</option>
                    <option value="PS3">PS3</option>
                    <option value="Xbox">Xbox</option>
                    <option value="Switch">Switch</option>
                    <option value="PC Steam">PC Steam</option>
                  </select>
                  <select v-model="manualInquiryLicense" class="bg-slate-900 border border-slate-700 rounded-xl px-2 py-2 text-xs text-white font-bold outline-none">
                    <option value="Primaria">⭐️ Primaria</option>
                    <option value="Secundaria">⚡️ Secundaria</option>
                    <option value="Digital / Clave">🔑 Completa / Clave</option>
                  </select>
                </div>
              </div>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <input
                  v-model="manualInquiryName"
                  type="text"
                  placeholder="Tu nombre o tienda..."
                  class="w-full bg-slate-900 border border-slate-700 rounded-xl px-2.5 py-2 text-xs text-white outline-none focus:border-amber-400"
                >
                <input
                  v-model="manualInquiryPhone"
                  type="text"
                  placeholder="Tu WhatsApp de respuesta (ej: +54 9...)"
                  class="w-full bg-slate-900 border border-slate-700 rounded-xl px-2.5 py-2 text-xs text-white font-mono outline-none focus:border-amber-400"
                >
              </div>
              <textarea
                v-model="manualInquiryNotes"
                rows="2"
                placeholder="Detalle de tu consulta o pedido manual (edición especial, DLC, presupuesto, etc.)..."
                class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2 text-xs text-white outline-none focus:border-amber-400 resize-none"
              />
              <div class="flex items-center justify-end gap-2">
                <button
                  type="button"
                  @click="submitManualGameInquiry"
                  class="px-3.5 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs cursor-pointer transition shadow"
                >
                  📨 Enviar Consulta al Admin
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

          <!-- BOTÓN DIRECTO: ANIMAR COMO REEL 9:16 (30 FPS) -->
          <div class="pt-2">
            <button 
              @click="handleOpenReels"
              type="button"
              class="w-full bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 hover:from-indigo-500 hover:to-pink-500 text-white font-black py-3 px-4 rounded-xl shadow-lg shadow-indigo-950/60 transition-all flex items-center justify-center gap-2 text-xs cursor-pointer border border-indigo-400/30"
            >
              <span>🎬 Animar como Reel 9:16 (30 FPS)</span>
              <span class="text-[10px] bg-white/20 px-2 py-0.5 rounded-full font-bold">NUEVO</span>
            </button>
          </div>

          <!-- BOTONES DE ACCIÓN -->
          <div class="flex flex-col sm:flex-row gap-2.5 pt-1">
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
        <div :class="['lg:col-span-5 flex flex-col items-center bg-slate-950/90 p-4 rounded-3xl border border-slate-800 space-y-3.5', mobileTab === 'preview' ? 'flex' : 'hidden lg:flex']">
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
              'max-w-[490px] aspect-[16/9]',
              currentTheme.cssContainerBorder
            ]"
          >
            <!-- Capa de Plantilla de Fondo con Filtro Temático -->
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

            <!-- MODO VIDEOJUEGOS DIGITALES: DISEÑO LIMPIO INTEGRADO + ETIQUETA SUP. IZQ. + ENCUADRE CENTRADO -->
            <div v-if="flyerProductType === 'gaming'" class="relative w-full h-full text-white select-none z-10">
              <!-- Marco Metálico Central de Portada con Arrastre Manual Directo -->
              <div
                @pointerdown="startCoverDrag"
                @pointermove="onCoverDrag"
                @pointerup="endCoverDrag"
                @pointercancel="endCoverDrag"
                @pointerleave="endCoverDrag"
                :style="{
                  left: currentGamingLayout.frame.left + '%',
                  top: currentGamingLayout.frame.top + '%',
                  width: currentGamingLayout.frame.width + '%',
                  height: currentGamingLayout.frame.height + '%'
                }"
                :class="['absolute z-20 overflow-hidden bg-slate-950 pointer-events-auto cursor-grab active:cursor-grabbing group', flyerFormat === 'banner' ? 'rounded-xl' : 'rounded-2xl']"
                title="Arrastra la portada para encuadrar manualmente"
              >
                <img
                  v-if="effectiveCoverImage"
                  :src="effectiveCoverImage"
                  :alt="displayTitle"
                  draggable="false"
                  class="absolute inset-0 w-full h-full object-cover filter blur-lg scale-125 brightness-50 saturate-125 pointer-events-none"
                >
                <div class="absolute inset-0 bg-gradient-to-b from-black/25 via-transparent to-black/45 pointer-events-none z-[5]" />
                <img
                  v-if="effectiveCoverImage"
                  :src="effectiveCoverImage"
                  :alt="displayTitle"
                  draggable="false"
                  :style="coverPreviewTransformStyle"
                  :class="[
                    'relative z-10 w-full h-full pointer-events-none transition-transform duration-75 filter drop-shadow-[0_10px_22px_rgba(0,0,0,0.85)]',
                    flyerImageFit === 'contain' ? 'object-contain p-2 sm:p-2.5' : 'object-cover object-center'
                  ]"
                >
                <div v-else class="w-full h-full flex items-center justify-center text-4xl">🎮</div>

                <!-- Sugerencia sutil de arrastre en hover -->
                <div class="absolute bottom-1.5 right-1.5 z-30 opacity-0 group-hover:opacity-100 transition-opacity bg-slate-950/85 text-[8px] font-bold text-cyan-300 px-1.5 py-0.5 rounded pointer-events-none">
                  ✋ Arrastrar
                </div>
              </div>

              <!-- Etiqueta Oficial de Tipo de Licencia (Superior Izquierda desde Recuadro Principal) -->
              <div
                v-if="activeGamingBadgeInfo && gamingBadgeOverride !== 'none'"
                :style="gamingDomBadgeStyle"
                class="absolute z-30 pointer-events-none flex items-center justify-start"
              >
                <img
                  v-if="activeGamingBadgeInfo.imgUrl"
                  :src="activeGamingBadgeInfo.imgUrl"
                  :alt="activeGamingBadgeInfo.label"
                  class="w-full h-auto object-contain drop-shadow-[0_4px_14px_rgba(0,220,255,0.65)]"
                >
                <span
                  v-else
                  class="px-3 py-1 rounded-full bg-gradient-to-r from-blue-900 via-sky-600 to-blue-800 text-[9px] font-black tracking-wide text-white shadow-lg uppercase"
                >
                  {{ activeGamingBadgeInfo.label }}
                </span>
              </div>

              <!-- 1) STORY 9:16 GAMER (Tipografía limpia sin recuadros anexados) -->
              <template v-if="flyerFormat === 'story'">
                <!-- Etiqueta Promocional Sutil -->
                <div class="absolute top-[46.6%] left-[12%] right-[12%] flex justify-center z-20 pointer-events-none">
                  <div :class="['font-black px-3 py-0.5 rounded-full shadow-lg text-[8.5px] uppercase tracking-wider truncate max-w-full', currentTheme.cssBadge]">
                    {{ headlineBadge }}
                  </div>
                </div>
                <!-- Título & Subtítulo dentro del Recuadro Oscuro Oficial (PILL: 54.4%..63.4%) -->
                <div class="absolute top-[54.6%] left-[11%] right-[11%] h-[8.6%] flex flex-col items-center justify-center text-center px-2 z-20 pointer-events-none">
                  <h2 class="font-black text-[11px] sm:text-xs tracking-tight leading-tight line-clamp-2 text-white drop-shadow">{{ displayTitle }}</h2>
                  <p :class="['font-bold text-[8px] font-mono truncate max-w-full mt-0.5', currentTheme.cssText]">{{ displaySubtitle }}</p>
                </div>
                <!-- Zona Inferior Abierta y Esculpida (sin recuadros anexados) -->
                <div class="absolute top-[64.8%] bottom-[8.6%] left-[10%] right-[10%] flex flex-col justify-between items-center text-center z-20 py-1 pointer-events-none">
                  <div class="w-full py-1 px-2 relative">
                    <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,rgba(0,220,255,0.12)_0%,transparent_70%)] pointer-events-none" />
                    <div v-if="displayOriginalPrice && Number(displayOriginalPrice) > Number(displayPriceNumeric)" class="flex items-center justify-center gap-1.5 mb-0.5 relative">
                      <span class="text-slate-400 line-through text-[9px]">Antes: ${{ Number(displayOriginalPrice).toLocaleString() }}</span>
                      <span v-if="calculatedDiscountPct" class="text-emerald-300 font-black text-[8.5px]">-{{ calculatedDiscountPct }}% OFF</span>
                    </div>
                    <div v-else class="text-[8px] uppercase font-bold text-slate-400 tracking-widest relative">PRECIO EXCLUSIVO</div>
                    <div class="flex items-baseline justify-center gap-0.5 relative drop-shadow-[0_0_14px_rgba(0,220,255,0.45)]">
                      <span :class="['text-xs font-black', currentTheme.cssCurrency]">$</span>
                      <span class="font-black tracking-tight text-white font-mono text-2xl sm:text-[26px] leading-none">{{ displayPrice }}</span>
                      <span class="text-[8.5px] font-bold text-slate-400 font-mono">ARS</span>
                    </div>
                    <div :class="['text-[7px] font-bold truncate mt-0.5 relative', currentTheme.cssText]">{{ gamingFlyerGuaranteeText }}</div>
                  </div>
                  <div class="w-2/3 h-px bg-gradient-to-r from-transparent via-cyan-400/50 to-transparent" />
                  <div class="w-full text-left space-y-1 text-[8px] sm:text-[8.5px] font-semibold text-slate-100 px-2">
                    <div class="flex items-center gap-1.5 truncate"><span :class="['font-black shrink-0', currentTheme.cssCheckText]">✓</span><span class="truncate">{{ bullet1 }}</span></div>
                    <div class="flex items-center gap-1.5 truncate"><span :class="['font-black shrink-0', currentTheme.cssCheckText]">✓</span><span class="truncate">{{ bullet2 }}</span></div>
                    <div class="flex items-center gap-1.5 truncate"><span :class="['font-black shrink-0', currentTheme.cssCheckText]">✓</span><span class="truncate">{{ bullet3 }}</span></div>
                  </div>
                  <div v-if="effectiveContactPhone" class="text-[8px] font-mono font-black text-cyan-200 tracking-wide truncate max-w-full drop-shadow">
                    📲 Pedidos WhatsApp: {{ effectiveContactPhone }}
                  </div>
                </div>
              </template>

              <!-- 2) FEED 4:5 GAMER (Texto flotante sobre degradado suave + Precio en recuadro oscuro oficial) -->
              <template v-else-if="flyerFormat === 'feed'">
                <div class="absolute top-[50.8%] h-[14.2%] left-[5%] right-[5%] bg-gradient-to-b from-transparent via-slate-950/85 to-transparent pointer-events-none z-[15]" />
                <div class="absolute top-[51.2%] h-[13.6%] left-[10%] right-[10%] flex flex-col items-center justify-center text-center z-20 pointer-events-none px-2">
                  <div :class="['font-black px-2.5 py-0.5 rounded-full text-[7px] uppercase tracking-wider mb-1 shadow', currentTheme.cssBadge]">
                    {{ headlineBadge }}
                  </div>
                  <h2 class="font-black text-xs sm:text-[13px] leading-tight line-clamp-1 text-white drop-shadow-[0_2px_6px_rgba(0,0,0,0.95)]">{{ displayTitle }}</h2>
                  <p :class="['font-bold text-[7.5px] font-mono truncate max-w-full mt-0.5 drop-shadow', currentTheme.cssText]">{{ displaySubtitle }}</p>
                </div>
                <!-- Precio Promo dentro del Recuadro Oscuro Oficial (65.62%..76.46%) -->
                <div class="absolute top-[65.9%] left-[10.5%] right-[10.5%] h-[10.2%] flex flex-col items-center justify-center text-center z-20 px-2 pointer-events-none">
                  <div v-if="displayOriginalPrice && Number(displayOriginalPrice) > Number(displayPriceNumeric)" class="flex items-center justify-center gap-1.5">
                    <span class="text-slate-400 line-through text-[8px]">Antes: ${{ Number(displayOriginalPrice).toLocaleString() }}</span>
                    <span v-if="calculatedDiscountPct" class="text-emerald-300 font-black text-[7.5px]">-{{ calculatedDiscountPct }}% OFF</span>
                  </div>
                  <div class="flex items-baseline justify-center gap-0.5 leading-none my-0.5 drop-shadow-[0_0_12px_rgba(0,220,255,0.4)]">
                    <span :class="['text-[11px] font-black', currentTheme.cssCurrency]">$</span>
                    <span class="font-black tracking-tight text-white font-mono text-lg sm:text-xl">{{ displayPrice }}</span>
                    <span class="text-[7.5px] font-bold text-slate-400 font-mono">ARS</span>
                  </div>
                  <div :class="['text-[6.5px] font-bold truncate max-w-full', currentTheme.cssText]">{{ gamingFlyerGuaranteeText }}</div>
                </div>
                <!-- Beneficios + WhatsApp limpios debajo del Recuadro Oscuro -->
                <div class="absolute top-[77.4%] bottom-[8.8%] left-[10%] right-[10%] flex flex-col justify-center items-center text-center space-y-1 text-[7.5px] text-slate-200 px-2 z-20 pointer-events-none">
                  <div class="flex items-center justify-center gap-3 w-full truncate">
                    <span class="truncate"><strong :class="currentTheme.cssCheckText">✓</strong> {{ bullet1 }}</span>
                    <span class="hidden sm:inline truncate"><strong :class="currentTheme.cssCheckText">✓</strong> {{ bullet2 }}</span>
                  </div>
                  <div v-if="effectiveContactPhone" class="text-[8px] font-mono text-cyan-200 font-black truncate">
                    📲 Pedidos WhatsApp: {{ effectiveContactPhone }}
                  </div>
                </div>
              </template>

              <!-- 3) SQUARE 1:1 GAMER (Sin recuadros extras sobre portada/pill) -->
              <template v-else-if="flyerFormat === 'square'">
                <div class="absolute top-[54.2%] h-[17.4%] left-[5%] right-[5%] bg-gradient-to-b from-transparent via-slate-950/88 to-transparent pointer-events-none z-[15]" />
                <div class="absolute top-[54.6%] h-[16.8%] left-[10%] right-[10%] flex flex-col items-center justify-center text-center z-20 pointer-events-none px-2">
                  <div :class="['font-black px-2.5 py-0.5 rounded-full text-[7px] uppercase tracking-wider mb-1 shadow', currentTheme.cssBadge]">
                    {{ headlineBadge }}
                  </div>
                  <h2 class="font-black text-xs sm:text-[13px] leading-tight line-clamp-2 text-white drop-shadow-[0_2px_8px_rgba(0,0,0,0.95)]">{{ displayTitle }}</h2>
                  <p :class="['font-bold text-[7.5px] font-mono truncate max-w-full mt-0.5', currentTheme.cssText]">{{ displaySubtitle }}</p>
                </div>
                <!-- Precio Promo dentro del Recuadro Oscuro Oficial (72.17%..83.69%) -->
                <div class="absolute top-[72.4%] left-[9.8%] right-[9.8%] h-[11.0%] flex flex-col items-center justify-center text-center z-20 px-2 pointer-events-none">
                  <div v-if="displayOriginalPrice && Number(displayOriginalPrice) > Number(displayPriceNumeric)" class="flex items-center justify-center gap-1.5">
                    <span class="text-slate-400 line-through text-[7.5px]">Antes: ${{ Number(displayOriginalPrice).toLocaleString() }}</span>
                    <span v-if="calculatedDiscountPct" class="text-emerald-300 font-black text-[7px]">-{{ calculatedDiscountPct }}% OFF</span>
                  </div>
                  <div class="flex items-baseline justify-center gap-0.5 leading-none my-0.5 drop-shadow-[0_0_12px_rgba(0,220,255,0.4)]">
                    <span :class="['text-[10px] font-black', currentTheme.cssCurrency]">$</span>
                    <span class="font-black tracking-tight text-white font-mono text-base sm:text-lg">{{ displayPrice }}</span>
                    <span class="text-[7px] font-bold text-slate-400 font-mono">ARS</span>
                  </div>
                  <div :class="['text-[6.5px] font-bold truncate max-w-full', currentTheme.cssText]">{{ gamingFlyerGuaranteeText }}</div>
                </div>
                <!-- Franja inferior limpia -->
                <div class="absolute top-[84.3%] bottom-[8.8%] left-[10%] right-[10%] flex items-center justify-between gap-2 text-[7px] text-slate-200 px-1.5 z-20 pointer-events-none">
                  <span class="truncate"><strong :class="currentTheme.cssCheckText">✓</strong> {{ bullet1 }}</span>
                  <span v-if="effectiveContactPhone" class="font-mono font-black text-cyan-200 shrink-0">📲 {{ effectiveContactPhone }}</span>
                </div>
              </template>

              <!-- 4) BANNER WEB 16:9 GAMER (Tipografía integrada en zona inferior izq. sin caja con bordes) -->
              <template v-else>
                <div class="absolute top-[67.5%] bottom-[8%] left-[4%] w-[47.5%] bg-gradient-to-r from-slate-950/90 via-slate-950/75 to-transparent pointer-events-none z-[15]" />
                <div class="absolute top-[68.8%] bottom-[9.8%] left-[7.2%] w-[43.0%] flex flex-col justify-center text-left z-20 pointer-events-none px-1.5">
                  <div class="flex items-center gap-1.5 mb-0.5">
                    <span :class="['text-[6.5px] font-black px-2 py-0.5 rounded-full uppercase truncate shadow', currentTheme.cssBadge]">{{ headlineBadge }}</span>
                  </div>
                  <h2 class="text-[11px] font-black text-white line-clamp-1 leading-tight drop-shadow-[0_2px_6px_rgba(0,0,0,0.95)]">{{ displayTitle }}</h2>
                  <div :class="['text-[6.5px] font-bold font-mono truncate mt-0.5', currentTheme.cssText]">{{ displaySubtitle }}</div>
                  <div class="text-[6.5px] text-slate-200 truncate mt-0.5"><strong :class="currentTheme.cssCheckText">✓</strong> {{ bullet1 }}</div>
                </div>
                <!-- Precio Promo dentro del Recuadro Oscuro Derecho Oficial (51.56%..94.53%, 72.33%..84.24%) -->
                <div class="absolute top-[72.6%] left-[52.0%] w-[42.2%] h-[11.2%] flex flex-col items-center justify-center text-center z-20 px-2 pointer-events-none">
                  <div class="flex items-baseline justify-center gap-1.5 leading-none">
                    <span v-if="displayOriginalPrice && Number(displayOriginalPrice) > Number(displayPriceNumeric)" class="text-[7px] text-slate-400 line-through">${{ Number(displayOriginalPrice).toLocaleString() }}</span>
                    <span v-if="calculatedDiscountPct" class="text-[6.5px] text-emerald-300 font-black">-{{ calculatedDiscountPct }}% OFF</span>
                  </div>
                  <div class="flex items-baseline justify-center gap-0.5 leading-none drop-shadow-[0_0_10px_rgba(0,220,255,0.4)]">
                    <span :class="['font-bold text-[9px]', currentTheme.cssCurrency]">$</span>
                    <span class="font-black text-sm sm:text-[15px] text-white font-mono">{{ displayPrice }}</span>
                    <span class="text-[6.5px] font-bold text-slate-400 font-mono">ARS</span>
                  </div>
                  <div :class="['text-[6px] font-bold truncate max-w-full mt-0.5', currentTheme.cssText]">{{ gamingFlyerGuaranteeText }}</div>
                </div>
                <div v-if="effectiveContactPhone" class="absolute top-[85.0%] left-[52.0%] w-[42.2%] text-center text-[7px] font-mono text-cyan-200 font-black truncate z-20 pointer-events-none">
                  📲 WhatsApp Pedidos: {{ effectiveContactPhone }}
                </div>
              </template>
            </div>

            <!-- MODOS STREAMING / COMBO / FÍSICO (SIN CAMBIOS) -->
            <div v-else class="relative w-full h-full text-white pointer-events-none select-none z-10">
              
              <!-- 1.1 STORY (9:16) -->
              <div v-if="flyerFormat === 'story'" class="relative w-full h-full">
                <!-- Visor Window -->
                <div class="absolute top-[13.6%] left-[18.3%] w-[63.3%] h-[28.3%] flex items-center justify-center p-1.5 z-10">
                  <div v-if="flyerProductType === 'physical'" :class="['w-full h-full rounded-2xl flex items-center justify-center shadow-xl border overflow-hidden relative group bg-slate-900', currentTheme.cssBorder]">
                    <img 
                      :src="effectiveCoverImage" 
                      :alt="displayTitle" 
                      class="absolute inset-0 w-full h-full object-cover filter blur-lg scale-125 opacity-40 pointer-events-none"
                    >
                    <img 
                      :src="effectiveCoverImage" 
                      :alt="displayTitle" 
                      :style="coverPreviewTransformStyle"
                      :class="[
                        'relative z-10 w-full h-full filter drop-shadow-2xl transition-transform duration-75',
                        flyerImageFit === 'contain' ? 'object-contain p-2' : 'object-cover object-center'
                      ]"
                    >
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
                      <span class="text-slate-400 line-through text-[9px]">Antes: ${{ Number(displayOriginalPrice).toLocaleString() }}</span>
                      <span v-if="calculatedDiscountPct" class="bg-emerald-500/20 text-emerald-300 font-black text-[8px] px-1 rounded">-{{ calculatedDiscountPct }}%</span>
                    </div>
                    <div v-else class="text-[8px] uppercase font-bold text-slate-400 tracking-wider">PRECIO EXCLUSIVO</div>
                    <div class="flex items-baseline justify-center gap-0.5">
                      <span :class="['text-xs font-black', currentTheme.cssCurrency]">$</span>
                      <span class="font-black tracking-tight text-white font-mono text-xl sm:text-2xl">{{ displayPrice }}</span>
                      <span class="text-[8px] font-bold text-slate-400 font-mono">ARS</span>
                    </div>
                    <div :class="['text-[7px] font-bold truncate', currentTheme.cssText]">
                      {{ flyerProductType === 'physical' ? '🚚 Envío a Todo el País · Garantía Oficial 100%' : '⚡ Entrega 60/90min · Garantía Oficial 100%' }}
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
                  <div v-if="flyerProductType === 'physical'" :class="['w-full h-full rounded-xl flex items-center justify-center shadow-xl border overflow-hidden relative group bg-slate-900', currentTheme.cssBorder]">
                    <img 
                      :src="effectiveCoverImage" 
                      :alt="displayTitle" 
                      class="absolute inset-0 w-full h-full object-cover filter blur-lg scale-125 opacity-40 pointer-events-none"
                    >
                    <img 
                      :src="effectiveCoverImage" 
                      :alt="displayTitle" 
                      :style="coverPreviewTransformStyle"
                      :class="[
                        'relative z-10 w-full h-full filter drop-shadow-xl transition-transform duration-75',
                        flyerImageFit === 'contain' ? 'object-contain p-1.5' : 'object-cover object-center'
                      ]"
                    >
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
                      <span class="text-slate-400 line-through text-[8px]">Antes: ${{ Number(displayOriginalPrice).toLocaleString() }}</span>
                      <span v-if="calculatedDiscountPct" class="bg-emerald-500/20 text-emerald-300 font-black text-[7px] px-1 rounded">-{{ calculatedDiscountPct }}%</span>
                    </div>
                    <div v-else class="text-[7px] uppercase font-bold text-slate-400">PRECIO EXCLUSIVO</div>
                    <div class="flex items-baseline justify-center gap-0.5">
                      <span :class="['text-[10px] font-black', currentTheme.cssCurrency]">$</span>
                      <span class="font-black tracking-tight text-white font-mono text-base sm:text-lg">{{ displayPrice }}</span>
                      <span class="text-[7px] font-bold text-slate-400 font-mono">ARS</span>
                    </div>
                    <div :class="['text-[6px] font-bold truncate', currentTheme.cssText]">
                      {{ flyerProductType === 'physical' ? '🚚 Envío a Todo el País · Garantía Oficial 100%' : '⚡ Entrega 60/90min · Garantía Oficial 100%' }}
                    </div>
                  </div>
                  <div class="w-full text-center py-0.5 text-[8px] font-medium text-slate-200 px-1">
                    <div class="flex items-center justify-center gap-2.5 truncate">
                      <span class="truncate"><span :class="['font-bold', currentTheme.cssCheckText]">✓</span> {{ bullet1 }}</span>
                      <span class="truncate"><span :class="['font-bold', currentTheme.cssCheckText]">✓</span> {{ bullet2 }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- 1.3 SQUARE (1:1) -->
              <div v-else-if="flyerFormat === 'square'" class="relative w-full h-full">
                <!-- Visor Window -->
                <div class="absolute top-[13.7%] left-[32.7%] w-[34.6%] h-[34.8%] flex items-center justify-center p-1 z-10">
                  <div v-if="flyerProductType === 'physical'" :class="['w-full h-full rounded-xl flex items-center justify-center shadow-xl border overflow-hidden relative group bg-slate-900', currentTheme.cssBorder]">
                    <img 
                      :src="effectiveCoverImage" 
                      :alt="displayTitle" 
                      class="absolute inset-0 w-full h-full object-cover filter blur-lg scale-125 opacity-40 pointer-events-none"
                    >
                    <img 
                      :src="effectiveCoverImage" 
                      :alt="displayTitle" 
                      :style="coverPreviewTransformStyle"
                      :class="[
                        'relative z-10 w-full h-full filter drop-shadow-xl transition-transform duration-75',
                        flyerImageFit === 'contain' ? 'object-contain p-1.5' : 'object-cover object-center'
                      ]"
                    >
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
                      <span class="text-slate-400 line-through text-[7px]">Antes: ${{ Number(displayOriginalPrice).toLocaleString() }}</span>
                      <span v-if="calculatedDiscountPct" class="bg-emerald-500/20 text-emerald-300 font-black text-[6px] px-0.5 rounded">-{{ calculatedDiscountPct }}%</span>
                    </div>
                    <div v-else class="text-[6px] uppercase font-bold text-slate-400">PRECIO EXCLUSIVO</div>
                    <div class="flex items-baseline justify-center gap-0.5">
                      <span :class="['text-[9px] font-black', currentTheme.cssCurrency]">$</span>
                      <span class="font-black tracking-tight text-white font-mono text-sm sm:text-base">{{ displayPrice }}</span>
                      <span class="text-[6px] font-bold text-slate-400 font-mono">ARS</span>
                    </div>
                    <div :class="['text-[6px] font-bold truncate', currentTheme.cssText]">
                      {{ flyerProductType === 'physical' ? '🚚 Envío a Todo el País · Garantía Oficial 100%' : '⚡ Entrega 60/90min · Garantía Oficial 100%' }}
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
                  <div v-if="flyerProductType === 'physical'" :class="['w-full h-full rounded-2xl flex items-center justify-center shadow-xl border overflow-hidden relative group bg-slate-900', currentTheme.cssBorder]">
                    <img 
                      :src="effectiveCoverImage" 
                      :alt="displayTitle" 
                      class="absolute inset-0 w-full h-full object-cover filter blur-lg scale-125 opacity-40 pointer-events-none"
                    >
                    <img 
                      :src="effectiveCoverImage" 
                      :alt="displayTitle" 
                      :style="coverPreviewTransformStyle"
                      :class="[
                        'relative z-10 w-full h-full filter drop-shadow-2xl transition-transform duration-75',
                        flyerImageFit === 'contain' ? 'object-contain p-2' : 'object-cover object-center'
                      ]"
                    >
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
                      <span v-if="displayOriginalPrice" class="text-[8px] text-slate-400 line-through">${{ Number(displayOriginalPrice).toLocaleString() }}</span>
                      <span v-if="calculatedDiscountPct" class="text-[6px] bg-emerald-500/20 text-emerald-300 font-black px-1 rounded">-{{ calculatedDiscountPct }}%</span>
                      <div class="flex items-baseline gap-0.5">
                        <span :class="['font-bold text-[9px]', currentTheme.cssCurrency]">$</span>
                        <span class="font-black text-xs text-white font-mono">{{ displayPrice }}</span>
                        <span class="text-[6px] font-bold text-slate-400 font-mono">ARS</span>
                      </div>
                    </div>
                    <div :class="['text-[6px] font-bold truncate', currentTheme.cssText]">
                      {{ flyerProductType === 'physical' ? '🚚 Envío a Todo el País · Garantía Oficial 100%' : '⚡ Entrega 60/90min · Garantía Oficial 100%' }}
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

          <!-- CAJA DE TEXTO ATRACTIVO AUTO-GENERADO PARA WHATSAPP / REDES -->
          <div class="w-full bg-[#020617] rounded-2xl border border-slate-800 p-3 space-y-2 mt-2">
            <div class="flex items-center justify-between">
              <span class="text-[11px] font-black text-amber-400 flex items-center gap-1.5 uppercase tracking-wide">
                <span>💬</span>
                <span>Texto Promocional Atractivo</span>
              </span>
              <div class="flex items-center gap-1.5">
                <button
                  type="button"
                  @click="copyAttractiveFlyerCopy"
                  class="px-2 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-[10px] font-bold border border-slate-700 flex items-center gap-1 transition active:scale-95 cursor-pointer"
                  title="Copiar texto con emojis y garantía"
                >
                  <span>📋 Copiar</span>
                </button>
                <button
                  type="button"
                  @click="shareFlyerWithText"
                  class="px-2 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-[10px] font-bold flex items-center gap-1 transition active:scale-95 cursor-pointer shadow"
                  title="Compartir directo a WhatsApp"
                >
                  <span>📲 WhatsApp</span>
                </button>
              </div>
            </div>
            <div class="bg-slate-900/90 rounded-xl p-2.5 border border-white/5 text-[10.5px] font-mono text-slate-300 whitespace-pre-wrap leading-relaxed max-h-28 overflow-y-auto select-all">
              {{ attractiveFlyerCopy }}
            </div>
          </div>

          <!-- BOTONES DE ACCIÓN FLUIDOS DE PREVISUALIZACIÓN -->
          <div class="w-full space-y-2 pt-1">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
              <button
                type="button"
                @click="downloadFlyerImage"
                :disabled="isExporting"
                class="bg-gradient-to-r from-amber-500 via-orange-500 to-amber-500 hover:from-amber-400 hover:to-orange-400 text-slate-950 font-black py-3 px-3 rounded-xl shadow-lg transition-all flex items-center justify-center gap-1.5 text-xs cursor-pointer active:scale-95 disabled:opacity-50"
              >
                <span>{{ isExporting ? '⏳ Generando HD...' : `⬇️ Descargar Flyer (${flyerFormatLabels[flyerFormat].split(' ')[0]})` }}</span>
              </button>
              <button
                type="button"
                @click="shareFlyerWithText"
                :disabled="isExporting"
                class="bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-slate-950 font-black py-3 px-3 rounded-xl shadow-lg transition-all flex items-center justify-center gap-1.5 text-xs cursor-pointer active:scale-95 disabled:opacity-50"
              >
                <span>📲 Compartir Flyer</span>
              </button>
            </div>

            <button
              v-if="flyerFormat === 'story'"
              type="button"
              @click="handleOpenReels"
              class="w-full bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 hover:from-indigo-500 hover:to-pink-500 text-white font-black py-2.5 px-3 rounded-xl shadow-lg transition-all flex items-center justify-center gap-1.5 text-xs cursor-pointer active:scale-95 border border-indigo-400/30"
            >
              <span>🎬 Animar como Reel 9:16 (Video 30 FPS)</span>
              <span class="text-[9px] bg-white/20 px-2 py-0.5 rounded-full font-bold">NUEVO</span>
            </button>
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

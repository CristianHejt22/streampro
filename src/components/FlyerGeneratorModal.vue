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
      // HELPERS DEL SISTEMA DE DISEÑO CYBER OFICIAL
      // ========================================================
      function drawCyberBackground(ctx, w, h) {
        const bgGrad = ctx.createLinearGradient(0, 0, 0, h)
        bgGrad.addColorStop(0, '#020617')
        bgGrad.addColorStop(0.5, '#082f49')
        bgGrad.addColorStop(1, '#020617')
        ctx.fillStyle = bgGrad
        ctx.fillRect(0, 0, w, h)
      }

      async function drawVisorHeroContent(ctx, visorX, visorY, visorW, visorH) {
        const centerX = visorX + visorW / 2
        const centerY = visorY + visorH / 2

        if (flyerProductType.value === 'physical') {
          const imgEl = await loadFlyerImageElement(productImageCORS.value)
          if (imgEl) {
            const cardW = Math.min(visorW - 36, 540)
            const cardH = Math.min(visorH - 36, 430)
            ctx.fillStyle = '#ffffff'
            roundRect(ctx, centerX - cardW / 2, centerY - cardH / 2, cardW, cardH, 20, true, true, 'rgba(56, 189, 248, 0.4)', 2)

            const imgAspect = imgEl.width / (imgEl.height || 1)
            let dw = cardW - 36
            let dh = cardH - 36
            if (imgAspect > 1) {
              dh = dw / imgAspect
            } else {
              dw = dh * imgAspect
            }
            ctx.drawImage(imgEl, centerX - dw / 2, centerY - dh / 2, dw, dh)
          } else {
            const iconSize = Math.min(130, visorH * 0.38)
            ctx.font = `${iconSize}px sans-serif`
            ctx.textAlign = 'center'
            ctx.fillText('📦', centerX, centerY + iconSize * 0.25)
            ctx.font = `bold ${Math.max(16, Math.min(26, visorH * 0.1))}px sans-serif`
            ctx.fillStyle = '#38bdf8'
            ctx.fillText('PRODUCTO OFICIAL', centerX, centerY + iconSize * 0.6)
          }
        } else if (flyerProductType.value === 'combo') {
          const services = (selectedPackage.value?.services || []).slice(0, 3)
          if (services.length > 0) {
            const cardW = Math.min((visorW - (services.length + 1) * 16) / services.length, 240)
            const cardH = Math.min(visorH - 32, 260)
            const gap = 16
            const totalW = services.length * cardW + (services.length - 1) * gap
            const startX = centerX - totalW / 2
            const startY = centerY - cardH / 2

            services.forEach((srv, idx) => {
              const cx = startX + idx * (cardW + gap)
              ctx.fillStyle = 'rgba(2, 6, 23, 0.92)'
              roundRect(ctx, cx, startY, cardW, cardH, 18, true, true, 'rgba(56, 189, 248, 0.5)', 2)

              const emojiSize = Math.min(cardH * 0.28, 64)
              ctx.font = `${emojiSize}px sans-serif`
              ctx.textAlign = 'center'
              ctx.fillText(getPlatformIcon(srv.platform), cx + cardW / 2, startY + cardH * 0.35)

              ctx.font = `bold ${Math.min(20, Math.max(12, cardW * 0.12))}px sans-serif`
              ctx.fillStyle = '#ffffff'
              ctx.fillText(srv.platform, cx + cardW / 2, startY + cardH * 0.55)

              ctx.font = `bold ${Math.min(15, Math.max(10, cardW * 0.09))}px monospace`
              ctx.fillStyle = '#38bdf8'
              ctx.fillText(srv.planLabel || '1 Perfil', cx + cardW / 2, startY + cardH * 0.72)

              const badgeW = Math.min(cardW - 16, 90)
              ctx.fillStyle = 'rgba(16, 185, 129, 0.25)'
              roundRect(ctx, cx + cardW / 2 - badgeW / 2, startY + cardH * 0.82, badgeW, 24, 8, true, true, '#10b981', 1)
              ctx.fillStyle = '#34d399'
              ctx.font = 'bold 12px sans-serif'
              ctx.fillText('INCLUIDO', cx + cardW / 2, startY + cardH * 0.82 + 16)
            })
          } else {
            ctx.font = '90px sans-serif'
            ctx.textAlign = 'center'
            ctx.fillText('🍿 🎬 ✨', centerX, centerY + 20)
          }
        } else {
          // Individual Streaming
          const iconSize = Math.min(130, visorH * 0.42)
          ctx.font = `${iconSize}px sans-serif`
          ctx.textAlign = 'center'
          ctx.fillText(currentPlatformIcon.value, centerX, centerY - visorH * 0.05)

          const titleSize = Math.min(46, Math.max(22, visorH * 0.15))
          ctx.font = `900 ${titleSize}px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif`
          ctx.fillStyle = '#38bdf8'
          ctx.fillText(selectedPlatform.value.toUpperCase(), centerX, centerY + visorH * 0.2)

          ctx.fillStyle = 'rgba(15, 23, 42, 0.75)'
          const subtagText = (currentPlanData.value.label || '1 PERFIL PRIVADO').toUpperCase() + ' · 4K ULTRA HD'
          const tagFontSize = Math.min(18, Math.max(12, visorH * 0.07))
          ctx.font = `bold ${tagFontSize}px monospace`
          const tagW = ctx.measureText(subtagText).width + 30
          const tagH = tagFontSize + 14
          roundRect(ctx, centerX - tagW / 2, centerY + visorH * 0.27, tagW, tagH, tagH / 2, true, true, 'rgba(56, 189, 248, 0.5)', 1.5)
          ctx.fillStyle = '#e0f2fe'
          ctx.fillText(subtagText, centerX, centerY + visorH * 0.27 + tagH * 0.68)
        }
      }

      function drawCyberBadge(ctx, centerX, y, h, maxW = 860, isSmall = false) {
        const badgeText = headlineBadge.value.toUpperCase()
        ctx.font = `900 ${isSmall ? 20 : 25}px sans-serif`
        const bW = Math.min(ctx.measureText(badgeText).width + (isSmall ? 40 : 54), maxW)
        const bX = centerX - bW / 2
        const bGrad = ctx.createLinearGradient(bX, y, bX + bW, y)
        bGrad.addColorStop(0, '#f59e0b')
        bGrad.addColorStop(1, '#ea580c')
        ctx.fillStyle = bGrad
        roundRect(ctx, bX, y, bW, h, h / 2, true, false)
        ctx.textAlign = 'center'
        ctx.fillStyle = '#020617'
        ctx.fillText(badgeText, centerX, y + h / 2 + (isSmall ? 7 : 8))
      }

      function drawCyberPriceCard(ctx, x, y, w, h, isSquare = false) {
        ctx.fillStyle = 'rgba(15, 23, 42, 0.88)'
        roundRect(ctx, x, y, w, h, 24, true, true, 'rgba(56, 189, 248, 0.45)', 2)

        const centerX = x + w / 2
        const hasOrig = displayOriginalPrice.value && Number(displayOriginalPrice.value) > Number(displayPriceNumeric.value)

        if (hasOrig) {
          ctx.font = `bold ${isSquare ? 16 : 22}px sans-serif`
          ctx.fillStyle = '#94a3b8'
          const origText = `Antes: $${Number(displayOriginalPrice.value).toLocaleString()} ARS`
          const origW = ctx.measureText(origText).width
          const discountShift = calculatedDiscountPct.value ? (isSquare ? 42 : 55) : 0
          const textX = centerX - discountShift
          ctx.textAlign = 'center'
          ctx.fillText(origText, textX, y + (isSquare ? 28 : 40))

          ctx.strokeStyle = '#ef4444'
          ctx.lineWidth = 2.5
          ctx.beginPath()
          ctx.moveTo(textX - origW / 2, y + (isSquare ? 23 : 33))
          ctx.lineTo(textX + origW / 2, y + (isSquare ? 23 : 33))
          ctx.stroke()

          if (calculatedDiscountPct.value) {
            const badgeX = textX + origW / 2 + 12
            const badgeW = isSquare ? 72 : 90
            const badgeH = isSquare ? 24 : 30
            ctx.fillStyle = 'rgba(16, 185, 129, 0.25)'
            roundRect(ctx, badgeX, y + (isSquare ? 14 : 20), badgeW, badgeH, 8, true, true, '#10b981', 1)
            ctx.fillStyle = '#34d399'
            ctx.font = `900 ${isSquare ? 13 : 16}px monospace`
            ctx.textAlign = 'center'
            ctx.fillText(`-${calculatedDiscountPct.value}%`, badgeX + badgeW / 2, y + (isSquare ? 29 : 41))
          }

          ctx.font = `900 ${isSquare ? 50 : 74}px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif`
          ctx.fillStyle = '#ffffff'
          ctx.fillText(`$${displayPrice.value} ARS`, centerX, y + (isSquare ? 80 : 115))

          if (!isSquare) {
            ctx.font = 'bold 18px sans-serif'
            ctx.fillStyle = '#38bdf8'
            ctx.fillText('⚡ Entrega Inmediata · Garantía Oficial 100%', centerX, y + 152)
          }
        } else {
          ctx.font = `bold ${isSquare ? 16 : 22}px sans-serif`
          ctx.fillStyle = '#94a3b8'
          ctx.textAlign = 'center'
          ctx.fillText('PRECIO EXCLUSIVO', centerX, y + (isSquare ? 28 : 40))

          ctx.font = `900 ${isSquare ? 52 : 76}px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif`
          ctx.fillStyle = '#ffffff'
          ctx.fillText(`$${displayPrice.value} ARS`, centerX, y + (isSquare ? 80 : 116))

          if (!isSquare) {
            ctx.font = 'bold 18px sans-serif'
            ctx.fillStyle = '#38bdf8'
            ctx.fillText('⚡ Entrega Inmediata · Garantía Oficial 100%', centerX, y + 152)
          }
        }
      }

      function drawCyberBullets(ctx, startX, startY, lineSpacing, maxBullets = 3, isSmall = false) {
        const bullets = [bullet1.value, bullet2.value, bullet3.value].slice(0, maxBullets)
        ctx.textAlign = 'left'
        bullets.forEach((b, idx) => {
          const by = startY + idx * lineSpacing
          const radius = isSmall ? 9 : 13

          ctx.fillStyle = '#00f0ff'
          ctx.beginPath()
          ctx.arc(startX, by - (isSmall ? 5 : 7), radius, 0, Math.PI * 2)
          ctx.fill()
          ctx.fillStyle = '#020617'
          ctx.font = `bold ${isSmall ? 13 : 17}px sans-serif`
          ctx.textAlign = 'center'
          ctx.fillText('✓', startX, by - (isSmall ? 1 : 2))
          ctx.textAlign = 'left'

          ctx.font = `600 ${isSmall ? 20 : 26}px sans-serif`
          ctx.fillStyle = '#f8fafc'
          const maxLen = isSmall ? 38 : 46
          ctx.fillText(b.length > maxLen ? b.slice(0, maxLen - 2) + '...' : b, startX + radius + 12, by)
        })
      }

      function drawCyberCTAButton(ctx, x, y, w, h, domainText, showMicroText = true, isSmall = false) {
        if (showMicroText) {
          ctx.textAlign = 'center'
          ctx.font = `bold ${isSmall ? 15 : 19}px sans-serif`
          ctx.fillStyle = '#94a3b8'
          ctx.fillText('⚡ ¡HAZ TU PEDIDO ONLINE CON GARANTÍA OFICIAL!', x + w / 2, y - 10)
        }

        const webGrad = ctx.createLinearGradient(x, y, x + w, y)
        webGrad.addColorStop(0, '#0284c7')
        webGrad.addColorStop(1, '#00f0ff')
        ctx.fillStyle = webGrad
        roundRect(ctx, x, y, w, h, isSmall ? 18 : 26, true, false)

        ctx.textAlign = 'center'
        ctx.font = `900 ${isSmall ? 25 : 34}px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif`
        ctx.fillStyle = '#020617'
        ctx.fillText(`🌐 ${domainText}   ·   ¡PIDE ONLINE AHORA!`, x + w / 2, y + h / 2 + (isSmall ? 8 : 11))
      }

      // ========================================================
      // 1. TEMA CYBER OFICIAL (CON PLANTILLAS ORIGINALES DE CANVA)
      // ========================================================
      if (flyerTheme.value === 'cyber') {
        const tplUrl = getCyberTemplateUrl(format)
        const cyberImg = await loadFlyerImageElement(tplUrl)
        if (cyberImg) {
          ctx.drawImage(cyberImg, 0, 0, width, height)
        } else {
          drawCyberBackground(ctx, width, height)
        }

        if (format === 'banner') {
          // ================= BANNER HORIZONTAL (1920 x 1080) =================
          // Visor: X: 137, Y: 261, W: 544, H: 579
          await drawVisorHeroContent(ctx, 137, 261, 544, 579)

          // Columna Derecha (X: 740, W: 1040)
          const rightX = 740
          const rightW = 1040

          drawCyberBadge(ctx, rightX + rightW / 2, 140, 56, rightW, false)

          ctx.textAlign = 'left'
          ctx.font = '900 46px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
          ctx.fillStyle = '#ffffff'
          const titleLines = wrapText(displayTitle.value, 32).slice(0, 2)
          titleLines.forEach((tl, i) => ctx.fillText(tl, rightX, 235 + i * 52))
          const subY = 235 + titleLines.length * 52 + 8

          ctx.font = 'bold 24px sans-serif'
          ctx.fillStyle = '#38bdf8'
          ctx.fillText(displaySubtitle.value.length > 56 ? displaySubtitle.value.slice(0, 54) + '...' : displaySubtitle.value, rightX, subY)

          const pCardY = subY + 20
          drawCyberPriceCard(ctx, rightX, pCardY, rightW, 165, false)

          drawCyberBullets(ctx, rightX + 20, pCardY + 165 + 50, 68, 3, false)

          // Etiqueta celeste eliminada: ya incluida en el diseño oficial

        } else if (format === 'story') {
          // ================= STORY (1080 x 1920, 9:16) =================
          // Visor: X: 188, Y: 262, W: 704, H: 546
          await drawVisorHeroContent(ctx, 188, 262, 704, 546)

          // Contenido Inferior
          drawCyberBadge(ctx, width / 2, 855, 58, 860, false)

          ctx.textAlign = 'center'
          ctx.font = '900 46px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
          ctx.fillStyle = '#ffffff'
          const titleLines = wrapText(displayTitle.value, 28).slice(0, 2)
          titleLines.forEach((tl, i) => ctx.fillText(tl, width / 2, 955 + i * 52))
          const subY = 955 + titleLines.length * 52 + 10

          ctx.font = 'bold 24px sans-serif'
          ctx.fillStyle = '#38bdf8'
          ctx.fillText(displaySubtitle.value.length > 48 ? displaySubtitle.value.slice(0, 46) + '...' : displaySubtitle.value, width / 2, subY)

          const pCardY = subY + 30
          drawCyberPriceCard(ctx, 110, pCardY, 860, 190, false)

          drawCyberBullets(ctx, 145, pCardY + 190 + 55, 75, 3, false)

          // Etiqueta celeste eliminada: ya incluida en el diseño oficial

        } else if (format === 'feed') {
          // ================= FEED PORTRAIT (1080 x 1350, 4:5) =================
          // Visor: X: 218, Y: 204, W: 645, H: 370
          await drawVisorHeroContent(ctx, 218, 204, 645, 370)

          // Contenido Inferior
          drawCyberBadge(ctx, width / 2, 605, 52, 840, false)

          ctx.textAlign = 'center'
          ctx.font = '900 38px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
          ctx.fillStyle = '#ffffff'
          const titleLines = wrapText(displayTitle.value, 30).slice(0, 2)
          titleLines.forEach((tl, i) => ctx.fillText(tl, width / 2, 685 + i * 44))
          const subY = 685 + titleLines.length * 44 + 8

          ctx.font = 'bold 22px sans-serif'
          ctx.fillStyle = '#38bdf8'
          ctx.fillText(displaySubtitle.value.length > 48 ? displaySubtitle.value.slice(0, 46) + '...' : displaySubtitle.value, width / 2, subY)

          const pCardY = subY + 22
          drawCyberPriceCard(ctx, 110, pCardY, 860, 160, false)

          drawCyberBullets(ctx, 145, pCardY + 160 + 45, 60, 3, false)

          // Etiqueta celeste eliminada: ya incluida en el diseño oficial

        } else {
          // ================= SQUARE (1080 x 1080, 1:1) =================
          // Visor: X: 273, Y: 180, W: 533, H: 464
          await drawVisorHeroContent(ctx, 273, 180, 533, 464)

          // Contenido Inferior
          drawCyberBadge(ctx, width / 2, 665, 40, 840, true)

          ctx.textAlign = 'center'
          ctx.font = '900 30px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
          ctx.fillStyle = '#ffffff'
          const titleLines = wrapText(displayTitle.value, 32).slice(0, 2)
          titleLines.forEach((tl, i) => ctx.fillText(tl, width / 2, 720 + i * 36))
          const subY = 720 + titleLines.length * 36 + 4

          ctx.font = 'bold 17px sans-serif'
          ctx.fillStyle = '#38bdf8'
          ctx.fillText(displaySubtitle.value.length > 46 ? displaySubtitle.value.slice(0, 44) + '...' : displaySubtitle.value, width / 2, subY)

          const pCardY = subY + 14
          drawCyberPriceCard(ctx, 120, pCardY, 840, 115, true)

          drawCyberBullets(ctx, 150, pCardY + 115 + 20, 36, 2, true)

          // Etiqueta celeste eliminada: ya incluida en el diseño oficial
        }

        canvas.toBlob((blob) => {
          if (!blob) {
            reject(new Error('Fallo al exportar blob de canvas'))
            return
          }
          resolve(blob)
        }, 'image/png')
        return
      }

      // ========================================================
      // 2. TEMAS CLÁSICOS (CINEMA, EMERALD, BLUE)
      // ========================================================
      let gradTop = '#020617'
      let gradMid = '#1e0506'
      let gradBot = '#020617'
      let accentBorder = '#ef4444'
      let glowColor = 'rgba(239, 68, 68, 0.25)'

      if (flyerTheme.value === 'emerald') {
        gradMid = '#064e3b'
        accentBorder = '#10b981'
        glowColor = 'rgba(16, 185, 129, 0.28)'
      } else if (flyerTheme.value === 'blue') {
        gradMid = '#0c4a6e'
        accentBorder = '#0ea5e9'
        glowColor = 'rgba(14, 165, 233, 0.28)'
      }

      const bgGrad = ctx.createLinearGradient(0, 0, 0, height)
      bgGrad.addColorStop(0, gradTop)
      bgGrad.addColorStop(0.5, gradMid)
      bgGrad.addColorStop(1, gradBot)
      ctx.fillStyle = bgGrad
      ctx.fillRect(0, 0, width, height)

      const glow1 = ctx.createRadialGradient(width * 0.8, height * 0.15, 20, width * 0.8, height * 0.15, width * 0.55)
      glow1.addColorStop(0, glowColor)
      glow1.addColorStop(1, 'transparent')
      ctx.fillStyle = glow1
      ctx.fillRect(0, 0, width, height)

      const glow2 = ctx.createRadialGradient(width * 0.2, height * 0.85, 20, width * 0.2, height * 0.85, width * 0.55)
      glow2.addColorStop(0, 'rgba(245, 158, 11, 0.16)')
      glow2.addColorStop(1, 'transparent')
      ctx.fillStyle = glow2
      ctx.fillRect(0, 0, width, height)

      roundRect(ctx, 24, 24, width - 48, height - 48, 48, false, true, accentBorder, 3)

      const isBanner = format === 'banner'

      if (isBanner) {
        ctx.textAlign = 'left'
        ctx.font = 'bold 36px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
        ctx.fillStyle = '#fbbf24'
        ctx.fillText(storeName.value.toUpperCase(), 100, 105)

        const boxX = 100
        const boxY = 150
        const boxW = 660
        const boxH = 690
        ctx.fillStyle = 'rgba(15, 23, 42, 0.85)'
        roundRect(ctx, boxX, boxY, boxW, boxH, 36, true, true, 'rgba(255,255,255,0.18)', 2)

        if (flyerProductType.value === 'physical') {
          const imgEl = await loadFlyerImageElement(productImageCORS.value)
          if (imgEl) {
            ctx.fillStyle = '#ffffff'
            roundRect(ctx, boxX + 40, boxY + 40, boxW - 80, boxH - 80, 24, true, false)
            const imgAspect = imgEl.width / (imgEl.height || 1)
            let dw = boxW - 120
            let dh = boxH - 120
            if (imgAspect > 1) {
              dh = dw / imgAspect
            } else {
              dw = dh * imgAspect
            }
            const dx = boxX + (boxW - dw) / 2
            const dy = boxY + (boxH - dh) / 2
            ctx.drawImage(imgEl, dx, dy, dw, dh)
          } else {
            ctx.font = '160px sans-serif'
            ctx.textAlign = 'center'
            ctx.fillText('📦', boxX + boxW / 2, boxY + boxH / 2 + 50)
          }
        } else if (flyerProductType.value === 'combo') {
          const services = (selectedPackage.value?.services || []).slice(0, 4)
          if (services.length > 0) {
            const cardW = 270
            const cardH = 140
            const startX = boxX + (boxW - (cardW * 2 + 20)) / 2
            const startY = boxY + (boxH - (cardH * 2 + 20)) / 2
            services.forEach((srv, idx) => {
              const r = Math.floor(idx / 2)
              const c = idx % 2
              const cx = startX + c * (cardW + 20)
              const cy = startY + r * (cardH + 20)
              ctx.fillStyle = 'rgba(2, 6, 23, 0.9)'
              roundRect(ctx, cx, cy, cardW, cardH, 20, true, true, 'rgba(236, 72, 153, 0.4)', 2)
              ctx.font = '48px sans-serif'
              ctx.textAlign = 'center'
              ctx.fillText(getPlatformIcon(srv.platform), cx + cardW / 2, cy + 55)
              ctx.font = 'bold 22px sans-serif'
              ctx.fillStyle = '#ffffff'
              ctx.fillText(srv.platform, cx + cardW / 2, cy + 90)
              ctx.font = '16px monospace'
              ctx.fillStyle = '#f472b6'
              ctx.fillText(srv.planLabel || '1 Perfil', cx + cardW / 2, cy + 118)
            })
          } else {
            ctx.font = '90px sans-serif'
            ctx.textAlign = 'center'
            ctx.fillText('🍿 🎬 ✨', boxX + boxW / 2, boxY + boxH / 2 + 30)
          }
        } else {
          ctx.font = '180px sans-serif'
          ctx.textAlign = 'center'
          ctx.fillText(currentPlatformIcon.value, boxX + boxW / 2, boxY + boxH / 2 + 10)
          ctx.font = '900 36px sans-serif'
          ctx.fillStyle = '#fbbf24'
          ctx.fillText(selectedPlatform.value.toUpperCase(), boxX + boxW / 2, boxY + boxH / 2 + 90)
          ctx.font = 'bold 22px sans-serif'
          ctx.fillStyle = '#94a3b8'
          ctx.fillText('CALIDAD ULTRA HD 4K', boxX + boxW / 2, boxY + boxH / 2 + 130)
        }

        ctx.fillStyle = 'rgba(2, 6, 23, 0.75)'
        roundRect(ctx, boxX, 865, boxW, 80, 24, true, true, 'rgba(16, 185, 129, 0.35)', 1.5)
        ctx.font = 'bold 22px sans-serif'
        ctx.fillStyle = '#34d399'
        ctx.textAlign = 'center'
        ctx.fillText('✓ DISTRIBUIDOR OFICIAL VERIFICADO · GARANTÍA 24/7', boxX + boxW / 2, 915)

        const rightX = 820
        const rightW = 1000
        ctx.textAlign = 'left'

        const badgeText = headlineBadge.value.toUpperCase()
        ctx.font = '900 28px sans-serif'
        const bW = Math.min(ctx.measureText(badgeText).width + 56, rightW)
        const bGrad = ctx.createLinearGradient(rightX, 100, rightX + bW, 100)
        bGrad.addColorStop(0, '#f59e0b')
        bGrad.addColorStop(1, '#ea580c')
        ctx.fillStyle = bGrad
        roundRect(ctx, rightX, 100, bW, 58, 29, true, false)
        ctx.fillStyle = '#020617'
        ctx.textAlign = 'center'
        ctx.fillText(badgeText, rightX + bW / 2, 140)

        ctx.textAlign = 'left'
        ctx.font = '900 50px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
        ctx.fillStyle = '#ffffff'
        const titleLines = wrapText(displayTitle.value, 30).slice(0, 2)
        let tY = 220
        titleLines.forEach((tl, i) => {
          ctx.fillText(tl, rightX, tY + i * 58)
        })
        const subY = tY + titleLines.length * 58 + 10

        ctx.font = 'bold 26px sans-serif'
        ctx.fillStyle = '#fbbf24'
        ctx.fillText(displaySubtitle.value.length > 56 ? displaySubtitle.value.slice(0, 54) + '...' : displaySubtitle.value, rightX, subY)

        const pCardY = subY + 35
        const pCardH = 160
        ctx.fillStyle = 'rgba(0,0,0,0.7)'
        roundRect(ctx, rightX, pCardY, rightW, pCardH, 28, true, true, 'rgba(255,255,255,0.15)', 2)

        if (displayOriginalPrice.value && Number(displayOriginalPrice.value) > Number(displayPriceNumeric.value)) {
          ctx.font = 'bold 24px sans-serif'
          ctx.fillStyle = '#94a3b8'
          const origText = `Antes: $${Number(displayOriginalPrice.value).toLocaleString()} ARS`
          ctx.fillText(origText, rightX + 40, pCardY + 48)
          const origW = ctx.measureText(origText).width
          ctx.strokeStyle = '#ef4444'
          ctx.lineWidth = 2.5
          ctx.beginPath()
          ctx.moveTo(rightX + 40, pCardY + 40)
          ctx.lineTo(rightX + 40 + origW, pCardY + 40)
          ctx.stroke()

          if (calculatedDiscountPct.value) {
            ctx.fillStyle = 'rgba(16, 185, 129, 0.25)'
            roundRect(ctx, rightX + 50 + origW, pCardY + 26, 100, 32, 12, true, true, '#10b981', 1)
            ctx.fillStyle = '#34d399'
            ctx.font = '900 18px monospace'
            ctx.textAlign = 'center'
            ctx.fillText(`-${calculatedDiscountPct.value}%`, rightX + 100 + origW, pCardY + 48)
            ctx.textAlign = 'left'
          }

          ctx.font = '900 68px sans-serif'
          ctx.fillStyle = '#ffffff'
          ctx.fillText(`$${displayPrice.value} ARS`, rightX + 40, pCardY + 124)
        } else {
          ctx.font = 'bold 22px sans-serif'
          ctx.fillStyle = '#94a3b8'
          ctx.fillText('PRECIO EXCLUSIVO', rightX + 40, pCardY + 48)

          ctx.font = '900 70px sans-serif'
          ctx.fillStyle = '#ffffff'
          ctx.fillText(`$${displayPrice.value} ARS`, rightX + 40, pCardY + 124)
        }

        const bullets = [bullet1.value, bullet2.value, bullet3.value]
        const bulletsStartY = pCardY + pCardH + 45
        bullets.forEach((b, idx) => {
          const by = bulletsStartY + idx * 52
          ctx.fillStyle = '#10b981'
          ctx.beginPath()
          ctx.arc(rightX + 20, by - 8, 14, 0, Math.PI * 2)
          ctx.fill()
          ctx.fillStyle = '#020617'
          ctx.font = 'bold 18px sans-serif'
          ctx.textAlign = 'center'
          ctx.fillText('✓', rightX + 20, by - 2)
          ctx.textAlign = 'left'

          ctx.font = '600 26px sans-serif'
          ctx.fillStyle = '#f8fafc'
          ctx.fillText(b.length > 52 ? b.slice(0, 50) + '...' : b, rightX + 50, by)
        })

        const footY = 865
        const footH = 95
        const webGrad = ctx.createLinearGradient(rightX, footY, rightX + rightW, footY)
        webGrad.addColorStop(0, '#0284c7')
        webGrad.addColorStop(1, '#0ea5e9')
        ctx.fillStyle = webGrad
        roundRect(ctx, rightX, footY, rightW, footH, 28, true, false)
        ctx.textAlign = 'center'
        ctx.font = '900 38px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
        ctx.fillStyle = '#020617'
        ctx.fillText(`🌐 ${domainText}   ·   ¡HAZ TU PEDIDO ONLINE AHORA!`, rightX + rightW / 2, footY + 58)

      } else {
        const isStory = format === 'story'
        const isFeed = format === 'feed'
        const isSquare = format === 'square'

        ctx.textAlign = 'left'
        ctx.font = `bold ${isStory ? 34 : isFeed ? 28 : 24}px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif`
        ctx.fillStyle = '#fbbf24'
        const headerY = isStory ? 140 : isFeed ? 95 : 75
        ctx.fillText(storeName.value.toUpperCase(), 80, headerY)

        ctx.textAlign = 'right'
        ctx.font = `bold ${isStory ? 24 : isFeed ? 20 : 17}px sans-serif`
        ctx.fillStyle = '#94a3b8'
        ctx.fillText('⭐ DISTRIBUIDOR OFICIAL', width - 80, headerY)

        const badgeY = isStory ? 220 : isFeed ? 150 : 120
        const badgeH = isStory ? 68 : isFeed ? 56 : 46
        const badgeText = headlineBadge.value.toUpperCase()
        ctx.font = `900 ${isStory ? 28 : isFeed ? 23 : 19}px sans-serif`
        const bW = Math.min(ctx.measureText(badgeText).width + 52, width - 140)
        const bX = (width - bW) / 2
        const bGrad = ctx.createLinearGradient(bX, badgeY, bX + bW, badgeY)
        bGrad.addColorStop(0, '#f59e0b')
        bGrad.addColorStop(1, '#ea580c')
        ctx.fillStyle = bGrad
        roundRect(ctx, bX, badgeY, bW, badgeH, badgeH / 2, true, false)
        ctx.textAlign = 'center'
        ctx.fillStyle = '#020617'
        ctx.fillText(badgeText, width / 2, badgeY + badgeH / 2 + (isStory ? 9 : isFeed ? 8 : 6))

        const heroBoxSize = isStory ? 440 : isFeed ? 320 : 230
        const heroX = (width - heroBoxSize) / 2
        const heroY = isStory ? 330 : isFeed ? 240 : 190

        ctx.fillStyle = 'rgba(15, 23, 42, 0.85)'
        roundRect(ctx, heroX, heroY, heroBoxSize, heroBoxSize, isStory ? 40 : 30, true, true, 'rgba(255,255,255,0.18)', 2)

        if (flyerProductType.value === 'physical') {
          const imgEl = await loadFlyerImageElement(productImageCORS.value)
          if (imgEl) {
            ctx.fillStyle = '#ffffff'
            roundRect(ctx, heroX + 20, heroY + 20, heroBoxSize - 40, heroBoxSize - 40, 20, true, false)
            const imgAspect = imgEl.width / (imgEl.height || 1)
            let dw = heroBoxSize - 60
            let dh = heroBoxSize - 60
            if (imgAspect > 1) {
              dh = dw / imgAspect
            } else {
              dw = dh * imgAspect
            }
            const dx = heroX + (heroBoxSize - dw) / 2
            const dy = heroY + (heroBoxSize - dh) / 2
            ctx.drawImage(imgEl, dx, dy, dw, dh)
          } else {
            ctx.font = `${isStory ? 140 : isFeed ? 100 : 75}px sans-serif`
            ctx.textAlign = 'center'
            ctx.fillText('📦', width / 2, heroY + heroBoxSize / 2 + 45)
          }
        } else if (flyerProductType.value === 'combo') {
          const services = (selectedPackage.value?.services || []).slice(0, 3)
          if (services.length > 0) {
            const cardW = isStory ? 120 : isFeed ? 90 : 65
            const cardH = isStory ? 140 : isFeed ? 100 : 75
            const totalW = services.length * cardW + (services.length - 1) * (isStory ? 16 : 10)
            const startX = (width - totalW) / 2
            const startY = heroY + (heroBoxSize - cardH) / 2
            services.forEach((srv, idx) => {
              const cx = startX + idx * (cardW + (isStory ? 16 : 10))
              ctx.fillStyle = 'rgba(2, 6, 23, 0.95)'
              roundRect(ctx, cx, startY, cardW, cardH, isStory ? 20 : 14, true, true, 'rgba(236, 72, 153, 0.45)', 2)
              ctx.font = `${isStory ? 44 : isFeed ? 32 : 24}px sans-serif`
              ctx.textAlign = 'center'
              ctx.fillText(getPlatformIcon(srv.platform), cx + cardW / 2, startY + (isStory ? 52 : isFeed ? 38 : 28))
              ctx.font = `bold ${isStory ? 16 : isFeed ? 13 : 10}px sans-serif`
              ctx.fillStyle = '#ffffff'
              ctx.fillText(srv.platform, cx + cardW / 2, startY + (isStory ? 85 : isFeed ? 62 : 46))
              ctx.font = `${isStory ? 13 : isFeed ? 10 : 8}px monospace`
              ctx.fillStyle = '#f472b6'
              ctx.fillText(srv.planLabel || '1 Perfil', cx + cardW / 2, startY + (isStory ? 112 : isFeed ? 82 : 62))
            })
          } else {
            ctx.font = `${isStory ? 90 : isFeed ? 70 : 50}px sans-serif`
            ctx.textAlign = 'center'
            ctx.fillText('🍿 🎬 ✨', width / 2, heroY + heroBoxSize / 2 + 25)
          }
        } else {
          ctx.font = `${isStory ? 140 : isFeed ? 100 : 75}px sans-serif`
          ctx.textAlign = 'center'
          ctx.fillText(currentPlatformIcon.value, width / 2, heroY + heroBoxSize / 2 + (isStory ? 20 : isFeed ? 15 : 10))
          ctx.font = `900 ${isStory ? 32 : isFeed ? 24 : 18}px sans-serif`
          ctx.fillStyle = '#fbbf24'
          ctx.fillText(selectedPlatform.value.toUpperCase(), width / 2, heroY + heroBoxSize - (isStory ? 45 : isFeed ? 35 : 25))
        }

        const titleY = heroY + heroBoxSize + (isStory ? 65 : isFeed ? 48 : 34)
        ctx.textAlign = 'center'
        ctx.font = `900 ${isStory ? 46 : isFeed ? 36 : 28}px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif`
        ctx.fillStyle = '#ffffff'
        const titleLines = wrapText(displayTitle.value, isStory ? 28 : isFeed ? 30 : 32).slice(0, 2)
        titleLines.forEach((tl, i) => {
          ctx.fillText(tl, width / 2, titleY + i * (isStory ? 52 : isFeed ? 42 : 32))
        })
        const subY = titleY + titleLines.length * (isStory ? 52 : isFeed ? 42 : 32) + (isStory ? 14 : isFeed ? 10 : 8)

        ctx.font = `bold ${isStory ? 26 : isFeed ? 21 : 17}px sans-serif`
        ctx.fillStyle = '#fbbf24'
        ctx.fillText(displaySubtitle.value.length > 46 ? displaySubtitle.value.slice(0, 44) + '...' : displaySubtitle.value, width / 2, subY)

        const priceBoxW = isStory ? 880 : isFeed ? 820 : 760
        const priceBoxH = isStory ? 200 : isFeed ? 155 : 130
        const priceBoxX = (width - priceBoxW) / 2
        const priceBoxY = subY + (isStory ? 40 : isFeed ? 32 : 24)

        ctx.fillStyle = 'rgba(0,0,0,0.72)'
        roundRect(ctx, priceBoxX, priceBoxY, priceBoxW, priceBoxH, isStory ? 32 : 24, true, true, 'rgba(255,255,255,0.16)', 2)

        if (displayOriginalPrice.value && Number(displayOriginalPrice.value) > Number(displayPriceNumeric.value)) {
          ctx.font = `bold ${isStory ? 24 : isFeed ? 20 : 16}px sans-serif`
          ctx.fillStyle = '#94a3b8'
          const origText = `Antes: $${Number(displayOriginalPrice.value).toLocaleString()} ARS`
          ctx.fillText(origText, width / 2 - (calculatedDiscountPct.value ? 60 : 0), priceBoxY + (isStory ? 46 : isFeed ? 38 : 30))
          const origW = ctx.measureText(origText).width
          ctx.strokeStyle = '#ef4444'
          ctx.lineWidth = 2
          ctx.beginPath()
          const stX = width / 2 - (calculatedDiscountPct.value ? 60 : 0) - origW / 2
          ctx.moveTo(stX, priceBoxY + (isStory ? 38 : isFeed ? 32 : 25))
          ctx.lineTo(stX + origW, priceBoxY + (isStory ? 38 : isFeed ? 32 : 25))
          ctx.stroke()

          if (calculatedDiscountPct.value) {
            ctx.fillStyle = 'rgba(16, 185, 129, 0.25)'
            roundRect(ctx, stX + origW + 16, priceBoxY + (isStory ? 24 : isFeed ? 18 : 14), isStory ? 100 : 80, isStory ? 32 : 26, 10, true, true, '#10b981', 1)
            ctx.fillStyle = '#34d399'
            ctx.font = `900 ${isStory ? 18 : isFeed ? 15 : 13}px monospace`
            ctx.textAlign = 'center'
            ctx.fillText(`-${calculatedDiscountPct.value}%`, stX + origW + 16 + (isStory ? 50 : 40), priceBoxY + (isStory ? 46 : isFeed ? 37 : 32))
            ctx.textAlign = 'center'
          }

          ctx.font = `900 ${isStory ? 80 : isFeed ? 64 : 54}px sans-serif`
          ctx.fillStyle = '#ffffff'
          ctx.fillText(`$${displayPrice.value} ARS`, width / 2, priceBoxY + (isStory ? 128 : isFeed ? 102 : 84))

          if (!isSquare) {
            ctx.font = 'bold 18px sans-serif'
            ctx.fillStyle = '#94a3b8'
            ctx.fillText('⚡ Entrega Inmediata · Garantía Oficial 100%', width / 2, priceBoxY + (isStory ? 168 : 136))
          }
        } else {
          ctx.font = `bold ${isStory ? 22 : isFeed ? 18 : 15}px sans-serif`
          ctx.fillStyle = '#94a3b8'
          ctx.fillText('PRECIO EXCLUSIVO', width / 2, priceBoxY + (isStory ? 46 : isFeed ? 38 : 30))

          ctx.font = `900 ${isStory ? 82 : isFeed ? 66 : 56}px sans-serif`
          ctx.fillStyle = '#ffffff'
          ctx.fillText(`$${displayPrice.value} ARS`, width / 2, priceBoxY + (isStory ? 130 : isFeed ? 104 : 86))

          if (!isSquare) {
            ctx.font = 'bold 18px sans-serif'
            ctx.fillStyle = '#94a3b8'
            ctx.fillText('⚡ Entrega Inmediata · Garantía Oficial 100%', width / 2, priceBoxY + (isStory ? 168 : 136))
          }
        }

        const bulletsStartY = priceBoxY + priceBoxH + (isStory ? 45 : isFeed ? 34 : 24)
        const bullets = [bullet1.value, bullet2.value, bullet3.value]
        ctx.textAlign = 'left'
        bullets.forEach((b, idx) => {
          if (isSquare && idx === 2) return
          const by = bulletsStartY + idx * (isStory ? 60 : isFeed ? 48 : 36)
          const bX = isStory ? 140 : isFeed ? 140 : 160

          ctx.fillStyle = '#10b981'
          ctx.beginPath()
          ctx.arc(bX, by - (isStory ? 8 : 6), isStory ? 14 : isFeed ? 12 : 9, 0, Math.PI * 2)
          ctx.fill()
          ctx.fillStyle = '#020617'
          ctx.font = `bold ${isStory ? 18 : isFeed ? 15 : 12}px sans-serif`
          ctx.textAlign = 'center'
          ctx.fillText('✓', bX, by - (isStory ? 2 : 2))
          ctx.textAlign = 'left'

          ctx.font = `600 ${isStory ? 30 : isFeed ? 24 : 19}px sans-serif`
          ctx.fillStyle = '#f8fafc'
          ctx.fillText(b.length > 44 ? b.slice(0, 42) + '...' : b, bX + (isStory ? 30 : 24), by)
        })

        const footW = isStory ? 880 : isFeed ? 820 : 760
        const footH = isStory ? 115 : isFeed ? 95 : 80
        const footX = (width - footW) / 2
        const footY = height - footH - (isStory ? 90 : isFeed ? 60 : 40)

        ctx.textAlign = 'center'
        ctx.font = `bold ${isStory ? 22 : isFeed ? 18 : 15}px sans-serif`
        ctx.fillStyle = '#94a3b8'
        ctx.fillText('⚡ ¡HAZ TU PEDIDO ONLINE CON GARANTÍA OFICIAL!', width / 2, footY - (isStory ? 20 : isFeed ? 16 : 12))

        const webGrad = ctx.createLinearGradient(footX, footY, footX + footW, footY)
        webGrad.addColorStop(0, '#0284c7')
        webGrad.addColorStop(1, '#0ea5e9')
        ctx.fillStyle = webGrad
        roundRect(ctx, footX, footY, footW, footH, footH / 2.6, true, false)

        ctx.font = `900 ${isStory ? 42 : isFeed ? 34 : 28}px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif`
        ctx.fillStyle = '#020617'
        ctx.fillText(`🌐 ${domainText}`, width / 2, footY + footH / 2 + (isStory ? 14 : isFeed ? 11 : 9))
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
              'w-full rounded-3xl flex flex-col justify-between relative overflow-hidden shadow-2xl border transition-all text-white select-none',
              flyerFormat === 'story' ? 'max-w-[320px] aspect-[9/16]' :
              flyerFormat === 'feed' ? 'max-w-[330px] aspect-[4/5]' :
              flyerFormat === 'square' ? 'max-w-[340px] aspect-square' :
              'max-w-[480px] aspect-[16/9]',
              flyerTheme === 'cyber' ? 'p-0 border-cyan-400/40 shadow-cyan-950/60' :
              (flyerFormat === 'story' ? 'p-4 sm:p-5' : flyerFormat === 'feed' ? 'p-3.5 sm:p-4' : flyerFormat === 'square' ? 'p-3 sm:p-3.5' : 'p-3 sm:p-3.5'),
              flyerTheme === 'cinema' ? 'bg-gradient-to-b from-black via-red-950/70 to-slate-950 border-red-500/40' :
              flyerTheme === 'emerald' ? 'bg-gradient-to-b from-slate-950 via-emerald-950/70 to-slate-950 border-emerald-500/40' :
              flyerTheme === 'blue' ? 'bg-gradient-to-b from-slate-950 via-sky-950/80 to-slate-950 border-sky-500/40' : ''
            ]"
            :style="flyerTheme === 'cyber' ? {
              backgroundImage: `url(${getCyberTemplateUrl(flyerFormat)})`,
              backgroundSize: '100% 100%',
              backgroundRepeat: 'no-repeat'
            } : {}"
          >
            <!-- ======================================================== -->
            <!-- 1. VISTA PREVIA CYBER OFICIAL (CON PLANTILLAS DE CANVA)  -->
            <!-- ======================================================== -->
            <div v-if="flyerTheme === 'cyber'" class="relative w-full h-full text-white pointer-events-none select-none">
              
              <!-- 1.1 STORY (9:16) -->
              <div v-if="flyerFormat === 'story'" class="relative w-full h-full">
                <!-- Visor Window -->
                <div class="absolute top-[13.67%] left-[17.48%] w-[65.03%] h-[28.32%] flex items-center justify-center p-2 z-10">
                  <div v-if="flyerProductType === 'physical'" class="w-full h-full bg-white rounded-2xl flex items-center justify-center p-1.5 shadow-lg border border-cyan-400/30 overflow-hidden">
                    <img :src="productImageCORS || selectedPhysicalProduct?.image" :alt="displayTitle" class="max-h-full max-w-full object-contain filter drop-shadow-sm">
                  </div>
                  <div v-else-if="flyerProductType === 'combo'" class="w-full h-full flex items-center justify-center gap-1.5 p-0.5">
                    <div v-for="(srv, idx) in (selectedPackage?.services?.slice(0, 3) || [])" :key="idx" class="flex-1 h-full rounded-xl bg-slate-950/90 border border-cyan-400/40 flex flex-col items-center justify-center p-1 shadow-md">
                      <span class="text-xl sm:text-2xl">{{ getPlatformIcon(srv.platform) }}</span>
                      <span class="text-[7px] font-black text-white truncate max-w-full mt-0.5">{{ srv.platform }}</span>
                      <span class="text-[6px] text-cyan-300 truncate font-mono">{{ srv.planLabel }}</span>
                      <span class="text-[5px] bg-emerald-500/20 text-emerald-300 font-bold px-1 rounded mt-0.5">INCLUIDO</span>
                    </div>
                  </div>
                  <div v-else class="flex flex-col items-center justify-center text-center">
                    <span class="text-4xl sm:text-5xl drop-shadow">{{ currentPlatformIcon }}</span>
                    <span class="font-black text-cyan-400 text-xs sm:text-sm uppercase tracking-wider mt-1 drop-shadow">{{ selectedPlatform }}</span>
                    <span class="text-[7px] font-mono font-bold text-sky-200 bg-cyan-950/70 border border-cyan-500/40 px-2 py-0.5 rounded-full mt-1">{{ currentPlanData?.label || '1 PERFIL' }} · 4K ULTRA HD</span>
                  </div>
                </div>

                <!-- Contenido Inferior -->
                <div class="absolute top-[44%] bottom-[8.5%] left-[10%] right-[10%] flex flex-col justify-between items-center text-center z-10 py-1">
                  <div class="bg-gradient-to-r from-amber-500 via-orange-500 to-amber-500 text-slate-950 font-black px-3 py-0.5 rounded-full shadow-lg text-[9px] uppercase tracking-wider">
                    {{ headlineBadge }}
                  </div>
                  <div class="space-y-0.5 w-full">
                    <h2 class="font-black text-xs sm:text-sm tracking-tight leading-tight line-clamp-2 text-white drop-shadow">{{ displayTitle }}</h2>
                    <p class="font-semibold text-cyan-300 text-[9px] font-mono truncate">{{ displaySubtitle }}</p>
                  </div>
                  <div class="w-full bg-slate-950/80 border border-cyan-400/30 rounded-2xl py-1.5 px-3 shadow-xl">
                    <div v-if="displayOriginalPrice" class="flex items-center justify-center gap-1 mb-0.5">
                      <span class="text-slate-400 line-through text-[9px]">Antes: $${Number(displayOriginalPrice).toLocaleString()}</span>
                      <span v-if="calculatedDiscountPct" class="bg-emerald-500/20 text-emerald-300 font-black text-[8px] px-1 rounded">-${calculatedDiscountPct}%</span>
                    </div>
                    <div v-else class="text-[8px] uppercase font-bold text-slate-400 tracking-wider">PRECIO EXCLUSIVO</div>
                    <div class="flex items-baseline justify-center gap-0.5">
                      <span class="text-xs font-black text-amber-400">$</span>
                      <span class="font-black tracking-tight text-white font-mono text-xl sm:text-2xl">{{ displayPrice }}</span>
                      <span class="text-[8px] font-bold text-slate-400 font-mono">ARS</span>
                    </div>
                    <div class="text-[7px] text-cyan-300 font-bold truncate">⚡ Entrega Inmediata · Garantía Oficial 100%</div>
                  </div>
                  <div class="w-full text-left space-y-0.5 text-[8px] sm:text-[9px] font-medium text-slate-200 px-1">
                    <div class="flex items-center gap-1.5 truncate"><span class="w-3.5 h-3.5 rounded-full bg-cyan-400 text-slate-950 flex items-center justify-center text-[8px] font-bold shrink-0">✓</span><span class="truncate">{{ bullet1 }}</span></div>
                    <div class="flex items-center gap-1.5 truncate"><span class="w-3.5 h-3.5 rounded-full bg-cyan-400 text-slate-950 flex items-center justify-center text-[8px] font-bold shrink-0">✓</span><span class="truncate">{{ bullet2 }}</span></div>
                    <div class="flex items-center gap-1.5 truncate"><span class="w-3.5 h-3.5 rounded-full bg-cyan-400 text-slate-950 flex items-center justify-center text-[8px] font-bold shrink-0">✓</span><span class="truncate">{{ bullet3 }}</span></div>
                  </div>

                </div>
              </div>

              <!-- 1.2 FEED (4:5) -->
              <div v-else-if="flyerFormat === 'feed'" class="relative w-full h-full">
                <!-- Visor Window: top: 15.14%, left: 20.15%, width: 59.71%, height: 27.44% -->
                <div class="absolute top-[15.14%] left-[20.15%] w-[59.71%] h-[27.44%] flex items-center justify-center p-1.5 z-10">
                  <div v-if="flyerProductType === 'physical'" class="w-full h-full bg-white rounded-xl flex items-center justify-center p-1 shadow border border-cyan-400/30 overflow-hidden">
                    <img :src="productImageCORS || selectedPhysicalProduct?.image" :alt="displayTitle" class="max-h-full max-w-full object-contain">
                  </div>
                  <div v-else-if="flyerProductType === 'combo'" class="w-full h-full flex items-center justify-center gap-1 p-0.5">
                    <div v-for="(srv, idx) in (selectedPackage?.services?.slice(0, 3) || [])" :key="idx" class="flex-1 h-full rounded-lg bg-slate-950/90 border border-cyan-400/40 flex flex-col items-center justify-center p-0.5">
                      <span class="text-base">{{ getPlatformIcon(srv.platform) }}</span>
                      <span class="text-[6px] font-black text-white truncate max-w-full">{{ srv.platform }}</span>
                      <span class="text-[5px] text-cyan-300 truncate font-mono">{{ srv.planLabel }}</span>
                    </div>
                  </div>
                  <div v-else class="flex flex-col items-center justify-center text-center">
                    <span class="text-3xl drop-shadow">{{ currentPlatformIcon }}</span>
                    <span class="font-black text-cyan-400 text-xs uppercase tracking-wider mt-0.5">{{ selectedPlatform }}</span>
                    <span class="text-[6px] font-mono text-sky-200 mt-0.5">4K ULTRA HD · {{ currentPlanData?.label || '1 PERFIL' }}</span>
                  </div>
                </div>

                <!-- Contenido Inferior: top: 44.5%, bottom: 8.5%, left: 10%, right: 10% -->
                <div class="absolute top-[44.5%] bottom-[8.5%] left-[10%] right-[10%] flex flex-col justify-between items-center text-center z-10 py-1">
                  <div class="bg-gradient-to-r from-amber-500 via-orange-500 to-amber-500 text-slate-950 font-black px-2.5 py-0.5 rounded-full shadow text-[8px] uppercase tracking-wider">
                    {{ headlineBadge }}
                  </div>
                  <div class="space-y-0.5 w-full">
                    <h2 class="font-black text-xs tracking-tight leading-tight line-clamp-2 text-white">{{ displayTitle }}</h2>
                    <p class="font-semibold text-cyan-300 text-[8px] font-mono truncate">{{ displaySubtitle }}</p>
                  </div>
                  <div class="w-full bg-slate-950/80 border border-cyan-400/30 rounded-xl py-1 px-2.5 shadow">
                    <div v-if="displayOriginalPrice" class="flex items-center justify-center gap-1 mb-0.5">
                      <span class="text-slate-400 line-through text-[8px]">Antes: $${Number(displayOriginalPrice).toLocaleString()}</span>
                      <span v-if="calculatedDiscountPct" class="bg-emerald-500/20 text-emerald-300 font-black text-[7px] px-1 rounded">-${calculatedDiscountPct}%</span>
                    </div>
                    <div v-else class="text-[7px] uppercase font-bold text-slate-400">PRECIO EXCLUSIVO</div>
                    <div class="flex items-baseline justify-center gap-0.5">
                      <span class="text-[10px] font-black text-amber-400">$</span>
                      <span class="font-black tracking-tight text-white font-mono text-base sm:text-lg">{{ displayPrice }}</span>
                      <span class="text-[7px] font-bold text-slate-400 font-mono">ARS</span>
                    </div>
                  </div>
                  <div class="w-full text-left space-y-0.5 text-[8px] font-medium text-slate-200 px-1">
                    <div class="flex items-center gap-1 truncate"><span class="text-cyan-400 font-bold">✓</span><span class="truncate">{{ bullet1 }}</span></div>
                    <div class="flex items-center gap-1 truncate"><span class="text-cyan-400 font-bold">✓</span><span class="truncate">{{ bullet2 }}</span></div>
                    <div class="flex items-center gap-1 truncate"><span class="text-cyan-400 font-bold">✓</span><span class="truncate">{{ bullet3 }}</span></div>
                  </div>

                </div>
              </div>

              <!-- 1.3 SQUARE (1:1) -->
              <div v-else-if="flyerFormat === 'square'" class="relative w-full h-full">
                <!-- Visor Window: top: 16.6%, left: 25.29%, width: 49.32%, height: 42.97% -->
                <div class="absolute top-[16.6%] left-[25.29%] w-[49.32%] h-[42.97%] flex items-center justify-center p-1.5 z-10">
                  <div v-if="flyerProductType === 'physical'" class="w-full h-full bg-white rounded-xl flex items-center justify-center p-1 shadow border border-cyan-400/30 overflow-hidden">
                    <img :src="productImageCORS || selectedPhysicalProduct?.image" :alt="displayTitle" class="max-h-full max-w-full object-contain">
                  </div>
                  <div v-else-if="flyerProductType === 'combo'" class="w-full h-full flex items-center justify-center gap-1 p-0.5">
                    <div v-for="(srv, idx) in (selectedPackage?.services?.slice(0, 3) || [])" :key="idx" class="flex-1 h-full rounded-lg bg-slate-950/90 border border-cyan-400/30 flex flex-col items-center justify-center p-0.5">
                      <span class="text-sm">{{ getPlatformIcon(srv.platform) }}</span>
                      <span class="text-[6px] font-black text-white truncate max-w-full">{{ srv.platform }}</span>
                    </div>
                  </div>
                  <div v-else class="flex flex-col items-center justify-center text-center">
                    <span class="text-3xl drop-shadow">{{ currentPlatformIcon }}</span>
                    <span class="font-black text-cyan-400 text-[10px] uppercase tracking-wider mt-0.5">{{ selectedPlatform }}</span>
                    <span class="text-[6px] font-mono text-sky-200 mt-0.5">4K ULTRA HD</span>
                  </div>
                </div>

                <!-- Contenido Inferior: top: 61.5%, bottom: 7%, left: 11%, right: 11% -->
                <div class="absolute top-[61.5%] bottom-[7%] left-[11%] right-[11%] flex flex-col justify-between items-center text-center z-10 py-0.5">
                  <div class="bg-gradient-to-r from-amber-500 to-orange-500 text-slate-950 font-black px-2 py-0.2 rounded-full shadow text-[7px] uppercase">
                    {{ headlineBadge }}
                  </div>
                  <div class="space-y-0.2 w-full">
                    <h2 class="font-black text-[10px] text-white line-clamp-1 leading-tight">{{ displayTitle }}</h2>
                    <p class="font-semibold text-cyan-300 text-[7px] font-mono truncate">{{ displaySubtitle }}</p>
                  </div>
                  <div class="w-full bg-slate-950/80 border border-cyan-400/30 rounded-lg py-0.5 px-2 shadow">
                    <div v-if="displayOriginalPrice" class="flex items-center justify-center gap-1">
                      <span class="text-slate-400 line-through text-[6px]">Antes: $${Number(displayOriginalPrice).toLocaleString()}</span>
                      <span v-if="calculatedDiscountPct" class="bg-emerald-500/20 text-emerald-300 font-black text-[6px] px-0.5 rounded">-${calculatedDiscountPct}%</span>
                    </div>
                    <div class="flex items-baseline justify-center gap-0.5">
                      <span class="text-[8px] font-black text-amber-400">$</span>
                      <span class="font-black text-xs text-white font-mono">{{ displayPrice }}</span>
                      <span class="text-[6px] font-bold text-slate-400 font-mono">ARS</span>
                    </div>
                  </div>
                  <div class="w-full text-left space-y-0 text-[7px] font-medium text-slate-200 px-1">
                    <div class="flex items-center gap-1 truncate"><span class="text-cyan-400 font-bold">✓</span><span class="truncate">{{ bullet1 }}</span></div>
                    <div class="flex items-center gap-1 truncate"><span class="text-cyan-400 font-bold">✓</span><span class="truncate">{{ bullet2 }}</span></div>
                  </div>

                </div>
              </div>

              <!-- 1.4 BANNER (16:9) -->
              <div v-else class="relative w-full h-full">
                <!-- Visor Window: top: 24.17%, left: 7.13%, width: 28.32%, height: 53.59% -->
                <div class="absolute top-[24.17%] left-[7.13%] w-[28.32%] h-[53.59%] flex items-center justify-center p-1.5 z-10">
                  <div v-if="flyerProductType === 'physical'" class="w-full h-full bg-white rounded-xl flex items-center justify-center p-1 shadow border border-cyan-400/30 overflow-hidden">
                    <img :src="productImageCORS || selectedPhysicalProduct?.image" :alt="displayTitle" class="max-h-full max-w-full object-contain">
                  </div>
                  <div v-else-if="flyerProductType === 'combo'" class="grid grid-cols-2 gap-1 w-full h-full p-0.5">
                    <div v-for="(srv, idx) in (selectedPackage?.services?.slice(0, 4) || [])" :key="idx" class="rounded bg-slate-950/90 border border-cyan-400/30 flex flex-col items-center justify-center p-0.5">
                      <span class="text-sm">{{ getPlatformIcon(srv.platform) }}</span>
                      <span class="text-[6px] text-white font-bold truncate max-w-full">{{ srv.platform }}</span>
                    </div>
                  </div>
                  <div v-else class="flex flex-col items-center justify-center text-center">
                    <span class="text-3xl drop-shadow">{{ currentPlatformIcon }}</span>
                    <span class="font-black text-cyan-400 text-[10px] uppercase mt-0.5">{{ selectedPlatform }}</span>
                    <span class="text-[6px] text-sky-200 font-mono mt-0.5">4K ULTRA HD</span>
                  </div>
                </div>

                <!-- Contenido Derecha: top: 14%, bottom: 10%, left: 38.5%, right: 6% -->
                <div class="absolute top-[14%] bottom-[10%] left-[38.5%] right-[6%] flex flex-col justify-between z-10 py-1 text-left">
                  <div class="inline-block bg-gradient-to-r from-amber-500 to-orange-500 text-slate-950 text-[7px] font-black px-2 py-0.5 rounded-full uppercase self-start">
                    {{ headlineBadge }}
                  </div>
                  <div class="space-y-0.2">
                    <h2 class="text-xs font-black text-white line-clamp-1 leading-tight">{{ displayTitle }}</h2>
                    <div class="text-[7px] text-cyan-300 font-mono truncate">{{ displaySubtitle }}</div>
                  </div>
                  <div class="w-full bg-slate-950/80 border border-cyan-400/30 rounded-xl py-1 px-3">
                    <div class="flex items-baseline gap-1.5">
                      <span v-if="displayOriginalPrice" class="text-[8px] text-slate-400 line-through">$${Number(displayOriginalPrice).toLocaleString()}</span>
                      <span v-if="calculatedDiscountPct" class="text-[6px] bg-emerald-500/20 text-emerald-300 font-black px-1 rounded">-${calculatedDiscountPct}%</span>
                      <div class="flex items-baseline gap-0.5">
                        <span class="text-amber-400 font-bold text-[9px]">$</span>
                        <span class="font-black text-xs text-white font-mono">{{ displayPrice }}</span>
                        <span class="text-[6px] font-bold text-slate-400 font-mono">ARS</span>
                      </div>
                    </div>
                  </div>
                  <div class="text-[7px] text-slate-300 truncate">
                    <span class="text-cyan-400 font-bold">✓</span> {{ bullet1 }}
                  </div>

                </div>
              </div>

            </div>

            <!-- ======================================================== -->
            <!-- 2. TEMAS CLÁSICOS (CINEMA, EMERALD, BLUE)                -->
            <!-- ======================================================== -->
            <template v-else>
            <!-- Efectos de Luces y Gradientes ambientales -->
            <div class="absolute -top-12 -right-12 w-44 h-44 bg-amber-500/15 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-12 -left-12 w-44 h-44 bg-purple-500/15 rounded-full blur-3xl pointer-events-none"></div>

            <!-- PARTE SUPERIOR: MARCA DE LA TIENDA & BADGE -->
            <div class="relative z-10 text-center space-y-1 shrink-0">
              <div class="flex items-center justify-between border-b border-white/10 pb-1">
                <span :class="['font-mono font-black uppercase tracking-widest text-amber-300 truncate max-w-[200px]', flyerFormat === 'square' ? 'text-[9px]' : 'text-[11px]']">
                  {{ storeName }}
                </span>
                <span :class="['font-bold bg-white/10 px-1.5 py-0.5 rounded-full border border-white/15 text-slate-300', flyerFormat === 'square' ? 'text-[8px]' : 'text-[9px]']">
                  ⭐ OFICIAL
                </span>
              </div>

              <!-- Badge Dinámico (oculto en banner para balancear espacio horizontal) -->
              <div v-if="flyerFormat !== 'banner'" :class="['inline-block bg-gradient-to-r from-amber-500 via-orange-500 to-amber-500 text-slate-950 font-black px-3 py-0.5 rounded-full shadow-lg uppercase tracking-wider', flyerFormat === 'square' ? 'text-[8px]' : 'text-[10px]']">
                {{ headlineBadge }}
              </div>
            </div>

            <!-- ============================================== -->
            <!-- CASO 1: FORMATO BANNER HORIZONTAL (16:9)       -->
            <!-- ============================================== -->
            <div v-if="flyerFormat === 'banner'" class="relative z-10 flex items-center gap-3 my-auto">
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
                  <span class="text-3xl">{{ currentPlatformIcon }}</span>
                  <span class="text-[9px] font-black text-amber-300 mt-1 uppercase">{{ selectedPlatform }}</span>
                </div>

                <!-- Modo Físico: Imagen con CORS -->
                <div v-else class="w-24 h-24 rounded-2xl bg-white p-1.5 flex items-center justify-center shadow-xl border border-white/40 overflow-hidden">
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
                <div class="inline-block bg-gradient-to-r from-amber-500 to-orange-500 text-slate-950 text-[8px] font-black px-2 py-0.5 rounded-full uppercase tracking-wider">
                  {{ headlineBadge }}
                </div>
                <h2 class="text-xs font-black tracking-tight leading-tight text-white line-clamp-1">
                  {{ displayTitle }}
                </h2>
                <div class="text-[8px] font-semibold text-amber-300 font-mono truncate">
                  {{ displaySubtitle }}
                </div>

                <!-- Precios con Tachado si corresponde -->
                <div class="flex items-baseline gap-1.5">
                  <span v-if="displayOriginalPrice" class="text-[9px] text-slate-400 line-through">
                    ${{ Number(displayOriginalPrice).toLocaleString() }}
                  </span>
                  <span v-if="calculatedDiscountPct" class="text-[7px] bg-emerald-500/20 text-emerald-300 font-black px-1 rounded">
                    -{{ calculatedDiscountPct }}%
                  </span>
                  <div class="flex items-baseline gap-0.5">
                    <span class="text-amber-400 font-bold text-[10px]">$</span>
                    <span class="font-black text-sm text-white font-mono">{{ displayPrice }}</span>
                    <span class="text-[7px] font-bold text-slate-400 font-mono">ARS</span>
                  </div>
                </div>

                <div class="text-[8px] text-slate-300 truncate">
                  <span class="text-emerald-400 font-bold">✓</span> {{ bullet1 }}
                </div>
              </div>
            </div>

            <!-- ============================================== -->
            <!-- CASO 2: FORMATOS VERTICALES (9:16, 4:5, 1:1)   -->
            <!-- ============================================== -->
            <div v-else class="relative z-10 text-center space-y-1.5 my-auto">
              
              <!-- HERO VISUAL SEGÚN EL MODO -->
              <div v-if="flyerProductType === 'combo'" class="mx-auto flex items-center justify-center gap-1.5 py-0.5">
                <div 
                  v-for="(srv, idx) in (selectedPackage?.services?.slice(0, 3) || [])" 
                  :key="idx"
                  :class="[
                    'rounded-2xl bg-slate-900/90 border border-pink-500/40 flex flex-col items-center justify-center shadow-lg transition-all',
                    flyerFormat === 'story' ? 'w-16 h-16 sm:w-18 sm:h-18 p-1' :
                    flyerFormat === 'feed' ? 'w-14 h-14 p-1' : 'w-11 h-11 p-0.5'
                  ]"
                >
                  <span :class="flyerFormat === 'square' ? 'text-base' : 'text-xl sm:text-2xl'">{{ getPlatformIcon(srv.platform) }}</span>
                  <span class="text-[7px] font-black text-white truncate max-w-full mt-0.5">{{ srv.platform }}</span>
                  <span v-if="flyerFormat !== 'square'" class="text-[6px] text-pink-300 truncate font-mono">{{ srv.planLabel }}</span>
                </div>
              </div>

              <!-- Modo Individual: Icono de streaming -->
              <div 
                v-else-if="flyerProductType === 'individual'" 
                :class="[
                  'mx-auto rounded-3xl bg-slate-900/90 border border-white/20 flex flex-col items-center justify-center shadow-2xl transition-all',
                  flyerFormat === 'story' ? 'w-24 h-24 sm:w-28 sm:h-28 p-2' :
                  flyerFormat === 'feed' ? 'w-18 h-18 sm:w-20 sm:h-20 p-1.5' : 'w-14 h-14 p-1'
                ]"
              >
                <span :class="flyerFormat === 'story' ? 'text-4xl sm:text-5xl' : flyerFormat === 'feed' ? 'text-3xl' : 'text-2xl'">
                  {{ currentPlatformIcon }}
                </span>
                <span :class="['font-black text-amber-300 uppercase tracking-wider', flyerFormat === 'square' ? 'text-[7px]' : 'text-[9px] mt-0.5']">
                  {{ selectedPlatform }}
                </span>
              </div>

              <!-- Modo Físico: Foto del producto -->
              <div 
                v-else
                :class="[
                  'mx-auto rounded-2xl bg-white flex items-center justify-center shadow-xl border border-white/40 overflow-hidden',
                  flyerFormat === 'story' ? 'w-28 h-28 sm:w-32 sm:h-32 p-2' :
                  flyerFormat === 'feed' ? 'w-22 h-22 p-1.5' : 'w-16 h-16 p-1'
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
                <h2 :class="['font-black tracking-tight leading-tight drop-shadow text-white', flyerFormat === 'square' ? 'text-[11px] line-clamp-1' : 'text-xs sm:text-sm line-clamp-2']">
                  {{ displayTitle }}
                </h2>
                <p :class="['font-semibold text-amber-300/90 font-mono mt-0.5 truncate', flyerFormat === 'square' ? 'text-[8px]' : 'text-[9px]']">
                  {{ displaySubtitle }}
                </p>
              </div>

              <!-- TARJETA DE PRECIO CON TACHADO -->
              <div :class="['rounded-2xl bg-black/60 border border-white/10 inline-block shadow-xl', flyerFormat === 'square' ? 'py-0.5 px-2.5' : 'py-1 px-3.5']">
                <!-- Precio original tachado si existe -->
                <div v-if="displayOriginalPrice" class="flex items-center justify-center gap-1 mb-0.5">
                  <span :class="['text-slate-400 line-through', flyerFormat === 'square' ? 'text-[8px]' : 'text-[9px]']">
                    Antes: ${{ Number(displayOriginalPrice).toLocaleString() }}
                  </span>
                  <span v-if="calculatedDiscountPct" :class="['bg-emerald-500/20 text-emerald-300 font-black rounded', flyerFormat === 'square' ? 'text-[7px] px-0.5' : 'text-[8px] px-1']">
                    -{{ calculatedDiscountPct }}%
                  </span>
                </div>
                <div v-else :class="['uppercase font-bold text-slate-400 tracking-wider', flyerFormat === 'square' ? 'text-[7px]' : 'text-[8px]']">
                  PRECIO EXCLUSIVO
                </div>

                <!-- Precio final -->
                <div class="flex items-baseline justify-center gap-0.5">
                  <span class="text-xs font-black text-amber-400">$</span>
                  <span :class="['font-black tracking-tight text-white font-mono', flyerFormat === 'square' ? 'text-base' : flyerFormat === 'feed' ? 'text-lg sm:text-xl' : 'text-xl sm:text-2xl']">
                    {{ displayPrice }}
                  </span>
                  <span class="text-[8px] font-bold text-slate-400 font-mono">ARS</span>
                </div>
              </div>

              <!-- PUNTOS CLAVE / BENEFICIOS -->
              <div :class="['text-left px-2 font-medium text-slate-200', flyerFormat === 'square' ? 'space-y-0 text-[8px]' : 'space-y-0.5 text-[9px]']">
                <div class="flex items-center gap-1 truncate">
                  <span class="text-emerald-400 font-bold">✓</span>
                  <span class="truncate">{{ bullet1 }}</span>
                </div>
                <div class="flex items-center gap-1 truncate">
                  <span class="text-emerald-400 font-bold">✓</span>
                  <span class="truncate">{{ bullet2 }}</span>
                </div>
                <div v-if="flyerFormat !== 'square'" class="flex items-center gap-1 truncate">
                  <span class="text-emerald-400 font-bold">✓</span>
                  <span class="truncate">{{ bullet3 }}</span>
                </div>
              </div>
            </div>

            <!-- PARTE INFERIOR: LLAMADA A LA ACCIÓN CON DOMINIO -->
            <div class="relative z-10 text-center space-y-0.5 pt-1 border-t border-white/10 shrink-0">
              <span :class="['text-slate-400 font-bold uppercase tracking-wider block', flyerFormat === 'square' ? 'text-[7px]' : 'text-[8px]']">
                ⚡ ¡Haz tu pedido online con garantía oficial!
              </span>
              <div :class="['bg-gradient-to-r from-sky-500 to-cyan-400 text-slate-950 font-black rounded-xl flex items-center justify-center gap-1 shadow-md', flyerFormat === 'square' ? 'py-1 px-2.5 text-[10px]' : 'py-1.5 px-3.5 text-xs']">
                <span class="text-xs">🌐</span>
                <span class="font-mono font-black tracking-wide">{{ displayDomain }}</span>
              </div>
            </div>
            </template>
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

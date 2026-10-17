<script setup>
import { ref, computed, watch } from 'vue'
import { useAdminStore } from '../store/admin'
import { useToastStore } from '../store/toast'

const props = defineProps({
  show: { type: Boolean, default: false },
  defaultStoreName: { type: String, default: 'StreamPro Oficial' },
  initialProduct: { type: Object, default: null },
  resellerRef: { type: String, default: 'DIRECT' },
  websiteDomain: { type: String, default: 'streamepro.me' }
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
const selectedPhysicalCat = ref('All')

// Personalización del flyer
const storeName = ref(props.defaultStoreName)
const websiteUrl = ref(props.websiteDomain || 'streamepro.me')
const headlineBadge = ref('🔥 ¡OFERTA EXCLUSIVA!')
const customPrice = ref(null)
const customTitle = ref('')
const customSubtitle = ref('')
const flyerTheme = ref('cinema') // 'cinema' | 'cyber' | 'emerald' | 'blue'
const flyerFormat = ref('story') // 'story' (9:16) | 'feed' (4:5) | 'square' (1:1) | 'banner' (16:9)
const flyerFormatLabels = {
  story: '9:16 Historia / Estado',
  feed: '4:5 Feed Instagram',
  square: '1:1 Post Cuadrado',
  banner: '16:9 Banner Horizontal'
}
const isExporting = ref(false)
const flyerPreviewRef = ref(null)

// Badges sugeridos
const suggestedBadges = [
  '🔥 ¡OFERTA EXCLUSIVA!',
  '⏱️ ENTREGA 10 MIN A 1 HORA',
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

watch(() => props.websiteDomain, (val) => {
  if (val) websiteUrl.value = val
}, { immediate: true })

// Manejar producto inicial si se abrió desde una tarjeta de producto
watch(() => props.initialProduct, (prod) => {
  if (!prod) return
  if (prod.itemType === 'physical' || prod.platform === 'Producto Físico' || prod.categoryIds || prod.price?.includes('$')) {
    flyerProductType.value = 'physical'
    selectedPhysicalId.value = String(prod.id)
    customTitle.value = prod.name || ''
    customSubtitle.value = 'Tecnología & Accesorios de Alta Calidad'
    customPrice.value = prod.priceNumeric || (parseInt(prod.price?.replace(/[$. ]/g, '').replace(',', '') || '0') / 100 || null)
    bullet1.value = 'Excelente Calidad y Diseño Garantizado'
    bullet2.value = 'Envío Rápido a Todo el País'
    bullet3.value = 'Compra 100% Segura y Garantizada Online'
  } else if (prod.platform) {
    flyerProductType.value = 'streaming'
    selectedPlatform.value = prod.platform
    selectedPlanKey.value = prod.planKey || '1_perfil'
    customTitle.value = ''
    customSubtitle.value = ''
    customPrice.value = null
  }
}, { immediate: true })

// Helper para convertir imagen a URL con CORS usando proxy seguro wsrv.nl
const getCORSImageUrl = (rawUrl) => {
  if (!rawUrl) return null
  let fullUrl = rawUrl
  if (!fullUrl.startsWith('http')) {
    fullUrl = `https://tua.ar/${rawUrl}`
  }
  // wsrv.nl responde con Access-Control-Allow-Origin: * y redimensiona para óptima calidad
  return `https://wsrv.nl/?url=${encodeURIComponent(fullUrl)}&w=800&output=jpg`
}

// Plataforma actual y sus planes
const currentPlatformData = computed(() => {
  return adminStore.pricingCatalog[selectedPlatform.value] || { name: selectedPlatform.value, plans: {} }
})

const currentPlanData = computed(() => {
  return currentPlatformData.value.plans?.[selectedPlanKey.value] || { label: '1 Perfil', clientPrice: 5000 }
})

// Filtrado de productos físicos (catálogo completo de 364 productos con buscador y categoría)
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

// Imagen con CORS para el flyer del producto seleccionado
const productImageCORS = computed(() => {
  if (flyerProductType.value === 'physical' && selectedPhysicalProduct.value?.image) {
    return getCORSImageUrl(selectedPhysicalProduct.value.image)
  }
  return null
})

// Inicializar selección de plan al cambiar plataforma
watch(selectedPlatform, (newPlat) => {
  const plans = adminStore.pricingCatalog[newPlat]?.plans || {}
  const keys = Object.keys(plans)
  if (keys.length > 0) {
    selectedPlanKey.value = keys[0]
  }
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

// Actualizar datos completos al cambiar producto físico
watch(selectedPhysicalId, (newId) => {
  if (flyerProductType.value === 'physical') {
    const prod = adminStore.physicalProducts.find(p => String(p.id) === String(newId))
    if (prod) {
      customTitle.value = prod.name || ''
      customSubtitle.value = 'Tecnología & Accesorios de Alta Calidad'
      customPrice.value = prod.priceNumeric || null
      bullet1.value = 'Excelente Calidad y Diseño Garantizado'
      bullet2.value = 'Envío Rápido a Todo el País'
      bullet3.value = 'Compra 100% Segura y Garantizada Online'
    }
  }
})

// Sincronizar al alternar entre streaming y físicos
watch(flyerProductType, (newType) => {
  if (newType === 'physical') {
    if (!selectedPhysicalId.value && adminStore.physicalProducts.length > 0) {
      selectedPhysicalId.value = String(adminStore.physicalProducts[0].id)
    }
    const prod = selectedPhysicalProduct.value
    if (prod) {
      customTitle.value = prod.name || ''
      customSubtitle.value = 'Tecnología & Accesorios de Alta Calidad'
      customPrice.value = prod.priceNumeric || null
      bullet1.value = 'Excelente Calidad y Diseño Garantizado'
      bullet2.value = 'Envío Rápido a Todo el País'
      bullet3.value = 'Compra 100% Segura y Garantizada Online'
    }
  } else {
    customTitle.value = ''
    customSubtitle.value = ''
    customPrice.value = null
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
  if (customTitle.value?.trim()) return customTitle.value.trim()
  if (flyerProductType.value === 'streaming') {
    return currentPlatformData.value?.name || selectedPlatform.value || 'Streaming'
  } else {
    return selectedPhysicalProduct.value?.name || 'Producto Tecnológico'
  }
})

// Subtítulo
const displaySubtitle = computed(() => {
  if (customSubtitle.value?.trim()) return customSubtitle.value.trim()
  if (flyerProductType.value === 'streaming') {
    return currentPlanData.value?.label || 'Suscripción Oficial'
  } else {
    return 'Tecnología & Accesorios de Alta Calidad'
  }
})

// Enlace de referencia directo del producto o tienda con atribución de comisión (3%)
const fullProductReferralUrl = computed(() => {
  const origin = typeof window !== 'undefined' && (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1')
    ? window.location.origin
    : `https://${websiteUrl.value || props.websiteDomain || 'streamepro.me'}`
  const refCode = props.resellerRef && props.resellerRef !== 'DIRECT' ? props.resellerRef : null
  
  if (flyerProductType.value === 'physical' && selectedPhysicalProduct.value?.id) {
    return refCode 
      ? `${origin}/?ref=${refCode}&prod=${selectedPhysicalProduct.value.id}`
      : `${origin}/?prod=${selectedPhysicalProduct.value.id}`
  }
  return refCode ? `${origin}/?ref=${refCode}` : `${origin}/`
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

// Carga resiliente de imagen para Canvas con triple fallback
const loadFlyerImage = (rawUrl) => {
  return new Promise((resolve) => {
    if (!rawUrl) return resolve(null)
    const fullUrl = rawUrl.startsWith('http') ? rawUrl : `https://tua.ar/${rawUrl}`
    const sources = [
      `https://wsrv.nl/?url=${encodeURIComponent(fullUrl)}&w=800&output=jpg`,
      `https://images.weserv.nl/?url=${encodeURIComponent(fullUrl)}&w=800&output=jpg`,
      fullUrl
    ]
    let currentIdx = 0

    const tryNextSource = () => {
      if (currentIdx >= sources.length) {
        return resolve(null)
      }
      const testSrc = sources[currentIdx++]
      const imageEl = new Image()
      imageEl.crossOrigin = 'anonymous'
      let timer = null

      imageEl.onload = () => {
        if (timer) clearTimeout(timer)
        resolve(imageEl)
      }
      imageEl.onerror = () => {
        if (timer) clearTimeout(timer)
        tryNextSource()
      }
      timer = setTimeout(() => {
        imageEl.src = ''
        tryNextSource()
      }, 3500)

      imageEl.src = testSrc
    }

    tryNextSource()
  })
}

// ==========================================
// RENDERIZADOR NATIVO DE CANVAS 2D MULTI-FORMATO
// (Story 9:16 | Feed 4:5 | Cuadrado 1:1 | Banner 16:9)
// ==========================================
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

      // 1. Fondo degradado según tema
      const theme = flyerTheme.value
      const bgGrad = ctx.createLinearGradient(0, 0, 0, height)
      if (theme === 'cinema') {
        bgGrad.addColorStop(0, '#030306')
        bgGrad.addColorStop(0.35, '#1e0409')
        bgGrad.addColorStop(0.7, '#2a040b')
        bgGrad.addColorStop(1, '#050508')
      } else if (theme === 'cyber') {
        bgGrad.addColorStop(0, '#03030c')
        bgGrad.addColorStop(0.35, '#1e0533')
        bgGrad.addColorStop(0.7, '#290642')
        bgGrad.addColorStop(1, '#031018')
      } else if (theme === 'emerald') {
        bgGrad.addColorStop(0, '#020805')
        bgGrad.addColorStop(0.35, '#032618')
        bgGrad.addColorStop(0.7, '#073322')
        bgGrad.addColorStop(1, '#020a06')
      } else {
        bgGrad.addColorStop(0, '#020814')
        bgGrad.addColorStop(0.35, '#071f3d')
        bgGrad.addColorStop(0.7, '#0b2b52')
        bgGrad.addColorStop(1, '#020914')
      }
      ctx.fillStyle = bgGrad
      ctx.fillRect(0, 0, width, height)

      // 2. Luces ambientales suaves de fondo
      const glowColor = theme === 'cinema' ? 'rgba(239, 68, 68, 0.28)' :
                        theme === 'cyber' ? 'rgba(168, 85, 247, 0.28)' :
                        theme === 'emerald' ? 'rgba(16, 185, 129, 0.28)' : 'rgba(14, 165, 233, 0.28)'
      const glow1 = ctx.createRadialGradient(width - 160, 200, 20, width - 160, 200, 480)
      glow1.addColorStop(0, glowColor)
      glow1.addColorStop(1, 'rgba(0,0,0,0)')
      ctx.fillStyle = glow1
      ctx.fillRect(width / 2, 0, width / 2, height / 2)

      const glow2 = ctx.createRadialGradient(160, height - 200, 20, 160, height - 200, 480)
      glow2.addColorStop(0, 'rgba(245, 158, 11, 0.22)')
      glow2.addColorStop(1, 'rgba(0,0,0,0)')
      ctx.fillStyle = glow2
      ctx.fillRect(0, height / 2, width / 2, height / 2)

      // 3. Marco exterior elegante
      roundRect(ctx, 35, 35, width - 70, height - 70, 44, false, true, 'rgba(255, 255, 255, 0.12)', 2)

      // 4. Carga de imagen si es producto físico con fallback automático
      const isPhysical = flyerProductType.value === 'physical'
      let img = null
      if (isPhysical && selectedPhysicalProduct.value?.image) {
        try {
          img = await loadFlyerImage(selectedPhysicalProduct.value.image)
        } catch (e) {
          img = null
        }
      }

      // Función auxiliar para dibujar la imagen / icono del producto ajustado armónicamente
      function drawProductBox(bx, by, bSize, rCorner = 36) {
        ctx.save()
        ctx.shadowColor = 'rgba(0, 0, 0, 0.45)'
        ctx.shadowBlur = 30
        ctx.shadowOffsetY = 12
        ctx.fillStyle = isPhysical ? '#ffffff' : 'rgba(255, 255, 255, 0.08)'
        roundRect(ctx, bx, by, bSize, bSize, rCorner, true, true, isPhysical ? 'rgba(255, 255, 255, 0.4)' : 'rgba(255, 255, 255, 0.2)', 2)
        ctx.restore()

        if (isPhysical && img) {
          ctx.save()
          ctx.beginPath()
          roundRect(ctx, bx + 10, by + 10, bSize - 20, bSize - 20, rCorner - 8, false, false)
          ctx.clip()

          ctx.fillStyle = '#ffffff'
          ctx.fillRect(bx + 10, by + 10, bSize - 20, bSize - 20)

          const padding = 20
          const availW = bSize - 20 - padding
          const availH = bSize - 20 - padding
          const ratio = Math.min(availW / img.width, availH / img.height)
          const drawW = img.width * ratio
          const drawH = img.height * ratio
          const shiftX = (bSize - 20 - drawW) / 2
          const shiftY = (bSize - 20 - drawH) / 2
          ctx.drawImage(img, 0, 0, img.width, img.height, bx + 10 + shiftX, by + 10 + shiftY, drawW, drawH)
          ctx.restore()
        } else if (isPhysical) {
          ctx.font = `${Math.round(bSize * 0.45)}px sans-serif`
          ctx.textAlign = 'center'
          ctx.textBaseline = 'middle'
          ctx.fillText('📦', bx + bSize / 2, by + bSize / 2)
        } else {
          const emoji = selectedPlatform.value === 'Netflix' ? '🍿' :
                        selectedPlatform.value === 'Disney+' ? '✨' :
                        selectedPlatform.value === 'Max' ? '🎬' :
                        selectedPlatform.value === 'IPTV' ? '📺' :
                        selectedPlatform.value === 'Spotify' ? '🎵' : '⚡'
          ctx.font = `${Math.round(bSize * 0.45)}px sans-serif`
          ctx.textAlign = 'center'
          ctx.textBaseline = 'middle'
          ctx.fillText(emoji, bx + bSize / 2, by + bSize / 2)
        }
      }

      // =====================================
      // RENDERIZADO FORMATO BANNER (16:9 - 1920x1080)
      // =====================================
      if (format === 'banner') {
        // Encabezado
        ctx.textAlign = 'left'
        ctx.textBaseline = 'middle'
        ctx.font = 'bold 36px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
        ctx.fillStyle = '#fbbf24'
        ctx.fillText(storeName.value.toUpperCase().slice(0, 32), 80, 85)

        ctx.fillStyle = 'rgba(255, 255, 255, 0.12)'
        roundRect(ctx, width - 230, 60, 150, 50, 25, true, true, 'rgba(255, 255, 255, 0.2)', 1.5)
        ctx.textAlign = 'center'
        ctx.font = 'bold 22px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
        ctx.fillStyle = '#f1f5f9'
        ctx.fillText('OFICIAL', width - 155, 85)

        ctx.strokeStyle = 'rgba(255, 255, 255, 0.12)'
        ctx.lineWidth = 1.5
        ctx.beginPath()
        ctx.moveTo(80, 130)
        ctx.lineTo(width - 80, 130)
        ctx.stroke()

        // Columna Izquierda: Producto
        const bSize = 520
        const bx = 110
        const by = 210
        drawProductBox(bx, by, bSize, 40)

        // Columna Derecha: Información
        const rightX = 700
        const badgeText = headlineBadge.value.toUpperCase()
        ctx.font = '900 24px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
        const bWidth = Math.min(ctx.measureText(badgeText).width + 50, 600)
        const bGrad = ctx.createLinearGradient(rightX, 175, rightX + bWidth, 175)
        bGrad.addColorStop(0, '#f59e0b')
        bGrad.addColorStop(1, '#ea580c')
        ctx.fillStyle = bGrad
        roundRect(ctx, rightX, 175, bWidth, 54, 27, true, false)
        ctx.textAlign = 'center'
        ctx.fillStyle = '#020617'
        ctx.fillText(badgeText, rightX + bWidth / 2, 203)

        // Título y Subtítulo
        ctx.textAlign = 'left'
        ctx.font = '900 48px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
        ctx.fillStyle = '#ffffff'
        const rawTitle = displayTitle.value
        if (ctx.measureText(rawTitle).width > 1100) {
          ctx.fillText(rawTitle.slice(0, 42) + '...', rightX, 290)
        } else {
          ctx.fillText(rawTitle, rightX, 290)
        }

        ctx.font = 'bold 28px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
        ctx.fillStyle = '#fbbf24'
        ctx.fillText(displaySubtitle.value, rightX, 345)

        // Tarjeta de Precio
        roundRect(ctx, rightX, 395, 1140, 145, 28, true, true, 'rgba(255, 255, 255, 0.16)', 2)
        ctx.fillStyle = 'rgba(0,0,0,0.65)'
        roundRect(ctx, rightX, 395, 1140, 145, 28, true, false)
        ctx.font = 'bold 18px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
        ctx.fillStyle = '#94a3b8'
        ctx.fillText('PRECIO EXCLUSIVO', rightX + 40, 435)
        ctx.font = '900 70px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
        ctx.fillStyle = '#ffffff'
        ctx.fillText(`$${displayPrice.value} ARS`, rightX + 40, 505)

        // Bullets
        const bullets = [bullet1.value, bullet2.value, bullet3.value]
        bullets.forEach((b, idx) => {
          const byy = 615 + (idx * 50)
          ctx.font = 'bold 26px sans-serif'
          ctx.fillStyle = '#10b981'
          ctx.fillText('✓', rightX + 10, byy)
          ctx.font = '600 26px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
          ctx.fillStyle = '#f1f5f9'
          ctx.fillText(b.length > 55 ? b.slice(0, 53) + '...' : b, rightX + 50, byy)
        })

        // Web Footer
        const webGrad = ctx.createLinearGradient(rightX, 810, rightX + 1140, 810)
        webGrad.addColorStop(0, '#0284c7')
        webGrad.addColorStop(1, '#0ea5e9')
        ctx.fillStyle = webGrad
        roundRect(ctx, rightX, 810, 1140, 105, 30, true, false)
        ctx.textAlign = 'center'
        ctx.font = '900 42px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
        ctx.fillStyle = '#020617'
        ctx.fillText(`🌐 ${websiteUrl.value}`, rightX + 570, 874)

      } else {
        // =====================================
        // RENDERIZADO VERTICAL (Story 9:16 | Feed 4:5 | Cuadrado 1:1)
        // =====================================
        const isStory = format === 'story'
        const isFeed = format === 'feed'

        const headerY = isStory ? 115 : isFeed ? 85 : 65
        const lineY = isStory ? 165 : isFeed ? 125 : 95
        const badgeY = isStory ? 205 : isFeed ? 150 : 115
        const badgeH = isStory ? 66 : isFeed ? 54 : 48
        const badgeFontSize = isStory ? 30 : isFeed ? 24 : 22

        const bSize = isStory ? 440 : isFeed ? 340 : 270
        const bx = (width - bSize) / 2
        const by = isStory ? 300 : isFeed ? 225 : 180

        // Encabezado
        ctx.textAlign = 'left'
        ctx.textBaseline = 'middle'
        ctx.font = `bold ${isStory ? 36 : isFeed ? 30 : 28}px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif`
        ctx.fillStyle = '#fbbf24'
        ctx.fillText(storeName.value.toUpperCase().slice(0, 28), 90, headerY)

        // Badge Oficial
        ctx.fillStyle = 'rgba(255, 255, 255, 0.12)'
        const ofW = isStory ? 150 : 130
        const ofH = isStory ? 52 : 44
        roundRect(ctx, width - 90 - ofW, headerY - ofH / 2, ofW, ofH, ofH / 2, true, true, 'rgba(255, 255, 255, 0.2)', 1.5)
        ctx.textAlign = 'center'
        ctx.font = `bold ${isStory ? 22 : 18}px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif`
        ctx.fillStyle = '#f1f5f9'
        ctx.fillText('OFICIAL', width - 90 - ofW / 2, headerY)

        // Línea
        ctx.strokeStyle = 'rgba(255, 255, 255, 0.12)'
        ctx.lineWidth = 1.5
        ctx.beginPath()
        ctx.moveTo(90, lineY)
        ctx.lineTo(width - 90, lineY)
        ctx.stroke()

        // Badge Oferta
        const badgeText = headlineBadge.value.toUpperCase()
        ctx.font = `900 ${badgeFontSize}px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif`
        const badgeWidth = Math.min(ctx.measureText(badgeText).width + 60, width - 180)
        const badgeX = (width - badgeWidth) / 2
        const badgeGrad = ctx.createLinearGradient(badgeX, badgeY, badgeX + badgeWidth, badgeY)
        badgeGrad.addColorStop(0, '#f59e0b')
        badgeGrad.addColorStop(1, '#ea580c')
        ctx.fillStyle = badgeGrad
        roundRect(ctx, badgeX, badgeY, badgeWidth, badgeH, badgeH / 2, true, false)
        ctx.textAlign = 'center'
        ctx.fillStyle = '#020617'
        ctx.fillText(badgeText, width / 2, badgeY + badgeH / 2 + 1)

        // Dibujar Foto / Icono
        drawProductBox(bx, by, bSize, isStory ? 36 : isFeed ? 32 : 28)

        // Título y Subtítulo
        let titleY = by + bSize + (isStory ? 55 : isFeed ? 42 : 36)
        ctx.textAlign = 'center'
        ctx.font = `900 ${isStory ? 50 : isFeed ? 42 : 36}px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif`
        ctx.fillStyle = '#ffffff'

        const rawTitle = displayTitle.value
        const maxTitleWidth = width - 180
        if (ctx.measureText(rawTitle).width > maxTitleWidth) {
          const words = rawTitle.split(' ')
          let line1 = ''
          let line2 = ''
          for (const w of words) {
            if (ctx.measureText(line1 + ' ' + w).width < maxTitleWidth && !line2) {
              line1 += (line1 ? ' ' : '') + w
            } else {
              line2 += (line2 ? ' ' : '') + w
            }
          }
          ctx.fillText(line1, width / 2, titleY)
          ctx.fillText(line2.slice(0, 36) + (line2.length > 36 ? '...' : ''), width / 2, titleY + (isStory ? 58 : isFeed ? 48 : 40))
          titleY += (isStory ? 65 : isFeed ? 50 : 42)
        } else {
          ctx.fillText(rawTitle, width / 2, titleY)
        }

        // Subtítulo
        ctx.font = `bold ${isStory ? 32 : isFeed ? 26 : 24}px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif`
        ctx.fillStyle = '#fbbf24'
        ctx.fillText(displaySubtitle.value, width / 2, titleY + (isStory ? 56 : isFeed ? 46 : 40))

        // Tarjeta de Precio
        const priceBoxY = titleY + (isStory ? 115 : isFeed ? 95 : 78)
        const priceBoxW = isStory ? 820 : isFeed ? 780 : 740
        const priceBoxH = isStory ? 170 : isFeed ? 135 : 115
        const priceBoxX = (width - priceBoxW) / 2
        ctx.fillStyle = 'rgba(0, 0, 0, 0.65)'
        roundRect(ctx, priceBoxX, priceBoxY, priceBoxW, priceBoxH, isStory ? 34 : 26, true, true, 'rgba(255, 255, 255, 0.16)', 2)

        ctx.font = `bold ${isStory ? 20 : isFeed ? 18 : 16}px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif`
        ctx.fillStyle = '#94a3b8'
        ctx.fillText('PRECIO EXCLUSIVO', width / 2, priceBoxY + (isStory ? 40 : isFeed ? 34 : 28))

        ctx.font = `900 ${isStory ? 78 : isFeed ? 66 : 58}px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif`
        ctx.fillStyle = '#ffffff'
        ctx.fillText(`$${displayPrice.value} ARS`, width / 2, priceBoxY + (isStory ? 118 : isFeed ? 96 : 82))

        // Puntos Clave / Beneficios
        const bulletsStartY = priceBoxY + priceBoxH + (isStory ? 45 : isFeed ? 35 : 28)
        const bullets = [bullet1.value, bullet2.value, bullet3.value]
        ctx.textAlign = 'left'
        bullets.forEach((b, idx) => {
          const byy = bulletsStartY + (idx * (isStory ? 55 : isFeed ? 44 : 36))
          ctx.font = `bold ${isStory ? 30 : isFeed ? 25 : 22}px sans-serif`
          ctx.fillStyle = '#10b981'
          ctx.fillText('✓', 140, byy)

          ctx.font = `600 ${isStory ? 30 : isFeed ? 25 : 22}px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif`
          ctx.fillStyle = '#f1f5f9'
          ctx.fillText(b.length > 46 ? b.slice(0, 44) + '...' : b, 190, byy)
        })

        // Pie de Página
        const footerY = isStory ? 1675 : isFeed ? 1155 : 915
        if (isStory || isFeed) {
          ctx.textAlign = 'center'
          ctx.font = `bold ${isStory ? 24 : 20}px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif`
          ctx.fillStyle = '#94a3b8'
          ctx.fillText('⚡ ¡HAZ TU PEDIDO ONLINE AHORA!', width / 2, footerY)
        }

        const webY = isStory ? 1715 : isFeed ? 1190 : 940
        const webH = isStory ? 110 : isFeed ? 85 : 78
        const webW = isStory ? 860 : isFeed ? 800 : 740
        const webX = (width - webW) / 2
        const webGrad = ctx.createLinearGradient(webX, webY, webX + webW, webY)
        webGrad.addColorStop(0, '#0284c7')
        webGrad.addColorStop(1, '#0ea5e9')
        ctx.fillStyle = webGrad
        roundRect(ctx, webX, webY, webW, webH, webH / 3, true, false)

        ctx.textAlign = 'center'
        ctx.font = `900 ${isStory ? 46 : isFeed ? 38 : 34}px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif`
        ctx.fillStyle = '#020617'
        ctx.fillText(`🌐 ${websiteUrl.value}`, width / 2, webY + webH / 2 + 2)
      }

      canvas.toBlob((blob) => {
        if (!blob) {
          reject(new Error('Fallo al exportar blob'))
          return
        }
        resolve(blob)
      }, 'image/png')
    } catch (e) {
      reject(e)
    }
  })
}

// Descargar imagen con motor nativo de alta definición
const downloadFlyerImage = async () => {
  isExporting.value = true
  toast.add(`Generando flyer en formato ${flyerFormatLabels[flyerFormat.value]}...`, 'info')

  try {
    const blob = await renderCanvasFlyerBlob()
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    const safeName = displayTitle.value.toLowerCase().replace(/[^a-z0-9]/g, '_').slice(0, 24)
    link.download = `flyer_${safeName}_${flyerFormat.value}.png`
    link.href = url
    link.click()
    URL.revokeObjectURL(url)

    toast.add(`¡Flyer descargado con éxito en formato ${flyerFormatLabels[flyerFormat.value]}!`, 'success')
  } catch (err) {
    console.error('Error generando flyer:', err)
    toast.add('Ocurrió un error al generar el flyer. Inténtalo de nuevo.', 'error')
  } finally {
    isExporting.value = false
  }
}

// Copiar texto para publicar en redes
const copySocialCopy = () => {
  const text = 
`🔥 *${headlineBadge.value}* 🔥\n\n` +
`📦 *${displayTitle.value}*\n` +
`📌 *Detalle:* ${displaySubtitle.value}\n` +
`💰 *PRECIO EXCLUSIVO:* $${displayPrice.value} ARS\n\n` +
`✅ ${bullet1.value}\n` +
`✅ ${bullet2.value}\n` +
`✅ ${bullet3.value}\n\n` +
`⚡ *¡Haz tu pedido 100% online con garantía oficial!*\n` +
`🌐 *Compra directa aquí:* ${fullProductReferralUrl.value}\n` +
`🏪 *${storeName.value}*`

  navigator.clipboard.writeText(text).then(() => {
    toast.add('¡Texto con tu enlace de referencia copiado! Pégalo en tu Estado o Historia.', 'success')
  })
}

// Compartir texto y enlace directo a WhatsApp
const shareToWhatsAppStatus = () => {
  const text = 
`🔥 *${headlineBadge.value}* 🔥\n\n` +
`📦 *${displayTitle.value}*\n` +
`💰 *PRECIO EXCLUSIVO:* $${displayPrice.value} ARS\n\n` +
`✅ ${bullet1.value}\n` +
`✅ ${bullet2.value}\n` +
`✅ ${bullet3.value}\n\n` +
`⚡ *Pide el tuyo online con garantía oficial:*\n` +
`👉 ${fullProductReferralUrl.value}`
  window.open(`https://wa.me/?text=${encodeURIComponent(text)}`, '_blank')
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
                MULTI-FORMATO REDES (9:16 · 4:5 · 1:1 · 16:9)
              </span>
            </h3>
            <p class="text-xs text-slate-400">Crea imágenes publicitarias profesionales para WhatsApp, Instagram, TikTok y Facebook en múltiples tamaños.</p>
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
              <span>Tecnología & Físicos ({{ adminStore.physicalProducts.length }})</span>
            </button>
          </div>

          <!-- Selector de Formato para Redes Sociales -->
          <div class="space-y-1.5 bg-[#020617] p-3 rounded-2xl border border-slate-800">
            <div class="flex justify-between items-center">
              <label class="block font-bold uppercase text-slate-400 text-[11px]">
                📐 Formato / Tamaño para Redes Sociales
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
                <span class="font-black text-[11px]">Historias / Estados</span>
                <span class="text-[9px] text-slate-500 font-mono">9:16 (WhatsApp/IG)</span>
              </button>
              <button 
                type="button"
                @click="flyerFormat = 'feed'"
                :class="['p-2 rounded-xl border text-center font-bold text-xs transition-all flex flex-col items-center gap-0.5', flyerFormat === 'feed' ? 'bg-amber-500/20 border-amber-500 text-amber-300 shadow' : 'bg-slate-950 border-slate-800 text-slate-400 hover:text-white']"
              >
                <span class="text-base">📸</span>
                <span class="font-black text-[11px]">Feed Instagram</span>
                <span class="text-[9px] text-slate-500 font-mono">4:5 (Retrato HD)</span>
              </button>
              <button 
                type="button"
                @click="flyerFormat = 'square'"
                :class="['p-2 rounded-xl border text-center font-bold text-xs transition-all flex flex-col items-center gap-0.5', flyerFormat === 'square' ? 'bg-amber-500/20 border-amber-500 text-amber-300 shadow' : 'bg-slate-950 border-slate-800 text-slate-400 hover:text-white']"
              >
                <span class="text-base">⏹️</span>
                <span class="font-black text-[11px]">Post Cuadrado</span>
                <span class="text-[9px] text-slate-500 font-mono">1:1 (Feed/Catálogo)</span>
              </button>
              <button 
                type="button"
                @click="flyerFormat = 'banner'"
                :class="['p-2 rounded-xl border text-center font-bold text-xs transition-all flex flex-col items-center gap-0.5', flyerFormat === 'banner' ? 'bg-amber-500/20 border-amber-500 text-amber-300 shadow' : 'bg-slate-950 border-slate-800 text-slate-400 hover:text-white']"
              >
                <span class="text-base">🖥️</span>
                <span class="font-black text-[11px]">Banner Horizontal</span>
                <span class="text-[9px] text-slate-500 font-mono">16:9 (Facebook/Web)</span>
              </button>
            </div>
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

          <!-- Si es Físico: Buscador completo + Selector de Categoría + Listado de 364 productos -->
          <div v-else class="space-y-3 bg-[#020617] p-3.5 rounded-2xl border border-slate-800">
            <div class="flex flex-col sm:flex-row gap-2">
              <div class="flex-1">
                <label class="block font-bold uppercase text-slate-400 mb-1">Buscar Producto</label>
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
                <label class="block font-bold uppercase text-slate-400">Seleccionar Producto del Catálogo ({{ filteredPhysicalProducts.length }})</label>
                <span class="text-[10px] font-mono text-slate-500">ID: #{{ selectedPhysicalProduct?.id }}</span>
              </div>
              <select v-model="selectedPhysicalId" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-white font-bold outline-none focus:border-amber-400">
                <option v-for="prod in filteredPhysicalProducts" :key="prod.id" :value="prod.id">
                  {{ prod.name }} — {{ prod.price }}
                </option>
              </select>
            </div>

            <!-- Previsualización Inmediata del Producto Seleccionado -->
            <div v-if="selectedPhysicalProduct" class="flex items-center gap-3 bg-slate-950 p-2.5 rounded-xl border border-slate-800">
              <div class="w-14 h-14 bg-white rounded-xl p-1 shrink-0 flex items-center justify-center shadow-sm border border-slate-700">
                <img :src="selectedPhysicalProduct.image" :alt="selectedPhysicalProduct.name" class="max-h-full max-w-full object-contain">
              </div>
              <div class="flex-1 min-w-0">
                <h5 class="text-xs font-bold text-white truncate">{{ selectedPhysicalProduct.name }}</h5>
                <span class="text-emerald-400 font-black text-xs font-mono">{{ selectedPhysicalProduct.price }}</span>
                <span class="text-[10px] text-sky-400 block font-mono">✅ Imagen HD destacada en el flyer</span>
              </div>
            </div>
          </div>

          <!-- Personalización de Título y Subtítulo -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 bg-[#020617] p-3 rounded-2xl border border-slate-800">
            <div>
              <div class="flex justify-between items-center mb-1">
                <label class="block font-bold uppercase text-slate-400 text-[11px]">Título / Nombre en Flyer</label>
                <button 
                  v-if="customTitle" 
                  type="button"
                  @click="customTitle = ''" 
                  class="text-[10px] text-amber-400 hover:underline"
                >
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
                <label class="block font-bold uppercase text-slate-400 text-[11px]">Subtítulo / Bajada en Flyer</label>
                <button 
                  v-if="customSubtitle" 
                  type="button"
                  @click="customSubtitle = ''" 
                  class="text-[10px] text-amber-400 hover:underline"
                >
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

          <!-- Datos de la Tienda y Web Oficial -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block font-bold uppercase text-slate-400 mb-1">Nombre de tu Tienda</label>
              <input v-model="storeName" type="text" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2 text-white outline-none">
            </div>
            <div>
              <label class="block font-bold uppercase text-slate-400 mb-1">Página Web en el Flyer</label>
              <input v-model="websiteUrl" type="text" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2 text-sky-400 outline-none font-mono font-bold" placeholder="streamepro.me">
            </div>
          </div>

          <!-- Banner Interactivo: Enlace de Referencia Directo del Producto -->
          <div class="bg-slate-950/90 p-3 rounded-2xl border border-sky-500/30 space-y-2">
            <div class="flex items-center justify-between text-xs">
              <span class="font-bold text-sky-400 flex items-center gap-1.5">
                <span>🔗</span> Enlace Directo con tu Comisión (3%):
              </span>
              <span class="text-[10px] font-mono text-slate-400 bg-slate-900 px-2 py-0.5 rounded border border-slate-800">
                Ref: {{ resellerRef || 'DIRECT' }}
              </span>
            </div>
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
              <input 
                :value="fullProductReferralUrl" 
                readonly 
                class="flex-1 bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-200 font-mono outline-none select-all"
              >
              <div class="flex gap-1.5">
                <button 
                  @click="copyProductReferralUrl" 
                  class="flex-1 sm:flex-none bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs px-3.5 py-2 rounded-xl transition flex items-center justify-center gap-1"
                  title="Copiar URL de Referencia"
                >
                  <span>📋</span> Copiar
                </button>
                <button 
                  @click="previewProductReferralUrl" 
                  class="flex-1 sm:flex-none bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs px-3.5 py-2 rounded-xl transition flex items-center justify-center gap-1"
                  title="Previsualizar en Nueva Pestaña"
                >
                  <span>👁️ Ver</span>
                  <span>↗</span>
                </button>
              </div>
            </div>
            <p class="text-[10px] text-slate-400">
              Al hacer clic en <strong>Ver ↗</strong> previsualizas la tienda tal como la verá tu cliente con este producto seleccionado y tu comisión vinculada.
            </p>
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
              <span>{{ isExporting ? '⏳ Generando Flyer HD...' : `⬇️ Descargar Flyer (${flyerFormatLabels[flyerFormat]})` }}</span>
            </button>
            <button 
              @click="copySocialCopy" 
              class="bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold px-4 py-3 rounded-xl border border-slate-700 transition-all flex items-center justify-center gap-1.5"
              title="Copiar texto con enlace listo para pegar en la descripción de la historia o estado"
            >
              <span>📋 Copiar Texto + Link</span>
            </button>
            <button 
              @click="previewProductReferralUrl" 
              class="bg-sky-600 hover:bg-sky-500 text-white font-bold px-4 py-3 rounded-xl shadow transition-all flex items-center justify-center gap-1.5"
              title="Previsualizar cómo verá el cliente este producto en la tienda online"
            >
              <span>👁️ Previsualizar</span>
            </button>
            <button 
              @click="shareToWhatsAppStatus" 
              class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold px-3 py-3 rounded-xl shadow transition-all flex items-center justify-center"
              title="Compartir directo a WhatsApp"
            >
              <span>📲</span>
            </button>
          </div>
        </div>

        <!-- COLUMNA DERECHA: VISTA PREVIA DEL FLYER (ADAPTABLE A CADA FORMATO) -->
        <div class="lg:col-span-5 flex flex-col items-center justify-center bg-slate-950/80 p-4 rounded-2xl border border-slate-800">
          <div class="flex items-center justify-between w-full mb-2">
            <span class="text-[11px] font-mono text-slate-400 font-bold uppercase tracking-wider">
              Vista Previa en Vivo ({{ flyerFormatLabels[flyerFormat] }})
            </span>
            <span class="text-[10px] font-mono text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded border border-amber-500/20">
              {{ flyerFormat === 'story' ? '9:16' : flyerFormat === 'feed' ? '4:5' : flyerFormat === 'square' ? '1:1' : '16:9' }}
            </span>
          </div>

          <!-- CONTENEDOR FLYER CAPTURABLE (Dinámico según formato) -->
          <div 
            ref="flyerPreviewRef" 
            :class="[
              'w-full rounded-3xl p-4 sm:p-5 flex flex-col justify-between relative overflow-hidden shadow-2xl border transition-all text-white select-none',
              flyerFormat === 'story' ? 'max-w-[280px] sm:max-w-[310px] aspect-[9/16]' :
              flyerFormat === 'feed' ? 'max-w-[290px] sm:max-w-[320px] aspect-[4/5]' :
              flyerFormat === 'square' ? 'max-w-[300px] sm:max-w-[320px] aspect-square' :
              'max-w-[430px] aspect-[16/9]',
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
            <div class="relative z-10 text-center space-y-1.5">
              <div class="flex items-center justify-between border-b border-white/10 pb-1.5">
                <span class="text-[11px] font-mono font-black uppercase tracking-widest text-amber-300 truncate max-w-[190px]">
                  {{ storeName }}
                </span>
                <span class="text-[9px] font-bold bg-white/10 px-2 py-0.5 rounded-full border border-white/15 text-slate-300">
                  OFICIAL
                </span>
              </div>

              <!-- Badge Dinámico (oculto en banner para ahorrar espacio en cabecera) -->
              <div v-if="flyerFormat !== 'banner'" class="inline-block bg-gradient-to-r from-amber-500 to-orange-500 text-slate-950 text-[10px] font-black px-3 py-0.5 rounded-full shadow-lg uppercase tracking-wider">
                {{ headlineBadge }}
              </div>
            </div>

            <!-- CONTENIDO PARA FORMATO BANNER (16:9 Horizontal) -->
            <div v-if="flyerFormat === 'banner'" class="relative z-10 flex items-center gap-3 my-auto">
              <!-- Foto / Icono en la izquierda -->
              <div :class="['w-28 h-28 rounded-2xl flex items-center justify-center p-2 shadow-xl shrink-0 overflow-hidden', flyerProductType === 'physical' ? 'bg-white border border-white/40' : 'bg-white/10 border border-white/20']">
                <img 
                  v-if="flyerProductType === 'physical' && (productImageCORS || selectedPhysicalProduct?.image)" 
                  :src="productImageCORS || selectedPhysicalProduct.image" 
                  :alt="displayTitle"
                  class="w-full h-full object-contain filter drop-shadow-sm"
                  crossorigin="anonymous"
                >
                <div v-else class="text-3xl">
                  {{ selectedPlatform === 'Netflix' ? '🍿' : selectedPlatform === 'Disney+' ? '✨' : selectedPlatform === 'Max' ? '🎬' : selectedPlatform === 'IPTV' ? '📺' : selectedPlatform === 'Spotify' ? '🎵' : '⚡' }}
                </div>
              </div>

              <!-- Info a la derecha -->
              <div class="flex-1 min-w-0 space-y-1">
                <div class="inline-block bg-gradient-to-r from-amber-500 to-orange-500 text-slate-950 text-[9px] font-black px-2 py-0.5 rounded-full uppercase tracking-wider">
                  {{ headlineBadge }}
                </div>
                <h2 class="text-sm font-black tracking-tight leading-tight text-white line-clamp-1">
                  {{ displayTitle }}
                </h2>
                <div class="text-[10px] font-semibold text-amber-300 font-mono truncate">
                  {{ displaySubtitle }}
                </div>
                <div class="flex items-baseline gap-1">
                  <span class="text-amber-400 font-bold text-xs">$</span>
                  <span class="font-black text-lg text-white font-mono">{{ displayPrice }}</span>
                  <span class="text-[9px] font-bold text-slate-400 font-mono">ARS</span>
                </div>
                <div class="text-[9px] text-slate-300 truncate">
                  <span class="text-emerald-400 font-bold">✓</span> {{ bullet1 }}
                </div>
              </div>
            </div>

            <!-- CONTENIDO PARA FORMATOS VERTICALES (Story 9:16, Feed 4:5, Cuadrado 1:1) -->
            <div v-else class="relative z-10 text-center space-y-1.5 my-auto">
              <!-- Icono / Imagen del Producto con Ajuste Armónico -->
              <div :class="[
                'mx-auto rounded-2xl flex items-center justify-center p-2 shadow-xl overflow-hidden transition-all duration-300',
                flyerProductType === 'physical' ? 'bg-white border border-white/40' : 'bg-white/10 border border-white/20',
                flyerFormat === 'story' ? 'w-28 h-28 sm:w-32 sm:h-32' :
                flyerFormat === 'feed' ? 'w-24 h-24 sm:w-26 sm:h-26' : 'w-20 h-20 sm:w-22 sm:h-22'
              ]">
                <img 
                  v-if="flyerProductType === 'physical' && (productImageCORS || selectedPhysicalProduct?.image)" 
                  :src="productImageCORS || selectedPhysicalProduct.image" 
                  :alt="displayTitle"
                  class="w-full h-full object-contain filter drop-shadow-sm"
                  crossorigin="anonymous"
                >
                <div v-else :class="flyerFormat === 'square' ? 'text-3xl' : 'text-4xl'">
                  {{ selectedPlatform === 'Netflix' ? '🍿' : selectedPlatform === 'Disney+' ? '✨' : selectedPlatform === 'Max' ? '🎬' : selectedPlatform === 'IPTV' ? '📺' : selectedPlatform === 'Spotify' ? '🎵' : '⚡' }}
                </div>
              </div>

              <div>
                <h2 :class="['font-black tracking-tight leading-tight drop-shadow-md text-white line-clamp-2', flyerFormat === 'square' ? 'text-sm' : 'text-base sm:text-lg']">
                  {{ displayTitle }}
                </h2>
                <p class="text-[10px] font-semibold text-amber-300/90 font-mono mt-0.5 truncate">
                  {{ displaySubtitle }}
                </p>
              </div>

              <!-- Precio Gigante -->
              <div class="py-1 px-3 rounded-xl bg-black/50 border border-white/10 backdrop-blur-md inline-block shadow-lg">
                <span class="text-[8px] uppercase font-bold text-slate-400 tracking-wider block">PRECIO EXCLUSIVO</span>
                <div class="flex items-baseline justify-center gap-1">
                  <span class="text-xs font-black text-amber-400">$</span>
                  <span :class="['font-black tracking-tight text-white', flyerFormat === 'square' ? 'text-lg' : 'text-xl sm:text-2xl']">{{ displayPrice }}</span>
                  <span class="text-[10px] font-bold text-slate-400 font-mono">ARS</span>
                </div>
              </div>

              <!-- Bullets / Beneficios -->
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

            <!-- PARTE INFERIOR: Llamada a la Acción de la Web Oficial -->
            <div class="relative z-10 text-center space-y-1 pt-1.5 border-t border-white/10">
              <span class="text-[8px] text-slate-400 font-bold uppercase tracking-wider block">
                ⚡ ¡Haz tu pedido online con garantía oficial!
              </span>
              <div class="bg-gradient-to-r from-sky-500 to-cyan-400 text-slate-950 font-black py-1.5 px-3 rounded-xl flex items-center justify-center gap-1.5 shadow-md text-xs">
                <span class="text-xs">🌐</span>
                <span class="font-mono text-[11px] font-black tracking-wide">{{ websiteUrl }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useAdminStore } from '../store/admin'
import { useToastStore } from '../store/toast'
import { generateAttractiveShareCopy } from '../utils/marketing'

const props = defineProps({
  show: { type: Boolean, default: false },
  payload: { type: Object, default: null },
  storeName: { type: String, default: 'StreamePro Oficial' },
  resellerRef: { type: String, default: 'DIRECT' },
  contactPhone: { type: String, default: '' },
  promoCode: { type: String, default: '' },
  promoLabel: { type: String, default: '' },
  isReseller: { type: Boolean, default: false }
})

const emit = defineEmits(['close', 'openInStudio'])
const adminStore = useAdminStore()
const toast = useToastStore()
const isExporting = ref(false)

// Selector de formato: 'story' (9:16) o 'post' (1:1)
const formatKey = ref('story')

watch(() => props.payload, (newP) => {
  if (newP?.format === 'post' || newP?.format === 'story') {
    formatKey.value = newP.format
  }
}, { immediate: true })

// Teléfono editable directo del distribuidor
const localContactPhone = ref(props.contactPhone || '')
watch(() => props.contactPhone, (newVal) => {
  if (newVal) localContactPhone.value = newVal
}, { immediate: true })

const effectiveDistributorPhone = computed(() => {
  return (localContactPhone.value || props.contactPhone || '+54 9 291 565-8321').trim()
})

const activePromoCode = computed(() => String(props.payload?.promoCode || props.promoCode || '').trim().toUpperCase())
const activePromoLabel = computed(() => String(props.payload?.promoLabel || props.promoLabel || 'Descuento Oficial').trim())

const isMultiMode = computed(() => props.payload?.mode === 'multi' && Array.isArray(props.payload?.items) && props.payload.items.length > 0)

const normalizedItems = computed(() => {
  if (!props.payload) return []
  if (isMultiMode.value) {
    return props.payload.items.slice(0, 4).map((it) => ({
      id: it.id || it.gameId || '',
      itemType: it.itemType || props.payload?.productType || 'gaming',
      title: String(it.title || it.name || 'Producto StreamePro'),
      subtitle: String(it.subtitle || it.platform || it.planLabel || it.category || 'Oficial'),
      price: Number(it.priceNumeric || it.price || it.clientPrice || 0),
      originalPrice: Number(it.originalPrice || 0),
      image: it.image || '',
      tag: String(it.tag || it.platform || it.badge || 'OFICIAL').slice(0, 22)
    }))
  }
  const p = props.payload
  return [{
    title: String(p.title || p.name || 'Producto StreamePro'),
    subtitle: String(p.subtitle || ''),
    price: Number(p.priceNumeric || p.price || 0),
    originalPrice: Number(p.originalPrice || 0),
    image: p.image || (p.productType === 'package' ? '/flyers/combos/combo-default.jpg' : ''),
    tag: String(p.tag || p.platform || 'OFICIAL').slice(0, 24),
    services: Array.isArray(p.services) ? p.services : null,
    discountPct: Number(p.discountPct || 0),
    features: p.features || null
  }]
})

const mainItem = computed(() => normalizedItems.value[0] || {
  title: 'StreamePro Oficial',
  subtitle: 'Catálogo Oficial',
  price: 0,
  originalPrice: 0,
  image: '',
  tag: 'OFICIAL',
  services: null,
  discountPct: 0
})

const effectiveProductType = computed(() => {
  if (props.payload?.productType) return props.payload.productType
  if (mainItem.value.services?.length) return 'package'
  if (props.payload?.categoryIds) return 'physical'
  const tag = (mainItem.value.tag || '').toLowerCase()
  const title = (mainItem.value.title || '').toLowerCase()
  if (tag.includes('ps5') || tag.includes('ps4') || tag.includes('gaming') || title.includes('ps5') || title.includes('ps4')) return 'gaming'
  if (tag.includes('4k') || tag.includes('streaming') || tag.includes('perfil') || tag.includes('hd')) return 'streaming'
  return 'gaming'
})

const plaqueHeadline = computed(() => {
  if (isMultiMode.value) {
    return props.payload?.title || 'Mi Selección en StreamePro'
  }
  return mainItem.value.title
})

const plaqueSubline = computed(() => {
  if (isMultiMode.value) {
    return props.payload?.subtitle || `${normalizedItems.value.length} productos con garantía oficial y entrega rápida`
  }
  if (mainItem.value.subtitle) return mainItem.value.subtitle
  const pt = effectiveProductType.value
  if (pt === 'physical') return '🚚 Stock Inmediato · Envíos a Todo el País · Factura Oficial'
  if (pt === 'package') return '🎁 Combo Ahorro VIP · Cuentas Privadas con PIN · 30d Garantía'
  if (pt === 'streaming') return '🎬 4K Ultra HD • HDR10 · PIN Privado Exclusivo · Sin Bloqueos'
  const lic = props.payload?.licenseType === 'secondary' ? 'Cuenta Secundaria (Ahorro)' : 'Cuenta Primaria (Juegas con tu usuario)'
  return `🎮 Licencia Digital Original · ${lic} · 60-90m`
})

// Píldora de Plataforma / Licencia (Ejemplo Image 2: "PS5 · PRIMARIA")
const qualityPillText = computed(() => {
  const p = props.payload || {}
  const pt = effectiveProductType.value
  if (pt === 'gaming') {
    const lic = (p.licenseType || mainItem.value.tag || '').toLowerCase().includes('secund') ? 'SECUNDARIA' : 'PRIMARIA'
    const platformRaw = (p.platform || mainItem.value.tag || '').toUpperCase()
    if (platformRaw.includes('PS5') && !platformRaw.includes('PS4')) return `PS5 · ${lic}`
    if (platformRaw.includes('PS4') && !platformRaw.includes('PS5')) return `PS4 · ${lic}`
    if (platformRaw.includes('PS3')) return 'PS3 · DIGITAL'
    if (platformRaw.includes('STEAM') || platformRaw.includes('PC')) return 'PC STEAM · CLAVE'
    if (platformRaw.includes('XBOX')) return `XBOX · ${lic}`
    if (platformRaw.includes('SWITCH')) return 'NINTENDO SWITCH'
    return `PS5 / PS4 · ${lic}`
  }
  if (pt === 'streaming') return '4K ULTRA HD · HDR10'
  if (pt === 'package') return 'MEGA COMBO VIP · MULTI-APP'
  if (pt === 'physical') return 'PRODUCTO FÍSICO · OFICIAL'
  return 'OFICIAL · STREAMEPRO'
})

const displayCtaStore = computed(() => {
  if (props.storeName && props.storeName !== 'StreamePro Oficial' && props.storeName.length < 24) {
    return props.storeName.toUpperCase()
  }
  return 'STREAMEPRO.ME'
})

const appendPromoToUrl = (baseUrl) => {
  const code = activePromoCode.value
  if (!code) return baseUrl
  try {
    const u = new URL(baseUrl)
    u.searchParams.set('promo', code)
    return u.toString()
  } catch {
    return baseUrl.includes('?') ? `${baseUrl}&promo=${encodeURIComponent(code)}` : `${baseUrl}?promo=${encodeURIComponent(code)}`
  }
}

const directShareUrl = computed(() => {
  const origin = (typeof window !== 'undefined' && window.location?.origin)
    ? window.location.origin
    : 'https://streamepro.me'
  if (props.payload?.shareUrl) return appendPromoToUrl(props.payload.shareUrl)
  const ref = props.resellerRef && props.resellerRef !== 'DIRECT' ? props.resellerRef : ''
  const pt = props.payload?.productType || 'gaming'
  const pid = props.payload?.id || ''

  if (isMultiMode.value || !pid) {
    const gIds = normalizedItems.value
      .filter(it => (it.itemType === 'gaming' || pt === 'gaming') && it.id)
      .map(it => it.id)
    const pIds = normalizedItems.value
      .filter(it => (it.itemType === 'physical' || pt === 'physical') && it.id)
      .map(it => it.id)

    if (gIds.length > 0 || pIds.length > 0) {
      const qs = new URLSearchParams()
      if (gIds.length > 0) qs.set('games', gIds.slice(0, 4).join(','))
      if (pIds.length > 0) qs.set('prods', pIds.slice(0, 4).join(','))
      if (ref) qs.set('ref', ref)
      if (activePromoCode.value) qs.set('promo', activePromoCode.value)
      if (props.payload?.title) qs.set('title', String(props.payload.title).slice(0, 56))
      return `${origin}/seleccion?${qs.toString()}`
    }
    if (pt === 'package') return appendPromoToUrl(ref ? `${origin}/combos?ref=${encodeURIComponent(ref)}` : `${origin}/combos`)
    if (pt === 'gaming') return appendPromoToUrl(ref ? `${origin}/juegos?ref=${encodeURIComponent(ref)}` : `${origin}/juegos`)
    if (pt === 'physical') return appendPromoToUrl(ref ? `${origin}/tienda?ref=${encodeURIComponent(ref)}` : `${origin}/tienda`)
    return appendPromoToUrl(ref ? `${origin}/?ref=${encodeURIComponent(ref)}` : `${origin}/`)
  }
  if (pt === 'gaming') {
    const lic = props.payload?.licenseType || 'primary'
    const base = ref
      ? `${origin}/juego/${encodeURIComponent(pid)}/${encodeURIComponent(ref)}?lic=${lic}`
      : `${origin}/juego/${encodeURIComponent(pid)}?lic=${lic}`
    return appendPromoToUrl(base)
  }
  if (pt === 'physical') {
    return appendPromoToUrl(ref ? `${origin}/producto/${encodeURIComponent(pid)}/${encodeURIComponent(ref)}` : `${origin}/producto/${encodeURIComponent(pid)}`)
  }
  if (pt === 'package') {
    return appendPromoToUrl(ref ? `${origin}/combo/${encodeURIComponent(pid)}/${encodeURIComponent(ref)}` : `${origin}/combo/${encodeURIComponent(pid)}`)
  }
  return appendPromoToUrl(ref ? `${origin}/servicio/${encodeURIComponent(pid)}/${encodeURIComponent(ref)}` : `${origin}/servicio/${encodeURIComponent(pid)}`)
})

const getCORSUrl = (raw) => {
  if (!raw) return null
  let u = String(raw)
  if (!u.startsWith('http')) {
    const base = typeof window !== 'undefined' ? window.location.origin : 'https://streamepro.me'
    u = u.startsWith('/') ? `${base}${u}` : `https://www.revende.com.ar/${u}`
  }
  if (typeof window !== 'undefined' && u.startsWith(window.location.origin)) return u
  return `https://wsrv.nl/?url=${encodeURIComponent(u)}&w=1200&output=jpg`
}

const loadImg = (url) => new Promise((resolve) => {
  if (!url) return resolve(null)
  const img = new Image()
  img.crossOrigin = 'anonymous'
  const timer = setTimeout(() => resolve(null), 5000)
  img.onload = () => { clearTimeout(timer); resolve(img) }
  img.onerror = () => { clearTimeout(timer); resolve(null) }
  img.src = url
})

const roundRectPath = (ctx, x, y, w, h, r) => {
  ctx.beginPath()
  ctx.moveTo(x + r, y)
  ctx.lineTo(x + w - r, y)
  ctx.quadraticCurveTo(x + w, y, x + w, y + r)
  ctx.lineTo(x + w, y + h - r)
  ctx.quadraticCurveTo(x + w, y + h, x + w - r, y + h)
  ctx.lineTo(x + r, y + h)
  ctx.quadraticCurveTo(x, y + h, x, y + h - r)
  ctx.lineTo(x, y + r)
  ctx.quadraticCurveTo(x, y, x + r, y)
  ctx.closePath()
}

const wrapTextLines = (ctx, text, maxWidth, maxLines = 3) => {
  const words = String(text || '').trim().split(/\s+/)
  const lines = []
  let cur = words[0] || ''
  for (let i = 1; i < words.length; i++) {
    const w = words[i]
    const test = cur + ' ' + w
    if (ctx.measureText(test).width <= maxWidth) {
      cur = test
    } else {
      lines.push(cur)
      cur = w
      if (lines.length === maxLines - 1) {
        break
      }
    }
  }
  if (cur) lines.push(cur)
  return lines.slice(0, maxLines)
}

// RENDERIZADO OFICIAL CANVAS DE ALTA DEFINICIÓN (HISTORIA 9:16 O POST 1:1)
async function renderPlaqueBlob(targetFormat = null) {
  const isStory = (targetFormat || formatKey.value) === 'story'
  const W = isStory ? 1080 : 1024
  const H = isStory ? 1920 : 1024
  const canvas = document.createElement('canvas')
  canvas.width = W
  canvas.height = H
  const ctx = canvas.getContext('2d')

  // 1. Cargar fondo oficial con marco metálico y chispas
  const bgPath = isStory
    ? '/flyers/template-story-streamepro.jpg'
    : '/flyers/template-post-streamepro.jpg'

  const bgImg = await loadImg(getCORSUrl(bgPath))
  if (bgImg) {
    ctx.drawImage(bgImg, 0, 0, W, H)
  } else {
    // Fallback cyber ambient background
    const bgGrad = ctx.createLinearGradient(0, 0, 0, H)
    bgGrad.addColorStop(0, '#040814')
    bgGrad.addColorStop(0.5, '#071530')
    bgGrad.addColorStop(1, '#020617')
    ctx.fillStyle = bgGrad
    ctx.fillRect(0, 0, W, H)
  }

  // Cargar imagen del producto
  const mainImgEl = await loadImg(getCORSUrl(mainItem.value.image))

  if (isStory) {
    // ==========================================
    // FORMATO HISTORIA 9:16 (DISEÑO IMAGEN 2)
    // ==========================================
    const cardW = 760
    const cardH = 680
    const cardX = (W - cardW) / 2 // 160
    const cardY = 240
    const cardR = 44

    // 2. Tarjeta Squircle Superior de la Portada
    ctx.save()
    ctx.shadowColor = 'rgba(0, 0, 0, 0.95)'
    ctx.shadowBlur = 38
    ctx.shadowOffsetY = 16
    roundRectPath(ctx, cardX, cardY, cardW, cardH, cardR)
    ctx.fillStyle = '#050814'
    ctx.fill()
    ctx.restore()

    ctx.save()
    roundRectPath(ctx, cardX, cardY, cardW, cardH, cardR)
    ctx.clip()

    if (mainImgEl) {
      // Fondo difuminado de ambientación
      ctx.save()
      ctx.filter = 'blur(20px) brightness(0.38) saturate(1.2)'
      ctx.drawImage(mainImgEl, cardX - 20, cardY - 20, cardW + 40, cardH + 40)
      ctx.restore()

      // Arte frontal con aspect-ratio correcto
      const ar = mainImgEl.width / mainImgEl.height
      let dw, dh
      if (ar > cardW / cardH) {
        dw = cardW
        dh = cardW / ar
      } else {
        dh = cardH
        dw = cardH * ar
      }
      const dx = cardX + (cardW - dw) / 2
      const dy = cardY + (cardH - dh) / 2
      ctx.shadowColor = 'rgba(0, 0, 0, 0.9)'
      ctx.shadowBlur = 30
      ctx.drawImage(mainImgEl, dx, dy, dw, dh)
    } else {
      // Fallback si no hay carátula
      ctx.fillStyle = '#091024'
      ctx.fillRect(cardX, cardY, cardW, cardH)
      ctx.fillStyle = '#ffffff'
      ctx.font = '900 36px sans-serif'
      ctx.textAlign = 'center'
      ctx.fillText(plaqueHeadline.value, cardX + cardW / 2, cardY + cardH / 2)
    }
    ctx.restore()

    // Borde brillante de la tarjeta
    ctx.save()
    roundRectPath(ctx, cardX, cardY, cardW, cardH, cardR)
    ctx.strokeStyle = 'rgba(56, 189, 248, 0.45)'
    ctx.lineWidth = 3
    ctx.stroke()
    ctx.restore()

    // 3. Píldora de Plataforma / Licencia (Ej: "PS5 · PRIMARIA")
    const pillY = 970
    const pillH = 58
    const pillText = qualityPillText.value
    ctx.save()
    ctx.font = '900 24px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
    const ptw = ctx.measureText(pillText).width
    const pillW = Math.max(260, ptw + 56)
    const pillX = (W - pillW) / 2

    const pillGrad = ctx.createLinearGradient(pillX, pillY, pillX + pillW, pillY)
    pillGrad.addColorStop(0, '#8b5cf6')
    pillGrad.addColorStop(1, '#6366f1')
    ctx.shadowColor = 'rgba(124, 58, 237, 0.5)'
    ctx.shadowBlur = 24
    ctx.fillStyle = pillGrad
    roundRectPath(ctx, pillX, pillY, pillW, pillH, pillH / 2)
    ctx.fill()

    ctx.strokeStyle = 'rgba(196, 181, 253, 0.6)'
    ctx.lineWidth = 2
    ctx.stroke()
    ctx.shadowBlur = 0

    ctx.fillStyle = '#ffffff'
    ctx.textAlign = 'center'
    ctx.textBaseline = 'middle'
    ctx.fillText(pillText, W / 2, pillY + pillH / 2 + 1)
    ctx.restore()

    // 4. Título del Producto (Tipografía blanca condensada en mayúsculas)
    ctx.save()
    ctx.textAlign = 'center'
    ctx.textBaseline = 'middle'
    ctx.fillStyle = '#ffffff'
    ctx.shadowColor = 'rgba(0, 0, 0, 0.95)'
    ctx.shadowBlur = 16
    ctx.shadowOffsetY = 4
    ctx.font = '900 48px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Impact, sans-serif'
    const titleLines = wrapTextLines(ctx, plaqueHeadline.value.toUpperCase(), 840, 2)
    const startTitleY = titleLines.length === 1 ? 1110 : 1080
    titleLines.forEach((line, idx) => {
      ctx.fillText(line, W / 2, startTitleY + idx * 56)
    })
    ctx.restore()

    // 5. Bloque de Precio Gigante Menta/Cian ($ 120.388)
    const priceY = 1280
    const priceText = mainItem.value.price > 0
      ? `$ ${Number(mainItem.value.price).toLocaleString('es-AR')}`
      : 'CONSULTAR'
    ctx.save()
    ctx.textAlign = 'center'
    ctx.textBaseline = 'middle'
    ctx.fillStyle = '#00f5a0' // Verde menta brillante estilo Imagen 2
    ctx.font = '900 92px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, monospace'
    ctx.shadowColor = 'rgba(0, 245, 160, 0.55)'
    ctx.shadowBlur = 28
    ctx.fillText(priceText, W / 2, priceY)
    ctx.shadowBlur = 0

    // Subtítulo "por transferencia"
    ctx.fillStyle = '#94a3b8'
    ctx.font = '600 24px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
    ctx.fillText('por transferencia', W / 2, priceY + 68)
    ctx.restore()

    // 6. Botón CTA Inferior: "COMPRALO EN \n STREAMEPRO.ME"
    const btnW = 760
    const btnH = 114
    const btnX = (W - btnW) / 2 // 160
    const btnY = 1530
    const btnR = 36

    ctx.save()
    const btnGrad = ctx.createLinearGradient(btnX, btnY, btnX + btnW, btnY)
    btnGrad.addColorStop(0, '#00f5a0')
    btnGrad.addColorStop(1, '#00d9f5')
    ctx.shadowColor = 'rgba(0, 245, 160, 0.45)'
    ctx.shadowBlur = 32
    ctx.shadowOffsetY = 10
    ctx.fillStyle = btnGrad
    roundRectPath(ctx, btnX, btnY, btnW, btnH, btnR)
    ctx.fill()

    ctx.strokeStyle = '#ffffff'
    ctx.lineWidth = 2
    ctx.stroke()
    ctx.shadowBlur = 0

    // Texto interior botón
    ctx.textAlign = 'center'
    ctx.textBaseline = 'middle'
    ctx.fillStyle = '#020617'
    ctx.font = '900 20px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
    ctx.fillText('COMPRALO EN', W / 2, btnY + 38)

    ctx.font = '900 40px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
    ctx.fillText(displayCtaStore.value, W / 2, btnY + 80)
    ctx.restore()

  } else {
    // ==========================================
    // FORMATO POST / PLACA 1:1 (DISEÑO IMAGEN 3)
    // ==========================================
    const cardW = 460
    const cardH = 380
    const cardX = (W - cardW) / 2 // 282
    const cardY = 105
    const cardR = 28

    // 2. Tarjeta Squircle Superior de la Portada
    ctx.save()
    ctx.shadowColor = 'rgba(0, 0, 0, 0.95)'
    ctx.shadowBlur = 30
    ctx.shadowOffsetY = 12
    roundRectPath(ctx, cardX, cardY, cardW, cardH, cardR)
    ctx.fillStyle = '#050814'
    ctx.fill()
    ctx.restore()

    ctx.save()
    roundRectPath(ctx, cardX, cardY, cardW, cardH, cardR)
    ctx.clip()

    if (mainImgEl) {
      ctx.save()
      ctx.filter = 'blur(16px) brightness(0.38) saturate(1.2)'
      ctx.drawImage(mainImgEl, cardX - 10, cardY - 10, cardW + 20, cardH + 20)
      ctx.restore()

      const ar = mainImgEl.width / mainImgEl.height
      let dw, dh
      if (ar > cardW / cardH) {
        dw = cardW
        dh = cardW / ar
      } else {
        dh = cardH
        dw = cardH * ar
      }
      const dx = cardX + (cardW - dw) / 2
      const dy = cardY + (cardH - dh) / 2
      ctx.shadowColor = 'rgba(0, 0, 0, 0.9)'
      ctx.shadowBlur = 24
      ctx.drawImage(mainImgEl, dx, dy, dw, dh)
    } else {
      ctx.fillStyle = '#091024'
      ctx.fillRect(cardX, cardY, cardW, cardH)
      ctx.fillStyle = '#ffffff'
      ctx.font = '900 24px sans-serif'
      ctx.textAlign = 'center'
      ctx.fillText(plaqueHeadline.value, cardX + cardW / 2, cardY + cardH / 2)
    }
    ctx.restore()

    ctx.save()
    roundRectPath(ctx, cardX, cardY, cardW, cardH, cardR)
    ctx.strokeStyle = 'rgba(56, 189, 248, 0.45)'
    ctx.lineWidth = 2.5
    ctx.stroke()
    ctx.restore()

    // 3. Píldora de Plataforma / Licencia
    const pillY = 512
    const pillH = 38
    const pillText = qualityPillText.value
    ctx.save()
    ctx.font = '900 16px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
    const ptw = ctx.measureText(pillText).width
    const pillW = Math.max(180, ptw + 40)
    const pillX = (W - pillW) / 2

    const pillGrad = ctx.createLinearGradient(pillX, pillY, pillX + pillW, pillY)
    pillGrad.addColorStop(0, '#8b5cf6')
    pillGrad.addColorStop(1, '#6366f1')
    ctx.shadowColor = 'rgba(124, 58, 237, 0.5)'
    ctx.shadowBlur = 18
    ctx.fillStyle = pillGrad
    roundRectPath(ctx, pillX, pillY, pillW, pillH, pillH / 2)
    ctx.fill()

    ctx.strokeStyle = 'rgba(196, 181, 253, 0.6)'
    ctx.lineWidth = 1.5
    ctx.stroke()
    ctx.shadowBlur = 0

    ctx.fillStyle = '#ffffff'
    ctx.textAlign = 'center'
    ctx.textBaseline = 'middle'
    ctx.fillText(pillText, W / 2, pillY + pillH / 2 + 1)
    ctx.restore()

    // 4. Título del Producto
    ctx.save()
    ctx.textAlign = 'center'
    ctx.textBaseline = 'middle'
    ctx.fillStyle = '#ffffff'
    ctx.shadowColor = 'rgba(0, 0, 0, 0.95)'
    ctx.shadowBlur = 12
    ctx.shadowOffsetY = 3
    ctx.font = '900 30px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Impact, sans-serif'
    const titleLines = wrapTextLines(ctx, plaqueHeadline.value.toUpperCase(), 800, 2)
    const startTitleY = titleLines.length === 1 ? 585 : 570
    titleLines.forEach((line, idx) => {
      ctx.fillText(line, W / 2, startTitleY + idx * 36)
    })
    ctx.restore()

    // 5. Bloque de Precio Gigante Menta/Cian ($ 120.388)
    const priceY = 680
    const priceText = mainItem.value.price > 0
      ? `$ ${Number(mainItem.value.price).toLocaleString('es-AR')}`
      : 'CONSULTAR'
    ctx.save()
    ctx.textAlign = 'center'
    ctx.textBaseline = 'middle'
    ctx.fillStyle = '#00f5a0'
    ctx.font = '900 62px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, monospace'
    ctx.shadowColor = 'rgba(0, 245, 160, 0.55)'
    ctx.shadowBlur = 20
    ctx.fillText(priceText, W / 2, priceY)
    ctx.shadowBlur = 0

    ctx.fillStyle = '#94a3b8'
    ctx.font = '600 16px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
    ctx.fillText('por transferencia', W / 2, priceY + 44)
    ctx.restore()

    // 6. Botón CTA Inferior
    const btnW = 560
    const btnH = 80
    const btnX = (W - btnW) / 2
    const btnY = 795
    const btnR = 26

    ctx.save()
    const btnGrad = ctx.createLinearGradient(btnX, btnY, btnX + btnW, btnY)
    btnGrad.addColorStop(0, '#00f5a0')
    btnGrad.addColorStop(1, '#00d9f5')
    ctx.shadowColor = 'rgba(0, 245, 160, 0.45)'
    ctx.shadowBlur = 24
    ctx.shadowOffsetY = 8
    ctx.fillStyle = btnGrad
    roundRectPath(ctx, btnX, btnY, btnW, btnH, btnR)
    ctx.fill()

    ctx.strokeStyle = '#ffffff'
    ctx.lineWidth = 1.5
    ctx.stroke()
    ctx.shadowBlur = 0

    ctx.textAlign = 'center'
    ctx.textBaseline = 'middle'
    ctx.fillStyle = '#020617'
    ctx.font = '900 13px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
    ctx.fillText('COMPRALO EN', W / 2, btnY + 26)

    ctx.font = '900 27px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
    ctx.fillText(displayCtaStore.value, W / 2, btnY + 54)
    ctx.restore()
  }

  return await new Promise((resolve) => canvas.toBlob(resolve, 'image/png', 0.95))
}

const attractiveCopy = computed(() => {
  const p = props.payload || {}
  const pt = p.productType || (p.categoryIds ? 'physical' : 'streaming')
  return generateAttractiveShareCopy({
    type: pt === 'package' ? 'combo' : pt,
    title: plaqueHeadline.value,
    subtitle: plaqueSubline.value,
    price: isMultiMode.value ? 0 : mainItem.value.price,
    originalPrice: isMultiMode.value ? 0 : mainItem.value.originalPrice,
    platform: p.platform || mainItem.value.tag || '',
    license: p.licenseType || '',
    storeName: props.storeName,
    phone: effectiveDistributorPhone.value,
    url: directShareUrl.value,
    promoCode: activePromoCode.value,
    promoDiscountPct: Number(p.discountPct || 0)
  })
})

const copyAttractiveText = async () => {
  try {
    await navigator.clipboard.writeText(attractiveCopy.value)
    toast.add('📋 ¡Texto promocional atractivo copiado para compartir!', 'success')
  } catch {
    toast.add(attractiveCopy.value, 'info')
  }
}

const copyPlaqueLink = async () => {
  try {
    await navigator.clipboard.writeText(directShareUrl.value)
    toast.add('🔗 Enlace directo copiado para compartir', 'success')
  } catch {
    toast.add(directShareUrl.value, 'info')
  }
}

const openWhatsAppWithAttractiveText = () => {
  const url = `https://api.whatsapp.com/send?text=${encodeURIComponent(attractiveCopy.value)}`
  window.open(url, '_blank')
}

const downloadPlaquePng = async () => {
  if (isExporting.value) return
  isExporting.value = true
  try {
    const blob = await renderPlaqueBlob()
    if (!blob) throw new Error('Error de render')
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    const slug = plaqueHeadline.value.toLowerCase().replace(/[^a-z0-9]/g, '_').slice(0, 28) || 'oferta'
    const prefix = formatKey.value === 'story' ? 'historia' : 'post'
    a.download = `${prefix}_${slug}_streamepro.png`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    setTimeout(() => {
      try { URL.revokeObjectURL(url) } catch (_) {}
    }, 60000)
    toast.add(`🖼️ ¡${formatKey.value === 'story' ? 'Historia' : 'Placa'} descargada en HD!`, 'success')
  } catch (e) {
    toast.add('No se pudo generar la imagen en este dispositivo.', 'error')
  } finally {
    isExporting.value = false
  }
}

const sharePlaqueDirect = async () => {
  if (isExporting.value) return
  isExporting.value = true
  const shareText = attractiveCopy.value

  try {
    const blob = await renderPlaqueBlob()
    if (blob && typeof navigator !== 'undefined' && navigator.share && navigator.canShare) {
      const fileName = formatKey.value === 'story' ? 'streamepro_historia.png' : 'streamepro_post.png'
      const file = new File([blob], fileName, { type: 'image/png' })
      if (navigator.canShare({ files: [file] })) {
        await navigator.share({
          title: plaqueHeadline.value,
          text: shareText,
          files: [file]
        })
        toast.add('✅ ¡Imagen y texto compartidos con éxito!', 'success')
        return
      }
    }
    // Fallback escritorio: descargar placa y abrir WhatsApp con mensaje prearmado
    if (blob) {
      const url = URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      const slug = plaqueHeadline.value.toLowerCase().replace(/[^a-z0-9]/g, '_').slice(0, 24) || 'oferta'
      a.download = `${formatKey.value}_${slug}_streamepro.png`
      document.body.appendChild(a)
      a.click()
      document.body.removeChild(a)
      setTimeout(() => {
        try { URL.revokeObjectURL(url) } catch (_) {}
      }, 60000)
    }
    window.open(`https://api.whatsapp.com/send?text=${encodeURIComponent(shareText)}`, '_blank')
    toast.add('📲 Imagen descargada y WhatsApp abierto con el texto promocional listo', 'success')
  } catch (err) {
    if (err?.name !== 'AbortError') {
      window.open(`https://api.whatsapp.com/send?text=${encodeURIComponent(shareText)}`, '_blank')
    }
  } finally {
    isExporting.value = false
  }
}

const shareToPlatform = async (platform) => {
  const url = directShareUrl.value
  const title = plaqueHeadline.value
  const copy = attractiveCopy.value

  try {
    adminStore.recordMarketingClick({
      type: effectiveProductType.value,
      targetId: String(props.payload?.id || props.payload?.title || ''),
      ref: props.resellerRef || 'DIRECT',
      channel: platform,
      promoCode: activePromoCode.value || ''
    })
  } catch (_) {}

  if (platform === 'whatsapp') {
    // Si soporta Web Share API con imagen en móvil
    if (typeof navigator !== 'undefined' && navigator.share && navigator.canShare) {
      try {
        isExporting.value = true
        const blob = await renderPlaqueBlob()
        if (blob) {
          const file = new File([blob], 'streamepro_whatsapp.png', { type: 'image/png' })
          if (navigator.canShare({ files: [file] })) {
            await navigator.share({ title, text: copy, files: [file] })
            return
          }
        }
      } catch (e) {
        if (e?.name === 'AbortError') return
      } finally {
        isExporting.value = false
      }
    }
    window.open(`https://api.whatsapp.com/send?text=${encodeURIComponent(copy)}`, '_blank')
    toast.add('💬 Abriendo WhatsApp con mensaje listo...', 'info')
    return
  }

  if (platform === 'telegram') {
    window.open(`https://t.me/share/url?url=${encodeURIComponent(url)}&text=${encodeURIComponent(copy)}`, '_blank')
    toast.add('✈️ Abriendo Telegram...', 'info')
    return
  }

  if (platform === 'facebook') {
    window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`, '_blank')
    toast.add('📘 Abriendo Facebook...', 'info')
    return
  }

  if (platform === 'instagram') {
    if (typeof navigator !== 'undefined' && navigator.share && navigator.canShare) {
      try {
        isExporting.value = true
        const blob = await renderPlaqueBlob()
        if (blob) {
          const file = new File([blob], 'streamepro_instagram.png', { type: 'image/png' })
          if (navigator.canShare({ files: [file] })) {
            await navigator.share({ title, text: copy, files: [file] })
            toast.add('📸 ¡Listo para publicar en Instagram!', 'success')
            return
          }
        }
      } catch (err) {
        if (err?.name === 'AbortError') return
      } finally {
        isExporting.value = false
      }
    }
    await downloadPlaquePng()
    try {
      await navigator.clipboard.writeText(copy)
      toast.add('📸 ¡Imagen descargada y texto copiado para pegar en Instagram!', 'success')
    } catch {
      toast.add('📸 ¡Imagen descargada para Instagram!', 'success')
    }
    return
  }

  if (platform === 'copy') {
    await copyPlaqueLink()
    return
  }

  if (platform === 'native') {
    await sharePlaqueDirect()
    return
  }
}
</script>

<template>
  <div v-if="show && payload" class="fixed inset-0 z-[85] flex items-center justify-center p-3 sm:p-5 bg-black/90 backdrop-blur-md animate-in fade-in duration-200 overflow-y-auto">
    <div class="bg-slate-900 border border-slate-700/80 w-full max-w-xl rounded-3xl shadow-2xl overflow-hidden text-white flex flex-col my-auto max-h-[96vh]">
      
      <!-- ENCABEZADO CON MARCA STREAMEPRO Y BOTÓN DE CIERRE -->
      <div class="px-5 py-3.5 border-b border-slate-800 flex items-center justify-between bg-slate-950/80 shrink-0">
        <div class="flex items-center gap-2.5 min-w-0">
          <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-cyan-400 via-sky-500 to-blue-600 flex items-center justify-center font-black text-xs text-slate-950 shadow shrink-0">
            SP
          </div>
          <div class="min-w-0">
            <h3 class="text-sm sm:text-base font-black text-white truncate">Compartir en Historias &amp; Redes</h3>
            <p class="text-[11px] text-slate-400 truncate">Diseño de alta atracción con plantilla oficial StreamePro</p>
          </div>
        </div>
        <button @click="emit('close')" type="button" class="w-9 h-9 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700 font-bold text-sm flex items-center justify-center cursor-pointer shrink-0">
          ✕
        </button>
      </div>

      <!-- CONTENIDO SCROLLEABLE -->
      <div class="p-4 sm:p-5 overflow-y-auto space-y-4 bg-[#020617] flex-1">
        
        <!-- SELECTOR DE FORMATO: HISTORIA 9:16 VS POST / PLACA 1:1 -->
        <div class="flex items-center gap-2 bg-slate-950 p-1.5 rounded-2xl border border-slate-800 shadow-inner">
          <button 
            type="button" 
            @click="formatKey = 'story'"
            :class="[
              'flex-1 py-2 px-3 rounded-xl font-black text-xs transition-all flex items-center justify-center gap-2 cursor-pointer',
              formatKey === 'story'
                ? 'bg-gradient-to-r from-pink-600 via-purple-600 to-indigo-600 text-white shadow-lg shadow-purple-950/70 border border-purple-400/40'
                : 'text-slate-400 hover:text-white hover:bg-slate-900'
            ]"
          >
            <span class="text-sm">📱</span>
            <span>Historia (9:16)</span>
            <span class="text-[9px] font-mono opacity-80 hidden xs:inline">Instagram / WhatsApp</span>
          </button>
          <button 
            type="button" 
            @click="formatKey = 'post'"
            :class="[
              'flex-1 py-2 px-3 rounded-xl font-black text-xs transition-all flex items-center justify-center gap-2 cursor-pointer',
              formatKey === 'post'
                ? 'bg-gradient-to-r from-cyan-600 via-blue-600 to-indigo-600 text-white shadow-lg shadow-blue-950/70 border border-cyan-400/40'
                : 'text-slate-400 hover:text-white hover:bg-slate-900'
            ]"
          >
            <span class="text-sm">🖼️</span>
            <span>Post / Placa (1:1)</span>
            <span class="text-[9px] font-mono opacity-80 hidden xs:inline">Feed / Grupos</span>
          </button>
        </div>

        <!-- VISTA PREVIA REACTIVA EN VIVO CON LA PLANTILLA OFICIAL -->
        <div class="flex justify-center">
          
          <!-- ============================================== -->
          <!-- 1. PREVIEW HISTORIA 9:16 (ESTILO IMAGEN 2)     -->
          <!-- ============================================== -->
          <div
            v-if="formatKey === 'story'"
            class="w-full max-w-[280px] sm:max-w-[310px] aspect-[9/16] rounded-3xl overflow-hidden relative shadow-2xl border border-cyan-500/30 select-none bg-slate-950"
            style="background-image: url('/flyers/template-story-streamepro.jpg'); background-size: 100% 100%; background-repeat: no-repeat;"
          >
            <!-- Carátula / Portada Squircle Superior -->
            <div class="absolute top-[12.5%] left-[14.5%] w-[71%] h-[35.4%] rounded-2xl overflow-hidden bg-slate-950/90 border border-cyan-400/30 flex items-center justify-center shadow-2xl">
              <template v-if="mainItem.image">
                <img
                  :src="mainItem.image"
                  :alt="mainItem.title"
                  class="absolute inset-0 w-full h-full object-cover filter blur-md scale-110 brightness-40 saturate-125 pointer-events-none"
                />
                <img
                  :src="mainItem.image"
                  :alt="mainItem.title"
                  class="relative z-10 max-w-full max-h-full object-contain p-1 drop-shadow-[0_12px_24px_rgba(0,0,0,0.9)]"
                />
              </template>
              <div v-else class="text-center p-3">
                <span class="text-2xl block mb-1">🎮</span>
                <span class="text-[10px] font-black text-white line-clamp-2">{{ mainItem.title }}</span>
              </div>
            </div>

            <!-- Píldora Morada: Plataforma · Licencia (Ej: PS5 · PRIMARIA) -->
            <div class="absolute top-[50.2%] left-[12%] w-[76%] flex items-center justify-center">
              <span class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white text-[10px] sm:text-[11px] font-black uppercase px-3 py-1 rounded-full border border-purple-300/40 shadow-lg tracking-wider">
                {{ qualityPillText }}
              </span>
            </div>

            <!-- Título Blanco Impactante -->
            <div class="absolute top-[55.2%] left-[6%] w-[88%] text-center px-1">
              <h4 class="text-xs sm:text-sm font-black text-white leading-tight uppercase line-clamp-2 drop-shadow-[0_2px_8px_rgba(0,0,0,0.9)]">
                {{ plaqueHeadline }}
              </h4>
            </div>

            <!-- Bloque de Precio Menta Gigante ($ 120.388) -->
            <div class="absolute top-[64.5%] left-[5%] w-[90%] flex flex-col items-center justify-center text-center">
              <span class="text-2xl sm:text-3xl font-black font-mono text-[#00f5a0] tracking-tight drop-shadow-[0_0_16px_rgba(0,245,160,0.6)] leading-none">
                {{ mainItem.price > 0 ? `$ ${Number(mainItem.price).toLocaleString('es-AR')}` : 'CONSULTAR' }}
              </span>
              <span class="text-[9px] sm:text-[9.5px] text-slate-300 font-semibold tracking-wide mt-1">
                por transferencia
              </span>
            </div>

            <!-- Botón CTA Inferior Menta/Cian: COMPRALO EN STREAMEPRO.ME -->
            <div class="absolute top-[78.8%] left-[14%] w-[72%] h-[6.8%] rounded-2xl bg-gradient-to-r from-[#00f5a0] to-[#00d9f5] border border-white/60 shadow-[0_8px_20px_rgba(0,245,160,0.4)] flex flex-col items-center justify-center text-[#020617] leading-none px-2 select-none">
              <span class="text-[8px] font-black tracking-widest uppercase opacity-90">COMPRALO EN</span>
              <span class="text-[12px] sm:text-[13px] font-black tracking-wider uppercase mt-0.5 truncate max-w-full">
                {{ displayCtaStore }}
              </span>
            </div>
          </div>

          <!-- ============================================== -->
          <!-- 2. PREVIEW POST / PLACA 1:1 (ESTILO IMAGEN 3)  -->
          <!-- ============================================== -->
          <div
            v-else
            class="w-full max-w-[320px] sm:max-w-[350px] aspect-square rounded-3xl overflow-hidden relative shadow-2xl border border-cyan-500/30 select-none bg-slate-950"
            style="background-image: url('/flyers/template-post-streamepro.jpg'); background-size: 100% 100%; background-repeat: no-repeat;"
          >
            <!-- Carátula / Portada Squircle Central -->
            <div class="absolute top-[10.5%] left-[27.5%] w-[45%] h-[37%] rounded-2xl overflow-hidden bg-slate-950/90 border border-cyan-400/30 flex items-center justify-center shadow-xl">
              <template v-if="mainItem.image">
                <img
                  :src="mainItem.image"
                  :alt="mainItem.title"
                  class="absolute inset-0 w-full h-full object-cover filter blur-md scale-110 brightness-40 saturate-125 pointer-events-none"
                />
                <img
                  :src="mainItem.image"
                  :alt="mainItem.title"
                  class="relative z-10 max-w-full max-h-full object-contain p-1 drop-shadow-[0_8px_16px_rgba(0,0,0,0.9)]"
                />
              </template>
              <div v-else class="text-center p-2">
                <span class="text-xl block mb-0.5">🎮</span>
                <span class="text-[9px] font-black text-white line-clamp-2">{{ mainItem.title }}</span>
              </div>
            </div>

            <!-- Píldora Morada: Plataforma · Licencia -->
            <div class="absolute top-[50%] left-[12%] w-[76%] flex items-center justify-center">
              <span class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white text-[9px] sm:text-[10px] font-black uppercase px-2.5 py-0.5 rounded-full border border-purple-300/40 shadow tracking-wider">
                {{ qualityPillText }}
              </span>
            </div>

            <!-- Título Blanco Impactante -->
            <div class="absolute top-[55.5%] left-[6%] w-[88%] text-center px-1">
              <h4 class="text-[11px] sm:text-xs font-black text-white leading-tight uppercase line-clamp-2 drop-shadow">
                {{ plaqueHeadline }}
              </h4>
            </div>

            <!-- Bloque de Precio Menta Gigante -->
            <div class="absolute top-[65.5%] left-[5%] w-[90%] flex flex-col items-center justify-center text-center">
              <span class="text-xl sm:text-2xl font-black font-mono text-[#00f5a0] tracking-tight drop-shadow-[0_0_12px_rgba(0,245,160,0.6)] leading-none">
                {{ mainItem.price > 0 ? `$ ${Number(mainItem.price).toLocaleString('es-AR')}` : 'CONSULTAR' }}
              </span>
              <span class="text-[8.5px] text-slate-300 font-semibold tracking-wide mt-0.5">
                por transferencia
              </span>
            </div>

            <!-- Botón CTA Inferior Menta/Cian -->
            <div class="absolute top-[77.8%] left-[22.5%] w-[55%] h-[8%] rounded-xl bg-gradient-to-r from-[#00f5a0] to-[#00d9f5] border border-white/60 shadow-[0_6px_16px_rgba(0,245,160,0.35)] flex flex-col items-center justify-center text-[#020617] leading-none px-2 select-none">
              <span class="text-[7.5px] font-black tracking-widest uppercase opacity-90">COMPRALO EN</span>
              <span class="text-[11px] sm:text-[12px] font-black tracking-wider uppercase mt-0.5 truncate max-w-full">
                {{ displayCtaStore }}
              </span>
            </div>
          </div>
        </div>

        <!-- CAJA DE TEXTO ATRACTIVO Y ENLACE DE COMPRA PARA WHATSAPP & REDES (DISPONIBLE PARA TODOS) -->
        <div class="bg-slate-950/90 rounded-2xl border border-sky-500/30 p-3 sm:p-3.5 space-y-2 shadow-lg">
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-black text-sky-300 flex items-center gap-1.5 uppercase tracking-wide">
              <span>💬</span>
              <span>Texto Atractivo &amp; Enlace Oficial</span>
            </span>
            <div class="flex items-center gap-1.5">
              <button
                type="button"
                @click="copyAttractiveText"
                class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-[10px] font-bold border border-slate-700 flex items-center gap-1 transition active:scale-95 cursor-pointer"
                title="Copiar texto listo para compartir"
              >
                <span>📋 Copiar Texto</span>
              </button>
              <button
                type="button"
                @click="copyPlaqueLink"
                class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-sky-300 text-[10px] font-bold border border-slate-700 flex items-center gap-1 transition active:scale-95 cursor-pointer"
                title="Copiar enlace de compra"
              >
                <span>🔗 Copiar Enlace</span>
              </button>
            </div>
          </div>
          <div class="bg-slate-900/90 rounded-xl p-2.5 border border-white/5 text-[11px] font-mono text-slate-300 whitespace-pre-wrap leading-relaxed max-h-28 overflow-y-auto custom-scrollbar select-all">
{{ attractiveCopy }}
          </div>
        </div>

        <!-- BOTÓN PRINCIPAL DE DESCARGA HD -->
        <button
          type="button"
          @click="downloadPlaquePng"
          :disabled="isExporting"
          class="w-full bg-gradient-to-r from-emerald-500 via-teal-500 to-cyan-500 hover:from-emerald-400 hover:to-cyan-400 text-slate-950 font-black py-3 px-4 rounded-2xl shadow-xl shadow-emerald-950/60 transition-all flex items-center justify-center gap-2 text-xs sm:text-sm cursor-pointer active:scale-95 disabled:opacity-50"
        >
          <span class="text-base">{{ isExporting ? '⏳' : '📥' }}</span>
          <span>{{ isExporting ? 'Generando Imagen HD...' : `Descargar ${formatKey === 'story' ? 'Historia HD (1080×1920)' : 'Placa HD (1024×1024)'}` }}</span>
        </button>

        <!-- BOTONERA DE DIFUSIÓN RÁPIDA EN REDES -->
        <div class="bg-slate-950/90 rounded-2xl border border-slate-800 p-3 space-y-2 shadow-lg">
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-black text-slate-200 uppercase tracking-wide flex items-center gap-1.5">
              <span class="text-amber-400">🚀</span> Difusión Directa
            </span>
            <span class="text-[9px] text-slate-500 font-mono">1 Clic Directo</span>
          </div>

          <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
            <!-- WhatsApp -->
            <button
              type="button"
              @click="shareToPlatform('whatsapp')"
              class="p-2.5 rounded-xl bg-emerald-600/20 hover:bg-emerald-600/30 border border-emerald-500/40 text-emerald-300 font-bold text-xs flex items-center justify-center gap-1.5 transition active:scale-95 cursor-pointer shadow"
              title="Compartir en WhatsApp"
            >
              <span>💬</span>
              <span>WhatsApp</span>
            </button>

            <!-- Instagram -->
            <button
              type="button"
              @click="shareToPlatform('instagram')"
              class="p-2.5 rounded-xl bg-gradient-to-r from-pink-600/20 to-purple-600/20 hover:from-pink-600/30 hover:to-purple-600/30 border border-pink-500/40 text-pink-300 font-bold text-xs flex items-center justify-center gap-1.5 transition active:scale-95 cursor-pointer shadow"
              title="Compartir en Historias o Feed de Instagram"
            >
              <span>📸</span>
              <span>Instagram</span>
            </button>

            <!-- Facebook -->
            <button
              type="button"
              @click="shareToPlatform('facebook')"
              class="p-2.5 rounded-xl bg-blue-600/20 hover:bg-blue-600/30 border border-blue-500/40 text-blue-300 font-bold text-xs flex items-center justify-center gap-1.5 transition active:scale-95 cursor-pointer shadow"
              title="Compartir en Facebook"
            >
              <span>📘</span>
              <span>Facebook</span>
            </button>

            <!-- Telegram -->
            <button
              type="button"
              @click="shareToPlatform('telegram')"
              class="p-2.5 rounded-xl bg-sky-500/20 hover:bg-sky-500/30 border border-sky-400/40 text-sky-300 font-bold text-xs flex items-center justify-center gap-1.5 transition active:scale-95 cursor-pointer shadow"
              title="Compartir en Telegram"
            >
              <span>✈️</span>
              <span>Telegram</span>
            </button>
          </div>

          <div class="grid grid-cols-2 gap-2 pt-1">
            <!-- Compartir Nativo (Apps en Celular) -->
            <button
              type="button"
              @click="shareToPlatform('native')"
              class="p-2 rounded-xl bg-indigo-950/70 hover:bg-indigo-900 text-indigo-300 hover:text-white border border-indigo-500/40 font-bold text-xs flex items-center justify-center gap-1.5 transition active:scale-95 cursor-pointer"
            >
              <span>📲</span>
              <span>Compartir en Apps</span>
            </button>

            <!-- Copiar Enlace Directo -->
            <button
              type="button"
              @click="shareToPlatform('copy')"
              class="p-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-white border border-slate-700 font-bold text-xs flex items-center justify-center gap-1.5 transition active:scale-95 cursor-pointer"
            >
              <span>🔗</span>
              <span>Copiar Enlace</span>
            </button>
          </div>
        </div>

        <!-- WHATSAPP DEL DISTRIBUIDOR (SOLO DISTRIBUIDORES / RESELLERS) -->
        <div v-if="isReseller" class="bg-slate-950/80 rounded-2xl border border-emerald-500/30 p-3 space-y-1.5 shadow-lg">
          <div class="flex items-center justify-between">
            <label class="text-[11px] font-black uppercase text-emerald-300 flex items-center gap-1.5">
              <span>📲</span> Tu WhatsApp de Distribuidor
            </label>
            <span class="text-[9px] text-slate-400 font-mono">Personaliza tu contacto oficial</span>
          </div>
          <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-emerald-400 font-bold text-xs">💬</span>
            <input
              v-model="localContactPhone"
              type="text"
              placeholder="Ej: +54 9 11 1234-5678"
              class="w-full bg-slate-900 border border-emerald-500/40 rounded-xl py-2 pl-9 pr-3 text-white text-xs font-mono font-bold outline-none focus:border-emerald-300 transition"
            />
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
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
const toast = useToastStore()
const isExporting = ref(false)

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

// Selector de Estilos Visuales para Placas Rápidas
const plaqueThemeKey = ref('playstation')

const PLAQUE_THEMES = {
  playstation: {
    id: 'playstation',
    label: '🎮 PlayStation Blue',
    glowRgba: 'rgba(0, 112, 209, 0.45)',
    borderHex: '#0070d1',
    accentHex: '#93c5fd',
    badgeGrad: ['#0070d1', '#00439c'],
    bgGrad: ['#020817', '#061433', '#020617'],
    cssPill: 'bg-blue-600/25 text-blue-300 border-blue-500/50',
    cssPrice: 'text-blue-300',
    cssBorder: 'border-blue-500/40'
  },
  crimson: {
    id: 'crimson',
    label: '🔴 4K Cinema',
    glowRgba: 'rgba(225, 29, 72, 0.42)',
    borderHex: '#e11d48',
    accentHex: '#fda4af',
    badgeGrad: ['#e11d48', '#9f1239'],
    bgGrad: ['#160309', '#270613', '#0a0205'],
    cssPill: 'bg-rose-500/20 text-rose-300 border-rose-400/50',
    cssPrice: 'text-rose-300',
    cssBorder: 'border-rose-500/40'
  },
  gold: {
    id: 'gold',
    label: '👑 Gold VIP Combos',
    glowRgba: 'rgba(245, 158, 11, 0.38)',
    borderHex: '#f59e0b',
    accentHex: '#fde68a',
    badgeGrad: ['#f59e0b', '#d97706'],
    bgGrad: ['#140e04', '#1f1608', '#0d0802'],
    cssPill: 'bg-amber-500/20 text-amber-300 border-amber-400/50',
    cssPrice: 'text-amber-300',
    cssBorder: 'border-amber-500/40'
  },
  emerald: {
    id: 'emerald',
    label: '💬 Emerald WhatsApp',
    glowRgba: 'rgba(16, 185, 129, 0.38)',
    borderHex: '#10b981',
    accentHex: '#a7f3d0',
    badgeGrad: ['#059669', '#047857'],
    bgGrad: ['#02120d', '#052117', '#010c08'],
    cssPill: 'bg-emerald-500/20 text-emerald-300 border-emerald-400/50',
    cssPrice: 'text-emerald-300',
    cssBorder: 'border-emerald-500/40'
  },
  cyber: {
    id: 'cyber',
    label: '⚡ Cyber Neon',
    glowRgba: 'rgba(56, 189, 248, 0.35)',
    borderHex: '#38bdf8',
    accentHex: '#7dd3fc',
    badgeGrad: ['#0284c7', '#2563eb'],
    bgGrad: ['#040814', '#0b1224', '#020617'],
    cssPill: 'bg-sky-500/20 text-sky-300 border-sky-400/50',
    cssPrice: 'text-sky-300',
    cssBorder: 'border-sky-500/40'
  }
}

// Auto-selección inteligente de tema según el tipo de producto ofrecido
watch(() => props.payload, (p) => {
  if (p) {
    const pt = p.productType || (p.categoryIds ? 'physical' : (p.services ? 'package' : 'gaming'))
    const titleStr = (p.title || p.name || p.platform || '').toLowerCase()
    if (pt === 'gaming' || titleStr.includes('ps5') || titleStr.includes('ps4') || titleStr.includes('playstation') || titleStr.includes('juego') || titleStr.includes('fifa') || titleStr.includes('gta')) {
      plaqueThemeKey.value = 'playstation'
    } else if (pt === 'package' || titleStr.includes('combo') || titleStr.includes('promo')) {
      plaqueThemeKey.value = 'gold'
    } else if (pt === 'streaming' || titleStr.includes('max') || titleStr.includes('disney') || titleStr.includes('spotify') || titleStr.includes('iptv') || titleStr.includes('4k')) {
      plaqueThemeKey.value = 'crimson'
    } else if (pt === 'physical') {
      plaqueThemeKey.value = 'emerald'
    } else {
      plaqueThemeKey.value = 'cyber'
    }
  }
}, { immediate: true })

const accentTheme = computed(() => {
  return PLAQUE_THEMES[plaqueThemeKey.value] || PLAQUE_THEMES.cyber
})

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

const isGamingProduct = computed(() => effectiveProductType.value === 'gaming')
const isStreamingProduct = computed(() => effectiveProductType.value === 'streaming')
const isPackageProduct = computed(() => effectiveProductType.value === 'package')
const isPhysicalProduct = computed(() => effectiveProductType.value === 'physical')

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
  const lic = props.payload?.licenseType === 'secondary' ? 'Cuenta Secundaria (Juega con usuario del juego)' : 'Cuenta Primaria (Juega con tu usuario personal)'
  return `🎮 Licencia Digital Original · ${lic} · Garantía Permanente`
})

const qualityTagText = computed(() => {
  const pt = effectiveProductType.value
  if (pt === 'gaming') {
    const t = (mainItem.value.title || '').toUpperCase()
    if (t.includes('PS5') && !t.includes('PS4')) return 'PLAYSTATION 5 DIGITAL'
    if (t.includes('PS4') && !t.includes('PS5')) return 'PLAYSTATION 4 DIGITAL'
    return 'PS5 & PS4 DIGITAL'
  }
  if (pt === 'streaming') return '4K ULTRA HD • HDR10'
  if (pt === 'package') return '🔥 MEGA COMBO VIP'
  if (pt === 'physical') return '🚚 ENVÍO NACIONAL'
  return (mainItem.value.tag || 'OFICIAL').toUpperCase()
})

const trustGuaranteeText = computed(() => {
  const pt = effectiveProductType.value
  if (pt === 'gaming') return '🛡️ LICENCIA ORIGINAL · ENTREGA INMEDIATA 60/90M · GARANTÍA PERMANENTE'
  if (pt === 'streaming') return '🛡️ GARANTÍA OFICIAL · PIN PRIVADO EXCLUSIVO · RENOVACIÓN MES A MES'
  if (pt === 'package') return '🛡️ GARANTÍA INTEGRAL EN TODAS LAS CUENTAS · AHORRO COMPROBADO'
  if (pt === 'physical') return '🛡️ GARANTÍA OFICIAL · COMPRA PROTEGIDA · SEGUIMIENTO EN VIVO'
  return '🛡️ GARANTÍA OFICIAL · ATENCIÓN VIP PERSONALIZADA'
})

const deliveryPillText = computed(() => {
  const pt = effectiveProductType.value
  if (pt === 'gaming') return '🟢 60-90m ENTREGA'
  if (pt === 'streaming') return '🟢 10-15m ENTREGA'
  if (pt === 'package') return '🟢 ACTIVACIÓN 10-15m'
  if (pt === 'physical') return '📦 STOCK INMEDIATO'
  return '🟢 ENTREGA RÁPIDA'
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
  return `https://wsrv.nl/?url=${encodeURIComponent(u)}&w=900&output=jpg`
}

const loadImg = (url) => new Promise((resolve) => {
  if (!url) return resolve(null)
  const img = new Image()
  img.crossOrigin = 'anonymous'
  const timer = setTimeout(() => resolve(null), 4000)
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

const drawLogoOverlayCrest = (ctx, x, y) => {
  ctx.save()
  const rawLabel = String(props.storeName || 'StreamePro Oficial').toUpperCase()
  const label = rawLabel.length > 20 ? rawLabel.slice(0, 19) + '…' : rawLabel
  ctx.font = '900 15px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
  const textW = ctx.measureText(label).width
  const crestW = Math.max(280, Math.min(340, textW + 90))
  const crestH = 56

  ctx.shadowColor = 'rgba(0, 0, 0, 0.75)'
  ctx.shadowBlur = 12
  const bg = ctx.createLinearGradient(x, y, x + crestW, y + crestH)
  bg.addColorStop(0, 'rgba(4, 10, 24, 0.94)')
  bg.addColorStop(1, 'rgba(12, 22, 44, 0.90)')
  ctx.fillStyle = bg
  roundRectPath(ctx, x, y, crestW, crestH, crestH / 2)
  ctx.fill()
  ctx.lineWidth = 2
  ctx.strokeStyle = '#38bdf8'
  ctx.stroke()
  ctx.shadowBlur = 0

  // Monograma SP circular
  const circGrad = ctx.createLinearGradient(x + 8, y + 8, x + 48, y + 48)
  circGrad.addColorStop(0, '#38bdf8')
  circGrad.addColorStop(1, '#2563eb')
  ctx.fillStyle = circGrad
  ctx.beginPath()
  ctx.arc(x + 28, y + 28, 20, 0, Math.PI * 2)
  ctx.fill()
  ctx.lineWidth = 1.5
  ctx.strokeStyle = '#ffffff'
  ctx.stroke()

  ctx.fillStyle = '#ffffff'
  ctx.font = '900 15px sans-serif'
  ctx.textAlign = 'center'
  ctx.textBaseline = 'middle'
  ctx.fillText('SP', x + 28, y + 29)

  ctx.textAlign = 'left'
  ctx.textBaseline = 'alphabetic'
  ctx.fillStyle = '#ffffff'
  ctx.font = '900 15px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
  ctx.fillText(label, x + 58, y + 25)

  ctx.fillStyle = '#38bdf8'
  ctx.font = 'bold 10px monospace'
  ctx.fillText('✓ TIENDA OFICIAL VERIFICADA', x + 58, y + 43)
  ctx.restore()
}

// Resplandor especular de estrella / lente sobre el precio
const drawStarFlare = (ctx, fx, fy) => {
  ctx.save()
  // 1. Resplandor radial
  const glow = ctx.createRadialGradient(fx, fy, 0, fx, fy, 42)
  glow.addColorStop(0, 'rgba(255, 255, 255, 1)')
  glow.addColorStop(0.25, 'rgba(125, 211, 252, 0.8)')
  glow.addColorStop(0.6, 'rgba(56, 189, 248, 0.25)')
  glow.addColorStop(1, 'rgba(56, 189, 248, 0)')
  ctx.fillStyle = glow
  ctx.beginPath()
  ctx.arc(fx, fy, 42, 0, Math.PI * 2)
  ctx.fill()

  // 2. Haz horizontal
  const hGrad = ctx.createLinearGradient(fx - 46, fy, fx + 46, fy)
  hGrad.addColorStop(0, 'rgba(255, 255, 255, 0)')
  hGrad.addColorStop(0.5, 'rgba(255, 255, 255, 0.95)')
  hGrad.addColorStop(1, 'rgba(255, 255, 255, 0)')
  ctx.fillStyle = hGrad
  ctx.beginPath()
  ctx.ellipse(fx, fy, 46, 2.5, 0, 0, Math.PI * 2)
  ctx.fill()

  // 3. Haz vertical
  const vGrad = ctx.createLinearGradient(fx, fy - 46, fx, fy + 46)
  vGrad.addColorStop(0, 'rgba(255, 255, 255, 0)')
  vGrad.addColorStop(0.5, 'rgba(255, 255, 255, 0.95)')
  vGrad.addColorStop(1, 'rgba(255, 255, 255, 0)')
  ctx.fillStyle = vGrad
  ctx.beginPath()
  ctx.ellipse(fx, fy, 2.5, 46, 0, 0, Math.PI * 2)
  ctx.fill()

  // 4. Núcleo blanco
  ctx.fillStyle = '#ffffff'
  ctx.beginPath()
  ctx.arc(fx, fy, 3, 0, Math.PI * 2)
  ctx.fill()
  ctx.restore()
}

async function renderPlaqueBlob() {
  const W = 1024
  const H = 1024
  const canvas = document.createElement('canvas')
  canvas.width = W
  canvas.height = H
  const ctx = canvas.getContext('2d')

  // 1. Cargar y dibujar la Plantilla Cyber Oficial de Fondo (1024x1024)
  const templateImg = await loadImg(getCORSUrl('/flyers/combos/plaque-template-cyber.webp'))
  if (templateImg) {
    ctx.drawImage(templateImg, 0, 0, W, H)
  } else {
    // Fallback cyber background de degradado si la imagen falla
    const bg = ctx.createLinearGradient(0, 0, 0, H)
    bg.addColorStop(0, '#040814')
    bg.addColorStop(0.55, '#071530')
    bg.addColorStop(1, '#020617')
    ctx.fillStyle = bg
    ctx.fillRect(0, 0, W, H)
  }

  // 2. Visor Superior de Imagen / Producto / Combo (X: 44, Y: 40, W: 936, H: 438, R: 26)
  const visX = 44
  const visY = 40
  const visW = 936
  const visH = 438
  const visR = 26

  ctx.save()
  roundRectPath(ctx, visX, visY, visW, visH, visR)
  ctx.clip()

  ctx.fillStyle = '#060a16'
  ctx.fillRect(visX, visY, visW, visH)

  if (isMultiMode.value) {
    // Modo Multi-producto dentro del marco superior
    const items = normalizedItems.value
    const imgs = await Promise.all(items.map(it => loadImg(getCORSUrl(it.image))))
    const rowH = Math.min(94, Math.floor((visH - 24) / Math.max(1, items.length)) - 8)
    const startY = visY + 14
    items.forEach((it, idx) => {
      const y = startY + idx * (rowH + 8)
      ctx.save()
      ctx.fillStyle = 'rgba(8, 14, 30, 0.92)'
      roundRectPath(ctx, visX + 16, y, visW - 32, rowH, 16)
      ctx.fill()
      ctx.strokeStyle = 'rgba(56, 189, 248, 0.3)'
      ctx.lineWidth = 1.5
      ctx.stroke()

      const thumbSize = rowH - 16
      const tx = visX + 24
      const ty = y + 8
      ctx.save()
      roundRectPath(ctx, tx, ty, thumbSize, thumbSize, 10)
      ctx.clip()
      const im = imgs[idx]
      if (im) {
        ctx.drawImage(im, tx, ty, thumbSize, thumbSize)
      } else {
        ctx.fillStyle = '#020617'
        ctx.fillRect(tx, ty, thumbSize, thumbSize)
      }
      ctx.restore()

      ctx.textAlign = 'left'
      ctx.textBaseline = 'alphabetic'
      ctx.fillStyle = '#ffffff'
      ctx.font = '900 18px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
      ctx.fillText(it.title.length > 34 ? it.title.slice(0, 32) + '…' : it.title, tx + thumbSize + 16, y + 32)

      ctx.fillStyle = '#7dd3fc'
      ctx.font = 'bold 13px sans-serif'
      ctx.fillText(it.subtitle.slice(0, 40), tx + thumbSize + 16, y + 54)

      ctx.textAlign = 'right'
      ctx.fillStyle = '#34d399'
      ctx.font = '900 24px monospace'
      ctx.fillText(`$${it.price.toLocaleString()}`, visX + visW - 36, y + rowH / 2 + 8)
      ctx.restore()
    })
  } else {
    // Modo Producto Único
    const mainImgEl = await loadImg(getCORSUrl(mainItem.value.image))
    if (mainImgEl) {
      // Fondo difuminado de alto contraste
      ctx.save()
      ctx.filter = 'blur(24px) brightness(0.42) saturate(1.3)'
      ctx.drawImage(mainImgEl, visX - 20, visY - 20, visW + 40, visH + 40)
      ctx.restore()

      // Arte frontal
      const ar = mainImgEl.width / mainImgEl.height
      if (ar > 1.6 && ar < 2.5) {
        // Banner horizontal o flyer combo: llenar con ajuste proporcional
        const sc = Math.max(visW / mainImgEl.width, visH / mainImgEl.height)
        const dw = mainImgEl.width * sc
        const dh = mainImgEl.height * sc
        ctx.drawImage(mainImgEl, visX + (visW - dw) / 2, visY + (visH - dh) / 2, dw, dh)
      } else {
        // Cover vertical o producto cuadrado: centrar con drop-shadow
        const pad = 24
        const sc = Math.min((visW - pad * 2) / mainImgEl.width, (visH - pad * 2) / mainImgEl.height)
        const dw = mainImgEl.width * sc
        const dh = mainImgEl.height * sc
        ctx.save()
        ctx.shadowColor = 'rgba(0, 0, 0, 0.88)'
        ctx.shadowBlur = 32
        ctx.shadowOffsetY = 10
        ctx.drawImage(mainImgEl, visX + (visW - dw) / 2, visY + (visH - dh) / 2, dw, dh)
        ctx.restore()
      }
    } else if (mainItem.value.services && mainItem.value.services.length > 0) {
      // Rejilla de plataformas combo
      const srvs = mainItem.value.services.slice(0, 4)
      const cardW = Math.min(195, Math.floor((visW - 100) / srvs.length) - 14)
      const cardH = 220
      const totalRowW = srvs.length * cardW + (srvs.length - 1) * 16
      const rx0 = visX + (visW - totalRowW) / 2
      const ry0 = visY + (visH - cardH) / 2 + 10
      srvs.forEach((srv, idx) => {
        const sx = rx0 + idx * (cardW + 16)
        ctx.fillStyle = 'rgba(15, 23, 42, 0.92)'
        roundRectPath(ctx, sx, ry0, cardW, cardH, 20)
        ctx.fill()
        ctx.strokeStyle = '#38bdf8'
        ctx.lineWidth = 2
        ctx.stroke()
        ctx.fillStyle = '#ffffff'
        ctx.font = '900 21px sans-serif'
        ctx.textAlign = 'center'
        ctx.fillText(srv.platform, sx + cardW / 2, ry0 + cardH / 2 + 10)
        ctx.fillStyle = '#7dd3fc'
        ctx.font = 'bold 14px monospace'
        ctx.fillText(srv.planLabel || '1 Mes', sx + cardW / 2, ry0 + cardH - 32)
      })
    } else {
      // Showcase visual atractivo y profesional adaptado por rubro cuando el producto no posee arte directo
      const pt = effectiveProductType.value
      const pTitle = (mainItem.value.title || 'Producto StreamePro').toUpperCase()
      const pTag = (mainItem.value.tag || '').toUpperCase()

      let brandColor = '#38bdf8'
      let brandGlow = 'rgba(56, 189, 248, 0.40)'
      let categoryBadge = '🎬 STREAMING OFICIAL • 4K ULTRA HD'
      let chip1 = '📺 4K ULTRA HD • HDR10'
      let chip2 = '🔒 PIN PRIVADO EXCLUSIVO'
      let chip3 = '⚡ ACTIVACIÓN 10-15m'

      if (pt === 'gaming') {
        brandColor = '#38bdf8'
        brandGlow = 'rgba(0, 112, 209, 0.45)'
        categoryBadge = '🎮 LICENCIA DIGITAL PLAYSTATION'
        chip1 = pTag.includes('SECUNDARIA') ? '🎮 CUENTA SECUNDARIA' : '🎮 CUENTA PRIMARIA'
        chip2 = '⚡ ENTREGA 60-90 MIN'
        chip3 = '🛡️ GARANTÍA PERMANENTE'
      } else if (pt === 'package') {
        brandColor = '#f59e0b'
        brandGlow = 'rgba(245, 158, 11, 0.42)'
        categoryBadge = '🔥 SUPER COMBO OFICIAL • MÁXIMO AHORRO'
        chip1 = '🍿 MULTI-PLATAFORMA'
        chip2 = '🔒 PERFILES CON PIN'
        chip3 = '⚡ ACTIVACIÓN INMEDIATA'
      } else if (pt === 'physical') {
        brandColor = '#10b981'
        brandGlow = 'rgba(16, 185, 129, 0.40)'
        categoryBadge = '📦 PRODUCTO FÍSICO • TIENDA OFICIAL'
        chip1 = '🚚 ENVÍOS A TODO EL PAÍS'
        chip2 = '📦 STOCK INMEDIATO'
        chip3 = '🛡️ FACTURA & GARANTÍA'
      } else {
        const lower = pTitle.toLowerCase()
        if (lower.includes('disney')) {
          brandColor = '#38bdf8'
          brandGlow = 'rgba(2, 132, 199, 0.45)'
        } else if (lower.includes('max') || lower.includes('hbo')) {
          brandColor = '#a855f7'
          brandGlow = 'rgba(147, 51, 234, 0.45)'
        } else if (lower.includes('spotify')) {
          brandColor = '#10b981'
          brandGlow = 'rgba(16, 185, 129, 0.45)'
        } else if (lower.includes('prime')) {
          brandColor = '#06b6d4'
          brandGlow = 'rgba(6, 182, 212, 0.45)'
        } else if (lower.includes('iptv')) {
          brandColor = '#f59e0b'
          brandGlow = 'rgba(245, 158, 11, 0.45)'
        } else if (lower.includes('netflix')) {
          brandColor = '#e11d48'
          brandGlow = 'rgba(225, 29, 72, 0.45)'
        }
      }

      // Resplandor radial de ambientación
      const radGrad = ctx.createRadialGradient(visX + visW / 2, visY + visH / 2, 20, visX + visW / 2, visY + visH / 2, 420)
      radGrad.addColorStop(0, brandGlow)
      radGrad.addColorStop(1, 'rgba(4, 8, 20, 0.96)')
      ctx.fillStyle = radGrad
      ctx.fillRect(visX, visY, visW, visH)

      // Tarjeta Central Flotante de Alto Impacto
      const cw = Math.min(680, visW - 140)
      const ch = 220
      const cx = visX + (visW - cw) / 2
      const cy = visY + (visH - ch) / 2 + 14

      ctx.save()
      ctx.shadowColor = brandGlow
      ctx.shadowBlur = 28
      const cardGrad = ctx.createLinearGradient(cx, cy, cx + cw, cy + ch)
      cardGrad.addColorStop(0, 'rgba(6, 14, 34, 0.95)')
      cardGrad.addColorStop(1, 'rgba(10, 22, 48, 0.90)')
      ctx.fillStyle = cardGrad
      roundRectPath(ctx, cx, cy, cw, ch, 24)
      ctx.fill()
      ctx.strokeStyle = brandColor
      ctx.lineWidth = 2
      ctx.stroke()
      ctx.shadowBlur = 0

      // Etiqueta superior de la tarjeta
      ctx.font = '900 13px monospace'
      ctx.fillStyle = brandColor
      ctx.textAlign = 'center'
      ctx.textBaseline = 'middle'
      ctx.fillText(categoryBadge, cx + cw / 2, cy + 38)

      // Título destacado central
      ctx.fillStyle = '#ffffff'
      let tSize = 40
      ctx.font = `900 ${tSize}px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif`
      while (ctx.measureText(pTitle).width > cw - 60 && tSize > 22) {
        tSize -= 2
        ctx.font = `900 ${tSize}px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif`
      }
      ctx.fillText(pTitle, cx + cw / 2, cy + 96)

      // 3 Chips de Beneficios Clave
      const chips = [chip1, chip2, chip3]
      const chipW = Math.floor((cw - 44) / 3)
      chips.forEach((chTxt, cIdx) => {
        const chX = cx + 16 + cIdx * (chipW + 6)
        const chY = cy + ch - 54
        ctx.fillStyle = 'rgba(2, 6, 23, 0.85)'
        roundRectPath(ctx, chX, chY, chipW, 34, 12)
        ctx.fill()
        ctx.strokeStyle = 'rgba(255, 255, 255, 0.15)'
        ctx.lineWidth = 1
        ctx.stroke()
        ctx.fillStyle = '#e2e8f0'
        ctx.font = 'bold 11px monospace'
        ctx.fillText(chTxt, chX + chipW / 2, chY + 17)
      })
      ctx.restore()
    }

    // Logo Crest Superpuesto en Esquina Superior Izquierda
    drawLogoOverlayCrest(ctx, visX + 20, visY + 22)

    // Badges en Esquina Superior Derecha
    ctx.save()
    const qtxt = qualityTagText.value
    ctx.font = '900 14px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
    const qtw = Math.max(160, ctx.measureText(qtxt).width + 36)
    const qtx = visX + visW - 20 - qtw
    const qty = visY + 22
    const tagGrad = ctx.createLinearGradient(qtx, qty, qtx + qtw, qty)
    tagGrad.addColorStop(0, '#06b6d4')
    tagGrad.addColorStop(1, '#0284c7')
    ctx.fillStyle = tagGrad
    roundRectPath(ctx, qtx, qty, qtw, 40, 20)
    ctx.fill()
    ctx.fillStyle = '#ffffff'
    ctx.textAlign = 'center'
    ctx.textBaseline = 'middle'
    ctx.fillText(qtxt, qtx + qtw / 2, qty + 20)

    // Píldora Inferior de Entrega / Calidad
    const dtxt = deliveryPillText.value
    ctx.font = 'bold 12px monospace'
    const dtw = Math.max(140, ctx.measureText(dtxt).width + 24)
    const dtx = visX + visW - 20 - dtw
    const dty = qty + 48
    ctx.fillStyle = '#064e3b'
    roundRectPath(ctx, dtx, dty, dtw, 30, 15)
    ctx.fill()
    ctx.lineWidth = 1.5
    ctx.strokeStyle = '#34d399'
    ctx.stroke()
    ctx.fillStyle = '#34d399'
    ctx.fillText(dtxt, dtx + dtw / 2, dty + 15)
    ctx.restore()
  }

  ctx.restore() // Fin clip visor superior

  // 3. TÍTULO DEL PRODUCTO (Centrado en X: 512, Y: 538)
  ctx.save()
  ctx.textAlign = 'center'
  ctx.textBaseline = 'middle'
  ctx.fillStyle = '#ffffff'
  ctx.shadowColor = 'rgba(0, 0, 0, 0.85)'
  ctx.shadowBlur = 12
  const titleStr = plaqueHeadline.value
  let titleFont = 36
  ctx.font = `900 ${titleFont}px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif`
  while (ctx.measureText(titleStr).width > 880 && titleFont > 24) {
    titleFont -= 2
    ctx.font = `900 ${titleFont}px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif`
  }
  ctx.fillText(titleStr, 512, 538)
  ctx.restore()

  // 4. SUBTÍTULO / ESPECIFICACIONES DENTRO DE LA PÍLDORA AZUL (Centrado en X: 512, Y: 605)
  ctx.save()
  ctx.textAlign = 'center'
  ctx.textBaseline = 'middle'
  ctx.fillStyle = '#e0f2fe'
  let subFont = 16
  ctx.font = `bold ${subFont}px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif`
  const subStr = plaqueSubline.value
  while (ctx.measureText(subStr).width > 840 && subFont > 11) {
    subFont -= 1
    ctx.font = `bold ${subFont}px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif`
  }
  ctx.fillText(subStr, 512, 605)
  ctx.restore()

  // 5. BLOQUE DE PRECIOS
  const hasOrig = mainItem.value.originalPrice > mainItem.value.price && mainItem.value.price > 0
  if (hasOrig) {
    // Línea de antes tachado (Y: 664)
    ctx.save()
    ctx.textAlign = 'center'
    ctx.textBaseline = 'middle'
    ctx.fillStyle = '#94a3b8'
    ctx.font = 'bold 17px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
    const origText = `Antes: $${mainItem.value.originalPrice.toLocaleString()} ARS`
    ctx.fillText(origText, 512, 664)
    const origW = ctx.measureText(origText).width
    ctx.strokeStyle = '#94a3b8'
    ctx.lineWidth = 1.5
    ctx.beginPath()
    ctx.moveTo(512 - origW / 2 - 2, 664)
    ctx.lineTo(512 + origW / 2 + 2, 664)
    ctx.stroke()
    ctx.restore()
  }

  // Precio Principal Gigante con Glow (Centrado en X: 512, Y: 716)
  const priceText = mainItem.value.price > 0 ? `$${mainItem.value.price.toLocaleString()} ARS` : 'CONSULTAR PRECIO'
  ctx.save()
  ctx.textAlign = 'center'
  ctx.textBaseline = 'middle'
  ctx.fillStyle = '#ffffff'
  ctx.font = '900 64px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
  ctx.shadowColor = 'rgba(56, 189, 248, 0.45)'
  ctx.shadowBlur = 24
  const pw = ctx.measureText(priceText).width
  ctx.fillText(priceText, 512, 716)
  ctx.restore()

  // Destello Especular de Estrella sobre el Precio
  const flareX = 512 + pw / 2 - 12
  const flareY = 698
  drawStarFlare(ctx, flareX, flareY)

  // Sello de Garantía y Confianza (Centrado en X: 512, Y: 757)
  ctx.save()
  ctx.textAlign = 'center'
  ctx.textBaseline = 'middle'
  ctx.fillStyle = '#34d399'
  ctx.font = 'bold 12px monospace'
  ctx.fillText(trustGuaranteeText.value, 512, 757)
  ctx.restore()

  // 6. CUPÓN DE DESCUENTO PROMOCIONAL (Si aplica) (Y: 798)
  if (activePromoCode.value) {
    ctx.save()
    const promoStr = `🎟️ CUPÓN ${activePromoCode.value} · ${activePromoLabel.value}`
    ctx.font = '900 15px monospace'
    const pbw = Math.min(700, ctx.measureText(promoStr).width + 36)
    const pbx = 512 - pbw / 2
    const pby = 786
    const pbH = 28
    const pGrad = ctx.createLinearGradient(pbx, pby, pbx + pbw, pby + pbH)
    pGrad.addColorStop(0, '#f59e0b')
    pGrad.addColorStop(1, '#ea580c')
    ctx.fillStyle = pGrad
    roundRectPath(ctx, pbx, pby, pbw, pbH, pbH / 2)
    ctx.fill()
    ctx.fillStyle = '#020617'
    ctx.textAlign = 'center'
    ctx.textBaseline = 'middle'
    ctx.fillText(promoStr, 512, pby + pbH / 2)
    ctx.restore()
  }

  // 7. FOOTER: PÍLDORA SUPERIOR WHATSAPP (Centrado en X: 512, Y: 866)
  ctx.save()
  ctx.textAlign = 'center'
  ctx.textBaseline = 'middle'
  ctx.fillStyle = '#ffffff'
  ctx.font = '900 21px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
  ctx.shadowColor = 'rgba(0, 0, 0, 0.6)'
  ctx.shadowBlur = 6
  ctx.fillText('PEDIDOS & ATENCIÓN DIRECTA POR WHATSAPP', 512, 866)
  ctx.restore()

  // 8. FOOTER: PÍLDORA INFERIOR WHATSAPP (Center Y: 933)
  const phoneToDisplay = (effectiveDistributorPhone.value || props.contactPhone || '+54 9 291 565-8321').trim()

  // Icono Circular WhatsApp en X: 118, Y: 933, R: 22
  ctx.save()
  ctx.beginPath()
  ctx.arc(118, 933, 22, 0, Math.PI * 2)
  ctx.fillStyle = '#25d366'
  ctx.fill()
  ctx.lineWidth = 2
  ctx.strokeStyle = '#86efac'
  ctx.stroke()
  ctx.fillStyle = '#ffffff'
  ctx.textAlign = 'center'
  ctx.textBaseline = 'middle'
  ctx.font = '22px sans-serif'
  ctx.fillText('💬', 118, 934)
  ctx.restore()

  // Número de WhatsApp en X: 160, Y: 933
  ctx.save()
  ctx.textAlign = 'left'
  ctx.textBaseline = 'middle'
  ctx.fillStyle = '#ffffff'
  ctx.font = '900 44px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, monospace'
  ctx.shadowColor = 'rgba(0, 0, 0, 0.6)'
  ctx.shadowBlur = 8
  ctx.fillText(phoneToDisplay, 160, 933)

  // Badge derecho "⚡ 24/7 ACTIVO" en X: 890, Y: 933
  ctx.textAlign = 'right'
  ctx.fillStyle = '#ecfdf5'
  ctx.font = '900 14px monospace'
  ctx.fillText('⚡ 24/7 ACTIVO', 890, 933)
  ctx.restore()

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
    toast.add('📋 ¡Texto promocional atractivo copiado para WhatsApp!', 'success')
  } catch {
    toast.add(attractiveCopy.value, 'info')
  }
}

const openWhatsAppWithAttractiveText = () => {
  const url = `https://api.whatsapp.com/send?text=${encodeURIComponent(attractiveCopy.value)}`
  window.open(url, '_blank')
}

const handleOpenInStudio = () => {
  const p = props.payload || {}
  emit('openInStudio', {
    itemType: p.productType || (p.categoryIds ? 'physical' : 'streaming'),
    id: p.id || '',
    gameId: p.id || '',
    title: plaqueHeadline.value,
    subtitle: plaqueSubline.value,
    price: mainItem.value.price,
    originalPrice: mainItem.value.originalPrice,
    image: mainItem.value.image,
    platform: p.platform || mainItem.value.tag || '',
    license: p.licenseType || 'primary',
    services: mainItem.value.services
  })
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
    const slug = plaqueHeadline.value.toLowerCase().replace(/[^a-z0-9]/g, '_').slice(0, 28) || 'placa'
    a.download = `placa_${slug}_streamepro.png`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    setTimeout(() => {
      try { URL.revokeObjectURL(url) } catch (_) {}
    }, 60000)
    toast.add('🖼️ ¡Placa para compartir descargada en HD!', 'success')
  } catch (e) {
    toast.add('No se pudo generar la placa en este dispositivo.', 'error')
  } finally {
    isExporting.value = false
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

const sharePlaqueDirect = async () => {
  if (isExporting.value) return
  isExporting.value = true
  const shareText = attractiveCopy.value

  try {
    const blob = await renderPlaqueBlob()
    if (blob && typeof navigator !== 'undefined' && navigator.share && navigator.canShare) {
      const file = new File([blob], 'streamepro_compartir.png', { type: 'image/png' })
      if (navigator.canShare({ files: [file] })) {
        await navigator.share({
          title: plaqueHeadline.value,
          text: shareText,
          files: [file]
        })
        toast.add('✅ ¡Placa compartida con éxito!', 'success')
        return
      }
    }
    // Fallback escritorio: descargar placa y abrir WhatsApp con mensaje prearmado
    if (blob) {
      const url = URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = `placa_${plaqueHeadline.value.toLowerCase().replace(/[^a-z0-9]/g, '_').slice(0, 24) || 'oferta'}.png`
      document.body.appendChild(a)
      a.click()
      document.body.removeChild(a)
      setTimeout(() => {
        try { URL.revokeObjectURL(url) } catch (_) {}
      }, 60000)
    }
    window.open(`https://api.whatsapp.com/send?text=${encodeURIComponent(shareText)}`, '_blank')
    toast.add('📲 Placa descargada y WhatsApp abierto con el texto promocional listo', 'success')
  } catch (err) {
    if (err?.name !== 'AbortError') {
      window.open(`https://api.whatsapp.com/send?text=${encodeURIComponent(shareText)}`, '_blank')
    }
  } finally {
    isExporting.value = false
  }
}

// Compartir fluido en todas las plataformas y redes
const shareToPlatform = async (platform) => {
  const url = directShareUrl.value
  const title = plaqueHeadline.value
  const copy = attractiveCopy.value

  if (platform === 'whatsapp') {
    window.open(`https://api.whatsapp.com/send?text=${encodeURIComponent(copy)}`, '_blank')
    toast.add('💬 Abriendo WhatsApp...', 'info')
    return
  }

  if (platform === 'telegram') {
    window.open(`https://t.me/share/url?url=${encodeURIComponent(url)}&text=${encodeURIComponent(title + '\n' + plaqueSubline.value)}`, '_blank')
    toast.add('✈️ Abriendo Telegram...', 'info')
    return
  }

  if (platform === 'facebook') {
    window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`, '_blank')
    toast.add('📘 Abriendo Facebook...', 'info')
    return
  }

  if (platform === 'twitter') {
    const tweetText = `${title} · ${plaqueSubline.value}\nEncontralo en StreamePro:`
    window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent(tweetText)}&url=${encodeURIComponent(url)}`, '_blank')
    toast.add('✖️ Abriendo X (Twitter)...', 'info')
    return
  }

  if (platform === 'instagram') {
    // Si el navegador soporta compartir archivos directamente a apps (móvil)
    if (typeof navigator !== 'undefined' && navigator.share && navigator.canShare) {
      try {
        isExporting.value = true
        const blob = await renderPlaqueBlob()
        if (blob) {
          const file = new File([blob], 'streamepro_instagram.png', { type: 'image/png' })
          if (navigator.canShare({ files: [file] })) {
            await navigator.share({
              title,
              text: copy,
              files: [file]
            })
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
    // Fallback escritorio / web: Descarga la imagen y copia el texto al portapapeles
    await downloadPlaquePng()
    try {
      await navigator.clipboard.writeText(copy)
      toast.add('📸 ¡Placa descargada y texto copiado para pegar en tu historia de Instagram!', 'success')
    } catch {
      toast.add('📸 ¡Placa descargada para tu Instagram!', 'success')
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
  <div v-if="show && payload" class="fixed inset-0 z-[85] flex items-center justify-center p-3 sm:p-5 bg-black/85 backdrop-blur-md animate-in fade-in duration-200 overflow-y-auto">
    <div class="bg-slate-900 border border-slate-700/80 w-full max-w-xl rounded-3xl shadow-2xl overflow-hidden text-white flex flex-col my-auto max-h-[95vh]">
      
      <!-- Encabezado Simple con Selector de Estilos Rápidos -->
      <div class="px-5 py-3.5 border-b border-slate-800 flex items-center justify-between bg-slate-950/80 shrink-0">
        <div class="flex items-center gap-2.5 min-w-0">
          <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-sky-400 to-blue-600 flex items-center justify-center font-black text-xs text-white shadow shrink-0">
            SP
          </div>
          <div class="min-w-0">
            <h3 class="text-sm sm:text-base font-black text-white truncate">Placa Rápida para Compartir</h3>
            <p class="text-[11px] text-slate-400 truncate">Diseño 1:1 de alto impacto con tu WhatsApp exclusivo</p>
          </div>
        </div>
        <button @click="emit('close')" type="button" class="w-9 h-9 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700 font-bold text-sm flex items-center justify-center cursor-pointer shrink-0">
          ✕
        </button>
      </div>

      <!-- Contenido Scrolleable -->
      <div class="p-4 sm:p-5 overflow-y-auto space-y-4 bg-[#020617] flex-1">
        
        <!-- SELECTOR DE ESTILOS VISUALES RÁPIDOS -->
        <div class="flex items-center justify-between gap-2 bg-slate-950/80 p-2.5 rounded-2xl border border-slate-800">
          <span class="text-[11px] font-black uppercase text-slate-400 tracking-wider shrink-0 flex items-center gap-1">
            <span>🎨</span> Estilo:
          </span>
          <div class="flex items-center gap-1.5 overflow-x-auto custom-scrollbar py-0.5">
            <button
              v-for="th in Object.values(PLAQUE_THEMES)"
              :key="th.id"
              type="button"
              @click="plaqueThemeKey = th.id"
              :class="[
                'px-2.5 py-1 rounded-xl text-xs font-bold transition-all shrink-0 cursor-pointer',
                plaqueThemeKey === th.id
                  ? 'bg-gradient-to-r from-sky-500 to-blue-600 text-white shadow-md scale-105 border border-white/30'
                  : 'bg-slate-900 text-slate-400 hover:text-white border border-slate-800'
              ]"
            >
              {{ th.label }}
            </button>
          </div>
        </div>

        <!-- Vista Previa de la Placa 1:1 con Plantilla Cyber Oficial -->
        <div class="flex justify-center">
          <div
            class="w-full max-w-[340px] aspect-square rounded-3xl overflow-hidden relative shadow-2xl border border-white/10 select-none bg-slate-950"
            style="background-image: url('/flyers/combos/plaque-template-cyber.webp'); background-size: 100% 100%; background-repeat: no-repeat;"
          >
            <!-- 1. Marco Superior de Imagen / Flyer / Combo (Y: 3.9%..46.7%, X: 4.3%..95.7%) -->
            <div class="absolute top-[3.9%] left-[4.3%] w-[91.4%] h-[42.8%] rounded-[14px] overflow-hidden bg-slate-950/80 flex items-center justify-center">
              <!-- Si es un producto individual con imagen -->
              <template v-if="!isMultiMode && mainItem.image">
                <img
                  :src="mainItem.image"
                  :alt="mainItem.title"
                  class="absolute inset-0 w-full h-full object-cover filter blur-md scale-110 brightness-40 saturate-125 pointer-events-none"
                />
                <img
                  :src="mainItem.image"
                  :alt="mainItem.title"
                  class="relative z-10 max-w-full max-h-full object-contain p-1.5 drop-shadow-[0_10px_20px_rgba(0,0,0,0.9)]"
                />
              </template>

              <!-- Si es combo con servicios -->
              <div v-else-if="!isMultiMode && mainItem.services?.length" class="relative z-10 flex flex-wrap items-center justify-center gap-1.5 p-2">
                <div v-for="(srv, idx) in mainItem.services.slice(0, 4)" :key="idx" class="bg-slate-900/90 border border-sky-400/40 rounded-lg px-2 py-1 text-center shadow">
                  <span class="text-[10px] font-black text-white block">{{ srv.platform }}</span>
                  <span class="text-[8px] text-sky-300 font-mono">{{ srv.planLabel }}</span>
                </div>
              </div>

              <!-- Si es modo multi-selección -->
              <div v-else-if="isMultiMode" class="relative z-10 w-full h-full p-2 flex flex-col justify-center space-y-1">
                <div v-for="(it, idx) in normalizedItems.slice(0, 4)" :key="idx" class="flex items-center gap-2 bg-slate-900/80 border border-sky-400/20 rounded-lg px-2 py-0.5">
                  <img v-if="it.image" :src="it.image" :alt="it.title" class="w-6 h-6 rounded object-cover bg-black shrink-0" />
                  <span class="text-[9px] font-black text-white truncate flex-1">{{ it.title }}</span>
                  <span class="text-[9px] font-mono font-black text-emerald-300 shrink-0">${{ it.price.toLocaleString() }}</span>
                </div>
              </div>

              <!-- Fallback profesional cuando no hay imagen disponible -->
              <div v-else class="relative z-10 w-full h-full flex items-center justify-center p-3">
                <div class="w-full max-w-[280px] bg-slate-950/90 border border-sky-400/50 rounded-2xl p-3 shadow-xl text-center space-y-1.5 backdrop-blur-md">
                  <span class="text-[8px] font-mono font-black text-sky-400 uppercase tracking-wider block">
                    {{ effectiveProductType === 'gaming' ? '🎮 LICENCIA PLAYSTATION DIGITAL' : effectiveProductType === 'package' ? '🔥 SUPER COMBO OFICIAL' : effectiveProductType === 'physical' ? '📦 PRODUCTO FÍSICO OFICIAL' : '🎬 STREAMING OFICIAL 4K' }}
                  </span>
                  <h5 class="text-xs sm:text-sm font-black text-white leading-tight truncate px-1">{{ mainItem.title }}</h5>
                  <div class="flex items-center justify-center gap-1 pt-0.5">
                    <span class="text-[7px] font-mono font-bold bg-slate-900 border border-white/10 px-1.5 py-0.5 rounded text-slate-300">
                      {{ effectiveProductType === 'gaming' ? '🏆 Trofeos' : '📺 4K UHD' }}
                    </span>
                    <span class="text-[7px] font-mono font-bold bg-slate-900 border border-white/10 px-1.5 py-0.5 rounded text-slate-300">
                      {{ effectiveProductType === 'gaming' ? '⚡ 60-90m' : '🔒 PIN Privado' }}
                    </span>
                    <span class="text-[7px] font-mono font-bold bg-slate-900 border border-white/10 px-1.5 py-0.5 rounded text-emerald-400">
                      🛡️ Garantía
                    </span>
                  </div>
                </div>
              </div>

              <!-- Crest Logo en Esquina Superior Izquierda (sobre la imagen) -->
              <div class="absolute top-2 left-2 z-20 flex items-center gap-1.5 bg-slate-950/90 backdrop-blur-md border border-cyan-400/60 rounded-full pl-1 pr-2.5 py-0.5 shadow-lg">
                <div class="w-5 h-5 rounded-full bg-gradient-to-br from-sky-400 to-blue-600 flex items-center justify-center text-[8px] font-black text-white shrink-0 border border-white/40">
                  SP
                </div>
                <div class="leading-none min-w-0">
                  <span class="text-[8px] font-black text-white tracking-wide uppercase block max-w-[100px] truncate">{{ storeName }}</span>
                  <span class="text-[6px] font-mono font-bold text-cyan-300 block">✓ TIENDA OFICIAL</span>
                </div>
              </div>

              <!-- Badges en Esquina Superior Derecha -->
              <div class="absolute top-2 right-2 z-20 flex flex-col items-end gap-1">
                <span class="text-[8px] font-black px-2 py-0.5 rounded-full bg-gradient-to-r from-cyan-500 to-blue-600 text-white shadow uppercase font-mono">
                  {{ qualityTagText }}
                </span>
                <span class="text-[7px] font-bold px-1.5 py-0.5 rounded-full bg-emerald-950/90 text-emerald-300 border border-emerald-500/50 font-mono">
                  {{ deliveryPillText }}
                </span>
              </div>
            </div>

            <!-- 2. TÍTULO DEL PRODUCTO (Y: ~51%) -->
            <div class="absolute top-[50.8%] left-[4%] w-[92%] text-center">
              <h4 class="text-xs sm:text-sm font-black text-white leading-tight truncate px-2 drop-shadow">{{ plaqueHeadline }}</h4>
            </div>

            <!-- 3. SUBTÍTULO DENTRO DE LA PÍLDORA AZUL (Y: ~57.5%) -->
            <div class="absolute top-[57.2%] left-[6%] w-[88%] h-[5.8%] flex items-center justify-center px-2">
              <p class="text-[8.5px] sm:text-[9.5px] font-bold text-sky-100 truncate text-center leading-none">{{ plaqueSubline }}</p>
            </div>

            <!-- 4. BLOQUE DE PRECIO (Y: ~64.5%) -->
            <div class="absolute top-[64.2%] left-[4%] w-[92%] flex flex-col items-center justify-center text-center">
              <span v-if="mainItem.originalPrice > mainItem.price && mainItem.price > 0" class="text-[8.5px] text-slate-400 line-through font-mono leading-none mb-0.5">
                Antes: ${{ mainItem.originalPrice.toLocaleString() }} ARS
              </span>
              <div class="relative inline-block leading-none">
                <span class="text-xl sm:text-2xl font-black font-mono text-white tracking-tight drop-shadow-[0_0_12px_rgba(56,189,248,0.5)]">
                  {{ mainItem.price > 0 ? `$${mainItem.price.toLocaleString()} ARS` : 'CONSULTAR' }}
                </span>
                <span class="absolute -top-1 -right-3 text-cyan-300 text-xs">✨</span>
              </div>
              <span class="text-[7px] sm:text-[7.5px] text-emerald-400 font-mono font-bold tracking-wide uppercase mt-1 leading-none">
                {{ trustGuaranteeText }}
              </span>
            </div>

            <!-- 5. CUPÓN DE DESCUENTO (Si existe) (Y: ~77.5%) -->
            <div v-if="activePromoCode" class="absolute top-[77.5%] left-[10%] w-[80%] bg-gradient-to-r from-amber-500 to-orange-500 text-slate-950 font-mono font-black text-[8px] py-0.5 px-2 rounded-full text-center shadow truncate">
              🎟️ CUPÓN {{ activePromoCode }} · {{ activePromoLabel }}
            </div>

            <!-- 6. PÍLDORA SUPERIOR WHATSAPP (Y: ~83.2%) -->
            <div class="absolute top-[83.2%] left-[12%] w-[76%] h-[5%] flex items-center justify-center">
              <span class="text-[8.5px] sm:text-[9px] font-black text-white tracking-wider uppercase leading-none">
                PEDIDOS &amp; ATENCIÓN DIRECTA POR WHATSAPP
              </span>
            </div>

            <!-- 7. PÍLDORA INFERIOR WHATSAPP (Y: ~88.6%) -->
            <div class="absolute top-[88.6%] left-[7.5%] w-[85%] h-[8.5%] flex items-center justify-between px-2.5">
              <div class="w-6 h-6 rounded-full bg-emerald-500 border border-emerald-300 flex items-center justify-center text-[11px] shadow shrink-0">
                💬
              </div>
              <span class="text-xs sm:text-sm font-black font-mono text-white tracking-wider truncate mx-2">
                {{ effectiveDistributorPhone || '+54 9 291 565-8321' }}
              </span>
              <span class="text-[7.5px] font-black font-mono text-emerald-200 uppercase bg-emerald-950/70 px-1.5 py-0.5 rounded border border-emerald-400/40 shrink-0">
                ⚡ 24/7 ACTIVO
              </span>
            </div>
          </div>
        </div>

        <!-- NÚMERO DE WHATSAPP DEL DISTRIBUIDOR (EXCLUSIVO EN LA PLACA - SOLO DISTRIBUIDORES) -->
        <div v-if="isReseller" class="bg-slate-950/80 rounded-2xl border border-emerald-500/30 p-3 space-y-1.5 shadow-lg">
          <div class="flex items-center justify-between">
            <label class="text-[11px] font-black uppercase text-emerald-300 flex items-center gap-1.5">
              <span>📲</span> WhatsApp de Distribuidor (Único Contacto en Placa)
            </label>
            <span class="text-[9px] text-slate-400 font-mono">Solo este número saldrá en la imagen</span>
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

        <!-- CAJA DE TEXTO ATRACTIVO AUTO-GENERADO PARA REDES (SOLO DISTRIBUIDORES) -->
        <div v-if="isReseller" class="bg-slate-950/80 rounded-2xl border border-slate-800 p-3.5 space-y-2">
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-black text-amber-400 flex items-center gap-1.5 uppercase tracking-wide">
              <span>💬</span>
              <span>Texto Atractivo para WhatsApp &amp; Redes</span>
            </span>
            <div class="flex items-center gap-1.5">
              <button
                type="button"
                @click="copyAttractiveText"
                class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-[10px] font-bold border border-slate-700 flex items-center gap-1 transition active:scale-95 cursor-pointer"
                title="Copiar texto al portapapeles"
              >
                <span>📋 Copiar Texto</span>
              </button>
              <button
                type="button"
                @click="openWhatsAppWithAttractiveText"
                class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-[10px] font-bold flex items-center gap-1 transition active:scale-95 cursor-pointer shadow"
                title="Abrir WhatsApp directo con este texto"
              >
                <span>💬 WhatsApp</span>
              </button>
            </div>
          </div>
          <div class="bg-slate-900/90 rounded-xl p-2.5 border border-white/5 text-[11px] font-mono text-slate-300 whitespace-pre-wrap leading-relaxed max-h-36 overflow-y-auto select-all">
            {{ attractiveCopy }}
          </div>
        </div>

        <!-- COMPARTIR EN TODAS LAS PLATAFORMAS -->
        <div class="bg-slate-950/90 rounded-2xl border border-slate-800/80 p-3.5 space-y-2.5 shadow-lg">
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-black text-slate-200 uppercase tracking-wide flex items-center gap-1.5">
              <span class="text-amber-400">🚀</span> Compartir en Todas las Plataformas
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

            <!-- Twitter / X -->
            <button
              type="button"
              @click="shareToPlatform('twitter')"
              class="p-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-white font-bold text-xs flex items-center justify-center gap-1.5 transition active:scale-95 cursor-pointer shadow"
              title="Compartir en X (Twitter)"
            >
              <span>✖️</span>
              <span>X / Twitter</span>
            </button>
          </div>

          <div class="grid grid-cols-2 gap-2 pt-0.5">
            <!-- Instagram (Descarga HD + Copia texto o Share Nativo) -->
            <button
              type="button"
              @click="shareToPlatform('instagram')"
              class="p-2.5 rounded-xl bg-gradient-to-r from-purple-600/25 via-pink-600/25 to-amber-500/25 hover:from-purple-600/35 hover:via-pink-600/35 hover:to-amber-500/35 border border-pink-500/40 text-pink-200 font-bold text-xs flex items-center justify-center gap-1.5 transition active:scale-95 cursor-pointer shadow"
              title="Descargar y preparar para publicar en Instagram"
            >
              <span>📸</span>
              <span>Instagram (Historia/Post)</span>
            </button>

            <!-- Compartir Nativo Móvil / Sistema -->
            <button
              type="button"
              @click="shareToPlatform('native')"
              :disabled="isExporting"
              class="p-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-slate-950 font-black text-xs flex items-center justify-center gap-1.5 transition active:scale-95 cursor-pointer shadow disabled:opacity-50"
              title="Abrir menú nativo de compartir del teléfono o navegador"
            >
              <span>📲</span>
              <span>{{ isExporting ? 'Generando...' : 'Más Opciones' }}</span>
            </button>
          </div>
        </div>

        <!-- BOTONERA DE ACCIÓN Y DESCARGA -->
        <div class="space-y-2 pt-1">
          <div class="grid grid-cols-2 gap-2">
            <button
              type="button"
              @click="downloadPlaquePng"
              :disabled="isExporting"
              class="bg-gradient-to-r from-sky-500 to-indigo-600 hover:from-sky-400 hover:to-indigo-500 text-white font-black text-xs py-3 px-3 rounded-xl shadow-lg flex items-center justify-center gap-1.5 cursor-pointer transition active:scale-95 disabled:opacity-50"
            >
              <span>⬇️</span>
              <span>{{ isExporting ? 'Generando...' : 'Descargar Placa PNG' }}</span>
            </button>
            <button
              type="button"
              @click="copyPlaqueLink"
              class="bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs py-3 px-3 rounded-xl border border-slate-700 flex items-center justify-center gap-1.5 cursor-pointer transition active:scale-95"
            >
              <span>🔗</span>
              <span>Copiar Enlace Directo</span>
            </button>
          </div>

          <!-- BOTÓN ESTUDIO: SOLO VISIBLE PARA DISTRIBUIDORES / RESELLERS -->
          <div v-if="isReseller" class="pt-0.5">
            <button
              type="button"
              @click="handleOpenInStudio"
              class="w-full bg-gradient-to-r from-purple-600 via-pink-600 to-indigo-600 hover:from-purple-500 hover:via-pink-500 hover:to-indigo-500 text-white font-bold text-xs py-2.5 px-3 rounded-xl flex items-center justify-center gap-1.5 cursor-pointer transition shadow"
            >
              <span>🎨</span>
              <span>Abrir en Estudio (Flyer / Reel Avanzado)</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

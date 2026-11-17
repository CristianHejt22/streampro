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
  return (localContactPhone.value || props.contactPhone || '').trim()
})

const activePromoCode = computed(() => String(props.payload?.promoCode || props.promoCode || '').trim().toUpperCase())
const activePromoLabel = computed(() => String(props.payload?.promoLabel || props.promoLabel || 'Descuento Oficial').trim())

const isMultiMode = computed(() => props.payload?.mode === 'multi' && Array.isArray(props.payload?.items) && props.payload.items.length > 0)

// Selector de Estilos Visuales para Placas Rápidas
const plaqueThemeKey = ref('cyber')

// Auto-selección inteligente de tema según el producto
watch(() => props.payload, (p) => {
  if (p) {
    const titleStr = (p.title || p.name || p.platform || '').toLowerCase()
    if (titleStr.includes('netflix')) {
      plaqueThemeKey.value = 'ruby'
    } else if (p.productType === 'physical') {
      plaqueThemeKey.value = 'gold'
    } else if (p.productType === 'package') {
      plaqueThemeKey.value = 'ruby'
    } else {
      plaqueThemeKey.value = 'cyber'
    }
  }
}, { immediate: true })

const PLAQUE_THEMES = {
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
  },
  ruby: {
    id: 'ruby',
    label: '🔴 Ruby Netflix',
    glowRgba: 'rgba(239, 68, 68, 0.40)',
    borderHex: '#ef4444',
    accentHex: '#fca5a5',
    badgeGrad: ['#dc2626', '#b91c1c'],
    bgGrad: ['#140404', '#22080a', '#0a0204'],
    cssPill: 'bg-red-500/20 text-red-300 border-red-400/50',
    cssPrice: 'text-red-300',
    cssBorder: 'border-red-500/40'
  },
  gold: {
    id: 'gold',
    label: '👑 Gold VIP',
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
    label: '💬 Emerald Pro',
    glowRgba: 'rgba(16, 185, 129, 0.38)',
    borderHex: '#10b981',
    accentHex: '#a7f3d0',
    badgeGrad: ['#059669', '#047857'],
    bgGrad: ['#02120d', '#052117', '#010c08'],
    cssPill: 'bg-emerald-500/20 text-emerald-300 border-emerald-400/50',
    cssPrice: 'text-emerald-300',
    cssBorder: 'border-emerald-500/40'
  }
}

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
    image: p.image || '',
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

const isNetflixItem = computed(() => {
  const t = (mainItem.value.title || '').toLowerCase()
  const tag = (mainItem.value.tag || '').toLowerCase()
  return t.includes('netflix') || tag.includes('netflix')
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
  if (isNetflixItem.value) {
    return '4K ULTRA HD • HDR10 · 🟢 10-15m · PIN Privado'
  }
  if (mainItem.value.subtitle) return mainItem.value.subtitle
  const pt = props.payload?.productType || 'gaming'
  if (pt === 'physical') return '🚚 Stock Inmediato · Envíos a Todo el País · Garantía Oficial'
  if (pt === 'package') return '🎁 Combo Promocional · Perfiles Privados con PIN'
  if (pt === 'streaming') return '🎬 Perfil Privado 4K · Entrega 10-15m · Garantía 30d'
  return '🎮 Licencia Digital Original · Entrega 60/90m · Garantía Oficial'
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
  const origin = (typeof window !== 'undefined' && (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1'))
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
  const label = String(props.storeName || 'StreamePro Oficial').toUpperCase().slice(0, 20)
  ctx.font = '900 22px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
  const textW = ctx.measureText(label).width
  const crestW = Math.max(280, Math.min(500, textW + 140))
  const crestH = 68

  ctx.shadowColor = 'rgba(0, 0, 0, 0.75)'
  ctx.shadowBlur = 18
  const bg = ctx.createLinearGradient(x, y, x + crestW, y + crestH)
  bg.addColorStop(0, 'rgba(4, 8, 20, 0.94)')
  bg.addColorStop(1, 'rgba(12, 22, 44, 0.90)')
  ctx.fillStyle = bg
  roundRectPath(ctx, x, y, crestW, crestH, crestH / 2)
  ctx.fill()
  ctx.lineWidth = 2.5
  ctx.strokeStyle = accentTheme.value.borderHex
  ctx.stroke()
  ctx.shadowBlur = 0

  // Monograma SP circular
  const circGrad = ctx.createLinearGradient(x + 10, y + 10, x + 58, y + 58)
  circGrad.addColorStop(0, accentTheme.value.badgeGrad[0])
  circGrad.addColorStop(1, accentTheme.value.badgeGrad[1])
  ctx.fillStyle = circGrad
  ctx.beginPath()
  ctx.arc(x + 34, y + 34, 24, 0, Math.PI * 2)
  ctx.fill()
  ctx.lineWidth = 2
  ctx.strokeStyle = 'rgba(255, 255, 255, 0.85)'
  ctx.stroke()

  ctx.fillStyle = '#ffffff'
  ctx.font = '900 20px sans-serif'
  ctx.textAlign = 'center'
  ctx.textBaseline = 'middle'
  ctx.fillText('SP', x + 34, y + 35)

  ctx.textAlign = 'left'
  ctx.textBaseline = 'alphabetic'
  ctx.fillStyle = '#ffffff'
  ctx.font = '900 20px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
  ctx.fillText(label, x + 70, y + 33)

  ctx.fillStyle = accentTheme.value.accentHex
  ctx.font = 'bold 13px monospace'
  ctx.fillText('✓ TIENDA OFICIAL VERIFICADA', x + 70, y + 53)
  ctx.restore()
}

const wrapTextLines = (ctx, txt, maxW) => {
  const words = String(txt || '').split(/\s+/)
  const lines = []
  let curr = ''
  for (const w of words) {
    const test = curr ? `${curr} ${w}` : w
    if (ctx.measureText(test).width > maxW && curr) {
      lines.push(curr)
      curr = w
    } else {
      curr = test
    }
  }
  if (curr) lines.push(curr)
  return lines
}

async function renderPlaqueBlob() {
  const W = 1080
  const H = 1080
  const canvas = document.createElement('canvas')
  canvas.width = W
  canvas.height = H
  const ctx = canvas.getContext('2d')
  const th = accentTheme.value

  // 1. Fondo Studio Cyber Dark de Alto Contraste con Tinte del Tema
  const bg = ctx.createLinearGradient(0, 0, 0, H)
  bg.addColorStop(0, th.bgGrad[0])
  bg.addColorStop(0.55, th.bgGrad[1])
  bg.addColorStop(1, th.bgGrad[2])
  ctx.fillStyle = bg
  ctx.fillRect(0, 0, W, H)

  // Resplandor radial superior
  const topGlow = ctx.createRadialGradient(W * 0.5, H * 0.22, 20, W * 0.5, H * 0.22, 560)
  topGlow.addColorStop(0, th.glowRgba)
  topGlow.addColorStop(1, 'rgba(0,0,0,0)')
  ctx.fillStyle = topGlow
  ctx.fillRect(0, 0, W, H)

  // Doble marco exterior metálico
  ctx.save()
  ctx.strokeStyle = 'rgba(255, 255, 255, 0.14)'
  ctx.lineWidth = 3
  roundRectPath(ctx, 20, 20, W - 40, H - 40, 36)
  ctx.stroke()
  ctx.strokeStyle = th.borderHex
  ctx.lineWidth = 1
  roundRectPath(ctx, 24, 24, W - 48, H - 48, 34)
  ctx.stroke()
  ctx.restore()

  if (isMultiMode.value) {
    // === PLACA MULTI-PRODUCTO ===
    drawLogoOverlayCrest(ctx, 52, 48)

    // Tag lateral
    ctx.save()
    const tagTxt = `${normalizedItems.value.length} SELECCIONADOS`
    ctx.font = '900 17px monospace'
    const tagW = ctx.measureText(tagTxt).width + 42
    const tagGrad = ctx.createLinearGradient(W - 52 - tagW, 58, W - 52, 106)
    tagGrad.addColorStop(0, th.badgeGrad[0])
    tagGrad.addColorStop(1, th.badgeGrad[1])
    ctx.fillStyle = tagGrad
    roundRectPath(ctx, W - 52 - tagW, 58, tagW, 46, 23)
    ctx.fill()
    ctx.fillStyle = '#ffffff'
    ctx.textAlign = 'center'
    ctx.fillText(tagTxt, W - 52 - tagW / 2, 87)
    ctx.restore()

    // Title band
    ctx.save()
    ctx.fillStyle = '#ffffff'
    ctx.textAlign = 'left'
    ctx.font = '900 42px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
    ctx.fillText(plaqueHeadline.value.slice(0, 38), 54, 178)
    ctx.fillStyle = th.accentHex
    ctx.font = 'bold 20px sans-serif'
    ctx.fillText(plaqueSubline.value.slice(0, 68), 54, 212)
    ctx.restore()

    // Showcase Product Rows
    const items = normalizedItems.value
    const imgs = await Promise.all(items.map(it => loadImg(getCORSUrl(it.image))))
    const startY = 246
    const rowH = Math.min(156, Math.floor(620 / Math.max(1, items.length)) - 14)

    items.forEach((it, idx) => {
      const y = startY + idx * (rowH + 14)
      ctx.save()
      ctx.fillStyle = 'rgba(12, 18, 36, 0.92)'
      roundRectPath(ctx, 52, y, W - 104, rowH, 22)
      ctx.fill()
      ctx.strokeStyle = 'rgba(255, 255, 255, 0.12)'
      ctx.lineWidth = 2
      ctx.stroke()

      // Thumbnail
      const thumbSize = rowH - 24
      const tx = 66
      const ty = y + 12
      ctx.save()
      roundRectPath(ctx, tx, ty, thumbSize, thumbSize, 14)
      ctx.clip()
      ctx.fillStyle = '#020617'
      ctx.fillRect(tx, ty, thumbSize, thumbSize)
      const im = imgs[idx]
      if (im) {
        const sc = Math.min(thumbSize / im.width, thumbSize / im.height)
        const dw = im.width * sc
        const dh = im.height * sc
        ctx.drawImage(im, tx + (thumbSize - dw) / 2, ty + (thumbSize - dh) / 2, dw, dh)
      }
      ctx.restore()

      // Title + tag
      ctx.textAlign = 'left'
      ctx.fillStyle = th.accentHex
      ctx.font = 'bold 15px monospace'
      ctx.fillText(it.tag.toUpperCase(), tx + thumbSize + 22, y + 40)

      ctx.fillStyle = '#ffffff'
      ctx.font = '900 26px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
      ctx.fillText(it.title.length > 32 ? it.title.slice(0, 30) + '...' : it.title, tx + thumbSize + 22, y + 78)

      ctx.fillStyle = '#94a3b8'
      ctx.font = '600 17px sans-serif'
      ctx.fillText(it.subtitle.slice(0, 42), tx + thumbSize + 22, y + 110)

      // Price right aligned
      ctx.textAlign = 'right'
      ctx.fillStyle = '#34d399'
      ctx.font = '900 32px monospace'
      ctx.fillText(`$${it.price.toLocaleString()}`, W - 78, y + rowH / 2 + 6)
      ctx.fillStyle = '#94a3b8'
      ctx.font = 'bold 14px monospace'
      ctx.fillText('ARS', W - 78, y + rowH / 2 + 26)
      ctx.restore()
    })
  } else {
    // === PLACA PRODUCTO ÚNICO (ARQUITECTURA DE CONTENIDO PERFECTA) ===
    // 1. Visor de Imagen Superior: Alto ajustado a 470px (deja 540px limpios para textos)
    const visX = 48
    const visY = 46
    const visW = W - 96
    const visH = 470

    ctx.save()
    roundRectPath(ctx, visX, visY, visW, visH, 30)
    ctx.clip()

    ctx.fillStyle = '#060a16'
    ctx.fillRect(visX, visY, visW, visH)

    const mainImgEl = await loadImg(getCORSUrl(mainItem.value.image))
    if (mainImgEl) {
      // Fondo difuminado de alto brillo
      const bgScale = Math.max(visW / mainImgEl.width, visH / mainImgEl.height) * 1.2
      const bw = mainImgEl.width * bgScale
      const bh = mainImgEl.height * bgScale
      ctx.save()
      ctx.filter = 'blur(28px) brightness(0.42) saturate(1.35)'
      ctx.drawImage(mainImgEl, visX + (visW - bw) / 2, visY + (visH - bh) / 2, bw, bh)
      ctx.restore()

      // Arte de producto nítido centrado
      const pad = 36
      const sc = Math.min((visW - pad * 2) / mainImgEl.width, (visH - pad * 2) / mainImgEl.height)
      const dw = mainImgEl.width * sc
      const dh = mainImgEl.height * sc
      ctx.save()
      ctx.shadowColor = 'rgba(0, 0, 0, 0.90)'
      ctx.shadowBlur = 36
      ctx.shadowOffsetY = 12
      ctx.drawImage(mainImgEl, visX + (visW - dw) / 2, visY + (visH - dh) / 2 + 8, dw, dh)
      ctx.restore()
    } else if (mainItem.value.services && mainItem.value.services.length > 0) {
      // Rejilla de mosaicos combo
      const srvs = mainItem.value.services.slice(0, 4)
      const cardW = Math.min(195, Math.floor((visW - 110) / srvs.length) - 14)
      const cardH = 240
      const totalRowW = srvs.length * cardW + (srvs.length - 1) * 16
      const rx0 = visX + (visW - totalRowW) / 2
      const ry0 = visY + (visH - cardH) / 2 + 18
      srvs.forEach((srv, idx) => {
        const sx = rx0 + idx * (cardW + 16)
        ctx.fillStyle = 'rgba(15, 23, 42, 0.92)'
        roundRectPath(ctx, sx, ry0, cardW, cardH, 20)
        ctx.fill()
        ctx.strokeStyle = th.borderHex
        ctx.lineWidth = 2
        ctx.stroke()
        ctx.fillStyle = '#ffffff'
        ctx.font = '900 21px sans-serif'
        ctx.textAlign = 'center'
        ctx.fillText(srv.platform, sx + cardW / 2, ry0 + cardH / 2 + 10)
        ctx.fillStyle = th.accentHex
        ctx.font = 'bold 14px monospace'
        ctx.fillText(srv.planLabel || '1 Mes', sx + cardW / 2, ry0 + cardH - 32)
      })
    } else {
      ctx.fillStyle = th.accentHex
      ctx.font = '900 64px sans-serif'
      ctx.textAlign = 'center'
      ctx.fillText(String(props.storeName || 'SP').toUpperCase().slice(0, 14), visX + visW / 2, visY + visH / 2 + 18)
    }

    // Viñeta inferior suave
    const vig = ctx.createLinearGradient(0, visY + visH - 120, 0, visY + visH)
    vig.addColorStop(0, 'rgba(6, 10, 22, 0)')
    vig.addColorStop(1, 'rgba(6, 10, 22, 0.92)')
    ctx.fillStyle = vig
    ctx.fillRect(visX, visY + visH - 120, visW, 120)

    // Crest Logo sobre la imagen (Esquina superior izquierda)
    drawLogoOverlayCrest(ctx, visX + 22, visY + 22)

    // Tag / Categoría / Calidad Top-Right sobre la imagen
    ctx.save()
    const qualityTag = isNetflixItem.value ? '4K ULTRA HD • HDR10' : (mainItem.value.tag || 'OFICIAL').toUpperCase()
    ctx.font = '900 16px monospace'
    const qtw = ctx.measureText(qualityTag).width + 36
    const qtx = visX + visW - 22 - qtw
    const qty = visY + 26
    const gPill = ctx.createLinearGradient(qtx, qty, qtx + qtw, qty + 42)
    gPill.addColorStop(0, th.badgeGrad[0])
    gPill.addColorStop(1, th.badgeGrad[1])
    ctx.fillStyle = gPill
    roundRectPath(ctx, qtx, qty, qtw, 42, 21)
    ctx.fill()
    ctx.fillStyle = '#ffffff'
    ctx.textAlign = 'center'
    ctx.fillText(qualityTag, qtx + qtw / 2, qty + 26)

    // Delivery pill debajo del tag
    const delPill = '🟢 10-15m ENTREGA'
    ctx.font = 'bold 13px monospace'
    const dtw = ctx.measureText(delPill).width + 24
    const dtx = visX + visW - 22 - dtw
    const dty = qty + 50
    ctx.fillStyle = 'rgba(6, 78, 59, 0.90)'
    roundRectPath(ctx, dtx, dty, dtw, 30, 15)
    ctx.fill()
    ctx.strokeStyle = '#34d399'
    ctx.lineWidth = 1.5
    ctx.stroke()
    ctx.fillStyle = '#34d399'
    ctx.fillText(delPill, dtx + dtw / 2, dty + 20)
    ctx.restore()

    ctx.restore() // Fin visor clip

    // Borde metálico del visor
    ctx.save()
    ctx.strokeStyle = 'rgba(255, 255, 255, 0.16)'
    ctx.lineWidth = 2.5
    roundRectPath(ctx, visX, visY, visW, visH, 30)
    ctx.stroke()
    ctx.restore()

    // 2. Zona de Texto Inferior (Medición estricta de coordenadas para cero solapamiento)
    ctx.save()
    ctx.textAlign = 'center'

    // 2.1 TÍTULO (Dinámico a 1 o 2 líneas, max 920px)
    const titleText = mainItem.value.title
    const titleLines = wrapTextLines(ctx, titleText, 940).slice(0, 2)
    const isTwoLines = titleLines.length > 1

    ctx.fillStyle = '#ffffff'
    ctx.font = isTwoLines
      ? '900 36px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
      : '900 44px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'

    if (isTwoLines) {
      ctx.fillText(titleLines[0], W / 2, 570)
      ctx.fillText(titleLines[1], W / 2, 612)
    } else {
      ctx.fillText(titleLines[0], W / 2, 584)
    }

    // 2.2 PÍLDORA DE ESPECIFICACIONES / SUBTÍTULO
    const specsY = isTwoLines ? 654 : 640
    const subText = isNetflixItem.value 
      ? '✓ 4K ULTRA HD • HDR10 · 🟢 10-15m · PIN Privado 4 dígitos'
      : (plaqueSubline.value.slice(0, 68))
    
    ctx.font = 'bold 18px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
    const subW = Math.min(W - 120, ctx.measureText(subText).width + 48)
    const subX = (W - subW) / 2
    ctx.fillStyle = 'rgba(15, 23, 42, 0.85)'
    roundRectPath(ctx, subX, specsY - 22, subW, 36, 18)
    ctx.fill()
    ctx.strokeStyle = th.borderHex
    ctx.lineWidth = 1.5
    ctx.stroke()

    ctx.fillStyle = th.accentHex
    ctx.fillText(subText, W / 2, specsY + 3)

    // 2.3 BLOQUE DE PRECIOS (Antes tachado + Descuento + Precio Venta Promo)
    const priceBoxY = isTwoLines ? 706 : 696
    const hasOrig = mainItem.value.originalPrice > mainItem.value.price && mainItem.value.price > 0
    const priceText = mainItem.value.price > 0 ? `$${mainItem.value.price.toLocaleString()} ARS` : 'CONSULTAR PRECIO'

    if (hasOrig) {
      // Línea de antes tachado
      const origText = `Antes: $${mainItem.value.originalPrice.toLocaleString()} ARS`
      ctx.fillStyle = '#94a3b8'
      ctx.font = 'bold 21px sans-serif'
      ctx.fillText(origText, W / 2, priceBoxY + 18)

      // Precio Principal Gigante con Glow
      ctx.shadowColor = th.glowRgba
      ctx.shadowBlur = 28
      ctx.fillStyle = '#ffffff'
      ctx.font = '900 64px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
      ctx.fillText(priceText, W / 2, priceBoxY + 82)
      ctx.shadowBlur = 0
    } else {
      ctx.shadowColor = th.glowRgba
      ctx.shadowBlur = 28
      ctx.fillStyle = '#ffffff'
      ctx.font = '900 68px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
      ctx.fillText(priceText, W / 2, priceBoxY + 68)
      ctx.shadowBlur = 0
    }

    // 2.4 SELLO DE CONFIANZA Y GARANTÍA OFICIAL
    const trustY = hasOrig ? (priceBoxY + 120) : (priceBoxY + 108)
    const trustText = isNetflixItem.value
      ? '🛡️ SIN BLOQUEO EN ARGENTINA · PIN 4 DÍGITOS · RENOVACIÓN MES A MES'
      : '🛡️ GARANTÍA OFICIAL · ENTREGA INMEDIATA 10-15M · ATENCIÓN VIP'
    
    ctx.fillStyle = '#34d399'
    ctx.font = '900 15px monospace'
    ctx.fillText(trustText, W / 2, trustY)
    ctx.restore()
  }

  // 3. SELLO PROMOCIONAL / CUPÓN (Si existe cupón)
  if (activePromoCode.value) {
    ctx.save()
    const badgeStr = `🎟️ CUPÓN ${activePromoCode.value} · ${activePromoLabel.value}`.slice(0, 48)
    ctx.font = '900 17px monospace'
    const bw = Math.min(W - 140, ctx.measureText(badgeStr).width + 50)
    const bx = (W - bw) / 2
    const by = 886
    const bGrad = ctx.createLinearGradient(bx, by, bx + bw, by + 38)
    bGrad.addColorStop(0, 'rgba(245, 158, 11, 0.95)')
    bGrad.addColorStop(1, 'rgba(234, 88, 12, 0.95)')
    ctx.fillStyle = bGrad
    roundRectPath(ctx, bx, by, bw, 38, 19)
    ctx.fill()
    ctx.fillStyle = '#020617'
    ctx.textAlign = 'center'
    ctx.fillText(badgeStr, W / 2, by + 25)
    ctx.restore()
  }

  // 4. FOOTER: WHATSAPP DE DISTRIBUIDOR (EXCLUSIVO Y LUMINOSO)
  ctx.save()
  const phoneToDisplay = (effectiveDistributorPhone.value || '').trim()
  const fY = activePromoCode.value ? 950 : 946
  const fH = 76
  const fW = Math.min(W - 96, 920)
  const fX = (W - fW) / 2

  // Cápsula verde esmeralda cyberpunk con resplandor
  ctx.shadowColor = 'rgba(16, 185, 129, 0.45)'
  ctx.shadowBlur = 24
  const pillGrad = ctx.createLinearGradient(fX, fY, fX + fW, fY + fH)
  pillGrad.addColorStop(0, 'rgba(6, 78, 59, 0.95)')
  pillGrad.addColorStop(0.5, 'rgba(5, 150, 105, 0.92)')
  pillGrad.addColorStop(1, 'rgba(4, 120, 87, 0.95)')
  ctx.fillStyle = pillGrad
  roundRectPath(ctx, fX, fY, fW, fH, fH / 2)
  ctx.fill()

  // Borde neón esmeralda
  ctx.lineWidth = 2.5
  ctx.strokeStyle = '#34d399'
  ctx.stroke()
  ctx.shadowBlur = 0

  // Monograma WhatsApp circular
  const iconR = 26
  const iconX = fX + 44
  const iconY = fY + fH / 2
  ctx.beginPath()
  ctx.arc(iconX, iconY, iconR, 0, Math.PI * 2)
  ctx.fillStyle = '#22c55e'
  ctx.fill()
  ctx.strokeStyle = '#86efac'
  ctx.lineWidth = 2
  ctx.stroke()

  ctx.fillStyle = '#ffffff'
  ctx.font = '28px sans-serif'
  ctx.textAlign = 'center'
  ctx.textBaseline = 'middle'
  ctx.fillText('💬', iconX, iconY + 1)

  // Textos del contacto
  ctx.textAlign = 'left'
  ctx.textBaseline = 'alphabetic'
  ctx.fillStyle = '#a7f3d0'
  ctx.font = '900 13px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
  ctx.fillText('PEDIDOS & ATENCIÓN DIRECTA POR WHATSAPP', iconX + iconR + 18, fY + 31)

  ctx.fillStyle = '#ffffff'
  ctx.font = '900 30px monospace'
  const displayPhoneText = phoneToDisplay ? `📲 ${phoneToDisplay}` : '📲 WHATSAPP OFICIAL DISPONIBLE'
  ctx.fillText(displayPhoneText, iconX + iconR + 18, fY + 62)

  // Badge derecho "24/7 ACTIVO"
  ctx.textAlign = 'right'
  ctx.fillStyle = '#ecfdf5'
  ctx.font = 'bold 15px monospace'
  ctx.fillText('⚡ 24/7 ACTIVO', fX + fW - 32, fY + fH / 2 + 5)
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

        <!-- Vista Previa de la Placa 1:1 (Aspect Ratio Cuadrado Perfecto y Cero Solapamiento) -->
        <div class="flex justify-center">
          <div class="w-full max-w-[340px] aspect-square rounded-3xl overflow-hidden bg-gradient-to-b from-[#040814] via-[#0b1224] to-[#020617] border border-white/15 shadow-2xl p-3 flex flex-col justify-between relative">
            
            <!-- MODO MULTI-PRODUCTO -->
            <template v-if="isMultiMode">
              <div class="flex items-center justify-between gap-2 pb-2 border-b border-white/10">
                <div class="flex items-center gap-2 bg-slate-950/90 border border-cyan-400/50 rounded-full pl-1.5 pr-3 py-1 shadow">
                  <span class="w-6 h-6 rounded-full bg-gradient-to-br from-sky-400 to-blue-600 flex items-center justify-center text-[9px] font-black">SP</span>
                  <span class="text-[10px] font-black tracking-wide uppercase truncate max-w-[130px]">{{ storeName }}</span>
                </div>
                <span :class="['text-[9px] font-mono font-black px-2.5 py-1 rounded-full border uppercase', accentTheme.cssPill]">
                  {{ normalizedItems.length }} Elegidos
                </span>
              </div>

              <div class="py-1">
                <h4 class="text-sm font-black text-white leading-tight truncate">{{ plaqueHeadline }}</h4>
                <p class="text-[10px] text-sky-300 truncate">{{ plaqueSubline }}</p>
              </div>

              <div class="flex-1 flex flex-col justify-center space-y-1.5 py-1 overflow-hidden">
                <div v-for="(it, idx) in normalizedItems" :key="idx" class="flex items-center gap-2.5 bg-slate-900/90 border border-white/10 rounded-xl p-1.5">
                  <img v-if="it.image" :src="it.image" :alt="it.title" class="w-10 h-10 rounded-lg object-cover bg-black shrink-0">
                  <div class="flex-1 min-w-0">
                    <div class="text-[10px] font-black text-white truncate">{{ it.title }}</div>
                    <div class="text-[9px] text-slate-400 truncate">{{ it.tag }}</div>
                  </div>
                  <span class="text-xs font-black text-emerald-300 font-mono shrink-0">${{ it.price.toLocaleString() }}</span>
                </div>
              </div>

              <div v-if="activePromoCode" class="bg-gradient-to-r from-amber-500 to-orange-500 text-slate-950 font-mono font-black text-[10px] px-3 py-1 rounded-full text-center shadow truncate">
                🎟️ CUPÓN {{ activePromoCode }} · {{ activePromoLabel }}
              </div>
              <div class="mt-2 py-1.5 px-3 rounded-full bg-gradient-to-r from-emerald-950/90 via-emerald-900/90 to-teal-950/90 border border-emerald-500/50 flex items-center justify-between text-[10px] font-mono shadow-md text-emerald-200">
                <div class="flex items-center gap-1.5 truncate">
                  <span class="text-xs">💬</span>
                  <span class="font-bold truncate">WhatsApp: {{ effectiveDistributorPhone || 'Oficial' }}</span>
                </div>
                <span class="text-[8px] bg-emerald-500/20 text-emerald-300 px-1.5 py-0.2 rounded font-black border border-emerald-400/30">24/7</span>
              </div>
            </template>

            <!-- MODO PRODUCTO ÚNICO (CON ENCAJE PERFECTO DE TEXTO) -->
            <template v-else>
              <!-- Marco superior de Imagen con Logo Superpuesto (46% de altura) -->
              <div class="relative w-full h-[46%] rounded-2xl overflow-hidden bg-slate-950 border border-white/15 flex items-center justify-center">
                <img
                  v-if="mainItem.image"
                  :src="mainItem.image"
                  :alt="mainItem.title"
                  class="absolute inset-0 w-full h-full object-cover filter blur-lg scale-125 brightness-50 saturate-125 pointer-events-none"
                >
                <img
                  v-if="mainItem.image"
                  :src="mainItem.image"
                  :alt="mainItem.title"
                  class="relative z-10 max-w-full max-h-full object-contain p-2.5 drop-shadow-[0_10px_20px_rgba(0,0,0,0.85)]"
                >
                <div v-else-if="mainItem.services?.length" class="relative z-10 flex flex-wrap items-center justify-center gap-2 p-3">
                  <div v-for="(srv, idx) in mainItem.services.slice(0, 4)" :key="idx" class="bg-slate-900/90 border border-pink-500/40 rounded-xl px-2.5 py-1.5 text-center shadow">
                    <span class="text-[11px] font-black text-white block">{{ srv.platform }}</span>
                    <span class="text-[8px] text-pink-300 font-mono">{{ srv.planLabel }}</span>
                  </div>
                </div>
                <div v-else class="relative z-10 text-2xl font-black text-sky-300 tracking-tight">{{ storeName }}</div>

                <!-- LOGO SOBRE LA IMAGEN DEL PRODUCTO -->
                <div class="absolute top-2 left-2 z-20 flex items-center gap-1.5 bg-slate-950/90 backdrop-blur-md border border-cyan-400/60 rounded-full pl-1 pr-2 py-0.5 shadow-lg">
                  <div class="w-5 h-5 rounded-full bg-gradient-to-br from-sky-400 to-blue-600 flex items-center justify-center text-[8px] font-black text-white shrink-0 border border-white/40">
                    SP
                  </div>
                  <div class="leading-none">
                    <span class="text-[8px] font-black text-white tracking-wide uppercase block max-w-[110px] truncate">{{ storeName }}</span>
                    <span class="text-[6px] font-mono font-bold text-cyan-300 block">✓ OFICIAL</span>
                  </div>
                </div>

                <!-- Tag Calidad / Entrega Top-Right -->
                <div class="absolute top-2 right-2 z-20 flex flex-col items-end gap-1">
                  <span :class="['text-[8px] font-black px-2 py-0.5 rounded-full border shadow uppercase font-mono', accentTheme.cssPill]">
                    {{ isNetflixItem ? '4K HDR10' : mainItem.tag }}
                  </span>
                  <span class="text-[7px] font-bold px-1.5 py-0.5 rounded-full bg-emerald-950/80 text-emerald-400 border border-emerald-500/40 font-mono">
                    🟢 10-15m
                  </span>
                </div>
              </div>

              <!-- Título, Subtítulo y Precios con Espaciado Fluido (54% de altura) -->
              <div class="flex-1 flex flex-col justify-between items-center text-center px-1 pt-1.5 pb-0.5">
                <div class="space-y-0.5 w-full">
                  <h4 class="text-xs sm:text-sm font-black text-white line-clamp-2 leading-snug">{{ mainItem.title }}</h4>
                  <p :class="['text-[9px] font-bold line-clamp-1 truncate', accentTheme.cssPrice]">
                    {{ plaqueSubline }}
                  </p>
                </div>

                <div class="py-0.5 w-full">
                  <div v-if="mainItem.originalPrice > mainItem.price && mainItem.price > 0" class="text-[9px] text-slate-400 line-through font-mono">
                    Antes: ${{ mainItem.originalPrice.toLocaleString() }} ARS
                  </div>
                  <div class="text-lg sm:text-xl font-black font-mono text-white drop-shadow">
                    {{ mainItem.price > 0 ? `$${mainItem.price.toLocaleString()} ARS` : 'Consultar' }}
                  </div>
                  <div class="text-[8px] text-emerald-400 font-bold tracking-wider font-mono uppercase">
                    {{ isNetflixItem ? '🛡️ Sin bloqueo Argentina · PIN 4 dígitos' : '🛡️ Garantía Oficial · Reemplazo Inmediato' }}
                  </div>
                </div>

                <!-- Cupón si aplica -->
                <div v-if="activePromoCode" class="w-full bg-gradient-to-r from-amber-500 to-orange-500 text-slate-950 font-mono font-black text-[9px] py-0.5 rounded-full text-center shadow truncate">
                  🎟️ CUPÓN {{ activePromoCode }} · {{ activePromoLabel }}
                </div>

                <!-- Cápsula WhatsApp del Distribuidor -->
                <div class="w-full py-1 px-2.5 rounded-full bg-gradient-to-r from-emerald-950/90 via-emerald-900/90 to-teal-950/90 border border-emerald-500/50 flex items-center justify-between text-[9px] font-mono shadow-md text-emerald-200">
                  <div class="flex items-center gap-1.5 truncate">
                    <span class="text-[11px]">💬</span>
                    <span class="font-bold truncate">WhatsApp: {{ effectiveDistributorPhone || 'Oficial' }}</span>
                  </div>
                  <span class="text-[7px] bg-emerald-500/20 text-emerald-300 px-1 py-0.2 rounded font-black border border-emerald-400/30">24/7</span>
                </div>
              </div>
            </template>
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

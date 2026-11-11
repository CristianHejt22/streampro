<script setup>
import { ref, computed } from 'vue'
import { useToastStore } from '../store/toast'

const props = defineProps({
  show: { type: Boolean, default: false },
  payload: { type: Object, default: null },
  storeName: { type: String, default: 'StreamePro Oficial' },
  resellerRef: { type: String, default: 'DIRECT' },
  contactPhone: { type: String, default: '' }
})

const emit = defineEmits(['close'])
const toast = useToastStore()
const isExporting = ref(false)

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
    image: p.image || '',
    tag: String(p.tag || p.platform || 'OFICIAL').slice(0, 24),
    services: Array.isArray(p.services) ? p.services : null,
    discountPct: Number(p.discountPct || 0)
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
  const pt = props.payload?.productType || 'gaming'
  if (pt === 'physical') return '🚚 Stock Disponible · Envíos a Todo el País · Garantía Oficial'
  if (pt === 'package') return '🎁 Combo Multi-Plataforma · Perfiles Privados con PIN'
  if (pt === 'streaming') return '🎬 Perfil Privado Oficial 4K · Activación en 10m a 1h'
  return '🎮 Licencia Digital Original · Entrega 60/90 min · Garantía Oficial'
})

const accentTheme = computed(() => {
  const pt = props.payload?.productType || 'gaming'
  if (pt === 'physical') {
    return {
      glowRgba: 'rgba(245, 158, 11, 0.32)',
      borderHex: '#f59e0b',
      accentHex: '#fbbf24',
      badgeGrad: ['#f59e0b', '#d97706'],
      cssPill: 'bg-amber-500/20 text-amber-300 border-amber-400/50',
      cssPrice: 'text-amber-300'
    }
  }
  if (pt === 'package') {
    return {
      glowRgba: 'rgba(236, 72, 153, 0.34)',
      borderHex: '#ec4899',
      accentHex: '#f472b6',
      badgeGrad: ['#ec4899', '#a855f7'],
      cssPill: 'bg-pink-500/20 text-pink-300 border-pink-400/50',
      cssPrice: 'text-pink-300'
    }
  }
  if (pt === 'streaming') {
    return {
      glowRgba: 'rgba(56, 189, 248, 0.32)',
      borderHex: '#38bdf8',
      accentHex: '#7dd3fc',
      badgeGrad: ['#0284c7', '#2563eb'],
      cssPill: 'bg-sky-500/20 text-sky-300 border-sky-400/50',
      cssPrice: 'text-sky-300'
    }
  }
  return {
    glowRgba: 'rgba(99, 102, 241, 0.38)',
    borderHex: '#38bdf8',
    accentHex: '#38bdf8',
    badgeGrad: ['#4f46e5', '#0284c7'],
    cssPill: 'bg-indigo-500/25 text-cyan-200 border-cyan-400/50',
    cssPrice: 'text-cyan-300'
  }
})

const directShareUrl = computed(() => {
  const origin = (typeof window !== 'undefined' && (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1'))
    ? window.location.origin
    : 'https://streamepro.me'
  if (props.payload?.shareUrl) return props.payload.shareUrl
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
      if (props.payload?.title) qs.set('title', String(props.payload.title).slice(0, 56))
      return `${origin}/seleccion?${qs.toString()}`
    }
    if (pt === 'package') return ref ? `${origin}/combos?ref=${encodeURIComponent(ref)}` : `${origin}/combos`
    if (pt === 'gaming') return ref ? `${origin}/juegos?ref=${encodeURIComponent(ref)}` : `${origin}/juegos`
    if (pt === 'physical') return ref ? `${origin}/tienda?ref=${encodeURIComponent(ref)}` : `${origin}/tienda`
    return ref ? `${origin}/?ref=${encodeURIComponent(ref)}` : `${origin}/`
  }
  if (pt === 'gaming') {
    const lic = props.payload?.licenseType || 'primary'
    return ref
      ? `${origin}/juego/${encodeURIComponent(pid)}/${encodeURIComponent(ref)}?lic=${lic}`
      : `${origin}/juego/${encodeURIComponent(pid)}?lic=${lic}`
  }
  if (pt === 'physical') {
    return ref ? `${origin}/producto/${encodeURIComponent(pid)}/${encodeURIComponent(ref)}` : `${origin}/producto/${encodeURIComponent(pid)}`
  }
  if (pt === 'package') {
    return ref ? `${origin}/combo/${encodeURIComponent(pid)}/${encodeURIComponent(ref)}` : `${origin}/combo/${encodeURIComponent(pid)}`
  }
  return ref ? `${origin}/servicio/${encodeURIComponent(pid)}/${encodeURIComponent(ref)}` : `${origin}/servicio/${encodeURIComponent(pid)}`
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
  const label = String(props.storeName || 'StreamePro Oficial').toUpperCase().slice(0, 22)
  ctx.font = '900 24px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
  const textW = ctx.measureText(label).width
  const crestW = Math.max(290, Math.min(520, textW + 148))
  const crestH = 74

  ctx.shadowColor = 'rgba(0, 0, 0, 0.7)'
  ctx.shadowBlur = 18
  const bg = ctx.createLinearGradient(x, y, x + crestW, y + crestH)
  bg.addColorStop(0, 'rgba(6, 12, 28, 0.92)')
  bg.addColorStop(1, 'rgba(15, 28, 56, 0.88)')
  ctx.fillStyle = bg
  roundRectPath(ctx, x, y, crestW, crestH, crestH / 2)
  ctx.fill()
  ctx.lineWidth = 2.5
  ctx.strokeStyle = 'rgba(56, 189, 248, 0.75)'
  ctx.stroke()
  ctx.shadowBlur = 0

  // Circular SP monogram badge
  const circGrad = ctx.createLinearGradient(x + 12, y + 12, x + 62, y + 62)
  circGrad.addColorStop(0, '#0ea5e9')
  circGrad.addColorStop(1, '#2563eb')
  ctx.fillStyle = circGrad
  ctx.beginPath()
  ctx.arc(x + 37, y + 37, 25, 0, Math.PI * 2)
  ctx.fill()
  ctx.lineWidth = 2
  ctx.strokeStyle = 'rgba(224, 242, 254, 0.85)'
  ctx.stroke()

  ctx.fillStyle = '#ffffff'
  ctx.font = '900 22px sans-serif'
  ctx.textAlign = 'center'
  ctx.textBaseline = 'middle'
  ctx.fillText('SP', x + 37, y + 38)

  ctx.textAlign = 'left'
  ctx.textBaseline = 'alphabetic'
  ctx.fillStyle = '#ffffff'
  ctx.font = '900 22px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
  ctx.fillText(label, x + 76, y + 36)

  ctx.fillStyle = '#38bdf8'
  ctx.font = 'bold 14px monospace'
  ctx.fillText('✓ TIENDA OFICIAL VERIFICADA', x + 76, y + 57)
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

  // 1. Rich Studio Dark Background + Glows
  const bg = ctx.createLinearGradient(0, 0, 0, H)
  bg.addColorStop(0, '#040814')
  bg.addColorStop(0.55, '#0b1224')
  bg.addColorStop(1, '#020617')
  ctx.fillStyle = bg
  ctx.fillRect(0, 0, W, H)

  const topGlow = ctx.createRadialGradient(W * 0.5, H * 0.25, 30, W * 0.5, H * 0.25, 580)
  topGlow.addColorStop(0, th.glowRgba)
  topGlow.addColorStop(1, 'rgba(0,0,0,0)')
  ctx.fillStyle = topGlow
  ctx.fillRect(0, 0, W, H)

  // Outer crisp border frame
  ctx.save()
  ctx.strokeStyle = 'rgba(255, 255, 255, 0.12)'
  ctx.lineWidth = 3
  roundRectPath(ctx, 22, 22, W - 44, H - 44, 38)
  ctx.stroke()
  ctx.restore()

  if (isMultiMode.value) {
    // === PLACA MULTI-PRODUCTO ===
    drawLogoOverlayCrest(ctx, 56, 52)

    // Right pill tag
    ctx.save()
    const tagTxt = `${normalizedItems.value.length} PRODUCTOS SELECCIONADOS`
    ctx.font = '900 17px monospace'
    const tagW = ctx.measureText(tagTxt).width + 44
    const tagGrad = ctx.createLinearGradient(W - 56 - tagW, 64, W - 56, 114)
    tagGrad.addColorStop(0, th.badgeGrad[0])
    tagGrad.addColorStop(1, th.badgeGrad[1])
    ctx.fillStyle = tagGrad
    roundRectPath(ctx, W - 56 - tagW, 64, tagW, 48, 24)
    ctx.fill()
    ctx.fillStyle = '#ffffff'
    ctx.textAlign = 'center'
    ctx.fillText(tagTxt, W - 56 - tagW / 2, 94)
    ctx.restore()

    // Title band
    ctx.save()
    ctx.fillStyle = '#ffffff'
    ctx.textAlign = 'left'
    ctx.font = '900 44px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
    ctx.fillText(plaqueHeadline.value.slice(0, 38), 58, 194)
    ctx.fillStyle = th.accentHex
    ctx.font = 'bold 21px sans-serif'
    ctx.fillText(plaqueSubline.value.slice(0, 68), 58, 228)
    ctx.restore()

    // Showcase Product Rows
    const items = normalizedItems.value
    const imgs = await Promise.all(items.map(it => loadImg(getCORSUrl(it.image))))
    const startY = 264
    const rowH = Math.min(162, Math.floor(600 / Math.max(1, items.length)) - 14)

    items.forEach((it, idx) => {
      const y = startY + idx * (rowH + 16)
      ctx.save()
      ctx.fillStyle = 'rgba(15, 23, 42, 0.90)'
      roundRectPath(ctx, 56, y, W - 112, rowH, 24)
      ctx.fill()
      ctx.strokeStyle = 'rgba(255, 255, 255, 0.12)'
      ctx.lineWidth = 2
      ctx.stroke()

      // Thumbnail
      const thumbSize = rowH - 24
      const tx = 70
      const ty = y + 12
      ctx.save()
      roundRectPath(ctx, tx, ty, thumbSize, thumbSize, 16)
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
      ctx.font = 'bold 16px monospace'
      ctx.fillText(it.tag.toUpperCase(), tx + thumbSize + 24, y + 44)

      ctx.fillStyle = '#ffffff'
      ctx.font = '900 28px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
      ctx.fillText(it.title.length > 34 ? it.title.slice(0, 32) + '...' : it.title, tx + thumbSize + 24, y + 84)

      ctx.fillStyle = '#94a3b8'
      ctx.font = '600 18px sans-serif'
      ctx.fillText(it.subtitle.slice(0, 44), tx + thumbSize + 24, y + 118)

      // Price right aligned
      ctx.textAlign = 'right'
      ctx.fillStyle = '#34d399'
      ctx.font = '900 34px monospace'
      ctx.fillText(`$${it.price.toLocaleString()}`, W - 82, y + rowH / 2 + 6)
      ctx.fillStyle = '#94a3b8'
      ctx.font = 'bold 15px monospace'
      ctx.fillText('ARS', W - 82, y + rowH / 2 + 28)
      ctx.restore()
    })
  } else {
    // === PLACA PRODUCTO ÚNICO (CON LOGO SOBRE IMAGEN DEL PRODUCTO) ===
    const visX = 52
    const visY = 50
    const visW = W - 104
    const visH = 590

    ctx.save()
    roundRectPath(ctx, visX, visY, visW, visH, 32)
    ctx.clip()

    ctx.fillStyle = '#060a16'
    ctx.fillRect(visX, visY, visW, visH)

    const mainImgEl = await loadImg(getCORSUrl(mainItem.value.image))
    if (mainImgEl) {
      // Blurred luminous backdrop
      const bgScale = Math.max(visW / mainImgEl.width, visH / mainImgEl.height) * 1.18
      const bw = mainImgEl.width * bgScale
      const bh = mainImgEl.height * bgScale
      ctx.save()
      ctx.filter = 'blur(26px) brightness(0.45) saturate(1.3)'
      ctx.drawImage(mainImgEl, visX + (visW - bw) / 2, visY + (visH - bh) / 2, bw, bh)
      ctx.restore()

      // Centered sharp full product artwork
      const pad = 42
      const sc = Math.min((visW - pad * 2) / mainImgEl.width, (visH - pad * 2) / mainImgEl.height)
      const dw = mainImgEl.width * sc
      const dh = mainImgEl.height * sc
      ctx.save()
      ctx.shadowColor = 'rgba(0, 0, 0, 0.85)'
      ctx.shadowBlur = 32
      ctx.shadowOffsetY = 12
      ctx.drawImage(mainImgEl, visX + (visW - dw) / 2, visY + (visH - dh) / 2 + 12, dw, dh)
      ctx.restore()
    } else if (mainItem.value.services && mainItem.value.services.length > 0) {
      // Combo visual tile grid when combo has no bitmap cover
      const srvs = mainItem.value.services.slice(0, 4)
      const cardW = Math.min(200, Math.floor((visW - 120) / srvs.length) - 16)
      const cardH = 260
      const totalRowW = srvs.length * cardW + (srvs.length - 1) * 18
      const rx0 = visX + (visW - totalRowW) / 2
      const ry0 = visY + (visH - cardH) / 2 + 24
      srvs.forEach((srv, idx) => {
        const sx = rx0 + idx * (cardW + 18)
        ctx.fillStyle = 'rgba(15, 23, 42, 0.88)'
        roundRectPath(ctx, sx, ry0, cardW, cardH, 22)
        ctx.fill()
        ctx.strokeStyle = th.borderHex
        ctx.lineWidth = 2
        ctx.stroke()
        ctx.fillStyle = '#ffffff'
        ctx.font = '900 22px sans-serif'
        ctx.textAlign = 'center'
        ctx.fillText(srv.platform, sx + cardW / 2, ry0 + cardH / 2 + 14)
        ctx.fillStyle = th.accentHex
        ctx.font = 'bold 15px monospace'
        ctx.fillText(srv.planLabel || '1 Mes', sx + cardW / 2, ry0 + cardH - 36)
      })
    } else {
      ctx.fillStyle = th.accentHex
      ctx.font = '900 68px sans-serif'
      ctx.textAlign = 'center'
      ctx.fillText(String(props.storeName || 'SP').toUpperCase().slice(0, 14), visX + visW / 2, visY + visH / 2 + 20)
    }

    // Bottom vignette inside visor
    const vig = ctx.createLinearGradient(0, visY + visH - 140, 0, visY + visH)
    vig.addColorStop(0, 'rgba(6, 10, 22, 0)')
    vig.addColorStop(1, 'rgba(6, 10, 22, 0.9)')
    ctx.fillStyle = vig
    ctx.fillRect(visX, visY + visH - 140, visW, 140)

    // LOGO DIRECTAMENTE SOBRE LA IMAGEN DEL PRODUCTO (ESQUINA SUP. IZQ.)
    drawLogoOverlayCrest(ctx, visX + 26, visY + 26)

    // Tag categoría/plataforma esquina superior derecha sobre la imagen
    if (mainItem.value.tag) {
      ctx.save()
      const tStr = mainItem.value.tag.toUpperCase()
      ctx.font = '900 18px monospace'
      const tw = ctx.measureText(tStr).width + 38
      const tx = visX + visW - 26 - tw
      const ty = visY + 34
      const gPill = ctx.createLinearGradient(tx, ty, tx + tw, ty + 48)
      gPill.addColorStop(0, th.badgeGrad[0])
      gPill.addColorStop(1, th.badgeGrad[1])
      ctx.fillStyle = gPill
      roundRectPath(ctx, tx, ty, tw, 48, 24)
      ctx.fill()
      ctx.fillStyle = '#ffffff'
      ctx.textAlign = 'center'
      ctx.fillText(tStr, tx + tw / 2, ty + 30)
      ctx.restore()
    }
    ctx.restore()

    // Borde sutil metálico del visor de imagen
    ctx.save()
    ctx.strokeStyle = 'rgba(255, 255, 255, 0.16)'
    ctx.lineWidth = 2.5
    roundRectPath(ctx, visX, visY, visW, visH, 32)
    ctx.stroke()
    ctx.restore()

    // Título, Subtítulo y Precio limpios en zona inferior
    ctx.save()
    ctx.textAlign = 'center'
    ctx.fillStyle = '#ffffff'
    ctx.font = '900 44px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
    const lines = wrapTextLines(ctx, mainItem.value.title, W - 130).slice(0, 2)
    lines.forEach((ln, i) => {
      ctx.fillText(ln, W / 2, 696 + i * 50)
    })

    const subY = lines.length > 1 ? 786 : 748
    ctx.fillStyle = th.accentHex
    ctx.font = 'bold 21px sans-serif'
    ctx.fillText(plaqueSubline.value.slice(0, 72), W / 2, subY)

    // Píldora limpia de Precio
    const pY = lines.length > 1 ? 824 : 792
    const priceText = mainItem.value.price > 0 ? `$${mainItem.value.price.toLocaleString()} ARS` : 'CONSULTAR PRECIO'
    const hasOrig = mainItem.value.originalPrice > mainItem.value.price && mainItem.value.price > 0
    if (hasOrig) {
      ctx.fillStyle = '#94a3b8'
      ctx.font = 'bold 22px sans-serif'
      ctx.fillText(`Antes: $${mainItem.value.originalPrice.toLocaleString()} ARS`, W / 2, pY + 22)
    }

    ctx.shadowColor = th.glowRgba
    ctx.shadowBlur = 22
    ctx.fillStyle = '#ffffff'
    ctx.font = '900 68px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
    ctx.fillText(priceText, W / 2, pY + (hasOrig ? 96 : 74))
    ctx.shadowBlur = 0
    ctx.restore()
  }

  // Footer elegante con enlace directo y teléfono
  ctx.save()
  const fY = 980
  const footGrad = ctx.createLinearGradient(120, fY - 12, W - 120, fY - 12)
  footGrad.addColorStop(0, 'rgba(56, 189, 248, 0)')
  footGrad.addColorStop(0.5, 'rgba(56, 189, 248, 0.55)')
  footGrad.addColorStop(1, 'rgba(56, 189, 248, 0)')
  ctx.strokeStyle = footGrad
  ctx.lineWidth = 2
  ctx.beginPath()
  ctx.moveTo(120, fY - 12)
  ctx.lineTo(W - 120, fY - 12)
  ctx.stroke()

  ctx.textAlign = 'center'
  ctx.fillStyle = '#e2e8f0'
  ctx.font = 'bold 22px monospace'
  const cleanUrl = directShareUrl.value.replace(/^https?:\/\//i, '')
  const footText = props.contactPhone
    ? `🌐 ${cleanUrl.slice(0, 42)}   •   📲 ${props.contactPhone}`
    : `🌐 Compra Oficial: ${cleanUrl.slice(0, 56)}`
  ctx.fillText(footText, W / 2, fY + 26)
  ctx.restore()

  return await new Promise((resolve) => canvas.toBlob(resolve, 'image/png', 0.95))
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
    setTimeout(() => URL.revokeObjectURL(url), 1000)
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
  const shareText = isMultiMode.value
    ? `🔥 *${plaqueHeadline.value} (${props.storeName})*\n\n${normalizedItems.value.map(i => `• *${i.title}* — $${i.price.toLocaleString()} ARS`).join('\n')}\n\n👉 Ver catálogo oficial:\n${directShareUrl.value}`
    : `🔥 *${mainItem.value.title}*\n📌 ${plaqueSubline.value}\n💰 *Precio: $${mainItem.value.price.toLocaleString()} ARS*\n\n👉 Compra directa aquí:\n${directShareUrl.value}`

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
    // Fallback escritorio: abrir WhatsApp con mensaje + ofrecer descarga automática de placa
    if (blob) {
      const url = URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = `placa_${plaqueHeadline.value.toLowerCase().replace(/[^a-z0-9]/g, '_').slice(0, 24) || 'oferta'}.png`
      document.body.appendChild(a)
      a.click()
      document.body.removeChild(a)
      setTimeout(() => URL.revokeObjectURL(url), 1000)
    }
    window.open(`https://api.whatsapp.com/send?text=${encodeURIComponent(shareText)}`, '_blank')
    toast.add('📲 Placa descargada y WhatsApp abierto para adjuntarla en un toque', 'success')
  } catch (err) {
    if (err?.name !== 'AbortError') {
      window.open(`https://api.whatsapp.com/send?text=${encodeURIComponent(shareText)}`, '_blank')
    }
  } finally {
    isExporting.value = false
  }
}
</script>

<template>
  <div v-if="show && payload" class="fixed inset-0 z-[85] flex items-center justify-center p-3 sm:p-5 bg-black/85 backdrop-blur-md animate-in fade-in duration-200 overflow-y-auto">
    <div class="bg-slate-900 border border-slate-700/80 w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden text-white flex flex-col">
      
      <!-- Encabezado Simple -->
      <div class="px-5 py-3.5 border-b border-slate-800 flex items-center justify-between bg-slate-950/75">
        <div class="flex items-center gap-2.5 min-w-0">
          <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-sky-400 to-blue-600 flex items-center justify-center font-black text-xs text-white shadow shrink-0">
            SP
          </div>
          <div class="min-w-0">
            <h3 class="text-sm sm:text-base font-black text-white truncate">Placa Lista para Compartir</h3>
            <p class="text-[11px] text-slate-400 truncate">Diseño con logo oficial directo para WhatsApp o redes</p>
          </div>
        </div>
        <button @click="emit('close')" type="button" class="w-9 h-9 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700 font-bold text-sm flex items-center justify-center cursor-pointer shrink-0">
          ✕
        </button>
      </div>

      <!-- Vista Previa Exacta de Placa 1:1 -->
      <div class="p-4 sm:p-5 flex flex-col items-center space-y-4 bg-[#020617]">
        <div class="w-full max-w-[380px] aspect-square rounded-3xl overflow-hidden bg-gradient-to-b from-[#040814] via-[#0b1224] to-[#020617] border border-white/15 shadow-2xl p-3 flex flex-col justify-between relative">
          
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

            <div class="pt-2 border-t border-white/10 text-center text-[9px] font-mono text-slate-300 truncate">
              🌐 {{ directShareUrl.replace(/^https?:\/\//i, '') }}
            </div>
          </template>

          <!-- MODO PRODUCTO ÚNICO (CON LOGO SOBRE LA IMAGEN DEL PRODUCTO) -->
          <template v-else>
            <!-- Marco superior de Imagen con Logo Superpuesto -->
            <div class="relative w-full h-[58%] rounded-2xl overflow-hidden bg-slate-950 border border-white/15 flex items-center justify-center">
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
                class="relative z-10 max-w-full max-h-full object-contain p-3 drop-shadow-[0_10px_20px_rgba(0,0,0,0.85)]"
              >
              <div v-else-if="mainItem.services?.length" class="relative z-10 flex flex-wrap items-center justify-center gap-2 p-4">
                <div v-for="(srv, idx) in mainItem.services.slice(0, 4)" :key="idx" class="bg-slate-900/90 border border-pink-500/40 rounded-xl px-3 py-2 text-center shadow">
                  <span class="text-xs font-black text-white block">{{ srv.platform }}</span>
                  <span class="text-[9px] text-pink-300 font-mono">{{ srv.planLabel }}</span>
                </div>
              </div>
              <div v-else class="relative z-10 text-2xl font-black text-sky-300 tracking-tight">{{ storeName }}</div>

              <!-- LOGO SOBRE LA IMAGEN DEL PRODUCTO -->
              <div class="absolute top-2.5 left-2.5 z-20 flex items-center gap-1.5 bg-slate-950/90 backdrop-blur-md border border-cyan-400/60 rounded-full pl-1 pr-2.5 py-1 shadow-lg">
                <div class="w-6 h-6 rounded-full bg-gradient-to-br from-sky-400 to-blue-600 flex items-center justify-center text-[9px] font-black text-white shrink-0 border border-white/40">
                  SP
                </div>
                <div class="leading-none">
                  <span class="text-[9px] font-black text-white tracking-wide uppercase block max-w-[125px] truncate">{{ storeName }}</span>
                  <span class="text-[7px] font-mono font-bold text-cyan-300 block">✓ OFICIAL</span>
                </div>
              </div>

              <!-- Tag Consola / Categoría Top-Right -->
              <div v-if="mainItem.tag" :class="['absolute top-2.5 right-2.5 z-20 text-[9px] font-black px-2.5 py-1 rounded-full border shadow uppercase font-mono', accentTheme.cssPill]">
                {{ mainItem.tag }}
              </div>
            </div>

            <!-- Título y Precio Limpios -->
            <div class="flex-1 flex flex-col justify-center items-center text-center px-2 space-y-1">
              <h4 class="text-sm sm:text-base font-black text-white line-clamp-2 leading-tight">{{ mainItem.title }}</h4>
              <p :class="['text-[10px] font-semibold line-clamp-1', accentTheme.cssPrice]">{{ plaqueSubline }}</p>
              <div class="pt-0.5 flex items-baseline justify-center gap-2">
                <span v-if="mainItem.originalPrice > mainItem.price && mainItem.price > 0" class="text-[10px] text-slate-400 line-through font-mono">
                  ${{ mainItem.originalPrice.toLocaleString() }}
                </span>
                <span class="text-xl sm:text-2xl font-black font-mono text-white drop-shadow">
                  {{ mainItem.price > 0 ? `$${mainItem.price.toLocaleString()} ARS` : 'Consultar' }}
                </span>
              </div>
            </div>

            <!-- Pie con Link Directo -->
            <div class="pt-1.5 border-t border-cyan-500/20 text-center text-[9px] font-mono text-slate-300 truncate">
              🌐 {{ directShareUrl.replace(/^https?:\/\//i, '') }}{{ contactPhone ? ` · 📲 ${contactPhone}` : '' }}
            </div>
          </template>
        </div>

        <!-- 3 BOTONES DIRECTOS Y SIMPLES -->
        <div class="w-full space-y-2">
          <div class="grid grid-cols-2 gap-2">
            <button
              type="button"
              @click="sharePlaqueDirect"
              :disabled="isExporting"
              class="bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-slate-950 font-black text-xs py-3 px-3 rounded-xl shadow-lg flex items-center justify-center gap-1.5 cursor-pointer transition active:scale-95 disabled:opacity-50"
            >
              <span>📲</span>
              <span>{{ isExporting ? 'Generando...' : 'Compartir Placa' }}</span>
            </button>
            <button
              type="button"
              @click="downloadPlaquePng"
              :disabled="isExporting"
              class="bg-gradient-to-r from-sky-500 to-indigo-600 hover:from-sky-400 hover:to-indigo-500 text-white font-black text-xs py-3 px-3 rounded-xl shadow-lg flex items-center justify-center gap-1.5 cursor-pointer transition active:scale-95 disabled:opacity-50"
            >
              <span>⬇️</span>
              <span>Descargar PNG</span>
            </button>
          </div>

          <button
            type="button"
            @click="copyPlaqueLink"
            class="w-full bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs py-2.5 px-3 rounded-xl border border-slate-700 flex items-center justify-center gap-1.5 cursor-pointer transition"
          >
            <span>🔗 Copiar Enlace Directo ({{ directShareUrl.replace(/^https?:\/\//i, '').slice(0, 38) }})</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

import fs from 'fs'

let cachedGamingList = null
let cachedPhysicalList = null

function getGamingList() {
  if (cachedGamingList) return cachedGamingList
  try {
    const url = new URL('../src/assets/gamingProducts.json', import.meta.url)
    cachedGamingList = JSON.parse(fs.readFileSync(url, 'utf8'))
  } catch (err) {
    console.warn('Could not load gamingProducts.json:', err?.message || err)
    cachedGamingList = []
  }
  return cachedGamingList
}

function getPhysicalList() {
  if (cachedPhysicalList) return cachedPhysicalList
  try {
    const url = new URL('../src/assets/physicalProducts.json', import.meta.url)
    cachedPhysicalList = JSON.parse(fs.readFileSync(url, 'utf8'))
  } catch (err) {
    console.warn('Could not load physicalProducts.json:', err?.message || err)
    cachedPhysicalList = []
  }
  return cachedPhysicalList
}

export const STREAMING_PLATFORMS_META = {
  Netflix: {
    name: 'Netflix Premium Ultra HD 4K',
    minPrice: 7700,
    tag: 'STREAMING 4K HDR',
    accentHex: '#ef4444',
    bgGrad: ['#450a0a', '#090d16'],
    description: 'Calidad 4K HDR con audio espacial, perfil privado con PIN y descargas offline.'
  },
  Spotify: {
    name: 'Spotify Familiar Individual',
    minPrice: 3800,
    tag: 'MÚSICA SIN LÍMITES',
    accentHex: '#22c55e',
    bgGrad: ['#052e16', '#090d16'],
    description: 'Música sin anuncios en tu propia cuenta o slot individual garantizado.'
  },
  Max: {
    name: 'Max Platino Estándar',
    minPrice: 6000,
    tag: 'HBO & CINE ESTRENO',
    accentHex: '#3b82f6',
    bgGrad: ['#172554', '#090d16'],
    description: 'Series HBO, Warner, DC y eventos deportivos en vivo en máxima calidad.'
  },
  'Disney+': {
    name: 'Disney+ / Star+ Combo',
    minPrice: 6500,
    tag: 'DISNEY & ESPN',
    accentHex: '#38bdf8',
    bgGrad: ['#082f49', '#090d16'],
    description: 'Todo Disney, Pixar, Marvel, Star Wars y fútbol ESPN en vivo con PIN.'
  },
  IPTV: {
    name: 'IPTV MagisTV Pro 1 Mes',
    minPrice: 6500,
    tag: '+1200 CANALES FHD',
    accentHex: '#a855f7',
    bgGrad: ['#2e1065', '#090d16'],
    description: '+1200 canales en vivo, deportes PPV, series y películas Full HD 24/7.'
  },
  'Prime Video': {
    name: 'Amazon Prime Video 4K',
    minPrice: 4800,
    tag: 'AMAZON 4K UHD',
    accentHex: '#06b6d4',
    bgGrad: ['#083344', '#090d16'],
    description: 'Estrenos exclusivos, Amazon Originals y miles de películas 4K UHD.'
  },
  'YouTube Premium': {
    name: 'YouTube Premium & Music',
    minPrice: 4500,
    tag: 'SIN PUBLICIDAD',
    accentHex: '#f43f5e',
    bgGrad: ['#4c0519', '#090d16'],
    description: 'Videos sin anuncios, reproducción en segundo plano y YouTube Music.'
  },
  'Canva Pro': {
    name: 'Canva Pro Oficial',
    minPrice: 4900,
    tag: 'DISEÑO PRO',
    accentHex: '#8b5cf6',
    bgGrad: ['#2e1065', '#090d16'],
    description: 'Plantillas ilimitadas, quitafondos con 1 clic y Kit de Marcas a tu correo.'
  }
}

export const DEFAULT_PACKAGES_META = {
  'PKG-101': {
    id: 'PKG-101',
    title: 'Combo Cine Total 4K (Netflix + Disney+ + Max)',
    description: 'Las 3 plataformas líderes de series, cine y estrenos en calidad Ultra HD.',
    badge: '🔥 25% OFF - RECOMENDADO',
    originalPrice: 20200,
    promoPrice: 15900,
    services: [
      { platform: 'Netflix', planLabel: '1 Pantalla 4K' },
      { platform: 'Max', planLabel: '1 Pantalla Platino' },
      { platform: 'Disney+', planLabel: '1 Pantalla VIP' }
    ]
  },
  'PKG-102': {
    id: 'PKG-102',
    title: 'Dúo Series & Música (Netflix + Spotify)',
    description: 'Tus series favoritas en Ultra HD y toda la música sin publicidad.',
    badge: '⚡ COMBO POPULAR',
    originalPrice: 11500,
    promoPrice: 9400,
    services: [
      { platform: 'Netflix', planLabel: '1 Pantalla 4K' },
      { platform: 'Spotify', planLabel: '1 Slot Familiar' }
    ]
  }
}

export function normalizeImageUrl(rawUrl) {
  if (!rawUrl) return ''
  const trimmed = String(rawUrl).trim()
  if (!trimmed) return ''
  if (trimmed.startsWith('http://') || trimmed.startsWith('https://')) return trimmed
  if (trimmed.startsWith('/')) return `https://streamepro.me${trimmed}`
  return `https://www.revende.com.ar/${trimmed}`
}

export function findGameByIdOrSlug(rawId) {
  if (!rawId) return null
  const needle = String(rawId).trim().toLowerCase()
  const normNeedle = needle.replace(/[^a-z0-9]/g, '')
  const list = getGamingList()
  return list.find(g =>
    String(g.id || '').toLowerCase() === needle ||
    String(g.slug || '').toLowerCase() === needle ||
    String(g.title || '').toLowerCase().replace(/[^a-z0-9]/g, '') === normNeedle
  ) || null
}

export function findPhysicalByIdOrSlug(rawId) {
  if (!rawId) return null
  const needle = String(rawId).trim()
  const lower = needle.toLowerCase()
  const list = getPhysicalList()
  return list.find(p =>
    String(p.id || '').trim() === needle ||
    (p.sku && String(p.sku).trim().toLowerCase() === lower) ||
    (p.pageSlug && String(p.pageSlug).trim().toLowerCase() === lower)
  ) || null
}

function parseArsNum(val) {
  if (typeof val === 'number' && Number.isFinite(val)) return val
  return Number(String(val || '').replace(/[^0-9]/g, '')) || 0
}

function formatGameItem(g, lic = 'primary') {
  const pvpPri = Number(g.effectivePrimaryPrice || 25000)
  const platLower = String(g.platform || '').toLowerCase()
  const supportsSec = Boolean(g.effectiveSecondaryPrice) && !platLower.includes('pc') && !platLower.includes('steam') && !platLower.includes('ps3')
  const pvpSec = supportsSec ? Number(g.effectiveSecondaryPrice) : 0
  const chosenLic = lic === 'secondary' && pvpSec > 0 ? 'secondary' : 'primary'
  const mainPrice = chosenLic === 'secondary' ? pvpSec : pvpPri

  return {
    type: 'gaming',
    id: g.id,
    title: g.title,
    platform: g.platform || 'PS4 / PS5',
    category: g.category || 'Videojuegos Digitales',
    licenseType: chosenLic,
    priceNumeric: mainPrice,
    priceStr: `$${mainPrice.toLocaleString('es-AR')} ARS`,
    primaryPrice: pvpPri,
    secondaryPrice: pvpSec,
    supportsSecondary: supportsSec,
    image: normalizeImageUrl(g.image)
  }
}

function formatPhysicalItem(p) {
  const priceNum = parseArsNum(p.priceNumeric || p.price)
  const origNum = p.hasDiscount ? parseArsNum(p.originalPriceNumeric || p.originalPrice) : 0
  return {
    type: 'physical',
    id: p.id,
    title: p.name,
    platform: p.categoryName || 'Tienda Física',
    category: p.categoryName || 'Tecnología & Bazar',
    priceNumeric: priceNum,
    priceStr: p.price || `$${priceNum.toLocaleString('es-AR')} ARS`,
    originalPriceNumeric: origNum > priceNum ? origNum : 0,
    discountPct: Number(p.discountPercentage || 0),
    description: String(p.description || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim(),
    image: normalizeImageUrl(p.image || (Array.isArray(p.images) ? p.images[0] : ''))
  }
}

export async function resolvePreviewPayload(query = {}) {
  const refCode = String(query.ref || '').trim()
  const licParam = String(query.lic || 'primary').toLowerCase() === 'secondary' ? 'secondary' : 'primary'
  const prodId = String(query.prod || query.id || query.p || '').trim()
  const gameId = String(query.game || query.g || '').trim()
  const pkgId = String(query.pkg || '').trim()
  const platId = String(query.plat || '').trim()
  const gamesCsv = String(query.games || '').trim()
  const prodsCsv = String(query.prods || '').trim()
  const isMulti = Boolean(query.multi === '1' || gamesCsv || prodsCsv)
  const sec = String(query.sec || '').trim()

  // 1. Selección Múltiple explícita (?games=id1,id2... o ?prods=id1,id2...)
  if (isMulti && (gamesCsv || prodsCsv)) {
    const items = []
    if (gamesCsv) {
      const uniqueGameIds = [...new Set(gamesCsv.split(',').map(s => s.trim()).filter(Boolean))].slice(0, 4)
      for (const gid of uniqueGameIds) {
        const g = findGameByIdOrSlug(gid)
        if (g) items.push(formatGameItem(g, licParam))
      }
    }
    if (prodsCsv && items.length < 4) {
      const uniqueProdIds = [...new Set(prodsCsv.split(',').map(s => s.trim()).filter(Boolean))].slice(0, 4 - items.length)
      for (const pid of uniqueProdIds) {
        const p = findPhysicalByIdOrSlug(pid)
        if (p) items.push(formatPhysicalItem(p))
      }
    }

    if (items.length === 1) {
      const only = items[0]
      if (only.type === 'gaming') {
        return buildSingleGamingPayload(only, refCode)
      }
      return buildSinglePhysicalPayload(only, refCode)
    }

    if (items.length > 1) {
      const isPhysicalGroup = items.every(i => i.type === 'physical')
      const customHeadline = String(query.title || '').trim().slice(0, 68)
      const headline = customHeadline || (isPhysicalGroup
        ? `Selección Destacada (${items.length} Productos)`
        : `Top Selección Gamer (${items.length} Títulos)`)
      const queryParams = new URLSearchParams()
      if (gamesCsv) queryParams.set('games', gamesCsv)
      if (prodsCsv) queryParams.set('prods', prodsCsv)
      if (refCode) queryParams.set('ref', refCode)
      if (customHeadline) queryParams.set('title', customHeadline)

      const redirectParams = new URLSearchParams()
      redirectParams.set('sec', isPhysicalGroup ? 'physical' : 'gaming')
      if (gamesCsv) redirectParams.set('games', gamesCsv)
      if (prodsCsv) redirectParams.set('prods', prodsCsv)
      if (refCode) redirectParams.set('ref', refCode)

      return {
        mode: 'multi',
        type: isPhysicalGroup ? 'physical' : 'gaming',
        refCode,
        headline,
        subtitle: `${items.length} productos oficiales con garantía StreamePro y entrega garantizada.`,
        badge: isPhysicalGroup ? 'TIENDA OFICIAL' : 'SELECCIÓN GAMER',
        items,
        canonicalUrl: `https://streamepro.me/seleccion?${queryParams.toString()}`,
        redirectUrl: `/?${redirectParams.toString()}`
      }
    }
  }

  // 2. Videojuego individual
  if (gameId) {
    const foundGame = findGameByIdOrSlug(gameId)
    if (foundGame) {
      return buildSingleGamingPayload(formatGameItem(foundGame, licParam), refCode)
    }
  }

  // 3. Producto físico individual
  if (prodId) {
    let foundProd = findPhysicalByIdOrSlug(prodId)
    if (!foundProd) {
      try {
        const res = await fetch('https://streamepro.me/api/sync-revende', { signal: AbortSignal.timeout(2500) })
        if (res.ok) {
          const data = await res.json()
          foundProd = (data.products || []).find(p => String(p.id).trim() === prodId) || null
        }
      } catch {}
    }
    if (foundProd) {
      return buildSinglePhysicalPayload(formatPhysicalItem(foundProd), refCode)
    }
  }

  // 4. Combo / Paquete Streaming
  if (pkgId) {
    const cleanPkgId = pkgId.toUpperCase()
    const matchedKey = Object.keys(DEFAULT_PACKAGES_META).find(k => k.toUpperCase() === cleanPkgId)
    const pkgMeta = matchedKey ? DEFAULT_PACKAGES_META[matchedKey] : {
      id: pkgId,
      title: String(query.title || 'Combo Streaming Oficial Multi-Plataforma').slice(0, 72),
      description: 'Perfiles privados con PIN, calidad 4K HDR y garantía oficial por 30 días.',
      badge: '🎁 COMBO VIP',
      originalPrice: parseArsNum(query.orig) || 0,
      promoPrice: parseArsNum(query.price) || 15900,
      services: [
        { platform: 'Netflix', planLabel: '4K HDR' },
        { platform: 'Disney+', planLabel: 'VIP' },
        { platform: 'Max', planLabel: 'Platino' }
      ]
    }
    const priceStr = `$${Number(pkgMeta.promoPrice || 0).toLocaleString('es-AR')} ARS`
    const canonicalPath = `https://streamepro.me/combo/${encodeURIComponent(pkgMeta.id)}${refCode ? `/${encodeURIComponent(refCode)}` : ''}`
    const redirectUrl = `/?sec=packages&pkg=${encodeURIComponent(pkgMeta.id)}${refCode ? `&ref=${encodeURIComponent(refCode)}` : ''}`

    return {
      mode: 'single',
      type: 'package',
      id: pkgMeta.id,
      refCode,
      title: pkgMeta.title,
      badge: pkgMeta.badge || 'COMBO OFICIAL',
      priceNumeric: Number(pkgMeta.promoPrice || 0),
      priceStr,
      originalPriceNumeric: Number(pkgMeta.originalPrice || 0),
      services: pkgMeta.services || [],
      subtitle: pkgMeta.description,
      description: `🎁 ${pkgMeta.title} a solo ${priceStr}. Incluye: ${(pkgMeta.services || []).map(s => s.platform).join(' + ')}. Entrega rápida y garantía oficial StreamePro.`,
      canonicalUrl: canonicalPath,
      redirectUrl
    }
  }

  // 5. Servicio / Plataforma Streaming individual
  if (platId && sec !== 'gaming' && sec !== 'physical') {
    const platKey = Object.keys(STREAMING_PLATFORMS_META).find(k =>
      k.toLowerCase().replace(/[^a-z0-9]/g, '') === platId.toLowerCase().replace(/[^a-z0-9]/g, '')
    )
    if (platKey) {
      const meta = STREAMING_PLATFORMS_META[platKey]
      const minPrice = parseArsNum(query.price) || meta.minPrice
      const priceStr = `Desde $${minPrice.toLocaleString('es-AR')} ARS`
      return {
        mode: 'single',
        type: 'streaming',
        id: platKey,
        platform: platKey,
        refCode,
        title: meta.name,
        badge: meta.tag,
        accentHex: meta.accentHex,
        bgGrad: meta.bgGrad,
        priceNumeric: minPrice,
        priceStr,
        subtitle: meta.description,
        description: `🎬 ${meta.name} — ${priceStr}. ${meta.description} Garantía Oficial StreamePro.`,
        canonicalUrl: `https://streamepro.me/servicio/${encodeURIComponent(platKey)}${refCode ? `/${encodeURIComponent(refCode)}` : ''}`,
        redirectUrl: `/?sec=digital&plat=${encodeURIComponent(platKey)}${refCode ? `&ref=${encodeURIComponent(refCode)}` : ''}`
      }
    }
  }

  // 6. Catálogo General / Portada compartida (?sec=gaming, physical, packages, digital o all)
  const gList = getGamingList()
  const pList = getPhysicalList()
  let featuredItems = []
  let headline = 'StreamePro | Tienda Oficial Gamer & Streaming'
  let sub = 'Videojuegos digitales PlayStation 24/7, suscripciones 4K y productos físicos.'
  let badge = 'TIENDA OFICIAL'
  let targetPath = '/'

  if (sec === 'physical') {
    featuredItems = pList.slice(0, 4).map(formatPhysicalItem)
    headline = 'Catálogo Tecnológico & Hogar | StreamePro'
    sub = `+${pList.length || 300} productos con stock real y envíos a todo el país.`
    badge = 'ENVÍOS A TODO EL PAÍS'
    targetPath = '/tienda'
  } else if (sec === 'packages') {
    const pkgs = Object.values(DEFAULT_PACKAGES_META)
    return {
      mode: 'single',
      type: 'package',
      id: pkgs[0].id,
      refCode,
      title: 'Combos & Packs Streaming 4K StreamePro',
      badge: 'HASTA 25% OFF EN COMBOS',
      priceNumeric: pkgs[0].promoPrice,
      priceStr: `Desde $${pkgs[1].promoPrice.toLocaleString('es-AR')} ARS`,
      services: pkgs[0].services,
      subtitle: 'Combiná Netflix, Disney+, Max y Spotify con perfiles privados con PIN.',
      description: '🎁 Combos Streaming Oficiales StreamePro: Netflix + Disney+ + Max y Dúo Series & Música con ahorro inmediato.',
      canonicalUrl: `https://streamepro.me/combos${refCode ? `?ref=${encodeURIComponent(refCode)}` : ''}`,
      redirectUrl: `/?sec=packages${refCode ? `&ref=${encodeURIComponent(refCode)}` : ''}`
    }
  } else {
    featuredItems = gList.slice(0, 4).map(g => formatGameItem(g, 'primary'))
    headline = sec === 'gaming'
      ? `Catálogo Gamer PS5 & PS4 (+${gList.length} Juegos)`
      : 'StreamePro | Juegos Digitales, Streaming & Tienda Oficial'
    sub = 'Entrega garantizada 60/90 min · Cuentas Primarias y Secundarias Oficiales.'
    badge = 'PLAYSTATION STORE OFICIAL'
    targetPath = sec === 'gaming' ? '/juegos' : '/'
  }

  const refQs = refCode ? `?ref=${encodeURIComponent(refCode)}` : ''
  return {
    mode: featuredItems.length > 1 ? 'multi' : 'catalog',
    type: sec === 'physical' ? 'physical' : 'gaming',
    refCode,
    headline,
    subtitle: sub,
    badge,
    items: featuredItems,
    canonicalUrl: `https://streamepro.me${targetPath}${refQs}`,
    redirectUrl: `/${sec && sec !== 'all' ? `?sec=${encodeURIComponent(sec)}${refCode ? `&ref=${encodeURIComponent(refCode)}` : ''}` : refQs}`
  }
}

function buildSingleGamingPayload(item, refCode) {
  const licLabel = item.licenseType === 'secondary' ? 'Cuenta Secundaria' : 'Cuenta Primaria'
  const otherInfo = item.supportsSecondary
    ? (item.licenseType === 'secondary'
      ? `Primaria $${item.primaryPrice.toLocaleString('es-AR')}`
      : `Secundaria desde $${item.secondaryPrice.toLocaleString('es-AR')}`)
    : 'Licencia Digital Original'
  const licQs = item.licenseType === 'secondary' ? '?lic=secondary' : '?lic=primary'
  const refSegment = refCode ? `/${encodeURIComponent(refCode)}` : ''

  return {
    mode: 'single',
    type: 'gaming',
    id: item.id,
    refCode,
    title: `${item.title} (${item.platform})`,
    shortTitle: item.title,
    badge: `${item.platform} · ${licLabel.toUpperCase()}`,
    priceNumeric: item.priceNumeric,
    priceStr: item.priceStr,
    subPriceStr: otherInfo,
    image: item.image,
    subtitle: `🎮 ${licLabel} · ${otherInfo} · Entrega 60/90 min con garantía oficial`,
    description: `🎮 ${item.title} (${item.platform}) — ${licLabel} ${item.priceStr}${item.supportsSecondary ? ` (${otherInfo})` : ''}. Entrega digital garantizada 60/90 min en StreamePro Oficial.`,
    canonicalUrl: `https://streamepro.me/juego/${encodeURIComponent(item.id)}${refSegment}${licQs}`,
    redirectUrl: `/?game=${encodeURIComponent(item.id)}&lic=${item.licenseType}${refCode ? `&ref=${encodeURIComponent(refCode)}` : ''}`
  }
}

function buildSinglePhysicalPayload(item, refCode) {
  const refSegment = refCode ? `/${encodeURIComponent(refCode)}` : ''
  const discTxt = item.discountPct > 0 ? ` (${item.discountPct}% OFF)` : ''
  return {
    mode: 'single',
    type: 'physical',
    id: item.id,
    refCode,
    title: item.title,
    shortTitle: item.title,
    badge: item.discountPct > 0 ? `${item.platform.toUpperCase()} · ${item.discountPct}% OFF` : `${item.platform.toUpperCase()} · ENVÍO RÁPIDO`,
    priceNumeric: item.priceNumeric,
    priceStr: item.priceStr,
    originalPriceNumeric: item.originalPriceNumeric,
    discountPct: item.discountPct,
    image: item.image,
    subtitle: `🚚 Stock Disponible${discTxt} · Envíos a Todo el País · Garantía Oficial`,
    description: `🛍️ ${item.title} — ${item.priceStr}${discTxt}. ${item.description ? item.description.slice(0, 140) : 'Stock disponible con envíos a todo el país.'} Garantía Oficial StreamePro.`,
    canonicalUrl: `https://streamepro.me/producto/${encodeURIComponent(item.id)}${refSegment}`,
    redirectUrl: `/?prod=${encodeURIComponent(item.id)}&sec=physical${refCode ? `&ref=${encodeURIComponent(refCode)}` : ''}`
  }
}

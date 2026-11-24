import { STREAMING_PLATFORMS_META } from './_catalogLookup.js'
import { STREAMING_BRAND_METADATA, getStreamingBrandMeta } from '../src/services/streamingBrandMeta.js'
import {
  OFFICIAL_COMBOS_CATALOG,
  STORE_STREAMING_UNIT_PRICES,
  calculatePackageFromCatalog
} from '../src/services/officialCombosData.js'
import { fetchStoreSettingsViaRest } from '../src/services/firebase.js'

const STORE_INFO = {
  name: 'StreamePro Oficial',
  tagline: 'Suscripciones Digitales & Videojuegos Argentina',
  domain: 'streamepro.me',
  website: 'https://streamepro.me',
  streamingUrl: 'https://streamepro.me/suscripciones',
  combosUrl: 'https://streamepro.me/combos',
  gamingUrl: 'https://streamepro.me/juegos',
  whatsappPhone: '+54 9 291 565-8321',
  whatsappNumber: '5492915658321',
  whatsappUrl: 'https://wa.me/5492915658321',
  supportHours: 'Lunes a Domingo · 09:00 a 23:00 hs',
  deliveryTime: '⚡ 60-90 min (Entrega Digital Inmediata)',
  paymentMethods: ['Transferencia Bancaria (CBU/CVU)', 'Mercado Pago']
}

function formatArs(amount) {
  const num = Number(amount) || 0
  return `$${num.toLocaleString('es-AR')}`
}

function buildWhatsAppOrderUrl(message) {
  return `https://wa.me/${STORE_INFO.whatsappNumber}?text=${encodeURIComponent(message)}`
}

async function fetchLiveCloudSettings(timeoutMs = 3500) {
  try {
    return await Promise.race([
      fetchStoreSettingsViaRest(),
      new Promise(resolve => setTimeout(() => resolve(null), timeoutMs))
    ])
  } catch {
    return null
  }
}

function buildFallbackPlans(platform) {
  const meta = STREAMING_PLATFORMS_META[platform] || {}
  const unit = STORE_STREAMING_UNIT_PRICES[platform] || {}
  const basePrice = Number(meta.minPrice || unit.unitPrice || 3800)
  const planKey = platform === 'Netflix Perfil Extra' ? 'perfil_extra' : '1_perfil'
  const label = platform === 'Netflix Perfil Extra'
    ? '1 Perfil Extra (Sin Bloqueo de Hogar)'
    : '1 Perfil / 1 Dispositivo'

  return {
    [planKey]: {
      label,
      units: 1,
      type: platform === 'Netflix Perfil Extra' ? 'perfil_extra' : 'perfil',
      clientPrice: basePrice
    }
  }
}

function normalizeEffectivePricingCatalog(cloudCatalog = {}) {
  const catalog = {}

  Object.keys(STREAMING_PLATFORMS_META).forEach(plat => {
    const meta = STREAMING_PLATFORMS_META[plat]
    catalog[plat] = {
      name: meta.name || plat,
      badge: meta.tag,
      description: meta.description,
      plans: buildFallbackPlans(plat)
    }
  })

  if (cloudCatalog && typeof cloudCatalog === 'object') {
    Object.keys(cloudCatalog).forEach(plat => {
      if (plat === 'Apple TV+') return
      const cloudEntry = cloudCatalog[plat]
      if (!cloudEntry || typeof cloudEntry !== 'object') return
      const existing = catalog[plat] || {}
      catalog[plat] = {
        ...existing,
        ...cloudEntry,
        plans: cloudEntry.plans && Object.keys(cloudEntry.plans).length > 0
          ? { ...cloudEntry.plans }
          : (existing.plans || buildFallbackPlans(plat))
      }
    })
  }

  delete catalog['Apple TV+']

  // Garantizar que Netflix Perfil Extra solo tenga 1 dispositivo ($10.000 si no fue editado a otro valor distinto)
  if (catalog['Netflix Perfil Extra']) {
    const npePlans = catalog['Netflix Perfil Extra'].plans || {}
    if (npePlans['2_perfiles_extra']) {
      delete npePlans['2_perfiles_extra']
    }
    const pExtra = npePlans.perfil_extra || buildFallbackPlans('Netflix Perfil Extra').perfil_extra
    if (!pExtra.clientPrice || pExtra.clientPrice === 6500 || pExtra.clientPrice === 5500) {
      pExtra.clientPrice = 10000
    }
    pExtra.units = 1
    catalog['Netflix Perfil Extra'].plans = { perfil_extra: pExtra }
  }

  return catalog
}

export default async function handler(req, res) {
  res.setHeader('Access-Control-Allow-Credentials', 'true')
  res.setHeader('Access-Control-Allow-Origin', '*')
  res.setHeader('Access-Control-Allow-Methods', 'GET, OPTIONS')
  res.setHeader('Access-Control-Allow-Headers', 'X-CSRF-Token, X-Requested-With, Accept, Accept-Version, Content-Length, Content-MD5, Content-Type, Date, X-Api-Version')
  res.setHeader('Content-Type', 'application/json; charset=utf-8')
  res.setHeader('Cache-Control', 'public, max-age=30, s-maxage=60, stale-while-revalidate=120')

  if (req.method === 'OPTIONS') {
    return res.status(200).end()
  }

  try {
    const cloudSettings = await fetchLiveCloudSettings()
    const hasLiveFirebase = Boolean(cloudSettings && (cloudSettings.pricingCatalog || cloudSettings.packages))
    const effectiveCatalog = normalizeEffectivePricingCatalog(cloudSettings?.pricingCatalog || {})

    // Ordenar mostrando primero las plataformas oficiales y luego cualquier personalizada en nube
    const orderedPlatforms = [
      ...Object.keys(STREAMING_PLATFORMS_META).filter(k => Boolean(effectiveCatalog[k])),
      ...Object.keys(effectiveCatalog).filter(k => !STREAMING_PLATFORMS_META[k])
    ]

    // 1. Compilar catálogo de Streaming
    const streamingList = orderedPlatforms.map(platform => {
      const catEntry = effectiveCatalog[platform] || {}
      const meta = STREAMING_PLATFORMS_META[platform] || {}
      const brand = STREAMING_BRAND_METADATA[platform] || getStreamingBrandMeta(platform) || {}
      const unit = STORE_STREAMING_UNIT_PRICES[platform] || {}

      const plans = Object.entries(catEntry.plans || buildFallbackPlans(platform)).map(([key, plan]) => {
        const planPrice = Number(plan?.clientPrice || meta.minPrice || unit.unitPrice || 3800)
        return {
          key,
          label: plan?.label || key,
          units: Number(plan?.units || 1),
          price: planPrice,
          formattedPrice: `${formatArs(planPrice)} ARS`
        }
      })

      const planPrices = plans.map(p => p.price).filter(n => Number.isFinite(n) && n > 0)
      const price = planPrices.length > 0 ? Math.min(...planPrices) : (meta.minPrice || unit.unitPrice || 3800)
      const formattedPrice = formatArs(price)
      const displayName = catEntry.name || meta.name || unit.name || platform
      const directUrl = `${STORE_INFO.streamingUrl}?plat=${encodeURIComponent(platform)}`
      const orderMessage = `¡Hola StreamePro! 👋 Quisiera solicitar la suscripción de *${displayName}* (${formattedPrice} ARS). ¿Tienen disponibilidad inmediata?`

      return {
        id: platform.toLowerCase().replace(/[^a-z0-9]+/g, '-'),
        platform,
        name: displayName,
        price,
        formattedPrice: `${formattedPrice} ARS`,
        currency: 'ARS',
        plans,
        badge: catEntry.badge || brand.badge || meta.tag || '⭐ OFICIAL',
        delivery: STORE_INFO.deliveryTime,
        warranty: catEntry.warranty || brand.warranty || 'Garantía Oficial StreamePro',
        description: catEntry.description || meta.description || `Suscripción oficial a ${displayName} con perfiles individuales y PIN privado.`,
        highlights: (Array.isArray(catEntry.highlights) && catEntry.highlights.length > 0)
          ? catEntry.highlights
          : (brand.highlights || [
              'Entrega rápida garantizada 60-90 min',
              'PIN privado de 4 dígitos',
              'Garantía total StreamePro'
            ]),
        url: directUrl,
        orderUrl: buildWhatsAppOrderUrl(orderMessage)
      }
    })

    // 2. Compilar catálogo de Combos
    const rawCombos = (Array.isArray(cloudSettings?.packages) && cloudSettings.packages.length > 0)
      ? cloudSettings.packages
      : (OFFICIAL_COMBOS_CATALOG || [])

    const combosList = rawCombos.map(rawPkg => {
      const combo = rawPkg?.isManualPrice
        ? rawPkg
        : calculatePackageFromCatalog(rawPkg, effectiveCatalog)
      const originalPrice = Number(combo.originalPrice || 0)
      const promoPrice = Number(combo.promoPrice || 0)
      const savings = Math.max(0, originalPrice - promoPrice)
      const discountPercent = combo.discountPercent || (originalPrice > 0 ? Math.round((savings / originalPrice) * 100) : 0)
      const directUrl = `${STORE_INFO.combosUrl}?combo=${encodeURIComponent(combo.id)}`
      const orderMessage = `¡Hola StreamePro! 👋 Me interesa el combo *${combo.title}* (${formatArs(promoPrice)} ARS). ¿Cómo puedo abonar?`

      return {
        id: combo.id,
        sku: combo.sku || combo.id,
        title: combo.title,
        badge: combo.badge || 'COMBO RECOMENDADO',
        category: combo.category || 'streaming',
        promoPrice,
        formattedPromoPrice: `${formatArs(promoPrice)} ARS`,
        originalPrice,
        formattedOriginalPrice: `${formatArs(originalPrice)} ARS`,
        savings,
        formattedSavings: `${formatArs(savings)} ARS`,
        discountPercent: `${discountPercent}%`,
        services: (combo.services || []).map(s => ({
          platform: s.platform || s,
          plan: s.planLabel || 'Oficial',
          units: s.units || 1
        })),
        url: directUrl,
        orderUrl: buildWhatsAppOrderUrl(orderMessage)
      }
    })

    // 3. Generar textos pre-formateados listos para responder en Telegram Bot (< 4096 caracteres por límite oficial de Telegram)
    const telegramStreamingText = [
      `🎬 *CATÁLOGO OFICIAL DE STREAMING STREAMEPRO*`,
      `_Activación en 60-90 min · Perfiles privados con PIN · Garantía total_\n`,
      ...streamingList.map(s => `• *${s.name}* · Desde ${s.formattedPrice}\n  _${s.badge}_ · [Ver / Pedir](${s.url})`),
      `\n🔗 *Ver catálogo web completo:* ${STORE_INFO.streamingUrl}`,
      `📲 *Atención WhatsApp:* ${STORE_INFO.whatsappUrl}`
    ].join('\n')

    const telegramCombosText = [
      `🎁 *COMBOS Y PROMOS DESTACADAS STREAMEPRO*`,
      `_Ahorrá hasta un 45% combinando tus plataformas favoritas_\n`,
      ...combosList.slice(0, 15).map(c => `• *${c.title}* · ${c.formattedPromoPrice} ~(${c.formattedOriginalPrice})~\n  _${c.services.map(s => s.platform).join(' + ')} · ${c.discountPercent} OFF_ · [Pedir](${c.url})`),
      `\n🔗 *Ver los ${combosList.length} combos completos:* ${STORE_INFO.combosUrl}`,
      `📲 *Atención WhatsApp:* ${STORE_INFO.whatsappUrl}`
    ].join('\n')

    const telegramFullCatalogText = [
      `🌟 *BIENVENIDO A STREAMEPRO OFICIAL*`,
      `Tu proveedor líder de Suscripciones 4K, Combos y Videojuegos.\n`,
      `🎬 *Suscripciones individuales:* ${streamingList.length} opciones disponibles.`,
      `🎁 *Combos especiales:* ${combosList.length} combos con descuento.`,
      `⚡ *Tiempo de entrega:* 60-90 min.\n`,
      `👇 *Opciones disponibles:*`,
      `1️⃣ /streaming - Ver listado de streaming y precios`,
      `2️⃣ /combos - Ver promociones y paquetes múltiples`,
      `3️⃣ /contacto - Hablar con un asesor oficial\n`,
      `🌐 *Sitio Web Oficial:* ${STORE_INFO.website}`,
      `📲 *WhatsApp Directo:* ${STORE_INFO.whatsappUrl}`
    ].join('\n')

    // 4. Filtro por parámetro query (type: streaming | combos | telegram)
    const reqType = String(req.query.type || req.query.category || '').toLowerCase()
    const reqFormat = String(req.query.format || '').toLowerCase()

    if (reqFormat === 'telegram' || reqType === 'telegram') {
      return res.status(200).json({
        ok: true,
        source: hasLiveFirebase ? 'firebase-live' : 'catalog-fallback',
        store: STORE_INFO,
        telegram: {
          welcome: telegramFullCatalogText,
          streaming: telegramStreamingText,
          combos: telegramCombosText
        }
      })
    }

    if (reqType === 'streaming') {
      return res.status(200).json({
        ok: true,
        source: hasLiveFirebase ? 'firebase-live' : 'catalog-fallback',
        store: STORE_INFO,
        summary: {
          total: streamingList.length,
          category: 'streaming'
        },
        streaming: streamingList,
        telegramText: telegramStreamingText
      })
    }

    if (reqType === 'combos' || reqType === 'packages') {
      return res.status(200).json({
        ok: true,
        source: hasLiveFirebase ? 'firebase-live' : 'catalog-fallback',
        store: STORE_INFO,
        summary: {
          total: combosList.length,
          category: 'combos'
        },
        combos: combosList,
        telegramText: telegramCombosText
      })
    }

    return res.status(200).json({
      ok: true,
      source: hasLiveFirebase ? 'firebase-live' : 'catalog-fallback',
      timestamp: new Date().toISOString(),
      store: STORE_INFO,
      summary: {
        totalStreaming: streamingList.length,
        totalCombos: combosList.length,
        deliveryTime: STORE_INFO.deliveryTime,
        currency: 'ARS'
      },
      streaming: streamingList,
      combos: combosList,
      telegram: {
        welcome: telegramFullCatalogText,
        streaming: telegramStreamingText,
        combos: telegramCombosText
      }
    })
  } catch (error) {
    console.error('Error in /api/services endpoint:', error)
    return res.status(500).json({
      ok: false,
      error: 'Error interno al generar catálogo de servicios',
      message: error?.message || String(error)
    })
  }
}

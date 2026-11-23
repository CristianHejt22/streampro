import { STREAMING_PLATFORMS_META } from './_catalogLookup.js'
import { STREAMING_BRAND_METADATA } from '../src/services/streamingBrandMeta.js'
import { OFFICIAL_COMBOS_CATALOG, STORE_STREAMING_UNIT_PRICES } from '../src/services/officialCombosData.js'

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

export default async function handler(req, res) {
  // CORS Headers
  res.setHeader('Access-Control-Allow-Credentials', 'true')
  res.setHeader('Access-Control-Allow-Origin', '*')
  res.setHeader('Access-Control-Allow-Methods', 'GET, OPTIONS')
  res.setHeader('Access-Control-Allow-Headers', 'X-CSRF-Token, X-Requested-With, Accept, Accept-Version, Content-Length, Content-MD5, Content-Type, Date, X-Api-Version')
  res.setHeader('Content-Type', 'application/json; charset=utf-8')
  res.setHeader('Cache-Control', 'public, max-age=60, s-maxage=120')

  if (req.method === 'OPTIONS') {
    return res.status(200).end()
  }

  try {
    // 1. Compilar catálogo de Streaming
    const streamingList = Object.keys(STREAMING_PLATFORMS_META).map(platform => {
      const meta = STREAMING_PLATFORMS_META[platform] || {}
      const brand = STREAMING_BRAND_METADATA[platform] || {}
      const unit = STORE_STREAMING_UNIT_PRICES[platform] || {}
      const price = meta.minPrice || unit.unitPrice || 3800
      const formattedPrice = formatArs(price)
      const directUrl = `${STORE_INFO.streamingUrl}?plat=${encodeURIComponent(platform)}`
      const orderMessage = `¡Hola StreamePro! 👋 Quisiera solicitar la suscripción de *${platform}* (${formattedPrice} ARS). ¿Tienen disponibilidad inmediata?`

      return {
        id: platform.toLowerCase().replace(/[^a-z0-9]+/g, '-'),
        platform,
        name: meta.name || unit.name || platform,
        price,
        formattedPrice: `${formattedPrice} ARS`,
        currency: 'ARS',
        badge: brand.badge || meta.tag || '⭐ OFICIAL',
        delivery: STORE_INFO.deliveryTime,
        warranty: brand.warranty || 'Garantía Oficial StreamePro',
        description: meta.description || `Suscripción oficial a ${platform} con perfiles individuales y PIN privado.`,
        highlights: brand.highlights || [
          'Entrega rápida garantizada 60-90 min',
          'PIN privado de 4 dígitos',
          'Garantía total StreamePro'
        ],
        url: directUrl,
        orderUrl: buildWhatsAppOrderUrl(orderMessage)
      }
    })

    // 2. Compilar catálogo de Combos
    const combosList = (OFFICIAL_COMBOS_CATALOG || []).map(combo => {
      const originalPrice = combo.originalPrice || 0
      const promoPrice = combo.promoPrice || 0
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

    // 3. Generar textos pre-formateados listos para responder en Telegram Bot
    const telegramStreamingText = [
      `🎬 *CATÁLOGO OFICIAL DE STREAMING STREAMEPRO*`,
      `_Activación en 60-90 min · Perfiles privados con PIN · Garantía total_\n`,
      ...streamingList.map(s => `• *${s.platform}* · ${s.formattedPrice}\n  _${s.badge}_ · [Pedir por WhatsApp](${s.orderUrl})`),
      `\n🔗 *Ver catálogo web completo:* ${STORE_INFO.streamingUrl}`,
      `📲 *Atención WhatsApp:* ${STORE_INFO.whatsappPhone}`
    ].join('\n')

    const telegramCombosText = [
      `🎁 *COMBOS Y PROMOS DESTACADAS STREAMEPRO*`,
      `_Ahorrá hasta un 45% combinando tus plataformas favoritas_\n`,
      ...combosList.slice(0, 15).map(c => `• *${c.title}* · ${c.formattedPromoPrice} ~(${c.formattedOriginalPrice})~\n  _${c.services.map(s => s.platform).join(' + ')} · ${c.discountPercent} OFF_\n  [Pedir este combo](${c.orderUrl})`),
      `\n🔗 *Ver los 32 combos completos:* ${STORE_INFO.combosUrl}`,
      `📲 *Atención WhatsApp:* ${STORE_INFO.whatsappPhone}`
    ].join('\n')

    const telegramFullCatalogText = [
      `🌟 *BIENVENIDO A STREAMEPRO OFICIAL*`,
      `Tu proveedor líder de Suscripciones 4K, Combos y Videojuegos.\n`,
      `🎬 *Suscripciones individuales:* ${streamingList.length} plataformas disponibles.`,
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
        store: STORE_INFO,
        summary: {
          total: combosList.length,
          category: 'combos'
        },
        combos: combosList,
        telegramText: telegramCombosText
      })
    }

    // Respuesta integral por defecto
    return res.status(200).json({
      ok: true,
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

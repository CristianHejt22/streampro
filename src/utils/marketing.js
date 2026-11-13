// Utilidades formales de Marketing, Sorteos y Usuarios Descuento (StreamePro Oficial)

export function normalizePhoneDigits(val) {
  return String(val || '')
    .replace(/[^0-9]/g, '')
    .replace(/^549?/, '')
    .replace(/^0/, '')
}

export function maskPhonePublic(phone) {
  const digits = String(phone || '').replace(/[^0-9]/g, '')
  if (digits.length < 4) return '****'
  return `***-${digits.slice(-4)}`
}

export function normalizePromoCode(raw) {
  return String(raw || '')
    .trim()
    .toUpperCase()
    .replace(/[^A-Z0-9_-]/g, '')
}

export function getCartItemCategory(item) {
  if (!item) return 'streaming'
  if (item.itemType === 'gaming' || item.gameId || item.licenseType) return 'gaming'
  if (item.itemType === 'physical') return 'physical'
  if (item.itemType === 'package') return 'packages'
  return 'streaming'
}

export const CATEGORY_LABELS = {
  all: 'Toda la Tienda',
  streaming: 'Streaming 4K',
  gaming: 'Juegos Digitales',
  packages: 'Combos y Packs',
  physical: 'Tienda Física'
}

/**
 * Calcula el descuento exacto por categoría para cupones simples o Usuarios Descuento con % ajustados.
 */
export function computePromoDiscountBreakdown(coupon, cartItems = [], options = {}) {
  const result = {
    totalDiscount: 0,
    eligibleSubtotal: 0,
    byCategory: {},
    effectivePctByCategory: {},
    cappedByMaxDiscount: false,
    cappedByStackLimit: false
  }

  if (!coupon || coupon.active === false || coupon.status === 'paused' || coupon.status === 'revoked' || coupon.status === 'expired') {
    return result
  }

  const items = Array.isArray(cartItems) ? cartItems : []
  if (items.length === 0) return result

  const volumeDiscount = Math.max(0, Number(options.volumeDiscount || 0))
  const maxCombinedPct = Math.min(80, Math.max(5, Number(options.maxCombinedPct ?? 35)))

  const totalCartSubtotal = items.reduce((acc, it) => acc + (Number(it.clientPrice || 0) * Math.max(1, Number(it.quantity || 1))), 0)
  if (totalCartSubtotal <= 0) return result

  if (coupon.isDiscountUserCode || coupon.discountByCategory) {
    const map = coupon.discountByCategory || {}
    let rawDiscount = 0

    items.forEach((it) => {
      const cat = getCartItemCategory(it)
      const lineTotal = Number(it.clientPrice || 0) * Math.max(1, Number(it.quantity || 1))
      if (lineTotal <= 0) return

      const rawPct = Number(map[cat] !== undefined && map[cat] !== null && map[cat] !== '' ? map[cat] : (map.all ?? coupon.value ?? 0))
      const pct = Math.min(70, Math.max(0, rawPct))
      if (pct <= 0) return

      result.eligibleSubtotal += lineTotal
      result.effectivePctByCategory[cat] = pct
      const itemDisc = Math.round(lineTotal * (pct / 100))
      result.byCategory[cat] = (result.byCategory[cat] || 0) + itemDisc
      rawDiscount += itemDisc
    })

    if (coupon.minOrderAmount && result.eligibleSubtotal < Number(coupon.minOrderAmount)) {
      return { ...result, totalDiscount: 0, byCategory: {} }
    }

    let finalDisc = rawDiscount
    const maxDisc = Number(coupon.maxDiscountAmount || 0)
    if (maxDisc > 0 && finalDisc > maxDisc) {
      finalDisc = maxDisc
      result.cappedByMaxDiscount = true
    }

    const maxAllowedTotal = Math.round(totalCartSubtotal * (maxCombinedPct / 100))
    const availableHeadroom = Math.max(0, maxAllowedTotal - volumeDiscount)
    if (finalDisc > availableHeadroom) {
      finalDisc = availableHeadroom
      result.cappedByStackLimit = true
    }

    result.totalDiscount = Math.min(result.eligibleSubtotal, Math.max(0, Math.round(finalDisc)))
    return result
  }

  // Cupón promocional estándar (promoCodes)
  const scope = coupon.scope || 'all'
  const eligibleSubtotal = items.reduce((acc, it) => {
    const lineTotal = Number(it.clientPrice || 0) * Math.max(1, Number(it.quantity || 1))
    const cat = getCartItemCategory(it)
    if (scope === 'all') return acc + lineTotal
    if (scope === cat) return acc + lineTotal
    return acc
  }, 0)

  result.eligibleSubtotal = eligibleSubtotal
  if (eligibleSubtotal <= 0) return result
  if (coupon.minAmount && eligibleSubtotal < Number(coupon.minAmount)) {
    return { ...result, totalDiscount: 0 }
  }

  const val = Number(coupon.value || 0)
  if (val <= 0) return result

  let rawDiscount = coupon.type === 'fixed' ? val : Math.round(eligibleSubtotal * (val / 100))
  rawDiscount = Math.min(eligibleSubtotal, Math.max(0, rawDiscount))

  const maxDisc = Number(coupon.maxDiscountAmount || 0)
  if (maxDisc > 0 && rawDiscount > maxDisc) {
    rawDiscount = maxDisc
    result.cappedByMaxDiscount = true
  }

  const maxAllowedTotal = Math.round(totalCartSubtotal * (maxCombinedPct / 100))
  const availableHeadroom = Math.max(0, maxAllowedTotal - volumeDiscount)
  if (coupon.type !== 'fixed' && rawDiscount > availableHeadroom) {
    rawDiscount = availableHeadroom
    result.cappedByStackLimit = true
  }

  result.byCategory[scope] = rawDiscount
  result.totalDiscount = Math.min(eligibleSubtotal, Math.max(0, Math.round(rawDiscount)))
  return result
}

/**
 * Calcula la recompensa / crédito para el Usuario Descuento según % ajustado de cada rubro vendido.
 */
export function computeReferrerRewardBreakdown(discountUser, cartItems = [], netRatio = 1) {
  if (!discountUser || discountUser.rewardMode === 'none') {
    return { totalReward: 0, byCategory: {} }
  }

  const rMap = discountUser.rewardByCategory || {}
  const ratio = Math.min(1, Math.max(0.05, Number(netRatio || 1)))
  let totalReward = 0
  const byCategory = {}

  ;(cartItems || []).forEach((it) => {
    const cat = getCartItemCategory(it)
    const rawLine = Number(it.clientPrice || 0) * Math.max(1, Number(it.quantity || 1))
    const paidLine = Math.round(rawLine * ratio)
    const rewardPct = Math.min(40, Math.max(0, Number(rMap[cat] !== undefined && rMap[cat] !== '' ? rMap[cat] : (rMap.all ?? 0))))
    if (rewardPct <= 0 || paidLine <= 0) return
    const rewardAmt = Math.round(paidLine * (rewardPct / 100))
    byCategory[cat] = (byCategory[cat] || 0) + rewardAmt
    totalReward += rewardAmt
  })

  return { totalReward, byCategory }
}

/**
 * Genera Bases y Condiciones formales estructuradas para cualquier sorteo.
 */
export function buildDefaultGiveawayFormalities(giveaway = {}) {
  const title = giveaway.title || 'Sorteo Oficial StreamePro'
  const prize = giveaway.prizeTitle || 'Premio Oficial de la Tienda'
  const endDate = giveaway.endDate || 'fecha de cierre indicada'
  const drawDate = giveaway.drawDateLabel || 'fecha y hora anunciada'
  const maxTickets = Number(giveaway.rules?.maxTicketsPerUser || 25)
  const freeEnabled = giveaway.rules?.freeEntryEnabled !== false
  const purchaseTickets = Number(giveaway.rules?.ticketsPerPurchase || 0)
  const refTickets = Number(giveaway.rules?.ticketsPerReferral || 0)

  const lines = [
    `1. ORGANIZADOR Y VIGENCIA: "${title}" es organizado por StreamePro Oficial (https://streamepro.me). El período de participación cierra el ${endDate} y el sorteo se realizará el ${drawDate}.`,
    `2. PREMIO Y DESTINATARIOS: Se sorteará "${prize}"${giveaway.prizeValue ? ` (valor estimado de referencia $${Number(giveaway.prizeValue).toLocaleString()} ARS)` : ''}. Podrán participar personas mayores de 18 años (o con autorización de padre/tutor) con WhatsApp válido de Argentina.`,
    `3. DINÁMICA DE CHANCES (MÁX. ${maxTickets} POR PERSONA):` +
      (freeEnabled ? ` • Inscripción web gratuita verificada: +${giveaway.rules?.freeEntryTickets || 1} ticket.` : ' • Modalidad exclusiva para compradores verificados.') +
      (purchaseTickets > 0 ? ` • Por cada pedido realizado durante la vigencia: +${purchaseTickets} tickets automáticos.` : '') +
      (refTickets > 0 ? ` • Por cada amigo nuevo verificado que participe con tu enlace: +${refTickets} tickets extra.` : ''),
    `4. TRANSPARENCIA Y DESCALIFICACIÓN: Cada participante recibe un rango correlativo de números verificados. Las inscripciones con números telefónicos inválidos, bots o identidades duplicadas serán anuladas previo al acta de sorteo.`,
    `5. ADJUDICACIÓN Y RECLAMO: La elección se efectúa mediante algoritmo aleatorio ponderado generando un Acta de Verificación Pública. El/los ganador/es serán contactados al WhatsApp registrado y contarán con 72 horas hábiles para reclamar su premio; vencido dicho plazo, se adjudicará al suplente.`
  ]
  return lines.join('\n\n')
}

export function buildDiscountProgramFormalities(config = {}) {
  const maxCombined = Number(config.maxCombinedDiscountPct || 35)
  return [
    '1. NATURALEZA DEL PROGRAMA: Los Usuarios Descuento (Embajadores) disponen de un código alfanumérico único con porcentajes ajustados por categoría para compartir ofertas de StreamePro.',
    '2. PORCENTAJES AJUSTADOS Y TOPES: Cada rubro (Streaming, Videojuegos Digitales, Combos y Tienda Física) posee un porcentaje independiente determinado por Administración para garantizar precios sustentables. El tope máximo combinado de descuentos por carrito es del ' + maxCombined + '%.',
    '3. ACREDITACIÓN DE BENEFICIOS: Toda comisión o crédito generado por compras de referidos permanece en estado "Pendiente" hasta que el pago y la entrega de la orden sean confirmados en el sistema oficial. Órdenes canceladas o impagas no generan crédito.',
    '4. REGLA DE USO LEAL Y FORMALIDAD: Salvo habilitación expresa, el código no es aplicable a autocompras del mismo titular. Queda prohibido difundir promesas falsas, suplantar cuentas oficiales de StreamePro o publicar en sitios de cupones sin autorización.'
  ]
}

export function generateMarketingCopy({
  channel = 'whatsapp', // 'whatsapp' | 'instagram' | 'tiktok' | 'telegram'
  headline = 'StreamePro Oficial',
  subline = '',
  promoCode = '',
  promoSummary = '',
  giveawayTitle = '',
  url = 'https://streamepro.me',
  callToAction = 'Pedí online con garantía oficial y entrega en 60/90 min'
}) {
  const cleanCode = normalizePromoCode(promoCode)
  const promoBlock = cleanCode
    ? (channel === 'instagram' || channel === 'tiktok'
        ? `🎟️ USA EL CÓDIGO: ${cleanCode}${promoSummary ? ` (${promoSummary})` : ''}`
        : `🎟️ *CUPÓN ACTIVO: ${cleanCode}*${promoSummary ? ` — _${promoSummary}_` : ''}`)
    : ''

  const giveawayBlock = giveawayTitle
    ? (channel === 'instagram' || channel === 'tiktok'
        ? `🎉 ¡SORTEO ACTIVO! ${giveawayTitle}`
        : `🎉 *SORTEO OFICIAL EN CURSO:* ${giveawayTitle}`)
    : ''

  if (channel === 'instagram') {
    return [
      `🔥 ${headline.toUpperCase()} 🔥`,
      subline,
      promoBlock,
      giveawayBlock,
      `✅ ${callToAction}`,
      `👇 Link directo en bio / historia:`,
      url,
      '',
      '#StreamePro #StreamingArgentina #PS5Argentina #PS4Argentina #OfertasGamer #Sorteo'
    ].filter(Boolean).join('\n')
  }

  if (channel === 'tiktok') {
    return [
      `🎮⚡ ${headline}`,
      subline || callToAction,
      promoBlock,
      giveawayBlock,
      `👉 Entrá acá: ${url}`,
      '#gamingargentina #ps5 #netflix4k #descuentos #streamepro #sorteogamer'
    ].filter(Boolean).join('\n')
  }

  if (channel === 'telegram') {
    return [
      `⚡ **${headline.toUpperCase()}**`,
      subline ? `${subline}` : '',
      promoBlock ? promoBlock.replace(/\*/g, '**') : '',
      giveawayBlock ? giveawayBlock.replace(/\*/g, '**') : '',
      `✅ ${callToAction}`,
      `🔗 **Acceso Oficial:** ${url}`
    ].filter(Boolean).join('\n\n')
  }

  // WhatsApp default
  return [
    `🔥 *${headline.toUpperCase()}* — *StreamePro Oficial*`,
    subline ? `_${subline}_` : '',
    promoBlock,
    giveawayBlock,
    `✅ ${callToAction}`,
    `👉 *Ingresá acá:* ${url}`
  ].filter(Boolean).join('\n\n')
}

/**
 * Genera un paquete integral de Guiones, Copys y Subtítulos para TikTok, Reels y Estados 9:16
 */
export function generateReelStoryPackage({
  type = 'streaming', // 'streaming' | 'gaming' | 'combo' | 'giveaway' | 'flash'
  title = '',
  planLabel = '',
  promoPrice = 0,
  regularPrice = 0,
  promoCode = '',
  discountPct = 0,
  storeName = 'StreamePro Oficial',
  phone = '',
  url = 'https://streamepro.me',
  isReseller = false
}) {
  const cleanCode = normalizePromoCode(promoCode)
  const priceFormatted = Number(promoPrice || 0) > 0 ? `$${Number(promoPrice).toLocaleString('es-AR')} ARS` : ''
  const regFormatted = Number(regularPrice || 0) > 0 ? `$${Number(regularPrice).toLocaleString('es-AR')} ARS` : ''
  const discountStr = discountPct ? `${discountPct}% OFF` : (cleanCode ? 'DESCUENTO EXCLUSIVO' : 'PRECIO PROMOCIONAL')

  let hook = ''
  let voiceover = ''
  let onScreenText = []
  let hashtags = []

  if (type === 'streaming') {
    hook = `¿Todavía pagás una fortuna por tus suscripciones en Argentina?`
    voiceover = `Dejá de tirar plata todos los meses. En ${storeName} conseguís ${title || 'Streaming 4K'} original con tu propio perfil privado y PIN exclusivo. La entrega es inmediata por WhatsApp con soporte y garantía oficial todo el mes. Si usás el cupón ${cleanCode || 'OFERTA'} te queda en tan solo ${priceFormatted || 'precio mayorista'}.`
    onScreenText = [
      `❌ Pagando de más en plataformas`,
      `✅ ${title || 'Streaming'} 4K Oficial`,
      `🔒 Perfil Privado con PIN`,
      `⚡ Entrega en 10-15 min`,
      priceFormatted ? `💰 Hoy a solo ${priceFormatted}` : `💰 Super Precio`,
      cleanCode ? `🎟️ Cupón: ${cleanCode}` : '📲 Pedilo al link del perfil'
    ]
    hashtags = ['#streamingargentina', '#netflixargentina', '#maxargentina', '#spotifyargentina', '#ahorroargentina', '#streamepro', '#cuentasoficiales']
  } else if (type === 'gaming') {
    hook = `¡No gastes 80 dólares en juegos de PlayStation!`
    voiceover = `Conseguí ${title || 'tu juego favorito'} para PS5 y PS4 en formato digital oficial, para jugar directo desde tu consola sin bloqueos y con activación guiada paso a paso. Entrega al instante con garantía. Aprovechá hoy por solo ${priceFormatted || 'precio especial'}${cleanCode ? ` con el cupón ${cleanCode}` : ''}.`
    onScreenText = [
      `🎮 ${title || 'Videojuegos Digitales'}`,
      `🕹️ PlayStation 5 & PS4 Oficial`,
      `🏆 Jugá desde tu usuario personal`,
      `⚡ Activación Digital Inmediata`,
      priceFormatted ? `🔥 Precio especial: ${priceFormatted}` : '🔥 Gran Oferta Gamer',
      cleanCode ? `🎟️ Cupón: ${cleanCode}` : '📲 Link en bio / mensaje directo'
    ]
    hashtags = ['#ps5argentina', '#ps4argentina', '#gamingargentina', '#playstationargentina', '#juegosdigitales', '#ofertasgamer']
  } else if (type === 'combo') {
    hook = `El combo definitivo de entretenimiento que no sabías que existía.`
    voiceover = `Armamos este mega combo con tus plataformas favoritas juntas por una fracción de lo que pagarías por separado. Todo 100% privado, oficial y con entrega ultra-rápida. Pedilo hoy a solo ${priceFormatted || 'un super precio'}.`
    onScreenText = [
      `🍿 Mega Combo Entretenimiento`,
      `✨ Múltiples plataformas en 1 solo pack`,
      `💸 Ahorrá hasta 40%`,
      `⚡ Entrega inmediata por WhatsApp`,
      priceFormatted ? `🔥 Solo ${priceFormatted}` : '🔥 Ahorro Garantizado',
      cleanCode ? `🎟️ Código: ${cleanCode}` : '📲 Escribinos al WhatsApp'
    ]
    hashtags = ['#combostreaming', '#ahorro', '#argentinapromos', '#netflixyrelax', '#seriesypelis', '#streamepro']
  } else if (type === 'giveaway') {
    hook = `¡Estamos sorteando una cuenta oficial gratis y participar te lleva 10 segundos!`
    voiceover = `En ${storeName} lanzamos un sorteo oficial para toda la comunidad. Solo tenés que registrar tu número gratis en nuestro sitio y ya estás participando por ${title || 'premios increíbles'}. El sorteo es transparente con acta criptográfica pública. ¡No te quedes afuera!`
    onScreenText = [
      `🎉 SORTEO OFICIAL ACTIVO`,
      `🎁 Premio: ${title || 'Cuenta Oficial'}`,
      `🎟️ Participación 100% Gratuita`,
      `🔒 Sorteo transparente con acta pública`,
      `👉 Registrate en el link de bio / historia`
    ]
    hashtags = ['#sorteoargentina', '#sorteogamer', '#sorteostreaming', '#sorteogratis', '#argentina', '#streamepro']
  } else {
    hook = `¡Aprovechá esta oportunidad antes de que se agoten los cupos!`
    voiceover = `En ${storeName} tenemos disponible ${title || 'este servicio'} con precio promocional por tiempo limitado. Cuentas privadas y garantizadas con soporte en Argentina. Pedí la tuya ahora.`
    onScreenText = [
      `🔥 OFERTA EXCLUSIVA`,
      `⚡ ${title || 'Servicio Oficial'}`,
      `💎 ${discountStr}`,
      priceFormatted ? `💰 Precio: ${priceFormatted}` : '💰 Oferta Imperdible',
      cleanCode ? `🎟️ Código: ${cleanCode}` : '📲 Pedilo al WhatsApp'
    ]
    hashtags = ['#ofertasargentina', '#descuentos', '#promociones', '#streamepro']
  }

  const cta = cleanCode
    ? `📲 Pedilo al WhatsApp o tocá el link del perfil ingresando el cupón *${cleanCode}* para congelar la oferta.`
    : `📲 Escribinos por WhatsApp o visitá ${url} para activar tu cuenta hoy mismo.`

  const caption = [
    `🔥 ${(title || 'OFERTA ESPECIAL').toUpperCase()} — ${discountStr} 🔥`,
    '',
    `⚡ ${onScreenText.slice(1, 4).join('\n⚡ ')}`,
    '',
    priceFormatted ? `💰 *Precio Promocional:* ${priceFormatted}${regFormatted ? ` (Antes ~${regFormatted}~)` : ''}` : '',
    cleanCode ? `🎟️ *CUPÓN ACTIVO:* ${cleanCode}` : '',
    '',
    cta,
    '',
    hashtags.join(' ')
  ].filter(Boolean).join('\n')

  return {
    type,
    title,
    hook,
    voiceover,
    onScreenText,
    cta,
    caption,
    hashtags,
    soundTip: '💡 Consejo TikTok/Reels: Usa un audio viral en tendencia a volumen bajo (5-10%) de fondo y leé el guión con voz enérgica o activa la voz de IA de TikTok.'
  }
}

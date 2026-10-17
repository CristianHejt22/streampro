// Servicio de Reconocimiento y Conciliación Automática de Transferencias Bancarias
// Analiza notificaciones por correo de Mercado Pago, Galicia, Santander, BBVA, Brubank, Ualá, Cuenta DNI, Lemon, etc.
// Estilo Wanderlust Web Design / Notificador de Transferencias

export const BANK_CONFIGS = [
  {
    id: 'mercadopago',
    name: 'Mercado Pago',
    icon: '💙',
    senderKeywords: ['mercadopago', 'mercado pago'],
    extract: (text, subject = '') => {
      let amount = null
      let sender = 'Desconocido'
      let operationId = null

      // Monto: "Te transfirieron $ 7.700" / "Recibiste $ 7.700,00" / "Ingresaron $ 7.700"
      const combined = `${subject}\n${text}`
      const amountMatch = combined.match(/(?:transfirieron|recibiste|enviaron|ingresaron|importe|monto|total)[\s:]*\$?\s*([0-9]{1,3}(?:\.[0-9]{3})*(?:,[0-9]{1,2})?|[0-9]+(?:\.[0-9]{1,2})?)/i)
      if (amountMatch) {
        amount = parseAmountNumber(amountMatch[1])
      }

      // Remitente: "Carlos Perez te transfirió" / "De: Carlos Perez" / "Fulano te envió"
      const senderMatch = combined.match(/([A-Za-zÁ-ÿ\s]{3,35})\s+(?:te transfiri[oó]|te envi[oó]|te acredit[oó])/i) ||
                          combined.match(/(?:De|Ordenante|Remitente|De parte de|Titular)[\s:]+([A-Za-zÁ-ÿ\s]{3,35})(?:\n|<|$)/i)
      if (senderMatch && senderMatch[1]) {
        sender = cleanSenderName(senderMatch[1])
      }

      // N° Operación
      const opMatch = combined.match(/(?:Operaci[oó]n|Comprobante|Transferencia|Referencia|Id)[\s#n°:]*([0-9]{6,15})/i)
      if (opMatch) {
        operationId = opMatch[1]
      }

      return { amount, sender, operationId }
    }
  },
  {
    id: 'galicia',
    name: 'Banco Galicia',
    icon: '🧡',
    senderKeywords: ['galicia', 'bancogalicia'],
    extract: (text, subject = '') => {
      const combined = `${subject}\n${text}`
      let amount = null
      let sender = 'Desconocido'
      let operationId = null

      const amountMatch = combined.match(/(?:Importe|Monto|Acreditaci[oó]n|Total)[\s:]*\$?\s*([0-9]{1,3}(?:\.[0-9]{3})*(?:,[0-9]{1,2})?|[0-9]+(?:\.[0-9]{1,2})?)/i)
      if (amountMatch) amount = parseAmountNumber(amountMatch[1])

      const senderMatch = combined.match(/(?:Ordenante|Origen|De)[\s:]+([A-Za-zÁ-ÿ\s]{3,35})/i)
      if (senderMatch) sender = cleanSenderName(senderMatch[1])

      const opMatch = combined.match(/(?:Comprobante|Referencia|Operaci[oó]n)[\s#n°:]*([0-9]{6,15})/i)
      if (opMatch) operationId = opMatch[1]

      return { amount, sender, operationId }
    }
  },
  {
    id: 'santander',
    name: 'Banco Santander',
    icon: '🔴',
    senderKeywords: ['santander'],
    extract: (text, subject = '') => {
      const combined = `${subject}\n${text}`
      let amount = null
      let sender = 'Desconocido'
      let operationId = null

      const amountMatch = combined.match(/(?:recibiste una transferencia por|importe|monto)[\s:]*\$?\s*([0-9]{1,3}(?:\.[0-9]{3})*(?:,[0-9]{1,2})?|[0-9]+(?:\.[0-9]{1,2})?)/i)
      if (amountMatch) amount = parseAmountNumber(amountMatch[1])

      const senderMatch = combined.match(/(?:origen|ordenante|de)[\s:]+([A-Za-zÁ-ÿ\s]{3,35})/i)
      if (senderMatch) sender = cleanSenderName(senderMatch[1])

      const opMatch = combined.match(/(?:comprobante|operaci[oó]n|n[uú]mero)[\s#n°:]*([0-9]{6,15})/i)
      if (opMatch) operationId = opMatch[1]

      return { amount, sender, operationId }
    }
  },
  {
    id: 'brubank',
    name: 'Brubank',
    icon: '💜',
    senderKeywords: ['brubank'],
    extract: (text, subject = '') => {
      const combined = `${subject}\n${text}`
      let amount = null
      let sender = 'Desconocido'
      let operationId = null

      const amountMatch = combined.match(/(?:recibiste|te transfirieron|monto)[\s:]*\$?\s*([0-9]{1,3}(?:\.[0-9]{3})*(?:,[0-9]{1,2})?|[0-9]+(?:\.[0-9]{1,2})?)/i)
      if (amountMatch) amount = parseAmountNumber(amountMatch[1])

      const senderMatch = combined.match(/de\s+([A-Za-zÁ-ÿ\s]{3,35})/i)
      if (senderMatch) sender = cleanSenderName(senderMatch[1])

      const opMatch = combined.match(/(?:operaci[oó]n|referencia|id)[\s#n°:]*([0-9A-Za-z]{6,15})/i)
      if (opMatch) operationId = opMatch[1]

      return { amount, sender, operationId }
    }
  },
  {
    id: 'uala',
    name: 'Ualá',
    icon: '🟣',
    senderKeywords: ['uala', 'ualá'],
    extract: (text, subject = '') => {
      const combined = `${subject}\n${text}`
      let amount = null
      let sender = 'Desconocido'
      let operationId = null

      const amountMatch = combined.match(/(?:te transfirieron|recibiste|monto)[\s:]*\$?\s*([0-9]{1,3}(?:\.[0-9]{3})*(?:,[0-9]{1,2})?|[0-9]+(?:\.[0-9]{1,2})?)/i)
      if (amountMatch) amount = parseAmountNumber(amountMatch[1])

      const senderMatch = combined.match(/de\s+([A-Za-zÁ-ÿ\s]{3,35})/i)
      if (senderMatch) sender = cleanSenderName(senderMatch[1])

      const opMatch = combined.match(/(?:operaci[oó]n|referencia|comprobante)[\s#n°:]*([0-9A-Za-z]{6,15})/i)
      if (opMatch) operationId = opMatch[1]

      return { amount, sender, operationId }
    }
  },
  {
    id: 'generico',
    name: 'Transferencia Bancaria General',
    icon: '🏦',
    senderKeywords: ['banco', 'transferencia', 'acreditacion', 'cbu', 'cvu', 'coelsa'],
    extract: (text, subject = '') => {
      const combined = `${subject}\n${text}`
      let amount = null
      let sender = 'Desconocido'
      let operationId = null

      const amountMatch = combined.match(/\$?\s*([0-9]{1,3}(?:\.[0-9]{3})*(?:,[0-9]{1,2})?)/)
      if (amountMatch) amount = parseAmountNumber(amountMatch[1])

      const senderMatch = combined.match(/(?:de|ordenante|titular|remitente|envi[oó])[\s:]+([A-Za-zÁ-ÿ\s]{3,35})/i)
      if (senderMatch) sender = cleanSenderName(senderMatch[1])

      const opMatch = combined.match(/(?:operaci[oó]n|comprobante|ref|id|n[uú]mero)[\s#n°:]*([0-9A-Za-z]{6,16})/i)
      if (opMatch) operationId = opMatch[1]

      return { amount, sender, operationId }
    }
  }
]

// Convierte "$ 7.700,00" o "7700.00" o "7.700" en número flotante
export function parseAmountNumber(str) {
  if (!str) return null
  let s = str.trim().replace(/\$/g, '').trim()
  if (s.includes('.') && s.includes(',')) {
    // Formato argentino: 7.700,50 -> quitar puntos de miles y reemplazar coma por punto
    s = s.replace(/\./g, '').replace(',', '.')
  } else if (s.includes(',')) {
    // 7700,50 -> 7700.50
    s = s.replace(',', '.')
  } else if (s.includes('.') && s.split('.').pop().length === 3) {
    // 7.700 sin centavos -> 7700
    s = s.replace(/\./g, '')
  }
  const n = parseFloat(s)
  return isNaN(n) ? null : n
}

export function cleanSenderName(name) {
  if (!name) return 'Desconocido'
  let n = name.replace(/<[^>]+>/g, '').replace(/[\r\n\t]+/g, ' ').trim()
  // Quitar palabras de cierre comunes
  n = n.replace(/(?:a tu cuenta|a mercado pago|en tu cuenta|por transferencia|en concepto|cbu|cvu).*$/i, '').trim()
  return n.length > 3 ? n : 'Desconocido'
}

// Analizador maestro de texto/correo
export function parseBankEmail(rawContent, subject = '') {
  const content = String(rawContent || '')
  const subj = String(subject || '')
  const lower = `${subj} ${content}`.toLowerCase()

  // Detectar entidad bancaria o fintech
  let matchedConfig = BANK_CONFIGS.find(cfg => 
    cfg.senderKeywords.some(kw => lower.includes(kw))
  )

  if (!matchedConfig) {
    matchedConfig = BANK_CONFIGS.find(c => c.id === 'generico')
  }

  const { amount, sender, operationId } = matchedConfig.extract(content, subj)

  return {
    bankId: matchedConfig.id,
    bankName: matchedConfig.name,
    bankIcon: matchedConfig.icon,
    amount,
    sender,
    operationId: operationId || `OP-${Date.now().toString().slice(-6)}`,
    rawSnippet: content.slice(0, 200),
    timestamp: new Date().toISOString()
  }
}

// Cruce inteligente con pedidos pendientes
export function matchTransferWithOrders(transfer, pendingOrders) {
  if (!transfer || !transfer.amount || !pendingOrders || pendingOrders.length === 0) {
    return []
  }

  const results = pendingOrders.map(order => {
    let score = 0
    const reasons = []

    // 1. Coincidencia de Monto (Criterio Principal)
    const orderAmount = order.clientPrice || order.total || 0
    const diff = Math.abs(orderAmount - transfer.amount)
    
    if (diff === 0) {
      score += 55
      reasons.push(`Monto exacto ($${transfer.amount.toLocaleString()} ARS)`)
    } else if (diff <= 5) {
      score += 45
      reasons.push(`Monto con centavos aproximados ($${orderAmount} vs $${transfer.amount})`)
    }

    // 2. Coincidencia de Nombre / Titular
    if (transfer.sender && transfer.sender !== 'Desconocido' && order.customerName) {
      const transWords = transfer.sender.toLowerCase().split(/\s+/).filter(w => w.length > 2)
      const orderWords = order.customerName.toLowerCase().split(/\s+/).filter(w => w.length > 2)
      
      const commonWords = transWords.filter(w => orderWords.some(ow => ow.includes(w) || w.includes(ow)))
      if (commonWords.length >= 2) {
        score += 35
        reasons.push(`Nombre y Apellido coincidente (${transfer.sender})`)
      } else if (commonWords.length === 1) {
        score += 20
        reasons.push(`Nombre coincidente parcial (${commonWords[0]})`)
      }
    }

    // 3. Coincidencia de N° Comprobante / Voucher
    if (order.voucher && transfer.operationId && order.voucher !== 'Sin comprobante adjunto') {
      const cleanVoucher = String(order.voucher).replace(/[^0-9A-Za-z]/g, '').toLowerCase()
      const cleanOp = String(transfer.operationId).replace(/[^0-9A-Za-z]/g, '').toLowerCase()
      if (cleanVoucher && cleanOp && (cleanVoucher.includes(cleanOp) || cleanOp.includes(cleanVoucher))) {
        score += 40
        reasons.push(`N° de Operación idéntico (${transfer.operationId})`)
      }
    }

    // 4. Bonificación si es pedido reciente (creado hoy)
    if (order.createdDate) {
      const orderDate = new Date(order.createdDate)
      const now = new Date()
      const hoursDiff = (now - orderDate) / (1000 * 60 * 60)
      if (hoursDiff < 3) {
        score += 10
        reasons.push('Pedido generado recientemente (< 3 horas)')
      }
    }

    let confidence = 'none'
    if (score >= 80) confidence = 'exact'
    else if (score >= 50) confidence = 'high'
    else if (score >= 30) confidence = 'medium'

    return {
      order,
      score,
      confidence,
      reasons
    }
  })

  // Ordenar por mayor puntaje
  return results.filter(r => r.score >= 30).sort((a, b) => b.score - a.score)
}

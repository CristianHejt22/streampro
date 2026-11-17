import https from 'https'

const OFFICIAL_TELEGRAM_BOT_TOKEN = '9120060693:AAFynj7rM0cfElf1PMZ0fox9fZxj3wLqA30'
const OFFICIAL_TELEGRAM_CHAT_ID = '7994226676'

function escapeHtml(str) {
  if (!str) return ''
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
}

function sendTelegramMessage(token, chatId, text) {
  return new Promise((resolve, reject) => {
    const postData = JSON.stringify({
      chat_id: chatId,
      text: text,
      parse_mode: 'HTML',
      disable_web_page_preview: true
    })

    const req = https.request({
      hostname: 'api.telegram.org',
      path: `/bot${token}/sendMessage`,
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Content-Length': Buffer.byteLength(postData)
      }
    }, res => {
      let data = ''
      res.on('data', chunk => data += chunk)
      res.on('end', () => {
        try {
          resolve(JSON.parse(data))
        } catch {
          resolve({ raw: data })
        }
      })
    })

    req.on('error', reject)
    req.write(postData)
    req.end()
  })
}

function parseDataUrl(dataUrl) {
  if (!dataUrl || typeof dataUrl !== 'string') return null
  const match = dataUrl.match(/^data:([^;]+);base64,(.+)$/)
  if (!match) return null
  try {
    const cleanBase64 = match[2].replace(/\s+/g, '')
    return {
      mimeType: match[1],
      buffer: Buffer.from(cleanBase64, 'base64')
    }
  } catch (err) {
    console.error('[Telegram] Error al parsear DataURL base64:', err)
    return null
  }
}

/**
 * Guarda el comprobante en Firestore vía REST API directamente desde el servidor
 * para asegurar disponibilidad inmediata en cualquier dispositivo
 */
async function persistReceiptToFirestore(orderIds = [], rawReceipt, customerName = '', customerPhone = '', amount = 0) {
  if (!rawReceipt || !rawReceipt.dataUrl || !Array.isArray(orderIds) || orderIds.length === 0) return
  const projectId = 'streamepro-6f5a3'
  const uniqueIds = [...new Set(orderIds.map(id => String(id || '').trim()).filter(Boolean))]

  for (const orderId of uniqueIds) {
    try {
      const url = `https://firestore.googleapis.com/v1/projects/${projectId}/databases/(default)/documents/receipts/${encodeURIComponent(orderId)}`
      const body = {
        fields: {
          orderId: { stringValue: orderId },
          fileName: { stringValue: String(rawReceipt.fileName || 'comprobante.jpg') },
          fileType: { stringValue: String(rawReceipt.fileType || 'image/jpeg') },
          fileSize: { integerValue: String(rawReceipt.fileSize || 0) },
          dataUrl: { stringValue: String(rawReceipt.dataUrl) },
          customerName: { stringValue: String(customerName || '') },
          customerPhone: { stringValue: String(customerPhone || '') },
          amount: { doubleValue: Number(amount || 0) },
          uploadedAt: { stringValue: new Date().toISOString() }
        }
      }
      await fetch(url, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body)
      })
    } catch (e) {
      console.warn(`[notify-order] Error guardando comprobante en Firestore para ${orderId}:`, e)
    }
  }
}

async function sendTelegramMedia(token, chatId, parsedFile, fileName = 'comprobante.jpg', caption = '') {
  const isPdf = parsedFile.mimeType === 'application/pdf' || (fileName && fileName.toLowerCase().endsWith('.pdf'))
  const finalFileName = fileName || (isPdf ? 'comprobante.pdf' : 'comprobante.jpg')

  // 1. Si no es PDF, intentar enviar como foto para previsualización grande en Telegram
  if (!isPdf) {
    try {
      const form = new FormData()
      form.append('chat_id', chatId)
      const blob = new Blob([new Uint8Array(parsedFile.buffer)], { type: parsedFile.mimeType || 'image/jpeg' })
      form.append('photo', blob, finalFileName)
      if (caption) {
        form.append('caption', caption.slice(0, 1000))
        form.append('parse_mode', 'HTML')
      }

      const res = await fetch(`https://api.telegram.org/bot${token}/sendPhoto`, {
        method: 'POST',
        body: form
      })
      const data = await res.json()
      if (data && data.ok) {
        return data
      }
      console.warn(`[Telegram sendPhoto] No se pudo enviar como foto (${data?.description || 'Error'}), reintentando como documento...`)
    } catch (photoErr) {
      console.warn('[Telegram sendPhoto] Excepción enviando foto, reintentando como documento:', photoErr)
    }
  }

  // 2. Enviar como Documento (sendDocument): Garantizado al 100% para PDFs y cualquier formato de imagen
  try {
    const docForm = new FormData()
    docForm.append('chat_id', chatId)
    const blob = new Blob([new Uint8Array(parsedFile.buffer)], { type: parsedFile.mimeType || 'application/octet-stream' })
    docForm.append('document', blob, finalFileName)
    if (caption) {
      docForm.append('caption', caption.slice(0, 1000))
      docForm.append('parse_mode', 'HTML')
    }

    const docRes = await fetch(`https://api.telegram.org/bot${token}/sendDocument`, {
      method: 'POST',
      body: docForm
    })
    return await docRes.json()
  } catch (err) {
    console.error(`[Telegram sendDocument] Error enviando media (${fileName}):`, err)
    return { ok: false, error: err?.message || String(err) }
  }
}

export default async function handler(req, res) {
  res.setHeader('Access-Control-Allow-Origin', '*')
  res.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type')

  if (req.method === 'OPTIONS') {
    return res.status(200).end()
  }

  if (req.method !== 'POST') {
    return res.status(405).json({ error: 'Method not allowed' })
  }

  try {
    const body = req.body || {}
    const token = body.telegramToken || process.env.TELEGRAM_BOT_TOKEN || OFFICIAL_TELEGRAM_BOT_TOKEN
    const chatId = body.telegramChatId || process.env.TELEGRAM_CHAT_ID || OFFICIAL_TELEGRAM_CHAT_ID

    if (!token || !chatId) {
      return res.status(200).json({ 
        success: false, 
        message: 'Telegram no configurado aún (falta TELEGRAM_BOT_TOKEN o TELEGRAM_CHAT_ID)' 
      })
    }

    // Mensaje de prueba desde el panel
    if (body.test) {
      const result = await sendTelegramMessage(
        token, 
        chatId, 
        '🔔 <b>¡Conexión Exitosa con StreamePro!</b>\n\nTu Bot de Telegram está configurado correctamente y listo para enviarte cada nuevo pedido en tiempo real.'
      )
      return res.status(200).json({ success: true, result })
    }

    // Envío directo o reenvío de comprobante desde el panel de administración
    if (body.sendReceiptOnly && body.receipt && body.receipt.dataUrl) {
      const orderNum = body.orderId || body.receipt.orderId || 'S/N'
      const custName = body.customerName || body.receipt.customerName || 'Cliente'
      const custPhone = body.customerPhone || body.receipt.customerPhone || ''
      const amt = body.amount || body.receipt.amount || 0

      // Asegurar persistencia inmediata en la nube Firestore bajo todos los identificadores conocidos
      const orderIdsToPersist = [
        orderNum,
        body.orderId,
        body.receipt.orderId,
        body.clientNumber,
        ...(Array.isArray(body.digitalOrderIds) ? body.digitalOrderIds : [])
      ]
      await persistReceiptToFirestore(orderIdsToPersist, body.receipt, custName, custPhone, amt)

      const parsed = parseDataUrl(body.receipt.dataUrl)
      if (parsed) {
        const caption = 
          `🧾 <b>COMPROBANTE DE PAGO REENVIADO</b>\n\n` +
          `📋 <b>N° Pedido:</b> <code>${escapeHtml(orderNum)}</code>\n` +
          `👤 <b>Cliente:</b> ${escapeHtml(custName)}\n` +
          `💰 <b>Monto:</b> <b>$${Number(amt).toLocaleString()} ARS</b>\n` +
          `☁️ <b>Almacenamiento:</b> Nube StreamePro Verificada`
        const mediaRes = await sendTelegramMedia(token, chatId, parsed, body.receipt.fileName, caption)
        return res.status(200).json({ success: true, mediaRes, persistedToFirestore: true })
      }
      return res.status(400).json({ error: 'Comprobante no válido o corrupto' })
    }

    // Notificación de nueva postulación de revendedor
    if (body.applicant) {
      const app = body.applicant
      const cleanCustomerPhone = String(app.whatsapp || '').replace(/[^0-9]/g, '')
      const waLink = cleanCustomerPhone ? `https://wa.me/${cleanCustomerPhone}` : null

      const applicantMessage = 
        `🤝 <b>¡NUEVA SOLICITUD DE REVENDEDOR EN STREAMEPRO!</b> 🚀\n\n` +
        `📋 <b>N° Solicitud:</b> <code>${escapeHtml(app.id || 'APP')}</code>\n` +
        `👤 <b>Postulante:</b> ${escapeHtml(app.fullName || 'Sin nombre')}\n` +
        `📱 <b>WhatsApp:</b> ${waLink ? `<a href="${waLink}">${escapeHtml(app.whatsapp)}</a>` : escapeHtml(app.whatsapp || 'Sin WhatsApp')}\n` +
        `✉️ <b>Email:</b> ${escapeHtml(app.email || 'No especificado')}\n` +
        `📍 <b>Ciudad / Zona:</b> ${escapeHtml(app.city || 'No especificada')}\n` +
        `🏪 <b>Nombre de Emprendimiento:</b> ${escapeHtml(app.storeName || 'A definir')}\n` +
        `💼 <b>Experiencia:</b> ${escapeHtml(app.experience || 'No especificada')}\n` +
        (app.notes ? `📝 <b>Notas:</b> <i>"${escapeHtml(app.notes)}"</i>\n` : '') +
        `\n🔗 <b>Revisar y Aprobar en Panel:</b> https://streamepro.me/admin`

      const telegramRes = await sendTelegramMessage(token, chatId, applicantMessage)
      return res.status(200).json({ success: true, telegramRes })
    }

    // Mensaje de texto libre / alerta personalizada
    if (body.customMessage) {
      const telegramRes = await sendTelegramMessage(token, chatId, body.customMessage)
      return res.status(200).json({ success: true, telegramRes })
    }

    const ord = body.order
    if (!ord) {
      return res.status(400).json({ error: 'Faltan datos de la orden' })
    }

    const itemsText = (ord.items || []).map(i => {
      const name = i.name || i.platform || 'Item'
      const qty = i.quantity || i.units || 1
      const price = i.clientPrice || (i.priceNumeric || (parseInt(String(i.price || '0').replace(/[$. ]/g, '')) || 0))
      let s = `• <b>${escapeHtml(name)}</b> (x${qty}): $${(price * qty).toLocaleString()} ARS`
      if (i.activationEmail) s += `\n  ✉️ Activación: <code>${escapeHtml(i.activationEmail)}</code>`
      return s
    }).join('\n')

    const cleanCustomerPhone = String(ord.customerPhone || '').replace(/[^0-9]/g, '')
    const waLink = cleanCustomerPhone ? `https://wa.me/${cleanCustomerPhone}` : null

    const rawReceipt = body.receipt || ord.receipt || (ord.receiptDataUrl ? { dataUrl: ord.receiptDataUrl, fileName: ord.receiptFileName, fileType: ord.receiptFileType } : null)
    const hasReceiptAttached = Boolean(rawReceipt && rawReceipt.dataUrl)

    // Persistir comprobante en la nube Firestore bajo todos los identificadores asociados (orden principal, subórdenes digitales, clientNumber)
    if (hasReceiptAttached) {
      const orderIdsToPersist = [
        ord.id,
        ord.clientNumber,
        ord.customerNumber,
        body.orderId,
        body.clientNumber,
        ...(Array.isArray(body.digitalOrderIds) ? body.digitalOrderIds : [])
      ]
      await persistReceiptToFirestore(
        orderIdsToPersist,
        rawReceipt,
        ord.customerName,
        ord.customerPhone,
        ord.total
      )
    }

    const message = 
      `🚨 <b>¡NUEVO PEDIDO RECIBIDO EN STREAMEPRO!</b> 🚀\n\n` +
      `📋 <b>N° de Pedido:</b> <code>${escapeHtml(ord.clientNumber || ord.id || 'S/N')}</code>\n` +
      `👤 <b>Cliente:</b> ${escapeHtml(ord.customerName || 'Cliente')}\n` +
      `📱 <b>WhatsApp:</b> ${waLink ? `<a href="${waLink}">${escapeHtml(ord.customerPhone)}</a>` : escapeHtml(ord.customerPhone || 'Sin teléfono')}\n` +
      `💰 <b>Monto Total:</b> <b>$${Number(ord.total || 0).toLocaleString()} ARS</b>\n` +
      `💳 <b>Método de Pago:</b> ${escapeHtml(ord.paymentMethod || 'Transferencia Bancaria')}\n` +
      `🏢 <b>Canal:</b> ${ord.isDirect ? 'Directo (StreamePro Oficial)' : escapeHtml(ord.reseller?.name || 'Distribuidor')}\n` +
      (hasReceiptAttached ? `🧾 <b>Comprobante de Pago:</b> ☁️ <i>Adjunto abajo en Telegram</i>\n\n` : `\n`) +
      `🛒 <b>Productos Solicitados:</b>\n${itemsText || '• Sin detalle'}\n` +
      (ord.shippingAddress ? `\n📦 <b>Dirección de Envío:</b> ${escapeHtml(ord.shippingAddress)}\n` : '') +
      `\n🔗 <b>Panel de Gestión:</b> https://streamepro.me/admin`

    const telegramRes = await sendTelegramMessage(token, chatId, message)
    let receiptRes = null

    // Envío del archivo de comprobante (foto o PDF) directamente al chat de Telegram
    if (hasReceiptAttached) {
      const parsed = parseDataUrl(rawReceipt.dataUrl)
      if (parsed) {
        const receiptCaption = 
          `🧾 <b>COMPROBANTE DE PAGO ADJUNTO</b>\n` +
          `📋 <b>Pedido:</b> <code>${escapeHtml(ord.clientNumber || ord.id || 'S/N')}</code>\n` +
          `👤 <b>Cliente:</b> ${escapeHtml(ord.customerName || 'Cliente')}\n` +
          `💰 <b>Monto:</b> <b>$${Number(ord.total || 0).toLocaleString()} ARS</b>\n` +
          `💳 <b>Método:</b> ${escapeHtml(ord.paymentMethod || 'Transferencia Bancaria')}`

        receiptRes = await sendTelegramMedia(
          token,
          chatId,
          parsed,
          rawReceipt.fileName || (parsed.mimeType === 'application/pdf' ? 'comprobante.pdf' : 'comprobante.jpg'),
          receiptCaption
        )
      }
    }

    return res.status(200).json({ success: true, telegramRes, receiptRes })
  } catch (error) {
    console.error('Error enviando notificación a Telegram:', error)
    return res.status(500).json({ error: error.message })
  }
}

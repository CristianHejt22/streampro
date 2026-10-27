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

    const message = 
      `🚨 <b>¡NUEVO PEDIDO RECIBIDO EN STREAMEPRO!</b> 🚀\n\n` +
      `📋 <b>N° de Pedido:</b> <code>${escapeHtml(ord.clientNumber || 'S/N')}</code>\n` +
      `👤 <b>Cliente:</b> ${escapeHtml(ord.customerName || 'Cliente')}\n` +
      `📱 <b>WhatsApp:</b> ${waLink ? `<a href="${waLink}">${escapeHtml(ord.customerPhone)}</a>` : escapeHtml(ord.customerPhone || 'Sin teléfono')}\n` +
      `💰 <b>Monto Total:</b> <b>$${Number(ord.total || 0).toLocaleString()} ARS</b>\n` +
      `💳 <b>Método de Pago:</b> ${escapeHtml(ord.paymentMethod || 'Transferencia Bancaria')}\n` +
      `🏢 <b>Canal:</b> ${ord.isDirect ? 'Directo (StreamePro Oficial)' : escapeHtml(ord.reseller?.name || 'Distribuidor')}\n\n` +
      `🛒 <b>Productos Solicitados:</b>\n${itemsText || '• Sin detalle'}\n` +
      (ord.shippingAddress ? `\n📦 <b>Dirección de Envío:</b> ${escapeHtml(ord.shippingAddress)}\n` : '') +
      `\n🔗 <b>Panel de Gestión:</b> https://streamepro.me/admin`

    const telegramRes = await sendTelegramMessage(token, chatId, message)
    return res.status(200).json({ success: true, telegramRes })
  } catch (error) {
    console.error('Error enviando notificación a Telegram:', error)
    return res.status(500).json({ error: error.message })
  }
}

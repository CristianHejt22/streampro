import https from 'https'

function sendTelegramMessage(token, chatId, text) {
  return new Promise((resolve, reject) => {
    const postData = JSON.stringify({
      chat_id: chatId,
      text: text,
      parse_mode: 'Markdown',
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
    const token = body.telegramToken || process.env.TELEGRAM_BOT_TOKEN
    const chatId = body.telegramChatId || process.env.TELEGRAM_CHAT_ID

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
        '🔔 *¡Conexión Exitosa con StreamePro!*\n\nTu Bot de Telegram está configurado correctamente y listo para enviarte cada nuevo pedido en tiempo real.'
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
      let s = `• *${name}* (x${qty}): $${(price * qty).toLocaleString()} ARS`
      if (i.activationEmail) s += `\n  ✉️ Activación: \`${i.activationEmail}\``
      return s
    }).join('\n')

    const message = 
      `🚨 *¡NUEVO PEDIDO EN STREAMEPRO!* 🚀\n\n` +
      `📋 *N° de Pedido:* \`${ord.clientNumber || 'S/N'}\`\n` +
      `👤 *Cliente:* ${ord.customerName || 'Cliente'}\n` +
      `📱 *WhatsApp:* [${ord.customerPhone || 'Sin teléfono'}](https://wa.me/${String(ord.customerPhone || '').replace(/[^0-9]/g, '')})\n` +
      `💰 *Monto Total:* *$${Number(ord.total || 0).toLocaleString()} ARS*\n` +
      `💳 *Método de Pago:* ${ord.paymentMethod || 'Transferencia Bancaria'}\n` +
      `🏢 *Venta:* ${ord.isDirect ? 'Directa (StreamePro Oficial)' : (ord.reseller?.name || 'Distribuidor Asignado')}\n\n` +
      `🛒 *Productos Solicitados:*\n${itemsText || '• Sin detalle'}\n` +
      (ord.shippingAddress ? `\n📦 *Dirección de Envío:* ${ord.shippingAddress}\n` : '') +
      `\n🔗 *Panel de Gestión:* https://streamepro.me/admin`

    const telegramRes = await sendTelegramMessage(token, chatId, message)
    return res.status(200).json({ success: true, telegramRes })
  } catch (error) {
    console.error('Error enviando notificación a Telegram:', error)
    return res.status(500).json({ error: error.message })
  }
}

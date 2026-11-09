import { MercadoPagoConfig, Payment } from 'mercadopago'
import https from 'https'

const OFFICIAL_TELEGRAM_BOT_TOKEN = '9120060693:AAFynj7rM0cfElf1PMZ0fox9fZxj3wLqA30'
const OFFICIAL_TELEGRAM_CHAT_ID = '7994226676'

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

  // Mercado Pago envía notificaciones por POST (o GET en ciertos tests de IPN)
  const query = req.query || {}
  const body = req.body || {}

  // Extraer el ID de pago de Mercado Pago
  // Formato Webhook v2: body.data.id o query['data.id']
  // Formato IPN legado: query.id o query['data.id'] o body.id
  const paymentId = 
    (body?.data?.id) || 
    (body?.id) || 
    (query['data.id']) || 
    (query?.id) || 
    null

  const type = (body?.type) || (body?.action) || (query?.type) || (query?.topic) || ''

  // Si es un ping de prueba de Mercado Pago sin payment ID, responder 200 OK inmediatamente
  if (!paymentId) {
    return res.status(200).json({ received: true, message: 'Ping de webhook recibido correctamente.' })
  }

  // Si no es un evento de pago (ej: merchant_order), confirmar recepción con 200
  if (type && !type.includes('payment')) {
    return res.status(200).json({ received: true, type, ignored: true })
  }

  const accessToken = (
    process.env.MP_ACCESS_TOKEN || 
    process.env.MERCADOPAGO_ACCESS_TOKEN || 
    ''
  ).trim()

  if (!accessToken) {
    console.warn('[MP WEBHOOK] Recibido pago pero MP_ACCESS_TOKEN no está definido en variables de entorno.')
    return res.status(200).json({ received: true, warning: 'MP_ACCESS_TOKEN missing' })
  }

  try {
    const client = new MercadoPagoConfig({ accessToken })
    const paymentClient = new Payment(client)

    const payment = await paymentClient.get({ id: String(paymentId) })

    if (!payment) {
      return res.status(200).json({ received: true, error: 'Payment not found on MP' })
    }

    const orderId = payment.external_reference || 'SIN_ORDEN'
    const status = payment.status
    const statusDetail = payment.status_detail || ''
    const amount = payment.transaction_amount || 0
    const paymentMethod = payment.payment_method_id || 'Mercado Pago'
    const paymentType = payment.payment_type_id || ''
    const payerEmail = payment.payer?.email || 'No especificado'
    const payerName = `${payment.payer?.first_name || ''} ${payment.payer?.last_name || ''}`.trim() || 'Cliente'

    console.log(`[MP WEBHOOK] Pago ID ${paymentId} para Orden #${orderId}: Status=${status} ($${amount} ARS)`)

    // NOTIFICAR POR TELEGRAM SI EL PAGO FUE APROBADO
    if (status === 'approved') {
      const telegramMsg = [
        '<b>💰 PAGO APROBADO EN MERCADO PAGO 🇦🇷</b>',
        '',
        `📦 <b>N° de Orden:</b> <code>#${orderId}</code>`,
        `💵 <b>Monto Acreditado:</b> <code>$${amount.toLocaleString()} ARS</code>`,
        `💳 <b>Medio de Pago:</b> ${paymentMethod.toUpperCase()} (${paymentType})`,
        `👤 <b>Pagador:</b> ${payerName} (<code>${payerEmail}</code>)`,
        `🆔 <b>ID Mercado Pago:</b> <code>${paymentId}</code>`,
        `⏱️ <b>Estado:</b> Aprobado Inmediato (${statusDetail})`,
        '',
        '⚡ <b>Acción Requerida:</b>',
        'Verificar pedido en el Panel Admin y entregar credenciales en <b>60 a 90 min</b>.'
      ].join('\n')

      const token = process.env.TELEGRAM_BOT_TOKEN || OFFICIAL_TELEGRAM_BOT_TOKEN
      const chatId = process.env.TELEGRAM_CHAT_ID || OFFICIAL_TELEGRAM_CHAT_ID

      try {
        await sendTelegramMessage(token, chatId, telegramMsg)
        console.log(`[MP WEBHOOK] Alerta Telegram enviada con éxito para orden #${orderId}`)
      } catch (tgErr) {
        console.error('[MP WEBHOOK] Error enviando Telegram:', tgErr.message)
      }
    }

    return res.status(200).json({
      received: true,
      paymentId,
      orderId,
      status,
      amount
    })
  } catch (err) {
    console.error(`[MP WEBHOOK] Error procesando pago ID ${paymentId}:`, err.message)
    // Siempre responder 200 a Mercado Pago para evitar reintentos en bucle
    return res.status(200).json({ received: true, error: err.message })
  }
}

import { MercadoPagoConfig, Preference } from 'mercadopago'

export default async function handler(req, res) {
  // Configuración de CORS
  res.setHeader('Access-Control-Allow-Origin', '*')
  res.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')

  if (req.method === 'OPTIONS') {
    return res.status(200).end()
  }

  if (req.method !== 'POST') {
    return res.status(405).json({ error: 'Método no permitido. Utiliza POST.' })
  }

  try {
    const { 
      items = [], 
      orderId, 
      payerName = '', 
      payerEmail = '', 
      payerPhone = '',
      origin = 'https://streamepro.me',
      customAccessToken = ''
    } = req.body || {}

    if (!Array.isArray(items) || items.length === 0) {
      return res.status(400).json({ error: 'El carrito no contiene productos para cobrar.' })
    }

    // Prioridad del Access Token:
    // 1) Enviado desde el panel de configuración de la tienda
    // 2) Variable de entorno en Vercel / Hosting
    const accessToken = (
      customAccessToken || 
      process.env.MP_ACCESS_TOKEN || 
      process.env.MERCADOPAGO_ACCESS_TOKEN || 
      ''
    ).trim()

    if (!accessToken) {
      return res.status(400).json({ 
        error: 'Mercado Pago no está configurado. Por favor ingresa tu Access Token en el Panel de Administración o configura la variable de entorno MP_ACCESS_TOKEN.',
        missingCredentials: true 
      })
    }

    const client = new MercadoPagoConfig({ 
      accessToken,
      options: { timeout: 10000 }
    })

    const cleanOrderId = orderId || `ORD-${Date.now().toString().slice(-6)}`
    const baseOrigin = origin.replace(/\/$/, '')

    // Formatear items para Mercado Pago
    const mpItems = items.map((item, idx) => {
      const unitPrice = Math.round(Number(item.price || item.clientPrice || item.unitPrice || 0))
      return {
        id: String(item.id || `ITEM-${idx + 1}`),
        title: String(item.title || item.name || 'Servicio Digital StreamePro').slice(0, 255),
        description: String(item.description || item.planLabel || item.platform || 'Licencia Oficial StreamePro').slice(0, 255),
        picture_url: item.image || 'https://image.api.playstation.com/vulcan/ap/rnd/202203/0302/bZ1JTRXzoyl3hkcsloKcCgdB.jpg',
        quantity: Math.max(1, parseInt(item.quantity || 1, 10)),
        unit_price: Math.max(1, unitPrice),
        currency_id: 'ARS'
      }
    })

    const preference = new Preference(client)
    const preferenceData = {
      body: {
        items: mpItems,
        external_reference: cleanOrderId,
        payer: {
          name: payerName || 'Cliente StreamePro',
          email: payerEmail || 'cliente@streamepro.me',
          phone: {
            number: payerPhone ? String(payerPhone).replace(/[^0-9]/g, '') : ''
          }
        },
        back_urls: {
          success: `${baseOrigin}/?mp_status=approved&order_id=${cleanOrderId}`,
          pending: `${baseOrigin}/?mp_status=pending&order_id=${cleanOrderId}`,
          failure: `${baseOrigin}/?mp_status=failure&order_id=${cleanOrderId}`
        },
        auto_return: 'approved',
        notification_url: `${baseOrigin}/api/mp-webhook`,
        statement_descriptor: 'STREAMEPRO',
        binary_mode: false // Permite pagos pendientes (ej: Rapipago/Pago Fácil o revisión)
      }
    }

    const response = await preference.create(preferenceData)

    return res.status(200).json({
      success: true,
      orderId: cleanOrderId,
      preferenceId: response.id,
      initPoint: response.init_point,
      sandboxInitPoint: response.sandbox_init_point
    })
  } catch (error) {
    console.error('Error al crear preferencia en Mercado Pago:', error)
    return res.status(500).json({ 
      error: error.message || 'Error al conectar con Mercado Pago para generar el link de pago.' 
    })
  }
}

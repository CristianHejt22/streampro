import fs from 'fs'

export default async function handler(req, res) {
  const prodId = req.query.prod || req.query.id || req.query.p || ''
  const gameId = req.query.game || req.query.g || ''
  const refCode = req.query.ref || ''

  res.setHeader('Content-Type', 'text/html; charset=utf-8')
  res.setHeader('Cache-Control', 's-maxage=3600, stale-while-revalidate=86400')

  let product = null
  let isGame = false

  // 1. Si se consulta por Videojuego Gamer
  if (gameId) {
    try {
      const gamingUrl = new URL('../src/assets/gamingProducts.json', import.meta.url)
      const gamingList = JSON.parse(fs.readFileSync(gamingUrl, 'utf8'))
      const foundGame = gamingList.find(g => 
        String(g.id).toLowerCase() === String(gameId).toLowerCase() ||
        String(g.slug).toLowerCase() === String(gameId).toLowerCase() ||
        g.title.toLowerCase().replace(/[^a-z0-9]/g, '') === String(gameId).toLowerCase().replace(/[^a-z0-9]/g, '')
      )

      if (foundGame) {
        isGame = true
        const pvpPrimary = foundGame.effectivePrimaryPrice || 25000
        const pvpSecondary = foundGame.effectiveSecondaryPrice || Math.round(pvpPrimary * 0.55)

        product = {
          id: foundGame.id,
          name: `${foundGame.title} (${foundGame.platform}) — PlayStation Store Oficial`,
          price: `$${pvpPrimary.toLocaleString('es-AR')} ARS`,
          priceNumeric: pvpPrimary,
          description: `🎮 Videojuego oficial digital para consolas ${foundGame.platform}. Cuenta Primaria ($${pvpPrimary.toLocaleString('es-AR')} ARS) y Cuenta Secundaria ($${pvpSecondary.toLocaleString('es-AR')} ARS). Entrega digital inmediata 24/7 con garantía oficial StreamePro.`,
          image: foundGame.image || 'https://images.unsplash.com/photo-1606813907291-d86efa9b94db?w=800&auto=format&fit=crop&q=80',
          platform: foundGame.platform,
          category: foundGame.category || 'Videojuegos Digitales'
        }
      }
    } catch (e) {
      console.warn('Error reading gamingProducts.json in api/product-preview.js:', e)
    }
  }

  // 2. Si se consulta por Producto Físico o no es juego
  if (!product && prodId) {
    try {
      const productsUrl = new URL('../src/assets/physicalProducts.json', import.meta.url)
      const products = JSON.parse(fs.readFileSync(productsUrl, 'utf8'))
      product = products.find(p => 
        String(p.id).trim() === String(prodId).trim() ||
        (p.sku && String(p.sku).trim().toLowerCase() === String(prodId).trim().toLowerCase()) ||
        (p.pageSlug && String(p.pageSlug).trim().toLowerCase() === String(prodId).trim().toLowerCase())
      )
    } catch (e) {
      console.warn('Error reading physicalProducts.json in api/product-preview.js:', e)
    }
  }

  if (!product && prodId) {
    // Fallback a sync-revende API
    try {
      const revendeRes = await fetch('https://streamepro.me/api/sync-revende')
      if (revendeRes.ok) {
        const data = await revendeRes.json()
        product = (data.products || []).find(p => String(p.id).trim() === String(prodId).trim())
      }
    } catch (e) {
      console.warn('Error fetching fallback product from sync-revende:', e)
    }
  }

  // Producto por defecto si no se encuentra
  if (!product) {
    product = {
      id: prodId || gameId,
      name: 'PlayStation Store Oficial & Catálogo StreamePro',
      price: 'Precios Exclusivos',
      priceNumeric: 0,
      description: 'Catálogo oficial de videojuegos digitales PlayStation 5, PS4, cuentas de streaming y tecnología en StreamePro. Compra directa con entrega rápida, garantía oficial y despacho seguro a todo el país.',
      image: 'https://streamepro.me/og-banner.png'
    }
  }

  let rawImg = product.image || 'https://streamepro.me/og-banner.png'
  if (!rawImg.startsWith('http')) {
    rawImg = `https://streamepro.me/${rawImg.replace(/^\//, '')}`
  }

  // Imagen en alta definición 1200x630 para WhatsApp / Facebook / Telegram / Twitter
  const focusedOgImage = rawImg.startsWith('https://streamepro.me/og-banner')
    ? 'https://streamepro.me/og-banner.png'
    : (rawImg.includes('playstation.com') 
        ? rawImg 
        : `https://wsrv.nl/?url=${encodeURIComponent(rawImg)}&w=1200&h=630&fit=contain&cbg=ffffff&output=jpg`)

  const targetRedirectUrl = isGame
    ? (refCode ? `/?game=${encodeURIComponent(product.id)}&ref=${encodeURIComponent(refCode)}` : `/?game=${encodeURIComponent(product.id)}`)
    : (refCode ? `/?prod=${encodeURIComponent(prodId)}&ref=${encodeURIComponent(refCode)}&sec=physical` : `/?prod=${encodeURIComponent(prodId)}&sec=physical`)

  const canonicalUrl = isGame
    ? `https://streamepro.me/?game=${encodeURIComponent(product.id)}`
    : `https://streamepro.me/p/${encodeURIComponent(prodId)}`

  const cleanTitle = isGame
    ? `${product.name} | StreamePro Oficial`
    : `${product.name} (${product.price}) | StreamePro Oficial`

  const plainDesc = (product.description || '')
    .replace(/<[^>]*>/g, ' ')
    .replace(/\s+/g, ' ')
    .trim()

  const cleanDesc = plainDesc
    ? `${isGame ? '🎮' : '🛍️'} ${product.price}. ${plainDesc.slice(0, 160)}... Garantía Oficial StreamePro.`
    : `${isGame ? '🎮 Precio' : '🛍️ Oferta'}: ${product.price}. ${isGame ? 'Entrega digital oficial garantizada por PlayStation Store & StreamePro.' : 'Tienda Oficial StreamePro con garantía y atención rápida.'}`

  const html = `<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>${escapeHtml(cleanTitle)}</title>
  <meta name="description" content="${escapeHtml(cleanDesc)}">
  <link rel="canonical" href="${canonicalUrl}">

  <!-- Open Graph / WhatsApp / Facebook / Telegram Previews (1200x630) -->
  <meta property="og:type" content="product">
  <meta property="og:site_name" content="PlayStation Store Oficial | StreamePro">
  <meta property="og:title" content="${escapeHtml(product.name)} — ${escapeHtml(product.price)}">
  <meta property="og:description" content="${escapeHtml(cleanDesc)}">
  <meta property="og:image" content="${focusedOgImage}">
  <meta property="og:image:secure_url" content="${focusedOgImage}">
  <meta property="og:image:type" content="image/jpeg">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta property="og:image:alt" content="${escapeHtml(product.name)}">
  <meta property="og:url" content="${canonicalUrl}">
  <meta property="og:locale" content="es_AR">

  <!-- Twitter Card -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:site" content="@StreamePro">
  <meta name="twitter:title" content="${escapeHtml(product.name)} — ${escapeHtml(product.price)}">
  <meta name="twitter:description" content="${escapeHtml(cleanDesc)}">
  <meta name="twitter:image" content="${focusedOgImage}">

  <!-- Datos Estructurados Schema.org -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org/",
    "@type": "${isGame ? 'VideoGame' : 'Product'}",
    "name": ${JSON.stringify(product.name)},
    "image": [${JSON.stringify(focusedOgImage)}, ${JSON.stringify(rawImg)}],
    "description": ${JSON.stringify(plainDesc || cleanDesc)},
    "offers": {
      "@type": "Offer",
      "priceCurrency": "ARS",
      "price": ${JSON.stringify(product.priceNumeric || 0)},
      "availability": "https://schema.org/InStock",
      "seller": {
        "@type": "Organization",
        "name": "StreamePro Cloud Enterprise"
      }
    }
  }
  </script>

  <!-- Redirección Inmediata para Navegadores Humanos -->
  <meta http-equiv="refresh" content="0;url=${escapeHtml(targetRedirectUrl)}">
  <script>
    window.location.replace(${JSON.stringify(targetRedirectUrl)});
  </script>
</head>
<body style="background:#020617;color:#f8fafc;font-family:system-ui,-apple-system,sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;padding:20px;box-sizing:border-box;">
  <div style="text-align:center;max-width:480px;background:#0f172a;padding:30px;border-radius:24px;border:1px solid #334155;box-shadow:0 20px 40px rgba(0,0,0,0.5);">
    <div style="width:140px;height:140px;background:#ffffff;border-radius:18px;margin:0 auto 20px;display:flex;align-items:center;justify-content:center;padding:10px;">
      <img src="${focusedOgImage}" alt="${escapeHtml(product.name)}" style="max-width:100%;max-height:100%;object-fit:contain;">
    </div>
    <h1 style="font-size:18px;margin:0 0 10px;color:#ffffff;font-weight:900;">${escapeHtml(product.name)}</h1>
    <div style="font-size:22px;color:#38bdf8;font-weight:900;margin-bottom:15px;">${escapeHtml(product.price)}</div>
    <p style="font-size:12px;color:#94a3b8;margin:0 0 20px;">${isGame ? 'Redirigiendo a PlayStation Store Oficial en StreamePro...' : 'Redirigiendo al catálogo oficial de StreamePro...'}</p>
    <a href="${escapeHtml(targetRedirectUrl)}" style="display:inline-block;background:linear-gradient(to right,#38bdf8,#2563eb);color:#020617;text-decoration:none;font-weight:900;font-size:13px;padding:12px 24px;border-radius:12px;">Ver en StreamePro</a>
  </div>
</body>
</html>`

  return res.status(200).send(html)
}

function escapeHtml(str) {
  if (!str) return ''
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;')
}

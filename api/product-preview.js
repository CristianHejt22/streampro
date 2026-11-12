import { resolvePreviewPayload } from './_catalogLookup.js'

function isCrawlerUserAgent(ua = '') {
  return /(bot|crawler|spider|telegram|whatsapp|facebookexternalhit|facebot|twitterbot|discordbot|slackbot|linkedinbot|pinterest|applebot|skype|vkshare|embedly|quora|outbrain|flipboard|nuzzel|google-inspectiontool|curl|wget)/i.test(String(ua || ''))
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

function buildOgImageUrl(query = {}) {
  const params = new URLSearchParams()
  const forwardKeys = ['game', 'g', 'lic', 'prod', 'p', 'id', 'pkg', 'plat', 'games', 'prods', 'multi', 'sec', 'ref', 'promo', 'sorteo', 'title', 'price', 'orig']
  for (const key of forwardKeys) {
    const val = query[key]
    if (val !== undefined && val !== null && String(val).trim() !== '') {
      params.set(key, String(val).trim())
    }
  }
  params.set('v', '4')
  return `https://streamepro.me/api/og-image?${params.toString()}`
}

export default async function handler(req, res) {
  res.setHeader('Content-Type', 'text/html; charset=utf-8')
  res.setHeader('Cache-Control', 'public, max-age=0, must-revalidate')

  const isBot = isCrawlerUserAgent(req.headers['user-agent'] || '')
  const payload = await resolvePreviewPayload(req.query)
  const ogImageUrl = buildOgImageUrl(req.query)

  const pageTitle = payload.mode === 'multi' || payload.mode === 'catalog'
    ? `${payload.headline} | StreamePro Oficial`
    : `${payload.title} (${payload.priceStr}) | StreamePro Oficial`

  const promoSuffix = payload.promoCode ? ` · Código ${payload.promoCode}` : ''
  const ogTitle = payload.mode === 'multi' || payload.mode === 'catalog'
    ? `${payload.headline}`
    : `${payload.title} — ${payload.priceStr}${promoSuffix}`

  const metaDescription = payload.description || payload.subtitle || 'Catálogo oficial de videojuegos digitales PlayStation, streaming y tienda StreamePro.'
  const schemaType = payload.type === 'gaming' && payload.mode === 'single' ? 'VideoGame' : 'Product'
  const previewCardImg = payload.image || ogImageUrl

  const html = `<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>${escapeHtml(pageTitle)}</title>
  <meta name="description" content="${escapeHtml(metaDescription)}">
  <link rel="canonical" href="${escapeHtml(payload.canonicalUrl)}">

  <!-- Open Graph / Telegram / WhatsApp / Facebook / Discord (1200x630 Generado con Logo Oficial) -->
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="StreamePro · Tienda Oficial">
  <meta property="og:title" content="${escapeHtml(ogTitle)}">
  <meta property="og:description" content="${escapeHtml(metaDescription)}">
  <meta property="og:image" content="${escapeHtml(ogImageUrl)}">
  <meta property="og:image:secure_url" content="${escapeHtml(ogImageUrl)}">
  <meta property="og:image:type" content="image/jpeg">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta property="og:image:alt" content="${escapeHtml(ogTitle)}">
  <meta property="og:url" content="${escapeHtml(payload.canonicalUrl)}">
  <meta property="og:locale" content="es_AR">

  <!-- Twitter / X Large Card -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:site" content="@StreamePro">
  <meta name="twitter:title" content="${escapeHtml(ogTitle)}">
  <meta name="twitter:description" content="${escapeHtml(metaDescription)}">
  <meta name="twitter:image" content="${escapeHtml(ogImageUrl)}">

  <!-- Datos Estructurados Schema.org -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org/",
    "@type": "${schemaType}",
    "name": ${JSON.stringify(ogTitle)},
    "image": [${JSON.stringify(ogImageUrl)}],
    "description": ${JSON.stringify(metaDescription)},
    "offers": {
      "@type": "Offer",
      "priceCurrency": "ARS",
      "price": ${JSON.stringify(payload.priceNumeric || payload.items?.[0]?.priceNumeric || 0)},
      "availability": "https://schema.org/InStock",
      "seller": {
        "@type": "Organization",
        "name": "StreamePro Oficial"
      }
    }
  }
  </script>
${isBot ? '' : `  <script>
    window.location.replace(${JSON.stringify(payload.redirectUrl)});
  </script>`}
</head>
<body style="background:#020617;color:#f8fafc;font-family:system-ui,-apple-system,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:20px;box-sizing:border-box;">
  <div style="text-align:center;max-width:520px;background:#0f172a;padding:28px;border-radius:24px;border:1px solid #334155;box-shadow:0 20px 40px rgba(0,0,0,0.5);">
    <div style="width:100%;max-width:440px;background:#090d16;border:1px solid #334155;border-radius:18px;margin:0 auto 20px;overflow:hidden;">
      <img src="${escapeHtml(ogImageUrl || previewCardImg)}" alt="${escapeHtml(ogTitle)}" style="width:100%;height:auto;display:block;">
    </div>
    <h1 style="font-size:18px;margin:0 0 8px;color:#ffffff;font-weight:900;">${escapeHtml(ogTitle)}</h1>
    <p style="font-size:12px;color:#94a3b8;margin:0 0 18px;line-height:1.5;">${escapeHtml(metaDescription)}</p>
    <a href="${escapeHtml(payload.redirectUrl)}" style="display:inline-block;background:linear-gradient(to right,#38bdf8,#2563eb);color:#020617;text-decoration:none;font-weight:900;font-size:13px;padding:12px 24px;border-radius:12px;">Ver en StreamePro</a>
  </div>
</body>
</html>`

  return res.status(200).send(html)
}

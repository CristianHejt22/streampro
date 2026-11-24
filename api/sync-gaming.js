import fs from 'fs'

export default async function handler(req, res) {
  res.setHeader('Access-Control-Allow-Origin', '*')
  res.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type')
  res.setHeader('Cache-Control', 'no-store, max-age=0')

  if (req.method === 'OPTIONS') {
    return res.status(200).end()
  }

  const shouldScrape = req.query?.scrape === 'true' || req.query?.scrape === '1' || req.url?.includes('scrape=true')
  const sourceParam = String(req.query?.source || '').trim()

  // Modo rápido por proveedor o categoría (evita timeouts serverless y permite progreso en vivo en Admin)
  if (shouldScrape && sourceParam) {
    try {
      const { scrapeSingleGamingProvider } = await import('../scripts/sync-gaming-catalog.js')
      const result = await scrapeSingleGamingProvider(sourceParam, {
        cat: req.query?.cat ? String(req.query.cat) : null,
        pageStart: req.query?.pageStart ? Number(req.query.pageStart) : 0,
        pageLimit: req.query?.pageLimit ? Number(req.query.pageLimit) : 0,
        customUrl: req.query?.customUrl ? String(req.query.customUrl) : '',
        sourceLabel: req.query?.sourceLabel ? String(req.query.sourceLabel) : ''
      })
      // Compactar payload cuando supera varios miles de ítems (DGS 14k) para mantenerse muy debajo del límite HTTP serverless
      const compactItems = Array.isArray(result?.items) && result.items.length > 2000
        ? result.items.map(it => ({
            t: it.title,
            n: it.norm,
            b: it.bucket,
            p: it.platform,
            c1: it.costPrimary,
            c2: it.costSecondary,
            ...(it.image && !it.image.includes('unsplash.com') ? { img: it.image } : {}),
            ...(it.isBlackFriday ? { bf: 1, pct: it.offerPct || 25 } : {})
          }))
        : result?.items

      return res.status(result?.success ? 200 : 400).json({
        ...result,
        compact: compactItems !== result?.items,
        items: compactItems,
        timestamp: new Date().toISOString()
      })
    } catch (err) {
      return res.status(500).json({
        success: false,
        scrapedLive: false,
        source: sourceParam,
        error: err?.message || 'Error ejecutando scraper del proveedor'
      })
    }
  }

  // Modo scrapeo completo multi-sitio sin escritura EROFS en Vercel
  if (shouldScrape) {
    try {
      const { scrapeAndSyncGamingCatalog } = await import('../scripts/sync-gaming-catalog.js')
      const liveResult = await scrapeAndSyncGamingCatalog({ writeFiles: false })
      if (liveResult && liveResult.success && Array.isArray(liveResult.products)) {
        return res.status(200).json({
          ...liveResult,
          scrapedLive: true,
          source: 'Live Multi-Store Scrape'
        })
      }
    } catch (scrapeErr) {
      console.warn('[api/sync-gaming] Live scrape fallback to local assets:', scrapeErr.message)
      try {
        const productsUrl = new URL('../src/assets/gamingProducts.json', import.meta.url)
        const categoriesUrl = new URL('../src/assets/gamingCategories.json', import.meta.url)
        const subcategoriesUrl = new URL('../src/assets/gamingSubcategories.json', import.meta.url)
        const products = JSON.parse(fs.readFileSync(productsUrl, 'utf8'))
        const categories = JSON.parse(fs.readFileSync(categoriesUrl, 'utf8'))
        const subcategories = JSON.parse(fs.readFileSync(subcategoriesUrl, 'utf8'))
        return res.status(200).json({
          success: true,
          scrapedLive: false,
          scrapeError: scrapeErr?.message || 'Timeout o error en scrapeo completo',
          source: 'PlayStation Store Oficial & Catálogo Consolidado',
          timestamp: new Date().toISOString(),
          count: products.length,
          categories,
          subcategories,
          products
        })
      } catch {}
    }
  }

  try {
    const productsUrl = new URL('../src/assets/gamingProducts.json', import.meta.url)
    const categoriesUrl = new URL('../src/assets/gamingCategories.json', import.meta.url)
    const subcategoriesUrl = new URL('../src/assets/gamingSubcategories.json', import.meta.url)

    const products = JSON.parse(fs.readFileSync(productsUrl, 'utf8'))
    const categories = JSON.parse(fs.readFileSync(categoriesUrl, 'utf8'))
    const subcategories = JSON.parse(fs.readFileSync(subcategoriesUrl, 'utf8'))

    return res.status(200).json({
      success: true,
      scrapedLive: false,
      source: 'PlayStation Store Oficial & Catálogo Consolidado',
      timestamp: new Date().toISOString(),
      count: products.length,
      categories,
      subcategories,
      products
    })
  } catch (error) {
    return res.status(500).json({
      success: false,
      error: error.message || 'Error al obtener catálogo gamer sincronizado'
    })
  }
}

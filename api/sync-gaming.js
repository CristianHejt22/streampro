import fs from 'fs'

export default async function handler(req, res) {
  res.setHeader('Access-Control-Allow-Origin', '*')
  res.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type')

  if (req.method === 'OPTIONS') {
    return res.status(200).end()
  }

  // Si se solicita scrapeo en vivo y estamos en entorno que soporte ejecución del scraper
  const shouldScrape = req.query?.scrape === 'true' || req.query?.scrape === '1' || req.url?.includes('scrape=true')
  if (shouldScrape) {
    try {
      const { scrapeAndSyncGamingCatalog } = await import('../scripts/sync-gaming-catalog.js')
      const liveResult = await scrapeAndSyncGamingCatalog()
      if (liveResult && liveResult.success && Array.isArray(liveResult.products)) {
        return res.status(200).json(liveResult)
      }
    } catch (scrapeErr) {
      console.warn('[api/sync-gaming] Live scrape fallback to local assets:', scrapeErr.message)
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

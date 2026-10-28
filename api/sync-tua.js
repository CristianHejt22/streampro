import fs from 'fs'

export default async function handler(req, res) {
  res.setHeader('Access-Control-Allow-Origin', '*')
  res.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type')

  if (req.method === 'OPTIONS') {
    return res.status(200).end()
  }

  try {
    const productsUrl = new URL('../src/assets/physicalProducts.json', import.meta.url)
    const categoriesUrl = new URL('../src/assets/physicalCategories.json', import.meta.url)

    const products = JSON.parse(fs.readFileSync(productsUrl, 'utf8'))
    const categories = JSON.parse(fs.readFileSync(categoriesUrl, 'utf8'))

    return res.status(200).json({
      success: true,
      source: 'https://www.revende.com.ar',
      legacyEndpoint: 'sync-tua',
      timestamp: new Date().toISOString(),
      count: products.length,
      categoriesCount: categories.length,
      categories: categories,
      products: products
    })
  } catch (error) {
    return res.status(500).json({
      success: false,
      error: error.message || 'Error al obtener catálogo sincronizado'
    })
  }
}

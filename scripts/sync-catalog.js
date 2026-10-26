/**
 * Script de Sincronización del Catálogo Físico (StreamPro)
 * Sincroniza los productos físicos desde el proyecto mayorista (C:\Users\yo\catalogo)
 * o directamente desde la API oficial de tua.ar hacia src/assets/
 */

import fs from 'fs'
import path from 'path'
import https from 'https'
import { fileURLToPath } from 'url'

const __filename = fileURLToPath(import.meta.url)
const __dirname = path.dirname(__filename)

const PROJECT_ROOT = path.resolve(__dirname, '..')
const ASSETS_DIR = path.join(PROJECT_ROOT, 'src', 'assets')
const EXTERNAL_CATALOG_DIR = 'C:\\Users\\yo\\catalogo'

const TARGET_PRODUCTS_FILE = path.join(ASSETS_DIR, 'physicalProducts.json')
const TARGET_CATEGORIES_FILE = path.join(ASSETS_DIR, 'physicalCategories.json')

const SOURCE_PRODUCTS_FILE = path.join(EXTERNAL_CATALOG_DIR, 'products.json')
const SOURCE_CATEGORIES_FILE = path.join(EXTERNAL_CATALOG_DIR, 'categories.json')

function log(msg) {
  console.log(`[CATÁLOGO FÍSICO] ${msg}`)
}

function parseArgentinePrice(val) {
  if (typeof val === 'number') return val
  if (!val) return 0
  let str = String(val).replace(/\$/g, '').trim()
  if (str.includes(',')) {
    str = str.split('.').join('').replace(',', '.')
  } else {
    const dotCount = (str.match(/\./g) || []).length
    if (dotCount > 1) {
      str = str.split('.').join('')
    } else if (dotCount === 1 && str.split('.')[1].length === 3) {
      str = str.split('.').join('')
    }
  }
  str = str.replace(/[^0-9.]/g, '')
  const num = parseFloat(str)
  return isNaN(num) ? 0 : num
}

function formatProduct(p, catMap = {}) {
  const catId = (p.categoryIds && p.categoryIds[0]) || p.category || ''
  const catName = catMap[catId] || p.category || 'General'
  const imageClean = p.image
    ? (p.image.startsWith('http') ? p.image : `https://tua.ar/${p.image.replace(/^\//, '')}`)
    : 'https://tua.ar/images/banner22.webp'

  return {
    ...p,
    category: catName,
    image: imageClean,
    priceNumeric: parseArgentinePrice(p.price),
    stock: p.stock || 20
  }
}

function postRequest(url, bodyData = '') {
  return new Promise((resolve, reject) => {
    const urlObj = new URL(url)
    const options = {
      hostname: urlObj.hostname,
      path: urlObj.pathname + urlObj.search,
      method: bodyData ? 'POST' : 'GET',
      headers: {
        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko)',
        'Content-Type': 'application/x-www-form-urlencoded',
        'Accept': 'application/json, text/plain, */*',
        'Content-Length': Buffer.byteLength(bodyData)
      }
    }

    const req = https.request(options, (res) => {
      let data = ''
      res.on('data', chunk => data += chunk)
      res.on('end', () => resolve(data))
    })

    req.on('error', e => reject(e))
    if (bodyData) req.write(bodyData)
    req.end()
  })
}

export async function fetchFromApi() {
  const baseUrl = "https://tua.ar/catalogo.php"
  const categoriesUrl = "https://tua.ar/catalogo.php?accion=categorias"

  log("Iniciando descarga directa desde https://tua.ar...")
  let categoriesResponse = []
  const catMap = {}
  try {
    const rawCats = await postRequest(categoriesUrl)
    categoriesResponse = JSON.parse(rawCats)
    categoriesResponse.forEach(c => { catMap[c.id] = c.name })
    const activeCats = categoriesResponse.filter(c => parseInt(c.cantidad || '0') > 0)
    fs.writeFileSync(TARGET_CATEGORIES_FILE, JSON.stringify(activeCats, null, 2), 'utf-8')
    log(`✓ ${activeCats.length} categorías obtenidas de la API`)
  } catch (e) {
    log(`Error al consultar categorías de la API: ${e.message}`)
    return false
  }

  const productDict = {}
  for (const cat of categoriesResponse) {
    if (parseInt(cat.cantidad || '0') <= 0) continue
    const catId = cat.id
    let page = 0

    while (page < 10) {
      const formData = `page=${page}&order=&category=${catId}&buscar=`
      try {
        const responseStr = await postRequest(baseUrl, formData)
        if (!responseStr || responseStr.trim() === '') break
        const response = JSON.parse(responseStr)
        if (response && response.length > 0) {
          for (const rawItem of response) {
            const formatted = formatProduct(rawItem, catMap)
            if (!productDict[formatted.id]) {
              formatted.categoryIds = [catId]
              productDict[formatted.id] = formatted
            } else {
              if (!productDict[formatted.id].categoryIds.includes(catId)) {
                productDict[formatted.id].categoryIds.push(catId)
              }
            }
          }
          if (response.length < 10) break
          page++
        } else {
          break
        }
      } catch (err) {
        break
      }
    }
  }

  const allProducts = Object.values(productDict).sort((a, b) => parseInt(b.id) - parseInt(a.id))
  log(`Total de productos únicos descargados: ${allProducts.length}`)
  fs.writeFileSync(TARGET_PRODUCTS_FILE, JSON.stringify(allProducts, null, 2), 'utf-8')

  // También guardar copia en C:\Users\yo\catalogo si la carpeta existe
  if (fs.existsSync(EXTERNAL_CATALOG_DIR)) {
    try {
      fs.writeFileSync(SOURCE_PRODUCTS_FILE, JSON.stringify(allProducts, null, 2), 'utf-8')
      fs.writeFileSync(SOURCE_CATEGORIES_FILE, JSON.stringify(categoriesResponse, null, 2), 'utf-8')
      log(`✓ Copia guardada en ${EXTERNAL_CATALOG_DIR}`)
    } catch (e) {}
  }

  return allProducts
}

function copyFromExternalProject() {
  if (fs.existsSync(SOURCE_PRODUCTS_FILE)) {
    log(`Encontrado catálogo en: ${EXTERNAL_CATALOG_DIR}`)
    const productsData = fs.readFileSync(SOURCE_PRODUCTS_FILE, 'utf-8')
    let categoriesData = null
    const catMap = {}
    if (fs.existsSync(SOURCE_CATEGORIES_FILE)) {
      try {
        categoriesData = JSON.parse(fs.readFileSync(SOURCE_CATEGORIES_FILE, 'utf-8').replace(/^\uFEFF/, ''))
        categoriesData.forEach(c => { catMap[c.id] = c.name })
        const activeCats = categoriesData.filter(c => parseInt(c.cantidad || '0') > 0)
        fs.writeFileSync(TARGET_CATEGORIES_FILE, JSON.stringify(activeCats, null, 2), 'utf-8')
        log(`✓ Sincronizadas categorías en ${TARGET_CATEGORIES_FILE}`)
      } catch (e) {}
    }

    try {
      const cleanData = productsData.replace(/^\uFEFF/, '')
      const parsed = JSON.parse(cleanData)
      if (Array.isArray(parsed)) {
        const formatted = parsed.map(p => formatProduct(p, catMap))
        fs.writeFileSync(TARGET_PRODUCTS_FILE, JSON.stringify(formatted, null, 2), 'utf-8')
        log(`✓ Sincronizados ${formatted.length} productos con precios normalizados en ${TARGET_PRODUCTS_FILE}`)
        return true
      }
    } catch (e) {
      log(`Nota al parsear productos: ${e.message}`)
    }
  }
  return false
}

async function main() {
  const args = process.argv.slice(2)
  const forceFetch = args.includes('--fetch')

  if (forceFetch || !fs.existsSync(SOURCE_PRODUCTS_FILE)) {
    log("Descargando actualización directamente desde el servidor mayorista tua.ar...")
    await fetchFromApi()
    log("¡Sincronización completada con éxito!")
  } else {
    log("Copiando y normalizando catálogo local desde C:\\Users\\yo\\catalogo...")
    copyFromExternalProject()
    log("¡Sincronización completada con éxito!")
  }
}

if (process.argv[1] === fileURLToPath(import.meta.url)) {
  main()
}

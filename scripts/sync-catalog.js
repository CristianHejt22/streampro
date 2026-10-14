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

function copyFromExternalProject() {
  if (fs.existsSync(SOURCE_PRODUCTS_FILE)) {
    log(`Encontrado catálogo en: ${EXTERNAL_CATALOG_DIR}`)
    const productsData = fs.readFileSync(SOURCE_PRODUCTS_FILE, 'utf-8')
    fs.writeFileSync(TARGET_PRODUCTS_FILE, productsData, 'utf-8')

    let count = 0
    try {
      const cleanData = productsData.replace(/^\uFEFF/, '')
      const parsed = JSON.parse(cleanData)
      count = Array.isArray(parsed) ? parsed.length : 0
      fs.writeFileSync(TARGET_PRODUCTS_FILE, cleanData, 'utf-8')
    } catch (e) {
      log(`Nota al parsear productos: ${e.message}`)
    }

    log(`✓ Sincronizados ${count} productos en ${TARGET_PRODUCTS_FILE}`)

    if (fs.existsSync(SOURCE_CATEGORIES_FILE)) {
      const categoriesData = fs.readFileSync(SOURCE_CATEGORIES_FILE, 'utf-8')
      const cleanCats = categoriesData.replace(/^\uFEFF/, '')
      fs.writeFileSync(TARGET_CATEGORIES_FILE, cleanCats, 'utf-8')
      log(`✓ Sincronizadas categorías en ${TARGET_CATEGORIES_FILE}`)
    }

    return true
  }
  return false
}

// Opcional: Descarga directa desde API tua.ar si se solicita con --fetch o no existe el archivo local
function postRequest(url, bodyData = '') {
  return new Promise((resolve, reject) => {
    const urlObj = new URL(url)
    const options = {
      hostname: urlObj.hostname,
      path: urlObj.pathname + urlObj.search,
      method: 'POST',
      headers: {
        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        'Content-Type': 'application/x-www-form-urlencoded',
        'Accept': '*/*',
        'Content-Length': Buffer.byteLength(bodyData)
      }
    }

    const req = https.request(options, (res) => {
      let data = ''
      res.on('data', chunk => data += chunk)
      res.on('end', () => resolve(data))
    })

    req.on('error', e => reject(e))
    req.write(bodyData)
    req.end()
  })
}

async function fetchFromApi() {
  const baseUrl = "https://tua.ar/catalogo.php"
  const categoriesUrl = "https://tua.ar/catalogo.php?accion=categorias"

  log("Iniciando descarga directa desde https://tua.ar...")
  let categoriesResponse = []
  try {
    const rawCats = await postRequest(categoriesUrl)
    categoriesResponse = JSON.parse(rawCats)
    fs.writeFileSync(TARGET_CATEGORIES_FILE, JSON.stringify(categoriesResponse, null, 2), 'utf-8')
    log(`✓ ${categoriesResponse.length} categorías obtenidas de la API`)
  } catch (e) {
    log(`Error al consultar categorías de la API: ${e.message}`)
    return false
  }

  const productDict = {}
  const rootCategory = { id: "", name: "Root/All", cantidad: 1 }
  const categoriesToProcess = [rootCategory, ...categoriesResponse]

  for (const cat of categoriesToProcess) {
    if (parseInt(cat.cantidad || '0') === 0 && cat.id !== "") continue
    const catId = cat.id
    log(`Descargando categoría: ${cat.name || 'General'} (${catId})...`)

    let page = 0
    let lastFirstId = null

    while (true) {
      const formData = `page=${page}&order=&category=${catId}&buscar=`
      try {
        const responseStr = await postRequest(baseUrl, formData)
        if (!responseStr || responseStr.trim() === '') break
        const response = JSON.parse(responseStr)
        if (response && response.length > 0) {
          if (response[0].id === lastFirstId) break
          lastFirstId = response[0].id

          for (const p of response) {
            if (!productDict[p.id]) {
              p.categoryIds = [catId]
              productDict[p.id] = p
            } else {
              if (!productDict[p.id].categoryIds.includes(catId)) {
                productDict[p.id].categoryIds.push(catId)
              }
            }
          }
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

  return true
}

async function main() {
  const args = process.argv.slice(2)
  const forceFetch = args.includes('--fetch')

  if (!forceFetch && fs.existsSync(SOURCE_PRODUCTS_FILE)) {
    log("Copiando catálogo local actualizado desde C:\\Users\\yo\\catalogo...")
    copyFromExternalProject()
    log("¡Sincronización completada con éxito!")
  } else {
    log("Descargando actualización directamente desde el servidor mayorista...")
    await fetchFromApi()
    log("¡Sincronización completada con éxito!")
  }
}

main()

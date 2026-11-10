/**
 * Script Oficial de Sincronización & Scraping Masivo Profundo de Videojuegos Digitales (StreamePro)
 * 
 * Fuentes Mayoristas & Plataformas Oficiales:
 * - PlayStation Store Oficial (https://store.playstation.com/)
 * - Digital Games Shop (https://digitalgamesshop.ar/) -> Base oficial PSN Vulcan CDN (14.000+ juegos)
 * - Tu Tienda Gamer (https://tutiendagamer.com.ar/productos) -> Juegos PS5/PS4/PS3 (excluyendo consolas y accesorios)
 * - Fusion Games (https://fusiongames.com.ar/tienda) -> Juegos Digitales PS
 * - Argentina Play (https://www.argentinaplay.com.ar/)
 * - Juegos Digitales Argentina (https://juegosdigitalesargentina.com/)
 * - Lista de ofertas mayoristas (.txt de juegos)
 * 
 * REGLA ESTRICTA DE PRIVACIDAD & MARCA SOLICITADA POR EL USUARIO:
 * "(NO MOSTRAR NOMBRES DE TERCERO QUE NO SEA PS STORE, PLAYSTATIONS ENTRE OTRAS)"
 * - Hacia el cliente y la plataforma pública: Solo se exhibe "PlayStation Store Oficial" / "StreamePro".
 * - En la administración interna: Se preservan los costos mayoristas para arbitraje y margen.
 */

import fs from 'fs'
import path from 'path'
import { fileURLToPath } from 'url'

const __filename = fileURLToPath(import.meta.url)
const __dirname = path.dirname(__filename)

const PROJECT_ROOT = path.resolve(__dirname, '..')
const ASSETS_DIR = path.join(PROJECT_ROOT, 'src', 'assets')

const TARGET_GAMING_PRODUCTS_FILE = path.join(ASSETS_DIR, 'gamingProducts.json')
const TARGET_GAMING_CATEGORIES_FILE = path.join(ASSETS_DIR, 'gamingCategories.json')
const TARGET_GAMING_SUBCATS_FILE = path.join(ASSETS_DIR, 'gamingSubcategories.json')
const TXT_FILE = 'c:/Users/yo/Desktop/juegos.txt.txt'

function log(msg) {
  console.log(`[SCRAPER GAMING] ${msg}`)
}

function decodeHtmlEntities(str) {
  if (!str) return ''
  return str
    .replace(/&#039;|&#39;|&apos;/g, "'")
    .replace(/&amp;/g, '&')
    .replace(/&quot;/g, '"')
    .replace(/&lt;/g, '<')
    .replace(/&gt;/g, '>')
    .replace(/&nbsp;/g, ' ')
    .replace(/&#(\d+);/g, (_, dec) => String.fromCharCode(dec))
}

function cleanTitleNormalization(raw) {
  if (!raw) return ''
  return decodeHtmlEntities(raw)
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[™®©\uFFFD?]/g, '')
    .replace(/mas alla de gotham/gi, 'beyond gotham')
    .replace(/\b(ii)\b/gi, '2')
    .replace(/\b(iii)\b/gi, '3')
    .replace(/\b(iv)\b/gi, '4')
    .replace(/\b(v)\b/gi, '5')
    .replace(/\b(vi)\b/gi, '6')
    .replace(/grand theft auto\s*(v|5)?/gi, 'gta 5')
    .replace(/(?:ea\s*sports\s*)?(?:fifa|fc)\s*(\d+)/gi, 'fc$1')
    .replace(/the\s+last\s+of\s+us\s+part(?:e)?\s*(i|1)\b/gi, 'the last of us 1')
    .replace(/the\s+last\s+of\s+us\s+part(?:e)?\s*(ii|2)\b/gi, 'the last of us 2')
    .replace(/\b(ps5|ps4|ps3|digital|original|cuenta|primaria|secundaria|edicion|edition|remastered|remake|standard|deluxe|gold|ultimate|complete|definitive|directors?\s*cut|next\s*gea?n|retro|bundle|pack|espanol|latino|subtitulado)\b/gi, ' ')
    .replace(/[^a-z0-9]/g, '')
    .trim()
}

function cleanTokens(raw) {
  if (!raw) return []
  return decodeHtmlEntities(raw)
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[™®©\uFFFD?]/g, '')
    .replace(/\b(ii)\b/gi, '2')
    .replace(/\b(iii)\b/gi, '3')
    .replace(/\b(iv)\b/gi, '4')
    .replace(/\b(v)\b/gi, '5')
    .replace(/\b(vi)\b/gi, '6')
    .replace(/grand theft auto/gi, 'gta')
    .replace(/(?:ea\s*sports\s*)?(?:fifa|fc)\s*(\d+)/gi, 'fc$1')
    .replace(/\b(ps5|ps4|ps3|digital|original|cuenta|primaria|secundaria|edicion|edition|remastered|remake|standard|deluxe|gold|ultimate|complete|definitive|directors?\s*cut|next\s*gea?n|retro|bundle|pack|espanol|latino|subtitulado)\b/gi, ' ')
    .replace(/[^a-z0-9\s]/g, ' ')
    .split(/\s+/)
    .filter(t => t.length >= 2)
}

function parseArsPrice(str) {
  if (!str) return 0
  // Extraer formato "$21.900,00" o "$21.900"
  const m = String(str).match(/\$?\s*([\d]{1,3}(?:\.[\d]{3})+|\d{4,7})(?:,\d{1,2})?/)
  if (!m) return 0
  return Number(m[1].replace(/\./g, '')) || 0
}

function inferTaxonomy(title) {
  const t = title.toLowerCase()
  let category = 'Acción & Aventura'
  let subcategory = 'Aventura Cinematográfica'

  // Deportes
  if (t.includes('fc 2') || t.includes('fifa') || t.includes('pes') || t.includes('turismo') || t.includes('nba') || t.includes('f1') || t.includes('wwe') || t.includes('madden') || t.includes('deporte') || t.includes('racing') || t.includes('rally') || t.includes('forza') || t.includes('motogp') || t.includes('mx vs atv') || t.includes('wrc')) {
    category = 'Deportes'
    if (t.includes('fc') || t.includes('fifa') || t.includes('pes') || t.includes('tsubasa')) subcategory = 'Fútbol (FC / FIFA)'
    else if (t.includes('turismo') || t.includes('f1') || t.includes('carrera') || t.includes('racing') || t.includes('rally') || t.includes('wrc') || t.includes('motogp')) subcategory = 'Carreras & Conducción'
    else subcategory = 'Básquet & Lucha Libre'
  }
  // Lucha
  else if (t.includes('kombat') || t.includes('dragon ball') || t.includes('sparking') || t.includes('tekken') || t.includes('street fighter') || t.includes('naruto') || t.includes('lucha') || t.includes('fight') || t.includes('budokai') || t.includes('guilty') || t.includes('ufc') || t.includes('king of fighters')) {
    category = 'Lucha'
    if (t.includes('dragon ball') || t.includes('naruto') || t.includes('sparking') || t.includes('anime')) subcategory = 'Anime Fighting'
    else if (t.includes('kombat')) subcategory = 'Torneo & Mortal Kombat'
    else subcategory = 'Combate 3D'
  }
  // Survival Horror
  else if (t.includes('resident evil') || t.includes('silent hill') || t.includes('dead space') || t.includes('alan wake') || t.includes('terror') || t.includes('horror') || t.includes('outlast') || t.includes('evil within') || t.includes('dead island') || t.includes('dayz') || t.includes('the forest') || t.includes('quarry')) {
    category = 'Survival Horror'
    if (t.includes('silent hill') || t.includes('alan wake') || t.includes('evil within')) subcategory = 'Terror Psicológico'
    else if (t.includes('resident evil') || t.includes('dead space') || t.includes('dead island') || t.includes('dayz')) subcategory = 'Zombies & Survival'
    else subcategory = 'Misterio & Terror'
  }
  // Rol & Aventura
  else if (t.includes('elden ring') || t.includes('hogwarts') || t.includes('wukong') || t.includes('cyberpunk') || t.includes('witcher') || t.includes('souls') || t.includes('final fantasy') || t.includes('baldurs') || t.includes('dune') || t.includes('skyrim') || t.includes('diablo') || t.includes('dragon age') || t.includes('blasphemous')) {
    category = 'Rol & Aventura'
    if (t.includes('elden ring') || t.includes('souls') || t.includes('wukong') || t.includes('blasphemous')) subcategory = 'Fantasía Oscura & Souls'
    else if (t.includes('cyberpunk') || t.includes('dune') || t.includes('starfield')) subcategory = 'Sci-Fi RPG'
    else subcategory = 'Mundos de Fantasía'
  }
  // Membresías
  else if (t.includes('ps plus') || t.includes('playstation plus') || t.includes('membres') || t.includes('gift card') || t.includes('suscripcion') || t.includes('psn')) {
    category = 'Membresías'
    if (t.includes('deluxe')) subcategory = 'PlayStation Plus Deluxe'
    else if (t.includes('extra')) subcategory = 'PlayStation Plus Extra'
    else if (t.includes('essential')) subcategory = 'PlayStation Plus Essential'
    else subcategory = 'Suscripciones Anuales'
  }
  // Plataformas
  else if (t.includes('astro bot') || t.includes('crash') || t.includes('mario') || t.includes('sonic') || t.includes('minecraft') || t.includes('lego') || t.includes('rayman') || t.includes('sackboy') || t.includes('ratchet') || t.includes('overcooked') || t.includes('little nightmares')) {
    category = 'Plataformas'
    if (t.includes('lego')) subcategory = 'Lego & Animación'
    else if (t.includes('minecraft') || t.includes('overcooked')) subcategory = 'Familiar & Coop'
    else subcategory = 'Aventuras 3D'
  }
  // Acción & Aventura
  else {
    category = 'Acción & Aventura'
    if (t.includes('gta') || t.includes('grand theft auto') || t.includes('red dead') || t.includes('mafia') || t.includes('yakuza') || t.includes('judgment') || t.includes('far cry') || t.includes('just cause')) subcategory = 'Mundo Abierto'
    else if (t.includes('call of duty') || t.includes('battlefield') || t.includes('ghost recon') || t.includes('wolfenstein') || t.includes('doom') || t.includes('arma')) subcategory = 'Shooters & Bélico'
    else if (t.includes('spider-man') || t.includes('batman') || t.includes('wolverine') || t.includes('marvel')) subcategory = 'Superhéroes'
    else if (t.includes('god of war') || t.includes('last of us') || t.includes('death stranding') || t.includes('assassin') || t.includes('007') || t.includes('hitman') || t.includes('metal gear')) subcategory = 'Aventura Cinematográfica'
    else subcategory = 'Acción General'
  }

  return { category, subcategory }
}

function inferBadge(cleanTitle) {
  const t = cleanTitle.toLowerCase()
  if (t.includes('fc 27') || t.includes('fc 25') || t.includes('gta 5') || t.includes('gta v') || t.includes('wukong') || t.includes('black ops 6')) return '⚽ MÁS VENDIDO'
  if (t.includes('astro bot') || t.includes('elden ring') || t.includes('god of war') || t.includes('last of us') || t.includes('silent hill 2')) return '🏆 JUEGO DEL AÑO'
  if (t.includes('007') || t.includes('death stranding 2') || t.includes('wolverine') || t.includes('silent hill f') || t.includes('gta 6')) return '🔥 LANZAMIENTO EXCLUSIVO'
  return '⭐️ PLAYSTATION OFICIAL'
}

/**
 * Scrapea el catálogo masivo oficial de Digital Games Shop (14.000+ juegos con Vulcan CDN de PS Store)
 */
async function scrapeDigitalGamesShop() {
  log('Descargando catálogo PS Store oficial desde snapshot de Digital Games Shop...')
  try {
    const res = await fetch('https://digitalgamesshop.ar/catalog-snapshot.js', {
      headers: {
        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
      },
      signal: AbortSignal.timeout(20000)
    })
    if (!res.ok) throw new Error(`HTTP ${res.status}`)
    const jsText = await res.text()

    const windowObj = {}
    const fn = new Function('window', jsText)
    fn(windowObj)
    const rawList = windowObj.DGS_PRODUCTS || []
    log(`✓ Snapshot descargado con ${rawList.length} títulos PlayStation`)

    const psCatalog = []
    const psImageMap = new Map()

    rawList.forEach(p => {
      const isArr = Array.isArray(p)
      const title = isArr ? p[1] : (p.title || '')
      const platform = isArr ? p[2] : (p.platform || 'PS5')
      const costPrimary = isArr ? p[3] : (p.price || 0)
      const costSecondary = isArr ? p[4] : (p.secondaryPrice || 0)
      let rawImg = isArr ? p[5] : (p.image || '')

      if (!title || costPrimary <= 0) return

      // Extraer y normalizar imagen oficial PlayStation Store Vulcan CDN
      let officialPsImage = rawImg
      if (officialPsImage && officialPsImage.includes('vulcan/')) {
        const idx = officialPsImage.indexOf('vulcan/')
        const pathPart = officialPsImage.slice(idx).split('?')[0]
        officialPsImage = `https://image.api.playstation.com/${pathPart}?w=800`
      } else if (!officialPsImage.startsWith('http')) {
        officialPsImage = 'https://images.unsplash.com/photo-1606813907291-d86efa9b94db?w=800&auto=format&fit=crop&q=80'
      }

      const norm = cleanTitleNormalization(title)
      if (officialPsImage.includes('playstation.com') && !psImageMap.has(norm)) {
        psImageMap.set(norm, officialPsImage)
      }

      psCatalog.push({
        source: 'dgs',
        title,
        norm,
        platform: platform || 'PS5',
        costPrimary,
        costSecondary: costSecondary || Math.round(costPrimary * 0.5),
        image: officialPsImage
      })
    })

    log(`✓ ${psCatalog.length} juegos procesados y ${psImageMap.size} portadas oficiales Vulcan indexadas`)
    return { catalog: psCatalog, imageMap: psImageMap }
  } catch (err) {
    log(`Advertencia en DGS scraper: ${err.message}. Continuando...`)
    return { catalog: [], imageMap: new Map() }
  }
}

/**
 * Scrapea Tu Tienda Gamer evitando consolas y accesorios, extrayendo título real, precio ARS y enlace
 */
async function scrapeTuTiendaGamer() {
  log('Scrapeando Tu Tienda Gamer con precios (excluyendo consolas y accesorios)...')
  const baseCategories = [
    { base: 'https://tutiendagamer.com.ar/juegos-ps5', platform: 'PS5' },
    { base: 'https://tutiendagamer.com.ar/juegos-ps4', platform: 'PS4' },
    { base: 'https://tutiendagamer.com.ar/juegos-ps3', platform: 'PS3' }
  ]
  const gamesMap = new Map()

  for (const cat of baseCategories) {
    for (let page = 1; page <= 3; page++) {
      const pageUrl = page === 1 ? cat.base : `${cat.base}?page=${page}`
      try {
        const res = await fetch(pageUrl, {
          headers: { 'User-Agent': 'Mozilla/5.0' },
          signal: AbortSignal.timeout(8000)
        })
        if (!res.ok) break
        const html = await res.text()
        const blocks = html.split('products-feed__product-name')
        if (blocks.length <= 1) break

        blocks.slice(1).forEach(b => {
          const linkMatch = b.match(/href="(https:\/\/[^"]*tutiendagamer\.com\.ar\/[^"]+)"[^>]*>([\s\S]*?)<\/a>/i)
          if (!linkMatch) return
          const link = linkMatch[1]
          // Regla explícita del usuario: Evitar consolas y accesorios
          if (/consolas?-y-accesorios|consola|accesorio|joystick|auricular|cable|funda/i.test(link)) {
            return
          }

          const rawTitle = decodeHtmlEntities(linkMatch[2].replace(/<[^>]+>/g, '').trim())
          const priceBlock = b.match(/<p class="products-feed__product-price[^>]*>([\s\S]*?)<\/p>/i)
          const cleanPriceHtml = priceBlock ? priceBlock[1].replace(/<del>[\s\S]*?<\/del>/gi, '') : ''
          const price = parseArsPrice(cleanPriceHtml)

          if (rawTitle.length > 2 && price > 0) {
            const cleanTitle = rawTitle.replace(/\s*[-–]\s*ps[345]\s*$/gi, '').trim()
            const norm = cleanTitleNormalization(cleanTitle)
            if (!gamesMap.has(norm + '_' + cat.platform)) {
              gamesMap.set(norm + '_' + cat.platform, {
                source: 'tutiendagamer',
                title: cleanTitle,
                norm,
                platform: cat.platform,
                price,
                url: link
              })
            }
          }
        })
      } catch (e) {
        log(`Fallo parcial TTG (${pageUrl}): ${e.message}`)
        break
      }
    }
  }

  const games = Array.from(gamesMap.values())
  log(`✓ Tu Tienda Gamer: ${games.length} videojuegos con precio extraídos sin accesorios`)
  return games
}

/**
 * Scrapea Fusion Games para juegos de PlayStation extrayendo título real, precio y enlace desde GTM JSON
 */
async function scrapeFusionGames() {
  log('Scrapeando Fusion Games con precios reales para PlayStation...')
  const categoryUrls = [
    { base: 'https://fusiongames.com.ar/categoria-producto/juegos-digitales-ps5/', platform: 'PS5' },
    { base: 'https://fusiongames.com.ar/categoria-producto/juegos-digitales-ps4/', platform: 'PS4' },
    { base: 'https://fusiongames.com.ar/categoria-producto/juegos-digitales-ps3/', platform: 'PS3' }
  ]
  const gamesMap = new Map()

  for (const cat of categoryUrls) {
    for (let page = 1; page <= 3; page++) {
      const pageUrl = page === 1 ? cat.base : `${cat.base}page/${page}/`
      try {
        const res = await fetch(pageUrl, {
          headers: { 'User-Agent': 'Mozilla/5.0' },
          signal: AbortSignal.timeout(8000)
        })
        if (!res.ok) break
        const html = await res.text()
        const gtmMatches = [...html.matchAll(/data-gtm4wp_product_data="([^"]+)"/gi)]
        if (gtmMatches.length === 0) break

        gtmMatches.forEach(m => {
          try {
            const parsed = JSON.parse(decodeHtmlEntities(m[1]))
            const rawTitle = (parsed.item_name || '').trim()
            const price = Number(parsed.price) || 0
            const link = parsed.productlink || ''
            if (!rawTitle || price <= 0 || /ps-plus|plus\s+(deluxe|extra|essential)/i.test(link)) return

            const cleanTitle = rawTitle.replace(/\s*\(?(ps5|ps4|ps3)\)?\s*/gi, ' ').trim()
            const norm = cleanTitleNormalization(cleanTitle)
            if (norm && !gamesMap.has(norm + '_' + cat.platform)) {
              gamesMap.set(norm + '_' + cat.platform, {
                source: 'fusiongames',
                title: cleanTitle,
                norm,
                platform: cat.platform,
                price,
                url: link
              })
            }
          } catch {}
        })
      } catch (e) {
        log(`Fallo parcial Fusion Games (${pageUrl}): ${e.message}`)
        break
      }
    }
  }

  const games = Array.from(gamesMap.values())
  log(`✓ Fusion Games: ${games.length} videojuegos con precio extraídos`)
  return games
}

/**
 * Parsea el archivo de ofertas .txt si existe en el escritorio
 */
function sanitizeBrokenEncodingTitle(str) {
  if (!str) return ''
  return str
    .replace(/\uFFFD\?/g, '')
    .replace(/ESPA\uFFFDOI|ESPA\uFFFDOL/gi, 'ESPAÑOL')
    .replace(/M\uFFFDs/gi, 'Más')
    .replace(/Ragnar\uFFFDk/gi, 'Ragnarök')
    .replace(/\uFFFD/g, '')
    .replace(/\s+/g, ' ')
    .trim()
}

function parseTxtOffers(psImageMap) {
  if (!fs.existsSync(TXT_FILE)) return []
  try {
    const buf = fs.readFileSync(TXT_FILE)
    let raw = buf.toString('utf-8')
    if (raw.includes('\uFFFD')) {
      raw = buf.toString('latin1')
    }
    const lines = raw.split(/\r?\n/).filter(l => l.trim() && !/OFERTAS/i.test(l) && !/HASTA/i.test(l))
    const results = []

    lines.forEach((l, idx) => {
      const cleanLine = l.replace(/^[^a-zA-Z0-9]+/, '').trim()
      const priceMatch = cleanLine.match(/[-–—:\s]*\$?\s*([\d]{1,3}(?:\.[\d]{3})*(?:,[\d]+)?|[\d]{4,7})\s*$/)
      if (!priceMatch) return

      const rawNum = priceMatch[1].replace(/\./g, '').replace(',', '.')
      const price = parseFloat(rawNum)
      const rawTitle = sanitizeBrokenEncodingTitle(cleanLine.slice(0, priceMatch.index).replace(/[-–—:\s]+$/, '').trim())
      if (!rawTitle || price <= 0) return

      let platform = 'PS4 / PS5'
      if (/\bPS5\b/i.test(rawTitle)) platform = 'PS5'
      else if (/\bPS4\b/i.test(rawTitle)) platform = 'PS4'
      else if (/\bPS3\b/i.test(rawTitle)) platform = 'PS3'

      const norm = cleanTitleNormalization(rawTitle)
      let bestImage = psImageMap.get(norm)
      if (!bestImage) {
        for (const [k, img] of psImageMap.entries()) {
          if (k.length >= 6 && (norm.includes(k) || k.includes(norm))) {
            bestImage = img
            break
          }
        }
      }

      results.push({
        id: `GAME-TXT-${idx + 1}`,
        title: rawTitle,
        norm,
        platform,
        costPrimary: price,
        costSecondary: Math.round(price * 0.55),
        image: bestImage || 'https://images.unsplash.com/photo-1606813907291-d86efa9b94db?w=800&auto=format&fit=crop&q=80'
      })
    })

    log(`✓ ${results.length} ofertas de juegos parseadas desde ${TXT_FILE}`)
    return results
  } catch (e) {
    log(`Advertencia leyendo ${TXT_FILE}: ${e.message}`)
    return []
  }
}

/**
 * Carga el catálogo original
 */
function loadExistingCatalog() {
  try {
    if (fs.existsSync(TARGET_GAMING_PRODUCTS_FILE)) {
      const data = JSON.parse(fs.readFileSync(TARGET_GAMING_PRODUCTS_FILE, 'utf-8'))
      if (data.length > 2000) {
        return data.slice(0, 960)
      }
      return data
    }
  } catch (e) {
    log(`Error leyendo catálogo existente: ${e.message}`)
  }
  return []
}

/**
 * Busca coincidencia inteligente de título en el mapa consolidado
 */
function findConsolidatedMatch(norm, rawTitle, consolidatedMap, tokenIndex) {
  if (norm && consolidatedMap.has(norm)) {
    return consolidatedMap.get(norm)
  }
  const tokens = cleanTokens(rawTitle)
  if (tokens.length < 2) return null

  let bestGame = null
  let bestJaccard = 0
  const setA = new Set(tokens)

  for (const entry of tokenIndex) {
    let intersection = 0
    for (const t of setA) {
      if (entry.tokenSet.has(t)) intersection++
    }
    if (intersection === 0) continue
    const union = setA.size + entry.tokenSet.size - intersection
    const jaccard = intersection / union
    if (jaccard >= 0.78 && jaccard > bestJaccard) {
      bestJaccard = jaccard
      bestGame = entry.game
    }
  }
  return bestGame
}

/**
 * Proceso Principal de Sincronización, Consolidación y Mejora Visual HD
 */
export async function scrapeAndSyncGamingCatalog() {
  log('Iniciando sincronización profunda de catálogos gaming...')

  // 1. Cargar catálogo actual y todas las fuentes web + .txt
  const existingCatalog = loadExistingCatalog()
  const { catalog: dgsCatalog, imageMap: psImageMap } = await scrapeDigitalGamesShop()
  const ttgGames = await scrapeTuTiendaGamer()
  const fusionGames = await scrapeFusionGames()
  const txtGames = parseTxtOffers(psImageMap)

  log(`Fuentes indexadas: ${existingCatalog.length} base + ${psImageMap.size} portadas PS Store Vulcan + ${dgsCatalog.length} DGS + ${ttgGames.length} TTG + ${fusionGames.length} Fusion + ${txtGames.length} TXT`)

  // 2. Mapa consolidado por título normalizado
  const consolidatedMap = new Map()
  const tokenIndex = []

  // 2.1 Primero insertar los títulos base y actualizar portadas oficiales Vulcan de PS Store
  let updatedImagesCount = 0
  existingCatalog.forEach(game => {
    const cleanTitle = sanitizeBrokenEncodingTitle(game.title)
    const norm = cleanTitleNormalization(cleanTitle)
    let bestImage = game.image
    if (psImageMap.has(norm)) {
      bestImage = psImageMap.get(norm)
      updatedImagesCount++
    } else {
      for (const [k, img] of psImageMap.entries()) {
        if (k.length >= 6 && (norm.includes(k) || k.includes(norm))) {
          bestImage = img
          updatedImagesCount++
          break
        }
      }
    }

    const { category, subcategory } = inferTaxonomy(cleanTitle)
    const badge = game.badge || inferBadge(cleanTitle)

    const publicGame = {
      ...game,
      title: cleanTitle,
      category,
      subcategory,
      badge,
      image: bestImage,
      providers: { ...(game.providers || {}) },
      visibleProviderName: 'PlayStation Store Oficial',
      visibleProviderShortName: 'PS Store',
      visibleProviderBadge: '🎮 PlayStation Store Oficial',
      visibleProviderTagColor: 'bg-indigo-500/20 text-indigo-300 border-indigo-500/30',
      primaryPriceLeaderName: 'PlayStation Store Oficial',
      secondaryPriceLeaderName: 'PlayStation Store Oficial'
    }

    if (consolidatedMap.has(norm)) {
      const existing = consolidatedMap.get(norm)
      const mergedProviders = { ...(existing.providers || {}), ...(publicGame.providers || {}) }
      const existingVal = Number(existing.effectivePrimaryPrice || existing.baseCostPrimary || 0)
      const newVal = Number(publicGame.effectivePrimaryPrice || publicGame.baseCostPrimary || 0)
      const winner = newVal > existingVal ? { ...existing, ...publicGame, providers: mergedProviders } : { ...publicGame, ...existing, providers: mergedProviders }
      consolidatedMap.set(norm, winner)
    } else {
      consolidatedMap.set(norm, publicGame)
    }
  })

  for (const [norm, g] of consolidatedMap.entries()) {
    tokenIndex.push({ norm, tokenSet: new Set(cleanTokens(g.title)), game: g })
  }

  log(`✓ Portadas oficiales de PlayStation Store aplicadas a ${updatedImagesCount} títulos del catálogo`)

  // 2.2 Incorporar ofertas de juegos.txt.txt (TANTO actualizar coincidencias existentes COMO agregar nuevos)
  let txtAddedCount = 0
  let txtMatchedCount = 0
  txtGames.forEach(tg => {
    const existingTarget = findConsolidatedMatch(tg.norm, tg.title, consolidatedMap, tokenIndex)
    if (existingTarget) {
      existingTarget.providers.mayorista_txt = {
        providerName: 'Lista Ofertas TXT',
        providerUrl: '',
        available: true,
        costPrimary: tg.costPrimary,
        costSecondary: tg.costSecondary,
        primaryPrice: tg.costPrimary,
        secondaryPrice: tg.costSecondary
      }
      txtMatchedCount++
    } else {
      const { category, subcategory } = inferTaxonomy(tg.title)
      const badge = inferBadge(tg.title)
      const markup = Math.ceil((tg.costPrimary * 0.20) / 100) * 100
      const pvpPrimary = tg.costPrimary + markup
      const pvpSecondary = tg.costSecondary + Math.ceil((tg.costSecondary * 0.20) / 100) * 100

      const newProduct = {
        id: tg.id,
        title: tg.title,
        slug: tg.title.toLowerCase().replace(/[^a-z0-9]/g, '-'),
        platform: tg.platform,
        category,
        subcategory,
        badge,
        image: tg.image,
        description: `Videojuego digital oficial para consolas PlayStation Network. Licencia oficial garantizada con audio en español y entrega inmediata.`,
        features: ['Audio Español Oficial', 'Entrega Digital Inmediata', 'Garantía Total StreamePro'],
        providers: {
          mayorista_txt: {
            providerName: 'Lista Ofertas TXT',
            providerUrl: '',
            available: true,
            costPrimary: tg.costPrimary,
            costSecondary: tg.costSecondary,
            primaryPrice: tg.costPrimary,
            secondaryPrice: tg.costSecondary
          }
        },
        baseCostPrimary: tg.costPrimary,
        baseCostSecondary: tg.costSecondary,
        effectivePrimaryPrice: pvpPrimary,
        effectiveSecondaryPrice: pvpSecondary,
        visibleProviderKey: 'mayorista_txt',
        visibleProviderName: 'PlayStation Store Oficial',
        visibleProviderShortName: 'PS Store',
        visibleProviderBadge: '🎮 PlayStation Store Oficial',
        visibleProviderTagColor: 'bg-indigo-500/20 text-indigo-300 border-indigo-500/30',
        primaryPriceLeaderName: 'PlayStation Store Oficial',
        secondaryPriceLeaderName: 'PlayStation Store Oficial'
      }

      consolidatedMap.set(tg.norm, newProduct)
      tokenIndex.push({ norm: tg.norm, tokenSet: new Set(cleanTokens(tg.title)), game: newProduct })
      txtAddedCount++
    }
  })
  log(`✓ Lista .TXT: ${txtMatchedCount} coincidencias vinculadas + ${txtAddedCount} nuevos juegos añadidos`)

  // 2.3 Añadir lanzamientos top adicionales de PS Store (ASTRO BOT, 007, WOLVERINE, FC 27, etc.)
  const priorityKeywords = ['astro bot', '007', 'wolverine', 'fc 27', 'silent hill', 'death stranding', 'gta 6', 'black ops', 'sparking', 'dragon ball', 'god of war']
  let topPsAdded = 0
  dgsCatalog.forEach(item => {
    const isPriority = priorityKeywords.some(kw => item.norm.includes(kw.replace(/[^a-z0-9]/g, '')))
    if (isPriority && !consolidatedMap.has(item.norm) && topPsAdded < 50) {
      const { category, subcategory } = inferTaxonomy(item.title)
      const badge = inferBadge(item.title)
      const markup = Math.ceil((item.costPrimary * 0.20) / 100) * 100
      const pvpPrimary = item.costPrimary + markup
      const pvpSecondary = item.costSecondary + Math.ceil((item.costSecondary * 0.20) / 100) * 100

      const newProduct = {
        id: `GAME-TOP-${item.norm.slice(0, 12)}`,
        title: item.title,
        slug: item.title.toLowerCase().replace(/[^a-z0-9]/g, '-'),
        platform: item.platform || 'PS5',
        category,
        subcategory,
        badge,
        image: item.image,
        description: `Videojuego digital oficial para consolas PlayStation Network. Licencia oficial con audio en español y entrega inmediata.`,
        features: ['Audio Español Oficial', 'Entrega Digital Inmediata', 'Garantía Total StreamePro'],
        providers: {
          digitalgamesshop: {
            providerName: 'Digital Games Shop',
            providerUrl: `https://digitalgamesshop.ar/?q=${encodeURIComponent(item.title)}`,
            available: true,
            costPrimary: item.costPrimary,
            costSecondary: item.costSecondary,
            primaryPrice: item.costPrimary,
            secondaryPrice: item.costSecondary
          }
        },
        baseCostPrimary: item.costPrimary,
        baseCostSecondary: item.costSecondary,
        effectivePrimaryPrice: pvpPrimary,
        effectiveSecondaryPrice: pvpSecondary,
        visibleProviderKey: 'digitalgamesshop',
        visibleProviderName: 'PlayStation Store Oficial',
        visibleProviderShortName: 'PS Store',
        visibleProviderBadge: '🎮 PlayStation Store Oficial',
        visibleProviderTagColor: 'bg-indigo-500/20 text-indigo-300 border-indigo-500/30',
        primaryPriceLeaderName: 'PlayStation Store Oficial',
        secondaryPriceLeaderName: 'PlayStation Store Oficial'
      }

      consolidatedMap.set(item.norm, newProduct)
      tokenIndex.push({ norm: item.norm, tokenSet: new Set(cleanTokens(item.title)), game: newProduct })
      topPsAdded++
    }
  })

  // 2.4 Fusionar precios de Digital Games Shop en todo el catálogo (incluyendo los recién agregados)
  let dgsMerged = 0
  dgsCatalog.forEach(item => {
    const target = consolidatedMap.get(item.norm)
    if (target && item.costPrimary > 0) {
      target.providers.digitalgamesshop = {
        providerName: 'Digital Games Shop',
        providerUrl: `https://digitalgamesshop.ar/?q=${encodeURIComponent(item.title)}`,
        available: true,
        costPrimary: item.costPrimary,
        costSecondary: item.costSecondary || Math.round(item.costPrimary * 0.55),
        primaryPrice: item.costPrimary,
        secondaryPrice: item.costSecondary || Math.round(item.costPrimary * 0.55)
      }
      if (item.image && item.image.includes('playstation.com') && (!target.image || !target.image.includes('playstation.com'))) {
        target.image = item.image
      }
      dgsMerged++
    }
  })
  log(`✓ Digital Games Shop: ${dgsMerged} coincidencias vinculadas con precio`)

  // 2.5 Fusionar precios de Tu Tienda Gamer en todo el catálogo (y agregar faltantes)
  let ttgMerged = 0
  let ttgAdded = 0
  ttgGames.forEach(item => {
    const target = findConsolidatedMatch(item.norm, item.title, consolidatedMap, tokenIndex)
    if (target && item.price > 0) {
      target.providers.tutiendagamer = {
        providerName: 'Tu Tienda Gamer',
        providerUrl: item.url,
        available: true,
        costPrimary: item.price,
        costSecondary: Math.round(item.price * 0.55),
        primaryPrice: item.price,
        secondaryPrice: Math.round(item.price * 0.55)
      }
      ttgMerged++
    } else if (item.price > 0 && item.norm) {
      const { category, subcategory } = inferTaxonomy(item.title)
      const badge = inferBadge(item.title)
      const bestImage = psImageMap.get(item.norm) || 'https://images.unsplash.com/photo-1606813907291-d86efa9b94db?w=800&auto=format&fit=crop&q=80'
      const newProduct = {
        id: `GAME-TTG-${item.norm.slice(0, 12)}`,
        title: item.title,
        slug: item.title.toLowerCase().replace(/[^a-z0-9]/g, '-'),
        platform: item.platform || 'PS5',
        category,
        subcategory,
        badge,
        image: bestImage,
        description: `Videojuego digital oficial para consolas PlayStation Network (${item.platform}). Licencia oficial garantizada con entrega inmediata.`,
        features: ['Audio Español Oficial', 'Entrega Digital Inmediata', 'Garantía Total StreamePro'],
        providers: {
          tutiendagamer: {
            providerName: 'Tu Tienda Gamer',
            providerUrl: item.url,
            available: true,
            costPrimary: item.price,
            costSecondary: Math.round(item.price * 0.55),
            primaryPrice: item.price,
            secondaryPrice: Math.round(item.price * 0.55)
          }
        },
        visibleProviderName: 'PlayStation Store Oficial',
        visibleProviderShortName: 'PS Store',
        visibleProviderBadge: '🎮 PlayStation Store Oficial',
        visibleProviderTagColor: 'bg-indigo-500/20 text-indigo-300 border-indigo-500/30',
        primaryPriceLeaderName: 'PlayStation Store Oficial',
        secondaryPriceLeaderName: 'PlayStation Store Oficial'
      }
      consolidatedMap.set(item.norm, newProduct)
      tokenIndex.push({ norm: item.norm, tokenSet: new Set(cleanTokens(item.title)), game: newProduct })
      ttgAdded++
    }
  })
  log(`✓ Tu Tienda Gamer: ${ttgMerged} coincidencias vinculadas + ${ttgAdded} añadidos`)

  // 2.6 Fusionar precios de Fusion Games en todo el catálogo (y agregar faltantes)
  let fusMerged = 0
  let fusAdded = 0
  fusionGames.forEach(item => {
    const target = findConsolidatedMatch(item.norm, item.title, consolidatedMap, tokenIndex)
    if (target && item.price > 0) {
      target.providers.fusiongames = {
        providerName: 'Fusion Games',
        providerUrl: item.url,
        available: true,
        costPrimary: item.price,
        costSecondary: Math.round(item.price * 0.55),
        primaryPrice: item.price,
        secondaryPrice: Math.round(item.price * 0.55)
      }
      fusMerged++
    } else if (item.price > 0 && item.norm) {
      const { category, subcategory } = inferTaxonomy(item.title)
      const badge = inferBadge(item.title)
      const bestImage = psImageMap.get(item.norm) || 'https://images.unsplash.com/photo-1606813907291-d86efa9b94db?w=800&auto=format&fit=crop&q=80'
      const newProduct = {
        id: `GAME-FUS-${item.norm.slice(0, 12)}`,
        title: item.title,
        slug: item.title.toLowerCase().replace(/[^a-z0-9]/g, '-'),
        platform: item.platform || 'PS4 / PS5',
        category,
        subcategory,
        badge,
        image: bestImage,
        description: `Videojuego digital oficial para consolas PlayStation Network (${item.platform}). Licencia oficial garantizada con entrega inmediata.`,
        features: ['Audio Español Oficial', 'Entrega Digital Inmediata', 'Garantía Total StreamePro'],
        providers: {
          fusiongames: {
            providerName: 'Fusion Games',
            providerUrl: item.url,
            available: true,
            costPrimary: item.price,
            costSecondary: Math.round(item.price * 0.55),
            primaryPrice: item.price,
            secondaryPrice: Math.round(item.price * 0.55)
          }
        },
        visibleProviderName: 'PlayStation Store Oficial',
        visibleProviderShortName: 'PS Store',
        visibleProviderBadge: '🎮 PlayStation Store Oficial',
        visibleProviderTagColor: 'bg-indigo-500/20 text-indigo-300 border-indigo-500/30',
        primaryPriceLeaderName: 'PlayStation Store Oficial',
        secondaryPriceLeaderName: 'PlayStation Store Oficial'
      }
      consolidatedMap.set(item.norm, newProduct)
      tokenIndex.push({ norm: item.norm, tokenSet: new Set(cleanTokens(item.title)), game: newProduct })
      fusAdded++
    }
  })
  log(`✓ Fusion Games: ${fusMerged} coincidencias vinculadas + ${fusAdded} añadidos`)

  // 2.7 Pasada final obligatoria: Recalcular MAYOR COSTO entre todos los sitios para fijar PVP público
  const consolidatedList = Array.from(consolidatedMap.values()).map(game => {
    const entries = Object.entries(game.providers || {})
      .filter(([, p]) => p && p.available !== false && (Number(p.costPrimary || p.primaryPrice || 0) > 0))
      .map(([k, p]) => ({
        key: k,
        name: p.providerName || k,
        url: p.providerUrl || '',
        costPrimary: Number(p.costPrimary || p.primaryPrice || 0),
        costSecondary: Number(p.costSecondary || p.secondaryPrice || Math.round(Number(p.costPrimary || p.primaryPrice || 0) * 0.55))
      }))

    if (entries.length > 0) {
      const priCosts = entries.map(e => e.costPrimary).filter(c => c > 0)
      const secCosts = entries.map(e => e.costSecondary).filter(c => c > 0)
      const maxPri = priCosts.length ? Math.max(...priCosts) : (game.baseCostPrimary || 20000)
      const minPri = priCosts.length ? Math.min(...priCosts) : maxPri
      const maxSec = secCosts.length ? Math.max(...secCosts) : (game.baseCostSecondary || 10000)
      const minSec = secCosts.length ? Math.min(...secCosts) : maxSec

      const highestEntry = entries.find(e => e.costPrimary === maxPri) || entries[0]
      const cheapestEntry = entries.find(e => e.costPrimary === minPri) || entries[0]

      const pvpPrimary = Math.ceil((maxPri * 1.20) / 100) * 100
      const pvpSecondary = Math.ceil((maxSec * 1.20) / 100) * 100

      return {
        ...game,
        baseCostPrimary: maxPri,
        baseCostSecondary: maxSec,
        effectivePrimaryPrice: pvpPrimary,
        effectiveSecondaryPrice: pvpSecondary,
        visibleCostPrimary: maxPri,
        visibleCostSecondary: maxSec,
        visiblePrimaryPrice: maxPri,
        visibleSecondaryPrice: maxSec,
        visibleProviderKey: highestEntry.key,
        visibleProviderUrl: highestEntry.url || game.visibleProviderUrl || '',
        primaryPriceLeaderKey: highestEntry.key,
        secondaryPriceLeaderKey: highestEntry.key,
        primaryPriceLeaderName: 'PlayStation Store Oficial',
        secondaryPriceLeaderName: 'PlayStation Store Oficial',
        visibleProviderName: 'PlayStation Store Oficial',
        visibleProviderShortName: 'PS Store',
        visibleProviderBadge: '🎮 PlayStation Store Oficial',
        bestFulfillmentPrimary: cheapestEntry.key,
        bestFulfillmentPrimaryName: cheapestEntry.name,
        bestMarginPrimary: Math.max(0, pvpPrimary - minPri),
        bestFulfillmentSecondary: cheapestEntry.key,
        bestFulfillmentSecondaryName: cheapestEntry.name,
        bestMarginSecondary: Math.max(0, pvpSecondary - minSec),
        diffPrimary: Math.max(0, maxPri - minPri),
        diffSecondary: Math.max(0, maxSec - minSec),
        similarityScore: entries.length >= 2 ? 100 : 70
      }
    }
    return game
  })

  log(`✓ Catálogo final consolidado: ${consolidatedList.length} videojuegos con MAYOR COSTO → PVP web actualizado`)

  // 3. Generar Categorías y Subcategorías Ordenadas
  const categoryOrder = [
    'Todas',
    'PS5',
    'PS4',
    'PS3',
    'Lanzamientos',
    'Acción & Aventura',
    'Deportes',
    'Lucha',
    'Rol & Aventura',
    'Survival Horror',
    'Plataformas',
    'Membresías'
  ]

  const categorySet = new Set(categoryOrder)
  consolidatedList.forEach(item => {
    if (item.category) categorySet.add(item.category)
  })
  const categoriesList = Array.from(categorySet)

  const subcatsByCat = {}
  categoriesList.forEach(c => {
    subcatsByCat[c] = new Set(['Todas'])
  })

  consolidatedList.forEach(item => {
    if (!item.subcategory) return
    subcatsByCat['Todas'].add(item.subcategory)
    if (item.category && subcatsByCat[item.category]) {
      subcatsByCat[item.category].add(item.subcategory)
    }
    if (item.platform && item.platform.includes('PS5')) {
      subcatsByCat['PS5'] = subcatsByCat['PS5'] || new Set(['Todas'])
      subcatsByCat['PS5'].add(item.subcategory)
    }
    if (item.platform && item.platform.includes('PS4')) {
      subcatsByCat['PS4'] = subcatsByCat['PS4'] || new Set(['Todas'])
      subcatsByCat['PS4'].add(item.subcategory)
    }
    if (item.platform && item.platform.includes('PS3')) {
      subcatsByCat['PS3'] = subcatsByCat['PS3'] || new Set(['Todas'])
      subcatsByCat['PS3'].add(item.subcategory)
    }
    if (item.badge && (item.badge.includes('MÁS VENDIDO') || item.badge.includes('JUEGO DEL AÑO') || item.badge.includes('LANZAMIENTO'))) {
      subcatsByCat['Lanzamientos'] = subcatsByCat['Lanzamientos'] || new Set(['Todas'])
      subcatsByCat['Lanzamientos'].add(item.subcategory)
    }
  })

  const formattedSubcats = {}
  for (const [k, v] of Object.entries(subcatsByCat)) {
    formattedSubcats[k] = Array.from(v)
  }

  // 4. Guardar archivos finales
  fs.writeFileSync(TARGET_GAMING_PRODUCTS_FILE, JSON.stringify(consolidatedList, null, 2), 'utf-8')
  fs.writeFileSync(TARGET_GAMING_CATEGORIES_FILE, JSON.stringify(categoriesList, null, 2), 'utf-8')
  fs.writeFileSync(TARGET_GAMING_SUBCATS_FILE, JSON.stringify(formattedSubcats, null, 2), 'utf-8')

  log(`✓ Archivo guardado: ${TARGET_GAMING_PRODUCTS_FILE} (${consolidatedList.length} videojuegos)`)
  log(`✓ Archivo guardado: ${TARGET_GAMING_CATEGORIES_FILE} (${categoriesList.length} categorías)`)
  log(`✓ Archivo guardado: ${TARGET_GAMING_SUBCATS_FILE}`)

  return {
    success: true,
    count: consolidatedList.length,
    products: consolidatedList,
    categories: categoriesList,
    subcategories: formattedSubcats
  }
}

// Ejecutar si se corre directamente
if (process.argv[1] === fileURLToPath(import.meta.url)) {
  scrapeAndSyncGamingCatalog().catch(err => {
    console.error('Error ejecutando scraper:', err)
    process.exit(1)
  })
}

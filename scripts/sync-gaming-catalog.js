/**
 * Script Oficial de Sincronización & Scraping Masivo Completo de Videojuegos Digitales (StreamePro)
 *
 * Fuentes Mayoristas Scrapeadas al Máximo:
 * - Digital Games Shop (https://digitalgamesshop.ar/catalog-snapshot.js) -> 14.200+ juegos PS5/PS4/PS3 + Vulcan CDN
 * - Argentina Play (https://www.argentinaplay.com.ar/) -> PS5, PS4, PS3, PC, PS Plus, Preventas (Primario & Secundario)
 * - Juegos Digitales Argentina (https://juegosdigitalesargentina.com/) -> PS5, PS4, PS3, Switch, Switch 2, Xbox, Membresías
 * - Tu Tienda Gamer (https://tutiendagamer.com.ar/) -> API Empretienda /v4/product/category (PS5/PS4/PS3/Xbox/Switch/PC)
 * - Fusion Games (https://fusiongames.com.ar/) -> API WooCommerce Store (PS5/PS4/PS3/Membresías)
 * - Lista de ofertas mayoristas (.txt de juegos)
 *
 * REGLA ESTRICTA DE PRIVACIDAD & MARCA:
 * - Hacia el cliente y la plataforma pública: Nunca se exhiben nombres de sitios terceros.
 * - En la administración interna: Se preservan los costos y URLs mayoristas para arbitraje y margen.
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

const FALLBACK_IMAGE = 'https://images.unsplash.com/photo-1606813907291-d86efa9b94db?w=800&auto=format&fit=crop&q=80'

function log(msg) {
  console.log(`[SCRAPER GAMING] ${msg}`)
}

function decodeHtmlEntities(str) {
  if (!str) return ''
  return String(str)
    .replace(/&#039;|&#39;|&apos;/g, "'")
    .replace(/&amp;/g, '&')
    .replace(/&quot;/g, '"')
    .replace(/&lt;/g, '<')
    .replace(/&gt;/g, '>')
    .replace(/&nbsp;/g, ' ')
    .replace(/&#(\d+);/g, (_, dec) => String.fromCharCode(dec))
}

function sanitizeBrokenEncodingTitle(str) {
  if (!str) return ''
  return decodeHtmlEntities(str)
    .replace(/\uFFFD\?/g, '')
    .replace(/ESPA\uFFFDOI|ESPA\uFFFDOL/gi, 'ESPAÑOL')
    .replace(/M\uFFFDs/gi, 'Más')
    .replace(/Ragnar\uFFFDk/gi, 'Ragnarök')
    .replace(/\uFFFD/g, '')
    .replace(/\s+/g, ' ')
    .trim()
}

function isPhysicalHardwareOrAccessory(title = '', url = '') {
  const s = `${title} ${url}`.toLowerCase()
  return /consolas?-y-accesorios|\/accesorios?\b|joystick|dualsense|dualshock|auricular|headset|cable\s+hdmi|funda\s|protector\s|cargador\s|estaci[oó]n\s+de\s+carga|control\s+inalambrico|microsd|volante\s+g29/i.test(s)
}

function getPlatformBucket(platform = '', title = '') {
  const s = `${platform} ${title}`.toLowerCase()
  if (s.includes('xbox')) return 'xbox'
  if (s.includes('switch') || s.includes('nintendo')) return 'switch'
  if (/\b(pc|steam)\b/i.test(s)) return 'pc'
  if (s.includes('ps3') && !s.includes('ps4') && !s.includes('ps5')) return 'ps3'
  return 'ps'
}

function supportsSecondary(platform = '') {
  return getPlatformBucket(platform) === 'ps'
}

function stripTitleNoiseForMatch(raw = '') {
  return sanitizeBrokenEncodingTitle(raw)
    .replace(/^\s*(?:juego\s+digital|juegos\s+digitales)\s*/i, '')
    .replace(/\s*\(?(?:primari[oa]|secundari[oa]|cuenta\s+primaria|cuenta\s+secundaria|digital\s+original|original|retro|espanol|latino|subtitulado|combo\s+x\d+)\)?\s*/gi, ' ')
    .replace(/\s*[-–|]+\s*(?:ps5|ps4|ps3|pc|xbox(?:\s+one|\s+series\s+x\|?s?)?|nintendo\s+switch(?:\s*2)?)\s*$/gi, '')
    .replace(/\s+/g, ' ')
    .trim()
}

function cleanTitleNormalization(raw) {
  if (!raw) return ''
  return sanitizeBrokenEncodingTitle(raw)
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
    .replace(/\b(ps5|ps4|ps3|nintendo|switch|xbox|one|series|steam|pc|digital|original|cuenta|primaria|primario|secundaria|secundario|edicion|edition|remastered|remake|standard|deluxe|gold|ultimate|complete|definitive|directors?\s*cut|next\s*gea?n|retro|bundle|pack|espanol|latino|subtitulado|gratis)\b/gi, ' ')
    .replace(/[^a-z0-9]/g, '')
    .trim()
}

function cleanTokens(raw) {
  if (!raw) return []
  return sanitizeBrokenEncodingTitle(raw)
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
    .replace(/\b(ps5|ps4|ps3|nintendo|switch|xbox|one|series|steam|pc|digital|original|cuenta|primaria|primario|secundaria|secundario|edicion|edition|remastered|remake|standard|deluxe|gold|ultimate|complete|definitive|directors?\s*cut|next\s*gea?n|retro|bundle|pack|espanol|latino|subtitulado)\b/gi, ' ')
    .replace(/[^a-z0-9\s]/g, ' ')
    .split(/\s+/)
    .filter(t => t.length >= 2)
}

function parseArsPrice(str) {
  if (typeof str === 'number') return Math.round(str)
  if (!str) return 0
  const m = String(str).match(/\$?\s*([\d]{1,3}(?:\.[\d]{3})+|\d{3,7})(?:,\d{1,2})?/)
  if (!m) return 0
  return Number(m[1].replace(/\./g, '')) || 0
}

function parseJdaPrice(str) {
  if (typeof str === 'number') return Math.round(str)
  if (!str) return 0
  const cleaned = String(str).replace(/&nbsp;/g, ' ').replace(/\s+/g, ' ').trim()
  // JDA suele usar coma como separador de miles ("$ 32,600 ARS" o "$ 1,400 ARS")
  const commaThousands = cleaned.match(/([\d]{1,3}(?:,[\d]{3})+)(?!\d)/)
  if (commaThousands) {
    return Number(commaThousands[1].replace(/,/g, '')) || 0
  }
  return parseArsPrice(cleaned)
}

function inferTaxonomy(title, platform = '') {
  const t = `${title} ${platform}`.toLowerCase()
  let category = 'Acción & Aventura'
  let subcategory = 'Aventura Cinematográfica'

  if (t.includes('xbox')) {
    return { category: 'Xbox', subcategory: t.includes('series') ? 'Xbox Series X|S' : 'Xbox One' }
  }
  if (t.includes('switch') || t.includes('nintendo')) {
    return { category: 'Nintendo Switch', subcategory: t.includes('switch 2') ? 'Nintendo Switch 2' : 'Nintendo Switch' }
  }
  if (/\b(pc|steam)\b/i.test(t) && !t.includes('ps4') && !t.includes('ps5')) {
    return { category: 'PC', subcategory: 'PC / Steam Digital' }
  }

  if (t.includes('ps plus') || t.includes('playstation plus') || t.includes('membres') || t.includes('gift card') || t.includes('suscripcion') || /\bpsn\b/.test(t) || t.includes('game pass')) {
    category = 'Membresías'
    if (t.includes('deluxe')) subcategory = 'PlayStation Plus Deluxe'
    else if (t.includes('extra')) subcategory = 'PlayStation Plus Extra'
    else if (t.includes('essential')) subcategory = 'PlayStation Plus Essential'
    else subcategory = 'Suscripciones & Tarjetas'
  } else if (t.includes('fc 2') || t.includes('fifa') || t.includes('pes') || t.includes('turismo') || t.includes('nba') || t.includes('f1') || t.includes('wwe') || t.includes('madden') || t.includes('deporte') || t.includes('racing') || t.includes('rally') || t.includes('forza') || t.includes('motogp') || t.includes('mx vs atv') || t.includes('wrc') || t.includes('need for speed')) {
    category = 'Deportes'
    if (t.includes('fc') || t.includes('fifa') || t.includes('pes') || t.includes('tsubasa')) subcategory = 'Fútbol (FC / FIFA)'
    else if (t.includes('turismo') || t.includes('f1') || t.includes('carrera') || t.includes('racing') || t.includes('rally') || t.includes('wrc') || t.includes('motogp') || t.includes('need for speed')) subcategory = 'Carreras & Conducción'
    else subcategory = 'Básquet & Otros Deportes'
  } else if (t.includes('kombat') || t.includes('dragon ball') || t.includes('sparking') || t.includes('tekken') || t.includes('street fighter') || t.includes('naruto') || t.includes('lucha') || t.includes('fight') || t.includes('budokai') || t.includes('guilty') || t.includes('ufc') || t.includes('king of fighters')) {
    category = 'Lucha'
    if (t.includes('dragon ball') || t.includes('naruto') || t.includes('sparking') || t.includes('anime')) subcategory = 'Anime Fighting'
    else if (t.includes('kombat')) subcategory = 'Torneo & Mortal Kombat'
    else subcategory = 'Combate 3D'
  } else if (t.includes('resident evil') || t.includes('silent hill') || t.includes('dead space') || t.includes('alan wake') || t.includes('terror') || t.includes('horror') || t.includes('outlast') || t.includes('evil within') || t.includes('dead island') || t.includes('dayz') || t.includes('the forest') || t.includes('quarry') || t.includes('dying light')) {
    category = 'Survival Horror'
    if (t.includes('silent hill') || t.includes('alan wake') || t.includes('evil within')) subcategory = 'Terror Psicológico'
    else if (t.includes('resident evil') || t.includes('dead space') || t.includes('dead island') || t.includes('dayz') || t.includes('dying light')) subcategory = 'Zombies & Survival'
    else subcategory = 'Misterio & Terror'
  } else if (t.includes('elden ring') || t.includes('hogwarts') || t.includes('wukong') || t.includes('cyberpunk') || t.includes('witcher') || t.includes('souls') || t.includes('final fantasy') || t.includes('baldurs') || t.includes('dune') || t.includes('skyrim') || t.includes('diablo') || t.includes('dragon age') || t.includes('blasphemous') || t.includes('persona ') || t.includes('expedition 33')) {
    category = 'Rol & Aventura'
    if (t.includes('elden ring') || t.includes('souls') || t.includes('wukong') || t.includes('blasphemous')) subcategory = 'Fantasía Oscura & Souls'
    else if (t.includes('cyberpunk') || t.includes('dune') || t.includes('starfield')) subcategory = 'Sci-Fi RPG'
    else subcategory = 'Mundos de Fantasía'
  } else if (t.includes('astro bot') || t.includes('crash') || t.includes('mario') || t.includes('sonic') || t.includes('minecraft') || t.includes('lego') || t.includes('rayman') || t.includes('sackboy') || t.includes('ratchet') || t.includes('overcooked') || t.includes('little nightmares') || t.includes('it takes two') || t.includes('split fiction')) {
    category = 'Plataformas'
    if (t.includes('lego')) subcategory = 'Lego & Animación'
    else if (t.includes('minecraft') || t.includes('overcooked') || t.includes('it takes two') || t.includes('split fiction')) subcategory = 'Familiar & Coop'
    else subcategory = 'Aventuras 3D'
  } else {
    category = 'Acción & Aventura'
    if (t.includes('gta') || t.includes('grand theft auto') || t.includes('red dead') || t.includes('mafia') || t.includes('yakuza') || t.includes('judgment') || t.includes('far cry') || t.includes('just cause')) subcategory = 'Mundo Abierto'
    else if (t.includes('call of duty') || t.includes('battlefield') || t.includes('ghost recon') || t.includes('wolfenstein') || t.includes('doom') || t.includes('arma') || t.includes('sniper') || t.includes('helldivers')) subcategory = 'Shooters & Bélico'
    else if (t.includes('spider-man') || t.includes('batman') || t.includes('wolverine') || t.includes('marvel')) subcategory = 'Superhéroes'
    else if (t.includes('god of war') || t.includes('last of us') || t.includes('death stranding') || t.includes('assassin') || t.includes('007') || t.includes('hitman') || t.includes('metal gear') || t.includes('pragmata') || t.includes('crimson desert')) subcategory = 'Aventura Cinematográfica'
    else subcategory = 'Acción General'
  }

  return { category, subcategory }
}

function inferBadge(cleanTitle, platform = '') {
  const t = cleanTitle.toLowerCase()
  if (t.includes('fc 27') || t.includes('fc 26') || t.includes('fc 25') || t.includes('gta 5') || t.includes('gta v') || t.includes('wukong') || t.includes('black ops')) return '⚽ MÁS VENDIDO'
  if (t.includes('astro bot') || t.includes('elden ring') || t.includes('god of war') || t.includes('last of us') || t.includes('silent hill 2') || t.includes('expedition 33')) return '🏆 JUEGO DEL AÑO'
  if (t.includes('007') || t.includes('death stranding 2') || t.includes('wolverine') || t.includes('silent hill f') || t.includes('gta 6') || t.includes('pragmata') || t.includes('resident evil 9') || t.includes('resident evil requiem')) return '🔥 LANZAMIENTO'
  if (String(platform).includes('Xbox')) return '💚 XBOX OFICIAL'
  if (String(platform).includes('Switch')) return '❤️ NINTENDO OFICIAL'
  if (String(platform).includes('PC')) return '🖥️ PC DIGITAL'
  return '⭐️ PLAYSTATION OFICIAL'
}

async function runPool(items, concurrency, fn) {
  const results = []
  let idx = 0
  async function worker() {
    while (idx < items.length) {
      const current = idx++
      try {
        const res = await fn(items[current], current)
        if (res !== undefined) results.push(res)
      } catch {}
    }
  }
  const workers = Array.from({ length: Math.min(concurrency, items.length) }, () => worker())
  await Promise.all(workers)
  return results
}

/**
 * 1. Digital Games Shop — 14.200+ juegos PS5 / PS4 / PS3 + Vulcan CDN PS Store
 */
async function scrapeDigitalGamesShop() {
  log('Descargando catálogo completo de Digital Games Shop...')
  try {
    const res = await fetch('https://digitalgamesshop.ar/catalog-snapshot.js', {
      headers: { 'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36' },
      signal: AbortSignal.timeout(25000)
    })
    if (!res.ok) throw new Error(`HTTP ${res.status}`)
    const jsText = await res.text()

    const windowObj = {}
    new Function('window', jsText)(windowObj)
    const rawList = windowObj.DGS_PRODUCTS || []

    const psCatalog = []
    const psImageMap = new Map()

    rawList.forEach(p => {
      const isArr = Array.isArray(p)
      const title = sanitizeBrokenEncodingTitle(isArr ? p[1] : (p.title || ''))
      const platform = isArr ? p[2] : (p.platform || 'PS5')
      const costPrimary = Number(isArr ? p[3] : (p.price || 0))
      const rawSec = Number(isArr ? p[4] : (p.secondaryPrice || 0))
      let rawImg = isArr ? p[5] : (p.image || '')

      if (!title || costPrimary <= 100 || isPhysicalHardwareOrAccessory(title)) return

      let officialPsImage = rawImg
      if (officialPsImage && officialPsImage.includes('vulcan/')) {
        const idx = officialPsImage.indexOf('vulcan/')
        const pathPart = officialPsImage.slice(idx).split('?')[0]
        officialPsImage = `https://image.api.playstation.com/${pathPart}?w=800`
      } else if (!officialPsImage || !officialPsImage.startsWith('http')) {
        officialPsImage = FALLBACK_IMAGE
      }

      const norm = cleanTitleNormalization(title)
      if (!norm) return
      if (officialPsImage.includes('playstation.com') && !psImageMap.has(norm)) {
        psImageMap.set(norm, officialPsImage)
      }

      const allowSec = supportsSecondary(platform)
      const costSecondary = allowSec ? (rawSec > 0 ? rawSec : Math.round(costPrimary * 0.55)) : 0

      psCatalog.push({
        source: 'digitalgamesshop',
        title,
        norm,
        bucket: getPlatformBucket(platform, title),
        platform: platform || 'PS5',
        costPrimary,
        costSecondary,
        image: officialPsImage
      })
    })

    log(`✓ Digital Games Shop: ${psCatalog.length} títulos descargados y ${psImageMap.size} portadas Vulcan`)
    return { catalog: psCatalog, imageMap: psImageMap }
  } catch (err) {
    log(`Advertencia DGS: ${err.message}`)
    return { catalog: [], imageMap: new Map() }
  }
}

/**
 * 2. Argentina Play — Paginación profunda TiendaNube (?results_only=true&limit=48)
 */
async function scrapeArgentinaPlay() {
  log('Scrapeando catálogo completo de Argentina Play (PS5/PS4/PS3/PC/Plus/Preventas)...')
  const categories = [
    { slug: 'juegos-digitales-ps5', platform: 'PS5' },
    { slug: 'juegos-digitales-ps4', platform: 'PS4' },
    { slug: 'juegos-digitales-ps3', platform: 'PS3' },
    { slug: 'juegos-pc', platform: 'PC' },
    { slug: 'ps-plus', platform: 'PS4 / PS5' },
    { slug: 'preventas', platform: 'PS5' }
  ]

  const mergedByKey = new Map()

  for (const cat of categories) {
    let prevSig = ''
    for (let page = 1; page <= 65; page++) {
      const url = `https://www.argentinaplay.com.ar/${cat.slug}/page/${page}/?results_only=true&limit=48`
      try {
        const res = await fetch(url, {
          headers: { 'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36' },
          signal: AbortSignal.timeout(15000)
        })
        if (!res.ok) break
        const html = await res.text()
        const blocks = html.split('js-item-product')
        if (blocks.length <= 1) break

        let pageSig = ''
        let pageAdded = 0
        for (let i = 1; i < blocks.length; i++) {
          const b = blocks[i]
          const hrefRaw = b.match(/href="(https:\/\/www\.argentinaplay\.com\.ar\/productos\/[^"]+)"/i)?.[1] || ''
          const href = hrefRaw.split('?')[0]
          if (!href) continue
          if (i === 1) pageSig = href

          const rawTitle = sanitizeBrokenEncodingTitle(
            b.match(/js-item-name[^>]*>([\s\S]*?)<\/div>/i)?.[1]?.replace(/<[^>]+>/g, '')
            || b.match(/title="([^"]+)"/i)?.[1]
            || ''
          )
          if (!rawTitle || isPhysicalHardwareOrAccessory(rawTitle, href)) continue

          // Evitar combos de dinero/tarjetas o regalos físicos si no corresponde, pero sí mantener juegos/plus
          const priceStr = b.match(/js-price-display[^>]*>([\s\S]*?)<\/span>/i)?.[1]
            || b.match(/\$[\d.]+(?:,\d{2})?/)?.[0]
            || ''
          const price = parseArsPrice(priceStr)
          if (price <= 100) continue

          let img = b.match(/data-srcset="([^",\s]+)/i)?.[1]
            || b.match(/srcset="([^",\s]+)/i)?.[1]
            || b.match(/src="(https?:\/\/[^"]+|\/\/acdn[^"]+)"/i)?.[1]
            || ''
          if (img.startsWith('//')) img = `https:${img}`

          const isSecondary = /\bsecundari[oa]\b/i.test(rawTitle) || /secundari[oa]/i.test(href)
          const cleanDisplayTitle = stripTitleNoiseForMatch(rawTitle) || rawTitle
          const norm = cleanTitleNormalization(cleanDisplayTitle)
          if (!norm) continue

          const bucket = getPlatformBucket(cat.platform, cleanDisplayTitle)
          const key = `${bucket}::${norm}`
          const existing = mergedByKey.get(key) || {
            source: 'argentinaplay',
            title: cleanDisplayTitle,
            norm,
            bucket,
            platform: cat.platform,
            costPrimary: 0,
            costSecondary: 0,
            url: href,
            image: img
          }
          if (isSecondary && supportsSecondary(cat.platform)) {
            existing.costSecondary = Math.max(existing.costSecondary || 0, price)
          } else {
            existing.costPrimary = Math.max(existing.costPrimary || 0, price)
            existing.url = href
            if (img) existing.image = img
          }
          mergedByKey.set(key, existing)
          pageAdded++
        }

        if (!pageSig || pageSig === prevSig || pageAdded === 0) break
        prevSig = pageSig
      } catch (e) {
        log(`Fallo parcial Argentina Play (${cat.slug} p${page}): ${e.message}`)
        break
      }
    }
  }

  const results = Array.from(mergedByKey.values()).filter(x => x.costPrimary > 0 || x.costSecondary > 0)
  log(`✓ Argentina Play: ${results.length} títulos consolidados (Primaria/Secundaria)`)
  return results
}

/**
 * 3. Juegos Digitales Argentina — Paginación completa todas las consolas
 */
async function scrapeJuegosDigitalesArgentina() {
  log('Scrapeando catálogo completo de Juegos Digitales Argentina (PS5/PS4/PS3/Switch/Xbox/Plus)...')
  const categories = [
    { slug: 'juegos-digitales-ps5', platform: 'PS5' },
    { slug: 'juegos-digitales-ps4', platform: 'PS4' },
    { slug: 'juegos-digitales-ps3', platform: 'PS3' },
    { slug: 'nintendo-switch', platform: 'Nintendo Switch' },
    { slug: 'nintendo-switch-2', platform: 'Nintendo Switch 2' },
    { slug: 'xbox', platform: 'Xbox' },
    { slug: 'membresias', platform: 'PS4 / PS5' }
  ]

  const mergedByKey = new Map()

  const parseJdaPageHtml = (html, defaultPlatform) => {
    const blocks = html.split(/<div[^>]*class="[^"]*\bproduct-col\b[^"]*"/i)
    const extracted = []
    for (let i = 1; i < blocks.length; i++) {
      const b = blocks[i]
      const url = b.match(/href="(https?:\/\/juegosdigitalesargentina\.com\/productos\/[^"]+)"/i)?.[1] || ''
      const rawTitle = sanitizeBrokenEncodingTitle(b.match(/<h4[^>]*class="[^"]*\bnombre\b[^"]*"[^>]*>([\s\S]*?)<\/h4>/i)?.[1]?.replace(/<[^>]+>/g, '') || '')
      if (!url || !rawTitle || isPhysicalHardwareOrAccessory(rawTitle, url)) continue

      const priceMatch = b.match(/st-new-price[^>]*>([\s\S]*?)<\/div>/i)?.[1] || b.match(/price-new[^>]*>([\s\S]*?)<\/div>/i)?.[1] || b.match(/\$[^<]{1,25}ARS/i)?.[0] || ''
      const price = parseJdaPrice(priceMatch)
      if (price <= 100) continue

      const imgRaw = b.match(/class="image"[\s\S]{0,350}?(?:data-src|src)="([^"]+)"/i)?.[1]
        || b.match(/src="(https?:\/\/juegosdigitalesargentina\.com\/files\/images\/(?:thumbs\/productos_|productos\/)[^"]+)"/i)?.[1]
        || ''
      const img = imgRaw.startsWith('http') ? imgRaw : (imgRaw ? `https://juegosdigitalesargentina.com/${imgRaw.replace(/^\/+/, '')}` : '')

      let platform = defaultPlatform
      if (/switch\s*2/i.test(rawTitle)) platform = 'Nintendo Switch 2'
      else if (/xbox/i.test(rawTitle)) platform = 'Xbox'

      const isSec = /\bsecundari[oa]\b/i.test(rawTitle) || /secundari[oa]/i.test(url)
      const cleanTitle = stripTitleNoiseForMatch(rawTitle) || rawTitle
      const norm = cleanTitleNormalization(cleanTitle)
      if (!norm) continue

      extracted.push({ title: cleanTitle, norm, platform, price, isSec, url, img })
    }
    return extracted
  }

  const upsertJda = (item) => {
    const bucket = getPlatformBucket(item.platform, item.title)
    const key = `${bucket}::${item.norm}`
    const existing = mergedByKey.get(key) || {
      source: 'juegosdigitalesargentina',
      title: item.title,
      norm: item.norm,
      bucket,
      platform: item.platform,
      costPrimary: 0,
      costSecondary: 0,
      url: item.url,
      image: item.img
    }
    if (item.isSec && supportsSecondary(item.platform)) {
      existing.costSecondary = Math.max(existing.costSecondary || 0, item.price)
    } else {
      existing.costPrimary = Math.max(existing.costPrimary || 0, item.price)
      if (item.img && !existing.image) existing.image = item.img
    }
    mergedByKey.set(key, existing)
  }

  for (const cat of categories) {
    try {
      const baseUrl = `https://juegosdigitalesargentina.com/categorias/${cat.slug}`
      const res0 = await fetch(baseUrl, {
        headers: { 'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36' },
        signal: AbortSignal.timeout(15000)
      })
      if (!res0.ok) continue
      const html0 = await res0.text()
      parseJdaPageHtml(html0, cat.platform).forEach(upsertJda)

      const offsets = [...html0.matchAll(new RegExp(`/categorias/${cat.slug}/(\\d+)`, 'gi'))].map(m => Number(m[1]))
      const maxOffset = offsets.length ? Math.max(...offsets) : 0
      const pageOffsets = []
      for (let off = 24; off <= maxOffset; off += 24) {
        pageOffsets.push(off)
      }

      await runPool(pageOffsets, 8, async (off) => {
        const res = await fetch(`${baseUrl}/${off}`, {
          headers: { 'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36' },
          signal: AbortSignal.timeout(15000)
        })
        if (!res.ok) return
        const html = await res.text()
        parseJdaPageHtml(html, cat.platform).forEach(upsertJda)
      })
    } catch (e) {
      log(`Fallo parcial JDA (${cat.slug}): ${e.message}`)
    }
  }

  const results = Array.from(mergedByKey.values()).filter(x => x.costPrimary > 0 || x.costSecondary > 0)
  log(`✓ Juegos Digitales Argentina: ${results.length} títulos extraídos (PS5/PS4/PS3/Switch/Xbox/Plus)`)
  return results
}

/**
 * 4. Tu Tienda Gamer — API JSON /v4/product/category (con Cookie + CSRF)
 */
async function scrapeTuTiendaGamer() {
  log('Scrapeando catálogo completo de Tu Tienda Gamer vía API JSON...')
  const baseCategories = [
    { slug: 'juegos-ps5', platform: 'PS5' },
    { slug: 'juegos-ps4', platform: 'PS4' },
    { slug: 'juegos-ps3', platform: 'PS3' },
    { slug: 'juegos-xbox', platform: 'Xbox' },
    { slug: 'xbox-series-x-s', platform: 'Xbox Series X|S' },
    { slug: 'juegos-pc', platform: 'PC' },
    { slug: 'nintendo-switch', platform: 'Nintendo Switch' }
  ]
  const gamesMap = new Map()

  for (const cat of baseCategories) {
    const catUrl = `https://tutiendagamer.com.ar/${cat.slug}`
    try {
      const res1 = await fetch(catUrl, {
        headers: { 'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36' },
        signal: AbortSignal.timeout(15000)
      })
      if (!res1.ok) continue
      const setCookies = typeof res1.headers.getSetCookie === 'function' ? res1.headers.getSetCookie() : []
      const cookieHeader = setCookies.map(c => c.split(';')[0]).join('; ')
      const html = await res1.text()
      const csrf = html.match(/name="csrf-token"\s+content="([^"]+)"/i)?.[1]
        || html.match(/csrf_token\s*=\s*['"]([^'"]+)['"]/i)?.[1]
        || ''
      const idsMatch = html.match(/var\s+ids\s*=\s*(\[[^\]]+\])/i)?.[1]
      if (!idsMatch) continue
      const idsArr = JSON.parse(idsMatch)
      const qs = idsArr.map(id => `filter_categories[]=${id}`).join('&')

      for (let page = 0; page < 80; page++) {
        const apiRes = await fetch(`https://tutiendagamer.com.ar/v4/product/category?filter_page=${page}&filter_order=4&${qs}`, {
          headers: {
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrf,
            'Cookie': cookieHeader,
            'Referer': catUrl
          },
          signal: AbortSignal.timeout(15000)
        })
        if (!apiRes.ok) break
        const payload = await apiRes.json()
        const items = payload?.data || []
        if (!Array.isArray(items) || items.length === 0) break

        items.forEach(item => {
          if (item.p_desactivado) return
          const rawTitle = sanitizeBrokenEncodingTitle(item.p_nombre || '')
          const slug = String(item.p_link || '').trim()
          const prodUrl = slug ? `https://tutiendagamer.com.ar/${cat.slug}/${slug}` : catUrl
          if (!rawTitle || isPhysicalHardwareOrAccessory(rawTitle, prodUrl)) return

          let pricePrimary = (item.p_oferta === 1 && Number(item.p_precio_oferta) > 0)
            ? Number(item.p_precio_oferta)
            : Number(item.p_precio || 0)
          let priceSecondary = 0

          if (Array.isArray(item.stock)) {
            item.stock.forEach(s => {
              const sPrice = (s.s_oferta === 1 && Number(s.s_precio_oferta) > 0) ? Number(s.s_precio_oferta) : Number(s.s_precio || 0)
              const attrStr = JSON.stringify(s.valoratributo || '').toLowerCase()
              if (sPrice > 100 && attrStr.includes('secundar') && supportsSecondary(cat.platform)) {
                priceSecondary = priceSecondary ? Math.min(priceSecondary, sPrice) : sPrice
              } else if (sPrice > 100 && attrStr.includes('primar')) {
                pricePrimary = sPrice
              }
            })
          }

          if (pricePrimary <= 100) return

          const imgPath = item.imagenes?.[0]?.i_link || ''
          const img = imgPath
            ? (imgPath.startsWith('http') ? imgPath : `https://d22fxaf9t8d39k.cloudfront.net/${imgPath}`)
            : ''

          const cleanTitle = stripTitleNoiseForMatch(rawTitle) || rawTitle
          const norm = cleanTitleNormalization(cleanTitle)
          if (!norm) return
          const bucket = getPlatformBucket(cat.platform, cleanTitle)
          const mapKey = `${bucket}::${norm}`

          if (!gamesMap.has(mapKey)) {
            gamesMap.set(mapKey, {
              source: 'tutiendagamer',
              title: cleanTitle,
              norm,
              bucket,
              platform: cat.platform,
              costPrimary: pricePrimary,
              costSecondary: priceSecondary,
              url: prodUrl,
              image: img
            })
          }
        })
      }
    } catch (e) {
      log(`Fallo parcial TTG (${cat.slug}): ${e.message}`)
    }
  }

  const games = Array.from(gamesMap.values())
  log(`✓ Tu Tienda Gamer: ${games.length} juegos digitales extraídos sin accesorios`)
  return games
}

/**
 * 5. Fusion Games — API oficial WooCommerce Store (100 por página)
 */
async function scrapeFusionGames() {
  log('Scrapeando catálogo completo de Fusion Games vía API WooCommerce...')
  const gamesMap = new Map()

  try {
    const first = await fetch('https://fusiongames.com.ar/wp-json/wc/store/v1/products?per_page=100&page=1', {
      headers: { 'User-Agent': 'Mozilla/5.0' },
      signal: AbortSignal.timeout(15000)
    })
    if (!first.ok) throw new Error(`HTTP ${first.status}`)
    const totalPages = Math.min(40, Math.max(1, Number(first.headers.get('x-wp-totalpages')) || 1))

    const processPageItems = (list) => {
      if (!Array.isArray(list)) return
      list.forEach(item => {
        const rawTitle = sanitizeBrokenEncodingTitle(item.name || '')
        const url = item.permalink || ''
        if (!rawTitle || isPhysicalHardwareOrAccessory(rawTitle, url)) return

        const minor = Number(item.prices?.currency_minor_unit ?? 2)
        const price = Math.round((Number(item.prices?.price || 0)) / (10 ** minor))
        if (price <= 100) return

        const catsText = (item.categories || []).map(c => `${c.slug} ${c.name}`).join(' ').toLowerCase()
        let platform = 'PS4 / PS5'
        if (catsText.includes('ps3') && !catsText.includes('ps4') && !catsText.includes('ps5')) platform = 'PS3'
        else if (catsText.includes('ps5') && !catsText.includes('ps4')) platform = 'PS5'
        else if (catsText.includes('ps4') && !catsText.includes('ps5')) platform = 'PS4'
        else if (/\bPS5\b/i.test(rawTitle) && !/\bPS4\b/i.test(rawTitle)) platform = 'PS5'
        else if (/\bPS4\b/i.test(rawTitle) && !/\bPS5\b/i.test(rawTitle)) platform = 'PS4'
        else if (/\bPS3\b/i.test(rawTitle)) platform = 'PS3'

        const cleanTitle = stripTitleNoiseForMatch(rawTitle) || rawTitle
        const norm = cleanTitleNormalization(cleanTitle)
        if (!norm) return
        const bucket = getPlatformBucket(platform, cleanTitle)
        const key = `${bucket}::${norm}`

        if (!gamesMap.has(key)) {
          gamesMap.set(key, {
            source: 'fusiongames',
            title: cleanTitle,
            norm,
            bucket,
            platform,
            costPrimary: price,
            costSecondary: 0,
            url,
            image: item.images?.[0]?.src || ''
          })
        }
      })
    }

    processPageItems(await first.json())

    const remainingPages = []
    for (let p = 2; p <= totalPages; p++) remainingPages.push(p)

    await runPool(remainingPages, 5, async (page) => {
      const r = await fetch(`https://fusiongames.com.ar/wp-json/wc/store/v1/products?per_page=100&page=${page}`, {
        headers: { 'User-Agent': 'Mozilla/5.0' },
        signal: AbortSignal.timeout(15000)
      })
      if (!r.ok) return
      processPageItems(await r.json())
    })
  } catch (e) {
    log(`Advertencia Fusion Games: ${e.message}`)
  }

  const games = Array.from(gamesMap.values())
  log(`✓ Fusion Games: ${games.length} videojuegos con precio extraídos`)
  return games
}

/**
 * 6. Ofertas de escritorio .txt
 */
function parseTxtOffers(psImageMap) {
  if (!fs.existsSync(TXT_FILE)) return []
  try {
    const buf = fs.readFileSync(TXT_FILE)
    let raw = buf.toString('utf-8')
    if (raw.includes('\uFFFD')) raw = buf.toString('latin1')
    const lines = raw.split(/\r?\n/).filter(l => l.trim() && !/OFERTAS/i.test(l) && !/HASTA/i.test(l))
    const results = []

    lines.forEach((l, idx) => {
      const cleanLine = l.replace(/^[^a-zA-Z0-9]+/, '').trim()
      const priceMatch = cleanLine.match(/[-–—:\s]*\$?\s*([\d]{1,3}(?:\.[\d]{3})*(?:,[\d]+)?|[\d]{4,7})\s*$/)
      if (!priceMatch) return

      const rawNum = priceMatch[1].replace(/\./g, '').replace(',', '.')
      const price = parseFloat(rawNum)
      const rawTitle = sanitizeBrokenEncodingTitle(cleanLine.slice(0, priceMatch.index).replace(/[-–—:\s]+$/, '').trim())
      if (!rawTitle || price <= 100) return

      let platform = 'PS4 / PS5'
      if (/\bPS5\b/i.test(rawTitle)) platform = 'PS5'
      else if (/\bPS4\b/i.test(rawTitle)) platform = 'PS4'
      else if (/\bPS3\b/i.test(rawTitle)) platform = 'PS3'

      const norm = cleanTitleNormalization(rawTitle)
      if (!norm) return
      const bestImage = psImageMap.get(norm) || FALLBACK_IMAGE

      results.push({
        id: `GAME-TXT-${idx + 1}`,
        source: 'mayorista_txt',
        title: rawTitle,
        norm,
        bucket: getPlatformBucket(platform, rawTitle),
        platform,
        costPrimary: price,
        costSecondary: supportsSecondary(platform) ? Math.round(price * 0.55) : 0,
        url: '',
        image: bestImage
      })
    })

    log(`✓ Lista .TXT: ${results.length} ofertas parseadas`)
    return results
  } catch (e) {
    log(`Advertencia leyendo ${TXT_FILE}: ${e.message}`)
    return []
  }
}

function loadExistingCatalog() {
  try {
    if (fs.existsSync(TARGET_GAMING_PRODUCTS_FILE)) {
      const data = JSON.parse(fs.readFileSync(TARGET_GAMING_PRODUCTS_FILE, 'utf-8'))
      return Array.isArray(data) ? data : []
    }
  } catch (e) {
    log(`Error leyendo catálogo existente: ${e.message}`)
  }
  return []
}

function findConsolidatedMatch(bucket, norm, rawTitle, consolidatedMap, bucketTokenIndexes) {
  const fullKey = `${bucket}::${norm}`
  if (norm && consolidatedMap.has(fullKey)) {
    return consolidatedMap.get(fullKey)
  }
  const tokens = cleanTokens(rawTitle)
  if (tokens.length < 2) return null

  const tokenIndex = bucketTokenIndexes.get(bucket) || []
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
    if (jaccard >= 0.80 && jaccard > bestJaccard) {
      bestJaccard = jaccard
      bestGame = entry.game
    }
  }
  return bestGame
}

function chooseBestImage(currentImg, candidateImg) {
  if (!candidateImg || !candidateImg.startsWith('http') || candidateImg === FALLBACK_IMAGE) return currentImg || FALLBACK_IMAGE
  if (!currentImg || currentImg === FALLBACK_IMAGE || currentImg.includes('unsplash.com')) return candidateImg
  if (candidateImg.includes('playstation.com') && !currentImg.includes('playstation.com')) return candidateImg
  return currentImg
}

export async function scrapeAndSyncGamingCatalog() {
  log('Iniciando scraping masivo multi-sitio SIN límites artificiales...')

  const existingCatalog = loadExistingCatalog()
  const [dgsRes, apGames, jdaGames, ttgGames, fusionGames] = await Promise.all([
    scrapeDigitalGamesShop(),
    scrapeArgentinaPlay(),
    scrapeJuegosDigitalesArgentina(),
    scrapeTuTiendaGamer(),
    scrapeFusionGames()
  ])

  const { catalog: dgsCatalog, imageMap: psImageMap } = dgsRes
  const txtGames = parseTxtOffers(psImageMap)

  log(`Resumen fuentes: base=${existingCatalog.length}, DGS=${dgsCatalog.length}, ArgPlay=${apGames.length}, JDA=${jdaGames.length}, TTG=${ttgGames.length}, Fusion=${fusionGames.length}, TXT=${txtGames.length}`)

  const consolidatedMap = new Map()
  const bucketTokenIndexes = new Map()
  const registerTokenIndex = (bucket, norm, game) => {
    if (!bucketTokenIndexes.has(bucket)) bucketTokenIndexes.set(bucket, [])
    bucketTokenIndexes.get(bucket).push({ norm, tokenSet: new Set(cleanTokens(game.title)), game })
  }

  // Sembrar catálogo existente conservando IDs curados y overrides admin
  existingCatalog.forEach(game => {
    const cleanTitle = sanitizeBrokenEncodingTitle(game.title)
    const norm = cleanTitleNormalization(cleanTitle)
    if (!norm) return
    const bucket = getPlatformBucket(game.platform, cleanTitle)
    const key = `${bucket}::${norm}`

    let bestImage = game.image || FALLBACK_IMAGE
    if (bucket === 'ps' || bucket === 'ps3') {
      if (psImageMap.has(norm)) bestImage = psImageMap.get(norm)
    }

    const { category, subcategory } = inferTaxonomy(cleanTitle, game.platform)
    const entry = {
      id: game.id,
      title: cleanTitle,
      slug: game.slug || cleanTitle.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, ''),
      platform: game.platform || 'PS4 / PS5',
      category: game.category || category,
      subcategory: game.subcategory || subcategory,
      badge: game.badge || inferBadge(cleanTitle, game.platform),
      image: bestImage,
      providers: { ...(game.providers || {}) },
      ...(game.customPrimaryPrice ? { customPrimaryPrice: game.customPrimaryPrice } : {}),
      ...(game.customSecondaryPrice ? { customSecondaryPrice: game.customSecondaryPrice } : {}),
      ...(game.preferredFulfillmentProvider ? { preferredFulfillmentProvider: game.preferredFulfillmentProvider } : {})
    }

    if (!consolidatedMap.has(key)) {
      consolidatedMap.set(key, entry)
      registerTokenIndex(bucket, norm, entry)
    } else {
      const existing = consolidatedMap.get(key)
      existing.providers = { ...(existing.providers || {}), ...(entry.providers || {}) }
      existing.image = chooseBestImage(existing.image, entry.image)
    }
  })

  const usedIds = new Set(Array.from(consolidatedMap.values()).map(g => g.id))
  const buildUniqueId = (prefix, norm) => {
    const base = `${prefix}-${(norm || 'item').slice(0, 14).toUpperCase()}`
    if (!usedIds.has(base)) {
      usedIds.add(base)
      return base
    }
    let idx = 2
    while (usedIds.has(`${base}-${idx}`)) idx++
    const next = `${base}-${idx}`
    usedIds.add(next)
    return next
  }

  const upsertSourceItems = (items, sourceKey, sourceLabel, idPrefix, allowFuzzy = true) => {
    let matched = 0
    let added = 0
    items.forEach(item => {
      if (!item.norm) return
      const bucket = item.bucket || getPlatformBucket(item.platform, item.title)
      const target = allowFuzzy
        ? findConsolidatedMatch(bucket, item.norm, item.title, consolidatedMap, bucketTokenIndexes)
        : consolidatedMap.get(`${bucket}::${item.norm}`)

      const secCost = supportsSecondary(item.platform)
        ? (item.costSecondary > 0 ? item.costSecondary : Math.round((item.costPrimary || 0) * 0.55))
        : 0

      const provPayload = {
        providerName: sourceLabel,
        providerUrl: item.url || '',
        costPrimary: item.costPrimary || 0,
        costSecondary: secCost
      }

      if (target) {
        if (!target.providers) target.providers = {}
        const prev = target.providers[sourceKey] || {}
        target.providers[sourceKey] = {
          providerName: sourceLabel,
          providerUrl: provPayload.providerUrl || prev.providerUrl || '',
          costPrimary: Math.max(prev.costPrimary || prev.primaryPrice || 0, provPayload.costPrimary),
          costSecondary: Math.max(prev.costSecondary || prev.secondaryPrice || 0, provPayload.costSecondary)
        }
        target.image = chooseBestImage(target.image, item.image || psImageMap.get(item.norm))

        // Combinar PS4 + PS5 cuando coinciden en bucket PlayStation
        if (bucket === 'ps' && item.platform && target.platform && !target.platform.includes(item.platform)) {
          if ((target.platform.includes('PS4') && item.platform.includes('PS5')) || (target.platform.includes('PS5') && item.platform.includes('PS4'))) {
            target.platform = 'PS4 / PS5'
          }
        }
        matched++
      } else if (item.costPrimary > 100) {
        const { category, subcategory } = inferTaxonomy(item.title, item.platform)
        const bestImg = chooseBestImage(FALLBACK_IMAGE, item.image || psImageMap.get(item.norm))
        const newProduct = {
          id: item.id && !usedIds.has(item.id) ? (usedIds.add(item.id), item.id) : buildUniqueId(idPrefix, item.norm),
          title: item.title,
          slug: item.title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, ''),
          platform: item.platform || 'PS5',
          category,
          subcategory,
          badge: inferBadge(item.title, item.platform),
          image: bestImg,
          providers: {
            [sourceKey]: provPayload
          }
        }
        consolidatedMap.set(`${bucket}::${item.norm}`, newProduct)
        registerTokenIndex(bucket, item.norm, newProduct)
        added++
      }
    })
    log(`  → ${sourceLabel}: ${matched} vinculados + ${added} nuevos títulos agregados`)
  }

  // Orden de ingesta: fuentes con precios argentinos + catálogo masivo DGS + TXT
  upsertSourceItems(apGames, 'argentinaplay', 'Argentina Play', 'GAME-AP', true)
  upsertSourceItems(jdaGames, 'juegosdigitalesargentina', 'Juegos Digitales Argentina', 'GAME-JDA', true)
  upsertSourceItems(ttgGames, 'tutiendagamer', 'Tu Tienda Gamer', 'GAME-TTG', true)
  upsertSourceItems(fusionGames, 'fusiongames', 'Fusion Games', 'GAME-FUS', true)
  upsertSourceItems(txtGames, 'mayorista_txt', 'Lista Ofertas TXT', 'GAME-TXT', true)
  // DGS: agregar TODOS los 14,294 juegos (allowFuzzy=false evita colisiones en títulos cortos sobre 14k items y acelera a < 50ms)
  upsertSourceItems(dgsCatalog, 'digitalgamesshop', 'Digital Games Shop', 'GAME-DGS', false)

  const BUILTIN_PROV_KEYS = new Set(['argentinaplay', 'juegosdigitalesargentina', 'digitalgamesshop', 'tutiendagamer', 'fusiongames', 'mayorista_txt'])

  // Pasada final compacta: Recalcular MAYOR COSTO y costo óptimo por título
  const compactList = Array.from(consolidatedMap.values()).map(game => {
    const cleanProviders = {}
    const entries = []
    for (const [k, p] of Object.entries(game.providers || {})) {
      if (!p || p.available === false) continue
      const cPri = Number(p.costPrimary || p.primaryPrice || 0)
      const cSec = supportsSecondary(game.platform)
        ? Number(p.costSecondary || p.secondaryPrice || (cPri > 0 ? Math.round(cPri * 0.55) : 0))
        : 0
      if (cPri <= 0 && cSec <= 0) continue

      const cleanUrl = p.providerUrl && !p.providerUrl.startsWith('https://digitalgamesshop.ar/?q=') ? p.providerUrl : ''
      cleanProviders[k] = {
        ...(!BUILTIN_PROV_KEYS.has(k) && p.providerName ? { providerName: p.providerName } : {}),
        ...(cleanUrl ? { providerUrl: cleanUrl } : {}),
        costPrimary: cPri,
        ...(cSec > 0 ? { costSecondary: cSec } : {})
      }
      entries.push({ key: k, name: p.providerName || k, costPrimary: cPri, costSecondary: cSec })
    }

    const priCosts = entries.map(e => e.costPrimary).filter(c => c > 0)
    const secCosts = entries.map(e => e.costSecondary).filter(c => c > 0)
    const maxPri = priCosts.length ? Math.max(...priCosts) : (Number(game.baseCostPrimary) || 20000)
    const minPri = priCosts.length ? Math.min(...priCosts) : maxPri
    const maxSec = supportsSecondary(game.platform)
      ? (secCosts.length ? Math.max(...secCosts) : Math.round(maxPri * 0.55))
      : 0
    const minSec = secCosts.length ? Math.min(...secCosts) : maxSec

    const highestEntry = entries.find(e => e.costPrimary === maxPri) || entries[0]
    const cheapestEntry = entries.find(e => e.costPrimary === minPri) || entries[0]

    const pvpPrimary = Math.ceil((maxPri * 1.20) / 100) * 100
    const pvpSecondary = maxSec > 0 ? Math.ceil((maxSec * 1.20) / 100) * 100 : 0

    return {
      id: game.id,
      title: game.title,
      slug: game.slug,
      platform: game.platform,
      category: game.category,
      subcategory: game.subcategory,
      badge: game.badge,
      image: game.image,
      providers: cleanProviders,
      baseCostPrimary: maxPri,
      ...(maxSec > 0 ? { baseCostSecondary: maxSec } : {}),
      effectivePrimaryPrice: pvpPrimary,
      ...(pvpSecondary > 0 ? { effectiveSecondaryPrice: pvpSecondary } : {}),
      visibleProviderKey: highestEntry?.key || 'digitalgamesshop',
      bestFulfillmentPrimary: cheapestEntry?.key || 'digitalgamesshop',
      bestFulfillmentSecondary: cheapestEntry?.key || 'digitalgamesshop',
      ...(game.customPrimaryPrice ? { customPrimaryPrice: game.customPrimaryPrice } : {}),
      ...(game.customSecondaryPrice ? { customSecondaryPrice: game.customSecondaryPrice } : {}),
      ...(game.preferredFulfillmentProvider ? { preferredFulfillmentProvider: game.preferredFulfillmentProvider } : {})
    }
  })

  // Priorizar títulos multi-sitio & lanzamientos arriba del catálogo por defecto
  compactList.sort((a, b) => {
    const aMulti = Object.keys(a.providers || {}).length
    const bMulti = Object.keys(b.providers || {}).length
    if (bMulti !== aMulti) return bMulti - aMulti
    const aCurated = String(a.id).startsWith('GAME-0') || String(a.id).startsWith('GAME-1') ? 1 : 0
    const bCurated = String(b.id).startsWith('GAME-0') || String(b.id).startsWith('GAME-1') ? 1 : 0
    if (bCurated !== aCurated) return bCurated - aCurated
    return 0
  })

  log(`✓ Catálogo final consolidado: ${compactList.length} videojuegos a la venta (compacto)`)

  const categoryOrder = [
    'Todas',
    'PS5',
    'PS4',
    'PS3',
    'Xbox',
    'Nintendo Switch',
    'PC',
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
  compactList.forEach(item => {
    if (item.category) categorySet.add(item.category)
  })
  const categoriesList = Array.from(categorySet)

  const subcatsByCat = {}
  categoriesList.forEach(c => {
    subcatsByCat[c] = new Set(['Todas'])
  })

  compactList.forEach(item => {
    if (!item.subcategory) return
    subcatsByCat['Todas'].add(item.subcategory)
    if (item.category && subcatsByCat[item.category]) {
      subcatsByCat[item.category].add(item.subcategory)
    }
    if (item.platform?.includes('PS5')) subcatsByCat['PS5']?.add(item.subcategory)
    if (item.platform?.includes('PS4')) subcatsByCat['PS4']?.add(item.subcategory)
    if (item.platform?.includes('PS3')) subcatsByCat['PS3']?.add(item.subcategory)
    if (item.platform?.includes('Xbox')) subcatsByCat['Xbox']?.add(item.subcategory)
    if (item.platform?.includes('Switch')) subcatsByCat['Nintendo Switch']?.add(item.subcategory)
    if (item.platform?.includes('PC')) subcatsByCat['PC']?.add(item.subcategory)
    if (item.badge && (item.badge.includes('MÁS VENDIDO') || item.badge.includes('JUEGO DEL AÑO') || item.badge.includes('LANZAMIENTO'))) {
      subcatsByCat['Lanzamientos']?.add(item.subcategory)
    }
  })

  const formattedSubcats = {}
  for (const [k, v] of Object.entries(subcatsByCat)) {
    formattedSubcats[k] = Array.from(v)
  }

  fs.writeFileSync(TARGET_GAMING_PRODUCTS_FILE, JSON.stringify(compactList), 'utf-8')
  fs.writeFileSync(TARGET_GAMING_CATEGORIES_FILE, JSON.stringify(categoriesList), 'utf-8')
  fs.writeFileSync(TARGET_GAMING_SUBCATS_FILE, JSON.stringify(formattedSubcats), 'utf-8')

  const sizeMb = (fs.statSync(TARGET_GAMING_PRODUCTS_FILE).size / (1024 * 1024)).toFixed(2)
  log(`✓ Archivo guardado: ${TARGET_GAMING_PRODUCTS_FILE} (${compactList.length} juegos, ${sizeMb} MB)`)

  return {
    success: true,
    count: compactList.length,
    products: compactList,
    categories: categoriesList,
    subcategories: formattedSubcats
  }
}

if (process.argv[1] === fileURLToPath(import.meta.url)) {
  scrapeAndSyncGamingCatalog().catch(err => {
    console.error('Error ejecutando scraper:', err)
    process.exit(1)
  })
}

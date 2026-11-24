// Motor de Sincronización en Vivo y Persistencia Escalable del Catálogo Gamer (StreamePro)

const IDB_NAME = 'streamepro_gaming_live_v1'
const IDB_STORE = 'catalog'
const IDB_KEY = 'live_snapshot'

const FALLBACK_GAME_IMG = 'https://images.unsplash.com/photo-1606813907291-d86efa9b94db?w=800&auto=format&fit=crop&q=80'

export const JDA_SCRAPE_CATEGORIES = [
  'juegos-digitales-ps5',
  'juegos-digitales-ps4',
  'juegos-digitales-ps3',
  'nintendo-switch',
  'nintendo-switch-2',
  'xbox',
  'membresias'
]

export const TTG_SCRAPE_CATEGORIES = [
  'juegos-ps5',
  'juegos-ps4',
  'juegos-ps3',
  'juegos-xbox',
  'xbox-series-x-s',
  'juegos-pc',
  'nintendo-switch'
]

export function getSyncPlatformBucket(platform = '', title = '') {
  const s = `${platform} ${title}`.toLowerCase()
  if (s.includes('xbox')) return 'xbox'
  if (s.includes('switch') || s.includes('nintendo')) return 'switch'
  if (/\b(pc|steam)\b/i.test(s)) return 'pc'
  if (s.includes('ps3') && !s.includes('ps4') && !s.includes('ps5')) return 'ps3'
  return 'ps'
}

export function supportsSyncSecondary(platform = '') {
  return getSyncPlatformBucket(platform) === 'ps'
}

function sanitizeSyncTitle(str = '') {
  return String(str || '')
    .replace(/\uFFFD\?/g, '')
    .replace(/\uFFFD/g, '')
    .replace(/\s+/g, ' ')
    .trim()
}

export function normalizeTitleForSync(raw = '') {
  if (!raw) return ''
  return sanitizeSyncTitle(raw)
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

export function tokenizeTitleForSync(raw = '') {
  if (!raw) return []
  return sanitizeSyncTitle(raw)
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

function inferBasicTaxonomy(title = '', platform = '') {
  const t = `${title} ${platform}`.toLowerCase()
  if (t.includes('xbox')) return { category: 'Xbox', subcategory: t.includes('series') ? 'Xbox Series X|S' : 'Xbox One' }
  if (t.includes('switch') || t.includes('nintendo')) return { category: 'Nintendo Switch', subcategory: t.includes('switch 2') ? 'Nintendo Switch 2' : 'Nintendo Switch' }
  if (/\b(pc|steam)\b/i.test(t) && !t.includes('ps4') && !t.includes('ps5')) return { category: 'PC', subcategory: 'PC / Steam Digital' }
  if (t.includes('ps plus') || t.includes('playstation plus') || t.includes('membres') || t.includes('gift card')) return { category: 'Membresías', subcategory: 'Suscripciones & Tarjetas' }
  if (t.includes('fc 2') || t.includes('fifa') || t.includes('nba') || t.includes('turismo') || t.includes('forza')) return { category: 'Deportes', subcategory: 'Fútbol (FC / FIFA)' }
  if (t.includes('kombat') || t.includes('dragon ball') || t.includes('tekken') || t.includes('street fighter')) return { category: 'Lucha', subcategory: 'Combate 3D' }
  if (t.includes('resident evil') || t.includes('silent hill') || t.includes('alan wake') || t.includes('dead space')) return { category: 'Survival Horror', subcategory: 'Zombies & Survival' }
  if (t.includes('elden ring') || t.includes('hogwarts') || t.includes('wukong') || t.includes('cyberpunk') || t.includes('witcher')) return { category: 'Rol & Aventura', subcategory: 'Mundos de Fantasía' }
  return { category: 'Acción & Aventura', subcategory: 'Acción General' }
}

function unpackScrapedItem(raw, fallbackSource = '', fallbackLabel = '') {
  if (!raw || typeof raw !== 'object') return null
  const title = sanitizeSyncTitle(raw.title || raw.t || '')
  if (!title) return null
  const platform = raw.platform || raw.p || 'PS4 / PS5'
  const norm = raw.norm || raw.n || normalizeTitleForSync(title)
  if (!norm) return null
  const bucket = raw.bucket || raw.b || getSyncPlatformBucket(platform, title)
  const costPrimary = Number(raw.costPrimary ?? raw.c1 ?? 0) || 0
  const allowSec = supportsSyncSecondary(platform)
  const rawSec = Number(raw.costSecondary ?? raw.c2 ?? 0) || 0
  const costSecondary = allowSec ? (rawSec > 0 ? rawSec : (costPrimary > 0 ? Math.round(costPrimary * 0.55) : 0)) : 0
  if (costPrimary <= 0 && costSecondary <= 0) return null

  return {
    source: raw.source || fallbackSource,
    providerName: raw.providerName || fallbackLabel || fallbackSource,
    title,
    norm,
    bucket,
    platform,
    costPrimary,
    costSecondary,
    url: raw.url || '',
    image: raw.image || raw.img || '',
    isBlackFriday: Boolean(raw.isBlackFriday || raw.bf),
    offerPct: Number(raw.offerPct || raw.pct || 0)
  }
}

export function computeBundleSignature(list = []) {
  if (!Array.isArray(list) || list.length === 0) return '0'
  const first = list[0]
  const mid = list[Math.floor(list.length / 2)]
  const last = list[list.length - 1]
  return `${list.length}:${first?.id || ''}:${first?.baseCostPrimary || 0}:${mid?.id || ''}:${mid?.baseCostPrimary || 0}:${last?.id || ''}`
}

/**
 * Fusiona batches en vivo dentro del catálogo actual en O(N + M), permitiendo subidas y bajadas de precio.
 */
export function mergeLiveScrapedBatchesIntoCatalog(currentCatalog = [], sourceBatches = [], sitesConfig = []) {
  const siteMap = new Map((sitesConfig || []).filter(Boolean).map(s => [s.key, s]))
  const updatedCatalog = (Array.isArray(currentCatalog) ? currentCatalog : []).map(g => ({
    ...g,
    providers: g.providers ? { ...g.providers } : {}
  }))

  const exactMap = new Map()
  const bucketTokenIndex = new Map()
  const usedIds = new Set()

  const registerGame = (game) => {
    if (!game) return
    if (game.id) usedIds.add(game.id)
    const bucket = getSyncPlatformBucket(game.platform, game.title)
    const norm = normalizeTitleForSync(game.title)
    if (!norm) return
    const key = `${bucket}::${norm}`
    if (!exactMap.has(key)) exactMap.set(key, game)
    if (!bucketTokenIndex.has(bucket)) bucketTokenIndex.set(bucket, [])
    const tokens = tokenizeTitleForSync(game.title)
    if (tokens.length >= 2) {
      bucketTokenIndex.get(bucket).push({ norm, tokenSet: new Set(tokens), game })
    }
  }

  updatedCatalog.forEach(registerGame)

  const findMatch = (bucket, norm, rawTitle) => {
    const exact = exactMap.get(`${bucket}::${norm}`)
    if (exact) return exact
    const tokens = tokenizeTitleForSync(rawTitle)
    if (tokens.length < 2) return null
    const entries = bucketTokenIndex.get(bucket) || []
    const setA = new Set(tokens)
    let best = null
    let bestJ = 0
    for (let i = 0; i < entries.length; i++) {
      const e = entries[i]
      let inter = 0
      for (const t of setA) {
        if (e.tokenSet.has(t)) inter++
      }
      if (inter === 0) continue
      const j = inter / (setA.size + e.tokenSet.size - inter)
      if (j >= 0.80 && j > bestJ) {
        bestJ = j
        best = e.game
      }
    }
    return best
  }

  const freshUpdated = new Map()
  const touchedGames = new Set()
  const stats = {
    updatedGames: 0,
    priceIncreased: 0,
    priceDecreased: 0,
    newlyAdded: 0,
    providersUpdated: 0
  }

  sourceBatches.forEach(batch => {
    const sourceKey = batch?.sourceKey
    if (!sourceKey || !Array.isArray(batch?.items) || batch.items.length === 0) return
    const siteMeta = siteMap.get(sourceKey)
    const sourceLabel = batch.sourceLabel || siteMeta?.name || sourceKey
    const allowFuzzy = sourceKey !== 'digitalgamesshop'
    stats.providersUpdated++

    batch.items.forEach(rawItem => {
      const item = unpackScrapedItem(rawItem, sourceKey, sourceLabel)
      if (!item) return

      const target = allowFuzzy
        ? findMatch(item.bucket, item.norm, item.title)
        : exactMap.get(`${item.bucket}::${item.norm}`)

      const nextPayload = {
        providerName: sourceLabel,
        providerUrl: item.url || '',
        costPrimary: item.costPrimary,
        ...(item.costSecondary > 0 ? { costSecondary: item.costSecondary } : {}),
        ...(item.isBlackFriday ? { isBlackFriday: true, offerPct: item.offerPct || 25 } : {})
      }

      if (target) {
        if (!target.providers) target.providers = {}
        let setForGame = freshUpdated.get(target)
        if (!setForGame) {
          setForGame = new Set()
          freshUpdated.set(target, setForGame)
        }
        const isFirstWriteThisRun = !setForGame.has(sourceKey)
        setForGame.add(sourceKey)

        const prev = target.providers[sourceKey] || {}
        const prevPri = Number(prev.costPrimary || prev.primaryPrice || 0)

        if (isFirstWriteThisRun) {
          const finalPri = nextPayload.costPrimary > 0 ? nextPayload.costPrimary : prevPri
          const finalSec = nextPayload.costSecondary > 0 ? nextPayload.costSecondary : Number(prev.costSecondary || prev.secondaryPrice || 0)
          target.providers[sourceKey] = {
            ...prev,
            providerName: sourceLabel,
            providerUrl: nextPayload.providerUrl || prev.providerUrl || '',
            costPrimary: finalPri,
            primaryPrice: finalPri,
            ...(finalSec > 0 ? { costSecondary: finalSec, secondaryPrice: finalSec } : {}),
            ...(nextPayload.isBlackFriday ? { isBlackFriday: true, offerPct: nextPayload.offerPct || 25 } : {})
          }
          if (!nextPayload.isBlackFriday && target.providers[sourceKey].isBlackFriday) {
            delete target.providers[sourceKey].isBlackFriday
            delete target.providers[sourceKey].offerPct
          }
          if (prevPri > 0 && finalPri !== prevPri && !touchedGames.has(target)) {
            touchedGames.add(target)
            stats.updatedGames++
            if (finalPri > prevPri) stats.priceIncreased++
            else stats.priceDecreased++
          } else if (prevPri === 0 && finalPri > 0 && !touchedGames.has(target)) {
            touchedGames.add(target)
            stats.updatedGames++
          }
        } else {
          // Otra variante/edición del mismo juego dentro del mismo proveedor en esta corrida
          const mergedPri = Math.max(prevPri, nextPayload.costPrimary)
          const mergedSec = Math.max(Number(prev.costSecondary || prev.secondaryPrice || 0), nextPayload.costSecondary)
          target.providers[sourceKey] = {
            ...prev,
            providerName: sourceLabel,
            providerUrl: nextPayload.providerUrl || prev.providerUrl || '',
            costPrimary: mergedPri,
            primaryPrice: mergedPri,
            ...(mergedSec > 0 ? { costSecondary: mergedSec, secondaryPrice: mergedSec } : {})
          }
        }

        if (item.image && (!target.image || target.image.includes('unsplash.com'))) {
          target.image = item.image
        }
        if (item.isBlackFriday) target.isBlackFriday = true
      } else if (item.costPrimary > 100) {
        const { category, subcategory } = inferBasicTaxonomy(item.title, item.platform)
        let newId = `GAME-LIVE-${item.norm.slice(0, 12).toUpperCase()}`
        let sfx = 2
        while (usedIds.has(newId)) newId = `GAME-LIVE-${item.norm.slice(0, 10).toUpperCase()}-${sfx++}`
        const newGame = {
          id: newId,
          title: item.title,
          slug: item.title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, ''),
          platform: item.platform || 'PS5',
          category,
          subcategory,
          badge: item.isBlackFriday ? 'BLACK FRIDAY' : '⭐️ PLAYSTATION OFICIAL',
          image: item.image && item.image.startsWith('http') ? item.image : FALLBACK_GAME_IMG,
          ...(item.isBlackFriday ? { isBlackFriday: true, offerPct: item.offerPct || 25 } : {}),
          providers: {
            [sourceKey]: {
              providerName: sourceLabel,
              providerUrl: nextPayload.providerUrl,
              costPrimary: nextPayload.costPrimary,
              primaryPrice: nextPayload.costPrimary,
              ...(nextPayload.costSecondary > 0 ? { costSecondary: nextPayload.costSecondary, secondaryPrice: nextPayload.costSecondary } : {}),
              ...(nextPayload.isBlackFriday ? { isBlackFriday: true, offerPct: nextPayload.offerPct || 25 } : {})
            }
          }
        }
        updatedCatalog.push(newGame)
        registerGame(newGame)
        stats.newlyAdded++
      }
    })
  })

  return { updatedCatalog, stats }
}

export async function fetchLiveGamingScrapes(providerSites = [], onProgress = null) {
  const sitesList = Array.isArray(providerSites) ? providerSites : []
  const isEnabled = (key) => {
    const found = sitesList.find(s => s && s.key === key)
    return found ? found.enabled !== false : true
  }

  const jobs = []
  if (isEnabled('pixeleros')) jobs.push({ sourceKey: 'pixeleros', sourceLabel: 'Pixeleros', query: 'source=pixeleros' })
  if (isEnabled('fusiongames')) jobs.push({ sourceKey: 'fusiongames', sourceLabel: 'Fusion Games', query: 'source=fusiongames' })
  if (isEnabled('palegames')) jobs.push({ sourceKey: 'palegames', sourceLabel: 'Pale Games', query: 'source=palegames' })
  if (isEnabled('argentinaplay')) jobs.push({ sourceKey: 'argentinaplay', sourceLabel: 'Argentina Play', query: 'source=argentinaplay' })
  if (isEnabled('tutiendagamer')) {
    TTG_SCRAPE_CATEGORIES.forEach(cat => {
      jobs.push({
        sourceKey: 'tutiendagamer',
        sourceLabel: 'Tu Tienda Gamer',
        label: `Tu Tienda Gamer (${cat})`,
        query: `source=tutiendagamer&cat=${encodeURIComponent(cat)}`
      })
    })
  }
  if (isEnabled('digitalgamesshop')) {
    jobs.push({ sourceKey: 'digitalgamesshop', sourceLabel: 'Digital Games Shop', query: 'source=digitalgamesshop' })
  }
  if (isEnabled('juegosdigitalesargentina')) {
    JDA_SCRAPE_CATEGORIES.forEach(cat => {
      jobs.push({
        sourceKey: 'juegosdigitalesargentina',
        sourceLabel: 'Juegos Digitales Argentina',
        label: `JDA (${cat})`,
        isJdaFirstChunk: true,
        cat,
        query: `source=juegosdigitalesargentina&cat=${encodeURIComponent(cat)}&pageStart=0&pageLimit=18`
      })
    })
  }

  // Sitios personalizados agregados manualmente por Admin con URL
  const builtinKeys = new Set([
    'pixeleros',
    'fusiongames',
    'palegames',
    'argentinaplay',
    'tutiendagamer',
    'digitalgamesshop',
    'juegosdigitalesargentina',
    'mayorista_txt'
  ])
  sitesList.forEach(site => {
    if (site && site.enabled !== false && !builtinKeys.has(site.key) && /^https?:\/\//i.test(site.url || '')) {
      jobs.push({
        sourceKey: site.key,
        sourceLabel: site.name || site.key,
        query: `source=${encodeURIComponent(site.key)}&customUrl=${encodeURIComponent(site.url)}&sourceLabel=${encodeURIComponent(site.name || site.key)}`
      })
    }
  })

  let totalJobs = jobs.length
  let completedJobs = 0
  const batches = []
  const sourcesSucceeded = new Set()
  const sourcesFailed = new Set()

  const emitProgress = (currentLabel) => {
    if (typeof onProgress === 'function') {
      onProgress({
        completed: completedJobs,
        total: totalJobs,
        currentLabel,
        sourcesSucceeded: Array.from(sourcesSucceeded),
        sourcesFailed: Array.from(sourcesFailed)
      })
    }
  }

  const runJob = async (job) => {
    emitProgress(job.label || job.sourceLabel)
    try {
      const res = await fetch(`/api/sync-gaming?scrape=true&${job.query}`, {
        signal: AbortSignal.timeout(45000)
      })
      if (!res.ok) throw new Error(`HTTP ${res.status}`)
      const json = await res.json()
      if (!json || !json.scrapedLive || !Array.isArray(json.items)) {
        throw new Error(json?.error || 'Sin respuesta en vivo')
      }
      if (json.items.length > 0) {
        batches.push({
          sourceKey: job.sourceKey,
          sourceLabel: job.sourceLabel,
          items: json.items
        })
        sourcesSucceeded.add(job.sourceKey)
      }

      // En JDA, encolar las páginas restantes en tramos de 18 páginas para evitar timeouts
      if (job.isJdaFirstChunk && Number(json.totalPages) > 18) {
        const tp = Number(json.totalPages)
        for (let ps = 18; ps < tp; ps += 18) {
          jobs.push({
            sourceKey: 'juegosdigitalesargentina',
            sourceLabel: 'Juegos Digitales Argentina',
            label: `JDA (${job.cat} p${ps + 1})`,
            query: `source=juegosdigitalesargentina&cat=${encodeURIComponent(job.cat)}&pageStart=${ps}&pageLimit=18`
          })
          totalJobs++
        }
      }
    } catch (err) {
      console.warn(`[Sync Gaming] Fallo parcial en ${job.label || job.sourceLabel}:`, err?.message || err)
      sourcesFailed.add(job.sourceKey)
    } finally {
      completedJobs++
      emitProgress(job.label || job.sourceLabel)
    }
  }

  // Cola concurrente de hasta 4 peticiones simultáneas
  const activeWorkers = Array.from({ length: 4 }, async () => {
    while (jobs.length > 0) {
      const next = jobs.shift()
      if (next) await runJob(next)
    }
  })
  await Promise.all(activeWorkers)

  return {
    batches,
    sourcesSucceeded: Array.from(sourcesSucceeded),
    sourcesFailed: Array.from(sourcesFailed).filter(k => !sourcesSucceeded.has(k))
  }
}

function openGamingIdb() {
  if (typeof window === 'undefined' || !window.indexedDB) return Promise.resolve(null)
  return new Promise((resolve) => {
    try {
      const req = window.indexedDB.open(IDB_NAME, 1)
      req.onupgradeneeded = () => {
        const db = req.result
        if (!db.objectStoreNames.contains(IDB_STORE)) {
          db.createObjectStore(IDB_STORE)
        }
      }
      req.onsuccess = () => resolve(req.result)
      req.onerror = () => resolve(null)
    } catch {
      resolve(null)
    }
  })
}

export async function saveLiveGamingCatalogToIdb(products = [], bundleSignature = '', extra = {}) {
  const db = await openGamingIdb()
  if (!db) return false
  return new Promise((resolve) => {
    try {
      const tx = db.transaction(IDB_STORE, 'readwrite')
      tx.objectStore(IDB_STORE).put({
        products,
        bundleSignature,
        scrapedAtMs: Date.now(),
        ...extra
      }, IDB_KEY)
      tx.oncomplete = () => resolve(true)
      tx.onerror = () => resolve(false)
    } catch {
      resolve(false)
    }
  })
}

export async function loadLiveGamingCatalogFromIdb() {
  const db = await openGamingIdb()
  if (!db) return null
  return new Promise((resolve) => {
    try {
      const tx = db.transaction(IDB_STORE, 'readonly')
      const req = tx.objectStore(IDB_STORE).get(IDB_KEY)
      req.onsuccess = () => resolve(req.result || null)
      req.onerror = () => resolve(null)
    } catch {
      resolve(null)
    }
  })
}

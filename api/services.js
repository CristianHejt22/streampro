import { STREAMING_PLATFORMS_META, STREAMING_PLANS_FALLBACK } from './_catalogLookup.js'
import {
  OFFICIAL_COMBOS_CATALOG,
  STORE_STREAMING_UNIT_PRICES,
  calculatePackageFromCatalog
} from '../src/services/officialCombosData.js'
import { fetchStoreSettingsViaRest } from '../src/services/firebase.js'

const TELEGRAM_MAX_MESSAGE_LEN = 4096

function formatArs(amount) {
  const num = Number(amount) || 0
  return `$${num.toLocaleString('es-AR')}`
}

function escapeHtml(str) {
  return String(str || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
}

function padRight(str, len) {
  const s = String(str || '')
  if (s.length >= len) return s.slice(0, len)
  return s + ' '.repeat(len - s.length)
}

function padLeft(str, len) {
  const s = String(str || '')
  if (s.length >= len) return s.slice(0, len)
  return ' '.repeat(len - s.length) + s
}

async function fetchLiveCloudSettings(timeoutMs = 3500) {
  try {
    return await Promise.race([
      fetchStoreSettingsViaRest(),
      new Promise(resolve => setTimeout(() => resolve(null), timeoutMs))
    ])
  } catch {
    return null
  }
}

function buildFallbackPlans(platform) {
  if (STREAMING_PLANS_FALLBACK[platform]) {
    return JSON.parse(JSON.stringify(STREAMING_PLANS_FALLBACK[platform]))
  }
  const meta = STREAMING_PLATFORMS_META[platform] || {}
  const unit = STORE_STREAMING_UNIT_PRICES[platform] || {}
  const basePrice = Number(meta.minPrice || unit.unitPrice || 3800)
  const baseCost = Number(unit.cost || Math.round(basePrice * 0.45))
  const planKey = platform === 'Netflix Perfil Extra' ? 'perfil_extra' : '1_perfil'
  const label = platform === 'Netflix Perfil Extra'
    ? '1 Perfil Extra (Sin Bloqueo de Hogar)'
    : '1 Perfil / 1 Dispositivo'

  return {
    [planKey]: {
      label,
      units: 1,
      type: platform === 'Netflix Perfil Extra' ? 'perfil_extra' : 'perfil',
      providerCost: baseCost,
      resellerProfit: Math.max(0, basePrice - baseCost),
      clientPrice: basePrice
    }
  }
}

function normalizeEffectivePricingCatalog(cloudCatalog = {}) {
  const catalog = {}

  Object.keys(STREAMING_PLATFORMS_META).forEach(plat => {
    const meta = STREAMING_PLATFORMS_META[plat]
    catalog[plat] = {
      name: meta.name || plat,
      plans: buildFallbackPlans(plat)
    }
  })

  if (cloudCatalog && typeof cloudCatalog === 'object') {
    Object.keys(cloudCatalog).forEach(plat => {
      if (plat === 'Apple TV+') return
      const cloudEntry = cloudCatalog[plat]
      if (!cloudEntry || typeof cloudEntry !== 'object') return
      const existing = catalog[plat] || {}
      const fallbackPlans = existing.plans || buildFallbackPlans(plat)

      let normalizedPlans = fallbackPlans
      if (cloudEntry.plans && Object.keys(cloudEntry.plans).length > 0) {
        normalizedPlans = {}
        Object.entries(cloudEntry.plans).forEach(([k, p]) => {
          const fb = fallbackPlans[k] || {}
          normalizedPlans[k] = {
            ...fb,
            ...p
          }
        })
      }

      catalog[plat] = {
        name: cloudEntry.name || existing.name || plat,
        plans: normalizedPlans
      }
    })
  }

  delete catalog['Apple TV+']

  // Garantizar Netflix Perfil Extra solo 1 dispositivo ($10.000)
  if (catalog['Netflix Perfil Extra']) {
    const npePlans = catalog['Netflix Perfil Extra'].plans || {}
    if (npePlans['2_perfiles_extra']) {
      delete npePlans['2_perfiles_extra']
    }
    const pExtra = npePlans.perfil_extra || buildFallbackPlans('Netflix Perfil Extra').perfil_extra
    if (!pExtra.clientPrice || pExtra.clientPrice === 6500 || pExtra.clientPrice === 5500) {
      pExtra.clientPrice = 10000
      pExtra.providerCost = 8000
      pExtra.resellerProfit = 2000
    }
    pExtra.units = 1
    catalog['Netflix Perfil Extra'].plans = { perfil_extra: pExtra }
  }

  return catalog
}

function buildStreamingTableLines(streamingList) {
  const colName = 20
  const colPant = 4
  const colProv = 7
  const colDist = 7
  const colPub = 7

  const header = `${padRight('Servicio', colName)} | ${padRight('Pant', colPant)} | ${padLeft('Prov.', colProv)} | ${padLeft('Dist.', colDist)} | ${padLeft('Precio', colPub)}`
  const sep = `${'-'.repeat(colName)}-+-${'-'.repeat(colPant)}-+-${'-'.repeat(colProv)}-+-${'-'.repeat(colDist)}-+-${'-'.repeat(colPub)}`

  const rows = []
  streamingList.forEach(item => {
    const shortName = item.platform === 'Disney+ (Copia)'
      ? 'Disney+ Standar'
      : item.platform === 'IPTV'
        ? 'IPTV Pro'
        : item.platform

    item.options.forEach(opt => {
      const pantTag = `${opt.screens}d`
      rows.push(
        `${padRight(shortName, colName)} | ${padRight(pantTag, colPant)} | ${padLeft(opt.formatted.provider, colProv)} | ${padLeft(opt.formatted.distributor, colDist)} | ${padLeft(opt.formatted.price, colPub)}`
      )
    })
  })

  return {
    title: 'SUSCRIPCIONES Y OPCIONES DE PANTALLAS (ARS)',
    headerLines: [header, sep],
    rows
  }
}

function buildCombosTableLines(combosList) {
  const colId = 4
  const colCombo = 28
  const colPant = 3
  const colProv = 7
  const colDist = 7
  const colPub = 7

  const header = `${padRight('ID', colId)} | ${padRight('Combo', colCombo)} | ${padRight('P', colPant)} | ${padLeft('Prov.', colProv)} | ${padLeft('Dist.', colDist)} | ${padLeft('Precio', colPub)}`
  const sep = `${'-'.repeat(colId)}-+-${'-'.repeat(colCombo)}-+-${'-'.repeat(colPant)}-+-${'-'.repeat(colProv)}-+-${'-'.repeat(colDist)}-+-${'-'.repeat(colPub)}`

  const rows = []
  combosList.forEach(c => {
    const srvSummary = c.services
      .map(s => {
        const platName = (String(s.platform).toLowerCase() === 'netflix' && String(s.plan).toLowerCase().includes('extra'))
          ? 'Netflix Perfil Extra'
          : s.platform
        return s.screens > 1 ? `${platName}(${s.screens}d)` : platName
      })
      .join(' + ')
    const mainRow = `${padRight(c.id, colId)} | ${padRight(c.title, colCombo)} | ${padRight(`${c.screens}d`, colPant)} | ${padLeft(c.formatted.provider, colProv)} | ${padLeft(c.formatted.distributor, colDist)} | ${padLeft(c.formatted.price, colPub)}`
    rows.push(`${mainRow}\n     > ${srvSummary}`)
  })

  return {
    title: 'COMBOS Y PROMOCIONES (ARS)',
    headerLines: [header, sep],
    rows
  }
}

function renderPlainTable(tableSpec) {
  return [tableSpec.title, ...tableSpec.headerLines, ...tableSpec.rows].join('\n')
}

function paginateForTelegram(tableSpec, maxPayloadChars = 3900) {
  // Reservar espacio para envoltorio de bloque monospace (```\n...\n``` = 8 chars)
  const effectiveMax = Math.min(maxPayloadChars, TELEGRAM_MAX_MESSAGE_LEN - 16)
  const pages = []
  const staticTop = (pageIdx, totalPagesPlaceholder) =>
    `${tableSpec.title}${totalPagesPlaceholder ? ` (${pageIdx})` : ''}\n${tableSpec.headerLines.join('\n')}`

  let currentChunk = []
  let currentLen = staticTop(1, true).length

  tableSpec.rows.forEach(row => {
    const addition = 1 + row.length
    if (currentChunk.length > 0 && currentLen + addition > effectiveMax) {
      pages.push(currentChunk)
      currentChunk = [row]
      currentLen = staticTop(pages.length + 1, true).length + 1 + row.length
    } else {
      currentChunk.push(row)
      currentLen += addition
    }
  })

  if (currentChunk.length > 0) {
    pages.push(currentChunk)
  }

  const hasMultiple = pages.length > 1
  return pages.map((rowsChunk, idx) => {
    const titleLine = hasMultiple ? `${tableSpec.title} [${idx + 1}/${pages.length}]` : tableSpec.title
    return [titleLine, ...tableSpec.headerLines, ...rowsChunk].join('\n')
  })
}

function wrapTelegramMarkdown(rawTable) {
  return `\`\`\`\n${rawTable}\n\`\`\``
}

function wrapTelegramHtml(rawTable) {
  return `<pre>${escapeHtml(rawTable)}</pre>`
}

export default async function handler(req, res) {
  res.setHeader('Access-Control-Allow-Credentials', 'true')
  res.setHeader('Access-Control-Allow-Origin', '*')
  res.setHeader('Access-Control-Allow-Methods', 'GET, OPTIONS')
  res.setHeader('Access-Control-Allow-Headers', 'X-CSRF-Token, X-Requested-With, Accept, Accept-Version, Content-Length, Content-MD5, Content-Type, Date, X-Api-Version')
  res.setHeader('Cache-Control', 'public, max-age=30, s-maxage=60, stale-while-revalidate=120')

  if (req.method === 'OPTIONS') {
    return res.status(200).end()
  }

  try {
    const cloudSettings = await fetchLiveCloudSettings()
    const hasLiveFirebase = Boolean(cloudSettings && (cloudSettings.pricingCatalog || cloudSettings.packages))
    const effectiveCatalog = normalizeEffectivePricingCatalog(cloudSettings?.pricingCatalog || {})

    const orderedPlatforms = [
      ...Object.keys(STREAMING_PLATFORMS_META).filter(k => Boolean(effectiveCatalog[k])),
      ...Object.keys(effectiveCatalog).filter(k => !STREAMING_PLATFORMS_META[k])
    ]

    // 1. Compilar Suscripciones con opciones de pantallas y precios (Proveedor / Distribuidor / Venta)
    const streamingList = orderedPlatforms.map(platform => {
      const catEntry = effectiveCatalog[platform] || {}
      const meta = STREAMING_PLATFORMS_META[platform] || {}
      const unit = STORE_STREAMING_UNIT_PRICES[platform] || {}

      const options = Object.entries(catEntry.plans || buildFallbackPlans(platform))
        .map(([key, plan]) => {
          const screens = Number(plan?.units || 1)
          const providerPrice = Number(plan?.providerCost || unit.cost || 0)
          const distributorPrice = Number(plan?.resellerCost ?? providerPrice)
          const price = Number(plan?.clientPrice || meta.minPrice || unit.unitPrice || 3800)
          const distributorProfit = Number(
            plan?.resellerProfit !== undefined ? plan.resellerProfit : Math.max(0, price - distributorPrice)
          )

          return {
            key,
            label: plan?.label || `${screens} ${screens === 1 ? 'Dispositivo' : 'Dispositivos'}`,
            screens,
            providerPrice,
            distributorPrice,
            price,
            distributorProfit,
            formatted: {
              provider: formatArs(providerPrice),
              distributor: formatArs(distributorPrice),
              price: formatArs(price),
              distributorProfit: formatArs(distributorProfit)
            }
          }
        })
        .sort((a, b) => a.screens - b.screens)

      return {
        id: platform.toLowerCase().replace(/[^a-z0-9]+/g, '-'),
        platform,
        name: catEntry.name || meta.name || unit.name || platform,
        minProviderPrice: options.length > 0 ? Math.min(...options.map(o => o.providerPrice)) : 0,
        minDistributorPrice: options.length > 0 ? Math.min(...options.map(o => o.distributorPrice)) : 0,
        minPrice: options.length > 0 ? Math.min(...options.map(o => o.price)) : Number(meta.minPrice || 3800),
        options
      }
    })

    // 2. Compilar Combos con precios (Proveedor / Distribuidor / Venta) y servicios incluidos
    const rawCombos = (Array.isArray(cloudSettings?.packages) && cloudSettings.packages.length > 0)
      ? cloudSettings.packages
      : (OFFICIAL_COMBOS_CATALOG || [])

    const reqCategory = String(req.query.category || '').toLowerCase().trim()

    const combosList = rawCombos
      .map(rawPkg => {
        const combo = rawPkg?.isManualPrice
          ? rawPkg
          : calculatePackageFromCatalog(rawPkg, effectiveCatalog)

        const providerPrice = Number(combo.providerCost || 0)
        const distributorPrice = Number(
          combo.resellerCost !== undefined
            ? combo.resellerCost
            : (providerPrice + Number(combo.platformProfit || 0))
        )
        const price = Number(combo.promoPrice || 0)
        const originalPrice = Number(combo.originalPrice || 0)
        const distributorProfit = Number(
          combo.resellerProfit !== undefined ? combo.resellerProfit : Math.max(0, price - distributorPrice)
        )
        const savings = Math.max(0, originalPrice - price)
        const discountPercent = combo.discountPercent || (originalPrice > 0 ? Math.round((savings / originalPrice) * 100) : 0)
        const screens = Number(combo.devicesNumber || combo.devices || 1) || 1

        return {
          id: combo.id,
          sku: combo.sku || combo.id,
          title: combo.title,
          category: combo.category || 'streaming',
          screens,
          providerPrice,
          distributorPrice,
          price,
          originalPrice,
          distributorProfit,
          discountPercent: `${discountPercent}%`,
          services: (combo.services || []).map(s => ({
            platform: s.platform || s,
            plan: s.planLabel || 'Oficial',
            screens: Number(s.units || 1)
          })),
          formatted: {
            provider: formatArs(providerPrice),
            distributor: formatArs(distributorPrice),
            price: formatArs(price),
            originalPrice: formatArs(originalPrice),
            distributorProfit: formatArs(distributorProfit)
          }
        }
      })
      .filter(c => (!reqCategory || reqCategory === 'all') ? true : c.category.toLowerCase() === reqCategory)

    // 3. Generar tablas de solo lectura (sin URLs, sin WhatsApp, sin imágenes)
    const streamingTableSpec = buildStreamingTableLines(streamingList)
    const combosTableSpec = buildCombosTableLines(combosList)

    const streamingPlainTable = renderPlainTable(streamingTableSpec)
    const combosPlainTable = renderPlainTable(combosTableSpec)
    const combinedPlainTable = `${streamingPlainTable}\n\n${combosPlainTable}`

    const streamingPagesRaw = paginateForTelegram(streamingTableSpec)
    const combosPagesRaw = paginateForTelegram(combosTableSpec)

    const reqType = String(req.query.type || '').toLowerCase()
    const reqFormat = String(req.query.format || '').toLowerCase()

    // Modo texto plano directo para lectura pura en tabla (?format=table | ?format=text | ?format=plain)
    if (reqFormat === 'table' || reqFormat === 'text' || reqFormat === 'plain') {
      res.setHeader('Content-Type', 'text/plain; charset=utf-8')
      if (reqType === 'streaming') return res.status(200).send(streamingPlainTable)
      if (reqType === 'combos' || reqType === 'packages') return res.status(200).send(combosPlainTable)
      return res.status(200).send(combinedPlainTable)
    }

    res.setHeader('Content-Type', 'application/json; charset=utf-8')

    const telegramStreaming = wrapTelegramMarkdown(streamingPagesRaw[0] || streamingPlainTable)
    const telegramCombos = wrapTelegramMarkdown(combosPagesRaw[0] || combosPlainTable)
    const telegramStreamingPages = streamingPagesRaw.map(wrapTelegramMarkdown)
    const telegramCombosPages = combosPagesRaw.map(wrapTelegramMarkdown)

    if (reqFormat === 'telegram' || reqType === 'telegram') {
      return res.status(200).json({
        ok: true,
        source: hasLiveFirebase ? 'firebase-live' : 'catalog-fallback',
        currency: 'ARS',
        tables: {
          streaming: streamingPlainTable,
          combos: combosPlainTable,
          all: combinedPlainTable
        },
        telegram: {
          streaming: telegramStreaming,
          combos: telegramCombos,
          streamingPages: telegramStreamingPages,
          combosPages: telegramCombosPages,
          streamingHtml: streamingPagesRaw.map(wrapTelegramHtml),
          combosHtml: combosPagesRaw.map(wrapTelegramHtml)
        }
      })
    }

    if (reqType === 'streaming') {
      return res.status(200).json({
        ok: true,
        source: hasLiveFirebase ? 'firebase-live' : 'catalog-fallback',
        currency: 'ARS',
        summary: {
          totalPlatforms: streamingList.length,
          totalOptions: streamingList.reduce((acc, s) => acc + s.options.length, 0)
        },
        streaming: streamingList,
        table: streamingPlainTable,
        telegramText: telegramStreaming,
        telegramPages: telegramStreamingPages,
        telegramHtml: streamingPagesRaw.map(wrapTelegramHtml)
      })
    }

    if (reqType === 'combos' || reqType === 'packages') {
      return res.status(200).json({
        ok: true,
        source: hasLiveFirebase ? 'firebase-live' : 'catalog-fallback',
        currency: 'ARS',
        summary: {
          totalCombos: combosList.length
        },
        combos: combosList,
        table: combosPlainTable,
        telegramText: telegramCombos,
        telegramPages: telegramCombosPages,
        telegramHtml: combosPagesRaw.map(wrapTelegramHtml)
      })
    }

    return res.status(200).json({
      ok: true,
      source: hasLiveFirebase ? 'firebase-live' : 'catalog-fallback',
      timestamp: new Date().toISOString(),
      currency: 'ARS',
      summary: {
        totalStreaming: streamingList.length,
        totalStreamingOptions: streamingList.reduce((acc, s) => acc + s.options.length, 0),
        totalCombos: combosList.length
      },
      streaming: streamingList,
      combos: combosList,
      tables: {
        streaming: streamingPlainTable,
        combos: combosPlainTable,
        all: combinedPlainTable
      },
      telegram: {
        streaming: telegramStreaming,
        combos: telegramCombos,
        streamingPages: telegramStreamingPages,
        combosPages: telegramCombosPages,
        streamingHtml: streamingPagesRaw.map(wrapTelegramHtml),
        combosHtml: combosPagesRaw.map(wrapTelegramHtml)
      }
    })
  } catch (error) {
    console.error('Error in /api/services endpoint:', error)
    return res.status(500).json({
      ok: false,
      error: 'Error interno al generar catálogo de servicios',
      message: error?.message || String(error)
    })
  }
}

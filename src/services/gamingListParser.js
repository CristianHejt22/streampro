// Servicio de Parsing y Similitud Inteligente de Listas de Videojuegos
// Soporta listas en texto plano o archivos .txt de proveedores mayoristas

// Normalización estricta para comparaciones
export function normalizeStrictTitle(str) {
  if (!str) return ''
  return str
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '') // Quitar tildes y diacríticos
    .replace(/[™®©]/g, '') // Quitar símbolos de marca registrada
    .replace(/\b(ps5|ps4|ps3|digital|deluxe edition|standard edition|gold edition|ultimate edition|remastered|espanol|latino|subtitulado|pack|bundle)\b/gi, '')
    .replace(/[^a-z0-9]/g, '')
    .trim()
}

// Normalización de tokens para búsqueda difusa
export function cleanTokens(str) {
  if (!str) return []
  return str
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[™®©]/g, '')
    .replace(/\b(ps5|ps4|ps3|digital|edition|edicion|remastered|espanol|latino|subtitulado)\b/gi, ' ')
    .replace(/[^a-z0-9\s]/g, ' ')
    .split(/\s+/)
    .filter(t => t.length >= 2)
}

// Expansión de siglas y alias comunes en el mundo gamer
const GAMING_ALIASES = [
  { regex: /\b(ea\s*sports\s*fc|fc)\s*(\d{2,4})\b/i, replace: (m, p1, p2) => `fc${p2}` },
  { regex: /\b(gta\s*v|gta\s*5|grand\s*theft\s*auto\s*v)\b/i, replace: () => 'gta5' },
  { regex: /\b(gow|god\s*of\s*war)\b/i, replace: () => 'godofwar' },
  { regex: /\b(cod|call\s*of\s*duty)\b/i, replace: () => 'callofduty' },
  { regex: /\b007\s*:?\s*first\s*light\b/i, replace: () => '007firstlight' },
  { regex: /\b(ac|assassin'?s?\s*creed)\b/i, replace: () => 'assassinscreed' },
  { regex: /\b(mk\s*1|mk1|mortal\s*kombat\s*1)\b/i, replace: () => 'mortalkombat1' },
  { regex: /\b(spiderman|spider-man)\b/i, replace: () => 'spiderman' },
  { regex: /\b(tlou|the\s*last\s*of\s*us)\b/i, replace: () => 'thelastofus' }
]

export function applyGamingAliases(str) {
  if (!str) return ''
  let s = str.toLowerCase()
  for (const a of GAMING_ALIASES) {
    if (a.regex.test(s)) {
      s = s.replace(a.regex, a.replace)
    }
  }
  return s
}

// Extrae el precio al final de la línea: ej. "- $42.500", ": $12.800", " - 15000", "$42.500"
export function extractPriceFromLine(cleanLine) {
  const priceRegex = /[-–—:\s]*\$?\s*([\d]{1,3}(?:\.[\d]{3})*(?:,[\d]+)?|[\d]{4,7})\s*$/
  const match = cleanLine.match(priceRegex)
  if (!match || !match[1]) return null

  const rawNum = match[1].replace(/\./g, '').replace(',', '.')
  const price = parseFloat(rawNum)
  if (isNaN(price) || price <= 0) return null

  const rawTitle = cleanLine.slice(0, match.index).replace(/[-–—:\s]+$/, '').trim()
  return { price, rawTitle }
}

// Detecta plataforma por texto
export function detectPlatformFromTitle(title) {
  if (!title) return 'PS4 / PS5'
  const upper = title.toUpperCase()
  const hasPs5 = /\bPS5\b/.test(upper)
  const hasPs4 = /\bPS4\b/.test(upper)
  const hasPs3 = /\bPS3\b/.test(upper)

  if (hasPs4 && hasPs5) return 'PS4 / PS5'
  if (hasPs5) return 'PS5'
  if (hasPs4) return 'PS4'
  if (hasPs3) return 'PS3'
  return 'PS4 / PS5'
}

/**
 * Parsea una lista en texto plano omitiendo avisos de fecha o cabeceras
 * @param {string} rawText Texto completo copiado o cargado de un .txt
 * @returns {Array} Lista de juegos parseados y cabeceras detectadas
 */
export function parseGamingTextList(rawText) {
  if (!rawText || typeof rawText !== 'string') {
    return { games: [], headers: [], errors: [] }
  }

  const lines = rawText.split(/\r?\n/)
  const games = []
  const headers = []
  const errors = []

  lines.forEach((line, index) => {
    const trimmed = line.trim()
    if (!trimmed) return

    // Detección de cabeceras, fechas u ofertas destacadas
    const isHeaderLine = 
      /OFERTAS/i.test(trimmed) || 
      /HASTA\s+EL\s+D[IÍ]A/i.test(trimmed) || 
      /PROMO/i.test(trimmed) ||
      /LISTA\s+DE\s+PRECIOS/i.test(trimmed) ||
      (/⚠️/.test(trimmed) && !/\$\s*\d/.test(trimmed)) ||
      /^[=\-*_~]{3,}$/.test(trimmed)

    if (isHeaderLine) {
      headers.push({ index: index + 1, text: trimmed })
      return
    }

    // Limpieza de emojis, signos de pregunta '??', viñetas iniciales
    // Se elimina cualquier caracter no alfanumérico al principio
    const cleanLine = trimmed.replace(/^[^a-zA-Z0-9]+/, '').trim()
    if (!cleanLine) return

    const extracted = extractPriceFromLine(cleanLine)
    if (extracted && extracted.rawTitle) {
      const platform = detectPlatformFromTitle(extracted.rawTitle)
      games.push({
        id: `parsed-${index + 1}-${Date.now().toString().slice(-4)}`,
        lineNumber: index + 1,
        originalLine: trimmed,
        rawTitle: extracted.rawTitle,
        cleanTitle: extracted.rawTitle.replace(/\s*\((ps5|ps4|ps3)\)\s*/gi, '').replace(/\b(ps5|ps4|ps3)\b/gi, '').trim(),
        price: extracted.price,
        platform
      })
    } else {
      errors.push({ index: index + 1, text: trimmed })
    }
  })

  return { games, headers, errors }
}

/**
 * Calcula la puntuación de similitud (0 a 100) entre un título de la lista y un juego del catálogo
 */
export function calculateGameSimilarity(inputTitle, inputPlatform, catGame) {
  const catTitle = catGame.title || ''
  const normInput = normalizeStrictTitle(inputTitle)
  const normCat = normalizeStrictTitle(catTitle)

  // 1. Coincidencia exacta estricta
  if (normInput === normCat && normInput.length > 2) {
    return { score: 100, reason: 'Coincidencia exacta de título' }
  }

  // 2. Coincidencia por alias gamer (FC 25, GTA V, 007 First Light, etc.)
  const aliasInput = normalizeStrictTitle(applyGamingAliases(inputTitle))
  const aliasCat = normalizeStrictTitle(applyGamingAliases(catTitle))
  if (aliasInput && aliasCat && aliasInput === aliasCat) {
    return { score: 98, reason: 'Coincidencia por alias gamer' }
  }

  // 3. Inclusión directa de subcadena
  if (normCat.length >= 5 && normInput.includes(normCat)) {
    return { score: 92, reason: 'Título de catálogo incluido en la lista' }
  }
  if (normInput.length >= 5 && normCat.includes(normInput)) {
    return { score: 90, reason: 'Título de lista incluido en catálogo' }
  }

  // 4. Coincidencia por Tokens (Jaccard similarity)
  const tokensInput = cleanTokens(inputTitle)
  const tokensCat = cleanTokens(catTitle)

  if (tokensInput.length > 0 && tokensCat.length > 0) {
    const setInput = new Set(tokensInput)
    const setCat = new Set(tokensCat)

    let intersection = 0
    for (const t of setInput) {
      if (setCat.has(t)) intersection++
    }

    const union = new Set([...setInput, ...setCat]).size
    const jaccard = intersection / union

    if (intersection === setCat.size && setCat.size >= 2) {
      return { score: Math.round(85 + jaccard * 10), reason: 'Todos los términos coinciden' }
    }
    if (intersection === setInput.size && setInput.size >= 2) {
      return { score: Math.round(80 + jaccard * 10), reason: 'Términos clave coincidentes' }
    }
    if (jaccard >= 0.45) {
      return { score: Math.round(65 + jaccard * 25), reason: 'Similitud léxica alta' }
    }
  }

  return { score: 0, reason: 'Sin coincidencia' }
}

/**
 * Empareja cada juego parseado de la lista con el catálogo de juegos existente
 * @param {Array} parsedGames Juegos extraídos de la lista
 * @param {Array} catalog Catálogo de juegos actual (adminStore.processedGamingProducts)
 * @param {Object} options Configuración de márgenes { storeMarginPercent, resellerCommissionPercent, minResellerProfit }
 */
export function matchGamingListWithCatalog(parsedGames, catalog, options = {}) {
  const storeMargin = Number(options.storeMarginPercent ?? 20)
  const resellerCommission = Number(options.resellerCommissionPercent ?? 10)
  const minProfit = Number(options.minResellerProfit ?? 1000)

  return parsedGames.map(item => {
    let bestGame = null
    let bestScore = 0
    let bestReason = ''

    for (const catGame of catalog) {
      const res = calculateGameSimilarity(item.rawTitle, item.platform, catGame)
      let finalScore = res.score

      // Bonificación si coincide exactamente la plataforma indicada
      if (finalScore >= 60 && item.platform && catGame.platform) {
        if (catGame.platform.includes(item.platform)) {
          finalScore = Math.min(100, finalScore + 2)
        }
      }

      if (finalScore > bestScore) {
        bestScore = finalScore
        bestGame = catGame
        bestReason = res.reason
      }
    }

    const isMatch = bestScore >= 70
    const matchedGame = isMatch ? bestGame : null

    // Cálculo del PVP propuesto con el margen de la tienda
    const markup = Math.ceil((item.price * (storeMargin / 100)) / 100) * 100
    const proposedPvp = item.price + markup
    const proposedResellerProfit = Math.max(minProfit, Math.round(proposedPvp * (resellerCommission / 100)))
    const proposedStoreGross = markup
    const proposedStoreNet = Math.max(0, proposedStoreGross - proposedResellerProfit)

    let matchStatus = 'new' // 'exact' | 'high' | 'partial' | 'new'
    if (bestScore === 100) matchStatus = 'exact'
    else if (bestScore >= 90) matchStatus = 'high'
    else if (bestScore >= 70) matchStatus = 'partial'
    else matchStatus = 'new'

    return {
      ...item,
      matchedGame,
      matchedGameId: matchedGame?.id || null,
      matchedGameTitle: matchedGame?.title || null,
      matchedGamePlatform: matchedGame?.platform || null,
      currentCost: matchedGame?.baseCostPrimary || matchedGame?.providers?.argentinaplay?.costPrimary || matchedGame?.providers?.juegosdigitalesargentina?.costPrimary || 0,
      currentPvp: matchedGame?.effectivePrimaryPrice || 0,
      similarityScore: bestScore,
      similarityReason: bestReason,
      matchStatus,
      isNewGame: !isMatch,
      selected: true, // Checkbox de selección activo por defecto
      newWholesaleCost: item.price,
      proposedPvp,
      proposedResellerProfit,
      proposedStoreGross,
      proposedStoreNet
    }
  })
}

/**
 * Aplica en lote los precios acordados y crea los nuevos juegos en el catálogo
 * @param {Array} analyzedItems Lista de elementos evaluados y aprobados por el admin
 * @param {Array} existingGamingProducts Catálogo original (adminStore.gamingProducts)
 * @param {Object} providerInfo Datos configurados del proveedor (nombre, whatsapp, url, licenseType)
 * @param {Object} options Configuración de márgenes
 */
export function applyGamingBatchUpdate(analyzedItems, existingGamingProducts, providerInfo, options = {}) {
  const storeMargin = Number(options.storeMarginPercent ?? 20)
  const providerKey = (providerInfo.providerKey || providerInfo.name || 'custom_provider').toLowerCase().replace(/[^a-z0-9]/g, '_')
  const providerName = providerInfo.name || 'Proveedor Mayorista'
  const providerUrl = providerInfo.url || ''
  const providerWhatsapp = providerInfo.whatsapp || ''
  const licenseType = providerInfo.licenseType || 'primary' // 'primary' | 'secondary'

  const updatedCatalog = [...existingGamingProducts]
  let updatedMatchesCount = 0
  let createdNewCount = 0

  analyzedItems.forEach(item => {
    if (!item.selected) return

    if (!item.isNewGame && item.matchedGameId) {
      // 1. ACTUALIZAR JUEGO EXISTENTE
      const gameIdx = updatedCatalog.findIndex(g => g.id === item.matchedGameId)
      if (gameIdx !== -1) {
        const game = { ...updatedCatalog[gameIdx] }
        if (!game.providers) game.providers = {}

        // Guardar o actualizar datos del proveedor convenido
        game.providers[providerKey] = {
          providerName,
          providerUrl,
          providerWhatsapp,
          available: true,
          ...(game.providers[providerKey] || {})
        }

        if (licenseType === 'primary') {
          game.providers[providerKey].costPrimary = item.newWholesaleCost
          game.providers[providerKey].primaryPrice = item.proposedPvp
        } else {
          game.providers[providerKey].costSecondary = item.newWholesaleCost
          game.providers[providerKey].secondaryPrice = item.proposedPvp
        }

        // Marcar el proveedor con el precio convenido
        game.lastConveyedProvider = providerName
        game.lastConveyedDate = new Date().toISOString()

        updatedCatalog[gameIdx] = game
        updatedMatchesCount++
      }
    } else {
      // 2. CREAR NUEVO JUEGO EN EL CATÁLOGO
      const newId = `GAME-OFFER-${Date.now().toString().slice(-5)}-${Math.floor(Math.random() * 900 + 100)}`
      const title = item.cleanTitle || item.rawTitle
      const slug = title.toLowerCase().replace(/[^a-z0-9]/g, '-')

      const pArgCost = licenseType === 'primary' ? item.newWholesaleCost : Math.round(item.newWholesaleCost * 1.5)
      const pSecCost = licenseType === 'secondary' ? item.newWholesaleCost : Math.round(item.newWholesaleCost * 0.5)

      const markupPrimary = Math.ceil((pArgCost * (storeMargin / 100)) / 100) * 100
      const pvpPrimary = pArgCost + markupPrimary
      const markupSec = Math.ceil((pSecCost * (storeMargin / 100)) / 100) * 100
      const pvpSec = pSecCost + markupSec

      const newGame = {
        id: newId,
        title,
        slug,
        platform: item.platform || 'PS4 / PS5',
        category: 'Acción & Aventura',
        badge: '🔥 OFERTA MAYORISTA',
        image: 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?w=600&auto=format&fit=crop&q=80',
        description: `Videojuego digital oficial para consolas PlayStation Network provisto por ${providerName}.`,
        features: ['Audio Español', 'Entrega Digital Garantizada', 'Soporte Post-Venta'],
        providers: {
          [providerKey]: {
            providerName,
            providerUrl,
            providerWhatsapp,
            available: true,
            costPrimary: pArgCost,
            costSecondary: pSecCost,
            primaryPrice: pvpPrimary,
            secondaryPrice: pvpSec
          }
        },
        effectivePrimaryPrice: pvpPrimary,
        effectiveSecondaryPrice: pvpSec,
        baseCostPrimary: pArgCost,
        baseCostSecondary: pSecCost,
        lastConveyedProvider: providerName,
        lastConveyedDate: new Date().toISOString()
      }

      updatedCatalog.unshift(newGame)
      createdNewCount++
    }
  })

  return {
    updatedCatalog,
    updatedMatchesCount,
    createdNewCount
  }
}

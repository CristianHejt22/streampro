// Servicio de Parsing y Similitud Inteligente de Listas de Videojuegos
// Soporta listas en texto plano o archivos .txt de proveedores mayoristas

// Expansión de siglas y alias comunes en el mundo gamer
const GAMING_ALIASES = [
  { regex: /\bmas\s+alla\s+de\s+gotham\b/gi, replace: () => 'beyond gotham' },
  { regex: /\b(ea\s*sports\s*)?(fc|fifa)\s*(\d{2,4})\b/gi, replace: (m, p1, p2, p3) => `fc${p3}` },
  { regex: /\b(gta\s*v|gta\s*5|grand\s*theft\s*auto\s*v?)\b/gi, replace: () => 'gta5' },
  { regex: /\b(gow|god\s*of\s*war)\b/gi, replace: () => 'godofwar' },
  { regex: /\b(cod|call\s*of\s*duty)\b/gi, replace: () => 'callofduty' },
  { regex: /\b007\s*:?\s*first\s*light\b/gi, replace: () => '007firstlight' },
  { regex: /\b(ac|assassin'?s?\s*creed)\b/gi, replace: () => 'assassinscreed' },
  { regex: /\b(mk\s*1|mk1|mortal\s*kombat\s*1)\b/gi, replace: () => 'mortalkombat1' },
  { regex: /\b(rdr\s*2|red\s*dead\s*redemption\s*2|red\s*dead\s*redemption\s*ii)\b/gi, replace: () => 'reddeadredemption2' },
  { regex: /\b(spiderman|spider-man)\b/gi, replace: () => 'spiderman' },
  { regex: /\b(tlou|the\s*last\s*of\s*us)\b/gi, replace: () => 'thelastofus' }
]

function normalizeRomanNumerals(str) {
  return str
    .replace(/\b(ii)\b/gi, '2')
    .replace(/\b(iii)\b/gi, '3')
    .replace(/\b(iv)\b/gi, '4')
    .replace(/\b(v)\b/gi, '5')
    .replace(/\b(vi)\b/gi, '6')
    .replace(/\b(vii)\b/gi, '7')
    .replace(/\b(viii)\b/gi, '8')
}

export function applyGamingAliases(str) {
  if (!str) return ''
  let s = str
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[™®©]/g, '')
  s = normalizeRomanNumerals(s)
  for (const a of GAMING_ALIASES) {
    if (a.regex.test(s)) {
      s = s.replace(a.regex, a.replace)
    }
  }
  return s
}

// Normalización estricta para comparaciones
export function normalizeStrictTitle(str) {
  if (!str) return ''
  return applyGamingAliases(str)
    .replace(/\b(ps5|ps4|ps3|digital|original|deluxe edition|standard edition|gold edition|ultimate edition|complete edition|definitive edition|definitive experience|directors?\s*cut|next\s*gea?n|remastered|remake|espanol|latino|subtitulado|pack|bundle|trilogy|collection|retro|edition|edicion|the game)\b/gi, ' ')
    .replace(/[^a-z0-9]/g, '')
    .trim()
}

// Normalización de tokens para búsqueda difusa
export function cleanTokens(str) {
  if (!str) return []
  return applyGamingAliases(str)
    .replace(/\b(ps5|ps4|ps3|digital|original|edition|edicion|deluxe|standard|gold|ultimate|complete|definitive|remastered|remake|espanol|latino|subtitulado|pack|bundle|next|gen|gean|retro)\b/gi, ' ')
    .replace(/[^a-z0-9\s]/g, ' ')
    .split(/\s+/)
    .filter(t => t.length >= 2 || /^\d$/.test(t))
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

    // Recopilar precios de todas las webs scrapeadas para comparar diferencias con la lista
    const allProviders = matchedGame?.activeProvidersList || []
    const webProviders = allProviders.filter(p => p.key !== 'mayorista_txt' && p.costPrimary > 0)
    const lowestWebObj = webProviders.length > 0
      ? webProviders.reduce((min, p) => (p.costPrimary < min.costPrimary ? p : min), webProviders[0])
      : null
    const highestWebObj = webProviders.length > 0
      ? webProviders.reduce((max, p) => (p.costPrimary > max.costPrimary ? p : max), webProviders[0])
      : null

    const currentCost = matchedGame?.baseCostPrimary || highestWebObj?.costPrimary || 0
    const lowestScrapedCost = lowestWebObj?.costPrimary || currentCost
    const cheapestScrapedProviderName = lowestWebObj?.name || matchedGame?.bestFulfillmentPrimaryName || ''

    const diffVsLowestScraped = lowestScrapedCost > 0 ? (item.price - lowestScrapedCost) : 0
    const diffPercentVsLowest = lowestScrapedCost > 0 ? Math.round((Math.abs(diffVsLowestScraped) / lowestScrapedCost) * 100) : 0
    const isCheaperInList = lowestScrapedCost > 0 && item.price < lowestScrapedCost
    const isCheaperInWebs = lowestScrapedCost > 0 && item.price > lowestScrapedCost

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
      scrapedProviders: webProviders,
      currentCost,
      lowestScrapedCost,
      cheapestScrapedProviderName,
      diffVsLowestScraped,
      diffPercentVsLowest,
      isCheaperInList,
      isCheaperInWebs,
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
 * Búsqueda Inteligente para Admin:
 * Soporta títulos con tildes/emojis/alias (FC25, GTA V, Dark Souls 3), líneas pegadas desde .txt,
 * búsqueda por precio ($42.500 o 42500 en cualquier web scrapeada o PVP) y por nombre de proveedor/web scrapeada.
 */
export function smartSearchGamingMatch(game, rawQuery) {
  if (!rawQuery || !rawQuery.trim()) return true
  const trimmed = rawQuery.trim()

  // 1. Detectar si el usuario ingresó una línea completa de .txt (ej: "🤖ASTRO BOT PS5 - $42.500")
  const cleanedLine = trimmed.replace(/^[^a-zA-Z0-9$]+/, '').trim()
  const extracted = extractPriceFromLine(cleanedLine)

  // 2. Búsqueda numérica pura por precio (ej: "42500", "$42.500", "42.500")
  const pureNumStr = trimmed.replace(/[$.,\s]/g, '')
  const isPurePriceQuery = /^\d{3,7}$/.test(pureNumStr)

  const allGamePrices = [
    Number(game.effectivePrimaryPrice) || 0,
    Number(game.effectiveSecondaryPrice) || 0,
    Number(game.baseCostPrimary) || 0,
    Number(game.baseCostSecondary) || 0,
    Number(game.minCostPrimary) || 0,
    Number(game.minCostSecondary) || 0
  ]
  if (game.providers && typeof game.providers === 'object') {
    for (const p of Object.values(game.providers)) {
      if (!p || p.available === false) continue
      if (Number(p.costPrimary) > 0) allGamePrices.push(Number(p.costPrimary))
      if (Number(p.costSecondary) > 0) allGamePrices.push(Number(p.costSecondary))
      if (Number(p.primaryPrice) > 0) allGamePrices.push(Number(p.primaryPrice))
    }
  }

  if (isPurePriceQuery) {
    const targetNum = Number(pureNumStr)
    const matchedByPrice = allGamePrices.some(pr => {
      if (pr <= 0) return false
      return pr === targetNum || String(pr).includes(pureNumStr)
    })
    if (matchedByPrice) return true
    // También permitir que coincida con un juego con número en el título (ej: "2024", "007", "21")
  }

  // 3. Extraer texto de búsqueda (si pegó "Título - $Precio", tomar el título)
  const searchTitlePart = (extracted && extracted.rawTitle && extracted.rawTitle.length >= 2)
    ? extracted.rawTitle
    : cleanedLine

  const searchableFields = [
    game.title || '',
    game.id || '',
    game.platform || '',
    game.category || '',
    game.subcategory || ''
  ]
  if (Array.isArray(game.activeProvidersList)) {
    game.activeProvidersList.forEach(p => {
      searchableFields.push(p.name || '', p.shortName || '', p.key || '')
    })
  } else if (game.providers && typeof game.providers === 'object') {
    Object.entries(game.providers).forEach(([k, p]) => {
      if (p && p.available !== false) searchableFields.push(k, p.providerName || '')
    })
  }

  // 4. Búsqueda directa sin tildes ni reemplazos agresivos contra todos los campos (incluyendo nombres de webs)
  const plainQuery = applyGamingAliases(trimmed).replace(/\s+/g, ' ').trim()
  const combinedText = applyGamingAliases(searchableFields.join(' ')).replace(/\s+/g, ' ').trim()
  if (plainQuery && combinedText.includes(plainQuery)) {
    return true
  }

  // 5. Coincidencia exacta por palabras clave crudas (conservando 'digital', 'games', etc.)
  const rawTokens = plainQuery.replace(/[^a-z0-9\s]/g, ' ').split(/\s+/).filter(t => t.length >= 2)
  if (rawTokens.length > 0) {
    const combinedRawTokens = combinedText.replace(/[^a-z0-9\s]/g, ' ').split(/\s+/).filter(Boolean)
    const allRawFound = rawTokens.every(rt => combinedRawTokens.some(ct => ct.includes(rt)))
    if (allRawFound) return true
  }

  // 6. Coincidencia inteligente normalizada en título (tildes/romanos/ediciones)
  const normQuery = normalizeStrictTitle(searchTitlePart)
  const normGame = normalizeStrictTitle(game.title || '')
  if (normQuery && normGame && normQuery.length >= 3 && rawTokens.some(t => !['digital', 'games', 'edition', 'edicion', 'ps5', 'ps4', 'ps3'].includes(t))) {
    if (normGame.includes(normQuery)) return true
  }

  const queryTokens = cleanTokens(searchTitlePart)
  if (queryTokens.length >= 2 || (queryTokens.length === 1 && !['games', 'game'].includes(queryTokens[0]))) {
    const gameTokenSet = new Set(cleanTokens(searchableFields.join(' ')))
    const allTokensFound = queryTokens.every(qt => {
      for (const gt of gameTokenSet) {
        if (gt.includes(qt) || qt.includes(gt)) return true
      }
      return false
    })
    if (allTokensFound) return true
  }

  // 6. Fallback similitud inteligente si pegó un título largo desde .txt
  if (searchTitlePart.length >= 4) {
    const sim = calculateGameSimilarity(searchTitlePart, '', game)
    if (sim.score >= 70) return true
  }

  return false
}

// Clasificación taxonómica automática de videojuegos
export function inferGamingTaxonomy(title) {
  const t = (title || '').toLowerCase()
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

export function inferGamingBadge(cleanTitle) {
  const t = (cleanTitle || '').toLowerCase()
  if (t.includes('fc 27') || t.includes('fc 25') || t.includes('gta 5') || t.includes('gta v') || t.includes('wukong') || t.includes('black ops 6')) return '⚽ MÁS VENDIDO'
  if (t.includes('astro bot') || t.includes('elden ring') || t.includes('god of war') || t.includes('last of us') || t.includes('silent hill 2')) return '🏆 JUEGO DEL AÑO'
  if (t.includes('007') || t.includes('death stranding 2') || t.includes('wolverine') || t.includes('silent hill f') || t.includes('gta 6')) return '🔥 LANZAMIENTO EXCLUSIVO'
  return '⭐️ PLAYSTATION OFICIAL'
}

export function resolveOfficialGameImage(title, existingCatalog = []) {
  if (!title) return 'https://image.api.playstation.com/vulcan/ap/rnd/202010/0222/b3Dea2fUuO0wFp8W5hJj2bQx.png'

  const normTarget = normalizeStrictTitle(title)
  // 1. Buscar en el catálogo existente
  for (const g of existingCatalog) {
    if (g.image && g.image.includes('playstation.com')) {
      const normG = normalizeStrictTitle(g.title)
      if (normG && (normG === normTarget || (normTarget.length > 5 && normG.includes(normTarget)) || (normG.length > 5 && normTarget.includes(normG)))) {
        return g.image
      }
    }
  }

  // 2. Fallbacks de alta calidad oficiales de PlayStation según categoría
  const t = title.toLowerCase()
  if (t.includes('fc') || t.includes('fifa')) return 'https://image.api.playstation.com/vulcan/ap/rnd/202503/2520/09767c0f0551c8a3edf9ab4727eb084c7cae88fecef16a09.png'
  if (t.includes('gta')) return 'https://image.api.playstation.com/vulcan/ap/rnd/202203/0302/bZ1JTRXzoyl3hkcsloKcCgdB.jpg'
  if (t.includes('spider')) return 'https://image.api.playstation.com/vulcan/ap/rnd/202306/1219/1c7b75d8b4e3f4e1f7c3cb7a5e8f5044a8fbbf111624b423.png'
  if (t.includes('god of war')) return 'https://image.api.playstation.com/vulcan/ap/rnd/202207/1210/4xJ8tPPddCompleteArtwork.png'
  if (t.includes('silent hill')) return 'https://image.api.playstation.com/vulcan/ap/rnd/202405/2912/4eaeb4968fb17726cb75b119102c7f4bb7f5f9a65675402f.png'

  return 'https://image.api.playstation.com/vulcan/ap/rnd/202010/0222/b3Dea2fUuO0wFp8W5hJj2bQx.png'
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

        // Guardar o actualizar datos del proveedor convenido (uso interno de admin)
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

        // Marcar proveedor convenido en admin y preservar marca pública PlayStation Store Oficial
        game.lastConveyedProvider = providerName
        game.lastConveyedDate = new Date().toISOString()
        game.visibleProviderName = 'PlayStation Store Oficial'
        game.visibleProviderShortName = 'PS Store'
        game.visibleProviderBadge = '🎮 PlayStation Store Oficial'
        game.primaryPriceLeaderName = 'PlayStation Store Oficial'
        game.secondaryPriceLeaderName = 'PlayStation Store Oficial'

        updatedCatalog[gameIdx] = game
        updatedMatchesCount++
      }
    } else {
      // 2. CREAR NUEVO JUEGO EN EL CATÁLOGO
      const newId = `GAME-OFFER-${Date.now().toString().slice(-5)}-${Math.floor(Math.random() * 900 + 100)}`
      const title = item.cleanTitle || item.rawTitle
      const slug = title.toLowerCase().replace(/[^a-z0-9]/g, '-')
      const taxonomy = inferGamingTaxonomy(title)
      const officialImage = resolveOfficialGameImage(title, existingGamingProducts)
      const dynamicBadge = inferGamingBadge(title)

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
        category: taxonomy.category,
        subcategory: taxonomy.subcategory,
        badge: dynamicBadge,
        image: officialImage,
        description: `Videojuego digital oficial original para consolas PlayStation (${item.platform || 'PS4 / PS5'}). Descarga directa y activación garantizada desde PlayStation Store.`,
        features: ['Audio Español Oficial', 'Entrega Inmediata Garantizada 24/7', 'Soporte y Asistencia Post-Venta'],
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
        visibleProviderKey: providerKey,
        visibleProviderName: 'PlayStation Store Oficial',
        visibleProviderShortName: 'PS Store',
        visibleProviderBadge: '🎮 PlayStation Store Oficial',
        visibleProviderTagColor: 'bg-indigo-500/20 text-indigo-300 border-indigo-500/30',
        primaryPriceLeaderName: 'PlayStation Store Oficial',
        secondaryPriceLeaderName: 'PlayStation Store Oficial',
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

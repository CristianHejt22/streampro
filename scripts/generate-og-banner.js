import fs from 'node:fs'
import sharp from 'sharp'

async function generateOgBanner() {
  const width = 1200
  const height = 630

  const svg = `
<svg width="${width}" height="${height}" viewBox="0 0 ${width} ${height}" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <!-- Background Gradients -->
    <linearGradient id="bgGrad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#020617"/>
      <stop offset="50%" stop-color="#090d1a"/>
      <stop offset="100%" stop-color="#020617"/>
    </linearGradient>

    <radialGradient id="glowCyan" cx="15%" cy="20%" r="45%">
      <stop offset="0%" stop-color="#0284c7" stop-opacity="0.35"/>
      <stop offset="100%" stop-color="#0284c7" stop-opacity="0"/>
    </radialGradient>

    <radialGradient id="glowPurple" cx="85%" cy="30%" r="50%">
      <stop offset="0%" stop-color="#7c3aed" stop-opacity="0.32"/>
      <stop offset="100%" stop-color="#7c3aed" stop-opacity="0"/>
    </radialGradient>

    <radialGradient id="glowAmber" cx="50%" cy="95%" r="40%">
      <stop offset="0%" stop-color="#d97706" stop-opacity="0.25"/>
      <stop offset="100%" stop-color="#d97706" stop-opacity="0"/>
    </radialGradient>

    <!-- Card Gradients -->
    <linearGradient id="cardGrad1" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#1e112a" stop-opacity="0.95"/>
      <stop offset="100%" stop-color="#0f0918" stop-opacity="0.95"/>
    </linearGradient>

    <linearGradient id="cardGrad2" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#081e36" stop-opacity="0.95"/>
      <stop offset="100%" stop-color="#050e1d" stop-opacity="0.95"/>
    </linearGradient>

    <linearGradient id="cardGrad3" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#241708" stop-opacity="0.95"/>
      <stop offset="100%" stop-color="#100b04" stop-opacity="0.95"/>
    </linearGradient>

    <!-- Border Gradients -->
    <linearGradient id="borderGrad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#38bdf8" stop-opacity="0.7"/>
      <stop offset="50%" stop-color="#818cf8" stop-opacity="0.5"/>
      <stop offset="100%" stop-color="#ec4899" stop-opacity="0.7"/>
    </linearGradient>

    <linearGradient id="logoGrad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#38bdf8"/>
      <stop offset="50%" stop-color="#2563eb"/>
      <stop offset="100%" stop-color="#7c3aed"/>
    </linearGradient>
  </defs>

  <style>
    .font-sans { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
    .title { font-weight: 900; letter-spacing: -0.02em; }
    .badge { font-weight: 800; letter-spacing: 0.05em; text-transform: uppercase; }
  </style>

  <!-- Deep Dark Base Background -->
  <rect width="${width}" height="${height}" fill="url(#bgGrad)"/>

  <!-- Ambient Glow Meshes -->
  <rect width="${width}" height="${height}" fill="url(#glowCyan)"/>
  <rect width="${width}" height="${height}" fill="url(#glowPurple)"/>
  <rect width="${width}" height="${height}" fill="url(#glowAmber)"/>

  <!-- Subtle Cyber Tech Grid Pattern -->
  <g opacity="0.08" stroke="#38bdf8" stroke-width="1">
    <line x1="0" y1="90" x2="1200" y2="90"/>
    <line x1="0" y1="230" x2="1200" y2="230"/>
    <line x1="0" y1="520" x2="1200" y2="520"/>
    <line x1="180" y1="0" x2="180" y2="630"/>
    <line x1="400" y1="0" x2="400" y2="630"/>
    <line x1="800" y1="0" x2="800" y2="630"/>
    <line x1="1020" y1="0" x2="1020" y2="630"/>
  </g>

  <!-- Outer Neon Frame -->
  <rect x="20" y="20" width="1160" height="590" rx="28" fill="none" stroke="url(#borderGrad)" stroke-width="2.5" opacity="0.85"/>

  <!-- TOP HEADER -->
  <g transform="translate(56, 52)">
    <!-- Logo Badge Circle -->
    <rect x="0" y="0" width="56" height="56" rx="16" fill="url(#logoGrad)"/>
    <text x="28" y="37" text-anchor="middle" class="font-sans title" font-size="24" font-weight="900" fill="#ffffff">SP</text>

    <!-- Brand Name -->
    <text x="72" y="32" class="font-sans title" font-size="28" font-weight="900" fill="#ffffff" letter-spacing="0.05em">STREAMEPRO</text>
    <text x="73" y="50" class="font-sans badge" font-size="11" font-weight="700" fill="#38bdf8" letter-spacing="0.12em">PLATAFORMA OFICIAL · STREAMING, GAMING &amp; TECNOLOGÍA</text>

    <!-- Top Right Pills -->
    <g transform="translate(810, 8)">
      <!-- Official Status Badge -->
      <rect x="0" y="0" width="168" height="36" rx="18" fill="#0c1e19" stroke="#10b981" stroke-width="1.5" stroke-opacity="0.6"/>
      <circle cx="18" cy="18" r="5" fill="#10b981"/>
      <text x="32" y="23" class="font-sans badge" font-size="11" font-weight="800" fill="#34d399">TIENDA VERIFICADA</text>

      <!-- Latam Badge -->
      <rect x="180" y="0" width="98" height="36" rx="18" fill="#131c31" stroke="#38bdf8" stroke-width="1.5" stroke-opacity="0.5"/>
      <text x="229" y="23" text-anchor="middle" class="font-sans badge" font-size="11" font-weight="800" fill="#7dd3fc">ARGENTINA</text>
    </g>
  </g>

  <!-- HERO TITLE & VALUE PROPOSITION -->
  <g transform="translate(56, 142)">
    <!-- Promo Pill -->
    <rect x="0" y="0" width="310" height="32" rx="16" fill="#1e1b4b" stroke="#818cf8" stroke-width="1.5" stroke-opacity="0.7"/>
    <text x="14" y="21" class="font-sans badge" font-size="12" font-weight="800" fill="#c7d2fe">⚡ ENTREGA INMEDIATA 24/7 · GARANTÍA OFICIAL</text>

    <!-- Headline -->
    <text x="0" y="74" class="font-sans title" font-size="40" font-weight="900" fill="#ffffff">
      Combos &amp; Streaming 4K <tspan fill="#38bdf8">·</tspan> PlayStation PS5 &amp; PS4
    </text>

    <!-- Subtitle -->
    <text x="0" y="104" class="font-sans" font-size="18" font-weight="500" fill="#94a3b8">
      Perfiles privados con PIN, videojuegos digitales originales y catálogo mayorista de tecnología con envíos a todo el país.
    </text>
  </g>

  <!-- 3 ATTRACTIVE PRODUCT PILLARS (CARDS) -->
  <g transform="translate(56, 276)">
    <!-- CARD 1: COMBOS STREAMING -->
    <g transform="translate(0, 0)">
      <rect width="346" height="200" rx="22" fill="url(#cardGrad1)" stroke="#ec4899" stroke-width="2" stroke-opacity="0.75"/>
      
      <!-- Top Pill -->
      <rect x="16" y="16" width="138" height="26" rx="13" fill="#831843" fill-opacity="0.6"/>
      <text x="26" y="33" class="font-sans badge" font-size="10" font-weight="800" fill="#fbcfe8">🔥 HASTA 45% OFF</text>

      <!-- Title -->
      <text x="16" y="74" class="font-sans title" font-size="20" font-weight="900" fill="#ffffff">Combos &amp; Packs 4K</text>
      <text x="16" y="96" class="font-sans" font-size="13" font-weight="600" fill="#f472b6">Netflix · Disney+ · Max · Prime · Spotify</text>

      <!-- Details -->
      <text x="16" y="128" class="font-sans" font-size="12" font-weight="500" fill="#cbd5e1">✓ Cuentas oficiales con PIN privado</text>
      <text x="16" y="148" class="font-sans" font-size="12" font-weight="500" fill="#cbd5e1">✓ Calidad Ultra HD 4K y reemplazo garantizado</text>

      <!-- Bottom Tag -->
      <g transform="translate(16, 162)">
        <text x="0" y="18" class="font-sans badge" font-size="11" font-weight="800" fill="#f43f5e">DESDE $4.200 ARS →</text>
      </g>
    </g>

    <!-- CARD 2: PLAYSTATION GAMING -->
    <g transform="translate(370, 0)">
      <rect width="346" height="200" rx="22" fill="url(#cardGrad2)" stroke="#38bdf8" stroke-width="2" stroke-opacity="0.75"/>
      
      <!-- Top Pill -->
      <rect x="16" y="16" width="144" height="26" rx="13" fill="#075985" fill-opacity="0.6"/>
      <text x="26" y="33" class="font-sans badge" font-size="10" font-weight="800" fill="#bae6fd">🎮 PS5 &amp; PS4 DIGITAL</text>

      <!-- Title -->
      <text x="16" y="74" class="font-sans title" font-size="20" font-weight="900" fill="#ffffff">Videojuegos Originales</text>
      <text x="16" y="96" class="font-sans" font-size="13" font-weight="600" fill="#38bdf8">Licencias Primarias &amp; Secundarias</text>

      <!-- Details -->
      <text x="16" y="128" class="font-sans" font-size="12" font-weight="500" fill="#cbd5e1">✓ Juegos completos con descarga oficial PSN</text>
      <text x="16" y="148" class="font-sans" font-size="12" font-weight="500" fill="#cbd5e1">✓ Soporte paso a paso para instalación</text>

      <!-- Bottom Tag -->
      <g transform="translate(16, 162)">
        <text x="0" y="18" class="font-sans badge" font-size="11" font-weight="800" fill="#38bdf8">+1.500 TÍTULOS EN STOCK →</text>
      </g>
    </g>

    <!-- CARD 3: CATÁLOGO FÍSICO Y TECNOLOGÍA -->
    <g transform="translate(740, 0)">
      <rect width="346" height="200" rx="22" fill="url(#cardGrad3)" stroke="#f59e0b" stroke-width="2" stroke-opacity="0.75"/>
      
      <!-- Top Pill -->
      <rect x="16" y="16" width="148" height="26" rx="13" fill="#78350f" fill-opacity="0.6"/>
      <text x="26" y="33" class="font-sans badge" font-size="10" font-weight="800" fill="#fde68a">📦 TECNO &amp; BAZAR</text>

      <!-- Title -->
      <text x="16" y="74" class="font-sans title" font-size="20" font-weight="900" fill="#ffffff">Catálogo Mayorista</text>
      <text x="16" y="96" class="font-sans" font-size="13" font-weight="600" fill="#fbbf24">Electrónica · Gadgets · Accesorios</text>

      <!-- Details -->
      <text x="16" y="128" class="font-sans" font-size="12" font-weight="500" fill="#cbd5e1">✓ Precios mayoristas directos de fábrica</text>
      <text x="16" y="148" class="font-sans" font-size="12" font-weight="500" fill="#cbd5e1">✓ Envíos rápidos y seguros a todo el país</text>

      <!-- Bottom Tag -->
      <g transform="translate(16, 162)">
        <text x="0" y="18" class="font-sans badge" font-size="11" font-weight="800" fill="#f59e0b">+360 PRODUCTOS DISPONIBLES →</text>
      </g>
    </g>
  </g>

  <!-- BOTTOM FOOTER BAR -->
  <g transform="translate(56, 528)">
    <line x1="0" y1="0" x2="1088" y2="0" stroke="#334155" stroke-width="1.5" stroke-opacity="0.6"/>

    <g transform="translate(0, 24)">
      <!-- Trust Points -->
      <text x="0" y="18" class="font-sans" font-size="14" font-weight="700" fill="#34d399">
        ✓ Garantía Oficial StreamePro  <tspan fill="#64748b">|</tspan>  ✓ Soporte Humano Directo  <tspan fill="#64748b">|</tspan>  ✓ Compra 100% Protegida
      </text>

      <!-- Web Link Badge -->
      <g transform="translate(860, -4)">
        <rect x="0" y="0" width="228" height="36" rx="18" fill="#0f172a" stroke="#38bdf8" stroke-width="1.5"/>
        <text x="114" y="23" text-anchor="middle" class="font-sans title" font-size="15" font-weight="900" fill="#38bdf8">streamepro.me</text>
      </g>
    </g>
  </g>
</svg>
`

  const svgBuf = Buffer.from(svg)

  // Generate PNG (high fidelity)
  await sharp(svgBuf)
    .png({ quality: 100, compressionLevel: 9 })
    .toFile('public/og-banner.png')
  console.log('public/og-banner.png generated successfully!')

  // Generate JPEG (high quality 92)
  await sharp(svgBuf)
    .jpeg({ quality: 92, progressive: true })
    .toFile('public/og-banner.jpg')
  console.log('public/og-banner.jpg generated successfully!')
}

generateOgBanner().catch(err => {
  console.error('Error generating OG banner:', err)
  process.exit(1)
})

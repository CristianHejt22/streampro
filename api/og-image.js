import fs from 'fs'
import sharp from 'sharp'
import satori from 'satori'
import { resolvePreviewPayload, STREAMING_PLATFORMS_META } from './_catalogLookup.js'

let cachedFonts = null
function loadFonts() {
  if (cachedFonts) return cachedFonts
  cachedFonts = [
    {
      name: 'Inter',
      data: fs.readFileSync(new URL('./fonts/inter-500.woff', import.meta.url)),
      weight: 500,
      style: 'normal'
    },
    {
      name: 'Inter',
      data: fs.readFileSync(new URL('./fonts/inter-700.woff', import.meta.url)),
      weight: 700,
      style: 'normal'
    },
    {
      name: 'Inter',
      data: fs.readFileSync(new URL('./fonts/inter-900.woff', import.meta.url)),
      weight: 900,
      style: 'normal'
    }
  ]
  return cachedFonts
}

function cleanText(str, maxLen = 80) {
  if (!str) return ''
  return String(str)
    .replace(/[\u{1F000}-\u{1FFFF}\u{2600}-\u{27BF}\uFE0F\u200D]/gu, '')
    .replace(/\s+/g, ' ')
    .trim()
    .slice(0, maxLen)
}

async function fetchImageBuffer(url) {
  if (!url) return null
  const tryFetch = async (u, ms) => {
    const res = await fetch(u, {
      signal: AbortSignal.timeout(ms),
      headers: {
        'User-Agent': 'Mozilla/5.0 (compatible; StreameProOG/1.0)',
        Accept: 'image/png,image/jpeg,image/webp,image/*,*/*'
      }
    })
    if (!res.ok) return null
    const buf = Buffer.from(await res.arrayBuffer())
    if (buf.length < 200) return null
    await sharp(buf).metadata()
    return buf
  }

  try {
    const direct = await tryFetch(url, 2600)
    if (direct) return direct
  } catch {}

  try {
    const proxied = `https://wsrv.nl/?url=${encodeURIComponent(url)}&w=800&output=png`
    return await tryFetch(proxied, 2600)
  } catch {
    return null
  }
}

async function createRoundedArtwork(buf, targetW, targetH, radius = 24, bgHex = '#0b1222') {
  const resized = await sharp(buf)
    .resize(targetW, targetH, {
      fit: 'contain',
      background: bgHex
    })
    .ensureAlpha()
    .png()
    .toBuffer()

  const mask = Buffer.from(
    `<svg width="${targetW}" height="${targetH}" viewBox="0 0 ${targetW} ${targetH}" xmlns="http://www.w3.org/2000/svg"><rect x="0" y="0" width="${targetW}" height="${targetH}" rx="${radius}" ry="${radius}" fill="#ffffff"/></svg>`
  )

  return sharp(resized)
    .composite([{ input: mask, blend: 'dest-in' }])
    .png()
    .toBuffer()
}

function el(type, style, children) {
  return {
    type,
    props: {
      style: { boxSizing: 'border-box', ...style },
      children
    }
  }
}

function spLogoCrestNode(refCode = '', compact = false) {
  const refClean = refCode && refCode !== 'DIRECT' ? cleanText(refCode, 14).toUpperCase() : ''
  const size = compact ? 36 : 44
  return el('div', {
    display: 'flex',
    alignItems: 'center',
    gap: compact ? 10 : 12,
    padding: compact ? '7px 16px 7px 9px' : '9px 22px 9px 11px',
    borderRadius: 999,
    background: 'rgba(6, 12, 26, 0.94)',
    border: '2px solid rgba(56, 189, 248, 0.72)',
    boxShadow: '0 10px 28px rgba(0, 0, 0, 0.65)'
  }, [
    el('div', {
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      width: size,
      height: size,
      borderRadius: 999,
      background: 'linear-gradient(135deg, #38bdf8 0%, #2563eb 55%, #4f46e5 100%)',
      color: '#ffffff',
      fontWeight: 900,
      fontSize: compact ? 15 : 19,
      letterSpacing: '-0.03em'
    }, 'SP'),
    el('div', {
      display: 'flex',
      flexDirection: 'column',
      justifyContent: 'center'
    }, [
      el('div', {
        display: 'flex',
        fontSize: compact ? 16 : 19,
        fontWeight: 900,
        color: '#ffffff',
        letterSpacing: '0.04em',
        lineHeight: 1.05
      }, 'STREAMEPRO'),
      el('div', {
        display: 'flex',
        fontSize: compact ? 10 : 11,
        fontWeight: 700,
        color: '#38bdf8',
        letterSpacing: '0.1em',
        marginTop: 2,
        lineHeight: 1.05
      }, refClean ? `TIENDA OFICIAL · ${refClean}` : 'TIENDA OFICIAL')
    ])
  ])
}

function getThemeColors(type) {
  if (type === 'physical') {
    return {
      accent: '#fbbf24',
      pillBg: 'rgba(245, 158, 11, 0.22)',
      pillBorder: 'rgba(251, 191, 36, 0.6)',
      pillText: '#fde68a'
    }
  }
  if (type === 'package') {
    return {
      accent: '#f472b6',
      pillBg: 'rgba(236, 72, 153, 0.22)',
      pillBorder: 'rgba(244, 114, 182, 0.6)',
      pillText: '#fbcfe8'
    }
  }
  return {
    accent: '#38bdf8',
    pillBg: 'rgba(56, 189, 248, 0.2)',
    pillBorder: 'rgba(56, 189, 248, 0.58)',
    pillText: '#bae6fd'
  }
}

function buildSingleOverlayVNode(payload, hasPhoto) {
  const theme = getThemeColors(payload.type)
  const titleText = cleanText(payload.shortTitle || payload.title || 'Producto Oficial StreamePro', 76)
  const titleSize = titleText.length > 52 ? 32 : titleText.length > 34 ? 38 : 44
  const badgeText = cleanText(payload.badge || 'OFICIAL', 34)
  const priceText = cleanText(payload.priceStr || 'Consultar Precio', 28)
  const subText = cleanText(payload.subtitle || 'Garantía Oficial y Entrega Rápida en StreamePro', 115)

  const servicesList = payload.type === 'package' && Array.isArray(payload.services)
    ? payload.services.slice(0, 4)
    : []

  const streamingMeta = payload.type === 'streaming'
    ? (STREAMING_PLATFORMS_META[payload.platform] || STREAMING_PLATFORMS_META.Netflix)
    : null

  const leftHeroFallback = !hasPhoto
    ? el('div', {
        display: 'flex',
        flexDirection: 'column',
        alignItems: 'center',
        justifyContent: 'center',
        width: '100%',
        height: '100%',
        background: streamingMeta
          ? `linear-gradient(155deg, ${streamingMeta.bgGrad[0]} 0%, ${streamingMeta.bgGrad[1]} 100%)`
          : 'linear-gradient(155deg, #172554 0%, #090d16 100%)',
        padding: 32,
        textAlign: 'center'
      }, [
        el('div', {
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          width: 112,
          height: 112,
          borderRadius: 32,
          background: 'rgba(255,255,255,0.08)',
          border: `2px solid ${streamingMeta?.accentHex || theme.accent}`,
          fontSize: 44,
          fontWeight: 900,
          color: '#ffffff',
          marginBottom: 18
        }, payload.type === 'package' ? 'VIP' : cleanText(payload.platform || payload.title || 'SP', 2).toUpperCase()),
        el('div', {
          display: 'flex',
          fontSize: 28,
          fontWeight: 900,
          color: '#ffffff',
          textAlign: 'center',
          justifyContent: 'center'
        }, cleanText(payload.platform || 'STREAMEPRO', 24).toUpperCase()),
        servicesList.length > 0
          ? el('div', {
              display: 'flex',
              flexWrap: 'wrap',
              justifyContent: 'center',
              gap: 8,
              marginTop: 20
            }, servicesList.map(s => el('div', {
              display: 'flex',
              padding: '6px 14px',
              borderRadius: 999,
              background: 'rgba(15,23,42,0.85)',
              border: '1px solid rgba(255,255,255,0.2)',
              color: '#f8fafc',
              fontSize: 15,
              fontWeight: 700
            }, cleanText(s.platform, 18))))
          : el('div', {
              display: 'flex',
              fontSize: 15,
              fontWeight: 700,
              color: streamingMeta?.accentHex || theme.accent,
              marginTop: 10
            }, 'CUENTA OFICIAL VERIFICADA')
      ])
    : null

  return el('div', {
    display: 'flex',
    position: 'relative',
    width: 1200,
    height: 630,
    fontFamily: 'Inter'
  }, [
    // Marco izquierdo exacto (x: 48, y: 48, w: 468, h: 534) — fondo transparente para no apagar la portada
    el('div', {
      display: 'flex',
      position: 'absolute',
      left: 48,
      top: 48,
      width: 468,
      height: 534,
      borderRadius: 28,
      border: `2.5px solid ${theme.pillBorder}`,
      background: hasPhoto ? 'transparent' : 'rgba(9, 13, 22, 0.92)',
      overflow: 'hidden'
    }, [
      leftHeroFallback,
      el('div', {
        display: 'flex',
        position: 'absolute',
        top: 16,
        left: 16
      }, [spLogoCrestNode(payload.refCode, false)])
    ]),

    // Columna derecha exacta (x: 552, w: 600 — termina en 1152 con 48px margen derecho)
    el('div', {
      display: 'flex',
      position: 'absolute',
      left: 552,
      top: 52,
      width: 600,
      height: 526,
      flexDirection: 'column',
      justifyContent: 'space-between'
    }, [
      el('div', {
        display: 'flex',
        flexDirection: 'column',
        gap: 16,
        width: '100%'
      }, [
        el('div', {
          display: 'flex',
          alignItems: 'center'
        }, [
          el('div', {
            display: 'flex',
            padding: '8px 18px',
            borderRadius: 999,
            background: theme.pillBg,
            border: `1.5px solid ${theme.pillBorder}`,
            color: theme.pillText,
            fontSize: 15,
            fontWeight: 900,
            letterSpacing: '0.07em'
          }, badgeText)
        ]),
        el('div', {
          display: 'flex',
          width: '100%',
          fontSize: titleSize,
          fontWeight: 900,
          color: '#ffffff',
          lineHeight: 1.14,
          letterSpacing: '-0.02em'
        }, titleText),
        el('div', {
          display: 'flex',
          width: '100%',
          fontSize: 19,
          fontWeight: 500,
          color: '#cbd5e1',
          lineHeight: 1.38
        }, subText)
      ]),

      el('div', {
        display: 'flex',
        flexDirection: 'column',
        gap: 16,
        width: '100%'
      }, [
        el('div', {
          display: 'flex',
          flexDirection: 'column',
          width: '100%',
          padding: '20px 24px',
          borderRadius: 24,
          background: 'rgba(11, 18, 34, 0.9)',
          border: `2px solid ${theme.pillBorder}`
        }, [
          el('div', {
            display: 'flex',
            width: '100%',
            alignItems: 'center',
            justifyContent: 'space-between'
          }, [
            el('div', {
              display: 'flex',
              fontSize: 13,
              fontWeight: 700,
              color: '#94a3b8',
              letterSpacing: '0.12em'
            }, 'PRECIO OFICIAL'),
            payload.discountPct > 0
              ? el('div', {
                  display: 'flex',
                  padding: '4px 12px',
                  borderRadius: 999,
                  background: '#dc2626',
                  color: '#ffffff',
                  fontSize: 13,
                  fontWeight: 900
                }, `${payload.discountPct}% OFF`)
              : payload.subPriceStr
                ? el('div', {
                    display: 'flex',
                    fontSize: 14,
                    fontWeight: 700,
                    color: '#a5b4fc'
                  }, cleanText(payload.subPriceStr, 36))
                : null
          ]),
          el('div', {
            display: 'flex',
            fontSize: 48,
            fontWeight: 900,
            color: theme.accent,
            marginTop: 6,
            letterSpacing: '-0.02em',
            lineHeight: 1.05
          }, priceText)
        ]),

        el('div', {
          display: 'flex',
          width: '100%',
          alignItems: 'center',
          justifyContent: 'space-between',
          paddingTop: 12,
          borderTop: '1px solid rgba(255, 255, 255, 0.16)',
          fontSize: 16,
          fontWeight: 700
        }, [
          el('div', { display: 'flex', color: '#34d399' }, 'Garantía Oficial Activa'),
          el('div', { display: 'flex', color: '#38bdf8', fontWeight: 900 }, 'streamepro.me')
        ])
      ])
    ])
  ])
}

function buildMultiOverlayVNode(payload, slots) {
  const theme = getThemeColors(payload.type)
  const headline = cleanText(payload.headline || 'Selección Oficial StreamePro', 48)
  const badgeText = cleanText(payload.badge || 'SELECCIÓN OFICIAL', 30)

  return el('div', {
    display: 'flex',
    position: 'relative',
    width: 1200,
    height: 630,
    fontFamily: 'Inter'
  }, [
    // Cabecera superior izquierda: Logo StreamePro (x: 44, y: 28)
    el('div', {
      display: 'flex',
      position: 'absolute',
      left: 44,
      top: 28
    }, [spLogoCrestNode(payload.refCode, false)]),

    // Cabecera superior derecha: Pill + Título (x: 430, w: 726 — termina en 1156)
    el('div', {
      display: 'flex',
      position: 'absolute',
      left: 430,
      top: 26,
      width: 726,
      flexDirection: 'column',
      alignItems: 'flex-end'
    }, [
      el('div', {
        display: 'flex',
        padding: '5px 14px',
        borderRadius: 999,
        background: theme.pillBg,
        border: `1.5px solid ${theme.pillBorder}`,
        color: theme.pillText,
        fontSize: 12,
        fontWeight: 900,
        letterSpacing: '0.08em'
      }, badgeText),
      el('div', {
        display: 'flex',
        fontSize: headline.length > 34 ? 26 : 31,
        fontWeight: 900,
        color: '#ffffff',
        marginTop: 6,
        textAlign: 'right',
        lineHeight: 1.15
      }, headline)
    ]),

    // Marco y pie exactos por tarjeta (alineados píxel a píxel con Sharp)
    ...slots.flatMap((slot) => [
      el('div', {
        display: 'flex',
        position: 'absolute',
        left: slot.cardX,
        top: slot.cardY,
        width: slot.cardW,
        height: slot.cardH,
        borderRadius: 24,
        border: `2px solid ${theme.pillBorder}`,
        background: slot.hasPhoto ? 'transparent' : 'rgba(15, 23, 42, 0.92)'
      }, []),

      el('div', {
        display: 'flex',
        position: 'absolute',
        left: slot.cardX + 10,
        top: slot.cardY + slot.photoH + 18,
        width: slot.cardW - 20,
        height: 132,
        flexDirection: 'column',
        justifyContent: 'space-between',
        padding: '10px 12px',
        borderRadius: 16,
        background: 'rgba(6, 11, 22, 0.94)',
        border: '1px solid rgba(255,255,255,0.14)'
      }, [
        el('div', {
          display: 'flex',
          flexDirection: 'column',
          gap: 4
        }, [
          el('div', {
            display: 'flex',
            fontSize: 12,
            fontWeight: 700,
            color: '#94a3b8'
          }, cleanText(slot.item.platform || 'OFICIAL', 20)),
          el('div', {
            display: 'flex',
            fontSize: 15,
            fontWeight: 900,
            color: '#ffffff',
            lineHeight: 1.18
          }, cleanText(slot.item.title, 34))
        ]),
        el('div', {
          display: 'flex',
          fontSize: 21,
          fontWeight: 900,
          color: theme.accent
        }, cleanText(slot.item.priceStr, 20))
      ])
    ]),

    // Pie inferior (y: 576..610)
    el('div', {
      display: 'flex',
      position: 'absolute',
      left: 44,
      top: 574,
      width: 1112,
      alignItems: 'center',
      justifyContent: 'space-between',
      paddingTop: 10,
      borderTop: '1px solid rgba(255, 255, 255, 0.16)',
      fontSize: 16,
      fontWeight: 700
    }, [
      el('div', { display: 'flex', color: '#34d399' }, 'Catálogo Verificado · Entrega Rápida y Garantía'),
      el('div', { display: 'flex', color: '#38bdf8', fontWeight: 900 }, 'streamepro.me')
    ])
  ])
}

export async function renderOgJpegBuffer(payload) {
  const fonts = loadFonts()
  const width = 1200
  const height = 630

  if (payload.mode === 'multi' && Array.isArray(payload.items) && payload.items.length > 1) {
    const items = payload.items.slice(0, 4)
    const count = items.length
    const gap = 20
    const containerW = 1112
    const cardW = Math.floor((containerW - gap * (count - 1)) / count)
    const cardH = 432
    const cardY = 124
    const photoH = 266

    const fetchedBuffers = await Promise.all(items.map(it => fetchImageBuffer(it.image)))
    const firstPhoto = fetchedBuffers.find(Boolean)

    let baseSharp = sharp({
      create: { width, height, channels: 4, background: '#070c18' }
    })

    if (firstPhoto) {
      const backdrop = await sharp(firstPhoto)
        .resize(width, height, { fit: 'cover' })
        .blur(38)
        .modulate({ brightness: 0.24, saturation: 1.2 })
        .toBuffer()
      baseSharp = sharp(backdrop)
    }

    const composites = []
    const slots = []
    for (let i = 0; i < count; i++) {
      const cardX = 44 + i * (cardW + gap)
      const buf = fetchedBuffers[i]
      if (buf) {
        const rounded = await createRoundedArtwork(buf, cardW - 20, photoH, 16, '#0b1222')
        composites.push({ input: rounded, left: cardX + 10, top: cardY + 10 })
      }
      slots.push({ item: items[i], cardX, cardY, cardW, cardH, photoH, hasPhoto: Boolean(buf) })
    }

    const overlaySvg = await satori(buildMultiOverlayVNode(payload, slots), {
      width,
      height,
      fonts
    })
    composites.push({ input: Buffer.from(overlaySvg), left: 0, top: 0 })

    return baseSharp
      .composite(composites)
      .jpeg({ quality: 88, progressive: true })
      .toBuffer()
  }

  // Modo Producto Individual
  const rawImg = payload.image || ''
  const photoBuf = await fetchImageBuffer(rawImg)

  let baseSharp = sharp({
    create: { width, height, channels: 4, background: '#060b16' }
  })

  if (photoBuf) {
    const backdrop = await sharp(photoBuf)
      .resize(width, height, { fit: 'cover' })
      .blur(36)
      .modulate({ brightness: 0.25, saturation: 1.2 })
      .toBuffer()
    baseSharp = sharp(backdrop)
  }

  const composites = []
  if (photoBuf) {
    const heroPhoto = await createRoundedArtwork(photoBuf, 464, 530, 26, '#0b1222')
    composites.push({ input: heroPhoto, left: 50, top: 50 })
  }

  const overlaySvg = await satori(buildSingleOverlayVNode(payload, Boolean(photoBuf)), {
    width,
    height,
    fonts
  })
  composites.push({ input: Buffer.from(overlaySvg), left: 0, top: 0 })

  return baseSharp
    .composite(composites)
    .jpeg({ quality: 88, progressive: true })
    .toBuffer()
}

export default async function handler(req, res) {
  try {
    const payload = await resolvePreviewPayload(req.query)
    const jpeg = await renderOgJpegBuffer(payload)
    res.setHeader('Content-Type', 'image/jpeg')
    res.setHeader('Cache-Control', 'public, max-age=86400, s-maxage=604800, stale-while-revalidate=86400')
    return res.status(200).send(jpeg)
  } catch (err) {
    console.error('OG image render error:', err)
    res.setHeader('X-Og-Error', String(err?.message || err || 'unknown').replace(/[\r\n]/g, ' ').slice(0, 180))
    return res.redirect(302, 'https://streamepro.me/og-banner.png')
  }
}

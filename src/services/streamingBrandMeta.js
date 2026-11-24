/**
 * Metadatos de Marcas, Colores e Iconos SVG de Streaming
 * Utilizados en HomeView, ResellerShopView, ResellerDashboard y AdminDashboard
 */

export const STREAMING_BRAND_METADATA = {
  Netflix: {
    badge: '4K ULTRA HD • HDR10',
    deliveryBadge: '🟢 10-15m',
    badgeClass: 'bg-red-500/15 text-red-400 border-red-500/30',
    accentGlow: 'hover:shadow-[0_0_30px_rgba(229,9,20,0.22)]',
    borderClass: 'border-white/[0.08] hover:border-red-500/50',
    gradientBg: 'from-red-950/25 via-slate-900/90 to-slate-950',
    accentColor: 'text-red-400',
    iconSvg: `<svg class="w-5 h-5 text-red-500" viewBox="0 0 24 24" fill="currentColor"><path d="M4 2h4.5l7.5 13.5V2H20v20h-4.5L8 8.5V22H4V2z"/></svg>`,
    warranty: 'Garantía Oficial Netflix Argentina',
    highlights: [
      'PIN Privado: 4 dígitos exclusivo.',
      'Sin bloqueo hogar: En Argentina (actualizar hogar).',
      'Renovación mensual: Misma cuenta mes a mes (Modificación de cuenta).'
    ]
  },
  'Netflix Perfil Extra': {
    badge: '⭐ SIN BLOQUEO',
    deliveryBadge: '🟢 10-15m',
    badgeClass: 'bg-rose-500/15 text-rose-400 border-rose-500/30',
    accentGlow: 'hover:shadow-[0_0_30px_rgba(244,63,94,0.22)]',
    borderClass: 'border-white/[0.08] hover:border-rose-500/50',
    gradientBg: 'from-rose-950/25 via-slate-900/90 to-slate-950',
    accentColor: 'text-rose-400',
    iconSvg: `<svg class="w-5 h-5 text-rose-500" viewBox="0 0 24 24" fill="currentColor"><path d="M4 2h4.5l7.5 13.5V2H20v20h-4.5L8 8.5V22H4V2z"/><circle cx="19" cy="5" r="3" fill="#22c55e"/></svg>`,
    warranty: 'Garantía Oficial Netflix Argentina',
    highlights: [
      'PIN Privado: 4 dígitos exclusivo.',
      'Sin bloqueo hogar: En Argentina (Smart TV directa).',
      'Renovación mensual: Misma cuenta mes a mes (Modificación de cuenta).'
    ]
  },
  'Disney+': {
    badge: '4K UHD • IMAX ENHANCED',
    deliveryBadge: '🟢 10-15m',
    badgeClass: 'bg-sky-500/15 text-sky-300 border-sky-500/30',
    accentGlow: 'hover:shadow-[0_0_30px_rgba(0,168,255,0.22)]',
    borderClass: 'border-white/[0.08] hover:border-sky-500/50',
    gradientBg: 'from-blue-950/30 via-slate-900/90 to-slate-950',
    accentColor: 'text-sky-300',
    iconSvg: `<svg class="w-5 h-5 text-sky-400" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.4 7.4h7.6l-6.2 4.5 2.4 7.4-6.2-4.5-6.2 4.5 2.4-7.4-6.2-4.5h7.6z"/></svg>`,
    warranty: 'Garantía Oficial StreamePro',
    highlights: ['Disney + Pixar + Marvel + ESPN', 'Perfil 100% privado con PIN', 'Soporte prioritario y garantía SLA']
  },
  Max: {
    badge: 'PLATINO 4K • DOLBY ATMOS',
    deliveryBadge: '🟢 10-15m',
    badgeClass: 'bg-indigo-500/15 text-indigo-300 border-indigo-500/30',
    accentGlow: 'hover:shadow-[0_0_30px_rgba(99,102,241,0.22)]',
    borderClass: 'border-white/[0.08] hover:border-indigo-500/50',
    gradientBg: 'from-indigo-950/30 via-slate-900/90 to-slate-950',
    accentColor: 'text-indigo-300',
    iconSvg: `<svg class="w-5 h-5 text-indigo-400" viewBox="0 0 24 24" fill="currentColor"><path d="M4 4h4l4 7 4-7h4v16h-4v-9l-4 7-4-7v9H4V4z"/></svg>`,
    warranty: 'Garantía Oficial StreamePro',
    highlights: ['Series HBO completas y estrenos Warner', 'Audio envolvente Dolby Atmos', 'PIN personal de 4 dígitos']
  },
  Spotify: {
    badge: 'AUDIO HI-FI • 320 KBPS',
    deliveryBadge: '🟢 10-15m',
    badgeClass: 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30',
    accentGlow: 'hover:shadow-[0_0_30px_rgba(16,185,129,0.22)]',
    borderClass: 'border-white/[0.08] hover:border-emerald-500/50',
    gradientBg: 'from-emerald-950/25 via-slate-900/90 to-slate-950',
    accentColor: 'text-emerald-400',
    iconSvg: `<svg class="w-5 h-5 text-emerald-400" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm4.586 14.424a.625.625 0 01-.86.208c-2.355-1.44-5.32-1.766-8.814-.967a.625.625 0 11-.28-1.22c3.822-.876 7.1-.508 9.746 1.119a.625.625 0 01.208.86zm1.226-2.727a.782.782 0 01-1.077.257c-2.695-1.656-6.804-2.135-9.992-1.168a.782.782 0 11-.456-1.498c3.642-1.106 8.188-.574 11.268 1.332a.782.782 0 01.257 1.077zm.106-2.836C14.69 8.905 9.387 8.73 6.307 9.664a.937.937 0 11-.544-1.792c3.535-1.073 9.404-.87 12.87 1.188a.938.938 0 11-.97 1.604z"/></svg>`,
    warranty: 'Garantía Oficial StreamePro',
    highlights: ['Música ilimitada sin publicidad', 'Descargas offline de playlists', 'Calidad máxima 320 kbps']
  },
  IPTV: {
    badge: 'FHD 1080P • +1200 CANALES',
    deliveryBadge: '🟢 10-15m',
    badgeClass: 'bg-amber-500/15 text-amber-300 border-amber-500/30',
    accentGlow: 'hover:shadow-[0_0_30px_rgba(245,158,11,0.22)]',
    borderClass: 'border-white/[0.08] hover:border-amber-500/50',
    gradientBg: 'from-amber-950/25 via-slate-900/90 to-slate-950',
    accentColor: 'text-amber-400',
    iconSvg: `<svg class="w-5 h-5 text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="15" rx="2" ry="2"></rect><polyline points="17 2 12 7 7 2"></polyline></svg>`,
    warranty: 'Garantía Oficial StreamePro',
    highlights: ['Fútbol argentino y eventos en vivo', '+1200 Canales en vivo FHD sin cortes', 'Compatible con Smart TV y celular']
  },
  'Prime Video': {
    badge: '4K ULTRA HD • ORIGINALS',
    deliveryBadge: '🟢 10-15m',
    badgeClass: 'bg-cyan-500/15 text-cyan-300 border-cyan-500/30',
    accentGlow: 'hover:shadow-[0_0_30px_rgba(6,182,212,0.22)]',
    borderClass: 'border-white/[0.08] hover:border-cyan-500/50',
    gradientBg: 'from-cyan-950/25 via-slate-900/90 to-slate-950',
    accentColor: 'text-cyan-400',
    iconSvg: `<svg class="w-5 h-5 text-cyan-400" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>`,
    warranty: 'Garantía Oficial StreamePro',
    highlights: ['Series Amazon Originals y cine', 'Audio 5.1 y resolución 4K HDR', 'Perfil individual con PIN exclusivo']
  },
  'YouTube Premium': {
    badge: '4K 60FPS • SIN PUBLICIDAD',
    deliveryBadge: '🟢 10-15m',
    badgeClass: 'bg-rose-500/15 text-rose-300 border-rose-500/30',
    accentGlow: 'hover:shadow-[0_0_30px_rgba(244,63,94,0.22)]',
    borderClass: 'border-white/[0.08] hover:border-rose-500/50',
    gradientBg: 'from-rose-950/25 via-slate-900/90 to-slate-950',
    accentColor: 'text-rose-400',
    iconSvg: `<svg class="w-5 h-5 text-rose-400" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>`,
    warranty: 'Garantía Oficial StreamePro',
    highlights: ['Sin anuncios en toda la app', 'YouTube Music en segundo plano', 'Activación a tu propio correo']
  },
  'Canva Pro': {
    badge: 'MAGIC STUDIO • KIT DE MARCAS',
    deliveryBadge: '🟢 10-15m',
    badgeClass: 'bg-purple-500/15 text-purple-300 border-purple-500/30',
    accentGlow: 'hover:shadow-[0_0_30px_rgba(168,85,247,0.22)]',
    borderClass: 'border-white/[0.08] hover:border-purple-500/50',
    gradientBg: 'from-purple-950/25 via-slate-900/90 to-slate-950',
    accentColor: 'text-purple-400',
    iconSvg: `<svg class="w-5 h-5 text-purple-400" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>`,
    warranty: 'Garantía Oficial StreamePro',
    highlights: ['Plantillas y fotos premium ilimitadas', 'Eliminador de fondos con 1 clic', 'Activación a tu propio correo']
  },
  'Paramount+': {
    badge: 'FULL HD • PREMIER LEAGUE',
    deliveryBadge: '🟢 10-15m',
    badgeClass: 'bg-blue-600/15 text-blue-300 border-blue-500/30',
    accentGlow: 'hover:shadow-[0_0_30px_rgba(37,99,235,0.22)]',
    borderClass: 'border-white/[0.08] hover:border-blue-500/50',
    gradientBg: 'from-blue-950/25 via-slate-900/90 to-slate-950',
    accentColor: 'text-blue-400',
    iconSvg: `<svg class="w-5 h-5 text-blue-400" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L4 18h16L12 2zm0 4.5l5.2 10.5H6.8L12 6.5z"/></svg>`,
    warranty: 'Garantía Oficial StreamePro',
    highlights: ['Series exclusivas, cine de estreno y Halo', 'Premier League en vivo y eventos deportivos', 'Perfil 100% individual y privado con PIN']
  },
  'Crunchyroll': {
    badge: 'MEGA FAN • SIMULCAST ANIME',
    deliveryBadge: '🟢 10-15m',
    badgeClass: 'bg-orange-500/15 text-orange-300 border-orange-500/30',
    accentGlow: 'hover:shadow-[0_0_30px_rgba(249,115,22,0.22)]',
    borderClass: 'border-white/[0.08] hover:border-orange-500/50',
    gradientBg: 'from-orange-950/25 via-slate-900/90 to-slate-950',
    accentColor: 'text-orange-400',
    iconSvg: `<svg class="w-5 h-5 text-orange-400" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"/><circle cx="14" cy="10" r="4" fill="#090d16"/></svg>`,
    warranty: 'Garantía Oficial StreamePro',
    highlights: ['Simulcast 1 hora después de Japón en HD/4K', 'Anime sin anuncios y doblajes exclusivos', 'Perfil individual con PIN y descargas offline']
  },
  'ChatGPT Plus': {
    badge: 'GPT-4o • CANVAS & SORA',
    deliveryBadge: '🟢 10-15m',
    badgeClass: 'bg-teal-500/15 text-teal-300 border-teal-500/30',
    accentGlow: 'hover:shadow-[0_0_30px_rgba(20,184,166,0.22)]',
    borderClass: 'border-white/[0.08] hover:border-teal-500/50',
    gradientBg: 'from-teal-950/25 via-slate-900/90 to-slate-950',
    accentColor: 'text-teal-400',
    iconSvg: `<svg class="w-5 h-5 text-teal-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a10 10 0 0 1 10 10c0 4.418-2.865 8.167-6.839 9.49L15 18l-3 3-3-3-.161 3.49C4.865 20.167 2 16.418 2 12A10 10 0 0 1 12 2z"/></svg>`,
    warranty: 'Garantía Oficial StreamePro',
    highlights: ['Acceso prioritario al modelo GPT-4o sin límites', 'Herramientas avanzadas: Canvas, Análisis de Datos y DALL-E', 'Cuenta y acceso privado de alta velocidad']
  },
  'CapCut Pro': {
    badge: 'PRO EDICIÓN • SIN MARCA',
    deliveryBadge: '🟢 10-15m',
    badgeClass: 'bg-sky-500/15 text-sky-300 border-sky-500/30',
    accentGlow: 'hover:shadow-[0_0_30px_rgba(14,165,233,0.22)]',
    borderClass: 'border-white/[0.08] hover:border-sky-500/50',
    gradientBg: 'from-sky-950/25 via-slate-900/90 to-slate-950',
    accentColor: 'text-sky-400',
    iconSvg: `<svg class="w-5 h-5 text-sky-400" viewBox="0 0 24 24" fill="currentColor"><path d="M6 3a3 3 0 0 0-3 3v12a3 3 0 0 0 3 3h12a3 3 0 0 0 3-3V6a3 3 0 0 0-3-3H6zm10.5 5.5l-5 3.5 5 3.5V8.5zM7 9h3v6H7V9z"/></svg>`,
    warranty: 'Garantía Oficial StreamePro',
    highlights: ['Plantillas VIP, efectos y transiciones Pro ilimitadas', 'Exportación en 4K 60fps sin marca de agua', 'Activación directa y soporte garantizado']
  },
  'Viki Rakuten': {
    badge: 'KDRAMA • PASS STANDARD HD',
    deliveryBadge: '🟢 10-15m',
    badgeClass: 'bg-cyan-500/15 text-cyan-300 border-cyan-500/30',
    accentGlow: 'hover:shadow-[0_0_30px_rgba(6,182,212,0.22)]',
    borderClass: 'border-white/[0.08] hover:border-cyan-500/50',
    gradientBg: 'from-cyan-950/25 via-slate-900/90 to-slate-950',
    accentColor: 'text-cyan-400',
    iconSvg: `<svg class="w-5 h-5 text-cyan-400" viewBox="0 0 24 24" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>`,
    warranty: 'Garantía Oficial StreamePro',
    highlights: ['Doramas, K-dramas y cine asiático exclusivo', 'Transmisión en Full HD sin publicidad', 'Perfil individual con PIN y subtítulos oficiales']
  },
  'ViX': {
    badge: 'VIX PREMIUM • LIGA MX & NOVELAS',
    deliveryBadge: '🟢 10-15m',
    badgeClass: 'bg-amber-600/15 text-amber-300 border-amber-500/30',
    accentGlow: 'hover:shadow-[0_0_30px_rgba(217,119,6,0.22)]',
    borderClass: 'border-white/[0.08] hover:border-amber-500/50',
    gradientBg: 'from-amber-950/25 via-slate-900/90 to-slate-950',
    accentColor: 'text-amber-400',
    iconSvg: `<svg class="w-5 h-5 text-amber-400" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>`,
    warranty: 'Garantía Oficial StreamePro',
    highlights: ['Partidos en vivo de Liga MX y deportes premium', 'Novelas clásicas y estrenos exclusivos sin cortes', 'Perfil individual con PIN privado']
  },
  'DramaBox': {
    badge: 'VIP SHORT DRAMA • DESBLOQUEADO',
    deliveryBadge: '🟢 10-15m',
    badgeClass: 'bg-fuchsia-500/15 text-fuchsia-300 border-fuchsia-500/30',
    accentGlow: 'hover:shadow-[0_0_30px_rgba(217,70,239,0.22)]',
    borderClass: 'border-white/[0.08] hover:border-fuchsia-500/50',
    gradientBg: 'from-fuchsia-950/25 via-slate-900/90 to-slate-950',
    accentColor: 'text-fuchsia-400',
    iconSvg: `<svg class="w-5 h-5 text-fuchsia-400" viewBox="0 0 24 24" fill="currentColor"><path d="M4 4h16v16H4V4zm2 2v12h12V6H6zm4 2l6 4-6 4V8z"/></svg>`,
    warranty: 'Garantía Oficial StreamePro',
    highlights: ['Miniseries cortas y dramas virales completos', 'Episodios desbloqueados sin límite de monedas', 'Acceso VIP garantizado y continuo']
  },
  'Pornhub': {
    badge: 'VIP PREMIUM • 4K ULTRA HD',
    deliveryBadge: '🟢 10-15m',
    badgeClass: 'bg-yellow-500/15 text-yellow-300 border-yellow-500/30',
    accentGlow: 'hover:shadow-[0_0_30px_rgba(234,179,8,0.22)]',
    borderClass: 'border-white/[0.08] hover:border-yellow-500/50',
    gradientBg: 'from-yellow-950/25 via-slate-900/90 to-slate-950',
    accentColor: 'text-yellow-400',
    iconSvg: `<svg class="w-5 h-5 text-yellow-400" viewBox="0 0 24 24" fill="currentColor"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>`,
    warranty: 'Garantía Oficial StreamePro',
    highlights: ['Videos Premium en 1080p y 4K sin publicidad', 'Descargas offline y canales exclusivos', 'Discreción y privacidad absoluta 100% garantizada']
  },
  'Disney+ (Copia)': {
    badge: 'Standar con anuncio',
    deliveryBadge: '🟢 10-15m',
    badgeClass: 'bg-sky-500/15 text-sky-300 border-sky-500/30',
    accentGlow: 'hover:shadow-[0_0_30px_rgba(0,168,255,0.22)]',
    borderClass: 'border-white/[0.08] hover:border-sky-500/50',
    gradientBg: 'from-blue-950/30 via-slate-900/90 to-slate-950',
    accentColor: 'text-sky-300',
    iconSvg: `<svg class="w-5 h-5 text-sky-400" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.4 7.4h7.6l-6.2 4.5 2.4 7.4-6.2-4.5-6.2 4.5 2.4-7.4-6.2-4.5h7.6z"/></svg>`,
    warranty: 'Garantía oficial y reposición 100% activa durante los 30 días.',
    highlights: ['Disney + Pixar + Marvel', 'Perfil individual con PIN', 'Garantía 30 días StreamePro']
  },
  'FreeReels': {
    badge: 'VIP DRAMA • EPISODIOS LIBRES',
    deliveryBadge: '🟢 10-15m',
    badgeClass: 'bg-violet-500/15 text-violet-300 border-violet-500/30',
    accentGlow: 'hover:shadow-[0_0_30px_rgba(139,92,246,0.22)]',
    borderClass: 'border-white/[0.08] hover:border-violet-500/50',
    gradientBg: 'from-violet-950/25 via-slate-900/90 to-slate-950',
    accentColor: 'text-violet-400',
    iconSvg: `<svg class="w-5 h-5 text-violet-400" viewBox="0 0 24 24" fill="currentColor"><path d="M4 6.5C4 5.12 5.12 4 6.5 4h11C18.88 4 20 5.12 20 6.5v11c0 1.38-1.12 2.5-2.5 2.5h-11C5.12 20 4 18.88 4 17.5v-11zm6 3v5l4.5-2.5L10 9.5z"/></svg>`,
    warranty: 'Garantía Oficial StreamePro',
    highlights: ['Dramas cortos asiáticos y occidentales', 'Episodios premium liberados sin monedas', 'Pase VIP sin anuncios ni interrupciones']
  },
  'NetShort': {
    badge: 'VIP MINI SERIES • HD',
    deliveryBadge: '🟢 10-15m',
    badgeClass: 'bg-pink-600/15 text-pink-300 border-pink-500/30',
    accentGlow: 'hover:shadow-[0_0_30px_rgba(219,39,119,0.22)]',
    borderClass: 'border-white/[0.08] hover:border-pink-500/50',
    gradientBg: 'from-pink-950/25 via-slate-900/90 to-slate-950',
    accentColor: 'text-pink-400',
    iconSvg: `<svg class="w-5 h-5 text-pink-400" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/></svg>`,
    warranty: 'Garantía Oficial StreamePro',
    highlights: ['Historias cortas virales de amor y misterio', 'Acceso inmediato a temporadas completas', 'Calidad HD sin cortes publicitarios']
  },
  'ReelShort': {
    badge: 'VIP PASE COMPLETO',
    deliveryBadge: '🟢 10-15m',
    badgeClass: 'bg-indigo-600/15 text-indigo-300 border-indigo-500/30',
    accentGlow: 'hover:shadow-[0_0_30px_rgba(79,70,229,0.22)]',
    borderClass: 'border-white/[0.08] hover:border-indigo-500/50',
    gradientBg: 'from-indigo-950/25 via-slate-900/90 to-slate-950',
    accentColor: 'text-indigo-400',
    iconSvg: `<svg class="w-5 h-5 text-indigo-400" viewBox="0 0 24 24" fill="currentColor"><rect x="2" y="4" width="20" height="16" rx="2"/><line x1="6" y1="4" x2="6" y2="20"/><line x1="18" y1="4" x2="18" y2="20"/></svg>`,
    warranty: 'Garantía Oficial StreamePro',
    highlights: ['Sensación mundial de novelas cortas', 'Acceso completo a todos los capítulos', 'Visualización fluida en cualquier dispositivo']
  }
}

export const getStreamingBrandMeta = (platform) => {
  if (!platform) return null
  return STREAMING_BRAND_METADATA[platform] || {
    badge: '4K ULTRA HD • HDR',
    deliveryBadge: '🟢 10-15m',
    badgeClass: 'bg-amber-500/15 text-amber-300 border-amber-500/30',
    accentGlow: 'hover:shadow-[0_0_30px_rgba(245,158,11,0.22)]',
    borderClass: 'border-white/[0.08] hover:border-amber-500/50',
    gradientBg: 'from-slate-950 via-slate-900 to-slate-950',
    accentColor: 'text-amber-400',
    iconSvg: `<svg class="w-5 h-5 text-amber-400" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.4 7.4h7.6l-6.2 4.5 2.4 7.4-6.2-4.5-6.2 4.5 2.4-7.4-6.2-4.5h7.6z"/></svg>`,
    warranty: 'Garantía Oficial StreamePro',
    highlights: ['Entrega rápida garantizada', 'PIN privado de 4 dígitos', 'Garantía oficial y soporte directo']
  }
}

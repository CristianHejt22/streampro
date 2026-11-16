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

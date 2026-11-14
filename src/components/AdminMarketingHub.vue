<script setup>
import { ref, computed, defineAsyncComponent } from 'vue'
import { useAdminStore } from '../store/admin'
import { useToastStore } from '../store/toast'
import {
  buildDefaultGiveawayFormalities,
  buildDiscountProgramFormalities,
  generateMarketingCopy,
  maskPhonePublic
} from '../utils/marketing'

const emit = defineEmits(['openPlaque'])
const adminStore = useAdminStore()
const toast = useToastStore()

const subTab = ref('discount_users') // 'campaign' | 'discount_users' | 'giveaways' | 'coupons' | 'analytics' | 'reels_studio'
const ReelsStoryStudioModal = defineAsyncComponent(() => import('./ReelsStoryStudioModal.vue'))
const showAdminReelsModal = ref(false)
const adminReelsPayload = ref(null)

const openAdminReelsStudio = (payload = null) => {
  adminReelsPayload.value = payload
  showAdminReelsModal.value = true
}

// ==========================================
// 1. CAMPAÑA GLOBAL & REDES SOCIALES
// ==========================================
const campaignForm = ref({
  bannerEnabled: adminStore.marketingCampaign?.bannerEnabled !== false,
  bannerBadge: adminStore.marketingCampaign?.bannerBadge || 'PROMOS & SORTEOS',
  bannerText: adminStore.marketingCampaign?.bannerText || 'Participa en sorteos oficiales o comparte códigos con porcentajes ajustados',
  bannerCtaLabel: adminStore.marketingCampaign?.bannerCtaLabel || 'Ver Sorteos',
  bannerCtaTarget: adminStore.marketingCampaign?.bannerCtaTarget || 'giveaways',
  highlightedCode: adminStore.marketingCampaign?.highlightedCode || 'BIENVENIDO10',
  maxCombinedDiscountPct: Number(adminStore.marketingCampaign?.maxCombinedDiscountPct ?? 35)
})

const saveCampaignBanner = () => {
  adminStore.saveMarketingCampaign({ ...campaignForm.value })
  toast.add('📣 Campaña y reglas de tope guardadas y sincronizadas', 'success')
}

const socialBuilder = ref({
  channel: 'whatsapp', // 'whatsapp' | 'instagram' | 'tiktok' | 'telegram'
  targetType: 'games', // 'games' | 'combos' | 'giveaway' | 'store'
  promoSource: 'NONE', // 'NONE' | 'DUSER:ID' | 'COUPON:CODE'
  resellerId: 'DIRECT',
  headline: 'Ofertas Oficiales StreamePro PS5 / PS4 & Streaming',
  subline: 'Entrega garantizada 60/90 min con garantía oficial en tu consola y TV.'
})

const socialGeneratedText = computed(() => {
  const b = socialBuilder.value
  const origin = 'https://streamepro.me'
  let baseUrl = origin
  let giveawayTitle = ''
  if (b.targetType === 'games') baseUrl = `${origin}/juegos`
  else if (b.targetType === 'combos') baseUrl = `${origin}/combos`
  else if (b.targetType === 'giveaway') {
    const g = (adminStore.activeGiveaways || [])[0] || (adminStore.giveaways || [])[0]
    if (g) {
      baseUrl = `${origin}/sorteos?sorteo=${encodeURIComponent(g.id)}`
      giveawayTitle = `${g.title} — Premio: ${g.prizeTitle}`
    } else {
      baseUrl = `${origin}/sorteos`
    }
  }

  const qs = new URLSearchParams()
  if (b.resellerId && b.resellerId !== 'DIRECT') qs.set('ref', b.resellerId)

  let promoCode = ''
  let promoSummary = ''
  if (b.promoSource.startsWith('DUSER:')) {
    const id = b.promoSource.slice(6)
    const du = (adminStore.discountUsers || []).find(u => u.id === id)
    if (du) {
      promoCode = du.code
      promoSummary = `Streaming ${du.discountByCategory?.streaming ?? 0}% · Juegos ${du.discountByCategory?.gaming ?? 0}% · Combos ${du.discountByCategory?.packages ?? 0}% OFF`
      qs.set('promo', du.code)
    }
  } else if (b.promoSource.startsWith('COUPON:')) {
    const code = b.promoSource.slice(7)
    const c = (adminStore.promoCodes || []).find(x => x.code === code)
    if (c) {
      promoCode = c.code
      promoSummary = c.type === 'fixed' ? `$${Number(c.value).toLocaleString()} OFF` : `${c.value}% OFF`
      qs.set('promo', c.code)
    }
  }

  const finalUrl = qs.toString() ? `${baseUrl}${baseUrl.includes('?') ? '&' : '?'}${qs.toString()}` : baseUrl
  return generateMarketingCopy({
    channel: b.channel,
    headline: b.headline,
    subline: b.subline,
    promoCode,
    promoSummary,
    giveawayTitle,
    url: finalUrl
  })
})

const copySocialCopy = async () => {
  try {
    await navigator.clipboard.writeText(socialGeneratedText.value)
    toast.add('📋 Texto optimizado copiado al portapapeles', 'success')
  } catch {
    toast.add('No se pudo copiar al portapapeles', 'error')
  }
}

const triggerCampaignPlaque = () => {
  const games = (adminStore.processedGamingProducts || []).slice(0, 4)
  let promoCode = ''
  let promoLabel = ''
  if (socialBuilder.value.promoSource.startsWith('DUSER:')) {
    const du = (adminStore.discountUsers || []).find(u => u.id === socialBuilder.value.promoSource.slice(6))
    if (du) {
      promoCode = du.code
      promoLabel = `Hasta ${Math.max(du.discountByCategory?.streaming || 0, du.discountByCategory?.gaming || 0)}% OFF`
    }
  } else if (socialBuilder.value.promoSource.startsWith('COUPON:')) {
    const c = (adminStore.promoCodes || []).find(x => x.code === socialBuilder.value.promoSource.slice(7))
    if (c) {
      promoCode = c.code
      promoLabel = c.type === 'fixed' ? `$${Number(c.value).toLocaleString()} OFF` : `${c.value}% OFF`
    }
  }
  emit('openPlaque', {
    mode: games.length > 1 ? 'multi' : 'single',
    productType: 'gaming',
    title: socialBuilder.value.headline.slice(0, 44) || 'Top Ofertas StreamePro',
    subtitle: socialBuilder.value.subline.slice(0, 64),
    promoCode,
    promoLabel,
    items: games.map(g => ({
      id: g.id,
      itemType: 'gaming',
      title: g.title,
      platform: g.platform,
      tag: g.platform || 'PS5',
      priceNumeric: Number(g.effectivePrimaryPrice || 0),
      image: g.image || ''
    }))
  })
}

// ==========================================
// 2. USUARIOS DESCUENTO (PORCENTAJES AJUSTADOS)
// ==========================================
const defaultDiscountForm = () => ({
  id: null,
  code: '',
  name: '',
  phone: '',
  email: '',
  pin: String(Math.floor(1000 + Math.random() * 9000)),
  tierLabel: 'Embajador Bronce',
  status: 'active',
  discountByCategory: { all: 8, streaming: 12, gaming: 5, packages: 10, physical: 8 },
  rewardMode: 'credit',
  rewardByCategory: { all: 5, streaming: 7, gaming: 3, packages: 5, physical: 4 },
  minOrderAmount: 0,
  maxDiscountAmount: 15000,
  maxTotalUses: 100,
  maxUsesPerClient: 3,
  allowSelfUse: false,
  expiresAt: '',
  notes: ''
})

const discountForm = ref(defaultDiscountForm())
const selectedDirectoryUserId = ref('')
const redeemModalUser = ref(null)
const redeemForm = ref({ amount: 0, mode: 'voucher', note: '' })

const applyPresetToForm = (preset) => {
  if (preset === 'gamer') {
    discountForm.value.tierLabel = 'Club Gamer'
    discountForm.value.discountByCategory = { all: 6, streaming: 10, gaming: 6, packages: 8, physical: 5 }
    discountForm.value.rewardByCategory = { all: 4, streaming: 6, gaming: 4, packages: 5, physical: 3 }
  } else if (preset === 'streamer') {
    discountForm.value.tierLabel = 'Streamer / Influencer'
    discountForm.value.discountByCategory = { all: 10, streaming: 15, gaming: 7, packages: 12, physical: 8 }
    discountForm.value.rewardByCategory = { all: 6, streaming: 9, gaming: 4, packages: 7, physical: 5 }
  } else if (preset === 'vip') {
    discountForm.value.tierLabel = 'Cliente VIP'
    discountForm.value.discountByCategory = { all: 8, streaming: 12, gaming: 5, packages: 10, physical: 8 }
    discountForm.value.rewardByCategory = { all: 3, streaming: 5, gaming: 2, packages: 4, physical: 3 }
  }
}

const fillFromRegisteredUser = () => {
  const u = (adminStore.registeredUsers || []).find(x => (x.id || x.clientNumber) === selectedDirectoryUserId.value)
  if (!u) return
  discountForm.value.name = u.name || ''
  discountForm.value.phone = u.phone || ''
  discountForm.value.email = u.email || ''
  discountForm.value.pin = u.pin || discountForm.value.pin
  if (!discountForm.value.code && u.name) {
    const prefix = u.name.split(' ')[0].replace(/[^A-Za-z0-9]/g, '').toUpperCase().slice(0, 7)
    discountForm.value.code = `${prefix || 'VIP'}10`
  }
}

const editDiscountUser = (du) => {
  discountForm.value = JSON.parse(JSON.stringify({ ...defaultDiscountForm(), ...du }))
}

const resetDiscountForm = () => {
  selectedDirectoryUserId.value = ''
  discountForm.value = defaultDiscountForm()
}

const saveDiscountUser = () => {
  const res = adminStore.upsertDiscountUser(discountForm.value)
  if (res.error) {
    toast.add(res.error, 'error')
    return
  }
  toast.add(`🏷️ Usuario Descuento ${res.discountUser.code} guardado y sincronizado`, 'success')
  resetDiscountForm()
}

const sendDiscountUserWhatsApp = (du) => {
  const d = du.discountByCategory || {}
  const r = du.rewardByCategory || {}
  const link = `https://streamepro.me/?promo=${encodeURIComponent(du.code)}`
  const msg = `🎉 *¡YA ERES USUARIO DESCUENTO STREAMEPRO!* 🏷️\n\n` +
    `👤 *Titular:* ${du.name} (${du.tierLabel})\n` +
    `🎟️ *Tu Código Exclusivo:* *${du.code}*\n` +
    `🔐 *PIN Bóveda:* ${du.pin}\n\n` +
    `📉 *Tus Porcentajes Ajustados para Clientes:*\n` +
    `• Streaming 4K: *${d.streaming ?? d.all}% OFF*\n` +
    `• Juegos PS4/PS5: *${d.gaming ?? d.all}% OFF*\n` +
    `• Combos Oficiales: *${d.packages ?? d.all}% OFF*\n` +
    `• Tienda Física: *${d.physical ?? d.all}% OFF*\n` +
    (du.rewardMode !== 'none' ? `\n💰 *Tu Beneficio/Crédito por Venta Confirmada:* Hasta *${Math.max(r.streaming || 0, r.gaming || 0, r.all || 0)}%*\n` : '') +
    `\n🔗 *Link directo para compartir (aplica el cupón solo):*\n${link}\n\n` +
    `📊 Revisa tus ventas y créditos en: https://streamepro.me/portal`
  const digits = String(du.phone || '').replace(/[^0-9]/g, '')
  const url = digits ? `https://wa.me/${digits}?text=${encodeURIComponent(msg)}` : `https://wa.me/?text=${encodeURIComponent(msg)}`
  window.open(url, '_blank')
}

const approveDiscountApp = (app) => {
  const res = adminStore.reviewDiscountUserApplication(app.id, 'approved', {
    discountByCategory: discountForm.value.discountByCategory,
    rewardByCategory: discountForm.value.rewardByCategory,
    maxDiscountAmount: discountForm.value.maxDiscountAmount,
    maxUsesPerClient: discountForm.value.maxUsesPerClient
  })
  if (res.error) {
    toast.add(res.error, 'error')
    return
  }
  toast.add(`✅ Postulación aprobada. Código asignado: ${res.discountUser.code}`, 'success')
}

const openRedeemModal = (du) => {
  redeemModalUser.value = du
  redeemForm.value = { amount: Number(du.creditBalance || 0), mode: 'voucher', note: '' }
}

const confirmRedeemCredit = () => {
  if (!redeemModalUser.value) return
  const res = adminStore.redeemDiscountUserCredit(redeemModalUser.value.id, redeemForm.value.amount, redeemForm.value.mode, redeemForm.value.note)
  if (res.error) {
    toast.add(res.error, 'error')
    return
  }
  toast.add(
    res.voucherCode
      ? `✅ Crédito canjeado. Vale personal creado: ${res.voucherCode}`
      : '✅ Crédito liquidado correctamente',
    'success'
  )
  redeemModalUser.value = null
}

// ==========================================
// 3. GESTOR DE SORTEOS OFICIALES
// ==========================================
const defaultGiveawayForm = () => ({
  id: null,
  title: '',
  subtitle: 'Participá gratis y sumá chances extra por compras o invitando amigos.',
  prizeTitle: '',
  prizeValue: 50000,
  prizeCategory: 'gaming',
  prizeImage: '',
  status: 'active',
  startDate: new Date().toISOString().slice(0, 10),
  endDate: new Date(Date.now() + 14 * 86400000).toISOString().slice(0, 10),
  drawDateLabel: 'Al cierre (21:00 hs ARG)',
  winnersCount: 1,
  alternatesCount: 1,
  rules: {
    freeEntryEnabled: true,
    freeEntryTickets: 1,
    ticketsPerPurchase: 3,
    bonusThresholdAmount: 25000,
    bonusThresholdTickets: 2,
    ticketsPerReferral: 2,
    maxTicketsPerUser: 25,
    requireInstagram: false
  },
  formalitiesText: ''
})

const giveawayForm = ref(defaultGiveawayForm())
const inspectingGiveawayId = ref(null)

const regenerateFormalities = () => {
  giveawayForm.value.formalitiesText = buildDefaultGiveawayFormalities(giveawayForm.value)
  toast.add('📜 Formalidades y Bases actualizadas según tus reglas', 'info')
}

const pickGameAsPrize = (gameId) => {
  const g = (adminStore.processedGamingProducts || []).find(x => x.id === gameId)
  if (!g) return
  giveawayForm.value.prizeTitle = `Juego Digital ${g.title} (${g.platform || 'PS4 / PS5'} Cuenta Primaria)`
  giveawayForm.value.prizeValue = Number(g.effectivePrimaryPrice || 45000)
  giveawayForm.value.prizeImage = g.image || ''
}

const restoreProductionGiveaways = () => {
  if (!confirm('¿Restablecer a los Sorteos Oficiales de Producción de StreamePro?\nEsta acción purga sorteos de prueba y deja exclusivamente los 3 sorteos oficiales (Trío VIP Anual, Juego PS5 y Saldo $50.000).')) return
  const res = adminStore.resetToProductionGiveaways()
  if (res.success) {
    giveawayForm.value = defaultGiveawayForm()
    toast.add(`✅ Se restablecieron ${res.count} sorteos oficiales de producción`, 'success')
  }
}

const saveGiveaway = () => {
  const res = adminStore.upsertGiveaway(giveawayForm.value)
  if (res.error) {
    toast.add(res.error, 'error')
    return
  }
  toast.add(`🎉 Sorteo "${res.giveaway.title}" guardado y publicado`, 'success')
  giveawayForm.value = defaultGiveawayForm()
}

const editGiveaway = (g) => {
  giveawayForm.value = JSON.parse(JSON.stringify({ ...defaultGiveawayForm(), ...g }))
}

const executeDraw = (g) => {
  const sum = adminStore.getGiveawayTicketSummary(g.id)
  if (!confirm(`¿Ejecutar el sorteo transparente de "${g.title}"?\nParticipantes: ${sum.uniqueParticipants} | Tickets válidos: ${sum.totalTickets}`)) return
  const res = adminStore.runGiveawayDraw(g.id)
  if (res.error) {
    toast.add(res.error, 'error')
    return
  }
  toast.add(`🏆 ¡Sorteo realizado! Acta ${res.giveaway.drawProof?.verificationCode}`, 'success')
}

const notifyWinnerWhatsApp = (g, w) => {
  const msg = `🏆 *¡FELICITACIONES ${w.participantName.toUpperCase()}!* 🎉\n\n` +
    `Resultaste *${w.roleLabel}* en el sorteo oficial *${g.title}* de *StreamePro Oficial*.\n` +
    `🎫 *Ticket Ganador:* #${String(w.ticketNumber).padStart(4, '0')}\n` +
    `📜 *Acta Verificada:* ${g.drawProof?.verificationCode}\n` +
    `🎁 *Premio:* ${g.prizeTitle}\n\n` +
    `Respondé este mensaje dentro de las próximas 72 hs hábiles para coordinar la entrega oficial.`
  const digits = String(w.phone || '').replace(/[^0-9]/g, '')
  window.open(`https://wa.me/${digits}?text=${encodeURIComponent(msg)}`, '_blank')
}

const inspectedSummary = computed(() => {
  if (!inspectingGiveawayId.value) return null
  return adminStore.getGiveawayTicketSummary(inspectingGiveawayId.value)
})

// ==========================================
// 4. CUPONES GLOBALES MEJORADOS
// ==========================================
const globalCouponForm = ref({
  code: '',
  type: 'percent',
  value: 10,
  scope: 'all',
  minAmount: 0,
  maxDiscountAmount: 0,
  maxUses: 100,
  expiresAt: '',
  description: ''
})

const createGlobalCoupon = () => {
  const res = adminStore.addPromoCode({ ...globalCouponForm.value })
  if (res.error) {
    toast.add(res.error, 'error')
    return
  }
  toast.add(`🎟️ Cupón ${res.coupon.code} creado`, 'success')
  globalCouponForm.value.code = ''
  globalCouponForm.value.description = ''
}

const programFormalitiesList = computed(() => buildDiscountProgramFormalities(adminStore.marketingCampaign))
</script>

<template>
  <div class="space-y-6">
    <!-- HEADER + KPI RESUMEN -->
    <div class="bg-slate-900/50 border border-fuchsia-500/30 rounded-3xl p-5 sm:p-6 space-y-4">
      <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
          <span class="text-[10px] font-black uppercase tracking-wider bg-fuchsia-500/20 text-fuchsia-300 border border-fuchsia-500/30 px-2.5 py-0.5 rounded-full">
            Growth &amp; Promociones
          </span>
          <h2 class="text-2xl font-black text-white mt-1">📣 Hub de Marketing, Sorteos &amp; Usuarios Descuento</h2>
          <p class="text-xs text-slate-400">
            Administra porcentajes ajustados por rubro, dinámicas de sorteos con actas transparentes, cupones globales y campañas multicanal.
          </p>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-center text-xs shrink-0">
          <div class="bg-slate-950/90 border border-slate-800 rounded-xl px-3 py-2">
            <span class="text-[9px] text-slate-400 uppercase block font-bold">Embajadores</span>
            <strong class="text-base font-black text-amber-300 font-mono">{{ adminStore.marketingMetrics.activeDiscountUsersCount }}</strong>
          </div>
          <div class="bg-slate-950/90 border border-slate-800 rounded-xl px-3 py-2">
            <span class="text-[9px] text-slate-400 uppercase block font-bold">Sorteos Activos</span>
            <strong class="text-base font-black text-fuchsia-300 font-mono">{{ adminStore.marketingMetrics.activeGiveawaysCount }}</strong>
          </div>
          <div class="bg-slate-950/90 border border-slate-800 rounded-xl px-3 py-2">
            <span class="text-[9px] text-slate-400 uppercase block font-bold">Tickets Sorteos</span>
            <strong class="text-base font-black text-sky-300 font-mono">{{ adminStore.marketingMetrics.totalGiveawayTickets }}</strong>
          </div>
          <div class="bg-slate-950/90 border border-slate-800 rounded-xl px-3 py-2">
            <span class="text-[9px] text-slate-400 uppercase block font-bold">Ventas Promo</span>
            <strong class="text-base font-black text-emerald-300 font-mono">${{ adminStore.marketingMetrics.totalPromoSales.toLocaleString() }}</strong>
          </div>
        </div>
      </div>

      <!-- SUB-NAVEGACIÓN -->
      <div class="flex flex-wrap gap-2 pt-2 border-t border-slate-800/80">
        <button
          type="button"
          @click="subTab = 'discount_users'"
          :class="['px-4 py-2 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-1.5', subTab === 'discount_users' ? 'bg-amber-400 text-slate-950 shadow' : 'bg-slate-950 text-slate-300 border border-slate-800 hover:bg-slate-800']"
        >
          <span>🏷️ Usuarios Descuento (% Ajustados)</span>
          <span v-if="adminStore.pendingDiscountApplications.length > 0" class="bg-rose-600 text-white text-[10px] px-1.5 rounded-full animate-pulse">
            {{ adminStore.pendingDiscountApplications.length }}
          </span>
        </button>
        <button
          type="button"
          @click="subTab = 'giveaways'"
          :class="['px-4 py-2 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-1.5', subTab === 'giveaways' ? 'bg-fuchsia-500 text-white shadow' : 'bg-slate-950 text-slate-300 border border-slate-800 hover:bg-slate-800']"
        >
          <span>🎉 Sorteos &amp; Actas ({{ (adminStore.giveaways || []).length }})</span>
        </button>
        <button
          type="button"
          @click="subTab = 'campaign'"
          :class="['px-4 py-2 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-1.5', subTab === 'campaign' ? 'bg-sky-400 text-slate-950 shadow' : 'bg-slate-950 text-slate-300 border border-slate-800 hover:bg-slate-800']"
        >
          <span>📢 Banner Tienda &amp; Copys Redes</span>
        </button>
        <button
          type="button"
          @click="subTab = 'coupons'"
          :class="['px-4 py-2 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-1.5', subTab === 'coupons' ? 'bg-indigo-500 text-white shadow' : 'bg-slate-950 text-slate-300 border border-slate-800 hover:bg-slate-800']"
        >
          <span>🎟️ Cupones Globales ({{ (adminStore.promoCodes || []).length }})</span>
        </button>
        <button
          type="button"
          @click="subTab = 'analytics'"
          :class="['px-4 py-2 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-1.5', subTab === 'analytics' ? 'bg-emerald-400 text-slate-950 shadow' : 'bg-slate-950 text-slate-300 border border-slate-800 hover:bg-slate-800']"
        >
          <span>📊 Métricas &amp; Créditos ({{ (adminStore.discountCreditsLog || []).length }})</span>
        </button>
        <button
          type="button"
          @click="subTab = 'reels_studio'"
          :class="['px-4 py-2 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-1.5', subTab === 'reels_studio' ? 'bg-gradient-to-r from-pink-500 to-purple-600 text-white shadow' : 'bg-slate-950 text-slate-300 border border-slate-800 hover:bg-slate-800']"
        >
          <span>🎬 Estudio Reels &amp; Estados 9:16</span>
        </button>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- SUB-TAB 1: USUARIOS DESCUENTO CON PORCENTAJES AJUSTADOS        -->
    <!-- ============================================================== -->
    <div v-if="subTab === 'discount_users'" class="space-y-6">
      <!-- Postulaciones Pendientes -->
      <div v-if="adminStore.pendingDiscountApplications.length > 0" class="bg-amber-950/20 border border-amber-500/40 rounded-3xl p-5 space-y-3">
        <h3 class="text-sm font-black text-amber-300">📩 Postulaciones Pendientes de Usuarios Descuento ({{ adminStore.pendingDiscountApplications.length }})</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div v-for="app in adminStore.pendingDiscountApplications" :key="app.id" class="bg-slate-950/90 border border-slate-800 rounded-2xl p-4 space-y-2 text-xs">
            <div class="flex justify-between items-start">
              <div>
                <strong class="text-white text-sm">{{ app.name }}</strong>
                <span class="block text-sky-400 font-mono text-[11px]">{{ app.phone }} · {{ app.channel }}</span>
              </div>
              <span class="font-mono font-black text-amber-300 bg-amber-500/15 px-2 py-0.5 rounded border border-amber-500/30">
                Desea: {{ app.preferredCode }}
              </span>
            </div>
            <p v-if="app.message" class="text-slate-400 text-[11px] bg-slate-900 p-2 rounded-lg">{{ app.message }}</p>
            <div class="flex gap-2 pt-1">
              <button type="button" @click="approveDiscountApp(app)" class="flex-1 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-black cursor-pointer">
                ✅ Aprobar (con % del formulario)
              </button>
              <button type="button" @click="adminStore.reviewDiscountUserApplication(app.id, 'rejected')" class="px-3 py-2 rounded-xl bg-rose-600/20 hover:bg-rose-600 text-rose-300 hover:text-white font-bold cursor-pointer">
                Rechazar
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
        <!-- Formulario Crear / Ajustar Porcentajes -->
        <div class="xl:col-span-5 bg-[#020617] border border-amber-500/30 rounded-3xl p-5 space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="text-base font-black text-white">
              {{ discountForm.id ? `✏️ Editando ${discountForm.code}` : '➕ Alta Usuario Descuento' }}
            </h3>
            <button v-if="discountForm.id" type="button" @click="resetDiscountForm" class="text-xs text-slate-400 hover:text-white cursor-pointer">Cancelar edición</button>
          </div>

          <!-- Presets & Vinculación Rápida -->
          <div class="space-y-2 text-xs">
            <select v-model="selectedDirectoryUserId" @change="fillFromRegisteredUser" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2 text-sky-300 outline-none">
              <option value="">-- Vincular cliente existente del Directorio --</option>
              <option v-for="u in adminStore.registeredUsers" :key="u.id || u.clientNumber" :value="u.id || u.clientNumber">
                {{ u.name }} ({{ u.phone }})
              </option>
            </select>
            <div class="flex flex-wrap gap-1.5">
              <button type="button" @click="applyPresetToForm('vip')" class="px-2.5 py-1 rounded-lg bg-slate-900 hover:bg-slate-800 border border-slate-700 text-[11px] font-bold text-slate-300 cursor-pointer">Preset VIP (12%/5%)</button>
              <button type="button" @click="applyPresetToForm('gamer')" class="px-2.5 py-1 rounded-lg bg-slate-900 hover:bg-slate-800 border border-slate-700 text-[11px] font-bold text-indigo-300 cursor-pointer">Preset Club Gamer</button>
              <button type="button" @click="applyPresetToForm('streamer')" class="px-2.5 py-1 rounded-lg bg-slate-900 hover:bg-slate-800 border border-slate-700 text-[11px] font-bold text-pink-300 cursor-pointer">Preset Influencer</button>
            </div>
          </div>

          <form @submit.prevent="saveDiscountUser" class="space-y-3 text-xs">
            <div class="grid grid-cols-2 gap-2">
              <input v-model="discountForm.code" type="text" required placeholder="CÓDIGO (Ej: SOFIA10)" class="bg-slate-900 border border-slate-700 rounded-xl p-2 text-amber-300 font-mono uppercase font-black outline-none">
              <input v-model="discountForm.tierLabel" type="text" placeholder="Nivel (Ej: Embajador)" class="bg-slate-900 border border-slate-700 rounded-xl p-2 text-white outline-none">
            </div>
            <div class="grid grid-cols-2 gap-2">
              <input v-model="discountForm.name" type="text" required placeholder="Nombre Completo" class="bg-slate-900 border border-slate-700 rounded-xl p-2 text-white outline-none">
              <input v-model="discountForm.phone" type="text" required placeholder="WhatsApp" class="bg-slate-900 border border-slate-700 rounded-xl p-2 text-white font-mono outline-none">
            </div>

            <!-- Tabla Porcentajes Ajustados al Comprador -->
            <div class="bg-slate-950 border border-slate-800 rounded-2xl p-3 space-y-2">
              <strong class="text-emerald-300 block text-[11px] uppercase">📉 % Descuento para quien compra (por rubro):</strong>
              <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                <div>
                  <label class="text-[10px] text-slate-400 block">Streaming %</label>
                  <input v-model.number="discountForm.discountByCategory.streaming" type="number" min="0" max="50" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-1.5 text-white font-mono text-center">
                </div>
                <div>
                  <label class="text-[10px] text-slate-400 block">Juegos %</label>
                  <input v-model.number="discountForm.discountByCategory.gaming" type="number" min="0" max="35" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-1.5 text-white font-mono text-center">
                </div>
                <div>
                  <label class="text-[10px] text-slate-400 block">Combos %</label>
                  <input v-model.number="discountForm.discountByCategory.packages" type="number" min="0" max="50" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-1.5 text-white font-mono text-center">
                </div>
                <div>
                  <label class="text-[10px] text-slate-400 block">Físicos %</label>
                  <input v-model.number="discountForm.discountByCategory.physical" type="number" min="0" max="50" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-1.5 text-white font-mono text-center">
                </div>
              </div>
            </div>

            <!-- Tabla Porcentajes Ajustados Crédito al Promotor -->
            <div class="bg-slate-950 border border-slate-800 rounded-2xl p-3 space-y-2">
              <div class="flex items-center justify-between">
                <strong class="text-sky-300 text-[11px] uppercase">💰 % Recompensa / Crédito al Usuario:</strong>
                <select v-model="discountForm.rewardMode" class="bg-slate-900 border border-slate-700 rounded-lg px-2 py-0.5 text-[10px] text-white">
                  <option value="credit">Crédito Tienda</option>
                  <option value="cashback">Liquidación $</option>
                  <option value="none">Solo Descuento (0%)</option>
                </select>
              </div>
              <div v-if="discountForm.rewardMode !== 'none'" class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                <div>
                  <label class="text-[10px] text-slate-400 block">Streaming %</label>
                  <input v-model.number="discountForm.rewardByCategory.streaming" type="number" min="0" max="30" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-1.5 text-sky-300 font-mono text-center">
                </div>
                <div>
                  <label class="text-[10px] text-slate-400 block">Juegos %</label>
                  <input v-model.number="discountForm.rewardByCategory.gaming" type="number" min="0" max="20" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-1.5 text-sky-300 font-mono text-center">
                </div>
                <div>
                  <label class="text-[10px] text-slate-400 block">Combos %</label>
                  <input v-model.number="discountForm.rewardByCategory.packages" type="number" min="0" max="30" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-1.5 text-sky-300 font-mono text-center">
                </div>
                <div>
                  <label class="text-[10px] text-slate-400 block">Físicos %</label>
                  <input v-model.number="discountForm.rewardByCategory.physical" type="number" min="0" max="25" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-1.5 text-sky-300 font-mono text-center">
                </div>
              </div>
            </div>

            <!-- Límites y Formalidades -->
            <div class="grid grid-cols-3 gap-2">
              <div>
                <label class="text-[10px] text-slate-400 block">Tope Ahorro ($)</label>
                <input v-model.number="discountForm.maxDiscountAmount" type="number" min="0" step="500" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-1.5 text-white font-mono">
              </div>
              <div>
                <label class="text-[10px] text-slate-400 block">Mín. Compra ($)</label>
                <input v-model.number="discountForm.minOrderAmount" type="number" min="0" step="500" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-1.5 text-white font-mono">
              </div>
              <div>
                <label class="text-[10px] text-slate-400 block">Usos / Cliente</label>
                <input v-model.number="discountForm.maxUsesPerClient" type="number" min="0" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-1.5 text-white font-mono">
              </div>
            </div>

            <div class="grid grid-cols-2 gap-2 items-center">
              <div>
                <label class="text-[10px] text-slate-400 block">Vigencia Hasta (Opc.)</label>
                <input v-model="discountForm.expiresAt" type="date" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-1.5 text-white text-[11px]">
              </div>
              <label class="flex items-center gap-2 text-[11px] text-slate-300 pt-3 cursor-pointer">
                <input v-model="discountForm.allowSelfUse" type="checkbox" class="rounded accent-amber-400">
                <span>Permitir auto-uso</span>
              </label>
            </div>

            <button type="submit" class="w-full py-3 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-black shadow cursor-pointer">
              💾 Guardar Usuario Descuento
            </button>
          </form>
        </div>

        <!-- Lista de Usuarios Descuento Activos -->
        <div class="xl:col-span-7 bg-[#020617] border border-slate-800 rounded-3xl p-5 space-y-4">
          <h3 class="text-base font-black text-white">👥 Usuarios Descuento Registrados ({{ (adminStore.discountUsers || []).length }})</h3>
          <div v-if="!adminStore.discountUsers?.length" class="text-center py-10 text-slate-500 text-xs">
            Aún no hay Usuarios Descuento creados. Usa el formulario izquierdo para asignar códigos con porcentajes ajustados.
          </div>
          <div v-else class="space-y-3 max-h-[600px] overflow-y-auto custom-scrollbar pr-1">
            <div v-for="du in adminStore.discountUsers" :key="du.id" class="bg-slate-950/90 border border-slate-800 rounded-2xl p-4 space-y-3 text-xs">
              <div class="flex flex-wrap justify-between items-start gap-2">
                <div>
                  <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-mono font-black text-amber-300 text-sm bg-amber-500/15 border border-amber-500/30 px-2.5 py-0.5 rounded-lg">
                      {{ du.code }}
                    </span>
                    <strong class="text-white">{{ du.name }}</strong>
                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-800 text-slate-300">{{ du.tierLabel }}</span>
                    <span :class="['text-[10px] px-2 py-0.5 rounded-full font-bold', du.status === 'active' ? 'bg-emerald-500/20 text-emerald-300' : 'bg-rose-500/20 text-rose-300']">
                      {{ du.status === 'active' ? 'Activo' : 'Pausado' }}
                    </span>
                  </div>
                  <p class="text-[11px] text-slate-400 font-mono mt-1">📱 {{ du.phone }} · PIN Bóveda: {{ du.pin }} · Usos: {{ du.usesCount || 0 }}{{ du.maxTotalUses ? `/${du.maxTotalUses}` : '' }}</p>
                </div>

                <div class="text-right font-mono">
                  <span class="text-[10px] text-slate-400 block">Crédito Disp. / Pend.</span>
                  <strong class="text-emerald-300 text-sm">${{ Number(du.creditBalance || 0).toLocaleString() }}</strong>
                  <span v-if="du.pendingCredit > 0" class="text-[10px] text-amber-300 ml-1">(+${{ Number(du.pendingCredit).toLocaleString() }} pend.)</span>
                </div>
              </div>

              <div class="grid grid-cols-4 gap-1.5 text-center bg-slate-900/70 p-2 rounded-xl border border-slate-800 text-[10px]">
                <div><span class="text-slate-400 block">Streaming</span><strong class="text-white">{{ du.discountByCategory?.streaming }}% / {{ du.rewardByCategory?.streaming }}%</strong></div>
                <div><span class="text-slate-400 block">Juegos</span><strong class="text-white">{{ du.discountByCategory?.gaming }}% / {{ du.rewardByCategory?.gaming }}%</strong></div>
                <div><span class="text-slate-400 block">Combos</span><strong class="text-white">{{ du.discountByCategory?.packages }}% / {{ du.rewardByCategory?.packages }}%</strong></div>
                <div><span class="text-slate-400 block">Físicos</span><strong class="text-white">{{ du.discountByCategory?.physical }}% / {{ du.rewardByCategory?.physical }}%</strong></div>
              </div>

              <div class="flex flex-wrap gap-1.5">
                <button type="button" @click="sendDiscountUserWhatsApp(du)" class="px-3 py-1.5 rounded-lg bg-emerald-600/20 hover:bg-emerald-600 text-emerald-300 hover:text-white font-bold cursor-pointer">💬 Enviar Link WA</button>
                <button type="button" @click="editDiscountUser(du)" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-white font-bold cursor-pointer">✏️ Ajustar %</button>
                <button v-if="du.creditBalance > 0" type="button" @click="openRedeemModal(du)" class="px-3 py-1.5 rounded-lg bg-sky-600/20 hover:bg-sky-600 text-sky-300 hover:text-white font-bold cursor-pointer">💳 Canjear / Liquidar</button>
                <button type="button" @click="adminStore.toggleDiscountUserStatus(du.id)" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-amber-300 font-bold cursor-pointer">{{ du.status === 'active' ? '⏸️ Pausar' : '▶️ Activar' }}</button>
                <button type="button" @click="adminStore.deleteDiscountUser(du.id)" class="px-3 py-1.5 rounded-lg bg-rose-500/15 hover:bg-rose-600 text-rose-300 hover:text-white font-bold cursor-pointer">🗑️</button>
              </div>
            </div>
          </div>

          <div class="p-3 rounded-2xl bg-slate-950 border border-slate-800 text-[11px] text-slate-400 space-y-1">
            <strong class="text-amber-300 block text-[10px] uppercase">Formalidades Activas del Programa:</strong>
            <p v-for="(r, ri) in programFormalitiesList" :key="ri">• {{ r }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- SUB-TAB 2: GESTOR DE SORTEOS & ACTAS                           -->
    <!-- ============================================================== -->
    <div v-else-if="subTab === 'giveaways'" class="grid grid-cols-1 xl:grid-cols-12 gap-6">
      <!-- Crear / Editar Sorteo -->
      <div class="xl:col-span-5 bg-[#020617] border border-fuchsia-500/30 rounded-3xl p-5 space-y-3 text-xs">
        <div class="flex flex-wrap justify-between items-center gap-2">
          <h3 class="text-base font-black text-white">{{ giveawayForm.id ? '✏️ Editando Sorteo' : '➕ Nuevo Sorteo Oficial' }}</h3>
          <div class="flex items-center gap-2">
            <button
              type="button"
              @click="restoreProductionGiveaways"
              class="px-2.5 py-1 rounded-xl bg-amber-500/15 hover:bg-amber-500/25 text-amber-300 border border-amber-500/30 font-bold text-[11px] transition cursor-pointer flex items-center gap-1"
              title="Restablece los 3 sorteos oficiales de producción y purga pruebas"
            >
              <span>🔄</span>
              <span>Reset Producción</span>
            </button>
            <button v-if="giveawayForm.id" type="button" @click="giveawayForm = defaultGiveawayForm()" class="text-slate-400 hover:text-white cursor-pointer text-xs">Cancelar</button>
          </div>
        </div>

        <form @submit.prevent="saveGiveaway" class="space-y-3">
          <input v-model="giveawayForm.title" type="text" required placeholder="Título del Sorteo (Ej: Mega Sorteo Gamer PS5)" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-white font-bold outline-none">
          <input v-model="giveawayForm.subtitle" type="text" placeholder="Bajada breve" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2 text-slate-300 outline-none">

          <select @change="pickGameAsPrize($event.target.value)" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2 text-indigo-300 outline-none">
            <option value="">-- Tomar premio desde catálogo de juegos (opcional) --</option>
            <option v-for="g in (adminStore.processedGamingProducts || []).slice(0, 60)" :key="g.id" :value="g.id">
              {{ g.title }} ({{ g.platform }}) — ${{ Number(g.effectivePrimaryPrice || 0).toLocaleString() }}
            </option>
          </select>

          <div class="grid grid-cols-3 gap-2">
            <input v-model="giveawayForm.prizeTitle" type="text" required placeholder="Premio Principal" class="col-span-2 bg-slate-900 border border-slate-700 rounded-xl p-2 text-white outline-none">
            <input v-model.number="giveawayForm.prizeValue" type="number" min="0" placeholder="Valor $" class="bg-slate-900 border border-slate-700 rounded-xl p-2 text-amber-300 font-mono outline-none">
          </div>
          <input v-model="giveawayForm.prizeImage" type="text" placeholder="URL Imagen del Premio (opcional)" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2 text-slate-300 font-mono outline-none">

          <div class="grid grid-cols-3 gap-2">
            <div>
              <label class="text-[10px] text-slate-400 block">Cierre Inscripción</label>
              <input v-model="giveawayForm.endDate" type="date" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-1.5 text-white">
            </div>
            <div>
              <label class="text-[10px] text-slate-400 block">Ganadores Titulares</label>
              <input v-model.number="giveawayForm.winnersCount" type="number" min="1" max="10" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-1.5 text-white text-center font-mono">
            </div>
            <div>
              <label class="text-[10px] text-slate-400 block">Suplentes</label>
              <input v-model.number="giveawayForm.alternatesCount" type="number" min="0" max="5" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-1.5 text-white text-center font-mono">
            </div>
          </div>

          <div class="bg-slate-950 border border-slate-800 rounded-2xl p-3 space-y-2">
            <strong class="text-fuchsia-300 text-[11px] uppercase block">⚡ Dinámica de Chances y Reglas Anti-Abuso:</strong>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
              <div>
                <label class="text-[10px] text-slate-400 block">Web Gratis</label>
                <input v-model.number="giveawayForm.rules.freeEntryTickets" type="number" min="1" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-1 text-white text-center font-mono">
              </div>
              <div>
                <label class="text-[10px] text-slate-400 block">Por Compra</label>
                <input v-model.number="giveawayForm.rules.ticketsPerPurchase" type="number" min="0" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-1 text-white text-center font-mono">
              </div>
              <div>
                <label class="text-[10px] text-slate-400 block">Por Referido</label>
                <input v-model.number="giveawayForm.rules.ticketsPerReferral" type="number" min="0" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-1 text-white text-center font-mono">
              </div>
              <div>
                <label class="text-[10px] text-slate-400 block">Tope / Persona</label>
                <input v-model.number="giveawayForm.rules.maxTicketsPerUser" type="number" min="1" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-1 text-white text-center font-mono">
              </div>
            </div>
            <div class="flex flex-wrap gap-4 pt-1">
              <label class="flex items-center gap-1.5 text-[11px] text-slate-300 cursor-pointer">
                <input v-model="giveawayForm.rules.freeEntryEnabled" type="checkbox" class="accent-fuchsia-500">
                <span>Permitir inscripción gratis</span>
              </label>
              <label class="flex items-center gap-1.5 text-[11px] text-slate-300 cursor-pointer">
                <input v-model="giveawayForm.rules.requireInstagram" type="checkbox" class="accent-fuchsia-500">
                <span>Pedir @Instagram</span>
              </label>
            </div>
          </div>

          <div class="space-y-1">
            <div class="flex justify-between items-center">
              <label class="text-[11px] font-bold text-slate-300">📜 Bases y Condiciones Formales</label>
              <button type="button" @click="regenerateFormalities" class="text-[11px] text-fuchsia-300 hover:underline cursor-pointer">Autogenerar texto formal</button>
            </div>
            <textarea v-model="giveawayForm.formalitiesText" rows="4" placeholder="Dejar vacío para autogenerar bases oficiales completas..." class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2 text-[11px] text-slate-300 outline-none"></textarea>
          </div>

          <button type="submit" class="w-full py-3 rounded-xl bg-gradient-to-r from-fuchsia-600 to-indigo-600 hover:from-fuchsia-500 hover:to-indigo-500 text-white font-black shadow cursor-pointer">
            🎉 Publicar / Guardar Sorteo
          </button>
        </form>
      </div>

      <!-- Lista de Sorteos, Participantes y Ejecución -->
      <div class="xl:col-span-7 bg-[#020617] border border-slate-800 rounded-3xl p-5 space-y-4 text-xs">
        <h3 class="text-base font-black text-white">🏆 Sorteos Activos y Finalizados ({{ (adminStore.giveaways || []).length }})</h3>
        <div v-if="!adminStore.giveaways?.length" class="text-center py-10 text-slate-500">
          Crea tu primer sorteo oficial con bases transparentes desde el formulario izquierdo.
        </div>
        <div v-else class="space-y-3">
          <div v-for="g in adminStore.giveaways" :key="g.id" class="bg-slate-950/90 border border-slate-800 rounded-2xl p-4 space-y-3">
            <div class="flex flex-wrap justify-between items-start gap-2">
              <div>
                <div class="flex items-center gap-2">
                  <span class="font-mono text-[10px] bg-fuchsia-500/20 text-fuchsia-300 px-2 py-0.5 rounded">{{ g.id }}</span>
                  <strong class="text-sm text-white">{{ g.title }}</strong>
                  <span :class="['text-[10px] px-2 py-0.5 rounded-full font-bold', g.status === 'active' ? 'bg-emerald-500/20 text-emerald-300' : g.status === 'drawn' ? 'bg-amber-500/20 text-amber-300' : 'bg-slate-800 text-slate-400']">
                    {{ g.status }}
                  </span>
                </div>
                <p class="text-slate-400 text-[11px] mt-0.5">🎁 {{ g.prizeTitle }} · Cierra: {{ g.endDate }}</p>
              </div>
              <div class="text-right font-mono">
                <span class="text-fuchsia-300 font-black">{{ adminStore.getGiveawayTicketSummary(g.id).totalTickets }} tickets</span>
                <span class="block text-[10px] text-slate-400">{{ adminStore.getGiveawayTicketSummary(g.id).uniqueParticipants }} participantes</span>
              </div>
            </div>

            <div v-if="g.winners?.length" class="bg-amber-500/10 border border-amber-400/30 rounded-xl p-3 space-y-1.5">
              <strong class="text-amber-300 block">Acta Verificada: {{ g.drawProof?.verificationCode }}</strong>
              <div v-for="(w, wi) in g.winners" :key="wi" class="flex items-center justify-between bg-slate-950 px-2.5 py-1.5 rounded-lg">
                <span><strong class="text-emerald-300">{{ w.roleLabel }}:</strong> {{ w.participantName }} ({{ w.phone }}) · #{{ String(w.ticketNumber).padStart(4, '0') }}</span>
                <button type="button" @click="notifyWinnerWhatsApp(g, w)" class="px-2 py-1 rounded bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[10px] cursor-pointer">💬 Notificar</button>
              </div>
            </div>

            <div class="flex flex-wrap gap-1.5">
              <button type="button" @click="inspectingGiveawayId = inspectingGiveawayId === g.id ? null : g.id" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-sky-300 font-bold cursor-pointer">
                👥 Ver Tickets
              </button>
              <button v-if="g.status !== 'drawn'" type="button" @click="executeDraw(g)" class="px-3 py-1.5 rounded-lg bg-amber-400 hover:bg-amber-300 text-slate-950 font-black cursor-pointer">
                🎲 Realizar Sorteo Ahora
              </button>
              <button type="button" @click="editGiveaway(g)" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-white font-bold cursor-pointer">✏️ Editar</button>
              <button type="button" @click="adminStore.setGiveawayStatus(g.id, g.status === 'active' ? 'closed' : 'active')" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-amber-300 font-bold cursor-pointer">
                {{ g.status === 'active' ? '⏸️ Cerrar' : '▶️ Reabrir' }}
              </button>
              <button type="button" @click="adminStore.deleteGiveaway(g.id)" class="px-3 py-1.5 rounded-lg bg-rose-500/15 hover:bg-rose-600 text-rose-300 hover:text-white font-bold cursor-pointer">🗑️</button>
            </div>

            <!-- Tabla Participantes del Sorteo Inspeccionado -->
            <div v-if="inspectingGiveawayId === g.id && inspectedSummary" class="bg-slate-900/90 border border-slate-700 rounded-xl p-3 space-y-1.5 max-h-56 overflow-y-auto custom-scrollbar">
              <div v-if="!inspectedSummary.entries.length" class="text-slate-400 text-center py-2">Sin inscripciones todavía.</div>
              <div v-for="ent in inspectedSummary.entries" :key="ent.id" class="flex items-center justify-between text-[11px] bg-slate-950 px-2.5 py-1.5 rounded-lg">
                <div>
                  <span class="font-mono text-fuchsia-300 font-bold mr-2">#{{ String(ent.startTicket).padStart(4, '0') }}<template v-if="ent.endTicket > ent.startTicket">-{{ String(ent.endTicket).padStart(4, '0') }}</template></span>
                  <strong class="text-white">{{ ent.participantName }}</strong>
                  <span class="text-slate-400 ml-1.5">{{ ent.phone }} ({{ ent.sourceType }})</span>
                </div>
                <button type="button" @click="adminStore.disqualifyGiveawayEntry(ent.id)" class="text-rose-400 hover:underline font-bold cursor-pointer">Anular</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- SUB-TAB 3: BANNER TIENDA & COPYS MULTICANAL + PLACAS           -->
    <!-- ============================================================== -->
    <div v-else-if="subTab === 'campaign'" class="grid grid-cols-1 lg:grid-cols-2 gap-6 text-xs">
      <!-- Banner Superior Storefront & Políticas -->
      <div class="bg-[#020617] border border-sky-500/30 rounded-3xl p-5 space-y-4">
        <h3 class="text-base font-black text-white">📢 Banner Activo en Portada &amp; Regla Anti-Acumulación</h3>
        <label class="flex items-center gap-2 text-slate-200 font-bold cursor-pointer">
          <input v-model="campaignForm.bannerEnabled" type="checkbox" class="accent-sky-400">
          <span>Mostrar barra promocional en tienda para todos los clientes</span>
        </label>
        <div class="grid grid-cols-2 gap-2">
          <div>
            <label class="text-[10px] text-slate-400 block">Etiqueta</label>
            <input v-model="campaignForm.bannerBadge" type="text" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2 text-amber-300 font-bold outline-none">
          </div>
          <div>
            <label class="text-[10px] text-slate-400 block">Tope Descuento Combinado (%)</label>
            <input v-model.number="campaignForm.maxCombinedDiscountPct" type="number" min="5" max="70" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2 text-white font-mono outline-none">
          </div>
        </div>
        <div>
          <label class="text-[10px] text-slate-400 block">Mensaje del Banner</label>
          <input v-model="campaignForm.bannerText" type="text" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2 text-white outline-none">
        </div>
        <div class="grid grid-cols-2 gap-2">
          <div>
            <label class="text-[10px] text-slate-400 block">Texto Botón CTA</label>
            <input v-model="campaignForm.bannerCtaLabel" type="text" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2 text-white outline-none">
          </div>
          <div>
            <label class="text-[10px] text-slate-400 block">Destino del Botón</label>
            <select v-model="campaignForm.bannerCtaTarget" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2 text-white outline-none">
              <option value="giveaways">Ir a Sorteos y Beneficios</option>
              <option value="gaming">Ir a Juegos Digitales</option>
              <option value="packages">Ir a Combos</option>
              <option value="apply_highlight">Aplicar Cupón Destacado</option>
            </select>
          </div>
        </div>
        <button type="button" @click="saveCampaignBanner" class="w-full py-2.5 rounded-xl bg-sky-400 hover:bg-sky-300 text-slate-950 font-black cursor-pointer">
          💾 Guardar y Sincronizar Banner en Tienda
        </button>
      </div>

      <!-- Generador Multi-Red & Placa con Cupón -->
      <div class="bg-[#020617] border border-pink-500/30 rounded-3xl p-5 space-y-3">
        <div class="flex justify-between items-center">
          <h3 class="text-base font-black text-white">📱 Generador Viral Multi-Red &amp; Placa Pro</h3>
          <button type="button" @click="triggerCampaignPlaque" class="px-3 py-1.5 rounded-xl bg-indigo-500/20 hover:bg-indigo-500 text-indigo-200 hover:text-white border border-indigo-400/40 font-black cursor-pointer">
            📤 Abrir Placa con Logo
          </button>
        </div>

        <div class="grid grid-cols-2 gap-2">
          <select v-model="socialBuilder.channel" class="bg-slate-900 border border-slate-700 rounded-xl p-2 text-white font-bold">
            <option value="whatsapp">WhatsApp</option>
            <option value="instagram">Instagram / Reels</option>
            <option value="tiktok">TikTok</option>
            <option value="telegram">Telegram Canal</option>
          </select>
          <select v-model="socialBuilder.targetType" class="bg-slate-900 border border-slate-700 rounded-xl p-2 text-white">
            <option value="games">Enlace a Juegos Digitales</option>
            <option value="combos">Enlace a Combos</option>
            <option value="giveaway">Enlace a Sorteo Activo</option>
            <option value="store">Portada Oficial</option>
          </select>
        </div>

        <div class="grid grid-cols-2 gap-2">
          <select v-model="socialBuilder.promoSource" class="bg-slate-900 border border-slate-700 rounded-xl p-2 text-amber-300 font-mono">
            <option value="NONE">Sin código adjunto</option>
            <option v-for="du in adminStore.activeDiscountUsers" :key="du.id" :value="`DUSER:${du.id}`">
              Embajador: {{ du.code }} ({{ du.name }})
            </option>
            <option v-for="c in (adminStore.promoCodes || []).filter(x => x.active !== false)" :key="c.code" :value="`COUPON:${c.code}`">
              Cupón: {{ c.code }}
            </option>
          </select>
          <select v-model="socialBuilder.resellerId" class="bg-slate-900 border border-slate-700 rounded-xl p-2 text-sky-300">
            <option value="DIRECT">Atribución Directa</option>
            <option v-for="r in adminStore.resellers" :key="r.id" :value="r.id">Revendedor: {{ r.storeName || r.name }}</option>
          </select>
        </div>

        <input v-model="socialBuilder.headline" type="text" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2 text-white font-bold" placeholder="Titular de campaña">
        <textarea :value="socialGeneratedText" readonly rows="7" class="w-full bg-slate-950 border border-slate-800 rounded-2xl p-3 text-slate-200 font-mono outline-none resize-none custom-scrollbar"></textarea>
        <button type="button" @click="copySocialCopy" class="w-full py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-black cursor-pointer">
          📋 Copiar Copy + Link con Atribución
        </button>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- SUB-TAB 4: CUPONES PROMOCIONALES GLOBALES                      -->
    <!-- ============================================================== -->
    <div v-else-if="subTab === 'coupons'" class="bg-[#020617] border border-indigo-500/30 rounded-3xl p-5 space-y-4 text-xs">
      <h3 class="text-base font-black text-white">🎟️ Cupones Globales de Campaña</h3>
      <form @submit.prevent="createGlobalCoupon" class="grid grid-cols-2 sm:grid-cols-6 gap-2">
        <input v-model="globalCouponForm.code" type="text" required placeholder="CÓDIGO" class="bg-slate-900 border border-slate-700 rounded-xl p-2 text-amber-300 font-mono uppercase">
        <select v-model="globalCouponForm.type" class="bg-slate-900 border border-slate-700 rounded-xl p-2 text-white">
          <option value="percent">% OFF</option>
          <option value="fixed">$ ARS</option>
        </select>
        <input v-model.number="globalCouponForm.value" type="number" min="1" placeholder="Valor" class="bg-slate-900 border border-slate-700 rounded-xl p-2 text-white font-mono">
        <select v-model="globalCouponForm.scope" class="bg-slate-900 border border-slate-700 rounded-xl p-2 text-white">
          <option value="all">Toda la tienda</option>
          <option value="gaming">Solo Juegos</option>
          <option value="streaming">Solo Streaming</option>
          <option value="packages">Solo Combos</option>
          <option value="physical">Solo Físicos</option>
        </select>
        <input v-model="globalCouponForm.expiresAt" type="date" class="bg-slate-900 border border-slate-700 rounded-xl p-2 text-white" title="Fecha vencimiento">
        <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white font-black rounded-xl px-3 py-2 cursor-pointer">➕ Crear Cupón</button>
      </form>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div v-for="c in adminStore.promoCodes" :key="c.code" class="bg-slate-950 border border-slate-800 rounded-2xl p-3 flex items-center justify-between">
          <div>
            <strong class="font-mono text-amber-300 text-sm">{{ c.code }}</strong>
            <span class="text-slate-400 ml-2">{{ c.uses || 0 }}/{{ c.maxUses || '∞' }} usos · {{ c.scope }}</span>
            <p class="text-[11px] text-slate-400">{{ c.description }} {{ c.expiresAt ? `· Vence ${c.expiresAt}` : '' }}</p>
          </div>
          <div class="flex gap-1.5">
            <button type="button" @click="adminStore.togglePromoCode(c.code)" class="px-2 py-1 rounded bg-slate-800 text-amber-300 cursor-pointer">{{ c.active !== false ? '⏸️' : '▶️' }}</button>
            <button type="button" @click="adminStore.deletePromoCode(c.code)" class="px-2 py-1 rounded bg-rose-500/20 text-rose-300 cursor-pointer">🗑️</button>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- SUB-TAB 5: MÉTRICAS Y LOG DE CRÉDITOS                          -->
    <!-- ============================================================== -->
    <div v-else-if="subTab === 'analytics'" class="bg-[#020617] border border-emerald-500/30 rounded-3xl p-5 space-y-4 text-xs">
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
        <div class="bg-slate-950 p-3 rounded-2xl border border-slate-800">
          <span class="text-[10px] text-slate-400 block">Órdenes c/ Promo</span>
          <strong class="text-xl text-white font-mono">{{ adminStore.marketingMetrics.promoOrdersCount }}</strong>
        </div>
        <div class="bg-slate-950 p-3 rounded-2xl border border-slate-800">
          <span class="text-[10px] text-slate-400 block">Ahorro Otorgado</span>
          <strong class="text-xl text-amber-300 font-mono">${{ adminStore.marketingMetrics.totalDiscountsGiven.toLocaleString() }}</strong>
        </div>
        <div class="bg-slate-950 p-3 rounded-2xl border border-slate-800">
          <span class="text-[10px] text-slate-400 block">Crédito Pendiente</span>
          <strong class="text-xl text-sky-300 font-mono">${{ adminStore.marketingMetrics.totalPendingRewards.toLocaleString() }}</strong>
        </div>
        <div class="bg-slate-950 p-3 rounded-2xl border border-slate-800">
          <span class="text-[10px] text-slate-400 block">Crédito Disponible Total</span>
          <strong class="text-xl text-emerald-300 font-mono">${{ adminStore.marketingMetrics.totalCreditBalance.toLocaleString() }}</strong>
        </div>
      </div>

      <h4 class="font-black text-white">📜 Movimientos de Atribución y Créditos de Usuarios Descuento</h4>
      <div v-if="!adminStore.discountCreditsLog?.length" class="text-center py-8 text-slate-500">Sin movimientos registrados aún.</div>
      <div v-else class="space-y-2 max-h-80 overflow-y-auto custom-scrollbar">
        <div v-for="l in adminStore.discountCreditsLog" :key="l.id" class="bg-slate-950 border border-slate-800 rounded-xl p-3 flex items-center justify-between gap-2">
          <div>
            <strong class="text-amber-300 font-mono">{{ l.code }}</strong>
            <span class="text-white font-bold ml-2">{{ l.userName }}</span>
            <span class="text-slate-400 ml-2">· {{ l.note }}</span>
          </div>
          <div class="text-right font-mono shrink-0">
            <span :class="['font-black', l.amount >= 0 ? 'text-emerald-300' : 'text-sky-300']">
              {{ l.amount >= 0 ? `+$${Number(l.amount).toLocaleString()}` : `-$${Math.abs(Number(l.amount)).toLocaleString()}` }}
            </span>
            <span class="ml-2 px-2 py-0.5 rounded bg-slate-800 text-[10px] text-slate-300">{{ l.status }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- SUB-TAB 6: ESTUDIO AUTOMÁTICO DE REELS, TIKTOK & ESTADOS 9:16  -->
    <!-- ============================================================== -->
    <div v-else-if="subTab === 'reels_studio'" class="space-y-6 animate-in fade-in">
      <div class="bg-gradient-to-r from-pink-950/40 via-purple-950/30 to-slate-900 p-6 rounded-3xl border border-pink-500/30 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
          <span class="text-xs font-bold uppercase tracking-wider bg-pink-500/20 text-pink-300 border border-pink-500/30 px-2.5 py-0.5 rounded-full">
            🎬 Generador de Contenido Vertical 9:16
          </span>
          <h3 class="text-2xl font-black text-white mt-1">Estudio de Reels, TikTok &amp; Estados de WhatsApp</h3>
          <p class="text-xs text-slate-300 mt-1 max-w-2xl">
            Genera automáticamente videos animados suaves de 5 segundos a 30 FPS o imágenes Ultra HD 9:16 con portadas oficiales, precios y cupones de StreamePro, listos para descargar o publicar en redes.
          </p>
        </div>

        <button
          type="button"
          @click="openAdminReelsStudio(null)"
          class="bg-gradient-to-r from-pink-500 via-purple-600 to-indigo-600 hover:from-pink-400 hover:to-indigo-500 text-white font-black text-xs sm:text-sm px-6 py-3.5 rounded-2xl shadow-xl shadow-pink-500/25 flex items-center gap-2 cursor-pointer shrink-0 active:scale-95 transition"
        >
          <span>🎬</span>
          <span>Abrir Estudio Completo 9:16</span>
        </button>
      </div>

      <!-- ACCESOS RÁPIDOS POR CATEGORÍA PARA EL ADMIN -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-slate-950 p-5 rounded-2xl border border-slate-800 hover:border-pink-500/40 transition space-y-3 flex flex-col justify-between">
          <div class="space-y-1">
            <span class="text-2xl">🎬</span>
            <h4 class="font-bold text-white text-sm">Streaming Oficial</h4>
            <p class="text-xs text-slate-400">Reels verticales para Netflix, Max, Spotify con cuentas privadas 4K y PIN exclusivo.</p>
          </div>
          <button
            type="button"
            @click="openAdminReelsStudio({ platform: 'Netflix' })"
            class="w-full py-2 px-3 rounded-xl bg-pink-600/20 hover:bg-pink-600 text-pink-300 hover:text-white border border-pink-500/30 text-xs font-bold transition cursor-pointer"
          >
            Crear Reel Streaming
          </button>
        </div>

        <div class="bg-slate-950 p-5 rounded-2xl border border-slate-800 hover:border-indigo-500/40 transition space-y-3 flex flex-col justify-between">
          <div class="space-y-1">
            <span class="text-2xl">🎮</span>
            <h4 class="font-bold text-white text-sm">Videojuegos Digitales PS5/PS4</h4>
            <p class="text-xs text-slate-400">Placas 9:16 de FC 25, GTA V, God of War con portadas oficiales de PS Store.</p>
          </div>
          <button
            type="button"
            @click="openAdminReelsStudio({ itemType: 'gaming' })"
            class="w-full py-2 px-3 rounded-xl bg-indigo-600/20 hover:bg-indigo-600 text-indigo-300 hover:text-white border border-indigo-500/30 text-xs font-bold transition cursor-pointer"
          >
            Crear Reel Gaming
          </button>
        </div>

        <div class="bg-slate-950 p-5 rounded-2xl border border-slate-800 hover:border-fuchsia-500/40 transition space-y-3 flex flex-col justify-between">
          <div class="space-y-1">
            <span class="text-2xl">🎉</span>
            <h4 class="font-bold text-white text-sm">Sorteos Oficiales Activos</h4>
            <p class="text-xs text-slate-400">Videos y estados para promocionar el sorteo en curso y atraer cientos de nuevos registros.</p>
          </div>
          <button
            type="button"
            @click="openAdminReelsStudio({ prizeTitle: 'Cuenta Anual' })"
            class="w-full py-2 px-3 rounded-xl bg-fuchsia-600/20 hover:bg-fuchsia-600 text-fuchsia-300 hover:text-white border border-fuchsia-500/30 text-xs font-bold transition cursor-pointer"
          >
            Crear Reel Sorteo
          </button>
        </div>

        <div class="bg-slate-950 p-5 rounded-2xl border border-slate-800 hover:border-purple-500/40 transition space-y-3 flex flex-col justify-between">
          <div class="space-y-1">
            <span class="text-2xl">🎁</span>
            <h4 class="font-bold text-white text-sm">Combos &amp; Paquetes VIP</h4>
            <p class="text-xs text-slate-400">Reels comparativos mostrando el super ahorro al comprar combos múltiples.</p>
          </div>
          <button
            type="button"
            @click="openAdminReelsStudio({ services: true })"
            class="w-full py-2 px-3 rounded-xl bg-purple-600/20 hover:bg-purple-600 text-purple-300 hover:text-white border border-purple-500/30 text-xs font-bold transition cursor-pointer"
          >
            Crear Reel Combo
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL CANJE / LIQUIDACIÓN DE CRÉDITO -->
    <div v-if="redeemModalUser" class="fixed inset-0 z-[90] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
      <div class="bg-slate-900 border border-sky-500/40 w-full max-w-md rounded-3xl p-5 space-y-4 text-xs">
        <div class="flex justify-between items-center border-b border-slate-800 pb-2">
          <h4 class="font-black text-white text-sm">💳 Liquidar / Canjear Crédito ({{ redeemModalUser.code }})</h4>
          <button type="button" @click="redeemModalUser = null" class="text-slate-400 hover:text-white font-bold cursor-pointer">✕</button>
        </div>
        <p class="text-slate-300">Saldo disponible: <strong class="text-emerald-300 font-mono">${{ Number(redeemModalUser.creditBalance || 0).toLocaleString() }} ARS</strong></p>
        <div class="space-y-2.5">
          <div>
            <label class="text-slate-400 block mb-1">Monto a canjear (ARS)</label>
            <input v-model.number="redeemForm.amount" type="number" min="1" :max="redeemModalUser.creditBalance" class="w-full bg-slate-950 border border-slate-700 rounded-xl p-2 text-white font-mono">
          </div>
          <div>
            <label class="text-slate-400 block mb-1">Modalidad</label>
            <select v-model="redeemForm.mode" class="w-full bg-slate-950 border border-slate-700 rounded-xl p-2 text-white">
              <option value="voucher">Generar Vale / Cupón Personal (1 uso en tienda)</option>
              <option value="payout">Liquidación por Transferencia / Saldo</option>
            </select>
          </div>
          <div>
            <label class="text-slate-400 block mb-1">Nota</label>
            <input v-model="redeemForm.note" type="text" placeholder="Ej: Canje octubre / Alias ..." class="w-full bg-slate-950 border border-slate-700 rounded-xl p-2 text-white">
          </div>
        </div>
        <button type="button" @click="confirmRedeemCredit" class="w-full py-2.5 rounded-xl bg-sky-400 hover:bg-sky-300 text-slate-950 font-black cursor-pointer">
          ✅ Confirmar Operación
        </button>
      </div>
    </div>
  </div>

    <!-- MODAL: ESTUDIO DE REELS, TIKTOK & ESTADOS 9:16 -->
    <ReelsStoryStudioModal
      :show="showAdminReelsModal"
      :initial-payload="adminReelsPayload"
      :store-name="'StreamePro Oficial'"
      :contact-phone="'+54 9 11 4050-6070'"
      :promo-code="adminStore.marketingCampaign?.highlightedCode || 'BIENVENIDO10'"
      :website-domain="'streamepro.me'"
      @close="showAdminReelsModal = false; adminReelsPayload = null"
    />
</template>

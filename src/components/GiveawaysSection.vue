<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAdminStore } from '../store/admin'
import { useToastStore } from '../store/toast'
import { normalizePhoneDigits, buildDiscountProgramFormalities } from '../utils/marketing'

const props = defineProps({
  resellerRef: { type: String, default: 'DIRECT' }
})

const route = useRoute()
const router = useRouter()
const adminStore = useAdminStore()
const toast = useToastStore()

const selectedGiveaway = ref(null)
const showParticipateModal = ref(false)
const showFormalitiesModal = ref(false)
const showDiscountProgramModal = ref(false)
const myLookupPhone = ref('')
const lastIssuedPass = ref(null)

const entryForm = ref({
  name: '',
  phone: '',
  email: '',
  instagram: '',
  invitedBy: '',
  acceptedTerms: false
})

const appForm = ref({
  name: '',
  phone: '',
  email: '',
  pin: '',
  preferredCode: '',
  channel: 'WhatsApp / Estados',
  audience: '',
  message: '',
  acceptedTerms: false
})

const hydrateFromSavedProfile = () => {
  try {
    const raw = localStorage.getItem('streampro_user_profile')
    if (raw) {
      const p = JSON.parse(raw)
      if (p.name && !entryForm.value.name) entryForm.value.name = p.name
      if (p.phone && !entryForm.value.phone) entryForm.value.phone = p.phone
      if (p.email && !entryForm.value.email) entryForm.value.email = p.email
      if (p.name && !appForm.value.name) appForm.value.name = p.name
      if (p.phone && !appForm.value.phone) appForm.value.phone = p.phone
      if (p.email && !appForm.value.email) appForm.value.email = p.email
      if (p.pin && !appForm.value.pin) appForm.value.pin = p.pin
      if (p.phone && !myLookupPhone.value) myLookupPhone.value = p.phone
    }
  } catch {}
}

onMounted(() => {
  hydrateFromSavedProfile()
  const sorteoParam = route.query.sorteo
  const invParam = route.query.inv
  if (invParam) {
    entryForm.value.invitedBy = String(invParam).trim()
    try { localStorage.setItem('streamepro_sorteo_inv', String(invParam).trim()) } catch {}
  } else {
    try {
      const savedInv = localStorage.getItem('streamepro_sorteo_inv')
      if (savedInv) entryForm.value.invitedBy = savedInv
    } catch {}
  }

  if (sorteoParam) {
    const found = (adminStore.visibleGiveaways || []).find(g => g.id === String(sorteoParam).trim())
    if (found) {
      openParticipateModal(found)
    }
  }
})

const visibleGiveaways = computed(() => {
  return (adminStore.visibleGiveaways || []).filter(g =>
    (g.resellerRef === 'DIRECT' || !g.resellerRef || g.isOfficial === true) &&
    g.status &&
    g.status !== 'draft'
  )
})
const discountProgramRules = computed(() => buildDiscountProgramFormalities(adminStore.marketingCampaign))

const getDaysLeftLabel = (endDateStr) => {
  if (!endDateStr) return 'Vigente'
  const end = new Date(`${endDateStr}T23:59:59`).getTime()
  const diff = end - Date.now()
  if (diff <= 0) return 'Cierra hoy'
  const days = Math.floor(diff / 86400000)
  const hrs = Math.floor((diff % 86400000) / 3600000)
  return days > 0 ? `Faltan ${days}d ${hrs}h` : `Últimas ${hrs}h`
}

const getSummary = (giveawayId) => adminStore.getGiveawayTicketSummary(giveawayId)

const openParticipateModal = (g) => {
  hydrateFromSavedProfile()
  selectedGiveaway.value = g
  lastIssuedPass.value = null
  showFormalitiesModal.value = false
  showParticipateModal.value = true
}

const submitParticipation = () => {
  if (!selectedGiveaway.value) return
  const res = adminStore.participateInGiveaway({
    giveawayId: selectedGiveaway.value.id,
    name: entryForm.value.name,
    phone: entryForm.value.phone,
    email: entryForm.value.email,
    instagram: entryForm.value.instagram,
    invitedBy: entryForm.value.invitedBy,
    acceptedTerms: entryForm.value.acceptedTerms
  })
  if (res.error) {
    toast.add(res.error, 'error')
    return
  }
  myLookupPhone.value = entryForm.value.phone
  lastIssuedPass.value = res.entry
  toast.add(`🎉 ¡Inscripto en ${selectedGiveaway.value.title}! Ticket #${String(res.entry.startTicket).padStart(4, '0')}`, 'success')
}

const buildGiveawayShareUrl = (giveaway, invToken = '') => {
  const origin = typeof window !== 'undefined' && (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1')
    ? window.location.origin
    : 'https://streamepro.me'
  const qs = new URLSearchParams()
  qs.set('sorteo', giveaway.id)
  if (invToken) qs.set('inv', invToken)
  if (props.resellerRef && props.resellerRef !== 'DIRECT') qs.set('ref', props.resellerRef)
  return `${origin}/sorteos?${qs.toString()}`
}

const shareGiveawayInviteWhatsApp = (giveaway, invToken = '') => {
  const url = buildGiveawayShareUrl(giveaway, invToken)
  const refTickets = Number(giveaway.rules?.ticketsPerReferral || 0)
  const msg = `🎉 *SORTEO OFICIAL STREAMEPRO: ${giveaway.title}*\n` +
    `🎁 *Premio:* ${giveaway.prizeTitle}\n` +
    (refTickets > 0 ? `⚡ ¡Participá gratis y sumamos chances extra!\n` : `✅ Participá gratis siguiendo las bases oficiales.\n`) +
    `\n👉 *Inscribite acá:*\n${url}`
  window.open(`https://wa.me/?text=${encodeURIComponent(msg)}`, '_blank')
}

const copyGiveawayInviteUrl = async (giveaway, invToken = '') => {
  const url = buildGiveawayShareUrl(giveaway, invToken)
  try {
    await navigator.clipboard.writeText(url)
    toast.add('🔗 Enlace del sorteo copiado', 'success')
  } catch {
    toast.add(url, 'info')
  }
}

const myTicketsByGiveaway = computed(() => {
  const digits = normalizePhoneDigits(myLookupPhone.value)
  if (digits.length < 6) return []
  const rows = []
  ;(visibleGiveaways.value || []).forEach((g) => {
    const sum = getSummary(g.id)
    const mine = sum.entries.filter(e => e.normalizedPhone === digits)
    if (mine.length > 0) {
      rows.push({
        giveaway: g,
        entries: mine,
        totalTickets: mine.reduce((a, b) => a + Number(b.ticketsCount || 1), 0),
        referralToken: mine.find(m => m.referralToken)?.referralToken || ''
      })
    }
  })
  return rows
})

const submitDiscountApplication = () => {
  const res = adminStore.submitDiscountUserApplication(appForm.value)
  if (res.error) {
    toast.add(res.error, 'error')
    return
  }
  toast.add(`✅ Solicitud #${res.application.id} enviada. Revisa el estado en Mi Bóveda con tu WhatsApp y PIN.`, 'success')
  showDiscountProgramModal.value = false
  appForm.value.preferredCode = ''
  appForm.value.message = ''
}
</script>

<template>
  <section id="sorteos-y-embajadores" class="space-y-6">
    <!-- Encabezado & Acceso Programa Usuarios Descuento -->
    <div class="bg-gradient-to-br from-[#071128] via-[#0c1633] to-[#081b29] border border-fuchsia-500/30 rounded-3xl p-5 sm:p-7 shadow-2xl relative overflow-hidden">
      <div class="absolute -right-14 -top-14 w-56 h-56 rounded-full bg-fuchsia-500/15 blur-3xl pointer-events-none"></div>
      <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 relative z-10">
        <div class="space-y-1.5 max-w-2xl">
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-fuchsia-500/15 border border-fuchsia-400/30 text-fuchsia-300 text-[10px] font-black uppercase tracking-wider">
            <span class="w-2 h-2 rounded-full bg-fuchsia-400 animate-pulse"></span>
            <span>Centro Oficial de Sorteos &amp; Usuarios Descuento</span>
          </div>
          <h3 class="text-2xl sm:text-3xl font-black text-white tracking-tight">
            Participá en Sorteos Oficiales o Compartí con Porcentajes Ajustados
          </h3>
          <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
            Inscribite gratis bajo bases transparentes, multiplicá tus chances con tus compras o invitando amigos, y postulate como <strong class="text-amber-300">Usuario Descuento</strong> para compartir tu código con porcentajes ajustados por rubro.
          </p>
        </div>

        <div class="flex flex-wrap gap-2.5 shrink-0">
          <button
            type="button"
            @click="showDiscountProgramModal = true"
            class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-amber-400 to-orange-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 font-black text-xs shadow-lg transition cursor-pointer flex items-center gap-1.5"
          >
            <span>🏷️</span>
            <span>Quiero Ser Usuario Descuento</span>
          </button>
          <button
            type="button"
            @click="router.push('/portal?tab=promos')"
            class="px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-sky-300 border border-sky-500/30 font-bold text-xs transition cursor-pointer flex items-center gap-1.5"
          >
            <span>🎟️</span>
            <span>Ver Mis Tickets y Código</span>
          </button>
        </div>
      </div>

      <!-- Consultar Mis Chances Rápido -->
      <div v-if="visibleGiveaways.length > 0" class="mt-5 pt-4 border-t border-white/10 flex flex-col sm:flex-row sm:items-center justify-between gap-3 relative z-10">
        <div class="flex items-center gap-2 text-xs text-slate-300">
          <span>🔎 Consultar mis números de ticket:</span>
          <input
            v-model="myLookupPhone"
            type="text"
            placeholder="Tu WhatsApp (ej: 11 2233-4455)"
            class="bg-slate-950/90 border border-slate-700 focus:border-fuchsia-400 rounded-xl px-3 py-1.5 text-xs text-white font-mono outline-none w-48 sm:w-56"
          >
        </div>
        <div v-if="myTicketsByGiveaway.length > 0" class="flex flex-wrap gap-2">
          <div
            v-for="mt in myTicketsByGiveaway"
            :key="mt.giveaway.id"
            class="px-3 py-1.5 rounded-xl bg-emerald-500/15 border border-emerald-400/30 text-[11px] text-emerald-200 flex items-center gap-2"
          >
            <strong class="text-white">{{ mt.giveaway.title }}:</strong>
            <span class="font-black text-emerald-300">{{ mt.totalTickets }} tickets</span>
            <button
              v-if="mt.referralToken && mt.giveaway.status === 'active'"
              type="button"
              @click="shareGiveawayInviteWhatsApp(mt.giveaway, mt.referralToken)"
              class="px-2 py-0.5 rounded bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[10px] cursor-pointer"
            >
              +Chances WA
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Lista de Sorteos Activos y Finalizados con Acta -->
    <div v-if="visibleGiveaways.length === 0" class="bg-slate-900/50 border border-slate-800 rounded-3xl p-8 text-center space-y-2">
      <span class="text-3xl block">🎉</span>
      <h4 class="text-base font-black text-white">Próximo Sorteo Oficial en Preparación</h4>
      <p class="text-xs text-slate-400 max-w-md mx-auto">
        Aún no hay sorteos publicados en este momento. Tus compras y referidos se registran en el sistema para próximas campañas especiales.
      </p>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-5">
      <article
        v-for="g in visibleGiveaways"
        :key="g.id"
        :class="[
          'rounded-3xl border overflow-hidden shadow-xl flex flex-col justify-between transition-all bg-[#060d1f]',
          g.status === 'active' ? 'border-fuchsia-500/40 hover:border-fuchsia-400/70' : 'border-slate-700/80'
        ]"
      >
        <div class="p-5 sm:p-6 space-y-4">
          <div class="flex items-start justify-between gap-3">
            <div class="space-y-1">
              <div class="flex flex-wrap items-center gap-1.5">
                <span :class="[
                  'text-[10px] font-black uppercase px-2.5 py-0.5 rounded-full border',
                  g.status === 'active' ? 'bg-emerald-500/20 text-emerald-300 border-emerald-400/40' :
                  g.status === 'drawn' ? 'bg-amber-500/20 text-amber-300 border-amber-400/40' :
                  'bg-slate-800 text-slate-300 border-slate-700'
                ]">
                  {{ g.status === 'active' ? '🟢 Inscripción Abierta' : g.status === 'drawn' ? '🏆 Sorteo Realizado' : '⏸️ Inscripción Cerrada' }}
                </span>
                <span class="text-[10px] font-mono font-bold px-2.5 py-0.5 rounded-full bg-fuchsia-500/15 text-fuchsia-300 border border-fuchsia-500/30">
                  {{ g.status === 'active' ? getDaysLeftLabel(g.endDate) : (g.drawDateLabel || g.endDate) }}
                </span>
              </div>
              <h4 class="text-lg sm:text-xl font-black text-white leading-tight pt-1">{{ g.title }}</h4>
              <p class="text-xs text-slate-400">{{ g.subtitle }}</p>
            </div>
            <img
              v-if="g.prizeImage"
              :src="g.prizeImage"
              :alt="g.prizeTitle"
              class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl object-cover border border-white/15 bg-slate-900 shrink-0 shadow-lg"
            >
          </div>

          <!-- Premio Principal -->
          <div class="p-3.5 rounded-2xl bg-gradient-to-r from-fuchsia-950/60 to-indigo-950/60 border border-fuchsia-500/30 flex items-center justify-between gap-3">
            <div>
              <span class="text-[10px] uppercase font-bold text-fuchsia-300 block">🎁 Premio Oficial</span>
              <strong class="text-sm text-white block">{{ g.prizeTitle }}</strong>
            </div>
            <div v-if="g.prizeValue > 0" class="text-right shrink-0">
              <span class="text-[9px] text-slate-400 uppercase block">Valor Ref.</span>
              <span class="text-sm font-black font-mono text-amber-300">${{ Number(g.prizeValue).toLocaleString() }}</span>
            </div>
          </div>

          <!-- Dinámicas de Tickets -->
          <div class="grid grid-cols-3 gap-2 text-center text-[11px]">
            <div class="bg-slate-950/80 border border-slate-800 rounded-xl p-2">
              <span class="font-black text-emerald-300 block">
                {{ g.rules?.freeEntryEnabled !== false ? `+${g.rules?.freeEntryTickets || 1}` : 'Solo Compra' }}
              </span>
              <span class="text-[10px] text-slate-400">Inscripción Web</span>
            </div>
            <div class="bg-slate-950/80 border border-slate-800 rounded-xl p-2">
              <span class="font-black text-sky-300 block">+{{ g.rules?.ticketsPerPurchase || 0 }}</span>
              <span class="text-[10px] text-slate-400">Por Compra</span>
            </div>
            <div class="bg-slate-950/80 border border-slate-800 rounded-xl p-2">
              <span class="font-black text-pink-300 block">+{{ g.rules?.ticketsPerReferral || 0 }}</span>
              <span class="text-[10px] text-slate-400">Por Referido</span>
            </div>
          </div>

          <!-- Contadores Verificados -->
          <div class="flex items-center justify-between text-xs text-slate-400 bg-slate-950/70 px-3.5 py-2 rounded-xl border border-slate-800/80">
            <span>👥 <strong class="text-white">{{ getSummary(g.id).uniqueParticipants }}</strong> participantes</span>
            <span>🎟️ <strong class="text-fuchsia-300 font-mono">{{ getSummary(g.id).totalTickets }}</strong> tickets emitidos</span>
            <span>🏆 <strong class="text-amber-300">{{ g.winnersCount || 1 }}</strong> ganador(es)</span>
          </div>

          <!-- Resultado Oficial Verificado (si ya fue sorteado) -->
          <div v-if="g.status === 'drawn' && g.winners?.length" class="p-3.5 rounded-2xl bg-amber-500/10 border border-amber-400/40 space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-xs font-black text-amber-300">🏆 Acta Verificada: {{ g.drawProof?.verificationCode }}</span>
              <span class="text-[10px] font-mono text-slate-400">{{ (g.drawProof?.drawnAt || '').slice(0, 10) }}</span>
            </div>
            <div class="space-y-1.5">
              <div
                v-for="(w, wi) in g.winners"
                :key="wi"
                class="flex items-center justify-between bg-slate-950/90 px-3 py-2 rounded-xl border border-slate-800 text-xs"
              >
                <div>
                  <span :class="['font-black text-[10px] uppercase mr-1.5', w.isAlternate ? 'text-slate-400' : 'text-emerald-300']">
                    {{ w.roleLabel }}:
                  </span>
                  <strong class="text-white">{{ w.participantName }}</strong>
                  <span class="text-slate-400 font-mono text-[10px] ml-1.5">({{ w.maskedPhone }})</span>
                </div>
                <span class="font-mono font-black text-amber-300 bg-amber-500/15 px-2 py-0.5 rounded">
                  Ticket #{{ String(w.ticketNumber).padStart(4, '0') }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Botonera Footer -->
        <div class="px-5 pb-5 flex flex-wrap gap-2">
          <button
            v-if="g.status === 'active'"
            type="button"
            @click="openParticipateModal(g)"
            class="flex-1 min-w-[160px] py-3 px-4 rounded-xl bg-gradient-to-r from-fuchsia-600 to-indigo-600 hover:from-fuchsia-500 hover:to-indigo-500 text-white font-black text-xs shadow-lg transition cursor-pointer"
          >
            🎟️ Participar y Obtener Ticket
          </button>
          <button
            type="button"
            @click="selectedGiveaway = g; showFormalitiesModal = true"
            class="py-3 px-3.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-700 font-bold text-xs transition cursor-pointer"
          >
            📜 Bases y Formalidades
          </button>
          <button
            v-if="g.status === 'active'"
            type="button"
            @click="shareGiveawayInviteWhatsApp(g)"
            class="py-3 px-3.5 rounded-xl bg-emerald-600/20 hover:bg-emerald-600 text-emerald-300 hover:text-white border border-emerald-500/30 font-bold text-xs transition cursor-pointer"
            title="Compartir sorteo por WhatsApp"
          >
            📲
          </button>
        </div>
      </article>
    </div>

    <!-- MODAL PARTICIPACIÓN EN SORTEO -->
    <div v-if="showParticipateModal && selectedGiveaway" class="fixed inset-0 z-[85] flex items-center justify-center p-3 sm:p-5 bg-black/85 backdrop-blur-md overflow-y-auto">
      <div class="bg-slate-900 border border-fuchsia-500/40 w-full max-w-lg rounded-3xl p-6 shadow-2xl space-y-4">
        <div class="flex justify-between items-start border-b border-slate-800 pb-3">
          <div>
            <span class="text-[10px] font-black uppercase text-fuchsia-300">Sorteo Oficial Verificado</span>
            <h4 class="text-lg font-black text-white">{{ selectedGiveaway.title }}</h4>
            <p class="text-xs text-slate-400">Premio: {{ selectedGiveaway.prizeTitle }}</p>
          </div>
          <button type="button" @click="showParticipateModal = false" class="w-9 h-9 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold cursor-pointer">✕</button>
        </div>

        <div v-if="lastIssuedPass" class="space-y-4">
          <div class="p-4 rounded-2xl bg-emerald-500/15 border border-emerald-400/40 text-center space-y-2">
            <span class="text-3xl block">🎫</span>
            <h5 class="text-base font-black text-white">¡Inscripción Confirmada!</h5>
            <p class="text-xs text-emerald-200">
              Tus números de ticket asignados:
              <strong class="font-mono text-sm text-amber-300 block mt-1">
                #{{ String(lastIssuedPass.startTicket).padStart(4, '0') }}
                <template v-if="lastIssuedPass.endTicket > lastIssuedPass.startTicket">
                  al #{{ String(lastIssuedPass.endTicket).padStart(4, '0') }}
                </template>
                ({{ lastIssuedPass.ticketsCount }} chance/s)
              </strong>
            </p>
          </div>

          <div v-if="selectedGiveaway.rules?.ticketsPerReferral > 0" class="p-4 rounded-2xl bg-slate-950 border border-slate-800 space-y-2.5 text-xs">
            <strong class="text-fuchsia-300 block">🚀 Suma +{{ selectedGiveaway.rules.ticketsPerReferral }} tickets extra por cada amigo:</strong>
            <p class="text-slate-400 text-[11px]">Compartí tu enlace personal de invitación. Por cada participante nuevo válido que se sume con tu link, acreditamos chances extra automáticamente.</p>
            <div class="flex gap-2">
              <button
                type="button"
                @click="copyGiveawayInviteUrl(selectedGiveaway, lastIssuedPass.referralToken)"
                class="flex-1 py-2.5 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-bold cursor-pointer"
              >
                🔗 Copiar Link Invitación
              </button>
              <button
                type="button"
                @click="shareGiveawayInviteWhatsApp(selectedGiveaway, lastIssuedPass.referralToken)"
                class="flex-1 py-2.5 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-black cursor-pointer"
              >
                📲 Invitar por WhatsApp
              </button>
            </div>
          </div>

          <button type="button" @click="showParticipateModal = false" class="w-full py-2.5 rounded-xl bg-slate-800 text-slate-200 font-bold text-xs cursor-pointer">
            Cerrar
          </button>
        </div>

        <form v-else @submit.prevent="submitParticipation" class="space-y-3 text-xs">
          <div>
            <label class="block font-bold text-slate-300 mb-1">Nombre Completo *</label>
            <input v-model="entryForm.name" type="text" required placeholder="Tu nombre y apellido real" class="w-full bg-slate-950 border border-slate-700 rounded-xl p-2.5 text-white outline-none focus:border-fuchsia-400">
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
            <div>
              <label class="block font-bold text-slate-300 mb-1">WhatsApp Oficial *</label>
              <input v-model="entryForm.phone" type="text" required placeholder="Ej: 11 5544-3322" class="w-full bg-slate-950 border border-slate-700 rounded-xl p-2.5 text-white font-mono outline-none focus:border-fuchsia-400">
            </div>
            <div>
              <label class="block font-bold text-slate-300 mb-1">Instagram {{ selectedGiveaway.rules?.requireInstagram ? '*' : '(Opcional)' }}</label>
              <input v-model="entryForm.instagram" type="text" :required="selectedGiveaway.rules?.requireInstagram" placeholder="@tu_usuario" class="w-full bg-slate-950 border border-slate-700 rounded-xl p-2.5 text-white outline-none focus:border-fuchsia-400">
            </div>
          </div>

          <div v-if="entryForm.invitedBy" class="p-2.5 rounded-xl bg-indigo-500/15 border border-indigo-400/30 text-[11px] text-indigo-200 flex items-center justify-between">
            <span>Invitación aplicada: <strong class="font-mono text-white">{{ entryForm.invitedBy }}</strong></span>
            <button type="button" @click="entryForm.invitedBy = ''" class="text-slate-400 hover:text-white cursor-pointer">✕</button>
          </div>

          <div class="bg-slate-950/90 border border-slate-800 rounded-xl p-3 space-y-2">
            <div class="flex items-center justify-between">
              <strong class="text-slate-200">📜 Bases y Condiciones Formales</strong>
              <button type="button" @click="showFormalitiesModal = true" class="text-fuchsia-300 hover:underline font-bold cursor-pointer">Ver texto completo</button>
            </div>
            <label class="flex items-start gap-2 cursor-pointer text-[11px] text-slate-300">
              <input v-model="entryForm.acceptedTerms" type="checkbox" class="mt-0.5 rounded accent-fuchsia-500 cursor-pointer">
              <span>Declaro haber leído y aceptado las Bases y Condiciones Oficiales del sorteo, incluyendo la verificación única por WhatsApp y el plazo de 72hs para reclamo.</span>
            </label>
          </div>

          <button type="submit" class="w-full py-3 rounded-xl bg-gradient-to-r from-fuchsia-600 to-indigo-600 hover:from-fuchsia-500 hover:to-indigo-500 text-white font-black shadow-lg cursor-pointer">
            ✅ Confirmar Participación y Generar Ticket
          </button>
        </form>
      </div>
    </div>

    <!-- MODAL BASES Y FORMALIDADES COMPLETAS -->
    <div v-if="showFormalitiesModal && selectedGiveaway" class="fixed inset-0 z-[95] flex items-center justify-center p-3 sm:p-5 bg-black/90 backdrop-blur-md">
      <div class="bg-slate-900 border border-slate-700 w-full max-w-lg rounded-3xl p-6 shadow-2xl space-y-4">
        <div class="flex justify-between items-center border-b border-slate-800 pb-3">
          <h4 class="text-base font-black text-white">📜 Bases y Formalidades Oficiales</h4>
          <button type="button" @click="showFormalitiesModal = false" class="w-8 h-8 rounded-lg bg-slate-800 text-slate-300 font-bold cursor-pointer">✕</button>
        </div>
        <div class="bg-slate-950 border border-slate-800 rounded-2xl p-4 max-h-80 overflow-y-auto text-xs text-slate-300 whitespace-pre-line leading-relaxed custom-scrollbar">
          {{ selectedGiveaway.formalitiesText }}
        </div>
        <button type="button" @click="showFormalitiesModal = false" class="w-full py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-black text-xs cursor-pointer">
          Entendido
        </button>
      </div>
    </div>

    <!-- MODAL POSTULACIÓN PROGRAMA USUARIOS DESCUENTO -->
    <div v-if="showDiscountProgramModal" class="fixed inset-0 z-[90] flex items-center justify-center p-3 sm:p-5 bg-black/85 backdrop-blur-md overflow-y-auto">
      <div class="bg-slate-900 border border-amber-500/40 w-full max-w-lg rounded-3xl p-6 shadow-2xl space-y-4 max-h-[92vh] overflow-y-auto custom-scrollbar">
        <div class="flex justify-between items-start border-b border-slate-800 pb-3">
          <div>
            <span class="text-[10px] font-black uppercase text-amber-300">Programa Oficial de Beneficios</span>
            <h4 class="text-lg font-black text-white">🏷️ Usuarios Descuento por Porcentajes Ajustados</h4>
            <p class="text-xs text-slate-400">Comparte tu código único en redes o grupos y acumula créditos por cada compra confirmada.</p>
          </div>
          <button type="button" @click="showDiscountProgramModal = false" class="w-9 h-9 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold cursor-pointer">✕</button>
        </div>

        <div class="p-3.5 rounded-2xl bg-slate-950 border border-slate-800 space-y-1.5 text-[11px] text-slate-300">
          <strong class="text-amber-300 block uppercase tracking-wider text-[10px]">Formalidades &amp; Dinámica Oficial:</strong>
          <p v-for="(rule, i) in discountProgramRules" :key="i" class="leading-relaxed">• {{ rule }}</p>
        </div>

        <form @submit.prevent="submitDiscountApplication" class="space-y-3 text-xs">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
            <div>
              <label class="block font-bold text-slate-300 mb-1">Nombre Completo *</label>
              <input v-model="appForm.name" type="text" required class="w-full bg-slate-950 border border-slate-700 rounded-xl p-2.5 text-white outline-none focus:border-amber-400">
            </div>
            <div>
              <label class="block font-bold text-slate-300 mb-1">WhatsApp *</label>
              <input v-model="appForm.phone" type="text" required placeholder="11 5544-3322" class="w-full bg-slate-950 border border-slate-700 rounded-xl p-2.5 text-white font-mono outline-none focus:border-amber-400">
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
            <div>
              <label class="block font-bold text-slate-300 mb-1">Código Deseado *</label>
              <input v-model="appForm.preferredCode" type="text" placeholder="Ej: LUCAS10" class="w-full bg-slate-950 border border-slate-700 rounded-xl p-2.5 text-amber-300 font-mono uppercase outline-none focus:border-amber-400">
            </div>
            <div>
              <label class="block font-bold text-slate-300 mb-1">Canal Principal</label>
              <select v-model="appForm.channel" class="w-full bg-slate-950 border border-slate-700 rounded-xl p-2.5 text-white outline-none">
                <option value="WhatsApp / Estados">WhatsApp / Estados</option>
                <option value="Instagram / Reels">Instagram / Reels</option>
                <option value="TikTok / Streams">TikTok / Streams</option>
                <option value="Comunidad Gamer / Discord">Comunidad Gamer</option>
              </select>
            </div>
            <div>
              <label class="block font-bold text-slate-300 mb-1">PIN Bóveda (4 díg.)</label>
              <input v-model="appForm.pin" type="text" maxlength="4" placeholder="1234" class="w-full bg-slate-950 border border-slate-700 rounded-xl p-2.5 text-white font-mono text-center outline-none">
            </div>
          </div>

          <div>
            <label class="block font-bold text-slate-300 mb-1">Cómo planeas difundir (Opcional)</label>
            <textarea v-model="appForm.message" rows="2" placeholder="Ej: Comparto ofertas de PS5 y Netflix en mi grupo de amigos / Instagram @..." class="w-full bg-slate-950 border border-slate-700 rounded-xl p-2.5 text-white outline-none"></textarea>
          </div>

          <label class="flex items-start gap-2 cursor-pointer text-[11px] text-slate-300 bg-slate-950 p-3 rounded-xl border border-slate-800">
            <input v-model="appForm.acceptedTerms" type="checkbox" class="mt-0.5 rounded accent-amber-500 cursor-pointer">
            <span>Acepto las formalidades del programa: porcentajes ajustados definidos por Administración, prohibición de auto-compras salvo permiso expreso y acreditación de beneficios tras validar pagos.</span>
          </label>

          <button type="submit" class="w-full py-3 rounded-xl bg-gradient-to-r from-amber-400 to-orange-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 font-black shadow-lg cursor-pointer">
            🚀 Enviar Solicitud de Usuario Descuento
          </button>
        </form>
      </div>
    </div>
  </section>
</template>

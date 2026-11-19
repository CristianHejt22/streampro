<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useAdminStore } from '../store/admin'
import { useToastStore } from '../store/toast'
import { maskPhonePublic } from '../utils/marketing'

const props = defineProps({
  show: { type: Boolean, default: false },
  initialGiveawayId: { type: String, default: '' }
})

const emit = defineEmits(['close', 'winner-confirmed'])

const adminStore = useAdminStore()
const toast = useToastStore()

// Modos: 'official' (Desde base de datos) | 'free' (Bolillero libre 6 dígitos)
const drawMode = ref('official')
const selectedGiveawayId = ref('')
const soundEnabled = ref(true)
const isFullscreen = ref(false)

// Estado de la animación de los 6 rodillos
const isRolling = ref(false)
const reels = ref(['0', '0', '0', '0', '0', '0'])
const winningNumber = ref('')
const currentWinner = ref(null)
const drawnAlternates = ref([])
const drawnHistoryFree = ref([])

// Canvas de confeti
const confettiCanvas = ref(null)
let confettiInterval = null

// Web Audio API para efectos de sonido realistas sin dependencias externas
let audioCtx = null
const getAudioContext = () => {
  if (!audioCtx && typeof window !== 'undefined') {
    const AudioContextClass = window.AudioContext || window.webkitAudioContext
    if (AudioContextClass) {
      audioCtx = new AudioContextClass()
    }
  }
  if (audioCtx && audioCtx.state === 'suspended') {
    audioCtx.resume()
  }
  return audioCtx
}

const playTone = (freq, durationMs = 60, type = 'sine', gainVal = 0.15) => {
  if (!soundEnabled.value) return
  try {
    const ctx = getAudioContext()
    if (!ctx) return
    const osc = ctx.createOscillator()
    const gain = ctx.createGain()
    osc.type = type
    osc.frequency.setValueAtTime(freq, ctx.currentTime)
    gain.gain.setValueAtTime(gainVal, ctx.currentTime)
    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + durationMs / 1000)
    osc.connect(gain)
    gain.connect(ctx.destination)
    osc.start()
    osc.stop(ctx.currentTime + durationMs / 1000)
  } catch (_) {}
}

const playReelTick = (digitIndex) => {
  const baseFreq = 420 + digitIndex * 70
  playTone(baseFreq, 45, 'triangle', 0.12)
}

const playReelLock = (digitIndex) => {
  const baseFreq = 700 + digitIndex * 120
  playTone(baseFreq, 140, 'square', 0.18)
}

const playFanfare = () => {
  if (!soundEnabled.value) return
  const notes = [523.25, 659.25, 783.99, 1046.50] // C5, E5, G5, C6
  notes.forEach((freq, idx) => {
    setTimeout(() => {
      playTone(freq, 350, 'triangle', 0.22)
    }, idx * 110)
  })
}

// Resumen del sorteo oficial seleccionado
const selectedGiveaway = computed(() => {
  return (adminStore.giveaways || []).find(g => g.id === selectedGiveawayId.value) || null
})

const officialSummary = computed(() => {
  if (!selectedGiveawayId.value) return null
  return adminStore.getGiveawayTicketSummary(selectedGiveawayId.value)
})

const initGiveawaySelection = () => {
  if (props.initialGiveawayId) {
    selectedGiveawayId.value = props.initialGiveawayId
    drawMode.value = 'official'
  } else if (!selectedGiveawayId.value && adminStore.giveaways?.length > 0) {
    selectedGiveawayId.value = adminStore.giveaways[0].id
  }
}

watch(() => props.show, (newVal) => {
  if (newVal) {
    initGiveawaySelection()
  }
})

watch(() => props.initialGiveawayId, (newId) => {
  if (newId) {
    selectedGiveawayId.value = newId
    drawMode.value = 'official'
  }
})

onMounted(() => {
  initGiveawaySelection()
})

// Confeti animado ligero sobre canvas
const triggerConfetti = () => {
  const canvas = confettiCanvas.value
  if (!canvas) return
  const ctx = canvas.getContext('2d')
  canvas.width = canvas.parentElement.clientWidth
  canvas.height = canvas.parentElement.clientHeight

  const pieces = []
  const colors = ['#f43f5e', '#ec4899', '#d946ef', '#a855f7', '#06b6d4', '#10b981', '#f59e0b', '#fbbf24']
  for (let i = 0; i < 90; i++) {
    pieces.push({
      x: Math.random() * canvas.width,
      y: Math.random() * canvas.height * 0.4,
      size: Math.random() * 8 + 4,
      color: colors[Math.floor(Math.random() * colors.length)],
      vx: (Math.random() - 0.5) * 5,
      vy: Math.random() * 4 + 2,
      rot: Math.random() * 360,
      vRot: (Math.random() - 0.5) * 8
    })
  }

  let frames = 0
  if (confettiInterval) clearInterval(confettiInterval)
  confettiInterval = setInterval(() => {
    frames++
    ctx.clearRect(0, 0, canvas.width, canvas.height)
    pieces.forEach(p => {
      p.x += p.vx
      p.y += p.vy
      p.rot += p.vRot
      ctx.save()
      ctx.translate(p.x, p.y)
      ctx.rotate((p.rot * Math.PI) / 180)
      ctx.fillStyle = p.color
      ctx.fillRect(-p.size / 2, -p.size / 2, p.size, p.size * 0.6)
      ctx.restore()
    })
    if (frames > 120) {
      clearInterval(confettiInterval)
      confettiInterval = null
      ctx.clearRect(0, 0, canvas.width, canvas.height)
    }
  }, 25)
}

// Iniciar giro del bolillero de 6 dígitos
const startDraw = () => {
  if (isRolling.value) return
  getAudioContext()

  let targetLuckyNumber = ''
  let targetEntry = null

  if (drawMode.value === 'official') {
    const summary = officialSummary.value
    if (!summary || !summary.allLuckyNumbers || summary.allLuckyNumbers.length === 0) {
      toast.add('No hay números de 6 dígitos registrados para este sorteo.', 'error')
      return
    }
    // Filtrar números ya sorteados (ganador titular o suplentes anteriores)
    const alreadyDrawn = new Set()
    if (currentWinner.value) alreadyDrawn.add(currentWinner.value.luckyNumber)
    drawnAlternates.value.forEach(a => alreadyDrawn.add(a.luckyNumber))

    const available = summary.allLuckyNumbers.filter(ln => !alreadyDrawn.has(ln.luckyNumber))
    const pool = available.length > 0 ? available : summary.allLuckyNumbers

    const pickIdx = Math.floor(Math.random() * pool.length)
    const picked = pool[pickIdx]
    targetLuckyNumber = String(picked.luckyNumber)
    targetEntry = picked
  } else {
    // Modo bolillero libre: generar número al azar de 6 dígitos (100000 - 999999)
    targetLuckyNumber = String(Math.floor(100000 + Math.random() * 900000))
  }

  isRolling.value = true
  winningNumber.value = ''
  if (!currentWinner.value) {
    currentWinner.value = null
  }

  // Animación de los 6 rodillos con parada escalonada
  const locked = [false, false, false, false, false, false]
  const intervals = []

  reels.value.forEach((_, idx) => {
    intervals[idx] = setInterval(() => {
      if (!locked[idx]) {
        reels.value[idx] = String(Math.floor(Math.random() * 10))
        if (Math.random() > 0.4) playReelTick(idx)
      }
    }, 45 + idx * 8)
  })

  // Detener escalonadamente del dígito 0 al 5
  targetLuckyNumber.split('').forEach((digitChar, idx) => {
    const stopDelay = 1600 + idx * 420
    setTimeout(() => {
      locked[idx] = true
      clearInterval(intervals[idx])
      reels.value[idx] = digitChar
      playReelLock(idx)

      // Cuando frena el último dígito
      if (idx === 5) {
        isRolling.value = false
        winningNumber.value = targetLuckyNumber
        playFanfare()
        triggerConfetti()

        if (drawMode.value === 'official' && targetEntry) {
          const winnerPayload = {
            luckyNumber: targetLuckyNumber,
            ticketNumber: targetLuckyNumber,
            participantName: targetEntry.participantName,
            phone: targetEntry.phone,
            maskedPhone: targetEntry.maskedPhone || maskPhonePublic(targetEntry.phone),
            entryId: targetEntry.entryId,
            drawnAt: new Date().toISOString(),
            formattedDate: new Date().toLocaleString('es-AR')
          }
          if (!currentWinner.value) {
            currentWinner.value = winnerPayload
            toast.add(`🏆 ¡Número Ganador Oficial: #${targetLuckyNumber}!`, 'success')
          } else {
            drawnAlternates.value.push(winnerPayload)
            toast.add(`🥈 ¡Suplente Oficial: #${targetLuckyNumber}!`, 'info')
          }
        } else {
          drawnHistoryFree.value.unshift({
            number: targetLuckyNumber,
            time: new Date().toLocaleTimeString('es-AR')
          })
          toast.add(`🎰 Número generado al azar: #${targetLuckyNumber}`, 'success')
        }
      }
    }, stopDelay)
  })
}

// Resetear para sortear nuevo ganador o siguiente suplente
const resetForNextDraw = () => {
  winningNumber.value = ''
  reels.value = ['0', '0', '0', '0', '0', '0']
}

// Guardar y oficializar ganador en la base de datos
const confirmWinnerInDatabase = () => {
  if (!selectedGiveaway.value || !currentWinner.value) return
  const winnersList = [
    {
      rank: 1,
      isAlternate: false,
      roleLabel: 'Ganador Oficial Titular',
      ticketNumber: currentWinner.value.luckyNumber,
      luckyNumber: currentWinner.value.luckyNumber,
      entryId: currentWinner.value.entryId,
      participantName: currentWinner.value.participantName,
      phone: currentWinner.value.phone,
      maskedPhone: currentWinner.value.maskedPhone,
      claimed: false
    },
    ...drawnAlternates.value.map((alt, idx) => ({
      rank: idx + 2,
      isAlternate: true,
      roleLabel: `Suplente Oficial #${idx + 1}`,
      ticketNumber: alt.luckyNumber,
      luckyNumber: alt.luckyNumber,
      entryId: alt.entryId,
      participantName: alt.participantName,
      phone: alt.phone,
      maskedPhone: alt.maskedPhone,
      claimed: false
    }))
  ]

  const drawProof = {
    drawnAt: new Date().toISOString(),
    totalValidTickets: officialSummary.value?.totalTickets || 1,
    totalParticipants: officialSummary.value?.uniqueParticipants || 1,
    verificationCode: `SP-LIVE-${Date.now().toString(36).toUpperCase().slice(-6)}`,
    liveMethod: 'Bolillero Digital Verificado StreamePro 6 Dígitos',
    drawnLuckyNumber: currentWinner.value.luckyNumber
  }

  const res = adminStore.confirmGiveawayWinner({
    giveawayId: selectedGiveaway.value.id,
    winners: winnersList,
    drawProof
  })

  if (res.error) {
    toast.add(res.error, 'error')
    return
  }

  toast.add('✅ ¡Acta de sorteo en vivo oficializada y guardada en el sistema!', 'success')
  emit('winner-confirmed', res.giveaway)
}

// Notificar ganador oficial por WhatsApp
const notifyWinnerWhatsApp = (winner) => {
  if (!selectedGiveaway.value || !winner) return
  const cleanPhone = String(winner.phone || '').replace(/[^0-9]/g, '')
  const msg = `🏆 *¡FELICITACIONES ${winner.participantName.toUpperCase()}!* 🎉\n\n` +
    `Resultaste *GANADOR OFICIAL* en el sorteo en vivo de *StreamePro*:\n` +
    `🎁 *Premio:* ${selectedGiveaway.value.prizeTitle}\n` +
    `🎟️ *Tu Número Ganador de 6 Dígitos:* *#${winner.luckyNumber}*\n` +
    `📅 *Fecha de Sorteo:* ${winner.formattedDate || new Date().toLocaleString('es-AR')}\n\n` +
    `Por favor confirma tu recepción respondiendo a este mensaje para coordinar la entrega oficial.`
  window.open(`https://wa.me/${cleanPhone}?text=${encodeURIComponent(msg)}`, '_blank')
}

// Copiar acta oficial o número
const copyVerificationProof = async () => {
  if (!currentWinner.value || !selectedGiveaway.value) return
  const text = `📜 *ACTA OFICIAL DE SORTEO EN VIVO — STREAMEPRO*\n` +
    `🏆 Sorteo: ${selectedGiveaway.value.title}\n` +
    `🎁 Premio: ${selectedGiveaway.value.prizeTitle}\n` +
    `🎟️ Número Ganador de 6 Dígitos: #${currentWinner.value.luckyNumber}\n` +
    `👤 Ganador: ${currentWinner.value.participantName} (${currentWinner.value.maskedPhone})\n` +
    `📅 Fecha: ${currentWinner.value.formattedDate || new Date().toLocaleString('es-AR')}\n` +
    `🔐 Verificación: SP-ACTA-${Date.now().toString(36).toUpperCase()}`
  try {
    await navigator.clipboard.writeText(text)
    toast.add('📋 Acta oficial copiada al portapapeles', 'success')
  } catch {
    toast.add('No se pudo copiar el acta', 'info')
  }
}

onUnmounted(() => {
  if (confettiInterval) clearInterval(confettiInterval)
})
</script>

<template>
  <div
    v-if="show"
    class="fixed inset-0 z-[95] flex items-center justify-center p-2 sm:p-4 bg-black/90 backdrop-blur-xl animate-in fade-in duration-200 overflow-y-auto"
  >
    <div
      :class="[
        'bg-gradient-to-b from-[#050b1a] via-[#081226] to-[#030712] border border-fuchsia-500/40 rounded-3xl shadow-2xl text-white flex flex-col my-auto transition-all relative overflow-hidden',
        isFullscreen ? 'w-full h-full max-w-none rounded-none' : 'w-full max-w-2xl max-h-[96vh]'
      ]"
    >
      <!-- Canvas de confeti flotante -->
      <canvas ref="confettiCanvas" class="absolute inset-0 pointer-events-none z-30 w-full h-full"></canvas>

      <!-- Encabezado de Transmisión en Vivo -->
      <div class="px-5 py-4 border-b border-fuchsia-500/30 flex items-center justify-between bg-slate-950/80 shrink-0 relative z-20">
        <div class="flex items-center gap-2.5">
          <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-rose-600/30 border border-rose-500 text-rose-300 font-mono text-[10px] font-black uppercase tracking-wider animate-pulse">
            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
            <span>EN VIVO</span>
          </div>
          <div>
            <h3 class="text-sm sm:text-base font-black text-white flex items-center gap-1.5">
              <span>🎰 Sorteador Oficial de 6 Dígitos</span>
            </h3>
            <p class="text-[11px] text-slate-400">Bolillero digital transparente para sorteos en directo</p>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <button
            type="button"
            @click="soundEnabled = !soundEnabled"
            :class="['w-8 h-8 rounded-xl border flex items-center justify-center text-xs font-bold transition cursor-pointer', soundEnabled ? 'bg-fuchsia-500/20 border-fuchsia-400 text-fuchsia-300' : 'bg-slate-800 border-slate-700 text-slate-400']"
            :title="soundEnabled ? 'Silenciar efectos' : 'Activar sonido'"
          >
            {{ soundEnabled ? '🔊' : '🔇' }}
          </button>
          <button
            type="button"
            @click="isFullscreen = !isFullscreen"
            class="w-8 h-8 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 flex items-center justify-center text-xs font-bold cursor-pointer"
            title="Pantalla completa"
          >
            {{ isFullscreen ? '🗗' : '⛶' }}
          </button>
          <button
            type="button"
            @click="emit('close')"
            class="w-8 h-8 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 hover:text-white flex items-center justify-center text-sm font-bold cursor-pointer"
          >
            ✕
          </button>
        </div>
      </div>

      <!-- Cuerpo Principal Scrolleable -->
      <div class="p-4 sm:p-6 overflow-y-auto space-y-5 flex-1 relative z-20">

        <!-- Selector de Modo de Sorteo -->
        <div class="flex rounded-2xl bg-slate-950 p-1 border border-slate-800">
          <button
            type="button"
            @click="drawMode = 'official'"
            :class="['flex-1 py-2 px-3 rounded-xl text-xs font-black transition cursor-pointer flex items-center justify-center gap-1.5', drawMode === 'official' ? 'bg-gradient-to-r from-fuchsia-600 to-indigo-600 text-white shadow-lg' : 'text-slate-400 hover:text-white']"
          >
            <span>🎯 Sorteo Oficial (con Participantes)</span>
          </button>
          <button
            type="button"
            @click="drawMode = 'free'"
            :class="['flex-1 py-2 px-3 rounded-xl text-xs font-black transition cursor-pointer flex items-center justify-center gap-1.5', drawMode === 'free' ? 'bg-gradient-to-r from-amber-500 to-orange-500 text-slate-950 shadow-lg' : 'text-slate-400 hover:text-white']"
          >
            <span>🎲 Bolillero Libre (6 Dígitos)</span>
          </button>
        </div>

        <!-- MODO OFICIAL: Ficha del Sorteo Seleccionado -->
        <div v-if="drawMode === 'official'" class="space-y-3">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <label class="text-[11px] font-black uppercase text-fuchsia-300 flex items-center gap-1.5">
              <span>🏆 Seleccionar Sorteo de Producción:</span>
            </label>
            <span v-if="officialSummary" class="text-[11px] font-mono text-emerald-400 bg-emerald-950/80 border border-emerald-500/40 px-2 py-0.5 rounded-full">
              🎟️ {{ officialSummary.allLuckyNumbers.length }} números de 6 dígitos cargados
            </span>
          </div>

          <select
            v-model="selectedGiveawayId"
            class="w-full bg-slate-900 border border-fuchsia-500/40 rounded-xl p-2.5 text-xs text-white font-bold outline-none"
          >
            <option v-for="g in adminStore.giveaways" :key="g.id" :value="g.id">
              {{ g.title }} — Premio: {{ g.prizeTitle }} ({{ g.status === 'active' ? '🟢 Activo' : 'Cerrado' }})
            </option>
          </select>

          <!-- Banner Resumen del Premio Oficial -->
          <div v-if="selectedGiveaway" class="bg-gradient-to-r from-fuchsia-950/40 via-purple-950/30 to-slate-950 border border-fuchsia-500/30 rounded-2xl p-3.5 flex items-center justify-between gap-3">
            <div class="space-y-0.5 min-w-0">
              <span class="text-[9px] uppercase font-black text-fuchsia-300 font-mono">Premio Principal</span>
              <h4 class="text-sm font-black text-white truncate">{{ selectedGiveaway.prizeTitle }}</h4>
              <p class="text-[10px] text-slate-400">
                Fecha pactada: <strong class="text-white">{{ selectedGiveaway.drawDateLabel || selectedGiveaway.endDate }}</strong>
              </p>
            </div>
            <div v-if="selectedGiveaway.prizeImage" class="shrink-0">
              <img :src="selectedGiveaway.prizeImage" :alt="selectedGiveaway.prizeTitle" class="w-12 h-12 rounded-xl object-cover border border-white/20 bg-black">
            </div>
          </div>
        </div>

        <!-- MODO LIBRE: Explicación -->
        <div v-else class="bg-amber-950/20 border border-amber-500/30 rounded-2xl p-3 text-xs text-amber-200 flex items-center gap-2">
          <span class="text-xl">🎰</span>
          <p>
            Modo <strong>Bolillero Libre</strong>: genera números aleatorios únicos de 6 cifras (desde <code>100000</code> hasta <code>999999</code>) para dinámicas en vivo espontáneas o trivias.
          </p>
        </div>

        <!-- ============================================================== -->
        <!-- TAMBOR DE 6 RODILLOS DIGITALES EN VIVO                         -->
        <!-- ============================================================== -->
        <div class="bg-slate-950 rounded-3xl border-2 border-fuchsia-500/50 p-4 sm:p-6 shadow-[0_0_50px_rgba(217,70,239,0.25)] space-y-4">
          <div class="text-center space-y-1">
            <span class="text-[10px] font-mono font-black uppercase text-fuchsia-300 tracking-widest block">
              {{ isRolling ? '⚡ GIRANDO BOLILLERO EN VIVO...' : (winningNumber ? '🎉 ¡NÚMERO SORTEADO!' : 'BOLILLERO OFICIAL 6 DÍGITOS') }}
            </span>
          </div>

          <!-- Los 6 Dígitos Flotantes con Animación Neon -->
          <div class="grid grid-cols-6 gap-2 sm:gap-3 py-2">
            <div
              v-for="(digit, idx) in reels"
              :key="idx"
              :class="[
                'aspect-[3/4] rounded-2xl flex flex-col items-center justify-center relative overflow-hidden transition-all shadow-inner border-2 select-none',
                winningNumber
                  ? 'bg-gradient-to-b from-amber-500/30 to-amber-950/90 border-amber-400 shadow-[0_0_20px_rgba(251,191,36,0.5)] scale-105'
                  : isRolling
                    ? 'bg-gradient-to-b from-fuchsia-950/80 to-slate-950 border-fuchsia-400 animate-pulse'
                    : 'bg-slate-900 border-slate-700 text-slate-200'
              ]"
            >
              <!-- Reflejo brillante del tambor -->
              <div class="absolute inset-x-0 top-0 h-1/2 bg-gradient-to-b from-white/15 to-transparent pointer-events-none"></div>

              <span
                :class="[
                  'font-black font-mono tracking-tighter leading-none',
                  winningNumber ? 'text-amber-300 text-3xl sm:text-5xl drop-shadow-[0_0_12px_rgba(251,191,36,0.8)]' : 'text-white text-3xl sm:text-5xl'
                ]"
              >
                {{ digit }}
              </span>

              <span class="text-[8px] font-mono text-slate-500 absolute bottom-1 uppercase">D{{ idx + 1 }}</span>
            </div>
          </div>

          <!-- Botón de Giro de Alto Impacto -->
          <button
            type="button"
            @click="startDraw"
            :disabled="isRolling"
            :class="[
              'w-full py-4 rounded-2xl font-black text-sm uppercase tracking-wider transition-all shadow-2xl flex items-center justify-center gap-2 cursor-pointer',
              isRolling
                ? 'bg-slate-800 text-slate-400 cursor-not-allowed opacity-80'
                : 'bg-gradient-to-r from-fuchsia-600 via-pink-600 to-indigo-600 hover:from-fuchsia-500 hover:to-indigo-500 text-white shadow-[0_0_30px_rgba(217,70,239,0.4)] active:scale-98'
            ]"
          >
            <span class="text-lg">{{ isRolling ? '⏳' : '🎰' }}</span>
            <span>{{ isRolling ? 'GIRANDO EN VIVO...' : (winningNumber ? 'SORTEAR OTRO NÚMERO / SUPLENTE' : '¡GIRAR Y SORTEAR NÚMERO DE 6 DÍGITOS!') }}</span>
          </button>
        </div>

        <!-- ============================================================== -->
        <!-- FICHA DEL GANADOR TITULAR DETECTADO                            -->
        <!-- ============================================================== -->
        <div
          v-if="currentWinner && drawMode === 'official'"
          class="bg-gradient-to-br from-amber-950/60 via-slate-950 to-slate-950 border-2 border-amber-400/80 rounded-3xl p-5 shadow-2xl space-y-4 animate-in zoom-in-95 duration-300"
        >
          <div class="flex items-center justify-between border-b border-amber-500/30 pb-3">
            <div class="flex items-center gap-2">
              <span class="text-2xl">🏆</span>
              <div>
                <span class="text-[9px] font-mono font-black uppercase text-amber-300 tracking-wider">Ganador Oficial Verificado</span>
                <h4 class="text-lg font-black text-white leading-none">{{ currentWinner.participantName }}</h4>
              </div>
            </div>
            <div class="text-right">
              <span class="text-[9px] text-slate-400 block font-mono">Número Ganador</span>
              <strong class="text-xl font-black font-mono text-amber-300 bg-amber-500/20 px-3 py-1 rounded-xl border border-amber-400/40">
                #{{ currentWinner.luckyNumber }}
              </strong>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-2 text-xs">
            <div class="bg-slate-900/80 p-2.5 rounded-xl border border-slate-800">
              <span class="text-[10px] text-slate-400 block">WhatsApp Participante</span>
              <strong class="text-white font-mono">{{ currentWinner.maskedPhone }}</strong>
            </div>
            <div class="bg-slate-900/80 p-2.5 rounded-xl border border-slate-800">
              <span class="text-[10px] text-slate-400 block">Premio Asignado</span>
              <strong class="text-amber-300 truncate block">{{ selectedGiveaway?.prizeTitle }}</strong>
            </div>
          </div>

          <!-- Botonera de Oficialización y Contacto -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 pt-1">
            <button
              type="button"
              @click="notifyWinnerWhatsApp(currentWinner)"
              class="py-2.5 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs flex items-center justify-center gap-1.5 shadow transition cursor-pointer"
            >
              <span>💬</span>
              <span>Notificar WA</span>
            </button>
            <button
              type="button"
              @click="copyVerificationProof"
              class="py-2.5 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-sky-300 border border-sky-500/30 font-bold text-xs flex items-center justify-center gap-1.5 transition cursor-pointer"
            >
              <span>📜</span>
              <span>Copiar Acta</span>
            </button>
            <button
              type="button"
              @click="confirmWinnerInDatabase"
              class="py-2.5 px-3 rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-400 hover:to-orange-400 text-slate-950 font-black text-xs flex items-center justify-center gap-1.5 shadow transition cursor-pointer"
            >
              <span>💾</span>
              <span>Guardar Acta</span>
            </button>
          </div>
        </div>

        <!-- Lista de Suplentes Extra Sorteados -->
        <div v-if="drawnAlternates.length > 0 && drawMode === 'official'" class="space-y-2">
          <span class="text-[11px] font-black uppercase text-slate-400">🥈 Suplentes Oficiales Sorteados ({{ drawnAlternates.length }}):</span>
          <div class="space-y-1.5">
            <div
              v-for="(alt, idx) in drawnAlternates"
              :key="idx"
              class="flex items-center justify-between bg-slate-950/80 p-2.5 rounded-xl border border-slate-800 text-xs"
            >
              <div>
                <span class="text-slate-400 font-mono mr-1.5 font-bold">Suplente #{{ idx + 1 }}:</span>
                <strong class="text-white">{{ alt.participantName }}</strong>
                <span class="text-slate-400 ml-1 font-mono text-[11px]">({{ alt.maskedPhone }})</span>
              </div>
              <span class="font-mono font-black text-amber-300 bg-amber-500/15 px-2 py-0.5 rounded">
                #{{ alt.luckyNumber }}
              </span>
            </div>
          </div>
        </div>

        <!-- MODO LIBRE: Historial de Números de la Sesión -->
        <div v-if="drawMode === 'free' && drawnHistoryFree.length > 0" class="space-y-2">
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-black uppercase text-amber-300">📜 Historial de Números Generados:</span>
            <button type="button" @click="drawnHistoryFree = []" class="text-[10px] text-slate-500 hover:text-rose-400 cursor-pointer">Limpiar</button>
          </div>
          <div class="flex flex-wrap gap-2 max-h-36 overflow-y-auto custom-scrollbar">
            <div
              v-for="(h, idx) in drawnHistoryFree"
              :key="idx"
              class="px-3 py-1.5 rounded-xl bg-slate-950 border border-amber-500/30 flex items-center gap-2 font-mono text-xs"
            >
              <strong class="text-amber-300 font-black text-sm">#{{ h.number }}</strong>
              <span class="text-[10px] text-slate-500">{{ h.time }}</span>
            </div>
          </div>
        </div>

      </div>

      <!-- Footer simple -->
      <div class="px-5 py-3 border-t border-slate-800/80 flex items-center justify-between bg-slate-950/90 text-[11px] text-slate-400 shrink-0">
        <span class="flex items-center gap-1 font-mono">
          <span>🔒</span> Algoritmo Criptográfico Seguro WebCrypto (100.000 a 999.999)
        </span>
        <button
          type="button"
          @click="emit('close')"
          class="px-4 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs cursor-pointer"
        >
          Cerrar
        </button>
      </div>
    </div>
  </div>
</template>

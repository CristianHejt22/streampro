<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useAdminStore } from '../store/admin'
import { useCartStore } from '../store/cart'
import { useToastStore } from '../store/toast'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  initialTab: {
    type: String,
    default: 'create' // 'create' | 'redeem'
  },
  prefilledCode: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['update:modelValue', 'cardPurchased', 'cardRedeemed', 'openCheckout'])

const adminStore = useAdminStore()
const cartStore = useCartStore()
const toast = useToastStore()

const activeTab = ref(props.initialTab || 'create')

// Formulario de Creación / Compra
const selectedPreset = ref(30000)
const customAmount = ref('')
const senderName = ref('')
const recipientName = ref('')
const recipientPhone = ref('')
const giftMessage = ref('¡Feliz Cumpleaños! Disfruta este regalo oficial para lo que quieras en StreamePro.')
const selectedTheme = ref('neon') // 'neon' | 'luxury' | 'cyber' | 'crimson'

// Formulario de Canje
const redeemCodeInput = ref(props.prefilledCode || '')
const redeemPhoneInput = ref('')
const isRedeeming = ref(false)
const lastRedeemedResult = ref(null)

// Temas de la tarjeta visual
const cardThemes = [
  { id: 'neon', name: 'StreamePro Neon', bg: 'from-sky-900 via-blue-950 to-slate-950', border: 'border-sky-500/40', accent: 'text-sky-400', badge: 'bg-sky-500/20 text-sky-300' },
  { id: 'luxury', name: 'Gold Luxury', bg: 'from-amber-950 via-yellow-950 to-slate-950', border: 'border-amber-500/40', accent: 'text-amber-400', badge: 'bg-amber-500/20 text-amber-300' },
  { id: 'cyber', name: 'Cyber Gaming', bg: 'from-purple-950 via-indigo-950 to-slate-950', border: 'border-purple-500/40', accent: 'text-purple-400', badge: 'bg-purple-500/20 text-purple-300' },
  { id: 'crimson', name: 'Crimson VIP', bg: 'from-rose-950 via-red-950 to-slate-950', border: 'border-rose-500/40', accent: 'text-rose-400', badge: 'bg-rose-500/20 text-rose-300' }
]

const currentTheme = computed(() => {
  return cardThemes.find(t => t.id === selectedTheme.value) || cardThemes[0]
})

// Cargar perfil guardado para pre-completar datos si existen
const loadUserProfile = () => {
  try {
    const raw = localStorage.getItem('streampro_user_profile')
    if (raw) {
      const p = JSON.parse(raw)
      if (p.name && !senderName.value) senderName.value = p.name
      if (p.phone && !redeemPhoneInput.value) redeemPhoneInput.value = p.phone
    }
  } catch {}
}

onMounted(() => {
  loadUserProfile()
  if (props.prefilledCode) {
    redeemCodeInput.value = props.prefilledCode
    activeTab.value = 'redeem'
  }
})

watch(() => props.prefilledCode, (newCode) => {
  if (newCode) {
    redeemCodeInput.value = newCode
    activeTab.value = 'redeem'
  }
})

watch(() => props.initialTab, (t) => {
  if (t) activeTab.value = t
})

// Cálculo de montos y descuentos
const effectiveAmount = computed(() => {
  if (customAmount.value) {
    return Math.max(0, parseInt(String(customAmount.value).replace(/\D/g, ''), 10) || 0)
  }
  return selectedPreset.value || 20000
})

const discountCalculation = computed(() => {
  return adminStore.calculateGiftCardDiscount(effectiveAmount.value)
})

// Billetera del cliente si ingresó un teléfono
const currentCustomerWallet = computed(() => {
  if (!redeemPhoneInput.value) return null
  return adminStore.getCustomerWallet(redeemPhoneInput.value)
})

// Selección rápida de presets
const setPreset = (amt) => {
  selectedPreset.value = amt
  customAmount.value = ''
}

// Acción de agregar la Gift Card al Carrito
const addGiftCardToCart = () => {
  const calc = discountCalculation.value
  if (!calc.valid) {
    toast.add(calc.error || 'El monto mínimo es de $20.000 ARS', 'error')
    return
  }

  // Generar la tarjeta registrada
  const res = adminStore.createGiftCard({
    amount: calc.amount,
    senderName: senderName.value,
    recipientName: recipientName.value,
    recipientPhone: recipientPhone.value,
    message: giftMessage.value,
    theme: selectedTheme.value,
    createdBy: 'user'
  })

  if (!res.success) {
    toast.add(res.error || 'Error al generar la Tarjeta de Regalo', 'error')
    return
  }

  const card = res.card

  // Añadir al carrito como producto oficial de saldo
  cartStore.addToCart({
    id: card.id,
    itemType: 'giftcard',
    platform: 'StreamePro Gift Card',
    title: `Tarjeta de Regalo $${card.amount.toLocaleString('es-AR')} ARS`,
    name: `Tarjeta de Regalo StreamePro ($${card.amount.toLocaleString('es-AR')} Saldo)`,
    planKey: 'giftcard',
    planLabel: `Código: ${card.code} (${card.discountPercent}% BONIFICADO)`,
    clientPrice: card.price,
    providerCost: Math.round(card.amount * 0.8),
    resellerProfit: 0,
    quantity: 1,
    giftCardCode: card.code,
    giftCardAmount: card.amount,
    recipientName: card.recipientName,
    senderName: card.senderName
  })

  toast.add(`¡Tarjeta de Regalo ${card.code} añadida al carrito con ${card.discountPercent}% OFF!`, 'success')
  emit('cardPurchased', card)
  emit('update:modelValue', false)
  cartStore.openDrawer()
}

// Compartir tarjeta por WhatsApp
const shareCardViaWhatsApp = () => {
  const calc = discountCalculation.value
  const amountStr = calc.amount.toLocaleString('es-AR')
  const toStr = recipientName.value ? `para *${recipientName.value}*` : 'para ti'
  const fromStr = senderName.value ? `De parte de *${senderName.value}*` : 'De StreamePro Oficial'
  
  const text = `🎁 *TARJETA DE REGALO OFICIAL STREAMEPRO* 🎁\n\n` +
    `¡Hola! Te enviaron una Tarjeta de Regalo ${toStr} por *$${amountStr} ARS* de saldo canjeable.\n\n` +
    `✨ *Dedicatoria:* "${giftMessage.value}"\n` +
    `👤 ${fromStr}\n\n` +
    `💳 *Válida para:* Suscripciones 4K (Max, Disney+, Prime Video), Videojuegos Digitales PS5/PS4, Combos y Tienda Oficial.\n` +
    `🔗 *Canjéala directamente aquí:* https://streamepro.me/?giftcard=1\n\n` +
    `¡Que la disfrutes!`

  const targetWa = recipientPhone.value ? recipientPhone.value.replace(/\D/g, '') : ''
  const waUrl = targetWa 
    ? `https://wa.me/${targetWa}?text=${encodeURIComponent(text)}`
    : `https://wa.me/?text=${encodeURIComponent(text)}`

  window.open(waUrl, '_blank')
}

// Canjear tarjeta de regalo
const handleRedeem = () => {
  if (!redeemCodeInput.value.trim()) {
    toast.add('Ingresa el código de la Tarjeta de Regalo (Ej: GIFT-STP-XXXX-XXXX)', 'warning')
    return
  }
  if (!redeemPhoneInput.value.trim()) {
    toast.add('Ingresa tu WhatsApp o Número de Cliente para acreditar el saldo', 'warning')
    return
  }

  isRedeeming.value = true
  const res = adminStore.redeemGiftCard({
    code: redeemCodeInput.value.trim(),
    customerPhone: redeemPhoneInput.value.trim()
  })
  isRedeeming.value = false

  if (!res.success) {
    toast.add(res.error || 'No se pudo canjear la tarjeta', 'error')
    return
  }

  lastRedeemedResult.value = res
  toast.add(`🎉 ¡ÉXITO! Se acreditaron $${res.amount.toLocaleString('es-AR')} ARS a tu Billetera StreamePro`, 'success')
  emit('cardRedeemed', res)
}

const copyText = (txt, msg) => {
  if (!txt) return
  navigator.clipboard.writeText(txt).then(() => {
    toast.add(msg || 'Copiado al portapapeles', 'success')
  })
}

const closeModal = () => {
  emit('update:modelValue', false)
}

watch(() => props.modelValue, (isOpen) => {
  if (typeof document !== 'undefined') {
    document.body.style.overflow = isOpen ? 'hidden' : ''
  }
})

onUnmounted(() => {
  if (typeof document !== 'undefined') {
    document.body.style.overflow = ''
  }
})
</script>

<template>
  <div v-if="modelValue" class="fixed inset-0 z-[85] flex items-center justify-center p-3 sm:p-5 pt-16 sm:pt-6 pb-6 overflow-y-auto overscroll-contain bg-slate-950/85 backdrop-blur-md animate-fade-in">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl max-w-2xl w-full p-5 sm:p-7 shadow-2xl relative overflow-hidden space-y-6 max-h-[92vh] overflow-y-auto custom-scrollbar">
      
      <!-- Fondo decorativo -->
      <div class="absolute -right-16 -top-16 w-52 h-52 bg-sky-500/10 rounded-full blur-3xl pointer-events-none"></div>
      <div class="absolute -left-16 -bottom-16 w-52 h-52 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>

      <!-- Header del Modal -->
      <div class="flex justify-between items-start border-b border-slate-800/80 pb-4 relative z-10">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-amber-400 via-pink-500 to-indigo-600 flex items-center justify-center text-white text-2xl shadow-lg shadow-pink-500/20">
            🎁
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h3 class="text-xl sm:text-2xl font-black text-white">Tarjetas de Regalo Oficiales</h3>
              <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 font-bold border border-emerald-500/30">
                Hasta 20% OFF
              </span>
            </div>
            <p class="text-xs text-slate-400">Regala saldo en streaming, juegos PS5/PS4 y combos con bonificación escalonada</p>
          </div>
        </div>
        <button 
          @click="closeModal" 
          type="button" 
          class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-slate-800/90 hover:bg-slate-700 active:scale-95 text-slate-300 hover:text-white border border-slate-700/80 shadow-md flex items-center justify-center text-sm font-bold transition-all shrink-0 cursor-pointer ml-auto" 
          title="Cerrar ventana"
        >
          ✕
        </button>
      </div>

      <!-- Pestañas de Acción: Regalar vs Canjear -->
      <div class="flex rounded-2xl bg-slate-950 p-1.5 border border-slate-800 relative z-10">
        <button 
          @click="activeTab = 'create'" 
          :class="[
            'flex-1 py-2.5 px-3 rounded-xl text-xs font-black transition-all flex items-center justify-center gap-2 cursor-pointer',
            activeTab === 'create'
              ? 'bg-gradient-to-r from-sky-500 to-blue-600 text-white shadow-md'
              : 'text-slate-400 hover:text-white'
          ]"
        >
          <span>🎁</span>
          <span>Regalar / Comprar Tarjeta</span>
        </button>
        <button 
          @click="activeTab = 'redeem'" 
          :class="[
            'flex-1 py-2.5 px-3 rounded-xl text-xs font-black transition-all flex items-center justify-center gap-2 cursor-pointer',
            activeTab === 'redeem'
              ? 'bg-gradient-to-r from-emerald-500 to-teal-600 text-white shadow-md'
              : 'text-slate-400 hover:text-white'
          ]"
        >
          <span>💳</span>
          <span>Canjear a mi Billetera</span>
        </button>
      </div>

      <!-- ==================== TAB 1: REGALAR / COMPRAR GIFT CARD ==================== -->
      <div v-if="activeTab === 'create'" class="space-y-6 relative z-10">
        
        <!-- PREVIEW DE LA PLACA / TARJETA VISUAL VIP -->
        <div 
          :class="[
            'rounded-3xl p-5 sm:p-6 border relative overflow-hidden shadow-2xl transition-all duration-300 bg-gradient-to-br',
            currentTheme.bg,
            currentTheme.border
          ]"
        >
          <!-- Textura holográfica y chip digital -->
          <div class="flex justify-between items-start mb-6">
            <div class="flex items-center gap-3">
              <div class="w-10 h-7 rounded-md bg-gradient-to-tr from-amber-400 to-yellow-200 border border-amber-300 shadow-inner flex items-center justify-center opacity-90">
                <div class="w-7 h-4 border border-amber-600/50 rounded flex divide-x divide-amber-600/40">
                  <div class="w-1/2"></div>
                  <div class="w-1/2"></div>
                </div>
              </div>
              <span class="text-xs font-black tracking-widest text-white/90 uppercase">STREAMEPRO GIFT CARD</span>
            </div>
            <span :class="['text-[11px] font-mono font-bold px-2.5 py-1 rounded-full border shadow', currentTheme.badge]">
              {{ discountCalculation.discountPercent }}% BONIFICADO
            </span>
          </div>

          <!-- Monto Destacado -->
          <div class="mb-4">
            <span class="text-[10px] text-slate-300 uppercase tracking-widest font-bold block mb-0.5">Saldo Canjeable Oficial</span>
            <div class="text-3xl sm:text-4xl font-black text-white tracking-tight flex items-baseline gap-2">
              <span>${{ discountCalculation.amount.toLocaleString('es-AR') }}</span>
              <span class="text-sm font-medium text-slate-300">ARS</span>
            </div>
          </div>

          <!-- Mensaje y Remitente -->
          <div class="bg-black/40 backdrop-blur-sm rounded-xl p-3 border border-white/10 mb-4 space-y-1">
            <p class="text-xs text-slate-200 italic line-clamp-2">"{{ giftMessage || '¡Disfruta tu regalo oficial StreamePro!' }}"</p>
            <div class="flex justify-between items-center text-[11px] text-slate-400 pt-1">
              <span>Para: <b class="text-white">{{ recipientName || 'Destinatario' }}</b></span>
              <span>De: <b class="text-white">{{ senderName || 'StreamePro' }}</b></span>
            </div>
          </div>

          <!-- Footer de Tarjeta con Código de Muestra y Vigencia -->
          <div class="flex justify-between items-end border-t border-white/10 pt-3 text-[10px] font-mono text-slate-400">
            <div>
              <span class="block text-[9px] uppercase tracking-wider text-slate-500">Código de Canje Único</span>
              <span class="font-bold text-amber-300 tracking-wider">GIFT-STP-••••-••••</span>
            </div>
            <div class="text-right">
              <span class="block text-[9px] uppercase tracking-wider text-slate-500">Vigencia</span>
              <span class="text-slate-300">12 Meses Garantizados</span>
            </div>
          </div>
        </div>

        <!-- SELECTOR DE MONTO Y PRESETS -->
        <div class="space-y-3">
          <label class="block text-xs font-bold uppercase text-slate-300">1. Selecciona el Monto del Regalo (Mínimo $20.000)</label>
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
            <button 
              type="button"
              @click="setPreset(20000)"
              :class="[
                'p-3 rounded-2xl border text-center transition-all cursor-pointer flex flex-col items-center justify-center gap-0.5',
                selectedPreset === 20000 && !customAmount
                  ? 'bg-sky-500/20 border-sky-400 text-white font-black shadow-lg shadow-sky-500/10'
                  : 'bg-slate-950 border-slate-800 text-slate-300 hover:border-slate-700'
              ]"
            >
              <span class="text-sm font-black">$20.000</span>
              <span class="text-[10px] font-mono text-emerald-400 font-bold">5% OFF</span>
              <span class="text-[9px] text-slate-400">Pagas $19.000</span>
            </button>

            <button 
              type="button"
              @click="setPreset(30000)"
              :class="[
                'p-3 rounded-2xl border text-center transition-all cursor-pointer flex flex-col items-center justify-center gap-0.5',
                selectedPreset === 30000 && !customAmount
                  ? 'bg-sky-500/20 border-sky-400 text-white font-black shadow-lg shadow-sky-500/10'
                  : 'bg-slate-950 border-slate-800 text-slate-300 hover:border-slate-700'
              ]"
            >
              <span class="text-sm font-black">$30.000</span>
              <span class="text-[10px] font-mono text-emerald-400 font-bold">10% OFF</span>
              <span class="text-[9px] text-slate-400">Pagas $27.000</span>
            </button>

            <button 
              type="button"
              @click="setPreset(50000)"
              :class="[
                'p-3 rounded-2xl border text-center transition-all cursor-pointer flex flex-col items-center justify-center gap-0.5',
                selectedPreset === 50000 && !customAmount
                  ? 'bg-sky-500/20 border-sky-400 text-white font-black shadow-lg shadow-sky-500/10'
                  : 'bg-slate-950 border-slate-800 text-slate-300 hover:border-slate-700'
              ]"
            >
              <span class="text-sm font-black">$50.000</span>
              <span class="text-[10px] font-mono text-emerald-400 font-bold">15% OFF</span>
              <span class="text-[9px] text-slate-400">Pagas $42.500</span>
            </button>

            <button 
              type="button"
              @click="setPreset(100000)"
              :class="[
                'p-3 rounded-2xl border text-center transition-all cursor-pointer flex flex-col items-center justify-center gap-0.5',
                selectedPreset === 100000 && !customAmount
                  ? 'bg-amber-500/20 border-amber-400 text-white font-black shadow-lg shadow-amber-500/10'
                  : 'bg-slate-950 border-slate-800 text-slate-300 hover:border-slate-700'
              ]"
            >
              <span class="text-sm font-black">$100.000</span>
              <span class="text-[10px] font-mono text-amber-300 font-bold">20% OFF</span>
              <span class="text-[9px] text-slate-400">Pagas $80.000</span>
            </button>
          </div>

          <!-- O Monto Libre Manual -->
          <div class="mt-2">
            <label class="block text-[11px] font-bold text-slate-400 mb-1">O escribe un monto personalizado (mínimo $20.000):</label>
            <div class="relative">
              <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 font-bold text-sm">$</span>
              <input 
                v-model="customAmount" 
                type="number" 
                min="20000" 
                step="1000" 
                placeholder="Ej: 45000" 
                class="w-full bg-slate-950 border border-slate-800 rounded-xl pl-8 pr-28 py-3 text-white text-sm font-mono focus:border-sky-500 focus:outline-none"
              >
              <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-mono text-emerald-400 font-bold">
                {{ discountCalculation.discountPercent }}% OFF
              </span>
            </div>
          </div>
        </div>

        <!-- RESUMEN DE COMPRA Y AHORRO -->
        <div class="bg-slate-950/70 rounded-2xl p-4 border border-slate-800 space-y-2 text-xs">
          <div class="flex justify-between items-center text-slate-400">
            <span>Saldo real acreditado en la tarjeta:</span>
            <span class="font-bold font-mono text-white">${{ discountCalculation.amount.toLocaleString('es-AR') }} ARS</span>
          </div>
          <div class="flex justify-between items-center text-emerald-400">
            <span>Descuento aplicado ({{ discountCalculation.discountPercent }}% OFF):</span>
            <span class="font-bold font-mono">- ${{ discountCalculation.discountAmount.toLocaleString('es-AR') }} ARS</span>
          </div>
          <div class="flex justify-between items-center pt-2 border-t border-slate-800 text-sm">
            <span class="font-black text-white">Total a pagar:</span>
            <span class="font-black font-mono text-sky-400 text-base">${{ discountCalculation.priceToPay.toLocaleString('es-AR') }} ARS</span>
          </div>
        </div>

        <!-- DATOS DEL DESTINATARIO Y DEDICATORIA -->
        <div class="space-y-3">
          <label class="block text-xs font-bold uppercase text-slate-300">2. Personaliza la Tarjeta</label>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-[11px] text-slate-400 mb-1">Tu Nombre (Remitente)</label>
              <input 
                v-model="senderName" 
                type="text" 
                placeholder="Ej: Cristian Gómez" 
                class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white text-xs focus:border-sky-500 focus:outline-none"
              >
            </div>
            <div>
              <label class="block text-[11px] text-slate-400 mb-1">Nombre de quien lo recibe</label>
              <input 
                v-model="recipientName" 
                type="text" 
                placeholder="Ej: Martín Rodríguez" 
                class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white text-xs focus:border-sky-500 focus:outline-none"
              >
            </div>
          </div>

          <div>
            <label class="block text-[11px] text-slate-400 mb-1">WhatsApp del destinatario (Opcional, para enviarle directo)</label>
            <input 
              v-model="recipientPhone" 
              type="tel" 
              placeholder="Ej: +54 9 11 1234-5678" 
              class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white text-xs font-mono focus:border-sky-500 focus:outline-none"
            >
          </div>

          <div>
            <label class="block text-[11px] text-slate-400 mb-1">Mensaje o Dedicatoria Especial</label>
            <textarea 
              v-model="giftMessage" 
              rows="2" 
              maxlength="140"
              placeholder="¡Feliz cumpleaños! Que lo disfrutes mucho en tus juegos o series favoritas." 
              class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white text-xs focus:border-sky-500 focus:outline-none resize-none"
            ></textarea>
            <span class="text-[10px] text-slate-500 text-right block">{{ giftMessage.length }}/140 caracteres</span>
          </div>

          <!-- Selector de Estilo Visual -->
          <div>
            <label class="block text-[11px] text-slate-400 mb-1.5">Estilo Visual de la Placa:</label>
            <div class="flex flex-wrap gap-2">
              <button 
                v-for="theme in cardThemes" 
                :key="theme.id"
                type="button"
                @click="selectedTheme = theme.id"
                :class="[
                  'px-3 py-1.5 rounded-xl text-xs font-bold transition-all border cursor-pointer',
                  selectedTheme === theme.id 
                    ? 'border-white text-white bg-slate-800 shadow' 
                    : 'border-slate-800 text-slate-400 hover:text-white bg-slate-950'
                ]"
              >
                {{ theme.name }}
              </button>
            </div>
          </div>
        </div>

        <!-- BOTONES DE ACCIÓN: COMPRAR & COMPARTIR -->
        <div class="flex flex-col sm:flex-row gap-3 pt-2">
          <button 
            type="button"
            @click="shareCardViaWhatsApp"
            class="flex-1 bg-slate-800 hover:bg-slate-700 text-emerald-400 border border-emerald-500/30 font-bold py-3 px-4 rounded-xl text-xs transition-all flex items-center justify-center gap-2 cursor-pointer"
          >
            <span>📲</span>
            <span>Compartir por WhatsApp</span>
          </button>

          <button 
            type="button"
            @click="addGiftCardToCart"
            class="flex-1 bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-400 hover:to-blue-500 text-white font-black py-3 px-4 rounded-xl text-xs shadow-lg shadow-sky-500/20 transition-all flex items-center justify-center gap-2 cursor-pointer"
          >
            <span>🛒</span>
            <span>Añadir al Carrito (${{ discountCalculation.priceToPay.toLocaleString('es-AR') }} ARS)</span>
          </button>
        </div>

      </div>

      <!-- ==================== TAB 2: CANJEAR A MI BILLETERA ==================== -->
      <div v-else class="space-y-6 relative z-10">
        
        <!-- Banner de Billetera Actual -->
        <div class="bg-gradient-to-r from-emerald-950/50 via-teal-950/30 to-slate-900 border border-emerald-500/30 rounded-3xl p-5 sm:p-6 space-y-3">
          <div class="flex justify-between items-start">
            <div>
              <span class="text-xs font-bold uppercase tracking-wider text-emerald-400">Billetera Digital StreamePro</span>
              <h4 class="text-2xl sm:text-3xl font-black text-white mt-1">
                ${{ (currentCustomerWallet?.balance || 0).toLocaleString('es-AR') }} <span class="text-xs font-normal text-slate-300">ARS</span>
              </h4>
              <p class="text-[11px] text-slate-300 mt-1">Saldo acumulado disponible para pagar total o parcialmente cualquier pedido de la tienda.</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center text-emerald-400 text-2xl shadow">
              💳
            </div>
          </div>
        </div>

        <!-- FORMULARIO DE CANJE -->
        <div class="bg-slate-950/80 rounded-2xl p-5 border border-slate-800 space-y-4">
          <h5 class="text-xs font-black uppercase tracking-wider text-slate-300 flex items-center gap-2">
            <span>⚡️</span>
            <span>Ingresa el Código de tu Tarjeta de Regalo</span>
          </h5>

          <div>
            <label class="block text-[11px] text-slate-400 mb-1">Código de Gift Card *</label>
            <input 
              v-model="redeemCodeInput" 
              type="text" 
              placeholder="Ej: GIFT-STP-A7K2-9Q8X" 
              class="w-full bg-slate-900 border border-slate-700 rounded-xl p-3 text-amber-300 font-mono font-black text-sm tracking-wider uppercase focus:border-amber-400 focus:outline-none"
            >
          </div>

          <div>
            <label class="block text-[11px] text-slate-400 mb-1">Tu WhatsApp o N° de Cliente *</label>
            <input 
              v-model="redeemPhoneInput" 
              type="tel" 
              placeholder="Ej: +54 9 11 1234-5678 o CLI-9024" 
              class="w-full bg-slate-900 border border-slate-700 rounded-xl p-3 text-white font-mono text-sm focus:border-emerald-500 focus:outline-none"
            >
            <span class="text-[10px] text-slate-500 mt-1 block">El saldo se vinculará a este número para que lo utilices en tu Bóveda y en el Checkout.</span>
          </div>

          <button 
            type="button"
            :disabled="isRedeeming"
            @click="handleRedeem"
            class="w-full bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-slate-950 font-black py-3.5 rounded-xl text-xs shadow-lg shadow-emerald-500/20 transition-all flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50"
          >
            <span>✨</span>
            <span>{{ isRedeeming ? 'Validando código...' : 'Canjear y Acreditar Saldo Inmediatamente' }}</span>
          </button>
        </div>

        <!-- RESULTADO DE CANJE RECIENTE -->
        <div v-if="lastRedeemedResult" class="p-4 rounded-2xl bg-emerald-950/40 border border-emerald-500/40 space-y-2">
          <div class="flex items-center gap-2 text-emerald-400 font-black text-sm">
            <span>🎉</span>
            <span>¡Canje Exitoso! Saldo acreditado</span>
          </div>
          <p class="text-xs text-slate-200">
            Se sumaron <b class="text-white">${{ lastRedeemedResult.amount.toLocaleString('es-AR') }} ARS</b> a tu Billetera.
            Tu nuevo saldo disponible es de <b class="text-emerald-400 font-mono">${{ lastRedeemedResult.newBalance.toLocaleString('es-AR') }} ARS</b>.
          </p>
          <div class="pt-2">
            <button 
              @click="closeModal" 
              class="px-4 py-2 rounded-xl bg-emerald-500 text-slate-950 font-bold text-xs hover:bg-emerald-400 cursor-pointer"
            >
              Comenzar a Comprar con mi Saldo →
            </button>
          </div>
        </div>

        <!-- HISTORIAL DE MOVIMIENTOS RECIENTES DE LA BILLETERA -->
        <div v-if="currentCustomerWallet && currentCustomerWallet.transactions && currentCustomerWallet.transactions.length > 0" class="space-y-2">
          <h6 class="text-xs font-bold uppercase text-slate-400">Movimientos de Billetera</h6>
          <div class="space-y-1.5 max-h-40 overflow-y-auto pr-1">
            <div 
              v-for="tx in currentCustomerWallet.transactions.slice(0, 5)" 
              :key="tx.id" 
              class="p-2.5 rounded-xl bg-slate-950 border border-slate-800 flex justify-between items-center text-xs"
            >
              <div>
                <span class="font-bold text-white block">{{ tx.description }}</span>
                <span class="text-[10px] text-slate-500 font-mono">{{ new Date(tx.date).toLocaleDateString('es-AR') }}</span>
              </div>
              <span 
                class="font-mono font-bold text-xs"
                :class="tx.type === 'credit' ? 'text-emerald-400' : 'text-rose-400'"
              >
                {{ tx.type === 'credit' ? '+' : '-' }} ${{ tx.amount.toLocaleString('es-AR') }}
              </span>
            </div>
          </div>
        </div>

      </div>

    </div>
  </div>
</template>

<style scoped>
.animate-fade-in {
  animation: fadeIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes fadeIn {
  from { opacity: 0; transform: scale(0.97); }
  to { opacity: 1; transform: scale(1); }
}
</style>

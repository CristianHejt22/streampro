<script setup>
import { ref, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useToastStore } from '../store/toast'
import { useCartStore } from '../store/cart'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  initialTab: {
    type: String,
    default: 'calculator' // 'calculator' | 'guide' | 'comparison' | 'warranty'
  },
  preselectedGame: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['update:modelValue', 'applyFilter'])

const router = useRouter()
const toast = useToastStore()
const cartStore = useCartStore()

const activeTab = ref(props.initialTab || 'calculator')

watch(() => props.initialTab, (newTab) => {
  if (newTab) activeTab.value = newTab
})

watch(() => props.modelValue, (isOpen) => {
  if (typeof document !== 'undefined') {
    document.body.style.overflow = isOpen ? 'hidden' : ''
  }
})

const closeModal = () => {
  emit('update:modelValue', false)
}

// ==========================================
// 1. CALCULADORA DE AHORRO GAMER
// ==========================================
const gamePresets = [
  {
    id: 'aaa_new',
    title: 'Estreno AAA Reciente (FC 25, Call of Duty, etc.)',
    usdOfficial: 70,
    officialArsWithTax: 128000,
    primaryPrice: 63600,
    secondaryPrice: 34000,
    tag: '70 USD Oficial + 60% Imp.'
  },
  {
    id: 'standard',
    title: 'Juego Estándar (God of War, Spider-Man 2, etc.)',
    usdOfficial: 60,
    officialArsWithTax: 110000,
    primaryPrice: 52000,
    secondaryPrice: 28000,
    tag: '60 USD Oficial + 60% Imp.'
  },
  {
    id: 'deluxe',
    title: 'Edición Deluxe / Gold (con Pase de Temporada)',
    usdOfficial: 90,
    officialArsWithTax: 165000,
    primaryPrice: 78000,
    secondaryPrice: 42000,
    tag: '90 USD Oficial + 60% Imp.'
  },
  {
    id: 'classic',
    title: 'Clásico / Indie Destacado',
    usdOfficial: 30,
    officialArsWithTax: 55000,
    primaryPrice: 26000,
    secondaryPrice: 15000,
    tag: '30 USD Oficial + 60% Imp.'
  }
]

const selectedPreset = ref(gamePresets[0])
const selectedLicenseOption = ref('primary') // 'primary' | 'secondary'

const currentStorePrice = computed(() => {
  return selectedLicenseOption.value === 'primary' 
    ? selectedPreset.value.primaryPrice 
    : selectedPreset.value.secondaryPrice
})

const currentSavings = computed(() => {
  return Math.max(0, selectedPreset.value.officialArsWithTax - currentStorePrice.value)
})

const currentSavingsPercentage = computed(() => {
  return Math.round((currentSavings.value / selectedPreset.value.officialArsWithTax) * 100)
})

const shareGamerSavingWhatsApp = () => {
  const text = `🎮 *CALCULADORA GAMER STREAMEPRO*\n` +
    `🔥 *Juego:* ${selectedPreset.value.title}\n` +
    `💳 *Precio Oficial PS Store (c/ 60% recargo bancario):* $${selectedPreset.value.officialArsWithTax.toLocaleString('es-AR')} ARS\n` +
    `⚡ *Precio StreamePro (${selectedLicenseOption.value === 'primary' ? 'Cuenta Primaria' : 'Cuenta Secundaria'}):* $${currentStorePrice.value.toLocaleString('es-AR')} ARS\n` +
    `🎉 *¡Ahorro Real:* $${currentSavings.value.toLocaleString('es-AR')} ARS (-${currentSavingsPercentage.value}% de ahorro directo)\n` +
    `🛡️ 100% original, garantía de por vida anti-candado y entrega en 60-90 min.\n` +
    `👉 Ver catálogo oficial: https://streamepro.me/juegos`

  window.open(`https://wa.me/?text=${encodeURIComponent(text)}`, '_blank')
}

// ==========================================
// 2. GUÍA DE ACTIVACIÓN OFICIAL
// ==========================================
const guideConsole = ref('PS5') // 'PS5' | 'PS4'

const ps5Steps = [
  {
    step: '1',
    title: 'Crear Nuevo Usuario',
    desc: 'En tu consola PS5, ve a la esquina superior derecha, selecciona tu perfil actual y presiona "Cambiar de usuario" > "Agregar usuario" > "Comenzar" (Acepta el acuerdo de licencia).'
  },
  {
    step: '2',
    title: 'Iniciar Sesión con los Datos Oficiales',
    desc: 'Introduce el Correo y la Contraseña que te entregamos. Si solicita código de verificación en 2 pasos, nuestro operador te lo envía de inmediato por WhatsApp.'
  },
  {
    step: '3',
    title: 'Activar Uso Compartido y Juego Offline',
    desc: 'Durante la configuración o en Ajustes > Usuarios y Cuentas > Otros > "Uso compartido de consola y juego offline", selecciona "ACTIVAR / HABILITAR".'
  },
  {
    step: '4',
    title: 'Descargar el Juego',
    desc: 'Ve a la "Biblioteca de Juegos" > pestaña "Tu colección", busca el juego comprado y presiona "Descargar".'
  },
  {
    step: '5',
    title: 'Cambiar a tu Usuario de Siempre',
    desc: 'Vuelve a cambiar al perfil con el que juegas habitualmente. Podrás jugar al juego completo, en multijugador online y ganando tus propios trofeos.'
  }
]

const ps4Steps = [
  {
    step: '1',
    title: 'Crear Nuevo Usuario',
    desc: 'Enciende tu PS4, en la pantalla inicial selecciona "Usuario nuevo" > "Crear un usuario" (Acepta los términos).'
  },
  {
    step: '2',
    title: 'Iniciar Sesión en PlayStation Network',
    desc: 'Coloca el Correo y Clave provistos por StreamePro. Si pide verificación, el operador te la proporciona al instante.'
  },
  {
    step: '3',
    title: 'Activar como PS4 Principal',
    desc: 'Si el sistema pregunta "¿Desea activar esta PS4 como su PS4 principal?", selecciona "ACTIVAR". También puedes verificarlo en Ajustes > Administración de cuentas > "Activar como PS4 principal".'
  },
  {
    step: '4',
    title: 'Poner a Descargar',
    desc: 'Dirígete a la "Biblioteca" > pestaña "Comprado", selecciona el juego y presiona "Descargar".'
  },
  {
    step: '5',
    title: 'Jugar desde tu Cuenta Personal',
    desc: 'Cierra sesión de ese perfil y vuelve a tu usuario personal. Juegas con tus partidas, trofeos y conexión online intacta.'
  }
]

// ==========================================
// 3. COMPARATIVA DE LICENCIAS
// ==========================================
const licenseComparison = [
  { feature: '¿Juegas en tu usuario personal de siempre?', primary: '✅ Sí, siempre', secondary: '❌ Juegas en el usuario asignado' },
  { feature: '¿Acumulas tus propios trofeos en tu PSN ID?', primary: '✅ Sí, en tu perfil', secondary: '❌ En el perfil del juego' },
  { feature: '¿Juego completo 100% original con actualizaciones?', primary: '✅ Sí, idéntico a disco/digital', secondary: '✅ Sí, idéntico a disco/digital' },
  { feature: '¿Multijugador online con amigos?', primary: '✅ Sí, con tu PS Plus', secondary: '✅ Sí, con el Plus de tu consola' },
  { feature: '¿Funciona sin conexión a internet (Offline)?', primary: '✅ Sí, no requiere Wi-Fi', secondary: '⚠️ Requiere conexión activa' },
  { feature: 'Calidad gráfica y rendimiento (4K / 60 FPS / SSD)', primary: '✅ Máximo rendimiento', secondary: '✅ Máximo rendimiento' },
  { feature: 'Nivel de Ahorro vs PlayStation Store Oficial', primary: '🔥 40% a 55% de ahorro', secondary: '⚡ Hasta 70% de ahorro directo' }
]

const copyGuideWhatsApp = () => {
  const steps = guideConsole.value === 'PS5' ? ps5Steps : ps4Steps
  const formatted = `📖 *GUÍA OFICIAL DE ACTIVACIÓN ${guideConsole.value} - STREAMEPRO*\n\n` +
    steps.map(s => `*Paso ${s.step}: ${s.title}*\n${s.desc}`).join('\n\n') +
    `\n\n🛡️ *Garantía permanente:* Nunca elimines el usuario creado de tu consola para mantener la licencia activa.`

  navigator.clipboard.writeText(formatted).then(() => {
    toast.add('Guía copiada al portapapeles', 'success')
  })
}
</script>

<template>
  <Transition name="fade">
    <div 
      v-if="modelValue" 
      class="fixed inset-0 z-[70] flex items-center justify-center p-2 sm:p-4 bg-slate-950/85 backdrop-blur-xl overflow-y-auto"
      @click.self="closeModal"
    >
      <div 
        class="relative w-full max-w-3xl my-auto rounded-3xl bg-slate-900/95 border border-indigo-500/40 shadow-[0_0_50px_rgba(99,102,241,0.25)] overflow-hidden flex flex-col max-h-[92vh]"
      >
        <!-- Top Neon Gamer Gradient Line -->
        <div class="absolute top-0 inset-x-0 h-1.5 bg-gradient-to-r from-blue-500 via-indigo-500 to-purple-500"></div>
        <div class="absolute -top-24 -right-24 w-60 h-60 bg-blue-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -left-24 w-60 h-60 bg-purple-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <!-- HEADER -->
        <div class="p-4 sm:p-6 border-b border-white/[0.08] flex items-center justify-between shrink-0 bg-slate-900/60 backdrop-blur-md">
          <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-indigo-500/30 via-blue-500/20 to-purple-500/30 border border-indigo-400/40 flex items-center justify-center text-2xl shadow-inner">
              🎮
            </div>
            <div>
              <div class="flex items-center gap-2">
                <h3 class="text-lg sm:text-2xl font-black text-white tracking-tight">Centro de Herramientas Gamer</h3>
                <span class="text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 font-mono">
                  PS5 / PS4
                </span>
              </div>
              <p class="text-xs text-slate-300 mt-0.5">Calculadora de ahorro oficial, guía de activación paso a paso y comparativa de licencias</p>
            </div>
          </div>

          <button 
            @click="closeModal" 
            class="w-9 h-9 rounded-xl bg-slate-800/80 hover:bg-slate-700 text-slate-400 hover:text-white transition-all flex items-center justify-center border border-white/10 cursor-pointer shadow-sm"
            aria-label="Cerrar modal"
          >
            ✕
          </button>
        </div>

        <!-- TABS DE NAVEGACIÓN GAMER -->
        <div class="px-4 sm:px-6 pt-3 pb-2 border-b border-white/[0.06] bg-slate-950/40 shrink-0 flex items-center gap-1.5 overflow-x-auto no-scrollbar">
          <button 
            type="button"
            @click="activeTab = 'calculator'"
            :class="[
              'px-3.5 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 shrink-0 cursor-pointer border',
              activeTab === 'calculator'
                ? 'bg-gradient-to-r from-indigo-500/30 to-blue-600/30 text-indigo-300 border-indigo-400/50 shadow-md font-black'
                : 'text-slate-400 hover:text-slate-200 border-transparent hover:bg-white/[0.04]'
            ]"
          >
            <span>🧮</span>
            <span>Calculadora vs PS Store</span>
            <span class="text-[9px] px-1.5 py-0.2 rounded-full bg-emerald-500/20 text-emerald-300 font-mono font-black border border-emerald-500/30">
              -60% OFF
            </span>
          </button>

          <button 
            type="button"
            @click="activeTab = 'guide'"
            :class="[
              'px-3.5 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 shrink-0 cursor-pointer border',
              activeTab === 'guide'
                ? 'bg-gradient-to-r from-sky-500/30 to-blue-600/30 text-sky-300 border-sky-400/50 shadow-md font-black'
                : 'text-slate-400 hover:text-slate-200 border-transparent hover:bg-white/[0.04]'
            ]"
          >
            <span>📖</span>
            <span>Guía de Activación</span>
          </button>

          <button 
            type="button"
            @click="activeTab = 'comparison'"
            :class="[
              'px-3.5 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 shrink-0 cursor-pointer border',
              activeTab === 'comparison'
                ? 'bg-gradient-to-r from-purple-500/30 to-pink-600/30 text-pink-300 border-purple-400/50 shadow-md font-black'
                : 'text-slate-400 hover:text-slate-200 border-transparent hover:bg-white/[0.04]'
            ]"
          >
            <span>⚖️</span>
            <span>Primaria vs Secundaria</span>
          </button>

          <button 
            type="button"
            @click="activeTab = 'warranty'"
            :class="[
              'px-3.5 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 shrink-0 cursor-pointer border',
              activeTab === 'warranty'
                ? 'bg-gradient-to-r from-amber-500/30 to-orange-600/30 text-amber-300 border-amber-400/50 shadow-md font-black'
                : 'text-slate-400 hover:text-slate-200 border-transparent hover:bg-white/[0.04]'
            ]"
          >
            <span>🛡️</span>
            <span>Garantía Anti-Candado</span>
          </button>
        </div>

        <!-- CUERPO PRINCIPAL -->
        <div class="p-4 sm:p-6 overflow-y-auto space-y-6">

          <!-- ========================================== -->
          <!-- TAB 1: CALCULADORA DE AHORRO GAMER -->
          <!-- ========================================== -->
          <div v-if="activeTab === 'calculator'" class="space-y-5">
            <div class="bg-indigo-950/40 border border-indigo-500/30 rounded-2xl p-4">
              <h4 class="text-sm sm:text-base font-black text-white flex items-center gap-2">
                <span>💡</span> ¿Cuánto pagas en PlayStation Store vs StreamePro?
              </h4>
              <p class="text-xs text-slate-300 mt-1 leading-relaxed">
                Al comprar juegos con tarjeta en PlayStation Store oficial, se aplican recargos bancarios del <strong>60% de impuestos</strong> en dólares. En StreamePro abonas en <strong>pesos argentinos finales sin cargos extra</strong>.
              </p>
            </div>

            <!-- Selector de Tipo de Juego -->
            <div>
              <label class="block text-xs font-bold text-slate-300 mb-2">Selecciona un tipo de juego de referencia:</label>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                <button
                  v-for="p in gamePresets"
                  :key="p.id"
                  type="button"
                  @click="selectedPreset = p"
                  :class="[
                    'p-3 rounded-2xl border text-left transition cursor-pointer flex flex-col justify-between',
                    selectedPreset.id === p.id 
                      ? 'bg-indigo-500/20 border-indigo-400 text-white shadow-md' 
                      : 'bg-slate-950/60 border-white/[0.06] text-slate-400 hover:text-white'
                  ]"
                >
                  <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-black">{{ p.title }}</span>
                    <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-slate-900 border border-white/10 text-amber-300">{{ p.tag }}</span>
                  </div>
                  <div class="text-[11px] text-slate-400 font-mono mt-1">
                    PS Store: <span class="line-through text-red-400">${{ p.officialArsWithTax.toLocaleString('es-AR') }}</span>
                  </div>
                </button>
              </div>
            </div>

            <!-- Selector de Modalidad (Primaria vs Secundaria) -->
            <div class="p-3.5 rounded-2xl bg-slate-950/80 border border-white/[0.08] flex items-center justify-between gap-3">
              <span class="text-xs font-bold text-slate-300">Modalidad de cuenta a comparar:</span>
              <div class="flex items-center gap-1.5 p-1 bg-slate-900 rounded-xl border border-white/10">
                <button 
                  type="button" 
                  @click="selectedLicenseOption = 'primary'"
                  :class="['px-3 py-1 rounded-lg text-xs font-bold transition cursor-pointer', selectedLicenseOption === 'primary' ? 'bg-amber-400 text-slate-950 font-black' : 'text-slate-400 hover:text-white']"
                >
                  👑 Primaria
                </button>
                <button 
                  type="button" 
                  @click="selectedLicenseOption = 'secondary'"
                  :class="['px-3 py-1 rounded-lg text-xs font-bold transition cursor-pointer', selectedLicenseOption === 'secondary' ? 'bg-indigo-500 text-white font-black' : 'text-slate-400 hover:text-white']"
                >
                  ⚡ Secundaria
                </button>
              </div>
            </div>

            <!-- PANEL DE AHORRO CALCULADO -->
            <div class="rounded-3xl bg-gradient-to-br from-indigo-950/70 via-slate-950 to-purple-950/60 border border-indigo-400/50 p-5 shadow-2xl relative overflow-hidden">
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 text-center sm:text-left items-center">
                <div class="p-3 rounded-2xl bg-slate-900/80 border border-white/[0.06]">
                  <span class="text-[10px] uppercase font-bold text-slate-400">PlayStation Store Oficial</span>
                  <div class="text-xl font-black text-red-400 font-mono mt-0.5">
                    ${{ selectedPreset.officialArsWithTax.toLocaleString('es-AR') }}
                  </div>
                  <span class="text-[10px] text-slate-500">c/ 60% recargo bancario</span>
                </div>

                <div class="p-3 rounded-2xl bg-indigo-950/60 border border-indigo-500/40">
                  <span class="text-[10px] uppercase font-bold text-indigo-300">Precio StreamePro (Final)</span>
                  <div class="text-xl font-black text-indigo-300 font-mono mt-0.5">
                    ${{ currentStorePrice.toLocaleString('es-AR') }}
                  </div>
                  <span class="text-[10px] text-emerald-400 font-bold">0% cargos sorpresa</span>
                </div>

                <div class="p-3 rounded-2xl bg-emerald-950/60 border border-emerald-400/50 shadow-md">
                  <div class="flex items-center justify-between">
                    <span class="text-[10px] uppercase font-black text-emerald-300">¡Tu Ahorro Real!</span>
                    <span class="text-[10px] font-black px-1.5 py-0.2 rounded bg-emerald-400 text-slate-950 font-mono">
                      -{{ currentSavingsPercentage }}%
                    </span>
                  </div>
                  <div class="text-2xl font-black text-emerald-300 font-mono mt-0.5">
                    ${{ currentSavings.toLocaleString('es-AR') }}
                  </div>
                  <span class="text-[10px] text-amber-300 font-bold">Ahorras este monto en 1 compra</span>
                </div>
              </div>

              <div class="mt-4 pt-3 border-t border-white/[0.08] flex flex-wrap items-center justify-between gap-3">
                <span class="text-xs text-slate-300 flex items-center gap-1.5">
                  <span class="text-emerald-400">✓</span> Licencia 100% original permanente con garantía StreamePro
                </span>

                <button 
                  type="button" 
                  @click="shareGamerSavingWhatsApp"
                  class="px-4 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black text-xs transition flex items-center gap-1.5 shadow-md cursor-pointer"
                >
                  <span>💬</span>
                  <span>Compartir Presupuesto por WhatsApp</span>
                </button>
              </div>
            </div>
          </div>

          <!-- ========================================== -->
          <!-- TAB 2: GUÍA DE ACTIVACIÓN OFICIAL -->
          <!-- ========================================== -->
          <div v-if="activeTab === 'guide'" class="space-y-5">
            <!-- Consola Switcher -->
            <div class="flex items-center justify-between bg-slate-950/80 p-3 rounded-2xl border border-white/[0.08]">
              <div>
                <span class="text-xs font-black text-white">Selecciona tu consola:</span>
                <span class="text-[10px] text-slate-400 block">Paso a paso oficial ilustrado</span>
              </div>
              <div class="flex items-center gap-1.5">
                <button 
                  type="button"
                  @click="guideConsole = 'PS5'"
                  :class="['px-3 py-1.5 rounded-xl font-bold text-xs transition cursor-pointer', guideConsole === 'PS5' ? 'bg-blue-600 text-white font-black shadow' : 'bg-slate-900 text-slate-400 hover:text-white']"
                >
                  ⚡ PlayStation 5
                </button>
                <button 
                  type="button"
                  @click="guideConsole = 'PS4'"
                  :class="['px-3 py-1.5 rounded-xl font-bold text-xs transition cursor-pointer', guideConsole === 'PS4' ? 'bg-sky-600 text-white font-black shadow' : 'bg-slate-900 text-slate-400 hover:text-white']"
                >
                  🎮 PlayStation 4
                </button>
              </div>
            </div>

            <!-- Steps List -->
            <div class="space-y-3">
              <div 
                v-for="st in (guideConsole === 'PS5' ? ps5Steps : ps4Steps)" 
                :key="st.step"
                class="p-4 rounded-2xl bg-slate-950/70 border border-white/[0.06] flex items-start gap-3.5 hover:border-indigo-400/40 transition"
              >
                <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-indigo-500 to-blue-600 text-white font-black text-sm flex items-center justify-center shrink-0 shadow-md">
                  {{ st.step }}
                </div>
                <div>
                  <h5 class="text-xs sm:text-sm font-black text-white">{{ st.title }}</h5>
                  <p class="text-xs text-slate-300 mt-1 leading-relaxed">{{ st.desc }}</p>
                </div>
              </div>
            </div>

            <div class="p-3.5 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-between gap-3 text-xs">
              <div class="text-amber-200">
                <strong>💡 Regla de Oro:</strong> Nunca elimines el perfil añadido de tu consola para no perder la licencia del juego.
              </div>
              <button 
                type="button" 
                @click="copyGuideWhatsApp" 
                class="px-3 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-sky-300 text-xs font-bold border border-white/10 shrink-0 cursor-pointer"
              >
                Copiar Guía
              </button>
            </div>
          </div>

          <!-- ========================================== -->
          <!-- TAB 3: PRIMARIA VS SECUNDARIA -->
          <!-- ========================================== -->
          <div v-if="activeTab === 'comparison'" class="space-y-5">
            <div class="bg-purple-950/40 border border-purple-500/30 rounded-2xl p-4">
              <h4 class="text-sm sm:text-base font-black text-white flex items-center gap-2">
                <span>⚖️</span> Tabla Comparativa: Cuenta Primaria vs Cuenta Secundaria
              </h4>
              <p class="text-xs text-slate-300 mt-1">
                Conoce con total claridad técnica en qué se diferencia cada modalidad antes de elegir.
              </p>
            </div>

            <div class="overflow-x-auto rounded-2xl border border-white/[0.08] bg-slate-950/70">
              <table class="w-full text-left border-collapse text-xs">
                <thead>
                  <tr class="border-b border-white/[0.08] bg-slate-900/80">
                    <th class="p-3 text-slate-400 font-bold uppercase tracking-wider text-[10px]">Característica</th>
                    <th class="p-3 text-amber-300 font-black uppercase tracking-wider text-[10px] bg-amber-500/10">👑 Cuenta Primaria</th>
                    <th class="p-3 text-indigo-300 font-black uppercase tracking-wider text-[10px] bg-indigo-500/10">⚡ Cuenta Secundaria</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-white/[0.04]">
                  <tr v-for="(row, idx) in licenseComparison" :key="idx" class="hover:bg-white/[0.02]">
                    <td class="p-3 text-slate-200 font-semibold">{{ row.feature }}</td>
                    <td class="p-3 text-slate-300 bg-amber-500/[0.02]">{{ row.primary }}</td>
                    <td class="p-3 text-slate-300 bg-indigo-500/[0.02]">{{ row.secondary }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 p-3.5 rounded-2xl bg-slate-900/80 border border-white/[0.06]">
              <span class="text-xs text-slate-300">
                ¿Aún tienes dudas de cuál elegir para tu consola?
              </span>
              <a 
                href="https://wa.me/5491136000000?text=Hola%20StreamePro,%20tengo%20dudas%20entre%20Primaria%20y%20Secundaria" 
                target="_blank" 
                class="px-4 py-1.5 rounded-xl bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 font-bold text-xs border border-emerald-500/30 cursor-pointer"
              >
                Consultar por WhatsApp
              </a>
            </div>
          </div>

          <!-- ========================================== -->
          <!-- TAB 4: GARANTÍA ANTI-CANDADO -->
          <!-- ========================================== -->
          <div v-if="activeTab === 'warranty'" class="space-y-5">
            <div class="bg-amber-950/40 border border-amber-500/30 rounded-2xl p-4 sm:p-5">
              <h4 class="text-base font-black text-white flex items-center gap-2">
                <span>🛡️</span> Garantía 100% Permanente Anti-Candado
              </h4>
              <p class="text-xs text-slate-300 mt-1">
                Todas nuestras licencias son adquiridas en PlayStation Network oficial. No utilizamos métodos temporales ni cuentas compartidas no autorizadas.
              </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
              <div class="p-4 rounded-2xl bg-slate-950/80 border border-white/[0.06] space-y-1.5">
                <span class="text-2xl">🔒</span>
                <h5 class="text-xs font-black text-white">Cero Candados Garantizados</h5>
                <p class="text-xs text-slate-300">La cuenta queda asociada de por vida a tu consola. Puedes reinstalar el juego cuando quieras.</p>
              </div>

              <div class="p-4 rounded-2xl bg-slate-950/80 border border-white/[0.06] space-y-1.5">
                <span class="text-2xl">⚡</span>
                <h5 class="text-xs font-black text-white">Entrega en 60 a 90 Minutos</h5>
                <p class="text-xs text-slate-300">Operadores dedicados de turno verifican tu pago y te entregan credenciales y código de verificación al instante.</p>
              </div>

              <div class="p-4 rounded-2xl bg-slate-950/80 border border-white/[0.06] space-y-1.5">
                <span class="text-2xl">🔄</span>
                <h5 class="text-xs font-black text-white">Reposición Inmediata</h5>
                <p class="text-xs text-slate-300">Si alguna vez sufres cualquier problema técnico, nuestro equipo de soporte te asiste o repone la cuenta sin costo.</p>
              </div>

              <div class="p-4 rounded-2xl bg-slate-950/80 border border-white/[0.06] space-y-1.5">
                <span class="text-2xl">🇦🇷</span>
                <h5 class="text-xs font-black text-white">Pesos Argentinos Finales</h5>
                <p class="text-xs text-slate-300">Abona por Transferencia Bancaria, Mercado Pago o Débito con el valor exacto que ves en pantalla.</p>
              </div>
            </div>
          </div>

        </div>

        <!-- FOOTER -->
        <div class="p-3.5 sm:p-4 border-t border-white/[0.08] bg-slate-950/80 flex items-center justify-between text-xs text-slate-400 shrink-0">
          <div class="flex items-center gap-2">
            <span class="text-emerald-400">✓</span>
            <span class="font-mono text-[11px]">StreamePro Gaming Suite Oficial</span>
          </div>

          <button 
            type="button"
            @click="closeModal"
            class="px-4 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white font-bold text-xs transition cursor-pointer"
          >
            Cerrar
          </button>
        </div>

      </div>
    </div>
  </Transition>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.25s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>

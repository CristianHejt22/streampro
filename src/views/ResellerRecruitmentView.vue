<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAdminStore } from '../store/admin'
import { useToastStore } from '../store/toast'

const router = useRouter()
const adminStore = useAdminStore()
const toast = useToastStore()

// Formulario de solicitud de revendedor
const applicationForm = ref({
  fullName: '',
  whatsapp: '',
  email: '',
  city: '',
  storeName: '',
  experience: 'Quiero empezar desde cero',
  notes: ''
})
const isSubmitting = ref(false)
const submittedApplication = ref(null)

const scrollToForm = () => {
  const el = document.getElementById('formulario-solicitud')
  if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }
}

const submitApplication = async () => {
  if (!applicationForm.value.fullName.trim()) {
    toast.add('Por favor ingresa tu nombre completo', 'error')
    return
  }
  if (!applicationForm.value.whatsapp.trim()) {
    toast.add('Por favor ingresa tu número de WhatsApp para enviarte tus credenciales', 'error')
    return
  }

  isSubmitting.value = true
  try {
    const res = adminStore.submitResellerApplication(applicationForm.value)
    if (res && res.success) {
      submittedApplication.value = res.application
      toast.add('¡Solicitud enviada con éxito! La revisaremos en breve.', 'success')
    }
  } catch (e) {
    toast.add('Hubo un inconveniente al enviar la solicitud', 'error')
  } finally {
    isSubmitting.value = false
  }
}

const resetForm = () => {
  submittedApplication.value = null
  applicationForm.value = {
    fullName: '',
    whatsapp: '',
    email: '',
    city: '',
    storeName: '',
    experience: 'Quiero empezar desde cero',
    notes: ''
  }
}

const contactPriorityWhatsApp = () => {
  const cleanPhone = providerWhatsApp.value.replace(/[^0-9]/g, '')
  const name = submittedApplication.value?.fullName || applicationForm.value.fullName || 'Revendedor'
  const text = `¡Hola! Acabo de enviar mi solicitud de registro como REVENDEDOR OFICIAL en la web de StreamePro a nombre de *${name}*. ¿Podrían habilitar mi panel prioritariamente?`
  window.open(`https://wa.me/${cleanPhone}?text=${encodeURIComponent(text)}`, '_blank')
}

// Calculadora interactiva de ganancias
const monthlyStreamingClients = ref(30)
const avgProfitPerAccount = ref(2500) // ARS promedio
const monthlyPhysicalSales = ref(5)
const avgProfitPerPhysical = ref(3500) // ARS promedio

const estimatedMonthlyStreamingProfit = computed(() => {
  return monthlyStreamingClients.value * avgProfitPerAccount.value
})

const estimatedMonthlyPhysicalProfit = computed(() => {
  return monthlyPhysicalSales.value * avgProfitPerPhysical.value
})

const totalEstimatedMonthlyProfit = computed(() => {
  return estimatedMonthlyStreamingProfit.value + estimatedMonthlyPhysicalProfit.value
})

// WhatsApp de contacto con el proveedor
const providerWhatsApp = computed(() => {
  return adminStore.providerBankInfo?.whatsapp || adminStore.adminAuth?.whatsapp || '+54 9 11 0000-0000'
})

const contactProviderWhatsApp = () => {
  const cleanPhone = providerWhatsApp.value.replace(/[^0-9]/g, '')
  const text = 
`¡Hola! Vi la propuesta en la web de StreamePro y quiero registrarme como REVENDEDOR OFICIAL para comenzar a vender streaming y tecnología. ¿Me brindarían información para habilitar mi panel?`
  window.open(`https://wa.me/${cleanPhone}?text=${encodeURIComponent(text)}`, '_blank')
}

// FAQ acordeón
const activeFaq = ref(null)
const toggleFaq = (idx) => {
  activeFaq.value = activeFaq.value === idx ? null : idx
}

const faqs = [
  {
    q: '¿Necesito experiencia previa o inversión inicial para empezar?',
    a: 'No requieres experiencia ni comprar stock por adelantado. Nosotros te damos acceso a tu panel de revendedor. Cuando un cliente te pide una cuenta o un producto, te transfiere a ti primero y tú despachas desde el panel quedándote con tu ganancia limpia.'
  },
  {
    q: '¿Cómo le entrego el servicio a mi cliente?',
    a: 'Desde tu panel de revendedor tienes un botón "Copiar para WhatsApp". Al presionarlo, se genera un mensaje formal con el correo de la cuenta, contraseña, el nombre de su perfil asignado y su PIN privado de 4 dígitos. Solo se lo envías por chat y listo.'
  },
  {
    q: '¿Qué pasa si una cuenta tiene alguna eventualidad o cambia la contraseña?',
    a: 'StreamePro cuenta con garantía total mayorista. Si un cliente reporta una novedad, el panel de administración central actualiza o estabiliza la cuenta y te notifica automáticamente con el estado [YA FUE AVISADO]. Tu reputación siempre está protegida.'
  },
  {
    q: '¿Cómo me pagan mis clientes?',
    a: 'Tus clientes te pagan directamente a tu cuenta bancaria personal (Mercado Pago, Cuenta DNI, Ualá, Banco Galicia, Santander, etc.). Puedes configurar tu CBU/Alias en tu panel para que figure en todos tus comprobantes.'
  },
  {
    q: '¿Puedo vender también los productos físicos de tecnología?',
    a: '¡Sí! Tienes acceso a más de 360 productos físicos (auriculares, smartwatches, cargadores, cortadoras de pelo, etc.) en modelo dropshipping. Ganas entre el 1% y el 3% de comisión por cada venta sin tener que empaquetar ni enviar nada; el centro de distribución se encarga del despacho.'
  }
]
</script>

<template>
  <div class="min-h-screen bg-[#020617] text-white flex flex-col selection:bg-amber-500 selection:text-slate-950">
    
    <!-- NAVBAR SUPERIOR -->
    <header class="border-b border-slate-800/80 bg-slate-950/80 backdrop-blur-md sticky top-0 z-40">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
        <div class="flex items-center gap-3 cursor-pointer" @click="router.push('/')">
          <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-amber-500 to-orange-500 flex items-center justify-center text-slate-950 font-black shadow-lg">
            ⚡
          </div>
          <div>
            <span class="font-black text-lg tracking-tight bg-gradient-to-r from-white via-slate-100 to-slate-400 bg-clip-text text-transparent">
              StreamePro
            </span>
            <span class="text-[10px] font-mono block text-amber-400 -mt-1 font-bold">RED DE DISTRIBUIDORES</span>
          </div>
        </div>

        <div class="flex items-center gap-3 text-xs">
          <button 
            @click="router.push('/')" 
            class="hidden sm:inline-block text-slate-400 hover:text-white font-bold px-3 py-1.5 transition-colors"
          >
            ← Volver a la Tienda
          </button>
          <button 
            @click="router.push('/crm')" 
            class="bg-slate-900 hover:bg-slate-800 text-slate-200 border border-slate-700 font-bold px-4 py-2 rounded-xl transition-all"
          >
            🔑 Ya soy Revendedor
          </button>
          <button 
            @click="scrollToForm" 
            class="bg-amber-400 hover:bg-amber-300 text-slate-950 font-black px-4 py-2 rounded-xl shadow-lg shadow-amber-500/20 transition-all flex items-center gap-1.5 cursor-pointer"
          >
            <span>📝 Solicitar Panel</span>
          </button>
        </div>
      </div>
    </header>

    <main class="flex-1 space-y-20 pb-20">
      
      <!-- HERO SECTION -->
      <section class="relative pt-12 sm:pt-20 pb-8 px-4 sm:px-6 max-w-5xl mx-auto text-center space-y-6">
        <div class="inline-flex items-center gap-2 bg-gradient-to-r from-amber-500/15 via-orange-500/15 to-amber-500/15 border border-amber-500/30 px-4 py-1.5 rounded-full text-xs font-mono font-bold text-amber-300 shadow-inner">
          <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
          <span>PROGRAMA DE DISTRIBUIDORES OFICIALES 2026</span>
        </div>

        <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black tracking-tight leading-tight sm:leading-none">
          Comienza tu Negocio de <br class="hidden sm:inline" />
          <span class="bg-gradient-to-r from-amber-400 via-orange-400 to-amber-200 bg-clip-text text-transparent">
            Streaming & Tecnología
          </span>
          <br />Sin Inversión en Stock
        </h1>

        <p class="text-sm sm:text-lg text-slate-300 max-w-2xl mx-auto font-normal leading-relaxed">
          Accede a precios mayoristas en <strong class="text-white">Netflix, Max, Disney+, IPTV, Spotify</strong> y más de 360 productos físicos. Entrega de 10 minutos a 1 hora con comprobante de pago, perfiles y PINs de seguridad, y cobra tus ganancias en el acto por WhatsApp.
        </p>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-4">
          <button 
            @click="scrollToForm" 
            class="w-full sm:w-auto bg-gradient-to-r from-amber-400 to-orange-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 font-black px-8 py-4 rounded-2xl shadow-xl shadow-amber-500/25 transition-all text-sm flex items-center justify-center gap-2 cursor-pointer"
          >
            <span>📝 Solicitar Panel de Revendedor</span>
            <span>→</span>
          </button>
          <a 
            href="#calculadora" 
            class="w-full sm:w-auto bg-slate-900/80 hover:bg-slate-800 text-slate-200 border border-slate-800 font-bold px-6 py-4 rounded-2xl transition-all text-sm flex items-center justify-center gap-2"
          >
            <span>📊 Calcular mis Ganancias</span>
          </a>
        </div>

        <!-- Métricas destacadas -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 pt-10 border-t border-slate-800/80 max-w-4xl mx-auto text-left">
          <div class="bg-slate-900/40 p-4 rounded-2xl border border-slate-800">
            <span class="text-2xl font-black text-amber-400 block">+40%</span>
            <span class="text-xs text-slate-400">Margen promedio por suscripción</span>
          </div>
          <div class="bg-slate-900/40 p-4 rounded-2xl border border-slate-800">
            <span class="text-2xl font-black text-emerald-400 block">&lt; 30 seg</span>
            <span class="text-xs text-slate-400">Tiempo de despacho con PIN</span>
          </div>
          <div class="bg-slate-900/40 p-4 rounded-2xl border border-slate-800">
            <span class="text-2xl font-black text-sky-400 block">+360</span>
            <span class="text-xs text-slate-400">Productos físicos en dropshipping</span>
          </div>
          <div class="bg-slate-900/40 p-4 rounded-2xl border border-slate-800">
            <span class="text-2xl font-black text-purple-400 block">100%</span>
            <span class="text-xs text-slate-400">Garantía directa ante caídas</span>
          </div>
        </div>
      </section>

      <!-- SECCIÓN: CALCULADORA INTERACTIVA DE GANANCIAS -->
      <section id="calculadora" class="max-w-4xl mx-auto px-4 sm:px-6">
        <div class="bg-gradient-to-b from-slate-900 via-slate-900/90 to-slate-950 p-6 sm:p-10 rounded-3xl border border-amber-500/30 shadow-2xl relative overflow-hidden">
          <div class="absolute -right-20 -top-20 w-64 h-64 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>

          <div class="text-center space-y-2 mb-8 relative z-10">
            <span class="text-xs font-mono font-bold uppercase tracking-wider text-amber-400 bg-amber-400/10 px-3 py-1 rounded-full border border-amber-400/20">
              SIMULADOR DE INGRESOS MENSUALES
            </span>
            <h2 class="text-2xl sm:text-3xl font-black text-white">¿Cuánto puedes ganar como Distribuidor?</h2>
            <p class="text-xs sm:text-sm text-slate-400">Ajusta los deslizadores según tus metas y descubre tu ganancia neta mensual estimada.</p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-8 relative z-10">
            <!-- Deslizadores -->
            <div class="space-y-6">
              <!-- Slider Clientes Streaming -->
              <div class="space-y-2">
                <div class="flex justify-between items-center text-xs">
                  <span class="font-bold text-slate-300">🍿 Clientes de Streaming al mes:</span>
                  <span class="font-mono font-black text-amber-400 text-sm">{{ monthlyStreamingClients }} clientes</span>
                </div>
                <input 
                  v-model.number="monthlyStreamingClients" 
                  type="range" 
                  min="5" 
                  max="150" 
                  step="5"
                  class="w-full accent-amber-400 cursor-pointer h-2 bg-slate-800 rounded-lg"
                >
                <div class="flex justify-between text-[10px] text-slate-500 font-mono">
                  <span>5 clientes</span>
                  <span>75 clientes</span>
                  <span>150 clientes</span>
                </div>
              </div>

              <!-- Slider Productos Físicos -->
              <div class="space-y-2">
                <div class="flex justify-between items-center text-xs">
                  <span class="font-bold text-slate-300">📦 Ventas de Tecnología (Dropshipping):</span>
                  <span class="font-mono font-black text-sky-400 text-sm">{{ monthlyPhysicalSales }} ventas</span>
                </div>
                <input 
                  v-model.number="monthlyPhysicalSales" 
                  type="range" 
                  min="0" 
                  max="40" 
                  step="1"
                  class="w-full accent-sky-400 cursor-pointer h-2 bg-slate-800 rounded-lg"
                >
                <div class="flex justify-between text-[10px] text-slate-500 font-mono">
                  <span>0 ventas</span>
                  <span>20 ventas</span>
                  <span>40 ventas</span>
                </div>
              </div>

              <div class="p-4 bg-slate-950 rounded-2xl border border-slate-800 text-[11px] text-slate-400 space-y-1">
                <p>💡 <strong class="text-slate-200">Ingreso Recurrente:</strong> El 85% de los clientes de streaming renuevan mes a mes. Esto significa que los clientes que sumas este mes te seguirán pagando el próximo.</p>
              </div>
            </div>

            <!-- Panel de Resultado -->
            <div class="bg-[#020617] rounded-2xl border border-slate-800 p-6 flex flex-col justify-between text-center space-y-4">
              <div>
                <span class="text-xs uppercase font-bold text-slate-400 tracking-wider">Tu Ganancia Neta Estimada</span>
                <div class="flex items-baseline justify-center gap-1 my-2">
                  <span class="text-2xl font-black text-emerald-400">$</span>
                  <span class="text-4xl sm:text-5xl font-black text-white tracking-tight">
                    {{ totalEstimatedMonthlyProfit.toLocaleString() }}
                  </span>
                  <span class="text-xs font-mono text-slate-400 font-bold">ARS/mes</span>
                </div>
                <span class="text-xs text-emerald-400 font-bold">Dinero directo a tu bolsillo</span>
              </div>

              <div class="space-y-2 border-t border-slate-800 pt-3 text-xs text-left font-mono">
                <div class="flex justify-between text-slate-400">
                  <span>Por Cuentas de Streaming:</span>
                  <strong class="text-slate-200">${{ estimatedMonthlyStreamingProfit.toLocaleString() }} ARS</strong>
                </div>
                <div class="flex justify-between text-slate-400">
                  <span>Por Comisiones de Tecnología:</span>
                  <strong class="text-slate-200">${{ estimatedMonthlyPhysicalProfit.toLocaleString() }} ARS</strong>
                </div>
              </div>

              <button 
                @click="scrollToForm" 
                class="w-full bg-amber-400 hover:bg-amber-300 text-slate-950 font-black py-3 rounded-xl shadow transition-all text-xs flex items-center justify-center gap-2 cursor-pointer"
              >
                <span>🚀 Solicitar mi Panel y Empezar a Ganar</span>
              </button>
            </div>
          </div>
        </div>
      </section>

      <!-- SECCIÓN: FORMULARIO DE SOLICITUD DE REVENDEDOR -->
      <section id="formulario-solicitud" class="max-w-3xl mx-auto px-4 sm:px-6 scroll-mt-24">
        <div class="bg-gradient-to-b from-slate-900 via-slate-900/90 to-slate-950 p-6 sm:p-10 rounded-3xl border border-amber-500/40 shadow-2xl relative overflow-hidden">
          <div class="absolute -left-20 -top-20 w-64 h-64 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>

          <!-- Cabecera del formulario -->
          <div class="text-center space-y-2 mb-8 relative z-10">
            <span class="text-xs font-mono font-bold uppercase tracking-wider text-amber-400 bg-amber-400/10 px-3 py-1 rounded-full border border-amber-400/20">
              ALTA DE DISTRIBUIDOR OFICIAL
            </span>
            <h2 class="text-2xl sm:text-3xl font-black text-white">Solicita tu Panel de Revendedor</h2>
            <p class="text-xs sm:text-sm text-slate-400 max-w-xl mx-auto">
              Completa tus datos para que el proveedor central evalúe tu perfil y te active tu cuenta con catálogo mayorista oficial.
            </p>
          </div>

          <!-- Estado de éxito al enviar -->
          <div v-if="submittedApplication" class="relative z-10 bg-slate-950/80 border border-emerald-500/40 rounded-2xl p-6 sm:p-8 text-center space-y-5 animate-fade-in">
            <div class="w-16 h-16 bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 rounded-2xl flex items-center justify-center text-3xl mx-auto shadow-lg shadow-emerald-500/10">
              ✓
            </div>

            <div class="space-y-2">
              <span class="text-[11px] font-mono font-bold text-emerald-400 bg-emerald-500/10 px-3 py-1 rounded-full border border-emerald-500/20">
                SOLICITUD REGISTRADA #{{ submittedApplication.id }}
              </span>
              <h3 class="text-xl sm:text-2xl font-black text-white">¡Muchas gracias, {{ submittedApplication.fullName }}!</h3>
              <p class="text-xs sm:text-sm text-slate-300 max-w-md mx-auto leading-relaxed">
                Tu solicitud ha ingresado directamente al <strong class="text-white">Panel del Proveedor Central</strong>. Nos comunicaremos a tu WhatsApp <span class="text-amber-400 font-mono font-bold">{{ submittedApplication.whatsapp }}</span> con tus credenciales de acceso.
              </p>
            </div>

            <div class="p-4 bg-slate-900/60 rounded-xl border border-slate-800 text-xs text-slate-400 max-w-lg mx-auto text-left space-y-1.5 font-mono">
              <div class="flex justify-between">
                <span>Estado:</span>
                <span class="text-amber-400 font-bold">Pendiente de Aprobación</span>
              </div>
              <div class="flex justify-between">
                <span>Fecha y Hora:</span>
                <span class="text-slate-300">{{ submittedApplication.submittedAt }}</span>
              </div>
              <div v-if="submittedApplication.storeName" class="flex justify-between">
                <span>Tienda:</span>
                <span class="text-slate-300">{{ submittedApplication.storeName }}</span>
              </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 justify-center pt-2">
              <button 
                @click="contactPriorityWhatsApp" 
                class="bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black px-6 py-3 rounded-xl shadow-lg shadow-emerald-500/20 text-xs transition-all flex items-center justify-center gap-2 cursor-pointer"
              >
                <span>⚡ Avisar por WhatsApp para Activación Rápida</span>
              </button>
              <button 
                @click="resetForm" 
                class="bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold px-5 py-3 rounded-xl text-xs transition-colors cursor-pointer"
              >
                Enviar otra solicitud
              </button>
            </div>
          </div>

          <!-- Formulario de carga -->
          <form v-else @submit.prevent="submitApplication" class="relative z-10 space-y-4 text-xs">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <!-- Nombre y Apellido -->
              <div class="space-y-1.5 sm:col-span-2">
                <label class="block font-bold text-slate-200">
                  Nombre Completo <span class="text-amber-400">*</span>
                </label>
                <input 
                  v-model="applicationForm.fullName" 
                  type="text" 
                  placeholder="Ej: Carlos Gómez" 
                  required
                  class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-amber-400 transition-colors"
                />
              </div>

              <!-- WhatsApp -->
              <div class="space-y-1.5">
                <label class="block font-bold text-slate-200">
                  Número de WhatsApp <span class="text-amber-400">*</span>
                </label>
                <input 
                  v-model="applicationForm.whatsapp" 
                  type="tel" 
                  placeholder="Ej: +54 9 11 1234-5678" 
                  required
                  class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-amber-400 transition-colors font-mono"
                />
                <span class="text-[10px] text-slate-500">Aquí te enviaremos el usuario y contraseña de tu panel</span>
              </div>

              <!-- Email -->
              <div class="space-y-1.5">
                <label class="block font-bold text-slate-200">
                  Correo Electrónico <span class="text-slate-500 font-normal">(Opcional)</span>
                </label>
                <input 
                  v-model="applicationForm.email" 
                  type="email" 
                  placeholder="ejemplo@gmail.com" 
                  class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-amber-400 transition-colors"
                />
              </div>

              <!-- Ciudad / Provincia -->
              <div class="space-y-1.5">
                <label class="block font-bold text-slate-200">
                  Ciudad y Provincia
                </label>
                <input 
                  v-model="applicationForm.city" 
                  type="text" 
                  placeholder="Ej: Córdoba, Argentina" 
                  class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-amber-400 transition-colors"
                />
              </div>

              <!-- Nombre Propuesto de Tienda -->
              <div class="space-y-1.5">
                <label class="block font-bold text-slate-200">
                  Nombre de tu Marca o Tienda <span class="text-slate-500 font-normal">(Opcional)</span>
                </label>
                <input 
                  v-model="applicationForm.storeName" 
                  type="text" 
                  placeholder="Ej: Carlos Digital Play" 
                  class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-amber-400 transition-colors"
                />
              </div>

              <!-- Experiencia Previa -->
              <div class="space-y-1.5 sm:col-span-2">
                <label class="block font-bold text-slate-200">
                  ¿Cuál es tu experiencia actual vendiendo cuentas o tecnología?
                </label>
                <select 
                  v-model="applicationForm.experience"
                  class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-amber-400 transition-colors cursor-pointer"
                >
                  <option value="Quiero empezar desde cero">Quiero empezar desde cero (sin experiencia previa)</option>
                  <option value="Tengo experiencia básica">Tengo experiencia básica vendiendo por redes / amigos</option>
                  <option value="Tengo cartera activa de clientes">Tengo cartera activa de clientes recurrentes</option>
                  <option value="Tengo local físico o tienda online">Tengo local comercial físico o tienda online</option>
                </select>
              </div>

              <!-- Notas o Mensaje -->
              <div class="space-y-1.5 sm:col-span-2">
                <label class="block font-bold text-slate-200">
                  ¿Alguna consulta o comentario para el proveedor? <span class="text-slate-500 font-normal">(Opcional)</span>
                </label>
                <textarea 
                  v-model="applicationForm.notes" 
                  rows="2" 
                  placeholder="Escribe si te interesa algún servicio en particular (Netflix, IPTV, productos físicos, etc.)" 
                  class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:border-amber-400 transition-colors"
                ></textarea>
              </div>
            </div>

            <!-- Privacidad y Garantía -->
            <div class="p-3.5 bg-slate-950/60 rounded-xl border border-slate-800/80 flex items-center gap-3 text-[11px] text-slate-400">
              <span class="text-base text-amber-400">🔒</span>
              <span>Tus datos son seguros y se utilizarán exclusivamente para habilitar y dar soporte a tu cuenta de distribuidor StreamePro.</span>
            </div>

            <!-- Botón de Envío -->
            <button 
              type="submit" 
              :disabled="isSubmitting"
              class="w-full bg-gradient-to-r from-amber-400 via-orange-400 to-amber-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 font-black py-4 rounded-xl shadow-xl shadow-amber-500/20 text-sm transition-all flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50"
            >
              <span v-if="isSubmitting" class="animate-spin inline-block">⏳</span>
              <span v-else>🚀</span>
              <span>{{ isSubmitting ? 'Enviando solicitud...' : 'Enviar Solicitud de Distribuidor Oficial' }}</span>
            </button>
          </form>
        </div>
      </section>

      <!-- SECCIÓN: CÓMO FUNCIONA EN 3 PASOS -->
      <section class="max-w-5xl mx-auto px-4 sm:px-6 space-y-10">
        <div class="text-center space-y-2">
          <span class="text-xs font-mono font-bold text-amber-400 uppercase tracking-wider">PROCESO SIMPLE</span>
          <h2 class="text-3xl font-black text-white">¿Cómo Funciona el Negocio?</h2>
          <p class="text-xs sm:text-sm text-slate-400">Empezar es rápido y no necesitas conocimientos técnicos.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-xs">
          <div class="bg-slate-900/40 p-6 rounded-3xl border border-slate-800 space-y-3 relative overflow-hidden group hover:border-amber-500/40 transition-colors">
            <span class="text-4xl font-black text-slate-800 group-hover:text-amber-500/20 transition-colors absolute right-4 top-4">01</span>
            <div class="w-12 h-12 rounded-2xl bg-amber-500/10 text-amber-400 flex items-center justify-center text-xl font-bold border border-amber-500/20">
              🔑
            </div>
            <h3 class="font-bold text-white text-base">1. Te habilitamos tu Panel Oficial</h3>
            <p class="text-slate-400 leading-relaxed">
              Te entregamos tu usuario y contraseña para acceder a <code class="text-amber-300 font-mono">/distribuidor</code> con tu catálogo mayorista y stock listo para despachar.
            </p>
          </div>

          <div class="bg-slate-900/40 p-6 rounded-3xl border border-slate-800 space-y-3 relative overflow-hidden group hover:border-amber-500/40 transition-colors">
            <span class="text-4xl font-black text-slate-800 group-hover:text-amber-500/20 transition-colors absolute right-4 top-4">02</span>
            <div class="w-12 h-12 rounded-2xl bg-orange-500/10 text-orange-400 flex items-center justify-center text-xl font-bold border border-orange-500/20">
              🎨
            </div>
            <h3 class="font-bold text-white text-base">2. Publicas con el Generador de Flyers</h3>
            <p class="text-slate-400 leading-relaxed">
              Descargas imágenes profesionales en 1 clic desde tu panel y las subes a tus estados de WhatsApp, Instagram o Facebook Marketplace con tu propio número de contacto.
            </p>
          </div>

          <div class="bg-slate-900/40 p-6 rounded-3xl border border-slate-800 space-y-3 relative overflow-hidden group hover:border-amber-500/40 transition-colors">
            <span class="text-4xl font-black text-slate-800 group-hover:text-amber-500/20 transition-colors absolute right-4 top-4">03</span>
            <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-xl font-bold border border-emerald-500/20">
              💰
            </div>
            <h3 class="font-bold text-white text-base">3. Cobras y Despachas al Instante</h3>
            <p class="text-slate-400 leading-relaxed">
              El cliente te transfiere a tu Alias personal. Entras a tu panel, despachas la cuenta al costo mayorista y le entregas las credenciales por WhatsApp en 10 segundos.
            </p>
          </div>
        </div>
      </section>

      <!-- SECCIÓN: PREGUNTAS FRECUENTES -->
      <section class="max-w-3xl mx-auto px-4 sm:px-6 space-y-6">
        <div class="text-center space-y-2">
          <span class="text-xs font-mono font-bold text-amber-400 uppercase tracking-wider">RESOLVEMOS TUS DUDAS</span>
          <h2 class="text-2xl sm:text-3xl font-black text-white">Preguntas Frecuentes</h2>
        </div>

        <div class="space-y-3 text-xs">
          <div 
            v-for="(faq, fIdx) in faqs" 
            :key="fIdx"
            class="bg-slate-900/40 rounded-2xl border border-slate-800 overflow-hidden transition-colors"
          >
            <button 
              @click="toggleFaq(fIdx)" 
              class="w-full text-left p-4 sm:p-5 flex justify-between items-center font-bold text-white hover:text-amber-300 transition-colors"
            >
              <span class="text-sm">{{ faq.q }}</span>
              <span class="text-base font-mono text-slate-400 ml-2">{{ activeFaq === fIdx ? '−' : '+' }}</span>
            </button>
            <div v-if="activeFaq === fIdx" class="px-5 pb-5 text-slate-400 leading-relaxed border-t border-slate-800/60 pt-3">
              {{ faq.a }}
            </div>
          </div>
        </div>
      </section>

      <!-- BANNER FINAL CTA -->
      <section class="max-w-4xl mx-auto px-4 sm:px-6 text-center">
        <div class="bg-gradient-to-r from-amber-500 via-orange-500 to-amber-500 p-8 sm:p-12 rounded-3xl text-slate-950 space-y-5 shadow-2xl relative overflow-hidden">
          <div class="relative z-10 space-y-3">
            <h2 class="text-3xl sm:text-4xl font-black tracking-tight leading-tight">
              ¿Listo para empezar a generar ingresos hoy?
            </h2>
            <p class="text-slate-950/80 font-bold max-w-xl mx-auto text-sm">
              Completa el formulario de solicitud o contáctanos para habilitar tu panel con catálogo mayorista en menos de 5 minutos.
            </p>
            <div class="pt-3 flex flex-col sm:flex-row items-center justify-center gap-3">
              <button 
                @click="scrollToForm" 
                class="bg-slate-950 hover:bg-slate-900 text-white font-black text-sm px-8 py-4 rounded-2xl shadow-xl transition-all flex items-center gap-2 cursor-pointer"
              >
                <span>📝 Completar Formulario de Registro</span>
                <span>→</span>
              </button>
              <button 
                @click="contactProviderWhatsApp" 
                class="bg-slate-900/10 hover:bg-slate-900/20 text-slate-950 border border-slate-950/30 font-bold text-sm px-6 py-4 rounded-2xl transition-all flex items-center gap-2 cursor-pointer"
              >
                <span>📲 WhatsApp Directo</span>
              </button>
            </div>
          </div>
        </div>
      </section>

    </main>

    <!-- FOOTER -->
    <footer class="border-t border-slate-800/80 py-8 text-center text-xs text-slate-500 font-mono">
      <p>© 2026 StreamePro Mayorista Central. Red de Distribución Oficial de Cuentas & Tecnología.</p>
    </footer>

  </div>
</template>

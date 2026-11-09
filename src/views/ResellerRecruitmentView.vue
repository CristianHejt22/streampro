<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAdminStore } from '../store/admin'
import { useToastStore } from '../store/toast'

const router = useRouter()
const adminStore = useAdminStore()
const toast = useToastStore()

// WhatsApp de contacto con el proveedor oficial
const providerWhatsApp = computed(() => {
  return adminStore.providerBankInfo?.whatsapp || adminStore.adminAuth?.whatsapp || '+54 9 291 565-9321'
})

// Scroll suave a cualquier sección
const scrollToSection = (id) => {
  const el = document.getElementById(id)
  if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }
}

// ==========================================
// FORMULARIO DE SOLICITUD DE REVENDEDOR
// ==========================================
const applicationForm = ref({
  fullName: '',
  whatsapp: '',
  email: '',
  city: '',
  storeName: '',
  interestCatalog: 'Todo el catálogo (Streaming + Videojuegos + Tecnología)',
  experience: 'Quiero empezar desde cero',
  notes: ''
})

const isSubmitting = ref(false)
const submittedApplication = ref(null)

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
    const payload = {
      ...applicationForm.value,
      notes: `[Interés: ${applicationForm.value.interestCatalog}] ${applicationForm.value.notes || ''}`.trim()
    }
    const res = await adminStore.submitResellerApplication(payload)
    if (res && res.success) {
      submittedApplication.value = res.application
      toast.add('¡Solicitud enviada con éxito! La revisaremos en breve.', 'success')
      scrollToSection('formulario-solicitud')
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
    interestCatalog: 'Todo el catálogo (Streaming + Videojuegos + Tecnología)',
    experience: 'Quiero empezar desde cero',
    notes: ''
  }
}

// Contacto WhatsApp prioritario para agilizar el alta
const contactPriorityWhatsApp = () => {
  const cleanPhone = providerWhatsApp.value.replace(/[^0-9]/g, '')
  const name = submittedApplication.value?.fullName || applicationForm.value.fullName || 'Revendedor'
  const appId = submittedApplication.value?.id || ''
  const store = submittedApplication.value?.storeName ? ` (${submittedApplication.value.storeName})` : ''
  const text = `¡Hola! Acabo de enviar mi solicitud de registro como DISTRIBUIDOR OFICIAL en StreamePro.
👤 Nombre: *${name}*${store}
🆔 Solicitud: *${appId}*
📲 WhatsApp: *${submittedApplication.value?.whatsapp || applicationForm.value.whatsapp}*

¿Podrían revisar mi postulación y habilitar mi panel prioritariamente? ¡Muchas gracias!`
  window.open(`https://wa.me/${cleanPhone}?text=${encodeURIComponent(text)}`, '_blank')
}

// Contacto WhatsApp general
const contactProviderWhatsApp = () => {
  const cleanPhone = providerWhatsApp.value.replace(/[^0-9]/g, '')
  const text = `¡Hola! Estuve viendo la propuesta mayorista en la web de StreamePro y me interesa registrarme como REVENDEDOR OFICIAL para vender Streaming, Videojuegos y Tecnología. ¿Me darían más información para comenzar?`
  window.open(`https://wa.me/${cleanPhone}?text=${encodeURIComponent(text)}`, '_blank')
}

// ==========================================
// CALCULADORA INTERACTIVA DE GANANCIAS
// ==========================================
const monthlyStreamingClients = ref(30)
const avgProfitPerAccount = ref(2500) // ARS promedio neto por suscripción

const monthlyGamingSales = ref(10)
const avgProfitPerGame = ref(3500) // ARS promedio neto por videojuego digital

const monthlyPhysicalSales = ref(5)
const avgProfitPerPhysical = ref(4000) // ARS promedio neto por producto de tecnología

const estimatedMonthlyStreamingProfit = computed(() => {
  return monthlyStreamingClients.value * avgProfitPerAccount.value
})

const estimatedMonthlyGamingProfit = computed(() => {
  return monthlyGamingSales.value * avgProfitPerGame.value
})

const estimatedMonthlyPhysicalProfit = computed(() => {
  return monthlyPhysicalSales.value * avgProfitPerPhysical.value
})

const totalEstimatedMonthlyProfit = computed(() => {
  return (
    estimatedMonthlyStreamingProfit.value +
    estimatedMonthlyGamingProfit.value +
    estimatedMonthlyPhysicalProfit.value
  )
})

// ==========================================
// FAQ ACORDEÓN
// ==========================================
const activeFaq = ref(0) // Primer ítem abierto por defecto
const toggleFaq = (idx) => {
  activeFaq.value = activeFaq.value === idx ? null : idx
}

const faqs = [
  {
    q: '¿Necesito experiencia previa o inversión inicial de dinero para empezar?',
    a: '¡Absolutamente ninguna! No pagas membresía ni compras inventario por adelantado. Nosotros te habilitamos tu Panel Oficial de Revendedor (/crm). Cuando un cliente te pide una suscripción, videojuego o producto, él te transfiere el dinero primero a tu cuenta y luego tú despachas desde el panel a precio mayorista, quedándote con tu ganancia neta en el momento.'
  },
  {
    q: '¿Qué catálogo tengo disponible exactamente para revender?',
    a: 'Accedes al catálogo mayorista unificado más completo del mercado en 3 categorías: 1) Streaming Premium (Netflix, Max, Disney+, Spotify, Paramount+, IPTV, Prime, YouTube con perfiles y PIN privado); 2) Videojuegos Digitales (+1.160 títulos para consolas PS3, PS4, PS5, Steam y PC con piso mínimo asegurado del 10% de ganancia); 3) Tecnología & Accesorios Físicos (+360 productos como smartwatches, auriculares inalámbricos, mandos gamer y gadgets con despacho directo a todo el país).'
  },
  {
    q: '¿Cuáles son los tiempos de entrega y cómo funciona la Garantía Oficial?',
    a: 'Para los servicios digitales (streaming y videojuegos), el tiempo garantizado de entrega es de 60 a 90 minutos con instructivo paso a paso y perfiles listos. Cuentas con Garantía Oficial StreamePro ante cualquier novedad técnica o eventualidad; el soporte central atiende las solicitudes y te respalda para cuidar al 100% la reputación de tu negocio.'
  },
  {
    q: '¿Cómo le entrego el servicio o juego a mi cliente?',
    a: 'Desde tu panel de revendedor tienes la herramienta de 1 clic "Copiar para WhatsApp". Al presionarlo, el sistema genera automáticamente un mensaje formal y profesional con el correo de acceso, contraseña, perfil asignado, PIN privado e instructivo de conexión. Solo pegas el mensaje en el chat con tu cliente y listo.'
  },
  {
    q: '¿Cómo cobro el dinero de mis ventas?',
    a: 'Tus clientes te pagan a ti directamente a través de tu CBU, CVU, Mercado Pago, Cuenta DNI, Ualá o cualquier entidad bancaria. Puedes configurar tu propio Alias bancario en el panel para que figure automáticamente en tus comprobantes.'
  },
  {
    q: '¿Puedo vender bajo mi propio nombre de marca o tienda personal?',
    a: '¡Por supuesto! Eres tu propio jefe y puedes usar el nombre comercial que elijas (ej: "TuNombre Digital Play", "Zona Gamer", etc.). Nosotros operamos como tu centro mayorista; tus clientes son tuyos y nunca verán datos que no sean los de tu propia marca.'
  },
  {
    q: '¿Cómo funciona el Dropshipping de productos de tecnología física?',
    a: 'Publicas los productos de tecnología de nuestro catálogo en tus redes o catálogo de WhatsApp al precio final que prefieras. Cuando un cliente te compra, cargas la orden en el panel indicando los datos de envío de tu cliente. Nuestro centro de logística empaqueta y despacha el pedido por correo a cualquier punto de Argentina sin que tengas que tocar una sola caja.'
  }
]
</script>

<template>
  <div class="min-h-screen bg-[#020617] text-white flex flex-col selection:bg-amber-500 selection:text-slate-950">
    
    <!-- NAVBAR SUPERIOR -->
    <header class="border-b border-slate-800/80 bg-slate-950/85 backdrop-blur-md sticky top-0 z-40">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
        <div class="flex items-center gap-3 cursor-pointer" @click="router.push('/')">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-amber-500 to-orange-500 flex items-center justify-center text-slate-950 font-black shadow-lg shadow-amber-500/20 text-lg">
            ⚡
          </div>
          <div>
            <span class="font-black text-xl tracking-tight bg-gradient-to-r from-white via-slate-100 to-slate-400 bg-clip-text text-transparent">
              StreamePro
            </span>
            <span class="text-[10px] font-mono block text-amber-400 -mt-1 font-bold tracking-wider">RED MAYORISTA OFICIAL</span>
          </div>
        </div>

        <nav class="hidden lg:flex items-center gap-6 text-xs font-bold text-slate-300">
          <button @click="scrollToSection('catalogo')" class="hover:text-amber-400 transition-colors cursor-pointer">
            📦 Catálogo Trío
          </button>
          <button @click="scrollToSection('calculadora')" class="hover:text-amber-400 transition-colors cursor-pointer">
            📊 Calculadora
          </button>
          <button @click="scrollToSection('comparativa')" class="hover:text-amber-400 transition-colors cursor-pointer">
            🛡️ Ventajas
          </button>
          <button @click="scrollToSection('como-funciona')" class="hover:text-amber-400 transition-colors cursor-pointer">
            🚀 Cómo Funciona
          </button>
          <button @click="scrollToSection('faq')" class="hover:text-amber-400 transition-colors cursor-pointer">
            ❓ FAQ
          </button>
        </nav>

        <div class="flex items-center gap-2.5 text-xs">
          <button 
            @click="router.push('/')" 
            class="hidden sm:inline-block text-slate-400 hover:text-white font-bold px-3 py-1.5 transition-colors"
          >
            ← Tienda
          </button>
          <button 
            @click="router.push('/crm')" 
            class="bg-slate-900 hover:bg-slate-800 text-slate-200 border border-slate-700 font-bold px-3.5 py-2 rounded-xl transition-all flex items-center gap-1.5 cursor-pointer"
            title="Ingreso para distribuidores con cuenta activa"
          >
            <span>🔑</span>
            <span class="hidden sm:inline">Ya soy Revendedor</span>
            <span class="sm:hidden">Ingresar</span>
          </button>
          <button 
            @click="scrollToSection('formulario-solicitud')" 
            class="bg-gradient-to-r from-amber-400 to-orange-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 font-black px-4 py-2 rounded-xl shadow-lg shadow-amber-500/20 transition-all flex items-center gap-1.5 cursor-pointer"
          >
            <span>📝</span>
            <span>Solicitar Panel</span>
          </button>
        </div>
      </div>
    </header>

    <main class="flex-1 space-y-24 pb-24">
      
      <!-- HERO SECTION -->
      <section class="relative pt-12 sm:pt-20 pb-8 px-4 sm:px-6 max-w-5xl mx-auto text-center space-y-6">
        <!-- Glow ambiental de fondo -->
        <div class="absolute -top-12 left-1/2 -translate-x-1/2 w-96 h-96 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="inline-flex items-center gap-2 bg-gradient-to-r from-amber-500/15 via-orange-500/15 to-amber-500/15 border border-amber-500/30 px-4 py-1.5 rounded-full text-xs font-mono font-bold text-amber-300 shadow-inner">
          <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
          <span>PROGRAMA DE DISTRIBUIDORES OFICIALES 2026 • REGISTRO ABIERTO</span>
        </div>

        <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black tracking-tight leading-tight sm:leading-none">
          Construye tu Propio Negocio de <br class="hidden sm:inline" />
          <span class="bg-gradient-to-r from-amber-400 via-orange-400 to-amber-200 bg-clip-text text-transparent">
            Streaming, Videojuegos & Tecnología
          </span>
          <br />Sin Invertir en Stock
        </h1>

        <p class="text-sm sm:text-lg text-slate-300 max-w-3xl mx-auto font-normal leading-relaxed">
          Accede a costos mayoristas exclusivos, despacha cuentas completas o perfiles con PIN en segundos, vende más de <strong class="text-white">1.160 videojuegos digitales</strong> para PlayStation/PC y más de <strong class="text-white">360 productos físicos en dropshipping</strong>. Entrega en 60/90 minutos con <strong class="text-amber-400 font-bold">Garantía Oficial</strong> y cobro directo a tu CBU/Alias.
        </p>

        <!-- Píldoras de las 3 ramas -->
        <div class="flex flex-wrap items-center justify-center gap-2 pt-2 text-xs font-mono font-bold">
          <span class="bg-slate-900/80 border border-slate-800 text-amber-400 px-3 py-1.5 rounded-full flex items-center gap-1.5 shadow-sm">
            🍿 Streaming & Cuentas PIN
          </span>
          <span class="bg-slate-900/80 border border-slate-800 text-purple-400 px-3 py-1.5 rounded-full flex items-center gap-1.5 shadow-sm">
            🎮 +1.160 Videojuegos Digitales
          </span>
          <span class="bg-slate-900/80 border border-slate-800 text-sky-400 px-3 py-1.5 rounded-full flex items-center gap-1.5 shadow-sm">
            📦 +360 Productos Dropshipping
          </span>
        </div>

        <!-- Botones CTA Hero -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-4">
          <button 
            @click="scrollToSection('formulario-solicitud')" 
            class="w-full sm:w-auto bg-gradient-to-r from-amber-400 via-orange-400 to-amber-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 font-black px-8 py-4 rounded-2xl shadow-xl shadow-amber-500/25 transition-all text-sm flex items-center justify-center gap-2 cursor-pointer"
          >
            <span>🚀 Solicitar mi Panel de Revendedor</span>
            <span>→</span>
          </button>
          <button 
            @click="scrollToSection('calculadora')" 
            class="w-full sm:w-auto bg-slate-900/90 hover:bg-slate-800 text-slate-200 border border-slate-800 font-bold px-6 py-4 rounded-2xl transition-all text-sm flex items-center justify-center gap-2 cursor-pointer"
          >
            <span>📊 Simular mis Ganancias</span>
          </button>
          <button 
            @click="contactProviderWhatsApp" 
            class="w-full sm:w-auto bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 font-bold px-5 py-4 rounded-2xl transition-all text-sm flex items-center justify-center gap-2 cursor-pointer"
          >
            <span>💬 WhatsApp</span>
          </button>
        </div>

        <!-- Métricas destacadas -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 pt-10 border-t border-slate-800/80 max-w-4xl mx-auto text-left">
          <div class="bg-slate-900/40 p-4 rounded-2xl border border-slate-800 hover:border-amber-500/30 transition-colors">
            <span class="text-2xl font-black text-amber-400 block">+40%</span>
            <span class="text-xs text-slate-400">Margen neto en streaming</span>
          </div>
          <div class="bg-slate-900/40 p-4 rounded-2xl border border-slate-800 hover:border-purple-500/30 transition-colors">
            <span class="text-2xl font-black text-purple-400 block">+1.160</span>
            <span class="text-xs text-slate-400">Juegos PlayStation & PC</span>
          </div>
          <div class="bg-slate-900/40 p-4 rounded-2xl border border-slate-800 hover:border-sky-500/30 transition-colors">
            <span class="text-2xl font-black text-sky-400 block">+360</span>
            <span class="text-xs text-slate-400">Productos dropshipping físico</span>
          </div>
          <div class="bg-slate-900/40 p-4 rounded-2xl border border-slate-800 hover:border-emerald-500/30 transition-colors">
            <span class="text-2xl font-black text-emerald-400 block">60/90 min</span>
            <span class="text-xs text-slate-400">Entrega con Garantía Oficial</span>
          </div>
        </div>
      </section>

      <!-- SECCIÓN: LAS 3 LÍNEAS DE NEGOCIO (CATÁLOGO TRÍO) -->
      <section id="catalogo" class="max-w-6xl mx-auto px-4 sm:px-6 space-y-10 scroll-mt-20">
        <div class="text-center space-y-3">
          <span class="text-xs font-mono font-bold uppercase tracking-wider text-amber-400 bg-amber-400/10 px-3 py-1 rounded-full border border-amber-400/20">
            CATÁLOGO MAYORISTA UNIFICADO
          </span>
          <h2 class="text-3xl sm:text-4xl font-black text-white">Un Solo Panel, Tres Negocios Altamente Rentables</h2>
          <p class="text-xs sm:text-sm text-slate-400 max-w-2xl mx-auto">
            A diferencia de proveedores informales que solo venden una cosa, con StreamePro diversificas tu oferta para multiplicar las ventas diarias a tus mismos clientes.
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          
          <!-- TARJETA 1: STREAMING -->
          <div class="bg-gradient-to-b from-slate-900/80 to-slate-950 p-6 sm:p-7 rounded-3xl border border-slate-800 hover:border-amber-500/40 transition-all flex flex-col justify-between space-y-5 relative overflow-hidden group">
            <div class="space-y-3 relative z-10">
              <div class="w-12 h-12 rounded-2xl bg-amber-500/15 text-amber-400 flex items-center justify-center text-2xl border border-amber-500/30">
                🍿
              </div>
              <div class="flex items-center justify-between">
                <span class="text-[11px] font-mono font-bold text-amber-400 uppercase tracking-wider">Alta Recompra</span>
                <span class="text-[10px] bg-amber-500/20 text-amber-300 font-bold px-2 py-0.5 rounded-full border border-amber-500/30">85% Renovación</span>
              </div>
              <h3 class="text-xl font-black text-white">Streaming & Cuentas PIN</h3>
              <p class="text-xs text-slate-400 leading-relaxed">
                Netflix, Max, Disney+, Spotify, Paramount+, IPTV, Prime Video y YouTube Premium. Perfiles individuales protegidos con PIN de 4 dígitos o cuentas completas.
              </p>
              
              <ul class="text-xs space-y-2 text-slate-300 pt-2 border-t border-slate-800/80">
                <li class="flex items-center gap-2">
                  <span class="text-amber-400">✓</span> Perfiles con PIN privado asignado
                </li>
                <li class="flex items-center gap-2">
                  <span class="text-amber-400">✓</span> Botón "Copiar para WhatsApp" listo
                </li>
                <li class="flex items-center gap-2">
                  <span class="text-amber-400">✓</span> Ingreso recurrente mes a mes
                </li>
              </ul>
            </div>

            <div class="pt-3 border-t border-slate-800">
              <span class="text-[11px] text-slate-500 font-mono block">Margen estimado:</span>
              <span class="text-lg font-black text-amber-400 font-mono">$2.000 - $3.500 ARS / cuenta</span>
            </div>
          </div>

          <!-- TARJETA 2: VIDEOJUEGOS DIGITALES -->
          <div class="bg-gradient-to-b from-slate-900/80 to-slate-950 p-6 sm:p-7 rounded-3xl border border-slate-800 hover:border-purple-500/40 transition-all flex flex-col justify-between space-y-5 relative overflow-hidden group">
            <div class="space-y-3 relative z-10">
              <div class="w-12 h-12 rounded-2xl bg-purple-500/15 text-purple-400 flex items-center justify-center text-2xl border border-purple-500/30">
                🎮
              </div>
              <div class="flex items-center justify-between">
                <span class="text-[11px] font-mono font-bold text-purple-400 uppercase tracking-wider">+1.160 Títulos</span>
                <span class="text-[10px] bg-purple-500/20 text-purple-300 font-bold px-2 py-0.5 rounded-full border border-purple-500/30">Piso 10%+</span>
              </div>
              <h3 class="text-xl font-black text-white">Videojuegos Digitales</h3>
              <p class="text-xs text-slate-400 leading-relaxed">
                Catálogo masivo para PS3, PS4, PS5, Steam y PC. Licencias de lanzamientos, clásicos, sagas icónicas y bundles con guía paso a paso para el cliente.
              </p>
              
              <ul class="text-xs space-y-2 text-slate-300 pt-2 border-t border-slate-800/80">
                <li class="flex items-center gap-2">
                  <span class="text-purple-400">✓</span> Licencias para PS3, PS4, PS5 y PC
                </li>
                <li class="flex items-center gap-2">
                  <span class="text-purple-400">✓</span> Entrega digital garantizada 60/90 min
                </li>
                <li class="flex items-center gap-2">
                  <span class="text-purple-400">✓</span> Margen asegurado con piso mínimo 10%
                </li>
              </ul>
            </div>

            <div class="pt-3 border-t border-slate-800">
              <span class="text-[11px] text-slate-500 font-mono block">Margen estimado:</span>
              <span class="text-lg font-black text-purple-400 font-mono">$3.000 - $6.000 ARS / juego</span>
            </div>
          </div>

          <!-- TARJETA 3: TECNOLOGÍA FÍSICA -->
          <div class="bg-gradient-to-b from-slate-900/80 to-slate-950 p-6 sm:p-7 rounded-3xl border border-slate-800 hover:border-sky-500/40 transition-all flex flex-col justify-between space-y-5 relative overflow-hidden group">
            <div class="space-y-3 relative z-10">
              <div class="w-12 h-12 rounded-2xl bg-sky-500/15 text-sky-400 flex items-center justify-center text-2xl border border-sky-500/30">
                📦
              </div>
              <div class="flex items-center justify-between">
                <span class="text-[11px] font-mono font-bold text-sky-400 uppercase tracking-wider">+360 Productos</span>
                <span class="text-[10px] bg-sky-500/20 text-sky-300 font-bold px-2 py-0.5 rounded-full border border-sky-500/30">Dropshipping</span>
              </div>
              <h3 class="text-xl font-black text-white">Tecnología & Gadgets</h3>
              <p class="text-xs text-slate-400 leading-relaxed">
                Smartwatches, auriculares TWS inalámbricos, mandos para juegos, aros LED, afeitadoras y accesorios. Despacho directo a tu cliente a toda Argentina.
              </p>
              
              <ul class="text-xs space-y-2 text-slate-300 pt-2 border-t border-slate-800/80">
                <li class="flex items-center gap-2">
                  <span class="text-sky-400">✓</span> Sin empaquetar ni tocar inventario
                </li>
                <li class="flex items-center gap-2">
                  <span class="text-sky-400">✓</span> Envíos a cualquier punto del país
                </li>
                <li class="flex items-center gap-2">
                  <span class="text-sky-400">✓</span> Fotos y fichas técnicas listas
                </li>
              </ul>
            </div>

            <div class="pt-3 border-t border-slate-800">
              <span class="text-[11px] text-slate-500 font-mono block">Margen estimado:</span>
              <span class="text-lg font-black text-sky-400 font-mono">$3.500 - $7.000 ARS / producto</span>
            </div>
          </div>

        </div>
      </section>

      <!-- SECCIÓN: CALCULADORA INTERACTIVA DE GANANCIAS -->
      <section id="calculadora" class="max-w-4xl mx-auto px-4 sm:px-6 scroll-mt-20">
        <div class="bg-gradient-to-b from-slate-900 via-slate-900/90 to-slate-950 p-6 sm:p-10 rounded-3xl border border-amber-500/30 shadow-2xl relative overflow-hidden">
          <div class="absolute -right-20 -top-20 w-64 h-64 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>

          <div class="text-center space-y-2 mb-8 relative z-10">
            <span class="text-xs font-mono font-bold uppercase tracking-wider text-amber-400 bg-amber-400/10 px-3 py-1 rounded-full border border-amber-400/20">
              SIMULADOR DE INGRESOS MENSUALES
            </span>
            <h2 class="text-2xl sm:text-3xl font-black text-white">¿Cuánto Puedes Ganar como Revendedor?</h2>
            <p class="text-xs sm:text-sm text-slate-400 max-w-xl mx-auto">
              Ajusta los deslizadores según tus metas y descubre tu ganancia neta mensual estimada combinando Streaming, Videojuegos y Tecnología.
            </p>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 relative z-10">
            <!-- Deslizadores -->
            <div class="space-y-6">
              
              <!-- Slider 1: Clientes Streaming -->
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

              <!-- Slider 2: Videojuegos Digitales -->
              <div class="space-y-2">
                <div class="flex justify-between items-center text-xs">
                  <span class="font-bold text-slate-300">🎮 Videojuegos Digitales al mes:</span>
                  <span class="font-mono font-black text-purple-400 text-sm">{{ monthlyGamingSales }} juegos</span>
                </div>
                <input 
                  v-model.number="monthlyGamingSales" 
                  type="range" 
                  min="0" 
                  max="60" 
                  step="1"
                  class="w-full accent-purple-400 cursor-pointer h-2 bg-slate-800 rounded-lg"
                >
                <div class="flex justify-between text-[10px] text-slate-500 font-mono">
                  <span>0 ventas</span>
                  <span>30 ventas</span>
                  <span>60 ventas</span>
                </div>
              </div>

              <!-- Slider 3: Productos Físicos -->
              <div class="space-y-2">
                <div class="flex justify-between items-center text-xs">
                  <span class="font-bold text-slate-300">📦 Ventas Tecnología (Dropshipping):</span>
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

              <div class="p-4 bg-slate-950 rounded-2xl border border-slate-800 text-[11px] text-slate-400 space-y-1.5">
                <p>💡 <strong class="text-slate-200">Efecto Bola de Nieve:</strong> El 85% de los clientes de streaming renueva mes a mes. Esto significa que los clientes que captas hoy te seguirán generando ingresos el mes próximo de forma automática.</p>
              </div>
            </div>

            <!-- Panel de Resultado -->
            <div class="bg-[#020617] rounded-2xl border border-slate-800 p-6 sm:p-7 flex flex-col justify-between text-center space-y-4">
              <div>
                <span class="text-xs uppercase font-bold text-slate-400 tracking-wider">Tu Ganancia Neta Estimada</span>
                <div class="flex items-baseline justify-center gap-1 my-2">
                  <span class="text-2xl font-black text-emerald-400">$</span>
                  <span class="text-4xl sm:text-5xl font-black text-white tracking-tight">
                    {{ totalEstimatedMonthlyProfit.toLocaleString() }}
                  </span>
                  <span class="text-xs font-mono text-slate-400 font-bold">ARS/mes</span>
                </div>
                <span class="text-xs text-emerald-400 font-bold flex items-center justify-center gap-1">
                  <span>💰</span> Dinero limpio directo a tu cuenta
                </span>
              </div>

              <div class="space-y-2.5 border-t border-slate-800 pt-3 text-xs text-left font-mono">
                <div class="flex justify-between text-slate-400">
                  <span class="flex items-center gap-1.5">
                    <span class="text-amber-400">🍿</span> Streaming ({{ monthlyStreamingClients }} cuentas):
                  </span>
                  <strong class="text-slate-200">${{ estimatedMonthlyStreamingProfit.toLocaleString() }} ARS</strong>
                </div>
                <div class="flex justify-between text-slate-400">
                  <span class="flex items-center gap-1.5">
                    <span class="text-purple-400">🎮</span> Videojuegos ({{ monthlyGamingSales }} títulos):
                  </span>
                  <strong class="text-slate-200">${{ estimatedMonthlyGamingProfit.toLocaleString() }} ARS</strong>
                </div>
                <div class="flex justify-between text-slate-400">
                  <span class="flex items-center gap-1.5">
                    <span class="text-sky-400">📦</span> Tecnología ({{ monthlyPhysicalSales }} despachos):
                  </span>
                  <strong class="text-slate-200">${{ estimatedMonthlyPhysicalProfit.toLocaleString() }} ARS</strong>
                </div>
              </div>

              <button 
                @click="scrollToSection('formulario-solicitud')" 
                class="w-full bg-gradient-to-r from-amber-400 via-orange-400 to-amber-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 font-black py-3.5 rounded-xl shadow-lg shadow-amber-500/20 transition-all text-xs flex items-center justify-center gap-2 cursor-pointer"
              >
                <span>🚀 Solicitar mi Panel y Empezar a Ganar</span>
              </button>
            </div>
          </div>
        </div>
      </section>

      <!-- SECCIÓN: TABLA COMPARATIVA (STREAMEPRO VS OTROS) -->
      <section id="comparativa" class="max-w-4xl mx-auto px-4 sm:px-6 space-y-8 scroll-mt-20">
        <div class="text-center space-y-2">
          <span class="text-xs font-mono font-bold text-amber-400 uppercase tracking-wider">¿POR QUÉ ELEGIRNOS?</span>
          <h2 class="text-3xl font-black text-white">Proveedores Informales vs. Red StreamePro</h2>
          <p class="text-xs sm:text-sm text-slate-400">No pongas en riesgo tu tiempo ni la confianza de tus clientes.</p>
        </div>

        <div class="bg-slate-900/50 rounded-3xl border border-slate-800 overflow-hidden shadow-xl text-xs">
          <div class="grid grid-cols-1 md:grid-cols-3 border-b border-slate-800 bg-slate-950/60 p-4 font-mono font-bold text-slate-400 text-center">
            <span class="text-left md:col-span-1 text-slate-300">Característica</span>
            <span class="text-red-400 hidden md:block">Proveedores Informales</span>
            <span class="text-emerald-400 hidden md:block">Distribuidor StreamePro</span>
          </div>

          <div class="divide-y divide-slate-800/80">
            
            <div class="p-4 grid grid-cols-1 md:grid-cols-3 gap-2 items-center">
              <span class="font-bold text-slate-200">Garantía ante caídas</span>
              <div class="text-slate-400 text-[11px] flex items-center gap-1.5 md:justify-center">
                <span class="text-red-400 font-bold">✕</span> Sin respuesta o te bloquean
              </div>
              <div class="text-emerald-400 font-bold text-[11px] flex items-center gap-1.5 md:justify-center">
                <span>✓</span> Garantía Oficial con reposición 60/90 min
              </div>
            </div>

            <div class="p-4 grid grid-cols-1 md:grid-cols-3 gap-2 items-center">
              <span class="font-bold text-slate-200">Plataforma de despacho</span>
              <div class="text-slate-400 text-[11px] flex items-center gap-1.5 md:justify-center">
                <span class="text-red-400 font-bold">✕</span> Grupos de Telegram desordenados
              </div>
              <div class="text-emerald-400 font-bold text-[11px] flex items-center gap-1.5 md:justify-center">
                <span>✓</span> Panel exclusivo /crm propio 24/7
              </div>
            </div>

            <div class="p-4 grid grid-cols-1 md:grid-cols-3 gap-2 items-center">
              <span class="font-bold text-slate-200">Variedad de catálogo</span>
              <div class="text-slate-400 text-[11px] flex items-center gap-1.5 md:justify-center">
                <span class="text-red-400 font-bold">✕</span> Solo 1 o 2 cuentas genéricas
              </div>
              <div class="text-emerald-400 font-bold text-[11px] flex items-center gap-1.5 md:justify-center">
                <span>✓</span> Streaming + 1.160 Juegos + 360 Gadgets
              </div>
            </div>

            <div class="p-4 grid grid-cols-1 md:grid-cols-3 gap-2 items-center">
              <span class="font-bold text-slate-200">Material de marketing</span>
              <div class="text-slate-400 text-[11px] flex items-center gap-1.5 md:justify-center">
                <span class="text-red-400 font-bold">✕</span> Tienes que diseñar todo tú mismo
              </div>
              <div class="text-emerald-400 font-bold text-[11px] flex items-center gap-1.5 md:justify-center">
                <span>✓</span> Generador de Flyers en 1 clic para tus redes
              </div>
            </div>

            <div class="p-4 grid grid-cols-1 md:grid-cols-3 gap-2 items-center">
              <span class="font-bold text-slate-200">Cobro de tus ganancias</span>
              <div class="text-slate-400 text-[11px] flex items-center gap-1.5 md:justify-center">
                <span class="text-red-400 font-bold">✕</span> Comisiones demoradas o retenciones
              </div>
              <div class="text-emerald-400 font-bold text-[11px] flex items-center gap-1.5 md:justify-center">
                <span>✓</span> 100% directo a tu CBU/Alias en el acto
              </div>
            </div>

          </div>
        </div>
      </section>

      <!-- SECCIÓN: CÓMO FUNCIONA EN 4 PASOS -->
      <section id="como-funciona" class="max-w-5xl mx-auto px-4 sm:px-6 space-y-10 scroll-mt-20">
        <div class="text-center space-y-2">
          <span class="text-xs font-mono font-bold text-amber-400 uppercase tracking-wider">CIRCUITO DE TRABAJO</span>
          <h2 class="text-3xl font-black text-white">¿Cómo Funciona el Negocio?</h2>
          <p class="text-xs sm:text-sm text-slate-400">Empezar es fácil y no necesitas conocimientos técnicos previos.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 text-xs">
          
          <div class="bg-slate-900/40 p-5 rounded-3xl border border-slate-800 space-y-3 relative overflow-hidden group hover:border-amber-500/40 transition-colors">
            <span class="text-3xl font-black text-slate-800 group-hover:text-amber-500/20 transition-colors absolute right-4 top-4">01</span>
            <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center text-lg font-bold border border-amber-500/20">
              📝
            </div>
            <h3 class="font-bold text-white text-sm">1. Postúlate Gratis</h3>
            <p class="text-slate-400 leading-relaxed text-[11px]">
              Completas el formulario de registro indicando tu WhatsApp y ciudad. Evaluamos tu solicitud de inmediato.
            </p>
          </div>

          <div class="bg-slate-900/40 p-5 rounded-3xl border border-slate-800 space-y-3 relative overflow-hidden group hover:border-orange-500/40 transition-colors">
            <span class="text-3xl font-black text-slate-800 group-hover:text-orange-500/20 transition-colors absolute right-4 top-4">02</span>
            <div class="w-10 h-10 rounded-xl bg-orange-500/10 text-orange-400 flex items-center justify-center text-lg font-bold border border-orange-500/20">
              🔑
            </div>
            <h3 class="font-bold text-white text-sm">2. Recibe tu Panel /crm</h3>
            <p class="text-slate-400 leading-relaxed text-[11px]">
              Te enviamos tu usuario y clave privada por WhatsApp con catálogo mayorista oficial habilitado 24/7.
            </p>
          </div>

          <div class="bg-slate-900/40 p-5 rounded-3xl border border-slate-800 space-y-3 relative overflow-hidden group hover:border-purple-500/40 transition-colors">
            <span class="text-3xl font-black text-slate-800 group-hover:text-purple-500/20 transition-colors absolute right-4 top-4">03</span>
            <div class="w-10 h-10 rounded-xl bg-purple-500/10 text-purple-400 flex items-center justify-center text-lg font-bold border border-purple-500/20">
              🎨
            </div>
            <h3 class="font-bold text-white text-sm">3. Publica con Flyers</h3>
            <p class="text-slate-400 leading-relaxed text-[11px]">
              Descargas imágenes profesionales desde tu panel y las subes a tus estados de WhatsApp o Instagram con tus precios.
            </p>
          </div>

          <div class="bg-slate-900/40 p-5 rounded-3xl border border-slate-800 space-y-3 relative overflow-hidden group hover:border-emerald-500/40 transition-colors">
            <span class="text-3xl font-black text-slate-800 group-hover:text-emerald-500/20 transition-colors absolute right-4 top-4">04</span>
            <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-lg font-bold border border-emerald-500/20">
              💰
            </div>
            <h3 class="font-bold text-white text-sm">4. Cobra y Despacha</h3>
            <p class="text-slate-400 leading-relaxed text-[11px]">
              El cliente te transfiere a tu Alias personal. Despachas al costo mayorista y le entregas el servicio en segundos.
            </p>
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
              Completa tus datos para que el Proveedor Central evalúe tu perfil y te active tu cuenta con catálogo mayorista oficial.
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
              <div v-if="submittedApplication.city" class="flex justify-between">
                <span>Ciudad:</span>
                <span class="text-slate-300">{{ submittedApplication.city }}</span>
              </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 justify-center pt-2">
              <button 
                @click="contactPriorityWhatsApp" 
                class="bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black px-6 py-3.5 rounded-xl shadow-lg shadow-emerald-500/20 text-xs transition-all flex items-center justify-center gap-2 cursor-pointer"
              >
                <span>⚡ Avisar por WhatsApp para Activación Rápida</span>
              </button>
              <button 
                @click="router.push('/crm')" 
                class="bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold px-5 py-3.5 rounded-xl text-xs transition-colors cursor-pointer border border-slate-700"
              >
                🔑 Ir al Login /crm
              </button>
              <button 
                @click="resetForm" 
                class="bg-transparent hover:bg-slate-900 text-slate-400 hover:text-white font-bold px-4 py-3.5 rounded-xl text-xs transition-colors cursor-pointer"
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
                <span class="text-[10px] text-slate-500">Aquí te enviaremos tu usuario y contraseña del panel</span>
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
                  placeholder="Ej: Bahía Blanca, Buenos Aires" 
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

              <!-- Rubro o Catálogo de Mayor Interés -->
              <div class="space-y-1.5 sm:col-span-2">
                <label class="block font-bold text-slate-200">
                  ¿Qué catálogo te interesa comercializar principalmente?
                </label>
                <select 
                  v-model="applicationForm.interestCatalog"
                  class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-amber-400 transition-colors cursor-pointer"
                >
                  <option value="Todo el catálogo (Streaming + Videojuegos + Tecnología)">🌟 Todo el catálogo completo (Streaming + Videojuegos + Tecnología)</option>
                  <option value="Cuentas y Pantallas de Streaming">🍿 Streaming (Netflix, Max, Disney+, Spotify, IPTV)</option>
                  <option value="Videojuegos Digitales PS3/PS4/PS5/PC">🎮 Videojuegos Digitales (PlayStation y PC)</option>
                  <option value="Tecnología Física en Dropshipping">📦 Dropshipping de Tecnología y Accesorios</option>
                </select>
              </div>

              <!-- Experiencia Previa -->
              <div class="space-y-1.5 sm:col-span-2">
                <label class="block font-bold text-slate-200">
                  ¿Cuál es tu experiencia actual en ventas digitales o presenciales?
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
                  placeholder="Escribe si tienes dudas sobre precios, horarios de entrega o medios de pago..." 
                  class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:border-amber-400 transition-colors"
                ></textarea>
              </div>
            </div>

            <!-- Privacidad y Garantía -->
            <div class="p-3.5 bg-slate-950/60 rounded-xl border border-slate-800/80 flex items-center gap-3 text-[11px] text-slate-400">
              <span class="text-base text-amber-400">🔒</span>
              <span>Tus datos son 100% confidenciales y se utilizarán exclusivamente para habilitar y dar soporte a tu cuenta de distribuidor oficial StreamePro.</span>
            </div>

            <!-- Botón de Envío -->
            <button 
              type="submit" 
              :disabled="isSubmitting"
              class="w-full bg-gradient-to-r from-amber-400 via-orange-400 to-amber-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 font-black py-4 rounded-xl shadow-xl shadow-amber-500/20 text-sm transition-all flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50"
            >
              <span v-if="isSubmitting" class="animate-spin inline-block">⏳</span>
              <span v-else>🚀</span>
              <span>{{ isSubmitting ? 'Enviando postulación al proveedor...' : 'Enviar Solicitud de Distribuidor Oficial' }}</span>
            </button>
          </form>
        </div>
      </section>

      <!-- SECCIÓN: PREGUNTAS FRECUENTES -->
      <section id="faq" class="max-w-3xl mx-auto px-4 sm:px-6 space-y-6 scroll-mt-20">
        <div class="text-center space-y-2">
          <span class="text-xs font-mono font-bold text-amber-400 uppercase tracking-wider">RESOLVEMOS TUS DUDAS</span>
          <h2 class="text-2xl sm:text-3xl font-black text-white">Preguntas Frecuentes</h2>
          <p class="text-xs sm:text-sm text-slate-400">Todo lo que necesitas saber antes de iniciar tu negocio con nosotros.</p>
        </div>

        <div class="space-y-3 text-xs">
          <div 
            v-for="(faq, fIdx) in faqs" 
            :key="fIdx"
            class="bg-slate-900/40 rounded-2xl border border-slate-800 overflow-hidden transition-colors"
          >
            <button 
              @click="toggleFaq(fIdx)" 
              class="w-full text-left p-4 sm:p-5 flex justify-between items-center font-bold text-white hover:text-amber-300 transition-colors cursor-pointer"
            >
              <span class="text-sm pr-4">{{ faq.q }}</span>
              <span class="text-lg font-mono text-amber-400 font-black flex-shrink-0">{{ activeFaq === fIdx ? '−' : '+' }}</span>
            </button>
            <div v-if="activeFaq === fIdx" class="px-5 pb-5 text-slate-300 leading-relaxed border-t border-slate-800/60 pt-3">
              {{ faq.a }}
            </div>
          </div>
        </div>
      </section>

      <!-- BANNER FINAL CTA -->
      <section class="max-w-4xl mx-auto px-4 sm:px-6 text-center">
        <div class="bg-gradient-to-r from-amber-500 via-orange-500 to-amber-500 p-8 sm:p-12 rounded-3xl text-slate-950 space-y-5 shadow-2xl relative overflow-hidden">
          <div class="relative z-10 space-y-3">
            <span class="text-xs font-mono font-black uppercase tracking-wider bg-slate-950 text-amber-400 px-3 py-1 rounded-full">
              ¡CUPOS LIMITADOS POR CIUDAD!
            </span>
            <h2 class="text-3xl sm:text-4xl font-black tracking-tight leading-tight">
              ¿Listo para Empezar a Generar Ingresos Hoy?
            </h2>
            <p class="text-slate-950/85 font-bold max-w-xl mx-auto text-sm leading-relaxed">
              Completa el formulario de postulación o escríbenos directamente por WhatsApp para activar tu panel con catálogo mayorista oficial en pocos minutos.
            </p>
            <div class="pt-3 flex flex-col sm:flex-row items-center justify-center gap-3">
              <button 
                @click="scrollToSection('formulario-solicitud')" 
                class="w-full sm:w-auto bg-slate-950 hover:bg-slate-900 text-white font-black text-sm px-8 py-4 rounded-2xl shadow-xl transition-all flex items-center justify-center gap-2 cursor-pointer"
              >
                <span>📝 Completar Formulario de Alta</span>
                <span>→</span>
              </button>
              <button 
                @click="contactProviderWhatsApp" 
                class="w-full sm:w-auto bg-slate-900/10 hover:bg-slate-900/20 text-slate-950 border border-slate-950/30 font-black text-sm px-6 py-4 rounded-2xl transition-all flex items-center justify-center gap-2 cursor-pointer"
              >
                <span>💬 WhatsApp Directo con Proveedor</span>
              </button>
            </div>
          </div>
        </div>
      </section>

    </main>

    <!-- FOOTER -->
    <footer class="border-t border-slate-800/80 py-10 bg-slate-950/60 text-center text-xs text-slate-500 font-mono space-y-3">
      <div class="flex items-center justify-center gap-6 text-slate-400 font-sans font-bold">
        <button @click="router.push('/')" class="hover:text-amber-400 transition-colors cursor-pointer">Tienda Principal</button>
        <button @click="router.push('/crm')" class="hover:text-amber-400 transition-colors cursor-pointer">Panel Revendedor (/crm)</button>
        <button @click="scrollToSection('formulario-solicitud')" class="hover:text-amber-400 transition-colors cursor-pointer">Solicitar Panel</button>
        <button @click="contactProviderWhatsApp" class="hover:text-amber-400 transition-colors cursor-pointer">Soporte WhatsApp</button>
      </div>
      <p>© 2026 StreamePro Mayorista Central. Red Oficial de Distribución de Streaming, Videojuegos & Tecnología.</p>
      <p class="text-[11px] text-slate-600">Bahía Blanca, Buenos Aires, Argentina • Garantía Oficial y Entrega 60/90 min</p>
    </footer>

  </div>
</template>

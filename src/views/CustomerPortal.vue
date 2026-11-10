<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useResellerStore, useAdminStore } from '../store/admin'
import { useToastStore } from '../store/toast'

const route = useRoute()
const router = useRouter()

const isUnlocked = ref(false)
const searchIdentifier = ref('') // WhatsApp (ej: 11 9988-1122) o N° Cliente (CLI-9024)
const masterPin = ref('') // PIN personal de 4 dígitos
const error = ref('')
const portalTab = ref('gaming') // 'gaming' | 'streaming' | 'physical' | 'support'

const resellerStore = useResellerStore()
const adminStore = useAdminStore()
const toast = useToastStore()

// Control de visibilidad de contraseñas y guías de consola
const revealedPasswords = ref({})
const expandedGameGuides = ref({})

const togglePasswordVisibility = (key) => {
  revealedPasswords.value[key] = !revealedPasswords.value[key]
}

const toggleGameGuide = (orderId) => {
  expandedGameGuides.value[orderId] = !expandedGameGuides.value[orderId]
}

const isGamingOrder = (o) => {
  if (!o) return false
  return o.orderType === 'gaming' ||
    o.category === 'Videojuegos Digitales' ||
    Boolean(o.gameTitle) ||
    Boolean(o.licenseType)
}

const normalizeDigits = (val) => String(val || '').replace(/[^0-9]/g, '').replace(/^549?/, '').replace(/^0/, '')
const normalizeId = (val) => String(val || '').trim().toLowerCase().replace(/[^a-z0-9-]/g, '')

const matchPhoneOrIdExact = (storedPhone, storedId, extraOrderId = '') => {
  const rawQuery = searchIdentifier.value.trim()
  const qId = normalizeId(rawQuery)
  const qDigits = normalizeDigits(rawQuery)

  if (qId && qId.length >= 4) {
    if (storedId && normalizeId(storedId) === qId) return true
    if (extraOrderId && normalizeId(extraOrderId) === qId) return true
  }
  if (qDigits && qDigits.length >= 6 && storedPhone) {
    const sDigits = normalizeDigits(storedPhone)
    if (sDigits && (sDigits === qDigits || sDigits.endsWith(qDigits) || qDigits.endsWith(sDigits))) {
      return true
    }
  }
  return false
}

// Registros vinculados estrictamente al usuario autenticado
const authenticatedClients = ref([])
const authenticatedOrders = ref([])
const authenticatedPhysicalOrders = ref([])
const authenticatedUsers = ref([])

const collectAuthenticatedContext = () => {
  const phones = new Set()
  const clientIds = new Set()
  const qDigits = normalizeDigits(searchIdentifier.value)
  if (qDigits.length >= 6) phones.add(qDigits)

  authenticatedUsers.value.forEach(u => {
    if (normalizeDigits(u.phone)) phones.add(normalizeDigits(u.phone))
    if (normalizeId(u.clientNumber || u.id)) clientIds.add(normalizeId(u.clientNumber || u.id))
  })
  authenticatedOrders.value.forEach(o => {
    if (normalizeDigits(o.customerPhone)) phones.add(normalizeDigits(o.customerPhone))
    if (normalizeId(o.customerNumber)) clientIds.add(normalizeId(o.customerNumber))
  })
  authenticatedClients.value.forEach(c => {
    if (normalizeDigits(c.phone)) phones.add(normalizeDigits(c.phone))
    if (normalizeId(c.clientNumber || c.id)) clientIds.add(normalizeId(c.clientNumber || c.id))
  })

  return { phones: Array.from(phones), clientIds: Array.from(clientIds) }
}

const belongsToAuthenticated = (phone, idA, idB = '') => {
  const { phones, clientIds } = collectAuthenticatedContext()
  const p = normalizeDigits(phone)
  if (p && phones.some(ap => ap === p || ap.endsWith(p) || p.endsWith(ap))) return true
  const nA = normalizeId(idA)
  const nB = normalizeId(idB)
  if (nA && clientIds.includes(nA)) return true
  if (nB && clientIds.includes(nB)) return true
  return false
}

const unlockVault = (silent = false) => {
  error.value = ''
  const rawQuery = searchIdentifier.value.trim()
  const qId = normalizeId(rawQuery)
  const qDigits = normalizeDigits(rawQuery)
  const pin = masterPin.value.trim()

  if (qDigits.length < 6 && qId.length < 4) {
    error.value = 'Ingresa un Número de WhatsApp completo (ej: 11 2233-4455) o tu N° Cliente (ej: CLI-9024).'
    return
  }

  const matchedClients = (resellerStore.clients || []).filter(c => matchPhoneOrIdExact(c.phone, c.clientNumber, c.id))
  const matchedOrders = (adminStore.orders || []).filter(o => matchPhoneOrIdExact(o.customerPhone, o.customerNumber, o.id))
  const matchedPhysical = (adminStore.physicalOrders || []).filter(o => matchPhoneOrIdExact(o.customerPhone, o.customerNumber, o.id))
  const matchedUsers = (adminStore.registeredUsers || []).filter(u => matchPhoneOrIdExact(u.phone, u.clientNumber, u.id))

  const hasAnyRecord = matchedClients.length > 0 || matchedOrders.length > 0 || matchedPhysical.length > 0 || matchedUsers.length > 0
  if (!hasAnyRecord) {
    error.value = 'No se encontraron servicios ni pedidos activos con esos datos. Verifica tu WhatsApp o N° de Cliente.'
    if (!silent) toast.add('No encontramos tus servicios ni pedidos', 'error')
    return
  }

  // Validación estricta de PIN si existe algún PIN registrado en los registros encontrados
  const registeredPins = new Set()
  matchedClients.forEach(c => {
    if (c.pin) registeredPins.add(String(c.pin).trim())
    ;(c.profiles || []).forEach(p => { if (p.pin && p.pin !== 'N/A') registeredPins.add(String(p.pin).trim()) })
  })
  matchedOrders.forEach(o => { if (o.pin) registeredPins.add(String(o.pin).trim()) })
  matchedPhysical.forEach(o => { if (o.pin) registeredPins.add(String(o.pin).trim()) })
  matchedUsers.forEach(u => { if (u.pin) registeredPins.add(String(u.pin).trim()) })

  if (registeredPins.size > 0) {
    if (!pin) {
      error.value = 'Ingresa tu PIN de 4 dígitos para desbloquear tu Bóveda.'
      if (!silent) toast.add('Por seguridad, ingresa tu PIN de 4 dígitos', 'warning')
      return
    }
    if (!registeredPins.has(pin)) {
      error.value = 'El PIN ingresado no coincide con tu registro. Verifica tus 4 dígitos.'
      if (!silent) toast.add('PIN incorrecto para esta cuenta', 'error')
      return
    }
  }

  // Filtrar estrictamente por PIN si múltiples usuarios compartiesen registros, o mantener todos del titular
  authenticatedClients.value = matchedClients
  authenticatedOrders.value = matchedOrders
  authenticatedPhysicalOrders.value = matchedPhysical
  authenticatedUsers.value = matchedUsers
  isUnlocked.value = true

  // Elegir la mejor pestaña inicial según los servicios del cliente
  const gamingCount = matchedOrders.filter(isGamingOrder).length
  const streamCount = matchedOrders.filter(o => !isGamingOrder(o)).length + matchedClients.length
  const physCount = matchedPhysical.length

  if (route.query.tab && ['gaming', 'streaming', 'physical', 'support'].includes(String(route.query.tab))) {
    portalTab.value = String(route.query.tab)
  } else if (gamingCount > 0 && streamCount === 0) {
    portalTab.value = 'gaming'
  } else if (streamCount > 0) {
    portalTab.value = 'streaming'
  } else if (physCount > 0) {
    portalTab.value = 'physical'
  } else {
    portalTab.value = 'gaming'
  }

  if (!silent) {
    toast.add('¡Bóveda desbloqueada! Bienvenido a tu centro de servicios oficiales.', 'success')
  }
}

const refreshPortalData = async () => {
  await adminStore.syncFromCloud().catch(() => {})
  if (isUnlocked.value) {
    unlockVault(true)
  }
  toast.add('Bóveda actualizada desde la nube', 'info')
}

onMounted(async () => {
  await Promise.all([
    adminStore.initCloudSync(),
    adminStore.initStoreSettingsSync().catch(() => {})
  ])

  if (route.query.track) {
    searchIdentifier.value = String(route.query.track)
  }

  try {
    const raw = localStorage.getItem('streampro_user_profile')
    if (raw) {
      const saved = JSON.parse(raw)
      if (saved.phone && !searchIdentifier.value) {
        searchIdentifier.value = saved.phone
      }
      if (saved.pin && !masterPin.value) {
        masterPin.value = saved.pin
      }
      if (searchIdentifier.value && masterPin.value) {
        unlockVault(true)
      }
    }
  } catch {}
})

const copyToClipboard = (text, field) => {
  if (!text) return
  navigator.clipboard.writeText(String(text)).then(() => {
    toast.add(`${field} copiado al portapapeles`, 'success')
  }).catch(() => {
    toast.add('Error al copiar', 'error')
  })
}

// 1. Videojuegos Digitales del cliente autenticado (re-computado reactivo para reflejar cloud updates)
const customerGamingOrders = computed(() => {
  if (!isUnlocked.value) return []
  return (adminStore.orders || []).filter(o =>
    isGamingOrder(o) && belongsToAuthenticated(o.customerPhone, o.customerNumber, o.id)
  )
})

// 2. Pedidos Streaming pendientes (solo los no-entregados, arreglando el bug previo)
const customerStreamingPendingOrders = computed(() => {
  if (!isUnlocked.value) return []
  return (adminStore.orders || []).filter(o =>
    !isGamingOrder(o) &&
    o.status !== 'Dispatched' &&
    o.status !== 'Entregado' &&
    o.status !== 'Cancelado' &&
    belongsToAuthenticated(o.customerPhone, o.customerNumber, o.id)
  )
})

// 3. Cuentas Streaming activas (CRM local + órdenes Dispatched con accountDetails en Firestore!)
const customerStreamingActiveAccounts = computed(() => {
  if (!isUnlocked.value) return []
  const crmList = (resellerStore.clients || []).filter(c =>
    belongsToAuthenticated(c.phone, c.clientNumber, c.id)
  )
  const cloudDispatched = (adminStore.orders || [])
    .filter(o =>
      !isGamingOrder(o) &&
      (o.status === 'Dispatched' || o.status === 'Entregado') &&
      o.accountDetails &&
      o.accountDetails.email &&
      belongsToAuthenticated(o.customerPhone, o.customerNumber, o.id)
    )
    .map(o => ({
      id: `ORD-ACC-${o.id}`,
      name: o.customerName,
      phone: o.customerPhone,
      clientNumber: o.customerNumber,
      platform: o.platform,
      planName: o.planLabel || `${o.devices || 1} Pantalla(s)`,
      devices: o.devices || 1,
      account: o.accountDetails.email,
      pass: o.accountDetails.pass || '',
      profiles: o.accountDetails.profiles || [{ name: 'Perfil Asignado', pin: o.pin || 'Ver en ficha' }],
      expiry: o.expiryDate || 'Vigente (30 días)',
      resellerId: o.resellerId || 'DIRECT'
    }))

  const seenEmails = new Set(crmList.map(c => `${c.platform}::${c.account}`.toLowerCase()))
  const merged = [...crmList]
  cloudDispatched.forEach(item => {
    const sig = `${item.platform}::${item.account}`.toLowerCase()
    if (!seenEmails.has(sig)) {
      seenEmails.add(sig)
      merged.push(item)
    }
  })
  return merged
})

// 4. Pedidos Físicos estrictamente filtrados
const customerPhysicalOrders = computed(() => {
  if (!isUnlocked.value) return []
  return (adminStore.physicalOrders || []).filter(o =>
    belongsToAuthenticated(o.customerPhone, o.customerNumber, o.id)
  )
})

// 5. Tickets / Reportes de Falla del cliente
const customerSupportTickets = computed(() => {
  if (!isUnlocked.value) return []
  return (adminStore.supportTickets || []).filter(t =>
    belongsToAuthenticated(t.phone, t.clientNumber, t.orderId)
  )
})

const getRemainingDays = (dateStr) => {
  if (!dateStr || String(dateStr).includes('Vigente')) return 30
  const diffTime = new Date(dateStr) - new Date()
  const days = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  return isNaN(days) ? 30 : days
}

// Resolver WhatsApp del vendedor asignado o central oficial
const resolveSupportPhone = (record = null) => {
  if (record?.resellerId && record.resellerId !== 'DIRECT') {
    const resObj = (adminStore.resellers || []).find(r => r.id === record.resellerId)
    if (resObj?.contactNumber) return resObj.contactNumber
  }
  return adminStore.providerBankInfo?.whatsapp || adminStore.adminAuth?.whatsapp || '+54 9 291 565-9321'
}

const formatWaDigits = (rawPhone) => {
  let cleanPhone = String(rawPhone || '').replace(/[^0-9]/g, '')
  if (cleanPhone.startsWith('0')) cleanPhone = cleanPhone.substring(1)
  if (cleanPhone.length === 10) cleanPhone = `549${cleanPhone}`
  return cleanPhone || '5492915659321'
}

// Gestión de Actualización de Hogar con Netflix
const showHogarModal = ref(false)
const selectedHogarAccount = ref(null)
const hogarForm = ref({
  profileName: '',
  tvModel: ''
})

const openHogarModal = (acc) => {
  selectedHogarAccount.value = acc
  hogarForm.value = {
    profileName: acc.profiles?.[0]?.name || 'Perfil 1',
    tvModel: ''
  }
  showHogarModal.value = true
}

const confirmHogarRequest = () => {
  if (!selectedHogarAccount.value) return
  const acc = selectedHogarAccount.value
  adminStore.requestHogarUpdate({
    account: acc.account,
    profileName: hogarForm.value.profileName,
    clientName: acc.name,
    clientNumber: acc.clientNumber,
    tvModel: hogarForm.value.tvModel,
    phone: acc.phone,
    resellerId: acc.resellerId || 'DIRECT'
  })
  toast.add('¡Solicitud de actualización de Hogar enviada al sistema oficial!', 'success')
  showHogarModal.value = false
}

const sendHogarViaWhatsApp = () => {
  if (!selectedHogarAccount.value) return
  const acc = selectedHogarAccount.value
  confirmHogarRequest()
  const msg = `¡Hola! Necesito actualizar el Hogar con Netflix para mi servicio 📺\n\n` +
    `• *Cuenta:* ${acc.account}\n` +
    `• *Perfil:* ${hogarForm.value.profileName}\n` +
    `• *Cliente:* ${acc.name} (${acc.clientNumber})\n` +
    (hogarForm.value.tvModel ? `• *Dispositivo:* ${hogarForm.value.tvModel}\n` : '') +
    `\nAcabo de presionar 'Actualizar Hogar / Estoy de viaje' en mi TV. Por favor confírmenme el enlace o código para continuar viendo.`

  const cleanPhone = formatWaDigits(resolveSupportPhone(acc))
  window.open(`https://wa.me/${cleanPhone}?text=${encodeURIComponent(msg)}`, '_blank')
}

// Sistema Real de Reportes de Falla / Tickets
const showTicketModal = ref(false)
const ticketForm = ref({
  orderId: '',
  category: 'gaming',
  subject: 'Credenciales / Contraseña no funciona',
  message: '',
  resellerId: 'DIRECT'
})

const openSupportTicketModal = (category = 'gaming', item = null) => {
  ticketForm.value = {
    orderId: item?.id || '',
    category,
    subject: category === 'gaming'
      ? `Juego: ${item?.gameTitle || item?.platform || 'PlayStation'}`
      : `Servicio: ${item?.platform || 'StreamePro'}`,
    message: '',
    resellerId: item?.resellerId || 'DIRECT'
  }
  showTicketModal.value = true
}

const submitSupportTicket = () => {
  if (!ticketForm.value.message.trim()) {
    toast.add('Por favor describe el inconveniente ocurrido', 'error')
    return
  }
  const { phones, clientIds } = collectAuthenticatedContext()
  const primaryUser = authenticatedUsers.value[0] || authenticatedOrders.value[0] || authenticatedClients.value[0] || {}
  const res = adminStore.createSupportTicket({
    orderId: ticketForm.value.orderId,
    clientNumber: primaryUser.clientNumber || clientIds[0] || 'CLI',
    clientName: primaryUser.name || primaryUser.customerName || 'Cliente',
    phone: primaryUser.phone || primaryUser.customerPhone || phones[0] || searchIdentifier.value,
    category: ticketForm.value.category,
    subject: ticketForm.value.subject,
    message: ticketForm.value.message.trim(),
    resellerId: ticketForm.value.resellerId
  })
  if (res?.success) {
    toast.add(`✓ Reporte #${res.ticket.id} enviado al equipo técnico. Te responderemos a la brevedad.`, 'success')
    showTicketModal.value = false
    portalTab.value = 'support'
  }
}

const openOrderWhatsApp = (order) => {
  const cleanPhone = formatWaDigits(resolveSupportPhone(order))
  const msg = `¡Hola! Tengo una consulta sobre mi Pedido *#${order.id || order.clientNumber}* en StreamePro 🚀\n\n` +
    `👤 *Cliente:* ${order.customerName || 'Cliente'}\n` +
    `📱 *WhatsApp:* ${order.customerPhone || ''}\n` +
    (order.pin ? `🔐 *PIN de Bóveda:* ${order.pin}\n` : '') +
    `📦 *Producto:* ${isGamingOrder(order) ? `🎮 ${order.gameTitle || order.platform}` : (order.platform || 'Streaming')} - ${order.planLabel || (order.devices ? order.devices + ' Pantalla(s)' : '1 Pantalla')}\n` +
    `💰 *Total:* ${(order.clientPrice || order.total || 0).toLocaleString()} ARS\n\n` +
    `Adjunto mi consulta / comprobante de pago.`

  window.open(`https://wa.me/${cleanPhone}?text=${encodeURIComponent(msg)}`, '_blank')
}

const openPhysicalOrderWhatsApp = (order) => {
  const cleanPhone = formatWaDigits(resolveSupportPhone(order))
  const msg = `¡Hola! Tengo una consulta sobre mi Pedido Físico *#${order.id || order.clientNumber}* en StreamePro 📦\n\n` +
    `👤 *Cliente:* ${order.customerName || 'Cliente'}\n` +
    `📱 *WhatsApp:* ${order.customerPhone || ''}\n` +
    (order.pin ? `🔐 *PIN de Bóveda:* ${order.pin}\n` : '') +
    `📦 *Dirección:* ${order.shippingAddress || 'A coordinar'}\n` +
    `💰 *Total:* ${(order.totalAmount || 0).toLocaleString()} ARS\n\n` +
    `Consulto por disponibilidad y coordinación de entrega.`

  window.open(`https://wa.me/${cleanPhone}?text=${encodeURIComponent(msg)}`, '_blank')
}

const handleCustomerRenewal = (acc) => {
  const result = adminStore.requestRenewal({
    clientId: acc.id,
    clientNumber: acc.clientNumber,
    clientName: acc.name,
    platform: acc.platform,
    planName: acc.planName || `${acc.devices} Pantallas`,
    resellerId: acc.resellerId || 'DIRECT',
    currentCredentials: {
      account: acc.account,
      pass: acc.pass,
      profiles: acc.profiles,
      phone: acc.phone
    }
  })

  if (result.success) {
    toast.add(`¡Solicitud de renovación registrada con éxito! Código: ${result.renewal.id}`, 'success')
  }
}

const getGameInstallSteps = (order) => {
  const plat = String(order.gamePlatform || order.platform || '').toLowerCase()
  const isPrim = order.licenseType === 'primary' || (order.licenseLabel && order.licenseLabel.toLowerCase().includes('primaria'))
  if (plat.includes('pc') || plat.includes('steam')) {
    return [
      'Inicia sesión en Steam con el usuario/código entregado arriba.',
      'Descarga el juego desde tu Biblioteca Oficial de Steam.',
      'Mantén las reglas de garantía enviadas (sin modificar correo ni contraseña).'
    ]
  }
  if (plat.includes('switch') || plat.includes('xbox')) {
    return [
      'Agrega el perfil recibido en tu consola e inicia sesión desde Configuración.',
      'Descarga el juego desde la tienda oficial eShop / Microsoft Store.',
      isPrim ? 'Después de descargar, inicia el juego desde tu usuario habitual.' : 'Juega manteniendo conexión desde el perfil asignado.'
    ]
  }
  if (isPrim) {
    return [
      'En tu consola PS4/PS5 selecciona "Agregar Usuario Nuevo" -> "Comenzar" (NO como invitado).',
      'Ingresa el Correo Electrónico y Contraseña mostrados en tu ficha de pedido.',
      'En PS5 habilita "Uso compartido de consola y juego offline" (o en PS4 activa "Como PS4 Principal").',
      'Entra a Biblioteca, inicia la descarga y luego vuelve a tu usuario personal para jugar con tus propios trofeos.'
    ]
  }
  return [
    'En tu consola PS4/PS5 selecciona "Agregar Usuario Nuevo" -> "Comenzar" (NO como invitado).',
    'Ingresa el Correo Electrónico y Contraseña asignados en este pedido.',
    'Selecciona "NO activar" como principal si la consola pregunta, ve a Biblioteca y descarga el juego.',
    'Para jugar en Cuenta Secundaria, abre siempre el juego desde este usuario provisto con conexión a internet.'
  ]
}
</script>

<template>
  <div class="max-w-3xl mx-auto mt-6">
    <Transition name="fade" mode="out-in">
      <!-- Estado Bloqueado: Acceso Seguro -->
      <div v-if="!isUnlocked" class="bg-slate-900/60 backdrop-blur-xl p-8 sm:p-10 rounded-3xl border border-slate-700/50 shadow-2xl text-center relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-b from-brand-accent/5 to-transparent pointer-events-none"></div>
        
        <div class="w-20 h-20 bg-slate-800 rounded-2xl flex items-center justify-center mx-auto mb-6 border border-slate-700 shadow-inner relative">
          <div class="absolute inset-0 bg-brand-accent/20 animate-pulse rounded-2xl"></div>
          <svg class="w-10 h-10 text-brand-accent relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"></path></svg>
        </div>
        
        <h2 class="text-3xl font-black mb-2 text-white">Bóveda de Credenciales & Pedidos</h2>
        <p class="text-slate-400 mb-6 max-w-md mx-auto text-xs leading-relaxed">
          Accede a tus cuentas oficiales de PlayStation PS5/PS4/PS3, suscripciones de streaming, seguimiento de pedidos físicos y reportes de garantía.
        </p>
        
        <form @submit.prevent="unlockVault(false)" class="max-w-md mx-auto space-y-3.5 text-left">
          <div>
            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-300 mb-1">
              WhatsApp de Compra o N° Cliente / Orden
            </label>
            <input 
              v-model="searchIdentifier" 
              type="text" 
              placeholder="Ej: 11 9988-1122 o CLI-9024" 
              class="w-full bg-slate-950 border border-slate-700 focus:border-brand-accent rounded-xl px-4 py-3 text-sm text-white outline-none transition-colors"
            >
          </div>

          <div>
            <div class="flex justify-between items-center mb-1">
              <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-300">
                PIN de Seguridad de Perfil *
              </label>
              <span class="text-[10px] text-slate-500 font-mono">4 dígitos asignados al comprar</span>
            </div>
            <input 
              v-model="masterPin" 
              type="password" 
              placeholder="••••" 
              maxlength="6"
              class="w-full bg-slate-950 border border-slate-700 focus:border-brand-accent rounded-xl px-4 py-3 text-center text-xl tracking-[0.5em] text-white outline-none transition-colors"
            >
          </div>

          <div v-if="error" class="p-3 bg-red-950/40 border border-red-500/40 rounded-xl text-red-300 text-xs text-center font-medium">
            ⚠️ {{ error }}
          </div>

          <button type="submit" class="w-full relative group overflow-hidden rounded-xl shadow-lg mt-2 cursor-pointer">
            <div class="absolute inset-0 bg-gradient-to-r from-brand-accent to-blue-500 transition-all duration-300 group-hover:scale-105"></div>
            <div class="relative px-6 py-3.5 flex items-center justify-center gap-2 text-slate-950 font-black text-sm">
              <span>Desbloquear Bóveda Segura</span>
              <span>→</span>
            </div>
          </button>
        </form>
      </div>

      <!-- Estado Desbloqueado: Mis Juegos, Streaming, Físicos y Soporte -->
      <div v-else class="space-y-6">
        <div class="flex flex-wrap justify-between items-center gap-3 bg-slate-900/60 p-4 rounded-2xl border border-slate-800 backdrop-blur-md">
          <div class="flex flex-wrap items-center gap-2">
            <button 
              @click="portalTab = 'gaming'"
              :class="[
                'px-3.5 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer',
                portalTab === 'gaming' 
                  ? 'bg-indigo-600 text-white font-black shadow-md' 
                  : 'bg-slate-950 text-slate-400 hover:text-white border border-slate-800'
              ]"
            >
              <span>🎮</span>
              <span>Mis Juegos ({{ customerGamingOrders.length }})</span>
            </button>
            <button 
              @click="portalTab = 'streaming'"
              :class="[
                'px-3.5 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer',
                portalTab === 'streaming' 
                  ? 'bg-brand-accent text-slate-950 font-black shadow-md' 
                  : 'bg-slate-950 text-slate-400 hover:text-white border border-slate-800'
              ]"
            >
              <span>🎬</span>
              <span>Streaming ({{ customerStreamingActiveAccounts.length + customerStreamingPendingOrders.length }})</span>
            </button>
            <button 
              @click="portalTab = 'physical'"
              :class="[
                'px-3.5 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer',
                portalTab === 'physical' 
                  ? 'bg-amber-400 text-slate-950 font-black shadow-md' 
                  : 'bg-slate-950 text-slate-400 hover:text-white border border-slate-800'
              ]"
            >
              <span>📦</span>
              <span>Físicos ({{ customerPhysicalOrders.length }})</span>
            </button>
            <button 
              @click="portalTab = 'support'"
              :class="[
                'px-3.5 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer',
                portalTab === 'support' 
                  ? 'bg-rose-600 text-white font-black shadow-md' 
                  : 'bg-slate-950 text-slate-400 hover:text-white border border-slate-800'
              ]"
            >
              <span>🆘</span>
              <span>Ayuda ({{ customerSupportTickets.length }})</span>
            </button>
          </div>
          <div class="flex items-center gap-2">
            <button 
              @click="refreshPortalData" 
              class="px-3 py-2 bg-slate-950 hover:bg-slate-800 text-xs font-bold rounded-xl transition-colors border border-slate-800 text-sky-400 flex items-center gap-1.5 cursor-pointer"
              title="Actualizar datos con la nube"
            >
              <span>🔄</span>
              <span>Actualizar</span>
            </button>
            <button @click="isUnlocked = false; masterPin = ''; authenticatedClients = []; authenticatedOrders = []; authenticatedPhysicalOrders = []; authenticatedUsers = []" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-xs font-bold rounded-xl transition-colors border border-slate-700 text-slate-300 cursor-pointer">
              🔒 Salir
            </button>
          </div>
        </div>

        <!-- ==================== PESTAÑA 1: MIS JUEGOS DIGITALES ==================== -->
        <div v-if="portalTab === 'gaming'" class="space-y-4">
          <div v-if="customerGamingOrders.length === 0" class="text-center py-12 bg-slate-900/40 rounded-2xl border border-slate-800 space-y-3">
            <span class="text-4xl block">🎮</span>
            <p class="text-slate-300 text-sm font-bold">Aún no tienes videojuegos digitales registrados en tu Bóveda.</p>
            <button @click="router.push('/juegos')" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-black shadow cursor-pointer">
              Explorar Catálogo de Videojuegos →
            </button>
          </div>

          <div v-else class="space-y-4">
            <div 
              v-for="order in customerGamingOrders" 
              :key="order.id"
              :class="[
                'bg-slate-900/60 rounded-2xl border overflow-hidden backdrop-blur-md shadow-xl',
                (order.status === 'Dispatched' || order.status === 'Entregado')
                  ? 'border-emerald-500/40'
                  : order.status === 'Cancelado'
                    ? 'border-rose-500/40'
                    : 'border-amber-500/40'
              ]"
            >
              <div class="bg-slate-950/90 px-5 py-4 border-b border-slate-800 flex flex-wrap justify-between items-start gap-3">
                <div>
                  <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-[11px] font-mono font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 px-2 py-0.5 rounded">
                      #{{ order.id }}
                    </span>
                    <span class="text-[11px] font-mono font-bold bg-slate-800 text-slate-300 px-2 py-0.5 rounded">
                      {{ order.gamePlatform || 'PlayStation' }}
                    </span>
                    <span class="text-[11px] font-bold bg-sky-500/15 text-sky-300 border border-sky-500/30 px-2 py-0.5 rounded">
                      {{ order.licenseLabel || order.planLabel || 'Cuenta Oficial' }}
                    </span>
                  </div>
                  <h3 class="text-white font-black text-base mt-1.5">🎮 {{ order.gameTitle || order.platform }}</h3>
                  <p class="text-[11px] text-slate-400">PlayStation Store Oficial • StreamePro Garantizado</p>
                </div>

                <div class="flex flex-col items-end gap-1.5">
                  <span v-if="order.status === 'Dispatched' || order.status === 'Entregado'" class="text-xs font-black bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 px-3 py-1 rounded-full flex items-center gap-1.5">
                    <span>✅</span>
                    <span>Entregado • Listo para Jugar</span>
                  </span>
                  <span v-else-if="order.status === 'Cancelado'" class="text-xs font-bold bg-rose-500/20 text-rose-300 border border-rose-500/40 px-3 py-1 rounded-full">
                    ❌ Pedido Cancelado
                  </span>
                  <span v-else class="text-xs font-bold bg-amber-400 text-slate-950 px-3 py-1 rounded-full flex items-center gap-1.5 animate-pulse">
                    <span>⏳</span>
                    <span>En Preparación (Activando...)</span>
                  </span>
                  <span v-if="order.dispatchedAt" class="text-[10px] text-slate-500 font-mono">Entregado: {{ order.dispatchedAt }}</span>
                </div>
              </div>

              <div class="p-5 space-y-4">
                <!-- CREDENCIALES DEL JUEGO SI YA FUE DESPACHADO -->
                <div v-if="(order.status === 'Dispatched' || order.status === 'Entregado') && order.accountDetails && (order.accountDetails.email || order.accountDetails.pin)" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div v-if="order.accountDetails.email" class="bg-[#020617] p-3.5 rounded-xl border border-emerald-500/30 space-y-1">
                    <span class="text-[10px] font-bold uppercase text-emerald-300 block">Correo / Cuenta / Clave Oficial</span>
                    <div class="flex items-center justify-between gap-2 font-mono">
                      <span class="text-white text-xs truncate">{{ order.accountDetails.email }}</span>
                      <button @click="copyToClipboard(order.accountDetails.email, 'Correo / Clave')" class="px-2 py-1 rounded-lg bg-slate-800 hover:bg-indigo-600 text-slate-200 text-[10px] font-bold cursor-pointer">Copiar</button>
                    </div>
                  </div>

                  <div v-if="order.accountDetails.pass" class="bg-[#020617] p-3.5 rounded-xl border border-emerald-500/30 space-y-1">
                    <span class="text-[10px] font-bold uppercase text-emerald-300 block">Contraseña Oficial</span>
                    <div class="flex items-center justify-between gap-2 font-mono">
                      <span class="text-white text-xs truncate">{{ revealedPasswords[order.id] ? order.accountDetails.pass : '••••••••••••' }}</span>
                      <div class="flex items-center gap-1">
                        <button @click="togglePasswordVisibility(order.id)" class="px-2 py-1 rounded-lg bg-slate-800 text-slate-300 text-[10px] cursor-pointer">{{ revealedPasswords[order.id] ? '🙈' : '👁️' }}</button>
                        <button @click="copyToClipboard(order.accountDetails.pass, 'Contraseña')" class="px-2 py-1 rounded-lg bg-slate-800 hover:bg-indigo-600 text-slate-200 text-[10px] font-bold cursor-pointer">Copiar</button>
                      </div>
                    </div>
                  </div>

                  <div v-if="order.accountDetails.pin" class="sm:col-span-2 bg-[#020617] p-3 rounded-xl border border-indigo-500/30 flex items-center justify-between font-mono text-xs">
                    <div>
                      <span class="text-slate-400 text-[10px] uppercase block">Código de Verificación / PIN 2FA / Extra</span>
                      <strong class="text-amber-300">{{ order.accountDetails.pin }}</strong>
                    </div>
                    <button @click="copyToClipboard(order.accountDetails.pin, 'Código / PIN')" class="px-2.5 py-1 rounded-lg bg-slate-800 text-amber-300 text-[10px] font-bold cursor-pointer">Copiar Código</button>
                  </div>

                  <div v-if="order.accountDetails.notes" class="sm:col-span-2 p-3 rounded-xl bg-indigo-950/30 border border-indigo-500/20 text-xs text-slate-200">
                    <strong class="text-indigo-300 block text-[10px] uppercase mb-0.5">Indicaciones de Entrega:</strong>
                    {{ order.accountDetails.notes }}
                  </div>
                </div>

                <!-- ESTADO PENDIENTE O ENTREGADO SIN CREDENCIAL CARGADA AUN -->
                <div v-else-if="order.status === 'Dispatched' || order.status === 'Entregado'" class="p-3.5 bg-emerald-950/30 border border-emerald-500/30 rounded-xl text-xs text-emerald-200">
                  ✅ Tu juego ya fue marcado como entregado. Si recibiste tus accesos directamente por WhatsApp o necesitas una copia aquí, toca en <strong>Consultar por WhatsApp</strong>.
                </div>
                <div v-else class="p-3.5 bg-slate-950/80 border border-slate-800 rounded-xl text-xs text-slate-300 space-y-1">
                  <div class="font-bold text-amber-300">⚡ Pedido en proceso de activación oficial</div>
                  <p class="text-[11px] text-slate-400">En breve aparecerán aquí tu usuario/contraseña de PlayStation Store y los pasos para descargar en tu consola.</p>
                </div>

                <!-- GUÍA INTERACTIVA DE INSTALACIÓN -->
                <div class="bg-slate-950/70 rounded-xl border border-slate-800 overflow-hidden">
                  <button
                    type="button"
                    @click="toggleGameGuide(order.id)"
                    class="w-full px-4 py-2.5 flex items-center justify-between text-xs font-bold text-indigo-300 hover:bg-slate-900/70 transition cursor-pointer"
                  >
                    <span>📋 Guía Paso a Paso para Instalar y Cuidar la Garantía</span>
                    <span>{{ expandedGameGuides[order.id] ? '▲ Ocultar' : '▼ Ver Pasos' }}</span>
                  </button>
                  <div v-if="expandedGameGuides[order.id]" class="px-4 pb-4 pt-1 space-y-2 border-t border-slate-800/70 text-[11px] text-slate-300">
                    <div v-for="(step, idx) in getGameInstallSteps(order)" :key="idx" class="flex items-start gap-2">
                      <span class="w-5 h-5 rounded-full bg-indigo-500/20 text-indigo-300 font-black flex items-center justify-center shrink-0 text-[10px]">{{ idx + 1 }}</span>
                      <span class="leading-relaxed">{{ step }}</span>
                    </div>
                    <div class="p-2.5 rounded-lg bg-amber-500/10 border border-amber-500/30 text-amber-200 text-[10px]">
                      ⚠️ <strong>Regla de Garantía Oficial:</strong> No modifiques el correo, contraseña ni la verificación en 2 pasos de la cuenta entregada ni la compartas en otras consolas.
                    </div>
                  </div>
                </div>

                <!-- ACCIONES CLIENTE PARA JUEGO -->
                <div class="flex flex-wrap gap-2">
                  <button
                    @click="openOrderWhatsApp(order)"
                    class="flex-1 bg-emerald-600/20 hover:bg-emerald-600/30 border border-emerald-500/40 text-emerald-300 hover:text-white font-bold py-2.5 px-3 rounded-xl text-xs flex items-center justify-center gap-1.5 transition cursor-pointer"
                  >
                    <span>💬</span>
                    <span>WhatsApp Soporte</span>
                  </button>
                  <button
                    @click="openSupportTicketModal('gaming', order)"
                    class="px-4 py-2.5 rounded-xl bg-rose-500/15 hover:bg-rose-500/30 border border-rose-500/30 text-rose-300 hover:text-white font-bold text-xs flex items-center gap-1.5 transition cursor-pointer"
                  >
                    <span>🆘</span>
                    <span>Reportar Falla / Ayuda</span>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ==================== PESTAÑA 2: SUSCRIPCIONES STREAMING DIGITALES ==================== -->
        <div v-else-if="portalTab === 'streaming'" class="space-y-6">
          <div v-if="customerStreamingPendingOrders.length > 0" class="space-y-3">
            <h4 class="text-xs font-bold text-amber-400 uppercase tracking-wider flex items-center gap-2">
              <span class="w-2 h-2 rounded-full bg-amber-400 animate-ping"></span>
              <span>Suscripciones en Proceso de Activación ({{ customerStreamingPendingOrders.length }})</span>
            </h4>
            <div 
              v-for="order in customerStreamingPendingOrders" 
              :key="order.id"
              class="bg-[#020617] border border-amber-500/40 p-5 rounded-2xl space-y-3 shadow-xl"
            >
              <div class="flex flex-wrap justify-between items-start gap-3">
                <div>
                  <div class="flex items-center gap-2">
                    <span class="text-xs font-mono font-bold bg-amber-400/20 text-amber-300 border border-amber-400/30 px-2 py-0.5 rounded">
                      Orden #{{ order.id }}
                    </span>
                    <h4 class="text-white font-bold text-sm">{{ order.platform }} - {{ order.planLabel || (order.devices ? order.devices + ' Pantalla(s)' : '1 Pantalla') }}</h4>
                  </div>
                  <p class="text-xs text-slate-300 mt-1">
                    Registrado a nombre de: <strong class="text-white">{{ order.customerName }}</strong> ({{ order.customerNumber }})
                  </p>
                </div>
                <div class="flex items-center gap-2">
                  <span v-if="order.pin" class="text-xs font-mono font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 px-2.5 py-1 rounded-lg">
                    🔐 PIN: {{ order.pin }}
                  </span>
                  <span class="text-xs font-bold bg-amber-400 text-slate-950 px-3 py-1 rounded-full shadow flex items-center gap-1.5 animate-pulse">
                    <span>⏳</span>
                    <span>En Preparación (10 min a 1 hora)</span>
                  </span>
                </div>
              </div>

              <div class="bg-slate-950/80 p-3.5 rounded-xl border border-slate-800 text-xs text-slate-300 space-y-1.5">
                <div class="flex items-center gap-2 text-emerald-400 font-bold">
                  <span>✓</span>
                  <span>{{ order.hasReceipt || order.receiptStatus ? 'Comprobante recibido en la nube (en verificación)' : 'Pedido registrado en el sistema oficial' }}</span>
                </div>
                <p class="text-slate-400 text-[11px] leading-relaxed">
                  Tus credenciales (correo, contraseña y PIN asignado) aparecerán aquí automáticamente apenas completemos la activación.
                </p>
              </div>

              <div class="pt-1 flex flex-wrap gap-2">
                <button 
                  @click="openOrderWhatsApp(order)"
                  class="flex-1 bg-emerald-600/20 hover:bg-emerald-600/30 border border-emerald-500/40 text-emerald-300 hover:text-white font-bold py-2.5 px-4 rounded-xl text-xs flex items-center justify-center gap-2 transition-all cursor-pointer"
                >
                  <span class="text-base">💬</span>
                  <span>Contactar Asesor por WhatsApp sobre esta Orden</span>
                  <span class="text-[10px] font-mono opacity-80">{{ resolveSupportPhone(order) }}</span>
                </button>
              </div>
            </div>
          </div>

          <!-- CUENTAS ACTIVAS ENTREGADAS -->
          <div v-if="customerStreamingActiveAccounts.length === 0 && customerStreamingPendingOrders.length === 0" class="text-center py-12 bg-slate-900/40 rounded-2xl border border-slate-800 text-slate-400 text-xs">
            No tienes suscripciones de streaming activas registradas aún.
          </div>

          <div v-for="acc in customerStreamingActiveAccounts" :key="acc.id" class="bg-slate-900/60 rounded-2xl border border-slate-700/50 overflow-hidden relative group backdrop-blur-md shadow-xl">
            <div class="bg-slate-950/70 px-6 py-4 border-b border-slate-800 flex justify-between items-center">
              <div>
                <h3 class="font-black text-lg text-white">{{ acc.platform }}</h3>
                <span class="text-xs text-blue-400 font-semibold">{{ acc.planName || acc.devices + ' Pantallas' }}</span>
              </div>
              <div class="text-right">
                <span :class="[
                  'text-xs font-bold px-3 py-1 rounded-full border',
                  getRemainingDays(acc.expiry) <= 2 ? 'bg-amber-500/20 text-amber-300 border-amber-500/40 animate-pulse' : 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40'
                ]">
                  Vence: {{ acc.expiry }} ({{ getRemainingDays(acc.expiry) }}d)
                </span>
              </div>
            </div>
            
            <div class="p-6 space-y-5">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <p class="text-xs font-semibold text-slate-400 mb-1 uppercase tracking-wider">Correo Electrónico Oficial</p>
                  <div class="bg-[#020617] p-3 rounded-xl font-mono flex justify-between items-center border border-slate-800 hover:border-brand-accent/40 transition-colors">
                    <span class="text-slate-200 text-xs truncate">{{ acc.account }}</span>
                    <button @click="copyToClipboard(acc.account, 'Correo')" class="text-slate-500 hover:text-brand-accent p-1 cursor-pointer" title="Copiar Correo">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                    </button>
                  </div>
                </div>
                <div>
                  <p class="text-xs font-semibold text-slate-400 mb-1 uppercase tracking-wider">Contraseña de Acceso</p>
                  <div class="bg-[#020617] p-3 rounded-xl font-mono flex justify-between items-center border border-slate-800 hover:border-brand-accent/40 transition-colors">
                    <span class="text-slate-200 text-xs">{{ acc.pass ? (revealedPasswords[acc.id] ? acc.pass : '••••••••') : 'Consultar con soporte' }}</span>
                    <div class="flex items-center gap-1" v-if="acc.pass">
                      <button @click="togglePasswordVisibility(acc.id)" class="text-slate-400 hover:text-white text-xs p-1 cursor-pointer">{{ revealedPasswords[acc.id] ? '🙈' : '👁️' }}</button>
                      <button @click="copyToClipboard(acc.pass, 'Contraseña')" class="text-slate-500 hover:text-brand-accent p-1 cursor-pointer" title="Copiar Contraseña">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
              
              <div>
                <p class="text-xs font-semibold text-slate-400 mb-2 uppercase tracking-wider">
                  Tus Pantallas Asignadas (Nombre de Perfil & PIN Provisto):
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                  <div 
                    v-for="(p, pIdx) in acc.profiles" 
                    :key="pIdx"
                    class="bg-[#020617] p-3 rounded-xl border border-slate-800 flex justify-between items-center"
                  >
                    <div>
                      <p class="text-xs font-bold text-white font-mono flex items-center gap-1.5">
                        <span class="text-brand-accent">👤</span> {{ p.name }}
                      </p>
                      <p class="text-[11px] text-slate-400 mt-0.5">
                        PIN de Bloqueo: <strong class="text-amber-300 font-mono text-xs">{{ p.pin }}</strong>
                      </p>
                    </div>
                    <button 
                      v-if="p.pin && p.pin !== 'N/A'" 
                      @click="copyToClipboard(p.pin, `PIN de ${p.name}`)" 
                      class="text-xs bg-slate-900 hover:bg-slate-800 text-amber-300 border border-slate-700 px-2 py-1 rounded-lg font-bold cursor-pointer"
                    >
                      Copiar PIN
                    </button>
                  </div>
                </div>
              </div>
              
              <div class="flex flex-wrap gap-2.5 pt-2 border-t border-slate-800/80">
                <button 
                  v-if="acc.platform && acc.platform.toLowerCase().includes('netflix')"
                  @click="openHogarModal(acc)" 
                  class="w-full sm:w-auto flex-1 bg-red-600/20 hover:bg-red-600/30 border border-red-500/40 text-red-300 hover:text-white text-xs font-bold py-2.5 px-3 rounded-xl transition-all flex justify-center items-center gap-1.5 shadow-sm cursor-pointer"
                >
                  <span>🏠</span>
                  <span>Actualizar Hogar</span>
                </button>
                <button @click="openSupportTicketModal('streaming', acc)" class="flex-1 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-xs font-semibold py-2.5 px-3 rounded-xl transition-colors flex justify-center items-center gap-1.5 text-slate-300 cursor-pointer">
                  <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                  <span>Reportar Falla</span>
                </button>
                <button @click="handleCustomerRenewal(acc)" class="flex-1 bg-brand-accent/10 hover:bg-brand-accent/20 border border-brand-accent/30 text-brand-accent text-xs font-bold py-2.5 px-3 rounded-xl transition-colors flex justify-center items-center gap-1.5 cursor-pointer">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                  <span>Solicitar Renovación</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- ==================== PESTAÑA 3: HISTORIAL DE PEDIDOS FÍSICOS ==================== -->
        <div v-else-if="portalTab === 'physical'" class="space-y-4">
          <div v-if="customerPhysicalOrders.length === 0" class="text-center py-12 bg-slate-900/40 rounded-2xl border border-slate-800 text-slate-400 text-xs">
            No tienes pedidos de productos físicos registrados con estos datos.
          </div>

          <div v-else class="space-y-4">
            <div 
              v-for="order in customerPhysicalOrders" 
              :key="order.id" 
              class="bg-slate-900/60 rounded-2xl border border-slate-800 overflow-hidden backdrop-blur-md shadow-xl"
            >
              <div class="bg-slate-950 px-6 py-4 border-b border-slate-800 flex flex-wrap justify-between items-center gap-2">
                <div class="flex items-center gap-2.5">
                  <span class="text-xs font-mono font-bold text-amber-400 bg-amber-400/10 px-2.5 py-1 rounded-lg border border-amber-400/30">
                    {{ order.id }}
                  </span>
                  <span class="text-xs text-slate-300 font-mono">{{ order.createdAt }}</span>
                </div>
                <div class="flex items-center gap-2">
                  <span :class="[
                    'text-xs font-bold px-3 py-1 rounded-full border flex items-center gap-1.5',
                    (order.status === 'Confirmado' || order.status === 'Aprobado') ? 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30' :
                    order.status === 'Recibido' ? 'bg-sky-500/15 text-sky-300 border-sky-500/30 animate-pulse' :
                    order.status === 'Cancelado' ? 'bg-red-500/15 text-red-300 border-red-500/30' :
                    'bg-amber-500/15 text-amber-300 border-amber-500/30 animate-pulse'
                  ]">
                    <span :class="[
                      'w-1.5 h-1.5 rounded-full',
                      (order.status === 'Confirmado' || order.status === 'Aprobado') ? 'bg-emerald-400' :
                      order.status === 'Recibido' ? 'bg-sky-400' :
                      order.status === 'Cancelado' ? 'bg-red-400' :
                      'bg-amber-400'
                    ]"></span>
                    {{ 
                      (order.status === 'Confirmado' || order.status === 'Aprobado') ? 'Confirmado' :
                      order.status === 'Recibido' ? 'Recibido (Vendedor Contactando)' :
                      order.status === 'Cancelado' ? 'Cancelado' :
                      'Pendiente'
                    }}
                  </span>
                </div>
              </div>

              <div class="p-6 space-y-4">
                <div class="space-y-2">
                  <div v-for="(pItem, pIdx) in order.items" :key="pIdx" class="flex items-center gap-3 bg-slate-950 p-3 rounded-xl border border-slate-800/80">
                    <img v-if="pItem.image" :src="pItem.image" :alt="pItem.name" class="w-12 h-12 object-contain bg-white rounded-lg p-1 shrink-0">
                    <div class="flex-1 min-w-0">
                      <h4 class="font-bold text-white text-xs truncate">{{ pItem.name }}</h4>
                      <div class="text-[11px] text-slate-400 mt-0.5">
                        Cantidad: <strong class="text-slate-200">{{ pItem.quantity }} un.</strong> · Precio: <span class="text-amber-400 font-bold">${{ pItem.priceNumeric ? pItem.priceNumeric.toLocaleString() : pItem.price }}</span>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs bg-slate-950/70 p-4 rounded-xl border border-slate-800/70">
                  <div>
                    <span class="text-slate-400 block text-[11px] uppercase tracking-wider font-semibold">Dirección de Entrega:</span>
                    <span class="text-white font-medium">{{ order.shippingAddress }}</span>
                  </div>
                  <div class="sm:text-right">
                    <span class="text-slate-400 block text-[11px] uppercase tracking-wider font-semibold">Total del Pedido:</span>
                    <span class="text-xl font-black text-amber-400">${{ (order.totalAmount || 0).toLocaleString() }} ARS</span>
                  </div>
                </div>

                <div v-if="order.status === 'Pendiente'" class="p-3.5 bg-amber-500/10 border border-amber-500/30 rounded-xl text-xs text-amber-300 flex items-start gap-2.5">
                  <span class="text-base shrink-0">⚠️</span>
                  <div>
                    <strong class="font-bold block uppercase text-[11px] tracking-wider text-amber-400">Paso 1: Pedido Pendiente (No transferir aún)</strong>
                    <span class="text-slate-300 leading-relaxed block mt-0.5">
                      Un asesor se comunicará contigo por WhatsApp para confirmar el stock de tu producto y coordinar el envío.
                    </span>
                  </div>
                </div>

                <div v-else-if="order.status === 'Recibido'" class="p-3.5 bg-sky-500/10 border border-sky-500/30 rounded-xl text-xs text-sky-300 flex items-start gap-2.5">
                  <span class="text-base shrink-0">📞</span>
                  <div>
                    <strong class="font-bold block uppercase text-[11px] tracking-wider text-sky-400">Paso 2: En contacto con Asesor</strong>
                    <span class="text-slate-300 leading-relaxed block mt-0.5">
                      Un asesor se encuentra en contacto contigo para coordinar el costo de envío y brindarte los datos para el pago seguro.
                    </span>
                  </div>
                </div>

                <div v-else-if="order.status === 'Confirmado' || order.status === 'Aprobado'" class="p-3.5 bg-emerald-500/10 border border-emerald-500/30 rounded-xl text-xs text-emerald-300 flex items-start gap-2.5">
                  <span class="text-base shrink-0">✅</span>
                  <div>
                    <strong class="font-bold block uppercase text-[11px] tracking-wider text-emerald-400">Paso 3: ¡Compra Confirmada!</strong>
                    <span class="text-slate-300 leading-relaxed block mt-0.5">
                      Tu pedido ha sido confirmado con éxito y se encuentra en proceso de preparación y despacho a tu dirección.
                    </span>
                  </div>
                </div>

                <div v-if="order.status === 'Cancelado' && order.cancelReason" class="p-3 bg-red-500/10 border border-red-500/20 rounded-xl text-xs text-red-300">
                  <strong>Motivo de cancelación:</strong> {{ order.cancelReason }}
                </div>

                <div class="pt-2 flex flex-wrap gap-2">
                  <button 
                    @click="openPhysicalOrderWhatsApp(order)"
                    class="flex-1 bg-emerald-600/20 hover:bg-emerald-600/30 border border-emerald-500/40 text-emerald-300 hover:text-white font-bold py-2.5 px-4 rounded-xl text-xs flex items-center justify-center gap-2 transition-all cursor-pointer"
                  >
                    <span class="text-base">💬</span>
                    <span>Contactar Asesor por WhatsApp sobre este Pedido</span>
                    <span class="text-[10px] font-mono opacity-80">{{ resolveSupportPhone(order) }}</span>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ==================== PESTAÑA 4: REPORTES & AYUDA EN GARANTÍA ==================== -->
        <div v-else-if="portalTab === 'support'" class="space-y-4">
          <div class="bg-slate-900/60 border border-slate-800 p-5 rounded-2xl flex flex-wrap items-center justify-between gap-3">
            <div>
              <h4 class="text-sm font-black text-white">Centro de Ayuda y Reportes de Garantía</h4>
              <p class="text-xs text-slate-400">Envía reportes sobre credenciales, candados o renovaciones y sigue su estado en tiempo real.</p>
            </div>
            <button
              type="button"
              @click="openSupportTicketModal('general')"
              class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-black text-xs shadow cursor-pointer"
            >
              ➕ Nuevo Reporte / Consulta
            </button>
          </div>

          <div v-if="customerSupportTickets.length === 0" class="text-center py-10 bg-slate-900/40 rounded-2xl border border-slate-800 text-slate-400 text-xs">
            No tienes tickets de soporte abiertos. ¡Todos tus servicios funcionan con normalidad!
          </div>

          <div v-else class="space-y-3">
            <div v-for="t in customerSupportTickets" :key="t.id" class="bg-slate-900/60 border border-slate-800 p-4 rounded-2xl space-y-2 text-xs">
              <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                  <span class="font-mono font-bold text-slate-300 bg-slate-950 px-2 py-0.5 rounded border border-slate-800">{{ t.id }}</span>
                  <strong class="text-white">{{ t.subject }}</strong>
                </div>
                <span :class="['px-2.5 py-0.5 rounded-full text-[10px] font-black', t.status === 'Resuelto' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-amber-500/20 text-amber-300 border border-amber-500/30']">
                  {{ t.status }}
                </span>
              </div>
              <p class="text-slate-300">{{ t.message }}</p>
              <div v-if="t.adminReply" class="p-3 rounded-xl bg-emerald-950/30 border border-emerald-500/30 text-emerald-200">
                <strong class="block text-[10px] uppercase text-emerald-400 mb-0.5">Respuesta de Soporte Oficial:</strong>
                {{ t.adminReply }}
              </div>
              <div class="text-[10px] text-slate-500 font-mono">{{ t.dateLabel }}</div>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- MODAL: ACTUALIZAR HOGAR CON NETFLIX -->
    <div v-if="showHogarModal && selectedHogarAccount" class="fixed inset-0 z-[80] flex items-center justify-center p-3 sm:p-5 pt-16 sm:pt-6 pb-6 overflow-y-auto overscroll-contain bg-black/85 backdrop-blur-md animate-in fade-in duration-200">
      <div class="bg-slate-900 border border-slate-700 w-full max-w-lg rounded-3xl p-6 shadow-2xl relative space-y-4">
        <div class="flex justify-between items-start border-b border-slate-800 pb-3">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-red-600/20 text-red-400 border border-red-500/30 flex items-center justify-center text-xl">
              🏠
            </div>
            <div>
              <h3 class="text-xl font-black text-white">Actualizar Hogar con Netflix</h3>
              <p class="text-xs text-slate-400 font-mono">{{ selectedHogarAccount.account }}</p>
            </div>
          </div>
          <button @click="showHogarModal = false" type="button" class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-slate-800/90 hover:bg-slate-700 active:scale-95 text-slate-300 hover:text-white border border-slate-700/80 shadow-md flex items-center justify-center text-sm sm:text-base font-bold transition-all shrink-0 cursor-pointer ml-auto" title="Cerrar ventana">✕</button>
        </div>

        <div class="p-4 bg-red-950/40 border border-red-500/30 rounded-2xl text-xs text-red-200 space-y-2">
          <p class="font-bold text-red-300">¿Te apareció el mensaje en tu televisor?</p>
          <p class="text-[11px] leading-relaxed text-slate-300">
            "Este televisor no forma parte de tu Hogar con Netflix" o "Enviar código para confirmar".
          </p>
          <div class="bg-slate-950/80 p-3 rounded-xl border border-red-500/20 space-y-1.5 text-[11px]">
            <p>1️⃣ En tu Smart TV, pulsa en <strong>"Actualizar Hogar con Netflix"</strong> o <strong>"Estoy de viaje"</strong>.</p>
            <p>2️⃣ Haz clic abajo para solicitar la autorización de tu dispositivo.</p>
            <p>3️⃣ El enlace o código de confirmación se validará inmediatamente para que continúes viendo.</p>
          </div>
        </div>

        <div class="space-y-3">
          <div>
            <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Perfil Utilizado</label>
            <select v-model="hogarForm.profileName" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-white text-xs font-bold outline-none cursor-pointer">
              <option v-for="(p, idx) in selectedHogarAccount.profiles" :key="idx" :value="p.name">
                {{ p.name }} (PIN: {{ p.pin }})
              </option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Modelo de TV / Dispositivo (Opcional)</label>
            <input 
              v-model="hogarForm.tvModel" 
              type="text" 
              placeholder="Ej: Smart TV Samsung 55', LG WebOS, Android TV"
              class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-white text-xs outline-none"
            >
          </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-3 pt-3 border-t border-slate-800">
          <button 
            type="button" 
            @click="sendHogarViaWhatsApp"
            class="flex-1 bg-emerald-600 hover:bg-emerald-500 text-white font-black py-3 rounded-xl text-xs flex items-center justify-center gap-2 transition-all shadow cursor-pointer"
          >
            <span>📱</span>
            <span>Solicitar por WhatsApp</span>
          </button>
          <button 
            type="button" 
            @click="confirmHogarRequest" 
            class="flex-1 bg-red-600 hover:bg-red-500 text-white font-black py-3 rounded-xl text-xs flex items-center justify-center gap-2 transition-all shadow cursor-pointer"
          >
            <span>⚡</span>
            <span>Notificar al Sistema</span>
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL: REPORTAR FALLA / SOPORTE TÉCNICO -->
    <div v-if="showTicketModal" class="fixed inset-0 z-[80] flex items-center justify-center p-3 sm:p-5 pt-16 sm:pt-6 pb-6 overflow-y-auto overscroll-contain bg-black/85 backdrop-blur-md animate-in fade-in duration-200">
      <div class="bg-slate-900 border border-rose-500/30 w-full max-w-lg rounded-3xl p-6 shadow-2xl relative space-y-4">
        <div class="flex justify-between items-start border-b border-slate-800 pb-3">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-rose-500/20 text-rose-400 border border-rose-500/30 flex items-center justify-center text-xl">
              🆘
            </div>
            <div>
              <h3 class="text-lg font-black text-white">Reportar Falla / Solicitar Soporte</h3>
              <p class="text-xs text-slate-400">{{ ticketForm.subject }}</p>
            </div>
          </div>
          <button @click="showTicketModal = false" type="button" class="w-9 h-9 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white flex items-center justify-center text-sm font-bold transition cursor-pointer">✕</button>
        </div>

        <form @submit.prevent="submitSupportTicket" class="space-y-3 text-xs">
          <div>
            <label class="block font-bold text-slate-300 mb-1">Tipo de Inconveniente</label>
            <select v-model="ticketForm.subject" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-white font-bold outline-none focus:border-rose-500">
              <option value="Credenciales / Contraseña no funciona">Credenciales / Contraseña no funciona</option>
              <option value="Cuenta con candado o verificación PS4/PS5">Cuenta con candado o verificación PS4/PS5</option>
              <option value="Demora en entrega o activación">Demora en entrega o activación</option>
              <option value="Problema con Perfil / Pantalla Streaming">Problema con Perfil / Pantalla Streaming</option>
              <option value="Otra consulta de garantía">Otra consulta de garantía</option>
            </select>
          </div>
          <div>
            <label class="block font-bold text-slate-300 mb-1">Detalle del Problema *</label>
            <textarea
              v-model="ticketForm.message"
              rows="3"
              required
              placeholder="Describe brevemente qué ocurrió (ej: me pide código en 2 pasos, apareció candado en PS5, etc.)..."
              class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-white outline-none focus:border-rose-500"
            ></textarea>
          </div>
          <div class="flex gap-2 pt-2">
            <button type="button" @click="showTicketModal = false" class="px-4 py-2.5 rounded-xl bg-slate-800 text-slate-300 font-bold cursor-pointer">Cancelar</button>
            <button type="submit" class="flex-1 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-black shadow cursor-pointer">Enviar Reporte Oficial</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease, transform 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(10px);
}
</style>

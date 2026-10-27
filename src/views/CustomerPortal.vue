<script setup>
import { ref, computed, onMounted } from 'vue'
import { useResellerStore, useAdminStore } from '../store/admin'
import { useToastStore } from '../store/toast'

const isUnlocked = ref(false)
const searchIdentifier = ref('') // Búsqueda por Teléfono (ej: 11 9988-1122) o N° Cliente (CLI-9024)
const masterPin = ref('') // PIN personal del cliente (ej. 1234, o PIN de sus perfiles)
const error = ref('')

const resellerStore = useResellerStore()
const adminStore = useAdminStore()
const toast = useToastStore()

onMounted(async () => {
  await adminStore.initCloudSync()
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
      // Si el cliente tiene datos guardados de compra reciente, autodesbloquear su bóveda
      if (searchIdentifier.value) {
        unlockVault(true)
      }
    }
  } catch {}
})

// Cuentas filtradas del cliente que ingresó su número de teléfono, ID de cliente o PIN
const authenticatedClients = ref([])
const authenticatedPendingOrders = ref([])

const unlockVault = (silent = false) => {
  error.value = ''
  const rawQuery = searchIdentifier.value.trim()
  const query = rawQuery.toLowerCase().replace(/[^a-z0-9]/g, '')
  const queryDigits = rawQuery.replace(/[^0-9]/g, '')
  const pin = masterPin.value.trim()

  if (!query && !pin) {
    error.value = 'Ingresa tu Número de Teléfono o N° de Cliente'
    return
  }

  const matchPhoneOrId = (storedPhone, storedId) => {
    if (storedId && query && storedId.toLowerCase().replace(/[^a-z0-9]/g, '').includes(query)) return true
    if (!storedPhone || !queryDigits) return false
    const sDigits = storedPhone.replace(/[^0-9]/g, '')
    if (sDigits.includes(queryDigits) || queryDigits.includes(sDigits)) return true
    const sClean = sDigits.replace(/^549?/, '').replace(/^0/, '')
    const qClean = queryDigits.replace(/^549?/, '').replace(/^0/, '')
    if (sClean && qClean && (sClean.includes(qClean) || qClean.includes(sClean))) return true
    return false
  }

  // 1. Clientes con cuentas/perfiles ya asignados en el CRM
  let matched = []
  if (query) {
    matched = resellerStore.clients.filter(c => matchPhoneOrId(c.phone, c.clientNumber || c.id))
  }

  // 2. Pedidos virtuales de streaming (pendientes o entregados)
  let matchedOrders = []
  if (query) {
    matchedOrders = adminStore.orders.filter(o => matchPhoneOrId(o.customerPhone, o.customerNumber || o.id))
  }

  // 3. Pedidos físicos asociados
  let matchedPhysical = []
  if (query) {
    matchedPhysical = adminStore.physicalOrders.filter(o => matchPhoneOrId(o.customerPhone, o.customerNumber || o.id))
  }

  // 4. Clientes registrados en el Directorio
  let matchedUsers = []
  if (query) {
    matchedUsers = adminStore.registeredUsers.filter(u => matchPhoneOrId(u.phone, u.clientNumber || u.id))
  }

  // Validación de PIN si fue ingresado por el cliente
  if (pin) {
    const pinMatchesProfile = matched.some(c => 
      (c.pin && c.pin === pin) ||
      (c.profiles && c.profiles.some(p => p.pin === pin))
    )
    const pinMatchesOrder = matchedOrders.some(o => o.pin && o.pin === pin)
    const pinMatchesPhysical = matchedPhysical.some(o => o.pin && o.pin === pin)
    const pinMatchesUser = matchedUsers.some(u => u.pin === pin)

    const hasAnyRecord = matched.length > 0 || matchedOrders.length > 0 || matchedPhysical.length > 0 || matchedUsers.length > 0
    if (hasAnyRecord && !pinMatchesProfile && !pinMatchesOrder && !pinMatchesPhysical && !pinMatchesUser) {
      error.value = 'El PIN ingresado no coincide con tu registro. Por favor verifica tus 4 dígitos.'
      if (!silent) toast.add('PIN incorrecto para esta cuenta', 'error')
      return
    }
  }

  if (matched.length > 0 || matchedOrders.length > 0 || matchedPhysical.length > 0) {
    authenticatedClients.value = matched
    authenticatedPendingOrders.value = matchedOrders
    isUnlocked.value = true
    if (matchedPhysical.length > 0 && matched.length === 0 && matchedOrders.length === 0) {
      portalTab.value = 'physical'
    } else {
      portalTab.value = 'streaming'
    }
    if (!silent) {
      if (matched.length > 0) {
        toast.add('¡Bienvenido! Se encontraron tus servicios y accesos oficiales', 'success')
      } else if (matchedOrders.length > 0) {
        toast.add('¡Bienvenido! Tu pedido está registrado en tu Bóveda', 'info')
      } else {
        toast.add(`¡Bienvenido! Se encontraron ${matchedPhysical.length} pedido(s) asociados`, 'success')
      }
    }
  } else {
    error.value = 'No se encontraron servicios ni pedidos activos con esos datos. Verifica tu número de WhatsApp o N° de Cliente.'
    if (!silent) toast.add('No encontramos tus servicios ni pedidos', 'error')
  }
}

const copyToClipboard = (text, field) => {
  navigator.clipboard.writeText(text).then(() => {
    toast.add(`${field} copiado al portapapeles`, 'success')
  }).catch(() => {
    toast.add('Error al copiar', 'error')
  })
}

// Cuentas vinculadas al cliente autenticado (NUNCA exponer clientes de otros usuarios)
const customerAccounts = computed(() => {
  if (!isUnlocked.value) return []
  return authenticatedClients.value
})

// Pedidos virtuales de streaming en proceso de activación / preparación
const customerPendingOrders = computed(() => {
  if (!isUnlocked.value) return []
  return authenticatedPendingOrders.value
})

const getRemainingDays = (dateStr) => {
  const diffTime = new Date(dateStr) - new Date()
  return Math.ceil(diffTime / (1000 * 60 * 60 * 24))
}

// Pestaña en el Portal del Cliente: 'streaming' vs 'physical'
const portalTab = ref('streaming') // 'streaming' | 'physical'

// Pedidos físicos del cliente autenticado (NUNCA mostrar órdenes globales)
const customerPhysicalOrders = computed(() => {
  if (!isUnlocked.value) return []
  const q = searchIdentifier.value.trim().toLowerCase().replace(/[^a-z0-9]/g, '')
  const matchedPhones = authenticatedClients.value.map(c => (c.phone || '').replace(/[^0-9]/g, '')).filter(Boolean)
  const matchedIds = authenticatedClients.value.map(c => (c.clientNumber || c.id || '').toLowerCase().replace(/[^a-z0-9]/g, '')).filter(Boolean)

  return adminStore.physicalOrders.filter(o => {
    const cleanPhone = (o.customerPhone || '').replace(/[^0-9]/g, '')
    const cleanId = (o.customerNumber || o.id || '').toLowerCase().replace(/[^a-z0-9]/g, '')
    if (q) {
      if ((cleanPhone && (cleanPhone.includes(q) || q.includes(cleanPhone))) || (cleanId && cleanId.includes(q))) {
        return true
      }
    }
    if (cleanPhone && matchedPhones.some(p => p.includes(cleanPhone) || cleanPhone.includes(p))) {
      return true
    }
    if (cleanId && matchedIds.some(id => id.includes(cleanId) || cleanId.includes(id))) {
      return true
    }
    return false
  })
})

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
  toast.add('¡Solicitud de actualización de Hogar enviada! Tu código/enlace se procesará de inmediato.', 'success')
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

  const providerPhone = adminStore.providerBankInfo?.whatsapp || adminStore.adminAuth?.whatsapp || '+54 9 291 565-9321'
  const cleanPhone = providerPhone.replace(/[^0-9]/g, '')
  window.open(`https://wa.me/${cleanPhone}?text=${encodeURIComponent(msg)}`, '_blank')
}

const openOrderWhatsApp = (order) => {
  const providerPhone = adminStore.providerBankInfo?.whatsapp || adminStore.adminAuth?.whatsapp || '+54 9 291 565-9321'
  let cleanPhone = providerPhone.replace(/[^0-9]/g, '')
  if (cleanPhone.startsWith('0')) cleanPhone = cleanPhone.substring(1)
  if (cleanPhone.length === 10) cleanPhone = `549${cleanPhone}`

  const msg = `¡Hola! Tengo una consulta sobre mi Pedido *#${order.id || order.clientNumber}* en StreamePro 🚀\n\n` +
    `👤 *Cliente:* ${order.customerName || 'Cliente'}\n` +
    `📱 *WhatsApp:* ${order.customerPhone || ''}\n` +
    (order.pin ? `🔐 *PIN de Bóveda:* ${order.pin}\n` : '') +
    `🎬 *Servicio:* ${order.platform || 'Streaming'} - ${order.planLabel || (order.devices ? order.devices + ' Pantalla(s)' : '1 Pantalla')}\n` +
    `💰 *Total:* ${(order.clientPrice || order.total || 0).toLocaleString()} ARS\n\n` +
    `Adjunto mi consulta / comprobante de pago.`

  window.open(`https://wa.me/${cleanPhone}?text=${encodeURIComponent(msg)}`, '_blank')
}

const openPhysicalOrderWhatsApp = (order) => {
  const providerPhone = adminStore.providerBankInfo?.whatsapp || adminStore.adminAuth?.whatsapp || '+54 9 291 565-9321'
  let cleanPhone = providerPhone.replace(/[^0-9]/g, '')
  if (cleanPhone.startsWith('0')) cleanPhone = cleanPhone.substring(1)
  if (cleanPhone.length === 10) cleanPhone = `549${cleanPhone}`

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
      profiles: acc.profiles
    }
  })

  if (result.success) {
    toast.add(`¡Solicitud de renovación registrada con éxito! Código: ${result.renewal.id}`, 'success')
  }
}
</script>

<template>
  <div class="max-w-3xl mx-auto mt-6">
    <Transition name="fade" mode="out-in">
      
      <!-- Estado Bloqueado: Acceso Seguro -->
      <div v-if="!isUnlocked" class="bg-slate-900/60 backdrop-blur-xl p-10 rounded-3xl border border-slate-700/50 shadow-2xl text-center relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-b from-brand-accent/5 to-transparent pointer-events-none"></div>
        
        <div class="w-20 h-20 bg-slate-800 rounded-2xl flex items-center justify-center mx-auto mb-6 border border-slate-700 shadow-inner relative">
          <div class="absolute inset-0 bg-brand-accent/20 animate-pulse rounded-2xl"></div>
          <svg class="w-10 h-10 text-brand-accent relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"></path></svg>
        </div>
        
        <h2 class="text-3xl font-black mb-2 text-white">Bóveda de Credenciales</h2>
        <p class="text-slate-400 mb-6 max-w-md mx-auto text-xs leading-relaxed">
          Consulta de forma segura tus cuentas y perfiles oficiales. Ingresa tu número de WhatsApp de compra o tu Código de Cliente (CLI-...).
        </p>
        
        <form @submit.prevent="unlockVault" class="max-w-md mx-auto space-y-3.5 text-left">
          <div>
            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-300 mb-1">
              WhatsApp de Compra o N° Cliente
            </label>
            <input 
              v-model="searchIdentifier" 
              type="text" 
              placeholder="Ej: +54 9 11 9988-1122 o CLI-9024" 
              class="w-full bg-slate-950 border border-slate-700 focus:border-brand-accent rounded-xl px-4 py-3 text-sm text-white outline-none transition-colors"
            >
          </div>

          <div>
            <div class="flex justify-between items-center mb-1">
              <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-300">
                PIN de Seguridad de Perfil
              </label>
              <span class="text-[10px] text-slate-500 font-mono">PIN asignado en tu compra</span>
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

          <button type="submit" class="w-full relative group overflow-hidden rounded-xl shadow-lg mt-2">
            <div class="absolute inset-0 bg-gradient-to-r from-brand-accent to-blue-500 transition-all duration-300 group-hover:scale-105"></div>
            <div class="relative px-6 py-3.5 flex items-center justify-center gap-2 text-slate-950 font-black text-sm">
              <span>Desbloquear y Ver Mis Cuentas</span>
              <span>→</span>
            </div>
          </button>
        </form>
      </div>

      <!-- Estado Desbloqueado: Mis Servicios y Pedidos -->
      <div v-else class="space-y-6">
        <!-- Navegación entre Servicios Streaming y Pedidos de Productos Físicos -->
        <div class="flex flex-wrap justify-between items-center gap-3 bg-slate-900/60 p-4 rounded-2xl border border-slate-800 backdrop-blur-md">
          <div class="flex flex-wrap items-center gap-2">
            <button 
              @click="portalTab = 'streaming'"
              :class="[
                'px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2',
                portalTab === 'streaming' 
                  ? 'bg-brand-accent text-slate-950 font-black shadow-md' 
                  : 'bg-slate-950 text-slate-400 hover:text-white border border-slate-800'
              ]"
            >
              <span>🎬</span>
              <span>Suscripciones Streaming ({{ customerAccounts.length + customerPendingOrders.length }})</span>
            </button>
            <button 
              @click="portalTab = 'physical'"
              :class="[
                'px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2',
                portalTab === 'physical' 
                  ? 'bg-amber-400 text-slate-950 font-black shadow-md' 
                  : 'bg-slate-950 text-slate-400 hover:text-white border border-slate-800'
              ]"
            >
              <span>📦</span>
              <span>Pedidos Físicos ({{ customerPhysicalOrders.length }})</span>
            </button>
          </div>
          <div class="flex items-center gap-2">
            <button 
              @click="adminStore.syncFromCloud(); toast.add('Bóveda sincronizada con la nube', 'info')" 
              class="px-3 py-2 bg-slate-950 hover:bg-slate-800 text-xs font-bold rounded-xl transition-colors border border-slate-800 text-sky-400 flex items-center gap-1.5 cursor-pointer"
              title="Actualizar datos con la nube"
            >
              <span>🔄</span>
              <span>Actualizar</span>
            </button>
            <button @click="isUnlocked = false; masterPin = ''; authenticatedClients = []; authenticatedPendingOrders = []" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-xs font-bold rounded-xl transition-colors border border-slate-700 text-slate-300 cursor-pointer">
              Bloquear Vista
            </button>
          </div>
        </div>

        <!-- PESTAÑA 1: SUSCRIPCIONES STREAMING DIGITALES -->
        <div v-if="portalTab === 'streaming'" class="space-y-6">
          <!-- PEDIDOS DE STREAMING EN CURSO / ESPERA DE ENTREGA (SLA 10m - 1h) -->
          <div v-if="customerPendingOrders.length > 0" class="space-y-3">
            <h4 class="text-xs font-bold text-amber-400 uppercase tracking-wider flex items-center gap-2">
              <span class="w-2 h-2 rounded-full bg-amber-400 animate-ping"></span>
              <span>Pedidos en Proceso de Activación ({{ customerPendingOrders.length }})</span>
            </h4>
            <div 
              v-for="order in customerPendingOrders" 
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
                  <span>{{ order.hasReceipt || order.receiptStatus ? 'Comprobante recibido en la nube (en verificación por el proveedor)' : 'Pedido registrado en el sistema oficial' }}</span>
                </div>
                <p class="text-slate-400 text-[11px] leading-relaxed">
                  Tus credenciales (correo, contraseña y PIN asignado) aparecerán en esta misma pantalla en cuanto el proveedor complete la activación.
                </p>
              </div>

              <!-- Botón opcional para WhatsApp si el cliente lo necesita -->
              <div class="pt-1 flex flex-wrap gap-2">
                <button 
                  @click="openOrderWhatsApp(order)"
                  class="flex-1 bg-emerald-600/20 hover:bg-emerald-600/30 border border-emerald-500/40 text-emerald-300 hover:text-white font-bold py-2.5 px-4 rounded-xl text-xs flex items-center justify-center gap-2 transition-all cursor-pointer"
                >
                  <span class="text-base">💬</span>
                  <span>Contactar Asesor por WhatsApp sobre esta Orden</span>
                  <span class="text-[10px] font-mono opacity-80">+54 9 291 565-9321</span>
                </button>
              </div>
            </div>
          </div>

          <!-- CUENTAS ACTIVAS ENTREGADAS -->
          <div v-for="acc in customerAccounts" :key="acc.id" class="bg-slate-900/60 rounded-2xl border border-slate-700/50 overflow-hidden relative group backdrop-blur-md shadow-xl">
            
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
              <!-- Correo y Contraseña -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <p class="text-xs font-semibold text-slate-400 mb-1 uppercase tracking-wider">Correo Electrónico Oficial</p>
                  <div class="bg-[#020617] p-3 rounded-xl font-mono flex justify-between items-center border border-slate-800 hover:border-brand-accent/40 transition-colors">
                    <span class="text-slate-200 text-xs truncate">{{ acc.account }}</span>
                    <button @click="copyToClipboard(acc.account, 'Correo')" class="text-slate-500 hover:text-brand-accent p-1" title="Copiar Correo">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                    </button>
                  </div>
                </div>
                <div>
                  <p class="text-xs font-semibold text-slate-400 mb-1 uppercase tracking-wider">Contraseña de Acceso</p>
                  <div class="bg-[#020617] p-3 rounded-xl font-mono flex justify-between items-center border border-slate-800 hover:border-brand-accent/40 transition-colors">
                    <span class="text-slate-200 text-xs">{{ acc.pass || 'Stream2026*' }}</span>
                    <button @click="copyToClipboard(acc.pass || 'Stream2026*', 'Contraseña')" class="text-slate-500 hover:text-brand-accent p-1" title="Copiar Contraseña">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                    </button>
                  </div>
                </div>
              </div>
              
              <!-- Perfiles con Nombre y PIN Asignado -->
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
                      class="text-xs bg-slate-900 hover:bg-slate-800 text-amber-300 border border-slate-700 px-2 py-1 rounded-lg font-bold"
                    >
                      Copiar PIN
                    </button>
                  </div>
                </div>
              </div>
              
              <!-- Acciones de Garantía, Renovación y Hogar Netflix -->
              <div class="flex flex-wrap gap-2.5 pt-2 border-t border-slate-800/80">
                <button 
                  v-if="acc.platform.toLowerCase().includes('netflix')"
                  @click="openHogarModal(acc)" 
                  class="w-full sm:w-auto flex-1 bg-red-600/20 hover:bg-red-600/30 border border-red-500/40 text-red-300 hover:text-white text-xs font-bold py-2.5 px-3 rounded-xl transition-all flex justify-center items-center gap-1.5 shadow-sm cursor-pointer"
                >
                  <span>🏠</span>
                  <span>Actualizar Hogar</span>
                </button>
                <button @click="toast.add('Falla reportada con éxito. Nuestro equipo técnico revisará el servicio en garantía.', 'success')" class="flex-1 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-xs font-semibold py-2.5 px-3 rounded-xl transition-colors flex justify-center items-center gap-1.5 text-slate-300 cursor-pointer">
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

        <!-- PESTAÑA 2: HISTORIAL DE PEDIDOS DE PRODUCTOS FÍSICOS -->
        <div v-else class="space-y-4">
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
                <!-- Productos del pedido -->
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

                <!-- Detalles de Envío y Total -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs bg-slate-950/70 p-4 rounded-xl border border-slate-800/70">
                  <div>
                    <span class="text-slate-400 block text-[11px] uppercase tracking-wider font-semibold">Dirección de Entrega:</span>
                    <span class="text-white font-medium">{{ order.shippingAddress }}</span>
                  </div>
                  <div class="sm:text-right">
                    <span class="text-slate-400 block text-[11px] uppercase tracking-wider font-semibold">Total del Pedido:</span>
                    <span class="text-xl font-black text-amber-400">${{ order.totalAmount.toLocaleString() }} ARS</span>
                  </div>
                </div>

                <!-- Mensaje de Estado / Asesoría para el Cliente -->
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

                <!-- Botón de contacto para orden física -->
                <div class="pt-2 flex flex-wrap gap-2">
                  <button 
                    @click="openPhysicalOrderWhatsApp(order)"
                    class="flex-1 bg-emerald-600/20 hover:bg-emerald-600/30 border border-emerald-500/40 text-emerald-300 hover:text-white font-bold py-2.5 px-4 rounded-xl text-xs flex items-center justify-center gap-2 transition-all cursor-pointer"
                  >
                    <span class="text-base">💬</span>
                    <span>Contactar Asesor por WhatsApp sobre este Pedido</span>
                    <span class="text-[10px] font-mono opacity-80">+54 9 291 565-9321</span>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- MODAL: ACTUALIZAR HOGAR CON NETFLIX -->
    <div v-if="showHogarModal && selectedHogarAccount" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-sm animate-in fade-in duration-200">
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
          <button @click="showHogarModal = false" class="text-slate-400 hover:text-white p-1 text-lg cursor-pointer">✕</button>
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

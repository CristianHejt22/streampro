<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useResellerStore, useAdminStore } from '../store/admin'
import { useToastStore } from '../store/toast'
import html2canvas from 'html2canvas'
import FlyerGeneratorModal from '../components/FlyerGeneratorModal.vue'
import { 
  isBiometricsAvailable, 
  registerBiometricCredential, 
  authenticateWithBiometrics, 
  hasBiometricEnrolled, 
  removeBiometricEnrollment, 
  saveRememberedSession, 
  getRememberedSession, 
  clearRememberedSession 
} from '../services/biometrics'

const tab = ref('crm') 
const showFlyerModal = ref(false)
const flyerInitialProduct = ref(null)

const openFlyerForProduct = (prod) => {
  flyerInitialProduct.value = prod
  showFlyerModal.value = true
}
const filterStatus = ref('All')
const searchQuery = ref('')
const selectedResellerFilter = ref('All') // Filtrar por Número y Nombre de Revendedor
const selectedClient = ref(null)
const showDetailModal = ref(false)

// Gestión de Billetera Virtual (Carga manual de saldo a favor acumulativo / Devoluciones)
const showWalletModal = ref(false)
const topUpAmount = ref(1000)
const topUpReason = ref('Carga de saldo a favor / Devolución')

const resellerStore = useResellerStore()
const adminStore = useAdminStore()
const toast = useToastStore()

// Alertas de Renovaciones pendientes que el Proveedor actualizó con nuevos datos de cuenta
const pendingRenewalsAlert = computed(() => {
  return adminStore.renewalRequests.filter(r => 
    r.status === 'Provider Updated' && 
    (selectedResellerFilter.value === 'All' || r.resellerId === selectedResellerFilter.value)
  )
})

// Alertas prioritarias enviadas por el Proveedor (Novedades, Cambios de clave, Eventualidades)
const resellerAlerts = computed(() => {
  return adminStore.resellerAlerts.filter(a => 
    (selectedResellerFilter.value === 'All' || a.resellerId === selectedResellerFilter.value)
  )
})

// Modal para ver los Datos de Transferencia Bancaria del Proveedor
const showProviderBankModal = ref(false)

// Modal y formulario para Solicitar Compras Manuales desde el Panel Revendedor
const showManualPurchaseModal = ref(false)
const manualPurchaseForm = ref({
  platform: 'Netflix',
  planKey: '1_perfil',
  customerName: '',
  customerPhone: '',
  pinPreference: 'same',
  payWithWallet: false,
  profiles: [{ name: 'PERFIL 1' }]
})

const onManualPlatformChange = () => {
  const catalog = adminStore.pricingCatalog[manualPurchaseForm.value.platform]
  if (catalog) {
    const firstPlanKey = Object.keys(catalog.plans)[0]
    manualPurchaseForm.value.planKey = firstPlanKey
    onManualPlanChange()
  }
}

const onManualPlanChange = () => {
  const catalog = adminStore.pricingCatalog[manualPurchaseForm.value.platform]
  if (catalog && catalog.plans[manualPurchaseForm.value.planKey]) {
    const plan = catalog.plans[manualPurchaseForm.value.planKey]
    manualPurchaseForm.value.profiles = Array.from({ length: plan.units }, (_, i) => ({
      name: `PERFIL ${i + 1}`
    }))
  }
}

const selectedManualPlanDetails = computed(() => {
  const catalog = adminStore.pricingCatalog[manualPurchaseForm.value.platform]
  return catalog?.plans[manualPurchaseForm.value.planKey] || null
})

const submitManualPurchase = () => {
  if (!manualPurchaseForm.value.customerName.trim()) {
    toast.add('Ingresa el nombre del cliente para la orden', 'error')
    return
  }

  const result = resellerStore.requestManualPurchase({
    platform: manualPurchaseForm.value.platform,
    planKey: manualPurchaseForm.value.planKey,
    units: selectedManualPlanDetails.value?.units || 1,
    customerName: manualPurchaseForm.value.customerName.trim(),
    customerPhone: manualPurchaseForm.value.customerPhone.trim(),
    pinPreference: manualPurchaseForm.value.pinPreference,
    profiles: manualPurchaseForm.value.profiles,
    payWithWallet: manualPurchaseForm.value.payWithWallet
  })

  if (result.success) {
    toast.add(`¡Orden manual enviada al Proveedor! Código: ${result.order.customerNumber}`, 'success')
    showManualPurchaseModal.value = false
    manualPurchaseForm.value.customerName = ''
    manualPurchaseForm.value.customerPhone = ''
  } else {
    toast.add(result.error || 'Error al procesar la compra manual', 'error')
  }
}

const filteredClients = computed(() => {
  return resellerStore.clients.filter(c => {
    const matchesStatus = filterStatus.value === 'All' || c.status === filterStatus.value
    const matchesReseller = selectedResellerFilter.value === 'All' || c.resellerId === selectedResellerFilter.value
    const query = searchQuery.value.toLowerCase()
    const matchesSearch = c.name.toLowerCase().includes(query) ||
                          (c.clientNumber && c.clientNumber.toLowerCase().includes(query)) ||
                          c.account.toLowerCase().includes(query) ||
                          c.id.toLowerCase().includes(query) ||
                          c.platform.toLowerCase().includes(query)
    return matchesStatus && matchesReseller && matchesSearch
  })
})

const getExpiryDays = (expiryDate) => {
  const diffTime = new Date(expiryDate) - new Date()
  return Math.ceil(diffTime / (1000 * 60 * 60 * 24))
}

const getExpiryBadge = (expiryDate) => {
  const days = getExpiryDays(expiryDate)
  if (days < 0) {
    return { text: `Venció hace ${Math.abs(days)}d`, class: 'bg-red-500/20 text-red-400 border-red-500/30' }
  } else if (days <= 2) {
    return { text: `Vence en ${days}d (Urgente)`, class: 'bg-amber-500/20 text-amber-300 border-amber-500/30 animate-pulse' }
  } else if (days <= 7) {
    return { text: `${days} días restantes`, class: 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20' }
  } else {
    return { text: `${days} días activos`, class: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' }
  }
}

const openDetails = (client) => {
  selectedClient.value = client
  showDetailModal.value = true
}

// Cargar saldo a favor en la billetera
const handleTopUpWallet = () => {
  if (topUpAmount.value > 0) {
    resellerStore.topUpWallet(topUpAmount.value, topUpReason.value)
    toast.add(`+$${topUpAmount.value} ARS cargados a la billetera (${topUpReason.value})`, 'success')
    showWalletModal.value = false
    topUpAmount.value = 1000
  }
}

// Reporte de caída SIN recompensa monetaria
const handleReportFailure = (client) => {
  resellerStore.reportFailureNoReward(client.id)
  toast.add(`Caída de ${client.platform} reportada. Se notificó al proveedor para revisión técnica.`, 'success')
}

// Confirmar nuevas cuentas actualizadas por el Proveedor en renovación
const confirmRenewalFromProvider = (renewal) => {
  resellerStore.confirmRenewalUpdate(renewal.id)
  toast.add(`Nuevas credenciales de ${renewal.clientName} confirmadas y activadas`, 'success')
}

const handleRenew = (client) => {
  const result = adminStore.requestRenewal({
    clientId: client.id,
    clientNumber: client.clientNumber,
    clientName: client.name,
    platform: client.platform,
    planName: client.planName || `${client.devices} Pantallas`,
    resellerId: client.resellerId || resellerStore.activeResellerId || 'DIRECT',
    currentCredentials: {
      account: client.account,
      pass: client.pass,
      profiles: client.profiles
    }
  })
  toast.add(`Solicitud de renovación para ${client.name} enviada al Proveedor (ID: ${result.renewal.id})`, 'success')
}

const formatWhatsAppReminder = (client) => {
  const days = getExpiryDays(client.expiry)
  let text = ''
  if (days <= 2) {
    text = `🚨 *AVISO DE VENCIMIENTO STREAMPRO*\nHola ${client.name} (N° ${client.clientNumber || client.id}), tu suscripción de *${client.platform}* (${client.planName || client.devices + ' pantallas'}) vence pronto (${client.expiry}).\n¿Deseas renovar hoy para mantener tus perfiles guardados? Responde a este mensaje para confirmar tu renovación.`
  } else {
    const profilesList = (client.profiles || []).map(p => `• ${p.name} (PIN: ${p.pin})`).join('\n')
    text = `🎬 *STREAMPRO - TUS CREDENCIALES OFICIALES*\nHola ${client.name}!\nN° Cliente: ${client.clientNumber || client.id}\nPlataforma: *${client.platform}*\nPlan: *${client.planName || client.devices + ' pantallas'}*\nCorreo: ${client.account}\nClave: ${client.pass}\nVence: ${client.expiry}\n\n*Perfiles asignados:*\n${profilesList}\n\n¡Gracias por tu compra!`
  }
  return `https://wa.me/${client.phone.replace(/[^0-9]/g, '')}?text=${encodeURIComponent(text)}`
}

// Alerta Automática de Vencimiento con 1 Clic
const sendUrgentRenewalReminder = (client) => {
  const text = `🚨 *STREAMPRO - RENOVACIÓN URGENTE*\nHola ${client.name}!\nTu servicio de *${client.platform}* (${client.planName || client.devices + ' pantallas'}) vence el *${client.expiry}*.\n\n⚠️ Renueva ahora para no perder tu historial, favoritos y perfiles configurados.\n\n💳 Puedes renovar respondiendo directamente a este mensaje o desde nuestra tienda oficial:\n👉 ${uniqueAffiliateLink.value}`
  window.open(`https://wa.me/${client.phone.replace(/[^0-9]/g, '')}?text=${encodeURIComponent(text)}`, '_blank')
  toast.add(`Aviso de renovación enviado a WhatsApp de ${client.name}`, 'success')
}

// Comprobante Digital de Entrega en Imagen / Tarjeta
const showVoucherModal = ref(false)
const selectedVoucherClient = ref(null)
const voucherCardRef = ref(null)

const openVoucherModal = (client) => {
  selectedVoucherClient.value = client
  showVoucherModal.value = true
}

const downloadVoucherImage = async () => {
  if (!voucherCardRef.value) return
  toast.add('Generando comprobante digital en alta definición...', 'info')
  try {
    const canvas = await html2canvas(voucherCardRef.value, { scale: 3, backgroundColor: null })
    const link = document.createElement('a')
    link.download = `comprobante-streampro-${selectedVoucherClient.value?.clientNumber || 'entrega'}.png`
    link.href = canvas.toDataURL('image/png')
    link.click()
    toast.add('¡Comprobante digital descargado con éxito!', 'success')
  } catch (err) {
    toast.add('Error al generar la imagen del comprobante', 'error')
  }
}

const shareVoucherWhatsApp = () => {
  if (!selectedVoucherClient.value) return
  const c = selectedVoucherClient.value
  const profilesList = (c.profiles || []).map(p => `• ${p.name} (PIN: ${p.pin})`).join('\n')
  const msg = `🧾 *COMPROBANTE OFICIAL DE ENTREGA - STREAMPRO*\n━━━━━━━━━━━━━━━━━━━━\n👤 Cliente: *${c.name}*\n🏷️ N° Cliente: *${c.clientNumber || c.id}*\n🍿 Servicio: *${c.platform}* (${c.planName || c.devices + ' pantallas'})\n📧 Cuenta: *${c.account}*\n🔑 Clave: *${c.pass}*\n📅 Vence: *${c.expiry}*\n\n*Perfiles asignados:*\n${profilesList}\n━━━━━━━━━━━━━━━━━━━━\n✅ Garantía activa y soporte directo.\n¡Disfruta de tu streaming!`
  window.open(`https://wa.me/${c.phone.replace(/[^0-9]/g, '')}?text=${encodeURIComponent(msg)}`, '_blank')
}

// Simulador y Calculadora de Ganancias Mayoristas
const showCalcModal = ref(false)
const calcPlatform = ref('Netflix')
const calcUnits = ref(10)
const calcCustomPrice = ref(null)

const availablePlansForCalc = computed(() => {
  const cat = adminStore.pricingCatalog[calcPlatform.value]
  return cat ? Object.entries(cat.plans).map(([key, plan]) => ({ key, ...plan })) : []
})
const selectedCalcPlanKey = ref('1_perfil')

const selectedCalcPlan = computed(() => {
  const cat = adminStore.pricingCatalog[calcPlatform.value]
  return cat?.plans[selectedCalcPlanKey.value] || null
})

const calcTotalCost = computed(() => {
  return (selectedCalcPlan.value?.providerCost || 0) * calcUnits.value
})

const calcTotalClientIncome = computed(() => {
  const unitPrice = calcCustomPrice.value !== null && calcCustomPrice.value > 0 ? Number(calcCustomPrice.value) : (selectedCalcPlan.value?.clientPrice || 0)
  return unitPrice * calcUnits.value
})

const calcTotalProfit = computed(() => {
  return calcTotalClientIncome.value - calcTotalCost.value
})

const calcProfitMargin = computed(() => {
  if (calcTotalClientIncome.value === 0) return 0
  return Math.round((calcTotalProfit.value / calcTotalClientIncome.value) * 100)
})

// Autenticación de Revendedores (Usuario, Contraseña y Biometría)
const loginForm = ref({
  username: '',
  password: ''
})
const loginError = ref('')
const rememberSession = ref(true)
const biometricsAvailable = ref(false)
const hasEnrolledBiometrics = ref(false)
const isBiometricLoading = ref(false)

onMounted(async () => {
  biometricsAvailable.value = await isBiometricsAvailable()
  hasEnrolledBiometrics.value = hasBiometricEnrolled('reseller')

  // Auto-restaurar sesión recordada
  if (!resellerStore.isAuthenticated) {
    const saved = getRememberedSession('reseller')
    if (saved?.id) {
      const found = adminStore.resellers.find(r => r.id === saved.id || r.username?.toLowerCase() === saved.username?.toLowerCase())
      if (found && found.isActive !== false) {
        resellerStore.isAuthenticated = true
        resellerStore.activeResellerId = found.id
        resellerStore.sessionUser = { ...found }
        resellerStore.wallet = found.balance || 0
        selectedResellerFilter.value = found.id
        toast.add(`¡Bienvenido de nuevo, ${found.name}! Sesión recordada.`, 'success')
      }
    }
  }
})

const handleLogin = () => {
  loginError.value = ''
  if (!loginForm.value.username || !loginForm.value.password) {
    loginError.value = 'Ingresa tu usuario y contraseña de distribuidor'
    toast.add(loginError.value, 'error')
    return
  }
  const res = resellerStore.login(loginForm.value.username, loginForm.value.password)
  if (res.success) {
    selectedResellerFilter.value = res.reseller.id
    if (rememberSession.value) {
      saveRememberedSession('reseller', res.reseller)
    } else {
      clearRememberedSession('reseller')
    }
    toast.add(`¡Bienvenido! Sesión iniciada como ${res.reseller.name}`, 'success')
  } else {
    loginError.value = res.error || 'Credenciales inválidas'
    toast.add(loginError.value, 'error')
  }
}

// Inicio Rápido con Biometría (Huella / Rostro / Windows Hello / Face ID)
const handleBiometricLogin = async () => {
  loginError.value = ''
  isBiometricLoading.value = true
  try {
    const bioRes = await authenticateWithBiometrics('reseller')
    if (bioRes.success && bioRes.bioData) {
      const found = adminStore.resellers.find(r => 
        r.id === bioRes.bioData.userId || 
        r.username?.toLowerCase() === bioRes.bioData.username?.toLowerCase()
      )
      if (found && found.isActive !== false) {
        resellerStore.isAuthenticated = true
        resellerStore.activeResellerId = found.id
        resellerStore.sessionUser = { ...found }
        resellerStore.wallet = found.balance || 0
        selectedResellerFilter.value = found.id
        if (rememberSession.value) {
          saveRememberedSession('reseller', found)
        }
        toast.add(`🔓 ¡Acceso biométrico verificado! Bienvenido, ${found.name}`, 'success')
        return
      }
    }
    loginError.value = bioRes.error || 'No se pudo verificar la autenticación biométrica'
    toast.add(loginError.value, 'error')
  } catch (err) {
    loginError.value = 'Error al activar lector biométrico'
    toast.add(loginError.value, 'error')
  } finally {
    isBiometricLoading.value = false
  }
}

// Configurar Biometría desde el panel del revendedor
const handleEnrollBiometrics = async () => {
  const user = resellerStore.sessionUser
  if (!user) return
  const res = await registerBiometricCredential('reseller', user)
  if (res.success) {
    hasEnrolledBiometrics.value = true
    toast.add('✅ ¡Biometría (Huella / Rostro) configurada para inicio rápido en este equipo!', 'success')
  } else {
    toast.add(res.error || 'No se pudo vincular la biometría', 'error')
  }
}

const handleRemoveBiometrics = () => {
  removeBiometricEnrollment('reseller')
  hasEnrolledBiometrics.value = false
  toast.add('Biometría desvinculada de este dispositivo.', 'info')
}

const handleLogout = () => {
  clearRememberedSession('reseller')
  resellerStore.logout()
  toast.add('Sesión de distribuidor cerrada correctamente.', 'info')
}

// Link único por revendedor & Acciones de compartir
const currentResellerId = computed(() => {
  return resellerStore.isAuthenticated && resellerStore.sessionUser?.id 
    ? resellerStore.sessionUser.id 
    : (selectedResellerFilter.value !== 'All' ? selectedResellerFilter.value : resellerStore.activeResellerId)
})

// Dominio oficial de producción para enlaces de revendedor
const getBaseOrigin = () => {
  if (typeof window !== 'undefined' && (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1')) {
    return window.location.origin
  }
  return 'https://streamepro.me'
}

const uniqueAffiliateLink = computed(() => {
  const origin = getBaseOrigin()
  return `${origin}/?ref=${currentResellerId.value}`
})

const copyAffiliateLink = () => {
  navigator.clipboard.writeText(uniqueAffiliateLink.value).then(() => {
    toast.add(`¡Link único de tu tienda copiado! (${uniqueAffiliateLink.value})`, 'success')
  }).catch(() => {
    toast.add('Error al copiar link', 'error')
  })
}

const showQrModal = ref(false)

const shareStoreToWhatsApp = () => {
  const currentRes = adminStore.resellers.find(r => r.id === currentResellerId.value)
  const name = currentRes?.name || 'Distribuidor Oficial'
  const text = `👋 ¡Hola! Te comparto nuestra tienda oficial de streaming de *${name}* 🎬🍿\n\n✅ Netflix, Max, Disney+, Spotify e IPTV al mejor precio garantizado\n✅ Cuentas 100% oficiales y entrega en 10 min a 1 hora con PIN propio (carga tu comprobante para agilizar)\n\n🛒 *Haz tu pedido directamente acá:*\n👉 ${uniqueAffiliateLink.value}\n\n¡Cualquier duda avísame para coordinar tu entrega!`
  window.open(`https://wa.me/?text=${encodeURIComponent(text)}`, '_blank')
}

const qrCodeUrl = computed(() => {
  return `https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encodeURIComponent(uniqueAffiliateLink.value)}`
})

// Alertas del Proveedor: Borrar o cerrar notificación
const dismissAlert = (alertId) => {
  resellerStore.dismissAlert(alertId)
  toast.add('Notificación cerrada y eliminada.', 'success')
}

const clearAllResellerAlerts = () => {
  resellerStore.clearAllAlerts()
  toast.add('Todas las notificaciones han sido eliminadas.', 'success')
}

// Marketing Flyer Generator
const platformStyles = {
  Netflix: { bg: 'bg-red-600', text: 'text-white', price: 'text-yellow-300' },
  Max: { bg: 'bg-blue-600', text: 'text-white', price: 'text-white' },
  Spotify: { bg: 'bg-emerald-600', text: 'text-black', price: 'text-black' },
  'Disney+': { bg: 'bg-sky-600', text: 'text-white', price: 'text-white' },
  IPTV: { bg: 'bg-purple-600', text: 'text-white', price: 'text-green-300' }
}

// GESTIÓN Y ARMADO DE PAQUETES (REVENDEDOR)
const resellerPackageForm = ref({
  title: '',
  description: '',
  badge: '🔥 20% OFF',
  selectedServices: [
    { platform: 'Netflix', planKey: '1_perfil', units: 1 },
    { platform: 'Spotify', planKey: '1_perfil', units: 1 }
  ],
  promoPrice: 0
})

const addServiceToResellerPackage = () => {
  resellerPackageForm.value.selectedServices.push({
    platform: 'Disney+',
    planKey: '1_perfil',
    units: 1
  })
}

const removeServiceFromResellerPackage = (index) => {
  if (resellerPackageForm.value.selectedServices.length > 1) {
    resellerPackageForm.value.selectedServices.splice(index, 1)
  }
}

const resellerPackageCalc = computed(() => {
  let cost = 0
  let regular = 0

  resellerPackageForm.value.selectedServices.forEach(s => {
    const platData = adminStore.pricingCatalog[s.platform]
    const plan = platData?.plans[s.planKey]
    if (plan) {
      cost += plan.providerCost || 0
      regular += plan.clientPrice || 0
    }
  })

  const promo = Number(resellerPackageForm.value.promoPrice) || regular
  const profit = promo - cost
  const discount = regular > 0 ? Math.max(0, Math.round(((regular - promo) / regular) * 100)) : 0

  return { cost, regular, promo, profit, discount }
})

// Auto sugerir precio promo cuando cambia la lista de servicios seleccionados
watch(() => resellerPackageForm.value.selectedServices, () => {
  let regular = 0
  resellerPackageForm.value.selectedServices.forEach(s => {
    const platData = adminStore.pricingCatalog[s.platform]
    const plan = platData?.plans[s.planKey]
    if (plan) regular += plan.clientPrice || 0
  })
  if (regular > 0 && (!resellerPackageForm.value.promoPrice || resellerPackageForm.value.promoPrice === 0)) {
    resellerPackageForm.value.promoPrice = Math.round(regular * 0.85) // 15% OFF inicial
  }
}, { deep: true, immediate: true })

const submitCreateResellerPackage = () => {
  if (!resellerPackageForm.value.title.trim()) {
    toast.add('Ingresa un título para tu combo', 'error')
    return
  }

  const formatted = resellerPackageForm.value.selectedServices.map(s => {
    const platData = adminStore.pricingCatalog[s.platform]
    const plan = platData?.plans[s.planKey]
    return {
      platform: s.platform,
      planKey: s.planKey,
      planLabel: plan?.label || s.planKey,
      units: plan?.units || 1,
      regularPrice: plan?.clientPrice || 0,
      cost: plan?.providerCost || 0
    }
  })

  const res = resellerStore.createResellerPackage({
    title: resellerPackageForm.value.title.trim(),
    description: resellerPackageForm.value.description.trim() || 'Combo promocional armado por tu distribuidor oficial.',
    badge: resellerPackageForm.value.badge.trim() || `🔥 ${resellerPackageCalc.value.discount}% OFF`,
    services: formatted,
    promoPrice: Number(resellerPackageForm.value.promoPrice) || resellerPackageCalc.value.regular
  })

  if (res.success) {
    toast.add(`¡Combo "${res.package.title}" creado con éxito! Puedes generar su flyer y copy en Marketing.`, 'success')
    resellerPackageForm.value = {
      title: '',
      description: '',
      badge: '🔥 20% OFF',
      selectedServices: [
        { platform: 'Netflix', planKey: '1_perfil', units: 1 },
        { platform: 'Spotify', planKey: '1_perfil', units: 1 }
      ],
      promoPrice: 0
    }
  }
}

// Cargar paquete directamente a la Suite de Marketing
const loadPackageToMarketing = (pkg) => {
  marketingMode.value = 'package'
  selectedMarketingPackageId.value = pkg.id
  syncMarketingFromPackage(pkg)
  tab.value = 'marketing'
  toast.add(`Flyer y copy generados automáticamente para "${pkg.title}".`, 'success')
}

// MARKETING FLYERS & COPY GENERATOR
const marketingMode = ref('package') // 'single' | 'package'
const selectedMarketingPackageId = ref('PKG-101')
const comboFlyerRef = ref(null)

// Datos dinámicos del combo en marketing
const comboMarketingData = ref({
  title: 'Combo Cine Total 4K (Netflix + Disney+ + Max)',
  badge: '🔥 25% OFF',
  services: [
    { platform: 'Netflix', planLabel: '1 Pantalla 4K' },
    { platform: 'Disney+', planLabel: '1 Pantalla' },
    { platform: 'Max', planLabel: '1 Pantalla' }
  ],
  promoPrice: 16500,
  originalPrice: 21500
})

const syncMarketingFromPackage = (pkg) => {
  if (!pkg) return
  comboMarketingData.value = {
    title: pkg.title,
    badge: pkg.badge || '🔥 OFERTA COMBO',
    services: pkg.services.map(s => ({ platform: s.platform, planLabel: s.planLabel })),
    promoPrice: pkg.promoPrice,
    originalPrice: pkg.originalPrice
  }
}

watch(selectedMarketingPackageId, (newId) => {
  const found = adminStore.packages.find(p => p.id === newId)
  if (found) {
    syncMarketingFromPackage(found)
  }
}, { immediate: true })

const togglePlatformInComboMarketing = (plat) => {
  const idx = comboMarketingData.value.services.findIndex(s => s.platform === plat)
  if (idx !== -1) {
    if (comboMarketingData.value.services.length > 1) {
      comboMarketingData.value.services.splice(idx, 1)
    }
  } else {
    comboMarketingData.value.services.push({ platform: plat, planLabel: '1 Perfil / Pantalla' })
  }
  const sumRegular = comboMarketingData.value.services.reduce((acc, s) => {
    const base = adminStore.pricingCatalog[s.platform]?.plans['1_perfil']?.clientPrice || 5000
    return acc + base
  }, 0)
  comboMarketingData.value.originalPrice = sumRegular
  comboMarketingData.value.promoPrice = Math.round(sumRegular * 0.8)
}

const selectedPlatform = ref('Netflix')
const priceToShow = ref(7700)
const devicesToPromo = ref(1)
const flyerRef = ref(null)

const downloadFlyer = async () => {
  const targetRef = marketingMode.value === 'package' ? comboFlyerRef.value : flyerRef.value
  if (!targetRef) return
  toast.add('Generando flyer en alta definición...', 'success')
  try {
    const canvas = await html2canvas(targetRef, { scale: 3, backgroundColor: null })
    const link = document.createElement('a')
    const fileName = marketingMode.value === 'package' 
      ? `streampro-combo-${comboMarketingData.value.title.toLowerCase().replace(/[^a-z0-9]/g, '-')}.png`
      : `streampro-flyer-${selectedPlatform.value.toLowerCase()}.png`
    link.download = fileName
    link.href = canvas.toDataURL('image/png')
    link.click()
    toast.add('¡Flyer descargado en HD listo para publicar!', 'success')
  } catch (err) {
    toast.add('Error al generar la imagen', 'error')
  }
}

const shareFlyerOfferToWhatsApp = () => {
  if (marketingMode.value === 'package') {
    const srvText = comboMarketingData.value.services.map(s => `• ${s.platform} (${s.planLabel})`).join('\n')
    const msg = `🔥 *${comboMarketingData.value.badge} - ${comboMarketingData.value.title.toUpperCase()}* 🔥\n\n📦 *Cuentas incluidas en el combo:*\n${srvText}\n\n💰 *Precio Oferta:* $${comboMarketingData.value.promoPrice.toLocaleString()} ARS *(Antes: ~$${comboMarketingData.value.originalPrice.toLocaleString()} ARS)*\n✅ Cuentas 100% oficiales con PIN personal y garantía.\n\n🛒 *Pide el tuyo en mi tienda oficial:*\n👉 ${uniqueAffiliateLink.value}\n\n¡Escríbeme antes de agotar cupos!`
    window.open(`https://wa.me/?text=${encodeURIComponent(msg)}`, '_blank')
  } else {
    const msg = `🔥 *OFERTA EXCLUSIVA EN ${selectedPlatform.value.toUpperCase()}* 🔥\n\n📺 Modalidad: *${devicesToPromo.value === 1 ? '1 Perfil individual' : devicesToPromo.value + ' Dispositivos simultáneos'}*\n💰 Precio especial: *$${priceToShow.value} ARS*\n✅ Cuenta oficial con PIN privado y garantía total.\n\n🛒 *Compra directa en mi tienda:* ${uniqueAffiliateLink.value}\n\n¡Consultame para coordinar tu entrega!`
    window.open(`https://wa.me/?text=${encodeURIComponent(msg)}`, '_blank')
  }
}

// Generador de Copys Atractivos para Redes Sociales
const socialNetwork = ref('whatsapp') // 'whatsapp' | 'instagram' | 'tiktok' | 'facebook'
const socialPlatform = ref('Netflix')
const promoTone = ref('urgente') // 'urgente' | 'vip' | 'ahorro'
const promoPrice = ref(7700)
const promoScreens = ref(1)

const generatedSocialCopy = computed(() => {
  const link = uniqueAffiliateLink.value

  if (marketingMode.value === 'package') {
    const title = comboMarketingData.value.title
    const badge = comboMarketingData.value.badge
    const promo = comboMarketingData.value.promoPrice.toLocaleString()
    const regular = comboMarketingData.value.originalPrice.toLocaleString()
    const platformsList = comboMarketingData.value.services.map(s => {
      const icon = s.platform === 'Netflix' ? '🍿' : s.platform === 'Spotify' ? '🎧' : s.platform === 'Disney+' ? '✨' : s.platform === 'IPTV' ? '📺' : '🎬'
      return `${icon} ${s.platform} (${s.planLabel})`
    }).join('\n')
    const platNames = comboMarketingData.value.services.map(s => s.platform).join(', ')

    if (socialNetwork.value === 'whatsapp') {
      if (promoTone.value === 'urgente') {
        return `⚡ *¡OFERTA RELÁMPAGO: COMBO STREAMING EN OFERTA!* ⚡\n\n🎁 *${title.toUpperCase()}*\n🏷️ *${badge}*\n\n🍿 *Cuentas Oficiales Incluidas:*\n${platformsList}\n\n🔥 *Solo por tiempo limitado:* *$${promo} ARS*\n(Precio regular individual: ~$${regular} ARS)\n\n✅ Perfil privado con PIN exclusivo por servicio\n✅ Entrega en 10 min a 1 hora con comprobante\n✅ 0 caídas - Cuentas 100% oficiales con garantía\n\n📲 *Pedí tu combo directo en mi tienda:*\n👉 ${link}\n\n¡Escríbeme antes de que se agoten los cupos disponibles! 🚀`
      } else if (promoTone.value === 'vip') {
        return `👑 *DISFRUTA DEL MÁXIMO ENTRETENIMIENTO EN ALTA DEFINICIÓN* 👑\n\n¿Cansado de contratar plataformas por separado? Pásate a nuestro *${title}*.\n\n✨ *Todo en un solo paquete:* \n${platformsList}\n\n💎 *Tarifa Especial Todo Incluido:* $${promo} ARS al mes\n(Ahorras frente a contratar por separado: ~$${regular} ARS)\n\n🛡️ PIN personal, perfiles privados y soporte VIP 24/7.\n🛒 *Catálogo online y pedidos directos:* ${link}\n\n¡Escríbeme para coordinar tu entrega!`
      } else {
        return `💰 *¡DEJA DE PAGAR DE MÁS EN STREAMING!* 💰\n\nAhorra a lo grande combinando tus plataformas favoritas con el combo *${title}*.\n\n🎉 *Incluye en un solo combo:*\n${platformsList}\n\n🏷️ *Precio Promocional:* solo *$${promo} ARS* al mes (Antes: ~$${regular} ARS).\n\n🔒 PIN personal | ⏱️ Entrega: 10 min a 1 hora | 🛡️ Garantía total por 30 días renovables.\n\nComprá directo acá:\n👉 ${link}\n\n¡Consultame antes de que termine la promoción!`
      }
    } else if (socialNetwork.value === 'instagram') {
      return `🔥 ¡Tu entretenimiento resuelto en un solo combo! 🔥\n\n¿Querés tener ${platNames} sin gastar de más? 🍿📺🎧\n\nConocé nuestro *${title}*:\n${platformsList}\n\n✔️ Perfiles privados con PIN exclusivo\n✔️ Calidad 4K Ultra HD y audio premium\n✔️ Entrega en 10 min a 1 hora con comprobante\n✔️ Soporte y garantía 24/7\n\n💳 Precio promo exclusivo: $${promo} ARS (Antes: ~$${regular} ARS)\n\n🔗 Link en bio para pedir el tuyo directo:\n${link}\n\n#streaming #series #peliculas #combos #ofertas #netflix #spotify #disneyplus #iptv`
    } else if (socialNetwork.value === 'tiktok') {
      return `POV: Encontraste el mejor combo de ${platNames} oficial a precio mayorista 😱🍿✨\n\n${title} a solo $${promo} ARS al mes 🚀\n\nComprobá stock y pedilo directo en el link de mi perfil: ${link} 💎\n\n#trucostreaming #cineencasa #combos #ahorro #series #peliculas`
    } else {
      // Facebook
      return `🎬 COMBO DE STREAMING OFICIAL - ${title.toUpperCase()} 🎬\n\n¡Ahorra contratando tus servicios combinados en un solo paquete con garantía oficial!\n\n📦 Incluye:\n${platformsList}\n\n💲 PRECIO PROMO: $${promo} ARS (Precio regular: ~$${regular} ARS).\n🛡️ Garantía durante los 30 días con renovación garantizada.\n\n🌐 Tienda Online Oficial: ${link}\n📩 Envíame un mensaje al privado con tu comprobante para entregarte tus accesos!`
    }
  }

  // Modo Individual
  const pName = socialPlatform.value
  const price = promoPrice.value
  const screens = promoScreens.value === 1 ? '1 Pantalla Individual Ultra HD' : `${promoScreens.value} Pantallas Simultáneas Ultra HD`

  if (socialNetwork.value === 'whatsapp') {
    if (promoTone.value === 'urgente') {
      return `⚡ *¡OFERTA RELÁMPAGO STREAMING!* ⚡\n\n🍿 *${pName.toUpperCase()}* (${screens})\n🔥 Solo por hoy: *$${price} ARS*\n\n✅ Perfil privado con PIN exclusivo\n✅ Entrega en 10 min a 1 hora y garantía total\n✅ 0 caídas - Cuentas 100% oficiales\n\n📲 *Pide la tuya directo en mi tienda:*\n👉 ${link}\n\n¡Escríbeme antes de que se agoten los slots! 🚀`
    } else if (promoTone.value === 'vip') {
      return `👑 *DISFRUTA DEL MEJOR STREAMING EN ALTA DEFINICIÓN* 👑\n\n¿Cansado de cuentas que se caen? Pasa al servicio VIP de *${pName}*.\n\n✨ Calidad 4K Ultra HD HDR\n✨ ${screens} con tu propio PIN\n✨ Garantía activa durante todo el mes\n\n💎 *Precio Especial:* $${price} ARS\n🛒 *Accede a mi catálogo online:* ${link}\n\n¡Escríbeme y envíame tu comprobante para agilizar tu entrega!`
    } else {
      return `💰 *¡DEJA DE PAGAR DE MÁS EN TUS SERIES FAVORITAS!* 💰\n\nAhorra hasta un 70% contratando *${pName}* (${screens}).\n🏷️ *Precio accesible:* solo *$${price} ARS* al mes.\n\n🔒 PIN personal | ⏱️ Entrega: 10 min a 1 hora | 🛡️ Soporte postventa\n\nComprá directo acá:\n👉 ${link}\n\n¡Consultame por combos familiares!`
    }
  } else if (socialNetwork.value === 'instagram') {
    return `🔥 ¡Tu finde de series asegurado con ${pName.toUpperCase()}! 🔥\n\n¿Querés maratonear en 4K sin pagar fortunas? 🍿📺\n\nAccedé a tu cuenta oficial con:\n✔️ ${screens}\n✔️ PIN personalizado y perfil privado\n✔️ Entrega en 10 min a 1 hora con comprobante\n✔️ Garantía y soporte 24/7\n\n💳 Precio exclusivo: $${price} ARS\n\n🔗 Link en bio o compralo en mi tienda:\n${link}\n\n#streaming #series #peliculas #${pName.toLowerCase()} #maraton #ofertas`
  } else if (socialNetwork.value === 'tiktok') {
    return `POV: Encontraste las cuentas de ${pName} más baratas y que nunca se caen 😱🍿\n\n${screens} con PIN propio a solo $${price} ARS 🚀\n\nComprobá stock en el link de mi perfil: ${link} ✨\n\n#trucostreaming #cineencasa #${pName.toLowerCase()} #ahorro #series`
  } else {
    // Facebook
    return `🎬 SERVICIO DE STREAMING OFICIAL - ${pName.toUpperCase()} 🎬\n\nDisponible entrega en 10 min a 1 hora para ${screens}.\nIncluye PIN exclusivo de seguridad y soporte durante todo el período contratado.\n\n💲 PRECIO PROMO: $${price} ARS\n🛡️ Garantía por 30 días renovables.\n\n🌐 Tienda Online Oficial: ${link}\n📩 Envíame un mensaje al privado con tu comprobante para agilizar tu entrega!`
  }
})

const copyGeneratedSocialCopy = () => {
  navigator.clipboard.writeText(generatedSocialCopy.value).then(() => {
    toast.add('¡Texto atractivo copiado! Listo para publicar en tus redes.', 'success')
  }).catch(() => {
    toast.add('Error al copiar texto', 'error')
  })
}

const shareSocialPromoToWhatsApp = () => {
  const url = `https://wa.me/?text=${encodeURIComponent(generatedSocialCopy.value)}`
  window.open(url, '_blank')
}

// Catálogo Físico con desglose exclusivo de ganancias (1% tienda vs 3% link compartido)
const physicalSearchQuery = ref('')
const physicalCatFilter = ref('All')

const filteredPhysicalProductsForReseller = computed(() => {
  return adminStore.physicalProducts.filter(p => {
    const matchesCat = physicalCatFilter.value === 'All' || 
      (p.categoryIds && p.categoryIds.includes(physicalCatFilter.value))
    const q = physicalSearchQuery.value.toLowerCase().trim()
    const matchesQuery = !q || p.name.toLowerCase().includes(q)
    return matchesCat && matchesQuery
  })
})

const getProductReferralUrl = (product, clean = false) => {
  if (!product) return ''
  const origin = getBaseOrigin()
  if (clean) {
    return `${origin}/producto/${product.id}?ref=${currentResellerId.value}`
  }
  return `${origin}/?ref=${currentResellerId.value}&prod=${product.id}`
}

const copyResellerProduct3PercentLink = (product) => {
  const link = getProductReferralUrl(product)
  navigator.clipboard.writeText(link).then(() => {
    toast.add(`¡Link 3% copiado! Ganas 3% ($${Math.round(product.priceNumeric * 0.03).toLocaleString()} ARS) por venta referida`, 'success')
  }).catch(() => {
    toast.add('Error al copiar link', 'error')
  })
}

const shareResellerProductWhatsApp = (product) => {
  const link = getProductReferralUrl(product)
  const msg = `🔥 *OFERTA ESPECIAL:* *${product.name}*\n💰 Precio Oficial: *${product.price}*\n🚚 ¡Envíos directos a todo el país!\n\n👉 *Haz tu pedido online aquí:* ${link}`
  window.open(`https://wa.me/?text=${encodeURIComponent(msg)}`, '_blank')
}

// Modal de Previsualización de Producto Físico y sus URLs
const previewingPhysicalProduct = ref(null)
const showPhysicalUrlModal = ref(false)

const openProductUrlPreviewModal = (product) => {
  previewingPhysicalProduct.value = product
  showPhysicalUrlModal.value = true
}

const openProductInNewTab = (product) => {
  const link = getProductReferralUrl(product)
  window.open(link, '_blank')
}
</script>

<template>
  <!-- PANTALLA DE ACCESO EXCLUSIVO PARA REVENDEDORES -->
  <div v-if="!resellerStore.isAuthenticated" class="min-h-[70vh] flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-slate-900 border border-slate-800 rounded-3xl p-8 shadow-2xl space-y-6 relative overflow-hidden">
      <!-- Glow decorativo -->
      <div class="absolute -top-12 -right-12 w-40 h-40 bg-brand-accent/10 rounded-full blur-3xl pointer-events-none"></div>

      <div class="text-center space-y-2">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-brand-accent/15 border border-brand-accent/30 text-2xl text-brand-accent shadow-inner">
          🔐
        </div>
        <h2 class="text-2xl font-black text-white tracking-tight">Acceso a Distribuidores</h2>
        <p class="text-xs text-slate-400">Ingresa con tus credenciales de revendedor autorizadas por el proveedor.</p>
      </div>

      <div v-if="loginError" class="p-3.5 bg-red-950/40 border border-red-500/40 rounded-xl text-red-300 text-xs flex items-center gap-2">
        <span class="shrink-0 text-base">⚠️</span>
        <span>{{ loginError }}</span>
      </div>

      <form @submit.prevent="handleLogin" class="space-y-4">
        <div>
          <label class="block text-xs font-bold text-slate-300 mb-1.5 uppercase tracking-wider">Usuario / Identificador</label>
          <input 
            v-model="loginForm.username" 
            type="text" 
            placeholder="ej. mi_usuario" 
            required 
            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-brand-accent transition-all"
          >
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-300 mb-1.5 uppercase tracking-wider">Contraseña</label>
          <input 
            v-model="loginForm.password" 
            type="password" 
            placeholder="••••••••••••" 
            required 
            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-brand-accent transition-all"
          >
        </div>

        <div class="flex items-center justify-between text-xs pt-1">
          <label class="flex items-center gap-2 text-slate-300 cursor-pointer select-none hover:text-white transition-colors">
            <input 
              v-model="rememberSession" 
              type="checkbox" 
              class="w-4 h-4 rounded bg-slate-950 border-slate-700 text-brand-accent focus:ring-brand-accent focus:ring-offset-slate-900 cursor-pointer"
            >
            <span>Recordar inicio de sesión</span>
          </label>
        </div>

        <button 
          type="submit" 
          class="w-full bg-brand-accent hover:bg-sky-400 text-slate-950 font-black py-3 rounded-xl transition-all shadow-lg shadow-brand-accent/20 flex items-center justify-center gap-2 text-sm"
        >
          <span>Ingresar a Mi Panel</span>
          <span>→</span>
        </button>

        <!-- Botón de Inicio Rápido con Huella / Biometría -->
        <div v-if="hasEnrolledBiometrics || biometricsAvailable" class="pt-2">
          <div class="relative flex py-2 items-center">
            <div class="flex-grow border-t border-slate-800"></div>
            <span class="flex-shrink mx-3 text-[10px] text-slate-500 uppercase font-bold tracking-wider">o acceso rápido</span>
            <div class="flex-grow border-t border-slate-800"></div>
          </div>

          <button 
            type="button" 
            @click="handleBiometricLogin"
            :disabled="isBiometricLoading"
            class="w-full bg-gradient-to-r from-emerald-500/15 via-teal-500/15 to-emerald-500/15 hover:from-emerald-500/25 hover:to-teal-500/25 text-emerald-300 border border-emerald-500/40 font-bold py-2.5 rounded-xl transition-all flex items-center justify-center gap-2 text-xs shadow-sm disabled:opacity-50"
          >
            <span class="text-base">👆</span>
            <span v-if="isBiometricLoading">Verificando Huella / Biometría...</span>
            <span v-else>Iniciar con Huella / Face ID / Windows Hello</span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- DASHBOARD AUTENTICADO DE DISTRIBUIDOR -->
  <div v-else class="space-y-6">
    <!-- Barra Superior de Sesión de Revendedor -->
    <div class="bg-gradient-to-r from-slate-900 via-slate-900/90 to-indigo-950/40 p-4 rounded-2xl border border-slate-800 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-brand-accent/20 border border-brand-accent/30 text-brand-accent flex items-center justify-center font-black text-sm">
          {{ resellerStore.sessionUser?.name ? resellerStore.sessionUser.name.substring(0, 2).toUpperCase() : 'RE' }}
        </div>
        <div>
          <div class="flex items-center gap-2">
            <h3 class="font-black text-white text-sm sm:text-base">{{ resellerStore.sessionUser?.name }}</h3>
            <span class="text-[10px] font-mono font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 px-2 py-0.5 rounded">
              SESIÓN ACTIVA
            </span>
          </div>
          <p class="text-xs text-slate-400 font-mono">
            ID: <span class="text-brand-accent">{{ resellerStore.sessionUser?.id }}</span> | Usuario: <span class="text-slate-300">@{{ resellerStore.sessionUser?.username }}</span>
          </p>
        </div>
      </div>

      <div class="flex flex-wrap items-center gap-2 self-end sm:self-auto">
        <!-- Botón Configurar / Gestionar Biometría -->
        <button 
          v-if="hasEnrolledBiometrics"
          @click="handleRemoveBiometrics"
          class="bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 px-3 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 shadow-sm"
          title="Huella / Biometría activa en este dispositivo. Clic para desvincular."
        >
          <span>🔐</span>
          <span>Huella Activa ✓</span>
        </button>
        <button 
          v-else
          @click="handleEnrollBiometrics"
          class="bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700 px-3 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 shadow-sm"
          title="Vincular inicio de sesión rápido con Huella dactilar, Windows Hello o Face ID en este dispositivo"
        >
          <span>👆</span>
          <span>Activar Huella / Biometría</span>
        </button>

        <button 
          @click="showFlyerModal = true"
          class="bg-gradient-to-r from-amber-500/20 to-orange-500/20 hover:from-amber-500/30 hover:to-orange-500/30 text-amber-300 border border-amber-500/30 px-3.5 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 shadow"
          title="Crear Flyers y Banners publicitarios para Estados de WhatsApp e Instagram"
        >
          <span>🎨</span>
          <span>Generar Flyer</span>
        </button>

        <div class="px-3 py-1.5 bg-slate-950 border border-slate-800 rounded-xl text-right">
          <span class="text-[10px] uppercase font-bold text-slate-500 block">Billetera Disponible</span>
          <span class="text-xs font-black text-emerald-400 font-mono">${{ resellerStore.wallet }} ARS</span>
        </div>
        <button 
          @click="handleLogout" 
          class="bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/20 hover:border-red-500/40 px-3.5 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5"
          title="Cerrar Sesión"
        >
          <span>🚪</span>
          <span>Salir</span>
        </button>
      </div>
    </div>

    <!-- Navegación Superior de Pestañas -->
    <div class="flex flex-wrap gap-2 p-1.5 bg-slate-900/60 backdrop-blur w-fit rounded-2xl border border-slate-800">
      <button 
        @click="tab = 'crm'" 
        :class="['font-bold px-6 py-2.5 rounded-xl transition-all flex items-center gap-2 text-sm', tab === 'crm' ? 'bg-brand-accent text-slate-950 shadow-md font-black' : 'text-slate-400 hover:text-white hover:bg-slate-800']"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
        Panel de Clientes & Servicios
      </button>
      <button 
        @click="tab = 'packages'" 
        :class="['font-bold px-6 py-2.5 rounded-xl transition-all flex items-center gap-2 text-sm', tab === 'packages' ? 'bg-gradient-to-r from-purple-500 to-pink-500 text-white shadow-md font-black' : 'text-slate-400 hover:text-white hover:bg-slate-800']"
      >
        <span>🎁</span>
        <span>Mis Paquetes & Combos ({{ resellerStore.myPackages.length }})</span>
      </button>
      <button 
        @click="tab = 'physical_orders'" 
        :class="['font-bold px-6 py-2.5 rounded-xl transition-all flex items-center gap-2 text-sm', tab === 'physical_orders' ? 'bg-amber-400 text-slate-950 shadow-md font-black' : 'text-slate-400 hover:text-white hover:bg-slate-800']"
      >
        <span>📦</span>
        <span>Pedidos Físicos & Comisiones ({{ resellerStore.myPhysicalOrders.length }})</span>
      </button>
      <button 
        @click="tab = 'marketing'" 
        :class="['font-bold px-6 py-2.5 rounded-xl transition-all flex items-center gap-2 text-sm', tab === 'marketing' ? 'bg-brand-accent text-slate-950 shadow-md font-black' : 'text-slate-400 hover:text-white hover:bg-slate-800']"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path></svg>
        Suite de Marketing & Flyers
      </button>
    </div>

    <!-- Pestaña CRM -->
    <div v-if="tab === 'crm'" class="space-y-6 animate-in fade-in duration-300">
      
      <!-- ALERTA DE NUEVAS CUENTAS ACTUALIZADAS POR EL PROVEEDOR EN RENOVACIONES -->
      <div v-if="pendingRenewalsAlert.length > 0" class="bg-amber-950/40 border border-amber-500/40 p-4 rounded-2xl flex flex-col md:flex-row justify-between items-start md:items-center gap-3">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center shrink-0">
            🔔
          </div>
          <div>
            <h4 class="font-black text-amber-300 text-sm">El Proveedor modificó datos de cuenta para renovación</h4>
            <p class="text-xs text-slate-300">Hay {{ pendingRenewalsAlert.length }} cliente(s) con nuevas credenciales listas para confirmar y entregar.</p>
          </div>
        </div>
        <div class="flex flex-wrap gap-2">
          <button 
            v-for="ren in pendingRenewalsAlert" 
            :key="ren.id" 
            @click="confirmRenewalFromProvider(ren)"
            class="bg-amber-500 hover:bg-amber-400 text-slate-950 px-3 py-1.5 rounded-lg text-xs font-black transition-all shadow"
          >
            Aceptar cuenta de {{ ren.clientName }} ({{ ren.newCredentials.account }})
          </button>
        </div>
      </div>

      <!-- Panel de Rentabilidad y Gestión de Billetera -->
      <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-5 gap-4">
        <div class="p-4 rounded-2xl bg-gradient-to-br from-slate-900 to-slate-950 border border-slate-800">
          <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Clientes</p>
          <p class="text-2xl font-black text-white mt-1">{{ resellerStore.counts.total }}</p>
        </div>
        
        <div class="p-4 rounded-2xl bg-gradient-to-br from-slate-900 to-emerald-950/40 border border-emerald-500/30">
          <p class="text-xs font-semibold text-emerald-400 uppercase tracking-wider">Ganancia Acumulada</p>
          <p class="text-2xl font-black text-emerald-400 mt-1">${{ resellerStore.totalProfit }} <span class="text-xs font-normal text-slate-400">ARS</span></p>
        </div>

        <div class="p-4 rounded-2xl bg-gradient-to-br from-slate-900 to-amber-950/40 border border-amber-500/30">
          <p class="text-xs font-semibold text-amber-400 uppercase tracking-wider">Por Vencer (&lt;48h)</p>
          <p class="text-2xl font-black text-amber-400 mt-1">{{ resellerStore.counts.porVencer }}</p>
        </div>

        <div class="p-4 rounded-2xl bg-gradient-to-br from-slate-900 to-red-950/40 border border-red-500/30">
          <p class="text-xs font-semibold text-red-400 uppercase tracking-wider">Vencidas</p>
          <p class="text-2xl font-black text-red-400 mt-1">{{ resellerStore.counts.vencidas }}</p>
        </div>

        <!-- Billetera Virtual del Revendedor: Administrada y Cargada desde Admin -->
        <div class="col-span-2 sm:col-span-4 lg:col-span-1 p-4 rounded-2xl bg-gradient-to-br from-slate-900 to-indigo-950/80 border border-slate-700/60 flex flex-col justify-between">
          <div>
            <div class="flex justify-between items-center">
              <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Billetera Revendedor</p>
              <span class="text-[10px] font-mono text-emerald-400 font-bold bg-emerald-500/10 px-1.5 py-0.5 rounded border border-emerald-500/20">Carga Admin</span>
            </div>
            <p class="text-2xl font-black text-brand-accent mt-0.5">${{ resellerStore.wallet }} <span class="text-xs text-slate-400 font-normal">ARS</span></p>
          </div>
          <div class="grid grid-cols-2 gap-1.5 mt-2">
            <button @click="showWalletModal = true" class="bg-indigo-600/30 hover:bg-indigo-600 text-[11px] font-bold py-1.5 px-1.5 rounded-lg text-indigo-200 border border-indigo-500/30 transition-colors flex items-center justify-center gap-1" title="Ver Historial de Movimientos de Billetera">
              <span>📊</span>
              <span>Historial</span>
            </button>
            <button @click="showCalcModal = true" class="bg-emerald-600/30 hover:bg-emerald-600 text-[11px] font-bold py-1.5 px-1.5 rounded-lg text-emerald-200 border border-emerald-500/30 transition-colors flex items-center justify-center gap-1" title="Calcular Ganancias Mayoristas">
              <span>🧮</span>
              <span>Simulador</span>
            </button>
          </div>
          <button @click="showProviderBankModal = true" class="mt-1.5 bg-slate-800 hover:bg-slate-700 text-[11px] font-bold py-1 px-2 rounded-lg text-slate-200 border border-slate-700 transition-colors flex items-center justify-center gap-1">
            <span>🏦 Ver Datos de Pago</span>
          </button>
        </div>
      </div>

      <!-- LINK ÚNICO DE AFILIADO / TIENDA POR REVENDEDOR -->
      <div class="bg-gradient-to-r from-blue-950/40 via-indigo-950/40 to-slate-900/80 p-4 rounded-2xl border border-indigo-500/30 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 shadow-xl">
        <div class="flex items-center gap-3">
          <div class="w-11 h-11 rounded-2xl bg-brand-accent/15 border border-brand-accent/30 flex items-center justify-center text-brand-accent text-xl shrink-0">
            🔗
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h4 class="font-black text-white text-sm">Tu Link Único de Tienda Oficial</h4>
              <span class="text-[11px] font-mono font-bold bg-brand-accent/20 text-brand-accent px-2 py-0.5 rounded border border-brand-accent/30">
                Ref: {{ currentResellerId }}
              </span>
            </div>
            <p class="text-xs text-slate-400 font-mono mt-0.5 break-all">{{ uniqueAffiliateLink }}</p>
          </div>
        </div>
        <div class="flex flex-wrap gap-2 w-full md:w-auto">
          <button 
            @click="copyAffiliateLink" 
            class="bg-brand-accent hover:bg-sky-400 text-slate-950 text-xs font-black px-3.5 py-2.5 rounded-xl transition-all shadow flex items-center justify-center gap-1.5 flex-1 md:flex-initial"
            title="Copiar link al portapapeles"
          >
            <span>📋</span>
            <span>Copiar Enlace</span>
          </button>
          <button 
            @click="shareStoreToWhatsApp" 
            class="bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black px-3.5 py-2.5 rounded-xl transition-all shadow flex items-center justify-center gap-1.5 flex-1 md:flex-initial"
            title="Enviar catálogo por WhatsApp"
          >
            <span>💬</span>
            <span>WhatsApp</span>
          </button>
          <button 
            @click="showQrModal = true" 
            class="bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-black px-3.5 py-2.5 rounded-xl transition-all shadow flex items-center justify-center gap-1.5 flex-1 md:flex-initial"
            title="Generar código QR para escanear o imprimir"
          >
            <span>📱</span>
            <span>Código QR</span>
          </button>
          <a 
            :href="uniqueAffiliateLink" 
            target="_blank" 
            class="bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold px-3 py-2.5 rounded-xl border border-slate-700 transition-all flex items-center justify-center gap-1.5"
            title="Abrir en pestaña nueva"
          >
            <span>🌐 Tienda</span>
          </a>
        </div>
      </div>

      <!-- BANNER DE ALERTAS PRIORITARIAS DEL PROVEEDOR (EVENTUALIDADES / CLAVES: YA FUE AVISADO) -->
      <div v-if="resellerAlerts.length > 0" class="bg-indigo-950/40 border border-indigo-500/40 p-4 rounded-2xl space-y-3">
        <div class="flex items-center justify-between border-b border-indigo-500/20 pb-2">
          <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-indigo-400 animate-ping"></span>
            <h4 class="font-black text-indigo-300 text-sm">Alertas del Proveedor (Auditoría: YA FUE AVISADO)</h4>
          </div>
          <div class="flex items-center gap-3">
            <span class="text-[11px] font-mono text-slate-400">{{ resellerAlerts.length }} comunicado(s)</span>
            <button 
              @click="clearAllResellerAlerts" 
              class="text-[11px] text-red-400 hover:text-red-300 font-bold underline cursor-pointer"
            >
              Borrar todas
            </button>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div v-for="alert in resellerAlerts" :key="alert.id" class="bg-[#020617] p-3.5 rounded-xl border border-indigo-500/30 flex justify-between items-start gap-3">
            <div class="space-y-1 text-xs flex-1">
              <div class="flex items-center gap-2">
                <span class="text-[10px] font-black uppercase tracking-wider bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 px-2 py-0.5 rounded">
                  {{ alert.statusText || 'YA FUE AVISADO' }}
                </span>
                <span class="text-slate-400 font-mono text-[11px]">{{ alert.date }}</span>
              </div>
              <h5 class="font-bold text-white text-sm">{{ alert.title }}</h5>
              <p class="text-slate-300 text-xs leading-relaxed">{{ alert.message }}</p>
              <div v-if="alert.clientNumber" class="text-[11px] text-brand-accent font-mono pt-1">
                Cliente: {{ alert.clientName }} ({{ alert.clientNumber }})
              </div>
            </div>
            <button 
              @click="dismissAlert(alert.id)" 
              class="text-slate-400 hover:text-red-400 hover:bg-red-500/10 p-1.5 rounded-lg border border-slate-800 transition-colors shrink-0 text-xs font-bold"
              title="Borrar o cerrar notificación"
            >
              ✕ Borrar
            </button>
          </div>
        </div>
      </div>

      <!-- Buscador y Barra de Acciones Pro del Revendedor -->
      <div class="flex flex-col lg:flex-row justify-between items-stretch lg:items-center gap-4 bg-slate-900/60 p-4 rounded-2xl border border-slate-800">
        <!-- Selector de Revendedor por Número y Nombre -->
        <div class="flex items-center gap-2">
          <span class="text-xs text-slate-400 font-bold uppercase whitespace-nowrap">Revendedor:</span>
          <select v-model="selectedResellerFilter" class="bg-[#020617] border border-slate-800 rounded-xl px-3 py-2 text-xs text-white font-semibold outline-none focus:border-brand-accent">
            <option value="All">Todos los Revendedores</option>
            <option v-for="res in adminStore.resellers" :key="res.id" :value="res.id">
              [{{ res.id }}] {{ res.name }}
            </option>
          </select>
        </div>

        <div class="relative flex-1">
          <svg class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
          <input 
            v-model="searchQuery" 
            type="text" 
            placeholder="Buscar por Número de Cliente (CLI-...), Nombre, Plataforma o Cuenta..." 
            class="w-full bg-[#020617] border border-slate-800 focus:border-brand-accent rounded-xl py-2.5 pl-10 pr-4 text-xs text-white outline-none"
          >
        </div>

        <!-- Botones de Acción: Compras Manuales y Datos Bancarios del Proveedor -->
        <div class="flex flex-wrap gap-2">
          <button 
            @click="showManualPurchaseModal = true" 
            class="bg-brand-accent hover:bg-sky-400 text-slate-950 font-black text-xs px-4 py-2.5 rounded-xl transition-all shadow flex items-center gap-2"
          >
            <span>🛒 Solicitar Compra Manual</span>
          </button>
          
          <button 
            @click="showProviderBankModal = true" 
            class="bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-bold text-xs px-4 py-2.5 rounded-xl transition-all flex items-center gap-2"
          >
            <span>🏦 Datos Bancarios Proveedor</span>
          </button>
        </div>
      </div>

      <!-- PESTAÑAS SELECCIONABLES DE CUENTAS: TODAS, ACTIVAS, POR VENCER, VENCIDAS, REEMPLAZOS -->
      <div class="flex flex-wrap items-center justify-between gap-3 bg-slate-950/80 p-2.5 rounded-2xl border border-slate-800">
        <div class="flex flex-wrap gap-1.5">
          <button 
            @click="filterStatus = 'All'" 
            :class="['px-3.5 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2', filterStatus === 'All' ? 'bg-brand-accent text-slate-950 font-black shadow' : 'text-slate-400 hover:text-white hover:bg-slate-900']"
          >
            <span>Todas</span>
            <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-slate-800 text-slate-300 font-mono">{{ resellerStore.counts.total }}</span>
          </button>

          <button 
            @click="filterStatus = 'Activa'" 
            :class="['px-3.5 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2', filterStatus === 'Activa' ? 'bg-emerald-500 text-slate-950 font-black shadow' : 'text-emerald-400 hover:text-white hover:bg-slate-900']"
          >
            <span>Activas</span>
            <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-emerald-950 border border-emerald-500/30 text-emerald-300 font-mono">{{ resellerStore.counts.activas }}</span>
          </button>

          <button 
            @click="filterStatus = 'Por Vencer'" 
            :class="['px-3.5 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2', filterStatus === 'Por Vencer' ? 'bg-amber-500 text-slate-950 font-black shadow animate-pulse' : 'text-amber-400 hover:text-white hover:bg-slate-900']"
          >
            <span>⚠️ Por Vencer (&lt;48h)</span>
            <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-amber-950 border border-amber-500/30 text-amber-300 font-mono">{{ resellerStore.counts.porVencer }}</span>
          </button>

          <button 
            @click="filterStatus = 'Vencida'" 
            :class="['px-3.5 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2', filterStatus === 'Vencida' ? 'bg-red-500 text-white font-black shadow' : 'text-red-400 hover:text-white hover:bg-slate-900']"
          >
            <span>🚨 Vencidas</span>
            <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-red-950 border border-red-500/30 text-red-300 font-mono">{{ resellerStore.counts.vencidas }}</span>
          </button>

          <button 
            @click="filterStatus = 'En Reemplazo'" 
            :class="['px-3.5 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2', filterStatus === 'En Reemplazo' ? 'bg-purple-600 text-white font-black shadow' : 'text-purple-400 hover:text-white hover:bg-slate-900']"
          >
            <span>🔧 En Reemplazo</span>
            <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-purple-950 border border-purple-500/30 text-purple-300 font-mono">{{ resellerStore.counts.enReemplazo }}</span>
          </button>
        </div>

        <span class="text-xs font-mono text-slate-400 px-2">
          Mostrando: <strong class="text-white">{{ filteredClients.length }}</strong> cuentas
        </span>
      </div>

      <!-- Tabla de Clientes con Identificador de Número de Cliente y Revendedor -->
      <div class="bg-slate-900/50 rounded-2xl border border-slate-800 overflow-hidden backdrop-blur-sm shadow-xl">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm whitespace-nowrap">
            <thead class="bg-slate-950/80 text-slate-400 text-xs uppercase tracking-wider font-bold border-b border-slate-800">
              <tr>
                <th class="px-6 py-4">N° Cliente / Nombre</th>
                <th class="px-6 py-4">Revendedor Asignado</th>
                <th class="px-6 py-4">Servicio & Pantallas</th>
                <th class="px-6 py-4">Perfiles Asignados</th>
                <th class="px-6 py-4">Vencimiento SLA</th>
                <th class="px-6 py-4">Estado</th>
                <th class="px-6 py-4 text-center">Acciones Rápidas Pro</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
              <tr v-for="client in filteredClients" :key="client.id" class="hover:bg-slate-800/40 transition-colors">
                <!-- N° Cliente y Nombre -->
                <td class="px-6 py-4">
                  <div class="flex items-center gap-2">
                    <span class="font-mono text-xs font-bold bg-brand-accent/15 text-brand-accent px-2 py-0.5 rounded border border-brand-accent/30">
                      {{ client.clientNumber || client.id }}
                    </span>
                    <span class="font-bold text-white">{{ client.name }}</span>
                  </div>
                  <div class="text-[11px] font-mono text-slate-400 mt-0.5">{{ client.phone }}</div>
                </td>

                <!-- Revendedor Número y Nombre -->
                <td class="px-6 py-4">
                  <span class="text-xs font-mono font-semibold bg-slate-950 text-slate-300 px-2 py-1 rounded border border-slate-800">
                    {{ client.resellerId }}
                  </span>
                </td>

                <!-- Plataforma y Plan -->
                <td class="px-6 py-4">
                  <div class="font-black text-slate-100">{{ client.platform }}</div>
                  <div class="text-xs text-slate-400 mt-0.5">
                    <span class="bg-blue-500/20 text-blue-300 px-2 py-0.5 rounded font-semibold text-[11px]">{{ client.planName || client.devices + ' Pantallas' }}</span>
                  </div>
                </td>

                <!-- Perfiles con Nombre y PIN -->
                <td class="px-6 py-4">
                  <div class="flex flex-wrap gap-1 max-w-xs">
                    <div v-for="(p, pIdx) in client.profiles" :key="pIdx" class="bg-slate-950 px-2 py-0.5 rounded text-[11px] font-mono border border-slate-800 flex items-center gap-1">
                      <span class="text-slate-300">{{ p.name }}</span>
                      <span class="text-amber-400 font-bold bg-amber-400/10 px-1 rounded">PIN: {{ p.pin }}</span>
                    </div>
                  </div>
                </td>

                <!-- Vencimiento -->
                <td class="px-6 py-4">
                  <div class="font-semibold text-slate-200 text-xs">{{ client.expiry }}</div>
                  <span :class="['text-[10px] font-bold px-2 py-0.5 rounded border inline-block mt-0.5', getExpiryBadge(client.expiry).class]">
                    {{ getExpiryBadge(client.expiry).text }}
                  </span>
                </td>

                <!-- Estado -->
                <td class="px-6 py-4">
                  <span :class="[
                    'px-2.5 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1.5 border', 
                    client.status === 'Activa' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 
                    client.status === 'Por Vencer' ? 'bg-amber-500/10 text-amber-400 border-amber-500/20' : 
                    client.status === 'Vencida' ? 'bg-red-500/10 text-red-400 border-red-500/20' :
                    'bg-purple-500/10 text-purple-400 border-purple-500/20'
                  ]">
                    <span :class="['w-1.5 h-1.5 rounded-full', 
                      client.status === 'Activa' ? 'bg-emerald-400' : 
                      client.status === 'Por Vencer' ? 'bg-amber-400' : 
                      client.status === 'Vencida' ? 'bg-red-400' : 'bg-purple-400'
                    ]"></span>
                    {{ client.status }}
                  </span>
                </td>

                <!-- Acciones Rápidas Pro (Sin solicitud de reemplazo para revendedor) -->
                <td class="px-6 py-4">
                  <div class="flex items-center gap-2">
                    <!-- Comprobante Digital de Entrega en Imagen / PDF -->
                    <button 
                      @click="openVoucherModal(client)" 
                      class="text-amber-400 hover:text-slate-950 hover:bg-amber-400 bg-slate-950 p-2 rounded-xl border border-amber-500/30 transition-all shadow-sm" 
                      title="Ver y Descargar Comprobante Digital Oficial"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </button>

                    <!-- Alerta Automática de Renovación Urgente a WhatsApp -->
                    <button 
                      v-if="getExpiryDays(client.expiry) <= 5"
                      @click="sendUrgentRenewalReminder(client)" 
                      class="text-amber-300 hover:text-slate-950 hover:bg-amber-400 bg-amber-500/15 p-2 rounded-xl border border-amber-500/40 transition-all shadow-sm animate-pulse" 
                      title="Enviar Alerta Urgente de Vencimiento a WhatsApp con 1 Clic"
                    >
                      <span class="text-xs">⚡</span>
                    </button>

                    <!-- WhatsApp con Credenciales y PINs -->
                    <a 
                      :href="formatWhatsAppReminder(client)" 
                      target="_blank" 
                      class="text-emerald-400 hover:text-white hover:bg-emerald-600 bg-slate-950 p-2 rounded-xl border border-emerald-500/30 transition-all shadow-sm" 
                      title="Enviar WhatsApp con Credenciales y PINs"
                    >
                      <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86s.274.072.376-.043c.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.045.072.045.419-.099.824z"/></svg>
                    </a>

                    <!-- Renovar (+30 Días) -->
                    <button 
                      @click="handleRenew(client)" 
                      class="text-blue-400 hover:text-white hover:bg-blue-600 bg-slate-950 p-2 rounded-xl border border-blue-500/30 transition-all shadow-sm" 
                      title="Renovar (+30 días)"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    </button>

                    <!-- Reportar Caída (SIN RECOMPENSA) -->
                    <button 
                      v-if="client.status !== 'En Reemplazo'" 
                      @click="handleReportFailure(client)" 
                      class="text-red-400 hover:text-white hover:bg-red-600 bg-slate-950 p-2 rounded-xl border border-red-500/30 transition-all shadow-sm" 
                      title="Reportar Caída Técnica"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </button>

                    <!-- Ver Ficha y Editar Perfiles -->
                    <button 
                      @click="openDetails(client)" 
                      class="text-slate-400 hover:text-brand-accent bg-slate-950 p-2 rounded-xl border border-slate-800 hover:border-brand-accent/40 transition-all"
                      title="Ver Ficha y Editar Nombres/PINs"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Pestaña Mis Paquetes & Ofertas para el Revendedor -->
    <div v-else-if="tab === 'packages'" class="space-y-8 animate-in fade-in duration-300">
      <!-- Hero de Paquetes -->
      <div class="bg-gradient-to-r from-purple-950/60 via-pink-950/40 to-slate-900 p-6 rounded-3xl border border-pink-500/30 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
          <div class="flex items-center gap-2">
            <span class="text-xs font-bold uppercase tracking-wider bg-pink-500/20 text-pink-300 px-2.5 py-0.5 rounded-full border border-pink-500/30">
              Combos & Ganancia Mayorista
            </span>
            <span class="text-xs font-mono text-slate-400">Distribuidor: {{ currentResellerId }}</span>
          </div>
          <h3 class="text-2xl font-black text-white mt-1">Armador de Combos & Ofertas Personalizadas</h3>
          <p class="text-xs text-slate-300 mt-1">Combina cuentas a precio mayorista, define tu precio de venta y genera automáticamente el Canva publicitario y los copys de venta.</p>
        </div>
        
        <button 
          @click="tab = 'marketing'; marketingMode = 'package'"
          class="bg-gradient-to-r from-pink-500 to-purple-600 hover:from-pink-400 hover:to-purple-500 text-white font-black text-xs px-5 py-3 rounded-xl transition-all shadow-lg shadow-pink-500/20 flex items-center gap-2 shrink-0"
        >
          <span>🎨 Abrir Generador Canva & Copys</span>
          <span>→</span>
        </button>
      </div>

      <!-- FORMULARIO CREAR COMBO PROPIO (REVENDEDOR) -->
      <form @submit.prevent="submitCreateResellerPackage" class="bg-slate-900/60 p-6 sm:p-8 rounded-3xl border border-slate-800 backdrop-blur-md space-y-6">
        <div class="flex justify-between items-center border-b border-slate-800 pb-3">
          <div class="flex items-center gap-2">
            <span class="text-xl">🛠️</span>
            <h4 class="text-lg font-black text-white">Armar un Nuevo Combo para Mis Clientes</h4>
          </div>
          <span class="text-xs text-slate-400">Selecciona 2 o más cuentas</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <div class="lg:col-span-2">
            <label class="block text-xs font-bold text-slate-400 uppercase mb-1.5">Nombre del Combo</label>
            <input 
              v-model="resellerPackageForm.title" 
              type="text" 
              placeholder="ej. Pack Cine Total + Música (Netflix + Disney+ + Spotify)" 
              required
              class="w-full bg-[#020617] border border-slate-800 focus:border-pink-500 rounded-xl p-3 text-white outline-none text-sm font-semibold"
            >
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-400 uppercase mb-1.5">Badge de Promoción</label>
            <input 
              v-model="resellerPackageForm.badge" 
              type="text" 
              placeholder="ej. 🔥 20% OFF, ⚡ COMBO VIP" 
              required
              class="w-full bg-[#020617] border border-slate-800 focus:border-pink-500 rounded-xl p-3 text-white outline-none text-sm font-semibold"
            >
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-400 uppercase mb-1.5">Detalle / Beneficios para el Cliente</label>
          <input 
            v-model="resellerPackageForm.description" 
            type="text" 
            placeholder="ej. Cuentas privadas y oficiales con PIN personal y soporte postventa garantizado todo el mes." 
            class="w-full bg-[#020617] border border-slate-800 focus:border-pink-500 rounded-xl p-3 text-white outline-none text-sm"
          >
        </div>

        <!-- SELECTOR DE PLATAFORMAS EN EL COMBO -->
        <div class="space-y-3 bg-slate-950 p-5 rounded-2xl border border-slate-800">
          <div class="flex justify-between items-center">
            <span class="text-xs font-bold text-slate-300 uppercase tracking-wider">Cuentas que Integran el Combo:</span>
            <button 
              type="button" 
              @click="addServiceToResellerPackage" 
              class="bg-pink-600 hover:bg-pink-500 text-white font-bold text-xs px-3 py-1.5 rounded-xl transition-all flex items-center gap-1.5 shadow"
            >
              <span>+ Sumar Otra Plataforma</span>
            </button>
          </div>

          <div class="space-y-2">
            <div 
              v-for="(srv, sIdx) in resellerPackageForm.selectedServices" 
              :key="sIdx"
              class="flex flex-wrap items-center gap-3 bg-slate-900/70 p-3 rounded-xl border border-slate-800"
            >
              <div class="w-8 h-8 rounded-lg bg-slate-950 border border-slate-800 flex items-center justify-center text-sm font-mono text-pink-400 shrink-0">
                #{{ sIdx + 1 }}
              </div>

              <!-- Plataforma -->
              <div class="flex-1 min-w-[130px]">
                <label class="text-[10px] text-slate-400 uppercase block mb-0.5">Plataforma</label>
                <select 
                  v-model="srv.platform" 
                  @change="srv.planKey = Object.keys(adminStore.pricingCatalog[srv.platform]?.plans || {})[0] || '1_perfil'"
                  class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2 text-xs text-white outline-none font-semibold"
                >
                  <option v-for="(cat, pKey) in adminStore.pricingCatalog" :key="pKey" :value="pKey">
                    {{ pKey }}
                  </option>
                </select>
              </div>

              <!-- Plan -->
              <div class="flex-1 min-w-[170px]">
                <label class="text-[10px] text-slate-400 uppercase block mb-0.5">Plan</label>
                <select 
                  v-model="srv.planKey" 
                  class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2 text-xs text-white outline-none font-semibold"
                >
                  <option 
                    v-for="(pl, planKey) in adminStore.pricingCatalog[srv.platform]?.plans" 
                    :key="planKey" 
                    :value="planKey"
                  >
                    {{ pl.label }} (Costo: ${{ pl.providerCost }})
                  </option>
                </select>
              </div>

              <!-- Costo mayorista que pagas -->
              <div class="text-right shrink-0 px-2">
                <span class="text-[10px] text-slate-400 block">Tu Costo:</span>
                <span class="text-xs font-mono font-bold text-amber-300">
                  ${{ (adminStore.pricingCatalog[srv.platform]?.plans[srv.planKey]?.providerCost || 0).toLocaleString() }}
                </span>
              </div>

              <button 
                v-if="resellerPackageForm.selectedServices.length > 1"
                type="button" 
                @click="removeServiceFromResellerPackage(sIdx)"
                class="text-red-400 hover:text-red-300 p-1.5 hover:bg-red-500/10 rounded-lg text-xs"
                title="Quitar"
              >
                ✕
              </button>
            </div>
          </div>
        </div>

        <!-- CÁLCULO DE GANANCIAS EN TIEMPO REAL -->
        <div class="bg-gradient-to-r from-slate-950 via-purple-950/30 to-slate-950 p-5 rounded-2xl border border-pink-500/20 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-center">
          <div>
            <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Costo Mayorista Total:</span>
            <span class="text-lg font-black text-white font-mono">${{ resellerPackageCalc.cost.toLocaleString() }} ARS</span>
          </div>

          <div>
            <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Precio Sugerido Regular:</span>
            <span class="text-lg font-black text-slate-400 line-through font-mono">${{ resellerPackageCalc.regular.toLocaleString() }} ARS</span>
          </div>

          <div>
            <label class="text-[10px] uppercase font-bold text-pink-300 block tracking-wider mb-1">Tu Precio Venta Promo (ARS):</label>
            <input 
              v-model="resellerPackageForm.promoPrice" 
              type="number" 
              step="100" 
              required
              class="w-full bg-[#020617] border border-pink-500/50 rounded-xl p-2.5 text-base font-black text-pink-400 font-mono outline-none"
            >
          </div>

          <div class="bg-emerald-500/10 p-3 rounded-xl border border-emerald-500/30 text-center">
            <span class="text-[10px] text-emerald-400 uppercase font-bold block">Tu Ganancia Neta:</span>
            <span class="text-xl font-black text-emerald-400">+${{ resellerPackageCalc.profit.toLocaleString() }} ARS</span>
            <span class="text-[10px] text-slate-400 block">({{ resellerPackageCalc.discount }}% descuento cliente)</span>
          </div>
        </div>

        <div class="flex justify-end pt-2">
          <button 
            type="submit" 
            class="bg-gradient-to-r from-pink-500 to-purple-600 hover:from-pink-400 hover:to-purple-500 text-white px-8 py-3.5 font-black rounded-xl transition-all shadow-lg shadow-pink-500/25 text-sm flex items-center gap-2"
          >
            <span>🚀 Publicar Mi Paquete & Vender</span>
          </button>
        </div>
      </form>

      <!-- CATÁLOGO DE COMBOS DISPONIBLES CON BOTÓN PARA GENERAR CANVA & COPY -->
      <div class="bg-slate-900/60 p-6 sm:p-8 rounded-3xl border border-slate-800 backdrop-blur-md space-y-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 border-b border-slate-800 pb-4">
          <div>
            <h3 class="text-xl font-black text-white">Combos Disponibles para Vender & Promocionar</h3>
            <p class="text-xs text-slate-400">Haz clic en "🎨 Generar Flyer & Copy" en cualquier paquete para generar automáticamente el arte en Canva y los textos de redes sociales.</p>
          </div>
          <span class="text-xs font-mono text-slate-400">{{ resellerStore.myPackages.length }} combos activos</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div 
            v-for="pkg in resellerStore.myPackages" 
            :key="pkg.id"
            class="bg-[#020617] rounded-2xl border border-slate-800 hover:border-pink-500/40 p-5 flex flex-col justify-between space-y-4 transition-all shadow-lg group"
          >
            <div class="space-y-3">
              <div class="flex justify-between items-start gap-2">
                <span class="text-[10px] font-black uppercase tracking-wider bg-pink-500/20 text-pink-300 border border-pink-500/30 px-2.5 py-0.5 rounded-full">
                  {{ pkg.badge }}
                </span>
                <span :class="[
                  'text-[10px] font-bold px-2 py-0.5 rounded border font-mono',
                  pkg.createdBy === 'provider' ? 'bg-sky-500/10 text-sky-400 border-sky-500/20' : 'bg-pink-500/10 text-pink-400 border-pink-500/20'
                ]">
                  {{ pkg.createdBy === 'provider' ? 'Oficial Proveedor' : 'Tu Combo Personalizado' }}
                </span>
              </div>

              <div>
                <h4 class="font-bold text-white text-base group-hover:text-pink-300 transition-colors">{{ pkg.title }}</h4>
                <p class="text-xs text-slate-400 mt-1 line-clamp-2">{{ pkg.description }}</p>
              </div>

              <!-- Cuentas incluidas -->
              <div class="bg-slate-900/60 p-3 rounded-xl border border-slate-800/80 space-y-1.5">
                <span class="text-[10px] uppercase font-bold text-slate-400 block">Cuentas que incluye:</span>
                <div v-for="(s, sIdx) in pkg.services" :key="sIdx" class="flex justify-between items-center text-xs">
                  <span class="text-slate-200 font-semibold">{{ s.platform }} ({{ s.planLabel }})</span>
                  <span class="text-[10px] text-slate-400 font-mono">${{ s.regularPrice.toLocaleString() }}</span>
                </div>
              </div>

              <!-- Resumen Financiero para el Revendedor -->
              <div class="bg-slate-950 p-3 rounded-xl border border-slate-800/80 flex justify-between items-center text-xs">
                <div>
                  <span class="text-[10px] text-slate-400 uppercase block">Precio Cliente:</span>
                  <span class="text-base font-black text-white font-mono">${{ pkg.promoPrice.toLocaleString() }} ARS</span>
                </div>
                <div class="text-right bg-emerald-500/10 px-2.5 py-1 rounded-lg border border-emerald-500/20">
                  <span class="text-[10px] text-emerald-400 uppercase font-bold block">Tu Ganancia:</span>
                  <span class="text-sm font-black text-emerald-400 font-mono">+${{ pkg.resellerProfit.toLocaleString() }} ARS</span>
                </div>
              </div>
            </div>

            <!-- Botones de Acción -->
            <div class="space-y-2 pt-2 border-t border-slate-800">
              <button 
                @click="loadPackageToMarketing(pkg)"
                class="w-full bg-gradient-to-r from-pink-500 to-purple-600 hover:from-pink-400 hover:to-purple-500 text-white font-black text-xs py-2.5 px-4 rounded-xl transition-all shadow-md flex items-center justify-center gap-2"
              >
                <span>🎨 Generar Flyer & Copy en Marketing</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Pestaña Pedidos Físicos y Comisiones para el Revendedor -->
    <div v-else-if="tab === 'physical_orders'" class="space-y-6 animate-in fade-in duration-300">
      <!-- Métricas de Comisiones Físicas -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="p-5 rounded-2xl bg-gradient-to-br from-slate-900 to-amber-950/40 border border-amber-500/30">
          <p class="text-xs font-semibold text-amber-400 uppercase tracking-wider">Pedidos Físicos Totales</p>
          <p class="text-3xl font-black text-white mt-1">{{ resellerStore.myPhysicalOrders.length }}</p>
          <span class="text-[11px] text-slate-400 mt-1 block">Asignados a tu ID: {{ currentResellerId }}</span>
        </div>

        <div class="p-5 rounded-2xl bg-gradient-to-br from-slate-900 to-blue-950/40 border border-blue-500/30">
          <p class="text-xs font-semibold text-blue-400 uppercase tracking-wider">Pendientes / En Asesoría</p>
          <p class="text-3xl font-black text-amber-300 mt-1">{{ resellerStore.myPendingPhysicalOrders.length + resellerStore.myReceivedPhysicalOrders.length }}</p>
          <span class="text-[11px] text-slate-400 mt-1 block">{{ resellerStore.myPendingPhysicalOrders.length }} pendientes • {{ resellerStore.myReceivedPhysicalOrders.length }} en contacto</span>
        </div>

        <div class="p-5 rounded-2xl bg-gradient-to-br from-slate-900 to-emerald-950/40 border border-emerald-500/30">
          <p class="text-xs font-semibold text-emerald-400 uppercase tracking-wider">Comisiones Confirmadas (1% y 3%)</p>
          <p class="text-3xl font-black text-emerald-400 mt-1">+${{ resellerStore.myPhysicalCommissionsEarned.toLocaleString() }} <span class="text-xs font-normal text-slate-400">ARS</span></p>
          <span class="text-[11px] text-emerald-400/80 mt-1 block">Acreditadas en tu Billetera disponible</span>
        </div>
      </div>

      <!-- Explicativo de Fidelización & Reglas de Comisión -->
      <div class="bg-gradient-to-r from-amber-950/40 via-orange-950/30 to-slate-900 p-5 rounded-2xl border border-amber-500/30 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 shadow-xl">
        <div class="space-y-1">
          <div class="flex items-center gap-2">
            <span class="text-xs font-black uppercase tracking-wider bg-amber-400/20 text-amber-300 border border-amber-400/30 px-2 py-0.5 rounded">
              Reglas de Comisión Automática
            </span>
            <span class="text-xs text-slate-300">Catálogo Físico StreamPro</span>
          </div>
          <h4 class="text-base font-bold text-white">¿Cómo ganas comisiones con productos físicos?</h4>
          <p class="text-xs text-slate-300 max-w-2xl leading-relaxed">
            • <strong>1% de Comisión:</strong> Cuando tus clientes compran navegando en tu tienda general.<br>
            • <strong>3% de Comisión:</strong> Cuando compartes el enlace individual de un producto (<code>?ref={{ currentResellerId }}&prod=ID</code>).<br>
            • <strong>Flujo Seguro de Compra:</strong> El cliente registra el pedido como <strong>Pendiente</strong> sin transferir dinero previo. Al pasar a <strong>Recibido</strong>, un vendedor oficial lo contacta por WhatsApp para confirmar stock y datos de pago. Al momento de <strong>Confirmar la compra</strong> el Administrador, tu comisión se acredita automáticamente en tu Billetera como saldo a favor disponible.
          </p>
        </div>

        <button 
          @click="showWalletModal = true"
          class="bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-xs px-5 py-3 rounded-xl transition-all shadow flex items-center gap-2 shrink-0"
        >
          <span>💳 Ver Saldo en Billetera (${{ resellerStore.wallet }} ARS)</span>
        </button>
      </div>

      <!-- Lista de Pedidos Físicos Registrados -->
      <div class="bg-slate-900/60 rounded-3xl border border-slate-800 p-6 backdrop-blur-md space-y-4">
        <div class="flex justify-between items-center border-b border-slate-800 pb-3">
          <h3 class="text-lg font-black text-white">Registro de Pedidos de tus Clientes</h3>
          <span class="text-xs font-mono text-slate-400">{{ resellerStore.myPhysicalOrders.length }} orden(es)</span>
        </div>

        <div v-if="resellerStore.myPhysicalOrders.length === 0" class="text-center py-12 text-slate-500 text-xs italic bg-slate-950 rounded-2xl border border-slate-800">
          No tienes pedidos de productos físicos registrados para este distribuidor aún. Comparte los productos con tu link para generar ventas al 3%.
        </div>

        <div v-else class="space-y-4">
          <div 
            v-for="order in resellerStore.myPhysicalOrders" 
            :key="order.id"
            class="bg-[#020617] rounded-2xl border border-slate-800 p-5 space-y-3 shadow-lg"
          >
            <div class="flex flex-wrap justify-between items-start gap-3 border-b border-slate-800 pb-3">
              <div>
                <div class="flex items-center gap-2">
                  <span class="text-xs font-mono font-bold text-amber-400 bg-amber-400/10 px-2 py-0.5 rounded border border-amber-400/30">
                    {{ order.id }}
                  </span>
                  <h4 class="font-bold text-white text-sm">{{ order.customerName }} ({{ order.customerNumber }})</h4>
                  <span class="text-xs text-slate-400 font-mono">{{ order.createdAt }}</span>
                </div>
                <div class="text-xs text-slate-400 mt-1">
                  WhatsApp: <strong class="text-slate-200">{{ order.customerPhone }}</strong> · Origen: <span :class="order.source === 'shared_url' ? 'text-emerald-400 font-bold' : 'text-blue-300'">{{ order.source === 'shared_url' ? '🔗 Link Compartido (3%)' : '🛍️ Tienda (1%)' }}</span>
                </div>
              </div>

              <div class="flex items-center gap-2">
                <span :class="[
                  'text-xs font-bold px-3 py-1 rounded-full border flex items-center gap-1.5',
                  (order.status === 'Confirmado' || order.status === 'Aprobado') ? 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30' :
                  order.status === 'Recibido' ? 'bg-sky-500/15 text-sky-400 border-sky-500/30 animate-pulse' :
                  order.status === 'Cancelado' ? 'bg-red-500/15 text-red-400 border-red-500/30' :
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
                    (order.status === 'Confirmado' || order.status === 'Aprobado') ? 'Confirmado (Comisión en Billetera)' :
                    order.status === 'Recibido' ? 'Recibido (Vendedor Contactando)' :
                    order.status === 'Cancelado' ? 'Cancelado' :
                    'Pendiente'
                  }}
                </span>
              </div>
            </div>

            <!-- Explicación del Estado del Pedido para el Revendedor -->
            <div v-if="order.status === 'Pendiente'" class="bg-amber-500/10 border border-amber-500/20 p-2.5 rounded-xl text-xs text-amber-300 flex items-center gap-2">
              <span>⏳</span>
              <span><strong>Estado PENDIENTE:</strong> El cliente registró sus datos pero NO transfirió dinero. Al pasar a <strong>Recibido</strong>, un vendedor oficial lo contactará por WhatsApp para validar stock y coordinar.</span>
            </div>
            <div v-else-if="order.status === 'Recibido'" class="bg-sky-500/10 border border-sky-500/20 p-2.5 rounded-xl text-xs text-sky-300 flex items-center gap-2">
              <span>📞</span>
              <span><strong>Estado RECIBIDO:</strong> Un vendedor oficial de la tienda física se comunicará con el cliente para asesorarlo. Cuando el Administrador confirme la compra, tu comisión se sumará a tu billetera.</span>
            </div>
            <div v-else-if="order.status === 'Confirmado' || order.status === 'Aprobado'" class="bg-emerald-500/10 border border-emerald-500/20 p-2.5 rounded-xl text-xs text-emerald-300 flex items-center gap-2">
              <span>✅</span>
              <span><strong>Compra CONFIRMADA:</strong> Administrador confirmó la compra. Tu comisión de +${{ order.commissionAmount.toLocaleString() }} ARS ya está acreditada en tu billetera como saldo a favor disponible.</span>
            </div>

            <!-- Items -->
            <div class="space-y-2">
              <div v-for="(pItem, idx) in order.items" :key="idx" class="flex items-center gap-3 bg-slate-900/60 p-2.5 rounded-xl border border-slate-800">
                <img v-if="pItem.image" :src="pItem.image" :alt="pItem.name" class="w-10 h-10 object-contain bg-white rounded p-1 shrink-0">
                <div class="flex-1 min-w-0 text-xs">
                  <div class="font-bold text-white truncate">{{ pItem.name }}</div>
                  <div class="text-slate-400 text-[11px]">
                    {{ pItem.quantity }} un. · ${{ pItem.priceNumeric ? pItem.priceNumeric.toLocaleString() : pItem.price }} ARS
                  </div>
                </div>
              </div>
            </div>

            <!-- Resumen Financiero del Pedido -->
            <div class="bg-slate-950 p-3 rounded-xl border border-slate-800/80 flex flex-wrap justify-between items-center gap-3 text-xs">
              <div class="text-slate-300">
                Dirección: <strong class="text-white">{{ order.shippingAddress }}</strong>
              </div>
              <div class="flex items-center gap-4">
                <div>
                  <span class="text-[10px] text-slate-400 uppercase block">Total Pedido:</span>
                  <span class="text-sm font-bold text-white">${{ order.totalAmount.toLocaleString() }} ARS</span>
                </div>
                <div class="bg-emerald-500/10 px-3 py-1 rounded-lg border border-emerald-500/20 text-right">
                  <span class="text-[10px] text-emerald-400 uppercase font-bold block">Tu Comisión ({{ order.commissionRate * 100 }}%):</span>
                  <span class="text-sm font-black text-emerald-400">+${{ order.commissionAmount.toLocaleString() }} ARS</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- SECCIÓN EXCLUSIVA PARA EL REVENDEDOR: CATÁLOGO FÍSICO CON DESGLOSE DE GANANCIAS (1% vs 3%) -->
      <div class="bg-slate-900/60 rounded-3xl border border-slate-800 p-6 backdrop-blur-md space-y-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 border-b border-slate-800 pb-4">
          <div>
            <div class="flex items-center gap-2">
              <span class="text-xs font-black uppercase tracking-wider bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 px-2 py-0.5 rounded">
                Solo Visible para Distribuidores
              </span>
              <span class="text-xs font-mono text-slate-400">Total: {{ adminStore.physicalProducts.length }} productos</span>
            </div>
            <h3 class="text-xl font-black text-white mt-1">Calculadora de Ganancias & Generador de Links Directos</h3>
            <p class="text-xs text-slate-400">
              Visualiza tu ganancia exacta por producto y obtén tus links de afiliado. Recuerda: <strong>1%</strong> si compran desde tu tienda general y <strong>3%</strong> si compran desde tu link compartido.
            </p>
          </div>
        </div>

        <!-- Filtros y Buscador para el Revendedor -->
        <div class="flex flex-col sm:flex-row gap-3">
          <div class="relative flex-1">
            <input 
              v-model="physicalSearchQuery" 
              type="text" 
              placeholder="Buscar producto por nombre o modelo..."
              class="w-full bg-[#020617] border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white placeholder-slate-500 outline-none focus:border-brand-accent transition-colors"
            >
            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 text-xs">🔍</span>
          </div>

          <select 
            v-model="physicalCatFilter"
            class="bg-[#020617] border border-slate-800 rounded-xl px-3 py-2.5 text-xs text-white outline-none focus:border-brand-accent transition-colors font-medium cursor-pointer"
          >
            <option value="All">Todas las Categorías ({{ adminStore.physicalCategories.length }})</option>
            <option v-for="cat in adminStore.physicalCategories" :key="cat.id" :value="cat.id">
              {{ cat.name }}
            </option>
          </select>
        </div>

        <!-- Grid de Productos con Desglose de Ganancia 1% vs 3% -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 max-h-[600px] overflow-y-auto pr-1 custom-scrollbar">
          <div 
            v-for="prod in filteredPhysicalProductsForReseller.slice(0, 48)" 
            :key="prod.id"
            class="bg-[#020617] border border-slate-800/90 rounded-2xl p-4 flex flex-col justify-between space-y-3 hover:border-slate-700 transition-all shadow-md"
          >
            <div class="flex gap-3.5 items-center">
              <div 
                @click="openProductUrlPreviewModal(prod)"
                class="w-24 h-24 sm:w-28 sm:h-28 bg-white rounded-2xl p-2 shrink-0 flex items-center justify-center shadow-lg border border-slate-200/20 cursor-pointer group hover:ring-2 hover:ring-brand-accent transition-all relative overflow-hidden"
                title="Clic para previsualizar producto en grande"
              >
                <img :src="prod.image" :alt="prod.name" class="max-h-full max-w-full object-contain group-hover:scale-110 transition-transform duration-300">
                <span class="absolute bottom-1 right-1 bg-black/60 text-white rounded p-0.5 text-[9px] opacity-0 group-hover:opacity-100 transition-opacity">🔍</span>
              </div>
              <div class="flex-1 min-w-0">
                <h4 
                  @click="openProductUrlPreviewModal(prod)"
                  class="font-black text-white text-xs sm:text-sm line-clamp-2 hover:text-brand-accent cursor-pointer transition-colors" 
                  :title="prod.name"
                >
                  {{ prod.name }}
                </h4>
                <div class="text-sm font-black text-amber-400 mt-1">${{ prod.price }}</div>
                <div class="text-[10px] text-slate-400 font-mono mt-0.5">ID: #{{ prod.id }}</div>
              </div>
            </div>

            <!-- Tabla de Comisiones para el Revendedor -->
            <div class="grid grid-cols-2 gap-2 text-center text-xs bg-slate-900/80 p-2.5 rounded-xl border border-slate-800">
              <div class="border-r border-slate-800 pr-1">
                <span class="text-[10px] uppercase font-bold text-slate-400 block">Tienda (1%)</span>
                <span class="text-xs font-black text-blue-300 font-mono">+${{ Math.round(prod.priceNumeric * 0.01).toLocaleString() }}</span>
              </div>
              <div class="pl-1">
                <span class="text-[10px] uppercase font-black text-emerald-400 block">Link Directo (3%)</span>
                <span class="text-xs font-black text-emerald-400 font-mono">+${{ Math.round(prod.priceNumeric * 0.03).toLocaleString() }}</span>
              </div>
            </div>

            <!-- Acciones de Difusión con 3% de Ganancia -->
            <div class="flex gap-1.5 pt-1">
              <button 
                @click="copyResellerProduct3PercentLink(prod)"
                class="flex-1 bg-emerald-500/15 hover:bg-emerald-500 text-emerald-300 hover:text-slate-950 text-[11px] font-black py-2 rounded-xl border border-emerald-500/30 transition-all flex items-center justify-center gap-1"
                title="Copiar enlace preferencial de 3% de comisión"
              >
                <span>🔗 Link 3%</span>
              </button>
              <button 
                @click="openProductUrlPreviewModal(prod)"
                class="bg-sky-500/20 hover:bg-sky-500 text-sky-300 hover:text-slate-950 text-[11px] font-bold px-2.5 py-2 rounded-xl border border-sky-500/30 transition-all flex items-center justify-center gap-1 shadow-sm"
                title="Previsualizar URL de este producto (Ver cómo lo ve el cliente)"
              >
                <span>👁️ Ver</span>
              </button>
              <button 
                @click="openFlyerForProduct(prod)"
                class="bg-amber-500/20 hover:bg-amber-500 text-amber-300 hover:text-slate-950 text-[11px] font-bold px-2.5 py-2 rounded-xl border border-amber-500/30 transition-all flex items-center justify-center gap-1 shadow"
                title="Crear Flyer publicitario para redes con streamepro.me"
              >
                <span>🎨 Flyer</span>
              </button>
              <button 
                @click="shareResellerProductWhatsApp(prod)"
                class="bg-emerald-600 hover:bg-emerald-500 text-white text-[11px] font-bold px-2 py-2 rounded-xl transition-all shadow flex items-center justify-center"
                title="Compartir directo a WhatsApp"
              >
                <span>📲</span>
              </button>
            </div>

            <!-- Barra Rápida de Previsualización de URL -->
            <div class="bg-slate-950/80 px-2 py-1 rounded-xl border border-slate-800/80 flex items-center justify-between gap-1 text-[10px] mt-1">
              <span class="text-slate-400 font-mono truncate" :title="getProductReferralUrl(prod)">
                streamepro.me/?ref={{ currentResellerId }}&prod={{ prod.id }}
              </span>
              <button 
                @click="openProductInNewTab(prod)" 
                class="text-sky-400 hover:text-sky-300 shrink-0 font-bold px-1.5 py-0.5 rounded bg-slate-900 border border-slate-800 hover:border-sky-500/30 transition text-[9px]"
                title="Abrir tienda en vivo con este producto"
              >
                Abrir ↗
              </button>
            </div>
          </div>
        </div>
        <p class="text-[11px] text-slate-500 text-center italic">Mostrando hasta 48 productos coincidentes. Usa el buscador o filtro de categorías para encontrar cualquier artículo del catálogo de 364 productos.</p>
      </div>
    </div>

    <!-- Pestaña Marketing & Generador de Copy Pro -->
    <div v-else class="space-y-8 animate-in fade-in duration-300">
      
      <!-- HERO MARKETING Y ENLACE DE TIENDA -->
      <div class="bg-gradient-to-r from-indigo-950/60 via-purple-950/40 to-slate-900 p-6 rounded-3xl border border-indigo-500/30 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
          <div class="flex items-center gap-2">
            <span class="text-xs font-bold uppercase tracking-wider bg-brand-accent/20 text-brand-accent px-2.5 py-0.5 rounded-full">
              Herramientas de Venta Pro
            </span>
            <span class="text-xs font-mono text-slate-400">Distribuidor: {{ currentResellerId }}</span>
          </div>
          <h3 class="text-2xl font-black text-white mt-1">Generador de Ofertas & Copys para Redes Sociales</h3>
          <p class="text-xs text-slate-300 mt-1">Comparte textos persuasivos y flyers listos para captar clientes en Instagram, TikTok, WhatsApp y Facebook.</p>
        </div>
        
        <div class="flex gap-2">
          <button 
            @click="copyAffiliateLink" 
            class="bg-brand-accent text-slate-950 font-black text-xs px-4 py-2.5 rounded-xl hover:bg-sky-400 transition-all shadow flex items-center gap-1.5"
          >
            <span>📋 Copiar Mi Link de Tienda</span>
          </button>
        </div>
      </div>

      <!-- SELECTOR DE MODO: COMBO / PAQUETE PROMOCIONAL VS SERVICIO INDIVIDUAL -->
      <div class="flex flex-wrap items-center justify-between gap-4 bg-slate-900/60 p-3 rounded-2xl border border-slate-800">
        <div class="flex flex-wrap gap-2">
          <button 
            type="button"
            @click="marketingMode = 'package'" 
            :class="['px-6 py-2.5 rounded-xl text-xs font-black transition-all flex items-center gap-2', marketingMode === 'package' ? 'bg-gradient-to-r from-purple-500 via-pink-500 to-rose-500 text-white shadow-lg shadow-pink-500/20' : 'text-slate-400 hover:text-white']"
          >
            <span>🎁</span>
            <span>Promoción de Combo / Paquete</span>
            <span class="bg-black/40 text-[10px] px-2 py-0.5 rounded-full font-mono text-pink-300">Recomendado</span>
          </button>

          <button 
            type="button"
            @click="marketingMode = 'single'" 
            :class="['px-6 py-2.5 rounded-xl text-xs font-black transition-all flex items-center gap-2', marketingMode === 'single' ? 'bg-brand-accent text-slate-950 shadow-lg shadow-sky-500/20' : 'text-slate-400 hover:text-white']"
          >
            <span>🎬</span>
            <span>Servicio Individual</span>
          </button>
        </div>

        <!-- Selector de Paquete cargado -->
        <div v-if="marketingMode === 'package'" class="flex items-center gap-2 pr-2">
          <span class="text-xs text-slate-400 font-bold">Cargar Combo Existente:</span>
          <select 
            v-model="selectedMarketingPackageId" 
            class="bg-slate-950 border border-slate-800 rounded-xl px-3 py-1.5 text-xs text-pink-300 font-bold outline-none"
          >
            <option v-for="p in resellerStore.myPackages" :key="p.id" :value="p.id">
              [{{ p.id }}] {{ p.title }} (${{ p.promoPrice }} ARS)
            </option>
          </select>
        </div>
      </div>

      <!-- SECCIÓN 1: GENERADOR DE TEXTOS ATRACTIVOS PARA REDES SOCIALES -->
      <div class="bg-slate-900/60 p-6 sm:p-8 rounded-3xl border border-slate-800 backdrop-blur-md space-y-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 border-b border-slate-800 pb-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-purple-500/15 border border-purple-500/30 text-purple-400 flex items-center justify-center text-xl">
              ✍️
            </div>
            <div>
              <h4 class="text-lg font-black text-white">Generador de Copy Persuasivo para Redes Sociales</h4>
              <p class="text-xs text-slate-400">
                {{ marketingMode === 'package' ? 'El texto se adapta automáticamente a todas las cuentas que agregues a la promoción.' : 'Selecciona el canal, producto y tono para generar un texto irresistible con tus enlaces y precios.' }}
              </p>
            </div>
          </div>
          
          <!-- Selector de Red Social -->
          <div class="flex flex-wrap gap-1 bg-slate-950 p-1.5 rounded-xl border border-slate-800">
            <button 
              @click="socialNetwork = 'whatsapp'" 
              :class="['px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5', socialNetwork === 'whatsapp' ? 'bg-emerald-600 text-white font-black shadow' : 'text-slate-400 hover:text-white']"
            >
              <span>🟢 WhatsApp</span>
            </button>
            <button 
              @click="socialNetwork = 'instagram'" 
              :class="['px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5', socialNetwork === 'instagram' ? 'bg-pink-600 text-white font-black shadow' : 'text-slate-400 hover:text-white']"
            >
              <span>📸 Instagram</span>
            </button>
            <button 
              @click="socialNetwork = 'tiktok'" 
              :class="['px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5', socialNetwork === 'tiktok' ? 'bg-cyan-600 text-white font-black shadow' : 'text-slate-400 hover:text-white']"
            >
              <span>🎵 TikTok</span>
            </button>
            <button 
              @click="socialNetwork = 'facebook'" 
              :class="['px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5', socialNetwork === 'facebook' ? 'bg-blue-600 text-white font-black shadow' : 'text-slate-400 hover:text-white']"
            >
              <span>🔵 Facebook</span>
            </button>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Controles de Configuración del Copy -->
          <div class="space-y-4 bg-slate-950/70 p-5 rounded-2xl border border-slate-800 text-xs">
            
            <!-- MODO PAQUETE / COMBO -->
            <div v-if="marketingMode === 'package'" class="space-y-3">
              <div>
                <label class="block font-bold text-slate-400 uppercase tracking-wider mb-1">Título del Combo</label>
                <input v-model="comboMarketingData.title" type="text" class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-white font-semibold outline-none focus:border-pink-500">
              </div>

              <div>
                <label class="block font-bold text-slate-400 uppercase tracking-wider mb-1.5">Cuentas Incluidas (Haz clic para sumar/quitar):</label>
                <div class="flex flex-wrap gap-1.5">
                  <button 
                    v-for="plat in ['Netflix', 'Max', 'Spotify', 'Disney+', 'IPTV']" 
                    :key="plat"
                    type="button"
                    @click="togglePlatformInComboMarketing(plat)"
                    :class="[
                      'px-2.5 py-1 rounded-lg font-bold text-[11px] border transition-all flex items-center gap-1',
                      comboMarketingData.services.some(s => s.platform === plat) 
                        ? 'bg-pink-500/20 text-pink-300 border-pink-500/40' 
                        : 'bg-slate-900 border-slate-800 text-slate-500 hover:text-slate-300'
                    ]"
                  >
                    <span>{{ comboMarketingData.services.some(s => s.platform === plat) ? '✓' : '+' }}</span>
                    <span>{{ plat }}</span>
                  </button>
                </div>
              </div>

              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block font-bold text-slate-400 uppercase tracking-wider mb-1">Precio Promo</label>
                  <input v-model="comboMarketingData.promoPrice" type="number" step="100" class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-pink-400 font-mono font-bold outline-none">
                </div>
                <div>
                  <label class="block font-bold text-slate-400 uppercase tracking-wider mb-1">Badge</label>
                  <input v-model="comboMarketingData.badge" type="text" class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-white text-xs outline-none">
                </div>
              </div>
            </div>

            <!-- MODO INDIVIDUAL -->
            <div v-else class="space-y-3">
              <div>
                <label class="block font-bold text-slate-400 uppercase tracking-wider mb-1.5">Servicio / Plataforma</label>
                <select v-model="socialPlatform" class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-white outline-none focus:border-brand-accent font-semibold">
                  <option value="Netflix">Netflix Premium 4K</option>
                  <option value="Max">Max Platino</option>
                  <option value="Spotify">Spotify Familiar</option>
                  <option value="Disney+">Disney+ Combo</option>
                  <option value="IPTV">IPTV MagisTV Pro</option>
                </select>
              </div>

              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block font-bold text-slate-400 uppercase tracking-wider mb-1.5">Pantallas</label>
                  <select v-model="promoScreens" class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-white outline-none font-semibold">
                    <option :value="1">1 Pantalla</option>
                    <option :value="2">2 Pantallas</option>
                    <option :value="4">4 Pantallas (Familiar)</option>
                  </select>
                </div>
                <div>
                  <label class="block font-bold text-slate-400 uppercase tracking-wider mb-1.5">Precio Promo</label>
                  <input v-model="promoPrice" type="number" step="100" class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-white font-mono font-bold outline-none">
                </div>
              </div>
            </div>

            <!-- Tono / Estilo del Copy -->
            <div>
              <label class="block font-bold text-slate-400 uppercase tracking-wider mb-1.5">Tono / Estilo del Copy</label>
              <div class="grid grid-cols-3 gap-1.5">
                <button 
                  type="button" 
                  @click="promoTone = 'urgente'" 
                  :class="['p-2 rounded-xl text-center border font-bold text-[11px] transition-all', promoTone === 'urgente' ? 'bg-amber-500/20 text-amber-300 border-amber-500/40' : 'bg-slate-900 border-slate-800 text-slate-400']"
                >
                  ⚡ Relámpago
                </button>
                <button 
                  type="button" 
                  @click="promoTone = 'vip'" 
                  :class="['p-2 rounded-xl text-center border font-bold text-[11px] transition-all', promoTone === 'vip' ? 'bg-purple-500/20 text-purple-300 border-purple-500/40' : 'bg-slate-900 border-slate-800 text-slate-400']"
                >
                  👑 Calidad VIP
                </button>
                <button 
                  type="button" 
                  @click="promoTone = 'ahorro'" 
                  :class="['p-2 rounded-xl text-center border font-bold text-[11px] transition-all', promoTone === 'ahorro' ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40' : 'bg-slate-900 border-slate-800 text-slate-400']"
                >
                  💰 Super Ahorro
                </button>
              </div>
            </div>

            <div class="pt-2 text-[11px] text-slate-400 bg-slate-900/60 p-3 rounded-xl border border-slate-800/80">
              💡 Incluye automáticamente tu enlace oficial con código de afiliado para acreditarte las ventas.
            </div>
          </div>

          <!-- Preview y Copiado del Copy Generado -->
          <div class="lg:col-span-2 flex flex-col justify-between bg-slate-950 p-5 rounded-2xl border border-slate-800 space-y-4">
            <div class="space-y-2">
              <div class="flex justify-between items-center border-b border-slate-800/80 pb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Texto Generado para Publicar:</span>
                <span class="text-[11px] text-brand-accent font-mono">Formato {{ socialNetwork.toUpperCase() }}</span>
              </div>
              <textarea 
                :value="generatedSocialCopy" 
                readonly 
                rows="10" 
                class="w-full bg-slate-900/70 border border-slate-800 rounded-xl p-3.5 text-xs text-white font-mono leading-relaxed outline-none resize-none selection:bg-brand-accent selection:text-slate-950"
              ></textarea>
            </div>

            <div class="flex flex-wrap gap-3 pt-2">
              <button 
                type="button"
                @click="copyGeneratedSocialCopy" 
                class="bg-brand-accent hover:bg-sky-400 text-slate-950 text-xs font-black px-5 py-3 rounded-xl transition-all shadow flex items-center justify-center gap-2 flex-1"
              >
                <span>📋 Copiar Texto</span>
              </button>
              
              <button 
                type="button"
                @click="shareSocialPromoToWhatsApp" 
                class="bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black px-5 py-3 rounded-xl transition-all shadow flex items-center justify-center gap-2 flex-1"
              >
                <span>📲 Publicar en WhatsApp</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- SECCIÓN 2: FLYERS Y PLANTILLAS DE CANVA (ADAPTATIVO SINGLE & COMBO) -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <div class="bg-slate-900/60 p-8 rounded-3xl border border-slate-800 backdrop-blur-sm space-y-6">
          <div class="flex items-center justify-between">
            <h3 class="text-2xl font-black text-white">Laboratorio de Flyers Publicitarios</h3>
            <span class="text-[10px] font-bold uppercase tracking-wider bg-purple-500/20 text-purple-300 border border-purple-500/30 px-2 py-0.5 rounded">
              Estilo Canva HD
            </span>
          </div>
          
          <!-- CONTROLES FLYER COMBO -->
          <div v-if="marketingMode === 'package'" class="space-y-4">
            <div>
              <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Título en Flyer</label>
              <input v-model="comboMarketingData.title" type="text" class="w-full bg-[#020617] border border-slate-800 rounded-xl p-3 text-white focus:border-pink-500 outline-none font-semibold">
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Plataformas en Flyer (Toca para activar/desactivar)</label>
              <div class="flex flex-wrap gap-2">
                <button 
                  v-for="plat in ['Netflix', 'Max', 'Spotify', 'Disney+', 'IPTV']" 
                  :key="plat"
                  type="button"
                  @click="togglePlatformInComboMarketing(plat)"
                  :class="[
                    'px-3 py-1.5 rounded-xl font-bold text-xs border transition-all flex items-center gap-1.5',
                    comboMarketingData.services.some(s => s.platform === plat)
                      ? 'bg-pink-500/20 text-pink-300 border-pink-500/50 shadow-sm'
                      : 'bg-[#020617] border-slate-800 text-slate-500 hover:text-slate-300'
                  ]"
                >
                  <span>{{ comboMarketingData.services.some(s => s.platform === plat) ? '✓' : '+' }}</span>
                  <span>{{ plat }}</span>
                </button>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Badge Promocional</label>
                <input v-model="comboMarketingData.badge" type="text" class="w-full bg-[#020617] border border-slate-800 rounded-xl p-3 text-white focus:border-pink-500 outline-none font-semibold text-xs">
              </div>
              <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Precio Flyer Promo (ARS)</label>
                <input v-model="comboMarketingData.promoPrice" type="number" step="100" class="w-full bg-[#020617] border border-slate-800 rounded-xl p-3 text-pink-400 focus:border-pink-500 outline-none font-semibold text-lg font-mono">
              </div>
            </div>

            <!-- Acciones de Flyer -->
            <div class="space-y-2 pt-2">
              <button type="button" @click="downloadFlyer" class="w-full relative group overflow-hidden rounded-xl shadow-lg">
                <div class="absolute inset-0 bg-gradient-to-r from-pink-500 via-rose-500 to-purple-600 transition-all duration-300 group-hover:scale-105"></div>
                <div class="relative px-6 py-3.5 flex items-center justify-center gap-2 text-white font-black text-sm tracking-wide">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                  Descargar Flyer Combo PNG (HD)
                </div>
              </button>

              <div class="grid grid-cols-2 gap-2">
                <button 
                  type="button"
                  @click="shareFlyerOfferToWhatsApp" 
                  class="bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black py-3 rounded-xl transition-all shadow flex items-center justify-center gap-1.5"
                >
                  <span>📲 Compartir a WhatsApp</span>
                </button>

                <a 
                  href="https://www.canva.com/templates/?query=streaming+bundle+promo" 
                  target="_blank" 
                  class="bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white text-xs font-black py-3 rounded-xl transition-all shadow flex items-center justify-center gap-1.5"
                >
                  <span>🎨 Abrir en Canva</span>
                </a>
              </div>
            </div>
          </div>

          <!-- CONTROLES FLYER INDIVIDUAL -->
          <div v-else class="space-y-4">
            <div>
              <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Plataforma</label>
              <select v-model="selectedPlatform" class="w-full bg-[#020617] border border-slate-800 rounded-xl p-3 text-white focus:border-brand-accent outline-none font-semibold">
                <option value="Netflix">Netflix (Tema Rojo)</option>
                <option value="Max">Max (Tema Azul)</option>
                <option value="Spotify">Spotify (Tema Verde)</option>
                <option value="Disney+">Disney+ (Tema Celeste)</option>
                <option value="IPTV">IPTV (Tema Morado)</option>
              </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Modalidad</label>
                <select v-model="devicesToPromo" class="w-full bg-[#020617] border border-slate-800 rounded-xl p-3 text-white focus:border-brand-accent outline-none font-semibold">
                  <option :value="1">1 Perfil</option>
                  <option :value="2">2 Dispositivos</option>
                  <option :value="4">4 Dispositivos (Familiar)</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Precio Flyer (ARS)</label>
                <input v-model="priceToShow" type="number" class="w-full bg-[#020617] border border-slate-800 rounded-xl p-3 text-white focus:border-brand-accent outline-none font-semibold text-lg">
              </div>
            </div>

            <!-- Acciones de Flyer -->
            <div class="space-y-2 pt-2">
              <button type="button" @click="downloadFlyer" class="w-full relative group overflow-hidden rounded-xl shadow-lg">
                <div class="absolute inset-0 bg-gradient-to-r from-brand-accent to-blue-600 transition-all duration-300 group-hover:scale-105"></div>
                <div class="relative px-6 py-3.5 flex items-center justify-center gap-2 text-white font-black text-sm tracking-wide">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                  Descargar Flyer PNG (HD)
                </div>
              </button>

              <div class="grid grid-cols-2 gap-2">
                <button 
                  type="button"
                  @click="shareFlyerOfferToWhatsApp" 
                  class="bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black py-3 rounded-xl transition-all shadow flex items-center justify-center gap-1.5"
                >
                  <span>📲 Compartir a WhatsApp</span>
                </button>

                <a 
                  href="https://www.canva.com/templates/?query=streaming+netflix+promo" 
                  target="_blank" 
                  class="bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white text-xs font-black py-3 rounded-xl transition-all shadow flex items-center justify-center gap-1.5"
                >
                  <span>🎨 Editar en Canva</span>
                </a>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Preview del Flyer Renderizado (HD) -->
        <div class="bg-slate-900/30 p-6 sm:p-8 rounded-3xl border border-slate-800/50 flex flex-col items-center justify-center min-h-[440px]">
          <p class="text-slate-500 mb-4 text-xs font-bold tracking-widest uppercase">
            {{ marketingMode === 'package' ? 'Vista Previa Flyer Combo (Canva HD)' : 'Vista Previa Flyer Individual (Canva HD)' }}
          </p>
          
          <!-- FLYER COMBO RENDERIZADO -->
          <div 
            v-if="marketingMode === 'package'"
            ref="comboFlyerRef" 
            class="w-[330px] min-h-[430px] rounded-3xl flex flex-col justify-between p-6 shadow-2xl relative overflow-hidden transition-all duration-500 bg-gradient-to-b from-[#0b0f19] via-[#1e1b4b] to-[#020617] border border-pink-500/40 text-white"
          >
            <!-- Efectos de fondo Canvas -->
            <div class="absolute -top-12 -right-12 w-36 h-36 bg-pink-500/25 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-12 -left-12 w-36 h-36 bg-purple-500/25 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute inset-0 opacity-15 bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI4IiBoZWlnaHQ9IjgiPgo8cmVjdCB3aWR0aD0iOCIgaGVpZ2h0PSI4IiBmaWxsPSIjZmZmIj48L3JlY3Q+CjxwYXRoIGQ9Ik0wIDBMOCA4Wk04IDBMMCA4WiIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utd2lkdGg9IjEiPjwvcGF0aD4KPC9zdmc+')] mix-blend-overlay pointer-events-none"></div>

            <!-- Cabecera y Badge -->
            <div class="relative z-10 text-center w-full space-y-1.5">
              <span class="inline-block text-[10px] font-black uppercase tracking-widest bg-gradient-to-r from-pink-500 via-rose-500 to-purple-600 px-3.5 py-1 rounded-full shadow-lg border border-white/20">
                {{ comboMarketingData.badge || '🔥 OFERTA EXCLUSIVA' }}
              </span>
              <h2 class="font-black text-2xl tracking-tight uppercase drop-shadow-xl text-white leading-tight">
                {{ comboMarketingData.title }}
              </h2>
              <p class="text-[10px] font-bold tracking-[0.2em] text-pink-300 uppercase">SUPER PACK STREAMING VIP</p>
            </div>

            <!-- Plataformas agregadas dinámicamente -->
            <div class="relative z-10 py-3 space-y-2 w-full">
              <div class="flex flex-wrap justify-center gap-1.5">
                <div 
                  v-for="(s, idx) in comboMarketingData.services" 
                  :key="idx"
                  class="flex items-center gap-1.5 bg-black/60 backdrop-blur-md px-2.5 py-1.5 rounded-xl border border-white/15 text-xs font-black shadow"
                >
                  <span>{{ s.platform === 'Netflix' ? '🍿' : s.platform === 'Spotify' ? '🎧' : s.platform === 'Disney+' ? '✨' : s.platform === 'IPTV' ? '📺' : '🎬' }}</span>
                  <span :class="[
                    s.platform === 'Netflix' ? 'text-red-400' :
                    s.platform === 'Spotify' ? 'text-emerald-400' :
                    s.platform === 'Disney+' ? 'text-sky-300' :
                    s.platform === 'Max' ? 'text-blue-400' : 'text-purple-400'
                  ]">{{ s.platform }}</span>
                </div>
              </div>
            </div>

            <!-- Precio -->
            <div class="relative z-10 text-center">
              <div class="bg-black/60 px-6 py-3 rounded-2xl backdrop-blur-md border border-pink-500/30 shadow-2xl">
                <span class="text-[11px] uppercase font-bold text-slate-400 line-through block font-mono">Antes: ${{ comboMarketingData.originalPrice.toLocaleString() }} ARS</span>
                <p class="text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-pink-400 via-rose-300 to-amber-300 drop-shadow-md font-mono">
                  ${{ comboMarketingData.promoPrice.toLocaleString() }}
                </p>
                <span class="text-[10px] uppercase font-mono tracking-wider text-pink-300">Precio Final / Mes</span>
              </div>
            </div>
            
            <!-- Garantías al pie -->
            <div class="relative z-10 flex items-center justify-between w-full text-[9px] font-black uppercase tracking-wider opacity-90 border-t border-white/20 pt-2 text-slate-300">
              <span>🔒 PIN Provisto</span>
              <span>🛡️ Cero Caídas</span>
              <span>⏱️ 10 min a 1 h</span>
            </div>
          </div>

          <!-- FLYER INDIVIDUAL RENDERIZADO -->
          <div 
            v-else
            ref="flyerRef" 
            :class="['w-[320px] h-[340px] rounded-3xl flex flex-col items-center justify-between p-6 shadow-2xl relative overflow-hidden transition-all duration-500', platformStyles[selectedPlatform].bg, platformStyles[selectedPlatform].text]"
          >
            <div class="absolute inset-0 opacity-15 bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI4IiBoZWlnaHQ9IjgiPgo8cmVjdCB3aWR0aD0iOCIgaGVpZ2h0PSI4IiBmaWxsPSIjZmZmIj48L3JlY3Q+CjxwYXRoIGQ9Ik0wIDBMOCA4Wk04IDBMMCA4WiIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utd2lkdGg9IjEiPjwvcGF0aD4KPC9zdmc+')] mix-blend-overlay"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-black/20"></div>
            
            <div class="relative z-10 text-center w-full">
              <span class="text-[11px] font-black uppercase tracking-widest bg-black/40 px-3 py-1 rounded-full backdrop-blur-sm border border-white/20">
                {{ devicesToPromo === 1 ? '1 PERFIL INDIVIDUAL' : devicesToPromo + ' DISPOSITIVOS SIMULTÁNEOS' }}
              </span>
              <h2 class="font-black text-4xl tracking-tighter uppercase drop-shadow-xl mt-3">{{ selectedPlatform }}</h2>
              <p class="text-sm font-black tracking-[0.3em] opacity-90">CUENTA OFICIAL</p>
            </div>

            <div class="relative z-10 text-center">
              <div class="bg-black/50 px-8 py-3 rounded-2xl backdrop-blur-md border border-white/15 shadow-xl">
                <span class="text-xs uppercase font-bold text-slate-300">Precio Final</span>
                <p :class="['text-5xl font-black drop-shadow-md', platformStyles[selectedPlatform].price]">${{ priceToShow }}</p>
              </div>
            </div>
            
            <div class="relative z-10 flex items-center justify-between w-full text-[10px] font-bold uppercase tracking-widest opacity-80 border-t border-white/20 pt-2">
              <span>PIN Provisto</span>
              <span>Garantía Oficial</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL GESTIÓN Y AUDITORÍA DE BILLETERA (Historial de Movimientos y Cargas) -->
    <div v-if="showWalletModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-sm animate-in fade-in duration-200">
      <div class="bg-slate-900 border border-slate-700 w-full max-w-xl rounded-2xl p-6 shadow-2xl relative space-y-4">
        <div class="flex justify-between items-start border-b border-slate-800 pb-3">
          <div>
            <div class="flex items-center gap-2">
              <span class="text-base">💳</span>
              <h3 class="text-xl font-black text-white">Historial y Auditoría de Billetera</h3>
            </div>
            <p class="text-xs text-slate-400 mt-0.5">Consulta las recargas del Administrador y todos los movimientos contables.</p>
          </div>
          <button @click="showWalletModal = false" class="text-slate-400 hover:text-white p-1">✕</button>
        </div>

        <div class="bg-gradient-to-r from-slate-950 via-indigo-950/40 to-slate-950 p-4 rounded-xl border border-indigo-500/30 flex justify-between items-center">
          <div>
            <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Saldo Disponible para Compras Mayoristas</span>
            <span class="text-2xl font-black text-emerald-400 font-mono">${{ resellerStore.wallet }} ARS</span>
          </div>
          <button @click="showProviderBankModal = true" class="bg-slate-900 hover:bg-slate-800 text-xs font-bold text-amber-300 border border-slate-700 px-3 py-2 rounded-xl transition-all">
            🏦 Transferir a Proveedor
          </button>
        </div>

        <!-- Tabla / Lista de Movimientos Contables -->
        <div class="space-y-2">
          <div class="flex justify-between items-center">
            <span class="text-xs font-bold uppercase text-slate-300 tracking-wider">Últimos Movimientos Registrados</span>
            <span class="text-[10px] text-slate-500 font-mono">Total registros: {{ adminStore.walletTransactions.filter(t => t.resellerId === currentResellerId).length }}</span>
          </div>

          <div class="max-h-[220px] overflow-y-auto space-y-2 pr-1 custom-scrollbar">
            <div v-if="adminStore.walletTransactions.filter(t => t.resellerId === currentResellerId).length === 0" class="text-center py-8 text-slate-500 text-xs italic bg-slate-950 rounded-xl border border-slate-800">
              No hay movimientos de recargas registrados aún.
            </div>
            <div 
              v-for="tx in adminStore.walletTransactions.filter(t => t.resellerId === currentResellerId)" 
              :key="tx.id"
              class="p-3 bg-slate-950 rounded-xl border border-slate-800 flex justify-between items-center text-xs"
            >
              <div class="space-y-0.5">
                <div class="flex items-center gap-2">
                  <span class="font-bold text-white">{{ tx.reason }}</span>
                  <span class="text-[10px] font-mono bg-indigo-500/10 text-indigo-300 border border-indigo-500/20 px-1.5 py-0.2 rounded">{{ tx.receipt }}</span>
                </div>
                <div class="text-[11px] text-slate-400 font-mono">{{ tx.date }}</div>
              </div>
              <div class="text-right">
                <span class="font-black text-sm text-emerald-400 font-mono">+${{ tx.amount }} ARS</span>
                <span class="text-[10px] text-slate-500 block">Acreditado</span>
              </div>
            </div>
          </div>
        </div>

        <div class="pt-2 border-t border-slate-800 flex justify-end">
          <button type="button" @click="showWalletModal = false" class="bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold px-6 py-2.5 rounded-xl text-xs transition-all">
            Cerrar Historial
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL DETALLE Y EDICIÓN DE CLIENTE -->
    <div v-if="showDetailModal && selectedClient" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm animate-in fade-in duration-200">
      <div class="bg-slate-900 border border-slate-700 w-full max-w-lg rounded-2xl p-6 shadow-2xl relative space-y-5">
        <div class="flex justify-between items-start border-b border-slate-800 pb-3">
          <div>
            <h3 class="text-xl font-black text-white">Ficha: {{ selectedClient.name }}</h3>
            <p class="text-xs text-slate-400">N° Cliente: <strong class="text-brand-accent">{{ selectedClient.clientNumber }}</strong> • {{ selectedClient.platform }}</p>
          </div>
          <button @click="showDetailModal = false" class="text-slate-400 hover:text-white p-1">✕</button>
        </div>

        <div class="space-y-4">
          <div class="bg-slate-950 p-3 rounded-xl border border-slate-800 text-xs space-y-1">
            <p class="text-slate-400">Revendedor Asignado: <strong class="text-white">{{ selectedClient.resellerId }}</strong></p>
            <p class="text-slate-400">Correo oficial: <strong class="text-white">{{ selectedClient.account }}</strong></p>
            <p class="text-slate-400">Contraseña: <strong class="text-white font-mono">{{ selectedClient.pass }}</strong></p>
            <p class="text-slate-400">Vencimiento: <strong class="text-amber-300">{{ selectedClient.expiry }}</strong></p>
          </div>

          <!-- Perfiles -->
          <div>
            <label class="block text-xs font-bold uppercase text-slate-400 tracking-wider mb-2">Editar Perfiles y PINs</label>
            <div class="space-y-2 max-h-[180px] overflow-y-auto pr-1">
              <div v-for="(prof, idx) in selectedClient.profiles" :key="idx" class="flex gap-2 items-center bg-slate-950 p-2.5 rounded-xl border border-slate-800">
                <input 
                  v-model="prof.name" 
                  type="text" 
                  class="bg-slate-900 border border-slate-700 rounded-lg px-3 py-1.5 text-xs text-white flex-1 font-mono outline-none focus:border-brand-accent"
                >
                <div class="flex items-center gap-1 w-28 bg-slate-900 border border-slate-700 rounded-lg px-2 py-1">
                  <span class="text-[10px] text-slate-500 font-bold">PIN:</span>
                  <input 
                    v-model="prof.pin" 
                    type="text" 
                    maxlength="4" 
                    class="bg-transparent text-amber-300 font-mono font-bold text-xs w-full text-center outline-none"
                  >
                </div>
              </div>
            </div>
          </div>

          <div class="flex gap-3 pt-2">
            <button @click="handleRenew(selectedClient)" class="flex-1 bg-blue-600/20 hover:bg-blue-600 text-blue-300 hover:text-white border border-blue-500/30 py-2.5 rounded-xl text-xs font-bold transition-all">
              +30 Días de Garantía
            </button>
            <button @click="showDetailModal = false; toast.add('Cambios guardados con éxito', 'success')" class="flex-1 bg-brand-accent text-slate-950 py-2.5 rounded-xl text-xs font-black hover:bg-sky-400 transition-all">
              Guardar Cambios
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL: SOLICITAR COMPRA MANUAL DESDE EL PANEL REVENDEDOR -->
    <div v-if="showManualPurchaseModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-sm animate-in fade-in duration-200">
      <div class="bg-slate-900 border border-slate-700 w-full max-w-lg rounded-2xl p-6 shadow-2xl relative space-y-4 text-xs">
        <div class="flex justify-between items-start border-b border-slate-800 pb-3">
          <div>
            <h3 class="text-xl font-black text-white">Solicitud de Compra Manual</h3>
            <p class="text-slate-400">Emite una orden directa al Proveedor para despachar a tu cliente o pagar con tu Billetera.</p>
          </div>
          <button @click="showManualPurchaseModal = false" class="text-slate-400 hover:text-white p-1">✕</button>
        </div>

        <form @submit.prevent="submitManualPurchase" class="space-y-4">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold uppercase text-slate-400 mb-1">Plataforma</label>
              <select v-model="manualPurchaseForm.platform" @change="onManualPlatformChange" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white font-semibold outline-none">
                <option v-for="(cat, pKey) in adminStore.pricingCatalog" :key="pKey" :value="pKey">
                  {{ pKey }}
                </option>
              </select>
            </div>

            <div>
              <label class="block font-bold uppercase text-slate-400 mb-1">Plan / Capacidad</label>
              <select v-model="manualPurchaseForm.planKey" @change="onManualPlanChange" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white font-semibold outline-none">
                <option v-for="(pVal, pKey) in adminStore.pricingCatalog[manualPurchaseForm.platform]?.plans" :key="pKey" :value="pKey">
                  {{ pVal.label }}
                </option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold uppercase text-slate-400 mb-1">Nombre del Cliente</label>
              <input v-model="manualPurchaseForm.customerName" type="text" placeholder="Ej: Marcos Silva" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white outline-none" required>
            </div>
            <div>
              <label class="block font-bold uppercase text-slate-400 mb-1">WhatsApp del Cliente</label>
              <input v-model="manualPurchaseForm.customerPhone" type="tel" placeholder="+54 9 11 ..." class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white outline-none">
            </div>
          </div>

          <!-- Nombres de Perfiles Requeridos -->
          <div>
            <label class="block font-bold uppercase text-slate-400 mb-1.5">Nombres de Perfiles Solicitados</label>
            <div class="space-y-2 max-h-[130px] overflow-y-auto pr-1">
              <div v-for="(prof, idx) in manualPurchaseForm.profiles" :key="idx" class="flex gap-2 items-center bg-slate-950 p-2 rounded-lg border border-slate-800">
                <span class="text-slate-500 font-bold w-6 text-center">#{{ idx + 1 }}</span>
                <input v-model="prof.name" type="text" class="w-full bg-slate-900 border border-slate-700 rounded px-2.5 py-1 text-xs text-white outline-none">
              </div>
            </div>
          </div>

          <!-- Resumen de Costos y Ganancia -->
          <div v-if="selectedManualPlanDetails" class="bg-slate-950 p-3.5 rounded-xl border border-slate-800 grid grid-cols-3 gap-2 text-center">
            <div>
              <span class="text-[10px] uppercase font-bold text-slate-500 block">Costo Proveedor</span>
              <span class="text-sm font-black text-amber-400">${{ selectedManualPlanDetails.providerCost }} ARS</span>
            </div>
            <div>
              <span class="text-[10px] uppercase font-bold text-slate-500 block">Tu Ganancia</span>
              <span class="text-sm font-black text-emerald-400">+${{ selectedManualPlanDetails.resellerProfit }} ARS</span>
            </div>
            <div>
              <span class="text-[10px] uppercase font-bold text-slate-500 block">Cobro al Cliente</span>
              <span class="text-sm font-black text-brand-accent">${{ selectedManualPlanDetails.clientPrice }} ARS</span>
            </div>
          </div>

          <!-- Opción de Pago con Billetera -->
          <label class="flex items-center gap-2.5 bg-slate-950 p-3 rounded-xl border border-slate-800 cursor-pointer">
            <input v-model="manualPurchaseForm.payWithWallet" type="checkbox" class="rounded border-slate-700 text-brand-accent focus:ring-0">
            <div class="text-xs">
              <span class="font-bold text-white block">Debitar directamente de mi Billetera Revendedor</span>
              <span class="text-slate-400 text-[11px]">Saldo actual disponible: ${{ resellerStore.wallet }} ARS</span>
            </div>
          </label>

          <div class="flex gap-3 pt-2">
            <button type="button" @click="showManualPurchaseModal = false" class="w-1/3 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold py-2.5 rounded-xl">
              Cancelar
            </button>
            <button type="submit" class="w-2/3 bg-brand-accent text-slate-950 font-black py-2.5 rounded-xl hover:bg-sky-400 transition-all shadow">
              Enviar Solicitud al Proveedor
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL: DATOS DE TRANSFERENCIA BANCARIA DEL PROVEEDOR -->
    <div v-if="showProviderBankModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-sm animate-in fade-in duration-200">
      <div class="bg-slate-900 border border-slate-700 w-full max-w-md rounded-2xl p-6 shadow-2xl relative space-y-4 text-xs">
        <div class="flex justify-between items-start border-b border-slate-800 pb-3">
          <div>
            <h3 class="text-xl font-black text-white">Datos Bancarios del Proveedor</h3>
            <p class="text-slate-400">Transfiere a esta cuenta para compras mayoristas o facilítala a clientes directos.</p>
          </div>
          <button @click="showProviderBankModal = false" class="text-slate-400 hover:text-white p-1">✕</button>
        </div>

        <div class="bg-gradient-to-br from-slate-950 to-indigo-950/40 p-4 rounded-xl border border-indigo-500/30 space-y-2 font-mono text-xs">
          <p class="text-slate-400">Titular Mayorista: <strong class="text-white text-sm">{{ adminStore.providerBankInfo.holderName }}</strong></p>
          <p class="text-slate-400">Entidad: <span class="text-slate-200">{{ adminStore.providerBankInfo.bankName }}</span></p>
          <p class="text-slate-400">CUIT: <span class="text-slate-200">{{ adminStore.providerBankInfo.cuit }}</span></p>
          
          <div class="bg-slate-900 p-3 rounded-lg border border-slate-800 space-y-1">
            <p class="text-amber-400 font-bold text-sm">Alias CBU: {{ adminStore.providerBankInfo.cbuAlias }}</p>
            <p class="text-slate-400 text-[11px]">CBU N°: {{ adminStore.providerBankInfo.cbuNumber }}</p>
          </div>

          <p class="text-slate-400">WhatsApp Oficial: <span class="text-brand-accent">{{ adminStore.providerBankInfo.whatsapp }}</span></p>
          <p class="text-slate-400">Email: <span class="text-slate-300">{{ adminStore.providerBankInfo.email }}</span></p>
        </div>

        <div class="p-3 bg-amber-950/30 border border-amber-500/30 rounded-xl text-amber-300 text-[11px]">
          ℹ️ Una vez realizada la transferencia, puedes enviar tu comprobante directamente vía WhatsApp al número del proveedor indicado arriba.
        </div>

        <div class="pt-2">
          <button @click="showProviderBankModal = false" class="w-full bg-slate-800 hover:bg-slate-700 text-white font-bold py-2.5 rounded-xl">
            Cerrar
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL: CÓDIGO QR INTERACTIVO DEL LINK DEL REVENDEDOR -->
    <div v-if="showQrModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-sm animate-in fade-in duration-200">
      <div class="bg-slate-900 border border-slate-700 w-full max-w-sm rounded-3xl p-6 shadow-2xl relative text-center space-y-4">
        <div class="flex justify-between items-center border-b border-slate-800 pb-3">
          <div class="text-left">
            <h3 class="text-base font-black text-white">Código QR de tu Tienda</h3>
            <p class="text-[11px] text-slate-400">Escanea para ir directo a tu enlace de afiliado</p>
          </div>
          <button @click="showQrModal = false" class="text-slate-400 hover:text-white p-1 text-base">✕</button>
        </div>

        <div class="bg-white p-4 rounded-2xl mx-auto w-fit shadow-lg border-4 border-brand-accent/30 flex items-center justify-center">
          <img :src="qrCodeUrl" alt="Código QR Tienda Revendedor" class="w-48 h-48 block" />
        </div>

        <div class="space-y-1">
          <p class="text-xs font-mono text-slate-300 break-all px-2 py-1 bg-slate-950 rounded border border-slate-800">{{ uniqueAffiliateLink }}</p>
          <p class="text-[10px] text-slate-400">Ideal para imprimir folletos, stickers o mostrar desde tu celular.</p>
        </div>

        <div class="flex gap-2 pt-2">
          <button 
            @click="copyAffiliateLink" 
            class="flex-1 bg-slate-800 hover:bg-slate-700 text-white font-bold py-2.5 rounded-xl text-xs transition-all"
          >
            Copiar Enlace
          </button>
          <a 
            :href="qrCodeUrl" 
            download="streampro-qr.png" 
            target="_blank" 
            class="flex-1 bg-brand-accent hover:bg-sky-400 text-slate-950 font-black py-2.5 rounded-xl text-xs transition-all flex items-center justify-center gap-1"
          >
            <span>Descargar</span>
          </a>
        </div>
      </div>
    </div>

    <!-- MODAL 1: COMPROBANTE DIGITAL OFICIAL DE ENTREGA (IMAGEN / PDF) -->
    <div v-if="showVoucherModal && selectedVoucherClient" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-sm animate-in fade-in duration-200">
      <div class="bg-slate-900 border border-slate-700 w-full max-w-md rounded-3xl p-6 shadow-2xl relative space-y-4">
        <div class="flex justify-between items-center border-b border-slate-800 pb-3">
          <div>
            <h3 class="text-base font-black text-white">Comprobante Oficial de Entrega</h3>
            <p class="text-[11px] text-slate-400">Diseñado para enviar directo por WhatsApp o descargar en imagen</p>
          </div>
          <button @click="showVoucherModal = false" class="text-slate-400 hover:text-white p-1">✕</button>
        </div>

        <!-- Tarjeta Renderizable para Imagen -->
        <div 
          ref="voucherCardRef" 
          class="bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 p-6 rounded-2xl border-2 border-brand-accent/40 shadow-2xl space-y-4 relative overflow-hidden text-white"
        >
          <div class="flex justify-between items-start border-b border-white/10 pb-3">
            <div class="flex items-center gap-2">
              <div class="w-8 h-8 rounded-lg bg-brand-accent text-slate-950 flex items-center justify-center font-black text-xs">
                SP
              </div>
              <div>
                <h4 class="font-black text-sm tracking-tight">STREAMPRO DIGITAL</h4>
                <p class="text-[9px] text-slate-400 font-mono">CERTIFICADO DE SUSCRIPCIÓN</p>
              </div>
            </div>
            <span class="text-[10px] font-mono font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 px-2 py-0.5 rounded">
              OFICIAL & ACTIVA
            </span>
          </div>

          <div class="grid grid-cols-2 gap-3 text-xs bg-slate-950/70 p-3 rounded-xl border border-slate-800">
            <div>
              <span class="text-[10px] uppercase font-bold text-slate-500 block">Cliente</span>
              <strong class="text-white text-xs">{{ selectedVoucherClient.name }}</strong>
              <span class="text-[10px] text-brand-accent font-mono block">N° {{ selectedVoucherClient.clientNumber || selectedVoucherClient.id }}</span>
            </div>
            <div>
              <span class="text-[10px] uppercase font-bold text-slate-500 block">Plataforma & Plan</span>
              <strong class="text-white text-xs">{{ selectedVoucherClient.platform }}</strong>
              <span class="text-[10px] text-sky-400 font-mono block">{{ selectedVoucherClient.planName || selectedVoucherClient.devices + ' pantallas' }}</span>
            </div>
          </div>

          <div class="space-y-1.5 text-xs bg-[#020617] p-3 rounded-xl border border-slate-800/80 font-mono">
            <div class="flex justify-between">
              <span class="text-slate-400">Cuenta:</span>
              <strong class="text-slate-200">{{ selectedVoucherClient.account }}</strong>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-400">Contraseña:</span>
              <strong class="text-amber-300">{{ selectedVoucherClient.pass }}</strong>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-400">Vencimiento:</span>
              <strong class="text-emerald-400">{{ selectedVoucherClient.expiry }}</strong>
            </div>
          </div>

          <!-- Perfiles Asignados con PIN -->
          <div class="space-y-1.5">
            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider block">Perfiles Habilitados con PIN:</span>
            <div class="grid grid-cols-2 gap-1.5">
              <div 
                v-for="(p, pIdx) in selectedVoucherClient.profiles" 
                :key="pIdx"
                class="bg-slate-950/90 p-2 rounded-lg border border-slate-800 text-[11px] font-mono flex justify-between items-center"
              >
                <span class="text-slate-200 truncate">{{ p.name }}</span>
                <span class="text-amber-300 font-bold bg-amber-400/10 px-1 rounded text-[10px]">PIN: {{ p.pin }}</span>
              </div>
            </div>
          </div>

          <div class="pt-2 border-t border-white/10 flex justify-between items-center text-[10px] text-slate-400 font-mono">
            <span>🛡️ Garantía 30 Días</span>
            <span>Atendido por {{ resellerStore.sessionUser?.name || 'Distribuidor Oficial' }}</span>
          </div>
        </div>

        <!-- Acciones del Comprobante -->
        <div class="flex gap-2 pt-2">
          <button 
            @click="downloadVoucherImage" 
            class="flex-1 bg-slate-800 hover:bg-slate-700 text-white font-bold py-2.5 rounded-xl text-xs transition-all flex items-center justify-center gap-1.5"
          >
            <span>📥</span>
            <span>Descargar Imagen</span>
          </button>
          <button 
            @click="shareVoucherWhatsApp" 
            class="flex-1 bg-emerald-600 hover:bg-emerald-500 text-white font-black py-2.5 rounded-xl text-xs transition-all shadow flex items-center justify-center gap-1.5"
          >
            <span>💬</span>
            <span>Enviar por WhatsApp</span>
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL 2: SIMULADOR Y CALCULADORA DE GANANCIAS MAYORISTAS -->
    <div v-if="showCalcModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-sm animate-in fade-in duration-200">
      <div class="bg-slate-900 border border-slate-700 w-full max-w-lg rounded-3xl p-6 shadow-2xl relative space-y-5 text-xs">
        <div class="flex justify-between items-start border-b border-slate-800 pb-3">
          <div>
            <div class="flex items-center gap-2">
              <span class="text-lg">🧮</span>
              <h3 class="text-lg font-black text-white">Simulador de Ganancias Mayoristas</h3>
            </div>
            <p class="text-slate-400 mt-0.5">Calcula tus márgenes y ganancias netas proyectadas antes de ordenar lotes.</p>
          </div>
          <button @click="showCalcModal = false" class="text-slate-400 hover:text-white p-1">✕</button>
        </div>

        <div class="space-y-4">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold uppercase text-slate-400 mb-1">Plataforma</label>
              <select v-model="calcPlatform" @change="selectedCalcPlanKey = Object.keys(adminStore.pricingCatalog[calcPlatform].plans)[0]" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white font-bold outline-none">
                <option v-for="(cat, pKey) in adminStore.pricingCatalog" :key="pKey" :value="pKey">{{ pKey }}</option>
              </select>
            </div>
            <div>
              <label class="block font-bold uppercase text-slate-400 mb-1">Plan / Modalidad</label>
              <select v-model="selectedCalcPlanKey" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white font-bold outline-none">
                <option v-for="plan in availablePlansForCalc" :key="plan.key" :value="plan.key">
                  {{ plan.label }}
                </option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold uppercase text-slate-400 mb-1">Cantidad de Cuentas / Slots</label>
              <input v-model.number="calcUnits" type="number" min="1" max="500" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white font-mono font-bold outline-none">
            </div>
            <div>
              <label class="block font-bold uppercase text-slate-400 mb-1">Precio Cobrado al Cliente (ARS)</label>
              <input 
                :placeholder="`Sugerido: $${selectedCalcPlan?.clientPrice || 0}`" 
                v-model.number="calcCustomPrice" 
                type="number" 
                class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white font-mono outline-none"
              >
            </div>
          </div>

          <!-- Cuadro de Rentabilidad Proyectada -->
          <div class="bg-gradient-to-br from-slate-950 via-slate-900 to-emerald-950/40 p-4 rounded-2xl border border-emerald-500/30 space-y-3 font-mono">
            <div class="flex justify-between items-center text-xs">
              <span class="text-slate-400">Costo Mayorista Total ({{ calcUnits }} unid.):</span>
              <span class="text-slate-200 font-bold">${{ calcTotalCost.toLocaleString() }} ARS</span>
            </div>
            <div class="flex justify-between items-center text-xs">
              <span class="text-slate-400">Facturación Total Cobrada a Clientes:</span>
              <span class="text-sky-300 font-bold">${{ calcTotalClientIncome.toLocaleString() }} ARS</span>
            </div>
            <div class="border-t border-slate-800 pt-2 flex justify-between items-center">
              <div>
                <span class="text-[10px] uppercase font-bold text-emerald-400 block font-sans">Ganancia Neta Limpia</span>
                <span class="text-2xl font-black text-emerald-400">+${{ calcTotalProfit.toLocaleString() }} ARS</span>
              </div>
              <div class="text-right">
                <span class="text-[10px] uppercase font-bold text-slate-400 block font-sans">Margen de Rentabilidad</span>
                <span class="text-xl font-black text-brand-accent">{{ calcProfitMargin }}%</span>
              </div>
            </div>
          </div>

          <div class="p-3 bg-indigo-950/30 border border-indigo-500/20 rounded-xl text-indigo-300 text-[11px]">
            💡 Puedes pedir lotes directamente desde el botón <strong>"Solicitar Compra Manual"</strong> y abonar con tu Billetera Revendedor.
          </div>

          <div class="flex gap-2 pt-2">
            <button @click="showCalcModal = false" class="w-1/3 bg-slate-800 hover:bg-slate-700 text-white font-bold py-2.5 rounded-xl">
              Cerrar
            </button>
            <button @click="showCalcModal = false; showManualPurchaseModal = true; manualPurchaseForm.platform = calcPlatform; manualPurchaseForm.planKey = selectedCalcPlanKey" class="w-2/3 bg-brand-accent hover:bg-sky-400 text-slate-950 font-black py-2.5 rounded-xl transition-all shadow">
              Pedir este Lote al Proveedor →
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL: PREVISUALIZADOR DE URL DE PRODUCTO FÍSICO -->
    <div v-if="showPhysicalUrlModal && previewingPhysicalProduct" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-md animate-in fade-in duration-200">
      <div class="bg-slate-900 border border-sky-500/30 w-full max-w-xl rounded-3xl p-6 shadow-2xl relative space-y-5">
        <div class="flex justify-between items-start border-b border-slate-800 pb-3">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-sky-500/20 text-sky-400 border border-sky-500/30 flex items-center justify-center text-xl shadow">
              👁️
            </div>
            <div>
              <h3 class="text-lg font-black text-white">Previsualizador de URL y Enlace de Referencia</h3>
              <p class="text-xs text-slate-400">Verifica cómo se abrirá la tienda para tu cliente y prueba la redirección.</p>
            </div>
          </div>
          <button @click="showPhysicalUrlModal = false" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 transition">✕</button>
        </div>

        <!-- Ficha Resumen del Producto con Alta Visibilidad -->
        <div class="flex flex-col sm:flex-row items-center gap-4 bg-slate-950 p-4 rounded-2xl border border-slate-800">
          <div class="w-32 h-32 sm:w-40 sm:h-40 bg-white rounded-2xl p-2.5 flex items-center justify-center shrink-0 border-2 border-white/20 shadow-xl overflow-hidden">
            <img 
              :src="previewingPhysicalProduct.image" 
              :alt="previewingPhysicalProduct.name" 
              class="max-h-full max-w-full object-contain hover:scale-105 transition-transform duration-300"
            >
          </div>
          <div class="space-y-1.5 flex-1 min-w-0 text-center sm:text-left">
            <h4 class="font-black text-white text-base leading-snug">{{ previewingPhysicalProduct.name }}</h4>
            <div class="flex items-center justify-center sm:justify-start gap-2">
              <span class="text-amber-400 font-black text-xl">${{ previewingPhysicalProduct.price }}</span>
              <span class="text-[10px] font-mono text-slate-400 bg-slate-900 px-2 py-0.5 rounded border border-slate-800">ID: #{{ previewingPhysicalProduct.id }}</span>
            </div>
            <div class="flex items-center gap-2 text-[11px]">
              <span class="text-emerald-400 font-bold bg-emerald-500/10 px-2 py-0.5 rounded border border-emerald-500/20">
                Tu Comisión (3%): +${{ Math.round(previewingPhysicalProduct.priceNumeric * 0.03).toLocaleString() }} ARS
              </span>
            </div>
          </div>
        </div>

        <!-- Enlaces Listos para Compartir -->
        <div class="space-y-3">
          <div>
            <div class="flex justify-between items-center mb-1">
              <label class="text-xs font-bold text-sky-400 uppercase tracking-wider">URL Principal con Referido Activo:</label>
              <span class="text-[10px] font-mono text-slate-500">Formato Estándar</span>
            </div>
            <div class="flex items-center gap-2">
              <input 
                :value="getProductReferralUrl(previewingPhysicalProduct, false)" 
                readonly 
                class="flex-1 bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-sky-300 font-mono outline-none select-all"
              >
              <button 
                @click="copyResellerProduct3PercentLink(previewingPhysicalProduct)" 
                class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs px-3.5 py-2 rounded-xl transition flex items-center gap-1 shadow"
                title="Copiar URL"
              >
                <span>📋 Copiar</span>
              </button>
            </div>
          </div>

          <div>
            <div class="flex justify-between items-center mb-1">
              <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">URL Amigable (Redirección Automática):</label>
              <span class="text-[10px] font-mono text-slate-500">SEO Friendly</span>
            </div>
            <div class="flex items-center gap-2">
              <input 
                :value="getProductReferralUrl(previewingPhysicalProduct, true)" 
                readonly 
                class="flex-1 bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-300 font-mono outline-none select-all"
              >
              <button 
                @click="navigator.clipboard.writeText(getProductReferralUrl(previewingPhysicalProduct, true)); toast.add('¡URL amigable copiada!', 'success')" 
                class="bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs px-3.5 py-2 rounded-xl border border-slate-700 transition"
              >
                Copiar
              </button>
            </div>
          </div>
        </div>

        <div class="p-3 bg-sky-950/30 border border-sky-500/20 rounded-xl text-sky-300 text-[11px] leading-relaxed">
          ℹ️ Cuando un cliente abre este enlace, la tienda en <strong>streamepro.me</strong> abre automáticamente este producto en pantalla grande, asocia la venta a tu código <strong>{{ currentResellerId }}</strong> y si el cliente compra, ganas el <strong>3% de comisión</strong>.
        </div>

        <!-- Botones de Acción -->
        <div class="flex flex-wrap gap-2 pt-1">
          <button 
            @click="openProductInNewTab(previewingPhysicalProduct)" 
            class="flex-1 bg-brand-accent hover:bg-sky-400 text-slate-950 font-black py-2.5 px-4 rounded-xl transition shadow flex items-center justify-center gap-2 text-xs"
          >
            <span>🌐 Probar en Vivo (Abrir Tienda) ↗</span>
          </button>
          <button 
            @click="showPhysicalUrlModal = false; openFlyerForProduct(previewingPhysicalProduct)" 
            class="bg-amber-500/20 hover:bg-amber-500 text-amber-300 hover:text-slate-950 font-bold py-2.5 px-4 rounded-xl border border-amber-500/30 transition flex items-center justify-center gap-1.5 text-xs shadow"
          >
            <span>🎨 Crear Flyer</span>
          </button>
          <button 
            @click="shareResellerProductWhatsApp(previewingPhysicalProduct)" 
            class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-2.5 px-3 rounded-xl transition flex items-center justify-center gap-1 text-xs shadow"
          >
            <span>📲 Compartir</span>
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL: GENERADOR DE FLYERS PARA ESTADOS & STORIES -->
    <FlyerGeneratorModal 
      :show="showFlyerModal" 
      :initial-product="flyerInitialProduct"
      :reseller-ref="currentResellerId"
      @close="showFlyerModal = false; flyerInitialProduct = null" 
      :default-store-name="resellerStore.sessionUser?.storeName || resellerStore.sessionUser?.name || 'StreamPro Distribuidor'" 
      website-domain="streamepro.me"
    />
  </div>
</template>

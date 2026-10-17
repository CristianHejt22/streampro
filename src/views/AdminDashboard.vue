<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useAdminStore, useResellerStore } from '../store/admin'
import { useToastStore } from '../store/toast'
import { generateSecurePassword, exportDatabaseBackup, importDatabaseBackup } from '../services/db'
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

const adminStore = useAdminStore()
const resellerStore = useResellerStore()
const toast = useToastStore()

// Formulario de Carga de Lotes
const newStock = ref({ 
  platform: 'Netflix', 
  account: '', 
  pass: '', 
  cost: 5500,
  maxProfiles: 4,
  profiles: [
    { name: '', pin: '1122' },
    { name: '', pin: '4455' },
    { name: '', pin: '8899' },
    { name: '', pin: '0000' }
  ]
})

const updateMaxProfiles = (val) => {
  newStock.value.maxProfiles = Number(val)
  newStock.value.profiles = Array.from({ length: Number(val) }, () => ({
    name: '',
    pin: String(Math.floor(1000 + Math.random() * 9000))
  }))
}

const handleAddStock = () => {
  if (newStock.value.account && newStock.value.pass && newStock.value.cost > 0) {
    adminStore.addStock({ ...newStock.value })
    toast.add(`Lote mayorista cargado: ${newStock.value.platform} (${newStock.value.maxProfiles} perfiles libres con PINs)`, 'success')
    newStock.value = { 
      platform: 'Netflix', 
      account: '', 
      pass: '', 
      cost: 5500,
      maxProfiles: 4,
      profiles: []
    }
  } else {
    toast.add('Completa todos los campos obligatorios del inventario', 'error')
  }
}

// Pestañas del Panel de Proveedor
const adminTab = ref('inventory') // 'inventory' | 'orders' | 'renewals' | 'resellers' | 'bank'

// CAMBIO PRIVADO DE CONTRASEÑA EN PANEL PROVEEDOR
// Solo visto por el proveedor hasta una nueva compra o reemplazo,
// CON OPCIÓN de enviar actualización a clientes con suscripciones ACTIVAS
const showPasswordModal = ref(false)
const selectedInvItem = ref(null)
const updatedPassword = ref('')
const notifyActiveClients = ref(false)
const updateReason = ref('Mantenimiento preventivo de seguridad')

const openPasswordModal = (item) => {
  selectedInvItem.value = item
  updatedPassword.value = item.pass
  notifyActiveClients.value = false
  updateReason.value = 'Mantenimiento preventivo de seguridad'
  showPasswordModal.value = true
}

const confirmPasswordUpdate = () => {
  if (updatedPassword.value.trim() && selectedInvItem.value) {
    adminStore.updateMasterPassword(selectedInvItem.value.id, updatedPassword.value.trim())
    
    if (notifyActiveClients.value) {
      // Envía actualización solo a clientes con suscripción ACTIVA (no por vencer ni cerradas sin solicitud)
      const count = adminStore.broadcastPasswordUpdateToActiveClients(
        selectedInvItem.value.account, 
        updatedPassword.value.trim(), 
        updateReason.value
      )
      toast.add(`Contraseña actualizada y enviada a ${count} cliente(s) con suscripción ACTIVA. Revendedor alertado: [YA FUE AVISADO].`, 'success')
    } else {
      toast.add(`Contraseña de ${selectedInvItem.value.account} guardada privadamente (solo visible por Proveedor).`, 'success')
    }
    showPasswordModal.value = false
  }
}

// GESTIÓN DE EVENTUALIDADES CON CLIENTES: Alertar al revendedor con badge "YA FUE AVISADO"
const showIncidentModal = ref(false)
const incidentForm = ref({
  resellerId: 'ALL',
  clientNumber: '',
  clientName: '',
  title: 'Aviso Importante sobre Servicio',
  message: 'Hubo una eventualidad en la cuenta. Se ha gestionado y estabilizado.'
})

const openIncidentModal = (order = null) => {
  if (order) {
    incidentForm.value.resellerId = order.resellerId
    incidentForm.value.clientNumber = order.customerNumber
    incidentForm.value.clientName = order.customerName
  }
  showIncidentModal.value = true
}

const submitIncidentAlert = () => {
  if (!incidentForm.value.title || !incidentForm.value.message) {
    toast.add('Completa el título y detalle del mensaje', 'error')
    return
  }
  adminStore.sendAlertToReseller({
    resellerId: incidentForm.value.resellerId,
    clientNumber: incidentForm.value.clientNumber || 'S/N',
    clientName: incidentForm.value.clientName || 'Cliente Asignado',
    title: incidentForm.value.title,
    message: incidentForm.value.message,
    type: 'incident'
  })
  toast.add(`Alerta formal enviada al revendedor ${incidentForm.value.resellerId}. Estado: YA FUE AVISADO.`, 'success')
  showIncidentModal.value = false
}

// ADMINISTRACIÓN DE REVENDEDORES DESDE EL PANEL PROVEEDOR
const showAddResellerModal = ref(false)
const newResellerForm = ref({
  name: '',
  storeName: '',
  username: '',
  password: '',
  contactNumber: '',
  cbuAlias: '',
  bankName: '',
  holderName: '',
  cuit: '',
  balance: 0,
  isActive: true
})

const generateNewResellerPassword = () => {
  newResellerForm.value.password = generateSecurePassword(10)
  if (newResellerForm.value.name.trim() && !newResellerForm.value.username.trim()) {
    newResellerForm.value.username = newResellerForm.value.name.toLowerCase().replace(/[^a-z0-9]/g, '')
  }
}

const openAddResellerModal = () => {
  newResellerForm.value = {
    name: '',
    storeName: '',
    username: '',
    password: generateSecurePassword(10),
    contactNumber: '+54 9 11 ',
    cbuAlias: '',
    bankName: 'Mercado Pago / Banco',
    holderName: '',
    cuit: '',
    balance: 0,
    isActive: true
  }
  showAddResellerModal.value = true
}

const handleAddReseller = () => {
  if (newResellerForm.value.name.trim()) {
    if (!newResellerForm.value.username.trim()) {
      newResellerForm.value.username = newResellerForm.value.name.toLowerCase().replace(/[^a-z0-9]/g, '') || `distribuidor${adminStore.resellers.length + 1}`
    }
    if (!newResellerForm.value.password.trim()) {
      newResellerForm.value.password = generateSecurePassword(10)
    }

    const created = adminStore.addReseller({ ...newResellerForm.value })
    toast.add(`Distribuidor [${created.name}] registrado con credenciales verídicas`, 'success')
    showAddResellerModal.value = false
  } else {
    toast.add('El nombre o razón social del revendedor es obligatorio', 'error')
  }
}

const copyResellerCredentials = (reseller) => {
  const origin = typeof window !== 'undefined' && window.location?.origin ? window.location.origin : 'https://streamepro.me'
  const text = `👋 *STREAMPRO - CREDENCIALES OFICIALES DE DISTRIBUIDOR*\n\n` +
    `¡Hola ${reseller.name}!\nTu cuenta de revendedor mayorista ha sido activada en el sistema:\n\n` +
    `🏪 *Tienda:* ${reseller.storeName || reseller.name}\n` +
    `🔑 *Código:* ${reseller.id}\n` +
    `👤 *Usuario:* ${reseller.username}\n` +
    `🔒 *Contraseña:* ${reseller.password}\n` +
    `💼 *Billetera Inicial:* $${(reseller.balance || 0).toLocaleString()} ARS\n\n` +
    `🌐 *Acceso al Portal:* ${origin}/distribuidor\n\n` +
    `¡Bienvenido al equipo de distribución oficial!`

  navigator.clipboard.writeText(text).then(() => {
    toast.add(`Credenciales de ${reseller.name} copiadas para WhatsApp`, 'success')
  })
}

const handleDeleteReseller = (resellerId) => {
  if (confirm(`¿Estás seguro de que deseas eliminar este distribuidor (${resellerId}) de la base de datos?`)) {
    adminStore.deleteReseller(resellerId)
    toast.add('Distribuidor eliminado de la base de datos', 'info')
  }
}

const handleDeleteInventory = (invId) => {
  if (confirm(`¿Estás seguro de que deseas eliminar este lote del inventario (${invId})?`)) {
    adminStore.deleteInventoryItem(invId)
    toast.add('Lote eliminado del inventario', 'info')
  }
}

const handleDeletePlatform = (platKey) => {
  if (confirm(`¿Eliminar la plataforma ${platKey} del catálogo?`)) {
    adminStore.deletePlatform(platKey)
    toast.add('Plataforma eliminada del catálogo', 'info')
  }
}

// IMPORTACIÓN Y ACTUALIZACIÓN DEL CATÁLOGO FÍSICO DESDE JSON
const catalogFileInput = ref(null)

const triggerPhysicalCatalogUpload = () => {
  if (catalogFileInput.value) catalogFileInput.value.click()
}

const handleImportPhysicalCatalog = (event) => {
  const file = event.target.files?.[0]
  if (!file) return

  const reader = new FileReader()
  reader.onload = (e) => {
    try {
      const raw = String(e.target.result).replace(/^\uFEFF/, '')
      const json = JSON.parse(raw)
      const res = adminStore.updatePhysicalProductsFromJson(json)
      if (res.success) {
        toast.add(`¡Catálogo físico actualizado! ${res.count} productos sincronizados en la base de datos.`, 'success')
      } else {
        toast.add(res.error || 'Error al procesar el archivo JSON', 'error')
      }
    } catch (err) {
      toast.add('El archivo seleccionado no es un JSON válido', 'error')
    }
    if (event.target) event.target.value = ''
  }
  reader.readAsText(file, 'utf-8')
}

// GENERADOR DE FLYERS PARA REDES Y ESTADOS DE WHATSAPP
const showFlyerModal = ref(false)

// RECORDATORIOS PERSUASIVOS DE RENOVACIÓN POR WHATSAPP
const getClientExpiryDays = (expiryStr) => {
  if (!expiryStr) return 999
  const diffTime = new Date(expiryStr) - new Date()
  return Math.ceil(diffTime / (1000 * 60 * 60 * 24))
}

const sendWhatsAppRenewalReminder = (client) => {
  const phone = (client.phone || '').replace(/[^0-9]/g, '')
  const storeTitle = adminStore.providerBankInfo.holderName || 'StreamPro Mayorista'
  const daysRemaining = getClientExpiryDays(client.expiry)
  const timeText = daysRemaining <= 0 ? 'hoy' : daysRemaining === 1 ? 'mañana' : `en ${daysRemaining} días`

  const message = 
    `¡Hola ${client.name}! Te saludamos de ${storeTitle}. 👋\n\n` +
    `Te recordamos que tu suscripción de *${client.platform}* (${client.planName || 'Plan Oficial'}) vence *${timeText}* (Fecha: ${client.expiry}). 📅\n\n` +
    `Para conservar tus perfiles, favoritos y no quedarte sin servicio este fin de semana, confírmanos por aquí para renovar tu mes.\n\n` +
    `🏦 Alias: ${adminStore.providerBankInfo.cbuAlias || 'STREAMPRO.MAYORISTA'}\n` +
    `💳 Titular: ${adminStore.providerBankInfo.holderName || 'StreamPro'}\n\n` +
    `¡Respondé a este mensaje para renovar en 1 minuto! ✨`

  if (phone && phone.length >= 8) {
    window.open(`https://wa.me/${phone}?text=${encodeURIComponent(message)}`, '_blank')
    toast.add(`Abriendo WhatsApp con recordatorio para ${client.name}`, 'success')
  } else {
    navigator.clipboard.writeText(message).then(() => {
      toast.add(`Mensaje de recordatorio copiado para ${client.name}. Pégalo en WhatsApp.`, 'success')
    })
  }
}

// ACTIVACIÓN DE USUARIO, CONTRASEÑA Y ESTADO PARA REVENDEDORES
const showResellerAuthModal = ref(false)
const selectedResellerForAuth = ref(null)
const resellerAuthForm = ref({
  username: '',
  password: '',
  isActive: true
})

const openResellerAuthModal = (reseller) => {
  selectedResellerForAuth.value = reseller
  resellerAuthForm.value = {
    username: reseller.username || '',
    password: reseller.password || '',
    isActive: reseller.isActive !== false
  }
  showResellerAuthModal.value = true
}

const saveResellerAuth = () => {
  if (!resellerAuthForm.value.username.trim() || !resellerAuthForm.value.password.trim()) {
    toast.add('El usuario y la contraseña son obligatorios', 'error')
    return
  }
  adminStore.updateResellerAuth(selectedResellerForAuth.value.id, {
    username: resellerAuthForm.value.username.trim(),
    password: resellerAuthForm.value.password.trim(),
    isActive: resellerAuthForm.value.isActive
  })
  toast.add(`Credenciales y estado de ${selectedResellerForAuth.value.name} guardados con éxito`, 'success')
  showResellerAuthModal.value = false
}

const toggleResellerStatus = (resellerId) => {
  const res = adminStore.toggleResellerStatus(resellerId)
  if (res) {
    toast.add(`Estado de ${res.name}: ${res.isActive ? 'HABILITADO / ACTIVO' : 'DESACTIVADO / BLOQUEADO'}`, res.isActive ? 'success' : 'info')
  }
}

// FILTROS Y BÚSQUEDA DE CLIENTES POR REVENDEDOR
const selectedResellerForFilter = ref('All')
const clientSearchQuery = ref('')
const clientStatusFilter = ref('All')
const showPasswordMap = ref({})

const togglePasswordVisibility = (clientId) => {
  showPasswordMap.value[clientId] = !showPasswordMap.value[clientId]
}

const dismissAlertAdmin = (alertId) => {
  adminStore.dismissAlert(alertId)
  toast.add('Notificación eliminada de auditoría', 'success')
}

const filteredAlertsForAdmin = computed(() => {
  return adminStore.resellerAlerts.filter(a => 
    selectedResellerForFilter.value === 'All' || a.resellerId === selectedResellerForFilter.value
  )
})

const copyClientDeliveryFormat = (client) => {
  const profilesList = (client.profiles || []).map(p => `• ${p.name} (PIN: ${p.pin})`).join('\n')
  const text = `🎬 *STREAMPRO - CREDENCIALES OFICIALES*\nHola ${client.name}!\nN° Cliente: ${client.clientNumber || client.id}\nPlataforma: *${client.platform}*\nPlan: *${client.planName || client.devices + ' pantallas'}*\nCorreo: ${client.account}\nClave: ${client.pass}\nVence: ${client.expiry}\n\n*Perfiles asignados:*\n${profilesList}\n\n¡Garantía StreamPro activa!`
  navigator.clipboard.writeText(text).then(() => {
    toast.add(`Credenciales de ${client.name} copiadas para WhatsApp`, 'success')
  })
}

const filteredClientsForAdmin = computed(() => {
  return resellerStore.clients.filter(c => {
    const matchesReseller = selectedResellerForFilter.value === 'All' || c.resellerId === selectedResellerForFilter.value
    const matchesStatus = clientStatusFilter.value === 'All' || c.status === clientStatusFilter.value
    const q = clientSearchQuery.value.toLowerCase().trim()
    const matchesQuery = !q || 
      c.name.toLowerCase().includes(q) || 
      (c.clientNumber && c.clientNumber.toLowerCase().includes(q)) ||
      c.account.toLowerCase().includes(q) ||
      c.phone.toLowerCase().includes(q) ||
      c.platform.toLowerCase().includes(q)
    return matchesReseller && matchesStatus && matchesQuery
  })
})

const viewClientsOfReseller = (resellerId) => {
  selectedResellerForFilter.value = resellerId
  adminTab.value = 'clients'
}

// GESTIÓN DE PEDIDOS DE PRODUCTOS FÍSICOS DESDE ADMIN
const physicalOrderStatusFilter = ref('All')
const physicalOrderSearch = ref('')

const filteredPhysicalOrdersForAdmin = computed(() => {
  return adminStore.physicalOrders.filter(o => {
    const matchesStatus = physicalOrderStatusFilter.value === 'All' || o.status === physicalOrderStatusFilter.value
    const q = physicalOrderSearch.value.toLowerCase().trim()
    const matchesQuery = !q || 
      o.id.toLowerCase().includes(q) || 
      o.customerName.toLowerCase().includes(q) || 
      o.customerNumber.toLowerCase().includes(q) ||
      o.customerPhone.includes(q) ||
      (o.resellerName && o.resellerName.toLowerCase().includes(q))
    return matchesStatus && matchesQuery
  })
})

const handleMarkPhysicalOrderReceived = (order) => {
  const res = adminStore.markPhysicalOrderReceived(order.id)
  if (res.success) {
    toast.add(`Pedido ${order.id} marcado como RECIBIDO. Un vendedor oficial se contactará con ${order.customerName} para confirmar stock y coordinar el pago.`, 'info')
  } else {
    toast.add(res.error || 'Error al actualizar el estado', 'error')
  }
}

const handleConfirmPhysicalOrder = (order) => {
  const res = adminStore.confirmPhysicalOrder(order.id)
  if (res.success) {
    toast.add(`¡Compra ${order.id} CONFIRMADA! Comisión de $${order.commissionAmount.toLocaleString()} ARS acreditada en la billetera de ${order.resellerName}.`, 'success')
  } else {
    toast.add(res.error || 'Error al confirmar el pedido', 'error')
  }
}

const handleApprovePhysicalOrder = (order) => {
  return handleConfirmPhysicalOrder(order)
}

const handleCancelPhysicalOrder = (order) => {
  const reason = prompt('Indica el motivo de cancelación del pedido físico:', 'Falta de stock o solicitud del comprador')
  if (reason !== null) {
    const res = adminStore.cancelPhysicalOrder(order.id, reason)
    if (res.success) {
      toast.add(`Pedido ${order.id} marcado como CANCELADO.`, 'info')
    }
  }
}

// RECARGA DE BILLETERA DE REVENDEDOR DESDE EL PANEL DE ADMINISTRACIÓN
const showRechargeModal = ref(false)
const selectedResellerForRecharge = ref(null)
const rechargeConfirmStep = ref(false)
const quickRechargeAmounts = [5000, 10000, 20000, 50000, 100000]
const rechargeForm = ref({
  amount: 10000,
  reason: 'Acreditación de transferencia mayorista',
  receiptNumber: ''
})

const openRechargeModal = (reseller) => {
  selectedResellerForRecharge.value = reseller
  rechargeConfirmStep.value = false
  rechargeForm.value = {
    amount: 10000,
    reason: 'Acreditación de transferencia mayorista',
    receiptNumber: `TRF-${Math.floor(100000 + Math.random() * 900000)}`
  }
  showRechargeModal.value = true
}

const proceedToRechargeConfirm = () => {
  if (rechargeForm.value.amount > 0 && selectedResellerForRecharge.value) {
    rechargeConfirmStep.value = true
  } else {
    toast.add('Ingresa un monto válido para recargar', 'error')
  }
}

const confirmRechargeWallet = () => {
  if (rechargeForm.value.amount > 0 && selectedResellerForRecharge.value) {
    adminStore.rechargeResellerWallet(
      selectedResellerForRecharge.value.id, 
      rechargeForm.value.amount, 
      rechargeForm.value.reason,
      rechargeForm.value.receiptNumber
    )
    toast.add(`+$${rechargeForm.value.amount} ARS acreditados a la Billetera de ${selectedResellerForRecharge.value.name} (Ref: ${rechargeForm.value.receiptNumber}). Alerta emitida: [YA FUE AVISADO].`, 'success')
    showRechargeModal.value = false
    rechargeConfirmStep.value = false
  }
}

// GESTIÓN MULTI-CUENTA BANCARIA & VALIDADOR DE TRANSFERENCIAS (ESTILO WANDERLUST)
const bankSubTab = ref('accounts') // 'accounts' | 'validator' | 'webhook_guide'
const isEditingProviderBank = ref(false)
const showAddAccountModal = ref(false)
const editingAccountId = ref(null)

const newAccountForm = ref({
  name: '',
  holderName: '',
  bankName: 'Mercado Pago',
  cuit: '',
  cbuAlias: '',
  cbuNumber: '',
  whatsapp: '',
  email: '',
  notifyEmail: ''
})

const handleSetActiveAccount = (accId) => {
  const res = adminStore.setActiveBankAccount(accId)
  if (res.success) {
    toast.add(`¡Cuenta activa cambiada a "${res.activeAccount.name}"! Los clientes y revendedores ahora verán estos datos para transferir.`, 'success')
  }
}

const openAddAccountModal = (acc = null) => {
  if (acc) {
    editingAccountId.value = acc.id
    newAccountForm.value = { ...acc }
  } else {
    editingAccountId.value = null
    newAccountForm.value = {
      name: `Cuenta #${(adminStore.providerBankAccounts?.length || 0) + 1}`,
      holderName: adminStore.providerBankInfo.holderName || '',
      bankName: 'Mercado Pago',
      cuit: adminStore.providerBankInfo.cuit || '',
      cbuAlias: '',
      cbuNumber: '',
      whatsapp: adminStore.providerBankInfo.whatsapp || '',
      email: adminStore.providerBankInfo.email || '',
      notifyEmail: ''
    }
  }
  showAddAccountModal.value = true
}

const saveAccountModal = () => {
  if (!newAccountForm.value.holderName || !newAccountForm.value.cbuAlias) {
    toast.add('Por favor completa Titular y Alias CBU', 'error')
    return
  }
  if (editingAccountId.value) {
    adminStore.updateProviderBankAccount({ id: editingAccountId.value, ...newAccountForm.value })
    toast.add('Cuenta bancaria actualizada correctamente', 'success')
  } else {
    adminStore.addProviderBankAccount(newAccountForm.value)
    toast.add('Nueva cuenta bancaria registrada con éxito', 'success')
  }
  showAddAccountModal.value = false
}

const handleDeleteAccount = (accId) => {
  if (confirm('¿Estás seguro de eliminar esta cuenta bancaria?')) {
    const res = adminStore.deleteProviderBankAccount(accId)
    if (res.success) {
      toast.add('Cuenta eliminada', 'success')
    } else {
      toast.add(res.error, 'error')
    }
  }
}

const providerBankForm = ref({ ...adminStore.providerBankInfo })
const saveProviderBankInfo = () => {
  Object.assign(adminStore.providerBankInfo, providerBankForm.value)
  isEditingProviderBank.value = false
  toast.add('Datos de transferencia bancaria del Proveedor actualizados con éxito', 'success')
}

// VALIDADOR MANUAL DE CORREO / COMPROBANTE (ESTILO WANDERLUST)
const testEmailText = ref('')
const testEmailSubject = ref('')
const isScanningEmail = ref(false)

const loadSampleEmail = (type) => {
  const pending = adminStore.pendingOrders[0]
  const amountToUse = pending ? (pending.clientPrice || 7700) : 7700
  const nameToUse = pending ? pending.customerName : 'Juan Carlos Martinez'
  const prevNum = pending ? pending.customerNumber : 'CLI-5421'

  if (type === 'mp') {
    testEmailSubject.value = `Te transfirieron $ ${amountToUse.toLocaleString()} a tu cuenta`
    testEmailText.value = `¡Buenas noticias!\n\n${nameToUse} te transfirió $ ${amountToUse.toLocaleString()} a tu cuenta de Mercado Pago.\n\nDetalle de la transferencia:\nMotivo: Varios / ${prevNum}\nOperación: #8945621345\nFecha: ${new Date().toLocaleDateString()}\n\nEl dinero ya se encuentra disponible en tu cuenta.`
  } else if (type === 'galicia') {
    testEmailSubject.value = 'Aviso de Acreditación de Transferencia - Banco Galicia'
    testEmailText.value = `Estimado cliente:\n\nLe informamos que ha recibido una acreditación en su cuenta.\n\nImporte: $ ${amountToUse.toLocaleString()},00\nOrdenante: ${nameToUse}\nConcepto: Pago orden ${prevNum}\nN° de Comprobante: 78423910\nFecha de Operación: ${new Date().toLocaleString()}`
  } else if (type === 'brubank') {
    testEmailSubject.value = `Recibiste $ ${amountToUse.toLocaleString()} de ${nameToUse}`
    testEmailText.value = `¡Hola! Recibiste $ ${amountToUse.toLocaleString()} de ${nameToUse} en tu cuenta de Brubank.\nReferencia: ${prevNum}\nID de Operación: BRU-${Math.floor(100000 + Math.random() * 900000)}`
  }
}

const scanPastedEmail = () => {
  if (!testEmailText.value.trim()) {
    toast.add('Pega el texto del correo bancario o aviso de transferencia para analizar', 'error')
    return
  }

  isScanningEmail.value = true
  setTimeout(() => {
    const res = adminStore.processBankTransferNotification(testEmailText.value, testEmailSubject.value)
    isScanningEmail.value = false

    if (res.error) {
      toast.add(res.error, 'error')
    } else {
      if (res.bestMatch && (res.bestMatch.confidence === 'exact' || res.bestMatch.confidence === 'high')) {
        toast.add(`¡Transferencia de $${res.transfer.amount.toLocaleString()} reconocida y vinculada a la orden #${res.bestMatch.order.customerNumber} (${res.bestMatch.order.customerName})!`, 'success')
      } else {
        toast.add(`Transferencia de $${res.transfer.amount.toLocaleString()} (${res.transfer.bankName}) registrada en la bandeja. No se halló orden pendiente idéntica.`, 'info')
      }
      testEmailText.value = ''
      testEmailSubject.value = ''
    }
  }, 400)
}

const handleConfirmTransferWithOrder = (orderId, transferId) => {
  const res = adminStore.confirmOrderPaymentWithTransfer(orderId, transferId)
  if (res.success) {
    toast.add(`¡Pago confirmado para el pedido #${res.order.customerNumber}! Listo para despachar cuentas.`, 'success')
  }
}

const handleDeleteTransfer = (transferId) => {
  adminStore.deleteBankTransfer(transferId)
  toast.add('Transferencia eliminada del historial', 'success')
}

// GESTIÓN DE RENOVACIONES: Renovar misma cuenta o cambiar datos de cuenta
const showRenewalEditModal = ref(false)
const selectedRenewal = ref(null)
const renewalMode = ref('same') // 'same' | 'edit'
const renewalDaysOption = ref(30)
const renewalFormData = ref({
  account: '',
  pass: '',
  profiles: []
})

const openRenewalEditModal = (renewal, initialMode = 'same') => {
  selectedRenewal.value = renewal
  renewalMode.value = initialMode
  renewalDaysOption.value = 30
  renewalFormData.value = {
    account: renewal.newCredentials.account,
    pass: renewal.newCredentials.pass,
    profiles: JSON.parse(JSON.stringify(renewal.newCredentials.profiles || []))
  }
  showRenewalEditModal.value = true
}

// Renovar misma cuenta directamente con 1 click o desde el modal
const handleRenewSameAccount = (renewal, days = 30) => {
  const result = adminStore.renewSameAccount(renewal.id, days)
  if (result.success) {
    toast.add(`Misma cuenta renovada por +${days} días para ${renewal.clientName} (${renewal.clientNumber}). Revendedor notificado [YA FUE AVISADO].`, 'success')
    showRenewalEditModal.value = false
  } else {
    toast.add('Error al procesar la renovación', 'error')
  }
}

const confirmRenewalUpdateByProvider = () => {
  if (renewalMode.value === 'same') {
    handleRenewSameAccount(selectedRenewal.value, renewalDaysOption.value)
  } else {
    if (!renewalFormData.value.account || !renewalFormData.value.pass) {
      toast.add('Completa correo y contraseña válidos', 'error')
      return
    }
    adminStore.updateRenewalByProvider(selectedRenewal.value.id, renewalFormData.value)
    toast.add(`Nuevas credenciales asignadas a ${selectedRenewal.value.clientName}. Se alertó al revendedor [YA FUE AVISADO] para confirmación.`, 'success')
    showRenewalEditModal.value = false
  }
}

// MODAL DE DESPACHO INTERACTIVO
const showDispatchModal = ref(false)
const selectedOrder = ref(null)
const selectedInventoryId = ref('')
const deliveryMatches = ref([])

const availableAccountsForOrder = computed(() => {
  if (!selectedOrder.value) return []
  return adminStore.inventory.filter(i => 
    i.platform === selectedOrder.value.platform && 
    (i.status === 'Available' || i.profiles.some(p => !p.isAssigned))
  )
})

const openDispatchModal = (order) => {
  selectedOrder.value = order
  
  const compatible = adminStore.inventory.find(i => 
    i.platform === order.platform && 
    (i.status === 'Available' || i.profiles.some(p => !p.isAssigned))
  )

  if (compatible) {
    selectedInventoryId.value = compatible.id
    generateDeliveryMatches(compatible, order)
  } else {
    selectedInventoryId.value = ''
    deliveryMatches.value = []
  }

  showDispatchModal.value = true
}

const onInventorySelected = () => {
  const inv = adminStore.inventory.find(i => i.id === selectedInventoryId.value)
  if (inv && selectedOrder.value) {
    generateDeliveryMatches(inv, selectedOrder.value)
  }
}

const generateDeliveryMatches = (inventory, order) => {
  const freeSlots = inventory.profiles.filter(p => !p.isAssigned)
  const basePin = freeSlots[0]?.pin || '1234'

  deliveryMatches.value = order.profileSelections.map((req, idx) => {
    const invProf = freeSlots[idx] || {}
    const pinToUse = order.pinPreference === 'same' 
      ? basePin 
      : (invProf.pin || String(Math.floor(1000 + Math.random() * 9000)))

    return {
      name: req.name || `PERFIL ${idx + 1}`,
      pin: pinToUse
    }
  })
}

const confirmDispatch = () => {
  if (!selectedInventoryId.value) {
    toast.add('Debes seleccionar una cuenta disponible para la entrega', 'error')
    return
  }

  const result = adminStore.dispatchOrderWithInventory(
    selectedOrder.value.id,
    selectedInventoryId.value,
    deliveryMatches.value
  )

  if (result && !result.error) {
    toast.add(`¡Entrega confirmada! Cuenta y PINs despachados a ${selectedOrder.value.customerName} (${selectedOrder.value.customerNumber}).`, 'success')
    showDispatchModal.value = false
  } else {
    toast.add(result?.error || 'Error al realizar el despacho de la cuenta', 'error')
  }
}

// Contactar directamente al cliente de una venta oficial por WhatsApp
const contactDirectCustomerWhatsApp = (order) => {
  const cleanPhone = (order.customerPhone || '').replace(/[^0-9]/g, '')
  const msg = `¡Hola ${order.customerName}! Te contactamos desde StreamPro Oficial respecto a tu pedido de ${order.platform} (${order.customerNumber}). Estamos preparando tu activación.`
  window.open(`https://wa.me/${cleanPhone}?text=${encodeURIComponent(msg)}`, '_blank')
}

// GESTIÓN DE SOLICITUDES DE NUEVOS REVENDEDORES DESDE EL LANDING PAGE
const applicationFilter = ref('pending') // 'pending' | 'approved' | 'rejected' | 'all'
const showApprovedCredentialsModal = ref(false)
const approvedCredentialsData = ref(null)

const filteredApplications = computed(() => {
  const apps = adminStore.resellerApplications || []
  if (applicationFilter.value === 'all') return apps
  return apps.filter(a => a.status === applicationFilter.value)
})

const handleApproveApplication = (app) => {
  const res = adminStore.approveResellerApplication(app.id)
  if (res.success) {
    approvedCredentialsData.value = {
      reseller: res.reseller,
      application: res.application,
      message: res.credentialsMessage
    }
    showApprovedCredentialsModal.value = true
    toast.add(`¡Revendedor ${res.reseller.name} (${res.reseller.id}) creado y aprobado con éxito!`, 'success')
  } else {
    toast.add(res.error || 'Error al aprobar solicitud', 'error')
  }
}

const handleRejectApplication = (app) => {
  adminStore.rejectResellerApplication(app.id)
  toast.add(`Solicitud de ${app.fullName} marcada como rechazada`, 'info')
}

const handleDeleteApplication = (app) => {
  if (confirm(`¿Eliminar la solicitud de ${app.fullName}?`)) {
    adminStore.deleteResellerApplication(app.id)
    toast.add('Solicitud eliminada', 'info')
  }
}

const sendCredentialsWhatsApp = (data) => {
  if (!data?.reseller?.contactNumber) return
  const cleanPhone = data.reseller.contactNumber.replace(/[^0-9]/g, '')
  window.open(`https://wa.me/${cleanPhone}?text=${encodeURIComponent(data.message)}`, '_blank')
}

const contactApplicantWhatsApp = (app) => {
  const cleanPhone = (app.whatsapp || '').replace(/[^0-9]/g, '')
  const msg = `¡Hola ${app.fullName}! Te contactamos desde el Panel de Proveedor Mayorista de StreamPro respecto a tu postulación como distribuidor oficial.`
  window.open(`https://wa.me/${cleanPhone}?text=${encodeURIComponent(msg)}`, '_blank')
}

// Control de Sesión y Autenticación de Administrador / Proveedor Mayorista
const isAdminAuthenticated = ref(false)
const adminLoginForm = ref({
  username: '',
  password: ''
})
const adminLoginError = ref('')
const rememberAdminSession = ref(true)
const adminBiometricsAvailable = ref(false)
const hasAdminEnrolledBiometrics = ref(false)
const isAdminBiometricLoading = ref(false)

onMounted(async () => {
  adminBiometricsAvailable.value = await isBiometricsAvailable()
  hasAdminEnrolledBiometrics.value = hasBiometricEnrolled('admin')

  // Auto-restaurar sesión recordada de administrador
  if (!isAdminAuthenticated.value) {
    const saved = getRememberedSession('admin')
    if (saved && (saved.username || saved.id === 'admin')) {
      const masterUser = (adminStore.adminAuth?.username || 'admin').toLowerCase()
      if (saved.username?.toLowerCase() === masterUser || saved.id === 'admin') {
        isAdminAuthenticated.value = true
        toast.add(`¡Bienvenido de nuevo, ${adminStore.adminAuth?.name || 'Administrador'}! Sesión restaurada.`, 'success')
      }
    }
  }
})

const handleAdminLogin = () => {
  adminLoginError.value = ''
  const u = adminLoginForm.value.username.trim().toLowerCase()
  const p = adminLoginForm.value.password.trim()

  const masterUser = (adminStore.adminAuth?.username || 'admin').toLowerCase()
  const masterPass = adminStore.adminAuth?.password || 'StreamPro2026*Admin'

  if (u === masterUser && p === masterPass) {
    isAdminAuthenticated.value = true
    if (rememberAdminSession.value) {
      saveRememberedSession('admin', { id: 'admin', username: masterUser, name: adminStore.adminAuth?.name || 'Administrador Principal' })
    } else {
      clearRememberedSession('admin')
    }
    toast.add(`¡Bienvenido al Centro Mayorista Proveedor, ${adminStore.adminAuth?.name || 'Administrador'}!`, 'success')
  } else {
    adminLoginError.value = 'Credenciales maestras incorrectas. Verifica tu usuario y contraseña de Administrador.'
    toast.add(adminLoginError.value, 'error')
  }
}

// Inicio Rápido de Administrador con Biometría (Windows Hello / Touch ID / Face ID)
const handleAdminBiometricLogin = async () => {
  adminLoginError.value = ''
  isAdminBiometricLoading.value = true
  try {
    const res = await authenticateWithBiometrics('admin')
    if (res.success) {
      isAdminAuthenticated.value = true
      if (rememberAdminSession.value) {
        saveRememberedSession('admin', { id: 'admin', username: adminStore.adminAuth?.username || 'admin', name: adminStore.adminAuth?.name || 'Administrador Principal' })
      }
      toast.add('🔓 ¡Acceso biométrico verificado! Bienvenido Administrador.', 'success')
    } else {
      adminLoginError.value = res.error || 'No se pudo verificar la huella o biometría'
      toast.add(adminLoginError.value, 'error')
    }
  } catch (e) {
    adminLoginError.value = 'Error al activar lector biométrico'
    toast.add(adminLoginError.value, 'error')
  } finally {
    isAdminBiometricLoading.value = false
  }
}

// Vincular Biometría de Administrador en este equipo
const handleAdminEnrollBiometrics = async () => {
  const user = {
    id: 'admin',
    username: adminStore.adminAuth?.username || 'admin',
    name: adminStore.adminAuth?.name || 'Administrador Principal'
  }
  const res = await registerBiometricCredential('admin', user)
  if (res.success) {
    hasAdminEnrolledBiometrics.value = true
    toast.add('✅ ¡Biometría (Huella / Windows Hello / Face ID) configurada para Administrador en este equipo!', 'success')
  } else {
    toast.add(res.error || 'No se pudo configurar la biometría', 'error')
  }
}

const handleAdminRemoveBiometrics = () => {
  removeBiometricEnrollment('admin')
  hasAdminEnrolledBiometrics.value = false
  toast.add('Biometría de Administrador desvinculada de este equipo.', 'info')
}

const handleAdminLogout = () => {
  clearRememberedSession('admin')
  isAdminAuthenticated.value = false
  adminLoginForm.value.username = ''
  adminLoginForm.value.password = ''
  toast.add('Sesión de Administrador cerrada correctamente.', 'info')
}

// GESTIÓN DE CREDENCIALES MAESTRAS DE ADMINISTRADOR
const showAdminAuthModal = ref(false)
const adminAuthForm = ref({
  username: '',
  password: '',
  name: '',
  whatsapp: ''
})

const openAdminAuthModal = () => {
  adminAuthForm.value = {
    username: adminStore.adminAuth?.username || 'admin',
    password: adminStore.adminAuth?.password || '',
    name: adminStore.adminAuth?.name || 'Administrador Principal',
    whatsapp: adminStore.adminAuth?.whatsapp || '+54 9 11 0000-0000'
  }
  showAdminAuthModal.value = true
}

const saveAdminAuth = () => {
  if (!adminAuthForm.value.username.trim() || !adminAuthForm.value.password.trim()) {
    toast.add('El usuario y la contraseña no pueden estar vacíos', 'error')
    return
  }
  adminStore.updateAdminAuth(adminAuthForm.value)
  toast.add('Credenciales maestras de Administrador actualizadas con éxito', 'success')
  showAdminAuthModal.value = false
}

// HERRAMIENTAS DE BASE DE DATOS (BACKUP, RESTORE, RESET)
const handleExportBackup = () => {
  const json = exportDatabaseBackup()
  if (!json) {
    toast.add('Error al exportar base de datos', 'error')
    return
  }
  const blob = new Blob([json], { type: 'application/json' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `streampro_db_backup_${new Date().toISOString().split('T')[0]}.json`
  a.click()
  URL.revokeObjectURL(url)
  toast.add('Copia de seguridad exportada correctamente (.json)', 'success')
}

const handleImportBackup = (e) => {
  const file = e.target.files?.[0]
  if (!file) return
  const reader = new FileReader()
  reader.onload = (event) => {
    const success = importDatabaseBackup(event.target.result)
    if (success) {
      toast.add('Base de datos restaurada con éxito. Actualizando...', 'success')
      setTimeout(() => window.location.reload(), 800)
    } else {
      toast.add('El archivo de copia de seguridad no es válido', 'error')
    }
  }
  reader.readAsText(file)
}

const handleResetCleanProduction = () => {
  const confirmText = prompt('⚠️ ATENCIÓN: Se eliminarán todas las cuentas, pedidos y clientes para dejar la base de datos limpia de producción.\n\nEscribe "LIMPIAR" para confirmar:')
  if (confirmText === 'LIMPIAR') {
    adminStore.resetDatabaseToCleanProduction()
    toast.add('Base de datos reiniciada a PRODUCCIÓN LIMPIA con éxito', 'success')
  } else if (confirmText !== null) {
    toast.add('Operación cancelada. El texto no coincidió.', 'info')
  }
}

// EDITOR DE PRODUCTOS VIRTUALES Y PRECIOS DEL CATÁLOGO
const editingPlanModal = ref(false)
const editingPlatformKey = ref('')
const editingPlanKey = ref('')
const planEditForm = ref({
  label: '',
  providerCost: 0,
  resellerProfit: 0,
  clientPrice: 0
})

const openPlanEdit = (platKey, pKey, plan) => {
  editingPlatformKey.value = platKey
  editingPlanKey.value = pKey
  planEditForm.value = {
    label: plan.label || '',
    providerCost: Number(plan.providerCost || 0),
    resellerProfit: Number(plan.resellerProfit || 0),
    clientPrice: Number(plan.clientPrice || 0)
  }
  editingPlanModal.value = true
}

const savePlanEdit = () => {
  adminStore.updatePricingPlan({
    platform: editingPlatformKey.value,
    planKey: editingPlanKey.value,
    planData: {
      label: planEditForm.value.label,
      providerCost: Number(planEditForm.value.providerCost),
      resellerProfit: Number(planEditForm.value.resellerProfit),
      clientPrice: Number(planEditForm.value.clientPrice)
    }
  })
  toast.add(`Precios de ${editingPlatformKey.value} (${planEditForm.value.label}) actualizados y guardados en DB`, 'success')
  editingPlanModal.value = false
}

const showAddPlatformModal = ref(false)
const newPlatformForm = ref({
  key: '',
  name: '',
  planLabel: '1 Pantalla / Perfil',
  providerCost: 3500,
  resellerProfit: 1500,
  clientPrice: 5000
})

const submitAddPlatform = () => {
  if (!newPlatformForm.value.key.trim() || !newPlatformForm.value.name.trim()) {
    toast.add('El código y nombre de la plataforma son requeridos', 'error')
    return
  }
  adminStore.addPlatform({
    key: newPlatformForm.value.key.trim(),
    name: newPlatformForm.value.name.trim(),
    plans: {
      '1_perfil': {
        label: newPlatformForm.value.planLabel.trim(),
        units: 1,
        type: 'perfil',
        providerCost: Number(newPlatformForm.value.providerCost),
        resellerProfit: Number(newPlatformForm.value.resellerProfit),
        clientPrice: Number(newPlatformForm.value.clientPrice)
      }
    }
  })
  toast.add(`Plataforma ${newPlatformForm.value.name} guardada en el catálogo virtual`, 'success')
  showAddPlatformModal.value = false
}

// GESTIÓN Y ARMADO DE PAQUETES & OFERTAS (PROVEEDOR)
const newPackageForm = ref({
  title: '',
  description: '',
  badge: '🔥 25% OFF',
  selectedServices: [
    { platform: 'Netflix', planKey: '1_perfil', units: 1 },
    { platform: 'Spotify', planKey: '1_perfil', units: 1 }
  ],
  promoPrice: 0
})

const addServiceToPackage = () => {
  newPackageForm.value.selectedServices.push({
    platform: 'Disney+',
    planKey: '1_perfil',
    units: 1
  })
}

const removeServiceFromPackage = (index) => {
  if (newPackageForm.value.selectedServices.length > 1) {
    newPackageForm.value.selectedServices.splice(index, 1)
  }
}

// Cálculo dinámico de costos y precios sugeridos de los servicios seleccionados
const packageCalculation = computed(() => {
  let totalCost = 0
  let totalRegular = 0

  newPackageForm.value.selectedServices.forEach(s => {
    const platData = adminStore.pricingCatalog[s.platform]
    if (platData) {
      const plan = platData.plans[s.planKey]
      if (plan) {
        totalCost += plan.providerCost || 0
        totalRegular += plan.clientPrice || 0
      }
    }
  })

  const promo = Number(newPackageForm.value.promoPrice) || totalRegular
  const profit = promo - totalCost
  const discount = totalRegular > 0 ? Math.max(0, Math.round(((totalRegular - promo) / totalRegular) * 100)) : 0

  return { totalCost, totalRegular, promo, profit, discount }
})

// Auto sugerir precio promo cuando cambia la lista de servicios seleccionados
watch(() => newPackageForm.value.selectedServices, () => {
  let totalRegular = 0
  newPackageForm.value.selectedServices.forEach(s => {
    const platData = adminStore.pricingCatalog[s.platform]
    const plan = platData?.plans[s.planKey]
    if (plan) totalRegular += plan.clientPrice || 0
  })
  if (totalRegular > 0 && (!newPackageForm.value.promoPrice || newPackageForm.value.promoPrice === 0)) {
    newPackageForm.value.promoPrice = Math.round(totalRegular * 0.8) // 20% OFF por defecto
  }
}, { deep: true, immediate: true })

const submitCreatePackage = () => {
  if (!newPackageForm.value.title.trim()) {
    toast.add('Ingresa un título para el paquete o combo', 'error')
    return
  }

  const servicesFormatted = newPackageForm.value.selectedServices.map(s => {
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

  const res = adminStore.createPackage({
    title: newPackageForm.value.title.trim(),
    description: newPackageForm.value.description.trim() || 'Combo exclusivo de entretenimiento con múltiples plataformas combinadas.',
    badge: newPackageForm.value.badge.trim() || `🔥 ${packageCalculation.value.discount}% OFF`,
    services: servicesFormatted,
    promoPrice: Number(newPackageForm.value.promoPrice) || packageCalculation.value.totalRegular,
    createdBy: 'provider',
    resellerId: 'ALL'
  })

  if (res.success) {
    toast.add(`¡Paquete oficial "${res.package.title}" publicado con éxito!`, 'success')
    newPackageForm.value = {
      title: '',
      description: '',
      badge: '🔥 25% OFF',
      selectedServices: [
        { platform: 'Netflix', planKey: '1_perfil', units: 1 },
        { platform: 'Spotify', planKey: '1_perfil', units: 1 }
      ],
      promoPrice: 0
    }
  }
}

const togglePackageState = (pkgId) => {
  const res = adminStore.togglePackage(pkgId)
  if (res.success) {
    toast.add(`Estado del paquete actualizado: ${res.active ? 'Activo en Tienda' : 'Pausado'}`, 'info')
  }
}

const deletePackageItem = (pkgId) => {
  if (confirm('¿Estás seguro de que deseas eliminar este paquete?')) {
    adminStore.deletePackage(pkgId)
    toast.add('Paquete eliminado del sistema.', 'info')
  }
}
</script>

<template>
  <!-- PANTALLA DE ACCESO RESTRINGIDO: PROVEEDOR / ADMINISTRACIÓN -->
  <div v-if="!isAdminAuthenticated" class="min-h-[70vh] flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-slate-900 border border-slate-800 rounded-3xl p-8 shadow-2xl space-y-6 relative overflow-hidden">
      <!-- Glow decorativo -->
      <div class="absolute -top-12 -right-12 w-40 h-40 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>

      <div class="text-center space-y-2">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-amber-500/15 border border-amber-500/30 text-2xl text-amber-400 shadow-inner">
          🛡️
        </div>
        <h2 class="text-2xl font-black text-white tracking-tight">Centro Mayorista Proveedor</h2>
        <p class="text-xs text-slate-400">Acceso a inventario central, auditoría de revendedores y pedidos físicos.</p>
      </div>

      <div v-if="adminLoginError" class="p-3.5 bg-red-950/40 border border-red-500/40 rounded-xl text-red-300 text-xs flex items-center gap-2">
        <span class="shrink-0 text-base">⚠️</span>
        <span>{{ adminLoginError }}</span>
      </div>

      <form @submit.prevent="handleAdminLogin" class="space-y-4">
        <div>
          <label class="block text-xs font-bold text-slate-300 mb-1.5 uppercase tracking-wider">Usuario Administrador</label>
          <input 
            v-model="adminLoginForm.username" 
            type="text" 
            placeholder="ej. admin" 
            required 
            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-amber-400 transition-all"
          >
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-300 mb-1.5 uppercase tracking-wider">Contraseña o PIN</label>
          <input 
            v-model="adminLoginForm.password" 
            type="password" 
            placeholder="••••••••••••" 
            required 
            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-amber-400 transition-all font-mono"
          >
        </div>

        <div class="flex items-center justify-between text-xs pt-1">
          <label class="flex items-center gap-2 text-slate-300 cursor-pointer select-none hover:text-white transition-colors">
            <input 
              v-model="rememberAdminSession" 
              type="checkbox" 
              class="w-4 h-4 rounded bg-slate-950 border-slate-700 text-amber-500 focus:ring-amber-500 focus:ring-offset-slate-900 cursor-pointer"
            >
            <span>Recordar sesión de Administrador</span>
          </label>
        </div>

        <button 
          type="submit" 
          class="w-full bg-amber-500 hover:bg-amber-400 text-slate-950 font-black py-3 rounded-xl transition-all shadow-lg shadow-amber-500/20 flex items-center justify-center gap-2 text-sm"
        >
          <span>Ingresar a Gestión Mayorista</span>
          <span>→</span>
        </button>

        <!-- Botón de Inicio Rápido con Biometría para Administrador -->
        <div v-if="hasAdminEnrolledBiometrics || adminBiometricsAvailable" class="pt-2">
          <div class="relative flex py-2 items-center">
            <div class="flex-grow border-t border-slate-800"></div>
            <span class="flex-shrink mx-3 text-[10px] text-slate-500 uppercase font-bold tracking-wider">o acceso rápido</span>
            <div class="flex-grow border-t border-slate-800"></div>
          </div>

          <button 
            type="button" 
            @click="handleAdminBiometricLogin"
            :disabled="isAdminBiometricLoading"
            class="w-full bg-gradient-to-r from-amber-500/15 via-yellow-500/15 to-amber-500/15 hover:from-amber-500/25 hover:to-yellow-500/25 text-amber-300 border border-amber-500/40 font-bold py-2.5 rounded-xl transition-all flex items-center justify-center gap-2 text-xs shadow-sm disabled:opacity-50"
          >
            <span class="text-base">👆</span>
            <span v-if="isAdminBiometricLoading">Verificando Biometría / Windows Hello...</span>
            <span v-else>Iniciar con Huella / Face ID / Windows Hello</span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- DASHBOARD MAYORISTA AUTENTICADO -->
  <div v-else class="space-y-8">
    <!-- Barra Superior de Sesión de Proveedor -->
    <div class="bg-gradient-to-r from-slate-900 via-amber-950/30 to-slate-900 p-4 rounded-2xl border border-amber-500/30 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-amber-500/20 border border-amber-500/30 text-amber-400 flex items-center justify-center font-black text-sm">
          🛡️
        </div>
        <div>
          <div class="flex items-center gap-2">
            <h3 class="font-black text-white text-sm sm:text-base">Centro Mayorista StreamPro S.R.L.</h3>
            <span class="text-[10px] font-mono font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30 px-2 py-0.5 rounded">
              ADMIN SESIÓN ACTIVA
            </span>
          </div>
          <p class="text-xs text-slate-400 font-mono">
            Panel de Control Central de Proveedor · Cuentas & Pedidos Físicos
          </p>
        </div>
      </div>

      <div class="flex flex-wrap items-center gap-2 self-end sm:self-auto">
        <!-- Biometría de Administrador -->
        <button 
          v-if="hasAdminEnrolledBiometrics"
          @click="handleAdminRemoveBiometrics"
          class="bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 border border-amber-500/30 px-3 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 shadow-sm"
          title="Huella / Biometría activa para Administrador en este equipo. Clic para desvincular."
        >
          <span>🔐</span>
          <span>Huella Admin Activa ✓</span>
        </button>
        <button 
          v-else
          @click="handleAdminEnrollBiometrics"
          class="bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700 px-3 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 shadow-sm"
          title="Vincular inicio de sesión rápido con Huella / Windows Hello / Face ID para Administrador"
        >
          <span>👆</span>
          <span>Activar Huella Admin</span>
        </button>
        <button 
          @click="showFlyerModal = true"
          class="bg-gradient-to-r from-amber-500/20 to-orange-500/20 hover:from-amber-500/30 hover:to-orange-500/30 text-amber-300 border border-amber-500/30 px-3 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 shadow"
          title="Crear imágenes publicitarias profesionales para WhatsApp e Instagram"
        >
          <span>🎨 Generar Flyer</span>
        </button>
        <button 
          @click="openAdminAuthModal"
          class="bg-amber-500/15 hover:bg-amber-500/30 text-amber-300 border border-amber-500/30 px-3 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5"
          title="Modificar usuario y clave maestra de administración"
        >
          <span>🔐 Mis Credenciales</span>
        </button>
        <button 
          @click="handleExportBackup"
          class="bg-blue-500/15 hover:bg-blue-500/30 text-blue-300 border border-blue-500/30 px-3 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5"
          title="Descargar copia de seguridad de la base de datos (.json)"
        >
          <span>💾 Backup DB</span>
        </button>
        <button 
          @click="handleResetCleanProduction"
          class="bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/20 px-3 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5"
          title="Reiniciar a base de datos limpia de producción"
        >
          <span>🧹 Limpiar DB</span>
        </button>
        <button 
          @click="handleAdminLogout" 
          class="bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 px-3 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5"
          title="Cerrar Sesión de Administrador"
        >
          <span>🚪 Salir</span>
        </button>
      </div>
    </div>
    <!-- Métricas Financieras del Proveedor -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="bg-slate-900/60 p-6 rounded-2xl border border-slate-700/50 backdrop-blur-sm relative overflow-hidden group hover:border-brand-accent/50 transition-colors">
        <div class="absolute right-0 top-0 w-32 h-32 bg-blue-500/10 rounded-full blur-2xl group-hover:bg-blue-500/20 transition-colors"></div>
        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 relative z-10">Capital Invertido en Stock</h3>
        <p class="text-4xl font-black text-white relative z-10">${{ adminStore.totalInvestment }} <span class="text-sm font-medium text-slate-500">ARS</span></p>
      </div>
      
      <div class="bg-slate-900/60 p-6 rounded-2xl border border-slate-700/50 backdrop-blur-sm relative overflow-hidden group hover:border-emerald-500/50 transition-colors">
        <div class="absolute right-0 top-0 w-32 h-32 bg-emerald-500/10 rounded-full blur-2xl group-hover:bg-emerald-500/20 transition-colors"></div>
        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 relative z-10">Cuentas Listas para Despacho</h3>
        <p class="text-4xl font-black text-emerald-400 relative z-10">{{ adminStore.availableStock }} <span class="text-sm font-medium text-emerald-600/50">Lotes Máster</span></p>
      </div>
      
      <div class="bg-slate-900/60 p-6 rounded-2xl border border-slate-700/50 backdrop-blur-sm relative overflow-hidden group hover:border-amber-500/50 transition-colors">
        <div class="absolute right-0 top-0 w-32 h-32 bg-amber-500/10 rounded-full blur-2xl group-hover:bg-amber-500/20 transition-colors"></div>
        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 relative z-10">Despachos Pendientes</h3>
        <p class="text-4xl font-black text-amber-400 relative z-10">{{ adminStore.pendingOrders.length }} <span class="text-sm font-medium text-amber-600/50">Por Entregar</span></p>
      </div>
    </div>

    <!-- Navegación de Pestañas del Proveedor -->
    <div class="flex flex-wrap items-center justify-between gap-4">
      <div class="flex flex-wrap gap-2 p-1.5 bg-slate-900/60 backdrop-blur rounded-2xl border border-slate-800">
        <button 
          @click="adminTab = 'inventory'"
          :class="['px-5 py-2.5 rounded-xl font-bold text-xs transition-all flex items-center gap-2', adminTab === 'inventory' ? 'bg-brand-accent text-slate-950 font-black shadow' : 'text-slate-400 hover:text-white hover:bg-slate-800']"
        >
          📦 Inventario & Stock
        </button>
        <button 
          @click="adminTab = 'catalog'"
          :class="['px-5 py-2.5 rounded-xl font-bold text-xs transition-all flex items-center gap-2', adminTab === 'catalog' ? 'bg-cyan-400 text-slate-950 font-black shadow' : 'text-slate-400 hover:text-white hover:bg-slate-800']"
        >
          🏷️ Catálogo & Precios ({{ Object.keys(adminStore.pricingCatalog).length }})
        </button>
        <button 
          @click="adminTab = 'orders'"
          :class="['px-5 py-2.5 rounded-xl font-bold text-xs transition-all flex items-center gap-2', adminTab === 'orders' ? 'bg-brand-accent text-slate-950 font-black shadow' : 'text-slate-400 hover:text-white hover:bg-slate-800']"
        >
          🚚 Despachos ({{ adminStore.pendingOrders.length }})
        </button>
        <button 
          @click="adminTab = 'renewals'"
          :class="['px-5 py-2.5 rounded-xl font-bold text-xs transition-all flex items-center gap-2', adminTab === 'renewals' ? 'bg-brand-accent text-slate-950 font-black shadow' : 'text-slate-400 hover:text-white hover:bg-slate-800']"
        >
          🔄 Renovaciones ({{ adminStore.renewalRequests.length }})
        </button>
        <button 
          @click="adminTab = 'resellers'"
          :class="['px-5 py-2.5 rounded-xl font-bold text-xs transition-all flex items-center gap-2', adminTab === 'resellers' ? 'bg-brand-accent text-slate-950 font-black shadow' : 'text-slate-400 hover:text-white hover:bg-slate-800']"
        >
          👥 Revendedores ({{ adminStore.resellers.length }})
          <span v-if="adminStore.pendingResellerApplications?.length > 0" class="bg-red-500 text-white font-mono text-[10px] px-2 py-0.5 rounded-full font-black animate-pulse shadow">
            {{ adminStore.pendingResellerApplications.length }} solicitudes
          </span>
        </button>
        <button 
          @click="adminTab = 'clients'"
          :class="['px-5 py-2.5 rounded-xl font-bold text-xs transition-all flex items-center gap-2', adminTab === 'clients' ? 'bg-brand-accent text-slate-950 font-black shadow' : 'text-slate-400 hover:text-white hover:bg-slate-800']"
        >
          👤 Clientes & Ventas ({{ resellerStore.clients.length }})
        </button>
        <button 
          @click="adminTab = 'physical'"
          :class="['px-5 py-2.5 rounded-xl font-bold text-xs transition-all flex items-center gap-2', adminTab === 'physical' ? 'bg-amber-400 text-slate-950 font-black shadow' : 'text-slate-400 hover:text-white hover:bg-slate-800']"
        >
          📦 Pedidos Físicos ({{ adminStore.pendingPhysicalOrders.length }})
        </button>
        <button 
          @click="adminTab = 'packages'"
          :class="['px-5 py-2.5 rounded-xl font-bold text-xs transition-all flex items-center gap-2', adminTab === 'packages' ? 'bg-gradient-to-r from-purple-500 to-pink-500 text-white font-black shadow' : 'text-slate-400 hover:text-white hover:bg-slate-800']"
        >
          🎁 Paquetes & Ofertas ({{ adminStore.packages.length }})
        </button>
        <button 
          @click="adminTab = 'bank'"
          :class="['px-5 py-2.5 rounded-xl font-bold text-xs transition-all flex items-center gap-2', adminTab === 'bank' ? 'bg-brand-accent text-slate-950 font-black shadow' : 'text-slate-400 hover:text-white hover:bg-slate-800']"
        >
          🏦 Cuentas & Validador de Pagos
          <span v-if="adminStore.bankTransfers?.length > 0" class="bg-emerald-500 text-slate-950 font-mono text-[10px] px-2 py-0.5 rounded-full font-black">
            {{ adminStore.bankTransfers.length }}
          </span>
        </button>
      </div>

      <!-- Botón para Notificar Eventualidad al Revendedor (YA FUE AVISADO) -->
      <button 
        @click="openIncidentModal()" 
        class="bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs px-4 py-2.5 rounded-xl transition-all shadow-md flex items-center gap-2"
      >
        <span>📢 Avisar Eventualidad a Revendedor</span>
      </button>
    </div>

    <!-- Módulo de Renovaciones Solicitadas: El Proveedor puede cambiar datos de cuenta -->
    <div v-if="adminTab === 'renewals' || adminTab === 'inventory'" class="bg-slate-900/40 rounded-3xl border border-slate-800 p-8 backdrop-blur-md">
      <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-800">
        <div class="w-10 h-10 rounded-xl bg-blue-500/10 flex items-center justify-center text-blue-400 border border-blue-500/20">
          🔄
        </div>
        <div class="flex-1">
          <div class="flex items-center gap-2">
            <h2 class="text-xl font-black text-white">Solicitudes de Renovación (Cambio de Datos de Cuenta)</h2>
            <span class="text-xs font-mono font-bold bg-blue-500/20 text-blue-300 border border-blue-500/30 px-2 py-0.5 rounded">
              {{ adminStore.renewalRequests.length }} Solicitud(es)
            </span>
          </div>
          <p class="text-xs text-slate-400">Puedes cambiar o mantener los datos de acceso para la renovación. Al guardar, se enviará una alerta al revendedor.</p>
        </div>
      </div>

      <div v-if="adminStore.renewalRequests.length === 0" class="text-slate-500 text-center py-8 bg-slate-900/30 rounded-2xl border border-slate-800 border-dashed text-xs">
        No hay solicitudes de renovación pendientes en este momento.
      </div>

      <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div v-for="ren in adminStore.renewalRequests" :key="ren.id" class="bg-[#020617] p-5 rounded-2xl border border-slate-800 space-y-3">
          <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
            <div>
              <div class="flex items-center gap-2">
                <span class="text-xs font-mono font-bold text-brand-accent bg-slate-900 px-2 py-0.5 rounded border border-slate-800">{{ ren.clientNumber }}</span>
                <span :class="['text-[10px] font-bold px-2 py-0.5 rounded border', ren.status === 'Pending Provider' ? 'bg-amber-500/15 text-amber-300 border-amber-500/30' : ren.status === 'Renovada (Misma Cuenta)' ? 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30' : 'bg-blue-500/15 text-blue-300 border-blue-500/30']">
                  {{ ren.status }}
                </span>
              </div>
              <h4 class="font-bold text-white text-base mt-1">{{ ren.clientName }} • {{ ren.platform }}</h4>
              <p class="text-xs text-slate-400">Revendedor: <strong class="text-amber-400">{{ ren.resellerId }}</strong> • Plan: <span class="text-slate-200">{{ ren.planName }}</span></p>
            </div>
            
            <div class="flex flex-wrap gap-2">
              <button 
                @click="handleRenewSameAccount(ren, 30)" 
                class="bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold px-3.5 py-2 rounded-xl transition-all shadow flex items-center gap-1.5"
                title="Extiende 30 días manteniendo las credenciales de la cuenta"
              >
                <span>⚡ Renovar Misma Cuenta (+30d)</span>
              </button>
              <button 
                @click="openRenewalEditModal(ren, 'edit')" 
                class="bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold px-3.5 py-2 rounded-xl transition-all shadow flex items-center gap-1.5"
                title="Editar y cambiar correo, contraseña o perfiles de la cuenta"
              >
                <span>✏️ Modificar Credenciales</span>
              </button>
            </div>
          </div>
          <div class="bg-slate-900/80 p-2.5 rounded-xl border border-slate-800 text-xs text-slate-300 font-mono flex justify-between items-center">
            <div>
              <p>Cuenta actual: <strong class="text-white">{{ ren.newCredentials?.account || 'No asignada' }}</strong></p>
              <p class="text-slate-500 text-[11px] mt-0.5">Fecha solicitada: {{ ren.dateRequested }}</p>
            </div>
            <span class="text-[11px] text-brand-accent font-sans">Garantía +30 Días</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Centro de Despachos de Pedidos -->
    <div v-if="adminTab === 'orders' || adminTab === 'inventory'" class="bg-slate-900/40 rounded-3xl border border-slate-800 p-8 backdrop-blur-md">
      <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-800">
        <div class="w-10 h-10 rounded-xl bg-amber-500/10 flex items-center justify-center text-amber-400 border border-amber-500/20">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
        </div>
        <div class="flex-1">
          <h2 class="text-2xl font-black">Centro de Despacho de Pedidos</h2>
          <p class="text-xs text-slate-400">Entrega cuentas verificando el Número de Cliente y los perfiles requeridos.</p>
        </div>
      </div>
      
      <div v-if="adminStore.pendingOrders.length === 0" class="text-slate-500 text-center py-10 bg-slate-900/30 rounded-2xl border border-slate-800 border-dashed">
        <p class="font-medium">Todos los pedidos se encuentran despachados y al día.</p>
      </div>
      
      <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <div v-for="order in adminStore.pendingOrders" :key="order.id" class="bg-[#020617] p-6 rounded-2xl border border-slate-800 hover:border-amber-500/40 transition-colors shadow-xl space-y-4">
          <div class="flex justify-between items-start gap-4">
            <div>
              <div class="flex items-center gap-2 mb-1">
                <span class="text-lg font-black text-white">{{ order.platform }}</span>
                <span class="text-xs font-mono text-brand-accent bg-brand-accent/15 px-2 py-0.5 rounded border border-brand-accent/30 font-bold">
                  {{ order.customerNumber }}
                </span>
                <span v-if="order.paymentMethod" class="text-[10px] bg-slate-800 text-slate-300 px-2 py-0.5 rounded font-mono">
                  {{ order.paymentMethod }}
                </span>
                <span v-if="order.bankTransferVerified" class="text-[10px] bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 px-2 py-0.5 rounded-full font-mono font-bold flex items-center gap-1">
                  <span>✓</span> Acreditada en {{ order.bankTransferDetails?.bankName || 'Banco' }} (${{ (order.bankTransferDetails?.amount || order.clientPrice).toLocaleString() }})
                </span>
              </div>
              <div class="text-xs text-slate-400 space-y-0.5">
                <p>Cliente: <strong class="text-white">{{ order.customerName }}</strong> ({{ order.customerPhone }})</p>
                <p v-if="order.resellerId === 'DIRECT' || !order.resellerId" class="pt-0.5">
                  <span class="text-[10px] font-black uppercase bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 px-2 py-0.5 rounded-full inline-flex items-center gap-1">
                    <span>🛒</span> VENTA DIRECTA OFICIAL (PROVEEDOR)
                  </span>
                </p>
                <p v-else>Revendedor: <strong class="text-amber-400">[{{ order.resellerId }}] {{ order.resellerName || '' }}</strong></p>
                <p>Plan: <strong class="text-slate-200">{{ order.planLabel }}</strong> • {{ order.pinPreference === 'same' ? 'Mismo PIN' : 'PINs distintos' }}</p>
              </div>
            </div>
            
            <div class="flex flex-col gap-2">
              <button 
                @click="openDispatchModal(order)" 
                class="bg-amber-500 hover:bg-amber-400 text-slate-950 px-5 py-2.5 font-black text-xs rounded-xl transition-all shadow-md flex items-center justify-center gap-2 cursor-pointer"
              >
                <span>Despachar Cuenta</span>
              </button>
              <button 
                v-if="order.resellerId === 'DIRECT' || !order.resellerId"
                @click="contactDirectCustomerWhatsApp(order)" 
                class="bg-emerald-600 hover:bg-emerald-500 text-white px-3 py-1.5 font-bold text-[11px] rounded-lg transition-colors flex items-center justify-center gap-1 cursor-pointer"
                title="Contactar al cliente directamente por WhatsApp"
              >
                <span>💬 WhatsApp Cliente</span>
              </button>
              <button 
                v-else
                @click="openIncidentModal(order)" 
                class="bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white px-3 py-1.5 font-bold text-[11px] rounded-lg border border-slate-700 transition-colors cursor-pointer"
                title="Avisar novedad de este cliente al revendedor"
              >
                📢 Avisar Revendedor
              </button>
            </div>
          </div>

          <div class="bg-slate-900/90 p-3 rounded-xl border border-slate-800">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">Nombres requeridos:</p>
            <div class="flex flex-wrap gap-2">
              <div v-for="(p, pIdx) in order.profileSelections" :key="pIdx" class="bg-slate-950 px-2.5 py-1 rounded text-xs border border-slate-800 flex items-center gap-1.5">
                <span class="text-brand-accent font-mono font-semibold">👤 {{ p.name }}</span>
                <span class="text-slate-500 text-[10px]">PIN por asignar</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- SECCIÓN: ADMINISTRAR REVENDEDORES DESDE EL PANEL PROVEEDOR -->
    <div v-if="adminTab === 'resellers'" class="bg-slate-900/40 rounded-3xl border border-slate-800 p-8 backdrop-blur-md space-y-6">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pb-4 border-b border-slate-800">
        <div>
          <h2 class="text-2xl font-black text-white">Administración de Revendedores Oficiales</h2>
          <p class="text-xs text-slate-400">Gestiona las cuentas de distribuidores, sus datos bancarios y su billetera.</p>
        </div>
        <button 
          @click="openAddResellerModal" 
          class="bg-brand-accent text-slate-950 font-black text-xs px-5 py-2.5 rounded-xl hover:bg-sky-400 transition-all shadow"
        >
          + Agregar Nuevo Revendedor
        </button>
      </div>

      <!-- SECCIÓN: SOLICITUDES DE NUEVOS REVENDEDORES DESDE EL LANDING PAGE -->
      <div class="bg-gradient-to-b from-[#020617] via-slate-950 to-[#020617] rounded-2xl border border-amber-500/30 p-6 space-y-5 shadow-2xl relative overflow-hidden">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 border-b border-slate-800 pb-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-amber-500 to-orange-500 flex items-center justify-center text-slate-950 font-black text-lg shadow">
              📥
            </div>
            <div>
              <h3 class="text-lg font-black text-white flex items-center gap-2">
                Solicitudes de Nuevos Revendedores
                <span class="text-xs font-mono font-bold bg-amber-400/20 text-amber-300 border border-amber-400/30 px-2 py-0.5 rounded">
                  {{ adminStore.resellerApplications?.length || 0 }} Totales • {{ adminStore.pendingResellerApplications?.length || 0 }} Pendientes
                </span>
              </h3>
              <p class="text-xs text-slate-400">Postulaciones enviadas en tiempo real desde el formulario del landing page (/revendedores).</p>
            </div>
          </div>

          <!-- Filtro de Solicitudes -->
          <div class="flex gap-1.5 p-1 bg-slate-900 rounded-xl border border-slate-800 text-xs">
            <button 
              type="button"
              @click="applicationFilter = 'pending'"
              :class="['px-3 py-1.5 rounded-lg font-bold transition-all', applicationFilter === 'pending' ? 'bg-amber-400 text-slate-950 shadow' : 'text-slate-400 hover:text-white']"
            >
              Pendientes ({{ adminStore.pendingResellerApplications?.length || 0 }})
            </button>
            <button 
              type="button"
              @click="applicationFilter = 'approved'"
              :class="['px-3 py-1.5 rounded-lg font-bold transition-all', applicationFilter === 'approved' ? 'bg-emerald-500 text-slate-950 shadow' : 'text-slate-400 hover:text-white']"
            >
              Aprobadas ({{ adminStore.approvedResellerApplications?.length || 0 }})
            </button>
            <button 
              type="button"
              @click="applicationFilter = 'all'"
              :class="['px-3 py-1.5 rounded-lg font-bold transition-all', applicationFilter === 'all' ? 'bg-slate-800 text-white shadow' : 'text-slate-400 hover:text-white']"
            >
              Todas ({{ adminStore.resellerApplications?.length || 0 }})
            </button>
          </div>
        </div>

        <!-- Estado vacío si no hay solicitudes -->
        <div v-if="filteredApplications.length === 0" class="text-center py-10 text-slate-500 text-xs bg-slate-950/60 rounded-xl border border-slate-800/80 border-dashed">
          <span class="text-2xl block mb-1">📭</span>
          No hay solicitudes de revendedores con el filtro seleccionado.
        </div>

        <!-- Grid de Solicitudes -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div 
            v-for="app in filteredApplications" 
            :key="app.id"
            :class="[
              'p-4 rounded-xl border space-y-3 relative transition-all shadow-md flex flex-col justify-between',
              app.status === 'pending' ? 'bg-slate-950 border-amber-500/30 hover:border-amber-500/60' :
              app.status === 'approved' ? 'bg-slate-950 border-emerald-500/30' :
              'bg-slate-950/60 border-slate-800 opacity-60'
            ]"
          >
            <div>
              <div class="flex items-center justify-between gap-2 mb-1.5">
                <span class="text-[11px] font-mono font-bold text-amber-400 bg-amber-400/10 px-2 py-0.5 rounded border border-amber-400/20">
                  {{ app.id }}
                </span>
                <span :class="[
                  'text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded border',
                  app.status === 'pending' ? 'bg-amber-500/15 text-amber-300 border-amber-500/30 animate-pulse' :
                  app.status === 'approved' ? 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30' :
                  'bg-red-500/15 text-red-300 border-red-500/30'
                ]">
                  {{ app.status === 'pending' ? '● Pendiente' : app.status === 'approved' ? '✓ Aprobado' : '✕ Rechazado' }}
                </span>
              </div>

              <h4 class="font-black text-white text-base leading-snug">{{ app.fullName }}</h4>
              <span class="text-[11px] font-mono text-slate-400 block">{{ app.city || 'Ciudad no especificada' }}</span>
              <span v-if="app.storeName" class="text-[11px] text-amber-300 font-bold block mt-0.5">
                🏪 Tienda propuesta: {{ app.storeName }}
              </span>

              <div class="space-y-1 text-xs text-slate-300 mt-2.5 pt-2.5 border-t border-slate-800/80">
                <p class="flex items-center gap-1.5">
                  <span class="text-slate-500">WhatsApp:</span>
                  <strong class="text-white font-mono">{{ app.whatsapp }}</strong>
                </p>
                <p v-if="app.email" class="truncate flex items-center gap-1.5">
                  <span class="text-slate-500">Email:</span>
                  <span class="text-slate-300 font-mono text-[11px]">{{ app.email }}</span>
                </p>
                <p class="text-[11px] text-slate-400">
                  <span class="text-slate-500">Experiencia:</span> {{ app.experience }}
                </p>
                <p v-if="app.notes" class="text-[11px] text-slate-400 italic bg-slate-900 p-2 rounded-lg border border-slate-800 mt-1">
                  "{{ app.notes }}"
                </p>
                <span class="text-[10px] text-slate-500 font-mono block mt-1">
                  Enviado: {{ app.submittedAt }}
                </span>
              </div>
            </div>

            <!-- Botones de Acción de la Solicitud -->
            <div class="pt-3 border-t border-slate-800 space-y-2">
              <div v-if="app.status === 'pending'" class="flex gap-2">
                <button 
                  @click="handleApproveApplication(app)" 
                  class="flex-1 bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-400 hover:to-emerald-500 text-slate-950 font-black text-xs py-2 rounded-xl shadow transition-all flex items-center justify-center gap-1 cursor-pointer"
                  title="Dar de alta como revendedor oficial y generar credenciales de acceso"
                >
                  <span>✅ Aprobar & Crear</span>
                </button>
                <button 
                  @click="contactApplicantWhatsApp(app)" 
                  class="bg-slate-800 hover:bg-slate-700 text-emerald-400 p-2 rounded-xl border border-slate-700 transition-colors"
                  title="Contactar al postulante por WhatsApp"
                >
                  💬
                </button>
                <button 
                  @click="handleRejectApplication(app)" 
                  class="bg-slate-800 hover:bg-red-950/60 text-slate-400 hover:text-red-400 p-2 rounded-xl border border-slate-700 transition-colors"
                  title="Rechazar solicitud"
                >
                  ✕
                </button>
              </div>
              <div v-else-if="app.status === 'approved'" class="flex items-center justify-between text-xs">
                <span class="text-emerald-400 font-mono font-bold text-[11px] flex items-center gap-1">
                  <span>✓</span> Asignado: [{{ app.createdResellerId }}]
                </span>
                <div class="flex gap-1.5">
                  <button 
                    @click="contactApplicantWhatsApp(app)" 
                    class="bg-slate-800 hover:bg-slate-700 text-slate-300 text-[11px] px-2.5 py-1 rounded-lg border border-slate-700 font-bold"
                  >
                    💬 WhatsApp
                  </button>
                  <button 
                    @click="handleDeleteApplication(app)" 
                    class="text-slate-500 hover:text-red-400 text-xs px-2 py-1 rounded"
                    title="Eliminar registro"
                  >
                    🗑️
                  </button>
                </div>
              </div>
              <div v-else class="flex justify-between items-center text-xs">
                <span class="text-slate-500 text-[11px] italic">Solicitud rechazada</span>
                <button 
                  @click="handleDeleteApplication(app)" 
                  class="text-slate-500 hover:text-red-400 text-xs px-2 py-1 rounded"
                  title="Eliminar registro"
                >
                  🗑️ Eliminar
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div v-for="res in adminStore.resellers" :key="res.id" class="bg-[#020617] p-6 rounded-2xl border border-slate-800 space-y-4 shadow-xl">
          <div class="flex justify-between items-start">
            <div>
              <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-mono font-bold bg-amber-500/15 text-amber-400 px-2 py-0.5 rounded border border-amber-500/30">
                  [{{ res.id }}]
                </span>
                <span :class="['text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded border', res.isActive !== false ? 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30' : 'bg-red-500/15 text-red-400 border-red-500/30']">
                  {{ res.isActive !== false ? '● ACTIVO' : '○ DESACTIVADO' }}
                </span>
                <span class="text-[11px] font-mono font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 px-2 py-0.5 rounded">
                  @{{ res.username }}
                </span>
              </div>
              <h4 class="font-black text-white text-lg mt-1.5">{{ res.name }}</h4>
              <p class="text-xs text-slate-400">WhatsApp: <span class="text-slate-200">{{ res.contactNumber }}</span></p>
            </div>
            <div class="text-right">
              <span class="text-[10px] text-slate-500 uppercase font-bold block">Billetera / Saldo</span>
              <span class="text-emerald-400 font-mono font-black text-xl">${{ res.balance || 0 }} ARS</span>
            </div>
          </div>

          <!-- Métricas de Cuentas Vendidas y Clientes del Revendedor -->
          <div class="grid grid-cols-3 gap-2 bg-slate-900/90 p-3 rounded-xl border border-slate-800 text-center font-mono">
            <div>
              <span class="text-[10px] text-slate-500 uppercase font-bold block font-sans">Clientes Activos</span>
              <span class="text-base font-black text-white">
                {{ resellerStore.clients.filter(c => c.resellerId === res.id).length }}
              </span>
            </div>
            <div>
              <span class="text-[10px] text-slate-500 uppercase font-bold block font-sans">Pantallas Vendidas</span>
              <span class="text-base font-black text-sky-400">
                {{ resellerStore.clients.filter(c => c.resellerId === res.id).reduce((acc, c) => acc + (c.devices || 1), 0) }}
              </span>
            </div>
            <div>
              <span class="text-[10px] text-slate-500 uppercase font-bold block font-sans">Total Cobrado</span>
              <span class="text-base font-black text-emerald-400">
                ${{ resellerStore.clients.filter(c => c.resellerId === res.id).reduce((acc, c) => acc + (c.clientPaid || 0), 0) }}
              </span>
            </div>
          </div>

          <!-- Datos de Transferencia y Cobro -->
          <div class="bg-slate-900/60 p-3.5 rounded-xl border border-slate-800 text-xs text-slate-300 font-mono space-y-1">
            <p><span class="text-slate-500 font-sans">Titular:</span> {{ res.holderName }} (CUIT: {{ res.cuit }})</p>
            <p><span class="text-slate-500 font-sans">Banco:</span> {{ res.bankName }}</p>
            <p><span class="text-slate-500 font-sans">Alias/CBU:</span> <strong class="text-amber-300">{{ res.cbuAlias }}</strong></p>
          </div>

          <!-- Cuentas y Servicios Despachados a este Revendedor -->
          <div class="space-y-2">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Últimas Cuentas Entregadas a sus Clientes:</span>
            <div v-if="resellerStore.clients.filter(c => c.resellerId === res.id).length === 0" class="text-xs text-slate-500 italic p-2 bg-slate-950 rounded-lg text-center">
              Aún no registra compras despachadas
            </div>
            <div v-else class="space-y-1.5 max-h-[140px] overflow-y-auto pr-1">
              <div 
                v-for="c in resellerStore.clients.filter(c => c.resellerId === res.id)" 
                :key="c.id"
                class="bg-slate-950 p-2.5 rounded-lg border border-slate-800 flex justify-between items-center text-xs"
              >
                <div>
                  <div class="flex items-center gap-1.5">
                    <span class="font-mono text-[10px] font-bold bg-sky-500/20 text-sky-300 px-1.5 py-0.5 rounded">{{ c.clientNumber }}</span>
                    <strong class="text-white">{{ c.name }}</strong>
                    <span class="text-slate-400 text-[11px]">({{ c.platform }})</span>
                  </div>
                  <div class="text-[11px] text-slate-400 font-mono mt-0.5">{{ c.account }} • Vence: {{ c.expiry }}</div>
                </div>
                <span :class="['text-[10px] font-bold px-2 py-0.5 rounded border', c.status === 'Activa' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' : 'bg-amber-500/10 text-amber-400 border-amber-500/30']">
                  {{ c.status }}
                </span>
              </div>
            </div>
          </div>

          <!-- Acciones de Gestión de Revendedor -->
          <div class="flex flex-wrap justify-end gap-2 pt-2 border-t border-slate-800/80">
            <button 
              @click="copyResellerCredentials(res)" 
              class="bg-emerald-600/20 hover:bg-emerald-600 text-emerald-300 hover:text-white border border-emerald-500/30 text-xs font-bold px-3 py-2 rounded-xl transition-all flex items-center gap-1.5"
              title="Copiar credenciales oficiales y enlace para enviar por WhatsApp"
            >
              <span>📲 Copiar para WhatsApp</span>
            </button>
            <button 
              @click="openResellerAuthModal(res)" 
              class="bg-indigo-600/20 hover:bg-indigo-600 text-indigo-300 hover:text-white border border-indigo-500/30 text-xs font-bold px-3 py-2 rounded-xl transition-all flex items-center gap-1.5"
              title="Configurar usuario, contraseña y estado de activación"
            >
              <span>🔐 Acceso & Clave</span>
            </button>
            <button 
              @click="toggleResellerStatus(res.id)" 
              :class="['text-xs font-bold px-3 py-2 rounded-xl transition-all border flex items-center gap-1.5', res.isActive !== false ? 'bg-red-500/10 hover:bg-red-500/20 text-red-400 border-red-500/20' : 'bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border-emerald-500/20']"
              :title="res.isActive !== false ? 'Desactivar acceso a este revendedor' : 'Activar acceso a este revendedor'"
            >
              <span>{{ res.isActive !== false ? '🚫 Desactivar' : '✅ Activar' }}</span>
            </button>
            <button 
              @click="viewClientsOfReseller(res.id)" 
              class="bg-sky-600/20 hover:bg-sky-600 text-sky-300 hover:text-white border border-sky-500/30 text-xs font-bold px-3 py-2 rounded-xl transition-all flex items-center gap-1.5"
            >
              <span>👤 Clientes ({{ resellerStore.clients.filter(c => c.resellerId === res.id).length }})</span>
            </button>
            <button 
              @click="openRechargeModal(res)" 
              class="bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold px-3 py-2 rounded-xl shadow transition-all flex items-center gap-1.5"
            >
              <span>💰 Cargar Saldo</span>
            </button>
            <button 
              @click="openIncidentModal({ resellerId: res.id, customerNumber: 'GENERAL', customerName: res.name })" 
              class="bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold px-3 py-2 rounded-xl border border-slate-700"
            >
              📢 Alerta
            </button>
            <button 
              @click="handleDeleteReseller(res.id)" 
              class="bg-red-500/10 hover:bg-red-500/30 text-red-400 border border-red-500/20 text-xs font-bold px-2.5 py-2 rounded-xl transition-all"
              title="Eliminar revendedor de la base de datos"
            >
              🗑️
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- SECCIÓN: CLIENTES Y CUENTAS VENDIDAS DE TODOS LOS REVENDEDORES -->
    <div v-if="adminTab === 'clients'" class="bg-slate-900/40 rounded-3xl border border-slate-800 p-8 backdrop-blur-md space-y-6">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pb-4 border-b border-slate-800">
        <div>
          <h2 class="text-2xl font-black text-white">Auditoría Global de Clientes por Revendedor</h2>
          <p class="text-xs text-slate-400">Supervisa las cuentas entregadas a clientes, credenciales activas, perfiles asignados y los distribuidores responsables.</p>
        </div>
        <div class="flex items-center gap-2">
          <span class="text-xs font-mono font-bold bg-sky-500/20 text-sky-300 px-3 py-1.5 rounded-xl border border-sky-500/30">
            {{ filteredClientsForAdmin.length }} / {{ resellerStore.clients.length }} Clientes
          </span>
        </div>
      </div>

      <!-- AUDITORÍA DE NOTIFICACIONES Y ALERTAS POR REVENDEDOR (ESTADO: YA FUE AVISADO) -->
      <div class="bg-indigo-950/30 border border-indigo-500/30 rounded-2xl p-5 space-y-3">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 border-b border-indigo-500/20 pb-3">
          <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-indigo-400 animate-ping"></span>
            <h3 class="font-black text-indigo-200 text-sm">
              Notificaciones y Alertas a Revendedores (Auditoría: YA FUE AVISADO)
            </h3>
            <span class="text-[11px] font-mono text-indigo-400 bg-indigo-500/10 px-2 py-0.5 rounded border border-indigo-500/20">
              {{ filteredAlertsForAdmin.length }} registro(s)
            </span>
          </div>
          <div class="flex items-center gap-2">
            <button 
              @click="openIncidentModal()" 
              class="bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold px-3 py-1.5 rounded-xl shadow transition-all flex items-center gap-1"
            >
              <span>📢 Redactar Comunicado</span>
            </button>
          </div>
        </div>

        <div v-if="filteredAlertsForAdmin.length === 0" class="text-center py-4 text-slate-500 text-xs italic">
          No hay notificaciones registradas para el revendedor seleccionado.
        </div>
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
          <div 
            v-for="alert in filteredAlertsForAdmin" 
            :key="alert.id"
            class="bg-[#020617] p-3.5 rounded-xl border border-indigo-500/30 flex flex-col justify-between gap-2 shadow-sm text-xs"
          >
            <div class="space-y-1.5">
              <div class="flex items-center justify-between gap-1">
                <span class="text-[10px] font-black uppercase tracking-wider bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 px-2 py-0.5 rounded">
                  {{ alert.statusText || 'YA FUE AVISADO' }}
                </span>
                <span class="text-[10px] font-mono text-slate-400">{{ alert.date }}</span>
              </div>
              <div class="font-bold text-white text-xs">{{ alert.title }}</div>
              <p class="text-slate-300 text-[11px] leading-relaxed">{{ alert.message }}</p>
            </div>
            <div class="flex items-center justify-between pt-2 border-t border-slate-800 text-[11px] font-mono">
              <span class="text-brand-accent">Para: [{{ alert.resellerId }}]</span>
              <button 
                @click="dismissAlertAdmin(alert.id)" 
                class="text-red-400 hover:text-red-300 font-bold hover:underline cursor-pointer"
              >
                Eliminar
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Barra de Filtros y Búsqueda Pro de Clientes -->
      <div class="flex flex-col md:flex-row justify-between items-stretch md:items-center gap-4 bg-slate-950/60 p-4 rounded-2xl border border-slate-800">
        <!-- Selector de Revendedor -->
        <div class="flex items-center gap-2">
          <span class="text-xs text-slate-400 font-bold uppercase whitespace-nowrap">Filtrar Revendedor:</span>
          <select v-model="selectedResellerForFilter" class="bg-[#020617] border border-slate-800 rounded-xl px-3 py-2 text-xs text-white font-semibold outline-none focus:border-brand-accent">
            <option value="All">🌐 Todos los Revendedores</option>
            <option v-for="res in adminStore.resellers" :key="res.id" :value="res.id">
              [{{ res.id }}] {{ res.name }}
            </option>
          </select>
        </div>

        <!-- Filtro por Estado -->
        <div class="flex flex-wrap gap-1 bg-slate-900 p-1 rounded-xl border border-slate-800">
          <button 
            @click="clientStatusFilter = 'All'" 
            :class="['px-3 py-1 text-xs rounded-lg font-bold transition-all', clientStatusFilter === 'All' ? 'bg-brand-accent text-slate-950' : 'text-slate-400 hover:text-white']"
          >
            Todos
          </button>
          <button 
            @click="clientStatusFilter = 'Activa'" 
            :class="['px-3 py-1 text-xs rounded-lg font-bold transition-all', clientStatusFilter === 'Activa' ? 'bg-emerald-500 text-slate-950' : 'text-emerald-400 hover:text-white']"
          >
            Activas
          </button>
          <button 
            @click="clientStatusFilter = 'Por Vencer'" 
            :class="['px-3 py-1 text-xs rounded-lg font-bold transition-all', clientStatusFilter === 'Por Vencer' ? 'bg-amber-500 text-slate-950' : 'text-amber-400 hover:text-white']"
          >
            Por Vencer
          </button>
          <button 
            @click="clientStatusFilter = 'Vencida'" 
            :class="['px-3 py-1 text-xs rounded-lg font-bold transition-all', clientStatusFilter === 'Vencida' ? 'bg-red-500 text-white' : 'text-red-400 hover:text-white']"
          >
            Vencidas
          </button>
        </div>

        <!-- Buscador -->
        <div class="relative flex-1 max-w-md">
          <input 
            v-model="clientSearchQuery" 
            type="text" 
            placeholder="Buscar por Nº Cliente (CLI-...), Nombre, Teléfono, Correo o Plataforma..." 
            class="w-full bg-[#020617] border border-slate-800 focus:border-brand-accent rounded-xl py-2 pl-3 pr-8 text-xs text-white outline-none"
          >
          <button v-if="clientSearchQuery" @click="clientSearchQuery = ''" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white text-xs">✕</button>
        </div>
      </div>

      <div class="overflow-x-auto rounded-2xl border border-slate-800 bg-[#020617]">
        <table class="w-full text-left text-sm whitespace-nowrap">
          <thead class="bg-slate-950 text-slate-400 text-xs uppercase tracking-wider font-bold border-b border-slate-800">
            <tr>
              <th class="px-6 py-4">N° Cliente / Nombre</th>
              <th class="px-6 py-4">Revendedor Responsable</th>
              <th class="px-6 py-4">Plataforma & Plan</th>
              <th class="px-6 py-4">Credenciales Asignadas (Cuenta / Clave)</th>
              <th class="px-6 py-4">Perfiles & PINs</th>
              <th class="px-6 py-4">Vencimiento SLA</th>
              <th class="px-6 py-4">Estado</th>
              <th class="px-6 py-4 text-center">Acciones Proveedor</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800">
            <tr v-if="filteredClientsForAdmin.length === 0">
              <td colspan="8" class="text-center py-8 text-slate-500 text-xs italic">
                No se encontraron clientes para los filtros aplicados.
              </td>
            </tr>
            <tr v-for="client in filteredClientsForAdmin" :key="client.id" class="hover:bg-slate-800/40 transition-colors">
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <span class="font-mono text-xs font-bold bg-brand-accent/15 text-brand-accent px-2 py-0.5 rounded border border-brand-accent/30">
                    {{ client.clientNumber || client.id }}
                  </span>
                  <span class="font-bold text-white">{{ client.name }}</span>
                </div>
                <div class="text-[11px] font-mono text-slate-400 mt-0.5">{{ client.phone }}</div>
              </td>
              <td class="px-6 py-4">
                <span class="text-xs font-mono font-bold bg-slate-900 text-amber-300 px-2 py-1 rounded border border-slate-800">
                  {{ client.resellerId }}
                </span>
              </td>
              <td class="px-6 py-4">
                <div class="font-bold text-white">{{ client.platform }}</div>
                <div class="text-xs text-sky-400 font-mono">{{ client.planName || client.devices + ' Pantalla(s)' }}</div>
              </td>
              <td class="px-6 py-4 font-mono text-xs">
                <div class="text-slate-200 flex items-center gap-1.5">
                  <span>{{ client.account }}</span>
                </div>
                <div class="text-slate-400 text-[11px] flex items-center gap-1 mt-0.5">
                  <span>Pass: </span>
                  <span v-if="showPasswordMap[client.id]" class="text-amber-300 font-bold">{{ client.pass }}</span>
                  <span v-else class="text-slate-500">••••••••</span>
                  <button 
                    @click="togglePasswordVisibility(client.id)" 
                    class="text-[10px] text-sky-400 hover:text-sky-300 ml-1 underline cursor-pointer"
                  >
                    {{ showPasswordMap[client.id] ? 'Ocultar' : 'Ver' }}
                  </button>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="flex flex-wrap gap-1 max-w-xs">
                  <div v-for="(p, pIdx) in client.profiles" :key="pIdx" class="bg-slate-950 px-2 py-0.5 rounded text-[11px] font-mono border border-slate-800 flex items-center gap-1">
                    <span class="text-slate-300">{{ p.name }}</span>
                    <span class="text-amber-400 font-bold bg-amber-400/10 px-1 rounded">PIN: {{ p.pin }}</span>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 font-mono text-xs">
                <div class="text-slate-200 font-bold">{{ client.expiry }}</div>
                <div v-if="getClientExpiryDays(client.expiry) <= 3 && getClientExpiryDays(client.expiry) >= 0" class="text-[10px] text-amber-400 font-bold flex items-center gap-1 mt-0.5">
                  <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-ping"></span>
                  <span>Vence en {{ getClientExpiryDays(client.expiry) }}d</span>
                </div>
                <div v-else-if="getClientExpiryDays(client.expiry) < 0" class="text-[10px] text-red-400 font-bold mt-0.5">
                  Venció hace {{ Math.abs(getClientExpiryDays(client.expiry)) }}d
                </div>
              </td>
              <td class="px-6 py-4">
                <span :class="['px-2.5 py-1 rounded-full text-xs font-bold border inline-block', client.status === 'Activa' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' : client.status === 'Por Vencer' ? 'bg-amber-500/10 text-amber-400 border-amber-500/30' : 'bg-red-500/10 text-red-400 border-red-500/30']">
                  {{ client.status }}
                </span>
              </td>
              <td class="px-6 py-4 text-center">
                <div class="flex items-center justify-center gap-1.5">
                  <button 
                    @click="copyClientDeliveryFormat(client)" 
                    class="bg-emerald-600/20 hover:bg-emerald-600 text-emerald-400 hover:text-white px-2.5 py-1.5 rounded-lg text-xs font-bold border border-emerald-500/30 transition-all flex items-center gap-1"
                    title="Copiar credenciales completas para enviar por WhatsApp"
                  >
                    <span>💬</span>
                    <span>Copiar</span>
                  </button>
                  <button 
                    @click="sendWhatsAppRenewalReminder(client)" 
                    class="bg-amber-500/20 hover:bg-amber-500 text-amber-300 hover:text-slate-950 px-2.5 py-1.5 rounded-lg text-xs font-bold border border-amber-500/30 transition-all flex items-center gap-1"
                    title="Enviar recordatorio persuasivo de renovación por WhatsApp"
                  >
                    <span>⏰</span>
                    <span>Recordar</span>
                  </button>
                  <button 
                    @click="openIncidentModal({ resellerId: client.resellerId, customerNumber: client.clientNumber, customerName: client.name })" 
                    class="bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white px-2.5 py-1.5 rounded-lg text-xs font-bold border border-slate-700 transition-colors"
                    title="Avisar novedad de este cliente al revendedor"
                  >
                    📢 Avisar
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- SECCIÓN: CATÁLOGO DE PRODUCTOS VIRTUALES Y CONFIGURACIÓN DE PRECIOS -->
    <div v-if="adminTab === 'catalog'" class="bg-slate-900/40 rounded-3xl border border-slate-800 p-8 backdrop-blur-md space-y-6">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pb-4 border-b border-slate-800">
        <div>
          <div class="flex items-center gap-2">
            <h2 class="text-2xl font-black text-white">Catálogo de Productos Virtuales & Planes</h2>
            <span class="text-xs font-mono font-bold bg-cyan-500/20 text-cyan-300 border border-cyan-500/30 px-2 py-0.5 rounded">
              BASE DE DATOS EN VIVO
            </span>
          </div>
          <p class="text-xs text-slate-400">Edita los costos mayoristas, ganancias de revendedores y precios al cliente de cada suscripción virtual.</p>
        </div>
        <button 
          @click="showAddPlatformModal = true"
          class="bg-cyan-400 hover:bg-cyan-300 text-slate-950 font-black text-xs px-5 py-2.5 rounded-xl transition-all shadow flex items-center gap-2"
        >
          <span>+ Agregar Nueva Plataforma</span>
        </button>
      </div>

      <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div 
          v-for="(platData, platKey) in adminStore.pricingCatalog" 
          :key="platKey"
          class="bg-[#020617] rounded-2xl border border-slate-800 p-6 space-y-4 shadow-xl relative overflow-hidden"
        >
          <div class="flex justify-between items-start border-b border-slate-800 pb-3">
            <div>
              <div class="flex items-center gap-2">
                <span class="font-black text-white text-lg">{{ platData.name || platKey }}</span>
                <span class="text-[10px] font-mono font-bold bg-slate-900 text-slate-300 px-2 py-0.5 rounded border border-slate-800">
                  {{ platKey }}
                </span>
              </div>
              <p class="text-xs text-slate-500">{{ Object.keys(platData.plans || {}).length }} plan(es) configurados</p>
            </div>
            <button 
              @click="handleDeletePlatform(platKey)"
              class="text-slate-500 hover:text-red-400 text-xs p-1"
              title="Eliminar plataforma del catálogo"
            >
              ✕
            </button>
          </div>

          <div class="space-y-2">
            <div 
              v-for="(plan, pKey) in platData.plans" 
              :key="pKey"
              class="bg-slate-950 p-3.5 rounded-xl border border-slate-800/80 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 text-xs"
            >
              <div>
                <strong class="text-white block">{{ plan.label }}</strong>
                <span class="text-[11px] text-slate-400 font-mono">Tipo: {{ plan.type }} • Unidades: {{ plan.units }}</span>
              </div>
              <div class="flex flex-wrap items-center gap-3">
                <div class="text-right font-mono">
                  <div class="text-slate-400 text-[10px]">Costo: <strong class="text-slate-200">${{ plan.providerCost?.toLocaleString() }}</strong></div>
                  <div class="text-emerald-400 text-[10px]">Ganancia Rev.: <strong>+${{ plan.resellerProfit?.toLocaleString() }}</strong></div>
                  <div class="text-amber-400 font-bold text-xs">P. Venta: ${{ plan.clientPrice?.toLocaleString() }} ARS</div>
                </div>
                <button 
                  @click="openPlanEdit(platKey, pKey, plan)"
                  class="bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white px-3 py-1.5 rounded-lg text-xs font-bold border border-slate-700 transition-colors"
                >
                  ✏️ Editar
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- SECCIÓN: CENTRO DE COBRANZAS, MULTI-CUENTA BANCARIA Y VALIDADOR WANDERLUST -->
    <div v-if="adminTab === 'bank'" class="bg-slate-900/40 rounded-3xl border border-slate-800 p-6 sm:p-8 backdrop-blur-md space-y-6">
      
      <!-- Encabezado con Navegación de Sub-Pestañas -->
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 border-b border-slate-800 pb-5">
        <div>
          <div class="flex items-center gap-2">
            <span class="text-2xl">🏦</span>
            <h2 class="text-2xl font-black text-white">Centro de Cobranzas & Validador de Transferencias</h2>
          </div>
          <p class="text-xs text-slate-400 mt-1">
            Administra tus cuentas bancarias, alterna la cuenta activa con 1 clic y verifica acreditaciones automáticas por correo estilo Wanderlust.
          </p>
        </div>

        <!-- Sub-Pestañas -->
        <div class="flex bg-slate-950 p-1 rounded-xl border border-slate-800 gap-1 text-xs font-bold">
          <button 
            @click="bankSubTab = 'accounts'"
            :class="['px-3.5 py-1.5 rounded-lg transition-all flex items-center gap-1.5 cursor-pointer', bankSubTab === 'accounts' ? 'bg-brand-accent text-slate-950 font-black' : 'text-slate-400 hover:text-white']"
          >
            <span>💳 Cuentas Bancarias</span>
            <span class="text-[10px] bg-slate-800 px-1.5 py-0.2 rounded font-mono">{{ adminStore.providerBankAccounts?.length || 1 }}</span>
          </button>
          <button 
            @click="bankSubTab = 'validator'"
            :class="['px-3.5 py-1.5 rounded-lg transition-all flex items-center gap-1.5 cursor-pointer', bankSubTab === 'validator' ? 'bg-amber-400 text-slate-950 font-black' : 'text-slate-400 hover:text-white']"
          >
            <span>⚡ Validador de Correos</span>
            <span v-if="adminStore.bankTransfers?.length > 0" class="text-[10px] bg-emerald-500 text-slate-950 px-1.5 py-0.2 rounded font-mono font-bold">
              {{ adminStore.bankTransfers.length }}
            </span>
          </button>
          <button 
            @click="bankSubTab = 'webhook_guide'"
            :class="['px-3.5 py-1.5 rounded-lg transition-all flex items-center gap-1.5 cursor-pointer', bankSubTab === 'webhook_guide' ? 'bg-indigo-500 text-white font-black' : 'text-slate-400 hover:text-white']"
          >
            <span>🤖 Conexión Gmail / IMAP</span>
          </button>
        </div>
      </div>

      <!-- SUB-TAB 1: GESTIÓN MULTI-CUENTA BANCARIA -->
      <div v-if="bankSubTab === 'accounts'" class="space-y-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 bg-slate-950 p-4 rounded-2xl border border-slate-800">
          <div>
            <span class="text-xs font-bold text-white block">Selector de Cuenta Bancaria Activa</span>
            <p class="text-[11px] text-slate-400">
              La cuenta marcada como <strong class="text-emerald-400">ACTIVA</strong> es la que se muestra en el Checkout a los clientes y en los paneles de revendedores.
            </p>
          </div>
          <button 
            @click="openAddAccountModal()" 
            class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs px-4 py-2.5 rounded-xl transition-all shadow flex items-center gap-2 cursor-pointer shrink-0"
          >
            <span>+ Agregar Otra Cuenta</span>
          </button>
        </div>

        <!-- Grid de Cuentas Bancarias Registradas -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
          <div 
            v-for="acc in adminStore.providerBankAccounts" 
            :key="acc.id"
            :class="['p-5 rounded-2xl border transition-all relative flex flex-col justify-between space-y-4 shadow-xl', acc.isActive ? 'bg-gradient-to-br from-slate-900 via-slate-900 to-indigo-950/40 border-emerald-500/50 shadow-emerald-500/5 ring-1 ring-emerald-500/30' : 'bg-[#020617] border-slate-800 hover:border-slate-700']"
          >
            <div class="space-y-3">
              <!-- Header de Cuenta -->
              <div class="flex justify-between items-start gap-2">
                <div>
                  <span class="text-[10px] font-mono uppercase text-slate-500 block">{{ acc.bankName || 'Banco' }}</span>
                  <h3 class="text-base font-black text-white flex items-center gap-2">
                    <span>{{ acc.name }}</span>
                  </h3>
                </div>
                <span 
                  v-if="acc.isActive" 
                  class="bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 text-[10px] font-mono font-bold px-2.5 py-1 rounded-full flex items-center gap-1.5 shadow"
                >
                  <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                  <span>CUENTA ACTIVA</span>
                </span>
                <span v-else class="text-[10px] font-mono text-slate-500 bg-slate-900 px-2 py-0.5 rounded border border-slate-800">
                  Secundaria
                </span>
              </div>

              <!-- Detalles de la Cuenta -->
              <div class="space-y-1.5 text-xs font-mono text-slate-300 bg-slate-950/80 p-3 rounded-xl border border-slate-800/80">
                <p><span class="text-slate-500">Titular:</span> <strong class="text-white">{{ acc.holderName }}</strong></p>
                <p><span class="text-slate-500">CUIT:</span> <span class="text-slate-200">{{ acc.cuit }}</span></p>
                <div class="pt-1 border-t border-slate-800/60">
                  <p class="text-amber-400 font-bold text-xs truncate">Alias: {{ acc.cbuAlias }}</p>
                  <p class="text-slate-400 text-[11px] truncate">CBU: {{ acc.cbuNumber }}</p>
                </div>
                <p v-if="acc.notifyEmail" class="text-[11px] text-sky-400 pt-0.5 truncate">
                  📧 Notificaciones: {{ acc.notifyEmail }}
                </p>
              </div>
            </div>

            <!-- Botones de Acción de Cuenta -->
            <div class="flex items-center gap-2 pt-2 border-t border-slate-800/80">
              <button 
                v-if="!acc.isActive" 
                @click="handleSetActiveAccount(acc.id)" 
                class="flex-1 bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs py-2 rounded-xl transition-all shadow flex items-center justify-center gap-1.5 cursor-pointer"
              >
                <span>⚡ Activar para Cobros</span>
              </button>
              <div v-else class="flex-1 text-center py-2 text-xs font-bold text-emerald-400 flex items-center justify-center gap-1.5">
                <span>✓ Cobrando por esta cuenta</span>
              </div>

              <button 
                @click="openAddAccountModal(acc)" 
                class="bg-slate-800 hover:bg-slate-700 text-slate-300 p-2 rounded-xl text-xs transition-colors cursor-pointer"
                title="Editar datos de esta cuenta"
              >
                ✏️
              </button>
              <button 
                v-if="adminStore.providerBankAccounts.length > 1" 
                @click="handleDeleteAccount(acc.id)" 
                class="bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 p-2 rounded-xl text-xs transition-colors cursor-pointer"
                title="Eliminar esta cuenta"
              >
                🗑️
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- SUB-TAB 2: VALIDADOR DE TRANSFERENCIAS POR CORREO (ESTILO WANDERLUST) -->
      <div v-if="bankSubTab === 'validator'" class="space-y-6">
        
        <!-- Banner Explicativo -->
        <div class="bg-gradient-to-r from-amber-500/15 via-orange-500/10 to-slate-900 border border-amber-500/30 p-5 rounded-2xl flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
          <div class="space-y-1">
            <span class="text-xs font-black uppercase tracking-wider text-amber-400 bg-amber-400/10 px-2.5 py-0.5 rounded-full border border-amber-400/20">
              ⚡ MOTOR WANDERLUST DE CONCILIACIÓN
            </span>
            <h3 class="text-base font-black text-white">Reconocimiento Automático de Acreditaciones</h3>
            <p class="text-xs text-slate-300 max-w-2xl">
              Cuando un cliente transfiere, Mercado Pago o tu Banco envían un correo de aviso. Este motor extrae el importe, remitente y N° de operación y lo vincula inmediatamente con la orden pendiente.
            </p>
          </div>
          <div class="text-right shrink-0">
            <span class="text-2xl font-black text-emerald-400 font-mono block">{{ adminStore.bankTransfers?.length || 0 }}</span>
            <span class="text-[11px] text-slate-400">Transferencias Procesadas</span>
          </div>
        </div>

        <!-- Escáner / Probador Manual de Correos y Comprobantes -->
        <div class="bg-slate-950 p-6 rounded-2xl border border-slate-800 space-y-4">
          <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
            <div>
              <h4 class="text-sm font-bold text-white flex items-center gap-2">
                <span>🔍</span>
                <span>Escanear Notificación de Correo o Comprobante Bancario</span>
              </h4>
              <p class="text-xs text-slate-400">Pega el correo o texto de Mercado Pago, Galicia, Santander, etc., para analizarlo en tiempo real.</p>
            </div>
            
            <!-- Botones de Ejemplo Rápido -->
            <div class="flex flex-wrap gap-2 text-xs">
              <span class="text-[11px] text-slate-500 font-semibold self-center hidden sm:inline">Probar con:</span>
              <button 
                type="button" 
                @click="loadSampleEmail('mp')" 
                class="bg-blue-600/20 hover:bg-blue-600/30 text-blue-400 border border-blue-500/30 px-2.5 py-1 rounded-lg transition-colors cursor-pointer text-[11px] font-bold"
              >
                💙 Mercado Pago
              </button>
              <button 
                type="button" 
                @click="loadSampleEmail('galicia')" 
                class="bg-orange-500/20 hover:bg-orange-500/30 text-orange-400 border border-orange-500/30 px-2.5 py-1 rounded-lg transition-colors cursor-pointer text-[11px] font-bold"
              >
                🧡 Galicia
              </button>
              <button 
                type="button" 
                @click="loadSampleEmail('brubank')" 
                class="bg-purple-500/20 hover:bg-purple-500/30 text-purple-400 border border-purple-500/30 px-2.5 py-1 rounded-lg transition-colors cursor-pointer text-[11px] font-bold"
              >
                💜 Brubank
              </button>
            </div>
          </div>

          <div class="space-y-3">
            <div>
              <label class="block text-[11px] font-bold uppercase text-slate-400 mb-1">Asunto del Correo (Opcional)</label>
              <input 
                v-model="testEmailSubject" 
                type="text" 
                placeholder="Ej: Te transfirieron $ 7.700 a tu cuenta de Mercado Pago" 
                class="w-full bg-[#020617] border border-slate-800 rounded-xl p-3 text-white text-xs outline-none focus:border-amber-400"
              >
            </div>
            <div>
              <label class="block text-[11px] font-bold uppercase text-slate-400 mb-1">Cuerpo / Texto del Correo o Notificación</label>
              <textarea 
                v-model="testEmailText" 
                rows="4" 
                placeholder="Pega aquí el contenido del aviso de transferencia recibido por correo..." 
                class="w-full bg-[#020617] border border-slate-800 rounded-xl p-3 text-white text-xs outline-none focus:border-amber-400 font-mono"
              ></textarea>
            </div>
          </div>

          <div class="flex justify-end gap-3 pt-1">
            <button 
              type="button" 
              @click="testEmailText = ''; testEmailSubject = ''" 
              class="bg-slate-900 hover:bg-slate-800 text-slate-400 px-4 py-2.5 rounded-xl text-xs font-bold transition-colors cursor-pointer"
            >
              Limpiar
            </button>
            <button 
              type="button" 
              @click="scanPastedEmail" 
              :disabled="isScanningEmail"
              class="bg-amber-400 hover:bg-amber-300 text-slate-950 font-black px-6 py-2.5 rounded-xl text-xs transition-all shadow-lg shadow-amber-400/20 flex items-center gap-2 cursor-pointer"
            >
              <span>{{ isScanningEmail ? 'Analizando...' : '⚡ Analizar y Cruzar con Pedidos' }}</span>
            </button>
          </div>
        </div>

        <!-- Lista de Transferencias Procesadas -->
        <div class="space-y-3">
          <div class="flex justify-between items-center">
            <h4 class="text-sm font-bold text-white flex items-center gap-2">
              <span>📋</span>
              <span>Bandeja de Transferencias Ingresadas ({{ adminStore.bankTransfers?.length || 0 }})</span>
            </h4>
            <span class="text-xs text-slate-400">Últimos avisos detectados</span>
          </div>

          <div v-if="!adminStore.bankTransfers || adminStore.bankTransfers.length === 0" class="text-slate-500 text-center py-10 bg-slate-950 rounded-2xl border border-slate-800 border-dashed text-xs">
            No hay transferencias registradas todavía. Pega un correo de prueba arriba o conecta tu cuenta de Gmail.
          </div>

          <div v-else class="space-y-3">
            <div 
              v-for="t in adminStore.bankTransfers" 
              :key="t.id"
              class="bg-[#020617] p-4 sm:p-5 rounded-2xl border border-slate-800 hover:border-slate-700 transition-all flex flex-col md:flex-row justify-between items-start md:items-center gap-4 text-xs"
            >
              <div class="flex items-start gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-center text-xl shrink-0">
                  {{ t.bankIcon || '🏦' }}
                </div>
                <div class="space-y-1">
                  <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-black text-white text-sm">{{ t.bankName }}</span>
                    <span class="font-mono font-black text-emerald-400 text-sm bg-emerald-500/10 px-2 py-0.5 rounded border border-emerald-500/20">
                      +${{ Number(t.amount).toLocaleString() }} ARS
                    </span>
                    <span class="text-slate-500 text-[10px] font-mono">{{ t.createdAt || 'Hoy' }}</span>
                  </div>
                  <p class="text-slate-300">
                    Remitente: <strong class="text-white">{{ t.sender }}</strong> • Op: <span class="font-mono text-slate-400">#{{ t.operationId }}</span>
                  </p>

                  <!-- ESTADO DE COINCIDENCIA CON PEDIDO -->
                  <div v-if="t.matchedOrderId" class="pt-1">
                    <div class="inline-flex items-center gap-2 bg-emerald-500/10 text-emerald-300 border border-emerald-500/30 px-3 py-1 rounded-xl text-[11px] font-medium">
                      <span>✓ Coincide con Pedido</span>
                      <strong class="font-mono font-bold text-white">#{{ t.matchedOrderClientNumber }}</strong>
                      <span>({{ t.matchedCustomerName }})</span>
                      <span v-if="t.reasons?.length" class="text-slate-400 text-[10px] hidden sm:inline">• {{ t.reasons[0] }}</span>
                    </div>
                  </div>
                  <div v-else class="pt-1">
                    <span class="inline-block bg-slate-900 text-slate-400 border border-slate-800 px-2.5 py-0.5 rounded-lg text-[10px]">
                      Sin pedido pendiente vinculado
                    </span>
                  </div>
                </div>
              </div>

              <!-- Acciones de la Transferencia -->
              <div class="flex items-center gap-2 shrink-0 self-end md:self-center">
                <button 
                  v-if="t.matchedOrderId && t.status !== 'confirmed'" 
                  @click="handleConfirmTransferWithOrder(t.matchedOrderId, t.id)" 
                  class="bg-emerald-600 hover:bg-emerald-500 text-white font-black px-4 py-2 rounded-xl transition-all shadow text-xs flex items-center gap-1.5 cursor-pointer"
                >
                  <span>✓ Aprobar Pedido</span>
                </button>
                <span v-else-if="t.status === 'confirmed'" class="text-emerald-400 font-bold text-xs font-mono px-3 py-1 bg-emerald-500/10 rounded-lg border border-emerald-500/20">
                  ✓ Verificado
                </span>

                <button 
                  @click="handleDeleteTransfer(t.id)" 
                  class="text-slate-500 hover:text-rose-400 p-2 rounded-lg hover:bg-slate-900 transition-colors cursor-pointer"
                  title="Eliminar registro"
                >
                  ✕
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- SUB-TAB 3: GUÍA DE CONEXIÓN GMAIL & WEBHOOK -->
      <div v-if="bankSubTab === 'webhook_guide'" class="space-y-6">
        <div class="bg-slate-950 p-6 rounded-2xl border border-slate-800 space-y-5">
          <div class="border-b border-slate-800 pb-3">
            <span class="text-xs font-bold text-indigo-400 uppercase tracking-wider block">Automatización 100% Manos Libres</span>
            <h3 class="text-xl font-black text-white mt-1">Cómo Conectar tu Correo (Estilo Wanderlust)</h3>
            <p class="text-xs text-slate-400 mt-1">
              Al igual que el plugin de Wanderlust en WooCommerce, puedes conectar tu cuenta de Gmail para que lea en tiempo real los correos de transferencias entrantes sin pagar suscripciones.
            </p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
            <div class="bg-slate-900/60 p-4 rounded-xl border border-slate-800 space-y-2">
              <div class="w-8 h-8 rounded-lg bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 flex items-center justify-center font-bold">1</div>
              <h4 class="font-bold text-white">Google Apps Script (Gratis)</h4>
              <p class="text-slate-400 text-[11px] leading-relaxed">
                Abre <a href="https://script.google.com" target="_blank" class="text-brand-accent underline">script.google.com</a> con la cuenta de Gmail donde recibes las notificaciones de Mercado Pago o Bancos.
              </p>
            </div>
            <div class="bg-slate-900/60 p-4 rounded-xl border border-slate-800 space-y-2">
              <div class="w-8 h-8 rounded-lg bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 flex items-center justify-center font-bold">2</div>
              <h4 class="font-bold text-white">Pega el Script Incluido</h4>
              <p class="text-slate-400 text-[11px] leading-relaxed">
                Copia el script listo que dejamos en <code class="text-amber-300">scripts/google-apps-script-email-watcher.gs</code>.
              </p>
            </div>
            <div class="bg-slate-900/60 p-4 rounded-xl border border-slate-800 space-y-2">
              <div class="w-8 h-8 rounded-lg bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 flex items-center justify-center font-bold">3</div>
              <h4 class="font-bold text-white">Disparador Cada 2 Minutos</h4>
              <p class="text-slate-400 text-[11px] leading-relaxed">
                Configura el temporizador automático. Cada vez que entre una transferencia, se analizará y conciliará con tus pedidos al instante.
              </p>
            </div>
          </div>

          <!-- Archivo de Script para Descargar / Visualizar -->
          <div class="p-4 bg-[#020617] rounded-xl border border-slate-800 text-xs font-mono space-y-2">
            <div class="flex justify-between items-center">
              <span class="text-slate-400 text-[11px]">📁 Script oficial incluido en el proyecto:</span>
              <span class="text-emerald-400 font-bold text-[10px]">scripts/google-apps-script-email-watcher.gs</span>
            </div>
            <p class="text-slate-400 text-[11px]">
              También cuentas con el microservicio IMAP en <code class="text-amber-300 font-sans">scripts/bank-email-watcher.js</code> si prefieres correrlo en tu propio servidor VPS o Node.js.
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- SECCIÓN: GESTIÓN DE PEDIDOS DE PRODUCTOS FÍSICOS (APROBAR / CANCELAR / COMISIONES 1% & 3%) -->
    <div v-if="adminTab === 'physical'" class="bg-slate-900/40 rounded-3xl border border-slate-800 p-8 backdrop-blur-md space-y-6">
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 border-b border-slate-800 pb-4">
        <div>
          <div class="flex flex-wrap items-center gap-2">
            <h2 class="text-2xl font-black text-white">Pedidos & Catálogo Físico</h2>
            <span class="text-xs font-mono font-bold bg-amber-400/20 text-amber-300 border border-amber-400/30 px-2 py-0.5 rounded">
              {{ adminStore.physicalProducts.length }} Productos Físicos Activos
            </span>
          </div>
          <p class="text-xs text-slate-400 mt-1">
            Sincronizado con el catálogo de C:\Users\yo\catalogo. Comisión del <strong>1% (tienda)</strong> o <strong>3% (link compartido)</strong>.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
          <input 
            type="file" 
            ref="catalogFileInput" 
            accept=".json" 
            @change="handleImportPhysicalCatalog" 
            class="hidden"
          >
          <button 
            @click="triggerPhysicalCatalogUpload" 
            class="bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-xs px-3.5 py-2 rounded-xl shadow transition-all flex items-center gap-1.5"
            title="Seleccionar el archivo products.json del otro proyecto para actualizar el catálogo al instante"
          >
            <span>📥</span>
            <span>Importar products.json</span>
          </button>

          <input 
            v-model="physicalOrderSearch" 
            type="text" 
            placeholder="Buscar por ID, cliente, teléfono..." 
            class="bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white outline-none focus:border-amber-400 flex-1 md:w-52"
          >
          <select 
            v-model="physicalOrderStatusFilter" 
            class="bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white outline-none focus:border-amber-400"
          >
            <option value="All">Todos los Estados ({{ adminStore.physicalOrders.length }})</option>
            <option value="Pendiente">Pendientes ({{ adminStore.pendingPhysicalOrders.length }})</option>
            <option value="Recibido">Recibidos ({{ adminStore.receivedPhysicalOrders.length }})</option>
            <option value="Confirmado">Confirmados ({{ adminStore.confirmedPhysicalOrders.length }})</option>
            <option value="Cancelado">Cancelados ({{ adminStore.cancelledPhysicalOrders.length }})</option>
          </select>
        </div>
      </div>

      <!-- BANNER DE SINCRONIZACIÓN CON EL OTRO PROYECTO -->
      <div class="bg-gradient-to-r from-slate-950 via-amber-950/20 to-slate-950 border border-amber-500/30 rounded-2xl p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs">
        <div class="flex items-center gap-3">
          <span class="text-xl">🔄</span>
          <div>
            <strong class="text-white block font-bold">¿Cómo actualizar este catálogo desde tu otro proyecto?</strong>
            <p class="text-slate-400 text-[11px]">
              Ejecuta <code class="bg-slate-900 text-amber-300 px-1.5 py-0.5 rounded font-mono">npm run sync:catalogo</code> en la terminal, o pulsa <strong>Importar products.json</strong> arriba y selecciona el archivo generado en <code class="text-slate-300 font-mono">C:\Users\yo\catalogo\products.json</code>.
            </p>
          </div>
        </div>
        <div class="flex items-center gap-2 font-mono text-[11px] text-slate-400">
          <span class="bg-slate-900 border border-slate-800 px-2.5 py-1 rounded-lg">Categorías: {{ adminStore.physicalCategories.length }}</span>
        </div>
      </div>

      <div v-if="filteredPhysicalOrdersForAdmin.length === 0" class="text-slate-500 text-center py-12 bg-slate-900/30 rounded-2xl border border-slate-800 border-dashed text-xs">
        No se encontraron pedidos de productos físicos con los filtros actuales.
      </div>

      <div v-else class="space-y-4">
        <div 
          v-for="order in filteredPhysicalOrdersForAdmin" 
          :key="order.id" 
          class="bg-[#020617] rounded-2xl border border-slate-800 p-6 space-y-4 shadow-xl"
        >
          <div class="flex flex-wrap justify-between items-start gap-4 border-b border-slate-800/80 pb-4">
            <div>
              <div class="flex items-center gap-2">
                <span class="text-xs font-mono font-black text-amber-400 bg-amber-400/10 px-2 py-0.5 rounded border border-amber-400/30">
                  {{ order.id }}
                </span>
                <h4 class="font-bold text-white text-base">{{ order.customerName }} ({{ order.customerNumber }})</h4>
                <span class="text-xs text-slate-400 font-mono">{{ order.createdAt }}</span>
              </div>
              <div class="flex flex-wrap items-center gap-3 mt-1.5 text-xs text-slate-400">
                <span>WhatsApp: <strong class="text-slate-200">{{ order.customerPhone }}</strong></span>
                <span>•</span>
                <span>Revendedor: <strong class="text-sky-300 font-mono">[{{ order.resellerId }}] {{ order.resellerName }}</strong></span>
                <span>•</span>
                <span :class="order.source === 'shared_url' ? 'text-emerald-400 font-bold' : 'text-blue-300'">
                  Origen: {{ order.source === 'shared_url' ? '🔗 Link Compartido (3% Comisión)' : '🛍️ Tienda General (1% Comisión)' }}
                </span>
              </div>
            </div>

            <div class="flex items-center gap-3">
              <span :class="[
                'text-xs font-black px-3 py-1 rounded-full border flex items-center gap-1.5',
                (order.status === 'Confirmado' || order.status === 'Aprobado') ? 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30' :
                order.status === 'Recibido' ? 'bg-sky-500/20 text-sky-400 border-sky-500/30 animate-pulse' :
                order.status === 'Cancelado' ? 'bg-red-500/20 text-red-400 border-red-500/30' :
                'bg-amber-500/20 text-amber-400 border-amber-500/30 animate-pulse'
              ]">
                <span :class="[
                  'w-1.5 h-1.5 rounded-full',
                  (order.status === 'Confirmado' || order.status === 'Aprobado') ? 'bg-emerald-400' :
                  order.status === 'Recibido' ? 'bg-sky-400' :
                  order.status === 'Cancelado' ? 'bg-red-400' :
                  'bg-amber-400'
                ]"></span>
                {{ (order.status === 'Confirmado' || order.status === 'Aprobado') ? 'Confirmado' : order.status }}
              </span>

              <!-- Acciones de Administrador -->
              <div class="flex items-center gap-2">
                <!-- Si está Pendiente: Botón Marcar como Recibido -->
                <button 
                  v-if="order.status === 'Pendiente'"
                  @click="handleMarkPhysicalOrderReceived(order)"
                  class="bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs px-3 py-2 rounded-xl transition-all shadow flex items-center gap-1.5"
                  title="Marcar como Recibido para que vendedor oficial contacte al comprador"
                >
                  <span>📞 Marcar como Recibido</span>
                </button>

                <!-- Si está Pendiente o Recibido: Botón Confirmar Compra -->
                <button 
                  v-if="order.status === 'Pendiente' || order.status === 'Recibido'"
                  @click="handleConfirmPhysicalOrder(order)"
                  class="bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs px-4 py-2 rounded-xl transition-all shadow flex items-center gap-1.5"
                  title="Confirmar compra y acreditar comisión a billetera del revendedor"
                >
                  <span>✓ Confirmar Compra</span>
                </button>

                <!-- Cancelar si no está finalizado -->
                <button 
                  v-if="order.status === 'Pendiente' || order.status === 'Recibido'"
                  @click="handleCancelPhysicalOrder(order)"
                  class="bg-red-600/20 hover:bg-red-600 text-red-400 hover:text-white font-bold text-xs px-3 py-2 rounded-xl border border-red-500/30 transition-all flex items-center gap-1"
                  title="Cancelar pedido físico"
                >
                  <span>✕ Cancelar</span>
                </button>
              </div>
            </div>
          </div>

          <!-- Mensaje de Asesoría Directa Oficial -->
          <div v-if="order.status === 'Pendiente'" class="bg-amber-500/10 border border-amber-500/20 p-3 rounded-xl text-xs text-amber-300 flex items-center gap-2">
            <span>⏳</span>
            <span><strong>Estado PENDIENTE:</strong> El cliente completó sus datos pero aún NO transfirió dinero. Al marcar como <strong>Recibido</strong>, un vendedor oficial se comunicará por WhatsApp para confirmar stock disponible y datos para el pago y envío.</span>
          </div>
          <div v-else-if="order.status === 'Recibido'" class="bg-sky-500/10 border border-sky-500/20 p-3 rounded-xl text-xs text-sky-300 flex items-center gap-2">
            <span>📞</span>
            <span><strong>Estado RECIBIDO:</strong> Un vendedor oficial de la tienda física se comunicará para continuar con el pedido y asesorarlo. Al momento de concretar la compra, haz clic en <strong>Confirmar Compra</strong> para acreditar la comisión en la billetera del revendedor.</span>
          </div>
          <div v-else-if="order.status === 'Confirmado' || order.status === 'Aprobado'" class="bg-emerald-500/10 border border-emerald-500/20 p-3 rounded-xl text-xs text-emerald-300 flex items-center gap-2">
            <span>✅</span>
            <span><strong>Compra CONFIRMADA:</strong> El Administrador confirmó la compra. Comisión de +${{ order.commissionAmount.toLocaleString() }} ARS acreditada como saldo a favor en la billetera de {{ order.resellerName }}.</span>
          </div>

          <!-- Items del Pedido Físico -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            <div v-for="(pItem, idx) in order.items" :key="idx" class="bg-slate-900/80 p-3 rounded-xl border border-slate-800 flex items-center gap-3">
              <img v-if="pItem.image" :src="pItem.image" :alt="pItem.name" class="w-12 h-12 object-contain bg-white rounded-lg p-1 shrink-0">
              <div class="flex-1 min-w-0 text-xs">
                <h5 class="font-bold text-white truncate">{{ pItem.name }}</h5>
                <div class="text-slate-400 mt-0.5">
                  Cantidad: <strong class="text-slate-200">{{ pItem.quantity }} un.</strong> · Precio: <span class="text-amber-400 font-bold">${{ pItem.priceNumeric ? pItem.priceNumeric.toLocaleString() : pItem.price }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Dirección de Envío y Liquidación de Comisión -->
          <div class="bg-slate-950 p-4 rounded-xl border border-slate-800/80 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 text-xs">
            <div>
              <span class="text-slate-400 uppercase font-semibold text-[11px] block">Dirección de Entrega:</span>
              <span class="text-white font-medium">{{ order.shippingAddress }}</span>
            </div>

            <div class="flex flex-wrap items-center gap-6 sm:text-right">
              <div>
                <span class="text-slate-400 uppercase font-semibold text-[11px] block">Total Pedido:</span>
                <span class="text-lg font-black text-white">${{ order.totalAmount.toLocaleString() }} ARS</span>
              </div>
              <div class="bg-amber-400/10 px-3.5 py-1.5 rounded-xl border border-amber-400/20">
                <span class="text-amber-400 uppercase font-bold text-[10px] block">Comisión Revendedor ({{ (order.commissionRate * 100) }}%):</span>
                <span class="text-lg font-black text-emerald-400">+${{ order.commissionAmount.toLocaleString() }} ARS</span>
              </div>
            </div>
          </div>

          <div v-if="order.status === 'Cancelado' && order.cancelReason" class="bg-red-500/10 border border-red-500/20 p-3 rounded-xl text-xs text-red-300">
            <strong>Motivo de Cancelación:</strong> {{ order.cancelReason }}
          </div>
        </div>
      </div>
    </div>

    <!-- GESTIÓN Y ARMADOR DE PAQUETES & OFERTAS (PROVEEDOR) -->
    <div v-if="adminTab === 'packages'" class="bg-slate-900/40 rounded-3xl border border-slate-800 p-8 backdrop-blur-md space-y-8">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pb-6 border-b border-slate-800">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white text-2xl shadow-lg shadow-purple-500/20">
            🎁
          </div>
          <div>
            <h2 class="text-2xl font-black text-white">Armador de Paquetes & Ofertas Promocionales</h2>
            <p class="text-xs text-slate-400">Crea combos oficiales combinando plataformas, define precios promocionales y publica ofertas en la tienda y para distribuidores.</p>
          </div>
        </div>
        <div class="bg-purple-500/10 border border-purple-500/30 px-4 py-2 rounded-2xl text-xs font-mono text-purple-300">
          Total Combos Activos: <strong class="text-white">{{ adminStore.packages.filter(p => p.active).length }}</strong> / {{ adminStore.packages.length }}
        </div>
      </div>

      <!-- FORMULARIO CREAR PAQUETE -->
      <form @submit.prevent="submitCreatePackage" class="bg-[#020617] p-6 sm:p-8 rounded-2xl border border-slate-800 space-y-6">
        <div class="flex justify-between items-center border-b border-slate-800/80 pb-3">
          <h3 class="text-base font-black text-white flex items-center gap-2">
            <span>✨</span>
            <span>Nuevo Combo Mayorista Oficial</span>
          </h3>
          <span class="text-xs text-slate-400">Configura servicios y precios</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <div class="lg:col-span-2">
            <label class="block text-xs font-bold text-slate-400 uppercase mb-1.5">Título del Combo</label>
            <input 
              v-model="newPackageForm.title" 
              type="text" 
              placeholder="ej. Pack Familiar Total 4K (Netflix + Disney+ + Spotify)" 
              required
              class="w-full bg-slate-900 border border-slate-800 focus:border-purple-400 rounded-xl p-3 text-white outline-none text-sm font-semibold"
            >
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-400 uppercase mb-1.5">Badge / Etiqueta Promocional</label>
            <input 
              v-model="newPackageForm.badge" 
              type="text" 
              placeholder="ej. 🔥 25% OFF, ⚡ COMBO POPULAR, 👑 VIP" 
              required
              class="w-full bg-slate-900 border border-slate-800 focus:border-purple-400 rounded-xl p-3 text-white outline-none text-sm font-semibold"
            >
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-400 uppercase mb-1.5">Descripción para Clientes</label>
          <input 
            v-model="newPackageForm.description" 
            type="text" 
            placeholder="ej. Disfruta de las mejores plataformas con calidad Ultra HD y PINs independientes con soporte 24/7." 
            class="w-full bg-slate-900 border border-slate-800 focus:border-purple-400 rounded-xl p-3 text-white outline-none text-sm"
          >
        </div>

        <!-- SELECTOR DE SERVICIOS INCLUIDOS EN EL PAQUETE -->
        <div class="space-y-3 bg-slate-900/60 p-5 rounded-2xl border border-slate-800">
          <div class="flex justify-between items-center">
            <span class="text-xs font-bold text-slate-300 uppercase tracking-wider">Servicios y Cuentas a Incluir en el Combo:</span>
            <button 
              type="button" 
              @click="addServiceToPackage" 
              class="bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs px-3 py-1.5 rounded-xl transition-all flex items-center gap-1.5 shadow"
            >
              <span>+ Agregar Otra Cuenta</span>
            </button>
          </div>

          <div class="space-y-2">
            <div 
              v-for="(srv, sIdx) in newPackageForm.selectedServices" 
              :key="sIdx"
              class="flex flex-wrap items-center gap-3 bg-slate-950 p-3 rounded-xl border border-slate-800"
            >
              <div class="w-8 h-8 rounded-lg bg-slate-900 border border-slate-800 flex items-center justify-center text-sm font-mono text-purple-400 shrink-0">
                #{{ sIdx + 1 }}
              </div>

              <!-- Selector de Plataforma -->
              <div class="flex-1 min-w-[140px]">
                <label class="text-[10px] text-slate-400 uppercase block mb-0.5">Plataforma</label>
                <select 
                  v-model="srv.platform" 
                  @change="srv.planKey = Object.keys(adminStore.pricingCatalog[srv.platform]?.plans || {})[0] || '1_perfil'"
                  class="w-full bg-slate-900 border border-slate-800 rounded-lg p-2 text-xs text-white outline-none font-semibold"
                >
                  <option v-for="(cat, pKey) in adminStore.pricingCatalog" :key="pKey" :value="pKey">
                    {{ pKey }} ({{ cat.name }})
                  </option>
                </select>
              </div>

              <!-- Selector de Plan de la Plataforma -->
              <div class="flex-1 min-w-[180px]">
                <label class="text-[10px] text-slate-400 uppercase block mb-0.5">Plan / Modalidad</label>
                <select 
                  v-model="srv.planKey" 
                  class="w-full bg-slate-900 border border-slate-800 rounded-lg p-2 text-xs text-white outline-none font-semibold"
                >
                  <option 
                    v-for="(pl, planKey) in adminStore.pricingCatalog[srv.platform]?.plans" 
                    :key="planKey" 
                    :value="planKey"
                  >
                    {{ pl.label }} - Costo: ${{ pl.providerCost }} / Venta: ${{ pl.clientPrice }}
                  </option>
                </select>
              </div>

              <!-- Costo y Precio del ítem -->
              <div class="text-right shrink-0 px-2">
                <span class="text-[10px] text-slate-400 block">Sugerido:</span>
                <span class="text-xs font-mono font-bold text-white">
                  ${{ (adminStore.pricingCatalog[srv.platform]?.plans[srv.planKey]?.clientPrice || 0).toLocaleString() }}
                </span>
              </div>

              <!-- Botón Eliminar si hay más de 1 servicio -->
              <button 
                v-if="newPackageForm.selectedServices.length > 1"
                type="button" 
                @click="removeServiceFromPackage(sIdx)"
                class="text-red-400 hover:text-red-300 p-1.5 hover:bg-red-500/10 rounded-lg text-xs"
                title="Quitar de combo"
              >
                ✕
              </button>
            </div>
          </div>
        </div>

        <!-- CÁLCULO EN VIVO Y PRECIO PROMO -->
        <div class="bg-gradient-to-r from-slate-950 via-purple-950/30 to-slate-950 p-5 rounded-2xl border border-purple-500/20 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-center">
          <div>
            <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Costo Proveedor Base:</span>
            <span class="text-lg font-black text-white font-mono">${{ packageCalculation.totalCost.toLocaleString() }} ARS</span>
          </div>

          <div>
            <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Precio Regular Sumado:</span>
            <span class="text-lg font-black text-slate-400 line-through font-mono">${{ packageCalculation.totalRegular.toLocaleString() }} ARS</span>
          </div>

          <div>
            <label class="text-[10px] uppercase font-bold text-purple-300 block tracking-wider mb-1">Precio Oferta Promo (ARS):</label>
            <input 
              v-model="newPackageForm.promoPrice" 
              type="number" 
              step="100" 
              required
              class="w-full bg-slate-900 border border-purple-500/50 rounded-xl p-2.5 text-base font-black text-pink-400 font-mono outline-none"
            >
          </div>

          <div class="bg-purple-500/15 p-3 rounded-xl border border-purple-500/30 text-center">
            <span class="text-[10px] text-purple-300 uppercase font-bold block">Descuento Cliente:</span>
            <span class="text-lg font-black text-emerald-400">{{ packageCalculation.discount }}% OFF</span>
            <span class="text-[10px] text-slate-400 block font-mono">Margen: +${{ packageCalculation.profit.toLocaleString() }} ARS</span>
          </div>
        </div>

        <div class="flex justify-end pt-2">
          <button 
            type="submit" 
            class="bg-gradient-to-r from-purple-500 via-pink-500 to-rose-500 hover:from-purple-400 hover:to-rose-400 text-white px-8 py-3.5 font-black rounded-xl transition-all shadow-lg shadow-purple-500/25 text-sm flex items-center gap-2"
          >
            <span>🚀 Publicar Paquete Oficial de Ofertas</span>
          </button>
        </div>
      </form>

      <!-- LISTA DE PAQUETES ACTIVOS Y GESTIÓN -->
      <div class="space-y-4">
        <div class="flex justify-between items-center border-b border-slate-800 pb-3">
          <h3 class="text-lg font-black text-white">Catálogo de Paquetes y Promociones Creadas</h3>
          <span class="text-xs text-slate-400 font-mono">{{ adminStore.packages.length }} paquete(s) registrados</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div 
            v-for="pkg in adminStore.packages" 
            :key="pkg.id"
            :class="[
              'rounded-2xl border p-5 flex flex-col justify-between space-y-4 transition-all',
              pkg.active ? 'bg-[#020617] border-purple-500/30 shadow-lg' : 'bg-slate-950/60 border-slate-800 opacity-60'
            ]"
          >
            <div class="space-y-3">
              <div class="flex justify-between items-start gap-2">
                <span class="text-[10px] font-black uppercase tracking-wider bg-purple-500/20 text-purple-300 border border-purple-500/30 px-2.5 py-0.5 rounded-full">
                  {{ pkg.badge }}
                </span>
                <span :class="[
                  'text-[10px] font-bold px-2 py-0.5 rounded border font-mono',
                  pkg.createdBy === 'provider' ? 'bg-sky-500/10 text-sky-400 border-sky-500/20' : 'bg-amber-500/10 text-amber-400 border-amber-500/20'
                ]">
                  {{ pkg.createdBy === 'provider' ? 'Oficial Proveedor' : `Revendedor: ${pkg.resellerId}` }}
                </span>
              </div>

              <div>
                <h4 class="font-bold text-white text-base">{{ pkg.title }}</h4>
                <p class="text-xs text-slate-400 mt-1 line-clamp-2">{{ pkg.description }}</p>
              </div>

              <!-- Servicios incluidos -->
              <div class="bg-slate-900/60 p-3 rounded-xl border border-slate-800/80 space-y-1.5">
                <span class="text-[10px] uppercase font-bold text-slate-400 block">Cuentas Incluidas:</span>
                <div v-for="(s, sIdx) in pkg.services" :key="sIdx" class="flex justify-between items-center text-xs">
                  <span class="text-slate-200 font-semibold">{{ s.platform }} ({{ s.planLabel }})</span>
                  <span class="text-[10px] text-slate-400 font-mono">${{ s.regularPrice.toLocaleString() }}</span>
                </div>
              </div>

              <!-- Precio -->
              <div class="flex justify-between items-end border-t border-slate-800 pt-3">
                <div>
                  <span class="text-[10px] text-slate-400 line-through block font-mono">${{ pkg.originalPrice.toLocaleString() }} ARS</span>
                  <span class="text-xl font-black text-pink-400 font-mono">${{ pkg.promoPrice.toLocaleString() }} ARS</span>
                </div>
                <div class="text-right">
                  <span class="text-[10px] text-slate-400 block">Costo Mayorista:</span>
                  <span class="text-xs font-mono text-slate-300">${{ pkg.providerCost.toLocaleString() }} ARS</span>
                </div>
              </div>
            </div>

            <!-- Acciones -->
            <div class="flex items-center gap-2 pt-2 border-t border-slate-800">
              <button 
                @click="togglePackageState(pkg.id)" 
                :class="[
                  'flex-1 text-xs font-bold py-2 px-3 rounded-xl transition-all border',
                  pkg.active ? 'bg-amber-500/10 text-amber-300 border-amber-500/30 hover:bg-amber-500/20' : 'bg-emerald-500/10 text-emerald-300 border-emerald-500/30 hover:bg-emerald-500/20'
                ]"
              >
                {{ pkg.active ? '⏸ Pausar' : '▶ Activar' }}
              </button>

              <button 
                @click="deletePackageItem(pkg.id)" 
                class="text-red-400 hover:text-red-300 hover:bg-red-500/10 p-2 rounded-xl border border-red-500/20 text-xs font-bold transition-all"
                title="Eliminar Paquete"
              >
                🗑️
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Gestión de Inventario Mayorista: Con opción de Cambiar Contraseña Privada (Solo Proveedor) -->
    <div v-if="adminTab === 'inventory'" class="bg-slate-900/40 rounded-3xl border border-slate-800 p-8 backdrop-blur-md">
      <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-800">
        <div class="w-10 h-10 rounded-xl bg-brand-accent/10 flex items-center justify-center text-brand-accent border border-brand-accent/20">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8-4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg>
        </div>
        <div>
          <h2 class="text-2xl font-black">Inventario Mayorista & Contraseñas Maestras</h2>
          <p class="text-xs text-slate-400">Los cambios de contraseña aquí solo son vistos por ti (Proveedor) y no por el revendedor hasta una nueva compra o reemplazo.</p>
        </div>
      </div>

      <form @submit.prevent="handleAddStock" class="bg-[#020617] p-6 rounded-2xl border border-slate-800 space-y-4 mb-8">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
          <div>
            <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Plataforma</label>
            <select v-model="newStock.platform" class="w-full bg-slate-900 border border-slate-800 focus:border-brand-accent rounded-xl p-3 text-white font-semibold outline-none text-sm">
              <option>Netflix</option>
              <option>Max</option>
              <option>Spotify</option>
              <option>Disney+</option>
              <option>IPTV</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Correo Máster</label>
            <input v-model="newStock.account" type="email" placeholder="master@cuenta.com" class="w-full bg-slate-900 border border-slate-800 focus:border-brand-accent rounded-xl p-3 text-white outline-none text-sm" required>
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Contraseña Máster</label>
            <input v-model="newStock.pass" type="text" placeholder="Pass123!#" class="w-full bg-slate-900 border border-slate-800 focus:border-brand-accent rounded-xl p-3 text-white outline-none text-sm" required>
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Capacidad Pantallas</label>
            <select :value="newStock.maxProfiles" @change="updateMaxProfiles($event.target.value)" class="w-full bg-slate-900 border border-slate-800 focus:border-brand-accent rounded-xl p-3 text-white outline-none text-sm font-semibold">
              <option :value="1">1 Pantalla</option>
              <option :value="2">2 Pantallas</option>
              <option :value="3">3 Pantallas</option>
              <option :value="4">4 Pantallas</option>
              <option :value="6">6 Pantallas</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Costo Lote (ARS)</label>
            <input v-model="newStock.cost" type="number" placeholder="5500" class="w-full bg-slate-900 border border-slate-800 focus:border-brand-accent rounded-xl p-3 text-white outline-none font-mono text-sm" required>
          </div>
        </div>

        <div class="flex justify-end pt-2">
          <button type="submit" class="bg-brand-accent text-[#020617] hover:bg-sky-400 px-8 py-3 font-black rounded-xl transition-all shadow-[0_0_15px_rgba(56,189,248,0.3)] text-sm">
            Cargar al Inventario Mayorista
          </button>
        </div>
      </form>
      
      <!-- Tabla de Lotes con Botón de Modificar Contraseña Privada -->
      <div v-if="adminStore.inventory.length === 0" class="text-center py-12 px-4 bg-[#020617] rounded-2xl border border-slate-800 border-dashed space-y-3">
        <span class="text-4xl block">📦</span>
        <h4 class="text-base font-bold text-white">Inventario Mayorista Vacío (Listo para Producción)</h4>
        <p class="text-xs text-slate-400 max-w-md mx-auto">
          No hay cuentas de prueba cargadas. Utiliza el formulario superior para registrar tus cuentas de streaming reales con sus perfiles y PINs para comenzar a despachar.
        </p>
      </div>

      <div v-else class="overflow-x-auto rounded-2xl border border-slate-800 bg-[#020617]">
        <table class="w-full text-left text-sm whitespace-nowrap">
          <thead class="bg-slate-950 text-slate-400 text-xs uppercase tracking-wider font-bold border-b border-slate-800">
            <tr>
              <th class="px-6 py-4">ID Lote</th>
              <th class="px-6 py-4">Plataforma</th>
              <th class="px-6 py-4">Cuenta & Clave Máster (Privada)</th>
              <th class="px-6 py-4">Pantallas Ocupadas / Libres</th>
              <th class="px-6 py-4">Acción Proveedor</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800">
            <tr v-for="item in adminStore.inventory" :key="item.id" class="hover:bg-slate-800/40 transition-colors">
              <td class="px-6 py-4 font-mono text-xs text-brand-accent">{{ item.id }}</td>
              <td class="px-6 py-4 font-black">{{ item.platform }}</td>
              <td class="px-6 py-4">
                <div class="font-mono text-xs text-slate-200">{{ item.account }}</div>
                <div class="font-mono text-[11px] text-amber-400">Pass: {{ item.pass }} <span class="text-slate-500 font-sans">(Solo tú la ves)</span></div>
              </td>
              <td class="px-6 py-4">
                <span class="text-xs font-mono font-bold bg-slate-900 px-2 py-1 rounded border border-slate-800">
                  {{ item.profiles.filter(p => !p.isAssigned).length }} / {{ item.profiles.length }} libres
                </span>
              </td>
              <td class="px-6 py-4 flex items-center gap-2">
                <button 
                  @click="openPasswordModal(item)" 
                  class="bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white px-3 py-1.5 rounded-lg text-xs font-bold border border-slate-700 transition-colors"
                >
                  🔒 Clave Privada
                </button>
                <button 
                  @click="handleDeleteInventory(item.id)" 
                  class="bg-red-500/10 hover:bg-red-500/30 text-red-400 border border-red-500/20 px-2.5 py-1.5 rounded-lg text-xs font-bold transition-colors"
                  title="Eliminar lote del inventario"
                >
                  🗑️
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- MODAL DE CAMBIO PRIVADO DE CONTRASEÑA -->
    <div v-if="showPasswordModal && selectedInvItem" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-sm animate-in fade-in duration-200">
      <div class="bg-slate-900 border border-slate-700 w-full max-w-md rounded-2xl p-6 shadow-2xl relative space-y-4">
        <div class="flex justify-between items-start border-b border-slate-800 pb-3">
          <div>
            <h3 class="text-xl font-black text-white">Cambiar Contraseña Maestra</h3>
            <p class="text-xs text-slate-400">{{ selectedInvItem.account }}</p>
          </div>
          <button @click="showPasswordModal = false" class="text-slate-400 hover:text-white p-1">✕</button>
        </div>

        <div class="p-3 bg-amber-950/40 border border-amber-500/30 rounded-xl text-xs text-amber-300">
          ℹ️ Esta contraseña solo es vista por ti como Proveedor. No será visible para el Revendedor hasta que se concrete una nueva compra o reemplazo de cuenta, salvo que actives la actualización para clientes activos.
        </div>

        <div>
          <label class="block text-xs font-bold uppercase text-slate-400 mb-1">Nueva Contraseña Maestra</label>
          <input v-model="updatedPassword" type="text" class="w-full bg-slate-950 border border-slate-800 focus:border-brand-accent rounded-xl p-3 text-white font-mono text-sm outline-none" required>
        </div>

        <!-- Enviar Actualización a Clientes con Suscripciones Activas -->
        <div class="bg-slate-950 p-3.5 rounded-xl border border-slate-800 space-y-2">
          <label class="flex items-start gap-2.5 cursor-pointer">
            <input v-model="notifyActiveClients" type="checkbox" class="mt-0.5 rounded border-slate-700 text-brand-accent focus:ring-0">
            <div class="text-xs">
              <span class="font-bold text-white block">Enviar actualización a Clientes con Suscripción ACTIVA</span>
              <span class="text-slate-400 text-[11px] block mt-0.5">
                Cuentas por vencer sin solicitud o no cerradas se excluyen automáticamente. Se alertará al Revendedor con [YA FUE AVISADO].
              </span>
            </div>
          </label>
          
          <div v-if="notifyActiveClients" class="pt-2">
            <label class="block text-[11px] font-bold uppercase text-slate-400 mb-1">Motivo del Cambio</label>
            <input v-model="updateReason" type="text" placeholder="Ej: Mantenimiento preventivo oficial" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2 text-xs text-white outline-none">
          </div>
        </div>

        <div class="flex gap-3 pt-2">
          <button type="button" @click="showPasswordModal = false" class="w-1/3 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold py-2.5 rounded-xl text-xs">
            Cancelar
          </button>
          <button type="button" @click="confirmPasswordUpdate" class="w-2/3 bg-brand-accent text-slate-950 font-black py-2.5 rounded-xl text-xs hover:bg-sky-400 transition-all shadow">
            {{ notifyActiveClients ? 'Actualizar y Notificar Activas' : 'Guardar Privadamente' }}
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL DE MODIFICACIÓN DE RENOVACIÓN POR EL PROVEEDOR -->
    <div v-if="showRenewalEditModal && selectedRenewal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-sm animate-in fade-in duration-200">
      <div class="bg-slate-900 border border-slate-700 w-full max-w-lg rounded-2xl p-6 shadow-2xl relative space-y-4 text-xs">
        <div class="flex justify-between items-start border-b border-slate-800 pb-3">
          <div>
            <div class="flex items-center gap-2">
              <h3 class="text-xl font-black text-white">Gestionar Renovación de Servicio</h3>
              <span class="text-xs font-mono font-bold bg-brand-accent/20 text-brand-accent px-2 py-0.5 rounded">{{ selectedRenewal.clientNumber }}</span>
            </div>
            <p class="text-slate-400 mt-0.5">{{ selectedRenewal.clientName }} • {{ selectedRenewal.platform }} (Revendedor: <strong class="text-amber-400">{{ selectedRenewal.resellerId }}</strong>)</p>
          </div>
          <button @click="showRenewalEditModal = false" class="text-slate-400 hover:text-white p-1">✕</button>
        </div>

        <!-- Selector de Modo de Renovación -->
        <div class="grid grid-cols-2 gap-2 p-1 bg-slate-950 rounded-xl border border-slate-800">
          <button 
            type="button"
            @click="renewalMode = 'same'"
            :class="['py-2.5 px-3 rounded-lg font-bold text-xs transition-all flex items-center justify-center gap-2', renewalMode === 'same' ? 'bg-emerald-600 text-white shadow font-black' : 'text-slate-400 hover:text-white hover:bg-slate-900']"
          >
            <span>⚡ Renovar Misma Cuenta</span>
          </button>
          <button 
            type="button"
            @click="renewalMode = 'edit'"
            :class="['py-2.5 px-3 rounded-lg font-bold text-xs transition-all flex items-center justify-center gap-2', renewalMode === 'edit' ? 'bg-blue-600 text-white shadow font-black' : 'text-slate-400 hover:text-white hover:bg-slate-900']"
          >
            <span>✏️ Asignar Nuevos Datos</span>
          </button>
        </div>

        <!-- MODO 1: RENOVAR MISMA CUENTA -->
        <div v-if="renewalMode === 'same'" class="space-y-3 bg-slate-950/70 p-4 rounded-xl border border-slate-800">
          <div class="p-3 bg-emerald-950/30 border border-emerald-500/30 rounded-xl text-emerald-300 text-xs">
            ✓ <strong>Mantener accesos actuales:</strong> El cliente seguirá usando su mismo correo y contraseña. Se extenderá su fecha de vencimiento y se alertará al revendedor [YA FUE AVISADO].
          </div>

          <div>
            <label class="block font-bold uppercase text-slate-400 mb-1">Días a Extender</label>
            <div class="grid grid-cols-3 gap-2">
              <button 
                type="button" 
                @click="renewalDaysOption = 30" 
                :class="['py-2 rounded-lg font-bold border text-xs', renewalDaysOption === 30 ? 'bg-emerald-600 border-emerald-500 text-white font-black' : 'bg-slate-900 border-slate-800 text-slate-400']"
              >
                +30 Días (1 Mes)
              </button>
              <button 
                type="button" 
                @click="renewalDaysOption = 60" 
                :class="['py-2 rounded-lg font-bold border text-xs', renewalDaysOption === 60 ? 'bg-emerald-600 border-emerald-500 text-white font-black' : 'bg-slate-900 border-slate-800 text-slate-400']"
              >
                +60 Días (2 Meses)
              </button>
              <button 
                type="button" 
                @click="renewalDaysOption = 90" 
                :class="['py-2 rounded-lg font-bold border text-xs', renewalDaysOption === 90 ? 'bg-emerald-600 border-emerald-500 text-white font-black' : 'bg-slate-900 border-slate-800 text-slate-400']"
              >
                +90 Días (Trimestre)
              </button>
            </div>
          </div>

          <div class="bg-slate-900 p-3 rounded-xl border border-slate-800 font-mono text-[11px] space-y-1">
            <p class="text-slate-400">Cuenta conservada: <strong class="text-white">{{ selectedRenewal.newCredentials?.account }}</strong></p>
            <p class="text-slate-400">Clave actual: <strong class="text-amber-400">{{ selectedRenewal.newCredentials?.pass }}</strong></p>
          </div>
        </div>

        <!-- MODO 2: ASIGNAR NUEVAS CREDENCIALES -->
        <div v-else class="space-y-3 bg-slate-950/70 p-4 rounded-xl border border-slate-800">
          <div class="p-3 bg-blue-950/30 border border-blue-500/30 rounded-xl text-blue-300 text-xs">
            ℹ️ <strong>Reemplazo o Cambio de Credenciales:</strong> Se actualizarán los datos de acceso y se enviará una notificación al revendedor para que confirme la entrega al cliente.
          </div>

          <div>
            <label class="block font-bold uppercase text-slate-400 mb-1">Nuevo Correo de la Cuenta</label>
            <input v-model="renewalFormData.account" type="email" placeholder="ejemplo@streampro.com" class="w-full bg-slate-900 border border-slate-800 focus:border-brand-accent rounded-xl p-2.5 text-white font-mono outline-none" required>
          </div>

          <div>
            <label class="block font-bold uppercase text-slate-400 mb-1">Nueva Contraseña</label>
            <input v-model="renewalFormData.pass" type="text" placeholder="PassSegura2026*" class="w-full bg-slate-900 border border-slate-800 focus:border-brand-accent rounded-xl p-2.5 text-white font-mono outline-none" required>
          </div>

          <!-- Perfiles y PINs -->
          <div>
            <label class="block font-bold uppercase text-slate-400 mb-1">Nombres de Perfiles y PINs Asignados</label>
            <div class="space-y-1.5 max-h-[120px] overflow-y-auto pr-1">
              <div v-for="(prof, pIdx) in renewalFormData.profiles" :key="pIdx" class="flex gap-2 items-center bg-slate-900 p-2 rounded-lg border border-slate-800">
                <input v-model="prof.name" type="text" placeholder="Nombre Perfil" class="flex-1 bg-slate-950 border border-slate-700 rounded px-2 py-1 text-xs text-white outline-none">
                <input v-model="prof.pin" type="text" maxlength="4" placeholder="PIN" class="w-20 bg-slate-950 border border-slate-700 text-amber-300 font-bold font-mono rounded px-2 py-1 text-xs text-center outline-none">
              </div>
            </div>
          </div>
        </div>

        <div class="flex gap-3 pt-2">
          <button type="button" @click="showRenewalEditModal = false" class="w-1/3 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold py-2.5 rounded-xl">
            Cancelar
          </button>
          <button 
            type="button" 
            @click="confirmRenewalUpdateByProvider" 
            :class="['w-2/3 font-black py-2.5 rounded-xl shadow transition-all', renewalMode === 'same' ? 'bg-emerald-500 hover:bg-emerald-400 text-slate-950' : 'bg-blue-600 hover:bg-blue-500 text-white']"
          >
            {{ renewalMode === 'same' ? `Confirmar Extensión (+${renewalDaysOption}d)` : 'Guardar Nuevos Datos y Alertar' }}
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL DE DESPACHO INTERACTIVO -->
    <div v-if="showDispatchModal && selectedOrder" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-sm animate-in fade-in duration-200">
      <div class="bg-slate-900 border border-slate-700 w-full max-w-xl rounded-2xl p-6 shadow-2xl relative space-y-6">
        <div class="flex justify-between items-start border-b border-slate-800 pb-3">
          <div>
            <div class="flex items-center gap-2">
              <h3 class="text-xl font-black text-white">Configurar Entrega de Cuenta</h3>
              <span class="text-xs font-mono font-bold bg-brand-accent/20 text-brand-accent px-2 py-0.5 rounded">{{ selectedOrder.customerNumber }}</span>
            </div>
            <p class="text-xs text-slate-400 mt-0.5">Orden {{ selectedOrder.id }} • {{ selectedOrder.customerName }} ({{ selectedOrder.platform }})</p>
          </div>
          <button @click="showDispatchModal = false" class="text-slate-400 hover:text-white p-1">✕</button>
        </div>

        <div class="space-y-4">
          <div>
            <label class="block text-xs font-bold uppercase text-slate-400 tracking-wider mb-1.5">
              Seleccionar Cuenta del Inventario ({{ availableAccountsForOrder.length }} disponibles)
            </label>
            <div v-if="availableAccountsForOrder.length === 0" class="p-3 bg-red-950/40 border border-red-500/30 rounded-xl text-xs text-red-300">
              ⚠️ No hay cuentas máster con stock libre para {{ selectedOrder.platform }}. Carga una cuenta en el inventario antes de despachar.
            </div>
            <select 
              v-else
              v-model="selectedInventoryId" 
              @change="onInventorySelected"
              class="w-full bg-slate-950 border border-slate-700 focus:border-brand-accent rounded-xl p-3 text-xs text-white font-mono outline-none"
            >
              <option v-for="inv in availableAccountsForOrder" :key="inv.id" :value="inv.id">
                [{{ inv.id }}] {{ inv.account }} • {{ inv.profiles.filter(p => !p.isAssigned).length }} pantallas libres
              </option>
            </select>
          </div>

          <div v-if="deliveryMatches.length > 0">
            <div class="flex justify-between items-center mb-1.5">
              <label class="block text-xs font-bold uppercase text-slate-400 tracking-wider">
                Mapeo de Entrega (Nombre de Perfil ➔ PIN Asignado)
              </label>
              <span class="text-[11px] text-brand-accent font-medium">{{ deliveryMatches.length }} pantalla(s) • {{ selectedOrder.pinPreference === 'same' ? 'Mismo PIN' : 'PINs distintos' }}</span>
            </div>
            
            <div class="space-y-2 bg-slate-950 p-3.5 rounded-xl border border-slate-800 max-h-[220px] overflow-y-auto">
              <div v-for="(match, idx) in deliveryMatches" :key="idx" class="flex gap-2 items-center bg-slate-900 p-2.5 rounded-lg border border-slate-800">
                <span class="text-xs font-bold text-slate-500 w-6 text-center">#{{ idx + 1 }}</span>
                <div class="flex-1">
                  <span class="text-[10px] text-slate-500 uppercase font-bold block">Nombre de Perfil:</span>
                  <input 
                    v-model="match.name" 
                    type="text" 
                    class="w-full bg-slate-950 border border-slate-700 rounded px-2.5 py-1 text-xs text-white font-mono outline-none focus:border-brand-accent"
                  >
                </div>
                <div class="w-32">
                  <span class="text-[10px] text-amber-400 uppercase font-bold block">PIN de Cuenta:</span>
                  <input 
                    v-model="match.pin" 
                    type="text" 
                    maxlength="4"
                    class="w-full bg-slate-950 border border-slate-700 text-amber-300 font-bold font-mono rounded px-2 py-1 text-xs text-center outline-none focus:border-amber-400"
                  >
                </div>
              </div>
            </div>
          </div>

          <div class="bg-slate-950 p-3.5 rounded-xl border border-slate-800 flex justify-between items-center text-xs">
            <div>
              <span class="text-slate-400">Cobro automático al revendedor:</span>
              <p class="text-base font-black text-emerald-400">${{ selectedOrder.providerCost }} ARS</p>
            </div>
            <button 
              @click="confirmDispatch"
              :disabled="!selectedInventoryId"
              class="bg-amber-500 hover:bg-amber-400 disabled:opacity-50 disabled:cursor-not-allowed text-slate-950 font-black px-6 py-2.5 rounded-xl text-xs transition-all shadow-lg"
            >
              Completar Despacho
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL: AVISAR EVENTUALIDAD A REVENDEDOR (ESTADO: YA FUE AVISADO) -->
    <div v-if="showIncidentModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-sm animate-in fade-in duration-200">
      <div class="bg-slate-900 border border-slate-700 w-full max-w-md rounded-2xl p-6 shadow-2xl relative space-y-4 text-xs">
        <div class="flex justify-between items-start border-b border-slate-800 pb-3">
          <div>
            <h3 class="text-xl font-black text-white">Notificar Eventualidad a Revendedor</h3>
            <p class="text-slate-400">Se enviará una alerta prioritaria al panel del revendedor marcada como [YA FUE AVISADO].</p>
          </div>
          <button @click="showIncidentModal = false" class="text-slate-400 hover:text-white p-1">✕</button>
        </div>

        <div>
          <label class="block font-bold uppercase text-slate-400 mb-1">Revendedor Destino</label>
          <select v-model="incidentForm.resellerId" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white font-semibold outline-none">
            <option v-for="res in adminStore.resellers" :key="res.id" :value="res.id">
              [{{ res.id }}] {{ res.name }}
            </option>
          </select>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block font-bold uppercase text-slate-400 mb-1">N° Cliente Afectado</label>
            <input v-model="incidentForm.clientNumber" type="text" placeholder="CLI-XXXX" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white font-mono outline-none">
          </div>
          <div>
            <label class="block font-bold uppercase text-slate-400 mb-1">Nombre Cliente</label>
            <input v-model="incidentForm.clientName" type="text" placeholder="Ej: Juan Perez" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white outline-none">
          </div>
        </div>

        <div>
          <label class="block font-bold uppercase text-slate-400 mb-1">Asunto / Eventualidad</label>
          <input v-model="incidentForm.title" type="text" placeholder="Ej: Reajuste en perfil / Actualización de acceso" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white outline-none" required>
        </div>

        <div>
          <label class="block font-bold uppercase text-slate-400 mb-1">Mensaje Detallado para el Revendedor</label>
          <textarea v-model="incidentForm.message" rows="3" placeholder="Explica la solución aplicada o instrucciones para el cliente..." class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white outline-none resize-none" required></textarea>
        </div>

        <div class="p-3 bg-indigo-950/40 border border-indigo-500/30 rounded-xl text-indigo-300 text-[11px]">
          ℹ️ Esta alerta quedará archivada en el panel del Revendedor con el sello de auditoría <strong>YA FUE AVISADO</strong>.
        </div>

        <div class="flex gap-3 pt-2">
          <button type="button" @click="showIncidentModal = false" class="w-1/3 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold py-2.5 rounded-xl">
            Cancelar
          </button>
          <button type="button" @click="submitIncidentAlert" class="w-2/3 bg-indigo-600 hover:bg-indigo-500 text-white font-black py-2.5 rounded-xl shadow">
            Emitir Alerta [YA FUE AVISADO]
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL: REGISTRAR NUEVO REVENDEDOR -->
    <div v-if="showAddResellerModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-sm animate-in fade-in duration-200">
      <div class="bg-slate-900 border border-slate-700 w-full max-w-lg rounded-2xl p-6 shadow-2xl relative space-y-4 text-xs">
        <div class="flex justify-between items-start border-b border-slate-800 pb-3">
          <div>
            <h3 class="text-xl font-black text-white">Alta de Nuevo Revendedor</h3>
            <p class="text-slate-400">Registra un nuevo distribuidor oficial con sus datos bancarios para transferencias.</p>
          </div>
          <button @click="showAddResellerModal = false" class="text-slate-400 hover:text-white p-1">✕</button>
        </div>

        <form @submit.prevent="handleAddReseller" class="space-y-3">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold uppercase text-slate-400 mb-1">Nombre / Razón Comercial</label>
              <input v-model="newResellerForm.name" type="text" placeholder="Ej: StreamMaster Digital" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white outline-none" required>
            </div>
            <div>
              <label class="block font-bold uppercase text-slate-400 mb-1">WhatsApp de Contacto</label>
              <input v-model="newResellerForm.contactNumber" type="tel" placeholder="+54 9 11 9988-7766" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white outline-none" required>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold uppercase text-slate-400 mb-1">Titular de Cobro</label>
              <input v-model="newResellerForm.holderName" type="text" placeholder="Ej: Mariano Torres" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white outline-none" required>
            </div>
            <div>
              <label class="block font-bold uppercase text-slate-400 mb-1">CUIT / CUIL</label>
              <input v-model="newResellerForm.cuit" type="text" placeholder="20-33445566-7" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white outline-none" required>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold uppercase text-slate-400 mb-1">Banco / Billetera</label>
              <input v-model="newResellerForm.bankName" type="text" placeholder="Ej: Santander / Mercado Pago" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white outline-none" required>
            </div>
            <div>
              <label class="block font-bold uppercase text-slate-400 mb-1">Alias / CBU</label>
              <input v-model="newResellerForm.cbuAlias" type="text" placeholder="STREAM.MARIANO.MP" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white font-mono outline-none" required>
            </div>
          </div>

          <!-- Credenciales Verídicas Generadas para el Distribuidor -->
          <div class="p-3.5 bg-slate-950 rounded-2xl border border-indigo-500/30 space-y-3">
            <div class="flex justify-between items-center">
              <span class="text-xs font-black text-indigo-300 uppercase tracking-wider">🔑 Credenciales de Acceso Oficial</span>
              <button 
                type="button" 
                @click="generateNewResellerPassword" 
                class="text-[11px] font-bold text-amber-300 hover:text-amber-200 bg-amber-500/10 border border-amber-500/30 px-2.5 py-1 rounded-lg transition-colors flex items-center gap-1"
              >
                <span>⚡ Generar Clave Segura</span>
              </button>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block font-bold text-slate-400 text-[11px] mb-1">Usuario de Login</label>
                <input 
                  v-model="newResellerForm.username" 
                  type="text" 
                  placeholder="ej: mariano_stream" 
                  class="w-full bg-slate-900 border border-slate-700 focus:border-indigo-400 rounded-xl p-2.5 text-white font-mono text-xs outline-none" 
                  required
                >
              </div>
              <div>
                <label class="block font-bold text-slate-400 text-[11px] mb-1">Contraseña de Login</label>
                <input 
                  v-model="newResellerForm.password" 
                  type="text" 
                  placeholder="Contraseña segura" 
                  class="w-full bg-slate-900 border border-slate-700 focus:border-indigo-400 rounded-xl p-2.5 text-white font-mono text-xs font-bold outline-none" 
                  required
                >
              </div>
            </div>

            <div class="grid grid-cols-2 gap-3 pt-1">
              <div>
                <label class="block font-bold text-slate-400 text-[11px] mb-1">Saldo Inicial Billetera (ARS)</label>
                <input 
                  v-model="newResellerForm.balance" 
                  type="number" 
                  min="0" 
                  step="500" 
                  class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2 text-emerald-400 font-mono font-bold text-xs outline-none"
                >
              </div>
              <div>
                <label class="block font-bold text-slate-400 text-[11px] mb-1">Nombre Tienda en Checkout</label>
                <input 
                  v-model="newResellerForm.storeName" 
                  type="text" 
                  placeholder="ej: Tienda Digital Play" 
                  class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2 text-white text-xs outline-none"
                >
              </div>
            </div>
          </div>

          <div class="flex gap-3 pt-3">
            <button type="button" @click="showAddResellerModal = false" class="w-1/3 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold py-2.5 rounded-xl">
              Cancelar
            </button>
            <button type="submit" class="w-2/3 bg-brand-accent text-slate-950 font-black py-2.5 rounded-xl hover:bg-sky-400 transition-all shadow">
              Registrar Revendedor
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL: CARGAR BILLETERA DE REVENDEDOR DESDE ADMIN CON CONFIRMACIÓN -->
    <div v-if="showRechargeModal && selectedResellerForRecharge" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-sm animate-in fade-in duration-200">
      <div class="bg-slate-900 border border-slate-700 w-full max-w-md rounded-2xl p-6 shadow-2xl relative space-y-4 text-xs">
        <div class="flex justify-between items-start border-b border-slate-800 pb-3">
          <div>
            <h3 class="text-xl font-black text-white">Cargar Saldo a Billetera Mayorista</h3>
            <p class="text-slate-400">Revendedor: <strong class="text-amber-400">[{{ selectedResellerForRecharge.id }}] {{ selectedResellerForRecharge.name }}</strong></p>
          </div>
          <button @click="showRechargeModal = false" class="text-slate-400 hover:text-white p-1">✕</button>
        </div>

        <div class="bg-slate-950 p-3 rounded-xl border border-slate-800 flex justify-between items-center font-mono">
          <span class="text-slate-400">Saldo actual registrado:</span>
          <span class="text-emerald-400 font-black text-base">${{ selectedResellerForRecharge.balance || 0 }} ARS</span>
        </div>

        <!-- PASO 1: Formulario de Carga y Montos Rápidos -->
        <div v-if="!rechargeConfirmStep" class="space-y-3">
          <div>
            <label class="block font-bold uppercase text-slate-400 mb-1.5">Montos Rápidos Sugeridos</label>
            <div class="grid grid-cols-3 sm:grid-cols-5 gap-1.5">
              <button 
                v-for="qAmount in quickRechargeAmounts" 
                :key="qAmount"
                type="button"
                @click="rechargeForm.amount = qAmount"
                :class="['py-1.5 rounded-lg font-mono font-bold text-[11px] border transition-all', rechargeForm.amount === qAmount ? 'bg-emerald-600 border-emerald-500 text-white font-black' : 'bg-slate-950 border-slate-800 text-slate-300 hover:border-slate-700']"
              >
                +${{ qAmount / 1000 }}k
              </button>
            </div>
          </div>

          <div>
            <label class="block font-bold uppercase text-slate-400 mb-1">Monto a Cargar (ARS)</label>
            <input v-model="rechargeForm.amount" type="number" min="100" step="500" class="w-full bg-slate-950 border border-slate-800 focus:border-emerald-400 rounded-xl p-3 text-white font-mono font-bold text-base outline-none" required>
          </div>

          <div>
            <label class="block font-bold uppercase text-slate-400 mb-1">N° Comprobante / Referencia Bancaria</label>
            <input v-model="rechargeForm.receiptNumber" type="text" placeholder="Ej: TRF-998821 / MP-1123" class="w-full bg-slate-950 border border-slate-800 focus:border-emerald-400 rounded-xl p-2.5 text-white font-mono outline-none" required>
          </div>

          <div>
            <label class="block font-bold uppercase text-slate-400 mb-1">Concepto / Motivo de la Carga</label>
            <select v-model="rechargeForm.reason" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white outline-none">
              <option>Acreditación de transferencia mayorista</option>
              <option>Carga de saldo autorizada por Proveedor</option>
              <option>Reintegro por ajuste contable</option>
              <option>Compensación técnica de servicio</option>
            </select>
          </div>

          <div class="p-3 bg-emerald-950/30 border border-emerald-500/30 rounded-xl text-emerald-300 text-[11px]">
            ℹ️ Al continuar, se verificará la confirmación de pago y se enviará la notificación formal <strong>[YA FUE AVISADO]</strong>.
          </div>

          <div class="flex gap-3 pt-2">
            <button type="button" @click="showRechargeModal = false" class="w-1/3 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold py-2.5 rounded-xl">
              Cancelar
            </button>
            <button type="button" @click="proceedToRechargeConfirm" class="w-2/3 bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black py-2.5 rounded-xl shadow transition-all">
              Revisar y Confirmar Pago
            </button>
          </div>
        </div>

        <!-- PASO 2: Confirmación Explícita de Pago -->
        <div v-else class="space-y-4">
          <div class="p-4 bg-emerald-950/40 border border-emerald-500/40 rounded-2xl text-center space-y-2">
            <span class="text-3xl">💳</span>
            <h4 class="text-base font-black text-white">¿Confirmar Acreditación de Saldo?</h4>
            <p class="text-slate-300 text-xs">
              Se acreditarán <strong class="text-emerald-400 font-mono text-sm">+${{ rechargeForm.amount }} ARS</strong> a la cuenta de <strong class="text-white">{{ selectedResellerForRecharge.name }}</strong>.
            </p>
          </div>

          <div class="bg-slate-950 p-3.5 rounded-xl border border-slate-800 space-y-1.5 font-mono text-xs">
            <div class="flex justify-between">
              <span class="text-slate-400">Comprobante:</span>
              <strong class="text-amber-300">{{ rechargeForm.receiptNumber }}</strong>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-400">Motivo:</span>
              <span class="text-slate-200">{{ rechargeForm.reason }}</span>
            </div>
            <div class="flex justify-between border-t border-slate-800 pt-1.5">
              <span class="text-slate-400">Nuevo Saldo Resultante:</span>
              <strong class="text-emerald-400 text-sm font-black">${{ (selectedResellerForRecharge.balance || 0) + Number(rechargeForm.amount) }} ARS</strong>
            </div>
          </div>

          <div class="flex gap-3 pt-2">
            <button type="button" @click="rechargeConfirmStep = false" class="w-1/3 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold py-2.5 rounded-xl">
              Atrás
            </button>
            <button type="button" @click="confirmRechargeWallet" class="w-2/3 bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black py-2.5 rounded-xl shadow transition-all flex items-center justify-center gap-2">
              <span>✓ Confirmar y Acreditar</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL: GESTIONAR ACCESO Y CREDENCIALES DE DISTRIBUIDOR -->
    <div v-if="showResellerAuthModal && selectedResellerForAuth" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-sm animate-in fade-in duration-200">
      <div class="bg-slate-900 border border-slate-700 w-full max-w-md rounded-3xl p-6 shadow-2xl relative space-y-5 text-xs">
        <div class="flex justify-between items-start border-b border-slate-800 pb-3">
          <div>
            <div class="flex items-center gap-2">
              <span class="text-xs font-mono font-bold bg-amber-500/15 text-amber-400 px-2 py-0.5 rounded border border-amber-500/30">
                [{{ selectedResellerForAuth.id }}]
              </span>
              <h3 class="text-lg font-black text-white">Credenciales y Acceso</h3>
            </div>
            <p class="text-slate-400 mt-0.5">Revendedor: <strong class="text-white">{{ selectedResellerForAuth.name }}</strong></p>
          </div>
          <button @click="showResellerAuthModal = false" class="text-slate-400 hover:text-white p-1">✕</button>
        </div>

        <form @submit.prevent="saveResellerAuth" class="space-y-4">
          <!-- Toggle Estado de Activación -->
          <div class="p-3.5 bg-slate-950 rounded-2xl border border-slate-800 flex justify-between items-center">
            <div>
              <span class="font-bold text-white block">Estado de la Cuenta</span>
              <span class="text-[11px] text-slate-400">Si está desactivado, no podrá iniciar sesión en /crm</span>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input v-model="resellerAuthForm.isActive" type="checkbox" class="sr-only peer">
              <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
            </label>
          </div>

          <div>
            <label class="block font-bold uppercase text-slate-400 mb-1">Usuario de Acceso</label>
            <input 
              v-model="resellerAuthForm.username" 
              type="text" 
              class="w-full bg-slate-950 border border-slate-800 focus:border-brand-accent rounded-xl p-3 text-white font-mono font-bold outline-none" 
              required
            >
          </div>

          <div>
            <div class="flex justify-between items-center mb-1">
              <label class="block font-bold uppercase text-slate-400 text-xs">Contraseña de Acceso</label>
              <button 
                type="button" 
                @click="resellerAuthForm.password = generateSecurePassword(10)" 
                class="text-[10px] text-amber-300 font-bold bg-amber-500/10 border border-amber-500/20 px-2 py-0.5 rounded hover:bg-amber-500/20 transition-colors"
              >
                ⚡ Generar Clave Segura
              </button>
            </div>
            <input 
              v-model="resellerAuthForm.password" 
              type="text" 
              class="w-full bg-slate-950 border border-slate-800 focus:border-brand-accent rounded-xl p-3 text-white font-mono font-bold outline-none" 
              required
            >
            <p class="text-[10px] text-slate-500 mt-1">Visible para que puedas suministrársela al distribuidor.</p>
          </div>

          <div class="p-3 bg-indigo-950/30 border border-indigo-500/20 rounded-xl text-indigo-300 text-[11px]">
            ℹ️ El revendedor utilizará estas credenciales para autenticarse en el panel <strong>/crm</strong>.
          </div>

          <div class="flex gap-3 pt-2">
            <button type="button" @click="showResellerAuthModal = false" class="w-1/3 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold py-2.5 rounded-xl">
              Cancelar
            </button>
            <button type="submit" class="w-2/3 bg-brand-accent hover:bg-sky-400 text-slate-950 font-black py-2.5 rounded-xl shadow transition-all">
              Guardar Credenciales
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL: GESTIÓN DE CREDENCIALES MAESTRAS DE ADMINISTRADOR -->
    <div v-if="showAdminAuthModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-sm animate-in fade-in duration-200">
      <div class="bg-slate-900 border border-amber-500/30 w-full max-w-md rounded-3xl p-6 shadow-2xl relative space-y-5 text-xs">
        <div class="flex justify-between items-start border-b border-slate-800 pb-3">
          <div class="flex items-center gap-2">
            <span class="text-xl">🛡️</span>
            <div>
              <h3 class="text-lg font-black text-white">Credenciales de Administrador</h3>
              <p class="text-slate-400">Acceso maestro al Centro Mayorista Proveedor.</p>
            </div>
          </div>
          <button @click="showAdminAuthModal = false" class="text-slate-400 hover:text-white p-1">✕</button>
        </div>

        <form @submit.prevent="saveAdminAuth" class="space-y-4">
          <div>
            <label class="block font-bold uppercase text-slate-400 mb-1">Usuario Maestro</label>
            <input 
              v-model="adminAuthForm.username" 
              type="text" 
              class="w-full bg-slate-950 border border-slate-800 focus:border-amber-400 rounded-xl p-3 text-white font-mono font-bold outline-none" 
              required
            >
          </div>

          <div>
            <label class="block font-bold uppercase text-slate-400 mb-1">Contraseña Maestra</label>
            <input 
              v-model="adminAuthForm.password" 
              type="text" 
              class="w-full bg-slate-950 border border-slate-800 focus:border-amber-400 rounded-xl p-3 text-white font-mono font-bold outline-none" 
              required
            >
            <p class="text-[10px] text-slate-500 mt-1">Guarda esta clave en un lugar seguro. Se almacena cifrada en la base de datos.</p>
          </div>

          <div>
            <label class="block font-bold uppercase text-slate-400 mb-1">Nombre / Razón Social Administrador</label>
            <input 
              v-model="adminAuthForm.name" 
              type="text" 
              class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white outline-none"
            >
          </div>

          <div>
            <label class="block font-bold uppercase text-slate-400 mb-1">WhatsApp de Soporte Mayorista</label>
            <input 
              v-model="adminAuthForm.whatsapp" 
              type="tel" 
              class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white outline-none"
            >
          </div>

          <div class="flex gap-3 pt-2">
            <button type="button" @click="showAdminAuthModal = false" class="w-1/3 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold py-2.5 rounded-xl">
              Cancelar
            </button>
            <button type="submit" class="w-2/3 bg-amber-500 hover:bg-amber-400 text-slate-950 font-black py-2.5 rounded-xl shadow transition-all">
              Guardar Credenciales
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL: EDITAR PRECIOS Y COSTOS DE PLAN VIRTUAL -->
    <div v-if="editingPlanModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-sm animate-in fade-in duration-200">
      <div class="bg-slate-900 border border-cyan-500/30 w-full max-w-md rounded-3xl p-6 shadow-2xl relative space-y-4 text-xs">
        <div class="flex justify-between items-start border-b border-slate-800 pb-3">
          <div>
            <span class="text-xs font-mono font-bold text-cyan-400">{{ editingPlatformKey }}</span>
            <h3 class="text-lg font-black text-white">Editar Precios y Ganancias</h3>
          </div>
          <button @click="editingPlanModal = false" class="text-slate-400 hover:text-white p-1">✕</button>
        </div>

        <form @submit.prevent="savePlanEdit" class="space-y-3">
          <div>
            <label class="block font-bold uppercase text-slate-400 mb-1">Nombre / Etiqueta del Plan</label>
            <input v-model="planEditForm.label" type="text" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white outline-none" required>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold uppercase text-slate-400 mb-1">Costo Proveedor (ARS)</label>
              <input v-model="planEditForm.providerCost" type="number" min="0" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white font-mono outline-none" required>
            </div>
            <div>
              <label class="block font-bold uppercase text-slate-400 mb-1">Ganancia Revendedor (ARS)</label>
              <input v-model="planEditForm.resellerProfit" type="number" min="0" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-emerald-400 font-mono outline-none" required>
            </div>
          </div>

          <div>
            <label class="block font-bold uppercase text-slate-400 mb-1">Precio al Cliente Final (ARS)</label>
            <input v-model="planEditForm.clientPrice" type="number" min="0" class="w-full bg-slate-950 border border-slate-800 focus:border-cyan-400 rounded-xl p-3 text-amber-400 font-mono font-black text-base outline-none" required>
          </div>

          <div class="flex gap-3 pt-3">
            <button type="button" @click="editingPlanModal = false" class="w-1/3 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold py-2.5 rounded-xl">
              Cancelar
            </button>
            <button type="submit" class="w-2/3 bg-cyan-400 hover:bg-cyan-300 text-slate-950 font-black py-2.5 rounded-xl shadow transition-all">
              Guardar Precios
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL: AGREGAR NUEVA PLATAFORMA VIRTUAL AL CATÁLOGO -->
    <div v-if="showAddPlatformModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-sm animate-in fade-in duration-200">
      <div class="bg-slate-900 border border-cyan-500/30 w-full max-w-md rounded-3xl p-6 shadow-2xl relative space-y-4 text-xs">
        <div class="flex justify-between items-start border-b border-slate-800 pb-3">
          <div>
            <h3 class="text-lg font-black text-white">Nueva Plataforma Virtual</h3>
            <p class="text-slate-400">Registra un nuevo servicio de streaming en el catálogo.</p>
          </div>
          <button @click="showAddPlatformModal = false" class="text-slate-400 hover:text-white p-1">✕</button>
        </div>

        <form @submit.prevent="submitAddPlatform" class="space-y-3">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold uppercase text-slate-400 mb-1">Código Único (ej: Crunchyroll)</label>
              <input v-model="newPlatformForm.key" type="text" placeholder="Crunchyroll" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white outline-none" required>
            </div>
            <div>
              <label class="block font-bold uppercase text-slate-400 mb-1">Nombre Comercial</label>
              <input v-model="newPlatformForm.name" type="text" placeholder="Crunchyroll Mega Fan" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white outline-none" required>
            </div>
          </div>

          <div>
            <label class="block font-bold uppercase text-slate-400 mb-1">Plan Inicial (Etiqueta)</label>
            <input v-model="newPlatformForm.planLabel" type="text" placeholder="1 Pantalla / Perfil Fan" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white outline-none" required>
          </div>

          <div class="grid grid-cols-3 gap-2">
            <div>
              <label class="block font-bold uppercase text-slate-400 mb-1">Costo Prov.</label>
              <input v-model="newPlatformForm.providerCost" type="number" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2 text-white font-mono" required>
            </div>
            <div>
              <label class="block font-bold uppercase text-slate-400 mb-1">Ganancia Rev.</label>
              <input v-model="newPlatformForm.resellerProfit" type="number" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2 text-emerald-400 font-mono" required>
            </div>
            <div>
              <label class="block font-bold uppercase text-slate-400 mb-1">P. Cliente</label>
              <input v-model="newPlatformForm.clientPrice" type="number" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2 text-amber-400 font-mono font-bold" required>
            </div>
          </div>

          <div class="flex gap-3 pt-3">
            <button type="button" @click="showAddPlatformModal = false" class="w-1/3 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold py-2.5 rounded-xl">
              Cancelar
            </button>
            <button type="submit" class="w-2/3 bg-cyan-400 hover:bg-cyan-300 text-slate-950 font-black py-2.5 rounded-xl shadow transition-all">
              Guardar Plataforma
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL: CREDENCIALES GENERADAS PARA NUEVO REVENDEDOR APROBADO -->
    <div v-if="showApprovedCredentialsModal && approvedCredentialsData" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-md animate-in fade-in duration-200">
      <div class="bg-slate-900 border border-emerald-500/40 w-full max-w-lg rounded-3xl p-6 sm:p-8 shadow-2xl space-y-6 relative">
        <div class="text-center space-y-2">
          <div class="w-16 h-16 rounded-2xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-3xl mx-auto border border-emerald-500/30 shadow-inner">
            🎉
          </div>
          <h3 class="text-xl font-black text-white">¡Revendedor Creado y Aprobado!</h3>
          <p class="text-xs text-slate-400">
            La cuenta para <strong>{{ approvedCredentialsData.reseller.name }}</strong> ha sido dada de alta en el sistema.
          </p>
        </div>

        <!-- Ficha de Credenciales Generadas -->
        <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 space-y-2.5 font-mono text-xs">
          <div class="flex justify-between items-center border-b border-slate-800 pb-2">
            <span class="text-slate-500 font-sans">Identificador:</span>
            <span class="text-amber-400 font-bold text-sm">[{{ approvedCredentialsData.reseller.id }}]</span>
          </div>
          <div class="flex justify-between items-center border-b border-slate-800 pb-2">
            <span class="text-slate-500 font-sans">Usuario de Acceso:</span>
            <span class="text-white font-bold">{{ approvedCredentialsData.reseller.username }}</span>
          </div>
          <div class="flex justify-between items-center border-b border-slate-800 pb-2">
            <span class="text-slate-500 font-sans">Contraseña Temporal:</span>
            <span class="text-emerald-400 font-bold bg-slate-900 px-2 py-0.5 rounded border border-slate-800">{{ approvedCredentialsData.reseller.password }}</span>
          </div>
          <div class="flex justify-between items-center border-b border-slate-800 pb-2">
            <span class="text-slate-500 font-sans">WhatsApp Registrado:</span>
            <span class="text-slate-300 font-sans">{{ approvedCredentialsData.reseller.contactNumber }}</span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-slate-500 font-sans">Enlace de Inicio:</span>
            <span class="text-sky-400">streamepro.me/crm</span>
          </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-3 pt-2">
          <button 
            @click="sendCredentialsWhatsApp(approvedCredentialsData)" 
            class="flex-1 bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-400 hover:to-emerald-500 text-slate-950 font-black py-3 rounded-xl shadow-lg transition-all text-xs flex items-center justify-center gap-2 cursor-pointer"
          >
            <span>📲 Enviar Credenciales por WhatsApp</span>
          </button>
          <button 
            @click="showApprovedCredentialsModal = false; approvedCredentialsData = null" 
            class="bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold py-3 px-5 rounded-xl text-xs"
          >
            Cerrar
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL AGREGAR / EDITAR CUENTA BANCARIA -->
    <div v-if="showAddAccountModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-sm animate-in fade-in duration-200">
      <div class="bg-slate-900 border border-slate-700 w-full max-w-lg rounded-2xl p-6 shadow-2xl relative space-y-4 text-xs">
        <div class="flex justify-between items-start border-b border-slate-800 pb-3">
          <div>
            <h3 class="text-xl font-black text-white">{{ editingAccountId ? 'Editar Cuenta Bancaria' : 'Nueva Cuenta Bancaria' }}</h3>
            <p class="text-xs text-slate-400 mt-0.5">Ingresa los datos para cobrar transferencias bancarias o virtuales.</p>
          </div>
          <button @click="showAddAccountModal = false" class="text-slate-400 hover:text-white p-1 cursor-pointer">✕</button>
        </div>

        <form @submit.prevent="saveAccountModal" class="space-y-3.5">
          <div>
            <label class="block font-bold uppercase text-slate-400 mb-1">Nombre Identificatorio de la Cuenta</label>
            <input v-model="newAccountForm.name" type="text" placeholder="Ej: Mercado Pago Principal, Banco Galicia, etc." class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white outline-none focus:border-brand-accent font-medium" required>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold uppercase text-slate-400 mb-1">Titular de la Cuenta</label>
              <input v-model="newAccountForm.holderName" type="text" placeholder="Ej: StreamPro Central" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white outline-none focus:border-brand-accent font-medium" required>
            </div>
            <div>
              <label class="block font-bold uppercase text-slate-400 mb-1">Entidad Bancaria</label>
              <input v-model="newAccountForm.bankName" type="text" placeholder="Ej: Mercado Pago, Galicia, BBVA" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white outline-none focus:border-brand-accent font-medium" required>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold uppercase text-slate-400 mb-1">Alias CBU / CVU</label>
              <input v-model="newAccountForm.cbuAlias" type="text" placeholder="Ej: STREAMPRO.MAYORISTA" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white outline-none focus:border-brand-accent font-mono font-bold text-amber-300" required>
            </div>
            <div>
              <label class="block font-bold uppercase text-slate-400 mb-1">CUIT / CUIL</label>
              <input v-model="newAccountForm.cuit" type="text" placeholder="Ej: 20-00000000-0" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white outline-none focus:border-brand-accent font-mono" required>
            </div>
          </div>

          <div>
            <label class="block font-bold uppercase text-slate-400 mb-1">CBU / CVU Numérico (22 dígitos)</label>
            <input v-model="newAccountForm.cbuNumber" type="text" placeholder="0000000000000000000000" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white outline-none focus:border-brand-accent font-mono" required>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold uppercase text-slate-400 mb-1">WhatsApp de Soporte</label>
              <input v-model="newAccountForm.whatsapp" type="text" placeholder="+54 9 11 0000-0000" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white outline-none focus:border-brand-accent">
            </div>
            <div>
              <label class="block font-bold uppercase text-slate-400 mb-1">Correo de Notificaciones</label>
              <input v-model="newAccountForm.notifyEmail" type="email" placeholder="notificaciones@gmail.com" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white outline-none focus:border-brand-accent">
            </div>
          </div>

          <div class="flex gap-2 pt-3">
            <button type="button" @click="showAddAccountModal = false" class="w-1/3 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold py-2.5 rounded-xl cursor-pointer">
              Cancelar
            </button>
            <button type="submit" class="w-2/3 bg-brand-accent hover:bg-sky-400 text-slate-950 font-black py-2.5 rounded-xl shadow transition-all cursor-pointer">
              {{ editingAccountId ? 'Guardar Cambios' : 'Registrar Cuenta Bancaria' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL: GENERADOR DE FLYERS Y BANNERS PUBLICITARIOS -->
    <FlyerGeneratorModal 
      :show="showFlyerModal" 
      @close="showFlyerModal = false" 
      :default-store-name="adminStore.adminAuth.name || 'StreamPro Mayorista'" 
      :default-phone="adminStore.providerBankInfo.whatsapp || adminStore.adminAuth.whatsapp" 
    />
  </div>
</template>

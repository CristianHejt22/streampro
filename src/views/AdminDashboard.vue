<script setup>
import { ref, computed, watch, onMounted, defineAsyncComponent } from 'vue'
import { useRouter } from 'vue-router'
import { useAdminStore, useResellerStore } from '../store/admin'
import { useToastStore } from '../store/toast'
import { loadDb, DB_KEYS, generateSecurePassword, exportDatabaseBackup, importDatabaseBackup } from '../services/db'
import { getDatabaseHealthStatus, applyPristineDatabase } from '../services/cleanDatabaseSeed'
import { testFirebaseConnection } from '../services/firebase'
import { parseGamingTextList, matchGamingListWithCatalog, smartSearchGamingMatch, resolveOfficialGameImage } from '../services/gamingListParser'
const FlyerGeneratorModal = defineAsyncComponent(() => import('../components/FlyerGeneratorModal.vue'))
const SharePlaqueModal = defineAsyncComponent(() => import('../components/SharePlaqueModal.vue'))
const ReceiptViewerModal = defineAsyncComponent(() => import('../components/ReceiptViewerModal.vue'))

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
const router = useRouter()

// MODAL DE COMPROBANTE EN MINI NUBE (JPG, PNG, PDF)
const showReceiptModal = ref(false)
const selectedReceiptOrder = ref(null)
const isPhysicalReceipt = ref(false)

const openReceiptViewer = (order, isPhysical = false) => {
  selectedReceiptOrder.value = order
  isPhysicalReceipt.value = isPhysical
  showReceiptModal.value = true
}

const handleConfirmCloseFromReceipt = (order) => {
  showReceiptModal.value = false
  if (isPhysicalReceipt.value) {
    handleConfirmPhysicalOrder(order)
  } else if (isOrderGamingType(order)) {
    openGamingDispatchModal(order)
  } else {
    openDispatchModal(order)
  }
}

const handleReceiptPurged = (orderId) => {
  adminStore.purgeOrderReceipt(orderId, isPhysicalReceipt.value)
  toast.add('Comprobante depurado de la mini nube con éxito', 'info')
}

// Formulario de Carga de Lotes
const defaultProfilesForCount = (count) => {
  return Array.from({ length: Number(count) }, (_, idx) => ({
    name: `Perfil ${idx + 1}`,
    pin: ''
  }))
}

const showNewStockPass = ref(false)
const newStock = ref({ 
  platform: 'Netflix', 
  account: '', 
  pass: '', 
  cost: 5500,
  maxProfiles: 4,
  profiles: defaultProfilesForCount(4)
})

const updateMaxProfiles = (val) => {
  const target = Number(val)
  newStock.value.maxProfiles = target
  const current = newStock.value.profiles || []
  if (current.length < target) {
    const extra = Array.from({ length: target - current.length }, (_, i) => ({
      name: `Perfil ${current.length + i + 1}`,
      pin: ''
    }))
    newStock.value.profiles = [...current, ...extra]
  } else {
    newStock.value.profiles = current.slice(0, target)
  }
}

const handleAddStock = () => {
  if (!newStock.value.account || !newStock.value.pass || !newStock.value.cost) {
    toast.add('Completa todos los campos obligatorios del inventario', 'error')
    return
  }

  // Validar que se haya colocado el PIN de cada perfil
  const hasEmptyPin = (newStock.value.profiles || []).some(p => !p.pin || !p.pin.trim())
  if (hasEmptyPin) {
    toast.add('Por favor, ingresa el PIN de cada uno de los perfiles antes de cargar', 'warning')
    return
  }

  adminStore.addStock({ ...newStock.value })
  toast.add(`Lote mayorista cargado: ${newStock.value.platform} (${newStock.value.maxProfiles} perfiles con PINs)`, 'success')
  newStock.value = { 
    platform: 'Netflix', 
    account: '', 
    pass: '', 
    cost: 5500,
    maxProfiles: 4,
    profiles: defaultProfilesForCount(4)
  }
  showNewStockPass.value = false
}

// Visibilidad de contraseñas en tabla (ocultas por defecto)
const visiblePasswordMap = ref({})
const toggleInvPasswordVisibility = (id) => {
  visiblePasswordMap.value[id] = !visiblePasswordMap.value[id]
}
const copyPasswordToClipboard = async (pass) => {
  if (!pass) return
  try {
    await navigator.clipboard.writeText(pass)
    toast.add('Contraseña copiada al portapapeles', 'info')
  } catch (err) {
    toast.add('Error al copiar la contraseña', 'error')
  }
}

// Edición Completa de Lote de Inventario Mayorista
const showEditInventoryModal = ref(false)
const editingInvItem = ref(null)
const showEditStockPass = ref(false)

const openEditInventoryModal = (item) => {
  editingInvItem.value = JSON.parse(JSON.stringify(item))
  if (!editingInvItem.value.profiles || editingInvItem.value.profiles.length === 0) {
    editingInvItem.value.profiles = defaultProfilesForCount(editingInvItem.value.maxProfiles || 4)
  }
  showEditStockPass.value = false
  showEditInventoryModal.value = true
}

const handleEditMaxProfiles = (val) => {
  if (!editingInvItem.value) return
  const target = Number(val)
  editingInvItem.value.maxProfiles = target
  const current = editingInvItem.value.profiles || []
  if (current.length < target) {
    const extra = Array.from({ length: target - current.length }, (_, i) => ({
      name: `Perfil ${current.length + i + 1}`,
      pin: '',
      isAssigned: false,
      assignedTo: null
    }))
    editingInvItem.value.profiles = [...current, ...extra]
  } else {
    editingInvItem.value.profiles = current.slice(0, target)
  }
}

const handleSaveEditedInventory = () => {
  if (!editingInvItem.value) return
  if (!editingInvItem.value.account?.trim() || !editingInvItem.value.pass?.trim()) {
    toast.add('Correo y contraseña máster son obligatorios', 'error')
    return
  }
  const hasEmptyPin = (editingInvItem.value.profiles || []).some(p => !p.pin || !p.pin.trim())
  if (hasEmptyPin) {
    toast.add('Por favor, ingresa el PIN de cada perfil', 'warning')
    return
  }

  const res = adminStore.updateInventoryItem(editingInvItem.value)
  if (res.success) {
    toast.add('Lote de inventario mayorista actualizado con éxito', 'success')
    showEditInventoryModal.value = false
  } else {
    toast.add(res.error || 'Error al actualizar lote', 'error')
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
const showPrivPassModal = ref(false)
const notifyActiveClients = ref(false)
const updateReason = ref('Mantenimiento preventivo de seguridad')

const openPasswordModal = (item) => {
  selectedInvItem.value = item
  updatedPassword.value = item.pass
  showPrivPassModal.value = false
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
  const text = `👋 *STREAMEPRO - CREDENCIALES OFICIALES DE DISTRIBUIDOR*\n\n` +
    `¡Hola ${reseller.name}!\nTu cuenta de revendedor mayorista ha sido activada en el sistema:\n\n` +
    `🏪 *Tienda:* ${reseller.storeName || reseller.name}\n` +
    `🔑 *Código:* ${reseller.id}\n` +
    `👤 *Usuario:* ${reseller.username}\n` +
    `🔒 *Contraseña:* ${reseller.password}\n` +
    `💼 *Billetera Inicial:* $${(reseller.balance || 0).toLocaleString()} ARS\n\n` +
    `🌐 *Acceso al Portal:* ${origin}/distribuidor\n` +
    `🔗 *Tu Enlace de Tienda Oficial:* ${origin}/?ref=${reseller.id}\n\n` +
    `¡Bienvenido al equipo de distribución oficial!`

  navigator.clipboard.writeText(text).then(() => {
    toast.add(`Credenciales de ${reseller.name} copiadas para WhatsApp`, 'success')
  })
}

const copyResellerStoreLink = (reseller) => {
  const origin = typeof window !== 'undefined' && window.location?.origin ? window.location.origin : 'https://streamepro.me'
  const url = `${origin}/?ref=${reseller.id}`
  navigator.clipboard.writeText(url).then(() => {
    toast.add(`🔗 Enlace de tienda copiado para ${reseller.name} (${reseller.id})`, 'success')
  })
}

const openResellerPanelDirect = (reseller) => {
  if (!reseller) return
  resellerStore.isAuthenticated = true
  resellerStore.activeResellerId = reseller.id
  resellerStore.sessionUser = { ...reseller }
  resellerStore.wallet = Number(reseller.balance || 0)
  resellerStore.persist()
  router.push('/crm')
  toast.add(`🚀 Abriendo Panel de Revendedor: ${reseller.name} (${reseller.id})`, 'success')
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

// GESTIÓN INTEGRAL DE TIENDA Y CATÁLOGO DE PRODUCTOS FÍSICOS
const adminPhysicalSubTab = ref('products') // 'products' | 'orders' | 'pages'
const adminPhysicalProductSearch = ref('')
const adminPhysicalCategoryFilter = ref('All')

// Modal de Creación / Edición de Producto Físico o Página
const showPhysicalProductModal = ref(false)
const isEditingPhysicalProduct = ref(false)
const physicalProductForm = ref({
  id: '',
  name: '',
  category: 'General',
  categoryId: 'general',
  sku: '',
  stock: 20,
  priceNumeric: 0,
  originalPriceNumeric: null,
  image: '',
  galleryUrlsText: '',
  description: '',
  adminOnly: false,
  pageSlug: '',
  pageType: 'producto',
  variables: []
})

// Modal de Previsualización de Página
const showPagePreviewModal = ref(false)
const previewingPage = ref(null)

const publicPhysicalProductsCount = computed(() => {
  return (adminStore.physicalProducts || []).filter(p => !p.adminOnly && p.categoryId !== 'paginas').length
})

const adminPagesList = computed(() => {
  return (adminStore.physicalProducts || []).filter(p => p.adminOnly || p.categoryId === 'paginas' || (p.categoryIds && p.categoryIds.includes('paginas')))
})

const filteredPhysicalProductsForAdmin = computed(() => {
  return (adminStore.physicalProducts || []).filter(p => {
    if (adminPhysicalCategoryFilter.value !== 'paginas' && (p.adminOnly || p.categoryId === 'paginas')) {
      return false
    }
    const matchCat = adminPhysicalCategoryFilter.value === 'All' || 
      (p.categoryIds && p.categoryIds.includes(adminPhysicalCategoryFilter.value)) ||
      p.categoryId === adminPhysicalCategoryFilter.value
    const q = (adminPhysicalProductSearch.value || '').toLowerCase().trim()
    const matchQ = !q || (p.name && p.name.toLowerCase().includes(q)) || (p.description && p.description.toLowerCase().includes(q)) || String(p.id).includes(q) || (p.sku && p.sku.toLowerCase().includes(q))
    return matchCat && matchQ
  })
})

// Paginación Reactiva Fluida para Productos Físicos
const physicalAdminCurrentPage = ref(1)
const physicalAdminPageSize = ref(24)
const totalPhysicalAdminPages = computed(() => {
  return Math.max(1, Math.ceil((filteredPhysicalProductsForAdmin.value?.length || 0) / physicalAdminPageSize.value))
})
const paginatedPhysicalProductsForAdmin = computed(() => {
  const list = filteredPhysicalProductsForAdmin.value || []
  const start = (physicalAdminCurrentPage.value - 1) * physicalAdminPageSize.value
  return list.slice(start, start + physicalAdminPageSize.value)
})
watch([adminPhysicalCategoryFilter, adminPhysicalProductSearch], () => {
  physicalAdminCurrentPage.value = 1
})

const filteredAdminPages = computed(() => {
  const q = (adminPhysicalProductSearch.value || '').toLowerCase().trim()
  return adminPagesList.value.filter(p => {
    return !q || (p.name && p.name.toLowerCase().includes(q)) || (p.description && p.description.toLowerCase().includes(q)) || (p.sku && p.sku.toLowerCase().includes(q)) || (p.pageSlug && p.pageSlug.toLowerCase().includes(q))
  })
})

const openCreatePhysicalProductModal = (isPage = false) => {
  isEditingPhysicalProduct.value = false
  physicalProductForm.value = {
    id: isPage ? ('PAGE-' + Date.now().toString().slice(-4)) : ('PROD-' + Date.now().toString().slice(-6)),
    name: '',
    category: isPage ? 'Páginas y Secciones' : (adminPhysicalCategoryFilter.value !== 'All' ? (adminStore.physicalCategories.find(c => c.id === adminPhysicalCategoryFilter.value)?.name || 'General') : 'General'),
    categoryId: isPage ? 'paginas' : (adminPhysicalCategoryFilter.value !== 'All' ? adminPhysicalCategoryFilter.value : 'general'),
    sku: isPage ? ('PAG-' + Date.now().toString().slice(-3)) : '',
    stock: isPage ? 1 : 20,
    priceNumeric: 0,
    originalPriceNumeric: null,
    image: isPage ? 'https://streamepro.me/og-banner.png' : '',
    galleryUrlsText: '',
    description: '',
    adminOnly: isPage,
    pageSlug: isPage ? 'nueva-pagina' : '',
    pageType: isPage ? 'informativa' : 'producto',
    variables: []
  }
  showPhysicalProductModal.value = true
}

const openEditPhysicalProductModal = (item) => {
  isEditingPhysicalProduct.value = true
  const isPage = Boolean(item.adminOnly || item.categoryId === 'paginas')
  physicalProductForm.value = {
    id: item.id,
    name: item.name || item.title || '',
    category: item.category || (isPage ? 'Páginas y Secciones' : 'General'),
    categoryId: item.categoryId || (item.categoryIds?.[0]) || (isPage ? 'paginas' : 'general'),
    sku: item.sku || '',
    stock: item.stock ?? 20,
    priceNumeric: item.priceNumeric || 0,
    originalPriceNumeric: item.originalPriceNumeric || null,
    image: item.image || '',
    galleryUrlsText: Array.isArray(item.images) ? item.images.join('\n') : (item.image || ''),
    description: item.description || '',
    adminOnly: isPage,
    pageSlug: item.pageSlug || '',
    pageType: item.pageType || (isPage ? 'informativa' : 'producto'),
    variables: item.variables ? JSON.parse(JSON.stringify(item.variables)) : []
  }
  showPhysicalProductModal.value = true
}

const savePhysicalProductForm = () => {
  if (!physicalProductForm.value.name.trim()) {
    toast.add('El nombre o título es obligatorio', 'error')
    return
  }

  const galleryImages = (physicalProductForm.value.galleryUrlsText || '')
    .split('\n')
    .map(u => u.trim())
    .filter(u => u.startsWith('http'))

  const mainImage = physicalProductForm.value.image.trim() || galleryImages[0] || 'https://streamepro.me/og-banner.png'
  const images = galleryImages.length > 0 ? galleryImages : [mainImage]

  const payload = {
    ...physicalProductForm.value,
    image: mainImage,
    images: images
  }

  if (isEditingPhysicalProduct.value) {
    const res = adminStore.updatePhysicalProduct(physicalProductForm.value.id, payload)
    if (res.success) {
      toast.add('"' + payload.name + '" actualizado exitosamente', 'success')
      showPhysicalProductModal.value = false
    } else {
      toast.add(res.error || 'Error al actualizar', 'error')
    }
  } else {
    const res = adminStore.createPhysicalProduct(payload)
    if (res.success) {
      toast.add('"' + payload.name + '" agregado con éxito', 'success')
      showPhysicalProductModal.value = false
    } else {
      toast.add(res.error || 'Error al crear', 'error')
    }
  }
}

const deletePhysicalProductItem = (item) => {
  const isPage = item.adminOnly || item.categoryId === 'paginas'
  const label = isPage ? 'la página' : 'el producto'
  if (confirm('¿Estás seguro de eliminar ' + label + ' "' + item.name + '"? Esta acción no se puede deshacer.')) {
    const res = adminStore.deletePhysicalProduct(item.id)
    if (res.success) {
      toast.add('"' + item.name + '" eliminado correctamente', 'info')
    } else {
      toast.add('Error al eliminar', 'error')
    }
  }
}

const quickAdjustStock = (item, delta) => {
  const newStock = Math.max(0, (item.stock || 0) + delta)
  adminStore.updatePhysicalStock(item.id, newStock)
  toast.add('Stock de "' + item.name.slice(0, 18) + '..." actualizado a ' + newStock + ' un.', 'info')
}

const applyDescriptionTemplate = () => {
  const name = physicalProductForm.value.name || 'Producto Oficial'
  const cat = physicalProductForm.value.category || 'Accesorios y Tecnología'
  physicalProductForm.value.description = '<div class="product-description-content space-y-3">\n' +
    '  <p class="font-semibold text-white">' + name + '</p>\n' +
    '  <p class="text-slate-300">Artículo seleccionado de alta calidad con garantía y compatibilidad garantizada.</p>\n' +
    '  <div class="bg-slate-900/60 p-3 rounded-xl border border-slate-800">\n' +
    '    <h5 class="text-xs font-bold text-amber-400 uppercase tracking-wider mb-2">📌 Especificaciones Destacadas:</h5>\n' +
    '    <ul class="list-disc pl-5 space-y-1 text-slate-300 text-xs">\n' +
    '      <li><strong>Categoría:</strong> ' + cat + '</li>\n' +
    '      <li><strong>Disponibilidad:</strong> Stock garantizado para despacho inmediato</li>\n' +
    '      <li><strong>Presentación:</strong> Empaque original protegido para traslados y envíos</li>\n' +
    '      <li><strong>Condición:</strong> Artículo nuevo y 100% verificado</li>\n' +
    '    </ul>\n' +
    '  </div>\n' +
    '  <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-[11px] text-slate-300 pt-1">\n' +
    '    <div class="bg-slate-900/80 p-2.5 rounded-xl border border-slate-800 flex items-center gap-2">\n' +
    '      <span class="text-emerald-400 text-base">🛡️</span>\n' +
    '      <div>\n' +
    '        <p class="font-bold text-white">Garantía StreamePro</p>\n' +
    '        <p class="text-slate-400">Verificado antes del despacho</p>\n' +
    '      </div>\n' +
    '    </div>\n' +
    '    <div class="bg-slate-900/80 p-2.5 rounded-xl border border-slate-800 flex items-center gap-2">\n' +
    '      <span class="text-amber-400 text-base">🚚</span>\n' +
    '      <div>\n' +
    '        <p class="font-bold text-white">Envíos a Todo el País</p>\n' +
    '        <p class="text-slate-400">Despacho express asegurado</p>\n' +
    '      </div>\n' +
    '    </div>\n' +
    '  </div>\n' +
    '</div>'
  toast.add('¡Plantilla profesional aplicada a la descripción!', 'success')
}

const cleanDescriptionHtml = () => {
  let desc = physicalProductForm.value.description || ''
  desc = desc.replace(/\s*(style|class|dir|id|align|bgcolor)="[^"]*"/gi, '')
  desc = desc.replace(/\s*(style|class|dir|id|align|bgcolor)='[^']*'/gi, '')
  desc = desc.replace(/<\/?font[^>]*>/gi, '')
  desc = desc.replace(/<span[^>]*>(.*?)<\/span>/gi, '$1')
  desc = desc.replace(/<\/?span[^>]*>/gi, '')
  desc = desc.replace(/<p>\s*(&nbsp;)?\s*<\/p>/gi, '')
  desc = desc.replace(/<div>\s*(&nbsp;)?\s*<\/div>/gi, '')
  physicalProductForm.value.description = desc.trim()
  toast.add('Estilos innecesarios limpiados exitosamente', 'info')
}

const addVariableToForm = () => {
  physicalProductForm.value.variables.push({
    id: Date.now(),
    name: 'Nueva Variante',
    sku: physicalProductForm.value.sku || '',
    price: physicalProductForm.value.priceNumeric,
    priceFormatted: '$' + Number(physicalProductForm.value.priceNumeric).toLocaleString('es-AR', { minimumFractionDigits: 2 }),
    comparePrice: physicalProductForm.value.originalPriceNumeric,
    stock: physicalProductForm.value.stock,
    available: true,
    image: physicalProductForm.value.image,
    options: ['Nueva Variante']
  })
}

const removeVariableFromForm = (idx) => {
  physicalProductForm.value.variables.splice(idx, 1)
}

const openPagePreview = (page) => {
  previewingPage.value = page
  showPagePreviewModal.value = true
}

// MODAL DE AJUSTE MASIVO POR PORCENTAJE (%) PARA TODOS LOS PRODUCTOS
const showBulkPercentageModal = ref(false)
const bulkPercentageForm = ref({
  action: 'discount', // 'discount' | 'increase' | 'restore'
  percentage: 10,
  categoryId: 'All',
  roundTo: 10
})

const openBulkPercentageModal = () => {
  bulkPercentageForm.value = {
    action: 'discount',
    percentage: 10,
    categoryId: adminPhysicalCategoryFilter.value || 'All',
    roundTo: 10
  }
  showBulkPercentageModal.value = true
}

const applyBulkPercentage = () => {
  const res = adminStore.applyBulkPercentageToPhysicalProducts(bulkPercentageForm.value)
  if (res.success) {
    const actionTxt = bulkPercentageForm.value.action === 'restore'
      ? 'Precios originales de revende.com.ar restaurados'
      : (bulkPercentageForm.value.action === 'discount'
          ? ('Descuento de oferta del ' + bulkPercentageForm.value.percentage + '% aplicado')
          : ('Aumento / margen del ' + bulkPercentageForm.value.percentage + '% aplicado'))
    toast.add('¡Éxito! ' + actionTxt + ' en ' + res.count + ' productos.', 'success')
    showBulkPercentageModal.value = false
  } else {
    toast.add(res.error || 'Error al aplicar porcentaje', 'error')
  }
}

const bulkPercentagePreviewList = computed(() => {
  const pct = Math.abs(Number(bulkPercentageForm.value.percentage) || 0)
  const roundTo = bulkPercentageForm.value.roundTo || 10
  const action = bulkPercentageForm.value.action
  const catId = bulkPercentageForm.value.categoryId

  const roundNum = (val) => {
    if (!roundTo || roundTo <= 1) return Math.round(val)
    return Math.round(val / roundTo) * roundTo
  }

  const sampleProducts = (adminStore.physicalProducts || [])
    .filter(p => !p.adminOnly && p.categoryId !== 'paginas')
    .filter(p => catId === 'All' || p.categoryId === catId || (p.categoryIds && p.categoryIds.includes(catId)))
    .slice(0, 3)

  return sampleProducts.map(p => {
    const current = p.priceNumeric || 0
    let simulated = current
    if (action === 'increase') {
      simulated = roundNum(current * (1 + pct / 100))
    } else if (action === 'discount') {
      const orig = (adminStore.physicalProducts || []).find(prod => String(prod.id).trim() === String(p.id).trim())
      const factoryBase = orig ? (orig.priceNumeric || current) : current
      const factoryList = orig ? (orig.originalPriceNumeric || orig.priceNumeric || current) : current
      const currentList = (p.originalPriceNumeric && p.originalPriceNumeric > current) ? p.originalPriceNumeric : current
      const markupRatio = Math.max(1, currentList / factoryList)
      const baseUndisc = roundNum(factoryBase * markupRatio)
      simulated = roundNum(baseUndisc * (1 - pct / 100))
    } else if (action === 'restore') {
      simulated = current
    }
    return {
      id: p.id,
      name: p.name,
      currentPrice: current,
      newPrice: simulated,
      diff: simulated - current
    }
  })
})

const setFormDiscountPercentage = (pct) => {
  const p = Math.abs(Number(pct) || 0)
  if (!p) return
  const current = physicalProductForm.value.priceNumeric || 0
  const currentOriginal = physicalProductForm.value.originalPriceNumeric || current

  if (current > 0) {
    // Remarca el precio de lista y calcula la oferta con el % de descuento
    const newPrice = Math.round(current * (1 - p / 100))
    const finalOriginal = Math.max(currentOriginal, current, Math.round(newPrice / (1 - p / 100)))

    physicalProductForm.value.originalPriceNumeric = finalOriginal
    physicalProductForm.value.priceNumeric = newPrice
    toast.add('Descuento de -' + p + '% remarcado ($' + newPrice.toLocaleString() + ' con lista en $' + finalOriginal.toLocaleString() + ')', 'info')
  }
}

const setFormMarkupPercentage = (pct) => {
  const p = Math.abs(Number(pct) || 0)
  if (!p) return
  const currentPrice = physicalProductForm.value.priceNumeric || 0
  const currentOriginal = physicalProductForm.value.originalPriceNumeric || 0
  const hasDiscount = currentOriginal > currentPrice

  if (currentPrice > 0) {
    physicalProductForm.value.priceNumeric = Math.round(currentPrice * (1 + p / 100))
    if (hasDiscount) {
      physicalProductForm.value.originalPriceNumeric = Math.round(currentOriginal * (1 + p / 100))
    }
    toast.add('Aumento de +' + p + '% aplicado ($' + physicalProductForm.value.priceNumeric.toLocaleString() + ')', 'info')
  }
}

const copyPageContent = (page) => {
  const plainText = (page.description || '').replace(/<[^>]*>/g, '').trim()
  if (navigator?.clipboard?.writeText) {
    navigator.clipboard.writeText(plainText).then(() => {
      toast.add('Contenido copiado al portapapeles', 'success')
    })
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
// SINCRONIZACIÓN EN 1 CLIC DESDE LA API DE REVENDE.COM.AR
const isSyncingRevende = ref(false)
const handleSyncFromRevendeApi = async () => {
  isSyncingRevende.value = true
  try {
    const res = await adminStore.syncPhysicalFromApi()
    if (res.success) {
      toast.add(`¡Catálogo físico actualizado! ${res.count} productos sincronizados en vivo desde revende.com.ar`, 'success')
    } else {
      toast.add(res.error || 'Error al conectar con revende.com.ar', 'error')
    }
  } catch (err) {
    toast.add('Error al sincronizar con revende.com.ar', 'error')
  } finally {
    isSyncingRevende.value = false
  }
}
const isSyncingTua = isSyncingRevende
const handleSyncFromTuaApi = handleSyncFromRevendeApi

// DIRECTORIO INTEGRAL DE USUARIOS REGISTRADOS
const userSearch = ref('')
const userRoleFilter = ref('all')
const showAddUserModal = ref(false)
const newUser = ref({
  name: '',
  phone: '',
  email: '',
  role: 'client',
  address: '',
  pin: ''
})

const filteredRegisteredUsers = computed(() => {
  return adminStore.registeredUsers.filter(u => {
    const matchesRole = userRoleFilter.value === 'all' || u.role === userRoleFilter.value
    const q = userSearch.value.trim().toLowerCase()
    if (!q) return matchesRole
    const matchesSearch = 
      (u.name && u.name.toLowerCase().includes(q)) ||
      (u.phone && u.phone.includes(q)) ||
      (u.email && u.email.toLowerCase().includes(q)) ||
      (u.clientNumber && u.clientNumber.toLowerCase().includes(q)) ||
      (u.id && u.id.toLowerCase().includes(q))
    return matchesRole && matchesSearch
  })
})

const handleAddUser = () => {
  if (!newUser.value.name.trim()) {
    toast.add('Ingresa el nombre del usuario', 'error')
    return
  }
  if (!newUser.value.phone.trim()) {
    toast.add('Ingresa el WhatsApp o teléfono', 'error')
    return
  }
  adminStore.registerOrUpdateUser({
    name: newUser.value.name.trim(),
    phone: newUser.value.phone.trim(),
    email: newUser.value.email.trim(),
    address: newUser.value.address.trim(),
    role: newUser.value.role,
    pin: newUser.value.pin.trim() || '1234'
  })
  toast.add('¡Usuario registrado con éxito en el directorio!', 'success')
  showAddUserModal.value = false
  newUser.value = { name: '', phone: '', email: '', role: 'client', address: '', pin: '' }
}

const handleChangeUserRole = (user, newRole) => {
  adminStore.updateUser(user.id, { role: newRole })
  toast.add(`Rol actualizado a: ${newRole === 'reseller' ? 'Revendedor' : newRole === 'client' ? 'Cliente' : 'Proveedor'}`, 'success')
}

const handleDeleteUser = (user) => {
  if (confirm(`¿Estás seguro de eliminar al usuario ${user.name} del directorio?`)) {
    adminStore.deleteUser(user.id)
    toast.add('Usuario eliminado del directorio', 'info')
  }
}

const exportRegisteredUsers = () => {
  const json = JSON.stringify(adminStore.registeredUsers, null, 2)
  const blob = new Blob([json], { type: 'application/json' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `streampro_usuarios_${new Date().toISOString().split('T')[0]}.json`
  a.click()
  URL.revokeObjectURL(url)
  toast.add('Directorio de usuarios exportado en JSON', 'success')
}

const showUserVaultModal = ref(false)
const selectedUserForVault = ref(null)

const openUserVault = (user) => {
  selectedUserForVault.value = user
  showUserVaultModal.value = true
}


const showFlyerModal = ref(false)

// ==========================================
// GESTIÓN DE PROVEEDORES GAMING MULTI-SITIO & ARBITRAJE
// ==========================================
const gamingAdminSubTab = ref('comparison') // 'comparison' | 'margins' | 'sites' | 'argentinaplay' | 'juegosdigitalesargentina' | 'bulk_loader' | 'orders'
const gamingAdminSearch = ref('')
const gamingAdminCategoryFilter = ref('Todas')
const gamingAdminSubcategoryFilter = ref('Todas')
const gamingAdminPlatformFilter = ref('ALL')
const gamingAdminProviderFilter = ref('ALL')
const onlyHighestCostProvider = ref(true)
const gamingComparisonViewMode = ref('table') // 'table' | 'cards'
const gamingAdminDifferenceFilter = ref('ALL') // 'ALL' | 'with_diff' | 'conveyed' | 'single'

// Filtros y Ordenamiento por Precio para Proveedores
const gamingAdminPriceSort = ref('default') // 'default' | 'diff_desc' | 'price_desc' | 'price_asc' | 'cost_desc' | 'margin_desc'
const gamingAdminMinPrice = ref('')
const gamingAdminMaxPrice = ref('')

// ==========================================
// ADMINISTRACIÓN DE SITIOS WEB Y PROVEEDORES AGREGADOS
// ==========================================
const newSiteForm = ref({
  name: '',
  shortName: '',
  url: '',
  whatsapp: '',
  badge: '',
  includeInPvp: true,
  notes: ''
})
const editingSiteKey = ref(null)
const editingSiteForm = ref({
  name: '',
  shortName: '',
  url: '',
  whatsapp: '',
  badge: '',
  includeInPvp: true,
  enabled: true,
  notes: ''
})

const handleAddGamingSite = () => {
  if (!newSiteForm.value.name.trim()) {
    toast.add('Ingresa el nombre del sitio web o proveedor', 'error')
    return
  }
  const res = adminStore.addGamingProviderSite({ ...newSiteForm.value })
  if (res?.error) {
    toast.add(res.error, 'error')
    return
  }
  toast.add(`✓ Sitio proveedor "${res.site.name}" agregado${res.site.includeInPvp ? ' e incluido en el cálculo de Mayor Costo para PVP' : ''}.`, 'success')
  newSiteForm.value = { name: '', shortName: '', url: '', whatsapp: '', badge: '', includeInPvp: true, notes: '' }
}

const startEditGamingSite = (site) => {
  editingSiteKey.value = site.key
  editingSiteForm.value = {
    name: site.name || '',
    shortName: site.shortName || '',
    url: site.url || '',
    whatsapp: site.whatsapp || '',
    badge: site.badge || '',
    includeInPvp: site.includeInPvp !== false,
    enabled: site.enabled !== false,
    notes: site.notes || ''
  }
}

const saveEditGamingSite = () => {
  if (!editingSiteKey.value) return
  const res = adminStore.updateGamingProviderSite(editingSiteKey.value, { ...editingSiteForm.value })
  if (res?.success) {
    toast.add(`✓ Configuración actualizada para "${res.site.name}".`, 'success')
    editingSiteKey.value = null
  }
}

const toggleSiteEnabled = (site) => {
  const next = site.enabled === false
  adminStore.updateGamingProviderSite(site.key, { enabled: next })
  toast.add(`${next ? '✓ Sitio activado' : '⚠️ Sitio desactivado'}: ${site.name}`, next ? 'success' : 'info')
}

const toggleSiteIncludeInPvp = (site) => {
  const next = site.includeInPvp === false
  adminStore.updateGamingProviderSite(site.key, { includeInPvp: next })
  toast.add(
    next
      ? `📈 "${site.name}" ahora participa en el cálculo de MAYOR COSTO para fijar PVP público.`
      : `🏷️ "${site.name}" quedó solo como proveedor de compra (excluido del Mayor Costo PVP).`,
    'info'
  )
}

const handleDeleteGamingSite = (site) => {
  const res = adminStore.deleteGamingProviderSite(site.key)
  if (res?.error) {
    toast.add(res.error, 'error')
    return
  }
  toast.add(`✓ Sitio "${site.name}" eliminado de la lista de proveedores.`, 'info')
}

const jumpToSiteInComparison = (siteKey) => {
  gamingAdminProviderFilter.value = siteKey
  gamingAdminSubTab.value = 'comparison'
  onlyHighestCostProvider.value = false
}

// ==========================================
// GESTIÓN RÁPIDA DE PROVEEDOR POR JUEGO (BOTÓN DE PROVEEDOR)
// ==========================================
const showProviderQuickModal = ref(false)
const selectedGameForProvider = ref(null)

const openProviderModal = (game) => {
  selectedGameForProvider.value = game
  showProviderQuickModal.value = true
}

const openProviderModalFromBulk = (item) => {
  const listProvEntry = {
    key: 'lista_txt_actual',
    name: `${bulkProviderConfig.value.name || 'Lista TXT'} (Lista Actual)`,
    shortName: 'Lista Actual',
    url: bulkProviderConfig.value.url || '',
    whatsapp: bulkProviderConfig.value.whatsapp || '',
    includeInPvp: true,
    costPrimary: item.newWholesaleCost,
    costSecondary: Math.round(item.newWholesaleCost * 0.55),
    stock: 'En Lista'
  }

  if (item.matchedGame) {
    const baseProviders = (item.matchedGame.activeProvidersList || []).filter(p => p.key !== 'mayorista_txt')
    const mergedList = [listProvEntry, ...baseProviders].sort((a, b) => (a.costPrimary || 9999999) - (b.costPrimary || 9999999))
    const costs = mergedList.map(p => p.costPrimary).filter(c => c > 0)
    const maxC = costs.length ? Math.max(...costs) : item.newWholesaleCost
    const minC = costs.length ? Math.min(...costs) : item.newWholesaleCost
    const diffP = Math.max(0, maxC - minC)
    const diffPct = minC > 0 ? Math.round((diffP / minC) * 100) : 0
    const cheapest = mergedList[0]
    const highest = mergedList.find(p => p.costPrimary === maxC) || mergedList[mergedList.length - 1]
    const marginPct = Number(adminStore.gamingStoreMarginPercent ?? 20)
    const autoPvp = maxC > 0 ? Math.ceil((maxC * (1 + marginPct / 100)) / 100) * 100 : (item.matchedGame.effectivePrimaryPrice || item.proposedPvp)
    mergedList.forEach(p => {
      p.realProfitPrimary = Math.max(0, autoPvp - (p.costPrimary || 0))
      p.extraArbitrage = Math.max(0, maxC - (p.costPrimary || 0))
      p.isHighestPvpDriver = p.costPrimary === maxC
      p.isCheapest = p.costPrimary === minC
    })

    selectedGameForProvider.value = {
      ...item.matchedGame,
      baseCostPrimary: maxC,
      minCostPrimary: minC,
      effectivePrimaryPrice: autoPvp,
      diffPrimary: diffP,
      diffPrimaryPercent: diffPct,
      highestProviderName: highest?.name || 'Mayor Costo Web',
      bestFulfillmentPrimary: cheapest?.key || item.matchedGame.bestFulfillmentPrimary,
      bestFulfillmentPrimaryName: cheapest?.name || item.matchedGame.bestFulfillmentPrimaryName,
      actualPurchaseCostPrimary: cheapest?.costPrimary || minC,
      actualStoreProfitPrimary: Math.max(0, autoPvp - (cheapest?.costPrimary || minC)),
      activeProvidersList: mergedList
    }
  } else {
    listProvEntry.realProfitPrimary = Math.max(0, (item.proposedPvp || 0) - (item.newWholesaleCost || 0))
    listProvEntry.extraArbitrage = 0
    listProvEntry.isHighestPvpDriver = true
    listProvEntry.isCheapest = true
    selectedGameForProvider.value = {
      id: item.id,
      title: item.cleanTitle || item.rawTitle,
      platform: item.platform || 'PS4 / PS5',
      category: 'Nuevo Título (.TXT)',
      subcategory: 'Carga Mayorista',
      image: resolveOfficialGameImage(item.cleanTitle || item.rawTitle, adminStore.processedGamingProducts),
      effectivePrimaryPrice: item.proposedPvp,
      effectiveSecondaryPrice: Math.round(item.proposedPvp * 0.55),
      baseCostPrimary: item.newWholesaleCost,
      minCostPrimary: item.newWholesaleCost,
      highestProviderName: listProvEntry.name,
      actualPurchaseCostPrimary: item.newWholesaleCost,
      actualStoreProfitPrimary: Math.max(0, (item.proposedPvp || 0) - (item.newWholesaleCost || 0)),
      diffPrimary: 0,
      diffPrimaryPercent: 0,
      bestFulfillmentPrimary: 'lista_txt_actual',
      bestFulfillmentPrimaryName: listProvEntry.name,
      activeProvidersList: [listProvEntry]
    }
  }
  showProviderQuickModal.value = true
}

const selectPreferredProvider = (game, providerKey) => {
  if (!game || !providerKey) return
  adminStore.setPreferredGamingProvider(game.id, providerKey)
  toast.add(`✓ Proveedor de compra fijado para "${game.title}". Rentabilidad actualizada.`, 'success')
  const refreshed = (adminStore.processedGamingProducts || []).find(g => g.id === game.id)
  if (selectedGameForProvider.value && selectedGameForProvider.value.id === game.id) {
    selectedGameForProvider.value = refreshed || { ...selectedGameForProvider.value, preferredFulfillmentProvider: providerKey }
  }
}

const openProviderWhatsApp = (game, provider) => {
  if (!provider) return
  const wa = (provider.whatsapp || bulkProviderConfig.value.whatsapp || '').replace(/[^0-9]/g, '')
  const title = game?.title || 'Videojuego'
  const plat = game?.platform || 'PS5'
  const cost = (provider.costPrimary || 0).toLocaleString()
  const msg = encodeURIComponent(`Hola, te consulto por disponibilidad y compra para el videojuego "${title}" (${plat}). Costo cotizado: $${cost} ARS.`)
  if (wa) {
    window.open(`https://wa.me/${wa}?text=${msg}`, '_blank')
  } else {
    window.open(`https://wa.me/5491140506070?text=${msg}`, '_blank')
  }
}

// ==========================================
// CONVENIR PRECIO CON PROVEEDOR
// ==========================================
const showConvenePriceModal = ref(false)
const conveningGame = ref(null)
const conveneForm = ref({
  providerKey: 'argentinaplay',
  targetLicense: 'primary',
  agreedCost: 0,
  customPvp: 0,
  lockCustomPvp: false,
  customProviderName: 'Proveedor Convenio'
})

const openConvenePriceModal = (game, specificProviderKey = null) => {
  conveningGame.value = game
  const activeList = game.activeProvidersList || []
  const pKey = specificProviderKey || game.preferredFulfillmentProvider || game.cheapestProviderKey || activeList[0]?.key || game.visibleProviderKey || 'argentinaplay'
  const pData = game.providers?.[pKey] || activeList.find(p => p.key === pKey) || {}
  const costsAll = activeList.map(p => Number(p.costPrimary || 0)).filter(c => c > 0)
  const agreedCost = pData.costPrimary || pData.primaryPrice || game.actualPurchaseCostPrimary || (costsAll.length ? Math.min(...costsAll) : 0) || game.baseCostPrimary || 20000
  const hasLockedCustom = Boolean(game.customPrimaryPrice && Number(game.customPrimaryPrice) > 0)

  conveneForm.value = {
    providerKey: pKey,
    targetLicense: 'primary',
    agreedCost,
    customPvp: game.effectivePrimaryPrice || 0,
    lockCustomPvp: hasLockedCustom,
    customProviderName: pData.providerName || pData.name || 'Proveedor Convenio'
  }
  showConvenePriceModal.value = true
}

const quickAdoptProviderCost = (providerKey) => {
  if (!conveningGame.value) return
  conveneForm.value.providerKey = providerKey
  const pListItem = (conveningGame.value.activeProvidersList || []).find(p => p.key === providerKey)
  const pData = conveningGame.value.providers?.[providerKey] || pListItem || {}
  const cost = conveneForm.value.targetLicense === 'primary'
    ? (pData.costPrimary || pData.primaryPrice || 0)
    : (pData.costSecondary || pData.secondaryPrice || 0)

  conveneForm.value.agreedCost = cost
  conveneForm.value.customProviderName = pListItem?.name || pData.providerName || providerKey
  const highestBase = Math.max(Number(cost) || 0, Number(conveningGame.value.baseCostPrimary) || 0)
  const calcBase = conveneForm.value.lockCustomPvp ? Number(cost || 0) : highestBase
  const markup = Math.ceil((calcBase * ((adminStore.gamingStoreMarginPercent || 20) / 100)) / 100) * 100
  conveneForm.value.customPvp = calcBase + markup
}

const saveConvenePrice = () => {
  if (!conveningGame.value) return
  const gameId = conveningGame.value.id
  adminStore.conveyGamingProductPrice(gameId, {
    providerKey: conveneForm.value.providerKey,
    agreedCost: Number(conveneForm.value.agreedCost) || 0,
    targetLicense: conveneForm.value.targetLicense,
    customPvp: conveneForm.value.lockCustomPvp ? (Number(conveneForm.value.customPvp) || 0) : null,
    resetCustomPvp: !conveneForm.value.lockCustomPvp
  })
  adminStore.setPreferredGamingProvider(gameId, conveneForm.value.providerKey)
  if (selectedGameForProvider.value && selectedGameForProvider.value.id === gameId) {
    const refreshed = (adminStore.processedGamingProducts || []).find(g => g.id === gameId)
    if (refreshed) selectedGameForProvider.value = refreshed
  }
  toast.add(
    `✓ Precio convenido con ${conveneForm.value.customProviderName} para "${conveningGame.value.title}" ($${Number(conveneForm.value.agreedCost).toLocaleString()} costo${conveneForm.value.lockCustomPvp ? ` / $${Number(conveneForm.value.customPvp).toLocaleString()} PVP fijo` : ' • PVP auto Mayor Costo'}).`,
    'success'
  )
  showConvenePriceModal.value = false
}

// ==========================================
// CARGA MASIVA POR LISTA / TXT & SIMILITUD INTELIGENTE
// ==========================================
const bulkLoaderStep = ref('input') // 'input' | 'analyzed' | 'completed'
const rawGamingListText = ref('')
const bulkProviderConfig = ref({
  name: 'Proveedor Ofertas',
  whatsapp: '+54 9 11 4050-6070',
  url: 'https://mayoristajuegos.com.ar',
  licenseType: 'primary'
})
const bulkTxtFileInput = ref(null)
const isAnalyzingBulkList = ref(false)
const isApplyingBulkBatch = ref(false)
const bulkAnalyzedGames = ref([])
const bulkSkippedHeaders = ref([])
const bulkParsingErrors = ref([])
const bulkFilterStatus = ref('ALL') // 'ALL' | 'matched' | 'new'
const bulkSearchQuery = ref('')

const handleBulkTxtFileUpload = (event) => {
  const file = event.target.files?.[0]
  if (!file) return

  const reader = new FileReader()
  reader.onload = (e) => {
    const text = e.target?.result || ''
    if (text.includes('\uFFFD')) {
      const latinReader = new FileReader()
      latinReader.onload = (ev) => {
        rawGamingListText.value = ev.target?.result || ''
        toast.add(`✓ Archivo "${file.name}" cargado en formato ANSI/Latin-1 (${rawGamingListText.value.length} caracteres).`, 'info')
      }
      latinReader.readAsText(file, 'ISO-8859-1')
      return
    }
    rawGamingListText.value = text
    toast.add(`✓ Archivo "${file.name}" cargado (${rawGamingListText.value.length} caracteres).`, 'info')
  }
  reader.onerror = () => {
    toast.add('Error al leer el archivo de texto', 'error')
  }
  reader.readAsText(file, 'UTF-8')
}

const loadExampleTxtList = () => {
  rawGamingListText.value = `OFERTAS DESTACADAS HASTA EL DÍA 20/11/2026⚠️

🤖ASTRO BOT PS5 - $42.500
🗡️Assassin's Creed Antiquity Pack - $22.400
🗡️Assassin's Creed Rogue Remastered - $11.900
🗡️Assassin's Creed®️ Odyssey - DELUXE EDITION ESPAÑOL - $19.500
🗡️Assassin's Creed®️ Odyssey - GOLD EDITION ESPAÑOL - $22.400
🗡️Assassin's Creed®️ Unity - $10.400
🗡️Assassin’s Creed®️ Syndicate - $11.900
🗡️Assassin’s Creed®️ The Ezio Collection - $15.500
🦇Batman™️: Arkham Knight - $7.900
🔫Bayonetta - $9.200
🏫Bully (Canis Canem Edit) - $14.100
⚽CAPTAIN TSUBASA 2: WORLD FIGHTERS - Ultimate Edition PS5 - $72.600
⚽CAPTAIN TSUBASA 2: WORLD FIGHTERS PS5 - $52.500
🔧Car Mechanic Simulator 2021 - $8.800
🌀Control Ultimate Edition - $8.800
⚔️DARK SOULS™️ II: Scholar of the First Sin - $22.400
⚔️DARK SOULS™️ III - $32.400
⚔️DARK SOULS™️ III - Deluxe Edition - $47.800
⚔️DARK SOULS™️: REMASTERED - $22.400
🚚DEATH STRANDING 2: ON THE BEACH PS5 - $42.500
🚚DEATH STRANDING DIRECTOR’S CUT PS5 - $24.600
😈Diablo®️ IV - Standard Edition - $32.400
💀DOOM Eternal Standard Edition - $12.800
🐉DRAGON BALL Z: KAKAROT DAIMA EDITION - $27.700
🎮EA Family Bundle - $10.900
🔫Far Cry 4 - Gold Edition - $14.000
🔫FAR CRY 5 - $11.900
🔫Far Cry New Dawn - $10.900
🔫Far Cry Primal - $10.400
🔫Far Cry®️ 6 Deluxe Edition - $9.700
🔫FAR CRY®️6 Gold Edition - $10.500
⚔️God Of War 2018 ESPAÑOL LATINO - $12.800
⚔️God of War Ragnarök PS4 ESPAÑOL LATINO - $27.700
⚔️God of War Ragnarök PS5 ESPAÑOL LATINO - $32.400
🎯HITMAN World of Assassination - $32.400
🕵️Judgment PS4 - $10.400
🕵️Judgment PS5 - $12.800
💥Just Cause 3: XXL Edition - $4.300
🌿Kena: Bridge of Spirits - $19.500
🦇LEGO®️ Batman™️ 3: Más Allá de Gotham - $7.900
🚓LEGO®️ CITY Undercover - $8.000
🦸LEGO®️ Marvel Super Heroes 2 Deluxe Edition - $9.800
🦸LEGO®️ Marvel’s Avengers Deluxe Edition - $8.800
🌌LEGO®️ Star Wars™️: The Force Awakens Standard Edition - $7.900
🧱LEGO®️ The Hobbit - $7.900
😱Little Nightmares I & II Bundle - $18.500
🕵️Lost Judgment Digital Ultimate Edition - $8.900
🕷️Marvel's Spider-Man: Miles Morales Ultimate Edition PS5 - $32.400
🦸Marvel’s Wolverine Digital Deluxe Edition PS5 - $62.600
🐍METAL GEAR SOLID V: THE DEFINITIVE EXPERIENCE - $15.500
🐍METAL GEAR SOLID: MASTER COLLECTION Vol.1 - $15.600
⚔️Middle-earth™️: The Shadow Bundle - $9.800
🏍️MotoGP™️24 - $8.800
⚔️Mount & Blade II: Bannerlord - $27.700
🏍️MX vs ATV Legends - $12.800
🍥NARUTO SHIPPUDEN: Ultimate Ninja STORM Legacy - $32.400
😱Outlast 1 - $6.600
😱Outlast: Bundle of Terror - $7.900
🍳Overcooked! 2 - $11.300
🤖Ratchet & Clank™️ - $12.800
🧸Sackboy: A Big Adventure - $29.300
👻SILENT HILL 2 PS5 - $22.400
👻SILENT HILL f PS5 - $22.400
🦔Sonic Colours: Ultimate - Digital Deluxe - $7.900
🏎️The Crew®️ 2 Special Edition - $15.500
🐉The Elder Scrolls V: Skyrim Special Edition - $10.900
😱The Evil Within 2 ESPAÑOL LATINO - $10.900
🧟The Last Of Us™️ Remastered PS4 ESPAÑOL LATINO - $12.800
🥋The Warriors®️ - $11.900
🦌TheHunter: Call Of The Wild™️ - $7.300
🔫Tom Clancy's Ghost Recon Wildlands - $12.800
🔫Wolfenstein: Alt History Collection - $15.500
🏎️WRC 10 FIA World Rally Championship PS4 - $10.900
🏎️WRC 10 FIA World Rally Championship PS5 - $10.900

OFERTAS HASTA EL DÍA 20/11/2026⚠️

🕵️007 First Light - Deluxe Edition PS5 - $42.500
🐀A Plague Tale Bundle PS5 - $32.400
⚔️Absolum - $18.500
🔫Arma Reforger PS5 - $32.400
🌸Beast of Reincarnation PS5 - $47.800
⚔️Blasphemous 2 - Mea Culpa Edition - $16.500
🚌Bus Simulator 21 Next Stop - Gold Edition - $19.500
🚌Bus Simulator 27 PS5 - $32.400
🧗Cairn PS5 - $25.100
⚔️Castle Crashers Remastered - $7.300
⚔️Darksiders II Deathinitive Edition - $14.000
⚔️Darksiders III Digital Deluxe Edition - $7.900
🦎Darwin's Paradox! PS5 - $12.800
🧟DayZ - $31.700
🧟Dead Island Definitive Edition - $5.800
🧟Dead Island: Riptide Definitive Edition - $5.800`
  toast.add('✓ Lista de ejemplo de 197 juegos cargada con éxito en el área de texto.', 'success')
}

const analyzeGamingList = () => {
  if (!rawGamingListText.value.trim()) {
    toast.add('Por favor ingresa o carga una lista de videojuegos para analizar', 'error')
    return
  }

  isAnalyzingBulkList.value = true
  try {
    const { games, headers, errors } = parseGamingTextList(rawGamingListText.value)
    bulkSkippedHeaders.value = headers
    bulkParsingErrors.value = errors

    if (games.length === 0) {
      toast.add('No se pudieron detectar videojuegos con precios en el texto proporcionado', 'error')
      isAnalyzingBulkList.value = false
      return
    }

    const matched = matchGamingListWithCatalog(games, adminStore.processedGamingProducts, {
      storeMarginPercent: adminStore.gamingStoreMarginPercent,
      resellerCommissionPercent: adminStore.gamingResellerCommissionPercent,
      minResellerProfit: adminStore.gamingMinResellerProfit
    })

    bulkAnalyzedGames.value = matched
    bulkLoaderStep.value = 'analyzed'
    toast.add(`✓ Análisis completado: ${matched.length} videojuegos detectados (${matched.filter(m => !m.isNewGame).length} coincidencias, ${matched.filter(m => m.isNewGame).length} nuevos).`, 'success')
  } catch (err) {
    console.error('Error analizando lista gamer:', err)
    toast.add('Ocurrió un error al analizar la lista de videojuegos', 'error')
  } finally {
    isAnalyzingBulkList.value = false
  }
}

const filteredAnalyzedBulkGames = computed(() => {
  let list = bulkAnalyzedGames.value || []
  if (bulkFilterStatus.value === 'matched') {
    list = list.filter(i => !i.isNewGame)
  } else if (bulkFilterStatus.value === 'new') {
    list = list.filter(i => i.isNewGame)
  } else if (bulkFilterStatus.value === 'cheaper_list') {
    list = list.filter(i => i.isCheaperInList)
  } else if (bulkFilterStatus.value === 'cheaper_webs') {
    list = list.filter(i => i.isCheaperInWebs)
  }

  const rawQ = bulkSearchQuery.value.trim()
  if (rawQ) {
    const cleanNum = rawQ.replace(/[$.,\s]/g, '')
    const isNumSearch = /^\d{3,7}$/.test(cleanNum)
    list = list.filter(i => {
      if (isNumSearch && String(i.price).includes(cleanNum)) return true
      if (smartSearchGamingMatch({ title: i.rawTitle, activeProvidersList: i.scrapedProviders || [], baseCostPrimary: i.price, effectivePrimaryPrice: i.proposedPvp }, rawQ)) return true
      if (i.matchedGame && smartSearchGamingMatch(i.matchedGame, rawQ)) return true
      return false
    })
  }
  return list
})

const selectedBulkCount = computed(() => {
  return bulkAnalyzedGames.value.filter(i => i.selected).length
})

const toggleAllBulkSelection = (select) => {
  bulkAnalyzedGames.value.forEach(i => i.selected = select)
}

const applyBulkBatchToCatalog = async () => {
  const selectedItems = bulkAnalyzedGames.value.filter(i => i.selected)
  if (selectedItems.length === 0) {
    toast.add('Selecciona al menos un juego para convenir y aplicar precios', 'error')
    return
  }

  isApplyingBulkBatch.value = true
  try {
    const res = adminStore.applyGamingBatchFromList(selectedItems, bulkProviderConfig.value)
    if (res.error) throw new Error(res.error)

    toast.add(`✓ ¡Éxito! Se actualizaron ${res.updatedMatchesCount} coincidencias y se añadieron ${res.createdNewCount} nuevos juegos de "${bulkProviderConfig.value.name}".`, 'success')
    bulkLoaderStep.value = 'completed'
  } catch (err) {
    console.error('Error aplicando lote de videojuegos:', err)
    toast.add(err.message || 'Error al aplicar el lote al catálogo', 'error')
  } finally {
    isApplyingBulkBatch.value = false
  }
}

// ==========================================
// ESTRATEGIA DE PRECIOS & MÁRGENES GAMING
// ==========================================
const gamingMarginForm = ref({
  storeMargin: adminStore.gamingStoreMarginPercent ?? 20,
  resellerCommission: adminStore.gamingResellerCommissionPercent ?? 10,
  minResellerProfit: adminStore.gamingMinResellerProfit ?? 1000,
  simulatedCost: 25000
})
const isSavingGamingMargins = ref(false)

watch(() => [adminStore.gamingStoreMarginPercent, adminStore.gamingResellerCommissionPercent, adminStore.gamingMinResellerProfit], ([sm, rc, mrp]) => {
  if (typeof sm === 'number') gamingMarginForm.value.storeMargin = sm
  if (typeof rc === 'number') gamingMarginForm.value.resellerCommission = rc
  if (typeof mrp === 'number') gamingMarginForm.value.minResellerProfit = mrp
})

const simulatedCalculation = computed(() => {
  const cost = Math.max(0, Number(gamingMarginForm.value.simulatedCost) || 0)
  const storeMarginPct = Math.max(0, Number(gamingMarginForm.value.storeMargin) || 0)
  const resellerCommissionPct = Math.max(0, Number(gamingMarginForm.value.resellerCommission) || 0)
  const minResellerProfit = Math.max(0, Number(gamingMarginForm.value.minResellerProfit) || 0)

  const markup = Math.ceil((cost * (storeMarginPct / 100)) / 100) * 100
  const pvp = cost + markup
  const resellerCut = Math.max(minResellerProfit, Math.round(pvp * (resellerCommissionPct / 100)))
  const storeGrossMargin = markup
  const storeNetWithReseller = Math.max(0, storeGrossMargin - resellerCut)
  const storeEffectiveNetPct = pvp > 0 ? Math.round((storeNetWithReseller / pvp) * 100) : 0

  return {
    cost,
    markup,
    pvp,
    resellerCut,
    storeGrossMargin,
    storeNetWithReseller,
    storeEffectiveNetPct
  }
})

const applyStoreMarginPreset = (pct) => {
  gamingMarginForm.value.storeMargin = pct
}

const applyResellerCommissionPreset = (pct) => {
  gamingMarginForm.value.resellerCommission = pct
}

const applyMinResellerProfitPreset = (amt) => {
  gamingMarginForm.value.minResellerProfit = amt
}

const saveGamingMarginSettings = async () => {
  try {
    isSavingGamingMargins.value = true
    await adminStore.updateGamingMargins({
      storeMargin: Number(gamingMarginForm.value.storeMargin) || 0,
      resellerCommission: Number(gamingMarginForm.value.resellerCommission) || 0,
      minResellerProfit: Number(gamingMarginForm.value.minResellerProfit) || 0
    })
    toast.add(`✓ Estrategia guardada: +${gamingMarginForm.value.storeMargin}% Ganancia Tienda y ${gamingMarginForm.value.resellerCommission}% Comisión Revendedor aplicados a los ${adminStore.processedGamingProducts.length} juegos.`, 'success')
  } catch (err) {
    console.error('Error saving gaming margins:', err)
    toast.add('Error al guardar la configuración de márgenes', 'error')
  } finally {
    isSavingGamingMargins.value = false
  }
}

watch(gamingAdminCategoryFilter, () => {
  gamingAdminSubcategoryFilter.value = 'Todas'
})

const availableAdminGamingSubcategories = computed(() => {
  const currentCat = gamingAdminCategoryFilter.value || 'Todas'
  const map = adminStore.gamingSubcategories || {}
  if (map[currentCat] && Array.isArray(map[currentCat]) && map[currentCat].length > 1) {
    return map[currentCat]
  }
  const set = new Set(['Todas'])
  const list = adminStore.processedGamingProducts || []
  list.forEach(game => {
    let matchesCat = false
    if (currentCat === 'Todas' || !currentCat) matchesCat = true
    else if (currentCat === 'PS5') matchesCat = Boolean(game.platform && game.platform.includes('PS5'))
    else if (currentCat === 'PS4') matchesCat = Boolean(game.platform && game.platform.includes('PS4'))
    else if (currentCat === 'PS3') matchesCat = Boolean(game.platform && game.platform.includes('PS3'))
    else if (currentCat === 'Lanzamientos') matchesCat = Boolean(game.badge && (game.badge.includes('LANZAMIENTO') || game.badge.includes('PREVENTA') || game.badge.includes('2026') || game.badge.includes('2025') || game.badge.includes('2024') || game.badge.includes('MÁS VENDIDO') || game.badge.includes('JUEGO DEL AÑO')))
    else matchesCat = game.category === currentCat

    if (matchesCat && game.subcategory) set.add(game.subcategory)
  })
  return Array.from(set)
})

const showGamingPriceEditModal = ref(false)
const showGamingNewModal = ref(false)
const editingGamingProduct = ref(null)
const gamingEditForm = ref({
  id: '',
  title: '',
  platform: 'PS4 / PS5',
  category: 'Acción & Aventura',
  preferredFulfillmentProvider: '',
  useCustomPvp: false,
  customPrimaryPrice: 0,
  customSecondaryPrice: 0,
  providersQuotes: []
})

const gamingNewForm = ref({
  title: '',
  platform: 'PS4 / PS5',
  category: 'Acción & Aventura',
  badge: '🔥 LANZAMIENTO',
  image: '',
  description: '',
  primarySiteKey: 'argentinaplay',
  costPrimary: 20000,
  costSecondary: 10000,
  secondarySiteKey: 'juegosdigitalesargentina',
  altCostPrimary: 21000,
  altCostSecondary: 9500
})

const filteredGamingForAdmin = computed(() => {
  const list = adminStore.processedGamingProducts || []
  let filtered = list.filter(game => {
    let matchesCat = false
    if (gamingAdminCategoryFilter.value === 'Todas' || !gamingAdminCategoryFilter.value) {
      matchesCat = true
    } else if (gamingAdminCategoryFilter.value === 'PS5') {
      matchesCat = Boolean(game.platform && game.platform.includes('PS5'))
    } else if (gamingAdminCategoryFilter.value === 'PS4') {
      matchesCat = Boolean(game.platform && game.platform.includes('PS4'))
    } else if (gamingAdminCategoryFilter.value === 'PS3') {
      matchesCat = Boolean(game.platform && game.platform.includes('PS3'))
    } else if (gamingAdminCategoryFilter.value === 'Lanzamientos') {
      matchesCat = Boolean(game.badge && (game.badge.includes('LANZAMIENTO') || game.badge.includes('PREVENTA') || game.badge.includes('2026') || game.badge.includes('2025') || game.badge.includes('2024') || game.badge.includes('TOP') || game.badge.includes('MÁS VENDIDO') || game.badge.includes('FENÓMENO') || game.badge.includes('JUEGO DEL AÑO')))
    } else {
      matchesCat = game.category === gamingAdminCategoryFilter.value
    }
    const matchesSubcat = gamingAdminSubcategoryFilter.value === 'Todas' || !gamingAdminSubcategoryFilter.value || game.subcategory === gamingAdminSubcategoryFilter.value
    const matchesPlatform = gamingAdminPlatformFilter.value === 'ALL' || (game.platform && game.platform.includes(gamingAdminPlatformFilter.value))
    const matchesQ = smartSearchGamingMatch(game, gamingAdminSearch.value)

    // Filtro por Proveedor / Web Scrapeada específica
    if (gamingAdminProviderFilter.value !== 'ALL') {
      const hasProv = (game.activeProvidersList || []).some(p => p.key === gamingAdminProviderFilter.value)
      if (!hasProv) return false
    }
    
    // Filtro por Rango de Precios (sobre PVP o cualquier costo mayorista scrapeado)
    const price = Number(game.effectivePrimaryPrice || 0)
    const costMin = Number(game.minCostPrimary || game.baseCostPrimary || 0)
    if (gamingAdminMinPrice.value && price < Number(gamingAdminMinPrice.value) && costMin < Number(gamingAdminMinPrice.value)) return false
    if (gamingAdminMaxPrice.value && price > Number(gamingAdminMaxPrice.value) && costMin > Number(gamingAdminMaxPrice.value)) return false

    // Filtro por Diferencias / Arbitraje
    if (gamingAdminDifferenceFilter.value === 'with_diff') {
      const hasDiff = Number(game.diffPrimary || 0) > 0 || (game.providerCount && game.providerCount > 1)
      if (!hasDiff) return false
    } else if (gamingAdminDifferenceFilter.value === 'conveyed') {
      const isConveyed = Boolean(game.lastConveyedDate || game.lastConveyedProvider)
      if (!isConveyed) return false
    } else if (gamingAdminDifferenceFilter.value === 'single') {
      const isSingle = !(Number(game.diffPrimary || 0) > 0 || (game.providerCount && game.providerCount > 1))
      if (!isSingle) return false
    }

    return matchesCat && matchesSubcat && matchesPlatform && matchesQ
  })

  // Ordenamiento por Diferencia / Precio / Costo / Margen
  if (gamingAdminPriceSort.value === 'diff_desc') {
    filtered.sort((a, b) => (Number(b.diffPrimary || 0) - Number(a.diffPrimary || 0)))
  } else if (gamingAdminPriceSort.value === 'price_desc') {
    filtered.sort((a, b) => (Number(b.effectivePrimaryPrice || 0) - Number(a.effectivePrimaryPrice || 0)))
  } else if (gamingAdminPriceSort.value === 'price_asc') {
    filtered.sort((a, b) => (Number(a.effectivePrimaryPrice || 0) - Number(b.effectivePrimaryPrice || 0)))
  } else if (gamingAdminPriceSort.value === 'cost_desc') {
    filtered.sort((a, b) => (Number(b.baseCostPrimary || 0) - Number(a.baseCostPrimary || 0)))
  } else if (gamingAdminPriceSort.value === 'margin_desc') {
    filtered.sort((a, b) => (Number(b.actualStoreProfitPrimary ?? b.storeGrossMarginPrimary ?? 0) - Number(a.actualStoreProfitPrimary ?? a.storeGrossMarginPrimary ?? 0)))
  }

  return filtered
})

// Paginación Reactiva Ultra-Fluida para Videojuegos Gaming
const gamingAdminCurrentPage = ref(1)
const gamingAdminPageSize = ref(30)
const totalGamingAdminPages = computed(() => {
  return Math.max(1, Math.ceil((filteredGamingForAdmin.value?.length || 0) / gamingAdminPageSize.value))
})
const paginatedGamingForAdmin = computed(() => {
  const list = filteredGamingForAdmin.value || []
  const start = (gamingAdminCurrentPage.value - 1) * gamingAdminPageSize.value
  return list.slice(start, start + gamingAdminPageSize.value)
})
watch([gamingAdminSearch, gamingAdminCategoryFilter, gamingAdminSubcategoryFilter, gamingAdminPlatformFilter, gamingAdminProviderFilter, onlyHighestCostProvider, gamingAdminSubTab, gamingAdminPriceSort, gamingAdminMinPrice, gamingAdminMaxPrice, gamingAdminDifferenceFilter, gamingComparisonViewMode], () => {
  gamingAdminCurrentPage.value = 1
})

const gamingEditLivePreview = computed(() => {
  const quotes = (gamingEditForm.value.providersQuotes || []).filter(
    q => q.available !== false && (Number(q.costPrimary) > 0 || Number(q.costSecondary) > 0)
  )
  const pvpCandidates = quotes.filter(q => q.enabled !== false && q.includeInPvp !== false)
  const effPvpList = pvpCandidates.length > 0 ? pvpCandidates : quotes.filter(q => q.enabled !== false)

  const priCostsPvp = effPvpList.map(q => Number(q.costPrimary) || 0).filter(c => c > 0)
  const secCostsPvp = effPvpList.map(q => Number(q.costSecondary) || 0).filter(c => c > 0)
  const priCostsAll = quotes.filter(q => q.enabled !== false).map(q => Number(q.costPrimary) || 0).filter(c => c > 0)
  const secCostsAll = quotes.filter(q => q.enabled !== false).map(q => Number(q.costSecondary) || 0).filter(c => c > 0)

  const maxCostPrimary = priCostsPvp.length > 0 ? Math.max(...priCostsPvp) : 0
  const minCostPrimary = priCostsAll.length > 0 ? Math.min(...priCostsAll) : maxCostPrimary
  const maxCostSecondary = secCostsPvp.length > 0 ? Math.max(...secCostsPvp) : 0
  const minCostSecondary = secCostsAll.length > 0 ? Math.min(...secCostsAll) : maxCostSecondary

  const storeMarginPct = Number(adminStore.gamingStoreMarginPercent ?? 20)
  const autoPvpPrimary = maxCostPrimary > 0 ? Math.ceil((maxCostPrimary * (1 + storeMarginPct / 100)) / 100) * 100 : 0
  const autoPvpSecondary = maxCostSecondary > 0 ? Math.ceil((maxCostSecondary * (1 + storeMarginPct / 100)) / 100) * 100 : 0

  const finalPvpPrimary = (gamingEditForm.value.useCustomPvp && Number(gamingEditForm.value.customPrimaryPrice) > 0)
    ? Number(gamingEditForm.value.customPrimaryPrice)
    : autoPvpPrimary
  const finalPvpSecondary = (gamingEditForm.value.useCustomPvp && Number(gamingEditForm.value.customSecondaryPrice) > 0)
    ? Number(gamingEditForm.value.customSecondaryPrice)
    : autoPvpSecondary

  const highestQuote = effPvpList.find(q => Number(q.costPrimary) === maxCostPrimary)
  const cheapestQuote = quotes.filter(q => q.enabled !== false && Number(q.costPrimary) > 0).sort((a, b) => Number(a.costPrimary) - Number(b.costPrimary))[0]
  const prefQuote = gamingEditForm.value.preferredFulfillmentProvider
    ? quotes.find(q => q.key === gamingEditForm.value.preferredFulfillmentProvider && Number(q.costPrimary) > 0)
    : null
  const buyerQuote = prefQuote || cheapestQuote
  const buyCostPrimary = buyerQuote ? Number(buyerQuote.costPrimary) : minCostPrimary
  const realProfitPrimary = Math.max(0, finalPvpPrimary - buyCostPrimary)
  const arbitrageExtra = Math.max(0, maxCostPrimary - buyCostPrimary)

  return {
    maxCostPrimary,
    minCostPrimary,
    maxCostSecondary,
    minCostSecondary,
    autoPvpPrimary,
    autoPvpSecondary,
    finalPvpPrimary,
    finalPvpSecondary,
    highestSiteName: highestQuote?.name || 'Mayor Costo',
    cheapestSiteName: cheapestQuote?.name || 'Menor Costo',
    chosenBuyerName: buyerQuote?.name || 'Automático',
    buyCostPrimary,
    realProfitPrimary,
    arbitrageExtra,
    quotedCount: quotes.length
  }
})

const openGamingPriceEdit = (game) => {
  adminStore.ensureGamingProviderSitesLoaded()
  editingGamingProduct.value = game
  const sites = adminStore.gamingProviderSites || []
  const rawProviders = game.providers || {}
  const quotes = []
  const seenKeys = new Set()

  sites.forEach(site => {
    seenKeys.add(site.key)
    const p = rawProviders[site.key] || {}
    const cPri = Number(p.costPrimary || p.primaryPrice || 0)
    const cSec = Number(p.costSecondary || p.secondaryPrice || 0)
    quotes.push({
      key: site.key,
      name: site.name,
      shortName: site.shortName || site.name,
      badge: site.badge || site.name,
      url: site.url || p.providerUrl || '',
      whatsapp: site.whatsapp || p.providerWhatsapp || '',
      tagColor: site.tagColor || 'bg-indigo-500/20 text-indigo-300 border-indigo-500/30',
      enabled: site.enabled !== false,
      includeInPvp: site.includeInPvp !== false,
      available: p.available !== false && (cPri > 0 || cSec > 0),
      costPrimary: cPri,
      costSecondary: cSec,
      stock: p.stock || 'Inmediato'
    })
  })

  Object.entries(rawProviders).forEach(([k, p]) => {
    if (seenKeys.has(k) || !p) return
    const cPri = Number(p.costPrimary || p.primaryPrice || 0)
    const cSec = Number(p.costSecondary || p.secondaryPrice || 0)
    quotes.push({
      key: k,
      name: p.providerName || k,
      shortName: (p.providerName || k).slice(0, 12),
      badge: p.providerName || k,
      url: p.providerUrl || '',
      whatsapp: p.providerWhatsapp || '',
      tagColor: 'bg-teal-500/20 text-teal-300 border-teal-500/30',
      enabled: true,
      includeInPvp: true,
      available: p.available !== false && (cPri > 0 || cSec > 0),
      costPrimary: cPri,
      costSecondary: cSec,
      stock: p.stock || 'Inmediato'
    })
  })

  gamingEditForm.value = {
    id: game.id,
    title: game.title,
    platform: game.platform,
    category: game.category,
    preferredFulfillmentProvider: game.preferredFulfillmentProvider || '',
    useCustomPvp: Boolean(game.customPrimaryPrice || game.customSecondaryPrice),
    customPrimaryPrice: Number(game.customPrimaryPrice || game.effectivePrimaryPrice || 0),
    customSecondaryPrice: Number(game.customSecondaryPrice || game.effectiveSecondaryPrice || 0),
    providersQuotes: quotes
  }
  showGamingPriceEditModal.value = true
}

const saveGamingPriceEdit = () => {
  if (!editingGamingProduct.value) return
  const providersMap = {}
  ;(gamingEditForm.value.providersQuotes || []).forEach(q => {
    const cPri = Math.max(0, Number(q.costPrimary) || 0)
    const cSec = Math.max(0, Number(q.costSecondary) || 0)
    providersMap[q.key] = {
      providerName: q.name,
      providerUrl: q.url || '',
      providerWhatsapp: q.whatsapp || '',
      available: q.available !== false && (cPri > 0 || cSec > 0),
      costPrimary: cPri,
      costSecondary: cSec,
      stock: q.stock || 'Inmediato'
    }
  })

  adminStore.updateGameMultiProviders(editingGamingProduct.value.id, {
    providersMap,
    preferredFulfillmentProvider: gamingEditForm.value.preferredFulfillmentProvider || '',
    customPrimaryPrice: gamingEditForm.value.useCustomPvp ? Number(gamingEditForm.value.customPrimaryPrice) || 0 : 0,
    customSecondaryPrice: gamingEditForm.value.useCustomPvp ? Number(gamingEditForm.value.customSecondaryPrice) || 0 : 0,
    resetCustomPvp: !gamingEditForm.value.useCustomPvp
  })

  const preview = gamingEditLivePreview.value
  toast.add(
    `✓ Cotizaciones guardadas para "${gamingEditForm.value.title}". PVP Web (Mayor Costo): $${(preview.finalPvpPrimary || 0).toLocaleString()} ARS • Compra en: ${preview.chosenBuyerName}.`,
    'success'
  )
  showGamingPriceEditModal.value = false
}

const saveNewGamingProduct = () => {
  if (!gamingNewForm.value.title.trim()) {
    toast.add('Ingresa el título del videojuego', 'error')
    return
  }
  adminStore.ensureGamingProviderSitesLoaded()
  const sites = adminStore.gamingProviderSites || []
  const site1 = sites.find(s => s.key === gamingNewForm.value.primarySiteKey) || { key: gamingNewForm.value.primarySiteKey || 'argentinaplay', name: 'Argentina Play', url: 'https://www.argentinaplay.com.ar/' }
  const site2 = sites.find(s => s.key === gamingNewForm.value.secondarySiteKey)

  const c1Pri = Math.max(0, Number(gamingNewForm.value.costPrimary) || 0)
  const c1Sec = Math.max(0, Number(gamingNewForm.value.costSecondary) || 0)
  const c2Pri = Math.max(0, Number(gamingNewForm.value.altCostPrimary) || 0)
  const c2Sec = Math.max(0, Number(gamingNewForm.value.altCostSecondary) || 0)

  const providers = {}
  if (c1Pri > 0 || c1Sec > 0) {
    providers[site1.key] = {
      providerName: site1.name,
      providerUrl: site1.url || '',
      providerWhatsapp: site1.whatsapp || '',
      available: true,
      primaryPrice: c1Pri,
      secondaryPrice: c1Sec,
      costPrimary: c1Pri,
      costSecondary: c1Sec,
      stock: 'Inmediato'
    }
  }
  if (site2 && site2.key !== site1.key && (c2Pri > 0 || c2Sec > 0)) {
    providers[site2.key] = {
      providerName: site2.name,
      providerUrl: site2.url || '',
      providerWhatsapp: site2.whatsapp || '',
      available: true,
      primaryPrice: c2Pri,
      secondaryPrice: c2Sec,
      costPrimary: c2Pri,
      costSecondary: c2Sec,
      stock: 'Inmediato'
    }
  }

  const maxPri = Math.max(c1Pri, c2Pri, 20000)
  const maxSec = Math.max(c1Sec, c2Sec, 10000)
  const marginPct = Number(adminStore.gamingStoreMarginPercent ?? 20)
  const newGame = {
    id: `GAME-${Date.now().toString().slice(-4)}`,
    title: gamingNewForm.value.title.trim(),
    slug: gamingNewForm.value.title.toLowerCase().replace(/[^a-z0-9]/g, '-'),
    platform: gamingNewForm.value.platform,
    category: gamingNewForm.value.category,
    badge: gamingNewForm.value.badge || '🔥 NUEVO',
    image: gamingNewForm.value.image || 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?w=600&auto=format&fit=crop&q=80',
    description: gamingNewForm.value.description || 'Juego digital oficial para consolas PlayStation Network.',
    features: ['Audio Español Latino', 'Entrega 60/90min', 'Garantía Total'],
    baseCostPrimary: maxPri,
    baseCostSecondary: maxSec,
    effectivePrimaryPrice: Math.ceil((maxPri * (1 + marginPct / 100)) / 100) * 100,
    effectiveSecondaryPrice: Math.ceil((maxSec * (1 + marginPct / 100)) / 100) * 100,
    visibleProviderKey: site1.key,
    visibleProviderName: 'PlayStation Store Oficial',
    visibleProviderShortName: 'PS Store',
    visibleProviderBadge: '🎮 PlayStation Store Oficial',
    primaryPriceLeaderName: 'PlayStation Store Oficial',
    secondaryPriceLeaderName: 'PlayStation Store Oficial',
    providers
  }

  adminStore.addGamingProduct(newGame)
  toast.add(`✓ Videojuego "${newGame.title}" agregado exitosamente y calculado con Mayor Costo para PVP.`, 'success')
  showGamingNewModal.value = false
  gamingNewForm.value.title = ''
}

const sendWhatsAppGameDelivery = (order) => {
  const phone = formatWaPhone(order.customerPhone)
  const isPrim = order.licenseType === 'primary' || (order.planLabel && order.planLabel.includes('Primaria'))
  const acc = order.accountDetails
  const portalUrl = 'https://streamepro.me/portal'

  const credsBlock = acc && acc.email
    ? `🔐 *TUS CREDENCIALES DE ACCESO:*\n` +
      `📧 *Correo:* ${acc.email}\n` +
      `🔑 *Contraseña:* ${acc.pass || '-'}\n` +
      (acc.pin ? `📌 *Código 2FA / PIN:* ${acc.pin}\n` : '') +
      (acc.notes ? `📝 *Nota:* ${acc.notes}\n` : '') + `\n`
    : ''

  const msg = `¡Hola ${order.customerName || 'Gamer'}! 🎮 Tu juego digital en *StreamePro* ${acc?.email ? 'ya está despachado ✅' : 'está listo para instalar'}:\n\n` +
    `🎯 *Juego:* ${order.gameTitle || order.platform || 'Juego Digital'}\n` +
    `🔑 *Modalidad:* ${isPrim ? '⭐️ Cuenta Primaria (Juegas con tu usuario personal)' : '⚡️ Cuenta Secundaria (Juegas con usuario provisto)'}\n` +
    `🛡️ *Entrega:* Licencia Digital Oficial StreamePro\n\n` +
    credsBlock +
    `📋 *Instrucciones Rápidas de Instalación:*\n` +
    `1. Enciende tu consola y crea un *Nuevo Usuario*.\n` +
    `2. Inicia sesión con el correo y contraseña entregados.\n` +
    `3. ${isPrim ? 'Activa la consola como Principal / Uso compartido y descarga el juego desde Biblioteca. ¡Luego vuelve a tu usuario personal!' : 'Descarga el juego y disfrútalo directamente desde este usuario con conexión a internet.'}\n\n` +
    `📱 También puedes ver tus accesos e instrucciones completas aquí:\n${portalUrl}` +
    (acc?.email ? '' : '\n\n¿Confirmas recepción para enviarte las credenciales?')
  window.open(`https://wa.me/${phone}?text=${encodeURIComponent(msg)}`, '_blank')
}

// ==========================================
// GESTIÓN AVANZADA DE VENTAS, POS, CUPONES, SALUD Y DESPACHO GAMER
// ==========================================
const flyerInitialProduct = ref(null)
const showSharePlaqueModal = ref(false)
const sharePlaquePayload = ref(null)
const adminPlaqueContext = ref({
  storeName: '',
  resellerRef: 'DIRECT',
  contactPhone: ''
})

const openAdminPlaqueForGaming = (game) => {
  if (!game) return
  adminPlaqueContext.value = {
    storeName: adminStore.adminAuth.name || 'StreamePro Oficial',
    resellerRef: 'DIRECT',
    contactPhone: adminStore.providerBankInfo.whatsapp || adminStore.adminAuth.whatsapp || ''
  }
  sharePlaquePayload.value = {
    mode: 'single',
    productType: 'gaming',
    id: game.id,
    licenseType: 'primary',
    title: game.title,
    subtitle: `${game.platform || 'PS4 / PS5'} · Cuenta Primaria Oficial · Entrega 60/90 min`,
    priceNumeric: Number(game.effectivePrimaryPrice || game.effectiveSecondaryPrice || 0),
    image: game.image || '',
    tag: `${game.platform || 'DIGITAL'} · PRIMARIA`
  }
  showSharePlaqueModal.value = true
}

const openFlyerForGaming = (game) => {
  if (!game) {
    showFlyerModal.value = true
    return
  }
  flyerInitialProduct.value = {
    itemType: 'gaming',
    gameId: game.id,
    id: game.id,
    title: game.title,
    category: 'Videojuegos Digitales',
    licenseType: 'primary',
    platform: game.platform,
    image: game.image,
    effectivePrimaryPrice: game.effectivePrimaryPrice,
    effectiveSecondaryPrice: game.effectiveSecondaryPrice,
    priceNumeric: game.effectivePrimaryPrice
  }
  showFlyerModal.value = true
}

const gamingOrderSearch = ref('')
const gamingOrderStatusFilter = ref('ALL') // 'ALL' | 'PENDING' | 'DISPATCHED'
const gamingOrderProviderFilter = ref('ALL')
const gamingOrderLicenseFilter = ref('ALL') // 'ALL' | 'primary' | 'secondary'
const gamingOrderCurrentPage = ref(1)
const gamingOrderPageSize = ref(15)

const filteredGamingOrdersForAdmin = computed(() => {
  let list = adminStore.gamingOrders || []

  // Filtro por Estado
  if (gamingOrderStatusFilter.value === 'PENDING') {
    list = list.filter(o => o.status !== 'Dispatched' && o.status !== 'Entregado')
  } else if (gamingOrderStatusFilter.value === 'DISPATCHED') {
    list = list.filter(o => o.status === 'Dispatched' || o.status === 'Entregado')
  }

  // Filtro por Proveedor
  if (gamingOrderProviderFilter.value !== 'ALL') {
    const filterKey = gamingOrderProviderFilter.value
    const siteMeta = (adminStore.gamingProviderSites || []).find(s => s.key === filterKey)
    const siteNameLower = (siteMeta?.name || '').toLowerCase()
    list = list.filter(o => {
      if (o.fulfillmentProvider === filterKey) return true
      const prov = (o.fulfillmentProvider || o.fulfillmentProviderName || '').toLowerCase()
      if (filterKey === 'argentinaplay') return prov.includes('argentina') || prov.includes('arg')
      if (filterKey === 'juegosdigitalesargentina') return prov.includes('juegos') || prov.includes('jda')
      return (siteNameLower && prov.includes(siteNameLower)) || prov.includes(filterKey)
    })
  }

  // Filtro por Tipo de Licencia
  if (gamingOrderLicenseFilter.value === 'primary') {
    list = list.filter(o => o.licenseType === 'primary' || (o.licenseLabel && o.licenseLabel.toLowerCase().includes('primaria')) || (o.planLabel && o.planLabel.toLowerCase().includes('primaria')))
  } else if (gamingOrderLicenseFilter.value === 'secondary') {
    list = list.filter(o => o.licenseType === 'secondary' || (o.licenseLabel && o.licenseLabel.toLowerCase().includes('secundaria')) || (o.planLabel && o.planLabel.toLowerCase().includes('secundaria')))
  }

  // Filtro por Búsqueda (Texto)
  if (gamingOrderSearch.value.trim()) {
    const q = gamingOrderSearch.value.trim().toLowerCase()
    list = list.filter(o => 
      (o.id && o.id.toLowerCase().includes(q)) ||
      (o.customerName && o.customerName.toLowerCase().includes(q)) ||
      (o.customerPhone && o.customerPhone.toLowerCase().includes(q)) ||
      (o.gameTitle && o.gameTitle.toLowerCase().includes(q)) ||
      (o.platform && o.platform.toLowerCase().includes(q)) ||
      (o.fulfillmentProviderName && o.fulfillmentProviderName.toLowerCase().includes(q)) ||
      (o.voucher && o.voucher.toLowerCase().includes(q))
    )
  }

  return list
})

const totalGamingOrderPages = computed(() => {
  return Math.max(1, Math.ceil((filteredGamingOrdersForAdmin.value?.length || 0) / gamingOrderPageSize.value))
})

const paginatedGamingOrdersForAdmin = computed(() => {
  const list = filteredGamingOrdersForAdmin.value || []
  const start = (gamingOrderCurrentPage.value - 1) * gamingOrderPageSize.value
  return list.slice(start, start + gamingOrderPageSize.value)
})

watch([gamingOrderSearch, gamingOrderStatusFilter, gamingOrderProviderFilter, gamingOrderLicenseFilter, gamingOrderPageSize], () => {
  gamingOrderCurrentPage.value = 1
})

const resolveAdminProviderLabel = (order) => {
  const site = (adminStore.gamingProviderSites || []).find(s => s.key === order.fulfillmentProvider)
  return site?.name || order.fulfillmentProviderName || order.fulfillmentProvider || 'Sin asignar'
}

const handleQuickChangeGamingProvider = (order, newProviderKey) => {
  if (!order || !newProviderKey) return
  const ok = adminStore.setGamingFulfillmentProvider(order.id, newProviderKey)
  if (ok) {
    toast.add(`Proveedor de orden #${order.id} actualizado a ${resolveAdminProviderLabel(order)}`, 'success')
  }
}

const handleToggleGamingOrderStatus = (order) => {
  const isDelivered = order.status === 'Dispatched' || order.status === 'Entregado'
  const newStatus = isDelivered ? 'Waiting Provider' : 'Dispatched'
  const res = adminStore.updateGamingOrderStatus(order.id, newStatus)
  if (res.success) {
    toast.add(`Pedido gamer #${order.id} marcado como: ${newStatus === 'Dispatched' ? 'Entregado' : 'Pendiente'}`, 'success')
  } else {
    toast.add(res.error || 'Error al actualizar estado', 'error')
  }
}

const handleDeleteGamingOrder = (order) => {
  if (confirm(`¿Seguro que deseas eliminar el pedido gamer #${order.id} de ${order.customerName}?`)) {
    const res = adminStore.deleteStreamingOrder(order.id)
    if (res.success) {
      toast.add(`Pedido gamer #${order.id} eliminado correctamente`, 'info')
    } else {
      toast.add(res.error || 'Error al eliminar', 'error')
    }
  }
}

const copyGamingOrderDetails = async (order) => {
  const isPrim = order.licenseType === 'primary' || (order.planLabel && order.planLabel.includes('Primaria'))
  const text = `🎮 Pedido Gamer #${order.id}
👤 Cliente: ${order.customerName || 'Cliente'} (${order.customerPhone || 'S/N'})
🎯 Juego: ${order.gameTitle || order.platform || 'PlayStation'}
🔑 Modalidad: ${isPrim ? 'Cuenta Primaria' : 'Cuenta Secundaria'}
🏢 Proveedor: ${resolveAdminProviderLabel(order)}
💰 Cobrado: $${(order.clientPrice || order.totalAmount || 0).toLocaleString()} ARS
📦 Estado: ${order.status === 'Dispatched' ? 'Entregado' : 'Pendiente'}`

  try {
    await navigator.clipboard.writeText(text)
    toast.add('Datos del pedido gamer copiados al portapapeles', 'info')
  } catch {
    toast.add('Error al copiar datos', 'error')
  }
}

const copyGamingOrderCredentials = async (order) => {
  const acc = order?.accountDetails
  if (!acc || !acc.email) return
  const text = `🎮 ${order.gameTitle || order.platform}\n📧 Correo: ${acc.email}\n🔑 Contraseña: ${acc.pass || '-'}` +
    (acc.pin ? `\n📌 PIN/2FA: ${acc.pin}` : '') +
    (acc.notes ? `\n📝 Nota: ${acc.notes}` : '')
  try {
    await navigator.clipboard.writeText(text)
    toast.add('Credenciales del juego copiadas', 'success')
  } catch {
    toast.add('Error al copiar credenciales', 'error')
  }
}

// MODAL DE DESPACHO DE CREDENCIALES GAMER
const showGamingDispatchModal = ref(false)
const dispatchingGamingOrder = ref(null)
const gamingDispatchForm = ref({
  providerKey: 'argentinaplay',
  providerCost: 0,
  email: '',
  pass: '',
  pin: '',
  notes: '',
  sendWhatsApp: true
})

const openGamingDispatchModal = (order) => {
  if (!order) return
  dispatchingGamingOrder.value = order
  const acc = order.accountDetails || {}
  gamingDispatchForm.value = {
    providerKey: order.fulfillmentProvider || 'argentinaplay',
    providerCost: Number(order.providerCost || 0),
    email: acc.email || '',
    pass: acc.pass || '',
    pin: acc.pin || '',
    notes: acc.notes || '',
    sendWhatsApp: true
  }
  showGamingDispatchModal.value = true
}

const confirmGamingDispatch = () => {
  if (!dispatchingGamingOrder.value) return
  if (!gamingDispatchForm.value.email.trim() || !gamingDispatchForm.value.pass.trim()) {
    toast.add('Ingresa al menos el correo y la contraseña PSN', 'error')
    return
  }
  const res = adminStore.dispatchGamingOrder(dispatchingGamingOrder.value.id, {
    email: gamingDispatchForm.value.email,
    pass: gamingDispatchForm.value.pass,
    pin: gamingDispatchForm.value.pin,
    notes: gamingDispatchForm.value.notes,
    providerKey: gamingDispatchForm.value.providerKey,
    providerCost: gamingDispatchForm.value.providerCost
  })
  if (res.success) {
    toast.add(`✅ Credenciales de ${dispatchingGamingOrder.value.gameTitle || 'Videojuego'} enviadas a la Bóveda`, 'success')
    const dispatchedOrder = res.order
    const shouldSendWa = gamingDispatchForm.value.sendWhatsApp && dispatchedOrder?.customerPhone
    showGamingDispatchModal.value = false
    if (shouldSendWa) {
      sendWhatsAppGameDelivery(dispatchedOrder)
    }
  } else {
    toast.add(res.error || 'Error al despachar pedido', 'error')
  }
}

// POS VENTA RÁPIDA & COTIZADOR GAMER
const posGameSearch = ref('')
const posSelectedGame = ref(null)
const isSubmittingPosSale = ref(false)
const posForm = ref({
  licenseType: 'primary',
  quantity: 1,
  providerKey: 'argentinaplay',
  customClientPrice: 0,
  discountAmount: 0,
  customerName: '',
  customerPhone: '',
  pin: '1234',
  resellerId: 'DIRECT',
  paymentMethod: 'Transferencia Bancaria',
  markDispatched: false,
  accountEmail: '',
  accountPass: '',
  accountPin: '',
  deliveryNotes: ''
})

const filteredPosGames = computed(() => {
  const list = adminStore.processedGamingProducts || []
  const q = posGameSearch.value.trim().toLowerCase()
  if (!q) return list.slice(0, 20)
  return list.filter(g =>
    (g.title && g.title.toLowerCase().includes(q)) ||
    (g.platform && g.platform.toLowerCase().includes(q)) ||
    (g.category && g.category.toLowerCase().includes(q)) ||
    (g.id && g.id.toLowerCase().includes(q))
  ).slice(0, 25)
})

const posSelectedGameSupportsSecondary = computed(() => {
  const g = posSelectedGame.value
  if (!g || !g.effectiveSecondaryPrice || Number(g.effectiveSecondaryPrice) <= 0) return false
  const p = String(g.platform || '').toLowerCase()
  return !p.includes('pc') && !p.includes('steam') && !p.includes('ps3')
})

const selectPosGame = (game) => {
  posSelectedGame.value = game
  posForm.value.licenseType = 'primary'
  posForm.value.providerKey = game.bestFulfillmentPrimary || (game.activeProvidersList?.[0]?.key) || 'argentinaplay'
  posForm.value.customClientPrice = 0
  posForm.value.discountAmount = 0
}

const clearPosGame = () => {
  posSelectedGame.value = null
}

const posSelectedGameQuotes = computed(() => {
  const g = posSelectedGame.value
  if (!g) return []
  const isPrim = posForm.value.licenseType === 'primary'
  const quotesMap = new Map((g.activeProvidersList || []).map(p => [p.key, p]))
  return (adminStore.gamingProviderSites || []).map(site => {
    const q = quotesMap.get(site.key)
    const costUnit = q ? (isPrim ? Number(q.costPrimary || 0) : Number(q.costSecondary || q.costPrimary || 0)) : 0
    return {
      key: site.key,
      name: site.name,
      badge: site.badge || site.name,
      url: site.url || '',
      costUnit
    }
  }).sort((a, b) => (a.costUnit || 9999999) - (b.costUnit || 9999999))
})

watch(() => posForm.value.licenseType, (lic) => {
  const g = posSelectedGame.value
  if (!g) return
  if (lic === 'secondary' && g.bestFulfillmentSecondary) {
    posForm.value.providerKey = g.bestFulfillmentSecondary
  } else if (g.bestFulfillmentPrimary) {
    posForm.value.providerKey = g.bestFulfillmentPrimary
  }
})

const posSummary = computed(() => {
  const g = posSelectedGame.value
  if (!g) return { unitPvp: 0, qty: 1, discount: 0, finalTotal: 0, costTotal: 0, resellerCut: 0, netProfit: 0 }
  const isPrim = posForm.value.licenseType === 'primary'
  const unitPvp = Number(posForm.value.customClientPrice) > 0
    ? Number(posForm.value.customClientPrice)
    : (isPrim ? Number(g.effectivePrimaryPrice || 0) : Number(g.effectiveSecondaryPrice || 0))
  const qty = Math.max(1, Number(posForm.value.quantity) || 1)
  const discount = Math.max(0, Number(posForm.value.discountAmount) || 0)
  const finalTotal = Math.max(0, (unitPvp * qty) - discount)

  const quote = posSelectedGameQuotes.value.find(q => q.key === posForm.value.providerKey)
  const unitCost = quote?.costUnit || (isPrim ? Number(g.actualPurchaseCostPrimary || g.baseCostPrimary || 0) : Number(g.actualPurchaseCostSecondary || g.baseCostSecondary || 0))
  const costTotal = unitCost * qty

  const pct = Number(adminStore.gamingResellerCommissionPercent ?? 10) / 100
  const minProf = Number(adminStore.gamingMinResellerProfit ?? 1000)
  const singleResCut = isPrim ? Math.max(minProf, Math.round(unitPvp * pct)) : Math.max(Math.round(minProf * 0.75), Math.round(unitPvp * pct))
  const resellerCut = posForm.value.resellerId !== 'DIRECT' ? singleResCut * qty : 0
  const netProfit = finalTotal - costTotal - resellerCut

  return { unitPvp, qty, discount, finalTotal, costTotal, resellerCut, netProfit }
})

const submitAdminPosGamingSale = async () => {
  if (!posSelectedGame.value) return
  if (!posForm.value.customerName.trim()) {
    toast.add('Ingresa el nombre del cliente para registrar la venta', 'warning')
    return
  }
  if (posForm.value.markDispatched && (!posForm.value.accountEmail.trim() || !posForm.value.accountPass.trim())) {
    toast.add('Completa Correo y Contraseña PSN para despacho inmediato', 'warning')
    return
  }
  isSubmittingPosSale.value = true
  try {
    const res = await adminStore.createAdminManualGamingSale({
      customerName: posForm.value.customerName,
      customerPhone: posForm.value.customerPhone,
      pin: posForm.value.pin || '1234',
      game: posSelectedGame.value,
      licenseType: posForm.value.licenseType,
      quantity: posForm.value.quantity,
      providerKey: posForm.value.providerKey,
      customClientPrice: posForm.value.customClientPrice,
      discountAmount: posForm.value.discountAmount,
      resellerId: posForm.value.resellerId,
      paymentMethod: posForm.value.paymentMethod,
      markDispatched: posForm.value.markDispatched,
      accountEmail: posForm.value.accountEmail,
      accountPass: posForm.value.accountPass,
      accountPin: posForm.value.accountPin,
      deliveryNotes: posForm.value.deliveryNotes
    })
    if (res?.success) {
      toast.add(`✅ Venta POS #${res.order.id} registrada ($${posSummary.value.finalTotal.toLocaleString()} ARS)`, 'success')
      posForm.value.customerName = ''
      posForm.value.customerPhone = ''
      posForm.value.discountAmount = 0
      posForm.value.accountEmail = ''
      posForm.value.accountPass = ''
      posForm.value.accountPin = ''
      gamingAdminSubTab.value = 'orders'
    } else {
      toast.add(res?.error || 'Error al registrar venta manual', 'error')
    }
  } finally {
    isSubmittingPosSale.value = false
  }
}

const sendPosGamingQuoteWhatsApp = () => {
  const g = posSelectedGame.value
  if (!g) return
  const refPart = posForm.value.resellerId && posForm.value.resellerId !== 'DIRECT'
    ? `/${encodeURIComponent(posForm.value.resellerId)}`
    : ''
  const link = `https://streamepro.me/juego/${encodeURIComponent(g.id)}${refPart}?lic=${posForm.value.licenseType}`
  const msg = `🎮 *${g.title} (${g.platform || 'PlayStation'})* — *StreamePro Oficial*\n\n` +
    (g.effectivePrimaryPrice ? `⭐️ *Cuenta Primaria:* $${g.effectivePrimaryPrice.toLocaleString()} ARS\n` : '') +
    (posSelectedGameSupportsSecondary.value ? `⚡️ *Cuenta Secundaria:* $${g.effectiveSecondaryPrice.toLocaleString()} ARS\n` : '') +
    (posForm.value.discountAmount > 0 ? `🏷️ *Tu Promo Final (${posForm.value.licenseType === 'primary' ? 'Primaria' : 'Secundaria'}):* $${posSummary.value.finalTotal.toLocaleString()} ARS\n` : '') +
    `\n⏱️ Entrega digital garantizada (60/90 min)\n💳 Transferencia, MP o Tarjeta\n\n👉 *Ver ficha y pedir aquí:*\n${link}`

  const phone = formatWaPhone(posForm.value.customerPhone)
  const url = phone ? `https://wa.me/${phone}?text=${encodeURIComponent(msg)}` : `https://wa.me/?text=${encodeURIComponent(msg)}`
  window.open(url, '_blank')
}

// GESTOR DE CUPONES PROMOCIONALES
const newPromoForm = ref({
  code: '',
  type: 'percent',
  value: 10,
  scope: 'all',
  minAmount: 0,
  maxUses: 100,
  description: ''
})

const handleCreatePromoCode = () => {
  const res = adminStore.addPromoCode({ ...newPromoForm.value })
  if (res.success) {
    toast.add(`🏷️ Cupón ${res.coupon.code} creado y sincronizado con la tienda`, 'success')
    newPromoForm.value.code = ''
    newPromoForm.value.description = ''
  } else {
    toast.add(res.error || 'Error al crear cupón', 'error')
  }
}

const handleTogglePromoCode = (code) => {
  adminStore.togglePromoCode(code)
  toast.add(`Estado del cupón ${code} actualizado`, 'info')
}

const handleDeletePromoCode = (code) => {
  if (!confirm(`¿Eliminar definitivamente el cupón ${code}?`)) return
  adminStore.deletePromoCode(code)
  toast.add(`Cupón ${code} eliminado`, 'info')
}

const buildPromoCodeBannerText = (coupon) => {
  const discountTxt = coupon.type === 'fixed' ? `$${Number(coupon.value).toLocaleString()} ARS OFF` : `${coupon.value}% OFF`
  return `🎁 *CUPÓN EXCLUSIVO STREAMEPRO: ${coupon.code}*\n🔥 Aprovecha *${discountTxt}* en tu próxima compra.\n` +
    (coupon.minAmount > 0 ? `💳 Válido desde $${Number(coupon.minAmount).toLocaleString()} ARS\n` : '') +
    `👉 Canjéalo en el carrito: https://streamepro.me/juegos`
}

const copyPromoCodeBanner = async (coupon) => {
  try {
    await navigator.clipboard.writeText(buildPromoCodeBannerText(coupon))
    toast.add(`Mensaje promo del cupón ${coupon.code} copiado`, 'success')
  } catch {
    toast.add('No se pudo copiar al portapapeles', 'error')
  }
}

const openPromoCodeWhatsApp = (coupon) => {
  window.open(`https://wa.me/?text=${encodeURIComponent(buildPromoCodeBannerText(coupon))}`, '_blank')
}

// GENERADOR DE LISTAS VIRALES WHATSAPP / TELEGRAM Y PLACA PRO
const marketingListConfig = ref({
  campaignMode: 'games', // 'games' | 'coupon_games' | 'combos'
  platform: 'ALL',
  maxPrice: 0,
  limit: 12,
  resellerId: 'DIRECT',
  sortMode: 'popular',
  couponCode: ''
})

const selectedMarketingReseller = computed(() => {
  const rid = marketingListConfig.value.resellerId
  if (!rid || rid === 'DIRECT') return null
  return (adminStore.resellers || []).find(r => r.id === rid) || null
})

const marketingFilteredGames = computed(() => {
  let list = [...(adminStore.processedGamingProducts || [])]
  const cfg = marketingListConfig.value
  if (cfg.platform !== 'ALL') {
    list = list.filter(g => g.platform && g.platform.includes(cfg.platform))
  }
  if (cfg.maxPrice > 0) {
    list = list.filter(g => Number(g.effectivePrimaryPrice || 0) > 0 && Number(g.effectivePrimaryPrice) <= cfg.maxPrice)
  }
  if (cfg.sortMode === 'lowest_price') {
    list.sort((a, b) => Number(a.effectivePrimaryPrice || 9999999) - Number(b.effectivePrimaryPrice || 9999999))
  } else if (cfg.sortMode === 'highest_margin') {
    const marginOf = (g) => Number(g.actualStoreProfitPrimary ?? g.storeGrossMarginPrimary ?? (Number(g.effectivePrimaryPrice || 0) - Number(g.baseCostPrimary || 0)))
    list.sort((a, b) => marginOf(b) - marginOf(a))
  }
  return list
})

const marketingDealsPreviewText = computed(() => {
  const cfg = marketingListConfig.value
  const resObj = selectedMarketingReseller.value
  const refSuffix = resObj ? `/${encodeURIComponent(resObj.id)}` : ''
  const refQuery = resObj ? `?ref=${encodeURIComponent(resObj.id)}` : ''
  const storeLabel = (resObj ? (resObj.storeName || resObj.name) : (adminStore.adminAuth.name || 'STREAMEPRO')).toUpperCase()
  const waContact = resObj
    ? (resObj.contactNumber || resObj.whatsapp || '')
    : (adminStore.providerBankInfo.whatsapp || adminStore.adminAuth.whatsapp || '')

  if (cfg.campaignMode === 'combos') {
    const pkgs = adminStore.packages || []
    const header = `🎁 *COMBOS DE STREAMING OFICIALES — ${storeLabel}* 🍿\nPerfiles privados con PIN, calidad 4K y entrega rápida.\n\n`
    const rows = pkgs.map((pkg, idx) => {
      const srvs = (pkg.services || []).map(s => s.platform).join(' + ')
      const saveStr = pkg.originalPrice > pkg.promoPrice
        ? ` *(Antes $${Number(pkg.originalPrice).toLocaleString()})*`
        : ''
      return `${idx + 1}. *${pkg.title}* (${srvs})\n   💥 *$${Number(pkg.promoPrice || 0).toLocaleString()} ARS*${saveStr}`
    }).join('\n\n')
    const footer = `\n\n🛒 Pedí tu combo online: https://streamepro.me/${refQuery ? `${refQuery}&sec=packages` : '?sec=packages'}` +
      (waContact ? `\n💬 Consultas directas: https://wa.me/${String(waContact).replace(/[^0-9]/g, '')}` : '')
    return header + (rows || 'Sin combos cargados.') + footer
  }

  const picked = marketingFilteredGames.value.slice(0, cfg.limit || 12)
  const platTitle = cfg.platform === 'ALL' ? 'PS4 / PS5' : cfg.platform
  let couponCallout = ''
  if (cfg.campaignMode === 'coupon_games') {
    const activeCoupons = (adminStore.promoCodes || []).filter(c => c.active !== false)
    const chosenCoupon = (cfg.couponCode && activeCoupons.find(c => c.code === cfg.couponCode)) || activeCoupons[0]
    if (chosenCoupon) {
      const discTxt = chosenCoupon.type === 'fixed'
        ? `$${Number(chosenCoupon.value).toLocaleString()} ARS OFF`
        : `${chosenCoupon.value}% OFF`
      couponCallout = `🎟️ *CUPÓN ACTIVO: ${chosenCoupon.code}* (${discTxt})\n\n`
    }
  }

  const header = `🔥 *OFERTAS DIGITALES ${platTitle} — ${storeLabel}* 🎮\nEntrega garantizada 60/90 min y soporte en consola.\n\n` + couponCallout
  const rows = picked.map((g, idx) => {
    const secStr = g.effectiveSecondaryPrice && !String(g.platform || '').match(/PC|Steam|PS3/i)
      ? ` | Sec $${g.effectiveSecondaryPrice.toLocaleString()}`
      : ''
    return `${idx + 1}. *${g.title}* (${g.platform || platTitle})\n   💰 Pri $${(g.effectivePrimaryPrice || 0).toLocaleString()}${secStr}\n   👉 https://streamepro.me/juego/${g.id}${refSuffix}`
  }).join('\n')
  const footer = `\n\n🛒 Ver catálogo completo (+${(adminStore.processedGamingProducts || []).length} juegos): https://streamepro.me/juegos${refQuery}` +
    (waContact ? `\n💬 WhatsApp Directo: https://wa.me/${String(waContact).replace(/[^0-9]/g, '')}` : '')
  return header + (rows || 'Sin títulos en este filtro.') + footer
})

const openAdminMarketingPlaque = () => {
  const cfg = marketingListConfig.value
  const resObj = selectedMarketingReseller.value
  adminPlaqueContext.value = {
    storeName: resObj ? (resObj.storeName || resObj.name) : (adminStore.adminAuth.name || 'StreamePro Oficial'),
    resellerRef: resObj ? resObj.id : 'DIRECT',
    contactPhone: resObj
      ? (resObj.contactNumber || resObj.whatsapp || '')
      : (adminStore.providerBankInfo.whatsapp || adminStore.adminAuth.whatsapp || '')
  }

  if (cfg.campaignMode === 'combos') {
    const pkgs = adminStore.packages || []
    if (!pkgs.length) {
      toast.add('No hay combos creados todavía.', 'info')
      return
    }
    sharePlaquePayload.value = {
      mode: pkgs.length === 1 ? 'single' : 'multi',
      productType: 'package',
      id: pkgs[0].id || '',
      title: pkgs.length === 1 ? pkgs[0].title : 'Combos Oficiales StreamePro',
      subtitle: `${pkgs.length} combos activos · Perfiles privados 4K con PIN`,
      priceNumeric: Number(pkgs[0].promoPrice || 0),
      originalPrice: Number(pkgs[0].originalPrice || 0),
      services: pkgs[0].services || [],
      tag: pkgs[0].badge || 'COMBOS VIP',
      items: pkgs.slice(0, 4).map(p => ({
        title: p.title,
        subtitle: (p.services || []).map(s => s.platform).join(' + '),
        priceNumeric: Number(p.promoPrice || 0),
        originalPrice: Number(p.originalPrice || 0),
        tag: p.badge || 'COMBO'
      }))
    }
    showSharePlaqueModal.value = true
    return
  }

  const picked = marketingFilteredGames.value.slice(0, 4)
  if (!picked.length) {
    toast.add('No hay juegos en este filtro para generar la placa.', 'info')
    return
  }
  const platTitle = cfg.platform === 'ALL' ? 'PS4 / PS5' : cfg.platform
  sharePlaquePayload.value = {
    mode: picked.length === 1 ? 'single' : 'multi',
    productType: 'gaming',
    id: picked.length === 1 ? picked[0].id : '',
    licenseType: picked.length === 1 ? 'primary' : undefined,
    title: picked.length === 1 ? picked[0].title : `Top Ofertas Gamer ${platTitle}`,
    subtitle: picked.length === 1
      ? `${picked[0].platform || 'PS4/PS5'} · Entrega 60/90 min`
      : `Selección destacada · Entrega digital garantizada 60/90 min`,
    priceNumeric: picked.length === 1 ? Number(picked[0].effectivePrimaryPrice || picked[0].effectiveSecondaryPrice || 0) : 0,
    image: picked[0].image || '',
    tag: platTitle,
    items: picked.map(g => ({
      title: g.title,
      subtitle: `${g.platform || platTitle} · Primaria`,
      priceNumeric: Number(g.effectivePrimaryPrice || g.effectiveSecondaryPrice || 0),
      image: g.image || '',
      tag: g.platform || 'GAMING'
    }))
  }
  showSharePlaqueModal.value = true
}

const copyMarketingDealsList = async () => {
  try {
    await navigator.clipboard.writeText(marketingDealsPreviewText.value)
    toast.add('📋 Lista viral copiada al portapapeles', 'success')
  } catch {
    toast.add('Error al copiar lista', 'error')
  }
}

const openMarketingDealsWhatsApp = () => {
  window.open(`https://wa.me/?text=${encodeURIComponent(marketingDealsPreviewText.value)}`, '_blank')
}

// SALUD DEL CATÁLOGO GAMER, DEPENDENCIAS Y EXPORTACIONES CSV
const healthInspectMode = ref('missing_covers')
const coverUrlEdits = ref({})

const handleAutoFixSecondaryPrices = () => {
  const res = adminStore.autoFixMissingSecondaryPrices(0.55)
  toast.add(res.updatedCount > 0 ? `⚡ Se calcularon ${res.updatedCount} cotizaciones Secundarias (55%)` : 'Todos los juegos PS4/PS5 ya tienen secundaria', res.updatedCount > 0 ? 'success' : 'info')
}

const handleResetAllCustomPrices = () => {
  if (!confirm('¿Restaurar todos los precios PVP manuales a cálculo automático de margen?')) return
  const res = adminStore.resetAllCustomGamingPrices()
  toast.add(`🔄 ${res.resetCount} PVP manuales restaurados a fórmula`, 'info')
}

const handleSaveGameCoverUrl = (gameId) => {
  const url = coverUrlEdits.value[gameId]
  const res = adminStore.updateGameCoverImage(gameId, url)
  if (res.success) {
    toast.add(`🖼️ Portada actualizada para ${res.game.title}`, 'success')
    coverUrlEdits.value[gameId] = ''
  } else {
    toast.add(res.error || 'URL inválida', 'error')
  }
}

const downloadCsvFile = (filename, headers, rows) => {
  const esc = (v) => `"${String(v ?? '').replace(/"/g, '""')}"`
  const csv = [headers.map(esc).join(','), ...rows.map(r => r.map(esc).join(','))].join('\n')
  const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = filename
  a.click()
  URL.revokeObjectURL(url)
}

const exportGamingCatalogCsv = () => {
  const list = adminStore.processedGamingProducts || []
  const headers = ['ID', 'Titulo', 'Plataforma', 'Categoria', 'Subcategoria', 'PVP_Primaria', 'PVP_Secundaria', 'Costo_Optimo_Pri', 'Proveedor_Optimo_Pri', 'Sitios_Activos', 'Portada']
  const rows = list.map(g => [
    g.id, g.title, g.platform, g.category, g.subcategory,
    g.effectivePrimaryPrice || 0, g.effectiveSecondaryPrice || 0,
    g.actualPurchaseCostPrimary || 0, g.bestFulfillmentPrimary || '',
    (g.activeProvidersList || []).map(p => p.name).join(' | '), g.image || ''
  ])
  downloadCsvFile(`catalogo_gamer_streamepro_${new Date().toISOString().slice(0, 10)}.csv`, headers, rows)
  toast.add(`📥 Exportados ${list.length} videojuegos a CSV`, 'success')
}

const exportGamingOrdersCsv = () => {
  const list = adminStore.gamingOrders || []
  const headers = ['ID', 'Fecha', 'Cliente', 'WhatsApp', 'Juego', 'Licencia', 'Canal', 'Proveedor_Compra', 'Cobrado_ARS', 'Costo_ARS', 'Estado']
  const rows = list.map(o => [
    o.id, o.createdAt || o.createdDate || '', o.customerName || '', o.customerPhone || '',
    o.gameTitle || o.platform || '', o.licenseType || '', o.resellerId || 'DIRECT',
    resolveAdminProviderLabel(o), o.clientPrice || 0, o.providerCost || 0, o.status || ''
  ])
  downloadCsvFile(`pedidos_gamer_streamepro_${new Date().toISOString().slice(0, 10)}.csv`, headers, rows)
  toast.add(`📥 Exportados ${list.length} pedidos gamer a CSV`, 'success')
}

// TICKETS / REPORTES DE FALLAS Y CONSULTAS MANUALES DE JUEGOS
const supportTicketReplies = ref({})
const supportTicketFilter = ref('OPEN')

const openGameRequestCount = computed(() => {
  return (adminStore.supportTickets || []).filter(t => t.type === 'game_request' && t.status !== 'Resuelto').length
})

const openSupportOnlyCount = computed(() => {
  return (adminStore.supportTickets || []).filter(t => t.type !== 'game_request' && t.status !== 'Resuelto').length
})

const gamingInquiryTicketsForAdmin = computed(() => {
  return (adminStore.supportTickets || []).filter(t => t.type === 'game_request' || t.category === 'gaming')
})

const filteredSupportTicketsForAdmin = computed(() => {
  const all = adminStore.supportTickets || []
  if (supportTicketFilter.value === 'OPEN') return all.filter(t => t.status !== 'Resuelto')
  if (supportTicketFilter.value === 'GAME_REQUESTS') return all.filter(t => t.type === 'game_request')
  if (supportTicketFilter.value === 'SUPPORT') return all.filter(t => t.type !== 'game_request')
  return all
})

const handleResolveSupportTicket = (ticket) => {
  const defaultReply = ticket.type === 'game_request'
    ? 'Consulta respondida y confirmada por Administración StreamePro.'
    : 'Solucionado por Soporte Técnico StreamePro.'
  const reply = supportTicketReplies.value[ticket.id] || defaultReply
  const res = adminStore.resolveSupportTicket(ticket.id, reply)
  if (res.success) {
    toast.add(`✅ Ticket #${ticket.id} marcado como resuelto y respondido`, 'success')
  }
}

const sendTicketReplyWhatsApp = (ticket) => {
  const defaultReply = ticket.type === 'game_request'
    ? '¡Tenemos buenas noticias sobre el juego consultado!'
    : 'Ya revisamos tu reporte y está solucionado.'
  const reply = supportTicketReplies.value[ticket.id] || ticket.adminReply || defaultReply
  const header = ticket.type === 'game_request'
    ? `🎮 Sobre tu solicitud manual *${ticket.requestedGame || ticket.subject}* (#${ticket.id}):`
    : `🛟 Sobre tu reporte *${ticket.subject}* (#${ticket.id}):`
  const msg = `¡Hola ${ticket.clientName || 'Cliente'}!\n${header}\n\n${reply}\n\nTienda Oficial: https://streamepro.me/juegos`
  window.open(`https://wa.me/${formatWaPhone(ticket.phone)}?text=${encodeURIComponent(msg)}`, '_blank')
}

const openSupportTicketInPos = (ticket) => {
  if (!ticket) return
  adminTab.value = 'gaming'
  gamingAdminSubTab.value = 'marketing_pos'
  const q = String(ticket.requestedGame || '').trim().toLowerCase()
  const match = q
    ? (adminStore.processedGamingProducts || []).find(g => g.title && g.title.toLowerCase().includes(q))
    : null
  if (match) {
    selectPosGame(match)
  } else {
    clearPosGame()
    posGameSearch.value = ticket.requestedGame || ''
  }
  posForm.value.customerName = ticket.clientName || ''
  posForm.value.customerPhone = ticket.phone || ''
  posForm.value.resellerId = ticket.resellerId || 'DIRECT'
  if (ticket.requestedLicense && ticket.requestedLicense.toLowerCase().includes('sec')) {
    posForm.value.licenseType = 'secondary'
  } else {
    posForm.value.licenseType = 'primary'
  }
  posForm.value.deliveryNotes = `Pedido manual desde ticket #${ticket.id}`
  toast.add(match ? `🛒 "${match.title}" cargado en POS Venta Rápida` : `🛒 Datos de ${ticket.clientName} cargados en POS (elige el título)`, 'info')
}

// ==========================================
// MODAL DE ASIGNACIÓN / EDICIÓN DE NOMBRE DEL JUEGO GAMER
// ==========================================
const showAssignGameModal = ref(false)
const targetGameOrder = ref(null)
const assignGameTitleInput = ref('')
const assignGamePlatformInput = ref('PS4 / PS5')
const assignGameLicenseInput = ref('primary')
const gameCatalogSearchQuery = ref('')

const filteredCatalogGamesForPicker = computed(() => {
  const q = gameCatalogSearchQuery.value.trim().toLowerCase()
  const list = adminStore.processedGamingProducts || []
  if (!q) return list.slice(0, 15)
  return list.filter(g => g.title.toLowerCase().includes(q) || (g.category && g.category.toLowerCase().includes(q))).slice(0, 20)
})

const openAssignGameModal = (order) => {
  targetGameOrder.value = order
  const existingTitle = order.gameTitle && !order.gameTitle.includes('Pendiente') 
    ? order.gameTitle 
    : (order.platform && !['PS4 / PS5', 'PS4', 'PS5', 'PlayStation'].includes(order.platform.trim())
        ? order.platform.replace(/\s*\((PS4|PS5|PS4\s*\/\s*PS5|PlayStation)\)/i, '').trim()
        : '')

  assignGameTitleInput.value = existingTitle
  assignGamePlatformInput.value = order.gamePlatform || (order.platform?.includes('PS5') ? 'PS5' : (order.platform?.includes('PS4') ? 'PS4' : 'PS4 / PS5'))
  assignGameLicenseInput.value = order.licenseType || (order.planLabel?.includes('Secundaria') ? 'secondary' : 'primary')
  gameCatalogSearchQuery.value = existingTitle
  showAssignGameModal.value = true
}

const selectCatalogGameForOrder = (game) => {
  assignGameTitleInput.value = game.title
  if (game.platform) assignGamePlatformInput.value = game.platform
}

const saveAssignedGameToOrder = () => {
  if (!targetGameOrder.value) return
  if (!assignGameTitleInput.value.trim()) {
    toast.add('Por favor, ingresa o selecciona el nombre del videojuego', 'warning')
    return
  }
  const res = adminStore.updateGamingOrderGameTitle(
    targetGameOrder.value.id,
    assignGameTitleInput.value.trim(),
    assignGamePlatformInput.value,
    assignGameLicenseInput.value
  )
  if (res.success) {
    toast.add(`✓ Videojuego "${assignGameTitleInput.value.trim()}" asignado al pedido #${targetGameOrder.value.id}`, 'success')
    showAssignGameModal.value = false
  } else {
    toast.add(res.error || 'Error al guardar nombre del juego', 'error')
  }
}

// ==========================================
// IMPORTACIÓN Y SINCRONIZACIÓN GAMING (ARGPLAY & JDA)
// ==========================================
const isSyncingGaming = ref(false)
const gamingCatalogFileInput = ref(null)

const triggerGamingCatalogUpload = () => {
  if (gamingCatalogFileInput.value) gamingCatalogFileInput.value.click()
}

const handleImportGamingCatalog = (event) => {
  const file = event.target.files?.[0]
  if (!file) return

  const reader = new FileReader()
  reader.onload = (e) => {
    try {
      const raw = String(e.target.result).replace(/^\uFEFF/, '')
      const json = JSON.parse(raw)
      const res = adminStore.updateGamingProductsFromJson(json)
      if (res.success) {
        toast.add(`¡Catálogo gaming actualizado! ${res.count} videojuegos sincronizados en la base de datos.`, 'success')
      } else {
        toast.add(res.error || 'Error al procesar el archivo JSON de videojuegos', 'error')
      }
    } catch (err) {
      toast.add('El archivo seleccionado no es un JSON válido', 'error')
    }
    if (event.target) event.target.value = ''
  }
  reader.readAsText(file, 'utf-8')
}

const handleSyncGamingCatalog = async () => {
  isSyncingGaming.value = true
  try {
    const res = await adminStore.syncGamingFromApi()
    if (res.success) {
      toast.add(`¡Catálogo Gaming sincronizado! ${res.count} videojuegos extraídos en vivo desde Argentina Play y JDA.`, 'success')
    } else {
      toast.add(res.error || 'Error al conectar con los proveedores gaming', 'error')
    }
  } catch (err) {
    toast.add('Error al sincronizar proveedores gaming', 'error')
  } finally {
    isSyncingGaming.value = false
  }
}

// RECORDATORIOS PERSUASIVOS DE RENOVACIÓN POR WHATSAPP
const getClientExpiryDays = (expiryStr) => {
  if (!expiryStr) return 999
  const diffTime = new Date(expiryStr) - new Date()
  return Math.ceil(diffTime / (1000 * 60 * 60 * 24))
}

const formatWaPhone = (phone) => {
  if (!phone) return ''
  let cleaned = String(phone).replace(/[^0-9]/g, '')
  if (cleaned.startsWith('0')) cleaned = cleaned.substring(1)
  if (cleaned.length === 10) cleaned = `549${cleaned}`
  else if (cleaned.length === 11 && cleaned.startsWith('15')) cleaned = `54911${cleaned.substring(2)}`
  else if (cleaned.length === 12 && cleaned.startsWith('54') && !cleaned.startsWith('549')) cleaned = `549${cleaned.substring(2)}`
  return cleaned
}

const sendWhatsAppRenewalReminder = (client) => {
  const phone = formatWaPhone(client.phone)
  const storeTitle = adminStore.providerBankInfo.holderName || 'StreamePro Mayorista'
  const daysRemaining = getClientExpiryDays(client.expiry)
  const timeText = daysRemaining <= 0 ? 'hoy' : daysRemaining === 1 ? 'mañana' : `en ${daysRemaining} días`

  const message = 
    `¡Hola ${client.name}! Te saludamos de ${storeTitle}. 👋\n\n` +
    `Te recordamos que tu suscripción de *${client.platform}* (${client.planName || 'Plan Oficial'}) vence *${timeText}* (Fecha: ${client.expiry}). 📅\n\n` +
    `Para conservar tus perfiles, favoritos y no quedarte sin servicio este fin de semana, confírmanos por aquí para renovar tu mes.\n\n` +
    `🏦 Alias: ${adminStore.providerBankInfo.cbuAlias || 'STREAMEPRO.MAYORISTA'}\n` +
    `💳 Titular: ${adminStore.providerBankInfo.holderName || 'StreamePro'}\n\n` +
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
  const updated = adminStore.updateResellerAuth(selectedResellerForAuth.value.id, {
    username: resellerAuthForm.value.username.trim(),
    password: resellerAuthForm.value.password.trim(),
    isActive: resellerAuthForm.value.isActive
  })
  if (updated.success) {
    toast.add(`Credenciales y estado de ${selectedResellerForAuth.value.name} guardados con éxito`, 'success')
    showResellerAuthModal.value = false
  } else {
    toast.add(updated.error || 'Error al guardar credenciales', 'error')
  }
}

const saveResellerAuthAndOpenCrm = () => {
  if (!resellerAuthForm.value.username.trim() || !resellerAuthForm.value.password.trim()) {
    toast.add('El usuario y la contraseña son obligatorios', 'error')
    return
  }
  const updated = adminStore.updateResellerAuth(selectedResellerForAuth.value.id, {
    username: resellerAuthForm.value.username.trim(),
    password: resellerAuthForm.value.password.trim(),
    isActive: resellerAuthForm.value.isActive
  })
  if (updated.success) {
    toast.add(`Credenciales guardadas. Accediendo al panel /crm como ${selectedResellerForAuth.value.name}...`, 'success')
    showResellerAuthModal.value = false
    openResellerPanelDirect(updated.reseller)
  } else {
    toast.add(updated.error || 'Error al guardar credenciales', 'error')
  }
}

const copyModalResellerCredentials = () => {
  const user = resellerAuthForm.value.username.trim()
  const pass = resellerAuthForm.value.password.trim()
  const origin = typeof window !== 'undefined' ? window.location.origin : 'https://streamepro.me'
  const text = `Panel de Distribuidor StreamePro:\n🌐 Enlace: ${origin}/crm\n👤 Usuario: ${user}\n🔑 Clave: ${pass}`
  if (typeof navigator !== 'undefined' && navigator.clipboard) {
    navigator.clipboard.writeText(text).then(() => {
      toast.add('Usuario y contraseña copiados al portapapeles', 'success')
    }).catch(() => {
      toast.add(`Usuario: ${user} | Clave: ${pass}`, 'info')
    })
  } else {
    toast.add(`Usuario: ${user} | Clave: ${pass}`, 'info')
  }
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
  const text = `🎬 *STREAMEPRO - CREDENCIALES OFICIALES*\nHola ${client.name}!\nN° Cliente: ${client.clientNumber || client.id}\nPlataforma: *${client.platform}*\nPlan: *${client.planName || client.devices + ' pantallas'}*\nCorreo: ${client.account}\nClave: ${client.pass}\nVence: ${client.expiry}\n\n*Perfiles asignados:*\n${profilesList}\n\n¡Garantía StreamePro activa!`
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
const bankSubTab = ref('accounts') // 'accounts' | 'validator' | 'webhook_guide' | 'cloud_notifications'

// CONFIGURACIÓN TELEGRAM BOT & FIREBASE CLOUD SYNC
const telegramForm = ref({
  botToken: adminStore.telegramConfig?.botToken || '',
  chatId: adminStore.telegramConfig?.chatId || '',
  enabled: adminStore.telegramConfig?.enabled !== false
})

const firebaseForm = ref({
  apiKey: adminStore.firebaseConfig?.apiKey || '',
  authDomain: adminStore.firebaseConfig?.authDomain || '',
  projectId: adminStore.firebaseConfig?.projectId || '',
  storageBucket: adminStore.firebaseConfig?.storageBucket || '',
  messagingSenderId: adminStore.firebaseConfig?.messagingSenderId || '',
  appId: adminStore.firebaseConfig?.appId || '',
  enabled: Boolean(adminStore.firebaseConfig?.enabled)
})

const isTestingTelegram = ref(false)
const isTestingFirebase = ref(false)

// CONFIGURACIÓN MERCADO PAGO CHECKOUT PRO
const mercadoPagoForm = ref({
  accessToken: adminStore.mercadoPagoConfig?.accessToken || '',
  publicKey: adminStore.mercadoPagoConfig?.publicKey || '',
  enabled: adminStore.mercadoPagoConfig?.enabled !== false
})
const showMpToken = ref(false)
const isTestingMercadoPago = ref(false)

const saveMercadoPagoSettings = () => {
  adminStore.updateMercadoPagoConfig(mercadoPagoForm.value)
  toast.add('¡Configuración de Mercado Pago guardada correctamente!', 'success')
}

const testMercadoPagoConnection = async () => {
  const token = (mercadoPagoForm.value.accessToken || '').trim()
  if (!token) {
    toast.add('Ingresa tu Access Token de Mercado Pago antes de probar', 'error')
    return
  }
  isTestingMercadoPago.value = true
  try {
    const res = await fetch('/api/create-preference', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        customAccessToken: token,
        orderId: `TEST-${Date.now().toString().slice(-4)}`,
        items: [
          {
            id: 'test-1',
            title: 'Verificación de Integración Mercado Pago',
            quantity: 1,
            unitPrice: 100
          }
        ]
      })
    })
    const data = await res.json()
    if (data.success && data.initPoint) {
      toast.add('¡Conexión exitosa con Mercado Pago! La pasarela está lista para cobrar.', 'success')
    } else {
      toast.add(`Error de Mercado Pago: ${data.error || 'Token inválido o permisos insuficientes'}`, 'error')
    }
  } catch (e) {
    toast.add(`No se pudo conectar con el endpoint de Mercado Pago: ${e.message}`, 'error')
  } finally {
    isTestingMercadoPago.value = false
  }
}

const saveTelegramSettings = () => {
  adminStore.updateTelegramConfig(telegramForm.value)
  toast.add('¡Configuración de Telegram guardada!', 'success')
}

const testTelegramBot = async () => {
  if (!telegramForm.value.botToken || !telegramForm.value.chatId) {
    toast.add('Ingresa el Bot Token y Chat ID antes de probar', 'error')
    return
  }
  isTestingTelegram.value = true
  try {
    const res = await fetch('/api/notify-order', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        test: true,
        telegramToken: telegramForm.value.botToken,
        telegramChatId: telegramForm.value.chatId
      })
    })
    const data = await res.json()
    if (data.success) {
      toast.add('¡Mensaje de prueba enviado con éxito a Telegram! Revisa tu teléfono.', 'success')
    } else {
      toast.add(`Error de Telegram: ${data.result?.description || data.message || 'No se pudo enviar'}`, 'error')
    }
  } catch (e) {
    toast.add('Error al conectar con la API de notificaciones: ' + e.message, 'error')
  } finally {
    isTestingTelegram.value = false
  }
}

const saveFirebaseSettings = () => {
  firebaseForm.value.enabled = true
  adminStore.updateFirebaseConfig(firebaseForm.value)
  toast.add('¡Credenciales de Firebase guardadas y sincronización iniciada!', 'success')
}

const testFirebaseConnectionHandler = async () => {
  if (!firebaseForm.value.apiKey || !firebaseForm.value.projectId) {
    toast.add('Ingresa al menos API Key y Project ID para probar', 'error')
    return
  }
  isTestingFirebase.value = true
  try {
    const res = await testFirebaseConnection(firebaseForm.value)
    if (res.success) {
      toast.add('¡Conexión exitosa con Firebase Firestore!', 'success')
    } else {
      toast.add('Error conectando a Firebase: ' + res.message, 'error')
    }
  } catch (e) {
    toast.add('Error en prueba de Firebase: ' + e.message, 'error')
  } finally {
    isTestingFirebase.value = false
  }
}
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
    adminStore.updateRenewalCredentials(selectedRenewal.value.id, renewalFormData.value)
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

// GESTIÓN Y CONTROL ORDENADO DE PEDIDOS STREAMING (Excluye videojuegos digitales para evitar contaminación)
const streamingOrderSearch = ref('')
const streamingOrderStatusFilter = ref('All') // 'All' | 'Waiting Provider' | 'Dispatched' | 'Cancelado'
const streamingOrderResellerFilter = ref('All')

const isOrderGamingType = (o) => {
  if (!o) return false
  return o.orderType === 'gaming' ||
    o.category === 'Videojuegos Digitales' ||
    Boolean(o.gameTitle) ||
    Boolean(o.licenseType) ||
    (o.planKey && String(o.planKey).startsWith('gaming_')) ||
    (o.platform && (o.platform.includes('PS4') || o.platform.includes('PS5') || o.platform.includes('PlayStation')))
}

const streamingBaseOrders = computed(() => {
  return (adminStore.orders || []).filter(o => !isOrderGamingType(o))
})

const filteredStreamingOrdersForAdmin = computed(() => {
  let list = streamingBaseOrders.value
  if (streamingOrderStatusFilter.value !== 'All') {
    if (streamingOrderStatusFilter.value === 'Waiting Provider') {
      list = list.filter(o => o.status === 'Waiting Provider' || o.status === 'Pendiente')
    } else {
      list = list.filter(o => o.status === streamingOrderStatusFilter.value)
    }
  }
  if (streamingOrderResellerFilter.value !== 'All') {
    if (streamingOrderResellerFilter.value === 'DIRECT') {
      list = list.filter(o => o.resellerId === 'DIRECT' || !o.resellerId)
    } else {
      list = list.filter(o => o.resellerId === streamingOrderResellerFilter.value)
    }
  }
  if (streamingOrderSearch.value.trim()) {
    const q = streamingOrderSearch.value.trim().toLowerCase()
    list = list.filter(o => 
      (o.id && o.id.toLowerCase().includes(q)) ||
      (o.customerNumber && o.customerNumber.toLowerCase().includes(q)) ||
      (o.customerName && o.customerName.toLowerCase().includes(q)) ||
      (o.customerPhone && o.customerPhone.toLowerCase().includes(q)) ||
      (o.platform && o.platform.toLowerCase().includes(q)) ||
      (o.pin && String(o.pin).includes(q)) ||
      (o.voucher && o.voucher.toLowerCase().includes(q)) ||
      (o.resellerName && o.resellerName.toLowerCase().includes(q))
    )
  }
  return list
})

const streamingStats = computed(() => {
  const all = streamingBaseOrders.value
  const pending = all.filter(o => o.status === 'Waiting Provider' || o.status === 'Pendiente').length
  const dispatched = all.filter(o => o.status === 'Dispatched').length
  const cancelled = all.filter(o => o.status === 'Cancelado').length
  const totalAmount = all.reduce((sum, o) => sum + (o.status !== 'Cancelado' ? Number(o.clientPrice || o.total || 0) : 0), 0)
  return { all: all.length, pending, dispatched, cancelled, totalAmount }
})

const handleCancelStreamingOrder = (order) => {
  const reason = prompt('Indica el motivo de cancelación del pedido:', 'Cancelado por administración')
  if (reason === null) return
  const result = adminStore.cancelStreamingOrder(order.id, reason)
  if (result.success) {
    toast.add(`Pedido #${order.id} cancelado correctamente`, 'info')
  } else {
    toast.add(result.error || 'Error al cancelar pedido', 'error')
  }
}

const handleDeleteStreamingOrder = (order) => {
  if (!confirm(`¿Eliminar definitivamente la orden #${order.id} de ${order.customerName}? Esta acción no se puede deshacer.`)) {
    return
  }
  const result = adminStore.deleteStreamingOrder(order.id)
  if (result.success) {
    toast.add(`Orden #${order.id} eliminada`, 'info')
  }
}

const handleDeletePhysicalOrder = (order) => {
  if (!confirm(`¿Eliminar definitivamente el pedido físico #${order.id} de ${order.customerName}? Esta acción no se puede deshacer.`)) {
    return
  }
  const res = adminStore.deletePhysicalOrder(order.id)
  if (res.success) {
    toast.add(`Pedido físico #${order.id} eliminado`, 'info')
  }
}

const copyOrderCredentials = (order) => {
  if (!order.accountDetails) {
    toast.add('No hay credenciales guardadas en este pedido', 'error')
    return
  }
  const acc = order.accountDetails
  let text = `🎬 Credenciales para ${order.platform} (${order.customerNumber}):\n` +
    `📧 Correo: ${acc.email}\n` +
    `🔑 Contraseña: ${acc.pass}\n`
  if (acc.profiles && acc.profiles.length > 0) {
    text += `\n👤 Perfiles asignados:\n`
    acc.profiles.forEach(p => {
      text += `• ${p.name} - PIN: ${p.pin}\n`
    })
  }
  navigator.clipboard.writeText(text).then(() => {
    toast.add('Credenciales copiadas al portapapeles', 'success')
  }).catch(() => {
    toast.add('Error al copiar', 'error')
  })
}

const resendOrderCredentialsWhatsApp = (order) => {
  if (!order.accountDetails) {
    toast.add('El pedido no tiene credenciales despachadas', 'error')
    return
  }
  const msg = adminStore.generateDeliveryWhatsAppMessage(order)
  const cleanPhone = (order.customerPhone || '').replace(/\D/g, '')
  window.open(`https://wa.me/${cleanPhone}?text=${encodeURIComponent(msg)}`, '_blank')
}

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

// MODAL DE RESULTADO DE ENTREGA & ENVÍO POR WHATSAPP
const showDeliveryResultModal = ref(false)
const deliveredOrderData = ref(null)
const deliveryWhatsAppText = ref('')

const handleSmartDispatch = (order) => {
  const result = adminStore.smartDispatchOrder(order.id)
  if (result && result.success) {
    deliveredOrderData.value = result.order
    deliveryWhatsAppText.value = result.deliveryMessage
    showDeliveryResultModal.value = true
    toast.add(`¡Pedido #${order.id} despachado exitosamente con auto-asignación!`, 'success')
  } else {
    toast.add(result?.error || 'No se pudo realizar el despacho inteligente', 'error')
  }
}

const copyDeliveryMessage = () => {
  if (!deliveryWhatsAppText.value) return
  navigator.clipboard.writeText(deliveryWhatsAppText.value).then(() => {
    toast.add('¡Mensaje con credenciales y PINs copiado al portapapeles!', 'success')
  }).catch(() => {
    toast.add('Error al copiar al portapapeles', 'error')
  })
}

const sendDeliveryMessageWhatsApp = () => {
  if (!deliveredOrderData.value || !deliveryWhatsAppText.value) return
  const cleanPhone = (deliveredOrderData.value.customerPhone || '').replace(/\D/g, '')
  window.open(`https://wa.me/${cleanPhone}?text=${encodeURIComponent(deliveryWhatsAppText.value)}`, '_blank')
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
    showDispatchModal.value = false
    deliveredOrderData.value = result.order
    deliveryWhatsAppText.value = result.deliveryMessage
    showDeliveryResultModal.value = true
    toast.add(`¡Entrega confirmada! Cuenta y PINs despachados a ${selectedOrder.value.customerName}.`, 'success')
  } else {
    toast.add(result?.error || 'Error al realizar el despacho de la cuenta', 'error')
  }
}

// RESTAURACIÓN Y AUDITORÍA DE BASE DE DATOS DEPURADA
const isResettingDb = ref(false)

const handleResetToCleanDatabase = async () => {
  if (!confirm('⚠️ ¿Estás seguro de que deseas RESTAURAR LA BASE DE DATOS DEPURADA?\n\nEsta acción eliminará registros de prueba, reseteará órdenes, pedidos físicos y comprobantes temporales, e inicializará el catálogo oficial y el stock inicial de cuentas.')) {
    return
  }

  isResettingDb.value = true
  try {
    await adminStore.resetDatabaseToCleanSeed()
    toast.add('¡Base de datos restaurada y depurada con éxito para producción!', 'success')
  } catch (err) {
    toast.add('Error al restaurar base de datos: ' + err.message, 'error')
  } finally {
    isResettingDb.value = false
  }
}

// Funciones de auto-generación de stock eliminadas por política de inventario real

// Respaldo del Sistema (Backup JSON)
const backupFileInput = ref(null)

const triggerImportBackup = () => {
  backupFileInput.value?.click()
}

const handleBackupFile = (event) => {
  const file = event.target.files?.[0]
  if (!file) return
  const reader = new FileReader()
  reader.onload = async (e) => {
    try {
      const ok = importDatabaseBackup(e.target.result)
      if (ok) {
        toast.add('Backup importado con éxito. Recargando página...', 'success')
        setTimeout(() => window.location.reload(), 1000)
      } else {
        toast.add('El archivo de backup no tiene un formato válido', 'error')
      }
    } catch (err) {
      toast.add('Error al procesar el archivo de backup', 'error')
    }
  }
  reader.readAsText(file)
}

const downloadBackupFile = () => {
  const backup = exportDatabaseBackup()
  if (!backup) {
    toast.add('Error al generar backup', 'error')
    return
  }
  const blob = new Blob([backup], { type: 'application/json' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `streampro_backup_${new Date().toISOString().split('T')[0]}.json`
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  URL.revokeObjectURL(url)
  toast.add('Backup descargado exitosamente', 'info')
}

// Contactar directamente al cliente de una venta oficial por WhatsApp
const contactDirectCustomerWhatsApp = (order) => {
  const cleanPhone = formatWaPhone(order.customerPhone)
  const msg = `¡Hola ${order.customerName}! Te contactamos desde StreamePro Oficial respecto a tu pedido de ${order.platform} (${order.customerNumber}). Estamos preparando tu activación.`
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
  const cleanPhone = formatWaPhone(data.reseller.contactNumber)
  window.open(`https://wa.me/${cleanPhone}?text=${encodeURIComponent(data.message)}`, '_blank')
}

const contactApplicantWhatsApp = (app) => {
  const cleanPhone = formatWaPhone(app.whatsapp)
  const msg = `¡Hola ${app.fullName}! Te contactamos desde el Panel de Proveedor Mayorista de StreamePro respecto a tu postulación como distribuidor oficial.`
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

const isSyncingCloud = ref(false)
const manualCloudSync = async () => {
  isSyncingCloud.value = true
  try {
    const res = await adminStore.syncFromCloud()
    if (res.success) {
      toast.add(`¡Nube Sincronizada! ${res.streamingCount} órdenes streaming, ${res.physicalCount} físicas.`, 'success')
    } else {
      toast.add('Error al sincronizar con Firebase: ' + (res.error || ''), 'error')
    }
  } catch (e) {
    toast.add('Error: ' + e.message, 'error')
  } finally {
    isSyncingCloud.value = false
  }
}

onMounted(async () => {
  // Asegurar catálogo de productos físicos, sitios y gaming disponibles
  adminStore.ensurePhysicalProductsLoaded()
  adminStore.ensureGamingProviderSitesLoaded()
  adminStore.ensureGamingProductsLoaded()

  // Sincronización automática de órdenes y postulaciones en la nube
  adminStore.initCloudSync()

  // Recargar aplicaciones de revendedores desde almacenamiento local
  const freshApps = loadDb(DB_KEYS.RESELLER_APPLICATIONS, null)
  if (Array.isArray(freshApps) && freshApps.length > 0) {
    adminStore.resellerApplications = freshApps
  }

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

watch(adminTab, (newTab) => {
  if (newTab === 'resellers') {
    adminStore.syncFromCloud().catch(() => {})
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

// GESTIÓN DE VISIBILIDAD DE LA TIENDA PÚBLICA (Mostrar u Ocultar Tienda)
const toggleStoreVisibility = () => {
  const isVisible = adminStore.toggleStoreVisibility()
  if (isVisible) {
    toast.add('✅ La tienda ahora es PÚBLICA y visible para todos los clientes.', 'success')
  } else {
    toast.add('⚠️ La tienda ahora está OCULTA. Los clientes verán aviso de mantenimiento.', 'warning')
  }
}

// GESTIÓN DE VISIBILIDAD DE TIENDA DE PRODUCTOS FÍSICOS (Mostrar u Ocultar en Navegación y Catálogo)
const togglePhysicalStoreVisibility = () => {
  const isVisible = adminStore.togglePhysicalStoreVisibility()
  if (isVisible) {
    toast.add('✅ Tienda de productos físicos ACTIVADA y visible en catálogo y menú público.', 'success')
  } else {
    toast.add('⚠️ Tienda de productos físicos DESACTIVADA y oculta del catálogo y menú público.', 'warning')
  }
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
    whatsapp: adminStore.adminAuth?.whatsapp || '+54 9 291 565-9321'
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
            <h3 class="font-black text-white text-sm sm:text-base">Centro Mayorista StreamePro S.R.L.</h3>
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
        <!-- BOTÓN MOSTRAR U OCULTAR TIENDA PÚBLICA -->
        <button 
          type="button"
          @click="toggleStoreVisibility"
          :class="[
            'px-3.5 py-1.5 rounded-xl text-xs font-black transition-all flex items-center gap-1.5 shadow-md cursor-pointer border',
            adminStore.isStoreVisible 
              ? 'bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 border-emerald-500/40' 
              : 'bg-red-500/25 hover:bg-red-500/35 text-red-300 border-red-500/50 animate-pulse'
          ]"
          :title="adminStore.isStoreVisible ? 'La tienda está VISIBLE para los clientes. Clic para ocultarla.' : 'La tienda está OCULTA para los clientes (modo mantenimiento). Clic para activarla.'"
        >
          <span>{{ adminStore.isStoreVisible ? '🟢 Tienda Global: VISIBLE' : '🔴 Tienda Global: OCULTA' }}</span>
        </button>
        <!-- BOTÓN MOSTRAR U OCULTAR TIENDA DE PRODUCTOS FÍSICOS -->
        <button 
          type="button"
          @click="togglePhysicalStoreVisibility"
          :class="[
            'px-3.5 py-1.5 rounded-xl text-xs font-black transition-all flex items-center gap-1.5 shadow-md cursor-pointer border',
            adminStore.isPhysicalStoreVisible 
              ? 'bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border-amber-500/40' 
              : 'bg-red-500/25 hover:bg-red-500/35 text-red-300 border-red-500/50 animate-pulse'
          ]"
          :title="adminStore.isPhysicalStoreVisible ? 'La tienda física está VISIBLE en el catálogo y menú. Clic para ocultarla.' : 'La tienda física está DESACTIVADA y oculta del catálogo y menú. Clic para activarla.'"
        >
          <span>{{ adminStore.isPhysicalStoreVisible ? '📦 Físicos: ACTIVA (VISIBLE)' : '📦 Físicos: DESACTIVADA (OCULTA)' }}</span>
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

    <!-- BANNER DE CONTROL DE ESTADO DE LA TIENDA PÚBLICA -->
    <div :class="[
      'p-4 rounded-2xl border transition-all flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-lg backdrop-blur-sm',
      adminStore.isStoreVisible 
        ? 'bg-emerald-950/30 border-emerald-500/30 text-emerald-200' 
        : 'bg-red-950/40 border-red-500/50 text-red-200 ring-2 ring-red-500/30'
    ]">
      <div class="flex items-center gap-3.5">
        <div :class="[
          'w-11 h-11 rounded-2xl flex items-center justify-center text-2xl shrink-0 font-bold border shadow-inner',
          adminStore.isStoreVisible ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40' : 'bg-red-500/25 text-red-300 border-red-500/50'
        ]">
          {{ adminStore.isStoreVisible ? '🏪' : '🔒' }}
        </div>
        <div>
          <div class="flex flex-wrap items-center gap-2">
            <strong class="font-black text-sm text-white">
              Visibilidad de la Tienda: {{ adminStore.isStoreVisible ? 'PÚBLICA Y ABIERTA' : 'OCULTA (MODO MANTENIMIENTO)' }}
            </strong>
            <span :class="[
              'text-[10px] font-mono px-2 py-0.5 rounded-full font-black uppercase tracking-wider',
              adminStore.isStoreVisible ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-red-500/20 text-red-300 border border-red-500/40 animate-pulse'
            ]">
              {{ adminStore.isStoreVisible ? '✓ Clientes Comprando' : '⚠️ Clientes en Mantenimiento' }}
            </span>
          </div>
          <p class="text-xs text-slate-300 mt-0.5 leading-relaxed">
            {{ adminStore.isStoreVisible 
              ? 'La tienda pública está abierta. Los clientes pueden explorar catálogos, combos y registrar pedidos normalmente.' 
              : 'La tienda pública está oculta al público general (muestra aviso de mantenimiento y botón de WhatsApp). Solo el Administrador puede ingresar a ver y editar.' }}
          </p>
        </div>
      </div>

      <div class="flex flex-wrap items-center gap-2 shrink-0 w-full sm:w-auto justify-end">
        <button 
          type="button" 
          @click="toggleStoreVisibility"
          :class="[
            'px-4 py-2.5 rounded-xl text-xs font-black transition-all shadow-lg flex items-center gap-2 cursor-pointer',
            adminStore.isStoreVisible 
              ? 'bg-red-600 hover:bg-red-500 text-white shadow-red-600/30' 
              : 'bg-emerald-500 hover:bg-emerald-400 text-slate-950 shadow-emerald-500/30'
          ]"
        >
          <span>{{ adminStore.isStoreVisible ? '🔴 Ocultar Tienda Global' : '🟢 Mostrar Tienda Global' }}</span>
        </button>
        <button 
          type="button" 
          @click="togglePhysicalStoreVisibility"
          :class="[
            'px-4 py-2.5 rounded-xl text-xs font-black transition-all shadow flex items-center gap-1.5 cursor-pointer border',
            adminStore.isPhysicalStoreVisible 
              ? 'bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border-amber-500/40' 
              : 'bg-red-500/20 hover:bg-red-500/30 text-red-300 border-red-500/40 animate-pulse'
          ]"
          :title="adminStore.isPhysicalStoreVisible ? 'Desactivar tienda de productos físicos en el catálogo y menú' : 'Activar tienda de productos físicos en el catálogo y menú'"
        >
          <span>{{ adminStore.isPhysicalStoreVisible ? '📦 Físicos en Catálogo: ACTIVA' : '📦 Físicos en Catálogo: DESACTIVADA' }}</span>
        </button>
        <a 
          href="/" 
          target="_blank" 
          class="bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs px-3.5 py-2.5 rounded-xl border border-slate-700 transition flex items-center gap-1.5"
          title="Ver cómo se ve la tienda pública en una nueva pestaña"
        >
          <span>👁️ Ver Tienda ↗</span>
        </a>
      </div>
    </div>

    <!-- Navegación de Pestañas del Proveedor -->
    <div class="flex flex-wrap items-center justify-between gap-4">
      <div class="flex items-center gap-2 p-1.5 bg-slate-900/60 backdrop-blur rounded-2xl border border-slate-800 overflow-x-auto no-scrollbar flex-nowrap sm:flex-wrap w-full">
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
          🚚 Despachos ({{ streamingStats.pending }})
          <span v-if="adminStore.openSupportTickets?.length > 0" class="bg-rose-500 text-white font-mono text-[10px] px-1.5 py-0.2 rounded-full font-black animate-pulse" title="Tickets de soporte abiertos">
            🆘 {{ adminStore.openSupportTickets.length }}
          </span>
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
          @click="adminTab = 'users'"
          :class="['px-5 py-2.5 rounded-xl font-bold text-xs transition-all flex items-center gap-2', adminTab === 'users' ? 'bg-sky-400 text-slate-950 font-black shadow' : 'text-slate-400 hover:text-white hover:bg-slate-800']"
        >
          📇 Directorio Clientes & PINs ({{ adminStore.registeredUsers.length }})
        </button>
        <button 
          @click="adminTab = 'physical'"
          :class="['px-5 py-2.5 rounded-xl font-bold text-xs transition-all flex items-center gap-2', adminTab === 'physical' ? 'bg-amber-400 text-slate-950 font-black shadow' : 'text-slate-400 hover:text-white hover:bg-slate-800']"
        >
          📦 Tienda & Catálogo Físico ({{ adminStore.physicalProducts.length }})
          <span v-if="adminStore.pendingPhysicalOrders.length > 0" class="bg-red-500 text-white font-mono text-[10px] px-1.5 py-0.2 rounded-full font-black animate-pulse">
            {{ adminStore.pendingPhysicalOrders.length }} pedidos
          </span>
        </button>
        <button 
          @click="adminTab = 'packages'"
          :class="['px-5 py-2.5 rounded-xl font-bold text-xs transition-all flex items-center gap-2', adminTab === 'packages' ? 'bg-gradient-to-r from-purple-500 to-pink-500 text-white font-black shadow' : 'text-slate-400 hover:text-white hover:bg-slate-800']"
        >
          🎁 Paquetes & Ofertas ({{ adminStore.packages.length }})
        </button>
        <button 
          @click="adminTab = 'gaming'"
          :class="['px-5 py-2.5 rounded-xl font-bold text-xs transition-all flex items-center gap-2', adminTab === 'gaming' ? 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white font-black shadow' : 'text-slate-400 hover:text-white hover:bg-slate-800']"
        >
          🎮 Juegos Digitales ({{ adminStore.processedGamingProducts.length }})
          <span v-if="adminStore.gamingSalesStats?.pendingCount > 0" class="bg-amber-400 text-slate-950 font-mono text-[10px] px-1.5 py-0.2 rounded-full font-black animate-pulse" title="Pedidos gamer pendientes">
            {{ adminStore.gamingSalesStats.pendingCount }} pend.
          </span>
          <span v-else-if="adminStore.gamingOrders?.length > 0" class="bg-indigo-400 text-slate-950 font-mono text-[10px] px-1.5 py-0.2 rounded-full font-black">
            {{ adminStore.gamingOrders.length }}
          </span>
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
        <button 
          @click="adminTab = 'database'"
          :class="['px-5 py-2.5 rounded-xl font-bold text-xs transition-all flex items-center gap-2', adminTab === 'database' ? 'bg-rose-500 text-white font-black shadow' : 'text-slate-400 hover:text-white hover:bg-slate-800']"
        >
          💾 Sistema & Base de Datos
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

    <!-- ACCESO RÁPIDO DUAL: TIENDA DE SUSCRIPCIONES & TIENDA DE PRODUCTOS FÍSICOS -->
    <div v-if="adminTab === 'inventory'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <!-- Tarjeta 1: Tienda de Suscripciones -->
      <div class="bg-gradient-to-br from-sky-950/50 via-slate-900 to-indigo-950/40 p-6 rounded-3xl border border-sky-500/30 shadow-xl flex flex-col justify-between space-y-4">
        <div class="flex items-start justify-between gap-3">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-sky-500/20 text-sky-400 border border-sky-500/30 flex items-center justify-center text-2xl shadow-inner">
              🎬
            </div>
            <div>
              <h3 class="text-lg font-black text-white">Tienda de Suscripciones Digitales</h3>
              <p class="text-xs text-slate-300">8 plataformas activas (Netflix, Max, Spotify, Disney+, IPTV, etc.)</p>
            </div>
          </div>
          <span class="text-xs font-mono font-bold bg-sky-400/15 text-sky-300 border border-sky-400/30 px-2.5 py-1 rounded-xl">
            {{ adminStore.orders.length }} Órdenes
          </span>
        </div>
        <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-white/[0.08]">
          <button 
            type="button" 
            @click="adminTab = 'catalog'"
            class="bg-cyan-500 hover:bg-cyan-400 text-slate-950 font-black text-xs px-3.5 py-2 rounded-xl transition shadow flex items-center gap-1.5 cursor-pointer"
          >
            <span>🏷️ Editar Precios & Planes</span>
          </button>
          <button 
            type="button" 
            @click="adminTab = 'orders'"
            class="bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs px-3.5 py-2 rounded-xl border border-slate-700 transition flex items-center gap-1.5 cursor-pointer"
          >
            <span>🚚 Despachos ({{ adminStore.pendingOrders.length }})</span>
          </button>
          <a 
            href="/?sec=digital" 
            target="_blank" 
            class="bg-sky-500/15 hover:bg-sky-500/25 text-sky-300 border border-sky-500/30 font-bold text-xs px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 ml-auto"
          >
            <span>🌐 Ver Tienda Pública ↗</span>
          </a>
        </div>
      </div>

      <!-- Tarjeta 2: Tienda de Productos Físicos -->
      <div class="bg-gradient-to-br from-amber-950/50 via-slate-900 to-orange-950/40 p-6 rounded-3xl border border-amber-500/30 shadow-xl flex flex-col justify-between space-y-4">
        <div class="flex items-start justify-between gap-3">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-amber-500/20 text-amber-400 border border-amber-500/30 flex items-center justify-center text-2xl shadow-inner">
              📦
            </div>
            <div>
              <h3 class="text-lg font-black text-white">Tienda & Catálogo de Productos Físicos</h3>
              <p class="text-xs text-slate-300">{{ adminStore.physicalProducts.length }} productos sincronizados (tecnología, bazar, herramientas)</p>
            </div>
          </div>
          <span class="text-xs font-mono font-bold bg-amber-400/15 text-amber-300 border border-amber-400/30 px-2.5 py-1 rounded-xl">
            {{ adminStore.pendingPhysicalOrders.length }} Pendientes
          </span>
        </div>
        <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-white/[0.08]">
          <button 
            type="button" 
            @click="adminTab = 'physical'"
            class="bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-xs px-3.5 py-2 rounded-xl transition shadow flex items-center gap-1.5 cursor-pointer"
          >
            <span>📦 Ver Catálogo & Pedidos Físicos</span>
          </button>
          <a 
            href="/p" 
            target="_blank" 
            class="bg-amber-500/15 hover:bg-amber-500/25 text-amber-300 border border-amber-500/30 font-bold text-xs px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 ml-auto"
          >
            <span>🌐 Ver Catálogo en Vivo (/p) ↗</span>
          </a>
        </div>
      </div>
    </div>

    <!-- Módulo de Renovaciones Solicitadas: El Proveedor puede cambiar datos de cuenta -->
    <div v-if="adminTab === 'renewals'" class="bg-slate-900/40 rounded-3xl border border-slate-800 p-8 backdrop-blur-md">
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
    <div v-if="adminTab === 'orders'" class="bg-slate-900/40 rounded-3xl border border-slate-800 p-6 sm:p-8 backdrop-blur-md space-y-6">
      
      <!-- HEADER CON TÍTULO Y BOTÓN DE SINCRONIZACIÓN -->
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 pb-4 border-b border-slate-800">
        <div>
          <div class="flex flex-wrap items-center gap-2">
            <h2 class="text-2xl font-black text-white">Centro de Despacho & Control de Pedidos Streaming</h2>
            <span class="text-xs font-mono font-bold bg-amber-400/20 text-amber-300 border border-amber-400/30 px-2.5 py-0.5 rounded-full">
              {{ streamingStats.pending }} Pendiente(s)
            </span>
          </div>
          <p class="text-xs text-slate-400 mt-1">
            Entrega cuentas oficiales, verifica comprobantes de pago y consulta el historial completo de ventas directas y de revendedores.
          </p>
        </div>

        <div class="flex items-center gap-3">
          <button 
            @click="manualCloudSync" 
            :disabled="isSyncingCloud"
            class="bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs px-4 py-2.5 rounded-xl transition flex items-center gap-2 cursor-pointer shadow-lg disabled:opacity-50 shrink-0"
            title="Sincronizar pedidos desde la base de datos de Firebase"
          >
            <span>{{ isSyncingCloud ? '⏳ Sincronizando...' : '🔄 Sincronizar Nube' }}</span>
          </button>
        </div>
      </div>

      <!-- AVISO DE PEDIDOS GAMER PENDIENTES (SEPARACIÓN LIMPIA STREAMING / VIDEOJUEGOS) -->
      <div
        v-if="(adminStore.gamingSalesStats?.pendingCount || 0) > 0"
        class="p-3.5 rounded-2xl bg-indigo-500/10 border border-indigo-500/30 flex flex-wrap items-center justify-between gap-3"
      >
        <div class="flex items-center gap-2 text-xs text-indigo-200">
          <span class="text-lg">🎮</span>
          <span>Hay <strong class="text-white">{{ adminStore.gamingSalesStats.pendingCount }} pedido(s) de Videojuegos Digitales</strong> pendientes de despacho en la sección Gaming.</span>
        </div>
        <button
          type="button"
          @click="adminTab = 'gaming'; gamingAdminSubTab = 'orders'"
          class="px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-black transition cursor-pointer shrink-0"
        >
          Ir a Pedidos Gamer →
        </button>
      </div>

      <!-- BANDEJA DE TICKETS, CONSULTAS MANUALES & FALLAS REPORTADAS -->
      <div v-if="(adminStore.supportTickets || []).length > 0" class="bg-[#020617] border border-rose-500/30 rounded-2xl p-4 sm:p-5 space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <div class="flex items-center gap-2">
            <span class="text-lg">📩</span>
            <div>
              <h3 class="text-sm font-black text-white">
                Consultas de Juegos Manuales & Tickets de Soporte ({{ adminStore.openSupportTickets.length }} abiertos)
              </h3>
              <p class="text-[11px] text-slate-400">Solicitudes de juegos manuales y reportes enviados por clientes o revendedores oficiales.</p>
            </div>
          </div>
          <div class="flex flex-wrap items-center gap-1.5">
            <button
              type="button"
              @click="supportTicketFilter = 'OPEN'"
              :class="['px-3 py-1 rounded-lg text-[11px] font-bold cursor-pointer', supportTicketFilter === 'OPEN' ? 'bg-rose-600 text-white' : 'bg-slate-900 text-slate-400 hover:text-white']"
            >
              Pendientes ({{ adminStore.openSupportTickets.length }})
            </button>
            <button
              type="button"
              @click="supportTicketFilter = 'GAME_REQUESTS'"
              :class="['px-3 py-1 rounded-lg text-[11px] font-bold cursor-pointer', supportTicketFilter === 'GAME_REQUESTS' ? 'bg-amber-500 text-slate-950 font-black' : 'bg-slate-900 text-amber-300 hover:text-white']"
            >
              🎮 Pedidos Manuales ({{ openGameRequestCount }})
            </button>
            <button
              type="button"
              @click="supportTicketFilter = 'SUPPORT'"
              :class="['px-3 py-1 rounded-lg text-[11px] font-bold cursor-pointer', supportTicketFilter === 'SUPPORT' ? 'bg-sky-600 text-white' : 'bg-slate-900 text-slate-400 hover:text-white']"
            >
              🆘 Fallas ({{ openSupportOnlyCount }})
            </button>
            <button
              type="button"
              @click="supportTicketFilter = 'ALL'"
              :class="['px-3 py-1 rounded-lg text-[11px] font-bold cursor-pointer', supportTicketFilter === 'ALL' ? 'bg-indigo-600 text-white' : 'bg-slate-900 text-slate-400 hover:text-white']"
            >
              Todos ({{ adminStore.supportTickets.length }})
            </button>
          </div>
        </div>

        <div v-if="filteredSupportTicketsForAdmin.length === 0" class="text-xs text-emerald-400 font-bold py-2">
          ✅ No hay elementos pendientes en este filtro.
        </div>
        <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-80 overflow-y-auto custom-scrollbar">
          <div
            v-for="t in filteredSupportTicketsForAdmin"
            :key="t.id"
            :class="['p-3 rounded-xl border text-xs space-y-2', t.status === 'Resuelto' ? 'bg-slate-950/60 border-emerald-500/20 opacity-75' : (t.type === 'game_request' ? 'bg-slate-950 border-amber-500/35' : 'bg-slate-950 border-rose-500/30')]"
          >
            <div class="flex items-center justify-between gap-2">
              <div class="flex items-center gap-1.5 flex-wrap">
                <span class="font-mono font-bold text-indigo-300">#{{ t.id }}</span>
                <span :class="['text-[10px] font-bold px-1.5 py-0.5 rounded', t.type === 'game_request' ? 'bg-amber-500/20 text-amber-300' : 'bg-rose-500/20 text-rose-300']">
                  {{ t.type === 'game_request' ? '🎮 Juego Manual' : '🆘 Soporte' }}
                </span>
                <span :class="['text-[10px] font-bold px-1.5 py-0.5 rounded', t.sourceRole === 'reseller' ? 'bg-purple-500/20 text-purple-300' : 'bg-sky-500/20 text-sky-300']">
                  {{ t.sourceRole === 'reseller' ? '🏪 Revendedor' : '👤 Cliente' }}
                </span>
                <span class="font-black text-white">{{ t.clientName }}</span>
                <span class="text-[10px] text-slate-400 font-mono">({{ t.phone || t.clientNumber }})</span>
              </div>
              <span :class="['text-[10px] px-2 py-0.5 rounded-full font-bold', t.status === 'Resuelto' ? 'bg-emerald-500/20 text-emerald-300' : 'bg-amber-500/20 text-amber-300']">
                {{ t.status }}
              </span>
            </div>
            <p class="text-amber-300 font-bold">
              {{ t.requestedGame ? `🎮 ${t.requestedGame}` : t.subject }}
              <span v-if="t.requestedPlatform" class="font-mono text-[10px] text-cyan-300"> · {{ t.requestedPlatform }}</span>
              <span v-if="t.requestedLicense" class="text-[10px] text-indigo-300"> · {{ t.requestedLicense }}</span>
              <span v-if="t.orderId" class="font-mono text-slate-400"> · Orden #{{ t.orderId }}</span>
            </p>
            <p class="text-slate-300 bg-slate-900/70 p-2 rounded-lg">{{ t.message }}</p>

            <div v-if="t.status !== 'Resuelto'" class="flex flex-wrap gap-1.5 pt-1">
              <input
                v-model="supportTicketReplies[t.id]"
                type="text"
                :placeholder="t.type === 'game_request' ? 'Responder disponibilidad o cotización...' : 'Respuesta para el cliente (Bóveda)...'"
                class="flex-1 min-w-[140px] bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-1.5 text-[11px] text-white outline-none"
              >
              <button
                type="button"
                @click="handleResolveSupportTicket(t)"
                class="px-2.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[11px] cursor-pointer"
              >
                ✅ Responder
              </button>
              <button
                v-if="t.phone"
                type="button"
                @click="sendTicketReplyWhatsApp(t)"
                class="px-2.5 py-1.5 rounded-lg bg-sky-600/20 hover:bg-sky-600 text-sky-300 hover:text-white border border-sky-500/30 font-bold text-[11px] cursor-pointer"
                title="Responder por WhatsApp"
              >
                💬 WA
              </button>
              <button
                v-if="t.type === 'game_request'"
                type="button"
                @click="openSupportTicketInPos(t)"
                class="px-2.5 py-1.5 rounded-lg bg-pink-600/20 hover:bg-pink-600 text-pink-300 hover:text-white border border-pink-500/30 font-bold text-[11px] cursor-pointer"
                title="Abrir en POS Venta Rápida Gamer"
              >
                🛒 POS
              </button>
            </div>
            <p v-else-if="t.adminReply" class="text-[11px] text-emerald-300 italic">
              Respuesta: {{ t.adminReply }}
            </p>
          </div>
        </div>
      </div>

      <!-- MÉTRICAS Y RESUMEN DE GESTIÓN ORDENADA -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="bg-[#020617] p-4 rounded-2xl border border-amber-500/30">
          <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider block">Por Despachar</span>
          <span class="text-2xl font-black text-amber-400 font-mono">{{ streamingStats.pending }}</span>
        </div>
        <div class="bg-[#020617] p-4 rounded-2xl border border-emerald-500/30">
          <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider block">Entregados / Activos</span>
          <span class="text-2xl font-black text-emerald-400 font-mono">{{ streamingStats.dispatched }}</span>
        </div>
        <div class="bg-[#020617] p-4 rounded-2xl border border-slate-800">
          <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider block">Total Histórico</span>
          <span class="text-2xl font-black text-white font-mono">{{ streamingStats.all }}</span>
        </div>
        <div class="bg-[#020617] p-4 rounded-2xl border border-sky-500/30">
          <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider block">Facturado Total</span>
          <span class="text-xl font-black text-sky-400 font-mono">${{ streamingStats.totalAmount.toLocaleString() }} ARS</span>
        </div>
      </div>

      <!-- BARRA DE FILTROS Y BÚSQUEDA ORDENADA -->
      <div class="flex flex-col md:flex-row gap-3 bg-slate-950 p-4 rounded-2xl border border-slate-800">
        <div class="flex-1 relative">
          <span class="absolute left-3 top-2.5 text-slate-500 text-xs">🔍</span>
          <input 
            v-model="streamingOrderSearch" 
            type="text" 
            placeholder="Buscar por N° Orden, cliente, WhatsApp, PIN, plataforma..." 
            class="w-full bg-slate-900 border border-slate-800 rounded-xl pl-9 pr-3 py-2 text-xs text-white outline-none focus:border-brand-accent transition-colors"
          >
        </div>
        
        <div class="flex flex-wrap items-center gap-2">
          <!-- Filtro por Estado -->
          <select 
            v-model="streamingOrderStatusFilter" 
            class="bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white outline-none focus:border-brand-accent cursor-pointer"
          >
            <option value="All">Todos los Estados ({{ streamingStats.all }})</option>
            <option value="Waiting Provider">⏳ Pendientes ({{ streamingStats.pending }})</option>
            <option value="Dispatched">✅ Despachados ({{ streamingStats.dispatched }})</option>
            <option value="Cancelado">❌ Cancelados ({{ streamingStats.cancelled }})</option>
          </select>

          <!-- Filtro por Canal / Revendedor -->
          <select 
            v-model="streamingOrderResellerFilter" 
            class="bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white outline-none focus:border-brand-accent cursor-pointer"
          >
            <option value="All">Todos los Canales</option>
            <option value="DIRECT">🛒 Ventas Directas Oficiales</option>
            <option v-for="res in adminStore.resellers" :key="res.id" :value="res.id">
              [{{ res.id }}] {{ res.name }}
            </option>
          </select>
        </div>
      </div>

      <!-- LISTA DE PEDIDOS FILTRADOS -->
      <div v-if="filteredStreamingOrdersForAdmin.length === 0" class="text-slate-500 text-center py-12 bg-slate-900/30 rounded-2xl border border-slate-800 border-dashed text-xs">
        <p class="font-medium">No se encontraron pedidos de streaming con los filtros seleccionados.</p>
      </div>

      <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <div 
          v-for="order in filteredStreamingOrdersForAdmin" 
          :key="order.id" 
          :class="[
            'bg-[#020617] p-6 rounded-2xl border transition-all shadow-xl space-y-4',
            order.status === 'Waiting Provider' || order.status === 'Pendiente' ? 'border-amber-500/40 hover:border-amber-400' :
            order.status === 'Dispatched' ? 'border-emerald-500/40 hover:border-emerald-400' :
            'border-rose-500/30 opacity-80'
          ]"
        >
          <div class="flex justify-between items-start gap-4">
            <div class="space-y-1">
              <div class="flex flex-wrap items-center gap-2">
                <span v-if="order.orderType === 'gaming' || order.gameTitle || (order.platform && (order.platform.includes('PS4') || order.platform.includes('PS5')))" class="text-[10px] font-black uppercase bg-indigo-500/20 text-indigo-300 border border-indigo-500/40 px-2 py-0.5 rounded-full flex items-center gap-1">
                  🎮 VIDEOJUEGO
                </span>
                <span class="text-lg font-black text-white">
                  {{ order.gameTitle || order.platform }}
                </span>
                <button 
                  v-if="order.orderType === 'gaming' || order.gameTitle || (order.platform && (order.platform.includes('PS4') || order.platform.includes('PS5')))"
                  type="button" 
                  @click="openAssignGameModal(order)"
                  class="text-[10px] bg-indigo-500/20 hover:bg-indigo-500/40 text-indigo-300 border border-indigo-500/30 px-2 py-0.5 rounded-lg font-bold transition flex items-center gap-1 cursor-pointer"
                  title="Asignar o editar nombre exacto del videojuego"
                >
                  ✏️ Editar Juego
                </button>
                <span class="text-xs font-mono text-brand-accent bg-brand-accent/15 px-2 py-0.5 rounded border border-brand-accent/30 font-bold">
                  {{ order.id }}
                </span>
                <span class="text-xs font-mono text-slate-400 bg-slate-900 px-2 py-0.5 rounded border border-slate-800">
                  {{ order.customerNumber }}
                </span>
                <span v-if="order.pin" class="text-[10px] bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 px-2 py-0.5 rounded font-mono font-bold" title="PIN de Bóveda del Cliente">
                  🔐 PIN: {{ order.pin }}
                </span>
                <span :class="[
                  'text-[10px] font-bold px-2 py-0.5 rounded-full border',
                  order.status === 'Waiting Provider' || order.status === 'Pendiente' ? 'bg-amber-500/15 text-amber-300 border-amber-500/30 animate-pulse' :
                  order.status === 'Dispatched' ? 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30' :
                  'bg-rose-500/15 text-rose-300 border-rose-500/30'
                ]">
                  {{ 
                    order.status === 'Waiting Provider' || order.status === 'Pendiente' ? '⏳ Pendiente' :
                    order.status === 'Dispatched' ? '✅ Despachado' :
                    '❌ Cancelado'
                  }}
                </span>
              </div>

              <!-- Metadatos del Cliente -->
              <div class="text-xs text-slate-400 space-y-0.5 pt-1">
                <p>
                  Cliente: <strong class="text-white">{{ order.customerName }}</strong> 
                  <span class="font-mono text-slate-300 ml-1">({{ order.customerPhone }})</span>
                </p>
                <p v-if="order.activationEmail" class="text-indigo-300 font-bold flex items-center gap-1.5 mt-0.5 bg-indigo-950/40 p-1.5 rounded-lg border border-indigo-500/30">
                  <span>✉️</span>
                  <span>Correo Activación: <strong class="text-white font-mono">{{ order.activationEmail }}</strong></span>
                </p>
                <p class="pt-0.5">
                  <span v-if="order.resellerId === 'DIRECT' || !order.resellerId" class="text-[10px] font-black uppercase bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 px-2 py-0.5 rounded-full inline-flex items-center gap-1">
                    <span>🛒</span> VENTA DIRECTA OFICIAL (PROVEEDOR)
                  </span>
                  <span v-else class="text-[11px] text-amber-400 font-medium">
                    Revendedor: <strong>[{{ order.resellerId }}] {{ order.resellerName || '' }}</strong>
                  </span>
                </p>
                <div class="flex flex-wrap items-center gap-2 text-[11px] text-slate-400 pt-0.5">
                  <span v-if="order.orderType === 'gaming' || order.gameTitle || (order.platform && (order.platform.includes('PS4') || order.platform.includes('PS5')))">
                    Modalidad: <strong class="text-amber-300">{{ order.licenseLabel || order.planLabel || '⭐️ Cuenta Primaria' }}</strong> • Plataforma: <strong class="text-slate-200">{{ order.gamePlatform || 'PS4 / PS5' }}</strong>
                  </span>
                  <span v-else>Plan: <strong class="text-slate-200">{{ order.planLabel || order.devices + ' Pantallas' }}</strong></span>
                  <span>•</span>
                  <span>Monto: <strong class="text-emerald-400 font-mono">${{ (order.clientPrice || order.total || 0).toLocaleString() }} ARS</strong></span>
                  <span v-if="order.paymentMethod">• {{ order.paymentMethod }}</span>
                </div>
              </div>

              <!-- Comprobante adjunto -->
              <div class="pt-1 flex flex-wrap items-center gap-2">
                <button 
                  v-if="order.hasReceipt || order.receiptStatus === 'pending_verification'"
                  @click="openReceiptViewer(order, false)"
                  class="text-[10px] bg-sky-500/20 hover:bg-sky-500/30 text-sky-300 border border-sky-500/40 px-2.5 py-0.5 rounded-full font-mono font-bold flex items-center gap-1 transition-all cursor-pointer shadow-sm"
                  title="Ver comprobante adjunto en mini nube (JPG/PDF)"
                >
                  <span>☁️</span>
                  <span>Ver Comprobante ({{ order.receiptFileType === 'application/pdf' ? 'PDF' : 'JPG' }})</span>
                </button>
                <span v-else-if="order.receiptStatus === 'verified_and_purged'" class="text-[10px] bg-slate-800 text-slate-400 border border-slate-700 px-2 py-0.5 rounded-full font-mono">
                  ✓ Comprobante depurado
                </span>
                <span v-if="order.bankTransferVerified" class="text-[10px] bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 px-2 py-0.5 rounded-full font-mono font-bold flex items-center gap-1">
                  <span>💰</span> Acreditada en {{ order.bankTransferDetails?.bankName || 'Banco' }} (${{ (order.bankTransferDetails?.amount || order.clientPrice).toLocaleString() }})
                </span>
              </div>
            </div>

            <!-- BOTONES DE ACCIÓN RÁPIDA -->
            <div class="flex flex-col gap-2 shrink-0">
              <!-- Acciones para Pedidos Pendientes -->
              <template v-if="order.status === 'Waiting Provider' || order.status === 'Pendiente'">
                <button 
                  @click="handleSmartDispatch(order)" 
                  class="bg-brand-accent hover:bg-sky-400 text-slate-950 px-3.5 py-2 font-black text-xs rounded-xl transition-all shadow-[0_0_15px_rgba(56,189,248,0.3)] flex items-center justify-center gap-1.5 cursor-pointer"
                  title="Auto-asignar stock disponible y generar credenciales de inmediato"
                >
                  <span>⚡ Despacho Rápido (1-Clic)</span>
                </button>
                <button 
                  @click="openDispatchModal(order)" 
                  class="bg-amber-500/20 hover:bg-amber-500 text-amber-300 hover:text-slate-950 border border-amber-500/30 px-3 py-1.5 font-bold text-[11px] rounded-xl transition-all flex items-center justify-center gap-1 cursor-pointer"
                  title="Seleccionar cuenta y PINs manualmente"
                >
                  <span>⚙️ Despacho Manual</span>
                </button>
                <button 
                  v-if="order.orderType === 'gaming' || order.gameTitle || (order.platform && (order.platform.includes('PS4') || order.platform.includes('PS5')))"
                  @click="sendWhatsAppGameDelivery(order)"
                  class="bg-indigo-600 hover:bg-indigo-500 text-white px-3 py-1.5 font-bold text-[11px] rounded-xl transition-all flex items-center justify-center gap-1 cursor-pointer shadow"
                  title="Enviar datos y credenciales del videojuego por WhatsApp"
                >
                  <span>🎮 Entregar Juego WhatsApp</span>
                </button>
                <button 
                  v-if="order.resellerId === 'DIRECT' || !order.resellerId"
                  @click="contactDirectCustomerWhatsApp(order)" 
                  class="bg-emerald-600 hover:bg-emerald-500 text-white px-3 py-1.5 font-bold text-[11px] rounded-xl transition-colors flex items-center justify-center gap-1 cursor-pointer"
                  title="Contactar al cliente directamente por WhatsApp"
                >
                  <span>💬 WhatsApp Cliente</span>
                </button>
                <button 
                  v-else
                  @click="openIncidentModal(order)" 
                  class="bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white px-3 py-1.5 font-bold text-[11px] rounded-xl border border-slate-700 transition-colors cursor-pointer"
                  title="Avisar novedad de este cliente al revendedor"
                >
                  <span>📢 Avisar Revendedor</span>
                </button>
                <button 
                  @click="handleCancelStreamingOrder(order)" 
                  class="bg-rose-950/40 hover:bg-rose-900 border border-rose-500/30 text-rose-300 text-[10px] font-bold px-2 py-1 rounded-lg transition cursor-pointer"
                >
                  ✕ Cancelar
                </button>
              </template>

              <!-- Acciones para Pedidos Despachados -->
              <template v-else-if="order.status === 'Dispatched'">
                <button 
                  @click="copyOrderCredentials(order)" 
                  class="bg-sky-500/20 hover:bg-sky-500/30 border border-sky-500/40 text-sky-300 text-xs font-bold px-3 py-1.5 rounded-xl transition flex items-center justify-center gap-1 cursor-pointer"
                  title="Copiar correo, contraseña y perfiles"
                >
                  <span>📋 Copiar Accesos</span>
                </button>
                <button 
                  @click="resendOrderCredentialsWhatsApp(order)" 
                  class="bg-emerald-600 hover:bg-emerald-500 text-white px-3 py-1.5 font-bold text-xs rounded-xl transition flex items-center justify-center gap-1 cursor-pointer"
                  title="Re-enviar credenciales por WhatsApp"
                >
                  <span>💬 Enviar WhatsApp</span>
                </button>
                <button 
                  @click="handleDeleteStreamingOrder(order)" 
                  class="text-slate-500 hover:text-rose-400 text-[10px] py-1 transition cursor-pointer"
                >
                  🗑️ Eliminar
                </button>
              </template>

              <!-- Acciones para Pedidos Cancelados -->
              <template v-else>
                <button 
                  @click="contactDirectCustomerWhatsApp(order)" 
                  class="bg-slate-800 hover:bg-slate-700 text-slate-300 text-[11px] font-bold px-3 py-1.5 rounded-xl transition cursor-pointer"
                >
                  💬 WhatsApp
                </button>
                <button 
                  @click="handleDeleteStreamingOrder(order)" 
                  class="text-slate-500 hover:text-rose-400 text-[10px] py-1 transition cursor-pointer"
                >
                  🗑️ Eliminar
                </button>
              </template>
            </div>
          </div>

          <!-- DETALLES ADICIONALES DE LA ORDEN -->
          <!-- Si es Despachado: Muestra las credenciales asignadas -->
          <div v-if="order.status === 'Dispatched' && order.accountDetails" class="bg-slate-950/90 p-3.5 rounded-xl border border-emerald-500/30 space-y-2 text-xs">
            <div class="flex items-center justify-between text-emerald-400 font-bold">
              <span>✓ Credenciales Entregadas al Cliente:</span>
              <span class="text-[10px] text-slate-400 font-mono">{{ order.accountDetails.dispatchedAt }}</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 font-mono text-[11px]">
              <div class="bg-slate-900 p-2 rounded-lg border border-slate-800">
                <span class="text-slate-500 block text-[9px] uppercase">Cuenta:</span>
                <span class="text-white font-bold truncate block">{{ order.accountDetails.email }}</span>
              </div>
              <div class="bg-slate-900 p-2 rounded-lg border border-slate-800">
                <span class="text-slate-500 block text-[9px] uppercase">Contraseña:</span>
                <span class="text-amber-300 font-bold truncate block">{{ order.accountDetails.pass }}</span>
              </div>
            </div>
            <div v-if="order.accountDetails.profiles && order.accountDetails.profiles.length > 0" class="flex flex-wrap gap-1.5 pt-1">
              <span v-for="(p, pIdx) in order.accountDetails.profiles" :key="pIdx" class="bg-slate-900 text-sky-300 text-[10px] px-2 py-0.5 rounded border border-slate-800 font-mono">
                👤 {{ p.name }} (PIN: {{ p.pin }})
              </span>
            </div>
          </div>

          <!-- Si es Pendiente: Muestra los perfiles requeridos -->
          <div v-else-if="order.status === 'Waiting Provider' || order.status === 'Pendiente'" class="bg-slate-900/90 p-3 rounded-xl border border-slate-800">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">Nombres requeridos:</p>
            <div class="flex flex-wrap gap-2">
              <div v-for="(p, pIdx) in order.profileSelections" :key="pIdx" class="bg-slate-950 px-2.5 py-1 rounded text-xs border border-slate-800 flex items-center gap-1.5">
                <span class="text-brand-accent font-mono font-semibold">👤 {{ p.name }}</span>
                <span class="text-slate-500 text-[10px]">PIN por asignar</span>
              </div>
            </div>
          </div>

          <!-- Si es Cancelado: Muestra el motivo -->
          <div v-else-if="order.status === 'Cancelado'" class="bg-rose-950/20 p-3 rounded-xl border border-rose-500/20 text-xs text-rose-300 space-y-1">
            <strong>Motivo de Cancelación:</strong> {{ order.cancelReason || 'Sin motivo especificado' }}
            <p v-if="order.cancelledAt" class="text-[10px] text-slate-500 font-mono">Fecha: {{ order.cancelledAt }}</p>
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

          <!-- Filtro de Solicitudes y Botón de Recarga -->
          <div class="flex flex-wrap items-center gap-2">
            <button 
              type="button" 
              @click="adminStore.syncFromCloud()" 
              class="bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-white border border-slate-700 px-3 py-1.5 rounded-xl font-bold text-xs flex items-center gap-1.5 cursor-pointer shadow transition-all"
              title="Sincronizar solicitudes desde la nube Firebase"
            >
              <span>🔄</span>
              <span>Sincronizar</span>
            </button>
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
                    @click="openResellerPanelDirect(adminStore.resellers.find(r => r.id === app.createdResellerId) || adminStore.ensureResellerFromApplication(app))"
                    class="bg-indigo-600 hover:bg-indigo-500 text-white text-[11px] px-2.5 py-1 rounded-lg font-bold flex items-center gap-1 cursor-pointer shadow"
                    title="Abrir e ingresar directamente al panel de este revendedor"
                  >
                    <span>🚀 Abrir</span>
                  </button>
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
              @click="openResellerPanelDirect(res)" 
              class="bg-indigo-600 hover:bg-indigo-500 text-white font-black text-xs px-3.5 py-2 rounded-xl transition-all shadow flex items-center gap-1.5 cursor-pointer"
              title="Abrir el panel de este revendedor e iniciar sesión directamente"
            >
              <span>🚀 Abrir Panel</span>
            </button>
            <button 
              @click="copyResellerCredentials(res)" 
              class="bg-emerald-600/20 hover:bg-emerald-600 text-emerald-300 hover:text-white border border-emerald-500/30 text-xs font-bold px-3 py-2 rounded-xl transition-all flex items-center gap-1.5"
              title="Copiar credenciales oficiales y enlace para enviar por WhatsApp"
            >
              <span>📲 Copiar para WhatsApp</span>
            </button>
            <button 
              @click="copyResellerStoreLink(res)" 
              class="bg-amber-500/20 hover:bg-amber-500 text-amber-300 hover:text-slate-950 border border-amber-500/30 text-xs font-bold px-3 py-2 rounded-xl transition-all flex items-center gap-1.5"
              title="Copiar enlace de tienda con referido de este distribuidor"
            >
              <span>🔗 Link Tienda</span>
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
    
    <!-- SECCIÓN: DIRECTORIO INTEGRAL DE USUARIOS REGISTRADOS (CLIENTES & DISTRIBUIDORES) -->
    <div v-if="adminTab === 'users'" class="bg-slate-900/40 rounded-3xl border border-slate-800 p-6 sm:p-8 backdrop-blur-md space-y-6">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pb-4 border-b border-slate-800">
        <div>
          <div class="flex items-center gap-2">
            <h2 class="text-2xl font-black text-white">Directorio de Usuarios Registrados</h2>
            <span class="text-xs font-mono font-bold bg-sky-500/20 text-sky-300 px-2.5 py-0.5 rounded-full border border-sky-500/30">
              {{ adminStore.registeredUsers.length }} Usuarios
            </span>
          </div>
          <p class="text-xs text-slate-400 mt-1">
            Gestión de perfiles de clientes y revendedores. Facilidad de acceso para compras y control de accesos.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
          <button 
            @click="showAddUserModal = true" 
            class="flex-1 sm:flex-none bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-400 hover:to-blue-500 text-white font-bold text-xs px-4 py-2.5 rounded-xl shadow-lg transition-all flex items-center justify-center gap-1.5"
          >
            <span>+</span>
            <span>Registrar Usuario</span>
          </button>
          <button 
            @click="exportRegisteredUsers" 
            class="flex-1 sm:flex-none bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs px-3.5 py-2.5 rounded-xl border border-slate-700 transition-all flex items-center justify-center gap-1.5"
            title="Exportar base de datos de usuarios en formato JSON"
          >
            <span>📥 Exportar</span>
          </button>
        </div>
      </div>

      <!-- Métricas de Usuarios -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-slate-950/60 p-4 rounded-2xl border border-slate-800 space-y-1">
          <span class="text-xs text-slate-400 uppercase font-bold tracking-wider">Total Registrados</span>
          <div class="text-2xl font-black text-white">{{ adminStore.registeredUsers.length }}</div>
        </div>
        <div class="bg-slate-950/60 p-4 rounded-2xl border border-slate-800 space-y-1">
          <span class="text-xs text-slate-400 uppercase font-bold tracking-wider">Clientes</span>
          <div class="text-2xl font-black text-sky-400">{{ adminStore.registeredUsers.filter(u => u.role === 'client').length }}</div>
        </div>
        <div class="bg-slate-950/60 p-4 rounded-2xl border border-slate-800 space-y-1">
          <span class="text-xs text-slate-400 uppercase font-bold tracking-wider">Distribuidores</span>
          <div class="text-2xl font-black text-amber-400">{{ adminStore.registeredUsers.filter(u => u.role === 'reseller').length }}</div>
        </div>
        <div class="bg-slate-950/60 p-4 rounded-2xl border border-slate-800 space-y-1">
          <span class="text-xs text-slate-400 uppercase font-bold tracking-wider">Facturado Total</span>
          <div class="text-2xl font-black text-emerald-400">
            ${{ adminStore.registeredUsers.reduce((s, u) => s + (u.totalSpent || 0), 0).toLocaleString() }}
          </div>
        </div>
      </div>

      <!-- Filtros y Búsqueda -->
      <div class="flex flex-col sm:flex-row gap-3">
        <div class="relative flex-1">
          <input 
            v-model="userSearch" 
            type="text" 
            placeholder="Buscar por nombre, teléfono, email o N° cliente..." 
            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white placeholder:text-slate-500 focus:outline-none focus:border-sky-500"
          >
        </div>
        <div class="flex gap-2">
          <button 
            @click="userRoleFilter = 'all'" 
            :class="['px-3 py-2 rounded-xl text-xs font-bold transition-colors', userRoleFilter === 'all' ? 'bg-sky-500 text-slate-950 font-black' : 'bg-slate-950 text-slate-400 border border-slate-800 hover:text-white']"
          >
            Todos
          </button>
          <button 
            @click="userRoleFilter = 'client'" 
            :class="['px-3 py-2 rounded-xl text-xs font-bold transition-colors', userRoleFilter === 'client' ? 'bg-sky-500 text-slate-950 font-black' : 'bg-slate-950 text-slate-400 border border-slate-800 hover:text-white']"
          >
            Solo Clientes
          </button>
          <button 
            @click="userRoleFilter = 'reseller'" 
            :class="['px-3 py-2 rounded-xl text-xs font-bold transition-colors', userRoleFilter === 'reseller' ? 'bg-amber-400 text-slate-950 font-black' : 'bg-slate-950 text-slate-400 border border-slate-800 hover:text-white']"
          >
            Solo Revendedores
          </button>
        </div>
      </div>

      <!-- Tabla de Usuarios Registrados -->
      <div class="overflow-x-auto rounded-2xl border border-slate-800 bg-slate-950/60">
        <table class="w-full text-left border-collapse text-xs">
          <thead>
            <tr class="border-b border-slate-800 bg-slate-900/80 text-slate-400 uppercase tracking-wider text-[11px]">
              <th class="p-3.5">Usuario / N° Cliente</th>
              <th class="p-3.5">Contacto (WhatsApp / Email)</th>
              <th class="p-3.5">Rol</th>
              <th class="p-3.5">Estado</th>
              <th class="p-3.5">Pedidos / Total</th>
              <th class="p-3.5">PIN Bóveda</th>
              <th class="p-3.5 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800/80">
            <tr v-if="filteredRegisteredUsers.length === 0">
              <td colspan="7" class="p-8 text-center text-slate-500">
                No se encontraron usuarios registrados con los filtros aplicados.
              </td>
            </tr>
            <tr v-for="user in filteredRegisteredUsers" :key="user.id" class="hover:bg-slate-900/40 transition-colors">
              <td class="p-3.5">
                <div class="font-bold text-white text-sm">{{ user.name }}</div>
                <div class="text-[11px] font-mono text-slate-400">
                  <span class="bg-slate-900 px-1.5 py-0.5 rounded border border-slate-800">{{ user.clientNumber || user.id }}</span>
                  <span v-if="user.registeredAt" class="ml-1 text-slate-500">Reg: {{ user.registeredAt }}</span>
                </div>
              </td>
              <td class="p-3.5 font-mono">
                <div class="text-sky-400 font-bold flex items-center gap-1.5">
                  <span>{{ user.phone || 'Sin WhatsApp' }}</span>
                  <a 
                    v-if="user.phone" 
                    :href="`https://wa.me/${user.phone.replace(/[^0-9]/g, '')}`" 
                    target="_blank" 
                    class="bg-emerald-600/20 text-emerald-400 hover:bg-emerald-600 hover:text-white px-1.5 py-0.5 rounded text-[10px] transition-colors"
                  >
                    💬 Chat
                  </a>
                </div>
                <div v-if="user.email" class="text-slate-400 text-[11px]">{{ user.email }}</div>
                <div v-if="user.address" class="text-slate-500 text-[10px] truncate max-w-xs">📍 {{ user.address }}</div>
              </td>
              <td class="p-3.5">
                <span 
                  class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                  :class="user.role === 'reseller' ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30' : user.role === 'provider' ? 'bg-purple-500/20 text-purple-300 border border-purple-500/30' : 'bg-sky-500/20 text-sky-300 border border-sky-500/30'"
                >
                  {{ user.role === 'reseller' ? 'Distribuidor' : user.role === 'provider' ? 'Proveedor' : 'Cliente' }}
                </span>
              </td>
              <td class="p-3.5">
                <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-400">
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                  {{ user.status || 'Activo' }}
                </span>
              </td>
              <td class="p-3.5 font-mono">
                <span class="text-white font-bold">{{ user.ordersCount || 0 }} pedidos</span>
                <div class="text-emerald-400 font-bold text-[11px]">${{ (user.totalSpent || 0).toLocaleString() }} ARS</div>
              </td>
              <td class="p-3.5 font-mono">
                <span class="bg-amber-500/10 text-amber-300 px-2 py-0.5 rounded border border-amber-500/20 font-black tracking-wider">
                  {{ user.pin || '1234' }}
                </span>
              </td>
              <td class="p-3.5 text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <button 
                    v-if="user.role === 'client'" 
                    @click="handleChangeUserRole(user, 'reseller')" 
                    class="bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 border border-amber-500/30 px-2.5 py-1 rounded-lg text-[10px] font-bold transition-colors"
                    title="Convertir a Revendedor Oficial"
                  >
                    ⭐ Hacer Revendedor
                  </button>
                  <button 
                    v-else-if="user.role === 'reseller'" 
                    @click="handleChangeUserRole(user, 'client')" 
                    class="bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 px-2 py-1 rounded-lg text-[10px] transition-colors"
                    title="Pasar a Cliente Directo"
                  >
                    Pasar a Cliente
                  </button>
                  <button 
                    @click="handleDeleteUser(user)" 
                    class="text-red-400 hover:text-red-300 hover:bg-red-950/40 p-1.5 rounded-lg transition-colors"
                    title="Eliminar usuario del directorio"
                  >
                    🗑️
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

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
          <button 
            @click="bankSubTab = 'cloud_notifications'"
            :class="['px-3.5 py-1.5 rounded-lg transition-all flex items-center gap-1.5 cursor-pointer', bankSubTab === 'cloud_notifications' ? 'bg-sky-400 text-slate-950 font-black' : 'text-slate-400 hover:text-white']"
          >
            <span>☁️ Alertas Telegram & Firebase</span>
            <span v-if="adminStore.telegramConfig?.botToken || adminStore.firebaseConfig?.projectId" class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
          </button>
          <button 
            @click="bankSubTab = 'mercadopago'"
            :class="['px-3.5 py-1.5 rounded-lg transition-all flex items-center gap-1.5 cursor-pointer', bankSubTab === 'mercadopago' ? 'bg-sky-400 text-slate-950 font-black' : 'text-slate-400 hover:text-white']"
          >
            <span>💳 Mercado Pago</span>
            <span v-if="(mercadoPagoForm.accessToken || '').trim()" class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
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

      <!-- SUB-TAB 4: ALERTAS TELEGRAM BOT & SINCRONIZACIÓN FIREBASE CLOUD -->
      <div v-if="bankSubTab === 'cloud_notifications'" class="space-y-8">
        
        <!-- SECCIÓN 1: BOT DE TELEGRAM (ALERTAS A TU CELULAR) -->
        <div class="bg-slate-950 p-6 rounded-2xl border border-slate-800 space-y-5">
          <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 border-b border-slate-800 pb-4">
            <div>
              <div class="flex items-center gap-2">
                <span class="text-xl">🤖</span>
                <h3 class="text-lg font-black text-white">Bot de Notificaciones de Telegram</h3>
                <span :class="['text-[10px] font-bold px-2 py-0.5 rounded-full', adminStore.telegramConfig?.botToken && adminStore.telegramConfig?.chatId ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-slate-800 text-slate-400']">
                  {{ adminStore.telegramConfig?.botToken && adminStore.telegramConfig?.chatId ? '🟢 Conectado y Activo' : '⚪ Pendiente de Configurar' }}
                </span>
              </div>
              <p class="text-xs text-slate-400 mt-1">
                Tu teléfono sonará al segundo exacto en que un cliente presione "Confirmar Pedido", con todo el desglose y voucher.
              </p>
            </div>
            <div class="flex items-center gap-2">
              <button 
                @click="testTelegramBot" 
                :disabled="isTestingTelegram"
                class="bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs px-4 py-2.5 rounded-xl transition-all shadow flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
              >
                <span>{{ isTestingTelegram ? '⏳ Enviando...' : '📲 Probar Notificación' }}</span>
              </button>
              <button 
                @click="saveTelegramSettings" 
                class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs px-4 py-2.5 rounded-xl transition-all shadow cursor-pointer"
              >
                Guardar
              </button>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-1">
              <label class="block text-xs font-bold uppercase text-slate-300">Bot Token de Telegram *</label>
              <input 
                v-model="telegramForm.botToken" 
                type="text" 
                placeholder="Ej: 7123456789:AAHk..." 
                class="w-full bg-slate-900 border border-slate-800 rounded-xl p-3 text-xs text-white font-mono outline-none focus:border-sky-400"
              />
              <span class="text-[10px] text-slate-500">Obtenido de @BotFather al crear el bot</span>
            </div>
            <div class="space-y-1">
              <label class="block text-xs font-bold uppercase text-slate-300">Tu Chat ID (ID de Telegram) *</label>
              <input 
                v-model="telegramForm.chatId" 
                type="text" 
                placeholder="Ej: 123456789" 
                class="w-full bg-slate-900 border border-slate-800 rounded-xl p-3 text-xs text-white font-mono outline-none focus:border-sky-400"
              />
              <span class="text-[10px] text-slate-500">Tu número identificador obtenido de @userinfobot</span>
            </div>
          </div>

          <!-- Guía rápida de 4 pasos para Telegram -->
          <div class="bg-slate-900/40 p-4 rounded-xl border border-slate-800 text-xs space-y-2">
            <span class="font-bold text-sky-400 block">📖 Pasos rápidos para obtener tu Bot en 2 minutos:</span>
            <ol class="list-decimal list-inside space-y-1.5 text-slate-300 text-[11px] leading-relaxed">
              <li>Abre Telegram y busca al usuario oficial <strong>@BotFather</strong>.</li>
              <li>Envía el mensaje <code class="text-amber-300 font-mono">/newbot</code>, dale un nombre (ej: <em>StreamePro Avisos</em>) y un usuario terminado en bot (ej: <em>streamepro_pedidos_bot</em>). Te devolverá el <strong>Token</strong>.</li>
              <li>Busca en Telegram a <strong>@userinfobot</strong> y pulsa Iniciar para ver tu <strong>Id</strong> numérico (Chat ID).</li>
              <li>Abre tu nuevo bot en Telegram, presiona <strong>/start</strong>, pega el Token y tu Chat ID arriba y pulsa <strong>Probar Notificación</strong>.</li>
            </ol>
          </div>
        </div>

        <!-- SECCIÓN 2: BASE DE DATOS EN LA NUBE FIREBASE FIRESTORE -->
        <div class="bg-slate-950 p-6 rounded-2xl border border-slate-800 space-y-5">
          <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 border-b border-slate-800 pb-4">
            <div>
              <div class="flex items-center gap-2">
                <span class="text-xl">🔥</span>
                <h3 class="text-lg font-black text-white">Google Firebase Firestore (Nube Central)</h3>
                <span :class="['text-[10px] font-bold px-2 py-0.5 rounded-full', adminStore.firebaseConfig?.projectId ? 'bg-amber-500/20 text-amber-400 border border-amber-500/30' : 'bg-slate-800 text-slate-400']">
                  {{ adminStore.firebaseConfig?.projectId ? '🟢 Sincronización en Vivo' : '⚪ Sin Conectar' }}
                </span>
              </div>
              <p class="text-xs text-slate-400 mt-1">
                Conecta la base de datos de Firebase para que todos los pedidos de la web aparezcan en tiempo real en tu panel desde cualquier celular o PC.
              </p>
            </div>
            <div class="flex items-center gap-2">
              <button 
                @click="testFirebaseConnectionHandler" 
                :disabled="isTestingFirebase"
                class="bg-amber-600 hover:bg-amber-500 text-white font-bold text-xs px-4 py-2.5 rounded-xl transition-all shadow flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
              >
                <span>{{ isTestingFirebase ? '⏳ Probando...' : '🔥 Probar Conexión' }}</span>
              </button>
              <button 
                @click="saveFirebaseSettings" 
                class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs px-4 py-2.5 rounded-xl transition-all shadow cursor-pointer"
              >
                Guardar y Conectar
              </button>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
            <div class="space-y-1">
              <label class="block font-bold uppercase text-slate-300 text-[11px]">API Key *</label>
              <input v-model="firebaseForm.apiKey" type="text" placeholder="AIzaSy..." class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-xs text-white font-mono outline-none focus:border-amber-400" />
            </div>
            <div class="space-y-1">
              <label class="block font-bold uppercase text-slate-300 text-[11px]">Project ID *</label>
              <input v-model="firebaseForm.projectId" type="text" placeholder="streamepro-prod" class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-xs text-white font-mono outline-none focus:border-amber-400" />
            </div>
            <div class="space-y-1">
              <label class="block font-bold uppercase text-slate-300 text-[11px]">Auth Domain</label>
              <input v-model="firebaseForm.authDomain" type="text" placeholder="streamepro-prod.firebaseapp.com" class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-xs text-white font-mono outline-none focus:border-amber-400" />
            </div>
            <div class="space-y-1">
              <label class="block font-bold uppercase text-slate-300 text-[11px]">Storage Bucket</label>
              <input v-model="firebaseForm.storageBucket" type="text" placeholder="streamepro-prod.appspot.com" class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-xs text-white font-mono outline-none focus:border-amber-400" />
            </div>
            <div class="space-y-1">
              <label class="block font-bold uppercase text-slate-300 text-[11px]">Messaging Sender ID</label>
              <input v-model="firebaseForm.messagingSenderId" type="text" placeholder="1234567890" class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-xs text-white font-mono outline-none focus:border-amber-400" />
            </div>
            <div class="space-y-1">
              <label class="block font-bold uppercase text-slate-300 text-[11px]">App ID</label>
              <input v-model="firebaseForm.appId" type="text" placeholder="1:123456:web:abcd" class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-xs text-white font-mono outline-none focus:border-amber-400" />
            </div>
          </div>

          <!-- Guía rápida de configuración Firebase -->
          <div class="bg-slate-900/40 p-4 rounded-xl border border-slate-800 text-xs space-y-2">
            <span class="font-bold text-amber-400 block">📖 Pasos para crear tu proyecto gratuito en Google Firebase:</span>
            <ol class="list-decimal list-inside space-y-1.5 text-slate-300 text-[11px] leading-relaxed">
              <li>Ingresa a <a href="https://console.firebase.google.com" target="_blank" class="text-brand-accent underline">console.firebase.google.com</a> con tu cuenta de Google y haz clic en <strong>"Crear un proyecto"</strong>.</li>
              <li>En el menú izquierdo, haz clic en <strong>"Compilación" -> "Firestore Database"</strong> y pulsa <strong>"Crear base de datos"</strong> (puedes elegir comenzar en Modo de Prueba).</li>
              <li>Ve a la rueda de engranaje ⚙️ (Configuración del proyecto) -> pestaña <strong>"General"</strong> -> baja hasta "Tus apps" y haz clic en el ícono Web <code class="text-amber-300 font-mono">&lt;/&gt;</code>.</li>
              <li>Copia los valores de <code class="text-amber-300 font-mono">firebaseConfig</code>, pégalos arriba y haz clic en <strong>"Guardar y Conectar"</strong>. ¡Todo quedará sincronizado!</li>
            </ol>
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

      <!-- SUB-TAB 5: MERCADO PAGO CHECKOUT PRO -->
      <div v-if="bankSubTab === 'mercadopago'" class="space-y-6">
        <div class="bg-slate-950 p-6 rounded-2xl border border-slate-800 space-y-5">
          <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 border-b border-slate-800 pb-4">
            <div>
              <div class="flex items-center gap-2">
                <span class="text-xl">💳</span>
                <h3 class="text-lg font-black text-white">Mercado Pago Checkout Pro (Pasarela Oficial)</h3>
                <span :class="['text-[10px] font-bold px-2 py-0.5 rounded-full', (mercadoPagoForm.accessToken || '').trim() ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-slate-800 text-slate-400']">
                  {{ (mercadoPagoForm.accessToken || '').trim() ? '🟢 Conectado y Listo para Cobrar' : '⚪ Pendiente de Token' }}
                </span>
              </div>
              <p class="text-xs text-slate-400 mt-1">
                Tus clientes podrán pagar al instante con Saldo de Mercado Pago, Tarjetas de Débito, Crédito en cuotas o Dinero en cuenta. Las órdenes se aprueban automáticamente mediante Webhook sin necesidad de subir comprobante manual.
              </p>
            </div>
            <div class="flex items-center gap-2">
              <button 
                type="button"
                @click="testMercadoPagoConnection" 
                :disabled="isTestingMercadoPago"
                class="bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs px-4 py-2.5 rounded-xl transition-all shadow flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
              >
                <span>{{ isTestingMercadoPago ? '⏳ Verificando...' : '⚡ Probar Conexión' }}</span>
              </button>
              <button 
                type="button"
                @click="saveMercadoPagoSettings" 
                class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs px-4 py-2.5 rounded-xl transition-all shadow cursor-pointer"
              >
                Guardar Credenciales
              </button>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-1">
              <div class="flex justify-between items-center">
                <label class="block text-xs font-bold uppercase text-slate-300">Mercado Pago Access Token *</label>
                <button 
                  type="button" 
                  @click="showMpToken = !showMpToken" 
                  class="text-[10px] text-sky-400 hover:underline cursor-pointer"
                >
                  {{ showMpToken ? 'Ocultar' : 'Mostrar' }}
                </button>
              </div>
              <input 
                v-model="mercadoPagoForm.accessToken" 
                :type="showMpToken ? 'text' : 'password'"
                placeholder="APP_USR-0000000000000000-000000-..." 
                class="w-full bg-slate-900 border border-slate-800 rounded-xl p-3 text-xs text-white font-mono outline-none focus:border-sky-400"
              />
              <span class="text-[10px] text-slate-500">Credencial de producción o pruebas obtenida del panel Developers de Mercado Pago.</span>
            </div>

            <div class="space-y-1">
              <label class="block text-xs font-bold uppercase text-slate-300">Mercado Pago Public Key (Opcional)</label>
              <input 
                v-model="mercadoPagoForm.publicKey" 
                type="text" 
                placeholder="APP_USR-00000000-0000-0000-..." 
                class="w-full bg-slate-900 border border-slate-800 rounded-xl p-3 text-xs text-white font-mono outline-none focus:border-sky-400"
              />
              <span class="text-[10px] text-slate-500">Clave pública para integraciones en el navegador o Bricks.</span>
            </div>
          </div>

          <!-- Switch para activar o pausar Mercado Pago -->
          <div class="p-3.5 bg-slate-900/60 rounded-xl border border-slate-800 flex items-center justify-between">
            <div>
              <span class="text-xs font-bold text-white block">Estado de la Pasarela en Tienda</span>
              <p class="text-[11px] text-slate-400">Si está habilitado, los clientes verán el botón de Mercado Pago en la pasarela de compra.</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" v-model="mercadoPagoForm.enabled" class="sr-only peer">
              <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
            </label>
          </div>

          <!-- Webhook y retorno oficial -->
          <div class="p-4 bg-slate-900/40 rounded-xl border border-slate-800 text-xs space-y-2">
            <span class="font-bold text-sky-400 block">⚡ Webhook de Aprobación Inmediata:</span>
            <p class="text-slate-300 text-[11px]">
              La URL de notificaciones automáticas ya está vinculada en las preferencias creadas:
            </p>
            <div class="bg-slate-950 p-2.5 rounded-lg border border-slate-800 flex items-center justify-between text-[11px] font-mono text-emerald-400">
              <span>https://streamepro.me/api/mp-webhook</span>
              <span class="text-slate-500 text-[10px]">Auto-verificación & Alerta a Telegram</span>
            </div>
          </div>

          <!-- Guía rápida de obtención de credenciales -->
          <div class="bg-slate-900/40 p-4 rounded-xl border border-slate-800 text-xs space-y-2">
            <span class="font-bold text-sky-400 block">📖 Cómo obtener tu Access Token de Mercado Pago en 1 minuto:</span>
            <ol class="list-decimal list-inside space-y-1.5 text-slate-300 text-[11px] leading-relaxed">
              <li>Inicia sesión en tu cuenta de Mercado Pago y abre <a href="https://www.mercadopago.com.ar/developers/panel" target="_blank" class="text-brand-accent underline">mercadopago.com.ar/developers/panel</a>.</li>
              <li>En el menú lateral haz clic en <strong>"Tus integraciones"</strong> y selecciona tu aplicación (o haz clic en <em>"Crear aplicación"</em> si aún no tienes una, seleccionando <em>Checkout Pro</em>).</li>
              <li>Ve a la pestaña <strong>"Credenciales de producción"</strong> (o "Credenciales de prueba" si deseas testear primero).</li>
              <li>Copia el <strong>Access token</strong> (comienza con <code class="text-amber-300 font-mono">APP_USR-...</code> o <code class="text-amber-300 font-mono">TEST-...</code>) y pégalo arriba. ¡Haz clic en "Guardar Credenciales" y tu tienda empezará a cobrar al instante!</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <!-- SECCIÓN: GESTIÓN DE TIENDA Y CATÁLOGO DE PRODUCTOS FÍSICOS -->
    <div v-if="adminTab === 'physical'" class="bg-slate-900/40 rounded-3xl border border-slate-800 p-8 backdrop-blur-md space-y-6">
      
      <!-- ENCABEZADO PRINCIPAL CON ACCESO DIRECTO A TIENDA PÚBLICA -->
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 border-b border-slate-800 pb-4">
        <div>
          <div class="flex flex-wrap items-center gap-2">
            <h2 class="text-2xl font-black text-white">Tienda & Catálogo de Productos Físicos</h2>
            <span class="text-xs font-mono font-bold bg-amber-400/20 text-amber-300 border border-amber-400/30 px-2.5 py-0.5 rounded-full">
              {{ adminStore.physicalProducts.length }} Productos en Catálogo
            </span>
            <span v-if="adminStore.pendingPhysicalOrders.length > 0" class="text-xs font-mono font-bold bg-red-500 text-white px-2.5 py-0.5 rounded-full animate-pulse">
              {{ adminStore.pendingPhysicalOrders.length }} Pedidos Pendientes
            </span>
          </div>
          <p class="text-xs text-slate-400 mt-1">
            Visualiza el catálogo de productos físicos sincronizados, gestiona pedidos recibidos de clientes y revendedores.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <!-- BOTÓN ACTIVAR O DESACTIVAR TIENDA FÍSICA EN NAVEGACIÓN -->
          <button 
            type="button"
            @click="togglePhysicalStoreVisibility"
            :class="[
              'text-xs font-black px-4 py-2 rounded-xl shadow transition flex items-center gap-1.5 cursor-pointer border',
              adminStore.isPhysicalStoreVisible 
                ? 'bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 border-emerald-500/40' 
                : 'bg-red-500/25 hover:bg-red-500/35 text-red-300 border-red-500/50 ring-2 ring-red-500/30 animate-pulse'
            ]"
            :title="adminStore.isPhysicalStoreVisible ? 'La tienda física está activa y visible en el catálogo y menú. Clic para desactivarla.' : 'La tienda física está desactivada y oculta del catálogo y menú. Clic para activarla.'"
          >
            <span>{{ adminStore.isPhysicalStoreVisible ? '🟢 Tienda Física en Catálogo: ACTIVA' : '🔴 Tienda Física en Catálogo: DESACTIVADA' }}</span>
          </button>
          <button 
            type="button"
            @click="openBulkPercentageModal"
            class="bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-slate-950 font-black text-xs px-3.5 py-2 rounded-xl shadow transition flex items-center gap-1.5 cursor-pointer"
            title="Aplicar porcentaje (%) de oferta, descuento o aumento masivo a todos los productos"
          >
            <span>🏷️ % Masivo (Todos)</span>
          </button>
          <button 
            type="button"
            @click="handleSyncFromRevendeApi" 
            :disabled="isSyncingRevende"
            class="bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs px-3.5 py-2 rounded-xl shadow transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
            title="Sincronizar directamente con la API en vivo de revende.com.ar"
          >
            <span>{{ isSyncingRevende ? '⏳ Sincronizando...' : '🔄 Sincronizar revende.com.ar' }}</span>
          </button>
          <input 
            type="file" 
            ref="catalogFileInput" 
            accept=".json" 
            @change="handleImportPhysicalCatalog" 
            class="hidden"
          >
          <button 
            type="button"
            @click="triggerPhysicalCatalogUpload" 
            class="bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-xs px-3.5 py-2 rounded-xl shadow transition flex items-center gap-1.5 cursor-pointer"
            title="Importar products.json generado en C:\Users\yo\catalogo\products.json"
          >
            <span>📥 Importar JSON</span>
          </button>
          <a 
            href="/p" 
            target="_blank" 
            class="bg-sky-500/20 hover:bg-sky-500/30 text-sky-300 border border-sky-500/40 text-xs font-bold px-3.5 py-2 rounded-xl shadow transition flex items-center gap-1.5 cursor-pointer"
            title="Abrir la tienda pública en vivo en streamepro.me/p"
          >
            <span>🌐 Ver Tienda Pública ↗</span>
          </a>
        </div>
      </div>

      <!-- NAVEGACIÓN ENTRE SUB-PESTAÑAS: CATÁLOGO VS PEDIDOS VS PÁGINAS (SOLO ADMIN) -->
      <div class="flex flex-wrap items-center justify-between gap-3 bg-slate-950 p-2.5 rounded-2xl border border-slate-800">
        <div class="flex flex-wrap gap-2">
          <button 
            type="button"
            @click="adminPhysicalSubTab = 'products'"
            :class="[
              'px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 cursor-pointer shadow',
              adminPhysicalSubTab === 'products' ? 'bg-amber-400 text-slate-950 font-black' : 'text-slate-400 hover:text-white hover:bg-slate-900'
            ]"
          >
            <span>📦</span>
            <span>Catálogo de Productos ({{ publicPhysicalProductsCount }})</span>
          </button>
          <button 
            type="button"
            @click="adminPhysicalSubTab = 'orders'"
            :class="[
              'px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 cursor-pointer shadow',
              adminPhysicalSubTab === 'orders' ? 'bg-amber-400 text-slate-950 font-black' : 'text-slate-400 hover:text-white hover:bg-slate-900'
            ]"
          >
            <span>🚚</span>
            <span>Gestión de Pedidos Físicos ({{ adminStore.physicalOrders.length }})</span>
            <span v-if="adminStore.pendingPhysicalOrders.length > 0" class="bg-red-500 text-white font-mono text-[9px] px-1.5 py-0.2 rounded-full font-black">
              {{ adminStore.pendingPhysicalOrders.length }}
            </span>
          </button>
          <button 
            type="button"
            @click="adminPhysicalSubTab = 'pages'"
            :class="[
              'px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 cursor-pointer shadow border',
              adminPhysicalSubTab === 'pages' ? 'bg-sky-500 text-slate-950 font-black border-sky-400' : 'text-sky-400 border-sky-500/30 hover:bg-sky-500/10'
            ]"
          >
            <span>📄</span>
            <span>Páginas (Solo Admin) 🔒 ({{ adminPagesList.length }})</span>
          </button>
        </div>

        <div class="flex items-center gap-2">
          <button 
            v-if="adminPhysicalSubTab === 'products'"
            type="button"
            @click="openBulkPercentageModal"
            class="bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 border border-emerald-500/40 font-black text-xs px-3.5 py-2 rounded-xl shadow transition flex items-center gap-1.5 cursor-pointer"
            title="Ajuste masivo de porcentaje para todos los productos"
          >
            <span>🏷️ Opción de % (Todos)</span>
          </button>
          <button 
            v-if="adminPhysicalSubTab === 'products'"
            type="button"
            @click="openCreatePhysicalProductModal(false)"
            class="bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 text-slate-950 font-black text-xs px-3.5 py-2 rounded-xl shadow transition flex items-center gap-1.5 cursor-pointer"
          >
            <span>➕ Nuevo Producto Físico</span>
          </button>
          <button 
            v-if="adminPhysicalSubTab === 'pages'"
            type="button"
            @click="openCreatePhysicalProductModal(true)"
            class="bg-gradient-to-r from-sky-400 to-sky-500 hover:from-sky-300 hover:to-sky-400 text-slate-950 font-black text-xs px-3.5 py-2 rounded-xl shadow transition flex items-center gap-1.5 cursor-pointer"
          >
            <span>➕ Nueva Página (Solo Admin)</span>
          </button>
        </div>
      </div>

      <!-- VISTA 1: CATÁLOGO COMPLETO DE PRODUCTOS FÍSICOS -->
      <div v-if="adminPhysicalSubTab === 'products'" class="space-y-4">
        <!-- Buscador y Filtro de Categorías de Productos -->
        <div class="flex flex-col sm:flex-row gap-3 bg-slate-950 p-4 rounded-2xl border border-slate-800">
          <div class="flex-1 relative">
            <span class="absolute left-3 top-2.5 text-slate-500 text-xs">🔍</span>
            <input 
              v-model="adminPhysicalProductSearch" 
              type="text" 
              placeholder="Buscar producto físico por nombre, ID o descripción..."
              class="w-full bg-slate-900 border border-slate-800 rounded-xl pl-9 pr-3 py-2 text-xs text-white outline-none focus:border-amber-400"
            />
          </div>
          <select 
            v-model="adminPhysicalCategoryFilter"
            class="bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white outline-none focus:border-amber-400 cursor-pointer"
          >
            <option value="All">Todas las Categorías ({{ adminStore.physicalProducts.length }})</option>
            <option v-for="cat in adminStore.physicalCategories" :key="cat.id" :value="cat.id">
              {{ cat.name }} {{ cat.adminOnly ? '🔒 (Solo Admin)' : '' }} ({{ cat.cantidad }})
            </option>
          </select>
        </div>

        
        <!-- Barra de Paginación Fluida Catálogo Físico -->
        <div v-if="totalPhysicalAdminPages > 1" class="flex flex-col sm:flex-row items-center justify-between gap-3 p-3.5 bg-[#020617] rounded-2xl border border-slate-800 text-xs">
          <div class="text-slate-400 font-mono text-[11px]">
            Mostrando <strong class="text-white">{{ ((physicalAdminCurrentPage - 1) * physicalAdminPageSize) + 1 }}</strong> - <strong class="text-white">{{ Math.min(physicalAdminCurrentPage * physicalAdminPageSize, filteredPhysicalProductsForAdmin.length) }}</strong> de <strong class="text-amber-400 font-bold">{{ filteredPhysicalProductsForAdmin.length }}</strong> productos
          </div>

          <div class="flex items-center gap-1.5">
            <button 
              type="button" 
              @click="physicalAdminCurrentPage = 1" 
              :disabled="physicalAdminCurrentPage === 1" 
              class="px-2.5 py-1.5 rounded-xl bg-slate-900 border border-slate-700 text-slate-300 hover:text-white disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer transition font-bold"
              title="Primera página"
            >
              «
            </button>
            <button 
              type="button" 
              @click="physicalAdminCurrentPage--" 
              :disabled="physicalAdminCurrentPage === 1" 
              class="px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-700 text-slate-300 hover:text-white disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer transition font-bold"
            >
              ‹ Anterior
            </button>

            <span class="px-3 py-1.5 rounded-xl bg-amber-500/20 text-amber-300 border border-amber-500/40 font-mono font-bold">
              {{ physicalAdminCurrentPage }} / {{ totalPhysicalAdminPages }}
            </span>

            <button 
              type="button" 
              @click="physicalAdminCurrentPage++" 
              :disabled="physicalAdminCurrentPage >= totalPhysicalAdminPages" 
              class="px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-700 text-slate-300 hover:text-white disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer transition font-bold"
            >
              Siguiente ›
            </button>
            <button 
              type="button" 
              @click="physicalAdminCurrentPage = totalPhysicalAdminPages" 
              :disabled="physicalAdminCurrentPage >= totalPhysicalAdminPages" 
              class="px-2.5 py-1.5 rounded-xl bg-slate-900 border border-slate-700 text-slate-300 hover:text-white disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer transition font-bold"
              title="Última página"
            >
              »
            </button>

            <select 
              v-model.number="physicalAdminPageSize" 
              class="ml-2 bg-slate-900 border border-slate-700 rounded-xl px-2.5 py-1.5 text-white font-mono text-xs cursor-pointer outline-none"
            >
              <option :value="24">24 / pág</option>
              <option :value="48">48 / pág</option>
              <option :value="96">96 / pág</option>
            </select>
          </div>
        </div>

        <!-- Grid de Tarjetas de Productos Físicos -->
        <div v-if="filteredPhysicalProductsForAdmin.length === 0" class="text-slate-500 text-center py-12 bg-slate-900/30 rounded-2xl border border-slate-800 border-dashed text-xs">
          No se encontraron productos físicos con los filtros actuales.
        </div>
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
          <div 
            v-for="item in paginatedPhysicalProductsForAdmin" :key="item.id" 
            class="bg-[#020617] p-4 rounded-2xl border border-slate-800 hover:border-amber-500/40 transition-all flex flex-col justify-between space-y-3 shadow-lg group"
          >
            <div class="space-y-2.5">
              <div class="w-full h-36 bg-white rounded-xl p-2 flex items-center justify-center overflow-hidden relative">
                <img :src="item.image" :alt="item.name" class="max-h-full max-w-full object-contain group-hover:scale-105 transition-transform" loading="lazy" />
                <span v-if="item.adminOnly" class="absolute top-2 left-2 bg-sky-950 text-sky-300 border border-sky-700 text-[9px] font-bold px-1.5 py-0.5 rounded">
                  🔒 Solo Admin
                </span>
              </div>
              <div>
                <div class="flex items-center justify-between text-[10px] text-slate-400 font-mono">
                  <span>ID: #{{ item.id }}</span>
                  <div class="flex items-center gap-1">
                    <span class="text-emerald-400 font-bold">Stock: {{ item.stock ?? 0 }} un.</span>
                    <button @click.stop="quickAdjustStock(item, -1)" class="w-4 h-4 bg-slate-800 hover:bg-slate-700 text-white rounded text-[10px] font-bold flex items-center justify-center leading-none cursor-pointer" title="Restar 1 un.">-</button>
                    <button @click.stop="quickAdjustStock(item, 1)" class="w-4 h-4 bg-slate-800 hover:bg-slate-700 text-white rounded text-[10px] font-bold flex items-center justify-center leading-none cursor-pointer" title="Sumar 1 un.">+</button>
                  </div>
                </div>
                <div class="flex items-center gap-1.5 mt-1">
                  <span v-if="item.sku" class="text-[9px] font-mono text-slate-300 bg-slate-800 px-1.5 py-0.5 rounded border border-slate-700 truncate max-w-[120px]" :title="'SKU: ' + item.sku">
                    SKU: {{ item.sku }}
                  </span>
                  <span v-if="item.variables && item.variables.length > 1" class="text-[9px] font-semibold text-sky-400 bg-sky-950/60 border border-sky-800/60 px-1.5 py-0.5 rounded">
                    {{ item.variables.length }} variantes
                  </span>
                </div>
                <h4 class="text-xs font-bold text-white line-clamp-2 mt-1 leading-snug" :title="item.name">{{ item.name }}</h4>
                <div class="text-[10px] text-slate-400 line-clamp-2 mt-1" v-html="item.description"></div>
              </div>
            </div>
            <div class="pt-2 border-t border-slate-800 space-y-2">
              <div class="flex items-center justify-between gap-2">
                <div class="flex flex-col">
                  <span class="text-sm font-black text-amber-400 font-mono">{{ item.price }}</span>
                  <span v-if="item.hasDiscount && item.originalPrice" class="text-[10px] text-slate-500 line-through font-mono">{{ item.originalPrice }}</span>
                </div>
                <a 
                  v-if="!item.adminOnly"
                  :href="'/?prod=' + item.id + '&sec=physical'" 
                  target="_blank" 
                  class="text-[10px] bg-slate-800 hover:bg-slate-700 text-sky-300 px-2 py-1 rounded-lg border border-slate-700 transition flex items-center gap-1 font-bold"
                  title="Ver este producto en la tienda pública"
                >
                  <span>🌐 Tienda</span>
                </a>
              </div>

              <!-- BOTONES DE GESTIÓN: EDITAR Y ELIMINAR -->
              <div class="flex gap-1.5 pt-1">
                <button 
                  type="button"
                  @click="openEditPhysicalProductModal(item)"
                  class="flex-1 bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 border border-amber-500/30 text-[11px] font-bold py-1.5 rounded-xl transition flex items-center justify-center gap-1 cursor-pointer"
                  title="Editar este producto físico"
                >
                  <span>✏️ Editar</span>
                </button>
                <button 
                  type="button"
                  @click="deletePhysicalProductItem(item)"
                  class="bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/30 text-[11px] font-bold px-2.5 py-1.5 rounded-xl transition flex items-center justify-center gap-1 cursor-pointer"
                  title="Eliminar del catálogo"
                >
                  <span>🗑️</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- VISTA 2: GESTIÓN DE PEDIDOS FÍSICOS -->
      <div v-else-if="adminPhysicalSubTab === 'orders'" class="space-y-4">
        <!-- Filtros de Búsqueda de Pedidos -->
        <div class="flex flex-col sm:flex-row gap-3 bg-slate-950 p-4 rounded-2xl border border-slate-800">
          <input 
            v-model="physicalOrderSearch" 
            type="text" 
            placeholder="Buscar pedido por ID, cliente, teléfono..." 
            class="bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white outline-none focus:border-amber-400 flex-1"
          >
          <select 
            v-model="physicalOrderStatusFilter" 
            class="bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white outline-none focus:border-amber-400 cursor-pointer"
          >
            <option value="All">Todos los Estados ({{ adminStore.physicalOrders.length }})</option>
            <option value="Pendiente">Pendientes ({{ adminStore.pendingPhysicalOrders.length }})</option>
            <option value="Recibido">Recibidos ({{ adminStore.receivedPhysicalOrders.length }})</option>
            <option value="Confirmado">Confirmados ({{ adminStore.confirmedPhysicalOrders.length }})</option>
            <option value="Cancelado">Cancelados ({{ adminStore.cancelledPhysicalOrders.length }})</option>
          </select>
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
                <!-- Botón Ver Comprobante en Mini Nube -->
                <button 
                  v-if="order.hasReceipt || order.receiptStatus === 'pending_verification'"
                  @click="openReceiptViewer(order, true)"
                  class="bg-sky-600/25 hover:bg-sky-600 text-sky-200 hover:text-white border border-sky-500/40 font-bold text-xs px-3 py-2 rounded-xl transition-all shadow flex items-center gap-1.5 cursor-pointer"
                  title="Ver comprobante en la mini nube (JPG/PDF)"
                >
                  <span>☁️</span>
                  <span>Ver Comprobante ({{ order.receiptFileType === 'application/pdf' ? 'PDF' : 'JPG' }})</span>
                </button>
                <span v-else-if="order.receiptStatus === 'verified_and_purged'" class="text-[10px] text-slate-400 bg-slate-900 border border-slate-800 px-2.5 py-1.5 rounded-xl font-mono">
                  ✓ Comprobante depurado
                </span>
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

                <!-- Eliminar orden física -->
                <button 
                  @click="handleDeletePhysicalOrder(order)"
                  class="bg-slate-800 hover:bg-rose-950 text-slate-400 hover:text-rose-300 font-bold text-xs px-2.5 py-2 rounded-xl border border-slate-700 transition-all flex items-center gap-1 cursor-pointer"
                  title="Eliminar pedido físico"
                >
                  <span>🗑️</span>
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

      <!-- VISTA 3: CATEGORÍA DE PÁGINAS Y SECCIONES (SOLO ADMIN) -->
      <div v-else-if="adminPhysicalSubTab === 'pages'" class="space-y-4">
        <!-- Banner Informativo -->
        <div class="bg-gradient-to-r from-sky-950/60 via-slate-900 to-slate-900 p-5 rounded-2xl border border-sky-500/30 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 shadow-xl">
          <div class="space-y-1">
            <div class="flex items-center gap-2">
              <span class="text-xl">📄</span>
              <h3 class="text-base font-bold text-white">Categoría de Páginas y Secciones (Solo Admin)</h3>
              <span class="bg-sky-500/20 text-sky-300 border border-sky-500/30 text-[10px] font-mono font-bold px-2 py-0.5 rounded-full">
                🔒 Oculto al Público
              </span>
            </div>
            <p class="text-xs text-slate-300">
              Crea y gestiona páginas institucionales, políticas de garantía, envíos, preguntas frecuentes y material de soporte.
            </p>
          </div>
          <button 
            type="button"
            @click="openCreatePhysicalProductModal(true)"
            class="bg-gradient-to-r from-sky-400 to-sky-500 hover:from-sky-300 hover:to-sky-400 text-slate-950 font-black text-xs px-4 py-2.5 rounded-xl shadow-lg transition flex items-center gap-1.5 shrink-0 cursor-pointer"
          >
            <span>➕ Nueva Página</span>
          </button>
        </div>

        <!-- Buscador de Páginas -->
        <div class="flex gap-3 bg-slate-950 p-3 rounded-2xl border border-slate-800">
          <div class="flex-1 relative">
            <span class="absolute left-3 top-2.5 text-slate-500 text-xs">🔍</span>
            <input 
              v-model="adminPhysicalProductSearch" 
              type="text" 
              placeholder="Buscar página por título, slug o contenido..."
              class="w-full bg-slate-900 border border-slate-800 rounded-xl pl-9 pr-3 py-2 text-xs text-white outline-none focus:border-sky-400"
            />
          </div>
        </div>

        <!-- Grid de Páginas -->
        <div v-if="filteredAdminPages.length === 0" class="text-slate-500 text-center py-12 bg-slate-900/30 rounded-2xl border border-slate-800 border-dashed text-xs">
          No se encontraron páginas en esta categoría. Haz clic en "Nueva Página" para crear una.
        </div>
        <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div 
            v-for="page in filteredAdminPages" 
            :key="page.id"
            class="bg-[#020617] p-5 rounded-2xl border border-slate-800 hover:border-sky-500/40 transition-all shadow-lg flex flex-col justify-between space-y-4"
          >
            <div class="space-y-2">
              <div class="flex flex-wrap items-center justify-between gap-2">
                <span class="text-[10px] font-mono text-sky-400 bg-sky-950/80 px-2 py-0.5 rounded border border-sky-800">
                  /{{ page.pageSlug || page.sku || page.id }}
                </span>
                <span class="text-[10px] text-slate-400 font-mono">
                  ID: #{{ page.id }}
                </span>
              </div>
              <h4 class="text-sm font-bold text-white leading-snug">{{ page.name }}</h4>
              <div class="text-xs text-slate-400 line-clamp-3 leading-relaxed bg-slate-950/60 p-3 rounded-xl border border-slate-800/80" v-html="page.description"></div>
            </div>

            <div class="pt-3 border-t border-slate-800/80 flex flex-wrap items-center justify-between gap-2">
              <button 
                type="button"
                @click="openPagePreview(page)"
                class="bg-sky-500/10 hover:bg-sky-500/20 text-sky-300 border border-sky-500/30 text-xs font-bold px-3 py-1.5 rounded-xl transition flex items-center gap-1 cursor-pointer"
              >
                <span>👁️ Previsualizar</span>
              </button>
              <div class="flex items-center gap-1.5">
                <button 
                  type="button"
                  @click="copyPageContent(page)"
                  class="bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold px-3 py-1.5 rounded-xl transition flex items-center gap-1 cursor-pointer"
                  title="Copiar texto de la página"
                >
                  <span>📋 Copiar</span>
                </button>
                <button 
                  type="button"
                  @click="openEditPhysicalProductModal(page)"
                  class="bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 border border-amber-500/30 text-xs font-bold px-3 py-1.5 rounded-xl transition flex items-center gap-1 cursor-pointer"
                >
                  <span>✏️ Editar</span>
                </button>
                <button 
                  type="button"
                  @click="deletePhysicalProductItem(page)"
                  class="bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/30 text-xs font-bold px-2.5 py-1.5 rounded-xl transition flex items-center justify-center cursor-pointer"
                  title="Eliminar página"
                >
                  <span>🗑️</span>
                </button>
              </div>
            </div>
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

      <!-- PANEL DINÁMICO DE STOCK EN VIVO POR PLATAFORMA -->
      <div class="mb-8 space-y-4">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 bg-slate-950 p-4 rounded-2xl border border-slate-800">
          <div>
            <h3 class="text-sm font-black text-white flex items-center gap-2">
              <span>📊</span>
              <span>Disponibilidad de Stock por Plataforma (Tiempo Real)</span>
            </h3>
            <p class="text-xs text-slate-400">Capacidad total y perfiles libres listos para entrega inmediata a clientes.</p>
          </div>
          
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
          <div 
            v-for="(stock, plat) in adminStore.platformStockSummary" 
            :key="plat"
            class="bg-[#020617] p-4 rounded-2xl border border-slate-800 flex flex-col justify-between space-y-3"
          >
            <div class="flex justify-between items-start">
              <div>
                <span class="text-xs font-mono font-bold text-slate-400 block">{{ plat }}</span>
                <span class="text-xl font-black text-white">
                  {{ stock.freeProfiles }}
                  <span class="text-xs text-slate-500 font-normal">/ {{ stock.totalProfiles }} perfiles</span>
                </span>
              </div>
              <span :class="[
                'text-[10px] font-bold px-2 py-0.5 rounded-full border font-mono',
                stock.status === 'in_stock' ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30' :
                stock.status === 'low_stock' ? 'bg-amber-500/20 text-amber-300 border-amber-500/30 animate-pulse' :
                'bg-red-500/20 text-red-400 border-red-500/30 animate-pulse'
              ]">
                {{ stock.status === 'in_stock' ? '🟢 Stock Óptimo' : (stock.status === 'low_stock' ? '🟡 Stock Bajo' : '🔴 Agotado') }}
              </span>
            </div>

            <div class="flex items-center justify-between text-xs pt-1 border-t border-slate-900">
              <span class="text-[11px] text-slate-400">{{ stock.availableAccounts }} cuenta(s) con cupo</span>
              <span class="text-[11px] font-mono text-slate-500">{{ stock.totalAccounts }} reg.</span>
            </div>
          </div>
        </div>
      </div>

      <form @submit.prevent="handleAddStock" class="bg-[#020617] p-6 rounded-2xl border border-slate-800 space-y-5 mb-8">
        <div class="flex items-center gap-2 pb-2 border-b border-slate-800/80">
          <span class="text-base">➕</span>
          <h3 class="text-sm font-black text-white">Cargar al Inventario Mayorista</h3>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
          <div>
            <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Plataforma</label>
            <select v-model="newStock.platform" class="w-full bg-slate-900 border border-slate-800 focus:border-brand-accent rounded-xl p-3 text-white font-semibold outline-none text-sm">
              <option>Netflix</option>
              <option>Max</option>
              <option>Spotify</option>
              <option>Disney+</option>
              <option>IPTV</option>
              <option>Prime Video</option>
              <option>YouTube Premium</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Correo Máster</label>
            <input v-model="newStock.account" type="email" placeholder="master@cuenta.com" class="w-full bg-slate-900 border border-slate-800 focus:border-brand-accent rounded-xl p-3 text-white outline-none text-sm" required>
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Contraseña Máster</label>
            <div class="relative">
              <input 
                v-model="newStock.pass" 
                :type="showNewStockPass ? 'text' : 'password'" 
                placeholder="Pass123!#" 
                class="w-full bg-slate-900 border border-slate-800 focus:border-brand-accent rounded-xl p-3 pr-10 text-white outline-none text-sm font-mono" 
                required
              >
              <button 
                type="button" 
                @click="showNewStockPass = !showNewStockPass" 
                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white p-1"
                :title="showNewStockPass ? 'Ocultar contraseña' : 'Ver contraseña'"
              >
                <svg v-if="showNewStockPass" class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
              </button>
            </div>
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
            <input v-model.number="newStock.cost" type="number" placeholder="5500" class="w-full bg-slate-900 border border-slate-800 focus:border-brand-accent rounded-xl p-3 text-white outline-none font-mono text-sm" required>
          </div>
        </div>

        <!-- SECCIÓN: COLOCAR PIN DE LOS PERFILES -->
        <div class="border-t border-slate-800/80 pt-4 space-y-3">
          <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
            <div>
              <h4 class="text-xs font-bold uppercase tracking-wider text-slate-300 flex items-center gap-2">
                <span>🔢</span>
                <span>PIN de los {{ newStock.profiles.length }} Perfiles</span>
              </h4>
              <p class="text-[11px] text-slate-400">Ingresa el PIN asignado a cada pantalla/perfil para entrega automática a los clientes.</p>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div 
              v-for="(prof, idx) in newStock.profiles" 
              :key="idx" 
              class="bg-slate-950/90 p-3.5 rounded-xl border border-slate-800 space-y-2"
            >
              <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-sky-400 flex items-center gap-1.5">
                  <span>📺</span>
                  <span>Perfil {{ idx + 1 }}</span>
                </span>
                <span class="text-[10px] text-slate-500 font-mono">Pantalla {{ idx + 1 }}</span>
              </div>

              <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">
                  PIN del Perfil <span class="text-rose-400">*</span>
                </label>
                <input 
                  v-model="prof.pin" 
                  type="text" 
                  maxlength="8" 
                  placeholder="Ej: 1234" 
                  class="w-full bg-slate-900 border border-slate-800 focus:border-brand-accent rounded-lg px-3 py-2 text-white font-mono text-sm outline-none font-bold"
                  required
                >
              </div>

              <div>
                <label class="block text-[10px] font-semibold text-slate-400 uppercase mb-1">
                  Nombre / Etiqueta
                </label>
                <input 
                  v-model="prof.name" 
                  type="text" 
                  :placeholder="`Perfil ${idx + 1}`" 
                  class="w-full bg-slate-900 border border-slate-800 focus:border-brand-accent rounded-lg px-3 py-1.5 text-white text-xs outline-none"
                >
              </div>
            </div>
          </div>
        </div>

        <div class="flex justify-end pt-2">
          <button type="submit" class="bg-brand-accent text-[#020617] hover:bg-sky-400 px-8 py-3 font-black rounded-xl transition-all shadow-[0_0_15px_rgba(56,189,248,0.3)] text-sm cursor-pointer">
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
              <th class="px-6 py-4">Perfiles & PINs</th>
              <th class="px-6 py-4">Acción Proveedor</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800">
            <tr v-for="item in adminStore.inventory" :key="item.id" class="hover:bg-slate-800/40 transition-colors">
              <td class="px-6 py-4 font-mono text-xs text-brand-accent font-bold">{{ item.id }}</td>
              <td class="px-6 py-4 font-black text-white">{{ item.platform }}</td>
              <td class="px-6 py-4">
                <div class="font-mono text-xs text-slate-200 flex items-center gap-1.5">
                  <span class="text-slate-400">📧</span>
                  <span>{{ item.account }}</span>
                </div>
                <div class="flex items-center gap-2 mt-1.5">
                  <span class="font-mono text-xs text-amber-300 font-bold bg-slate-950 px-2 py-0.5 rounded border border-slate-800">
                    {{ visiblePasswordMap[item.id] ? item.pass : '••••••••••••' }}
                  </span>
                  <button 
                    type="button"
                    @click="toggleInvPasswordVisibility(item.id)"
                    class="text-slate-400 hover:text-white p-1 hover:bg-slate-800 rounded transition-colors cursor-pointer"
                    :title="visiblePasswordMap[item.id] ? 'Ocultar contraseña' : 'Ver contraseña'"
                  >
                    <svg v-if="visiblePasswordMap[item.id]" class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                  </button>
                  <button 
                    type="button"
                    @click="copyPasswordToClipboard(item.pass)"
                    class="text-slate-400 hover:text-sky-400 p-1 hover:bg-slate-800 rounded transition-colors cursor-pointer"
                    title="Copiar contraseña"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                  </button>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-2 mb-1.5">
                  <span class="text-xs font-mono font-bold bg-slate-900 px-2 py-0.5 rounded border border-slate-800 text-sky-300">
                    {{ (item.profiles || []).filter(p => !p.isAssigned).length }} / {{ (item.profiles || []).length }} libres
                  </span>
                  <span class="text-[11px] font-mono text-emerald-400 font-bold">
                    ${{ (item.cost || 0).toLocaleString() }} ARS
                  </span>
                </div>
                <div class="flex flex-wrap gap-1 max-w-sm">
                  <span 
                    v-for="(p, pIdx) in item.profiles" 
                    :key="pIdx"
                    :class="[
                      'text-[10px] px-1.5 py-0.5 rounded font-mono border flex items-center gap-1',
                      p.isAssigned 
                        ? 'bg-red-500/10 border-red-500/20 text-red-300 line-through' 
                        : 'bg-slate-900 border-slate-800 text-slate-300'
                    ]"
                    :title="`Perfil: ${p.name || ('P' + (pIdx + 1))} | PIN: ${p.pin || 'Sin PIN'} | ${p.isAssigned ? ('Asignado a ' + (p.assignedTo || 'Cliente')) : 'Libre'}`"
                  >
                    <span>${p.name || ('P' + (pIdx + 1))}:</span>
                    <span class="text-amber-300 font-bold font-mono">PIN ${p.pin || '—'}</span>
                  </span>
                </div>
              </td>
              <td class="px-6 py-4 flex items-center gap-2">
                <button 
                  @click="openEditInventoryModal(item)" 
                  class="bg-indigo-600/20 hover:bg-indigo-600/40 text-indigo-300 hover:text-white px-2.5 py-1.5 rounded-lg text-xs font-bold border border-indigo-500/30 transition-colors flex items-center gap-1 cursor-pointer"
                  title="Editar cuenta, costo, plataforma y PINs de perfiles"
                >
                  <span>✏️</span>
                  <span>Editar</span>
                </button>
                <button 
                  @click="openPasswordModal(item)" 
                  class="bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white px-2.5 py-1.5 rounded-lg text-xs font-bold border border-slate-700 transition-colors flex items-center gap-1 cursor-pointer"
                  title="Cambiar contraseña de forma privada o notificar a clientes activos"
                >
                  <span>🔒</span>
                  <span>Clave</span>
                </button>
                <button 
                  @click="handleDeleteInventory(item.id)" 
                  class="bg-red-500/10 hover:bg-red-500/30 text-red-400 border border-red-500/20 px-2.5 py-1.5 rounded-lg text-xs font-bold transition-colors cursor-pointer"
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

    <!-- ==================== PESTAÑA: GESTIÓN DE PROVEEDORES GAMING (ARGENTINA PLAY & JDA) ==================== -->
    <div v-if="adminTab === 'gaming'" class="bg-slate-900/40 rounded-3xl border border-slate-800 p-6 sm:p-8 backdrop-blur-md space-y-8">
      <!-- Encabezado de Sección Oficial de Proveedores Gaming -->
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 pb-6 border-b border-slate-800">
        <div class="flex items-center gap-3.5">
          <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-2xl shadow-lg shadow-indigo-500/20 font-black">
            🎮
          </div>
          <div>
            <div class="flex items-center gap-2 flex-wrap">
              <h2 class="text-2xl font-black text-white">Proveedores Gaming & Arbitraje Multi-Sitio</h2>
              <span class="text-[10px] font-mono font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 px-2.5 py-0.5 rounded-full uppercase">
                Mayor Costo → PVP Público
              </span>
            </div>
            <p class="text-xs sm:text-sm text-slate-400 mt-0.5">
              Consolidado entre todos los sitios scrapeados y agregados. La tienda exhibe el <strong class="text-amber-300">MAYOR COSTO</strong> (+{{ adminStore.gamingStoreMarginPercent }}% margen) como PVP oficial y tú decides a qué proveedor conviene comprar.
            </p>
          </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
          <!-- Input oculto para importación de gamingProducts.json -->
          <input 
            type="file" 
            ref="gamingCatalogFileInput" 
            accept=".json" 
            @change="handleImportGamingCatalog" 
            class="hidden"
          >

          <!-- Sincronización en 1 Clic desde Scrapers Multi-Sitio -->
          <button 
            type="button"
            @click="handleSyncGamingCatalog"
            :disabled="isSyncingGaming"
            class="bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white font-black text-xs px-4 py-2.5 rounded-xl shadow transition flex items-center gap-1.5 cursor-pointer"
            title="Sincronizar en vivo catálogos y precios desde todos los sitios gaming configurados"
          >
            <span :class="{'animate-spin inline-block': isSyncingGaming}">🔄</span>
            <span>{{ isSyncingGaming ? 'Sincronizando Gaming...' : 'Sincronizar Sitios Gaming' }}</span>
          </button>

          <!-- Importar JSON -->
          <button 
            type="button"
            @click="triggerGamingCatalogUpload" 
            class="bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-xs px-3.5 py-2.5 rounded-xl shadow transition flex items-center gap-1.5 cursor-pointer"
            title="Importar gamingProducts.json generado por scraper"
          >
            <span>📥 Importar JSON</span>
          </button>

          <button 
            type="button"
            @click="showGamingNewModal = true"
            class="bg-indigo-600 hover:bg-indigo-500 text-white font-black text-xs px-4 py-2.5 rounded-xl transition-all shadow-md shadow-indigo-600/30 flex items-center gap-1.5 cursor-pointer"
          >
            <span>➕ Agregar Videojuego</span>
          </button>
          <button 
            type="button"
            @click="showFlyerModal = true"
            class="bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 font-black text-xs px-3.5 py-2.5 rounded-xl border border-amber-500/30 transition-all flex items-center gap-1.5 cursor-pointer"
          >
            <span>🎨 Flyer Gamer</span>
          </button>
        </div>
      </div>

      <!-- Tarjetas Métricas de Proveedores Gaming -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-[#020617] border border-slate-800 p-4 rounded-2xl space-y-1">
          <span class="text-[11px] font-bold text-slate-400 block uppercase tracking-wider">Títulos en Catálogo</span>
          <div class="text-2xl font-black text-white font-mono">{{ adminStore.processedGamingProducts.length }} Juegos</div>
          <span class="text-[10px] text-amber-300 font-bold">
            ⚡ {{ adminStore.gamingPricingSummary?.withDiffCount || 0 }} con brecha entre sitios
          </span>
        </div>

        <div class="bg-[#020617] border border-blue-500/30 p-4 rounded-2xl space-y-1">
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold text-blue-300 uppercase tracking-wider">🏢 Sitios & Proveedores</span>
            <button type="button" @click="gamingAdminSubTab = 'sites'" class="text-[10px] text-blue-400 hover:underline font-bold cursor-pointer">Administrar →</button>
          </div>
          <div class="text-2xl font-black text-blue-300 font-mono">{{ adminStore.gamingPricingSummary?.activeSitesCount || (adminStore.gamingProviderSites || []).length }} Sitios</div>
          <span class="text-[10px] text-slate-400">
            📈 {{ adminStore.gamingPricingSummary?.pvpSitesCount || 0 }} activos fijando Mayor Costo PVP
          </span>
        </div>

        <div class="bg-[#020617] border border-amber-500/30 p-4 rounded-2xl space-y-1">
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold text-amber-300 uppercase tracking-wider">💰 Ganancia Real Prom.</span>
            <span class="text-[10px] font-bold text-amber-300 bg-amber-500/15 px-2 py-0.5 rounded-full font-mono">
              +{{ adminStore.gamingStoreMarginPercent }}% PVP
            </span>
          </div>
          <div class="text-2xl font-black text-amber-300 font-mono">+${{ (adminStore.gamingPricingSummary?.avgRealProfitPrimary || 0).toLocaleString() }} ARS</div>
          <span class="text-[10px] text-slate-400">
            Ahorro extra arbitraje: <strong class="text-emerald-300">+${{ (adminStore.gamingPricingSummary?.avgArbitrageExtra || 0).toLocaleString() }} ARS</strong>
          </span>
        </div>

        <div class="bg-[#020617] border border-emerald-500/30 p-4 rounded-2xl space-y-1">
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold text-emerald-400 uppercase tracking-wider">Ventas Gamer</span>
            <span class="text-[10px] font-bold text-emerald-300 bg-emerald-500/20 px-2 py-0.5 rounded-full font-mono">
              ${{ (adminStore.gamingSalesStats?.totalBilled || 0).toLocaleString() }} ARS
            </span>
          </div>
          <div class="text-2xl font-black text-emerald-400 font-mono">{{ adminStore.gamingOrders.length }} Pedidos</div>
          <span class="text-[10px] text-slate-400">Margen est: <strong class="text-emerald-300 font-mono font-bold">${{ (adminStore.gamingSalesStats?.totalProfit || 0).toLocaleString() }} ARS</strong></span>
        </div>
      </div>

      <!-- Navegación de Sub-Pestañas -->
      <div class="flex flex-wrap gap-2 p-1.5 bg-[#020617] rounded-2xl border border-slate-800">
        <button 
          type="button"
          @click="gamingAdminSubTab = 'comparison'"
          :class="['px-4 py-2.5 rounded-xl text-xs font-black transition-all flex items-center gap-1.5', gamingAdminSubTab === 'comparison' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-white']"
        >
          <span>⚖️</span>
          <span>Comparador de Similitudes & Mayor Precio</span>
        </button>

        <button 
          type="button"
          @click="gamingAdminSubTab = 'sites'"
          :class="['px-4 py-2.5 rounded-xl text-xs font-black transition-all flex items-center gap-1.5 cursor-pointer', gamingAdminSubTab === 'sites' ? 'bg-gradient-to-r from-teal-600 to-cyan-600 text-white shadow-lg shadow-teal-500/20' : 'text-slate-400 hover:text-white']"
        >
          <span>🏢</span>
          <span>Sitios & Proveedores ({{ (adminStore.gamingProviderSites || []).length }})</span>
        </button>

        <button 
          type="button"
          @click="gamingAdminSubTab = 'margins'"
          :class="['px-4 py-2.5 rounded-xl text-xs font-black transition-all flex items-center gap-1.5', gamingAdminSubTab === 'margins' ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white shadow-lg shadow-emerald-500/20' : 'text-slate-400 hover:text-white']"
        >
          <span>📊</span>
          <span>Estrategia de Márgenes & Precios (+{{ adminStore.gamingStoreMarginPercent }}% / {{ adminStore.gamingResellerCommissionPercent }}%)</span>
        </button>

        <button 
          type="button"
          @click="gamingAdminSubTab = 'argentinaplay'"
          :class="['px-4 py-2.5 rounded-xl text-xs font-black transition-all flex items-center gap-1.5', gamingAdminSubTab === 'argentinaplay' ? 'bg-blue-600 text-white shadow' : 'text-slate-400 hover:text-white']"
        >
          <span>🇦🇷</span>
          <span>Catálogo ArgPlay</span>
        </button>

        <button 
          type="button"
          @click="gamingAdminSubTab = 'juegosdigitalesargentina'"
          :class="['px-4 py-2.5 rounded-xl text-xs font-black transition-all flex items-center gap-1.5', gamingAdminSubTab === 'juegosdigitalesargentina' ? 'bg-amber-500 text-slate-950 shadow' : 'text-slate-400 hover:text-white']"
        >
          <span>🎮</span>
          <span>Catálogo JDA</span>
        </button>

        <button 
          type="button"
          @click="gamingAdminSubTab = 'bulk_loader'"
          :class="['px-4 py-2.5 rounded-xl text-xs font-black transition-all flex items-center gap-1.5', gamingAdminSubTab === 'bulk_loader' ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-lg shadow-blue-500/20' : 'text-slate-400 hover:text-white']"
        >
          <span>📋</span>
          <span>Carga Masiva TXT</span>
        </button>

        <button 
          type="button"
          @click="gamingAdminSubTab = 'orders'"
          :class="['px-4 py-2.5 rounded-xl text-xs font-black transition-all flex items-center gap-1.5', gamingAdminSubTab === 'orders' ? 'bg-emerald-600 text-white shadow' : 'text-slate-400 hover:text-white']"
        >
          <span>📦</span>
          <span>Pedidos Gamer ({{ adminStore.gamingOrders.length }})</span>
          <span v-if="adminStore.gamingSalesStats?.pendingCount > 0" class="bg-amber-400 text-slate-950 font-mono text-[10px] px-1.5 py-0.2 rounded-full font-black">
            {{ adminStore.gamingSalesStats.pendingCount }}
          </span>
        </button>

        <button 
          type="button"
          @click="gamingAdminSubTab = 'marketing_pos'"
          :class="['px-4 py-2.5 rounded-xl text-xs font-black transition-all flex items-center gap-1.5 cursor-pointer', gamingAdminSubTab === 'marketing_pos' ? 'bg-gradient-to-r from-pink-600 to-purple-600 text-white shadow-lg shadow-pink-500/20' : 'text-slate-400 hover:text-white']"
        >
          <span>📢</span>
          <span>POS, Cupones & Marketing</span>
        </button>

        <button 
          type="button"
          @click="gamingAdminSubTab = 'health_deps'"
          :class="['px-4 py-2.5 rounded-xl text-xs font-black transition-all flex items-center gap-1.5 cursor-pointer', gamingAdminSubTab === 'health_deps' ? 'bg-gradient-to-r from-cyan-600 to-sky-600 text-white shadow-lg shadow-cyan-500/20' : 'text-slate-400 hover:text-white']"
        >
          <span>🩺</span>
          <span>Salud & Dependencias</span>
          <span v-if="(adminStore.gamingCatalogHealth?.healthScore || 100) < 95" class="bg-amber-400 text-slate-950 font-mono text-[10px] px-1.5 py-0.2 rounded-full font-black">
            {{ adminStore.gamingCatalogHealth.healthScore }}%
          </span>
        </button>
      </div>

      <!-- ==================== SUB-VISTA 1: COMPARADOR DE SIMILITUDES & ARBITRAJE DE PRECIOS ==================== -->
      <div v-if="gamingAdminSubTab === 'comparison'" class="space-y-4">
        <!-- Barra de Búsqueda y Filtros de Comparación -->
        <div class="space-y-3 bg-[#020617] p-3.5 rounded-2xl border border-slate-800">
          <!-- Fila 1: Filtros de Diferencia / Arbitraje & Selector de Vista (Tabla vs Tarjetas) -->
          <div class="flex items-center justify-between gap-2 flex-wrap">
            <div class="flex items-center gap-1.5 flex-wrap">
              <button 
                type="button" 
                @click="gamingAdminDifferenceFilter = 'ALL'"
                :class="['px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1 border cursor-pointer', gamingAdminDifferenceFilter === 'ALL' ? 'bg-indigo-600 text-white border-indigo-500 shadow' : 'bg-slate-900 text-slate-400 border-slate-800 hover:text-white']"
              >
                <span>📌 Todos ({{ (adminStore.processedGamingProducts || []).length }})</span>
              </button>
              <button 
                type="button" 
                @click="gamingAdminDifferenceFilter = 'with_diff'"
                :class="['px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1 border cursor-pointer', gamingAdminDifferenceFilter === 'with_diff' ? 'bg-amber-500 text-slate-950 border-amber-400 font-black shadow' : 'bg-slate-900 text-amber-400/80 border-slate-800 hover:text-amber-300']"
              >
                <span>⚡ Con Diferencia / Arbitraje</span>
              </button>
              <button 
                type="button" 
                @click="gamingAdminDifferenceFilter = 'conveyed'"
                :class="['px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1 border cursor-pointer', gamingAdminDifferenceFilter === 'conveyed' ? 'bg-emerald-600 text-white border-emerald-500 shadow' : 'bg-slate-900 text-emerald-400/80 border-slate-800 hover:text-emerald-300']"
              >
                <span>🤝 Precios Convenidos</span>
              </button>
              <button 
                type="button" 
                @click="gamingAdminDifferenceFilter = 'single'"
                :class="['px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1 border cursor-pointer', gamingAdminDifferenceFilter === 'single' ? 'bg-blue-600 text-white border-blue-500 shadow' : 'bg-slate-900 text-slate-400 border-slate-800 hover:text-white']"
              >
                <span>🔹 Proveedor Único</span>
              </button>
            </div>

            <!-- Switcher: Tabla vs Tarjetas -->
            <div class="flex items-center gap-1 bg-slate-900 p-1 rounded-xl border border-slate-800 shrink-0">
              <button 
                type="button" 
                @click="gamingComparisonViewMode = 'table'"
                :class="['px-2.5 py-1 rounded-lg text-xs font-bold transition cursor-pointer', gamingComparisonViewMode === 'table' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-white']"
                title="Vista en tabla completa"
              >
                📑 Tabla
              </button>
              <button 
                type="button" 
                @click="gamingComparisonViewMode = 'cards'"
                :class="['px-2.5 py-1 rounded-lg text-xs font-bold transition cursor-pointer', gamingComparisonViewMode === 'cards' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-white']"
                title="Vista en tarjetas visuales con comparativa"
              >
                🗂️ Tarjetas
              </button>
            </div>
          </div>

          <!-- Fila 2: Buscador & Controles de Filtros -->
          <div class="flex flex-col sm:flex-row gap-2.5 items-center justify-between pt-1 border-t border-slate-800/80">
            <div class="relative flex-1 w-full">
              <input 
                v-model="gamingAdminSearch"
                type="text" 
                placeholder="Buscar título, alias (FC25, GTA V), precio ($42.500) o web (DGS, TTG, Fusion, ArgPlay, JDA, TXT)..."
                class="w-full bg-slate-900 border border-slate-700 rounded-xl py-2 px-3 pl-9 text-xs text-white outline-none focus:border-indigo-400"
              >
              <span class="absolute left-3 top-2.5 text-slate-500 text-xs">🔍</span>
            </div>

            <div class="flex flex-wrap gap-2 w-full sm:w-auto items-center">
              <!-- Toggle: Proveedor Único Visible vs Ver Todas las Webs Scrapeadas -->
              <button 
                type="button" 
                @click="onlyHighestCostProvider = !onlyHighestCostProvider"
                :class="['px-3 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 border cursor-pointer', onlyHighestCostProvider ? 'bg-indigo-600 text-white border-indigo-500 shadow' : 'bg-slate-900 text-slate-400 border-slate-700 hover:text-white']"
                title="Alternar entre vista resumida o comparar todas las webs scrapeadas lado a lado"
              >
                <span>{{ onlyHighestCostProvider ? '✓ Proveedor Único (Mayor Costo)' : '👁️ Comparando Webs Scrapeadas' }}</span>
              </button>

              <!-- Filtrar por Web Scrapeada, Agregada o Lista TXT -->
              <select 
                v-model="gamingAdminProviderFilter" 
                class="bg-slate-900 border border-slate-700 rounded-xl py-2 px-3 text-xs text-white outline-none font-bold cursor-pointer"
                title="Filtrar por web scrapeada, sitio agregado o lista TXT"
              >
                <option value="ALL">🌐 Todas las Fuentes ({{ (adminStore.gamingProviderSites || []).length }})</option>
                <option v-for="site in (adminStore.gamingProviderSites || [])" :key="site.key" :value="site.key">
                  {{ site.badge || site.name }}
                </option>
              </select>

              <!-- Ordenar por Precio / Margen / Diferencia -->
              <select 
                v-model="gamingAdminPriceSort" 
                class="bg-slate-900 border border-slate-700 rounded-xl py-2 px-3 text-xs text-white outline-none font-bold cursor-pointer"
                title="Ordenar por precio, diferencia o costo de proveedor"
              >
                <option value="default">↕️ Orden Predeterminado</option>
                <option value="diff_desc">⚡ Mayor Brecha de Precio (Arbitraje ↓)</option>
                <option value="price_desc">💰 Mayor Precio (PVP ↓)</option>
                <option value="price_asc">🏷️ Menor Precio (PVP ↑)</option>
                <option value="cost_desc">🏢 Mayor Costo Proveedor (↓)</option>
                <option value="margin_desc">📈 Mayor Margen Tienda (↓)</option>
              </select>

              <!-- Rango de Precios -->
              <div class="flex items-center gap-1 bg-slate-900 border border-slate-700 rounded-xl px-2.5 py-1">
                <span class="text-[10px] text-slate-500 font-mono font-bold">$</span>
                <input v-model.number="gamingAdminMinPrice" type="number" placeholder="Min" class="w-14 bg-transparent text-white text-xs outline-none font-mono">
                <span class="text-slate-600 text-xs">-</span>
                <input v-model.number="gamingAdminMaxPrice" type="number" placeholder="Max" class="w-14 bg-transparent text-white text-xs outline-none font-mono">
                <button v-if="gamingAdminMinPrice || gamingAdminMaxPrice" @click="gamingAdminMinPrice = ''; gamingAdminMaxPrice = ''" type="button" class="text-[10px] text-slate-400 hover:text-white ml-0.5" title="Limpiar rango">✕</button>
              </div>

              <select v-model="gamingAdminCategoryFilter" class="bg-slate-900 border border-slate-700 rounded-xl py-2 px-3 text-xs text-white outline-none font-bold">
                <option v-for="cat in adminStore.gamingCategories" :key="cat" :value="cat">{{ cat }}</option>
              </select>
              <select v-if="availableAdminGamingSubcategories.length > 1" v-model="gamingAdminSubcategoryFilter" class="bg-slate-900 border border-slate-700 rounded-xl py-2 px-3 text-xs text-white outline-none font-bold">
                <option v-for="sub in availableAdminGamingSubcategories" :key="sub" :value="sub">
                  {{ sub === 'Todas' ? 'Todas las Subcategorías' : sub }}
                </option>
              </select>
              <select v-model="gamingAdminPlatformFilter" class="bg-slate-900 border border-slate-700 rounded-xl py-2 px-3 text-xs text-white outline-none font-bold">
                <option value="ALL">Todas las Consolas</option>
                <option value="PS5">PlayStation 5</option>
                <option value="PS4">PlayStation 4</option>
                <option value="PS3">PlayStation 3</option>
                <option value="Xbox">Xbox</option>
                <option value="Switch">Nintendo Switch</option>
                <option value="PC">PC / Steam</option>
              </select>
            </div>
          </div>
        </div>

        
        <!-- Barra de Paginación Fluida Gaming -->
        <div v-if="totalGamingAdminPages > 1" class="flex flex-col sm:flex-row items-center justify-between gap-3 p-3.5 bg-[#020617] rounded-2xl border border-slate-800 text-xs">
          <div class="text-slate-400 font-mono text-[11px]">
            Mostrando <strong class="text-white">{{ ((gamingAdminCurrentPage - 1) * gamingAdminPageSize) + 1 }}</strong> - <strong class="text-white">{{ Math.min(gamingAdminCurrentPage * gamingAdminPageSize, filteredGamingForAdmin.length) }}</strong> de <strong class="text-indigo-400 font-bold">{{ filteredGamingForAdmin.length }}</strong> títulos (Sin repetidos • Mayor Valor)
          </div>

          <div class="flex items-center gap-1.5">
            <button 
              type="button" 
              @click="gamingAdminCurrentPage = 1" 
              :disabled="gamingAdminCurrentPage === 1" 
              class="px-2.5 py-1.5 rounded-xl bg-slate-900 border border-slate-700 text-slate-300 hover:text-white disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer transition font-bold"
              title="Primera página"
            >
              «
            </button>
            <button 
              type="button" 
              @click="gamingAdminCurrentPage--" 
              :disabled="gamingAdminCurrentPage === 1" 
              class="px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-700 text-slate-300 hover:text-white disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer transition font-bold"
            >
              ‹ Anterior
            </button>

            <span class="px-3 py-1.5 rounded-xl bg-indigo-500/20 text-indigo-300 border border-indigo-500/40 font-mono font-bold">
              {{ gamingAdminCurrentPage }} / {{ totalGamingAdminPages }}
            </span>

            <button 
              type="button" 
              @click="gamingAdminCurrentPage++" 
              :disabled="gamingAdminCurrentPage >= totalGamingAdminPages" 
              class="px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-700 text-slate-300 hover:text-white disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer transition font-bold"
            >
              Siguiente ›
            </button>
            <button 
              type="button" 
              @click="gamingAdminCurrentPage = totalGamingAdminPages" 
              :disabled="gamingAdminCurrentPage >= totalGamingAdminPages" 
              class="px-2.5 py-1.5 rounded-xl bg-slate-900 border border-slate-700 text-slate-300 hover:text-white disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer transition font-bold"
              title="Última página"
            >
              »
            </button>

            <select 
              v-model.number="gamingAdminPageSize" 
              class="ml-2 bg-slate-900 border border-slate-700 rounded-xl px-2.5 py-1.5 text-white font-mono text-xs cursor-pointer outline-none"
            >
              <option :value="30">30 / pág</option>
              <option :value="60">60 / pág</option>
              <option :value="120">120 / pág</option>
            </select>
          </div>
        </div>

        <!-- Vista 1: Tabla Comparativa Detallada de Similitudes -->
        <div v-if="gamingComparisonViewMode === 'table'" class="overflow-x-auto rounded-2xl border border-slate-800 bg-[#020617]">
          <table class="w-full text-left text-xs text-slate-300">
            <thead class="bg-slate-950 text-slate-400 font-bold uppercase text-[10px] tracking-wider border-b border-slate-800">
              <tr>
                <th class="p-3.5">Videojuego & Plataforma</th>
                <th class="p-3.5 text-indigo-300 bg-indigo-950/20 border-x border-slate-800">
                  {{ onlyHighestCostProvider ? '🏢 Proveedor, Mayor Costo & Brecha' : '🌐 Comparativa Todos los Sitios Agregados & Scrapeados' }}
                </th>
                <th class="p-3.5 text-emerald-400 bg-emerald-950/30 border-r border-slate-800">
                  ⭐️ PVP Web (Mayor Costo)
                </th>
                <th class="p-3.5 text-purple-300">Ganancia Real & Conveniencia</th>
                <th class="p-3.5 text-right">Botón de Proveedor & Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-850">
              <tr v-for="game in paginatedGamingForAdmin" :key="game.id" class="hover:bg-slate-900/60 transition-colors">
                <!-- Título e Imagen Oficial PS Store Vulcan CDN -->
                <td class="p-3.5">
                  <div class="flex items-center gap-3">
                    <img 
                      :src="game.image" 
                      :alt="game.title" 
                      class="w-12 h-12 object-cover rounded-xl border border-slate-700 shrink-0 shadow" 
                      @error="(e) => e.target.src = 'https://images.unsplash.com/photo-1606813907291-d86efa9b94db?w=800&auto=format&fit=crop&q=80'"
                    >
                    <div>
                      <h4 class="font-black text-white text-xs line-clamp-1" :title="game.title">{{ game.title }}</h4>
                      <div class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                        <span class="text-[9px] bg-indigo-500/20 text-indigo-300 font-mono font-bold px-1.5 py-0.2 rounded">{{ game.platform }}</span>
                        <span class="text-[9px] text-slate-400 font-semibold">{{ game.category }}</span>
                        <span v-if="game.subcategory" class="text-[9px] text-amber-400/90 font-medium bg-amber-500/10 px-1.5 py-0.2 rounded border border-amber-500/20">{{ game.subcategory }}</span>
                      </div>
                    </div>
                  </div>
                </td>

                <!-- Columna Proveedor, Webs Scrapeadas & Diferencia de Costos -->
                <td v-if="onlyHighestCostProvider" class="p-3.5 bg-slate-900/60 border-x border-slate-800 font-mono">
                  <button 
                    type="button" 
                    @click="openProviderModal(game)"
                    class="w-full text-left p-2.5 rounded-xl bg-slate-950 hover:bg-slate-900/90 border border-slate-800 hover:border-indigo-500/50 transition cursor-pointer group shadow-sm space-y-1.5"
                    title="Clic para abrir el Botón Proveedor y elegir a quién comprar"
                  >
                    <div class="flex items-center justify-between gap-1">
                      <span class="text-[10px] font-bold text-white flex items-center gap-1.5 truncate">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 shrink-0"></span>
                        <span class="truncate">Compras en: {{ game.bestFulfillmentPrimaryName || 'Proveedor Óptimo' }}</span>
                      </span>
                      <span class="text-[9px] text-indigo-400 group-hover:text-indigo-300 font-bold shrink-0 bg-indigo-500/15 px-1.5 py-0.5 rounded border border-indigo-500/30">
                        {{ (game.activeProvidersList || []).length }} sitios ⚙️
                      </span>
                    </div>

                    <!-- Mini-Pills de Todas las Webs Scrapeadas y Lista .TXT -->
                    <div v-if="game.activeProvidersList && game.activeProvidersList.length > 0" class="flex flex-wrap gap-1 pt-0.5">
                      <span 
                        v-for="p in game.activeProvidersList" 
                        :key="p.key"
                        :class="[
                          'text-[9px] px-1.5 py-0.5 rounded border font-mono',
                          p.isCheapest && game.diffPrimary > 0
                            ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40 font-black'
                            : p.isHighestPvpDriver && game.diffPrimary > 0
                              ? 'bg-amber-500/15 text-amber-300 border-amber-500/35 font-bold'
                              : 'bg-slate-900 text-slate-300 border-slate-700'
                        ]"
                      >
                        <span v-if="p.isHighestPvpDriver && game.diffPrimary > 0">📈</span>
                        <span v-else-if="p.isCheapest && game.diffPrimary > 0">🏆</span>
                        {{ p.shortName }}: ${{ (p.costPrimary || 0).toLocaleString() }}
                      </span>
                    </div>

                    <div class="grid grid-cols-2 gap-2 pt-1 border-t border-slate-800/80 text-[10px]">
                      <div class="min-w-0">
                        <span class="text-amber-300 block text-[9px] truncate" :title="game.highestProviderName">📈 Mayor ({{ game.highestProviderName || 'Base' }}):</span>
                        <strong class="text-white font-black">${{ (game.baseCostPrimary || 0).toLocaleString() }}</strong>
                      </div>
                      <div class="min-w-0">
                        <span class="text-emerald-300 block text-[9px] truncate" :title="game.bestFulfillmentPrimaryName">🏆 Compra ({{ game.bestFulfillmentPrimaryName }}):</span>
                        <strong class="text-emerald-400 font-black">${{ (game.actualPurchaseCostPrimary ?? game.minCostPrimary ?? game.baseCostPrimary ?? 0).toLocaleString() }}</strong>
                      </div>
                    </div>

                    <!-- DIFERENCIA / ARBITRAJE VISIBLE -->
                    <div v-if="game.diffPrimary > 0" class="mt-1 pt-1 border-t border-slate-800/80 flex items-center justify-between text-[10px]">
                      <span class="text-amber-400 font-bold flex items-center gap-1">
                        <span>⚡</span> Diferencia: +${{ game.diffPrimary.toLocaleString() }}
                      </span>
                      <span class="bg-amber-500/20 text-amber-300 px-1.5 py-0.2 rounded text-[9px] font-bold border border-amber-500/40">
                        +{{ game.diffPrimaryPercent }}% Brecha
                      </span>
                    </div>
                    <div v-else class="mt-1 pt-1 border-t border-slate-800/80 flex items-center justify-between text-[9px] text-slate-500">
                      <span>🔹 1 Fuente Cotizada</span>
                      <span>Sin diferencia</span>
                    </div>
                  </button>
                </td>

                <!-- Comparativa Completa de TODAS las Webs Agregadas, Scrapeadas & Lista .TXT -->
                <td v-else class="p-3 bg-slate-900/50 border-x border-slate-800 font-mono min-w-[330px]">
                  <div class="space-y-1.5">
                    <div 
                      v-for="p in (game.activeProvidersList || [])" 
                      :key="p.key"
                      :class="[
                        'p-2 rounded-xl border flex items-center justify-between gap-2 text-[10px]',
                        (game.preferredFulfillmentProvider === p.key || (p.isCheapest && !game.preferredFulfillmentProvider && game.diffPrimary > 0))
                          ? 'bg-emerald-950/35 border-emerald-500/50'
                          : p.isHighestPvpDriver && game.diffPrimary > 0
                            ? 'bg-amber-950/20 border-amber-500/35'
                            : 'bg-slate-950/80 border-slate-800'
                      ]"
                    >
                      <div class="min-w-0 space-y-0.5">
                        <div class="flex items-center gap-1 flex-wrap">
                          <span class="font-bold text-white truncate">{{ p.name }}</span>
                          <span v-if="game.preferredFulfillmentProvider === p.key" class="text-[8px] bg-emerald-600 text-white font-black px-1.5 py-0.2 rounded shrink-0">✓ Elegido</span>
                          <span v-else-if="p.isCheapest && game.diffPrimary > 0" class="text-[8px] bg-emerald-500/20 text-emerald-300 font-black px-1.5 py-0.2 rounded border border-emerald-500/30 shrink-0">🏆 +Barato</span>
                          <span v-if="p.isHighestPvpDriver" class="text-[8px] bg-amber-500/20 text-amber-300 font-black px-1.5 py-0.2 rounded border border-amber-500/30 shrink-0">📈 Base PVP</span>
                        </div>
                        <div class="flex items-center gap-2 text-[9px] text-slate-400">
                          <span>Sec: ${{ (p.costSecondary || 0).toLocaleString() }}</span>
                          <span class="text-emerald-300 font-bold">Ganas: +${{ (p.realProfitPrimary || 0).toLocaleString() }}</span>
                        </div>
                      </div>
                      <div class="flex items-center gap-2 shrink-0">
                        <div class="text-right">
                          <strong :class="['font-black text-xs block', p.isCheapest ? 'text-emerald-400' : 'text-white']">
                            ${{ (p.costPrimary || 0).toLocaleString() }}
                          </strong>
                          <span v-if="p.costPrimary > game.minCostPrimary" class="text-[8px] text-amber-400 block">
                            +${{ (p.costPrimary - game.minCostPrimary).toLocaleString() }}
                          </span>
                        </div>
                        <button
                          type="button"
                          @click="selectPreferredProvider(game, p.key)"
                          :class="[
                            'px-2 py-1 rounded-lg text-[9px] font-black transition cursor-pointer',
                            (game.preferredFulfillmentProvider === p.key || (!game.preferredFulfillmentProvider && game.bestFulfillmentPrimary === p.key))
                              ? 'bg-emerald-600 text-white'
                              : 'bg-slate-800 hover:bg-indigo-600 text-slate-300 hover:text-white'
                          ]"
                          title="Elegir este proveedor para comprar este juego"
                        >
                          {{ (game.preferredFulfillmentProvider === p.key || (!game.preferredFulfillmentProvider && game.bestFulfillmentPrimary === p.key)) ? '✓ Activo' : 'Elegir' }}
                        </button>
                      </div>
                    </div>

                    <div v-if="game.diffPrimary > 0" class="flex items-center justify-between px-2 py-1 rounded-lg bg-amber-500/15 border border-amber-500/30 text-[10px]">
                      <span class="text-amber-300 font-bold">⚡ Brecha Total (Mayor vs Barato):</span>
                      <strong class="text-amber-300 font-black">+${{ game.diffPrimary.toLocaleString() }} (+{{ game.diffPrimaryPercent }}%)</strong>
                    </div>
                  </div>
                </td>

                <!-- PRECIO EN PLATAFORMA (MAYOR PRECIO) -->
                <td class="p-3.5 bg-emerald-950/20 border-r border-slate-800 font-mono">
                  <div class="space-y-1">
                    <div class="flex items-center justify-between gap-2">
                      <span class="text-slate-400 text-[10px]">Primaria:</span>
                      <div class="text-right">
                        <strong class="text-emerald-400 text-sm font-black">${{ game.effectivePrimaryPrice?.toLocaleString() }}</strong>
                        <span class="text-[8px] bg-slate-900 px-1 py-0.2 rounded text-amber-300 block font-normal">
                          {{ game.pvpMode === 'custom' ? 'PVP Manual Fijo' : `📈 Base: ${game.highestProviderName || 'Mayor Costo'}` }}
                        </span>
                      </div>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                      <span class="text-slate-400 text-[10px]">Secundaria:</span>
                      <div class="text-right">
                        <strong class="text-emerald-300 text-xs font-bold">${{ game.effectiveSecondaryPrice?.toLocaleString() }}</strong>
                        <span class="text-[8px] bg-slate-900 px-1 py-0.2 rounded text-slate-400 block font-normal">
                          Público: PS Store Oficial
                        </span>
                      </div>
                    </div>
                  </div>
                </td>

                <!-- Margen Real & Desglose Financiero -->
                <td class="p-3.5 font-mono text-[11px]">
                  <div class="space-y-1.5">
                    <div class="bg-slate-950/80 p-2 rounded-xl border border-slate-800 space-y-1">
                      <div class="flex items-center justify-between text-[10px]">
                        <span class="text-slate-400">Ganancia Real Tienda:</span>
                        <strong class="text-emerald-400 font-black">+${{ (game.actualStoreProfitPrimary ?? game.storeGrossMarginPrimary ?? 0).toLocaleString() }} ARS</strong>
                      </div>
                      <div v-if="game.arbitrageExtraProfit > 0" class="flex items-center justify-between text-[9px] text-amber-300">
                        <span>⚡ Ahorro Arbitraje:</span>
                        <strong>+${{ game.arbitrageExtraProfit.toLocaleString() }} ARS</strong>
                      </div>
                      <div class="flex items-center justify-between text-[10px]">
                        <span class="text-slate-400">Comisión Revendedor:</span>
                        <span class="text-indigo-300 font-bold">+${{ (game.resellerPrimaryProfit || 0).toLocaleString() }} ARS</span>
                      </div>
                      <div class="flex items-center justify-between text-[9px] pt-1 border-t border-slate-800/80 text-slate-400">
                        <span>Neto Real Tienda c/ Rev:</span>
                        <span class="text-emerald-300 font-bold">+${{ (game.actualNetProfitWithReseller ?? game.storeNetPrimaryWithReseller ?? 0).toLocaleString() }} ARS</span>
                      </div>
                    </div>
                    <div class="text-[9px] text-slate-400 flex items-center justify-between px-1 gap-1">
                      <span>Comprar en:</span>
                      <span class="font-bold text-indigo-300 truncate">{{ game.bestFulfillmentPrimaryName }}</span>
                    </div>
                  </div>
                </td>

                <!-- Botón de Proveedor & Acciones -->
                <td class="p-3.5 text-right space-y-1.5">
                  <button 
                    type="button" 
                    @click="openProviderModal(game)"
                    class="w-full px-2.5 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-black text-xs transition flex items-center justify-center gap-1 shadow cursor-pointer active:scale-95"
                    title="Abrir panel del proveedor y elegir a quién comprar"
                  >
                    <span>🏢 Proveedor</span>
                  </button>
                  <div class="flex items-center justify-end gap-1.5">
                    <button 
                      type="button" 
                      @click="openConvenePriceModal(game)"
                      class="px-2 py-1 rounded bg-emerald-600/30 hover:bg-emerald-600 text-emerald-300 hover:text-white font-bold text-[10px] border border-emerald-500/40 transition cursor-pointer active:scale-95"
                      title="Convenir Precio"
                    >
                      🤝 Convenir
                    </button>
                    <button 
                      type="button" 
                      @click="openGamingPriceEdit(game)"
                      class="px-2 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-[10px] transition cursor-pointer"
                      title="Editar cotizaciones multi-sitio"
                    >
                      ✏️
                    </button>
                    <a 
                      v-if="adminStore.gamingProviderSites?.find(s => s.key === game.bestFulfillmentPrimary)?.url || game.visibleProviderUrl || game.activeProvidersList?.[0]?.url"
                      :href="adminStore.gamingProviderSites?.find(s => s.key === game.bestFulfillmentPrimary)?.url || game.visibleProviderUrl || game.activeProvidersList?.[0]?.url" 
                      target="_blank"
                      class="text-[10px] text-indigo-400 hover:underline"
                    >
                      Sitio ↗
                    </a>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Vista 2: Tarjetas Visuales con Comparativa & Arbitraje -->
        <div v-else-if="gamingComparisonViewMode === 'cards'" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
          <div 
            v-for="game in paginatedGamingForAdmin" 
            :key="game.id"
            class="bg-slate-900/70 border border-slate-800 hover:border-indigo-500/50 rounded-2xl p-4 transition-all flex flex-col justify-between space-y-3.5 shadow-lg group"
          >
            <!-- Ficha Encabezado con Portada PS Store -->
            <div class="flex items-start gap-3">
              <img 
                :src="game.image" 
                :alt="game.title" 
                class="w-16 h-16 object-cover rounded-xl border border-slate-700 shrink-0 shadow"
                @error="(e) => e.target.src = 'https://images.unsplash.com/photo-1606813907291-d86efa9b94db?w=800&auto=format&fit=crop&q=80'"
              >
              <div class="flex-1 min-w-0">
                <h4 class="font-black text-white text-xs line-clamp-1 group-hover:text-indigo-300 transition-colors" :title="game.title">{{ game.title }}</h4>
                <div class="flex items-center gap-1.5 mt-1 flex-wrap">
                  <span class="text-[9px] bg-indigo-500/20 text-indigo-300 font-mono font-bold px-1.5 py-0.2 rounded">{{ game.platform }}</span>
                  <span class="text-[9px] text-slate-400 font-semibold">{{ game.category }}</span>
                  <span v-if="game.subcategory" class="text-[9px] text-amber-400/90 font-medium bg-amber-500/10 px-1.5 py-0.2 rounded border border-amber-500/20">{{ game.subcategory }}</span>
                </div>
              </div>
            </div>

            <!-- Webs Scrapeadas y Lista .TXT Cotizadas -->
            <div v-if="game.activeProvidersList && game.activeProvidersList.length > 0" class="flex flex-wrap gap-1.5">
              <span 
                v-for="p in game.activeProvidersList" 
                :key="p.key"
                :class="[
                  'text-[10px] px-2 py-0.5 rounded-lg border font-mono',
                  p.isCheapest && game.diffPrimary > 0
                    ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40 font-black'
                    : p.isHighestPvpDriver && game.diffPrimary > 0
                      ? 'bg-amber-500/15 text-amber-300 border-amber-500/35 font-bold'
                      : 'bg-slate-950 text-slate-300 border-slate-700'
                ]"
              >
                <span v-if="p.isHighestPvpDriver && game.diffPrimary > 0">📈 </span>
                <span v-else-if="p.isCheapest && game.diffPrimary > 0">🏆 </span>
                {{ p.shortName }}: ${{ (p.costPrimary || 0).toLocaleString() }}
              </span>
            </div>

            <!-- Indicador de Brecha y Diferencia de Precios -->
            <div v-if="game.diffPrimary > 0" class="p-2.5 rounded-xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-between text-xs font-mono">
              <div class="flex items-center gap-1.5 text-amber-300 font-bold text-[11px]">
                <span>⚡ Brecha Mayorista:</span>
                <strong class="text-white">+${{ game.diffPrimary.toLocaleString() }}</strong>
              </div>
              <span class="text-[10px] bg-amber-400 text-slate-950 font-black px-2 py-0.5 rounded-full">
                +{{ game.diffPrimaryPercent }}% Arbitraje
              </span>
            </div>
            <div v-else class="p-2 rounded-xl bg-slate-950 border border-slate-800/80 flex items-center justify-between text-[11px] text-slate-400 font-mono">
              <span>🔹 Proveedor Único</span>
              <span>Sin brecha de costos</span>
            </div>

            <!-- Caja Comparativa: Mayor Costo Base & Compra vs PVP Plataforma -->
            <div class="grid grid-cols-2 gap-2 text-xs font-mono">
              <div class="p-2.5 rounded-xl bg-slate-950 border border-slate-800 space-y-1">
                <span class="text-[9px] text-amber-300 font-bold block uppercase tracking-wider truncate" :title="game.highestProviderName">📈 Mayor Costo ({{ game.highestProviderName || 'Base' }})</span>
                <div class="flex justify-between text-[11px]">
                  <span class="text-slate-400">Prim:</span>
                  <strong class="text-white font-black">${{ (game.baseCostPrimary || 0).toLocaleString() }}</strong>
                </div>
                <div class="flex justify-between text-[11px]">
                  <span class="text-slate-400">Sec:</span>
                  <span class="text-indigo-300 font-bold">${{ (game.baseCostSecondary || 0).toLocaleString() }}</span>
                </div>
                <div class="text-[9px] text-emerald-300 pt-1 border-t border-slate-800/80 truncate" :title="game.bestFulfillmentPrimaryName">
                  Compras a: <strong>${{ (game.actualPurchaseCostPrimary ?? game.minCostPrimary ?? game.baseCostPrimary ?? 0).toLocaleString() }} ({{ game.bestFulfillmentPrimaryName }})</strong>
                </div>
              </div>

              <div class="p-2.5 rounded-xl bg-emerald-950/20 border border-emerald-500/30 space-y-1">
                <span class="text-[9px] text-emerald-400 font-bold block uppercase tracking-wider">PVP Público (Mayor Costo)</span>
                <div class="flex justify-between text-[11px]">
                  <span class="text-slate-400">Prim:</span>
                  <strong class="text-emerald-400 font-black">${{ (game.effectivePrimaryPrice || 0).toLocaleString() }}</strong>
                </div>
                <div class="flex justify-between text-[11px]">
                  <span class="text-slate-400">Sec:</span>
                  <strong class="text-emerald-300 font-bold">${{ (game.effectiveSecondaryPrice || 0).toLocaleString() }}</strong>
                </div>
                <div class="text-[9px] text-emerald-300 pt-1 border-t border-emerald-500/20">
                  Ganancia Real: <strong>+${{ (game.actualStoreProfitPrimary ?? game.storeGrossMarginPrimary ?? 0).toLocaleString() }}</strong>
                </div>
              </div>
            </div>

            <!-- Botones de Acción de la Tarjeta -->
            <div class="pt-1 flex items-center gap-1.5">
              <button 
                type="button" 
                @click="openProviderModal(game)"
                class="flex-1 py-2 px-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-black text-xs transition flex items-center justify-center gap-1 shadow cursor-pointer active:scale-95"
              >
                <span>🏢 Proveedor</span>
              </button>
              <button 
                type="button" 
                @click="openConvenePriceModal(game)"
                class="py-2 px-2.5 rounded-xl bg-emerald-600/30 hover:bg-emerald-600 text-emerald-300 hover:text-white border border-emerald-500/40 font-bold text-xs transition flex items-center justify-center gap-1 cursor-pointer active:scale-95"
                title="Convenir Precio"
              >
                <span>🤝 Convenir</span>
              </button>
              <button 
                type="button" 
                @click="openGamingPriceEdit(game)"
                class="py-2 px-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs transition cursor-pointer active:scale-95"
                title="Editar"
              >
                ✏️
              </button>
            </div>
          </div>
        </div>

        <!-- Barra de Paginación Fluida Gaming -->
        <div v-if="totalGamingAdminPages > 1" class="flex flex-col sm:flex-row items-center justify-between gap-3 p-3.5 bg-[#020617] rounded-2xl border border-slate-800 text-xs">
          <div class="text-slate-400 font-mono text-[11px]">
            Mostrando <strong class="text-white">{{ ((gamingAdminCurrentPage - 1) * gamingAdminPageSize) + 1 }}</strong> - <strong class="text-white">{{ Math.min(gamingAdminCurrentPage * gamingAdminPageSize, filteredGamingForAdmin.length) }}</strong> de <strong class="text-indigo-400 font-bold">{{ filteredGamingForAdmin.length }}</strong> títulos (Sin repetidos • Mayor Valor)
          </div>

          <div class="flex items-center gap-1.5">
            <button 
              type="button" 
              @click="gamingAdminCurrentPage = 1" 
              :disabled="gamingAdminCurrentPage === 1" 
              class="px-2.5 py-1.5 rounded-xl bg-slate-900 border border-slate-700 text-slate-300 hover:text-white disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer transition font-bold"
              title="Primera página"
            >
              «
            </button>
            <button 
              type="button" 
              @click="gamingAdminCurrentPage--" 
              :disabled="gamingAdminCurrentPage === 1" 
              class="px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-700 text-slate-300 hover:text-white disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer transition font-bold"
            >
              ‹ Anterior
            </button>

            <span class="px-3 py-1.5 rounded-xl bg-indigo-500/20 text-indigo-300 border border-indigo-500/40 font-mono font-bold">
              {{ gamingAdminCurrentPage }} / {{ totalGamingAdminPages }}
            </span>

            <button 
              type="button" 
              @click="gamingAdminCurrentPage++" 
              :disabled="gamingAdminCurrentPage >= totalGamingAdminPages" 
              class="px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-700 text-slate-300 hover:text-white disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer transition font-bold"
            >
              Siguiente ›
            </button>
            <button 
              type="button" 
              @click="gamingAdminCurrentPage = totalGamingAdminPages" 
              :disabled="gamingAdminCurrentPage >= totalGamingAdminPages" 
              class="px-2.5 py-1.5 rounded-xl bg-slate-900 border border-slate-700 text-slate-300 hover:text-white disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer transition font-bold"
              title="Última página"
            >
              »
            </button>

            <select 
              v-model.number="gamingAdminPageSize" 
              class="ml-2 bg-slate-900 border border-slate-700 rounded-xl px-2.5 py-1.5 text-white font-mono text-xs cursor-pointer outline-none"
            >
              <option :value="30">30 / pág</option>
              <option :value="60">60 / pág</option>
              <option :value="120">120 / pág</option>
            </select>
          </div>
        </div>
      </div>

      <!-- ==================== SUB-VISTA 2: CATÁLOGO ARGENTINA PLAY ==================== -->
      <!-- ==================== SUB-VISTA: ESTRATEGIA DE PRECIOS & MÁRGENES GAMING ==================== -->
      <div v-else-if="gamingAdminSubTab === 'margins'" class="space-y-6">
        <!-- Banner Explicativo de la Estrategia -->
        <div class="bg-gradient-to-r from-emerald-950/40 via-indigo-950/30 to-purple-950/30 border border-emerald-500/30 rounded-3xl p-6 relative overflow-hidden">
          <div class="relative z-10 space-y-2">
            <div class="flex items-center gap-2">
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 uppercase tracking-wider">
                Fórmula Activa de Arbitraje y Margen
              </span>
              <span class="text-xs font-mono text-slate-400">Catálogo: {{ adminStore.gamingPricingSummary?.totalGames || adminStore.processedGamingProducts.length }} Títulos</span>
            </div>
            <h3 class="text-xl sm:text-2xl font-black text-white">
              Gestión Integral de Ganancias y Comisiones Gaming
            </h3>
            <p class="text-xs sm:text-sm text-slate-300 max-w-3xl leading-relaxed">
              Configura el porcentaje de ganancia de StreamePro aplicado sobre el <strong class="text-amber-300">MAYOR COSTO</strong> detectado entre todos los sitios proveedores agregados y scrapeados (ArgPlay, JDA, DGS, Tu Tienda Gamer, Fusion Games, Lista .TXT y tus sitios propios), más la comisión líquida para revendedores. El precio público (PVP) se redondea automáticamente a la centena superior mientras compras en el proveedor más barato.
            </p>
          </div>
        </div>

        <!-- Métricas Promedio del Catálogo Global -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
          <div class="bg-[#020617] border border-slate-800 p-4 rounded-2xl space-y-1">
            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Mayor Costo Prom. (Base PVP)</span>
            <div class="text-lg font-black text-slate-200 font-mono">
              ${{ (adminStore.gamingPricingSummary?.avgPrimaryCost || 0).toLocaleString() }} ARS
            </div>
            <span class="text-[9px] text-slate-400">Máximo entre sitios activos</span>
          </div>

          <div class="bg-[#020617] border border-indigo-500/30 p-4 rounded-2xl space-y-1">
            <span class="text-[10px] uppercase font-bold text-indigo-400 tracking-wider">PVP Final Tienda Prom.</span>
            <div class="text-lg font-black text-indigo-300 font-mono">
              ${{ (adminStore.gamingPricingSummary?.avgPrimaryPVP || 0).toLocaleString() }} ARS
            </div>
            <span class="text-[9px] text-indigo-400/80">Markup aplicado: +{{ adminStore.gamingStoreMarginPercent }}%</span>
          </div>

          <div class="bg-[#020617] border border-emerald-500/30 p-4 rounded-2xl space-y-1">
            <span class="text-[10px] uppercase font-bold text-emerald-400 tracking-wider">Ganancia Real Tienda Prom.</span>
            <div class="text-lg font-black text-emerald-400 font-mono">
              +${{ (adminStore.gamingPricingSummary?.avgRealProfitPrimary || adminStore.gamingPricingSummary?.avgPrimaryStoreProfit || 0).toLocaleString() }} ARS
            </div>
            <span class="text-[9px] text-emerald-400/80">Incl. +${{ (adminStore.gamingPricingSummary?.avgArbitrageExtra || 0).toLocaleString() }} arbitraje</span>
          </div>

          <div class="bg-[#020617] border border-purple-500/30 p-4 rounded-2xl space-y-1">
            <span class="text-[10px] uppercase font-bold text-purple-300 tracking-wider">Comisión Revendedor</span>
            <div class="text-lg font-black text-purple-300 font-mono">
              +${{ (adminStore.gamingPricingSummary?.avgPrimaryResellerProfit || 0).toLocaleString() }} ARS
            </div>
            <span class="text-[9px] text-purple-300/80">{{ adminStore.gamingResellerCommissionPercent }}% s/ PVP (mín ${{ adminStore.gamingMinResellerProfit }})</span>
          </div>

          <div class="bg-[#020617] border border-teal-500/30 p-4 rounded-2xl space-y-1">
            <span class="text-[10px] uppercase font-bold text-teal-300 tracking-wider">Neto Real Tienda c/ Rev.</span>
            <div class="text-lg font-black text-teal-300 font-mono">
              +${{ (adminStore.gamingPricingSummary?.avgRealNetPrimary || adminStore.gamingPricingSummary?.avgPrimaryNetStore || 0).toLocaleString() }} ARS
            </div>
            <span class="text-[9px] text-teal-300/80">Margen neto real de StreamePro</span>
          </div>
        </div>

        <!-- Panel de Control de Parámetros y Simulador Interactivo -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
          <!-- Columna Izquierda: Formulario de Configuración -->
          <div class="lg:col-span-6 bg-[#020617] border border-slate-800 p-6 rounded-3xl space-y-6">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
              <h4 class="text-base font-black text-white flex items-center gap-2">
                <span>⚙️</span>
                <span>Configurar Reglas de Precios</span>
              </h4>
              <span class="text-[10px] font-mono text-slate-400 bg-slate-900 px-2 py-0.5 rounded border border-slate-800">
                Afecta {{ adminStore.processedGamingProducts.length }} juegos
              </span>
            </div>

            <!-- Parámetro 1: % Margen Tienda -->
            <div class="space-y-2">
              <div class="flex justify-between items-center">
                <label class="text-xs font-bold text-slate-300">
                  📈 % Ganancia Tienda (Markup sobre Costo Mayorista)
                </label>
                <span class="text-xs font-black font-mono text-emerald-400">+{{ gamingMarginForm.storeMargin }}%</span>
              </div>
              <input 
                v-model.number="gamingMarginForm.storeMargin" 
                type="number" 
                min="0" 
                max="200" 
                step="1"
                class="w-full bg-slate-900 border border-slate-700 rounded-xl p-3 text-white font-mono font-bold text-sm outline-none focus:border-emerald-500 transition"
              >
              <div class="flex flex-wrap gap-1.5 pt-1">
                <span class="text-[10px] text-slate-400 mr-1 self-center">Presets:</span>
                <button 
                  v-for="p in [15, 20, 25, 30, 35, 40]" 
                  :key="p"
                  type="button"
                  @click="applyStoreMarginPreset(p)"
                  :class="['px-2.5 py-1 rounded-lg text-xs font-mono font-bold transition cursor-pointer', gamingMarginForm.storeMargin === p ? 'bg-emerald-600 text-white' : 'bg-slate-800 text-slate-300 hover:bg-slate-700']"
                >
                  +{{ p }}%
                </button>
              </div>
              <p class="text-[10px] text-slate-400">
                Se suma automáticamente al costo mayorista del proveedor líder y se redondea a la centena superior.
              </p>
            </div>

            <!-- Parámetro 2: % Comisión Revendedor -->
            <div class="space-y-2">
              <div class="flex justify-between items-center">
                <label class="text-xs font-bold text-slate-300">
                  🤝 % Comisión Revendedor (Sobre PVP Final)
                </label>
                <span class="text-xs font-black font-mono text-indigo-400">{{ gamingMarginForm.resellerCommission }}%</span>
              </div>
              <input 
                v-model.number="gamingMarginForm.resellerCommission" 
                type="number" 
                min="0" 
                max="50" 
                step="1"
                class="w-full bg-slate-900 border border-slate-700 rounded-xl p-3 text-white font-mono font-bold text-sm outline-none focus:border-indigo-500 transition"
              >
              <div class="flex flex-wrap gap-1.5 pt-1">
                <span class="text-[10px] text-slate-400 mr-1 self-center">Presets:</span>
                <button 
                  v-for="c in [5, 8, 10, 12, 15, 20]" 
                  :key="c"
                  type="button"
                  @click="applyResellerCommissionPreset(c)"
                  :class="['px-2.5 py-1 rounded-lg text-xs font-mono font-bold transition cursor-pointer', gamingMarginForm.resellerCommission === c ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-300 hover:bg-slate-700']"
                >
                  {{ c }}%
                </button>
              </div>
              <p class="text-[10px] text-slate-400">
                Comisión directa que se acredita en la billetera virtual del revendedor cuando un cliente compra con su enlace de referido.
              </p>
            </div>

            <!-- Parámetro 3: Ganancia Mínima Revendedor -->
            <div class="space-y-2">
              <div class="flex justify-between items-center">
                <label class="text-xs font-bold text-slate-300">
                  🛡️ Ganancia Mínima Garantizada por Juego ($ ARS)
                </label>
                <span class="text-xs font-black font-mono text-purple-400">${{ gamingMarginForm.minResellerProfit.toLocaleString() }} ARS</span>
              </div>
              <input 
                v-model.number="gamingMarginForm.minResellerProfit" 
                type="number" 
                min="0" 
                max="10000" 
                step="100"
                class="w-full bg-slate-900 border border-slate-700 rounded-xl p-3 text-white font-mono font-bold text-sm outline-none focus:border-purple-500 transition"
              >
              <div class="flex flex-wrap gap-1.5 pt-1">
                <span class="text-[10px] text-slate-400 mr-1 self-center">Presets:</span>
                <button 
                  v-for="amt in [800, 1000, 1500, 2000]" 
                  :key="amt"
                  type="button"
                  @click="applyMinResellerProfitPreset(amt)"
                  :class="['px-2.5 py-1 rounded-lg text-xs font-mono font-bold transition cursor-pointer', gamingMarginForm.minResellerProfit === amt ? 'bg-purple-600 text-white' : 'bg-slate-800 text-slate-300 hover:bg-slate-700']"
                >
                  ${{ amt.toLocaleString() }}
                </button>
              </div>
              <p class="text-[10px] text-slate-400">
                Garantiza que el revendedor nunca gane menos de este valor en ningún título del catálogo.
              </p>
            </div>

            <!-- Botón Guardar -->
            <div class="pt-2">
              <button 
                type="button"
                @click="saveGamingMarginSettings"
                :disabled="isSavingGamingMargins"
                class="w-full py-3.5 px-4 rounded-xl font-black text-xs text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 disabled:opacity-50 transition shadow-lg shadow-emerald-600/30 flex items-center justify-center gap-2 cursor-pointer"
              >
                <span>💾</span>
                <span>{{ isSavingGamingMargins ? 'Guardando y Aplicando...' : `Guardar y Aplicar a Todo el Catálogo (${adminStore.processedGamingProducts.length} Juegos)` }}</span>
              </button>
            </div>
          </div>

          <!-- Columna Derecha: Simulador Interactivo en Vivo -->
          <div class="lg:col-span-6 bg-[#020617] border border-slate-800 p-6 rounded-3xl space-y-6">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
              <h4 class="text-base font-black text-white flex items-center gap-2">
                <span>🧮</span>
                <span>Simulador en Tiempo Real</span>
              </h4>
              <span class="text-[10px] font-mono text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-2 py-0.5 rounded">
                Calculadora Dinámica
              </span>
            </div>

            <!-- Entrada de Costo de Prueba -->
            <div class="space-y-2">
              <label class="text-xs font-bold text-slate-300">
                Simular Costo Mayorista de Proveedor ($ ARS):
              </label>
              <div class="relative">
                <span class="absolute left-3.5 top-3 text-slate-500 font-mono font-bold">$</span>
                <input 
                  v-model.number="gamingMarginForm.simulatedCost" 
                  type="number" 
                  step="500"
                  class="w-full bg-slate-900 border border-slate-700 rounded-xl p-3 pl-8 text-white font-mono font-bold text-sm outline-none focus:border-indigo-500"
                >
              </div>
              <div class="flex flex-wrap gap-1.5 pt-1">
                <span class="text-[10px] text-slate-400 mr-1 self-center">Ejemplos típicos:</span>
                <button 
                  v-for="amt in [12000, 18000, 25000, 35000, 48000]" 
                  :key="amt"
                  type="button"
                  @click="gamingMarginForm.simulatedCost = amt"
                  class="px-2 py-0.5 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 text-[10px] font-mono transition cursor-pointer"
                >
                  ${{ amt.toLocaleString() }}
                </button>
              </div>
            </div>

            <!-- Tarjeta Desglose del Resultado -->
            <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800/90 space-y-3">
              <div class="flex items-center justify-between text-xs pb-2 border-b border-slate-800">
                <span class="text-slate-400">Costo Base Proveedor:</span>
                <strong class="text-white font-mono font-bold">${{ simulatedCalculation.cost.toLocaleString() }} ARS</strong>
              </div>

              <div class="flex items-center justify-between text-xs">
                <span class="text-emerald-400 font-bold">+ Ganancia Tienda (Markup +{{ gamingMarginForm.storeMargin }}%):</span>
                <span class="text-emerald-400 font-mono font-black">+${{ simulatedCalculation.markup.toLocaleString() }} ARS</span>
              </div>

              <div class="flex items-center justify-between text-sm py-2 px-3 bg-indigo-500/10 rounded-xl border border-indigo-500/20">
                <span class="text-white font-black">🏷️ PVP Final al Público:</span>
                <span class="text-indigo-300 font-mono font-black text-base">${{ simulatedCalculation.pvp.toLocaleString() }} ARS</span>
              </div>

              <div class="flex items-center justify-between text-xs pt-1 border-t border-slate-800">
                <span class="text-purple-300">🤝 Comisión Revendedor ({{ gamingMarginForm.resellerCommission }}%):</span>
                <span class="text-purple-400 font-mono font-bold">+${{ simulatedCalculation.resellerCut.toLocaleString() }} ARS</span>
              </div>

              <div class="flex items-center justify-between text-xs">
                <span class="text-teal-300">💰 Ganancia Neta Tienda (con Revendedor):</span>
                <strong class="text-teal-400 font-mono font-black">+${{ simulatedCalculation.storeNetWithReseller.toLocaleString() }} ARS ({{ simulatedCalculation.storeEffectiveNetPct }}% s/ PVP)</strong>
              </div>
            </div>

            <!-- Comparación Escenarios de Venta -->
            <div class="grid grid-cols-2 gap-3 text-xs">
              <div class="bg-emerald-950/20 border border-emerald-500/30 p-3 rounded-xl space-y-1">
                <span class="text-[10px] text-emerald-400 font-bold uppercase block">Escenario A: Venta Directa</span>
                <div class="text-sm font-black text-white font-mono">+${{ simulatedCalculation.storeGrossMargin.toLocaleString() }} ARS</div>
                <p class="text-[9px] text-slate-400">100% de la ganancia queda para StreamePro.</p>
              </div>

              <div class="bg-indigo-950/20 border border-indigo-500/30 p-3 rounded-xl space-y-1">
                <span class="text-[10px] text-indigo-400 font-bold uppercase block">Escenario B: Con Revendedor</span>
                <div class="text-sm font-black text-white font-mono">+${{ simulatedCalculation.storeNetWithReseller.toLocaleString() }} ARS</div>
                <p class="text-[9px] text-slate-400">Tienda gana neto tras pagar ${{ simulatedCalculation.resellerCut.toLocaleString() }} al revendedor.</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Muestra en Vivo de Juegos Reales con Margen Aplicado -->
        <div class="bg-[#020617] border border-slate-800 p-6 rounded-3xl space-y-4">
          <div class="flex items-center justify-between">
            <div>
              <h4 class="text-base font-black text-white">Muestra en Vivo de Precios y Márgenes</h4>
              <p class="text-xs text-slate-400">Comprueba cómo se aplican los márgenes en los juegos reales del catálogo</p>
            </div>
            <button 
              type="button" 
              @click="gamingAdminSubTab = 'comparison'"
              class="text-xs font-bold text-indigo-400 hover:text-indigo-300 cursor-pointer flex items-center gap-1"
            >
              <span>Ver los {{ adminStore.processedGamingProducts.length }} juegos en Comparador</span>
              <span>→</span>
            </button>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            <div 
              v-for="game in (adminStore.processedGamingProducts || []).slice(0, 6)" 
              :key="game.id"
              class="bg-slate-900/60 border border-slate-800 p-3.5 rounded-2xl flex items-start gap-3 hover:border-slate-700 transition"
            >
              <img 
                :src="game.image" 
                :alt="game.title" 
                class="w-14 h-14 object-cover rounded-xl border border-slate-700 shrink-0"
                @error="(e) => e.target.src = 'https://images.unsplash.com/photo-1606813907291-d86efa9b94db?w=800&auto=format&fit=crop&q=80'"
              >
              <div class="flex-1 min-w-0 space-y-1 text-xs">
                <h5 class="font-black text-white line-clamp-1 text-xs">{{ game.title }}</h5>
                <div class="flex items-center gap-1">
                  <span class="text-[9px] bg-indigo-500/20 text-indigo-300 font-mono font-bold px-1.5 py-0.2 rounded">{{ game.platform }}</span>
                  <span class="text-[9px] text-slate-400">{{ game.visibleProviderBadge }}</span>
                </div>
                <div class="pt-1 space-y-0.5 text-[11px] font-mono">
                  <div class="flex justify-between text-slate-400">
                    <span>Costo Proveedor:</span>
                    <strong class="text-slate-200">${{ (game.baseCostPrimary || 0).toLocaleString() }}</strong>
                  </div>
                  <div class="flex justify-between text-indigo-300">
                    <span>PVP Público:</span>
                    <strong class="text-emerald-400 font-bold">${{ (game.effectivePrimaryPrice || 0).toLocaleString() }}</strong>
                  </div>
                  <div class="flex justify-between text-[10px] text-slate-400 pt-0.5 border-t border-slate-800">
                    <span>Tienda: <strong class="text-emerald-400">+${{ (game.storeGrossMarginPrimary || 0).toLocaleString() }}</strong></span>
                    <span>Revendedor: <strong class="text-purple-300">+${{ (game.resellerPrimaryProfit || 0).toLocaleString() }}</strong></span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ==================== SUB-VISTA: CARGA MASIVA POR LISTA / TXT & SIMILITUD INTELIGENTE ==================== -->
      <div v-else-if="gamingAdminSubTab === 'bulk_loader'" class="space-y-6">
        <!-- Banner Explicativo -->
        <div class="bg-gradient-to-r from-blue-950/40 via-indigo-950/30 to-purple-950/30 border border-blue-500/30 rounded-3xl p-6 relative overflow-hidden">
          <div class="relative z-10 space-y-2">
            <div class="flex items-center gap-2">
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-500/20 text-blue-300 border border-blue-500/30 uppercase tracking-wider">
                Motor de Similitud Inteligente Activo
              </span>
              <span class="text-xs font-mono text-slate-400">Catálogo Base: {{ adminStore.processedGamingProducts.length }} Juegos</span>
            </div>
            <h3 class="text-xl sm:text-2xl font-black text-white">
              Carga Masiva de Videojuegos por Lista o Archivo .TXT
            </h3>
            <p class="text-xs sm:text-sm text-slate-300 max-w-3xl leading-relaxed">
              Pega listas de precios mayoristas enviadas por WhatsApp o sube un archivo <code>.txt</code>. El motor omite automáticamente fechas y cabeceras de ofertas (como <em>OFERTAS DESTACADAS HASTA EL DÍA...</em>), elimina emojis y símbolos iniciales, y asocia inteligentemente cada título con tu catálogo para actualizar costos o crear juegos nuevos en un solo clic.
            </p>
          </div>
        </div>

        <!-- PASO 1: Configuración de Proveedor y Entrada de Lista -->
        <div v-if="bulkLoaderStep === 'input'" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
          <!-- Columna Izquierda: Datos del Proveedor Mayorista -->
          <div class="lg:col-span-5 bg-[#020617] border border-slate-800 p-6 rounded-3xl space-y-5">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
              <h4 class="text-base font-black text-white flex items-center gap-2">
                <span>🏢</span>
                <span>Datos del Proveedor Mayorista</span>
              </h4>
              <span class="text-[10px] font-mono text-slate-400 bg-slate-900 px-2 py-0.5 rounded border border-slate-800">
                Paso 1 de 2
              </span>
            </div>

            <!-- Nombre del Proveedor -->
            <div class="space-y-1.5">
              <label class="text-xs font-bold text-slate-300">Nombre del Proveedor / Mayorista:</label>
              <input 
                v-model="bulkProviderConfig.name" 
                type="text" 
                placeholder="Ej: Argentina Play, JDA, Distribuidor Ofertas..."
                class="w-full bg-slate-900 border border-slate-700 rounded-xl p-3 text-white text-xs font-bold outline-none focus:border-blue-500"
              >
              <div class="flex flex-wrap gap-1.5 pt-1">
                <span class="text-[10px] text-slate-400 mr-1 self-center">Rápidos:</span>
                <button 
                  v-for="pName in ['Argentina Play', 'Juegos Digitales Argentina', 'Proveedor Ofertas']" 
                  :key="pName"
                  type="button"
                  @click="bulkProviderConfig.name = pName"
                  class="px-2 py-0.5 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 text-[10px] font-mono transition cursor-pointer"
                >
                  {{ pName }}
                </button>
              </div>
            </div>

            <!-- WhatsApp del Proveedor -->
            <div class="space-y-1.5">
              <label class="text-xs font-bold text-slate-300">Número de WhatsApp del Proveedor:</label>
              <div class="relative">
                <span class="absolute left-3 top-3 text-emerald-400 text-xs">💬</span>
                <input 
                  v-model="bulkProviderConfig.whatsapp" 
                  type="text" 
                  placeholder="Ej: +54 9 11 4050-6070"
                  class="w-full bg-slate-900 border border-slate-700 rounded-xl p-3 pl-8 text-white text-xs font-mono font-bold outline-none focus:border-emerald-500"
                >
              </div>
              <p class="text-[10px] text-slate-400">Permite contactar al proveedor directamente al realizar el pedido.</p>
            </div>

            <!-- Página Web del Proveedor -->
            <div class="space-y-1.5">
              <label class="text-xs font-bold text-slate-300">Página Web o Enlace Oficial:</label>
              <div class="relative">
                <span class="absolute left-3 top-3 text-blue-400 text-xs">🌐</span>
                <input 
                  v-model="bulkProviderConfig.url" 
                  type="url" 
                  placeholder="https://..."
                  class="w-full bg-slate-900 border border-slate-700 rounded-xl p-3 pl-8 text-white text-xs font-mono outline-none focus:border-blue-500"
                >
              </div>
            </div>

            <!-- Modalidad de Licencia a Convenir -->
            <div class="space-y-2 pt-2 border-t border-slate-800">
              <label class="text-xs font-bold text-slate-300">Aplicar precios de la lista como:</label>
              <div class="grid grid-cols-2 gap-2">
                <button 
                  type="button" 
                  @click="bulkProviderConfig.licenseType = 'primary'"
                  :class="['p-3 rounded-xl border text-xs font-bold transition flex flex-col items-center gap-1 cursor-pointer', bulkProviderConfig.licenseType === 'primary' ? 'bg-indigo-600/20 border-indigo-500 text-white' : 'bg-slate-900 border-slate-800 text-slate-400 hover:text-white']"
                >
                  <span>⭐️ Cuenta Primaria</span>
                  <span class="text-[10px] text-slate-400 font-normal">Costo Base Primaria</span>
                </button>
                <button 
                  type="button" 
                  @click="bulkProviderConfig.licenseType = 'secondary'"
                  :class="['p-3 rounded-xl border text-xs font-bold transition flex flex-col items-center gap-1 cursor-pointer', bulkProviderConfig.licenseType === 'secondary' ? 'bg-amber-600/20 border-amber-500 text-white' : 'bg-slate-900 border-slate-800 text-slate-400 hover:text-white']"
                >
                  <span>⚡️ Cuenta Secundaria</span>
                  <span class="text-[10px] text-slate-400 font-normal">Costo Base Secundaria</span>
                </button>
              </div>
            </div>

            <!-- Recordatorio de Margen Activo -->
            <div class="p-3 bg-emerald-950/20 border border-emerald-500/30 rounded-xl flex items-center justify-between text-xs">
              <span class="text-slate-300">Margen de Tienda Activo:</span>
              <strong class="text-emerald-400 font-mono font-bold">+{{ adminStore.gamingStoreMarginPercent }}% PVP</strong>
            </div>
          </div>

          <!-- Columna Derecha: Entrada de Lista de Texto / Subida .TXT -->
          <div class="lg:col-span-7 bg-[#020617] border border-slate-800 p-6 rounded-3xl space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-slate-800">
              <div>
                <h4 class="text-base font-black text-white flex items-center gap-2">
                  <span>📝</span>
                  <span>Lista de Videojuegos & Precios</span>
                </h4>
                <p class="text-xs text-slate-400">Pega tu lista o sube un archivo .txt con títulos y precios.</p>
              </div>

              <!-- Input de Archivo .TXT -->
              <div class="flex items-center gap-2">
                <input 
                  type="file" 
                  ref="bulkTxtFileInput" 
                  accept=".txt" 
                  @change="handleBulkTxtFileUpload" 
                  class="hidden"
                >
                <button 
                  type="button" 
                  @click="bulkTxtFileInput.click()" 
                  class="px-3 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold transition flex items-center gap-1 shadow cursor-pointer"
                >
                  <span>📁 Subir .TXT</span>
                </button>
                <button 
                  type="button" 
                  @click="loadExampleTxtList" 
                  class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold transition flex items-center gap-1 cursor-pointer"
                  title="Cargar la lista de ejemplo con 197 juegos"
                >
                  <span>⚡️ Cargar Ejemplo</span>
                </button>
              </div>
            </div>

            <!-- Textarea de Entrada -->
            <div class="space-y-2">
              <textarea 
                v-model="rawGamingListText" 
                rows="16" 
                placeholder="Pega aquí la lista de ofertas del proveedor. Ejemplo:

OFERTAS DESTACADAS HASTA EL DÍA 20/11/2026⚠️

🤖ASTRO BOT PS5 - $42.500
🗡️Assassin's Creed Antiquity Pack - $22.400
🗡️Assassin's Creed Rogue Remastered - $11.900
⚽CAPTAIN TSUBASA 2: WORLD FIGHTERS PS5 - $52.500
...
"
                class="w-full bg-slate-900 border border-slate-700 rounded-2xl p-4 text-white text-xs font-mono leading-relaxed outline-none focus:border-blue-500 custom-scrollbar resize-y"
              ></textarea>
              <div class="flex items-center justify-between text-xs text-slate-400">
                <span>{{ rawGamingListText ? rawGamingListText.split('\n').filter(l => l.trim()).length : 0 }} líneas en total</span>
                <button 
                  v-if="rawGamingListText" 
                  @click="rawGamingListText = ''" 
                  type="button" 
                  class="text-rose-400 hover:underline cursor-pointer"
                >
                  Limpiar texto
                </button>
              </div>
            </div>

            <!-- Botón de Acción: Analizar con Similitud Inteligente -->
            <div class="pt-2">
              <button 
                type="button" 
                @click="analyzeGamingList" 
                :disabled="isAnalyzingBulkList || !rawGamingListText.trim()"
                class="w-full py-4 rounded-2xl bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 disabled:opacity-40 text-white font-black text-sm transition shadow-lg shadow-indigo-600/30 flex items-center justify-center gap-2 cursor-pointer"
              >
                <span :class="{'animate-spin inline-block': isAnalyzingBulkList}">🔍</span>
                <span>{{ isAnalyzingBulkList ? 'Analizando Similitudes y Omitiendo Avisos...' : 'Analizar Lista con Similitud Inteligente' }}</span>
              </button>
            </div>
          </div>
        </div>

        <!-- PASO 2: Vista Previa, Similitud y Confirmación -->
        <div v-else-if="bulkLoaderStep === 'analyzed'" class="space-y-6">
          <!-- Métricas del Análisis -->
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-[#020617] border border-slate-800 p-4 rounded-2xl space-y-1">
              <span class="text-[10px] uppercase font-bold text-slate-400">Total Juegos Detectados</span>
              <div class="text-2xl font-black text-white font-mono">{{ bulkAnalyzedGames.length }} Juegos</div>
              <span class="text-[10px] text-slate-400">Sin contar cabeceras</span>
            </div>

            <div class="bg-[#020617] border border-emerald-500/30 p-4 rounded-2xl space-y-1">
              <div class="flex items-center justify-between">
                <span class="text-[10px] uppercase font-bold text-emerald-400">🟢 Coincidencias en Catálogo</span>
                <span class="text-[10px] bg-emerald-500/20 text-emerald-300 px-1.5 py-0.2 rounded font-mono font-bold">Actualizar</span>
              </div>
              <div class="text-2xl font-black text-emerald-400 font-mono">
                {{ bulkAnalyzedGames.filter(i => !i.isNewGame).length }}
              </div>
              <span class="text-[10px] text-slate-400">Se actualizará su costo y margen</span>
            </div>

            <div class="bg-[#020617] border border-blue-500/30 p-4 rounded-2xl space-y-1">
              <div class="flex items-center justify-between">
                <span class="text-[10px] uppercase font-bold text-blue-400">🔵 Títulos Nuevos</span>
                <span class="text-[10px] bg-blue-500/20 text-blue-300 px-1.5 py-0.2 rounded font-mono font-bold">Crear</span>
              </div>
              <div class="text-2xl font-black text-blue-400 font-mono">
                {{ bulkAnalyzedGames.filter(i => i.isNewGame).length }}
              </div>
              <span class="text-[10px] text-slate-400">Se añadirán al catálogo oficial</span>
            </div>

            <div class="bg-[#020617] border border-amber-500/30 p-4 rounded-2xl space-y-1">
              <div class="flex items-center justify-between">
                <span class="text-[10px] uppercase font-bold text-amber-400">⚠️ Avisos y Fechas Omitidos</span>
                <span class="text-[10px] bg-amber-500/20 text-amber-300 px-1.5 py-0.2 rounded font-mono font-bold">Filtro OK</span>
              </div>
              <div class="text-2xl font-black text-amber-400 font-mono">
                {{ bulkSkippedHeaders.length }}
              </div>
              <span class="text-[10px] text-slate-400">Líneas de ofertas no numéricas</span>
            </div>
          </div>

          <!-- Barra de Control y Filtros de la Tabla Analizada -->
          <div class="flex flex-col sm:flex-row gap-3 items-center justify-between bg-[#020617] p-4 rounded-2xl border border-slate-800">
            <!-- Pestañas de Filtro (Incluye Comparativa vs Webs Scrapeadas) -->
            <div class="flex flex-wrap items-center gap-1.5">
              <button 
                type="button" 
                @click="bulkFilterStatus = 'ALL'"
                :class="['px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer', bulkFilterStatus === 'ALL' ? 'bg-indigo-600 text-white shadow' : 'bg-slate-900 text-slate-400 hover:text-white']"
              >
                Todos ({{ bulkAnalyzedGames.length }})
              </button>
              <button 
                type="button" 
                @click="bulkFilterStatus = 'matched'"
                :class="['px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer', bulkFilterStatus === 'matched' ? 'bg-emerald-600 text-white shadow' : 'bg-slate-900 text-slate-400 hover:text-white']"
              >
                🟢 Coincidencias ({{ bulkAnalyzedGames.filter(i => !i.isNewGame).length }})
              </button>
              <button 
                type="button" 
                @click="bulkFilterStatus = 'cheaper_list'"
                :class="['px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer', bulkFilterStatus === 'cheaper_list' ? 'bg-teal-600 text-white shadow' : 'bg-slate-900 text-teal-400 hover:text-teal-300 border border-teal-500/20']"
                title="Juegos donde tu lista .txt tiene menor precio que todas las webs scrapeadas"
              >
                💰 Más Barato en Lista ({{ bulkAnalyzedGames.filter(i => i.isCheaperInList).length }})
              </button>
              <button 
                type="button" 
                @click="bulkFilterStatus = 'cheaper_webs'"
                :class="['px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer', bulkFilterStatus === 'cheaper_webs' ? 'bg-amber-500 text-slate-950 font-black shadow' : 'bg-slate-900 text-amber-400 hover:text-amber-300 border border-amber-500/20']"
                title="Juegos donde alguna web scrapeada es aún más económica que tu lista"
              >
                🌐 Más Barato en Webs ({{ bulkAnalyzedGames.filter(i => i.isCheaperInWebs).length }})
              </button>
              <button 
                type="button" 
                @click="bulkFilterStatus = 'new'"
                :class="['px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer', bulkFilterStatus === 'new' ? 'bg-blue-600 text-white shadow' : 'bg-slate-900 text-slate-400 hover:text-white']"
              >
                🔵 Nuevos ({{ bulkAnalyzedGames.filter(i => i.isNewGame).length }})
              </button>
            </div>

            <!-- Búsqueda Inteligente por Título, Precio o Web Scrapeada -->
            <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
              <input 
                v-model="bulkSearchQuery" 
                type="text" 
                placeholder="Buscar título, precio ($42.500) o web..."
                class="bg-slate-900 border border-slate-700 rounded-xl px-3 py-1.5 text-xs text-white outline-none focus:border-indigo-400 w-56 sm:w-64"
              >
              <button 
                type="button" 
                @click="toggleAllBulkSelection(true)"
                class="px-2.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold transition cursor-pointer"
              >
                ✓ Todos
              </button>
              <button 
                type="button" 
                @click="toggleAllBulkSelection(false)"
                class="px-2.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold transition cursor-pointer"
              >
                ✕ Ninguno
              </button>
              <button 
                type="button" 
                @click="bulkLoaderStep = 'input'"
                class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold transition cursor-pointer"
              >
                ✏️ Editar Lista
              </button>
            </div>
          </div>

          <!-- Tabla de Resultados, Similitud y Comparativa con Webs Scrapeadas -->
          <div class="overflow-x-auto rounded-2xl border border-slate-800 bg-[#020617]">
            <table class="w-full text-left text-xs text-slate-300">
              <thead class="bg-slate-950 text-slate-400 font-bold uppercase text-[10px] tracking-wider border-b border-slate-800">
                <tr>
                  <th class="p-3 w-10 text-center">
                    <input 
                      type="checkbox" 
                      :checked="selectedBulkCount === bulkAnalyzedGames.length && bulkAnalyzedGames.length > 0" 
                      @change="(e) => toggleAllBulkSelection(e.target.checked)"
                      class="rounded accent-indigo-600"
                    >
                  </th>
                  <th class="p-3">Título en Lista & Precio Lista</th>
                  <th class="p-3">Similitud Inteligente</th>
                  <th class="p-3">Juego en Catálogo & Webs Scrapeadas</th>
                  <th class="p-3">Diferencia de Precios</th>
                  <th class="p-3 text-right text-emerald-400">PVP Público & Neto</th>
                  <th class="p-3 text-right">Botón Proveedor</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-850">
                <tr 
                  v-for="item in filteredAnalyzedBulkGames" 
                  :key="item.id"
                  :class="['hover:bg-slate-900/60 transition-colors', !item.selected ? 'opacity-40' : '']"
                >
                  <td class="p-3 text-center">
                    <input type="checkbox" v-model="item.selected" class="rounded accent-indigo-600">
                  </td>

                  <!-- Título Original y Precio en la lista -->
                  <td class="p-3">
                    <div class="font-bold text-white text-xs">{{ item.rawTitle }}</div>
                    <div class="flex items-center gap-2 mt-1 flex-wrap">
                      <span class="text-[9px] font-mono px-1.5 py-0.2 rounded bg-indigo-500/20 text-indigo-300">{{ item.platform }}</span>
                      <span class="text-xs font-mono font-black text-emerald-400">${{ item.newWholesaleCost.toLocaleString() }} Lista</span>
                      <span class="text-[10px] text-slate-500 font-mono">L{{ item.lineNumber }}</span>
                    </div>
                  </td>

                  <!-- Estado de Similitud -->
                  <td class="p-3">
                    <span 
                      v-if="item.matchStatus === 'exact'" 
                      class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30"
                    >
                      🟢 100% Exacto
                    </span>
                    <span 
                      v-else-if="item.matchStatus === 'high'" 
                      class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20"
                    >
                      🟢 {{ item.similarityScore }}% Coincidencia
                    </span>
                    <span 
                      v-else-if="item.matchStatus === 'partial'" 
                      class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30"
                    >
                      🟡 {{ item.similarityScore }}% Cercano
                    </span>
                    <span 
                      v-else 
                      class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-500/20 text-blue-300 border border-blue-500/30"
                    >
                      🔵 Nuevo Juego
                    </span>
                    <span class="text-[9px] text-slate-500 block mt-0.5">{{ item.similarityReason }}</span>
                  </td>

                  <!-- Juego Asociado + Webs Scrapeadas -->
                  <td class="p-3 min-w-[230px]">
                    <div v-if="item.matchedGame" class="space-y-1">
                      <strong class="text-white text-xs block line-clamp-1">{{ item.matchedGame.title }}</strong>
                      <div v-if="item.scrapedProviders && item.scrapedProviders.length > 0" class="flex flex-wrap gap-1">
                        <span 
                          v-for="sp in item.scrapedProviders" 
                          :key="sp.key"
                          :class="[
                            'text-[9px] font-mono px-1.5 py-0.5 rounded border',
                            sp.costPrimary === item.lowestScrapedCost
                              ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40 font-bold'
                              : 'bg-slate-900 text-slate-300 border-slate-700'
                          ]"
                        >
                          {{ sp.shortName }}: ${{ (sp.costPrimary || 0).toLocaleString() }}
                        </span>
                      </div>
                      <span v-else class="text-[10px] text-slate-400 font-mono block">
                        Costo base: ${{ (item.currentCost || 0).toLocaleString() }} · PVP: ${{ (item.currentPvp || 0).toLocaleString() }}
                      </span>
                    </div>
                    <div v-else class="text-blue-300 text-xs italic">
                      + Se creará como juego nuevo con portada oficial PS Store
                    </div>
                  </td>

                  <!-- Diferencia de Precios (Lista vs Webs Scrapeadas) -->
                  <td class="p-3 font-mono">
                    <div v-if="item.isCheaperInList" class="space-y-0.5">
                      <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 font-black text-[11px]">
                        💰 Ahorras -${{ Math.abs(item.diffVsLowestScraped).toLocaleString() }} (-{{ item.diffPercentVsLowest }}%)
                      </span>
                      <span class="text-[9px] text-slate-400 block">
                        Mejor que {{ item.cheapestScrapedProviderName }} (${{ (item.lowestScrapedCost || 0).toLocaleString() }})
                      </span>
                    </div>
                    <div v-else-if="item.isCheaperInWebs" class="space-y-0.5">
                      <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg bg-amber-500/20 text-amber-300 border border-amber-500/40 font-black text-[11px]">
                        ⚠️ +${{ item.diffVsLowestScraped.toLocaleString() }} (+{{ item.diffPercentVsLowest }}%)
                      </span>
                      <span class="text-[9px] text-amber-200/80 block">
                        🏆 {{ item.cheapestScrapedProviderName }} más barato (${{ (item.lowestScrapedCost || 0).toLocaleString() }})
                      </span>
                    </div>
                    <div v-else-if="!item.isNewGame && item.lowestScrapedCost > 0" class="text-[10px] text-slate-400">
                      ⚖️ Igualado con web (${{ (item.lowestScrapedCost || 0).toLocaleString() }})
                    </div>
                    <div v-else class="text-[10px] text-blue-300">
                      🔹 Sin referencia previa
                    </div>
                  </td>

                  <!-- PVP Calculado con Margen de Tienda & Neto -->
                  <td class="p-3 text-right font-mono">
                    <strong class="text-emerald-400 text-sm font-black block">${{ item.proposedPvp.toLocaleString() }}</strong>
                    <span class="text-[9px] text-slate-400 block">
                      Rev: <span class="text-purple-300">+${{ item.proposedResellerProfit.toLocaleString() }}</span> · Neto: <span class="text-teal-300 font-bold">+${{ item.proposedStoreNet.toLocaleString() }}</span>
                    </span>
                  </td>

                  <!-- Botón Proveedor en Cada Fila -->
                  <td class="p-3 text-right">
                    <button 
                      type="button" 
                      @click="openProviderModalFromBulk(item)"
                      class="px-3 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-black text-xs transition inline-flex items-center gap-1 shadow cursor-pointer active:scale-95"
                      title="Ver cotizaciones scrapeadas, enlace del proveedor o contactar por WhatsApp"
                    >
                      <span>🏢 Proveedor</span>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Barra Inferior de Acción y Guardado -->
          <div class="bg-[#020617] border border-slate-800 p-6 rounded-3xl flex flex-col sm:flex-row items-center justify-between gap-4">
            <div>
              <h5 class="text-base font-black text-white">¿Listo para convenir los precios con {{ bulkProviderConfig.name }}?</h5>
              <p class="text-xs text-slate-400">
                Se actualizarán <strong>{{ selectedBulkCount }} videojuegos seleccionados</strong> con sus nuevos costos mayoristas, márgenes de ganancia y comisiones de revendedor.
              </p>
            </div>

            <div class="flex items-center gap-3 shrink-0">
              <button 
                type="button" 
                @click="bulkLoaderStep = 'input'"
                class="px-4 py-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs transition cursor-pointer"
              >
                Volver
              </button>
              <button 
                type="button" 
                @click="applyBulkBatchToCatalog" 
                :disabled="isApplyingBulkBatch || selectedBulkCount === 0"
                class="px-6 py-3 rounded-xl bg-gradient-to-r from-emerald-600 via-teal-600 to-indigo-600 hover:from-emerald-500 hover:to-indigo-500 disabled:opacity-40 text-white font-black text-xs transition shadow-lg shadow-emerald-600/30 flex items-center gap-2 cursor-pointer"
              >
                <span>🚀</span>
                <span>{{ isApplyingBulkBatch ? 'Aplicando Precios al Catálogo...' : `Convenir y Aplicar Precios (${selectedBulkCount} Juegos)` }}</span>
              </button>
            </div>
          </div>
        </div>

        <!-- PASO 3: Estado Completado -->
        <div v-else-if="bulkLoaderStep === 'completed'" class="bg-[#020617] border border-emerald-500/30 p-8 rounded-3xl text-center space-y-4 max-w-xl mx-auto">
          <span class="text-5xl block">🎉</span>
          <h4 class="text-xl font-black text-white">¡Lista de Precios Convenida con Éxito!</h4>
          <p class="text-xs text-slate-300 leading-relaxed">
            Los precios acordados con <strong>{{ bulkProviderConfig.name }}</strong> se aplicaron al catálogo general de videojuegos. Los precios de venta al público (PVP) y las comisiones de revendedor ya están vigentes y sincronizados en la nube.
          </p>
          <div class="flex flex-wrap justify-center gap-3 pt-3">
            <button 
              type="button" 
              @click="gamingAdminSubTab = 'comparison'"
              class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-black text-xs transition shadow cursor-pointer"
            >
              Ver Catálogo en Comparador →
            </button>
            <button 
              type="button" 
              @click="bulkLoaderStep = 'input'; rawGamingListText = ''"
              class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs transition cursor-pointer"
            >
              Cargar Otra Lista
            </button>
          </div>
        </div>
      </div>

      <div v-else-if="gamingAdminSubTab === 'argentinaplay'" class="space-y-4">
        <div class="p-4 bg-blue-950/30 border border-blue-500/30 rounded-2xl flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
          <div class="flex items-center gap-3">
            <span class="text-3xl">🇦🇷</span>
            <div>
              <h3 class="text-base font-black text-white">Catálogo de Argentina Play (argentinaplay.com.ar)</h3>
              <p class="text-xs text-slate-400">Proveedor especializado en cuentas de PlayStation Network para Argentina con entrega digital.</p>
            </div>
          </div>
          <a 
            href="https://www.argentinaplay.com.ar/" 
            target="_blank" 
            class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-black text-xs transition flex items-center gap-1.5 shadow"
          >
            <span>🌐 Abrir argentinaplay.com.ar ↗</span>
          </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <div v-for="game in paginatedGamingForAdmin" :key="game.id" class="bg-[#020617] border border-blue-500/20 rounded-2xl p-4 space-y-3">
            <div class="flex gap-3 items-center">
              <img :src="game.image" :alt="game.title" class="w-14 h-14 object-cover rounded-xl border border-slate-700 shrink-0" @error="(e) => e.target.src = 'https://images.unsplash.com/photo-1606813907291-d86efa9b94db?w=800&auto=format&fit=crop&q=80'">
              <div class="min-w-0 flex-1">
                <h4 class="font-black text-white text-xs truncate">{{ game.title }}</h4>
                <div class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                  <span class="text-[10px] text-blue-400 font-mono font-bold">{{ game.platform }}</span>
                  <span class="text-[10px] text-slate-400 font-semibold">{{ game.category }}</span>
                  <span v-if="game.subcategory" class="text-[9px] text-blue-300 font-medium">({{ game.subcategory }})</span>
                </div>
              </div>
            </div>
            <div class="grid grid-cols-2 gap-2 text-xs font-mono bg-slate-900/80 p-2.5 rounded-xl border border-slate-800">
              <div>
                <span class="text-[10px] text-slate-400 block">Costo Primaria:</span>
                <strong class="text-white">${{ game.providers?.argentinaplay?.primaryPrice?.toLocaleString() }}</strong>
              </div>
              <div>
                <span class="text-[10px] text-slate-400 block">Costo Secundaria:</span>
                <strong class="text-blue-300">${{ game.providers?.argentinaplay?.secondaryPrice?.toLocaleString() }}</strong>
              </div>
            </div>
            <div class="flex gap-2">
              <button type="button" @click="openGamingPriceEdit(game)" class="flex-1 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold transition">
                ✏️ Editar
              </button>
              <a href="https://www.argentinaplay.com.ar/" target="_blank" class="py-1.5 px-3 rounded-lg bg-blue-600/20 hover:bg-blue-600 text-blue-300 hover:text-white text-xs font-bold transition flex items-center justify-center">
                Comprar ↗
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- ==================== SUB-VISTA 3: CATÁLOGO JUEGOS DIGITALES ARGENTINA (JDA) ==================== -->
      <div v-else-if="gamingAdminSubTab === 'juegosdigitalesargentina'" class="space-y-4">
        <div class="p-4 bg-amber-950/30 border border-amber-500/30 rounded-2xl flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
          <div class="flex items-center gap-3">
            <span class="text-3xl">🎮</span>
            <div>
              <h3 class="text-base font-black text-white">Catálogo de Juegos Digitales Argentina (juegosdigitalesargentina.com)</h3>
              <p class="text-xs text-slate-400">Proveedor líder en slots primarios y secundarios para PS4 y PlayStation 5.</p>
            </div>
          </div>
          <a 
            href="https://juegosdigitalesargentina.com/" 
            target="_blank" 
            class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs transition flex items-center gap-1.5 shadow"
          >
            <span>🌐 Abrir juegosdigitalesargentina.com ↗</span>
          </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <div v-for="game in paginatedGamingForAdmin" :key="game.id" class="bg-[#020617] border border-amber-500/20 rounded-2xl p-4 space-y-3">
            <div class="flex gap-3 items-center">
              <img :src="game.image" :alt="game.title" class="w-14 h-14 object-cover rounded-xl border border-slate-700 shrink-0" @error="(e) => e.target.src = 'https://images.unsplash.com/photo-1606813907291-d86efa9b94db?w=800&auto=format&fit=crop&q=80'">
              <div class="min-w-0 flex-1">
                <h4 class="font-black text-white text-xs truncate">{{ game.title }}</h4>
                <div class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                  <span class="text-[10px] text-amber-400 font-mono font-bold">{{ game.platform }}</span>
                  <span class="text-[10px] text-slate-400 font-semibold">{{ game.category }}</span>
                  <span v-if="game.subcategory" class="text-[9px] text-amber-300 font-medium">({{ game.subcategory }})</span>
                </div>
              </div>
            </div>
            <div class="grid grid-cols-2 gap-2 text-xs font-mono bg-slate-900/80 p-2.5 rounded-xl border border-slate-800">
              <div>
                <span class="text-[10px] text-slate-400 block">Costo Primaria:</span>
                <strong class="text-white">${{ game.providers?.juegosdigitalesargentina?.primaryPrice?.toLocaleString() }}</strong>
              </div>
              <div>
                <span class="text-[10px] text-slate-400 block">Costo Secundaria:</span>
                <strong class="text-amber-300">${{ game.providers?.juegosdigitalesargentina?.secondaryPrice?.toLocaleString() }}</strong>
              </div>
            </div>
            <div class="flex gap-2">
              <button type="button" @click="openGamingPriceEdit(game)" class="flex-1 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold transition">
                ✏️ Editar
              </button>
              <a href="https://juegosdigitalesargentina.com/" target="_blank" class="py-1.5 px-3 rounded-lg bg-amber-500/20 hover:bg-amber-500 text-amber-300 hover:text-slate-950 text-xs font-bold transition flex items-center justify-center">
                Comprar ↗
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- ==================== SUB-VISTA: ADMINISTRAR SITIOS WEB & PROVEEDORES ==================== -->
      <div v-else-if="gamingAdminSubTab === 'sites'" class="space-y-6">
        <!-- Explicación del Regimen Multi-Sitio Mayor Costo PVP -->
        <div class="bg-gradient-to-br from-indigo-950/60 via-slate-900 to-amber-950/30 border border-indigo-500/30 rounded-3xl p-6 flex flex-col lg:flex-row justify-between gap-4">
          <div class="space-y-1.5">
            <div class="flex items-center gap-2 flex-wrap">
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-amber-500/20 text-amber-300 border border-amber-500/30 uppercase tracking-wider">
                Control Maestro Multi-Sitio
              </span>
              <span class="text-xs font-mono text-slate-400">
                {{ adminStore.gamingPricingSummary?.activeSitesCount || 0 }} activos · {{ adminStore.gamingPricingSummary?.pvpSitesCount || 0 }} fijan Mayor Costo PVP
              </span>
            </div>
            <h3 class="text-xl font-black text-white">Sitios Web Scrapeados & Proveedores Agregados</h3>
            <p class="text-xs sm:text-sm text-slate-300 max-w-3xl leading-relaxed">
              Administra todas las fuentes agregadas y scrapeadas (ArgPlay, JDA, Digital Games Shop, Tu Tienda Gamer, Fusion Games, Lista .TXT y tus nuevos sitios). El sistema toma automáticamente el <strong class="text-amber-300">MAYOR COSTO</strong> entre los sitios habilitados para calcular el <strong class="text-emerald-300">PVP visible público</strong> (siempre bajo marca oficial StreamePro / PS Store) y te marca el proveedor más económico para comprar.
            </p>
          </div>
          <div class="flex items-center gap-2 shrink-0">
            <button
              type="button"
              @click="gamingAdminSubTab = 'comparison'; onlyHighestCostProvider = false"
              class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-black transition shadow cursor-pointer"
            >
              ⚖️ Comparar Todos los Sitios
            </button>
          </div>
        </div>

        <!-- Formulario para Agregar Nuevo Sitio Web / Proveedor -->
        <div class="bg-[#020617] border border-teal-500/30 rounded-3xl p-5 sm:p-6 space-y-4">
          <h4 class="text-sm font-black text-white flex items-center gap-2">
            <span>➕</span>
            <span>Agregar Nuevo Sitio Web o Proveedor Mayorista</span>
          </h4>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div>
              <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Nombre del Sitio / Proveedor *</label>
              <input v-model="newSiteForm.name" type="text" placeholder="Ej: Eneba / G2A / Proveedor Norte" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-xs text-white font-bold outline-none focus:border-teal-500">
            </div>
            <div>
              <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Alias Corto</label>
              <input v-model="newSiteForm.shortName" type="text" placeholder="Ej: Eneba / Norte" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-xs text-white outline-none focus:border-teal-500">
            </div>
            <div>
              <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Sitio Web (URL)</label>
              <input v-model="newSiteForm.url" type="text" placeholder="https://..." class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-xs text-white outline-none focus:border-teal-500">
            </div>
            <div>
              <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">WhatsApp Contacto (Opcional)</label>
              <input v-model="newSiteForm.whatsapp" type="text" placeholder="+54 9 11 ..." class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-xs text-white outline-none focus:border-teal-500">
            </div>
          </div>

          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-1">
            <label class="flex items-center gap-2 text-xs text-slate-200 font-semibold cursor-pointer">
              <input v-model="newSiteForm.includeInPvp" type="checkbox" class="accent-amber-500 w-4 h-4 rounded">
              <span>📈 Incluir costos de este sitio al calcular el <strong>MAYOR COSTO</strong> para PVP en tienda pública</span>
            </label>

            <button
              type="button"
              @click="handleAddGamingSite"
              class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-500 hover:to-emerald-500 text-white font-black text-xs shadow transition cursor-pointer shrink-0"
            >
              ➕ Guardar Sitio Proveedor
            </button>
          </div>
        </div>

        <!-- Grid de Todos los Sitios Scrapeados y Agregados -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
          <div
            v-for="site in (adminStore.gamingSitesStats || [])"
            :key="site.key"
            :class="[
              'bg-[#020617] border rounded-3xl p-5 flex flex-col justify-between space-y-4 transition-all',
              site.enabled === false ? 'border-slate-800/60 opacity-60' : 'border-slate-800 hover:border-indigo-500/40 shadow-lg'
            ]"
          >
            <div class="space-y-3">
              <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                  <div class="flex items-center gap-2 flex-wrap">
                    <span :class="['text-xs font-black px-2.5 py-1 rounded-xl border', site.tagColor || 'bg-indigo-500/20 text-indigo-300 border-indigo-500/30']">
                      {{ site.badge || site.name }}
                    </span>
                    <span v-if="!site.isDefault" class="text-[9px] bg-teal-500/20 text-teal-300 px-1.5 py-0.5 rounded font-bold border border-teal-500/30">Custom</span>
                  </div>
                  <h4 class="text-base font-black text-white mt-2 truncate">{{ site.name }}</h4>
                  <p class="text-[11px] text-slate-400 line-clamp-1" :title="site.notes">{{ site.notes || 'Sitio proveedor gaming' }}</p>
                </div>

                <div class="flex items-center gap-1 shrink-0">
                  <button
                    type="button"
                    @click="editingSiteKey === site.key ? (editingSiteKey = null) : startEditGamingSite(site)"
                    class="px-2.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold cursor-pointer"
                    title="Editar datos del sitio"
                  >
                    ✏️
                  </button>
                  <button
                    v-if="!site.isDefault"
                    type="button"
                    @click="handleDeleteGamingSite(site)"
                    class="px-2.5 py-1.5 rounded-xl bg-rose-500/20 hover:bg-rose-500 text-rose-300 hover:text-white text-xs font-bold cursor-pointer"
                    title="Eliminar sitio custom"
                  >
                    🗑️
                  </button>
                </div>
              </div>

              <!-- Editor Inline si está seleccionado -->
              <div v-if="editingSiteKey === site.key" class="p-3 bg-slate-900 rounded-2xl border border-indigo-500/40 space-y-2 text-xs">
                <input v-model="editingSiteForm.name" type="text" placeholder="Nombre" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-white font-bold">
                <div class="grid grid-cols-2 gap-2">
                  <input v-model="editingSiteForm.shortName" type="text" placeholder="Alias corto" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-white">
                  <input v-model="editingSiteForm.whatsapp" type="text" placeholder="WhatsApp" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-white">
                </div>
                <input v-model="editingSiteForm.url" type="text" placeholder="https://..." class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-white">
                <div class="flex justify-end gap-2 pt-1">
                  <button type="button" @click="editingSiteKey = null" class="px-3 py-1 rounded-lg bg-slate-800 text-slate-300 font-bold cursor-pointer">Cancelar</button>
                  <button type="button" @click="saveEditGamingSite" class="px-3 py-1 rounded-lg bg-emerald-600 text-white font-black cursor-pointer">Guardar</button>
                </div>
              </div>

              <!-- Estadísticas de Catálogo y Arbitraje de este Sitio -->
              <div class="grid grid-cols-2 gap-2 text-xs font-mono">
                <div class="p-2.5 rounded-xl bg-slate-900/70 border border-slate-800">
                  <span class="text-[9px] text-slate-400 uppercase block">Títulos Cotizados</span>
                  <strong class="text-sm font-black text-white">{{ site.count }}</strong>
                </div>
                <div class="p-2.5 rounded-xl bg-slate-900/70 border border-slate-800">
                  <span class="text-[9px] text-slate-400 uppercase block">Costo Prom. Prim.</span>
                  <strong class="text-sm font-black text-indigo-300">${{ (site.avgCostPrimary || 0).toLocaleString() }}</strong>
                </div>
                <div class="p-2.5 rounded-xl bg-amber-950/20 border border-amber-500/30">
                  <span class="text-[9px] text-amber-300 uppercase block">📈 Fija Mayor PVP</span>
                  <strong class="text-sm font-black text-amber-400">{{ site.highestLeaderCount }} juegos</strong>
                </div>
                <div class="p-2.5 rounded-xl bg-emerald-950/20 border border-emerald-500/30">
                  <span class="text-[9px] text-emerald-300 uppercase block">🏆 Más Barato Compra</span>
                  <strong class="text-sm font-black text-emerald-400">{{ site.cheapestBuyerCount }} juegos</strong>
                </div>
              </div>

              <!-- Toggles de Activación y Participación en PVP -->
              <div class="flex flex-wrap gap-2 pt-1">
                <button
                  type="button"
                  @click="toggleSiteEnabled(site)"
                  :class="[
                    'flex-1 py-1.5 px-2.5 rounded-xl text-[11px] font-bold border transition cursor-pointer',
                    site.enabled !== false
                      ? 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30 hover:bg-emerald-500/25'
                      : 'bg-rose-500/15 text-rose-300 border-rose-500/30 hover:bg-rose-500/25'
                  ]"
                >
                  {{ site.enabled !== false ? '✓ Sitio Activo' : '⛔ Desactivado' }}
                </button>

                <button
                  type="button"
                  @click="toggleSiteIncludeInPvp(site)"
                  :class="[
                    'flex-1 py-1.5 px-2.5 rounded-xl text-[11px] font-bold border transition cursor-pointer',
                    site.includeInPvp !== false
                      ? 'bg-amber-500/15 text-amber-300 border-amber-500/30 hover:bg-amber-500/25'
                      : 'bg-slate-800 text-slate-400 border-slate-700 hover:text-white'
                  ]"
                  title="Define si los costos de este sitio suben el Mayor Costo para PVP público"
                >
                  {{ site.includeInPvp !== false ? '📈 Cuenta p/ PVP' : '🛒 Solo Compra' }}
                </button>
              </div>
            </div>

            <!-- Pie de Tarjeta -->
            <div class="flex items-center justify-between gap-2 pt-3 border-t border-slate-800">
              <button
                type="button"
                @click="jumpToSiteInComparison(site.key)"
                class="flex-1 py-2 px-3 rounded-xl bg-indigo-600/20 hover:bg-indigo-600 text-indigo-300 hover:text-white border border-indigo-500/30 text-xs font-bold transition cursor-pointer"
              >
                🔍 Ver {{ site.count }} juegos
              </button>
              <a
                v-if="site.url"
                :href="site.url"
                target="_blank"
                class="py-2 px-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-white border border-slate-700 text-xs font-bold transition"
              >
                Web ↗
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- ==================== SUB-VISTA 4: PEDIDOS GAMER & PROVEEDOR ELEGIDO ==================== -->
      <div v-else-if="gamingAdminSubTab === 'orders'" class="space-y-6">
        
        <!-- ENCABEZADO Y ACCIONES RÁPIDAS -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-[#020617] p-5 rounded-2xl border border-slate-800">
          <div>
            <div class="flex items-center gap-2">
              <span class="text-xl">🎮📦</span>
              <h3 class="text-lg font-black text-white">Centro de Despacho & Control de Ventas Gamer</h3>
              <span class="text-[10px] font-mono font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 px-2 py-0.5 rounded-full">
                {{ adminStore.gamingSalesStats?.totalOrders || adminStore.gamingOrders.length }} Total
              </span>
            </div>
            <p class="text-xs text-slate-400 mt-1">
              Control de facturación, cálculo de márgenes netos, verificación de comprobantes y entrega de licencias vía WhatsApp.
            </p>
          </div>

          <div class="flex items-center gap-2">
            <button 
              type="button"
              @click="manualCloudSync"
              :disabled="isSyncingCloud"
              class="bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white font-bold text-xs px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 cursor-pointer shadow"
              title="Sincronizar pedidos gamer con Firebase"
            >
              <span>{{ isSyncingCloud ? '⏳ Sincronizando...' : '🔄 Sincronizar Nube' }}</span>
            </button>
          </div>
        </div>

        <!-- KPI METRICS GRID: DATOS FINANCIEROS Y OPERATIVOS GAMER -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
          <!-- Facturación Total -->
          <div class="bg-[#020617] p-4 rounded-2xl border border-emerald-500/30 space-y-1">
            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider block">Facturado Total</span>
            <span class="text-xl font-black text-emerald-400 font-mono block">
              ${{ (adminStore.gamingSalesStats?.totalBilled || 0).toLocaleString() }}
            </span>
            <span class="text-[10px] text-slate-400">ARS cobrados</span>
          </div>

          <!-- Margen / Ganancia Estimada -->
          <div class="bg-[#020617] p-4 rounded-2xl border border-purple-500/30 space-y-1">
            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider block">Ganancia / Margen</span>
            <span class="text-xl font-black text-purple-400 font-mono block">
              +${{ (adminStore.gamingSalesStats?.totalProfit || 0).toLocaleString() }}
            </span>
            <span class="text-[10px] text-purple-300/80">Margen neto est.</span>
          </div>

          <!-- Por Despachar / Pendientes -->
          <div class="bg-[#020617] p-4 rounded-2xl border border-amber-500/30 space-y-1">
            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider block">Por Entregar</span>
            <span class="text-2xl font-black text-amber-400 font-mono block">
              {{ adminStore.gamingSalesStats?.pendingCount || 0 }}
            </span>
            <span class="text-[10px] text-amber-300/80">Esperando entrega</span>
          </div>

          <!-- Entregados -->
          <div class="bg-[#020617] p-4 rounded-2xl border border-sky-500/30 space-y-1">
            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider block">Entregados</span>
            <span class="text-2xl font-black text-sky-400 font-mono block">
              {{ adminStore.gamingSalesStats?.dispatchedCount || 0 }}
            </span>
            <span class="text-[10px] text-sky-300/80">Despachados</span>
          </div>

          <!-- Licencias Primarias vs Secundarias -->
          <div class="bg-[#020617] p-4 rounded-2xl border border-slate-800 space-y-1">
            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider block">Tipo de Licencia</span>
            <div class="text-sm font-black text-white font-mono flex items-center gap-1.5 pt-1">
              <span class="text-amber-300">⭐️ {{ adminStore.gamingSalesStats?.primaryCount || 0 }}</span>
              <span class="text-slate-500">/</span>
              <span class="text-indigo-300">⚡️ {{ adminStore.gamingSalesStats?.secondaryCount || 0 }}</span>
            </div>
            <span class="text-[10px] text-slate-400">Primarias / Secundarias</span>
          </div>

          <!-- Desglose por Proveedor -->
          <div class="bg-[#020617] p-4 rounded-2xl border border-slate-800 space-y-1">
            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider block">Por Proveedor</span>
            <div class="text-xs font-black text-white font-mono flex items-center gap-1 pt-1">
              <span class="text-blue-300">🇦🇷 {{ adminStore.gamingSalesStats?.argPlayCount || 0 }}</span>
              <span class="text-slate-500">|</span>
              <span class="text-amber-300">🎮 {{ adminStore.gamingSalesStats?.jdaCount || 0 }}</span>
            </div>
            <span class="text-[10px] text-slate-400">Arg Play / JDA</span>
          </div>
        </div>

        <!-- BANDEJA DE CONSULTAS Y SOLICITUDES MANUALES DE JUEGOS (CLIENTES / REVENDEDORES) -->
        <div v-if="gamingInquiryTicketsForAdmin.length > 0" class="bg-[#020617] border border-amber-500/35 rounded-2xl p-4 sm:p-5 space-y-3">
          <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2">
              <span class="text-lg">📩</span>
              <div>
                <h4 class="text-sm font-black text-amber-300">
                  Consultas & Solicitudes Manuales de Juegos ({{ openGameRequestCount }} pendientes)
                </h4>
                <p class="text-[11px] text-slate-400">
                  Pedidos o consultas enviadas por Clientes y Revendedores Oficiales. Contesta directo o pásalo al POS con 1 click.
                </p>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-80 overflow-y-auto custom-scrollbar">
            <div
              v-for="t in gamingInquiryTicketsForAdmin"
              :key="t.id"
              :class="['p-3.5 rounded-xl border text-xs space-y-2', t.status === 'Resuelto' ? 'bg-slate-950/60 border-emerald-500/25 opacity-80' : 'bg-slate-950 border-amber-500/35']"
            >
              <div class="flex items-center justify-between gap-2 flex-wrap">
                <div class="flex items-center gap-1.5 flex-wrap">
                  <span class="font-mono font-bold text-indigo-300">#{{ t.id }}</span>
                  <span :class="['text-[10px] font-black px-2 py-0.5 rounded-full', t.sourceRole === 'reseller' ? 'bg-purple-500/20 text-purple-300 border border-purple-500/30' : 'bg-sky-500/20 text-sky-300 border border-sky-500/30']">
                    {{ t.sourceRole === 'reseller' ? '🏪 Revendedor' : '👤 Cliente' }}
                  </span>
                  <span class="font-black text-white">{{ t.clientName }}</span>
                  <span v-if="t.phone" class="text-[10px] text-cyan-300 font-mono">({{ t.phone }})</span>
                </div>
                <span :class="['text-[10px] px-2 py-0.5 rounded-full font-bold', t.status === 'Resuelto' ? 'bg-emerald-500/20 text-emerald-300' : 'bg-amber-500/20 text-amber-300']">
                  {{ t.status }}
                </span>
              </div>

              <div class="flex flex-wrap items-center gap-1.5 text-[11px]">
                <span class="font-black text-amber-300">🎮 {{ t.requestedGame || t.subject }}</span>
                <span v-if="t.requestedPlatform" class="px-1.5 py-0.5 rounded bg-slate-900 border border-slate-700 text-slate-300 font-mono text-[10px]">{{ t.requestedPlatform }}</span>
                <span v-if="t.requestedLicense" class="px-1.5 py-0.5 rounded bg-indigo-950/60 border border-indigo-500/30 text-indigo-200 font-bold text-[10px]">{{ t.requestedLicense }}</span>
              </div>

              <p class="text-slate-300 bg-slate-900/70 p-2 rounded-lg">{{ t.message }}</p>

              <div v-if="t.status !== 'Resuelto'" class="flex flex-wrap gap-1.5 pt-1">
                <input
                  v-model="supportTicketReplies[t.id]"
                  type="text"
                  placeholder="Responder cotización o disponibilidad al cliente/revendedor..."
                  class="flex-1 min-w-[140px] bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-1.5 text-[11px] text-white outline-none focus:border-amber-400"
                >
                <button
                  type="button"
                  @click="handleResolveSupportTicket(t)"
                  class="px-2.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[11px] cursor-pointer"
                >
                  ✅ Responder
                </button>
                <button
                  v-if="t.phone"
                  type="button"
                  @click="sendTicketReplyWhatsApp(t)"
                  class="px-2.5 py-1.5 rounded-lg bg-sky-600/20 hover:bg-sky-600 text-sky-300 hover:text-white border border-sky-500/30 font-bold text-[11px] cursor-pointer"
                  title="Responder directo por WhatsApp"
                >
                  💬 WA
                </button>
                <button
                  type="button"
                  @click="openSupportTicketInPos(t)"
                  class="px-2.5 py-1.5 rounded-lg bg-pink-600/20 hover:bg-pink-600 text-pink-300 hover:text-white border border-pink-500/30 font-bold text-[11px] cursor-pointer"
                  title="Cargar en POS Venta Rápida para cobrar o cotizar"
                >
                  🛒 POS
                </button>
              </div>
              <div v-else class="flex flex-wrap items-center justify-between gap-2 pt-1">
                <p v-if="t.adminReply" class="text-[11px] text-emerald-300 italic">
                  Respuesta Admin: {{ t.adminReply }}
                </p>
                <div class="flex gap-1.5 ml-auto">
                  <button
                    v-if="t.phone"
                    type="button"
                    @click="sendTicketReplyWhatsApp(t)"
                    class="px-2 py-1 rounded-lg bg-sky-600/20 hover:bg-sky-600 text-sky-300 hover:text-white border border-sky-500/30 font-bold text-[10px] cursor-pointer"
                  >
                    💬 Reenviar WA
                  </button>
                  <button
                    type="button"
                    @click="openSupportTicketInPos(t)"
                    class="px-2 py-1 rounded-lg bg-pink-600/20 hover:bg-pink-600 text-pink-300 hover:text-white border border-pink-500/30 font-bold text-[10px] cursor-pointer"
                  >
                    🛒 POS
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- BARRA DE FILTROS Y BÚSQUEDA REACTIVA -->
        <div class="flex flex-col md:flex-row gap-3 bg-[#020617] p-4 rounded-2xl border border-slate-800">
          <div class="flex-1 relative">
            <span class="absolute left-3.5 top-2.5 text-slate-500 text-xs">🔍</span>
            <input 
              v-model="gamingOrderSearch" 
              type="text" 
              placeholder="Buscar por N° Orden, cliente, WhatsApp, videojuego o proveedor..." 
              class="w-full bg-slate-900 border border-slate-800 rounded-xl pl-9 pr-8 py-2 text-xs text-white placeholder-slate-500 outline-none focus:border-indigo-500 transition-colors"
            >
            <button 
              v-if="gamingOrderSearch" 
              @click="gamingOrderSearch = ''" 
              type="button" 
              class="absolute right-3 top-2.5 text-slate-400 hover:text-white text-xs cursor-pointer"
            >
              ✕
            </button>
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <!-- Filtro por Estado -->
            <select 
              v-model="gamingOrderStatusFilter" 
              class="bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white outline-none focus:border-indigo-500 cursor-pointer"
            >
              <option value="ALL">Todos los Estados ({{ adminStore.gamingOrders.length }})</option>
              <option value="PENDING">⏳ Pendientes ({{ adminStore.gamingSalesStats?.pendingCount || 0 }})</option>
              <option value="DISPATCHED">✅ Entregados ({{ adminStore.gamingSalesStats?.dispatchedCount || 0 }})</option>
            </select>

            <!-- Filtro por Proveedor -->
            <select 
              v-model="gamingOrderProviderFilter" 
              class="bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white outline-none focus:border-indigo-500 cursor-pointer"
            >
              <option value="ALL">Todos los Proveedores</option>
              <option v-for="site in (adminStore.gamingProviderSites || [])" :key="site.key" :value="site.key">
                {{ site.badge || site.name }}
              </option>
            </select>

            <!-- Filtro por Licencia -->
            <select 
              v-model="gamingOrderLicenseFilter" 
              class="bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white outline-none focus:border-indigo-500 cursor-pointer"
            >
              <option value="ALL">Todas las Licencias</option>
              <option value="primary">⭐️ Cuenta Primaria</option>
              <option value="secondary">⚡️ Cuenta Secundaria</option>
            </select>

            <!-- Reset -->
            <button 
              v-if="gamingOrderSearch || gamingOrderStatusFilter !== 'ALL' || gamingOrderProviderFilter !== 'ALL' || gamingOrderLicenseFilter !== 'ALL'"
              @click="gamingOrderSearch = ''; gamingOrderStatusFilter = 'ALL'; gamingOrderProviderFilter = 'ALL'; gamingOrderLicenseFilter = 'ALL'"
              type="button"
              class="px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold transition cursor-pointer"
              title="Limpiar filtros"
            >
              Limpiar
            </button>
          </div>
        </div>

        <!-- LISTA DE PEDIDOS GAMER -->
        <div v-if="adminStore.gamingOrders.length === 0" class="p-12 bg-[#020617] border border-slate-800 rounded-2xl text-center space-y-3">
          <span class="text-4xl block">🎮📦</span>
          <h4 class="text-base font-bold text-white">No hay pedidos de videojuegos registrados aún</h4>
          <p class="text-xs text-slate-400 max-w-md mx-auto">
            Cuando los clientes agreguen juegos digitales desde la tienda web o WhatsApp, aquí aparecerá el pedido con el proveedor asignado (Argentina Play o JDA), el cálculo de márgenes y los botones de compra y entrega en 1 clic.
          </p>
        </div>

        <div v-else-if="filteredGamingOrdersForAdmin.length === 0" class="p-10 bg-[#020617] border border-slate-800 border-dashed rounded-2xl text-center space-y-3">
          <span class="text-3xl block">🔍</span>
          <h4 class="text-sm font-bold text-white">No se encontraron pedidos gamer con los filtros seleccionados</h4>
          <p class="text-xs text-slate-400">Intenta cambiar el término de búsqueda o restablecer los filtros.</p>
          <button 
            type="button"
            @click="gamingOrderSearch = ''; gamingOrderStatusFilter = 'ALL'; gamingOrderProviderFilter = 'ALL'; gamingOrderLicenseFilter = 'ALL'"
            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition cursor-pointer"
          >
            Restablecer Filtros
          </button>
        </div>

        <div v-else class="space-y-4">
          <!-- Selector de Paginación Superior -->
          <div class="flex justify-between items-center text-xs text-slate-400 px-1">
            <span>Mostrando <strong>{{ paginatedGamingOrdersForAdmin.length }}</strong> de <strong>{{ filteredGamingOrdersForAdmin.length }}</strong> pedidos</span>
            <div class="flex items-center gap-2">
              <span>Por página:</span>
              <select v-model="gamingOrderPageSize" class="bg-slate-900 border border-slate-800 rounded-lg px-2 py-1 text-white text-xs outline-none">
                <option :value="15">15</option>
                <option :value="30">30</option>
                <option :value="60">60</option>
              </select>
            </div>
          </div>

          <!-- Cards de Pedidos Gamer -->
          <div class="grid grid-cols-1 gap-3.5">
            <div 
              v-for="order in paginatedGamingOrdersForAdmin" 
              :key="order.id"
              :class="[
                'bg-[#020617] border rounded-2xl p-4 sm:p-5 transition-all shadow-lg space-y-3.5',
                order.status === 'Dispatched' || order.status === 'Entregado' 
                  ? 'border-emerald-500/30 hover:border-emerald-500/50' 
                  : 'border-amber-500/40 hover:border-amber-400/60 shadow-amber-500/5'
              ]"
            >
              <!-- Fila Superior: ID, Estado, Canal y Fecha -->
              <div class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-slate-800/80">
                <div class="flex items-center gap-2.5">
                  <span class="font-mono font-bold text-xs text-indigo-400 bg-indigo-500/10 px-2 py-0.5 rounded-lg border border-indigo-500/20">
                    #{{ order.id }}
                  </span>
                  
                  <span 
                    :class="[
                      'text-[10px] font-black px-2.5 py-0.5 rounded-full flex items-center gap-1 cursor-pointer transition',
                      order.status === 'Dispatched' || order.status === 'Entregado'
                        ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 hover:bg-emerald-500/30' 
                        : 'bg-amber-500/20 text-amber-300 border border-amber-500/30 hover:bg-amber-500/30'
                    ]"
                    @click="handleToggleGamingOrderStatus(order)"
                    :title="'Click para cambiar estado a ' + (order.status === 'Dispatched' ? 'Pendiente' : 'Entregado')"
                  >
                    <span>{{ order.status === 'Dispatched' || order.status === 'Entregado' ? '✅ Entregado' : '⏳ Pendiente' }}</span>
                  </span>

                  <span v-if="order.resellerName" class="text-[10px] font-bold text-slate-400 bg-slate-900 px-2 py-0.5 rounded-md border border-slate-800">
                    🏪 {{ order.resellerName }}
                  </span>
                </div>

                <div class="flex items-center gap-2 text-xs text-slate-400">
                  <span v-if="order.createdAt || order.createdDate" class="font-mono text-[11px]">
                    🕒 {{ order.createdAt || order.createdDate }}
                  </span>
                  <button 
                    type="button"
                    @click="copyGamingOrderDetails(order)"
                    class="text-[11px] text-slate-400 hover:text-white px-2 py-1 rounded bg-slate-900 hover:bg-slate-800 border border-slate-800 transition flex items-center gap-1 cursor-pointer"
                    title="Copiar resumen del pedido"
                  >
                    📋 Copiar
                  </button>
                </div>
              </div>

              <!-- Fila Principal: Datos del Juego, Cliente y Precios -->
              <div class="grid grid-cols-1 md:grid-cols-12 gap-3.5 items-center">
                <!-- Info del Cliente y Juego (md:col-span-7) -->
                <div class="md:col-span-7 space-y-1.5">
                  <div class="flex items-center gap-2">
                    <span class="text-sm font-black text-white">{{ order.customerName || 'Cliente' }}</span>
                    <a 
                      v-if="order.customerPhone"
                      :href="'https://wa.me/' + formatWaPhone(order.customerPhone)" 
                      target="_blank" 
                      class="text-xs text-emerald-400 hover:underline flex items-center gap-0.5 font-mono"
                      title="Abrir chat de WhatsApp"
                    >
                      <span>💬 {{ order.customerPhone }}</span>
                    </a>
                  </div>

                  <div class="text-xs text-slate-300 flex flex-wrap items-center gap-2">
                    <span class="text-white font-black text-base">🎮 {{ order.gameTitle || order.platform }}</span>
                    <button 
                      type="button" 
                      @click="openAssignGameModal(order)"
                      class="text-[10px] bg-indigo-500/20 hover:bg-indigo-500/40 text-indigo-300 border border-indigo-500/30 px-2.5 py-0.5 rounded-lg font-bold transition flex items-center gap-1 cursor-pointer"
                      title="Editar o cambiar el nombre del juego"
                    >
                      ✏️ Editar Juego
                    </button>
                    <span :class="['px-2 py-0.5 rounded text-[11px] font-bold', (order.licenseType === 'primary' || (order.licenseLabel && order.licenseLabel.includes('Primaria'))) ? 'bg-amber-400/20 text-amber-300 border border-amber-400/30' : 'bg-indigo-400/20 text-indigo-300 border border-indigo-400/30']">
                      {{ order.licenseLabel || (order.licenseType === 'primary' ? '⭐️ Cuenta Primaria' : '⚡️ Cuenta Secundaria') }}
                    </span>
                  </div>

                  <div v-if="!order.gameTitle || order.gameTitle.includes('Pendiente') || order.gameTitle === 'Videojuego PS4 / PS5'" class="text-[11px] text-amber-300 bg-amber-500/10 border border-amber-500/30 p-2 rounded-xl flex flex-wrap items-center justify-between gap-2">
                    <span>⚠️ Nombre del juego pendiente de asignar.</span>
                    <button @click="openAssignGameModal(order)" type="button" class="underline font-bold text-amber-200 hover:text-white cursor-pointer">
                      Asignar Nombre del Juego Ahora →
                    </button>
                  </div>

                  <div class="flex flex-wrap items-center gap-2 text-xs pt-1">
                    <span class="text-slate-400">Proveedor de compra:</span>
                    <select
                      :value="order.fulfillmentProvider || 'argentinaplay'"
                      @change="handleQuickChangeGamingProvider(order, $event.target.value)"
                      class="bg-slate-900 border border-indigo-500/40 rounded-lg px-2 py-1 text-[11px] font-bold text-indigo-300 outline-none focus:border-indigo-400 cursor-pointer"
                      title="Cambiar sitio web donde comprar este pedido y recalcular costo"
                    >
                      <option v-for="site in (adminStore.gamingProviderSites || [])" :key="site.key" :value="site.key">
                        {{ site.badge || site.name }}
                      </option>
                    </select>
                  </div>

                  <!-- Credenciales Gamer Entregadas (si existen) -->
                  <div v-if="order.accountDetails && order.accountDetails.email" class="mt-2 p-2.5 rounded-xl bg-emerald-950/30 border border-emerald-500/30 text-[11px] space-y-1">
                    <div class="flex items-center justify-between gap-2">
                      <span class="font-black text-emerald-300 uppercase text-[10px]">🔑 Credenciales Despachadas</span>
                      <button
                        type="button"
                        @click="copyGamingOrderCredentials(order)"
                        class="text-[10px] bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-200 px-2 py-0.5 rounded font-bold cursor-pointer"
                      >
                        📋 Copiar Acceso
                      </button>
                    </div>
                    <p class="text-slate-200 font-mono truncate">Correo: <strong>{{ order.accountDetails.email }}</strong></p>
                    <p class="text-slate-200 font-mono">Clave: <strong>{{ order.accountDetails.pass || '-' }}</strong> <span v-if="order.accountDetails.pin" class="text-amber-300 ml-2">PIN/2FA: <strong>{{ order.accountDetails.pin }}</strong></span></p>
                    <p v-if="order.accountDetails.notes" class="text-slate-400 italic">{{ order.accountDetails.notes }}</p>
                  </div>
                </div>

                <!-- Desglose Financiero (md:col-span-5) -->
                <div class="md:col-span-5 bg-slate-950/70 p-3 rounded-xl border border-slate-800/80 grid grid-cols-3 gap-2 text-center">
                  <div>
                    <span class="text-[9px] uppercase font-bold text-slate-400 block">Cobrado</span>
                    <span class="text-xs sm:text-sm font-black text-emerald-400 font-mono block">
                      ${{ (order.clientPrice || order.totalAmount || 0).toLocaleString() }}
                    </span>
                    <span class="text-[9px] text-slate-500">ARS</span>
                  </div>

                  <div>
                    <span class="text-[9px] uppercase font-bold text-slate-400 block">Costo Prov.</span>
                    <span class="text-xs sm:text-sm font-bold text-slate-300 font-mono block">
                      ${{ (order.providerCost || 0).toLocaleString() }}
                    </span>
                    <span class="text-[9px] text-slate-500">ARS</span>
                  </div>

                  <div>
                    <span class="text-[9px] uppercase font-bold text-purple-300 block">Margen Est.</span>
                    <span class="text-xs sm:text-sm font-black text-purple-400 font-mono block">
                      +${{ Math.max(0, (order.resellerProfit || ((order.clientPrice || 0) - (order.providerCost || 0)))).toLocaleString() }}
                    </span>
                    <span class="text-[9px] text-purple-300/70">ARS neto</span>
                  </div>
                </div>
              </div>

              <!-- Fila Inferior: Botones de Acción -->
              <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-slate-800/60">
                <div class="flex flex-wrap items-center gap-2">
                  <!-- Botón Despachar Credenciales en Nube -->
                  <button
                    type="button"
                    @click="openGamingDispatchModal(order)"
                    class="px-3 py-1.5 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-black text-xs transition flex items-center gap-1 shadow cursor-pointer"
                    title="Cargar correo, clave PSN/2FA y despachar a la Bóveda del cliente"
                  >
                    <span>🔑</span>
                    <span>{{ order.accountDetails?.email ? 'Editar Credenciales' : 'Despachar Juego' }}</span>
                  </button>

                  <!-- Botón de Alternar Estado (Entregado / Pendiente) -->
                  <button 
                    type="button"
                    @click="handleToggleGamingOrderStatus(order)"
                    :class="[
                      'px-3 py-1.5 rounded-xl font-bold text-xs transition flex items-center gap-1 shadow cursor-pointer',
                      order.status === 'Dispatched' || order.status === 'Entregado'
                        ? 'bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border border-amber-500/30'
                        : 'bg-emerald-600 hover:bg-emerald-500 text-white'
                    ]"
                  >
                    <span>{{ order.status === 'Dispatched' || order.status === 'Entregado' ? '🔄 Pendiente' : '✅ Entregado' }}</span>
                  </button>

                  <!-- Botón de Ver Comprobante si existe -->
                  <button 
                    v-if="order.hasReceipt || order.voucher"
                    type="button"
                    @click="openReceiptViewer(order)"
                    class="px-3 py-1.5 rounded-xl bg-indigo-600/20 hover:bg-indigo-600/30 text-indigo-300 border border-indigo-500/30 font-bold text-xs transition flex items-center gap-1 cursor-pointer"
                    title="Ver comprobante de pago subido por el cliente"
                  >
                    <span>🧾 Ver Comprobante</span>
                  </button>

                  <!-- Botón Comprar en Proveedor -->
                  <a 
                    v-if="order.providerUrl || adminStore.gamingProviderSites?.find(s => s.key === order.fulfillmentProvider)?.url"
                    :href="order.providerUrl || adminStore.gamingProviderSites?.find(s => s.key === order.fulfillmentProvider)?.url" 
                    target="_blank"
                    class="px-3 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs transition flex items-center gap-1 shadow"
                  >
                    <span>🔗 Comprar en Proveedor ↗</span>
                  </a>

                  <!-- Botón Entregar por WhatsApp -->
                  <button 
                    type="button" 
                    @click="sendWhatsAppGameDelivery(order)"
                    class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs transition flex items-center gap-1 shadow cursor-pointer"
                  >
                    <span>💬 Entregar por WhatsApp</span>
                  </button>

                  <!-- Botón Editar / Asignar Juego -->
                  <button 
                    type="button" 
                    @click="openAssignGameModal(order)"
                    class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs border border-slate-700 transition flex items-center gap-1 cursor-pointer"
                    title="Editar título o plataforma del juego"
                  >
                    <span>✏️ Editar Juego</span>
                  </button>
                </div>

                <!-- Botón Eliminar Pedido -->
                <button 
                  type="button"
                  @click="handleDeleteGamingOrder(order)"
                  class="p-1.5 rounded-lg text-slate-500 hover:text-rose-400 hover:bg-rose-500/10 transition cursor-pointer"
                  title="Eliminar pedido gamer"
                >
                  <span class="text-sm">🗑️</span>
                </button>
              </div>
            </div>
          </div>

          <!-- Paginador Inferior -->
          <div v-if="totalGamingOrderPages > 1" class="flex flex-col sm:flex-row justify-between items-center gap-3 pt-4 border-t border-slate-800">
            <span class="text-xs text-slate-400">
              Página <strong>{{ gamingOrderCurrentPage }}</strong> de <strong>{{ totalGamingOrderPages }}</strong> ({{ filteredGamingOrdersForAdmin.length }} pedidos gamer)
            </span>
            <div class="flex items-center gap-1">
              <button 
                type="button" 
                @click="gamingOrderCurrentPage = 1"
                :disabled="gamingOrderCurrentPage === 1"
                class="px-2.5 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-xs font-bold text-slate-300 hover:text-white disabled:opacity-30 disabled:cursor-not-allowed transition cursor-pointer"
              >
                ««
              </button>
              <button 
                type="button" 
                @click="gamingOrderCurrentPage--"
                :disabled="gamingOrderCurrentPage === 1"
                class="px-3 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-xs font-bold text-slate-300 hover:text-white disabled:opacity-30 disabled:cursor-not-allowed transition cursor-pointer"
              >
                Anterior
              </button>
              <span class="px-3 py-1 text-xs font-mono font-bold text-indigo-400 bg-indigo-500/10 border border-indigo-500/20 rounded-lg">
                {{ gamingOrderCurrentPage }} / {{ totalGamingOrderPages }}
              </span>
              <button 
                type="button" 
                @click="gamingOrderCurrentPage++"
                :disabled="gamingOrderCurrentPage === totalGamingOrderPages"
                class="px-3 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-xs font-bold text-slate-300 hover:text-white disabled:opacity-30 disabled:cursor-not-allowed transition cursor-pointer"
              >
                Siguiente
              </button>
              <button 
                type="button" 
                @click="gamingOrderCurrentPage = totalGamingOrderPages"
                :disabled="gamingOrderCurrentPage === totalGamingOrderPages"
                class="px-2.5 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-xs font-bold text-slate-300 hover:text-white disabled:opacity-30 disabled:cursor-not-allowed transition cursor-pointer"
              >
                »»
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- ==================== SUB-VISTA 6: POS VENTA RÁPIDA, CUPONES Y MARKETING ==================== -->
      <div v-if="gamingAdminSubTab === 'marketing_pos'" class="space-y-6">
        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
          <!-- COLUMNA IZQUIERDA: POS VENTA DIRECTA & COTIZADOR (xl:col-span-7) -->
          <div class="xl:col-span-7 bg-[#020617] border border-indigo-500/30 rounded-3xl p-5 sm:p-6 space-y-5 shadow-xl">
            <div class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-slate-800">
              <div>
                <h3 class="text-base sm:text-lg font-black text-white flex items-center gap-2">
                  <span>🛒 POS Venta Rápida & Cotizador Gamer</span>
                </h3>
                <p class="text-[11px] text-slate-400">
                  Registra ventas manuales (WhatsApp / Instagram / Revendedor) o genera cotizaciones limpias sin exponer proveedores.
                </p>
              </div>
              <button
                v-if="posSelectedGame"
                type="button"
                @click="clearPosGame"
                class="text-[11px] font-bold text-slate-400 hover:text-white bg-slate-900 px-3 py-1.5 rounded-xl border border-slate-800 cursor-pointer"
              >
                ↺ Cambiar Juego
              </button>
            </div>

            <!-- Buscador / Selector de Juego -->
            <div v-if="!posSelectedGame" class="space-y-3">
              <div class="relative">
                <span class="absolute left-3.5 top-3 text-slate-500 text-xs">🔍</span>
                <input
                  v-model="posGameSearch"
                  type="text"
                  placeholder="Busca el juego por título, PS5, PS4 o categoría..."
                  class="w-full bg-slate-900 border border-slate-700 focus:border-indigo-400 rounded-xl pl-9 pr-4 py-2.5 text-sm text-white outline-none"
                >
              </div>
              <div class="max-h-72 overflow-y-auto custom-scrollbar divide-y divide-slate-800/70 bg-slate-950/60 border border-slate-800 rounded-2xl">
                <button
                  v-for="g in filteredPosGames"
                  :key="g.id"
                  type="button"
                  @click="selectPosGame(g)"
                  class="w-full p-3 flex items-center justify-between gap-3 text-left hover:bg-indigo-500/10 transition cursor-pointer"
                >
                  <div class="flex items-center gap-3 min-w-0">
                    <img :src="g.image" class="w-10 h-12 rounded-lg object-cover bg-slate-800 shrink-0" />
                    <div class="min-w-0">
                      <p class="text-xs sm:text-sm font-black text-white truncate">{{ g.title }}</p>
                      <p class="text-[10px] text-slate-400 font-mono">{{ g.platform }} · {{ g.category }}</p>
                    </div>
                  </div>
                  <div class="text-right shrink-0 font-mono">
                    <span class="text-xs font-black text-emerald-400 block">${{ (g.effectivePrimaryPrice || 0).toLocaleString() }}</span>
                    <span v-if="g.effectiveSecondaryPrice" class="text-[10px] text-indigo-300 block">Sec: ${{ g.effectiveSecondaryPrice.toLocaleString() }}</span>
                  </div>
                </button>
              </div>
            </div>

            <!-- Configurador de Venta / Cotización para el Juego Elegido -->
            <div v-else class="space-y-4">
              <div class="flex items-center gap-3 bg-slate-900/70 p-3 rounded-2xl border border-slate-800">
                <img :src="posSelectedGame.image" class="w-12 h-14 rounded-xl object-cover shrink-0" />
                <div class="min-w-0 flex-1">
                  <span class="text-[10px] font-mono text-indigo-300 uppercase font-bold">{{ posSelectedGame.platform }} · {{ posSelectedGame.id }}</span>
                  <h4 class="text-sm sm:text-base font-black text-white truncate">{{ posSelectedGame.title }}</h4>
                </div>
                <div class="flex items-center gap-1.5 shrink-0">
                  <button
                    type="button"
                    @click="openAdminPlaqueForGaming(posSelectedGame)"
                    class="px-3 py-2 rounded-xl bg-sky-500/20 hover:bg-sky-500 text-sky-200 hover:text-slate-950 border border-sky-500/35 text-xs font-black transition cursor-pointer"
                    title="Generar placa 1:1 con logo sobre portada"
                  >
                    📤 Placa
                  </button>
                  <button
                    type="button"
                    @click="openFlyerForGaming(posSelectedGame)"
                    class="px-3 py-2 rounded-xl bg-pink-600/20 hover:bg-pink-600 text-pink-300 hover:text-white border border-pink-500/30 text-xs font-black transition cursor-pointer"
                  >
                    🎨 Flyer
                  </button>
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                  <label class="text-[10px] font-bold uppercase text-slate-400 block mb-1">Licencia</label>
                  <select v-model="posForm.licenseType" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-xs text-white font-bold outline-none">
                    <option value="primary">⭐️ Primaria (${{ (posSelectedGame.effectivePrimaryPrice || 0).toLocaleString() }})</option>
                    <option v-if="posSelectedGameSupportsSecondary" value="secondary">⚡️ Secundaria (${{ (posSelectedGame.effectiveSecondaryPrice || 0).toLocaleString() }})</option>
                  </select>
                </div>

                <div>
                  <label class="text-[10px] font-bold uppercase text-slate-400 block mb-1">Proveedor de Compra</label>
                  <select v-model="posForm.providerKey" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-xs text-white font-bold outline-none">
                    <option v-for="q in posSelectedGameQuotes" :key="q.key" :value="q.key">
                      {{ q.badge || q.name }} ({{ q.costUnit > 0 ? '$' + q.costUnit.toLocaleString() : 'Sin cotiz.' }})
                    </option>
                  </select>
                </div>

                <div>
                  <label class="text-[10px] font-bold uppercase text-slate-400 block mb-1">Cantidad / Canal</label>
                  <div class="grid grid-cols-2 gap-1.5">
                    <input v-model.number="posForm.quantity" type="number" min="1" max="20" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-xs text-white font-mono outline-none">
                    <select v-model="posForm.resellerId" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-xs text-white font-bold outline-none">
                      <option value="DIRECT">Directa</option>
                      <option v-for="r in adminStore.resellers" :key="r.id" :value="r.id">{{ r.name }}</option>
                    </select>
                  </div>
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                  <label class="text-[10px] font-bold uppercase text-slate-400 block mb-1">Nombre Cliente</label>
                  <input v-model="posForm.customerName" type="text" placeholder="Ej: Juan Pérez" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-xs text-white outline-none">
                </div>
                <div>
                  <label class="text-[10px] font-bold uppercase text-slate-400 block mb-1">WhatsApp Cliente</label>
                  <input v-model="posForm.customerPhone" type="text" placeholder="Ej: 54911..." class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-xs text-white font-mono outline-none">
                </div>
                <div>
                  <label class="text-[10px] font-bold uppercase text-slate-400 block mb-1">Descuento (ARS) / PIN Bóveda</label>
                  <div class="grid grid-cols-2 gap-1.5">
                    <input v-model.number="posForm.discountAmount" type="number" min="0" placeholder="$ Desc." class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-xs text-amber-300 font-mono outline-none">
                    <input v-model="posForm.pin" type="text" maxlength="4" placeholder="1234" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-xs text-white font-mono outline-none">
                  </div>
                </div>
              </div>

              <div class="p-3 bg-slate-900/70 border border-slate-800 rounded-2xl space-y-2.5">
                <label class="flex items-center gap-2 text-xs font-bold text-emerald-300 cursor-pointer">
                  <input v-model="posForm.markDispatched" type="checkbox" class="rounded text-emerald-500">
                  <span>✅ Cargar credenciales y despachar al instante a la Bóveda del cliente</span>
                </label>
                <div v-if="posForm.markDispatched" class="grid grid-cols-1 sm:grid-cols-3 gap-2 pt-1">
                  <input v-model="posForm.accountEmail" type="email" placeholder="Correo PSN" class="bg-slate-950 border border-slate-700 rounded-xl p-2 text-xs text-white outline-none">
                  <input v-model="posForm.accountPass" type="text" placeholder="Contraseña PSN" class="bg-slate-950 border border-slate-700 rounded-xl p-2 text-xs text-white font-mono outline-none">
                  <input v-model="posForm.accountPin" type="text" placeholder="PIN / 2FA (opcional)" class="bg-slate-950 border border-slate-700 rounded-xl p-2 text-xs text-amber-300 font-mono outline-none">
                </div>
              </div>

              <!-- Resumen Financiero Vivo -->
              <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 bg-slate-950 p-3 rounded-2xl border border-slate-800 text-center">
                <div>
                  <span class="text-[9px] text-slate-500 uppercase font-bold block">Total Cliente</span>
                  <span class="text-sm font-black text-emerald-400 font-mono">${{ posSummary.finalTotal.toLocaleString() }}</span>
                </div>
                <div>
                  <span class="text-[9px] text-slate-500 uppercase font-bold block">Costo Mayorista</span>
                  <span class="text-sm font-bold text-slate-300 font-mono">${{ posSummary.costTotal.toLocaleString() }}</span>
                </div>
                <div>
                  <span class="text-[9px] text-slate-500 uppercase font-bold block">Comisión Rev.</span>
                  <span class="text-sm font-bold text-amber-300 font-mono">${{ posSummary.resellerCut.toLocaleString() }}</span>
                </div>
                <div>
                  <span class="text-[9px] text-slate-500 uppercase font-bold block">Ganancia Neta</span>
                  <span class="text-sm font-black text-purple-400 font-mono">+${{ posSummary.netProfit.toLocaleString() }}</span>
                </div>
              </div>

              <div class="flex flex-wrap gap-2.5 pt-1">
                <button
                  type="button"
                  @click="submitAdminPosGamingSale"
                  :disabled="isSubmittingPosSale"
                  class="flex-1 min-w-[180px] bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-black text-xs py-3 px-4 rounded-xl shadow-lg transition cursor-pointer disabled:opacity-50"
                >
                  {{ isSubmittingPosSale ? '⏳ Registrando...' : '✅ Registrar Venta POS' }}
                </button>
                <button
                  type="button"
                  @click="sendPosGamingQuoteWhatsApp"
                  class="flex-1 min-w-[180px] bg-indigo-600 hover:bg-indigo-500 text-white font-black text-xs py-3 px-4 rounded-xl shadow transition cursor-pointer"
                >
                  📲 Enviar Cotización WhatsApp
                </button>
              </div>
            </div>
          </div>

          <!-- COLUMNA DERECHA: CUPONES PROMOCIONALES + LISTAS VIRALES (xl:col-span-5) -->
          <div class="xl:col-span-5 space-y-5">
            <!-- Gestor de Cupones Promocionales -->
            <div class="bg-[#020617] border border-amber-500/30 rounded-3xl p-5 space-y-4 shadow-xl">
              <div>
                <h3 class="text-base font-black text-white flex items-center gap-2">
                  <span>🏷️ Cupones Promocionales ({{ (adminStore.promoCodes || []).length }})</span>
                </h3>
                <p class="text-[11px] text-slate-400">Se sincronizan con Firebase Firestore para el carrito web.</p>
              </div>

              <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                <input v-model="newPromoForm.code" type="text" placeholder="CÓDIGO (Ej: GAMER15)" class="col-span-2 bg-slate-900 border border-slate-700 rounded-xl p-2 text-xs text-white font-mono uppercase outline-none">
                <select v-model="newPromoForm.type" class="bg-slate-900 border border-slate-700 rounded-xl p-2 text-xs text-white font-bold outline-none">
                  <option value="percent">% OFF</option>
                  <option value="fixed">$ ARS</option>
                </select>
                <input v-model.number="newPromoForm.value" type="number" min="1" placeholder="Valor" class="bg-slate-900 border border-slate-700 rounded-xl p-2 text-xs text-emerald-300 font-mono outline-none">
              </div>

              <div class="grid grid-cols-3 gap-2">
                <select v-model="newPromoForm.scope" class="bg-slate-900 border border-slate-700 rounded-xl p-2 text-xs text-white outline-none">
                  <option value="all">Toda la tienda</option>
                  <option value="gaming">Solo Juegos</option>
                  <option value="streaming">Solo Streaming</option>
                  <option value="physical">Solo Físicos</option>
                </select>
                <input v-model.number="newPromoForm.minAmount" type="number" min="0" placeholder="Mínimo $" class="bg-slate-900 border border-slate-700 rounded-xl p-2 text-xs text-white font-mono outline-none">
                <button
                  type="button"
                  @click="handleCreatePromoCode"
                  class="bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs rounded-xl px-3 py-2 transition cursor-pointer"
                >
                  ➕ Crear
                </button>
              </div>

              <div class="max-h-52 overflow-y-auto custom-scrollbar space-y-2">
                <div
                  v-for="coupon in (adminStore.promoCodes || [])"
                  :key="coupon.code"
                  class="p-2.5 rounded-xl bg-slate-900/70 border border-slate-800 flex items-center justify-between gap-2 text-xs"
                >
                  <div class="min-w-0">
                    <div class="flex items-center gap-1.5">
                      <span class="font-mono font-black text-amber-300">{{ coupon.code }}</span>
                      <span :class="['text-[9px] px-1.5 py-0.5 rounded font-bold', coupon.active !== false ? 'bg-emerald-500/20 text-emerald-300' : 'bg-slate-800 text-slate-500']">
                        {{ coupon.active !== false ? 'Activo' : 'Pausado' }}
                      </span>
                      <span class="text-[10px] text-slate-400 font-mono">{{ coupon.uses || 0 }}/{{ coupon.maxUses || '∞' }}</span>
                    </div>
                    <p class="text-[10px] text-slate-400 truncate">{{ coupon.description }} · Alcance: {{ coupon.scope }}</p>
                  </div>
                  <div class="flex items-center gap-1 shrink-0">
                    <button type="button" @click="copyPromoCodeBanner(coupon)" class="px-2 py-1 rounded bg-slate-800 hover:bg-slate-700 text-white text-[10px] cursor-pointer" title="Copiar mensaje promo">📲</button>
                    <button type="button" @click="openPromoCodeWhatsApp(coupon)" class="px-2 py-1 rounded bg-emerald-600/20 hover:bg-emerald-600 text-emerald-300 hover:text-white text-[10px] cursor-pointer" title="Difundir cupón por WhatsApp">💬</button>
                    <button type="button" @click="handleTogglePromoCode(coupon.code)" class="px-2 py-1 rounded bg-slate-800 hover:bg-slate-700 text-amber-300 text-[10px] cursor-pointer" title="Pausar/Activar">{{ coupon.active !== false ? '⏸️' : '▶️' }}</button>
                    <button type="button" @click="handleDeletePromoCode(coupon.code)" class="px-2 py-1 rounded bg-rose-500/10 hover:bg-rose-500/30 text-rose-400 text-[10px] cursor-pointer" title="Eliminar">🗑️</button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Generador de Listas Promocionales WhatsApp / Telegram -->
            <div class="bg-[#020617] border border-pink-500/30 rounded-3xl p-5 space-y-3 shadow-xl">
              <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                  <h3 class="text-base font-black text-white">📢 Generador Lista Viral &amp; Placa Pro</h3>
                  <p class="text-[11px] text-slate-400">Listas y placas con atribución directa o enlace ?ref= del distribuidor.</p>
                </div>
                <button
                  type="button"
                  @click="openAdminMarketingPlaque"
                  class="bg-indigo-500/20 hover:bg-indigo-500 text-indigo-200 hover:text-white border border-indigo-500/40 font-black text-[11px] px-3 py-1.5 rounded-xl transition cursor-pointer shrink-0"
                  title="Crear placa multi-producto con los primeros destacados de esta campaña"
                >
                  📤 Placa Top
                </button>
              </div>

              <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                <select v-model="marketingListConfig.campaignMode" class="bg-slate-900 border border-slate-700 rounded-xl p-2 text-xs text-white font-semibold outline-none">
                  <option value="games">🎮 Juegos Digitales</option>
                  <option value="coupon_games">🎟️ Juegos + Cupón</option>
                  <option value="combos">🎁 Combos Streaming</option>
                </select>

                <select v-model="marketingListConfig.resellerId" class="bg-slate-900 border border-slate-700 rounded-xl p-2 text-xs text-sky-300 font-semibold outline-none">
                  <option value="DIRECT">🏪 Tienda Directa</option>
                  <option v-for="r in adminStore.resellers" :key="r.id" :value="r.id">👤 {{ r.storeName || r.name }}</option>
                </select>

                <select v-if="marketingListConfig.campaignMode === 'coupon_games'" v-model="marketingListConfig.couponCode" class="bg-slate-900 border border-slate-700 rounded-xl p-2 text-xs text-amber-300 font-mono outline-none">
                  <option value="">Auto (Primer Cupón Activo)</option>
                  <option v-for="c in (adminStore.promoCodes || []).filter(x => x.active !== false)" :key="c.code" :value="c.code">
                    {{ c.code }} ({{ c.type === 'fixed' ? '$' + c.value : c.value + '%' }})
                  </option>
                </select>
              </div>

              <div v-if="marketingListConfig.campaignMode !== 'combos'" class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                <select v-model="marketingListConfig.platform" class="bg-slate-900 border border-slate-700 rounded-xl p-2 text-xs text-white outline-none">
                  <option value="ALL">Todas las plataformas</option>
                  <option value="PS5">PS5</option>
                  <option value="PS4">PS4</option>
                  <option value="PS3">PS3</option>
                  <option value="Xbox">Xbox</option>
                  <option value="Switch">Switch</option>
                  <option value="PC">PC / Steam</option>
                </select>
                <select v-model.number="marketingListConfig.maxPrice" class="bg-slate-900 border border-slate-700 rounded-xl p-2 text-xs text-white outline-none">
                  <option :value="0">Cualquier $</option>
                  <option :value="15000">&le; $15.000</option>
                  <option :value="25000">&le; $25.000</option>
                  <option :value="35000">&le; $35.000</option>
                </select>
                <select v-model="marketingListConfig.sortMode" class="bg-slate-900 border border-slate-700 rounded-xl p-2 text-xs text-white outline-none">
                  <option value="popular">Destacados</option>
                  <option value="lowest_price">Más Baratos</option>
                  <option value="highest_margin">Mayor Margen $</option>
                </select>
                <select v-model.number="marketingListConfig.limit" class="bg-slate-900 border border-slate-700 rounded-xl p-2 text-xs text-white font-mono outline-none">
                  <option :value="8">8 juegos</option>
                  <option :value="12">12 juegos</option>
                  <option :value="20">20 juegos</option>
                </select>
              </div>

              <textarea
                :value="marketingDealsPreviewText"
                readonly
                rows="8"
                class="w-full bg-slate-950 border border-slate-800 rounded-2xl p-3 text-[11px] text-slate-200 font-mono outline-none resize-none custom-scrollbar"
              ></textarea>

              <div class="flex flex-wrap gap-2">
                <button
                  type="button"
                  @click="copyMarketingDealsList"
                  class="flex-1 bg-indigo-600 hover:bg-indigo-500 text-white font-black text-xs py-2.5 px-3 rounded-xl transition cursor-pointer"
                >
                  📋 Copiar Lista Completa
                </button>
                <button
                  type="button"
                  @click="openMarketingDealsWhatsApp"
                  class="flex-1 bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs py-2.5 px-3 rounded-xl transition cursor-pointer"
                >
                  📲 Enviar por WhatsApp
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ==================== SUB-VISTA 7: SALUD DEL CATÁLOGO, DEPENDENCIAS Y EXPORTACIÓN ==================== -->
      <div v-if="gamingAdminSubTab === 'health_deps'" class="space-y-6">
        <!-- Estado de Dependencias Técnicas Gaming -->
        <div class="bg-[#020617] border border-cyan-500/30 rounded-3xl p-5 sm:p-6 space-y-4">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
              <h3 class="text-base sm:text-lg font-black text-white">🩺 Salud de Dependencias & Integridad del Catálogo</h3>
              <p class="text-[11px] text-slate-400">
                Audita proveedores, enlaces OG, sincronización Firebase Firestore y corrige faltantes de costos o portadas en 1 clic.
              </p>
            </div>
            <div class="flex flex-wrap gap-2">
              <button type="button" @click="testFirebaseConnectionHandler" :disabled="isTestingFirebase" class="px-3 py-2 rounded-xl bg-sky-600/20 hover:bg-sky-600 text-sky-300 hover:text-white border border-sky-500/30 text-xs font-bold transition cursor-pointer">
                {{ isTestingFirebase ? '⏳ Probando...' : '🔥 Test Firebase Cloud' }}
              </button>
              <button type="button" @click="manualCloudSync" :disabled="isSyncingCloud" class="px-3 py-2 rounded-xl bg-indigo-600/20 hover:bg-indigo-600 text-indigo-300 hover:text-white border border-indigo-500/30 text-xs font-bold transition cursor-pointer">
                {{ isSyncingCloud ? '⏳ Sincronizando...' : '☁️ Sincronizar Nube' }}
              </button>
              <button type="button" @click="adminTab = 'database'" class="px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold transition cursor-pointer">
                💾 Ir a Backup / DB
              </button>
            </div>
          </div>

          <!-- KPIs de Salud -->
          <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
            <div class="bg-slate-950 p-4 rounded-2xl border border-emerald-500/30">
              <span class="text-[10px] uppercase font-bold text-slate-400 block">Puntaje de Salud</span>
              <span class="text-2xl font-black text-emerald-400 font-mono">{{ adminStore.gamingCatalogHealth?.healthScore || 0 }}%</span>
              <span class="text-[10px] text-slate-500 block">{{ adminStore.gamingCatalogHealth?.total || 0 }} títulos totales</span>
            </div>
            <div class="bg-slate-950 p-4 rounded-2xl border border-sky-500/30">
              <span class="text-[10px] uppercase font-bold text-slate-400 block">Portadas Oficiales</span>
              <span class="text-2xl font-black text-sky-400 font-mono">{{ adminStore.gamingCatalogHealth?.officialCoverRate || 0 }}%</span>
              <span class="text-[10px] text-amber-300 block">{{ adminStore.gamingCatalogHealth?.noOfficialImageCount || 0 }} sin portada oficial</span>
            </div>
            <div class="bg-slate-950 p-4 rounded-2xl border border-indigo-500/30">
              <span class="text-[10px] uppercase font-bold text-slate-400 block">Cobertura Multi-Sitio</span>
              <span class="text-2xl font-black text-indigo-400 font-mono">{{ adminStore.gamingCatalogHealth?.multiSiteRate || 0 }}%</span>
              <span class="text-[10px] text-slate-500 block">{{ adminStore.gamingCatalogHealth?.singleProviderCount || 0 }} en 1 solo proveedor</span>
            </div>
            <div class="bg-slate-950 p-4 rounded-2xl border border-amber-500/30">
              <span class="text-[10px] uppercase font-bold text-slate-400 block">Falta Costo Secundaria</span>
              <span class="text-2xl font-black text-amber-400 font-mono">{{ adminStore.gamingCatalogHealth?.missingSecondaryCount || 0 }}</span>
              <span class="text-[10px] text-slate-500 block">PS4/PS5 sin precio Sec.</span>
            </div>
            <div class="bg-slate-950 p-4 rounded-2xl border border-purple-500/30">
              <span class="text-[10px] uppercase font-bold text-slate-400 block">Precios PVP Manuales</span>
              <span class="text-2xl font-black text-purple-400 font-mono">{{ adminStore.gamingCatalogHealth?.customPvpCount || 0 }}</span>
              <span class="text-[10px] text-slate-500 block">Con override activo</span>
            </div>
          </div>

          <!-- Botones de Reparación Masiva y Exportación -->
          <div class="flex flex-wrap gap-2.5 pt-2 border-t border-slate-800/80">
            <button
              type="button"
              @click="handleAutoFixSecondaryPrices"
              class="px-4 py-2.5 rounded-xl bg-amber-500/20 hover:bg-amber-500 text-amber-300 hover:text-slate-950 border border-amber-500/40 font-black text-xs transition cursor-pointer"
            >
              ⚡ Auto-Calcular Secundarias Faltantes (55%)
            </button>
            <button
              v-if="(adminStore.gamingCatalogHealth?.customPvpCount || 0) > 0"
              type="button"
              @click="handleResetAllCustomPrices"
              class="px-4 py-2.5 rounded-xl bg-purple-500/20 hover:bg-purple-500 text-purple-300 hover:text-white border border-purple-500/40 font-black text-xs transition cursor-pointer"
            >
              🔄 Resetear {{ adminStore.gamingCatalogHealth.customPvpCount }} PVP Manuales a Fórmula
            </button>
            <button
              type="button"
              @click="exportGamingCatalogCsv"
              class="px-4 py-2.5 rounded-xl bg-emerald-600/20 hover:bg-emerald-600 text-emerald-300 hover:text-white border border-emerald-500/40 font-black text-xs transition cursor-pointer"
            >
              📥 Exportar Catálogo Gamer CSV
            </button>
            <button
              type="button"
              @click="exportGamingOrdersCsv"
              class="px-4 py-2.5 rounded-xl bg-blue-600/20 hover:bg-blue-600 text-blue-300 hover:text-white border border-blue-500/40 font-black text-xs transition cursor-pointer"
            >
              📥 Exportar Pedidos Gamer CSV
            </button>
          </div>
        </div>

        <!-- Inspector Interactivo de Hallazgos -->
        <div class="bg-[#020617] border border-slate-800 rounded-3xl p-5 space-y-4">
          <div class="flex flex-wrap items-center gap-2">
            <button
              type="button"
              @click="healthInspectMode = 'missing_covers'"
              :class="['px-3 py-1.5 rounded-xl text-xs font-black cursor-pointer transition', healthInspectMode === 'missing_covers' ? 'bg-sky-600 text-white' : 'bg-slate-900 text-slate-400 hover:text-white']"
            >
              🖼️ Portadas Faltantes ({{ adminStore.gamingCatalogHealth?.noOfficialImageCount || 0 }})
            </button>
            <button
              type="button"
              @click="healthInspectMode = 'missing_sec'"
              :class="['px-3 py-1.5 rounded-xl text-xs font-black cursor-pointer transition', healthInspectMode === 'missing_sec' ? 'bg-amber-500 text-slate-950' : 'bg-slate-900 text-slate-400 hover:text-white']"
            >
              ⚡️ Sin Secundaria ({{ adminStore.gamingCatalogHealth?.missingSecondaryCount || 0 }})
            </button>
            <button
              type="button"
              @click="healthInspectMode = 'custom_pvp'"
              :class="['px-3 py-1.5 rounded-xl text-xs font-black cursor-pointer transition', healthInspectMode === 'custom_pvp' ? 'bg-purple-600 text-white' : 'bg-slate-900 text-slate-400 hover:text-white']"
            >
              💎 PVP Manuales ({{ adminStore.gamingCatalogHealth?.customPvpCount || 0 }})
            </button>
          </div>

          <div v-if="healthInspectMode === 'missing_covers'" class="space-y-2 max-h-96 overflow-y-auto custom-scrollbar">
            <div v-if="!(adminStore.gamingCatalogHealth?.noOfficialImageList || []).length" class="p-6 text-center text-xs text-emerald-400 font-bold">
              ✅ Todos los títulos cuentan con portada oficial verificada.
            </div>
            <div
              v-for="g in (adminStore.gamingCatalogHealth?.noOfficialImageList || [])"
              :key="g.id"
              class="p-3 rounded-xl bg-slate-950 border border-slate-800 flex flex-col sm:flex-row sm:items-center gap-2.5"
            >
              <div class="min-w-0 flex-1">
                <p class="text-xs font-black text-white truncate">{{ g.title }}</p>
                <span class="text-[10px] font-mono text-slate-500">{{ g.id }} · {{ g.platform }}</span>
              </div>
              <div class="flex items-center gap-2 sm:w-1/2">
                <input
                  v-model="coverUrlEdits[g.id]"
                  type="url"
                  placeholder="https://image.api.playstation.com/..."
                  class="flex-1 bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-white outline-none"
                >
                <button
                  type="button"
                  @click="handleSaveGameCoverUrl(g.id)"
                  class="px-3 py-1.5 rounded-lg bg-sky-600 hover:bg-sky-500 text-white text-xs font-bold cursor-pointer shrink-0"
                >
                  Guardar
                </button>
              </div>
            </div>
          </div>

          <div v-else-if="healthInspectMode === 'missing_sec'" class="space-y-2 max-h-96 overflow-y-auto custom-scrollbar">
            <div v-if="!(adminStore.gamingCatalogHealth?.missingSecondaryList || []).length" class="p-6 text-center text-xs text-emerald-400 font-bold">
              ✅ Todos los títulos PS4/PS5 tienen cotización Secundaria configurada.
            </div>
            <div
              v-for="g in (adminStore.gamingCatalogHealth?.missingSecondaryList || [])"
              :key="g.id"
              class="p-3 rounded-xl bg-slate-950 border border-slate-800 flex items-center justify-between gap-2 text-xs"
            >
              <div>
                <p class="font-bold text-white">{{ g.title }}</p>
                <span class="text-[10px] font-mono text-slate-500">{{ g.id }} · {{ g.platform }} · Primaria PVP: ${{ (g.effectivePrimaryPrice || 0).toLocaleString() }}</span>
              </div>
              <button type="button" @click="openGamingPriceEdit(g)" class="px-3 py-1.5 rounded-lg bg-indigo-600/20 hover:bg-indigo-600 text-indigo-300 hover:text-white text-xs font-bold cursor-pointer">
                ✏️ Editar Precios
              </button>
            </div>
          </div>

          <div v-else class="space-y-2 max-h-96 overflow-y-auto custom-scrollbar">
            <div v-if="!(adminStore.gamingCatalogHealth?.customPvpList || []).length" class="p-6 text-center text-xs text-slate-400">
              No hay videojuegos con override manual de PVP en este momento.
            </div>
            <div
              v-for="g in (adminStore.gamingCatalogHealth?.customPvpList || [])"
              :key="g.id"
              class="p-3 rounded-xl bg-slate-950 border border-slate-800 flex items-center justify-between gap-2 text-xs"
            >
              <div>
                <p class="font-bold text-white">{{ g.title }}</p>
                <span class="text-[10px] font-mono text-purple-300">PVP Manual Pri: ${{ (g.customPrimaryPrice || g.effectivePrimaryPrice || 0).toLocaleString() }} / Sec: ${{ (g.customSecondaryPrice || g.effectiveSecondaryPrice || 0).toLocaleString() }}</span>
              </div>
              <button type="button" @click="adminStore.resetGameCustomPrices(g.id); toast.add('PVP restaurado a fórmula', 'info')" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold cursor-pointer">
                🔄 Restaurar
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ==================== MODAL DE DESPACHO REAL DE CREDENCIALES GAMER ==================== -->
    <div v-if="showGamingDispatchModal && dispatchingGamingOrder" class="fixed inset-0 z-[85] flex items-center justify-center p-3 sm:p-5 bg-black/85 backdrop-blur-md">
      <div class="bg-[#0b0f19] border border-emerald-500/40 rounded-3xl max-w-lg w-full max-h-[90vh] overflow-y-auto custom-scrollbar p-6 space-y-4 shadow-2xl">
        <div class="flex justify-between items-start border-b border-slate-800 pb-3">
          <div>
            <span class="text-[10px] font-mono uppercase text-emerald-400 font-bold">Despacho Bóveda Cliente</span>
            <h3 class="text-lg font-black text-white">🔑 {{ dispatchingGamingOrder.gameTitle || dispatchingGamingOrder.platform }}</h3>
            <p class="text-xs text-slate-400">Cliente: <strong class="text-white">{{ dispatchingGamingOrder.customerName }}</strong> · #{{ dispatchingGamingOrder.id }}</p>
          </div>
          <button type="button" @click="showGamingDispatchModal = false" class="w-8 h-8 rounded-xl bg-slate-800 text-slate-400 hover:text-white flex items-center justify-center font-bold cursor-pointer">✕</button>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-[10px] font-bold uppercase text-slate-400 block mb-1">Sitio de Compra</label>
            <select v-model="gamingDispatchForm.providerKey" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-xs text-white outline-none">
              <option v-for="site in (adminStore.gamingProviderSites || [])" :key="site.key" :value="site.key">{{ site.badge || site.name }}</option>
            </select>
          </div>
          <div>
            <label class="text-[10px] font-bold uppercase text-slate-400 block mb-1">Costo Real (ARS)</label>
            <input v-model.number="gamingDispatchForm.providerCost" type="number" min="0" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-xs text-emerald-300 font-mono outline-none">
          </div>
        </div>

        <div class="space-y-3">
          <div>
            <label class="text-[10px] font-bold uppercase text-slate-400 block mb-1">Correo de Cuenta PSN *</label>
            <input v-model="gamingDispatchForm.email" type="email" placeholder="cuenta.psn@ejemplo.com" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-sm text-white outline-none">
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-[10px] font-bold uppercase text-slate-400 block mb-1">Contraseña *</label>
              <input v-model="gamingDispatchForm.pass" type="text" placeholder="Clave PSN" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-sm text-white font-mono outline-none">
            </div>
            <div>
              <label class="text-[10px] font-bold uppercase text-slate-400 block mb-1">Código 2FA / PIN (Opcional)</label>
              <input v-model="gamingDispatchForm.pin" type="text" placeholder="Ej: 482910" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-sm text-amber-300 font-mono outline-none">
            </div>
          </div>
          <div>
            <label class="text-[10px] font-bold uppercase text-slate-400 block mb-1">Notas de Instalación (Opcional)</label>
            <input v-model="gamingDispatchForm.notes" type="text" placeholder="Ej: Activar consola principal al iniciar..." class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-xs text-white outline-none">
          </div>
          <label class="flex items-center gap-2 text-xs text-emerald-300 font-bold cursor-pointer">
            <input v-model="gamingDispatchForm.sendWhatsApp" type="checkbox" class="rounded text-emerald-500">
            <span>📲 Abrir WhatsApp con el cliente al guardar</span>
          </label>
        </div>

        <div class="flex gap-2.5 pt-2">
          <button type="button" @click="showGamingDispatchModal = false" class="w-1/3 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold cursor-pointer">Cancelar</button>
          <button type="button" @click="confirmGamingDispatch" class="w-2/3 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white text-xs font-black shadow-lg cursor-pointer">
            ✅ Guardar y Enviar a Bóveda
          </button>
        </div>
      </div>
    </div>

    <!-- ==================== MODAL DE GESTIÓN RÁPIDA DE PROVEEDOR POR JUEGO (BOTÓN DE PROVEEDOR) ==================== -->
    <div v-if="showProviderQuickModal && selectedGameForProvider" class="fixed inset-0 z-[80] flex items-center justify-center p-3 sm:p-5 pt-16 sm:pt-6 pb-6 overflow-y-auto overscroll-contain bg-black/85 backdrop-blur-md">
      <div class="bg-[#0b0f19] border border-indigo-500/30 rounded-3xl max-w-xl w-full max-h-[90vh] overflow-y-auto overscroll-contain custom-scrollbar p-6 space-y-5 shadow-2xl">
        <div class="flex justify-between items-center pb-3 border-b border-slate-800">
          <div class="flex items-center gap-2.5">
            <div class="w-10 h-10 rounded-2xl bg-indigo-500/20 border border-indigo-500/30 flex items-center justify-center text-xl">
              🏢
            </div>
            <div>
              <h3 class="text-base font-black text-white">Gestión de Proveedor & Cotizaciones</h3>
              <p class="text-[11px] text-slate-400">Arbitraje mayorista, selección de cumplimiento y negociación en 1 clic</p>
            </div>
          </div>
          <button 
            @click="showProviderQuickModal = false" 
            type="button" 
            class="w-8 h-8 rounded-xl bg-slate-800 text-slate-400 hover:text-white flex items-center justify-center text-sm font-bold transition cursor-pointer"
          >
            ✕
          </button>
        </div>

        <!-- Ficha del Juego -->
        <div class="flex items-center gap-3.5 p-3.5 bg-slate-900/70 rounded-2xl border border-slate-800">
          <img 
            :src="selectedGameForProvider.image" 
            :alt="selectedGameForProvider.title" 
            class="w-16 h-16 object-cover rounded-xl border border-slate-700 shrink-0"
            @error="(e) => e.target.src = 'https://images.unsplash.com/photo-1606813907291-d86efa9b94db?w=800&auto=format&fit=crop&q=80'"
          >
          <div class="flex-1 min-w-0 space-y-1">
            <h4 class="font-black text-white text-xs line-clamp-1" :title="selectedGameForProvider.title">{{ selectedGameForProvider.title }}</h4>
            <div class="flex items-center gap-1.5 flex-wrap">
              <span class="text-[9px] bg-indigo-500/20 text-indigo-300 font-mono font-bold px-1.5 py-0.2 rounded">{{ selectedGameForProvider.platform }}</span>
              <span class="text-[9px] text-slate-400">{{ selectedGameForProvider.category }}</span>
              <span v-if="selectedGameForProvider.subcategory" class="text-[9px] text-amber-400 bg-amber-500/10 px-1.5 py-0.2 rounded border border-amber-500/20">{{ selectedGameForProvider.subcategory }}</span>
            </div>
            <div class="flex items-center gap-3 pt-0.5 text-[11px] font-mono">
              <span class="text-slate-400">PVP Prim: <strong class="text-emerald-400 font-bold">${{ (selectedGameForProvider.effectivePrimaryPrice || 0).toLocaleString() }}</strong></span>
              <span class="text-slate-400">PVP Sec: <strong class="text-emerald-300 font-bold">${{ (selectedGameForProvider.effectiveSecondaryPrice || 0).toLocaleString() }}</strong></span>
            </div>
          </div>
        </div>

        <!-- Resumen Mayor Costo PVP vs Compra Conveniente -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 text-xs font-mono">
          <div class="p-3 rounded-2xl bg-amber-500/10 border border-amber-500/30 space-y-1">
            <div class="flex items-center justify-between">
              <span class="text-[10px] font-black text-amber-300 uppercase">📈 Marca Mayor Costo (PVP Web)</span>
            </div>
            <div class="text-sm font-black text-white">{{ selectedGameForProvider.highestProviderName || 'Sitio Base' }}</div>
            <div class="text-[11px] text-slate-300">
              Costo: <strong class="text-white">${{ (selectedGameForProvider.baseCostPrimary || 0).toLocaleString() }}</strong> → PVP: <strong class="text-emerald-400">${{ (selectedGameForProvider.effectivePrimaryPrice || 0).toLocaleString() }}</strong>
            </div>
          </div>

          <div class="p-3 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 space-y-1">
            <div class="flex items-center justify-between">
              <span class="text-[10px] font-black text-emerald-300 uppercase">🏆 Tu Proveedor para Comprar</span>
              <span v-if="selectedGameForProvider.diffPrimary > 0" class="text-[9px] bg-amber-400 text-slate-950 font-black px-1.5 py-0.2 rounded-full">+${{ selectedGameForProvider.diffPrimary.toLocaleString() }} brecha</span>
            </div>
            <div class="text-sm font-black text-white">{{ selectedGameForProvider.bestFulfillmentPrimaryName }}</div>
            <div class="text-[11px] text-slate-300">
              Compras a: <strong class="text-emerald-300">${{ (selectedGameForProvider.actualPurchaseCostPrimary ?? selectedGameForProvider.minCostPrimary ?? selectedGameForProvider.baseCostPrimary ?? 0).toLocaleString() }}</strong> · Ganas: <strong class="text-emerald-400">+${{ (selectedGameForProvider.actualStoreProfitPrimary ?? selectedGameForProvider.storeGrossMarginPrimary ?? 0).toLocaleString() }}</strong>
            </div>
          </div>
        </div>

        <!-- Lista de Cotizaciones por Proveedor -->
        <div class="space-y-3">
          <div class="flex items-center justify-between text-xs font-bold text-slate-300">
            <span>Cotizaciones por Sitio Web / Proveedor:</span>
            <span class="text-slate-400 text-[11px] font-mono">{{ (selectedGameForProvider.activeProvidersList || []).length }} sitios</span>
          </div>

          <div class="space-y-2.5">
            <div 
              v-for="prov in (selectedGameForProvider.activeProvidersList || [])" 
              :key="prov.key"
              :class="[
                'p-3.5 rounded-2xl border transition-all space-y-2.5',
                (selectedGameForProvider.preferredFulfillmentProvider === prov.key || selectedGameForProvider.bestFulfillmentPrimary === prov.key)
                  ? 'bg-indigo-950/30 border-indigo-500/50 shadow-md'
                  : 'bg-slate-950/80 border-slate-800 hover:border-slate-700'
              ]"
            >
              <div class="flex items-center justify-between gap-2 flex-wrap">
                <div class="flex items-center gap-1.5 flex-wrap">
                  <span class="text-sm font-black text-white">{{ prov.name }}</span>
                  <span 
                    v-if="selectedGameForProvider.preferredFulfillmentProvider === prov.key || (selectedGameForProvider.bestFulfillmentPrimary === prov.key && !selectedGameForProvider.preferredFulfillmentProvider)"
                    class="text-[9px] bg-emerald-500/20 text-emerald-300 font-bold px-2 py-0.5 rounded-full border border-emerald-500/40 flex items-center gap-1"
                  >
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    <span>✓ Asignado para Compra</span>
                  </span>
                  <span 
                    v-if="prov.isCheapest && (selectedGameForProvider.diffPrimary || 0) > 0"
                    class="text-[9px] bg-amber-500/20 text-amber-300 font-bold px-2 py-0.5 rounded-full border border-amber-500/40"
                  >
                    🏆 Más Conveniente
                  </span>
                  <span 
                    v-if="prov.isHighestPvpDriver"
                    class="text-[9px] bg-purple-500/20 text-purple-300 font-bold px-2 py-0.5 rounded-full border border-purple-500/40"
                  >
                    📈 Fija Mayor Costo PVP
                  </span>
                  <span 
                    v-else-if="(selectedGameForProvider.diffPrimary || 0) > 0 && prov.costPrimary > (selectedGameForProvider.minCostPrimary || 0)"
                    class="text-[9px] bg-rose-500/15 text-rose-300 font-mono px-2 py-0.5 rounded-full border border-rose-500/30"
                  >
                    +${{ (prov.costPrimary - (selectedGameForProvider.minCostPrimary || 0)).toLocaleString() }} vs más barato
                  </span>
                </div>

                <span class="text-[10px] text-slate-400 font-mono">{{ prov.stock }}</span>
              </div>

              <!-- Costos y Ganancia Real del Proveedor -->
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs font-mono">
                <div class="p-2 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-between">
                  <span class="text-slate-400 text-[10px]">Costo Prim:</span>
                  <strong class="text-white font-black">${{ (prov.costPrimary || 0).toLocaleString() }}</strong>
                </div>
                <div class="p-2 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-between">
                  <span class="text-slate-400 text-[10px]">Costo Sec:</span>
                  <span class="text-indigo-300 font-bold">${{ (prov.costSecondary || 0).toLocaleString() }}</span>
                </div>
                <div class="p-2 rounded-xl bg-emerald-950/25 border border-emerald-500/30 flex items-center justify-between">
                  <span class="text-emerald-300 text-[10px]">Ganancia Real:</span>
                  <strong class="text-emerald-400 font-black">+${{ (prov.realProfitPrimary ?? Math.max(0, (selectedGameForProvider.effectivePrimaryPrice || 0) - (prov.costPrimary || 0))).toLocaleString() }}</strong>
                </div>
              </div>

              <!-- Botones de Acción de este Proveedor -->
              <div class="flex items-center gap-2 flex-wrap pt-1">
                <button 
                  type="button" 
                  @click="selectPreferredProvider(selectedGameForProvider, prov.key)"
                  :disabled="selectedGameForProvider.preferredFulfillmentProvider === prov.key || (selectedGameForProvider.bestFulfillmentPrimary === prov.key && !selectedGameForProvider.preferredFulfillmentProvider)"
                  :class="[
                    'py-1.5 px-3 rounded-xl text-xs font-bold transition flex items-center gap-1 cursor-pointer active:scale-95',
                    (selectedGameForProvider.preferredFulfillmentProvider === prov.key || (selectedGameForProvider.bestFulfillmentPrimary === prov.key && !selectedGameForProvider.preferredFulfillmentProvider))
                      ? 'bg-slate-800 text-slate-500 cursor-not-allowed border border-slate-700/50'
                      : 'bg-indigo-600 hover:bg-indigo-500 text-white shadow'
                  ]"
                >
                  <span>{{ (selectedGameForProvider.preferredFulfillmentProvider === prov.key || (selectedGameForProvider.bestFulfillmentPrimary === prov.key && !selectedGameForProvider.preferredFulfillmentProvider)) ? '✓ Asignado' : 'Elegir para Comprar' }}</span>
                </button>

                <button 
                  type="button" 
                  @click="openConvenePriceModal(selectedGameForProvider, prov.key)"
                  class="py-1.5 px-3 rounded-xl bg-emerald-600/20 hover:bg-emerald-600 text-emerald-300 hover:text-white border border-emerald-500/30 text-xs font-bold transition flex items-center gap-1 cursor-pointer active:scale-95"
                  title="Convenir costo acordado para este proveedor"
                >
                  <span>🤝 Convenir Costo</span>
                </button>

                <a 
                  v-if="prov.url" 
                  :href="prov.url" 
                  target="_blank" 
                  class="py-1.5 px-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-white border border-slate-700 text-xs font-bold transition flex items-center gap-1 cursor-pointer"
                >
                  <span>Sitio ↗</span>
                </a>

                <button 
                  type="button" 
                  @click="openProviderWhatsApp(selectedGameForProvider, prov)"
                  class="py-1.5 px-2.5 rounded-xl bg-teal-600/20 hover:bg-teal-600 text-teal-300 hover:text-white border border-teal-500/30 text-xs font-bold transition flex items-center gap-1 cursor-pointer active:scale-95"
                  title="Consultar al proveedor por WhatsApp"
                >
                  <span>💬 WhatsApp</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Botones Inferiores -->
        <div class="flex flex-wrap items-center justify-between gap-2 pt-3 border-t border-slate-800">
          <div class="flex flex-wrap items-center gap-2">
            <button 
              type="button" 
              @click="openConvenePriceModal(selectedGameForProvider)"
              class="px-4 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white rounded-xl text-xs font-black transition shadow cursor-pointer active:scale-95 flex items-center gap-1.5"
            >
              <span>🤝 Convenir Nuevo Precio</span>
            </button>

            <button 
              type="button" 
              @click="showProviderQuickModal = false; openGamingPriceEdit(selectedGameForProvider)"
              class="px-3.5 py-2 bg-indigo-600/20 hover:bg-indigo-600 text-indigo-300 hover:text-white border border-indigo-500/30 rounded-xl text-xs font-bold transition cursor-pointer flex items-center gap-1.5"
            >
              <span>✏️ Editar / Agregar Sitios</span>
            </button>
          </div>

          <button 
            type="button" 
            @click="showProviderQuickModal = false" 
            class="px-5 py-2 bg-slate-800 text-slate-300 hover:text-white rounded-xl text-xs font-bold transition cursor-pointer"
          >
            Cerrar
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL DE EDICIÓN DE PRECIOS GAMING (AMBOS PROVEEDORES) -->
    <!-- MODAL DE CONVENIR PRECIO CON PROVEEDOR -->
    <div v-if="showConvenePriceModal && conveningGame" class="fixed inset-0 z-[80] flex items-center justify-center p-3 sm:p-5 pt-16 sm:pt-6 pb-6 overflow-y-auto overscroll-contain bg-black/85 backdrop-blur-md">
      <div class="bg-[#0b0f19] border border-emerald-500/30 rounded-3xl max-w-lg w-full max-h-[90vh] overflow-y-auto overscroll-contain custom-scrollbar p-6 space-y-5 shadow-2xl">
        <div class="flex justify-between items-center pb-3 border-b border-slate-800">
          <div class="flex items-center gap-2">
            <span class="text-xl">🤝</span>
            <div>
              <h3 class="text-base font-black text-white">Convenir Precio de Proveedor</h3>
              <p class="text-[11px] text-slate-400">Pacta el costo del mayorista y fija el precio oficial para la plataforma</p>
            </div>
          </div>
          <button 
            @click="showConvenePriceModal = false" 
            type="button" 
            class="w-8 h-8 rounded-xl bg-slate-800 text-slate-400 hover:text-white flex items-center justify-center text-sm font-bold transition cursor-pointer"
          >
            ✕
          </button>
        </div>

        <!-- Ficha del Juego -->
        <div class="flex items-center gap-3 p-3 bg-slate-900/60 rounded-2xl border border-slate-800">
          <img 
            :src="conveningGame.image" 
            :alt="conveningGame.title" 
            class="w-14 h-14 object-cover rounded-xl border border-slate-700 shrink-0"
            @error="(e) => e.target.src = 'https://images.unsplash.com/photo-1606813907291-d86efa9b94db?w=800&auto=format&fit=crop&q=80'"
          >
          <div class="flex-1 min-w-0">
            <h4 class="font-black text-white text-xs line-clamp-1">{{ conveningGame.title }}</h4>
            <div class="flex items-center gap-1.5 mt-0.5">
              <span class="text-[9px] bg-indigo-500/20 text-indigo-300 font-mono font-bold px-1.5 py-0.2 rounded">{{ conveningGame.platform }}</span>
              <span class="text-[10px] text-slate-400 font-mono">PVP actual: ${{ (conveningGame.effectivePrimaryPrice || 0).toLocaleString() }}</span>
            </div>
          </div>
        </div>

        <!-- Adopción Rápida con 1 Clic desde TODOS los Sitios Cotizados -->
        <div class="space-y-2">
          <div class="flex items-center justify-between">
            <label class="text-xs font-bold text-slate-300">Adopción Rápida con 1 Clic (Todos los Sitios Cotizados):</label>
            <span class="text-[10px] font-mono text-slate-400">Mayor Costo Web: ${{ (conveningGame.baseCostPrimary || 0).toLocaleString() }}</span>
          </div>
          <div v-if="conveningGame.activeProvidersList && conveningGame.activeProvidersList.length > 0" class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-48 overflow-y-auto custom-scrollbar pr-1">
            <button
              v-for="prov in conveningGame.activeProvidersList"
              :key="prov.key"
              type="button"
              @click="quickAdoptProviderCost(prov.key)"
              :class="[
                'p-2.5 rounded-xl border text-left transition cursor-pointer space-y-0.5',
                conveneForm.providerKey === prov.key
                  ? 'border-emerald-400 bg-emerald-950/35 shadow'
                  : prov.isCheapest
                    ? 'border-emerald-500/30 bg-emerald-950/15 hover:bg-emerald-950/30'
                    : 'border-slate-700 bg-slate-900/70 hover:bg-slate-800/80'
              ]"
            >
              <div class="flex items-center justify-between gap-1">
                <span class="text-[10px] font-bold text-white truncate">{{ prov.name }}</span>
                <span v-if="prov.isCheapest" class="text-[8px] px-1.5 rounded bg-emerald-500/20 text-emerald-300 font-black">🏆 Barato</span>
                <span v-else-if="prov.isHighestPvpDriver" class="text-[8px] px-1.5 rounded bg-amber-500/20 text-amber-300 font-black">📈 PVP Web</span>
              </div>
              <div class="text-xs font-black text-white font-mono">
                ${{ (prov.costPrimary || 0).toLocaleString() }} ARS
                <span class="text-[9px] text-slate-400 font-normal">· Sec ${{ (prov.costSecondary || 0).toLocaleString() }}</span>
              </div>
              <span class="text-[9px] text-emerald-300/90 block">Ganancia real aquí: +${{ (prov.realProfitPrimary || 0).toLocaleString() }} ARS</span>
            </button>
          </div>
        </div>

        <!-- Formulario de Acuerdo de Precio -->
        <div class="space-y-3 pt-2 border-t border-slate-800">
          <div class="flex items-center justify-between gap-2 text-[11px]">
            <span class="text-slate-400">Sitio asignado para compra:</span>
            <strong class="text-emerald-300 font-black">{{ conveneForm.customProviderName }}</strong>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1">
              <label class="text-[11px] font-bold text-slate-300">Costo Acordado ($ ARS):</label>
              <input 
                v-model.number="conveneForm.agreedCost" 
                type="number" 
                step="500" 
                class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-white font-mono font-bold text-xs outline-none focus:border-emerald-500"
              >
            </div>
            <div class="space-y-1">
              <label class="text-[11px] font-bold text-slate-300">
                {{ conveneForm.lockCustomPvp ? 'PVP Manual Fijo ($ ARS):' : 'PVP Automático Mayor Costo:' }}
              </label>
              <input 
                v-model.number="conveneForm.customPvp" 
                :disabled="!conveneForm.lockCustomPvp"
                type="number" 
                step="500" 
                :class="[
                  'w-full border rounded-xl p-2.5 font-mono font-black text-xs outline-none',
                  conveneForm.lockCustomPvp
                    ? 'bg-slate-900 border-amber-500/50 text-emerald-400 focus:border-emerald-500'
                    : 'bg-slate-950 border-slate-800 text-slate-400 cursor-not-allowed'
                ]"
              >
            </div>
          </div>

          <label class="flex items-center gap-2 text-[11px] text-slate-300 cursor-pointer">
            <input v-model="conveneForm.lockCustomPvp" type="checkbox" class="accent-amber-500 rounded">
            <span>Fijar PVP manual (si no se marca, la tienda sigue usando el Mayor Costo de todos los sitios +{{ adminStore.gamingStoreMarginPercent }}%)</span>
          </label>

          <div class="flex items-center justify-between p-2.5 bg-slate-950 rounded-xl border border-slate-800 text-[11px]">
            <span class="text-slate-400">Ganancia Real Tienda estimada:</span>
            <strong class="text-emerald-400 font-mono font-black">
              +${{ Math.max(0, ((conveneForm.lockCustomPvp ? conveneForm.customPvp : (conveningGame.effectivePrimaryPrice || conveneForm.customPvp)) || 0) - (conveneForm.agreedCost || 0)).toLocaleString() }} ARS
            </strong>
          </div>
        </div>

        <!-- Botones de Acción -->
        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-800">
          <button 
            type="button" 
            @click="showConvenePriceModal = false" 
            class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl text-xs font-bold transition hover:bg-slate-700 cursor-pointer"
          >
            Cancelar
          </button>
          <button 
            type="button" 
            @click="saveConvenePrice" 
            class="px-5 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white rounded-xl text-xs font-black transition shadow-lg shadow-emerald-600/30 cursor-pointer"
          >
            ✓ Guardar Precio Convenido
          </button>
        </div>
      </div>
    </div>

    <div v-if="showGamingPriceEditModal" class="fixed inset-0 z-[80] flex items-center justify-center p-3 sm:p-5 pt-16 sm:pt-6 pb-6 overflow-y-auto overscroll-contain bg-black/85 backdrop-blur-md">
      <div class="bg-[#0b0f19] border border-indigo-500/30 rounded-3xl max-w-2xl w-full max-h-[90vh] overflow-y-auto overscroll-contain custom-scrollbar p-6 space-y-5 shadow-2xl">
        <div class="flex justify-between items-center pb-3 border-b border-slate-800">
          <div>
            <h3 class="text-lg font-black text-white">Editar Costos Multi-Sitio & Proveedor: {{ gamingEditForm.title }}</h3>
            <p class="text-xs text-slate-400">Administra cotizaciones de todos los sitios scrapeados/agregados. El <strong class="text-amber-300">Mayor Costo</strong> fija el PVP Web y tú eliges el sitio más conveniente para comprar.</p>
          </div>
          <button @click="showGamingPriceEditModal = false" type="button" class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-slate-800/90 hover:bg-slate-700 active:scale-95 text-slate-300 hover:text-white border border-slate-700/80 shadow-md flex items-center justify-center text-sm sm:text-base font-bold transition-all shrink-0 cursor-pointer ml-auto" title="Cerrar ventana">✕</button>
        </div>

        <!-- Resumen Live de Cálculo Mayor Costo -> PVP vs Compra Conveniente -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 p-3.5 bg-slate-950 rounded-2xl border border-slate-800 text-xs font-mono">
          <div class="p-2.5 rounded-xl bg-amber-500/10 border border-amber-500/30 space-y-0.5">
            <span class="text-[9px] text-amber-300 font-bold uppercase block">📈 Base Mayor Costo → PVP</span>
            <div class="text-sm font-black text-white">${{ (gamingEditLivePreview.maxCostPrimary || 0).toLocaleString() }} → <span class="text-emerald-400">${{ (gamingEditLivePreview.finalPvpPrimary || 0).toLocaleString() }}</span></div>
            <span class="text-[10px] text-slate-400 block truncate">Líder: {{ gamingEditLivePreview.highestSiteName }}</span>
          </div>

          <div class="p-2.5 rounded-xl bg-indigo-500/10 border border-indigo-500/30 space-y-0.5">
            <span class="text-[9px] text-indigo-300 font-bold uppercase block">🏢 Tu Compra Elegida</span>
            <div class="text-sm font-black text-white">${{ (gamingEditLivePreview.buyCostPrimary || 0).toLocaleString() }} ARS</div>
            <span class="text-[10px] text-indigo-200 block truncate">En: {{ gamingEditLivePreview.chosenBuyerName }}</span>
          </div>

          <div class="p-2.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 space-y-0.5">
            <span class="text-[9px] text-emerald-300 font-bold uppercase block">💰 Tu Ganancia Real</span>
            <div class="text-sm font-black text-emerald-400">+${{ (gamingEditLivePreview.realProfitPrimary || 0).toLocaleString() }} ARS</div>
            <span class="text-[10px] text-emerald-200/80 block">
              {{ gamingEditLivePreview.arbitrageExtra > 0 ? `Incluye +$${gamingEditLivePreview.arbitrageExtra.toLocaleString()} arbitraje` : 'Margen directo catálogo' }}
            </span>
          </div>
        </div>

        <!-- Lista Editable de Todos los Sitios & Proveedores -->
        <div class="space-y-2.5">
          <div class="flex items-center justify-between text-xs font-bold text-slate-300">
            <span>Cotizaciones por Sitio Web / Proveedor ({{ (gamingEditForm.providersQuotes || []).length }})</span>
            <button
              v-if="gamingEditForm.preferredFulfillmentProvider"
              type="button"
              @click="gamingEditForm.preferredFulfillmentProvider = ''"
              class="text-[10px] text-indigo-400 hover:text-indigo-300 cursor-pointer underline"
            >
              Volver a Compra Automática (Más Barato)
            </button>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div
              v-for="q in gamingEditForm.providersQuotes"
              :key="q.key"
              :class="[
                'p-3.5 rounded-2xl border space-y-2.5 transition-all',
                gamingEditForm.preferredFulfillmentProvider === q.key
                  ? 'bg-emerald-950/25 border-emerald-500/50'
                  : (q.available && (Number(q.costPrimary) > 0 || Number(q.costSecondary) > 0))
                    ? 'bg-slate-900/70 border-slate-700'
                    : 'bg-slate-950/60 border-slate-800/80 opacity-80'
              ]"
            >
              <div class="flex items-center justify-between gap-2">
                <div class="min-w-0">
                  <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="font-black text-white text-xs truncate">{{ q.badge || q.name }}</span>
                    <span v-if="q.available && q.includeInPvp !== false && Number(q.costPrimary) > 0 && Number(q.costPrimary) === gamingEditLivePreview.maxCostPrimary" class="text-[8px] px-1.5 py-0.2 rounded bg-amber-500/20 text-amber-300 border border-amber-500/30 font-bold">📈 Fija PVP</span>
                    <span v-if="q.available && Number(q.costPrimary) > 0 && Number(q.costPrimary) === gamingEditLivePreview.minCostPrimary" class="text-[8px] px-1.5 py-0.2 rounded bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-bold">🏆 Menor Costo</span>
                  </div>
                  <span v-if="q.enabled === false" class="text-[9px] text-rose-400 block">Sitio desactivado globalmente</span>
                  <span v-else-if="q.includeInPvp === false" class="text-[9px] text-slate-400 block">Excluido del Mayor Costo PVP</span>
                </div>

                <label class="flex items-center gap-1 text-[10px] text-slate-300 cursor-pointer shrink-0">
                  <input v-model="q.available" type="checkbox" class="accent-indigo-500 rounded">
                  <span>Cotizado</span>
                </label>
              </div>

              <div class="grid grid-cols-2 gap-2">
                <div>
                  <label class="block text-[9px] text-slate-400 uppercase font-bold mb-0.5">Costo Primaria</label>
                  <input
                    v-model.number="q.costPrimary"
                    @input="Number(q.costPrimary) > 0 && (q.available = true)"
                    type="number"
                    step="100"
                    class="w-full bg-slate-950 border border-slate-700 rounded-xl p-2 text-white font-mono font-bold text-xs outline-none focus:border-indigo-500"
                  >
                </div>
                <div>
                  <label class="block text-[9px] text-slate-400 uppercase font-bold mb-0.5">Costo Secundaria</label>
                  <input
                    v-model.number="q.costSecondary"
                    @input="Number(q.costSecondary) > 0 && (q.available = true)"
                    type="number"
                    step="100"
                    class="w-full bg-slate-950 border border-slate-700 rounded-xl p-2 text-slate-300 font-mono text-xs outline-none focus:border-indigo-500"
                  >
                </div>
              </div>

              <div class="flex items-center justify-between gap-2 pt-1">
                <button
                  type="button"
                  :disabled="!q.available || Number(q.costPrimary) <= 0"
                  @click="gamingEditForm.preferredFulfillmentProvider = (gamingEditForm.preferredFulfillmentProvider === q.key ? '' : q.key)"
                  :class="[
                    'px-2.5 py-1 rounded-lg text-[10px] font-bold transition cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed',
                    gamingEditForm.preferredFulfillmentProvider === q.key
                      ? 'bg-emerald-600 text-white'
                      : 'bg-slate-800 hover:bg-slate-700 text-slate-300'
                  ]"
                >
                  {{ gamingEditForm.preferredFulfillmentProvider === q.key ? '✓ Proveedor Compra Elegido' : 'Elegir para Compra' }}
                </button>
                <a v-if="q.url" :href="q.url" target="_blank" class="text-[10px] text-indigo-400 hover:underline">Web ↗</a>
              </div>
            </div>
          </div>
        </div>

        <!-- Opcional: Fijar PVP Manual vs Automático por Mayor Costo -->
        <div class="p-3.5 bg-slate-950 rounded-2xl border border-slate-800 space-y-3">
          <div class="flex items-center justify-between gap-2 flex-wrap">
            <label class="flex items-center gap-2 text-xs font-bold text-white cursor-pointer">
              <input v-model="gamingEditForm.useCustomPvp" type="checkbox" class="accent-amber-500 w-4 h-4 rounded">
              <span>Fijar PVP público manual personalizado (si se desmarca, usa Automático Mayor Costo +{{ adminStore.gamingStoreMarginPercent }}%)</span>
            </label>
            <span class="text-[11px] font-mono font-bold text-emerald-400">
              Auto PVP Sec: ${{ (gamingEditLivePreview.autoPvpSecondary || 0).toLocaleString() }} ARS
            </span>
          </div>

          <div v-if="gamingEditForm.useCustomPvp" class="grid grid-cols-2 gap-3 pt-1">
            <div>
              <label class="block text-[10px] text-slate-400 font-bold mb-1">PVP Público Primaria Manual</label>
              <input v-model.number="gamingEditForm.customPrimaryPrice" type="number" step="100" class="w-full bg-slate-900 border border-amber-500/40 rounded-xl p-2 text-emerald-400 font-mono font-black text-xs">
            </div>
            <div>
              <label class="block text-[10px] text-slate-400 font-bold mb-1">PVP Público Secundaria Manual</label>
              <input v-model.number="gamingEditForm.customSecondaryPrice" type="number" step="100" class="w-full bg-slate-900 border border-amber-500/40 rounded-xl p-2 text-emerald-300 font-mono font-bold text-xs">
            </div>
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
          <button type="button" @click="showGamingPriceEditModal = false" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl text-xs font-bold cursor-pointer">
            Cancelar
          </button>
          <button type="button" @click="saveGamingPriceEdit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-black shadow cursor-pointer">
            💾 Guardar Cotizaciones y Aplicar PVP
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL PARA AGREGAR NUEVO VIDEOJUEGO -->
    <div v-if="showGamingNewModal" class="fixed inset-0 z-[80] flex items-center justify-center p-3 sm:p-5 pt-16 sm:pt-6 pb-6 overflow-y-auto overscroll-contain bg-black/85 backdrop-blur-md">
      <div class="bg-[#0b0f19] border border-indigo-500/30 rounded-3xl max-w-xl w-full max-h-[90vh] overflow-y-auto custom-scrollbar p-6 space-y-4 shadow-2xl">
        <div class="flex justify-between items-center pb-3 border-b border-slate-800">
          <h3 class="text-lg font-black text-white">➕ Agregar Nuevo Videojuego Digital</h3>
          <button @click="showGamingNewModal = false" type="button" class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-slate-800/90 hover:bg-slate-700 active:scale-95 text-slate-300 hover:text-white border border-slate-700/80 shadow-md flex items-center justify-center text-sm sm:text-base font-bold transition-all shrink-0 cursor-pointer ml-auto" title="Cerrar ventana">✕</button>
        </div>

        <div class="space-y-3">
          <div>
            <label class="block text-xs font-bold text-slate-400 mb-1">Título del Videojuego *</label>
            <input v-model="gamingNewForm.title" type="text" placeholder="Ej: Dragon Ball: Sparking! ZERO" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-white font-bold text-xs">
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-400 mb-1">Consola / Plataforma</label>
              <select v-model="gamingNewForm.platform" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2 text-white font-bold text-xs">
                <option value="PS4 / PS5">PS4 / PS5</option>
                <option value="PS5">PlayStation 5</option>
                <option value="PS4">PlayStation 4</option>
                <option value="PS3">PlayStation 3</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-400 mb-1">Categoría</label>
              <select v-model="gamingNewForm.category" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2 text-white font-bold text-xs">
                <option v-for="cat in adminStore.gamingCategories.filter(c => c !== 'Todas')" :key="cat" :value="cat">{{ cat }}</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-400 mb-1">URL de Portada (Imagen)</label>
            <input v-model="gamingNewForm.image" type="text" placeholder="https://..." class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-white text-xs">
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-400 mb-1">Descripción Breve</label>
            <textarea v-model="gamingNewForm.description" rows="2" placeholder="Sinopsis del juego..." class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2 text-white text-xs"></textarea>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
            <div class="p-3 bg-blue-950/20 border border-blue-500/30 rounded-xl space-y-2">
              <label class="text-[10px] font-black text-blue-300 uppercase block">Sitio Proveedor #1 *</label>
              <select v-model="gamingNewForm.primarySiteKey" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-1.5 text-white text-xs font-bold">
                <option v-for="s in (adminStore.gamingProviderSites || [])" :key="s.key" :value="s.key">{{ s.badge || s.name }}</option>
              </select>
              <div class="grid grid-cols-2 gap-1.5">
                <div>
                  <span class="text-[9px] text-slate-400 block">Costo Prim.</span>
                  <input v-model.number="gamingNewForm.costPrimary" type="number" placeholder="Primaria" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2 text-white text-xs font-mono">
                </div>
                <div>
                  <span class="text-[9px] text-slate-400 block">Costo Sec.</span>
                  <input v-model.number="gamingNewForm.costSecondary" type="number" placeholder="Secundaria" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2 text-white text-xs font-mono">
                </div>
              </div>
            </div>

            <div class="p-3 bg-amber-950/20 border border-amber-500/30 rounded-xl space-y-2">
              <label class="text-[10px] font-black text-amber-300 uppercase block">Sitio Proveedor #2 (Comparativa)</label>
              <select v-model="gamingNewForm.secondarySiteKey" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-1.5 text-white text-xs font-bold">
                <option value="">-- Ninguno --</option>
                <option v-for="s in (adminStore.gamingProviderSites || [])" :key="s.key" :value="s.key">{{ s.badge || s.name }}</option>
              </select>
              <div class="grid grid-cols-2 gap-1.5">
                <div>
                  <span class="text-[9px] text-slate-400 block">Costo Prim.</span>
                  <input v-model.number="gamingNewForm.altCostPrimary" type="number" placeholder="Primaria" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2 text-white text-xs font-mono">
                </div>
                <div>
                  <span class="text-[9px] text-slate-400 block">Costo Sec.</span>
                  <input v-model.number="gamingNewForm.altCostSecondary" type="number" placeholder="Secundaria" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2 text-white text-xs font-mono">
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <button type="button" @click="showGamingNewModal = false" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl text-xs font-bold">
            Cancelar
          </button>
          <button type="button" @click="saveNewGamingProduct" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-black shadow">
            Guardar Videojuego
          </button>
        </div>
      </div>
    </div>

    <!-- PESTAÑA: SISTEMA & BASE DE DATOS DEPURADA (PRODUCCIÓN LIMPIA) -->
    <div v-if="adminTab === 'database'" class="bg-slate-900/40 rounded-3xl border border-slate-800 p-8 backdrop-blur-md space-y-8">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pb-4 border-b border-slate-800">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-rose-500/10 flex items-center justify-center text-rose-400 border border-rose-500/20 text-lg">
            💾
          </div>
          <div>
            <h2 class="text-2xl font-black text-white">Sistema & Base de Datos Depurada</h2>
            <p class="text-xs text-slate-400">Supervisión, salud y restauración inmaculada de todas las dependencias del ecosistema StreamePro.</p>
          </div>
        </div>

        <div class="flex flex-wrap gap-2">
          <button 
            type="button"
            @click="adminTab = 'gaming'; gamingAdminSubTab = 'health_deps'"
            class="bg-indigo-600/20 hover:bg-indigo-600 text-indigo-300 hover:text-white border border-indigo-500/40 font-black text-xs px-4 py-2.5 rounded-xl transition-all flex items-center gap-1.5 cursor-pointer"
          >
            <span>🩺</span>
            <span>Salud Catálogo Gamer ({{ adminStore.gamingCatalogHealth?.healthScore || 100 }}%)</span>
          </button>
          <button 
            @click="handleResetToCleanDatabase"
            :disabled="isResettingDb"
            class="bg-rose-600 hover:bg-rose-500 disabled:opacity-50 text-white font-black text-xs px-4 py-2.5 rounded-xl transition-all shadow flex items-center gap-2 cursor-pointer"
          >
            <span>🧹</span>
            <span>{{ isResettingDb ? 'Restaurando...' : 'Restaurar Base de Datos Depurada' }}</span>
          </button>
          <button 
            @click="downloadBackupFile"
            class="bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-bold text-xs px-4 py-2.5 rounded-xl transition-all flex items-center gap-1.5 cursor-pointer"
          >
            <span>📥 Exportar Backup JSON</span>
          </button>
          <button 
            @click="triggerImportBackup"
            class="bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-bold text-xs px-4 py-2.5 rounded-xl transition-all flex items-center gap-1.5 cursor-pointer"
          >
            <span>📤 Importar Backup JSON</span>
          </button>
          <input 
            ref="backupFileInput" 
            type="file" 
            accept=".json" 
            class="hidden" 
            @change="handleBackupFile"
          >
        </div>
      </div>

      <!-- ESTADO Y DIAGNÓSTICO EN TIEMPO REAL DE LAS COLECCIONES -->
      <div class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <h3 class="text-sm font-black text-slate-300 uppercase tracking-wider flex items-center gap-2">
            <span>🛡️</span>
            <span>Estado de las Dependencias del Ecosistema</span>
          </h3>
          <div class="flex flex-wrap items-center gap-2 text-[11px] font-mono">
            <span class="px-2.5 py-1 rounded-lg bg-purple-500/15 text-purple-300 border border-purple-500/30">
              🏷️ Cupones: {{ (adminStore.promoCodes || []).length }}
            </span>
            <span class="px-2.5 py-1 rounded-lg bg-rose-500/15 text-rose-300 border border-rose-500/30">
              🆘 Tickets Abiertos: {{ (adminStore.openSupportTickets || []).length }}
            </span>
          </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
          <div class="bg-[#020617] p-3.5 rounded-xl border border-slate-800 space-y-1">
            <span class="text-[10px] uppercase font-mono text-slate-500 block">1. INVENTARIO VIRTUAL</span>
            <span class="text-lg font-black text-white">{{ adminStore.inventory.length }} cuentas</span>
            <span class="text-[11px] text-emerald-400 block font-mono">Stock de streaming activo</span>
          </div>
          <div class="bg-[#020617] p-3.5 rounded-xl border border-slate-800 space-y-1">
            <span class="text-[10px] uppercase font-mono text-slate-500 block">2. CATÁLOGO DE PLANES</span>
            <span class="text-lg font-black text-white">{{ Object.keys(adminStore.pricingCatalog).length }} plataformas</span>
            <span class="text-[11px] text-blue-400 block font-mono">Precios mayoristas/clientes</span>
          </div>
          <div class="bg-[#020617] p-3.5 rounded-xl border border-slate-800 space-y-1">
            <span class="text-[10px] uppercase font-mono text-slate-500 block">3. REVENDEDORES</span>
            <span class="text-lg font-black text-white">{{ adminStore.resellers.length }} distribuidores</span>
            <span class="text-[11px] text-amber-400 block font-mono">Cuentas con billetera</span>
          </div>
          <div class="bg-[#020617] p-3.5 rounded-xl border border-slate-800 space-y-1">
            <span class="text-[10px] uppercase font-mono text-slate-500 block">4. PEDIDOS VIRTUALES</span>
            <span class="text-lg font-black text-white">{{ adminStore.orders.length }} órdenes</span>
            <span class="text-[11px] text-sky-400 block font-mono">{{ adminStore.pendingOrders.length }} pendientes de entrega</span>
          </div>
          <div class="bg-[#020617] p-3.5 rounded-xl border border-slate-800 space-y-1">
            <span class="text-[10px] uppercase font-mono text-slate-500 block">5. CLIENTES & BÓVEDA</span>
            <span class="text-lg font-black text-white">{{ resellerStore.clients.length }} servicios</span>
            <span class="text-[11px] text-indigo-400 block font-mono">SLA y fechas de vencimiento</span>
          </div>
          <div class="bg-[#020617] p-3.5 rounded-xl border border-slate-800 space-y-1">
            <span class="text-[10px] uppercase font-mono text-slate-500 block">6. CATÁLOGO FÍSICO</span>
            <span class="text-lg font-black text-white">{{ adminStore.physicalProducts.length }} productos</span>
            <span class="text-[11px] text-purple-400 block font-mono">TV Boxes, Remotos, Smart</span>
          </div>
          <div class="bg-[#020617] p-3.5 rounded-xl border border-slate-800 space-y-1">
            <span class="text-[10px] uppercase font-mono text-slate-500 block">7. CATEGORÍAS FÍSICAS</span>
            <span class="text-lg font-black text-white">{{ adminStore.physicalCategories.length }} categorías</span>
            <span class="text-[11px] text-teal-400 block font-mono">Filtros de hardware</span>
          </div>
          <div class="bg-[#020617] p-3.5 rounded-xl border border-slate-800 space-y-1">
            <span class="text-[10px] uppercase font-mono text-slate-500 block">8. PEDIDOS FÍSICOS</span>
            <span class="text-lg font-black text-white">{{ adminStore.physicalOrders.length }} órdenes</span>
            <span class="text-[11px] text-amber-400 block font-mono">Envíos y comisiones (1% vs 3%)</span>
          </div>
          <div class="bg-[#020617] p-3.5 rounded-xl border border-slate-800 space-y-1">
            <span class="text-[10px] uppercase font-mono text-slate-500 block">9. COMBOS & PAQUETES</span>
            <span class="text-lg font-black text-white">{{ adminStore.packages.length }} paquetes</span>
            <span class="text-[11px] text-pink-400 block font-mono">Cine Total 4K, Dúo Series</span>
          </div>
          <div class="bg-[#020617] p-3.5 rounded-xl border border-slate-800 space-y-1">
            <span class="text-[10px] uppercase font-mono text-slate-500 block">10. CUENTAS BANCARIAS</span>
            <span class="text-lg font-black text-white">{{ adminStore.providerBankAccounts.length }} cuentas</span>
            <span class="text-[11px] text-emerald-400 block font-mono">Multi-cuenta con switch activo</span>
          </div>
          <div class="bg-[#020617] p-3.5 rounded-xl border border-slate-800 space-y-1">
            <span class="text-[10px] uppercase font-mono text-slate-500 block">11. TRANSFERENCIAS CORREO</span>
            <span class="text-lg font-black text-white">{{ adminStore.bankTransfers.length }} registros</span>
            <span class="text-[11px] text-blue-400 block font-mono">Reconocimiento Wanderlust</span>
          </div>
          <div class="bg-[#020617] p-3.5 rounded-xl border border-slate-800 space-y-1">
            <span class="text-[10px] uppercase font-mono text-slate-500 block">12. BILLETERA VIRTUAL</span>
            <span class="text-lg font-black text-white">{{ adminStore.walletTransactions.length }} movimientos</span>
            <span class="text-[11px] text-cyan-400 block font-mono">Historial de saldo distribuidores</span>
          </div>
          <div class="bg-[#020617] p-3.5 rounded-xl border border-slate-800 space-y-1">
            <span class="text-[10px] uppercase font-mono text-slate-500 block">13. POSTULACIONES REVENDEDOR</span>
            <span class="text-lg font-black text-white">{{ adminStore.resellerApplications.length }} recibidas</span>
            <span class="text-[11px] text-amber-400 block font-mono">{{ adminStore.pendingResellerApplications.length }} por revisar</span>
          </div>
          <div class="bg-[#020617] p-3.5 rounded-xl border border-slate-800 space-y-1">
            <span class="text-[10px] uppercase font-mono text-slate-500 block">14. ALERTAS Y AUDITORÍA</span>
            <span class="text-lg font-black text-white">{{ adminStore.resellerAlerts.length }} comunicados</span>
            <span class="text-[11px] text-indigo-400 block font-mono">Estado: YA FUE AVISADO</span>
          </div>
          <div class="bg-[#020617] p-3.5 rounded-xl border border-slate-800 space-y-1">
            <span class="text-[10px] uppercase font-mono text-slate-500 block">15. RENOVACIONES CLIENTE</span>
            <span class="text-lg font-black text-white">{{ adminStore.renewalRequests.length }} solicitudes</span>
            <span class="text-[11px] text-red-400 block font-mono">{{ adminStore.pendingRenewals.length }} pendientes</span>
          </div>
          <div class="bg-[#020617] p-3.5 rounded-xl border border-slate-800 space-y-1">
            <span class="text-[10px] uppercase font-mono text-slate-500 block">16. MINI NUBE COMPROBANTES</span>
            <span class="text-lg font-black text-white">IndexedDB</span>
            <span class="text-[11px] text-emerald-400 block font-mono">JPG & PDF con depuración al cierre</span>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL DE CAMBIO PRIVADO DE CONTRASEÑA -->
    <div v-if="showPasswordModal && selectedInvItem" class="fixed inset-0 z-[80] flex items-center justify-center p-3 sm:p-5 pt-16 sm:pt-6 pb-6 overflow-y-auto overscroll-contain bg-black/85 backdrop-blur-md animate-in fade-in duration-200">
      <div class="bg-slate-900 border border-slate-700 w-full max-w-md max-h-[90vh] overflow-y-auto overscroll-contain custom-scrollbar rounded-3xl p-6 shadow-2xl relative space-y-4">
        <div class="flex justify-between items-start border-b border-slate-800 pb-3">
          <div>
            <h3 class="text-xl font-black text-white">Cambiar Contraseña Maestra</h3>
            <p class="text-xs text-slate-400">{{ selectedInvItem.account }}</p>
          </div>
          <button @click="showPasswordModal = false" type="button" class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-slate-800/90 hover:bg-slate-700 active:scale-95 text-slate-300 hover:text-white border border-slate-700/80 shadow-md flex items-center justify-center text-sm sm:text-base font-bold transition-all shrink-0 cursor-pointer ml-auto" title="Cerrar ventana">✕</button>
        </div>

        <div class="p-3 bg-amber-950/40 border border-amber-500/30 rounded-xl text-xs text-amber-300">
          ℹ️ Esta contraseña solo es vista por ti como Proveedor. No será visible para el Revendedor hasta que se concrete una nueva compra o reemplazo de cuenta, salvo que actives la actualización para clientes activos.
        </div>

        <div>
          <label class="block text-xs font-bold uppercase text-slate-400 mb-1">Nueva Contraseña Maestra</label>
          <div class="relative">
            <input 
              v-model="updatedPassword" 
              :type="showPrivPassModal ? 'text' : 'password'" 
              class="w-full bg-slate-950 border border-slate-800 focus:border-brand-accent rounded-xl p-3 pr-10 text-white font-mono text-sm outline-none" 
              required
            >
            <button 
              type="button" 
              @click="showPrivPassModal = !showPrivPassModal" 
              class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white p-1"
              :title="showPrivPassModal ? 'Ocultar contraseña' : 'Ver contraseña'"
            >
              <svg v-if="showPrivPassModal" class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
              <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            </button>
          </div>
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

    <!-- MODAL DE EDICIÓN COMPLETA DE LOTE DE INVENTARIO MAYORISTA -->
    <div v-if="showEditInventoryModal && editingInvItem" class="fixed inset-0 z-[80] flex items-center justify-center p-3 sm:p-5 pt-16 sm:pt-6 pb-6 overflow-y-auto overscroll-contain bg-black/85 backdrop-blur-md animate-in fade-in duration-200">
      <div class="bg-slate-900 border border-slate-700 w-full max-w-2xl max-h-[90vh] overflow-y-auto overscroll-contain custom-scrollbar rounded-3xl p-6 shadow-2xl relative space-y-5">
        <div class="flex justify-between items-start border-b border-slate-800 pb-3">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-indigo-500/10 flex items-center justify-center text-indigo-400 border border-indigo-500/20 text-lg">
              ✏️
            </div>
            <div>
              <h3 class="text-xl font-black text-white">Editar Lote de Inventario</h3>
              <p class="text-xs font-mono text-slate-400">{{ editingInvItem.id }} • {{ editingInvItem.platform }}</p>
            </div>
          </div>
          <button @click="showEditInventoryModal = false" type="button" class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-slate-800/90 hover:bg-slate-700 active:scale-95 text-slate-300 hover:text-white border border-slate-700/80 shadow-md flex items-center justify-center text-sm sm:text-base font-bold transition-all shrink-0 cursor-pointer ml-auto" title="Cerrar ventana">✕</button>
        </div>

        <!-- Formulario de Edición -->
        <div class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Plataforma</label>
              <select v-model="editingInvItem.platform" class="w-full bg-slate-950 border border-slate-800 focus:border-brand-accent rounded-xl p-3 text-white font-semibold outline-none text-sm">
                <option>Netflix</option>
                <option>Max</option>
                <option>Spotify</option>
                <option>Disney+</option>
                <option>IPTV</option>
                <option>Prime Video</option>
                <option>YouTube Premium</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Costo Lote (ARS)</label>
              <input v-model.number="editingInvItem.cost" type="number" class="w-full bg-slate-950 border border-slate-800 focus:border-brand-accent rounded-xl p-3 text-white font-mono text-sm outline-none" required>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Correo / Usuario Máster</label>
              <input v-model="editingInvItem.account" type="text" class="w-full bg-slate-950 border border-slate-800 focus:border-brand-accent rounded-xl p-3 text-white text-sm outline-none font-mono" required>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Contraseña Máster</label>
              <div class="relative">
                <input 
                  v-model="editingInvItem.pass" 
                  :type="showEditStockPass ? 'text' : 'password'" 
                  class="w-full bg-slate-950 border border-slate-800 focus:border-brand-accent rounded-xl p-3 pr-10 text-white font-mono text-sm outline-none" 
                  required
                >
                <button 
                  type="button" 
                  @click="showEditStockPass = !showEditStockPass" 
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white p-1"
                  :title="showEditStockPass ? 'Ocultar contraseña' : 'Ver contraseña'"
                >
                  <svg v-if="showEditStockPass" class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                  <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                </button>
              </div>
            </div>
          </div>

          <!-- Capacidad y Perfiles -->
          <div>
            <div class="flex items-center justify-between mb-2">
              <label class="block text-xs font-bold text-slate-400 uppercase">Capacidad de Pantallas / Perfiles</label>
              <span class="text-xs font-mono text-sky-400 font-bold">{{ editingInvItem.profiles.length }} Perfiles Configurados</span>
            </div>
            <select :value="editingInvItem.maxProfiles || editingInvItem.profiles.length" @change="handleEditMaxProfiles($event.target.value)" class="w-full bg-slate-950 border border-slate-800 focus:border-brand-accent rounded-xl p-3 text-white font-semibold outline-none text-sm">
              <option :value="1">1 Pantalla</option>
              <option :value="2">2 Pantallas</option>
              <option :value="3">3 Pantallas</option>
              <option :value="4">4 Pantallas</option>
              <option :value="6">6 Pantallas</option>
            </select>
          </div>

          <!-- PINs de los perfiles en edición -->
          <div class="border-t border-slate-800 pt-3 space-y-3">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-300 flex items-center gap-2">
              <span>🔢</span>
              <span>PINs y Asignación de Perfiles</span>
            </h4>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-60 overflow-y-auto pr-1">
              <div 
                v-for="(prof, pIdx) in editingInvItem.profiles" 
                :key="pIdx"
                class="bg-slate-950 p-3 rounded-xl border border-slate-800 space-y-2"
              >
                <div class="flex items-center justify-between">
                  <span class="text-xs font-bold text-sky-400">📺 Perfil {{ pIdx + 1 }}</span>
                  <span :class="[
                    'text-[10px] font-bold px-1.5 py-0.5 rounded border',
                    prof.isAssigned 
                      ? 'bg-amber-500/20 text-amber-300 border-amber-500/30' 
                      : 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30'
                  ]">
                    {{ prof.isAssigned ? ('Asignado (' + (prof.assignedTo || 'Cliente') + ')') : 'Libre' }}
                  </span>
                </div>

                <div class="grid grid-cols-2 gap-2">
                  <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-0.5">PIN</label>
                    <input 
                      v-model="prof.pin" 
                      type="text" 
                      maxlength="8" 
                      placeholder="PIN" 
                      class="w-full bg-slate-900 border border-slate-800 focus:border-brand-accent rounded-lg px-2.5 py-1.5 text-white font-mono font-bold text-xs outline-none"
                      required
                    >
                  </div>
                  <div>
                    <label class="block text-[10px] font-semibold text-slate-400 uppercase mb-0.5">Nombre</label>
                    <input 
                      v-model="prof.name" 
                      type="text" 
                      :placeholder="`Perfil ${pIdx + 1}`" 
                      class="w-full bg-slate-900 border border-slate-800 focus:border-brand-accent rounded-lg px-2.5 py-1.5 text-white text-xs outline-none"
                    >
                  </div>
                </div>

                <div v-if="prof.isAssigned" class="pt-1 flex items-center justify-between">
                  <button 
                    type="button" 
                    @click="prof.isAssigned = false; prof.assignedTo = null" 
                    class="text-[10px] text-amber-400 hover:text-amber-300 underline cursor-pointer"
                  >
                    Liberar este perfil
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Botones de Acción -->
        <div class="flex justify-end gap-3 border-t border-slate-800 pt-4">
          <button 
            type="button" 
            @click="showEditInventoryModal = false" 
            class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-400 hover:text-white bg-slate-800 hover:bg-slate-700 transition-colors cursor-pointer"
          >
            Cancelar
          </button>
          <button 
            type="button" 
            @click="handleSaveEditedInventory" 
            class="px-6 py-2.5 rounded-xl text-xs font-black text-slate-950 bg-brand-accent hover:bg-sky-400 transition-all shadow-[0_0_15px_rgba(56,189,248,0.3)] cursor-pointer"
          >
            Guardar Cambios
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL DE MODIFICACIÓN DE RENOVACIÓN POR EL PROVEEDOR -->
    <div v-if="showRenewalEditModal && selectedRenewal" class="fixed inset-0 z-[80] flex items-center justify-center p-3 sm:p-5 pt-16 sm:pt-6 pb-6 overflow-y-auto overscroll-contain bg-black/85 backdrop-blur-md animate-in fade-in duration-200">
      <div class="bg-slate-900 border border-slate-700 w-full max-w-lg max-h-[90vh] overflow-y-auto overscroll-contain custom-scrollbar rounded-3xl p-6 shadow-2xl relative space-y-4 text-xs">
        <div class="flex justify-between items-start border-b border-slate-800 pb-3">
          <div>
            <div class="flex items-center gap-2">
              <h3 class="text-xl font-black text-white">Gestionar Renovación de Servicio</h3>
              <span class="text-xs font-mono font-bold bg-brand-accent/20 text-brand-accent px-2 py-0.5 rounded">{{ selectedRenewal.clientNumber }}</span>
            </div>
            <p class="text-slate-400 mt-0.5">{{ selectedRenewal.clientName }} • {{ selectedRenewal.platform }} (Revendedor: <strong class="text-amber-400">{{ selectedRenewal.resellerId }}</strong>)</p>
          </div>
          <button @click="showRenewalEditModal = false" type="button" class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-slate-800/90 hover:bg-slate-700 active:scale-95 text-slate-300 hover:text-white border border-slate-700/80 shadow-md flex items-center justify-center text-sm sm:text-base font-bold transition-all shrink-0 cursor-pointer ml-auto" title="Cerrar ventana">✕</button>
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
    <div v-if="showDispatchModal && selectedOrder" class="fixed inset-0 z-[80] flex items-center justify-center p-3 sm:p-5 pt-16 sm:pt-6 pb-6 overflow-y-auto overscroll-contain bg-black/85 backdrop-blur-md animate-in fade-in duration-200">
      <div class="bg-slate-900 border border-slate-700 w-full max-w-xl max-h-[90vh] overflow-y-auto overscroll-contain custom-scrollbar rounded-3xl p-6 shadow-2xl relative space-y-6">
        <div class="flex justify-between items-start border-b border-slate-800 pb-3">
          <div>
            <div class="flex items-center gap-2">
              <h3 class="text-xl font-black text-white">Configurar Entrega de Cuenta</h3>
              <span class="text-xs font-mono font-bold bg-brand-accent/20 text-brand-accent px-2 py-0.5 rounded">{{ selectedOrder.customerNumber }}</span>
            </div>
            <p class="text-xs text-slate-400 mt-0.5">
              Orden {{ selectedOrder.id }} • {{ selectedOrder.customerName }} 
              <span v-if="selectedOrder.gameTitle || selectedOrder.orderType === 'gaming'" class="text-indigo-300 font-bold">• 🎮 {{ selectedOrder.gameTitle || selectedOrder.platform }}</span>
              <span v-else>({{ selectedOrder.platform }})</span>
            </p>
          </div>
          <button @click="showDispatchModal = false" type="button" class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-slate-800/90 hover:bg-slate-700 active:scale-95 text-slate-300 hover:text-white border border-slate-700/80 shadow-md flex items-center justify-center text-sm sm:text-base font-bold transition-all shrink-0 cursor-pointer ml-auto" title="Cerrar ventana">✕</button>
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

    <!-- MODAL DE ÉXITO DE ENTREGA Y ENVÍO POR WHATSAPP -->
    <div v-if="showDeliveryResultModal && deliveredOrderData" class="fixed inset-0 z-[80] flex items-center justify-center p-3 sm:p-5 pt-16 sm:pt-6 pb-6 overflow-y-auto overscroll-contain bg-black/85 backdrop-blur-md animate-in fade-in duration-200">
      <div class="bg-slate-900 border border-emerald-500/40 w-full max-w-xl max-h-[90vh] overflow-y-auto overscroll-contain custom-scrollbar rounded-3xl p-6 sm:p-7 shadow-2xl relative space-y-5 text-white">
        <div class="flex justify-between items-start border-b border-slate-800 pb-3">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-xl font-black">
              ✓
            </div>
            <div>
              <h3 class="text-lg font-black text-white">¡Entrega Confirmada con Éxito!</h3>
              <p class="text-xs text-slate-400">Orden {{ deliveredOrderData.id }} • {{ deliveredOrderData.customerName }} ({{ deliveredOrderData.customerNumber }})</p>
            </div>
          </div>
          <button @click="showDeliveryResultModal = false" type="button" class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-slate-800/90 hover:bg-slate-700 active:scale-95 text-slate-300 hover:text-white border border-slate-700/80 shadow-md flex items-center justify-center text-sm sm:text-base font-bold transition-all shrink-0 cursor-pointer ml-auto" title="Cerrar ventana">✕</button>
        </div>

        <div class="space-y-4">
          <!-- Credenciales Asignadas -->
          <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 space-y-3 font-mono text-xs">
            <div class="flex justify-between items-center text-slate-400 border-b border-slate-900 pb-2">
              <span class="text-slate-200 font-bold">{{ deliveredOrderData.platform }}</span>
              <span class="text-emerald-400 font-bold">Estado: ACTIVA</span>
            </div>
            <div>
              <span class="text-slate-500 block text-[10px] uppercase">Usuario / Email:</span>
              <span class="text-white font-bold text-sm">{{ deliveredOrderData.accountDetails?.email }}</span>
            </div>
            <div>
              <span class="text-slate-500 block text-[10px] uppercase">Contraseña:</span>
              <span class="text-amber-300 font-bold text-sm">{{ deliveredOrderData.accountDetails?.pass }}</span>
            </div>
            <div v-if="deliveredOrderData.accountDetails?.profiles?.length" class="pt-1 space-y-1.5">
              <span class="text-slate-500 block text-[10px] uppercase font-sans">Perfiles y PINs asignados:</span>
              <div v-for="(p, i) in deliveredOrderData.accountDetails.profiles" :key="i" class="flex justify-between items-center bg-slate-900 p-2 rounded-xl text-xs">
                <span class="text-white font-semibold">👤 {{ p.name }}</span>
                <span class="bg-amber-400/10 text-amber-300 border border-amber-400/30 px-2 py-0.5 rounded font-bold">🔒 PIN: {{ p.pin }}</span>
              </div>
            </div>
          </div>

          <!-- Mensaje listo para WhatsApp -->
          <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 space-y-2">
            <div class="flex justify-between items-center text-xs">
              <span class="text-slate-400 font-bold">Formato Oficial de Entrega:</span>
              <button @click="copyDeliveryMessage" class="text-brand-accent hover:underline font-bold text-xs flex items-center gap-1 cursor-pointer">
                <span>📋 Copiar Todo</span>
              </button>
            </div>
            <pre class="text-[11px] text-slate-300 whitespace-pre-wrap font-sans bg-slate-900/80 p-3 rounded-xl border border-slate-800 max-h-36 overflow-y-auto leading-relaxed">{{ deliveryWhatsAppText }}</pre>
          </div>

          <!-- Acciones de Cierre -->
          <div class="flex flex-wrap gap-2 pt-1">
            <button 
              @click="sendDeliveryMessageWhatsApp" 
              class="flex-1 bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs py-3 px-4 rounded-xl transition-all shadow flex items-center justify-center gap-2 cursor-pointer"
            >
              <span>📲 Abrir WhatsApp y Enviar al Cliente</span>
            </button>
            <button 
              @click="copyDeliveryMessage" 
              class="bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-bold text-xs py-3 px-4 rounded-xl transition-all flex items-center justify-center gap-1.5 cursor-pointer"
            >
              <span>📋 Copiar</span>
            </button>
            <button 
              @click="showDeliveryResultModal = false" 
              class="bg-brand-accent hover:bg-sky-400 text-slate-950 font-black text-xs py-3 px-5 rounded-xl transition-all shadow cursor-pointer"
            >
              <span>Listo</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL: AVISAR EVENTUALIDAD A REVENDEDOR (ESTADO: YA FUE AVISADO) -->
    <div v-if="showIncidentModal" class="fixed inset-0 z-[80] flex items-center justify-center p-3 sm:p-5 pt-16 sm:pt-6 pb-6 overflow-y-auto overscroll-contain bg-black/85 backdrop-blur-md animate-in fade-in duration-200">
      <div class="bg-slate-900 border border-slate-700 w-full max-w-md max-h-[90vh] overflow-y-auto overscroll-contain custom-scrollbar rounded-3xl p-6 shadow-2xl relative space-y-4 text-xs">
        <div class="flex justify-between items-start border-b border-slate-800 pb-3">
          <div>
            <h3 class="text-xl font-black text-white">Notificar Eventualidad a Revendedor</h3>
            <p class="text-slate-400">Se enviará una alerta prioritaria al panel del revendedor marcada como [YA FUE AVISADO].</p>
          </div>
          <button @click="showIncidentModal = false" type="button" class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-slate-800/90 hover:bg-slate-700 active:scale-95 text-slate-300 hover:text-white border border-slate-700/80 shadow-md flex items-center justify-center text-sm sm:text-base font-bold transition-all shrink-0 cursor-pointer ml-auto" title="Cerrar ventana">✕</button>
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
    <div v-if="showAddResellerModal" class="fixed inset-0 z-[80] flex items-center justify-center p-3 sm:p-5 pt-16 sm:pt-6 pb-6 overflow-y-auto overscroll-contain bg-black/85 backdrop-blur-md animate-in fade-in duration-200">
      <div class="bg-slate-900 border border-slate-700 w-full max-w-lg max-h-[90vh] overflow-y-auto overscroll-contain custom-scrollbar rounded-3xl p-6 shadow-2xl relative space-y-4 text-xs">
        <div class="flex justify-between items-start border-b border-slate-800 pb-3">
          <div>
            <h3 class="text-xl font-black text-white">Alta de Nuevo Revendedor</h3>
            <p class="text-slate-400">Registra un nuevo distribuidor oficial con sus datos bancarios para transferencias.</p>
          </div>
          <button @click="showAddResellerModal = false" type="button" class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-slate-800/90 hover:bg-slate-700 active:scale-95 text-slate-300 hover:text-white border border-slate-700/80 shadow-md flex items-center justify-center text-sm sm:text-base font-bold transition-all shrink-0 cursor-pointer ml-auto" title="Cerrar ventana">✕</button>
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
    <div v-if="showRechargeModal && selectedResellerForRecharge" class="fixed inset-0 z-[80] flex items-center justify-center p-3 sm:p-5 pt-16 sm:pt-6 pb-6 overflow-y-auto overscroll-contain bg-black/85 backdrop-blur-md animate-in fade-in duration-200">
      <div class="bg-slate-900 border border-slate-700 w-full max-w-md max-h-[90vh] overflow-y-auto overscroll-contain custom-scrollbar rounded-3xl p-6 shadow-2xl relative space-y-4 text-xs">
        <div class="flex justify-between items-start border-b border-slate-800 pb-3">
          <div>
            <h3 class="text-xl font-black text-white">Cargar Saldo a Billetera Mayorista</h3>
            <p class="text-slate-400">Revendedor: <strong class="text-amber-400">[{{ selectedResellerForRecharge.id }}] {{ selectedResellerForRecharge.name }}</strong></p>
          </div>
          <button @click="showRechargeModal = false" type="button" class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-slate-800/90 hover:bg-slate-700 active:scale-95 text-slate-300 hover:text-white border border-slate-700/80 shadow-md flex items-center justify-center text-sm sm:text-base font-bold transition-all shrink-0 cursor-pointer ml-auto" title="Cerrar ventana">✕</button>
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
    <div v-if="showResellerAuthModal && selectedResellerForAuth" class="fixed inset-0 z-[80] flex items-center justify-center p-3 sm:p-5 pt-16 sm:pt-6 pb-6 overflow-y-auto overscroll-contain bg-black/85 backdrop-blur-md animate-in fade-in duration-200">
      <div class="bg-slate-900 border border-slate-700 w-full max-w-md max-h-[90vh] overflow-y-auto overscroll-contain custom-scrollbar rounded-3xl p-6 shadow-2xl relative space-y-5 text-xs">
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
          <button @click="showResellerAuthModal = false" type="button" class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-slate-800/90 hover:bg-slate-700 active:scale-95 text-slate-300 hover:text-white border border-slate-700/80 shadow-md flex items-center justify-center text-sm sm:text-base font-bold transition-all shrink-0 cursor-pointer ml-auto" title="Cerrar ventana">✕</button>
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

          <div class="p-3 bg-indigo-950/30 border border-indigo-500/20 rounded-xl text-indigo-300 text-[11px] space-y-1.5">
            <div class="flex items-start justify-between gap-2">
              <p>
                ℹ️ El revendedor utilizará estas credenciales para autenticarse en el panel 
                <router-link to="/crm" target="_blank" class="text-brand-accent underline font-bold">/crm</router-link>.
              </p>
              <button 
                type="button" 
                @click="copyModalResellerCredentials"
                class="shrink-0 text-[10px] bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 px-2 py-1 rounded-lg font-bold flex items-center gap-1 cursor-pointer transition-colors"
                title="Copiar usuario, clave y enlace"
              >
                📋 Copiar
              </button>
            </div>
            <p class="text-[10px] text-slate-400">
              💡 Como Administrador, también puedes ingresar a cualquier revendedor usando tu contraseña maestra de Admin.
            </p>
          </div>

          <div class="space-y-2 pt-2">
            <div class="flex gap-3">
              <button type="button" @click="showResellerAuthModal = false" class="w-1/3 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold py-2.5 rounded-xl cursor-pointer">
                Cancelar
              </button>
              <button type="submit" class="w-2/3 bg-slate-700 hover:bg-slate-600 text-white font-bold py-2.5 rounded-xl shadow transition-all cursor-pointer">
                Guardar Credenciales
              </button>
            </div>
            <button 
              type="button" 
              @click="saveResellerAuthAndOpenCrm" 
              class="w-full bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-400 text-white font-black py-2.5 rounded-xl shadow-lg transition-all flex items-center justify-center gap-2 cursor-pointer text-xs"
            >
              <span>🚀 Guardar y Abrir Panel en /crm</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL: GESTIÓN DE CREDENCIALES MAESTRAS DE ADMINISTRADOR -->
    <div v-if="showAdminAuthModal" class="fixed inset-0 z-[80] flex items-center justify-center p-3 sm:p-5 pt-16 sm:pt-6 pb-6 overflow-y-auto overscroll-contain bg-black/85 backdrop-blur-md animate-in fade-in duration-200">
      <div class="bg-slate-900 border border-amber-500/30 w-full max-w-md max-h-[90vh] overflow-y-auto overscroll-contain custom-scrollbar rounded-3xl p-6 shadow-2xl relative space-y-5 text-xs">
        <div class="flex justify-between items-start border-b border-slate-800 pb-3">
          <div class="flex items-center gap-2">
            <span class="text-xl">🛡️</span>
            <div>
              <h3 class="text-lg font-black text-white">Credenciales de Administrador</h3>
              <p class="text-slate-400">Acceso maestro al Centro Mayorista Proveedor.</p>
            </div>
          </div>
          <button @click="showAdminAuthModal = false" type="button" class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-slate-800/90 hover:bg-slate-700 active:scale-95 text-slate-300 hover:text-white border border-slate-700/80 shadow-md flex items-center justify-center text-sm sm:text-base font-bold transition-all shrink-0 cursor-pointer ml-auto" title="Cerrar ventana">✕</button>
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
    <div v-if="editingPlanModal" class="fixed inset-0 z-[80] flex items-center justify-center p-3 sm:p-5 pt-16 sm:pt-6 pb-6 overflow-y-auto overscroll-contain bg-black/85 backdrop-blur-md animate-in fade-in duration-200">
      <div class="bg-slate-900 border border-cyan-500/30 w-full max-w-md max-h-[90vh] overflow-y-auto overscroll-contain custom-scrollbar rounded-3xl p-6 shadow-2xl relative space-y-4 text-xs">
        <div class="flex justify-between items-start border-b border-slate-800 pb-3">
          <div>
            <span class="text-xs font-mono font-bold text-cyan-400">{{ editingPlatformKey }}</span>
            <h3 class="text-lg font-black text-white">Editar Precios y Ganancias</h3>
          </div>
          <button @click="editingPlanModal = false" type="button" class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-slate-800/90 hover:bg-slate-700 active:scale-95 text-slate-300 hover:text-white border border-slate-700/80 shadow-md flex items-center justify-center text-sm sm:text-base font-bold transition-all shrink-0 cursor-pointer ml-auto" title="Cerrar ventana">✕</button>
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
    <div v-if="showAddPlatformModal" class="fixed inset-0 z-[80] flex items-center justify-center p-3 sm:p-5 pt-16 sm:pt-6 pb-6 overflow-y-auto overscroll-contain bg-black/85 backdrop-blur-md animate-in fade-in duration-200">
      <div class="bg-slate-900 border border-cyan-500/30 w-full max-w-md max-h-[90vh] overflow-y-auto overscroll-contain custom-scrollbar rounded-3xl p-6 shadow-2xl relative space-y-4 text-xs">
        <div class="flex justify-between items-start border-b border-slate-800 pb-3">
          <div>
            <h3 class="text-lg font-black text-white">Nueva Plataforma Virtual</h3>
            <p class="text-slate-400">Registra un nuevo servicio de streaming en el catálogo.</p>
          </div>
          <button @click="showAddPlatformModal = false" type="button" class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-slate-800/90 hover:bg-slate-700 active:scale-95 text-slate-300 hover:text-white border border-slate-700/80 shadow-md flex items-center justify-center text-sm sm:text-base font-bold transition-all shrink-0 cursor-pointer ml-auto" title="Cerrar ventana">✕</button>
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
    <div v-if="showApprovedCredentialsModal && approvedCredentialsData" class="fixed inset-0 z-[80] flex items-center justify-center p-3 sm:p-5 pt-16 sm:pt-6 pb-6 overflow-y-auto overscroll-contain bg-black/85 backdrop-blur-md animate-in fade-in duration-200">
      <div class="bg-slate-900 border border-emerald-500/40 w-full max-w-lg max-h-[90vh] overflow-y-auto overscroll-contain custom-scrollbar rounded-3xl p-6 sm:p-8 shadow-2xl space-y-6 relative">
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
            @click="openResellerPanelDirect(approvedCredentialsData.reseller)" 
            class="bg-indigo-600 hover:bg-indigo-500 text-white font-black py-3 px-4 rounded-xl shadow-lg transition-all text-xs flex items-center justify-center gap-2 cursor-pointer"
          >
            <span>🚀 Abrir Panel Ahora</span>
          </button>
          <button 
            @click="sendCredentialsWhatsApp(approvedCredentialsData)" 
            class="flex-1 bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-400 hover:to-emerald-500 text-slate-950 font-black py-3 rounded-xl shadow-lg transition-all text-xs flex items-center justify-center gap-2 cursor-pointer"
          >
            <span>📲 Enviar por WhatsApp</span>
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
    <div v-if="showAddAccountModal" class="fixed inset-0 z-[80] flex items-center justify-center p-3 sm:p-5 pt-16 sm:pt-6 pb-6 overflow-y-auto overscroll-contain bg-black/85 backdrop-blur-md animate-in fade-in duration-200">
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
              <input v-model="newAccountForm.holderName" type="text" placeholder="Ej: StreamePro Central" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white outline-none focus:border-brand-accent font-medium" required>
            </div>
            <div>
              <label class="block font-bold uppercase text-slate-400 mb-1">Entidad Bancaria</label>
              <input v-model="newAccountForm.bankName" type="text" placeholder="Ej: Mercado Pago, Galicia, BBVA" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white outline-none focus:border-brand-accent font-medium" required>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold uppercase text-slate-400 mb-1">Alias CBU / CVU</label>
              <input v-model="newAccountForm.cbuAlias" type="text" placeholder="Ej: STREAMEPRO.MAYORISTA" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white outline-none focus:border-brand-accent font-mono font-bold text-amber-300" required>
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
              <input v-model="newAccountForm.whatsapp" type="text" placeholder="+54 9 291 565-9321" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white outline-none focus:border-brand-accent">
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
      @close="showFlyerModal = false; flyerInitialProduct = null" 
      :default-store-name="adminStore.adminAuth.name || 'StreamePro Mayorista'" 
      :default-phone="adminStore.providerBankInfo.whatsapp || adminStore.adminAuth.whatsapp" 
      :initial-product="flyerInitialProduct"
    />

    <!-- MODAL: PLACA DIRECTA CON LOGO SOBRE PRODUCTO -->
    <SharePlaqueModal
      :show="showSharePlaqueModal"
      :payload="sharePlaquePayload"
      :store-name="adminPlaqueContext.storeName || adminStore.adminAuth.name || 'StreamePro Oficial'"
      :reseller-ref="adminPlaqueContext.resellerRef || 'DIRECT'"
      :contact-phone="adminPlaqueContext.contactPhone || adminStore.providerBankInfo.whatsapp || adminStore.adminAuth.whatsapp || ''"
      @close="showSharePlaqueModal = false; sharePlaquePayload = null"
    />

    <!-- MODAL DE GESTIÓN: CREAR / EDITAR PRODUCTO FÍSICO O PÁGINA (ADMIN) -->
    <div v-if="showPhysicalProductModal" class="fixed inset-0 z-[80] flex items-center justify-center p-3 sm:p-5 pt-16 sm:pt-6 pb-6 overflow-y-auto overscroll-contain bg-black/85 backdrop-blur-md animate-in fade-in duration-200">
      <div class="bg-slate-900 border border-slate-700 w-full max-w-2xl rounded-3xl p-6 sm:p-7 shadow-2xl relative space-y-5 max-h-[90vh] overflow-y-auto overscroll-contain custom-scrollbar">
        <!-- Encabezado Modal -->
        <div class="flex justify-between items-start border-b border-slate-800 pb-3">
          <div>
            <h3 class="text-lg font-black text-white flex items-center gap-2">
              <span>{{ isEditingPhysicalProduct ? '✏️ Editar' : '➕ Nuevo' }} {{ physicalProductForm.adminOnly ? 'Página / Sección' : 'Producto Físico' }}</span>
            </h3>
            <p class="text-xs text-slate-400 mt-0.5">
              {{ physicalProductForm.adminOnly ? 'Configuración de página visible únicamente para administradores.' : 'Gestiona los detalles, SKU, stock, precios y descripción en catálogo.' }}
            </p>
          </div>
          <button @click="showPhysicalProductModal = false" type="button" class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-slate-800/90 hover:bg-slate-700 active:scale-95 text-slate-300 hover:text-white border border-slate-700/80 shadow-md flex items-center justify-center text-sm sm:text-base font-bold transition-all shrink-0 cursor-pointer ml-auto" title="Cerrar ventana">✕</button>
        </div>

        <form @submit.prevent="savePhysicalProductForm" class="space-y-4 text-xs">
          <!-- Visibilidad: Checkbox Solo Admin -->
          <div class="bg-slate-950 p-3 rounded-xl border border-slate-800 flex items-center justify-between">
            <div>
              <span class="font-bold text-white flex items-center gap-1.5">
                <span>🔒</span> Categoría / Visible Solo en Admin
              </span>
              <p class="text-[11px] text-slate-400">Si está marcado, este ítem no se mostrará en la tienda pública (Páginas, Políticas, Guías).</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" v-model="physicalProductForm.adminOnly" class="sr-only peer">
              <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-sky-500"></div>
            </label>
          </div>

          <!-- Título / Nombre -->
          <div>
            <label class="block text-slate-300 font-bold mb-1">Título / Nombre *</label>
            <input 
              v-model="physicalProductForm.name" 
              type="text" 
              required
              placeholder="Ej: Smartwatch D20 Macarrones o Políticas de Devolución" 
              class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-white outline-none focus:border-amber-400 text-xs"
            >
          </div>

          <!-- Categoría y SKU -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-slate-300 font-bold mb-1">Categoría</label>
              <input 
                v-model="physicalProductForm.category" 
                type="text" 
                placeholder="Ej: Accesorios Celulares, Hogar, Páginas" 
                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-white outline-none focus:border-amber-400 text-xs"
              >
            </div>
            <div>
              <label class="block text-slate-300 font-bold mb-1">SKU / Código</label>
              <input 
                v-model="physicalProductForm.sku" 
                type="text" 
                placeholder="Ej: D20-MC o PAG-01" 
                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-white outline-none focus:border-amber-400 text-xs font-mono"
              >
            </div>
          </div>

          <!-- Precios, Stock y Ajuste por Porcentaje (%) -->
          <div v-if="!physicalProductForm.adminOnly" class="bg-slate-950 p-3.5 rounded-2xl border border-slate-800 space-y-3">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <div>
                <label class="block text-amber-400 font-bold mb-1">Precio Oferta (Venta) *</label>
                <input 
                  v-model.number="physicalProductForm.priceNumeric" 
                  type="number" 
                  min="0"
                  step="1"
                  placeholder="Ej: 11990" 
                  class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-white font-mono outline-none focus:border-amber-400 text-xs"
                >
              </div>
              <div>
                <label class="block text-slate-400 font-bold mb-1">Precio Real (Tachado)</label>
                <input 
                  v-model.number="physicalProductForm.originalPriceNumeric" 
                  type="number" 
                  min="0"
                  step="1"
                  placeholder="Ej: 13000" 
                  class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-white font-mono outline-none focus:border-amber-400 text-xs"
                >
              </div>
              <div>
                <label class="block text-emerald-400 font-bold mb-1">Stock Disponible</label>
                <input 
                  v-model.number="physicalProductForm.stock" 
                  type="number" 
                  min="0" 
                  placeholder="Ej: 50" 
                  class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-white font-mono outline-none focus:border-emerald-400 text-xs"
                >
              </div>
            </div>

            <!-- Accesos rápidos de porcentaje individual -->
            <div class="pt-2 border-t border-slate-900 flex flex-wrap items-center justify-between gap-2 text-[10px]">
              <div class="flex items-center gap-1.5 flex-wrap">
                <span class="text-slate-400 font-bold">🔥 Aplicar Descuento %:</span>
                <button type="button" @click="setFormDiscountPercentage(5)" class="bg-red-500/20 hover:bg-red-500/30 text-red-300 px-2 py-0.5 rounded font-bold transition cursor-pointer">-5%</button>
                <button type="button" @click="setFormDiscountPercentage(10)" class="bg-red-500/20 hover:bg-red-500/30 text-red-300 px-2 py-0.5 rounded font-bold transition cursor-pointer">-10%</button>
                <button type="button" @click="setFormDiscountPercentage(15)" class="bg-red-500/20 hover:bg-red-500/30 text-red-300 px-2 py-0.5 rounded font-bold transition cursor-pointer">-15%</button>
                <button type="button" @click="setFormDiscountPercentage(20)" class="bg-red-500/20 hover:bg-red-500/30 text-red-300 px-2 py-0.5 rounded font-bold transition cursor-pointer">-20%</button>
                <button type="button" @click="setFormDiscountPercentage(30)" class="bg-red-500/20 hover:bg-red-500/30 text-red-300 px-2 py-0.5 rounded font-bold transition cursor-pointer">-30%</button>
              </div>
              <div class="flex items-center gap-1.5 flex-wrap">
                <span class="text-slate-400 font-bold">📈 Aumento % (hasta 150%):</span>
                <button type="button" @click="setFormMarkupPercentage(10)" class="bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 px-2 py-0.5 rounded font-bold transition cursor-pointer">+10%</button>
                <button type="button" @click="setFormMarkupPercentage(25)" class="bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 px-2 py-0.5 rounded font-bold transition cursor-pointer">+25%</button>
                <button type="button" @click="setFormMarkupPercentage(50)" class="bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 px-2 py-0.5 rounded font-bold transition cursor-pointer">+50%</button>
                <button type="button" @click="setFormMarkupPercentage(100)" class="bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 px-2 py-0.5 rounded font-bold transition cursor-pointer">+100%</button>
                <button type="button" @click="setFormMarkupPercentage(150)" class="bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 px-2 py-0.5 rounded font-bold transition cursor-pointer">+150%</button>
              </div>
            </div>
          </div>

          <!-- Slug si es página -->
          <div v-if="physicalProductForm.adminOnly" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-sky-400 font-bold mb-1">Slug URL</label>
              <input 
                v-model="physicalProductForm.pageSlug" 
                type="text" 
                placeholder="Ej: politicas-de-garantia" 
                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-white font-mono outline-none focus:border-sky-400 text-xs"
              >
            </div>
            <div>
              <label class="block text-slate-300 font-bold mb-1">Tipo de Página</label>
              <select v-model="physicalProductForm.pageType" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-white outline-none focus:border-sky-400 text-xs cursor-pointer">
                <option value="informativa">Informativa</option>
                <option value="legal">Legal / Términos</option>
                <option value="faq">Preguntas Frecuentes</option>
                <option value="comercial">Comercial / Promoción</option>
              </select>
            </div>
          </div>

          <!-- Imágenes -->
          <div class="space-y-2">
            <label class="block text-slate-300 font-bold">URL Imagen Principal</label>
            <input 
              v-model="physicalProductForm.image" 
              type="url" 
              placeholder="https://..." 
              class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-white font-mono outline-none focus:border-amber-400 text-xs"
            >
            <label class="block text-slate-400 font-bold mt-2">Galería de Imágenes (una URL por línea)</label>
            <textarea 
              v-model="physicalProductForm.galleryUrlsText" 
              rows="2"
              placeholder="https://...&#10;https://..." 
              class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-white font-mono outline-none focus:border-amber-400 text-xs custom-scrollbar"
            ></textarea>
          </div>

          <!-- Variantes / Opciones (si aplica) -->
          <div v-if="!physicalProductForm.adminOnly" class="bg-slate-950 p-3.5 rounded-2xl border border-slate-800 space-y-3">
            <div class="flex items-center justify-between">
              <span class="font-bold text-sky-400 flex items-center gap-1.5">
                <span>🎨</span> Variantes de Producto (Colores, Talles, Modelos)
              </span>
              <button 
                type="button" 
                @click="addVariableToForm"
                class="bg-sky-500/20 hover:bg-sky-500/30 text-sky-300 border border-sky-500/30 font-bold text-[11px] px-2.5 py-1 rounded-lg transition cursor-pointer"
              >
                + Añadir Opción
              </button>
            </div>

            <div v-if="physicalProductForm.variables.length === 0" class="text-slate-500 text-[11px]">
              Sin variantes configuradas. El producto se venderá como modelo único.
            </div>
            <div v-else class="space-y-2">
              <div 
                v-for="(v, vIdx) in physicalProductForm.variables" 
                :key="v.id || vIdx"
                class="bg-slate-900 p-2.5 rounded-xl border border-slate-800 flex flex-wrap items-center gap-2"
              >
                <input 
                  v-model="v.name" 
                  type="text" 
                  placeholder="Nombre de Variante (Ej: Blanco)"
                  class="flex-1 bg-slate-950 border border-slate-800 rounded-lg px-2.5 py-1.5 text-xs text-white outline-none"
                >
                <input 
                  v-model="v.sku" 
                  type="text" 
                  placeholder="SKU"
                  class="w-24 bg-slate-950 border border-slate-800 rounded-lg px-2 py-1.5 text-xs text-white font-mono outline-none"
                >
                <input 
                  v-model.number="v.stock" 
                  type="number" 
                  min="0"
                  placeholder="Stock"
                  class="w-20 bg-slate-950 border border-slate-800 rounded-lg px-2 py-1.5 text-xs text-white font-mono outline-none"
                >
                <button 
                  type="button" 
                  @click="removeVariableFromForm(vIdx)"
                  class="text-red-400 hover:text-red-300 p-1 font-bold text-xs cursor-pointer"
                  title="Quitar variante"
                >✕</button>
              </div>
            </div>
          </div>

          <!-- Descripción y Herramientas de Mejora -->
          <div class="space-y-2">
            <div class="flex flex-wrap items-center justify-between gap-2">
              <label class="block text-slate-300 font-bold">Descripción del Producto / Contenido</label>
              <div class="flex items-center gap-1.5">
                <button 
                  type="button" 
                  @click="applyDescriptionTemplate"
                  class="bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border border-amber-500/30 text-[10px] font-bold px-2 py-1 rounded-lg transition cursor-pointer"
                  title="Inserta una plantilla estructurada con especificaciones y garantías"
                >
                  ✨ Plantilla Profesional
                </button>
                <button 
                  type="button" 
                  @click="cleanDescriptionHtml"
                  class="bg-slate-800 hover:bg-slate-700 text-slate-300 text-[10px] font-bold px-2 py-1 rounded-lg transition cursor-pointer"
                  title="Limpia estilos inline y etiquetas sucias"
                >
                  🧹 Limpiar Estilos
                </button>
              </div>
            </div>
            <textarea 
              v-model="physicalProductForm.description" 
              rows="6"
              placeholder="Escribe la descripción o especificaciones en texto o HTML..." 
              class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-white font-mono outline-none focus:border-amber-400 text-xs custom-scrollbar leading-relaxed"
            ></textarea>
          </div>

          <!-- Botones de Acción -->
          <div class="flex items-center gap-3 pt-3 border-t border-slate-800">
            <button 
              type="button" 
              @click="showPhysicalProductModal = false" 
              class="flex-1 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold py-2.5 rounded-xl transition cursor-pointer"
            >
              Cancelar
            </button>
            <button 
              type="submit" 
              class="flex-1 bg-amber-400 hover:bg-amber-300 text-slate-950 font-black py-2.5 rounded-xl transition shadow-lg cursor-pointer"
            >
              {{ isEditingPhysicalProduct ? 'Guardar Cambios' : 'Crear Producto' }}
            </button>
          </div>
        </form>
      </div>
    </div>

        <!-- MODAL: AJUSTE MASIVO POR PORCENTAJE (%) PARA TODOS LOS PRODUCTOS -->
    <div v-if="showBulkPercentageModal" class="fixed inset-0 z-[80] flex items-center justify-center p-3 sm:p-5 pt-16 sm:pt-6 pb-6 overflow-y-auto overscroll-contain bg-black/85 backdrop-blur-md animate-in fade-in duration-200">
      <div class="bg-slate-900 border border-slate-700 w-full max-w-xl rounded-3xl p-6 sm:p-7 shadow-2xl relative space-y-5 max-h-[90vh] overflow-y-auto overscroll-contain custom-scrollbar">
        <!-- Encabezado Modal -->
        <div class="flex justify-between items-start border-b border-slate-800 pb-3">
          <div class="space-y-1">
            <div class="flex items-center gap-2">
              <span class="text-xl">🏷️</span>
              <h3 class="text-lg font-black text-white">Opción de Porcentaje (%) para Productos</h3>
            </div>
            <p class="text-xs text-slate-400">
              Aplica hasta 150% de aumento conservando la proporción de descuentos existentes, o programa ofertas sobre precios de lista.
            </p>
          </div>
          <button @click="showBulkPercentageModal = false" type="button" class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-slate-800/90 hover:bg-slate-700 active:scale-95 text-slate-300 hover:text-white border border-slate-700/80 shadow-md flex items-center justify-center text-sm sm:text-base font-bold transition-all shrink-0 cursor-pointer ml-auto" title="Cerrar ventana">✕</button>
        </div>

        <form @submit.prevent="applyBulkPercentage" class="space-y-4 text-xs">
          <!-- Acción a realizar -->
          <div class="space-y-2">
            <label class="block text-slate-300 font-bold">Tipo de Ajuste por Porcentaje:</label>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
              <button 
                type="button" 
                @click="bulkPercentageForm.action = 'increase'"
                class="p-3 rounded-xl border text-left transition flex flex-col justify-between gap-1 cursor-pointer"
                :class="bulkPercentageForm.action === 'increase' ? 'bg-emerald-500/20 border-emerald-500 text-white shadow' : 'bg-slate-950 border-slate-800 text-slate-400 hover:border-slate-700'"
              >
                <span class="font-bold text-xs flex items-center gap-1 text-emerald-400">
                  <span>📈</span> Aumento / Ganancias
                </span>
                <span class="text-[10px] text-slate-300">Aumento de hasta 150% manteniendo proporción de descuentos existentes.</span>
              </button>

              <button 
                type="button" 
                @click="bulkPercentageForm.action = 'discount'"
                class="p-3 rounded-xl border text-left transition flex flex-col justify-between gap-1 cursor-pointer"
                :class="bulkPercentageForm.action === 'discount' ? 'bg-red-500/20 border-red-500 text-white shadow' : 'bg-slate-950 border-slate-800 text-slate-400 hover:border-slate-700'"
              >
                <span class="font-bold text-xs flex items-center gap-1 text-red-400">
                  <span>🔥</span> Oferta / Descuento
                </span>
                <span class="text-[10px] text-slate-300">Aplica sobre precio de lista. Nunca descuenta sobre descuentos ni baja del costo final.</span>
              </button>

              <button 
                type="button" 
                @click="bulkPercentageForm.action = 'restore'"
                class="p-3 rounded-xl border text-left transition flex flex-col justify-between gap-1 cursor-pointer"
                :class="bulkPercentageForm.action === 'restore' ? 'bg-sky-500/20 border-sky-500 text-white shadow' : 'bg-slate-950 border-slate-800 text-slate-400 hover:border-slate-700'"
              >
                <span class="font-bold text-xs flex items-center gap-1 text-sky-400">
                  <span>🔄</span> Restaurar Fábrica
                </span>
                <span class="text-[10px] text-slate-300">Vuelve a los precios base oficiales de revende.com.ar.</span>
              </button>
            </div>
          </div>

          <!-- Selector de Porcentaje con Botones Rápidos (si no es restore) -->
          <div v-if="bulkPercentageForm.action !== 'restore'" class="bg-slate-950 p-4 rounded-2xl border border-slate-800 space-y-3">
            <div class="flex items-center justify-between">
              <label class="font-bold text-white">
                {{ bulkPercentageForm.action === 'increase' ? 'Porcentaje de Aumento (hasta 150%):' : 'Porcentaje de Descuento Promocional (%):' }}
              </label>
              <div class="flex items-center gap-1.5">
                <input 
                  v-model.number="bulkPercentageForm.percentage" 
                  type="number" 
                  min="1" 
                  :max="bulkPercentageForm.action === 'increase' ? 150 : 90" 
                  required
                  class="w-20 bg-slate-900 border border-slate-700 rounded-xl px-3 py-1.5 text-center text-sm font-black font-mono text-amber-400 outline-none focus:border-amber-400"
                >
                <span class="text-sm font-bold text-amber-400">%</span>
              </div>
            </div>

            <!-- Botones Rápidos Dinámicos según Aumento o Descuento -->
            <div class="flex flex-wrap items-center gap-1.5 pt-1">
              <span class="text-[10px] text-slate-400 font-bold mr-1">Rápidos:</span>
              <template v-if="bulkPercentageForm.action === 'increase'">
                <button 
                  v-for="p in [10, 25, 40, 50, 75, 100, 125, 150]" 
                  :key="p"
                  type="button"
                  @click="bulkPercentageForm.percentage = p"
                  class="px-2.5 py-1 rounded-lg text-xs font-bold border transition cursor-pointer"
                  :class="bulkPercentageForm.percentage === p ? 'bg-amber-400 text-slate-950 border-amber-300 font-black' : 'bg-slate-900 text-slate-300 border-slate-800 hover:border-slate-600'"
                >
                  +{{ p }}%
                </button>
              </template>
              <template v-else>
                <button 
                  v-for="p in [5, 10, 15, 20, 25, 30, 40, 50]" 
                  :key="p"
                  type="button"
                  @click="bulkPercentageForm.percentage = p"
                  class="px-2.5 py-1 rounded-lg text-xs font-bold border transition cursor-pointer"
                  :class="bulkPercentageForm.percentage === p ? 'bg-amber-400 text-slate-950 border-amber-300 font-black' : 'bg-slate-900 text-slate-300 border-slate-800 hover:border-slate-600'"
                >
                  -{{ p }}%
                </button>
              </template>
            </div>
          </div>

          <!-- Alcance y Redondeo -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-slate-300 font-bold mb-1">Aplicar a:</label>
              <select v-model="bulkPercentageForm.categoryId" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-white outline-none focus:border-amber-400 text-xs cursor-pointer">
                <option value="All">Todos los Productos Físicos ({{ publicPhysicalProductsCount }})</option>
                <option v-for="cat in adminStore.physicalCategories.filter(c => !c.adminOnly)" :key="cat.id" :value="cat.id">
                  {{ cat.name }} ({{ cat.cantidad }})
                </option>
              </select>
            </div>
            <div>
              <label class="block text-slate-300 font-bold mb-1">Redondeo en Pesos ($):</label>
              <select v-model.number="bulkPercentageForm.roundTo" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-white outline-none focus:border-amber-400 text-xs cursor-pointer">
                <option :value="10">Redondear a $10 más cercano (Ej: $11.990)</option>
                <option :value="50">Redondear a $50 más cercano (Ej: $11.950)</option>
                <option :value="100">Redondear a $100 más cercano (Ej: $12.000)</option>
                <option :value="1">Exacto sin redondeo</option>
              </select>
            </div>
          </div>

          <!-- Simulación / Previsualización de Muestra -->
          <div class="bg-slate-950 p-3.5 rounded-2xl border border-slate-800 space-y-2">
            <div class="flex items-center justify-between text-[11px]">
              <span class="font-bold text-slate-400 flex items-center gap-1">
                <span>👁️</span> Simulación de Precios en Vivo:
              </span>
              <span class="text-emerald-400 font-mono font-bold">
                {{ bulkPercentageForm.action === 'restore' ? 'Restaurar' : (bulkPercentageForm.action === 'discount' ? '-' + bulkPercentageForm.percentage + '% OFF' : '+' + bulkPercentageForm.percentage + '% Ganancia') }}
              </span>
            </div>
            <div class="space-y-1.5 text-xs">
              <div 
                v-for="sample in bulkPercentagePreviewList" 
                :key="sample.id"
                class="flex items-center justify-between bg-slate-900/80 px-3 py-1.5 rounded-xl border border-slate-800/80"
              >
                <span class="text-white truncate max-w-[200px]" :title="sample.name">{{ sample.name }}</span>
                <div class="flex items-center gap-2 font-mono shrink-0">
                  <span class="text-slate-500 line-through text-[11px]">${{ sample.currentPrice.toLocaleString() }}</span>
                  <span class="text-amber-400 font-black">${{ sample.newPrice.toLocaleString() }}</span>
                  <span class="text-[10px] font-bold" :class="sample.diff >= 0 ? 'text-emerald-400' : 'text-red-400'">
                    {{ sample.diff >= 0 ? '+' : '' }}${{ sample.diff.toLocaleString() }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Botones de Acción -->
          <div class="flex items-center gap-3 pt-3 border-t border-slate-800">
            <button 
              type="button" 
              @click="showBulkPercentageModal = false" 
              class="flex-1 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold py-2.5 rounded-xl transition cursor-pointer"
            >
              Cancelar
            </button>
            <button 
              type="submit" 
              class="flex-1 bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-slate-950 font-black py-2.5 rounded-xl transition shadow-lg flex items-center justify-center gap-1.5 cursor-pointer"
            >
              <span>✅ Aplicar {{ bulkPercentageForm.action === 'restore' ? 'Restauración' : (bulkPercentageForm.action === 'increase' ? '+' + bulkPercentageForm.percentage + '% de Aumento' : '-' + bulkPercentageForm.percentage + '% de Descuento') }} a Productos</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL: PREVISUALIZADOR DE PÁGINA / CONTENIDO (SOLO ADMIN) -->
    <div v-if="showPagePreviewModal && previewingPage" class="fixed inset-0 z-[80] flex items-center justify-center p-3 sm:p-5 pt-16 sm:pt-6 pb-6 overflow-y-auto overscroll-contain bg-black/85 backdrop-blur-md animate-in fade-in duration-200">
      <div class="bg-slate-900 border border-slate-700 w-full max-w-2xl rounded-3xl p-6 sm:p-7 shadow-2xl relative space-y-5 max-h-[90vh] overflow-y-auto overscroll-contain custom-scrollbar">
        <div class="flex justify-between items-start border-b border-slate-800 pb-3">
          <div class="space-y-1">
            <div class="flex items-center gap-2">
              <span class="bg-sky-500/20 text-sky-300 text-[10px] font-bold uppercase px-2 py-0.5 rounded border border-sky-500/30">
                📄 Página Solo Admin
              </span>
              <span class="text-slate-400 text-xs font-mono">/{{ previewingPage.pageSlug || previewingPage.sku }}</span>
            </div>
            <h3 class="text-lg font-black text-white leading-snug">{{ previewingPage.name }}</h3>
          </div>
          <button @click="showPagePreviewModal = false" type="button" class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-slate-800/90 hover:bg-slate-700 active:scale-95 text-slate-300 hover:text-white border border-slate-700/80 shadow-md flex items-center justify-center text-sm sm:text-base font-bold transition-all shrink-0 cursor-pointer ml-auto" title="Cerrar ventana">✕</button>
        </div>

        <div class="bg-slate-950 p-5 rounded-2xl border border-slate-800 space-y-4">
          <div 
            class="text-slate-300 leading-relaxed space-y-3 [&>p]:mb-2 [&>h3]:text-base [&>h3]:font-bold [&>h3]:text-white [&>h4]:text-sm [&>h4]:font-bold [&>h4]:text-amber-400 [&>ul]:list-disc [&>ul]:pl-5 [&>ul]:space-y-1"
            v-html="previewingPage.description"
          ></div>
        </div>

        <div class="flex justify-between items-center pt-2 border-t border-slate-800">
          <button 
            type="button"
            @click="copyPageContent(previewingPage)"
            class="bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold px-4 py-2 rounded-xl transition flex items-center gap-1.5 cursor-pointer"
          >
            <span>📋 Copiar Texto Completo</span>
          </button>
          <button 
            type="button" 
            @click="showPagePreviewModal = false"
            class="bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-xs px-5 py-2 rounded-xl transition cursor-pointer"
          >
            Cerrar
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL DE ASIGNACIÓN / EDICIÓN DE NOMBRE DEL JUEGO GAMER -->
    <div v-if="showAssignGameModal && targetGameOrder" class="fixed inset-0 z-[85] flex items-center justify-center p-3 sm:p-5 pt-16 sm:pt-6 pb-6 overflow-y-auto overscroll-contain bg-black/85 backdrop-blur-md animate-in fade-in duration-150">
      <div class="bg-[#0b0f19] border border-indigo-500/30 rounded-3xl max-w-lg w-full max-h-[90vh] overflow-y-auto custom-scrollbar p-6 space-y-5 shadow-2xl">
        <div class="flex justify-between items-center pb-3 border-b border-slate-800">
          <div>
            <h3 class="text-lg font-black text-white flex items-center gap-2">
              <span>🎮</span>
              <span>Asignar Videojuego a Orden #{{ targetGameOrder.id }}</span>
            </h3>
            <p class="text-xs text-slate-400 mt-0.5">
              Cliente: <strong class="text-white">{{ targetGameOrder.customerName }}</strong> • {{ targetGameOrder.customerPhone }}
            </p>
          </div>
          <button @click="showAssignGameModal = false" type="button" class="w-9 h-9 rounded-xl bg-slate-800/90 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700/80 flex items-center justify-center text-sm font-bold transition cursor-pointer" title="Cerrar">✕</button>
        </div>

        <div class="space-y-4">
          <!-- Campo Nombre del Videojuego -->
          <div>
            <label class="block text-xs font-bold uppercase text-slate-400 mb-1">Nombre Exacto del Videojuego a Entregar</label>
            <input 
              v-model="assignGameTitleInput" 
              type="text" 
              placeholder="Ej: Grand Theft Auto V, EA Sports FC 25, God of War..." 
              class="w-full bg-slate-900 border border-slate-700 rounded-xl p-3 text-sm text-white font-bold outline-none focus:border-indigo-500"
            >
          </div>

          <!-- Selector Rápido desde el Catálogo de 960 Juegos -->
          <div class="space-y-2">
            <div class="flex justify-between items-center">
              <label class="text-[11px] font-bold uppercase text-indigo-300">Seleccionar desde el Catálogo ({{ adminStore.processedGamingProducts.length }} Juegos)</label>
              <span class="text-[10px] text-slate-500">Click para autocompletar</span>
            </div>
            <input 
              v-model="gameCatalogSearchQuery" 
              type="text" 
              placeholder="Buscar en catálogo (ej: Spider, FIFA, Mortal, Dragon, GTA...)" 
              class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2 text-xs text-white outline-none focus:border-indigo-500"
            >
            <div class="max-h-40 overflow-y-auto space-y-1 pr-1 custom-scrollbar">
              <button 
                v-for="game in filteredCatalogGamesForPicker" 
                :key="game.id" 
                type="button"
                @click="selectCatalogGameForOrder(game)"
                :class="[
                  'w-full text-left p-2 rounded-lg text-xs font-bold transition flex items-center justify-between cursor-pointer',
                  assignGameTitleInput === game.title ? 'bg-indigo-600 text-white' : 'bg-slate-900/60 hover:bg-slate-800 text-slate-300'
                ]"
              >
                <span class="truncate">{{ game.title }}</span>
                <span class="text-[10px] text-slate-400 font-mono shrink-0 ml-2">{{ game.platform }}</span>
              </button>
            </div>
          </div>

          <!-- Plataforma y Modalidad -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold uppercase text-slate-400 mb-1">Plataforma Consola</label>
              <select v-model="assignGamePlatformInput" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-xs text-white font-bold outline-none cursor-pointer">
                <option value="PS4 / PS5">PS4 / PS5</option>
                <option value="PS5">PlayStation 5</option>
                <option value="PS4">PlayStation 4</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold uppercase text-slate-400 mb-1">Modalidad de Cuenta</label>
              <select v-model="assignGameLicenseInput" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-xs text-white font-bold outline-none cursor-pointer">
                <option value="primary">⭐️ Cuenta Primaria</option>
                <option value="secondary">⚡️ Cuenta Secundaria</option>
              </select>
            </div>
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t border-slate-800">
          <button 
            type="button" 
            @click="showAssignGameModal = false" 
            class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold transition cursor-pointer"
          >
            Cancelar
          </button>
          <button 
            type="button" 
            @click="saveAssignedGameToOrder" 
            class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-black shadow-lg shadow-indigo-600/30 transition flex items-center gap-1.5 cursor-pointer"
          >
            <span>💾 Guardar Videojuego</span>
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL: VISUALIZADOR DE COMPROBANTES EN MINI NUBE (JPG / PDF) -->
    <ReceiptViewerModal 
      :show="showReceiptModal"
      :order="selectedReceiptOrder"
      @close="showReceiptModal = false"
      @confirmed-close="handleConfirmCloseFromReceipt"
      @receipt-purged="handleReceiptPurged"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useCartStore } from '../store/cart'
import { useAdminStore, PLATFORM_PLANS } from '../store/admin'
import { useToastStore } from '../store/toast'
import { sendLocalNotification } from '../services/notifications'
import { saveReceipt, processReceiptFile } from '../services/receiptCloudStorage'
import FlyerGeneratorModal from '../components/FlyerGeneratorModal.vue'

const route = useRoute()
const router = useRouter()
const cartStore = useCartStore()
const adminStore = useAdminStore()
const toast = useToastStore()

const showFlyerModal = ref(false)
const flyerInitialProduct = ref(null)

const openFlyerForPackage = (pkg) => {
  flyerInitialProduct.value = {
    itemType: 'package',
    ...pkg
  }
  showFlyerModal.value = true
}

const openFlyerForPhysical = (prod) => {
  flyerInitialProduct.value = prod
  showFlyerModal.value = true
}

// Comprobante de Pago en Mini Nube (JPG, PNG, WEBP, PDF)
const receiptFile = ref(null) // { fileName, fileType, fileSize, dataUrl }
const isProcessingReceipt = ref(false)

const handleReceiptFileChange = async (event) => {
  const file = event.target.files?.[0]
  if (!file) return

  if (file.size > 15 * 1024 * 1024) {
    toast.add('El archivo es demasiado grande (máximo 15 MB)', 'error')
    return
  }

  isProcessingReceipt.value = true
  try {
    const processed = await processReceiptFile(file)
    receiptFile.value = processed
    toast.add(`✓ Comprobante cargado a la mini nube: ${processed.fileName}`, 'success')
  } catch (err) {
    toast.add(err.message || 'Error al procesar el archivo de comprobante', 'error')
    receiptFile.value = null
  } finally {
    isProcessingReceipt.value = false
  }
}

const removeReceiptFile = () => {
  receiptFile.value = null
}

const products = ref([
  { 
    id: 1, 
    name: 'Netflix Premium Ultra HD', 
    category: 'Streaming', 
    platform: 'Netflix',
    description: 'Calidad 4K HDR con audio espacial y descargas offline.',
    plans: PLATFORM_PLANS.Netflix.plans
  },
  { 
    id: 2, 
    name: 'Spotify Familiar Individual', 
    category: 'Musica', 
    platform: 'Spotify',
    description: 'Música ilimitada sin anuncios y reproducción offline.',
    plans: PLATFORM_PLANS.Spotify.plans
  },
  { 
    id: 3, 
    name: 'Max Platino Estándar', 
    category: 'Streaming', 
    platform: 'Max',
    description: 'Series de HBO, películas de Warner y transmisiones en vivo.',
    plans: PLATFORM_PLANS.Max.plans
  },
  { 
    id: 4, 
    name: 'Disney+ / Star+ Combo', 
    category: 'Streaming', 
    platform: 'Disney+',
    description: 'Todo el contenido de Disney, Pixar, Marvel y deportes ESPN.',
    plans: PLATFORM_PLANS['Disney+'].plans
  },
  { 
    id: 5, 
    name: 'IPTV MagisTV Pro 1 Mes', 
    category: 'IPTV', 
    platform: 'IPTV',
    description: '+1200 canales en vivo, FHD, 24/7 y eventos deportivos.',
    plans: PLATFORM_PLANS.IPTV.plans
  },
  { 
    id: 6, 
    name: 'Amazon Prime Video 4K', 
    category: 'Streaming', 
    platform: 'Prime Video',
    description: 'Series Amazon Originals, estrenos y películas en 4K UHD.',
    plans: PLATFORM_PLANS['Prime Video']?.plans || {}
  },
  { 
    id: 7, 
    name: 'YouTube Premium & Music', 
    category: 'Streaming', 
    platform: 'YouTube Premium',
    description: 'Videos sin publicidad, descargas y YouTube Music en segundo plano.',
    plans: PLATFORM_PLANS['YouTube Premium']?.plans || {},
    requiresActivationEmail: true
  },
  { 
    id: 8, 
    name: 'Canva Pro', 
    category: 'Diseño & Productividad', 
    platform: 'Canva Pro',
    description: 'Acceso ilimitado a plantillas premium, eliminador de fondos con 1 clic y kit de marcas oficial a tu propio correo.',
    plans: PLATFORM_PLANS['Canva Pro']?.plans || {},
    requiresActivationEmail: true
  }
])

// Modal de Configuración
const selectedProduct = ref(null)
const selectedPlanKey = ref('1_perfil')
const customProfiles = ref([])
const showConfigModal = ref(false)
const pinPreference = ref('same')
const isProfilesExpanded = ref(true)
const activationEmail = ref('')

// Modal de Ficha Técnica / +info para Streaming
const showStreamingInfoModal = ref(false)
const selectedStreamingInfo = ref(null)

const openStreamingInfo = (product) => {
  selectedStreamingInfo.value = product
  showStreamingInfoModal.value = true
}

const openConfigFromInfo = () => {
  if (selectedStreamingInfo.value) {
    const prod = selectedStreamingInfo.value
    showStreamingInfoModal.value = false
    openConfig(prod)
  }
}

// Sección Activa en Tienda: 'all' (Todo el Catálogo) | 'digital' (Streaming) | 'physical' (Productos Físicos) | 'packages' (Combos)
const storeSection = ref('all')

// Modal de Detalle de Producto Físico
const selectedPhysicalProduct = ref(null)
const showPhysicalModal = ref(false)
const showImageZoomModal = ref(false)
const physicalQuantity = ref(1)

// Modal del Carrito previo al envío
const showCartDrawer = ref(false)

// Modal y Formulario Checkout
const showCheckoutModal = ref(false)
const showOrderSuccessModal = ref(false)
const lastPlacedOrder = ref(null)

const getInitialClientForm = () => {
  let saved = null
  try {
    const raw = localStorage.getItem('streampro_user_profile')
    if (raw) saved = JSON.parse(raw)
  } catch {}

  return {
    clientNumber: saved?.clientNumber || `CLI-${Math.floor(1000 + Math.random() * 9000)}`,
    fullName: saved?.name || '',
    whatsapp: saved?.phone || '',
    pin: saved?.pin || '',
    shippingAddress: saved?.address || '',
    selectedResellerId: 'DIRECT',
    paymentMethod: 'Transferencia Bancaria / CBU',
    transferVoucher: '',
    isSharedUrl: false
  }
}

const clientForm = ref(getInitialClientForm())

// GESTIÓN DE PERFIL REGISTRADO / INVITACIÓN DE REGISTRO
const showEditProfileInputs = ref(false)
const showQuickLogin = ref(false)
const quickLoginPhone = ref('')
const quickLoginPin = ref('')

const hasSavedProfile = computed(() => {
  return Boolean(clientForm.value.fullName?.trim() && clientForm.value.whatsapp?.trim())
})

const handleLogoutProfile = () => {
  try {
    localStorage.removeItem('streampro_user_profile')
  } catch {}
  clientForm.value.fullName = ''
  clientForm.value.whatsapp = ''
  clientForm.value.pin = ''
  clientForm.value.shippingAddress = ''
  clientForm.value.clientNumber = `CLI-${Math.floor(1000 + Math.random() * 9000)}`
  showEditProfileInputs.value = true
  toast.add('Sesión cerrada. Puedes registrar nuevos datos.', 'info')
}

const handleQuickLogin = () => {
  const phone = (quickLoginPhone.value || '').replace(/\D/g, '')
  const pin = (quickLoginPin.value || '').trim()

  if (!phone) {
    toast.add('Ingresa tu número de WhatsApp de compra', 'error')
    return
  }

  // 1. Buscar en Directorio de Usuarios Registrados
  let user = adminStore.registeredUsers.find(u => {
    const uPhone = (u.phone || '').replace(/\D/g, '')
    return uPhone.includes(phone) || phone.includes(uPhone)
  })

  // 2. Si no está en registeredUsers, buscar en órdenes previas
  if (!user) {
    const order = adminStore.orders.find(o => {
      const oPhone = (o.customerPhone || '').replace(/\D/g, '')
      return oPhone.includes(phone) || phone.includes(oPhone)
    })
    if (order) {
      user = {
        name: order.customerName,
        phone: order.customerPhone,
        clientNumber: order.customerNumber,
        pin: order.pin,
        address: order.shippingAddress || ''
      }
    }
  }

  if (user) {
    // Si la cuenta tiene PIN registrado y el usuario ingresó uno, verificar
    if (user.pin && pin && user.pin !== pin) {
      toast.add('El PIN ingresado no coincide con tu cuenta', 'error')
      return
    }

    clientForm.value.fullName = user.name
    clientForm.value.whatsapp = user.phone
    clientForm.value.clientNumber = user.clientNumber || clientForm.value.clientNumber
    if (user.pin) clientForm.value.pin = user.pin
    if (user.address) clientForm.value.shippingAddress = user.address

    try {
      localStorage.setItem('streampro_user_profile', JSON.stringify({
        clientNumber: clientForm.value.clientNumber,
        name: user.name,
        phone: user.phone,
        pin: clientForm.value.pin,
        address: clientForm.value.shippingAddress
      }))
    } catch {}

    showQuickLogin.value = false
    showEditProfileInputs.value = false
    toast.add(`¡Bienvenido de vuelta, ${user.name}! Tus datos fueron cargados.`, 'success')
  } else {
    toast.add('No encontramos una cuenta con ese WhatsApp. Por favor completa tus datos abajo para registrarte.', 'info')
    showEditProfileInputs.value = true
  }
}

// CONTROL DE SCROLL ESTÁTICO DE LA PÁGINA AL ABRIR VENTANAS / MODALES
const isAnyModalOpen = computed(() => {
  return showCheckoutModal.value ||
    showOrderSuccessModal.value ||
    showCartDrawer.value ||
    showConfigModal.value ||
    showStreamingInfoModal.value ||
    showPhysicalModal.value ||
    showImageZoomModal.value ||
    showFlyerModal.value
})

watch(isAnyModalOpen, (isOpen) => {
  if (typeof document !== 'undefined') {
    document.body.style.overflow = isOpen ? 'hidden' : ''
  }
})

onUnmounted(() => {
  if (typeof document !== 'undefined') {
    document.body.style.overflow = ''
  }
})

// Determina si es venta directa oficial (sin intermediario) o referida por revendedor
const isDirectSale = computed(() => {
  return !clientForm.value.selectedResellerId || clientForm.value.selectedResellerId === 'DIRECT'
})

// Datos del Revendedor Seleccionado (si es venta referida)
const currentReseller = computed(() => {
  if (isDirectSale.value) return null
  return adminStore.resellers.find(r => r.id === clientForm.value.selectedResellerId) || null
})

// Nombre comercial limpio de la tienda para los clientes
const resellerStoreName = computed(() => {
  if (isDirectSale.value) {
    return 'StreamePro Tienda Oficial'
  }
  const r = currentReseller.value
  if (!r) return 'StreamePro Tienda Oficial'
  if (r.storeName) return r.storeName
  return r.name.replace(/\s*\(Distribuidor\s*#?\d+\)/gi, '').replace(/^\[.*?\]\s*/, '').trim()
})

// Base URL canónica oficial para producción (streamepro.me)
const getBaseOrigin = () => {
  if (typeof window !== 'undefined' && (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1')) {
    return window.location.origin
  }
  return 'https://streamepro.me'
}

// Resolver revendedor con máxima flexibilidad (ID, username, tienda o número correlativo)
const resolveReseller = (param) => {
  if (!param) return null
  const q = String(param).trim().toLowerCase()
  if (!q || q === 'direct' || q === 'oficial' || q === 'null' || q === 'undefined') return null

  // 1. Coincidencia exacta de ID (RES-01, res-01)
  let found = adminStore.resellers.find(r => r.id?.toLowerCase() === q)
  if (found) return found

  // 2. Coincidencia por username (distribuidor, carlos)
  found = adminStore.resellers.find(r => r.username?.toLowerCase() === q)
  if (found) return found

  // 3. Coincidencia por storeName o nombre
  found = adminStore.resellers.find(r => 
    r.storeName?.toLowerCase() === q || 
    r.name?.toLowerCase() === q
  )
  if (found) return found

  // 4. Coincidencia por número correlativo (ej: ?ref=1 o ?ref=01 -> RES-01)
  const digits = q.replace(/\D/g, '')
  if (digits.length > 0) {
    found = adminStore.resellers.find(r => {
      const rDigits = r.id?.replace(/\D/g, '')
      return rDigits && parseInt(rDigits, 10) === parseInt(digits, 10)
    })
    if (found) return found
  }

  return null
}

// Detección de Parámetros de URL: Afiliado (?ref=RES-01) y Producto compartido (?prod=962)
const applyRouteParams = () => {
  const refParam = route.query.ref
  if (refParam) {
    const cleanRef = String(refParam).trim().toLowerCase()
    if (cleanRef === 'direct' || cleanRef === 'oficial') {
      clientForm.value.selectedResellerId = 'DIRECT'
      try {
        localStorage.removeItem('streamepro_referral')
      } catch (e) {}
    } else {
      const matchedRes = resolveReseller(cleanRef)
      if (matchedRes) {
        clientForm.value.selectedResellerId = matchedRes.id
        try {
          localStorage.setItem('streamepro_referral', matchedRes.id)
        } catch (e) {}
        toast.add(`Atendido por distribuidor oficial: ${matchedRes.name}`, 'info')
      }
    }
  } else {
    // Si no viene en la URL, verificar si tenía referido previo guardado en memoria
    try {
      const savedRef = localStorage.getItem('streamepro_referral')
      if (savedRef) {
        const matchedRes = resolveReseller(savedRef)
        if (matchedRes) {
          clientForm.value.selectedResellerId = matchedRes.id
        } else {
          clientForm.value.selectedResellerId = 'DIRECT'
        }
      } else {
        clientForm.value.selectedResellerId = 'DIRECT'
      }
    } catch (e) {
      clientForm.value.selectedResellerId = 'DIRECT'
    }
  }

  // Redirección y activación de sección física, digital, paquetes o todo
  if (route.query.sec === 'physical') {
    storeSection.value = 'physical'
  } else if (route.query.sec === 'digital') {
    storeSection.value = 'digital'
  } else if (route.query.sec === 'packages' || route.query.pkg) {
    storeSection.value = 'packages'
    if (route.query.pkg) {
      clientForm.value.isSharedUrl = true
      toast.add('¡Mostrando paquete promocional seleccionado!', 'info')
    }
  } else if (route.query.sec === 'all' || !route.query.sec) {
    storeSection.value = 'all'
  }

  // Previsualización y apertura automática de producto físico compartido
  const sharedProdId = route.query.prod || route.query.p
  if (sharedProdId) {
    clientForm.value.isSharedUrl = true
    storeSection.value = 'physical'
    const cleanId = String(sharedProdId).trim()
    const foundProd = adminStore.physicalProducts.find(p => String(p.id).trim() === cleanId)
    if (foundProd) {
      openPhysicalModal(foundProd)
      toast.add(`¡Bienvenido! Mostrando: ${foundProd.name.slice(0, 32)}...`, 'info')
    }
  }
}

onMounted(() => {
  adminStore.ensurePhysicalProductsLoaded()
  applyRouteParams()
})

watch(() => route.query, () => {
  applyRouteParams()
}, { deep: true })

const openConfig = (product) => {
  selectedProduct.value = product
  const firstKey = Object.keys(product.plans)[0]
  selectedPlanKey.value = firstKey
  pinPreference.value = 'same'
  activationEmail.value = ''
  updateProfilesList(product.plans[firstKey])
  isProfilesExpanded.value = true
  showConfigModal.value = true
}

const onPlanChange = () => {
  const planData = selectedProduct.value.plans[selectedPlanKey.value]
  updateProfilesList(planData)
}

const updateProfilesList = (planData) => {
  const units = planData.units
  const type = planData.type
  const platform = selectedProduct.value.platform.toUpperCase()

  customProfiles.value = Array.from({ length: units }, (_, idx) => ({
    name: type === 'perfil' 
      ? `PERFIL ${idx + 1} - ${platform}` 
      : `DISPOSITIVO ${idx + 1} - ${platform}`
  }))
}

const confirmAddToCart = () => {
  const planData = selectedProduct.value.plans[selectedPlanKey.value]
  
  if ((selectedProduct.value.requiresActivationEmail || selectedProduct.value.platform === 'Canva Pro' || selectedProduct.value.platform === 'YouTube Premium') && !activationEmail.value.trim()) {
    toast.add('Por favor, ingresa el correo asociado a tu cuenta para recibir la activación', 'warning')
    return
  }

  cartStore.addItem(
    selectedProduct.value,
    selectedPlanKey.value,
    planData,
    customProfiles.value.map((p, idx) => ({ name: p.name.trim() || `PERFIL ${idx + 1} - ${selectedProduct.value.platform.toUpperCase()}` })),
    activationEmail.value.trim()
  )

  const lastItem = cartStore.items[cartStore.items.length - 1]
  if (lastItem) {
    lastItem.pinPreference = pinPreference.value
    lastItem.activationEmail = activationEmail.value.trim()
  }

  toast.add(`${selectedProduct.value.name} (${planData.label}) añadido al carrito`, 'success')
  showConfigModal.value = false
}

// GESTIÓN DE PRODUCTOS FÍSICOS
const openPhysicalModal = (product) => {
  selectedPhysicalProduct.value = product
  physicalQuantity.value = 1
  showPhysicalModal.value = true
}

const addPhysicalToCart = (product, qty = 1) => {
  cartStore.addPhysicalItem(product, qty)
  toast.add(`"${product.name.slice(0, 30)}..." añadido al carrito`, 'success')
  showPhysicalModal.value = false
}

const getProductDirectUrl = (product) => {
  if (!product) return ''
  const origin = getBaseOrigin()
  const refCode = isDirectSale.value ? '' : clientForm.value.selectedResellerId
  return refCode ? `${origin}/p/${product.id}?ref=${refCode}` : `${origin}/p/${product.id}`
}

const getProductShortUrl = (product) => {
  if (!product) return ''
  const resId = isDirectSale.value ? '' : clientForm.value.selectedResellerId
  return resId ? `https://spro.li/producto/${product.id}/${resId}` : `https://spro.li/p/${product.id}`
}

const copyProductShortUrl = (product) => {
  const shortUrl = getProductShortUrl(product)
  if (navigator?.clipboard?.writeText) {
    navigator.clipboard.writeText(shortUrl).then(() => {
      toast.add('¡Enlace corto https://spro.li copiado con éxito!', 'success')
    }).catch(() => {
      toast.add('Error al copiar enlace corto', 'error')
    })
  } else {
    toast.add('Portapapeles no disponible en este dispositivo', 'error')
  }
}

const copyShareProductUrl = (product) => {
  const shareUrl = getProductDirectUrl(product)
  if (navigator?.clipboard?.writeText) {
    navigator.clipboard.writeText(shareUrl).then(() => {
      toast.add('¡Enlace de producto copiado! Compártelo donde quieras.', 'success')
    }).catch(() => {
      toast.add('Error al copiar enlace', 'error')
    })
  } else {
    toast.add('Portapapeles no disponible en este dispositivo', 'error')
  }
}

const shareProductWhatsApp = (product) => {
  const shareUrl = getProductDirectUrl(product)
  const msg = `🔥 *MIRA ESTE PRODUCTO EN STREAMEPRO*\n📦 *${product.name}*\n💰 Precio: *${product.price}*\n\n👉 Compra directa aquí:\n${shareUrl}`
  window.open(`https://wa.me/?text=${encodeURIComponent(msg)}`, '_blank')
}

// Búsqueda y Filtros
const searchQuery = ref('')
const physicalSearchQuery = ref('')
const selectedCategory = ref('All')
const physicalCategoryFilter = ref('All')

const filteredProducts = computed(() => {
  return products.value.filter(p => {
    const matchCat = selectedCategory.value === 'All' || p.category === selectedCategory.value
    const matchSearch = p.name.toLowerCase().includes(searchQuery.value.toLowerCase())
    return matchCat && matchSearch
  })
})

const filteredPhysicalProducts = computed(() => {
  const list = adminStore.physicalProducts || []
  return list.filter(p => {
    if (!p) return false
    const matchesCat = physicalCategoryFilter.value === 'All' || 
      (p.categoryIds && p.categoryIds.includes(physicalCategoryFilter.value))
    const q = (physicalSearchQuery.value || '').toLowerCase().trim()
    const name = String(p.name || '').toLowerCase()
    const desc = String(p.description || '').toLowerCase()
    const id = String(p.id || '')
    const matchesQuery = !q || name.includes(q) || desc.includes(q) || id.includes(q)
    return matchesCat && matchesQuery
  })
})

const filteredPackages = computed(() => {
  return adminStore.packages.filter(p => {
    if (!p.active) return false
    const q = searchQuery.value.toLowerCase().trim()
    if (!q) return true
    const matchTitle = p.title.toLowerCase().includes(q)
    const matchDesc = p.description.toLowerCase().includes(q)
    const matchServices = p.services.some(s => s.platform.toLowerCase().includes(q))
    return matchTitle || matchDesc || matchServices
  })
})

const addPackageToCart = (pkg) => {
  cartStore.addPackageItem(pkg)
  toast.add(`¡Combo "${pkg.title}" agregado al pedido!`, 'success')
  showCartDrawer.value = true
}

const sharePackageLink = (pkg) => {
  const origin = getBaseOrigin()
  const link = `${origin}/?ref=${clientForm.value.selectedResellerId}&pkg=${pkg.id}`
  navigator.clipboard.writeText(link).then(() => {
    toast.add(`¡Enlace copiado! Comparte este combo con tus contactos`, 'success')
  }).catch(() => {
    toast.add('Error al copiar enlace', 'error')
  })
}

const openCheckoutForm = () => {
  if (cartStore.items.length === 0) {
    toast.add('El carrito está vacío', 'error')
    return
  }
  showCartDrawer.value = false
  showCheckoutModal.value = true
}

const copyBankDetails = () => {
  if (!lastPlacedOrder.value) return
  const info = lastPlacedOrder.value.isDirect 
    ? lastPlacedOrder.value.providerBankInfo 
    : lastPlacedOrder.value.reseller
  
  const text = `DATOS PARA TRANSFERENCIA BANCARIA - STREAMEPRO\n` +
    `• Titular: ${info?.holderName || info?.name || 'StreamePro'}\n` +
    `• Banco/Entidad: ${info?.bankName || 'Mercado Pago'}\n` +
    `• CUIT/CUIL: ${info?.cuit || '20-00000000-0'}\n` +
    `• CBU/Alias: ${info?.cbuAlias || 'STREAMEPRO.PAGOS'}\n` +
    `• Monto Total: $${lastPlacedOrder.value.total.toFixed(2)} ARS\n` +
    `• N° de Pedido: ${lastPlacedOrder.value.clientNumber}`

  navigator.clipboard.writeText(text).then(() => {
    toast.add('¡Datos bancarios copiados al portapapeles!', 'success')
  }).catch(() => {
    toast.add('No se pudo copiar automáticamente', 'error')
  })
}

const sendOrderToWhatsApp = () => {
  if (!lastPlacedOrder.value) return
  const ord = lastPlacedOrder.value
  const targetPhone = ord.isDirect
    ? (ord.providerBankInfo?.whatsapp || adminStore.adminAuth?.whatsapp || '+54 9 291 565-9321')
    : (ord.reseller?.contactNumber || ord.providerBankInfo?.whatsapp || '+54 9 291 565-9321')

  let cleanPhone = targetPhone.replace(/[^0-9]/g, '')
  if (cleanPhone.startsWith('0')) {
    cleanPhone = cleanPhone.substring(1)
  }
  if (cleanPhone.length === 10) {
    cleanPhone = `549${cleanPhone}`
  } else if (cleanPhone.length === 11 && cleanPhone.startsWith('15')) {
    cleanPhone = `54911${cleanPhone.substring(2)}`
  } else if (cleanPhone.length === 12 && cleanPhone.startsWith('54') && !cleanPhone.startsWith('549')) {
    cleanPhone = `549${cleanPhone.substring(2)}`
  }

  const itemsList = ord.items.map(i => {
    const name = i.name || i.platform || 'Item'
    const qty = i.quantity || i.units || 1
    const price = i.clientPrice || (i.priceNumeric || (parseInt(String(i.price || '0').replace(/[$. ]/g, '')) || 0))
    let str = `• ${name} (x${qty}): ${(price * qty).toLocaleString()} ARS`
    if (i.activationEmail) {
      str += `\n  ✉️ Correo activación: ${i.activationEmail}`
    }
    return str
  }).join('\n')

  let message = `¡Hola! Acabo de registrar mi pedido en la web de *StreamePro* 🚀\n\n` +
    `📋 *N° de Pedido:* ${ord.clientNumber}\n` +
    `👤 *Cliente:* ${ord.customerName}\n` +
    `📱 *WhatsApp:* ${ord.customerPhone}\n` +
    (ord.pin ? `🔐 *PIN de Bóveda:* ${ord.pin}\n\n` : `\n`) +
    `🛒 *Detalle de la compra:*\n${itemsList}\n\n` +
    `💰 *Total:* $${ord.total.toFixed(2)} ARS\n` +
    `\n🔐 *Mi Bóveda de Accesos:* https://streamepro.me/boveda\n` +
    `(Consulto mis accesos con mi número de celular y mi PIN)`

  if (ord.digitalItems.length > 0) {
    message += `\n📎 Adjunto mi comprobante de transferencia bancaria para agilizar la entrega de mis cuentas (Tiempo estimado: 10 min a 1 hora).`
  }
  if (ord.physicalItems.length > 0) {
    message += `\n📦 Dirección de envío informada: ${ord.shippingAddress || 'A coordinar'}\nConsulto disponibilidad y costo final de envío.`
  }

  const waUrl = `https://wa.me/${cleanPhone}?text=${encodeURIComponent(message)}`
  const isMobile = typeof navigator !== 'undefined' && /Android|iPhone|iPad|iPod/i.test(navigator.userAgent)
  if (isMobile) {
    window.location.href = waUrl
  } else {
    try {
      const win = window.open(waUrl, '_blank')
      if (!win || win.closed || typeof win.closed === 'undefined') {
        window.location.href = waUrl
      }
    } catch (e) {
      window.location.href = waUrl
    }
  }
}

const goToCustomerPortal = () => {
  showOrderSuccessModal.value = false
  router.push('/portal')
}

const submitOrder = async () => {
  if (!clientForm.value.fullName.trim() || !clientForm.value.whatsapp.trim()) {
    toast.add('Por favor completa Nombre y WhatsApp para el envío', 'error')
    return
  }

  const cleanPin = (clientForm.value.pin || '').trim()
  if (!cleanPin || cleanPin.length !== 4) {
    toast.add('Por favor ingresa un PIN de 4 dígitos para tu Bóveda (Ej: 1234)', 'error')
    return
  }

  const digitalItems = cartStore.items.filter(i => i.itemType !== 'physical')
  const physicalItems = cartStore.items.filter(i => i.itemType === 'physical')
  const isDirect = isDirectSale.value

  // Snapshot del pedido antes de vaciar el carrito
  const prevNum = clientForm.value.clientNumber
  const currentRef = clientForm.value.selectedResellerId
  const hasUploadedReceipt = Boolean(receiptFile.value)
  const receiptMeta = receiptFile.value || {}

  const orderSnapshot = {
    clientNumber: prevNum,
    customerName: clientForm.value.fullName,
    customerPhone: clientForm.value.whatsapp,
    shippingAddress: clientForm.value.shippingAddress || 'A coordinar por WhatsApp',
    isDirect: isDirect,
    reseller: currentReseller.value ? { ...currentReseller.value } : null,
    providerBankInfo: { ...adminStore.providerBankInfo },
    items: [...cartStore.items],
    digitalItems: [...digitalItems],
    physicalItems: [...physicalItems],
    subtotal: cartStore.subtotal,
    discountPercentage: cartStore.discountPercentage,
    total: cartStore.total,
    paymentMethod: clientForm.value.paymentMethod,
    hasReceipt: hasUploadedReceipt,
    receiptFileName: receiptMeta.fileName || null,
    receiptFileType: receiptMeta.fileType || null,
    receiptFileSize: receiptMeta.fileSize || 0,
    date: new Date().toLocaleString()
  }

  // Guardar comprobante en la Mini Nube (IndexedDB) para consulta directa del Proveedor y Revendedor
  if (hasUploadedReceipt) {
    try {
      await saveReceipt(prevNum, {
        fileName: receiptMeta.fileName,
        fileType: receiptMeta.fileType,
        fileSize: receiptMeta.fileSize,
        dataUrl: receiptMeta.dataUrl,
        customerName: clientForm.value.fullName,
        customerPhone: clientForm.value.whatsapp,
        amount: cartStore.total
      })
    } catch (e) {
      console.warn('Error al guardar comprobante en mini nube:', e)
    }
  }

  // 1. Guardar o actualizar perfil local del cliente
  try {
    localStorage.setItem('streampro_user_profile', JSON.stringify({
      clientNumber: clientForm.value.clientNumber,
      name: clientForm.value.fullName,
      phone: clientForm.value.whatsapp,
      pin: clientForm.value.pin,
      address: clientForm.value.shippingAddress || ''
    }))
  } catch {}

  // 2. Registrar órdenes digitales de streaming esperando confirmación de guardado en la nube
  for (const item of digitalItems) {
    const streamingOrderId = `ORD-${Date.now().toString().slice(-4)}`
    
    if (hasUploadedReceipt) {
      saveReceipt(streamingOrderId, {
        fileName: receiptMeta.fileName,
        fileType: receiptMeta.fileType,
        fileSize: receiptMeta.fileSize,
        dataUrl: receiptMeta.dataUrl,
        customerName: clientForm.value.fullName,
        customerPhone: clientForm.value.whatsapp,
        amount: item.clientPrice
      }).catch(() => {})
    }

    await adminStore.createStreamingOrder({
      id: streamingOrderId,
      customerNumber: clientForm.value.clientNumber,
      customerName: clientForm.value.fullName,
      customerPhone: clientForm.value.whatsapp,
      pin: clientForm.value.pin,
      platform: item.platform,
      activationEmail: item.activationEmail || null,
      devices: item.units,
      pinPreference: item.pinPreference || 'same',
      planKey: item.planKey,
      planLabel: item.planLabel,
      providerCost: item.providerCost,
      resellerProfit: isDirect ? 0 : item.resellerProfit,
      clientPrice: item.clientPrice,
      resellerId: isDirect ? 'DIRECT' : clientForm.value.selectedResellerId,
      resellerName: isDirect ? 'StreamePro Oficial (Venta Directa)' : (currentReseller.value?.name || 'Distribuidor Asignado'),
      voucher: clientForm.value.transferVoucher || (hasUploadedReceipt ? `Comprobante en Nube (${receiptMeta.fileName})` : 'Sin comprobante adjunto'),
      hasReceipt: hasUploadedReceipt,
      receiptFileName: receiptMeta.fileName || null,
      receiptFileType: receiptMeta.fileType || null,
      receiptFileSize: receiptMeta.fileSize || 0,
      paymentMethod: clientForm.value.paymentMethod,
      profileSelections: (item.profiles || []).map(p => ({
        name: p.name
      }))
    })
  }

  // 3. Registrar orden de productos físicos esperando confirmación
  if (physicalItems.length > 0) {
    const source = clientForm.value.isSharedUrl ? 'shared_url' : 'store'
    await adminStore.createPhysicalOrder({
      customerNumber: clientForm.value.clientNumber,
      customerName: clientForm.value.fullName,
      customerPhone: clientForm.value.whatsapp,
      pin: clientForm.value.pin,
      shippingAddress: clientForm.value.shippingAddress || 'A coordinar por WhatsApp',
      resellerId: isDirect ? 'DIRECT' : clientForm.value.selectedResellerId,
      source,
      items: physicalItems,
      paymentMethod: clientForm.value.paymentMethod,
      voucher: clientForm.value.transferVoucher || (hasUploadedReceipt ? `Comprobante en Nube (${receiptMeta.fileName})` : 'Sin comprobante adjunto'),
      hasReceipt: hasUploadedReceipt,
      receiptFileName: receiptMeta.fileName || null,
      receiptFileType: receiptMeta.fileType || null,
      receiptFileSize: receiptMeta.fileSize || 0
    })
  }

  // 4. Disparar y esperar notificación a Telegram
  try {
    await fetch('/api/notify-order', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        order: {
          ...orderSnapshot,
          pin: clientForm.value.pin
        },
        telegramToken: adminStore.telegramConfig?.botToken || '9120060693:AAFynj7rM0cfElf1PMZ0fox9fZxj3wLqA30',
        telegramChatId: adminStore.telegramConfig?.chatId || '7994226676'
      })
    })
  } catch (err) {
    console.warn('Telegram notify error:', err)
  }

  lastPlacedOrder.value = {
    ...orderSnapshot,
    pin: clientForm.value.pin
  }
  showCheckoutModal.value = false
  showOrderSuccessModal.value = true
  cartStore.clearCart()

  // Enviar notificación local/push del sistema si está habilitada
  sendLocalNotification(`🎉 Pedido #${prevNum} Registrado Directamente`, {
    body: `Tu pedido por ${orderSnapshot.total.toLocaleString()} ARS fue enviado a los paneles de atención. Tiempo de entrega: 10 min a 1 hora.`,
    icon: '/pwa-192x192.png',
    tag: `streampro-order-${prevNum}`
  })

  const savedProfile = JSON.parse(localStorage.getItem('streampro_user_profile') || '{}')
  clientForm.value = {
    clientNumber: savedProfile.clientNumber || `CLI-${Math.floor(1000 + Math.random() * 9000)}`,
    fullName: savedProfile.name || '',
    whatsapp: savedProfile.phone || '',
    pin: savedProfile.pin || '',
    shippingAddress: '',
    selectedResellerId: currentRef, // Mantener estado de sesión actual
    paymentMethod: 'Transferencia Bancaria / CBU',
    transferVoucher: '',
    isSharedUrl: false
  }
  receiptFile.value = null

  if (physicalItems.length > 0 && digitalItems.length === 0) {
    toast.add(`¡Pedido registrado directamente en el panel (#${prevNum})! Un asesor coordinará tu envío.`, 'success')
  } else {
    toast.add(`¡Pedido y comprobante recibidos en el panel (#${prevNum})! Tiempo estimado de entrega: 10 min a 1 hora.`, 'success')
  }
}
</script>

<template>
  <div class="space-y-8">
    <!-- Hero Banner Promocional Enterprise -->
    <div class="glass-panel rounded-3xl p-8 sm:p-10 relative overflow-hidden border border-white/[0.08] shadow-2xl">
      <div class="absolute right-[-10%] top-[-20%] w-96 h-96 bg-gradient-to-br from-sky-500/20 via-blue-600/10 to-transparent rounded-full blur-3xl pointer-events-none"></div>
      <div class="max-w-3xl relative z-10">
        <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-sky-500/10 border border-sky-500/25 text-sky-400 text-xs font-bold uppercase tracking-wider mb-4 shadow-sm">
          <span class="w-2 h-2 rounded-full bg-sky-400 animate-pulse"></span>
          Entrega: 10 min a 1 hora • Stock 100% Garantizado
        </div>
        <h2 class="text-3xl sm:text-5xl font-black mb-3 text-white tracking-tight leading-tight">
          Streaming Premium al Mejor Costo.
        </h2>
        <p class="text-slate-300 text-sm sm:text-base leading-relaxed max-w-2xl font-normal">
          Acceso oficial por pantalla individual o cuentas multi-dispositivo.
          Acceso oficial garantizado con entrega de perfil y PIN exclusivo listo para usar.
        </p>

        <!-- Trust Metrics -->
        <div class="mt-6 flex flex-wrap items-center gap-3 text-xs font-semibold">
          <div class="flex items-center gap-2 bg-slate-900/80 px-3.5 py-2 rounded-xl border border-white/[0.08]">
            <span class="text-emerald-400 text-sm">⏱️</span>
            <span class="text-slate-200">Entrega en 10 min a 1 hora</span>
          </div>
          <div class="flex items-center gap-2 bg-slate-900/80 px-3.5 py-2 rounded-xl border border-white/[0.08]">
            <span class="text-sky-400 text-sm">🔒</span>
            <span class="text-slate-200">Garantía SLA 30 Días</span>
          </div>
          <div class="flex items-center gap-2 bg-slate-900/80 px-3.5 py-2 rounded-xl border border-white/[0.08]">
            <span class="text-amber-400 text-sm">🛡️</span>
            <span class="text-slate-200">Protección Anti-Caídas</span>
          </div>
        </div>

        <!-- Botones de Acceso Rápido a Secciones de la Tienda -->
        <div class="mt-6 flex flex-wrap items-center gap-2.5">
          <button 
            type="button"
            @click="storeSection = 'all'"
            :class="[
              'px-4 py-2.5 rounded-xl font-black text-xs transition-all flex items-center gap-2 cursor-pointer shadow-md',
              storeSection === 'all' ? 'bg-gradient-to-r from-sky-400 via-indigo-500 to-amber-400 text-slate-950 font-black shadow-sky-500/20' : 'bg-slate-900/90 text-slate-300 hover:text-white border border-white/10 hover:bg-slate-800'
            ]"
          >
            <span>🌟</span>
            <span>Ver Todo el Catálogo</span>
          </button>
          <button 
            type="button"
            @click="storeSection = 'digital'"
            :class="[
              'px-4 py-2.5 rounded-xl font-black text-xs transition-all flex items-center gap-2 cursor-pointer shadow-md',
              storeSection === 'digital' ? 'bg-gradient-to-r from-sky-400 to-blue-500 text-slate-950 shadow-sky-500/20' : 'bg-slate-900/90 text-slate-300 hover:text-white border border-white/10 hover:bg-slate-800'
            ]"
          >
            <span>🎬</span>
            <span>Suscripciones Digitales</span>
          </button>
          <button 
            type="button"
            @click="storeSection = 'physical'"
            :class="[
              'px-4 py-2.5 rounded-xl font-black text-xs transition-all flex items-center gap-2 cursor-pointer shadow-md',
              storeSection === 'physical' ? 'bg-gradient-to-r from-amber-400 to-orange-500 text-slate-950 shadow-amber-500/20' : 'bg-slate-900/90 text-amber-300 hover:text-white border border-amber-500/30 hover:bg-slate-800'
            ]"
          >
            <span>📦</span>
            <span>Tienda & Catálogo Físico ({{ adminStore.physicalProducts.length }})</span>
          </button>
          <button 
            type="button"
            @click="storeSection = 'packages'"
            :class="[
              'px-4 py-2.5 rounded-xl font-black text-xs transition-all flex items-center gap-2 cursor-pointer shadow-md',
              storeSection === 'packages' ? 'bg-gradient-to-r from-purple-500 via-pink-500 to-rose-500 text-white shadow-pink-500/20' : 'bg-slate-900/90 text-pink-300 hover:text-white border border-pink-500/30 hover:bg-slate-800'
            ]"
          >
            <span>🎁</span>
            <span>Combos & Paquetes</span>
          </button>
        </div>
      </div>
    </div>

    <!-- BANNER DE CANAL ACTIVO: DISTRIBUIDOR OFICIAL vs TIENDA DIRECTA -->
    <div v-if="!isDirectSale && currentReseller" class="bg-gradient-to-r from-amber-500/15 via-orange-500/10 to-amber-500/15 border border-amber-500/30 rounded-2xl p-3.5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs shadow-md">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-xl bg-amber-500/20 text-amber-300 border border-amber-500/30 flex items-center justify-center font-bold text-sm shrink-0">
          🏪
        </div>
        <div>
          <div class="flex items-center gap-2">
            <span class="font-black text-white text-sm tracking-tight">{{ resellerStoreName }}</span>
            <span class="text-[10px] font-mono font-bold bg-amber-400/20 text-amber-300 border border-amber-400/30 px-2 py-0.5 rounded">
              Distribuidor Autorizado ({{ currentReseller.id }})
            </span>
          </div>
          <p class="text-[11px] text-slate-300">
            Estás realizando tu compra a través de este canal oficial con garantía directa y soporte garantizado.
          </p>
        </div>
      </div>
      <div class="flex items-center gap-1.5 bg-emerald-500/10 border border-emerald-500/20 px-3 py-1.5 rounded-xl text-emerald-400 font-bold text-[11px] shrink-0 self-end sm:self-auto">
        <span>✓</span> Canal Oficial Verificado
      </div>
    </div>

    <!-- Selector Principal de Sección: Streaming Digital vs Paquetes vs Productos Físicos -->
    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-white/[0.08] pb-4">
      <div class="flex flex-wrap p-1.5 bg-slate-900/80 backdrop-blur-xl rounded-2xl border border-white/[0.08] shadow-lg gap-1">
        <button 
          @click="storeSection = 'all'"
          :class="[
            'px-5 py-3 rounded-xl font-black text-xs sm:text-sm transition-all flex items-center gap-2 cursor-pointer',
            storeSection === 'all' 
              ? 'bg-gradient-to-r from-sky-400 via-indigo-500 to-amber-400 text-slate-950 shadow-lg font-black' 
              : 'text-slate-400 hover:text-white hover:bg-white/[0.04]'
          ]"
        >
          <span>🌟</span>
          <span>Todo el Catálogo</span>
        </button>

        <button 
          @click="storeSection = 'digital'"
          :class="[
            'px-5 py-3 rounded-xl font-black text-xs sm:text-sm transition-all flex items-center gap-2 cursor-pointer',
            storeSection === 'digital' 
              ? 'bg-gradient-to-r from-sky-400 to-blue-500 text-slate-950 shadow-lg shadow-sky-500/20' 
              : 'text-slate-400 hover:text-white hover:bg-white/[0.04]'
          ]"
        >
          <span>🎬</span>
          <span>Suscripciones Digitales</span>
        </button>

        <button 
          @click="storeSection = 'packages'"
          :class="[
            'px-5 py-3 rounded-xl font-black text-xs sm:text-sm transition-all flex items-center gap-2 cursor-pointer',
            storeSection === 'packages' 
              ? 'bg-gradient-to-r from-purple-500 via-pink-500 to-rose-500 text-white shadow-lg shadow-purple-500/20' 
              : 'text-slate-400 hover:text-white hover:bg-white/[0.04]'
          ]"
        >
          <span>🎁</span>
          <span>Paquetes & Combos en Oferta</span>
          <span class="bg-slate-950/80 text-pink-300 text-[10px] px-2 py-0.5 rounded-full font-mono border border-pink-400/30">
            {{ adminStore.packages.filter(p => p.active).length }}
          </span>
        </button>

        <button 
          @click="storeSection = 'physical'"
          :class="[
            'px-5 py-3 rounded-xl font-black text-xs sm:text-sm transition-all flex items-center gap-2 cursor-pointer',
            storeSection === 'physical' 
              ? 'bg-gradient-to-r from-amber-400 to-orange-500 text-slate-950 shadow-lg shadow-amber-500/20' 
              : 'text-slate-400 hover:text-white hover:bg-white/[0.04]'
          ]"
        >
          <span>📦</span>
          <span>Catálogo Físico</span>
          <span class="bg-slate-950/80 text-amber-300 text-[10px] px-2 py-0.5 rounded-full font-mono border border-amber-400/30">
            {{ adminStore.physicalProducts.length }}
          </span>
        </button>
      </div>

      <!-- Banner de Afiliación / Reseller Asignado (Sin porcentajes privados para el cliente) -->
      <div class="flex items-center gap-2 bg-slate-900/90 px-4 py-2.5 rounded-2xl border border-white/[0.08] text-xs">
        <span class="text-slate-400">Distribuidor Asignado:</span>
        <strong class="text-white font-bold bg-slate-800 px-2.5 py-0.5 rounded">{{ resellerStoreName }}</strong>
        <span v-if="clientForm.isSharedUrl" class="bg-emerald-500/20 text-emerald-300 font-bold px-2 py-0.5 rounded text-[11px] border border-emerald-500/30">
          🤝 Recomendación Compartida
        </span>
        <span v-else class="bg-sky-500/15 text-sky-300 font-bold px-2.5 py-0.5 rounded text-[11px] border border-sky-500/30 flex items-center gap-1 shadow-sm">
          🛍️ Tienda Oficial (VERIFICADO)
        </span>
      </div>
    </div>

    <!-- SECCIÓN 1: SUSCRIPCIONES DIGITALES -->
    <div v-if="storeSection === 'all' || storeSection === 'digital'" id="suscripciones-digitales" class="space-y-6">
      <!-- Header de Sección Oficial -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 bg-gradient-to-r from-sky-950/40 via-slate-900 to-slate-950 p-4 rounded-2xl border border-sky-500/30">
        <div class="flex items-center gap-3">
          <span class="text-2xl">🎬</span>
          <div>
            <h3 class="text-lg font-black text-white">Suscripciones Digitales & Cuentas Oficiales</h3>
            <p class="text-xs text-slate-400">Netflix, Max, Spotify, Disney+, IPTV, Prime Video, YouTube Premium y Canva Pro con PIN exclusivo y entrega de 10 min a 1 hora.</p>
          </div>
        </div>
        <span class="text-xs font-mono font-bold bg-sky-500/20 text-sky-300 border border-sky-500/30 px-3 py-1 rounded-xl shrink-0">
          8 Plataformas Disponibles
        </span>
      </div>

      <!-- Banner Destacado para Descubrir Catálogo Físico (Visible si se filtra sólo digital) -->
      <div v-if="storeSection === 'digital'" class="bg-gradient-to-r from-amber-950/40 via-slate-900 to-orange-950/30 p-4 rounded-2xl border border-amber-500/30 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-lg">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center text-xl shrink-0 border border-amber-500/30">
            📦
          </div>
          <div>
            <strong class="text-white text-xs sm:text-sm block">¿Sabías que también tenemos Catálogo de Productos Físicos?</strong>
            <p class="text-[11px] text-slate-300 mt-0.5">Tecnología, electrónica, hogar y bazar con stock listo y asesoramiento por WhatsApp.</p>
          </div>
        </div>
        <button 
          type="button"
          @click="storeSection = 'physical'"
          class="bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-xs px-4 py-2 rounded-xl transition shadow shrink-0 flex items-center gap-1.5 cursor-pointer"
        >
          <span>Ver Productos Físicos ({{ adminStore.physicalProducts.length }}) →</span>
        </button>
      </div>
      <!-- Barra de Búsqueda y Botón de Carrito -->
      <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
        <div class="flex gap-4 p-2 bg-slate-900/70 backdrop-blur-xl border border-white/[0.08] rounded-2xl flex-1 w-full shadow-lg">
          <div class="relative flex-1">
            <svg class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            <input v-model="searchQuery" type="text" placeholder="Buscar por Netflix, Max, Spotify, Disney, IPTV..." class="w-full bg-transparent border-none p-3 pl-10 text-white focus:outline-none focus:ring-0 text-sm placeholder:text-slate-500">
          </div>
          <div class="w-px bg-white/10 my-2"></div>
          <select v-model="selectedCategory" class="bg-transparent border-none p-3 text-white focus:outline-none cursor-pointer text-sm font-semibold pr-4">
            <option value="All" class="bg-slate-900">Todas las categorías</option>
            <option value="Streaming" class="bg-slate-900">Series & Películas</option>
            <option value="Musica" class="bg-slate-900">Música & Audio</option>
            <option value="IPTV" class="bg-slate-900">Televisión IPTV</option>
          </select>
        </div>

        <!-- Botón Ver Carrito Pro -->
        <button 
          @click="showCartDrawer = true" 
          class="relative bg-gradient-to-r from-sky-400 via-blue-500 to-indigo-600 hover:from-sky-300 hover:to-indigo-500 text-slate-950 font-black px-7 py-3.5 rounded-2xl transition-all shadow-[0_0_20px_rgba(56,189,248,0.3)] hover:shadow-[0_0_25px_rgba(56,189,248,0.5)] flex items-center gap-3 w-full sm:w-auto justify-center transform active:scale-95"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
          <span>Mi Carrito</span>
          <span v-if="cartStore.items.length > 0" class="bg-slate-950 text-white text-xs px-2.5 py-0.5 rounded-full font-bold shadow-inner border border-white/20">
            {{ cartStore.items.length }}
          </span>
        </button>
      </div>

      <!-- Banner Promocional Combos & Ofertas Especiales -->
      <div 
        @click="storeSection = 'packages'" 
        class="cursor-pointer bg-gradient-to-r from-purple-950/60 via-pink-950/40 to-slate-900 p-4 rounded-2xl border border-pink-500/30 hover:border-pink-500/60 transition-all shadow-xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 group"
      >
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-pink-500/20 text-pink-300 flex items-center justify-center text-xl shrink-0 group-hover:scale-110 transition-transform">
            🎁
          </div>
          <div>
            <div class="flex items-center gap-2">
              <span class="text-xs font-black uppercase tracking-wider bg-pink-500/20 text-pink-300 px-2 py-0.5 rounded border border-pink-500/30">
                ¡Super Ahorro!
              </span>
              <span class="text-xs text-slate-300 font-semibold">¿Quieres combinar plataformas en oferta?</span>
            </div>
            <p class="text-xs text-slate-400 mt-0.5">Descubre nuestros combos de streaming con hasta 35% de descuento directo en un solo pago.</p>
          </div>
        </div>
        <div class="flex items-center gap-2 text-pink-300 font-black text-xs group-hover:translate-x-1 transition-transform shrink-0">
          <span>Ver Paquetes en Oferta ({{ adminStore.packages.filter(p => p.active).length }})</span>
          <span>→</span>
        </div>
      </div>

      <!-- Catálogo de Suscripciones -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="product in filteredProducts" :key="product.id" class="glass-panel glass-panel-hover rounded-3xl p-6 border border-white/[0.08] flex flex-col justify-between relative overflow-hidden group">
          <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-sky-400 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
          <div>
            <div class="flex flex-wrap justify-between items-center gap-2 mb-3">
              <span class="text-[10px] font-black text-sky-300 tracking-widest uppercase bg-sky-500/10 px-2.5 py-1 rounded-lg border border-sky-500/20">{{ product.category }}</span>
              <span 
                :class="[
                  'text-[10px] font-bold px-2 py-0.5 rounded-full border flex items-center gap-1 font-mono',
                  adminStore.getPlatformStock(product.platform).status === 'in_stock' ? 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30' :
                  adminStore.getPlatformStock(product.platform).status === 'low_stock' ? 'bg-amber-500/15 text-amber-300 border-amber-500/30' :
                  'bg-sky-500/15 text-sky-300 border-sky-500/30'
                ]"
              >
                <span>{{ adminStore.getPlatformStock(product.platform).status === 'in_stock' ? '🟢 Stock 10-15m' : (adminStore.getPlatformStock(product.platform).status === 'low_stock' ? '🟡 Últimos cupos' : '⏱️ 10m a 1h') }}</span>
              </span>
            </div>
            <h3 class="text-xl font-bold text-white mb-2">{{ product.name }}</h3>
            <div class="flex items-center justify-between gap-2 mb-3">
              <span class="text-xs text-slate-400 font-medium">Plan oficial garantizado</span>
              <button 
                type="button"
                @click="openStreamingInfo(product)"
                class="text-[11px] font-bold text-sky-400 hover:text-sky-300 flex items-center gap-1 cursor-pointer hover:underline"
              >
                <span>ℹ️</span>
                <span>Ver +info</span>
              </button>
            </div>
            
            <!-- Modalidades Disponibles (Precios visibles al Elegir Plan) -->
            <div class="space-y-2 bg-slate-950/70 p-3.5 rounded-2xl border border-slate-800 mb-4 text-xs">
              <div class="text-[10px] font-bold text-sky-400 uppercase tracking-wider flex items-center justify-between">
                <span class="flex items-center gap-1.5">
                  <span>✨</span> Modalidades Oficiales
                </span>
                <span class="text-[9px] text-slate-500 font-mono font-medium">Ver precios al elegir plan</span>
              </div>
              <div class="space-y-1 pt-0.5">
                <div v-for="(plan, pKey) in product.plans" :key="pKey" class="flex items-center justify-between text-slate-300 py-0.5">
                  <div class="flex items-center gap-1.5">
                    <span class="text-emerald-400 font-bold text-xs">✓</span>
                    <span class="font-medium text-slate-200">{{ plan.label }}</span>
                  </div>
                  <span class="text-[10px] text-slate-400 font-mono bg-slate-900 px-2 py-0.5 rounded border border-slate-800/80">Oficial</span>
                </div>
              </div>
            </div>
          </div>

          <div class="flex gap-2">
            <button 
              type="button" 
              @click="openStreamingInfo(product)"
              class="px-3.5 py-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-sky-400 hover:text-white border border-slate-700 font-bold text-xs transition-colors flex items-center justify-center gap-1 shrink-0 cursor-pointer"
              title="Ver detalles y especificaciones del servicio"
            >
              <span>ℹ️</span>
              <span>+info</span>
            </button>
            <button @click="openConfig(product)" class="flex-1 bg-slate-800 text-brand-accent hover:bg-brand-accent hover:text-slate-950 font-black py-3 px-3 rounded-xl transition-all duration-300 flex items-center justify-center gap-1.5 group-hover:bg-brand-accent group-hover:text-slate-950 shadow-sm text-xs sm:text-sm cursor-pointer">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
              <span>Elegir Plan</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- SECCIÓN 2: PAQUETES & COMBOS EN OFERTA -->
    <div v-if="storeSection === 'all' || storeSection === 'packages'" id="paquetes-combos" class="space-y-6">
      <!-- Barra de Filtros y Ver Carrito para Combos -->
      <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
        <div class="flex gap-4 p-2 bg-slate-900/70 backdrop-blur-xl border border-white/[0.08] rounded-2xl flex-1 w-full shadow-lg">
          <div class="relative flex-1">
            <svg class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            <input v-model="searchQuery" type="text" placeholder="Buscar combos (Netflix + Spotify, Trío VIP, MagisTV, Disney)..." class="w-full bg-transparent border-none p-3 pl-10 text-white focus:outline-none focus:ring-0 text-sm placeholder:text-slate-500">
          </div>
        </div>

        <button 
          @click="showCartDrawer = true" 
          class="relative bg-gradient-to-r from-purple-500 via-pink-500 to-rose-500 hover:from-purple-400 hover:to-rose-400 text-white font-black px-7 py-3.5 rounded-2xl transition-all shadow-[0_0_20px_rgba(236,72,153,0.3)] flex items-center gap-3 w-full sm:w-auto justify-center transform active:scale-95 shrink-0"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
          <span>Ver Mi Pedido</span>
          <span v-if="cartStore.items.length > 0" class="bg-slate-950 text-white text-xs px-2.5 py-0.5 rounded-full font-bold shadow-inner border border-white/20">
            {{ cartStore.items.length }}
          </span>
        </button>
      </div>

      <!-- Banner de Garantía y Beneficios de los Combos -->
      <div class="bg-gradient-to-r from-purple-950/50 via-pink-950/40 to-slate-900 p-6 rounded-3xl border border-pink-500/30 shadow-2xl flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="flex items-center gap-3.5">
          <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-purple-500 to-pink-500 text-white flex items-center justify-center text-2xl shrink-0 shadow-lg shadow-pink-500/20">
            🎁
          </div>
          <div>
            <div class="flex items-center gap-2">
              <span class="text-xs font-black uppercase tracking-wider bg-pink-500/20 text-pink-300 px-2 py-0.5 rounded border border-pink-500/30">
                Ahorro Inteligente
              </span>
              <span class="text-xs text-slate-300 font-mono">Entrega: 10 min a 1 hora con PIN</span>
            </div>
            <h3 class="text-xl font-black text-white mt-1">Paquetes Combinados & Ofertas Exclusivas</h3>
            <p class="text-xs text-slate-400">Contrata múltiples servicios en un solo pago con tarifas promocionales y soporte integral continuo.</p>
          </div>
        </div>
      </div>

      <!-- Grid de Paquetes -->
      <div v-if="filteredPackages.length === 0" class="text-center py-16 bg-slate-900/40 rounded-3xl border border-slate-800 text-slate-400">
        <span class="text-3xl block mb-2">🔍</span>
        <p class="text-sm font-bold text-white">No se encontraron combos para tu búsqueda.</p>
        <p class="text-xs text-slate-500 mt-1">Prueba borrando los filtros o explorando las suscripciones individuales.</p>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div 
          v-for="pkg in filteredPackages" 
          :key="pkg.id"
          class="glass-panel rounded-3xl p-6 border border-white/[0.08] hover:border-pink-500/40 transition-all flex flex-col justify-between relative group hover:shadow-2xl hover:shadow-pink-500/10"
        >
          <div class="space-y-4">
            <!-- Header de Tarjeta -->
            <div class="flex justify-between items-start gap-2">
              <span class="text-[10px] font-black uppercase tracking-wider bg-gradient-to-r from-pink-500 to-rose-500 text-white px-3 py-1 rounded-full shadow-sm">
                {{ pkg.badge || '🔥 OFERTA' }}
              </span>
              <span class="text-[11px] font-mono text-slate-400 bg-slate-900 px-2 py-0.5 rounded border border-slate-800">
                {{ pkg.services.length }} Plataformas
              </span>
            </div>

            <!-- Título y Descripción -->
            <div>
              <h3 class="text-lg font-black text-white group-hover:text-pink-300 transition-colors leading-tight">
                {{ pkg.title }}
              </h3>
              <p class="text-xs text-slate-400 mt-1.5 leading-relaxed line-clamp-2">
                {{ pkg.description }}
              </p>
            </div>

            <!-- Servicios Incluidos -->
            <div class="bg-slate-950/80 p-3.5 rounded-2xl border border-slate-800/80 space-y-2">
              <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Servicios Incluidos en el Combo:</span>
              <div class="space-y-1.5">
                <div 
                  v-for="(srv, sIdx) in pkg.services" 
                  :key="sIdx"
                  class="flex items-center justify-between text-xs bg-slate-900/60 p-2 rounded-xl border border-slate-800/60"
                >
                  <div class="flex items-center gap-2">
                    <span class="text-base">
                      {{ srv.platform === 'Netflix' ? '🍿' : srv.platform === 'Spotify' ? '🎧' : srv.platform === 'Disney+' ? '✨' : srv.platform === 'IPTV' ? '📺' : '🎬' }}
                    </span>
                    <strong class="text-white">{{ srv.platform }}</strong>
                    <span class="text-[11px] text-slate-400">({{ srv.planLabel }})</span>
                  </div>
                  <span class="text-[10px] text-slate-400 font-mono">1 mes</span>
                </div>
              </div>
            </div>

            <!-- Precio y Descuento -->
            <div class="bg-gradient-to-r from-slate-950 to-pink-950/20 p-4 rounded-2xl border border-pink-500/20 flex justify-between items-center">
              <div>
                <span class="text-[11px] text-slate-400 line-through block">Antes: ${{ pkg.originalPrice.toLocaleString() }} ARS</span>
                <span class="text-2xl font-black text-transparent bg-clip-text bg-gradient-to-r from-pink-400 via-rose-300 to-amber-300 font-mono">
                  ${{ pkg.promoPrice.toLocaleString() }} <span class="text-xs font-normal text-slate-400">ARS</span>
                </span>
              </div>
              <div v-if="pkg.originalPrice > pkg.promoPrice" class="bg-emerald-500/15 border border-emerald-500/30 text-emerald-400 text-xs font-bold px-2.5 py-1 rounded-xl text-center">
                <span>Ahorras</span>
                <div class="font-black text-sm">${{ (pkg.originalPrice - pkg.promoPrice).toLocaleString() }}</div>
              </div>
            </div>
          </div>

          <!-- Botones de Acción -->
          <div class="space-y-2 pt-4">
            <button 
              @click="addPackageToCart(pkg)" 
              class="w-full bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-400 hover:to-pink-400 text-white font-black py-3 px-4 rounded-xl transition-all shadow-lg shadow-pink-500/20 flex items-center justify-center gap-2"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
              <span>+ Agregar Combo al Pedido</span>
            </button>

            <button 
              @click="sharePackageLink(pkg)"
              class="w-full bg-slate-900/80 hover:bg-slate-800 text-slate-300 hover:text-white text-xs font-bold py-2.5 px-3 rounded-xl transition-all border border-slate-800 flex items-center justify-center gap-1.5"
            >
              <span>🤝</span>
              <span>AYUDAR COMPARTIENDO</span>
            </button>
            
          </div>
        </div>
      </div>
    </div>

    <!-- SECCIÓN 3: CATÁLOGO DE PRODUCTOS FÍSICOS (C:\Users\yo\catalogo) -->
    <div v-if="storeSection === 'all' || storeSection === 'physical'" id="catalogo-fisico" class="space-y-6">
      <!-- Header de Sección Oficial -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 bg-gradient-to-r from-amber-950/40 via-slate-900 to-slate-950 p-4 rounded-2xl border border-amber-500/30">
        <div class="flex items-center gap-3">
          <span class="text-2xl">📦</span>
          <div>
            <h3 class="text-lg font-black text-white">Tienda & Catálogo de Productos Físicos</h3>
            <p class="text-xs text-slate-400">Electrónica, tecnología, accesorios, hogar, bazar y herramientas con asesoramiento directo por WhatsApp.</p>
          </div>
        </div>
        <span class="text-xs font-mono font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30 px-3 py-1 rounded-xl shrink-0">
          {{ adminStore.physicalProducts.length }} Productos Sincronizados
        </span>
      </div>

      <!-- Banner Destacado para Volver a Streaming Digital (Visible si se filtra sólo físico) -->
      <div v-if="storeSection === 'physical'" class="bg-gradient-to-r from-sky-950/40 via-slate-900 to-indigo-950/30 p-4 rounded-2xl border border-sky-500/30 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-lg">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-sky-500/20 text-sky-400 flex items-center justify-center text-xl shrink-0 border border-sky-500/30">
            🎬
          </div>
          <div>
            <strong class="text-white text-xs sm:text-sm block">¿Buscando Cuentas y Pantallas de Streaming?</strong>
            <p class="text-[11px] text-slate-300 mt-0.5">Netflix, Max, Spotify, Disney+, YouTube Premium e IPTV con entrega de 10 min a 1 hora.</p>
          </div>
        </div>
        <button 
          type="button"
          @click="storeSection = 'digital'"
          class="bg-brand-accent hover:bg-sky-400 text-slate-950 font-black text-xs px-4 py-2 rounded-xl transition shadow shrink-0 flex items-center gap-1.5 cursor-pointer"
        >
          <span>Ver Suscripciones Digitales →</span>
        </button>
      </div>
      <!-- Filtros del Catálogo Físico -->
      <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
        <div class="flex gap-4 p-2 bg-slate-900/70 backdrop-blur-xl border border-white/[0.08] rounded-2xl flex-1 w-full shadow-lg">
          <div class="relative flex-1">
            <svg class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            <input v-model="physicalSearchQuery" type="text" placeholder="Buscar productos físicos (herramientas, hogar, electrónica, bazar)..." class="w-full bg-transparent border-none p-3 pl-10 text-white focus:outline-none focus:ring-0 text-sm placeholder:text-slate-500">
          </div>
          <div class="w-px bg-white/10 my-2"></div>
          <select v-model="physicalCategoryFilter" class="bg-transparent border-none p-3 text-white focus:outline-none cursor-pointer text-sm font-semibold pr-4">
            <option value="All" class="bg-slate-900">Todas las categorías ({{ adminStore.physicalProducts.length }})</option>
            <option v-for="cat in adminStore.physicalCategories" :key="cat.id" :value="cat.id" class="bg-slate-900">
              {{ cat.name }} ({{ cat.cantidad }})
            </option>
          </select>
        </div>

        <button 
          @click="showCartDrawer = true" 
          class="relative bg-gradient-to-r from-amber-400 via-orange-500 to-red-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 font-black px-7 py-3.5 rounded-2xl transition-all shadow-[0_0_20px_rgba(251,191,36,0.3)] flex items-center gap-3 w-full sm:w-auto justify-center"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
          <span>Mi Pedido</span>
          <span v-if="cartStore.items.length > 0" class="bg-slate-950 text-white text-xs px-2.5 py-0.5 rounded-full font-bold border border-white/20">
            {{ cartStore.items.length }}
          </span>
        </button>
      </div>

      <!-- Banner Oficial para Clientes con Imagen Solicitada -->
      <div class="w-full rounded-2xl overflow-hidden border border-amber-500/30 shadow-2xl relative bg-slate-950 group">
        <img 
          src="https://tua.ar/images/banner22.webp" 
          alt="Catálogo Oficial Productos Físicos StreamePro" 
          class="w-full h-auto max-h-64 sm:max-h-72 object-cover object-center group-hover:scale-[1.01] transition-transform duration-500"
          loading="eager"
        >
      </div>

      <!-- Banner Oficial de Fidelidad para Clientes -->
      <div class="bg-gradient-to-r from-amber-950/40 via-orange-950/30 to-slate-900 p-5 rounded-2xl border border-amber-500/30 shadow-xl flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="flex items-center gap-3.5">
          <div class="w-12 h-12 rounded-2xl bg-amber-500/20 text-amber-400 flex items-center justify-center text-2xl shrink-0 border border-amber-500/30 shadow-inner">
            📦
          </div>
          <div>
            <h3 class="text-base sm:text-lg font-black tracking-wider text-transparent bg-clip-text bg-gradient-to-r from-amber-300 via-orange-300 to-yellow-200 uppercase">
              ⭐ EL PRODUCTO QUE NECESITAS ESTA AQUI.
            </h3>
            <p class="text-xs text-slate-300 mt-0.5">
              Catálogo oficial con asesoría directa personalizada. No transfieras previo: confirmamos stock y envío contigo por WhatsApp.
            </p>
          </div>
        </div>
        <div class="text-xs font-mono text-amber-300 bg-slate-950/80 px-3.5 py-2 rounded-xl border border-amber-500/30 shrink-0 flex items-center gap-2">
          <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
          <span>Asesoría Directa Oficial</span>
        </div>
      </div>

      <!-- Estado Vacío si no hay coincidencias -->
      <div v-if="filteredPhysicalProducts.length === 0" class="text-center py-12 bg-slate-900/40 rounded-3xl border border-slate-800 border-dashed space-y-3">
        <span class="text-4xl block">📦</span>
        <h4 class="text-white font-black text-base">No se encontraron productos físicos</h4>
        <p class="text-xs text-slate-400 max-w-md mx-auto">No hay resultados para "{{ physicalSearchQuery }}". Prueba con otro término o limpia los filtros.</p>
        <button 
          type="button"
          @click="physicalSearchQuery = ''; physicalCategoryFilter = 'All'" 
          class="bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-xs px-4 py-2 rounded-xl transition cursor-pointer shadow-md inline-flex items-center gap-1.5"
        >
          <span>Ver todos los {{ adminStore.physicalProducts.length }} productos</span>
        </button>
      </div>

      <!-- Grid de Productos Físicos (2 columnas en móviles, 4 en desktop) -->
      <div v-else class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2.5 sm:gap-5">
        <div 
          v-for="item in filteredPhysicalProducts" 
          :key="item.id" 
          class="bg-slate-900/80 rounded-xl sm:rounded-2xl border border-white/[0.08] overflow-hidden flex flex-col justify-between hover:border-amber-400/40 hover:-translate-y-1 transition-all duration-300 shadow-lg group relative"
        >
          <!-- Envío Gratis Badge si corresponde -->
          <div v-if="item.priceNumeric >= 40000" class="absolute top-2 left-2 sm:top-3 sm:left-3 bg-emerald-500 text-slate-950 text-[8px] sm:text-[10px] font-black px-1.5 sm:px-2 py-0.5 rounded-full shadow-md z-10">
            ENVÍO GRATIS
          </div>

          <!-- Imagen del Producto -->
          <div class="w-full h-36 sm:h-52 bg-white flex items-center justify-center p-2 sm:p-3.5 overflow-hidden cursor-pointer relative group-hover:bg-slate-50 transition-colors" @click="openPhysicalModal(item)">
            <img 
              :src="item.image" 
              :alt="item.name" 
              class="max-h-full max-w-full object-contain group-hover:scale-105 transition-transform duration-300"
              loading="lazy"
            >
          </div>

          <!-- Información y Precios -->
          <div class="p-2 sm:p-4 flex flex-col flex-1 justify-between space-y-1.5 sm:space-y-3">
            <div>
              <h4 class="font-bold text-white text-[11px] sm:text-sm line-clamp-2 leading-snug cursor-pointer hover:text-amber-300 transition-colors" @click="openPhysicalModal(item)">
                {{ item.name }}
              </h4>
              <button 
                type="button"
                @click.stop="openPhysicalModal(item)"
                class="text-[10px] sm:text-[11px] font-semibold text-amber-400/90 hover:text-amber-300 mt-1 flex items-center gap-1 cursor-pointer hover:underline"
              >
                <span>📋</span>
                <span>Ver Ficha Técnica</span>
              </button>
            </div>

            <div class="pt-1.5 sm:pt-2 border-t border-slate-800">
              <div class="flex justify-between items-baseline mb-1.5 sm:mb-3">
                <span class="text-[9px] sm:text-xs text-slate-400">Oficial:</span>
                <span class="text-xs sm:text-lg font-black text-amber-400 font-mono">{{ item.price }}</span>
              </div>

              <!-- Botones de Acción: Ver Más, Agregar y Compartir Link -->
              <div class="space-y-1 sm:space-y-2">
                <div class="flex gap-1 sm:gap-2">
                  <button 
                    @click="openPhysicalModal(item)" 
                    class="flex-1 bg-slate-800 hover:bg-slate-700 text-slate-200 text-[10px] sm:text-xs font-bold py-1.5 sm:py-2 rounded-lg sm:rounded-xl transition-colors border border-slate-700 flex items-center justify-center gap-1"
                  >
                    <span>Ficha</span>
                  </button>
                  <button 
                    @click="addPhysicalToCart(item, 1)" 
                    class="flex-1 bg-amber-500 hover:bg-amber-400 text-slate-950 text-[10px] sm:text-xs font-black py-1.5 sm:py-2 rounded-lg sm:rounded-xl transition-all shadow flex items-center justify-center gap-1"
                  >
                    <span>+ Carrito</span>
                  </button>
                </div>

                <!-- Botón AYUDAR COMPARTIENDO -->
                <div class="flex gap-1 pt-0.5">
                  <button 
                    @click="copyShareProductUrl(item)" 
                    class="flex-1 bg-amber-500/10 hover:bg-amber-500/20 text-[9px] sm:text-[11px] font-black py-1 sm:py-1.5 rounded-lg border border-amber-500/30 text-amber-300 hover:text-amber-200 transition-colors flex items-center justify-center gap-1 truncate px-1"
                    title="Copiar enlace para ayudar compartiendo"
                  >
                    <span class="truncate">🤝 COMPARTIR</span>
                  </button>
                  <button 
                    @click="shareProductWhatsApp(item)" 
                    class="bg-emerald-600/20 hover:bg-emerald-600 text-emerald-400 hover:text-white text-[10px] sm:text-[11px] font-bold px-1.5 sm:px-2 py-1 sm:py-1.5 rounded-lg border border-emerald-500/30 transition-colors shrink-0"
                    title="Compartir por WhatsApp"
                  >
                    💬
                  </button>
                  <button 
                    @click="openFlyerForPhysical(item)" 
                    class="bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 hover:text-white text-[10px] sm:text-[11px] font-bold px-1.5 sm:px-2 py-1 sm:py-1.5 rounded-lg border border-amber-500/30 transition-colors shrink-0 flex items-center justify-center"
                    title="Crear Flyer de este producto"
                  >
                    🎨
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL DE ELECCIÓN DE PLAN -->
    <div v-if="showConfigModal" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-black/80 backdrop-blur-sm animate-in fade-in duration-200">
      <div class="bg-slate-900 border border-slate-700 w-full max-w-lg rounded-3xl p-6 shadow-2xl relative space-y-5 max-h-[90vh] overflow-y-auto overscroll-contain custom-scrollbar">
        <div class="flex justify-between items-start border-b border-slate-800 pb-3">
          <div>
            <h3 class="text-xl font-black text-white">Elegir Plan: {{ selectedProduct.name }}</h3>
            <p class="text-xs text-slate-400 mt-0.5">Selecciona la modalidad deseada para tu orden oficial.</p>
          </div>
          <button @click="showConfigModal = false" class="text-slate-400 hover:text-white p-1 text-lg">✕</button>
        </div>

        <!-- Selector Visual de Planes con Precios Dentro de las Selecciones -->
        <div class="space-y-2">
          <label class="block text-xs font-bold uppercase text-slate-400 tracking-wider">
            Modalidades y Precios Disponibles
          </label>
          <div class="grid grid-cols-1 gap-2.5">
            <div 
              v-for="(plan, pKey) in selectedProduct.plans" 
              :key="pKey"
              @click="selectedPlanKey = pKey; onPlanChange()"
              :class="[
                'p-3.5 rounded-2xl border-2 cursor-pointer transition-all flex items-center justify-between',
                selectedPlanKey === pKey 
                  ? 'bg-sky-500/15 border-brand-accent text-white shadow-[0_0_20px_rgba(56,189,248,0.25)]' 
                  : 'bg-slate-950/80 border-slate-800 text-slate-300 hover:border-slate-700 hover:bg-slate-950'
              ]"
            >
              <div class="flex items-center gap-3">
                <div :class="['w-5 h-5 rounded-full border-2 flex items-center justify-center shrink-0', selectedPlanKey === pKey ? 'border-brand-accent bg-brand-accent' : 'border-slate-600']">
                  <div v-if="selectedPlanKey === pKey" class="w-2 h-2 rounded-full bg-slate-950"></div>
                </div>
                <div>
                  <div class="font-black text-sm text-white">{{ plan.label }}</div>
                  <div class="text-[11px] text-slate-400">Entrega rápida con PIN exclusivo</div>
                </div>
              </div>
              <div class="text-right">
                <div class="font-black text-base text-brand-accent font-mono">${{ plan.clientPrice }} <span class="text-[10px] font-normal text-slate-400">ARS</span></div>
                <div class="text-[10px] text-emerald-400 font-bold uppercase tracking-wider">Garantizado</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Nota Oficial: Nombre de Perfil y PIN se entregan en las cuentas -->
        <div class="bg-slate-950/80 border border-slate-800 rounded-2xl p-3.5 flex items-start gap-3 text-xs text-slate-300">
          <span class="text-base shrink-0">🛡️</span>
          <div>
            <div class="font-bold text-white mb-0.5">Asignación Oficial de Perfil & PIN</div>
            <p class="text-[11px] text-slate-400 leading-relaxed">
              El nombre del perfil y su PIN exclusivo se te entregarán directamente en las credenciales finales de tu cuenta, listos para acceder sin necesidad de configuraciones previas.
            </p>
          </div>
        </div>

        <!-- Campo de Correo Personal para Activaciones (CANVA, YouTube Premium, etc.) -->
        <div v-if="selectedProduct?.requiresActivationEmail || selectedProduct?.platform === 'Canva Pro' || selectedProduct?.platform === 'YouTube Premium'" class="bg-indigo-950/40 border border-indigo-500/40 rounded-xl p-3.5 space-y-2">
          <div class="flex items-center gap-2 text-indigo-300 font-bold text-xs uppercase tracking-wider">
            <span>✉️</span>
            <span>Correo para Recibir tu Cuenta</span>
          </div>
          <p class="text-[11px] text-slate-300 leading-relaxed">
            Ingresa el correo electrónico asociado a tu cuenta de {{ selectedProduct.name }} donde recibirás la invitación o activación oficial:
          </p>
          <div>
            <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">
              Tu Correo Personal (Gmail/Canva) <span class="text-rose-400">*</span>
            </label>
            <input 
              v-model="activationEmail" 
              type="email" 
              placeholder="ejemplo@gmail.com"
              class="w-full bg-slate-900 border border-indigo-500/50 focus:border-brand-accent rounded-xl p-3 text-white text-xs font-mono outline-none"
              required
            >
          </div>
        </div>

        <!-- Total y Añadir a la Orden -->
        <div class="flex justify-between items-center bg-slate-950 p-4 rounded-2xl border border-slate-800">
          <div>
            <p class="text-xs text-slate-400 font-medium">Total a pagar:</p>
            <p class="text-2xl font-black text-brand-accent font-mono">
              ${{ selectedProduct.plans[selectedPlanKey]?.clientPrice }} <span class="text-xs text-slate-400 font-normal">ARS</span>
            </p>
          </div>
          <button @click="confirmAddToCart" class="bg-brand-accent text-slate-950 font-black px-6 py-3 rounded-xl hover:bg-sky-400 transition-all shadow-md text-sm cursor-pointer">
            Añadir a la Orden
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL CARRITO PREVIO AL ENVÍO -->
    <div v-if="showCartDrawer" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-black/80 backdrop-blur-sm animate-in fade-in duration-200">
      <div class="bg-slate-900 border border-slate-700 w-full max-w-2xl rounded-2xl p-6 shadow-2xl relative space-y-6 max-h-[90vh] overflow-y-auto overscroll-contain custom-scrollbar">
        <div class="flex justify-between items-center border-b border-slate-800 pb-4">
          <div class="flex items-center gap-2">
            <svg class="w-6 h-6 text-brand-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
            <h3 class="text-xl font-black text-white">Carrito de Compras</h3>
          </div>
          <button @click="showCartDrawer = false" class="text-slate-400 hover:text-white p-1">✕</button>
        </div>

        <div v-if="cartStore.items.length === 0" class="text-center py-12 text-slate-500">
          <p class="text-sm">Tu carrito no tiene ítems añadidos aún.</p>
        </div>

        <div v-else class="space-y-4">
          <div class="space-y-3 max-h-[280px] overflow-y-auto pr-2 custom-scrollbar">
            <div v-for="(item, index) in cartStore.items" :key="index" class="bg-slate-950 p-4 rounded-xl border border-slate-800 flex justify-between items-start gap-4">
              <div class="space-y-1 flex-1">
                <div class="flex items-center gap-2">
                  <img v-if="item.image" :src="item.image" :alt="item.name" class="w-8 h-8 object-contain bg-white rounded p-0.5 shrink-0">
                  <h4 class="font-bold text-white text-sm line-clamp-1">{{ item.name }}</h4>
                  <span :class="['text-[10px] font-black uppercase px-2 py-0.5 rounded', item.itemType === 'physical' ? 'bg-amber-400/20 text-amber-300' : 'bg-brand-accent/20 text-brand-accent']">
                    {{ item.planLabel }}
                  </span>
                  <span v-if="item.units > 1 && item.itemType !== 'physical'" class="text-[10px] bg-slate-800 text-amber-300 font-bold px-2 py-0.5 rounded">
                    {{ item.pinPreference === 'same' ? 'Mismo PIN' : 'PINs Distintos' }}
                  </span>
                </div>
                
                <div v-if="item.itemType !== 'physical'" class="flex items-center gap-1.5 text-[11px] text-slate-400 pt-0.5">
                  <span class="text-emerald-400 font-bold">✓</span> Perfil y PIN oficial asignados al entregar
                </div>

                <div class="text-xs text-slate-400 pt-1">
                  Precio unitario: <strong class="text-white">${{ Number(item.clientPrice).toLocaleString() }} ARS</strong>
                </div>
              </div>

              <div class="flex flex-col items-end gap-2">
                <div class="flex items-center gap-2 bg-slate-900 border border-slate-800 rounded-lg p-1">
                  <button @click="cartStore.updateItemQuantity(index, -1)" class="w-6 h-6 flex items-center justify-center text-slate-400 hover:text-white font-bold bg-slate-800 rounded">-</button>
                  <span class="text-xs font-bold px-1 text-white">{{ item.quantity }}</span>
                  <button @click="cartStore.updateItemQuantity(index, 1)" class="w-6 h-6 flex items-center justify-center text-slate-400 hover:text-white font-bold bg-slate-800 rounded">+</button>
                </div>
                <button @click="cartStore.removeItem(index)" class="text-xs text-red-400 hover:underline">Eliminar</button>
              </div>
            </div>
          </div>

          <div class="bg-slate-950 p-4 rounded-xl space-y-2 text-sm border border-slate-800">
            <div class="flex justify-between text-slate-400">
              <span>Subtotal:</span>
              <span class="text-white font-semibold">${{ cartStore.subtotal }} ARS</span>
            </div>
            <div v-if="cartStore.discountPercentage > 0" class="flex justify-between text-emerald-400 font-bold bg-emerald-400/10 p-2 rounded-lg">
              <span>Descuento por Volumen ({{ cartStore.discountPercentage }}%):</span>
              <span>- ${{ (cartStore.subtotal * (cartStore.discountPercentage / 100)).toFixed(2) }} ARS</span>
            </div>
            <div class="flex justify-between items-center text-lg font-black border-t border-slate-800 pt-2 text-white">
              <span>Total Final:</span>
              <span class="text-brand-accent text-2xl">${{ cartStore.total.toFixed(2) }} ARS</span>
            </div>
          </div>

          <div class="flex gap-3">
            <button @click="showCartDrawer = false" class="flex-1 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold py-3 rounded-xl transition-all text-sm">
              Seguir Comprando
            </button>
            <button @click="openCheckoutForm" class="flex-1 bg-brand-accent hover:bg-sky-400 text-slate-950 font-black py-3 rounded-xl transition-all shadow-lg text-sm flex items-center justify-center gap-2">
              <span>Continuar al Pago</span>
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL CHECKOUT: CON DATOS COMPLETOS DEL REVENDEDOR PARA CONFIRMAR PAGOS -->
    <div v-if="showCheckoutModal" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-black/80 backdrop-blur-sm animate-in fade-in duration-200">
      <div class="bg-slate-900 border border-slate-700 w-full max-w-xl rounded-2xl p-6 sm:p-7 shadow-2xl relative space-y-5 max-h-[90vh] overflow-y-auto overscroll-contain custom-scrollbar">
        <div class="flex justify-between items-start border-b border-slate-800 pb-3">
          <div>
            <div class="flex items-center gap-2">
              <h3 class="text-xl font-black text-white">Finalizar Pedido</h3>
              <span class="text-xs bg-slate-800 text-brand-accent font-mono font-bold px-2 py-0.5 rounded border border-slate-700">
                {{ clientForm.clientNumber }}
              </span>
            </div>
            <p class="text-xs text-slate-400 mt-0.5">Identificador de cliente generado para vincular tu perfil y servicio.</p>
          </div>
          <button @click="showCheckoutModal = false" class="text-slate-400 hover:text-white p-1">✕</button>
        </div>

        <form @submit.prevent="submitOrder" class="space-y-4 text-xs">
          <!-- SECCIÓN DE IDENTIFICACIÓN: SMART CHECKOUT CON REGISTRO O INVITACIÓN -->
          
          <!-- BANNER 1: SI EL CLIENTE YA TIENE PERFIL GUARDADO EN SU DISPOSITIVO -->
          <div v-if="hasSavedProfile" class="bg-gradient-to-r from-emerald-950/40 via-slate-900 to-indigo-950/40 p-3.5 rounded-2xl border border-emerald-500/40 flex items-center justify-between gap-3 shadow-md">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-xl bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 flex items-center justify-center font-black text-sm shrink-0">
                ✓
              </div>
              <div>
                <div class="flex items-center gap-2">
                  <span class="text-xs font-bold text-white">Datos cargados de tu cuenta</span>
                  <span class="text-[9px] bg-emerald-500/20 text-emerald-300 font-mono font-bold px-1.5 py-0.5 rounded border border-emerald-500/30">Cliente Frecuente</span>
                </div>
                <p class="text-[11px] text-slate-400 mt-0.5">
                  Estás comprando como <strong class="text-white">{{ clientForm.fullName }}</strong> ({{ clientForm.whatsapp }}). Puedes editar los campos abajo si compras para otra persona.
                </p>
              </div>
            </div>
            <button 
              type="button" 
              @click="handleLogoutProfile" 
              class="text-xs text-sky-400 hover:text-rose-400 underline font-medium cursor-pointer shrink-0"
              title="Borrar datos guardados en este dispositivo para ingresar otros"
            >
              Cambiar cuenta
            </button>
          </div>

          <!-- BANNER 2: INVITACIÓN A INICIAR SESIÓN SI YA TIENE CUENTA O BÓVEDA -->
          <div v-else class="bg-gradient-to-r from-sky-950/40 via-slate-900 to-indigo-950/40 p-3.5 rounded-2xl border border-sky-500/30 space-y-2.5">
            <div class="flex items-center justify-between gap-3">
              <div class="flex items-center gap-2.5">
                <span class="text-base shrink-0">👋</span>
                <div>
                  <strong class="text-white text-xs block">¿Ya compraste antes o tienes Bóveda?</strong>
                  <p class="text-[11px] text-slate-400">Inicia sesión con tu WhatsApp y PIN para autocompletar tus datos en 1 clic.</p>
                </div>
              </div>
              <button 
                type="button" 
                @click="showQuickLogin = !showQuickLogin" 
                class="text-xs bg-sky-500/20 hover:bg-sky-500/30 text-sky-300 border border-sky-500/40 px-3 py-1.5 rounded-xl font-bold transition cursor-pointer shrink-0"
              >
                {{ showQuickLogin ? 'Cerrar' : '🔑 Iniciar Sesión' }}
              </button>
            </div>

            <!-- Formulario Desplegable de Login Rápido -->
            <div v-if="showQuickLogin" class="pt-2 border-t border-sky-500/20 space-y-2.5 animate-in fade-in duration-150">
              <div class="grid grid-cols-2 gap-2">
                <div>
                  <label class="block text-[10px] text-slate-400 uppercase font-bold mb-1">WhatsApp de Compra</label>
                  <input 
                    v-model="quickLoginPhone" 
                    type="tel" 
                    placeholder="Ej: 11 1234-5678" 
                    class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white outline-none focus:border-sky-400 font-medium"
                  >
                </div>
                <div>
                  <label class="block text-[10px] text-slate-400 uppercase font-bold mb-1">PIN de 4 dígitos</label>
                  <input 
                    v-model="quickLoginPin" 
                    type="password" 
                    maxlength="4"
                    placeholder="••••" 
                    class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-center font-mono font-bold tracking-widest text-amber-300 outline-none focus:border-amber-400"
                  >
                </div>
              </div>
              <div class="flex justify-between items-center pt-0.5">
                <span class="text-[10px] text-slate-500">O completa tus datos abajo para registrarte.</span>
                <button 
                  type="button" 
                  @click="handleQuickLogin" 
                  class="bg-sky-500 hover:bg-sky-400 text-slate-950 font-black text-xs px-3.5 py-1.5 rounded-xl transition cursor-pointer shadow-md"
                >
                  Cargar Mis Datos →
                </button>
              </div>
            </div>
          </div>

          <!-- CAMPOS OBLIGATORIOS Y PERMANENTES DE IDENTIFICACIÓN -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block font-bold uppercase text-slate-400 mb-1">
                Nombre Completo <span class="text-rose-400">*</span>
              </label>
              <input 
                v-model="clientForm.fullName" 
                type="text" 
                placeholder="Ej: Carlos Lopez" 
                class="w-full bg-slate-950 border border-slate-800 focus:border-brand-accent rounded-xl p-3 text-white outline-none font-medium text-xs" 
                required
              >
            </div>
            <div>
              <label class="block font-bold uppercase text-slate-400 mb-1">
                WhatsApp de Contacto <span class="text-rose-400">*</span>
              </label>
              <input 
                v-model="clientForm.whatsapp" 
                type="tel" 
                placeholder="+54 9 11 1234-5678" 
                class="w-full bg-slate-950 border border-slate-800 focus:border-brand-accent rounded-xl p-3 text-white outline-none font-medium text-xs" 
                required
              >
            </div>
          </div>

          <!-- PIN DE SEGURIDAD PARA BÓVEDA PRIVADA (4 DÍGITOS OBLIGATORIO) -->
          <div class="bg-gradient-to-r from-indigo-950/60 via-slate-950 to-slate-950 p-4 rounded-2xl border border-indigo-500/40 space-y-2.5 shadow-lg">
            <div class="flex items-center justify-between">
              <label class="font-black uppercase text-indigo-300 text-xs flex items-center gap-2">
                <span>🔐</span>
                <span>PIN de Acceso a tu Bóveda (4 dígitos) <span class="text-rose-400">*</span></span>
              </label>
              <span class="text-[10px] bg-amber-400/15 text-amber-300 px-2.5 py-0.5 rounded-full font-mono font-bold border border-amber-400/30">
                Obligatorio
              </span>
            </div>
            <p class="text-[11px] text-slate-300 leading-relaxed">
              Elige 4 dígitos personales (ej: <strong>1234</strong>). Te servirán para ingresar a tu Bóveda en cualquier momento y ver tus pedidos o credenciales.
            </p>
            <div class="flex items-center gap-3">
              <input 
                v-model="clientForm.pin" 
                @input="clientForm.pin = (clientForm.pin || '').replace(/\D/g, '').slice(0, 4)"
                type="text" 
                inputmode="numeric"
                pattern="[0-9]*"
                maxlength="4"
                minlength="4"
                class="w-36 bg-slate-900 border-2 border-indigo-500/60 focus:border-amber-400 rounded-xl py-2.5 px-3 text-center text-lg font-black font-mono tracking-[0.3em] text-amber-300 outline-none shadow-inner placeholder:text-slate-600 placeholder:tracking-normal placeholder:text-xs"
                placeholder="Ej: 1234"
                required
              />
              <span class="text-[11px] font-medium text-slate-400">
                {{ clientForm.pin && clientForm.pin.length === 4 ? '✓ PIN de 4 dígitos ingresado' : 'Ingresa 4 dígitos numéricos' }}
              </span>
            </div>
            <p class="text-[10px] text-slate-400 pt-1 leading-normal">
              💡 Tus datos y PIN quedarán guardados en este dispositivo para comprar en 1 clic tus próximas veces sin tener que volver a escribirlos.
            </p>
          </div>

          <!-- Canal Oficial de Despacho Verificado -->
          <div class="flex items-center justify-between p-3.5 bg-slate-950 rounded-xl border border-slate-800">
            <div>
              <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">
                {{ isDirectSale ? 'Canal Oficial de Compra' : 'Canal Autorizado Asignado' }}
              </span>
              <span class="text-xs font-bold text-white">{{ resellerStoreName }}</span>
              <span v-if="!isDirectSale" class="text-[10px] text-amber-400 block font-mono">
                Distribuidor: {{ currentReseller?.name }} ({{ currentReseller?.id }})
              </span>
              <span v-else class="text-[10px] text-sky-400 block font-mono">
                Compra Directa Oficial • Proveedor StreamePro Central
              </span>
            </div>
            <span :class="['text-[10px] font-mono font-bold px-2 py-0.5 rounded flex items-center gap-1', isDirectSale ? 'bg-sky-500/15 text-sky-300 border border-sky-500/30' : 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30']">
              <span>✓</span> {{ isDirectSale ? 'Oficial Directo' : 'Distribuidor' }}
            </span>
          </div>

          <!-- AVISO PRODUCTOS FÍSICOS: PASOS A SEGUIR PARA EL CLIENTE -->
          <div v-if="cartStore.hasPhysicalItems" class="bg-gradient-to-br from-amber-950/60 via-slate-950 to-orange-950/40 p-4 rounded-xl border border-amber-500/40 space-y-2.5 shadow-lg">
            <div class="flex items-center gap-2 text-amber-300 font-black">
              <span class="text-xl">⚠️</span>
              <span class="text-xs uppercase tracking-wider">NO REALICES NINGUNA TRANSFERENCIA AHORA</span>
            </div>
            <p class="text-slate-200 text-xs leading-relaxed">
              Tu pedido quedará guardado en estado <strong class="text-amber-300 font-mono">PENDIENTE</strong>.
            </p>
            <div class="text-[11px] text-slate-300 space-y-2 bg-slate-900/80 p-3 rounded-lg border border-slate-800">
              <div class="font-bold text-amber-400 uppercase tracking-wider text-[10px]">Pasos a seguir:</div>
              <div class="flex items-start gap-2">
                <span class="text-amber-400 font-bold">1.</span>
                <span><strong>Te contactamos por WhatsApp:</strong> Un asesor se comunicará contigo para confirmar el stock de tu producto.</span>
              </div>
              <div class="flex items-start gap-2">
                <span class="text-amber-400 font-bold">2.</span>
                <span><strong>Coordinamos el envío:</strong> Acordaremos la dirección de entrega y el costo exacto hasta tu domicilio.</span>
              </div>
              <div class="flex items-start gap-2">
                <span class="text-amber-400 font-bold">3.</span>
                <span><strong>Pago seguro:</strong> Te brindaremos los datos oficiales para pagar únicamente cuando todo esté confirmado.</span>
              </div>
            </div>
          </div>

          <!-- TARJETA CON DATOS OFICIALES PARA TRANSFERENCIA (SOLO SI TIENE SERVICIOS DIGITALES) -->
          <div v-if="cartStore.hasDigitalItems" class="bg-gradient-to-br from-slate-950 to-indigo-950/40 p-4 rounded-xl border border-brand-accent/30 space-y-2">
            <div class="flex justify-between items-center border-b border-slate-800/80 pb-2">
              <span class="text-[10px] font-black uppercase tracking-wider text-brand-accent">
                {{ isDirectSale ? 'Datos Oficiales del Proveedor Central (Venta Directa)' : 'Datos para Transferencia (Distribuidor Oficial)' }}
              </span>
              <span class="text-[11px] font-mono text-emerald-400">Verificado</span>
            </div>
            
            <!-- CASO 1: VENTA DIRECTA OFICIAL (PROVEEDOR CENTRAL RECIBE PAGO) -->
            <div v-if="isDirectSale" class="space-y-1 text-slate-300 font-mono text-xs">
              <p>Titular Oficial: <strong class="text-white">{{ adminStore.providerBankInfo?.holderName || 'StreamePro Mayorista Central' }}</strong></p>
              <p>Entidad: <span class="text-slate-300">{{ adminStore.providerBankInfo?.bankName || 'Mercado Pago / Banco' }}</span></p>
              <p>CUIT/CUIL: <span class="text-slate-300">{{ adminStore.providerBankInfo?.cuit || '20-00000000-0' }}</span></p>
              <div class="bg-slate-900 p-2.5 rounded-lg border border-slate-800 flex justify-between items-center mt-1.5">
                <span class="text-amber-300 font-bold">Alias/CBU: {{ adminStore.providerBankInfo?.cbuAlias || 'STREAMEPRO.MAYORISTA' }}</span>
                <span class="text-[11px] text-slate-300 font-sans">WhatsApp Oficial: {{ adminStore.providerBankInfo?.whatsapp || adminStore.adminAuth?.whatsapp || '+54 9 291 565-9321' }}</span>
              </div>
            </div>

            <!-- CASO 2: VENTA POR REVENDEDOR REFERIDO -->
            <div v-else class="space-y-1 text-slate-300 font-mono text-xs">
              <p>Titular: <strong class="text-white">{{ currentReseller?.holderName || currentReseller?.name }}</strong></p>
              <p>Entidad: <span class="text-slate-300">{{ currentReseller?.bankName || 'Mercado Pago / Banco' }}</span></p>
              <p>CUIT/CUIL: <span class="text-slate-300">{{ currentReseller?.cuit || '20-00000000-0' }}</span></p>
              <div class="bg-slate-900 p-2.5 rounded-lg border border-slate-800 flex justify-between items-center mt-1.5">
                <span class="text-amber-300 font-bold">Alias/CBU: {{ currentReseller?.cbuAlias || 'PAGOS.DISTRIBUIDOR' }}</span>
                <span class="text-[11px] text-slate-300 font-sans">WhatsApp: {{ currentReseller?.contactNumber }}</span>
              </div>
            </div>
          </div>

          <!-- Dirección de Envío para Productos Físicos -->
          <div v-if="cartStore.hasPhysicalItems">
            <label class="block font-bold uppercase text-amber-400 mb-1">Dirección de Entrega / Envío (Productos Físicos)</label>
            <input 
              v-model="clientForm.shippingAddress" 
              type="text" 
              placeholder="Calle, Número, Piso/Depto, Localidad, Código Postal" 
              class="w-full bg-slate-950 border border-slate-800 focus:border-amber-400 rounded-xl p-3 text-white outline-none font-medium"
              required
            >
          </div>

          <!-- AVISO DE TIEMPO DE ENTREGA PARA STREAMING -->
          <div v-if="cartStore.hasDigitalItems" class="bg-amber-500/10 border border-amber-500/30 rounded-xl p-3 flex items-start gap-2.5 text-xs text-amber-200">
            <span class="text-base shrink-0">⏱️</span>
            <div>
              <strong class="font-bold text-amber-300 block">Tiempo estimado de entrega:</strong>
              <span>La entrega de las cuentas puede demorar de 10 minutos a 1 hora. Carga tu comprobante para agilizar la entrega.</span>
            </div>
          </div>

          <!-- Comprobante de Pago en JPG / PNG o PDF con Mini Nube -->
          <div class="space-y-2 pt-1">
            <div class="flex justify-between items-center">
              <label class="block font-bold uppercase text-slate-300 text-xs">
                Adjuntar Comprobante de Pago (JPG, PNG o PDF)
              </label>
              <span class="text-[10px] text-sky-400 font-mono flex items-center gap-1 font-bold">
                <span>☁️</span> Mini Nube Temporal
              </span>
            </div>

            <!-- Zona de Subida de Archivo -->
            <div 
              v-if="!receiptFile"
              class="border-2 border-dashed border-slate-700 hover:border-brand-accent/60 bg-slate-950/70 rounded-2xl p-4 text-center transition-all cursor-pointer relative group"
            >
              <input 
                type="file" 
                accept="image/jpeg,image/png,image/webp,application/pdf"
                @change="handleReceiptFileChange"
                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
              />
              <div class="space-y-1.5 pointer-events-none">
                <div class="w-10 h-10 rounded-xl bg-slate-900 border border-slate-800 text-brand-accent flex items-center justify-center mx-auto text-xl group-hover:scale-110 transition-transform">
                  📁
                </div>
                <p class="text-xs font-bold text-slate-200">
                  Haz clic o arrastra tu comprobante en <span class="text-brand-accent font-black">JPG, PNG o PDF</span>
                </p>
                <p class="text-[10px] text-slate-400">
                  Llegará directamente al panel del revendedor y proveedor para verificar el pago y se eliminará al confirmar la entrega.
                </p>
              </div>
            </div>

            <!-- Vista previa del comprobante cargado -->
            <div 
              v-else 
              class="bg-slate-950 p-3 rounded-2xl border border-emerald-500/40 flex items-center justify-between gap-3 shadow-lg"
            >
              <div class="flex items-center gap-3 overflow-hidden">
                <div v-if="receiptFile.fileType === 'application/pdf'" class="w-10 h-10 rounded-xl bg-red-950/50 border border-red-500/30 text-red-400 flex items-center justify-center text-lg shrink-0">
                  📄
                </div>
                <div v-else class="w-10 h-10 rounded-xl overflow-hidden border border-emerald-500/30 shrink-0 bg-black flex items-center justify-center">
                  <img :src="receiptFile.dataUrl" alt="Comprobante" class="w-full h-full object-cover" />
                </div>
                <div class="overflow-hidden">
                  <div class="flex items-center gap-1.5">
                    <span class="text-emerald-400 text-xs font-black truncate max-w-[190px]">{{ receiptFile.fileName }}</span>
                    <span class="text-[9px] bg-emerald-500/20 text-emerald-300 font-mono px-1.5 py-0.2 rounded font-bold">☁️ En Nube</span>
                  </div>
                  <p class="text-[10px] text-slate-400">
                    {{ receiptFile.fileType === 'application/pdf' ? 'Documento PDF' : 'Imagen' }} • {{ Math.round(receiptFile.fileSize / 1024) }} KB
                  </p>
                </div>
              </div>
              <button 
                type="button" 
                @click="removeReceiptFile" 
                class="px-2.5 py-1.5 bg-slate-900 hover:bg-red-950/60 text-slate-400 hover:text-red-300 text-xs font-bold rounded-xl border border-slate-800 transition-colors cursor-pointer shrink-0"
                title="Quitar comprobante"
              >
                ✕ Quitar
              </button>
            </div>

            <!-- Número de Operación Opcional -->
            <div class="pt-1">
              <input 
                v-model="clientForm.transferVoucher" 
                type="text" 
                placeholder="N° de Operación o Referencia de Transferencia (Opcional)" 
                class="w-full bg-slate-950 border border-slate-800 focus:border-brand-accent rounded-xl px-3 py-2.5 text-xs text-white outline-none font-mono"
              />
            </div>
          </div>

          <div class="bg-slate-950 p-3 rounded-xl border border-slate-800 flex justify-between items-center text-sm">
            <span class="text-slate-400 font-semibold">
              {{ cartStore.hasPhysicalItems && !cartStore.hasDigitalItems ? 'Total Estimado del Pedido:' : 'Total a transferir:' }}
            </span>
            <span :class="['font-black text-lg', cartStore.hasPhysicalItems && !cartStore.hasDigitalItems ? 'text-amber-400' : 'text-brand-accent']">
              ${{ cartStore.total.toFixed(2) }} ARS
            </span>
          </div>

          <div class="flex gap-3 pt-2">
            <button type="button" @click="showCheckoutModal = false; showCartDrawer = true" class="w-1/3 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold py-3 rounded-xl">
              Volver
            </button>
            <button 
              type="submit" 
              :class="[
                'w-2/3 font-black py-3 rounded-xl transition-all shadow-lg text-sm',
                cartStore.hasPhysicalItems && !cartStore.hasDigitalItems 
                  ? 'bg-amber-400 hover:bg-amber-300 text-slate-950 shadow-amber-400/20' 
                  : 'bg-brand-accent text-slate-950 hover:bg-sky-400 shadow-brand-accent/20'
              ]"
            >
              {{ cartStore.hasPhysicalItems && !cartStore.hasDigitalItems ? 'Confirmar Pedido Físico (Estado Pendiente)' : 'Confirmar y Finalizar Pedido' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL: FICHA TÉCNICA E INFORMACIÓN DEL SERVICIO (+INFO) -->
    <div v-if="showStreamingInfoModal && selectedStreamingInfo" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-sm animate-in fade-in duration-200">
      <div class="bg-slate-900 border border-slate-700 w-full max-w-xl max-h-[90vh] overflow-y-auto overscroll-contain custom-scrollbar rounded-3xl p-6 shadow-2xl relative space-y-5">
        <div class="flex justify-between items-start border-b border-slate-800 pb-3">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-sky-500/10 flex items-center justify-center text-sky-400 border border-sky-500/20 text-lg">
              ℹ️
            </div>
            <div>
              <h3 class="text-xl font-black text-white">{{ selectedStreamingInfo.name }}</h3>
              <p class="text-xs text-sky-400 font-semibold">{{ selectedStreamingInfo.category }} • Garantía Oficial StreamePro</p>
            </div>
          </div>
          <button @click="showStreamingInfoModal = false" class="text-slate-400 hover:text-white p-1 text-lg cursor-pointer">✕</button>
        </div>

        <!-- Descripción General -->
        <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 space-y-2">
          <h4 class="text-xs font-bold uppercase tracking-wider text-slate-300">Descripción del Servicio</h4>
          <p class="text-xs text-slate-300 leading-relaxed">{{ selectedStreamingInfo.description }}</p>
        </div>

        <!-- Modalidades y Precios -->
        <div class="space-y-2">
          <h4 class="text-xs font-bold uppercase tracking-wider text-slate-300">Modalidades Disponibles</h4>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
            <div 
              v-for="(plan, pKey) in selectedStreamingInfo.plans" 
              :key="pKey"
              class="bg-slate-950 p-3 rounded-xl border border-slate-800 flex justify-between items-center"
            >
              <div>
                <span class="font-bold text-white block">{{ plan.label }}</span>
                <span class="text-[10px] text-slate-400">{{ plan.type === 'perfil' ? '1 Pantalla exclusiva' : plan.units + ' Pantallas simultáneas' }}</span>
              </div>
              <span class="text-sm font-black text-amber-300 font-mono">${{ plan.clientPrice }} ARS</span>
            </div>
          </div>
        </div>

        <!-- Características y Especificaciones Técnicas -->
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 text-xs">
          <div class="bg-slate-950 p-3 rounded-xl border border-slate-800">
            <span class="text-[10px] uppercase font-bold text-slate-500 block">Calidad</span>
            <span class="font-bold text-white text-xs">4K Ultra HD + HDR</span>
          </div>
          <div class="bg-slate-950 p-3 rounded-xl border border-slate-800">
            <span class="text-[10px] uppercase font-bold text-slate-500 block">Audio</span>
            <span class="font-bold text-white text-xs">Dolby Atmos / 5.1</span>
          </div>
          <div class="bg-slate-950 p-3 rounded-xl border border-slate-800">
            <span class="text-[10px] uppercase font-bold text-slate-500 block">Descargas</span>
            <span class="font-bold text-white text-xs">Modo Offline</span>
          </div>
          <div class="bg-slate-950 p-3 rounded-xl border border-slate-800">
            <span class="text-[10px] uppercase font-bold text-slate-500 block">Compatibilidad</span>
            <span class="font-bold text-white text-xs">TV, PC, Móviles</span>
          </div>
          <div class="bg-slate-950 p-3 rounded-xl border border-slate-800">
            <span class="text-[10px] uppercase font-bold text-slate-500 block">Seguridad</span>
            <span class="font-bold text-white text-xs">PIN Exclusivo</span>
          </div>
          <div class="bg-slate-950 p-3 rounded-xl border border-slate-800">
            <span class="text-[10px] uppercase font-bold text-slate-500 block">Garantía</span>
            <span class="font-bold text-emerald-400 text-xs">30 Días Total</span>
          </div>
        </div>

        <!-- Sección Específica si es Netflix: Hogar con Netflix -->
        <div v-if="selectedStreamingInfo.platform === 'Netflix'" class="bg-red-950/30 border border-red-500/30 p-4 rounded-2xl text-xs space-y-1.5">
          <div class="flex items-center gap-2 text-red-400 font-bold">
            <span>🏠</span>
            <span>Soporte de Actualización de Hogar con Netflix y Viajes</span>
          </div>
          <p class="text-slate-300 text-[11px] leading-relaxed">
            Nuestras cuentas Netflix cuentan con asistencia técnica y actualización de hogar. Si en tu Smart TV aparece el aviso de "Hogar con Netflix", podrás solicitar tu código o enlace de confirmación con 1 solo clic desde tu panel de cliente.
          </p>
        </div>

        <!-- Sección Específica si es Canva Pro o YouTube: Activación personal -->
        <div v-if="selectedStreamingInfo.requiresActivationEmail || selectedStreamingInfo.platform === 'Canva Pro' || selectedStreamingInfo.platform === 'YouTube Premium'" class="bg-indigo-950/30 border border-indigo-500/30 p-4 rounded-2xl text-xs space-y-1.5">
          <div class="flex items-center gap-2 text-indigo-300 font-bold">
            <span>✉️</span>
            <span>Activación a tu Propio Correo Electrónico</span>
          </div>
          <p class="text-slate-300 text-[11px] leading-relaxed">
            Este servicio se vincula a tu correo personal (Gmail o Canva). No pierdes tus proyectos, listas de reproducción ni historial: se activa directamente sobre tu cuenta.
          </p>
        </div>

        <!-- Botones de Acción -->
        <div class="flex justify-end gap-3 pt-3 border-t border-slate-800">
          <button 
            type="button" 
            @click="showStreamingInfoModal = false"
            class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-400 hover:text-white bg-slate-800 hover:bg-slate-700 transition-colors cursor-pointer"
          >
            Cerrar
          </button>
          <button 
            type="button" 
            @click="openConfigFromInfo"
            class="px-6 py-2.5 rounded-xl text-xs font-black text-slate-950 bg-brand-accent hover:bg-sky-400 transition-all shadow-[0_0_15px_rgba(56,189,248,0.3)] cursor-pointer flex items-center gap-2"
          >
            <span>Elegir Plan</span>
            <span>→</span>
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL DE DETALLE DE PRODUCTO FÍSICO -->
    <div v-if="showPhysicalModal && selectedPhysicalProduct" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm animate-in fade-in duration-200">
      <div class="bg-slate-900 border border-slate-700 w-full max-w-xl rounded-3xl p-6 shadow-2xl relative space-y-5 max-h-[90vh] overflow-y-auto overscroll-contain custom-scrollbar">
        <div class="flex justify-between items-start border-b border-slate-800 pb-3">
          <div class="flex items-center gap-2">
            <span class="bg-amber-400/20 text-amber-300 text-[10px] font-black uppercase px-2 py-0.5 rounded border border-amber-400/30">
              Catálogo Físico Oficial
            </span>
            <span class="text-slate-400 text-xs font-mono">ID: #{{ selectedPhysicalProduct.id }}</span>
          </div>
          <button @click="showPhysicalModal = false" class="text-slate-400 hover:text-white p-1">✕</button>
        </div>

        <div class="flex flex-col sm:flex-row gap-6 items-center">
          <div class="w-full sm:w-64 sm:h-64 h-56 bg-white rounded-3xl p-4 flex items-center justify-center shrink-0 shadow-2xl border-2 border-white/20 overflow-hidden group">
            <img :src="selectedPhysicalProduct.image" :alt="selectedPhysicalProduct.name" class="max-h-full max-w-full object-contain group-hover:scale-105 transition-transform duration-300" @error="(e) => e.target.src = 'https://tua.ar/images/banner22.webp'">
          </div>
          <div class="space-y-2 flex-1">
            <h3 class="text-lg font-black text-white leading-snug">{{ selectedPhysicalProduct.name }}</h3>
            <div class="text-2xl font-black text-amber-400">{{ selectedPhysicalProduct.price }}</div>
            <div v-if="selectedPhysicalProduct.priceNumeric >= 40000" class="inline-block bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 text-xs font-bold px-2.5 py-0.5 rounded-full">
              ✓ Califica para Envío Gratis
            </div>
          </div>
        </div>

        <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 space-y-2 text-xs">
          <h4 class="font-bold text-slate-300 uppercase tracking-wider text-[11px]">Descripción y Especificaciones:</h4>
          <p class="text-slate-300 whitespace-pre-line leading-relaxed">{{ selectedPhysicalProduct.description || 'Sin descripción adicional.' }}</p>
        </div>

        <!-- Previsualización de Enlace Directo del Producto -->
        <div class="bg-slate-950 p-3.5 rounded-2xl border border-sky-500/30 space-y-2 text-xs">
          <div class="flex items-center justify-between">
            <span class="font-bold text-sky-400 flex items-center gap-1.5 text-[11px]">
              <span>🔗</span> Enlace Directo Oficial del Producto:
            </span>
            <span class="text-[10px] font-mono text-slate-400 bg-slate-900 px-2 py-0.5 rounded border border-slate-800">
              Ref: {{ clientForm.selectedResellerId || 'DIRECT' }}
            </span>
          </div>
          <div class="flex items-center gap-2">
            <input 
              :value="getProductDirectUrl(selectedPhysicalProduct)" 
              readonly 
              class="flex-1 bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-200 font-mono outline-none select-all"
            >
            <button 
              @click="copyShareProductUrl(selectedPhysicalProduct)" 
              class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs px-3.5 py-2 rounded-xl transition flex items-center gap-1"
              title="Copiar URL"
            >
              <span>📋</span> Copiar
            </button>
          </div>
        </div>

        <!-- Opción de Compartir Producto: AYUDAR COMPARTIENDO -->
        <div class="bg-gradient-to-r from-amber-950/40 via-slate-950 to-slate-950 p-4 rounded-2xl border border-amber-500/30 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs shadow-md">
          <div class="space-y-0.5">
            <div class="font-black text-amber-300 flex items-center gap-1.5">
              <span>🤝</span>
              <span>AYUDAR COMPARTIENDO</span>
            </div>
            <div class="text-[11px] text-slate-300">
              El producto que necesitas está aquí. Comparte el enlace directo con quien quieras.
            </div>
          </div>
          <div class="flex flex-wrap gap-2 shrink-0 w-full sm:w-auto">
            <button @click="copyShareProductUrl(selectedPhysicalProduct)" class="flex-1 sm:flex-none bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 font-black px-4 py-2 rounded-xl border border-amber-500/40 flex items-center justify-center gap-1.5 transition-all text-xs">
              <span>🤝 Ayudar Compartiendo</span>
            </button>
            <button @click="shareProductWhatsApp(selectedPhysicalProduct)" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold px-3 py-2 rounded-xl flex items-center justify-center gap-1 transition-all text-xs">
              <span>WhatsApp</span>
            </button>
            <button @click="openFlyerForPhysical(selectedPhysicalProduct)" class="bg-gradient-to-r from-amber-500/20 to-orange-500/20 hover:from-amber-500/30 text-amber-300 font-black px-3.5 py-2 rounded-xl border border-amber-500/40 flex items-center justify-center gap-1.5 transition-all text-xs" title="Crear Flyer publicitario para WhatsApp e Instagram de este producto">
              <span>🎨 Flyer</span>
            </button>
          </div>
        </div>

        <div class="flex items-center gap-3 pt-2">
          <div class="flex items-center border border-slate-800 rounded-xl bg-slate-950 p-1">
            <button @click="physicalQuantity = Math.max(1, physicalQuantity - 1)" class="w-8 h-8 rounded-lg bg-slate-800 text-white font-bold hover:bg-slate-700">-</button>
            <span class="px-3 text-sm font-bold text-white">{{ physicalQuantity }}</span>
            <button @click="physicalQuantity++" class="w-8 h-8 rounded-lg bg-slate-800 text-white font-bold hover:bg-slate-700">+</button>
          </div>
          <button @click="addPhysicalToCart(selectedPhysicalProduct, physicalQuantity)" class="flex-1 bg-amber-500 hover:bg-amber-400 text-slate-950 font-black py-3 rounded-xl transition-all shadow-lg text-sm flex items-center justify-center gap-2">
            <span>Agregar al Pedido ({{ physicalQuantity }} un.)</span>
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL DE CONFIRMACIÓN DE PEDIDO Y ENVÍO DE COMPROBANTE POR WHATSAPP -->
    <div v-if="showOrderSuccessModal && lastPlacedOrder" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-md animate-in fade-in duration-200">
      <div class="bg-slate-900 border border-slate-700 w-full max-w-xl rounded-3xl p-6 sm:p-8 shadow-2xl relative space-y-6 max-h-[90vh] overflow-y-auto overscroll-contain custom-scrollbar">
        
        <!-- Header con ícono de éxito -->
        <div class="text-center space-y-3">
          <div class="w-16 h-16 rounded-2xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 flex items-center justify-center text-3xl mx-auto shadow-xl shadow-emerald-500/10">
            ✓
          </div>
          <div>
            <span class="text-[11px] font-mono font-bold text-emerald-400 bg-emerald-500/10 px-3 py-1 rounded-full border border-emerald-500/20">
              PEDIDO REGISTRADO CON ÉXITO
            </span>
            <h3 class="text-2xl font-black text-white mt-2">¡Gracias por tu compra, {{ lastPlacedOrder.customerName }}!</h3>
            <p class="text-xs text-slate-400 mt-1">
              Tu identificador de pedido es <span class="text-brand-accent font-mono font-bold text-sm">#{{ lastPlacedOrder.clientNumber }}</span>
            </p>
          </div>
        </div>

        <!-- CLAVE DE ACCESO A TU BÓVEDA -->
        <div class="bg-gradient-to-r from-amber-500/10 via-slate-950 to-indigo-950/40 p-4 rounded-2xl border border-amber-500/30 flex items-center justify-between gap-3 shadow-lg">
          <div>
            <div class="flex items-center gap-1.5 text-amber-400 font-bold text-xs uppercase tracking-wider">
              <span>🔐</span>
              <span>Clave PIN de tu Bóveda Privada</span>
            </div>
            <p class="text-[11px] text-slate-300 mt-0.5">
              Guarda este PIN junto a tu celular para consultar tus accesos y cuentas en <span class="text-sky-300 font-mono">streamepro.me/boveda</span>
            </p>
          </div>
          <div class="bg-slate-950 border border-amber-500/50 px-4 py-2 rounded-xl text-center shrink-0 shadow-inner">
            <span class="text-[9px] text-slate-400 uppercase font-mono block">Tu PIN</span>
            <span class="text-xl font-black font-mono text-amber-300 tracking-widest">{{ lastPlacedOrder.pin }}</span>
          </div>
        </div>

        <!-- PASO 1: RESUMEN DEL PEDIDO -->
        <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 space-y-2.5 text-xs font-mono">
          <div class="flex justify-between items-center text-slate-400 border-b border-slate-800/80 pb-2">
            <span class="font-bold uppercase tracking-wider text-[10px]">Detalle de Artículos</span>
            <span class="text-white font-bold">{{ lastPlacedOrder.items.length }} ítem(s)</span>
          </div>

          <div class="space-y-1.5 max-h-36 overflow-y-auto pr-1">
            <div v-for="(item, idx) in lastPlacedOrder.items" :key="idx" class="flex justify-between items-center text-slate-300">
              <span class="truncate max-w-[220px] sm:max-w-[300px]">
                {{ item.name || item.platform }} <span class="text-slate-500">(x{{ item.quantity || item.units || 1 }})</span>
              </span>
              <span class="text-white font-bold shrink-0">
                ${{ ((item.clientPrice || item.priceNumeric || 0) * (item.quantity || item.units || 1)).toLocaleString() }} ARS
              </span>
            </div>
          </div>

          <div class="border-t border-slate-800/80 pt-2 flex justify-between items-center text-sm font-sans font-bold">
            <span class="text-slate-300">Total a Abonar:</span>
            <span class="text-emerald-400 text-xl font-mono font-black">${{ lastPlacedOrder.total.toLocaleString() }} ARS</span>
          </div>
        </div>

        <!-- PASO 2: DATOS BANCARIOS (SI TIENE STREAMING / DIGITAL) -->
        <div v-if="lastPlacedOrder.digitalItems.length > 0" class="bg-gradient-to-br from-indigo-950/40 via-slate-950 to-slate-950 p-5 rounded-2xl border border-brand-accent/40 space-y-3">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-brand-accent uppercase tracking-wider flex items-center gap-1.5">
              <span>💳</span>
              <span>Datos Oficiales para Transferir</span>
            </span>
            <button 
              @click="copyBankDetails" 
              class="bg-brand-accent/20 hover:bg-brand-accent/30 text-brand-accent font-bold px-2.5 py-1 rounded-lg text-[10px] transition-colors border border-brand-accent/30 cursor-pointer flex items-center gap-1"
            >
              <span>📋 Copiar Datos</span>
            </button>
          </div>

          <div class="space-y-1.5 text-xs font-mono text-slate-300">
            <div class="flex justify-between">
              <span class="text-slate-500">Titular:</span>
              <strong class="text-white">
                {{ lastPlacedOrder.isDirect ? (lastPlacedOrder.providerBankInfo?.holderName || 'StreamePro Mayorista Central') : (lastPlacedOrder.reseller?.holderName || lastPlacedOrder.reseller?.name) }}
              </strong>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500">Banco / Billetera:</span>
              <span class="text-slate-200">
                {{ lastPlacedOrder.isDirect ? (lastPlacedOrder.providerBankInfo?.bankName || 'Mercado Pago / Banco') : (lastPlacedOrder.reseller?.bankName || 'Mercado Pago') }}
              </span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500">CUIT / CUIL:</span>
              <span class="text-slate-200">
                {{ lastPlacedOrder.isDirect ? (lastPlacedOrder.providerBankInfo?.cuit || '20-00000000-0') : (lastPlacedOrder.reseller?.cuit || '20-00000000-0') }}
              </span>
            </div>
            <div class="p-2.5 bg-slate-900 rounded-xl border border-slate-800 flex justify-between items-center mt-2">
              <span class="text-slate-400 text-[11px]">CBU / Alias:</span>
              <span class="text-amber-300 font-bold text-sm tracking-wide">
                {{ lastPlacedOrder.isDirect ? (lastPlacedOrder.providerBankInfo?.cbuAlias || 'STREAMEPRO.MAYORISTA') : (lastPlacedOrder.reseller?.cbuAlias || 'PAGOS.DISTRIBUIDOR') }}
              </span>
            </div>
          </div>
        </div>

        <!-- AVISO DE PRODUCTO FÍSICO (SI TIENE PRODUCTOS FÍSICOS) -->
        <div v-if="lastPlacedOrder.physicalItems.length > 0" class="bg-amber-500/10 border border-amber-500/30 p-4 rounded-2xl text-xs text-amber-300 space-y-1">
          <div class="font-bold flex items-center gap-1.5 text-amber-400">
            <span>📦</span>
            <span>Productos Físicos en Estado PENDIENTE</span>
          </div>
          <p class="text-slate-300 text-[11px] leading-relaxed">
            Un asesor oficial se comunicará contigo por WhatsApp para confirmar stock disponible y acordar el costo final de entrega a tu domicilio antes de solicitar cualquier pago.
          </p>
        </div>

        <!-- AVISO DE TIEMPO DE ENTREGA Y ESTADO DIRECTO EN PANEL -->
        <div class="bg-gradient-to-br from-emerald-950/40 via-slate-900 to-sky-950/40 border border-emerald-500/30 p-4 rounded-2xl text-xs space-y-2 shadow-lg">
          <div class="flex items-center gap-2 text-emerald-400 font-bold">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
            <span>¡Pedido Registrado Directamente en el Panel Oficial!</span>
          </div>
          <p class="text-slate-300 text-xs leading-relaxed">
            Tu orden ha ingresado de forma instantánea al <strong class="text-white">Panel del {{ lastPlacedOrder.isDirect ? 'Proveedor Mayorista' : 'Revendedor y Proveedor' }}</strong>.
            Tiempo estimado de entrega de accesos: <strong class="text-amber-300 font-bold">10 minutos a 1 hora</strong>.
          </p>

          <!-- Estado del Comprobante en Mini Nube -->
          <div v-if="lastPlacedOrder.hasReceipt" class="bg-sky-500/10 border border-sky-500/20 p-2.5 rounded-xl flex items-center justify-between gap-2 text-[11px] text-sky-200 font-mono">
            <div class="flex items-center gap-2 truncate">
              <span>☁️</span>
              <span class="truncate">Comprobante en Mini Nube: <strong>{{ lastPlacedOrder.receiptFileName || 'Archivo cargado' }}</strong></span>
            </div>
            <span class="text-emerald-400 font-bold shrink-0">✓ Verificando</span>
          </div>
          <div v-else class="text-[11px] text-amber-300/90 flex items-center gap-1.5">
            <span>ℹ️</span>
            <span>No adjuntaste comprobante. Puedes consultarlo o reportarlo desde tu Bóveda de Clientes.</span>
          </div>
        </div>

        <!-- ACCIONES PRINCIPALES: BÓVEDA DIRECTA Y WHATSAPP OPCIONAL -->
        <div class="space-y-3 pt-1">
          <!-- BOTÓN PRINCIPAL: IR A LA BÓVEDA -->
          <button 
            @click="goToCustomerPortal" 
            class="w-full bg-gradient-to-r from-sky-500 via-indigo-600 to-blue-600 hover:brightness-110 text-white font-black py-4 rounded-2xl shadow-xl shadow-indigo-500/25 transition-all text-sm sm:text-base flex items-center justify-center gap-2 cursor-pointer"
          >
            <span class="text-xl">🔐</span>
            <span>Ir a mi Bóveda y Consultar Mis Accesos</span>
            <span>→</span>
          </button>

          <!-- BOTÓN SECUNDARIO: WHATSAPP OPCIONAL -->
          <button 
            @click="sendOrderToWhatsApp" 
            class="w-full bg-emerald-600/20 hover:bg-emerald-600/30 border border-emerald-500/40 text-emerald-300 hover:text-white font-bold py-3 rounded-xl text-xs transition-colors flex items-center justify-center gap-2 cursor-pointer"
          >
            <span class="text-base">💬</span>
            <span>Enviar comprobante o consultar por WhatsApp (Opcional)</span>
            <span class="text-[10px] bg-slate-950/40 px-2 py-0.5 rounded-full font-mono text-emerald-400 font-bold">+54 9 291 565-9321</span>
          </button>

          <div class="flex justify-center pt-1">
            <button 
              @click="showOrderSuccessModal = false" 
              class="text-slate-400 hover:text-slate-200 text-xs py-1 transition-colors cursor-pointer"
            >
              🛒 Continuar en la Tienda
            </button>
          </div>
        </div>

      </div>
    </div>

    <!-- MODAL LIGHTBOX: IMAGEN DE PRODUCTO FÍSICO EN FOCO HD -->
    <div v-if="showImageZoomModal && selectedPhysicalProduct" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/90 backdrop-blur-md animate-in fade-in duration-200" @click="showImageZoomModal = false">
      <div class="relative max-w-2xl w-full bg-slate-900 border border-slate-700 rounded-3xl p-5 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto overscroll-contain custom-scrollbar" @click.stop>
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
          <div>
            <h4 class="font-black text-white text-sm sm:text-base">{{ selectedPhysicalProduct.name }}</h4>
            <span class="text-amber-400 font-black text-xs font-mono">{{ selectedPhysicalProduct.price }}</span>
          </div>
          <button @click="showImageZoomModal = false" class="w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 text-white font-bold flex items-center justify-center transition cursor-pointer">✕</button>
        </div>
        <div class="w-full h-80 sm:h-96 bg-white rounded-2xl p-4 flex items-center justify-center shadow-inner overflow-hidden">
          <img :src="selectedPhysicalProduct.image" :alt="selectedPhysicalProduct.name" class="max-h-full max-w-full object-contain" @error="(e) => e.target.src = 'https://tua.ar/images/banner22.webp'">
        </div>
        <div class="flex justify-between items-center text-xs text-slate-400">
          <span class="font-mono text-[11px] text-emerald-400 flex items-center gap-1">
            <span>✓</span> Imagen Oficial en Foco Ultra HD
          </span>
          <button @click="showImageZoomModal = false" class="bg-slate-800 hover:bg-slate-700 text-white px-4 py-1.5 rounded-xl font-bold transition">Cerrar</button>
        </div>
      </div>
    </div>

    <!-- MODAL GENERADOR DE FLYERS (SOLO PRODUCTOS FÍSICOS PARA CLIENTES) -->
    <FlyerGeneratorModal 
      :show="showFlyerModal" 
      :initial-product="flyerInitialProduct"
      :reseller-ref="clientForm.selectedResellerId || 'DIRECT'"
      :default-store-name="resellerStoreName"
      website-domain="streamepro.me"
      @close="showFlyerModal = false" 
    />
  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #334155;
  border-radius: 4px;
}
</style>

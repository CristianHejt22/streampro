<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useCartStore } from '../store/cart'
import { useAdminStore, PLATFORM_PLANS } from '../store/admin'
import { useToastStore } from '../store/toast'
import { sendLocalNotification } from '../services/notifications'
import { saveReceipt, processReceiptFile } from '../services/receiptCloudStorage'
import FlyerGeneratorModal from '../components/FlyerGeneratorModal.vue'
import { getRememberedSession } from '../services/biometrics'

const route = useRoute()
const router = useRouter()
const cartStore = useCartStore()
const adminStore = useAdminStore()
const toast = useToastStore()

// Detección de sesión activa de Administrador para previsualización cuando la tienda está oculta
const isAdminUser = computed(() => {
  try {
    const saved = getRememberedSession('admin')
    if (saved && (saved.username || saved.id === 'admin')) return true
    if (route.query.preview === 'admin') return true
  } catch (e) {}
  return false
})

// Mapeo dinámico y enriquecido de íconos para categorías del catálogo físico
const getCategoryIcon = (catId, catName = '') => {
  const id = String(catId || '').toLowerCase()
  const name = String(catName || '').toLowerCase()
  if (id === 'virales' || name.includes('viral')) return '🔥'
  if (id === 'perfumes' || name.includes('perfum')) return '🌸'
  if (id === 'hogar' || name.includes('hogar') || name.includes('deco')) return '🏠'
  if (id.includes('juguet') || name.includes('juguet')) return '🎮'
  if (id.includes('celular') || name.includes('celular') || name.includes('telef')) return '📱'
  if (id === 'herramientas' || name.includes('herramienta')) return '🔧'
  if (id === 'auriculares' || name.includes('audio') || name.includes('auricular')) return '🎧'
  if (id === 'belleza' || name.includes('belleza') || name.includes('cuidado')) return '✨'
  if (id === 'smartwatch' || name.includes('reloj') || name.includes('watch')) return '⌚'
  if (id.includes('madre') || name.includes('madre') || name.includes('regalo')) return '🎁'
  if (id === 'bazar' || name.includes('bazar') || name.includes('cocina')) return '🍳'
  if (id === 'electronica' || name.includes('electr')) return '⚡'
  if (id === 'luces' || name.includes('iluminac') || name.includes('lampara')) return '💡'
  if (id === 'auto' || name.includes('vehicul') || name.includes('auto')) return '🚗'
  return '📦'
}

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
const selectedPhysicalVariant = ref(null)
const activePhysicalImage = ref('')
const showPhysicalModal = ref(false)
const showImageZoomModal = ref(false)
const physicalQuantity = ref(1)

// Modal del Carrito previo al envío
const showCartDrawer = ref(false)

watch(() => cartStore.isDrawerOpen, (isOpen) => {
  showCartDrawer.value = isOpen
})
watch(showCartDrawer, (isOpen) => {
  cartStore.isDrawerOpen = isOpen
})

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

  // Apertura automática del carrito si viene en URL (?openCart=true)
  if (route.query.openCart === 'true' || route.query.cart === 'true') {
    showCartDrawer.value = true
    cartStore.openDrawer()
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

// GESTIÓN DE PRODUCTOS FÍSICOS (Sincronizado con revende.com.ar)
const openPhysicalModal = (product) => {
  selectedPhysicalProduct.value = product
  physicalQuantity.value = 1
  if (product.variables && product.variables.length > 0) {
    const firstAvailable = product.variables.find(v => v.available && v.stock > 0) || product.variables[0]
    selectedPhysicalVariant.value = firstAvailable
    activePhysicalImage.value = firstAvailable?.image || product.image
  } else {
    selectedPhysicalVariant.value = null
    activePhysicalImage.value = product.image
  }
  showPhysicalModal.value = true
}

const selectPhysicalVariant = (variant) => {
  selectedPhysicalVariant.value = variant
  if (variant.image) {
    activePhysicalImage.value = variant.image
  }
}

const selectPhysicalImage = (imgUrl) => {
  activePhysicalImage.value = imgUrl
}

const addPhysicalToCart = (product, qty = 1) => {
  if (!selectedPhysicalProduct.value && product.variables && product.variables.length > 1) {
    openPhysicalModal(product)
    return
  }
  const variant = (selectedPhysicalProduct.value?.id === product.id)
    ? selectedPhysicalVariant.value
    : (product.variables?.[0] || null)

  cartStore.addPhysicalItem(product, qty, variant)
  const variantTxt = variant && variant.name !== 'Variante 1' ? ` (${variant.name})` : ''
  toast.add(`"${product.name.slice(0, 25)}..."${variantTxt} añadido al carrito`, 'success')
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
  const discountText = product.hasDiscount ? ` (${product.discountPercentage}% OFF 🔥 Antes: ${product.originalPrice})` : ''
  const msg = `🔥 *MIRA ESTE PRODUCTO EN STREAMEPRO*\n📦 *${product.name}*\n💰 Precio: *${product.price}*${discountText}\n\n👉 Compra directa y garantía oficial aquí:\n${shareUrl}`
  window.open(`https://wa.me/?text=${encodeURIComponent(msg)}`, '_blank')
}

// SEO Dinámico y Títulos Optimizados
watch([storeSection, selectedPhysicalProduct], ([sec, prod]) => {
  if (prod) {
    document.title = `${prod.name} (${prod.price}) | StreamePro Oficial`
  } else if (sec === 'physical') {
    document.title = 'Tienda de Productos Físicos & Tecnología | StreamePro Oficial'
  } else {
    document.title = 'StreamePro | Suscripciones Streaming Oficiales & Combos'
  }
}, { immediate: true })

// ==================== BÚSQUEDA, FILTROS Y ORDENAMIENTO ====================
// Filtros y Paginación de Suscripciones Digitales
const searchQuery = ref('')
const selectedCategory = ref('All')
const digitalPlatformFilter = ref('All')
const digitalCurrentPage = ref(1)
const digitalItemsPerPage = ref(8) // 4, 6, 8, 'all'

// Paginación de Paquetes y Combos
const packagesCurrentPage = ref(1)
const packagesItemsPerPage = ref(6) // 4, 6, 8, 'all'

// Filtros, Ordenamiento y Paginación de Productos Físicos
const physicalSearchQuery = ref('')
const physicalCategoryFilter = ref('All')
const onlyDiscountsFilter = ref(false)
const freeShippingFilter = ref(false)
const inStockOnlyFilter = ref(false)
const physicalPriceRange = ref('all') // 'all', 'under_15k', '15k_to_30k', '30k_to_60k', 'over_60k'
const minPriceFilter = ref('')
const maxPriceFilter = ref('')
const physicalSortBy = ref('default') // 'default', 'price_asc', 'price_desc', 'discount_desc', 'stock_desc', 'name_asc'
const physicalCurrentPage = ref(1)
const physicalItemsPerPage = ref(16) // 12, 16, 24, 32, 48, 'all'

// Métricas computadas de filtros rápidos del catálogo físico
const physicalDiscountsCount = computed(() => {
  const list = adminStore.physicalProducts || []
  return list.filter(p => !p.adminOnly && p.categoryId !== 'paginas' && p.hasDiscount && p.discountPercentage > 0).length
})

const physicalFreeShippingCount = computed(() => {
  const list = adminStore.physicalProducts || []
  return list.filter(p => !p.adminOnly && p.categoryId !== 'paginas' && (Number(p.priceNumeric) || 0) >= 40000).length
})

const physicalInStockCount = computed(() => {
  const list = adminStore.physicalProducts || []
  return list.filter(p => !p.adminOnly && p.categoryId !== 'paginas' && (Number(p.stock) || 0) > 0).length
})

const activePhysicalFiltersCount = computed(() => {
  let count = 0
  if (physicalSearchQuery.value) count++
  if (physicalCategoryFilter.value !== 'All') count++
  if (onlyDiscountsFilter.value) count++
  if (freeShippingFilter.value) count++
  if (inStockOnlyFilter.value) count++
  if (physicalPriceRange.value !== 'all') count++
  if (minPriceFilter.value !== '' || maxPriceFilter.value !== '') count++
  if (physicalSortBy.value !== 'default') count++
  return count
})

const scrollToSection = (sectionId) => {
  if (typeof document !== 'undefined') {
    const el = document.getElementById(sectionId)
    if (el) {
      el.scrollIntoView({ behavior: 'smooth', block: 'start' })
    }
  }
}

const selectStoreSection = (sec, targetAnchor = null) => {
  storeSection.value = sec
  const query = { ...route.query }
  if (sec === 'all') {
    delete query.sec
  } else {
    query.sec = sec
  }
  router.replace({ path: '/', query }).catch(() => {})

  const anchor = targetAnchor || (
    sec === 'digital' ? 'suscripciones-digitales' :
    sec === 'packages' ? 'paquetes-combos' :
    sec === 'physical' ? 'catalogo-fisico' : 'hero-banner'
  )

  if (anchor) {
    setTimeout(() => {
      scrollToSection(anchor)
    }, 100)
  }
}

const toggleOnlyDiscounts = () => {
  onlyDiscountsFilter.value = !onlyDiscountsFilter.value
  if (onlyDiscountsFilter.value) {
    if (storeSection.value === 'digital') {
      storeSection.value = 'all'
    }
    toast.add(`🔥 Mostrando productos con ofertas y descuentos activos`, 'info')
  } else {
    toast.add('Mostrando todos los productos del catálogo', 'info')
  }
}

const toggleFreeShipping = () => {
  freeShippingFilter.value = !freeShippingFilter.value
  if (freeShippingFilter.value) {
    if (storeSection.value === 'digital') {
      storeSection.value = 'physical'
    }
    toast.add('🚚 Mostrando solo productos con Envío Gratis (+$40.000)', 'info')
  } else {
    toast.add('Filtro de envío gratis desactivado', 'info')
  }
}

const getWhatsAppContactUrl = (customMsg = '') => {
  const targetPhone = (!isDirectSale.value && currentReseller.value?.contactNumber)
    ? currentReseller.value.contactNumber
    : (adminStore.providerBankInfo?.whatsapp || adminStore.adminAuth?.whatsapp || '+54 9 291 565-9321')
  
  let cleanPhone = targetPhone.replace(/[^0-9]/g, '')
  if (cleanPhone.startsWith('0')) cleanPhone = cleanPhone.substring(1)
  if (cleanPhone.length === 10) cleanPhone = `549${cleanPhone}`
  else if (cleanPhone.length === 11 && cleanPhone.startsWith('15')) cleanPhone = `54911${cleanPhone.substring(2)}`
  else if (cleanPhone.length === 12 && cleanPhone.startsWith('54') && !cleanPhone.startsWith('549')) cleanPhone = `549${cleanPhone.substring(2)}`

  const msg = customMsg || `¡Hola! Me comunico desde la web oficial de StreamePro para consultar por productos, stock y promociones disponibles 🚀`
  return `https://wa.me/${cleanPhone}?text=${encodeURIComponent(msg)}`
}

// ----------------- SECCIÓN 1: SUSCRIPCIONES DIGITALES -----------------
const filteredProducts = computed(() => {
  return products.value.filter(p => {
    const matchCat = selectedCategory.value === 'All' || p.category === selectedCategory.value
    const matchPlatform = digitalPlatformFilter.value === 'All' || 
      p.platform.toLowerCase() === digitalPlatformFilter.value.toLowerCase() ||
      p.name.toLowerCase().includes(digitalPlatformFilter.value.toLowerCase())
    const q = searchQuery.value.toLowerCase().trim()
    const matchSearch = !q || p.name.toLowerCase().includes(q) || p.platform.toLowerCase().includes(q)
    return matchCat && matchPlatform && matchSearch
  })
})

const digitalTotalPages = computed(() => {
  if (digitalItemsPerPage.value === 'all') return 1
  const perPage = Number(digitalItemsPerPage.value) || 8
  return Math.max(1, Math.ceil(filteredProducts.value.length / perPage))
})

const paginatedDigitalProducts = computed(() => {
  if (digitalItemsPerPage.value === 'all') {
    return filteredProducts.value
  }
  const perPage = Number(digitalItemsPerPage.value) || 8
  const start = (digitalCurrentPage.value - 1) * perPage
  return filteredProducts.value.slice(start, start + perPage)
})

const changeDigitalPage = (page) => {
  if (page < 1) page = 1
  if (page > digitalTotalPages.value) page = digitalTotalPages.value
  digitalCurrentPage.value = page
  scrollToSection('suscripciones-digitales')
}

watch([searchQuery, selectedCategory, digitalPlatformFilter, digitalItemsPerPage], () => {
  digitalCurrentPage.value = 1
})

// ----------------- SECCIÓN 2: PAQUETES & COMBOS -----------------
const filteredPackages = computed(() => {
  return adminStore.packages.filter(p => {
    if (!p.active) return false
    const q = searchQuery.value.toLowerCase().trim()
    const matchSearch = !q || p.title.toLowerCase().includes(q) || p.description.toLowerCase().includes(q) || p.services.some(s => s.platform.toLowerCase().includes(q))
    const matchDiscount = !onlyDiscountsFilter.value || (p.originalPrice > p.promoPrice)
    return matchSearch && matchDiscount
  })
})

const packagesTotalPages = computed(() => {
  if (packagesItemsPerPage.value === 'all') return 1
  const perPage = Number(packagesItemsPerPage.value) || 6
  return Math.max(1, Math.ceil(filteredPackages.value.length / perPage))
})

const paginatedPackages = computed(() => {
  if (packagesItemsPerPage.value === 'all') {
    return filteredPackages.value
  }
  const perPage = Number(packagesItemsPerPage.value) || 6
  const start = (packagesCurrentPage.value - 1) * perPage
  return filteredPackages.value.slice(start, start + perPage)
})

const changePackagesPage = (page) => {
  if (page < 1) page = 1
  if (page > packagesTotalPages.value) page = packagesTotalPages.value
  packagesCurrentPage.value = page
  scrollToSection('paquetes-combos')
}

watch([searchQuery, onlyDiscountsFilter, packagesItemsPerPage], () => {
  packagesCurrentPage.value = 1
})

// ----------------- SECCIÓN 3: CATÁLOGO FÍSICO -----------------
const filteredPhysicalProducts = computed(() => {
  const list = adminStore.physicalProducts || []
  const result = list.filter(p => {
    if (!p) return false
    // Excluir contenido o páginas marcadas como visibles solo en admin
    if (p.adminOnly || p.categoryId === 'paginas' || (p.categoryIds && p.categoryIds.includes('paginas'))) {
      return false
    }

    // Filtro Categoría
    if (physicalCategoryFilter.value !== 'All') {
      const matchArray = Array.isArray(p.categoryIds) && p.categoryIds.includes(physicalCategoryFilter.value)
      const matchDirect = p.categoryId === physicalCategoryFilter.value
      if (!matchArray && !matchDirect) return false
    }

    // Filtro Descuento
    if (onlyDiscountsFilter.value && !(p.hasDiscount && p.discountPercentage > 0)) {
      return false
    }

    // Filtro Envío Gratis (+$40k)
    if (freeShippingFilter.value && (Number(p.priceNumeric) || 0) < 40000) {
      return false
    }

    // Filtro Solo en Stock
    if (inStockOnlyFilter.value && (Number(p.stock) || 0) <= 0) {
      return false
    }

    // Filtro Rango de Precios
    const price = Number(p.priceNumeric) || 0
    if (physicalPriceRange.value === 'under_15k' && price > 15000) return false
    if (physicalPriceRange.value === '15k_to_30k' && (price < 15000 || price > 30000)) return false
    if (physicalPriceRange.value === '30k_to_60k' && (price < 30000 || price > 60000)) return false
    if (physicalPriceRange.value === 'over_60k' && price < 60000) return false

    // Filtro Manual Min/Max
    if (minPriceFilter.value !== '' && minPriceFilter.value !== null) {
      const min = Number(minPriceFilter.value)
      if (!isNaN(min) && price < min) return false
    }
    if (maxPriceFilter.value !== '' && maxPriceFilter.value !== null) {
      const max = Number(maxPriceFilter.value)
      if (!isNaN(max) && price > max) return false
    }

    // Filtro de Búsqueda de Texto
    const q = (physicalSearchQuery.value || '').toLowerCase().trim()
    if (q) {
      const name = String(p.name || '').toLowerCase()
      const desc = String(p.description || '').toLowerCase()
      const id = String(p.id || '')
      const sku = String(p.sku || '').toLowerCase()
      if (!name.includes(q) && !desc.includes(q) && !id.includes(q) && !sku.includes(q)) {
        return false
      }
    }

    return true
  })

  // Ordenamiento
  if (physicalSortBy.value === 'price_asc') {
    result.sort((a, b) => (Number(a.priceNumeric) || 0) - (Number(b.priceNumeric) || 0))
  } else if (physicalSortBy.value === 'price_desc') {
    result.sort((a, b) => (Number(b.priceNumeric) || 0) - (Number(a.priceNumeric) || 0))
  } else if (physicalSortBy.value === 'discount_desc') {
    result.sort((a, b) => (Number(b.discountPercentage) || 0) - (Number(a.discountPercentage) || 0))
  } else if (physicalSortBy.value === 'stock_desc') {
    result.sort((a, b) => (Number(b.stock) || 0) - (Number(a.stock) || 0))
  } else if (physicalSortBy.value === 'name_asc') {
    result.sort((a, b) => String(a.name || '').localeCompare(String(b.name || '')))
  }

  return result
})

const physicalTotalPages = computed(() => {
  if (physicalItemsPerPage.value === 'all') return 1
  const perPage = Number(physicalItemsPerPage.value) || 16
  return Math.max(1, Math.ceil(filteredPhysicalProducts.value.length / perPage))
})

const paginatedPhysicalProducts = computed(() => {
  if (physicalItemsPerPage.value === 'all') {
    return filteredPhysicalProducts.value
  }
  const perPage = Number(physicalItemsPerPage.value) || 16
  const start = (physicalCurrentPage.value - 1) * perPage
  return filteredPhysicalProducts.value.slice(start, start + perPage)
})

const changePhysicalPage = (page) => {
  if (page < 1) page = 1
  if (page > physicalTotalPages.value) page = physicalTotalPages.value
  physicalCurrentPage.value = page
  scrollToSection('catalogo-fisico-grid')
}

const visiblePhysicalPages = computed(() => {
  const total = physicalTotalPages.value
  const current = physicalCurrentPage.value
  if (total <= 7) {
    return Array.from({ length: total }, (_, i) => i + 1)
  }
  const pages = []
  if (current <= 4) {
    for (let i = 1; i <= 5; i++) pages.push(i)
    pages.push('...')
    pages.push(total)
  } else if (current >= total - 3) {
    pages.push(1)
    pages.push('...')
    for (let i = total - 4; i <= total; i++) pages.push(i)
  } else {
    pages.push(1)
    pages.push('...')
    pages.push(current - 1)
    pages.push(current)
    pages.push(current + 1)
    pages.push('...')
    pages.push(total)
  }
  return pages
})

const resetPhysicalFilters = () => {
  physicalSearchQuery.value = ''
  physicalCategoryFilter.value = 'All'
  onlyDiscountsFilter.value = false
  freeShippingFilter.value = false
  inStockOnlyFilter.value = false
  physicalPriceRange.value = 'all'
  minPriceFilter.value = ''
  maxPriceFilter.value = ''
  physicalSortBy.value = 'default'
  physicalCurrentPage.value = 1
  toast.add('Filtros del catálogo físico restablecidos', 'info')
}

watch(
  [physicalSearchQuery, physicalCategoryFilter, onlyDiscountsFilter, freeShippingFilter, inStockOnlyFilter, physicalPriceRange, minPriceFilter, maxPriceFilter, physicalSortBy, physicalItemsPerPage],
  () => {
    physicalCurrentPage.value = 1
  }
)

const addPackageToCart = (pkg) => {
  cartStore.addPackageItem(pkg)
  toast.add(`¡Combo "${pkg.title}" agregado al pedido!`, 'success')
  showCartDrawer.value = true
}

const sharePackageLink = (pkg) => {
  const origin = getBaseOrigin()
  const refCode = isDirectSale.value ? '' : clientForm.value.selectedResellerId
  const link = refCode ? `${origin}/?ref=${refCode}&pkg=${pkg.id}` : `${origin}/?pkg=${pkg.id}`
  navigator.clipboard.writeText(link).then(() => {
    toast.add(`¡Enlace copiado! Comparte este combo con tus contactos`, 'success')
  }).catch(() => {
    toast.add('Error al copiar enlace', 'error')
  })
}

const sharePackageWhatsApp = (pkg) => {
  const origin = getBaseOrigin()
  const refCode = isDirectSale.value ? '' : clientForm.value.selectedResellerId
  const link = refCode ? `${origin}/?ref=${refCode}&pkg=${pkg.id}` : `${origin}/?pkg=${pkg.id}`
  const savings = pkg.originalPrice > pkg.promoPrice ? ` (Ahorras $${(pkg.originalPrice - pkg.promoPrice).toLocaleString()} ARS)` : ''
  const platforms = pkg.services.map(s => s.platform).join(' + ')
  const msg = `🎁 *SUPER COMBO STREAMEPRO: ${pkg.title}*\n🎬 Incluye: ${platforms}\n💰 Precio Promo: *$${pkg.promoPrice.toLocaleString()} ARS*${savings}\n\n👉 Aprovecha esta oferta garantizada aquí:\n${link}`
  window.open(`https://wa.me/?text=${encodeURIComponent(msg)}`, '_blank')
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
  <!-- PANTALLA DE MANTENIMIENTO SI LA TIENDA ESTÁ OCULTA (SOLO VISITANTES REGULARES) -->
  <div v-if="!adminStore.isStoreVisible && !isAdminUser" class="min-h-[80vh] flex items-center justify-center py-12 px-4">
    <div class="max-w-2xl w-full bg-slate-900/95 border border-amber-500/30 rounded-3xl p-8 sm:p-12 shadow-2xl text-center space-y-6 relative overflow-hidden backdrop-blur-xl">
      <div class="absolute -top-24 -left-24 w-72 h-72 bg-amber-500/15 rounded-full blur-3xl pointer-events-none"></div>
      <div class="absolute -bottom-24 -right-24 w-72 h-72 bg-sky-500/15 rounded-full blur-3xl pointer-events-none"></div>

      <div class="relative z-10 space-y-5">
        <div class="w-20 h-20 mx-auto rounded-3xl bg-amber-500/20 border border-amber-500/40 flex items-center justify-center text-4xl shadow-inner animate-pulse">
          🛠️
        </div>

        <div class="space-y-2">
          <span class="inline-block px-3.5 py-1 rounded-full bg-amber-500/15 border border-amber-500/30 text-amber-300 text-xs font-black uppercase tracking-wider">
            Mantenimiento & Actualización de Catálogo
          </span>
          <h1 class="text-3xl sm:text-4xl font-black text-white tracking-tight">
            Tienda Temporalmente Cerrada
          </h1>
          <p class="text-slate-300 text-sm sm:text-base leading-relaxed max-w-lg mx-auto">
            Estamos actualizando nuestro catálogo oficial, sincronizando nuevo stock y configurando promociones exclusivas para brindarte la mejor experiencia.
          </p>
        </div>

        <div class="p-4 bg-slate-950/70 border border-slate-800 rounded-2xl text-xs text-slate-400 space-y-1.5 max-w-md mx-auto text-left sm:text-center">
          <div class="flex items-center sm:justify-center gap-2 text-emerald-400 font-bold">
            <span>✓</span>
            <span>Atención y entregas activas por WhatsApp</span>
          </div>
          <div>Si tienes un pedido pendiente o deseas consultar por servicios disponibles, comunícate directo con nuestro equipo oficial.</div>
        </div>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
          <a 
            :href="getWhatsAppContactUrl('¡Hola! Me comunico porque la web se encuentra en pausa temporal y quisiera consultar por suscripciones y productos.')"
            target="_blank"
            rel="noopener noreferrer"
            class="w-full sm:w-auto px-6 py-3.5 rounded-2xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-slate-950 font-black text-sm flex items-center justify-center gap-2 shadow-lg shadow-emerald-500/20 transition-all cursor-pointer"
          >
            <span>💬</span>
            <span>Consultar por WhatsApp</span>
          </a>

          <router-link
            to="/vault"
            class="w-full sm:w-auto px-6 py-3.5 rounded-2xl bg-slate-800/90 hover:bg-slate-700 text-sky-300 hover:text-white border border-sky-500/30 font-bold text-sm flex items-center justify-center gap-2 transition-all"
          >
            <span>🔐</span>
            <span>Mi Bóveda de Cliente</span>
          </router-link>

          <router-link
            to="/admin"
            class="w-full sm:w-auto px-4 py-3.5 rounded-2xl bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 text-xs font-semibold flex items-center justify-center gap-1.5 transition-all"
          >
            <span>⚙️</span>
            <span>Acceso Admin</span>
          </router-link>
        </div>
      </div>
    </div>
  </div>

  <!-- TIENDA PÚBLICA NORMAL (O VISTA PREVIA PARA ADMINISTRADOR) -->
  <div v-else class="space-y-8">
    <!-- BANNER DE ADVERTENCIA PARA ADMINISTRADOR SI LA TIENDA ESTÁ OCULTA -->
    <div v-if="!adminStore.isStoreVisible && isAdminUser" class="bg-gradient-to-r from-amber-950/90 via-red-950/90 to-amber-950/90 border-2 border-amber-500/70 p-4 sm:p-5 rounded-2xl shadow-2xl flex flex-col sm:flex-row items-center justify-between gap-4">
      <div class="flex items-center gap-3.5">
        <div class="w-10 h-10 rounded-xl bg-amber-500/20 text-amber-300 flex items-center justify-center text-xl shrink-0 border border-amber-500/40">
          ⚠️
        </div>
        <div>
          <h4 class="text-white font-black text-sm sm:text-base flex items-center gap-2">
            <span>MODO ADMINISTRADOR: Tienda Oculta al Público</span>
            <span class="text-[10px] bg-red-500 text-white font-mono px-2 py-0.5 rounded-full uppercase font-bold">Pausada</span>
          </h4>
          <p class="text-xs text-amber-200/80">Los visitantes normales ven la pantalla de mantenimiento. Tú estás navegando en modo edición/previa.</p>
        </div>
      </div>
      <div class="flex items-center gap-2 shrink-0 w-full sm:w-auto justify-end">
        <button 
          type="button"
          @click="adminStore.setStoreVisibility(true); toast.add('¡Tienda visible al público!', 'success')"
          class="w-full sm:w-auto bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-slate-950 font-black text-xs px-5 py-2.5 rounded-xl transition shadow flex items-center justify-center gap-1.5 cursor-pointer"
        >
          <span>🟢</span>
          <span>Hacer Tienda Visible Ahora</span>
        </button>
      </div>
    </div>
    <!-- Hero Banner Promocional Enterprise -->
    <div id="hero-banner" class="glass-panel rounded-3xl p-6 sm:p-10 relative overflow-hidden border border-white/[0.08] shadow-2xl">
      <div class="absolute right-[-10%] top-[-20%] w-96 h-96 bg-gradient-to-br from-sky-500/25 via-blue-600/15 to-transparent rounded-full blur-3xl pointer-events-none"></div>
      <div class="absolute left-[-10%] bottom-[-20%] w-80 h-80 bg-gradient-to-tr from-amber-500/15 via-orange-600/10 to-transparent rounded-full blur-3xl pointer-events-none"></div>
      
      <div class="max-w-4xl relative z-10">
        <div class="inline-flex flex-wrap items-center gap-2 px-3.5 py-1.5 rounded-full bg-sky-500/10 border border-sky-500/30 text-sky-300 text-xs font-bold uppercase tracking-wider mb-4 shadow-sm">
          <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
          <span>Entrega Oficial en 10 min a 1 hora</span>
          <span class="text-slate-500">•</span>
          <span class="text-amber-300">145+ Productos Físicos</span>
          <span class="text-slate-500">•</span>
          <span class="text-emerald-300">Garantía SLA 30 Días</span>
        </div>

        <h2 class="text-3xl sm:text-5xl lg:text-6xl font-black mb-3 text-white tracking-tight leading-tight">
          Streaming Premium 4K <br class="hidden sm:inline">
          <span class="text-transparent bg-clip-text bg-gradient-to-r from-sky-400 via-indigo-300 to-amber-300">
            & Catálogo Oficial de Tecnología
          </span>
        </h2>
        
        <p class="text-slate-300 text-sm sm:text-base leading-relaxed max-w-2xl font-normal">
          Acceso garantizado a tus plataformas favoritas con PIN exclusivo y entrega inmediata + Más de 145 productos de tecnología, electrónica, bazar y herramientas con envíos a todo el país y confirmación por WhatsApp antes de transferir.
        </p>

        <!-- Trust Metrics Grid -->
        <div class="mt-6 flex flex-wrap items-center gap-2.5 text-xs font-semibold">
          <div class="flex items-center gap-2 bg-slate-900/90 px-3.5 py-2 rounded-xl border border-white/[0.08] shadow-sm">
            <span class="text-emerald-400 text-sm">⏱️</span>
            <span class="text-slate-200">Entrega Digital: 10 min a 1h</span>
          </div>
          <div class="flex items-center gap-2 bg-slate-900/90 px-3.5 py-2 rounded-xl border border-white/[0.08] shadow-sm">
            <span class="text-amber-400 text-sm">🚚</span>
            <span class="text-slate-200">Envíos a Todo el País</span>
          </div>
          <div class="flex items-center gap-2 bg-slate-900/90 px-3.5 py-2 rounded-xl border border-white/[0.08] shadow-sm">
            <span class="text-sky-400 text-sm">🔒</span>
            <span class="text-slate-200">Garantía SLA 30 Días</span>
          </div>
          <div class="flex items-center gap-2 bg-slate-900/90 px-3.5 py-2 rounded-xl border border-white/[0.08] shadow-sm">
            <span class="text-pink-400 text-sm">🎁</span>
            <span class="text-slate-200">Combos con hasta 35% OFF</span>
          </div>
        </div>

        <!-- Botones de Acción Rápida Dinámicos y Reestructurados -->
        <div class="mt-6 flex flex-wrap items-center gap-2 sm:gap-2.5">
          <button 
            type="button"
            @click="selectStoreSection('all')"
            :class="[
              'px-4 py-2.5 rounded-xl font-black text-xs transition-all flex items-center gap-2 cursor-pointer shadow-md',
              storeSection === 'all' && !onlyDiscountsFilter
                ? 'bg-gradient-to-r from-sky-400 via-indigo-500 to-amber-400 text-slate-950 font-black shadow-sky-500/25 scale-[1.02]' 
                : 'bg-slate-900/90 text-slate-300 hover:text-white border border-white/10 hover:bg-slate-800'
            ]"
          >
            <span>🌟</span>
            <span>Explorar Todo</span>
          </button>

          <button 
            type="button"
            @click="selectStoreSection('digital', 'suscripciones-digitales')"
            :class="[
              'px-4 py-2.5 rounded-xl font-black text-xs transition-all flex items-center gap-2 cursor-pointer shadow-md',
              storeSection === 'digital' 
                ? 'bg-gradient-to-r from-sky-400 to-blue-500 text-slate-950 shadow-sky-500/25 scale-[1.02]' 
                : 'bg-slate-900/90 text-slate-300 hover:text-white border border-white/10 hover:bg-slate-800'
            ]"
          >
            <span>🎬</span>
            <span>Suscripciones (8)</span>
          </button>

          <button 
            type="button"
            @click="selectStoreSection('physical', 'catalogo-fisico')"
            :class="[
              'px-4 py-2.5 rounded-xl font-black text-xs transition-all flex items-center gap-2 cursor-pointer shadow-md',
              storeSection === 'physical' 
                ? 'bg-gradient-to-r from-amber-400 to-orange-500 text-slate-950 shadow-amber-500/25 scale-[1.02]' 
                : 'bg-slate-900/90 text-amber-300 hover:text-white border border-amber-500/30 hover:bg-slate-800'
            ]"
          >
            <span>📦</span>
            <span>Catálogo Físico ({{ adminStore.physicalProducts.length }})</span>
          </button>

          <button 
            type="button"
            @click="selectStoreSection('packages', 'paquetes-combos')"
            :class="[
              'px-4 py-2.5 rounded-xl font-black text-xs transition-all flex items-center gap-2 cursor-pointer shadow-md',
              storeSection === 'packages' 
                ? 'bg-gradient-to-r from-purple-500 via-pink-500 to-rose-500 text-white shadow-pink-500/25 scale-[1.02]' 
                : 'bg-slate-900/90 text-pink-300 hover:text-white border border-pink-500/30 hover:bg-slate-800'
            ]"
          >
            <span>🎁</span>
            <span>Combos & Promos</span>
          </button>

          <!-- Botón Dinámico: Filtrar Solo Ofertas con Descuento -->
          <button 
            type="button"
            @click="toggleOnlyDiscounts"
            :class="[
              'px-4 py-2.5 rounded-xl font-black text-xs transition-all flex items-center gap-2 cursor-pointer shadow-md',
              onlyDiscountsFilter
                ? 'bg-gradient-to-r from-red-500 to-amber-500 text-white shadow-red-500/30 ring-2 ring-amber-400' 
                : 'bg-red-950/60 text-red-300 hover:text-white border border-red-500/40 hover:bg-red-900/50'
            ]"
          >
            <span>🔥</span>
            <span>{{ onlyDiscountsFilter ? 'Mostrando Ofertas' : 'Solo Ofertas % OFF' }}</span>
            <span v-if="physicalDiscountsCount > 0" class="text-[10px] px-1.5 py-0.2 rounded-full font-mono bg-black/40 text-amber-300 border border-amber-400/40">
              {{ physicalDiscountsCount }}
            </span>
          </button>

          <!-- Botón Asesoría Directa por WhatsApp -->
          <a 
            :href="getWhatsAppContactUrl()"
            target="_blank"
            rel="noopener noreferrer"
            class="px-4 py-2.5 rounded-xl font-bold text-xs bg-emerald-600/20 hover:bg-emerald-600 text-emerald-300 hover:text-white border border-emerald-500/30 transition-all flex items-center gap-1.5 shadow-sm"
          >
            <span>💬</span>
            <span>Asesoría WhatsApp</span>
          </a>
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
          type="button"
          @click="selectStoreSection('all')"
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
          type="button"
          @click="selectStoreSection('digital', 'suscripciones-digitales')"
          :class="[
            'px-5 py-3 rounded-xl font-black text-xs sm:text-sm transition-all flex items-center gap-2 cursor-pointer',
            storeSection === 'digital' 
              ? 'bg-gradient-to-r from-sky-400 to-blue-500 text-slate-950 shadow-lg shadow-sky-500/20' 
              : 'text-slate-400 hover:text-white hover:bg-white/[0.04]'
          ]"
        >
          <span>🎬</span>
          <span>Suscripciones Digitales</span>
          <span class="bg-slate-950/80 text-sky-300 text-[10px] px-2 py-0.5 rounded-full font-mono border border-sky-400/30">8</span>
        </button>

        <button 
          type="button"
          @click="selectStoreSection('packages', 'paquetes-combos')"
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
          type="button"
          @click="selectStoreSection('physical', 'catalogo-fisico')"
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
      <!-- Header de Sección Oficial con Filtros por Plataforma -->
      <div class="bg-gradient-to-r from-sky-950/50 via-slate-900 to-slate-950 p-4 sm:p-5 rounded-2xl border border-sky-500/30 shadow-xl space-y-3">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
          <div class="flex items-center gap-3">
            <span class="text-3xl">🎬</span>
            <div>
              <h3 class="text-lg sm:text-xl font-black text-white">Suscripciones Digitales & Pantallas 4K</h3>
              <p class="text-xs text-slate-300">Netflix, Max, Spotify, Disney+, IPTV, Prime Video, YouTube Premium y Canva Pro con PIN exclusivo y entrega de 10 min a 1 hora.</p>
            </div>
          </div>
          <span class="text-xs font-mono font-bold bg-sky-500/20 text-sky-300 border border-sky-500/30 px-3 py-1.5 rounded-xl shrink-0 flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            8 Plataformas Oficiales
          </span>
        </div>

        <!-- Chips Interactivos de Plataformas Digitales -->
        <div class="pt-2 border-t border-white/[0.08] flex items-center gap-1.5 overflow-x-auto no-scrollbar pb-1 text-xs font-bold">
          <span class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider shrink-0 mr-1">Filtrar:</span>
          <button 
            type="button"
            @click="digitalPlatformFilter = 'All'"
            :class="[
              'px-3 py-1.5 rounded-xl transition-all shrink-0 cursor-pointer text-xs',
              digitalPlatformFilter === 'All' 
                ? 'bg-sky-400 text-slate-950 font-black shadow-md shadow-sky-400/20' 
                : 'bg-slate-900 text-slate-300 hover:text-white border border-white/10 hover:bg-slate-800'
            ]"
          >
            🌟 Todas (8)
          </button>
          <button 
            v-for="plat in ['Netflix', 'Spotify', 'Max', 'Disney+', 'IPTV', 'Prime Video', 'YouTube Premium', 'Canva Pro']" 
            :key="plat"
            type="button"
            @click="digitalPlatformFilter = digitalPlatformFilter === plat ? 'All' : plat"
            :class="[
              'px-3 py-1.5 rounded-xl transition-all shrink-0 cursor-pointer text-xs flex items-center gap-1.5',
              digitalPlatformFilter === plat 
                ? 'bg-sky-500 text-slate-950 font-black shadow-md shadow-sky-500/25' 
                : 'bg-slate-900 text-slate-300 hover:text-white border border-white/10 hover:bg-slate-800'
            ]"
          >
            <span>{{ plat === 'Netflix' ? '🍿' : plat === 'Spotify' ? '🎧' : plat === 'Max' ? '🎬' : plat === 'Disney+' ? '✨' : plat === 'IPTV' ? '📺' : plat === 'Prime Video' ? '📦' : plat === 'YouTube Premium' ? '▶️' : '🎨' }}</span>
            <span>{{ plat }}</span>
          </button>
        </div>
      </div>

      <!-- Banner Destacado para Descubrir Catálogo Físico (Visible si se filtra sólo digital) -->
      <div v-if="storeSection === 'digital'" class="bg-gradient-to-r from-amber-950/50 via-slate-900 to-orange-950/40 p-4 sm:p-5 rounded-2xl border border-amber-500/30 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-xl">
        <div class="flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-2xl bg-amber-500/20 text-amber-300 flex items-center justify-center text-2xl shrink-0 border border-amber-500/30">
            📦
          </div>
          <div>
            <strong class="text-white text-sm sm:text-base block font-black">¿Sabías que también tenemos Catálogo de Productos Físicos?</strong>
            <p class="text-xs text-slate-300 mt-0.5">Tecnología, electrónica, herramientas, bazar y hogar con envíos a todo el país y stock garantizado.</p>
          </div>
        </div>
        <div class="flex items-center gap-2 shrink-0 w-full sm:w-auto justify-end">
          <button 
            type="button"
            @click="selectStoreSection('physical', 'catalogo-fisico')"
            class="bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-xs px-4 py-2.5 rounded-xl transition shadow flex items-center gap-1.5 cursor-pointer"
          >
            <span>Ver Productos Físicos ({{ adminStore.physicalProducts.length }}) →</span>
          </button>
        </div>
      </div>

      <!-- Barra de Búsqueda y Botón de Carrito -->
      <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
        <div class="flex gap-4 p-2 bg-slate-900/70 backdrop-blur-xl border border-white/[0.08] rounded-2xl flex-1 w-full shadow-lg">
          <div class="relative flex-1">
            <svg class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            <input v-model="searchQuery" type="text" placeholder="Buscar por Netflix, Max, Spotify, Disney, IPTV, YouTube, Canva..." class="w-full bg-transparent border-none p-3 pl-10 text-white focus:outline-none focus:ring-0 text-sm placeholder:text-slate-500">
            <button v-if="searchQuery" @click="searchQuery = ''" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white text-xs bg-slate-800 px-2 py-0.5 rounded cursor-pointer">✕ Limpiar</button>
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
          class="relative bg-gradient-to-r from-sky-400 via-blue-500 to-indigo-600 hover:from-sky-300 hover:to-indigo-500 text-slate-950 font-black px-7 py-3.5 rounded-2xl transition-all shadow-[0_0_20px_rgba(56,189,248,0.3)] hover:shadow-[0_0_25px_rgba(56,189,248,0.5)] flex items-center gap-3 w-full sm:w-auto justify-center transform active:scale-95 cursor-pointer"
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
        @click="selectStoreSection('packages', 'paquetes-combos')" 
        class="cursor-pointer bg-gradient-to-r from-purple-950/60 via-pink-950/40 to-slate-900 p-4 sm:p-5 rounded-2xl border border-pink-500/30 hover:border-pink-500/60 transition-all shadow-xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 group"
      >
        <div class="flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-2xl bg-pink-500/20 text-pink-300 flex items-center justify-center text-2xl shrink-0 group-hover:scale-110 transition-transform">
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
        <div v-for="product in paginatedDigitalProducts" :key="product.id" class="glass-panel glass-panel-hover rounded-3xl p-6 border border-white/[0.08] flex flex-col justify-between relative overflow-hidden group">
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

      <!-- Paginación de Suscripciones Digitales -->
      <div v-if="filteredProducts.length > 0" class="pt-4 border-t border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
        <div class="flex items-center gap-2 text-slate-400">
          <span>Mostrando</span>
          <span class="font-bold text-white font-mono">{{ ((digitalCurrentPage - 1) * (digitalItemsPerPage === 'all' ? filteredProducts.length : digitalItemsPerPage)) + 1 }} - {{ Math.min(digitalCurrentPage * (digitalItemsPerPage === 'all' ? filteredProducts.length : digitalItemsPerPage), filteredProducts.length) }}</span>
          <span>de</span>
          <span class="font-bold text-sky-400 font-mono">{{ filteredProducts.length }}</span>
          <span>plataformas</span>
        </div>

        <div class="flex items-center gap-3">
          <div class="flex items-center gap-1.5 text-slate-400">
            <span class="text-[11px]">Por pág:</span>
            <select v-model="digitalItemsPerPage" class="bg-slate-900 border border-slate-700 rounded-lg px-2 py-1 text-white text-xs font-mono focus:outline-none cursor-pointer">
              <option :value="4">4</option>
              <option :value="6">6</option>
              <option :value="8">8</option>
              <option value="all">Todas</option>
            </select>
          </div>

          <div v-if="digitalTotalPages > 1" class="flex items-center gap-1">
            <button 
              type="button"
              @click="changeDigitalPage(digitalCurrentPage - 1)" 
              :disabled="digitalCurrentPage <= 1"
              class="px-2.5 py-1 rounded-lg bg-slate-900 hover:bg-slate-800 disabled:opacity-30 disabled:cursor-not-allowed border border-slate-700 text-slate-200 font-bold cursor-pointer transition-colors"
            >
              ‹
            </button>
            <button 
              v-for="p in digitalTotalPages" 
              :key="p"
              type="button"
              @click="changeDigitalPage(p)"
              :class="[
                'px-3 py-1 rounded-lg font-bold text-xs font-mono transition-all cursor-pointer',
                digitalCurrentPage === p 
                  ? 'bg-sky-400 text-slate-950 shadow-md font-black' 
                  : 'bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800'
              ]"
            >
              {{ p }}
            </button>
            <button 
              type="button"
              @click="changeDigitalPage(digitalCurrentPage + 1)" 
              :disabled="digitalCurrentPage >= digitalTotalPages"
              class="px-2.5 py-1 rounded-lg bg-slate-900 hover:bg-slate-800 disabled:opacity-30 disabled:cursor-not-allowed border border-slate-700 text-slate-200 font-bold cursor-pointer transition-colors"
            >
              ›
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
      <div class="bg-gradient-to-r from-purple-950/60 via-pink-950/40 to-slate-900 p-6 sm:p-7 rounded-3xl border border-pink-500/30 shadow-2xl flex flex-col md:flex-row justify-between items-start md:items-center gap-5">
        <div class="flex items-center gap-4">
          <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-purple-500 to-pink-500 text-white flex items-center justify-center text-3xl shrink-0 shadow-lg shadow-pink-500/25">
            🎁
          </div>
          <div>
            <div class="flex items-center gap-2">
              <span class="text-xs font-black uppercase tracking-wider bg-pink-500/20 text-pink-300 px-2.5 py-0.5 rounded border border-pink-500/30">
                Ahorro Inteligente hasta 35% OFF
              </span>
              <span class="text-xs text-slate-300 font-mono">Entrega Oficial con PIN</span>
            </div>
            <h3 class="text-xl sm:text-2xl font-black text-white mt-1">Paquetes Combinados & Ofertas Exclusivas</h3>
            <p class="text-xs text-slate-300 max-w-xl">Contrata múltiples servicios en un solo pago con tarifas promocionales garantizadas y soporte directo.</p>
          </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 shrink-0 w-full md:w-auto justify-end">
          <a 
            :href="getWhatsAppContactUrl('¡Hola! Me gustaría armar un combo personalizado a mi medida.')"
            target="_blank"
            rel="noopener noreferrer"
            class="px-4 py-2.5 rounded-xl font-bold text-xs bg-emerald-600/20 hover:bg-emerald-600 text-emerald-300 hover:text-white border border-emerald-500/30 transition-all flex items-center gap-1.5 shadow-sm"
          >
            <span>💬</span>
            <span>Combo a Medida por WhatsApp</span>
          </a>
          <button 
            type="button"
            @click="selectStoreSection('physical', 'catalogo-fisico')"
            class="px-4 py-2.5 rounded-xl font-bold text-xs bg-slate-900/90 hover:bg-slate-800 text-amber-300 hover:text-white border border-amber-500/30 transition-all flex items-center gap-1.5 cursor-pointer"
          >
            <span>📦</span>
            <span>Ver Físicos ({{ adminStore.physicalProducts.length }}) →</span>
          </button>
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
          v-for="pkg in paginatedPackages" 
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
                      {{ srv.platform === 'Netflix' ? '🍿' : srv.platform === 'Spotify' ? '🎧' : srv.platform === 'Max' ? '🎬' : srv.platform === 'Disney+' ? '✨' : srv.platform === 'IPTV' ? '📺' : '🎬' }}
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
              class="w-full bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-400 hover:to-pink-400 text-white font-black py-3 px-4 rounded-xl transition-all shadow-lg shadow-pink-500/20 flex items-center justify-center gap-2 cursor-pointer active:scale-95"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
              <span>+ Agregar Combo al Pedido</span>
            </button>

            <!-- Acciones Secundarias: Compartir, WhatsApp y Flyer -->
            <div class="flex gap-1.5">
              <button 
                @click="sharePackageLink(pkg)"
                class="flex-1 bg-slate-900/80 hover:bg-slate-800 text-slate-300 hover:text-white text-xs font-bold py-2 px-2.5 rounded-xl transition-all border border-slate-800 flex items-center justify-center gap-1 cursor-pointer truncate"
                title="Copiar enlace del combo"
              >
                <span>🤝</span>
                <span class="truncate">COMPARTIR</span>
              </button>
              <button 
                @click="sharePackageWhatsApp(pkg)"
                class="bg-emerald-600/20 hover:bg-emerald-600 text-emerald-400 hover:text-white text-xs font-bold px-3 py-2 rounded-xl border border-emerald-500/30 transition-colors shrink-0 cursor-pointer"
                title="Compartir combo por WhatsApp"
              >
                💬
              </button>
              <button 
                @click="openFlyerForPackage(pkg)"
                class="bg-pink-500/20 hover:bg-pink-500/30 text-pink-300 hover:text-white text-xs font-bold px-3 py-2 rounded-xl border border-pink-500/30 transition-colors shrink-0 cursor-pointer"
                title="Crear flyer publicitario de este combo"
              >
                🎨
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Paginación de Combos y Paquetes -->
      <div v-if="filteredPackages.length > 0" class="pt-4 border-t border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
        <div class="flex items-center gap-2 text-slate-400">
          <span>Mostrando</span>
          <span class="font-bold text-white font-mono">{{ ((packagesCurrentPage - 1) * (packagesItemsPerPage === 'all' ? filteredPackages.length : packagesItemsPerPage)) + 1 }} - {{ Math.min(packagesCurrentPage * (packagesItemsPerPage === 'all' ? filteredPackages.length : packagesItemsPerPage), filteredPackages.length) }}</span>
          <span>de</span>
          <span class="font-bold text-pink-400 font-mono">{{ filteredPackages.length }}</span>
          <span>combos</span>
        </div>

        <div class="flex items-center gap-3">
          <div class="flex items-center gap-1.5 text-slate-400">
            <span class="text-[11px]">Por pág:</span>
            <select v-model="packagesItemsPerPage" class="bg-slate-900 border border-slate-700 rounded-lg px-2 py-1 text-white text-xs font-mono focus:outline-none cursor-pointer">
              <option :value="4">4</option>
              <option :value="6">6</option>
              <option :value="8">8</option>
              <option value="all">Todos</option>
            </select>
          </div>

          <div v-if="packagesTotalPages > 1" class="flex items-center gap-1">
            <button 
              type="button"
              @click="changePackagesPage(packagesCurrentPage - 1)" 
              :disabled="packagesCurrentPage <= 1"
              class="px-2.5 py-1 rounded-lg bg-slate-900 hover:bg-slate-800 disabled:opacity-30 disabled:cursor-not-allowed border border-slate-700 text-slate-200 font-bold cursor-pointer transition-colors"
            >
              ‹
            </button>
            <button 
              v-for="p in packagesTotalPages" 
              :key="p"
              type="button"
              @click="changePackagesPage(p)"
              :class="[
                'px-3 py-1 rounded-lg font-bold text-xs font-mono transition-all cursor-pointer',
                packagesCurrentPage === p 
                  ? 'bg-pink-500 text-white shadow-md font-black' 
                  : 'bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800'
              ]"
            >
              {{ p }}
            </button>
            <button 
              type="button"
              @click="changePackagesPage(packagesCurrentPage + 1)" 
              :disabled="packagesCurrentPage >= packagesTotalPages"
              class="px-2.5 py-1 rounded-lg bg-slate-900 hover:bg-slate-800 disabled:opacity-30 disabled:cursor-not-allowed border border-slate-700 text-slate-200 font-bold cursor-pointer transition-colors"
            >
              ›
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- SECCIÓN 3: CATÁLOGO DE PRODUCTOS FÍSICOS (C:\Users\yo\catalogo) -->
    <div v-if="storeSection === 'all' || storeSection === 'physical'" id="catalogo-fisico" class="space-y-6">
      <!-- Header de Sección Oficial -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 bg-gradient-to-r from-amber-950/50 via-slate-900 to-slate-950 p-4 sm:p-5 rounded-2xl border border-amber-500/30 shadow-xl">
        <div class="flex items-center gap-3.5">
          <span class="text-3xl">📦</span>
          <div>
            <h3 class="text-lg sm:text-xl font-black text-white">Tienda & Catálogo de Productos Físicos</h3>
            <p class="text-xs text-slate-300">Electrónica, tecnología, accesorios, hogar, bazar y herramientas con envíos a todo el país y confirmación por WhatsApp.</p>
          </div>
        </div>
        <div class="flex items-center gap-2 shrink-0">
          <span class="text-xs font-mono font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30 px-3 py-1.5 rounded-xl flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            {{ adminStore.physicalProducts.length }} Productos Sincronizados
          </span>
        </div>
      </div>

      <!-- Banner Destacado para Volver a Streaming Digital (Visible si se filtra sólo físico) -->
      <div v-if="storeSection === 'physical'" class="bg-gradient-to-r from-sky-950/50 via-slate-900 to-indigo-950/40 p-4 sm:p-5 rounded-2xl border border-sky-500/30 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-xl">
        <div class="flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-2xl bg-sky-500/20 text-sky-400 flex items-center justify-center text-2xl shrink-0 border border-sky-500/30">
            🎬
          </div>
          <div>
            <strong class="text-white text-sm sm:text-base block font-black">¿Buscando Cuentas y Pantallas de Streaming 4K?</strong>
            <p class="text-xs text-slate-300 mt-0.5">Netflix, Max, Spotify, Disney+, YouTube Premium e IPTV con PIN exclusivo y entrega de 10 min a 1 hora.</p>
          </div>
        </div>
        <button 
          type="button"
          @click="selectStoreSection('digital', 'suscripciones-digitales')"
          class="bg-brand-accent hover:bg-sky-400 text-slate-950 font-black text-xs px-4 py-2.5 rounded-xl transition shadow shrink-0 flex items-center gap-1.5 cursor-pointer"
        >
          <span>Ver Suscripciones Digitales →</span>
        </button>
      </div>

      <!-- Filtros Principales del Catálogo Físico -->
      <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
        <div class="flex gap-4 p-2 bg-slate-900/70 backdrop-blur-xl border border-white/[0.08] rounded-2xl flex-1 w-full shadow-lg">
          <div class="relative flex-1">
            <svg class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            <input v-model="physicalSearchQuery" type="text" placeholder="Buscar productos físicos (herramientas, hogar, electrónica, bazar)..." class="w-full bg-transparent border-none p-3 pl-10 text-white focus:outline-none focus:ring-0 text-sm placeholder:text-slate-500">
            <button v-if="physicalSearchQuery" @click="physicalSearchQuery = ''" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white text-xs bg-slate-800 px-2 py-0.5 rounded cursor-pointer">✕ Limpiar</button>
          </div>
          <div class="w-px bg-white/10 my-2"></div>
          <select v-model="physicalCategoryFilter" class="bg-transparent border-none p-3 text-white focus:outline-none cursor-pointer text-sm font-semibold pr-4">
            <option value="All" class="bg-slate-900">Todas las categorías ({{ adminStore.physicalProducts.filter(p => !p.adminOnly && p.categoryId !== 'paginas').length }})</option>
            <option v-for="cat in adminStore.physicalCategories.filter(c => !c.adminOnly && c.id !== 'paginas')" :key="cat.id" :value="cat.id" class="bg-slate-900">
              {{ getCategoryIcon(cat.id, cat.name) }} {{ cat.name }} ({{ cat.cantidad }})
            </option>
          </select>
        </div>

        <button 
          @click="showCartDrawer = true" 
          class="relative bg-gradient-to-r from-amber-400 via-orange-500 to-red-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 font-black px-7 py-3.5 rounded-2xl transition-all shadow-[0_0_20px_rgba(251,191,36,0.3)] flex items-center gap-3 w-full sm:w-auto justify-center cursor-pointer transform active:scale-95 shrink-0"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
          <span>Mi Pedido</span>
          <span v-if="cartStore.items.length > 0" class="bg-slate-950 text-white text-xs px-2.5 py-0.5 rounded-full font-bold border border-white/20">
            {{ cartStore.items.length }}
          </span>
        </button>
      </div>

      <!-- Banner Cyber Showcase Interactivo del Catálogo Físico -->
      <div class="w-full rounded-3xl overflow-hidden border border-amber-500/30 shadow-2xl relative bg-gradient-to-br from-slate-950 via-slate-900 to-amber-950/40 p-6 sm:p-8 group">
        <!-- Ambient glows -->
        <div class="absolute right-[-5%] top-[-20%] w-72 h-72 bg-amber-500/15 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute left-[-5%] bottom-[-20%] w-72 h-72 bg-orange-600/10 rounded-full blur-3xl pointer-events-none"></div>
        
        <div class="relative z-10 space-y-4">
          <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="inline-flex items-center gap-2 bg-amber-500/15 border border-amber-500/30 text-amber-300 text-xs font-bold uppercase tracking-wider px-3 py-1 rounded-full">
              <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
              <span>📦 TIENDA OFICIAL STREAMEPRO • DESPACHOS A TODO EL PAÍS</span>
            </div>
            <div class="text-xs font-mono text-amber-400 bg-slate-950/90 px-3 py-1 rounded-xl border border-amber-500/30 font-bold">
              {{ adminStore.physicalProducts.filter(p => !p.adminOnly && p.categoryId !== 'paginas').length }} Productos Sincronizados
            </div>
          </div>

          <div class="max-w-3xl">
            <h3 class="text-2xl sm:text-4xl font-black text-white tracking-tight leading-tight">
              Tecnología, Herramientas, Electrónica & Bazar
            </h3>
            <p class="text-slate-300 text-xs sm:text-sm mt-2 leading-relaxed">
              Catálogo oficial con asesoramiento directo y personalizado. Precios transparentes en pesos argentinos. No realices transferencias previas: confirmamos stock y despacho contigo por WhatsApp.
            </p>
          </div>

          <!-- Trust Indicators -->
          <div class="flex flex-wrap items-center gap-2 pt-1 text-xs">
            <div class="flex items-center gap-1.5 bg-slate-900/90 px-3 py-1.5 rounded-xl border border-white/[0.08] text-slate-200">
              <span class="text-amber-400">🚚</span> Envíos a todo el país
            </div>
            <div class="flex items-center gap-1.5 bg-slate-900/90 px-3 py-1.5 rounded-xl border border-white/[0.08] text-slate-200">
              <span class="text-emerald-400">🛡️</span> Garantía de Compra
            </div>
            <div class="flex items-center gap-1.5 bg-slate-900/90 px-3 py-1.5 rounded-xl border border-white/[0.08] text-slate-200">
              <span class="text-sky-400">💬</span> Asesoría WhatsApp Directa
            </div>
            <div class="flex items-center gap-1.5 bg-slate-900/90 px-3 py-1.5 rounded-xl border border-white/[0.08] text-slate-200">
              <span class="text-rose-400">🔥</span> Descuentos Marcados
            </div>
          </div>
        </div>
      </div>

      <!-- SELECCIÓN AVANZADA DE CATEGORÍAS (RIBBON CAROUSEL VISUAL) -->
      <div class="glass-panel rounded-3xl p-4 sm:p-5 border border-amber-500/25 bg-slate-950/70 shadow-xl space-y-3">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 border-b border-white/[0.08] pb-3">
          <div class="flex items-center gap-2">
            <span class="text-lg">🏷️</span>
            <div>
              <h4 class="text-white font-black text-sm uppercase tracking-wider flex items-center gap-2">
                <span>Categorías del Catálogo</span>
                <span class="text-[10px] font-mono bg-amber-500/20 text-amber-300 px-2 py-0.5 rounded-full border border-amber-500/30">
                  {{ adminStore.physicalCategories.filter(c => !c.adminOnly && c.id !== 'paginas' && parseInt(c.cantidad || '0') > 0).length }} Rubros
                </span>
              </h4>
              <p class="text-[11px] text-slate-400">Selecciona un rubro para filtrar productos al instante:</p>
            </div>
          </div>

          <div v-if="physicalCategoryFilter !== 'All'" class="flex items-center gap-2">
            <span class="text-xs text-amber-300 font-bold bg-amber-500/10 border border-amber-500/30 px-2.5 py-1 rounded-xl flex items-center gap-1.5">
              <span>Filtro activo:</span>
              <strong class="text-white">{{ adminStore.physicalCategories.find(c => c.id === physicalCategoryFilter)?.name || physicalCategoryFilter }}</strong>
            </span>
            <button 
              type="button"
              @click="physicalCategoryFilter = 'All'" 
              class="text-xs text-slate-400 hover:text-white bg-slate-800 hover:bg-slate-700 px-2.5 py-1 rounded-xl font-bold cursor-pointer transition-colors"
            >
              ✕ Quitar
            </button>
          </div>
        </div>

        <!-- Carousel / Ribbon Scrollable de Categorías -->
        <div class="flex items-center gap-2 overflow-x-auto no-scrollbar py-1">
          <!-- Todas las categorías -->
          <button 
            type="button"
            @click="physicalCategoryFilter = 'All'"
            :class="[
              'px-4 py-2.5 rounded-2xl transition-all shrink-0 cursor-pointer text-xs font-bold flex items-center gap-2 shadow-sm',
              physicalCategoryFilter === 'All' 
                ? 'bg-gradient-to-r from-amber-400 to-orange-500 text-slate-950 font-black shadow-amber-500/25 ring-2 ring-amber-300 scale-[1.02]' 
                : 'bg-slate-900/90 text-slate-300 hover:text-white border border-white/10 hover:bg-slate-800 hover:border-amber-500/30'
            ]"
          >
            <span class="text-base">🌟</span>
            <span>Todas</span>
            <span class="text-[10px] px-1.5 py-0.5 rounded-full font-mono bg-black/40 text-amber-200 border border-white/10">
              {{ adminStore.physicalProducts.filter(p => !p.adminOnly && p.categoryId !== 'paginas').length }}
            </span>
          </button>

          <!-- Categorías Dinámicas -->
          <button 
            v-for="cat in adminStore.physicalCategories.filter(c => !c.adminOnly && c.id !== 'paginas' && parseInt(c.cantidad || '0') > 0)"
            :key="cat.id"
            type="button"
            @click="physicalCategoryFilter = physicalCategoryFilter === cat.id ? 'All' : cat.id"
            :class="[
              'px-3.5 py-2.5 rounded-2xl transition-all shrink-0 cursor-pointer text-xs font-bold flex items-center gap-2 shadow-sm',
              physicalCategoryFilter === cat.id 
                ? 'bg-gradient-to-r from-amber-400 to-amber-500 text-slate-950 font-black shadow-amber-500/25 ring-2 ring-amber-300 scale-[1.02]' 
                : 'bg-slate-900/90 text-slate-300 hover:text-white border border-white/10 hover:bg-slate-800 hover:border-amber-500/30'
            ]"
          >
            <span class="text-base">{{ getCategoryIcon(cat.id, cat.name) }}</span>
            <span>{{ cat.name }}</span>
            <span 
              :class="[
                'text-[10px] px-1.5 py-0.5 rounded-full font-mono font-bold',
                physicalCategoryFilter === cat.id ? 'bg-slate-950 text-amber-300' : 'bg-slate-800 text-slate-400 border border-slate-700'
              ]"
            >
              {{ cat.cantidad }}
            </span>
          </button>
        </div>
      </div>

      <!-- FILTRACIÓN POR PRECIOS, ORDENAMIENTO Y ACCIONES RÁPIDAS ("entre otros") -->
      <div class="glass-panel rounded-3xl p-4 sm:p-5 border border-white/[0.08] bg-slate-900/70 shadow-xl space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3.5 items-center">
          <!-- Ordenar Por -->
          <div class="md:col-span-4 flex items-center gap-2 bg-slate-950/80 p-2 rounded-2xl border border-slate-800">
            <span class="text-xs font-bold text-slate-400 pl-2 shrink-0">Ordenar:</span>
            <select 
              v-model="physicalSortBy" 
              class="w-full bg-transparent border-none text-white text-xs font-semibold focus:outline-none cursor-pointer py-1 pr-3"
            >
              <option value="default" class="bg-slate-900">✨ Destacados / Relevancia</option>
              <option value="price_asc" class="bg-slate-900">💲 Menor Precio ($ → $$$)</option>
              <option value="price_desc" class="bg-slate-900">💰 Mayor Precio ($$$ → $)</option>
              <option value="discount_desc" class="bg-slate-900">🔥 Mayor Descuento (% OFF)</option>
              <option value="stock_desc" class="bg-slate-900">📦 Mayor Stock Disponible</option>
              <option value="name_asc" class="bg-slate-900">🔤 Nombre Alfabético (A - Z)</option>
            </select>
          </div>

          <!-- Rango Rápido de Precios -->
          <div class="md:col-span-5 flex items-center gap-1.5 overflow-x-auto no-scrollbar">
            <button 
              type="button"
              @click="physicalPriceRange = 'all'; minPriceFilter = ''; maxPriceFilter = ''"
              :class="[
                'px-2.5 py-2 rounded-xl text-[11px] font-bold shrink-0 transition-all cursor-pointer',
                physicalPriceRange === 'all' && !minPriceFilter && !maxPriceFilter
                  ? 'bg-amber-400 text-slate-950 font-black shadow-sm'
                  : 'bg-slate-950 text-slate-400 hover:text-white border border-slate-800'
              ]"
            >
              Todos Precios
            </button>
            <button 
              type="button"
              @click="physicalPriceRange = 'under_15k'; minPriceFilter = ''; maxPriceFilter = ''"
              :class="[
                'px-2.5 py-2 rounded-xl text-[11px] font-bold shrink-0 transition-all cursor-pointer',
                physicalPriceRange === 'under_15k'
                  ? 'bg-amber-400 text-slate-950 font-black shadow-sm'
                  : 'bg-slate-950 text-slate-400 hover:text-white border border-slate-800'
              ]"
            >
              Hasta $15k
            </button>
            <button 
              type="button"
              @click="physicalPriceRange = '15k_to_30k'; minPriceFilter = ''; maxPriceFilter = ''"
              :class="[
                'px-2.5 py-2 rounded-xl text-[11px] font-bold shrink-0 transition-all cursor-pointer',
                physicalPriceRange === '15k_to_30k'
                  ? 'bg-amber-400 text-slate-950 font-black shadow-sm'
                  : 'bg-slate-950 text-slate-400 hover:text-white border border-slate-800'
              ]"
            >
              $15k - $30k
            </button>
            <button 
              type="button"
              @click="physicalPriceRange = '30k_to_60k'; minPriceFilter = ''; maxPriceFilter = ''"
              :class="[
                'px-2.5 py-2 rounded-xl text-[11px] font-bold shrink-0 transition-all cursor-pointer',
                physicalPriceRange === '30k_to_60k'
                  ? 'bg-amber-400 text-slate-950 font-black shadow-sm'
                  : 'bg-slate-950 text-slate-400 hover:text-white border border-slate-800'
              ]"
            >
              $30k - $60k
            </button>
            <button 
              type="button"
              @click="physicalPriceRange = 'over_60k'; minPriceFilter = ''; maxPriceFilter = ''"
              :class="[
                'px-2.5 py-2 rounded-xl text-[11px] font-bold shrink-0 transition-all cursor-pointer',
                physicalPriceRange === 'over_60k'
                  ? 'bg-amber-400 text-slate-950 font-black shadow-sm'
                  : 'bg-slate-950 text-slate-400 hover:text-white border border-slate-800'
              ]"
            >
              + $60k
            </button>
          </div>

          <!-- Filtro Manual Min / Max -->
          <div class="md:col-span-3 flex items-center gap-1.5 bg-slate-950/80 p-1.5 rounded-2xl border border-slate-800 text-xs">
            <span class="text-slate-500 font-bold pl-1">$</span>
            <input 
              v-model="minPriceFilter" 
              type="number" 
              placeholder="Mín" 
              class="w-full bg-slate-900 border border-slate-800 rounded-lg px-2 py-1 text-white text-xs font-mono focus:outline-none focus:border-amber-400"
              @input="physicalPriceRange = 'custom'"
            >
            <span class="text-slate-600">-</span>
            <input 
              v-model="maxPriceFilter" 
              type="number" 
              placeholder="Máx" 
              class="w-full bg-slate-900 border border-slate-800 rounded-lg px-2 py-1 text-white text-xs font-mono focus:outline-none focus:border-amber-400"
              @input="physicalPriceRange = 'custom'"
            >
            <button 
              v-if="minPriceFilter || maxPriceFilter" 
              @click="minPriceFilter = ''; maxPriceFilter = ''; physicalPriceRange = 'all'" 
              class="text-slate-500 hover:text-white px-1 font-bold cursor-pointer"
              title="Borrar rango de precios"
            >
              ✕
            </button>
          </div>
        </div>

        <!-- Chips de Filtros Rápidos (Descuentos, Envío Gratis, Stock, Limpiar) -->
        <div class="pt-2 border-t border-white/[0.08] flex flex-wrap items-center justify-between gap-2 text-xs">
          <div class="flex flex-wrap items-center gap-2">
            <button 
              type="button" 
              @click="toggleOnlyDiscounts"
              :class="[
                'px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer shadow-sm',
                onlyDiscountsFilter 
                  ? 'bg-gradient-to-r from-red-500 to-amber-500 text-white font-black shadow-red-500/25 ring-2 ring-amber-400' 
                  : 'bg-slate-950 text-slate-300 hover:text-white border border-slate-800 hover:bg-slate-800'
              ]"
            >
              <span>🔥</span>
              <span>Solo Ofertas % OFF</span>
              <span v-if="physicalDiscountsCount > 0" class="text-[10px] px-1.5 py-0.2 rounded-full font-mono bg-black/40 text-amber-300 border border-amber-400/30">
                {{ physicalDiscountsCount }}
              </span>
            </button>

            <button 
              type="button" 
              @click="toggleFreeShipping"
              :class="[
                'px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer shadow-sm',
                freeShippingFilter 
                  ? 'bg-gradient-to-r from-emerald-500 to-teal-500 text-slate-950 font-black shadow-emerald-500/25 ring-2 ring-emerald-300' 
                  : 'bg-slate-950 text-slate-300 hover:text-white border border-slate-800 hover:bg-slate-800'
              ]"
            >
              <span>🚚</span>
              <span>Envío Gratis (+$40k)</span>
              <span v-if="physicalFreeShippingCount > 0" class="text-[10px] px-1.5 py-0.2 rounded-full font-mono bg-black/40 text-emerald-300 border border-emerald-400/30">
                {{ physicalFreeShippingCount }}
              </span>
            </button>

            <button 
              type="button" 
              @click="inStockOnlyFilter = !inStockOnlyFilter"
              :class="[
                'px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer shadow-sm',
                inStockOnlyFilter 
                  ? 'bg-gradient-to-r from-sky-500 to-blue-600 text-white font-black shadow-sky-500/25 ring-2 ring-sky-300' 
                  : 'bg-slate-950 text-slate-300 hover:text-white border border-slate-800 hover:bg-slate-800'
              ]"
            >
              <span>🟢</span>
              <span>Solo con Stock</span>
              <span v-if="physicalInStockCount > 0" class="text-[10px] px-1.5 py-0.2 rounded-full font-mono bg-black/40 text-sky-300 border border-sky-400/30">
                {{ physicalInStockCount }}
              </span>
            </button>
          </div>

          <!-- Botón Limpiar Todos los Filtros si hay alguno activo -->
          <button 
            v-if="activePhysicalFiltersCount > 0"
            type="button"
            @click="resetPhysicalFilters"
            class="px-3 py-1.5 rounded-xl text-xs font-bold bg-red-950/60 hover:bg-red-900 text-red-300 hover:text-white border border-red-500/40 transition-colors flex items-center gap-1.5 cursor-pointer shadow-sm ml-auto"
          >
            <span>↺</span>
            <span>Limpiar Filtros ({{ activePhysicalFiltersCount }})</span>
          </button>
        </div>
      </div>

      <!-- Barra de Resumen y Paginación Superior del Catálogo Físico -->
      <div id="catalogo-fisico-grid" class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-2 text-xs">
        <div class="flex items-center gap-2 text-slate-400">
          <span class="w-2.5 h-2.5 rounded-full bg-amber-400 animate-pulse"></span>
          <span>Mostrando</span>
          <span class="font-bold text-white font-mono">{{ filteredPhysicalProducts.length === 0 ? 0 : (((physicalCurrentPage - 1) * (physicalItemsPerPage === 'all' ? filteredPhysicalProducts.length : physicalItemsPerPage)) + 1) }} - {{ Math.min(physicalCurrentPage * (physicalItemsPerPage === 'all' ? filteredPhysicalProducts.length : physicalItemsPerPage), filteredPhysicalProducts.length) }}</span>
          <span>de</span>
          <span class="font-bold text-amber-400 font-mono">{{ filteredPhysicalProducts.length }}</span>
          <span>productos</span>
          <span v-if="physicalTotalPages > 1" class="text-slate-500 font-mono">
            (Pág. {{ physicalCurrentPage }} de {{ physicalTotalPages }})
          </span>
        </div>

        <div class="flex items-center gap-3">
          <div class="flex items-center gap-1.5 text-slate-400">
            <span class="text-[11px]">Por página:</span>
            <select v-model="physicalItemsPerPage" class="bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-1 text-white text-xs font-mono focus:outline-none cursor-pointer">
              <option :value="12">12</option>
              <option :value="16">16</option>
              <option :value="24">24</option>
              <option :value="32">32</option>
              <option :value="48">48</option>
              <option value="all">Todos</option>
            </select>
          </div>

          <div v-if="physicalTotalPages > 1" class="flex items-center gap-1">
            <button 
              type="button"
              @click="changePhysicalPage(physicalCurrentPage - 1)" 
              :disabled="physicalCurrentPage <= 1"
              class="px-2.5 py-1 rounded-lg bg-slate-900 hover:bg-slate-800 disabled:opacity-30 disabled:cursor-not-allowed border border-slate-700 text-slate-200 font-bold cursor-pointer transition-colors"
              title="Página anterior"
            >
              ‹ Ant
            </button>
            <span class="px-2 py-1 bg-slate-950 font-mono text-amber-400 font-bold border border-slate-800 rounded-lg">
              {{ physicalCurrentPage }} / {{ physicalTotalPages }}
            </span>
            <button 
              type="button"
              @click="changePhysicalPage(physicalCurrentPage + 1)" 
              :disabled="physicalCurrentPage >= physicalTotalPages"
              class="px-2.5 py-1 rounded-lg bg-slate-900 hover:bg-slate-800 disabled:opacity-30 disabled:cursor-not-allowed border border-slate-700 text-slate-200 font-bold cursor-pointer transition-colors"
              title="Página siguiente"
            >
              Sig ›
            </button>
          </div>
        </div>
      </div>

      <!-- Estado Vacío si no hay coincidencias -->
      <div v-if="filteredPhysicalProducts.length === 0" class="text-center py-12 bg-slate-900/40 rounded-3xl border border-slate-800 border-dashed space-y-3">
        <span class="text-4xl block">📦</span>
        <h4 class="text-white font-black text-base">No se encontraron productos físicos</h4>
        <p class="text-xs text-slate-400 max-w-md mx-auto">No hay resultados para los filtros actuales. Prueba limpiando la búsqueda o los filtros de precio aplicados.</p>
        <button 
          type="button"
          @click="resetPhysicalFilters" 
          class="bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-xs px-5 py-2.5 rounded-xl transition cursor-pointer shadow-md inline-flex items-center gap-1.5"
        >
          <span>Restablecer Filtros y Ver Todos ({{ adminStore.physicalProducts.filter(p => !p.adminOnly && p.categoryId !== 'paginas').length }})</span>
        </button>
      </div>

      <!-- Grid de Productos Físicos (2 columnas en móviles, 4 en desktop) -->
      <div v-else class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2.5 sm:gap-5">
        <div 
          v-for="item in paginatedPhysicalProducts" 
          :key="item.id" 
          class="bg-slate-900/80 rounded-xl sm:rounded-2xl border border-white/[0.08] overflow-hidden flex flex-col justify-between hover:border-amber-400/40 hover:-translate-y-1 transition-all duration-300 shadow-lg group relative"
        >
          <!-- Envío Gratis Badge si corresponde -->
          <div v-if="item.priceNumeric >= 40000" class="absolute top-2 left-2 sm:top-3 sm:left-3 bg-emerald-500 text-slate-950 text-[8px] sm:text-[10px] font-black px-1.5 sm:px-2 py-0.5 rounded-full shadow-md z-10">
            ENVÍO GRATIS
          </div>

          <!-- Badge Descuento / Oferta -->
          <div v-if="item.hasDiscount && item.discountPercentage > 0" class="absolute top-2 right-2 sm:top-3 sm:right-3 bg-red-600 text-white text-[8px] sm:text-[10px] font-black px-1.5 sm:px-2 py-0.5 rounded-full shadow-md z-10 flex items-center gap-0.5">
            <span>🔥</span> {{ item.discountPercentage }}% OFF
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
              <!-- Badges de SKU, Stock y Variantes -->
              <div class="flex flex-wrap items-center gap-1 mb-1.5">
                <span v-if="item.sku" class="text-[9px] font-mono text-slate-400 bg-slate-800/90 px-1.5 py-0.5 rounded border border-slate-700/60 truncate max-w-[120px]" :title="'SKU: ' + item.sku">
                  SKU: {{ item.sku }}
                </span>
                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded" :class="(item.stock || 0) > 10 ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : 'bg-amber-500/10 text-amber-400 border border-amber-500/30'">
                  Stock: {{ item.stock || 0 }} un.
                </span>
                <span v-if="item.variables && item.variables.length > 1" class="text-[9px] font-semibold text-sky-300 bg-sky-950/60 border border-sky-800/60 px-1.5 py-0.5 rounded">
                  {{ item.variables.length }} opciones
                </span>
              </div>

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
              <div class="flex flex-col mb-1.5 sm:mb-2.5">
                <div class="flex items-center justify-between">
                  <span class="text-[9px] sm:text-xs text-slate-400">Oferta:</span>
                  <span class="text-xs sm:text-lg font-black text-amber-400 font-mono">{{ item.price }}</span>
                </div>
                <div v-if="item.hasDiscount && item.originalPrice" class="flex items-center justify-between text-[10px] sm:text-xs text-slate-400">
                  <span class="text-[9px] text-slate-500">Real de Lista:</span>
                  <span class="line-through text-slate-400 font-mono">{{ item.originalPrice }}</span>
                </div>
              </div>

              <!-- Botones de Acción: Ver Más, Agregar y Compartir Link -->
              <div class="space-y-1 sm:space-y-2">
                <div class="flex gap-1 sm:gap-2">
                  <button 
                    @click="openPhysicalModal(item)" 
                    class="flex-1 bg-slate-800 hover:bg-slate-700 text-slate-200 text-[10px] sm:text-xs font-bold py-1.5 sm:py-2 rounded-lg sm:rounded-xl transition-colors border border-slate-700 flex items-center justify-center gap-1 cursor-pointer active:scale-95"
                  >
                    <span>📋</span>
                    <span>Ficha</span>
                  </button>
                  <button 
                    @click="addPhysicalToCart(item, 1)" 
                    class="flex-1 bg-amber-500 hover:bg-amber-400 text-slate-950 text-[10px] sm:text-xs font-black py-1.5 sm:py-2 rounded-lg sm:rounded-xl transition-all shadow flex items-center justify-center gap-1 cursor-pointer active:scale-95"
                  >
                    <span>🛒</span>
                    <span>+ Carrito</span>
                  </button>
                </div>

                <!-- Botón AYUDAR COMPARTIENDO -->
                <div class="flex gap-1 pt-0.5">
                  <button 
                    @click="copyShareProductUrl(item)" 
                    class="flex-1 bg-amber-500/10 hover:bg-amber-500/20 text-[9px] sm:text-[11px] font-black py-1 sm:py-1.5 rounded-lg border border-amber-500/30 text-amber-300 hover:text-amber-200 transition-colors flex items-center justify-center gap-1 truncate px-1 cursor-pointer"
                    title="Copiar enlace para compartir"
                  >
                    <span class="truncate">🤝 COMPARTIR</span>
                  </button>
                  <button 
                    @click="shareProductWhatsApp(item)" 
                    class="bg-emerald-600/20 hover:bg-emerald-600 text-emerald-400 hover:text-white text-[10px] sm:text-[11px] font-bold px-1.5 sm:px-2 py-1 sm:py-1.5 rounded-lg border border-emerald-500/30 transition-colors shrink-0 cursor-pointer"
                    title="Compartir por WhatsApp"
                  >
                    💬
                  </button>
                  <button 
                    @click="openFlyerForPhysical(item)" 
                    class="bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 hover:text-white text-[10px] sm:text-[11px] font-bold px-1.5 sm:px-2 py-1 sm:py-1.5 rounded-lg border border-amber-500/30 transition-colors shrink-0 flex items-center justify-center cursor-pointer"
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

      <!-- Paginación Inferior del Catálogo Físico -->
      <div v-if="filteredPhysicalProducts.length > 0" class="pt-6 border-t border-slate-800 flex flex-col md:flex-row items-center justify-between gap-4 text-xs">
        <div class="flex items-center gap-2 text-slate-400">
          <span>Mostrando</span>
          <span class="font-bold text-white font-mono">{{ ((physicalCurrentPage - 1) * (physicalItemsPerPage === 'all' ? filteredPhysicalProducts.length : physicalItemsPerPage)) + 1 }} - {{ Math.min(physicalCurrentPage * (physicalItemsPerPage === 'all' ? filteredPhysicalProducts.length : physicalItemsPerPage), filteredPhysicalProducts.length) }}</span>
          <span>de</span>
          <span class="font-bold text-amber-400 font-mono">{{ filteredPhysicalProducts.length }}</span>
          <span>productos</span>
          <span v-if="activePhysicalFiltersCount > 0" class="text-[10px] bg-amber-500/15 text-amber-300 border border-amber-500/30 px-2 py-0.5 rounded-full font-bold ml-1">
            Filtrados ({{ activePhysicalFiltersCount }})
          </span>
        </div>

        <div class="flex flex-wrap items-center justify-center gap-3">
          <div class="flex items-center gap-1.5 text-slate-400">
            <span class="text-[11px]">Por pág:</span>
            <select v-model="physicalItemsPerPage" class="bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-1.5 text-white text-xs font-mono focus:outline-none cursor-pointer">
              <option :value="12">12</option>
              <option :value="16">16</option>
              <option :value="24">24</option>
              <option :value="32">32</option>
              <option :value="48">48</option>
              <option value="all">Todos</option>
            </select>
          </div>

          <div v-if="physicalTotalPages > 1" class="flex items-center gap-1">
            <button 
              type="button"
              @click="changePhysicalPage(1)" 
              :disabled="physicalCurrentPage <= 1"
              class="px-2.5 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 disabled:opacity-30 disabled:cursor-not-allowed border border-slate-700 text-slate-200 font-bold cursor-pointer transition-colors"
              title="Primera página"
            >
              ««
            </button>
            <button 
              type="button"
              @click="changePhysicalPage(physicalCurrentPage - 1)" 
              :disabled="physicalCurrentPage <= 1"
              class="px-3 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 disabled:opacity-30 disabled:cursor-not-allowed border border-slate-700 text-slate-200 font-bold cursor-pointer transition-colors"
              title="Página anterior"
            >
              ‹ Anterior
            </button>

            <!-- Números de Página con Elipsis -->
            <template v-for="(p, idx) in visiblePhysicalPages" :key="idx">
              <span v-if="p === '...'" class="px-2 py-1 text-slate-500 font-mono">...</span>
              <button 
                v-else
                type="button"
                @click="changePhysicalPage(p)"
                :class="[
                  'px-3.5 py-1.5 rounded-lg font-bold text-xs font-mono transition-all cursor-pointer',
                  physicalCurrentPage === p 
                    ? 'bg-amber-400 text-slate-950 shadow-md shadow-amber-400/25 font-black ring-2 ring-amber-300' 
                    : 'bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800'
                ]"
              >
                {{ p }}
              </button>
            </template>

            <button 
              type="button"
              @click="changePhysicalPage(physicalCurrentPage + 1)" 
              :disabled="physicalCurrentPage >= physicalTotalPages"
              class="px-3 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 disabled:opacity-30 disabled:cursor-not-allowed border border-slate-700 text-slate-200 font-bold cursor-pointer transition-colors"
              title="Página siguiente"
            >
              Siguiente ›
            </button>
            <button 
              type="button"
              @click="changePhysicalPage(physicalTotalPages)" 
              :disabled="physicalCurrentPage >= physicalTotalPages"
              class="px-2.5 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 disabled:opacity-30 disabled:cursor-not-allowed border border-slate-700 text-slate-200 font-bold cursor-pointer transition-colors"
              title="Última página"
            >
              »»
            </button>
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

    <!-- MODAL DE DETALLE DE PRODUCTO FÍSICO (revende.com.ar) -->
    <div v-if="showPhysicalModal && selectedPhysicalProduct" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm animate-in fade-in duration-200">
      <div class="bg-slate-900 border border-slate-700 w-full max-w-2xl rounded-3xl p-6 shadow-2xl relative space-y-5 max-h-[90vh] overflow-y-auto overscroll-contain custom-scrollbar">
        <!-- Encabezado con Categoría, ID, SKU y Stock -->
        <div class="flex justify-between items-start border-b border-slate-800 pb-3">
          <div class="flex flex-wrap items-center gap-2">
            <span class="bg-amber-400/20 text-amber-300 text-[10px] font-black uppercase px-2 py-0.5 rounded border border-amber-400/30">
              {{ selectedPhysicalProduct.category || 'Catálogo Oficial' }}
            </span>
            <span class="text-slate-400 text-xs font-mono">ID: #{{ selectedPhysicalProduct.id }}</span>
            <span v-if="selectedPhysicalVariant?.sku || selectedPhysicalProduct.sku" class="text-slate-300 text-xs font-mono bg-slate-800 px-2 py-0.5 rounded border border-slate-700">
              SKU: {{ selectedPhysicalVariant?.sku || selectedPhysicalProduct.sku }}
            </span>
            <span class="text-xs font-bold px-2 py-0.5 rounded" :class="((selectedPhysicalVariant ? selectedPhysicalVariant.stock : selectedPhysicalProduct.stock) || 0) > 10 ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-amber-500/20 text-amber-300 border border-amber-500/30'">
              Stock: {{ (selectedPhysicalVariant ? selectedPhysicalVariant.stock : selectedPhysicalProduct.stock) || 0 }} disponibles
            </span>
          </div>
          <button @click="showPhysicalModal = false" class="text-slate-400 hover:text-white p-1 text-lg">✕</button>
        </div>

        <!-- Imagen y Galería + Info de Precios -->
        <div class="flex flex-col sm:flex-row gap-6 items-start">
          <div class="w-full sm:w-64 shrink-0 space-y-2">
            <div class="w-full h-56 sm:h-64 bg-white rounded-3xl p-4 flex items-center justify-center shadow-2xl border-2 border-white/20 overflow-hidden group">
              <img 
                :src="activePhysicalImage || selectedPhysicalProduct.image" 
                :alt="selectedPhysicalProduct.name" 
                class="max-h-full max-w-full object-contain group-hover:scale-105 transition-transform duration-300"
              >
            </div>
            <!-- Miniaturas de Galería de Imágenes si hay más de 1 -->
            <div v-if="selectedPhysicalProduct.images && selectedPhysicalProduct.images.length > 1" class="flex gap-2 overflow-x-auto py-1 custom-scrollbar">
              <button 
                v-for="(img, idx) in selectedPhysicalProduct.images" 
                :key="idx"
                type="button"
                @click="selectPhysicalImage(img)"
                class="w-12 h-12 rounded-xl bg-white p-1 border-2 shrink-0 transition-all cursor-pointer overflow-hidden flex items-center justify-center"
                :class="(activePhysicalImage === img || (!activePhysicalImage && idx === 0)) ? 'border-amber-400 scale-105 shadow-md' : 'border-slate-700 opacity-70 hover:opacity-100'"
              >
                <img :src="img" :alt="selectedPhysicalProduct.name" class="max-h-full max-w-full object-contain" />
              </button>
            </div>
          </div>

          <div class="space-y-3 flex-1">
            <h3 class="text-lg sm:text-xl font-black text-white leading-snug">{{ selectedPhysicalProduct.name }}</h3>

            <!-- Precios: Oferta Rebajado + Real de Lista -->
            <div class="space-y-1 bg-slate-950 p-3.5 rounded-2xl border border-slate-800">
              <div class="flex items-baseline gap-3">
                <span class="text-2xl sm:text-3xl font-black text-amber-400 font-mono">
                  {{ selectedPhysicalVariant?.priceFormatted || selectedPhysicalProduct.price }}
                </span>
                <span 
                  v-if="selectedPhysicalVariant?.comparePriceFormatted || (selectedPhysicalProduct.hasDiscount && selectedPhysicalProduct.originalPrice)" 
                  class="text-sm sm:text-base text-slate-400 line-through font-mono"
                >
                  {{ selectedPhysicalVariant?.comparePriceFormatted || selectedPhysicalProduct.originalPrice }}
                </span>
              </div>
              <div class="flex flex-wrap items-center gap-2 pt-1">
                <span 
                  v-if="selectedPhysicalProduct.hasDiscount && selectedPhysicalProduct.discountPercentage > 0" 
                  class="bg-red-600/20 text-red-400 border border-red-500/30 text-xs font-bold px-2 py-0.5 rounded-full"
                >
                  🔥 ¡Ahorras {{ selectedPhysicalProduct.discountPercentage }}% en esta oferta!
                </span>
                <span 
                  v-if="(selectedPhysicalVariant ? selectedPhysicalVariant.price : selectedPhysicalProduct.priceNumeric) >= 40000" 
                  class="bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 text-xs font-bold px-2 py-0.5 rounded-full"
                >
                  ✓ Califica para Envío Gratis
                </span>
              </div>
            </div>

            <!-- Selector de Variantes / Opciones (si el producto tiene más de 1 variable) -->
            <div v-if="selectedPhysicalProduct.variables && selectedPhysicalProduct.variables.length > 1" class="space-y-2 bg-slate-950 p-3.5 rounded-2xl border border-sky-500/30">
              <div class="flex items-center justify-between text-xs">
                <span class="font-bold text-sky-300">Seleccionar Opción / Variante:</span>
                <span class="text-amber-400 font-mono font-bold">{{ selectedPhysicalVariant?.name }}</span>
              </div>
              <div class="flex flex-wrap gap-2">
                <button 
                  v-for="v in selectedPhysicalProduct.variables" 
                  :key="v.id"
                  type="button"
                  @click="selectPhysicalVariant(v)"
                  class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all border flex items-center gap-1.5 cursor-pointer"
                  :class="selectedPhysicalVariant?.id === v.id 
                    ? 'bg-amber-400 text-slate-950 border-amber-300 shadow-md font-black scale-105' 
                    : 'bg-slate-900 text-slate-300 border-slate-700 hover:border-slate-500'"
                >
                  <span>{{ v.name }}</span>
                  <span v-if="v.stock !== undefined" class="text-[10px] opacity-75">({{ v.stock }} un.)</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Descripción Completa -->
        <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 space-y-2 text-xs">
          <h4 class="font-bold text-slate-300 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
            <span>📋</span>
            <span>Descripción y Especificaciones:</span>
          </h4>
          <div 
            class="text-slate-300 leading-relaxed space-y-2 [&>p]:mb-2 [&>h1]:text-sm [&>h1]:font-bold [&>h1]:text-white [&>ul]:list-disc [&>ul]:pl-4" 
            v-html="selectedPhysicalProduct.description || 'Sin descripción adicional.'"
          ></div>
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
          <button @click="addPhysicalToCart(selectedPhysicalProduct, physicalQuantity)" class="flex-1 bg-amber-500 hover:bg-amber-400 text-slate-950 font-black py-3 rounded-xl transition-all shadow-lg text-sm flex items-center justify-center gap-2 cursor-pointer">
            <span>Agregar al Pedido ({{ physicalQuantity }} un.){{ selectedPhysicalVariant && selectedPhysicalVariant.name !== 'Variante 1' ? ' - ' + selectedPhysicalVariant.name : '' }}</span>
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
          <img :src="selectedPhysicalProduct.image" :alt="selectedPhysicalProduct.name" class="max-h-full max-w-full object-contain" @error="(e) => e.target.src = '/og-banner.png'">
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

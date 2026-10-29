import { defineStore } from 'pinia'
import physicalCategoriesData from '../assets/physicalCategories.json'
import physicalProductsData from '../assets/physicalProducts.json'
import { loadDb, saveDb, DB_KEYS, clearDbAll } from '../services/db'
import { parseBankEmail, matchTransferWithOrders } from '../services/bankEmailParser'
import { deleteReceipt } from '../services/receiptCloudStorage'
import { parseArgentinePrice } from '../utils/pricing'
import { CLEAN_PRICING_CATALOG, createStarterAccountForPlatform, getCleanStarterInventory, applyPristineDatabase } from '../services/cleanDatabaseSeed'
import { 
  initFirebase, 
  saveStreamingOrderToFirebase, 
  savePhysicalOrderToFirebase, 
  saveUserToFirebase,
  fetchLatestOrdersFromFirebase,
  fetchLatestPhysicalOrdersFromFirebase,
  fetchRegisteredUsersFromFirebase,
  listenToStreamingOrders, 
  listenToPhysicalOrders,
  listenToRegisteredUsers,
  testFirebaseConnection 
} from '../services/firebase'

export const OFFICIAL_PROVIDER_WHATSAPP = '+54 9 291 565-9321'

function sanitizePhone(phone) {
  if (!phone || phone.includes('0000-0000') || phone.includes('0000000') || phone.trim() === '') {
    return OFFICIAL_PROVIDER_WHATSAPP
  }
  return phone
}

// Catálogo Base Oficial de Planes de Productos Virtuales
export const DEFAULT_PLATFORM_PLANS = {
  Netflix: {
    name: 'Netflix Premium Ultra HD 4K',
    plans: {
      '1_perfil': {
        label: '1 Perfil (1 Dispositivo)',
        units: 1,
        type: 'perfil',
        providerCost: 5500,
        resellerProfit: 2200,
        clientPrice: 7700
      },
      '2_dispositivos': {
        label: '2 Dispositivos simultáneos',
        units: 2,
        type: 'dispositivo',
        providerCost: 10000,
        resellerProfit: 3000,
        clientPrice: 13000
      },
      '3_dispositivos': {
        label: '3 Dispositivos simultáneos',
        units: 3,
        type: 'dispositivo',
        providerCost: 14500,
        resellerProfit: 4000,
        clientPrice: 18500
      },
      '4_dispositivos': {
        label: '4 Dispositivos simultáneos (Cuenta Completa)',
        units: 4,
        type: 'dispositivo',
        providerCost: 18500,
        resellerProfit: 5500,
        clientPrice: 24000
      }
    }
  },
  Max: {
    name: 'Max Platino Estándar',
    plans: {
      '1_perfil': {
        label: '1 Perfil individual',
        units: 1,
        type: 'perfil',
        providerCost: 4200,
        resellerProfit: 1800,
        clientPrice: 6000
      },
      '2_dispositivos': {
        label: '2 Dispositivos simultáneos',
        units: 2,
        type: 'dispositivo',
        providerCost: 7800,
        resellerProfit: 2700,
        clientPrice: 10500
      },
      '3_dispositivos': {
        label: '3 Dispositivos simultáneos',
        units: 3,
        type: 'dispositivo',
        providerCost: 11000,
        resellerProfit: 3500,
        clientPrice: 14500
      },
      '4_dispositivos': {
        label: '4 Dispositivos (Familia)',
        units: 4,
        type: 'dispositivo',
        providerCost: 14000,
        resellerProfit: 4500,
        clientPrice: 18500
      }
    }
  },
  Spotify: {
    name: 'Spotify Familiar Individual',
    plans: {
      '1_perfil': {
        label: '1 Slot / Perfil individual',
        units: 1,
        type: 'perfil',
        providerCost: 2500,
        resellerProfit: 1300,
        clientPrice: 3800
      },
      '2_dispositivos': {
        label: '2 Slots Familiares',
        units: 2,
        type: 'dispositivo',
        providerCost: 4800,
        resellerProfit: 2000,
        clientPrice: 6800
      },
      '4_dispositivos': {
        label: '4 Slots Familiares',
        units: 4,
        type: 'dispositivo',
        providerCost: 9000,
        resellerProfit: 3500,
        clientPrice: 12500
      }
    }
  },
  'Disney+': {
    name: 'Disney+ / Star+ Combo',
    plans: {
      '1_perfil': {
        label: '1 Perfil individual',
        units: 1,
        type: 'perfil',
        providerCost: 4500,
        resellerProfit: 1800,
        clientPrice: 6300
      },
      '2_dispositivos': {
        label: '2 Dispositivos simultáneos',
        units: 2,
        type: 'dispositivo',
        providerCost: 8200,
        resellerProfit: 2600,
        clientPrice: 10800
      },
      '4_dispositivos': {
        label: '4 Dispositivos simultáneos',
        units: 4,
        type: 'dispositivo',
        providerCost: 15000,
        resellerProfit: 4500,
        clientPrice: 19500
      }
    }
  },
  IPTV: {
    name: 'IPTV MagisTV Pro',
    plans: {
      '1_perfil': {
        label: '1 Dispositivo / Pantalla',
        units: 1,
        type: 'dispositivo',
        providerCost: 3500,
        resellerProfit: 2000,
        clientPrice: 5500
      },
      '2_dispositivos': {
        label: '2 Dispositivos simultáneos',
        units: 2,
        type: 'dispositivo',
        providerCost: 6500,
        resellerProfit: 3000,
        clientPrice: 9500
      },
      '3_dispositivos': {
        label: '3 Dispositivos simultáneos',
        units: 3,
        type: 'dispositivo',
        providerCost: 9000,
        resellerProfit: 4000,
        clientPrice: 13000
      }
    }
  },
  'Prime Video': {
    name: 'Amazon Prime Video 4K',
    plans: {
      '1_perfil': {
        label: '1 Perfil 4K',
        units: 1,
        type: 'perfil',
        providerCost: 2800,
        resellerProfit: 1400,
        clientPrice: 4200
      },
      '3_dispositivos': {
        label: '3 Pantallas Simultáneas',
        units: 3,
        type: 'dispositivo',
        providerCost: 7000,
        resellerProfit: 2800,
        clientPrice: 9800
      }
    }
  },
  'YouTube Premium': {
    name: 'YouTube Premium & Music',
    plans: {
      '1_perfil': {
        label: '1 Slot Familiar Individual',
        units: 1,
        type: 'perfil',
        providerCost: 2900,
        resellerProfit: 1500,
        clientPrice: 4400
      }
    }
  },
  'Canva Pro': {
    name: 'Canva Pro Diseño & Productividad',
    plans: {
      '1_perfil': {
        label: 'Activación a tu Correo Personal (1 Año)',
        units: 1,
        type: 'perfil',
        providerCost: 2800,
        resellerProfit: 1700,
        clientPrice: 4500
      }
    }
  }
}

export const PLATFORM_PLANS = DEFAULT_PLATFORM_PLANS

// Configuración Inicial de Revendedor Oficial Base para Producción (Limpio para arrancar)
const DEFAULT_INITIAL_RESELLERS = []

const DEFAULT_PROVIDER_BANK = {
  holderName: 'StreamePro Mayorista Central',
  bankName: 'Mercado Pago',
  cuit: '20-00000000-0',
  cbuAlias: 'STREAMEPRO.MAYORISTA',
  cbuNumber: '0000000000000000000000',
  whatsapp: OFFICIAL_PROVIDER_WHATSAPP,
  email: 'pagos@streampro.com'
}

function getInitialProviderBankSetup() {
  const rawBankInfo = loadDb(DB_KEYS.PROVIDER_BANK, DEFAULT_PROVIDER_BANK)
  const savedBankInfo = {
    ...rawBankInfo,
    whatsapp: sanitizePhone(rawBankInfo?.whatsapp)
  }
  let savedAccounts = loadDb(DB_KEYS.PROVIDER_BANK_ACCOUNTS, null)

  if (!Array.isArray(savedAccounts) || savedAccounts.length === 0) {
    savedAccounts = [
      {
        id: 'acc_mp_principal',
        name: savedBankInfo.bankName ? `${savedBankInfo.bankName} Principal` : 'Mercado Pago Principal',
        isActive: true,
        holderName: savedBankInfo.holderName || DEFAULT_PROVIDER_BANK.holderName,
        bankName: savedBankInfo.bankName || DEFAULT_PROVIDER_BANK.bankName,
        cuit: savedBankInfo.cuit || DEFAULT_PROVIDER_BANK.cuit,
        cbuAlias: savedBankInfo.cbuAlias || DEFAULT_PROVIDER_BANK.cbuAlias,
        cbuNumber: savedBankInfo.cbuNumber || DEFAULT_PROVIDER_BANK.cbuNumber,
        whatsapp: sanitizePhone(savedBankInfo.whatsapp),
        email: savedBankInfo.email || DEFAULT_PROVIDER_BANK.email,
        notifyEmail: savedBankInfo.notifyEmail || savedBankInfo.email || 'cobros.streampro@gmail.com'
      }
    ]
  } else {
    savedAccounts.forEach(acc => {
      acc.whatsapp = sanitizePhone(acc.whatsapp)
    })
  }

  // Asegurar que exista al menos una cuenta activa
  const hasActive = savedAccounts.some(a => a.isActive)
  if (!hasActive && savedAccounts.length > 0) {
    savedAccounts[0].isActive = true
  }

  const activeAcc = savedAccounts.find(a => a.isActive) || savedAccounts[0]
  const synchronizedBankInfo = {
    holderName: activeAcc.holderName,
    bankName: activeAcc.bankName,
    cuit: activeAcc.cuit,
    cbuAlias: activeAcc.cbuAlias,
    cbuNumber: activeAcc.cbuNumber,
    whatsapp: sanitizePhone(activeAcc.whatsapp),
    email: activeAcc.email
  }

  return { accounts: savedAccounts, info: synchronizedBankInfo }
}

export const useAdminStore = defineStore('admin', {
  state: () => {
    const initialBank = getInitialProviderBankSetup()
    return {
      // Credenciales Maestras Verídicas de Administrador
      adminAuth: (() => {
        const auth = loadDb(DB_KEYS.ADMIN_AUTH, {
          username: 'admin',
          password: 'StreamPro2026*Admin',
          name: 'Administrador Principal',
          whatsapp: OFFICIAL_PROVIDER_WHATSAPP
        })
        auth.whatsapp = sanitizePhone(auth.whatsapp)
        return auth
      })(),

      // Configuración de Notificaciones Automáticas por Telegram Bot
      telegramConfig: (() => {
        const conf = loadDb(DB_KEYS.TELEGRAM_CONFIG, {
          botToken: '9120060693:AAFynj7rM0cfElf1PMZ0fox9fZxj3wLqA30',
          chatId: '7994226676',
          enabled: true
        })
        if (!conf.botToken) conf.botToken = '9120060693:AAFynj7rM0cfElf1PMZ0fox9fZxj3wLqA30'
        if (!conf.chatId) conf.chatId = '7994226676'
        conf.enabled = true
        return conf
      })(),

      // Configuración de Sincronización en la Nube con Firebase Firestore
      firebaseConfig: (() => {
        const conf = loadDb(DB_KEYS.FIREBASE_CONFIG, {
          apiKey: 'AIzaSyBOk3oDY6--EKyETxAfKzRAwasF_HAXMZM',
          authDomain: 'streamepro-6f5a3.firebaseapp.com',
          projectId: 'streamepro-6f5a3',
          storageBucket: 'streamepro-6f5a3.firebasestorage.app',
          messagingSenderId: '55882803488',
          appId: '1:55882803488:web:dd4693a750541ce154cf45',
          measurementId: 'G-DVRKEHEERZ',
          enabled: true
        })
        if (!conf.apiKey || !conf.projectId) {
          conf.apiKey = 'AIzaSyBOk3oDY6--EKyETxAfKzRAwasF_HAXMZM'
          conf.authDomain = 'streamepro-6f5a3.firebaseapp.com'
          conf.projectId = 'streamepro-6f5a3'
          conf.storageBucket = 'streamepro-6f5a3.firebasestorage.app'
          conf.messagingSenderId = '55882803488'
          conf.appId = '1:55882803488:web:dd4693a750541ce154cf45'
          conf.measurementId = 'G-DVRKEHEERZ'
          conf.enabled = true
        }
        return conf
      })(),

      // Catálogo de Productos Virtuales y Precios persistente
      pricingCatalog: loadDb(DB_KEYS.PRICING_CATALOG, DEFAULT_PLATFORM_PLANS),

      // Revendedores reales generados desde el panel con usuario, contraseña y datos bancarios
      resellers: loadDb(DB_KEYS.RESELLERS, DEFAULT_INITIAL_RESELLERS),

      // Historial global de transacciones de billetera cargadas desde Admin (Inicia limpio en producción)
      walletTransactions: loadDb(DB_KEYS.WALLET_TRANSACTIONS, []),

      // Cuentas Bancarias registradas del PROVEEDOR (Multi-Cuenta con opción de cambiar de cuenta activa)
      providerBankAccounts: initialBank.accounts,

      // Datos de transferencia bancaria oficial del PROVEEDOR (Sincronizado con la cuenta activa)
      providerBankInfo: initialBank.info,

    // Transferencias detectadas por correo (Estilo Wanderlust)
    bankTransfers: loadDb(DB_KEYS.BANK_TRANSFERS, []),

    // Alertas emitidas por el Proveedor hacia los Revendedores (Limpio en producción)
    resellerAlerts: loadDb(DB_KEYS.RESELLER_ALERTS, []),

    // Solicitudes de Renovación emitidas por clientes (Limpio en producción)
    renewalRequests: loadDb(DB_KEYS.RENEWAL_REQUESTS, []),

    // INVENTARIO VIRTUAL: Cuentas maestras mayoristas con perfiles y PINs (Limpio en producción)
    inventory: loadDb(DB_KEYS.INVENTORY, []),

    // PEDIDOS VIRTUALES: Órdenes de streaming (Limpio en producción)
    orders: loadDb(DB_KEYS.ORDERS, []),

    // CATÁLOGO Y PEDIDOS DE PRODUCTOS FÍSICOS (Sincronizado con revende.com.ar y Gestión Admin)
    physicalProducts: (() => {
      const stored = loadDb(DB_KEYS.PHYSICAL_PRODUCTS, null)
      if (Array.isArray(stored) && stored.length >= 140 && stored[0]?.source === 'revende.com.ar') {
        const hasPages = stored.some(p => p.adminOnly || p.categoryId === 'paginas')
        if (hasPages) return stored
      }
      const initial = physicalProductsData.map(p => ({
        ...p,
        image: p.image || '',
        priceNumeric: p.priceNumeric || parseArgentinePrice(p.price)
      }))
      saveDb(DB_KEYS.PHYSICAL_PRODUCTS, initial)
      return initial
    })(),
    physicalCategories: (() => {
      const stored = loadDb(DB_KEYS.PHYSICAL_CATEGORIES, null)
      if (Array.isArray(stored) && stored.length > 0 && stored.some(c => c.id === 'paginas')) {
        return stored
      }
      const initial = physicalCategoriesData.filter(c => parseInt(c.cantidad || '0') > 0 || c.adminOnly)
      saveDb(DB_KEYS.PHYSICAL_CATEGORIES, initial)
      return initial
    })(),
    physicalOrders: loadDb(DB_KEYS.PHYSICAL_ORDERS, []),

    // DIRECTORIO CENTRAL DE USUARIOS REGISTRADOS (Clientes y Distribuidores)
    registeredUsers: loadDb(DB_KEYS.REGISTERED_USERS, [
      {
        id: 'USR-PROV-01',
        clientNumber: 'ADM-001',
        name: 'Administrador StreamePro',
        phone: OFFICIAL_PROVIDER_WHATSAPP,
        email: 'soporte@streamepro.me',
        role: 'provider',
        status: 'Verificado',
        pin: '2026',
        ordersCount: 0,
        totalSpent: 0,
        registeredAt: '2026-10-01',
        lastActive: '2026-10-26',
        resellerId: 'DIRECT'
      }
    ]),

    // SOLICITUDES DE POSTULACIÓN DE NUEVOS REVENDEDORES DESDE EL LANDING PAGE
    resellerApplications: loadDb(DB_KEYS.RESELLER_APPLICATIONS, []),

    // PAQUETES Y COMBOS VIRTUALES OFICIALES
    packages: loadDb(DB_KEYS.PACKAGES, [
      {
        id: 'PKG-101',
        title: 'Combo Cine Total 4K (Netflix + Disney+ + Max)',
        description: 'Las 3 plataformas líderes de series, cine y estrenos en calidad Ultra HD.',
        badge: '🔥 25% OFF - RECOMENDADO',
        services: [
          { platform: 'Netflix', planKey: '1_perfil', planLabel: '1 Pantalla 4K', units: 1, regularPrice: 7700, cost: 5500 },
          { platform: 'Max', planKey: '1_perfil', planLabel: '1 Pantalla Platino', units: 1, regularPrice: 6000, cost: 4200 },
          { platform: 'Disney+', planKey: '1_perfil', planLabel: '1 Pantalla VIP', units: 1, regularPrice: 6500, cost: 4500 }
        ],
        originalPrice: 20200,
        promoPrice: 15900,
        providerCost: 14200,
        resellerProfit: 1700,
        createdBy: 'provider',
        resellerId: 'ALL',
        active: true,
        createdAt: '2026-10-14'
      },
      {
        id: 'PKG-102',
        title: 'Dúo Series & Música (Netflix + Spotify)',
        description: 'Tus series favoritas en Ultra HD y toda la música del mundo sin publicidad.',
        badge: '⚡ COMBO POPULAR',
        services: [
          { platform: 'Netflix', planKey: '1_perfil', planLabel: '1 Pantalla 4K', units: 1, regularPrice: 7700, cost: 5500 },
          { platform: 'Spotify', planKey: '1_perfil', planLabel: '1 Slot Familiar', units: 1, regularPrice: 3800, cost: 2500 }
        ],
        originalPrice: 11500,
        promoPrice: 9400,
        providerCost: 8000,
        resellerProfit: 1400,
        createdBy: 'provider',
        resellerId: 'ALL',
        active: true,
        createdAt: '2026-10-14'
      }
    ]),

    // Visibilidad de la tienda pública y del catálogo físico (Mostrar u Ocultar tienda desde el Panel Admin)
    isStoreVisible: (() => {
      const stored = loadDb(DB_KEYS.STORE_SETTINGS, null)
      if (stored && typeof stored.isStoreVisible === 'boolean') {
        return stored.isStoreVisible
      }
      return true
    })(),
    isPhysicalStoreVisible: (() => {
      const stored = loadDb(DB_KEYS.STORE_SETTINGS, null)
      if (stored && typeof stored.isPhysicalStoreVisible === 'boolean') {
        return stored.isPhysicalStoreVisible
      }
      return true
    })()
  }},
  getters: {
    totalInvestment: (state) => state.inventory.reduce((acc, item) => acc + (Number(item.cost) || 0), 0),
    totalProviderRevenue: (state) => state.orders.filter(o => o.status === 'Dispatched').reduce((acc, o) => acc + (o.providerCost || 0), 0),
    availableStock: (state) => state.inventory.filter(i => i.status === 'Available').length,
    pendingOrders: (state) => state.orders.filter(o => o.status === 'Waiting Provider'),
    pendingRenewals: (state) => state.renewalRequests.filter(r => r.status === 'Pending Provider'),
    pendingPhysicalOrders: (state) => state.physicalOrders.filter(o => o.status === 'Pendiente'),
    receivedPhysicalOrders: (state) => state.physicalOrders.filter(o => o.status === 'Recibido'),
    confirmedPhysicalOrders: (state) => state.physicalOrders.filter(o => o.status === 'Confirmado' || o.status === 'Aprobado'),
    approvedPhysicalOrders: (state) => state.physicalOrders.filter(o => o.status === 'Confirmado' || o.status === 'Aprobado'),
    cancelledPhysicalOrders: (state) => state.physicalOrders.filter(o => o.status === 'Cancelado'),
    pendingResellerApplications: (state) => (state.resellerApplications || []).filter(a => a.status === 'pending'),
    approvedResellerApplications: (state) => (state.resellerApplications || []).filter(a => a.status === 'approved'),
    directOrders: (state) => (state.orders || []).filter(o => o.resellerId === 'DIRECT' || !o.resellerId),
    resellerOrders: (state) => (state.orders || []).filter(o => o.resellerId && o.resellerId !== 'DIRECT'),

    // Métricas Dinámicas de Stock por Plataforma y Pantallas
    getPlatformStock: (state) => (platform) => {
      const accounts = (state.inventory || []).filter(i => i.platform === platform)
      let totalProfiles = 0
      let freeProfiles = 0
      accounts.forEach(acc => {
        const max = acc.maxProfiles || acc.profiles?.length || 4
        totalProfiles += max
        const free = (acc.profiles || []).filter(p => !p.isAssigned).length
        freeProfiles += free
      })
      const availableAccounts = accounts.filter(acc => (acc.profiles || []).some(p => !p.isAssigned))
      return {
        platform,
        totalAccounts: accounts.length,
        availableAccounts: availableAccounts.length,
        totalProfiles,
        freeProfiles,
        status: freeProfiles >= 3 ? 'in_stock' : (freeProfiles > 0 ? 'low_stock' : 'out_of_stock')
      }
    },

    platformStockSummary: (state) => {
      const platforms = ['Netflix', 'Max', 'Disney+', 'Spotify', 'IPTV', 'Prime Video', 'YouTube Premium']
      const summary = {}
      platforms.forEach(plat => {
        const accounts = (state.inventory || []).filter(i => i.platform === plat)
        let totalProfiles = 0
        let freeProfiles = 0
        accounts.forEach(acc => {
          const max = acc.maxProfiles || acc.profiles?.length || 4
          totalProfiles += max
          const free = (acc.profiles || []).filter(p => !p.isAssigned).length
          freeProfiles += free
        })
        const availableAccounts = accounts.filter(acc => (acc.profiles || []).some(p => !p.isAssigned))
        summary[plat] = {
          totalAccounts: accounts.length,
          availableAccounts: availableAccounts.length,
          totalProfiles,
          freeProfiles,
          status: freeProfiles >= 3 ? 'in_stock' : (freeProfiles > 0 ? 'low_stock' : 'out_of_stock')
        }
      })
      return summary
    }
  },
  actions: {
    // Sincronización forzada a Base de Datos
    persist(key) {
      if (key && this[key] !== undefined) {
        saveDb(key, this[key])
      } else if (key === DB_KEYS.TELEGRAM_CONFIG) {
        saveDb(DB_KEYS.TELEGRAM_CONFIG, this.telegramConfig)
      } else if (key === DB_KEYS.FIREBASE_CONFIG) {
        saveDb(DB_KEYS.FIREBASE_CONFIG, this.firebaseConfig)
      } else {
        saveDb(DB_KEYS.INVENTORY, this.inventory)
        saveDb(DB_KEYS.RESELLERS, this.resellers)
        saveDb(DB_KEYS.ORDERS, this.orders)
        saveDb(DB_KEYS.PACKAGES, this.packages)
        saveDb(DB_KEYS.PHYSICAL_ORDERS, this.physicalOrders)
        saveDb(DB_KEYS.WALLET_TRANSACTIONS, this.walletTransactions)
        saveDb(DB_KEYS.RESELLER_ALERTS, this.resellerAlerts)
        saveDb(DB_KEYS.RENEWAL_REQUESTS, this.renewalRequests)
        saveDb(DB_KEYS.PROVIDER_BANK, this.providerBankInfo)
        saveDb(DB_KEYS.PROVIDER_BANK_ACCOUNTS, this.providerBankAccounts)
        saveDb(DB_KEYS.BANK_TRANSFERS, this.bankTransfers)
        saveDb(DB_KEYS.PRICING_CATALOG, this.pricingCatalog)
        saveDb(DB_KEYS.ADMIN_AUTH, this.adminAuth)
        saveDb(DB_KEYS.PHYSICAL_PRODUCTS, this.physicalProducts)
        saveDb(DB_KEYS.PHYSICAL_CATEGORIES, this.physicalCategories)
        saveDb(DB_KEYS.REGISTERED_USERS, this.registeredUsers)
        saveDb(DB_KEYS.TELEGRAM_CONFIG, this.telegramConfig)
        saveDb(DB_KEYS.FIREBASE_CONFIG, this.firebaseConfig)
      }
    },

    // Actualización de credenciales y estado del Bot de Telegram
    updateTelegramConfig({ botToken, chatId, enabled }) {
      if (botToken !== undefined) this.telegramConfig.botToken = botToken.trim()
      if (chatId !== undefined) this.telegramConfig.chatId = chatId.trim()
      if (enabled !== undefined) this.telegramConfig.enabled = Boolean(enabled)
      this.persist(DB_KEYS.TELEGRAM_CONFIG)
      return { success: true }
    },

    // Actualización de configuración de Firebase Firestore
    updateFirebaseConfig(config) {
      this.firebaseConfig = { ...this.firebaseConfig, ...config }
      this.persist(DB_KEYS.FIREBASE_CONFIG)
      this.initCloudSync()
      return { success: true }
    },

    // Sincronización completa directa desde la nube Firebase
    async syncFromCloud() {
      initFirebase(this.firebaseConfig)
      try {
        const [remoteStreaming, remotePhysical, remoteUsers] = await Promise.all([
          fetchLatestOrdersFromFirebase(),
          fetchLatestPhysicalOrdersFromFirebase(),
          fetchRegisteredUsersFromFirebase()
        ])

        let updatedStreaming = 0
        let updatedPhysical = 0
        let updatedUsers = 0

        remoteStreaming.forEach(rem => {
          const idx = this.orders.findIndex(o => o.id === rem.id)
          if (idx === -1) {
            this.orders.unshift(rem)
            updatedStreaming++
          } else if (this.orders[idx].status !== rem.status) {
            this.orders[idx] = { ...this.orders[idx], ...rem }
            updatedStreaming++
          }
        })
        if (updatedStreaming > 0) this.persist(DB_KEYS.ORDERS)

        remotePhysical.forEach(rem => {
          const idx = this.physicalOrders.findIndex(o => o.id === rem.id)
          if (idx === -1) {
            this.physicalOrders.unshift(rem)
            updatedPhysical++
          } else if (this.physicalOrders[idx].status !== rem.status) {
            this.physicalOrders[idx] = { ...this.physicalOrders[idx], ...rem }
            updatedPhysical++
          }
        })
        if (updatedPhysical > 0) this.persist(DB_KEYS.PHYSICAL_ORDERS)

        remoteUsers.forEach(rem => {
          const idx = this.registeredUsers.findIndex(u => u.clientNumber === rem.clientNumber || (u.phone && rem.phone && u.phone.replace(/[^0-9]/g, '') === rem.phone.replace(/[^0-9]/g, '')))
          if (idx === -1) {
            this.registeredUsers.unshift(rem)
            updatedUsers++
          }
        })
        if (updatedUsers > 0) this.persist(DB_KEYS.REGISTERED_USERS)

        return {
          success: true,
          streamingCount: remoteStreaming.length,
          physicalCount: remotePhysical.length,
          usersCount: remoteUsers.length,
          totalNew: updatedStreaming + updatedPhysical + updatedUsers
        }
      } catch (e) {
        console.warn('[Sync] Error al sincronizar desde la nube:', e)
        return { success: false, error: e.message }
      }
    },

    // Inicialización y escucha en tiempo real de Firebase
    initCloudSync() {
      initFirebase(this.firebaseConfig)

      // Carga inicial directa de la nube
      this.syncFromCloud().catch(() => {})

      // Escucha en tiempo real de pedidos de Streaming
      listenToStreamingOrders((remoteOrders) => {
        if (!remoteOrders || remoteOrders.length === 0) return
        let updated = false
        remoteOrders.forEach(rem => {
          const idx = this.orders.findIndex(o => o.id === rem.id)
          if (idx === -1) {
            this.orders.unshift(rem)
            updated = true
          } else if (this.orders[idx].status !== rem.status) {
            this.orders[idx] = { ...this.orders[idx], ...rem }
            updated = true
          }
        })
        if (updated) {
          this.persist(DB_KEYS.ORDERS)
        }
      })

      // Escucha en tiempo real de pedidos Físicos
      listenToPhysicalOrders((remotePhysical) => {
        if (!remotePhysical || remotePhysical.length === 0) return
        let updated = false
        remotePhysical.forEach(rem => {
          const idx = this.physicalOrders.findIndex(o => o.id === rem.id)
          if (idx === -1) {
            this.physicalOrders.unshift(rem)
            updated = true
          } else if (this.physicalOrders[idx].status !== rem.status) {
            this.physicalOrders[idx] = { ...this.physicalOrders[idx], ...rem }
            updated = true
          }
        })
        if (updated) {
          this.persist(DB_KEYS.PHYSICAL_ORDERS)
        }
      })

      // Escucha en tiempo real de clientes registrados
      listenToRegisteredUsers((remoteUsers) => {
        if (!remoteUsers || remoteUsers.length === 0) return
        let updated = false
        remoteUsers.forEach(rem => {
          const idx = this.registeredUsers.findIndex(u => u.clientNumber === rem.clientNumber || (u.phone && rem.phone && u.phone.replace(/[^0-9]/g, '') === rem.phone.replace(/[^0-9]/g, '')))
          if (idx === -1) {
            this.registeredUsers.unshift(rem)
            updated = true
          }
        })
        if (updated) {
          this.persist(DB_KEYS.REGISTERED_USERS)
        }
      })
    },

    // Gestión de Visibilidad de la Tienda Pública y Tienda de Productos Físicos
    toggleStoreVisibility() {
      this.isStoreVisible = !this.isStoreVisible
      this.persistStoreSettings()
      return this.isStoreVisible
    },
    setStoreVisibility(visible) {
      this.isStoreVisible = Boolean(visible)
      this.persistStoreSettings()
      return this.isStoreVisible
    },
    togglePhysicalStoreVisibility() {
      this.isPhysicalStoreVisible = !this.isPhysicalStoreVisible
      this.persistStoreSettings()
      return this.isPhysicalStoreVisible
    },
    setPhysicalStoreVisibility(visible) {
      this.isPhysicalStoreVisible = Boolean(visible)
      this.persistStoreSettings()
      return this.isPhysicalStoreVisible
    },
    persistStoreSettings() {
      saveDb(DB_KEYS.STORE_SETTINGS, {
        isStoreVisible: this.isStoreVisible,
        isPhysicalStoreVisible: this.isPhysicalStoreVisible
      })
    },

    // Gestión de Credenciales Maestras del Administrador
    updateAdminAuth({ username, password, name, whatsapp }) {
      if (username) this.adminAuth.username = username.trim()
      if (password) this.adminAuth.password = password.trim()
      if (name) this.adminAuth.name = name.trim()
      if (whatsapp) this.adminAuth.whatsapp = whatsapp.trim()
      this.persist(DB_KEYS.ADMIN_AUTH)
      return { success: true }
    },

    // Actualizar Datos Bancarios del Proveedor (Sincroniza con la cuenta activa)
    updateProviderBankInfo(bankData) {
      Object.assign(this.providerBankInfo, bankData)
      let active = this.providerBankAccounts.find(a => a.isActive)
      if (!active && this.providerBankAccounts.length > 0) {
        active = this.providerBankAccounts[0]
        active.isActive = true
      }
      if (active) {
        Object.assign(active, bankData)
        this.persist(DB_KEYS.PROVIDER_BANK_ACCOUNTS)
      }
      this.persist(DB_KEYS.PROVIDER_BANK)
      return { success: true }
    },

    // GESTIÓN MULTI-CUENTA BANCARIA DEL PROVEEDOR
    setActiveBankAccount(accountId) {
      this.providerBankAccounts.forEach(acc => {
        acc.isActive = (acc.id === accountId)
      })
      let active = this.providerBankAccounts.find(a => a.isActive)
      if (!active && this.providerBankAccounts.length > 0) {
        active = this.providerBankAccounts[0]
        active.isActive = true
      }
      if (active) {
        this.providerBankInfo = {
          holderName: active.holderName,
          bankName: active.bankName,
          cuit: active.cuit,
          cbuAlias: active.cbuAlias,
          cbuNumber: active.cbuNumber,
          whatsapp: active.whatsapp,
          email: active.email
        }
        this.persist(DB_KEYS.PROVIDER_BANK)
        this.persist(DB_KEYS.PROVIDER_BANK_ACCOUNTS)
      }
      return { success: true, activeAccount: active }
    },

    addProviderBankAccount(accountData) {
      const newId = `acc_${Date.now()}`
      const isFirst = this.providerBankAccounts.length === 0
      const newAcc = {
        id: newId,
        name: accountData.name || 'Nueva Cuenta Bancaria',
        isActive: isFirst,
        holderName: accountData.holderName || '',
        bankName: accountData.bankName || '',
        cuit: accountData.cuit || '',
        cbuAlias: accountData.cbuAlias || '',
        cbuNumber: accountData.cbuNumber || '',
        whatsapp: accountData.whatsapp || '',
        email: accountData.email || '',
        notifyEmail: accountData.notifyEmail || accountData.email || ''
      }
      this.providerBankAccounts.push(newAcc)
      if (isFirst) {
        this.setActiveBankAccount(newId)
      } else {
        this.persist(DB_KEYS.PROVIDER_BANK_ACCOUNTS)
      }
      return { success: true, account: newAcc }
    },

    updateProviderBankAccount(accountData) {
      const idx = this.providerBankAccounts.findIndex(a => a.id === accountData.id)
      if (idx !== -1) {
        Object.assign(this.providerBankAccounts[idx], accountData)
        if (this.providerBankAccounts[idx].isActive) {
          this.setActiveBankAccount(accountData.id)
        } else {
          this.persist(DB_KEYS.PROVIDER_BANK_ACCOUNTS)
        }
        return { success: true }
      }
      return { error: 'Cuenta no encontrada' }
    },

    deleteProviderBankAccount(accountId) {
      if (this.providerBankAccounts.length <= 1) {
        return { error: 'No puedes eliminar la única cuenta bancaria configurada.' }
      }
      const target = this.providerBankAccounts.find(a => a.id === accountId)
      const wasActive = target?.isActive
      this.providerBankAccounts = this.providerBankAccounts.filter(a => a.id !== accountId)
      if (wasActive && this.providerBankAccounts.length > 0) {
        this.setActiveBankAccount(this.providerBankAccounts[0].id)
      } else {
        this.persist(DB_KEYS.PROVIDER_BANK_ACCOUNTS)
      }
      return { success: true }
    },

    // RECONOCIMIENTO Y CONCILIACIÓN DE TRANSFERENCIAS BANCARIAS (ESTILO WANDERLUST)
    processBankTransferNotification(rawEmailText, subject = '') {
      const parsed = parseBankEmail(rawEmailText, subject)
      if (!parsed.amount) {
        return { error: 'No se detectó un monto válido en el texto de la notificación' }
      }

      // Buscar coincidencia en órdenes pendientes
      const matches = matchTransferWithOrders(parsed, this.pendingOrders)
      const bestMatch = matches.length > 0 ? matches[0] : null

      const transferRecord = {
        id: `TR-${Date.now().toString().slice(-6)}`,
        ...parsed,
        matchedOrderId: bestMatch ? bestMatch.order.id : null,
        matchedOrderClientNumber: bestMatch ? bestMatch.order.customerNumber : null,
        matchedCustomerName: bestMatch ? bestMatch.order.customerName : null,
        confidence: bestMatch ? bestMatch.confidence : 'none',
        reasons: bestMatch ? bestMatch.reasons : [],
        status: bestMatch ? 'matched' : 'unmatched',
        createdAt: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
        createdDate: new Date().toISOString()
      }

      this.bankTransfers.unshift(transferRecord)

      // Si la coincidencia es exacta o alta, actualizar la orden
      if (bestMatch && (bestMatch.confidence === 'exact' || bestMatch.confidence === 'high')) {
        const order = this.orders.find(o => o.id === bestMatch.order.id)
        if (order) {
          order.bankTransferVerified = true
          order.bankTransferId = transferRecord.id
          order.bankTransferDetails = {
            bankName: parsed.bankName,
            amount: parsed.amount,
            sender: parsed.sender,
            operationId: parsed.operationId,
            verifiedAt: new Date().toLocaleString()
          }
          this.persist(DB_KEYS.ORDERS)
        }
      }

      this.persist(DB_KEYS.BANK_TRANSFERS)
      return { success: true, transfer: transferRecord, bestMatch }
    },

    confirmOrderPaymentWithTransfer(orderId, transferId) {
      const order = this.orders.find(o => o.id === orderId)
      const transfer = this.bankTransfers.find(t => t.id === transferId)
      if (!order) return { error: 'Orden no encontrada' }

      order.bankTransferVerified = true
      order.bankTransferId = transferId
      if (transfer) {
        transfer.status = 'confirmed'
        transfer.matchedOrderId = order.id
        transfer.matchedOrderClientNumber = order.customerNumber
        order.bankTransferDetails = {
          bankName: transfer.bankName,
          amount: transfer.amount,
          sender: transfer.sender,
          operationId: transfer.operationId,
          verifiedAt: new Date().toLocaleString()
        }
        this.persist(DB_KEYS.BANK_TRANSFERS)
      }

      this.persist(DB_KEYS.ORDERS)
      return { success: true, order }
    },

    deleteBankTransfer(transferId) {
      this.bankTransfers = this.bankTransfers.filter(t => t.id !== transferId)
      this.persist(DB_KEYS.BANK_TRANSFERS)
      return { success: true }
    },

    // Gestión de Catálogo de Productos Virtuales y Precios
    updatePricingPlan({ platform, planKey, planData }) {
      if (!this.pricingCatalog[platform]) return { error: 'Plataforma no encontrada' }
      if (!this.pricingCatalog[platform].plans[planKey]) return { error: 'Plan no encontrado' }
      
      Object.assign(this.pricingCatalog[platform].plans[planKey], planData)
      this.persist(DB_KEYS.PRICING_CATALOG)
      return { success: true }
    },

    addPlatform({ key, name, plans }) {
      if (this.pricingCatalog[key]) return { error: 'La plataforma ya existe' }
      this.pricingCatalog[key] = {
        name: name || key,
        plans: plans || {}
      }
      this.persist(DB_KEYS.PRICING_CATALOG)
      return { success: true }
    },

    deletePlatform(key) {
      if (this.pricingCatalog[key]) {
        delete this.pricingCatalog[key]
        this.persist(DB_KEYS.PRICING_CATALOG)
        return { success: true }
      }
      return { error: 'Plataforma no encontrada' }
    },

    ensurePhysicalProductsLoaded() {
      if (!this.physicalProducts || !Array.isArray(this.physicalProducts) || this.physicalProducts.length === 0 || this.physicalProducts[0]?.source !== 'revende.com.ar') {
        this.physicalProducts = physicalProductsData.map(p => ({
          ...p,
          image: p.image || '',
          priceNumeric: p.priceNumeric || parseArgentinePrice(p.price)
        }))
        this.persist(DB_KEYS.PHYSICAL_PRODUCTS)
      }
      if (!this.physicalCategories || !Array.isArray(this.physicalCategories) || this.physicalCategories.length === 0) {
        this.physicalCategories = physicalCategoriesData.filter(c => parseInt(c.cantidad || '0') > 0)
        this.persist(DB_KEYS.PHYSICAL_CATEGORIES)
      }
    },

    updatePhysicalProductsFromJson(productsList, categoriesList = null) {
      if (Array.isArray(productsList) && productsList.length > 0) {
        const formatted = productsList.map(p => ({
          ...p,
          image: p.image || '',
          priceNumeric: p.priceNumeric || parseArgentinePrice(p.price),
          stock: typeof p.stock === 'number' ? p.stock : (parseInt(p.stock) || 20)
        }))
        this.physicalProducts = formatted
        this.persist(DB_KEYS.PHYSICAL_PRODUCTS)

        if (categoriesList && Array.isArray(categoriesList)) {
          this.physicalCategories = categoriesList.filter(c => parseInt(c.cantidad || '0') > 0)
          this.persist(DB_KEYS.PHYSICAL_CATEGORIES)
        }
        return { success: true, count: formatted.length }
      }
      return { error: 'El archivo JSON no contiene una lista válida de productos.' }
    },

    // Sincronización en 1 Clic desde la API de revende.com.ar (con fallback automático)
    async syncPhysicalFromApi() {
      try {
        const res = await fetch('/api/sync-revende')
        if (!res.ok) throw new Error(`HTTP ${res.status}`)
        const data = await res.json()
        if (data.success && Array.isArray(data.products) && data.products.length > 0) {
          this.physicalProducts = data.products
          this.persist(DB_KEYS.PHYSICAL_PRODUCTS)
          if (Array.isArray(data.categories)) {
            this.physicalCategories = data.categories
            this.persist(DB_KEYS.PHYSICAL_CATEGORIES)
          }
          return { success: true, count: data.products.length }
        }
        throw new Error(data.error || 'Respuesta inválida de la API revende.com.ar')
      } catch (err) {
        console.warn('Sync API falló, aplicando catálogo base actualizado de revende.com.ar...', err)
        if (physicalProductsData && physicalProductsData.length > 0) {
          return this.updatePhysicalProductsFromJson(physicalProductsData, physicalCategoriesData)
        }
        return { error: err.message || 'No se pudo conectar con revende.com.ar' }
      }
    },

    // GESTIÓN CRUD DE PRODUCTOS FÍSICOS Y PÁGINAS (ADMIN)
    createPhysicalProduct(payload) {
      const id = payload.id || ('PROD-' + Date.now().toString().slice(-6))
      const priceNumeric = typeof payload.priceNumeric === 'number' ? payload.priceNumeric : parseArgentinePrice(payload.price)
      const originalPriceNumeric = payload.originalPriceNumeric ? Number(payload.originalPriceNumeric) : null
      const hasDiscount = Boolean(originalPriceNumeric && originalPriceNumeric > priceNumeric)
      const discountPercentage = hasDiscount 
        ? Math.round(((originalPriceNumeric - priceNumeric) / originalPriceNumeric) * 100) 
        : 0

      const formatPriceStr = (num) => {
        if (!num) return '$0,00'
        return '$' + Number(num).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
      }

      const categoryName = payload.category?.trim() || 'General'
      const categoryId = payload.categoryId || categoryName.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '')

      const newProduct = {
        id: String(id),
        sku: (payload.sku || '').trim(),
        name: (payload.name || payload.title || 'Nuevo Producto').trim(),
        title: (payload.name || payload.title || 'Nuevo Producto').trim(),
        category: categoryName,
        categoryId: categoryId,
        categoryIds: [categoryId],
        categoryHierarchy: [categoryName],
        price: payload.priceFormatted || formatPriceStr(priceNumeric),
        priceNumeric: priceNumeric,
        originalPrice: originalPriceNumeric ? formatPriceStr(originalPriceNumeric) : null,
        originalPriceNumeric: originalPriceNumeric,
        hasDiscount: hasDiscount,
        discountPercentage: discountPercentage,
        stock: typeof payload.stock === 'number' ? payload.stock : (parseInt(payload.stock) || 0),
        description: payload.description || '',
        image: payload.image || 'https://streamepro.me/og-banner.png',
        images: Array.isArray(payload.images) && payload.images.length > 0 ? payload.images : [payload.image || 'https://streamepro.me/og-banner.png'],
        url: payload.url || ('https://streamepro.me/p/' + id),
        variables: Array.isArray(payload.variables) ? payload.variables : [],
        requiresShipping: payload.requiresShipping !== false,
        source: payload.adminOnly ? 'admin-pages' : 'admin-created',
        adminOnly: Boolean(payload.adminOnly || categoryId === 'paginas'),
        pageSlug: payload.pageSlug || null,
        pageType: payload.pageType || 'producto',
        updatedAt: new Date().toISOString()
      }

      this.physicalProducts.unshift(newProduct)
      this.recalculatePhysicalCategoryCounts()
      this.persist(DB_KEYS.PHYSICAL_PRODUCTS)
      return { success: true, product: newProduct }
    },

    updatePhysicalProduct(id, payload) {
      const idx = this.physicalProducts.findIndex(p => String(p.id).trim() === String(id).trim())
      if (idx === -1) return { error: 'Producto no encontrado' }

      const current = this.physicalProducts[idx]
      const priceNumeric = payload.priceNumeric !== undefined 
        ? Number(payload.priceNumeric) 
        : (payload.price ? parseArgentinePrice(payload.price) : current.priceNumeric)
      
      const originalPriceNumeric = payload.originalPriceNumeric !== undefined 
        ? (payload.originalPriceNumeric ? Number(payload.originalPriceNumeric) : null)
        : current.originalPriceNumeric

      const hasDiscount = Boolean(originalPriceNumeric && originalPriceNumeric > priceNumeric)
      const discountPercentage = hasDiscount 
        ? Math.round(((originalPriceNumeric - priceNumeric) / originalPriceNumeric) * 100) 
        : 0

      const formatPriceStr = (num) => {
        if (!num) return '$0,00'
        return '$' + Number(num).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
      }

      const categoryName = payload.category !== undefined ? payload.category.trim() : current.category
      const categoryId = payload.categoryId || categoryName.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '')

      const updated = {
        ...current,
        ...payload,
        id: String(current.id),
        name: (payload.name || payload.title || current.name).trim(),
        title: (payload.name || payload.title || current.title).trim(),
        sku: payload.sku !== undefined ? payload.sku.trim() : current.sku,
        category: categoryName,
        categoryId: categoryId,
        categoryIds: [categoryId],
        price: formatPriceStr(priceNumeric),
        priceNumeric: priceNumeric,
        originalPrice: originalPriceNumeric ? formatPriceStr(originalPriceNumeric) : null,
        originalPriceNumeric: originalPriceNumeric,
        hasDiscount: hasDiscount,
        discountPercentage: discountPercentage,
        stock: payload.stock !== undefined ? Number(payload.stock) : current.stock,
        description: payload.description !== undefined ? payload.description : current.description,
        image: payload.image || current.image,
        images: Array.isArray(payload.images) && payload.images.length > 0 ? payload.images : current.images,
        variables: Array.isArray(payload.variables) ? payload.variables : current.variables,
        adminOnly: Boolean(payload.adminOnly !== undefined ? payload.adminOnly : (current.adminOnly || categoryId === 'paginas')),
        updatedAt: new Date().toISOString()
      }

      this.physicalProducts[idx] = updated
      this.recalculatePhysicalCategoryCounts()
      this.persist(DB_KEYS.PHYSICAL_PRODUCTS)
      return { success: true, product: updated }
    },

    deletePhysicalProduct(id) {
      const idx = this.physicalProducts.findIndex(p => String(p.id).trim() === String(id).trim())
      if (idx === -1) return { error: 'Producto no encontrado' }

      const removed = this.physicalProducts.splice(idx, 1)[0]
      this.recalculatePhysicalCategoryCounts()
      this.persist(DB_KEYS.PHYSICAL_PRODUCTS)
      return { success: true, product: removed }
    },

    updatePhysicalStock(id, newStock) {
      const prod = this.physicalProducts.find(p => String(p.id).trim() === String(id).trim())
      if (!prod) return { error: 'Producto no encontrado' }
      prod.stock = Math.max(0, parseInt(newStock) || 0)
      prod.updatedAt = new Date().toISOString()
      this.persist(DB_KEYS.PHYSICAL_PRODUCTS)
      return { success: true, stock: prod.stock }
    },

    recalculatePhysicalCategoryCounts() {
      if (!Array.isArray(this.physicalCategories)) return

      // Ensure 'paginas' category exists
      let paginasCat = this.physicalCategories.find(c => c.id === 'paginas')
      if (!paginasCat) {
        paginasCat = {
          id: 'paginas',
          name: '📄 Páginas y Secciones',
          slug: 'paginas',
          adminOnly: true,
          cantidad: '0'
        }
        this.physicalCategories.push(paginasCat)
      } else {
        paginasCat.adminOnly = true
      }

      this.physicalCategories.forEach(cat => {
        const count = this.physicalProducts.filter(p => 
          p.categoryId === cat.id || (p.categoryIds && p.categoryIds.includes(cat.id))
        ).length
        cat.cantidad = String(count)
      })
      this.persist(DB_KEYS.PHYSICAL_CATEGORIES)
    },

    // APLICAR PORCENTAJE (%) MASIVO A PRODUCTOS FÍSICOS (AUMENTO, DESCUENTO U OFERTA)
    applyBulkPercentageToPhysicalProducts({ action = 'increase', percentage = 10, categoryId = 'All', roundTo = 10 }) {
      const pct = Math.abs(Number(percentage) || 0)
      if (action !== 'restore' && pct === 0) {
        return { error: 'El porcentaje debe ser mayor a 0' }
      }
      if (action === 'increase' && pct > 150) {
        return { error: 'El aumento máximo permitido es de hasta 150%' }
      }
      if (action === 'discount' && pct >= 100) {
        return { error: 'El descuento no puede ser igual o mayor al 100%' }
      }

      const formatPriceStr = (num) => {
        if (!num) return '$0,00'
        return '$' + Number(num).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
      }

      const roundNum = (val) => {
        if (!roundTo || roundTo <= 1) return Math.round(val)
        return Math.round(val / roundTo) * roundTo
      }

      let modifiedCount = 0

      this.physicalProducts.forEach(prod => {
        // Excluir páginas y secciones de solo admin
        if (prod.adminOnly || prod.categoryId === 'paginas') return

        // Filtro por categoría opcional
        if (categoryId !== 'All') {
          const inCat = prod.categoryId === categoryId || (prod.categoryIds && prod.categoryIds.includes(categoryId))
          if (!inCat) return
        }

        // Obtener datos originales base de revende.com.ar (costo base / referencia oficial)
        const orig = physicalProductsData.find(p => String(p.id).trim() === String(prod.id).trim())

        if (action === 'restore') {
          if (orig) {
            prod.price = orig.price
            prod.priceNumeric = orig.priceNumeric
            prod.originalPrice = orig.originalPrice
            prod.originalPriceNumeric = orig.originalPriceNumeric
            prod.hasDiscount = orig.hasDiscount
            prod.discountPercentage = orig.discountPercentage
            if (Array.isArray(orig.variables)) {
              prod.variables = JSON.parse(JSON.stringify(orig.variables))
            }
            modifiedCount++
          }
          return
        }

        const currentPrice = prod.priceNumeric || parseArgentinePrice(prod.price)
        if (!currentPrice || currentPrice <= 0) return

        const factoryBase = orig ? (orig.priceNumeric || currentPrice) : currentPrice
        const factoryList = orig ? (orig.originalPriceNumeric || orig.priceNumeric || currentPrice) : currentPrice

        if (action === 'increase') {
          // AUMENTO / REMARCAR GANANCIAS (hasta 150%):
          // Mantener los descuentos de los productos que ya los tienen en proporciones de remarcar ganancias.
          const hasExistingDiscount = prod.hasDiscount || (prod.originalPriceNumeric && prod.originalPriceNumeric > currentPrice)

          if (hasExistingDiscount) {
            const currentOriginal = prod.originalPriceNumeric || currentPrice
            const newPrice = roundNum(currentPrice * (1 + pct / 100))
            const newOriginal = roundNum(currentOriginal * (1 + pct / 100))

            prod.priceNumeric = newPrice
            prod.price = formatPriceStr(newPrice)
            prod.originalPriceNumeric = newOriginal
            prod.originalPrice = formatPriceStr(newOriginal)
            prod.hasDiscount = true
            prod.discountPercentage = Math.round(((newOriginal - newPrice) / newOriginal) * 100)
          } else {
            const newPrice = roundNum(currentPrice * (1 + pct / 100))
            prod.priceNumeric = newPrice
            prod.price = formatPriceStr(newPrice)
            prod.originalPriceNumeric = null
            prod.originalPrice = null
            prod.hasDiscount = false
            prod.discountPercentage = 0
          }

          // Variantes proporcionales
          if (Array.isArray(prod.variables) && prod.variables.length > 0) {
            prod.variables.forEach(v => {
              const vCurrent = v.price || currentPrice
              v.price = roundNum(vCurrent * (1 + pct / 100))
              v.priceFormatted = formatPriceStr(v.price)
              if (v.comparePrice && v.comparePrice > vCurrent) {
                v.comparePrice = roundNum(v.comparePrice * (1 + pct / 100))
                v.comparePriceFormatted = formatPriceStr(v.comparePrice)
              }
            })
          }
        } else if (action === 'discount') {
          // REMARCAR DESCUENTOS TAMBIÉN CUANDO SE AJUSTEN DESCUENTOS:
          // 1) Si el catálogo tiene un margen remarcado previo, calculamos la proporción de markup
          const currentList = (prod.originalPriceNumeric && prod.originalPriceNumeric > currentPrice)
            ? prod.originalPriceNumeric
            : currentPrice

          const markupRatio = Math.max(1, currentList / factoryList)
          const baseUndiscountedPrice = roundNum(factoryBase * markupRatio)
          const fullListPrice = roundNum(factoryList * markupRatio)

          // 2) Calculamos el nuevo precio de oferta aplicando el descuento sobre la base de venta oficial
          const newSalePrice = roundNum(baseUndiscountedPrice * (1 - pct / 100))

          // 3) Remarcamos el precio de lista (tachado) asegurando que sea mayor al precio de oferta
          const finalOriginalPrice = Math.max(fullListPrice, roundNum(newSalePrice / (1 - pct / 100)))

          prod.originalPriceNumeric = finalOriginalPrice
          prod.originalPrice = formatPriceStr(finalOriginalPrice)
          prod.priceNumeric = newSalePrice
          prod.price = formatPriceStr(newSalePrice)
          prod.hasDiscount = true
          prod.discountPercentage = Math.round(((finalOriginalPrice - newSalePrice) / finalOriginalPrice) * 100)

          // Variantes remarcando descuentos en la misma proporción
          if (Array.isArray(prod.variables) && prod.variables.length > 0) {
            prod.variables.forEach(v => {
              const origVar = orig?.variables?.find(ov => ov.id === v.id)
              const vFactoryBase = origVar ? (origVar.price || origVar.comparePrice || factoryBase) : factoryBase
              const vFactoryList = origVar ? (origVar.comparePrice || origVar.price || factoryList) : factoryList
              const vMarkupRatio = Math.max(1, (v.comparePrice || v.price || currentPrice) / vFactoryList)

              const vBaseUndisc = roundNum(vFactoryBase * vMarkupRatio)
              const vFullList = roundNum(vFactoryList * vMarkupRatio)
              const vNewSale = roundNum(vBaseUndisc * (1 - pct / 100))
              const vFinalOrig = Math.max(vFullList, roundNum(vNewSale / (1 - pct / 100)))

              v.comparePrice = vFinalOrig
              v.comparePriceFormatted = formatPriceStr(vFinalOrig)
              v.price = vNewSale
              v.priceFormatted = formatPriceStr(vNewSale)
            })
          }
        }

        prod.updatedAt = new Date().toISOString()
        modifiedCount++
      })

      this.persist(DB_KEYS.PHYSICAL_PRODUCTS)
      return { success: true, count: modifiedCount }
    },

    // Gestión Centralizada de Usuarios Registrados (Clientes & Distribuidores)
    registerOrUpdateUser(userData) {
      if (!userData || !userData.name) return null
      const phone = userData.phone || userData.whatsapp || userData.customerPhone || ''
      const email = userData.email || ''
      const clientNumber = userData.clientNumber || userData.customerNumber || ''

      let existing = this.registeredUsers.find(u => 
        (clientNumber && u.clientNumber === clientNumber) ||
        (phone && u.phone && u.phone.replace(/[^0-9]/g, '') === phone.replace(/[^0-9]/g, '')) ||
        (email && u.email && u.email.toLowerCase() === email.toLowerCase())
      )

      const nowStr = new Date().toISOString().split('T')[0]
      const amount = Number(userData.amount || userData.total || 0)

      if (existing) {
        existing.name = userData.name || existing.name
        if (phone) existing.phone = phone
        if (email) existing.email = email
        if (userData.address) existing.address = userData.address
        if (userData.pin) existing.pin = userData.pin
        if (userData.role) existing.role = userData.role
        existing.lastActive = nowStr
        existing.ordersCount = (existing.ordersCount || 0) + (userData.isNewOrder ? 1 : 0)
        existing.totalSpent = (existing.totalSpent || 0) + amount
      } else {
        const newId = `USR-${Date.now().toString().slice(-4)}`
        existing = {
          id: newId,
          clientNumber: clientNumber || `CLI-${Math.floor(1000 + Math.random() * 9000)}`,
          name: userData.name,
          phone: phone,
          email: email,
          address: userData.address || '',
          role: userData.role || 'client',
          status: 'Activo',
          pin: userData.pin || String(Math.floor(1000 + Math.random() * 9000)),
          ordersCount: userData.isNewOrder ? 1 : 0,
          totalSpent: amount,
          registeredAt: nowStr,
          lastActive: nowStr,
          resellerId: userData.resellerId || 'DIRECT'
        }
        this.registeredUsers.unshift(existing)
      }

      this.persist(DB_KEYS.REGISTERED_USERS)
      saveUserToFirebase(existing).catch(() => {})
      return existing
    },

    updateUser(userId, updates) {
      const idx = this.registeredUsers.findIndex(u => u.id === userId)
      if (idx !== -1) {
        this.registeredUsers[idx] = { ...this.registeredUsers[idx], ...updates }
        this.persist(DB_KEYS.REGISTERED_USERS)
        return { success: true, user: this.registeredUsers[idx] }
      }
      return { error: 'Usuario no encontrado' }
    },

    deleteUser(userId) {
      const idx = this.registeredUsers.findIndex(u => u.id === userId)
      if (idx !== -1) {
        this.registeredUsers.splice(idx, 1)
        this.persist(DB_KEYS.REGISTERED_USERS)
        return { success: true }
      }
      return { error: 'Usuario no encontrado' }
    },

    // Carga de Inventario Mayorista Real
    addStock(item) {
      const generatedProfiles = item.profiles && item.profiles.length > 0 
        ? item.profiles.map((p, idx) => ({
            name: p.name?.trim() || `Perfil ${idx + 1}`,
            pin: String(p.pin ?? '').trim(),
            isAssigned: Boolean(p.isAssigned),
            assignedTo: p.assignedTo || null
          }))
        : Array.from({ length: Number(item.maxProfiles) || 4 }, (_, idx) => ({
            name: `Perfil ${idx + 1}`,
            pin: '',
            isAssigned: false,
            assignedTo: null
          }))

      const newItem = {
        ...item,
        id: `INV-${Date.now().toString().slice(-4)}`,
        status: 'Available',
        maxProfiles: Number(item.maxProfiles) || (generatedProfiles.length || 4),
        profiles: generatedProfiles,
        createdAt: new Date().toISOString()
      }

      this.inventory.push(newItem)
      this.persist(DB_KEYS.INVENTORY)
      return newItem
    },

    updateInventoryItem(updatedItem) {
      const idx = this.inventory.findIndex(i => i.id === updatedItem.id)
      if (idx !== -1) {
        const existing = this.inventory[idx]
        const cleanProfiles = (updatedItem.profiles || []).map((p, pIdx) => ({
          name: p.name?.trim() || `Perfil ${pIdx + 1}`,
          pin: String(p.pin ?? '').trim(),
          isAssigned: Boolean(p.isAssigned),
          assignedTo: p.assignedTo || null
        }))

        this.inventory[idx] = {
          ...existing,
          platform: updatedItem.platform || existing.platform,
          account: updatedItem.account || existing.account,
          pass: updatedItem.pass !== undefined ? updatedItem.pass : existing.pass,
          cost: Number(updatedItem.cost) || existing.cost,
          maxProfiles: Number(updatedItem.maxProfiles) || cleanProfiles.length || existing.maxProfiles,
          profiles: cleanProfiles,
          lastUpdated: new Date().toISOString()
        }
        this.persist(DB_KEYS.INVENTORY)
        return { success: true, item: this.inventory[idx] }
      }
      return { error: 'Lote de inventario no encontrado' }
    },

    deleteInventoryItem(invId) {
      const idx = this.inventory.findIndex(i => i.id === invId)
      if (idx !== -1) {
        this.inventory.splice(idx, 1)
        this.persist(DB_KEYS.INVENTORY)
        return { success: true }
      }
      return { error: 'Cuenta no encontrada' }
    },

    updateMasterPassword(invId, newPassword) {
      const inv = this.inventory.find(i => i.id === invId)
      if (inv) {
        inv.pass = newPassword
        inv.lastPasswordUpdate = new Date().toISOString()
        this.persist(DB_KEYS.INVENTORY)
      }
    },

    // Solicitud de Renovación
    requestRenewal({ clientId, clientNumber, clientName, platform, planName, resellerId, currentCredentials = null }) {
      const renewalId = `REN-${Date.now().toString().slice(-4)}`
      const newRenewal = {
        id: renewalId,
        clientId: clientId || `C-${Date.now().toString().slice(-3)}`,
        clientNumber: clientNumber || 'CLI-0000',
        clientName: clientName || 'Cliente',
        platform: platform || 'Streaming',
        planName: planName || 'Plan Oficial',
        resellerId: resellerId || 'DIRECT',
        status: 'Pending Provider',
        dateRequested: new Date().toISOString().split('T')[0],
        newCredentials: {
          account: currentCredentials?.account || 'cuenta.por.renovar@streampro.com',
          pass: currentCredentials?.pass || 'StreamRenov2026*',
          profiles: currentCredentials?.profiles ? JSON.parse(JSON.stringify(currentCredentials.profiles)) : [
            { name: 'PERFIL 1', pin: '1234' }
          ]
        }
      }
      this.renewalRequests.unshift(newRenewal)
      this.persist(DB_KEYS.RENEWAL_REQUESTS)
      return { success: true, renewal: newRenewal }
    },

    renewSameAccount(renewalId, days = 30) {
      const req = this.renewalRequests.find(r => r.id === renewalId)
      if (req) {
        req.status = 'Renovada (Misma Cuenta)'
        const resellerStore = useResellerStore()
        const client = resellerStore.clients.find(c => c.id === req.clientId)
        if (client) {
          client.status = 'Activa'
          const curExp = new Date(client.expiry)
          const baseDate = isNaN(curExp.getTime()) || curExp < new Date() ? new Date() : curExp
          baseDate.setDate(baseDate.getDate() + Number(days))
          client.expiry = baseDate.toISOString().split('T')[0]
          client.notes = `Misma cuenta renovada (+${days} días) el ${new Date().toLocaleDateString()}. Credenciales mantenidas.`
          resellerStore.persist()
        }

        this.sendAlertToReseller({
          resellerId: req.resellerId,
          clientId: req.clientId,
          clientNumber: req.clientNumber,
          clientName: req.clientName,
          type: 'general',
          title: `Renovación Exitosa: ${req.clientName}`,
          message: `El Proveedor renovó exitosamente la MISMA CUENTA para ${req.clientName} (+${days} días). Se mantienen el correo y claves. Estado: ACTIVA.`
        })

        this.persist(DB_KEYS.RENEWAL_REQUESTS)
        return { success: true }
      }
      return { error: 'Solicitud no encontrada' }
    },

    updateRenewalCredentials(renewalId, { account, pass, profiles }) {
      const req = this.renewalRequests.find(r => r.id === renewalId)
      if (req) {
        req.status = 'Provider Updated'
        req.newCredentials = {
          account: account.trim(),
          pass: pass.trim(),
          profiles: profiles || []
        }

        this.sendAlertToReseller({
          resellerId: req.resellerId,
          clientId: req.clientId,
          clientNumber: req.clientNumber,
          clientName: req.clientName,
          type: 'password_update',
          title: `Nuevas Credenciales Listas: ${req.clientName}`,
          message: `El Proveedor cargó nuevas credenciales para la renovación de ${req.clientName}. Por favor confirma en la pestaña Renovaciones para actualizar su ficha.`
        })

        this.persist(DB_KEYS.RENEWAL_REQUESTS)
        return { success: true }
      }
      return { error: 'Solicitud no encontrada' }
    },

    // Despacho de Pedido Digital y Asignación de Perfiles
    dispatchOrder(orderId, inventoryId, customBasePin = null) {
      const order = this.orders.find(o => o.id === orderId)
      const inv = this.inventory.find(i => i.id === inventoryId)
      
      if (!order || !inv) return { error: 'Orden o Cuenta no encontrada' }

      const freeSlots = inv.profiles.filter(p => !p.isAssigned)
      if (freeSlots.length < order.devices) {
        return { error: `La cuenta seleccionada solo tiene ${freeSlots.length} perfiles libres (se requieren ${order.devices}).` }
      }

      const basePin = customBasePin || freeSlots[0]?.pin || String(Math.floor(1000 + Math.random() * 9000))

      let deliveredProfiles = []
      if (order.profileSelections && order.profileSelections.length > 0) {
        deliveredProfiles = order.profileSelections.map((clientReq, idx) => {
          const slot = freeSlots[idx]
          const pinToUse = order.pinPreference === 'same' 
            ? basePin 
            : (slot?.pin || String(Math.floor(1000 + Math.random() * 9000)))

          return {
            name: clientReq.name || `PERFIL ${idx + 1}`,
            pin: pinToUse
          }
        })
      }

      let matchIdx = 0
      inv.profiles.forEach(p => {
        if (!p.isAssigned && matchIdx < deliveredProfiles.length) {
          p.isAssigned = true
          p.assignedTo = `${order.customerName} (${order.customerNumber})`
          p.name = deliveredProfiles[matchIdx].name
          p.pin = deliveredProfiles[matchIdx].pin
          matchIdx++
        }
      })

      const remainingFree = inv.profiles.filter(p => !p.isAssigned).length
      if (remainingFree === 0) {
        inv.status = 'Fully Assigned'
      }

      order.status = 'Dispatched'
      order.accountId = inv.id
      order.accountDetails = {
        email: inv.account,
        pass: inv.pass,
        platform: inv.platform,
        devicesCount: order.devices,
        profiles: deliveredProfiles
      }

      // Al confirmar cierre y entrega, purgar el comprobante de la mini nube para liberar espacio
      if (order.hasReceipt || order.receiptStatus === 'pending_verification') {
        order.hasReceipt = false
        order.receiptStatus = 'verified_and_purged'
        order.voucher = order.voucher && order.voucher !== 'Sin comprobante adjunto'
          ? `${order.voucher} (Verificado y depurado)`
          : 'Comprobante verificado y depurado'
        deleteReceipt(order.id).catch(() => {})
      }

      const resellerStore = useResellerStore()
      resellerStore.addDeliveredOrderToClients(order)

      this.persist(DB_KEYS.INVENTORY)
      this.persist(DB_KEYS.ORDERS)
      saveStreamingOrderToFirebase(order).catch(() => {})

      const deliveryMessage = this.generateDeliveryWhatsAppMessage(order)
      return { success: true, order, deliveryMessage }
    },

    // Despacho Manual con selección de Inventario y Perfiles
    dispatchOrderWithInventory(orderId, inventoryId, deliveryMatches = []) {
      const order = this.orders.find(o => o.id === orderId)
      const inv = this.inventory.find(i => i.id === inventoryId)

      if (!order || !inv) return { error: 'Orden o Cuenta no encontrada' }

      const freeSlots = inv.profiles.filter(p => !p.isAssigned)
      const requiredDevices = deliveryMatches.length || order.devices
      if (freeSlots.length < requiredDevices) {
        return { error: `La cuenta seleccionada solo tiene ${freeSlots.length} perfiles libres (se requieren ${requiredDevices}).` }
      }

      const deliveredProfiles = []
      let matchIdx = 0
      inv.profiles.forEach(p => {
        if (!p.isAssigned && matchIdx < deliveryMatches.length) {
          const match = deliveryMatches[matchIdx]
          p.isAssigned = true
          p.assignedTo = `${order.customerName} (${order.customerNumber})`
          p.name = match.name || `PERFIL ${matchIdx + 1}`
          p.pin = match.pin || String(Math.floor(1000 + Math.random() * 9000))
          deliveredProfiles.push({
            name: p.name,
            pin: p.pin
          })
          matchIdx++
        }
      })

      const remainingFree = inv.profiles.filter(p => !p.isAssigned).length
      if (remainingFree === 0) {
        inv.status = 'Fully Assigned'
      } else {
        inv.status = 'Partially Assigned'
      }

      order.status = 'Dispatched'
      order.accountId = inv.id
      order.accountDetails = {
        email: inv.account,
        pass: inv.pass,
        platform: inv.platform,
        devicesCount: order.devices,
        profiles: deliveredProfiles,
        dispatchedAt: new Date().toLocaleString()
      }

      // Al confirmar cierre y entrega, purgar el comprobante de la mini nube para liberar espacio
      if (order.hasReceipt || order.receiptStatus === 'pending_verification') {
        order.hasReceipt = false
        order.receiptStatus = 'verified_and_purged'
        order.voucher = order.voucher && order.voucher !== 'Sin comprobante adjunto'
          ? `${order.voucher} (Verificado y depurado)`
          : 'Comprobante verificado y depurado'
        deleteReceipt(order.id).catch(() => {})
      }

      const resellerStore = useResellerStore()
      resellerStore.addDeliveredOrderToClients(order)

      this.persist(DB_KEYS.INVENTORY)
      this.persist(DB_KEYS.ORDERS)
      saveStreamingOrderToFirebase(order).catch(() => {})

      const deliveryMessage = this.generateDeliveryWhatsAppMessage(order)

      return { success: true, order, deliveryMessage }
    },

    // Despacho Inteligente en 1 Clic (Auto-asignación según stock disponible)
    smartDispatchOrder(orderId) {
      const order = this.orders.find(o => o.id === orderId)
      if (!order) return { error: 'Orden no encontrada' }

      // Buscar cuenta compatible con suficientes perfiles libres
      const compatible = this.inventory.find(i => 
        i.platform === order.platform && 
        (i.profiles || []).filter(p => !p.isAssigned).length >= order.devices
      )

      if (!compatible) {
        return { 
          error: `Sin stock disponible para ${order.platform} (${order.devices} perfil/es requerido/s). Reabastece o agrega una cuenta primero.` 
        }
      }

      const freeSlots = compatible.profiles.filter(p => !p.isAssigned)
      const basePin = freeSlots[0]?.pin || String(Math.floor(1000 + Math.random() * 9000))

      const matches = (order.profileSelections && order.profileSelections.length > 0)
        ? order.profileSelections.map((req, idx) => ({
            name: req.name || `PERFIL ${idx + 1}`,
            pin: order.pinPreference === 'same' ? basePin : (freeSlots[idx]?.pin || String(Math.floor(1000 + Math.random() * 9000)))
          }))
        : Array.from({ length: order.devices }, (_, idx) => ({
            name: `PERFIL ${idx + 1}`,
            pin: order.pinPreference === 'same' ? basePin : (freeSlots[idx]?.pin || String(Math.floor(1000 + Math.random() * 9000)))
          }))

      return this.dispatchOrderWithInventory(order.id, compatible.id, matches)
    },

    // Generador de Mensaje de Entrega Oficial para WhatsApp
    generateDeliveryWhatsAppMessage(order) {
      if (!order || !order.accountDetails) return ''
      const details = order.accountDetails
      const profilesText = (details.profiles || []).map((p, i) => `  👤 *Perfil ${i + 1}:* ${p.name} (PIN: 🔒 ${p.pin})`).join('\n')
      
      return `🎉 *¡Tu pedido en StreamePro ha sido ENTREGADO con éxito!* 🍿🎬\n\n` +
        `📦 *Orden:* #${order.id} | *Cliente:* ${order.customerName} (${order.customerNumber})\n` +
        `📺 *Servicio:* ${order.platform} - ${order.planLabel || order.devices + ' Pantalla(s)'}\n\n` +
        `🔐 *Credenciales Oficiales:*\n` +
        `📧 *Usuario / Email:* ${details.email}\n` +
        `🔑 *Contraseña:* ${details.pass}\n\n` +
        `👥 *Tus Perfiles & PINs asignados:*\n` +
        `${profilesText}\n\n` +
        `⏳ *Garantía & Vigencia:* 30 días con soporte prioritario.\n\n` +
        `🛡️ *Consulta tu Bóveda Privada de Accesos en cualquier momento:*\n` +
        `👉 https://streamepro.me/boveda\n` +
        `(Ingresa con tu número ${order.customerPhone} o tu código ${order.customerNumber})\n\n` +
        `¡Que disfrutes tu servicio!`
    },

    // Reabastecimiento Dinámico de Stock por Plataforma
    replenishPlatformStock(platform, count = 1) {
      const added = []
      for (let i = 0; i < count; i++) {
        const newAcc = createStarterAccountForPlatform(platform, this.inventory.length + 1 + i)
        this.inventory.push(newAcc)
        added.push(newAcc)
      }
      this.persist(DB_KEYS.INVENTORY)
      return { success: true, count, added }
    },

    // Reabastecimiento Completo de Stock Inicial
    replenishAllStarterStock() {
      const starter = getCleanStarterInventory()
      this.inventory.push(...starter)
      this.persist(DB_KEYS.INVENTORY)
      return { success: true, added: starter.length }
    },

    // Restauración Integral a Base de Datos Depurada de Producción
    async resetDatabaseToCleanSeed() {
      const res = await applyPristineDatabase()
      
      this.adminAuth = loadDb(DB_KEYS.ADMIN_AUTH, this.adminAuth)
      this.pricingCatalog = loadDb(DB_KEYS.PRICING_CATALOG, CLEAN_PRICING_CATALOG)
      this.inventory = loadDb(DB_KEYS.INVENTORY, [])
      this.resellers = loadDb(DB_KEYS.RESELLERS, [])
      this.resellerApplications = []
      this.orders = []
      this.physicalOrders = []
      this.packages = loadDb(DB_KEYS.PACKAGES, [])
      this.providerBankAccounts = loadDb(DB_KEYS.PROVIDER_BANK_ACCOUNTS, [])
      this.providerBankInfo = loadDb(DB_KEYS.PROVIDER_BANK, this.providerBankInfo)
      this.bankTransfers = []
      this.walletTransactions = []
      this.resellerAlerts = []
      this.renewalRequests = []
      this.ensurePhysicalProductsLoaded()
      
      const resellerStore = useResellerStore()
      resellerStore.clients = []
      resellerStore.wallet = 50000
      resellerStore.persist()
      
      return res
    },

    // Administrar Revendedores desde el Panel Proveedor
    addReseller(resellerData) {
      const newId = `RES-${String(this.resellers.length + 1).padStart(2, '0')}`
      const baseUsername = resellerData.username?.trim().toLowerCase() || `distribuidor${this.resellers.length + 1}`
      const basePassword = resellerData.password?.trim() || `Stream${Math.floor(1000 + Math.random() * 9000)}*`
      
      const newReseller = {
        id: newId,
        name: resellerData.name?.trim(),
        storeName: resellerData.storeName?.trim() || resellerData.name?.trim(),
        username: baseUsername,
        password: basePassword,
        isActive: resellerData.isActive !== false,
        contactNumber: resellerData.contactNumber?.trim() || '+54 9 11 0000-0000',
        cbuAlias: resellerData.cbuAlias?.trim() || 'DISTRIBUIDOR.ALIAS.MP',
        bankName: resellerData.bankName?.trim() || 'Banco / Billetera Virtual',
        holderName: resellerData.holderName?.trim() || resellerData.name?.trim(),
        cuit: resellerData.cuit?.trim() || '20-00000000-0',
        balance: Number(resellerData.balance || 0),
        walletHistory: []
      }

      this.resellers.push(newReseller)
      this.persist(DB_KEYS.RESELLERS)
      return newReseller
    },

    updateReseller(resellerId, updatedData) {
      const res = this.resellers.find(r => r.id === resellerId)
      if (res) {
        Object.assign(res, updatedData)
        this.persist(DB_KEYS.RESELLERS)
        return { success: true, reseller: res }
      }
      return { error: 'Revendedor no encontrado' }
    },

    deleteReseller(resellerId) {
      const idx = this.resellers.findIndex(r => r.id === resellerId)
      if (idx !== -1) {
        this.resellers.splice(idx, 1)
        this.persist(DB_KEYS.RESELLERS)
        return { success: true }
      }
      return { error: 'Revendedor no encontrado' }
    },

    updateResellerAuth(resellerId, { username, password, isActive }) {
      const res = this.resellers.find(r => r.id === resellerId)
      if (res) {
        if (username) res.username = username.trim().toLowerCase()
        if (password) res.password = password.trim()
        if (isActive !== undefined) res.isActive = Boolean(isActive)
        
        const resellerStore = useResellerStore()
        if (resellerStore.activeResellerId === resellerId && !res.isActive) {
          resellerStore.logout()
        }
        this.persist(DB_KEYS.RESELLERS)
        return { success: true, reseller: res }
      }
      return { error: 'Revendedor no encontrado' }
    },

    toggleResellerStatus(resellerId) {
      const res = this.resellers.find(r => r.id === resellerId)
      if (res) {
        res.isActive = !res.isActive
        const resellerStore = useResellerStore()
        if (resellerStore.activeResellerId === resellerId && !res.isActive) {
          resellerStore.logout()
        }
        this.persist(DB_KEYS.RESELLERS)
        return { success: true, isActive: res.isActive, name: res.name }
      }
      return null
    },

    rechargeResellerWallet(resellerId, amount, reason = 'Recarga autorizada por Administración', receiptNumber = '') {
      const res = this.resellers.find(r => r.id === resellerId)
      if (res) {
        const numAmount = Number(amount)
        res.balance = (res.balance || 0) + numAmount
        const receiptCode = receiptNumber || `REC-${Date.now().toString().slice(-6)}`
        const dateStr = new Date().toLocaleString([], { month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' })
        
        const tx = {
          id: `WT-${Date.now().toString().slice(-4)}`,
          resellerId,
          resellerName: res.name,
          amount: numAmount,
          reason,
          receipt: receiptCode,
          date: dateStr,
          balanceAfter: res.balance
        }

        if (!res.walletHistory) res.walletHistory = []
        res.walletHistory.unshift(tx)
        this.walletTransactions.unshift(tx)

        const resellerStore = useResellerStore()
        if (resellerStore.activeResellerId === resellerId) {
          resellerStore.wallet = res.balance
        }

        this.sendAlertToReseller({
          resellerId,
          clientId: null,
          clientNumber: 'BILLETERA',
          clientName: res.name,
          type: 'general',
          title: `Carga de Saldo en Billetera: +$${numAmount} ARS`,
          message: `El Administrador acreditó $${numAmount} ARS a tu Billetera (Comprobante: ${receiptCode}). Motivo: ${reason}. Saldo actual: $${res.balance} ARS.`
        })

        this.persist(DB_KEYS.RESELLERS)
        this.persist(DB_KEYS.WALLET_TRANSACTIONS)
        return { success: true, newBalance: res.balance, tx }
      }
      return { error: 'Revendedor no encontrado' }
    },

    sendAlertToReseller({ resellerId, clientId, clientNumber, clientName, title, message, type = 'general' }) {
      this.resellerAlerts.unshift({
        id: `ALT-${Date.now().toString().slice(-4)}`,
        resellerId,
        clientId,
        clientNumber,
        clientName,
        type,
        title,
        message,
        date: new Date().toLocaleString([], { month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' }),
        read: false,
        statusText: 'YA FUE AVISADO'
      })
      this.persist(DB_KEYS.RESELLER_ALERTS)
    },

    dismissAlert(alertId) {
      const idx = this.resellerAlerts.findIndex(a => a.id === alertId)
      if (idx !== -1) {
        this.resellerAlerts.splice(idx, 1)
        this.persist(DB_KEYS.RESELLER_ALERTS)
        return true
      }
      return false
    },

    clearResellerAlerts(resellerId = null) {
      if (!resellerId || resellerId === 'All') {
        this.resellerAlerts = []
      } else {
        this.resellerAlerts = this.resellerAlerts.filter(a => a.resellerId !== resellerId)
      }
      this.persist(DB_KEYS.RESELLER_ALERTS)
    },

    broadcastPasswordUpdateToActiveClients(accountEmail, newPassword, reason = 'Actualización preventiva de seguridad') {
      const resellerStore = useResellerStore()
      let updatedCount = 0
      
      resellerStore.clients.forEach(client => {
        if (client.account.toLowerCase() === accountEmail.toLowerCase() && client.status === 'Activa') {
          client.pass = newPassword
          client.notes = `Contraseña actualizada por Proveedor el ${new Date().toLocaleDateString()}. Motivo: ${reason}`
          updatedCount++
          
          this.sendAlertToReseller({
            resellerId: client.resellerId,
            clientId: client.id,
            clientNumber: client.clientNumber,
            clientName: client.name,
            type: 'password_update',
            title: `Nueva Contraseña para ${client.name} (${client.clientNumber})`,
            message: `El Proveedor actualizó la contraseña para la cuenta ${client.account}. Nueva clave: ${newPassword}. Suscripción Activa vinculada.`,
          })
        }
      })
      
      resellerStore.persist()
      return updatedCount
    },

    // Solicitud de Actualización de Hogar Netflix
    requestHogarUpdate({ account, profileName, clientName, clientNumber, tvModel = '', phone = '', resellerId = 'DIRECT' }) {
      const alertId = `HOG-${Date.now().toString().slice(-4)}`
      this.resellerAlerts.unshift({
        id: alertId,
        resellerId: resellerId || 'DIRECT',
        clientNumber: clientNumber || 'CLI',
        clientName: clientName || 'Cliente',
        type: 'hogar_netflix',
        title: '🏠 Solicitud de Hogar Netflix',
        message: `El cliente solicitó actualización de Hogar con Netflix para la cuenta ${account} (Perfil: ${profileName || 'Principal'}${tvModel ? `, TV: ${tvModel}` : ''}). Contacto: ${phone || 'No especificado'}.`,
        date: new Date().toLocaleString([], { month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' }),
        read: false,
        urgent: true
      })
      this.persist(DB_KEYS.RESELLER_ALERTS)
      return { success: true, id: alertId }
    },

    // CREAR PEDIDO STREAMING DIGITAL (DIRECTO O POR REVENDEDOR)
    async createStreamingOrder(orderData) {
      const isDirect = !orderData.resellerId || orderData.resellerId === 'DIRECT'
      const resellerObj = isDirect ? null : this.resellers.find(r => r.id === orderData.resellerId)
      
      const newOrder = {
        id: orderData.id || `ORD-${Date.now().toString().slice(-4)}`,
        customerNumber: orderData.customerNumber || `CLI-${Math.floor(1000 + Math.random() * 9000)}`,
        customerName: orderData.customerName || 'Cliente',
        customerPhone: orderData.customerPhone || '',
        pin: orderData.pin || null,
        platform: orderData.platform,
        activationEmail: orderData.activationEmail || null,
        devices: orderData.devices || 1,
        pinPreference: orderData.pinPreference || 'same',
        planKey: orderData.planKey,
        planLabel: orderData.planLabel,
        providerCost: orderData.providerCost || 0,
        resellerProfit: isDirect ? 0 : (orderData.resellerProfit || 0),
        clientPrice: orderData.clientPrice || 0,
        resellerId: isDirect ? 'DIRECT' : orderData.resellerId,
        resellerName: isDirect ? 'StreamePro Oficial (Venta Directa)' : (resellerObj?.name || orderData.resellerName || 'Distribuidor Asignado'),
        voucher: orderData.voucher || 'Sin comprobante adjunto',
        hasReceipt: Boolean(orderData.hasReceipt),
        receiptFileName: orderData.receiptFileName || null,
        receiptFileType: orderData.receiptFileType || null,
        receiptFileSize: orderData.receiptFileSize || 0,
        receiptStatus: orderData.hasReceipt ? 'pending_verification' : 'no_receipt',
        paymentMethod: orderData.paymentMethod || 'Transferencia Bancaria',
        profileSelections: (orderData.profileSelections || []).map(p => ({
          name: p.name
        })),
        status: 'Waiting Provider',
        createdAt: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
        createdDate: new Date().toISOString()
      }

      this.orders.unshift(newOrder)
      this.persist(DB_KEYS.ORDERS)

      // Guardar en la nube (Firebase Firestore) con AWAIT garantizado
      try {
        await saveStreamingOrderToFirebase(newOrder)
      } catch (e) {
        console.warn('[Firebase] Error guardando streaming order:', e)
      }

      // Registrar o actualizar automáticamente al cliente en el Directorio de Usuarios
      this.registerOrUpdateUser({
        name: newOrder.customerName,
        phone: newOrder.customerPhone,
        clientNumber: newOrder.customerNumber,
        pin: orderData.pin,
        resellerId: newOrder.resellerId,
        amount: newOrder.clientPrice,
        role: 'client',
        isNewOrder: true
      })

      // Alertar al revendedor solo si la venta provino de su enlace
      if (!isDirect && resellerObj) {
        this.sendAlertToReseller({
          resellerId: resellerObj.id,
          clientId: null,
          clientNumber: newOrder.customerNumber,
          clientName: newOrder.customerName,
          type: 'general',
          title: `Nuevo Pedido Streaming (${newOrder.platform})`,
          message: `El cliente ${newOrder.customerName} realizó un pedido de ${newOrder.platform} (${newOrder.planLabel}). Ganancia referida: $${newOrder.resellerProfit.toLocaleString()} ARS. En proceso de despacho por Proveedor.`
        })
      }

      return { success: true, order: newOrder }
    },

    // PEDIDOS FÍSICOS
    async createPhysicalOrder({ customerNumber, customerName, customerPhone, pin = null, shippingAddress, resellerId, source = 'store', items, paymentMethod = 'Transferencia Bancaria', voucher = '', hasReceipt = false, receiptFileName = null, receiptFileType = null, receiptFileSize = 0 }) {
      const isDirect = !resellerId || resellerId === 'DIRECT'
      const targetResellerId = isDirect ? 'DIRECT' : resellerId
      const resellerObj = isDirect ? null : this.resellers.find(r => r.id === targetResellerId)
      const isSharedUrl = !isDirect && source === 'shared_url'
      const commissionRate = isDirect ? 0 : (isSharedUrl ? 0.03 : 0.01)
      
      const totalAmount = items.reduce((sum, item) => sum + ((item.clientPrice || item.priceNumeric || 0) * item.quantity), 0)
      const commissionAmount = isDirect ? 0 : Math.round(totalAmount * commissionRate)
      const orderId = `PED-FIS-${Date.now().toString().slice(-4)}`

      const newOrder = {
        id: orderId,
        customerNumber: customerNumber || `CLI-${Math.floor(1000 + Math.random() * 9000)}`,
        customerName: customerName || 'Cliente',
        customerPhone: customerPhone || OFFICIAL_PROVIDER_WHATSAPP,
        shippingAddress: shippingAddress || 'Retiro en punto acordado / Envío a coordinar',
        resellerId: targetResellerId,
        resellerName: isDirect ? 'StreamePro Tienda Oficial (Venta Directa)' : (resellerObj?.name || 'Distribuidor Asignado'),
        source: isDirect ? 'direct_store' : source,
        commissionRate,
        commissionAmount,
        totalAmount,
        paymentMethod,
        hasReceipt: Boolean(hasReceipt),
        receiptFileName: receiptFileName || null,
        receiptFileType: receiptFileType || null,
        receiptFileSize: receiptFileSize || 0,
        receiptStatus: hasReceipt ? 'pending_verification' : 'no_receipt',
        voucher: voucher || 'Sin comprobante adjunto',
        status: 'Pendiente',
        createdAt: new Date().toLocaleString([], { month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' }),
        items: items.map(i => ({
          id: i.id,
          name: i.name,
          sku: i.sku || '',
          variantName: i.variantName || '',
          variantId: i.variantId || null,
          price: i.priceFormatted || i.price || `$${i.clientPrice || i.priceNumeric}`,
          priceNumeric: i.clientPrice || i.priceNumeric,
          image: i.image,
          quantity: i.quantity
        }))
      }

      newOrder.pin = pin || null
      this.physicalOrders.unshift(newOrder)
      this.persist(DB_KEYS.PHYSICAL_ORDERS)

      // Guardar en la nube (Firebase Firestore) con AWAIT garantizado
      try {
        await savePhysicalOrderToFirebase(newOrder)
      } catch (e) {
        console.warn('[Firebase] Error guardando physical order:', e)
      }

      // Registrar o actualizar automáticamente al cliente en el Directorio de Usuarios
      this.registerOrUpdateUser({
        name: newOrder.customerName,
        phone: newOrder.customerPhone,
        clientNumber: newOrder.customerNumber,
        pin: pin,
        address: newOrder.shippingAddress,
        resellerId: targetResellerId,
        amount: totalAmount,
        role: 'client',
        isNewOrder: true
      })

      if (!isDirect && resellerObj) {
        this.sendAlertToReseller({
          resellerId: targetResellerId,
          clientId: null,
          clientNumber: newOrder.customerNumber,
          clientName: newOrder.customerName,
          type: 'general',
          title: `Nuevo Pedido Físico Pendiente (${isSharedUrl ? '3% Link Compartido' : '1% Tienda'})`,
          message: `El cliente ${newOrder.customerName} realizó el pedido ${orderId} por $${totalAmount.toLocaleString()} ARS. Comisión estimada: +$${commissionAmount.toLocaleString()} ARS (${commissionRate * 100}%). En espera de aprobación por Administración.`
        })
      }

      this.persist(DB_KEYS.PHYSICAL_ORDERS)
      return { success: true, order: newOrder }
    },

    markPhysicalOrderReceived(orderId) {
      const order = this.physicalOrders.find(o => o.id === orderId)
      if (!order) return { error: 'Pedido no encontrado' }

      order.status = 'Recibido'
      order.receivedAt = new Date().toLocaleString([], { month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' })
      order.statusNote = 'Un vendedor oficial de la tienda física se comunicará para continuar con el pedido y asesorarte en stock y datos de pago.'

      this.sendAlertToReseller({
        resellerId: order.resellerId,
        clientId: null,
        clientNumber: order.customerNumber,
        clientName: order.customerName,
        type: 'general',
        title: `Pedido Físico Recibido (${order.id})`,
        message: `El pedido ${order.id} de ${order.customerName} pasó a RECIBIDO. Un vendedor oficial se pondrá en contacto directo para verificar stock y coordinar el pago/envío.`
      })

      this.persist(DB_KEYS.PHYSICAL_ORDERS)
      savePhysicalOrderToFirebase(order).catch(() => {})
      return { success: true, order }
    },

    confirmPhysicalOrder(orderId) {
      const order = this.physicalOrders.find(o => o.id === orderId)
      if (!order) return { error: 'Pedido no encontrado' }
      if (order.status === 'Confirmado' || order.status === 'Aprobado') {
        return { error: 'El pedido ya se encuentra confirmado' }
      }

      order.status = 'Confirmado'
      order.confirmedAt = new Date().toLocaleString([], { month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' })
      order.statusNote = 'Compra confirmada por el Administrador. Pedido en proceso de entrega y comisión disponible en billetera.'

      const reseller = this.resellers.find(r => r.id === order.resellerId)
      if (reseller && order.commissionAmount > 0) {
        reseller.balance = (reseller.balance || 0) + order.commissionAmount
        const receiptCode = `COM-${order.id}`
        const dateStr = new Date().toLocaleString([], { month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' })

        const tx = {
          id: `WT-${Date.now().toString().slice(-4)}`,
          resellerId: reseller.id,
          resellerName: reseller.name,
          amount: order.commissionAmount,
          reason: `Comisión ${order.commissionRate * 100}% por Venta Física Confirmada (${order.id})`,
          receipt: receiptCode,
          date: dateStr,
          balanceAfter: reseller.balance
        }

        if (!reseller.walletHistory) reseller.walletHistory = []
        reseller.walletHistory.unshift(tx)
        this.walletTransactions.unshift(tx)

        const resellerStore = useResellerStore()
        if (resellerStore.activeResellerId === reseller.id) {
          resellerStore.wallet = reseller.balance
        }

        this.sendAlertToReseller({
          resellerId: reseller.id,
          clientId: null,
          clientNumber: order.customerNumber,
          clientName: order.customerName,
          type: 'general',
          title: `¡Comisión Acreditada! +$${order.commissionAmount.toLocaleString()} ARS`,
          message: `El Administrador CONFIRMÓ la compra física ${order.id}. Se acreditaron $${order.commissionAmount.toLocaleString()} ARS a tu Billetera como saldo disponible. Saldo actual: $${reseller.balance.toLocaleString()} ARS.`
        })

        this.persist(DB_KEYS.RESELLERS)
        this.persist(DB_KEYS.WALLET_TRANSACTIONS)
      }

      // Al confirmar cierre del pedido físico, purgar el comprobante de la mini nube para liberar espacio
      if (order.hasReceipt || order.receiptStatus === 'pending_verification') {
        order.hasReceipt = false
        order.receiptStatus = 'verified_and_purged'
        order.voucher = order.voucher && order.voucher !== 'Sin comprobante adjunto'
          ? `${order.voucher} (Verificado y depurado)`
          : 'Comprobante verificado y depurado'
        deleteReceipt(order.id).catch(() => {})
      }

      this.persist(DB_KEYS.PHYSICAL_ORDERS)
      savePhysicalOrderToFirebase(order).catch(() => {})
      return { success: true, order }
    },

    // Purga definitiva de comprobante desde la interfaz para liberar almacenamiento
    async purgeOrderReceipt(orderId, isPhysical = false) {
      if (isPhysical) {
        const pOrder = this.physicalOrders.find(o => o.id === orderId)
        if (pOrder) {
          pOrder.hasReceipt = false
          pOrder.receiptStatus = 'verified_and_purged'
          pOrder.voucher = pOrder.voucher && pOrder.voucher !== 'Sin comprobante adjunto'
            ? `${pOrder.voucher} (Depurado)`
            : 'Comprobante verificado y depurado'
          this.persist(DB_KEYS.PHYSICAL_ORDERS)
        }
      } else {
        const order = this.orders.find(o => o.id === orderId)
        if (order) {
          order.hasReceipt = false
          order.receiptStatus = 'verified_and_purged'
          order.voucher = order.voucher && order.voucher !== 'Sin comprobante adjunto'
            ? `${order.voucher} (Depurado)`
            : 'Comprobante verificado y depurado'
          this.persist(DB_KEYS.ORDERS)
        }
      }
      await deleteReceipt(orderId)
      return { success: true }
    },

    approvePhysicalOrder(orderId) {
      return this.confirmPhysicalOrder(orderId)
    },

    cancelPhysicalOrder(orderId, reason = 'Cancelado por administración o falta de stock') {
      const order = this.physicalOrders.find(o => o.id === orderId)
      if (!order) return { error: 'Pedido no encontrado' }

      order.status = 'Cancelado'
      order.cancelReason = reason
      order.cancelledAt = new Date().toLocaleString([], { month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' })

      this.sendAlertToReseller({
        resellerId: order.resellerId,
        clientId: null,
        clientNumber: order.customerNumber,
        clientName: order.customerName,
        type: 'incident',
        title: `Pedido Físico Cancelado (${order.id})`,
        message: `El Administrador canceló el pedido ${order.id} de ${order.customerName}. Motivo: ${reason}.`
      })

      this.persist(DB_KEYS.PHYSICAL_ORDERS)
      savePhysicalOrderToFirebase(order).catch(() => {})
      return { success: true, order }
    },

    cancelStreamingOrder(orderId, reason = 'Cancelado por administración') {
      const order = this.orders.find(o => o.id === orderId)
      if (!order) return { error: 'Orden no encontrada' }

      order.status = 'Cancelado'
      order.cancelReason = reason
      order.cancelledAt = new Date().toLocaleString([], { month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' })

      this.persist(DB_KEYS.ORDERS)
      saveStreamingOrderToFirebase(order).catch(() => {})
      return { success: true, order }
    },

    deleteStreamingOrder(orderId) {
      const idx = this.orders.findIndex(o => o.id === orderId)
      if (idx === -1) return { error: 'Orden no encontrada' }
      const removed = this.orders.splice(idx, 1)[0]
      this.persist(DB_KEYS.ORDERS)
      if (removed.hasReceipt || removed.receiptStatus) {
        deleteReceipt(removed.id).catch(() => {})
      }
      return { success: true }
    },

    deletePhysicalOrder(orderId) {
      const idx = this.physicalOrders.findIndex(o => o.id === orderId)
      if (idx === -1) return { error: 'Pedido no encontrado' }
      const removed = this.physicalOrders.splice(idx, 1)[0]
      this.persist(DB_KEYS.PHYSICAL_ORDERS)
      if (removed.hasReceipt || removed.receiptStatus) {
        deleteReceipt(removed.id).catch(() => {})
      }
      return { success: true }
    },

    createPackage({ title, description, badge = 'OFERTA ESPECIAL', services = [], promoPrice, createdBy = 'provider', resellerId = 'ALL' }) {
      const origPrice = services.reduce((sum, s) => sum + (Number(s.regularPrice) || 0), 0)
      const costTotal = services.reduce((sum, s) => sum + (Number(s.cost) || 0), 0)
      const finalPromo = Number(promoPrice || origPrice)
      const profit = finalPromo - costTotal
      const discountPct = origPrice > 0 ? Math.max(0, Math.round(((origPrice - finalPromo) / origPrice) * 100)) : 0

      const newPkg = {
        id: `PKG-${Date.now().toString().slice(-4)}`,
        title: title || 'Combo Especial de Streaming',
        description: description || 'Paquete exclusivo con múltiples plataformas combinadas.',
        badge: badge || (discountPct > 0 ? `🔥 ${discountPct}% OFF` : '⭐ COMBO VIP'),
        services: services.map(s => ({
          platform: s.platform,
          planKey: s.planKey || '1_perfil',
          planLabel: s.planLabel || '1 Pantalla',
          units: Number(s.units || 1),
          regularPrice: Number(s.regularPrice || 0),
          cost: Number(s.cost || 0)
        })),
        originalPrice: origPrice,
        promoPrice: finalPromo,
        providerCost: costTotal,
        resellerProfit: profit,
        discountPercent: discountPct,
        createdBy,
        resellerId: resellerId || 'ALL',
        active: true,
        createdAt: new Date().toISOString().split('T')[0]
      }

      this.packages.unshift(newPkg)
      this.persist(DB_KEYS.PACKAGES)

      if (createdBy === 'provider') {
        this.sendAlertToReseller({
          resellerId: 'All',
          clientId: null,
          clientNumber: 'OFERTAS',
          clientName: 'Novedad Mayorista',
          type: 'general',
          title: `¡Nuevo Paquete Oficial Mayorista: ${newPkg.title}!`,
          message: `El Proveedor lanzó el combo oficial ${newPkg.title}. Precio Oferta: $${finalPromo.toLocaleString()} ARS. Disponible para vender y promocionar.`
        })
      }

      return { success: true, package: newPkg }
    },

    togglePackage(pkgId) {
      const p = this.packages.find(pkg => pkg.id === pkgId)
      if (p) {
        p.active = !p.active
        this.persist(DB_KEYS.PACKAGES)
        return { success: true, active: p.active }
      }
      return { error: 'Paquete no encontrado' }
    },

    deletePackage(pkgId) {
      const idx = this.packages.findIndex(pkg => pkg.id === pkgId)
      if (idx !== -1) {
        this.packages.splice(idx, 1)
        this.persist(DB_KEYS.PACKAGES)
        return { success: true }
      }
      return { error: 'Paquete no encontrado' }
    },

    // GESTIÓN DE SOLICITUDES DE NUEVOS REVENDEDORES DESDE EL LANDING PAGE
    submitResellerApplication(applicationData) {
      const newApp = {
        id: `APP-${Date.now().toString().slice(-4)}`,
        fullName: applicationData.fullName?.trim() || '',
        whatsapp: applicationData.whatsapp?.trim() || '',
        email: applicationData.email?.trim() || '',
        city: applicationData.city?.trim() || '',
        storeName: applicationData.storeName?.trim() || '',
        experience: applicationData.experience || 'Quiero empezar desde cero',
        notes: applicationData.notes?.trim() || '',
        status: 'pending', // 'pending' | 'approved' | 'rejected'
        submittedAt: new Date().toLocaleString([], { month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' }),
        submittedDate: new Date().toISOString(),
        createdResellerId: null
      }
      this.resellerApplications.unshift(newApp)
      this.persist(DB_KEYS.RESELLER_APPLICATIONS)
      return { success: true, application: newApp }
    },

    approveResellerApplication(appId, customData = {}) {
      const app = this.resellerApplications.find(a => a.id === appId)
      if (!app) return { error: 'Solicitud no encontrada' }

      // Obtener el próximo ID numérico correlativo (RES-02, RES-03...)
      const existingNums = this.resellers
        .map(r => parseInt(r.id?.replace(/\D/g, '') || '0'))
        .filter(n => !isNaN(n))
      const nextNum = (existingNums.length > 0 ? Math.max(...existingNums) : 0) + 1
      const newResellerId = `RES-${String(nextNum).padStart(2, '0')}`

      // Generar usuario base amigable
      const baseUser = app.fullName.toLowerCase().replace(/[^a-z0-9]/g, '_').slice(0, 14) || `dist_${nextNum}`
      const initialPassword = customData.password?.trim() || `StreamePro${Math.floor(1000 + Math.random() * 9000)}*`
      const username = customData.username?.trim().toLowerCase() || baseUser

      const newReseller = {
        id: newResellerId,
        name: app.fullName,
        storeName: app.storeName?.trim() || `${app.fullName} Digital`,
        username: username,
        password: initialPassword,
        isActive: true,
        contactNumber: app.whatsapp,
        cbuAlias: customData.cbuAlias?.trim() || '',
        bankName: customData.bankName?.trim() || 'Mercado Pago / Banco',
        holderName: app.fullName,
        cuit: customData.cuit?.trim() || '',
        balance: 0,
        walletHistory: []
      }

      this.resellers.push(newReseller)
      this.persist(DB_KEYS.RESELLERS)

      app.status = 'approved'
      app.createdResellerId = newResellerId
      app.reviewedAt = new Date().toLocaleString([], { month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' })
      this.persist(DB_KEYS.RESELLER_APPLICATIONS)

      return { 
        success: true, 
        reseller: newReseller, 
        application: app,
        credentialsMessage: `🎉 ¡Felicitaciones ${newReseller.name}! Tu solicitud como distribuidor en StreamePro ha sido aprobada.\n\n` +
          `🔐 *Tus datos de acceso al Panel Oficial:*\n` +
          `🌐 *Enlace:* https://streamepro.me/crm\n` +
          `👤 *Usuario:* ${newReseller.username} (o ${newReseller.id})\n` +
          `🔑 *Contraseña:* ${newReseller.password}\n\n` +
          `📦 Ya puedes ingresar, ver los costos mayoristas, usar el Generador de Flyers y comenzar a despachar cuentas y tecnología.`
      }
    },

    rejectResellerApplication(appId) {
      const app = this.resellerApplications.find(a => a.id === appId)
      if (app) {
        app.status = 'rejected'
        app.reviewedAt = new Date().toLocaleString([], { month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' })
        this.persist(DB_KEYS.RESELLER_APPLICATIONS)
      }
      return { success: true }
    },

    deleteResellerApplication(appId) {
      this.resellerApplications = this.resellerApplications.filter(a => a.id !== appId)
      this.persist(DB_KEYS.RESELLER_APPLICATIONS)
      return { success: true }
    },

    // Resetear a Base de Datos Limpia de Producción
    resetDatabaseToCleanProduction() {
      clearDbAll()
      this.inventory = []
      this.orders = []
      this.walletTransactions = []
      this.resellerAlerts = []
      this.renewalRequests = []
      this.physicalOrders = []
      this.resellerApplications = []
      this.pricingCatalog = JSON.parse(JSON.stringify(DEFAULT_PLATFORM_PLANS))
      this.resellers = []
      this.adminAuth = {
        username: 'admin',
        password: 'StreamPro2026*Admin',
        name: 'Administrador Principal',
        whatsapp: '+54 9 11 0000-0000'
      }
      this.providerBankInfo = {
        holderName: 'StreamePro Mayorista Central',
        bankName: 'Banco / Billetera Virtual',
        cuit: '20-00000000-0',
        cbuAlias: 'STREAMEPRO.MAYORISTA',
        cbuNumber: '0000000000000000000000',
        whatsapp: '+54 9 11 0000-0000',
        email: 'pagos@streampro.com'
      }
      
      const resellerStore = useResellerStore()
      resellerStore.activeResellerId = null
      resellerStore.isAuthenticated = false
      resellerStore.sessionUser = null
      resellerStore.clients = []
      resellerStore.wallet = 0
      resellerStore.persist()

      this.persist()
      return true
    }
  }
})

export const useResellerStore = defineStore('reseller', {
  state: () => ({
    activeResellerId: null,
    isAuthenticated: false,
    sessionUser: null,
    // Base de datos de clientes y suscripciones entregadas (Limpio en producción)
    clients: loadDb(DB_KEYS.CLIENTS, []),
    wallet: 0
  }),
  getters: {
    totalProfit: (state) => {
      const resId = state.activeResellerId
      return state.clients
        .filter(c => c.resellerId === resId && c.resellerId !== 'DIRECT')
        .reduce((acc, c) => acc + (c.resellerProfit || 0), 0)
    },
    counts: (state) => {
      const resId = state.activeResellerId
      const myClients = state.clients.filter(c => c.resellerId === resId && c.resellerId !== 'DIRECT')
      return {
        total: myClients.length,
        activas: myClients.filter(c => c.status === 'Activa').length,
        porVencer: myClients.filter(c => c.status === 'Por Vencer').length,
        vencidas: myClients.filter(c => c.status === 'Vencida').length,
        enReemplazo: myClients.filter(c => c.status === 'En Reemplazo').length
      }
    },
    myClients: (state) => {
      const resId = state.activeResellerId
      return state.clients.filter(c => c.resellerId === resId && c.resellerId !== 'DIRECT')
    },
    myPhysicalOrders: (state) => {
      const adminStore = useAdminStore()
      return adminStore.physicalOrders.filter(o => o.resellerId === state.activeResellerId && o.resellerId !== 'DIRECT')
    },
    myPendingPhysicalOrders: (state) => {
      const adminStore = useAdminStore()
      return adminStore.physicalOrders.filter(o => o.resellerId === state.activeResellerId && o.resellerId !== 'DIRECT' && o.status === 'Pendiente')
    },
    myReceivedPhysicalOrders: (state) => {
      const adminStore = useAdminStore()
      return adminStore.physicalOrders.filter(o => o.resellerId === state.activeResellerId && o.resellerId !== 'DIRECT' && o.status === 'Recibido')
    },
    myConfirmedPhysicalOrders: (state) => {
      const adminStore = useAdminStore()
      return adminStore.physicalOrders.filter(o => o.resellerId === state.activeResellerId && o.resellerId !== 'DIRECT' && (o.status === 'Confirmado' || o.status === 'Aprobado'))
    },
    myApprovedPhysicalOrders: (state) => {
      const adminStore = useAdminStore()
      return adminStore.physicalOrders.filter(o => o.resellerId === state.activeResellerId && o.resellerId !== 'DIRECT' && (o.status === 'Confirmado' || o.status === 'Aprobado'))
    },
    myPhysicalCommissionsEarned: (state) => {
      const adminStore = useAdminStore()
      return adminStore.physicalOrders
        .filter(o => o.resellerId === state.activeResellerId && o.resellerId !== 'DIRECT' && (o.status === 'Confirmado' || o.status === 'Aprobado'))
        .reduce((sum, o) => sum + (o.commissionAmount || 0), 0)
    },
    myPackages: (state) => {
      const adminStore = useAdminStore()
      return adminStore.packages.filter(p => (p.resellerId === 'ALL' || p.resellerId === state.activeResellerId) && p.active)
    },
    myCustomPackages: (state) => {
      const adminStore = useAdminStore()
      return adminStore.packages.filter(p => p.resellerId === state.activeResellerId && p.resellerId !== 'DIRECT')
    },
    myStreamingOrders: (state) => {
      const adminStore = useAdminStore()
      return adminStore.orders.filter(o => o.resellerId === state.activeResellerId && o.resellerId !== 'DIRECT')
    },
    myPendingStreamingOrders: (state) => {
      const adminStore = useAdminStore()
      return adminStore.orders.filter(o => o.resellerId === state.activeResellerId && o.resellerId !== 'DIRECT' && o.status === 'Waiting Provider')
    },
    myIncomingOrdersWithReceipt: (state) => {
      const adminStore = useAdminStore()
      const streaming = adminStore.orders.filter(o => o.resellerId === state.activeResellerId && o.resellerId !== 'DIRECT' && (o.hasReceipt || o.receiptStatus === 'pending_verification'))
      const physical = adminStore.physicalOrders.filter(o => o.resellerId === state.activeResellerId && o.resellerId !== 'DIRECT' && (o.hasReceipt || o.receiptStatus === 'pending_verification'))
      return [...streaming, ...physical]
    }
  },
  actions: {
    persist() {
      saveDb(DB_KEYS.CLIENTS, this.clients)
    },

    addDeliveredOrderToClients(order) {
      const expDate = new Date()
      expDate.setDate(expDate.getDate() + 30)

      this.clients.unshift({
        id: `C00${this.clients.length + 1}`,
        clientNumber: order.customerNumber || `CLI-${Math.floor(1000 + Math.random() * 9000)}`,
        name: order.customerName,
        phone: order.customerPhone || '+54 9 11 0000-0000',
        pin: order.pin || null,
        account: order.accountDetails?.email || 'cuenta.oficial@streampro.com',
        pass: order.accountDetails?.pass || 'Stream2026*',
        platform: order.platform,
        resellerId: order.resellerId || this.activeResellerId,
        planName: order.planLabel || `${order.devices} Pantalla(s)`,
        clientPaid: order.clientPrice,
        resellerProfit: order.resellerProfit,
        providerCost: order.providerCost,
        status: 'Activa',
        expiry: expDate.toISOString().split('T')[0],
        devices: order.devices,
        profiles: order.accountDetails?.profiles || [],
        notes: `Entregada automáticamente desde orden ${order.id}.`
      })
      this.persist()
    },

    topUpWallet(amount, reason = 'Carga de saldo a favor / Devolución') {
      this.wallet += Number(amount)
      const adminStore = useAdminStore()
      const res = adminStore.resellers.find(r => r.id === this.activeResellerId)
      if (res) {
        res.balance = this.wallet
        adminStore.persist(DB_KEYS.RESELLERS)
      }
    },

    reportFailureNoReward(clientId) {
      const client = this.clients.find(c => c.id === clientId)
      if (client) {
        client.status = 'En Reemplazo'
        client.notes = `Caída reportada el ${new Date().toLocaleDateString()}. En espera de solución.`
        this.persist()
      }
    },

    confirmRenewalUpdate(renewalId) {
      const adminStore = useAdminStore()
      const req = adminStore.renewalRequests.find(r => r.id === renewalId)
      if (req) {
        req.status = 'Confirmed By Reseller'
        const client = this.clients.find(c => c.id === req.clientId)
        if (client) {
          client.account = req.newCredentials.account
          client.pass = req.newCredentials.pass
          client.profiles = req.newCredentials.profiles
          client.status = 'Activa'
          const date = new Date()
          date.setDate(date.getDate() + 30)
          client.expiry = date.toISOString().split('T')[0]
          client.notes = `Renovación confirmada con nuevas credenciales el ${new Date().toLocaleDateString()}.`
          this.persist()
        }
        adminStore.persist(DB_KEYS.RENEWAL_REQUESTS)
      }
    },

    renewClient(clientId, days = 30) {
      const client = this.clients.find(c => c.id === clientId)
      if (client) {
        client.status = 'Activa'
        const date = new Date()
        date.setDate(date.getDate() + days)
        client.expiry = date.toISOString().split('T')[0]
        this.persist()
      }
    },

    updateClient(clientId, data) {
      const client = this.clients.find(c => c.id === clientId)
      if (client) {
        Object.assign(client, data)
        this.persist()
      }
    },

    deleteClient(clientId) {
      const idx = this.clients.findIndex(c => c.id === clientId)
      if (idx !== -1) {
        this.clients.splice(idx, 1)
        this.persist()
        return { success: true }
      }
      return { error: 'Cliente no encontrado' }
    },

    requestManualPurchase({ platform, planKey, units, customerName, customerPhone, pinPreference = 'same', profiles = [], payWithWallet = false }) {
      const adminStore = useAdminStore()
      const catalog = adminStore.pricingCatalog[platform]
      if (!catalog) return { error: 'Plataforma no válida' }

      const plan = catalog.plans[planKey]
      if (!plan) return { error: 'Plan no válido' }

      if (payWithWallet) {
        if (this.wallet < plan.providerCost) {
          return { error: `Saldo insuficiente en Billetera ($${this.wallet} ARS). Costo mayorista: $${plan.providerCost} ARS` }
        }
        this.wallet -= plan.providerCost
        const curRes = adminStore.resellers.find(r => r.id === this.activeResellerId)
        if (curRes) {
          curRes.balance = this.wallet
          adminStore.persist(DB_KEYS.RESELLERS)
        }
      }

      const customerNumber = `CLI-${Math.floor(1000 + Math.random() * 9000)}`
      const orderId = `ORD-MAN-${Date.now().toString().slice(-4)}`

      const newOrder = {
        id: orderId,
        customerNumber,
        customerName: customerName || `Cliente de ${this.activeResellerId}`,
        customerPhone: customerPhone || OFFICIAL_PROVIDER_WHATSAPP,
        platform,
        devices: Number(units || plan.units),
        pinPreference,
        planKey,
        planLabel: plan.label,
        providerCost: plan.providerCost,
        resellerProfit: plan.resellerProfit,
        clientPrice: plan.clientPrice,
        resellerId: this.activeResellerId,
        resellerName: adminStore.resellers.find(r => r.id === this.activeResellerId)?.name || 'Revendedor Autorizado',
        paymentMethod: payWithWallet ? 'Saldo Billetera Revendedor' : 'Transferencia Bancaria a Proveedor',
        voucher: payWithWallet ? 'Debitado de Billetera Virtual' : 'Pendiente Verificación Proveedor',
        profileSelections: profiles.length > 0 ? profiles : Array.from({ length: Number(units || plan.units) }, (_, i) => ({
          name: `PERFIL ${i + 1}`
        })),
        status: 'Waiting Provider',
        createdAt: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
      }

      adminStore.orders.unshift(newOrder)
      adminStore.persist(DB_KEYS.ORDERS)
      return { success: true, order: newOrder }
    },

    markAlertAsRead(alertId) {
      const adminStore = useAdminStore()
      const alert = adminStore.resellerAlerts.find(a => a.id === alertId)
      if (alert) {
        alert.read = true
        adminStore.persist(DB_KEYS.RESELLER_ALERTS)
      }
    },

    dismissAlert(alertId) {
      const adminStore = useAdminStore()
      return adminStore.dismissAlert(alertId)
    },

    clearAllAlerts() {
      const adminStore = useAdminStore()
      return adminStore.clearResellerAlerts(this.activeResellerId)
    },

    login(username, password) {
      const adminStore = useAdminStore()
      const u = username.trim().toLowerCase()
      const p = password.trim()
      
      const found = adminStore.resellers.find(r => 
        (r.username?.toLowerCase() === u || r.id?.toLowerCase() === u) &&
        (r.password || '').trim() === p
      )

      if (!found) {
        return { error: 'Usuario o contraseña incorrectos. Verifica tus credenciales otorgadas por Administración.' }
      }

      if (found.isActive === false) {
        return { error: 'Acceso denegado: Tu cuenta de distribuidor ha sido deshabilitada por Administración.' }
      }

      this.isAuthenticated = true
      this.activeResellerId = found.id
      this.sessionUser = { ...found }
      this.wallet = found.balance || 0
      return { success: true, reseller: found }
    },

    createResellerPackage(packageData) {
      const adminStore = useAdminStore()
      return adminStore.createPackage({
        ...packageData,
        createdBy: 'reseller',
        resellerId: this.activeResellerId
      })
    },

    logout() {
      this.isAuthenticated = false
      this.sessionUser = null
    }
  }
})

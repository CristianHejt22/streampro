// Servicio de Semilla y Restauración de Base de Datos Depurada para Producción (StreamePro)
// Provee una estructura inicial inmaculada para todas las dependencias del ecosistema.

import { DB_KEYS, saveDb, loadDb } from './db'
import physicalCategoriesData from '../assets/physicalCategories.json'
import physicalProductsData from '../assets/physicalProducts.json'
import { clearAllReceipts } from './receiptCloudStorage'

export const CLEAN_PRICING_CATALOG = {
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
  }
}

// Generador de 1 Cuenta Maestra Inicial por Plataforma para Stock Inmediato
export function createStarterAccountForPlatform(platform, index = 1) {
  const profileCounts = {
    Netflix: 4,
    Max: 4,
    'Disney+': 4,
    Spotify: 4,
    IPTV: 3,
    'Prime Video': 3,
    'YouTube Premium': 5
  }

  const costs = {
    Netflix: 18500,
    Max: 14000,
    'Disney+': 15000,
    Spotify: 9000,
    IPTV: 9000,
    'Prime Video:': 7000,
    'YouTube Premium': 8000
  }

  const maxProfiles = profileCounts[platform] || 4
  const generatedProfiles = Array.from({ length: maxProfiles }, (_, i) => ({
    id: `prof_${platform.toLowerCase().replace(/[^a-z0-9]/g, '')}_${index}_${i + 1}`,
    name: `PERFIL ${i + 1}`,
    pin: String(Math.floor(1000 + Math.random() * 9000)),
    isAssigned: false,
    assignedTo: null
  }))

  const cleanPrefix = platform.toLowerCase().replace(/[^a-z0-9]/g, '')
  const randomSuffix = Math.floor(100 + Math.random() * 900)

  return {
    id: `INV-${Date.now().toString().slice(-4)}${index}`,
    platform: platform,
    account: `${cleanPrefix}.stock${randomSuffix}@streampro.me`,
    pass: `StreamePro${Math.floor(1000 + Math.random() * 9000)}*`,
    cost: costs[platform] || 10000,
    maxProfiles: maxProfiles,
    status: 'Available',
    profiles: generatedProfiles,
    createdAt: new Date().toISOString()
  }
}

export function getCleanStarterInventory() {
  const platforms = ['Netflix', 'Max', 'Disney+', 'Spotify', 'IPTV', 'Prime Video', 'YouTube Premium']
  return platforms.map((plat, idx) => createStarterAccountForPlatform(plat, idx + 1))
}

export function getCleanDatabaseSeed() {
  const starterBankAccounts = [
    {
      id: 'acc_mp_principal',
      name: 'Mercado Pago Principal (Cobros)',
      isActive: true,
      holderName: 'StreamePro Mayorista Central',
      bankName: 'Mercado Pago',
      cuit: '20-00000000-0',
      cbuAlias: 'STREAMEPRO.MAYORISTA',
      cbuNumber: '0000000000000000000000',
      whatsapp: '+54 9 11 0000-0000',
      email: 'pagos@streampro.com',
      notifyEmail: 'cobros.streampro@gmail.com'
    },
    {
      id: 'acc_galicia_secundaria',
      name: 'Banco Galicia (Transferencias)',
      isActive: false,
      holderName: 'StreamePro Mayorista Central',
      bankName: 'Banco Galicia',
      cuit: '20-00000000-0',
      cbuAlias: 'STREAMEPRO.GALICIA',
      cbuNumber: '0070000000000000000000',
      whatsapp: '+54 9 11 0000-0000',
      email: 'pagos@streampro.com',
      notifyEmail: 'cobros.streampro@gmail.com'
    }
  ]

  const starterReseller = [
    {
      id: 'RES-01',
      name: 'Distribuidor Central Oficial',
      storeName: 'StreamePro Digital Store',
      username: 'dist_oficial',
      password: 'StreamPro2026*Dist',
      isActive: true,
      contactNumber: '+54 9 11 0000-0000',
      cbuAlias: 'STREAMEPRO.DIST.MP',
      bankName: 'Mercado Pago',
      holderName: 'Distribuidor Central Oficial',
      cuit: '20-00000000-0',
      balance: 50000,
      walletHistory: []
    }
  ]

  const cleanPhysicalProducts = physicalProductsData.map(p => ({
    ...p,
    image: p.image?.startsWith('http') ? p.image : `https://tua.ar/${p.image}`,
    priceNumeric: parseInt(p.price?.replace(/[$. ]/g, '').replace(',', '') || '0') / 100 || 0,
    stock: p.stock || 20
  }))

  const cleanPhysicalCategories = physicalCategoriesData.filter(c => parseInt(c.cantidad || '0') > 0)

  const starterPackages = [
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
      createdAt: new Date().toISOString().split('T')[0]
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
      createdAt: new Date().toISOString().split('T')[0]
    }
  ]

  return {
    [DB_KEYS.ADMIN_AUTH]: {
      username: 'admin',
      password: 'StreamPro2026*Admin',
      name: 'Administrador Principal',
      whatsapp: '+54 9 11 0000-0000',
      email: 'admin@streampro.com'
    },
    [DB_KEYS.PRICING_CATALOG]: CLEAN_PRICING_CATALOG,
    [DB_KEYS.INVENTORY]: getCleanStarterInventory(),
    [DB_KEYS.RESELLERS]: starterReseller,
    [DB_KEYS.RESELLER_APPLICATIONS]: [],
    [DB_KEYS.ORDERS]: [],
    [DB_KEYS.CLIENTS]: [],
    [DB_KEYS.PHYSICAL_CATEGORIES]: cleanPhysicalCategories,
    [DB_KEYS.PHYSICAL_PRODUCTS]: cleanPhysicalProducts,
    [DB_KEYS.PHYSICAL_ORDERS]: [],
    [DB_KEYS.PACKAGES]: starterPackages,
    [DB_KEYS.PROVIDER_BANK_ACCOUNTS]: starterBankAccounts,
    [DB_KEYS.PROVIDER_BANK]: {
      holderName: starterBankAccounts[0].holderName,
      bankName: starterBankAccounts[0].bankName,
      cuit: starterBankAccounts[0].cuit,
      cbuAlias: starterBankAccounts[0].cbuAlias,
      cbuNumber: starterBankAccounts[0].cbuNumber,
      whatsapp: starterBankAccounts[0].whatsapp,
      email: starterBankAccounts[0].email
    },
    [DB_KEYS.BANK_TRANSFERS]: [],
    [DB_KEYS.WALLET_TRANSACTIONS]: [],
    [DB_KEYS.RESELLER_ALERTS]: [],
    [DB_KEYS.RENEWAL_REQUESTS]: []
  }
}

// Aplica la base de datos depurada completa
export async function applyPristineDatabase() {
  const seed = getCleanDatabaseSeed()
  
  for (const [key, val] of Object.entries(seed)) {
    saveDb(key, val)
  }

  // Limpiar mini-nube de comprobantes en IndexedDB
  try {
    await clearAllReceipts()
  } catch (e) {
    console.warn('[DB] Mini nube purgada:', e)
  }

  return {
    success: true,
    timestamp: new Date().toISOString(),
    keysRestored: Object.keys(seed).length
  }
}

// Obtiene un diagnóstico del estado y cantidad de registros en cada colección
export function getDatabaseHealthStatus() {
  const seed = getCleanDatabaseSeed()
  const health = {}

  Object.values(DB_KEYS).forEach(k => {
    const val = loadDb(k, null)
    let count = 0
    if (Array.isArray(val)) {
      count = val.length
    } else if (val && typeof val === 'object') {
      count = Object.keys(val).length
    } else if (val !== null && val !== undefined) {
      count = 1
    }
    health[k] = {
      isInitialized: val !== null && val !== undefined,
      count
    }
  })

  return health
}

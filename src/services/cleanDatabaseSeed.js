// Servicio de Semilla y Restauración de Base de Datos Depurada para Producción (StreamePro)
// Provee una estructura inicial inmaculada para todas las dependencias del ecosistema.

import { DB_KEYS, saveDb, loadDb } from './db'
import physicalCategoriesData from '../assets/physicalCategories.json'
import physicalProductsData from '../assets/physicalProducts.json'
import { clearAllReceipts } from './receiptCloudStorage'
import { DEFAULT_PRODUCTION_GIVEAWAYS } from '../store/admin'

export const CLEAN_PRICING_CATALOG = {
  Netflix: {
    name: 'Netflix Premium Ultra HD 4K',
    plans: {
      '1_perfil': {
        label: '1 Perfil Ultra HD 4K (1 Dispositivo)',
        units: 1,
        type: 'perfil',
        providerCost: 4300,
        resellerProfit: 3400,
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
  'Netflix Perfil Extra': {
    name: 'Netflix Perfil Extra (Sin Bloqueo)',
    plans: {
      'perfil_extra': {
        label: '1 Perfil Extra (Sin Bloqueo de Hogar)',
        units: 1,
        type: 'perfil_extra',
        providerCost: 4500,
        resellerProfit: 2000,
        clientPrice: 6500
      },
      '2_perfiles_extra': {
        label: '2 Perfiles Extra (2 Dispositivos)',
        units: 2,
        type: 'perfil_extra',
        providerCost: 9000,
        resellerProfit: 4000,
        clientPrice: 13000
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
        providerCost: 2000,
        resellerProfit: 4000,
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
        providerCost: 1500,
        resellerProfit: 2300,
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
        label: '1 Perfil individual (Premium con ESPN)',
        units: 1,
        type: 'perfil',
        providerCost: 2000,
        resellerProfit: 4500,
        clientPrice: 6500
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
        providerCost: 2000,
        resellerProfit: 4500,
        clientPrice: 6500
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
        label: '1 Perfil 4K (1 Pantalla HD)',
        units: 1,
        type: 'perfil',
        providerCost: 1500,
        resellerProfit: 3300,
        clientPrice: 4800
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
        providerCost: 1500,
        resellerProfit: 3000,
        clientPrice: 4500
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
        providerCost: 2000,
        resellerProfit: 2900,
        clientPrice: 4900
      }
    }
  },
  'Paramount+': {
    name: 'Paramount+ Plan Estándar',
    plans: {
      '1_perfil': {
        label: '1 Perfil individual (Full HD)',
        units: 1,
        type: 'perfil',
        providerCost: 1500,
        resellerProfit: 2300,
        clientPrice: 3800
      }
    }
  },
  'Crunchyroll': {
    name: 'Crunchyroll Mega Fan',
    plans: {
      '1_perfil': {
        label: '1 Perfil Mega Fan (HD / 4K)',
        units: 1,
        type: 'perfil',
        providerCost: 1500,
        resellerProfit: 2300,
        clientPrice: 3800
      }
    }
  },
  'ChatGPT Plus': {
    name: 'ChatGPT Plus IA',
    plans: {
      '1_perfil': {
        label: '1 Acceso Plus IA (GPT-4o)',
        units: 1,
        type: 'perfil',
        providerCost: 3000,
        resellerProfit: 3500,
        clientPrice: 6500
      }
    }
  },
  'CapCut Pro': {
    name: 'CapCut Pro Edición Video',
    plans: {
      '1_perfil': {
        label: '1 Cuenta Pro (1 Mes)',
        units: 1,
        type: 'perfil',
        providerCost: 1500,
        resellerProfit: 2000,
        clientPrice: 3500
      }
    }
  },
  'Viki Rakuten': {
    name: 'Viki Pass Standard',
    plans: {
      '1_perfil': {
        label: '1 Perfil Pass Standard HD',
        units: 1,
        type: 'perfil',
        providerCost: 1500,
        resellerProfit: 2300,
        clientPrice: 3800
      }
    }
  },
  'ViX': {
    name: 'ViX Premium Deportes & Novelas',
    plans: {
      '1_perfil': {
        label: '1 Perfil Premium HD',
        units: 1,
        type: 'perfil',
        providerCost: 1500,
        resellerProfit: 2300,
        clientPrice: 3800
      }
    }
  },
  'DramaBox': {
    name: 'DramaBox VIP Short Series',
    plans: {
      '1_perfil': {
        label: '1 Cuenta VIP Completa',
        units: 1,
        type: 'perfil',
        providerCost: 1500,
        resellerProfit: 2300,
        clientPrice: 3800
      }
    }
  },
  'Pornhub': {
    name: 'Pornhub Premium VIP',
    plans: {
      '1_perfil': {
        label: '1 Cuenta VIP Privada',
        units: 1,
        type: 'perfil',
        providerCost: 1500,
        resellerProfit: 2500,
        clientPrice: 4000
      }
    }
  },
  'Apple TV+': {
    name: 'Apple TV+ 4K Original',
    plans: {
      '1_perfil': {
        label: '1 Perfil individual 4K',
        units: 1,
        type: 'perfil',
        providerCost: 2000,
        resellerProfit: 2900,
        clientPrice: 4900
      }
    }
  },
  'FreeReels': {
    name: 'FreeReels VIP Drama',
    plans: {
      '1_perfil': {
        label: '1 Cuenta VIP Desbloqueada',
        units: 1,
        type: 'perfil',
        providerCost: 1500,
        resellerProfit: 2300,
        clientPrice: 3800
      }
    }
  },
  'NetShort': {
    name: 'NetShort VIP Mini Series',
    plans: {
      '1_perfil': {
        label: '1 Cuenta VIP Acceso Total',
        units: 1,
        type: 'perfil',
        providerCost: 1500,
        resellerProfit: 2300,
        clientPrice: 3800
      }
    }
  },
  'ReelShort': {
    name: 'ReelShort VIP Series',
    plans: {
      '1_perfil': {
        label: '1 Cuenta Pase VIP',
        units: 1,
        type: 'perfil',
        providerCost: 1500,
        resellerProfit: 2300,
        clientPrice: 3800
      }
    }
  }
};

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
    'Prime Video': 7000,
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
  const platforms = ['Netflix', 'Netflix Perfil Extra', 'Max', 'Disney+', 'Spotify', 'IPTV', 'Prime Video', 'YouTube Premium']
  return platforms.map((plat, idx) => createStarterAccountForPlatform(plat, idx + 1))
}

export function getCleanDatabaseSeed() {
  const starterBankAccounts = [
    {
      id: 'acc_mp_principal',
      name: 'Mercado Pago Principal (Cobros)',
      isActive: true,
      holderName: 'Cristian Hejt',
      bankName: 'Mercado Pago',
      cuit: '20-00000000-0',
      cbuAlias: 'STREAMEPRO',
      cbuNumber: '0000000000000000000000',
      whatsapp: '+54 9 291 565-8321',
      email: 'pagos@streampro.com',
      notifyEmail: 'cobros.streampro@gmail.com'
    },
    {
      id: 'acc_galicia_secundaria',
      name: 'Banco Galicia (Transferencias)',
      isActive: false,
      holderName: 'Cristian Hejt',
      bankName: 'Banco Galicia',
      cuit: '20-00000000-0',
      cbuAlias: 'STREAMEPRO.GALICIA',
      cbuNumber: '0070000000000000000000',
      whatsapp: '+54 9 291 565-8321',
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
      contactNumber: '+54 9 291 565-8321',
      cbuAlias: 'STREAMEPRO',
      bankName: 'Mercado Pago',
      holderName: 'Cristian Hejt',
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
      title: 'Combo Cine Total 4K (Max + Disney+ ESPN + Prime)',
      description: 'Las 3 plataformas líderes de series, cine y estrenos en calidad Ultra HD.',
      badge: '🔥 25% OFF - RECOMENDADO',
      services: [
        { platform: 'Prime Video', planKey: '1_perfil', planLabel: '1 Pantalla HD', units: 1, regularPrice: 4800, cost: 1500 },
        { platform: 'Max', planKey: '1_perfil', planLabel: '1 Pantalla Platino', units: 1, regularPrice: 6000, cost: 2000 },
        { platform: 'Disney+', planKey: '1_perfil', planLabel: '1 Pantalla VIP', units: 1, regularPrice: 7200, cost: 2000 }
      ],
      originalPrice: 21700,
      promoPrice: 16250,
      providerCost: 8300,
      resellerProfit: 2950,
      createdBy: 'provider',
      resellerId: 'ALL',
      active: true,
      createdAt: new Date().toISOString().split('T')[0]
    },
    {
      id: 'PKG-102',
      title: 'Dúo Series & Música (Max + Spotify)',
      description: 'Tus series favoritas en Ultra HD y toda la música del mundo sin publicidad.',
      badge: '⚡ COMBO POPULAR',
      image: '/flyers/combos/combo-default.jpg',
      services: [
        { platform: 'Max', planKey: '1_perfil', planLabel: '1 Pantalla Platino', units: 1, regularPrice: 6000, cost: 2000 },
        { platform: 'Spotify', planKey: '1_perfil', planLabel: '1 Slot Familiar', units: 1, regularPrice: 3800, cost: 1500 }
      ],
      originalPrice: 12300,
      promoPrice: 9850,
      providerCost: 5800,
      resellerProfit: 2050,
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
      whatsapp: '+54 9 291 565-8321',
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
    [DB_KEYS.RENEWAL_REQUESTS]: [],
    [DB_KEYS.GIVEAWAYS]: DEFAULT_PRODUCTION_GIVEAWAYS,
    [DB_KEYS.GIVEAWAY_ENTRIES]: [],
    [DB_KEYS.MARKETING_CLICKS]: [],
    [DB_KEYS.SEO_CONFIG]: {
      siteTitle: 'StreamePro Oficial | Streaming 4K, Juegos PS5/PS4 y Tecnología en Argentina',
      metaDescription: 'Tienda Oficial de Streaming 4K Ultra HD y Cuentas Digitales PlayStation en Argentina. Activación express 10-60m, PIN privado, garantía total y medios de pago locales.',
      keywords: 'streaming argentina, cuentas netflix argentina, juegos ps5 digitales, combos streaming, disney plus, max hbo, playstation cuentas primarias',
      canonicalUrl: 'https://streamepro.me',
      ogImage: 'https://streamepro.me/flyers/combos/plaque-template-cyber.webp'
    }
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

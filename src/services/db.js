// Servicio de Base de Datos Persistente para StreamPro (Producción)
// Almacena de forma persistente productos virtuales, inventario, revendedores, credenciales y pedidos

const STORAGE_PREFIX = 'streampro_db_prod_'

export const DB_KEYS = {
  ADMIN_AUTH: 'admin_auth',
  PRICING_CATALOG: 'pricing_catalog',
  INVENTORY: 'inventory',
  RESELLERS: 'resellers',
  ORDERS: 'orders',
  CLIENTS: 'clients',
  PACKAGES: 'packages',
  PHYSICAL_ORDERS: 'physical_orders',
  WALLET_TRANSACTIONS: 'wallet_transactions',
  RESELLER_ALERTS: 'reseller_alerts',
  RENEWAL_REQUESTS: 'renewal_requests',
  PROVIDER_BANK: 'provider_bank'
}

export function loadDb(key, defaultValue) {
  try {
    const raw = localStorage.getItem(STORAGE_PREFIX + key)
    if (raw === null || raw === undefined) {
      return defaultValue
    }
    return JSON.parse(raw)
  } catch (e) {
    console.error(`[DB] Error al cargar ${key}:`, e)
    return defaultValue
  }
}

export function saveDb(key, value) {
  try {
    localStorage.setItem(STORAGE_PREFIX + key, JSON.stringify(value))
    return true
  } catch (e) {
    console.error(`[DB] Error al guardar ${key}:`, e)
    return false
  }
}

export function clearDbAll() {
  try {
    Object.values(DB_KEYS).forEach(k => {
      localStorage.removeItem(STORAGE_PREFIX + k)
    })
    return true
  } catch (e) {
    console.error('[DB] Error al resetear base de datos:', e)
    return false
  }
}

export function exportDatabaseBackup() {
  try {
    const backup = {}
    Object.values(DB_KEYS).forEach(k => {
      backup[k] = loadDb(k, null)
    })
    return JSON.stringify(backup, null, 2)
  } catch (e) {
    console.error('[DB] Error al exportar backup:', e)
    return null
  }
}

export function importDatabaseBackup(jsonString) {
  try {
    const data = JSON.parse(jsonString)
    Object.values(DB_KEYS).forEach(k => {
      if (data[k] !== undefined) {
        saveDb(k, data[k])
      }
    })
    return true
  } catch (e) {
    console.error('[DB] Error al importar backup:', e)
    return false
  }
}

// Generador de Contraseñas Seguras para Revendedores
export function generateSecurePassword(length = 12) {
  const upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ'
  const lower = 'abcdefghijkmnopqrstuvwxyz'
  const numbers = '23456789'
  const symbols = '!@#$%&*+'
  const all = upper + lower + numbers + symbols

  let pwd = ''
  pwd += upper[Math.floor(Math.random() * upper.length)]
  pwd += lower[Math.floor(Math.random() * lower.length)]
  pwd += numbers[Math.floor(Math.random() * numbers.length)]
  pwd += symbols[Math.floor(Math.random() * symbols.length)]

  for (let i = 4; i < length; i++) {
    pwd += all[Math.floor(Math.random() * all.length)]
  }

  // Mezclar caracteres
  return pwd.split('').sort(() => 0.5 - Math.random()).join('')
}

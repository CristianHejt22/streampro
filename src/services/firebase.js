// Servicio Oficial de Sincronización en Tiempo Real con Google Firebase Firestore
import { initializeApp, getApps, getApp, deleteApp } from 'firebase/app'
import { 
  getFirestore, 
  collection, 
  addDoc, 
  setDoc,
  deleteDoc,
  getDoc,
  doc, 
  deleteField,
  query, 
  orderBy, 
  onSnapshot, 
  serverTimestamp,
  getDocs,
  limit
} from 'firebase/firestore'

// Configuración Oficial de Firebase de StreamePro
export const OFFICIAL_FIREBASE_CONFIG = {
  apiKey: "AIzaSyBOk3oDY6--EKyETxAfKzRAwasF_HAXMZM",
  authDomain: "streamepro-6f5a3.firebaseapp.com",
  projectId: "streamepro-6f5a3",
  storageBucket: "streamepro-6f5a3.firebasestorage.app",
  messagingSenderId: "55882803488",
  appId: "1:55882803488:web:dd4693a750541ce154cf45",
  measurementId: "G-DVRKEHEERZ"
}

let dbInstance = null
let currentApp = null

/**
 * Inicializa o re-inicializa Firebase con la configuración provista u oficial
 */
export function initFirebase(config = null) {
  const effectiveConfig = (config && config.apiKey && config.projectId) ? config : OFFICIAL_FIREBASE_CONFIG

  if (!effectiveConfig || !effectiveConfig.apiKey || !effectiveConfig.projectId) {
    dbInstance = null
    return null
  }

  try {
    const apps = getApps()
    // Buscar específicamente la app por defecto '[DEFAULT]', para evitar errores si existen apps de prueba
    const defaultApp = apps.find(a => a.name === '[DEFAULT]')
    currentApp = defaultApp || initializeApp(effectiveConfig)
    dbInstance = getFirestore(currentApp)
    return dbInstance
  } catch (error) {
    console.error('[Firebase] Error al inicializar:', error)
    dbInstance = null
    return null
  }
}

export function isFirebaseConfigured() {
  if (!dbInstance) {
    initFirebase()
  }
  return dbInstance !== null
}

/**
 * Guarda una orden de streaming en la colección 'orders' de Firestore
 */
export async function saveStreamingOrderToFirebase(orderData) {
  if (!dbInstance) {
    initFirebase()
  }
  if (!dbInstance) return { success: false, reason: 'Firebase no inicializado' }

  try {
    const cleanOrder = JSON.parse(JSON.stringify(orderData))
    cleanOrder.createdAtTimestamp = serverTimestamp()
    cleanOrder.sourceOrigin = 'streamepro.me'
    cleanOrder.updatedAt = new Date().toISOString()

    const orderId = String(cleanOrder.id || `ORD-${Date.now().toString().slice(-4)}`)
    await setDoc(doc(dbInstance, 'orders', orderId), cleanOrder, { merge: true })

    return { success: true, id: orderId }
  } catch (error) {
    console.error('[Firebase] Error guardando orden de streaming:', error)
    return { success: false, error: error.message }
  }
}

/**
 * Guarda una orden de productos físicos en la colección 'physical_orders' de Firestore
 */
export async function savePhysicalOrderToFirebase(orderData) {
  if (!dbInstance) {
    initFirebase()
  }
  if (!dbInstance) return { success: false, reason: 'Firebase no inicializado' }

  try {
    const cleanOrder = JSON.parse(JSON.stringify(orderData))
    cleanOrder.createdAtTimestamp = serverTimestamp()
    cleanOrder.sourceOrigin = 'streamepro.me'
    cleanOrder.updatedAt = new Date().toISOString()

    const orderId = String(cleanOrder.id || `PED-FIS-${Date.now().toString().slice(-4)}`)
    await setDoc(doc(dbInstance, 'physical_orders', orderId), cleanOrder, { merge: true })

    return { success: true, id: orderId }
  } catch (error) {
    console.error('[Firebase] Error guardando orden física:', error)
    return { success: false, error: error.message }
  }
}

/**
 * Guarda un usuario en la colección 'registered_users' de Firestore
 */
export async function saveUserToFirebase(userData) {
  if (!dbInstance) {
    initFirebase()
  }
  if (!dbInstance) return { success: false, reason: 'Firebase no inicializado' }

  try {
    const cleanUser = JSON.parse(JSON.stringify(userData))
    cleanUser.updatedAt = new Date().toISOString()
    const docId = String(cleanUser.clientNumber || cleanUser.id || `USR-${cleanUser.phone || Date.now()}`)
    await setDoc(doc(dbInstance, 'registered_users', docId), cleanUser, { merge: true })
    return { success: true, id: docId }
  } catch (error) {
    console.warn('[Firebase] Error guardando usuario:', error)
    return { success: false, error: error.message }
  }
}

/**
 * Consulta directa y única de todas las órdenes de streaming
 */
export async function fetchLatestOrdersFromFirebase() {
  if (!dbInstance) {
    initFirebase()
  }
  if (!dbInstance) return []

  try {
    const q = query(collection(dbInstance, 'orders'), limit(150))
    const snap = await getDocs(q)
    const list = []
    snap.forEach((docSnap) => {
      list.push({ id: docSnap.id, ...docSnap.data() })
    })
    return list
  } catch (error) {
    console.warn('[Firebase] Error al consultar orders:', error)
    return []
  }
}

/**
 * Consulta directa y única de todas las órdenes físicas
 */
export async function fetchLatestPhysicalOrdersFromFirebase() {
  if (!dbInstance) {
    initFirebase()
  }
  if (!dbInstance) return []

  try {
    const q = query(collection(dbInstance, 'physical_orders'), limit(150))
    const snap = await getDocs(q)
    const list = []
    snap.forEach((docSnap) => {
      list.push({ id: docSnap.id, ...docSnap.data() })
    })
    return list
  } catch (error) {
    console.warn('[Firebase] Error al consultar physical_orders:', error)
    return []
  }
}

/**
 * Consulta directa de usuarios registrados
 */
export async function fetchRegisteredUsersFromFirebase() {
  if (!dbInstance) {
    initFirebase()
  }
  if (!dbInstance) return []

  try {
    const q = query(collection(dbInstance, 'registered_users'), limit(150))
    const snap = await getDocs(q)
    const list = []
    snap.forEach((docSnap) => {
      list.push({ id: docSnap.id, ...docSnap.data() })
    })
    return list
  } catch (error) {
    console.warn('[Firebase] Error al consultar registered_users:', error)
    return []
  }
}

/**
 * Escucha cambios en tiempo real en la colección 'orders'
 */
export function listenToStreamingOrders(callback) {
  if (!dbInstance) {
    initFirebase()
  }
  if (!dbInstance) return () => {}

  try {
    const q = query(collection(dbInstance, 'orders'), limit(150))
    return onSnapshot(q, (snapshot) => {
      const orders = []
      snapshot.forEach((docSnap) => {
        orders.push({ id: docSnap.id, ...docSnap.data() })
      })
      callback(orders)
    }, (err) => {
      console.warn('[Firebase] Snapshot error en orders:', err)
    })
  } catch (err) {
    console.warn('[Firebase] Listener error:', err)
    return () => {}
  }
}

/**
 * Escucha cambios en tiempo real en la colección 'physical_orders'
 */
export function listenToPhysicalOrders(callback) {
  if (!dbInstance) {
    initFirebase()
  }
  if (!dbInstance) return () => {}

  try {
    const q = query(collection(dbInstance, 'physical_orders'), limit(150))
    return onSnapshot(q, (snapshot) => {
      const orders = []
      snapshot.forEach((docSnap) => {
        orders.push({ id: docSnap.id, ...docSnap.data() })
      })
      callback(orders)
    }, (err) => {
      console.warn('[Firebase] Snapshot error en physical_orders:', err)
    })
  } catch (err) {
    console.warn('[Firebase] Listener error:', err)
    return () => {}
  }
}

/**
 * Escucha cambios en tiempo real en la colección 'registered_users'
 */
export function listenToRegisteredUsers(callback) {
  if (!dbInstance) {
    initFirebase()
  }
  if (!dbInstance) return () => {}

  try {
    const q = query(collection(dbInstance, 'registered_users'), limit(150))
    return onSnapshot(q, (snapshot) => {
      const users = []
      snapshot.forEach((docSnap) => {
        users.push({ id: docSnap.id, ...docSnap.data() })
      })
      callback(users)
    }, (err) => {
      console.warn('[Firebase] Snapshot error en registered_users:', err)
    })
  } catch (err) {
    console.warn('[Firebase] Listener error:', err)
    return () => {}
  }
}

function convertValueToFirestoreRest(val) {
  if (val === null || val === undefined) return { nullValue: null }
  if (typeof val === 'boolean') return { booleanValue: val }
  if (typeof val === 'number') {
    return Number.isInteger(val) ? { integerValue: String(val) } : { doubleValue: val }
  }
  if (typeof val === 'string') return { stringValue: val }
  if (Array.isArray(val)) {
    return { arrayValue: { values: val.map(convertValueToFirestoreRest) } }
  }
  if (typeof val === 'object') {
    return { mapValue: { fields: convertJsToFirestoreFields(val) } }
  }
  return { stringValue: String(val) }
}

function convertJsToFirestoreFields(obj) {
  const fields = {}
  if (!obj || typeof obj !== 'object') return fields
  for (const [key, value] of Object.entries(obj)) {
    if (value !== undefined) {
      fields[key] = convertValueToFirestoreRest(value)
    }
  }
  return fields
}

function convertFirestoreValueToJs(val) {
  if (!val || typeof val !== 'object') return val
  if (val.stringValue !== undefined) return val.stringValue
  if (val.booleanValue !== undefined) return val.booleanValue
  if (val.integerValue !== undefined) return parseInt(val.integerValue, 10)
  if (val.doubleValue !== undefined) return val.doubleValue
  if (val.nullValue !== undefined) return null
  if (val.arrayValue !== undefined) {
    return (val.arrayValue.values || []).map(convertFirestoreValueToJs)
  }
  if (val.mapValue !== undefined) {
    return parseFirestoreFields(val.mapValue.fields || {})
  }
  return null
}

function parseFirestoreFields(fields) {
  const result = {}
  if (!fields || typeof fields !== 'object') return result
  for (const [key, value] of Object.entries(fields)) {
    result[key] = convertFirestoreValueToJs(value)
  }
  return result
}

export async function saveStoreSettingsViaRest(cleanSettings) {
  try {
    const projectId = OFFICIAL_FIREBASE_CONFIG.projectId
    const url = `https://firestore.googleapis.com/v1/projects/${projectId}/databases/(default)/documents/settings/store_settings`
    const body = {
      fields: convertJsToFirestoreFields(cleanSettings)
    }
    const res = await fetch(url, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body)
    })
    return res.ok
  } catch (err) {
    console.warn('[Firebase REST] Error guardando store_settings vía REST:', err)
    return false
  }
}

export async function fetchStoreSettingsViaRest() {
  try {
    const projectId = OFFICIAL_FIREBASE_CONFIG.projectId
    const url = `https://firestore.googleapis.com/v1/projects/${projectId}/databases/(default)/documents/settings/store_settings`
    const res = await fetch(url)
    if (!res.ok) return null
    const json = await res.json()
    if (!json || !json.fields) return null
    return parseFirestoreFields(json.fields)
  } catch (err) {
    console.warn('[Firebase REST] Error obteniendo store_settings vía REST:', err)
    return null
  }
}

/**
 * Guarda la configuración de visibilidad y datos bancarios de la tienda en Firestore para sincronización global
 */
export async function saveStoreSettingsToFirebase(settings) {
  if (!settings) return { success: false, reason: 'Sin datos de configuración' }

  const data = {
    isStoreVisible: typeof settings.isStoreVisible === 'boolean' ? settings.isStoreVisible : true,
    isPhysicalStoreVisible: typeof settings.isPhysicalStoreVisible === 'boolean' ? settings.isPhysicalStoreVisible : false,
    gamingStoreMarginPercent: Number(settings.gamingStoreMarginPercent ?? 20),
    gamingResellerCommissionPercent: Number(settings.gamingResellerCommissionPercent ?? 10),
    gamingMinResellerProfit: Number(settings.gamingMinResellerProfit ?? 1000),
    ...(Array.isArray(settings.promoCodes) ? { promoCodes: JSON.parse(JSON.stringify(settings.promoCodes)) } : {}),
    ...(settings.pricingCatalog && typeof settings.pricingCatalog === 'object' ? { pricingCatalog: JSON.parse(JSON.stringify(settings.pricingCatalog)) } : {}),
    ...(Array.isArray(settings.packages) ? { packages: JSON.parse(JSON.stringify(settings.packages)) } : {}),
    ...(Array.isArray(settings.gamingProviderSites) ? { gamingProviderSites: JSON.parse(JSON.stringify(settings.gamingProviderSites)) } : {}),
    ...(settings.gamingOverrides && typeof settings.gamingOverrides === 'object' ? { gamingOverrides: JSON.parse(JSON.stringify(settings.gamingOverrides)) } : {}),
    ...(settings.providerBankInfo && typeof settings.providerBankInfo === 'object' ? { providerBankInfo: JSON.parse(JSON.stringify(settings.providerBankInfo)) } : {}),
    ...(Array.isArray(settings.providerBankAccounts) ? { providerBankAccounts: JSON.parse(JSON.stringify(settings.providerBankAccounts)) } : {}),
    updatedAt: new Date().toISOString()
  }

  // 1. Intentar con Firestore SDK nativo
  if (!dbInstance) {
    initFirebase()
  }
  if (dbInstance) {
    try {
      await setDoc(doc(dbInstance, 'settings', 'store_settings'), data, { merge: true })
      return { success: true, settings: data }
    } catch (sdkError) {
      console.warn('[Firebase SDK] Error guardando store_settings, usando REST fallback:', sdkError)
    }
  }

  // 2. Fallback de alta confiabilidad vía REST API directa
  const restOk = await saveStoreSettingsViaRest(data)
  if (restOk) {
    return { success: true, settings: data }
  }

  return { success: false, reason: 'No se pudo guardar la configuración en Firebase' }
}

/**
 * Guarda una postulación de revendedor en la colección 'reseller_applications' de Firestore
 */
export async function saveResellerApplicationToFirebase(appData) {
  if (!dbInstance) {
    initFirebase()
  }
  if (!dbInstance) return { success: false, reason: 'Firebase no inicializado' }

  try {
    const cleanApp = JSON.parse(JSON.stringify(appData))
    cleanApp.createdAtTimestamp = serverTimestamp()
    cleanApp.updatedAt = new Date().toISOString()
    const docId = String(cleanApp.id || `APP-${Date.now().toString().slice(-4)}`)
    await setDoc(doc(dbInstance, 'reseller_applications', docId), cleanApp, { merge: true })
    return { success: true, id: docId }
  } catch (error) {
    console.warn('[Firebase] Error guardando postulación de revendedor:', error)
    return { success: false, error: error.message }
  }
}

/**
 * Consulta directa de postulaciones de revendedores
 */
export async function fetchLatestResellerApplicationsFromFirebase() {
  if (!dbInstance) {
    initFirebase()
  }
  if (!dbInstance) return []

  try {
    const q = query(collection(dbInstance, 'reseller_applications'), limit(150))
    const snap = await getDocs(q)
    const list = []
    snap.forEach((docSnap) => {
      list.push({ id: docSnap.id, ...docSnap.data() })
    })
    return list
  } catch (error) {
    console.warn('[Firebase] Error al consultar reseller_applications:', error)
    return []
  }
}

/**
 * Escucha cambios en tiempo real en la colección 'reseller_applications'
 */
export function listenToResellerApplications(callback) {
  if (!dbInstance) {
    initFirebase()
  }
  if (!dbInstance) return () => {}

  try {
    const q = query(collection(dbInstance, 'reseller_applications'), limit(150))
    return onSnapshot(q, (snapshot) => {
      const apps = []
      snapshot.forEach((docSnap) => {
        apps.push({ id: docSnap.id, ...docSnap.data() })
      })
      callback(apps)
    }, (err) => {
      console.warn('[Firebase] Snapshot error en reseller_applications:', err)
    })
  } catch (err) {
    console.warn('[Firebase] Listener error en reseller_applications:', err)
    return () => {}
  }
}

/**
 * Actualiza el estado de una postulación de revendedor en Firestore
 */
export async function updateResellerApplicationInFirebase(appId, data) {
  if (!dbInstance) {
    initFirebase()
  }
  if (!dbInstance) return { success: false, reason: 'Firebase no inicializado' }

  try {
    const docRef = doc(dbInstance, 'reseller_applications', String(appId))
    await setDoc(docRef, { ...data, updatedAt: new Date().toISOString() }, { merge: true })
    return { success: true }
  } catch (error) {
    console.warn('[Firebase] Error actualizando postulación en Firestore:', error)
    return { success: false, error: error.message }
  }
}

/**
 * Guarda un revendedor oficial en la colección 'resellers' de Firestore
 */
export async function saveResellerToFirebase(resellerData) {
  if (!dbInstance) {
    initFirebase()
  }
  if (!dbInstance) return { success: false, reason: 'Firebase no inicializado' }

  try {
    const cleanRes = JSON.parse(JSON.stringify(resellerData))
    cleanRes.updatedAt = new Date().toISOString()
    cleanRes.serverTimestamp = serverTimestamp()
    const docId = String(cleanRes.id || `RES-${Date.now().toString().slice(-4)}`)
    await setDoc(doc(dbInstance, 'resellers', docId), cleanRes, { merge: true })
    return { success: true, id: docId }
  } catch (error) {
    console.warn('[Firebase] Error guardando revendedor en Firestore:', error)
    return { success: false, error: error.message }
  }
}

/**
 * Consulta la lista de revendedores oficiales en Firestore
 */
export async function fetchResellersFromFirebase() {
  if (!dbInstance) {
    initFirebase()
  }
  if (!dbInstance) return []

  try {
    const q = query(collection(dbInstance, 'resellers'), limit(200))
    const snap = await getDocs(q)
    const list = []
    snap.forEach((docSnap) => {
      list.push({ id: docSnap.id, ...docSnap.data() })
    })
    return list
  } catch (error) {
    console.warn('[Firebase] Error al consultar resellers en Firestore:', error)
    return []
  }
}

/**
 * Escucha cambios en tiempo real en la colección 'resellers' de Firestore
 */
export function listenToResellers(callback) {
  if (!dbInstance) {
    initFirebase()
  }
  if (!dbInstance) return () => {}

  try {
    const q = query(collection(dbInstance, 'resellers'), limit(200))
    return onSnapshot(q, (snapshot) => {
      const resellers = []
      snapshot.forEach((docSnap) => {
        resellers.push({ id: docSnap.id, ...docSnap.data() })
      })
      callback(resellers)
    }, (err) => {
      console.warn('[Firebase] Snapshot error en resellers:', err)
    })
  } catch (err) {
    console.warn('[Firebase] Listener error en resellers:', err)
    return () => {}
  }
}

/**
 * Elimina un revendedor de Firestore
 */
export async function deleteResellerFromFirebase(resellerId) {
  if (!dbInstance) {
    initFirebase()
  }
  if (!dbInstance) return { success: false }

  try {
    const docRef = doc(dbInstance, 'resellers', String(resellerId))
    await deleteDoc(docRef)
    return { success: true }
  } catch (error) {
    console.warn('[Firebase] Error eliminando revendedor de Firestore:', error)
    return { success: false, error: error.message }
  }
}

/**
 * Elimina una orden de streaming o gaming de Firestore
 */
export async function deleteOrderFromFirebase(orderId) {
  if (!dbInstance) initFirebase()
  if (!dbInstance) return { success: false }
  try {
    const docRef = doc(dbInstance, 'orders', String(orderId))
    await deleteDoc(docRef)
    return { success: true }
  } catch (error) {
    console.warn('[Firebase] Error eliminando orden de Firestore:', error)
    return { success: false, error: error.message }
  }
}

/**
 * Elimina una orden de productos físicos de Firestore
 */
export async function deletePhysicalOrderFromFirebase(orderId) {
  if (!dbInstance) initFirebase()
  if (!dbInstance) return { success: false }
  try {
    const docRef = doc(dbInstance, 'physical_orders', String(orderId))
    await deleteDoc(docRef)
    return { success: true }
  } catch (error) {
    console.warn('[Firebase] Error eliminando orden física de Firestore:', error)
    return { success: false, error: error.message }
  }
}

/**
 * Consulta la configuración de la tienda en Firestore
 */
export async function fetchStoreSettingsFromFirebase() {
  if (!dbInstance) {
    initFirebase()
  }

  // 1. Intentar con Firestore SDK nativo
  if (dbInstance) {
    try {
      const snap = await getDoc(doc(dbInstance, 'settings', 'store_settings'))
      if (snap.exists()) {
        return snap.data()
      }
    } catch (error) {
      console.warn('[Firebase SDK] Error consultando store_settings en la nube, usando REST:', error)
    }
  }

  // 2. Fallback por REST API directo
  try {
    const restData = await fetchStoreSettingsViaRest()
    if (restData) {
      return restData
    }
  } catch (err) {
    console.warn('[Firebase REST] Error consultando store_settings vía REST:', err)
  }

  return null
}

/**
 * Escucha cambios en tiempo real en la configuración de la tienda para reflejar activaciones/desactivaciones al instante
 */
export function listenToStoreSettings(callback) {
  if (!dbInstance) {
    initFirebase()
  }
  if (!dbInstance) return () => {}

  try {
    return onSnapshot(doc(dbInstance, 'settings', 'store_settings'), (snap) => {
      // Ignorar escrituras locales pendientes para no revertir ediciones inmediatas del admin
      if (snap.metadata && snap.metadata.hasPendingWrites) {
        return
      }
      if (snap.exists()) {
        callback(snap.data())
      }
    }, (err) => {
      console.warn('[Firebase] Snapshot error en store_settings:', err)
    })
  } catch (err) {
    console.warn('[Firebase] Listener error store_settings:', err)
    return () => {}
  }
}

/**
 * Guarda o actualiza un ticket / reporte de falla en Firestore
 */
export async function saveSupportTicketToFirebase(ticketData) {
  if (!dbInstance) {
    initFirebase()
  }
  if (!dbInstance) return { success: false, reason: 'Firebase no inicializado' }

  try {
    const cleanTicket = JSON.parse(JSON.stringify(ticketData))
    cleanTicket.updatedAt = new Date().toISOString()
    const docId = String(cleanTicket.id || `TCK-${Date.now()}`)
    await setDoc(doc(dbInstance, 'support_tickets', docId), cleanTicket, { merge: true })
    return { success: true, id: docId }
  } catch (error) {
    console.warn('[Firebase] Error guardando ticket de soporte:', error)
    return { success: false, error: error.message }
  }
}

/**
 * Escucha cambios en tiempo real en 'support_tickets'
 */
export function listenToSupportTickets(callback) {
  if (!dbInstance) {
    initFirebase()
  }
  if (!dbInstance) return () => {}

  try {
    const q = query(collection(dbInstance, 'support_tickets'), limit(150))
    return onSnapshot(q, (snapshot) => {
      const tickets = []
      snapshot.forEach((docSnap) => {
        tickets.push({ id: docSnap.id, ...docSnap.data() })
      })
      callback(tickets)
    }, (err) => {
      console.warn('[Firebase] Snapshot error en support_tickets:', err)
    })
  } catch (err) {
    console.warn('[Firebase] Listener error support_tickets:', err)
    return () => {}
  }
}

/**
 * Sincroniza configuración de campaña, Usuarios Descuento, postulaciones, créditos y sorteos en 'settings/marketing_hub'
 */
export async function saveMarketingHubPatchToFirebase(patch = {}) {
  if (!dbInstance) initFirebase()
  if (!dbInstance) return { success: false, reason: 'Firebase no inicializado' }

  try {
    const cleanPatch = JSON.parse(JSON.stringify(patch))
    cleanPatch.updatedAt = new Date().toISOString()
    await setDoc(doc(dbInstance, 'settings', 'marketing_hub'), cleanPatch, { merge: true })
    return { success: true }
  } catch (error) {
    console.warn('[Firebase] Error guardando marketing_hub:', error)
    return { success: false, error: error.message }
  }
}

export async function deleteMarketingHubMapKeyInFirebase(mapField, itemId) {
  if (!dbInstance) initFirebase()
  if (!dbInstance || !mapField || !itemId) return { success: false }

  try {
    await setDoc(
      doc(dbInstance, 'settings', 'marketing_hub'),
      { [mapField]: { [String(itemId)]: deleteField() }, updatedAt: new Date().toISOString() },
      { merge: true }
    )
    return { success: true }
  } catch (error) {
    console.warn('[Firebase] Error eliminando clave en marketing_hub:', error)
    return { success: false, error: error.message }
  }
}

export async function saveGiveawayEntryToFirebase(entryData) {
  if (!dbInstance) initFirebase()
  if (!dbInstance || !entryData?.id) return { success: false }

  try {
    const cleanEntry = JSON.parse(JSON.stringify(entryData))
    cleanEntry.updatedAt = new Date().toISOString()
    await setDoc(
      doc(dbInstance, 'settings', 'giveaways_entries'),
      { entriesById: { [String(cleanEntry.id)]: cleanEntry }, updatedAt: new Date().toISOString() },
      { merge: true }
    )
    return { success: true }
  } catch (error) {
    console.warn('[Firebase] Error guardando participación en sorteo:', error)
    return { success: false, error: error.message }
  }
}

export async function deleteGiveawayEntryInFirebase(entryId) {
  if (!dbInstance) initFirebase()
  if (!dbInstance || !entryId) return { success: false }

  try {
    await setDoc(
      doc(dbInstance, 'settings', 'giveaways_entries'),
      { entriesById: { [String(entryId)]: deleteField() }, updatedAt: new Date().toISOString() },
      { merge: true }
    )
    return { success: true }
  } catch (error) {
    return { success: false, error: error.message }
  }
}

export async function fetchMarketingHubFromFirebase() {
  if (!dbInstance) initFirebase()
  if (!dbInstance) return null
  try {
    const snap = await getDoc(doc(dbInstance, 'settings', 'marketing_hub'))
    return snap.exists() ? snap.data() : null
  } catch (error) {
    return null
  }
}

export function listenToMarketingHub(callback) {
  if (!dbInstance) initFirebase()
  if (!dbInstance) return () => {}
  try {
    return onSnapshot(doc(dbInstance, 'settings', 'marketing_hub'), (snap) => {
      if (snap.exists()) callback(snap.data())
    }, (err) => {
      console.warn('[Firebase] Snapshot error marketing_hub:', err)
    })
  } catch {
    return () => {}
  }
}

export async function fetchGiveawayEntriesFromFirebase() {
  if (!dbInstance) initFirebase()
  if (!dbInstance) return null
  try {
    const snap = await getDoc(doc(dbInstance, 'settings', 'giveaways_entries'))
    return snap.exists() ? snap.data() : null
  } catch {
    return null
  }
}

export function listenToGiveawayEntries(callback) {
  if (!dbInstance) initFirebase()
  if (!dbInstance) return () => {}
  try {
    return onSnapshot(doc(dbInstance, 'settings', 'giveaways_entries'), (snap) => {
      if (snap.exists()) callback(snap.data())
    }, (err) => {
      console.warn('[Firebase] Snapshot error giveaways_entries:', err)
    })
  } catch {
    return () => {}
  }
}

/**
 * Guarda un comprobante directamente en la colección 'receipts' mediante la REST API de Firestore
 * Garantiza persistencia inmediata incluso si el SDK de Firebase tiene conflictos
 */
export async function saveReceiptViaFirestoreRest(orderId, cleanReceipt) {
  try {
    const projectId = OFFICIAL_FIREBASE_CONFIG.projectId
    const url = `https://firestore.googleapis.com/v1/projects/${projectId}/databases/(default)/documents/receipts/${encodeURIComponent(orderId)}`
    const body = {
      fields: {
        orderId: { stringValue: String(cleanReceipt.orderId || orderId) },
        fileName: { stringValue: String(cleanReceipt.fileName || 'comprobante.jpg') },
        fileType: { stringValue: String(cleanReceipt.fileType || 'image/jpeg') },
        fileSize: { integerValue: String(cleanReceipt.fileSize || 0) },
        dataUrl: { stringValue: String(cleanReceipt.dataUrl || '') },
        customerName: { stringValue: String(cleanReceipt.customerName || '') },
        customerPhone: { stringValue: String(cleanReceipt.customerPhone || '') },
        amount: { doubleValue: Number(cleanReceipt.amount || 0) },
        uploadedAt: { stringValue: String(cleanReceipt.uploadedAt || new Date().toISOString()) }
      }
    }
    const res = await fetch(url, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body)
    })
    return res.ok
  } catch (err) {
    console.warn('[Firebase REST] Error guardando comprobante vía REST:', err)
    return false
  }
}

/**
 * Lee un comprobante directamente desde Firestore mediante su REST API
 */
export async function getReceiptViaFirestoreRest(orderId) {
  try {
    const projectId = OFFICIAL_FIREBASE_CONFIG.projectId
    const url = `https://firestore.googleapis.com/v1/projects/${projectId}/databases/(default)/documents/receipts/${encodeURIComponent(orderId)}`
    const res = await fetch(url)
    if (!res.ok) return null
    const json = await res.json()
    if (!json || !json.fields) return null
    const f = json.fields
    return {
      orderId: f.orderId?.stringValue || orderId,
      fileName: f.fileName?.stringValue || 'comprobante.jpg',
      fileType: f.fileType?.stringValue || 'image/jpeg',
      fileSize: parseInt(f.fileSize?.integerValue || '0', 10),
      dataUrl: f.dataUrl?.stringValue || '',
      customerName: f.customerName?.stringValue || '',
      customerPhone: f.customerPhone?.stringValue || '',
      amount: f.amount?.doubleValue ?? parseFloat(f.amount?.integerValue || '0'),
      uploadedAt: f.uploadedAt?.stringValue || ''
    }
  } catch (err) {
    console.warn('[Firebase REST] Error obteniendo comprobante vía REST:', err)
    return null
  }
}

/**
 * Elimina un comprobante directamente desde Firestore mediante REST API
 */
export async function deleteReceiptViaFirestoreRest(orderId) {
  try {
    const projectId = OFFICIAL_FIREBASE_CONFIG.projectId
    const url = `https://firestore.googleapis.com/v1/projects/${projectId}/databases/(default)/documents/receipts/${encodeURIComponent(orderId)}`
    const res = await fetch(url, { method: 'DELETE' })
    return res.ok
  } catch (err) {
    return false
  }
}

/**
 * Guarda un comprobante en la colección 'receipts' de Firestore (Mini Nube en la Nube Real)
 * Utiliza SDK nativo con fallback automático y transparente a REST API
 */
export async function saveReceiptToFirestore(orderId, receiptData) {
  if (!orderId || !receiptData) return { success: false, reason: 'Datos incompletos' }

  const cleanReceipt = {
    orderId: String(orderId),
    fileName: receiptData.fileName || 'comprobante.jpg',
    fileType: receiptData.fileType || 'image/jpeg',
    fileSize: Number(receiptData.fileSize || 0),
    dataUrl: String(receiptData.dataUrl || ''),
    customerName: receiptData.customerName || '',
    customerPhone: receiptData.customerPhone || '',
    amount: Number(receiptData.amount || 0),
    uploadedAt: receiptData.uploadedAt || new Date().toISOString()
  }

  // 1. Intentar con Firestore SDK si está inicializado o se puede inicializar
  if (!dbInstance) initFirebase()
  if (dbInstance) {
    try {
      await setDoc(doc(dbInstance, 'receipts', String(orderId)), {
        ...cleanReceipt,
        updatedAt: serverTimestamp()
      }, { merge: true })
      return { success: true, id: orderId }
    } catch (sdkError) {
      console.warn('[Firebase SDK] Falló setDoc, usando REST fallback:', sdkError)
    }
  }

  // 2. Fallback de alta confiabilidad por REST API de Firestore
  const restOk = await saveReceiptViaFirestoreRest(orderId, cleanReceipt)
  if (restOk) {
    return { success: true, id: orderId }
  }

  return { success: false, reason: 'No se pudo guardar el comprobante en Firestore' }
}

/**
 * Obtiene un comprobante desde la colección 'receipts' de Firestore
 * Consulta SDK con fallback a REST API
 */
export async function getReceiptFromFirestore(orderId) {
  if (!orderId) return null
  if (!dbInstance) initFirebase()

  // 1. Intentar con Firestore SDK
  if (dbInstance) {
    try {
      const snap = await getDoc(doc(dbInstance, 'receipts', String(orderId)))
      if (snap.exists()) {
        return snap.data()
      }
    } catch (sdkError) {
      console.warn('[Firebase SDK] Error obteniendo comprobante, intentando REST:', sdkError)
    }
  }

  // 2. Fallback por REST API directa
  const restData = await getReceiptViaFirestoreRest(orderId)
  if (restData) {
    return restData
  }

  return null
}

/**
 * Elimina un comprobante de la colección 'receipts' de Firestore (SDK + REST)
 */
export async function deleteReceiptFromFirestore(orderId) {
  if (!orderId) return false
  if (!dbInstance) initFirebase()

  if (dbInstance) {
    try {
      await deleteDoc(doc(dbInstance, 'receipts', String(orderId)))
    } catch (e) {}
  }

  try {
    await deleteReceiptViaFirestoreRest(orderId)
  } catch (e) {}

  return true
}

/**
 * Prueba la conexión con Firebase intentando una lectura básica y limpiando apps temporales
 */
export async function testFirebaseConnection(config = null) {
  let testApp = null
  try {
    const testConfig = (config && config.apiKey && config.projectId) ? config : OFFICIAL_FIREBASE_CONFIG
    testApp = initializeApp(testConfig, 'test-app-' + Date.now())
    const testDb = getFirestore(testApp)
    const q = query(collection(testDb, 'orders'), limit(1))
    await getDocs(q)
    return { success: true, message: '¡Conexión exitosa con Firebase Firestore!' }
  } catch (error) {
    return { success: false, message: error.message }
  } finally {
    if (testApp) {
      try {
        await deleteApp(testApp)
      } catch (_) {}
    }
  }
}

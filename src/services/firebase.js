// Servicio Oficial de Sincronización en Tiempo Real con Google Firebase Firestore
import { initializeApp, getApps, getApp } from 'firebase/app'
import { 
  getFirestore, 
  collection, 
  addDoc, 
  setDoc,
  doc, 
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
    currentApp = apps.length > 0 ? getApp() : initializeApp(effectiveConfig)
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

/**
 * Prueba la conexión con Firebase intentando una lectura básica
 */
export async function testFirebaseConnection(config = null) {
  try {
    const testConfig = (config && config.apiKey && config.projectId) ? config : OFFICIAL_FIREBASE_CONFIG
    const testApp = initializeApp(testConfig, 'test-app-' + Date.now())
    const testDb = getFirestore(testApp)
    const q = query(collection(testDb, 'orders'), limit(1))
    await getDocs(q)
    return { success: true, message: '¡Conexión exitosa con Firebase Firestore!' }
  } catch (error) {
    return { success: false, message: error.message }
  }
}

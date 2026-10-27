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

let dbInstance = null
let currentApp = null

/**
 * Inicializa o re-inicializa Firebase con la configuración provista
 */
export function initFirebase(config) {
  if (!config || !config.apiKey || !config.projectId) {
    dbInstance = null
    return null
  }

  try {
    const apps = getApps()
    currentApp = apps.length > 0 ? getApp() : initializeApp(config)
    dbInstance = getFirestore(currentApp)
    return dbInstance
  } catch (error) {
    console.error('[Firebase] Error al inicializar:', error)
    dbInstance = null
    return null
  }
}

export function isFirebaseConfigured() {
  return dbInstance !== null
}

/**
 * Guarda una orden de streaming en la colección 'orders'
 */
export async function saveStreamingOrderToFirebase(orderData) {
  if (!dbInstance) return { success: false, reason: 'Firebase no inicializado' }

  try {
    const cleanOrder = JSON.parse(JSON.stringify(orderData))
    cleanOrder.createdAtTimestamp = serverTimestamp()
    cleanOrder.sourceOrigin = 'streamepro.me'

    if (cleanOrder.id) {
      await setDoc(doc(dbInstance, 'orders', String(cleanOrder.id)), cleanOrder, { merge: true })
    } else {
      await addDoc(collection(dbInstance, 'orders'), cleanOrder)
    }

    return { success: true }
  } catch (error) {
    console.error('[Firebase] Error guardando orden de streaming:', error)
    return { success: false, error: error.message }
  }
}

/**
 * Guarda una orden de productos físicos en la colección 'physical_orders'
 */
export async function savePhysicalOrderToFirebase(orderData) {
  if (!dbInstance) return { success: false, reason: 'Firebase no inicializado' }

  try {
    const cleanOrder = JSON.parse(JSON.stringify(orderData))
    cleanOrder.createdAtTimestamp = serverTimestamp()
    cleanOrder.sourceOrigin = 'streamepro.me'

    if (cleanOrder.id) {
      await setDoc(doc(dbInstance, 'physical_orders', String(cleanOrder.id)), cleanOrder, { merge: true })
    } else {
      await addDoc(collection(dbInstance, 'physical_orders'), cleanOrder)
    }

    return { success: true }
  } catch (error) {
    console.error('[Firebase] Error guardando orden física:', error)
    return { success: false, error: error.message }
  }
}

/**
 * Escucha cambios en tiempo real en la colección 'orders'
 */
export function listenToStreamingOrders(callback) {
  if (!dbInstance) return () => {}

  try {
    const q = query(collection(dbInstance, 'orders'), orderBy('createdAtTimestamp', 'desc'), limit(100))
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
  if (!dbInstance) return () => {}

  try {
    const q = query(collection(dbInstance, 'physical_orders'), orderBy('createdAtTimestamp', 'desc'), limit(100))
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
 * Prueba la conexión con Firebase intentando una lectura básica
 */
export async function testFirebaseConnection(config) {
  try {
    const testApp = initializeApp(config, 'test-app-' + Date.now())
    const testDb = getFirestore(testApp)
    const q = query(collection(testDb, 'orders'), limit(1))
    await getDocs(q)
    return { success: true, message: '¡Conexión exitosa con Firebase Firestore!' }
  } catch (error) {
    return { success: false, message: error.message }
  }
}

// Servicio de Nube de Comprobantes de Pago para StreamePro
// Almacena comprobantes en formato JPG, PNG, WEBP y PDF con sincronización en la Nube Real (Firebase Firestore)
// y aceleración en memoria y almacenamiento local (IndexedDB)
// Permite que el revendedor y el proveedor verifiquen los comprobantes directamente desde sus paneles
// Al confirmar el cierre de la orden, el comprobante se elimina definitivamente para no ocupar espacio en disco.

import { 
  saveReceiptToFirestore, 
  getReceiptFromFirestore, 
  deleteReceiptFromFirestore 
} from './firebase'

const DB_NAME = 'streampro_receipt_cloud_v1'
const STORE_NAME = 'receipts'
const DB_VERSION = 1

// Caché en memoria para accesos ultrarrápidos durante la sesión
const memoryReceiptCache = new Map()

function openDb() {
  return new Promise((resolve, reject) => {
    if (typeof window === 'undefined' || !window.indexedDB) {
      resolve(null)
      return
    }

    try {
      const request = indexedDB.open(DB_NAME, DB_VERSION)

      request.onupgradeneeded = (event) => {
        const db = event.target.result
        if (!db.objectStoreNames.contains(STORE_NAME)) {
          db.createObjectStore(STORE_NAME, { keyPath: 'orderId' })
        }
      }

      request.onsuccess = () => resolve(request.result)
      request.onerror = (e) => {
        console.warn('[ReceiptCloud] Error al abrir IndexedDB:', e)
        resolve(null)
      }
    } catch (err) {
      console.warn('[ReceiptCloud] Excepción IndexedDB:', err)
      resolve(null)
    }
  })
}

/**
 * Guarda un comprobante en la Nube (Memoria + IndexedDB + Firebase Firestore)
 */
export async function saveReceipt(orderId, receiptData) {
  if (!orderId || !receiptData) return false

  const record = {
    orderId: String(orderId),
    fileName: receiptData.fileName || 'comprobante.jpg',
    fileType: receiptData.fileType || 'image/jpeg',
    fileSize: receiptData.fileSize || 0,
    dataUrl: receiptData.dataUrl,
    customerName: receiptData.customerName || '',
    customerPhone: receiptData.customerPhone || '',
    amount: receiptData.amount || 0,
    uploadedAt: receiptData.uploadedAt || new Date().toISOString()
  }

  // 1. Guardar en caché de memoria inmediato
  memoryReceiptCache.set(orderId, record)

  // 2. Guardar en IndexedDB local
  const db = await openDb()
  if (db) {
    try {
      await new Promise((resolve) => {
        const tx = db.transaction(STORE_NAME, 'readwrite')
        const store = tx.objectStore(STORE_NAME)
        const req = store.put(record)
        req.onsuccess = () => resolve(true)
        req.onerror = () => resolve(true)
      })
    } catch (e) {
      console.warn('[ReceiptCloud] Error al guardar en IndexedDB:', e)
    }
  }

  // 3. Guardar en la Nube Global (Firebase Firestore) para acceso inmediato desde cualquier dispositivo
  try {
    await saveReceiptToFirestore(orderId, record)
  } catch (cloudErr) {
    console.warn('[ReceiptCloud] Error guardando comprobante en Firebase:', cloudErr)
  }

  return true
}

/**
 * Obtiene el comprobante desde la Nube (Memoria -> IndexedDB -> Firebase Firestore)
 */
export async function getReceipt(orderId) {
  if (!orderId) return null

  // 1. Revisar memoria
  if (memoryReceiptCache.has(orderId)) {
    return memoryReceiptCache.get(orderId)
  }

  // 2. Revisar IndexedDB local
  const db = await openDb()
  if (db) {
    try {
      const localResult = await new Promise((resolve) => {
        const tx = db.transaction(STORE_NAME, 'readonly')
        const store = tx.objectStore(STORE_NAME)
        const req = store.get(orderId)
        req.onsuccess = () => resolve(req.result || null)
        req.onerror = () => resolve(null)
      })
      if (localResult && localResult.dataUrl) {
        memoryReceiptCache.set(orderId, localResult)
        return localResult
      }
    } catch (e) {
      console.warn('[ReceiptCloud] Error al leer IndexedDB:', e)
    }
  }

  // 3. Recuperar desde la Nube Global (Firebase Firestore)
  try {
    const cloudReceipt = await getReceiptFromFirestore(orderId)
    if (cloudReceipt && cloudReceipt.dataUrl) {
      memoryReceiptCache.set(orderId, cloudReceipt)
      if (db) {
        try {
          const tx = db.transaction(STORE_NAME, 'readwrite')
          tx.objectStore(STORE_NAME).put(cloudReceipt)
        } catch {}
      }
      return cloudReceipt
    }
  } catch (cloudErr) {
    console.warn('[ReceiptCloud] Error recuperando comprobante de Firebase:', cloudErr)
  }

  return null
}

/**
 * Elimina el comprobante definitivamente de la Nube (Memoria + IndexedDB + Firebase Firestore)
 */
export async function deleteReceipt(orderId) {
  if (!orderId) return true

  // 1. Limpiar memoria
  memoryReceiptCache.delete(orderId)

  // 2. Limpiar IndexedDB local
  const db = await openDb()
  if (db) {
    try {
      await new Promise((resolve) => {
        const tx = db.transaction(STORE_NAME, 'readwrite')
        const store = tx.objectStore(STORE_NAME)
        const req = store.delete(orderId)
        req.onsuccess = () => resolve(true)
        req.onerror = () => resolve(false)
      })
    } catch (e) {
      console.warn('[ReceiptCloud] Error al eliminar de IndexedDB:', e)
    }
  }

  // 3. Eliminar de Firebase Firestore
  try {
    await deleteReceiptFromFirestore(orderId)
  } catch (cloudErr) {
    console.warn('[ReceiptCloud] Error eliminando comprobante de Firebase:', cloudErr)
  }

  return true
}

/**
 * Verifica si existe un comprobante registrado para esa orden
 */
export async function hasReceipt(orderId) {
  if (!orderId) return false
  if (memoryReceiptCache.has(orderId)) return true
  const receipt = await getReceipt(orderId)
  return Boolean(receipt)
}

/**
 * Limpia todos los comprobantes de la mini nube
 */
export async function clearAllReceipts() {
  memoryReceiptCache.clear()
  const db = await openDb()
  if (db) {
    try {
      const tx = db.transaction(STORE_NAME, 'readwrite')
      tx.objectStore(STORE_NAME).clear()
    } catch (e) {}
  }
}

/**
 * Comprime imágenes antes de subirlas a la mini nube para optimizar espacio,
 * o devuelve DataURL directo para archivos PDF.
 */
export function processReceiptFile(file) {
  return new Promise((resolve, reject) => {
    if (!file) {
      reject(new Error('Archivo no provisto'))
      return
    }

    const isPdf = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf')
    const isImage = file.type.startsWith('image/') || /\.(jpe?g|png|webp)$/i.test(file.name)

    if (!isPdf && !isImage) {
      reject(new Error('Formato no soportado. Por favor adjunta JPG, PNG o PDF.'))
      return
    }

    const reader = new FileReader()

    if (isPdf) {
      // PDF: Leer como DataURL directo
      reader.onload = (e) => {
        resolve({
          fileName: file.name,
          fileType: 'application/pdf',
          fileSize: file.size,
          dataUrl: e.target.result
        })
      }
      reader.onerror = reject
      reader.readAsDataURL(file)
      return
    }

    // Imagen (JPG/PNG/WEBP): Comprimir proporcionalmente si supera 1200px para almacenamiento óptimo en nube
    reader.onload = (e) => {
      const img = new Image()
      img.onload = () => {
        const maxDim = 1200
        let width = img.width
        let height = img.height

        if (width > maxDim || height > maxDim) {
          if (width > height) {
            height = Math.round((height * maxDim) / width)
            width = maxDim
          } else {
            width = Math.round((width * maxDim) / height)
            height = maxDim
          }
        }

        const canvas = document.createElement('canvas')
        canvas.width = width
        canvas.height = height
        const ctx = canvas.getContext('2d')
        ctx.drawImage(img, 0, 0, width, height)

        const compressedDataUrl = canvas.toDataURL('image/jpeg', 0.78)
        resolve({
          fileName: file.name.replace(/\.[^/.]+$/, '') + '.jpg',
          fileType: 'image/jpeg',
          fileSize: Math.round(compressedDataUrl.length * 0.75),
          dataUrl: compressedDataUrl
        })
      }
      img.onerror = () => {
        // Fallback en caso de error al cargar imagen en canvas
        resolve({
          fileName: file.name,
          fileType: file.type || 'image/jpeg',
          fileSize: file.size,
          dataUrl: e.target.result
        })
      }
      img.src = e.target.result
    }
    reader.onerror = reject
    reader.readAsDataURL(file)
  })
}

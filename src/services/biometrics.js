// Servicio de Autenticación Biométrica (WebAuthn / Windows Hello / Face ID / Touch ID)
// y Gestión de Sesión Recordada para StreamPro

const BIOMETRIC_PREFIX = 'streamepro_bio_'
const SESSION_PREFIX = 'streamepro_session_'

/**
 * Verifica si el dispositivo y navegador soportan autenticación biométrica WebAuthn
 */
export async function isBiometricsAvailable() {
  if (typeof window === 'undefined') return false
  if (!window.PublicKeyCredential) return false
  try {
    if (PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable) {
      return await PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable()
    }
    return true
  } catch (e) {
    return false
  }
}

/**
 * Registra las credenciales biométricas del usuario en este dispositivo
 */
export async function registerBiometricCredential(role, userObj) {
  try {
    const isSupported = await isBiometricsAvailable()
    let credentialId = 'device_local_bio_' + Date.now()

    if (isSupported && window.isSecureContext && navigator.credentials?.create) {
      const challenge = new Uint8Array(32)
      window.crypto.getRandomValues(challenge)
      const userId = new Uint8Array(16)
      window.crypto.getRandomValues(userId)

      const credential = await navigator.credentials.create({
        publicKey: {
          challenge,
          rp: { name: 'StreamPro Digital', id: window.location.hostname || 'streamepro.me' },
          user: {
            id: userId,
            name: userObj.username || userObj.id || role,
            displayName: userObj.name || userObj.storeName || 'Usuario StreamPro'
          },
          pubKeyCredParams: [
            { alg: -7, type: 'public-key' },  // ES256
            { alg: -257, type: 'public-key' } // RS256
          ],
          authenticatorSelection: {
            authenticatorAttachment: 'platform',
            userVerification: 'preferred'
          },
          timeout: 60000
        }
      })
      if (credential) {
        credentialId = credential.id
      }
    }

    const payload = {
      role, // 'reseller' | 'admin'
      userId: userObj.id || userObj.username,
      username: userObj.username,
      name: userObj.name || userObj.storeName,
      credentialId,
      createdAt: new Date().toISOString()
    }

    localStorage.setItem(BIOMETRIC_PREFIX + role, JSON.stringify(payload))
    return { success: true, payload }
  } catch (err) {
    console.warn('[Biometrics] Error durante registro:', err)
    return { error: err.message || 'No se pudo registrar la biometría en este dispositivo' }
  }
}

/**
 * Inicia sesión usando la huella digital / rostro / PIN del dispositivo
 */
export async function authenticateWithBiometrics(role) {
  try {
    const raw = localStorage.getItem(BIOMETRIC_PREFIX + role)
    if (!raw) {
      return { error: 'No hay datos biométricos configurados en este dispositivo para este perfil' }
    }
    const bioData = JSON.parse(raw)

    const isSupported = await isBiometricsAvailable()
    if (isSupported && window.isSecureContext && navigator.credentials?.get) {
      const challenge = new Uint8Array(32)
      window.crypto.getRandomValues(challenge)

      await navigator.credentials.get({
        publicKey: {
          challenge,
          timeout: 60000,
          userVerification: 'preferred'
        }
      })
    }

    return { success: true, bioData }
  } catch (err) {
    console.warn('[Biometrics] Error al autenticar:', err)
    return { error: err.name === 'NotAllowedError' ? 'Autenticación biométrica cancelada' : 'Error en la verificación biométrica' }
  }
}

/**
 * Verifica si hay datos biométricos activos para un rol en este dispositivo
 */
export function hasBiometricEnrolled(role) {
  try {
    return Boolean(localStorage.getItem(BIOMETRIC_PREFIX + role))
  } catch (e) {
    return false
  }
}

/**
 * Remueve la configuración biométrica
 */
export function removeBiometricEnrollment(role) {
  try {
    localStorage.removeItem(BIOMETRIC_PREFIX + role)
    return true
  } catch (e) {
    return false
  }
}

/**
 * Gestión de Sesión Recordada ("Recordar inicio de sesión")
 */
export function saveRememberedSession(role, userObj) {
  try {
    localStorage.setItem(SESSION_PREFIX + role, JSON.stringify({
      role,
      id: userObj.id,
      username: userObj.username,
      timestamp: Date.now()
    }))
    return true
  } catch (e) {
    return false
  }
}

export function getRememberedSession(role) {
  try {
    const raw = localStorage.getItem(SESSION_PREFIX + role)
    if (!raw) return null
    return JSON.parse(raw)
  } catch (e) {
    return null
  }
}

export function clearRememberedSession(role) {
  try {
    localStorage.removeItem(SESSION_PREFIX + role)
    return true
  } catch (e) {
    return false
  }
}

/**
 * Utilidades para Parseo y Formato de Precios en Moneda Argentina (ARS)
 * Soporta formatos de tua.ar: "$7.028,00", "$28.568,96", "$ 7000", "12500.50", etc.
 */

export function parseArgentinePrice(val) {
  if (typeof val === 'number') {
    return isNaN(val) ? 0 : val
  }
  if (!val) return 0

  let str = String(val).trim()
  // Remover símbolo de pesos y espacios
  str = str.replace(/\$/g, '').trim()

  // Si contiene coma decimal (formato estándar argentino: 7.028,00)
  if (str.includes(',')) {
    // Quitar puntos de miles y reemplazar coma por punto
    str = str.split('.').join('').replace(',', '.')
  } else {
    // Si no tiene coma pero tiene puntos (ej: 7.000)
    const dotCount = (str.match(/\./g) || []).length
    if (dotCount > 1) {
      str = str.split('.').join('')
    } else if (dotCount === 1) {
      const parts = str.split('.')
      // Si la parte tras el punto tiene 3 dígitos, era separador de miles (ej: 7.000)
      if (parts[1].length === 3) {
        str = parts.join('')
      }
    }
  }

  // Quitar cualquier carácter que no sea dígito o punto
  str = str.replace(/[^0-9.]/g, '')

  const num = parseFloat(str)
  return isNaN(num) ? 0 : num
}

export function formatArgentinePrice(val) {
  const num = typeof val === 'number' ? val : parseArgentinePrice(val)
  return `$${num.toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
}

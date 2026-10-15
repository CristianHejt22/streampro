<script setup>
import { ref, computed, watch } from 'vue'
import { useAdminStore } from '../store/admin'
import { useToastStore } from '../store/toast'

const props = defineProps({
  show: { type: Boolean, default: false },
  defaultStoreName: { type: String, default: 'StreamPro Oficial' },
  defaultPhone: { type: String, default: '+54 9 11 0000-0000' },
  initialProduct: { type: Object, default: null }
})

const emit = defineEmits(['close'])

const adminStore = useAdminStore()
const toast = useToastStore()

// Tipo de producto a promocionar: 'streaming' | 'physical'
const flyerProductType = ref('streaming')

// Streaming selection
const selectedPlatform = ref('Netflix')
const selectedPlanKey = ref('1_perfil')

// Physical product selection
const selectedPhysicalId = ref('')
const physicalSearch = ref('')
const selectedPhysicalCat = ref('All')

// Personalización del flyer
const storeName = ref(props.defaultStoreName)
const contactPhone = ref(props.defaultPhone)
const headlineBadge = ref('🔥 ¡OFERTA EXCLUSIVA!')
const customPrice = ref(null)
const customTitle = ref('')
const customSubtitle = ref('')
const flyerTheme = ref('cinema') // 'cinema' | 'cyber' | 'emerald' | 'blue'
const isExporting = ref(false)
const flyerPreviewRef = ref(null)

// Badges sugeridos
const suggestedBadges = [
  '🔥 ¡OFERTA EXCLUSIVA!',
  '⚡ ENTREGA INMEDIATA',
  '🍿 CINE EN CASA',
  '🚀 100% GARANTIZADO',
  '💎 CALIDAD ULTRA HD 4K',
  '📦 ENVÍO A TODO EL PAÍS'
]

// Bullet points destacados
const bullet1 = ref('Calidad Ultra HD 4K Sin Caídas')
const bullet2 = ref('Perfil Privado con PIN de Seguridad')
const bullet3 = ref('Soporte & Garantía Directa 24/7')

watch(() => props.defaultStoreName, (val) => {
  if (val) storeName.value = val
}, { immediate: true })

watch(() => props.defaultPhone, (val) => {
  if (val) contactPhone.value = val
}, { immediate: true })

// Manejar producto inicial si se abrió desde una tarjeta de producto
watch(() => props.initialProduct, (prod) => {
  if (!prod) return
  if (prod.itemType === 'physical' || prod.platform === 'Producto Físico' || prod.categoryIds || prod.price?.includes('$')) {
    flyerProductType.value = 'physical'
    selectedPhysicalId.value = String(prod.id)
    customTitle.value = prod.name || ''
    customSubtitle.value = 'Tecnología & Accesorios de Alta Calidad'
    customPrice.value = prod.priceNumeric || (parseInt(prod.price?.replace(/[$. ]/g, '').replace(',', '') || '0') / 100 || null)
    bullet1.value = 'Excelente Calidad y Diseño Garantizado'
    bullet2.value = 'Envío Rápido a Todo el País'
    bullet3.value = 'Compra Segura y Asesoría por WhatsApp'
  } else if (prod.platform) {
    flyerProductType.value = 'streaming'
    selectedPlatform.value = prod.platform
    selectedPlanKey.value = prod.planKey || '1_perfil'
    customTitle.value = ''
    customSubtitle.value = ''
    customPrice.value = null
  }
}, { immediate: true })

// Helper para convertir imagen a URL con CORS usando proxy seguro wsrv.nl
const getCORSImageUrl = (rawUrl) => {
  if (!rawUrl) return null
  let fullUrl = rawUrl
  if (!fullUrl.startsWith('http')) {
    fullUrl = `https://tua.ar/${rawUrl}`
  }
  // wsrv.nl responde con Access-Control-Allow-Origin: * y redimensiona para óptima calidad
  return `https://wsrv.nl/?url=${encodeURIComponent(fullUrl)}&w=800&output=jpg`
}

// Plataforma actual y sus planes
const currentPlatformData = computed(() => {
  return adminStore.pricingCatalog[selectedPlatform.value] || { name: selectedPlatform.value, plans: {} }
})

const currentPlanData = computed(() => {
  return currentPlatformData.value.plans?.[selectedPlanKey.value] || { label: '1 Perfil', clientPrice: 5000 }
})

// Filtrado de productos físicos (catálogo completo de 364 productos con buscador y categoría)
const filteredPhysicalProducts = computed(() => {
  return adminStore.physicalProducts.filter(p => {
    const matchesCat = selectedPhysicalCat.value === 'All' || (p.categoryIds && p.categoryIds.includes(selectedPhysicalCat.value))
    const q = physicalSearch.value.toLowerCase().trim()
    const matchesQ = !q || p.name.toLowerCase().includes(q) || String(p.id).includes(q)
    return matchesCat && matchesQ
  })
})

const selectedPhysicalProduct = computed(() => {
  if (!selectedPhysicalId.value) return adminStore.physicalProducts[0] || null
  return adminStore.physicalProducts.find(p => String(p.id) === String(selectedPhysicalId.value)) || adminStore.physicalProducts[0]
})

// Imagen con CORS para el flyer del producto seleccionado
const productImageCORS = computed(() => {
  if (flyerProductType.value === 'physical' && selectedPhysicalProduct.value?.image) {
    return getCORSImageUrl(selectedPhysicalProduct.value.image)
  }
  return null
})

// Inicializar selección de plan al cambiar plataforma
watch(selectedPlatform, (newPlat) => {
  const plans = adminStore.pricingCatalog[newPlat]?.plans || {}
  const keys = Object.keys(plans)
  if (keys.length > 0) {
    selectedPlanKey.value = keys[0]
  }
  if (newPlat === 'Spotify') {
    bullet1.value = 'Música Sin Anuncios en Alta Fidelidad'
    bullet2.value = 'Descarga tus canciones sin conexión'
    bullet3.value = 'Compatible con Celular, PC y TV'
  } else if (newPlat === 'IPTV') {
    bullet1.value = '+1.500 Canales en Vivo Fútbol Libre'
    bullet2.value = 'Series, Películas y Estrenos 2026'
    bullet3.value = 'Compatible con Smart TV, TV Box y Celular'
  } else {
    bullet1.value = 'Calidad Ultra HD 4K Sin Caídas'
    bullet2.value = 'Perfil Privado con PIN de Seguridad'
    bullet3.value = 'Soporte & Garantía Directa 24/7'
  }
})

// Actualizar bullets al cambiar producto físico
watch(selectedPhysicalId, (newId) => {
  if (flyerProductType.value === 'physical' && selectedPhysicalProduct.value) {
    bullet1.value = 'Excelente Calidad y Diseño Garantizado'
    bullet2.value = 'Envío Rápido a Todo el País'
    bullet3.value = 'Compra Segura y Asesoría por WhatsApp'
  }
})

// Precio final a mostrar
const displayPrice = computed(() => {
  if (customPrice.value !== null && customPrice.value !== '') {
    return Number(customPrice.value).toLocaleString()
  }
  if (flyerProductType.value === 'streaming') {
    return (currentPlanData.value.clientPrice || 0).toLocaleString()
  } else {
    return (selectedPhysicalProduct.value?.priceNumeric || 0).toLocaleString()
  }
})

// Título principal a mostrar
const displayTitle = computed(() => {
  if (customTitle.value.trim()) return customTitle.value
  if (flyerProductType.value === 'streaming') {
    return currentPlatformData.value.name || selectedPlatform.value
  } else {
    return selectedPhysicalProduct.value?.name || 'Producto Tecnológico'
  }
})

// Subtítulo
const displaySubtitle = computed(() => {
  if (customSubtitle.value.trim()) return customSubtitle.value
  if (flyerProductType.value === 'streaming') {
    return currentPlanData.value.label || 'Suscripción Oficial'
  } else {
    return 'Tecnología & Accesorios de Alta Calidad'
  }
})

// ==========================================
// RENDERIZADOR NATIVO DE CANVAS 2D (1080x1920)
// ==========================================
// 100% inmune a errores de CSS, CORS y navegadores móviles
function renderCanvasFlyerBlob() {
  return new Promise(async (resolve, reject) => {
    try {
      const canvas = document.createElement('canvas')
      canvas.width = 1080
      canvas.height = 1920
      const ctx = canvas.getContext('2d')

      // 1. Fondo degradado según tema
      const theme = flyerTheme.value
      const bgGrad = ctx.createLinearGradient(0, 0, 0, 1920)
      if (theme === 'cinema') {
        bgGrad.addColorStop(0, '#030306')
        bgGrad.addColorStop(0.35, '#1e0409')
        bgGrad.addColorStop(0.7, '#2a040b')
        bgGrad.addColorStop(1, '#050508')
      } else if (theme === 'cyber') {
        bgGrad.addColorStop(0, '#03030c')
        bgGrad.addColorStop(0.35, '#1e0533')
        bgGrad.addColorStop(0.7, '#290642')
        bgGrad.addColorStop(1, '#031018')
      } else if (theme === 'emerald') {
        bgGrad.addColorStop(0, '#020805')
        bgGrad.addColorStop(0.35, '#032618')
        bgGrad.addColorStop(0.7, '#073322')
        bgGrad.addColorStop(1, '#020a06')
      } else {
        bgGrad.addColorStop(0, '#020814')
        bgGrad.addColorStop(0.35, '#071f3d')
        bgGrad.addColorStop(0.7, '#0b2b52')
        bgGrad.addColorStop(1, '#020914')
      }
      ctx.fillStyle = bgGrad
      ctx.fillRect(0, 0, 1080, 1920)

      // 2. Luces ambientales suaves de fondo
      const glow1 = ctx.createRadialGradient(920, 240, 20, 920, 240, 480)
      glow1.addColorStop(0, theme === 'cinema' ? 'rgba(239, 68, 68, 0.28)' : theme === 'cyber' ? 'rgba(168, 85, 247, 0.28)' : theme === 'emerald' ? 'rgba(16, 185, 129, 0.28)' : 'rgba(14, 165, 233, 0.28)')
      glow1.addColorStop(1, 'rgba(0,0,0,0)')
      ctx.fillStyle = glow1
      ctx.fillRect(440, 0, 640, 680)

      const glow2 = ctx.createRadialGradient(160, 1680, 20, 160, 1680, 480)
      glow2.addColorStop(0, 'rgba(245, 158, 11, 0.22)')
      glow2.addColorStop(1, 'rgba(0,0,0,0)')
      ctx.fillStyle = glow2
      ctx.fillRect(0, 1200, 640, 720)

      // Función auxiliar para dibujar rectángulos redondeados
      function roundRect(ctx, x, y, width, height, radius, fill, stroke, strokeColor = '#fff', lineWidth = 1) {
        ctx.beginPath()
        ctx.moveTo(x + radius, y)
        ctx.lineTo(x + width - radius, y)
        ctx.quadraticCurveTo(x + width, y, x + width, y + radius)
        ctx.lineTo(x + width, y + height - radius)
        ctx.quadraticCurveTo(x + width, y + height, x + width - radius, y + height)
        ctx.lineTo(x + radius, y + height)
        ctx.quadraticCurveTo(x, y + height, x, y + height - radius)
        ctx.lineTo(x, y + radius)
        ctx.quadraticCurveTo(x, y, x + radius, y)
        ctx.closePath()
        if (fill) ctx.fill()
        if (stroke) {
          ctx.lineWidth = lineWidth
          ctx.strokeStyle = strokeColor
          ctx.stroke()
        }
      }

      // 3. Marco exterior elegante
      roundRect(ctx, 40, 40, 1000, 1840, 48, false, true, 'rgba(255, 255, 255, 0.12)', 2)

      // 4. Encabezado de la Tienda
      ctx.textAlign = 'left'
      ctx.textBaseline = 'middle'
      ctx.font = 'bold 36px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
      ctx.fillStyle = '#fbbf24' // Dorado
      ctx.fillText(storeName.value.toUpperCase().slice(0, 28), 90, 120)

      // Badge "OFICIAL"
      ctx.fillStyle = 'rgba(255, 255, 255, 0.12)'
      roundRect(ctx, 840, 94, 150, 52, 26, true, true, 'rgba(255, 255, 255, 0.2)', 1.5)
      ctx.textAlign = 'center'
      ctx.font = 'bold 22px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
      ctx.fillStyle = '#f1f5f9'
      ctx.fillText('OFICIAL', 915, 120)

      // Línea divisoria
      ctx.strokeStyle = 'rgba(255, 255, 255, 0.12)'
      ctx.lineWidth = 1.5
      ctx.beginPath()
      ctx.moveTo(90, 170)
      ctx.lineTo(990, 170)
      ctx.stroke()

      // 5. Badge de Impacto (Pill naranja/dorado)
      const badgeText = headlineBadge.value.toUpperCase()
      ctx.font = '900 32px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
      const badgeWidth = Math.min(ctx.measureText(badgeText).width + 64, 900)
      const badgeX = (1080 - badgeWidth) / 2
      const badgeGrad = ctx.createLinearGradient(badgeX, 210, badgeX + badgeWidth, 210)
      badgeGrad.addColorStop(0, '#f59e0b')
      badgeGrad.addColorStop(1, '#ea580c')
      ctx.fillStyle = badgeGrad
      roundRect(ctx, badgeX, 210, badgeWidth, 70, 35, true, false)
      ctx.textAlign = 'center'
      ctx.fillStyle = '#020617'
      ctx.fillText(badgeText, 540, 246)

      // 6. Contenedor de la Imagen / Ícono
      const boxSize = 440
      const boxX = (1080 - boxSize) / 2
      const boxY = 320
      ctx.fillStyle = 'rgba(255, 255, 255, 0.05)'
      roundRect(ctx, boxX, boxY, boxSize, boxSize, 44, true, true, 'rgba(255, 255, 255, 0.18)', 2.5)

      // Dibujar imagen con proxy CORS o ícono
      if (flyerProductType.value === 'physical' && selectedPhysicalProduct.value?.image) {
        try {
          const imgUrl = productImageCORS.value || getCORSImageUrl(selectedPhysicalProduct.value.image)
          const img = new Image()
          img.crossOrigin = 'anonymous'
          await new Promise((res, rej) => {
            img.onload = () => res()
            img.onerror = () => rej(new Error('Img load error'))
            img.src = imgUrl
            setTimeout(() => rej(new Error('Img timeout')), 5000)
          })

          ctx.save()
          ctx.beginPath()
          roundRect(ctx, boxX + 16, boxY + 16, boxSize - 32, boxSize - 32, 32, false, false)
          ctx.clip()

          // Fondo blanco limpio para la foto del producto
          ctx.fillStyle = '#ffffff'
          ctx.fillRect(boxX + 16, boxY + 16, boxSize - 32, boxSize - 32)

          const hRatio = (boxSize - 40) / img.width
          const vRatio = (boxSize - 40) / img.height
          const ratio = Math.min(hRatio, vRatio)
          const centerShiftX = (boxSize - 32 - img.width * ratio) / 2
          const centerShiftY = (boxSize - 32 - img.height * ratio) / 2
          ctx.drawImage(img, 0, 0, img.width, img.height, boxX + 16 + centerShiftX, boxY + 16 + centerShiftY, img.width * ratio, img.height * ratio)
          ctx.restore()
        } catch (imgErr) {
          // Si falla la imagen externa por red, dibuja ícono de fallback
          ctx.font = '160px sans-serif'
          ctx.textAlign = 'center'
          ctx.fillText('📦', 540, boxY + 270)
        }
      } else {
        // Ícono grande para plataformas de streaming
        const emoji = selectedPlatform.value === 'Netflix' ? '🍿' :
                      selectedPlatform.value === 'Disney+' ? '✨' :
                      selectedPlatform.value === 'Max' ? '🎬' :
                      selectedPlatform.value === 'IPTV' ? '📺' :
                      selectedPlatform.value === 'Spotify' ? '🎵' : '⚡'
        ctx.font = '170px sans-serif'
        ctx.textAlign = 'center'
        ctx.fillText(emoji, 540, boxY + 270)
      }

      // 7. Título y Subtítulo
      let titleY = 820
      ctx.textAlign = 'center'
      ctx.font = '900 56px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
      ctx.fillStyle = '#ffffff'
      
      const rawTitle = displayTitle.value
      const maxTitleWidth = 920
      if (ctx.measureText(rawTitle).width > maxTitleWidth) {
        const words = rawTitle.split(' ')
        let line1 = ''
        let line2 = ''
        for (const w of words) {
          if (ctx.measureText(line1 + ' ' + w).width < maxTitleWidth && !line2) {
            line1 += (line1 ? ' ' : '') + w
          } else {
            line2 += (line2 ? ' ' : '') + w
          }
        }
        ctx.fillText(line1, 540, titleY)
        ctx.fillText(line2.slice(0, 38) + (line2.length > 38 ? '...' : ''), 540, titleY + 70)
        titleY += 90
      } else {
        ctx.fillText(rawTitle, 540, titleY)
        titleY += 20
      }

      // Subtítulo
      ctx.font = 'bold 36px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
      ctx.fillStyle = '#fbbf24'
      ctx.fillText(displaySubtitle.value, 540, titleY + 60)

      // 8. Tarjeta de Precio Gigante
      const priceBoxY = titleY + 120
      ctx.fillStyle = 'rgba(0, 0, 0, 0.65)'
      roundRect(ctx, 130, priceBoxY, 820, 190, 36, true, true, 'rgba(255, 255, 255, 0.16)', 2)

      ctx.font = 'bold 22px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
      ctx.fillStyle = '#94a3b8'
      ctx.fillText('PRECIO EXCLUSIVO', 540, priceBoxY + 45)

      ctx.font = '900 86px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
      ctx.fillStyle = '#ffffff'
      ctx.fillText(`$${displayPrice.value} ARS`, 540, priceBoxY + 130)

      // 9. Puntos Clave / Beneficios
      const bulletsStartY = priceBoxY + 240
      const bullets = [bullet1.value, bullet2.value, bullet3.value]
      ctx.textAlign = 'left'

      bullets.forEach((b, idx) => {
        const by = bulletsStartY + (idx * 65)
        ctx.font = 'bold 34px sans-serif'
        ctx.fillStyle = '#10b981'
        ctx.fillText('✓', 140, by)

        ctx.font = '600 32px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
        ctx.fillStyle = '#f1f5f9'
        ctx.fillText(b.length > 46 ? b.slice(0, 44) + '...' : b, 190, by)
      })

      // 10. Pie de Página con WhatsApp
      const footerY = 1680
      ctx.textAlign = 'center'
      ctx.font = 'bold 24px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
      ctx.fillStyle = '#94a3b8'
      ctx.fillText('⚡ ¡PIDE EL TUYO AHORA POR WHATSAPP!', 540, footerY)

      const waY = footerY + 35
      ctx.fillStyle = '#10b981'
      roundRect(ctx, 110, waY, 860, 115, 34, true, false)

      ctx.font = '900 44px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
      ctx.fillStyle = '#020617'
      ctx.fillText(`💬 WhatsApp: ${contactPhone.value}`, 540, waY + 70)

      canvas.toBlob((blob) => {
        if (!blob) {
          reject(new Error('Fallo al exportar blob'))
          return
        }
        resolve(blob)
      }, 'image/png')
    } catch (e) {
      reject(e)
    }
  })
}

// Descargar imagen con motor nativo de alta definición
const downloadFlyerImage = async () => {
  isExporting.value = true
  toast.add('Generando flyer en alta definición (1080x1920)...', 'info')

  try {
    const blob = await renderCanvasFlyerBlob()
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    const safeName = displayTitle.value.toLowerCase().replace(/[^a-z0-9]/g, '_').slice(0, 24)
    link.download = `flyer_${safeName}_${Date.now()}.png`
    link.href = url
    link.click()
    URL.revokeObjectURL(url)

    toast.add('¡Flyer descargado con éxito! Listo para tu Estado de WhatsApp o Instagram Stories.', 'success')
  } catch (err) {
    console.error('Error generando flyer:', err)
    toast.add('Ocurrió un error al generar el flyer. Inténtalo de nuevo.', 'error')
  } finally {
    isExporting.value = false
  }
}

// Copiar texto para publicar en redes
const copySocialCopy = () => {
  const text = 
`🔥 *${headlineBadge.value}* 🔥\n\n` +
`🎬 *${displayTitle.value}*\n` +
`📌 *Plan / Detalle:* ${displaySubtitle.value}\n` +
`💰 *PRECIO EXCLUSIVO:* $${displayPrice.value} ARS\n\n` +
`✅ ${bullet1.value}\n` +
`✅ ${bullet2.value}\n` +
`✅ ${bullet3.value}\n\n` +
`⚡ *¡Entrega inmediata! Cupos limitados.*\n` +
`📲 *Pide el tuyo por WhatsApp:* ${contactPhone.value}\n` +
`🏪 *${storeName.value}*`

  navigator.clipboard.writeText(text).then(() => {
    toast.add('¡Texto de publicación copiado! Pégalo en tu Estado o Historia.', 'success')
  })
}

// Abrir WhatsApp con el copy
const shareDirectWhatsApp = () => {
  const cleanPhone = contactPhone.value.replace(/[^0-9]/g, '')
  const text = 
`¡Hola! Quiero aprovechar la oferta de *${displayTitle.value}* (${displaySubtitle.value}) por $${displayPrice.value} ARS. ¿Tienen disponible para entrega inmediata?`
  window.open(`https://wa.me/${cleanPhone}?text=${encodeURIComponent(text)}`, '_blank')
}
</script>

<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 bg-black/85 backdrop-blur-md overflow-y-auto animate-in fade-in duration-200">
    <div class="bg-slate-900 border border-amber-500/30 w-full max-w-5xl rounded-3xl shadow-2xl overflow-hidden my-auto flex flex-col max-h-[94vh]">
      <!-- Encabezado Modal -->
      <div class="flex justify-between items-center px-6 py-4 border-b border-slate-800 bg-slate-950">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-amber-500 to-orange-500 flex items-center justify-center text-slate-950 font-black text-lg shadow">
            🎨
          </div>
          <div>
            <h3 class="text-lg font-black text-white flex items-center gap-2">
              Generador de Flyers & Banners de Oferta
              <span class="text-[10px] font-mono font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30 px-2 py-0.5 rounded">
                ESTADOS & STORIES (9:16)
              </span>
            </h3>
            <p class="text-xs text-slate-400">Crea imágenes publicitarias profesionales para WhatsApp e Instagram en 1 segundo y multiplica tus ventas.</p>
          </div>
        </div>
        <button @click="emit('close')" class="text-slate-400 hover:text-white p-2 rounded-xl hover:bg-slate-800 transition-colors">
          ✕
        </button>
      </div>

      <!-- Cuerpo: Grid con Controles a la Izquierda y Preview a la Derecha -->
      <div class="p-6 grid grid-cols-1 lg:grid-cols-12 gap-6 overflow-y-auto flex-1">
        
        <!-- COLUMNA IZQUIERDA: CONTROLES DE PERSONALIZACIÓN -->
        <div class="lg:col-span-7 space-y-4 text-xs">
          
          <!-- Selector de Tipo de Producto -->
          <div class="flex gap-2 p-1 bg-slate-950 rounded-xl border border-slate-800">
            <button 
              @click="flyerProductType = 'streaming'"
              :class="['flex-1 py-2 rounded-lg font-black transition-all flex items-center justify-center gap-1.5', flyerProductType === 'streaming' ? 'bg-amber-400 text-slate-950 shadow' : 'text-slate-400 hover:text-white']"
            >
              <span>🍿</span>
              <span>Streaming & Cuentas</span>
            </button>
            <button 
              @click="flyerProductType = 'physical'"
              :class="['flex-1 py-2 rounded-lg font-black transition-all flex items-center justify-center gap-1.5', flyerProductType === 'physical' ? 'bg-amber-400 text-slate-950 shadow' : 'text-slate-400 hover:text-white']"
            >
              <span>📦</span>
              <span>Tecnología & Físicos ({{ adminStore.physicalProducts.length }})</span>
            </button>
          </div>

          <!-- Si es Streaming -->
          <div v-if="flyerProductType === 'streaming'" class="grid grid-cols-1 sm:grid-cols-2 gap-3 bg-[#020617] p-3.5 rounded-2xl border border-slate-800">
            <div>
              <label class="block font-bold uppercase text-slate-400 mb-1">Plataforma</label>
              <select v-model="selectedPlatform" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-white font-bold outline-none focus:border-amber-400">
                <option v-for="(plat, key) in adminStore.pricingCatalog" :key="key" :value="key">
                  {{ plat.name || key }}
                </option>
              </select>
            </div>
            <div>
              <label class="block font-bold uppercase text-slate-400 mb-1">Plan / Pantallas</label>
              <select v-model="selectedPlanKey" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-white font-bold outline-none focus:border-amber-400">
                <option v-for="(pl, pKey) in currentPlatformData.plans" :key="pKey" :value="pKey">
                  {{ pl.label }} (${{ pl.clientPrice?.toLocaleString() }})
                </option>
              </select>
            </div>
          </div>

          <!-- Si es Físico: Buscador completo + Selector de Categoría + Listado de 364 productos -->
          <div v-else class="space-y-3 bg-[#020617] p-3.5 rounded-2xl border border-slate-800">
            <div class="flex flex-col sm:flex-row gap-2">
              <div class="flex-1">
                <label class="block font-bold uppercase text-slate-400 mb-1">Buscar Producto</label>
                <input 
                  v-model="physicalSearch" 
                  type="text" 
                  placeholder="Buscar por nombre (ej: cortadora, smartwatch, trípode)..."
                  class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2 text-white outline-none focus:border-amber-400"
                >
              </div>
              <div class="sm:w-44">
                <label class="block font-bold uppercase text-slate-400 mb-1">Categoría</label>
                <select v-model="selectedPhysicalCat" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2 text-white outline-none focus:border-amber-400 font-bold">
                  <option value="All">Todas ({{ adminStore.physicalProducts.length }})</option>
                  <option v-for="cat in adminStore.physicalCategories" :key="cat.id" :value="cat.id">
                    {{ cat.name }}
                  </option>
                </select>
              </div>
            </div>

            <div>
              <div class="flex justify-between items-center mb-1">
                <label class="block font-bold uppercase text-slate-400">Seleccionar Producto del Catálogo ({{ filteredPhysicalProducts.length }})</label>
                <span class="text-[10px] font-mono text-slate-500">ID: #{{ selectedPhysicalProduct?.id }}</span>
              </div>
              <select v-model="selectedPhysicalId" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-white font-bold outline-none focus:border-amber-400">
                <option v-for="prod in filteredPhysicalProducts" :key="prod.id" :value="prod.id">
                  {{ prod.name }} — {{ prod.price }}
                </option>
              </select>
            </div>
          </div>

          <!-- Personalización de Textos -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block font-bold uppercase text-slate-400 mb-1">Badge Superior de Impacto</label>
              <input v-model="headlineBadge" list="badgesList" type="text" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2 text-amber-300 font-bold outline-none">
              <datalist id="badgesList">
                <option v-for="b in suggestedBadges" :key="b" :value="b" />
              </datalist>
            </div>
            <div>
              <label class="block font-bold uppercase text-slate-400 mb-1">Precio a Mostrar (ARS)</label>
              <input v-model="customPrice" type="number" :placeholder="`Por defecto: $${displayPrice}`" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2 text-emerald-400 font-mono font-bold outline-none">
            </div>
          </div>

          <!-- Datos de la Tienda y Contacto -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block font-bold uppercase text-slate-400 mb-1">Nombre de tu Tienda</label>
              <input v-model="storeName" type="text" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2 text-white outline-none">
            </div>
            <div>
              <label class="block font-bold uppercase text-slate-400 mb-1">WhatsApp de Pedidos</label>
              <input v-model="contactPhone" type="text" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2 text-white outline-none font-mono">
            </div>
          </div>

          <!-- Puntos Clave / Beneficios -->
          <div class="space-y-1.5 bg-[#020617] p-3 rounded-2xl border border-slate-800">
            <span class="block font-bold uppercase text-slate-400 text-[11px] mb-1">Puntos Clave / Beneficios de la Oferta</span>
            <input v-model="bullet1" type="text" class="w-full bg-slate-950 border border-slate-800 rounded-lg p-1.5 text-slate-200 outline-none text-xs">
            <input v-model="bullet2" type="text" class="w-full bg-slate-950 border border-slate-800 rounded-lg p-1.5 text-slate-200 outline-none text-xs">
            <input v-model="bullet3" type="text" class="w-full bg-slate-950 border border-slate-800 rounded-lg p-1.5 text-slate-200 outline-none text-xs">
          </div>

          <!-- Selector de Estilo / Tema Visual -->
          <div>
            <label class="block font-bold uppercase text-slate-400 mb-1.5">Estilo Visual</label>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
              <button 
                @click="flyerTheme = 'cinema'"
                :class="['p-2 rounded-xl border text-center font-bold text-[11px] transition-all', flyerTheme === 'cinema' ? 'bg-red-950/40 border-red-500 text-red-300' : 'bg-slate-950 border-slate-800 text-slate-400']"
              >
                🎬 Cinema Dark
              </button>
              <button 
                @click="flyerTheme = 'cyber'"
                :class="['p-2 rounded-xl border text-center font-bold text-[11px] transition-all', flyerTheme === 'cyber' ? 'bg-purple-950/40 border-purple-500 text-purple-300' : 'bg-slate-950 border-slate-800 text-slate-400']"
              >
                ⚡ Cyber Neon
              </button>
              <button 
                @click="flyerTheme = 'emerald'"
                :class="['p-2 rounded-xl border text-center font-bold text-[11px] transition-all', flyerTheme === 'emerald' ? 'bg-emerald-950/40 border-emerald-500 text-emerald-300' : 'bg-slate-950 border-slate-800 text-slate-400']"
              >
                💎 Emerald Deal
              </button>
              <button 
                @click="flyerTheme = 'blue'"
                :class="['p-2 rounded-xl border text-center font-bold text-[11px] transition-all', flyerTheme === 'blue' ? 'bg-sky-950/40 border-sky-500 text-sky-300' : 'bg-slate-950 border-slate-800 text-slate-400']"
              >
                ✨ Deep Blue
              </button>
            </div>
          </div>

          <!-- Botones de Acción -->
          <div class="flex flex-col sm:flex-row gap-2.5 pt-2">
            <button 
              @click="downloadFlyerImage" 
              :disabled="isExporting"
              class="flex-1 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-400 hover:to-orange-400 text-slate-950 font-black py-3 rounded-xl shadow-lg transition-all flex items-center justify-center gap-2 text-xs cursor-pointer disabled:opacity-50"
            >
              <span>{{ isExporting ? '⏳ Generando Flyer HD...' : '⬇️ Descargar Flyer (PNG 9:16)' }}</span>
            </button>
            <button 
              @click="copySocialCopy" 
              class="bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold px-4 py-3 rounded-xl border border-slate-700 transition-all flex items-center justify-center gap-1.5"
              title="Copiar texto listo para pegar en la descripción de la historia o estado"
            >
              <span>📋 Copiar Texto</span>
            </button>
            <button 
              @click="shareDirectWhatsApp" 
              class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold px-4 py-3 rounded-xl shadow transition-all flex items-center justify-center gap-1.5"
              title="Probar enlace directo de pedido en WhatsApp"
            >
              <span>💬 Test WhatsApp</span>
            </button>
          </div>
        </div>

        <!-- COLUMNA DERECHA: VISTA PREVIA DEL FLYER (FORMATO STORIES 9:16) -->
        <div class="lg:col-span-5 flex flex-col items-center justify-center bg-slate-950/80 p-4 rounded-2xl border border-slate-800">
          <span class="text-[11px] font-mono text-slate-400 mb-2 font-bold uppercase tracking-wider">
            Vista Previa de Estado / Story
          </span>

          <!-- CONTENEDOR FLYER CAPTURABLE (Ratio 9:16) -->
          <div 
            ref="flyerPreviewRef" 
            :class="[
              'w-full max-w-[320px] aspect-[9/16] rounded-3xl p-5 flex flex-col justify-between relative overflow-hidden shadow-2xl border transition-all text-white select-none',
              flyerTheme === 'cinema' ? 'bg-gradient-to-b from-black via-red-950/40 to-slate-950 border-red-500/30' :
              flyerTheme === 'cyber' ? 'bg-gradient-to-b from-slate-950 via-purple-950/50 to-cyan-950/30 border-purple-500/40' :
              flyerTheme === 'emerald' ? 'bg-gradient-to-b from-slate-950 via-emerald-950/40 to-amber-950/30 border-emerald-500/40' :
              'bg-gradient-to-b from-slate-950 via-sky-950/50 to-blue-950/40 border-sky-500/40'
            ]"
          >
            <!-- Efectos de Luces y Gradientes de fondo -->
            <div class="absolute -top-12 -right-12 w-40 h-40 bg-amber-500/15 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-12 -left-12 w-40 h-40 bg-purple-500/15 rounded-full blur-3xl pointer-events-none"></div>

            <!-- PARTE SUPERIOR: Marca de la Tienda & Badge -->
            <div class="relative z-10 text-center space-y-2">
              <div class="flex items-center justify-between border-b border-white/10 pb-2">
                <span class="text-[11px] font-mono font-black uppercase tracking-widest text-amber-300 truncate max-w-[190px]">
                  {{ storeName }}
                </span>
                <span class="text-[9px] font-bold bg-white/10 px-2 py-0.5 rounded-full border border-white/15 text-slate-300">
                  OFICIAL
                </span>
              </div>

              <!-- Badge Dinámico -->
              <div class="inline-block bg-gradient-to-r from-amber-500 to-orange-500 text-slate-950 text-[11px] font-black px-3.5 py-1 rounded-full shadow-lg uppercase tracking-wider">
                {{ headlineBadge }}
              </div>
            </div>

            <!-- PARTE CENTRAL: Imagen / Icono y Titular -->
            <div class="relative z-10 text-center space-y-2.5 my-auto">
              <!-- Icono / Imagen del Producto -->
              <div class="w-24 h-24 mx-auto rounded-2xl bg-white/5 border border-white/15 flex items-center justify-center p-2 backdrop-blur-md shadow-inner overflow-hidden">
                <img 
                  v-if="flyerProductType === 'physical' && (productImageCORS || selectedPhysicalProduct?.image)" 
                  :src="productImageCORS || selectedPhysicalProduct.image" 
                  :alt="displayTitle"
                  class="w-full h-full object-contain rounded-xl bg-white"
                  crossorigin="anonymous"
                >
                <div v-else class="text-4xl">
                  {{ selectedPlatform === 'Netflix' ? '🍿' : selectedPlatform === 'Disney+' ? '✨' : selectedPlatform === 'Max' ? '🎬' : selectedPlatform === 'IPTV' ? '📺' : selectedPlatform === 'Spotify' ? '🎵' : '⚡' }}
                </div>
              </div>

              <div>
                <h2 class="text-xl font-black tracking-tight leading-tight drop-shadow-md text-white line-clamp-2">
                  {{ displayTitle }}
                </h2>
                <p class="text-xs font-semibold text-amber-300/90 font-mono mt-0.5 truncate">
                  {{ displaySubtitle }}
                </p>
              </div>

              <!-- Precio Gigante -->
              <div class="py-2 px-3.5 rounded-2xl bg-black/50 border border-white/10 backdrop-blur-md inline-block shadow-xl">
                <span class="text-[9px] uppercase font-bold text-slate-400 tracking-wider block">PRECIO EXCLUSIVO</span>
                <div class="flex items-baseline justify-center gap-1">
                  <span class="text-sm font-black text-amber-400">$</span>
                  <span class="text-2xl font-black tracking-tight text-white">{{ displayPrice }}</span>
                  <span class="text-[11px] font-bold text-slate-400 font-mono">ARS</span>
                </div>
              </div>

              <!-- Bullets / Beneficios -->
              <div class="space-y-0.5 text-left text-[11px] px-2 font-medium text-slate-200">
                <div class="flex items-center gap-1.5 truncate">
                  <span class="text-emerald-400 font-bold">✓</span>
                  <span class="truncate">{{ bullet1 }}</span>
                </div>
                <div class="flex items-center gap-1.5 truncate">
                  <span class="text-emerald-400 font-bold">✓</span>
                  <span class="truncate">{{ bullet2 }}</span>
                </div>
                <div class="flex items-center gap-1.5 truncate">
                  <span class="text-emerald-400 font-bold">✓</span>
                  <span class="truncate">{{ bullet3 }}</span>
                </div>
              </div>
            </div>

            <!-- PARTE INFERIOR: Llamada a la Acción de WhatsApp -->
            <div class="relative z-10 text-center space-y-1 pt-2 border-t border-white/10">
              <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider block">
                ⚡ ¡Pide el tuyo ahora antes de que se agote!
              </span>
              <div class="bg-emerald-500 text-slate-950 font-black py-2 px-3 rounded-xl flex items-center justify-center gap-1.5 shadow-lg text-xs">
                <span>💬 WhatsApp:</span>
                <span class="font-mono text-xs tracking-tight">{{ contactPhone }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

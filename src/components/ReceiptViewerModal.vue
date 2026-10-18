<script setup>
import { ref, watch } from 'vue'
import { getReceipt, deleteReceipt } from '../services/receiptCloudStorage'
import { useToastStore } from '../store/toast'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  order: {
    type: Object,
    default: null
  },
  canConfirmClose: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['close', 'confirmed-close', 'receipt-purged'])

const toast = useToastStore()
const loading = ref(false)
const receiptData = ref(null)
const isZoomed = ref(false)

const loadReceipt = async () => {
  if (!props.order?.id) {
    receiptData.value = null
    return
  }
  loading.value = true
  isZoomed.value = false
  try {
    const data = await getReceipt(props.order.id)
    receiptData.value = data
  } catch (err) {
    console.warn('Error al cargar comprobante:', err)
    receiptData.value = null
  } finally {
    loading.value = false
  }
}

watch(() => props.show, (newVal) => {
  if (newVal && props.order) {
    loadReceipt()
  } else {
    receiptData.value = null
  }
})

watch(() => props.order?.id, () => {
  if (props.show && props.order) {
    loadReceipt()
  }
})

const handlePurgeReceiptOnly = async () => {
  if (!props.order?.id) return
  if (!confirm('¿Deseas eliminar este archivo de comprobante de la mini nube? Esta acción liberará espacio de almacenamiento de forma inmediata.')) {
    return
  }

  const success = await deleteReceipt(props.order.id)
  if (success) {
    receiptData.value = null
    emit('receipt-purged', props.order.id)
    toast.add('Comprobante eliminado de la mini nube con éxito', 'success')
  } else {
    toast.add('No se pudo eliminar el comprobante', 'error')
  }
}

const handleConfirmClose = () => {
  if (!props.order) return
  emit('confirmed-close', props.order)
}

const downloadReceipt = () => {
  if (!receiptData.value?.dataUrl) return
  const a = document.createElement('a')
  a.href = receiptData.value.dataUrl
  a.download = receiptData.value.fileName || `comprobante-${props.order?.id || 'orden'}`
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  toast.add('Descargando comprobante...', 'info')
}
</script>

<template>
  <div 
    v-if="show" 
    class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-5 bg-black/85 backdrop-blur-md animate-in fade-in duration-200"
  >
    <div class="bg-slate-900 border border-slate-700/80 w-full max-w-2xl rounded-3xl p-5 sm:p-7 shadow-2xl relative space-y-4 max-h-[92vh] flex flex-col text-white">
      
      <!-- Encabezado con información del pedido -->
      <div class="flex justify-between items-start border-b border-slate-800 pb-3.5 shrink-0">
        <div class="space-y-1">
          <div class="flex items-center gap-2">
            <span class="text-xl">☁️</span>
            <h3 class="text-base sm:text-lg font-black text-white">
              Comprobante de Pago en Mini Nube
            </h3>
            <span class="text-[10px] font-mono font-bold bg-sky-500/20 text-sky-300 border border-sky-500/30 px-2 py-0.5 rounded-full">
              {{ order?.id }}
            </span>
          </div>
          <p class="text-xs text-slate-400">
            Cliente: <strong class="text-white">{{ order?.customerName }}</strong>
            <span v-if="order?.customerPhone" class="text-slate-500"> • {{ order.customerPhone }}</span>
            <span v-if="order?.platform" class="text-sky-400"> • {{ order.platform }}</span>
            <span v-if="order?.clientPrice || order?.totalAmount" class="text-emerald-400 font-bold">
              • ${{ ((order.clientPrice || order.totalAmount) || 0).toLocaleString() }} ARS
            </span>
          </p>
        </div>
        
        <button 
          @click="emit('close')" 
          class="text-slate-400 hover:text-white p-2 rounded-xl hover:bg-slate-800 transition-colors cursor-pointer text-lg"
          title="Cerrar modal"
        >
          ✕
        </button>
      </div>

      <!-- Estado: Cargando -->
      <div v-if="loading" class="py-16 text-center space-y-3">
        <div class="w-10 h-10 border-4 border-brand-accent border-t-transparent rounded-full animate-spin mx-auto"></div>
        <p class="text-xs text-slate-400 font-medium">Recuperando comprobante desde la mini nube...</p>
      </div>

      <!-- Estado: Comprobante encontrado -->
      <div v-else-if="receiptData" class="flex-1 overflow-y-auto space-y-3.5 pr-1 custom-scrollbar">
        
        <!-- Barra de estado del archivo -->
        <div class="bg-slate-950/80 p-3 rounded-2xl border border-slate-800 flex flex-wrap justify-between items-center gap-2 text-xs">
          <div class="flex items-center gap-2">
            <span class="text-base">{{ receiptData.fileType === 'application/pdf' ? '📄' : '🖼️' }}</span>
            <div>
              <p class="font-bold text-slate-200 truncate max-w-xs">{{ receiptData.fileName }}</p>
              <p class="text-[10px] text-slate-400">
                {{ receiptData.fileType === 'application/pdf' ? 'Documento PDF' : 'Imagen JPG/PNG' }} • 
                {{ Math.round((receiptData.fileSize || 0) / 1024) }} KB • 
                Subido: {{ new Date(receiptData.uploadedAt).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}
              </p>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <button 
              @click="downloadReceipt" 
              class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-white rounded-xl font-bold text-xs transition-colors flex items-center gap-1.5 cursor-pointer"
            >
              <span>📥</span>
              <span>Descargar</span>
            </button>
            <button 
              v-if="receiptData.fileType !== 'application/pdf'"
              @click="isZoomed = !isZoomed" 
              class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-white rounded-xl font-bold text-xs transition-colors flex items-center gap-1.5 cursor-pointer"
            >
              <span>{{ isZoomed ? '🔍 Ajustar' : '🔍 Ampliar' }}</span>
            </button>
          </div>
        </div>

        <!-- Renderizado de Imagen -->
        <div 
          v-if="receiptData.fileType !== 'application/pdf'" 
          class="bg-black/60 rounded-2xl p-2 border border-slate-800/80 flex items-center justify-center overflow-hidden min-h-[260px] max-h-[50vh]"
        >
          <img 
            :src="receiptData.dataUrl" 
            :alt="receiptData.fileName" 
            :class="[
              'rounded-xl transition-all duration-300 object-contain',
              isZoomed ? 'w-full max-h-none cursor-zoom-out' : 'max-h-[48vh] cursor-zoom-in'
            ]"
            @click="isZoomed = !isZoomed"
          />
        </div>

        <!-- Renderizado de PDF -->
        <div v-else class="space-y-2">
          <div class="h-[46vh] rounded-2xl overflow-hidden border border-slate-700/80 bg-slate-950 relative">
            <iframe 
              :src="receiptData.dataUrl" 
              class="w-full h-full border-0"
              title="Vista previa del comprobante PDF"
            ></iframe>
          </div>
          <p class="text-[11px] text-slate-400 text-center">
            Si tu navegador bloquea la vista previa interna de PDFs, puedes 
            <button @click="downloadReceipt" class="text-brand-accent underline font-bold cursor-pointer">descargarlo directamente aquí</button>.
          </p>
        </div>

        <!-- Alerta explicativa de Mini Nube -->
        <div class="bg-sky-950/30 border border-sky-500/25 p-3 rounded-xl flex items-start gap-2.5 text-[11px] text-sky-200">
          <span class="text-sm shrink-0">☁️</span>
          <div>
            <strong class="font-bold text-sky-300 block">Almacenamiento Temporal en Mini Nube:</strong>
            <span>
              Este comprobante permanecerá accesible para el revendedor y el proveedor hasta verificar el pago. 
              Al confirmar el cierre de la orden, se eliminará automáticamente de la memoria para que no ocupe espacio.
            </span>
          </div>
        </div>
      </div>

      <!-- Estado: No hay comprobante cargado o ya fue depurado -->
      <div v-else class="py-12 text-center space-y-3 bg-slate-950/50 rounded-2xl border border-slate-800 p-6">
        <span class="text-4xl">🗑️</span>
        <h4 class="font-bold text-slate-300 text-sm">Comprobante no disponible o ya depurado</h4>
        <p class="text-xs text-slate-400 max-w-md mx-auto leading-relaxed">
          El comprobante de este pedido ya fue verificado y eliminado de la mini nube al confirmar el cierre de la entrega para optimizar el almacenamiento.
        </p>
        <div v-if="order?.voucher" class="font-mono text-xs text-brand-accent pt-1">
          Referencia registrada: {{ order.voucher }}
        </div>
      </div>

      <!-- Footer con Acciones -->
      <div class="pt-3 border-t border-slate-800 flex flex-wrap gap-2.5 justify-between items-center shrink-0">
        <button 
          v-if="receiptData"
          @click="handlePurgeReceiptOnly" 
          class="px-4 py-2.5 bg-red-950/40 hover:bg-red-900/60 text-red-300 border border-red-500/30 rounded-xl text-xs font-bold transition-colors flex items-center gap-1.5 cursor-pointer"
          title="Eliminar este archivo de comprobante de la mini nube para liberar espacio"
        >
          <span>🗑️</span>
          <span>Eliminar de la Nube</span>
        </button>

        <div class="flex items-center gap-2 ml-auto">
          <button 
            @click="emit('close')" 
            class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white rounded-xl text-xs font-bold transition-colors cursor-pointer"
          >
            Cerrar
          </button>

          <button 
            v-if="canConfirmClose && order?.status !== 'Dispatched' && order?.status !== 'Confirmado' && order?.status !== 'Aprobado'"
            @click="handleConfirmClose" 
            class="px-5 py-2.5 bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-slate-950 font-black rounded-xl text-xs transition-all shadow-lg shadow-emerald-500/20 flex items-center gap-1.5 cursor-pointer"
          >
            <span>✓</span>
            <span>Confirmar Cierre & Entregar</span>
          </button>
        </div>
      </div>

    </div>
  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 5px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #334155;
  border-radius: 4px;
}
</style>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAdminStore, useResellerStore } from '../store/admin'
import { useToastStore } from '../store/toast'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue', 'profileSaved'])

const router = useRouter()
const adminStore = useAdminStore()
const resellerStore = useResellerStore()
const toast = useToastStore()

const STORAGE_PROFILE_KEY = 'streampro_user_profile'

const profile = ref({
  name: '',
  phone: '',
  email: '',
  address: '',
  pin: '',
  role: 'client'
})

const isEditing = ref(false)

const loadSavedProfile = () => {
  try {
    const raw = localStorage.getItem(STORAGE_PROFILE_KEY)
    if (raw) {
      profile.value = JSON.parse(raw)
      isEditing.value = false
    } else {
      isEditing.value = true
    }
  } catch (e) {
    isEditing.value = true
  }
}

onMounted(() => {
  loadSavedProfile()
})

const isSaved = computed(() => Boolean(profile.value.name && profile.value.phone))

const clientOrders = computed(() => {
  if (!profile.value.phone && !profile.value.name) return []
  const cleanPhone = profile.value.phone.replace(/[^0-9]/g, '')
  const streaming = adminStore.orders.filter(o => 
    (o.customerPhone && o.customerPhone.replace(/[^0-9]/g, '') === cleanPhone) ||
    (o.customerName && o.customerName.toLowerCase() === profile.value.name.toLowerCase())
  )
  const physical = adminStore.physicalOrders.filter(o => 
    (o.customerPhone && o.customerPhone.replace(/[^0-9]/g, '') === cleanPhone) ||
    (o.customerName && o.customerName.toLowerCase() === profile.value.name.toLowerCase())
  )
  return [...streaming, ...physical]
})

const closeModal = () => {
  emit('update:modelValue', false)
}

const saveProfile = () => {
  if (!profile.value.name.trim()) {
    toast.add('Ingresa tu nombre completo', 'error')
    return
  }
  if (!profile.value.phone.trim()) {
    toast.add('Ingresa tu WhatsApp o teléfono de contacto', 'error')
    return
  }

  // Si no tiene PIN, generar uno de 4 dígitos
  if (!profile.value.pin) {
    profile.value.pin = String(Math.floor(1000 + Math.random() * 9000))
  }

  // Guardar en localStorage
  try {
    localStorage.setItem(STORAGE_PROFILE_KEY, JSON.stringify(profile.value))
  } catch (e) {}

  // Registrar en la base central de usuarios
  adminStore.registerOrUpdateUser({
    name: profile.value.name.trim(),
    phone: profile.value.phone.trim(),
    email: profile.value.email.trim(),
    address: profile.value.address.trim(),
    pin: profile.value.pin,
    role: profile.value.role || 'client'
  })

  isEditing.value = false
  toast.add('¡Perfil guardado! Tus compras y envíos se completarán automáticamente.', 'success')
  emit('profileSaved', profile.value)
}

const goToVault = () => {
  closeModal()
  router.push('/portal')
}

const goToReseller = () => {
  closeModal()
  router.push('/crm')
}

const openSupportWhatsApp = () => {
  closeModal()
  const targetPhone = adminStore.providerBankInfo?.whatsapp || '+54 9 11 0000-0000'
  const cleanPhone = targetPhone.replace(/[^0-9]/g, '')
  const msg = `¡Hola StreamePro! 👋 Necesito soporte o información sobre mi cuenta (${profile.value.name || 'Cliente'}).`
  window.open(`https://wa.me/${cleanPhone}?text=${encodeURIComponent(msg)}`, '_blank')
}

const handleLogout = () => {
  try {
    localStorage.removeItem(STORAGE_PROFILE_KEY)
  } catch (e) {}
  profile.value = {
    name: '',
    phone: '',
    email: '',
    address: '',
    pin: '',
    role: 'client'
  }
  isEditing.value = true
  toast.add('Sesión cerrada en este dispositivo', 'info')
}
</script>

<template>
  <div v-if="modelValue" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md animate-fade-in">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl relative overflow-hidden space-y-6 max-h-[90vh] overflow-y-auto">
      
      <!-- Fondo decorativo -->
      <div class="absolute -right-12 -top-12 w-40 h-40 bg-sky-500/10 rounded-full blur-3xl pointer-events-none"></div>

      <!-- Header del Modal -->
      <div class="flex justify-between items-start border-b border-slate-800/80 pb-4 relative z-10">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-sky-400 to-blue-600 flex items-center justify-center text-white text-2xl shadow-lg shadow-sky-500/20">
            👤
          </div>
          <div>
            <h3 class="text-xl font-black text-white">Mi Cuenta StreamePro</h3>
            <p class="text-xs text-slate-400">Facilidad de navegación y compras rápidas</p>
          </div>
        </div>
        <button @click="closeModal" class="text-slate-400 hover:text-white p-2 rounded-xl hover:bg-slate-800 transition-colors">
          ✕
        </button>
      </div>

      <!-- VISTA: Perfil Guardado Activo -->
      <div v-if="isSaved && !isEditing" class="space-y-5">
        <div class="bg-slate-950/60 p-4 rounded-2xl border border-slate-800 space-y-3">
          <div class="flex justify-between items-center">
            <span class="text-xs text-slate-400 font-bold uppercase tracking-wider">Usuario Registrado</span>
            <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 font-bold border border-emerald-500/30">
              Activo
            </span>
          </div>
          <div>
            <h4 class="text-lg font-black text-white">{{ profile.name }}</h4>
            <p class="text-xs font-mono text-sky-400">{{ profile.phone }}</p>
            <p v-if="profile.email" class="text-xs text-slate-400">{{ profile.email }}</p>
            <p v-if="profile.address" class="text-xs text-slate-300 mt-1">📍 {{ profile.address }}</p>
          </div>

          <div class="flex items-center justify-between pt-2 border-t border-slate-800 text-xs">
            <span class="text-slate-400">Tu PIN de Bóveda:</span>
            <span class="font-mono font-black text-amber-300 bg-amber-500/10 px-2 py-0.5 rounded border border-amber-500/20 tracking-widest">
              {{ profile.pin }}
            </span>
          </div>
        </div>

        <!-- Accesos Rápidos de Navegación -->
        <div class="grid grid-cols-2 gap-3">
          <button 
            @click="goToVault" 
            class="bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-400 hover:to-blue-500 text-white font-bold p-3.5 rounded-2xl shadow-lg transition-all flex flex-col items-center justify-center gap-1 text-center"
          >
            <span class="text-xl">🔐</span>
            <span class="text-xs font-black">Mi Bóveda</span>
            <span class="text-[10px] text-sky-100 opacity-80">Ver cuentas activas</span>
          </button>

          <button 
            v-if="resellerStore.isAuthenticated"
            @click="goToReseller" 
            class="bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-400 hover:to-orange-400 text-slate-950 font-bold p-3.5 rounded-2xl shadow-lg transition-all flex flex-col items-center justify-center gap-1 text-center cursor-pointer"
          >
            <span class="text-xl">💼</span>
            <span class="text-xs font-black">Panel Distribuidor</span>
            <span class="text-[10px] text-slate-900 font-bold">Sesión activa</span>
          </button>

          <button 
            v-else
            @click="openSupportWhatsApp" 
            class="bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold p-3.5 rounded-2xl shadow-lg transition-all flex flex-col items-center justify-center gap-1 text-center cursor-pointer"
          >
            <span class="text-xl">💬</span>
            <span class="text-xs font-black">Soporte WhatsApp</span>
            <span class="text-[10px] text-emerald-100 opacity-90">Atención oficial 24/7</span>
          </button>
        </div>

        <!-- Historial de Pedidos Recientes -->
        <div v-if="clientOrders.length > 0" class="space-y-2">
          <h5 class="text-xs font-bold uppercase text-slate-400">Tus Pedidos Recientes ({{ clientOrders.length }})</h5>
          <div class="space-y-1.5 max-h-40 overflow-y-auto pr-1">
            <div v-for="ord in clientOrders.slice(0, 5)" :key="ord.id" class="p-2.5 rounded-xl bg-slate-950/40 border border-slate-800/80 flex justify-between items-center text-xs">
              <div>
                <span class="font-bold text-white">{{ ord.platform || (ord.items?.[0]?.name) || ord.id }}</span>
                <span class="block text-[10px] text-slate-500 font-mono">{{ ord.customerNumber || ord.id }}</span>
              </div>
              <span class="px-2 py-0.5 rounded text-[10px] font-bold" :class="ord.status === 'Delivered' || ord.status === 'Confirmado' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-amber-500/20 text-amber-300'">
                {{ ord.status || 'En Proceso' }}
              </span>
            </div>
          </div>
        </div>

        <!-- Botones de Acción -->
        <div class="flex gap-2 pt-2">
          <button 
            @click="isEditing = true" 
            class="flex-1 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white font-bold py-2.5 rounded-xl text-xs transition-colors border border-slate-700"
          >
            ✏️ Modificar Datos
          </button>
          <button 
            @click="handleLogout" 
            class="px-4 bg-red-950/40 hover:bg-red-900/60 text-red-300 font-bold py-2.5 rounded-xl text-xs transition-colors border border-red-800/40"
          >
            Cerrar Sesión
          </button>
        </div>
      </div>

      <!-- VISTA: Formulario de Registro / Modificación -->
      <form v-else @submit.prevent="saveProfile" class="space-y-4">
        <div>
          <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Nombre Completo *</label>
          <input 
            v-model="profile.name" 
            type="text" 
            placeholder="Ej: Cristian Gómez" 
            required 
            class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-white focus:outline-none focus:border-sky-500 text-sm"
          >
        </div>

        <div>
          <label class="block text-xs font-bold uppercase text-slate-300 mb-1">WhatsApp / Teléfono *</label>
          <input 
            v-model="profile.phone" 
            type="tel" 
            placeholder="Ej: +54 9 11 1234-5678" 
            required 
            class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-white focus:outline-none focus:border-sky-500 text-sm font-mono"
          >
          <span class="text-[10px] text-slate-500">Necesario para coordinar la entrega y asistencia directa.</span>
        </div>

        <div>
          <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Correo Electrónico (Opcional)</label>
          <input 
            v-model="profile.email" 
            type="email" 
            placeholder="correo@ejemplo.com" 
            class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-white focus:outline-none focus:border-sky-500 text-sm"
          >
        </div>

        <div>
          <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Dirección de Envío (Para Productos Físicos)</label>
          <input 
            v-model="profile.address" 
            type="text" 
            placeholder="Calle, Número, Ciudad, Provincia" 
            class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-white focus:outline-none focus:border-sky-500 text-sm"
          >
        </div>

        <div>
          <label class="block text-xs font-bold uppercase text-slate-300 mb-1">PIN de Seguridad (Bóveda)</label>
          <input 
            v-model="profile.pin" 
            type="text" 
            maxlength="6" 
            placeholder="4 dígitos (ej: 1234)" 
            class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-amber-400 focus:outline-none focus:border-amber-500 text-sm font-mono tracking-widest"
          >
          <span class="text-[10px] text-slate-500">Clave personal para consultar tus cuentas en Mi Bóveda.</span>
        </div>

        <div class="pt-2 flex gap-3">
          <button 
            type="button" 
            v-if="isSaved" 
            @click="isEditing = false" 
            class="flex-1 bg-slate-800 hover:bg-slate-700 text-slate-300 py-3 rounded-xl text-xs font-bold transition-colors"
          >
            Cancelar
          </button>
          <button 
            type="submit" 
            class="flex-1 bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-400 hover:to-blue-500 text-white font-black py-3 rounded-xl text-xs shadow-lg shadow-sky-500/20 transition-all"
          >
            Guardar & Recordar en este Dispositivo
          </button>
        </div>
      </form>

    </div>
  </div>
</template>

<style scoped>
.animate-fade-in {
  animation: fadeIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes fadeIn {
  from { opacity: 0; transform: scale(0.97); }
  to { opacity: 1; transform: scale(1); }
}
</style>

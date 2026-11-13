<template>
  <div v-if="show" class="fixed inset-0 z-[120] bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-3 sm:p-5 overflow-y-auto">
    <div class="max-w-6xl w-full bg-[#030712] border border-slate-800 rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[94vh]">
      
      <!-- HEADER -->
      <div class="px-5 py-4 border-b border-slate-800 flex items-center justify-between bg-slate-900/40">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-pink-500 via-purple-600 to-indigo-500 flex items-center justify-center text-xl shadow-lg shadow-pink-500/20">
            🎬
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h2 class="text-base sm:text-lg font-black text-white flex items-center gap-2">
                Estudio Automático de Reels, TikTok &amp; Estados 9:16
              </h2>
              <span class="text-[10px] uppercase font-black px-2 py-0.5 rounded-full bg-pink-500/20 text-pink-300 border border-pink-500/30">
                1080 × 1920 HD
              </span>
            </div>
            <p class="text-xs text-slate-400 mt-0.5">
              Crea contenido vertical animado o imágenes HD con tu marca y cupones listos para publicar en Instagram, TikTok y WhatsApp.
            </p>
          </div>
        </div>

        <button 
          type="button" 
          @click="closeModal" 
          class="text-slate-400 hover:text-white p-2 rounded-xl hover:bg-slate-800 transition text-lg"
          title="Cerrar"
        >
          ✕
        </button>
      </div>

      <!-- MAIN STUDIO BODY (2 COLUMNAS) -->
      <div class="flex-1 overflow-y-auto p-4 sm:p-6 grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- COLUMNA IZQUIERDA: PREVIEW 9:16 Y ACCIONES DE EXPORTACIÓN -->
        <div class="lg:col-span-5 flex flex-col items-center space-y-4">
          <div class="relative w-full max-w-[320px] aspect-[9/16] rounded-2xl overflow-hidden shadow-2xl border border-white/10 bg-slate-950 flex items-center justify-center group">
            
            <!-- Canvas 9:16 (1080x1920) -->
            <canvas 
              ref="canvasRef" 
              width="1080" 
              height="1920" 
              class="w-full h-full object-contain"
            ></canvas>

            <!-- Overlay de Grabación en Vivo -->
            <div 
              v-if="isRecording" 
              class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm flex flex-col items-center justify-center p-6 text-center animate-in fade-in"
            >
              <div class="w-16 h-16 rounded-full border-4 border-pink-500 border-t-transparent animate-spin mb-4"></div>
              <div class="text-sm font-black text-white uppercase tracking-wider flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-rose-500 animate-ping"></span>
                <span>Grabando Reel 9:16...</span>
              </div>
              <p class="text-xs text-slate-300 mt-1">Renderizando animación suave a 30 FPS</p>
              
              <!-- Barra de progreso -->
              <div class="w-full bg-slate-800 h-2.5 rounded-full overflow-hidden mt-4 border border-white/10">
                <div 
                  class="bg-gradient-to-r from-pink-500 to-indigo-500 h-full transition-all duration-100" 
                  :style="{ width: recordingProgress + '%' }"
                ></div>
              </div>
              <span class="text-xs font-mono text-pink-300 mt-2 font-bold">{{ recordingSeconds }}s / 5.0s</span>
            </div>

            <!-- Badges flotantes sobre la vista previa -->
            <div class="absolute top-2.5 left-2.5 bg-black/60 backdrop-blur-md px-2 py-0.5 rounded-lg border border-white/10 text-[9px] font-mono text-slate-300 flex items-center gap-1">
              <span>📱 Formato Vertical 9:16</span>
            </div>
            <div class="absolute top-2.5 right-2.5 bg-black/60 backdrop-blur-md px-2 py-0.5 rounded-lg border border-white/10 text-[9px] font-mono text-emerald-400 flex items-center gap-1">
              <span>● En Vivo</span>
            </div>
          </div>

          <!-- CONTROLES RÁPIDOS BAJO EL CANVAS -->
          <div class="w-full max-w-[320px] space-y-2.5">
            <!-- Botón 1: Grabar Video Reel (5s) -->
            <button
              type="button"
              @click="recordVideoReel"
              :disabled="isRecording || isExportingImage"
              class="w-full py-3 px-4 rounded-xl font-black text-xs transition-all shadow-lg flex items-center justify-center gap-2 cursor-pointer bg-gradient-to-r from-pink-500 via-purple-600 to-indigo-600 hover:from-pink-400 hover:to-indigo-500 text-white shadow-pink-500/25 active:scale-98 disabled:opacity-50"
            >
              <span>🎬</span>
              <span>{{ isRecording ? 'Grabando Video...' : 'Grabar Video Reel / TikTok (5s)' }}</span>
            </button>

            <!-- Botón 2: Descargar Imagen HD (1080x1920) -->
            <div class="grid grid-cols-2 gap-2">
              <button
                type="button"
                @click="downloadImagePng"
                :disabled="isRecording || isExportingImage"
                class="py-2.5 px-3 rounded-xl font-black text-xs transition-all shadow border border-slate-700 bg-slate-900 hover:bg-slate-800 text-white flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-50"
              >
                <span>📸</span>
                <span>Descargar PNG</span>
              </button>
              
              <button
                type="button"
                @click="shareDirect"
                :disabled="isRecording || isExportingImage"
                class="py-2.5 px-3 rounded-xl font-black text-xs transition-all shadow border border-emerald-500/40 bg-emerald-600/20 hover:bg-emerald-600 text-emerald-300 hover:text-white flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-50"
              >
                <span>📲</span>
                <span>Compartir</span>
              </button>
            </div>

            <!-- Toggle de animación continua -->
            <button
              type="button"
              @click="isAnimated = !isAnimated"
              class="w-full py-2 px-3 rounded-xl text-[11px] font-semibold text-slate-400 hover:text-white bg-slate-900/60 hover:bg-slate-900 border border-slate-800 transition flex items-center justify-center gap-1.5 cursor-pointer"
            >
              <span>{{ isAnimated ? '⏸️ Pausar Animación' : '▶️ Activar Animación en Pantalla' }}</span>
            </button>
          </div>
        </div>

        <!-- COLUMNA DERECHA: CONFIGURADOR & GUIONES -->
        <div class="lg:col-span-7 space-y-5">
          
          <!-- PESTAÑAS DEL ESTUDIO -->
          <div class="flex gap-2 border-b border-slate-800 pb-3">
            <button
              type="button"
              @click="studioTab = 'content'"
              :class="['px-4 py-2 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-1.5', studioTab === 'content' ? 'bg-pink-500 text-white shadow' : 'bg-slate-950 text-slate-300 border border-slate-800 hover:bg-slate-800']"
            >
              <span>🎨 Diseño &amp; Contenido</span>
            </button>
            <button
              type="button"
              @click="studioTab = 'script'"
              :class="['px-4 py-2 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-1.5', studioTab === 'script' ? 'bg-purple-600 text-white shadow' : 'bg-slate-950 text-slate-300 border border-slate-800 hover:bg-slate-800']"
            >
              <span>🎙️ Guión para TikTok &amp; Reels</span>
            </button>
          </div>

          <!-- TAB 1: DISEÑO Y CONTENIDO -->
          <div v-if="studioTab === 'content'" class="space-y-4">
            
            <!-- SELECTOR DE RUBRO / TIPO DE CONTENIDO -->
            <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 space-y-3">
              <label class="text-[11px] uppercase font-bold text-slate-400 block tracking-wider">
                1. ¿Qué querés promocionar en tu Reel / Estado?
              </label>
              <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                <button
                  v-for="mode in contentModes"
                  :key="mode.id"
                  type="button"
                  @click="selectContentMode(mode.id)"
                  :class="[
                    'p-2.5 rounded-xl border text-left transition flex flex-col justify-between gap-1 cursor-pointer',
                    selectedMode === mode.id
                      ? 'bg-pink-500/15 border-pink-500 text-white'
                      : 'bg-slate-900/60 border-slate-800 text-slate-300 hover:border-slate-700'
                  ]"
                >
                  <span class="text-lg">{{ mode.icon }}</span>
                  <div>
                    <span class="text-xs font-bold block">{{ mode.label }}</span>
                    <span class="text-[10px] text-slate-400 block">{{ mode.desc }}</span>
                  </div>
                </button>
              </div>
            </div>

            <!-- SELECTOR ESPECÍFICO DEL PRODUCTO SEGÚN EL MODO -->
            <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 space-y-3">
              
              <!-- MODO STREAMING -->
              <template v-if="selectedMode === 'streaming'">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div>
                    <label class="text-[10px] font-bold uppercase text-slate-400 block mb-1">Plataforma</label>
                    <select 
                      v-model="pickedStreamingKey" 
                      @change="onStreamingServiceChange" 
                      class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-xs text-white font-semibold outline-none"
                    >
                      <option v-for="(cat, key) in adminStore.pricingCatalog" :key="key" :value="key">
                        {{ key }}
                      </option>
                    </select>
                  </div>
                  <div>
                    <label class="text-[10px] font-bold uppercase text-slate-400 block mb-1">Plan / Duración</label>
                    <select 
                      v-model="pickedStreamingPlanKey" 
                      @change="onStreamingPlanChange"
                      class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-xs text-white font-semibold outline-none"
                    >
                      <option v-for="(p, pKey) in currentStreamingPlans" :key="pKey" :value="pKey">
                        {{ p.label }} — ${{ p.clientPrice }} ARS
                      </option>
                    </select>
                  </div>
                </div>
              </template>

              <!-- MODO GAMING -->
              <template v-if="selectedMode === 'gaming'">
                <div class="space-y-3">
                  <div>
                    <label class="text-[10px] font-bold uppercase text-slate-400 block mb-1">Elegir Videojuego del Catálogo</label>
                    <select 
                      v-model="pickedGameId" 
                      @change="onGamingChange"
                      class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-xs text-white font-semibold outline-none"
                    >
                      <option v-for="g in gamingCatalogSample" :key="g.id" :value="g.id">
                        🎮 {{ g.title }} ({{ g.platform || 'PS4/PS5' }}) — ${{ (g.effectivePrimaryPrice || 15000).toLocaleString() }} ARS
                      </option>
                    </select>
                  </div>

                  <div class="flex gap-2">
                    <button
                      type="button"
                      @click="pickedGamingLicense = 'primary'; applyGamingProduct()"
                      :class="['flex-1 py-2 px-3 rounded-xl text-xs font-bold transition border cursor-pointer', pickedGamingLicense === 'primary' ? 'bg-indigo-600 border-indigo-500 text-white' : 'bg-slate-900 border-slate-800 text-slate-400']"
                    >
                      👑 Cuenta Primaria (Jugar con tu perfil)
                    </button>
                    <button
                      type="button"
                      @click="pickedGamingLicense = 'secondary'; applyGamingProduct()"
                      :class="['flex-1 py-2 px-3 rounded-xl text-xs font-bold transition border cursor-pointer', pickedGamingLicense === 'secondary' ? 'bg-indigo-600 border-indigo-500 text-white' : 'bg-slate-900 border-slate-800 text-slate-400']"
                    >
                      🎮 Cuenta Secundaria (Económica)
                    </button>
                  </div>
                </div>
              </template>

              <!-- MODO COMBO -->
              <template v-if="selectedMode === 'combo'">
                <div>
                  <label class="text-[10px] font-bold uppercase text-slate-400 block mb-1">Combo de Suscripciones</label>
                  <select 
                    v-model="pickedComboId" 
                    @change="onComboChange"
                    class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-xs text-white font-semibold outline-none"
                  >
                    <option v-for="c in adminStore.packages.filter(p => p.active)" :key="c.id" :value="c.id">
                      🎁 {{ c.title }} — ${{ (c.promoPrice || 12000).toLocaleString() }} ARS ({{ c.badge || 'Ahorro' }})
                    </option>
                  </select>
                </div>
              </template>

              <!-- MODO SORTEO -->
              <template v-if="selectedMode === 'giveaway'">
                <div>
                  <label class="text-[10px] font-bold uppercase text-slate-400 block mb-1">Sorteo Oficial Activo</label>
                  <select 
                    v-model="pickedGiveawayId" 
                    @change="onGiveawayChange"
                    class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-xs text-white font-semibold outline-none"
                  >
                    <option v-for="g in adminStore.giveaways" :key="g.id" :value="g.id">
                      🎉 {{ g.title }} — Premio: {{ g.prizeTitle }} ({{ g.status === 'open' ? 'Activo' : g.status }})
                    </option>
                  </select>
                </div>
              </template>

              <!-- MODO FLASH / PERSONALIZADO -->
              <template v-if="selectedMode === 'flash'">
                <div class="text-xs text-slate-400">
                  Configura a continuación los textos, precio y badge de tu oferta flash personalizada.
                </div>
              </template>
            </div>

            <!-- TEMA VISUAL Y ESTILO DE MARCA -->
            <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 space-y-3">
              <label class="text-[11px] uppercase font-bold text-slate-400 block tracking-wider">
                2. Tema Visual del Reel (Efectos &amp; Neón)
              </label>
              <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                <button
                  v-for="th in visualThemes"
                  :key="th.id"
                  type="button"
                  @click="currentThemeId = th.id; requestCanvasRedraw()"
                  :class="[
                    'p-2.5 rounded-xl border text-left transition flex items-center gap-2 cursor-pointer',
                    currentThemeId === th.id
                      ? 'bg-white/10 border-white text-white font-black'
                      : 'bg-slate-900/60 border-slate-800 text-slate-400 hover:border-slate-700'
                  ]"
                >
                  <span class="w-4 h-4 rounded-full border border-white/20 shrink-0" :style="{ background: th.previewGrad }"></span>
                  <span class="text-xs">{{ th.name }}</span>
                </button>
              </div>
            </div>

            <!-- TEXTOS Y DETALLES DEL REEL -->
            <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 space-y-3">
              <label class="text-[11px] uppercase font-bold text-slate-400 block tracking-wider">
                3. Textos, Precios y Cupones
              </label>
              
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Titular Principal</label>
                  <input 
                    v-model="reelHeadline" 
                    @input="requestCanvasRedraw"
                    type="text" 
                    class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-xs text-white font-bold outline-none"
                    placeholder="ej. NETFLIX 4K ULTRA HD"
                  >
                </div>

                <div>
                  <label class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Subtítulo / Beneficio</label>
                  <input 
                    v-model="reelSubline" 
                    @input="requestCanvasRedraw"
                    type="text" 
                    class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-xs text-white outline-none"
                    placeholder="ej. Perfil Privado con PIN Exclusivo"
                  >
                </div>

                <div>
                  <label class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Badge de Oferta</label>
                  <input 
                    v-model="reelBadgeText" 
                    @input="requestCanvasRedraw"
                    type="text" 
                    class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-xs text-amber-300 font-bold outline-none"
                    placeholder="ej. 🔥 25% OFF HOY"
                  >
                </div>

                <div>
                  <label class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Llamado a la Acción (CTA)</label>
                  <input 
                    v-model="reelCtaText" 
                    @input="requestCanvasRedraw"
                    type="text" 
                    class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-xs text-pink-300 font-bold outline-none"
                    placeholder="ej. 📲 PEDILO AL WHATSAPP"
                  >
                </div>

                <div>
                  <label class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Precio Promocional (ARS)</label>
                  <input 
                    v-model="reelPromoPrice" 
                    @input="requestCanvasRedraw"
                    type="number" 
                    class="w-full bg-slate-900 border border-emerald-500/40 rounded-xl p-2.5 text-sm text-emerald-400 font-black font-mono outline-none"
                    placeholder="5999"
                  >
                </div>

                <div>
                  <label class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Precio Regular Tachado (ARS)</label>
                  <input 
                    v-model="reelRegularPrice" 
                    @input="requestCanvasRedraw"
                    type="number" 
                    class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-xs text-slate-400 font-mono outline-none"
                    placeholder="8500"
                  >
                </div>

                <div>
                  <label class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Cupón Descuento Destacado</label>
                  <input 
                    v-model="reelPromoCode" 
                    @input="requestCanvasRedraw"
                    type="text" 
                    class="w-full bg-slate-900 border border-amber-500/40 rounded-xl p-2.5 text-xs text-amber-300 font-black font-mono uppercase outline-none"
                    placeholder="LUCAS15"
                  >
                </div>

                <div>
                  <label class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Nombre de Tienda / Marca</label>
                  <input 
                    v-model="reelStoreName" 
                    @input="requestCanvasRedraw"
                    type="text" 
                    class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-xs text-white font-bold outline-none"
                    placeholder="StreamePro Oficial"
                  >
                </div>
              </div>
            </div>

          </div>

          <!-- TAB 2: GUIÓN PARA TIKTOK & REELS -->
          <div v-else-if="studioTab === 'script'" class="space-y-4">
            
            <div class="bg-gradient-to-r from-purple-950/40 via-slate-900 to-slate-950 p-5 rounded-2xl border border-purple-500/30 flex items-center justify-between gap-3">
              <div>
                <h4 class="text-sm font-black text-white flex items-center gap-1.5">
                  <span>🎙️ Guión Estratégico de 15 Segundos</span>
                </h4>
                <p class="text-xs text-slate-300 mt-0.5">
                  Listo para leer ante la cámara, grabar con voz en off o usar con la voz de IA de TikTok / Instagram.
                </p>
              </div>

              <button
                type="button"
                @click="copyFullScriptPackage"
                class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-black text-xs shadow flex items-center gap-1.5 transition cursor-pointer shrink-0"
              >
                <span>📋</span>
                <span>Copiar Guión &amp; Copy</span>
              </button>
            </div>

            <!-- GANCHO (HOOK) -->
            <div class="bg-slate-950 p-4 rounded-2xl border border-pink-500/30 space-y-1.5">
              <div class="flex items-center justify-between">
                <span class="text-[10px] uppercase font-black tracking-wider text-pink-400">
                  🎯 Gancho de Entrada (Primeros 0 a 3 segundos)
                </span>
                <span class="text-[9px] font-mono text-slate-400">Captura la atención inmediata</span>
              </div>
              <p class="text-sm font-bold text-white bg-slate-900/80 p-3 rounded-xl border border-white/5">
                "{{ currentReelPackage.hook }}"
              </p>
            </div>

            <!-- VOZ EN OFF (CUERPO DEL VIDEO) -->
            <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 space-y-1.5">
              <div class="flex items-center justify-between">
                <span class="text-[10px] uppercase font-black tracking-wider text-indigo-400">
                  🗣️ Locución / Voz en Off (3 a 12 segundos)
                </span>
                <span class="text-[9px] font-mono text-slate-400">Explica beneficio + oferta</span>
              </div>
              <p class="text-xs text-slate-200 leading-relaxed bg-slate-900/80 p-3 rounded-xl border border-white/5">
                {{ currentReelPackage.voiceover }}
              </p>
            </div>

            <!-- LLAMADO A LA ACCIÓN (CTA) -->
            <div class="bg-slate-950 p-4 rounded-2xl border border-emerald-500/30 space-y-1.5">
              <div class="flex items-center justify-between">
                <span class="text-[10px] uppercase font-black tracking-wider text-emerald-400">
                  📲 Cierre &amp; Llamado a la Acción (12 a 15 segundos)
                </span>
                <span class="text-[9px] font-mono text-slate-400">Conversión a WhatsApp / Web</span>
              </div>
              <p class="text-xs font-bold text-white bg-slate-900/80 p-3 rounded-xl border border-white/5">
                {{ currentReelPackage.cta }}
              </p>
            </div>

            <!-- TEXTO LISTO PARA PEGAR EN LA DESCRIPCIÓN DEL REEL -->
            <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 space-y-2">
              <div class="flex items-center justify-between">
                <span class="text-[10px] uppercase font-black tracking-wider text-amber-400">
                  📝 Copy Listo para Pegar en TikTok / Instagram
                </span>
                <button
                  type="button"
                  @click="copyCaptionOnly"
                  class="text-[10px] text-amber-300 hover:text-white font-bold cursor-pointer"
                >
                  Copiar Solo Descripción
                </button>
              </div>
              <pre class="text-[11px] font-sans text-slate-300 bg-slate-900/90 p-3 rounded-xl border border-slate-800 whitespace-pre-wrap select-all max-h-40 overflow-y-auto leading-relaxed">{{ currentReelPackage.caption }}</pre>
            </div>

            <!-- CONSEJO VIRAL -->
            <div class="bg-slate-900/50 p-3 rounded-xl border border-slate-800 text-[11px] text-slate-300 flex items-start gap-2">
              <span>💡</span>
              <span><strong>Consejo Pro:</strong> Publicá el Reel y colocá tu enlace con cupón en la historia de Instagram usando el sticker de enlace (Link Sticker) o en la biografía de TikTok para maximizar ventas.</span>
            </div>

          </div>

        </div>

      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { useAdminStore } from '../store/admin'
import { useToastStore } from '../store/toast'
import { generateReelStoryPackage, normalizePromoCode } from '../utils/marketing'

const props = defineProps({
  show: { type: Boolean, default: false },
  initialPayload: { type: Object, default: () => null },
  resellerRef: { type: String, default: 'DIRECT' },
  storeName: { type: String, default: 'StreamePro Oficial' },
  contactPhone: { type: String, default: '+54 9 11 4050-6070' },
  promoCode: { type: String, default: '' },
  promoDiscountPct: { type: Number, default: 0 },
  websiteDomain: { type: String, default: 'streamepro.me' }
})

const emit = defineEmits(['close'])

const adminStore = useAdminStore()
const toast = useToastStore()

const canvasRef = ref(null)
const studioTab = ref('content') // 'content' | 'script'
const selectedMode = ref('streaming') // 'streaming' | 'gaming' | 'combo' | 'giveaway' | 'flash'
const currentThemeId = ref('cyber_glow')

// Estado de grabación
const isAnimated = ref(true)
const isRecording = ref(false)
const isExportingImage = ref(false)
const recordingProgress = ref(0)
const recordingSeconds = ref('0.0')

// Modelos del formulario reactivo
const reelHeadline = ref('NETFLIX 4K ULTRA HD')
const reelSubline = ref('Perfil Privado con PIN Exclusivo')
const reelBadgeText = ref('🔥 25% OFF HOY')
const reelCtaText = ref('📲 PEDILO AL WHATSAPP')
const reelPromoPrice = ref(5999)
const reelRegularPrice = ref(8500)
const reelPromoCode = ref('')
const reelStoreName = ref('StreamePro Oficial')
const reelArtworkUrl = ref('/hero.png')
const loadedArtworkImage = ref(null)

// Selección de entidades
const pickedStreamingKey = ref('Netflix')
const pickedStreamingPlanKey = ref('')
const pickedGameId = ref('')
const pickedGamingLicense = ref('primary')
const pickedComboId = ref('')
const pickedGiveawayId = ref('')

const contentModes = [
  { id: 'streaming', icon: '🎬', label: 'Streaming', desc: 'Netflix, Max, Spotify' },
  { id: 'gaming', icon: '🎮', label: 'Videojuegos', desc: 'PS5, PS4 Digitales' },
  { id: 'combo', icon: '🎁', label: 'Combos', desc: 'Packs con Super Ahorro' },
  { id: 'giveaway', icon: '🎉', label: 'Sorteo', desc: 'Viral para captar clientes' }
]

const visualThemes = [
  {
    id: 'cyber_glow',
    name: 'Cyber Neón',
    previewGrad: 'linear-gradient(135deg, #06b6d4, #6366f1)',
    bg1: '#040814',
    bg2: '#0b1224',
    bg3: '#020617',
    glow: 'rgba(6, 182, 212, 0.40)',
    accent: '#38bdf8',
    pillBg: 'linear-gradient(90deg, #06b6d4, #6366f1)',
    badgeGrad: ['#06b6d4', '#6366f1']
  },
  {
    id: 'gold_vip',
    name: 'Gold Luxury',
    previewGrad: 'linear-gradient(135deg, #f59e0b, #d97706)',
    bg1: '#0a0904',
    bg2: '#1a1608',
    bg3: '#020617',
    glow: 'rgba(245, 158, 11, 0.35)',
    accent: '#fbbf24',
    pillBg: 'linear-gradient(90deg, #f59e0b, #ea580c)',
    badgeGrad: ['#f59e0b', '#d97706']
  },
  {
    id: 'fire_sale',
    name: 'Fuego Urgente',
    previewGrad: 'linear-gradient(135deg, #ef4444, #f97316)',
    bg1: '#120404',
    bg2: '#200a0b',
    bg3: '#020617',
    glow: 'rgba(239, 68, 68, 0.40)',
    accent: '#f87171',
    pillBg: 'linear-gradient(90deg, #ef4444, #f97316)',
    badgeGrad: ['#ef4444', '#f97316']
  },
  {
    id: 'tokyo_pink',
    name: 'Tokyo Synth',
    previewGrad: 'linear-gradient(135deg, #ec4899, #8b5cf6)',
    bg1: '#0f0414',
    bg2: '#1b0b24',
    bg3: '#020617',
    glow: 'rgba(236, 72, 153, 0.40)',
    accent: '#f472b6',
    pillBg: 'linear-gradient(90deg, #ec4899, #8b5cf6)',
    badgeGrad: ['#ec4899', '#8b5cf6']
  }
]

const currentTheme = computed(() => {
  return visualThemes.find(t => t.id === currentThemeId.value) || visualThemes[0]
})

const currentStreamingPlans = computed(() => {
  return adminStore.pricingCatalog[pickedStreamingKey.value]?.plans || {}
})

const gamingCatalogSample = computed(() => {
  return (adminStore.processedGamingProducts || []).slice(0, 40)
})

const currentReelPackage = computed(() => {
  return generateReelStoryPackage({
    type: selectedMode.value,
    title: reelHeadline.value,
    promoPrice: reelPromoPrice.value,
    regularPrice: reelRegularPrice.value,
    promoCode: reelPromoCode.value,
    discountPct: props.promoDiscountPct || (reelPromoCode.value ? 15 : 0),
    storeName: reelStoreName.value,
    phone: props.contactPhone,
    url: props.websiteDomain ? `https://${props.websiteDomain}` : 'https://streamepro.me',
    isReseller: props.resellerRef && props.resellerRef !== 'DIRECT'
  })
})

// ==========================================
// MÉTODOS DE SELECCIÓN Y CONFIGURACIÓN
// ==========================================
function selectContentMode(modeId) {
  selectedMode.value = modeId
  if (modeId === 'streaming') {
    pickedStreamingKey.value = Object.keys(adminStore.pricingCatalog)[0] || 'Netflix'
    onStreamingServiceChange()
  } else if (modeId === 'gaming') {
    const firstGame = (adminStore.processedGamingProducts || [])[0]
    if (firstGame) {
      pickedGameId.value = firstGame.id
      applyGamingProduct()
    }
  } else if (modeId === 'combo') {
    const firstCombo = adminStore.packages.find(p => p.active)
    if (firstCombo) {
      pickedComboId.value = firstCombo.id
      onComboChange()
    }
  } else if (modeId === 'giveaway') {
    const firstGiveaway = (adminStore.giveaways || [])[0]
    if (firstGiveaway) {
      pickedGiveawayId.value = firstGiveaway.id
      onGiveawayChange()
    }
  }
}

function onStreamingServiceChange() {
  const plans = currentStreamingPlans.value
  const firstPlanKey = Object.keys(plans)[0] || ''
  pickedStreamingPlanKey.value = firstPlanKey
  onStreamingPlanChange()
}

function onStreamingPlanChange() {
  const srvKey = pickedStreamingKey.value
  const plan = currentStreamingPlans.value[pickedStreamingPlanKey.value]
  reelHeadline.value = `${srvKey.toUpperCase()} 4K OFICIAL`
  reelSubline.value = plan?.label ? `${plan.label} · PIN Exclusivo` : 'Perfil Privado con PIN'
  reelPromoPrice.value = Number(plan?.clientPrice || 4500)
  reelRegularPrice.value = Math.round(reelPromoPrice.value * 1.35)
  reelBadgeText.value = '🔥 ENTREGA EN 10 MIN'
  reelArtworkUrl.value = `/flyers/cover-${srvKey.toLowerCase().replace(/[^a-z0-9]/g, '')}.jpg`
  loadArtwork(reelArtworkUrl.value)
}

function onGamingChange() {
  applyGamingProduct()
}

function applyGamingProduct() {
  const g = (adminStore.processedGamingProducts || []).find(x => x.id === pickedGameId.value)
  if (!g) return
  reelHeadline.value = (g.title || 'Videojuego').toUpperCase().slice(0, 36)
  const isPri = pickedGamingLicense.value === 'primary'
  reelSubline.value = isPri ? 'Cuenta Primaria · Jugá desde tu usuario' : 'Cuenta Secundaria · Activación Oficial'
  reelPromoPrice.value = isPri ? Number(g.effectivePrimaryPrice || 15000) : Number(g.effectiveSecondaryPrice || 9000)
  reelRegularPrice.value = Math.round(reelPromoPrice.value * 1.3)
  reelBadgeText.value = isPri ? '👑 PS STORE OFICIAL' : '⚡ OFERTA ECONÓMICA'
  reelArtworkUrl.value = g.image || '/hero.png'
  loadArtwork(reelArtworkUrl.value)
}

function onComboChange() {
  const c = adminStore.packages.find(p => p.id === pickedComboId.value)
  if (!c) return
  reelHeadline.value = (c.title || 'Mega Combo').toUpperCase().slice(0, 34)
  reelSubline.value = (c.description || 'Suscripciones combinadas con descuento').slice(0, 48)
  reelPromoPrice.value = Number(c.promoPrice || 12000)
  reelRegularPrice.value = Number(c.regularPrice || Math.round(reelPromoPrice.value * 1.4))
  reelBadgeText.value = c.badge || '🎁 SUPER AHORRO'
  reelArtworkUrl.value = '/flyers/combo-cover.jpg'
  loadArtwork(reelArtworkUrl.value)
}

function onGiveawayChange() {
  const g = adminStore.giveaways.find(x => x.id === pickedGiveawayId.value)
  if (!g) return
  reelHeadline.value = `SORTEO: ${g.prizeTitle || g.title}`.toUpperCase().slice(0, 36)
  reelSubline.value = 'Participá 100% Gratis con tu celular'
  reelPromoPrice.value = 0
  reelRegularPrice.value = 0
  reelBadgeText.value = '🎉 PARTICIPÁ GRATIS'
  reelCtaText.value = '👉 REGISTRATE AL LINK'
  reelArtworkUrl.value = '/hero.png'
  loadArtwork(reelArtworkUrl.value)
}

// Carga asíncrona segura de imagen
function loadArtwork(src) {
  if (!src) return
  const img = new Image()
  img.crossOrigin = 'anonymous'
  img.onload = () => {
    loadedArtworkImage.value = img
    requestCanvasRedraw()
  }
  img.onerror = () => {
    // Fallback a logo
    if (src !== '/hero.png') {
      loadArtwork('/hero.png')
    }
  }
  img.src = src
}

// ==========================================
// MOTOR DE RENDERIZADO EN CANVAS 9:16 (1080x1920)
// ==========================================
let animFrameId = null
let animationStartTime = 0

function roundRect(ctx, x, y, w, h, r) {
  if (w < 2 * r) r = w / 2
  if (h < 2 * r) r = h / 2
  ctx.beginPath()
  ctx.moveTo(x + r, y)
  ctx.arcTo(x + w, y, x + w, y + h, r)
  ctx.arcTo(x + w, y + h, x, y + h, r)
  ctx.arcTo(x, y + h, x, y, r)
  ctx.arcTo(x, y, x + w, y, r)
  ctx.closePath()
}

function renderFrame(timeMs = 0) {
  const canvas = canvasRef.value
  if (!canvas) return
  const ctx = canvas.getContext('2d')
  if (!ctx) return

  const W = 1080
  const H = 1920
  const th = currentTheme.value
  const t = timeMs * 0.001

  // 1. Fondo Oscuro Studio
  const bgGrad = ctx.createLinearGradient(0, 0, 0, H)
  bgGrad.addColorStop(0, th.bg1)
  bgGrad.addColorStop(0.5, th.bg2)
  bgGrad.addColorStop(1, th.bg3)
  ctx.fillStyle = bgGrad
  ctx.fillRect(0, 0, W, H)

  // 2. Glow dinámico / radial con respiración
  const glowPulse = isAnimated.value ? (0.85 + Math.sin(t * 2) * 0.15) : 1
  const radialGlow = ctx.createRadialGradient(W * 0.5, H * 0.38, 50, W * 0.5, H * 0.38, 700)
  radialGlow.addColorStop(0, th.glow)
  radialGlow.addColorStop(1, 'rgba(0,0,0,0)')
  ctx.fillStyle = radialGlow
  ctx.fillRect(0, 0, W, H)

  // 3. Partículas de ambiente neón (animadas)
  if (isAnimated.value) {
    ctx.save()
    for (let i = 0; i < 18; i++) {
      const px = (W * ((i * 0.061 + t * 0.03) % 1))
      const py = (H * ((i * 0.073 + Math.sin(t + i) * 0.05 + 0.1) % 0.95))
      const pAlpha = 0.2 + Math.sin(t * 3 + i) * 0.15
      ctx.fillStyle = `rgba(255, 255, 255, ${Math.max(0.05, pAlpha)})`
      ctx.beginPath()
      ctx.arc(px, py, (i % 3) + 2, 0, Math.PI * 2)
      ctx.fill()
    }
    ctx.restore()
  }

  // 4. Marco exterior fino estilo Story
  ctx.save()
  ctx.strokeStyle = 'rgba(255, 255, 255, 0.10)'
  ctx.lineWidth = 4
  roundRect(ctx, 30, 30, W - 60, H - 60, 48)
  ctx.stroke()
  ctx.restore()

  // 5. Header superior: Insignia de Tienda & Verificado (Y: 100)
  ctx.save()
  const brandPillW = 420
  const brandPillH = 68
  const brandPillX = (W - brandPillW) / 2
  const brandPillY = 88
  ctx.fillStyle = 'rgba(15, 23, 42, 0.85)'
  roundRect(ctx, brandPillX, brandPillY, brandPillW, brandPillH, 34)
  ctx.fill()
  ctx.strokeStyle = 'rgba(255, 255, 255, 0.15)'
  ctx.lineWidth = 2
  ctx.stroke()

  ctx.fillStyle = '#ffffff'
  ctx.font = '900 24px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
  ctx.textAlign = 'center'
  ctx.fillText(`⚡ ${reelStoreName.value.toUpperCase()} · OFICIAL`, W / 2, brandPillY + 43)
  ctx.restore()

  // 6. Titular Principal (Y: 220 a 330)
  ctx.save()
  ctx.fillStyle = '#ffffff'
  ctx.font = '900 56px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
  ctx.textAlign = 'center'
  ctx.shadowColor = 'rgba(0, 0, 0, 0.8)'
  ctx.shadowBlur = 18
  ctx.fillText(reelHeadline.value.slice(0, 34), W / 2, 220)

  ctx.fillStyle = th.accent
  ctx.font = 'bold 30px sans-serif'
  ctx.fillText(reelSubline.value.slice(0, 52), W / 2, 276)
  ctx.restore()

  // 7. Tarjeta Central con Portada (Y: 340 a 1140) (800px alto)
  const cardX = 90
  const cardY = 320
  const cardW = W - 180
  const cardH = 800

  ctx.save()
  // Sombra exterior
  ctx.shadowColor = th.glow
  ctx.shadowBlur = 40
  ctx.fillStyle = '#050a18'
  roundRect(ctx, cardX, cardY, cardW, cardH, 40)
  ctx.fill()
  ctx.restore()

  // Borde neón
  ctx.save()
  ctx.strokeStyle = th.accent
  ctx.lineWidth = 4
  roundRect(ctx, cardX, cardY, cardW, cardH, 40)
  ctx.stroke()
  ctx.restore()

  // Dibujar Imagen con efecto Ken Burns (Zoom suave animado)
  if (loadedArtworkImage.value) {
    ctx.save()
    roundRect(ctx, cardX + 6, cardY + 6, cardW - 12, cardH - 12, 36)
    ctx.clip()

    const img = loadedArtworkImage.value
    const zoom = isAnimated.value ? (1.0 + Math.sin(t * 1.5) * 0.035) : 1.0
    const dw = (cardW - 12) * zoom
    const dh = (cardH - 12) * zoom
    const dx = cardX + 6 - (dw - (cardW - 12)) / 2
    const dy = cardY + 6 - (dh - (cardH - 12)) / 2
    ctx.drawImage(img, dx, dy, dw, dh)

    // Gradiente oscuro inferior sobre la imagen para legibilidad
    const imgShade = ctx.createLinearGradient(0, cardY + cardH - 220, 0, cardY + cardH)
    imgShade.addColorStop(0, 'rgba(0,0,0,0)')
    imgShade.addColorStop(1, 'rgba(2, 6, 23, 0.95)')
    ctx.fillStyle = imgShade
    ctx.fillRect(cardX, cardY + cardH - 220, cardW, 220)

    ctx.restore()
  }

  // Badge flotante en la tarjeta (ej: 🔥 25% OFF HOY)
  if (reelBadgeText.value) {
    ctx.save()
    const badgeW = 340
    const badgeH = 64
    const bx = cardX + 30
    const by = cardY + 30
    const bGrad = ctx.createLinearGradient(bx, by, bx + badgeW, by)
    bGrad.addColorStop(0, th.badgeGrad[0])
    bGrad.addColorStop(1, th.badgeGrad[1])
    ctx.fillStyle = bGrad
    roundRect(ctx, bx, by, badgeW, badgeH, 32)
    ctx.fill()
    ctx.fillStyle = '#ffffff'
    ctx.font = '900 24px -apple-system, sans-serif'
    ctx.textAlign = 'center'
    ctx.fillText(reelBadgeText.value, bx + badgeW / 2, by + 41)
    ctx.restore()
  }

  // 8. Zona de Precios & Cupones (Y: 1160 a 1480)
  const priceBoxY = 1160
  const priceBoxH = 310
  ctx.save()
  ctx.fillStyle = 'rgba(10, 16, 32, 0.92)'
  roundRect(ctx, cardX, priceBoxY, cardW, priceBoxH, 36)
  ctx.fill()
  ctx.strokeStyle = 'rgba(255, 255, 255, 0.12)'
  ctx.lineWidth = 2
  ctx.stroke()

  // Precio regular tachado si aplica
  if (Number(reelRegularPrice.value) > 0 && Number(reelRegularPrice.value) > Number(reelPromoPrice.value)) {
    ctx.fillStyle = '#94a3b8'
    ctx.font = 'bold 30px sans-serif'
    ctx.textAlign = 'center'
    const regText = `Precio Regular: $${Number(reelRegularPrice.value).toLocaleString('es-AR')} ARS`
    ctx.fillText(regText, W / 2, priceBoxY + 60)

    // Línea de tachado
    const tw = ctx.measureText(regText).width
    ctx.strokeStyle = '#f43f5e'
    ctx.lineWidth = 3
    ctx.beginPath()
    ctx.moveTo(W / 2 - tw / 2 - 10, priceBoxY + 50)
    ctx.lineTo(W / 2 + tw / 2 + 10, priceBoxY + 50)
    ctx.stroke()
  }

  // Precio Promocional Gigante
  if (Number(reelPromoPrice.value) > 0) {
    ctx.fillStyle = '#4ade80' // Emerald bright
    ctx.font = '900 86px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
    ctx.textAlign = 'center'
    ctx.shadowColor = 'rgba(74, 222, 128, 0.4)'
    ctx.shadowBlur = 24
    ctx.fillText(`$${Number(reelPromoPrice.value).toLocaleString('es-AR')} ARS`, W / 2, priceBoxY + 160)
  } else {
    ctx.fillStyle = '#fde047'
    ctx.font = '900 78px -apple-system, sans-serif'
    ctx.textAlign = 'center'
    ctx.fillText('¡SORTEO GRATIS!', W / 2, priceBoxY + 160)
  }
  ctx.restore()

  // Pill del Cupón Promocional con efecto Shimmer animado
  if (reelPromoCode.value) {
    ctx.save()
    const cpW = cardW - 60
    const cpH = 72
    const cpX = (W - cpW) / 2
    const cpY = priceBoxY + 205

    const cpGrad = ctx.createLinearGradient(cpX, cpY, cpX + cpW, cpY)
    cpGrad.addColorStop(0, '#f59e0b')
    cpGrad.addColorStop(1, '#d97706')
    ctx.fillStyle = cpGrad
    roundRect(ctx, cpX, cpY, cpW, cpH, 36)
    ctx.fill()

    // Destello de brillo animado
    if (isAnimated.value) {
      const shimmerX = cpX + ((t * 400) % (cpW + 200)) - 100
      const shimGrad = ctx.createLinearGradient(shimmerX, cpY, shimmerX + 80, cpY)
      shimGrad.addColorStop(0, 'rgba(255,255,255,0)')
      shimGrad.addColorStop(0.5, 'rgba(255,255,255,0.45)')
      shimGrad.addColorStop(1, 'rgba(255,255,255,0)')
      ctx.fillStyle = shimGrad
      roundRect(ctx, cpX, cpY, cpW, cpH, 36)
      ctx.fill()
    }

    ctx.fillStyle = '#020617'
    ctx.font = '900 26px -apple-system, sans-serif'
    ctx.textAlign = 'center'
    ctx.fillText(`🎟️ USA EL CÓDIGO: ${reelPromoCode.value.toUpperCase()} (DESCUENTO EXTRA)`, W / 2, cpY + 45)
    ctx.restore()
  }

  // 9. Gran Botón Llamado a la Acción (CTA) (Y: 1510 a 1630)
  ctx.save()
  const ctaW = cardW
  const ctaH = 100
  const ctaX = (W - ctaW) / 2
  const ctaY = 1510
  const ctaPulse = isAnimated.value ? (1.0 + Math.sin(t * 3.5) * 0.02) : 1.0

  ctx.translate(W / 2, ctaY + ctaH / 2)
  ctx.scale(ctaPulse, ctaPulse)
  ctx.translate(-W / 2, -(ctaY + ctaH / 2))

  const ctaGrad = ctx.createLinearGradient(ctaX, ctaY, ctaX + ctaW, ctaY)
  ctaGrad.addColorStop(0, '#ec4899')
  ctaGrad.addColorStop(0.5, '#a855f7')
  ctaGrad.addColorStop(1, '#6366f1')
  ctx.fillStyle = ctaGrad
  ctx.shadowColor = 'rgba(236, 72, 153, 0.45)'
  ctx.shadowBlur = 30
  roundRect(ctx, ctaX, ctaY, ctaW, ctaH, 50)
  ctx.fill()

  ctx.fillStyle = '#ffffff'
  ctx.font = '900 36px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
  ctx.textAlign = 'center'
  ctx.shadowColor = 'rgba(0,0,0,0.4)'
  ctx.shadowBlur = 10
  ctx.fillText(reelCtaText.value || '📲 PEDILO AL WHATSAPP', W / 2, ctaY + 62)
  ctx.restore()

  // 10. Footer / Contacto (Y: 1670 a 1860)
  ctx.save()
  ctx.fillStyle = '#ffffff'
  ctx.font = '900 28px -apple-system, sans-serif'
  ctx.textAlign = 'center'
  ctx.fillText(`📲 WhatsApp de Pedidos: ${props.contactPhone}`, W / 2, 1710)

  ctx.fillStyle = th.accent
  ctx.font = 'bold 24px monospace'
  const domainText = props.resellerRef && props.resellerRef !== 'DIRECT'
    ? `${props.websiteDomain || 'streamepro.me'}/ref/${props.resellerRef}`
    : `${props.websiteDomain || 'streamepro.me'}`
  ctx.fillText(`🔗 ${domainText}`, W / 2, 1756)

  ctx.fillStyle = 'rgba(148, 163, 184, 0.8)'
  ctx.font = '19px sans-serif'
  ctx.fillText('Activación guiada · Cuentas oficiales garantizadas · StreamePro', W / 2, 1800)
  ctx.restore()
}

function startAnimationLoop() {
  if (animFrameId) cancelAnimationFrame(animFrameId)
  animationStartTime = performance.now()

  const loop = (now) => {
    const elapsed = now - animationStartTime
    renderFrame(elapsed)
    if (isAnimated.value || isRecording.value) {
      animFrameId = requestAnimationFrame(loop)
    }
  }
  animFrameId = requestAnimationFrame(loop)
}

function requestCanvasRedraw() {
  nextTick(() => {
    renderFrame(performance.now() - animationStartTime)
  })
}

// ==========================================
// GRABACIÓN DE VIDEO REEL / TIKTOK (MEDIA RECORDER)
// ==========================================
async function recordVideoReel() {
  if (isRecording.value) return
  const canvas = canvasRef.value
  if (!canvas) return

  // Asegurar que la animación esté corriendo
  isAnimated.value = true
  isRecording.value = true
  recordingProgress.value = 0
  recordingSeconds.value = '0.0'

  try {
    // 30 FPS stream desde el canvas 9:16
    const stream = canvas.captureStream(30)
    
    // Detección del mimeType óptimo soportado por el navegador
    const candidates = [
      'video/webm;codecs=vp9',
      'video/webm;codecs=vp8',
      'video/webm',
      'video/mp4;codecs=h264',
      'video/mp4'
    ]
    let mimeType = 'video/webm'
    if (typeof MediaRecorder !== 'undefined') {
      const supported = candidates.find(t => MediaRecorder.isTypeSupported(t))
      if (supported) mimeType = supported
    }

    const recorder = new MediaRecorder(stream, {
      mimeType,
      videoBitsPerSecond: 6000000 // 6 Mbps para alta nitidez en TikTok / Reels
    })

    const chunks = []
    recorder.ondataavailable = (e) => {
      if (e.data && e.data.size > 0) {
        chunks.push(e.data)
      }
    }

    const durationMs = 5000 // 5 segundos
    const startTime = performance.now()

    // Temporizador de barra de progreso
    const progressInterval = setInterval(() => {
      const elapsed = performance.now() - startTime
      recordingProgress.value = Math.min(100, Math.round((elapsed / durationMs) * 100))
      recordingSeconds.value = (elapsed / 1000).toFixed(1)
    }, 100)

    recorder.onstop = () => {
      clearInterval(progressInterval)
      isRecording.value = false
      const ext = mimeType.includes('mp4') ? 'mp4' : 'webm'
      const blob = new Blob(chunks, { type: mimeType })
      const url = URL.createObjectURL(blob)
      const a = document.createElement('a')
      const cleanTitle = reelHeadline.value.toLowerCase().replace(/[^a-z0-9]/g, '_').slice(0, 20)
      a.href = url
      a.download = `reel_streamepro_${cleanTitle}_9x16.${ext}`
      document.body.appendChild(a)
      a.click()
      document.body.removeChild(a)
      setTimeout(() => URL.revokeObjectURL(url), 10000)

      toast.add(`🎬 ¡Video Reel 9:16 exportado con éxito (${ext.toUpperCase()})! Listo para TikTok, Reels o Estados.`, 'success')
    }

    recorder.start(100) // Emitir fragmentos cada 100ms

    setTimeout(() => {
      if (recorder.state !== 'inactive') {
        recorder.stop()
      }
    }, durationMs)

  } catch (err) {
    console.error('Error grabando reel en canvas:', err)
    isRecording.value = false
    toast.add('No se pudo completar la grabación de video en este navegador. Podes usar "Descargar PNG".', 'error')
  }
}

// ==========================================
// EXPORTACIÓN DE IMAGEN ESTÁTICA HD (PNG)
// ==========================================
async function downloadImagePng() {
  const canvas = canvasRef.value
  if (!canvas || isExportingImage.value) return
  isExportingImage.value = true

  try {
    renderFrame(performance.now() - animationStartTime)
    const blob = await new Promise(res => canvas.toBlob(res, 'image/png', 0.95))
    if (!blob) throw new Error('Error al generar imagen PNG')

    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    const cleanTitle = reelHeadline.value.toLowerCase().replace(/[^a-z0-9]/g, '_').slice(0, 20)
    a.href = url
    a.download = `estado_streamepro_${cleanTitle}_9x16.png`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    setTimeout(() => URL.revokeObjectURL(url), 10000)

    toast.add('📸 ¡Imagen HD 9:16 descargada con éxito! Lista para Estados de WhatsApp e Historias.', 'success')
  } catch (err) {
    console.error('Error exportando PNG:', err)
    toast.add('Error al exportar imagen', 'error')
  } finally {
    isExportingImage.value = false
  }
}

async function shareDirect() {
  const canvas = canvasRef.value
  if (!canvas) return
  try {
    renderFrame(performance.now() - animationStartTime)
    const blob = await new Promise(res => canvas.toBlob(res, 'image/png', 0.95))
    if (!blob) throw new Error('Blob nulo')

    if (typeof navigator !== 'undefined' && navigator.share && navigator.canShare) {
      const file = new File([blob], 'streamepro_story_9x16.png', { type: 'image/png' })
      if (navigator.canShare({ files: [file] })) {
        await navigator.share({
          files: [file],
          title: reelHeadline.value,
          text: currentReelPackage.value.caption
        })
        toast.add('Compartido con éxito', 'success')
        return
      }
    }
    // Fallback si no soporta archivos directos
    downloadImagePng()
  } catch (err) {
    if (err.name !== 'AbortError') {
      downloadImagePng()
    }
  }
}

// ==========================================
// COPIADO DE GUIONES Y COPYS
// ==========================================
function copyFullScriptPackage() {
  const pkg = currentReelPackage.value
  const fullText = [
    `🎬 GUIÓN PARA TIKTOK / REELS — ${pkg.title}`,
    `----------------------------------------`,
    `🎯 GANCHO (0 a 3 seg):`,
    `"${pkg.hook}"`,
    ``,
    `🗣️ VOZ EN OFF (3 a 12 seg):`,
    `"${pkg.voiceover}"`,
    ``,
    `📲 LLAMADO A LA ACCIÓN:`,
    `"${pkg.cta}"`,
    ``,
    `----------------------------------------`,
    `📝 COPY PARA LA DESCRIPCIÓN:`,
    pkg.caption,
    ``,
    pkg.soundTip
  ].join('\n')

  navigator.clipboard.writeText(fullText)
    .then(() => toast.add('📋 ¡Guión y copy completo copiado al portapapeles!', 'success'))
    .catch(() => toast.add('Error al copiar texto', 'error'))
}

function copyCaptionOnly() {
  navigator.clipboard.writeText(currentReelPackage.value.caption)
    .then(() => toast.add('📝 Descripción copiada lista para pegar', 'success'))
    .catch(() => toast.add('Error al copiar', 'error'))
}

function closeModal() {
  if (animFrameId) cancelAnimationFrame(animFrameId)
  emit('close')
}

// Inicialización según props
function initFromProps() {
  if (props.promoCode) {
    reelPromoCode.value = normalizePromoCode(props.promoCode)
  }
  if (props.storeName) {
    reelStoreName.value = props.storeName
  }

  const p = props.initialPayload
  if (p) {
    if (p.itemType === 'gaming' || p.platform?.includes('PS') || p.effectivePrimaryPrice) {
      selectedMode.value = 'gaming'
      pickedGameId.value = p.id
      applyGamingProduct()
    } else if (p.services || p.promoPrice && p.packages) {
      selectedMode.value = 'combo'
      pickedComboId.value = p.id
      onComboChange()
    } else if (p.prizeTitle || p.ticketsCount !== undefined) {
      selectedMode.value = 'giveaway'
      pickedGiveawayId.value = p.id
      onGiveawayChange()
    } else {
      selectedMode.value = 'streaming'
      pickedStreamingKey.value = p.platform || 'Netflix'
      onStreamingServiceChange()
    }
  } else {
    selectContentMode('streaming')
  }

  nextTick(() => {
    startAnimationLoop()
  })
}

watch(() => props.show, (newVal) => {
  if (newVal) {
    nextTick(() => {
      initFromProps()
    })
  } else {
    if (animFrameId) cancelAnimationFrame(animFrameId)
  }
})

watch(() => props.promoCode, (newVal) => {
  if (newVal) reelPromoCode.value = normalizePromoCode(newVal)
})

onMounted(() => {
  if (props.show) {
    initFromProps()
  }
})

onBeforeUnmount(() => {
  if (animFrameId) cancelAnimationFrame(animFrameId)
})
</script>

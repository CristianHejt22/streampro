<template>
  <div v-if="show" class="fixed inset-0 z-[120] bg-slate-950/95 backdrop-blur-md flex items-center justify-center p-0 sm:p-3 md:p-6 overflow-y-auto">
    <div class="max-w-7xl w-full bg-[#030712] border-0 sm:border border-slate-800 rounded-none sm:rounded-3xl shadow-2xl overflow-hidden flex flex-col h-full sm:h-auto max-h-[100dvh] sm:max-h-[96vh]">
      
      <!-- HEADER CON INFORMACIÓN Y BOTÓN DE CIERRE -->
      <div class="px-4 sm:px-6 py-3 sm:py-3.5 border-b border-slate-800 flex items-center justify-between bg-slate-900/70 shrink-0">
        <div class="flex items-center gap-2.5 sm:gap-3">
          <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-2xl bg-gradient-to-tr from-pink-500 via-purple-600 to-indigo-500 flex items-center justify-center text-lg sm:text-xl shadow-lg shadow-pink-500/25 shrink-0">
            🎬
          </div>
          <div>
            <div class="flex items-center gap-1.5 sm:gap-2">
              <h2 class="text-sm sm:text-base md:text-lg font-black text-white flex items-center gap-1.5">
                Estudio Reels, TikTok &amp; Estados 9:16
              </h2>
              <span class="hidden xs:inline-block text-[9px] sm:text-[10px] uppercase font-black px-2 py-0.5 rounded-full bg-gradient-to-r from-pink-500/20 to-purple-500/20 text-pink-300 border border-pink-500/30">
                1080 × 1920 HD
              </span>
            </div>
            <p class="text-[11px] sm:text-xs text-slate-400 line-clamp-1">
              Diseño tipo flyer profesional, personalización total de carátulas y descarga optimizada para móvil y PC.
            </p>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <!-- Toggle Vista Móvil (Preview vs Editor) solo en pantallas pequeñas -->
          <div class="flex lg:hidden bg-slate-950 p-0.5 rounded-xl border border-slate-800 text-xs">
            <button
              type="button"
              @click="mobileViewMode = 'preview'"
              :class="['px-2.5 py-1 rounded-lg font-bold transition cursor-pointer', mobileViewMode === 'preview' ? 'bg-pink-600 text-white shadow' : 'text-slate-400 hover:text-white']"
            >
              👁️ Ver 9:16
            </button>
            <button
              type="button"
              @click="mobileViewMode = 'editor'"
              :class="['px-2.5 py-1 rounded-lg font-bold transition cursor-pointer', mobileViewMode === 'editor' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-white']"
            >
              🛠️ Editor
            </button>
          </div>

          <button 
            type="button" 
            @click="closeModal" 
            class="text-slate-400 hover:text-white p-2 rounded-xl hover:bg-slate-800 transition text-lg cursor-pointer"
            title="Cerrar estudio"
          >
            ✕
          </button>
        </div>
      </div>

      <!-- MAIN WORKSPACE (GRID 2 COLUMNAS EN PC / CONDICIONAL EN MÓVIL) -->
      <div class="flex-1 overflow-y-auto p-3 sm:p-5 grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
        
        <!-- ========================================== -->
        <!-- COLUMNA IZQUIERDA: CANVAS 9:16 EN VIVO     -->
        <!-- ========================================== -->
        <div 
          :class="[
            'lg:col-span-5 flex flex-col items-center space-y-3.5 lg:sticky lg:top-0',
            mobileViewMode === 'preview' ? 'block' : 'hidden lg:flex'
          ]"
        >
          
          <!-- CONTENEDOR 9:16 DE ALTA DEFINICIÓN CON INTERACCIÓN TÁCTIL / MOUSE DRAG -->
          <div 
            class="relative w-full max-w-[310px] sm:max-w-[325px] aspect-[9/16] rounded-2xl overflow-hidden shadow-2xl border border-white/10 bg-black flex items-center justify-center group select-none cursor-grab active:cursor-grabbing touch-none"
            @pointerdown="onCanvasPointerDown"
            @pointermove="onCanvasPointerMove"
            @pointerup="onCanvasPointerUp"
            @pointercancel="onCanvasPointerUp"
            title="Toca o haz clic y arrastra para encuadrar la portada al instante"
          >
            
            <!-- Canvas 9:16 Nativo (1080x1920) -->
            <canvas 
              ref="canvasRef" 
              width="1080" 
              height="1920" 
              class="w-full h-full object-contain pointer-events-none"
            ></canvas>

            <!-- Overlay de Grabación de Video en Progreso -->
            <div 
              v-if="isRecording" 
              class="absolute inset-0 bg-slate-950/85 backdrop-blur-md flex flex-col items-center justify-center p-6 text-center animate-in fade-in z-20 pointer-events-auto"
            >
              <div class="relative mb-4">
                <div class="w-16 h-16 rounded-full border-4 border-pink-500/30 border-t-pink-500 animate-spin"></div>
                <span class="absolute inset-0 flex items-center justify-center text-xl">🎬</span>
              </div>
              <div class="text-sm font-black text-white uppercase tracking-wider flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-rose-500 animate-ping"></span>
                <span>Renderizando Video 9:16...</span>
              </div>
              <p class="text-xs text-slate-300 mt-1">Animación fluida a 30 FPS en resolución 1080p</p>
              
              <!-- Barra de progreso con gradiente neón -->
              <div class="w-full bg-slate-800 h-2.5 rounded-full overflow-hidden mt-4 border border-white/10">
                <div 
                  class="bg-gradient-to-r from-pink-500 via-purple-500 to-indigo-500 h-full transition-all duration-100" 
                  :style="{ width: recordingProgress + '%' }"
                ></div>
              </div>
              <span class="text-xs font-mono text-pink-300 mt-2 font-black">{{ recordingSeconds }}s / 5.0s</span>
            </div>

            <!-- Badges flotantes en vista previa -->
            <div class="absolute top-2.5 left-2.5 bg-black/70 backdrop-blur-md px-2.5 py-1 rounded-lg border border-white/15 text-[10px] font-mono text-slate-200 flex items-center gap-1.5 shadow pointer-events-none">
              <span>📱 9:16 {{ currentLayoutName }}</span>
            </div>

            <div class="absolute top-2.5 right-2.5 flex items-center gap-1.5 pointer-events-none">
              <span 
                :class="[
                  'px-2 py-0.5 rounded-md text-[9px] font-mono font-bold border backdrop-blur-md flex items-center gap-1',
                  isAnimated ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30' : 'bg-slate-800/80 text-slate-400 border-white/10'
                ]"
              >
                <span class="w-1.5 h-1.5 rounded-full" :class="isAnimated ? 'bg-emerald-400 animate-pulse' : 'bg-slate-400'"></span>
                <span>{{ isAnimated ? 'Animado 30 FPS' : 'Estático' }}</span>
              </span>
            </div>

            <!-- Hint de arrastre táctil / mouse -->
            <div class="absolute bottom-2.5 left-2.5 bg-black/75 backdrop-blur-md px-2 py-1 rounded-lg border border-white/15 text-[9px] text-slate-300 flex items-center gap-1 pointer-events-none opacity-80 group-hover:opacity-100 transition-opacity">
              <span>🖐️</span>
              <span>Arrastra para mover imagen</span>
            </div>

            <!-- Botón flotante para ver en grande / pantalla completa -->
            <button
              type="button"
              @click.stop="toggleFullscreenPreview"
              class="absolute bottom-2.5 right-2.5 bg-black/70 hover:bg-black/90 text-white text-xs p-2 rounded-xl border border-white/20 backdrop-blur-md transition shadow cursor-pointer opacity-70 hover:opacity-100 pointer-events-auto"
              title="Ver en Pantalla Completa"
            >
              🔍
            </button>
          </div>

          <!-- ======================================================== -->
          <!-- BOTONERA DE ACCIÓN Y DESCARGAS ADAPTADAS (MÓVIL & PC)    -->
          <!-- ======================================================== -->
          <div class="w-full max-w-[325px] space-y-2">
            
            <!-- Botón Principal: Grabar Video Reel 5s (TikTok / Reels / Estados) -->
            <button
              type="button"
              @click="recordVideoReel"
              :disabled="isRecording || isExportingImage"
              class="w-full py-3 px-4 rounded-xl font-black text-xs transition-all shadow-lg flex items-center justify-center gap-2 cursor-pointer bg-gradient-to-r from-pink-500 via-purple-600 to-indigo-600 hover:from-pink-400 hover:to-indigo-500 text-white shadow-pink-500/25 active:scale-98 disabled:opacity-50"
            >
              <span>🎬</span>
              <span>{{ isRecording ? 'Grabando Video...' : 'Grabar Video Reel (5s @ 30 FPS)' }}</span>
            </button>

            <!-- Acciones Adaptadas Móvil y PC -->
            <div class="grid grid-cols-2 gap-2">
              <!-- Descargar PNG HD -->
              <button
                type="button"
                @click="downloadImagePng"
                :disabled="isRecording || isExportingImage"
                class="py-2.5 px-3 rounded-xl font-black text-xs transition-all shadow border border-slate-700 bg-slate-900 hover:bg-slate-800 text-white flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-50"
                title="Descargar imagen en alta resolución 1080x1920"
              >
                <span>📸</span>
                <span>{{ isExportingImage ? 'Generando...' : 'Descargar PNG' }}</span>
              </button>
              
              <!-- Compartir / Guardar en Móvil (Web Share API nativa o visor táctil) -->
              <button
                type="button"
                @click="handleMobileOrShareAction"
                :disabled="isRecording || isExportingImage"
                class="py-2.5 px-3 rounded-xl font-black text-xs transition-all shadow border border-emerald-500/40 bg-emerald-600/20 hover:bg-emerald-600 text-emerald-300 hover:text-white flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-50"
                :title="isMobileDevice ? 'Compartir a WhatsApp / Instagram o Guardar en Fotos' : 'Abrir opciones de guardado móvil'"
              >
                <span>📲</span>
                <span>{{ isMobileDevice ? 'Compartir / Fotos' : 'Guardar en Celular' }}</span>
              </button>
            </div>

            <!-- Función Especial para PC: Copiar al Portapapeles (Ctrl + V para WhatsApp Web) -->
            <button
              v-if="!isMobileDevice"
              type="button"
              @click="copyImageToClipboard"
              :disabled="isRecording || isExportingImage"
              class="w-full py-2 px-3 rounded-xl font-bold text-xs transition-all shadow border border-indigo-500/30 bg-indigo-950/40 hover:bg-indigo-900 text-indigo-300 hover:text-white flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-50"
              title="Copia la imagen para pegarla directamente con Ctrl + V en WhatsApp Web o Telegram"
            >
              <span>📋</span>
              <span>Copiar Imagen al Portapapeles (Ctrl + V)</span>
            </button>

            <!-- Control de Animación en Tiempo Real -->
            <div class="flex items-center justify-between bg-slate-900/60 border border-slate-800/80 rounded-xl px-3 py-1.5 text-xs text-slate-300">
              <span class="text-[11px] font-semibold text-slate-400">Dinámicas &amp; Animación:</span>
              <button
                type="button"
                @click="isAnimated = !isAnimated; requestCanvasRedraw()"
                :class="['px-2.5 py-0.5 rounded-lg text-[10px] font-bold cursor-pointer transition', isAnimated ? 'bg-indigo-600 text-white shadow' : 'bg-slate-800 text-slate-400 hover:text-white']"
              >
                {{ isAnimated ? '⏸️ Pausar Dinámicas' : '▶️ Activar Dinámicas' }}
              </button>
            </div>

            <!-- En Móvil: Botón para cambiar a modo edición -->
            <button
              v-if="mobileViewMode === 'preview'"
              type="button"
              @click="mobileViewMode = 'editor'"
              class="lg:hidden w-full py-2 px-3 rounded-xl font-bold text-xs bg-slate-800 text-slate-200 border border-slate-700 flex items-center justify-center gap-2 cursor-pointer mt-2"
            >
              <span>🛠️ Personalizar Textos y Ajustes</span>
            </button>
          </div>
        </div>

        <!-- ========================================== -->
        <!-- COLUMNA DERECHA: SUITE DE EDICIÓN & TABS   -->
        <!-- ========================================== -->
        <div 
          :class="[
            'lg:col-span-7 space-y-4',
            mobileViewMode === 'editor' ? 'block' : 'hidden lg:block'
          ]"
        >
          
          <!-- NAVEGACIÓN ENTRE TABS DEL ESTUDIO -->
          <div class="flex flex-wrap gap-1.5 sm:gap-2 border-b border-slate-800 pb-3">
            <button
              type="button"
              @click="studioTab = 'product'"
              :class="['px-3 py-2 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-1.5', studioTab === 'product' ? 'bg-pink-500 text-white shadow' : 'bg-slate-950 text-slate-300 border border-slate-800 hover:bg-slate-800']"
            >
              <span>🎮 Catálogo &amp; Buscar</span>
            </button>
            <button
              type="button"
              @click="studioTab = 'image'"
              :class="['px-3 py-2 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-1.5', studioTab === 'image' ? 'bg-cyan-500 text-slate-950 shadow' : 'bg-slate-950 text-slate-300 border border-slate-800 hover:bg-slate-800']"
            >
              <span>🖼️ Cambiar &amp; Ajustar Imagen</span>
            </button>
            <button
              type="button"
              @click="studioTab = 'design'"
              :class="['px-3 py-2 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-1.5', studioTab === 'design' ? 'bg-indigo-600 text-white shadow' : 'bg-slate-950 text-slate-300 border border-slate-800 hover:bg-slate-800']"
            >
              <span>🎨 Plantillas &amp; Estilo</span>
            </button>
            <button
              type="button"
              @click="studioTab = 'custom'"
              :class="['px-3 py-2 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-1.5', studioTab === 'custom' ? 'bg-amber-500 text-slate-950 shadow' : 'bg-slate-950 text-slate-300 border border-slate-800 hover:bg-slate-800']"
            >
              <span>⚙️ Textos &amp; Precios</span>
            </button>
            <button
              type="button"
              @click="studioTab = 'script'"
              :class="['px-3 py-2 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-1.5', studioTab === 'script' ? 'bg-purple-600 text-white shadow' : 'bg-slate-950 text-slate-300 border border-slate-800 hover:bg-slate-800']"
            >
              <span>🎙️ Guión Viral</span>
            </button>
          </div>

          <!-- ========================================== -->
          <!-- TAB 1: PRODUCTO & BÚSQUEDA INTERACTIVA     -->
          <!-- ========================================== -->
          <div v-if="studioTab === 'product'" class="space-y-4">
            
            <!-- Selector de Tipo de Contenido -->
            <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 space-y-2.5">
              <label class="text-[11px] uppercase font-bold text-slate-400 block tracking-wider">
                1. ¿Qué producto vas a promocionar?
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
                      ? 'bg-pink-500/15 border-pink-500 text-white shadow-md'
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

            <!-- SECCIÓN ESPECIAL: BUSCADOR EN VIVO DE VIDEOJUEGOS (+15.000 TÍTULOS) -->
            <div v-if="selectedMode === 'gaming'" class="bg-slate-950 p-4 rounded-2xl border border-indigo-500/40 space-y-3.5 animate-in fade-in">
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                  <h4 class="text-xs sm:text-sm font-black text-white flex items-center gap-1.5">
                    <span>🔍 Buscador de Videojuegos (+15.000 Títulos)</span>
                  </h4>
                  <p class="text-[11px] text-slate-400">Escribe el título del juego para cargar su portada oficial y precios automáticamente.</p>
                </div>
                <span class="text-[10px] font-mono text-indigo-300 bg-indigo-500/15 px-2 py-0.5 rounded border border-indigo-500/30 shrink-0">
                  {{ filteredGamingGames.length }} coincidencias
                </span>
              </div>

              <!-- Input de Búsqueda Reactivo -->
              <div class="relative">
                <input 
                  v-model="gamingSearchTerm" 
                  type="text" 
                  placeholder="Escribe para buscar... ej: GTA, FC 25, Spider-Man, God of War, Mortal Kombat..." 
                  class="w-full bg-slate-900 border border-slate-800 focus:border-indigo-500 rounded-xl pl-9 pr-8 py-2.5 text-xs text-white outline-none font-semibold transition"
                >
                <span class="absolute left-3 top-2.5 text-slate-400 text-xs">🔍</span>
                <button 
                  v-if="gamingSearchTerm" 
                  type="button" 
                  @click="gamingSearchTerm = ''" 
                  class="absolute right-2.5 top-2.5 text-slate-400 hover:text-white text-xs cursor-pointer"
                >
                  ✕
                </button>
              </div>

              <!-- Chips de Filtros Rápidos / Géneros -->
              <div class="flex flex-wrap gap-1.5">
                <button
                  v-for="chip in gamingQuickFilters"
                  :key="chip.id"
                  type="button"
                  @click="selectGamingQuickFilter(chip.id)"
                  :class="[
                    'px-2.5 py-1 rounded-lg text-[10px] font-bold transition cursor-pointer border',
                    gamingQuickCategory === chip.id
                      ? 'bg-indigo-600 text-white border-indigo-500'
                      : 'bg-slate-900 text-slate-400 border-slate-800 hover:text-white'
                  ]"
                >
                  {{ chip.label }}
                </button>
              </div>

              <!-- Galería de Resultados Visuales Seleccionables -->
              <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 max-h-56 overflow-y-auto pr-1 custom-scrollbar">
                <div
                  v-for="g in filteredGamingGames"
                  :key="g.id"
                  @click="selectGamingProduct(g)"
                  :class="[
                    'p-2 rounded-xl border transition flex items-center gap-2 cursor-pointer group',
                    pickedGameId === g.id
                      ? 'bg-indigo-600/25 border-indigo-500 text-white shadow-md'
                      : 'bg-slate-900/60 border-slate-800/80 text-slate-300 hover:border-slate-700 hover:bg-slate-900'
                  ]"
                >
                  <!-- Portada Thumbnail -->
                  <div class="w-10 h-12 rounded-lg bg-slate-950 overflow-hidden shrink-0 border border-white/10 flex items-center justify-center">
                    <img v-if="g.image" :src="g.image" :alt="g.title" class="w-full h-full object-cover">
                    <span v-else class="text-xs">🎮</span>
                  </div>

                  <!-- Info resumida -->
                  <div class="flex-1 min-w-0">
                    <span class="text-[11px] font-bold block truncate group-hover:text-indigo-300 transition-colors">
                      {{ g.title }}
                    </span>
                    <span class="text-[9px] text-slate-400 block font-mono">
                      {{ g.platform || 'PS4/PS5' }}
                    </span>
                    <span class="text-[10px] font-mono font-black text-emerald-400 block mt-0.5">
                      ${{ (g.effectivePrimaryPrice || 15000).toLocaleString() }}
                    </span>
                  </div>

                  <!-- Checkmark de activo -->
                  <span v-if="pickedGameId === g.id" class="text-indigo-400 text-xs font-black shrink-0">✓</span>
                </div>
              </div>

              <!-- Selector de Licencia Primaria / Secundaria con Badges Oficiales -->
              <div class="pt-2 border-t border-slate-800/80 space-y-1.5">
                <label class="text-[10px] font-bold uppercase text-slate-400 block">Tipo de Licencia a Promocionar:</label>
                <div class="grid grid-cols-2 gap-2">
                  <button
                    type="button"
                    @click="pickedGamingLicense = 'primary'; applyGamingProduct()"
                    :class="[
                      'py-2 px-3 rounded-xl text-xs font-bold transition border cursor-pointer flex items-center justify-center gap-1.5',
                      pickedGamingLicense === 'primary' 
                        ? 'bg-indigo-600 border-indigo-500 text-white shadow' 
                        : 'bg-slate-900 border-slate-800 text-slate-400 hover:text-white'
                    ]"
                  >
                    <span>👑</span>
                    <span>Cuenta Primaria (Jugar con tu perfil)</span>
                  </button>
                  <button
                    type="button"
                    @click="pickedGamingLicense = 'secondary'; applyGamingProduct()"
                    :class="[
                      'py-2 px-3 rounded-xl text-xs font-bold transition border cursor-pointer flex items-center justify-center gap-1.5',
                      pickedGamingLicense === 'secondary' 
                        ? 'bg-indigo-600 border-indigo-500 text-white shadow' 
                        : 'bg-slate-900 border-slate-800 text-slate-400 hover:text-white'
                    ]"
                  >
                    <span>🎮</span>
                    <span>Cuenta Secundaria (Económica)</span>
                  </button>
                </div>
              </div>
            </div>

            <!-- MODO STREAMING: SELECTOR DE SERVICIOS -->
            <div v-else-if="selectedMode === 'streaming'" class="bg-slate-950 p-4 rounded-2xl border border-slate-800 space-y-3 animate-in fade-in">
              <h4 class="text-xs font-bold uppercase text-slate-400">Catálogo de Suscripciones</h4>
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
            </div>

            <!-- MODO COMBOS -->
            <div v-else-if="selectedMode === 'combo'" class="bg-slate-950 p-4 rounded-2xl border border-slate-800 space-y-3 animate-in fade-in">
              <h4 class="text-xs font-bold uppercase text-slate-400">Packs &amp; Combos Multi-Plataforma</h4>
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

            <!-- MODO SORTEOS -->
            <div v-else-if="selectedMode === 'giveaway'" class="bg-slate-950 p-4 rounded-2xl border border-slate-800 space-y-3 animate-in fade-in">
              <h4 class="text-xs font-bold uppercase text-slate-400">Sorteos Oficiales</h4>
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

          </div>

          <!-- ======================================================== -->
          <!-- TAB 2: CAMBIAR & AJUSTAR IMAGEN (SUITE PRO)              -->
          <!-- ======================================================== -->
          <div v-else-if="studioTab === 'image'" class="space-y-4">
            
            <!-- 1. CAMBIAR IMAGEN (SUBIR ARCHIVO / PEGAR LINK / PRESETS) -->
            <div class="bg-slate-950 p-4 rounded-2xl border border-cyan-500/30 space-y-3">
              <div class="flex items-center justify-between">
                <div>
                  <h4 class="text-xs sm:text-sm font-black text-white flex items-center gap-1.5">
                    <span>🖼️ Cambiar Imagen de Portada</span>
                  </h4>
                  <p class="text-[11px] text-slate-400">Sube una foto desde tu móvil o PC, ingresa una URL web o elige fondos pro.</p>
                </div>
                <button
                  type="button"
                  @click="resetToOriginalProductCover"
                  class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-amber-300 text-[10px] font-bold transition cursor-pointer border border-amber-500/20"
                  title="Restaurar la carátula oficial del producto"
                >
                  ↺ Carátula Original
                </button>
              </div>

              <!-- Botones de Acción de Imagen -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1">
                <!-- Subir Archivo Local -->
                <button
                  type="button"
                  @click="triggerCustomFileUpload"
                  class="p-2.5 rounded-xl bg-cyan-600/20 hover:bg-cyan-600/30 border border-cyan-500/40 text-cyan-300 hover:text-white text-xs font-bold transition flex items-center justify-center gap-2 cursor-pointer"
                >
                  <span>📁</span>
                  <span>Subir Foto desde tu Equipo / Móvil</span>
                </button>
                <input 
                  ref="fileInputRef" 
                  type="file" 
                  accept="image/*" 
                  class="hidden" 
                  @change="handleCustomFileUpload"
                >

                <!-- Pegar URL Web de Imagen -->
                <div class="flex gap-1.5">
                  <input 
                    v-model="customUrlInput" 
                    type="url" 
                    placeholder="O pega link de imagen (https://...)" 
                    class="flex-1 bg-slate-900 border border-slate-800 rounded-xl px-2.5 py-1.5 text-xs text-white outline-none focus:border-cyan-500"
                    @keyup.enter="applyCustomUrlImage"
                  >
                  <button 
                    type="button" 
                    @click="applyCustomUrlImage" 
                    class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-bold text-white cursor-pointer"
                  >
                    OK
                  </button>
                </div>
              </div>

              <!-- Galería de Wallpapers y Fondos Temáticos Rápidos -->
              <div class="pt-2 border-t border-slate-800/80">
                <span class="text-[10px] font-bold uppercase text-slate-400 block mb-2">Fondos Cinemáticos &amp; Wallpapers de Relleno:</span>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                  <button
                    v-for="preset in artworkPresets"
                    :key="preset.id"
                    type="button"
                    @click="selectArtworkPreset(preset.url)"
                    class="p-1.5 rounded-xl bg-slate-900 border border-slate-800 hover:border-cyan-500/50 transition flex items-center gap-2 cursor-pointer text-left group"
                  >
                    <span class="text-base">{{ preset.icon }}</span>
                    <span class="text-[10px] font-bold text-slate-300 group-hover:text-cyan-300 truncate">{{ preset.label }}</span>
                  </button>
                </div>
              </div>
            </div>

            <!-- 2. AJUSTES FINOS DE ENCUADRE (ZOOM, PAN X, PAN Y, INCLINACIÓN, MODO DE AJUSTE) -->
            <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 space-y-3.5">
              <div class="flex items-center justify-between">
                <div>
                  <h4 class="text-xs sm:text-sm font-black text-white flex items-center gap-1.5">
                    <span>🎛️ Calibración Fina de Encuadre</span>
                  </h4>
                  <p class="text-[10px] text-slate-400">También puedes arrastrar directamente con el dedo o mouse sobre el flyer.</p>
                </div>

                <button
                  type="button"
                  @click="resetImageTransformations"
                  class="px-2 py-1 rounded-lg bg-slate-900 hover:bg-slate-800 text-slate-300 text-[10px] font-bold transition cursor-pointer border border-slate-800"
                  title="Restaurar zoom y posición a cero"
                >
                  ⌖ Centrar
                </button>
              </div>

              <!-- Sliders de Zoom y Desplazamientos X / Y -->
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                
                <!-- Zoom -->
                <div class="bg-slate-900/60 p-2.5 rounded-xl border border-slate-800/80">
                  <div class="flex justify-between text-[10px] font-bold text-slate-300 mb-1">
                    <span>🔍 Zoom / Escala</span>
                    <span class="text-cyan-400 font-mono">{{ Math.round(imageZoomFactor * 100) }}%</span>
                  </div>
                  <input 
                    v-model.number="imageZoomFactor" 
                    @input="requestCanvasRedraw"
                    type="range" 
                    min="0.5" 
                    max="2.2" 
                    step="0.05" 
                    class="w-full accent-cyan-400 cursor-pointer"
                  >
                </div>

                <!-- Posición X -->
                <div class="bg-slate-900/60 p-2.5 rounded-xl border border-slate-800/80">
                  <div class="flex justify-between text-[10px] font-bold text-slate-300 mb-1">
                    <span>↔️ Posición Horizontal</span>
                    <span class="text-cyan-400 font-mono">{{ imageOffsetX > 0 ? '+' + imageOffsetX : imageOffsetX }}%</span>
                  </div>
                  <input 
                    v-model.number="imageOffsetX" 
                    @input="requestCanvasRedraw"
                    type="range" 
                    min="-45" 
                    max="45" 
                    step="1" 
                    class="w-full accent-cyan-400 cursor-pointer"
                  >
                </div>

                <!-- Posición Y -->
                <div class="bg-slate-900/60 p-2.5 rounded-xl border border-slate-800/80">
                  <div class="flex justify-between text-[10px] font-bold text-slate-300 mb-1">
                    <span>↕️ Posición Vertical</span>
                    <span class="text-cyan-400 font-mono">{{ imageOffsetY > 0 ? '+' + imageOffsetY : imageOffsetY }}%</span>
                  </div>
                  <input 
                    v-model.number="imageOffsetY" 
                    @input="requestCanvasRedraw"
                    type="range" 
                    min="-45" 
                    max="45" 
                    step="1" 
                    class="w-full accent-cyan-400 cursor-pointer"
                  >
                </div>

              </div>

              <!-- Inclinación / Rotación + Modo de Ajuste -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                
                <!-- Inclinación Dinámica -->
                <div class="bg-slate-900/60 p-2.5 rounded-xl border border-slate-800/80">
                  <div class="flex justify-between text-[10px] font-bold text-slate-300 mb-1">
                    <span>📐 Inclinación / Tilt Acción</span>
                    <span class="text-cyan-400 font-mono">{{ imageRotation }}°</span>
                  </div>
                  <input 
                    v-model.number="imageRotation" 
                    @input="requestCanvasRedraw"
                    type="range" 
                    min="-15" 
                    max="15" 
                    step="1" 
                    class="w-full accent-cyan-400 cursor-pointer"
                  >
                </div>

                <!-- Modo de Ajuste (Contain vs Cover) -->
                <div class="bg-slate-900/60 p-2.5 rounded-xl border border-slate-800/80">
                  <div class="flex justify-between text-[10px] font-bold text-slate-300 mb-1">
                    <span>🎯 Modo de Encuadre</span>
                  </div>
                  <div class="grid grid-cols-2 gap-1.5">
                    <button
                      type="button"
                      @click="imageFitMode = 'contain'; requestCanvasRedraw()"
                      :class="['py-1 px-2 rounded-lg text-xs font-bold transition cursor-pointer', imageFitMode === 'contain' ? 'bg-cyan-600 text-white shadow' : 'bg-slate-800 text-slate-400 hover:text-white']"
                    >
                      Completa
                    </button>
                    <button
                      type="button"
                      @click="imageFitMode = 'cover'; requestCanvasRedraw()"
                      :class="['py-1 px-2 rounded-lg text-xs font-bold transition cursor-pointer', imageFitMode === 'cover' ? 'bg-cyan-600 text-white shadow' : 'bg-slate-800 text-slate-400 hover:text-white']"
                    >
                      Rellenar
                    </button>
                  </div>
                </div>

              </div>
            </div>

            <!-- 3. FILTROS VISUALES (BRILLO, CONTRASTE, SATURACIÓN) -->
            <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 space-y-3">
              <label class="text-[11px] uppercase font-bold text-slate-400 block tracking-wider">
                Filtros Visuales &amp; Realce Neón de Carátula
              </label>

              <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                  <div class="flex justify-between text-[10px] font-bold text-slate-300 mb-1">
                    <span>☀️ Brillo</span>
                    <span class="font-mono text-cyan-300">{{ imageBrightness }}%</span>
                  </div>
                  <input 
                    v-model.number="imageBrightness" 
                    @input="requestCanvasRedraw" 
                    type="range" 
                    min="70" 
                    max="140" 
                    step="2" 
                    class="w-full accent-cyan-400 cursor-pointer"
                  >
                </div>

                <div>
                  <div class="flex justify-between text-[10px] font-bold text-slate-300 mb-1">
                    <span>🌗 Contraste</span>
                    <span class="font-mono text-cyan-300">{{ imageContrast }}%</span>
                  </div>
                  <input 
                    v-model.number="imageContrast" 
                    @input="requestCanvasRedraw" 
                    type="range" 
                    min="80" 
                    max="150" 
                    step="2" 
                    class="w-full accent-cyan-400 cursor-pointer"
                  >
                </div>

                <div>
                  <div class="flex justify-between text-[10px] font-bold text-slate-300 mb-1">
                    <span>🎨 Saturación Neón</span>
                    <span class="font-mono text-cyan-300">{{ imageSaturation }}%</span>
                  </div>
                  <input 
                    v-model.number="imageSaturation" 
                    @input="requestCanvasRedraw" 
                    type="range" 
                    min="80" 
                    max="180" 
                    step="2" 
                    class="w-full accent-cyan-400 cursor-pointer"
                  >
                </div>
              </div>
            </div>

          </div>

          <!-- ========================================== -->
          <!-- TAB 3: PLANTILLAS DE DISEÑO & ESTILO       -->
          <!-- ========================================== -->
          <div v-else-if="studioTab === 'design'" class="space-y-4">
            
            <!-- Selector de Plantilla Estructural (Layout) -->
            <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 space-y-3">
              <label class="text-[11px] uppercase font-bold text-slate-400 block tracking-wider">
                1. Plantilla Estructural 9:16 (Cambia dinámicamente el diseño)
              </label>
              <div class="grid grid-cols-2 gap-2.5">
                <button
                  v-for="tpl in layoutTemplates"
                  :key="tpl.id"
                  type="button"
                  @click="currentLayoutId = tpl.id; requestCanvasRedraw()"
                  :class="[
                    'p-3 rounded-xl border text-left transition flex items-start gap-2.5 cursor-pointer',
                    currentLayoutId === tpl.id
                      ? 'bg-indigo-600/20 border-indigo-500 text-white shadow-md'
                      : 'bg-slate-900/60 border-slate-800 text-slate-300 hover:border-slate-700'
                  ]"
                >
                  <span class="text-xl shrink-0 mt-0.5">{{ tpl.icon }}</span>
                  <div>
                    <span class="text-xs font-bold block">{{ tpl.name }}</span>
                    <span class="text-[10px] text-slate-400 block mt-0.5">{{ tpl.desc }}</span>
                  </div>
                </button>
              </div>
            </div>

            <!-- Selector de Tema de Color Neón / Gradiente -->
            <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 space-y-3">
              <label class="text-[11px] uppercase font-bold text-slate-400 block tracking-wider">
                2. Paleta de Color &amp; Efectos Neón
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

            <!-- Sello Holográfico de Confianza -->
            <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 space-y-2.5">
              <label class="text-[11px] uppercase font-bold text-slate-400 block tracking-wider">
                3. Sello Holográfico / Insignia de Confianza
              </label>
              <div class="flex flex-wrap gap-2">
                <button
                  v-for="seal in trustSeals"
                  :key="seal.id"
                  type="button"
                  @click="currentSealText = seal.text; requestCanvasRedraw()"
                  :class="[
                    'px-3 py-1.5 rounded-xl text-xs font-bold transition border cursor-pointer',
                    currentSealText === seal.text
                      ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40 shadow'
                      : 'bg-slate-900 text-slate-400 border-slate-800 hover:text-white'
                  ]"
                >
                  {{ seal.label }}
                </button>
              </div>
            </div>

            <!-- Interruptores de Visibilidad de Elementos -->
            <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 space-y-2.5">
              <label class="text-[11px] uppercase font-bold text-slate-400 block tracking-wider">
                4. Elementos Visibles en la Pieza
              </label>
              <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs">
                <label class="flex items-center gap-2 p-2 bg-slate-900/60 rounded-xl border border-slate-800 cursor-pointer">
                  <input type="checkbox" v-model="showRegularPrice" @change="requestCanvasRedraw" class="accent-pink-500">
                  <span class="text-slate-300">Precio Regular</span>
                </label>
                <label class="flex items-center gap-2 p-2 bg-slate-900/60 rounded-xl border border-slate-800 cursor-pointer">
                  <input type="checkbox" v-model="showPromoPill" @change="requestCanvasRedraw" class="accent-pink-500">
                  <span class="text-slate-300">Cupón Promo</span>
                </label>
                <label class="flex items-center gap-2 p-2 bg-slate-900/60 rounded-xl border border-slate-800 cursor-pointer">
                  <input type="checkbox" v-model="showContactPhone" @change="requestCanvasRedraw" class="accent-pink-500">
                  <span class="text-slate-300">Teléfono WhatsApp</span>
                </label>
                <label class="flex items-center gap-2 p-2 bg-slate-900/60 rounded-xl border border-slate-800 cursor-pointer">
                  <input type="checkbox" v-model="showBrandPill" @change="requestCanvasRedraw" class="accent-pink-500">
                  <span class="text-slate-300">Insignia Tienda</span>
                </label>
              </div>
            </div>

          </div>

          <!-- ========================================== -->
          <!-- TAB 4: TEXTOS & PRECIOS                    -->
          <!-- ========================================== -->
          <div v-else-if="studioTab === 'custom'" class="space-y-4">
            
            <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 space-y-3">
              <label class="text-[11px] uppercase font-bold text-slate-400 block tracking-wider">
                Textos y Precios del Reel
              </label>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Titular Principal</label>
                  <input 
                    v-model="reelHeadline" 
                    @input="requestCanvasRedraw"
                    type="text" 
                    class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-xs text-white font-bold outline-none"
                  >
                </div>

                <div>
                  <label class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Subtítulo / Beneficio</label>
                  <input 
                    v-model="reelSubline" 
                    @input="requestCanvasRedraw"
                    type="text" 
                    class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-xs text-white outline-none"
                  >
                </div>

                <div>
                  <label class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Badge de Oferta</label>
                  <input 
                    v-model="reelBadgeText" 
                    @input="requestCanvasRedraw"
                    type="text" 
                    class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-xs text-amber-300 font-bold outline-none"
                  >
                </div>

                <div>
                  <label class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Botón CTA (Llamado a la Acción)</label>
                  <input 
                    v-model="reelCtaText" 
                    @input="requestCanvasRedraw"
                    type="text" 
                    class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-xs text-pink-300 font-bold outline-none"
                  >
                </div>

                <div>
                  <label class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Precio Promocional (ARS)</label>
                  <input 
                    v-model.number="reelPromoPrice" 
                    @input="requestCanvasRedraw"
                    type="number" 
                    class="w-full bg-slate-900 border border-emerald-500/40 rounded-xl p-2.5 text-sm text-emerald-400 font-black font-mono outline-none"
                  >
                </div>

                <div>
                  <label class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Precio Regular Tachado (ARS)</label>
                  <input 
                    v-model.number="reelRegularPrice" 
                    @input="requestCanvasRedraw"
                    type="number" 
                    class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-xs text-slate-400 font-mono outline-none"
                  >
                </div>

                <div>
                  <label class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Cupón Descuento Destacado</label>
                  <input 
                    v-model="reelPromoCode" 
                    @input="requestCanvasRedraw"
                    type="text" 
                    class="w-full bg-slate-900 border border-amber-500/40 rounded-xl p-2.5 text-xs text-amber-300 font-black font-mono uppercase outline-none"
                  >
                </div>

                <div>
                  <label class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Nombre de Tienda / Distribuidor</label>
                  <input 
                    v-model="reelStoreName" 
                    @input="requestCanvasRedraw"
                    type="text" 
                    class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-xs text-white font-bold outline-none"
                  >
                </div>
              </div>
            </div>

          </div>

          <!-- ========================================== -->
          <!-- TAB 5: GUIÓN VIRAL PARA TIKTOK & REELS     -->
          <!-- ========================================== -->
          <div v-else-if="studioTab === 'script'" class="space-y-4">
            
            <div class="bg-gradient-to-r from-purple-950/40 via-slate-900 to-slate-950 p-5 rounded-2xl border border-purple-500/30 flex items-center justify-between gap-3">
              <div>
                <h4 class="text-sm font-black text-white flex items-center gap-1.5">
                  <span>🎙️ Guión de 15 Segundos para {{ reelHeadline }}</span>
                </h4>
                <p class="text-xs text-slate-300 mt-0.5">
                  Estructura probada con Gancho (Hook), desarrollo y llamado a la acción listo para narrar o usar con la voz de IA.
                </p>
              </div>

              <button
                type="button"
                @click="copyFullScriptPackage"
                class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-black text-xs shadow flex items-center gap-1.5 transition cursor-pointer shrink-0"
              >
                <span>📋</span>
                <span>Copiar Todo</span>
              </button>
            </div>

            <!-- GANCHO -->
            <div class="bg-slate-950 p-4 rounded-2xl border border-pink-500/30 space-y-1.5">
              <span class="text-[10px] uppercase font-black tracking-wider text-pink-400">
                🎯 Gancho de Entrada (Primeros 0 a 3 segundos)
              </span>
              <p class="text-sm font-bold text-white bg-slate-900/80 p-3 rounded-xl border border-white/5">
                "{{ currentReelPackage.hook }}"
              </p>
            </div>

            <!-- VOZ EN OFF -->
            <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 space-y-1.5">
              <span class="text-[10px] uppercase font-black tracking-wider text-indigo-400">
                🗣️ Locución / Voz en Off (3 a 12 segundos)
              </span>
              <p class="text-xs text-slate-200 leading-relaxed bg-slate-900/80 p-3 rounded-xl border border-white/5">
                {{ currentReelPackage.voiceover }}
              </p>
            </div>

            <!-- CTA -->
            <div class="bg-slate-950 p-4 rounded-2xl border border-emerald-500/30 space-y-1.5">
              <span class="text-[10px] uppercase font-black tracking-wider text-emerald-400">
                📲 Cierre &amp; Llamado a la Acción (12 a 15 segundos)
              </span>
              <p class="text-xs font-bold text-white bg-slate-900/80 p-3 rounded-xl border border-white/5">
                {{ currentReelPackage.cta }}
              </p>
            </div>

            <!-- COPY DESCRIPCIÓN -->
            <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 space-y-2">
              <div class="flex items-center justify-between">
                <span class="text-[10px] uppercase font-black tracking-wider text-amber-400">
                  📝 Texto para la Descripción de TikTok / Instagram / WhatsApp
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

          </div>

        </div>

      </div>

    </div>

    <!-- ======================================================== -->
    <!-- MODAL DE GUARDADO EN MÓVIL (VISOR TÁCTIL PARA CELULARES) -->
    <!-- ======================================================== -->
    <div 
      v-if="showMobileSaveModal" 
      class="fixed inset-0 z-[130] bg-black/95 backdrop-blur-md flex flex-col items-center justify-center p-4 animate-in fade-in"
    >
      <div class="max-w-sm w-full bg-slate-900 border border-slate-700 rounded-3xl p-4 flex flex-col items-center text-center shadow-2xl space-y-3">
        <div class="flex items-center justify-between w-full">
          <span class="text-xs font-black text-pink-400 uppercase tracking-wider flex items-center gap-1.5">
            <span>📱</span> Guardar en Móvil
          </span>
          <button 
            type="button" 
            @click="showMobileSaveModal = false" 
            class="text-slate-400 hover:text-white text-base p-1 cursor-pointer"
          >
            ✕
          </button>
        </div>

        <p class="text-xs text-slate-300 font-medium">
          Mantén pulsada la imagen para <strong class="text-white">"Guardar en Fotos"</strong> o compartir a tus Estados.
        </p>

        <!-- Preview táctil de la imagen generada -->
        <div class="w-full max-w-[240px] aspect-[9/16] rounded-2xl overflow-hidden shadow-2xl border border-white/20 bg-black">
          <img 
            :src="mobileSaveImageUrl" 
            alt="Flyer 9:16 StreamePro" 
            class="w-full h-full object-contain select-auto"
          >
        </div>

        <!-- Botonera de descarga directa y compartir -->
        <div class="w-full grid grid-cols-2 gap-2 pt-1">
          <button
            type="button"
            @click="shareDirect"
            class="py-2.5 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow flex items-center justify-center gap-1.5 cursor-pointer"
          >
            <span>📲</span>
            <span>Compartir</span>
          </button>
          
          <button
            type="button"
            @click="downloadImagePng"
            class="py-2.5 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs shadow border border-slate-600 flex items-center justify-center gap-1.5 cursor-pointer"
          >
            <span>💾</span>
            <span>Descargar</span>
          </button>
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
const fileInputRef = ref(null)

// Modos y navegación
const studioTab = ref('product') // 'product' | 'image' | 'design' | 'custom' | 'script'
const mobileViewMode = ref('preview') // 'preview' | 'editor'
const selectedMode = ref('streaming') // 'streaming' | 'gaming' | 'combo' | 'giveaway'
const currentThemeId = ref('cyber_glow')
const currentLayoutId = ref('cyber_pro') // 'cyber_pro' | 'cinematic' | 'glass_vip' | 'split_flash'

// Estado de grabación y animación
const isAnimated = ref(true)
const isRecording = ref(false)
const isExportingImage = ref(false)
const recordingProgress = ref(0)
const recordingSeconds = ref('0.0')

// Modal de guardado para móviles
const showMobileSaveModal = ref(false)
const mobileSaveImageUrl = ref('')

// Detección reactiva de móvil vs PC
const isMobileDevice = computed(() => {
  if (typeof window === 'undefined') return false
  return /iPhone|iPad|iPod|Android|webOS|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || window.innerWidth < 768
})

// ==========================================
// CALIBRACIÓN Y AJUSTES FINOS DE IMAGEN
// ==========================================
const imageZoomFactor = ref(1.0)
const imageOffsetX = ref(0)
const imageOffsetY = ref(0)
const imageRotation = ref(0)
const imageFitMode = ref('contain') // 'contain' | 'cover'
const imageBrightness = ref(100)
const imageContrast = ref(100)
const imageSaturation = ref(100)

// Interruptores de visibilidad
const showRegularPrice = ref(true)
const showPromoPill = ref(true)
const showContactPhone = ref(true)
const showBrandPill = ref(true)
const currentSealText = ref('🛡️ GARANTÍA OFICIAL 30 DÍAS')

// Modelos reactivos de textos
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
const defaultProductCoverUrl = ref('/hero.png')
const customUrlInput = ref('')

// Buscador de Gaming
const gamingSearchTerm = ref('')
const gamingQuickCategory = ref('all')

// Cache en memoria para imágenes precargadas
const imageCache = new Map()

function loadImage(src) {
  if (!src) return Promise.resolve(null)
  if (imageCache.has(src)) return Promise.resolve(imageCache.get(src))
  return new Promise((resolve) => {
    const img = new Image()
    img.crossOrigin = 'anonymous'
    img.onload = () => {
      imageCache.set(src, img)
      resolve(img)
    }
    img.onerror = () => {
      if (src.startsWith('data:') || src.startsWith('blob:')) {
        const localImg = new Image()
        localImg.onload = () => {
          imageCache.set(src, localImg)
          resolve(localImg)
        }
        localImg.onerror = () => resolve(null)
        localImg.src = src
      } else {
        // Fallback seguro a portada local para evitar taining del canvas
        const fallback = new Image()
        fallback.onload = () => resolve(fallback)
        fallback.onerror = () => resolve(null)
        fallback.src = '/hero.png'
      }
    }
    img.src = src
  })
}

// Entidades seleccionadas
const pickedStreamingKey = ref('Netflix')
const pickedStreamingPlanKey = ref('')
const pickedGameId = ref('')
const pickedGamingLicense = ref('primary')
const pickedComboId = ref('')
const pickedGiveawayId = ref('')

const contentModes = [
  { id: 'streaming', icon: '🎬', label: 'Streaming', desc: 'Netflix, Max, Spotify' },
  { id: 'gaming', icon: '🎮', label: 'Videojuegos', desc: 'Buscador PS5/PS4' },
  { id: 'combo', icon: '🎁', label: 'Combos VIP', desc: 'Packs con Super Ahorro' },
  { id: 'giveaway', icon: '🎉', label: 'Sorteos', desc: 'Captación de clientes' }
]

const layoutTemplates = [
  { id: 'cyber_pro', icon: '⚡', name: 'Cyber Pro Oficial', desc: 'Marco tecnológico StreamePro con visores metálicos' },
  { id: 'cinematic', icon: '🎬', name: 'Póster Cinemático 9:16', desc: 'Portada a pantalla completa con viñeta cinemática' },
  { id: 'glass_vip', icon: '💎', name: 'Glassmorphism VIP', desc: 'Cristal esmerilado oscuro con resplandor neón de lujo' },
  { id: 'split_flash', icon: '🥊', name: 'Split Flash / Urgente', desc: 'Diseño 50/50 con cuenta regresiva en vivo' }
]

const visualThemes = [
  {
    id: 'cyber_glow',
    name: 'Cyber Neón',
    previewGrad: 'linear-gradient(135deg, #06b6d4, #6366f1)',
    bg1: '#020617',
    bg2: '#0b1224',
    bg3: '#020617',
    glow: 'rgba(6, 182, 212, 0.45)',
    accent: '#38bdf8',
    pillGrad: ['#06b6d4', '#6366f1']
  },
  {
    id: 'gold_vip',
    name: 'Gold Luxury',
    previewGrad: 'linear-gradient(135deg, #f59e0b, #d97706)',
    bg1: '#090803',
    bg2: '#1c1707',
    bg3: '#030200',
    glow: 'rgba(245, 158, 11, 0.40)',
    accent: '#fbbf24',
    pillGrad: ['#f59e0b', '#d97706']
  },
  {
    id: 'fire_sale',
    name: 'Fuego Urgente',
    previewGrad: 'linear-gradient(135deg, #ef4444, #f97316)',
    bg1: '#120404',
    bg2: '#200a0b',
    bg3: '#040101',
    glow: 'rgba(239, 68, 68, 0.45)',
    accent: '#f87171',
    pillGrad: ['#ef4444', '#f97316']
  },
  {
    id: 'tokyo_pink',
    name: 'Tokyo Synth',
    previewGrad: 'linear-gradient(135deg, #ec4899, #8b5cf6)',
    bg1: '#0e0314',
    bg2: '#1d0b26',
    bg3: '#020104',
    glow: 'rgba(236, 72, 153, 0.45)',
    accent: '#f472b6',
    pillGrad: ['#ec4899', '#8b5cf6']
  }
]

const trustSeals = [
  { id: 'warranty', label: '🛡️ Garantía 30 Días', text: '🛡️ GARANTÍA OFICIAL 30 DÍAS' },
  { id: 'speed', label: '⚡ Entrega 15 Min', text: '⚡ ENTREGA INMEDIATA 15 MIN' },
  { id: 'pin', label: '🔒 PIN Exclusivo', text: '🔒 100% CUENTA PRIVADA CON PIN' },
  { id: 'psn', label: '🎮 Jugá en tu Perfil', text: '🎮 JUGÁ CON TU USUARIO (PSN)' },
  { id: 'flash', label: '🔥 Oferta 24 Hs', text: '🔥 OFERTA LIMITADA 24 HS' },
  { id: 'none', label: 'Ninguno', text: '' }
]

const gamingQuickFilters = [
  { id: 'all', label: '🔥 Destacados' },
  { id: 'deportes', label: '⚽ Deportes' },
  { id: 'accion', label: '💥 Acción' },
  { id: 'terror', label: '🧟 Terror' }
]

const artworkPresets = [
  { id: 'cyber_gamer', icon: '🎮', label: 'Cyber Gaming', url: '/hero.png' },
  { id: 'ps5_neon', icon: '⚡', label: 'PS5 Neon Blue', url: '/flyers/gaming-template-story.jpg' },
  { id: 'cinema_pop', icon: '🍿', label: 'Cinema Lounge', url: '/flyers/combo-cover.jpg' },
  { id: 'gold_vip', icon: '💎', label: 'Gold Luxury', url: '/flyers/cover-netflix.jpg' }
]

const currentTheme = computed(() => {
  return visualThemes.find(t => t.id === currentThemeId.value) || visualThemes[0]
})

const currentLayoutName = computed(() => {
  return layoutTemplates.find(l => l.id === currentLayoutId.value)?.name || 'Cyber Pro'
})

const currentStreamingPlans = computed(() => {
  return adminStore.pricingCatalog[pickedStreamingKey.value]?.plans || {}
})

// Filtrado de Videojuegos
const filteredGamingGames = computed(() => {
  const q = gamingSearchTerm.value.toLowerCase().trim()
  const list = adminStore.processedGamingProducts || []
  
  if (!q) {
    if (gamingQuickCategory.value === 'deportes') {
      return list.filter(g => (g.title || '').toLowerCase().match(/fc|fifa|nba|wwe|f1|moto|tennis|golf/)).slice(0, 30)
    }
    if (gamingQuickCategory.value === 'accion') {
      return list.filter(g => (g.title || '').toLowerCase().match(/gta|spider|god of war|last of us|uncharted|batman|assassin|call of duty/)).slice(0, 30)
    }
    if (gamingQuickCategory.value === 'terror') {
      return list.filter(g => (g.title || '').toLowerCase().match(/silent|resident|evil|outlast|dead|horror|alan wake/)).slice(0, 30)
    }
    return list.slice(0, 30)
  }

  return list.filter(g => {
    const titleMatch = (g.title || '').toLowerCase().includes(q)
    const slugMatch = (g.slug || '').toLowerCase().includes(q)
    return titleMatch || slugMatch
  }).slice(0, 60)
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

function selectGamingQuickFilter(filterId) {
  gamingQuickCategory.value = filterId
  gamingSearchTerm.value = ''
}

function selectGamingProduct(game) {
  if (!game) return
  pickedGameId.value = game.id
  applyGamingProduct()
}

function selectContentMode(modeId) {
  selectedMode.value = modeId
  if (modeId === 'streaming') {
    pickedStreamingKey.value = Object.keys(adminStore.pricingCatalog)[0] || 'Netflix'
    onStreamingServiceChange()
  } else if (modeId === 'gaming') {
    if (!adminStore.isGamingCatalogLoaded) {
      adminStore.loadFullGamingCatalog().catch(() => {})
    }
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
  currentSealText.value = '🔒 100% CUENTA PRIVADA CON PIN'
  const cover = `/flyers/cover-${srvKey.toLowerCase().replace(/[^a-z0-9]/g, '')}.jpg`
  reelArtworkUrl.value = cover
  defaultProductCoverUrl.value = cover
  loadArtwork(cover)
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
  currentSealText.value = isPri ? '🎮 JUGÁ CON TU USUARIO (PSN)' : '⚡ ENTREGA INMEDIATA 15 MIN'
  const cover = g.image || '/hero.png'
  reelArtworkUrl.value = cover
  defaultProductCoverUrl.value = cover
  loadArtwork(cover)
}

function onComboChange() {
  const c = adminStore.packages.find(p => p.id === pickedComboId.value)
  if (!c) return
  reelHeadline.value = (c.title || 'Mega Combo').toUpperCase().slice(0, 34)
  reelSubline.value = (c.description || 'Suscripciones combinadas con descuento').slice(0, 48)
  reelPromoPrice.value = Number(c.promoPrice || 12000)
  reelRegularPrice.value = Number(c.regularPrice || Math.round(reelPromoPrice.value * 1.4))
  reelBadgeText.value = c.badge || '🎁 SUPER AHORRO'
  currentSealText.value = '🛡️ GARANTÍA OFICIAL 30 DÍAS'
  const cover = '/flyers/combo-cover.jpg'
  reelArtworkUrl.value = cover
  defaultProductCoverUrl.value = cover
  loadArtwork(cover)
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
  currentSealText.value = '🎟️ ACTA PÚBLICA TRANSPARENTE'
  const cover = '/hero.png'
  reelArtworkUrl.value = cover
  defaultProductCoverUrl.value = cover
  loadArtwork(cover)
}

async function loadArtwork(src) {
  if (!src) return
  const img = await loadImage(src)
  if (img) {
    loadedArtworkImage.value = img
    requestCanvasRedraw()
  } else if (src !== '/hero.png') {
    loadArtwork('/hero.png')
  }
}

// ==========================================
// ACCIONES PARA CAMBIAR Y AJUSTAR IMÁGENES
// ==========================================
function triggerCustomFileUpload() {
  fileInputRef.value?.click()
}

function handleCustomFileUpload(e) {
  const file = e.target?.files?.[0]
  if (!file) return
  const reader = new FileReader()
  reader.onload = (event) => {
    const dataUrl = event.target?.result
    if (dataUrl) {
      reelArtworkUrl.value = dataUrl
      loadArtwork(dataUrl)
      toast.add('🖼️ Imagen subida correctamente', 'success')
    }
  }
  reader.readAsDataURL(file)
  if (e.target) e.target.value = ''
}

function applyCustomUrlImage() {
  const url = customUrlInput.value.trim()
  if (!url) return
  reelArtworkUrl.value = url
  loadArtwork(url)
  customUrlInput.value = ''
  toast.add('🖼️ Imagen web aplicada', 'success')
}

function selectArtworkPreset(url) {
  if (!url) return
  reelArtworkUrl.value = url
  loadArtwork(url)
  toast.add('🎨 Fondo temático aplicado', 'success')
}

function resetToOriginalProductCover() {
  reelArtworkUrl.value = defaultProductCoverUrl.value || '/hero.png'
  loadArtwork(reelArtworkUrl.value)
  resetImageTransformations()
  toast.add('↺ Portada original restaurada', 'success')
}

function resetImageTransformations() {
  imageZoomFactor.value = 1.0
  imageOffsetX.value = 0
  imageOffsetY.value = 0
  imageRotation.value = 0
  imageBrightness.value = 100
  imageContrast.value = 100
  imageSaturation.value = 100
  requestCanvasRedraw()
}

// ========================================================
// INTERACCIÓN TÁCTIL Y MOUSE DRAG DIRECTO SOBRE EL CANVAS
// ========================================================
let isPointerDragging = false
let dragStartPointerX = 0
let dragStartPointerY = 0
let dragInitialOffsetX = 0
let dragInitialOffsetY = 0
let dragContainerWidth = 1
let dragContainerHeight = 1

function onCanvasPointerDown(e) {
  const container = e.currentTarget
  if (!container) return
  try {
    container.setPointerCapture?.(e.pointerId)
  } catch {}
  const rect = container.getBoundingClientRect()
  isPointerDragging = true
  dragStartPointerX = e.clientX
  dragStartPointerY = e.clientY
  dragInitialOffsetX = imageOffsetX.value
  dragInitialOffsetY = imageOffsetY.value
  dragContainerWidth = Math.max(1, rect.width)
  dragContainerHeight = Math.max(1, rect.height)
}

function onCanvasPointerMove(e) {
  if (!isPointerDragging) return
  e.preventDefault?.()
  const dxPct = ((e.clientX - dragStartPointerX) / dragContainerWidth) * 100
  const dyPct = ((e.clientY - dragStartPointerY) / dragContainerHeight) * 100
  imageOffsetX.value = Math.max(-45, Math.min(45, Math.round(dragInitialOffsetX + dxPct)))
  imageOffsetY.value = Math.max(-45, Math.min(45, Math.round(dragInitialOffsetY + dyPct)))
  requestCanvasRedraw()
}

function onCanvasPointerUp(e) {
  isPointerDragging = false
  if (e?.currentTarget && e.pointerId) {
    try {
      e.currentTarget.releasePointerCapture?.(e.pointerId)
    } catch {}
  }
}

// ==========================================
// MOTOR CANVAS ELEVADO 9:16 (1080x1920)
// ==========================================
let animFrameId = null
let animationStartTime = 0
let recordingInterval = null
let recordingTimeout = null

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

function drawCornerBrackets(ctx, x, y, w, h, size = 30, color = 'rgba(56, 189, 248, 0.75)') {
  ctx.save()
  ctx.strokeStyle = color
  ctx.lineWidth = 4
  ctx.lineCap = 'round'
  ctx.beginPath(); ctx.moveTo(x, y + size); ctx.lineTo(x, y); ctx.lineTo(x + size, y); ctx.stroke()
  ctx.beginPath(); ctx.moveTo(x + w - size, y); ctx.lineTo(x + w, y); ctx.lineTo(x + w, y + size); ctx.stroke()
  ctx.beginPath(); ctx.moveTo(x, y + h - size); ctx.lineTo(x, y + h); ctx.lineTo(x + size, y + h); ctx.stroke()
  ctx.beginPath(); ctx.moveTo(x + w - size, y + h); ctx.lineTo(x + w, y + h); ctx.lineTo(x + w, y + h - size); ctx.stroke()
  ctx.restore()
}

// Visualizador de Audio Animado (Trending TikTok/Reels Audio Wave)
function drawAudioEqualizer(ctx, x, y, barCount = 7, color = '#38bdf8', t = 0) {
  ctx.save()
  const barW = 6
  const gap = 4
  const totalW = barCount * barW + (barCount - 1) * gap
  const startX = x - totalW / 2
  ctx.fillStyle = color
  for (let i = 0; i < barCount; i++) {
    const h = 10 + Math.abs(Math.sin(t * 7 + i * 0.9)) * 26
    const bx = startX + i * (barW + gap)
    const by = y - h / 2
    roundRect(ctx, bx, by, barW, h, 3)
    ctx.fill()
  }
  ctx.restore()
}

// Haz de luz / Escáner Cyber horizontal
function drawLaserScanline(ctx, x, y, w, h, t = 0, color = 'rgba(56, 189, 248, 0.45)') {
  const scanY = y + ((t * 260) % (h + 80)) - 40
  if (scanY >= y && scanY <= y + h) {
    const scanGrad = ctx.createLinearGradient(0, scanY - 14, 0, scanY + 14)
    scanGrad.addColorStop(0, 'rgba(0,0,0,0)')
    scanGrad.addColorStop(0.5, color)
    scanGrad.addColorStop(1, 'rgba(0,0,0,0)')
    ctx.fillStyle = scanGrad
    ctx.fillRect(x, scanY - 14, w, 28)
  }
}

// Renderiza la imagen centrada con todos los ajustes finos y animación
function drawAdjustedArtwork(ctx, img, targetX, targetY, targetW, targetH, t, options = {}) {
  if (!img) return

  const { blurBg = true, showReticle = isPointerDragging, addVignette = true } = options
  const innerX = targetX
  const innerY = targetY
  const innerW = targetW
  const innerH = targetH

  // 1. Fondo ambiental saturado difuso
  if (blurBg) {
    const bgScale = Math.max(innerW / (img.width || 1), innerH / (img.height || 1)) * 1.25
    const bgW = (img.width || 1) * bgScale
    const bgH = (img.height || 1) * bgScale
    ctx.save()
    ctx.filter = 'blur(28px) brightness(0.40) saturate(1.4)'
    ctx.drawImage(img, innerX + (innerW - bgW) / 2, innerY + (innerH - bgH) / 2, bgW, bgH)
    ctx.restore()
  }

  // 2. Portada nítida en primer plano con Zoom, Desplazamiento X/Y, Inclinación y Filtros
  const zoomAnim = isAnimated.value ? (1.0 + Math.sin(t * 1.4) * 0.03) : 1.0
  const finalZoom = zoomAnim * (Number(imageZoomFactor.value) || 1.0)
  let baseW = innerW
  let baseH = innerH

  if (imageFitMode.value === 'contain') {
    const imgAspect = (img.width || 1) / (img.height || 1)
    const boxAspect = innerW / innerH
    if (boxAspect > imgAspect) {
      baseH = innerH - 40
      baseW = baseH * imgAspect
    } else {
      baseW = innerW - 40
      baseH = baseW / imgAspect
    }
  }

  const dw = baseW * finalZoom
  const dh = baseH * finalZoom
  const panX = ((Number(imageOffsetX.value) || 0) / 100) * innerW
  const panY = ((Number(imageOffsetY.value) || 0) / 100) * innerH
  const centerX = innerX + innerW / 2 + panX
  const centerY = innerY + innerH / 2 + panY

  ctx.save()
  ctx.translate(centerX, centerY)
  if (imageRotation.value) {
    ctx.rotate((imageRotation.value * Math.PI) / 180)
  }
  ctx.filter = `brightness(${imageBrightness.value}%) contrast(${imageContrast.value}%) saturate(${imageSaturation.value}%)`
  ctx.shadowColor = 'rgba(0, 0, 0, 0.9)'
  ctx.shadowBlur = 35
  ctx.shadowOffsetY = 12
  ctx.drawImage(img, -dw / 2, -dh / 2, dw, dh)
  ctx.restore()

  // 3. Efecto Escáner Neón en Movimiento
  if (isAnimated.value) {
    drawLaserScanline(ctx, innerX, innerY, innerW, innerH, t, currentTheme.value.glow)
  }

  // 4. Retícula táctil de arrastre (cuando el usuario está interactuando)
  if (showReticle) {
    ctx.save()
    ctx.strokeStyle = currentTheme.value.accent
    ctx.lineWidth = 3
    ctx.beginPath()
    ctx.arc(centerX, centerY, 32, 0, Math.PI * 2)
    ctx.moveTo(centerX - 48, centerY); ctx.lineTo(centerX + 48, centerY)
    ctx.moveTo(centerX, centerY - 48); ctx.lineTo(centerX, centerY + 48)
    ctx.stroke()
    ctx.restore()
  }

  // 5. Viñeta oscura inferior para legibilidad
  if (addVignette) {
    const vigGrad = ctx.createLinearGradient(0, innerY + innerH - 240, 0, innerY + innerH)
    vigGrad.addColorStop(0, 'rgba(0,0,0,0)')
    vigGrad.addColorStop(1, 'rgba(2, 6, 23, 0.95)')
    ctx.fillStyle = vigGrad
    ctx.fillRect(innerX, innerY + innerH - 240, innerW, 240)
  }
}

// RENDERIZADO COMPLETO DEL FRAME 9:16 (1080x1920) CON 4 PLANTILLAS VISUALES DISTINTAS
function renderFrame(timeMs = 0) {
  const canvas = canvasRef.value
  if (!canvas) return
  const ctx = canvas.getContext('2d')
  if (!ctx) return

  const W = 1080
  const H = 1920
  const th = currentTheme.value
  const t = timeMs * 0.001
  const layout = currentLayoutId.value

  // ==========================================
  // 1. FONDO AMBIENTAL SEGÚN PLANTILLA
  // ==========================================
  if (layout === 'cinematic' && loadedArtworkImage.value) {
    // EN CINEMÁTICO: LA IMAGEN CUBRE TODO EL 9:16 CON VIÑETAS DRAMÁTICAS
    const img = loadedArtworkImage.value
    const bgScale = Math.max(W / (img.width || 1), H / (img.height || 1))
    const bw = (img.width || 1) * bgScale
    const bh = (img.height || 1) * bgScale
    ctx.save()
    ctx.filter = `brightness(${imageBrightness.value * 0.85}%) contrast(${imageContrast.value * 1.1}%) saturate(${imageSaturation.value * 1.2}%)`
    ctx.drawImage(img, (W - bw) / 2, (H - bh) / 2, bw, bh)
    ctx.restore()

    // Viñeta Cinematográfica Profunda Superior e Inferior
    const topVig = ctx.createLinearGradient(0, 0, 0, 520)
    topVig.addColorStop(0, 'rgba(2, 6, 23, 0.95)')
    topVig.addColorStop(0.65, 'rgba(2, 6, 23, 0.65)')
    topVig.addColorStop(1, 'rgba(2, 6, 23, 0)')
    ctx.fillStyle = topVig
    ctx.fillRect(0, 0, W, 520)

    const botVig = ctx.createLinearGradient(0, H - 980, 0, H)
    botVig.addColorStop(0, 'rgba(2, 6, 23, 0)')
    botVig.addColorStop(0.35, 'rgba(2, 6, 23, 0.78)')
    botVig.addColorStop(1, 'rgba(2, 6, 23, 0.98)')
    ctx.fillStyle = botVig
    ctx.fillRect(0, H - 980, W, 980)

  } else {
    // FONDOS CON GRADIENTE Y PARTÍCULAS (CYBER PRO, GLASS VIP, SPLIT FLASH)
    const bgGrad = ctx.createLinearGradient(0, 0, 0, H)
    bgGrad.addColorStop(0, th.bg1)
    bgGrad.addColorStop(0.45, th.bg2)
    bgGrad.addColorStop(1, th.bg3)
    ctx.fillStyle = bgGrad
    ctx.fillRect(0, 0, W, H)

    // Resplandor Neón Central
    const radialGlow = ctx.createRadialGradient(W * 0.5, H * 0.38, 40, W * 0.5, H * 0.38, 720)
    radialGlow.addColorStop(0, th.glow)
    radialGlow.addColorStop(1, 'rgba(0,0,0,0)')
    ctx.fillStyle = radialGlow
    ctx.fillRect(0, 0, W, H)
  }

  // Partículas Neón flotantes animadas
  if (isAnimated.value) {
    ctx.save()
    for (let i = 0; i < 22; i++) {
      const px = (W * ((i * 0.047 + t * 0.025) % 1))
      const py = (H * ((i * 0.063 + Math.sin(t + i) * 0.04 + 0.08) % 0.95))
      const pAlpha = 0.2 + Math.sin(t * 3 + i) * 0.15
      ctx.fillStyle = `rgba(255, 255, 255, ${Math.max(0.04, pAlpha)})`
      ctx.beginPath()
      ctx.arc(px, py, (i % 3) + 2.5, 0, Math.PI * 2)
      ctx.fill()
    }
    ctx.restore()
  }

  // Marco Cyber 9:16 con esquinas HUD (en Cyber Pro, Glass VIP y Split Flash)
  if (layout !== 'cinematic') {
    ctx.save()
    ctx.strokeStyle = 'rgba(255, 255, 255, 0.12)'
    ctx.lineWidth = 4
    roundRect(ctx, 32, 32, W - 64, H - 64, 48)
    ctx.stroke()
    drawCornerBrackets(ctx, 32, 32, W - 64, H - 64, 40, th.accent)
    ctx.restore()
  }

  // Header: Insignia de Tienda / Distribuidor
  if (showBrandPill.value) {
    ctx.save()
    const brandPillW = 460
    const brandPillH = 72
    const brandPillX = (W - brandPillW) / 2
    const brandPillY = 80
    ctx.fillStyle = 'rgba(15, 23, 42, 0.90)'
    roundRect(ctx, brandPillX, brandPillY, brandPillW, brandPillH, 36)
    ctx.fill()
    ctx.strokeStyle = 'rgba(255, 255, 255, 0.18)'
    ctx.lineWidth = 2
    ctx.stroke()

    ctx.fillStyle = '#ffffff'
    ctx.font = '900 24px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
    ctx.textAlign = 'center'
    ctx.fillText(`⚡ ${reelStoreName.value.toUpperCase()} · OFICIAL`, W / 2, brandPillY + 45)
    ctx.restore()
  }

  // Ecualizador de Audio / Trending Sound Bar en Header
  if (isAnimated.value) {
    drawAudioEqualizer(ctx, W / 2, 168, 9, th.accent, t)
  }

  // Titular Principal y Subtítulo
  ctx.save()
  ctx.fillStyle = '#ffffff'
  ctx.font = '900 58px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
  ctx.textAlign = 'center'
  ctx.shadowColor = 'rgba(0, 0, 0, 0.85)'
  ctx.shadowBlur = 20
  ctx.fillText(reelHeadline.value.slice(0, 36), W / 2, 230)

  ctx.fillStyle = th.accent
  ctx.font = 'bold 30px sans-serif'
  ctx.fillText(reelSubline.value.slice(0, 52), W / 2, 282)
  ctx.restore()

  // ==========================================
  // 2. RENDERIZADO SEGÚN PLANTILLA
  // ==========================================

  // ----------------------------------------------------
  // PLANTILLA 1: CYBER PRO OFICIAL (HUD Y MARCO PRO)
  // ----------------------------------------------------
  if (layout === 'cyber_pro') {
    const cardX = 85
    const cardY = 325
    const cardW = W - 170
    const cardH = 810

    // Fondo y borde del visor
    ctx.save()
    ctx.shadowColor = th.glow
    ctx.shadowBlur = 45
    ctx.fillStyle = '#050a18'
    roundRect(ctx, cardX, cardY, cardW, cardH, 44)
    ctx.fill()
    ctx.strokeStyle = th.accent
    ctx.lineWidth = 4
    roundRect(ctx, cardX, cardY, cardW, cardH, 44)
    ctx.stroke()
    ctx.restore()

    if (loadedArtworkImage.value) {
      ctx.save()
      roundRect(ctx, cardX + 6, cardY + 6, cardW - 12, cardH - 12, 38)
      ctx.clip()
      drawAdjustedArtwork(ctx, loadedArtworkImage.value, cardX + 6, cardY + 6, cardW - 12, cardH - 12, t)
      ctx.restore()
    }

    // Badge en portada
    if (reelBadgeText.value) {
      drawBadgePill(ctx, cardX + 30, cardY + 30, 360, 68, reelBadgeText.value, th.pillGrad)
    }

    // Sello de confianza
    if (currentSealText.value) {
      drawTrustSealPill(ctx, cardX + cardW - 440 - 24, cardY + cardH - 58 - 24, 440, 58, currentSealText.value, th.accent)
    }

    // Zona de Precios
    drawPriceBox(ctx, cardX, 1170, cardW, 310, th, t)

    // Botón CTA Pulsante
    drawCtaButton(ctx, cardX, 1515, cardW, 104, t)
  }

  // ----------------------------------------------------
  // PLANTILLA 2: PÓSTER CINEMÁTICO 9:16 (FULL-BLEED)
  // ----------------------------------------------------
  else if (layout === 'cinematic') {
    // En cinemático la imagen cubre todo el fondo (ya dibujada arriba).
    // Aquí destacamos elementos flotantes tipo cartelera de cine
    const cardX = 85
    const cardW = W - 170

    // Marquee de estreno
    ctx.save()
    ctx.fillStyle = th.accent
    ctx.font = '900 22px monospace'
    ctx.textAlign = 'center'
    ctx.fillText('★ ESTRENO EXCLUSIVO · STREAMEPRO STUDIOS ★', W / 2, 335)
    ctx.restore()

    // Badge Flotante Cinemático
    if (reelBadgeText.value) {
      drawBadgePill(ctx, cardX + 20, 370, 360, 68, reelBadgeText.value, th.pillGrad)
    }

    // Placa de Precio Glassmorphism Translúcida Flotante
    ctx.save()
    const glassY = 1100
    const glassH = 340
    ctx.fillStyle = 'rgba(2, 6, 23, 0.88)'
    roundRect(ctx, cardX, glassY, cardW, glassH, 44)
    ctx.fill()
    ctx.strokeStyle = th.accent
    ctx.lineWidth = 3
    ctx.stroke()

    // Precios
    drawPriceContent(ctx, glassY, cardW, th, t)
    ctx.restore()

    // Sello de confianza
    if (currentSealText.value) {
      drawTrustSealPill(ctx, (W - 480) / 2, 1475, 480, 56, currentSealText.value, th.accent)
    }

    // Botón CTA Cinemático
    drawCtaButton(ctx, cardX, 1560, cardW, 104, t)
  }

  // ----------------------------------------------------
  // PLANTILLA 3: GLASSMORPHISM VIP LUXURY
  // ----------------------------------------------------
  else if (layout === 'glass_vip') {
    const cardX = 85
    const cardY = 320
    const cardW = W - 170
    const cardH = 790

    // Tarjeta VIP de Cristal Esmerilado Profundo
    ctx.save()
    ctx.shadowColor = th.glow
    ctx.shadowBlur = 55
    ctx.fillStyle = 'rgba(15, 23, 42, 0.85)'
    roundRect(ctx, cardX, cardY, cardW, cardH, 48)
    ctx.fill()
    ctx.strokeStyle = 'rgba(255, 255, 255, 0.28)'
    ctx.lineWidth = 4
    roundRect(ctx, cardX, cardY, cardW, cardH, 48)
    ctx.stroke()
    ctx.restore()

    if (loadedArtworkImage.value) {
      ctx.save()
      roundRect(ctx, cardX + 8, cardY + 8, cardW - 16, cardH - 16, 40)
      ctx.clip()
      drawAdjustedArtwork(ctx, loadedArtworkImage.value, cardX + 8, cardY + 8, cardW - 16, cardH - 16, t)
      ctx.restore()
    }

    // Cinta VIP Superior
    ctx.save()
    const vipW = 380
    const vipH = 60
    const vx = (W - vipW) / 2
    const vy = cardY - 26
    const vipGrad = ctx.createLinearGradient(vx, vy, vx + vipW, vy)
    vipGrad.addColorStop(0, '#f59e0b')
    vipGrad.addColorStop(1, '#d97706')
    ctx.fillStyle = vipGrad
    roundRect(ctx, vx, vy, vipW, vipH, 30)
    ctx.fill()
    ctx.fillStyle = '#020617'
    ctx.font = '900 22px -apple-system, sans-serif'
    ctx.textAlign = 'center'
    ctx.fillText('👑 EDICIÓN VIP OFICIAL', W / 2, vy + 38)
    ctx.restore()

    if (reelBadgeText.value) {
      drawBadgePill(ctx, cardX + 30, cardY + 50, 360, 68, reelBadgeText.value, th.pillGrad)
    }

    if (currentSealText.value) {
      drawTrustSealPill(ctx, cardX + cardW - 440 - 24, cardY + cardH - 58 - 24, 440, 58, currentSealText.value, th.accent)
    }

    drawPriceBox(ctx, cardX, 1150, cardW, 310, th, t)
    drawCtaButton(ctx, cardX, 1495, cardW, 104, t)
  }

  // ----------------------------------------------------
  // PLANTILLA 4: SPLIT FLASH / URGENCIA RELÁMPAGO (50/50)
  // ----------------------------------------------------
  else if (layout === 'split_flash') {
    const cardX = 85
    const cardY = 310
    const cardW = W - 170
    const cardH = 680

    // Marco Superior de Acción
    ctx.save()
    ctx.fillStyle = '#050a18'
    roundRect(ctx, cardX, cardY, cardW, cardH, 40)
    ctx.fill()
    ctx.strokeStyle = '#ef4444' // Borde Rojo Fuego Urgente
    ctx.lineWidth = 4
    roundRect(ctx, cardX, cardY, cardW, cardH, 40)
    ctx.stroke()
    ctx.restore()

    if (loadedArtworkImage.value) {
      ctx.save()
      roundRect(ctx, cardX + 6, cardY + 6, cardW - 12, cardH - 12, 34)
      ctx.clip()
      drawAdjustedArtwork(ctx, loadedArtworkImage.value, cardX + 6, cardY + 6, cardW - 12, cardH - 12, t)
      ctx.restore()
    }

    // Tira Neón Divisora Relámpago con Cuenta Regresiva
    ctx.save()
    const stripY = cardY + cardH + 16
    const stripH = 64
    const sGrad = ctx.createLinearGradient(cardX, stripY, cardX + cardW, stripY)
    sGrad.addColorStop(0, '#ef4444')
    sGrad.addColorStop(0.5, '#f97316')
    sGrad.addColorStop(1, '#ef4444')
    ctx.fillStyle = sGrad
    roundRect(ctx, cardX, stripY, cardW, stripH, 32)
    ctx.fill()

    // Cuenta regresiva dinámica
    const baseSec = 15838 - Math.floor((t * 2) % 3600)
    const hrs = String(Math.floor(baseSec / 3600)).padStart(2, '0')
    const mins = String(Math.floor((baseSec % 3600) / 60)).padStart(2, '0')
    const secs = String(baseSec % 60).padStart(2, '0')

    ctx.fillStyle = '#ffffff'
    ctx.font = '900 26px monospace'
    ctx.textAlign = 'center'
    ctx.fillText(`⚡ OFERTA FLASH · TERMINA EN ${hrs}:${mins}:${secs} ⚡`, W / 2, stripY + 42)
    ctx.restore()

    // Panel Inferior de Conversión Urgente
    const lowerY = stripY + stripH + 18
    const lowerH = 430
    ctx.save()
    ctx.fillStyle = 'rgba(10, 16, 32, 0.96)'
    roundRect(ctx, cardX, lowerY, cardW, lowerH, 40)
    ctx.fill()
    ctx.strokeStyle = 'rgba(239, 68, 68, 0.45)'
    ctx.lineWidth = 3
    roundRect(ctx, cardX, lowerY, cardW, lowerH, 40)
    ctx.stroke()

    drawPriceContent(ctx, lowerY, cardW, th, t)
    ctx.restore()

    drawCtaButton(ctx, cardX, 1540, cardW, 104, t)
  }

  // ==========================================
  // 3. PIE DE PÁGINA Y CONTACTO DE CIERRE
  // ==========================================
  ctx.save()
  if (showContactPhone.value && props.contactPhone) {
    ctx.fillStyle = '#ffffff'
    ctx.font = '900 28px -apple-system, sans-serif'
    ctx.textAlign = 'center'
    ctx.fillText(`📲 WhatsApp de Pedidos: ${props.contactPhone}`, W / 2, 1705)
  }

  ctx.fillStyle = th.accent
  ctx.font = 'bold 24px monospace'
  const domainText = props.resellerRef && props.resellerRef !== 'DIRECT'
    ? `${props.websiteDomain || 'streamepro.me'}/ref/${props.resellerRef}`
    : `${props.websiteDomain || 'streamepro.me'}`
  ctx.textAlign = 'center'
  ctx.fillText(`🔗 ${domainText}`, W / 2, 1752)

  ctx.fillStyle = 'rgba(148, 163, 184, 0.82)'
  ctx.font = '20px sans-serif'
  ctx.fillText('Activación guiada · Cuentas oficiales garantizadas · StreamePro', W / 2, 1798)
  ctx.restore()
}

// Helpers modulares de dibujo
function drawBadgePill(ctx, bx, by, badgeW, badgeH, text, pillGrad) {
  ctx.save()
  const bGrad = ctx.createLinearGradient(bx, by, bx + badgeW, by)
  bGrad.addColorStop(0, pillGrad[0])
  bGrad.addColorStop(1, pillGrad[1])
  ctx.fillStyle = bGrad
  roundRect(ctx, bx, by, badgeW, badgeH, 34)
  ctx.fill()
  ctx.fillStyle = '#ffffff'
  ctx.font = '900 25px -apple-system, sans-serif'
  ctx.textAlign = 'center'
  ctx.fillText(text, bx + badgeW / 2, by + 43)
  ctx.restore()
}

function drawTrustSealPill(ctx, sx, sy, sealW, sealH, text, accentColor) {
  ctx.save()
  ctx.fillStyle = 'rgba(2, 6, 23, 0.88)'
  roundRect(ctx, sx, sy, sealW, sealH, 29)
  ctx.fill()
  ctx.strokeStyle = accentColor
  ctx.lineWidth = 2
  ctx.stroke()
  ctx.fillStyle = '#ffffff'
  ctx.font = '900 20px -apple-system, sans-serif'
  ctx.textAlign = 'center'
  ctx.fillText(text, sx + sealW / 2, sy + 37)
  ctx.restore()
}

function drawPriceBox(ctx, boxX, boxY, boxW, boxH, th, t) {
  ctx.save()
  ctx.fillStyle = 'rgba(10, 16, 32, 0.94)'
  roundRect(ctx, boxX, boxY, boxW, boxH, 38)
  ctx.fill()
  ctx.strokeStyle = 'rgba(255, 255, 255, 0.14)'
  ctx.lineWidth = 2
  ctx.stroke()
  drawPriceContent(ctx, boxY, boxW, th, t)
  ctx.restore()
}

function drawPriceContent(ctx, boxY, boxW, th, t) {
  const W = 1080
  // Precio regular tachado
  if (showRegularPrice.value && Number(reelRegularPrice.value) > 0 && Number(reelRegularPrice.value) > Number(reelPromoPrice.value)) {
    ctx.fillStyle = '#94a3b8'
    ctx.font = 'bold 30px sans-serif'
    ctx.textAlign = 'center'
    const regText = `Precio Regular: $${Number(reelRegularPrice.value).toLocaleString('es-AR')} ARS`
    ctx.fillText(regText, W / 2, boxY + 58)

    const tw = ctx.measureText(regText).width
    ctx.strokeStyle = '#f43f5e'
    ctx.lineWidth = 3
    ctx.beginPath()
    ctx.moveTo(W / 2 - tw / 2 - 12, boxY + 48)
    ctx.lineTo(W / 2 + tw / 2 + 12, boxY + 48)
    ctx.stroke()
  }

  // Precio Promocional Gigante
  if (Number(reelPromoPrice.value) > 0) {
    ctx.fillStyle = '#4ade80'
    ctx.font = '900 88px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
    ctx.textAlign = 'center'
    ctx.shadowColor = 'rgba(74, 222, 128, 0.45)'
    ctx.shadowBlur = 28
    ctx.fillText(`$${Number(reelPromoPrice.value).toLocaleString('es-AR')} ARS`, W / 2, boxY + 160)
  } else {
    ctx.fillStyle = '#fde047'
    ctx.font = '900 80px -apple-system, sans-serif'
    ctx.textAlign = 'center'
    ctx.fillText('¡SORTEO GRATIS!', W / 2, boxY + 160)
  }

  // Pill del Cupón Promocional con Destello Animado
  if (showPromoPill.value && reelPromoCode.value) {
    ctx.save()
    const cpW = boxW - 60
    const cpH = 74
    const cpX = (W - cpW) / 2
    const cpY = boxY + 205

    const cpGrad = ctx.createLinearGradient(cpX, cpY, cpX + cpW, cpY)
    cpGrad.addColorStop(0, '#f59e0b')
    cpGrad.addColorStop(1, '#d97706')
    ctx.fillStyle = cpGrad
    roundRect(ctx, cpX, cpY, cpW, cpH, 37)
    ctx.fill()

    if (isAnimated.value) {
      const shimmerX = cpX + ((t * 420) % (cpW + 240)) - 120
      const shimGrad = ctx.createLinearGradient(shimmerX, cpY, shimmerX + 90, cpY)
      shimGrad.addColorStop(0, 'rgba(255,255,255,0)')
      shimGrad.addColorStop(0.5, 'rgba(255,255,255,0.48)')
      shimGrad.addColorStop(1, 'rgba(255,255,255,0)')
      ctx.fillStyle = shimGrad
      roundRect(ctx, cpX, cpY, cpW, cpH, 37)
      ctx.fill()
    }

    ctx.fillStyle = '#020617'
    ctx.font = '900 27px -apple-system, sans-serif'
    ctx.textAlign = 'center'
    ctx.fillText(`🎟️ USA EL CÓDIGO: ${reelPromoCode.value.toUpperCase()} (DESCUENTO EXTRA)`, W / 2, cpY + 46)
    ctx.restore()
  }
}

function drawCtaButton(ctx, ctaX, ctaY, ctaW, ctaH, t) {
  const W = 1080
  ctx.save()
  const ctaPulse = isAnimated.value ? (1.0 + Math.sin(t * 3.6) * 0.02) : 1.0

  ctx.translate(W / 2, ctaY + ctaH / 2)
  ctx.scale(ctaPulse, ctaPulse)
  ctx.translate(-W / 2, -(ctaY + ctaH / 2))

  const ctaGrad = ctx.createLinearGradient(ctaX, ctaY, ctaX + ctaW, ctaY)
  ctaGrad.addColorStop(0, '#ec4899')
  ctaGrad.addColorStop(0.5, '#a855f7')
  ctaGrad.addColorStop(1, '#6366f1')
  ctx.fillStyle = ctaGrad
  ctx.shadowColor = 'rgba(236, 72, 153, 0.5)'
  ctx.shadowBlur = 32
  roundRect(ctx, ctaX, ctaY, ctaW, ctaH, 52)
  ctx.fill()

  ctx.fillStyle = '#ffffff'
  ctx.font = '900 38px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
  ctx.textAlign = 'center'
  ctx.shadowColor = 'rgba(0,0,0,0.5)'
  ctx.shadowBlur = 12
  ctx.fillText(reelCtaText.value || '📲 PEDILO AL WHATSAPP', W / 2, ctaY + 65)
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

// ========================================================
// DESCARGAS Y EXPORTACIÓN ADAPTADA PARA MÓVILES Y PC
// ========================================================

// 1. Grabación de Video Reel / TikTok 5s (30 FPS)
async function recordVideoReel() {
  if (isRecording.value) return
  const canvas = canvasRef.value
  if (!canvas) return

  const captureStreamFn = canvas.captureStream || canvas.mozCaptureStream
  if (!captureStreamFn || typeof MediaRecorder === 'undefined') {
    toast.add('Tu navegador móvil no soporta grabación directa de video. Descarga la imagen HD o comparte directo.', 'info')
    downloadImagePng()
    return
  }

  isAnimated.value = true
  isRecording.value = true
  recordingProgress.value = 0
  recordingSeconds.value = '0.0'

  try {
    const stream = captureStreamFn.call(canvas, 30)
    
    const candidates = [
      'video/webm;codecs=vp9',
      'video/webm;codecs=vp8',
      'video/webm',
      'video/mp4;codecs=h264',
      'video/mp4'
    ]
    let mimeType = 'video/webm'
    const supported = candidates.find(t => MediaRecorder.isTypeSupported(t))
    if (supported) mimeType = supported

    const recorder = new MediaRecorder(stream, {
      mimeType,
      videoBitsPerSecond: 6500000
    })

    const chunks = []
    recorder.ondataavailable = (e) => {
      if (e.data && e.data.size > 0) {
        chunks.push(e.data)
      }
    }

    const durationMs = 5000
    const startTime = performance.now()

    if (recordingInterval) clearInterval(recordingInterval)
    recordingInterval = setInterval(() => {
      const elapsed = performance.now() - startTime
      recordingProgress.value = Math.min(100, Math.round((elapsed / durationMs) * 100))
      recordingSeconds.value = (elapsed / 1000).toFixed(1)
    }, 100)

    recorder.onstop = async () => {
      if (recordingInterval) clearInterval(recordingInterval)
      isRecording.value = false
      const ext = mimeType.includes('mp4') ? 'mp4' : 'webm'
      const blob = new Blob(chunks, { type: mimeType })
      const cleanTitle = reelHeadline.value.toLowerCase().replace(/[^a-z0-9]/g, '_').slice(0, 20)
      const fileName = `reel_streamepro_${cleanTitle}_9x16.${ext}`

      // Si está en móvil y soporta compartir archivos nativos
      if (isMobileDevice.value && typeof navigator !== 'undefined' && navigator.share && navigator.canShare) {
        const file = new File([blob], fileName, { type: mimeType })
        if (navigator.canShare({ files: [file] })) {
          try {
            await navigator.share({
              files: [file],
              title: reelHeadline.value,
              text: currentReelPackage.value.caption
            })
            toast.add('🎬 Video compartido con éxito', 'success')
            return
          } catch (shareErr) {
            if (shareErr.name === 'AbortError') return
          }
        }
      }

      // Descarga directa habitual para PC o fallback móvil
      const url = URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = fileName
      document.body.appendChild(a)
      a.click()
      document.body.removeChild(a)
      setTimeout(() => URL.revokeObjectURL(url), 10000)

      toast.add(`🎬 ¡Video Reel 9:16 generado (${ext.toUpperCase()})! Listo para TikTok, Reels o Estados.`, 'success')
    }

    recorder.start(100)

    if (recordingTimeout) clearTimeout(recordingTimeout)
    recordingTimeout = setTimeout(() => {
      if (recorder.state !== 'inactive') {
        recorder.stop()
      }
    }, durationMs)

  } catch (err) {
    console.error('Error grabando reel en canvas:', err)
    isRecording.value = false
    if (recordingInterval) clearInterval(recordingInterval)
    toast.add('No se pudo grabar video en este navegador. Usa "Descargar PNG".', 'error')
  }
}

// 2. Descargar Imagen PNG 1080x1920 (PC y Móvil)
async function downloadImagePng() {
  const canvas = canvasRef.value
  if (!canvas || isExportingImage.value) return
  isExportingImage.value = true

  try {
    renderFrame(performance.now() - animationStartTime)
    const blob = await new Promise((res, rej) => {
      try {
        canvas.toBlob((b) => {
          if (!b) rej(new Error('Fallo al generar Blob de Canvas'))
          else res(b)
        }, 'image/png', 0.95)
      } catch (err) {
        rej(err)
      }
    })

    const cleanTitle = reelHeadline.value.toLowerCase().replace(/[^a-z0-9]/g, '_').slice(0, 20)
    const fileName = `estado_streamepro_${cleanTitle}_9x16.png`

    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = fileName
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    setTimeout(() => URL.revokeObjectURL(url), 10000)

    toast.add('📸 ¡Imagen HD 9:16 descargada! Lista para Estados e Historias.', 'success')
  } catch (err) {
    console.error('Error exportando PNG:', err)
    if (err.name === 'SecurityError' || String(err).includes('Tainted')) {
      toast.add('La carátula externa tiene protección CORS. Se ha restaurado la oficial para descargar.', 'error')
      resetToOriginalProductCover()
    } else {
      toast.add('Error al exportar imagen en este dispositivo.', 'error')
    }
  } finally {
    isExportingImage.value = false
  }
}

// 3. Acción Adaptada Móvil: Compartir o Abrir Visor Táctil
async function handleMobileOrShareAction() {
  const canvas = canvasRef.value
  if (!canvas) return

  renderFrame(performance.now() - animationStartTime)
  try {
    const dataUrl = canvas.toDataURL('image/png', 0.95)
    mobileSaveImageUrl.value = dataUrl
  } catch (err) {
    if (err.name === 'SecurityError' || String(err).includes('Tainted')) {
      resetToOriginalProductCover()
      return
    }
  }

  // Si el navegador soporta Web Share API con archivos, intentar compartir directamente
  if (typeof navigator !== 'undefined' && navigator.share && navigator.canShare) {
    try {
      const blob = await new Promise((res, rej) => {
        try {
          canvas.toBlob((b) => b ? res(b) : rej(new Error('Blob nulo')), 'image/png', 0.95)
        } catch (err) {
          rej(err)
        }
      })
      if (blob) {
        const file = new File([blob], 'streamepro_story_9x16.png', { type: 'image/png' })
        if (navigator.canShare({ files: [file] })) {
          await navigator.share({
            files: [file],
            title: reelHeadline.value,
            text: currentReelPackage.value.caption
          })
          toast.add('📲 Compartido con éxito', 'success')
          return
        }
      }
    } catch (err) {
      if (err.name === 'AbortError') return
    }
  }

  // Fallback / Experiencia móvil dedicada: Abrir Visor de Guardado Táctil
  showMobileSaveModal.value = true
}

async function shareDirect() {
  const canvas = canvasRef.value
  if (!canvas) return
  try {
    renderFrame(performance.now() - animationStartTime)
    const blob = await new Promise((res, rej) => {
      try {
        canvas.toBlob((b) => b ? res(b) : rej(new Error('Blob nulo')), 'image/png', 0.95)
      } catch (err) {
        rej(err)
      }
    })

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
    downloadImagePng()
  } catch (err) {
    if (err.name !== 'AbortError') {
      downloadImagePng()
    }
  }
}

// 4. Copiar Imagen al Portapapeles (Ctrl + V para PC)
async function copyImageToClipboard() {
  const canvas = canvasRef.value
  if (!canvas) return

  try {
    renderFrame(performance.now() - animationStartTime)
    const blob = await new Promise((res, rej) => {
      try {
        canvas.toBlob((b) => b ? res(b) : rej(new Error('Blob nulo')), 'image/png', 0.95)
      } catch (err) {
        rej(err)
      }
    })

    if (typeof ClipboardItem !== 'undefined' && navigator.clipboard && navigator.clipboard.write) {
      await navigator.clipboard.write([
        new ClipboardItem({ 'image/png': blob })
      ])
      toast.add('📋 ¡Imagen copiada! Pégala con Ctrl + V en WhatsApp Web o Telegram.', 'success')
    } else {
      downloadImagePng()
    }
  } catch (err) {
    console.warn('Fallo copia al portapapeles, usando descarga directa:', err)
    downloadImagePng()
  }
}

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

function toggleFullscreenPreview() {
  const canvas = canvasRef.value
  if (!canvas) return
  if (!document.fullscreenElement) {
    canvas.requestFullscreen?.().catch(() => {})
  } else {
    document.exitFullscreen?.().catch(() => {})
  }
}

function closeModal() {
  if (animFrameId) cancelAnimationFrame(animFrameId)
  if (recordingInterval) clearInterval(recordingInterval)
  if (recordingTimeout) clearTimeout(recordingTimeout)
  emit('close')
}

// Inicialización instantánea según props
function initFromProps() {
  if (props.promoCode) {
    reelPromoCode.value = normalizePromoCode(props.promoCode)
  }
  if (props.storeName) {
    reelStoreName.value = props.storeName
  }

  const p = props.initialPayload
  if (p) {
    if (p.itemType === 'gaming' || p.platform?.includes('PS') || p.effectivePrimaryPrice || p.category === 'Juegos Digitales') {
      selectedMode.value = 'gaming'
      pickedGameId.value = p.id
      // Inmediatamente usar datos del item sin esperar a que termine de cargar el catálogo completo
      reelHeadline.value = (p.title || 'Videojuego').toUpperCase().slice(0, 36)
      reelPromoPrice.value = Number(p.effectivePrimaryPrice || p.price || 15000)
      reelRegularPrice.value = Math.round(reelPromoPrice.value * 1.3)
      if (p.image) {
        reelArtworkUrl.value = p.image
        defaultProductCoverUrl.value = p.image
        loadArtwork(p.image)
      }
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
    if (recordingInterval) clearInterval(recordingInterval)
    if (recordingTimeout) clearTimeout(recordingTimeout)
  }
})

watch(() => props.promoCode, (newVal) => {
  if (newVal) reelPromoCode.value = normalizePromoCode(newVal)
})

onMounted(() => {
  if (!adminStore.isGamingCatalogLoaded) {
    adminStore.loadFullGamingCatalog().catch(() => {})
  }
  if (props.show) {
    initFromProps()
  }
})

onBeforeUnmount(() => {
  if (animFrameId) cancelAnimationFrame(animFrameId)
  if (recordingInterval) clearInterval(recordingInterval)
  if (recordingTimeout) clearTimeout(recordingTimeout)
})
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: rgba(15, 23, 42, 0.6);
  border-radius: 9999px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(99, 102, 241, 0.4);
  border-radius: 9999px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: rgba(99, 102, 241, 0.7);
}
</style>
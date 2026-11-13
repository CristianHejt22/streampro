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
          <!-- Botón de Optimización Automática Rápida -->
          <button
            type="button"
            @click="autoOptimizeReel"
            class="hidden sm:flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-gradient-to-r from-amber-500 to-pink-500 hover:from-amber-400 hover:to-pink-400 text-slate-950 font-black text-xs shadow-md transition cursor-pointer"
            title="Calibra automáticamente encuadre, tema, badges y proporciones perfectas"
          >
            <span>⚡</span>
            <span>Auto-Optimizar</span>
          </button>

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

            <!-- Banner Flotante Superior de Grabación (Lienzo 100% Despejado y Visible) -->
            <div 
              v-if="isRecording" 
              class="absolute top-2.5 inset-x-2.5 bg-slate-950/92 backdrop-blur-md border border-rose-500/60 p-2 sm:p-2.5 rounded-xl flex items-center justify-between shadow-2xl z-30 animate-in fade-in pointer-events-none"
            >
              <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-rose-500 animate-ping shrink-0"></span>
                <span class="text-[11px] font-black text-white uppercase tracking-wider">🔴 Grabando Reel 9:16</span>
              </div>
              <div class="flex items-center gap-2">
                <div class="w-20 sm:w-24 bg-slate-800 h-2 rounded-full overflow-hidden border border-white/10">
                  <div 
                    class="bg-gradient-to-r from-pink-500 via-rose-500 to-amber-500 h-full transition-all duration-100" 
                    :style="{ width: recordingProgress + '%' }"
                  ></div>
                </div>
                <span class="text-[10px] font-mono text-rose-300 font-bold">{{ recordingSeconds }}s / {{ selectedDurationSec }}s</span>
              </div>
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
              <span>Arrastra para encuadrar</span>
            </div>

            <!-- Botones flotantes inferiores derechos (Zonas seguras y Fullscreen) -->
            <div class="absolute bottom-2.5 right-2.5 flex items-center gap-1.5 pointer-events-auto">
              <button
                type="button"
                @click.stop="showSafeZoneOverlay = !showSafeZoneOverlay; requestCanvasRedraw()"
                :class="['text-xs p-1.5 rounded-xl border backdrop-blur-md transition shadow cursor-pointer', showSafeZoneOverlay ? 'bg-pink-600 border-pink-400 text-white' : 'bg-black/70 hover:bg-black/90 border-white/20 text-slate-300']"
                title="Mostrar/Ocultar Guías de Zonas Seguras de TikTok e Instagram"
              >
                🛡️
              </button>
              <button
                type="button"
                @click.stop="toggleFullscreenPreview"
                class="bg-black/70 hover:bg-black/90 text-white text-xs p-1.5 rounded-xl border border-white/20 backdrop-blur-md transition shadow cursor-pointer opacity-75 hover:opacity-100"
                title="Ver en Pantalla Completa"
              >
                🔍
              </button>
            </div>
          </div>

          <!-- ======================================================== -->
          <!-- BOTONERA DE ACCIÓN Y DESCARGAS ADAPTADAS (MÓVIL & PC)    -->
          <!-- ======================================================== -->
          <div class="w-full max-w-[325px] space-y-2">
            
            <!-- Selector de Duración del Reel Animado -->
            <div class="flex items-center justify-between bg-slate-900/90 border border-slate-800 rounded-xl p-1.5 gap-1.5 text-xs">
              <span class="text-[10px] uppercase font-bold text-slate-300 pl-1.5 flex items-center gap-1 shrink-0">
                <span>⏱️ Duración:</span>
              </span>
              <div class="grid grid-cols-4 gap-1 w-full">
                <button
                  v-for="d in [5, 8, 10, 15]"
                  :key="d"
                  type="button"
                  @click="selectedDurationSec = d"
                  :class="[
                    'py-1 rounded-lg text-xs font-mono font-bold transition cursor-pointer text-center',
                    selectedDurationSec === d
                      ? 'bg-pink-600 text-white shadow-md shadow-pink-900/50'
                      : 'bg-slate-950 text-slate-400 hover:text-white border border-slate-800'
                  ]"
                >
                  {{ d }}s
                </button>
              </div>
            </div>

            <!-- Botón Principal: Grabar Video Reel (TikTok / Reels / Estados) -->
            <button
              type="button"
              @click="recordVideoReel"
              :disabled="isRecording || isExportingImage"
              class="w-full py-3 px-4 rounded-xl font-black text-xs transition-all shadow-lg flex items-center justify-center gap-2 cursor-pointer bg-gradient-to-r from-pink-500 via-purple-600 to-indigo-600 hover:from-pink-400 hover:to-indigo-500 text-white shadow-pink-500/25 active:scale-98 disabled:opacity-50"
            >
              <span>🎬</span>
              <span>{{ isRecording ? `Grabando Video (${recordingSeconds}s)...` : `Grabar Video Reel (${selectedDurationSec}s @ 30 FPS)` }}</span>
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
              <span>🖼️ Cambiar &amp; Encuadre</span>
            </button>
            <button
              type="button"
              @click="studioTab = 'design'"
              :class="['px-3 py-2 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-1.5', studioTab === 'design' ? 'bg-indigo-600 text-white shadow' : 'bg-slate-950 text-slate-300 border border-slate-800 hover:bg-slate-800']"
            >
              <span>🎨 Plantillas, FX &amp; Herramientas</span>
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
              <div class="flex items-center justify-between">
                <label class="text-[11px] uppercase font-bold text-slate-400 block tracking-wider">
                  1. ¿Qué producto vas a promocionar?
                </label>
                <button
                  type="button"
                  @click="autoOptimizeReel"
                  class="text-[10px] font-bold text-amber-300 hover:text-white flex items-center gap-1 cursor-pointer"
                >
                  <span>⚡</span>
                  <span>Auto-Optimizar Estilo</span>
                </button>
              </div>

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
                    <img v-if="g.image" :src="getCORSImageUrl(g.image)" :alt="g.title" class="w-full h-full object-cover" loading="lazy">
                    <span v-else class="text-xs">🎮</span>
                  </div>

                  <!-- Info resumida -->
                  <div class="flex-1 min-w-0">
                    <span class="text-[11px] font-bold block truncate group-hover:text-indigo-300 transition-colors">
                      {{ cleanGameTitle(g.title) }}
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

          <!-- ======================================================== -->
          <!-- TAB 3: PLANTILLAS, HERRAMIENTAS AJUSTADAS & EFECTOS      -->
          <!-- ======================================================== -->
          <div v-else-if="studioTab === 'design'" class="space-y-4">
            
            <!-- 1. SELECTOR DE PLANTILLA ESTRUCTURAL 9:16 -->
            <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 space-y-3">
              <label class="text-[11px] uppercase font-bold text-slate-400 block tracking-wider">
                1. Plantilla Estructural 9:16 (Cambia radicalmente la arquitectura)
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

            <!-- 2. HERRAMIENTAS ESPECÍFICAS AJUSTADAS A CADA DISEÑO -->
            <div class="bg-slate-950 p-4 rounded-2xl border border-indigo-500/30 space-y-3 animate-in fade-in">
              <div class="flex items-center justify-between">
                <label class="text-[11px] uppercase font-bold text-indigo-300 block tracking-wider flex items-center gap-1.5">
                  <span>🎛️ Herramientas Específicas de {{ currentLayoutName }}</span>
                </label>
                <span class="text-[10px] text-slate-400">Personalización exclusiva</span>
              </div>

              <!-- HERRAMIENTAS: FLYER OFICIAL ANIMADO -->
              <div v-if="currentLayoutId === 'flyer_official'" class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 text-xs">
                <div class="p-2.5 bg-slate-900/80 rounded-xl border border-indigo-500/30 flex items-center justify-between gap-1">
                  <span class="text-indigo-300 font-bold text-[11px]">Plantilla Oficial:</span>
                  <span class="text-[10px] bg-indigo-500/20 text-indigo-200 px-2 py-0.5 rounded-full font-mono font-bold">
                    {{ selectedMode === 'gaming' ? 'Gaming Story 9:16' : 'Cyber Story 9:16' }}
                  </span>
                </div>
                <label class="flex items-center justify-between p-2.5 bg-slate-900/70 rounded-xl border border-slate-800 cursor-pointer">
                  <span class="text-slate-300">Malla Holográfica</span>
                  <input type="checkbox" v-model="showCyberGrid" @change="requestCanvasRedraw" class="accent-indigo-500">
                </label>
                <label class="flex items-center justify-between p-2.5 bg-slate-900/70 rounded-xl border border-slate-800 cursor-pointer">
                  <span class="text-slate-300">Animación a 30 FPS</span>
                  <input type="checkbox" v-model="isAnimated" @change="requestCanvasRedraw" class="accent-indigo-500">
                </label>
              </div>

              <!-- HERRAMIENTAS: CYBER PRO -->
              <div v-else-if="currentLayoutId === 'cyber_pro'" class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 text-xs">
                <label class="flex items-center justify-between p-2.5 bg-slate-900/70 rounded-xl border border-slate-800 cursor-pointer">
                  <span class="text-slate-300">Malla Holográfica</span>
                  <input type="checkbox" v-model="showCyberGrid" @change="requestCanvasRedraw" class="accent-indigo-500">
                </label>
                <label class="flex items-center justify-between p-2.5 bg-slate-900/70 rounded-xl border border-slate-800 cursor-pointer">
                  <span class="text-slate-300">Esquinas HUD Metálicas</span>
                  <input type="checkbox" v-model="showHudBrackets" @change="requestCanvasRedraw" class="accent-indigo-500">
                </label>
                <div class="p-2.5 bg-slate-900/70 rounded-xl border border-slate-800 flex items-center justify-between gap-1">
                  <span class="text-slate-300 text-[11px]">Escáner Láser:</span>
                  <select v-model="cyberScanlineSpeed" @change="requestCanvasRedraw" class="bg-slate-950 text-indigo-300 text-[11px] rounded-lg p-1 outline-none font-bold">
                    <option value="normal">Normal</option>
                    <option value="fast">Rápido</option>
                    <option value="off">Apagado</option>
                  </select>
                </div>
              </div>

              <!-- HERRAMIENTAS: PÓSTER CINEMÁTICO -->
              <div v-else-if="currentLayoutId === 'cinematic'" class="space-y-2.5 text-xs">
                <div>
                  <label class="text-[10px] text-slate-400 font-bold block mb-1">Texto de Marquesina de Cine:</label>
                  <input 
                    v-model="cinematicStudioText" 
                    @input="requestCanvasRedraw"
                    type="text" 
                    class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2 text-xs text-white font-bold outline-none"
                  >
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                  <div class="bg-slate-900/70 p-2.5 rounded-xl border border-slate-800">
                    <div class="flex justify-between text-[10px] font-bold text-slate-300 mb-1">
                      <span>Viñeta de Cine</span>
                      <span class="text-indigo-400">{{ cinematicVignetteDepth }}%</span>
                    </div>
                    <input v-model.number="cinematicVignetteDepth" @input="requestCanvasRedraw" type="range" min="60" max="95" step="1" class="w-full accent-indigo-400 cursor-pointer">
                  </div>
                  <div class="bg-slate-900/70 p-2.5 rounded-xl border border-slate-800">
                    <div class="flex justify-between text-[10px] font-bold text-slate-300 mb-1">
                      <span>Opacidad Placa Cristal</span>
                      <span class="text-indigo-400">{{ cinematicGlassOpacity }}%</span>
                    </div>
                    <input v-model.number="cinematicGlassOpacity" @input="requestCanvasRedraw" type="range" min="50" max="95" step="1" class="w-full accent-indigo-400 cursor-pointer">
                  </div>
                </div>
              </div>

              <!-- HERRAMIENTAS: GLASSMORPHISM VIP -->
              <div v-else-if="currentLayoutId === 'glass_vip'" class="space-y-2.5 text-xs">
                <div>
                  <label class="text-[10px] text-slate-400 font-bold block mb-1">Texto de la Cinta Dorada VIP:</label>
                  <input 
                    v-model="vipRibbonText" 
                    @input="requestCanvasRedraw"
                    type="text" 
                    class="w-full bg-slate-900 border border-amber-500/40 rounded-xl p-2 text-xs text-amber-300 font-bold outline-none"
                  >
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                  <div class="bg-slate-900/70 p-2.5 rounded-xl border border-slate-800">
                    <div class="flex justify-between text-[10px] font-bold text-slate-300 mb-1">
                      <span>Profundidad Frosted Glass</span>
                      <span class="text-amber-400">{{ glassBlurFactor }}px</span>
                    </div>
                    <input v-model.number="glassBlurFactor" @input="requestCanvasRedraw" type="range" min="10" max="45" step="1" class="w-full accent-amber-400 cursor-pointer">
                  </div>
                  <div class="p-2.5 bg-slate-900/70 rounded-xl border border-slate-800 flex items-center justify-between gap-1">
                    <span class="text-slate-300 text-[11px]">Estrellas de Lujo:</span>
                    <select v-model="vipStarsDensity" @change="requestCanvasRedraw" class="bg-slate-950 text-amber-300 text-[11px] rounded-lg p-1 outline-none font-bold">
                      <option value="baja">Sutiles</option>
                      <option value="media">Medias</option>
                      <option value="alta">Lluvia VIP</option>
                    </select>
                  </div>
                </div>
              </div>

              <!-- HERRAMIENTAS: SPLIT FLASH -->
              <div v-else-if="currentLayoutId === 'split_flash'" class="space-y-2.5 text-xs">
                <div>
                  <label class="text-[10px] text-slate-400 font-bold block mb-1">Texto de la Franja Eléctrica:</label>
                  <input 
                    v-model="flashBannerText" 
                    @input="requestCanvasRedraw"
                    type="text" 
                    class="w-full bg-slate-900 border border-rose-500/40 rounded-xl p-2 text-xs text-rose-300 font-bold outline-none"
                  >
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                  <div class="p-2.5 bg-slate-900/70 rounded-xl border border-slate-800 flex items-center justify-between gap-1">
                    <span class="text-slate-300 text-[11px]">Cuenta Regresiva:</span>
                    <select v-model.number="countdownHours" @change="requestCanvasRedraw" class="bg-slate-950 text-rose-400 text-[11px] rounded-lg p-1 outline-none font-bold">
                      <option :value="2">2 Horas</option>
                      <option :value="6">6 Horas</option>
                      <option :value="12">12 Horas</option>
                      <option :value="24">24 Horas</option>
                    </select>
                  </div>
                  <label class="flex items-center justify-between p-2.5 bg-slate-900/70 rounded-xl border border-slate-800 cursor-pointer">
                    <span class="text-slate-300">Pulso Eléctrico</span>
                    <input type="checkbox" v-model="electricPulse" @change="requestCanvasRedraw" class="accent-rose-500">
                  </label>
                </div>
              </div>
            </div>

            <!-- 3. EFECTOS MULTIMEDIA ADAPTABLES (ONDAS DE AUDIO & PARTÍCULAS) -->
            <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 space-y-3">
              <label class="text-[11px] uppercase font-bold text-slate-400 block tracking-wider">
                3. Efectos Dinámicos en Tendencia (Sin Romper el Diseño)
              </label>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <!-- Estilo de Ondas de Audio -->
                <div class="bg-slate-900/60 p-2.5 rounded-xl border border-slate-800">
                  <label class="text-[10px] font-bold text-slate-300 block mb-1">🎵 Efecto de Audio (Trending Sound):</label>
                  <select 
                    v-model="audioWaveStyle" 
                    @change="requestCanvasRedraw"
                    class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2 text-xs text-white font-semibold outline-none"
                  >
                    <option value="bars">📊 Ecualizador Neón (9 Barras)</option>
                    <option value="wave">〰️ Onda Sonora Analógica</option>
                    <option value="pulse">⭕ Anillo Pulsante</option>
                    <option value="none">🚫 Desactivado</option>
                  </select>
                </div>

                <!-- Estilo de Partículas Neón -->
                <div class="bg-slate-900/60 p-2.5 rounded-xl border border-slate-800">
                  <label class="text-[10px] font-bold text-slate-300 block mb-1">✨ Partículas de Ambiente:</label>
                  <select 
                    v-model="particleStyle" 
                    @change="requestCanvasRedraw"
                    class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2 text-xs text-white font-semibold outline-none"
                  >
                    <option value="neon_dots">💠 Partículas Cyber Neón</option>
                    <option value="gold_dust">⭐ Polvo de Estrellas Dorado</option>
                    <option value="fire_sparks">🔥 Chispas de Fuego Relámpago</option>
                    <option value="none">🚫 Sin Partículas (Limpio)</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- 4. PALETA DE COLOR NEÓN -->
            <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 space-y-3">
              <label class="text-[11px] uppercase font-bold text-slate-400 block tracking-wider">
                4. Paleta de Color &amp; Efectos Neón
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

            <!-- 5. SELLO HOLOGRÁFICO DE CONFIANZA -->
            <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 space-y-2.5">
              <label class="text-[11px] uppercase font-bold text-slate-400 block tracking-wider">
                5. Sello Holográfico / Insignia de Confianza
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

            <!-- 6. ELEMENTOS VISIBLES -->
            <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 space-y-2.5">
              <label class="text-[11px] uppercase font-bold text-slate-400 block tracking-wider">
                6. Elementos Visibles en la Pieza
              </label>
              <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 text-xs">
                <label class="flex items-center gap-2 p-2 bg-slate-900/60 rounded-xl border border-slate-800 cursor-pointer">
                  <input type="checkbox" v-model="showRegularPrice" @change="requestCanvasRedraw" class="accent-pink-500">
                  <span class="text-slate-300">Precio Regular</span>
                </label>
                <label class="flex items-center gap-2 p-2 bg-slate-900/60 rounded-xl border border-slate-800 cursor-pointer">
                  <input type="checkbox" v-model="showPromoPill" @change="requestCanvasRedraw" class="accent-pink-500">
                  <span class="text-slate-300">Cupón Promo</span>
                </label>
                <label class="flex items-center gap-2 p-2 bg-slate-900/60 rounded-xl border border-emerald-500/30 cursor-pointer bg-emerald-950/20">
                  <input type="checkbox" v-model="showContactPhone" @change="requestCanvasRedraw" class="accent-emerald-500">
                  <span class="text-emerald-300 font-bold">Barra WhatsApp</span>
                </label>
                <label class="flex items-center gap-2 p-2 bg-slate-900/60 rounded-xl border border-slate-800 cursor-pointer">
                  <input type="checkbox" v-model="showReelBullets" @change="requestCanvasRedraw" class="accent-pink-500">
                  <span class="text-slate-300">Beneficios Flyer</span>
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

            <!-- CONFIGURACIÓN DE WHATSAPP PROFESIONAL -->
            <div class="bg-slate-950 p-4 rounded-2xl border border-emerald-500/30 space-y-3">
              <div class="flex items-center justify-between">
                <label class="text-[11px] uppercase font-bold text-emerald-400 block tracking-wider flex items-center gap-1.5">
                  <span>📲 WhatsApp Oficial de Pedidos (Destacado en Reel)</span>
                </label>
                <span class="text-[9px] bg-emerald-500/10 text-emerald-300 border border-emerald-500/20 px-2 py-0.5 rounded-full font-bold">Animado 30 FPS</span>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Número de WhatsApp</label>
                  <input 
                    v-model="customContactPhone" 
                    @input="requestCanvasRedraw"
                    type="text" 
                    placeholder="Ej: +54 9 11 4050-6070"
                    class="w-full bg-slate-900 border border-emerald-500/40 rounded-xl p-2.5 text-xs text-emerald-300 font-mono font-bold outline-none focus:border-emerald-400"
                  >
                </div>

                <div>
                  <label class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Llamado de WhatsApp (Micro-etiqueta)</label>
                  <input 
                    v-model="whatsappActionText" 
                    @input="requestCanvasRedraw"
                    type="text" 
                    placeholder="PEDIDOS & CONSULTAS WHATSAPP"
                    class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-xs text-white font-bold outline-none"
                  >
                </div>
              </div>

              <div>
                <label class="text-[10px] uppercase font-bold text-slate-400 block mb-1.5">Estilo Visual de la Barra WhatsApp</label>
                <div class="grid grid-cols-3 gap-2">
                  <button 
                    type="button" 
                    @click="whatsappStyle = 'official_emerald'; requestCanvasRedraw()"
                    :class="['p-2 rounded-xl text-[11px] font-bold border transition flex items-center justify-center gap-1.5 cursor-pointer', whatsappStyle === 'official_emerald' ? 'bg-emerald-600 text-white border-emerald-400 shadow-lg shadow-emerald-950' : 'bg-slate-900 text-slate-300 border-slate-800 hover:bg-slate-800']"
                  >
                    <span>🟢</span> <span>Verde Oficial</span>
                  </button>
                  <button 
                    type="button" 
                    @click="whatsappStyle = 'cyber_neon'; requestCanvasRedraw()"
                    :class="['p-2 rounded-xl text-[11px] font-bold border transition flex items-center justify-center gap-1.5 cursor-pointer', whatsappStyle === 'cyber_neon' ? 'bg-cyan-600 text-white border-cyan-400 shadow-lg shadow-cyan-950' : 'bg-slate-900 text-slate-300 border-slate-800 hover:bg-slate-800']"
                  >
                    <span>💎</span> <span>Neón Cyber</span>
                  </button>
                  <button 
                    type="button" 
                    @click="whatsappStyle = 'glass_luxury'; requestCanvasRedraw()"
                    :class="['p-2 rounded-xl text-[11px] font-bold border transition flex items-center justify-center gap-1.5 cursor-pointer', whatsappStyle === 'glass_luxury' ? 'bg-amber-600 text-white border-amber-400 shadow-lg shadow-amber-950' : 'bg-slate-900 text-slate-300 border-slate-800 hover:bg-slate-800']"
                  >
                    <span>👑</span> <span>VIP Obsidiana</span>
                  </button>
                </div>
              </div>
            </div>

            <!-- BENEFICIOS OFICIALES ESTILO FLYER -->
            <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 space-y-3">
              <div class="flex items-center justify-between">
                <label class="text-[11px] uppercase font-bold text-slate-400 block tracking-wider">
                  ✓ Beneficios y Garantías Oficiales (Estilo Flyer)
                </label>
                <span class="text-[9px] text-slate-500">Insignias ✓ en el Reel</span>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                <div>
                  <label class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Beneficio 1 (Velocidad)</label>
                  <input 
                    v-model="reelBullet1" 
                    @input="requestCanvasRedraw"
                    type="text" 
                    placeholder="⚡ Entrega Inmediata en 15 Minutos"
                    class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-xs text-white outline-none"
                  >
                </div>

                <div>
                  <label class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Beneficio 2 (Licencia / Uso)</label>
                  <input 
                    v-model="reelBullet2" 
                    @input="requestCanvasRedraw"
                    type="text" 
                    placeholder="👑 Cuenta Oficial 100% Garantizada"
                    class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-xs text-white outline-none"
                  >
                </div>

                <div>
                  <label class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Beneficio 3 (Garantía / Soporte)</label>
                  <input 
                    v-model="reelBullet3" 
                    @input="requestCanvasRedraw"
                    type="text" 
                    placeholder="🛡️ Garantía Oficial 30 Días"
                    class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-xs text-white outline-none"
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
            <span>📱</span> {{ mobileSaveMediaType === 'video' ? 'Guardar Video Reel' : 'Guardar Flyer en Móvil' }}
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
          <template v-if="mobileSaveMediaType === 'video'">
            Mantén presionado el video o pulsa <strong class="text-white">"Descargar Video"</strong> para guardarlo en la galería de tu celular o compartirlo en TikTok, Reels o WhatsApp.
          </template>
          <template v-else>
            Mantén pulsada la imagen para <strong class="text-white">"Guardar en Fotos"</strong> o compartir a tus Estados.
          </template>
        </p>

        <!-- Preview táctil del medio generado (Video o Imagen) -->
        <div class="w-full max-w-[240px] aspect-[9/16] rounded-2xl overflow-hidden shadow-2xl border border-white/20 bg-black flex items-center justify-center">
          <video 
            v-if="mobileSaveMediaType === 'video'"
            :src="mobileSaveVideoUrl" 
            autoplay 
            loop 
            playsinline 
            controls 
            class="w-full h-full object-contain select-auto"
          ></video>
          <img 
            v-else
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
            v-if="mobileSaveMediaType === 'video'"
            type="button"
            @click="downloadVideoFromModal"
            class="py-2.5 px-3 rounded-xl bg-pink-600 hover:bg-pink-500 text-white font-bold text-xs shadow flex items-center justify-center gap-1.5 cursor-pointer"
          >
            <span>🎬</span>
            <span>Descargar Video</span>
          </button>
          <button
            v-else
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
const currentLayoutId = ref('flyer_official') // 'flyer_official' | 'cyber_pro' | 'cinematic' | 'glass_vip' | 'split_flash'

// Estado de grabación y animación
const isAnimated = ref(true)
const isRecording = ref(false)
const isExportingImage = ref(false)
const isDrawingForExport = ref(false)
const recordingProgress = ref(0)
const recordingSeconds = ref('0.0')
const selectedDurationSec = ref(8) // 5 | 8 | 10 | 15 segundos

// Modal de guardado para móviles
const showMobileSaveModal = ref(false)
const mobileSaveImageUrl = ref('')
const mobileSaveVideoUrl = ref('')
const mobileSaveMediaType = ref('image') // 'image' | 'video'
const mobileSaveFileName = ref('reel_streamepro_9x16.webm')
const mobileSaveVideoBlob = ref(null)

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

// ==========================================
// HERRAMIENTAS ESPECÍFICAS DE CADA DISEÑO
// ==========================================
// Cyber Pro
const showCyberGrid = ref(true)
const showHudBrackets = ref(true)
const cyberScanlineSpeed = ref('normal') // 'normal' | 'fast' | 'off'

// Póster Cinemático
const cinematicVignetteDepth = ref(85) // 60 a 95
const cinematicStudioText = ref('★ ESTRENO EXCLUSIVO · STREAMEPRO STUDIOS ★')
const cinematicGlassOpacity = ref(88) // 50 a 95

// Glassmorphism VIP
const glassBlurFactor = ref(25) // 10 a 45
const vipRibbonText = ref('👑 EDICIÓN VIP OFICIAL')
const vipStarsDensity = ref('media') // 'baja' | 'media' | 'alta'

// Split Flash
const flashBannerText = ref('⚡ OFERTA FLASH RELÁMPAGO ⚡')
const countdownHours = ref(6) // 2, 6, 12, 24
const electricPulse = ref(true)

// Efectos Multimedia Globales
const audioWaveStyle = ref('bars') // 'bars' | 'wave' | 'pulse' | 'none'
const particleStyle = ref('neon_dots') // 'neon_dots' | 'gold_dust' | 'fire_sparks' | 'none'
const showSafeZoneOverlay = ref(false)

// Interruptores de visibilidad
const showRegularPrice = ref(true)
const showPromoPill = ref(true)
const showContactPhone = ref(true)
const showReelBullets = ref(true)
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

// Beneficios Oficiales Estilo Flyer
const reelBullet1 = ref('⚡ Entrega Inmediata en 15 Minutos')
const reelBullet2 = ref('👑 Cuenta Oficial 100% Garantizada')
const reelBullet3 = ref('🛡️ Soporte VIP & Garantía 30 Días')

// WhatsApp de Pedidos Personalizado y Animado
const customContactPhone = ref('')
const whatsappActionText = ref('PEDIDOS & CONSULTAS WHATSAPP')
const whatsappStyle = ref('official_emerald') // 'official_emerald' | 'cyber_neon' | 'glass_luxury'

const effectiveContactPhone = computed(() => {
  const manual = String(customContactPhone.value || '').trim()
  if (manual) return manual
  if (props.contactPhone && props.contactPhone.trim()) return props.contactPhone.trim()
  return adminStore.providerBankInfo?.whatsapp || '+54 9 11 4050-6070'
})
const reelArtworkUrl = ref('/flyers/gaming-template-story.jpg')
const loadedArtworkImage = ref(null)
const defaultProductCoverUrl = ref('/flyers/gaming-template-story.jpg')
const customUrlInput = ref('')

// Buscador de Gaming
const gamingSearchTerm = ref('')
const gamingQuickCategory = ref('all')

// Helper para garantizar carga de imagen con CORS sin taint en el Canvas
function getCORSImageUrl(rawUrl) {
  if (!rawUrl) return ''
  if (rawUrl.startsWith('data:') || rawUrl.startsWith('blob:')) return rawUrl
  if (rawUrl.includes('wsrv.nl/?url=')) return rawUrl
  let fullUrl = rawUrl
  if (!fullUrl.startsWith('http')) {
    if (fullUrl.startsWith('/')) return fullUrl
    fullUrl = `https://streamepro.me/${fullUrl.replace(/^\/+/, '')}`
  }
  // Enrutamiento mediante proxy global seguro con CORS habilitado
  return `https://wsrv.nl/?url=${encodeURIComponent(fullUrl)}&w=1080&output=jpg`
}

// Limpiador estético de títulos de videojuegos para flyers y reels 9:16
function cleanGameTitle(rawTitle) {
  if (!rawTitle) return ''
  let t = rawTitle
    .replace(/\b(ESPAÑOL LATINO|AUDIO LATINO|SUBTITULADO|ESPAÑOL|LATINO)\b/gi, '')
    .replace(/\b(PS4 Y PS5|PS4 & PS5|PS4 \/ PS5|PS4|PS5)\b/gi, '')
    .replace(/\b(DIGITAL VERSION|DIGITAL EDITION|DIGITAL|DIGITALES)\b/gi, '')
    .replace(/\b(CUENTA PRIMARIA|CUENTA SECUNDARIA)\b/gi, '')
    .replace(/[\[\(][^\]\)]*[\]\)]/g, '')
    .replace(/\s*-\s*$/, '')
    .replace(/\s+/g, ' ')
    .trim()
  return t || rawTitle.trim()
}

// Sintetizador de carátula procedural en memoria (100% libre de CORS, nunca falla ni queda negro)
function createProceduralCover(title = 'StreamePro', subtitle = 'Edición Oficial', icon = '🎬', bg1 = '#0f172a', bg2 = '#020617', accent = '#38bdf8') {
  if (typeof document === 'undefined') return ''
  try {
    const c = document.createElement('canvas')
    c.width = 1000
    c.height = 1000
    const ctx = c.getContext('2d')
    if (!ctx) return ''

    // Fondo radial profundo
    const grad = ctx.createRadialGradient(500, 480, 50, 500, 480, 650)
    grad.addColorStop(0, bg1)
    grad.addColorStop(1, bg2)
    ctx.fillStyle = grad
    ctx.fillRect(0, 0, 1000, 1000)

    // Cuadrícula cyberpunk sutil
    ctx.strokeStyle = 'rgba(255, 255, 255, 0.04)'
    ctx.lineWidth = 2
    for (let x = 0; x < 1000; x += 50) {
      ctx.beginPath(); ctx.moveTo(x, 0); ctx.lineTo(x, 1000); ctx.stroke()
    }
    for (let y = 0; y < 1000; y += 50) {
      ctx.beginPath(); ctx.moveTo(0, y); ctx.lineTo(1000, y); ctx.stroke()
    }

    // Marco central circular con resplandor neón
    ctx.save()
    ctx.shadowColor = accent
    ctx.shadowBlur = 45
    ctx.fillStyle = 'rgba(15, 23, 42, 0.92)'
    ctx.beginPath()
    ctx.arc(500, 410, 210, 0, Math.PI * 2)
    ctx.fill()
    ctx.strokeStyle = accent
    ctx.lineWidth = 5
    ctx.stroke()
    ctx.restore()

    // Icono / Símbolo gigante en el centro
    ctx.font = '160px "Apple Color Emoji", "Segoe UI Emoji", sans-serif'
    ctx.textAlign = 'center'
    ctx.textBaseline = 'middle'
    ctx.fillText(icon, 500, 410)

    // Título Principal
    ctx.fillStyle = '#ffffff'
    ctx.font = '900 46px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
    ctx.fillText(title.toUpperCase().slice(0, 24), 500, 700)

    // Subtítulo
    ctx.fillStyle = accent
    ctx.font = 'bold 28px -apple-system, sans-serif'
    ctx.fillText(subtitle.slice(0, 36), 500, 760)

    // Insignia oficial inferior
    ctx.fillStyle = 'rgba(148, 163, 184, 0.8)'
    ctx.font = 'bold 22px monospace'
    ctx.fillText('⚡ ACTIVACIÓN OFICIAL · STREAMEPRO', 500, 850)

    return c.toDataURL('image/png')
  } catch (e) {
    console.warn('Error sintetizando carátula:', e)
    return ''
  }
}

// Carátula procedural temática por plataforma de streaming
function getStreamingPlatformCover(srvKey) {
  const p = (srvKey || '').toLowerCase()
  if (p.includes('netflix')) {
    return createProceduralCover('NETFLIX 4K ULTRA', 'Perfil Privado con PIN', '🍿', '#3b0764', '#020617', '#ec4899')
  }
  if (p.includes('disney')) {
    return createProceduralCover('DISNEY+ OFICIAL', 'Disney · Pixar · Marvel', '✨', '#0369a1', '#020617', '#38bdf8')
  }
  if (p.includes('spotify') || p.includes('music')) {
    return createProceduralCover('SPOTIFY PREMIUM', 'Música en Alta Fidelidad', '🎵', '#065f46', '#020617', '#22c55e')
  }
  if (p.includes('max') || p.includes('hbo')) {
    return createProceduralCover('MAX ULTRA HD', 'Warner Bros & HBO Original', '🎬', '#312e81', '#020617', '#818cf8')
  }
  if (p.includes('prime') || p.includes('amazon')) {
    return createProceduralCover('PRIME VIDEO', 'Series y Películas 4K', '📦', '#0e7490', '#020617', '#22d3ee')
  }
  if (p.includes('youtube')) {
    return createProceduralCover('YOUTUBE PREMIUM', 'Sin Anuncios + Music', '▶️', '#991b1b', '#020617', '#f87171')
  }
  if (p.includes('paramount')) {
    return createProceduralCover('PARAMOUNT+', 'Películas & Deportes', '⛰️', '#1e3a8a', '#020617', '#60a5fa')
  }
  if (p.includes('crunchy')) {
    return createProceduralCover('CRUNCHYROLL', 'Anime en Simulcast', '🍥', '#7c2d12', '#020617', '#fb923c')
  }
  if (p.includes('iptv') || p.includes('magis') || p.includes('tele')) {
    return createProceduralCover('IPTV PRO 4K', '+1.500 Canales en Vivo', '📺', '#4c1d95', '#020617', '#c084fc')
  }
  return createProceduralCover((srvKey || 'STREAMING').toUpperCase(), 'Suscripción Oficial', '⚡', '#1e293b', '#020617', '#38bdf8')
}

function readEbmlVint(bytes, pos) {
  if (pos >= bytes.length) return null
  const b = bytes[pos]
  let width = 0
  let mask = 0x80
  while (mask > 0) {
    width++
    if (b & mask) break
    mask >>= 1
  }
  if (width > 8) return null
  let val = b & (mask - 1)
  for (let i = 1; i < width; i++) {
    val = (val * 256) + bytes[pos + i]
  }
  return { width, value: val }
}

function writeEbmlVint(val, width) {
  const res = new Uint8Array(width)
  const mask = 0x80 >> (width - 1)
  let cur = val
  for (let i = width - 1; i > 0; i--) {
    res[i] = cur & 0xFF
    cur = Math.floor(cur / 256)
  }
  res[0] = (cur & (mask - 1)) | mask
  return res
}

// Inyector de duración matemática exacta para WebM
// Elimina de raíz el error de 00.000001ms y asegura compatibilidad 100% con reproductores móviles y PC
async function fixWebmDurationBlob(blob, durationMs = 8000) {
  try {
    const buffer = await blob.arrayBuffer()
    const bytes = new Uint8Array(buffer)

    // Segment Info ID: 0x15, 0x49, 0xA9, 0x66
    let infoPos = -1
    for (let i = 0; i < Math.min(bytes.length - 4, 4096); i++) {
      if (bytes[i] === 0x15 && bytes[i + 1] === 0x49 && bytes[i + 2] === 0xA9 && bytes[i + 3] === 0x66) {
        infoPos = i
        break
      }
    }

    if (infoPos === -1) return blob

    const infoVint = readEbmlVint(bytes, infoPos + 4)
    if (!infoVint) return blob

    const infoContentStart = infoPos + 4 + infoVint.width
    const infoContentEnd = infoContentStart + infoVint.value

    // Obtener TimecodeScale (0x2A, 0xD7, 0xB1) para calcular ticks exactos de duración
    let timecodeScaleNs = 1000000
    for (let i = infoContentStart; i < Math.min(bytes.length - 3, infoContentEnd); i++) {
      if (bytes[i] === 0x2A && bytes[i + 1] === 0xD7 && bytes[i + 2] === 0xB1) {
        const tcVint = readEbmlVint(bytes, i + 3)
        if (tcVint) {
          let tcVal = 0
          for (let j = 0; j < tcVint.value; j++) {
            tcVal = (tcVal * 256) + bytes[i + 3 + tcVint.width + j]
          }
          if (tcVal > 0) timecodeScaleNs = tcVal
        }
        break
      }
    }

    const durationTicks = (durationMs * 1000000) / timecodeScaleNs

    // Buscar si ya existe la etiqueta Duration (0x44, 0x89)
    for (let i = infoContentStart; i < Math.min(bytes.length - 2, infoContentEnd); i++) {
      if (bytes[i] === 0x44 && bytes[i + 1] === 0x89) {
        const durVint = readEbmlVint(bytes, i + 2)
        if (durVint) {
          const valPos = i + 2 + durVint.width
          const view = new DataView(buffer)
          if (durVint.value === 4) {
            view.setFloat32(valPos, durationTicks, false)
            return new Blob([buffer], { type: blob.type })
          } else if (durVint.value === 8) {
            view.setFloat64(valPos, durationTicks, false)
            return new Blob([buffer], { type: blob.type })
          }
        }
      }
    }

    // Si no existía, insertamos la etiqueta Duration (7 bytes) limpia al inicio del contenido de Info
    const durElement = new Uint8Array(7)
    durElement[0] = 0x44
    durElement[1] = 0x89
    durElement[2] = 0x84 // float32 de 4 bytes
    const dView = new DataView(durElement.buffer)
    dView.setFloat32(3, durationTicks, false)

    const newInfoSize = infoVint.value + 7
    const newInfoVint = writeEbmlVint(newInfoSize, infoVint.width)

    const patched = new Uint8Array(bytes.length + 7)
    patched.set(bytes.subarray(0, infoPos + 4), 0)
    patched.set(newInfoVint, infoPos + 4)
    patched.set(durElement, infoContentStart)
    patched.set(bytes.subarray(infoContentStart), infoContentStart + 7)

    return new Blob([patched.buffer], { type: blob.type })
  } catch (err) {
    console.warn('Could not patch WebM duration:', err)
    return blob
  }
}

// Cache en memoria para imágenes precargadas
const imageCache = new Map()

function loadImage(src) {
  if (!src) return Promise.resolve(null)
  if (imageCache.has(src)) return Promise.resolve(imageCache.get(src))
  return new Promise((resolve) => {
    const isDataOrBlob = src.startsWith('data:') || src.startsWith('blob:')
    const img = new Image()
    if (!isDataOrBlob) {
      img.crossOrigin = 'anonymous'
    }
    img.onload = () => {
      imageCache.set(src, img)
      resolve(img)
    }
    img.onerror = () => {
      if (isDataOrBlob) {
        const localImg = new Image()
        localImg.onload = () => {
          imageCache.set(src, localImg)
          resolve(localImg)
        }
        localImg.onerror = () => resolve(null)
        localImg.src = src
      } else {
        // Fallback seguro inmediato que NUNCA falla ni deja la pantalla negra
        const fallbackData = createProceduralCover(reelHeadline.value || 'StreamePro', reelSubline.value || 'Edición Oficial', '⚡')
        if (fallbackData) {
          const fallback = new Image()
          fallback.onload = () => {
            imageCache.set(src, fallback)
            resolve(fallback)
          }
          fallback.onerror = () => resolve(null)
          fallback.src = fallbackData
        } else {
          resolve(null)
        }
      }
    }
    img.src = src
    if (img.complete && (img.naturalWidth > 0 || img.width > 0)) {
      imageCache.set(src, img)
      resolve(img)
    }
  })
}

// ==========================================
// PRECARGA Y CACHÉ DE PLANTILLAS OFICIALES DE FLYER 9:16
// Sincronizado exactamente con las plantillas originales de FlyerGeneratorModal
// ==========================================
const loadedFlyerTemplates = {
  gaming: null,
  cyber: null
}

async function preloadFlyerTemplates() {
  try {
    const [gImg, cImg] = await Promise.all([
      loadImage('/flyers/gaming-template-story.jpg'),
      loadImage('/flyer-template-cyber-story.png')
    ])
    if (gImg) loadedFlyerTemplates.gaming = gImg
    if (cImg) loadedFlyerTemplates.cyber = cImg
    requestCanvasRedraw()
  } catch (err) {
    console.warn('[ReelsStudio] Error preloading flyer templates:', err)
  }
}

function wrapReelsText(text, maxChars) {
  if (!text) return []
  const words = text.split(' ')
  const lines = []
  let cur = words[0] || ''
  for (let i = 1; i < words.length; i++) {
    if ((cur + ' ' + words[i]).length > maxChars) {
      lines.push(cur)
      cur = words[i]
    } else {
      cur += ' ' + words[i]
    }
  }
  lines.push(cur)
  return lines
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
  { id: 'flyer_official', icon: '🎨', name: 'Flyer Oficial (Story 9:16)', desc: 'Mismo diseño exacto y plantilla de FlyerGenerator, pero animado a 30 FPS' },
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
  { id: 'ps5_neon', icon: '🎮', label: 'PS5 Gaming', url: '/flyers/gaming-template-story.jpg' },
  { id: 'cyber_stream', icon: '⚡', label: 'Cyber Stream', url: '/flyer-template-cyber-story.png' },
  { id: 'vip_banner', icon: '💎', label: 'VIP Gold', url: '/og-banner.jpg' },
  { id: 'arcade_feed', icon: '🕹️', label: 'Retro Arcade', url: '/flyers/gaming-template-feed.jpg' }
]

const currentTheme = computed(() => {
  return visualThemes.find(t => t.id === currentThemeId.value) || visualThemes[0]
})

const currentLayoutName = computed(() => {
  return layoutTemplates.find(l => l.id === currentLayoutId.value)?.name || 'Flyer Oficial'
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
    phone: effectiveContactPhone.value,
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
  applyGamingProduct(game)
}

function selectContentMode(modeId) {
  selectedMode.value = modeId
  if (modeId === 'streaming') {
    pickedStreamingKey.value = Object.keys(adminStore.pricingCatalog)[0] || 'Netflix'
    onStreamingServiceChange()
  } else if (modeId === 'gaming') {
    if (!adminStore.isGamingCatalogLoaded && typeof adminStore.loadFullGamingCatalog === 'function') {
      adminStore.loadFullGamingCatalog().then(() => {
        const firstGame = (adminStore.processedGamingProducts || [])[0]
        if (firstGame && !pickedGameId.value) {
          pickedGameId.value = firstGame.id
          applyGamingProduct(firstGame)
        }
      }).catch(() => {})
    }
    const firstGame = (adminStore.processedGamingProducts || [])[0]
    if (firstGame) {
      pickedGameId.value = firstGame.id
      applyGamingProduct(firstGame)
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
  reelBullet1.value = '⚡ Entrega Inmediata en 15 Minutos'
  reelBullet2.value = '🔒 Perfil Privado con PIN Exclusivo'
  const cover = getStreamingPlatformCover(srvKey)
  reelArtworkUrl.value = cover
  defaultProductCoverUrl.value = cover
  loadArtwork(cover)
}

function applyGamingProduct(passedGame = null) {
  const g = passedGame || (adminStore.processedGamingProducts || []).find(x => x.id === pickedGameId.value)
  if (!g) return
  const rawTitle = g.title || g.name || 'Videojuego'
  reelHeadline.value = cleanGameTitle(rawTitle).toUpperCase()
  const isPri = pickedGamingLicense.value === 'primary'
  reelSubline.value = g.subtitle || (isPri ? 'Cuenta Primaria · Jugá desde tu usuario (PSN)' : 'Cuenta Secundaria · Activación Oficial')
  reelPromoPrice.value = g.price ? Number(g.price) : (isPri ? Number(g.effectivePrimaryPrice || 15000) : Number(g.effectiveSecondaryPrice || 9000))
  reelRegularPrice.value = g.originalPrice ? Number(g.originalPrice) : Math.round(reelPromoPrice.value * 1.3)
  reelBadgeText.value = g.badge || (isPri ? '👑 PS STORE OFICIAL' : '⚡ OFERTA ECONÓMICA')
  currentSealText.value = g.guaranteeText || (isPri ? '🎮 JUGÁ CON TU PERFIL PSN' : '⚡ ENTREGA INMEDIATA 15 MIN')
  reelBullet1.value = g.bullet1 || '⚡ Entrega Inmediata en 15 Minutos'
  reelBullet2.value = g.bullet2 || (isPri ? '🎮 Jugá desde tu usuario personal (PSN)' : '🎮 Cuenta Secundaria Oficial Anti-Candado')
  reelBullet3.value = g.bullet3 || '🛡️ Soporte VIP & Garantía 30 Días'
  const rawCover = g.image || g.cover || '/flyers/gaming-template-story.jpg'
  reelArtworkUrl.value = rawCover
  defaultProductCoverUrl.value = rawCover
  loadArtwork(rawCover)
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
  reelBullet1.value = '⚡ Activación Simultánea de Pantallas'
  reelBullet2.value = '🛡️ Ahorro Máximo con Soporte 24/7'
  const cover = '/flyer-template-cyber-story.png'
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
  reelBullet1.value = '🎁 Participación 100% Gratuita'
  reelBullet2.value = '🏆 Sorteo Transparente y Oficial'
  const cover = '/og-banner.jpg'
  reelArtworkUrl.value = cover
  defaultProductCoverUrl.value = cover
  loadArtwork(cover)
}

async function loadArtwork(src) {
  if (!src) return
  const safeSrc = getCORSImageUrl(src)
  const img = await loadImage(safeSrc)
  if (img) {
    loadedArtworkImage.value = img
    requestCanvasRedraw()
  } else {
    if (src !== safeSrc) {
      const directImg = await loadImage(src)
      if (directImg) {
        loadedArtworkImage.value = directImg
        requestCanvasRedraw()
        return
      }
    }
    // Generar respaldo procedural garantizado
    const fallbackData = createProceduralCover(reelHeadline.value || 'StreamePro', reelSubline.value || 'Edición Oficial', '⚡')
    const fallbackImg = await loadImage(fallbackData)
    if (fallbackImg) {
      loadedArtworkImage.value = fallbackImg
      requestCanvasRedraw()
    }
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
  const safeUrl = getCORSImageUrl(url)
  reelArtworkUrl.value = safeUrl
  loadArtwork(safeUrl)
  customUrlInput.value = ''
  toast.add('🖼️ Imagen web aplicada con protección CORS', 'success')
}

function selectArtworkPreset(url) {
  if (!url) return
  const safeUrl = getCORSImageUrl(url)
  reelArtworkUrl.value = safeUrl
  loadArtwork(safeUrl)
  toast.add('🎨 Fondo temático aplicado', 'success')
}

function resetToOriginalProductCover() {
  reelArtworkUrl.value = defaultProductCoverUrl.value || '/flyers/gaming-template-story.jpg'
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

// AUTOMATIZACIÓN Y PULIDO INTELIGENTE
function autoOptimizeReel() {
  const mode = selectedMode.value
  imageOffsetX.value = 0
  imageOffsetY.value = 0
  imageRotation.value = 0
  imageZoomFactor.value = 1.0
  imageBrightness.value = 100
  imageContrast.value = 105
  imageSaturation.value = 115

  if (mode === 'gaming') {
    currentLayoutId.value = 'flyer_official'
    currentThemeId.value = 'cyber_glow'
    imageFitMode.value = 'contain'
    showBrandPill.value = true
    showRegularPrice.value = true
    showPromoPill.value = true
    showContactPhone.value = true
    audioWaveStyle.value = 'bars'
    particleStyle.value = 'neon_dots'
    showCyberGrid.value = true
    showHudBrackets.value = true
    cyberScanlineSpeed.value = 'normal'
    reelBadgeText.value = pickedGamingLicense.value === 'primary' ? '🔥 CUENTA PRIMARIA PS5/PS4' : '⚡ CUENTA SECUNDARIA PS5/PS4'
    currentSealText.value = pickedGamingLicense.value === 'primary' ? '🎮 JUGÁ CON TU USUARIO (PSN)' : '⚡ ENTREGA INMEDIATA 15 MIN'
    reelCtaText.value = '🎮 PEDILO AL WHATSAPP'
  } else if (mode === 'streaming') {
    currentLayoutId.value = 'flyer_official'
    currentThemeId.value = pickedStreamingKey.value?.toLowerCase().includes('netflix') ? 'fire_sale' : 'cyber_glow'
    imageFitMode.value = 'contain'
    showBrandPill.value = true
    showRegularPrice.value = true
    showPromoPill.value = true
    showContactPhone.value = true
    audioWaveStyle.value = 'wave'
    particleStyle.value = 'neon_dots'
    cinematicStudioText.value = '★ ESTRENO EXCLUSIVO · STREAMEPRO STUDIOS ★'
    cinematicVignetteDepth.value = 85
    cinematicGlassOpacity.value = 88
    reelBadgeText.value = '🍿 PANTALLA ULTRA HD 4K'
    currentSealText.value = '🔒 100% CUENTA PRIVADA CON PIN'
    reelCtaText.value = '🍿 PEDÍ TU CUENTA HOY'
  } else if (mode === 'combo') {
    currentLayoutId.value = 'flyer_official'
    currentThemeId.value = 'gold_vip'
    imageFitMode.value = 'cover'
    showBrandPill.value = true
    showRegularPrice.value = true
    showPromoPill.value = true
    showContactPhone.value = true
    audioWaveStyle.value = 'pulse'
    particleStyle.value = 'gold_dust'
    vipRibbonText.value = '👑 EDICIÓN VIP OFICIAL'
    glassBlurFactor.value = 28
    vipStarsDensity.value = 'alta'
    reelBadgeText.value = '🎁 SUPER COMBO AHORRO'
    currentSealText.value = '🛡️ GARANTÍA OFICIAL 30 DÍAS'
    reelCtaText.value = '📲 APROVECHAR COMBO VIP'
  } else if (mode === 'giveaway') {
    currentLayoutId.value = 'flyer_official'
    currentThemeId.value = 'fire_sale'
    imageFitMode.value = 'cover'
    showBrandPill.value = true
    showRegularPrice.value = false
    showPromoPill.value = true
    showContactPhone.value = true
    audioWaveStyle.value = 'bars'
    particleStyle.value = 'fire_sparks'
    flashBannerText.value = '⚡ OFERTA RELÁMPAGO ACTIVA'
    countdownHours.value = 6
    electricPulse.value = true
    reelBadgeText.value = '🎉 SORTEO EXCLUSIVO'
    currentSealText.value = '🎟️ ACTA PÚBLICA TRANSPARENTE'
    reelCtaText.value = '🎟️ PARTICIPAR GRATIS'
  }

  requestCanvasRedraw()
  toast.add('⚡ ¡Reel optimizado automáticamente con proporciones y estilo profesional!', 'success')
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
  requestCanvasRedraw()
}

// ==========================================
// MOTOR CANVAS ELEVADO 9:16 (1080x1920)
// ==========================================
let animFrameId = null
let animationStartTime = 0
let recordingInterval = null
let recordingTimeout = null

function roundRect(ctx, x, y, w, h, r) {
  if (typeof ctx.roundRect === 'function') {
    ctx.beginPath()
    ctx.roundRect(x, y, w, h, r)
    return
  }
  let radius = Number(r) || 0
  if (w < 2 * radius) radius = w / 2
  if (h < 2 * radius) radius = h / 2
  ctx.beginPath()
  ctx.moveTo(x + radius, y)
  ctx.lineTo(x + w - radius, y)
  ctx.quadraticCurveTo(x + w, y, x + w, y + radius)
  ctx.lineTo(x + w, y + h - radius)
  ctx.quadraticCurveTo(x + w, y + h, x + w - radius, y + h)
  ctx.lineTo(x + radius, y + h)
  ctx.quadraticCurveTo(x, y + h, x, y + h - radius)
  ctx.lineTo(x, y + radius)
  ctx.quadraticCurveTo(x, y, x + radius, y)
  ctx.closePath()
}

// Generador procedural directo en el canvas (garantiza que NUNCA quede una placa o recuadro vacío)
function drawDirectProceduralCover(ctx, x, y, w, h, t = 0) {
  ctx.save()
  const th = currentTheme.value
  const cx = x + w / 2
  const cy = y + h / 2
  const maxR = Math.max(w, h) * 0.75

  // 1. Fondo radial con gradiente profundo
  const grad = ctx.createRadialGradient(cx, cy * 0.95, 30, cx, cy, maxR)
  grad.addColorStop(0, th.bg1 || '#0f172a')
  grad.addColorStop(0.65, th.bg2 || '#020617')
  grad.addColorStop(1, '#000000')
  ctx.fillStyle = grad
  ctx.fillRect(x, y, w, h)

  // 2. Cuadrícula cibernética sutil
  ctx.strokeStyle = 'rgba(255, 255, 255, 0.04)'
  ctx.lineWidth = 1.5
  for (let lx = x; lx < x + w; lx += 45) {
    ctx.beginPath(); ctx.moveTo(lx, y); ctx.lineTo(lx, y + h); ctx.stroke()
  }
  for (let ly = y; ly < y + h; ly += 45) {
    ctx.beginPath(); ctx.moveTo(x, ly); ctx.lineTo(x + w, ly); ctx.stroke()
  }

  // 3. Resplandor neón pulsante
  const pulse = isAnimated.value ? (1.0 + Math.sin(t * 3.5) * 0.05) : 1.0
  const glowR = Math.min(w, h) * 0.26 * pulse
  const rGlow = ctx.createRadialGradient(cx, cy - 40, 10, cx, cy - 40, glowR * 1.6)
  rGlow.addColorStop(0, th.glow || 'rgba(56, 189, 248, 0.45)')
  rGlow.addColorStop(1, 'rgba(0, 0, 0, 0)')
  ctx.fillStyle = rGlow
  ctx.fillRect(x, y, w, h)

  // 4. Medallón central circular con borde neón
  ctx.save()
  ctx.shadowColor = th.accent
  ctx.shadowBlur = 35
  ctx.fillStyle = 'rgba(15, 23, 42, 0.94)'
  ctx.beginPath()
  ctx.arc(cx, cy - 40, glowR, 0, Math.PI * 2)
  ctx.fill()
  ctx.strokeStyle = th.accent
  ctx.lineWidth = 4
  ctx.stroke()
  ctx.restore()

  // 5. Emblema o Icono temático dinámico
  let icon = '⚡'
  if (selectedMode.value === 'streaming') {
    const k = (pickedStreamingKey.value || '').toLowerCase()
    if (k.includes('netflix')) icon = '🍿'
    else if (k.includes('disney')) icon = '✨'
    else if (k.includes('spotify') || k.includes('music')) icon = '🎵'
    else if (k.includes('youtube')) icon = '▶️'
    else if (k.includes('max') || k.includes('hbo')) icon = '🎬'
    else if (k.includes('prime')) icon = '📦'
    else icon = '📺'
  } else if (selectedMode.value === 'gaming') {
    icon = '🎮'
  } else if (selectedMode.value === 'combo') {
    icon = '🎁'
  } else if (selectedMode.value === 'giveaway') {
    icon = '🎟️'
  }

  ctx.font = `${Math.round(glowR * 0.9)}px "Apple Color Emoji", "Segoe UI Emoji", sans-serif`
  ctx.textAlign = 'center'
  ctx.textBaseline = 'middle'
  ctx.fillText(icon, cx, cy - 40)

  // 6. Tipografía del Producto
  ctx.fillStyle = '#ffffff'
  ctx.font = `900 ${Math.min(38, Math.round(w * 0.052))}px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif`
  ctx.shadowColor = 'rgba(0, 0, 0, 0.9)'
  ctx.shadowBlur = 18
  const headline = (reelHeadline.value || 'STREAMEPRO OFICIAL').toUpperCase().slice(0, 30)
  ctx.fillText(headline, cx, cy + glowR + 32)

  // 7. Subtítulo con color acentuado
  ctx.fillStyle = th.accent
  ctx.font = `bold ${Math.min(24, Math.round(w * 0.036))}px -apple-system, sans-serif`
  ctx.fillText((reelSubline.value || 'Activación Inmediata').slice(0, 40), cx, cy + glowR + 70)

  // 8. Distintivo inferior de confianza
  ctx.fillStyle = 'rgba(148, 163, 184, 0.85)'
  ctx.font = 'bold 18px monospace'
  ctx.fillText('⚡ ACTIVACIÓN OFICIAL · STREAMEPRO', cx, cy + glowR + 112)

  ctx.restore()
}

// Renderiza el titular y subtítulo con encaje tipográfico perfecto (1 o 2 líneas según extensión sin cortar palabras)
function drawFittedHeadline(ctx, headlineText, sublineText, W, th, t = 0) {
  if (!headlineText && !sublineText) return
  ctx.save()

  const maxW = 900
  const centerX = W / 2
  const text = (headlineText || '').trim().toUpperCase()

  // 1. Probar en 1 línea con 54px
  ctx.font = '900 54px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
  const w54 = ctx.measureText(text).width

  if (w54 <= maxW) {
    ctx.fillStyle = '#ffffff'
    ctx.textAlign = 'center'
    ctx.shadowColor = 'rgba(0, 0, 0, 0.92)'
    ctx.shadowBlur = 22
    ctx.shadowOffsetY = 4
    ctx.fillText(text, centerX, 260)
  } else {
    // 2. Probar en 1 línea con 44px
    ctx.font = '900 44px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
    const w44 = ctx.measureText(text).width
    if (w44 <= maxW) {
      ctx.fillStyle = '#ffffff'
      ctx.textAlign = 'center'
      ctx.shadowColor = 'rgba(0, 0, 0, 0.92)'
      ctx.shadowBlur = 20
      ctx.shadowOffsetY = 4
      ctx.fillText(text, centerX, 262)
    } else {
      // 3. Dividir limpiamente en 2 líneas por palabras
      const words = text.split(' ')
      let line1 = ''
      let line2 = ''
      const targetHalf = text.length / 2
      let currentLen = 0

      for (let i = 0; i < words.length; i++) {
        if (currentLen + words[i].length <= targetHalf + 4 || i === 0) {
          line1 = line1 ? `${line1} ${words[i]}` : words[i]
          currentLen = line1.length
        } else {
          line2 = words.slice(i).join(' ')
          break
        }
      }

      if (!line2 && words.length > 1) {
        const mid = Math.ceil(words.length / 2)
        line1 = words.slice(0, mid).join(' ')
        line2 = words.slice(mid).join(' ')
      }

      let fontSize = 38
      ctx.font = `900 ${fontSize}px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif`
      while ((ctx.measureText(line1).width > maxW || ctx.measureText(line2).width > maxW) && fontSize > 26) {
        fontSize -= 2
        ctx.font = `900 ${fontSize}px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif`
      }

      ctx.fillStyle = '#ffffff'
      ctx.textAlign = 'center'
      ctx.shadowColor = 'rgba(0, 0, 0, 0.92)'
      ctx.shadowBlur = 18
      ctx.shadowOffsetY = 3
      ctx.fillText(line1, centerX, 238)
      ctx.fillText(line2, centerX, 278)
    }
  }

  // Subtítulo con acento y auto-ajuste de escala
  if (sublineText) {
    let subSize = 26
    ctx.font = `bold ${subSize}px -apple-system, sans-serif`
    while (ctx.measureText(sublineText).width > maxW && subSize > 20) {
      subSize -= 2
      ctx.font = `bold ${subSize}px -apple-system, sans-serif`
    }
    ctx.fillStyle = th.accent
    ctx.textAlign = 'center'
    ctx.shadowColor = 'rgba(0, 0, 0, 0.85)'
    ctx.shadowBlur = 14
    ctx.shadowOffsetY = 2
    ctx.fillText(sublineText, centerX, 316)
  }

  ctx.restore()
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

// Visualizador de Audio Adaptable
function drawAudioVisualizer(ctx, x, y, color = '#38bdf8', t = 0) {
  if (audioWaveStyle.value === 'none') return
  ctx.save()

  if (audioWaveStyle.value === 'bars') {
    const barCount = 9
    const barW = 6
    const gap = 4
    const totalW = barCount * barW + (barCount - 1) * gap
    const startX = x - totalW / 2
    ctx.fillStyle = color
    for (let i = 0; i < barCount; i++) {
      const h = 8 + Math.abs(Math.sin(t * 7 + i * 0.9)) * 24
      const bx = startX + i * (barW + gap)
      const by = y - h / 2
      roundRect(ctx, bx, by, barW, h, 3)
      ctx.fill()
    }
  } else if (audioWaveStyle.value === 'wave') {
    ctx.beginPath()
    for (let px = -70; px <= 70; px += 4) {
      const py = Math.sin(t * 6 + px * 0.08) * 12
      if (px === -70) ctx.moveTo(x + px, y + py)
      else ctx.lineTo(x + px, y + py)
    }
    ctx.strokeStyle = color
    ctx.lineWidth = 3
    ctx.lineCap = 'round'
    ctx.stroke()
  } else if (audioWaveStyle.value === 'pulse') {
    const radius = 13 + Math.abs(Math.sin(t * 5)) * 7
    ctx.strokeStyle = color
    ctx.lineWidth = 3
    ctx.beginPath()
    ctx.arc(x, y, radius, 0, Math.PI * 2)
    ctx.stroke()
    ctx.save()
    ctx.globalAlpha = 0.35
    ctx.beginPath()
    ctx.arc(x, y, radius + 10, 0, Math.PI * 2)
    ctx.stroke()
    ctx.restore()
  }

  ctx.restore()
}

// Partículas Adaptables (Neón, Oro o Fuego)
function drawAdaptiveParticles(ctx, W, H, t = 0) {
  if (!isAnimated.value || particleStyle.value === 'none') return
  ctx.save()

  let count = 22
  if (particleStyle.value === 'gold_dust') {
    count = vipStarsDensity.value === 'baja' ? 14 : (vipStarsDensity.value === 'alta' ? 42 : 26)
  }
  for (let i = 0; i < count; i++) {
    const px = (W * ((i * 0.047 + t * 0.025) % 1))
    const py = (H * ((i * 0.063 + Math.sin(t + i) * 0.04 + 0.08) % 0.95))
    const pAlpha = 0.2 + Math.sin(t * 3 + i) * 0.15

    if (particleStyle.value === 'gold_dust') {
      ctx.fillStyle = `rgba(245, 158, 11, ${Math.max(0.05, pAlpha)})`
      ctx.beginPath()
      ctx.arc(px, py, (i % 3) + 2, 0, Math.PI * 2)
      ctx.fill()
    } else if (particleStyle.value === 'fire_sparks') {
      ctx.fillStyle = `rgba(239, 68, 68, ${Math.max(0.05, pAlpha * 1.2)})`
      ctx.beginPath()
      ctx.arc(px, py, (i % 2) + 2.5, 0, Math.PI * 2)
      ctx.fill()
    } else {
      ctx.fillStyle = `rgba(255, 255, 255, ${Math.max(0.04, pAlpha)})`
      ctx.beginPath()
      ctx.arc(px, py, (i % 3) + 2.5, 0, Math.PI * 2)
      ctx.fill()
    }
  }

  ctx.restore()
}

// Haz de luz / Escáner Cyber horizontal
function drawLaserScanline(ctx, x, y, w, h, t = 0, color = 'rgba(56, 189, 248, 0.45)') {
  if (cyberScanlineSpeed.value === 'off') return
  const mult = cyberScanlineSpeed.value === 'fast' ? 420 : 260
  const scanY = y + ((t * mult) % (h + 80)) - 40
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
  if (!img || !(img.width > 0 || img.naturalWidth > 0)) return

  const { blurBg = true, showReticle = isPointerDragging, addVignette = true } = options
  const innerX = targetX
  const innerY = targetY
  const innerW = targetW
  const innerH = targetH

  // 1. Fondo ambiental saturado difuso (con blur adaptable en VIP)
  if (blurBg) {
    const bgScale = Math.max(innerW / (img.width || 1), innerH / (img.height || 1)) * 1.25
    const bgW = (img.width || 1) * bgScale
    const bgH = (img.height || 1) * bgScale
    const blurPx = currentLayoutId.value === 'glass_vip' ? (glassBlurFactor.value || 25) : 28
    ctx.save()
    ctx.filter = `blur(${blurPx}px) brightness(0.40) saturate(1.4)`
    ctx.drawImage(img, innerX + (innerW - bgW) / 2, innerY + (innerH - bgH) / 2, bgW, bgH)
    ctx.restore()
  }

  // 2. Portada nítida en primer plano con Zoom, Desplazamiento X/Y, Inclinación y Filtros
  const zoomAnim = isAnimated.value ? (1.0 + Math.sin(t * 1.4) * 0.03) : 1.0
  const finalZoom = zoomAnim * (Number(imageZoomFactor.value) || 1.0)
  
  const imgAspect = (img.width || 1) / (img.height || 1)
  const boxAspect = innerW / innerH
  let baseW = innerW
  let baseH = innerH

  if (imageFitMode.value === 'contain') {
    if (boxAspect > imgAspect) {
      baseH = innerH - 36
      baseW = baseH * imgAspect
    } else {
      baseW = innerW - 36
      baseH = baseW / imgAspect
    }
  } else {
    // Modo cover: mantener proporción llenando todo el marco sin estirar ni aplastar
    if (boxAspect > imgAspect) {
      baseW = innerW
      baseH = innerW / imgAspect
    } else {
      baseH = innerH
      baseW = innerH * imgAspect
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
  if (isAnimated.value && currentLayoutId.value === 'cyber_pro') {
    drawLaserScanline(ctx, innerX, innerY, innerW, innerH, t, currentTheme.value.glow)
  }

  // 4. Retícula táctil de arrastre (solo si el usuario arrastra y no está exportando)
  if (showReticle && !isDrawingForExport.value) {
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

// Dibuja las Guías de Zonas Seguras de TikTok / Reels / WhatsApp (Solo en Vista Previa)
function drawSafeZonesGuide(ctx, W, H) {
  if (isDrawingForExport.value || !showSafeZoneOverlay.value) return
  ctx.save()

  // Margen superior (avatar, status bar)
  ctx.fillStyle = 'rgba(236, 72, 153, 0.12)'
  ctx.fillRect(0, 0, W, 130)
  ctx.strokeStyle = 'rgba(236, 72, 153, 0.45)'
  ctx.lineWidth = 2
  ctx.setLineDash([8, 8])
  ctx.beginPath(); ctx.moveTo(0, 130); ctx.lineTo(W, 130); ctx.stroke()
  ctx.fillStyle = '#f472b6'
  ctx.font = 'bold 20px monospace'
  ctx.fillText('🛡️ MARGEN SUPERIOR TIKTOK / REELS', 30, 80)

  // Margen inferior (descripción, audio ticker)
  ctx.fillStyle = 'rgba(236, 72, 153, 0.12)'
  ctx.fillRect(0, 1720, W, 200)
  ctx.beginPath(); ctx.moveTo(0, 1720); ctx.lineTo(W, 1720); ctx.stroke()
  ctx.fillText('💬 ZONA DE DESCRIPCIÓN & AUDIO DE LA RED SOCIAL', 30, 1760)

  // Margen lateral derecho (botones like, compartir, comentarios)
  ctx.fillStyle = 'rgba(236, 72, 153, 0.10)'
  ctx.fillRect(W - 120, 600, 120, 950)
  ctx.strokeRect(W - 120, 600, 120, 950)
  ctx.fillText('❤️ BOTONES', W - 110, 650)

  ctx.restore()
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
  if (layout === 'cinematic' && loadedArtworkImage.value && (loadedArtworkImage.value.width > 0 || loadedArtworkImage.value.naturalWidth > 0)) {
    const img = loadedArtworkImage.value
    const zoomAnim = isAnimated.value ? (1.0 + Math.sin(t * 1.2) * 0.02) : 1.0
    const finalZoom = zoomAnim * (Number(imageZoomFactor.value) || 1.0)
    const bgScale = Math.max(W / (img.width || 1), H / (img.height || 1)) * finalZoom
    const bw = (img.width || 1) * bgScale
    const bh = (img.height || 1) * bgScale
    const panX = ((Number(imageOffsetX.value) || 0) / 100) * W
    const panY = ((Number(imageOffsetY.value) || 0) / 100) * H
    const centerX = W / 2 + panX
    const centerY = H / 2 + panY

    ctx.save()
    ctx.translate(centerX, centerY)
    if (imageRotation.value) {
      ctx.rotate((imageRotation.value * Math.PI) / 180)
    }
    ctx.filter = `brightness(${imageBrightness.value * 0.85}%) contrast(${imageContrast.value * 1.1}%) saturate(${imageSaturation.value * 1.2}%)`
    ctx.drawImage(img, -bw / 2, -bh / 2, bw, bh)
    ctx.restore()

    // Retícula interactiva al arrastrar sobre el póster cinemático
    if (isPointerDragging && !isDrawingForExport.value) {
      ctx.save()
      ctx.strokeStyle = th.accent
      ctx.lineWidth = 3
      ctx.beginPath()
      ctx.arc(centerX, centerY, 36, 0, Math.PI * 2)
      ctx.moveTo(centerX - 54, centerY); ctx.lineTo(centerX + 54, centerY)
      ctx.moveTo(centerX, centerY - 54); ctx.lineTo(centerX, centerY + 54)
      ctx.stroke()
      ctx.restore()
    }

    // Viñeta Cinematográfica Configurable
    const vigRatio = (cinematicVignetteDepth.value || 85) / 100
    const topVig = ctx.createLinearGradient(0, 0, 0, 520)
    topVig.addColorStop(0, `rgba(2, 6, 23, ${vigRatio})`)
    topVig.addColorStop(0.65, `rgba(2, 6, 23, ${vigRatio * 0.7})`)
    topVig.addColorStop(1, 'rgba(2, 6, 23, 0)')
    ctx.fillStyle = topVig
    ctx.fillRect(0, 0, W, 520)

    const botVig = ctx.createLinearGradient(0, H - 980, 0, H)
    botVig.addColorStop(0, 'rgba(2, 6, 23, 0)')
    botVig.addColorStop(0.35, `rgba(2, 6, 23, ${vigRatio * 0.85})`)
    botVig.addColorStop(1, `rgba(2, 6, 23, ${vigRatio})`)
    ctx.fillStyle = botVig
    ctx.fillRect(0, H - 980, W, 980)

  } else {
    // FONDOS CON GRADIENTE Y PARTÍCULAS
    const bgGrad = ctx.createLinearGradient(0, 0, 0, H)
    bgGrad.addColorStop(0, th.bg1)
    bgGrad.addColorStop(0.45, th.bg2)
    bgGrad.addColorStop(1, th.bg3)
    ctx.fillStyle = bgGrad
    ctx.fillRect(0, 0, W, H)

    // Malla Holográfica de fondo en Cyber Pro si está activa
    if (layout === 'cyber_pro' && showCyberGrid.value) {
      ctx.save()
      ctx.strokeStyle = 'rgba(56, 189, 248, 0.05)'
      ctx.lineWidth = 1
      for (let x = 0; x < W; x += 60) {
        ctx.beginPath(); ctx.moveTo(x, 0); ctx.lineTo(x, H); ctx.stroke()
      }
      for (let y = 0; y < H; y += 60) {
        ctx.beginPath(); ctx.moveTo(0, y); ctx.lineTo(W, y); ctx.stroke()
      }
      ctx.restore()
    }

    // Resplandor Neón Central
    const radialGlow = ctx.createRadialGradient(W * 0.5, H * 0.38, 40, W * 0.5, H * 0.38, 720)
    radialGlow.addColorStop(0, th.glow)
    radialGlow.addColorStop(1, 'rgba(0,0,0,0)')
    ctx.fillStyle = radialGlow
    ctx.fillRect(0, 0, W, H)
  }

  // Partículas Adaptables (Neón, Oro o Fuego)
  drawAdaptiveParticles(ctx, W, H, t)

  // Marco Cyber 9:16 con esquinas HUD (en Cyber Pro, Glass VIP y Split Flash)
  if (layout !== 'cinematic' && layout !== 'flyer_official') {
    ctx.save()
    ctx.strokeStyle = 'rgba(255, 255, 255, 0.12)'
    ctx.lineWidth = 4
    roundRect(ctx, 32, 32, W - 64, H - 64, 48)
    ctx.stroke()
    if (showHudBrackets.value || layout !== 'cyber_pro') {
      drawCornerBrackets(ctx, 32, 32, W - 64, H - 64, 40, th.accent)
    }
    ctx.restore()
  }

  // Header: Insignia de Tienda / Distribuidor (Proporción perfecta dentro de zona segura)
  if (showBrandPill.value && layout !== 'flyer_official') {
    ctx.save()
    const brandPillW = 460
    const brandPillH = 68
    const brandPillX = (W - brandPillW) / 2
    const brandPillY = 125
    ctx.fillStyle = 'rgba(15, 23, 42, 0.90)'
    roundRect(ctx, brandPillX, brandPillY, brandPillW, brandPillH, 34)
    ctx.fill()
    ctx.strokeStyle = 'rgba(255, 255, 255, 0.18)'
    ctx.lineWidth = 2
    ctx.stroke()

    ctx.fillStyle = '#ffffff'
    ctx.font = '900 23px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
    ctx.textAlign = 'center'
    ctx.fillText(`⚡ ${reelStoreName.value.toUpperCase()} · OFICIAL`, W / 2, brandPillY + 42)
    ctx.restore()
  }

  // Ecualizador de Audio / Trending Sound Bar en Header
  if (layout !== 'flyer_official') {
    drawAudioVisualizer(ctx, W / 2, 208, th.accent, t)
    drawFittedHeadline(ctx, reelHeadline.value, reelSubline.value, W, th, t)
  }

  // ==========================================
  // 2. RENDERIZADO SEGÚN PLANTILLA
  // ==========================================

  // ----------------------------------------------------
  // PLANTILLA 0: FLYER OFICIAL ANIMADO (STORY 9:16)
  // Mismo diseño exacto y plantilla de FlyerGenerator, pero con dinámicas a 30 FPS
  // ----------------------------------------------------
  if (layout === 'flyer_official') {
    const tplImg = selectedMode.value === 'gaming' ? loadedFlyerTemplates.gaming : loadedFlyerTemplates.cyber
    if (tplImg && (tplImg.width > 0 || tplImg.naturalWidth > 0)) {
      ctx.save()
      ctx.drawImage(tplImg, 0, 0, W, H)
      ctx.restore()
    } else {
      const bgGrad = ctx.createLinearGradient(0, 0, 0, H)
      bgGrad.addColorStop(0, '#020617')
      bgGrad.addColorStop(0.5, '#082f49')
      bgGrad.addColorStop(1, '#020617')
      ctx.fillStyle = bgGrad
      ctx.fillRect(0, 0, W, H)
    }

    drawAdaptiveParticles(ctx, W, H, t)
    drawAudioVisualizer(ctx, W / 2, 140, th.accent, t)

    if (selectedMode.value === 'gaming') {
      // 1. Marco metálico visor gaming exacto (fx = 201, fy = 290, fw = 678, fh = 516, radius = 46)
      const fx = 201
      const fy = 290
      const fw = 678
      const fh = 516
      const cornerR = 46

      ctx.save()
      roundRect(ctx, fx, fy, fw, fh, cornerR)
      ctx.clip()

      ctx.fillStyle = '#060a14'
      ctx.fillRect(fx, fy, fw, fh)

      const img = loadedArtworkImage.value
      if (img && (img.width > 0 || img.naturalWidth > 0)) {
        // Renderizado del arte con ajustes finos (zoom, rotación, pan, brillo, contraste, saturación)
        drawAdjustedArtwork(ctx, img, fx, fy, fw, fh, t)
      } else {
        drawDirectProceduralCover(ctx, fx, fy, fw, fh, t)
      }
      ctx.restore()

      // Borde sutil del marco metálico
      ctx.save()
      ctx.strokeStyle = 'rgba(56, 189, 248, 0.35)'
      ctx.lineWidth = 2
      roundRect(ctx, fx, fy, fw, fh, cornerR)
      ctx.stroke()
      ctx.restore()

      // 2. Etiqueta Oficial de Licencia Superior (bx = 201, by = 216, bw = 678, bh = 56)
      if (reelBadgeText.value) {
        const bw = 678
        const bh = 56
        const bx = (W - bw) / 2
        const by = 216
        ctx.save()
        const bGrad = ctx.createLinearGradient(bx, by, bx + bw, by)
        bGrad.addColorStop(0, '#0f172a')
        bGrad.addColorStop(0.5, '#0284c7')
        bGrad.addColorStop(1, '#1e3a8a')
        ctx.fillStyle = bGrad
        ctx.shadowColor = 'rgba(0, 220, 255, 0.70)'
        ctx.shadowBlur = 16
        roundRect(ctx, bx, by, bw, bh, bh / 2)
        ctx.fill()

        ctx.strokeStyle = 'rgba(56, 189, 248, 0.75)'
        ctx.lineWidth = 2
        ctx.stroke()

        if (isAnimated.value) {
          const shimmerPos = ((t * 0.45) % 1) * (bw + 200) - 100
          const sGrad = ctx.createLinearGradient(bx + shimmerPos, by, bx + shimmerPos + 80, by)
          sGrad.addColorStop(0, 'rgba(255, 255, 255, 0)')
          sGrad.addColorStop(0.5, 'rgba(255, 255, 255, 0.35)')
          sGrad.addColorStop(1, 'rgba(255, 255, 255, 0)')
          ctx.fillStyle = sGrad
          ctx.clip()
          ctx.fillRect(bx, by, bw, bh)
        }

        ctx.shadowBlur = 0
        ctx.fillStyle = '#ffffff'
        ctx.font = '900 24px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
        ctx.textAlign = 'center'
        ctx.fillText(reelBadgeText.value.toUpperCase(), W / 2, by + bh * 0.68)
        ctx.restore()
      }

      // 3. Etiqueta Impacto Central (y = 895, 46.6%)
      const impactText = currentSealText.value || '🔥 ENTREGA INMEDIATA · GARANTÍA OFICIAL'
      if (impactText) {
        ctx.save()
        ctx.font = '900 24px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
        const badgeW = Math.min(ctx.measureText(impactText).width + 56, 780)
        const badgeH = 44
        const badgeX = (W - badgeW) / 2
        const badgeY = Math.round(H * 0.466)

        ctx.shadowColor = th.glow
        ctx.shadowBlur = 18
        const bGrad = ctx.createLinearGradient(badgeX, badgeY, badgeX + badgeW, badgeY)
        bGrad.addColorStop(0, th.pillGrad[0])
        bGrad.addColorStop(1, th.pillGrad[1])
        ctx.fillStyle = bGrad
        roundRect(ctx, badgeX, badgeY, badgeW, badgeH, badgeH / 2)
        ctx.fill()

        ctx.shadowBlur = 0
        ctx.textAlign = 'center'
        ctx.fillStyle = '#ffffff'
        ctx.fillText(impactText, W / 2, badgeY + badgeH * 0.68)
        ctx.restore()
      }

      // 4. Título + Subtítulo dentro del Recuadro Oscuro Oficial (PILL: 54.6%..63.4%)
      const pTop = Math.round(H * 0.546)
      const pH = Math.round(H * 0.088)
      ctx.save()
      ctx.textAlign = 'center'
      const cleanTitle = cleanGameTitle(reelHeadline.value)
      const titleLines = wrapReelsText(cleanTitle, 28).slice(0, 2)
      ctx.font = `900 ${titleLines.length > 1 ? 36 : 44}px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif`
      ctx.fillStyle = '#ffffff'
      ctx.shadowColor = 'rgba(0, 0, 0, 0.95)'
      ctx.shadowBlur = 12
      titleLines.forEach((tl, i) => {
        const ty = pTop + (titleLines.length > 1 ? 52 + i * 44 : pH * 0.48)
        ctx.fillText(tl, W / 2, ty)
      })
      ctx.shadowBlur = 0
      ctx.font = 'bold 22px monospace'
      ctx.fillStyle = th.accent
      const subStr = reelSubline.value.length > 48 ? reelSubline.value.slice(0, 46) + '...' : reelSubline.value
      ctx.fillText(subStr, W / 2, pTop + pH - 22)
      ctx.restore()

      // 5. Zona Inferior de Precios con Halo Pulsante a 30 FPS y Descuento Oficial
      ctx.save()
      const haloRadius = isAnimated.value ? (320 + Math.sin(t * 3.2) * 25) : 320
      const haloGrad = ctx.createRadialGradient(W / 2, 1340, 20, W / 2, 1340, haloRadius)
      haloGrad.addColorStop(0, th.glow || 'rgba(0, 220, 255, 0.16)')
      haloGrad.addColorStop(1, 'rgba(0, 220, 255, 0)')
      ctx.fillStyle = haloGrad
      ctx.fillRect(100, 1220, W - 200, 240)

      ctx.textAlign = 'center'
      const hasOrig = showRegularPrice.value && Number(reelRegularPrice.value) > Number(reelPromoPrice.value)
      if (hasOrig) {
        ctx.font = 'bold 21px sans-serif'
        ctx.fillStyle = '#94a3b8'
        const discPct = calculatedDiscountPercent.value
        const origText = `Antes: $${Number(reelRegularPrice.value).toLocaleString('es-AR')}`
        const origW = ctx.measureText(origText).width
        const discountShift = discPct ? 48 : 0
        const textX = (W / 2) - discountShift
        ctx.fillText(origText, textX, 1272)

        // Línea roja tachada oficial exacta del Flyer
        ctx.strokeStyle = '#ef4444'
        ctx.lineWidth = 2.5
        ctx.beginPath()
        ctx.moveTo(textX - origW / 2, 1265)
        ctx.lineTo(textX + origW / 2, 1265)
        ctx.stroke()

        // Píldora esmeralda con porcentaje de descuento
        if (discPct) {
          const badgeX = textX + origW / 2 + 10
          const badgeW = 86
          const badgeH = 28
          ctx.fillStyle = 'rgba(16, 185, 129, 0.25)'
          roundRect(ctx, badgeX, 1250, badgeW, badgeH, 8)
          ctx.fill()
          ctx.strokeStyle = '#10b981'
          ctx.lineWidth = 1.5
          roundRect(ctx, badgeX, 1250, badgeW, badgeH, 8)
          ctx.stroke()

          ctx.fillStyle = '#34d399'
          ctx.font = '900 16px monospace'
          ctx.textAlign = 'center'
          ctx.fillText(`-${discPct}%`, badgeX + badgeW / 2, 1270)
        }
      } else {
        ctx.font = 'bold 20px sans-serif'
        ctx.fillStyle = '#94a3b8'
        ctx.fillText('PRECIO EXCLUSIVO', W / 2, 1272)
      }

      ctx.font = '900 78px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
      ctx.fillStyle = '#ffffff'
      ctx.shadowColor = th.glow
      ctx.shadowBlur = isAnimated.value ? (22 + Math.sin(t * 4.0) * 8) : 20
      ctx.fillText(`$${Number(reelPromoPrice.value).toLocaleString('es-AR')} ARS`, W / 2, 1344)
      ctx.shadowBlur = 0

      ctx.font = 'bold 18px sans-serif'
      ctx.fillStyle = th.accent
      ctx.fillText('🛡️ Garantía Oficial 30 Días · Soporte VIP 24/7', W / 2, 1378)

      const divGrad = ctx.createLinearGradient(200, 0, W - 200, 0)
      divGrad.addColorStop(0, 'rgba(56, 189, 248, 0)')
      divGrad.addColorStop(0.5, 'rgba(56, 189, 248, 0.6)')
      divGrad.addColorStop(1, 'rgba(56, 189, 248, 0)')
      ctx.strokeStyle = divGrad
      ctx.lineWidth = 1.5
      ctx.beginPath()
      ctx.moveTo(200, 1400)
      ctx.lineTo(W - 200, 1400)
      ctx.stroke()
      ctx.restore()

      // 6. Beneficios Oficiales Animados (calcula altura dinámica para evitar solapamientos)
      const bulletsY = 1424
      const bulletsUsedH = showReelBullets.value ? drawAnimatedFlyerBullets(ctx, 130, bulletsY, W - 260, th, t) : 0

      // 7. Botón de Acción WhatsApp Oficial Animado (ubicado de forma segura sin tapar nada)
      const waY = Math.min(1595, bulletsY + bulletsUsedH + 20)
      const waH = 96
      if (showContactPhone.value && effectiveContactPhone.value) {
        drawAnimatedWhatsAppPill(ctx, 140, waY, W - 280, waH, t)
      } else {
        drawCtaButton(ctx, 140, waY, W - 280, waH, t)
      }

    } else {
      // STREAMING / COMBOS OFICIAL STORY (1080 x 1920)
      const vx = 198
      const vy = 261
      const vw = 684
      const vh = 544
      const cornerR = 36

      ctx.save()
      roundRect(ctx, vx, vy, vw, vh, cornerR)
      ctx.clip()

      ctx.fillStyle = '#0f172a'
      ctx.fillRect(vx, vy, vw, vh)

      const img = loadedArtworkImage.value
      if (img && (img.width > 0 || img.naturalWidth > 0)) {
        drawAdjustedArtwork(ctx, img, vx, vy, vw, vh, t)
      } else {
        drawDirectProceduralCover(ctx, vx, vy, vw, vh, t)
      }
      ctx.restore()

      ctx.save()
      ctx.strokeStyle = 'rgba(56, 189, 248, 0.35)'
      ctx.lineWidth = 2
      roundRect(ctx, vx, vy, vw, vh, cornerR)
      ctx.stroke()
      ctx.restore()

      const badgeText = reelBadgeText.value || '🍿 PANTALLA ULTRA HD 4K · PERFIL PRIVADO'
      if (badgeText) {
        drawBadgePill(ctx, (W - 520) / 2, 835, 520, 56, badgeText, th.pillGrad)
      }

      ctx.save()
      ctx.textAlign = 'center'
      ctx.font = '900 46px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
      ctx.fillStyle = '#ffffff'
      ctx.shadowColor = 'rgba(0, 0, 0, 0.9)'
      ctx.shadowBlur = 12
      const titleLines = wrapReelsText(reelHeadline.value, 28).slice(0, 2)
      titleLines.forEach((tl, i) => ctx.fillText(tl, W / 2, 940 + i * 50))
      ctx.shadowBlur = 0
      const subY = 940 + titleLines.length * 50 + 8

      ctx.font = 'bold 24px sans-serif'
      ctx.fillStyle = th.accent
      const subStr = reelSubline.value.length > 48 ? reelSubline.value.slice(0, 46) + '...' : reelSubline.value
      ctx.fillText(subStr, W / 2, subY)
      ctx.restore()

      const pCardY = subY + 22
      drawPriceBox(ctx, 110, pCardY, 860, 190, th, t)

      const bulletsY = pCardY + 190 + 20
      const bulletsUsedH = showReelBullets.value ? drawAnimatedFlyerBullets(ctx, 130, bulletsY, 820, th, t) : 0

      const waY = bulletsY + bulletsUsedH + 20
      const waH = 96
      if (showContactPhone.value && effectiveContactPhone.value) {
        drawAnimatedWhatsAppPill(ctx, 140, waY, W - 280, waH, t)
      } else {
        drawCtaButton(ctx, 140, waY, W - 280, waH, t)
      }
    }
  }

  // ----------------------------------------------------
  // PLANTILLA 1: CYBER PRO OFICIAL (HUD Y MARCO PRO)
  // ----------------------------------------------------
  else if (layout === 'cyber_pro') {
    const cardX = 85
    const cardY = 350
    const cardW = W - 170
    const cardH = 780

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

    if (loadedArtworkImage.value && (loadedArtworkImage.value.width > 0 || loadedArtworkImage.value.naturalWidth > 0)) {
      ctx.save()
      roundRect(ctx, cardX + 6, cardY + 6, cardW - 12, cardH - 12, 38)
      ctx.clip()
      drawAdjustedArtwork(ctx, loadedArtworkImage.value, cardX + 6, cardY + 6, cardW - 12, cardH - 12, t)
      ctx.restore()
    } else {
      ctx.save()
      roundRect(ctx, cardX + 6, cardY + 6, cardW - 12, cardH - 12, 38)
      ctx.clip()
      drawDirectProceduralCover(ctx, cardX + 6, cardY + 6, cardW - 12, cardH - 12, t)
      ctx.restore()
    }

    if (reelBadgeText.value) {
      drawBadgePill(ctx, cardX + 30, cardY + 30, 360, 68, reelBadgeText.value, th.pillGrad)
    }

    if (currentSealText.value) {
      drawTrustSealPill(ctx, cardX + cardW - 440 - 24, cardY + cardH - 58 - 24, 440, 58, currentSealText.value, th.accent)
    }

    // Zona de Precios con proporciones refinadas estilo Flyer
    const hasPromoCoupon = Boolean(showPromoPill.value && reelPromoCode.value)
    const priceY = 1148
    const priceH = hasPromoCoupon ? 232 : 166
    drawPriceBox(ctx, cardX, priceY, cardW, priceH, th, t)

    // Beneficios Oficiales Estilo Flyer
    const bulletsY = priceY + priceH + 16
    const bulletsUsedH = showReelBullets.value ? drawAnimatedFlyerBullets(ctx, cardX, bulletsY, cardW, th, t) : 0

    // Barra / Botón de Acción Oficial de WhatsApp Animado (o CTA alternativo)
    const waY = bulletsY + bulletsUsedH + 20
    const waH = 96
    if (showContactPhone.value && effectiveContactPhone.value) {
      drawAnimatedWhatsAppPill(ctx, cardX, waY, cardW, waH, t)
    } else {
      drawCtaButton(ctx, cardX, waY, cardW, waH, t)
    }
  }

  // ----------------------------------------------------
  // PLANTILLA 2: PÓSTER CINEMÁTICO 9:16 (FULL-BLEED)
  // ----------------------------------------------------
  else if (layout === 'cinematic') {
    const cardX = 85
    const cardW = W - 170

    // Marquesina de cine personalizada
    ctx.save()
    ctx.fillStyle = th.accent
    ctx.font = '900 22px monospace'
    ctx.textAlign = 'center'
    ctx.fillText(cinematicStudioText.value || '★ ESTRENO EXCLUSIVO · STREAMEPRO STUDIOS ★', W / 2, 345)
    ctx.restore()

    if (reelBadgeText.value) {
      drawBadgePill(ctx, cardX + 20, 380, 360, 68, reelBadgeText.value, th.pillGrad)
    }

    if (!(loadedArtworkImage.value && (loadedArtworkImage.value.width > 0 || loadedArtworkImage.value.naturalWidth > 0))) {
      ctx.save()
      roundRect(ctx, cardX, 370, cardW, 700, 44)
      ctx.clip()
      drawDirectProceduralCover(ctx, cardX, 370, cardW, 700, t)
      ctx.restore()
    }

    // Placa de Precio Glassmorphism Translúcida Flotante
    ctx.save()
    const hasPromoCoupon = Boolean(showPromoPill.value && reelPromoCode.value)
    const glassY = 1105
    const glassH = hasPromoCoupon ? 232 : 166
    const op = (cinematicGlassOpacity.value || 88) / 100
    ctx.fillStyle = `rgba(2, 6, 23, ${op})`
    roundRect(ctx, cardX, glassY, cardW, glassH, 44)
    ctx.fill()
    ctx.strokeStyle = th.accent
    ctx.lineWidth = 3
    ctx.stroke()

    drawPriceContent(ctx, glassY, cardW, th, t)
    ctx.restore()

    if (currentSealText.value) {
      drawTrustSealPill(ctx, (W - 480) / 2, glassY - 56, 480, 48, currentSealText.value, th.accent)
    }

    const bulletsY = glassY + glassH + 16
    const bulletsUsedH = showReelBullets.value ? drawAnimatedFlyerBullets(ctx, cardX, bulletsY, cardW, th, t) : 0

    const waY = bulletsY + bulletsUsedH + 20
    const waH = 96
    if (showContactPhone.value && effectiveContactPhone.value) {
      drawAnimatedWhatsAppPill(ctx, cardX, waY, cardW, waH, t)
    } else {
      drawCtaButton(ctx, cardX, waY, cardW, waH, t)
    }
  }

  // ----------------------------------------------------
  // PLANTILLA 3: GLASSMORPHISM VIP LUXURY
  // ----------------------------------------------------
  else if (layout === 'glass_vip') {
    const cardX = 85
    const cardY = 350
    const cardW = W - 170
    const cardH = 760

    ctx.save()
    ctx.shadowColor = th.glow
    ctx.shadowBlur = 40 + (glassBlurFactor.value || 25)
    ctx.fillStyle = `rgba(15, 23, 42, ${0.76 + ((glassBlurFactor.value || 25) / 180)})`
    roundRect(ctx, cardX, cardY, cardW, cardH, 48)
    ctx.fill()
    ctx.strokeStyle = 'rgba(255, 255, 255, 0.28)'
    ctx.lineWidth = 4
    roundRect(ctx, cardX, cardY, cardW, cardH, 48)
    ctx.stroke()
    ctx.restore()

    if (loadedArtworkImage.value && (loadedArtworkImage.value.width > 0 || loadedArtworkImage.value.naturalWidth > 0)) {
      ctx.save()
      roundRect(ctx, cardX + 8, cardY + 8, cardW - 16, cardH - 16, 40)
      ctx.clip()
      drawAdjustedArtwork(ctx, loadedArtworkImage.value, cardX + 8, cardY + 8, cardW - 16, cardH - 16, t)
      ctx.restore()
    } else {
      ctx.save()
      roundRect(ctx, cardX + 8, cardY + 8, cardW - 16, cardH - 16, 40)
      ctx.clip()
      drawDirectProceduralCover(ctx, cardX + 8, cardY + 8, cardW - 16, cardH - 16, t)
      ctx.restore()
    }

    // Cinta VIP Superior Personalizada
    ctx.save()
    const vipW = 400
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
    ctx.fillText(vipRibbonText.value || '👑 EDICIÓN VIP OFICIAL', W / 2, vy + 38)
    ctx.restore()

    if (reelBadgeText.value) {
      drawBadgePill(ctx, cardX + 30, cardY + 50, 360, 68, reelBadgeText.value, th.pillGrad)
    }

    if (currentSealText.value) {
      drawTrustSealPill(ctx, cardX + cardW - 440 - 24, cardY + cardH - 58 - 24, 440, 58, currentSealText.value, th.accent)
    }

    const hasPromoCoupon = Boolean(showPromoPill.value && reelPromoCode.value)
    const priceY = 1140
    const priceH = hasPromoCoupon ? 232 : 166
    drawPriceBox(ctx, cardX, priceY, cardW, priceH, th, t)

    const bulletsY = priceY + priceH + 16
    const bulletsUsedH = showReelBullets.value ? drawAnimatedFlyerBullets(ctx, cardX, bulletsY, cardW, th, t) : 0

    const waY = bulletsY + bulletsUsedH + 20
    const waH = 96
    if (showContactPhone.value && effectiveContactPhone.value) {
      drawAnimatedWhatsAppPill(ctx, cardX, waY, cardW, waH, t)
    } else {
      drawCtaButton(ctx, cardX, waY, cardW, waH, t)
    }
  }

  // ----------------------------------------------------
  // PLANTILLA 4: SPLIT FLASH / URGENCIA RELÁMPAGO (50/50)
  // ----------------------------------------------------
  else if (layout === 'split_flash') {
    const cardX = 85
    const cardY = 340
    const cardW = W - 170
    const cardH = 620

    ctx.save()
    ctx.fillStyle = '#050a18'
    if (electricPulse.value && isAnimated.value) {
      ctx.shadowColor = '#ef4444'
      ctx.shadowBlur = 18 + Math.sin(t * 8) * 8
    }
    roundRect(ctx, cardX, cardY, cardW, cardH, 40)
    ctx.fill()
    ctx.strokeStyle = '#ef4444'
    ctx.lineWidth = electricPulse.value && isAnimated.value ? (3 + Math.sin(t * 8) * 1.5) : 4
    roundRect(ctx, cardX, cardY, cardW, cardH, 40)
    ctx.stroke()
    ctx.restore()

    if (loadedArtworkImage.value && (loadedArtworkImage.value.width > 0 || loadedArtworkImage.value.naturalWidth > 0)) {
      ctx.save()
      roundRect(ctx, cardX + 6, cardY + 6, cardW - 12, cardH - 12, 34)
      ctx.clip()
      drawAdjustedArtwork(ctx, loadedArtworkImage.value, cardX + 6, cardY + 6, cardW - 12, cardH - 12, t)
      ctx.restore()
    } else {
      ctx.save()
      roundRect(ctx, cardX + 6, cardY + 6, cardW - 12, cardH - 12, 34)
      ctx.clip()
      drawDirectProceduralCover(ctx, cardX + 6, cardY + 6, cardW - 12, cardH - 12, t)
      ctx.restore()
    }

    // Tira Neón Divisora Relámpago con Cuenta Regresiva
    ctx.save()
    const stripY = cardY + cardH + 14
    const stripH = 58
    const sGrad = ctx.createLinearGradient(cardX, stripY, cardX + cardW, stripY)
    sGrad.addColorStop(0, '#ef4444')
    sGrad.addColorStop(0.5, '#f97316')
    sGrad.addColorStop(1, '#ef4444')
    ctx.fillStyle = sGrad
    if (electricPulse.value && isAnimated.value) {
      ctx.shadowColor = '#f97316'
      ctx.shadowBlur = 14 + Math.sin(t * 8) * 6
    }
    roundRect(ctx, cardX, stripY, cardW, stripH, 30)
    ctx.fill()

    const maxSec = (countdownHours.value || 6) * 3600
    const baseSec = maxSec - Math.floor((t * 2) % maxSec)
    const hrs = String(Math.floor(baseSec / 3600)).padStart(2, '0')
    const mins = String(Math.floor((baseSec % 3600) / 60)).padStart(2, '0')
    const secs = String(baseSec % 60).padStart(2, '0')

    ctx.fillStyle = '#ffffff'
    ctx.font = '900 24px monospace'
    ctx.textAlign = 'center'
    ctx.fillText(`${flashBannerText.value} · ${hrs}:${mins}:${secs}`, W / 2, stripY + 38)
    ctx.restore()

    // Panel Inferior de Conversión Urgente
    const hasPromoCoupon = Boolean(showPromoPill.value && reelPromoCode.value)
    const lowerY = stripY + stripH + 16
    const lowerH = hasPromoCoupon ? 232 : 166
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

    const bulletsY = lowerY + lowerH + 16
    const bulletsUsedH = showReelBullets.value ? drawAnimatedFlyerBullets(ctx, cardX, bulletsY, cardW, th, t) : 0

    const waY = bulletsY + bulletsUsedH + 20
    const waH = 96
    if (showContactPhone.value && effectiveContactPhone.value) {
      drawAnimatedWhatsAppPill(ctx, cardX, waY, cardW, waH, t)
    } else {
      drawCtaButton(ctx, cardX, waY, cardW, waH, t)
    }
  }

  // ==========================================
  // 3. PIE DE PÁGINA Y CONTACTO DE CIERRE
  // ==========================================
  ctx.save()

  // Enlace Web Oficial / Referido con estilo pill translúcido de alta gama
  const domainText = props.resellerRef && props.resellerRef !== 'DIRECT'
    ? `${props.websiteDomain || 'streamepro.me'}/ref/${props.resellerRef}`
    : `${props.websiteDomain || 'streamepro.me'}`
  const domW = 460
  const domH = 44
  const domX = (W - domW) / 2
  const domY = 1718

  ctx.fillStyle = 'rgba(15, 23, 42, 0.90)'
  roundRect(ctx, domX, domY, domW, domH, 22)
  ctx.fill()
  ctx.strokeStyle = th.accent
  ctx.lineWidth = 1.5
  roundRect(ctx, domX, domY, domW, domH, 22)
  ctx.stroke()

  ctx.fillStyle = '#ffffff'
  ctx.font = 'bold 22px monospace'
  ctx.textAlign = 'center'
  ctx.shadowColor = 'rgba(0, 0, 0, 0.75)'
  ctx.shadowBlur = 6
  ctx.fillText(`🔗 ${domainText}`, W / 2, domY + 30)

  // Sello de Garantía y Confianza
  ctx.fillStyle = 'rgba(148, 163, 184, 0.90)'
  ctx.font = 'bold 19px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
  ctx.textAlign = 'center'
  ctx.fillText('Activación guiada · Cuentas oficiales garantizadas · StreamePro', W / 2, 1782)
  ctx.restore()

  // Guías de Zonas Seguras de TikTok / Reels / WhatsApp (Solo en Vista Previa)
  drawSafeZonesGuide(ctx, W, H)
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

// Icono vectorial oficial de WhatsApp (100% nítido, sin depender de imágenes de red)
function drawVectorWhatsAppIcon(ctx, cx, cy, size = 68) {
  ctx.save()
  const r = size / 2

  // 1. Círculo blanco de base para contraste perfecto
  ctx.fillStyle = '#ffffff'
  ctx.shadowColor = 'rgba(0, 0, 0, 0.40)'
  ctx.shadowBlur = 10
  ctx.beginPath()
  ctx.arc(cx, cy, r, 0, Math.PI * 2)
  ctx.fill()

  // 2. Círculo interior verde oficial
  ctx.fillStyle = '#25d366'
  ctx.beginPath()
  ctx.arc(cx, cy, r - 3.5, 0, Math.PI * 2)
  ctx.fill()

  // 3. Pequeña colita de burbuja de diálogo abajo a la izquierda
  ctx.beginPath()
  ctx.moveTo(cx - r * 0.45, cy + r * 0.55)
  ctx.lineTo(cx - r * 0.82, cy + r * 0.95)
  ctx.lineTo(cx - r * 0.12, cy + r * 0.72)
  ctx.fill()

  // 4. Auricular de teléfono clásico en blanco
  ctx.save()
  ctx.translate(cx, cy)
  ctx.rotate(-0.15)
  ctx.fillStyle = '#ffffff'

  const hr = size * 0.32
  ctx.beginPath()
  ctx.arc(-hr * 0.45, -hr * 0.45, hr * 0.28, 0, Math.PI * 2)
  ctx.arc(hr * 0.45, hr * 0.45, hr * 0.28, 0, Math.PI * 2)
  ctx.fill()

  ctx.lineWidth = hr * 0.48
  ctx.lineCap = 'round'
  ctx.strokeStyle = '#ffffff'
  ctx.beginPath()
  ctx.arc(0, 0, hr * 0.52, Math.PI * 0.25, Math.PI * 0.82)
  ctx.stroke()
  ctx.restore()

  ctx.restore()
}

// Barra / Píldora de Acción Oficial de WhatsApp Animada de Alto Impacto (30 FPS)
function drawAnimatedWhatsAppPill(ctx, pillX, pillY, pillW, pillH = 110, t = 0) {
  const phone = effectiveContactPhone.value || props.contactPhone || '+54 9 11 4050-6070'
  const cx = pillX + pillW / 2
  const cy = pillY + pillH / 2

  ctx.save()

  // 1. Pulso suave de respiración en tiempo real (30 FPS)
  const pulse = isAnimated.value ? (1.0 + Math.sin(t * 3.6) * 0.02) : 1.0
  ctx.translate(cx, cy)
  ctx.scale(pulse, pulse)
  ctx.translate(-cx, -cy)

  // 2. Resplandor exterior Neón Esmeralda pulsante
  const glowAlpha = isAnimated.value ? (0.42 + Math.sin(t * 3.6) * 0.22) : 0.42
  ctx.shadowColor = `rgba(37, 211, 102, ${glowAlpha})`
  ctx.shadowBlur = isAnimated.value ? (28 + Math.sin(t * 3.6) * 12) : 28

  // 3. Fondo según estilo seleccionado
  if (whatsappStyle.value === 'official_emerald') {
    const waGrad = ctx.createLinearGradient(pillX, pillY, pillX + pillW, pillY + pillH)
    waGrad.addColorStop(0, '#25d366') // Verde oficial WhatsApp
    waGrad.addColorStop(0.55, '#1fb855')
    waGrad.addColorStop(1, '#128c7e') // Teal profundo WhatsApp
    ctx.fillStyle = waGrad
  } else if (whatsappStyle.value === 'glass_luxury') {
    const waGrad = ctx.createLinearGradient(pillX, pillY, pillX + pillW, pillY)
    waGrad.addColorStop(0, 'rgba(15, 23, 42, 0.96)')
    waGrad.addColorStop(0.5, 'rgba(6, 78, 59, 0.92)')
    waGrad.addColorStop(1, 'rgba(15, 23, 42, 0.96)')
    ctx.fillStyle = waGrad
  } else {
    // cyber_neon
    const waGrad = ctx.createLinearGradient(pillX, pillY, pillX + pillW, pillY)
    waGrad.addColorStop(0, '#020617')
    waGrad.addColorStop(0.5, '#064e3b')
    waGrad.addColorStop(1, '#020617')
    ctx.fillStyle = waGrad
  }

  roundRect(ctx, pillX, pillY, pillW, pillH, 36)
  ctx.fill()

  // Borde nítido
  ctx.strokeStyle = whatsappStyle.value === 'official_emerald' ? 'rgba(255, 255, 255, 0.45)' : '#25d366'
  ctx.lineWidth = 3
  roundRect(ctx, pillX, pillY, pillW, pillH, 36)
  ctx.stroke()

  // 4. Haz de brillo Shimmer que recorre la píldora horizontalmente
  if (isAnimated.value) {
    ctx.save()
    roundRect(ctx, pillX, pillY, pillW, pillH, 36)
    ctx.clip()
    const shimX = pillX + ((t * 420) % (pillW + 360)) - 180
    const shimGrad = ctx.createLinearGradient(shimX, pillY, shimX + 110, pillY)
    shimGrad.addColorStop(0, 'rgba(255, 255, 255, 0)')
    shimGrad.addColorStop(0.5, 'rgba(255, 255, 255, 0.40)')
    shimGrad.addColorStop(1, 'rgba(255, 255, 255, 0)')
    ctx.fillStyle = shimGrad
    ctx.fillRect(shimX, pillY, 110, pillH)
    ctx.restore()
  }

  // 5. Icono Oficial Vectorial de WhatsApp con Ondas de Radar
  const iconSize = 72
  const iconX = pillX + 54
  const iconY = cy

  if (isAnimated.value) {
    const waveProgress = (t * 1.2) % 1.0
    const waveR = (iconSize / 2) + waveProgress * 26
    const waveAlpha = (1.0 - waveProgress) * 0.55
    ctx.save()
    ctx.strokeStyle = `rgba(255, 255, 255, ${waveAlpha})`
    ctx.lineWidth = 2.5
    ctx.beginPath()
    ctx.arc(iconX, iconY, waveR, 0, Math.PI * 2)
    ctx.stroke()
    ctx.restore()
  }

  drawVectorWhatsAppIcon(ctx, iconX, iconY, iconSize)

  // 6. Textos: Micro-etiqueta superior y Número en Grande
  ctx.save()
  const textStartX = iconX + iconSize / 2 + 28
  const actionLabel = (whatsappActionText.value || 'PEDIDOS & CONSULTAS WHATSAPP').toUpperCase()

  // Micro-etiqueta
  ctx.textAlign = 'left'
  ctx.fillStyle = whatsappStyle.value === 'official_emerald' ? '#f0fdf4' : '#86efac'
  ctx.font = '900 19px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
  ctx.shadowColor = 'rgba(0, 0, 0, 0.70)'
  ctx.shadowBlur = 8
  ctx.fillText(actionLabel, textStartX, cy - 12)

  // Número destacado
  ctx.fillStyle = '#ffffff'
  ctx.font = '900 38px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, monospace'
  ctx.shadowColor = 'rgba(0, 0, 0, 0.92)'
  ctx.shadowBlur = 16
  ctx.shadowOffsetY = 3
  ctx.fillText(phone, textStartX, cy + 30)

  // Flecha o Símbolo CTA a la derecha con animación
  const rightArrowX = pillX + pillW - 46
  ctx.fillStyle = '#ffffff'
  ctx.font = '900 34px sans-serif'
  ctx.textAlign = 'center'
  const arrowBounce = isAnimated.value ? Math.sin(t * 6) * 4 : 0
  ctx.fillText('➔', rightArrowX + arrowBounce, cy + 12)

  ctx.restore()
  ctx.restore()
}

// Dibuja los beneficios y garantías oficiales estilo Flyer con animación a 30 FPS
function drawAnimatedFlyerBullets(ctx, startX, startY, totalW, th, t = 0) {
  const b1 = (reelBullet1.value || '').trim()
  const b2 = (reelBullet2.value || '').trim()
  const b3 = (reelBullet3.value || '').trim()
  const bullets = [b1, b2, b3].filter(Boolean)
  if (bullets.length === 0) return 0

  ctx.save()
  const isStacked = bullets.length > 2 || bullets.some(b => b.length > 24)

  if (!isStacked && bullets.length === 2) {
    // Modo Horizontal: 2 Píldoras lado a lado
    const gap = 16
    const pillW = (totalW - gap) / 2
    const pillH = 48
    bullets.forEach((b, idx) => {
      const px = startX + idx * (pillW + gap)
      const py = startY

      // Fondo cápsula translúcida
      ctx.fillStyle = 'rgba(15, 23, 42, 0.85)'
      roundRect(ctx, px, py, pillW, pillH, 24)
      ctx.fill()
      ctx.strokeStyle = 'rgba(255, 255, 255, 0.18)'
      ctx.lineWidth = 1.5
      roundRect(ctx, px, py, pillW, pillH, 24)
      ctx.stroke()

      // Icono de checkmark animado
      const pulse = isAnimated.value ? (1.0 + Math.sin(t * 4 + idx * 1.5) * 0.08) : 1.0
      const cx = px + 26
      const cy = py + pillH / 2

      ctx.save()
      ctx.translate(cx, cy)
      ctx.scale(pulse, pulse)
      ctx.fillStyle = th.bulletColor || '#22c55e'
      ctx.shadowColor = th.glow || '#22c55e'
      ctx.shadowBlur = isAnimated.value ? 10 : 4
      ctx.beginPath()
      ctx.arc(0, 0, 12, 0, Math.PI * 2)
      ctx.fill()
      ctx.fillStyle = '#020617'
      ctx.font = '900 14px sans-serif'
      ctx.textAlign = 'center'
      ctx.fillText('✓', 0, 5)
      ctx.restore()

      // Texto
      ctx.fillStyle = '#f1f5f9'
      ctx.font = 'bold 20px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
      ctx.textAlign = 'left'
      ctx.shadowColor = 'rgba(0, 0, 0, 0.90)'
      ctx.shadowBlur = 6
      const maxLen = 25
      const clipText = b.length > maxLen ? b.slice(0, maxLen - 2) + '...' : b
      ctx.fillText(clipText, px + 48, py + 31)
    })
    ctx.restore()
    return pillH
  } else {
    // Modo Vertical: Hasta 3 filas limpias con viñeta verde y texto nítido
    const rowH = 38
    bullets.forEach((b, idx) => {
      const py = startY + idx * rowH
      const pulse = isAnimated.value ? (1.0 + Math.sin(t * 3.5 + idx * 1.5) * 0.06) : 1.0
      const cx = startX + 22
      const cy = py + 14

      // Checkmark circular pulsante
      ctx.save()
      ctx.translate(cx, cy)
      ctx.scale(pulse, pulse)
      ctx.fillStyle = th.bulletColor || '#22c55e'
      ctx.shadowColor = th.glow || '#22c55e'
      ctx.shadowBlur = isAnimated.value ? 10 : 4
      ctx.beginPath()
      ctx.arc(0, 0, 12, 0, Math.PI * 2)
      ctx.fill()
      ctx.fillStyle = '#020617'
      ctx.font = '900 14px sans-serif'
      ctx.textAlign = 'center'
      ctx.fillText('✓', 0, 5)
      ctx.restore()

      // Texto de beneficio
      ctx.fillStyle = '#f8fafc'
      ctx.font = '600 22px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
      ctx.textAlign = 'left'
      ctx.shadowColor = 'rgba(0, 0, 0, 0.92)'
      ctx.shadowBlur = 6
      const maxLen = 42
      const clipText = b.length > maxLen ? b.slice(0, maxLen - 2) + '...' : b
      ctx.fillText(clipText, startX + 46, py + 22)
    })
    ctx.restore()
    return bullets.length * rowH
  }
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

  // 1. Halo radial de energía detrás del precio promocional (animado a 30 FPS como en Flyer)
  if (isAnimated.value) {
    ctx.save()
    const haloR = 260 + Math.sin(t * 3.2) * 25
    const haloGrad = ctx.createRadialGradient(W / 2, boxY + 115, 20, W / 2, boxY + 115, haloR)
    haloGrad.addColorStop(0, th.glow || 'rgba(74, 222, 128, 0.22)')
    haloGrad.addColorStop(1, 'rgba(0, 0, 0, 0)')
    ctx.fillStyle = haloGrad
    ctx.fillRect(W / 2 - haloR, boxY + 115 - haloR, haloR * 2, haloR * 2)
    ctx.restore()
  }

  // 2. Precio regular tachado con porcentaje de descuento
  if (showRegularPrice.value && Number(reelRegularPrice.value) > 0 && Number(reelRegularPrice.value) > Number(reelPromoPrice.value)) {
    ctx.fillStyle = '#94a3b8'
    ctx.font = 'bold 28px sans-serif'
    ctx.textAlign = 'center'
    const discPct = Math.round((1 - (Number(reelPromoPrice.value) / Number(reelRegularPrice.value))) * 100)
    const regText = `Antes: $${Number(reelRegularPrice.value).toLocaleString('es-AR')}${discPct > 0 ? ` (-${discPct}% OFF)` : ''}`
    ctx.fillText(regText, W / 2, boxY + 46)

    const tw = ctx.measureText(regText).width
    ctx.strokeStyle = '#f43f5e'
    ctx.lineWidth = 3
    ctx.beginPath()
    ctx.moveTo(W / 2 - tw / 2 - 12, boxY + 36)
    ctx.lineTo(W / 2 + tw / 2 + 12, boxY + 36)
    ctx.stroke()
  }

  // 3. Precio Promocional Gigante con pulso neón
  if (Number(reelPromoPrice.value) > 0) {
    ctx.fillStyle = '#4ade80'
    ctx.font = '900 82px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
    ctx.textAlign = 'center'
    ctx.shadowColor = isAnimated.value ? `rgba(74, 222, 128, ${0.45 + Math.sin(t * 3.5) * 0.2})` : 'rgba(74, 222, 128, 0.45)'
    ctx.shadowBlur = isAnimated.value ? (24 + Math.sin(t * 3.5) * 10) : 24
    ctx.fillText(`$${Number(reelPromoPrice.value).toLocaleString('es-AR')} ARS`, W / 2, boxY + 132)
    ctx.shadowBlur = 0
  } else {
    ctx.fillStyle = '#fde047'
    ctx.font = '900 76px -apple-system, sans-serif'
    ctx.textAlign = 'center'
    ctx.shadowColor = 'rgba(253, 224, 71, 0.5)'
    ctx.shadowBlur = 24
    ctx.fillText('¡SORTEO GRATIS!', W / 2, boxY + 132)
    ctx.shadowBlur = 0
  }

  // 4. Pill del Cupón Promocional con Destello Animado
  if (showPromoPill.value && reelPromoCode.value) {
    ctx.save()
    const cpW = boxW - 60
    const cpH = 62
    const cpX = (W - cpW) / 2
    const cpY = boxY + 158

    const cpGrad = ctx.createLinearGradient(cpX, cpY, cpX + cpW, cpY)
    cpGrad.addColorStop(0, '#f59e0b')
    cpGrad.addColorStop(1, '#d97706')
    ctx.fillStyle = cpGrad
    roundRect(ctx, cpX, cpY, cpW, cpH, 31)
    ctx.fill()

    if (isAnimated.value) {
      const shimmerX = cpX + ((t * 420) % (cpW + 240)) - 120
      const shimGrad = ctx.createLinearGradient(shimmerX, cpY, shimmerX + 90, cpY)
      shimGrad.addColorStop(0, 'rgba(255,255,255,0)')
      shimGrad.addColorStop(0.5, 'rgba(255,255,255,0.48)')
      shimGrad.addColorStop(1, 'rgba(255,255,255,0)')
      ctx.fillStyle = shimGrad
      roundRect(ctx, cpX, cpY, cpW, cpH, 31)
      ctx.fill()
    }

    ctx.fillStyle = '#020617'
    ctx.font = '900 24px -apple-system, sans-serif'
    ctx.textAlign = 'center'
    ctx.fillText(`🎟️ USA EL CÓDIGO: ${reelPromoCode.value.toUpperCase()} (DESCUENTO EXTRA)`, W / 2, cpY + 39)
    ctx.restore()
  }
}

function drawCtaButton(ctx, ctaX, ctaY, ctaW, ctaH = 100, t = 0) {
  const W = 1080
  const width = ctaW || (W - 170)
  ctx.save()
  const ctaPulse = isAnimated.value ? (1.0 + Math.sin(t * 3.6) * 0.02) : 1.0

  ctx.translate(W / 2, ctaY + ctaH / 2)
  ctx.scale(ctaPulse, ctaPulse)
  ctx.translate(-W / 2, -(ctaY + ctaH / 2))

  const ctaGrad = ctx.createLinearGradient(ctaX, ctaY, ctaX + width, ctaY)
  ctaGrad.addColorStop(0, '#ec4899')
  ctaGrad.addColorStop(0.5, '#a855f7')
  ctaGrad.addColorStop(1, '#6366f1')
  ctx.fillStyle = ctaGrad
  ctx.shadowColor = 'rgba(236, 72, 153, 0.5)'
  ctx.shadowBlur = 32
  roundRect(ctx, ctaX, ctaY, width, ctaH, 50)
  ctx.fill()

  ctx.fillStyle = '#ffffff'
  ctx.font = '900 36px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
  ctx.textAlign = 'center'
  ctx.shadowColor = 'rgba(0,0,0,0.5)'
  ctx.shadowBlur = 12
  ctx.fillText(reelCtaText.value || '📲 PEDILO AL WHATSAPP', W / 2, ctaY + 62)
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

  // Garantizar que el bucle de animación esté activo para alimentar frames frescos a 30 FPS
  isDrawingForExport.value = true
  isAnimated.value = true
  isRecording.value = true
  recordingProgress.value = 0
  recordingSeconds.value = '0.0'
  startAnimationLoop()

  try {
    const stream = captureStreamFn.call(canvas, 30)
    
    // Priorizar MP4 nativo si está disponible en el navegador; si no, WebM
    const candidates = [
      'video/mp4;codecs=avc1',
      'video/mp4;codecs=h264',
      'video/mp4',
      'video/webm;codecs=vp9',
      'video/webm;codecs=vp8',
      'video/webm'
    ]
    let mimeType = 'video/webm'
    for (const cand of candidates) {
      if (typeof MediaRecorder !== 'undefined' && MediaRecorder.isTypeSupported(cand)) {
        mimeType = cand
        break
      }
    }

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

    const durationSec = Number(selectedDurationSec.value || 8)
    const durationMs = durationSec * 1000
    const startTime = performance.now()

    if (recordingInterval) clearInterval(recordingInterval)
    recordingInterval = setInterval(() => {
      const elapsed = performance.now() - startTime
      recordingProgress.value = Math.min(100, Math.round((elapsed / durationMs) * 100))
      recordingSeconds.value = (elapsed / 1000).toFixed(1)
    }, 100)

    recorder.onstop = async () => {
      try {
        if (recordingInterval) clearInterval(recordingInterval)
        const ext = mimeType.includes('mp4') ? 'mp4' : 'webm'
        const rawBlob = new Blob(chunks, { type: mimeType })

        // Solución al bug de 00.000001ms: Inyectar duración exacta en la cabecera EBML de WebM
        const finalBlob = mimeType.includes('webm')
          ? await fixWebmDurationBlob(rawBlob, durationMs)
          : rawBlob

        const cleanTitle = reelHeadline.value.toLowerCase().replace(/[^a-z0-9]/g, '_').slice(0, 20) || 'reel_9x16'
        const fileName = `reel_streamepro_${cleanTitle}_9x16.${ext}`
        const url = URL.createObjectURL(finalBlob)

        mobileSaveMediaType.value = 'video'
        mobileSaveVideoUrl.value = url
        mobileSaveFileName.value = fileName
        mobileSaveVideoBlob.value = finalBlob

        // Si está en móvil, abrir el modal táctil para previsualización inmediata y guardado/compartido seguro
        if (isMobileDevice.value) {
          showMobileSaveModal.value = true
          toast.add(`🎬 ¡Video Reel listo (${durationSec}s)! Puedes verlo, guardarlo o compartirlo.`, 'success')
          return
        }

        // Descarga directa habitual para PC
        const a = document.createElement('a')
        a.href = url
        a.download = fileName
        document.body.appendChild(a)
        a.click()
        document.body.removeChild(a)
        setTimeout(() => URL.revokeObjectURL(url), 15000)

        toast.add(`🎬 ¡Video Reel 9:16 generado (${ext.toUpperCase()} · ${durationSec}s)! Listo para TikTok, Reels o Estados.`, 'success')
      } catch (err) {
        console.error('Error procesando archivo de video:', err)
        toast.add('Ocurrió un error al empaquetar el video. Descargando imagen HD...', 'info')
        downloadImagePng()
      } finally {
        isDrawingForExport.value = false
        isRecording.value = false
        recordingProgress.value = 0
        recordingSeconds.value = '0.0'
        requestCanvasRedraw()
      }
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
    isDrawingForExport.value = false
    isRecording.value = false
    if (recordingInterval) clearInterval(recordingInterval)
    toast.add('No se pudo grabar video en este navegador. Usa "Descargar PNG".', 'error')
  }
}

// 2. Descargar Video desde el Modal de Guardado Móvil
function downloadVideoFromModal() {
  if (!mobileSaveVideoUrl.value) return
  const a = document.createElement('a')
  a.href = mobileSaveVideoUrl.value
  a.download = mobileSaveFileName.value || 'reel_streamepro_9x16.webm'
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  toast.add('🎬 Descargando video a tu dispositivo...', 'success')
}

// 3. Descargar Imagen PNG 1080x1920 (PC y Móvil)
async function downloadImagePng() {
  const canvas = canvasRef.value
  if (!canvas || isExportingImage.value) return
  isExportingImage.value = true

  try {
    isDrawingForExport.value = true
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

    const cleanTitle = reelHeadline.value.toLowerCase().replace(/[^a-z0-9]/g, '_').slice(0, 20) || 'flyer_9x16'
    const fileName = `estado_streamepro_${cleanTitle}_9x16.png`

    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = fileName
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    setTimeout(() => URL.revokeObjectURL(url), 10000)

    toast.add('📸 ¡Flyer HD 9:16 descargado con éxito!', 'success')
  } catch (err) {
    console.error('Error exportando PNG:', err)
    if (err.name === 'SecurityError' || String(err).includes('Tainted')) {
      toast.add('Sincronizando carátula segura con protección CORS...', 'info')
      const safeDataUrl = createProceduralCover(reelHeadline.value || 'StreamePro', reelSubline.value || 'Edición Oficial', '⚡')
      await loadArtwork(safeDataUrl)
      setTimeout(() => downloadImagePng(), 200)
    } else {
      toast.add('Error al exportar imagen en este dispositivo.', 'error')
    }
  } finally {
    isDrawingForExport.value = false
    isExportingImage.value = false
    requestCanvasRedraw()
  }
}

// 4. Acción Adaptada Móvil: Compartir o Abrir Visor Táctil
async function handleMobileOrShareAction() {
  const canvas = canvasRef.value
  if (!canvas) return

  mobileSaveMediaType.value = 'image'
  isDrawingForExport.value = true
  renderFrame(performance.now() - animationStartTime)
  try {
    const dataUrl = canvas.toDataURL('image/png', 0.95)
    mobileSaveImageUrl.value = dataUrl
  } catch (err) {
    if (err.name === 'SecurityError' || String(err).includes('Tainted')) {
      const safeDataUrl = createProceduralCover(reelHeadline.value || 'StreamePro', reelSubline.value || 'Edición Oficial', '⚡')
      await loadArtwork(safeDataUrl)
      isDrawingForExport.value = false
      return handleMobileOrShareAction()
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
          isDrawingForExport.value = false
          return
        }
      }
    } catch (err) {
      if (err.name === 'AbortError') {
        isDrawingForExport.value = false
        return
      }
    }
  }

  isDrawingForExport.value = false
  // Fallback / Experiencia móvil dedicada: Abrir Visor de Guardado Táctil
  showMobileSaveModal.value = true
}

async function shareDirect() {
  // Si el modal está abierto para video, compartir el archivo de video
  if (mobileSaveMediaType.value === 'video' && mobileSaveVideoBlob.value) {
    if (typeof navigator !== 'undefined' && navigator.share && navigator.canShare) {
      try {
        const file = new File([mobileSaveVideoBlob.value], mobileSaveFileName.value || 'reel_streamepro_9x16.webm', {
          type: mobileSaveVideoBlob.value.type || 'video/webm'
        })
        if (navigator.canShare({ files: [file] })) {
          await navigator.share({
            files: [file],
            title: reelHeadline.value,
            text: currentReelPackage.value?.caption || 'Promoción oficial StreamePro'
          })
          toast.add('🎬 ¡Video Reel compartido con éxito!', 'success')
          return
        }
      } catch (shareErr) {
        if (shareErr.name === 'AbortError') return
      }
    }
    downloadVideoFromModal()
    return
  }

  const canvas = canvasRef.value
  if (!canvas) return
  try {
    isDrawingForExport.value = true
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
        isDrawingForExport.value = false
        return
      }
    }
    isDrawingForExport.value = false
    downloadImagePng()
  } catch (err) {
    isDrawingForExport.value = false
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
    isDrawingForExport.value = true
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
  } finally {
    isDrawingForExport.value = false
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
  const el = canvas.parentElement || canvas
  if (!document.fullscreenElement) {
    el.requestFullscreen?.().catch(() => {
      canvas.requestFullscreen?.().catch(() => {})
    })
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
  if (props.contactPhone) {
    customContactPhone.value = props.contactPhone
  }

  preloadFlyerTemplates()
  currentLayoutId.value = 'flyer_official'

  const p = props.initialPayload
  if (p) {
    if (p.itemType === 'gaming' || p.platform?.includes('PS') || p.effectivePrimaryPrice || p.category === 'Juegos Digitales' || p.gameId) {
      selectedMode.value = 'gaming'
      pickedGameId.value = p.id || p.gameId || ''
      const rawTitle = p.title || p.name || 'Videojuego'
      reelHeadline.value = cleanGameTitle(rawTitle).toUpperCase()
      if (p.license) pickedGamingLicense.value = p.license
      const isPri = pickedGamingLicense.value === 'primary'
      reelSubline.value = p.subtitle || (isPri ? 'Cuenta Primaria · Jugá desde tu usuario (PSN)' : 'Cuenta Secundaria · Activación Oficial')
      reelPromoPrice.value = p.price ? Number(p.price) : (isPri ? Number(p.effectivePrimaryPrice || 15000) : Number(p.effectiveSecondaryPrice || 9000))
      reelRegularPrice.value = p.originalPrice ? Number(p.originalPrice) : Math.round(reelPromoPrice.value * 1.3)
      reelBadgeText.value = p.badge || (isPri ? '👑 PS STORE OFICIAL' : '⚡ OFERTA ECONÓMICA')
      currentSealText.value = p.guaranteeText || (isPri ? '🎮 JUGÁ CON TU PERFIL PSN' : '⚡ ENTREGA INMEDIATA 15 MIN')
      reelBullet1.value = p.bullet1 || '⚡ Entrega Inmediata en 15 Minutos'
      reelBullet2.value = p.bullet2 || (isPri ? '🎮 Jugá desde tu usuario personal (PSN)' : '🎮 Cuenta Secundaria Oficial Anti-Candado')
      reelBullet3.value = p.bullet3 || '🛡️ Soporte VIP & Garantía 30 Días'
      if (p.phone) customContactPhone.value = p.phone
      if (p.theme) {
        if (['cyber_glow', 'blue', 'cyber'].includes(p.theme)) currentThemeId.value = 'cyber_glow'
        else if (['fire_sale', 'red', 'crimson'].includes(p.theme)) currentThemeId.value = 'fire_sale'
        else if (['gold_vip', 'gold', 'amber'].includes(p.theme)) currentThemeId.value = 'gold_vip'
        else if (['emerald_pro', 'green', 'emerald'].includes(p.theme)) currentThemeId.value = 'emerald_pro'
        else if (['purple_galaxy', 'purple', 'violet'].includes(p.theme)) currentThemeId.value = 'purple_galaxy'
      }
      if (p.zoom !== undefined) imageZoomFactor.value = Number(p.zoom)
      if (p.fitMode) imageFitMode.value = p.fitMode
      if (p.brightness !== undefined) imageBrightness.value = Number(p.brightness)
      if (p.contrast !== undefined) imageContrast.value = Number(p.contrast)
      if (p.saturation !== undefined) imageSaturation.value = Number(p.saturation)
      if (p.rotation !== undefined) imageRotation.value = Number(p.rotation)
      if (p.panX !== undefined) imageOffsetX.value = Number(p.panX)
      if (p.panY !== undefined) imageOffsetY.value = Number(p.panY)

      const coverUrl = p.image || p.cover || '/flyers/gaming-template-story.jpg'
      reelArtworkUrl.value = coverUrl
      defaultProductCoverUrl.value = coverUrl
      loadArtwork(coverUrl)
      if (adminStore.processedGamingProducts?.length) {
        applyGamingProduct(p)
      }
    } else if (p.services || (p.promoPrice && p.packages)) {
      selectedMode.value = 'combo'
      pickedComboId.value = p.id || ''
      if (p.title) reelHeadline.value = p.title.toUpperCase().slice(0, 34)
      if (p.subtitle) reelSubline.value = p.subtitle.slice(0, 48)
      else if (p.description) reelSubline.value = p.description.slice(0, 48)
      if (p.price || p.promoPrice) reelPromoPrice.value = Number(p.price || p.promoPrice)
      if (p.originalPrice || p.regularPrice) reelRegularPrice.value = Number(p.originalPrice || p.regularPrice)
      if (p.badge) reelBadgeText.value = p.badge
      if (p.phone) customContactPhone.value = p.phone
      if (p.bullet1) reelBullet1.value = p.bullet1
      if (p.bullet2) reelBullet2.value = p.bullet2
      if (p.bullet3) reelBullet3.value = p.bullet3
      if (p.image) {
        reelArtworkUrl.value = p.image
        defaultProductCoverUrl.value = p.image
        loadArtwork(p.image)
      } else {
        onComboChange()
      }
    } else if (p.prizeTitle || p.ticketsCount !== undefined) {
      selectedMode.value = 'giveaway'
      pickedGiveawayId.value = p.id || ''
      if (p.prizeTitle) reelHeadline.value = `SORTEO: ${p.prizeTitle.toUpperCase()}`
      if (p.phone) customContactPhone.value = p.phone
      onGiveawayChange()
    } else {
      selectedMode.value = 'streaming'
      pickedStreamingKey.value = p.platform || p.service || p.name || 'Netflix'
      if (p.title) reelHeadline.value = p.title.toUpperCase()
      if (p.subtitle) reelSubline.value = p.subtitle
      if (p.price) reelPromoPrice.value = Number(p.price)
      if (p.originalPrice) reelRegularPrice.value = Number(p.originalPrice)
      if (p.badge) reelBadgeText.value = p.badge
      if (p.phone) customContactPhone.value = p.phone
      if (p.bullet1) reelBullet1.value = p.bullet1
      if (p.bullet2) reelBullet2.value = p.bullet2
      if (p.bullet3) reelBullet3.value = p.bullet3
      if (p.theme) {
        if (['cyber_glow', 'blue', 'cyber'].includes(p.theme)) currentThemeId.value = 'cyber_glow'
        else if (['fire_sale', 'red', 'crimson'].includes(p.theme)) currentThemeId.value = 'fire_sale'
        else if (['gold_vip', 'gold', 'amber'].includes(p.theme)) currentThemeId.value = 'gold_vip'
        else if (['emerald_pro', 'green', 'emerald'].includes(p.theme)) currentThemeId.value = 'emerald_pro'
        else if (['purple_galaxy', 'purple', 'violet'].includes(p.theme)) currentThemeId.value = 'purple_galaxy'
      }
      if (p.image) {
        reelArtworkUrl.value = p.image
        defaultProductCoverUrl.value = p.image
        loadArtwork(p.image)
      } else {
        onStreamingServiceChange()
      }
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
    preloadFlyerTemplates()
    nextTick(() => {
      initFromProps()
    })
  } else {
    if (animFrameId) cancelAnimationFrame(animFrameId)
    if (recordingInterval) clearInterval(recordingInterval)
    if (recordingTimeout) clearTimeout(recordingTimeout)
  }
})

watch(() => props.contactPhone, (newPhone) => {
  if (newPhone && !customContactPhone.value) {
    customContactPhone.value = newPhone
  }
})

watch(() => props.promoCode, (newVal) => {
  if (newVal) reelPromoCode.value = normalizePromoCode(newVal)
})

onMounted(() => {
  preloadFlyerTemplates()
  if (!adminStore.isGamingCatalogLoaded && typeof adminStore.loadFullGamingCatalog === 'function') {
    adminStore.loadFullGamingCatalog().catch(() => {})
  }
  if (props.show) {
    nextTick(() => {
      initFromProps()
    })
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
@extends('layouts.app')

@section('title', 'Gestor de Atajos')
@section('page_title', 'Gestor de Atajos Rápidos')
@section('page_subtitle', 'Configura accesos directos a WhatsApp, canales de Telegram, herramientas de diagnóstico y cPanel')

@section('content')
<div class="space-y-6" x-data="{ newShortcutModal: false }">

    <!-- Top Action -->
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-base font-bold text-white">Atajos Configurables en el Dock Superior</h3>
            <p class="text-xs text-gray-400">Accede rápidamente a tus utilidades diarias con un solo clic.</p>
        </div>
        <button @click="newShortcutModal = true" class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-cyan-500 hover:opacity-95 text-white font-bold text-xs shadow-lg shadow-indigo-600/30 flex items-center gap-2 transition-all">
            <i data-lucide="plus" class="w-4 h-4"></i>
            <span>Nuevo Atajo Rápido</span>
        </button>
    </div>

    <!-- Shortcuts Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($shortcuts as $sc)
        <div class="bg-gray-900/80 border border-gray-800 rounded-2xl p-4 flex items-center justify-between hover:border-gray-700 transition-all shadow-md group">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white shrink-0 shadow-sm" style="background-color: {{ $sc->color }}20; border: 1px solid {{ $sc->color }}40">
                    <span class="w-3 h-3 rounded-full" style="background-color: {{ $sc->color }}"></span>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h4 class="font-bold text-sm text-white">{{ $sc->title }}</h4>
                        @if($sc->badge)
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                            {{ $sc->badge }}
                        </span>
                        @endif
                    </div>
                    <span class="text-[11px] text-gray-500">{{ $sc->category }}</span>
                    <a href="{{ $sc->url }}" target="_blank" class="text-[11px] text-indigo-400 hover:underline block truncate max-w-[200px]">
                        {{ $sc->url }}
                    </a>
                </div>
            </div>

            <div class="flex items-center gap-1">
                <a href="{{ $sc->url }}" target="_blank" class="p-2 rounded-lg bg-gray-800 hover:bg-gray-700 text-gray-300 hover:text-white" title="Abrir">
                    <i data-lucide="external-link" class="w-4 h-4"></i>
                </a>
                <form method="POST" action="{{ route('shortcuts.destroy', $sc) }}" onsubmit="return confirm('¿Eliminar este atajo?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="p-2 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400" title="Eliminar">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </button>
                </form>
            </div>
        </div>
        @empty
        <div class="col-span-full py-8 text-center text-gray-500 text-xs bg-gray-900/40 rounded-2xl border border-gray-800">
            No hay atajos configurados todavía.
        </div>
        @endforelse
    </div>

    <!-- Modal: Nuevo Atajo -->
    <div x-show="newShortcutModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
        <div @click.away="newShortcutModal = false" class="bg-gray-900 border border-gray-800 rounded-3xl p-6 w-full max-w-md shadow-2xl">
            <h3 class="text-base font-bold text-white mb-4">Agregar Nuevo Atajo Rápido</h3>

            <form method="POST" action="{{ route('shortcuts.store') }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-1">Título del Atajo *</label>
                    <input type="text" name="title" required placeholder="Ej: WhatsApp Directo Soporte"
                        class="w-full px-4 py-2 bg-gray-950 border border-gray-800 rounded-xl text-white text-xs focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-1">Enlace / URL *</label>
                    <input type="url" name="url" required placeholder="https://wa.me/54911..."
                        class="w-full px-4 py-2 bg-gray-950 border border-gray-800 rounded-xl text-white text-xs focus:ring-2 focus:ring-indigo-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-1">Categoría</label>
                        <select name="category" class="w-full px-3 py-2 bg-gray-950 border border-gray-800 rounded-xl text-white text-xs">
                            <option value="Comunicaciones">Comunicaciones</option>
                            <option value="Herramientas">Herramientas</option>
                            <option value="Servidor">Servidor / cPanel</option>
                            <option value="General">General</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-1">Color Distintivo</label>
                        <select name="color" class="w-full px-3 py-2 bg-gray-950 border border-gray-800 rounded-xl text-white text-xs">
                            <option value="#22c55e">Verde (WhatsApp)</option>
                            <option value="#0ea5e9">Azul Celeste (Telegram)</option>
                            <option value="#6366f1">Índigo (StreamPro)</option>
                            <option value="#f59e0b">Ámbar (Herramientas)</option>
                            <option value="#ef4444">Rojo (cPanel/Hosting)</option>
                            <option value="#8b5cf6">Violeta (DNS)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-1">Insignia / Badge (Opcional)</label>
                        <input type="text" name="badge" placeholder="Ej: VIP, 24/7"
                            class="w-full px-4 py-2 bg-gray-950 border border-gray-800 rounded-xl text-white text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-1">Ícono</label>
                        <input type="text" name="icon" value="link" placeholder="link"
                            class="w-full px-4 py-2 bg-gray-950 border border-gray-800 rounded-xl text-white text-xs">
                    </div>
                </div>

                <div class="pt-2 border-t border-gray-800 flex items-center justify-end gap-3">
                    <button type="button" @click="newShortcutModal = false" class="px-4 py-2 rounded-xl bg-gray-800 text-gray-300 text-xs font-semibold">
                        Cancelar
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold">
                        Guardar Atajo
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

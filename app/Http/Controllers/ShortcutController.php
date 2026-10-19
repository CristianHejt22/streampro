<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Shortcut;

class ShortcutController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $shortcuts = Shortcut::whereNull('user_id')
            ->orWhere('user_id', $user->id)
            ->orderBy('order_index')
            ->get();

        return view('shortcuts.index', compact('shortcuts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:100',
            'url' => 'required|url|max:255',
            'icon' => 'required|string|max:50',
            'category' => 'required|string|max:50',
            'color' => 'nullable|string|max:20',
            'badge' => 'nullable|string|max:30',
        ]);

        $user = Auth::user();

        Shortcut::create([
            'user_id' => $user->isAdmin() ? null : $user->id,
            'title' => $request->title,
            'url' => $request->url,
            'icon' => $request->icon,
            'category' => $request->category,
            'color' => $request->color ?: '#6366f1',
            'badge' => $request->badge,
            'is_external' => true,
            'order_index' => Shortcut::max('order_index') + 1,
        ]);

        return back()->with('success', 'Atajo rápido creado exitosamente.');
    }

    public function destroy(Shortcut $shortcut)
    {
        $user = Auth::user();
        if ($user->isReseller() && $shortcut->user_id !== $user->id) {
            abort(403);
        }

        $shortcut->delete();
        return back()->with('success', 'Atajo eliminado.');
    }
}

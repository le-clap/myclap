<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Billboard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BillboardController extends Controller
{
    private const MAX_BILLBOARDS = 5;

    private const COLOR_OPTIONS = [
        ['value' => 'gradient-dark-red', 'label' => 'Rouge'],
        ['value' => 'gradient-calm-darya', 'label' => 'Bleu/Violet'],
        ['value' => 'gradient-purple-dream', 'label' => 'Violet'],
        ['value' => 'gradient-sexy-blue', 'label' => 'Bleu'],
        ['value' => 'gradient-emerald-water', 'label' => 'Vert/Bleu'],
    ];

    public function index(): Response
    {
        return Inertia::render('Manager/Billboard/Index', [
            'billboards' => Billboard::orderBy('id')->get(),
        ]);
    }

    public function create(): Response|RedirectResponse
    {
        if (Billboard::count() >= self::MAX_BILLBOARDS) {
            return redirect()->route('manager.billboard.index')
                ->with('error', 'Maximum 5 annonces autorisées');
        }

        return Inertia::render('Manager/Billboard/Create', [
            'colorOptions' => self::COLOR_OPTIONS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        if (Billboard::count() >= self::MAX_BILLBOARDS) {
            return back()->withErrors(['error' => 'Maximum 5 annonces autorisées']);
        }

        Billboard::create($validated);

        return redirect()->route('manager.billboard.index')
            ->with('success', 'Annonce créée');
    }

    public function edit(Billboard $billboard): Response
    {
        return Inertia::render('Manager/Billboard/Edit', [
            'billboard' => $billboard,
            'colorOptions' => self::COLOR_OPTIONS,
        ]);
    }

    public function update(Request $request, Billboard $billboard): RedirectResponse
    {
        $billboard->update($request->validate($this->rules()));

        return redirect()->route('manager.billboard.index')->with('success', 'Annonce modifiée');
    }

    public function destroy(Billboard $billboard): RedirectResponse
    {
        $billboard->delete();

        return redirect()->route('manager.billboard.index')
            ->with('success', 'Annonce supprimée');
    }

    private function rules(): array
    {
        return [
            'title' => 'required|string|max:60',
            'button' => 'required|string|max:30',
            'url' => ['required', 'string', 'max:255', 'regex:#^(https?://|/)#i'],
            'icon' => 'nullable|string|max:30',
            'color' => ['required', Rule::in(array_column(self::COLOR_OPTIONS, 'value'))],
        ];
    }
}

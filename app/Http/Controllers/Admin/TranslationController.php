<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveTranslationRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Translation;
use App\Services\Translation\SaveManualTranslationService;
use App\Services\Translation\TranslationCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TranslationController extends Controller
{
    public function index(Request $request, TranslationCatalog $catalog): View
    {
        abort_unless($request->user()?->can('translation.update'), 403);
        $q = trim((string) $request->query('q'));
        $locale = $request->query('locale');
        $source = $request->query('source');
        $translations = Translation::query()->with(['updatedBy:id,name', 'translatable'])->when($q, fn ($x) => $x->where(fn ($y) => $y->where('source_text', 'like', "%{$q}%")->orWhere('translated_text', 'like', "%{$q}%")))
            ->when(in_array($locale, TranslationCatalog::LOCALES, true), fn ($x) => $x->where('locale', $locale))->when(in_array($source, ['manual', 'provider'], true), fn ($x) => $x->where('source', $source))->latest()->paginate(30)->withQueryString();

        return view('admin.translations.index', ['translations' => $translations, 'categories' => Category::orderBy('name')->get(['id', 'name']), 'products' => Product::orderBy('name')->get(['id', 'name']), 'catalog' => $catalog]);
    }

    public function store(SaveTranslationRequest $request, SaveManualTranslationService $service): RedirectResponse
    {
        $d = $request->validated();
        $service->save($d['entity_type'], (int) $d['entity_id'], $d['field'], $d['locale'], $d['translated_text'], $request->user());

        return back()->with('success',__('translation.saved'));
    }
}

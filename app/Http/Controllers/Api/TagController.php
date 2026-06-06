<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Label;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class TagController extends Controller
{
    public function index(Request $request)
    {
        $vault = $request->user()->currentVault;
        $cacheKey = "vault_{$vault->id}_tags";

        $tags = Cache::remember($cacheKey, 600, function () use ($vault) {
            return Label::withCount('contacts')
                ->where('vault_id', $vault->id)
                ->get();
        });

        return response()->json(['data' => $tags]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'tag_category' => 'nullable|string|max:255',
            'bg_color'     => 'nullable|string|max:255',
            'text_color'   => 'nullable|string|max:255',
        ]);

        $vault = $request->user()->currentVault;

        $tag = Label::create([
            'vault_id'     => $vault->id,
            'name'         => $validated['name'],
            'slug'         => Str::slug($validated['name']),
            'tag_category' => $validated['tag_category'] ?? null,
            'bg_color'     => $validated['bg_color'] ?? 'bg-zinc-200',
            'text_color'   => $validated['text_color'] ?? 'text-zinc-700',
        ]);

        Cache::forget("vault_{$vault->id}_tags");

        return response()->json(['data' => $tag], 201);
    }

    public function update(Request $request, Label $tag)
    {
        $validated = $request->validate([
            'name'         => 'sometimes|string|max:255',
            'tag_category' => 'nullable|string|max:255',
            'bg_color'     => 'nullable|string|max:255',
            'text_color'   => 'nullable|string|max:255',
        ]);

        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $tag->update($validated);

        Cache::forget("vault_{$tag->vault_id}_tags");

        return response()->json(['data' => $tag]);
    }

    public function destroy(Request $request, Label $tag)
    {
        $vaultId = $tag->vault_id;
        $tag->contacts()->detach();
        $tag->delete();

        Cache::forget("vault_{$vaultId}_tags");

        return response()->json(['message' => 'Tag deleted successfully']);
    }
}

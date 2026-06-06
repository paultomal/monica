<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Label;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ContactTagController extends Controller
{
    public function attach(Request $request, Contact $contact)
    {
        $validated = $request->validate([
            'tag_ids'   => 'required|array',
            'tag_ids.*' => 'exists:labels,id',
        ]);

        $contact->labels()->syncWithoutDetaching($validated['tag_ids']);

        Cache::forget("vault_{$contact->vault_id}_tags");

        return response()->json(['message' => 'Tags attached successfully']);
    }

    public function detach(Request $request, Contact $contact, Label $tag)
    {
        $contact->labels()->detach($tag->id);

        Cache::forget("vault_{$contact->vault_id}_tags");

        return response()->json(['message' => 'Tag detached successfully']);
    }
}

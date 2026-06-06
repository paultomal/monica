<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;

class ContactFilterController extends Controller
{
    public function index(Request $request)
    {
        $query = Contact::where('vault_id', $request->user()->currentVault->id);

        if ($request->has('tags') && count($request->tags) > 0) {
            $tagIds = $request->tags;
            $tagCount = count($tagIds);

            $query->whereHas('labels', function ($q) use ($tagIds) {
                $q->whereIn('labels.id', $tagIds);
            }, '=', $tagCount);
        }

        if ($request->has('sort')) {
            $query->orderBy($request->sort);
        }

        $contacts = $query->paginate(15);

        return response()->json($contacts);
    }
}

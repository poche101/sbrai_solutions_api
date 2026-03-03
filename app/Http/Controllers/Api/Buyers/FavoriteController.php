<?php

namespace App\Http\Controllers\Api\Buyers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{
    // GET: Fetch all favorite items
    public function index()
    {
        $favorites = Auth::user()->favorites()->get();
        return response()->json([
            'status' => 'success',
            'count' => $favorites->count(),
            'data' => $favorites
        ]);
    }

    // POST: Toggle favorite (Add/Remove)
    public function toggle(Request $request)
    {
        $request->validate(['product_id' => 'required|exists:products,id']);

        // toggle() adds if missing, removes if exists
        $status = Auth::user()->favorites()->toggle($request->product_id);

        $attached = count($status['attached']) > 0;

        return response()->json([
            'message' => $attached ? 'Added to favorites' : 'Removed from favorites',
            'is_favorite' => $attached
        ]);
    }
}

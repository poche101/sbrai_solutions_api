<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Models\SaleProperty;
use App\Models\SalePropertyImage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Traits\Favoritable;

class SalePropertyController extends Controller
{
    use Favoritable;
    /**
     * List all sale properties with images (Paginated)
     */
    public function index()
    {
        return response()->json([
            'status' => true,
            'data' => SaleProperty::with('images')->latest()->paginate(10)
        ]);
    }

    /**
     * Store a new sale property
     */
    public function store(Request $request)
    {
        $request->validate([
            'sale_category_id' => 'required|exists:sale_categories,id',
            'title' => 'required|string|max:255',
            'price' => 'required|numeric',
            'images' => 'required|array',
        ]);

        return DB::transaction(function () use ($request) {
            $property = SaleProperty::create($request->except('images') + [
                'slug' => Str::slug($request->title) . '-' . time()
            ]);

            foreach ($request->images as $base64) {
                if (preg_match('/^data:image\/(\w+);base64,/', $base64, $type)) {
                    $data = base64_decode(substr($base64, strpos($base64, ',') + 1));
                    $path = "properties/sale/" . Str::random(20) . '.' . $type[1];
                    Storage::disk('public')->put($path, $data);

                    SalePropertyImage::create([
                        'sale_property_id' => $property->id,
                        'image_path' => $path
                    ]);
                }
            }
            return response()->json([
                'status' => true,
                'message' => 'Sale property created successfully',
                'data' => $property->load('images')
            ], 201);
        });
    }

    /**
     * Show a specific sale property
     */
    public function show($id)
    {
        $property = SaleProperty::with('images')->findOrFail($id);

        return response()->json([
            'status' => true,
            'data' => $property
        ]);
    }

    /**
     * Update a sale property (Basic details only)
     * Note: Image updates usually require a separate endpoint or specific logic
     */
    public function update(Request $request, $id)
    {
        $property = SaleProperty::findOrFail($id);

        $property->update($request->except('images', 'slug'));

        // Optional: Update slug if title changes
        if ($request->has('title')) {
            $property->slug = Str::slug($request->title) . '-' . time();
            $property->save();
        }

        return response()->json([
            'status' => true,
            'message' => 'Property updated successfully',
            'data' => $property->load('images')
        ]);
    }

    /**
     * Delete a sale property
     * The model 'booted' method handles deleting images from storage
     */
    public function destroy($id)
    {
        $property = SaleProperty::findOrFail($id);
        $property->delete();

        return response()->json([
            'status' => true,
            'message' => 'Sale property and associated images deleted successfully'
        ]);
    }
}

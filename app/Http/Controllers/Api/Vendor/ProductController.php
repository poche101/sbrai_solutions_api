<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validation - Adjusted to accept strings/URLs for your JSON preference
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'price'       => 'required|numeric',
            'price_unit'  => 'required|string',
            'location'    => 'required|string',
            'photos'      => 'nullable|array|max:5',
            'photos.*'    => 'string', // Expecting path strings or URLs from JSON
        ]);

        try {
            // 2. Identify Vendor (Fallback to first user if not logged in for testing)
            $vendorId = auth()->id() ?? User::first()?->id;

            if (!$vendorId) {
                return response()->json([
                    'status' => false,
                    'message' => 'No vendor found. Please register a user first.'
                ], 422);
            }

            // 3. Create Product
            $product = Product::create([
                'vendor_id'   => $vendorId,
                'category_id' => $request->category_id,
                'title'       => $request->title,
                'description' => $request->description,
                'price'       => $request->price,
                'price_unit'  => $request->price_unit,
                'location'    => $request->location,
                'photos'      => $request->photos, // Directly saving the array of strings
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Product created successfully',
                'data' => $product
            ], 201);

        } catch (\Exception $e) {
            // This logs the real error to storage/logs/laravel.log
            Log::error("Product Store Error: " . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Server Error',
                'error' => $e->getMessage() // This will show you exactly why it failed in Postman
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $product->update($request->only([
            'title', 'description', 'price', 'price_unit', 'location', 'category_id', 'photos'
        ]));

        return response()->json(['status' => true, 'message' => 'Product updated', 'data' => $product]);
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        // Delete images from storage if they are local paths
        if ($product->photos && is_array($product->photos)) {
            foreach ($product->photos as $path) {
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }
        }

        $product->delete();
        return response()->json(['status' => true, 'message' => 'Product deleted']);
    }
}

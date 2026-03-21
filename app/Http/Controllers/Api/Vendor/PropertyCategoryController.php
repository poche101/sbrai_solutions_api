<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Models\RentCategory;
use App\Models\SaleCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PropertyCategoryController extends Controller
{
    /**
     * Display a listing of categories based on type.
     * GET /api/categories/property?type=rent
     */
    public function index(Request $request)
    {
        $type = $request->query('type');

        if (!in_array($type, ['rent', 'sale'])) {
            return response()->json(['status' => false, 'message' => 'Valid type (rent or sale) is required'], 400);
        }

        $categories = ($type === 'rent') ? RentCategory::all() : SaleCategory::all();

        return response()->json([
            'status' => true,
            'data' => $categories
        ]);
    }

    /**
     * Store a new category.
     * POST /api/categories/property
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:rent,sale',
        ]);

        $data = [
            'name' => $request->name,
            'slug' => Str::slug($request->name) . '-' . time(),
        ];

        $category = ($request->type === 'rent')
            ? RentCategory::create($data)
            : SaleCategory::create($data);

        return response()->json([
            'status' => true,
            'message' => 'Category created successfully',
            'data' => $category
        ], 201);
    }

    /**
     * Display a specific category.
     * GET /api/categories/property/{id}?type=rent
     */
    public function show(Request $request, $id)
    {
        $type = $request->query('type');
        $category = ($type === 'rent') ? RentCategory::find($id) : SaleCategory::find($id);

        if (!$category) {
            return response()->json(['status' => false, 'message' => 'Category not found'], 404);
        }

        return response()->json(['status' => true, 'data' => $category]);
    }

    /**
     * Update the specified category.
     * PUT /api/categories/property/{id}
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:rent,sale',
        ]);

        $category = ($request->type === 'rent') ? RentCategory::find($id) : SaleCategory::find($id);

        if (!$category) {
            return response()->json(['status' => false, 'message' => 'Category not found'], 404);
        }

        $category->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name) . '-' . time(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Category updated successfully',
            'data' => $category
        ]);
    }

    /**
     * Remove the specified category.
     * DELETE /api/categories/property/{id}?type=rent
     */
    public function destroy(Request $request, $id)
    {
        $type = $request->query('type');
        $category = ($type === 'rent') ? RentCategory::find($id) : SaleCategory::find($id);

        if (!$category) {
            return response()->json(['status' => false, 'message' => 'Category not found'], 404);
        }

        $category->delete();

        return response()->json([
            'status' => true,
            'message' => 'Category deleted successfully'
        ]);
    }
}

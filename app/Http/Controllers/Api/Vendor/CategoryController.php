<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Display a listing of the categories for the vendor to choose from.
     */
    public function index()
    {
        $categories = Category::all();
        return response()->json([
            'status' => true,
            'message' => 'Categories retrieved successfully',
            'data' => $categories
        ], 200);
    }

    /**
     * Store a newly created category (if vendors are allowed to propose new ones).
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:categories,name|max:255',
        ]);

        $category = Category::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Category created successfully',
            'data' => $category
        ], 201);
    }

    /**
     * Display a specific category.
     */
    public function show(string $id)
    {
        $category = Category::findOrFail($id);
        return response()->json(['data' => $category], 200);
    }

    /**
     * Update an existing category.
     */
    public function update(Request $request, $id) // Use $id here
{
    $category = Category::find($id);

    if (!$category) {
        return response()->json(['message' => 'Category not found'], 404);
    }

    $category->update($request->all());
    return response()->json(['status' => true, 'data' => $category]);
}

    /**
     * Remove a category.
     */
  public function destroy($id)
{
    $category = Category::find($id);

    if (!$category) {
        return response()->json(['message' => 'Category not found'], 404);
    }

    $category->delete();
    return response()->json(['status' => true, 'message' => 'Deleted']);
}
}

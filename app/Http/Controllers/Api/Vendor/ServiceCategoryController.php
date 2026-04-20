<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Models\ServiceCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ServiceCategoryController extends Controller
{
    /**
     * Display a listing of the categories.
     */
    public function index()
    {
        $categories = ServiceCategory::all();
        return response()->json([
            'status' => true,
            'message' => 'Service categories retrieved successfully',
            'data' => $categories
        ], 200);
    }

    /**
     * Store a newly created category.
     */
    public function store(Request $request)
    {
        // Fixed validation: ensuring the unique check matches the actual column name 'title'
        $request->validate([
            'title' => 'required|string|unique:service_categories,title|max:255',
            'description' => 'nullable|string',
        ]);

        $category = ServiceCategory::create([
            'title' => $request->title,
            'slug' => Str::slug($request->title),
            'description' => $request->description,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Service category created successfully',
            'data' => $category
        ], 201);
    }

    /**
     * Display the specified category.
     */
    public function show(string $id)
    {
        $category = ServiceCategory::findOrFail($id);
        return response()->json([
            'status' => true,
            'data' => $category
        ], 200);
    }

    /**
     * Update the specified category.
     */
    public function update(Request $request, $id)
    {
        $category = ServiceCategory::find($id);

        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'Service category not found'
            ], 404);
        }

        // Validate title uniqueness but ignore the current ID
        $request->validate([
            'title' => 'sometimes|string|unique:service_categories,title,' . $id . '|max:255',
        ]);

        $data = $request->all();

        // Update slug if title is changed
        if ($request->has('title')) {
            $data['slug'] = Str::slug($request->title);
        }

        $category->update($data);

        return response()->json([
            'status' => true,
            'message' => 'Service category updated successfully',
            'data' => $category
        ], 200);
    }

    /**
     * Remove the specified category.
     */
    public function destroy($id)
    {
        $category = ServiceCategory::find($id);

        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'Service category not found'
            ], 404);
        }

        $category->delete();

        return response()->json([
            'status' => true,
            'message' => 'Service category deleted successfully'
        ], 200);
    }
}

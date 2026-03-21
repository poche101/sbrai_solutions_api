<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Models\ServiceCategory; // Updated model name
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ServiceCategoryController extends Controller
{
    public function index()
    {
        $categories = ServiceCategory::all();
        return response()->json([
            'status' => true,
            'message' => 'Service categories retrieved successfully',
            'data' => $categories
        ], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:service_categories,name|max:255',
        ]);

        $category = ServiceCategory::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Service category created successfully',
            'data' => $category
        ], 201);
    }

    public function show(string $id)
    {
        $category = ServiceCategory::findOrFail($id);
        return response()->json(['data' => $category], 200);
    }

    public function update(Request $request, $id)
    {
        $category = ServiceCategory::find($id);

        if (!$category) {
            return response()->json(['message' => 'Service category not found'], 404);
        }

        $category->update($request->all());
        return response()->json(['status' => true, 'data' => $category]);
    }

    public function destroy($id)
    {
        $category = ServiceCategory::find($id);

        if (!$category) {
            return response()->json(['message' => 'Service category not found'], 404);
        }

        $category->delete();
        return response()->json(['status' => true, 'message' => 'Deleted']);
    }
}

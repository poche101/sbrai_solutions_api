<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Models\RentProperty;
use App\Models\RentPropertyImage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class RentPropertyController extends Controller {
    public function index() {
        return response()->json([
            'status' => true,
            'data' => RentProperty::with('images')->latest()->paginate(10)
        ]);
    }

    public function store(Request $request) {
        $request->validate([
            'rent_category_id' => 'required|exists:rent_categories,id',
            'title' => 'required|string|max:255',
            'price' => 'required|numeric',
            'images' => 'required|array',
        ]);

        return DB::transaction(function () use ($request) {
            $property = RentProperty::create($request->all() + [
                'slug' => Str::slug($request->title) . '-' . time()
            ]);

            foreach ($request->images as $base64) {
                if (preg_match('/^data:image\/(\w+);base64,/', $base64, $type)) {
                    $data = base64_decode(substr($base64, strpos($base64, ',') + 1));
                    $path = "properties/rent/" . Str::random(20) . '.' . $type[1];
                    Storage::disk('public')->put($path, $data);

                    RentPropertyImage::create([
                        'rent_property_id' => $property->id,
                        'image_path' => $path
                    ]);
                }
            }
            return response()->json(['status' => true, 'data' => $property->load('images')], 201);
        });
    }

    public function show($id) {
        return response()->json(['status' => true, 'data' => RentProperty::with('images')->findOrFail($id)]);
    }

    public function update(Request $request, $id) {
        $property = RentProperty::findOrFail($id);
        $property->update($request->all());
        return response()->json(['status' => true, 'data' => $property]);
    }

    public function destroy($id) {
        RentProperty::findOrFail($id)->delete();
        return response()->json(['status' => true, 'message' => 'Deleted successfully']);
    }
}

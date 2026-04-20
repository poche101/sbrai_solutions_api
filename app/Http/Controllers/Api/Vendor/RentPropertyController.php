<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Models\RentProperty;
use App\Models\RentPropertyImage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Traits\Favoritable;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class RentPropertyController extends Controller {

    use Favoritable;

    protected $imageManager;

    public function __construct()
    {
        $this->imageManager = new ImageManager(new Driver);
    }

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
                    
                    // Apply watermark to the saved image
                    $this->applyWatermark($path);
                    
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
        
        DB::transaction(function () use ($request, $property) {
            $property->update($request->all());
   
            if ($request->has('images') && is_array($request->images)) {
                foreach ($property->images as $oldImage) {
                    Storage::disk('public')->delete($oldImage->image_path);
                    $oldImage->delete();
                }
                
                foreach ($request->images as $base64) {
                    if (preg_match('/^data:image\/(\w+);base64,/', $base64, $type)) {
                        $data = base64_decode(substr($base64, strpos($base64, ',') + 1));
                        $path = "properties/rent/" . Str::random(20) . '.' . $type[1];
                        Storage::disk('public')->put($path, $data);
                        
                        $this->applyWatermark($path);
                        
                        RentPropertyImage::create([
                            'rent_property_id' => $property->id,
                            'image_path' => $path
                        ]);
                    }
                }
            }
        });
        
        return response()->json(['status' => true, 'data' => $property->load('images')]);
    }

    public function destroy($id) {
        $property = RentProperty::findOrFail($id);
        
        foreach ($property->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }
        
        $property->delete();
        
        return response()->json(['status' => true, 'message' => 'Deleted successfully']);
    }
    
    /**
     * Apply watermark to an image file.
     */
    private function applyWatermark($imagePath)
    {
        try {
            $fullPath = Storage::disk('public')->path($imagePath);
            
            if (!file_exists($fullPath)) {
                Log::error('Watermark Error: Image not found at path: ' . $fullPath);
                return false;
            }
            
            $img = $this->imageManager->read($fullPath);
            $watermarkPath = public_path('images/watermark.png');
            
            if (file_exists($watermarkPath)) {
                $watermark = $this->imageManager->read($watermarkPath);
                $img->place($watermark, 'bottom-right', 10, 10);
                $img->save($fullPath);
                return true;
            } else {
                Log::warning('Watermark file not found at: ' . $watermarkPath);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Watermark Error: ' . $e->getMessage());
            return false;
        }
    }
}
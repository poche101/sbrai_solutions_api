<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Models\SaleProperty;
use App\Models\SalePropertyImage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Traits\Favoritable;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class SalePropertyController extends Controller
{
    use Favoritable;

    protected $imageManager;

    public function __construct()
    {
        $this->imageManager = new ImageManager(new Driver);
    }

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
                    
                    $this->applyWatermark($path);

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

        DB::transaction(function () use ($request, $property) {
            $property->update($request->except('images', 'slug'));

            if ($request->has('images') && is_array($request->images)) {
                foreach ($property->images as $oldImage) {
                    Storage::disk('public')->delete($oldImage->image_path);
                    $oldImage->delete();
                }
                
                foreach ($request->images as $base64) {
                    if (preg_match('/^data:image\/(\w+);base64,/', $base64, $type)) {
                        $data = base64_decode(substr($base64, strpos($base64, ',') + 1));
                        $path = "properties/sale/" . Str::random(20) . '.' . $type[1];
                        Storage::disk('public')->put($path, $data);
                        
                        $this->applyWatermark($path);
                        
                        SalePropertyImage::create([
                            'sale_property_id' => $property->id,
                            'image_path' => $path
                        ]);
                    }
                }
            }

            if ($request->has('title')) {
                $property->slug = Str::slug($request->title) . '-' . time();
                $property->save();
            }
        });

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
        
        foreach ($property->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }
        
        $property->delete();

        return response()->json([
            'status' => true,
            'message' => 'Sale property and associated images deleted successfully'
        ]);
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
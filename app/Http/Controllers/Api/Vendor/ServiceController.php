<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServicePhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Traits\Favoritable;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ServiceController extends Controller
{
    use Favoritable;

    protected $imageManager;

    public function __construct()
    {
        $this->imageManager = new ImageManager(new Driver);
    }

    public function index(Request $request)
{
    $query = Service::query()->with('photos');

    // Filter by category if provided in the request
    if ($request->has('category_id')) {
        $query->where('service_category_id', $request->category_id);
    }

    return response()->json([
        'status' => true,
        'data' => $query->get()
    ]);
}

    /**
     * Create a new service
     */
    public function store(Request $request)
    {
        $request->validate([
            'service_category_id' => 'required|exists:service_categories,id',
            'title'               => 'required|string|max:255',
            'description'         => 'nullable|string',
            'price'               => 'nullable|numeric',
            'price_unit'          => 'nullable|string',
            'location'            => 'nullable|string',
            'images'              => 'required|array',
            'images.*'            => 'required|string',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                $service = Service::create([
                    'service_category_id' => $request->service_category_id,
                    'title'               => $request->title,
                    'slug'                => Str::slug($request->title) . '-' . time(),
                    'description'         => $request->description,
                    'price'               => $request->price,
                    'price_unit'          => $request->price_unit,
                    'location'            => $request->location,
                ]);

                $this->uploadImages($service, $request->images);

                return response()->json([
                    'status'  => true,
                    'message' => 'Service created successfully',
                    'data'    => $service->load('photos')
                ], 201);
            });
        } catch (\Exception $e) {
            Log::error('Service Store Error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update an existing service
     */
    public function update(Request $request, $id)
    {
        $service = Service::findOrFail($id);

        $request->validate([
            'service_category_id' => 'sometimes|exists:service_categories,id',
            'title'               => 'sometimes|string|max:255',
            'images'              => 'nullable|array', // Optional: only send if adding new photos
        ]);

        try {
            return DB::transaction(function () use ($request, $service) {
                $service->update($request->only([
                    'service_category_id', 'title', 'description', 'price', 'price_unit', 'location'
                ]));

                if ($request->has('title')) {
                    $service->slug = Str::slug($request->title) . '-' . time();
                    $service->save();
                }

                // If new images are provided, upload them (adds to existing ones)
                if ($request->has('images')) {
                    $this->uploadImages($service, $request->images);
                }

                return response()->json([
                    'status'  => true,
                    'message' => 'Service updated successfully',
                    'data'    => $service->load('photos')
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Service Update Error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete a service and its photos
     */
    public function destroy($id)
    {
        $service = Service::findOrFail($id);

        try {
            foreach ($service->photos as $photo) {
                Storage::disk('public')->delete($photo->image_path);
            }

            $service->delete();

            return response()->json([
                'status'  => true,
                'message' => 'Service and associated photos deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Service Destroy Error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Helper Method: Handle Base64 Uploads with Watermark
     */
   private function uploadImages($service, array $images)
{
    foreach ($images as $base64Image) {
        // 1. Detect the file type and clean the Base64 string
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
            $data = substr($base64Image, strpos($base64Image, ',') + 1);
            $extension = strtolower($type[1]); // e.g., png, jpg
            $decodedData = base64_decode($data);

            if ($decodedData === false) continue;

            // 2. Generate a unique name to prevent overwriting
            $fileName = Str::random(20) . '.' . $extension;
            $path = "services/photos/{$fileName}";

            // 3. Save the actual file to storage/app/public/services/photos
            Storage::disk('public')->put($path, $decodedData);

            // 4. Apply your watermark (as you have in your logic)
            $this->applyWatermark($path);

            // 5. Save the relationship in the database
            ServicePhoto::create([
                'service_id' => $service->id,
                'image_path' => $path
            ]);
        }
    }
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

    /**
     * Optional: Add a method to delete specific images from a service
     */
    public function deleteImage($serviceId, $photoId)
    {
        try {
            $service = Service::findOrFail($serviceId);
            $photo = $service->photos()->findOrFail($photoId);

            Storage::disk('public')->delete($photo->image_path);

            // Delete record from database
            $photo->delete();

            return response()->json([
                'status' => true,
                'message' => 'Image deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Delete Image Error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete image'
            ], 500);
        }
    }
}

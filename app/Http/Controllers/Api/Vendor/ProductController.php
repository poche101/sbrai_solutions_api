<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use App\Traits\Favoritable;

class ProductController extends Controller
{
    use Favoritable;
    protected $imageManager;

    public function __construct()
    {
        $this->imageManager = new ImageManager(new Driver);
    }

    /**
     * Apply watermark to an image
     */
    private function applyWatermark($imagePath)
    {
        try {
            $fullPath = Storage::disk('public')->path($imagePath);

            $img = $this->imageManager->read($fullPath);

            $watermarkPath = public_path('images/watermark.png');

            if (file_exists($watermarkPath)) {
                $watermark = $this->imageManager->read($watermarkPath);

                $img->place($watermark, 'bottom-right', 10, 10);

                $img->save($fullPath);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Watermark Error: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Process multiple images with watermark
     */
    private function processImagesWithWatermark($photos)
    {
        $processedPhotos = [];

        foreach ($photos as $photo) {
            if (is_string($photo) && strpos($photo, 'data:image') === 0) {
                $imageData = base64_decode(
                    preg_replace('#^data:image/\w+;base64,#i', '', $photo)
                );

                $fileName = 'products/'.uniqid().'.jpg';

                Storage::disk('public')->put($fileName, $imageData);

                $this->applyWatermark($fileName);

                $processedPhotos[] = $fileName;

            } elseif ($photo instanceof UploadedFile) {
                // Handle uploaded file
                $fileName = $photo->store('products', 'public');

                $this->applyWatermark($fileName);

                $processedPhotos[] = $fileName;

            } else {
                $processedPhotos[] = $photo;
            }
        }

        return $processedPhotos;
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric',
            'price_unit' => 'required|string',
            'location' => 'required|string',
            'photos' => 'nullable|array|max:5',
            'photos.*' => 'required_with:photos|file|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        try {
            $vendorId = auth()->id() ?? User::first()?->id;

            if (! $vendorId) {
                return response()->json([
                    'status' => false,
                    'message' => 'No vendor found. Please register a user first.',
                ], 422);
            }

            $processedPhotos = [];

            if ($request->has('photos') && is_array($request->photos)) {
                $processedPhotos = $this->processImagesWithWatermark($request->photos);
            }

            $product = Product::create([
                'vendor_id' => $vendorId,
                'category_id' => $request->category_id,
                'title' => $request->title,
                'description' => $request->description,
                'price' => $request->price,
                'price_unit' => $request->price_unit,
                'location' => $request->location,
                'photos' => $processedPhotos,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Product created successfully with watermarked images',
                'data' => $product,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Product Store Error: '.$e->getMessage());

            // rollback uploaded images
            if (! empty($processedPhotos)) {
                foreach ($processedPhotos as $photo) {
                    if (Storage::disk('public')->exists($photo)) {
                        Storage::disk('public')->delete($photo);
                    }
                }
            }

            return response()->json([
                'status' => false,
                'message' => 'Server Error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        try {
            $updateData = $request->only([
                'title',
                'description',
                'price',
                'price_unit',
                'location',
                'category_id',
            ]);

            if ($request->has('photos') && is_array($request->photos)) {

                // delete old photos
                if ($product->photos && is_array($product->photos)) {
                    foreach ($product->photos as $oldPhoto) {
                        if (Storage::disk('public')->exists($oldPhoto)) {
                            Storage::disk('public')->delete($oldPhoto);
                        }
                    }
                }

                $processedPhotos = $this->processImagesWithWatermark($request->photos);

                $updateData['photos'] = $processedPhotos;
            }

            $product->update($updateData);

            return response()->json([
                'status' => true,
                'message' => 'Product updated with watermarked images',
                'data' => $product->fresh(),
            ]);

        } catch (\Exception $e) {
            Log::error('Product Update Error: '.$e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Update failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        if ($product->photos && is_array($product->photos)) {
            foreach ($product->photos as $path) {
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }
        }

        $product->delete();

        return response()->json([
            'status' => true,
            'message' => 'Product deleted',
        ]);
    }
}

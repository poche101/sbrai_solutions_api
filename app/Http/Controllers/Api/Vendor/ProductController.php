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
     * Display a listing of products with filtering.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 10);

            // Start query
            $query = Product::query();

            // 1. Filter by Category Name (via relationship)
            if ($request->filled('category')) {
                $query->whereHas('category', function ($q) use ($request) {
                    $q->where('categories.name', $request->category);
                });
            }

            // 2. Filter by State/Location
            if ($request->filled('state')) {
                $query->where('location', 'like', '%' . $request->state . '%');
            }

            // 3. Search Filter (Title or Description)
            if ($request->filled('search')) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('title', 'like', '%' . $searchTerm . '%')
                        ->orWhere('description', 'like', '%' . $searchTerm . '%');
                });
            }

            // Get paginated results with category relationship
            $products = $query->with('category')->latest()->paginate($perPage);

            return response()->json([
                'status' => true,
                'data' => $products->items(),
                'meta' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'total' => $products->total(),
                    'per_page' => $products->perPage(),
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Product Index Error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch products',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified product.
     */
    public function show($id)
    {
        try {
            $product = Product::with('category')->findOrFail($id);
            return response()->json([
                'status' => true,
                'data' => $product,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Product not found',
            ], 404);
        }
    }

    /**
     * Store a newly created product.
     */
    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'title' => 'required',
            'description' => 'required',
            'price' => 'required|numeric',
            'price_unit' => 'required|string',
            'location' => 'required|string',
            'photos' => 'nullable|array|max:5',
        ]);

        try {
            $vendorId = auth()->id() ?? User::first()?->id;

            if (!$vendorId) {
                return response()->json([
                    'status' => false,
                    'message' => 'No vendor found. Please register a user first.',
                ], 422);
            }

            $processedPhotos = [];

            if ($request->has('photos') && is_array($request->photos)) {
                $processedPhotos = $this->processImagesWithWatermark($request->photos);
            }

            $product = new Product();
            $product->vendor_id = $vendorId;
            $product->category_id = $request->category_id;
            $product->price = $request->price;
            $product->price_unit = $request->price_unit;
            $product->location = $request->location;
            $product->photos = $processedPhotos;

            // Handle Translations
            $product->setTranslations('title', is_array($request->title) ? $request->title : [app()->getLocale() => $request->title]);
            $product->setTranslations('description', is_array($request->description) ? $request->description : [app()->getLocale() => $request->description]);

            $product->save();

            return response()->json([
                'status' => true,
                'message' => 'Product created successfully',
                'data' => $product,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Product Store Error: ' . $e->getMessage());
            if (!empty($processedPhotos)) {
                foreach ($processedPhotos as $photo) {
                    Storage::disk('public')->delete($photo);
                }
            }
            return response()->json([
                'status' => false,
                'message' => 'Server Error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified product.
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        try {
            if ($request->has('price')) $product->price = $request->price;
            if ($request->has('price_unit')) $product->price_unit = $request->price_unit;
            if ($request->has('location')) $product->location = $request->location;
            if ($request->has('category_id')) $product->category_id = $request->category_id;

            if ($request->has('title')) {
                $product->setTranslations('title', is_array($request->title) ? $request->title : [app()->getLocale() => $request->title]);
            }

            if ($request->has('description')) {
                $product->setTranslations('description', is_array($request->description) ? $request->description : [app()->getLocale() => $request->description]);
            }

            if ($request->has('photos') && is_array($request->photos)) {
                // Delete old photos
                if ($product->photos && is_array($product->photos)) {
                    foreach ($product->photos as $oldPhoto) {
                        Storage::disk('public')->delete($oldPhoto);
                    }
                }
                $product->photos = $this->processImagesWithWatermark($request->photos);
            }

            $product->save();

            return response()->json([
                'status' => true,
                'message' => 'Product updated successfully',
                'data' => $product->fresh(),
            ]);

        } catch (\Exception $e) {
            Log::error('Product Update Error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Update failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified product.
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        if ($product->photos && is_array($product->photos)) {
            foreach ($product->photos as $path) {
                Storage::disk('public')->delete($path);
            }
        }

        $product->delete();

        return response()->json([
            'status' => true,
            'message' => 'Product deleted successfully',
        ]);
    }

    /**
     * Apply watermark to an image file.
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
            Log::error('Watermark Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Process images (Base64 or UploadedFile) and add watermarks.
     */
    private function processImagesWithWatermark($photos)
    {
        $processedPhotos = [];

        foreach ($photos as $photo) {
            if (is_string($photo) && strpos($photo, 'data:image') === 0) {
                $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $photo));
                $fileName = 'products/' . uniqid() . '.jpg';
                Storage::disk('public')->put($fileName, $imageData);
                $this->applyWatermark($fileName);
                $processedPhotos[] = $fileName;
            } elseif ($photo instanceof UploadedFile) {
                $fileName = $photo->store('products', 'public');
                $this->applyWatermark($fileName);
                $processedPhotos[] = $fileName;
            } else {
                $processedPhotos[] = $photo;
            }
        }

        return $processedPhotos;
    }
}

<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServicePhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage; // CRITICAL: Missing this causes 500 error
use Illuminate\Support\Facades\DB;      // CRITICAL: Missing this causes 500 error

class ServiceController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validation
        $request->validate([
            'service_category_id' => 'required|exists:service_categories,id',
            'name'                => 'required|string|max:255',
            'description'         => 'nullable|string',
            'price'               => 'nullable|numeric',
            'images'              => 'required|array',
            'images.*'            => 'required|string', // Base64 strings
        ]);

        try {
            // Use a transaction to ensure database integrity
            return DB::transaction(function () use ($request) {

                // 2. Create the Service
                $service = Service::create([
                    'service_category_id' => $request->service_category_id,
                    'name'                => $request->name,
                    'slug'                => Str::slug($request->name) . '-' . time(),
                    'description'         => $request->description,
                    'price'               => $request->price,
                ]);

                // 3. Process Base64 Images
                foreach ($request->images as $base64Image) {
                    // Extract extension and data using regex
                    if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {

                        $data = substr($base64Image, strpos($base64Image, ',') + 1);
                        $extension = strtolower($type[1]); // png, jpg, etc.
                        $decodedData = base64_decode($data);

                        if ($decodedData === false) continue;

                        // Generate unique filename
                        $fileName = Str::random(20) . '.' . $extension;
                        $path = "services/photos/{$fileName}";

                        // Save file to storage/app/public/services/photos
                        Storage::disk('public')->put($path, $decodedData);

                        // 4. Save photo record
                        ServicePhoto::create([
                            'service_id' => $service->id,
                            'image_path' => $path
                        ]);
                    }
                }

                return response()->json([
                    'status'  => true,
                    'message' => 'Service and photos created successfully',
                    'data'    => $service->load('photos')
                ], 201);
            });

        } catch (\Exception $e) {
            // This will tell you EXACTLY what went wrong in Postman
            return response()->json([
                'status'  => false,
                'message' => 'Server Error: ' . $e->getMessage(),
                'line'    => $e->getLine()
            ], 500);
        }
    }
}

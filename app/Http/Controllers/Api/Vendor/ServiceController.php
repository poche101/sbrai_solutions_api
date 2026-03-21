<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServicePhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ServiceController extends Controller
{
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

                // Update slug if title changed
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
            // Note: The physical file deletion is handled by the "booted" method
            // we added to the Service Model earlier.
            $service->delete();

            return response()->json([
                'status'  => true,
                'message' => 'Service and associated photos deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Helper Method: Handle Base64 Uploads
     */
    private function uploadImages($service, array $images)
    {
        foreach ($images as $base64Image) {
            if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
                $data = substr($base64Image, strpos($base64Image, ',') + 1);
                $extension = strtolower($type[1]);
                $decodedData = base64_decode($data);

                if ($decodedData === false) continue;

                $fileName = Str::random(20) . '.' . $extension;
                $path = "services/photos/{$fileName}";

                Storage::disk('public')->put($path, $decodedData);

                ServicePhoto::create([
                    'service_id' => $service->id,
                    'image_path' => $path
                ]);
            }
        }
    }
}

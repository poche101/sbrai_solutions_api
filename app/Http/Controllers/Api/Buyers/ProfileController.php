<?php

namespace App\Http\Controllers\Api\Buyers;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserProfileResource;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    /**
     * Display the current user's profile.
     * Fix: Uses firstOrCreate to prevent 404 errors for new users.
     */
    public function index()
    {
        $user = Auth::user();

        // Automatically create a profile if it doesn't exist to prevent 404
        $profile = $user->profile()->firstOrCreate(
            ['user_id' => $user->id],
            ['full_name' => $user->name]
        );

        return new UserProfileResource($profile);
    }

    /**
     * Store a newly created profile (Base64).
     */
    public function store(Request $request)
    {
        try {
            $user = Auth::user();

            if ($user->profile()->exists()) {
                return response()->json(['message' => 'Profile already exists'], 422);
            }

            $validated = $request->validate([
                'fullName' => 'required|string|max:255',
                'phone'    => 'nullable|string|max:20',
                'address'  => 'nullable|string',
                'photo'    => 'nullable|string',
            ]);

            $profileData = [
                'user_id'   => $user->id,
                'full_name' => $validated['fullName'],
                'phone'     => $validated['phone'],
                'address'   => $validated['address'],
            ];

            if ($request->filled('photo')) {
                $path = $this->uploadBase64Image($request->photo);
                if ($path) {
                    $profileData['profile_photo'] = $path;
                }
            }

            $profile = UserProfile::create($profileData);

            return response()->json([
                'status'  => 'success',
                'message' => 'Profile created successfully',
                'data'    => new UserProfileResource($profile)
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display specific profile.
     */
    public function show($id = null)
    {
        // Re-use the index logic to ensure a profile is always found/created
        return $this->index();
    }

    /**
     * Update profile using JSON/Base64.
     */
   public function update(Request $request, $id = null)
{
    try {
        $user = Auth::user();
        $profile = $user->profile()->firstOrCreate(['user_id' => $user->id]);

        $validated = $request->validate([
            'fullName' => 'nullable|string|max:255',
            'phone'    => 'nullable|string|max:20',
            'address'  => 'nullable|string',
            'photo'    => 'nullable|string',
        ]);

        // Use the validated keys
        $updateData = [
            'full_name' => $request->input('fullName', $profile->full_name),
            'phone'     => $request->input('phone', $profile->phone),
            'address'   => $request->input('address', $profile->address),
        ];

        // Image Handling
        if ($request->filled('photo') && str_starts_with($request->photo, 'data:image')) {
            $path = $this->uploadBase64Image($request->photo);
            if ($path) {
                if ($profile->profile_photo && Storage::disk('public')->exists($profile->profile_photo)) {
                    Storage::disk('public')->delete($profile->profile_photo);
                }
                $updateData['profile_photo'] = $path;
            }
        }

        $profile->update($updateData);

        return response()->json([
            'status'  => 'success',
            'message' => 'Profile updated successfully',
            'data'    => new UserProfileResource($profile->fresh())
        ]);

    } catch (\Throwable $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
}

    /**
     * Remove profile.
     */
    public function destroy($id = null)
    {
        $profile = Auth::user()->profile;

        if (!$profile) {
            return response()->json(['message' => 'Profile not found'], 404);
        }

        if ($profile->profile_photo) {
            Storage::disk('public')->delete($profile->profile_photo);
        }

        $profile->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Profile data cleared successfully'
        ]);
    }

    /**
     * Helper to decode and store Base64 images safely.
     */
    private function uploadBase64Image($base64String)
    {
        try {
            if (!preg_match('/^data:image\/(\w+);base64,/', $base64String, $type)) {
                return null;
            }

            $extension = strtolower($type[1]);
            $data = substr($base64String, strpos($base64String, ',') + 1);
            $decodedData = base64_decode($data);

            if (!$decodedData) {
                return null;
            }

            $fileName = 'profiles/' . Str::random(30) . '.' . $extension;
            Storage::disk('public')->put($fileName, $decodedData);

            return $fileName;
        } catch (\Exception $e) {
            return null;
        }
    }
}

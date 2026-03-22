<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // Use null-safe operator ?-> to prevent "Trying to get property of non-object"
            'fullName' => $this->full_name ?? $this->user?->name ?? 'User',
            'email'    => $this->user?->email ?? '',
            'phone'    => $this->phone ?? '',
            'address'  => $this->address ?? '',

            // Generate a full URL for the photo if it exists
            'photo'    => $this->profile_photo ? asset('storage/' . $this->profile_photo) : null,

            // Format date safely; fall back to current date if record is brand new/unsaved
            'joinDate' => $this->created_at ? $this->created_at->format('M Y') : now()->format('M Y'),
        ];
    }
}

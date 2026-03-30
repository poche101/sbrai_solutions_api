<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait Favoritable
{
    /**
     * Get the users who have favorited this item.
     * This connects to a 'favorites' pivot table.
     */
    public function favoritedBy(): BelongsToMany
    {
        // 'favorites' is the table name
        // 'favoritable_id' and 'favoritable_type' are for polymorphic relations
        return $this->morphToMany(User::class, 'favoritable', 'favorites')
                    ->withTimestamps();
    }

    /**
     * Check if a specific user has favorited this.
     */
    public function isFavoritedBy(User $user): bool
    {
        return $this->favoritedBy()->where('user_id', $user->id)->exists();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForumCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'slug',
        'description',
        'ordre',
        'admin_only',
    ];

    protected $casts = [
        'admin_only' => 'boolean',
    ];

    public function posts(): HasMany
    {
        return $this->hasMany(ForumPost::class);
    }

    public function postsCount(): int
    {
        return $this->posts()->count();
    }
}

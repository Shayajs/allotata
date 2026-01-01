<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    protected $fillable = [
        'question',
        'reponse',
        'categorie',
        'ordre',
        'est_actif',
    ];

    protected $casts = [
        'est_actif' => 'boolean',
        'ordre' => 'integer',
    ];

    public static function getCategories(): array
    {
        return self::where('est_actif', true)
            ->distinct()
            ->pluck('categorie')
            ->filter()
            ->sort()
            ->values()
            ->toArray();
    }

    public static function getByCategorie(?string $categorie = null)
    {
        $query = self::where('est_actif', true)->orderBy('ordre')->orderBy('id');
        
        if ($categorie) {
            $query->where('categorie', $categorie);
        }
        
        return $query->get();
    }
}

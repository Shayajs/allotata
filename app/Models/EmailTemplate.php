<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'name',
        'subject',
        'body',
        'variables',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'variables' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Obtenir un template par type
     */
    public static function getByType(string $type): ?self
    {
        return self::where('type', $type)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Remplacer les variables dans le sujet et le corps
     */
    public function replaceVariables(array $data): array
    {
        $subject = $this->subject;
        $body = $this->body;

        foreach ($data as $key => $value) {
            $placeholder = '{' . $key . '}';
            $subject = str_replace($placeholder, $value, $subject);
            $body = str_replace($placeholder, $value, $body);
        }

        return [
            'subject' => $subject,
            'body' => $body,
        ];
    }
}

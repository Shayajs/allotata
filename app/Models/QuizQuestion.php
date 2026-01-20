<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizQuestion extends Model
{
    protected $fillable = [
        'lesson_id',
        'question',
        'type',
        'options_json',
        'bonne_reponse',
        'points',
        'ordre',
    ];

    protected $casts = [
        'options_json' => 'array',
        'points' => 'integer',
        'ordre' => 'integer',
    ];

    /**
     * Relation : Une question appartient à une leçon
     */
    public function lesson()
    {
        return $this->belongsTo(CourseLesson::class, 'lesson_id');
    }

    /**
     * Obtenir les options formatées pour les questions à choix multiples
     */
    public function getOptions(): array
    {
        return $this->options_json ?? [];
    }

    /**
     * Vérifier si une réponse est correcte
     */
    public function checkAnswer($answer): bool
    {
        if ($this->type === 'true_false') {
            return (bool) $answer === (bool) $this->bonne_reponse;
        }

        if ($this->type === 'multiple_choice') {
            return $answer === $this->bonne_reponse;
        }

        // Pour les questions texte, on compare en minuscules et sans espaces
        if ($this->type === 'text') {
            return strtolower(trim($answer)) === strtolower(trim($this->bonne_reponse));
        }

        return false;
    }
}

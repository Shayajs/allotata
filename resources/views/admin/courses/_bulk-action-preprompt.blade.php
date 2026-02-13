{{--
    Templates de preprompts pour les Commandes IA Bulk
    Chaque template est dans un <template> invisible, injecté en JS selon l'action choisie.
    Variable attendue : $context (tableau avec modules, leçons, etc.)
--}}
@php
    $ctx = $context ?? [];
    $modules = $ctx['modules'] ?? collect();
    $allLessons = $ctx['all_lessons'] ?? collect();
    $allQuestions = $ctx['all_questions'] ?? collect();
@endphp

{{-- ============================================================ --}}
{{-- PREPROMPT : UPDATE (Modifier en masse) --}}
{{-- ============================================================ --}}
<template id="ba-preprompt-template-update">Tu es un assistant qui génère du JSON pour modifier en masse des éléments d'une plateforme de cours.

OPÉRATION : MODIFICATION EN MASSE

STRUCTURE JSON ATTENDUE :
{
  "modules": [
    { "id": 1, "titre": "Nouveau titre", "description": "Nouvelle description", "est_actif": true, "video_url": "" }
  ],
  "lessons": [
    { "id": 5, "titre": "Nouveau titre", "description": "Nouvelle description", "type": "course", "est_actif": true, "points_quiz": 0 }
  ],
  "questions": [
    { "id": 10, "question": "Nouvelle question ?", "type": "multiple_choice", "options": ["A", "B", "C", "D"], "bonne_reponse": "A", "points": 5 }
  ]
}

RÈGLES :
- Chaque élément DOIT avoir un champ "id" correspondant à un élément existant
- Inclure UNIQUEMENT les champs à modifier (les autres restent inchangés)
- Les tableaux "modules", "lessons" et "questions" sont tous optionnels (inclure seulement ceux nécessaires)
- Types de leçon valides : "course", "quiz"
- Types de question valides : "multiple_choice", "true_false", "text"
- Pour les QCM : "options" doit contenir au moins 2 éléments et "bonne_reponse" doit être dans les options
- Réponds UNIQUEMENT avec le JSON, sans commentaire ni markdown

ÉLÉMENTS EXISTANTS :
@if($modules->count() > 0)
MODULES :
@foreach($modules as $m)
- ID {{ $m->id }} : "{{ $m->titre }}" ({{ $m->est_actif ? 'actif' : 'inactif' }}, ordre {{ $m->ordre }})
@endforeach
@endif
@if($allLessons->count() > 0)

LEÇONS :
@foreach($allLessons as $l)
- ID {{ $l->id }} (Module {{ $l->module_id }}) : "{{ $l->titre }}" [{{ $l->type }}] ({{ $l->est_actif ? 'actif' : 'inactif' }}, {{ $l->is_draft ? 'brouillon' : 'publié' }})
@endforeach
@endif
@if($allQuestions->count() > 0)

QUESTIONS :
@foreach($allQuestions as $q)
- ID {{ $q->id }} (Leçon {{ $q->lesson_id }}) : "{{ Str::limit($q->question, 80) }}" [{{ $q->type }}] ({{ $q->points }} pts)
@endforeach
@endif

MODIFICATIONS À EFFECTUER :
[DÉCRIS ICI CE QUE TU VEUX MODIFIER, ex: "Renommer tous les modules pour ajouter un numéro, corriger les descriptions des leçons du module 2, changer les points de toutes les questions à 10"]</template>

{{-- ============================================================ --}}
{{-- PREPROMPT : DELETE (Supprimer en masse) --}}
{{-- ============================================================ --}}
<template id="ba-preprompt-template-delete">Tu es un assistant qui génère du JSON pour supprimer en masse des éléments d'une plateforme de cours.

OPÉRATION : SUPPRESSION EN MASSE

STRUCTURE JSON ATTENDUE :
{
  "modules": [1, 3, 5],
  "lessons": [2, 4, 6],
  "questions": [10, 11, 12]
}

RÈGLES :
- Chaque tableau contient les IDs (entiers) des éléments à supprimer
- Les tableaux "modules", "lessons" et "questions" sont tous optionnels
- ATTENTION : Supprimer un module supprime TOUTES ses leçons et questions (cascade)
- ATTENTION : Supprimer une leçon supprime TOUTES ses questions (cascade)
- Pas besoin de lister les leçons/questions d'un module si tu supprimes le module entier
- Réponds UNIQUEMENT avec le JSON, sans commentaire ni markdown

ÉLÉMENTS EXISTANTS :
@if($modules->count() > 0)
MODULES :
@foreach($modules as $m)
- ID {{ $m->id }} : "{{ $m->titre }}" ({{ $m->lessons->count() }} leçons)
@endforeach
@endif
@if($allLessons->count() > 0)

LEÇONS :
@foreach($allLessons as $l)
- ID {{ $l->id }} (Module {{ $l->module_id }}) : "{{ $l->titre }}" [{{ $l->type }}]
@endforeach
@endif
@if($allQuestions->count() > 0)

QUESTIONS :
@foreach($allQuestions as $q)
- ID {{ $q->id }} (Leçon {{ $q->lesson_id }}) : "{{ Str::limit($q->question, 80) }}" [{{ $q->type }}]
@endforeach
@endif

ÉLÉMENTS À SUPPRIMER :
[DÉCRIS ICI CE QUE TU VEUX SUPPRIMER, ex: "Supprimer les modules 3 et 5, et les leçons quiz du module 1"]</template>

{{-- ============================================================ --}}
{{-- PREPROMPT : TOGGLE (Basculer les états) --}}
{{-- ============================================================ --}}
<template id="ba-preprompt-template-toggle">Tu es un assistant qui génère du JSON pour basculer les états d'éléments d'une plateforme de cours (activer/désactiver, publier/dépublier).

OPÉRATION : BASCULER LES ÉTATS EN MASSE

STRUCTURE JSON ATTENDUE :
{
  "activer_modules": [1, 2],
  "desactiver_modules": [3, 4],
  "activer_lecons": [5, 6],
  "desactiver_lecons": [7, 8],
  "publier_lecons": [5, 6],
  "depublier_lecons": [9, 10]
}

CLÉS DISPONIBLES :
- "activer_modules" : IDs des modules à rendre actifs (est_actif = true)
- "desactiver_modules" : IDs des modules à rendre inactifs (est_actif = false)
- "activer_lecons" : IDs des leçons à rendre actives (est_actif = true)
- "desactiver_lecons" : IDs des leçons à rendre inactives (est_actif = false)
- "publier_lecons" : IDs des leçons à publier (is_draft = false, published_at = maintenant si jamais publié)
- "depublier_lecons" : IDs des leçons à dépublier (is_draft = true, redevient brouillon)

RÈGLES :
- Toutes les clés sont optionnelles, inclure seulement celles nécessaires
- Les valeurs sont des tableaux d'IDs (entiers)
- Un module désactivé cache toutes ses leçons côté public
- Publier ≠ Activer : une leçon peut être publiée mais inactive, ou brouillon mais active
- Réponds UNIQUEMENT avec le JSON, sans commentaire ni markdown

ÉTAT ACTUEL :
@if($modules->count() > 0)
MODULES :
@foreach($modules as $m)
- ID {{ $m->id }} : "{{ $m->titre }}" → {{ $m->est_actif ? '✅ ACTIF' : '❌ INACTIF' }}
@endforeach
@endif
@if($allLessons->count() > 0)

LEÇONS :
@foreach($allLessons as $l)
- ID {{ $l->id }} (Module {{ $l->module_id }}) : "{{ $l->titre }}" → {{ $l->est_actif ? '✅ actif' : '❌ inactif' }} / {{ $l->is_draft ? '📝 brouillon' : '📢 publié' }}
@endforeach
@endif

CHANGEMENTS SOUHAITÉS :
[DÉCRIS ICI LES CHANGEMENTS D'ÉTAT, ex: "Désactiver tous les modules sauf le premier, publier toutes les leçons du module 1, dépublier les leçons du module 3"]</template>

{{-- ============================================================ --}}
{{-- PREPROMPT : REORDER (Réordonner) --}}
{{-- ============================================================ --}}
<template id="ba-preprompt-template-reorder">Tu es un assistant qui génère du JSON pour réordonner des éléments d'une plateforme de cours.

OPÉRATION : RÉORDONNER EN MASSE

STRUCTURE JSON ATTENDUE :
{
  "modules": [3, 1, 2, 5, 4],
  "lessons": {
    "1": [5, 3, 4, 1, 2],
    "2": [8, 7, 6]
  }
}

RÈGLES :
- "modules" : tableau des IDs de modules dans le NOUVEL ordre souhaité (index 0 = ordre 0, index 1 = ordre 1, etc.)
- "lessons" : objet où chaque clé est un ID de module et la valeur est le tableau des IDs de leçons dans le NOUVEL ordre
- Les deux clés sont optionnelles, inclure seulement celles nécessaires
- IMPORTANT : Inclure TOUS les IDs existants pour un réordonnancement complet, ou seulement certains pour un réordonnancement partiel
- Réponds UNIQUEMENT avec le JSON, sans commentaire ni markdown

ORDRE ACTUEL :
@if($modules->count() > 0)
MODULES (ordre actuel) :
@foreach($modules->sortBy('ordre') as $m)
- Position {{ $m->ordre }} → ID {{ $m->id }} : "{{ $m->titre }}"
@endforeach
@endif
@if($modules->count() > 0)
@foreach($modules as $m)
@if($m->lessons->count() > 0)

LEÇONS DU MODULE ID {{ $m->id }} "{{ $m->titre }}" (ordre actuel) :
@foreach($m->lessons->sortBy('ordre') as $l)
- Position {{ $l->ordre }} → ID {{ $l->id }} : "{{ $l->titre }}"
@endforeach
@endif
@endforeach
@endif

NOUVEL ORDRE SOUHAITÉ :
[DÉCRIS ICI L'ORDRE SOUHAITÉ, ex: "Mettre le module 'Avancé' en premier, inverser l'ordre des leçons du module 2"]</template>

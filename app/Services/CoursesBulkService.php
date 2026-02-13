<?php

namespace App\Services;

use App\Models\CourseModule;
use App\Models\CourseLesson;
use App\Models\QuizQuestion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CoursesBulkService
{
    /**
     * Types de blocs supportés par le système
     */
    public const SUPPORTED_BLOCK_TYPES = [
        'text', 'heading', 'image', 'video', 'iframe', 'gallery',
        'columns', 'divider', 'code', 'callout', 'steps',
        'checklist', 'exercise', 'quiz_block', 'embed',
    ];

    // =====================================================================
    // BULK FILL (Remplissage IA)
    // =====================================================================

    /**
     * Valider un remplissage IA (dry-run)
     */
    public function validateFill(array $data, string $mode, ?int $targetId = null): array
    {
        $errors = [];
        $summary = ['modules' => 0, 'lessons' => 0, 'questions' => 0, 'blocks' => 0];

        switch ($mode) {
            case 'global':
                $this->validateGlobalData($data, $errors, $summary);
                break;
            case 'module':
                if (!$targetId || !CourseModule::find($targetId)) {
                    $errors[] = 'Module cible introuvable (ID: ' . $targetId . ')';
                } else {
                    $this->validateModuleData($data, $errors, $summary);
                }
                break;
            case 'lesson':
                if (!$targetId || !CourseLesson::find($targetId)) {
                    $errors[] = 'Leçon cible introuvable (ID: ' . $targetId . ')';
                } else {
                    $this->validateLessonBlocksData($data, $errors, $summary);
                }
                break;
        }

        return ['success' => empty($errors), 'errors' => $errors, 'summary' => $summary];
    }

    /**
     * Exécuter un remplissage IA (dans une transaction)
     */
    public function executeFill(array $data, string $mode, ?int $targetId = null): array
    {
        return DB::transaction(function () use ($data, $mode, $targetId) {
            $created = ['modules' => 0, 'lessons' => 0, 'questions' => 0, 'blocks' => 0];

            switch ($mode) {
                case 'global':
                    $this->insertGlobalData($data, $created);
                    break;
                case 'module':
                    $module = CourseModule::findOrFail($targetId);
                    $this->insertModuleData($data, $module, $created);
                    break;
                case 'lesson':
                    $lesson = CourseLesson::findOrFail($targetId);
                    $this->insertLessonData($data, $lesson, $created);
                    break;
            }

            return $created;
        });
    }

    // =====================================================================
    // BULK ACTION (Update / Delete / Toggle / Reorder)
    // =====================================================================

    /**
     * Valider une commande bulk (dry-run)
     */
    public function validateAction(array $data, string $action): array
    {
        $errors = [];
        $summary = [];

        switch ($action) {
            case 'update':
                $summary = ['modules' => 0, 'lessons' => 0, 'questions' => 0];
                $this->validateBulkUpdate($data, $errors, $summary);
                break;
            case 'delete':
                $summary = ['modules' => 0, 'lessons' => 0, 'questions' => 0];
                $this->validateBulkDelete($data, $errors, $summary);
                break;
            case 'toggle':
                $summary = ['activations' => 0, 'desactivations' => 0, 'publications' => 0, 'depublications' => 0];
                $this->validateBulkToggle($data, $errors, $summary);
                break;
            case 'reorder':
                $summary = ['modules' => 0, 'lessons' => 0];
                $this->validateBulkReorder($data, $errors, $summary);
                break;
        }

        return ['success' => empty($errors), 'errors' => $errors, 'summary' => $summary];
    }

    /**
     * Exécuter une commande bulk (dans une transaction)
     */
    public function executeAction(array $data, string $action): array
    {
        return DB::transaction(function () use ($data, $action) {
            switch ($action) {
                case 'update':
                    return $this->executeBulkUpdate($data);
                case 'delete':
                    return $this->executeBulkDelete($data);
                case 'toggle':
                    return $this->executeBulkToggle($data);
                case 'reorder':
                    return $this->executeBulkReorder($data);
                default:
                    return [];
            }
        });
    }

    // =====================================================================
    // EXPORT
    // =====================================================================

    /**
     * Exporter toute la structure des cours en JSON
     */
    public function exportAll(): array
    {
        $modules = CourseModule::orderBy('ordre')
            ->with(['lessons' => function ($q) {
                $q->orderBy('ordre')->with('quizQuestions');
            }])
            ->get();

        $result = ['modules' => []];

        foreach ($modules as $module) {
            $moduleData = [
                'id' => $module->id,
                'titre' => $module->titre,
                'description' => $module->description,
                'video_url' => $module->video_url,
                'ordre' => $module->ordre,
                'est_actif' => $module->est_actif,
                'lessons' => [],
            ];

            foreach ($module->lessons as $lesson) {
                $lessonData = [
                    'id' => $lesson->id,
                    'titre' => $lesson->titre,
                    'description' => $lesson->description,
                    'type' => $lesson->type,
                    'ordre' => $lesson->ordre,
                    'points_quiz' => $lesson->points_quiz,
                    'est_actif' => $lesson->est_actif,
                    'is_draft' => $lesson->is_draft,
                    'published_at' => $lesson->published_at?->toIso8601String(),
                    'blocks_count' => is_array($lesson->contenu_blocks_json) ? count($lesson->contenu_blocks_json) : 0,
                ];

                if ($lesson->type === 'quiz') {
                    $lessonData['questions'] = $lesson->quizQuestions->map(function ($q) {
                        return [
                            'id' => $q->id,
                            'question' => $q->question,
                            'type' => $q->type,
                            'options' => $q->options_json,
                            'bonne_reponse' => $q->bonne_reponse,
                            'points' => $q->points,
                            'ordre' => $q->ordre,
                        ];
                    })->toArray();
                }

                $moduleData['lessons'][] = $lessonData;
            }

            $result['modules'][] = $moduleData;
        }

        return $result;
    }

    /**
     * Retourner la documentation des formats JSON
     */
    public function getJsonSchema(): string
    {
        return <<<'SCHEMA'
=== FORMATS JSON POUR LA GESTION DES COURS ===

--- REMPLISSAGE (add) ---

Mode "global" : Créer des modules complets
{
  "modules": [
    {
      "titre": "Nom du module",
      "description": "Description",
      "video_url": "",
      "est_actif": true,
      "lessons": [
        {
          "titre": "Nom de la leçon",
          "description": "Description",
          "type": "course|quiz",
          "est_actif": true,
          "blocks": [
            { "type": "heading", "content": { "text": "Titre", "level": 1 }, "settings": {} },
            { "type": "text", "content": { "html": "<p>Contenu</p>" }, "settings": {} }
          ],
          "questions": [
            { "question": "?", "type": "multiple_choice", "options": ["A","B","C"], "bonne_reponse": "A", "points": 5 }
          ]
        }
      ]
    }
  ]
}

Mode "module" : Ajouter des leçons à un module
{ "lessons": [ ... ] }

Mode "lesson" : Ajouter des blocs à une leçon
{ "blocks": [ ... ] }

--- MODIFICATION (update) ---
{
  "modules": [ { "id": 1, "titre": "Nouveau titre" } ],
  "lessons": [ { "id": 5, "titre": "Nouveau titre", "type": "course" } ],
  "questions": [ { "id": 10, "question": "?" , "bonne_reponse": "A" } ]
}

--- SUPPRESSION (delete) ---
{
  "modules": [1, 3],
  "lessons": [5, 8],
  "questions": [10, 11]
}

--- BASCULE (toggle) ---
{
  "activer_modules": [1, 2],
  "desactiver_modules": [3],
  "activer_lecons": [5],
  "desactiver_lecons": [7],
  "publier_lecons": [5, 6],
  "depublier_lecons": [9]
}

--- RÉORDONNANCEMENT (reorder) ---
{
  "modules": [3, 1, 2, 5, 4],
  "lessons": {
    "1": [5, 3, 4, 1, 2],
    "2": [8, 7, 6]
  }
}

--- COMMANDE BRUTE (cmd) ---
{
  "action": "update|delete|toggle|reorder",
  ... (corps selon l'action choisie)
}

--- TYPES DE BLOCS ---
heading  : { "text": "Titre", "level": 1|2|3 }
text     : { "html": "<p>Contenu HTML</p>" }
callout  : { "type": "info|warning|tip|danger", "title": "Titre", "html": "<p>Contenu</p>" }
code     : { "code": "source", "language": "javascript|python|php|html|css|bash" }
steps    : { "title": "Étapes", "steps": [{ "title": "Étape", "content": "<p>...</p>" }] }
checklist: { "title": "Liste", "items": [{ "text": "Élément", "checked": false }] }
divider  : {}
exercise : { "title": "Exercice", "instruction": "<p>Consigne</p>", "hint": "<p>Indice</p>" }
image    : { "src": "", "alt": "Description", "caption": "Légende" }
video    : { "src": "", "poster": "", "title": "Titre" }
iframe   : content { "src": "https://url" }, settings { "height": 400, "rounded": true }
gallery  : { "title": "", "images": [{ "src": "", "alt": "" }], "columns": 3 }
columns  : { "columns": 2, "content": [{ "html": "<p>Col1</p>" }, { "html": "<p>Col2</p>" }] }
quiz_block: { "question": "?", "type": "multiple_choice", "options": ["A","B"], "correctAnswer": "A", "explanation": "<p>...</p>" }
embed    : { "url": "", "title": "Titre", "type": "pdf|document" }

--- TYPES DE QUESTIONS QUIZ ---
multiple_choice : options[] (min 2) + bonne_reponse (doit être dans options)
true_false      : options ["Vrai", "Faux"] + bonne_reponse
text            : bonne_reponse (texte attendu)
SCHEMA;
    }

    // =====================================================================
    // VALIDATION FILL — Méthodes privées
    // =====================================================================

    private function validateGlobalData(array $data, array &$errors, array &$summary): void
    {
        if (!isset($data['modules']) || !is_array($data['modules'])) {
            $errors[] = 'Clé "modules" manquante ou invalide. Le JSON doit contenir un tableau "modules".';
            return;
        }

        foreach ($data['modules'] as $mi => $module) {
            $prefix = "Module #" . ($mi + 1);

            if (empty($module['titre'])) {
                $errors[] = "{$prefix} : champ \"titre\" requis.";
            }

            $summary['modules']++;

            if (isset($module['lessons']) && is_array($module['lessons'])) {
                foreach ($module['lessons'] as $li => $lesson) {
                    $this->validateLessonEntry($lesson, $li, "{$prefix} > Leçon #" . ($li + 1), $errors, $summary);
                }
            }
        }
    }

    private function validateModuleData(array $data, array &$errors, array &$summary): void
    {
        if (!isset($data['lessons']) || !is_array($data['lessons'])) {
            $errors[] = 'Clé "lessons" manquante ou invalide. Le JSON doit contenir un tableau "lessons".';
            return;
        }

        foreach ($data['lessons'] as $li => $lesson) {
            $this->validateLessonEntry($lesson, $li, "Leçon #" . ($li + 1), $errors, $summary);
        }
    }

    private function validateLessonBlocksData(array $data, array &$errors, array &$summary): void
    {
        if (!isset($data['blocks']) || !is_array($data['blocks'])) {
            $errors[] = 'Clé "blocks" manquante ou invalide. Le JSON doit contenir un tableau "blocks".';
            return;
        }

        foreach ($data['blocks'] as $bi => $block) {
            $this->validateBlockEntry($block, $bi, "Bloc #" . ($bi + 1), $errors);
            $summary['blocks']++;
        }
    }

    private function validateLessonEntry(array $lesson, int $index, string $prefix, array &$errors, array &$summary): void
    {
        if (empty($lesson['titre'])) {
            $errors[] = "{$prefix} : champ \"titre\" requis.";
        }

        $type = $lesson['type'] ?? 'course';
        if (!in_array($type, ['course', 'quiz'])) {
            $errors[] = "{$prefix} : type \"{$type}\" invalide (course ou quiz).";
        }

        $summary['lessons']++;

        if (isset($lesson['blocks']) && is_array($lesson['blocks'])) {
            foreach ($lesson['blocks'] as $bi => $block) {
                $this->validateBlockEntry($block, $bi, "{$prefix} > Bloc #" . ($bi + 1), $errors);
                $summary['blocks']++;
            }
        }

        if ($type === 'quiz' && isset($lesson['questions']) && is_array($lesson['questions'])) {
            foreach ($lesson['questions'] as $qi => $question) {
                $qPrefix = "{$prefix} > Question #" . ($qi + 1);

                if (empty($question['question'])) {
                    $errors[] = "{$qPrefix} : champ \"question\" requis.";
                }

                $qType = $question['type'] ?? 'multiple_choice';
                if (!in_array($qType, ['multiple_choice', 'true_false', 'text'])) {
                    $errors[] = "{$qPrefix} : type \"{$qType}\" invalide.";
                }

                if ($qType === 'multiple_choice') {
                    if (empty($question['options']) || !is_array($question['options']) || count($question['options']) < 2) {
                        $errors[] = "{$qPrefix} : au moins 2 options requises pour un QCM.";
                    }
                    if (!empty($question['options']) && !empty($question['bonne_reponse'])) {
                        if (!in_array($question['bonne_reponse'], $question['options'])) {
                            $errors[] = "{$qPrefix} : la bonne réponse \"{$question['bonne_reponse']}\" n'est pas dans les options.";
                        }
                    }
                }

                if (empty($question['bonne_reponse']) && $question['bonne_reponse'] !== '0' && $question['bonne_reponse'] !== false) {
                    $errors[] = "{$qPrefix} : champ \"bonne_reponse\" requis.";
                }

                $summary['questions']++;
            }
        }
    }

    private function validateBlockEntry(array $block, int $index, string $prefix, array &$errors): void
    {
        if (empty($block['type'])) {
            $errors[] = "{$prefix} : champ \"type\" requis.";
            return;
        }

        if (!in_array($block['type'], self::SUPPORTED_BLOCK_TYPES)) {
            $errors[] = "{$prefix} : type de bloc \"{$block['type']}\" non reconnu. Types supportés : " . implode(', ', self::SUPPORTED_BLOCK_TYPES);
        }

        if (!isset($block['content']) || !is_array($block['content'])) {
            $errors[] = "{$prefix} : champ \"content\" requis (objet).";
        }

        $content = $block['content'] ?? [];
        switch ($block['type'] ?? '') {
            case 'text':
                if (empty($content['html'])) $errors[] = "{$prefix} (text) : champ \"content.html\" requis.";
                break;
            case 'heading':
                if (empty($content['text'])) $errors[] = "{$prefix} (heading) : champ \"content.text\" requis.";
                break;
            case 'code':
                if (!isset($content['code'])) $errors[] = "{$prefix} (code) : champ \"content.code\" requis.";
                break;
            case 'callout':
                if (empty($content['html'])) $errors[] = "{$prefix} (callout) : champ \"content.html\" requis.";
                break;
            case 'steps':
                if (!isset($content['steps']) || !is_array($content['steps'])) $errors[] = "{$prefix} (steps) : champ \"content.steps\" requis (tableau).";
                break;
            case 'checklist':
                if (!isset($content['items']) || !is_array($content['items'])) $errors[] = "{$prefix} (checklist) : champ \"content.items\" requis (tableau).";
                break;
        }
    }

    // =====================================================================
    // INSERTION FILL — Méthodes privées
    // =====================================================================

    public function insertGlobalData(array $data, array &$created): void
    {
        $maxOrdre = CourseModule::max('ordre') ?? -1;

        foreach ($data['modules'] as $mi => $moduleData) {
            $maxOrdre++;

            $module = CourseModule::create([
                'titre' => $moduleData['titre'],
                'description' => $moduleData['description'] ?? null,
                'video_url' => $moduleData['video_url'] ?? null,
                'ordre' => $moduleData['ordre'] ?? $maxOrdre,
                'est_actif' => $moduleData['est_actif'] ?? true,
            ]);

            $created['modules']++;

            if (isset($moduleData['lessons']) && is_array($moduleData['lessons'])) {
                $this->insertModuleData($moduleData, $module, $created);
            }
        }
    }

    public function insertModuleData(array $data, CourseModule $module, array &$created): void
    {
        $lessons = $data['lessons'] ?? [];
        $maxOrdre = $module->lessons()->max('ordre') ?? -1;

        foreach ($lessons as $li => $lessonData) {
            $maxOrdre++;
            $type = $lessonData['type'] ?? 'course';

            $blocks = [];
            if (isset($lessonData['blocks']) && is_array($lessonData['blocks'])) {
                foreach ($lessonData['blocks'] as $block) {
                    $blocks[] = [
                        'id' => 'block-' . uniqid(),
                        'type' => $block['type'],
                        'content' => $block['content'] ?? [],
                        'settings' => $block['settings'] ?? [],
                    ];
                    $created['blocks']++;
                }
            }

            if (empty($blocks)) {
                $blocks = CourseLesson::getDefaultBlocks();
            }

            $lesson = CourseLesson::create([
                'module_id' => $module->id,
                'titre' => $lessonData['titre'],
                'description' => $lessonData['description'] ?? null,
                'contenu_blocks_json' => $blocks,
                'type' => $type,
                'ordre' => $lessonData['ordre'] ?? $maxOrdre,
                'points_quiz' => $lessonData['points_quiz'] ?? 0,
                'est_actif' => $lessonData['est_actif'] ?? true,
                'is_draft' => true,
            ]);

            try {
                $html = $lesson->generateHtmlFromBlocks();
                $lesson->update(['contenu_rich_html' => $html]);
            } catch (\Exception $e) {
                \Log::warning('Bulk fill: impossible de générer le HTML pour la leçon ' . $lesson->id, ['error' => $e->getMessage()]);
            }

            $created['lessons']++;

            if ($type === 'quiz' && isset($lessonData['questions']) && is_array($lessonData['questions'])) {
                $qOrdre = 0;
                foreach ($lessonData['questions'] as $questionData) {
                    QuizQuestion::create([
                        'lesson_id' => $lesson->id,
                        'question' => $questionData['question'],
                        'type' => $questionData['type'] ?? 'multiple_choice',
                        'options_json' => $questionData['options'] ?? [],
                        'bonne_reponse' => (string) $questionData['bonne_reponse'],
                        'points' => $questionData['points'] ?? 1,
                        'ordre' => $questionData['ordre'] ?? $qOrdre,
                    ]);
                    $qOrdre++;
                    $created['questions']++;
                }
            }
        }
    }

    public function insertLessonData(array $data, CourseLesson $lesson, array &$created): void
    {
        $blocks = [];
        foreach ($data['blocks'] as $block) {
            $blocks[] = [
                'id' => 'block-' . uniqid(),
                'type' => $block['type'],
                'content' => $block['content'] ?? [],
                'settings' => $block['settings'] ?? [],
            ];
            $created['blocks']++;
        }

        $lesson->update([
            'contenu_blocks_json' => $blocks,
            'is_draft' => true,
        ]);

        try {
            $lesson->refresh();
            $html = $lesson->generateHtmlFromBlocks();
            $lesson->update(['contenu_rich_html' => $html]);
        } catch (\Exception $e) {
            \Log::warning('Bulk fill: impossible de générer le HTML pour la leçon ' . $lesson->id, ['error' => $e->getMessage()]);
        }
    }

    // =====================================================================
    // VALIDATION BULK ACTION — Méthodes privées
    // =====================================================================

    private function validateBulkUpdate(array $data, array &$errors, array &$summary): void
    {
        if (isset($data['modules']) && is_array($data['modules'])) {
            foreach ($data['modules'] as $i => $mod) {
                $prefix = "Module #" . ($i + 1);
                if (empty($mod['id'])) { $errors[] = "{$prefix} : champ \"id\" requis."; continue; }
                if (!CourseModule::find($mod['id'])) { $errors[] = "{$prefix} : module ID {$mod['id']} introuvable."; continue; }
                if (isset($mod['titre']) && empty($mod['titre'])) $errors[] = "{$prefix} : le titre ne peut pas être vide.";
                if (isset($mod['est_actif']) && !is_bool($mod['est_actif'])) $errors[] = "{$prefix} : \"est_actif\" doit être true ou false.";
                if (isset($mod['video_url']) && !empty($mod['video_url']) && !filter_var($mod['video_url'], FILTER_VALIDATE_URL)) $errors[] = "{$prefix} : \"video_url\" invalide.";
                $summary['modules']++;
            }
        }

        if (isset($data['lessons']) && is_array($data['lessons'])) {
            foreach ($data['lessons'] as $i => $les) {
                $prefix = "Leçon #" . ($i + 1);
                if (empty($les['id'])) { $errors[] = "{$prefix} : champ \"id\" requis."; continue; }
                if (!CourseLesson::find($les['id'])) { $errors[] = "{$prefix} : leçon ID {$les['id']} introuvable."; continue; }
                if (isset($les['titre']) && empty($les['titre'])) $errors[] = "{$prefix} : le titre ne peut pas être vide.";
                if (isset($les['type']) && !in_array($les['type'], ['course', 'quiz'])) $errors[] = "{$prefix} : type invalide (course ou quiz).";
                $summary['lessons']++;
            }
        }

        if (isset($data['questions']) && is_array($data['questions'])) {
            foreach ($data['questions'] as $i => $q) {
                $prefix = "Question #" . ($i + 1);
                if (empty($q['id'])) { $errors[] = "{$prefix} : champ \"id\" requis."; continue; }
                if (!QuizQuestion::find($q['id'])) { $errors[] = "{$prefix} : question ID {$q['id']} introuvable."; continue; }
                if (isset($q['type']) && !in_array($q['type'], ['multiple_choice', 'true_false', 'text'])) $errors[] = "{$prefix} : type invalide.";
                if (isset($q['options']) && ($q['type'] ?? '') === 'multiple_choice' && count($q['options']) < 2) $errors[] = "{$prefix} : au moins 2 options requises pour un QCM.";
                $summary['questions']++;
            }
        }

        if ($summary['modules'] === 0 && $summary['lessons'] === 0 && $summary['questions'] === 0) {
            $errors[] = 'Aucun élément à modifier. Ajoutez au moins un tableau "modules", "lessons" ou "questions".';
        }
    }

    private function validateBulkDelete(array $data, array &$errors, array &$summary): void
    {
        foreach (['modules' => CourseModule::class, 'lessons' => CourseLesson::class, 'questions' => QuizQuestion::class] as $key => $model) {
            if (isset($data[$key]) && is_array($data[$key])) {
                foreach ($data[$key] as $i => $id) {
                    if (!is_int($id)) { $errors[] = "{$key}[{$i}] : ID invalide (entier attendu)."; continue; }
                    if (!$model::find($id)) { $errors[] = ucfirst(rtrim($key, 's')) . " ID {$id} introuvable."; continue; }
                    $summary[$key]++;
                }
            }
        }

        if ($summary['modules'] === 0 && $summary['lessons'] === 0 && $summary['questions'] === 0) {
            $errors[] = 'Aucun élément à supprimer. Ajoutez au moins un tableau "modules", "lessons" ou "questions" avec des IDs.';
        }
    }

    private function validateBulkToggle(array $data, array &$errors, array &$summary): void
    {
        $validKeys = ['activer_modules', 'desactiver_modules', 'activer_lecons', 'desactiver_lecons', 'publier_lecons', 'depublier_lecons'];
        $hasData = false;

        foreach ($validKeys as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                $hasData = true;
                foreach ($data[$key] as $i => $id) {
                    if (!is_int($id)) { $errors[] = "{$key}[{$i}] : ID invalide (entier attendu)."; continue; }
                    if (str_contains($key, 'modules')) {
                        if (!CourseModule::find($id)) $errors[] = "{$key} : Module ID {$id} introuvable.";
                    } else {
                        if (!CourseLesson::find($id)) $errors[] = "{$key} : Leçon ID {$id} introuvable.";
                    }
                }
                $count = count($data[$key]);
                if (str_contains($key, 'activer') && !str_contains($key, 'desactiver')) $summary['activations'] += $count;
                if (str_contains($key, 'desactiver')) $summary['desactivations'] += $count;
                if ($key === 'publier_lecons') $summary['publications'] += $count;
                if ($key === 'depublier_lecons') $summary['depublications'] += $count;
            }
        }

        if (!$hasData) {
            $errors[] = 'Aucune opération de bascule trouvée. Clés acceptées : ' . implode(', ', $validKeys);
        }
    }

    private function validateBulkReorder(array $data, array &$errors, array &$summary): void
    {
        if (isset($data['modules']) && is_array($data['modules'])) {
            foreach ($data['modules'] as $i => $id) {
                if (!is_int($id)) { $errors[] = "modules[{$i}] : ID invalide."; continue; }
                if (!CourseModule::find($id)) { $errors[] = "Module ID {$id} introuvable."; continue; }
            }
            $summary['modules'] = count($data['modules']);
        }

        if (isset($data['lessons']) && is_array($data['lessons'])) {
            foreach ($data['lessons'] as $moduleId => $lessonIds) {
                if (!CourseModule::find($moduleId)) { $errors[] = "Module parent ID {$moduleId} introuvable."; continue; }
                if (!is_array($lessonIds)) { $errors[] = "lessons[{$moduleId}] : tableau d'IDs attendu."; continue; }
                foreach ($lessonIds as $i => $lid) {
                    if (!is_int($lid)) { $errors[] = "lessons[{$moduleId}][{$i}] : ID invalide."; continue; }
                    $lesson = CourseLesson::find($lid);
                    if (!$lesson) { $errors[] = "Leçon ID {$lid} introuvable."; }
                    elseif ($lesson->module_id != $moduleId) { $errors[] = "Leçon ID {$lid} n'appartient pas au module {$moduleId}."; }
                }
                $summary['lessons'] += count($lessonIds);
            }
        }

        if ($summary['modules'] === 0 && $summary['lessons'] === 0) {
            $errors[] = 'Aucun élément à réordonner.';
        }
    }

    // =====================================================================
    // EXÉCUTION BULK ACTION — Méthodes privées
    // =====================================================================

    private function executeBulkUpdate(array $data): array
    {
        $affected = ['modules' => 0, 'lessons' => 0, 'questions' => 0];

        if (isset($data['modules']) && is_array($data['modules'])) {
            foreach ($data['modules'] as $mod) {
                $module = CourseModule::find($mod['id']);
                if (!$module) continue;
                $u = [];
                if (isset($mod['titre'])) $u['titre'] = $mod['titre'];
                if (isset($mod['description'])) $u['description'] = $mod['description'];
                if (isset($mod['video_url'])) $u['video_url'] = $mod['video_url'] ?: null;
                if (isset($mod['ordre'])) $u['ordre'] = (int) $mod['ordre'];
                if (isset($mod['est_actif'])) $u['est_actif'] = (bool) $mod['est_actif'];
                if (!empty($u)) { $module->update($u); $affected['modules']++; }
            }
        }

        if (isset($data['lessons']) && is_array($data['lessons'])) {
            foreach ($data['lessons'] as $les) {
                $lesson = CourseLesson::find($les['id']);
                if (!$lesson) continue;
                $u = [];
                if (isset($les['titre'])) $u['titre'] = $les['titre'];
                if (isset($les['description'])) $u['description'] = $les['description'];
                if (isset($les['type'])) $u['type'] = $les['type'];
                if (isset($les['ordre'])) $u['ordre'] = (int) $les['ordre'];
                if (isset($les['points_quiz'])) $u['points_quiz'] = (int) $les['points_quiz'];
                if (isset($les['est_actif'])) $u['est_actif'] = (bool) $les['est_actif'];
                if (isset($les['blocks']) && is_array($les['blocks'])) {
                    $blocks = [];
                    foreach ($les['blocks'] as $block) {
                        $blocks[] = ['id' => $block['id'] ?? 'block-' . uniqid(), 'type' => $block['type'], 'content' => $block['content'] ?? [], 'settings' => $block['settings'] ?? []];
                    }
                    $u['contenu_blocks_json'] = $blocks;
                    $u['is_draft'] = true;
                }
                if (!empty($u)) {
                    $lesson->update($u);
                    if (isset($u['contenu_blocks_json'])) {
                        try { $lesson->refresh(); $lesson->update(['contenu_rich_html' => $lesson->generateHtmlFromBlocks()]); } catch (\Exception $e) {}
                    }
                    $affected['lessons']++;
                }
            }
        }

        if (isset($data['questions']) && is_array($data['questions'])) {
            foreach ($data['questions'] as $q) {
                $question = QuizQuestion::find($q['id']);
                if (!$question) continue;
                $u = [];
                if (isset($q['question'])) $u['question'] = $q['question'];
                if (isset($q['type'])) $u['type'] = $q['type'];
                if (isset($q['options'])) $u['options_json'] = $q['options'];
                if (isset($q['bonne_reponse'])) $u['bonne_reponse'] = (string) $q['bonne_reponse'];
                if (isset($q['points'])) $u['points'] = (int) $q['points'];
                if (isset($q['ordre'])) $u['ordre'] = (int) $q['ordre'];
                if (!empty($u)) { $question->update($u); $affected['questions']++; }
            }
        }

        return $affected;
    }

    private function executeBulkDelete(array $data): array
    {
        $affected = ['modules' => 0, 'lessons' => 0, 'questions' => 0];

        if (isset($data['questions']) && is_array($data['questions'])) {
            foreach ($data['questions'] as $id) {
                $q = QuizQuestion::find($id);
                if ($q) { $q->delete(); $affected['questions']++; }
            }
        }

        if (isset($data['lessons']) && is_array($data['lessons'])) {
            foreach ($data['lessons'] as $id) {
                $l = CourseLesson::find($id);
                if ($l) {
                    if ($l->image_path) Storage::disk('public')->delete($l->image_path);
                    $l->delete();
                    $affected['lessons']++;
                }
            }
        }

        if (isset($data['modules']) && is_array($data['modules'])) {
            foreach ($data['modules'] as $id) {
                $m = CourseModule::find($id);
                if ($m) {
                    if ($m->image_path) Storage::disk('public')->delete($m->image_path);
                    $m->delete();
                    $affected['modules']++;
                }
            }
        }

        return $affected;
    }

    private function executeBulkToggle(array $data): array
    {
        $affected = ['activations' => 0, 'desactivations' => 0, 'publications' => 0, 'depublications' => 0];

        if (isset($data['activer_modules'])) {
            foreach ($data['activer_modules'] as $id) { CourseModule::where('id', $id)->update(['est_actif' => true]); $affected['activations']++; }
        }
        if (isset($data['desactiver_modules'])) {
            foreach ($data['desactiver_modules'] as $id) { CourseModule::where('id', $id)->update(['est_actif' => false]); $affected['desactivations']++; }
        }
        if (isset($data['activer_lecons'])) {
            foreach ($data['activer_lecons'] as $id) { CourseLesson::where('id', $id)->update(['est_actif' => true]); $affected['activations']++; }
        }
        if (isset($data['desactiver_lecons'])) {
            foreach ($data['desactiver_lecons'] as $id) { CourseLesson::where('id', $id)->update(['est_actif' => false]); $affected['desactivations']++; }
        }
        if (isset($data['publier_lecons'])) {
            foreach ($data['publier_lecons'] as $id) {
                $lesson = CourseLesson::find($id);
                if ($lesson) {
                    $u = ['is_draft' => false];
                    if ($lesson->published_at === null) $u['published_at'] = now();
                    $lesson->update($u);
                    $affected['publications']++;
                }
            }
        }
        if (isset($data['depublier_lecons'])) {
            foreach ($data['depublier_lecons'] as $id) { CourseLesson::where('id', $id)->update(['is_draft' => true]); $affected['depublications']++; }
        }

        return $affected;
    }

    private function executeBulkReorder(array $data): array
    {
        $affected = ['modules' => 0, 'lessons' => 0];

        if (isset($data['modules']) && is_array($data['modules'])) {
            foreach ($data['modules'] as $index => $id) {
                CourseModule::where('id', $id)->update(['ordre' => $index]);
                $affected['modules']++;
            }
        }

        if (isset($data['lessons']) && is_array($data['lessons'])) {
            foreach ($data['lessons'] as $moduleId => $lessonIds) {
                foreach ($lessonIds as $index => $lid) {
                    CourseLesson::where('id', $lid)->where('module_id', $moduleId)->update(['ordre' => $index]);
                    $affected['lessons']++;
                }
            }
        }

        return $affected;
    }
}

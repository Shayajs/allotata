<?php

namespace App\Console\Commands;

use App\Models\CourseModule;
use App\Models\CourseLesson;
use App\Models\QuizQuestion;
use App\Services\CoursesBulkService;
use Illuminate\Console\Command;

class CoursesManageCommand extends Command
{
    protected $signature = 'courses:manage
        {action : view|modify|delete|add|json|cmd}
        {--id= : ID de l\'élément cible}
        {--module= : ID du module cible (pour add)}
        {--type= : Type d\'élément ou d\'opération (module|lesson|question|global|export|schema|import|validate)}
        {--file= : Chemin vers un fichier JSON}
        {--inline= : JSON en ligne}
        {--force : Pas de confirmation}';

    protected $description = 'Gérer les cours depuis la ligne de commande (view, modify, delete, add, json, cmd)';

    private CoursesBulkService $service;

    public function __construct(CoursesBulkService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function handle(): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'view' => $this->handleView(),
            'modify' => $this->handleModify(),
            'delete' => $this->handleDelete(),
            'add' => $this->handleAdd(),
            'json' => $this->handleJson(),
            'cmd' => $this->handleCmd(),
            default => $this->showHelp(),
        };
    }

    // =====================================================================
    // VIEW
    // =====================================================================

    private function handleView(): int
    {
        $id = $this->option('id');
        $type = $this->option('type');

        if ($id && $type === 'module') {
            return $this->viewModule((int) $id);
        }

        if ($id && $type === 'lesson') {
            return $this->viewLesson((int) $id);
        }

        return $this->viewAll();
    }

    private function viewAll(): int
    {
        $modules = CourseModule::orderBy('ordre')->with(['lessons' => fn($q) => $q->orderBy('ordre')])->get();

        if ($modules->isEmpty()) {
            $this->warn('Aucun module trouvé.');
            return 0;
        }

        $this->info("=== STRUCTURE DES COURS ({$modules->count()} modules) ===");
        $this->newLine();

        foreach ($modules as $module) {
            $status = $module->est_actif ? '<fg=green>ACTIF</>' : '<fg=red>INACTIF</>';
            $this->line("  <fg=cyan>[M:{$module->id}]</> <options=bold>{$module->titre}</> {$status} (ordre: {$module->ordre})");

            if ($module->description) {
                $this->line("         <fg=gray>" . \Str::limit($module->description, 80) . "</>");
            }

            foreach ($module->lessons as $lesson) {
                $lStatus = $lesson->est_actif ? '<fg=green>actif</>' : '<fg=red>inactif</>';
                $draft = $lesson->is_draft ? '<fg=yellow>brouillon</>' : '<fg=blue>publié</>';
                $typeLabel = $lesson->type === 'quiz' ? '<fg=magenta>quiz</>' : '<fg=white>cours</>';
                $blocksCount = is_array($lesson->contenu_blocks_json) ? count($lesson->contenu_blocks_json) : 0;

                $this->line("    <fg=gray>[L:{$lesson->id}]</> {$lesson->titre} [{$typeLabel}] {$lStatus} {$draft} ({$blocksCount} blocs)");

                if ($lesson->type === 'quiz') {
                    $qCount = $lesson->quizQuestions()->count();
                    if ($qCount > 0) {
                        $this->line("           <fg=gray>{$qCount} question(s), {$lesson->points_quiz} pts</>");
                    }
                }
            }

            $this->newLine();
        }

        $totalLessons = $modules->sum(fn($m) => $m->lessons->count());
        $this->info("Total : {$modules->count()} modules, {$totalLessons} leçons");

        return 0;
    }

    private function viewModule(int $id): int
    {
        $module = CourseModule::with(['lessons' => fn($q) => $q->orderBy('ordre')->with('quizQuestions')])->find($id);

        if (!$module) {
            $this->error("Module ID {$id} introuvable.");
            return 1;
        }

        $this->info("=== MODULE #{$module->id} : {$module->titre} ===");
        $this->table(
            ['Champ', 'Valeur'],
            [
                ['ID', $module->id],
                ['Titre', $module->titre],
                ['Description', \Str::limit($module->description ?? '-', 100)],
                ['Video URL', $module->video_url ?? '-'],
                ['Ordre', $module->ordre],
                ['Actif', $module->est_actif ? 'Oui' : 'Non'],
                ['Nb leçons', $module->lessons->count()],
            ]
        );

        if ($module->lessons->isNotEmpty()) {
            $this->newLine();
            $this->info('Leçons :');
            $this->table(
                ['ID', 'Titre', 'Type', 'Ordre', 'Actif', 'Statut', 'Blocs', 'Questions'],
                $module->lessons->map(fn($l) => [
                    $l->id,
                    \Str::limit($l->titre, 40),
                    $l->type,
                    $l->ordre,
                    $l->est_actif ? 'Oui' : 'Non',
                    $l->is_draft ? 'Brouillon' : 'Publié',
                    is_array($l->contenu_blocks_json) ? count($l->contenu_blocks_json) : 0,
                    $l->type === 'quiz' ? $l->quizQuestions->count() : '-',
                ])->toArray()
            );
        }

        return 0;
    }

    private function viewLesson(int $id): int
    {
        $lesson = CourseLesson::with(['module', 'quizQuestions'])->find($id);

        if (!$lesson) {
            $this->error("Leçon ID {$id} introuvable.");
            return 1;
        }

        $this->info("=== LEÇON #{$lesson->id} : {$lesson->titre} ===");
        $this->table(
            ['Champ', 'Valeur'],
            [
                ['ID', $lesson->id],
                ['Module', "{$lesson->module->titre} (ID: {$lesson->module_id})"],
                ['Titre', $lesson->titre],
                ['Description', \Str::limit($lesson->description ?? '-', 100)],
                ['Type', $lesson->type],
                ['Ordre', $lesson->ordre],
                ['Actif', $lesson->est_actif ? 'Oui' : 'Non'],
                ['Statut', $lesson->is_draft ? 'Brouillon' : 'Publié'],
                ['Publié le', $lesson->published_at?->format('d/m/Y H:i') ?? '-'],
                ['Points quiz', $lesson->points_quiz],
            ]
        );

        $blocks = $lesson->contenu_blocks_json;
        if (is_array($blocks) && !empty($blocks)) {
            $this->newLine();
            $this->info('Blocs (' . count($blocks) . ') :');
            $this->table(
                ['#', 'Type', 'Aperçu'],
                collect($blocks)->map(function ($b, $i) {
                    $preview = match ($b['type'] ?? '') {
                        'heading' => $b['content']['text'] ?? '',
                        'text' => strip_tags(\Str::limit($b['content']['html'] ?? '', 60)),
                        'callout' => ($b['content']['type'] ?? '') . ': ' . strip_tags(\Str::limit($b['content']['html'] ?? '', 50)),
                        'code' => \Str::limit($b['content']['code'] ?? '', 50),
                        'steps' => count($b['content']['steps'] ?? []) . ' étapes',
                        'checklist' => count($b['content']['items'] ?? []) . ' éléments',
                        'image' => $b['content']['alt'] ?? 'image',
                        'video' => $b['content']['title'] ?? 'vidéo',
                        'divider' => '---',
                        default => json_encode(\Str::limit(json_encode($b['content'] ?? []), 50)),
                    };
                    return [$i + 1, $b['type'] ?? '?', \Str::limit($preview, 60)];
                })->toArray()
            );
        }

        if ($lesson->type === 'quiz' && $lesson->quizQuestions->isNotEmpty()) {
            $this->newLine();
            $this->info('Questions (' . $lesson->quizQuestions->count() . ') :');
            $this->table(
                ['ID', 'Question', 'Type', 'Points', 'Réponse'],
                $lesson->quizQuestions->map(fn($q) => [
                    $q->id,
                    \Str::limit($q->question, 50),
                    $q->type,
                    $q->points,
                    \Str::limit($q->bonne_reponse, 30),
                ])->toArray()
            );
        }

        return 0;
    }

    // =====================================================================
    // MODIFY
    // =====================================================================

    private function handleModify(): int
    {
        $data = $this->resolveJsonData();
        if ($data === null) return 1;

        // Mode simple : --id + --type + --inline pour un seul élément
        $id = $this->option('id');
        $type = $this->option('type');
        if ($id && $type && !isset($data['modules']) && !isset($data['lessons']) && !isset($data['questions'])) {
            $data = [$type . 's' => [array_merge(['id' => (int) $id], $data)]];
        }

        $validation = $this->service->validateAction($data, 'update');
        if (!$validation['success']) {
            $this->error('Erreurs de validation :');
            foreach ($validation['errors'] as $err) $this->line("  - {$err}");
            return 1;
        }

        $this->showSummary('Modification', $validation['summary']);

        if (!$this->option('force') && !$this->confirm('Exécuter la modification ?')) {
            $this->info('Annulé.');
            return 0;
        }

        try {
            $result = $this->service->executeAction($data, 'update');
            $this->displayResult('Modification terminée', $result);
            return 0;
        } catch (\Exception $e) {
            $this->error('Erreur : ' . $e->getMessage());
            return 1;
        }
    }

    // =====================================================================
    // DELETE
    // =====================================================================

    private function handleDelete(): int
    {
        $id = $this->option('id');
        $type = $this->option('type');

        // Mode simple : --id + --type
        if ($id && $type) {
            $key = match ($type) {
                'module' => 'modules',
                'lesson' => 'lessons',
                'question' => 'questions',
                default => null,
            };
            if (!$key) {
                $this->error('Type invalide. Utilisez : module, lesson, question');
                return 1;
            }
            $data = [$key => [(int) $id]];
        } else {
            $data = $this->resolveJsonData();
            if ($data === null) return 1;
        }

        $validation = $this->service->validateAction($data, 'delete');
        if (!$validation['success']) {
            $this->error('Erreurs de validation :');
            foreach ($validation['errors'] as $err) $this->line("  - {$err}");
            return 1;
        }

        $this->showSummary('Suppression', $validation['summary']);
        $this->warn('ATTENTION : Cette action est IRRÉVERSIBLE.');

        if (!$this->option('force') && !$this->confirm('Confirmer la suppression ?', false)) {
            $this->info('Annulé.');
            return 0;
        }

        try {
            $result = $this->service->executeAction($data, 'delete');
            $this->displayResult('Suppression terminée', $result);
            return 0;
        } catch (\Exception $e) {
            $this->error('Erreur : ' . $e->getMessage());
            return 1;
        }
    }

    // =====================================================================
    // ADD
    // =====================================================================

    private function handleAdd(): int
    {
        $data = $this->resolveJsonData();
        if ($data === null) return 1;

        $type = $this->option('type') ?? 'global';
        $moduleId = $this->option('module');

        // Détection auto du mode
        if ($moduleId) {
            $type = 'module';
        } elseif (isset($data['lessons']) && !isset($data['modules'])) {
            $type = 'module';
            if (!$moduleId) {
                $this->error('Spécifiez --module=ID pour ajouter des leçons à un module.');
                return 1;
            }
        } elseif (isset($data['blocks']) && !isset($data['modules']) && !isset($data['lessons'])) {
            $type = 'lesson';
        }

        $targetId = match ($type) {
            'module' => $moduleId ? (int) $moduleId : null,
            'lesson' => $this->option('id') ? (int) $this->option('id') : null,
            default => null,
        };

        $validation = $this->service->validateFill($data, $type, $targetId);
        if (!$validation['success']) {
            $this->error('Erreurs de validation :');
            foreach ($validation['errors'] as $err) $this->line("  - {$err}");
            return 1;
        }

        $this->showSummary('Ajout', $validation['summary']);

        if (!$this->option('force') && !$this->confirm('Exécuter l\'ajout ?')) {
            $this->info('Annulé.');
            return 0;
        }

        try {
            $result = $this->service->executeFill($data, $type, $targetId);
            $this->displayResult('Ajout terminé', $result);
            return 0;
        } catch (\Exception $e) {
            $this->error('Erreur : ' . $e->getMessage());
            return 1;
        }
    }

    // =====================================================================
    // JSON
    // =====================================================================

    private function handleJson(): int
    {
        $type = $this->option('type') ?? 'schema';

        return match ($type) {
            'export' => $this->jsonExport(),
            'schema' => $this->jsonSchema(),
            'validate' => $this->jsonValidate(),
            'import' => $this->handleAdd(), // alias
            default => $this->jsonSchema(),
        };
    }

    private function jsonExport(): int
    {
        $data = $this->service->exportAll();
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $file = $this->option('file');
        if ($file) {
            file_put_contents($file, $json);
            $this->info("Export sauvegardé dans : {$file}");
        } else {
            $this->line($json);
        }

        $moduleCount = count($data['modules']);
        $lessonCount = collect($data['modules'])->sum(fn($m) => count($m['lessons']));
        $this->info("Exporté : {$moduleCount} modules, {$lessonCount} leçons");

        return 0;
    }

    private function jsonSchema(): int
    {
        $this->line($this->service->getJsonSchema());
        return 0;
    }

    private function jsonValidate(): int
    {
        $data = $this->resolveJsonData();
        if ($data === null) return 1;

        // Essayer de détecter le type
        $actionType = $this->option('type');

        if (isset($data['action'])) {
            $validation = $this->service->validateAction($data, $data['action']);
        } elseif (isset($data['modules']) && isset($data['modules'][0]['titre']) && !isset($data['modules'][0]['id'])) {
            $validation = $this->service->validateFill($data, 'global');
        } elseif (isset($data['modules']) || isset($data['lessons']) || isset($data['questions'])) {
            $validation = $this->service->validateAction($data, 'update');
        } elseif (isset($data['blocks'])) {
            $validation = $this->service->validateFill($data, 'lesson', $this->option('id') ? (int) $this->option('id') : null);
        } else {
            $this->error('Format JSON non reconnu.');
            return 1;
        }

        if ($validation['success']) {
            $this->info('Validation OK');
            $this->showSummary('Résumé', $validation['summary']);
        } else {
            $this->error('Erreurs de validation :');
            foreach ($validation['errors'] as $err) $this->line("  - {$err}");
        }

        return $validation['success'] ? 0 : 1;
    }

    // =====================================================================
    // CMD (commande brute)
    // =====================================================================

    private function handleCmd(): int
    {
        $data = $this->resolveJsonData();
        if ($data === null) return 1;

        $action = $data['action'] ?? null;
        if (!$action || !in_array($action, ['update', 'delete', 'toggle', 'reorder'])) {
            $this->error('Le JSON doit contenir un champ "action" : update, delete, toggle ou reorder');
            return 1;
        }

        // Retirer le champ action pour passer le reste au service
        unset($data['action']);

        $validation = $this->service->validateAction($data, $action);
        if (!$validation['success']) {
            $this->error('Erreurs de validation :');
            foreach ($validation['errors'] as $err) $this->line("  - {$err}");
            return 1;
        }

        $this->showSummary("Commande : {$action}", $validation['summary']);

        if ($action === 'delete') {
            $this->warn('ATTENTION : Suppression IRRÉVERSIBLE.');
        }

        if (!$this->option('force') && !$this->confirm('Exécuter ?')) {
            $this->info('Annulé.');
            return 0;
        }

        try {
            $result = $this->service->executeAction($data, $action);
            $this->displayResult('Commande exécutée', $result);
            return 0;
        } catch (\Exception $e) {
            $this->error('Erreur : ' . $e->getMessage());
            return 1;
        }
    }

    // =====================================================================
    // HELPERS
    // =====================================================================

    private function resolveJsonData(): ?array
    {
        $inline = $this->option('inline');
        $file = $this->option('file');

        if (!$inline && !$file) {
            $this->error('Spécifiez --inline=\'{"..."}\' ou --file=chemin.json');
            return null;
        }

        if ($file) {
            if (!file_exists($file)) {
                $this->error("Fichier introuvable : {$file}");
                return null;
            }
            $json = file_get_contents($file);
        } else {
            $json = $inline;
        }

        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('JSON invalide : ' . json_last_error_msg());
            return null;
        }

        return $data;
    }

    private function showSummary(string $label, array $summary): void
    {
        $this->newLine();
        $this->info("--- {$label} ---");
        foreach ($summary as $key => $val) {
            if ($val > 0) {
                $this->line("  {$key} : <fg=cyan>{$val}</>");
            }
        }
    }

    private function displayResult(string $label, array $result): void
    {
        $this->newLine();
        $this->info("=== {$label} ===");
        foreach ($result as $key => $val) {
            if ($val > 0) {
                $this->line("  {$key} : <fg=green>{$val}</>");
            }
        }
    }

    private function showHelp(): int
    {
        $this->info('=== courses:manage — Gestion des cours en CLI ===');
        $this->newLine();
        $this->line('<fg=cyan>Actions disponibles :</>');
        $this->line('  <fg=green>view</>      Voir la structure des cours');
        $this->line('  <fg=green>modify</>    Modifier des éléments existants');
        $this->line('  <fg=green>delete</>    Supprimer des éléments');
        $this->line('  <fg=green>add</>       Ajouter de nouveaux éléments (remplissage)');
        $this->line('  <fg=green>json</>      Export/import/schema/validation JSON');
        $this->line('  <fg=green>cmd</>       Commande brute (JSON avec champ "action")');
        $this->newLine();
        $this->line('<fg=cyan>Exemples :</>');
        $this->line('  php artisan courses:manage view');
        $this->line('  php artisan courses:manage view --id=3 --type=module');
        $this->line('  php artisan courses:manage view --id=5 --type=lesson');
        $this->line('  php artisan courses:manage modify --inline=\'{"modules":[{"id":1,"titre":"Nouveau"}]}\'');
        $this->line('  php artisan courses:manage delete --id=5 --type=module --force');
        $this->line('  php artisan courses:manage delete --file=delete.json');
        $this->line('  php artisan courses:manage add --file=cours.json --type=global');
        $this->line('  php artisan courses:manage add --file=lecons.json --module=3');
        $this->line('  php artisan courses:manage json --type=export --file=export.json');
        $this->line('  php artisan courses:manage json --type=schema');
        $this->line('  php artisan courses:manage json --type=validate --file=data.json');
        $this->line('  php artisan courses:manage cmd --inline=\'{"action":"toggle","activer_modules":[1,2]}\'');

        return 0;
    }
}

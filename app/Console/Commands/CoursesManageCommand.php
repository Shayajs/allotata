<?php

namespace App\Console\Commands;

use App\Models\CourseModule;
use App\Models\CourseLesson;
use App\Models\QuizQuestion;
use App\Models\UserLessonProgress;
use App\Models\UserModuleProgress;
use App\Services\CoursesBulkService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CoursesManageCommand extends Command
{
    protected $signature = 'courses:manage
        {action : help|view|tree|status|stats|search|modify|delete|add|duplicate|move|publish|backup|restore|diff|clean|json|cmd}
        {--id= : ID de l\'élément cible}
        {--module= : ID du module cible}
        {--type= : Type (module|lesson|question|global|export|schema|import|validate)}
        {--file= : Chemin vers un fichier JSON}
        {--inline= : JSON en ligne}
        {--query= : Terme de recherche}
        {--to= : Destination (module ID pour move)}
        {--force : Pas de confirmation}
        {--drafts : Filtrer les brouillons}
        {--inactive : Filtrer les inactifs}
        {--compact : Affichage compact}';

    protected $description = 'Gestionnaire complet des cours en CLI — 18 sous-commandes';

    private CoursesBulkService $service;

    public function __construct(CoursesBulkService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function handle(): int
    {
        return match ($this->argument('action')) {
            'help' => $this->showHelp(),
            'view' => $this->handleView(),
            'tree' => $this->handleTree(),
            'status' => $this->handleStatus(),
            'stats' => $this->handleStats(),
            'search' => $this->handleSearch(),
            'modify' => $this->handleModify(),
            'delete' => $this->handleDelete(),
            'add' => $this->handleAdd(),
            'duplicate' => $this->handleDuplicate(),
            'move' => $this->handleMove(),
            'publish' => $this->handlePublish(),
            'backup' => $this->handleBackup(),
            'restore' => $this->handleRestore(),
            'diff' => $this->handleDiff(),
            'clean' => $this->handleClean(),
            'json' => $this->handleJson(),
            'cmd' => $this->handleCmd(),
            default => $this->showHelp(),
        };
    }

    // =====================================================================
    // HELP
    // =====================================================================

    private function showHelp(): int
    {
        $this->newLine();
        $this->line('<fg=white;options=bold>  ╔══════════════════════════════════════════════════════════════╗</>');
        $this->line('<fg=white;options=bold>  ║           courses:manage — Gestionnaire des cours           ║</>');
        $this->line('<fg=white;options=bold>  ╚══════════════════════════════════════════════════════════════╝</>');
        $this->newLine();

        $this->line('<fg=cyan;options=bold>  CONSULTATION</>');
        $this->line('  <fg=green>help</>       Afficher cette aide');
        $this->line('  <fg=green>view</>       Voir un module/leçon en détail          <fg=gray>--id= --type=</>');
        $this->line('  <fg=green>tree</>       Arbre compact de toute la structure      <fg=gray>--compact --inactive</>');
        $this->line('  <fg=green>status</>     Tableau de bord rapide (état global)');
        $this->line('  <fg=green>stats</>      Statistiques détaillées + progressions');
        $this->line('  <fg=green>search</>     Chercher dans les cours                  <fg=gray>--query="mot clé"</>');
        $this->newLine();

        $this->line('<fg=cyan;options=bold>  MODIFICATION</>');
        $this->line('  <fg=green>modify</>     Modifier des éléments existants          <fg=gray>--inline= ou --file=</>');
        $this->line('  <fg=green>delete</>     Supprimer (module/leçon/question)        <fg=gray>--id= --type= ou --file=</>');
        $this->line('  <fg=green>add</>        Ajouter (remplissage IA)                 <fg=gray>--file= --module=</>');
        $this->line('  <fg=green>duplicate</>  Dupliquer un module ou une leçon         <fg=gray>--id= --type=</>');
        $this->line('  <fg=green>move</>       Déplacer une leçon vers un autre module  <fg=gray>--id= --to=</>');
        $this->line('  <fg=green>publish</>    Publier/dépublier des leçons             <fg=gray>--id= ou --module=</>');
        $this->newLine();

        $this->line('<fg=cyan;options=bold>  DONNÉES JSON</>');
        $this->line('  <fg=green>json</>       Export, import, schéma, validation        <fg=gray>--type=export|schema|validate|import</>');
        $this->line('  <fg=green>cmd</>        Commande brute (JSON action)             <fg=gray>--inline= ou --file=</>');
        $this->line('  <fg=green>backup</>     Sauvegarder en JSON horodaté             <fg=gray>--file=</>');
        $this->line('  <fg=green>restore</>    Restaurer depuis un backup               <fg=gray>--file=</>');
        $this->line('  <fg=green>diff</>       Comparer l\'état actuel vs un fichier     <fg=gray>--file=</>');
        $this->newLine();

        $this->line('<fg=cyan;options=bold>  MAINTENANCE</>');
        $this->line('  <fg=green>clean</>      Nettoyer (orphelins, blocs vides, ordres) <fg=gray>--force</>');
        $this->newLine();

        $this->line('<fg=yellow;options=bold>  EXEMPLES</>');
        $this->line('  <fg=gray>php artisan courses:manage tree</>');
        $this->line('  <fg=gray>php artisan courses:manage status</>');
        $this->line('  <fg=gray>php artisan courses:manage stats</>');
        $this->line('  <fg=gray>php artisan courses:manage view --id=3 --type=module</>');
        $this->line('  <fg=gray>php artisan courses:manage search --query="micro-entreprise"</>');
        $this->line('  <fg=gray>php artisan courses:manage duplicate --id=1 --type=module</>');
        $this->line('  <fg=gray>php artisan courses:manage move --id=5 --to=2</>');
        $this->line('  <fg=gray>php artisan courses:manage publish --module=3</>');
        $this->line('  <fg=gray>php artisan courses:manage backup --file=backup.json</>');
        $this->line('  <fg=gray>php artisan courses:manage diff --file=backup.json</>');
        $this->line('  <fg=gray>php artisan courses:manage clean</>');
        $this->line('  <fg=gray>php artisan courses:manage modify --id=1 --type=module --inline=\'{"titre":"Nouveau"}\'</>');
        $this->line('  <fg=gray>php artisan courses:manage delete --id=5 --type=lesson --force</>');
        $this->line('  <fg=gray>php artisan courses:manage json --type=export --file=export.json</>');
        $this->line('  <fg=gray>php artisan courses:manage json --type=schema</>');
        $this->line('  <fg=gray>php artisan courses:manage cmd --inline=\'{"action":"toggle","activer_modules":[1,2]}\'</>');
        $this->newLine();

        return 0;
    }

    // =====================================================================
    // VIEW (détails)
    // =====================================================================

    private function handleView(): int
    {
        $id = $this->option('id');
        $type = $this->option('type');

        if ($id && $type === 'module') return $this->viewModule((int) $id);
        if ($id && $type === 'lesson') return $this->viewLesson((int) $id);
        if ($id && $type === 'question') return $this->viewQuestion((int) $id);

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
        if (!$module) { $this->error("Module ID {$id} introuvable."); return 1; }

        $this->info("=== MODULE #{$module->id} : {$module->titre} ===");
        $this->table(['Champ', 'Valeur'], [
            ['ID', $module->id],
            ['Titre', $module->titre],
            ['Description', \Str::limit($module->description ?? '-', 100)],
            ['Video URL', $module->video_url ?? '-'],
            ['Ordre', $module->ordre],
            ['Actif', $module->est_actif ? 'Oui' : 'Non'],
            ['Nb leçons', $module->lessons->count()],
            ['Créé le', $module->created_at?->format('d/m/Y H:i') ?? '-'],
            ['Modifié le', $module->updated_at?->format('d/m/Y H:i') ?? '-'],
        ]);

        if ($module->lessons->isNotEmpty()) {
            $this->newLine();
            $this->info('Leçons :');
            $this->table(
                ['ID', 'Titre', 'Type', 'Ordre', 'Actif', 'Statut', 'Blocs', 'Questions'],
                $module->lessons->map(fn($l) => [
                    $l->id, \Str::limit($l->titre, 40), $l->type, $l->ordre,
                    $l->est_actif ? 'Oui' : 'Non', $l->is_draft ? 'Brouillon' : 'Publié',
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
        if (!$lesson) { $this->error("Leçon ID {$id} introuvable."); return 1; }

        $this->info("=== LEÇON #{$lesson->id} : {$lesson->titre} ===");
        $this->table(['Champ', 'Valeur'], [
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
            ['Créé le', $lesson->created_at?->format('d/m/Y H:i') ?? '-'],
            ['Modifié le', $lesson->updated_at?->format('d/m/Y H:i') ?? '-'],
        ]);

        $blocks = $lesson->contenu_blocks_json;
        if (is_array($blocks) && !empty($blocks)) {
            $this->newLine();
            $this->info('Blocs (' . count($blocks) . ') :');
            $this->table(['#', 'Type', 'Aperçu'], collect($blocks)->map(function ($b, $i) {
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
                    default => \Str::limit(json_encode($b['content'] ?? []), 50),
                };
                return [$i + 1, $b['type'] ?? '?', \Str::limit($preview, 60)];
            })->toArray());
        }

        if ($lesson->type === 'quiz' && $lesson->quizQuestions->isNotEmpty()) {
            $this->newLine();
            $this->info('Questions (' . $lesson->quizQuestions->count() . ') :');
            $this->table(['ID', 'Question', 'Type', 'Points', 'Réponse'],
                $lesson->quizQuestions->map(fn($q) => [
                    $q->id, \Str::limit($q->question, 50), $q->type, $q->points, \Str::limit($q->bonne_reponse, 30),
                ])->toArray()
            );
        }

        return 0;
    }

    private function viewQuestion(int $id): int
    {
        $question = QuizQuestion::with('lesson.module')->find($id);
        if (!$question) { $this->error("Question ID {$id} introuvable."); return 1; }

        $this->info("=== QUESTION #{$question->id} ===");
        $this->table(['Champ', 'Valeur'], [
            ['ID', $question->id],
            ['Leçon', "{$question->lesson->titre} (ID: {$question->lesson_id})"],
            ['Module', $question->lesson->module->titre ?? '-'],
            ['Question', $question->question],
            ['Type', $question->type],
            ['Bonne réponse', $question->bonne_reponse],
            ['Points', $question->points],
            ['Ordre', $question->ordre],
        ]);

        if (!empty($question->options_json)) {
            $this->newLine();
            $this->info('Options :');
            foreach ($question->options_json as $i => $opt) {
                $marker = ($opt === $question->bonne_reponse) ? '<fg=green>  ✓</>' : '   ';
                $this->line("{$marker} " . ($i + 1) . ". {$opt}");
            }
        }

        return 0;
    }

    // =====================================================================
    // TREE (arbre compact)
    // =====================================================================

    private function handleTree(): int
    {
        $modules = CourseModule::orderBy('ordre')
            ->with(['lessons' => fn($q) => $q->orderBy('ordre')->withCount('quizQuestions')])
            ->get();

        if ($this->option('inactive')) {
            $modules = $modules->filter(fn($m) => !$m->est_actif);
        }

        if ($modules->isEmpty()) {
            $this->warn('Aucun module trouvé.');
            return 0;
        }

        $this->newLine();
        $this->line('<fg=white;options=bold>  ARBRE DES COURS</>');
        $this->newLine();

        foreach ($modules as $mi => $module) {
            $isLast = $mi === $modules->count() - 1;
            $connector = $isLast ? '└──' : '├──';
            $pipe = $isLast ? '   ' : '│  ';

            $icon = $module->est_actif ? '<fg=green>●</>' : '<fg=red>○</>';
            $this->line("  {$connector} {$icon} <fg=cyan>M:{$module->id}</> <options=bold>{$module->titre}</> <fg=gray>({$module->lessons->count()} leçons)</>") ;

            $lessons = $module->lessons;
            if ($this->option('drafts')) {
                $lessons = $lessons->filter(fn($l) => $l->is_draft);
            }

            foreach ($lessons as $li => $lesson) {
                $isLastL = $li === $lessons->count() - 1;
                $lConnector = $isLastL ? '└──' : '├──';

                $lIcon = match (true) {
                    !$lesson->est_actif => '<fg=red>○</>',
                    $lesson->is_draft => '<fg=yellow>◐</>',
                    default => '<fg=green>●</>',
                };

                $typeIcon = $lesson->type === 'quiz' ? '<fg=magenta>?</>' : '<fg=white>📖</>';
                $qInfo = '';
                if ($lesson->type === 'quiz' && $lesson->quiz_questions_count > 0) {
                    $qInfo = " <fg=gray>({$lesson->quiz_questions_count}Q, {$lesson->points_quiz}pts)</>";
                }

                $blocksCount = is_array($lesson->contenu_blocks_json) ? count($lesson->contenu_blocks_json) : 0;
                $blocksInfo = $this->option('compact') ? '' : " <fg=gray>[{$blocksCount}b]</>";

                $this->line("  {$pipe} {$lConnector} {$lIcon} {$typeIcon} <fg=gray>L:{$lesson->id}</> {$lesson->titre}{$qInfo}{$blocksInfo}");
            }

            if (!$isLast) $this->line("  {$pipe}");
        }

        $this->newLine();
        $totalL = $modules->sum(fn($m) => $m->lessons->count());
        $totalQ = $modules->sum(fn($m) => $m->lessons->sum('quiz_questions_count'));
        $this->line("  <fg=gray>Légende: <fg=green>●</> actif+publié  <fg=yellow>◐</> brouillon  <fg=red>○</> inactif</>");
        $this->line("  <fg=gray>Total: {$modules->count()} modules, {$totalL} leçons, {$totalQ} questions</>");
        $this->newLine();

        return 0;
    }

    // =====================================================================
    // STATUS (tableau de bord rapide)
    // =====================================================================

    private function handleStatus(): int
    {
        $modules = CourseModule::withCount('lessons')->get();
        $lessons = CourseLesson::all();
        $questions = QuizQuestion::count();

        $activeModules = $modules->where('est_actif', true)->count();
        $inactiveModules = $modules->where('est_actif', false)->count();
        $activeLessons = $lessons->where('est_actif', true)->count();
        $draftLessons = $lessons->where('is_draft', true)->count();
        $publishedLessons = $lessons->where('is_draft', false)->count();
        $quizLessons = $lessons->where('type', 'quiz')->count();
        $courseLessons = $lessons->where('type', 'course')->count();

        $emptyModules = $modules->where('lessons_count', 0)->count();
        $emptyLessons = $lessons->filter(fn($l) => empty($l->contenu_blocks_json) || (is_array($l->contenu_blocks_json) && count($l->contenu_blocks_json) === 0))->count();

        $this->newLine();
        $this->line('<fg=white;options=bold>  ╔════════════════════════════════════════╗</>');
        $this->line('<fg=white;options=bold>  ║       TABLEAU DE BORD DES COURS       ║</>');
        $this->line('<fg=white;options=bold>  ╚════════════════════════════════════════╝</>');
        $this->newLine();

        $this->line('  <fg=cyan;options=bold>CONTENU</>');
        $this->line("  Modules      : <fg=white;options=bold>{$modules->count()}</> (<fg=green>{$activeModules} actifs</>, <fg=red>{$inactiveModules} inactifs</>)");
        $this->line("  Leçons       : <fg=white;options=bold>{$lessons->count()}</> (<fg=white>{$courseLessons} cours</>, <fg=magenta>{$quizLessons} quiz</>)");
        $this->line("  Questions    : <fg=white;options=bold>{$questions}</>");
        $this->newLine();

        $this->line('  <fg=cyan;options=bold>PUBLICATION</>');
        $this->line("  Publiées     : <fg=blue>{$publishedLessons}</>");
        $this->line("  Brouillons   : <fg=yellow>{$draftLessons}</>");
        $this->line("  Actives      : <fg=green>{$activeLessons}</>");
        $this->newLine();

        if ($emptyModules > 0 || $emptyLessons > 0) {
            $this->line('  <fg=cyan;options=bold>ALERTES</>');
            if ($emptyModules > 0) $this->line("  <fg=red>⚠</> Modules sans leçons : {$emptyModules}");
            if ($emptyLessons > 0) $this->line("  <fg=red>⚠</> Leçons sans blocs   : {$emptyLessons}");
            $this->newLine();
        }

        // Points totaux
        $totalPoints = $lessons->where('type', 'quiz')->sum('points_quiz');
        $totalBlocks = $lessons->sum(fn($l) => is_array($l->contenu_blocks_json) ? count($l->contenu_blocks_json) : 0);
        $this->line('  <fg=cyan;options=bold>MÉTRIQUES</>');
        $this->line("  Blocs totaux : <fg=white>{$totalBlocks}</>");
        $this->line("  Points quiz  : <fg=white>{$totalPoints}</>");

        $avgBlocksPerLesson = $lessons->count() > 0 ? round($totalBlocks / $lessons->count(), 1) : 0;
        $this->line("  Moy. blocs/leçon : <fg=white>{$avgBlocksPerLesson}</>");
        $this->newLine();

        return 0;
    }

    // =====================================================================
    // STATS (statistiques détaillées + progressions)
    // =====================================================================

    private function handleStats(): int
    {
        $modules = CourseModule::orderBy('ordre')
            ->withCount(['lessons', 'lessons as active_lessons_count' => fn($q) => $q->where('est_actif', true)])
            ->get();

        $this->info('=== STATISTIQUES DÉTAILLÉES ===');
        $this->newLine();

        // Stats par module
        $this->table(
            ['ID', 'Module', 'Leçons', 'Actives', 'Quiz', 'Questions', 'Points', 'Blocs', 'Actif'],
            $modules->map(function ($m) {
                $lessons = CourseLesson::where('module_id', $m->id)->get();
                $quizCount = $lessons->where('type', 'quiz')->count();
                $qCount = QuizQuestion::whereIn('lesson_id', $lessons->pluck('id'))->count();
                $points = $lessons->where('type', 'quiz')->sum('points_quiz');
                $blocks = $lessons->sum(fn($l) => is_array($l->contenu_blocks_json) ? count($l->contenu_blocks_json) : 0);

                return [$m->id, \Str::limit($m->titre, 35), $m->lessons_count, $m->active_lessons_count, $quizCount, $qCount, $points, $blocks, $m->est_actif ? 'Oui' : 'Non'];
            })->toArray()
        );

        // Progressions
        $usersWithProgress = UserModuleProgress::distinct('user_id')->count('user_id');
        $completedModules = UserModuleProgress::where('progress_percentage', '>=', 100)->count();
        $completedLessons = UserLessonProgress::whereNotNull('completed_at')->count();
        $avgScore = UserLessonProgress::whereNotNull('score')->avg('score');

        $this->newLine();
        $this->info('=== PROGRESSIONS UTILISATEURS ===');
        $this->table(['Métrique', 'Valeur'], [
            ['Utilisateurs engagés', $usersWithProgress],
            ['Modules complétés', $completedModules],
            ['Leçons complétées', $completedLessons],
            ['Score moyen quiz', $avgScore !== null ? round($avgScore, 1) . '%' : 'N/A'],
        ]);

        // Top modules par completion
        if ($usersWithProgress > 0) {
            $this->newLine();
            $this->info('Top modules (par taux de complétion) :');
            $topModules = UserModuleProgress::select('module_id', DB::raw('AVG(progress_percentage) as avg_progress'), DB::raw('COUNT(DISTINCT user_id) as users'))
                ->groupBy('module_id')
                ->orderByDesc('avg_progress')
                ->limit(5)
                ->get();

            $this->table(['Module', 'Progression moy.', 'Utilisateurs'], $topModules->map(function ($tp) {
                $module = CourseModule::find($tp->module_id);
                return [\Str::limit($module?->titre ?? "ID:{$tp->module_id}", 40), round($tp->avg_progress, 1) . '%', $tp->users];
            })->toArray());
        }

        // Distribution types de blocs
        $this->newLine();
        $this->info('Distribution des types de blocs :');
        $allBlocks = CourseLesson::all()->flatMap(fn($l) => is_array($l->contenu_blocks_json) ? $l->contenu_blocks_json : []);
        $blockTypes = collect($allBlocks)->countBy(fn($b) => $b['type'] ?? 'unknown')->sortDesc();

        $this->table(['Type', 'Nombre', 'Pourcentage'],
            $blockTypes->map(function ($count, $type) use ($allBlocks) {
                $pct = $allBlocks->count() > 0 ? round($count / $allBlocks->count() * 100, 1) : 0;
                $bar = str_repeat('█', (int) ($pct / 3)) . str_repeat('░', max(0, 33 - (int) ($pct / 3)));
                return [$type, $count, "{$pct}% {$bar}"];
            })->toArray()
        );

        return 0;
    }

    // =====================================================================
    // SEARCH
    // =====================================================================

    private function handleSearch(): int
    {
        $query = $this->option('query');
        if (!$query) {
            $this->error('Spécifiez --query="terme de recherche"');
            return 1;
        }

        $this->info("=== Recherche : \"{$query}\" ===");
        $this->newLine();
        $found = false;

        // Modules
        $modules = CourseModule::where('titre', 'LIKE', "%{$query}%")
            ->orWhere('description', 'LIKE', "%{$query}%")
            ->get();

        if ($modules->isNotEmpty()) {
            $found = true;
            $this->line("<fg=cyan;options=bold>Modules ({$modules->count()}) :</>");
            foreach ($modules as $m) {
                $this->line("  <fg=cyan>[M:{$m->id}]</> <options=bold>{$m->titre}</>");
                if ($m->description && stripos($m->description, $query) !== false) {
                    $this->line("         <fg=gray>" . \Str::limit($m->description, 100) . "</>");
                }
            }
            $this->newLine();
        }

        // Leçons
        $lessons = CourseLesson::with('module')
            ->where('titre', 'LIKE', "%{$query}%")
            ->orWhere('description', 'LIKE', "%{$query}%")
            ->get();

        if ($lessons->isNotEmpty()) {
            $found = true;
            $this->line("<fg=cyan;options=bold>Leçons ({$lessons->count()}) :</>");
            foreach ($lessons as $l) {
                $this->line("  <fg=gray>[L:{$l->id}]</> <options=bold>{$l->titre}</> <fg=gray>(module: {$l->module->titre})</>");
            }
            $this->newLine();
        }

        // Questions
        $questions = QuizQuestion::with('lesson.module')
            ->where('question', 'LIKE', "%{$query}%")
            ->orWhere('bonne_reponse', 'LIKE', "%{$query}%")
            ->get();

        if ($questions->isNotEmpty()) {
            $found = true;
            $this->line("<fg=cyan;options=bold>Questions ({$questions->count()}) :</>");
            foreach ($questions as $q) {
                $this->line("  <fg=gray>[Q:{$q->id}]</> {$q->question}");
                $this->line("         <fg=gray>→ {$q->lesson->titre} > {$q->lesson->module->titre}</>");
            }
            $this->newLine();
        }

        // Recherche dans les blocs
        $lessonsWithBlockMatch = CourseLesson::with('module')->get()->filter(function ($l) use ($query) {
            if (!is_array($l->contenu_blocks_json)) return false;
            $json = json_encode($l->contenu_blocks_json);
            return stripos($json, $query) !== false;
        });

        // Exclure celles déjà trouvées par titre/description
        $blockOnlyLessons = $lessonsWithBlockMatch->diff($lessons);
        if ($blockOnlyLessons->isNotEmpty()) {
            $found = true;
            $this->line("<fg=cyan;options=bold>Dans le contenu des blocs ({$blockOnlyLessons->count()}) :</>");
            foreach ($blockOnlyLessons as $l) {
                $this->line("  <fg=gray>[L:{$l->id}]</> {$l->titre} <fg=gray>(module: {$l->module->titre})</>");
            }
            $this->newLine();
        }

        if (!$found) {
            $this->warn("Aucun résultat pour \"{$query}\".");
        }

        return 0;
    }

    // =====================================================================
    // DUPLICATE
    // =====================================================================

    private function handleDuplicate(): int
    {
        $id = $this->option('id');
        $type = $this->option('type') ?? 'module';

        if (!$id) {
            $this->error('Spécifiez --id= et --type=module|lesson');
            return 1;
        }

        if ($type === 'module') {
            return $this->duplicateModule((int) $id);
        } elseif ($type === 'lesson') {
            return $this->duplicateLesson((int) $id);
        }

        $this->error('Type invalide. Utilisez --type=module ou --type=lesson');
        return 1;
    }

    private function duplicateModule(int $id): int
    {
        $source = CourseModule::with(['lessons' => fn($q) => $q->orderBy('ordre')->with('quizQuestions')])->find($id);
        if (!$source) { $this->error("Module ID {$id} introuvable."); return 1; }

        $this->info("Duplication du module : {$source->titre}");
        $this->line("  {$source->lessons->count()} leçons seront dupliquées");

        if (!$this->option('force') && !$this->confirm('Confirmer la duplication ?')) {
            $this->info('Annulé.');
            return 0;
        }

        try {
            $result = DB::transaction(function () use ($source) {
                $maxOrdre = CourseModule::max('ordre') ?? 0;

                $newModule = CourseModule::create([
                    'titre' => $source->titre . ' (copie)',
                    'description' => $source->description,
                    'video_url' => $source->video_url,
                    'ordre' => $maxOrdre + 1,
                    'est_actif' => false,
                ]);

                $lessonsCreated = 0;
                $questionsCreated = 0;

                foreach ($source->lessons as $lesson) {
                    $newLesson = CourseLesson::create([
                        'module_id' => $newModule->id,
                        'titre' => $lesson->titre,
                        'description' => $lesson->description,
                        'contenu_blocks_json' => $lesson->contenu_blocks_json,
                        'contenu_rich_html' => $lesson->contenu_rich_html,
                        'type' => $lesson->type,
                        'ordre' => $lesson->ordre,
                        'points_quiz' => $lesson->points_quiz,
                        'est_actif' => $lesson->est_actif,
                        'is_draft' => true,
                    ]);
                    $lessonsCreated++;

                    foreach ($lesson->quizQuestions as $q) {
                        QuizQuestion::create([
                            'lesson_id' => $newLesson->id,
                            'question' => $q->question,
                            'type' => $q->type,
                            'options_json' => $q->options_json,
                            'bonne_reponse' => $q->bonne_reponse,
                            'points' => $q->points,
                            'ordre' => $q->ordre,
                        ]);
                        $questionsCreated++;
                    }
                }

                return ['module' => $newModule, 'lessons' => $lessonsCreated, 'questions' => $questionsCreated];
            });

            $this->newLine();
            $this->info("=== Duplication terminée ===");
            $this->line("  Nouveau module : <fg=cyan>[M:{$result['module']->id}]</> {$result['module']->titre}");
            $this->line("  Leçons créées  : <fg=green>{$result['lessons']}</>");
            $this->line("  Questions      : <fg=green>{$result['questions']}</>");
            $this->warn("  Le module dupliqué est INACTIF par défaut.");

            return 0;
        } catch (\Exception $e) {
            $this->error('Erreur : ' . $e->getMessage());
            return 1;
        }
    }

    private function duplicateLesson(int $id): int
    {
        $source = CourseLesson::with('quizQuestions')->find($id);
        if (!$source) { $this->error("Leçon ID {$id} introuvable."); return 1; }

        $targetModuleId = $this->option('module') ?? $source->module_id;
        $targetModule = CourseModule::find($targetModuleId);
        if (!$targetModule) { $this->error("Module cible ID {$targetModuleId} introuvable."); return 1; }

        $this->info("Duplication de la leçon : {$source->titre}");
        $this->line("  Vers le module : {$targetModule->titre} (ID: {$targetModule->id})");

        if (!$this->option('force') && !$this->confirm('Confirmer ?')) {
            $this->info('Annulé.');
            return 0;
        }

        try {
            $result = DB::transaction(function () use ($source, $targetModule) {
                $maxOrdre = CourseLesson::where('module_id', $targetModule->id)->max('ordre') ?? 0;

                $newLesson = CourseLesson::create([
                    'module_id' => $targetModule->id,
                    'titre' => $source->titre . ' (copie)',
                    'description' => $source->description,
                    'contenu_blocks_json' => $source->contenu_blocks_json,
                    'contenu_rich_html' => $source->contenu_rich_html,
                    'type' => $source->type,
                    'ordre' => $maxOrdre + 1,
                    'points_quiz' => $source->points_quiz,
                    'est_actif' => $source->est_actif,
                    'is_draft' => true,
                ]);

                $questionsCreated = 0;
                foreach ($source->quizQuestions as $q) {
                    QuizQuestion::create([
                        'lesson_id' => $newLesson->id,
                        'question' => $q->question,
                        'type' => $q->type,
                        'options_json' => $q->options_json,
                        'bonne_reponse' => $q->bonne_reponse,
                        'points' => $q->points,
                        'ordre' => $q->ordre,
                    ]);
                    $questionsCreated++;
                }

                return ['lesson' => $newLesson, 'questions' => $questionsCreated];
            });

            $this->info("Leçon dupliquée : <fg=cyan>[L:{$result['lesson']->id}]</> {$result['lesson']->titre}");
            if ($result['questions'] > 0) $this->line("  Questions : <fg=green>{$result['questions']}</>");

            return 0;
        } catch (\Exception $e) {
            $this->error('Erreur : ' . $e->getMessage());
            return 1;
        }
    }

    // =====================================================================
    // MOVE (déplacer une leçon vers un autre module)
    // =====================================================================

    private function handleMove(): int
    {
        $id = $this->option('id');
        $to = $this->option('to');

        if (!$id || !$to) {
            $this->error('Usage : courses:manage move --id=LESSON_ID --to=MODULE_ID');
            return 1;
        }

        $lesson = CourseLesson::with('module')->find($id);
        if (!$lesson) { $this->error("Leçon ID {$id} introuvable."); return 1; }

        $targetModule = CourseModule::find($to);
        if (!$targetModule) { $this->error("Module cible ID {$to} introuvable."); return 1; }

        if ($lesson->module_id == $to) {
            $this->warn("La leçon est déjà dans le module {$to}.");
            return 0;
        }

        $this->info("Déplacement de la leçon :");
        $this->line("  <fg=gray>[L:{$lesson->id}]</> {$lesson->titre}");
        $this->line("  De : {$lesson->module->titre} (M:{$lesson->module_id})");
        $this->line("  Vers : {$targetModule->titre} (M:{$targetModule->id})");

        if (!$this->option('force') && !$this->confirm('Confirmer le déplacement ?')) {
            $this->info('Annulé.');
            return 0;
        }

        $maxOrdre = CourseLesson::where('module_id', $to)->max('ordre') ?? 0;
        $lesson->update(['module_id' => (int) $to, 'ordre' => $maxOrdre + 1]);

        $this->info("Leçon déplacée avec succès (nouvel ordre : " . ($maxOrdre + 1) . ")");

        return 0;
    }

    // =====================================================================
    // PUBLISH (publier/dépublier)
    // =====================================================================

    private function handlePublish(): int
    {
        $id = $this->option('id');
        $moduleId = $this->option('module');
        $type = $this->option('type'); // 'unpublish' pour dépublier

        $unpublish = ($type === 'unpublish' || $type === 'depublish');

        if ($moduleId) {
            // Publier toutes les leçons d'un module
            $module = CourseModule::with('lessons')->find($moduleId);
            if (!$module) { $this->error("Module ID {$moduleId} introuvable."); return 1; }

            $lessons = $module->lessons;
            $action = $unpublish ? 'dépublier' : 'publier';
            $count = $lessons->count();

            $this->info("{$count} leçons du module \"{$module->titre}\" vont être {$action}es.");

            if (!$this->option('force') && !$this->confirm('Confirmer ?')) {
                $this->info('Annulé.');
                return 0;
            }

            $affected = 0;
            foreach ($lessons as $lesson) {
                if ($unpublish) {
                    $lesson->update(['is_draft' => true]);
                } else {
                    $update = ['is_draft' => false];
                    if (!$lesson->published_at) $update['published_at'] = now();
                    $lesson->update($update);
                }
                $affected++;
            }

            $this->info("{$affected} leçons {$action}es.");
            return 0;
        }

        if ($id) {
            $lesson = CourseLesson::find($id);
            if (!$lesson) { $this->error("Leçon ID {$id} introuvable."); return 1; }

            if ($unpublish) {
                $lesson->update(['is_draft' => true]);
                $this->info("Leçon \"{$lesson->titre}\" dépubliée (brouillon).");
            } else {
                $update = ['is_draft' => false];
                if (!$lesson->published_at) $update['published_at'] = now();
                $lesson->update($update);
                $this->info("Leçon \"{$lesson->titre}\" publiée.");
            }

            return 0;
        }

        $this->error('Spécifiez --id=LESSON_ID ou --module=MODULE_ID');
        $this->line('  Pour dépublier, ajoutez --type=unpublish');
        return 1;
    }

    // =====================================================================
    // BACKUP
    // =====================================================================

    private function handleBackup(): int
    {
        $data = $this->service->exportAll();

        // Ajouter métadonnées
        $backup = [
            '_meta' => [
                'type' => 'courses_backup',
                'version' => '1.0',
                'created_at' => now()->toIso8601String(),
                'app_env' => app()->environment(),
                'modules_count' => count($data['modules']),
                'lessons_count' => collect($data['modules'])->sum(fn($m) => count($m['lessons'])),
            ],
            ...$data,
        ];

        $json = json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $file = $this->option('file') ?? 'courses_backup_' . now()->format('Y-m-d_His') . '.json';

        file_put_contents($file, $json);

        $size = round(strlen($json) / 1024, 1);
        $this->info("=== Backup créé ===");
        $this->line("  Fichier  : <fg=cyan>{$file}</>");
        $this->line("  Taille   : <fg=white>{$size} Ko</>");
        $this->line("  Modules  : <fg=white>{$backup['_meta']['modules_count']}</>");
        $this->line("  Leçons   : <fg=white>{$backup['_meta']['lessons_count']}</>");
        $this->line("  Date     : <fg=white>{$backup['_meta']['created_at']}</>");

        return 0;
    }

    // =====================================================================
    // RESTORE
    // =====================================================================

    private function handleRestore(): int
    {
        $file = $this->option('file');
        if (!$file) { $this->error('Spécifiez --file=chemin.json'); return 1; }
        if (!file_exists($file)) { $this->error("Fichier introuvable : {$file}"); return 1; }

        $data = json_decode(file_get_contents($file), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('JSON invalide : ' . json_last_error_msg());
            return 1;
        }

        // Retirer les métadonnées
        $meta = $data['_meta'] ?? null;
        unset($data['_meta']);

        if (!isset($data['modules'])) {
            $this->error('Le fichier ne contient pas de clé "modules".');
            return 1;
        }

        $this->warn('=== RESTAURATION DEPUIS UN BACKUP ===');
        if ($meta) {
            $this->line("  Backup du  : <fg=cyan>{$meta['created_at']}</>");
            $this->line("  Modules    : {$meta['modules_count']}");
            $this->line("  Leçons     : {$meta['lessons_count']}");
        }

        $this->newLine();
        $this->warn('ATTENTION : Cela va AJOUTER les données du backup (pas remplacer).');
        $this->warn('Les IDs existants ne seront pas écrasés.');

        if (!$this->option('force') && !$this->confirm('Continuer la restauration ?', false)) {
            $this->info('Annulé.');
            return 0;
        }

        // Reformater les données : retirer les IDs pour créer de nouveaux éléments
        $importData = $this->stripIdsForImport($data);

        $validation = $this->service->validateFill($importData, 'global');
        if (!$validation['success']) {
            $this->error('Erreurs de validation :');
            foreach ($validation['errors'] as $err) $this->line("  - {$err}");
            return 1;
        }

        try {
            $result = $this->service->executeFill($importData, 'global');
            $this->displayResult('Restauration terminée', $result);
            return 0;
        } catch (\Exception $e) {
            $this->error('Erreur : ' . $e->getMessage());
            return 1;
        }
    }

    private function stripIdsForImport(array $data): array
    {
        $clean = ['modules' => []];
        foreach ($data['modules'] as $m) {
            $module = [
                'titre' => $m['titre'],
                'description' => $m['description'] ?? null,
                'video_url' => $m['video_url'] ?? null,
                'est_actif' => $m['est_actif'] ?? true,
                'lessons' => [],
            ];

            foreach ($m['lessons'] ?? [] as $l) {
                $lesson = [
                    'titre' => $l['titre'],
                    'description' => $l['description'] ?? null,
                    'type' => $l['type'] ?? 'course',
                    'est_actif' => $l['est_actif'] ?? true,
                    'points_quiz' => $l['points_quiz'] ?? 0,
                ];

                // Récupérer les blocs depuis l'export complet si disponibles
                if (isset($l['blocks'])) {
                    $lesson['blocks'] = $l['blocks'];
                }

                if (isset($l['questions'])) {
                    $lesson['questions'] = array_map(fn($q) => [
                        'question' => $q['question'],
                        'type' => $q['type'] ?? 'multiple_choice',
                        'options' => $q['options'] ?? [],
                        'bonne_reponse' => $q['bonne_reponse'],
                        'points' => $q['points'] ?? 1,
                    ], $l['questions']);
                }

                $module['lessons'][] = $lesson;
            }

            $clean['modules'][] = $module;
        }

        return $clean;
    }

    // =====================================================================
    // DIFF (comparer avec un fichier)
    // =====================================================================

    private function handleDiff(): int
    {
        $file = $this->option('file');
        if (!$file) { $this->error('Spécifiez --file=chemin.json'); return 1; }
        if (!file_exists($file)) { $this->error("Fichier introuvable : {$file}"); return 1; }

        $fileData = json_decode(file_get_contents($file), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('JSON invalide.');
            return 1;
        }

        unset($fileData['_meta']);
        $currentData = $this->service->exportAll();

        $this->info('=== COMPARAISON ===');
        $this->newLine();

        $fileModules = collect($fileData['modules'] ?? []);
        $currentModules = collect($currentData['modules']);

        $fileModuleIds = $fileModules->pluck('id')->filter();
        $currentModuleIds = $currentModules->pluck('id');

        // Modules ajoutés (dans current, pas dans file)
        $added = $currentModuleIds->diff($fileModuleIds);
        // Modules supprimés (dans file, pas dans current)
        $removed = $fileModuleIds->diff($currentModuleIds);
        // Modules en commun
        $common = $currentModuleIds->intersect($fileModuleIds);

        if ($added->isNotEmpty()) {
            $this->line('<fg=green;options=bold>  + MODULES AJOUTÉS</>');
            foreach ($added as $id) {
                $m = $currentModules->firstWhere('id', $id);
                $lessonCount = count($m['lessons'] ?? []);
                $this->line("    <fg=green>+ [M:{$id}] {$m['titre']} ({$lessonCount} leçons)</>");
            }
            $this->newLine();
        }

        if ($removed->isNotEmpty()) {
            $this->line('<fg=red;options=bold>  - MODULES SUPPRIMÉS</>');
            foreach ($removed as $id) {
                $m = $fileModules->firstWhere('id', $id);
                $this->line("    <fg=red>- [M:{$id}] {$m['titre']}</>");
            }
            $this->newLine();
        }

        // Comparer les modules communs
        $changes = [];
        foreach ($common as $id) {
            $fileMod = $fileModules->firstWhere('id', $id);
            $currMod = $currentModules->firstWhere('id', $id);

            $diffs = [];
            foreach (['titre', 'description', 'est_actif', 'ordre'] as $field) {
                if (($fileMod[$field] ?? null) !== ($currMod[$field] ?? null)) {
                    $diffs[] = $field;
                }
            }

            $fileLessonCount = count($fileMod['lessons'] ?? []);
            $currLessonCount = count($currMod['lessons'] ?? []);

            if (!empty($diffs) || $fileLessonCount !== $currLessonCount) {
                $changes[$id] = [
                    'titre' => $currMod['titre'],
                    'diffs' => $diffs,
                    'lessons_before' => $fileLessonCount,
                    'lessons_after' => $currLessonCount,
                ];
            }
        }

        if (!empty($changes)) {
            $this->line('<fg=yellow;options=bold>  ~ MODULES MODIFIÉS</>');
            foreach ($changes as $id => $info) {
                $diffStr = !empty($info['diffs']) ? ' (' . implode(', ', $info['diffs']) . ')' : '';
                $lessonDiff = '';
                if ($info['lessons_before'] !== $info['lessons_after']) {
                    $lessonDiff = " leçons: {$info['lessons_before']} → {$info['lessons_after']}";
                }
                $this->line("    <fg=yellow>~ [M:{$id}] {$info['titre']}{$diffStr}{$lessonDiff}</>");
            }
            $this->newLine();
        }

        if ($added->isEmpty() && $removed->isEmpty() && empty($changes)) {
            $this->info('  Aucune différence détectée au niveau des modules.');
        }

        // Résumé
        $this->newLine();
        $this->table(['', 'Fichier', 'Actuel', 'Diff'], [
            ['Modules', $fileModules->count(), $currentModules->count(), $this->diffSign($currentModules->count() - $fileModules->count())],
            ['Leçons',
                $fileModules->sum(fn($m) => count($m['lessons'] ?? [])),
                $currentModules->sum(fn($m) => count($m['lessons'] ?? [])),
                $this->diffSign($currentModules->sum(fn($m) => count($m['lessons'] ?? [])) - $fileModules->sum(fn($m) => count($m['lessons'] ?? []))),
            ],
        ]);

        return 0;
    }

    private function diffSign(int $val): string
    {
        if ($val > 0) return "+{$val}";
        if ($val < 0) return "{$val}";
        return "=";
    }

    // =====================================================================
    // CLEAN (nettoyage)
    // =====================================================================

    private function handleClean(): int
    {
        $this->info('=== ANALYSE DE NETTOYAGE ===');
        $this->newLine();

        $issues = [];

        // 1. Modules sans leçons
        $emptyModules = CourseModule::withCount('lessons')->having('lessons_count', 0)->get();
        if ($emptyModules->isNotEmpty()) {
            $issues[] = ['type' => 'empty_modules', 'count' => $emptyModules->count(), 'items' => $emptyModules];
            $this->line("  <fg=yellow>⚠</> <fg=white>{$emptyModules->count()}</> modules sans aucune leçon");
            foreach ($emptyModules as $m) {
                $this->line("    <fg=gray>[M:{$m->id}] {$m->titre}</>");
            }
        }

        // 2. Leçons sans blocs
        $emptyLessons = CourseLesson::with('module')->get()->filter(function ($l) {
            return empty($l->contenu_blocks_json) || (is_array($l->contenu_blocks_json) && count($l->contenu_blocks_json) === 0);
        });
        if ($emptyLessons->isNotEmpty()) {
            $issues[] = ['type' => 'empty_lessons', 'count' => $emptyLessons->count(), 'items' => $emptyLessons];
            $this->line("  <fg=yellow>⚠</> <fg=white>{$emptyLessons->count()}</> leçons sans contenu (0 blocs)");
            foreach ($emptyLessons as $l) {
                $this->line("    <fg=gray>[L:{$l->id}] {$l->titre} (module: {$l->module->titre})</>");
            }
        }

        // 3. Quiz sans questions
        $quizWithoutQuestions = CourseLesson::where('type', 'quiz')->withCount('quizQuestions')
            ->having('quiz_questions_count', 0)->get();
        if ($quizWithoutQuestions->isNotEmpty()) {
            $issues[] = ['type' => 'empty_quizzes', 'count' => $quizWithoutQuestions->count(), 'items' => $quizWithoutQuestions];
            $this->line("  <fg=yellow>⚠</> <fg=white>{$quizWithoutQuestions->count()}</> quiz sans questions");
            foreach ($quizWithoutQuestions as $q) {
                $this->line("    <fg=gray>[L:{$q->id}] {$q->titre}</>");
            }
        }

        // 4. Questions orphelines (leçon supprimée)
        $orphanQuestions = QuizQuestion::whereDoesntHave('lesson')->get();
        if ($orphanQuestions->isNotEmpty()) {
            $issues[] = ['type' => 'orphan_questions', 'count' => $orphanQuestions->count(), 'items' => $orphanQuestions];
            $this->line("  <fg=red>⚠</> <fg=white>{$orphanQuestions->count()}</> questions orphelines (leçon supprimée)");
        }

        // 5. Leçons orphelines (module supprimé)
        $orphanLessons = CourseLesson::whereDoesntHave('module')->get();
        if ($orphanLessons->isNotEmpty()) {
            $issues[] = ['type' => 'orphan_lessons', 'count' => $orphanLessons->count(), 'items' => $orphanLessons];
            $this->line("  <fg=red>⚠</> <fg=white>{$orphanLessons->count()}</> leçons orphelines (module supprimé)");
        }

        // 6. Ordres discontinus
        $modulesWithGaps = CourseModule::orderBy('ordre')->pluck('ordre', 'id');
        $hasModuleGaps = false;
        $expected = 0;
        foreach ($modulesWithGaps as $id => $ordre) {
            if ($ordre != $expected) { $hasModuleGaps = true; break; }
            $expected++;
        }
        if ($hasModuleGaps) {
            $issues[] = ['type' => 'module_order_gaps', 'count' => 1, 'items' => collect()];
            $this->line("  <fg=yellow>⚠</> Ordres des modules discontinus (trous dans la séquence)");
        }

        // 7. Progressions orphelines
        $orphanModuleProgress = UserModuleProgress::whereDoesntHave('module')->count();
        $orphanLessonProgress = UserLessonProgress::whereDoesntHave('lesson')->count();
        if ($orphanModuleProgress > 0 || $orphanLessonProgress > 0) {
            $total = $orphanModuleProgress + $orphanLessonProgress;
            $issues[] = ['type' => 'orphan_progress', 'count' => $total, 'items' => collect()];
            $this->line("  <fg=yellow>⚠</> <fg=white>{$total}</> progressions orphelines ({$orphanModuleProgress} module, {$orphanLessonProgress} leçon)");
        }

        if (empty($issues)) {
            $this->newLine();
            $this->info('  Tout est propre. Aucun problème détecté.');
            return 0;
        }

        $this->newLine();
        $totalIssues = collect($issues)->sum('count');
        $this->warn("{$totalIssues} problème(s) détecté(s).");

        if (!$this->option('force') && !$this->confirm('Corriger les problèmes automatiquement ?', false)) {
            $this->info('Annulé. Utilisez --force pour corriger sans confirmation.');
            return 0;
        }

        // Corrections
        $fixed = 0;

        // Supprimer orphelins
        if ($orphanQuestions->isNotEmpty()) {
            QuizQuestion::whereDoesntHave('lesson')->delete();
            $this->line("  <fg=green>✓</> {$orphanQuestions->count()} questions orphelines supprimées");
            $fixed += $orphanQuestions->count();
        }

        if ($orphanLessons->isNotEmpty()) {
            CourseLesson::whereDoesntHave('module')->delete();
            $this->line("  <fg=green>✓</> {$orphanLessons->count()} leçons orphelines supprimées");
            $fixed += $orphanLessons->count();
        }

        // Reséquencer les ordres
        if ($hasModuleGaps) {
            $modules = CourseModule::orderBy('ordre')->get();
            foreach ($modules as $i => $m) {
                if ($m->ordre !== $i) $m->update(['ordre' => $i]);
            }
            $this->line("  <fg=green>✓</> Ordres des modules reséquencés (0.." . ($modules->count() - 1) . ")");

            // Aussi reséquencer les leçons dans chaque module
            foreach ($modules as $m) {
                $lessons = CourseLesson::where('module_id', $m->id)->orderBy('ordre')->get();
                foreach ($lessons as $i => $l) {
                    if ($l->ordre !== $i) $l->update(['ordre' => $i]);
                }
            }
            $this->line("  <fg=green>✓</> Ordres des leçons reséquencés dans chaque module");
            $fixed++;
        }

        // Supprimer progressions orphelines
        if ($orphanModuleProgress > 0) {
            UserModuleProgress::whereDoesntHave('module')->delete();
            $this->line("  <fg=green>✓</> {$orphanModuleProgress} progressions module orphelines supprimées");
            $fixed += $orphanModuleProgress;
        }
        if ($orphanLessonProgress > 0) {
            UserLessonProgress::whereDoesntHave('lesson')->delete();
            $this->line("  <fg=green>✓</> {$orphanLessonProgress} progressions leçon orphelines supprimées");
            $fixed += $orphanLessonProgress;
        }

        $this->newLine();
        $this->info("{$fixed} correction(s) appliquée(s).");

        return 0;
    }

    // =====================================================================
    // MODIFY
    // =====================================================================

    private function handleModify(): int
    {
        $data = $this->resolveJsonData();
        if ($data === null) return 1;

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
            'import' => $this->handleAdd(),
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
    // HELPERS PRIVÉS
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
}

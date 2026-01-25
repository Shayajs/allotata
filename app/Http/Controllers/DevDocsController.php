<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;

class DevDocsController extends Controller
{
    private string $basePath;

    public function __construct()
    {
        $this->basePath = base_path('dev-docs');
    }

    /**
     * Liste les sections et fichiers, en filtrant admin_only pour les non-admins.
     */
    private function loadTree(): array
    {
        $isAdmin = auth()->check() && auth()->user()->is_admin;
        $sections = [];
        $dirs = File::directories($this->basePath);

        foreach ($dirs as $dir) {
            $slug = basename($dir);
            $jsonPath = $dir . DIRECTORY_SEPARATOR . 'section.json';
            if (! File::exists($jsonPath)) {
                continue;
            }

            $config = json_decode(File::get($jsonPath), true) ?: [];
            $adminOnly = $config['admin_only'] ?? [];
            $files = [];

            foreach (File::files($dir) as $file) {
                if (strtolower($file->getExtension()) !== 'md') {
                    continue;
                }
                $name = $file->getFilename();
                if ($this->isAdminOnly($name, $adminOnly) && ! $isAdmin) {
                    continue;
                }
                $files[] = [
                    'name' => $name,
                    'path' => $slug . '/' . $name,
                    'admin_only' => $this->isAdminOnly($name, $adminOnly),
                ];
            }

            usort($files, fn ($a, $b) => strcasecmp($a['name'], $b['name']));

            $sections[] = [
                'slug' => $slug,
                'title' => $config['title'] ?? $slug,
                'emoji' => $config['emoji'] ?? '📄',
                'color' => $config['color'] ?? '#64748b',
                'description' => $config['description'] ?? '',
                'files' => $files,
            ];
        }

        usort($sections, fn ($a, $b) => strcasecmp($a['title'], $b['title']));

        return $sections;
    }

    private function injectHeadingIds(string $html, array $toc): string
    {
        if (empty($toc)) {
            return $html;
        }
        $i = 0;
        return preg_replace_callback(
            '/<h([1-6])\b([^>]*)>/',
            function ($m) use ($toc, &$i) {
                $out = '<h' . $m[1] . $m[2];
                if (isset($toc[$i])) {
                    $out .= ' id="' . htmlspecialchars($toc[$i]['id']) . '"';
                    $i++;
                }
                return $out . '>';
            },
            $html
        );
    }

    private function isAdminOnly(string $filename, array $patterns): bool
    {
        foreach ($patterns as $p) {
            if (str_starts_with($p, '/') && str_ends_with($p, '/')) {
                if (preg_match($p, $filename)) {
                    return true;
                }
                continue;
            }
            if ($p === $filename) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extraire les titres (h1–h6) du Markdown pour le sommaire.
     */
    private function extractToc(string $raw): array
    {
        $toc = [];
        $used = [];
        if (! preg_match_all('/^(#{1,6})\s+(.+)$/m', $raw, $m, PREG_SET_ORDER)) {
            return $toc;
        }
        foreach ($m as $i => $match) {
            $level = strlen($match[1]);
            $title = trim($match[2]);
            $slug = preg_replace('/[^\pL\pN\-]+/u', '-', mb_strtolower($title));
            $slug = trim($slug, '-') ?: 'h-' . $i;
            $base = $slug;
            $n = 0;
            while (isset($used[$slug])) {
                $n++;
                $slug = $base . '-' . $n;
            }
            $used[$slug] = true;

            $toc[] = ['level' => $level, 'title' => $title, 'id' => $slug];
        }

        return $toc;
    }

    public function index()
    {
        $sections = $this->loadTree();

        return view('dev.index', [
            'sections' => $sections,
        ]);
    }

    public function show(Request $request, string $path)
    {
        $path = str_replace(['..', '\\'], ['', '/'], $path);
        $path = trim($path, '/');
        $full = $this->basePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);

        if (! File::exists($full) || ! File::isFile($full)) {
            abort(404, 'Document introuvable.');
        }

        $real = realpath($full);
        $baseReal = realpath($this->basePath);
        if (! $real || ! $baseReal || ! str_starts_with($real, $baseReal)) {
            abort(403, 'Accès refusé.');
        }

        $sectionSlug = dirname($path);
        if (str_contains($sectionSlug, DIRECTORY_SEPARATOR)) {
            $sectionSlug = basename($sectionSlug);
        }
        $filename = basename($path);
        $jsonPath = $this->basePath . DIRECTORY_SEPARATOR . $sectionSlug . DIRECTORY_SEPARATOR . 'section.json';
        $config = [];
        if (File::exists($jsonPath)) {
            $config = json_decode(File::get($jsonPath), true) ?: [];
        }
        $adminOnly = $config['admin_only'] ?? [];
        $isAdmin = auth()->check() && auth()->user()->is_admin;
        if ($this->isAdminOnly($filename, $adminOnly) && ! $isAdmin) {
            abort(403, 'Document réservé aux administrateurs.');
        }

        $raw = File::get($full);
        $toc = $this->extractToc($raw);

        $converter = new CommonMarkConverter([
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
        ]);
        $result = $converter->convert($raw);
        $html = $result->getContent();
        $html = $this->injectHeadingIds($html, $toc);

        $sections = $this->loadTree();

        return view('dev.show', [
            'path' => $path,
            'title' => pathinfo($filename, PATHINFO_FILENAME),
            'html' => $html,
            'toc' => $toc,
            'sections' => $sections,
            'sectionSlug' => $sectionSlug,
            'sectionConfig' => $config,
        ]);
    }
}

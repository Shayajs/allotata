<?php

namespace App\Http\Controllers;

use App\Models\ForumCategory;
use App\Models\ForumPost;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ForumController extends Controller
{
    public function index()
    {
        $categories = ForumCategory::orderBy('ordre')
            ->withCount('posts')
            ->get()
            ->groupBy(function ($category) {
                return $category->admin_only ? 'admin' : 'public';
            });

        $nouveautes = collect();
        $demandes = collect();
        $autres = collect();

        if (isset($categories['admin'])) {
            $nouveautes = ForumPost::whereIn('forum_category_id', $categories['admin']->pluck('id'))
                ->with(['user', 'category'])
                ->orderBy('est_epingle', 'desc')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        }

        if (isset($categories['public'])) {
            $publicCategories = $categories['public'];
            $demandes = ForumPost::whereIn('forum_category_id', $publicCategories->pluck('id'))
                ->whereHas('category', function ($query) {
                    $query->where('slug', 'like', '%demande%');
                })
                ->with(['user', 'category'])
                ->orderBy('est_epingle', 'desc')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            $autres = ForumPost::whereIn('forum_category_id', $publicCategories->pluck('id'))
                ->whereHas('category', function ($query) {
                    $query->where('slug', 'not like', '%demande%');
                })
                ->with(['user', 'category'])
                ->orderBy('est_epingle', 'desc')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        }

        return view('forum.index', compact('nouveautes', 'demandes', 'autres', 'categories'));
    }

    public function show(ForumCategory $category)
    {
        $posts = ForumPost::where('forum_category_id', $category->id)
            ->with(['user', 'category'])
            ->withCount('comments')
            ->orderBy('est_epingle', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('forum.category', compact('category', 'posts'));
    }

    public function showPost(ForumPost $post)
    {
        $post->incrementViews();
        $post->load(['user', 'category', 'comments.user', 'comments.replies.user']);

        return view('forum.post', compact('post'));
    }

    public function create(Request $request)
    {
        $categoryId = $request->get('category');
        $categories = ForumCategory::where('admin_only', false)->get();

        return view('forum.create', compact('categories', 'categoryId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'forum_category_id' => 'required|exists:forum_categories,id',
            'titre' => 'required|string|max:255',
            'contenu' => 'required|string',
        ]);

        $category = ForumCategory::findOrFail($validated['forum_category_id']);
        
        if ($category->admin_only && !auth()->user()->isAdmin()) {
            abort(403, 'Seuls les administrateurs peuvent créer des posts dans cette catégorie.');
        }

        $post = ForumPost::create([
            ...$validated,
            'user_id' => auth()->id(),
        ]);

        return redirect()->route('forum.post.show', $post)
            ->with('success', 'Post créé avec succès.');
    }

    public function edit(ForumPost $post)
    {
        if (!$post->user_id === auth()->id() && !auth()->user()->isAdmin()) {
            abort(403);
        }

        $categories = ForumCategory::where('admin_only', false)->get();

        return view('forum.edit', compact('post', 'categories'));
    }

    public function update(Request $request, ForumPost $post)
    {
        if (!$post->user_id === auth()->id() && !auth()->user()->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'contenu' => 'required|string',
        ]);

        $post->update($validated);

        return redirect()->route('forum.post.show', $post)
            ->with('success', 'Post mis à jour avec succès.');
    }

    public function destroy(ForumPost $post)
    {
        if (!$post->user_id === auth()->id() && !auth()->user()->isAdmin()) {
            abort(403);
        }

        $post->delete();

        return redirect()->route('forum.index')
            ->with('success', 'Post supprimé avec succès.');
    }

    // Admin uniquement
    public function createNouveaute()
    {
        $this->authorize('admin');
        
        $categories = ForumCategory::where('admin_only', true)->get();

        return view('forum.create-nouveaute', compact('categories'));
    }

    public function storeNouveaute(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'forum_category_id' => 'required|exists:forum_categories,id',
            'titre' => 'required|string|max:255',
            'contenu' => 'required|string',
            'est_epingle' => 'boolean',
        ]);

        $category = ForumCategory::findOrFail($validated['forum_category_id']);
        
        if (!$category->admin_only) {
            abort(403, 'Cette catégorie n\'est pas réservée aux nouveautés.');
        }

        $post = ForumPost::create([
            'forum_category_id' => $validated['forum_category_id'],
            'user_id' => auth()->id(),
            'titre' => $validated['titre'],
            'contenu' => $validated['contenu'],
            'est_epingle' => $request->has('est_epingle'),
        ]);

        return redirect()->route('forum.post.show', $post)
            ->with('success', 'Nouveauté créée avec succès.');
    }
}

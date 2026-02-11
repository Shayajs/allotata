<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
            ->get();

        $posts = ForumPost::with(['user', 'category'])
            ->withCount('comments')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $stats = [
            'total_categories' => ForumCategory::count(),
            'total_posts' => ForumPost::count(),
            'posts_admin' => ForumPost::whereHas('category', function($q) {
                $q->where('admin_only', true);
            })->count(),
            'posts_public' => ForumPost::whereHas('category', function($q) {
                $q->where('admin_only', false);
            })->count(),
        ];

        return view('admin.forum.index', compact('categories', 'posts', 'stats'));
    }

    public function createCategory()
    {
        return view('admin.forum.create-category');
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'ordre' => 'nullable|integer',
            'admin_only' => 'boolean',
        ]);

        ForumCategory::create([
            'nom' => $validated['nom'],
            'slug' => Str::slug($validated['nom']),
            'description' => $validated['description'] ?? null,
            'ordre' => $validated['ordre'] ?? 0,
            'admin_only' => $request->has('admin_only'),
        ]);

        return redirect()->route('admin.forum.index')
            ->with('success', 'Catégorie créée avec succès.');
    }

    public function editCategory(ForumCategory $category)
    {
        return view('admin.forum.edit-category', compact('category'));
    }

    public function updateCategory(Request $request, ForumCategory $category)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'ordre' => 'nullable|integer',
            'admin_only' => 'boolean',
        ]);

        $category->update([
            'nom' => $validated['nom'],
            'slug' => Str::slug($validated['nom']),
            'description' => $validated['description'] ?? null,
            'ordre' => $validated['ordre'] ?? 0,
            'admin_only' => $request->has('admin_only'),
        ]);

        return redirect()->route('admin.forum.index')
            ->with('success', 'Catégorie mise à jour avec succès.');
    }

    public function destroyCategory(ForumCategory $category)
    {
        $category->delete();

        return redirect()->route('admin.forum.index')
            ->with('success', 'Catégorie supprimée avec succès.');
    }

    public function destroyPost(ForumPost $post)
    {
        $post->delete();

        return redirect()->route('admin.forum.index')
            ->with('success', 'Post supprimé avec succès.');
    }

    public function togglePin(ForumPost $post)
    {
        $post->update(['est_epingle' => !$post->est_epingle]);

        return back()->with('success', $post->est_epingle ? 'Post épinglé.' : 'Post désépinglé.');
    }
}

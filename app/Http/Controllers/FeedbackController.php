<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\FeedbackComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeedbackController extends Controller
{
    public function index(Request $request)
    {
        $query = Feedback::with(['user', 'votes'])
            ->withCount('comments');

        // Filtres
        if ($request->has('categorie') && $request->categorie !== '') {
            $query->where('categorie', $request->categorie);
        }

        if ($request->has('statut') && $request->statut !== '') {
            $query->where('statut', $request->statut);
        }

        if ($request->has('search') && $request->search !== '') {
            $query->where(function ($q) use ($request) {
                $q->where('titre', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        // Tri
        $sort = $request->get('sort', 'recent');
        switch ($sort) {
            case 'votes':
                $query->orderBy('votes_count', 'desc');
                break;
            case 'commentaires':
                $query->orderBy('commentaires_count', 'desc');
                break;
            case 'recent':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $feedbacks = $query->paginate(20);

        return view('feedback.index', compact('feedbacks'));
    }

    public function dashboard()
    {
        // Meilleurs feedbacks non résolus
        $meilleursNonResolus = Feedback::whereIn('statut', ['poste', 'traitement_en_cours'])
            ->orderBy('votes_count', 'desc')
            ->with(['user'])
            ->limit(10)
            ->get();

        // Meilleurs feedbacks de tous les temps
        $meilleursTousTemps = Feedback::orderBy('votes_count', 'desc')
            ->with(['user'])
            ->limit(10)
            ->get();

        // Feedbacks récemment terminés
        $recemmentTermines = Feedback::where('statut', 'termine')
            ->orderBy('updated_at', 'desc')
            ->with(['user'])
            ->limit(10)
            ->get();

        // Statistiques
        $stats = [
            'total' => Feedback::count(),
            'poste' => Feedback::where('statut', 'poste')->count(),
            'traitement_en_cours' => Feedback::where('statut', 'traitement_en_cours')->count(),
            'termine' => Feedback::where('statut', 'termine')->count(),
        ];

        return view('feedback.dashboard', compact(
            'meilleursNonResolus',
            'meilleursTousTemps',
            'recemmentTermines',
            'stats'
        ));
    }

    public function create()
    {
        // Récupérer les titres existants pour l'autocomplétion
        $titresExistants = Feedback::select('titre', 'id', 'votes_count')
            ->orderBy('votes_count', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($feedback) {
                return [
                    'id' => $feedback->id,
                    'titre' => $feedback->titre,
                    'votes' => $feedback->votes_count,
                ];
            });

        return view('feedback.create', compact('titresExistants'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'description' => 'required|string',
            'categorie' => 'required|in:demande,remerciement,erreur,conseil,autre',
        ]);

        $feedback = Feedback::create([
            ...$validated,
            'user_id' => auth()->id(),
            'statut' => 'poste',
        ]);

        return redirect()->route('feedback.show', $feedback)
            ->with('success', 'Feedback créé avec succès.');
    }

    public function show(Feedback $feedback)
    {
        $feedback->load(['user', 'comments.user', 'votes']);
        $hasVoted = auth()->check() ? $feedback->hasUserVoted(auth()->id()) : false;

        return view('feedback.show', compact('feedback', 'hasVoted'));
    }

    public function vote(Feedback $feedback)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $hasVoted = $feedback->toggleVote(auth()->id());

        return response()->json([
            'success' => true,
            'hasVoted' => $hasVoted,
            'votesCount' => $feedback->fresh()->votes_count,
        ]);
    }

    public function comment(Request $request, Feedback $feedback)
    {
        $validated = $request->validate([
            'contenu' => 'required|string',
        ]);

        FeedbackComment::create([
            'feedback_id' => $feedback->id,
            'user_id' => auth()->id(),
            'contenu' => $validated['contenu'],
        ]);

        $feedback->increment('commentaires_count');

        return redirect()->route('feedback.show', $feedback)
            ->with('success', 'Commentaire ajouté.');
    }

    // Admin uniquement
    public function adminUpdate(Request $request, Feedback $feedback)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'categorie' => 'sometimes|in:demande,remerciement,erreur,conseil,autre',
            'statut' => 'sometimes|in:poste,traitement_en_cours,termine,refuse,deja_fait',
        ]);

        $feedback->update($validated);

        return redirect()->route('feedback.show', $feedback)
            ->with('success', 'Feedback mis à jour.');
    }

    public function searchTitres(Request $request)
    {
        $query = $request->get('q', '');
        
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $feedbacks = Feedback::where('titre', 'like', '%' . $query . '%')
            ->select('id', 'titre', 'votes_count')
            ->orderBy('votes_count', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($feedback) {
                return [
                    'id' => $feedback->id,
                    'titre' => $feedback->titre,
                    'votes' => $feedback->votes_count,
                    'url' => route('feedback.show', $feedback),
                ];
            });

        return response()->json($feedbacks);
    }
}

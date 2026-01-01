<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
{
    /**
     * Liste des tickets de l'utilisateur
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $query = Ticket::where('user_id', $user->id)
            ->with(['assigneA', 'derniersMessages'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->filled('categorie')) {
            $query->where('categorie', $request->categorie);
        }

        $tickets = $query->paginate(15)->withQueryString();

        return view('tickets.index', compact('tickets'));
    }

    /**
     * Afficher le formulaire de création de ticket
     */
    public function create()
    {
        return view('tickets.create');
    }

    /**
     * Créer un nouveau ticket
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sujet' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'categorie' => 'required|in:technique,facturation,compte,autre',
            'priorite' => 'nullable|in:basse,normale,haute,urgente',
        ]);

        $ticket = Ticket::create([
            'numero_ticket' => Ticket::generateNumeroTicket(),
            'sujet' => $validated['sujet'],
            'description' => $validated['description'],
            'categorie' => $validated['categorie'],
            'priorite' => $validated['priorite'] ?? 'normale',
            'user_id' => Auth::id(),
            'statut' => 'ouvert',
        ]);

        return redirect()->route('tickets.show', $ticket)
            ->with('success', 'Votre ticket a été créé avec succès. Numéro : ' . $ticket->numero_ticket);
    }

    /**
     * Afficher un ticket
     */
    public function show(Ticket $ticket)
    {
        // Vérifier que l'utilisateur peut voir ce ticket
        if (!$ticket->user_id === Auth::id() && !Auth::user()->is_admin) {
            abort(403);
        }

        $messages = $ticket->messages()
            ->with('user')
            ->where(function($q) {
                if (!Auth::user()->is_admin) {
                    $q->where('est_interne', false);
                }
            })
            ->get();

        // Marquer les messages comme lus
        $ticket->messages()
            ->where('user_id', '!=', Auth::id())
            ->where('est_lu', false)
            ->update(['est_lu' => true]);

        return view('tickets.show', compact('ticket', 'messages'));
    }

    /**
     * Ajouter un message à un ticket
     */
    public function addMessage(Request $request, Ticket $ticket)
    {
        // Vérifier que l'utilisateur peut répondre à ce ticket
        if ($ticket->user_id !== Auth::id() && !Auth::user()->is_admin) {
            abort(403);
        }

        $validated = $request->validate([
            'message' => 'required|string|max:5000',
            'est_interne' => 'nullable|boolean',
        ]);

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'message' => $validated['message'],
            'est_interne' => Auth::user()->is_admin && ($validated['est_interne'] ?? false),
        ]);

        // Si le ticket était résolu et qu'un nouveau message est ajouté, le rouvrir
        if ($ticket->statut === 'resolu' && $ticket->user_id === Auth::id()) {
            $ticket->update(['statut' => 'ouvert']);
        }

        return back()->with('success', 'Message ajouté avec succès.');
    }

    /**
     * Liste des tickets (admin uniquement)
     */
    public function adminIndex(Request $request)
    {
        $query = Ticket::with(['user', 'assigneA', 'derniersMessages'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('numero_ticket', 'like', "%{$search}%")
                  ->orWhere('sujet', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->filled('priorite')) {
            $query->where('priorite', $request->priorite);
        }

        if ($request->filled('categorie')) {
            $query->where('categorie', $request->categorie);
        }

        if ($request->filled('assigne_a')) {
            if ($request->assigne_a === 'non_assignes') {
                $query->whereNull('assigne_a');
            } else {
                $query->where('assigne_a', $request->assigne_a);
            }
        }

        $tickets = $query->paginate(20)->withQueryString();
        $admins = User::where('is_admin', true)->get();

        return view('admin.tickets.index', compact('tickets', 'admins'));
    }

    /**
     * Afficher un ticket (admin uniquement)
     */
    public function adminShow(Ticket $ticket)
    {
        $messages = $ticket->messages()->with('user')->get();
        $admins = User::where('is_admin', true)->get();

        return view('admin.tickets.show', compact('ticket', 'messages', 'admins'));
    }

    /**
     * Mettre à jour un ticket (admin uniquement)
     */
    public function adminUpdate(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'statut' => 'nullable|in:ouvert,en_cours,resolu,ferme',
            'priorite' => 'nullable|in:basse,normale,haute,urgente',
            'assigne_a' => 'nullable|exists:users,id',
        ]);

        if (isset($validated['statut']) && $validated['statut'] === 'resolu') {
            $validated['resolu_at'] = now();
        } elseif ($ticket->statut === 'resolu' && isset($validated['statut']) && $validated['statut'] !== 'resolu') {
            $validated['resolu_at'] = null;
        }

        $ticket->update($validated);

        return back()->with('success', 'Ticket mis à jour avec succès.');
    }
}

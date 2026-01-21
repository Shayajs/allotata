<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KanbanBoard;
use App\Models\KanbanColumn;
use App\Models\KanbanCard;
use App\Models\Reservation;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KanbanController extends Controller
{
    /**
     * Affichage du board principal
     */
    public function index()
    {
        // Récupérer ou créer le board principal
        $board = KanbanBoard::firstOrCreate(
            ['nom' => 'Principal'],
            ['description' => 'Board Kanban principal pour la gestion des tâches']
        );

        // Créer les colonnes par défaut si elles n'existent pas
        if ($board->columns()->count() === 0) {
            $colonnes = [
                ['nom' => 'À faire', 'ordre' => 0, 'couleur' => '#3b82f6'],
                ['nom' => 'En cours', 'ordre' => 1, 'couleur' => '#f59e0b'],
                ['nom' => 'En attente', 'ordre' => 2, 'couleur' => '#8b5cf6'],
                ['nom' => 'Terminé', 'ordre' => 3, 'couleur' => '#10b981'],
            ];

            foreach ($colonnes as $colonne) {
                KanbanColumn::create([
                    'board_id' => $board->id,
                    'nom' => $colonne['nom'],
                    'ordre' => $colonne['ordre'],
                    'couleur' => $colonne['couleur'],
                ]);
            }
        }

        $board->load(['columns.cards.assignee', 'columns.cards.creator']);

        return view('admin.kanban.index', compact('board'));
    }

    /**
     * Création d'une carte
     */
    public function storeCard(Request $request)
    {
        try {
            $validated = $request->validate([
                'column_id' => 'required|exists:kanban_columns,id',
                'board_id' => 'required|exists:kanban_boards,id',
                'titre' => 'required|string|max:255',
                'description' => 'nullable|string',
                'type' => 'nullable|in:tache,reservation,ticket',
                'reference_id' => 'nullable|integer',
                'assignee_id' => 'nullable|exists:users,id',
                'priorite' => 'nullable|in:basse,normale,haute,urgente',
                'couleur' => 'nullable|string',
                'due_date' => 'nullable|date',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $e->errors(),
            ], 422);
        }

        // Déterminer l'ordre (dernier de la colonne)
        $lastCard = KanbanCard::where('column_id', $validated['column_id'])
            ->orderBy('ordre', 'desc')
            ->first();

        $validated['ordre'] = $lastCard ? $lastCard->ordre + 1 : 0;
        $validated['created_by'] = auth()->id();
        $validated['type'] = $validated['type'] ?? 'tache';
        $validated['priorite'] = $validated['priorite'] ?? 'normale';

        $card = KanbanCard::create($validated);

        return response()->json([
            'success' => true,
            'card' => $card->load('assignee', 'creator'),
        ]);
    }

    /**
     * Récupération d'une carte
     */
    public function showCard(KanbanCard $card)
    {
        return response()->json([
            'success' => true,
            'card' => $card->load('assignee', 'creator'),
        ]);
    }

    /**
     * Mise à jour d'une carte
     */
    public function updateCard(Request $request, KanbanCard $card)
    {
        $validated = $request->validate([
            'titre' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'column_id' => 'sometimes|exists:kanban_columns,id',
            'assignee_id' => 'nullable|exists:users,id',
            'priorite' => 'nullable|in:basse,normale,haute,urgente',
            'couleur' => 'nullable|string',
            'due_date' => 'nullable|date',
        ]);

        $card->update($validated);

        return response()->json([
            'success' => true,
            'card' => $card->load('assignee', 'creator'),
        ]);
    }

    /**
     * Déplacement d'une carte entre colonnes
     */
    public function moveCard(Request $request, KanbanCard $card)
    {
        $validated = $request->validate([
            'column_id' => 'required|exists:kanban_columns,id',
            'ordre' => 'nullable|integer',
        ]);

        $oldColumnId = $card->column_id;
        $newColumnId = $validated['column_id'];

        DB::transaction(function () use ($card, $validated, $oldColumnId, $newColumnId) {
            // Réorganiser les cartes de l'ancienne colonne
            if ($oldColumnId != $newColumnId) {
                KanbanCard::where('column_id', $oldColumnId)
                    ->where('ordre', '>', $card->ordre)
                    ->decrement('ordre');
            }

            // Insérer à la nouvelle position
            $newOrdre = $validated['ordre'] ?? 0;
            if ($oldColumnId == $newColumnId && $newOrdre > $card->ordre) {
                $newOrdre--;
            }

            // Décaler les cartes existantes
            KanbanCard::where('column_id', $newColumnId)
                ->where('ordre', '>=', $newOrdre)
                ->where('id', '!=', $card->id)
                ->increment('ordre');

            $card->update([
                'column_id' => $newColumnId,
                'ordre' => $newOrdre,
            ]);
        });

        return response()->json([
            'success' => true,
            'card' => $card->load('assignee', 'creator'),
        ]);
    }

    /**
     * Suppression d'une carte
     */
    public function deleteCard(KanbanCard $card)
    {
        $columnId = $card->column_id;
        $ordre = $card->ordre;

        DB::transaction(function () use ($card, $columnId, $ordre) {
            // Réorganiser les cartes restantes
            KanbanCard::where('column_id', $columnId)
                ->where('ordre', '>', $ordre)
                ->decrement('ordre');

            $card->delete();
        });

        return response()->json(['success' => true]);
    }

    /**
     * Synchronisation des réservations
     */
    public function syncFromReservations()
    {
        $board = KanbanBoard::where('nom', 'Principal')->first();
        if (!$board) {
            return response()->json(['error' => 'Board principal introuvable'], 404);
        }

        $columnEnAttente = $board->columns()->where('nom', 'À faire')->first();
        if (!$columnEnAttente) {
            return response()->json(['error' => 'Colonne "À faire" introuvable'], 404);
        }

        // Récupérer les réservations qui n'ont pas encore de carte Kanban
        $existingCardReservationIds = KanbanCard::where('type', 'reservation')
            ->whereNotNull('reference_id')
            ->pluck('reference_id')
            ->toArray();

        $reservations = Reservation::whereNotIn('id', $existingCardReservationIds)
            ->where('statut', 'en_attente')
            ->get();

        $created = 0;
        foreach ($reservations as $reservation) {
            KanbanCard::create([
                'column_id' => $columnEnAttente->id,
                'board_id' => $board->id,
                'titre' => "Réservation #{$reservation->id}",
                'description' => "Réservation pour {$reservation->entreprise->nom}",
                'type' => 'reservation',
                'reference_id' => $reservation->id,
                'created_by' => auth()->id(),
                'priorite' => 'normale',
                'ordre' => 0,
            ]);
            $created++;
        }

        return response()->json([
            'success' => true,
            'created' => $created,
        ]);
    }

    /**
     * Synchronisation des tickets
     */
    public function syncFromTickets()
    {
        $board = KanbanBoard::where('nom', 'Principal')->first();
        if (!$board) {
            return response()->json(['error' => 'Board principal introuvable'], 404);
        }

        $columnEnAttente = $board->columns()->where('nom', 'À faire')->first();
        if (!$columnEnAttente) {
            return response()->json(['error' => 'Colonne "À faire" introuvable'], 404);
        }

        $tickets = Ticket::where('statut', 'ouvert')->get();

        $created = 0;
        foreach ($tickets as $ticket) {
            // Vérifier si une carte existe déjà pour ce ticket
            $existingCard = KanbanCard::where('type', 'ticket')
                ->where('reference_id', $ticket->id)
                ->first();

            if (!$existingCard) {
                KanbanCard::create([
                    'column_id' => $columnEnAttente->id,
                    'board_id' => $board->id,
                    'titre' => "Ticket #{$ticket->id}: {$ticket->sujet}",
                    'description' => $ticket->description,
                    'type' => 'ticket',
                    'reference_id' => $ticket->id,
                    'created_by' => auth()->id(),
                    'priorite' => 'haute',
                    'ordre' => 0,
                ]);
                $created++;
            }
        }

        return response()->json([
            'success' => true,
            'created' => $created,
        ]);
    }
}

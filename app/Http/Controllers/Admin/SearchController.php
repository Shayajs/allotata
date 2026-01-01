<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Entreprise;
use App\Models\Reservation;
use App\Models\Ticket;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Recherche globale admin
     */
    public function index(Request $request)
    {
        $query = $request->get('q', '');
        
        if (strlen($query) < 2) {
            return view('admin.search', [
                'query' => $query,
                'results' => [],
                'counts' => [],
            ]);
        }

        $results = [
            'users' => $this->searchUsers($query),
            'entreprises' => $this->searchEntreprises($query),
            'reservations' => $this->searchReservations($query),
            'tickets' => $this->searchTickets($query),
        ];

        $counts = [
            'users' => $results['users']->count(),
            'entreprises' => $results['entreprises']->count(),
            'reservations' => $results['reservations']->count(),
            'tickets' => $results['tickets']->count(),
            'total' => array_sum(array_map(fn($r) => $r->count(), $results)),
        ];

        return view('admin.search', compact('query', 'results', 'counts'));
    }

    private function searchUsers(string $query)
    {
        return User::where(function($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")
              ->orWhere('email', 'like', "%{$query}%");
        })
        ->take(10)
        ->get();
    }

    private function searchEntreprises(string $query)
    {
        return Entreprise::where(function($q) use ($query) {
            $q->where('nom', 'like', "%{$query}%")
              ->orWhere('email', 'like', "%{$query}%")
              ->orWhere('telephone', 'like', "%{$query}%")
              ->orWhere('siren', 'like', "%{$query}%")
              ->orWhere('ville', 'like', "%{$query}%");
        })
        ->with('user')
        ->take(10)
        ->get();
    }

    private function searchReservations(string $query)
    {
        return Reservation::where(function($q) use ($query) {
            $q->where('type_service', 'like', "%{$query}%")
              ->orWhere('lieu', 'like', "%{$query}%")
              ->orWhereHas('user', function($userQ) use ($query) {
                  $userQ->where('name', 'like', "%{$query}%")
                        ->orWhere('email', 'like', "%{$query}%");
              })
              ->orWhereHas('entreprise', function($entQ) use ($query) {
                  $entQ->where('nom', 'like', "%{$query}%");
              });
        })
        ->with(['user', 'entreprise'])
        ->take(10)
        ->get();
    }

    private function searchTickets(string $query)
    {
        return Ticket::where(function($q) use ($query) {
            $q->where('numero_ticket', 'like', "%{$query}%")
              ->orWhere('sujet', 'like', "%{$query}%")
              ->orWhere('description', 'like', "%{$query}%")
              ->orWhereHas('user', function($userQ) use ($query) {
                  $userQ->where('name', 'like', "%{$query}%")
                        ->orWhere('email', 'like', "%{$query}%");
              });
        })
        ->with('user')
        ->take(10)
        ->get();
    }
}

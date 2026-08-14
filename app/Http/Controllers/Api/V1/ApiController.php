<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Entreprise;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Socle commun de l'API de gestion : resolution de l'entreprise, pagination,
 * periodes et forme des erreurs.
 *
 * Les reponses sont toujours du JSON, meme quand le client n'envoie pas d'en-tete
 * Accept : une erreur d'API ne doit jamais renvoyer une page HTML.
 */
abstract class ApiController extends Controller
{
    private const PAR_PAGE_MAX = 100;

    protected function utilisateur(Request $request): User
    {
        return $request->user();
    }

    /**
     * Retourne l'entreprise du slug si le porteur du jeton a le droit de la gerer.
     */
    protected function entreprise(Request $request, string $slug): Entreprise
    {
        $entreprise = Entreprise::where('slug', $slug)->first();

        if (! $entreprise) {
            $this->erreur('Entreprise introuvable.', 'entreprise_inconnue', 404);
        }

        if (! $entreprise->peutEtreGereePar($this->utilisateur($request))) {
            $this->erreur(
                'Ce jeton ne donne pas accès à la gestion de cette entreprise.',
                'entreprise_hors_perimetre',
                403
            );
        }

        return $entreprise;
    }

    /**
     * Entreprises que le porteur du jeton peut gerer : les siennes et celles ou il
     * est administrateur.
     */
    protected function entreprisesAccessibles(Request $request)
    {
        $user = $this->utilisateur($request);

        return Entreprise::query()
            ->where(fn ($requete) => $requete
                ->where('user_id', $user->id)
                ->orWhereHas('membres', fn ($membres) => $membres
                    ->where('user_id', $user->id)
                    ->where('role', 'administrateur')
                )
            )
            ->orderBy('nom');
    }

    protected function parPage(Request $request, int $defaut = 25): int
    {
        $demande = (int) $request->query('par_page', $defaut);

        return max(1, min($demande, self::PAR_PAGE_MAX));
    }

    /**
     * Enveloppe commune des listes : les donnees d'un cote, la pagination de l'autre.
     *
     * @param  callable(mixed): array<string, mixed>  $forme
     * @param  array<string, mixed>  $complements
     */
    protected function liste(LengthAwarePaginator $page, callable $forme, array $complements = []): JsonResponse
    {
        return response()->json(array_merge([
            'donnees' => array_map($forme, $page->items()),
            'pagination' => [
                'page' => $page->currentPage(),
                'par_page' => $page->perPage(),
                'total' => $page->total(),
                'total_pages' => $page->lastPage(),
            ],
        ], $complements));
    }

    /**
     * Pagine une liste deja construite en memoire.
     *
     * @param  list<mixed>  $elements
     */
    protected function paginer(Request $request, array $elements, int $defaut = 25): LengthAwarePaginator
    {
        $parPage = $this->parPage($request, $defaut);
        $page = max(1, (int) $request->query('page', 1));

        return new \Illuminate\Pagination\LengthAwarePaginator(
            array_slice($elements, ($page - 1) * $parPage, $parPage),
            count($elements),
            $parPage,
            $page,
        );
    }

    /**
     * Periode demandee via du/au, bornee sur des jours entiers.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function periode(Request $request, int $joursParDefaut = 30): array
    {
        $du = $this->date($request->query('du')) ?? now()->subDays($joursParDefaut);
        $au = $this->date($request->query('au')) ?? now();

        if ($du->gt($au)) {
            $this->erreur('La date de début est postérieure à la date de fin.', 'periode_invalide', 422);
        }

        return [$du->startOfDay(), $au->endOfDay()];
    }

    protected function date(mixed $valeur): ?Carbon
    {
        if (! is_string($valeur) || trim($valeur) === '') {
            return null;
        }

        try {
            return Carbon::parse($valeur);
        } catch (\Throwable) {
            $this->erreur("Date illisible : {$valeur}. Format attendu AAAA-MM-JJ.", 'date_invalide', 422);
        }
    }

    /**
     * @return never
     */
    protected function erreur(string $message, string $code, int $statut)
    {
        abort(response()->json(['message' => $message, 'code' => $code], $statut));
    }
}

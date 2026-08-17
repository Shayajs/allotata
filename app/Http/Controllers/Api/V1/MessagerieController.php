<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Conversation;
use App\Services\MessagerieViewService;
use App\Support\ApiV1Presenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessagerieController extends ApiController
{
    public function conversations(Request $request): JsonResponse
    {
        $user = $this->utilisateur($request);
        $service = app(MessagerieViewService::class);
        $liste = collect();

        if ($user->est_client) {
            $liste = $liste->concat($service->clientConversations($user));
        }

        foreach ($this->entreprisesAccessibles($request)->get() as $entreprise) {
            $liste = $liste->concat($service->entrepriseConversations($entreprise));
        }

        $liste = $liste->unique('id')->sortByDesc(fn ($c) => $c->dernier_message_at?->timestamp ?? 0)->values();

        return response()->json([
            'donnees' => $liste->take(50)->map(fn ($c) => ApiV1Presenter::conversation(
                $c->loadMissing(['entreprise:id,nom', 'user:id,name', 'dernierMessage', 'messages']),
                $user->id
            ))->all(),
        ]);
    }

    public function messages(Request $request, int $conversation): JsonResponse
    {
        $user = $this->utilisateur($request);
        $modele = Conversation::with(['messages.user', 'entreprise'])->find($conversation);

        if (! $modele) {
            $this->erreur('Conversation introuvable.', 'conversation_inconnue', 404);
        }

        $gerant = $modele->entreprise && $modele->entreprise->peutEtreGereePar($user);
        if ((int) $modele->user_id !== (int) $user->id && ! $gerant && ! $user->is_admin) {
            $this->erreur('Cette conversation n’est pas dans votre périmètre.', 'hors_perimetre', 403);
        }

        $limite = min(100, max(1, (int) $request->query('limit', 50)));
        $messages = $modele->messages()->with('user:id,name')->orderByDesc('id')->limit($limite)->get()->reverse()->values();

        return response()->json([
            'conversation' => ApiV1Presenter::conversation($modele->loadMissing(['dernierMessage', 'user:id,name']), $user->id),
            'donnees' => $messages->map(fn ($m) => ApiV1Presenter::message($m))->all(),
        ]);
    }
}

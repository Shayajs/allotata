<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Canal de présence pour les utilisateurs
Broadcast::channel('presence.users', function ($user) {
    return [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
    ];
});

// Channels pour le Kanban
Broadcast::channel('kanban.{boardId}', function ($user) {
    // Seuls les admins peuvent accéder au Kanban
    return $user->is_admin ?? false;
});

// Channels pour les Notes (Presence Channel pour la collaboration)
// Pusher utilise le préfixe "presence-" pour les canaux de présence
Broadcast::channel('presence-note.{noteId}', function ($user, $noteId) {
    // Logger pour debug
    \Log::info('Broadcasting auth pour note', [
        'user_id' => $user->id,
        'user_is_admin' => $user->is_admin ?? false,
        'note_id' => $noteId,
    ]);
    
    // Seuls les admins peuvent accéder aux notes
    if (!($user->is_admin ?? false)) {
        \Log::warning('Accès refusé: utilisateur pas admin', ['user_id' => $user->id]);
        return false;
    }
    
    // Vérifier que l'utilisateur est collaborateur de la note
    $isCollaborator = \App\Models\NoteCollaborator::where('note_id', $noteId)
        ->where('user_id', $user->id)
        ->exists();
    
    if (!$isCollaborator) {
        \Log::warning('Accès refusé: utilisateur pas collaborateur', [
            'user_id' => $user->id,
            'note_id' => $noteId
        ]);
        return false;
    }
    
    // Retourner les données pour le Presence Channel
    \Log::info('Accès autorisé au canal Presence', [
        'user_id' => $user->id,
        'note_id' => $noteId
    ]);
    
    return [
        'id' => $user->id,
        'user_id' => $user->id, // Alias pour compatibilité
        'name' => $user->name,
        'email' => $user->email,
        'joined_at' => now()->timestamp, // Pour déterminer qui est le Master
        'info' => [ // Info supplémentaire pour Pusher
            'name' => $user->name,
        ],
    ];
});

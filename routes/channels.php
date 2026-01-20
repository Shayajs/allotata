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

// Channels pour les Notes (canaux privés)
Broadcast::channel('note.{noteId}', function ($user, $noteId) {
    // Seuls les admins peuvent accéder aux notes
    if (!($user->is_admin ?? false)) {
        return false;
    }
    
    // Vérifier que l'utilisateur est collaborateur de la note
    return \App\Models\NoteCollaborator::where('note_id', $noteId)
        ->where('user_id', $user->id)
        ->exists();
});

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
// IMPORTANT: Ne pas ajouter le préfixe "presence-" ici, Laravel Echo le fait automatiquement
Broadcast::channel('note.{noteId}', function ($user, $noteId) {
    // IMPORTANT: Vérifier que l'utilisateur est bien fourni (authentifié)
    if (!$user) {
        \Log::error('❌ Broadcasting auth: utilisateur non authentifié');
        return false;
    }
    
    // Logger pour debug avec plus de détails
    \Log::info('🔐 Broadcasting auth pour note', [
        'user_id' => $user->id ?? 'NULL',
        'user_email' => $user->email ?? 'NULL',
        'user_is_admin' => $user->is_admin ?? false,
        'note_id' => $noteId,
        'note_id_type' => gettype($noteId),
        'request_method' => request()->method(),
        'request_path' => request()->path(),
    ]);
    
    // Seuls les admins peuvent accéder aux notes
    if (!($user->is_admin ?? false)) {
        \Log::warning('❌ Accès refusé: utilisateur pas admin', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'is_admin_value' => $user->is_admin,
        ]);
        return false;
    }
    
    // Convertir noteId en integer si c'est une string
    $noteIdInt = is_numeric($noteId) ? (int) $noteId : $noteId;
    
    // Vérifier que la note existe
    try {
        $noteExists = \App\Models\Note::where('id', $noteIdInt)->exists();
        if (!$noteExists) {
            \Log::warning('❌ Accès refusé: note n\'existe pas', [
                'user_id' => $user->id,
                'note_id' => $noteIdInt,
            ]);
            return false;
        }
    } catch (\Exception $e) {
        \Log::error('❌ Erreur lors de la vérification de la note: ' . $e->getMessage());
        return false;
    }
    
    // Vérifier que l'utilisateur est collaborateur de la note
    // Si ce n'est pas le cas, l'ajouter automatiquement (cas où on ouvre la page directement)
    try {
        $isCollaborator = \App\Models\NoteCollaborator::where('note_id', $noteIdInt)
            ->where('user_id', $user->id)
            ->exists();
        
        if (!$isCollaborator) {
            \Log::info('⚠️ Utilisateur pas encore collaborateur, ajout automatique...', [
                'user_id' => $user->id,
                'note_id' => $noteIdInt
            ]);
            
            // Ajouter automatiquement comme collaborateur
            \App\Models\NoteCollaborator::create([
                'note_id' => $noteIdInt,
                'user_id' => $user->id,
                'derniere_activite' => now(),
            ]);
            \Log::info('✅ Collaborateur ajouté automatiquement');
        }
    } catch (\Exception $e) {
        \Log::error('❌ Erreur lors de l\'ajout/vérification du collaborateur: ' . $e->getMessage());
        // Ne pas bloquer si l'ajout échoue, on autorise quand même
    }
    
    // Retourner les données pour le Presence Channel (IMPORTANT: doit être un tableau)
    $userData = [
        'id' => $user->id,
        'user_id' => $user->id, // Alias pour compatibilité
        'name' => $user->name,
        'email' => $user->email,
        'joined_at' => now()->timestamp, // Pour déterminer qui est le Master
        'info' => [ // Info supplémentaire pour Pusher
            'name' => $user->name,
        ],
    ];
    
    \Log::info('✅ Accès autorisé au canal Presence', [
        'user_id' => $user->id,
        'note_id' => $noteIdInt,
        'user_data' => $userData
    ]);
    
    return $userData;
});

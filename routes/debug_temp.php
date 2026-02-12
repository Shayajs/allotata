<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\CourseLesson;
use Laravel\Cashier\Subscription;

// Route de diagnostic pour les leçons de cours
Route::get('/debug-lesson/{id}', function ($id) {
    if (!auth()->check() || !auth()->user()->is_admin) {
        abort(403, 'Admin requis');
    }
    
    $lesson = CourseLesson::find($id);
    
    if (!$lesson) {
        return "Leçon ID $id introuvable.";
    }
    
    $html = "<h1>Debug Leçon #{$lesson->id}: {$lesson->titre}</h1>";
    $html .= "<style>
        body { font-family: sans-serif; padding: 20px; }
        .section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        pre { background: #f5f5f5; padding: 15px; overflow: auto; max-height: 400px; }
        h2 { color: #333; border-bottom: 2px solid #22c55e; padding-bottom: 5px; }
        .info { background: #e0f2fe; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .warning { background: #fef3c7; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error { background: #fee2e2; padding: 10px; border-radius: 4px; margin: 10px 0; }
    </style>";
    
    // Infos générales
    $html .= "<div class='section'>";
    $html .= "<h2>Informations générales</h2>";
    $html .= "<ul>";
    $html .= "<li><strong>ID:</strong> {$lesson->id}</li>";
    $html .= "<li><strong>Titre:</strong> {$lesson->titre}</li>";
    $html .= "<li><strong>Type:</strong> {$lesson->type}</li>";
    $html .= "<li><strong>Est brouillon:</strong> " . ($lesson->is_draft ? 'OUI' : 'NON') . "</li>";
    $html .= "<li><strong>Publié le:</strong> " . ($lesson->published_at ? $lesson->published_at->format('d/m/Y H:i') : 'Jamais') . "</li>";
    $html .= "<li><strong>Est actif:</strong> " . ($lesson->est_actif ? 'OUI' : 'NON') . "</li>";
    $html .= "</ul>";
    $html .= "</div>";
    
    // Blocs JSON
    $html .= "<div class='section'>";
    $html .= "<h2>contenu_blocks_json (Blocs stockés)</h2>";
    $blocksJson = $lesson->contenu_blocks_json;
    if (empty($blocksJson)) {
        $html .= "<div class='warning'>⚠️ VIDE - Aucun bloc stocké</div>";
    } else {
        $html .= "<div class='info'>✓ " . count($blocksJson) . " bloc(s) trouvé(s)</div>";
        $html .= "<pre>" . htmlspecialchars(json_encode($blocksJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";
    }
    $html .= "</div>";
    
    // HTML stocké
    $html .= "<div class='section'>";
    $html .= "<h2>contenu_rich_html (HTML stocké en BDD)</h2>";
    $richHtml = $lesson->contenu_rich_html;
    if (empty($richHtml)) {
        $html .= "<div class='warning'>⚠️ VIDE - Aucun HTML stocké</div>";
    } else {
        $html .= "<div class='info'>✓ HTML stocké: " . strlen($richHtml) . " caractères</div>";
        $html .= "<h3>Code source (échappé):</h3>";
        $html .= "<pre>" . htmlspecialchars($richHtml) . "</pre>";
    }
    $html .= "</div>";
    
    // Test de génération HTML
    $html .= "<div class='section'>";
    $html .= "<h2>Test: generateHtmlFromBlocks()</h2>";
    try {
        $generatedHtml = $lesson->generateHtmlFromBlocks();
        if (empty($generatedHtml)) {
            $html .= "<div class='warning'>⚠️ La génération retourne une chaîne vide</div>";
        } else {
            $html .= "<div class='info'>✓ HTML généré: " . strlen($generatedHtml) . " caractères</div>";
            $html .= "<h3>Code source généré (échappé):</h3>";
            $html .= "<pre>" . htmlspecialchars($generatedHtml) . "</pre>";
        }
    } catch (\Exception $e) {
        $html .= "<div class='error'>❌ Erreur: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    $html .= "</div>";
    
    // Rendu visuel
    $html .= "<div class='section'>";
    $html .= "<h2>Rendu visuel du contenu_rich_html</h2>";
    if (!empty($richHtml)) {
        $html .= "<div style='border: 2px solid #22c55e; padding: 20px; border-radius: 8px; background: white;'>";
        $html .= $richHtml;
        $html .= "</div>";
    } else {
        $html .= "<div class='warning'>Rien à afficher (HTML vide)</div>";
    }
    $html .= "</div>";
    
    // Action de republication
    $html .= "<div class='section'>";
    $html .= "<h2>Actions</h2>";
    $html .= "<form method='POST' action='/debug-lesson/{$lesson->id}/regenerate'>";
    $html .= csrf_field();
    $html .= "<button type='submit' style='background: #22c55e; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;'>Régénérer le HTML depuis les blocs</button>";
    $html .= "</form>";
    $html .= "</div>";
    
    return $html;
})->middleware('auth');

Route::post('/debug-lesson/{id}/regenerate', function ($id) {
    if (!auth()->check() || !auth()->user()->is_admin) {
        abort(403, 'Admin requis');
    }
    
    $lesson = CourseLesson::findOrFail($id);
    
    try {
        $generatedHtml = $lesson->generateHtmlFromBlocks();
        $lesson->contenu_rich_html = $generatedHtml;
        $lesson->save();
        
        return redirect("/debug-lesson/{$id}")->with('success', 'HTML régénéré avec succès !');
    } catch (\Exception $e) {
        return redirect("/debug-lesson/{$id}")->with('error', 'Erreur: ' . $e->getMessage());
    }
})->middleware('auth');

Route::get('/debug-cannelle', function () {
    $email = 'cannelle.nebot@gmail.com';
    $user = User::where('email', $email)->first();
    
    if (!$user) {
        return "Utilisateur $email introuvable.";
    }
    
    $subs = Subscription::where('user_id', $user->id)->get();
    
    $html = "<h1>Debug pour $email (ID: {$user->id}, Stripe: {$user->stripe_id})</h1>";
    $html .= "<h2>Table 'subscriptions' (Cashier)</h2>";
    $html .= "<table border='1' cellpadding='5'><tr><th>ID</th><th>Name</th><th>Stripe ID</th><th>Status</th><th>Ends At</th><th>Actions</th></tr>";
    
    foreach ($subs as $sub) {
        $html .= "<tr>
            <td>{$sub->id}</td>
            <td>{$sub->name}</td>
            <td>{$sub->stripe_id}</td>
            <td>{$sub->stripe_status}</td>
            <td>{$sub->ends_at}</td>
            <td>
                <form method='POST' action='/debug-cannelle/force-cancel/{$sub->id}'>
                    " . csrf_field() . "
                    <button type='submit' style='color:red'>Forcer Annulation (DB)</button>
                </form>
            </td>
        </tr>";
    }
    $html .= "</table>";
    
    $html .= "<h2>Check Méthodes</h2>";
    $html .= "<ul>";
    $html .= "<li>subscribed('default'): " . ($user->subscribed('default') ? 'OUI' : 'NON') . "</li>";
    $html .= "<li>onGracePeriod: " . ($user->subscription('default')?->onGracePeriod() ? 'OUI' : 'NON') . "</li>";
    $html .= "<li>aAbonnementActif(): " . ($user->aAbonnementActif() ? 'OUI' : 'NON') . "</li>";
    $html .= "</ul>";

    return $html;
});

Route::post('/debug-cannelle/force-cancel/{id}', function ($id) {
    try {
        $sub = Subscription::findOrFail($id);
        $sub->update([
            'stripe_status' => 'canceled',
            'ends_at' => now()->subDay(),
        ]);
        return redirect('/debug-cannelle')->with('msg', 'Abonnement forcé à canceled + ends_at yesterday');
    } catch (\Exception $e) {
        return "Erreur : " . $e->getMessage();
    }
});

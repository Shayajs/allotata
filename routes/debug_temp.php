<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Laravel\Cashier\Subscription;

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

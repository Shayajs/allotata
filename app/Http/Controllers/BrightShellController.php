<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Services\BrightShellImapService;

class BrightShellController extends Controller
{
    /**
     * Infos entreprise BrightShell
     */
    private function getEntrepriseInfo(): array
    {
        return [
            'nom' => 'BrightShell EI',
            'forme_juridique' => 'Entrepreneur Individuel',
            'responsable' => 'Lucas Espinar',
            'siret' => '994 535 904 00019',
            'email' => 'lucas.espinar@brightshell.fr',
            'telephone' => '06.44.07.30.37',
            'site' => 'https://brightshell.fr',
        ];
    }

    public function installer()
    {
        return view('brightshell.installer');
    }
    
    /**
     * Récupère les couleurs PDF personnalisées
     */
    private function getPdfColors(): array
    {
        $defaults = [
            'primary' => '#5bbce4',
            'secondary' => '#0a0e1a',
            'text' => '#1a1a1a',
            'muted' => '#6b7280',
            'background' => '#f9fafb',
            'border' => '#e5e7eb',
            'success' => '#10b981',
        ];
        
        try {
            if (\Schema::hasTable('brightshell_settings')) {
                $settings = DB::table('brightshell_settings')
                    ->where('key', 'like', 'pdf_color_%')
                    ->pluck('value', 'key');
                
                foreach ($settings as $key => $value) {
                    $colorKey = str_replace('pdf_color_', '', $key);
                    if (isset($defaults[$colorKey])) {
                        $defaults[$colorKey] = $value;
                    }
                }
            }
        } catch (\Exception $e) {}
        
        return $defaults;
    }

    /**
     * Dashboard principal
     */
    public function index()
    {
        $stats = $this->getStats();
        $entreprise = $this->getEntrepriseInfo();
        
        return view('brightshell.dashboard', compact('stats', 'entreprise'));
    }

    /**
     * Calcul des statistiques dashboard
     */
    private function getStats(): array
    {
        $currentYear = date('Y');
        $currentMonth = date('m');
        
        // Stats depuis les tables brightshell_* si elles existent
        $caAnnuel = 0;
        $caMensuel = 0;
        $clientsCount = 0;
        $devisEnCours = 0;
        $facturesImpayees = 0;
        $projetsActifs = 0;
        
        try {
            if (\Schema::hasTable('brightshell_factures')) {
                $caAnnuel = DB::table('brightshell_factures')
                    ->whereYear('created_at', $currentYear)
                    ->where('statut', 'payee')
                    ->sum('montant_total');
                    
                $caMensuel = DB::table('brightshell_factures')
                    ->whereYear('created_at', $currentYear)
                    ->whereMonth('created_at', $currentMonth)
                    ->where('statut', 'payee')
                    ->sum('montant_total');
                    
                $facturesImpayees = DB::table('brightshell_factures')
                    ->where('statut', 'envoyee')
                    ->count();
            }
            
            if (\Schema::hasTable('brightshell_clients')) {
                $clientsCount = DB::table('brightshell_clients')->count();
            }
            
            if (\Schema::hasTable('brightshell_devis')) {
                $devisEnCours = DB::table('brightshell_devis')
                    ->whereIn('statut', ['brouillon', 'envoye'])
                    ->count();
            }
            
            if (\Schema::hasTable('brightshell_projets')) {
                $projetsActifs = DB::table('brightshell_projets')
                    ->where('statut', 'en_cours')
                    ->count();
            }
        } catch (\Exception $e) {
            // Tables pas encore créées
        }
        
        return [
            'ca_annuel' => $caAnnuel,
            'ca_mensuel' => $caMensuel,
            'clients' => $clientsCount,
            'devis_en_cours' => $devisEnCours,
            'factures_impayees' => $facturesImpayees,
            'projets_actifs' => $projetsActifs,
            'taux_cotisations' => 0.212, // 21.2% en 2026
            'seuil_tva' => 36800,
            'seuil_micro' => 77700,
        ];
    }

    // ==========================================
    // CLIENTS
    // ==========================================
    
    public function clients()
    {
        $clients = [];
        $potentiels = [];
        
        try {
            if (\Schema::hasTable('brightshell_clients')) {
                $clients = DB::table('brightshell_clients')
                    ->orderBy('created_at', 'desc')
                    ->get();
            }
            
            // On récupère les entreprises inscrites sur Allotata qui ne sont pas encore des clients BrightShell
            if (\Schema::hasTable('entreprises')) {
                $potentiels = DB::table('entreprises')
                    ->leftJoin('users', 'entreprises.user_id', '=', 'users.id')
                    ->select('entreprises.*', 'users.name as owner_name', 'users.email as owner_email')
                    ->whereNotIn('entreprises.nom', function($query) {
                        $query->select('societe')->from('brightshell_clients')->whereNotNull('societe');
                    })
                    ->orderBy('entreprises.created_at', 'desc')
                    ->get();
            }
        } catch (\Exception $e) {}
        
        return view('brightshell.clients.index', compact('clients', 'potentiels'));
    }
    
    public function clientCreate()
    {
        return view('brightshell.clients.form', ['client' => null]);
    }
    
    public function clientStore(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'telephone' => 'nullable|string|max:20',
            'societe' => 'nullable|string|max:255',
            'siret' => 'nullable|string|max:14',
            'adresse' => 'nullable|string',
            'code_postal' => 'nullable|string|max:10',
            'ville' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);
        
        $validated['created_at'] = now();
        $validated['updated_at'] = now();
        
        DB::table('brightshell_clients')->insert($validated);
        
        return redirect()->route('brightshell.clients')->with('success', 'Client créé avec succès.');
    }
    
    public function clientEdit($id)
    {
        $client = DB::table('brightshell_clients')->find($id);
        if (!$client) abort(404);
        
        return view('brightshell.clients.form', compact('client'));
    }
    
    public function clientUpdate(Request $request, $id)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'telephone' => 'nullable|string|max:20',
            'societe' => 'nullable|string|max:255',
            'siret' => 'nullable|string|max:14',
            'adresse' => 'nullable|string',
            'code_postal' => 'nullable|string|max:10',
            'ville' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);
        
        $validated['updated_at'] = now();
        
        DB::table('brightshell_clients')->where('id', $id)->update($validated);
        
        return redirect()->route('brightshell.clients')->with('success', 'Client mis à jour.');
    }
    
    public function clientDelete($id)
    {
        DB::table('brightshell_clients')->where('id', $id)->delete();
        return redirect()->route('brightshell.clients')->with('success', 'Client supprimé.');
    }

    // ==========================================
    // DEVIS
    // ==========================================
    
    public function devis()
    {
        $devis = [];
        try {
            if (\Schema::hasTable('brightshell_devis')) {
                $devis = DB::table('brightshell_devis')
                    ->leftJoin('brightshell_clients', 'brightshell_devis.client_id', '=', 'brightshell_clients.id')
                    ->select('brightshell_devis.*', 'brightshell_clients.nom as client_nom', 'brightshell_clients.societe as client_societe')
                    ->orderBy('brightshell_devis.created_at', 'desc')
                    ->get();
            }
        } catch (\Exception $e) {}
        
        return view('brightshell.devis.index', compact('devis'));
    }
    
    public function devisCreate()
    {
        $clients = DB::table('brightshell_clients')->orderBy('nom')->get();
        return view('brightshell.devis.form', ['devis' => null, 'clients' => $clients]);
    }
    
    public function devisStore(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|integer',
            'date_devis' => 'nullable|date',
            'objet' => 'required|string|max:255',
            'lignes' => 'required|array|min:1',
            'notes' => 'nullable|string',
            'validite_jours' => 'nullable|integer|min:1',
            'mode_tva' => 'required|in:non_assujetti,ht,ttc',
            'taux_tva' => 'nullable|numeric|min:0|max:100',
        ]);
        
        // Calcul du montant HT (somme des lignes)
        $montantHt = 0;
        foreach ($validated['lignes'] as $ligne) {
            if (!empty($ligne['description']) && isset($ligne['quantite'])) {
                $pu = floatval($ligne['prix_unitaire'] ?? 0);
                if (!empty($ligne['sous_lignes'])) {
                    $pu = 0;
                    foreach ($ligne['sous_lignes'] as $sl) {
                        $pu += floatval($sl['quantite'] ?? 0) * floatval($sl['prix_unitaire'] ?? 0);
                    }
                }
                $montantHt += floatval($ligne['quantite']) * $pu;
            }
        }
        
        // Calcul TVA et Total selon le mode
        $modeTva = $validated['mode_tva'];
        $tauxTva = floatval($validated['taux_tva'] ?? 20);
        $montantTva = 0;
        $montantTotal = $montantHt;
        
        if ($modeTva === 'ht') {
            // Prix saisis HT, on ajoute la TVA
            $montantTva = $montantHt * ($tauxTva / 100);
            $montantTotal = $montantHt + $montantTva;
        } elseif ($modeTva === 'ttc') {
            // Prix saisis TTC, le montant HT contient déjà le TTC
            $montantTotal = $montantHt;
            $htFromTtc = $montantHt / (1 + $tauxTva / 100);
            $montantTva = $montantHt - $htFromTtc;
            $montantHt = $htFromTtc;
        }
        // Si non_assujetti, pas de TVA, montant_total = montant_ht
        
        // Génération numéro
        $lastNum = DB::table('brightshell_devis')->whereYear('created_at', date('Y'))->count() + 1;
        $numero = 'DEV-' . date('Y') . '-' . str_pad($lastNum, 4, '0', STR_PAD_LEFT);
        
        $devisId = DB::table('brightshell_devis')->insertGetId([
            'numero' => $numero,
            'client_id' => $validated['client_id'],
            'date_devis' => $validated['date_devis'] ?? now()->format('Y-m-d'),
            'objet' => $validated['objet'],
            'lignes' => json_encode($validated['lignes']),
            'montant_ht' => round($montantHt, 2),
            'mode_tva' => $modeTva,
            'taux_tva' => $tauxTva,
            'montant_tva' => round($montantTva, 2),
            'montant_total' => round($montantTotal, 2),
            'notes' => $validated['notes'] ?? null,
            'validite_jours' => $validated['validite_jours'] ?? 30,
            'statut' => 'brouillon',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        return redirect()->route('brightshell.devis.show', $devisId)->with('success', 'Devis créé avec succès.');
    }
    
    public function devisShow($id)
    {
        $devis = DB::table('brightshell_devis')
            ->leftJoin('brightshell_clients', 'brightshell_devis.client_id', '=', 'brightshell_clients.id')
            ->select('brightshell_devis.*', 'brightshell_clients.nom as client_nom', 'brightshell_clients.prenom as client_prenom', 
                     'brightshell_clients.societe as client_societe', 'brightshell_clients.email as client_email',
                     'brightshell_clients.adresse as client_adresse', 'brightshell_clients.code_postal as client_cp',
                     'brightshell_clients.ville as client_ville', 'brightshell_clients.siret as client_siret')
            ->where('brightshell_devis.id', $id)
            ->first();
            
        if (!$devis) abort(404);
        
        $devis->lignes = json_decode($devis->lignes, true) ?? [];
        $entreprise = $this->getEntrepriseInfo();
        
        return view('brightshell.devis.show', compact('devis', 'entreprise'));
    }
    
    public function devisConvertToFacture($id)
    {
        $devis = DB::table('brightshell_devis')->find($id);
        if (!$devis) abort(404);
        
        // Génération numéro facture
        $lastNum = DB::table('brightshell_factures')->whereYear('created_at', date('Y'))->count() + 1;
        $numero = 'FAC-' . date('Y') . '-' . str_pad($lastNum, 4, '0', STR_PAD_LEFT);
        
        $factureId = DB::table('brightshell_factures')->insertGetId([
            'numero' => $numero,
            'client_id' => $devis->client_id,
            'date_facture' => now()->format('Y-m-d'),
            'devis_id' => $devis->id,
            'objet' => $devis->objet,
            'lignes' => $devis->lignes,
            'montant_total' => $devis->montant_total ?? $devis->montant_ht,
            'mode_tva' => $devis->mode_tva ?? 'non_assujetti',
            'taux_tva' => $devis->taux_tva ?? 20,
            'montant_tva' => $devis->montant_tva ?? 0,
            'notes' => $devis->notes,
            'echeance_jours' => 30,
            'statut' => 'brouillon',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Marquer le devis comme accepté
        DB::table('brightshell_devis')->where('id', $id)->update([
            'statut' => 'accepte',
            'updated_at' => now(),
        ]);
        
        return redirect()->route('brightshell.factures.show', $factureId)->with('success', 'Devis converti en facture.');
    }
    
    public function devisEdit($id)
    {
        $devis = DB::table('brightshell_devis')->find($id);
        if (!$devis) abort(404);
        
        $devis->lignes = json_decode($devis->lignes, true) ?? [];
        $clients = DB::table('brightshell_clients')->orderBy('nom')->get();
        
        return view('brightshell.devis.form', compact('devis', 'clients'));
    }
    
    public function devisUpdate(Request $request, $id)
    {
        $validated = $request->validate([
            'client_id' => 'required|integer',
            'date_devis' => 'nullable|date',
            'objet' => 'required|string|max:255',
            'lignes' => 'required|array|min:1',
            'notes' => 'nullable|string',
            'validite_jours' => 'nullable|integer|min:1',
            'mode_tva' => 'required|in:non_assujetti,ht,ttc',
            'taux_tva' => 'nullable|numeric|min:0|max:100',
        ]);
        
        // Calcul du montant HT
        $montantHt = 0;
        foreach ($validated['lignes'] as $ligne) {
            if (!empty($ligne['description']) && isset($ligne['quantite'])) {
                $pu = floatval($ligne['prix_unitaire'] ?? 0);
                if (!empty($ligne['sous_lignes'])) {
                    $pu = 0;
                    foreach ($ligne['sous_lignes'] as $sl) {
                        $pu += floatval($sl['quantite'] ?? 0) * floatval($sl['prix_unitaire'] ?? 0);
                    }
                }
                $montantHt += floatval($ligne['quantite']) * $pu;
            }
        }
        
        // Calcul TVA et Total selon le mode
        $modeTva = $validated['mode_tva'];
        $tauxTva = floatval($validated['taux_tva'] ?? 20);
        $montantTva = 0;
        $montantTotal = $montantHt;
        
        if ($modeTva === 'ht') {
            $montantTva = $montantHt * ($tauxTva / 100);
            $montantTotal = $montantHt + $montantTva;
        } elseif ($modeTva === 'ttc') {
            $montantTotal = $montantHt;
            $htFromTtc = $montantHt / (1 + $tauxTva / 100);
            $montantTva = $montantHt - $htFromTtc;
            $montantHt = $htFromTtc;
        }
        
        DB::table('brightshell_devis')->where('id', $id)->update([
            'client_id' => $validated['client_id'],
            'date_devis' => $validated['date_devis'] ?? null,
            'objet' => $validated['objet'],
            'lignes' => json_encode($validated['lignes']),
            'montant_ht' => round($montantHt, 2),
            'mode_tva' => $modeTva,
            'taux_tva' => $tauxTva,
            'montant_tva' => round($montantTva, 2),
            'montant_total' => round($montantTotal, 2),
            'notes' => $validated['notes'] ?? null,
            'validite_jours' => $validated['validite_jours'] ?? 30,
            'updated_at' => now(),
        ]);
        
        return redirect()->route('brightshell.devis.show', $id)->with('success', 'Devis mis à jour.');
    }
    
    public function devisDelete($id)
    {
        DB::table('brightshell_devis')->where('id', $id)->delete();
        return redirect()->route('brightshell.devis')->with('success', 'Devis supprimé.');
    }
    
    public function devisUpdateStatus(Request $request, $id)
    {
        $statut = $request->input('statut');
        if (!in_array($statut, ['brouillon', 'envoye', 'accepte', 'refuse', 'expire'])) {
            return redirect()->back()->with('error', 'Statut invalide.');
        }
        
        $data = [
            'statut' => $statut,
            'updated_at' => now(),
        ];
        
        if ($statut === 'envoye') {
            $data['date_envoi'] = now();
        } elseif (in_array($statut, ['accepte', 'refuse'])) {
            $data['date_reponse'] = now();
        }
        
        DB::table('brightshell_devis')->where('id', $id)->update($data);
        
        return redirect()->route('brightshell.devis.show', $id)->with('success', 'Statut mis à jour.');
    }
    
    public function devisPdf($id)
    {
        $devis = DB::table('brightshell_devis')
            ->leftJoin('brightshell_clients', 'brightshell_devis.client_id', '=', 'brightshell_clients.id')
            ->select('brightshell_devis.*', 'brightshell_clients.nom as client_nom', 'brightshell_clients.prenom as client_prenom', 
                     'brightshell_clients.societe as client_societe', 'brightshell_clients.email as client_email',
                     'brightshell_clients.adresse as client_adresse', 'brightshell_clients.code_postal as client_cp',
                     'brightshell_clients.ville as client_ville', 'brightshell_clients.siret as client_siret')
            ->where('brightshell_devis.id', $id)
            ->first();
            
        if (!$devis) abort(404);
        
        $devis->lignes = json_decode($devis->lignes, true) ?? [];
        $entreprise = $this->getEntrepriseInfo();
        $couleurs = $this->getPdfColors();
        
        // Logo et Signature
        $logoPath = public_path('media/brightshell/logo.png');
        $logo = file_exists($logoPath) ? $logoPath : null;
        
        $signaturePath = public_path('media/brightshell/signature.png');
        $signature = file_exists($signaturePath) ? $signaturePath : null;
        
        // Générer le HTML du PDF
        $html = view('brightshell.devis.pdf', compact('devis', 'entreprise', 'couleurs', 'logo', 'signature'))->render();
        
        // Utiliser dompdf si disponible, sinon retourner une vue imprimable
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
            return $pdf->download('devis-' . $devis->numero . '.pdf');
        }
        
        // Fallback: retourner une page HTML imprimable
        return response($html)->header('Content-Type', 'text/html');
    }


    // ==========================================
    // FACTURES
    // ==========================================
    
    public function factures()
    {
        $factures = [];
        try {
            if (\Schema::hasTable('brightshell_factures')) {
                $factures = DB::table('brightshell_factures')
                    ->leftJoin('brightshell_clients', 'brightshell_factures.client_id', '=', 'brightshell_clients.id')
                    ->select('brightshell_factures.*', 'brightshell_clients.nom as client_nom', 'brightshell_clients.societe as client_societe')
                    ->orderBy('brightshell_factures.created_at', 'desc')
                    ->get();
            }
        } catch (\Exception $e) {}
        
        return view('brightshell.factures.index', compact('factures'));
    }
    
    public function factureCreate()
    {
        $clients = DB::table('brightshell_clients')->orderBy('nom')->get();
        return view('brightshell.factures.form', ['facture' => null, 'clients' => $clients]);
    }
    
    public function factureStore(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|integer',
            'date_facture' => 'nullable|date',
            'objet' => 'required|string|max:255',
            'lignes' => 'required|array|min:1',
            'notes' => 'nullable|string',
            'echeance_jours' => 'nullable|integer|min:1',
            'mode_tva' => 'required|in:non_assujetti,ht,ttc',
            'taux_tva' => 'nullable|numeric|min:0|max:100',
        ]);
        
        // Calcul du montant
        $montantHt = 0;
        foreach ($validated['lignes'] as $ligne) {
            if (!empty($ligne['description']) && isset($ligne['quantite'])) {
                $pu = floatval($ligne['prix_unitaire'] ?? 0);
                // Si sous-lignes, le prix unitaire est la somme des sous-lignes
                if (!empty($ligne['sous_lignes'])) {
                    $pu = 0;
                    foreach ($ligne['sous_lignes'] as $sl) {
                        $pu += floatval($sl['quantite'] ?? 0) * floatval($sl['prix_unitaire'] ?? 0);
                    }
                }
                $montantHt += floatval($ligne['quantite']) * $pu;
            }
        }
        
        // Calcul TVA et Total selon le mode
        $modeTva = $validated['mode_tva'];
        $tauxTva = floatval($validated['taux_tva'] ?? 20);
        $montantTva = 0;
        $montantTotal = $montantHt;
        
        if ($modeTva === 'ht') {
            $montantTva = $montantHt * ($tauxTva / 100);
            $montantTotal = $montantHt + $montantTva;
        } elseif ($modeTva === 'ttc') {
            $montantTotal = $montantHt;
            $htFromTtc = $montantHt / (1 + $tauxTva / 100);
            $montantTva = $montantHt - $htFromTtc;
        }

        // Génération numéro
        $lastNum = DB::table('brightshell_factures')->whereYear('created_at', date('Y'))->count() + 1;
        $numero = 'FAC-' . date('Y') . '-' . str_pad($lastNum, 4, '0', STR_PAD_LEFT);
        
        DB::table('brightshell_factures')->insert([
            'numero' => $numero,
            'client_id' => $validated['client_id'],
            'date_facture' => $validated['date_facture'] ?? now()->format('Y-m-d'),
            'objet' => $validated['objet'],
            'lignes' => json_encode($validated['lignes']),
            'montant_total' => round($montantTotal, 2),
            'mode_tva' => $modeTva,
            'taux_tva' => $tauxTva,
            'montant_tva' => round($montantTva, 2),
            'notes' => $validated['notes'] ?? null,
            'echeance_jours' => $validated['echeance_jours'] ?? 30,
            'statut' => 'brouillon',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        return redirect()->route('brightshell.factures')->with('success', 'Facture créée avec succès.');
    }
    
    public function factureShow($id)
    {
        $facture = DB::table('brightshell_factures')
            ->leftJoin('brightshell_clients', 'brightshell_factures.client_id', '=', 'brightshell_clients.id')
            ->select('brightshell_factures.*', 'brightshell_clients.nom as client_nom', 'brightshell_clients.prenom as client_prenom', 
                     'brightshell_clients.societe as client_societe', 'brightshell_clients.email as client_email',
                     'brightshell_clients.adresse as client_adresse', 'brightshell_clients.code_postal as client_cp',
                     'brightshell_clients.ville as client_ville', 'brightshell_clients.siret as client_siret')
            ->where('brightshell_factures.id', $id)
            ->first();
            
        if (!$facture) abort(404);
        
        $facture->lignes = json_decode($facture->lignes, true) ?? [];
        $entreprise = $this->getEntrepriseInfo();
        
        // Récupérer les échéances si paiement échelonné
        $echeances = [];
        if ($facture->paiement_echelonne) {
            $echeances = DB::table('brightshell_echeances')
                ->where('facture_id', $id)
                ->orderBy('numero')
                ->get();
        }

        // Récupérer l'historique des paiements (recettes liées à cette facture)
        $paiements = [];
        if (\Schema::hasTable('brightshell_recettes')) {
            $paiements = DB::table('brightshell_recettes')
                ->where('facture_id', $id)
                ->orderBy('date', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();
        }
        
        return view('brightshell.factures.show', compact('facture', 'entreprise', 'echeances', 'paiements'));
    }

    /**
     * Ajouter un règlement manuel à une facture (rétroactif ou partiel)
     */
    public function factureAddPayment(Request $request, $id)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'montant' => 'required|numeric|min:0.01',
            'mode_paiement' => 'required|string',
            'note' => 'nullable|string'
        ]);

        $facture = DB::table('brightshell_factures')->find($id);
        if (!$facture) abort(404);

        // Enregistrer le paiement dans les recettes
        // S'assurer que la table existe
        try {
            DB::table('brightshell_recettes')->insert([
                'date' => $validated['date'],
                'reference' => $facture->numero, 
                'client_id' => $facture->client_id,
                'client_nom' => DB::table('brightshell_clients')->where('id', $facture->client_id)->value('nom') ?? 'Client', 
                'nature' => $facture->objet . ($validated['note'] ? ' (' . $validated['note'] . ')' : ''),
                'montant' => $validated['montant'],
                'mode_reglement' => $validated['mode_paiement'],
                'facture_id' => $id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Mettre à jour la trésorerie
            if (\Schema::hasTable('brightshell_tresorerie')) {
                $tresorerie = DB::table('brightshell_tresorerie')->first();
                if ($tresorerie) {
                    DB::table('brightshell_tresorerie')->where('id', $tresorerie->id)->update([
                        'solde_courant' => $tresorerie->solde_courant + $validated['montant'],
                        'updated_at' => now()
                    ]);
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erreur ajout paiement manuel: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors de l\'enregistrement du paiement : ' . $e->getMessage());
        }

        // Vérifier si le total payé couvre la facture pour changer le statut
        $totalPaye = DB::table('brightshell_recettes')->where('facture_id', $id)->sum('montant');
        
        if ($totalPaye >= $facture->montant_total && $facture->statut !== 'payee') {
            DB::table('brightshell_factures')->where('id', $id)->update([
                'statut' => 'payee',
                'date_paiement' => $validated['date'], // Date du dernier paiement
                'updated_at' => now(),
            ]);
            return redirect()->route('brightshell.factures.show', $id)
                ->with('success', 'Paiement ajouté. La facture est maintenant soldée.');
        }

        return redirect()->route('brightshell.factures.show', $id)
            ->with('success', 'Paiement ajouté à l\'historique.');
    }
    
    public function factureEdit($id)
    {
        $facture = DB::table('brightshell_factures')->find($id);
        if (!$facture) abort(404);
        
        $facture->lignes = json_decode($facture->lignes, true) ?? [];
        $clients = DB::table('brightshell_clients')->orderBy('nom')->get();
        
        return view('brightshell.factures.form', compact('facture', 'clients'));
    }
    
    public function factureUpdate(Request $request, $id)
    {
        $validated = $request->validate([
            'client_id' => 'required|integer',
            'date_facture' => 'nullable|date',
            'objet' => 'required|string|max:255',
            'lignes' => 'required|array|min:1',
            'notes' => 'nullable|string',
            'echeance_jours' => 'nullable|integer|min:1',
            'mode_tva' => 'required|in:non_assujetti,ht,ttc',
            'taux_tva' => 'nullable|numeric|min:0|max:100',
        ]);
        
        // Calcul du montant
        $montantHt = 0;
        foreach ($validated['lignes'] as $ligne) {
            if (!empty($ligne['description']) && isset($ligne['quantite'])) {
                $pu = floatval($ligne['prix_unitaire'] ?? 0);
                // Si sous-lignes, le prix unitaire est la somme des sous-lignes
                if (!empty($ligne['sous_lignes'])) {
                    $pu = 0;
                    foreach ($ligne['sous_lignes'] as $sl) {
                        $pu += floatval($sl['quantite'] ?? 0) * floatval($sl['prix_unitaire'] ?? 0);
                    }
                }
                $montantHt += floatval($ligne['quantite']) * $pu;
            }
        }
        
        // Calcul TVA et Total selon le mode
        $modeTva = $validated['mode_tva'];
        $tauxTva = floatval($validated['taux_tva'] ?? 20);
        $montantTva = 0;
        $montantTotal = $montantHt;
        
        if ($modeTva === 'ht') {
            $montantTva = $montantHt * ($tauxTva / 100);
            $montantTotal = $montantHt + $montantTva;
        } elseif ($modeTva === 'ttc') {
            $montantTotal = $montantHt;
            $htFromTtc = $montantHt / (1 + $tauxTva / 100);
            $montantTva = $montantHt - $htFromTtc;
            $montantHt = $htFromTtc;
        }
        
        DB::table('brightshell_factures')->where('id', $id)->update([
            'client_id' => $validated['client_id'],
            'date_facture' => $validated['date_facture'] ?? null,
            'objet' => $validated['objet'],
            'lignes' => json_encode($validated['lignes']),
            'montant_total' => round($montantTotal, 2),
            'mode_tva' => $modeTva,
            'taux_tva' => $tauxTva,
            'montant_tva' => round($montantTva, 2),
            'notes' => $validated['notes'] ?? null,
            'echeance_jours' => $validated['echeance_jours'] ?? 30,
            'updated_at' => now(),
        ]);
        
        return redirect()->route('brightshell.factures.show', $id)->with('success', 'Facture mise à jour.');
    }
    
    public function factureMarkPaid(Request $request, $id)
    {
        $modePaiement = $request->input('mode_paiement', 'Virement bancaire');
        $montantPaye = $request->input('montant_paye'); // Peut être nul
        
        $facture = DB::table('brightshell_factures')->find($id);
        if (!$facture) abort(404);

        $montantTotal = $montantPaye ? floatval($montantPaye) : $facture->montant_total;

        // Si la facture est échelonnée, on solde les échéances
        if ($facture->paiement_echelonne) {
            $echeancesNonPayees = DB::table('brightshell_echeances')
                ->where('facture_id', $id)
                ->where('est_payee', false)
                ->orderBy('numero', 'asc')
                ->get();

            if ($echeancesNonPayees->count() > 0) {
                $montantRestant = $echeancesNonPayees->sum('montant');
                $premiereEcheanceAHasher = $echeancesNonPayees->first();

                // On met tout sur la première échéance non payée
                DB::table('brightshell_echeances')->where('id', $premiereEcheanceAHasher->id)->update([
                    'montant' => $montantRestant,
                    'est_payee' => true,
                    'date_paiement' => now()->format('Y-m-d'),
                    'mode_paiement' => $modePaiement,
                    'updated_at' => now(),
                ]);

                // On supprime les échéances suivantes (annulation des paiements à venir)
                DB::table('brightshell_echeances')
                    ->where('facture_id', $id)
                    ->where('id', '>', $premiereEcheanceAHasher->id)
                    ->delete();
            }
        }

        DB::table('brightshell_factures')->where('id', $id)->update([
            'statut' => 'payee',
            'mode_paiement' => $modePaiement,
            'date_paiement' => now(),
            'updated_at' => now(),
        ]);
        
        // Enregistrer dans le livre des recettes
        $facture = DB::table('brightshell_factures')
            ->leftJoin('brightshell_clients', 'brightshell_factures.client_id', '=', 'brightshell_clients.id')
            ->select('brightshell_factures.*', 'brightshell_clients.nom as client_nom', 'brightshell_clients.societe as client_societe')
            ->where('brightshell_factures.id', $id)
            ->first();
            
        // S'assurer que la table existe
        try {
            if ($facture) {
                DB::table('brightshell_recettes')->insert([
                    'date' => now(),
                    'reference' => $facture->numero,
                    'client_id' => $facture->client_id,
                    'client_nom' => $facture->client_societe ?? $facture->client_nom ?? 'Client',
                    'nature' => $facture->objet,
                    'montant' => $montantTotal, // Utiliser le montant réellement payé
                    'mode_reglement' => $modePaiement,
                    'facture_id' => $id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                // Mettre à jour la trésorerie si la table existe
                if (\Schema::hasTable('brightshell_tresorerie')) {
                    $tresorerie = DB::table('brightshell_tresorerie')->first();
                    if ($tresorerie) {
                        DB::table('brightshell_tresorerie')->where('id', $tresorerie->id)->update([
                            'solde_courant' => $tresorerie->solde_courant + $montantTotal,
                            'updated_at' => now()
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erreur enregistrement recette factureMarkPaid: ' . $e->getMessage());
        }
        
        return redirect()->route('brightshell.factures')->with('success', 'Facture marquée comme payée (' . number_format($montantTotal, 2) . ' €).');
    }
    
    /**
     * Créer un avoir à partir d'une facture existante
     */
    public function factureCreateAvoir($id)
    {
        $oldFacture = DB::table('brightshell_factures')->find($id);
        if (!$oldFacture) abort(404);
        
        // On récupère les lignes et on inverse les prix
        $lignes = json_decode($oldFacture->lignes, true);
        foreach ($lignes as &$ligne) {
            $ligne['prix_unitaire'] = -abs($ligne['prix_unitaire']);
        }
        
        // Génération numéro AVOIR
        $lastNum = DB::table('brightshell_factures')
            ->where('numero', 'LIKE', 'AVO-' . date('Y') . '-%')
            ->count() + 1;
        $numero = 'AVO-' . date('Y') . '-' . str_pad($lastNum, 4, '0', STR_PAD_LEFT);
        
        $newId = DB::table('brightshell_factures')->insertGetId([
            'numero' => $numero,
            'client_id' => $oldFacture->client_id,
            'objet' => 'Avoir sur facture ' . $oldFacture->numero,
            'lignes' => json_encode($lignes),
            'montant_total' => -$oldFacture->montant_total,
            'notes' => 'Avoir venant annuler tout ou partie de la facture ' . $oldFacture->numero,
            'echeance_jours' => 0,
            'statut' => 'payee', // Un avoir est considéré comme "soldé" immédiatement
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        return redirect()->route('brightshell.factures.show', $newId)->with('success', 'Avoir créé avec succès.');
    }
    
    public function facturePdf($id)
    {
        $facture = DB::table('brightshell_factures')
            ->leftJoin('brightshell_clients', 'brightshell_factures.client_id', '=', 'brightshell_clients.id')
            ->select('brightshell_factures.*', 'brightshell_clients.nom as client_nom', 'brightshell_clients.prenom as client_prenom', 
                     'brightshell_clients.societe as client_societe', 'brightshell_clients.email as client_email',
                     'brightshell_clients.adresse as client_adresse', 'brightshell_clients.code_postal as client_cp',
                     'brightshell_clients.ville as client_ville', 'brightshell_clients.siret as client_siret')
            ->where('brightshell_factures.id', $id)
            ->first();
            
        if (!$facture) abort(404);
        
        $facture->lignes = json_decode($facture->lignes, true) ?? [];
        $entreprise = $this->getEntrepriseInfo();
        $couleurs = $this->getPdfColors();
        
        // Logo et Signature
        $logoPath = public_path('media/brightshell/logo.png');
        $logo = file_exists($logoPath) ? $logoPath : null;
        
        $signaturePath = public_path('media/brightshell/signature.png');
        $signature = file_exists($signaturePath) ? $signaturePath : null;
        
        // Récupérer les échéances si paiement échelonné
        $echeances = [];
        if ($facture->paiement_echelonne) {
            $echeances = DB::table('brightshell_echeances')
                ->where('facture_id', $id)
                ->orderBy('numero', 'asc')
                ->get();
        }
        
        // Générer le HTML du PDF
        $html = view('brightshell.factures.pdf', compact('facture', 'entreprise', 'couleurs', 'logo', 'signature', 'echeances'))->render();
        
        // Utiliser dompdf si disponible, sinon retourner une vue imprimable
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
            return $pdf->download('facture-' . $facture->numero . '.pdf');
        }
        
        // Fallback: retourner une page HTML imprimable
        return response($html)->header('Content-Type', 'text/html');
    }
    
    /**
     * Créer un plan de paiement échelonné pour une facture
     */
    public function factureCreateEcheances(Request $request, $id)
    {
        $facture = DB::table('brightshell_factures')->find($id);
        if (!$facture) abort(404);
        
        $validated = $request->validate([
            'nombre_echeances' => 'required|integer|min:2|max:12',
            'jour_echeance' => 'required|integer|min:1|max:28',
            'premiere_echeance' => 'required|date|after:today',
            'mode_paiement' => 'required|string|in:Virement bancaire,Chèque,Carte bleue',
            'montant_mensuel' => 'nullable|numeric|min:1',
        ]);
        
        $nombreEcheances = (int) $validated['nombre_echeances'];
        $jourEcheance = (int) $validated['jour_echeance'];
        $premiereEcheance = Carbon::parse($validated['premiere_echeance']);
        $montantTotal = $facture->montant_total;
        
        $montantMensuelFixe = $request->filled('montant_mensuel') ? (float)$validated['montant_mensuel'] : null;
        
        if ($montantMensuelFixe) {
            $montantParEcheance = $montantMensuelFixe;
            $resteArrondi = round($montantTotal - ($montantParEcheance * ($nombreEcheances - 1)), 2);
            // Si le reste est négatif ou trop élevé, on revient au calcul standard
            if ($resteArrondi <= 0 || $resteArrondi > $montantParEcheance * 2) {
                $montantParEcheance = round($montantTotal / $nombreEcheances, 2);
                $resteArrondi = round($montantTotal - ($montantParEcheance * $nombreEcheances), 2);
                $isStandard = true;
            } else {
                $isStandard = false;
            }
        } else {
            $montantParEcheance = round($montantTotal / $nombreEcheances, 2);
            $resteArrondi = round($montantTotal - ($montantParEcheance * $nombreEcheances), 2);
            $isStandard = true;
        }
        
        // Supprimer les anciennes échéances
        DB::table('brightshell_echeances')->where('facture_id', $id)->delete();
        
        // Créer les nouvelles échéances
        for ($i = 1; $i <= $nombreEcheances; $i++) {
            $dateEcheance = $premiereEcheance->copy();
            if ($i > 1) {
                $dateEcheance->addMonths($i - 1);
            }
            // S'assurer que le jour correspond
            $dateEcheance->day = min($jourEcheance, $dateEcheance->daysInMonth);
            
            // Calcul du montant de cette échéance
            if ($isStandard) {
                $montant = $montantParEcheance;
                if ($i === $nombreEcheances) {
                    $montant += $resteArrondi;
                }
            } else {
                if ($i === $nombreEcheances) {
                    $montant = $resteArrondi;
                } else {
                    $montant = $montantParEcheance;
                }
            }
            
            DB::table('brightshell_echeances')->insert([
                'facture_id' => $id,
                'numero' => $i,
                'date_echeance' => $dateEcheance->format('Y-m-d'),
                'montant' => $montant,
                'est_payee' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        // Mettre à jour la facture
        DB::table('brightshell_factures')->where('id', $id)->update([
            'paiement_echelonne' => true,
            'nombre_echeances' => $nombreEcheances,
            'mode_paiement' => $validated['mode_paiement'],
            'statut' => 'envoyee', // La facture passe en "envoyée" quand on crée un plan
            'updated_at' => now(),
        ]);
        
        return redirect()->route('brightshell.factures.show', $id)
            ->with('success', "Plan de paiement en {$nombreEcheances}x créé avec succès.");
    }

    public function factureDeleteEcheances($id)
    {
        $facture = DB::table('brightshell_factures')->find($id);
        if (!$facture) abort(404);
        
        DB::table('brightshell_echeances')->where('facture_id', $id)->delete();
        
        DB::table('brightshell_factures')->where('id', $id)->update([
            'paiement_echelonne' => false,
            'nombre_echeances' => null,
            'updated_at' => now(),
        ]);
        
        return redirect()->route('brightshell.factures.show', $id)->with('success', 'Plan de paiement supprimé.');
    }
    
    /**
     * Marquer une échéance comme payée
     */
    public function echeanceMarkPaid(Request $request, $id, $echeanceId)
    {
        $echeance = DB::table('brightshell_echeances')
            ->where('id', $echeanceId)
            ->where('facture_id', $id)
            ->first();
        
        if (!$echeance) abort(404);
        
        $modePaiement = $request->input('mode_paiement', 'virement');
        $montantPaye = $request->input('montant_paye');
        $datePaiement = $request->input('date_paiement') ? $request->input('date_paiement') : now()->format('Y-m-d');
        
        // Si un montant spécifique est payé (différent du montant prévu)
        $montantReel = $montantPaye ? floatval($montantPaye) : $echeance->montant;
        
        DB::table('brightshell_echeances')->where('id', $echeanceId)->update([
            'est_payee' => true,
            'montant' => $montantReel, // Mettre à jour avec le montant réellement payé
            'date_paiement' => $datePaiement,
            'mode_paiement' => $modePaiement,
            'updated_at' => now(),
        ]);
        
        // Enregistrer la recette pour cette échéance spécifique
        $facture = DB::table('brightshell_factures')
            ->leftJoin('brightshell_clients', 'brightshell_factures.client_id', '=', 'brightshell_clients.id')
            ->select('brightshell_factures.*', 'brightshell_clients.nom as client_nom', 'brightshell_clients.societe as client_societe')
            ->where('brightshell_factures.id', $id)
            ->first();
            
        // Vérification et enregistrement explicite
        if ($facture) {
            // S'assurer que la table existe
            try {
                DB::table('brightshell_recettes')->insert([
                    'date' => $datePaiement, // Utiliser la date du paiement de l'échéance
                    'reference' => $facture->numero . ' (' . $echeance->numero . ')',
                    'client_id' => $facture->client_id,
                    'client_nom' => $facture->client_societe ?? $facture->client_nom ?? 'Client',
                    'nature' => $facture->objet . ' (échéance ' . $echeance->numero . ')',
                    'montant' => $montantReel,
                    'mode_reglement' => $modePaiement,
                    'facture_id' => $id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                // Mettre à jour la trésorerie
                if (\Schema::hasTable('brightshell_tresorerie')) {
                    $tresorerie = DB::table('brightshell_tresorerie')->first();
                    if ($tresorerie) {
                        DB::table('brightshell_tresorerie')->where('id', $tresorerie->id)->update([
                            'solde_courant' => $tresorerie->solde_courant + $montantReel,
                            'updated_at' => now()
                        ]);
                    }
                }
            } catch (\Exception $e) {
                // Log l'erreur silencieusement ou gérer autrement si besoin
                \Illuminate\Support\Facades\Log::error('Erreur enregistrement recette échéance: ' . $e->getMessage());
            }
        }
        
        // Vérifier si toutes les échéances sont payées
        $echeancesRestantes = DB::table('brightshell_echeances')
            ->where('facture_id', $id)
            ->where('est_payee', false)
            ->count();
        
        if ($echeancesRestantes === 0) {
            // Marquer la facture comme payée
            DB::table('brightshell_factures')->where('id', $id)->update([
                'statut' => 'payee',
                'date_paiement' => now(),
                'updated_at' => now(),
            ]);
            
            return redirect()->route('brightshell.factures.show', $id)
                ->with('success', 'Dernière échéance payée ! Facture complètement réglée.');
        }
        
        return redirect()->route('brightshell.factures.show', $id)
            ->with('success', 'Échéance marquée comme payée (' . number_format($montantReel, 2) . ' €).');
    }
    
    /**
     * Récupérer les prochaines échéances à recevoir (pour stats)
     */
    private function getProchainsPaiements(): array
    {
        $echeances = [];
        
        try {
            if (\Schema::hasTable('brightshell_echeances')) {
                $echeances = DB::table('brightshell_echeances')
                    ->leftJoin('brightshell_factures', 'brightshell_echeances.facture_id', '=', 'brightshell_factures.id')
                    ->leftJoin('brightshell_clients', 'brightshell_factures.client_id', '=', 'brightshell_clients.id')
                    ->select(
                        'brightshell_echeances.*',
                        'brightshell_factures.numero as facture_numero',
                        'brightshell_factures.objet as facture_objet',
                        'brightshell_clients.nom as client_nom',
                        'brightshell_clients.societe as client_societe'
                    )
                    ->where('brightshell_echeances.est_payee', false)
                    ->orderBy('brightshell_echeances.date_echeance', 'asc')
                    ->get();
            }
        } catch (\Exception $e) {}
        
        return $echeances->toArray();
    }
    
    /**
     * Calcule le montant total des paiements attendus par mois
     */
    private function getPaiementsParMois(): array
    {
        $paiementsParMois = [];
        
        try {
            if (\Schema::hasTable('brightshell_echeances')) {
                $echeances = DB::table('brightshell_echeances')
                    ->where('est_payee', false)
                    ->where('date_echeance', '>=', now()->startOfMonth())
                    ->where('date_echeance', '<=', now()->addMonths(6)->endOfMonth())
                    ->selectRaw('DATE_FORMAT(date_echeance, "%Y-%m") as mois, SUM(montant) as total')
                    ->groupBy('mois')
                    ->orderBy('mois')
                    ->get();
                
                foreach ($echeances as $e) {
                    $paiementsParMois[$e->mois] = $e->total;
                }
            }
        } catch (\Exception $e) {}
        
        return $paiementsParMois;
    }
    // ==========================================
    // PROJETS
    // ==========================================
    
    public function projets()
    {
        $projets = [];
        try {
            if (\Schema::hasTable('brightshell_projets')) {
                $projets = DB::table('brightshell_projets')
                    ->leftJoin('brightshell_clients', 'brightshell_projets.client_id', '=', 'brightshell_clients.id')
                    ->select('brightshell_projets.*', 'brightshell_clients.nom as client_nom', 'brightshell_clients.societe as client_societe')
                    ->orderBy('brightshell_projets.created_at', 'desc')
                    ->get();
            }
        } catch (\Exception $e) {}
        
        return view('brightshell.projets.index', compact('projets'));
    }
    
    public function projetCreate()
    {
        $clients = DB::table('brightshell_clients')->orderBy('nom')->get();
        return view('brightshell.projets.form', ['projet' => null, 'clients' => $clients]);
    }
    
    public function projetStore(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'client_id' => 'nullable|integer',
            'description' => 'nullable|string',
            'date_debut' => 'nullable|date',
            'date_fin_prevue' => 'nullable|date',
            'budget' => 'nullable|numeric|min:0',
            'statut' => 'required|in:en_attente,en_cours,termine,annule',
        ]);
        
        $validated['created_at'] = now();
        $validated['updated_at'] = now();
        
        DB::table('brightshell_projets')->insert($validated);
        
        return redirect()->route('brightshell.projets')->with('success', 'Projet créé avec succès.');
    }

    // ==========================================
    // COMPTABILITÉ
    // ==========================================
    
    public function comptabilite()
    {
        $recettes = [];
        $achats = [];
        $totalRecettes = 0;
        $totalAchats = 0;
        
        try {
            if (\Schema::hasTable('brightshell_recettes')) {
                $recettes = DB::table('brightshell_recettes')
                    ->whereYear('date', date('Y'))
                    ->orderBy('date', 'desc')
                    ->get();
                $totalRecettes = $recettes->sum('montant');
            }
            
            if (\Schema::hasTable('brightshell_achats')) {
                $achats = DB::table('brightshell_achats')
                    ->whereYear('date', date('Y'))
                    ->orderBy('date', 'desc')
                    ->get();
                $totalAchats = $achats->sum('montant');
            }
        } catch (\Exception $e) {}
        
        $stats = [
            'total_recettes' => $totalRecettes,
            'total_achats' => $totalAchats,
            'benefice' => $totalRecettes - $totalAchats,
            'cotisations_estimees' => $totalRecettes * 0.212,
            'seuil_tva' => 36800,
            'seuil_micro' => 77700,
        ];
        
        return view('brightshell.comptabilite.index', compact('recettes', 'achats', 'stats'));
    }

    // ==========================================
    // RESSOURCES / TRÉSORERIE
    // ==========================================

    public function ressources()
    {
        $year = request('annee', date('Y'));
        $tresorerie = $this->ressourcesTresorerieRow();
        $recettes = [];
        $achats = [];
        $mouvements = [];
        $reserves = [];
        $abonnements = [];

        try {
            if (\Schema::hasTable('brightshell_recettes')) {
                $recettes = DB::table('brightshell_recettes')
                    ->whereYear('date', $year)
                    ->orderBy('date', 'desc')
                    ->get();
            }
            if (\Schema::hasTable('brightshell_achats')) {
                $achats = DB::table('brightshell_achats')
                    ->whereYear('date', $year)
                    ->orderBy('date', 'desc')
                    ->get();
            }
            if (\Schema::hasTable('brightshell_mouvements')) {
                $mouvements = DB::table('brightshell_mouvements')
                    ->whereYear('date', $year)
                    ->orderBy('date', 'desc')
                    ->get();
            }
            if (\Schema::hasTable('brightshell_reserves')) {
                $reserves = DB::table('brightshell_reserves')
                    ->orderBy('date_prevue')
                    ->get();
            }
            if (\Schema::hasTable('brightshell_abonnements')) {
                $abonnements = DB::table('brightshell_abonnements')
                    ->orderBy('prochaine_echeance')
                    ->get();
            }
        } catch (\Exception $e) {}

        $recettes = collect($recettes);
        $achats = collect($achats);
        $mouvements = collect($mouvements);
        $reserves = collect($reserves);
        $abonnements = collect($abonnements);

        $totalEntrees = $recettes->sum('montant') + $mouvements->where('type', 'entree')->sum('montant');
        $totalSorties = $achats->sum('montant') + $mouvements->where('type', 'sortie')->sum('montant');
        $reservesNonPayees = $reserves->where('payee', false);
        $totalReserves = $reservesNonPayees->sum('montant');

        return view('brightshell.ressources.index', compact(
            'tresorerie', 'recettes', 'achats', 'mouvements', 'reserves', 'abonnements',
            'year', 'totalEntrees', 'totalSorties', 'totalReserves'
        ));
    }

    private function ressourcesTresorerieRow(): ?object
    {
        if (!\Schema::hasTable('brightshell_tresorerie')) {
            return null;
        }
        $row = DB::table('brightshell_tresorerie')->first();
        if (!$row) {
            DB::table('brightshell_tresorerie')->insert([
                'solde_courant' => 0,
                'date_maj' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $row = DB::table('brightshell_tresorerie')->first();
        }
        return $row;
    }

    public function ressourcesTresorerieUpdate(Request $request)
    {
        $v = $request->validate(['solde_courant' => 'required|numeric']);
        $this->ressourcesTresorerieRow();
        DB::table('brightshell_tresorerie')->where('id', 1)->update([
            'solde_courant' => round($v['solde_courant'], 2),
            'date_maj' => now(),
            'updated_at' => now(),
        ]);
        return redirect()->route('brightshell.ressources')->with('success', 'Monnaie mise à jour.');
    }

    public function ressourcesReserveStore(Request $request)
    {
        $v = $request->validate([
            'libelle' => 'required|string|max:255',
            'montant' => 'required|numeric|min:0',
            'date_prevue' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);
        DB::table('brightshell_reserves')->insert([
            'libelle' => $v['libelle'],
            'montant' => round($v['montant'], 2),
            'date_prevue' => $v['date_prevue'] ?? null,
            'notes' => $v['notes'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return redirect()->route('brightshell.ressources')->with('success', 'Réserve ajoutée.');
    }

    public function ressourcesReserveUpdate(Request $request, $id)
    {
        $v = $request->validate([
            'libelle' => 'required|string|max:255',
            'montant' => 'required|numeric|min:0',
            'date_prevue' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);
        DB::table('brightshell_reserves')->where('id', $id)->update([
            'libelle' => $v['libelle'],
            'montant' => round($v['montant'], 2),
            'date_prevue' => $v['date_prevue'] ?? null,
            'notes' => $v['notes'] ?? null,
            'updated_at' => now(),
        ]);
        return redirect()->route('brightshell.ressources')->with('success', 'Réserve mise à jour.');
    }

    public function ressourcesReserveDelete($id)
    {
        DB::table('brightshell_reserves')->where('id', $id)->delete();
        return redirect()->route('brightshell.ressources')->with('success', 'Réserve supprimée.');
    }

    public function ressourcesReserveTogglePaid($id)
    {
        $r = DB::table('brightshell_reserves')->find($id);
        if (!$r) {
            return redirect()->route('brightshell.ressources')->with('error', 'Réserve introuvable.');
        }
        $payee = !$r->payee;
        DB::table('brightshell_reserves')->where('id', $id)->update([
            'payee' => $payee,
            'date_paiement' => $payee ? now() : null,
            'updated_at' => now(),
        ]);
        return redirect()->route('brightshell.ressources')->with('success', $payee ? 'Réserve marquée comme payée.' : 'Réserve marquée non payée.');
    }

    public function ressourcesMouvementStore(Request $request)
    {
        $v = $request->validate([
            'type' => 'required|in:entree,sortie',
            'libelle' => 'required|string|max:255',
            'montant' => 'required|numeric|min:0',
            'date' => 'required|date',
            'categorie' => 'nullable|string|max:64',
            'notes' => 'nullable|string',
        ]);
        
        try {
            DB::table('brightshell_mouvements')->insert([
                'type' => $v['type'],
                'libelle' => $v['libelle'],
                'montant' => round($v['montant'], 2),
                'date' => $v['date'],
                'categorie' => $v['categorie'] ?? null,
                'notes' => $v['notes'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Mettre à jour la trésorerie
            if (\Schema::hasTable('brightshell_tresorerie')) {
                $tresorerie = DB::table('brightshell_tresorerie')->first();
                if ($tresorerie) {
                    $nouveauSolde = $tresorerie->solde_courant;
                    if ($v['type'] === 'entree') {
                        $nouveauSolde += $v['montant'];
                    } else {
                        $nouveauSolde -= $v['montant'];
                    }
                    
                    DB::table('brightshell_tresorerie')->where('id', $tresorerie->id)->update([
                        'solde_courant' => $nouveauSolde,
                        'updated_at' => now()
                    ]);
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erreur enregistrement mouvement: ' . $e->getMessage());
            return back()->with('error', 'Erreur : ' . $e->getMessage());
        }
        
        return redirect()->route('brightshell.ressources')->with('success', 'Mouvement enregistré.');
    }

    public function ressourcesMouvementDelete($id)
    {
        try {
            // Récupérer le mouvement avant suppression pour inverser son effet sur la trésorerie
            $mouvement = DB::table('brightshell_mouvements')->find($id);
            
            if ($mouvement) {
                // Inverser l'effet sur la trésorerie
                if (\Schema::hasTable('brightshell_tresorerie')) {
                    $tresorerie = DB::table('brightshell_tresorerie')->first();
                    if ($tresorerie) {
                        $nouveauSolde = $tresorerie->solde_courant;
                        // Inverser : si c'était une entrée, on soustrait ; si c'était une sortie, on ajoute
                        if ($mouvement->type === 'entree') {
                            $nouveauSolde -= $mouvement->montant;
                        } else {
                            $nouveauSolde += $mouvement->montant;
                        }
                        
                        DB::table('brightshell_tresorerie')->where('id', $tresorerie->id)->update([
                            'solde_courant' => $nouveauSolde,
                            'updated_at' => now()
                        ]);
                    }
                }
                
                // Supprimer le mouvement
                DB::table('brightshell_mouvements')->where('id', $id)->delete();
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erreur suppression mouvement: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors de la suppression : ' . $e->getMessage());
        }
        
        return redirect()->route('brightshell.ressources')->with('success', 'Mouvement supprimé.');
    }

    public function ressourcesAbonnementStore(Request $request)
    {
        $v = $request->validate([
            'type' => 'required|in:entree,sortie',
            'libelle' => 'required|string|max:255',
            'beneficiaire' => 'nullable|string|max:255',
            'montant' => 'required|numeric|min:0',
            'frequence' => 'required|in:mensuel,semaines_strictes',
            'intervalle_semaines' => 'nullable|integer|min:1|max:52',
            'date_debut' => 'required|date',
            'date_fin' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);
        $prochaine = $this->prochaineEcheanceAbonnement(
            $v['date_debut'],
            $v['frequence'],
            $v['intervalle_semaines'] ?? null,
            $v['date_fin'] ?? null
        );
        DB::table('brightshell_abonnements')->insert([
            'type' => $v['type'],
            'libelle' => $v['libelle'],
            'beneficiaire' => $v['beneficiaire'] ?? null,
            'montant' => round($v['montant'], 2),
            'frequence' => $v['frequence'],
            'intervalle_semaines' => $v['frequence'] === 'semaines_strictes' ? ($v['intervalle_semaines'] ?? 4) : null,
            'date_debut' => $v['date_debut'],
            'date_fin' => $v['date_fin'] ?? null,
            'prochaine_echeance' => $prochaine,
            'actif' => true,
            'notes' => $v['notes'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return redirect()->route('brightshell.ressources')->with('success', 'Abonnement ajouté.');
    }

    public function ressourcesAbonnementUpdate(Request $request, $id)
    {
        $v = $request->validate([
            'type' => 'required|in:entree,sortie',
            'libelle' => 'required|string|max:255',
            'beneficiaire' => 'nullable|string|max:255',
            'montant' => 'required|numeric|min:0',
            'frequence' => 'required|in:mensuel,semaines_strictes',
            'intervalle_semaines' => 'nullable|integer|min:1|max:52',
            'date_debut' => 'required|date',
            'date_fin' => 'nullable|date',
            'prochaine_echeance' => 'nullable|date',
            'actif' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);
        $prochaine = $v['prochaine_echeance'] ?? $this->prochaineEcheanceAbonnement(
            $v['date_debut'],
            $v['frequence'],
            $v['frequence'] === 'semaines_strictes' ? ($v['intervalle_semaines'] ?? null) : null,
            $v['date_fin'] ?? null
        );
        DB::table('brightshell_abonnements')->where('id', $id)->update([
            'type' => $v['type'],
            'libelle' => $v['libelle'],
            'beneficiaire' => $v['beneficiaire'] ?? null,
            'montant' => round($v['montant'], 2),
            'frequence' => $v['frequence'],
            'intervalle_semaines' => $v['frequence'] === 'semaines_strictes' ? ($v['intervalle_semaines'] ?? null) : null,
            'date_debut' => $v['date_debut'],
            'date_fin' => $v['date_fin'] ?? null,
            'prochaine_echeance' => $prochaine,
            'actif' => $request->boolean('actif'),
            'notes' => $v['notes'] ?? null,
            'updated_at' => now(),
        ]);
        return redirect()->route('brightshell.ressources')->with('success', 'Abonnement mis à jour.');
    }

    public function ressourcesAbonnementDelete($id)
    {
        DB::table('brightshell_abonnements')->where('id', $id)->delete();
        return redirect()->route('brightshell.ressources')->with('success', 'Abonnement supprimé.');
    }

    private function prochaineEcheanceAbonnement(?string $dateDebut, string $frequence, ?int $intervalleSemaines, ?string $dateFin): ?string
    {
        if (!$dateDebut) {
            return null;
        }
        $d = Carbon::parse($dateDebut);
        $now = Carbon::today();
        if ($d->isFuture()) {
            return $d->format('Y-m-d');
        }
        if ($frequence === 'mensuel') {
            $next = $d->copy();
            while ($next->lte($now)) {
                $next->addMonth();
            }
            if ($dateFin && $next->gt(Carbon::parse($dateFin))) {
                return null;
            }
            return $next->format('Y-m-d');
        }
        $weeks = (int) ($intervalleSemaines ?: 4);
        $next = $d->copy();
        while ($next->lte($now)) {
            $next->addWeeks($weeks);
        }
        if ($dateFin && $next->gt(Carbon::parse($dateFin))) {
            return null;
        }
        return $next->format('Y-m-d');
    }

    // ==========================================
    // MAILING
    // ==========================================
    
    public function mailing(BrightShellImapService $imap)
    {
        $sent = [];
        try {
            if (\Schema::hasTable('brightshell_mail_logs')) {
                $sent = DB::table('brightshell_mail_logs')
                    ->orderBy('created_at', 'desc')
                    ->limit(50)
                    ->get();
            }
        } catch (\Exception $e) {}

        $received = collect();
        $imapConfigured = $imap->isConfigured();
        if ($imapConfigured) {
            $received = $imap->listInbox(50);
        }

        return view('brightshell.mailing.index', compact('sent', 'received', 'imapConfigured'));
    }

    public function mailShowReceived(int $id, BrightShellImapService $imap)
    {
        $mail = $imap->isConfigured() ? $imap->getMessage($id) : null;
        return view('brightshell.mailing.show-received', compact('mail'));
    }
    
    public function mailCompose()
    {
        $clients = [];
        try {
            if (\Schema::hasTable('brightshell_clients')) {
                $clients = DB::table('brightshell_clients')
                    ->whereNotNull('email')
                    ->orderBy('nom')
                    ->get();
            }
        } catch (\Exception $e) {}
        
        return view('brightshell.mailing.compose', compact('clients'));
    }
    
    public function mailSend(Request $request)
    {
        $validated = $request->validate([
            'to' => 'required|email',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
        ]);
        
        try {
            // Envoi via le mailer brightshell configuré dans config/mail.php
            Mail::mailer('brightshell')->raw($validated['body'], function ($message) use ($validated) {
                $message->to($validated['to'])
                    ->subject($validated['subject'])
                    ->from(config('mail.mailers.brightshell.username'), 'BrightShell');
            });
            
            // Log (colonnes : to, subject, body, status — cf. migration brightshell_mail_logs)
            if (\Schema::hasTable('brightshell_mail_logs')) {
                DB::table('brightshell_mail_logs')->insert([
                    'to' => $validated['to'],
                    'subject' => $validated['subject'],
                    'body' => $validated['body'],
                    'status' => 'sent',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            
            return redirect()->route('brightshell.mailing')->with('success', 'Email envoyé avec succès.');
            
        } catch (\Exception $e) {
            return redirect()->route('brightshell.mailing.compose')
                ->withInput()
                ->with('error', 'Erreur lors de l\'envoi : ' . $e->getMessage());
        }
    }

    // ==========================================
    // PARAMÈTRES
    // ==========================================
    
    public function settings()
    {
        $entreprise = $this->getEntrepriseInfo();
        $logoPath = public_path('media/brightshell/logo.png');
        $logo = file_exists($logoPath) ? asset('media/brightshell/logo.png') : null;
        
        $signaturePath = public_path('media/brightshell/signature.png');
        $signature = file_exists($signaturePath) ? asset('media/brightshell/signature.png') : null;
        
        $faviconPath = public_path('media/brightshell/favicon.png');
        $favicon = file_exists($faviconPath) ? asset('media/brightshell/favicon.png') : null;
        
        $couleurs = $this->getPdfColors();
        
        return view('brightshell.settings', compact('entreprise', 'logo', 'signature', 'favicon', 'couleurs'));
    }
    
    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:png,jpg,jpeg,svg,webp|max:2048',
        ]);
        
        $dir = public_path('media/brightshell');
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        
        $request->file('logo')->move($dir, 'logo.png');
        
        return redirect()->route('brightshell.settings')->with('success', 'Logo mis à jour.');
    }

    public function uploadFavicon(Request $request)
    {
        $request->validate([
            'favicon' => 'required|image|mimes:png,ico|max:1024',
        ]);
        
        $dir = public_path('media/brightshell');
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        
        $request->file('favicon')->move($dir, 'favicon.png');
        
        return redirect()->route('brightshell.settings')->with('success', 'Favicon mis à jour.');
    }
    
    public function uploadSignature(Request $request)
    {
        $request->validate([
            'signature' => 'required|image|mimes:png,jpg,jpeg,svg,webp|max:2048',
        ]);
        
        $dir = public_path('media/brightshell');
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        
        $request->file('signature')->move($dir, 'signature.png');
        
        return redirect()->route('brightshell.settings')->with('success', 'Signature mise à jour.');
    }
    
    public function updatePdfColors(Request $request)
    {
        $colors = [
            'primary' => $request->input('pdf_color_primary', '#5bbce4'),
            'secondary' => $request->input('pdf_color_secondary', '#0a0e1a'),
            'text' => $request->input('pdf_color_text', '#1a1a1a'),
            'muted' => $request->input('pdf_color_muted', '#6b7280'),
            'background' => $request->input('pdf_color_background', '#f9fafb'),
            'border' => $request->input('pdf_color_border', '#e5e7eb'),
            'success' => $request->input('pdf_color_success', '#10b981'),
        ];
        
        foreach ($colors as $key => $value) {
            DB::table('brightshell_settings')->updateOrInsert(
                ['key' => 'pdf_color_' . $key],
                ['value' => $value, 'updated_at' => now()]
            );
        }
        
        return redirect()->route('brightshell.settings')->with('success', 'Couleurs PDF mises à jour.');
    }

    // ==========================================
    // TÂCHES
    // ==========================================
    
    public function taches()
    {
        $taches = [];
        try {
            if (\Schema::hasTable('brightshell_taches')) {
                $taches = DB::table('brightshell_taches')
                    ->orderBy('completed')
                    ->orderBy('priorite', 'desc')
                    ->orderBy('created_at', 'desc')
                    ->get();
            }
        } catch (\Exception $e) {}
        
        return view('brightshell.taches.index', compact('taches'));
    }
    
    public function tacheStore(Request $request)
    {
        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priorite' => 'nullable|in:basse,normale,haute,urgente',
            'echeance' => 'nullable|date',
        ]);
        
        DB::table('brightshell_taches')->insert([
            'titre' => $validated['titre'],
            'description' => $validated['description'] ?? null,
            'priorite' => $validated['priorite'] ?? 'normale',
            'echeance' => $validated['echeance'] ?? null,
            'completed' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        return redirect()->route('brightshell.taches')->with('success', 'Tâche ajoutée.');
    }
    
    public function tacheToggle($id)
    {
        $tache = DB::table('brightshell_taches')->find($id);
        if ($tache) {
            DB::table('brightshell_taches')->where('id', $id)->update([
                'completed' => !$tache->completed,
                'updated_at' => now(),
            ]);
        }
        return redirect()->route('brightshell.taches');
    }
    
    public function tacheDelete($id)
    {
        DB::table('brightshell_taches')->where('id', $id)->delete();
        return redirect()->route('brightshell.taches')->with('success', 'Tâche supprimée.');
    }

    // ==========================================
    // NOTES
    // ==========================================
    
    public function notes()
    {
        $notes = [];
        try {
            if (\Schema::hasTable('brightshell_notes')) {
                $notes = DB::table('brightshell_notes')
                    ->orderBy('updated_at', 'desc')
                    ->get();
            }
        } catch (\Exception $e) {}
        
        return view('brightshell.notes.index', compact('notes'));
    }
    
    public function noteCreate()
    {
        return view('brightshell.notes.form', ['note' => null]);
    }
    
    public function noteStore(Request $request)
    {
        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'contenu' => 'nullable|string',
            'couleur' => 'nullable|string|max:20',
        ]);
        
        DB::table('brightshell_notes')->insert([
            'titre' => $validated['titre'],
            'contenu' => $validated['contenu'] ?? '',
            'couleur' => $validated['couleur'] ?? 'default',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        return redirect()->route('brightshell.notes')->with('success', 'Note créée.');
    }
    
    public function noteShow($id)
    {
        $note = DB::table('brightshell_notes')->find($id);
        if (!$note) abort(404);
        return view('brightshell.notes.form', compact('note'));
    }
    
    public function noteUpdate(Request $request, $id)
    {
        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'contenu' => 'nullable|string',
            'couleur' => 'nullable|string|max:20',
        ]);
        
        DB::table('brightshell_notes')->where('id', $id)->update([
            'titre' => $validated['titre'],
            'contenu' => $validated['contenu'] ?? '',
            'couleur' => $validated['couleur'] ?? 'default',
            'updated_at' => now(),
        ]);
        
        return redirect()->route('brightshell.notes')->with('success', 'Note mise à jour.');
    }
    
    public function noteDelete($id)
    {
        DB::table('brightshell_notes')->where('id', $id)->delete();
        return redirect()->route('brightshell.notes')->with('success', 'Note supprimée.');
    }

    // ==========================================
    // AGENDA
    // ==========================================
    
    public function agenda()
    {
        $date = request('date', now()->format('Y-m-d'));
        $date = \Carbon\Carbon::parse($date)->format('Y-m-d');
        $events = [];
        try {
            if (\Schema::hasTable('brightshell_events')) {
                $events = DB::table('brightshell_events')
                    ->where('date', $date)
                    ->orderByRaw('heure IS NULL, heure ASC')
                    ->get();
            }
        } catch (\Exception $e) {}

        $events = collect($events);

        return view('brightshell.agenda.index', compact('events', 'date'));
    }

    public function eventStore(Request $request)
    {
        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'date' => 'required|date',
            'heure' => 'nullable|string|max:5',
            'heure_fin' => 'nullable|string|max:5',
            'description' => 'nullable|string',
            'type' => 'nullable|in:rdv,deadline,rappel,autre',
        ]);

        DB::table('brightshell_events')->insert([
            'titre' => $validated['titre'],
            'date' => $validated['date'],
            'heure' => $validated['heure'] ?? null,
            'heure_fin' => $validated['heure_fin'] ?? null,
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'] ?? 'autre',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $redirect = redirect()->route('brightshell.agenda', ['date' => $validated['date']]);
        return $redirect->with('success', 'Événement ajouté.');
    }
    
    public function eventDelete($id)
    {
        $event = DB::table('brightshell_events')->find($id);
        $date = $event ? $event->date : now()->format('Y-m-d');
        DB::table('brightshell_events')->where('id', $id)->delete();
        return redirect()->route('brightshell.agenda', ['date' => $date])->with('success', 'Événement supprimé.');
    }

    // ==========================================
    // DOCUMENTS
    // ==========================================
    // DOCUMENTS LÉGAUX (GÉNÉRATEUR)
    // ==========================================
    
    public function legals()
    {
        $legals = [];
        try {
            if (\Schema::hasTable('brightshell_legals')) {
                $legals = DB::table('brightshell_legals')
                    ->leftJoin('brightshell_clients', 'brightshell_legals.client_id', '=', 'brightshell_clients.id')
                    ->select('brightshell_legals.*', 'brightshell_clients.nom as client_nom', 'brightshell_clients.prenom as client_prenom', 'brightshell_clients.societe as client_societe')
                    ->orderBy('created_at', 'desc')
                    ->get();
            }
        } catch (\Exception $e) {}
        
        return view('brightshell.legals.index', compact('legals'));
    }
    
    public function legalCreate()
    {
        $clients = [];
        try {
            if (\Schema::hasTable('brightshell_clients')) {
                $clients = DB::table('brightshell_clients')->orderBy('nom')->get();
            }
        } catch (\Exception $e) {}
        
        return view('brightshell.legals.create', compact('clients'));
    }
    
    public function legalStore(Request $request)
    {
        // Emergency fix for missing columns if migration couldn't run
        if (!\Schema::hasColumn('brightshell_legals', 'destinataire_prenom')) {
            \Schema::table('brightshell_legals', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->string('destinataire_prenom')->nullable()->after('destinataire_nom');
                $table->string('destinataire_titre')->nullable()->after('destinataire_prenom');
                $table->text('pieces_jointes')->nullable()->after('contenu');
            });
        }

        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'type' => 'required|string|in:attestation,courrier,autre',
            'client_id' => 'nullable|exists:brightshell_clients,id',
            'contenu' => 'required|string',
            'destinataire_nom' => 'nullable|string|max:255',
            'destinataire_prenom' => 'nullable|string|max:255',
            'destinataire_titre' => 'nullable|string|max:255',
            'destinataire_adresse' => 'nullable|string',
            'pieces_jointes' => 'nullable|string',
            'date_document' => 'required|date',
            'lieu' => 'required|string|max:255',
        ]);
        
        if ($validated['client_id'] && empty($validated['destinataire_nom'])) {
            $client = DB::table('brightshell_clients')->find($validated['client_id']);
            $validated['destinataire_nom'] = $client->societe ?: $client->nom;
            $validated['destinataire_prenom'] = $client->societe ? null : $client->prenom;
            $validated['destinataire_adresse'] = $client->adresse . "\n" . $client->code_postal . ' ' . $client->ville;
        }
        
        $validated['created_at'] = now();
        $validated['updated_at'] = now();
        
        DB::table('brightshell_legals')->insert($validated);
        
        return redirect()->route('brightshell.legals')->with('success', 'Document créé avec succès.');
    }

    public function legalEdit($id)
    {
        $document = DB::table('brightshell_legals')->find($id);
        if (!$document) abort(404);
        
        $clients = DB::table('brightshell_clients')->orderBy('nom')->get();
        return view('brightshell.legals.edit', compact('document', 'clients'));
    }

    public function legalUpdate(Request $request, $id)
    {
        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'type' => 'required|string|in:attestation,courrier,autre',
            'client_id' => 'nullable|exists:brightshell_clients,id',
            'contenu' => 'required|string',
            'destinataire_nom' => 'nullable|string|max:255',
            'destinataire_prenom' => 'nullable|string|max:255',
            'destinataire_titre' => 'nullable|string|max:255',
            'destinataire_adresse' => 'nullable|string',
            'pieces_jointes' => 'nullable|string',
            'date_document' => 'required|date',
            'lieu' => 'required|string|max:255',
        ]);
        
        $validated['updated_at'] = now();
        
        DB::table('brightshell_legals')->where('id', $id)->update($validated);
        
        return redirect()->route('brightshell.legals')->with('success', 'Document mis à jour.');
    }
    
    public function legalShow($id)
    {
        $document = DB::table('brightshell_legals')
            ->leftJoin('brightshell_clients', 'brightshell_legals.client_id', '=', 'brightshell_clients.id')
            ->select('brightshell_legals.*', 'brightshell_clients.nom as client_nom', 'brightshell_clients.societe as client_societe')
            ->where('brightshell_legals.id', $id)
            ->first();
            
        if (!$document) abort(404);
        
        return view('brightshell.legals.show', compact('document'));
    }
    
    public function legalPdf($id)
    {
        $document = DB::table('brightshell_legals')->find($id);
        if (!$document) abort(404);
        
        $entreprise = $this->getEntrepriseInfo();
        $couleurs = $this->getPdfColors();
        
        $logoPath = public_path('media/brightshell/logo.png');
        $logo = file_exists($logoPath) ? $logoPath : null;
        
        $signaturePath = public_path('media/brightshell/signature.png');
        $signature = file_exists($signaturePath) ? $signaturePath : null;
        
        $html = view('brightshell.legals.pdf', compact('document', 'entreprise', 'couleurs', 'logo', 'signature'))->render();
        
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
            return $pdf->download('document-legal-' . $id . '.pdf');
        }
        
        return response($html)->header('Content-Type', 'text/html');
    }

    public function legalDelete($id)
    {
        DB::table('brightshell_legals')->where('id', $id)->delete();
        return redirect()->route('brightshell.legals')->with('success', 'Document légal supprimé.');
    }

    // ==========================================
    // DOCUMENTS (FICHIERS)
    // ==========================================
    
    public function documents()
    {
        $documents = [];
        try {
            if (\Schema::hasTable('brightshell_documents')) {
                $documents = DB::table('brightshell_documents')
                    ->leftJoin('brightshell_clients', 'brightshell_documents.client_id', '=', 'brightshell_clients.id')
                    ->select('brightshell_documents.*', 'brightshell_clients.nom as client_nom', 'brightshell_clients.societe as client_societe')
                    ->orderBy('created_at', 'desc')
                    ->get();
            }
        } catch (\Exception $e) {}
        
        return view('brightshell.documents.index', compact('documents'));
    }
    
    public function documentUpload(Request $request)
    {
        $request->validate([
            'fichier' => 'required|file|max:10240',
            'nom' => 'nullable|string|max:255',
            'client_id' => 'nullable|exists:brightshell_clients,id',
            'categorie' => 'required|string',
        ]);
        
        $file = $request->file('fichier');
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9_.-]/', '_', $originalName);
        
        $dir = public_path('media/brightshell/docs');
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        
        $size = $file->getSize();
        $file->move($dir, $fileName);
        
        DB::table('brightshell_documents')->insert([
            'nom' => $request->input('nom') ?: $originalName,
            'fichier' => $fileName,
            'extension' => $extension,
            'taille' => $size,
            'categorie' => $request->input('categorie'),
            'client_id' => $request->input('client_id'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        return redirect()->route('brightshell.documents')->with('success', 'Document uploadé.');
    }
    
    public function documentDestroy($id)
    {
        $doc = DB::table('brightshell_documents')->find($id);
        if ($doc) {
            $path = public_path('media/brightshell/docs/' . $doc->fichier);
            if (file_exists($path)) unlink($path);
            DB::table('brightshell_documents')->where('id', $id)->delete();
        }
        return redirect()->route('brightshell.documents')->with('success', 'Document supprimé.');
    }
    
    public function documentDelete($id)
    {
        DB::table('brightshell_documents')->where('id', $id)->delete();
        return redirect()->route('brightshell.documents')->with('success', 'Document supprimé.');
    }

    // ==========================================
    // STATISTIQUES
    // ==========================================
    
    public function statistiques()
    {
        $stats = [];
        
        try {
            // CA par mois (année en cours)
            $caParMois = [];
            for ($m = 1; $m <= 12; $m++) {
                $montant = 0;
                if (\Schema::hasTable('brightshell_factures')) {
                    $montant = DB::table('brightshell_factures')
                        ->whereYear('created_at', date('Y'))
                        ->whereMonth('created_at', $m)
                        ->where('statut', 'payee')
                        ->sum('montant_total');
                }
                $caParMois[$m] = $montant;
            }
            $stats['ca_par_mois'] = $caParMois;
            
            // Top clients
            $topClients = [];
            if (\Schema::hasTable('brightshell_factures') && \Schema::hasTable('brightshell_clients')) {
                $topClients = DB::table('brightshell_factures')
                    ->join('brightshell_clients', 'brightshell_factures.client_id', '=', 'brightshell_clients.id')
                    ->selectRaw('brightshell_clients.nom, brightshell_clients.societe, SUM(montant_total) as total')
                    ->where('brightshell_factures.statut', 'payee')
                    ->groupBy('brightshell_clients.id', 'brightshell_clients.nom', 'brightshell_clients.societe')
                    ->orderBy('total', 'desc')
                    ->limit(5)
                    ->get();
            }
            $stats['top_clients'] = $topClients;
            
            // Compteurs
            $stats['nb_clients'] = \Schema::hasTable('brightshell_clients') ? DB::table('brightshell_clients')->count() : 0;
            $stats['nb_devis'] = \Schema::hasTable('brightshell_devis') ? DB::table('brightshell_devis')->whereYear('created_at', date('Y'))->count() : 0;
            $stats['nb_factures'] = \Schema::hasTable('brightshell_factures') ? DB::table('brightshell_factures')->whereYear('created_at', date('Y'))->count() : 0;
            $stats['nb_projets'] = \Schema::hasTable('brightshell_projets') ? DB::table('brightshell_projets')->where('statut', 'en_cours')->count() : 0;
            
            // Prochains paiements attendus (échéances)
            $prochainsPaiements = $this->getProchainsPaiements();
            $stats['prochains_paiements'] = $prochainsPaiements;
            
            // Paiements attendus par mois (6 prochains mois)
            $stats['paiements_par_mois'] = $this->getPaiementsParMois();
            
            // Total des paiements en attente
            $stats['total_a_recevoir'] = 0;
            if (\Schema::hasTable('brightshell_echeances')) {
                $stats['total_a_recevoir'] = DB::table('brightshell_echeances')
                    ->where('est_payee', false)
                    ->sum('montant');
            }
            
            // Factures impayées (sans échéances)
            $stats['factures_impayees'] = 0;
            $stats['montant_impaye'] = 0;
            if (\Schema::hasTable('brightshell_factures')) {
                $impayees = DB::table('brightshell_factures')
                    ->where('statut', '!=', 'payee')
                    ->where('paiement_echelonne', false)
                    ->get();
                $stats['factures_impayees'] = count($impayees);
                $stats['montant_impaye'] = $impayees->sum('montant_total');
            }
            
        } catch (\Exception $e) {}
        
        return view('brightshell.statistiques.index', compact('stats'));
    }

    // ==========================================
    // FOURNISSEURS
    // ==========================================
    
    public function fournisseurs()
    {
        $fournisseurs = [];
        try {
            if (\Schema::hasTable('brightshell_fournisseurs')) {
                $fournisseurs = DB::table('brightshell_fournisseurs')
                    ->orderBy('nom')
                    ->get();
            }
        } catch (\Exception $e) {}
        
        return view('brightshell.fournisseurs.index', compact('fournisseurs'));
    }
    
    public function fournisseurCreate()
    {
        return view('brightshell.fournisseurs.form', ['fournisseur' => null]);
    }
    
    public function fournisseurStore(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'telephone' => 'nullable|string|max:20',
            'adresse' => 'nullable|string',
            'siret' => 'nullable|string|max:14',
            'notes' => 'nullable|string',
        ]);
        
        $validated['created_at'] = now();
        $validated['updated_at'] = now();
        
        DB::table('brightshell_fournisseurs')->insert($validated);
        
        return redirect()->route('brightshell.fournisseurs')->with('success', 'Fournisseur ajouté.');
    }
    
    public function fournisseurDelete($id)
    {
        DB::table('brightshell_fournisseurs')->where('id', $id)->delete();
        return redirect()->route('brightshell.fournisseurs')->with('success', 'Fournisseur supprimé.');
    }

    // ==========================================
    // ACHATS
    // ==========================================
    
    public function achatCreate()
    {
        $fournisseurs = [];
        try {
            if (\Schema::hasTable('brightshell_fournisseurs')) {
                $fournisseurs = DB::table('brightshell_fournisseurs')->orderBy('nom')->get();
            }
        } catch (\Exception $e) {}
        
        return view('brightshell.achats.form', compact('fournisseurs'));
    }
    
    public function achatStore(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'description' => 'required|string|max:255',
            'montant' => 'required|numeric|min:0',
            'fournisseur_id' => 'nullable|integer',
            'fournisseur_nom' => 'nullable|string|max:255',
            'mode_paiement' => 'nullable|string|max:50',
            'reference' => 'nullable|string|max:100',
        ]);
        
        try {
            DB::table('brightshell_achats')->insert([
                'date' => $validated['date'],
                'description' => $validated['description'],
                'montant' => $validated['montant'],
                'fournisseur' => $validated['fournisseur_nom'] ?? null,
                'categorie' => $validated['reference'] ? 'Ref: ' . $validated['reference'] : null, // Utilisation de categorie pour la ref
                'notes' => $validated['mode_paiement'] ? 'Paiement: ' . $validated['mode_paiement'] : null, // Utilisation de notes pour le mode
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Mettre à jour la trésorerie
            if (\Schema::hasTable('brightshell_tresorerie')) {
                $tresorerie = DB::table('brightshell_tresorerie')->first();
                if ($tresorerie) {
                    DB::table('brightshell_tresorerie')->where('id', $tresorerie->id)->update([
                        'solde_courant' => $tresorerie->solde_courant - $validated['montant'], // Soustraction pour un achat
                        'updated_at' => now()
                    ]);
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erreur enregistrement achat: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors de l\'enregistrement : ' . $e->getMessage());
        }
        
        return redirect()->route('brightshell.comptabilite')->with('success', 'Achat enregistré.');
    }

    // ==========================================
    // EXPORTS
    // ==========================================
    
    public function exports()
    {
        return view('brightshell.exports.index');
    }
    
    public function exportDownload($type)
    {
        $filename = 'brightshell_export_' . $type . '_' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];
        
        $callback = function() use ($type) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8
            
            switch ($type) {
                case 'clients':
                    fputcsv($handle, ['Nom', 'Prénom', 'Email', 'Téléphone', 'Société', 'SIRET', 'Adresse', 'Code Postal', 'Ville']);
                    if (\Schema::hasTable('brightshell_clients')) {
                        $clients = DB::table('brightshell_clients')->get();
                        foreach ($clients as $c) {
                            fputcsv($handle, [$c->nom, $c->prenom, $c->email, $c->telephone, $c->societe, $c->siret, $c->adresse, $c->code_postal, $c->ville]);
                        }
                    }
                    break;
                    
                case 'factures':
                    fputcsv($handle, ['Numéro', 'Date', 'Client', 'Objet', 'Montant', 'Statut']);
                    if (\Schema::hasTable('brightshell_factures')) {
                        $factures = DB::table('brightshell_factures')
                            ->leftJoin('brightshell_clients', 'brightshell_factures.client_id', '=', 'brightshell_clients.id')
                            ->select('brightshell_factures.*', 'brightshell_clients.nom as client_nom')
                            ->get();
                        foreach ($factures as $f) {
                            fputcsv($handle, [$f->numero, $f->created_at, $f->client_nom, $f->objet, $f->montant_total, $f->statut]);
                        }
                    }
                    break;
                    
                case 'recettes':
                    fputcsv($handle, ['Date', 'Description', 'Montant', 'Mode de paiement']);
                    if (\Schema::hasTable('brightshell_recettes')) {
                        $recettes = DB::table('brightshell_recettes')->whereYear('date', date('Y'))->orderBy('date')->get();
                        foreach ($recettes as $r) {
                            fputcsv($handle, [$r->date, $r->description, $r->montant, $r->mode_paiement]);
                        }
                    }
                    break;
                    
                case 'achats':
                    fputcsv($handle, ['Date', 'Description', 'Fournisseur', 'Montant', 'Mode de paiement']);
                    if (\Schema::hasTable('brightshell_achats')) {
                        $achats = DB::table('brightshell_achats')->whereYear('date', date('Y'))->orderBy('date')->get();
                        foreach ($achats as $a) {
                            fputcsv($handle, [$a->date, $a->description, $a->fournisseur, $a->montant, $a->mode_paiement]);
                        }
                    }
                    break;
            }
            
            fclose($handle);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}

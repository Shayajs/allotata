<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
use App\Models\Produit;
use App\Models\Stock;
use App\Models\Promotion;
use App\Models\ProduitImage;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class StockController extends Controller
{
    /**
     * Afficher la page de gestion des stocks
     */
    public function index($slug)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)
            ->firstOrFail();
        
        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        $produits = $entreprise->produits()
            ->with(['stock', 'images', 'imageCouverture', 'promotionActive'])
            ->orderBy('nom')
            ->get();

        return redirect()->route('entreprise.dashboard', ['slug' => $slug, 'tab' => 'stock']);
    }

    /**
     * Créer ou mettre à jour un produit
     */
    public function storeProduit(Request $request, $slug)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)
            ->firstOrFail();
        
        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'prix' => 'required|numeric|min:0',
            'est_actif' => 'nullable|boolean',
            'gestion_stock' => 'required|in:disponible_immediatement,en_attente_commandes',
            'quantite_disponible' => 'nullable|integer|min:0',
            'quantite_minimum' => 'nullable|integer|min:0',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        // Gérer le champ est_actif
        $validated['est_actif'] = $request->has('est_actif') && $request->est_actif == '1';

        try {
            $imageService = app(ImageService::class);
            
            if ($request->has('produit_id') && !empty($request->produit_id)) {
                $produit = Produit::where('id', $request->produit_id)
                    ->where('entreprise_id', $entreprise->id)
                    ->firstOrFail();
                $produit->update([
                    'nom' => $validated['nom'],
                    'description' => $validated['description'] ?? null,
                    'prix' => $validated['prix'],
                    'est_actif' => $validated['est_actif'],
                    'gestion_stock' => $validated['gestion_stock'],
                ]);
                $message = 'Le produit a été mis à jour avec succès.';
            } else {
                $produit = Produit::create([
                    'entreprise_id' => $entreprise->id,
                    'nom' => $validated['nom'],
                    'description' => $validated['description'] ?? null,
                    'prix' => $validated['prix'],
                    'est_actif' => $validated['est_actif'],
                    'gestion_stock' => $validated['gestion_stock'],
                ]);
                $message = 'Le produit a été créé avec succès.';
            }

            // Gérer le stock si gestion immédiate
            if ($validated['gestion_stock'] === 'disponible_immediatement') {
                $stock = Stock::firstOrCreate(
                    ['produit_id' => $produit->id],
                    [
                        'quantite_disponible' => $validated['quantite_disponible'] ?? 0,
                        'quantite_minimum' => $validated['quantite_minimum'] ?? 0,
                        'alerte_stock' => false,
                    ]
                );
                
                if ($request->has('quantite_disponible')) {
                    $stock->quantite_disponible = $validated['quantite_disponible'];
                }
                if ($request->has('quantite_minimum')) {
                    $stock->quantite_minimum = $validated['quantite_minimum'];
                    // Vérifier si alerte nécessaire
                    if ($stock->quantite_disponible <= $stock->quantite_minimum) {
                        $stock->alerte_stock = true;
                    }
                }
                $stock->save();
            } else {
                // Supprimer le stock si on passe en mode en_attente_commandes
                Stock::where('produit_id', $produit->id)->delete();
            }

            // Gérer l'upload des images
            if ($request->hasFile('images')) {
                $images = $request->file('images');
                $maxOrdre = ProduitImage::where('produit_id', $produit->id)->max('ordre') ?? 0;
                $hasCouverture = ProduitImage::where('produit_id', $produit->id)->where('est_couverture', true)->exists();
                
                foreach ($images as $index => $image) {
                    $imagePath = $imageService->processAndStore($image, 'produits');
                    $estCouverture = !$hasCouverture && $index === 0;
                    
                    ProduitImage::create([
                        'produit_id' => $produit->id,
                        'image_path' => $imagePath,
                        'est_couverture' => $estCouverture,
                        'ordre' => $maxOrdre + $index + 1,
                    ]);
                    
                    if ($estCouverture) {
                        $hasCouverture = true;
                    }
                }
            }

            return redirect()->route('entreprise.dashboard', ['slug' => $slug, 'tab' => 'stock'])
                ->with('success', $message);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de l\'enregistrement du produit', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->route('entreprise.dashboard', ['slug' => $slug, 'tab' => 'stock'])
                ->withInput()
                ->withErrors(['error' => 'Une erreur est survenue lors de l\'enregistrement du produit. Veuillez réessayer.']);
        }
    }

    /**
     * Supprimer un produit
     */
    public function deleteProduit(Request $request, $slug, $produitId)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)
            ->firstOrFail();
        
        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        $produit = Produit::where('id', $produitId)
            ->where('entreprise_id', $entreprise->id)
            ->firstOrFail();

        $produit->delete();

        return redirect()->route('entreprise.dashboard', ['slug' => $slug, 'tab' => 'stock'])
            ->with('success', 'Le produit a été supprimé.');
    }

    /**
     * Uploader une image pour un produit
     */
    public function uploadProduitImage(Request $request, $slug, $produitId)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)
            ->firstOrFail();
        
        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        $produit = Produit::where('id', $produitId)
            ->where('entreprise_id', $entreprise->id)
            ->firstOrFail();

        $validated = $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $imageService = app(ImageService::class);
        $imagePath = $imageService->processAndStore($request->file('image'), 'produits');

        $maxOrdre = ProduitImage::where('produit_id', $produit->id)->max('ordre') ?? 0;
        $estCouverture = ProduitImage::where('produit_id', $produit->id)->count() === 0;

        $produitImage = ProduitImage::create([
            'produit_id' => $produit->id,
            'image_path' => $imagePath,
            'est_couverture' => $estCouverture,
            'ordre' => $maxOrdre + 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Image uploadée avec succès.',
            'image' => [
                'id' => $produitImage->id,
                'path' => asset('media/' . $produitImage->image_path),
                'est_couverture' => $produitImage->est_couverture,
            ],
        ]);
    }

    /**
     * Définir une image comme couverture
     */
    public function setProduitImageCover(Request $request, $slug, $produitId, $imageId)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)
            ->firstOrFail();
        
        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        $produit = Produit::where('id', $produitId)
            ->where('entreprise_id', $entreprise->id)
            ->firstOrFail();

        $image = ProduitImage::where('id', $imageId)
            ->where('produit_id', $produit->id)
            ->firstOrFail();

        ProduitImage::where('produit_id', $produit->id)
            ->update(['est_couverture' => false]);

        $image->update(['est_couverture' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Image de couverture mise à jour.',
        ]);
    }

    /**
     * Supprimer une image de produit
     */
    public function deleteProduitImage(Request $request, $slug, $produitId, $imageId)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)
            ->firstOrFail();
        
        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        $produit = Produit::where('id', $produitId)
            ->where('entreprise_id', $entreprise->id)
            ->firstOrFail();

        $image = ProduitImage::where('id', $imageId)
            ->where('produit_id', $produit->id)
            ->firstOrFail();

        $imagePath = $image->image_path;
        $estCouverture = $image->est_couverture;

        $image->delete();

        if ($imagePath && Storage::disk('public')->exists($imagePath)) {
            try {
                $imageService = app(ImageService::class);
                $imageService->delete($imagePath);
            } catch (\Exception $e) {
                \Log::warning('Erreur lors de la suppression de l\'image de produit', [
                    'path' => $imagePath,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($estCouverture) {
            $premiereImage = ProduitImage::where('produit_id', $produit->id)
                ->orderBy('ordre')
                ->first();
            
            if ($premiereImage) {
                $premiereImage->update(['est_couverture' => true]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Image supprimée avec succès.',
        ]);
    }

    /**
     * Mettre à jour le stock
     */
    public function updateStock(Request $request, $slug, $produitId)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)
            ->firstOrFail();
        
        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        $produit = Produit::where('id', $produitId)
            ->where('entreprise_id', $entreprise->id)
            ->firstOrFail();

        if ($produit->gestion_stock !== 'disponible_immediatement') {
            return redirect()->route('entreprise.dashboard', ['slug' => $slug, 'tab' => 'stock'])
                ->with('error', 'Ce produit n\'utilise pas la gestion de stock immédiate.');
        }

        $validated = $request->validate([
            'quantite_disponible' => 'required|integer|min:0',
            'quantite_minimum' => 'nullable|integer|min:0',
        ]);

        $stock = Stock::firstOrCreate(
            ['produit_id' => $produit->id],
            ['quantite_disponible' => 0, 'quantite_minimum' => 0, 'alerte_stock' => false]
        );

        $stock->quantite_disponible = $validated['quantite_disponible'];
        if ($request->has('quantite_minimum')) {
            $stock->quantite_minimum = $validated['quantite_minimum'];
        }

        // Vérifier si alerte nécessaire
        if ($stock->quantite_disponible <= $stock->quantite_minimum) {
            $stock->alerte_stock = true;
        } else {
            $stock->alerte_stock = false;
        }

        $stock->save();

        return redirect()->route('entreprise.dashboard', ['slug' => $slug, 'tab' => 'stock'])
            ->with('success', 'Le stock a été mis à jour avec succès.');
    }

    /**
     * Créer ou mettre à jour une promotion
     */
    public function storePromotion(Request $request, $slug, $produitId)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)
            ->firstOrFail();
        
        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        $produit = Produit::where('id', $produitId)
            ->where('entreprise_id', $entreprise->id)
            ->firstOrFail();

        $validated = $request->validate([
            'prix_promotion' => 'required|numeric|min:0|lt:' . $produit->prix,
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after:date_debut',
            'est_active' => 'nullable|boolean',
        ]);

        $validated['est_active'] = $request->has('est_active') && $request->est_active == '1';
        $validated['produit_id'] = $produit->id;

        if ($request->has('promotion_id') && !empty($request->promotion_id)) {
            $promotion = Promotion::where('id', $request->promotion_id)
                ->where('produit_id', $produit->id)
                ->firstOrFail();
            $promotion->update($validated);
            $message = 'La promotion a été mise à jour avec succès.';
        } else {
            Promotion::create($validated);
            $message = 'La promotion a été créée avec succès.';
        }

        return redirect()->route('entreprise.dashboard', ['slug' => $slug, 'tab' => 'stock'])
            ->with('success', $message);
    }

    /**
     * Supprimer une promotion
     */
    public function deletePromotion(Request $request, $slug, $produitId, $promotionId)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)
            ->firstOrFail();
        
        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        $produit = Produit::where('id', $produitId)
            ->where('entreprise_id', $entreprise->id)
            ->firstOrFail();

        $promotion = Promotion::where('id', $promotionId)
            ->where('produit_id', $produit->id)
            ->firstOrFail();

        $promotion->delete();

        return redirect()->route('entreprise.dashboard', ['slug' => $slug, 'tab' => 'stock'])
            ->with('success', 'La promotion a été supprimée.');
    }
}

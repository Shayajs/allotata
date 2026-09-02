<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EntrepriseModification;
use App\Services\EntrepriseModificationService;
use Illuminate\Http\Request;

class EntrepriseModificationController extends Controller
{
    public function approve(EntrepriseModification $modification, EntrepriseModificationService $service)
    {
        if (! $modification->estEnAttente()) {
            return back()->with('error', 'Cette demande a déjà été traitée.');
        }

        $entreprise = $service->approve($modification, auth()->user());

        return redirect()
            ->route('admin.entreprises.show', $entreprise)
            ->with('success', 'Modification appliquée. La fiche publique est à jour.');
    }

    public function reject(Request $request, EntrepriseModification $modification, EntrepriseModificationService $service)
    {
        if (! $modification->estEnAttente()) {
            return back()->with('error', 'Cette demande a déjà été traitée.');
        }

        $validated = $request->validate([
            'motif_refus' => ['nullable', 'string', 'max:1000'],
        ]);

        $service->reject($modification, auth()->user(), $validated['motif_refus'] ?? null);

        return redirect()
            ->route('admin.entreprises.show', $modification->entreprise)
            ->with('success', 'Modification refusée. La fiche publique n\'a pas changé.');
    }
}

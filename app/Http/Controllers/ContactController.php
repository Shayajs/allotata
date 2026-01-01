<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactController extends Controller
{
    /**
     * Afficher le formulaire de contact
     */
    public function create()
    {
        return view('contact.create');
    }

    /**
     * Envoyer un message de contact
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'sujet' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        Contact::create([
            'nom' => $validated['nom'],
            'email' => $validated['email'],
            'sujet' => $validated['sujet'],
            'message' => $validated['message'],
            'user_id' => Auth::id(),
        ]);

        return redirect()->route('contact.create')
            ->with('success', 'Votre message a été envoyé avec succès. Nous vous répondrons dans les plus brefs délais.');
    }

    /**
     * Liste des contacts (admin uniquement)
     */
    public function index(Request $request)
    {
        $query = Contact::with('user')->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('sujet', 'like', "%{$search}%");
            });
        }

        if ($request->filled('est_lu')) {
            $query->where('est_lu', $request->est_lu === '1');
        }

        $contacts = $query->paginate(20)->withQueryString();

        return view('admin.contacts.index', compact('contacts'));
    }

    /**
     * Afficher un contact (admin uniquement)
     */
    public function show(Contact $contact)
    {
        if (!$contact->est_lu) {
            $contact->update([
                'est_lu' => true,
                'lu_at' => now(),
            ]);
        }

        return view('admin.contacts.show', compact('contact'));
    }

    /**
     * Marquer un contact comme lu/non lu (admin uniquement)
     */
    public function toggleRead(Contact $contact)
    {
        $contact->update([
            'est_lu' => !$contact->est_lu,
            'lu_at' => $contact->est_lu ? now() : null,
        ]);

        return back()->with('success', 'Statut de lecture mis à jour.');
    }

    /**
     * Supprimer un contact (admin uniquement)
     */
    public function destroy(Contact $contact)
    {
        $contact->delete();

        return redirect()->route('admin.contacts.index')
            ->with('success', 'Contact supprimé avec succès.');
    }
}

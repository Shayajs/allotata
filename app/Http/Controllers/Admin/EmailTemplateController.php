<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Models\Entreprise;
use App\Models\EmailLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class EmailTemplateController extends Controller
{
    /**
     * Afficher la liste des templates
     */
    public function index()
    {
        $templates = EmailTemplate::orderBy('name')->get();
        
        // Statistiques d'emails
        $emailsSent = EmailLog::where('created_at', '>=', now()->subDays(30))->count();
        $lastSentLog = EmailLog::latest()->first();
        $lastSent = $lastSentLog ? $lastSentLog->created_at->diffForHumans() : 'N/A';
        
        return view('admin.email-templates.index', [
            'templates' => $templates,
            'emailsSent' => $emailsSent,
            'lastSent' => $lastSent,
        ]);
    }

    /**
     * Afficher le formulaire de création
     */
    public function create()
    {
        return view('admin.email-templates.create');
    }

    /**
     * Enregistrer un nouveau template
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|max:100|unique:email_templates,type|regex:/^[a-z_]+$/',
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'description' => 'nullable|string',
            'variables' => 'nullable|string',
            'is_active' => 'boolean',
        ], [
            'type.regex' => 'Le type doit contenir uniquement des lettres minuscules et des underscores.',
            'type.unique' => 'Ce type de template existe déjà.',
        ]);

        // Convertir les variables en tableau
        $variables = [];
        if (!empty($validated['variables'])) {
            $variables = array_map('trim', explode(',', $validated['variables']));
            $variables = array_filter($variables);
        }

        EmailTemplate::create([
            'type' => $validated['type'],
            'name' => $validated['name'],
            'subject' => $validated['subject'],
            'body' => $validated['body'],
            'description' => $validated['description'] ?? null,
            'variables' => $variables,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('admin.email-templates.index')
            ->with('success', 'Template créé avec succès.');
    }

    /**
     * Afficher un template pour édition
     */
    public function edit(EmailTemplate $emailTemplate)
    {
        return view('admin.email-templates.edit', [
            'template' => $emailTemplate,
        ]);
    }

    /**
     * Mettre à jour un template
     */
    public function update(Request $request, EmailTemplate $emailTemplate)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
        ]);

        $emailTemplate->update([
            'name' => $validated['name'],
            'subject' => $validated['subject'],
            'body' => $validated['body'],
            'description' => $validated['description'] ?? $emailTemplate->description,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('admin.email-templates.edit', $emailTemplate)
            ->with('success', 'Template mis à jour avec succès.');
    }

    /**
     * Prévisualiser un template avec des données d'exemple
     */
    public function preview(EmailTemplate $emailTemplate)
    {
        // Données d'exemple pour la prévisualisation
        $exampleData = [];
        if ($emailTemplate->variables) {
            foreach ($emailTemplate->variables as $variable) {
                $exampleData[$variable] = $this->getExampleValue($variable);
            }
        }

        $replaced = $emailTemplate->replaceVariables($exampleData);

        return view('emails.template', [
            'subject' => $replaced['subject'],
            'body' => $replaced['body'],
            'template' => $emailTemplate,
        ]);
    }

    /**
     * Envoyer un email de test
     */
    public function test(Request $request, EmailTemplate $emailTemplate)
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        try {
            // Données d'exemple
            $exampleData = [];
            if ($emailTemplate->variables) {
                foreach ($emailTemplate->variables as $variable) {
                    $exampleData[$variable] = $this->getExampleValue($variable);
                }
            }

            $replaced = $emailTemplate->replaceVariables($exampleData);

            Mail::send('emails.template', [
                'subject' => $replaced['subject'],
                'body' => $replaced['body'],
            ], function ($message) use ($validated, $replaced) {
                $message->to($validated['email'])
                        ->subject('[TEST] ' . $replaced['subject']);
            });

            return redirect()->back()
                ->with('success', 'Email de test envoyé à ' . $validated['email']);

        } catch (\Exception $e) {
            Log::error('Erreur envoi email test: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Erreur lors de l\'envoi: ' . $e->getMessage());
        }
    }

    /**
     * Afficher le composeur d'emails
     */
    public function compose()
    {
        $templates = EmailTemplate::where('is_active', true)->orderBy('name')->get();
        $usersCount = User::count();
        $entreprisesCount = Entreprise::count();

        return view('admin.email-templates.compose', [
            'templates' => $templates,
            'usersCount' => $usersCount,
            'entreprisesCount' => $entreprisesCount,
        ]);
    }

    /**
     * Prévisualiser un email composé
     */
    public function previewCompose(Request $request)
    {
        $body = $request->input('body', '');
        $subject = $request->input('subject', 'Aperçu');

        return view('emails.template', [
            'subject' => $subject,
            'body' => $body,
        ]);
    }

    /**
     * Envoyer un email personnalisé
     */
    public function send(Request $request)
    {
        $validated = $request->validate([
            'recipient_type' => 'required|in:custom,users,entreprises',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'recipients' => 'required_if:recipient_type,custom|nullable|string',
            'user_filter' => 'nullable|string',
            'user_limit' => 'nullable|integer|min:1|max:1000',
            'entreprise_filter' => 'nullable|string',
            'entreprise_limit' => 'nullable|integer|min:1|max:500',
        ]);

        try {
            $recipients = $this->getRecipients($validated);

            if (empty($recipients)) {
                return redirect()->back()
                    ->with('error', 'Aucun destinataire trouvé.');
            }

            $sentCount = 0;
            $errorCount = 0;

            foreach ($recipients as $email) {
                try {
                    Mail::send('emails.template', [
                        'subject' => $validated['subject'],
                        'body' => $validated['body'],
                    ], function ($message) use ($email, $validated) {
                        $message->to($email)
                                ->subject($validated['subject']);
                    });
                    $sentCount++;
                } catch (\Exception $e) {
                    Log::error("Erreur envoi email à {$email}: " . $e->getMessage());
                    $errorCount++;
                }
            }

            $message = "{$sentCount} email(s) envoyé(s) avec succès.";
            if ($errorCount > 0) {
                $message .= " {$errorCount} erreur(s).";
            }

            return redirect()->route('admin.email-templates.compose')
                ->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Erreur envoi emails: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Erreur lors de l\'envoi: ' . $e->getMessage());
        }
    }

    /**
     * Supprimer un template
     */
    public function destroy(EmailTemplate $emailTemplate)
    {
        $emailTemplate->delete();

        return redirect()->route('admin.email-templates.index')
            ->with('success', 'Template supprimé avec succès.');
    }

    /**
     * Obtenir les destinataires selon le type
     */
    private function getRecipients(array $data): array
    {
        $recipients = [];

        switch ($data['recipient_type']) {
            case 'custom':
                // Parse custom emails
                $input = $data['recipients'] ?? '';
                $emails = preg_split('/[\s,;]+/', $input);
                foreach ($emails as $email) {
                    $email = trim($email);
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $recipients[] = $email;
                    }
                }
                break;

            case 'users':
                $query = User::query();
                $filter = $data['user_filter'] ?? 'all';
                
                switch ($filter) {
                    case 'verified':
                        $query->whereNotNull('email_verified_at');
                        break;
                    case 'active':
                        $query->where('last_login_at', '>=', now()->subDays(30));
                        break;
                    case 'with_subscription':
                        $query->whereHas('subscriptions', function ($q) {
                            $q->where('stripe_status', 'active');
                        });
                        break;
                }

                $limit = min($data['user_limit'] ?? 100, 1000);
                $recipients = $query->whereNotNull('email')
                    ->limit($limit)
                    ->pluck('email')
                    ->toArray();
                break;

            case 'entreprises':
                $query = Entreprise::query();
                $filter = $data['entreprise_filter'] ?? 'all';
                
                switch ($filter) {
                    case 'with_subscription':
                        $query->whereHas('subscriptions', function ($q) {
                            $q->where('stripe_status', 'active');
                        });
                        break;
                    case 'trial':
                        $query->where('is_trial', true)
                              ->where('trial_ends_at', '>', now());
                        break;
                    case 'expired':
                        $query->whereDoesntHave('subscriptions', function ($q) {
                            $q->where('stripe_status', 'active');
                        })->where(function ($q) {
                            $q->where('is_trial', false)
                              ->orWhere('trial_ends_at', '<', now());
                        });
                        break;
                }

                $limit = min($data['entreprise_limit'] ?? 50, 500);
                $recipients = $query->whereNotNull('email')
                    ->limit($limit)
                    ->pluck('email')
                    ->toArray();
                break;
        }

        return array_unique($recipients);
    }

    /**
     * Générer une valeur d'exemple pour une variable
     */
    private function getExampleValue(string $variable): string
    {
        $examples = [
            'nom' => 'Jean Dupont',
            'nom_client' => 'Jean Dupont',
            'nom_gerant' => 'Marie Martin',
            'nom_entreprise' => 'Mon Entreprise SARL',
            'email' => 'jean.dupont@example.com',
            'telephone' => '06 12 34 56 78',
            'date' => Carbon::now()->format('d/m/Y'),
            'date_reservation' => Carbon::now()->addDays(3)->format('d/m/Y à H:i'),
            'date_paiement' => Carbon::now()->format('d/m/Y'),
            'heure' => Carbon::now()->format('H:i'),
            'heures_avant' => '24',
            'montant' => '49,90',
            'prix' => '49,90',
            'duree' => '60',
            'nom_service' => 'Consultation Premium',
            'url' => url('/'),
            'url_dashboard' => route('dashboard'),
            'url_reservation' => url('/reservation/123'),
            'url_verification' => url('/verify/abc123'),
            'url_entreprise' => url('/entreprise/mon-entreprise'),
            'url_messagerie' => route('messagerie.index'),
            'lieu' => '15 rue de la Paix, 75001 Paris',
            'membre' => 'Sophie Leclerc',
            'lieu_html' => '<p><strong>Lieu :</strong> 15 rue de la Paix, 75001 Paris</p>',
            'membre_html' => '<p><strong>Avec :</strong> Sophie Leclerc</p>',
            'notes' => 'Client régulier, préfère le matin.',
            'notes_html' => '<p><strong>Notes :</strong> Client régulier, préfère le matin.</p>',
            'contenu_message' => 'Bonjour, je souhaitais vous informer que votre rendez-vous a été confirmé.',
            'message_annulation' => '<p>Votre réservation a été annulée par l\'entreprise.</p>',
            'remboursement_html' => '<p><strong>Remboursement :</strong> Un remboursement de 49,90 € sera effectué sous 5-7 jours ouvrés.</p>',
            'contact_entreprise' => '01 23 45 67 89 - contact@entreprise.fr',
            'total_reservations' => '25',
            'reservations_confirmees' => '22',
            'reservations_en_attente' => '3',
            'revenu_total' => '1 250,00',
            'revenu_paye' => '1 100,00',
        ];

        return $examples[$variable] ?? "Exemple de {$variable}";
    }
}
